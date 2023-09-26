<?php
/**
 * 伝票毎集計 バッチ処理
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\AnalyzedSalesDetailRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class AggregateSalesDetailCommand extends PlusnaoBaseCommand
{

  protected function configure()
  {
    $this
      ->setName('batch:aggregate-sales-detail')
      ->setDescription('受注明細 伝票毎集計処理')
      ->addOption('start-date', null, InputOption::VALUE_OPTIONAL, '集計開始年月日 ※受注明細取込分のみがデフォルト運用')
      ->addOption('end-date', null, InputOption::VALUE_OPTIONAL, '集計終了年月日 ※受注明細取込分のみがデフォルト運用')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '伝票毎利益集計';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $doctrine = $container->get('doctrine');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $time = microtime(true);

    // SHOPLIST設定
    $shoppingMallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);

    // データ取得範囲
    $startDateStr = $input->getOption('start-date');
    $endDateStr = $input->getOption('end-date');
    $startDate = $startDateStr ? new \DateTime($startDateStr) : null;
    $endDate = $endDateStr ? new \DateTime($endDateStr) : null;
    $logger->info(sprintf('伝票毎集計 範囲： %s - %s', $startDateStr ?? '-', $endDateStr ?? '-'));
    $logger->addDbLog($logger->makeDbLog(
      $this->commandName,
      '集計範囲',
      $startDateStr ? "開始日[{$startDateStr}]" : '全期間'
    ));


    $logger->info('現在のデータ削除 開始');

    // 更新対象テーブル データ削除
    $targetTables = [
      // 日単位 更新
        'TbSalesDetailProfit' // tb_sales_detail_profit
      , 'TbSalesDetailVoucher' // tb_sales_detail_voucher
      , 'TbSalesDetailVoucherRepeater' // tb_sales_detail_voucher_repeater
      , 'TbSalesDetailVoucherRepeaterShop' // tb_sales_detail_voucher_repeater_shop
      , 'TbSalesDetailSummaryYmd' // tb_sales_detail_summary_ymd

      // 月単位 更新
      , 'TbSalesDetailVoucherOrderYm' // tb_sales_detail_voucher_order_ym
      , 'TbSalesDetailVoucherOrderYmRepeater' // tb_sales_detail_voucher_order_ym_repeater
      , 'TbSalesDetailVoucherItemOrderYm' // tb_sales_detail_voucher_item_order_ym
      , 'TbSalesDetailSummaryItemYm' // tb_sales_detail_summary_item_ym

      , 'TbSalesDetailVoucherYmShop' // tb_sales_detail_voucher_ym_shop
      , 'TbSalesDetailVoucherYmShopRepeater' // tb_sales_detail_voucher_ym_shop_repeater

      //　全更新（出荷日集計）
      , 'TbSalesDetailVoucherShippingYm' // tb_sales_detail_voucher_shipping_ym 全更新が必要
      , 'TbSalesDetailVoucherItemShippingYm' // tb_sales_detail_voucher_item_shipping_ym 全更新が必要
    ];
    foreach($targetTables as $tableClass) {

      $logger->info($tableClass . ' deleting');

      /** @var AnalyzedSalesDetailRepository $repo */
      $repo = $doctrine->getRepository('MiscBundle:' . $tableClass);
      $repo->deleteByDateRange($startDate, $endDate, $logger);
    }

    $logger->info('現在のデータ削除 完了');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 除外店舗
    $exceptShopCondition = $this->buildExceptShop();

    //'====================
    //'tb_sales_detail_voucher（伝票レベル）の作成
    //'====================
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注日');
    $dateConditionStr = $dateConditions
                      ? (" AND " . implode(" AND ", $dateConditions))
                      : '';

    // 危なっかしいGroup By。現行ママ
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher (
          伝票番号
        , 受注年月日
        , 受注年
        , 受注月
        , 出荷年月日
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 購入者名
        , 購入者電話番号

        , ポイント数を含む総合計

        , 受注数合計
      )
      SELECT
          伝票番号
        , 受注日 AS 受注日の先頭
        , 受注年 AS 受注年の先頭
        , 受注月 AS 受注月の先頭
        , 出荷確定日 AS 出荷確定日の先頭
        , 総合計 AS 総合計の先頭
        , 商品計 AS 商品計の先頭
        , 税金 AS 税金の先頭
        , 発送代 AS 発送代の先頭
        , 手数料 AS 手数料の先頭
        , 他費用 AS 他費用の先頭
        , ポイント数 AS ポイント数の先頭
        , 購入者名 AS 購入者名の先頭
        , 購入者電話番号 AS 購入者電話番号の先頭

        , 総合計 + ポイント数

        , SUM(受注数) AS 受注数合計
      FROM
        tb_sales_detail_analyze
      WHERE
              キャンセル区分 = '0'
          AND 明細行キャンセル = '0'
          AND {$exceptShopCondition}
          {$dateConditionStr}
      GROUP BY
           伝票番号
         /*
         , 受注年
         , 受注月
         , 総合計
         , 商品計
         , 税金
         , 発送代
         , 手数料
         , 他費用
         , ポイント数
         , 購入者名
         , 購入者電話番号
         */
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $this->processExecuteLog->setProcessNumber1($stmt->rowCount()); // 処理件数
    $this->processExecuteLog->setVersion(1.1);

    $logger->info('tb_sales_detail_voucher（伝票レベル）の作成');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //'tb_sales_detail_profit（明細レベル）の作成
    //'====================
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注日');
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';

    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_profit (
          伝票番号
        , 明細行
        , 受注番号
        , 受注年月日
        , 出荷年月日
        , キャンセル区分
        , 店舗コード
        , 配送方法コード
        , 支払方法コード
        , ポイント数を含む総合計
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 受注状態
        , 明細行キャンセル
        , 商品コード
        , 商品オプション
        , 受注数
        , 引当数
        , 売単価
        , 小計
        , 受注年
        , 受注月

        , 代表商品コード
      )
      SELECT
          伝票番号
        , 明細行
        , 受注番号
        , 受注日
        , 出荷確定日
        , キャンセル区分
        , 店舗コード
        , 配送方法コード
        , 支払方法コード
        , 総合計 + ポイント数 AS ポイント数を含む総合計
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 受注状態
        , 明細行キャンセル
        , 商品コード（伝票）
        , 商品オプション
        , 受注数
        , 引当数
        , IFNULL(seti.distribution_unit_price, a.売単価) -- セット商品の場合は案分後から取る、そうでなければ通常
        , IFNULL(seti.distribution_subtotal, a.小計) -- セット商品の場合は案分後から取る、そうでなければ通常
        , 受注年
        , 受注月

        , daihyo_syohin_code
      FROM
        tb_sales_detail_analyze a
      LEFT JOIN tb_sales_detail_set_distribute_info seti ON a.伝票番号 = seti.voucher_number AND a.明細行 = seti.line_number
      WHERE
              キャンセル区分 = '0'
          AND 明細行キャンセル = '0'
          AND {$exceptShopCondition}
          {$dateConditionStr}

EOD;

    $stmt = $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の作成');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $this->processExecuteLog->setProcessNumber2($stmt->rowCount()); // 処理件数2 明細行数


    //'====================
    //'tb_sales_detail_voucher（伝票レベル）の更新
    //'====================

    // 店舗コード・支払い方法コード・配送方法コード
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';

    // ※これは、明細のすべてが同じものという前提による（MySQLの寛容すぎるGROUP BY による実装）実装
    //   ・・・最初の voucher INSERTと同じ方法だが、その時に入れてはいけない理由があるのか？
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS V
      INNER JOIN (
          SELECT
              伝票番号
            , 店舗コード
            , 支払方法コード
            , 配送方法コード
          FROM
            tb_sales_detail_profit

          {$dateConditionStr}

          GROUP BY
            伝票番号
      ) AS P ON V.伝票番号 = P.伝票番号
      SET V.店舗コード = P.店舗コード
        , V.支払方法コード = P.支払方法コード
        , V.配送方法コード = P.配送方法コード
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 01. 店舗コード、支払い方法コード、配送方法コード');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 配送料額　受注日時点の価格にならないのは現在は許容（#164907）
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS V
      INNER JOIN tb_delivery_method AS D ON V.配送方法コード = D.delivery_id
      SET V.配送料額 = D.delivery_cost
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 02. 配送料額');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $codAbnormal = $commonUtil->getSettingValue('COD_ABNORMAL_POSTAGE');
    $codSagawa = $commonUtil->getSettingValue('COD_SAGAWA_POSTAGE');


    //'支払方法＝代金引換(1)　受注日時点の価格にならないのは現在は許容（#164907）
    //'配送方法＝定形外郵便(代引)(40)
    //'配送方法＝定形外郵便(佐川急便(e飛伝2))(13)

    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';

    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 代引手数料額 = :codAbnormal
      WHERE 支払方法コード = 1
        AND 配送方法コード = 40
        {$dateConditionStr}
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':codAbnormal', $codAbnormal);
    $stmt->execute();
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 03. 代引き手数料更新 abnormal');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 代引手数料額 = :codSagawa
      WHERE 支払方法コード = 1
        AND 配送方法コード = 13
        {$dateConditionStr}
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':codSagawa', $codSagawa);
    $stmt->execute();
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 04. 代引き手数料更新 sagawa');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // モールシステム料率 受注日時点の価格にならないのは現在は許容（#164907）
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'P'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS P
      INNER JOIN tb_shopping_mall as M ON P.店舗コード = M.ne_mall_id
      SET モールシステム料率 = M.system_usage_cost_ratio
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 05. モールシステム料率');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // モール別支払方法別手数料率 受注日時点の価格にならないのは現在は許容（#164907）
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'P'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS P
        INNER JOIN tb_mall_payment_method AS M
          ON  P.店舗コード = M.ne_mall_id
          AND P.支払方法コード = M.payment_id
        INNER JOIN tb_payment_method AS PM
          ON P.支払方法コード = PM.payment_id
      SET P.モール別支払方法別手数料率 = IF(
            M.payment_cost_ratio = 0
          , PM.payment_cost_ratio
          , M.payment_cost_ratio
      )
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 06. モール別支払方法別手数料率');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET モールシステム料額 = ポイント数を含む総合計 * モールシステム料率 / 100
        , モール別支払方法別手数料額 = ポイント数を含む総合計 * モール別支払方法別手数料率 / 100
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 07. モールシステム料額, モール別支払方法別手数料額 計算');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 出荷年 = YEAR(出荷年月日)
        , 出荷月 = MONTH(出荷年月日)
        , 出荷年月 = CONCAT(YEAR(出荷年月日), LPAD(MONTH(出荷年月日), 2, '0'))
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 08. 出荷年, 出荷月');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 受注年月 = CONCAT( 受注年, LPAD(`受注月`,2,'0') )
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 09. 受注年月');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    /* 統合
    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 出荷年月 = CONCAT(出荷年, LPAD(出荷月, 2, '0'))
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 10. 出荷年月');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
    */

    // 伝票番号ごとの最遅を取得する処理なので、これは全レコード更新する。
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS V
        INNER JOIN (
          SELECT
              伝票番号
            , MAX(出荷予定年月日) AS 最遅出荷予定日
          FROM tb_sales_detail_analyze
          GROUP BY 伝票番号
        ) AS SUB ON V.伝票番号 = SUB.伝票番号
      SET V.出荷予定年月日 = SUB.最遅出荷予定日
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher（伝票レベル）の更新 11. 出荷予定年月日（最遅出荷予定日）');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //'====================
    //'tb_sales_detail_profit（明細レベル）の更新
    //'====================
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit
      SET 出荷年 = YEAR(出荷年月日)
        , 出荷月 = MONTH(出荷年月日)
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 01. 出荷年・出荷月');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      LEFT JOIN tb_shopping_mall AS T ON P.店舗コード = T.ne_mall_id
      SET 店舗名 = mall_name
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 02. 店舗名');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      LEFT JOIN tb_payment_method AS T ON P.支払方法コード = T.payment_id
      SET 支払方法名 = payment_name
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 03. 支払い方法コード');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      LEFT JOIN tb_delivery_method AS T ON P.配送方法コード = T.delivery_id
      SET 配送方法名 = delivery_name
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 05.配送方法名');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //

    /* => sales_detail_analyze 作成段階で頑張って入れているので、 tb_sales_detail_profit 作成時に入れるように変更。なのでスキップ
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS SD
      INNER JOIN tb_productchoiceitems AS PC ON SD.商品コード = PC.ne_syohin_syohin_code
      SET 代表商品コード = daihyo_syohin_code
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 06.代表商品コード');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //
    */

    // 07 諸々 JOIN

    // 日付の絞込は明細で。
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'P'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      LEFT JOIN tb_mainproducts     AS M    ON P.代表商品コード = M.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS CAL  ON P.代表商品コード = CAL.daihyo_syohin_code
      LEFT JOIN tb_vendormasterdata AS V    ON M.sire_code    = V.sire_code
      LEFT JOIN tb_shopping_mall    AS MALL ON P.店舗コード    = MALL.ne_mall_id
      LEFT JOIN tb_sales_detail_voucher AS VOUCHER ON P.伝票番号 = VOUCHER.伝票番号
      LEFT JOIN tb_sales_detail_set_distribute_info AS seti ON P.伝票番号 = seti.voucher_number AND seti.line_number = P.明細行
      SET P.仕入先コード = M.sire_code
        , P.付加費用額   = M.additional_cost * P.受注数
        , P.固定費用額   = CAL.fixed_cost    * P.受注数
        , P.仕入原価     = M.genka_tnk * P.受注数
        , P.仕入先名     = V.sire_name
        , P.仕入先費用率 = V.additional_cost_rate

        /* ( 仕入原価 + 付加費用額 ) * 仕入先費用率 / 100 */
        , P.仕入先費用額 = ROUND(
                          (
                              (M.genka_tnk * P.受注数) /* 仕入原価 */
                            + (M.additional_cost * P.受注数) /* 付加費用額 */
                            + (CAL.fixed_cost    * P.受注数) /* 固定費用額 */
                          ) * V.additional_cost_rate / 100   /* 仕入先費用率 */
                        , 0)

        /* 小計 - (仕入原価 + 付加費用額 + 固定費用額 + 仕入先費用額) */
        , P.明細粗利額   = IFNULL(seti.distribution_subtotal, P.小計)
                         /* 原価 & 諸費用 */
                         - COALESCE(
                                (M.genka_tnk * P.受注数) /* 仕入原価 */
                              + (M.additional_cost * P.受注数) /* 付加費用額 */
                              + (CAL.fixed_cost * P.受注数)    /* 固定費用額 */
                              + ROUND(
                                  (
                                      (M.genka_tnk * P.受注数) /* 仕入原価 */
                                    + (M.additional_cost * P.受注数) /* 付加費用額 */
                                    + (CAL.fixed_cost    * P.受注数) /* 固定費用額 */
                                  ) * V.additional_cost_rate / 100 /* 仕入先費用率 */
                                , 0) /* 仕入先費用額 */
                            , 0)

        /* 小計 - (仕入原価 + 付加費用額 + 固定費用額 + 仕入先費用額) + ((伝票諸費用 加算分 - 同経費 減算分) * 明細小計の伝票商品計における割合)  */
        , P.明細粗利額_伝票費用除外 =
                         IFNULL(seti.distribution_subtotal, P.小計)

                         /* 原価 & 諸費用 */
                         - COALESCE(
                                (M.genka_tnk * P.受注数) /* 仕入原価 */
                              + (M.additional_cost * P.受注数) /* 付加費用額 */
                              + (CAL.fixed_cost    * P.受注数) /* 固定費用額 */
                              + ROUND(
                                  (
                                      (M.genka_tnk * P.受注数) /* 仕入原価 */
                                    + (M.additional_cost * P.受注数) /* 付加費用額 */
                                    + (CAL.fixed_cost    * P.受注数) /* 固定費用額 */
                                  ) * V.additional_cost_rate / 100 /* 仕入先費用率 */
                                , 0) /* 仕入先費用額 */
                            , 0)

                         /* 伝票に加算されている諸費用と、その分の経費を共に小計額に応じて各明細に按分する */
                         + (
                              (
                                /* + 加算諸費用：発送代, 手数料, 他費用, 税金 ※消費税は粗利額に含める（従来の計算との整合のため） */
                                (
                                    VOUCHER.発送代
                                  + VOUCHER.手数料
                                  + VOUCHER.他費用

                                  + VOUCHER.税金
                                )

                                -

                                /* - 減算経費　：配送料額, 代引手数料, モールシステム料額, モール別支払い方法別手数料額 */
                                (
                                    VOUCHER.配送料額
                                  + VOUCHER.代引手数料額
                                  + VOUCHER.モールシステム料額
                                  + VOUCHER.モール別支払方法別手数料額
                                )
                              )
                              /* 明細小計に応じて按分 */
                              *
                              (IFNULL(seti.distribution_subtotal, P.小計) / IF(VOUCHER.商品計 >= IFNULL(seti.distribution_subtotal, P.小計), VOUCHER.商品計, IFNULL(seti.distribution_subtotal, P.小計)))
                           )

        /* 小計 + (伝票諸費用 加算分 * 明細小計の伝票商品計における割合)  */
        , P.小計_伝票料金加算 =
                         IFNULL(seti.distribution_subtotal, P.小計)

                         /* 伝票に加算されている諸費用を共に小計額に応じて各明細に按分する */
                         + (
                              /* + 加算諸費用：発送代, 手数料, 他費用, 税金 */
                              (
                                  VOUCHER.発送代
                                + VOUCHER.手数料
                                + VOUCHER.他費用

                                + VOUCHER.税金
                              )
                              /* 明細小計に応じて按分 */
                              *
                              (IFNULL(seti.distribution_subtotal, P.小計) / IF(VOUCHER.商品計 >= IFNULL(seti.distribution_subtotal, P.小計), VOUCHER.商品計, IFNULL(seti.distribution_subtotal, P.小計)))
                           )

        /* 伝票料金 加算額 * 明細小計の伝票商品計における割合  */
        , P.伝票料金加算額 =
                           (
                              /* + 加算諸費用：発送代, 手数料, 他費用, 税金 */
                              (
                                  VOUCHER.発送代
                                + VOUCHER.手数料
                                + VOUCHER.他費用

                                + VOUCHER.税金
                              )
                              /* 明細小計に応じて按分 */
                              *
                              (IFNULL(seti.distribution_subtotal, P.小計) / IF(VOUCHER.商品計 >= IFNULL(seti.distribution_subtotal, P.小計), VOUCHER.商品計, IFNULL(seti.distribution_subtotal, P.小計)))
                           )
        /* 伝票費用 減算額 * 明細小計の伝票商品計における割合  */
        , P.伝票費用減算額 =
                           (
                              /* 減算経費　：配送料額, 代引手数料, モールシステム料額, モール別支払い方法別手数料額 */
                              (
                                  VOUCHER.配送料額
                                + VOUCHER.代引手数料額
                                + VOUCHER.モールシステム料額
                                + VOUCHER.モール別支払方法別手数料額
                              )
                              /* 明細小計に応じて按分 */
                              *
                              (IFNULL(seti.distribution_subtotal, P.小計) / IF(VOUCHER.商品計 >= IFNULL(seti.distribution_subtotal, P.小計), VOUCHER.商品計, IFNULL(seti.distribution_subtotal, P.小計)))
                           )
      {$dateConditionStr}
EOD;

    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 07.仕入先コード、付加費用額、固定費用額、仕入原価 、仕入先関連、粗利額、伝票費用');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //

    // 明細粗利率 計算
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'P'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      LEFT JOIN tb_mainproducts     AS M    ON P.代表商品コード = M.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS CAL  ON P.代表商品コード = CAL.daihyo_syohin_code
      LEFT JOIN tb_vendormasterdata AS V    ON M.sire_code    = V.sire_code
      LEFT JOIN tb_shopping_mall    AS MALL ON P.店舗コード    = MALL.ne_mall_id
      LEFT JOIN tb_sales_detail_voucher AS VOUCHER ON P.伝票番号 = VOUCHER.伝票番号
      SET P.明細粗利率 = CASE
        WHEN P.小計_伝票料金加算 = 0 THEN 0
        ELSE P.明細粗利額_伝票費用除外 * 100 / P.小計_伝票料金加算
      END

      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 08. 明細粗利率');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //

    /* 順番移動、さらに 07 に統合
    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      LEFT JOIN tb_vendormasterdata AS T ON P.仕入先コード = T.sire_code
      SET 仕入先名 = sire_name
        , 仕入先費用率 = T.additional_cost_rate
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 04. 仕入先名 (※実行順変更。 元のシステムでは動いていなかった？)');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //
    */

    /* 07 に統合
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      INNER JOIN tb_mainproducts AS M ON P.代表商品コード = M.daihyo_syohin_code
      SET 付加費用額 = M.additional_cost * 受注数
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 08.付加費用額');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //
    */

    /* 07 に統合
    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      INNER JOIN tb_mainproducts_cal AS CAL ON P.代表商品コード=CAL.DAIHYO_SYOHIN_CODE
      SET 固定費用額=CAL.FIXED_COST*受注数
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 09. 固定費用額');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //
    */

    /* 07 に統合
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      INNER JOIN tb_mainproducts_cal AS CAL ON P.代表商品コード = CAL.daihyo_syohin_code
      SET 仕入原価 = CAL.genka_tnk_ave * 受注数
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 10. 仕入原価');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
    */

    /* 04 -> 07 に統合
    $sql = <<<EOD
      UPDATE TB_SALES_DETAIL_PROFIT AS P
      INNER JOIN tb_vendormasterdata AS V ON P.仕入先コード=V.sire_code
      SET 仕入先費用率 = V.additional_cost_rate
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_profit（明細レベル）の更新 11.仕入先費用率');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));      //
    */

    /* 07に統合
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit AS P
      SET 仕入先費用額 = (仕入原価+付加費用額)*仕入先費用率 / 100
EOD;
    */

    /* 07に統合
    $sql = <<<EOD
      UPDATE tb_sales_detail_profit
      SET 明細粗利額 = 小計 - (仕入原価 + 付加費用額 + 固定費用額 + 仕入先費用額)
EOD;
    */

    //'====================
    //'明細を集計してtb_sales_detail_voucher（伝票レベル）の更新
    //'====================

    /* 02. 仕入原価へ統合
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'V'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS V
      SET V.明細数 = (
        SELECT COUNT(*) AS 明細数
        FROM tb_sales_detail_profit
        WHERE 伝票番号 = V.伝票番号
      )
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('明細を集計してtb_sales_detail_voucher（伝票レベル）の更新 01. 明細数');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
    */


    // 日付の絞込は明細で。
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'P'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';

    $taxIncludedRate = $commonUtil->getTaxRate(new \DateTimeImmutable());
    $taxRate = $taxIncludedRate - 1;
    $logger->info(sprintf('tax rate: %.2f', $taxRate));
    // 消費税 受注日の価格にならないのは現在は許容（#164907）
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher AS V
      INNER JOIN (
        SELECT
            伝票番号
          , SUM(仕入原価 + 付加費用額 + 固定費用額 + 仕入先費用額) AS 仕入原価合計額
          , COUNT(*) AS 明細数
        FROM tb_sales_detail_profit AS P
        {$dateConditionStr}
        GROUP BY 伝票番号
      ) AS P ON V.伝票番号 = P.伝票番号
      SET V.仕入原価 = P.仕入原価合計額
        , V.仕入原価の消費税 = TRUNCATE(P.仕入原価合計額 * CAST(:taxRate AS DECIMAL(10, 2)), 0)
        , V.明細数 = P.明細数
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':taxRate', $taxRate, \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info('明細を集計してtb_sales_detail_voucher（伝票レベル）の更新 02. 仕入原価');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    /* 02へ移動
    // 消費税 （常に最新の消費税で計算しなおしてるのは元の実装のママ）
    // $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 仕入原価の消費税 = TRUNCATE(仕入原価 * 0.08, 0)
EOD;
    $dbMain->query($sql);
    $logger->info('明細を集計してtb_sales_detail_voucher（伝票レベル）の更新 03. 仕入原価の消費税');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));
    */


    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher
      SET 粗利額 =
               ポイント数を含む総合計
             - (配送料額 + 代引手数料額)
             - (モールシステム料額 + モール別支払方法別手数料額 + 仕入原価)
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('明細を集計してtb_sales_detail_voucher（伝票レベル）の更新 04. 伝票粗利額 計算');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //'====================
    //'購入回数を計算
    //'====================
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
    INSERT IGNORE INTO tb_sales_detail_buycount (
        伝票番号
      , 購入者名
      , 購入者電話番号
    )
    SELECT
        伝票番号
      , 購入者名
      , 購入者電話番号
    FROM tb_sales_detail_voucher
    {$dateConditionStr}
EOD;
    $stmt = $dbMain->query($sql);
    $logger->info('購入回数 tb_sales_detail_buycount 作成（追記）');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $this->processExecuteLog->setProcessNumber3($stmt->rowCount()); // 処理件数3: buycountテーブルの件数

    // 新規に追加された、回数:0 のもののみ更新であるため、
    // 更新では特に日時の絞り込みは行わない。
    $sql = <<<EOD
      UPDATE tb_sales_detail_buycount B
      INNER JOIN (
        SELECT
            B.伝票番号
          , B.購入者電話番号
          , COUNT(V.伝票番号) AS 今回含む購入回数
        FROM tb_sales_detail_buycount B
        INNER JOIN tb_sales_detail_voucher V ON B.購入者電話番号 = V.購入者電話番号
        WHERE B.購入回数 = 0
          AND V.伝票番号 <= B.伝票番号
          AND B.購入者電話番号 <> '(none)'
        GROUP BY B.伝票番号, B.購入者電話番号
      ) V ON B.伝票番号 = V.伝票番号
      SET B.購入回数 = 今回含む購入回数
EOD;
    $dbMain->query($sql);
    $logger->info('購入回数 tb_sales_detail_buycount 更新(電話番号あり)');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // (none)
    $sql = <<<EOD
      UPDATE tb_sales_detail_buycount AS B
      SET B.購入回数=1
      WHERE B.購入者電話番号='(none)';
EOD;
    $dbMain->query($sql);
    $logger->info('購入回数 tb_sales_detail_buycount 更新(電話番号なし)');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //'tb_sales_detail_voucher_order_ym
    //'====================

    // Q200_INS_受注年月_店舗毎

    // 更新受注データ範囲の月初めから、月末まで
    $monthStart = $startDate ? (new \DateTime($startDate->format('Y-m-01 00:00:00'))) : null;
    $monthEnd = $endDate ? (new \DateTime($endDate->format('Y-m-t 00:00:00'))) : null;

    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, '受注年月日', 'Y-m-d'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';

    $sql = <<<EOD
      INSERT INTO tb_sales_detail_voucher_order_ym (
          店舗コード
        , 受注年月
        , 受注年
        , 受注月
        , ポイント数を含む総合計
        , 平均購入額
        , 明細数
        , 伝票数
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 配送料額
        , 代引手数料額
        , モールシステム料率
        , モールシステム料額
        , モール別支払方法別手数料率
        , モール別支払方法別手数料額
        , 仕入原価
        , 仕入原価の消費税
        , 粗利額
        , 粗利額小計
        , 総合計小計
        , 伝票数小計
        , 粗利率
      )
      SELECT
          店舗コード
        , 受注年月
        , 受注年
        , 受注月
        , SUM(ポイント数を含む総合計) AS ポイント数を含む総合計
        , SUM(ポイント数を含む総合計) / COUNT(伝票番号) AS 平均購入額
        , SUM(明細数) AS 明細数
        , Count(伝票番号) AS 伝票番号のカウント
        , SUM(総合計) AS 総合計
        , SUM(商品計) AS 商品計
        , SUM(税金) AS 税金
        , SUM(発送代) AS 発送代
        , SUM(手数料) AS 手数料
        , SUM(他費用) AS 他費用
        , SUM(ポイント数) AS ポイント数
        , SUM(配送料額) AS 配送料額
        , SUM(代引手数料額) AS 代引手数料額
        , SUM(モールシステム料率) AS モールシステム料率
        , SUM(モールシステム料額) AS モールシステム料額
        , SUM(モール別支払方法別手数料率) AS モール別支払方法別手数料率
        , SUM(モール別支払方法別手数料額) AS モール別支払方法別手数料額
        , SUM(仕入原価) AS 仕入原価
        , SUM(仕入原価の消費税) AS 仕入原価の消費税
        , SUM(粗利額) AS 粗利額
        , SUM(粗利額) AS 粗利額小計
        , SUM(ポイント数を含む総合計) AS 総合計小計
        , COUNT(伝票番号) AS 伝票数小計
        , CASE
            WHEN SUM(ポイント数を含む総合計) = 0 THEN 0
            ELSE SUM(粗利額) * 100 / SUM(ポイント数を含む総合計)
          END AS 粗利率
      FROM
        tb_sales_detail_voucher
      {$dateConditionStr}
      GROUP BY
          店舗コード
        , 受注年月
        , 受注年
        , 受注月
      ORDER BY
          受注年
        , 受注月
EOD;
    $dbMain->query($sql);
    $logger->info('受注年月_店舗毎 tb_sales_detail_voucher_order_ym作成');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // CurrentDb.Execute "Q200_INS_受注年月_店舗計"
    // $dateConditionStr 使い回し（月単位）
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_order_ym (
          店舗コード
        , 受注年月
        , 受注年
        , 受注月
        , ポイント数を含む総合計
        , 平均購入額
        , 明細数
        , 伝票数
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 配送料額
        , 代引手数料額
        , モールシステム料率
        , モールシステム料額
        , モール別支払方法別手数料率
        , モール別支払方法別手数料額
        , 仕入原価
        , 仕入原価の消費税
        , 粗利額
        , 粗利額小計
        , 総合計小計
        , 伝票数小計
        , 粗利率
      )
      SELECT
          "999" AS 店舗コード
        , 受注年月
        , 受注年
        , 受注月
        , SUM(ポイント数を含む総合計) AS ポイント数を含む総合計
        , SUM(ポイント数を含む総合計) / COUNT(伝票番号) AS 平均購入額
        , SUM(明細数) AS 明細数
        , COUNT(伝票番号) AS 伝票番号のカウント
        , SUM(総合計) AS 総合計
        , SUM(商品計) AS 商品計
        , SUM(税金) AS 税金
        , SUM(発送代) AS 発送代
        , SUM(手数料) AS 手数料
        , SUM(他費用) AS 他費用
        , SUM(ポイント数) AS ポイント数
        , SUM(配送料額) AS 配送料額
        , SUM(代引手数料額) AS 代引手数料額
        , SUM(モールシステム料率) AS モールシステム料率
        , SUM(モールシステム料額) AS モールシステム料額
        , SUM(モール別支払方法別手数料率) AS モール別支払方法別手数料率
        , SUM(モール別支払方法別手数料額) AS モール別支払方法別手数料額
        , SUM(仕入原価) AS 仕入原価
        , SUM(仕入原価の消費税) AS 仕入原価の消費税
        , SUM(粗利額) AS 粗利額
        , SUM(粗利額) AS 粗利額小計
        , SUM(ポイント数を含む総合計) AS 総合計小計
        , COUNT(伝票番号) AS 伝票数小計
        , CASE
            WHEN SUM(ポイント数を含む総合計) = 0 THEN 0
            ELSE SUM(粗利額) * 100 / SUM(ポイント数を含む総合計)
          END AS 粗利率
      FROM
        tb_sales_detail_voucher

      {$dateConditionStr}
      GROUP BY
          "999"
        , 受注年月
        , 受注年
        , 受注月
      ORDER BY
          受注年
        , 受注月
EOD;

    $dbMain->query($sql);
    $logger->info('受注年月_店舗計 tb_sales_detail_voucher_order_ym');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // -----------------------
    // 販促費 反映 （※粗利額・粗利率の再計算）
    // 更新期間のみ
    // -----------------------
    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, 'yyyymm', 'Ym', 'spc'); // tb_sales_detail_voucher_order_ym
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE
      tb_sales_detail_voucher_order_ym sdo
      LEFT JOIN v_sales_promotion_cost_shop spc ON sdo.店舗コード = spc.店舗コード AND sdo.受注年月 = spc.yyyymm
      SET sdo.販促費     = spc.販促費
        , sdo.粗利額     = sdo.粗利額 - spc.販促費
        , sdo.粗利額小計 = sdo.粗利額小計 - spc.販促費
        , sdo.粗利率     = CASE WHEN sdo.総合計小計 = 0 THEN 0
                                ELSE (sdo.粗利額 - spc.販促費) * 100 / sdo.総合計小計
                           END
      WHERE spc.yyyymm IS NOT NULL
        AND spc.販促費 > 0
        {$dateConditionStr}
      ;
EOD;
    $dbMain->query($sql);
    $logger->info('受注年月_店舗計_販促費反映 tb_sales_detail_voucher_order_ym');
    $logger->info($dateConditionStr);
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //'====================
    //'tb_sales_detail_voucher_shipping_ym
    //'====================
    //CurrentDb.Execute "Q200_INS_出荷年月_店舗毎"
    // ※こちらは出荷年月絞りであるため、全件更新やむなし
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_shipping_ym (
          店舗コード
        , 出荷年月
        , 出荷年
        , 出荷月
        , ポイント数を含む総合計
        , 平均購入額
        , 明細数
        , 伝票数
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 配送料額
        , 代引手数料額
        , モールシステム料率
        , モールシステム料額
        , モール別支払方法別手数料率
        , モール別支払方法別手数料額
        , 仕入原価
        , 仕入原価の消費税
        , 粗利額
        , 粗利額小計
        , 総合計小計
        , 伝票数小計
        , 粗利率
      )
      SELECT
          店舗コード
        , 出荷年月
        , 出荷年
        , 出荷月
        , SUM(ポイント数を含む総合計) AS ポイント数を含む総合計
        , SUM(ポイント数を含む総合計) / COUNT(伝票番号) AS 平均購入額
        , SUM(明細数) AS 明細数
        , COUNT(伝票番号) AS 伝票番号のカウント
        , SUM(総合計) AS 総合計
        , SUM(商品計) AS 商品計
        , SUM(税金) AS 税金
        , SUM(発送代) AS 発送代
        , SUM(手数料) AS 手数料
        , SUM(他費用) AS 他費用
        , SUM(ポイント数) AS ポイント数
        , SUM(配送料額) AS 配送料額
        , SUM(代引手数料額) AS 代引手数料額
        , SUM(モールシステム料率) AS モールシステム料率
        , SUM(モールシステム料額) AS モールシステム料額
        , SUM(モール別支払方法別手数料率) AS モール別支払方法別手数料率
        , SUM(モール別支払方法別手数料額) AS モール別支払方法別手数料額
        , SUM(仕入原価) AS 仕入原価
        , SUM(仕入原価の消費税) AS 仕入原価の消費税
        , SUM(粗利額) AS 粗利額
        , SUM(粗利額) AS 粗利額小計
        , SUM(ポイント数を含む総合計) AS 総合計小計
        , COUNT(伝票番号) AS 伝票数小計
        , CASE
            WHEN SUM(ポイント数を含む総合計) = 0 THEN 0
            ELSE SUM(粗利額) * 100 / SUM(ポイント数を含む総合計)
          END AS 粗利率
      FROM
        tb_sales_detail_voucher
      GROUP BY
          店舗コード
        , 出荷年月
        , 出荷年
        , 出荷月
      ORDER BY
          出荷年
        , 出荷月
EOD;

    $dbMain->query($sql);
    $logger->info('出荷年月_店舗毎 tb_sales_detail_voucher_shipping_ym');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //CurrentDb.Execute "Q200_INS_出荷年月_店舗計"
    // ※こちらは出荷年月絞りであるため、全件更新やむなし
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_shipping_ym (
          店舗コード
        , 出荷年月
        , 出荷年
        , 出荷月
        , ポイント数を含む総合計
        , 平均購入額
        , 明細数
        , 伝票数
        , 総合計
        , 商品計
        , 税金
        , 発送代
        , 手数料
        , 他費用
        , ポイント数
        , 配送料額
        , 代引手数料額
        , モールシステム料率
        , モールシステム料額
        , モール別支払方法別手数料率
        , モール別支払方法別手数料額
        , 仕入原価
        , 仕入原価の消費税
        , 粗利額
        , 粗利額小計
        , 総合計小計
        , 伝票数小計
        , 粗利率
      )
      SELECT
          "999" AS 店舗コード
        , 出荷年月
        , 出荷年
        , 出荷月
        , SUM(ポイント数を含む総合計) AS ポイント数を含む総合計
        , SUM(ポイント数を含む総合計) / COUNT(伝票番号) AS 平均購入額
        , SUM(明細数) AS 明細数
        , COUNT(伝票番号) AS 伝票番号のカウント
        , SUM(総合計) AS 総合計
        , SUM(商品計) AS 商品計
        , SUM(税金) AS 税金
        , SUM(発送代) AS 発送代
        , SUM(手数料) AS 手数料
        , SUM(他費用) AS 他費用
        , SUM(ポイント数) AS ポイント数
        , SUM(配送料額) AS 配送料額
        , SUM(代引手数料額) AS 代引手数料額
        , SUM(モールシステム料率) AS モールシステム料率
        , SUM(モールシステム料額) AS モールシステム料額
        , SUM(モール別支払方法別手数料率) AS モール別支払方法別手数料率
        , SUM(モール別支払方法別手数料額) AS モール別支払方法別手数料額
        , SUM(仕入原価) AS 仕入原価
        , SUM(仕入原価の消費税) AS 仕入原価の消費税
        , SUM(粗利額) AS 粗利額
        , SUM(粗利額) AS 粗利額小計
        , SUM(ポイント数を含む総合計) AS 総合計小計
        , COUNT(伝票番号) AS 伝票数小計
        , CASE
            WHEN SUM(ポイント数を含む総合計) = 0 THEN 0
            ELSE SUM(粗利額) * 100 / SUM(ポイント数を含む総合計)
          END AS 粗利率
      FROM
        tb_sales_detail_voucher
      GROUP BY
          "999"
        , 出荷年月
        , 出荷年
        , 出荷月
      ORDER BY
          出荷年
        , 出荷月;

EOD;

    $dbMain->query($sql);
    $logger->info('出荷年月_店舗計 tb_sales_detail_voucher_shipping_ym');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // -----------------------
    // 販促費 反映 （※粗利額・粗利率の再計算）
    // -----------------------
    $sql = <<<EOD
      UPDATE
      tb_sales_detail_voucher_shipping_ym sds
      LEFT JOIN v_sales_promotion_cost_shop spc ON sds.店舗コード = spc.店舗コード AND sds.出荷年月 = spc.yyyymm
      SET sds.販促費     = spc.販促費
        , sds.粗利額     = sds.粗利額 - spc.販促費
        , sds.粗利額小計 = sds.粗利額小計 - spc.販促費
        , sds.粗利率     = CASE WHEN sds.総合計小計 = 0 THEN 0
                                ELSE (sds.粗利額 - spc.販促費) * 100 / sds.総合計小計
                           END
      WHERE spc.yyyymm IS NOT NULL
        AND spc.販促費 > 0
      ;
EOD;
    $dbMain->query($sql);
    $logger->info('出荷年月_店舗計_販促費反映 tb_sales_detail_voucher_shipping_ym');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));



    //'====================
    //'tb_sales_detail_voucher_order_ym_repeater
    //'====================
    //CurrentDb.Execute "Q200_INS_受注年月_店舗毎_購入回数毎"
    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, '受注年月日', 'Y-m-d', 'V'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';

    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_order_ym_repeater (
          店舗コード
        , 受注年月
        , 購入回数
        , 受注年
        , 受注月
        , ポイント数を含む総合計
        , 明細数
        , 伝票数
      )
      SELECT
        店舗コード
        , 受注年月
        , CASE
            WHEN B.購入回数 > 4 THEN 4
            ELSE B.購入回数
          END AS 購入回数
        , 受注年
        , 受注月
        , Sum(ポイント数を含む総合計) AS ポイント数を含む総合計
        , Sum(明細数) AS 明細数
        , Count(V.伝票番号) AS 伝票番号のカウント
      FROM tb_sales_detail_voucher V
      INNER JOIN tb_sales_detail_buycount B
          ON V.伝票番号 = B.伝票番号
      {$dateConditionStr}
      GROUP BY
          店舗コード
        , 受注年月
        , CASE
            WHEN B.購入回数 > 4 THEN 4
            ELSE B.購入回数
          END
        , 受注年
        , 受注月
      ORDER BY
          受注年
        , 受注月
EOD;
    $dbMain->query($sql);
    $logger->info('受注年月_店舗毎_購入回数毎 tb_sales_detail_voucher_order_ym_repeater');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //CurrentDb.Execute "Q200_INS_受注年月_店舗計_購入回数毎"
    // $dateConditionStr 使い回し（月単位）
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_order_ym_repeater (
          店舗コード
        , 受注年月
        , 購入回数
        , 受注年
        , 受注月
        , ポイント数を含む総合計
        , 明細数
        , 伝票数
      )
      SELECT
          "999" AS 店舗コード
        , 受注年月
        , CASE
            WHEN B.購入回数 > 4 THEN 4
            ELSE B.購入回数
          END AS 購入回数
        , 受注年
        , 受注月
        , Sum(ポイント数を含む総合計) AS ポイント数を含む総合計
        , Sum(明細数) AS 明細数
        , Count(V.伝票番号) AS 伝票番号のカウント
      FROM tb_sales_detail_voucher V
      INNER JOIN tb_sales_detail_buycount B ON V.伝票番号 = B.伝票番号
      {$dateConditionStr}
      GROUP BY
        "999"
        , 受注年月
        , CASE
            WHEN B.購入回数 > 4 THEN 4
            ELSE B.購入回数
          END
        , 受注年
        , 受注月
      ORDER BY
          受注年
        , 受注月
EOD;
    $dbMain->query($sql);
    $logger->info('受注年月_店舗計_購入回数毎 tb_sales_detail_voucher_order_ym_repeater');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //'tb_sales_detail_voucher_ym_shop"
    //'====================
    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, '受注年月', 'Ym'); // tb_sales_detail_voucher_order_ym
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';

    //CurrentDb.Execute "Q200_INS_sales_detail_voucher_ym_shop"
    // ここではINSERTのみ。SalesAnalyzer でフォームを開いた時に一括更新される（フォーム表示用の枠としてのテーブル）
    $sql = <<<EOD
      INSERT INTO tb_sales_detail_voucher_ym_shop (yyyymm)
      SELECT
        受注年月
      FROM tb_sales_detail_voucher_order_ym
      {$dateConditionStr}
      GROUP BY 受注年月
      ORDER BY 受注年月 DESC
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher_ym_shop');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //'tb_sales_detail_voucher_ym_shop_repeater"
    //'====================
    //CurrentDb.Execute "Q200_INS_sales_detail_voucher_ym_shop_repeater"
    // $dateConditionStr 使い回し（月単位）

    // ここではINSERTのみ。SalesAnalyzer でフォームを開いた時に一括更新される（フォーム表示用の枠としてのテーブル）
    $sql = <<<EOD
      INSERT INTO tb_sales_detail_voucher_ym_shop_repeater(yyyymm)
      SELECT
        受注年月
      FROM tb_sales_detail_voucher_order_ym_repeater
      {$dateConditionStr}
      GROUP BY 受注年月
      ORDER BY 受注年月 DESC;
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher_ym_shop_repeater');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));





    //'====================
    //'tb_sales_detail_voucher_item_order_ym
    // おそらくAccess 商品売上推移用の集計。セット商品対応でWebシステムに移行した後は不要の可能性がある
    //'====================
    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, '受注年月日', 'Y-m-d'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_item_order_ym (
          daihyo_syohin_code
        , 受注年月
        , 受注年
        , 受注月
        , 受注数
        , 明細数
        , 明細粗利額
        , 明細金額
        , 明細粗利率
        , 明細売上合計
        , 伝票料金加算額
        , 伝票費用減算額
      )
      SELECT
          P.代表商品コード
        , CONCAT(P.受注年, LPAD(P.受注月, 2, '0')) AS 受注年月
        , P.受注年
        , P.受注月
        , SUM(P.受注数) AS 受注数
        , COUNT(P.代表商品コード) AS 明細数
        , SUM(P.明細粗利額_伝票費用除外) AS 明細粗利額の合計
        , SUM(IFNULL(seti.distribution_subtotal	, P.小計)) AS 小計の合計
        , CASE
            WHEN SUM(P.小計_伝票料金加算) > 0 THEN SUM(P.明細粗利額_伝票費用除外) * 100 / SUM(P.小計_伝票料金加算)
            ELSE 0
          END AS 明細粗利率
        , SUM(P.小計_伝票料金加算) AS 明細売上合計
        , SUM(P.伝票料金加算額) AS 伝票料金加算額
        , SUM(P.伝票費用減算額) AS 伝票費用減算額
      FROM tb_sales_detail_profit AS P
      LEFT JOIN tb_sales_detail_set_distribute_info seti ON P.伝票番号 = seti.voucher_number AND P.明細行 = seti.line_number
      WHERE P.代表商品コード <> ''
        AND P.代表商品コード IS NOT NULL
        AND P.キャンセル区分 = '0'
        AND P.明細行キャンセル = '0'
        {$dateConditionStr}
      GROUP BY
          P.代表商品コード
        , P.受注年
        , P.受注月

EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher_item_order_ym INSERT & 明細粗利率');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 更新（親伝票件数・金額等の補完）
    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, '受注年月日', 'Y-m-d', 'P'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_item_order_ym AS ITEM
        INNER JOIN (
          SELECT
              P.代表商品コード
            , CONCAT(P.受注年, LPAD(P.受注月, 2, '0')) AS 受注年月
            , P.受注年
            , P.受注月
            , SUM(V.粗利額) AS 伝票粗利額
            , SUM(V.ポイント数を含む総合計) AS 伝票金額
            , COUNT(DISTINCT V.伝票番号) AS 伝票数
          FROM tb_sales_detail_profit AS P
          INNER JOIN tb_sales_detail_voucher AS V ON P.伝票番号 = V.伝票番号
          WHERE P.代表商品コード <> ''
            AND P.代表商品コード IS NOT NULL
            AND P.キャンセル区分 = '0'
            AND P.明細行キャンセル = '0'
            {$dateConditionStr}
          GROUP BY
              P.代表商品コード
            , P.受注年
            , P.受注月
        ) AS SUM伝票金額
          ON ITEM.daihyo_syohin_code = SUM伝票金額.代表商品コード
          AND ITEM.受注年月 = SUM伝票金額.受注年月
      SET
          ITEM.伝票金額   = SUM伝票金額.伝票金額
        , ITEM.伝票粗利額 = SUM伝票金額.伝票粗利額
        , ITEM.伝票数     = SUM伝票金額.伝票数
        , ITEM.伝票粗利率 = CASE
                             WHEN SUM伝票金額.伝票金額 > 0 THEN
                                 SUM伝票金額.伝票粗利額 * 100 / SUM伝票金額.伝票金額
                             ELSE 0
                           END
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher_item_order_ym UPDATE 01. 伝票金額, 伝票粗利額, 伝票数, 伝票粗利率');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    // 伝票粗利率 ↑ に統合
    //mysql = "Update tb_sales_detail_voucher_item_order_ym" & _
    //        " Set 伝票粗利率 = 伝票粗利額 * 100 / 伝票金額" & _
    //        " where 伝票金額>0;"

    // 明細粗利率 ↑ INSERT に統合
    //mysql = "Update tb_sales_detail_voucher_item_order_ym" & _
    //        " Set 明細粗利率 = 明細粗利額 * 100 / 明細金額" & _
    //        " where 明細金額>0;"

    //'====================
    //'tb_sales_detail_summary_item_ym
    // セット商品対応版 商品売上推移用の集計。
    //'====================
    $dateConditions = $this->buildDateConditions($monthStart, $monthEnd, '受注年月日', 'Y-m-d'); // tb_sales_detail_profit
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      INSERT INTO tb_sales_detail_summary_item_ym (
        daihyo_syohin_code,
        order_ym,
        voucher_quantity,
        order_quantity,
        detail_amount_including_cost,
        detail_amount,
        detail_gross_profit,
        additional_amount,
        subtraction_amount
      )
      SELECT
        daihyo_syohin_code,
        order_ym,
        SUM(voucher_quantity),
        SUM(order_quantity),
        SUM(detail_amount_including_cost),
        SUM(detail_amount),
        SUM(detail_gross_profit),
        SUM(additional_amount),
        SUM(subtraction_amount)
      FROM (
        SELECT
          p.代表商品コード AS daihyo_syohin_code,
          CONCAT(p.受注年, LPAD(p.受注月, 2, '0')) AS order_ym,
          COUNT(DISTINCT p.伝票番号) AS voucher_quantity,
          SUM(p.受注数) AS order_quantity,
          SUM(p.小計_伝票料金加算) AS detail_amount_including_cost,
          SUM(p.小計) AS detail_amount,
          SUM(p.明細粗利額_伝票費用除外) AS detail_gross_profit,
          SUM(p.伝票料金加算額) AS additional_amount,
          SUM(p.伝票費用減算額) AS subtraction_amount
        FROM tb_sales_detail_profit AS p
        LEFT JOIN tb_sales_detail_set_distribute_info s
          ON p.伝票番号 = s.voucher_number AND p.明細行 = s.line_number
        WHERE s.voucher_number IS NULL
          AND p.代表商品コード <> ''
          AND p.代表商品コード IS NOT NULL
          AND p.キャンセル区分 = '0'
          AND p.明細行キャンセル = '0'
          {$dateConditionStr}
        GROUP BY
          p.代表商品コード, p.受注年, p.受注月
        UNION ALL
        SELECT
          pci.daihyo_syohin_code AS daihyo_syohin_code,
          CONCAT(p.受注年, LPAD(p.受注月, 2, '0')) AS order_ym,
          COUNT(DISTINCT p.伝票番号) AS voucher_quantity,
          s.original_quantity AS order_quantity,
          SUM(p.小計_伝票料金加算) AS detail_amount_including_cost,
          SUM(p.小計) AS detail_amount,
          SUM(p.明細粗利額_伝票費用除外) AS detail_gross_profit,
          SUM(p.伝票料金加算額) AS additional_amount,
          SUM(p.伝票費用減算額) AS subtraction_amount
        FROM tb_sales_detail_profit AS p
        INNER JOIN tb_sales_detail_set_distribute_info s
          ON p.伝票番号 = s.voucher_number AND p.明細行 = s.line_number
        INNER JOIN tb_productchoiceitems pci
          ON s.original_ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        WHERE p.代表商品コード <> ''
          AND p.代表商品コード IS NOT NULL
          AND p.キャンセル区分 = '0'
          AND p.明細行キャンセル = '0'
          {$dateConditionStr}
        GROUP BY
          s.voucher_number, s.original_ne_syohin_syohin_code
      ) tmp
      GROUP BY
        daihyo_syohin_code, order_ym
EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_summary_item_ym INSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //'====================
    //'tb_sales_detail_voucher_item_shipping_ym
    //'====================
    // 出荷年月絞込のため全件更新
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_item_shipping_ym (
          daihyo_syohin_code
        , 出荷年月
        , 出荷年
        , 出荷月
        , 受注数
        , 明細数
        , 明細粗利額
        , 明細金額
        , 明細粗利率
      )
      SELECT
          P.代表商品コード
        , CONCAT(P.出荷年, LPAD(P.出荷月, 2, '0')) AS 出荷年月
        , P.出荷年
        , P.出荷月
        , SUM(P.受注数) AS 受注数
        , COUNT(P.代表商品コード) AS 明細数
        , SUM(P.明細粗利額) AS 明細粗利額の合計
        , SUM(IFNULL(seti.distribution_subtotal, P.小計)) AS 小計の合計
        , CASE
            WHEN SUM(IFNULL(seti.distribution_subtotal, P.小計)) > 0 THEN SUM(P.明細粗利額) * 100 / SUM(IFNULL(seti.distribution_subtotal, P.小計))
            ELSE 0
          END AS 明細粗利率
      FROM tb_sales_detail_profit AS P
      LEFT JOIN tb_sales_detail_set_distribute_info seti ON P.伝票番号 = seti.voucher_number AND P.明細行 = seti.line_number
      WHERE P.代表商品コード <> ''
        AND P.代表商品コード IS NOT NULL
        AND P.キャンセル区分 = '0'
        AND P.明細行キャンセル = '0'
      GROUP BY
          P.代表商品コード
        , P.出荷年
        , P.出荷月

EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher_item_shipping_ym INSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 出荷年月絞込のため全件更新
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_item_shipping_ym AS ITEM
      INNER JOIN (
        SELECT
            P.代表商品コード
          , CONCAT(P.出荷年, LPAD(P.出荷月, 2, '0')) AS 出荷年月
          , P.出荷年
          , P.出荷月
          , SUM(V.粗利額) AS 伝票粗利額
          , SUM(V.ポイント数を含む総合計) AS 伝票金額
          , COUNT(V.伝票番号) AS 伝票数
        FROM tb_sales_detail_profit AS P
        INNER JOIN tb_sales_detail_voucher AS V ON P.伝票番号 = V.伝票番号

        WHERE P.代表商品コード <> ''
          AND P.代表商品コード IS NOT NULL
          AND P.キャンセル区分 = '0'
          AND P.明細行キャンセル = '0'
        GROUP BY
            P.代表商品コード
          , P.出荷年
          , P.出荷月
      ) AS SUM伝票金額
        ON ITEM.daihyo_syohin_code = SUM伝票金額.代表商品コード
        AND ITEM.出荷年月 = SUM伝票金額.出荷年月
    SET
        ITEM.伝票金額   = SUM伝票金額.伝票金額
      , ITEM.伝票粗利額 = SUM伝票金額.伝票粗利額
      , ITEM.伝票数     = SUM伝票金額.伝票数
      , ITEM.伝票粗利率 = CASE
                            WHEN SUM伝票金額.伝票金額 > 0 THEN SUM伝票金額.伝票粗利額 * 100 / SUM伝票金額.伝票金額
                            ELSE 0
                         END


EOD;
    $dbMain->query($sql);
    $logger->info('tb_sales_detail_voucher_item_shipping_ym UPDATE');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    // 伝票粗利率 ↑へ統合
    //mysql = "Update tb_sales_detail_voucher_item_shipping_ym" & _
    //        " Set 伝票粗利率 = 伝票粗利額 * 100 / 伝票金額" & _
    //        " where 伝票金額>0;"

    // 明細粗利率 ↑ INSERTへ統合
    //mysql = "Update tb_sales_detail_voucher_item_shipping_ym" & _
    //        " Set 明細粗利率 = 明細粗利額 * 100 / 明細金額" & _
    //        " where 明細金額>0;"


    // -----------------------------
    // tb_sales_detail_voucher_repeater
    // -----------------------------
    //'リピーター分析(全店舗)
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'V'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_repeater (
          受注年月日
        , 受注年月
        , 受注年
        , 受注月
        , 伝票数
        , ポイント数を含む総合計
      )
      SELECT
          V.受注年月日
        , V.受注年月
        , V.受注年
        , V.受注月
        , COUNT(V.伝票番号)
        , SUM(V.ポイント数を含む総合計)
      FROM tb_sales_detail_voucher AS V
      {$dateConditionStr}
      GROUP BY V.受注年月日
EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(全店舗) tb_sales_detail_voucher_repeater INSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'V'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    // UPDATE 01.  ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater AS R
        INNER JOIN (
          SELECT
              V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 = 1
          {$dateConditionStr}
          GROUP BY V.受注年月日
        ) AS S ON R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入１回 = S.cnt
        , R.ポイント数を含む総合計_購入１回 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(全店舗) tb_sales_detail_voucher_repeater UPDATE 01. 購入1回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 02.  ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater AS R
        INNER JOIN (
          SELECT
              V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 = 2
          {$dateConditionStr}
          GROUP BY V.受注年月日
        ) AS S ON R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入２回 = S.cnt
        , R.ポイント数を含む総合計_購入２回 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(全店舗) tb_sales_detail_voucher_repeater UPDATE 02. 購入2回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 03.  ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater AS R
        INNER JOIN (
          SELECT
              V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 = 3
          {$dateConditionStr}
          GROUP BY V.受注年月日
        ) AS S ON R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入３回 = S.cnt
        , R.ポイント数を含む総合計_購入３回 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(全店舗) tb_sales_detail_voucher_repeater UPDATE 03. 購入3回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 04.  ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater AS R
        INNER JOIN (
          SELECT
              V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 >= 4
          {$dateConditionStr}
          GROUP BY V.受注年月日
        ) AS S ON R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入４回以上 = S.cnt
        , R.ポイント数を含む総合計_購入４回以上 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(全店舗) tb_sales_detail_voucher_repeater UPDATE 04. 購入4回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    //mysql = "update tb_sales_detail_voucher_repeater set" & _
    //        " 購入割合_購入１回=ポイント数を含む総合計_購入１回/ポイント数を含む総合計" & _
    //        ",購入割合_購入２回=ポイント数を含む総合計_購入２回/ポイント数を含む総合計" & _
    //        ",購入割合_購入３回=ポイント数を含む総合計_購入３回/ポイント数を含む総合計" & _
    //        ",購入割合_購入４回以上=ポイント数を含む総合計_購入４回以上/ポイント数を含む総合計" & _
    //        " where ポイント数を含む総合計<>0;"
    //
    //CN.Execute mysql
    //DoEvents

    // UPDATE 05.
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d'); // tb_sales_detail_voucher_repeater
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater
      SET
          購入割合_購入１回 = ポイント数を含む総合計_購入１回 / ポイント数を含む総合計
        , 購入割合_購入２回 = ポイント数を含む総合計_購入２回 / ポイント数を含む総合計
        , 購入割合_購入３回 = ポイント数を含む総合計_購入３回 / ポイント数を含む総合計
        , 購入割合_購入４回以上 = ポイント数を含む総合計_購入４回以上 / ポイント数を含む総合計
      WHERE ポイント数を含む総合計 <> 0
      {$dateConditionStr}

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(全店舗) tb_sales_detail_voucher_repeater UPDATE 05. 購入割合更新');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    // tb_sales_detail_voucher_repeater_shop
    //'リピーター分析(店舗指定)
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'V'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" WHERE " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_repeater_shop (
          店舗コード
        , 受注年月日
        , 受注年月
        , 受注年
        , 受注月
        , 伝票数
        , ポイント数を含む総合計
      )
      SELECT
          V.店舗コード
        , V.受注年月日
        , V.受注年月
        , V.受注年
        , V.受注月
        , COUNT(V.伝票番号)
        , SUM(V.ポイント数を含む総合計)
      FROM tb_sales_detail_voucher AS V
      {$dateConditionStr}
      GROUP BY
          V.店舗コード
        , V.受注年月日

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(店舗指定) tb_sales_detail_voucher_repeater_shop INSERT');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d', 'V'); // tb_sales_detail_voucher
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    // UPDATE 01.
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater_shop AS R
        INNER JOIN (
          SELECT
              V.店舗コード
            , V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 = 1
          {$dateConditionStr}
          GROUP BY
              V.店舗コード
            , V.受注年月日
        ) AS S
          ON R.店舗コード = S.店舗コード
          AND R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入１回 = S.cnt
        , R.ポイント数を含む総合計_購入１回 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(店舗指定) tb_sales_detail_voucher_repeater_shop UPDATE 01. 購入1回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 02. ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater_shop AS R
        INNER JOIN (
          SELECT
              V.店舗コード
            , V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 = 2
          {$dateConditionStr}
          GROUP BY
              V.店舗コード
            , V.受注年月日
        ) AS S
          ON R.店舗コード = S.店舗コード
          AND R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入２回 = S.cnt
        , R.ポイント数を含む総合計_購入２回 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(店舗指定) tb_sales_detail_voucher_repeater_shop UPDATE 02. 購入2回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 03. ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater_shop AS R
        INNER JOIN (
          SELECT
              V.店舗コード
            , V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 = 3
          {$dateConditionStr}
          GROUP BY
              V.店舗コード
            , V.受注年月日
        ) AS S
          ON R.店舗コード = S.店舗コード
          AND R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入３回 = S.cnt
        , R.ポイント数を含む総合計_購入３回 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(店舗指定) tb_sales_detail_voucher_repeater_shop UPDATE 03. 購入3回');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 04. ※ $dateConditionStr 使い回し
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater_shop AS R
        INNER JOIN (
          SELECT
              V.店舗コード
            , V.受注年月日
            , SUM(V.ポイント数を含む総合計) AS amount
            , COUNT(V.伝票番号) AS cnt
          FROM tb_sales_detail_voucher AS V
          INNER JOIN tb_sales_detail_buycount AS B ON V.伝票番号 = B.伝票番号
          WHERE B.購入回数 >= 4
          {$dateConditionStr}
          GROUP BY
              V.店舗コード
            , V.受注年月日
        ) AS S
          ON R.店舗コード = S.店舗コード
          AND R.受注年月日 = S.受注年月日
      SET
          R.伝票数_購入４回以上 = S.cnt
        , R.ポイント数を含む総合計_購入４回以上 = S.amount

EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(店舗指定) tb_sales_detail_voucher_repeater_shop UPDATE 04. 購入4回以上');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // UPDATE 05.
    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d'); // tb_sales_detail_voucher_repeater_shop
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      UPDATE tb_sales_detail_voucher_repeater_shop
      SET
          購入割合_購入１回 = ポイント数を含む総合計_購入１回 / ポイント数を含む総合計
        , 購入割合_購入２回 = ポイント数を含む総合計_購入２回 / ポイント数を含む総合計
        , 購入割合_購入３回 = ポイント数を含む総合計_購入３回 / ポイント数を含む総合計
        , 購入割合_購入４回以上 = ポイント数を含む総合計_購入４回以上 / ポイント数を含む総合計
      WHERE ポイント数を含む総合計 <> 0
      {$dateConditionStr}
EOD;
    $dbMain->query($sql);
    $logger->info('リピーター分析(店舗指定) tb_sales_detail_voucher_repeater_shop UPDATE 05. 購入割合 更新');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $this->insertSalesDetailSummaryYmd($startDate, $endDate);
  }

  /**
   * 日別受注集計追加
   * @param \DateTime $startDate
   * @param \DateTime $endDate
   */
  private function insertSalesDetailSummaryYmd($startDate, $endDate)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    $dateConditions = $this->buildDateConditions($startDate, $endDate, '受注年月日', 'Y-m-d');
    $dateConditionStr = $dateConditions ? (" AND " . implode(" AND ", $dateConditions)) : '';
    $sql = <<<EOD
      INSERT INTO
        tb_sales_detail_summary_ymd(order_date, total_sales, total_gross_profit)
      SELECT
        `受注年月日`,
        SUM(`小計_伝票料金加算`),
        SUM(`明細粗利額_伝票費用除外`)
      FROM
        tb_sales_detail_profit
      WHERE
        1
        {$dateConditionStr}
      GROUP BY
        `受注年月日`
      ORDER BY
        `受注年月日`;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  /**
   * 日付条件
   * @param \DateTime $startDate
   * @param \DateTime $endDate
   * @param string $fieldName
   * @param string $dateFormat
   * @param string $prefix
   * @return array WHERE句配列(AND)
   */
  private function buildDateConditions($startDate, $endDate, $fieldName, $dateFormat = 'Y-m-d', $prefix = '')
  {
    $result = [];

    if (!$startDate && !$endDate) {
      return $result;
    }

    $prefix = $prefix ? sprintf('`%s`.', $prefix) : '';
    if ($startDate) {
      $result[] = sprintf(" %s`%s` >= '%s' ", $prefix, $fieldName, $startDate->format($dateFormat));
    }
    if ($endDate) {
      $result[] = sprintf(" %s`%s` <= '%s' ", $prefix, $fieldName, $endDate->format($dateFormat));
    }

    return $result;
  }


  /**
   * 除外店舗条件 作成
   * Access 実装では、 T_伝票毎利益_除外店舗 テーブルを参照している TODO 同期 or 統一
   * @return string
   */
  private function buildExceptShop()
  {
//    Public Function buildExceptShop() As String
//
//    Dim lc_where As String
//    Dim lc_rs As DAO.Recordset
//    Dim cnt As Long
//
//    lc_where = "(店舗コード not in ("
//
//    Set lc_rs = CurrentDb.OpenRecordset("T_伝票毎利益_除外店舗")
//    Do Until lc_rs.EOF
//        cnt = cnt + 1
//        If (cnt > 1) Then
//            lc_where = lc_where & ","
//        End If
//  lc_where = lc_where & lc_rs![店舗コード]
//        lc_rs.MoveNext
//    Loop
//
//    lc_where = lc_where & "))"
//
//    buildExceptShop = lc_where
//
//End Function

    $commonUtil = $this->getDbCommonUtil();
    $shoppingMallShopList = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);

    // tb_shopping_mall.ne_mall_id を参照
    $exceptShops = [
        4 // 'Plus Nao フリーオーダー'
      , 6 // 'I AM 1号店'
      , 11 // 'Yours 1号店'
      , 19 // 引き当て用
      , 25 // 楽天ロジ
      , $shoppingMallShopList->getNeMallId() // SHOPLIST は除外。（必要な時に、tb_shoplist_daily_sales から補完する）
    ];

    $shops = [];
    foreach($exceptShops as $shop) {
      $shops[] = sprintf("'%s'", $shop); // tb_sales_detail_analyze.店舗コード は Varchar
    }

    $where = " `店舗コード` NOT IN (" . implode(', ', $shops) . ") ";
    return $where;
  }

}
