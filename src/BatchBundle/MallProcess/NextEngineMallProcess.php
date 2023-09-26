<?php
namespace BatchBundle\MallProcess;

use Doctrine\DBAL\Connection;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbRakutenExpressConversionRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbShipmentTrackingNumberRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Entity\VSalesVoucher;
use MiscBundle\Service\SetProductSalesDistributionService;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use stdClass;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MiscBundle\Entity\TbSalesVoucherCustomerStatisticsInfo;

/**
 * モール別特殊処理 - NextEngine
 */
class NextEngineMallProcess extends BaseMallProcess
{
  const MONTH_BORDER_SHIPPING = 6; // Access SalesAnalyzer C_MONTH_BORDER_SHIPPING
  const MONTH_BORDER_ORDER_MONTH = 10; // Access SalesAnalyzer C_MONTH_BORDER_ORDERMONTH

  const API_DATA_TYPE_CSV = 'csv';
  const API_DATA_TYPE_GZ = 'gz';

  const API_QUEUE_METHOD_NAME_PRODUCT = 'SYOHIN_KIHON_CSV';
  const API_QUEUE_METHOD_NAME_ORDER = 'JYUCHU_CSV';

  const MALL_ORDER_CSV_ROW_LIMIT = 1000; // モール受注CSV 1ファイル出力件数（明細数。これを超えたあとは、出力中の伝票の明細全て出して次へ）
  const MALL_ORDER_CLICK_CSV_ROW_LIMIT = 20; // クリックポスト用受注CSV 1ファイル出力件数（明細数。これを超えたあとは、出力中の伝票の明細全て出して次へ）

  // 支払方法変換設定
  protected $paymentConvertList = null;

  // 発送方法変換設定
  protected $deliveryConvertList = null;

  /**
   * 受注明細取込 結果オブジェクト作成
   * @return stdClass
   */
  public function createImportInfo()
  {
    $importInfo = new stdClass();
    $importInfo->importCount = 0;
    $importInfo->importMinCode = null;
    $importInfo->importMaxCode = null;
    $importInfo->importMinDate = null;
    $importInfo->importMaxDate = null;

    return $importInfo;
  }

  /**
   * tb_sales_detail_tmpのデータから、個人情報の一部を加工して取得する
   * （統計的に使用する範囲のみとし、個人に紐づく可能性もある内容を保持しない）
   *
   * 現在の処理処理フロー
   * (1) 開始番号から伝票情報を、1000件ずつ取得する
   * (2) 加工した情報を、購入者情報テーブルに格納する
   * ※特にトランザクションは必要ないので、伝票番号順に処理を行う事
   * ※出荷後の伝票は対象としない（NE側で一定期間後加工されるため）
   */
  public function convertPersonalInfo($logger) {

    // この処理が原因で、受注明細の取り込みがストップするのは問題があるので、エラー発生用に try-catchを行う
    // 例外発生時はログを吐いて握りつぶす
    try {
      // 伝票取得開始
      $offset = 0;
      $limit = 1000;
      $sql = 'SELECT distinct 伝票番号 as voucher_number, 購入者電話番号 as tel, 購入者住所1 as address FROM tb_sales_detail_tmp ';
      $sql .= ' WHERE 受注状態 <> :orderStatusValueFix';
      $sql .= ' ORDER BY 伝票番号 ASC ';
      $sql .= ' LIMIT :offset, :limit';

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':orderStatusValueFix', TbSalesDetail::ORDER_STATUS_VALUE_FIX); // 出荷確定済（完了）を除く

      /** @var TbPrefectureRepository $prefRepo */
      $prefRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');
      $prefectureMap = $prefRepo->getPrefectureNameMap();

      /** @var TbSalesVoucherCustomerStatisticsInfoRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesVoucherCustomerStatisticsInfo');

      for ($i = 0; $i < 100000; $i++) { // 無限ループ避け
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (! $list) { // 対象データがなくなったら終了
          break;
        }
        foreach ($list as $data) {
          $repo->replaceData($data, $prefectureMap);
        }
        $offset = $offset + $limit;
      }
    } catch (\Throwable $t) {
      $logger->addDbLog(
          $logger->makeDbLog(null, '購入者統計情報取得でエラー発生', '処理継続')->setInformation($t->getMessage() . ':' . $t->getTraceAsString())
          , true, '購入者統計情報取得でエラーが発生しました。\nデータ取得漏れがないか確認してください', 'error'
          );
    }
  }

  /**
   * 受注明細取込・差分更新共通　一時テーブルに取り込んだデータを加工する。
   */
  public function maskPersonalInfo() {

    $dbMain = $this->getDb('main');

    // Amazon受注分の個人情報のマスク
    $sql = <<<EOD
      UPDATE tb_sales_detail_tmp
      SET
        `購入者名` = `受注番号`
        , `購入者カナ` = '********************'
        , `購入者電話番号` = '00000000000'
        , `購入者郵便番号` = '*******'
        , `購入者住所1` = '********************'
        , `購入者住所2` = '********************'
        , `購入者（住所1+住所2）` = '******************** ********************'
        , `購入者メールアドレス` = 'xxxxx@xxx.xxx'
        , `送り先名` = '********************'
        , `送り先カナ` = '********************'
        , `送り先電話番号` = '00000000000'
        , `送り先郵便番号` = '*******'
        , `送り先住所1` = '********************'
        , `送り先住所2` = '********************'
        , `送り先（住所1+住所2）` = '******************** ********************'
        , `名義人` = ''
      WHERE `店舗コード` IN (:ne_shop_id_amazon, :ne_shop_id_amazon_com)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':ne_shop_id_amazon', TbShoppingMall::NE_MALL_ID_AMAZON, \PDO::PARAM_INT);
    $stmt->bindValue(':ne_shop_id_amazon_com', TbShoppingMall::NE_MALL_ID_AMAZON_COM, \PDO::PARAM_INT);
    $stmt->execute();
  }


  /**
   * 受注明細取込 一時テーブルから明細テーブルへの更新処理
   * ※ 一時テーブル tb_sales_detail_tmp にデータが事前に準備されていることが前提
   * 一時テーブルの作成後に呼び出される。
   * 一時テーブルは、CSV取込 あるいは NextEngine API取得データのいずれかから作成される。
   * @param stdClass $importInfo
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateSalesDetailWithSalesDetailTmp($importInfo)
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $time = microtime(true);

    // '====================
    // 'sales
    // '====================
    // CSVファイル毎に繰り返される想定の実装。
    // 毎回tmpテーブルが入れ替わるため、必要な削除処理を都度行う。
    // APIからの更新では１回だけの実行
    //
    // '同一レコード(伝票番号・明細行)の削除
    $sql  = " DELETE tb_sales_detail.* ";
    $sql .= " FROM tb_sales_detail INNER JOIN tb_sales_detail_tmp ";
    $sql .= " ON tb_sales_detail.`伝票番号` = tb_sales_detail_tmp.`伝票番号` ";
    $sql .= " AND tb_sales_detail.`明細行` = tb_sales_detail_tmp.`明細行` ";
    $dbMain->query($sql);
    $logger->info('受注明細削除');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // '同一レコード(伝票番号・明細行)の削除（分析用テーブル）
    $sql  = " DELETE tb_sales_detail_analyze.* ";
    $sql .= " FROM tb_sales_detail_analyze INNER JOIN tb_sales_detail_tmp ";
    $sql .= " ON tb_sales_detail_analyze.`伝票番号` = tb_sales_detail_tmp.`伝票番号` ";
    $sql .= " AND tb_sales_detail_analyze.`明細行` = tb_sales_detail_tmp.`明細行` ";
    $dbMain->query($sql);
    $logger->info('受注明細削除（analyze）');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 無効伝票の削除
    // 楽天の「受注メール取込済」で、受注番号・明細行が一致する伝票を削除
    //
    // 楽天の複数配送先の受注では、受注メール取込で作成される伝票が、あとの受注CSV取込で
    // 何故か作りなおされて伝票番号が振り直される。
    // このため、もとの伝票番号は無効となり、削除する必要がある。（重複してしまう）
    // [削除対象]
    // * 店舗コード: 1 (Plus Nao 楽天市場店)
    // * 受注状態: 「受注メール取込済」
    // * 受注番号と明細行が一致 → 作りなおされた伝票は、先頭（親）伝票が受注番号一致、子伝票は受注番号の末尾に -h1, -h2... が付与。
    // 明細テーブル
    $sql = <<<EOD
      DELETE s
      FROM tb_sales_detail s
      INNER JOIN tb_sales_detail_tmp t ON s.`受注番号` = t.`受注番号`
                                      AND s.`明細行` = t.`明細行`
      WHERE s.`店舗コード` = 1
        AND s.`受注状態` = '受注メール取込済'
EOD;
    $dbMain->query($sql);
    // 分析テーブル
    $sql = <<<EOD
      DELETE a
      FROM tb_sales_detail_analyze a
      INNER JOIN tb_sales_detail_tmp t ON a.`受注番号` = t.`受注番号`
                                      AND a.`明細行` = t.`明細行`
      WHERE a.`店舗コード` = 1
        AND a.`受注状態` = '受注メール取込済'
EOD;
    $dbMain->query($sql);
    $logger->info('受注明細削除 - 楽天「受注メール取込済み」（sales, analyze）');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));



    // '一時テーブルからの取り込み処理
    $sql  = " INSERT INTO tb_sales_detail SELECT * FROM tb_sales_detail_tmp ";
    $dbMain->query($sql);
    $logger->info('受注明細へINSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 処理件数、最小伝票番号、最大伝票番号の更新
    $sql  = " SELECT ";
    $sql .= "    COUNT(*) AS import_count ";
    $sql .= "  , MIN(伝票番号) AS min_code ";
    $sql .= "  , MAX(伝票番号) AS max_code ";
    $sql .= "  , MIN(受注日) AS min_date ";
    $sql .= "  , MAX(受注日) AS max_date ";
    $sql .= " FROM tb_sales_detail_tmp ";
    $stmt = $dbMain->query($sql);

    if ($row = $stmt->fetch()) {
      $importInfo->importCount += $row['import_count'];

      if ($importInfo->importMinCode > $row['min_code'] || is_null($importInfo->importMinCode)) {
        $importInfo->importMinCode = $row['min_code'];
      }
      if ($importInfo->importMaxCode < $row['max_code'] || is_null($importInfo->importMaxCode)) {
        $importInfo->importMaxCode = $row['max_code'];
      }
    }

    // 最小受注日、最大受注日の更新
    $min = new \DateTime($row['min_date']);
    if (is_null($importInfo->importMinDate) || $importInfo->importMinDate > $min) {
      $importInfo->importMinDate = $min;
    }
    $max = new \DateTime($row['max_date']);
    if (is_null($importInfo->importMaxDate) || $importInfo->importMaxDate < $max) {
      $importInfo->importMaxDate = $max;
    }

    $logger->info(sprintf('%d / %d / %d', $importInfo->importCount, $importInfo->importMinCode, $importInfo->importMaxCode));
  }

  /**
   * 受注明細分析用テーブル（analyze）更新処理。
   * セット商品案分テーブルにもこの中で登録する。分析テーブルの補足情報なので連動して処理するため、ここで処理する
   * @param string $mode
   * @param stdClass $importInfo
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateSalesDetailAnalyze($mode = 'all', $importInfo = null)
  {
    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logger = $this->getLogger();

    $commonUtil = $this->getDbCommonUtil();

    $time = microtime(true);

    $fromDate = (new \DateTime())->setTime(0, 0, 0);
    $fromDate->modify('-1 year');
    $toDate = (new \DateTime())->setTime(0, 0, 0);
    $toDate->modify('+1 year');

    $logger->info("NEW updateSalesDetailAnalyze Start");
    
    // '分析用テーブルへ移送
    if ($mode == 'all') {
      $this->insertAnalyzeTableWithMinMaxCode($importInfo);
    } else if ($mode == 'use_tmp') {
      $this->insertAnalyzeTableWithTmp();
    } else {
      throw new \RuntimeException('order import invalid mode : ' . $mode);
    }

    // 分析用 データ加工処理

    //'====================
    //'出荷予定年月日 新実装
    //'====================

    // モール別設定
    $shoppingMallAmazon = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_AMAZON);
    $shoppingMallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);
    $shoppingMallRakuten = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_RAKUTEN);
    $shoppingMallRakutenMotto = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_RAKUTEN_MOTTO);
    $shoppingMallRakutenLaforest = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_RAKUTEN_LAFOREST);
    $shoppingMallRakutenDolcissimo = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_RAKUTEN_DOLCISSIMO);
    $shoppingMallRakutenGekipla = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_RAKUTEN_GEKIPLA);
    $shoppingMallYahooPlusnao = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_YAHOO);
    $shoppingMallYahooKawaemon = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_YAHOOKAWA);
    $shoppingMallYahooOtoriyose = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE);
    $shoppingMallDena = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_BIDDERS);
    $shoppingMallPpm = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_PPM);
    $shoppingMallQ10 = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_Q10);


    // 出荷予定日があればそのまま上書き利用（最優先）
    $sql  = " UPDATE tb_sales_detail_analyze AS a  ";
    $sql .= " SET a.出荷予定年月日 = 出荷予定日  ";
    $sql .= "   , a.出荷予定月日   = DATE_FORMAT(出荷予定日, '%c-%e')  ";
    $sql .= "   , a.出荷予定月     = CAST(DATE_FORMAT(出荷予定日, '%c') AS DECIMAL) ";
    $sql .= " WHERE a.受注状態 <> '出荷確定済（完了）' ";
    $sql .= "   AND a.出荷予定日 <> '0000-00-00' ";
    $sql .= "   AND a.出荷予定日 <> a.出荷予定年月日 ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 01. 出荷予定日利用');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('n-rap: ' . round($rap, 2));

    // 即納用 2営業日後のカレンダー対照表作成
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_shipping_date");
    $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_shipping_date
         (
            calendar_date DATE NOT NULL PRIMARY KEY
          , shipping_date_one_day DATE
          , shipping_date DATE
        )
        SELECT
            c1.calendar_date AS calendar_date
          , (
            SELECT
              c2.calendar_date
            FROM tb_calendar c2
            WHERE c2.calendar_date > c1.calendar_date
              AND c2.workingday <> 0
              LIMIT 1 OFFSET 0
          ) AS shipping_date_one_day
          , (
            SELECT
              c2.calendar_date
            FROM tb_calendar c2
            WHERE c2.calendar_date > c1.calendar_date
              AND c2.workingday <> 0
              LIMIT 1 OFFSET 1
          ) AS shipping_date

        FROM tb_calendar c1
        ORDER BY c1.calendar_date
EOD;
    $dbMain->query($sql);

    // Amazon 補完（即納）
    $sql  = " UPDATE tb_sales_detail_analyze AS a ";
    $sql .= " INNER JOIN tmp_work_shipping_date t ON a.受注日 = t.calendar_date ";
    $sql .= " SET a.出荷予定年月日 = t.shipping_date ";
    $sql .= "   , a.出荷予定月日   = DATE_FORMAT(t.shipping_date, '%c-%e')  ";
    $sql .= "   , a.出荷予定月     = CAST(DATE_FORMAT(t.shipping_date, '%c') AS DECIMAL) ";
    $sql .= " WHERE a.店舗コード = :neMallId ";
    $sql .= "   AND a.出荷予定月日 = '' ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $shoppingMallAmazon->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 07. Amazon モール受注CSV変換を通るが、固定で即納');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // SHOPLIST 補完（即納 ※1営業日）
    $sql  = " UPDATE tb_sales_detail_analyze AS a ";
    $sql .= " INNER JOIN tmp_work_shipping_date t ON a.受注日 = t.calendar_date ";
    $sql .= " SET a.出荷予定年月日 = t.shipping_date_one_day ";
    $sql .= "   , a.出荷予定月日   = DATE_FORMAT(t.shipping_date_one_day, '%c-%e')  ";
    $sql .= "   , a.出荷予定月     = CAST(DATE_FORMAT(t.shipping_date_one_day, '%c') AS DECIMAL) ";
    $sql .= " WHERE a.店舗コード = :neMallId ";
    $sql .= "   AND a.出荷予定月日 = '' ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $shoppingMallShoplist->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 08. SHOPLIST モール受注CSV変換を通らず、出荷予定月日なし => 即納');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 楽天 補完（即納） ※ #9296 予約販売が再開されるまでの暫定仕様
    $sql  = " UPDATE tb_sales_detail_analyze AS a ";
    $sql .= " INNER JOIN tmp_work_shipping_date t ON a.受注日 = t.calendar_date ";
    $sql .= " SET a.出荷予定年月日 = t.shipping_date ";
    $sql .= "   , a.出荷予定月日   = DATE_FORMAT(t.shipping_date, '%c-%e')  ";
    $sql .= "   , a.出荷予定月     = CAST(DATE_FORMAT(t.shipping_date, '%c') AS DECIMAL) ";
    $sql .= " WHERE a.店舗コード IN (:neMallIdRakuten, :neMallIdMotto, :neMallIdLaforest, :neMallIdDolcissimo, :neMallIdGekipla) ";
    $sql .= "   AND a.出荷予定月日 = '' ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallIdRakuten', $shoppingMallRakuten->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdMotto', $shoppingMallRakutenMotto->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdLaforest', $shoppingMallRakutenLaforest->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdDolcissimo', $shoppingMallRakutenDolcissimo->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdGekipla', $shoppingMallRakutenGekipla->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 08.1. 楽天 モール受注CSV変換を通るが、固定で即納');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // Yahoo 補完（即納） ※ #9296 予約販売が再開されるまでの暫定仕様
    $sql  = " UPDATE tb_sales_detail_analyze AS a ";
    $sql .= " INNER JOIN tmp_work_shipping_date t ON a.受注日 = t.calendar_date ";
    $sql .= " SET a.出荷予定年月日 = t.shipping_date ";
    $sql .= "   , a.出荷予定月日   = DATE_FORMAT(t.shipping_date, '%c-%e')  ";
    $sql .= "   , a.出荷予定月     = CAST(DATE_FORMAT(t.shipping_date, '%c') AS DECIMAL) ";
    $sql .= " WHERE a.店舗コード IN ( ";
    $sql .= "            :neMallIdPlusnao ";
    $sql .= "          , :neMallIdKawaemon ";
    $sql .= "          , :neMallIdOtoriyose ";

    $sql .= "          , :neMallIdDena ";
    $sql .= "          , :neMallIdPpm ";
    $sql .= "          , :neMallIdQ10 ";
    $sql .= " ) ";

    $sql .= "   AND a.出荷予定月日 = '' ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallIdPlusnao', $shoppingMallYahooPlusnao->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdKawaemon', $shoppingMallYahooKawaemon->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdOtoriyose', $shoppingMallYahooOtoriyose->getNeMallId(), \PDO::PARAM_INT);

    $stmt->bindValue(':neMallIdDena', $shoppingMallDena->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdPpm', $shoppingMallPpm->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':neMallIdQ10', $shoppingMallQ10->getNeMallId(), \PDO::PARAM_INT);

    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);

    $stmt->execute();
    $logger->info('出荷予定日更新 08.2. Yahoo・その他即納サイト モール受注CSV変換を通るが、固定で即納');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 現時点で在庫が足りていれば、即納扱い
    $sql = <<<EOD
      UPDATE
      tb_sales_detail_analyze a
      INNER JOIN tb_productchoiceitems pci ON a.`商品コード（伝票）` = pci.ne_syohin_syohin_code
      INNER JOIN ( /* 判定対象自身の商品が必ず含まれるはずなのでINNER JOINでよい */
        SELECT
            a.`商品コード（伝票）` AS ne_syohin_syohin_code
          , SUM(a.`受注数`) - SUM(a.`引当数`) AS 未引当数
        FROM tb_sales_detail_analyze a
        WHERE a.`受注状態` NOT IN ('出荷確定済（完了）')
          AND a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
        GROUP BY a.`商品コード（伝票）`
      ) T ON pci.ne_syohin_syohin_code = T.ne_syohin_syohin_code
      INNER JOIN tmp_work_shipping_date t ON a.受注日 = t.calendar_date
      SET a.出荷予定年月日 = t.shipping_date
        , a.出荷予定月日   = DATE_FORMAT(t.shipping_date, '%c-%e')
        , a.出荷予定月     = CAST(DATE_FORMAT(t.shipping_date, '%c') AS DECIMAL)
      WHERE (
           a.受注数 = a.引当数
           OR pci.`フリー在庫数` >= T.未引当数
        )
        AND a.`受注状態` NOT IN ('取込情報不足', '出荷確定済（完了）')
        AND a.`キャンセル区分` = '0'
        AND a.`明細行キャンセル` = '0'
        AND a.`出荷予定年月日` = '0000-00-00'
        AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 09. フリー在庫あり「即納」');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    // 出荷設定日履歴から取得
    $sql = <<<EOD
        UPDATE
        tb_sales_detail_analyze a
        INNER JOIN {$dbLog->getDatabase()}.tb_product_price_log pl ON a.受注日 = pl.log_date
                                                         AND a.daihyo_syohin_code = pl.daihyo_syohin_code
        SET a.出荷予定年月日 = pl.sunfactoryset
          , a.出荷予定月日   = DATE_FORMAT(pl.sunfactoryset, '%c-%e')
          , a.出荷予定月     = CAST(DATE_FORMAT(pl.sunfactoryset, '%c') AS DECIMAL)
        WHERE pl.sunfactoryset <> '0000-00-00'
          AND a.`受注状態` NOT IN ('取込情報不足', '出荷確定済（完了）')
          AND a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
          AND a.出荷予定年月日 = '0000-00-00'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 02. 出荷設定日履歴から取得');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //'====================
    //'出荷予定月日
    // 上記までで出荷予定年月日が取得できなかったものについて、従来のタイトルからの取得処理を実行
    //'====================
    // 以下、商品タイトルから 出荷予定月日 → 出荷予定月 → 出荷予定年月日を取得する、従来の処理
    $sql = <<<EOD
        UPDATE tb_sales_detail_analyze AS a SET 出荷予定月日 = ''
        WHERE a.`商品名（伝票）` NOT LIKE '%頃出荷予定%'
          AND a.商品オプション NOT LIKE '%頃出荷予定%'
          AND a.出荷予定年月日 = '0000-00-00'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定月日 推測 01. 空文字更新');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'商品名に「本日注文○月○日頃出荷予定」
    $sql = <<<EOD
        UPDATE tb_sales_detail_analyze AS a
        SET 出荷予定月日=REPLACE(REPLACE(SUBSTRING(a.`商品名（伝票）`,INSTR(a.`商品名（伝票）`,'本日注文')+CHAR_LENGTH('本日注文'),LOCATE('頃出荷予定',a.`商品名（伝票）`,INSTR(a.`商品名（伝票）`,'本日注文'))-(INSTR(a.`商品名（伝票）`,'本日注文')+CHAR_LENGTH('本日注文'))),'月','-'),'日','')
        WHERE a.`商品名（伝票）` LIKE '%本日注文%頃出荷予定%'
          AND a.出荷予定年月日 = '0000-00-00'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 02. 商品名に「本日注文○月○日頃出荷予定」');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'商品名に「◎○月○日頃出荷予定」
    $sql = <<<EOD
        UPDATE tb_sales_detail_analyze AS a
        SET 出荷予定月日=REPLACE(REPLACE(SUBSTRING(a.`商品名（伝票）`,INSTR(a.`商品名（伝票）`,'◎')+CHAR_LENGTH('◎'),LOCATE('頃出荷予定',a.`商品名（伝票）`,INSTR(a.`商品名（伝票）`,'◎'))-(INSTR(a.`商品名（伝票）`,'◎')+CHAR_LENGTH('◎'))),'月','-'),'日','')
        WHERE a.`商品名（伝票）` like '%◎%頃出荷予定%' and a.`商品名（伝票）` not like '%本日注文%頃出荷予定%'
          AND a.出荷予定年月日 = '0000-00-00'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 03. 商品名に「◎○月○日頃出荷予定」');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'商品名に「○月○日頃出荷予定」
    $sql = <<<EOD
        UPDATE tb_sales_detail_analyze AS a
        SET 出荷予定月日 = REPLACE(
               REPLACE(
                   SUBSTRING(a.`商品名（伝票）`, 1, LOCATE('頃出荷予定', a.`商品名（伝票）`, 1) - 1)
                 , '月'
                 , '-'
               )
               , '日'
               , ''
            )
        WHERE a.`商品名（伝票）` LIKE '%頃出荷予定%'
          AND a.`商品名（伝票）` NOT LIKE '%本日注文%頃出荷予定%'
          AND INSTR(a.`商品名（伝票）`, '頃出荷予定') <= 10
          AND a.出荷予定年月日 = '0000-00-00'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 04. 商品名に「○月○日頃出荷予定」');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'商品オプションに「本日注文:○月○日頃出荷予定」←コロン(:)が付いていることに注意！
    $sql = <<<EOD
        UPDATE tb_sales_detail_analyze AS a
        SET 出荷予定月日 = REPLACE(
            REPLACE(
              SUBSTRING(
                  a.`商品オプション`
                , INSTR(a.`商品オプション`, '本日注文:') + CHAR_LENGTH('本日注文:')
                , LOCATE(
                    '頃出荷予定'
                  , a.`商品オプション`
                  , INSTR(a.`商品オプション`, '本日注文:')
                ) - (
                  INSTR(a.`商品オプション`, '本日注文:') + CHAR_LENGTH('本日注文:')
                )
              )
              , '月'
              , '-'
            )
            , '日'
            , ''
          )
        WHERE a.`商品オプション` LIKE '%本日注文:%頃出荷予定%'
          AND a.出荷予定年月日 = '0000-00-00'
          AND a.受注日 >= :fromDate AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 05. 商品オプションに「本日注文:○月○日頃出荷予定」←コロン(:)が付いていることに注意！');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'即納 「1～2日以内に発送予定（店舗休業日を除く）」
    $sql  = " UPDATE tb_sales_detail_analyze AS a ";
    $sql .= " INNER JOIN tmp_work_shipping_date t ON a.受注日 = t.calendar_date ";
    $sql .= " SET 出荷予定月日 = DATE_FORMAT(t.shipping_date, '%c-%e') ";
    $sql .= " WHERE a.`商品名（伝票）` LIKE '%1～2日以内に発送予定（店舗休業日を除く）%' ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate ";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定日更新 06. 商品名に「1～2日以内に発送予定（店舗休業日を除く）」');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //'====================
    //'出荷予定月
    //'====================
    $sql  = " UPDATE tb_sales_detail_analyze AS a  ";
    $sql .= " SET 出荷予定月 = CASE ";
    $sql .= "     WHEN INSTR(出荷予定月日, '-') THEN SUBSTRING_INDEX(出荷予定月日, '-', 1)  ";
    $sql .= "     WHEN INSTR(出荷予定月日, '/') THEN SUBSTRING_INDEX(出荷予定月日, '/', 1)  ";
    $sql .= "     ELSE 0 ";
    $sql .= "   END ";
    $sql .= " WHERE 出荷予定月日 <> '' ";
    $sql .= "   AND 出荷予定月 = 0 ";
    $sql .= "   AND a.受注日 >= :fromDate AND a.受注日 <= :toDate ";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定月更新');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //'出荷予定年月日 （出荷予定月日から）
    //'====================
    $logger->info('出荷予定年月日 01. 空(0)更新 → ※不要。スキップ');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'出荷予定日が入っている場合はそれを使用
    $logger->info('出荷予定年月日 更新 02. 出荷予定日利用 → ※実行済み');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'その他は出荷予定月日から計算（年に注意）
    $sql  = " UPDATE tb_sales_detail_analyze as a ";
    $sql .= " Set a.出荷予定年月日 = ";
    $sql .= " CAST(concat(year(a.受注日)+0,'-',a.出荷予定月日) AS DATE)";
    $sql .= " WHERE a.出荷予定月 <> 0 ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND MONTH(a.受注日) < :borderMonth ";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':borderMonth', self::MONTH_BORDER_ORDER_MONTH, \PDO::PARAM_INT);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 04. その他は出荷予定月日から計算 その1');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $sql  = " UPDATE tb_sales_detail_analyze as a ";
    $sql .= " Set a.出荷予定年月日 = ";
    $sql .= " case when a.出荷予定月 <= :borderShipping ";
    $sql .= " then CAST(concat(year(a.受注日)+1,'-',a.出荷予定月日) AS DATE)";
    $sql .= " else CAST(concat(year(a.受注日)+0,'-',a.出荷予定月日) AS DATE)";
    $sql .= " end";
    $sql .= " WHERE a.出荷予定月 <> 0";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND MONTH(a.受注日) >= :borderMonth ";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':borderShipping', self::MONTH_BORDER_SHIPPING, \PDO::PARAM_INT);
    $stmt->bindValue(':borderMonth', self::MONTH_BORDER_ORDER_MONTH, \PDO::PARAM_INT);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 05. その他は出荷予定月日から計算 その2');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //' 取込情報不足、受注メール取込済み以外で、ここまで来て出荷予定年月日が入っていない場合は受注日を出荷予定年月日に設定
    //  ※この場合のみ、出荷予定月日と出荷予定月が入らないが、これは現状そのままとしておく（識別の役には立つかも）
    $sql  = " UPDATE tb_sales_detail_analyze as a ";
    $sql .= " Set a.出荷予定年月日 = 受注日 ";
    $sql .= " WHERE a.出荷予定月 = 0 ";
    $sql .= "   AND a.出荷予定年月日 = '0000-00-00' ";
    $sql .= "   AND a.受注状態 NOT IN ('取込情報不足', '受注メール取込済') ";
    $dbMain->query($sql);
    $logger->info('出荷予定年月日 更新 03. 受注日利用 ※順序を最後に変更');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //' 初回出荷予定年月日・即納フラグ挿入 ※各伝票・明細行ごとに１回のみ。
    //'====================
    $sql = <<<EOD
        INSERT INTO tb_sales_detail_first_shipping_date (
            伝票番号
          , 明細行
          , 初回出荷予定年月日
          , 即納フラグ
        )
        SELECT
            a.伝票番号
          , a.明細行
          , a.出荷予定年月日
          , CASE
              WHEN (
                    DATEDIFF(a.出荷予定年月日, a.受注日) <= 5
                 OR a.店舗コード IN (9, 18) /* Amazon, SHOPLIST */
                 OR a.`商品名（伝票）` LIKE '%1～2日以内に発送予定（店舗休業日を除く）%'
              ) THEN -1
              ELSE 0
            END AS 即納フラグ
        FROM tb_sales_detail_analyze a
        LEFT JOIN tb_sales_detail_first_shipping_date s ON a.伝票番号 = s.`伝票番号` AND a.明細行 = s.`明細行`
        WHERE a.出荷予定年月日 <> '0000-00-00'
          AND s.`伝票番号` IS NULL
          AND a.受注日 >= :fromDate
          AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 06. 初回出荷予定年月日・即納フラグ 保存');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('n-rap: ' . round($rap, 2));

    // 存在しなくなった伝票の初回出荷予定日レコードを削除
    $sql = <<<EOD
        DELETE s
        FROM tb_sales_detail_first_shipping_date s
        LEFT JOIN tb_sales_detail_analyze a ON s.伝票番号 = a.`伝票番号` AND s.明細行 = a.`明細行`
        WHERE a.`伝票番号` IS NULL
          AND a.受注日 >= :fromDate
          AND a.受注日 <= :toDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 07. 初回出荷予定年月日・即納フラグ 不要レコード削除');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('n-rap: ' . round($rap, 2));

    // 伝票ごとの初回出荷予定日を更新
    // 伝票の同梱・分割、明細キャンセルにより変わることがある
    $addSql = "";
    if ($mode == 'use_tmp') {
      $addSql = <<<EOD
        INNER JOIN (
          SELECT DISTINCT t.伝票番号
          FROM tb_sales_detail_tmp t
        ) TMP ON s.伝票番号 = TMP.伝票番号
EOD;
    }

    $sql = <<<EOD
        UPDATE
        tb_sales_detail_first_shipping_date s
        INNER JOIN (
          SELECT
              s.`伝票番号`
            , MAX(s.`初回出荷予定年月日`) AS 伝票初回出荷予定年月日
          FROM tb_sales_detail_first_shipping_date s
          INNER JOIN tb_sales_detail_analyze a USING (伝票番号, 明細行)
          WHERE a.キャンセル区分 = '0'
            AND a.明細行キャンセル = '0'
            AND a.受注日 >= :fromDate
            AND a.受注日 <= :toDate
          GROUP BY s.`伝票番号`
        ) AS T ON s.`伝票番号` = T.伝票番号
        {$addSql}
        SET s.伝票初回出荷予定年月日 = T.伝票初回出荷予定年月日
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('出荷予定年月日 更新 08. 伝票ごとの初回出荷予定日を更新');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('n-rap: ' . round($rap, 2));

    //'====================
    //'平均同梱数 メール便の送料分割用
    //'====================
    $limitDate = (new \DateTime())->setTime(0, 0, 0);
    $limitDate->modify('-1 year');

    $sql  = "";
    $sql .= " UPDATE tb_mainproducts_cal cal  ";
    $sql .= " INNER JOIN ( ";
    $sql .= "    SELECT  ";
    $sql .= "        a.daihyo_syohin_code ";
    $sql .= "      , AVG(N.num) AS bundle_num_average ";
    $sql .= "    FROM ( ";
    $sql .= "      SELECT DISTINCT  ";
    $sql .= "          a.`伝票番号` ";
    $sql .= "        , a.daihyo_syohin_code ";
    $sql .= "       FROM tb_sales_detail_analyze a ";
    $sql .= "       WHERE a.受注日 >= :limitDate ";
    $sql .= "         AND a.`受注日` <= :toDate  ";
    $sql .= "         AND a.`キャンセル区分` = '0'   ";
    $sql .= "         AND a.`明細行キャンセル` = '0'   ";
    $sql .= "         AND a.daihyo_syohin_code <> '' ";
    $sql .= "         AND a.`店舗コード` IN ( ";
    $sql .= "            '1' "; // 楽天市場店
    $sql .= "          , '2' "; // DeNAモバイル店
    $sql .= "          , '12' "; // Yahoo plusnao
    $sql .= "          , '16' "; // Plus Nao ポンパレモール店
    $sql .= "         ) ";
    $sql .= "    ) AS  a  ";
    $sql .= "    INNER JOIN ( ";
    $sql .= "       SELECT   ";
    $sql .= "          a.`伝票番号`   ";
    $sql .= "        , SUM(a.`受注数`) AS num  ";
    $sql .= "       FROM tb_sales_detail_analyze a   ";
    $sql .= "       WHERE a.受注日 >= :limitDate ";
    $sql .= "         AND a.`受注日` <= :toDate  ";
    $sql .= "         AND a.`キャンセル区分` = '0'   ";
    $sql .= "         AND a.`明細行キャンセル` = '0'   ";
    $sql .= "         AND a.daihyo_syohin_code <> '' ";
    $sql .= "         AND a.`店舗コード` IN ( ";
    $sql .= "            '1' "; // 楽天市場店
    $sql .= "          , '2' "; // DeNAモバイル店
    $sql .= "          , '12' "; // Yahoo plusnao
    $sql .= "          , '16' "; // Plus Nao ポンパレモール店
    $sql .= "         ) ";
    $sql .= "       GROUP BY a.`伝票番号` ";
    $sql .= "    ) AS N ON a.`伝票番号` = N.伝票番号 ";
    $sql .= "    GROUP BY a.daihyo_syohin_code ";
    $sql .= " ) AS T ON cal.daihyo_syohin_code = T.daihyo_syohin_code ";

    if ($mode == 'use_tmp') {
      $sql .= "    INNER JOIN ( ";
      $sql .= "       SELECT  ";
      $sql .= "         DISTINCT a.daihyo_syohin_code  ";
      $sql .= "       FROM tb_sales_detail_analyze a  ";
      $sql .= "       INNER JOIN tb_sales_detail_tmp t USING (伝票番号, 明細行)  ";
      $sql .= "    ) AS TMP ON T.daihyo_syohin_code = TMP.daihyo_syohin_code ";
    }

    $sql .= " SET cal.bundle_num_average = ROUND(T.bundle_num_average, 2) ";

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':limitDate', $limitDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('平均同梱数更新');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('n-rap: ' . round($rap, 2));
    
    // セット商品案分テーブルにも登録　エラーが出ても当面はログだけ出して継続（本番で正常処理が確認出来たら try - catch は除去してよい）
    try {
      /** @var $service SetProductSalesDistritubionService */
      $setDistributionService = $this->getContainer()->get('misc.service.set_product_sales_distribution');
      if ($mode == 'use_tmp') {
        $setDistributionService->recalcurateSetDistributeInfo($importInfo->importMinCode,  $importInfo->importMaxCode, true);
      } else {
        $setDistributionService->recalcurateSetDistributeInfo($importInfo->importMinCode,  $importInfo->importMaxCode, false);
      }
    } catch (\Exception $e) {
      $logger->error('受注明細取込（差分更新） 処理 セット商品案分処理エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog('受注明細取込（差分更新）処理', 'セット商品案分処理エラー', '処理継続')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, 'セット商品案分処理エラーが発生しました', 'error');
    }


    $logger->info("NEW updateSalesDetailAnalyze END");
  }

  /**
   * 分析用テーブルへのコピー処理 CSV版：minCode, maxCodeで絞込
   * @param stdClass $importInfo
   * @throws \Doctrine\DBAL\DBALException
   */
  private function insertAnalyzeTableWithMinMaxCode($importInfo)
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $time = microtime(true);

    $logger->info(sprintf('min -> max : %s -> %s', ($importInfo ? $importInfo->importMinCode : '-'), ($importInfo ? $importInfo->importMaxCode : '-')));

    $addSql = "";
    if ($importInfo && $importInfo->importMinCode && $importInfo->importMaxCode) {
      $addSql = <<<EOD
 WHERE D.伝票番号 >= :minCode
   AND D.伝票番号 <= :maxCode
EOD;
    }

    $sql = <<<EOD

 INSERT IGNORE INTO tb_sales_detail_analyze (
     伝票番号
   , 明細行
   , 受注番号
   , 受注日
   , 出荷確定日
   , 取込日
   , 入金日
   , 配達希望日
   , 出荷予定日
   , 納品書印刷指示日
   , キャンセル日
   , キャンセル区分
   , 入金額
   , 発送伝票番号
   , 店舗名
   , 店舗コード
   , 発送方法
   , 配送方法コード
   , 支払方法
   , 支払方法コード
   , 総合計
   , 商品計
   , 税金
   , 発送代
   , 手数料
   , 他費用
   , ポイント数
   , 受注状態
   , 受注分類タグ
   , 確認チェック
   , 発送伝票備考欄
   , ピッキング指示
   , 納品書特記事項
   , 顧客cd
   , 顧客区分
   , 入金状況
   , 名義人
   , 承認状況
   , 承認額
   , 納品書発行日
   , 重要チェック
   , 重要チェック者
   , 明細行キャンセル
   , `商品コード（伝票）`

   , daihyo_syohin_code

   , `商品名（伝票）`
   , 商品オプション
   , 受注数
   , 引当数
   , 引当日
   , 売単価
   , 小計
   , 元単価
   , 掛率

   , 受注年
   , 受注月

   , 購入者名
   , 購入者電話番号
 )
 SELECT
     D.伝票番号
   , D.明細行
   , D.受注番号
   , D.受注日
   , D.出荷確定日
   , D.取込日
   , D.入金日
   , D.配達希望日
   , D.出荷予定日
   , D.納品書印刷指示日
   , D.キャンセル日
   , D.キャンセル区分
   , D.入金額
   , D.発送伝票番号
   , D.店舗名
   , D.店舗コード
   , D.発送方法
   , D.配送方法コード
   , D.支払方法
   , D.支払方法コード
   , D.総合計
   , D.商品計
   , D.税金
   , D.発送代
   , D.手数料
   , D.他費用
   , D.ポイント数
   , D.受注状態
   , D.受注分類タグ
   , D.確認チェック
   , D.発送伝票備考欄
   , D.ピッキング指示
   , D.納品書特記事項
   , D.顧客cd
   , D.顧客区分
   , D.入金状況
   , D.名義人
   , D.承認状況
   , D.承認額
   , D.納品書発行日
   , D.重要チェック
   , D.重要チェック者
   , D.明細行キャンセル
   , D.`商品コード（伝票）`

   , COALESCE(P.daihyo_syohin_code, '')

   , D.`商品名（伝票）`
   , D.商品オプション
   , D.受注数
   , D.引当数
   , D.引当日
   , D.売単価
   , D.小計
   , D.元単価
   , D.掛率

   , YEAR(D.受注日) AS 受注年
   , MONTH(D.受注日) AS 受注月

   , D.購入者名
   , CASE
         WHEN D.購入者電話番号 = '' OR D.購入者電話番号 IS NULL
         THEN '(none)'
         ELSE D.購入者電話番号
     END AS 購入者電話番号

 FROM tb_sales_detail AS D
 LEFT JOIN tb_productchoiceitems P ON D.`商品コード（伝票）` = P.ne_syohin_syohin_code /* daihyo_syohin_code 用 */
 {$addSql}
EOD;
    $stmt = $dbMain->prepare($sql);
    if ($addSql) {
      $stmt->bindValue(':minCode', $importInfo->importMinCode, \PDO::PARAM_INT);
      $stmt->bindValue(':maxCode', ($importInfo->importMaxCode ? $importInfo->importMaxCode : 2147483647), \PDO::PARAM_INT);
    }
    $stmt->execute();

    $logger->info('受注明細(Analyze)へINSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
  }

  /**
   * 分析用テーブルへのコピー処理 api版：tb_sales_detail_tmp に存在するもののみ対象
   * ※ 一時テーブル tb_sales_detail_tmp にデータが事前に準備されていることが前提
   * @throws \Doctrine\DBAL\DBALException
   */
  private function insertAnalyzeTableWithTmp()
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $time = microtime(true);

    $sql = <<<EOD

 INSERT IGNORE INTO tb_sales_detail_analyze (
     伝票番号
   , 明細行
   , 受注番号
   , 受注日
   , 出荷確定日
   , 取込日
   , 入金日
   , 配達希望日
   , 出荷予定日
   , 納品書印刷指示日
   , キャンセル日
   , キャンセル区分
   , 入金額
   , 発送伝票番号
   , 店舗名
   , 店舗コード
   , 発送方法
   , 配送方法コード
   , 支払方法
   , 支払方法コード
   , 総合計
   , 商品計
   , 税金
   , 発送代
   , 手数料
   , 他費用
   , ポイント数
   , 受注状態
   , 受注分類タグ
   , 確認チェック
   , 発送伝票備考欄
   , ピッキング指示
   , 納品書特記事項
   , 顧客cd
   , 顧客区分
   , 入金状況
   , 名義人
   , 承認状況
   , 承認額
   , 納品書発行日
   , 重要チェック
   , 重要チェック者
   , 明細行キャンセル
   , `商品コード（伝票）`

   , daihyo_syohin_code

   , `商品名（伝票）`
   , 商品オプション
   , 受注数
   , 引当数
   , 引当日
   , 売単価
   , 小計
   , 元単価
   , 掛率

   , 受注年
   , 受注月

   , 購入者名
   , 購入者電話番号
 )
 SELECT
     D.伝票番号
   , D.明細行
   , D.受注番号
   , D.受注日
   , D.出荷確定日
   , D.取込日
   , D.入金日
   , D.配達希望日
   , D.出荷予定日
   , D.納品書印刷指示日
   , D.キャンセル日
   , D.キャンセル区分
   , D.入金額
   , D.発送伝票番号
   , D.店舗名
   , D.店舗コード
   , D.発送方法
   , D.配送方法コード
   , D.支払方法
   , D.支払方法コード
   , D.総合計
   , D.商品計
   , D.税金
   , D.発送代
   , D.手数料
   , D.他費用
   , D.ポイント数
   , D.受注状態
   , D.受注分類タグ
   , D.確認チェック
   , D.発送伝票備考欄
   , D.ピッキング指示
   , D.納品書特記事項
   , D.顧客cd
   , D.顧客区分
   , D.入金状況
   , D.名義人
   , D.承認状況
   , D.承認額
   , D.納品書発行日
   , D.重要チェック
   , D.重要チェック者
   , D.明細行キャンセル
   , D.`商品コード（伝票）`

   , COALESCE(P.daihyo_syohin_code, '')

   , D.`商品名（伝票）`
   , D.商品オプション
   , D.受注数
   , D.引当数
   , D.引当日
   , D.売単価
   , D.小計
   , D.元単価
   , D.掛率

   , YEAR(D.受注日) AS 受注年
   , MONTH(D.受注日) AS 受注月

   , D.購入者名
   , CASE
         WHEN D.購入者電話番号 = '' OR D.購入者電話番号 IS NULL
         THEN '(none)'
         ELSE D.購入者電話番号
     END AS 購入者電話番号

 FROM tb_sales_detail AS D
 INNER JOIN tb_sales_detail_tmp AS T ON D.伝票番号 = T.伝票番号 AND D.明細行 = T.明細行
 LEFT JOIN tb_productchoiceitems P ON D.`商品コード（伝票）` = P.ne_syohin_syohin_code /* daihyo_syohin_code 用 */
EOD;
    $dbMain->query($sql);

    $logger->info('受注明細(Analyze)へINSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
  }

  /// 受注データ取込一時テーブル 作成処理
  /**
   * @throws \Doctrine\DBAL\DBALException
   */
  public function renewTableTbOrderDataTmp()
  {
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      CREATE TABLE IF NOT EXISTS tb_order_data_tmp (
          店舗名 VARCHAR (255)
        , 伝票番号 INT (10)
        , 受注番号 VARCHAR (255)
        , 受注日 DATETIME
        , 取込日 DATETIME
        , 受注チェック VARCHAR (255)
        , 受注チェック担当者 VARCHAR (255)
        , 確認チェック VARCHAR (255)
        , キャンセル VARCHAR (255)
        , 受注キャンセル日 VARCHAR (255)
        , 受注状態 VARCHAR (255)
        , 受注担当者 VARCHAR (255)
        , 発送方法 VARCHAR (255)
        , 支払方法 VARCHAR (255)
        , 合計金額 VARCHAR (255)
        , 税金 VARCHAR (255)
        , 手数料 VARCHAR (255)
        , 送料 VARCHAR (255)
        , その他 VARCHAR (255)
        , ポイント VARCHAR (255)
        , 承認金額 VARCHAR (255)
        , 備考 MEDIUMTEXT
        , 入金金額 VARCHAR (255)
        , 入金区分 VARCHAR (255)
        , 入金日 VARCHAR (255)
        , 納品書印刷指示日 VARCHAR (255)
        , 納品書発行日 VARCHAR (255)
        , 納品書備考 MEDIUMTEXT
        , 出荷日 VARCHAR (255)
        , 出荷予定日 VARCHAR (255)
        , 出荷担当者 VARCHAR (255)
        , 作業者欄 MEDIUMTEXT
        , ピック指示内容 VARCHAR (255)
        , ラベル発行日 VARCHAR (255)
        , 配送日 VARCHAR (255)
        , 配送時間帯 VARCHAR (255)
        , 配送伝票番号 VARCHAR (255)
        , クレジット区分 VARCHAR (255)
        , 名義人 VARCHAR (255)
        , 有効期限 VARCHAR (255)
        , 承認番号 VARCHAR (255)
        , 承認区分 VARCHAR (255)
        , 承認日 VARCHAR (255)
        , オーソリ名 VARCHAR (255)
        , 顧客区分 VARCHAR (255)
        , 顧客コード VARCHAR (255)
        , 購入者名 VARCHAR (255)
        , 購入者カナ VARCHAR (255)
        , 購入者郵便番号 VARCHAR (255)
        , 購入者住所1 VARCHAR (255)
        , 購入者住所2 VARCHAR (255)
        , 購入者電話番号 VARCHAR (255)
        , 購入者ＦＡＸ VARCHAR (255)
        , 購入者メールアドレス VARCHAR (255)
        , 発送先名 VARCHAR (255)
        , 発送先カナ VARCHAR (255)
        , 発送先郵便番号 VARCHAR (255)
        , 発送先住所1 VARCHAR (255)
        , 発送先住所2 VARCHAR (255)
        , 発送先電話番号 VARCHAR (255)
        , 発送先ＦＡＸ VARCHAR (255)
        , 配送備考 VARCHAR (255)
        , 商品コード VARCHAR (255)
        , 商品名 VARCHAR (255) NOT NULL DEFAULT ''
        , 受注数 VARCHAR (255)
        , 商品単価 VARCHAR (255)
        , 掛率 VARCHAR (255)
        , 小計 VARCHAR (255)
        , 商品オプション VARCHAR (255)
        , 引当数 VARCHAR (255)
        , 引当日 VARCHAR (255)
        , 出荷予定月日 VARCHAR(5) NOT NULL DEFAULT ''
        , 出荷予定月 VARCHAR(2) NOT NULL DEFAULT ''
        , INDEX INDEX_1(伝票番号)
      );
EOD;

    $dbMain->query($sql);

    $dbMain->query("TRUNCATE tb_order_data_tmp");
    return;
  }

  /**
   * tb_order_data_mainadd 更新
   * 従来は tb_order_data から作成されていたが、差分取込対応のため
   * tb_sales_detail_analyze から作成する。
   * また、パフォーマンスのため、tb_order_data_tmp に存在する伝票番号のみ更新対象とする
   */
  public function updateOrderDataMainaddWithSalesDetailAnalyze()
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $time = microtime(true);

    // 更新対象伝票番号テーブル
    $dbMain->query("DROP /* TEMPORARY */ TABLE IF EXISTS tmp_work_update_order_data_mainadd");
    $sql = <<<EOD
      CREATE /* TEMPORARY */ TABLE tmp_work_update_order_data_mainadd (
        伝票番号 INT NOT NULL PRIMARY KEY
      ) ENGINE=InnoDB DEFAULT CHARSET utf8
      SELECT
        DISTINCT 伝票番号
      FROM tb_order_data_tmp
EOD;
    $dbMain->query($sql);

    // '====================
    // 'order_data_mainadd
    // '====================
    $sql = <<<EOD
      INSERT IGNORE INTO tb_order_data_mainadd ( 伝票番号 )
      SELECT
        伝票番号
      FROM tmp_work_update_order_data_mainadd
EOD;
    $dbMain->query($sql);
    $logger->info('受注データ取込 tb_order_data_mainadd レコード作成');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 区分値変換SQL 作成
    $kubunParams = [];

    // 確認チェック
    $kubunConfirmCheckSql = "CASE ";
    foreach($this->commonUtil->getKubunList('確認チェック') as $value => $name) {
      $holderValue = sprintf(':kubunConfirmCheckValue%s', $value);
      $holderName = sprintf(':kubunConfirmCheckName%s', $value);
      $kubunParams[$holderValue] = $value;
      $kubunParams[$holderName] = $name;
      $kubunConfirmCheckSql .= sprintf(" WHEN OD.確認チェック = %s THEN %s ", $holderValue, $holderName);
    }
    $kubunConfirmCheckSql .= " ELSE '' END ";

    // 入金区分
    $kubunPaymentSql = "CASE ";
    foreach($this->commonUtil->getKubunList('入金区分') as $value => $name) {
      $holderValue = sprintf(':kubunPaymentValue%s', $value);
      $holderName = sprintf(':kubunPaymentName%s', $value);
      $kubunParams[$holderValue] = $value;
      $kubunParams[$holderName] = $name;
      $kubunPaymentSql .= sprintf(" WHEN OD.入金状況 = %s THEN %s ", $holderValue, $holderName); // sales_detailではカラム名が違う
    }
    $kubunPaymentSql .= " ELSE '' END ";

    $sql = <<<EOD
      UPDATE tb_order_data_mainadd AS ODM
        INNER JOIN (
          SELECT
              OD.伝票番号
            , MAX({$kubunConfirmCheckSql}) AS 確認チェック
            , MAX(
                CASE
                  WHEN OD.`キャンセル区分` = '0' AND OD.`明細行キャンセル` = '0' THEN OD.出荷予定年月日
                  ELSE NULL
                END
            ) AS 出荷予定日
            , MAX(OD.受注状態) AS 受注状態
            , MAX(OD.支払方法) AS 支払方法
            , MAX({$kubunPaymentSql}) AS 入金区分
            , MIN(OD.受注日) AS 受注日
          FROM tb_sales_detail_analyze AS OD
          INNER JOIN tmp_work_update_order_data_mainadd T ON OD.`伝票番号` = T.`伝票番号`
          INNER JOIN tb_sales_detail_first_shipping_date F ON OD.伝票番号 = F.伝票番号 AND OD.明細行 = F.明細行
          GROUP BY OD.伝票番号
        ) AS ODSUM ON ODM.伝票番号 = ODSUM.伝票番号
      SET
          ODM.確認チェック   = ODSUM.確認チェック
        , ODM.shipping_time = ODSUM.出荷予定日
        , ODM.受注状態 = ODSUM.受注状態
        , ODM.支払方法 = ODSUM.支払方法
        , ODM.入金区分 = ODSUM.入金区分
        , ODM.確認チェック_checked = 0
        , ODM.purchase_quantity = 0
        , ODM.delivery_terms = NULL
        , ODM.受注日 = ODSUM.受注日
EOD;

    $stmt = $dbMain->prepare($sql);
    // 区分値変換パラメータ
    foreach($kubunParams as $key => $value) {
      $stmt->bindValue($key, $value, \PDO::PARAM_STR); // すべて文字列
    }
    $stmt->execute();

    $logger->info('受注データ取込 tb_order_data_mainadd レコード更新 その１');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $logger->info('受注データ取込 tb_order_data_mainadd レコード更新 その２ shipping_time => 初回出荷予定日テーブル情報利用のため省略');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $sql = <<<EOD
      UPDATE tb_order_data_mainadd AS ODM
      INNER JOIN tmp_work_update_order_data_mainadd T ON ODM.`伝票番号` = T.`伝票番号`
      SET ODM.check_for_dates_confirmed = NULL
      WHERE ODM.確認チェック = '確認済（表示）'
EOD;
    $dbMain->query($sql);
    $logger->info('受注データ取込 tb_order_data_mainadd レコード更新 その４ check_for_dates_confirmed = NULL');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $sql = <<<EOD
      UPDATE tb_order_data_mainadd AS ODM
      INNER JOIN tmp_work_update_order_data_mainadd T ON ODM.`伝票番号` = T.`伝票番号`
      SET ODM.check_for_dates_confirmed  = CURDATE()
      WHERE ODM.check_for_dates_confirmed IS NULL
EOD;
    $dbMain->query($sql);
    $logger->info('受注データ取込 tb_order_data_mainadd レコード更新 その５ check_for_dates_confirmed = CURDATE()');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $sql = <<<EOD
      UPDATE tb_order_data_mainadd AS ODM
        INNER JOIN (
          SELECT
              OD.伝票番号
            , MAX(OD.作業用欄) AS 作業者欄
          FROM tb_sales_detail AS OD /* _analyzeには作業用欄がない */
          INNER JOIN tmp_work_update_order_data_mainadd T ON OD.`伝票番号` = T.`伝票番号`
          GROUP BY OD.伝票番号
        ) AS ODSUM  ON ODM.伝票番号 = ODSUM.伝票番号
      SET ODM.check_for_dates_confirmed = CURDATE()
        , ODM.作業者欄_former = ODSUM.作業者欄
      WHERE
        IFNULL(ODM.作業者欄_former, '') <> IFNULL(ODSUM.作業者欄, '')

EOD;
    $dbMain->query($sql);
    $logger->info('受注データ取込 tb_order_data_mainadd レコード更新 その６ check_for_dates_confirmed, 作業者欄');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $sql = <<<EOD
      UPDATE tb_order_data_mainadd AS ODM
        INNER JOIN (
          SELECT
              OD.伝票番号
            , SUM(OD.受注数) AS 受注合計
            , SUM(OD.引当数) AS 引当合計
            , MAX(OD.引当日) AS 最遅引当日
          FROM tb_sales_detail_analyze AS OD
          INNER JOIN tmp_work_update_order_data_mainadd T ON OD.`伝票番号` = T.`伝票番号`
          WHERE OD.キャンセル区分 = '0'
            AND OD.明細行キャンセル = '0'
          GROUP BY OD.伝票番号
        ) AS ODSUM ON ODM.伝票番号 = ODSUM.伝票番号
      SET ODM.purchase_quantity = ODSUM.受注合計
        , ODM.delivery_terms = CASE
                                  WHEN ODSUM.受注合計 = ODSUM.引当合計 THEN ODSUM.最遅引当日
                                  ELSE ODM.delivery_terms
                               END
EOD;
    $dbMain->query($sql);
    $logger->info('受注データ取込 tb_order_data_mainadd レコード更新 その７ delivery_terms, purchase_quantity');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
  }


  /**
   * 受注明細データから 商品引当数・受注数・出荷予定取置数更新処理
   */
  public function updateProductchoiceitemsAssignedNum()
  {
    $dbMain = $this->getDb('main');

    // 引当数更新
    $sql = <<<EOD
      UPDATE tb_productchoiceitems
      SET 引当数 = 0
      WHERE 引当数 > 0
EOD;
    $dbMain->query($sql);

    // いろいろ無駄っぽいWHEREをつけているのは、ロック待ちによるタイムアウト回避のため。（無いとインデックスがうまく使われない？）
    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN (
        SELECT
            a.`商品コード（伝票）`
          , SUM(引当数) AS 引当数
        FROM tb_sales_detail_analyze a
        WHERE a.`受注状態` <> '出荷確定済（完了）'
          AND a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
        GROUP BY a.`商品コード（伝票）`
        HAVING 引当数 > 0
      ) T  ON  pci.ne_syohin_syohin_code = T.`商品コード（伝票）`
      SET pci.引当数 = T.引当数
      WHERE pci.引当数 = 0
        AND pci.引当数 <> T.引当数
EOD;
    $dbMain->query($sql);

    // 受注数更新
    $sql = <<<EOD
      UPDATE tb_productchoiceitems
      SET 受注数 = 0
      WHERE 受注数 > 0
EOD;
    $dbMain->query($sql);

    // いろいろ無駄っぽいWHEREをつけているのは、ロック待ちによるタイムアウト回避のため。（無いとインデックスがうまく使われない？）
    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN (
        SELECT
            a.`商品コード（伝票）`
          , SUM(受注数) AS 受注数
        FROM tb_sales_detail_analyze a
        WHERE a.`受注状態` <> '出荷確定済（完了）'
          AND a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
        GROUP BY a.`商品コード（伝票）`
        HAVING 受注数 > 0
      ) T  ON  pci.ne_syohin_syohin_code = T.`商品コード（伝票）`
      SET pci.受注数 = T.受注数
      WHERE pci.受注数 = 0
        AND pci.受注数 <> T.受注数
EOD;
    $dbMain->query($sql);

    // 出荷予定取置数更新
    $sql = <<<EOD
      UPDATE tb_productchoiceitems
      SET 出荷予定取置数 = 0
      WHERE 出荷予定取置数 > 0
EOD;
    $dbMain->query($sql);

    // こちらはVIEWでやってみる。ダメなら↑のように直接を試す。
    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN v_product_stock_shipping_reserve v ON  pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
      SET pci.出荷予定取置数 = v.reserve_num
      WHERE pci.出荷予定取置数 = 0
        AND pci.出荷予定取置数 <> v.reserve_num
EOD;
    $dbMain->query($sql);

    // フリー在庫再計算
    // generated column 実装により削除予定
    // $this->recalculateFreeStock();

    // 予約フリー在庫の再計算は、NextEngine CSV出力でのみ必要なため、ここでは省略する。
  }

  /**
   * 販売不可在庫数 再計算
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateNotForSaleStock()
  {
    $dbMain = $this->getDb('main');

    // 販売不可在庫数
    $sql = <<<EOD
      UPDATE tb_productchoiceitems
      SET 販売不可在庫数 = 0
      WHERE 販売不可在庫数 > 0
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN (
        SELECT
            pl.ne_syohin_syohin_code
          , SUM(pl.stock) AS stock
        FROM tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
        INNER JOIN tb_warehouse w ON l.warehouse_id = w.id
        INNER JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        WHERE pl.stock > 0
          AND pl.position >= 0
          AND (
               (m.fba_multi_flag <> 0 AND l.warehouse_id <> :fbaMultiWarehouseId)
            OR ( w.sale_enabled = 0 )
          )
        GROUP BY pl.ne_syohin_syohin_code
      ) T ON pci.ne_syohin_syohin_code = T.ne_syohin_syohin_code
      SET pci.`販売不可在庫数` = T.stock
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fbaMultiWarehouseId', TbWarehouseRepository::FBA_MULTI_WAREHOUSE_ID, \PDO::PARAM_INT);
    $stmt->execute();

  }

  /**
   * 受注明細データから最終受注日を更新。取り込む伝票番号の最小・最大を指定して最終受注日を更新する。
   *
   * ※範囲外の伝票に、より新しい受注日の受注があるかもしれないので、
   * 既に設定されている最終受注日が、今回の最終受注日より新しい場合、更新しない
   */
  public function updateLastOrderDateWithMinMax($importInfo) {
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      INNER JOIN (
        SELECT pci.daihyo_syohin_code, MAX(sa.受注日) as last_order_date
        FROM tb_sales_detail_analyze sa
        INNER JOIN tb_productchoiceitems pci ON sa.商品コード（伝票） = pci.ne_syohin_syohin_code
        WHERE
          sa.伝票番号 >= :minCode
          AND sa.伝票番号 <= :maxCode
        GROUP BY pci.daihyo_syohin_code) summary ON cal.daihyo_syohin_code = summary.daihyo_syohin_code
      SET cal.last_order_date = summary.last_order_date
      WHERE cal.last_order_date IS NULL OR cal.last_order_date < summary.last_order_date
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':minCode', $importInfo->importMinCode, \PDO::PARAM_INT);
    $stmt->bindValue(':maxCode', $importInfo->importMaxCode, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 受注明細データの一時テーブルから最終受注日を更新。tmpテーブルのデータを元に、最終受注日を更新する。
   *
   * ※範囲外の伝票に、より新しい受注日の受注があるかもしれないので、
   * 既に設定されている最終受注日が、今回の最終受注日より新しい場合、更新しない
   */
  public function updateLastOrderDateWithTmp() {
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      INNER JOIN (
        SELECT pci.daihyo_syohin_code, MAX(sa.受注日) as last_order_date
        FROM tb_sales_detail_tmp sa
        INNER JOIN tb_productchoiceitems pci ON sa.商品コード（伝票） = pci.ne_syohin_syohin_code
        GROUP BY pci.daihyo_syohin_code) summary ON cal.daihyo_syohin_code = summary.daihyo_syohin_code
      SET cal.last_order_date = summary.last_order_date
      WHERE cal.last_order_date IS NULL OR cal.last_order_date < summary.last_order_date
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

//  /**
//   * ピッキング引当数 一括更新
//   * トリガ実装により削除予定
//   * @param array $productCodes
//   * @throws \Doctrine\DBAL\DBALException
//   */
//  public function updateStockPickingAssignNum($productCodes = [])
//  {
//    $dbMain = $this->getDb('main');
//
//    // 指定商品のみ更新
//    if ($productCodes) {
//      $quoted = [];
//      foreach($productCodes as $i => $code) {
//        $quoted[] = $dbMain->quote($code);
//      }
//      $productCodesStr = implode(', ', $quoted);
//
//      $sql = <<<EOD
//      UPDATE
//      tb_productchoiceitems pci
//      LEFT JOIN (
//        SELECT
//            dpl.`商品コード`
//          , SUM(dpl.総ピッキング数) AS ピッキング引当数
//        FROM tb_delivery_picking_list dpl
//        WHERE dpl.picking_status = :pickingStatusNone
//          AND dpl.`商品コード` IN ( {$productCodesStr} )
//        GROUP BY dpl.`商品コード`
//      ) T ON pci.ne_syohin_syohin_code = T.`商品コード`
//      SET pci.ピッキング引当数 = COALESCE(T.ピッキング引当数, 0)
//      WHERE pci.ne_syohin_syohin_code IN ( {$productCodesStr} )
//EOD;
//      $stmt = $dbMain->prepare($sql);
//      $stmt->bindValue(':pickingStatusNone', TbDeliveryPickingListRepository::PICKING_STATUS_NONE, \PDO::PARAM_INT);
//      $stmt->execute();
//
//      // 全更新
//    } else {
//
//      // ピッキング引当数更新
//      $sql = <<<EOD
//        UPDATE tb_productchoiceitems
//        SET ピッキング引当数 = 0
//        WHERE ピッキング引当数 > 0
//EOD;
//      $dbMain->query($sql);
//
//      $sql = <<<EOD
//      UPDATE
//      tb_productchoiceitems pci
//      INNER JOIN (
//        SELECT
//            dpl.`商品コード`
//          , SUM(dpl.総ピッキング数) AS ピッキング引当数
//        FROM tb_delivery_picking_list dpl
//        WHERE dpl.picking_status = :pickingStatusNone
//        GROUP BY dpl.`商品コード`
//      ) T ON pci.ne_syohin_syohin_code = T.`商品コード`
//      SET pci.ピッキング引当数 = T.ピッキング引当数
//EOD;
//      $stmt = $dbMain->prepare($sql);
//      $stmt->bindValue(':pickingStatusNone', TbDeliveryPickingListRepository::PICKING_STATUS_NONE, \PDO::PARAM_INT);
//      $stmt->execute();
//    }
//
//
//    // フリー在庫再計算
//    $this->recalculateFreeStock($productCodes);
//  }
//
//  /**
//   * フリー在庫数再計算
//   * @param array $productCodes
//   * @throws \Doctrine\DBAL\DBALException
//   */
//  public function recalculateFreeStock($productCodes = [])
//  {
//    $dbMain = $this->getDb('main');
//
//    $logger = $this->getLogger();
//    $logger->info('recalculateFreeStock : product codes' . print_r($productCodes, true));
//
//    // 指定商品のみ更新
//    if ($productCodes) {
//      $quoted = [];
//      foreach($productCodes as $i => $code) {
//        $quoted[] = $dbMain->quote($code);
//      }
//
//      $addSql = sprintf(" WHERE pci.ne_syohin_syohin_code IN ( %s ) ", implode(', ', $quoted));
//    // 全更新
//    } else {
//      $addSql = " WHERE pci.フリー在庫数 <> (pci.在庫数 - pci.引当数 - pci.ピッキング引当数) ";
//      $addSql .= "   OR pci.フリー在庫数 < 0 ";
//    }
//
//    $sql = <<<EOD
//      UPDATE
//      tb_productchoiceitems pci
//      SET pci.`フリー在庫数` = CASE
//                                  WHEN (pci.在庫数 - pci.引当数 - pci.ピッキング引当数) < 0 THEN 0
//                                  ELSE (pci.在庫数 - pci.引当数 - pci.ピッキング引当数)
//                              END
//      {$addSql}
//EOD;
//    $dbMain->query($sql);
//  }

  /**
   * 予約フリー在庫数再計算
   */
  public function recalculateFreeReservedStock()
  {
    $dbMain = $this->getDb('main');

    // 予約引当数の更新（ = 未引当の受注数）
    $sql = <<<EOD
      UPDATE tb_productchoiceitems pci
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      SET pci.予約引当数 = 0
        , pci.予約在庫数 = CASE
                           WHEN pci.`受発注可能フラグ` <> 0 AND cal.`受発注可能フラグ退避F` = 0 THEN 99999
                           ELSE 0 /* pci.`発注残数` -- ※2017/04/24 #15105 注残は予約在庫に含まない */
                          END
        , 予約フリー在庫数 = 0
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN (
        SELECT
            a.`商品コード（伝票）`
          , SUM(a.受注数 - a.引当数) AS 予約引当数
        FROM tb_sales_detail_analyze a
        WHERE a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
          AND a.`受注状態` <> '出荷確定済（完了）'
          AND a.`受注数` > a.引当数
        GROUP BY a.`商品コード（伝票）`
      ) T ON pci.ne_syohin_syohin_code = T.`商品コード（伝票）`
      SET pci.`予約引当数` = T.予約引当数
EOD;
    $dbMain->query($sql);

    // 予約フリー在庫数の更新（99999 or 注残-予約引当数）
    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      SET pci.`予約フリー在庫数` =  CASE
                                     WHEN pci.予約在庫数 <= pci.予約引当数 THEN 0
                                     ELSE pci.予約在庫数 - pci.予約引当数
                                   END
EOD;
    $dbMain->query($sql);
  }

  /**
   * NextEngine 在庫データダウンロード ＆ 格納テーブル更新処理
   * ※2017/05/30 NextEngineがメモリ足りないエラーを吐く様になったため、利用中止。オリジナルCSVの分割ダウンロードに切り替え
   * @param string $dataDir
   * @param string $fileName
   * @param SymfonyUsers $account
   * @param string $targetEnv
   * @param BatchLogger $logger
   * @throws \Doctrine\DBAL\DBALException
   */
  public function downloadNextEngineStockData($dataDir, $fileName, $account = null, $targetEnv = null, $logger = null)
  {
    $dbMain = $this->getDb('main');
    $fileUtil = $this->getFileUtil();

    if (!$logger) {
      $logger = $this->getLogger();
    }

    // NextEngine在庫データダウンロード（差分確認用）
    // CSVダウンロード処理
    $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      , sprintf('--output-file=%s', $fileName)
    ];
    if ($account) {
      $commandArgs[] = sprintf('--account=%d', $account->getId());
    }
    if ($targetEnv) {
      $commandArgs[] = sprintf('--target-env=%s', $targetEnv);
    }
    $logger->info('main job: import_stock_list: ' . print_r($commandArgs, true));
    $commandInput = new ArgvInput($commandArgs);
    $commandOutput = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.csv_download_stock_command');
    $exitCode = $command->run($commandInput, $commandOutput);

    if ($exitCode !== 0) {
      throw new \RuntimeException('CSVダウンロードエラー.');
    } else {
      $logger->info('CSVダウンロード成功。CSV出力開始');
    }

    $filePath = $dataDir . '/' . $fileName;

    $fileInfo = $fileUtil->getTextFileInfo($filePath);
    if (!$fileInfo['exists']) {
      throw new \RuntimeException('no file!! [' . $filePath . ']');
    }
    if (!$fileInfo['readable']) {
      throw new \RuntimeException('not readable!! [' . $filePath . ']');
    }

    // CSV書式確認
    // ヘッダ行チェック
    $fp = fopen($fileInfo['path'], "r");
    $fileHeader = trim(mb_convert_encoding(fgets($fp), 'UTF-8', 'SJIS-WIN'));
    $validHeaderLine = '"商品コード","商品名","在庫数","引当数","フリー在庫数","予約在庫数","予約引当数","予約フリー在庫数","不良在庫数"';

    if ($fileHeader !== $validHeaderLine) {
      throw new \RuntimeException('invalid CSV data!! [' . $filePath . ']');
    }

    // 在庫CSVファイルの取込
    $time = microtime(true);
    $logger->info('一時テーブルへCSV格納');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain->query("TRUNCATE tb_totalstock_dl");
    $dbMain->query("SET character_set_database=sjis;");

    $sql  = " LOAD DATA LOCAL INFILE '" . $filePath . "' ";
    $sql .= " INTO TABLE tb_totalstock_dl ";
    $sql .= "   FIELDS ENCLOSED BY '\"' TERMINATED BY ',' ";
    $sql .= "   LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES;";

    $dbMain->query($sql);
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

  }

  /**
   * NextEngine 在庫データダウンロード（オリジナルCSV） ＆ 格納テーブル更新処理。
   * 在庫CSVをダウンロードして、tb_totalstock_dl へ取り込む。tb_totalstock_dl は TRUNCATE & INSERT。
   *
   * @param string $exportDir ダウンロードした在庫データの格納ディレクトリ。指定しなければ data/stocks/yyyymmddhhmmss。
   * @param SymfonyUsers $account
   * @param string $targetEnv
   * @param bool $doDownload ダウンロードを行うか。falseならば ダウンロードはせず、exportDirのデータを一時テーブルにそのまま取り込む
   * @param string $type
   * @throws \Doctrine\DBAL\DBALException
   */
  public function downloadNextEngineStockDataOriginal($exportDir = null, $account = null, $targetEnv = null, $doDownload = true, $type = 'all')
  {
    $dbMain = $this->getDb('main');
    $fileUtil = $this->getFileUtil();

    $logger = $this->getLogger();

    if (!$exportDir) {
      $rootDir = $this->getContainer()->get('kernel')->getRootDir();
      $dataDir = dirname($rootDir) . '/data/stocks';
      $exportDir = $dataDir . '/' . (new \DateTime())->format('YmdHis');
    }

    if ($doDownload) {
      // NextEngine在庫データダウンロード（差分確認用）
      // CSVダウンロード処理
      $commandArgs = [
          'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        , sprintf('--export-dir=%s', $exportDir)
        , sprintf('--type=%s', $type)
      ];
      if ($account) {
        $commandArgs[] = sprintf('--account=%d', $account->getId());
      }
      if ($targetEnv) {
        $commandArgs[] = sprintf('--target-env=%s', $targetEnv);
      }

      $logger->info('main job: import_stock_list_original: ' . print_r($commandArgs, true));
      $commandInput = new ArgvInput($commandArgs);
      $commandOutput = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.csv_download_stock_original_command');
      $exitCode = $command->run($commandInput, $commandOutput);

      if ($exitCode !== 0) {
        throw new \RuntimeException('CSVダウンロードエラー.');
      } else {
        $logger->info('CSVダウンロード成功。CSV出力開始');
      }
    }

    // 以下、import
    $fs = new Filesystem();

    if (!$fs->exists($exportDir)) {
      throw new \RuntimeException('no dir!! [' . $exportDir . ']');
    }

    $finder = new Finder();
    $files = $finder->in($exportDir)->name('*.csv')->files();
    if (!$files->count()) {
      throw new \RuntimeException('no files!! [' . $exportDir . ']');
    }

    // 在庫CSVファイルの取込
    $time = microtime(true);
    $logger->info('一時テーブルへCSV格納');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain->query("TRUNCATE tb_totalstock_dl");
    $dbMain->query("SET character_set_database=sjis;");

    /** @var SplFileInfo $file */
    foreach($files as $file) {

      $filePath = $file->getPathname();
      $logger->info($filePath);

      $fileInfo = $fileUtil->getTextFileInfo($filePath);
      if (!$fileInfo['readable']) {
        throw new \RuntimeException('not readable!! [' . $filePath . ']');
      }

      // CSV書式確認
      // ヘッダ行チェック
      $fp = fopen($fileInfo['path'], "r");
      $fileHeader = trim(mb_convert_encoding(fgets($fp), 'UTF-8', 'SJIS-WIN'));
      $validHeaderLine = '"商品コード","商品名","在庫数","引当数","フリー在庫数","予約在庫数","予約引当数","予約フリー在庫数","不良在庫数"';

      if ($fileHeader !== $validHeaderLine) {
        throw new \RuntimeException('invalid CSV data!! [' . $filePath . ']');
      }

      // 1ファイルずつ実行
      $sql  = " LOAD DATA LOCAL INFILE '" . $filePath . "' ";
      $sql .= " IGNORE INTO TABLE tb_totalstock_dl ";
      $sql .= "   FIELDS ENCLOSED BY '\"' TERMINATED BY ',' ";
      $sql .= "   LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES;";

      $dbMain->query($sql);
    }

    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
  }


  /**
   * NextEngine APIクライアント取得
   *
   * @param $account
   * @return \ForestNeApiClient
   */
  public function getApiClient($account)
  {
    // アカウントに応じて取得情報が変わる実装が必要になれば対応。
    // $account

    $commonUtil = $this->getDbCommonUtil();

    $apiInfo = $this->getContainer()->getParameter('ne_api');
    $clientId = $apiInfo['client_id'];
    $clientSecret = $apiInfo['client_secret'];
    $redirectUrl = $apiInfo['redirect_url'];

    $accessToken = $commonUtil->getSettingValue('NE_API_ACCESS_TOKEN');
    if (!$accessToken) {
      $accessToken = null;
    }
    $refreshToken = $commonUtil->getSettingValue('NE_API_REFRESH_TOKEN');
    if (!$refreshToken) {
      $refreshToken = null;
    }

    $client = new \ForestNeApiClient($clientId, $clientSecret, $redirectUrl, $accessToken, $refreshToken);

    $loginId = $commonUtil->getSettingValue('NE_API_LOGIN_ID');
    $loginPassword = $commonUtil->getSettingValue('NE_API_LOGIN_PASSWORD');

    $client->setUserAccount($loginId, $loginPassword);

    $client->log('create instance.');
    $client->log('access_token: ' . $client->_access_token);
    $client->log('refresh_token: ' . $client->_refresh_token);

    return $client;
  }

  /**
   * NextEngine API アップロードキュー一覧取得
   * 直近の指定件数を取得する。
   * @param string $methodName 機能名 未指定なら全て。
   * @param \DateTimeInterface $limitDateTime 初期値3日 ※並び順を指定できないため、件数limitが利用できない。日付で取得
   * @return array
   */
  public function getApiUploadQueueList($methodName = null, $limitDateTime = null)
  {
    $logger = $this->getLogger();

    $client = $this->getApiClient('api');
    $client->setLogger($logger);

    if (!$limitDateTime) {
      $limitDateTime = (new \DateTimeImmutable())->modify('-3 day');
      $limitDateTime->setTime(0, 0, 0);
    }

    // 情報を取得
    $query = [];
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    $query['fields'] = implode(',', [
        'que_id'
      , 'que_method_name'
      , 'que_shop_id'
      , 'que_upload_name'
      , 'que_client_file_name'
      , 'que_file_name'
      , 'que_status_id'
      , 'que_message'
      , 'que_deleted_flag'
      , 'que_creation_date'
      , 'que_last_modified_date'
      , 'que_last_modified_null_safe_date'
      , 'que_creator_id'
      , 'que_creator_name'
      , 'que_last_modified_by_id'
      , 'que_last_modified_by_null_safe_id'
      , 'que_last_modified_by_name'
      , 'que_last_modified_by_null_safe_name'
    ]);
    $query['offset'] = '0' ;
    $query['limit'] = 1000 ;
    if ($methodName) {
      $query['que_method_name-eq'] = $methodName;
    }
    $query['que_creation_date-gte'] = $limitDateTime->format('Y-m-d H:i:s');

    // 検索実行
    $receives = $client->apiExecute('/api_v1_system_que/search', $query) ;
    $client->log($receives['result']);
    $client->log($receives['count']);

    if ($receives['result'] != 'success') {
      $logger->info(print_r($receives, true));
      $message = 'NE APIエラー';
      if (isset($receives['code'])) {
        $message = sprintf('[%s] ', $receives['code']);
      }
      if (isset($receives['message'])) {
        $message .= $receives['message'];
      }

      throw new \RuntimeException($message);
    }

    if (isset($receives['data']) && is_array($receives['data'])) {
      $data = $receives['data'];
      usort($data, function($a, $b) {
        $dateA = new \DateTimeImmutable($a['que_creation_date']);
        $dateB = new \DateTimeImmutable($b['que_creation_date']);

        if ($dateA == $dateB) {
          return 0;
        }

        return $dateA < $dateB ? 1 : -1;
      });

      $receives['data'] = $data;
    }

    return $receives;
  }


  /**
   * NextEngine API 商品マスタ一括アップロード処理
   * @param \SplFileInfo $file
   * @return array
   */
  public function apiUploadProductCsv(\SplFileInfo $file)
  {
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'result' => null
      , 'queues' => null
    ];

    $logger = $this->getLogger();

    try {
      $fs = new FileSystem();

      // gzip 圧縮
      $uploadFile = $file;
      $isTmpFile = false;
      if ($uploadFile->getExtension() != 'gz') {
        $fileUtil = $this->getFileUtil();
        $tmpFilePath = sprintf('%s/%s.gz', $fileUtil->getCacheDir(), uniqid('tmp_ne_product_')); // アップロード時に拡張子で判定する。
        $fp = fopen($tmpFilePath, 'wb');
        fwrite($fp, gzencode(file_get_contents($file->getPathname())));
        fclose($fp);
        $uploadFile = new \SplFileInfo($tmpFilePath);
        $isTmpFile = true;
      }

      $logger->info(sprintf('upload: %s (%d)', $uploadFile->getPathname(), $uploadFile->getSize()));

      // アップロード処理
      $logger->info(sprintf('ne_api/upload_product: %s (%d)', $uploadFile->getPathname(), $uploadFile->getSize()));

      // ---------------------------------------------
      // API 受注一括登録処理
      $client = $this->getApiClient('api');
      $client->setLogger($logger);

      // 情報を取得
      $query = [
          'data_type' => self::API_DATA_TYPE_GZ
        , 'data' => file_get_contents($uploadFile->getPathname())
      ];

      // アクセス制限中はアクセス制限が終了するまで待つ。
      // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
      $query['wait_flag'] = '1';

      // アップロード
      $receives = $client->apiExecute('/api_v1_master_goods/upload', $query);
      $client->log($receives['result']);

      if ($isTmpFile) {
        $fs->remove($uploadFile->getPathname());
      }

      $result['result'] = $receives;
      if ($receives['result'] != 'success') {
        $logger->info(print_r($receives, true));
        $message = 'NE APIエラー';
        if (isset($receives['code'])) {
          $message = sprintf('[%s] ', $receives['code']);
        }
        if (isset($receives['message'])) {
          $message .= $receives['message'];
        }

        throw new \RuntimeException($message);
      }

      // キュー状況取得処理
      $result['queues'] = $this->getApiUploadQueueList(NextEngineMallProcess::API_QUEUE_METHOD_NAME_PRODUCT);

    } catch (\Exception $e) {
      $logger->error('ne_api/upload_product: ' . $e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return $result;
  }

  /**
   * NextEngine API セット商品マスタ一括アップロード処理
   * @see https://developer.next-engine.com/api/api_v1_master_setgoods/upload
   * @param \SplFileInfo $file
   * @return array
   */
  public function apiUploadSetProductCsv(\SplFileInfo $file)
  {
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'result' => null
      , 'queues' => null
    ];
    
    $logger = $this->getLogger();
    
    try {
      $fs = new FileSystem();
      
      // gzip 圧縮
      $uploadFile = $file;
      $isTmpFile = false;
      if ($uploadFile->getExtension() != 'gz') {
        $fileUtil = $this->getFileUtil();
        $tmpFilePath = sprintf('%s/%s.gz', $fileUtil->getCacheDir(), uniqid('tmp_ne_set_product_')); // アップロード時に拡張子で判定する。
        $fp = fopen($tmpFilePath, 'wb');
        fwrite($fp, gzencode(file_get_contents($file->getPathname())));
        fclose($fp);
        $uploadFile = new \SplFileInfo($tmpFilePath);
        $isTmpFile = true;
      }
      
      // アップロード処理
      $logger->info(sprintf('ne_api/upload_set_product: %s (%d)', $uploadFile->getPathname(), $uploadFile->getSize()));
      
      // ---------------------------------------------
      // APIクライアント取得
      $client = $this->getApiClient('api');
      $client->setLogger($logger);
      
      // 情報を取得
      $query = [
        'data_type' => self::API_DATA_TYPE_GZ
        , 'data' => file_get_contents($uploadFile->getPathname())
      ];
      
      // アクセス制限中はアクセス制限が終了するまで待つ。
      // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
      $query['wait_flag'] = '1';
      
      // アップロード
      $receives = $client->apiExecute('/api_v1_master_setgoods/upload', $query);
      $client->log($receives['result']);
      
      if ($isTmpFile) {
        $fs->remove($uploadFile->getPathname());
      }
      
      $result['result'] = $receives;
      if ($receives['result'] != 'success') {
        $logger->info('APIセット商品一括更新でエラー発生: ' . print_r($receives, true));
        $message = 'NE APIエラー（セット商品一括更新）';
        if (isset($receives['code'])) {
          $message = sprintf('[%s] ', $receives['code']);
        }
        if (isset($receives['message'])) {
          $message .= $receives['message'];
        }
        throw new \RuntimeException($message);
      }
      
      // キュー状況取得処理　セット商品もこのIDで良いのかわからないが、それらしいID定義がないのでいったんこのままとしてみる
      $result['queues'] = $this->getApiUploadQueueList(NextEngineMallProcess::API_QUEUE_METHOD_NAME_PRODUCT);
      
    } catch (\Exception $e) {
      $logger->error('ne_api/upload_set_product: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    
    return $result;
  }
  
  

  /**
   * NextEngine API 受注一括アップロード処理
   * @param TbShoppingMall $mall
   * @param \SplFileInfo $uploadFile
   * @return array
   */
  public function apiUploadMallOrderCsv(TbShoppingMall $mall, \SplFileInfo $uploadFile)
  {
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    $logger = $this->getLogger();

    try {
      $logger->info('ne_api/upload_mall_order: ' . $uploadFile->getPathname());

      // ---------------------------------------------
      // API 受注一括登録処理
      /** @var NextEngineMallProcess $process */
      $process = $this->getContainer()->get('batch.mall_process.next_engine');

      $client = $process->getApiClient('api');
      $client->setLogger($logger);

      // 情報を取得
      $query = [
          'receive_order_upload_pattern_id' => $mall->getNeOrderUploadPatternId()
        , 'data_type_1' => ($uploadFile->getExtension() === 'gz' ? self::API_DATA_TYPE_GZ : self::API_DATA_TYPE_CSV)
        , 'data_1' => file_get_contents($uploadFile->getPathname())
      ];

      // アクセス制限中はアクセス制限が終了するまで待つ。
      // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
      $query['wait_flag'] = '1';

      // 検索実行
      $receives = $client->apiExecute('/api_v1_receiveorder_base/upload', $query);
      $client->log($receives['result']);

      if ($receives['result'] != 'success') {
        $logger->info(print_r($receives, true));
        $message = 'NE APIエラー';
        if (isset($receives['code'])) {
          $message = sprintf('[%s] ', $receives['code']);
        }
        if (isset($receives['message'])) {
          $message .= $receives['message'];
        }

        throw new \RuntimeException($message);
      }

      $result['result'] = $receives;

    } catch (\Exception $e) {
      $logger->error('ne_api/upload_mall_order: ' . $e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return $result;
  }

  /**
   * モール受注CSV出力
   * ファイル出力時には、指定件数ごとにファイルを分割して出力する。（NextEngineがエラーを起こすため）
   * 出力はUTF-8。
   *
   * @param integer $shopCode NextEngine 店舗ID
   * @param string $converted 変換日時
   * @param string $output
   * @param bool $newAlwaysExport
   * @return SplFileInfo[]|StreamedResponse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function generateMallOrderCsv($shopCode, $converted, $output = 'file', $newAlwaysExport = false)
  {
    $logger = $this->getLogger();

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    $newAlwaysExportSql = '';
    if ($newAlwaysExport) {
      $newAlwaysExportSql = sprintf(" OR ( converted IS NOT NULL AND downloaded IS NULL )");
    }

    $sql = <<<EOD
      SELECT
          `店舗伝票番号`
        , `受注日`
        , `受注郵便番号`
        , `受注住所1`
        , `受注住所2`
        , `受注名`
        , `受注名カナ`
        , `受注電話番号`
        , `受注メールアドレス`
        , `発送郵便番号`
        , `発送先住所１`
        , `発送先住所２`
        , `発送先名`
        , `発送先カナ`
        , `発送電話番号`
        , `支払方法`
        , `発送方法`
        , `商品計`
        , `税金`
        , `発送料`
        , `手数料`
        , `ポイント`
        , `その他費用`
        , `合計金額`
        , `ギフトフラグ`
        , `時間帯指定`
        , CASE WHEN `日付指定` = '0000-00-00' THEN '' ELSE `日付指定` END AS `日付指定`
        , `作業者欄`
        , `備考`
        , `商品名`
        , `商品コード`
        , `商品価格`
        , `受注数量`
        , `商品オプション`
        , `出荷済フラグ`
        , `顧客区分`
        , `顧客コード`
      FROM tb_ne_mall_order o
      WHERE shop_code = :shopCode
        AND (
          converted = :converted
          {$newAlwaysExportSql}
        )
      ORDER BY o.店舗伝票番号
             , o.明細行
             , o.商品コード
             , o.受注日
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $shopCode);
    $stmt->bindValue(':converted', $converted);
    $stmt->execute();

    $logger->info('csv count: ' . $stmt->rowCount());

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = [
        '店舗伝票番号'  => '店舗伝票番号'
      , '受注日'        => '受注日'
      , '受注郵便番号'  => '受注郵便番号'
      , '受注住所1'     => '受注住所1'
      , '受注住所2'     => '受注住所2'
      , '受注名'       => '受注名'
      , '受注名カナ'   => '受注名カナ'
      , '受注電話番号'  => '受注電話番号'
      , '受注メールアドレス' => '受注メールアドレス'
      , '発送郵便番号'  => '発送郵便番号'
      , '発送先住所１'  => '発送先住所１'
      , '発送先住所２'  => '発送先住所２'
      , '発送先名'      => '発送先名'
      , '発送先カナ'   => '発送先カナ'
      , '発送電話番号'  => '発送電話番号'
      , '支払方法'      => '支払方法'
      , '発送方法'      => '発送方法'
      , '商品計'        => '商品計'
      , '税金'          => '税金'
      , '発送料'        => '発送料'
      , '手数料'        => '手数料'
      , 'ポイント'      => 'ポイント'
      , 'その他費用'     => 'その他費用'
      , '合計金額'      => '合計金額'
      , 'ギフトフラグ'  => 'ギフトフラグ'
      , '時間帯指定'     => '時間帯指定'
      , '日付指定'      => '日付指定'
      , '作業者欄'      => '作業者欄'
      , '備考'          => '備考'
      , '商品名'       => '商品名'
      , '商品コード'   => '商品コード'
      , '商品価格'      => '商品価格'
      , '受注数量'      => '受注数量'
      , '商品オプション' => '商品オプション'
      , '出荷済フラグ'   => '出荷済フラグ'
      , '顧客区分'      => '顧客区分'
      , '顧客コード'    => '顧客コード'
    ];

    $logger->info('csv output: ' . $output);

    // StreamedResponse
    if ($output == 'response') {

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($stmt, $stringUtil, $headers)
        {
          $eol = "\r\n";
          $exportFile = new \SplFileObject('php://output', 'w');

          // ヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
          $exportFile->fwrite($header);

          while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;

            $exportFile->fwrite($line);

            flush();
          }
        }
      );

      $fileName = $this->createMallOrderCsvFileName($shopCode, $converted);
      $logger->info('csv name: ' . $fileName);

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));

      $logger->info('csv output: response done!');

      return $response;


    // CSVファイル出力
    } else {

      $exportFiles = [];

      $fileUtil = $this->getFileUtil();
      $fs = new Filesystem();
      $exportDir = sprintf('%s/MallOrder/Export', $fileUtil->getWebCsvDir());
      if (!$fs->exists($exportDir)) {
        $fs->mkdir($exportDir, 0755);
      }

      // データが無ければ出力しない
      if ($stmt->rowCount() == 0) {
        return '';
      }

      $fileIndex = 1;
      $fetchedRow = null; // 取得済みの出力行の引き継ぎが無い状態でスタート

      do {
        $fileName = (new \DateTime())->format('YmdHis') . '_' . $this->createMallOrderCsvFileName($shopCode, $converted, sprintf('%02d', $fileIndex++));

        $exportCsvPath = sprintf('%s/%s', $exportDir, $fileName);
        $exportFile = new \SplFileObject($exportCsvPath, 'w');

        $logger->info('csv output: file start! ' . $fileName);

        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
        $exportFile->fwrite($header);

        $rowNum = 0; // MALL_ORDER_CSV_ROW_LIMITまで数える
        $currentVoucherNumber = null;

        // 引き継ぎ行があればそれをまず出力
        while(($row = $fetchedRow) || ($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
          $fetchedRow = null; // 引き継ぎはさっさとリセット
          $rowNum++;
          $voucherNumber = $row['店舗伝票番号'];

          // 行数リミット判定
          if ($rowNum > self::MALL_ORDER_CSV_ROW_LIMIT) {
            // 同一伝票であれば続け、別伝票に移っていれば出力せず次のファイルへ持ち越し
            if (isset($currentVoucherNumber) && $currentVoucherNumber != $voucherNumber) {
              $fetchedRow = $row;
              $exportFile->fflush();
              break;
            }
          }
          $currentVoucherNumber = $voucherNumber;

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;

          $exportFile->fwrite($line);

        }

        $logger->info('csv output: file done!');

        $exportFiles[] = $exportFile;
        if (!$row) { // 最後まで出力されていれば、終了
          break;
        }

      } while (true);

      return $exportFiles;
    }
  }

  /** モール受注CSVファイル名作成
   * @param string $shopCode
   * @param string $converted
   * @param string $postfix
   * @return string
   */
  public function createMallOrderCsvFileName($shopCode, $converted, $postfix = '')
  {
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');
    $shopName = $commonUtil->getMallCodeByNeMallId($shopCode);
    if (!$shopName) {
      $shopName = 'unknown';
    }

    return sprintf('%s_order_%s%s.csv', $shopName, (new \DateTime($converted))->format('YmdHis'), (strlen($postfix) ? ('_' . $postfix) : ''));
  }


  /**
   * 支払変換設定 取得処理
   */
  public function getPaymentConvertList()
  {
    if (is_null($this->paymentConvertList)) {

      $this->paymentConvertList = [];

      /** @var Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $sql = <<<EOD
        SELECT
            s.payment_delivery_convert_text
          , p.payment_method_name
        FROM ne_payment_delivery_convert_setting s
        INNER JOIN ne_kubun_payment_method p ON s.payment_delivery_convert_multi_id = p.payment_method_id
        WHERE s.payment_delivery_convert_type = 'siharai_kbn'
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      foreach($stmt as $row) {
        $this->paymentConvertList[$row['payment_delivery_convert_text']] = $row['payment_method_name'];
      }
    }

    return $this->paymentConvertList;
  }

  /**
   * 配送変換設定 取得処理
   */
  public function getDeliveryConvertList()
  {
    if (is_null($this->deliveryConvertList)) {

      $this->deliveryConvertList = [];

      /** @var Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $sql = <<<EOD
        SELECT
            s.payment_delivery_convert_text
          , d.delivery_name
        FROM ne_payment_delivery_convert_setting s
        INNER JOIN ne_kubun_delivery_method d ON s.payment_delivery_convert_multi_id = d.delivery_id
        WHERE s.payment_delivery_convert_type = 'hasou_kbn'
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      foreach($stmt as $row) {
        $this->deliveryConvertList[$row['payment_delivery_convert_text']] = $row['delivery_name'];
      }
    }

    return $this->deliveryConvertList;
  }

  /**
   * 支払方法文言 変換
   * @param string $word
   * @param bool $emptyIfNotExists
   * @return string
   */
  public function convertPaymentMethod($word, $emptyIfNotExists = false)
  {
    $convertList = $this->getPaymentConvertList();
    return isset($convertList[$word]) ? $convertList[$word] : ($emptyIfNotExists ? null : $word);
  }

  /**
   * 配送方法文言 変換
   * @param string $word
   * @param bool $emptyIfNotExists
   * @return string
   */
  public function convertDeliveryMethod($word, $emptyIfNotExists = false)
  {
    $convertList = $this->getDeliveryConvertList();
    return isset($convertList[$word]) ? $convertList[$word] : ($emptyIfNotExists ? null : $word);
  }

  /**
   * 配送方法文言取得
   * @param int $deliveryId
   * @return string
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getDeliveryMethodName($deliveryId)
  {
    /** @var Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
        SELECT
           d.delivery_name
        FROM ne_kubun_delivery_method d
        WHERE d.delivery_id = :deliveryId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryId', $deliveryId, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn(0);
  }


  /**
   * モール受注CSV 取込処理 Amazon
   * ※モール受注CSV変換前処理
   * @param File[] $files
   * @param \DateTimeInterface $now
   * @throws \Doctrine\DBAL\DBALException
   */
  public function importMallOrderAmazon($files, $now = null)
  {
    if (!$now) {
      $now = new \DateTime();
    }

    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $logDbName = $dbLog->getDatabase();

    $dbMain = $this->getDoctrine()->getConnection('main');

    $result = [
      'load_data' => []
    ];

    foreach($files as $i => $file) {
      $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_amazon")->fetchColumn(0);
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFilePath
        IGNORE INTO TABLE tb_mall_order_amazon
        FIELDS TERMINATED BY '\t' ENCLOSED BY '"' ESCAPED BY ''
        LINES TERMINATED BY '\n' IGNORE 1 LINES (
            `order-id`
          , `order-item-id`
          , `purchase-date`
          , `payments-date`
          , `buyer-email`
          , `buyer-name`
          , `buyer-phone-number`
          , `sku`
          , `product-name`
          , `quantity-purchased`
          , `currency`
          , `item-price`
          , `item-tax`
          , `shipping-price`
          , `shipping-tax`
          , `gift-wrap-price`
          , `gift-wrap-tax`
          , `ship-service-level`
          , `recipient-name`
          , `ship-address-1`
          , `ship-address-2`
          , `ship-address-3`
          , `ship-city`
          , `ship-state`
          , `ship-postal-code`
          , `ship-country`
          , `ship-phone-number`
          , `gift-wrap-type`
          , `gift-message-text`
          , `item-promotion-discount`
          , `item-promotion-id`
          , `ship-promotion-discount`
          , `ship-promotion-id`
          , `delivery-start-date`
          , `delivery-end-date`
          , `delivery-time-zone`
          , `delivery-Instructions`
          , `is-prime`
        )
        SET imported = CURRENT_TIMESTAMP
EOD;
      $stmt = $dbLog->prepare($sql);
      $stmt->bindValue(':importFilePath', $file->getPathname());
      $stmt->execute();

      // 代表商品コード補完
      $sql = <<<EOD
        UPDATE
        {$logDbName}.tb_mall_order_amazon mo
        INNER JOIN tb_productchoiceitems pci ON mo.sku = pci.ne_syohin_syohin_code
        SET mo.daihyo_syohin_code = pci.daihyo_syohin_code
        WHERE mo.daihyo_syohin_code = ''
          AND mo.convert_flg = 0
EOD;
      $dbMain->exec($sql);

      $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_amazon")->fetchColumn(0);

      // ファイル名sをモールがわかるものに変更（検証用履歴データ）
      $fileName = sprintf('%s_%s_%02d', DbCommonUtil::MALL_CODE_AMAZON, $now->format('YmdHis'), $i + 1);
      $file->move($file->getPath(), $fileName);
      $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
      $files[$i] = $file;

      $result['load_data'][$file->getPathname()] = [
          'before' => $beforeCount
        , 'after' => $afterCount
        , 'num' => $afterCount - $beforeCount
      ];
    }

    return $result;
  }


  /**
   * 宛名CSV出力（配送情報CSV出力）。
   * @param string $shippingMethodCode
   * @param string[] $voucherNumbers
   * @param \DateTimeInterface $now
   * @return StreamedResponse
   * @throws \Exception
   */
  public function generateShippingLabelCsv($shippingMethodCode, $voucherNumbers, $now = null)
  {
    $logger = $this->getLogger();

    // 自社管理発送伝票番号使用済の伝票が有れば、ステータスをキャンセルに変更する
    /** @var TbShipmentTrackingNumberRepository $repoTracking */
    $repoTracking = $this->getDoctrine()->getRepository('MiscBundle:TbShipmentTrackingNumber');
    $repoTracking->updateStatusToCancelled($voucherNumbers);

    // データ取得
    /** @var TbSalesDetailRepository $repoVoucher */
    $repoVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
    $vouchers = $repoVoucher->getVoucherByVoucherNumbers($voucherNumbers, true);

    if (!$now) {
      $now = new \DateTime();
    }

    $voucherNumbers = [];
    foreach ($vouchers as $voucher) {
      $voucherNumbers[] = $voucher->getVoucherNumber();
    }

    // 配送情報を取得する。これは納品書CSVアップロード時のデータ。$vouchersのデータはamazonデータがマスクされているため依頼元・配送先データ取得にはこれを利用
    /** @var TbShippingVoucherRepository $shippingVoucherRepo */
    $shippingVoucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $shippingInfos = $shippingVoucherRepo->getShippingInfoByVoucherNumbers($voucherNumbers);

    // 発送種別毎にラベル書式を切り替え。
    switch ($shippingMethodCode) {
      case TbSalesDetail::SHIPPING_METHOD_CODE_SAGAWA: // 佐川急便(e飛伝2)
      case TbSalesDetail::SHIPPING_METHOD_CODE_FUKUYAMA: // 福山通運
        $response = $this->createShippingLabelCsvResponseSagawa($vouchers, $shippingInfos);
        break;

      case TbSalesDetail::SHIPPING_METHOD_CODE_CLICKPOST: //クリックポスト
      case TbSalesDetail::SHIPPING_METHOD_CODE_CLICKPOST_2: //クリックポスト
        $response = $this->createShippingLabelCsvResponseClickPost($vouchers,$shippingMethodCode, $shippingInfos, $now);
        break;

      case TbSalesDetail::SHIPPING_METHOD_CODE_MAILBIN: // メール便
      case TbSalesDetail::SHIPPING_METHOD_CODE_NEKOPOSU: // ねこポス
        $response = $this->createShippingLabelCsvResponseYamato($vouchers, $shippingMethodCode, $shippingInfos);
        break;

      case TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK: // ゆうパック
      case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI_DAIBIKI: // 定形外郵便(代引)
      case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI: // 定形外郵便
      case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEI: // 定形郵便
        $response = $this->createShippingLabelCsvResponseYuubin($vouchers, $shippingMethodCode, $shippingInfos);
        break;

      case TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACKET: // ゆうパケット
        $response = $this->createShippingLabelCsvResponseYuuPacket($vouchers, $shippingInfos);
        break;

      // 日本郵便の配達方法だが発送時の持ち込み先は「楽天スーパーロジ」なので楽天Express形式で生成する必要がある
      case TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK_RSL: // ゆうパック(RSL)
      case TbSalesDetail::SHIPPING_METHOD_CODE_YAMATO: // ﾔﾏﾄ(発払い)B2v6
        $response = $this->createShippingLabelCsvResponseRakuten($vouchers, $shippingMethodCode, $shippingInfos);
        break;

      default:
        throw new \RuntimeException('unknown shipping method: ' . $shippingMethodCode);
        break;
    }

    if(!($shippingMethodCode == TbSalesDetail::SHIPPING_METHOD_CODE_CLICKPOST || $shippingMethodCode == TbSalesDetail::SHIPPING_METHOD_CODE_CLICKPOST_2)) {
      $fileName = $this->createShippingLabelFileName($shippingMethodCode, $now->format('Y-m-d H:i:s'));
      $logger->info('csv name: ' . $fileName);

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
    }
    $logger->info('csv output: response done!');

    return $response;
  }

  /** モール受注CSVファイル名作成
   * @param string $shippingMethodCode
   * @param string $datetime
   * @param string $postfix
   * @return string
   * @throws \Doctrine\DBAL\DBALException
   */
  public function createShippingLabelFileName($shippingMethodCode, $datetime, $postfix = '')
  {
    $methodName = $this->getDeliveryMethodName($shippingMethodCode);
    return sprintf('宛名_%s_%s%s.csv', $methodName, (new \DateTime($datetime))->format('YmdHis'), (strlen($postfix) ? ('_' . $postfix) : ''));
  }


  /**
   * @param VSalesVoucher[] $vouchers
   * @param $shippingMethodCode
   * @return StreamedResponse
   * @throws \Exception
   */
  private function createShippingLabelCsvResponseYamato($vouchers, $shippingMethodCode, $shippingInfos)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = [
        'お客様管理番号' => 'お客様管理番号'
      , '送り状種別' => '送り状種別'
      , '温度区分' => '温度区分'
      , '予備4' => '予備4'
      , '出荷予定日' => '出荷予定日'
      , '配達指定日' => '配達指定日'
      , '配達時間帯区分' => '配達時間帯区分'
      , '届け先コード' => '届け先コード'
      , '届け先電話番号' => '届け先電話番号'
      , '届け先電話番号(枝番)' => '届け先電話番号(枝番)'
      , '届け先郵便番号' => '届け先郵便番号'
      , '届け先住所' => '届け先住所'
      , 'お届け先建物名（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）' => 'お届け先建物名（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）'
      , '会社・部門名１' => '会社・部門名１'
      , '会社・部門名２' => '会社・部門名２'
      , '届け先名(漢字)' => '届け先名(漢字)'
      , '届け先名(カナ)' => '届け先名(カナ)'
      , '敬称' => '敬称'
      , '依頼主コード' => '依頼主コード'
      , '依頼主電話番号' => '依頼主電話番号'
      , '依頼主電話番号(枝番)' => '依頼主電話番号(枝番)'
      , '依頼主郵便番号' => '依頼主郵便番号'
      , '依頼主住所' => '依頼主住所'
      , '依頼主建物名（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）' => '依頼主建物名（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）'
      , '依頼主名（漢字）' => '依頼主名（漢字）'
      , '依頼主名(カナ)' => '依頼主名(カナ)'
      , '品名コード１' => '品名コード１'
      , '品名１' => '品名１'
      , '品名コード２' => '品名コード２'
      , '品名２' => '品名２'
      , '荷扱い１' => '荷扱い１'
      , '荷扱い２' => '荷扱い２'
      , '記事' => '記事'
      , 'コレクト代金引換額(税込)' => 'コレクト代金引換額(税込)'
      , 'コレクト内消費税額' => 'コレクト内消費税額'
      , '営業所止め置き' => '営業所止め置き'
      , '止め置き営業所コード' => '止め置き営業所コード'
      , '発行枚数' => '発行枚数'
      , '個数口枠の印字' => '個数口枠の印字'
      , '請求先顧客コード' => '請求先顧客コード'
      , '請求先分類コード' => '請求先分類コード'
      , '運賃管理番号' => '運賃管理番号'
      , '総数量' => '総数量'
    ];

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($vouchers, $shippingInfos, $shippingMethodCode, $stringUtil, $headers)
      {
        $eol = "\r\n";
        $exportFile = new \SplFileObject('php://output', 'w');

        if ($shippingMethodCode == TbSalesDetail::SHIPPING_METHOD_CODE_MAILBIN) {
          $labelType = '3'; // メール便
        } else {
          $labelType = '7'; // ねこポス
        }

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $exportFile->fwrite($header);

        foreach($vouchers as $voucher) {
          $item = $voucher->toScalarArray();
          $row = $item;
          $shippingInfo = null;
          if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
            $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
          }

          // ヤマトDM便
          $row['お客様管理番号'] = $voucher->getVoucherNumber();
          $row['送り状種別'] = $labelType;

          $row['出荷予定日'] = (new \DateTime())->format('Y/m/d'); // 必須。出力日固定。
          $row['配達指定日'] = $voucher->getShippingOrderedDate() ? $voucher->getShippingOrderedDate()->format('Y/m/d') : '';

          if ($shippingInfo) {
            $row['届け先電話番号'] = $shippingInfo['発送先電話番号'];;
            $row['届け先郵便番号'] = $shippingInfo['発送先郵便番号'];
            // 住所の仕様が厳しい： 「都道府県（4文字）＋市区郡町村（12文字）＋町・番地（16文字）」で、32文字まで、とのこと。NextEngineにならい、住所1＋住所2でがっしゃん
            $row['届け先住所'] = preg_replace('/[ 　]+/u', '', $shippingInfo['発送先住所1'] . $shippingInfo['発送先住所2']);
            $row['届け先名(漢字)'] = $shippingInfo['発送先名'];
          } else {
            $row['届け先電話番号'] = $voucher->getDeliveryTel();
            $row['届け先郵便番号'] = $voucher->getDeliveryZipcode();
            // 住所の仕様が厳しい： 「都道府県（4文字）＋市区郡町村（12文字）＋町・番地（16文字）」で、32文字まで、とのこと。NextEngineにならい、住所1＋住所2でがっしゃん
            $row['届け先住所'] = preg_replace('/[ 　]+/u', '', $voucher->getDeliveryAddress1() . $voucher->getDeliveryAddress2());
            $row['届け先名(漢字)'] = $voucher->getDeliveryName();
          }
          $row['お届け先建物名（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）'] = '';
          // $row['届け先名(カナ)'] = str_replace(' ', '', mb_convert_kana($voucher->getDeliveryNameKana(), 'khas', 'UTF-8')); // 半角カナじゃないとエラーらしい。NextEngineでは出していないので出さない。
          $row['届け先名(カナ)'] = '';

          // 依頼主：ひとまず決め打ち
					//20190131 【更新】　南京終から古市へ
/*
          $row['依頼主電話番号'] = '05053056841';
          $row['依頼主郵便番号'] = '6308141';
          $row['依頼主住所'] = '奈良県奈良市南京終町778-2';
*/
          $row['依頼主電話番号'] = '05053056840';
          $row['依頼主郵便番号'] = '6308424';
          $row['依頼主住所'] = '奈良県奈良市古市町789';

          $row['依頼主名（漢字）'] = '株式会社フォレスト';
          $row['品名１'] = '洋服';
          $row['請求先顧客コード'] = '05053056841';

          $row['コレクト代金引換額(税込)'] = '0';

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          $exportFile->fwrite($line);

          flush();
        }
      }
    );

    return $response;
  }


  /**
   * @param VSalesVoucher[] $vouchers
   * @return StreamedResponse
   * @throws \Exception
   */
  private function createShippingLabelCsvResponseSagawa($vouchers, $shippingInfos)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = [
        '伝票no' => '伝票no'
      , '受注no' => '受注no'
      , '合計金額' => '合計金額'
      , '合計金額（単位）' => '合計金額（単位）'
      , 'shitei_bi_a' => 'shitei_bi_a'
      , 'shitei_bi_b' => 'shitei_bi_b'
      , 'shitei_bi_c' => 'shitei_bi_c'
      , '時間指定' => '時間指定'
      , '便種' => '便種'
      , 'シール１（佐川専用）' => 'シール１（佐川専用）'
      , 'シール2（佐川専用）' => 'シール2（佐川専用）'
      , 'シール3（佐川専用）' => 'シール3（佐川専用）'
      , '営業所止め' => '営業所止め'
      , '営業所名' => '営業所名'
      , '送り状区分（ヤマト専用）' => '送り状区分（ヤマト専用）'
      , '温度区分（ヤマト専用）' => '温度区分（ヤマト専用）'
      , '受注名' => '受注名'
      , '受注郵便番号' => '受注郵便番号'
      , '受注者住所' => '受注者住所'
      , '受注者電話番号' => '受注者電話番号'
      , '発送先名' => '発送先名'
      , '発送先郵便番号' => '発送先郵便番号'
      , '発送先住所' => '発送先住所'
      , '発送先電話番号' => '発送先電話番号'
      , 'option1' => 'option1'
      , 'option2' => 'option2'
      , 'option3' => 'option3'
      , 'option4' => 'option4'
      , 'option5' => 'option5'
      , 'option6' => 'option6'
      , '企業名' => '企業名'
      , '企業郵便番号' => '企業郵便番号'
      , '企業住所' => '企業住所'
      , '企業電話番号' => '企業電話番号'
      , '取扱商品' => '取扱商品'
      , '郵便種別' => '郵便種別'
      , '元・着払い種別' => '元・着払い種別'
      , '代引き種別' => '代引き種別'
      , "if(e_txt is null,'',e_txt)" => "if(e_txt is null,'',e_txt)"
      , '総数量' => '総数量'
    ];

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($vouchers, $shippingInfos, $stringUtil, $headers)
      {
        $eol = "\r\n";
        $exportFile = new \SplFileObject('php://output', 'w');

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $exportFile->fwrite($header);

        foreach($vouchers as $voucher) {
          $item = $voucher->toScalarArray();
          $shippingInfo = null;
          if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
            $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
          }
          $row = $item;

          $row['伝票no'] = $voucher->getVoucherNumber();
          $row['受注no'] = $voucher->getOrderNumber();

          $row['合計金額'] = $voucher->isDaibiki() ? $voucher->getPaymentTotal() : 0; // 代引き料金
          $row['合計金額（単位）'] = intval($voucher->getPaymentTotal()) . '円';
          $row['shitei_bi_c'] = $voucher->getShippingOrderedDate() ? $voucher->getShippingOrderedDate()->format('Ymd') : '';

          $info = $voucher->getDeliveryInfo(); // 発送方法変更データが現状配送情報を保持している。
          if ($info) {
             $row['時間指定'] = $info->getReceiveOrderHopeDeliveryTimeSlotId();
             $row['営業所止め'] = $info->getReceiveOrderBusinessOfficeStopId();
             $row['営業所名'] = $info->getReceiveOrderBusinessOfficeName();
          }

          if ($shippingInfo) {
            $row['受注名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['購入者名']
            );
            $row['受注郵便番号'] = $shippingInfo['購入者郵便番号'];
            $row['受注者住所'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['購入者住所1'] . $shippingInfo['購入者住所2']
            );
            $row['受注者電話番号'] = $shippingInfo['購入者電話番号'];

            $row['発送先名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['発送先名']
            );
            $row['発送先郵便番号'] = $shippingInfo['発送先郵便番号'];
            $row['発送先住所'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['発送先住所1'] . $shippingInfo['発送先住所2']
            );
            $row['発送先電話番号'] = $shippingInfo['発送先電話番号'];
          } else {
            $row['受注名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getCustomerName()
            );
            $row['受注郵便番号'] = $voucher->getCustomerZipcode();
            $row['受注者住所'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getCustomerAddress1() . $voucher->getCustomerAddress2()
            );
            $row['受注者電話番号'] = $voucher->getCustomerTel();

            $row['発送先名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getDeliveryName()
            );
            $row['発送先郵便番号'] = $voucher->getDeliveryZipcode();
            $row['発送先住所'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getDeliveryAddress1() . $voucher->getDeliveryAddress2()
            );
            $row['発送先電話番号'] = $voucher->getDeliveryTel();
          }

          // 依頼主：ひとまず決め打ち
					//20190131 更新　南京終から古市へ
/*
          $row['企業名'] = '株式会社フォレスト';
          $row['企業郵便番号'] = '6308141';
          $row['企業住所'] = '奈良県奈良市南京終町778-2';
          $row['企業電話番号'] = '05053056841';
*/
          $row['企業名'] = '株式会社フォレスト';
          $row['企業郵便番号'] = '6308424';
          $row['企業住所'] = '奈良県奈良市古市町789';
          $row['企業電話番号'] = '05053056840';

          $row['取扱商品'] = '洋服';

          $row['郵便種別'] = '';
          $row['元・着払い種別'] = '1'; // ひとまずは「1」で固定（利用していない）

          $row['代引き種別'] = $voucher->isDaibiki() ? 'Yes' : 'No';
          $row['総数量'] = $voucher->getOrderedNumTotal();

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          $exportFile->fwrite($line);

          flush();
        }
      }
    );

    return $response;
  }

//  /**
//   * @param $vouchers, $shippingMethodCode, $now
//   */
  /**
   * @param $vouchers
   * @param $shippingMethodCode
   * @param $now
   * @return Response|StreamedResponse
   */
  private function createShippingLabelCsvResponseClickPost($vouchers, $shippingMethodCode, $shippingInfos, $now)
  {
    $logger = $this->getLogger();
    try {
      /** @var FileUtil $fileUtil */
      $fileUtil = $this->getFileUtil();
      /** @var \MiscBundle\Util\StringUtil $stringUtil */
      $stringUtil = $this->getContainer()->get('misc.util.string');
      $exportDir = $fileUtil->getWebCsvDir() . '/Download/ClickPost';
      $fs = new FileSystem();
      if (!$fs->exists($exportDir)) {
        $fs->mkdir($exportDir, 0777);
      }

      //1ファイル インポート40行までだが20行に固定して事故等防止
      $limit = self::MALL_ORDER_CLICK_CSV_ROW_LIMIT;
      $eol = "\r\n";

      // ヘッダ
      $headers = [
        'お届け先郵便番号' => 'お届け先郵便番号'
        , 'お届け先氏名' => 'お届け先氏名'
        , 'お届け先敬称' => 'お届け先敬称'
        , 'お届け先住所1行目' => 'お届け先住所1行目'
        , 'お届け先住所2行目' => 'お届け先住所2行目'
        , 'お届け先住所3行目' => 'お届け先住所3行目'
        , 'お届け先住所4行目' => 'お届け先住所4行目'
        , '内容品' => '内容品'
      ];
      $header = $stringUtil->convertArrayToCsvLine($headers,[], array_keys($headers) , ',') . $eol;
      $headerLine = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');

      //本データ作成
      $fileIndex = 1;
      $voucherIndex = 1;
      $exportFile = null;

      foreach ($vouchers as $voucher) {
        if (!isset($exportFile)) {
          //書き込み用のファイル作成
          $fileName = $this->createShippingLabelFileName($shippingMethodCode, $now->format('Y-m-d H:i:s'), $fileIndex++);
          $filePath = sprintf('%s/%s', $exportDir, $fileName);

          $exportFile = new \SplFileObject($filePath, 'w');
          $exportFile->fwrite($headerLine);
        }
        $shippingInfo = null;
        if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
          $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
        }
        $row = [];

        if ($shippingInfo) {
          $row['お届け先郵便番号'] = $shippingInfo['発送先郵便番号'];
          $row['お届け先氏名'] = $shippingInfo['発送先名'];
          $row['お届け先住所1行目'] = $shippingInfo['発送先住所1'];
          $row['お届け先住所2行目'] = $shippingInfo['発送先住所2'];
        } else {
          $row['お届け先郵便番号'] = $voucher->getDeliveryZipcode();
          $row['お届け先氏名'] = $voucher->getDeliveryName();
          $row['お届け先住所1行目'] = $voucher->getDeliveryAddress1();
          $row['お届け先住所2行目'] = $voucher->getDeliveryAddress2();
        }
        $row['お届け先敬称'] = '様';
        $row['お届け先住所3行目'] = '';
        $row['お届け先住所4行目'] = '<' . $voucher->getVoucherNumber() . '>';
        $row['内容品'] = '衣類';

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers),array_keys($headers), ',') . $eol;
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
        $exportFile->fwrite($line);

        if (($voucherIndex++) % $limit === 0) {
          $fileName = $this->createShippingLabelFileName($shippingMethodCode, $now->format('Y-m-d H:i:s'), $fileIndex++);
          $filePath = sprintf('%s/%s', $exportDir, $fileName);
          $exportFile = new \SplFileObject($filePath, 'w');
          $exportFile->fwrite($headerLine);
        }
      }
      //zipファイル作成
      $finder = new Finder();
      $files = $finder->in($exportDir)->name(sprintf('宛名_クリックポスト_%s_*.csv',$now->format('YmdHis')))->files();

      if ($files->count() > 0) {
        $downloadFilePath = sprintf(sprintf('%s/クリックポスト_%s.zip', $exportDir, $now->format('YmdHis')));

        $zip = new \ZipArchive();
        if (!$zip->open($downloadFilePath, \ZipArchive::CREATE)) {
          throw new \RuntimeException('can not create zip file. aborted. [' . $downloadFilePath . ']');
        }
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
          $zip->addFile($file->getPathname(), $file->getBasename());
        }

      } else {
        $downloadFilePath = sprintf(sprintf('%s/no-data.txt', $exportDir));
        $exportFile = new \SplFileObject($downloadFilePath, 'w');
        $exportFile->fwrite('');
        unset($exportFile);
      }

      // ダウンロード出力
      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($downloadFilePath) {
          $outputFile = new \SplFileObject('php://output', 'w');

          $outputFile->fwrite(file_get_contents($downloadFilePath));
          flush();
        }
      );

      $logger->info('file path: ' . $downloadFilePath);

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', basename($downloadFilePath)));
    } catch (\Exception $e) {
      $logger->info($e->getMessage());
      $logger->error($e->getTraceAsString());

      $response = new Response($e->getMessage(), 500);
    }
    return $response;
  }

  /**
   * @param VSalesVoucher[] $vouchers
   * @return StreamedResponse
   */
  private function createShippingLabelCsvResponseYuubin($vouchers, $shippingMethodCode, $shippingInfos)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = [
        '伝票no' => '伝票no'
      , '受注no' => '受注no'
      , '合計金額' => '合計金額'
      , '合計金額（単位）' => '合計金額（単位）'
      , 'shitei_bi_a' => 'shitei_bi_a'
      , 'shitei_bi_b' => 'shitei_bi_b'
      , 'shitei_bi_c' => 'shitei_bi_c'
      , '時間指定' => '時間指定'
      , '便種' => '便種'
      , 'シール１（佐川専用）' => 'シール１（佐川専用）'
      , 'シール2（佐川専用）' => 'シール2（佐川専用）'
      , 'シール3（佐川専用）' => 'シール3（佐川専用）'
      , 'シール4（佐川専用）' => 'シール4（佐川専用）'
      , '営業所止め' => '営業所止め'
      , '営業所名' => '営業所名'
      , '送り状区分（ヤマト専用）' => '送り状区分（ヤマト専用）'
      , '温度区分（ヤマト専用）' => '温度区分（ヤマト専用）'
      , '受注名' => '受注名'
      , '受注郵便番号' => '受注郵便番号'
      , '受注者住所' => '受注者住所'
      , '受注者電話番号' => '受注者電話番号'
      , '発送先名' => '発送先名'
      , '発送先郵便番号' => '発送先郵便番号'
      , '発送先住所' => '発送先住所'
      , '発送先電話番号' => '発送先電話番号'
      , 'option1' => 'option1'
      , 'option2' => 'option2'
      , 'option3' => 'option3'
      , 'option4' => 'option4'
      , 'option5' => 'option5'
      , 'option6' => 'option6'
      , '企業名' => '企業名'
      , '企業郵便番号' => '企業郵便番号'
      , '企業住所' => '企業住所'
      , '企業電話番号' => '企業電話番号'
      , '取扱商品' => '取扱商品'
      , '郵便種別' => '郵便種別'
      , '元・着払い種別' => '元・着払い種別'
      , '代引き種別' => '代引種別'
      , '総数量' => '総数量'
    ];

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($vouchers, $shippingInfos, $stringUtil, $headers, $shippingMethodCode)
      {
        $eol = "\r\n";
        $exportFile = new \SplFileObject('php://output', 'w');

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $exportFile->fwrite($header);

        foreach($vouchers as $voucher) {
          $item = $voucher->toScalarArray();
          $row = $item;
          $shippingInfo = null;
          if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
            $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
          }

          $row['伝票no'] = $voucher->getVoucherNumber();
          $row['受注no'] = $voucher->getOrderNumber();

          $row['合計金額'] = $voucher->isDaibiki() ? $voucher->getPaymentTotal() : 0; // 代引き料金
          $row['合計金額（単位）'] = intval($voucher->getPaymentTotal()) . '円';
          $row['shitei_bi_c'] = $voucher->getShippingOrderedDate() ? $voucher->getShippingOrderedDate()->format('Ymd') : '';

          $info = $voucher->getDeliveryInfo(); // 発送方法変更データが現状配送情報を保持している。
          if ($info) {
            $row['時間指定'] = $info->getReceiveOrderHopeDeliveryTimeSlotId();
            $row['温度区分（ヤマト専用）'] = $info->getReceiveOrderTemperatureId();
          }

          if ($shippingInfo) {
            $row['受注名'] = $shippingInfo['購入者名'];
            $row['受注郵便番号'] = $shippingInfo['購入者郵便番号'];
            $row['受注者住所'] = $shippingInfo['購入者住所1'] . $shippingInfo['購入者住所2'];
            $row['受注者電話番号'] = $shippingInfo['購入者電話番号'];

            $row['発送先名'] = $shippingInfo['発送先名'];
            $row['発送先郵便番号'] = $shippingInfo['発送先郵便番号'];
            $row['発送先住所'] = $shippingInfo['発送先住所1'] . $shippingInfo['発送先住所2'];
            $row['発送先電話番号'] = $shippingInfo['発送先電話番号'];
          } else {
            $row['受注名'] = $voucher->getCustomerName();
            $row['受注郵便番号'] = $voucher->getCustomerZipcode();
            $row['受注者住所'] = $voucher->getCustomerAddress1() . $voucher->getCustomerAddress2();
            $row['受注者電話番号'] = $voucher->getCustomerTel();

            $row['発送先名'] = $voucher->getDeliveryName();
            $row['発送先郵便番号'] = $voucher->getDeliveryZipcode();
            $row['発送先住所'] = $voucher->getDeliveryAddress1() . $voucher->getDeliveryAddress2();
            $row['発送先電話番号'] = $voucher->getDeliveryTel();
          }
          $row['受注名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $row['受注名']
          );
          $row['受注者住所'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $row['受注者住所']
          );
          $row['発送先名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $row['発送先名']
          );
          $row['発送先住所'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $row['発送先住所']
          );

          // 依頼主：ひとまず決め打ち
					//20190131 更新　南京終から古市へ
/*
          $row['企業名'] = '株式会社フォレスト';
          $row['企業郵便番号'] = '6308141';
          $row['企業住所'] = '奈良県奈良市南京終町778-2';
          $row['企業電話番号'] = '05053056841';
*/
          $row['企業名'] = '株式会社フォレスト';
          $row['企業郵便番号'] = '6308424';
          $row['企業住所'] = '奈良県奈良市古市町789';
          $row['企業電話番号'] = '05053056840';

          $row['取扱商品'] = '洋服';

          // 郵便種別
          // ゆうパックプリントRのマニュアルに指定が無い？ NextEngine出力値に合わせる
          switch ($shippingMethodCode) {
            case TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK:
              $row['郵便種別'] = '0';
              break;
            case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI_DAIBIKI:
              $row['郵便種別'] = '3';
              break;
            case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI: // fallthrough ※定形外は空文字
            case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEI: // ※定形は空文字
            default:
              $row['郵便種別'] = '';
              break;
          }
          $row['元・着払い種別'] = '1'; // ひとまずは定形外で出ている「1」で固定。（ゆうパック・ゆうパケットは0？謎。）

          $row['代引き種別'] = $voucher->isDaibiki() ? 'Yes' : 'No';
          $row['総数量'] = $voucher->getOrderedNumTotal();

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          $exportFile->fwrite($line);

          flush();
        }
      }
    );

    return $response;
  }

  /**
   * @param VSalesVoucher[] $vouchers
   * @param $shippingInfos
   * @return StreamedResponse
   */
  private function createShippingLabelCsvResponseYuuPacket($vouchers, $shippingInfos)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = [
        '伝票no' => '伝票no'
      , '発送先名' => '発送先名'
      , '発送先郵便番号' => '発送先郵便番号'
      , '発送先住所1' => '発送先住所1'
      , '発送先住所2' => '発送先住所2'
      , '発送先住所3' => '発送先住所3'
      , '発送先電話番号' => '発送先電話番号'
      , '企業名' => '企業名'
      , '企業郵便番号' => '企業郵便番号'
      , '企業住所' => '企業住所'
      , '企業電話番号' => '企業電話番号'
      , '郵便種別' => '郵便種別'
      , '並び順' => '並び順'
    ];

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($vouchers, $shippingInfos, $stringUtil, $headers)
      {
        $eol = "\r\n";
        $exportFile = new \SplFileObject('php://output', 'w');

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $exportFile->fwrite($header);

        $voucherCount = 1;
        foreach($vouchers as $voucher) {
          $item = $voucher->toScalarArray();
          $row = $item;
          $shippingInfo = null;
          if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
            $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
          }

          $row['伝票no'] = $voucher->getVoucherNumber();
          $shippingAddreses = [];
          if ($shippingInfo) {
            $row['発送先名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['発送先名']
            );
            $row['発送先郵便番号'] = $shippingInfo['発送先郵便番号'];
            $row['発送先電話番号'] = $shippingInfo['発送先電話番号'];
            $shippingAddreses = $this->convertAddressForYuuPacket(
              $shippingInfo['発送先住所1'] . $shippingInfo['発送先住所2']
            );
          } else {
            $row['発送先名'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getDeliveryName()
            );
            $row['発送先郵便番号'] = $voucher->getDeliveryZipcode();
            $row['発送先電話番号'] = $voucher->getDeliveryTel();
            $shippingAddreses = $this->convertAddressForYuuPacket(
              $voucher->getDeliveryAddress1() . $voucher->getDeliveryAddress2()
            );
          }
          $row['発送先住所1'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $shippingAddreses['発送先住所1']
          );
          $row['発送先住所2'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $shippingAddreses['発送先住所2']
          );
          $row['発送先住所3'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
            $shippingAddreses['発送先住所3']
          );

          // 依頼主：ひとまず決め打ち
          $row['企業名'] = '株式会社フォレスト';
          $row['企業郵便番号'] = '6308424';
          $row['企業住所'] = '奈良県奈良市古市町789';
          $row['企業電話番号'] = '05053056840';

          $row['郵便種別'] = '9';

          $row['並び順'] = $voucherCount;
          $voucherCount++;

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          $exportFile->fwrite($line);

          flush();
        }
      }
    );

    return $response;
  }

  /**
   * 住所を分割する。前詰めで住所1と2に20文字以内ずつ、余った分はすべて3へ格納。
   * @param string $address 住所
   * @return array 分割した発送先住所が格納された配列
   */
  public function convertAddressForYuuPacket(string $address) {
    $regAddress = '/(...??[都道府県])((?:旭川|伊達|石狩|盛岡|奥州|田村|南相馬|那須塩原|東村山|武蔵村山|羽村|十日町|上越|富山|野々市|大町|蒲郡|四日市|姫路|大和郡山|廿日市|下松|岩国|田川|大村)市|.+?郡(?:玉村|大町|.+?)[町村]|.+?市.+?区|.+?[市区町村])(.*?)((?:[0-9０-９])(?:[-−‐ー－―0-9０-９]|丁目|番地|番|号)*)(.*)/u';
    $matches = [];
    preg_match($regAddress, $address, $matches);
    $splitedAddreses = [];
    $shippingAddreses = ['発送先住所1' => '', '発送先住所2' => '', '発送先住所3' => ''];
    $regSplit = '/[-−‐－―0-9０-９]+/u';
    // 正規表現にマッチしなかった場合
    if (count($matches) != 6) {
      if (20 < mb_strlen($address)) {
        $pos = $this->getStrLastPosByRegex($regSplit, $address, 20);
        $shippingAddreses['発送先住所1'] = mb_substr($address, 0, $pos);
        $shippingAddreses['発送先住所2'] = mb_substr($address, $pos);
        if (20 < strlen($shippingAddreses['発送先住所2'])) {
          $pos = $this->getStrLastPosByRegex($regSplit, $shippingAddreses['発送先住所2'], 20);
          $shippingAddreses['発送先住所3'] = mb_substr($shippingAddreses['発送先住所2'], $pos);
          $shippingAddreses['発送先住所2'] = mb_substr($shippingAddreses['発送先住所2'], 0, $pos);
        }
      } else {
        $shippingAddreses['発送先住所1'] = $address;
      }
      return $shippingAddreses;
    }

    // マッチした場合
    $splitedAddreses = [
      '都道府県' => $matches[1],
      '市区町村郡' => $matches[2],
      '字' => $matches[3],
      '番地' => $matches[4],
      '建物名など' => $matches[5]
    ];
    $shippingAddreses['発送先住所1'] =
      $splitedAddreses['都道府県']
      . $splitedAddreses['市区町村郡']
      . $splitedAddreses['字'];

    if (20 < strlen($shippingAddreses['発送先住所1'])) {
      $shippingAddreses['発送先住所2'] .= mb_substr($shippingAddreses['発送先住所1'], 20);
      $shippingAddreses['発送先住所1'] = mb_substr($shippingAddreses['発送先住所1'], 0, 20);
    }
    $shippingAddreses['発送先住所2'] .= $splitedAddreses['番地'] . $splitedAddreses['建物名など'];

    // 20文字より多い場合は数字とハイフンで検索して分割、数字記号が途切れないようにする
    if (20 < mb_strlen($shippingAddreses['発送先住所2'])) {
      $pos = $this->getStrLastPosByRegex($regSplit, $shippingAddreses['発送先住所2'], 20);
      $shippingAddreses['発送先住所3'] = mb_substr($shippingAddreses['発送先住所2'], $pos);
      $shippingAddreses['発送先住所2'] = mb_substr($shippingAddreses['発送先住所2'], 0, $pos);
    }
    return $shippingAddreses;
  }

  /**
   * 与えられた正規表現で検索し、合致した最後の位置を返す。
   * 位置以前の文字数が指定のmaxLengthに収まらなければ自身を再帰呼び出しする。
   * @param string $regSplit 正規表現文字列
   * @param string $address 住所
   * @param int $maxLength 文字列の最大長
   * @return 最後に合致した正規表現の位置（マルチバイト）、合致しなければ$lengthの値を返す
   */
  private function getStrLastPosByRegex($regSplit, $targetStr, $maxLength) {
    $maches = [];
    preg_match_all($regSplit, $targetStr, $maches);

    // 正規表現で拾えなければlengthを返す
    if (empty($maches[0])) {
      return $maxLength;
    }

    $macheLastStr = $maches[0][count($maches[0]) - 1];
    $pos = mb_strrpos($targetStr, $macheLastStr, -1);

    // 位置が先頭となってしまい、これ以上区切れない場合はlengthを返す
    if ($pos == 0) {
      return $maxLength;
    }
    $splitedStr = mb_substr($targetStr, 0, $pos);

    // 指定文字数に収まらなければ再帰呼び出し
    if ($maxLength < mb_strlen($splitedStr)) {
      $pos = $this->getStrLastPosByRegex($regSplit, $splitedStr, $maxLength);
    };
    return $pos;
  }

  /**
   * @param VSalesVoucher[] $vouchers
   * @param $shippingMethodCode
   * @return StreamedResponse
   * @throws \Exception
   */
  private function createShippingLabelCsvResponseRakuten($vouchers, $shippingMethodCode, $shippingInfos)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = TbRakutenExpressConversionRepository::$RAKUTEN_EXPRESS_HEADER;

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($vouchers, $shippingMethodCode, $shippingInfos, $stringUtil, $headers)
      {
        $eol = "\r\n";
        $exportFile = new \SplFileObject('php://output', 'w');

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $exportFile->fwrite($header);

        // #125650 送り状PDF生成処理が、CSVと逆順に出力するため、CSV生成前に順序を反転する
        switch($shippingMethodCode) {
          case TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK_RSL: // ゆうパック(RSL)
            $vouchers = array_reverse($vouchers);
            break;
    
          default:
            break;
        }

        foreach($vouchers as $voucher) {
          $item = $voucher->toScalarArray();
          $row = $item;
          $shippingInfo = null;
          if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
            $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
          }

          $row['配送キャリア'] = '';
          $row['受注番号'] = $voucher->getVoucherNumber();
          $row['配送方法'] = '0';
          $row['温度区分'] = '0';
          $row['決済方法'] = '1';
          $row['荷物のサイズ'] = '';
          $row['荷物の重量'] = '25';
          $row['お届け希望日'] = '';
          $row['お届け希望時間帯コード'] = '';

          /** @var TbRakutenExpressConversionRepository $repo */
          $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenExpressConversion');

          if ($shippingInfo) {
            $row['お届け先郵便番号'] = $shippingInfo['発送先郵便番号'];
            $row['お届け先お名前'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['発送先名']
            );
            $row['お届け先電話番号'] = $shippingInfo['発送先電話番号'];
            $address = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $shippingInfo['発送先住所1'] . $shippingInfo['発送先住所2']
            );
            $repo->convertAddress($row, $address, TbRakutenExpressConversionRepository::DELIVERY_PREFIX);
          } else {
            $row['お届け先郵便番号'] = $voucher->getDeliveryZipcode();
            $row['お届け先お名前'] = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getDeliveryName()
            );
            $row['お届け先電話番号'] = $voucher->getDeliveryTel();
            $address = $stringUtil->convertCommaAndAmpersandToFullwidth(
              $voucher->getDeliveryAddress1() . $voucher->getDeliveryAddress2()
            );
            $repo->convertAddress($row, $address, TbRakutenExpressConversionRepository::DELIVERY_PREFIX);
          }

          // 依頼主はフォレスト固定
          $row["ご依頼主郵便番号"] = "6308424";
          $row["ご依頼主都道府県"] = "奈良県";
          $row["ご依頼主市区郡町村"] = "奈良市古市町";
          $row["ご依頼主町・番地"] = "789";
          $row["ご依頼主建物名・部屋番号"] = "";
          $row["ご依頼主お名前"] = "株式会社フォレスト";
          $row["ご依頼主電話番号"] = "05053056840";

          $row['品名'] = '衣類';
          $row['記事'] = '';
          $row['荷扱い区分1'] = '';
          $row['荷扱い区分2'] = '';
          $row['置き配'] = '0';
          $row['消費税金額'] = '';
          $row['請求金額'] = '';
          $row['顧客管理番号'] = '';

          switch($voucher->getNeMallId()){
            // 楽天
            case TbShoppingMall::NE_MALL_ID_RAKUTEN :
            case TbShoppingMall::NE_MALL_ID_RAKUTEN_SHANZE :
            case TbShoppingMall::NE_MALL_ID_RAKUTEN_LOGI :
            case TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI :
            case TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO :
            case TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST :
            case TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA :
              $row['サイト区分'] = '0';
              break;

            // Yahoo
            case TbShoppingMall::NE_MALL_ID_YAHOO :
            case TbShoppingMall::NE_MALL_ID_OTORIYOSE :
            case TbShoppingMall::NE_MALL_ID_KAWA_E_MON :
              $row['サイト区分'] = '1';
              break;

            // Amazon
            case TbShoppingMall::NE_MALL_ID_AMAZON :
            case TbShoppingMall::NE_MALL_ID_AMAZON_COM :
              $row['サイト区分'] = '2';
              break;

            // DeNA
            case TbShoppingMall::NE_MALL_ID_WOWMA :
              $row['サイト区分'] = '3';
              break;

            // その他
            default :
              $row['サイト区分'] = '9';
              break;
          }

          switch($shippingMethodCode) {
            case TbSalesDetail::SHIPPING_METHOD_CODE_YAMATO: // ﾔﾏﾄ(発払い)B2v6
              $row['出荷予定日'] = date('Y/m/d');
              break;
      
            default:
              $row['出荷予定日'] = '';
              break;
          }

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          $exportFile->fwrite($line);

          flush();
        }
      }
    );

    return $response;
  }

  /**
   * SKU重量・サイズチェックおよびネコポスサイズ制限値 取得
   * ランサーズ 株式会社ちよだ様「有能な開発者」実装。そのまま記念に保存。
   */
  public function getSkuWeightSizeLimits()
  {
    // SKU重量・サイズ設定値取得
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
    $keys = [
        ['ws_limits_weight_ubound','weight_ubound']
      , ['ws_limits_weight_lbound','weight_lbound']
      , ['ws_limits_side1_ubound','side1_ubound']
      , ['ws_limits_side2_ubound','side2_ubound']
      , ['ws_limits_side3_ubound','side3_ubound']
      , ['ws_limits_sides_ubound','sides_ubound']
      , ['ws_limits_weight_aubound','weight_aubound']
    ] ;
    $settingKeys = array();
    foreach($keys as $key) {
      $setting = $settingRepo->findOneBy(['settingKey'=>$key[0]]);
      $settingKeys[] = [$key[1],$setting->getSettingVal()];
    }

    $wsLimits = [
        $settingKeys[0][0] => intval($settingKeys[0][1])
      , $settingKeys[1][0] => intval($settingKeys[1][1])
      , $settingKeys[2][0] => floatval($settingKeys[2][1])
      , $settingKeys[3][0] => floatval($settingKeys[3][1])
      , $settingKeys[4][0] => floatval($settingKeys[4][1])
      , $settingKeys[5][0] => floatval($settingKeys[5][1])
      , $settingKeys[6][0] => intval($settingKeys[6][1])
    ];

    return $wsLimits;
  }

  public function getListDesc()
  {
    $descRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSkuDescriptions');
    $listDesc = $descRepo->findAll();
    $data = [];
    foreach($listDesc as $item) {
      $data[] = $item->toArray();
    }
    return $data;
  }

  /**
   * ネコポス設定値
   */
  public function getNekoposuSizeWeightLimits()
  {
    $limits = $this->getSkuWeightSizeLimits();
    return [
        'side1'  => $limits['side1_ubound']
      , 'side2'  => $limits['side2_ubound']
      , 'side3'  => $limits['side3_ubound']
      , 'weight' => $limits['weight_aubound']
    ];

  }

  /**
   * DB印刷待ち件数　取得
   */
  public function getPrintCount()
  {
      /** @var Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      // DBの印刷済み件数を取得
      $sql = <<<EOD
        SELECT
          COUNT(DISTINCT d.伝票番号) AS CNT
        FROM tb_sales_detail_analyze d
        WHERE 受注状態 = "納品書印刷済" AND 明細行キャンセル = 0
EOD;
      $stmt = $dbMain->query($sql);
      $dbCount = intval($stmt->fetchColumn(0));

    return $dbCount;
  }


}
