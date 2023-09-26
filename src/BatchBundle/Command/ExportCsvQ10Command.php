<?php
/**
 * バッチ処理 Q10 CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\Q10MallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvQ10Command extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  private $exportPath;

  private $skipCommonProcess = false;

  private $exportAll = false;

  /** @var \DateTime */
  private $processStart; // 処理開始日時。処理完了後、前回処理日時として保存する。

  /** @var bool  */
  private $doUploadCsv = true;

  private $results = [];

  const EXPORT_PATH = 'Q10/Export';

  const UPLOAD_EXEC_TITLE = 'Q10 CSV出力処理';

  // アップロードファイルの分割行数
  const UPLOAD_CSV_MAX_NUM = 4000; // 4000行で分割


  protected function configure()
  {
    $this
      ->setName('batch:export-csv-q10')
      ->setDescription('Q10 CSV出力処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('export-dir', InputArgument::OPTIONAL, '出力先ディレクトリ', null)
      ->addOption('do-upload-csv', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ(CSV)', 1)
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理をスキップ', '0')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Q10 CSV出力処理を開始しました。');

    $this->processStart = new \DateTime();
    $this->doUploadCsv = (bool)$input->getOption('do-upload-csv');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $this->results = [
          'message' => null
        , 'item.csv' => []
      ];

      $logExecTitle = sprintf('Q10 CSV出力処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 出力パス
      $this->exportPath = $input->getArgument('export-dir');
      if (!$this->exportPath) {
        $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $this->processStart->format('YmdHis');
      }
      // 出力ディレクトリ 作成
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $fs->mkdir($this->exportPath, 0755);
      }

      // 共通処理スキップフラグ
      $this->skipCommonProcess = boolval($input->getOption('skip-common-process'));

      if (!$this->skipCommonProcess) {
        $commonUtil = $this->getDbCommonUtil();
        $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_Q10);
        /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }

      // CSV出力準備処理
      $this->prepare();

      // CSV出力
      $this->export();

      // アップロード（キュー追加）
      if ($this->doUploadCsv) {
        $this->upload($this->results['item.csv']);
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Q10 CSV出力処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Q10 CSV出力処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Q10 CSV出力処理 エラー', 'Q10 CSV出力処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Q10 CSV出力処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }

  /**
   * CSVファイル出力準備処理
   */
  private function prepare()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '商品データの準備';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $temporaryWord = " TEMPORARY ";
    $temporaryWord = " "; // FOR DEBUG

    //'====================
    //'商品コード
    //'====================
    $discardDays = intval($commonUtil->getSettingValue('Q10_DISCARD_DAY'));

    // ' 0. 不正レコードの削除
    // ブラック・未審査は強制割り当て解除
    $sql = <<<EOD
     UPDATE tb_qten_itemcode AS item
     INNER JOIN tb_mainproducts_cal cal ON item.daihyo_syohin_code = cal.daihyo_syohin_code  
     SET item.daihyo_syohin_code = ''  
     WHERE cal.adult_check_status NOT IN ( :adultCheckStatusWhite, :adultCheckStatusGray )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->execute();

    // ' -- 不正レコードの削除(itemcode)
    $sql = <<<EOD
     UPDATE tb_qten_itemcode AS item
     LEFT JOIN  tb_qten_information AS i ON item.daihyo_syohin_code = i.daihyo_syohin_code
                                        AND item.q10_itemcode = i.q10_itemcode
     SET item.daihyo_syohin_code = ''
     WHERE i.daihyo_syohin_code IS NULL
EOD;
    $dbMain->exec($sql);

    // ' -- 不正レコードの削除(information)
    $sql = <<<EOD
      UPDATE tb_qten_information AS i  
      LEFT JOIN tb_qten_itemcode AS item ON i.daihyo_syohin_code = item.daihyo_syohin_code 
                                        AND i.q10_itemcode = item.q10_itemcode 
      SET i.q10_itemcode = ''  
      WHERE item.q10_itemcode IS NULL 
EOD;
    $dbMain->exec($sql);

    $logger->info('無効なQ10商品コードの割り当てを解除中');

    // ' 1. 出力対象を決定
    // ' 既存出品商品 (tb_qten_information.q10_itemcode <> '')
    // ' 未出品商品（即納・一部即納のみ）
    // ' registration_flg <> 0
    // ' アダルトチェック: ホワイト or グレー
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_qten_csv_products;");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_qten_csv_products ( 
          daihyo_syohin_code VARCHAR(30) NOT NULL DEFAULT '' PRIMARY KEY  
        , q10_itemcode VARCHAR(30) NOT NULL DEFAULT '' 
        , deliverycode TINYINT NOT NULL  
        , endofavailability DATETIME  
        , last_orderdate DATE  
        , delete_flg TINYINT NOT NULL DEFAULT 0  
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->exec($sql);

    $sql = <<<EOD
      INSERT INTO tmp_qten_csv_products ( 
          daihyo_syohin_code 
        , q10_itemcode 
        , deliverycode 
        , endofavailability 
        , last_orderdate 
        , delete_flg 
      ) 
      SELECT  
         m.daihyo_syohin_code  
       , iq.q10_itemcode 
       , cal.deliverycode_pre 
       , cal.endofavailability 
       , cal.last_orderdate 
       , 0 
      FROM tb_mainproducts m  
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code 
      INNER JOIN tb_qten_information iq  ON m.daihyo_syohin_code = iq.daihyo_syohin_code  
      WHERE iq.registration_flg <> 0  
       AND (cal.adult_check_status IN ( :adultCheckStatusWhite, :adultCheckStatusGray ))
           AND NOT (  
                   cal.endofavailability IS NOT NULL  
               AND cal.endofavailability <= DATE_ADD(CURRENT_DATE, INTERVAL - :discardDays DAY) 
           ) 
           AND cal.deliverycode_pre <> :deliveryCodeTemporary  
           AND ( 
                iq.q10_itemcode <> '' 
             OR cal.deliverycode_pre IN ( :deliveryCodeReady, :deliveryCodeReadyPartially ) 
           ) 
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':discardDays', $discardDays, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);

    $stmt->execute();

    // ' 2. 件数確認
    // 新規
    $sql = <<<EOD
     SELECT COUNT(*) AS CNT 
     FROM tmp_qten_csv_products 
     WHERE q10_itemcode = '' 
EOD;
    $infoNum = $dbMain->query($sql)->fetchColumn(0);

    // 空きコード数
    $sql = <<<EOD
     SELECT COUNT(*) AS CNT 
     FROM tb_qten_itemcode 
     WHERE daihyo_syohin_code = '' 
EOD;
    $codeNum = $dbMain->query($sql)->fetchColumn(0);

    $diffNum = $infoNum - $codeNum;
    $logger->info(sprintf('Q10: 新規 %d 件 / 空きコード数 %d 件 / 解除件数 %d 件', $infoNum, $codeNum, $diffNum));


    // ' 3. itemcodeの足りない件数分、既存出品商品から、販売終了、受注発注商品を出力対象外に変更
    // ' 販売終了商品
    // ' 受注発注商品 （最終仕入れ日時 昇順）

    //' コードが足りない場合、割り当て解除
    if ($diffNum > 0) {

      // ' 足りない件数分、割当解除フラグをセット
      $sql = <<<EOD
        UPDATE tmp_qten_csv_products tmp 
        SET tmp.delete_flg = -1 
        WHERE tmp.q10_itemcode <> '' 
          AND (
               tmp.endofavailability IS NOT NULL 
            OR tmp.deliverycode NOT IN ( :deliveryCodeReady, :deliveryCodeReadyPartially )
          )  
        ORDER BY CASE WHEN tmp.endofavailability IS NOT NULL 
                  THEN tmp.endofavailability 
                  ELSE NOW() 
                END ASC
              , CASE WHEN tmp.last_orderdate IS NULL 
                   THEN '0000-00-00'
                   ELSE tmp.last_orderdate 
                END
        LIMIT :diffNum
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
      $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
      $stmt->bindValue(':diffNum', $diffNum, \PDO::PARAM_INT);
      $stmt->execute();

      // ' 解除 from q10_information
      $sql = <<<EOD
        UPDATE tb_qten_information iq 
        INNER JOIN tmp_qten_csv_products tmp ON iq.daihyo_syohin_code = tmp.daihyo_syohin_code
        SET iq.q10_itemcode = ''
        WHERE tmp.delete_flg <> 0
EOD;
      $dbMain->exec($sql);

      // ' 解除 from q10_itemcode
      $sql = <<<EOD
        UPDATE tb_qten_itemcode q_code
        INNER JOIN tmp_qten_csv_products tmp ON q_code.daihyo_syohin_code = tmp.daihyo_syohin_code
        SET q_code.daihyo_syohin_code = ''
        WHERE tmp.delete_flg <> 0
EOD;
      $dbMain->exec($sql);
    }

    //' 4. 未割り振り商品へアイテムコードを割り振り
    $logger->info('Q10商品コードの割り当てIndexを設定中(tb_qten_information)');

    //' info.q10_itemcode_index に連番を格納 1～
    $dbMain->exec("UPDATE tb_qten_information i SET q10_itemcode_index = 0 WHERE i.q10_itemcode_index <> 0;");
    $sql = <<<EOD
      UPDATE tb_qten_information AS q
      INNER JOIN (
         SELECT
             @num := @num + 1 AS no
           , tmp.daihyo_syohin_code
         FROM (SELECT @num := 0) AS dmy
         INNER JOIN tmp_qten_csv_products tmp
         WHERE tmp.delete_flg = 0
           AND tmp.q10_itemcode = ''
         ORDER BY
           CASE
             WHEN tmp.endofavailability IS NULL
             THEN '1'
             ELSE '2'
             END
           , tmp.last_orderdate DESC
           , tmp.daihyo_syohin_code
      ) AS T ON q.daihyo_syohin_code = T.daihyo_syohin_code
      SET q.q10_itemcode_index = T.no
EOD;
    $dbMain->exec($sql);

    //' itemcode.q10_itemcode_index に連番を格納
    $dbMain->exec("UPDATE tb_qten_itemcode SET q10_itemcode_index = 0");
    $sql = <<<EOD
      UPDATE tb_qten_itemcode AS qc
      INNER JOIN (
         SELECT
             @num := @num + 1 AS no
           , TBL1.q10_itemcode
         FROM (select @num := 0) AS dmy
            , (
              SELECT q10_itemcode 
              FROM tb_qten_itemcode
              WHERE daihyo_syohin_code = ''
              ORDER BY q10_itemcode
            ) AS TBL1
       ) AS TBL2 ON qc.q10_itemcode = TBL2.q10_itemcode
      SET qc.q10_itemcode_index = TBL2.no
EOD;
    $dbMain->exec($sql);

    $logger->info("Q10商品コードの割り当て中(tb_qten_information)");
    $sql = <<<EOD
      UPDATE tb_qten_information AS i
      INNER JOIN tb_qten_itemcode AS item ON i.q10_itemcode_index = item.q10_itemcode_index
      SET i.q10_itemcode = item.q10_itemcode
      WHERE i.q10_itemcode_index > 0
        AND item.q10_itemcode_index > 0
EOD;
    $dbMain->exec($sql);

    $logger->info("Q10商品コードの割り当て中(tb_qten_itemcode)");
    $sql = <<<EOD
      UPDATE tb_qten_itemcode AS item
      INNER JOIN  tb_qten_information AS i ON item.q10_itemcode_index = i.q10_itemcode_index
      SET item.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE i.q10_itemcode_index > 0
        AND item.q10_itemcode_index > 0
EOD;
    $dbMain->exec($sql);

    //' 一時テーブル削除
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_qten_csv_products");

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * CSVファイル出力処理
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  private function export()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logger->addDbLog($logger->makeDbLog(null, 'エクスポート', '開始'));

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $immediateShippingDate = $commonUtil->getImmediateShippingDate();
    $today = (new \DateTime())->setTime(0, 0, 0);
    $daysToImmediateShippingDate = $immediateShippingDate->diff($today)->format('%a');
    $includeEnd = intval($commonUtil->getSettingValue('Q10_INCLUDE_END')) !== 0;

    $addWhereSql = '';
    if (!$includeEnd) {
      $addWheres = [];
      $addWheres[] = "i.q10_itemcode <> ''";
      $addWheres[] = "i.daihyo_syohin_code <> ''";
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }

    // '====================
    // 'item.csv
    // '====================
    $logTitle = 'エクスポート(item.csv)';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 表示画像コード
    $imageCodeListSql = '';
    $tmp = [];
    foreach(Q10MallProcess::$IMAGE_CODE_LIST as $code) {
      $tmp[] = sprintf("%s", $dbMain->quote($code, \PDO::PARAM_STR));
    }
    if ($tmp) {
      $codes = implode(', ', $tmp);
      $imageCodeListSql = <<<EOD
      WHERE i.code IN ( {$codes} )
EOD;
    }

    // このデータの処理中に、さらにSELECTを発行するため、
    // MySQLドライバで「バッファモード」 MYSQL_ATTR_USE_BUFFERED_QUERY : true で実行されている必要がある。
    $dbMain->query("SET SESSION group_concat_max_len = 20480");

    $sql = <<<EOD
      SELECT
          i.q10_itemcode                            AS `Item Code`
        , i.daihyo_syohin_code                      AS `Seller Code`
--        , CASE
--            WHEN cal.endofavailability IS NULL THEN 'S2'
--            ELSE 'S1'
--          END                                         AS Status
        , 'S1'                                      AS Status
        , COALESCE(d.`Q10ディレクトリID`, '300000052') AS `2nd Cat Code`
        , LEFT(
            CONCAT(
                COALESCE(tp.front_title, '')
              , COALESCE(i.q10_title, '')
              , '【'
              , COALESCE(m.sire_code, '')
              , '】'
            )
            , 75 /* ざっくり。従来はここからさらに16文字引いていたが、ひとまず不要と判断 */
          )                                         AS `Item Name`
        , ""                                        AS `Item Description`
        , ""                                        AS `Short Title`
        , ""                                        AS `Item Detail Header`
        , ""                                        AS `Item Detail Footer`
        , i.q10_title                               AS `Brief Description`
        , ""                                        AS `Image URL`
        , COALESCE(price.taxed, i.baika_tanka)      AS `Sell Price`
        , CASE 
            WHEN cal.endofavailability IS NULL THEN 1000
            ELSE 0
          END                                       AS `Sell Qty`
        , CASE sd.shipping_group_code
            WHEN :shippingGroupCodeTakuhaibin THEN '299580' /* 宅配便 => 佐川急便送料無料 */
            WHEN :shippingGroupCodeMailbin THEN '299538' /* メール便 => クロネコメール便 */
            WHEN :shippingGroupCodeTeikei THEN '45150' /* 定形郵便 =>  送料無料 */
            WHEN :shippingGroupCodeTeikeigai THEN '299578' /* 定形外郵便 => 定形外郵便 */
            WHEN :shippingGroupCodeYuuPacket THEN '369135' /* ゆうパケット => ゆうパケット */
            WHEN :shippingGroupCodeNekoposu THEN '408279' /* ねこポス => ねこポス */
            WHEN :shippingGroupCodeClickpost THEN '369135' /* クリックポスト => ゆうパケット */
          END                                                       AS `Shipping Group No`
        , 1                                                         AS `Item Weight`
        , ""                                                        AS `Option Info`
        , i.inventory_info                                          AS `Inventory Info`
        , 0                                                         AS `Maker No`
        , 0                                                         AS `Brand No`
        , ""                                                        AS `Product Model Name`
        , 0                                                         AS `Retail Price`
        , 2                                                         AS `Origin Type`
        , ""                                                        AS `Place of Origin`
        , ""                                                        AS `Industrial Code`
        , 1                                                         AS `Item Condition`
        , ""                                                        AS `Manufacture Date`
        , "N"                                                       AS `Adult Product Y/N`
        , ""                                                        AS `A/S Info`
        , :daysToImmediateShippingDate             AS `Available Date`
        , ""                                                        AS Gift
        , ""                                                        AS `Additional Item Image`
        , ""                                                        AS `Inventory Cover Image`
        , ""                                                        AS `Multi Shipping Rate` 
        
        /* テンプレート用情報 */
        , image.images
        , m.商品コメントPC
        , m.サイズについて
        , m.カラーについて
        , m.素材について
        , m.ブランドについて
        , m.使用上の注意
        , m.補足説明PC
        , m.横軸項目名
        , m.縦軸項目名
      FROM ( 
        tb_qten_information i 
        RIGHT JOIN tb_qten_itemcode item ON i.q10_itemcode = item.q10_itemcode
      ) 
      LEFT JOIN tb_mainproducts m ON i.daihyo_syohin_code = m.daihyo_syohin_code 
      LEFT JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code 
      LEFT JOIN tb_title_parts AS tp ON i.daihyo_syohin_code = tp.daihyo_syohin_code
      LEFT JOIN tax_price price ON i.baika_tanka = price.base
      LEFT JOIN tb_plusnaoproductdirectory AS d ON m.NEディレクトリID = d.NEディレクトリID 
      LEFT JOIN tb_shippingdivision sd ON sd.id = m.送料設定
      LEFT JOIN (
        SELECT
            i.daihyo_syohin_code
          , GROUP_CONCAT(
              CONCAT(
                  i.code
                , '$'
                , i.address
              )
              ORDER BY i.code
              SEPARATOR '\n'
            ) AS images
        FROM product_images i
        {$imageCodeListSql}
        GROUP BY i.daihyo_syohin_code
      ) AS image ON i.daihyo_syohin_code = image.daihyo_syohin_code
      
      WHERE i.registration_flg <> 0 
        AND cal.adult_check_status IN ( :adultCheckStatusWhite, :adultCheckStatusGray )
        AND COALESCE(price.taxed, i.baika_tanka, 0) > 0
        {$addWhereSql}    
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daysToImmediateShippingDate', $daysToImmediateShippingDate, \PDO::PARAM_STR);
    $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeMailbin', TbShippingdivision::SHIPPING_GROUP_CODE_MAILBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeNekoposu', TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeClickpost', TbShippingdivision::SHIPPING_GROUP_CODE_CLICKPOST, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);

    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      /** @var \Twig_Environment $twig */
      $twig = $this->getContainer()->get('twig');

      $templateDescription = $twig->load('BatchBundle:ExportCsvQ10:description.html.twig');

      // ヘッダ
      $headers = [
          'Item Code'
        , 'Seller Code'
        , 'Status'
        , '2nd Cat Code'
        , 'Item Name'
        , 'Item Description'
        , 'Short Title'
        , 'Item Detail Header'
        , 'Item Detail Footer'
        , 'Brief Description'
        , 'Image URL'
        , 'Sell Price'
        , 'Sell Qty'
        , 'Shipping Group No'
        , 'Item Weight'
        , 'Option Info'
        , 'Inventory Info'
        , 'Maker No'
        , 'Brand No'
        , 'Product Model Name'
        , 'Retail Price'
        , 'Origin Type'
        , 'Place of Origin'
        , 'Industrial Code'
        , 'Item Condition'
        , 'Manufacture Date'
        , 'Adult Product Y/N'
        , 'A/S Info'
        , 'Available Date'
        , 'Gift'
        , 'Additional Item Image'
        , 'Inventory Cover Image'
        , 'Multi Shipping Rate'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers) . "\r\n";

      $files = [];
      $fp = null;
      $num = 0;
      $numTotal = 0;
      $index = 0;
      $fileNameDateTime = new \DateTime();
      $fileNameDateTime->modify('+30 minutes'); // 30分後からスタート

      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        if (!isset($fp)) {
          $fileNameDateTime->modify(sprintf('+%d minutes', $index++ * 5)); // なんとなく5分刻み
          $filePath = sprintf('%s/item_%s.csv', $this->exportPath, $fileNameDateTime->format('YmdHi00'));
          $files[$index] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, pack('C*',0xEF,0xBB,0xBF)); // Q10はBOMが必要。
          fputs($fp, $headerLine);
          $logger->info('csv output: ' . $filePath);
        }

        // HTML作成、画像URL作成、各種変換処理 -> 数万回のselect GO!
        $this->convertCsvContents($row, [
            'templateDescription' => $templateDescription
        ]);

        $line = $stringUtil->convertArrayToCsvLine($row, $headers) . "\r\n";
        fputs($fp, $line);

        $num++;
        $numTotal++;

        if ($num >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $num = 0;
        }
      }

      if (isset($fp)) {
        fclose($fp);
      }

      $this->results['item.csv'] = $files;
      $logger->info(sprintf("Q10 CSV出力 item.csv: $numTotal 件 / ファイル数: %d", count($files)));

    } else {
      $logger->info("Q10 CSV出力 item.csv: 件数が0のためファイルは作成しませんでした。");
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));


    $logger->addDbLog($logger->makeDbLog(null, 'エクスポート', '終了'));
  }


  /**
   * CSV出力内容 作成処理
   * @param array &$row
   * @param array $options
   * @throws \Doctrine\DBAL\DBALException
   */
  private function convertCsvContents(&$row, $options)
  {
    $dbMain = $this->getDb('main');

    $tmp = explode("\n", $row['images']);
    $images = [];
    foreach($tmp as $image) {
      if (strpos($image, '$') !== false) {
        list($code, $address) = explode('$', $image);
        if ($code && $address) {
          $images[$code] = $address;
        }
      }
    }

    $data = [
        'row' => $row
      , 'images' => $images
    ];

    // HTML作成：商品説明 ※改行はだめな模様？
    $row['Item Description'] = str_replace("\n", "", str_replace("\r", "", trim($options['templateDescription']->render($data))));

    // 在庫表(Inventory Info) 作成  (毎回ガチャコン！)
    // ループの外のStatementも絶賛処理中なので、
    // MySQLドライバで「バッファモード」 MYSQL_ATTR_USE_BUFFERED_QUERY : true で実行されている必要がある。
    $sql = <<<EOD
      SELECT 
           pci.ne_syohin_syohin_code
         , pci.colname
         , pci.colcode
         , pci.rowname
         , pci.rowcode
         , pci.`フリー在庫数`
      FROM tb_productchoiceitems pci 
      WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
      ORDER BY pci.`並び順No` 
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $row['Seller Code'], \PDO::PARAM_STR);
    $stmt->execute();
    $choices = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $inventories = [];
    foreach($choices as $choice) {
      $inventory = [
          $row['横軸項目名']
        , $choice['colname']
        , $row['縦軸項目名']
        , $choice['rowname']
        , '0.00'
        , $choice['フリー在庫数']
        , sprintf('%s%s', $choice['colcode'], $choice['rowcode'])
      ];

      $inventories[] = implode('||*', $inventory);
    }
    $row['Inventory Info'] = implode('$$', $inventories);

    // 商品画像URL
    if (isset($images['p001']) && strlen($images['p001'])) {
      $row['Image URL'] = $images['p001'];
    }

    // 追加商品画像
    $addImages = [];
    foreach($images as $code => $address) {
      if ($code == 'p001') {
        continue;
      }
      if (strlen($address)) {
        $addImages[] = $address;
      }
    }
    if ($addImages) {
      $row['Additional Item Image'] = implode('$$', $addImages);
    }
  }

  /**
   * アップロード処理（キュー追加）
   * @param $files
   */
  private function upload($files)
  {
    $logger = $this->getLogger();
    $logger->addDbLog($logger->makeDbLog(null, 'アップロードキュー追加', '開始'));

    /** @var Q10MallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.q10');

    foreach($files as $filePath) {
      $processor->enqueueUploadCsv($filePath, basename($filePath), $this->getEnvironment(), self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
    }

    $logger->addDbLog($logger->makeDbLog(null, 'アップロードキュー追加', '終了'));
  }

}
