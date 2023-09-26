<?php
/**
 * Yahoo CSV出力処理
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;
use MiscBundle\Entity\TbShippingdivision;

class ExportCsvYahooCommand extends PlusnaoBaseCommand
{
  private $exportParentPath;
  private $exportPath;

  /**
   * ファイル名に付与する日時。連番にするために必要っぽい
   * @see http://www.realmax.co.jp/system/e-commerce/post-12302/）
   * @var  \DateTime
   */
  private $fileTimeStamp;

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM_DATA_ADD = 20000; // 2万件で分割（data_add。data_delはほとんどデータがないため現時点では見送り。10万を超えるときはひとまず手動分割で対応）
  const UPLOAD_CSV_MAX_NUM_DATA_QUANTITY = 100000; // 10万件で分割（quantity）
  const QUANTITY_MAX = 9999; // quantity 数量 最大値（NextEngineとの自動連携の数値に合わせたもの）

  const EXPORT_TARGET_PLUSNAO = 'plusnao';
  const EXPORT_TARGET_KAWAEMON = 'kawaemon';

  /** Yahoo在庫管理テーブル：plusnao Yahoo側からAPIでダウンロードしたデータを保存する。元ファイル名はquantity.csv */
  const PRODUCT_STOCK_TABLE_PLUSNAO = 'tb_yahoo_product_stock';
  /** Yahoo在庫管理テーブル：kawaemon Yahoo側からAPIでダウンロードしたデータを保存する。元ファイル名はquantity.csv */
  const PRODUCT_STOCK_TABLE_KAWAEMON = 'tb_yahoo_kawa_product_stock';
  
  /** Yahoo在庫管理テーブル：plusnao Yahoo側からAPIでダウンロードしたデータを保存する。tmpデータベース。元ファイル名はdata.csv */
  const PRODUCT_DATA_TABLE_PLUSNAO = 'tb_yahoo_product_data_dl';
  /** Yahoo在庫管理テーブル：kawaemon Yahoo側からAPIでダウンロードしたデータを保存する。tmpデータベース。元ファイル名はdata.csv */
  const PRODUCT_DATA_TABLE_KAWAEMON = 'tb_yahoo_kawa_product_data_dl';

  // ブランドコード。paypayモールで使用

  /** ブランドコード：ノンブランド */
  const BRAND_CODE_NONBRAND = '38074';
  /** ブランドコード：plusnao */
  const BRAND_CODE_PLUSNAO = '51926';
  
  /** 発送日情報管理番号： デフォルト（3営業日以内） */
  const READ_TIME_INSTOCK_DEFAULT = 1;
  /** 発送日情報管理番号： 優良配送用（翌日発送）  */
  const READ_TIME_INSTOCK_GOODDELIVERY = 2;
  
  /** 配送グループ管理番号： デフォルト（配送方法なし） */
  const POSTAGE_SET_DEFAULT = 1;
  /** 配送グループ管理番号： 宅配便 */
  const POSTAGE_SET_TAKUHAIBIN = 2;
  /** 配送グループ管理番号： ゆうパケット */
  const POSTAGE_SET_YUUPACKET = 3;
  /** 配送グループ管理番号： 定形外 */
  const POSTAGE_SET_TEIKEIGAI = 4;

  public static $EXPORT_TARGET_LIST = [
      self::EXPORT_TARGET_PLUSNAO
    , self::EXPORT_TARGET_KAWAEMON
  ];

  public static $EXPORT_TARGET_MALL_CODE = [
      self::EXPORT_TARGET_PLUSNAO => DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO
    , self::EXPORT_TARGET_KAWAEMON => DbCommonUtil::MALL_CODE_KAWA_YAHOO
  ];


  const DOWNLOADED_DIR = 'Yahoo/Import';

  private $countDataAdd = 0; // プロセス履歴用。dataAdd件数。plusnaoとkawaemon同時実行された場合合計を出せるように別に保持
  private $countQuantity = 0; // プロセス履歴用。dataQuantity件数。plusnaoとkawaemon同時実行された場合合計を出せるように別に保持

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-yahoo')
      ->setDescription('CSVエクスポート Yahoo')
      ->addArgument('export-dir', InputArgument::REQUIRED, '出力先ディレクトリ', null)
      ->addOption('export-target', null, InputOption::VALUE_REQUIRED, '出力対象(コンマ区切り)。(plusnao|kawaemon)', NULL)
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('import-path', null, InputOption::VALUE_OPTIONAL, 'インポートファイルパス', null)
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'YahooCSV出力処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    // 親ディレクトリ
    $this->exportParentPath = $input->getArgument('export-dir');

    if (!strlen($this->exportParentPath)) {
      throw new RuntimeException('export path is not determined. (Yahoo CSV)');
    }

    // なければエラー終了
    $fs = new FileSystem();
    if (!$fs->exists($this->exportParentPath)) {
      throw new RuntimeException('export path is not exists. (Yahoo CSV). [' . $this->exportParentPath . ']');
    }

    // 対象モールごとに処理
    $exportTargetList = $input->getOption('export-target')
      ? explode(',', $input->getOption('export-target'))
      : null;

    if (!$exportTargetList) {
      throw new RuntimeException('CSV出力対象が選択されていません。(plusnao|kawaemon)');
    }
    $logger->info('ヤフーCSV出力対象: ' . implode(', ', $exportTargetList));

    $fileUtil = $this->getFileUtil();

    $dbMain = $this->getDb('main');

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    // モール別ループ
    foreach ($exportTargetList as $currentTarget) {

      $this->stopwatch->start($currentTarget);

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0
      $logExecTitle = sprintf('ヤフーCSV出力処理[%s][共通処理]', $currentTarget);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '開始'));

      $mallCode = null;
      switch($currentTarget) {
        case self::EXPORT_TARGET_PLUSNAO:
          $mallCode = DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO;
          break;
        case self::EXPORT_TARGET_KAWAEMON:
          $mallCode = DbCommonUtil::MALL_CODE_KAWA_YAHOO;
          break;
      }
      $rakutenCsvOutputDir = $fileUtil->getWebCsvDir() . '/RakutenNokiKanri';
      $commonUtil->exportCsvCommonProcess($logger, $rakutenCsvOutputDir);
      $commonUtil->calculateMallPrice($logger, $mallCode);

      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '終了'));
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // ログ出力 EXEC_TITLE 切り替え
      // plusnao_yahoo : [plusnao]
      // kawa_yahoo : [kawaemon]
      $logExecTitle = sprintf('ヤフーCSV出力処理[%s]', $currentTarget);
      $logger->initLogTimer(true);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '開始'));

      // 出力ディレクトリ 作成
      $this->exportPath = sprintf('%s/%s', $this->exportParentPath, $currentTarget);
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $fs->mkdir($this->exportPath, 0755);
      }

      try {
        // CSV出力 データ作成処理 実装

        // '====================
        // Call YAHOOディレクトリID補正___
        // '未入力のYAHOOディレクトリIDにデフォルトのIDを設定
        // '====================
        $sql = <<<EOD
        UPDATE tb_mainproducts AS M
        INNER JOIN tb_plusnaoproductdirectory AS PD ON M.NEディレクトリID = PD.NEディレクトリID
        SET M.YAHOOディレクトリID = PD.YAHOOディレクトリID
        WHERE COALESCE(M.YAHOOディレクトリID, '') = ''
          AND COALESCE(PD.YAHOOディレクトリID, '') <> ''
EOD;
        $dbMain->query($sql);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('YAHOOディレクトリID補正', $currentTarget));

        // $logTitle = 'ヤフーCSV出力処理';

        // '====================
        // Call Prepare___(lc_kawaemon)
        // '====================
        // Call setYahooInformation___(lc_kawaemon)
        $this->updateYahooInformation($currentTarget);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('updateYahooInformation', $currentTarget));

        // Call prepareData___(lc_kawaemon)
        $this->prepareData($currentTarget);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('prepareData', $currentTarget));

        // Call prepareStock___(lc_kawaemon)
        $this->prepareStock($currentTarget);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('prepareStock', $currentTarget));
        
        // 商品データ取込
        $this->importProductData($currentTarget, $input->getOption('import-path') . '/data.csv');
        $logger->debug($this->getLapTimeAndMemory('importProductData', $currentTarget));
        
        // '====================
        // Call Export___(lc_export_dir, lc_kawaemon)
        // '====================
        $this->export($currentTarget, $input->getOption('import-path'));
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('export', $currentTarget));

        // '====================
        // Call NE更新カラムリセット
        // ここだけ、帳尻（処理が終わったらリセットされている、という出口）を合わせるため残す。
        // '====================
        $commonUtil->resetNextEngineUpdateColumn($logger);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('resetNextEngineUpdateColumn', $currentTarget));


        $finder = new Finder(); // 結果ファイル確認
        $message = '';
        $fileNum = $finder->in($this->exportPath)->files()->count();
        if (!$fileNum) {
          $message = 'CSVファイルが作成されませんでした。処理を完了します。';
        }

        $this->processExecuteLog->setProcessNumber1($this->countDataAdd);
        $this->processExecuteLog->setProcessNumber2($this->countQuantity);
        $this->processExecuteLog->setVersion(1.1);

        $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '終了')->setInformation($message));
        $logger->logTimerFlush();

        $event = $this->stopwatch->stop($currentTarget);
        $logger->info(sprintf('%s: duration: %.02f / memory: %s', $currentTarget, $event->getDuration() / 1000000, number_format($event->getMemory())));

      } catch (\Exception $e) {

        $event = $this->stopwatch->stop('main');

        // 出力ディレクトリが空なら削除しておく
        $fs = new Filesystem();
        if ($this->exportPath && $fs->exists($this->exportPath)) {

          // 画像ディレクトリ
          if ($fs->exists($this->exportPath . '/PIC')) {
            $finder = new Finder();
            if ($finder->in($this->exportPath . '/PIC')->files()->count() === 0) {
              $fs->remove($this->exportPath . '/PIC');
            }
          }

          $finder = new Finder();
          if ($finder->in($this->exportPath)->count() == 0) {
            $fs->remove($this->exportPath);
          }
        }
        throw $e;
      }
    }

    $logger->info('Yahoo CSV Export 完了');
    $event = $this->stopwatch->stop('main');
    $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));
  }


  /**
   * CSV出力処理
   * @param string $exportTarget
   * @param string $importCsvPath
   * @throws \Doctrine\DBAL\DBALException
     */
  private function export($exportTarget, $importCsvPath)
  {
    $logger = $this->getLogger();
    $db = $this->getDb('main');

    $logTitle = 'ヤフーCSV出力処理';
    $subTitle = 'Export___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $result = [
        'data_del.csv' => null
      , 'data_add.csv' => null
      , 'quantity.csv' => null
    ];

    $this->fileTimeStamp = new \DateTime();
    $this->fileTimeStamp->modify('+20 minutes'); // 画像チェックが20分程度で終わってくれることを期待

    // '====================
    // 'data_add.csv
    // '====================
    $logger->info('data_add.csv 作成中');
    
    $commonUtil = $this->getDbCommonUtil();
    
    // 差分チェック：　アップロード対象外をOFF
    if (!$commonUtil->getSettingValue('YAHOO_PRODUCT_EXPORT_ALL')) {
      $this->updateProductOutputFlg($exportTarget);
    }

    // 画像用設定値

    // plusnao, kawaemon とも同じテーブルからの出力だが、中身は個別に作成されているため違う。
    $sql = <<<EOD
      SELECT
          a.path
        , a.name
        , a.code
        , a.`sub-code`
        , a.`original-price`
        , a.price
        , a.`sale-price`
        , a.options
        , a.headline
        , a.caption
        , a.abstract
        , a.explanation
        , a.additional1
        , a.additional2
        , a.additional3
        , a.`relevant-links`
        , a.`ship-weight`
        , a.taxable
        , a.`release-date`
        , a.`temporary-point-term`
        , a.`point-code`
        , a.`meta-key`
        , a.`meta-desc`
        , a.template
        , a.`sale-period-start`
        , a.`sale-period-end`
        , a.`sale-limit`
        , a.`sp-code`
        , a.`brand-code`
        , a.`person-code`
        , a.`yahoo-product-code`
        , a.`product-code`
        , a.jan
        , a.isbn
        , a.delivery
        , a.`astk-code`
        , a.condition
        , a.taojapan
        , a.`product-category`
        , a.spec1
        , a.spec2
        , a.spec3
        , a.spec4
        , a.spec5
        , a.display
        , a.sort as `sort_priority`
        , a.`sp-additional`
        , a.`lead-time-instock`
        , a.`pr-rate`
        , a.`postage-set`
        , 2 as `supplier-type`
        , 1 as `y-shopping-display-flag`
      FROM tb_yahoo_data_add a
      WHERE output_flg = 1
        AND COALESCE(a.price, 0) > 0
      ORDER BY a.code
EOD;
    $stmt = $db->query($sql);

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
          'path'
        , 'name'
        , 'code'
        , 'sub-code'
        , 'original-price'
        , 'price'
        , 'sale-price'
        , 'options'
        , 'headline'
        , 'caption'
        , 'abstract'
        , 'explanation'
        , 'additional1'
        , 'additional2'
        , 'additional3'
        , 'relevant-links'
        , 'ship-weight'
        , 'taxable'
        , 'release-date'
        , 'temporary-point-term'
        , 'point-code'
        , 'meta-key'
        , 'meta-desc'
        , 'template'
        , 'sale-period-start'
        , 'sale-period-end'
        , 'sale-limit'
        , 'sp-code'
        , 'brand-code'
        , 'person-code'
        , 'yahoo-product-code'
        , 'product-code'
        , 'jan'
        , 'isbn'
        , 'delivery'
        , 'astk-code'
        , 'condition'
        , 'taojapan'
        , 'product-category'
        , 'spec1'
        , 'spec2'
        , 'spec3'
        , 'spec4'
        , 'spec5'
        , 'display'
        , 'sort_priority'
        , 'sp-additional'
        , 'lead-time-instock'
        , 'pr-rate'
        , 'postage-set'
      ];

      // PayPay対応
      if($exportTarget === self::EXPORT_TARGET_PLUSNAO){
        $headers[] = 'supplier-type';
        $headers[] = 'y-shopping-display-flag';
      }

      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // なぜかダブルクォートで囲まないフィールド ※ Access出力との一致確認用。本番ではすべて囲っておいた方が無難かも。
      $noEncloseFields = [
          'original-price'
        , 'price'
        , 'sale-price'
        , 'additional1'
        , 'additional2'
        , 'additional3'
        , 'relevant-links'
        , 'release-date'
        , 'temporary-point-term'
        , 'point-code'
        , 'sale-period-start'
        , 'sale-period-end'
        , 'sale-limit'
        , 'sp-code'
        , 'brand-code'
        , 'person-code'
        , 'yahoo-product-code'
        , 'product-code'
        , 'jan'
        , 'isbn'
      ];

      // データ
      $num = 0;
      $count = 0;
      // このファイルは10万件で分割する。
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/data_add%s%02d00.csv', $this->exportPath, $this->fileTimeStamp->format('YmdH'), intval($this->fileTimeStamp->format('i')) + $fileIndex++);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        // 出力時データ加工

        // 文字数制限対応
        //'厳密にバイト数で切り捨ててアップロードしたが警告が発生したためザックリと文字数で切り捨てるように変更する
        //'そうなるともはやローカルで実行する意味はないのでサーバーで実行する
        //'出荷予定文言（backtitle）を付加してから切り捨てるとNGだったが、現在は問題ないためこのままここで。
        $row['explanation'] = mb_substr($row['explanation'], 0, 499, 'UTF-8');
        $row['caption'] = mb_substr($row['caption'], 0, 4999, 'UTF-8');
        $row['headline'] = mb_substr($row['name'], 0, 30, 'UTF-8');
        // 出力
        $line = $stringUtil->convertArrayToCsvLine($row, $headers, $noEncloseFields);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;

        if ($count >= self::UPLOAD_CSV_MAX_NUM_DATA_ADD) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      fclose($fp);

      $result['data_add.csv'] = $num;
      $logger->info("Yahoo CSV出力 data_add.csv: $num 件");

      $this->countDataAdd += $num;

    } else {
      $logger->info("Yahoo CSV出力 data_add.csv: 件数が0のためファイルは作成しませんでした。");
    }

    // '====================
    // 'quantity.csv
    // '====================
    // plusnao, kawaemon とも同じテーブルからの出力だが、中身は個別に作成されているため違う。
    $sql = <<<EOD
      SELECT
          a.code
        , a.`sub-code`
        , a.quantity
      FROM tb_yahoo_quantity_add a
      ORDER BY
          a.code
        , a.`sub-code`
EOD;
    $stmt = $db->query($sql);

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
          'code'
        , 'sub-code'
        , 'quantity'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // このファイルは10万件で分割する。
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/quantity%s%02d00.csv', $this->exportPath, $this->fileTimeStamp->format('YmdH'), intval($this->fileTimeStamp->format('i')) + $fileIndex++);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM_DATA_QUANTITY) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $result['quantity.csv'] = $num;
      $logger->info("Yahoo CSV出力 quantity.csv: $num 件");

      $this->countQuantity += $num;

    } else {
      $logger->info("Yahoo CSV出力 quantity.csv: 件数が0のためファイルは作成しませんでした。");
    }

    // '====================
    // 'data_del.csv
    // '====================
    //'2014.01.30 削除せずに非表示にするよう仕様変更。エクスポートしない
    //'2015.06.18 もはやAccessに存在しない商品をエクスポート対象とするように変更
    // 2015.12.09 仕様：Accessに存在しない、またはモール別情報「出品フラグ」（registration_flg）がOFFのものは削除
    //            仕様：削除対象外商品コードは除外 (tb_delete_excluded_products.syohin_code)
    // 2016.09.06 仕様：出力対象外は全て削除( #8327 )
    $fs = new FileSystem();
    $quantityCsvPath = '';
    if ($importCsvPath) {
      $quantityCsvPath = "$importCsvPath/quantity.csv";
    }
    if ($quantityCsvPath && $fs->exists($quantityCsvPath)) {
      $logger->info('data_del.csv 作成中');
      $logger->info('Yahoo在庫データインポート');

      // 在庫データ取込
      $commonUtil = $this->getDbCommonUtil();
      $mallCode = self::$EXPORT_TARGET_MALL_CODE[$exportTarget];
      $mallId = $commonUtil->getMallIdByMallCode($mallCode);
      $productStockTable = $commonUtil->getYahooProductStockTableName($exportTarget);
      $db->query("TRUNCATE {$productStockTable}");
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFilePath
        INTO TABLE {$productStockTable}
        FIELDS TERMINATED BY ',' ENCLOSED BY '' ESCAPED BY ''
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
        (`code`, `sub-code`, `quantity`)
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':importFilePath', $quantityCsvPath);
      $stmt->execute();
      
      $sql = <<<EOD
        SELECT
          DISTINCT dl.code
        FROM {$productStockTable} dl
        LEFT JOIN tb_yahoo_data_add a ON dl.code = a.code
        WHERE a.code IS NULL
          AND NOT EXISTS (
              SELECT * FROM tb_delete_excluded_products dep
              WHERE dep.mall_id = :mallId
                AND dep.syohin_code = dl.code
          )
        ORDER BY dl.code
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':mallId', $mallId, \PDO::PARAM_INT);
      $stmt->execute();

      // 出力
      if ($stmt->rowCount()) {
        // ヘッダ
        $headers = [
          'code'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        // データ
        $num = 0;
        $filePath = sprintf('%s/data_del%s.csv', $this->exportPath, $this->fileTimeStamp->format('YmdHi00'));
        $fp = fopen($filePath, 'wb');
        fputs($fp, $headerLine);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          $num++;
        }
        fclose($fp);

        $result['data_del.csv'] = $num;
        $logger->info("Yahoo CSV出力 data_del.csv: $num 件");

      } else {
        $logger->info("Yahoo CSV出力 data_del.csv: 件数が0のためファイルは作成しませんでした。");
      }
    }


    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了')->setInformation($result));
  }
  
  /**
   * Yahooからダウンロードした商品CSVデータをインポートする。インポート先はtmpデータベース。
   * 商品CSVは巨大であり、またレイアウトが変わっている様子があるため
   * 必要なカラムのみインポートする。
   * CSVが存在しない場合は、空のテーブルのみ作成する。
   * 
   * @param string $exportTarget 出力対象店舗
   * @param string $importFilePath ファイルパス
   */
  private function importProductData($exportTarget, $importFilePath) {
    $logger = $this->getLogger();
    $dbTmp = $this->getDb('tmp');
    $targetTable = self::PRODUCT_DATA_TABLE_PLUSNAO;
    if ($exportTarget == self::EXPORT_TARGET_KAWAEMON) {
      $targetTable = self::PRODUCT_DATA_TABLE_KAWAEMON;
    }
    
    // TRUNCATE & CREATE
    $dbTmp->query("DROP TABLE IF EXISTS ${targetTable}");
    $sql = <<<EOD
      CREATE TABLE ${targetTable} (
        `path` varchar(255) DEFAULT NULL COMMENT 'パス',
        `name` varchar(255) DEFAULT NULL COMMENT '商品名',
        `code` varchar(30) NOT NULL COMMENT '商品コード\n代表商品コード',
        `sub_code` mediumtext COMMENT '個別商品コード\nCSVカラム名sub-code。SKUコードの列挙',
        `original_price` int(11) DEFAULT NULL COMMENT 'メーカー希望小売価格\nCSVカラム名original-price',
        `price` int(11) DEFAULT NULL COMMENT '通常販売価格',
        `sale_price` int(255) DEFAULT '0' COMMENT '特価\nCSVカラム名sale-price',
        `options` mediumtext COMMENT 'オプション\n項目選択肢・注記',
        `display` varchar(1) NOT NULL DEFAULT 0 COMMENT 'ページ公開',
        `lead_time_instock` int(11) DEFAULT NULL COMMENT '発送日情報（在庫あり）\nCSVカラム名lead-time-instock',
        `lead_time_outstock` int(11) DEFAULT NULL COMMENT '発送日情報（在庫なし）\nCSVカラム名lead-time-outstock',
        `postage_set` int(11) DEFAULT NULL COMMENT '発送グループ\nCSVカラム名postage-set',
        `sp_code` varchar(255) DEFAULT NULL COMMENT '販促コード\nCSVカラム名sp-code',
        PRIMARY KEY (`code`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品画像情報（${targetTable}）'
EOD;
    $dbTmp->query($sql);
    
    // data.csvがなければここで終了
    $fs = new FileSystem();
    if (!$fs->exists($importFilePath)) {
      $logger->warn("YahooCSV出力[$exportTarget]： 商品CSVが存在しないため、空のテーブルを作成して終了");
      return;
    }
    
    // CSVはShift_JISなので文字コード設定
    $dbTmp->query("SET character_set_database=cp932;");
    
    // CSVのヘッダを読み込み、各カラム位置を取得
    $fp = fopen($importFilePath, 'rb'); // 商品CSVを読み込む
    $needColumn = [ // 商品DLテーブルに必要なカラムリスト
        'path' => ''
        , 'name' => ''
        , 'code' => ''
        , 'sub-code' => ''
        , 'original-price' => ''
        , 'price' => ''
        , 'sale-price' => ''
        , 'options' => ''
        , 'display' => ''
        , 'lead-time-instock' => ''
        , 'lead-time-outstock' => ''
        , 'postage-set' => ''
        , 'sp-code' => ''
      ];
    $headerLine = fgetcsv($fp); // ヘッダ行のみ読み込む
    $varList = []; // LOAD DATA INFILE のためのユーザ変数リスト　CSVのカラム数分 @1 @2 @3... と列挙する
    for ($i = 0; $i < count($headerLine); $i++) {
      $varList[] = '@' . ($i + 1);
      if (array_key_exists($headerLine[$i], $needColumn)) { // 現在のカラムが、dlテーブルに必要なものであればカラム位置を取得
        $needColumn[$headerLine[$i]] = $i + 1; // 'path' => 1, 'name' => 2, 'code' => 3, ... と入る。数字は元CSVの位置
      }
    }
    
    // 指定カラムだけ投入
    $varListStr = implode(',', $varList);
    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :filePath
      INTO TABLE ${targetTable}
      FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
      LINES TERMINATED BY '\n'
      IGNORE 1 LINES
      ({$varListStr})
      SET path = @${needColumn["path"]}
        , name = @${needColumn["name"]}
        , code = @${needColumn["code"]}
        , sub_code = @${needColumn["sub-code"]}
        , original_price = @${needColumn["original-price"]}
        , price = @${needColumn["price"]}
        , sale_price = @${needColumn["sale-price"]}
        , options = @${needColumn["options"]}
        , display = @${needColumn["display"]}
        , lead_time_instock = @${needColumn["lead-time-instock"]}
        , lead_time_outstock = @${needColumn["lead-time-outstock"]}
        , postage_set = @${needColumn["postage-set"]}
        , sp_code = @${needColumn["sp-code"]}
EOD;
    $stmt = $dbTmp->prepare($sql);
    $stmt->bindValue(':filePath', $importFilePath, \PDO::PARAM_STR);
    $stmt->execute();
    
    //　文字コードをもとにもどす
    $dbTmp->query("SET character_set_database=utf8;");
  }
  
  /**
   * 登録済み商品と、出力予定商品を比較し、出力不要なものの出力フラグを0（出力対象外）とする。
   * @param unknown $exportTarget
   */
  private function updateProductOutputFlg($exportTarget) {
    $logger = $this->getLogger();
    $dataTable = self::PRODUCT_DATA_TABLE_PLUSNAO;
    if ($exportTarget == self::EXPORT_TARGET_KAWAEMON) {
      $dataTable = self::PRODUCT_DATA_TABLE_KAWAEMON;
    }
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    
    // まず、現在Yahooで非表示で、今後も非表示のものをOFF
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add a
      JOIN ${dbTmpName}.${dataTable} d ON a.code = d.code
      SET a.output_flg = 0
      WHERE a.display = 0 AND d.display = 0;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $logger->debug("YahooCSV出力[$exportTarget]: 非表示のまま変更なしのものを出力対象外へ。件数" . $stmt->rowCount());
    
    // 差分チェック対象カラムに差がないものをOFF
    // pathは改行コード変更と末尾の改行除去、nameはtrim、optionsは改行コードの変更を行う。このあたりはいずれ、元データのほうを修正したい
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add a
      JOIN ${dbTmpName}.${dataTable} d ON a.code = d.code
      SET a.output_flg = 0
      WHERE REPLACE(TRIM(TRAILING '\r\n' FROM a.path), '\r\n', '\n') = d.path
        AND TRIM(a.name) = d.name
        AND a.`sub-code` = d.sub_code
        AND a.price = d.price
        AND REPLACE(a.options, '\r\n', '\n') = d.options
        AND a.display = d.display
        AND a.`lead-time-instock` = d.lead_time_instock
        AND a.`postage-set` = d.postage_set
        AND a.`sp-code` = d.sp_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $logger->debug("YahooCSV出力[$exportTarget]: 差分のないものを出力対象外へ。件数" . $stmt->rowCount());
  }

  /// データ作成
  private function updateYahooInformation($exportTarget)
  {
    $logger = $this->getLogger();

    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);

    //'商品情報(explanation)の組み立て。ここはHTMLタグ不可。sp版商品情報まで構築してから500文字以内に切るので、ここでは文字数オーバー。
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      LEFT JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.explanation =
        CONCAT(
            CASE WHEN
              COALESCE(m.商品コメントPC, '') = '' THEN ''
              ELSE CONCAT(m.商品コメントPC, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`サイズについて`, '') = '' THEN ''
              ELSE CONCAT('【サイズについて】\n', m.`サイズについて`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`素材について`, '') = '' THEN ''
              ELSE CONCAT('【素材について】\n', m.`素材について`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`カラーについて`, '') = '' THEN ''
              ELSE CONCAT('【カラーについて】\n', m.`カラーについて`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`ブランドについて`, '') = '' THEN ''
              ELSE CONCAT('【ブランド】\n', m.`ブランドについて`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`使用上の注意`, '') = '' THEN ''
              ELSE CONCAT('【使用上の注意】\n', m.`使用上の注意`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`補足説明PC`, '') = '' THEN ''
              ELSE CONCAT('【補足説明】\n', m.`補足説明PC`, '\n\n')
            END
        )
      WHERE cal.endofavailability is NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: 商品情報(explanation)の組み立て', $exportTarget));

    // 商品説明(caption)とスマートフォン用フリースペース(sp-additional)の組み立て。
    // ・caption←explanation 個別情報があればそれも追加、改行コードを<br>に変換も実施、末尾に<br>を付加もまとめて。
    // 　末尾に<br>付加は、前verでは「末尾が<br>でないときだけ<br>付加」だったが、UPDATEが1回増えてしまうので速度を考慮して常時<br>付加にしてみる。
    // ・sp-additional←explanation　ここは、商品情報の500文字を超える部分（HTML） + Yahoo用個別情報 + モールデザイン。
    // モールデザインは最後に tb_yahoo_data_add 構築時に足すのでここでは何もしない。
    // スマホ版は、現在のYahoo商品詳細画面は、
    // 商品情報とフリースペースが連続したデザイン（ http://tk2-217-18298.vs.sakura.ne.jp/issues/197154#note-7 参照）なので、
    // 改行などを入れない。
    $sql = <<<EOD
        UPDATE ${targetTable} AS i
        SET caption = CONCAT(
            REPLACE(REPLACE(REPLACE(REPLACE(
              i.explanation
            , '\\r\\n', '<br>') , '\\r', '<br>') , '\\n', '<br>') , '<br>', '<br>\\r\\n')
            , '<br>\\r\\n', IFNULL(i.`input_caption`, ''), '<br>\\r\\n')
          , i.`sp-additional` = CONCAT(
            REPLACE(REPLACE(REPLACE(REPLACE(
              SUBSTRING(i.explanation, 500) -- 500字に満たなければ空文字
            , '\\r\\n', '<br>') , '\\r', '<br>') , '\\n', '<br>') , '<br>', '<br>\\r\\n')
          , '<br>\\r\\n', IFNULL(i.`input_sp_additional`, ''), '<br>\\r\\n')
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: 商品説明(caption)、SP版追加情報（sp-additional）の組み立て', $exportTarget));

    // '=================
    // 'Path
    // '=================
    // 'Pathを初期化
    $dbMain->query("UPDATE ${targetTable} AS i SET i.path = ''");
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: Pathを初期化', $exportTarget));

    //'発売終了品に関しては画像が無くてもPATHを'soldout'に設定する
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.path = CASE WHEN cal.endofavailability IS NOT NULL THEN 'soldout'
                        ELSE ''
                   END
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: PATHをsoldoutに設定', $exportTarget));

    $sql = <<<EOD
      UPDATE tb_plusnaoproductdirectory as pd
      INNER JOIN tb_mainproducts as m ON pd.NEディレクトリID = m.NEディレクトリID
      INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN ${targetTable} as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      SET i.path = buildYahooPath(rakutencategories_1)
        , pd.yahoo_category = buildYahooPath(rakutencategories_1)
      WHERE cal.endofavailability IS NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND IFNULL(m.YAHOOディレクトリID, '') <> ''
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: buildYahooPath(rakutencategories_1)', $exportTarget));

    $this->updateVariationImageFlg($exportTarget, $targetTable);
  }

  /**
   * 商品バリエーション画像出力フラグ設定
   * 画像が登録されており、かつ在庫数が基準以上のもののフラグを立てる。
   * 対象となる代表商品は、バリエーション画像が存在し、かつ tb_yahoo_data_add.display = 1 となり、在庫数が基準以上
   *
   * 一度ONにしたものはOFFにしてはならない（Yahoo側の画像は自動で消せないので、画像を残したままOFFにすると縦横設定で問題が出る）。
   * ここが新たにONになると、次のProductImageUploadFtpCommandの実行時にバリエーション画像がアップロードされる。
   * なお、Yahoo側の画像を消すのはかなりの手間なので、基準を緩めすぎないこと
   */
  private function updateVariationImageFlg($exportTarget, $targetTable) {

    // PLUSNAO特有の処理
    if ($exportTarget == self::EXPORT_TARGET_PLUSNAO) {
      $updateStockBase = intval($this->commonUtil->getSettingValue(TbSetting::KEY_YAHOO_VARI_IMG_STOCK_BASE)); // 基準在庫数 0 未満なら何もせず終了
      if (is_null($updateStockBase) || $updateStockBase === '' ||  $updateStockBase < 0 ) {
        return;
      }

      $sql = <<<EOD
        UPDATE ${targetTable} i
        JOIN (
          SELECT distinct v.daihyo_syohin_code
          FROM product_images_variation v
          INNER JOIN tb_mainproducts m ON m.daihyo_syohin_code = v.daihyo_syohin_code
          INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code AND
              CASE
                WHEN m.カラー軸 = 'row' THEN pci.rowcode = v.variation_code AND v.code = 'row'
                WHEN m.カラー軸 = 'col' THEN pci.colcode = v.variation_code AND v.code = 'col'
              END
          INNER JOIN ${targetTable} AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
          INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN (
            SELECT daihyo_syohin_code FROM tb_productchoiceitems GROUP BY daihyo_syohin_code HAVING SUM(フリー在庫数) >= :baseStock
          ) stock ON stock.daihyo_syohin_code = m.daihyo_syohin_code
          WHERE
            i.variation_image_upload_flg = 0
            AND IFNULL(m.YAHOOディレクトリID, '') <> ''
            AND cal.deliverycode_pre IN ( :deliveryCodeReady , :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder)
            AND i.registration_flg <> 0
            AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
            AND cal.endofavailability IS NULL
        ) target ON i.daihyo_syohin_code = target.daihyo_syohin_code
        SET i.variation_image_upload_flg = 1
        WHERE  i.variation_image_upload_flg = 0
EOD;
      $dbMain = $this->getDb('main');
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':baseStock', $updateStockBase);
      $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
      $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
      $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);

      $stmt->execute();

    // kawa-e-mon特有の処理
    } else {
      $updateStockBase = intval($this->commonUtil->getSettingValue(TbSetting::KEY_YAHOO_KAWA_VARI_IMG_STOCK_BASE)); // 基準在庫数 0 未満なら何もせず終了
      if (is_null($updateStockBase) || $updateStockBase === '' ||  $updateStockBase < 0 ) {
        return;
      }

      $today = new \DateTime();
      $today->setTime(0, 0, 0);

      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $settingsArray = $repo->findUnpublishedPageSettingByArray('kawaemon');
      $quantityBaseDate = clone $today;
      $quantityBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY_DAYS]));
      $salesBaseDate = clone $today;
      $salesBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES_DAYS]));

      $sql = <<<EOD
        UPDATE ${targetTable} i
        JOIN (
          SELECT distinct v.daihyo_syohin_code FROM product_images_variation v
          INNER JOIN tb_mainproducts m ON m.daihyo_syohin_code = v.daihyo_syohin_code
          INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code AND
              CASE
                WHEN m.カラー軸 = 'row' THEN pci.rowcode = v.variation_code AND v.code = 'row'
                WHEN m.カラー軸 = 'col' THEN pci.colcode = v.variation_code AND v.code = 'col'
              END
          INNER JOIN ${targetTable} AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
          INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN (
            SELECT daihyo_syohin_code FROM tb_productchoiceitems GROUP BY daihyo_syohin_code HAVING SUM(フリー在庫数) >= :baseStock
          ) stock ON stock.daihyo_syohin_code = m.daihyo_syohin_code
          LEFT JOIN (
              SELECT
                daihyo_syohin_code
              FROM
                tb_sales_detail_analyze a
              WHERE
                `受注日` >= :quantityBaseDate
                AND a.`キャンセル区分` = '0'
                AND a.`明細行キャンセル` = '0'
                AND a.`店舗コード` = '12'
              GROUP BY
                daihyo_syohin_code
              HAVING
                SUM(`受注数`) >= :quantityBaseSum
            ) amt ON m.daihyo_syohin_code = amt.daihyo_syohin_code
          LEFT JOIN (
              SELECT
                daihyo_syohin_code
              FROM
                tb_sales_detail_analyze a
              WHERE
                `受注日` >= :salesBaseDate
                AND a.`キャンセル区分` = '0'
                AND a.`明細行キャンセル` = '0'
                AND a.`店舗コード` = '12'
              GROUP BY
                daihyo_syohin_code
              HAVING
                SUM(`小計`) >= :salesBaseSum
            ) total ON m.daihyo_syohin_code = total.daihyo_syohin_code
          WHERE
            i.variation_image_upload_flg = 0
            AND IFNULL(m.YAHOOディレクトリID, '') <> ''
            AND cal.deliverycode_pre IN ( :deliveryCodeReady , :deliveryCodeReadyPartially)
            AND i.registration_flg <> 0
            AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
            AND cal.endofavailability IS NULL
            AND amt.daihyo_syohin_code IS NULL
            AND total.daihyo_syohin_code IS NULL
        ) target ON i.daihyo_syohin_code = target.daihyo_syohin_code
        SET i.variation_image_upload_flg = 1
        WHERE  i.variation_image_upload_flg = 0
EOD;

      $dbMain = $this->getDb('main');
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':baseStock', $updateStockBase);
      $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
      $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
      $stmt->bindValue(':quantityBaseDate', $quantityBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':quantityBaseSum', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY], \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseDate', $salesBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseSum', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES], \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
      $stmt->execute();
    }
  }

  /**
   * エクスポートデータ作成
   * @param $exportTarget
   */
  private function prepareData($exportTarget)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    $logTitle = 'ヤフーCSV出力処理';
    $subTitle = 'prepareData___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);
    $kawaemonAddRate = intval($commonUtil->getSettingValue('YAHOO_KAWAEMON_B_TNK_ADD_RATE'));
    $yahooTemplate = $commonUtil->getSettingValue('YAHOO_TEMPLATE');

    // Web画面で設定した設定値を取得
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
    $settingsArray = $repo->findUnpublishedPageSettingByArray('kawaemon');

    //'====================
    //    '商品データ
    //'====================
    $dbMain->query("TRUNCATE tb_yahoo_data_add");

    /**
     * 出品条件
     * 1 出品フラグがonの商品
     * 2 権利侵害・アダルト審査が「ブラック」「グレー」「未審査」ではない商品
     * 3 Yahoo(plusnao)へ既登録済みの全商品で1、2に該当しない完売3年以内の全商品
     */
    // '販売中
    $sql = <<<EOD
      INSERT INTO tb_yahoo_data_add (
          path
        , code
        , `product-category`
        , display
        , `lead-time-instock`
      )
      SELECT
          COALESCE(CONCAT('nopictures:', m.picfolderP1), '＿新規') AS path
        , m.daihyo_syohin_code
        , m.YAHOOディレクトリID
        , '0' AS display
        , CASE
            WHEN cal.deliverycode_pre IN (:deliveryCodeReady, :deliveryCodeReadyPartially) THEN '1'
            ELSE '4000'
          END AS `lead-time-instock`
      FROM tb_mainproducts           AS m
      INNER JOIN ${targetTable}      AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE IFNULL(m.YAHOOディレクトリID, '') <> ''
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
        AND cal.endofavailability IS NULL
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: 商品データ', $exportTarget));

    //'販売終了品
    $sql = <<<EOD
      INSERT INTO tb_yahoo_data_add (
          path
        , code
        , `product-category`
        , display
        , `lead-time-instock`
      )
      SELECT
          COALESCE(i.path, '＿新規') AS path
        , m.daihyo_syohin_code
        , m.YAHOOディレクトリID
        , '0' AS display
        , '4000' AS `lead-time-instock` /* 空文字ではエラーが出るため、一応値を入れる */
      FROM tb_mainproducts           AS m
      INNER JOIN ${targetTable}      AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE IFNULL(m.YAHOOディレクトリID, '') <> ''
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
        AND cal.endofavailability is NOT NULL
        AND cal.endofavailability >= :exportLimitDate
EOD;
    $exportLimit = new \DateTime();
    $exportLimit->modify('-3 year'); // 販売終了から三年間

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':exportLimitDate', $exportLimit->format('Y-m-d 00:00:00'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: 販売終了品', $exportTarget));

    $logger->info("SUBコードとOptionの設定");

    //'====================
    //    'Sub-Code & Options
    //'====================
    /*
    switch ($exportTarget) {
      case self::EXPORT_TARGET_PLUSNAO:
        $dbMain->query("CALL BuildSubcodeOptionsDataAdd()");
        break;
      case self::EXPORT_TARGET_KAWAEMON:
        $dbMain->query("CALL BuildSubcodeOptionsKawaDataAdd()");
        break;
    }
    */
    $dbMain->query("SET SESSION group_concat_max_len = 20480");

    $sql = <<<EOD
      INSERT INTO {$targetTable} (
           daihyo_syohin_code
        , `sub-code`
        , options
      )
      SELECT
          T1.daihyo_syohin_code
        , T1.sub_code
        , T2.options
      FROM (
        SELECT
            daihyo_syohin_code
          , GROUP_CONCAT(sub_code ORDER BY 並び順No SEPARATOR '&') AS sub_code
        FROM (
          SELECT
              pci.daihyo_syohin_code
            , 並び順No
            , CASE
                WHEN i.variation_image_upload_flg = 0 OR m.カラー軸 <> 'col' THEN
                  CONCAT(
                    m.row_title
                  , ':'
                  , rowname
                  , '#'
                  , m.col_title
                  , ':'
                  , pci.colname
                  , '='
                  , pci.daihyo_syohin_code
                  , pci.colcode
                  , pci.rowcode
                  )
                ELSE
                  CONCAT(
                    m.col_title
                  , ':'
                  , colname
                  , '#'
                  , m.row_title
                  , ':'
                  , pci.rowname
                  , '='
                  , pci.daihyo_syohin_code
                  , pci.colcode
                  , pci.rowcode
                  )
              END AS sub_code
          FROM tb_productchoiceitems pci
          INNER JOIN tb_yahoo_data_add a ON pci.daihyo_syohin_code = a.code
          INNER JOIN {$targetTable} i ON i.daihyo_syohin_code = a.code
          INNER JOIN (
            SELECT
                  daihyo_syohin_code
                , CASE
                    WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN
                      CASE WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN 'カラー'
                           WHEN m.`横軸項目名` <> 'カラー' THEN 'カラー'
                           ELSE 'サイズ'
                      END
                    ELSE m.`縦軸項目名`
                  END AS row_title
                , CASE
                    WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN
                      CASE WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN 'サイズ'
                           WHEN m.`縦軸項目名` <> 'サイズ' THEN 'サイズ'
                           ELSE 'カラー'
                      END
                    ELSE m.`横軸項目名`
                  END AS col_title
                , m.カラー軸
            FROM tb_mainproducts m
          ) AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        ) AS T
        GROUP BY daihyo_syohin_code
      ) T1
      INNER JOIN (
        SELECT
            m.daihyo_syohin_code
          , CASE
              WHEN i.variation_image_upload_flg = 0 OR m.カラー軸 <> 'col' THEN
                CONCAT(
                    m.row_title
                  , ' '
                  , pci.rowname_list
                  , '\\r\\n\\r\\n'
                  , m.col_title
                  , ' '
                  , pci.colname_list
                )
              ELSE
                CONCAT(
                    m.col_title
                  , ' '
                  , pci.colname_list
                  , '\\r\\n\\r\\n'
                  , m.row_title
                  , ' '
                  , pci.rowname_list
                )
              END
            AS options
        FROM tb_yahoo_data_add a
        INNER JOIN {$targetTable} i ON i.daihyo_syohin_code = a.code
        INNER JOIN (
          SELECT
                daihyo_syohin_code
              , CASE
                  WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN
                    CASE WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN 'カラー'
                         WHEN m.`横軸項目名` <> 'カラー' THEN 'カラー'
                         ELSE 'サイズ'
                    END
                  ELSE m.`縦軸項目名`
                END AS row_title
              , CASE
                  WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN
                    CASE WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN 'サイズ'
                         WHEN m.`縦軸項目名` <> 'サイズ' THEN 'サイズ'
                         ELSE 'カラー'
                    END
                  ELSE m.`横軸項目名`
                END AS col_title
              , m.カラー軸
          FROM tb_mainproducts m
        ) AS m ON a.code = m.daihyo_syohin_code
        INNER JOIN (
          SELECT
              daihyo_syohin_code
            , GROUP_CONCAT(DISTINCT rowname ORDER BY 並び順No SEPARATOR ' ') AS rowname_list
            , GROUP_CONCAT(DISTINCT colname ORDER BY 並び順No SEPARATOR ' ') AS colname_list
          FROM tb_productchoiceitems pci
          INNER JOIN tb_yahoo_data_add a ON pci.daihyo_syohin_code = a.code
          GROUP BY daihyo_syohin_code
        ) AS pci ON a.code = pci.daihyo_syohin_code
      ) T2 ON T1.daihyo_syohin_code = T2.daihyo_syohin_code
      ON DUPLICATE KEY UPDATE
          `sub-code` = T1.sub_code
        , options = T2.options
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('prepareData: BuildSubcodeOptions(Kawa)DataAdd', $exportTarget));

    // '2015.03.09 optionsに注記を
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'appendOptions', '開始'));
    $this->appendOptions($exportTarget);
    $logger->info($this->getLapTimeAndMemory('prepareData: appendOptions', $exportTarget));
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'appendOptions', '終了'));

    // tb_yahoo_data_add に値をコピー
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add
      INNER JOIN ${targetTable} AS i ON tb_yahoo_data_add.code = i.daihyo_syohin_code
      SET tb_yahoo_data_add.`sub-code` = i.`sub-code`
        , tb_yahoo_data_add.options = i.options
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('prepareData: tb_yahoo_data_add に値をコピー', $exportTarget));
    
    // 優良配送のための配送情報
    $this->prepareDeliveryData();

    $logger->info("その他の情報を設定中");

    // '2014.01.23 商品情報（explanation）ではなく商品説明（caption）に設定するように変更
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add      AS A
      LEFT JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      LEFT JOIN ${targetTable}      AS i   ON A.code = i.daihyo_syohin_code
      SET A.`original-price` = m.`実勢価格`
        , A.price = TRUNCATE(i.baika_tanka * :taxRate, - 1)
        , A.price_add_10per = TRUNCATE(
            i.baika_tanka * ((100 + :kawaemonAddRate) / 100) * :taxRate
          , - 1
        )
        , A.`sale-price`    = NULL
        , A.`meta-key`      = IFNULL(i.`meta-key`, '')
        , A.`meta-desc`     = IFNULL(m.`商品コメントPC`, '')
        , A.caption         = IFNULL(i.caption, '')
        , A.explanation     = IFNULL(i.explanation, '')
        , A.`sp-additional` = IFNULL(i.`sp-additional`, '')
        , A.`pr-rate`       = i.`pr-rate`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':kawaemonAddRate', $kawaemonAddRate, \PDO::PARAM_STR);
    $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: その他の情報を設定', $exportTarget));

    // Call abstract___
    $this->updateAbstract($exportTarget);
    $logger->info($this->getLapTimeAndMemory('prepareData: updateAbstract', $exportTarget));

    // spec更新
    // spec1: サイズ、 spec2: カラー、 spec3~: 現状利用予定なし それぞれマッチするものがなければカラでよい
    $yahooDataAddRepo = $this->getDoctrine()->getRepository('MiscBundle:TbYahooDataAdd');
    $yahooDataAddRepo->applySizeSpec();
    $logger->info($this->getLapTimeAndMemory('prepareData: a.spec1 ~ 3', $exportTarget));
    $yahooDataAddRepo->applyColorSpec();
    $logger->info($this->getLapTimeAndMemory('prepareData: a.spec4', $exportTarget));

    // 2017/09/22 PlusNaoとおとりよせ.com相互リンク
    // スマートフォン商品情報下部もこちらで追加するよう変更

    $logger->info("スマートフォン用情報に追加情報を設定中");

    if ($exportTarget == self::EXPORT_TARGET_PLUSNAO) {

      // tb_mall_designからデザインHTML取得
      // 【YahooPlusnao】おとりよせ.comへの商品リンク（SP大）(code="p_to_o_sp_l")
      $designHTML = '';
      $sql = <<<EOD
        SELECT design_html FROM tb_mall_design d WHERE d.code = 'p_to_o_sp_l'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $designHTML = $tmp['design_html'];
      }
      // 【YahooPlusnao】おとりよせ.comへの商品リンク（SP小）(code="p_to_o_sp_s")
      $sql = <<<EOD
        SELECT design_html FROM tb_mall_design d WHERE d.code = 'p_to_o_sp_s'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $designHTML = $designHTML .$tmp['design_html'];
      }
      // 【YahooPlusnao】スマートフォン商品情報上部(code="yahoo_sp_top")
      $sql = <<<EOD
        SELECT design_html FROM tb_mall_design d WHERE d.code = 'yahoo_sp_top'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $designHTML = $designHTML .$tmp['design_html'];
      }
      // 【YahooPlusnao】スマートフォン商品情報下部(code="yahoo_sp_bottom")
      $addHTML = '';
      $sql = <<<EOD
        SELECT design_html FROM tb_mall_design d WHERE d.code = 'yahoo_sp_bottom'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $addHTML = $tmp['design_html'];
      }

      $designHTML = preg_replace('/^\\s+/m', '', $designHTML); // m修飾子 大事
      $designHTML = preg_replace('/\r\n|\r|\n/', '',  $designHTML);
      $addHTML = preg_replace('/^\\s+/m', '', $addHTML); // m修飾子 大事
      $addHTML = preg_replace('/\r\n|\r|\n/', '',  $addHTML);

      // デザインHTMLに含まれる「##code##」はcode（商品コード：小文字）に置き換えて更新する
      $sql = <<<EOD
        UPDATE
          tb_yahoo_data_add
        SET
          `sp-additional` = CONCAT(`sp-additional`, REPLACE(:designHTML, '##code##', LOWER(code)), '<br>\\r\\n', :addHTML)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':designHTML', $designHTML);
      $stmt->bindValue(':addHTML', $addHTML);
      $stmt->execute();

    }else{

      // 【Kawa-e-mon】スマートフォン商品情報下部(code="kawa_sp_bottom")
      $addHTML = '';
      $sql = <<<EOD
        SELECT design_html FROM tb_mall_design d WHERE d.code = 'kawa_sp_bottom'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $addHTML = $tmp['design_html'];
      }

      $addHTML = preg_replace('/^\\s+/m', '', $addHTML); // m修飾子 大事
      $addHTML = preg_replace('/\r\n|\r|\n/', '',  $addHTML);

      $sql = <<<EOD
        UPDATE
          tb_yahoo_data_add
        SET
          `sp-additional` = CONCAT(`sp-additional`, :addHTML)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':addHTML', $addHTML);
      $stmt->execute();
    }

    // ??? JOINの意図は不明
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add AS A
      LEFT JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      LEFT JOIN ${targetTable}      AS i   ON A.code = i.daihyo_syohin_code
      SET A.taxable     = '1'
        , A.template    = :yahooTemplate
        , A.`astk-code` = '0'
        , A.condition   = '0'
        , A.taojapan    = '1'
        , A.sort        = (9999999 - cal.priority)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':yahooTemplate', $yahooTemplate);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: taxable,template,astk-code,condition,taojapan,sort', $exportTarget));


    $dbMain->query("UPDATE tb_yahoo_data_add SET `sort` = 9999999 WHERE `sort` IS NULL");

    //'2014.01.30 削除対象を削除せずに非表示にしたことから条件を変更する。出品対象外商品についてはdisplayを1にしない
    // 2016/08/24 予約販売停止により、在庫がない商品は display 0 （非公開）
    // 2017/09/25 YahooPlusnaoはおとりよせ.comと相互リンクになったため、予約商品(受発注のみ)も表示する

    // PLUSNAO特有の処理
    if ($exportTarget == self::EXPORT_TARGET_PLUSNAO) {
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS a
        INNER JOIN ${targetTable} AS i ON a.code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON a.code = cal.daihyo_syohin_code
        SET a.path = i.path
          , a.display = CASE WHEN cal.deliverycode_pre IN ( :deliveryCodeReady , :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder) THEN '1' ELSE '0' END
          , a.`brand-code` = :brandCode /* ブランドコードのデフォルト値として利用 */
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
      $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
      $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER);
      $stmt->bindValue(':brandCode', self::BRAND_CODE_PLUSNAO);
      $stmt->execute();
      $logger->info($this->getLapTimeAndMemory('prepareData: a.path', $exportTarget));

      // ブランドコード設定
      // 1件しかマッチしてはならないが、複数にマッチした場合はIDの小さい方が適用される
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS a
        INNER JOIN tb_mainproducts AS m ON a.code = m.daihyo_syohin_code
        INNER JOIN tb_yahoo_product_category AS c ON m.`YAHOOディレクトリID` = c.yahoo_id
        SET a.`brand-code` = c.`brand_code`
EOD;
      $dbMain->query($sql);
      $logger->info($this->getLapTimeAndMemory('prepareData: a.brand-code', $exportTarget));

    // kawa-e-mon特有の処理
    }else{
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS a
        INNER JOIN ${targetTable} AS i ON a.code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON a.code = cal.daihyo_syohin_code
        SET a.path = i.path
          , a.display = CASE WHEN cal.deliverycode_pre IN ( :deliveryCodeReady , :deliveryCodeReadyPartially) THEN '1' ELSE '0' END
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
      $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
      $stmt->execute();

      // YahooPlusnaoで一定期間中に一定個数売れている商品は非公開
      // 基準値はシステムより変更可能
      $quantityBaseDate = clone $today; // 新着とする基準日（この日を含む）
      $quantityBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY_DAYS]));
      $logger->info("ページ非公開設定：　基準日:" . $quantityBaseDate->format('Y-m-d')
          . ", 基準数:" .  $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY]);

      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS a
          INNER JOIN (
            SELECT
              daihyo_syohin_code
            FROM
              tb_sales_detail_analyze a
            WHERE
              `受注日` >= :amountBaseDate
              AND a.`キャンセル区分` = '0'
              AND a.`明細行キャンセル` = '0'
              AND a.`店舗コード` = '12'
            GROUP BY
              daihyo_syohin_code
            HAVING
              SUM(`受注数`) >= :amountBaseSum
          ) amt
            ON a.code = amt.daihyo_syohin_code
        SET a.display = 0
EOD;

      $logger->debug("sql:" . $sql);
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':amountBaseDate', $quantityBaseDate->format('Y-m-d'), \PDO::PARAM_STR, \PDO::PARAM_STR);
      $stmt->bindValue(':amountBaseSum', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY], \PDO::PARAM_STR);
      $stmt->execute();

      // YahooPlusnaoで一定期間中に一定売上金額売れている商品は非公開
      // 基準値はシステムより変更可能
      $salesBaseDate = clone $today; // 新着とする基準日（この日を含む）
      $salesBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES_DAYS]));
      $logger->info("ページ非公開設定：　基準日:" . $salesBaseDate->format('Y-m-d')
          . ", 基準数:" .  $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES]);

      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS a
          INNER JOIN (
            SELECT
              daihyo_syohin_code
            FROM
              tb_sales_detail_analyze a
            WHERE
              `受注日` >= :salesBaseDate
              AND a.`キャンセル区分` = '0'
              AND a.`明細行キャンセル` = '0'
              AND a.`店舗コード` = '12'
            GROUP BY
              daihyo_syohin_code
            HAVING
              SUM(`小計`) >= :salesBaseSum
          ) amt
            ON a.code = amt.daihyo_syohin_code
        SET a.display = 0
EOD;

      $logger->debug("sql:" . $sql);
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':salesBaseDate', $salesBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseSum', $settingsArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES], \PDO::PARAM_STR);
      $stmt->execute();

      $logger->info($this->getLapTimeAndMemory('prepareData: a.path', $exportTarget));
    }

    // 'ゲリラSALE
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add AS A
      INNER JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata AS v   ON m.sire_code = v.sire_code
      SET A.path = CONCAT(A.path, '\\r\\n【ゲリラSALE】\\r\\n')
      WHERE
          (
               v.`cost_rate` >= v.`guerrilla_margin`
            OR m.手動ゲリラSALE <> 0
          )
        AND cal.endofavailability IS NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND A.path <> '＿新規'
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: ゲリラSALE', $exportTarget));

    //'アウトレット
    if ($commonUtil->getSettingValue('RAKUTEN_TITLE_OUTLET') <> 0) {
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS A
        INNER JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
        SET A.path = CONCAT(A.path, '\\r\\n【アウトレット】\\r\\n')
        WHERE cal.outlet <> 0
          AND A.path <> '＿新規'
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $logger->info($this->getLapTimeAndMemory('prepareData: アウトレット', $exportTarget));
    }

    //YahooPlusnaoは全品送料無料となるため送料無料のカテゴリーは不要
    if($exportTarget == self::EXPORT_TARGET_KAWAEMON){
      //'メール便送料無料
      if ($commonUtil->getSettingValue('YAHOO_TITLE_MAIL') <> 0) {
        $sql = <<<EOD
          UPDATE tb_yahoo_data_add as A
          INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
          INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
          SET A.path = CONCAT(A.path, '\\r\\n【メール便送料無料】\\r\\n')
          WHERE sd.shipping_group_code = :shippingGroupCodeMailbin
            AND A.path <> '＿新規'
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':shippingGroupCodeMailbin', TbShippingdivision::SHIPPING_GROUP_CODE_MAILBIN, \PDO::PARAM_INT);
        $stmt->execute();
        $logger->info($this->getLapTimeAndMemory('prepareData: メール便送料無料', $exportTarget));
      }

      //'定形送料無料
      if ($commonUtil->getSettingValue('YAHOO_TITLE_STANDARD_SIZE_MAIL') <> 0) {
        $sql = <<<EOD
          UPDATE tb_yahoo_data_add AS A
          INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
          INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
          SET A.path = CONCAT(A.path,'\\r\\n【定形送料無料】\\r\\n')
          WHERE sd.shipping_group_code = :shippingGroupCodeTeikei
            AND A.path <> '＿新規'
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
        $stmt->execute();
        $logger->info($this->getLapTimeAndMemory('prepareData: 定形送料無料', $exportTarget));
      }

      //'定形外送料無料
      if ($commonUtil->getSettingValue('YAHOO_TITLE_ABNORMAL') <> 0) {
        $sql = <<<EOD
          UPDATE tb_yahoo_data_add AS A
          INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
          INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
          SET A.path = CONCAT(A.path,'\\r\\n【定形外送料無料】\\r\\n')
          WHERE sd.shipping_group_code = :shippingGroupCodeTeikeigai
            AND A.path <> '＿新規'
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
        $stmt->execute();
        $logger->info($this->getLapTimeAndMemory('prepareData: 定形外送料無料', $exportTarget));
      }

      //'ゆうパケット送料無料
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add as A
        INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
        INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
        SET A.path = CONCAT(A.path, '\\r\\n【ゆうパケット送料無料】\\r\\n')
        WHERE sd.shipping_group_code = :shippingGroupCodeYuuPacket
          AND A.path <> '＿新規'
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
      $stmt->execute();
      $logger->info($this->getLapTimeAndMemory('prepareData: ゆうパケット送料無料', $exportTarget));

      //'ねこポス送料無料
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add as A
        INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
        INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
        SET A.path = CONCAT(A.path, '\\r\\n【ねこポス送料無料】\\r\\n')
        WHERE sd.shipping_group_code = :shippingGroupCodeNekoposu
          AND A.path <> '＿新規'
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':shippingGroupCodeNekoposu', TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU, \PDO::PARAM_INT);
      $stmt->execute();
      $logger->info($this->getLapTimeAndMemory('prepareData: ねこポス送料無料', $exportTarget));


      // '宅配便送料無料【同梱商品も送料無料】
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add as A
        INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
        INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
        SET A.path = CONCAT(A.path,'\\r\\n【同梱商品も送料無料】\\r\\n')
        WHERE sd.shipping_group_code = :shippingGroupCodeTakuhaibin
        AND A.path <>'＿新規'
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
      $stmt->execute();
      $logger->info($this->getLapTimeAndMemory('prepareData: 宅配便送料無料', $exportTarget));
    }

    // 'PATHで改行が２つ連続している場合は１つにする
    $dbMain->query("UPDATE tb_yahoo_data_add AS A SET A.Path = REPLACE(A.path,'\\r\\n\\r\\n', '\\r\\n')");
    $logger->info($this->getLapTimeAndMemory('prepareData: PATHで改行が２つ連続している場合は１つにする', $exportTarget));

    // 'nameとexplanationがどういうわけか一部のレコードが更新されない。商品コード指定で単品だと更新する
    // '理由が分からないが仕方がないのでサーバー側でSQL実行する
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add A
      LEFT JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      LEFT JOIN ${targetTable}      AS i   ON A.code = i.daihyo_syohin_code
      SET A.name = IFNULL(i.yahoo_title, '')
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('prepareData: A.name = i.yahoo_title', $exportTarget));

    // '全角記号等の除去
    $this->fixName("[]♪、。，．・：；？！゛゜´｀¨＾￣＿ヽヾゝゞ〃仝々〆〇―‐／＼∥｜…‥‘’（）〔〕［］｛｝〈〉《》「」『』【】＋－±×÷＝≠＜＞≦≧∞∴♂♀°′″℃￥＄￠￡％＃＆＊＠§☆★○●◇◆□■△▲▽▼※〒→←↑↓〓“”◎", ' ');
    $logger->info($this->getLapTimeAndMemory('prepareData: fixName', $exportTarget));

    //Call fixNameSpaces___
    // 全角空白の変換・余分な空白の除去
    $this->fixNameSpaces();
    $logger->info($this->getLapTimeAndMemory('prepareData: fixNameSpaces', $exportTarget));

    //'項目の切り捨て（backtitle付加前に！）
    $dbMain->query("UPDATE tb_yahoo_data_add SET `meta-desc` = LEFT(`meta-desc`,80)");
    $logger->info($this->getLapTimeAndMemory('prepareData: meta-desc 80文字', $exportTarget));

    //'出荷予定のないものは75文字
    $dbMain->query("UPDATE tb_yahoo_data_add SET `name` = LEFT(`name`,75)");
    $logger->info($this->getLapTimeAndMemory('prepareData: name 75文字', $exportTarget));

    //'重量設定・、送料設定
    // 全て送料無料のため、重量 = 0、送料 = 1 とする
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add A
      INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
      SET `ship-weight` = 0
      , delivery = 1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: 重量設定・、送料設定', $exportTarget));

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }
  
  /**
   * Yahoo優良配送対応のための、配送関連情報を設定する。
   * prepareDataから切り出し。
   * 
   * Yahooでは、「注文日＋2日以内にお届けできる商品」は「優良配送」マークがつき、検索時に優先表示される（営業日はYahoo側で計算）
   * この対応が可能なのは宅配便と、近県のゆうパケットのみ（2022/09現在）
   * このため、この機能に関連する「発送日情報（lead-time-instock）と配送グループ設定（postage-set）」をパターン分けして設定する。
   * 
   * 設定基準は以下の通り。
   * ・各種設定で「優良配送無効」時は、全てデフォルト。
   *  有効な時だけ送料設定に従い、以下の通り。
   * ・送料設定を参照し、もっとも遅いSKUに合わせて代表商品の発送日情報・配送グループ設定を設定
   * ・送料設定が未設定のSKUは考慮しない（例えばゆうパケットのSKUと未設定のSKUがある場合、未設定のSKUは定形外発送かもしれないが、考慮しない）
   * ・全てのSKUが未設定の新規商品は、代表商品の送料設定を利用
   * ・受発注のみ、販売終了は、発送日情報4000（おとりよせ）、送料グループ設定1（送料無料。発送方法記載なし）
   * 
   * このメソッドを呼び出した時点で
   * ・即納・一部即納：　発送日情報1、送料グループ設定1
   * ・受発注のみ、販売終了：　発送日情報4000、送料グループ設定1
   * となっていること。
   * このため、この中では、優良配送有効時のみ、即納・一部即納の商品のみ更新する。
   */
  private function prepareDeliveryData() {    
    $dbMain = $this->getDb('main');
    
    // 優良配送無効時はそのまま終了
    $commonUtil = $this->getDbCommonUtil();
    $excellentDeliveryFlg = $commonUtil->getSettingValue(TbSetting::KEY_YAHOO_EXCELLENT_DELIVERY_AVAILABLE);
    if (!$excellentDeliveryFlg) {
      return;
    }
    
    // 即納・一部即納の代表商品の発送日・配送グループを、送料設定に合わせてリセット（SKUに1件も送料設定がない商品用）
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add A
      JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
      JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET 
        A.`lead-time-instock` = 
          CASE sd.shipping_group_code
            WHEN :shippingGroupCodeTeikeigai THEN :leadTimeInstockDefault
            WHEN :shippingGroupCodeTeikei THEN :leadTimeInstockDefault
            WHEN :shippingGroupCodeYuuPacket THEN :leadTimeInstockGooddelivery
            WHEN :shippingGroupCodeTakuhaibin THEN :leadTimeInstockGooddelivery
            ELSE :leadTimeInstockDefault
          END,
        A.`postage-set` =
          CASE sd.shipping_group_code
            WHEN :shippingGroupCodeTeikeigai THEN :postageSetTeikeigai
            WHEN :shippingGroupCodeTeikei THEN :postageSetTeikeigai
            WHEN :shippingGroupCodeYuuPacket THEN :postageSetYuuPacket
            WHEN :shippingGroupCodeTakuhaibin THEN :postageSetTakuhaibin
            ELSE :postageSetDefault
          END
      WHERE cal.deliverycode_pre IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
EOD;
    
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
    $stmt->bindValue(':leadTimeInstockDefault', self::READ_TIME_INSTOCK_DEFAULT, \PDO::PARAM_INT);
    $stmt->bindValue(':leadTimeInstockGooddelivery', self::READ_TIME_INSTOCK_GOODDELIVERY, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetTeikeigai', self::POSTAGE_SET_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetYuuPacket', self::POSTAGE_SET_YUUPACKET, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetTakuhaibin', self::POSTAGE_SET_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetDefault', self::POSTAGE_SET_DEFAULT, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
    $stmt->execute();
    
    // 最遅SKUに従って更新
    $rankTeikeiagi = 1; // 定形外が最優先 定形もここ
    $rankYuupacket = 2; // ゆうパケット
    $rankTakuhaibin = 3; // 宅配便
    $rankDefault = 9; // どれにも当てはまらない
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add A
      JOIN (
        SELECT daihyo_syohin_code, MIN(latest_delivery_type) as latest_delivery_type
        FROM (
          -- 各SKUの配送方法を優先度に変換　優先度が大きい（到着が遅い）ものほど latest_delivery_type を小さい数字とする
          -- 外側のクエリでMIN(latest_delivery_type) を取り、代表商品ごとの最遅を取得
          SELECT
            pci.daihyo_syohin_code,
            CASE sd.shipping_group_code
              WHEN :shippingGroupCodeTeikeigai THEN :rankTeikeigai -- 定形外
              WHEN :shippingGroupCodeTeikei THEN :rankTeikeigai -- 定形郵便
              WHEN :shippingGroupCodeYuuPacket THEN :rankYuupacket -- ゆうパケット
              WHEN :shippingGroupCodeTakuhaibin THEN :rankTakuhaibin -- 宅配便
              ELSE :rankDefault -- その他、未設定
            END as latest_delivery_type
          FROM tb_productchoiceitems pci
          JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
          JOIN tb_shippingdivision sd ON pci.shippingdivision_id = sd.id
          WHERE cal.deliverycode_pre IN (:deliveryCodeReady, :deliveryCodeReadyPartially) -- 即納、一部即納だけに絞り込み
        ) T
        GROUP BY daihyo_syohin_code
      ) S ON S.daihyo_syohin_code = A.code
      SET A.`lead-time-instock` =
          CASE latest_delivery_type
            WHEN :rankTeikeigai THEN :leadTimeInstockDefault -- デフォルト配送
            WHEN :rankYuupacket THEN :leadTimeInstockGooddelivery -- 優良配送
            WHEN :rankTakuhaibin THEN :leadTimeInstockGooddelivery -- 優良配送
            ELSE :leadTimeInstockDefault -- デフォルト配送
          END
        , A.`postage-set` =
          CASE latest_delivery_type
            WHEN :rankTeikeigai THEN :postageSetTeikeigai -- 定形外、定形郵便
            WHEN :rankYuupacket THEN :postageSetYuuPacket -- ゆうパケット
            WHEN :rankTakuhaibin THEN :postageSetTakuhaibin -- 宅配便
            ELSE :postageSetDefault -- デフォルト
          END
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
    $stmt->bindValue(':leadTimeInstockDefault', self::READ_TIME_INSTOCK_DEFAULT, \PDO::PARAM_INT);
    $stmt->bindValue(':leadTimeInstockGooddelivery', self::READ_TIME_INSTOCK_GOODDELIVERY, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetTeikeigai', self::POSTAGE_SET_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetYuuPacket', self::POSTAGE_SET_YUUPACKET, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetTakuhaibin', self::POSTAGE_SET_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':postageSetDefault', self::POSTAGE_SET_DEFAULT, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
    $stmt->bindValue(':rankTeikeigai', $rankTeikeiagi, \PDO::PARAM_INT);
    $stmt->bindValue(':rankYuupacket', $rankYuupacket, \PDO::PARAM_INT);
    $stmt->bindValue(':rankTakuhaibin', $rankTakuhaibin, \PDO::PARAM_INT);
    $stmt->bindValue(':rankDefault', $rankDefault, \PDO::PARAM_INT);
    
    $stmt->execute();
    
  }

  /**
   * オプションに注記追加
   * @param $exportTarget
   */
  private function appendOptions($exportTarget)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);

    //メール便
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は メール便発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeMailbin
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeMailbin', TbShippingdivision::SHIPPING_GROUP_CODE_MAILBIN, \PDO::PARAM_INT);
    $stmt->execute();

    //定形
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は 定形郵便にて発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeTeikei
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->execute();

    //定形外
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は ゆうパケット又は、定形外にて発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeTeikeigai
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->execute();

    //ゆうパケット
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は ゆうパケット又は、定形外にて発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeYuuPacket
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
    $stmt->execute();

    //ねこポス
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は ねこポス発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeNekoposu
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeNekoposu', TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU, \PDO::PARAM_INT);
    $stmt->execute();


    //宅配便(YahooPlusnaoでは宅配便送料別は送料込みとなるため、同じ設定にする)
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(
            i.`options`
          , '\\r\\n\\r\\n'
          , '★この商品は 宅配便発送予定です'
        )
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeTakuhaibin
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->execute();

    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata AS v ON m.sire_code = v.sire_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★セール特典 ゲリラSALE中')
      WHERE
        cal.endofavailability IS NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND (
            v.`cost_rate` >= v.`guerrilla_margin`
          OR m.手動ゲリラSALE <> 0
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    //店内全品送料無料
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★店内全品送料無料 一部地域を除く')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    //キャンセル・変更不可
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★ご注文後のキャンセル・変更不可 了承済み')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    //代引き・時間指定
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★代引き・時間指定不可 了承済み')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /// ひと言コメント
  private function updateAbstract($exportTarget)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    // リセット
    $dbMain->query("UPDATE tb_yahoo_data_add SET tb_yahoo_data_add.abstract = ''");


    // 2017/09/22 PlusNaoとおとりよせ.com相互リンク
    // tb_mall_designからデザインHTML取得、おとりよせ.comの商品ページへリンク
    // 【YahooPlusnao】おとりよせ.comへの商品リンク（PC）(code="p_to_o_pc")
    if ($exportTarget == self::EXPORT_TARGET_PLUSNAO) {

      $logger->info("ひと言コメントを設定中");

      // デザインHTML取得
      $designHTML = '';
      $sql = <<<EOD
        SELECT design_html FROM tb_mall_design d WHERE d.code = 'p_to_o_pc'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $designHTML = $tmp['design_html'];
      }

      $designHTML = preg_replace('/^\\s+/m', '', $designHTML); // m修飾子 大事
      $designHTML = preg_replace('/\r\n|\r|\n/', '',  $designHTML);

      // デザインHTMLに含まれる「##code##」はcode（商品コード：小文字）に置き換えて更新する
      $sql = <<<EOD
        UPDATE
          tb_yahoo_data_add
        SET
          abstract = REPLACE(:designHTML, '##code##', LOWER(code))
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':designHTML', $designHTML);
      $stmt->execute();
    }

  }

  /**
   * 利用できない文字を削除
   * TODO なんとか効率化できないか。（文字数の回数だけUPDATEが走ってしまうのは豪快すぎる）
   * @param $fromChars
   * @param $to
   */
  private function fixName($fromChars, $to)
  {
    $dbMain = $this->getDb('main');
    $fromCharsArray = preg_split('//u', $fromChars, -1, PREG_SPLIT_NO_EMPTY);

    $stmt = $dbMain->prepare("UPDATE tb_yahoo_data_add SET NAME = REPLACE(name, :fromChar, :toChar)");
    $stmt->bindValue(':toChar', $to);
    foreach($fromCharsArray as $from) {
      $stmt->bindValue('fromChar', $from);
      $stmt->execute();
    }
  }

  /// 空白の変換・除去
  private function fixNameSpaces()
  {
    // Accessでは専用テーブルまで利用し、再帰的に連続空白を除去している。
    // ここの実装は当面は下記で済ませる。 TODO 連続空白除去処理を他での処理に置き換える。

    $dbMain = $this->getDb('main');

    // '全角スペースの置換
    $dbMain->query("UPDATE tb_yahoo_data_add SET name = REPLACE(name, '　', ' ')");

    // 連続スペースを一つに
    $dbMain->query("UPDATE tb_yahoo_data_add SET name = REPLACE(name, '  ', ' ')");
  }


  /**
   * 在庫表データ （prepareData の後に実行）
   * @param $exportTarget
   */
  private function prepareStock($exportTarget)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $logger->info("追加用在庫表データの準備中です");

    $dbMain->query("TRUNCATE tb_yahoo_quantity_add");

    // 2015/12/18 仕様変更：出力する商品分(data_add の出力分) すべて出力
    //                     → tb_yahoo_data_add と連結
    // 2016/08/24 フリー在庫のみをアップ。（予約販売停止）
    $sql = <<<EOD
      INSERT INTO tb_yahoo_quantity_add (
          code
        , `sub-code`
        , quantity
      )
      SELECT
          m.daihyo_syohin_code
        , pci.ne_syohin_syohin_code
        , pci.フリー在庫数
          /*
          CASE
            WHEN (pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0) THEN :quantityMax
            WHEN pci.フリー在庫数 + pci.予約フリー在庫数 > :quantityMax THEN :quantityMax
            ELSE pci.フリー在庫数 + pci.予約フリー在庫数
          END
          */
      FROM tb_mainproducts as m
      INNER JOIN tb_yahoo_data_add AS a ON m.daihyo_syohin_code = a.code /* 出力条件 */
      INNER JOIN tb_productchoiceitems  AS pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal    AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      ORDER BY m.daihyo_syohin_code, pci.並び順No
EOD;
    $stmt = $dbMain->prepare($sql);
    // $stmt->bindValue(':quantityMax', self::QUANTITY_MAX, \PDO::PARAM_INT);
    $stmt->execute();

    // セット商品の在庫数を更新する
    // セット商品は、セット商品としてpciに登録している在庫数ではなく、構成品の在庫数を元に計算する
    $sql = <<<EOD
      UPDATE tb_yahoo_quantity_add a
      INNER JOIN (
        SELECT
            pci.ne_syohin_syohin_code AS set_sku
          , MIN(TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0))  AS creatable_num /* 内訳SKUフリー在庫からの作成可能数 */
        FROM tb_productchoiceitems pci 
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        WHERE m.set_flg <> 0
        GROUP BY set_sku
      ) T ON a.`sub-code` = T.set_sku
      SET a.quantity = T.creatable_num
EOD;
    $dbMain->query($sql);
  }
}
