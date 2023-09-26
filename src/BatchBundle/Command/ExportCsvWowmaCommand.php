<?php
/**
 * バッチ処理 Wowma CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\WowmaMallProcess;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvWowmaCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  private $exportPath;

  private $skipCommonProcess = false;
  private $exportDaihyoSyohinCode = null; // ほぼテスト用

  private $exportAll = false;

  /** @var \DateTime */
  private $processStart; // 処理開始日時。処理完了後、前回処理日時として保存する。

  /** @var bool  */
  private $doCreateCsv = true;

  /** @var bool  */
  private $doUploadCsv = true;

  /** @var bool  */
  private $doUploadImage = true;

  private $results = [];

  /** 販売中商品のみCSV出力するかのフラグ */
  private $biddersProductExportAllFlg = 0; // tb_settingの値を格納する

  const DOWNLOADED_PATH = 'Wowma/Downloaded';
  const EXPORT_PATH = 'Wowma/Export';

  const UPLOAD_EXEC_TITLE = 'Wowma CSV出力処理';

  // アップロードファイルの分割行数
  const UPLOAD_CSV_MAX_NUM = 20000; // 2万行で分割

  // 在庫数の上限が50個な理由は不明だが、おそらく『選択肢つき商品に関し、全枝番号のフリー在庫数の合計が99999を超えてはならない』という仕様対応
  const QUANTITY_MAX = 50;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-wowma')
      ->setDescription('Wowma CSV出力処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('export-dir', InputArgument::OPTIONAL, '出力先ディレクトリ', null)
      ->addOption('do-create-csv', null, InputOption::VALUE_OPTIONAL, 'CSV出力フラグ', 1)
      ->addOption('do-upload-csv', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ(CSV)', 1)
      ->addOption('do-upload-image', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ(画像)', 1)
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理をスキップ', '0')
      ->addOption('daihyo-syohin-code', null, InputOption::VALUE_OPTIONAL, '商品コード指定')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Wowma CSV出力処理を開始しました。');

    $this->processStart = new \DateTime();
    $this->doCreateCsv = (bool)$input->getOption('do-create-csv');
    $this->doUploadCsv = (bool)$input->getOption('do-upload-csv');
    $this->doUploadImage = (bool)$input->getOption('do-upload-image');

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
        , 'stock.csv' => []
        , 'delete.csv' => []
      ];

      $logExecTitle = sprintf('Wowma CSV出力処理');
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

      // 入力パス
      $downloadedDir = $this->getFileUtil()->getWebCsvDir() . '/' . self::DOWNLOADED_PATH;
      if ($fs->exists($downloadedDir)) {

        $finder = new Finder();
        $finder->in($downloadedDir);
        $finder->sort(function($a, $b) {
          /** @var \Symfony\Component\Finder\SplFileInfo $a */
          /** @var \Symfony\Component\Finder\SplFileInfo $b */
          if ($a->getMTime() == $b->getMTime()) {
            return 0;
          }

          return $a->getMTime() > $b->getMTime() ? -1 : 0;
        });
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        $file = null;
        foreach($finder as $file) {
          break;
        }

        if ($file) {
          /** @var WowmaMallProcess $processor */
          $processor = $this->getContainer()->get('batch.mall_process.wowma');
          $importFiles = $processor->processUploadedCsvFiles([ $file ]);
          $processor->importLotNumberCsv($importFiles);
        }
      }

      // 共通処理スキップフラグ
      $this->skipCommonProcess = boolval($input->getOption('skip-common-process'));
      $this->exportDaihyoSyohinCode = $input->getOption('daihyo-syohin-code');

      if (!$this->skipCommonProcess) {
        $commonUtil = $this->getDbCommonUtil();
        $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_BIDDERS);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }

      // CSV出力
      $this->export();

      // 0円商品をチェック
      $this->check0priceProduct();

      // 画像ファイル収集＆アップロード
      $this->uploadImage();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Wowma CSV出力処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Wowma CSV出力処理 エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog('Wowma CSV出力処理 エラー', 'Wowma CSV出力処理 エラー', 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, 'Wowma CSV出力処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

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
    $dbMainName = $dbMain->getDatabase();

    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    $dbTmpName = $dbTmp->getDatabase();

    $commonUtil = $this->getDbCommonUtil();

    /** @var WowmaMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.wowma');

    $logger->addDbLog($logger->makeDbLog(null, 'エクスポート', '開始'));

    // CSV出力モードのフラグ値を取得
    $this->biddersProductExportAllFlg = $commonUtil->getSettingValue('BIDDERS_PRODUCT_EXPORT_ALL');

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    if ($this->exportDaihyoSyohinCode) {
      $addWheres[] = sprintf(" ( m.daihyo_syohin_code = :daihyoSyohinCode ) ");
      $addParams[':daihyoSyohinCode'] = $this->exportDaihyoSyohinCode;
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }


    // $temporaryWord = 'TEMPORARY';
    $temporaryWord = ''; // FOR DEBUG
    // '====================
    // 'stock.csv
    // '====================
    $logTitle = 'エクスポート(stock.csv)';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 横軸・縦軸 並び順テーブル作成
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_sku_col_order;");
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_sku_row_order;");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_sku_col_order (
          daihyo_syohin_code VARCHAR(30) NOT NULL
        , colcode VARCHAR(100) NOT NULL
        , col_order INTEGER NOT NULL DEFAULT 0 
        , PRIMARY KEY (`daihyo_syohin_code`, `colcode`)
      )
      SELECT 
          o1.daihyo_syohin_code
        , o1.colcode
        , SUM(
              o2.display_order > o1.display_order
           OR (
                  o2.display_order = o1.display_order
              AND o2.colcode >= o1.colcode
           )
        ) AS col_order
      FROM v_sku_order_col o1 
      INNER JOIN v_sku_order_col o2 
         ON o1.daihyo_syohin_code = o2.daihyo_syohin_code
      GROUP BY o1.daihyo_syohin_code, o1.colcode
      ORDER BY o1.daihyo_syohin_code, col_order
      ;
EOD;
    $dbMain->exec($sql);

    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_sku_row_order (
          daihyo_syohin_code VARCHAR(30) NOT NULL
        , rowcode VARCHAR(100) NOT NULL
        , row_order INTEGER NOT NULL DEFAULT 0 
        , PRIMARY KEY (`daihyo_syohin_code`, `rowcode`)
      )
      SELECT 
          o1.daihyo_syohin_code
        , o1.rowcode
        , SUM(
              o2.display_order > o1.display_order
           OR (
                  o2.display_order = o1.display_order
              AND o2.rowcode >= o1.rowcode
           )
        ) AS row_order
      FROM v_sku_order_row o1 
      INNER JOIN v_sku_order_row o2 
         ON o1.daihyo_syohin_code = o2.daihyo_syohin_code
      GROUP BY o1.daihyo_syohin_code, o1.rowcode
      ORDER BY o1.daihyo_syohin_code, row_order
      ;
EOD;
    $dbMain->exec($sql);

    $logger->debug('tmp_wowma_csv_stock 構築開始');

    // 出力対象格納テーブル。行数制限による分割のため、一時テーブルに保存する。
    $dbTmp->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_wowma_csv_stock;");

    // このテーブルは、出力設定を問わず「全件」のときのデータを登録する
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_wowma_csv_stock (
          ctrlCol VARCHAR(1) NOT NULL
        , itemCode VARCHAR(30) NOT NULL
        , stockSegment VARCHAR(1) NOT NULL
        , choicesStockHorizontalName VARCHAR(50) NOT NULL
        , choicesStockHorizontalCode VARCHAR(50) NOT NULL
        , choicesStockHorizontalSeq VARCHAR(50) NOT NULL
        , choicesStockVerticalName VARCHAR(50) NOT NULL
        , choicesStockVerticalCode VARCHAR(50) NOT NULL
        , choicesStockVerticalSeq VARCHAR(50) NOT NULL
        , choicesStockCount INTEGER NOT NULL
        , choicesStockShippingDayId VARCHAR(3) NOT NULL
        , current_deliverycode int NOT NULL
        , export_only_sale_flg tinyint NOT NULL default 0 COMMENT '販売中CSV出力フラグ\n販売中CSV出力対象であれば1、出力対象でなければ0。販売中の場合はこれを元に絞り込みを行う'
        , id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY
        , UNIQUE KEY (`itemCode`, `choicesStockHorizontalCode`, `choicesStockVerticalCode`)
      )
      SELECT
          'N'                     AS ctrlCol
        , pci.daihyo_syohin_code  AS itemCode
        , '2'                     AS stockSegment
        , pci.colname   AS choicesStockHorizontalName
        , pci.colcode   AS choicesStockHorizontalCode
        , col.col_order AS choicesStockHorizontalSeq
        , pci.rowname   AS choicesStockVerticalName
        , pci.rowcode   AS choicesStockVerticalCode
        , row.row_order AS choicesStockVerticalSeq
        , LEAST(COALESCE(pci.フリー在庫数, 0), :quantityMax) AS choicesStockCount -- フリー在庫数を取得。最大数を超えていたら最大数まで 
        , (
            SELECT DATEDIFF(MAX(c2.calendar_date),DATE(NOW())) + 1 # 当日も含めるため1を加算
            FROM (
                SELECT c1.calendar_date
                FROM {$dbMainName}.tb_calendar c1
                WHERE c1.workingday = -1
                AND CASE
                      WHEN (HOUR(NOW()) < 18) THEN DATE(NOW()) < c1.calendar_date
                      ELSE DATE_ADD(DATE(NOW()),INTERVAL 1 DAY) < c1.calendar_date
                    END
                LIMIT 2 # 2営業日
            ) c2
          ) AS choicesStockShippingDayId
        , cal.deliverycode_pre AS current_deliverycode
        , CASE
            WHEN cal.deliverycode_pre IN (:deliveryCodeReady, :deliveryCodeReadyPartially) THEN 1 -- plusnao側が即納、一部即納
            WHEN i.last_deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially) THEN 1 -- Wowma側が即納、一部即納
            WHEN i.last_deliverycode = :deliveryCodePurchaseOnOrder AND cal.deliverycode_pre = :deliveryCodeReadyFinished THEN 1 -- Wowma側が受発注のみ、plusnao側が販売終了
          ELSE 0 END AS export_only_sale_flg
      FROM {$dbMainName}.tb_productchoiceitems pci
      INNER JOIN  {$dbMainName}.tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN {$dbMainName}.tb_plusnaoproductdirectory d   ON m.`NEディレクトリID` = d.`NEディレクトリID`
      INNER JOIN {$dbMainName}.tb_mainproducts_cal        cal ON m.`daihyo_syohin_code` = cal.`daihyo_syohin_code`
      INNER JOIN {$dbMainName}.tb_title_parts             tp  ON m.daihyo_syohin_code = tp.daihyo_syohin_code
      INNER JOIN {$dbMainName}.tb_biddersinfomation       i   ON m.`daihyo_syohin_code` = i.`daihyo_syohin_code`
      INNER JOIN {$dbMainName}.tmp_sku_col_order          col ON pci.daihyo_syohin_code = col.daihyo_syohin_code
                                               AND pci.colcode = col.colcode
      INNER JOIN {$dbMainName}.tmp_sku_row_order          row ON pci.daihyo_syohin_code = row.daihyo_syohin_code
                                               AND pci.rowcode = row.rowcode
      WHERE
          cal.deliverycode_pre <> :deliveryCodeTemporary
          AND (
               cal.endofavailability > DATE_ADD(CURRENT_DATE, INTERVAL - 10 DAY)
            OR cal.endofavailability IS NULL
            OR (i.last_deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder) AND cal.deliverycode_pre = :deliveryCodeReadyFinished)
          )
          AND i.registration_flg <> 0
          AND (
            cal.adult_check_status <> :adultCheckStatusNone
          )
          AND COALESCE(i.`baika_tanka`, 0) > 0 /* 0 はイレギュラー（仮登録商品など）。Wowmaでエラーになる。いったん除外。 */
          {$addWhereSql}
      ORDER BY
          pci.daihyo_syohin_code
        , pci.`並び順No`
EOD;
    $stmt = $dbTmp->prepare($sql);
    $stmt->bindValue(':quantityMax', self::QUANTITY_MAX, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    foreach($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    // セット商品の在庫数を更新する
    $sql = <<< EOD
      UPDATE tmp_wowma_csv_stock t
      INNER JOIN (
        SELECT
            pci.ne_syohin_syohin_code AS set_sku
          , MIN(TRUNCATE((COALESCE(pci_detail.フリー在庫数, 0)/ d.num), 0)) AS creatable_num
        FROM {$dbMainName}.tb_productchoiceitems pci 
        INNER JOIN {$dbMainName}.tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN {$dbMainName}.tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN {$dbMainName}.tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        INNER JOIN {$dbMainName}.tb_biddersinfomation i ON pci_detail.daihyo_syohin_code = i.daihyo_syohin_code
        WHERE m.set_flg <> 0
        GROUP BY set_sku
      ) STOCK ON CONCAT(t.itemCode, t.choicesStockHorizontalCode, t.choicesStockVerticalCode) = STOCK.set_sku
      SET t.choicesStockCount = LEAST(STOCK.creatable_num, :quantityMax)
EOD;
    $stmt = $dbTmp->prepare($sql);
    $stmt->bindValue(':quantityMax', self::QUANTITY_MAX, \PDO::PARAM_INT);
    $stmt->execute();

    $logger->debug('tmp_wowma_csv_stock_id 構築開始');

    // 分割計数用一時テーブル
    $dbTmp->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_wowma_csv_stock_id;");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_wowma_csv_stock_id (
          itemCode VARCHAR(30) NOT NULL
        , min_id int NOT NULL
        , max_id int NOT NULL
        , PRIMARY KEY ( itemCode )
      )
      SELECT
          t.itemCode
        , MIN(id) AS min_id
        , MAX(id) AS max_id
      FROM tmp_wowma_csv_stock t
      GROUP BY t.itemCode
      ;
EOD;
    $dbTmp->exec($sql);

    $logger->debug('CSV分割の境界取得');
    $limit = self::UPLOAD_CSV_MAX_NUM - floor(self::UPLOAD_CSV_MAX_NUM * 0.1); // 境界のブレ吸収のためのマージン
    $exportBorderIdList = [];

    // 全件出力と販売中のみでWhere句が変わる
    $where = '';
    if ($this->biddersProductExportAllFlg != 1) {
      $where = 'JOIN (
        SELECT DISTINCT itemCode FROM tmp_wowma_csv_stock WHERE export_only_sale_flg = 1
      ) base ON base.itemCode = id.itemCode';
    }

    $sql = <<<EOD
      SELECT id.itemCode, id.min_id, id.max_id FROM tmp_wowma_csv_stock_id  id
      {$where}
      ORDER BY id.min_id
EOD;
    $stmt = $dbTmp->prepare($sql);
    $stmt->execute();

    $exportBorderIdList = array(); // 境界値を入れていく配列
    $skuNum = 0; // 境界計算用に、紐づくSKU数をカウントしていく
    $minId = 0;
    $maxId = 0;

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      // 1周目は現在のminId取得
      if ($minId == 0) {
        $minId = $row['min_id'];
      }
      $maxId = $row['max_id'];
      $skuNum += $maxId - $row['min_id'] + 1;
      if ($skuNum >= $limit) { // 上限を超えたらその代表商品を最後とする（バッファがあるので多少のオーバーは問題ない）
        $exportBorderIdList[] = ['min_id' => $minId, 'max_id' => $maxId];
        $skuNum = 0;
        $minId = 0;
      }
    }
    if ($minId) {
      $exportBorderIdList[] = ['min_id' => $minId, 'max_id' => $maxId]; // 最後の1件があれば登録
    }

    // 出力
    $files = [];
    $fp = null;
    $num = 0;
    if (!$this->doCreateCsv) {
      $logger->info("Wowma CSV出力 stock.csv: CSV作成フラグOFFのため、ファイルは作成しませんでした。");

    } else if ($exportBorderIdList) {

      // 全件出力と販売中のみでWhere句が変わる
      $where = '';
      if ($this->biddersProductExportAllFlg != 1) {
        $where = 'AND s.export_only_sale_flg = 1';
      }

      foreach($exportBorderIdList as $index => $border) {
        $logger->debug("border: " . print_r($border, true));

        $sql = <<<EOD
        SELECT
          *
        FROM tmp_wowma_csv_stock s
        WHERE s.id >= :minId
          AND s.id <= :maxId
          {$where}
        ORDER BY s.id
EOD;
        $stmt = $dbTmp->prepare($sql);
        $stmt->bindValue(':minId', $border['min_id'], \PDO::PARAM_INT);
        $stmt->bindValue(':maxId', $border['max_id'], \PDO::PARAM_INT);
        $stmt->execute();

        // ヘッダ
        $headers = [
            'ctrlCol'
          , 'itemCode'
          , 'stockSegment'
          , 'choicesStockHorizontalName'
          , 'choicesStockHorizontalCode'
          , 'choicesStockHorizontalSeq'
          , 'choicesStockVerticalName'
          , 'choicesStockVerticalCode'
          , 'choicesStockVerticalSeq'
          , 'choicesStockCount'
          , 'choicesStockShippingDayId'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        $filePath = sprintf('%s/stock-%02d.csv', $this->exportPath, ($index + 1));
        $files[$index] = $filePath;
        $fp = fopen($filePath, 'wb');
        fputs($fp, $headerLine);

        $logger->info('csv output: ' . $filePath);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          $num++;
        }
        fclose($fp);

        $this->results['stock.csv'] = $files;
        $logger->info(sprintf("Wowma CSV出力 stock.csv: $num 件 / ファイル数: (%d)", $index + 1));
      }

    } else {
      $logger->info("Wowma CSV出力 stock.csv: 件数が0のためファイルは作成しませんでした。");
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));



    // '====================
    // 'item.csv
    // '====================
    $logTitle = 'エクスポート(item.csv)';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 表示画像コード
    $imageCodeListSql = '';
    $tmp = [];
    foreach(WowmaMallProcess::$IMAGE_CODE_LIST as $code) {
      $tmp[] = sprintf("%s", $dbMain->quote($code, \PDO::PARAM_STR));
    }
    if ($tmp) {
      $codes = implode(', ', $tmp);
      $imageCodeListSql = <<<EOD
      WHERE i.code IN ( {$codes} )
EOD;
    }

    $dbMain->query("SET SESSION group_CONCAT_max_len = 20480");

    $files = [];
    $fp = null;
    $num = 0;
    if (!$this->doCreateCsv) {
      $logger->info("Wowma CSV出力 item.csv: CSV作成フラグOFFのため、ファイルは作成しませんでした。");

    } else if ($exportBorderIdList) {
      foreach($exportBorderIdList as $index => $border) {

        // 全件出力と販売中のみでWhere句が変わる
        $where = '';
        if ($this->biddersProductExportAllFlg != 1) {
          $where = 'AND stock.export_only_sale_flg = 1';
        }

        $sql = <<<EOD
          SELECT
              CASE WHEN (l.itemCode IS NULL OR COALESCE(l.lotNumber, '') = '') THEN 'N' ELSE 'U' END AS ctrlCol
            , m.`daihyo_syohin_code`      AS itemCode
            , d.`BIDDDERSディレクトリID`  AS categoryId
            , CASE
                WHEN i.`search_keyword1` <> ''
                THEN i.`search_keyword1`
                ELSE 'NULL'
              END AS searchKeyword1
            , CASE
                WHEN
                      i.`search_keyword1` <> ''
                  AND i.`search_keyword2` <> ''
                THEN i.`search_keyword2`
                ELSE 'NULL'
              END AS searchKeyword2
            , CASE
                WHEN
                      i.`search_keyword1` <> ''
                  AND i.`search_keyword2` <> ''
                  AND i.`search_keyword3` <> ''
                THEN i.`search_keyword3`
                ELSE 'NULL'
              END AS searchKeyword3
            , '3'                         AS taxSegment
            , '2'                         AS postageSegment
            , ''                          AS postage
            , '' AS deliveryMethodId1
            , '' AS deliveryMethodId2
            , '' AS deliveryMethodId3
            , '' AS deliveryMethodId4
            , '' AS deliveryMethodId5
            , CASE WHEN cal.`maxbuynum` = 0 THEN 'NULL' ELSE cal.`maxbuynum` END AS limitedOrderCount
            , '1' AS displayStockSegment
            , FLOOR(i.`baika_tanka` * CAST(:taxRate AS DECIMAL)) AS itemPrice
            , LEFT(CONCAT(COALESCE(i.`front_title`, ''), i.`bidders_title`), 64) AS itemName
            , ''  AS description  
            , ''  AS descriptionForSP
            , ''  AS descriptionForPC
            , CASE WHEN COALESCE(m.`商品画像P1Cption`, '') <> '' THEN m.`商品画像P1Cption` ELSE '【1】' END AS imageName1
            , CASE WHEN COALESCE(m.`商品画像P2Cption`, '') <> '' THEN m.`商品画像P2Cption` ELSE '【2】' END AS imageName2
            , CASE WHEN COALESCE(m.`商品画像P3Cption`, '') <> '' THEN m.`商品画像P3Cption` ELSE '【3】' END AS imageName3
            , CASE WHEN COALESCE(m.`商品画像P4Cption`, '') <> '' THEN m.`商品画像P4Cption` ELSE '【4】' END AS imageName4
            , CASE WHEN COALESCE(m.`商品画像P5Cption`, '') <> '' THEN m.`商品画像P5Cption` ELSE '【5】' END AS imageName5
            , CASE WHEN COALESCE(m.`商品画像P6Cption`, '') <> '' THEN m.`商品画像P6Cption` ELSE '【6】' END AS imageName6
            , CASE WHEN COALESCE(m.`商品画像P7Cption`, '') <> '' THEN m.`商品画像P7Cption` ELSE '【7】' END AS imageName7
            , CASE WHEN COALESCE(m.`商品画像P8Cption`, '') <> '' THEN m.`商品画像P8Cption` ELSE '【8】' END AS imageName8
            , CASE WHEN COALESCE(m.`商品画像P9Cption`, '') <> '' THEN m.`商品画像P9Cption` ELSE '【9】' END AS imageName9
            , CASE WHEN COALESCE(m.`商品画像P10Cption`, '') <> '' THEN m.`商品画像P10Cption` ELSE '【10】' END AS imageName10
            , CASE WHEN COALESCE(m.`商品画像P11Cption`, '') <> '' THEN m.`商品画像P11Cption` ELSE '【11】' END AS imageName11
            , CASE WHEN COALESCE(m.`商品画像P12Cption`, '') <> '' THEN m.`商品画像P12Cption` ELSE '【12】' END AS imageName12
            , CASE WHEN COALESCE(m.`商品画像P13Cption`, '') <> '' THEN m.`商品画像P13Cption` ELSE '【13】' END AS imageName13
            , CASE WHEN COALESCE(m.`商品画像P14Cption`, '') <> '' THEN m.`商品画像P14Cption` ELSE '【14】' END AS imageName14
            , CASE WHEN COALESCE(m.`商品画像P15Cption`, '') <> '' THEN m.`商品画像P15Cption` ELSE '【15】' END AS imageName15
            , CASE WHEN COALESCE(m.`商品画像P16Cption`, '') <> '' THEN m.`商品画像P16Cption` ELSE '【16】' END AS imageName16
            , CASE WHEN COALESCE(m.`商品画像P17Cption`, '') <> '' THEN m.`商品画像P17Cption` ELSE '【17】' END AS imageName17
            , CASE WHEN COALESCE(m.`商品画像P18Cption`, '') <> '' THEN m.`商品画像P18Cption` ELSE '【18】' END AS imageName18
            , CASE WHEN COALESCE(m.`商品画像P19Cption`, '') <> '' THEN m.`商品画像P19Cption` ELSE '【19】' END AS imageName19
            , CASE WHEN COALESCE(m.`商品画像P20Cption`, '') <> '' THEN m.`商品画像P20Cption` ELSE '【20】' END AS imageName20
            , 'NULL' as imageUrl1
            , 'NULL' as imageUrl2
            , 'NULL' as imageUrl3
            , 'NULL' as imageUrl4
            , 'NULL' as imageUrl5
            , 'NULL' as imageUrl6
            , 'NULL' as imageUrl7
            , 'NULL' as imageUrl8
            , 'NULL' as imageUrl9
            , 'NULL' as imageUrl10
            , 'NULL' as imageUrl11
            , 'NULL' as imageUrl12
            , 'NULL' as imageUrl13
            , 'NULL' as imageUrl14
            , 'NULL' as imageUrl15
            , 'NULL' as imageUrl16
            , 'NULL' as imageUrl17
            , 'NULL' as imageUrl18
            , 'NULL' as imageUrl19
            , 'NULL' as imageUrl20

            , CASE
                WHEN (
                       (l.itemCode IS NULL OR COALESCE(l.lotNumber, '') = '') /* 新規は 2:販売終了で登録 */
                    OR cal.deliverycode_pre NOT IN ( :deliveryCodeReady, :deliveryCodeReadyPartially )
                ) THEN '2'
                ElSE '1'
              END AS saleStatus

            , '&#xE719;詳細情報'            AS detailTitle
            , '' AS detailDescription
            , '2'                             AS stockSegment
            , REPLACE(m.`横軸項目名`, ',', '') AS choicesStockHorizontalItemName
            , REPLACE(m.`縦軸項目名`, ',', '') AS choicesStockVerticalItemName
            , '1'                             AS displayChoicesStockSegment
            , ''                              AS displayChoicesStockThreshold
            
            , image.images
            , tp.front_title
            , tp.back_title
            , m.送料設定
            , m.一言ポイント
    
            , m.商品コメントPC
            , m.サイズについて
            , m.カラーについて
            , m.素材について
            , m.ブランドについて
            , m.使用上の注意
            , m.補足説明PC

            , m.B固有必要補足説明
            , m.必要補足説明
            , cal.outlet

          FROM tb_mainproducts m
          INNER JOIN tb_plusnaoproductdirectory d   ON m.`NEディレクトリID` = d.`NEディレクトリID`
          INNER JOIN tb_mainproducts_cal        cal ON m.`daihyo_syohin_code` = cal.`daihyo_syohin_code`
          INNER JOIN tb_title_parts             tp  ON m.daihyo_syohin_code = tp.daihyo_syohin_code
          INNER JOIN tb_biddersinfomation       i   ON m.`daihyo_syohin_code` = i.`daihyo_syohin_code`
          INNER JOIN (
            SELECT
                stock.itemCode
            FROM {$dbTmpName}.tmp_wowma_csv_stock stock
            WHERE stock.id >= :minId
              AND stock.id <= :maxId
              {$where}
            GROUP BY stock.itemCode
          ) stock ON m.daihyo_syohin_code = stock.itemCode

          LEFT JOIN tb_wowma_lot_number         l   ON m.daihyo_syohin_code = l.itemCode
          LEFT JOIN (
            SELECT
                i.daihyo_syohin_code
              , GROUP_CONCAT(
                  CONCAT(
                      i.code
                    , ':'
                    , i.directory
                    , '/'
                    , i.filename
                  )
                  ORDER BY i.code
                  SEPARATOR '\n'
                ) AS images
            FROM product_images i
            {$imageCodeListSql}
            GROUP BY i.daihyo_syohin_code
          ) AS image ON m.daihyo_syohin_code = image.daihyo_syohin_code
EOD;

        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
        $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
        $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);

        $stmt->bindValue(':minId', $border['min_id'], \PDO::PARAM_INT);
        $stmt->bindValue(':maxId', $border['max_id'], \PDO::PARAM_INT);
        foreach ($addParams as $k => $v) {
          $stmt->bindValue($k, $v, \PDO::PARAM_STR);
        }

        $stmt->execute();

        // 出力
        if ($stmt->rowCount()) {

          /** @var \Twig_Environment $twig */
          $twig = $this->getContainer()->get('twig');

          $templateDescription = $twig->load('BatchBundle:ExportCsvWowma:description.html.twig');
          $templateDescriptionPc = $twig->load('BatchBundle:ExportCsvWowma:description-pc.html.twig');
          $templateDescriptionSp = $twig->load('BatchBundle:ExportCsvWowma:description-sp.html.twig');
          $templateDetailDescription = $twig->load('BatchBundle:ExportCsvWowma:detail-description.html.twig');


          // モールデザイン取得
          $sql = <<<EOD
            SELECT
                md.code
              , md.design_html
            FROM tb_mall_design md
EOD;
            $tmp = $dbMain->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $mallDesigns = [];
            foreach ($tmp as $mallDesign) {
              $mallDesigns[$mallDesign['code']] = $mallDesign['design_html'];
            }

          // ヘッダ
          $headers = [
              'ctrlCol'
            , 'itemCode'
            , 'categoryId'
            , 'searchKeyword1'
            , 'searchKeyword2'
            , 'searchKeyword3'
            , 'taxSegment'
            , 'postageSegment'
            , 'postage'
            , 'deliveryMethodId1'
            , 'deliveryMethodId2'
            , 'deliveryMethodId3'
            , 'deliveryMethodId4'
            , 'deliveryMethodId5'
            , 'limitedOrderCount'
            , 'displayStockSegment'
            , 'itemPrice'
            , 'itemName'
            , 'description'
            , 'descriptionForSP'
            , 'descriptionForPC'
            , 'imageName1'
            , 'imageUrl1'
            , 'imageName2'
            , 'imageUrl2'
            , 'imageName3'
            , 'imageUrl3'
            , 'imageName4'
            , 'imageUrl4'
            , 'imageName5'
            , 'imageUrl5'
            , 'imageName6'
            , 'imageUrl6'
            , 'imageName7'
            , 'imageUrl7'
            , 'imageName8'
            , 'imageUrl8'
            , 'imageName9'
            , 'imageUrl9'
            , 'imageName10'
            , 'imageUrl10'
            , 'imageName11'
            , 'imageUrl11'
            , 'imageName12'
            , 'imageUrl12'
            , 'imageName13'
            , 'imageUrl13'
            , 'imageName14'
            , 'imageUrl14'
            , 'imageName15'
            , 'imageUrl15'
            , 'imageName16'
            , 'imageUrl16'
            , 'imageName17'
            , 'imageUrl17'
            , 'imageName18'
            , 'imageUrl18'
            , 'imageName19'
            , 'imageUrl19'
            , 'imageName20'
            , 'imageUrl20'
            , 'saleStatus'
            , 'detailTitle'
            , 'detailDescription'
            , 'stockSegment'
            , 'choicesStockHorizontalItemName'
            , 'choicesStockVerticalItemName'
            , 'displayChoicesStockSegment'
            , 'displayChoicesStockThreshold'
          ];
          $headerLine = $stringUtil->convertArrayToCsvLine($headers);
          $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";


          $filePath = sprintf('%s/item-%02d.csv', $this->exportPath, ($index + 1));
          $files[$index] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
          $logger->info('csv output: ' . $filePath);

          while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // HTML作成、画像URL作成、各種変換処理
            $this->convertCsvContents($row, [
                'templateDescription' => $templateDescription
              , 'templateDescriptionPc' => $templateDescriptionPc
              , 'templateDescriptionSp' => $templateDescriptionSp
              , 'templateDetailDescription' => $templateDetailDescription
              , 'mallDesigns' => $mallDesigns
            ]);

            $line = $stringUtil->convertArrayToCsvLine($row, $headers);
            $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
            fputs($fp, $line);

            $num++;
          }

          fclose($fp);
        }
      }

      $this->results['item.csv'] = $files;
      $logger->info(sprintf("Wowma CSV出力 item.csv: $num 件 / ファイル数: %d", count($files)));

    } else {
      $logger->info("Wowma CSV出力 item.csv: 件数が0のためファイルは作成しませんでした。");
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));


    // '====================
    // 'delete.csv
    // '====================
    $logTitle = 'エクスポート(delete.csv)';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 念のため、一時テーブルの件数チェック。少なすぎる場合には何らかのイレギュラーとして出力しない。
    $remainCount = intval($dbMain->query("SELECT COUNT(*) FROM {$dbTmpName}.tmp_wowma_csv_stock")->fetchColumn(0));

    if (!$this->doCreateCsv) {
      $logger->info("Wowma CSV出力 delete.csv: CSV作成フラグOFFのため、ファイルは作成しませんでした。");

    } else if ($remainCount <= 10000) {
      $logger->warning('出力対象が少なすぎるため、削除CSVの出力を中止します。');

    } else {

      // 削除条件に合致しないものは、データを更新しないだけでモール側データは残す
      $sql = <<<EOD
        SELECT
            l.lotNumber
          , l.itemCode
        FROM tb_wowma_lot_number l
        LEFT JOIN tb_biddersinfomation i ON l.itemCode = i.daihyo_syohin_code
        LEFT JOIN tb_mainproducts_cal cal ON cal.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN (
          SELECT
             s.itemCode
          FROM {$dbTmpName}.tmp_wowma_csv_stock s
          GROUP BY s.itemCode
        ) T ON l.itemCode = T.itemCode
        WHERE T.itemCode IS NULL                                -- tmp_wowma_csv_stockに存在するものは常に削除対象にはしない
          AND (
            i.registration_flg = 0                         -- データがないなかで、出品OFFのものは削除
            OR cal.adult_check_status = :adultCheckStatusNone     -- アダルトチェックが未審査のものも削除
            OR i.daihyo_syohin_code IS NULL                -- informationテーブルにデータがないものも削除
          )
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
      $stmt->execute();

      $files = [];
      $fp = null;
      $num = 0;
      $lineNum = 0;
      $index = 1;
      $quotedDelCodeList = array(); // 削除した代表商品は、informationテーブルのdeliverycodeも削除する必要がある

      if ($stmt->rowCount()) {

        // ヘッダ
        $headers = [
            'ctrlCol'
          , 'lotNumber'
          , 'itemCode'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

          if (!isset($fp)) {
            $filePath = sprintf('%s/delete-%02d.csv', $this->exportPath, $index++);
            $files[$index] = $filePath;
            $fp = fopen($filePath, 'wb');
            fputs($fp, $headerLine);
            $logger->info('csv output: ' . $filePath);
          }

          $row['ctrlCol'] = 'D';
          $quotedDelCodeList[] = "'" . $row['itemCode'] . "'";

          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          if (++$lineNum >= self::UPLOAD_CSV_MAX_NUM) {
            fclose($fp);
            unset($fp);
            $lineNum = 0;
          }
        }

        $this->results['delete.csv'] = $files;
        $logger->info(sprintf("Wowma CSV出力 delete.csv: " . $stmt->rowCount() . " 件 / ファイル数: %d", count($files)));

      } else {
        $logger->info("Wowma CSV出力 delete.csv: 件数が0のためファイルは作成しませんでした。");
      }
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));


    // 削除ファイルがあれば先にアップロード。
    if ($this->doUploadCsv) {
      foreach ($this->results['delete.csv'] as $filePath) {
        $processor->enqueueUploadCsv($filePath, 'item.csv', $this->getEnvironment(), self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    }

    // zipファイル作成
    $zipFiles = [];
    if (
            $this->results['item.csv']
         && $this->results['stock.csv']
         && count($this->results['item.csv']) == count($this->results['stock.csv'])
    ) {

      foreach($this->results['item.csv'] as $index => $item) {
        $stock = $this->results['stock.csv'][$index];

        $zip = new \ZipArchive();
        $zipFilePath = sprintf('%s/item_stock.%02d.zip', $this->exportPath, $index);

        if (!$zip->open($zipFilePath, \ZipArchive::CREATE)) {
          throw new \RuntimeException('can not create image zip file. aborted. [' . $zipFilePath . ']');
        }

        $zip->addFile($item, basename('item.csv'));
        $zip->addFile($stock, basename('stock.csv'));

        $zip->close();
        unset($zip);

        $zipFiles[] = $zipFilePath;
      }

      // アップロード
      if ($this->doUploadCsv) {
        // 登録・更新 zipファイル
        foreach($zipFiles as $zipFilePath) {
          $processor->enqueueUploadCsv($zipFilePath, 'item_stock.zip', $this->getEnvironment(), self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }
    } else if ($this->results['item.csv'] || $this->results['stock.csv'] ) {
      throw new \RuntimeException('item, select のファイルのどちらかがありません。アップロードはされません。');
    } else {
      // 両方ないのならスルー
      $logger->info('no data.');
    }

    if ($this->doUploadCsv) {
      // 今回item.csvとdelete.csvに載せた商品について、今回のdeiverycodeを tb_biddersinfomationに反映
      // 全件出力と販売中のみでWhere句が変わる
      $where = '';
      if ($this->biddersProductExportAllFlg != 1) {
        $where = 'WHERE stock.export_only_sale_flg = 1';
      }

      $sql = <<<EOD
      UPDATE tb_biddersinfomation i
      JOIN (
        SELECT DISTINCT itemCode, current_deliverycode
        FROM {$dbTmpName}.tmp_wowma_csv_stock stock
        {$where}
      ) stock
      ON i.daihyo_syohin_code = stock.itemCode
      SET i.last_deliverycode = stock.current_deliverycode
EOD;
      $dbMain->exec($sql);
      if ($quotedDelCodeList) {
        $delCodeStr = implode(', ', $quotedDelCodeList);
        $sql = <<<EOD
        UPDATE tb_biddersinfomation i
        SET i.last_deliverycode = NULL
        WHERE daihyo_syohin_code IN ( {$delCodeStr} );
EOD;
        $dbMain->exec($sql);
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, 'エクスポート', '終了'));
  }

  /**
   * 売価単価が0の商品がないかチェックし、あればエラー処理を行います。
   * （共通処理の後なので、このタイミングでは販売可能で0円のものはないはずです）
   */
  private function check0priceProduct() {

    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    // baika_tankaが0のため、出力対象から外された商品があればこのタイミングでエラー送信
    // （旧価格のままWowma側に残るので、チェックが必要な可能性がある）
    $sql = <<<EOD
    SELECT
      cal.daihyo_syohin_code
      , m.販売開始日 as sales_start_date
      , cal.endofavailability
      , CASE cal.deliverycode_pre
          WHEN 0 THEN '即納・一部即納'
          WHEN 1 THEN '一部即納'
          WHEN 2 THEN '受発注のみ'
          WHEN 3 THEN '販売終了'
        END AS deliverycode
    FROM tb_biddersinfomation i
    INNER JOIN tb_mainproducts_cal cal ON i.`daihyo_syohin_code` = cal.`daihyo_syohin_code`
    INNER JOIN tb_mainproducts m ON i.`daihyo_syohin_code` = m.`daihyo_syohin_code`
    INNER JOIN tb_wowma_lot_number l ON i.`daihyo_syohin_code` = l.itemCode
    WHERE
    cal.deliverycode_pre <> :deliveryCodeTemporary
    AND (
        cal.endofavailability > DATE_ADD(CURRENT_DATE, INTERVAL - 10 DAY)
        OR cal.endofavailability IS NULL
        )
        AND i.registration_flg <> 0
        AND (
            cal.adult_check_status <> :adultCheckStatusNone
            )
            AND COALESCE(i.`baika_tanka`, 0) = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $stmt->execute();

    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $products = array();
    if ($list) {
      foreach ($list as $product) {
        $data[] = $product['daihyo_syohin_code'] . ' 販売開始[' . $product['sales_start_date'] . ']-販売終了[' . $product['endofavailability'] . '] ' . $product['deliverycode'];
      }
      $datas = implode('\n', $data);
      $message = <<<EOD
販売可能、あるいはWowma側で販売中の可能性がある、単価0円の商品がありました。

共通処理で価格設定されるため、通常このタイミングでは0円商品は発生しません。
0円商品はWowmaに同期しないため、既にWowma側にデータがある場合、古い内容がそのまま残ります。
以下の商品の状態が問題ないか、確認をお願いします。

----------------------------------------------------
{$datas}
----------------------------------------------------
EOD;

      $logger->addDbLog(
          $logger->makeDbLog(null, 'Wowma 商品売価単価チェック', '単価0円商品')->setInformation($message)
          , true, 'Wowma CSV出力処理中に、単価0円商品がありました。', 'error'
          );
    }
    $logger->debug("0円出力チェック完了");
  }


  /**
   * CSV出力内容 作成処理
   * @param array &$row
   * @param array $options
   */
  private function convertCsvContents(&$row, $options)
  {
    $tmp = explode("\n", $row['images']);
    $images = [];
    $num = 1;
    foreach($tmp as $image) {
      if (strpos($image, ':') !== false) {
        list($code, $dirFile) = explode(':', $image);
        if ($code && $dirFile) {
          $images[$code] = $dirFile;
          $num++;
        }
      }
    }

    $data = [
        'row' => $row
      , 'images' => $images
      , 'mallDesigns' => $options['mallDesigns']
    ];

    // HTML作成：商品説明（共通）
    $row['description'] = trim($options['templateDescription']->render($data));

    // HTML作成：PC用商品説明
    $row['descriptionForPC'] = $options['templateDescriptionPc']->render($data);

    // HTML作成：SP用商品説明
    $row['descriptionForSP'] = $options['templateDescriptionSp']->render($data);

    // HTML作成：商品詳細説明
    $row['detailDescription'] = trim($options['templateDetailDescription']->render($data));


    // 商品画像URL
    $i = 1;
    foreach($images as $code => $dirPath) {
      $key = sprintf('imageUrl%d', $i++);
      if (strlen($dirPath)) {
        $row[$key] = sprintf('%s%s', WowmaMallProcess::IMAGE_URL, $dirPath);
      }
    }

  }


  /**
   * 更新画像 ZIPファイル作成
   * @param $temporaryTableName
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  private function createImageUpdateZip($temporaryTableName)
  {
    /** @var ProductImagesRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');

    $zipFiles = [];
    $totalSize = 0;
    $num = 0;

    $logger = $this->getLogger();

    $logger->info('zip : fetch image files');

    $updateImages = $this->findWowmaNewImages($temporaryTableName);

    if (count($updateImages)) {
      $fileNameTime = new \DateTime();
      $limitSize = 1800000000; // 2GB 余裕を見て 1800MiB (-200MiB)で制限
      $currentSize = 0;
      $fileNameIndex = 0;

      $fs = new FileSystem();
      $distDir = sprintf('%s/PIC', $this->exportPath);
      if (!$fs->exists($distDir)) {
        $fs->mkdir($distDir);
      }

      $imageDir = $this->getContainer()->getParameter('product_image_dir');

      foreach ($updateImages as $image) {

        $filePath = sprintf('%s/%s', $imageDir, $image->getFileDirPath());
        $file = new \SplFileInfo($filePath);
        // 画像がなければスキップ
        if (!$file->isFile()) {
          continue;
        }

        if (!isset($zip)) {
          $fileNameIndex++;

          $zip = new \ZipArchive();
          $fileName = sprintf('%s/img%03d.zip', $distDir, $fileNameIndex);
          if (!$zip->open($fileName, \ZipArchive::CREATE)) {
            throw new \RuntimeException('can not create image zip file. aborted. [' . $fileName . ']');
          }

          $zipFiles[] = $fileName;
        }

        $zip->addFile($file->getPathname(), $image->getFileDirPath());
        $currentSize += $file->getSize();
        $totalSize += $file->getSize();
        $num++;

        // 閉じてオブジェクト削除（次のファイル作成の判定のため）
        if ($currentSize >= $limitSize) {
          $zip->close();
          $currentSize = 0;
          unset($zip);
        }
      }

      // 最後のファイルを閉じる
      if (isset($zip)) {
        $zip->close();
      }
    }

    $result = [
        '圧縮ファイル' => $zipFiles
      , '画像ファイル数' => $num
      , '画像サイズ' => $totalSize
    ];

    return $result;
  }



  /**
   * 画像ファイル収集＆アップロード処理。
   * Wowma側でファイルが読み込まれないエラーがあったため、
   * 1ファイルが10分間待機しても処理されなかった場合、または画像全体で1時間経過した場合、処理を中断する
   * 
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  private function uploadImage()
  {
    $logger = $this->getLogger();

    $logger->addDbLog($logger->makeDbLog(null, '画像ファイル処理', '開始'));
    $logger->addDbLog($logger->makeDbLog(null, '画像ファイル処理', '収集', '開始'));

    // 画像取得開始を最終更新日時とする。
    $now = new \DateTime();
    $limit = new \DateTime(); // 複数ファイルある場合の待機時間
    $limit->modify("+ 60 minute"); // 指定時間で諦める

    $temporaryTableName = 'tmp_wowma_csv_stock';
    $zipFiles = $this->createImageUpdateZip($temporaryTableName);

    $logger->addDbLog($logger->makeDbLog(null, '画像ファイル処理', '収集', '終了'));

    // アップロード処理
    if ($this->doUploadImage) {
      $logger->addDbLog($logger->makeDbLog(null, '画像ファイル処理', 'アップロード', '開始'));
      if ($zipFiles && isset($zipFiles['圧縮ファイル']) && count($zipFiles['圧縮ファイル']) > 0) {

        /** @var WowmaMallProcess $processor */
        $processor = $this->getContainer()->get('batch.mall_process.wowma');

        $ftpConfig = $this->getContainer()->getParameter('ftp_wowma');
        $config = $ftpConfig['image_upload'];
        $commonUtil = $this->getDbCommonUtil();
        $config['user'] = $commonUtil->getSettingValue(TbSetting::KEY_WOWMA_FTP_USER);
        $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_WOWMA_FTP_PASSWORD);

        foreach($zipFiles['圧縮ファイル'] as $fileName) {
          // 同名のファイルがあれば待つ（前日から残り続けた場合のみ）
          $processor->uploadCsv($config, $fileName, basename($fileName), true, 10);
          if ($limit < new \DateTime()) {
            $message = 'Wowmaの画像アップロード処理中、待機時間が基準を超えました。処理を終了します';
            throw new \RuntimeException($message);
          }
        }

        // 画像最終更新日時 更新
        $this->updateWowmaNewImagesUpdateDate($now, $temporaryTableName);
      }

      $logger->addDbLog($logger->makeDbLog(null, '画像ファイル処理', 'アップロード', '終了'));
    }

    $logger->addDbLog($logger->makeDbLog(null, '画像ファイル処理', '終了'));
  }


  /**
   * Wowma アップロード対象 画像コード一覧取得
   */
  private function getWowmaImageCodeList()
  {
    $codeList = [];
    for ($i = 1; $i <= 20; $i++) { // p1 ～ p20 まで
      $codeList[] = sprintf('p%03d', $i);
    }

    return $codeList;
  }

  /**
   * @param string $temporaryTableName
   * @return ProductImages[]
   */
  private function findWowmaNewImages($temporaryTableName)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $dbTmp = $this->getDb('tmp');
    $dbTmpName = $dbTmp->getDatabase();

    $codeList = $this->getWowmaImageCodeList();
    foreach($codeList as $i => $code) {
      $codeList[$i] = $dbMain->quote($code, \PDO::PARAM_STR);
    }

    $codeListStr = implode(', ', $codeList);

    $temporaryTableNameQuoted = $dbMain->quoteIdentifier($temporaryTableName);

    // 画像一覧取得
    $sql = <<<EOD
      SELECT
        pi.*
      FROM product_images pi
      INNER JOIN tb_biddersinfomation i ON pi.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN (
        SELECT
          t.itemCode
        FROM {$dbTmpName}.{$temporaryTableNameQuoted} t
        GROUP BY t.itemCode
      ) T ON pi.daihyo_syohin_code = T.itemCode
      WHERE pi.code IN ( {$codeListStr} )
        AND (
             i.last_image_upload_datetime IS NULL
          OR pi.updated > i.last_image_upload_datetime
        )
      ORDER BY pi.daihyo_syohin_code , pi.code
EOD;

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');

    $rsm = new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:ProductImages', 'pi');

    $query = $em->createNativeQuery($sql, $rsm);

    return $query->getResult();
  }

  /**
   * @param \DateTimeInterface $now
   * @param string $temporaryTableName
   * @return void
   * @throws \Doctrine\DBAL\DBALException
   */
  private function updateWowmaNewImagesUpdateDate($now, $temporaryTableName)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $dbTmp = $this->getDb('tmp');
    $dbTmpName = $dbTmp->getDatabase();
    $logger = $this->getLogger();

    $codeList = $this->getWowmaImageCodeList();
    foreach($codeList as $i => $code) {
      $codeList[$i] = $dbMain->quote($code, \PDO::PARAM_STR);
    }
    $codeListStr = implode(', ', $codeList);

    $temporaryTableNameQuoted = $dbMain->quoteIdentifier($temporaryTableName);

    $sql = <<<EOD
      UPDATE tb_biddersinfomation i
      INNER JOIN product_images pi ON i.daihyo_syohin_code = pi.daihyo_syohin_code
      INNER JOIN (
        SELECT
          t.itemCode
        FROM {$dbTmpName}.{$temporaryTableNameQuoted} t
        GROUP BY t.itemCode
      ) T ON pi.daihyo_syohin_code = T.itemCode
      SET i.last_image_upload_datetime = :now
      WHERE pi.code IN ( {$codeListStr} )
        AND (
             i.last_image_upload_datetime IS NULL
          OR pi.updated > i.last_image_upload_datetime
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':now', $now->format('Y-m-d H:i:s'));
    $stmt->execute();
  }




}


