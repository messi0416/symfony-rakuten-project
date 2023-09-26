<?php
/**
 * 楽天 CSV出力処理
 * User: hirai
 * Date: 2016/01/29
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\RakutenMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Exception\ValidationException;
use phpseclib\Net\SFTP;


/**
 * 楽天CSV出力処理。
 *
 * 楽天plusnao、楽天motto-motto、楽天LaForest、楽天dolcissimo、楽天gekipla用のCSV出力を行う。
 * ・楽天plusnaoは、受発注のみの商品も含め、すべての商品をアップロードする。
 * ・楽天motto-motto、楽天LaForest、楽天dolcissimo、楽天gekiplaは、即納・一部即納のみアップロードする。
 *
 * 大まかな流れは以下の通り。
 *   (1) 別コマンドで事前準備。現在楽天側に登録している商品情報のCSVを、FTP出力させておく。
 *   (2) (1)から1時間程度空ける必要がある。生成されたCSVを取得する。
 *   (3) (2)のCSVをDBに取り込む。item-cat.csv（カテゴリ情報）、item.csv（商品情報）、item-select.csv（SKUと在庫情報）
 *   (4) (3)のデータ（楽天に登録済み商品）と、tb_mainproducts、tb_rakuteninformationなどに登録されている最新の商品情報を比較し
 *       CSVに出力する（販売中のものはすべて出力するが、販売状況によって設定変更などがある）
 *   (5) (4)のCSVをFTPにアップロードする。
 *  生成ファイルの詳細はWiki参照。
 *  http://tk2-217-18298.vs.sakura.ne.jp/projects/forest-sys/wiki/CSV%E5%87%BA%E5%8A%9B%E5%87%A6%E7%90%86_%E6%A5%BD%E5%A4%A9
 *
 * (1)のCSVデータについては、他機能でも使用する部分があるため、(3)のDB保存時、plusnao, motto, laforest, dolcissimo, gekiplaは別々に残す。
 * それ以後、(4)の生成のための一時データを作成するテーブルは、他からは使用されないため個別には作成せず共通とする。
 * 余談だが、楽天Plusnaoのみ最初から存在したため、「楽天」と書かれていれば通常は楽天Plusnaoを指す。
 * plusnaoと書くとYahoo Plusnaoを指すので注意（2021/08時点）。
 *
 * @package BatchBundle\Command
 */
class ExportCsvRakutenCommand extends PlusnaoBaseCommand
{
  // 店舗ごとに固定のもの　initializeProcessで設定
  private $targetShop; // 対象店舗。rakuten|motto|laforest|dolcissimo|gekipla
  private $shopName;   // 店舗名。ログ出力用
  private $ftpParamRoot; // parametars.ymlに定義しているFTP設定のルートキー。ftp_rakuten|ftp_rakuten_motto|ftp_rakuten_laforest|ftp_rakuten_dolcissimo|ftp_rakuten_gekipla
  private $settingKeyPrefix; // tb_setting から値を取得するためのキーのプレフィックス。RAKUTEN|MOTTO|LAFOREST|DOLCISSIMO|GEKIPLA
  private $cabinetUrl; // 楽天Cabinet URL
  private $tableItemDl; // 楽天item.csvのインポートテーブル。他処理でも使うため店舗ごとに分ける
  private $tableSelectDl; // 楽天select.csvのインポートテーブル。他処理でも使うため店舗ごとに分ける
  private $tableCategoryDl; // 楽天category.csvのインポートテーブル。このテーブルは他処理で使わないが、ついでに分ける
  private $tableInformation; // モール別のinformationテーブル。モールごとの情報が全てここに格納されている。
  private $mallDesignTopBannerCode; // tb_mall_designからトップバナーを取得するコード。
  private $targetMallCode; // 再計算処理用 更新モールコード

  private $importPath; // 元データ読み込みディレクトリ。パラメータ指定されればそれ。downloadする場合末尾に$targetShopがつく
  private $exportPath; // CSV出力ディレクトリ、パラメータで指定がなければ、$targetShopが含まれる
  private $skipCommonProcess = false; // 共通処理をスキップ
  private $skipRakutencommonProcess = false; // 楽天内の共通処理をスキップ

  private $exportAll = false;

  private $updateRecordNumberKick; // キック処理の最終実行日時、サイト別
  private $updateRecordNumberProcess; // CSV出力の最終実行日時、サイト別


  /** @var \DateTime */
  private $processStart; // 処理開始日時。処理完了後、前回処理日時として保存する。

  /** @var bool  */
  private $doUpload = true;

  private $results = [];

  /** @var \DateTime */
  private $attentionDateTime; // 注記判定用日時

  private $test;

  const IMPORT_PATH = 'Rakuten/Import';
  const EXPORT_PATH = 'Rakuten/Export';
  const IMPORT_DOWNLOADED_PATH_RAKUTEN_PLUSNAO = 'Rakuten/Downloaded/RakutenPlusnao';
  const IMPORT_DOWNLOADED_PATH_RAKUTEN_MOTTO = 'Rakuten/Downloaded/MottoMotto';
  const IMPORT_DOWNLOADED_PATH_RAKUTEN_LAFOREST = 'Rakuten/Downloaded/LaForest';
  const IMPORT_DOWNLOADED_PATH_RAKUTEN_DOLCISSIMO = 'Rakuten/Downloaded/dolcissimo';
  const IMPORT_DOWNLOADED_PATH_RAKUTEN_GEKIPLA = 'Rakuten/Downloaded/gekipla';



  /** 対象店舗文字列：楽天 */
  const EXPORT_TARGET_RAKUTEN = 'rakuten';

  /** 対象店舗文字列：motto-motto */
  const EXPORT_TARGET_MOTTO = 'motto';

  /** 対象店舗文字列：LaForest */
  const EXPORT_TARGET_LAFOREST = 'laforest';

  /** 対象店舗文字列：dolcissimo */
  const EXPORT_TARGET_DOLCISSIMO = 'dolcissimo';

  /** 対象店舗文字列：gekipla */
  const EXPORT_TARGET_GEKIPLA = 'gekipla';

  const UPLOAD_EXEC_TITLE = '楽天CSV出力処理';

  // アップロードファイルの分割行数
  const UPLOAD_CSV_MAX_NUM = 50000; // 5万行で分割 （item.csv, select.csv, item-cat.csvのみ）

  /* 倉庫に入れないで表示する件数 */
  const NON_WAREHOUSE_LIMIT = 50000;

  // 注記納期計算用
  const ADD_PRICE = 100;
  const ADD_RATE = 1.1;

  // 注記対象日付が変わる時(24時間表記)。深夜バッチなどの場合、この時間より後に起動すると明日扱いとなる。
  const DAY_CHANGE_HOUR = 23;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-rakuten')
      ->setDescription('CSVエクスポート 楽天')
      ->addArgument('export-dir', InputArgument::OPTIONAL, '出力先ディレクトリ', null)
      ->addOption('import-dir', null, InputOption::VALUE_OPTIONAL, 'インポート元ディレクトリ ※指定すればダウンロードなし')
      ->addOption('import-file-datetime', null, InputOption::VALUE_OPTIONAL, 'インポートファイル日時 ※指定がなければ、最終CSVダウンロードキック時間')
      ->addOption('attention-datetime', null, InputOption::VALUE_OPTIONAL, '注記判定日時 ※指定がなければ、実行日時')
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ', 1)
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理をスキップ', '0')
      ->addOption('export-all', null, InputOption::VALUE_OPTIONAL, '全件出力', '0')
      ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。rakuten|motto|laforest|dolcissimo|gekipla')
      ->addOption('skip-rakutencommon-process', null, InputOption::VALUE_OPTIONAL, '楽天との共通的な商品情報更新などをスキップ。楽天plusnao,motto,laforest,dolcissimo,gekiplaの連続実行の時、後の店舗で有効とする', '0')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ->addOption('test', null, InputArgument::OPTIONAL, '本番環境テスト')
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '楽天CSV出力処理';
    $logger = $this->getLogger();

    $this->test = $input->getOption('test');
    if (!empty($this->test)) {
      $logger->debug("TEST SFTP START");
      return;
    }

    $this->targetShop = $input->getOption('target-shop');
    if (!$this->targetShop) {
      $logger->addDbLog($logger->makeDbLog($this->commandName, 'エラー終了', '対象店舗指定なし'));
      throw new BusinessException("[楽天CSV出力処理]対象店舗指定なしのため処理終了");
    } else if ($this->targetShop == self::EXPORT_TARGET_RAKUTEN) {
      $this->commandName = '楽天CSV出力処理[楽天]';
      $this->shopName = '楽天';
      $this->updateRecordNumberKick = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_RAKUTEN_KICK;
      $this->updateRecordNumberProcess = DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_RAKUTEN;
      $this->ftpParamRoot = 'ftp_rakuten';
      $this->settingKeyPrefix = 'RAKUTEN';
      $this->cabinetUrl = RakutenMallProcess::RAKUTEN_CABINET_URL;
      $this->tableItemDl = 'tb_rakutenitem_dl';
      $this->tableSelectDl = 'tb_rakutenselect_dl';
      $this->tableCategoryDl = 'tb_rakutencategory_dl';
      $this->tableInformation = 'tb_rakuteninformation';
      $this->mallDesignTopBannerCode = 'rakuten_sp_top';
      $this->targetMallCode = DbCommonUtil::MALL_CODE_RAKUTEN;
    } else if ($this->targetShop == self::EXPORT_TARGET_MOTTO) {
      $this->commandName = '楽天CSV出力処理[motto]';
      $this->shopName = 'motto-motto';
      $this->updateRecordNumberKick = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_MOTTO_KICK;
      $this->updateRecordNumberProcess = DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_MOTTO;
      $this->ftpParamRoot = 'ftp_rakuten_motto';
      $this->settingKeyPrefix = 'MOTTO';
      $this->cabinetUrl = RakutenMallProcess::RAKUTEN_MOTTO_CABINET_URL;
      $this->tableItemDl = 'tb_rakutenmotto_item_dl';
      $this->tableSelectDl = 'tb_rakutenmotto_select_dl';
      $this->tableCategoryDl = 'tb_rakutenmotto_category_dl';
      $this->tableInformation = 'tb_rakuten_motto_information';
      $this->mallDesignTopBannerCode = 'rakuten_motto_sp_top';
      $this->targetMallCode = DbCommonUtil::MALL_CODE_RAKUTEN_MOTTO;
    } else if ($this->targetShop == self::EXPORT_TARGET_LAFOREST) {
      $this->commandName = '楽天CSV出力処理[laforest]';
      $this->shopName = 'LaForest';
      $this->updateRecordNumberKick = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_LAFOREST_KICK;
      $this->updateRecordNumberProcess = DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_LAFOREST;
      $this->ftpParamRoot = 'ftp_rakuten_laforest';
      $this->settingKeyPrefix = 'LAFOREST';
      $this->cabinetUrl = RakutenMallProcess::RAKUTEN_LAFOREST_CABINET_URL;
      $this->tableItemDl = 'tb_rakutenlaforest_item_dl';
      $this->tableSelectDl = 'tb_rakutenlaforest_select_dl';
      $this->tableCategoryDl = 'tb_rakutenlaforest_category_dl';
      $this->tableInformation = 'tb_rakuten_laforest_information';
      $this->mallDesignTopBannerCode = 'rakuten_laforest_sp_top';
      $this->targetMallCode = DbCommonUtil::MALL_CODE_RAKUTEN_LAFOREST;
    } else if ($this->targetShop == self::EXPORT_TARGET_DOLCISSIMO) {
      $this->commandName = '楽天CSV出力処理[dolcissimo]';
      $this->shopName = 'dolcissimo';
      $this->updateRecordNumberKick = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_DOLCISSIMO_KICK;
      $this->updateRecordNumberProcess = DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_DOLCISSIMO;
      $this->ftpParamRoot = 'ftp_rakuten_dolcissimo';
      $this->settingKeyPrefix = 'DOLCISSIMO';
      $this->cabinetUrl = RakutenMallProcess::RAKUTEN_DOLCISSIMO_CABINET_URL;
      $this->tableItemDl = 'tb_rakutendolcissimo_item_dl';
      $this->tableSelectDl = 'tb_rakutendolcissimo_select_dl';
      $this->tableCategoryDl = 'tb_rakutendolcissimo_category_dl';
      $this->tableInformation = 'tb_rakuten_dolcissimo_information';
      $this->mallDesignTopBannerCode = 'rakuten_dolcissimo_sp_top';
      $this->targetMallCode = DbCommonUtil::MALL_CODE_RAKUTEN_DOLCISSIMO;
    } else if ($this->targetShop == self::EXPORT_TARGET_GEKIPLA) {
      $this->commandName = '楽天CSV出力処理[gekipla]';
      $this->shopName = 'gekipla';
      $this->updateRecordNumberKick = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_GEKIPLA_KICK;
      $this->updateRecordNumberProcess = DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_GEKIPLA;
      $this->ftpParamRoot = 'ftp_rakuten_gekipla';
      $this->settingKeyPrefix = 'GEKIPLA';
      $this->cabinetUrl = RakutenMallProcess::RAKUTEN_GEKIPLA_CABINET_URL;
      $this->tableItemDl = 'tb_rakutengekipla_item_dl';
      $this->tableSelectDl = 'tb_rakutengekipla_select_dl';
      $this->tableCategoryDl = 'tb_rakutengekipla_category_dl';
      $this->tableInformation = 'tb_rakuten_gekipla_information';
      $this->mallDesignTopBannerCode = 'rakuten_gekipla_sp_top';
      $this->targetMallCode = DbCommonUtil::MALL_CODE_RAKUTEN_GEKIPLA;
    } else {
      $logger->addDbLog($logger->makeDbLog($this->commandName, 'エラー終了', "店舗指定不正[" . $this->targetShop . "]"));
      throw new BusinessException("[楽天 インポート用CSV出力準備処理]対象店舗指定不正のため処理終了");
    }
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->test = $input->getOption('test');
    if (!empty($this->test)) {
      $this->downloadImportCsvDataTest($this->test);
      return;
    }

    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();

    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    $this->processStart = new \DateTime();
    $this->doUpload = (bool)$input->getOption('do-upload');
  
    // 出力パス
    $this->exportPath = $input->getArgument('export-dir');
    if (!$this->exportPath) {
      $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $this->targetShop . '/' . $this->processStart->format('YmdHis');
    }
    // 出力ディレクトリ 作成
    $fs = new FileSystem();
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }

    // インポート元ディレクトリ
    // 既存ファイルを利用するときに指定する。（＝ 指定されれば、ファイルのダウンロードは行わない）
    $this->importPath = $input->getOption('import-dir');
    if ($this->importPath && !$fs->exists($this->importPath)) {
      $fs->mkdir($this->importPath);
    }

    // 共通処理スキップフラグ
    $this->skipCommonProcess = boolval($input->getOption('skip-common-process'));
    $this->skipRakutencommonProcess = boolval($input->getOption('skip-rakutencommon-process'));

    // 全件出力フラグ（販売終了も含めて出力）
    $this->exportAll = boolval($input->getOption('export-all'));

    // 注記判定日時
    if ($input->getOption('attention-datetime')) {
      $this->attentionDateTime = new \DateTime($input->getOption('attention-datetime'));
      if (!$this->attentionDateTime) {
        throw new ValidationException('[' . $this->shopName . ']' . '注記日時の指定が不正です。[' . $input->getOption('attention-datetime') . ']');
      }
    } else {
      // 指定されない場合、バッチ起動日時を使用する
      $this->attentionDateTime = clone $this->processStart;
    }
    // cron起動に合わせ、23時以降の場合は次の日扱いとする。
    if (intval($this->attentionDateTime->format("H")) >= self::DAY_CHANGE_HOUR) {
      $this->attentionDateTime = $this->attentionDateTime->modify('+1 day'); 
    }
    $logger->debug("processStart:".$this->processStart->format('Y-m-d H:i:s'));
    $logger->debug("attentionDateTime:".$this->attentionDateTime->format('Y-m-d H:i:s'));

    $fileUtil = $this->getFileUtil();
    $commonUtil = $this->getDbCommonUtil();
    $this->stopwatch->start('main');

    try {
      // CSV出力 データ作成処理 実装
      $dbMain = $this->getDb('main');

      // 楽天商品・カテゴリデータ  ダウンロード処理
      // 取込基準日時の指定があればその日時を利用。
      // なければ事前にRMSでダウンロード準備実行が必要。完了メールが来ていることを前提に実行し、
      //   前回実行時より新しいファイルが無ければエラー終了
      if (!$this->importPath) {
        $baseDateTime = null;
        if ($input->getOption('import-file-datetime')) {
          $baseDateTime = new \DateTime($input->getOption('import-file-datetime'));
          if (!$baseDateTime) {
            throw new \RuntimeException('[' . $this->shopName . ']' . 'インポートファイル日付の指定が不正です。[' . $input->getOption('import-file-datetime') . ']');
          }
        }

        $this->downloadImportCsvData($baseDateTime);
      }

      // 楽天商品・カテゴリデータ インポート処理
      $this->importDownloadData();
      // /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0
      if (!$this->skipCommonProcess) {
        $rakutenCsvOutputDir = $fileUtil->getWebCsvDir() . '/RakutenNokiKanri';
        $commonUtil->exportCsvCommonProcess($logger, $rakutenCsvOutputDir);
        /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }
      // 楽天は店舗ごとに価格計算が必要なため、共通処理をスキップしても価格再計算はスキップしない　（いずれ整理したほうが良い）
      $commonUtil->calculateMallPrice($logger, $this->targetMallCode); 

      // Call Export1___(C_DIR)  ※ 段階アップロード。この段階でアップロードしてしまう。
      $this->exportCsvIntoWarehouse();
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // Call NE更新カラム補正___
      $this->setNeUpdateColumn();
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // Call 楽天タイトル補正___(lc_臨時NO) '★NE更新カラムを補正した後で呼び出すこと！
      $this->fixTitle();
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // Call rakutenカテゴリ登録
      if (!$this->skipRakutencommonProcess) {
        $this->registerRakutenCategory();
        /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }

      // Call rakutenNULL補正___
      if (!$this->skipRakutencommonProcess) {
        $this->fixRakutenNull();
        /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }

      // 倉庫格納フラグ設定
      /** @var RakutenMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.rakuten');
      $processor->setWarehouseStoredFlg($logger, $this->targetShop);
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // Call Export2___(C_DIR)  ※  段階アップロード。 この段階でアップロードしてしまう。
      $this->exportCsvUpdateCategory();
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // Call setRakutenInforamtion___
      $this->updateRakutenInformation($logger);
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      //Call Export3___(C_DIR)  ※  段階アップロード。この段階でアップロードしてしまう。
      $this->exportCsvUpdateItems();
      /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // 注記納期情報 出力・アップロード
      $this->exportAttentionCsv();

      // 在庫確認用テーブル 再作成
      // 楽天 在庫更新CSV出力用。現在は使用していない様子（NextEngineの同期を利用）
      // 仕組みの見直しが必要そう。現在はmotto|laforest|dolcissimo|gekipla対応しない（2021/10/27）
      $logger->addDbLog($logger->makeDbLog(null, '在庫差分テーブル再作成'));
      $dbMain->query('TRUNCATE tb_rakuten_product_stock');
      $sql = <<<EOD
        INSERT INTO tb_rakuten_product_stock (
            `product_code`
          , `colname`
          , `colcode`
          , `rowname`
          , `rowcode`
          , `stock`
        )
        SELECT
            pci.daihyo_syohin_code
          , pci.colname
          , pci.colcode
          , pci.rowname
          , pci.rowcode
          , pci.`フリー在庫数`
        FROM tb_productchoiceitems pci
        INNER JOIN tb_rakutenselect_key k ON pci.ne_syohin_syohin_code = k.ne_syohin_syohin_code
        ORDER BY pci.ne_syohin_syohin_code
EOD;
      $dbMain->query($sql);

      // '受発注可能フラグの復元 ※ Yahooと同様に不要。

      // 結果ファイル確認
      $finder = new Finder();
      $message = '';
      $files = [];
      /** @var SplFileInfo $file */
      foreach($finder->in($this->exportPath)->files() as $file) {
        $files[] = $file->getPathname();
      }
      if (!$files) {
        $message = 'CSVファイルが作成されませんでした。処理を完了します。';
        $this->results['message'] = $message;
      }
      $this->results['files'] = $files;
      $logger->addDbLog($logger->makeDbLog(null, '登録結果')->setInformation($this->results));

      // 速度測定用ログ
      $itemCnt = $this->results['item-3rd.csv'] ?? 0;
      $selectCnt = $this->results['select-update.csv'] ?? 0;
      $attentionCnt = $this->results['select-attention-add.csv'] ?? 0;
      $this->processExecuteLog->setProcessNumber1($itemCnt);
      $this->processExecuteLog->setProcessNumber2($selectCnt);
      $this->processExecuteLog->setProcessNumber3($attentionCnt);
      $this->processExecuteLog->setVersion(1.1);

    } catch (\Exception $e) {

      // 出力ディレクトリが空なら削除しておく
      $fs = new Filesystem();
      if ($this->exportPath && $fs->exists($this->exportPath)) {
        $finder = new Finder();
        if ($finder->in($this->exportPath)->count() == 0) {
          $fs->remove($this->exportPath);
        }
      }
      throw $e;
    }

    // 最終処理日時 更新
    $commonUtil->updateUpdateRecordTable($this->updateRecordNumberProcess, $this->processStart);

    $event = $this->stopwatch->stop('main');
    $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));
  }

  /**
   * 楽天注記納期情報出力
   * 
   * 出力内容を #182468 で全面的に更新。ただし、現在使われていないと思われる注記についても、現時点では念のため末尾に残す
   */
  private function exportAttentionCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'エクスポート（注記納期情報）';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '注記情報作成', '開始'));

    $dbMain->query("TRUNCATE tb_rakutenselect_tmp");
    
    // 全商品対象
    $sql = <<<EOD
      INSERT
      INTO tb_rakutenselect_tmp(
          `項目選択肢用コントロールカラム`
        , `商品管理番号（商品URL）`
        , `選択肢タイプ`
        , `Select/Checkbox用項目名`
        , `Select/Checkbox用選択肢`
      )
      SELECT
          'n' AS 項目選択肢用コントロールカラム
        , LCASE(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
        , 'c' AS `選択肢タイプ`
        , '迅速に発送させて頂くため、ご注文後のキャンセルや発送先、注文内容などのご変更は、ご遠慮頂いております。(注文から30分以内であれば、購入履歴からキャンセル手続きが可能です。)' AS `Select/Checkbox用項目名`
        , '確認しました' AS `Select/Checkbox用選択肢`
      FROM tb_mainproducts AS m
      INNER JOIN (
        SELECT
          DISTINCT daihyo_syohin_code
        FROM tb_rakutenselect_key
      ) k ON m.daihyo_syohin_code = k.daihyo_syohin_code
      ORDER BY m.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    
    // 以下、発送方法ごとにランク付け　ランクが小さいものが優先で注記表示に採用される
    $rankTeikeiagi = 1; // 定形外
    $rankTeikei = 2; // 定形
    $rankYuupacket = 3; // ゆうパケット
    $rankTakuhaibin = 4; // 宅配便
    $rankDefault = 9; // どれにも当てはまらない
    
    $logger->info('[' . $this->commandName . '] 注記情報作成： 発送方法');
    $sql = <<<EOD
      INSERT
      INTO tb_rakutenselect_tmp (
          `項目選択肢用コントロールカラム`
        , `商品管理番号（商品URL）`
        , `選択肢タイプ`
        , `Select/Checkbox用項目名`
        , `Select/Checkbox用選択肢`
      )
      SELECT 
        'n' AS 項目選択肢用コントロールカラム
        , LCASE(daihyo_syohin_code) AS `商品管理番号（商品URL）`
        , 'c' AS `選択肢タイプ`
        -- SKUに配送方法があればそれ、全て未設定なら代表商品の配送方法を採用
        , CASE
            WHEN (MIN(latest_pci_delivery_type) = :rankTeikeigai OR (MIN(latest_pci_delivery_type) = :rankDefault AND product_delivery_type = :rankTeikeigai)) 
              THEN '定形外郵便、着日指定・追跡不可、土日祝日の配達はしないポスト投函です。ポスト投函出来ずご不在の場合は不在票が投函されます。3営業日以内の出荷後、4～10日程度で到着予定です。'
            WHEN (MIN(latest_pci_delivery_type) = :rankTeikei OR (MIN(latest_pci_delivery_type) = :rankDefault AND product_delivery_type = :rankTeikei)) 
              THEN  '定形郵便、着日指定・追跡不可、土日祝日の配達はしないポスト投函です。ポスト投函出来ずご不在の場合は不在票が投函されます。3営業日以内の出荷後、4～10日程度で到着予定です。'
            WHEN (MIN(latest_pci_delivery_type) = :rankYuupacket OR (MIN(latest_pci_delivery_type) = :rankDefault AND product_delivery_type = :rankYuupacket)) 
              THEN 'ゆうパケットの発送予定ですが、予告なく出荷時に発送方法を変更する場合がございます。3営業日以内の出荷後、1～3日程度で到着予定です。'
            WHEN (MIN(latest_pci_delivery_type) = :rankTakuhaibin OR (MIN(latest_pci_delivery_type) = :rankDefault AND product_delivery_type = :rankTakuhaibin)) 
              THEN '宅配便の発送予定ですが、予告なく出荷時に発送方法を変更する場合がございます。3営業日以内の出荷後、1～3日程度で到着予定です。'
          END AS `Select/Checkbox用項目名`
        , '確認しました' AS `Select/Checkbox用選択肢`
      FROM (
        -- 各SKUの配送方法を優先度に変換　優先度が大きい（到着が遅い）ものほど latest_delivery_type を小さい数字とする
        -- 外側のクエリでMIN(latest_delivery_type) を取り、代表商品ごとに、SKUの最遅配送方法と、代表商品の配送方法を取得
        SELECT
          pci.daihyo_syohin_code,
          CASE pci_sd.shipping_group_code
            WHEN :shippingGroupCodeTeikeigai THEN :rankTeikeigai -- 定形外
            WHEN :shippingGroupCodeTeikei THEN :rankTeikei -- 定形郵便
            WHEN :shippingGroupCodeYuuPacket THEN :rankYuupacket -- ゆうパケット
            WHEN :shippingGroupCodeTakuhaibin THEN :rankTakuhaibin -- 宅配便
            ELSE :rankDefault
          END as latest_pci_delivery_type, -- SKUごとの最遅
          CASE m_sd.shipping_group_code
            WHEN :shippingGroupCodeTeikeigai THEN :rankTeikeigai -- 定形外
            WHEN :shippingGroupCodeTeikei THEN :rankTeikei -- 定形郵便
            WHEN :shippingGroupCodeYuuPacket THEN :rankYuupacket -- ゆうパケット
            WHEN :shippingGroupCodeTakuhaibin THEN :rankTakuhaibin -- 宅配便
          END as product_delivery_type -- 代表商品の配送方法
        FROM tb_productchoiceitems pci
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM tb_rakutenselect_key
        ) k ON pci.daihyo_syohin_code = k.daihyo_syohin_code
        LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKU発送方法はない場合があるのでLEFT JOIN
        JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        JOIN tb_shippingdivision m_sd ON m.`送料設定` = m_sd.id
      ) T
      GROUP BY daihyo_syohin_code
      ORDER BY daihyo_syohin_code;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
    $stmt->bindValue(':rankTeikeigai', $rankTeikeiagi, \PDO::PARAM_INT);
    $stmt->bindValue(':rankTeikei', $rankTeikei, \PDO::PARAM_INT);
    $stmt->bindValue(':rankYuupacket', $rankYuupacket, \PDO::PARAM_INT);
    $stmt->bindValue(':rankTakuhaibin', $rankTakuhaibin, \PDO::PARAM_INT);
    $stmt->bindValue(':rankDefault', $rankDefault, \PDO::PARAM_INT);
    $stmt->execute();
    
    // 全商品対象
    // 合計x円(税込)以上でクーポン進呈
    $sql = <<<EOD
      INSERT
      INTO tb_rakutenselect_tmp(
          `項目選択肢用コントロールカラム`
        , `商品管理番号（商品URL）`
        , `選択肢タイプ`
        , `Select/Checkbox用項目名`
        , `Select/Checkbox用選択肢`
      )
      SELECT
          'n' AS 項目選択肢用コントロールカラム
        , LCASE(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
        , 's' AS `選択肢タイプ`
        , CONCAT('合計', :price, '円(税込)以上ご注文で次回使える') AS `Select/Checkbox用項目名`
        , CONCAT(:coupon, '円引きクーポン進呈中') AS `Select/Checkbox用選択肢`
      FROM tb_mainproducts AS m
      INNER JOIN (
        SELECT
          DISTINCT daihyo_syohin_code
        FROM tb_rakutenselect_key
      ) k ON m.daihyo_syohin_code = k.daihyo_syohin_code
      ORDER BY m.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':price', number_format($commonUtil->getSettingValue($this->settingKeyPrefix . '_SELECT_COUPON_AMOUNT')), \PDO::PARAM_STR);
    $stmt->bindValue(':coupon', number_format($commonUtil->getSettingValue($this->settingKeyPrefix . '_SELECT_COUPON_PRICE')), \PDO::PARAM_STR);
    $stmt->execute();

    // GW期間出荷注釈
    // 全商品対象
    $specificPeriodConfirmText = null;
    $specificPeriodConfirmSelector = null;
    if ($this->attentionDateTime >= new \DateTime('2023-04-28 00:00:00') && $this->attentionDateTime < new \DateTime('2023-05-01 00:00:00')){ 
      $specificPeriodConfirmText = "GW中は出荷をお休みします。本日のご購入は5/8以降の出荷になる場合がございます。";
      $specificPeriodConfirmSelector = "5/8以降の出荷了承しました";
    }else if ($this->attentionDateTime >= new \DateTime('2023-05-01 00:00:00') && $this->attentionDateTime < new \DateTime('2023-05-02 00:00:00')){ 
      $specificPeriodConfirmText = "GW中は出荷をお休みします。本日のご購入は5/9以降の出荷になる場合がございます。";
      $specificPeriodConfirmSelector = "5/9以降の出荷了承しました";
    }else if ($this->attentionDateTime >= new \DateTime('2023-05-02 00:00:00') && $this->attentionDateTime < new \DateTime('2023-05-14 00:00:00')){ 
      $specificPeriodConfirmText = "GW中は出荷をお休みします。本日のご購入は5/10以降の出荷になる場合がございます。";
      $specificPeriodConfirmSelector = "5/10以降の出荷了承しました";
    }else if ($this->attentionDateTime >= new \DateTime('2023-08-09 00:00:00') && $this->attentionDateTime < new \DateTime('2023-08-10 00:00:00')){ 
      $specificPeriodConfirmText = "お盆中は出荷をお休みします。本日のご購入は8/18以降の出荷になる場合がございます。";
      $specificPeriodConfirmSelector = "8/18以降の出荷了承しました";
    }else if ($this->attentionDateTime >= new \DateTime('2023-08-10 00:00:00') && $this->attentionDateTime < new \DateTime('2023-08-22 00:00:00')){ 
      $specificPeriodConfirmText = "お盆中は出荷をお休みします。本日のご購入は8/21以降の出荷になる場合がございます。";
      $specificPeriodConfirmSelector = "8/21以降の出荷了承しました";
    }
    $logger->debug("specificPeriodConfirmText:".$specificPeriodConfirmText);
    $logger->debug("specificPeriodConfirmSelector:".$specificPeriodConfirmSelector);

    if ($specificPeriodConfirmText !== null && $specificPeriodConfirmSelector !== null) {
    $sql = <<<EOD
      INSERT
      INTO tb_rakutenselect_tmp(
          `項目選択肢用コントロールカラム`
        , `商品管理番号（商品URL）`
        , `選択肢タイプ`
        , `Select/Checkbox用項目名`
        , `Select/Checkbox用選択肢`
      )
      SELECT
          'n' AS 項目選択肢用コントロールカラム
        , LCASE(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
        , 'c' AS `選択肢タイプ`
        , '{$specificPeriodConfirmText}' AS `Select/Checkbox用項目名`
        , '{$specificPeriodConfirmSelector}' AS `Select/Checkbox用選択肢`
      FROM tb_mainproducts AS m
      INNER JOIN (
        SELECT
          DISTINCT daihyo_syohin_code
        FROM tb_rakutenselect_key
      ) k ON m.daihyo_syohin_code = k.daihyo_syohin_code
      ORDER BY m.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    }

    // これ以降は現在使用無し
    // アウトレット　2022/07現在対象なし
    if ($commonUtil->getSettingValue($this->settingKeyPrefix . '_SELECT_OUTLET')) {
      $logger->info('[' . $this->commandName . '] 注記情報作成：アウトレット');

      $sql = <<<EOD
        INSERT
        INTO tb_rakutenselect_tmp(
          `項目選択肢用コントロールカラム`
          , `商品管理番号（商品URL）`
          , `選択肢タイプ`
          , `Select/Checkbox用項目名`
          , `Select/Checkbox用選択肢`
        )
        SELECT
            'n' AS 項目選択肢用コントロールカラム
          , LCASE(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
          , 's' AS `選択肢タイプ`
          , 'アウトレット商品について' AS `Select/Checkbox用項目名`
          , '確認・了承しました。' AS `Select/Checkbox用選択肢`
        FROM tb_mainproducts AS m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM tb_rakutenselect_key
        ) k ON m.daihyo_syohin_code = k.daihyo_syohin_code
        WHERE cal.outlet <> 0
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
    }

    // 商品到着後レビュー　　2022/07現在OFF
    if ($commonUtil->getSettingValue($this->settingKeyPrefix . '_SELECT_REVIEW')) {
      $logger->info('[' . $this->commandName . '] 注記情報作成：商品到着後レビュー');

      $sql = <<<EOD
        INSERT
        INTO tb_rakutenselect_tmp(
            `項目選択肢用コントロールカラム`
          , `商品管理番号（商品URL）`
          , `選択肢タイプ`
          , `Select/Checkbox用項目名`
          , `Select/Checkbox用選択肢`
        )
        SELECT
            'n' AS 項目選択肢用コントロールカラム
          , LCASE(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
          , 's' AS `選択肢タイプ`
          , '商品到着後レビューを' AS `Select/Checkbox用項目名`
          , CONCAT(
            '書くのでPlusNao価格'
            , TRUNCATE (
              (IFNULL(cal.baika_tnk, 0) * :taxRate)
              , 0
            )
            , '円で購入'
          ) AS `Select/Checkbox用選択肢`
        FROM tb_mainproducts AS m
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM tb_rakutenselect_key
        ) k ON m.daihyo_syohin_code = k.daihyo_syohin_code
        WHERE cal.outlet = 0
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate());
      $stmt->execute();

      $sql = <<<EOD
        INSERT
        INTO tb_rakutenselect_tmp(
          `項目選択肢用コントロールカラム`
          , `商品管理番号（商品URL）`
          , `選択肢タイプ`
          , `Select/Checkbox用項目名`
          , `Select/Checkbox用選択肢`
        )
        SELECT
            'n' AS 項目選択肢用コントロールカラム
          , LCASE(cal.daihyo_syohin_code) AS `商品管理番号（商品URL）`
          , 's' AS `選択肢タイプ`
          , '商品到着後レビューを' AS `Select/Checkbox用項目名`
          , concat(
            '書かないので'
            , TRUNCATE (
              (
                TRUNCATE (
                  (IFNULL(cal.baika_tnk, 0) * :taxRate)
                  , 0
                ) * :addRate
              )
              , - 1
            )
            , '円で購入'
          ) AS `Select/Checkbox用選択肢`
        FROM tb_mainproducts AS m
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM tb_rakutenselect_key
        ) k ON m.daihyo_syohin_code = k.daihyo_syohin_code
        WHERE  cal.outlet = 0
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate());
      $stmt->bindValue(':addRate', self::ADD_RATE);
      $stmt->execute();
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '注記情報作成', '終了'));

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');

    // ---------------------------
    // 注記削除CSV 出力処理
    // tb_rakutenselect_dl にあって、tb_rakutenselect_key にないもの
    // 商品管理番号単位で一部でも更新があった場合、その番号の全ての注記を出力対象とする
    // ---------------------------
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '注記CSV（削除）出力'));

    $sql = <<<EOD
      SELECT
          'd' AS `項目選択肢用コントロールカラム`
        , dl.`商品管理番号（商品URL）`
        , dl.`選択肢タイプ`
        , dl.`Select/Checkbox用項目名` as '項目選択肢項目名'
        , dl.`Select/Checkbox用選択肢` as '項目選択肢'
      FROM {$this->tableSelectDl} dl
      INNER JOIN {$this->tableInformation} i ON dl.`商品管理番号（商品URL）` = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON cal.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE dl.`選択肢タイプ` IN ('s', 'c')
            AND 
              dl.`商品管理番号（商品URL）` in ( 
                SELECT
                    dl.`商品管理番号（商品URL）` 
                FROM
                  {$this->tableSelectDl} dl 
                    LEFT JOIN tb_rakutenselect_tmp t 
                        ON dl.`商品管理番号（商品URL）` = t.`商品管理番号（商品URL）` 
                        AND dl.`Select/Checkbox用項目名` = t.`Select/Checkbox用項目名` 
                        AND dl.`Select/Checkbox用選択肢` = t.`Select/Checkbox用選択肢` 
                        AND dl.`選択肢タイプ` = t.`選択肢タイプ` 
                WHERE
                    dl.`選択肢タイプ` IN ('s', 'c') 
                    AND t.`商品管理番号（商品URL）` IS NULL
                
                UNION

                SELECT
                  t.`商品管理番号（商品URL）` 
                FROM
                  {$this->tableSelectDl} dl 
                    RIGHT JOIN tb_rakutenselect_tmp t 
                        ON dl.`商品管理番号（商品URL）` = t.`商品管理番号（商品URL）` 
                        AND dl.`Select/Checkbox用項目名` = t.`Select/Checkbox用項目名` 
                        AND dl.`Select/Checkbox用選択肢` = t.`Select/Checkbox用選択肢` 
                        AND dl.`選択肢タイプ` = t.`選択肢タイプ` 
                  WHERE
                    t.`選択肢タイプ` IN ('s', 'c') 
                    AND dl.`商品管理番号（商品URL）` IS NULL
              )
          AND i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
        '項目選択肢用コントロールカラム'
        , '商品管理番号（商品URL）'
        , '選択肢タイプ'
        , '項目選択肢項目名'
        , '項目選択肢'
//        , 'Select/Checkbox用項目名'
//        , 'Select/Checkbox用選択肢'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // このファイルは指定行数で分割する
      $files = [];
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/select-attention-del-%02d.csv', $this->exportPath, $fileIndex++);
          $files[] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $this->results['select-attention-del.csv'] = $num;
      $logger->info('[' . $this->commandName . "] select-attention-del.csv: $num 件 / ファイル数: $fileIndex");

      // FTPアップロード ※空になるのを待つ
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'select.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

    } else {
      $logger->info('[' . $this->commandName . '] select-attention-del.csv: 件数が0のためファイルは作成しませんでした。');
    }

    // ---------------------------
    // 注記登録CSV 出力処理
    // tb_rakutenselect_key にあって、tb_rakutenselect_dl にないもの
    // 商品管理番号単位で一部でも更新があった場合、その番号の全ての注記を出力対象とする
    // ---------------------------
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '注記CSV（登録）出力'));

    $sql = <<<EOD
      SELECT
          'n' AS `項目選択肢用コントロールカラム`
        , t.`商品管理番号（商品URL）`
        , t.`選択肢タイプ`
        , t.`Select/Checkbox用項目名` as '項目選択肢項目名'
        , t.`Select/Checkbox用選択肢` as '項目選択肢'
        , CASE WHEN t.`選択肢タイプ` = 'c' THEN 1 ELSE 0 END AS '項目選択肢選択必須'
      FROM tb_rakutenselect_tmp t
      WHERE 
        t.`商品管理番号（商品URL）` in ( 
          SELECT
              t.`商品管理番号（商品URL）` 
          FROM tb_rakutenselect_tmp t
              LEFT JOIN {$this->tableSelectDl} dl 
                  ON t.`商品管理番号（商品URL）` = dl.`商品管理番号（商品URL）` 
                  AND t.`Select/Checkbox用項目名` = dl.`Select/Checkbox用項目名` 
                  AND t.`Select/Checkbox用選択肢` = dl.`Select/Checkbox用選択肢` 
                  AND t.`選択肢タイプ` = dl.`選択肢タイプ` 
          WHERE
              t.`選択肢タイプ` IN ('s', 'c') 
              AND dl.`商品管理番号（商品URL）` IS NULL

          UNION

          SELECT
            dl.`商品管理番号（商品URL）` 
          FROM tb_rakutenselect_tmp t
            RIGHT JOIN {$this->tableSelectDl} dl 
                ON t.`商品管理番号（商品URL）` = dl.`商品管理番号（商品URL）` 
                AND t.`Select/Checkbox用項目名` = dl.`Select/Checkbox用項目名` 
                AND t.`Select/Checkbox用選択肢` = dl.`Select/Checkbox用選択肢` 
                AND t.`選択肢タイプ` = dl.`選択肢タイプ` 
          WHERE
            dl.`選択肢タイプ` IN ('s', 'c') 
            AND t.`商品管理番号（商品URL）` IS NULL
        )
      ORDER BY t.`商品管理番号（商品URL）`, t.ID
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
        '項目選択肢用コントロールカラム'
        , '商品管理番号（商品URL）'
        , '選択肢タイプ'
        , '項目選択肢項目名'
        , '項目選択肢'
        , '項目選択肢選択必須'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // このファイルは指定行数で分割する
      $files = [];
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/select-attention-add-%02d.csv', $this->exportPath, $fileIndex++);
          $files[] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $this->results['select-attention-add.csv'] = $num;
      $logger->info('[' . $this->commandName . "] select-attention-add.csv: $num 件 / ファイル数: $fileIndex");

      // FTPアップロード ※空になるのを待つ
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'select.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

    } else {
      $logger->info('[' . $this->commandName . '] select-attention-add.csv: 件数が0のためファイルは作成しませんでした。');
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * エクスポート 3回目
   * item-3rd.csv, select.csv, item-cat-3rd.csv アップロード処理
   *
   * 最新の販売情報を出力、アップロードする。
   * motto-motto|LaForest|dolcissimo|gekiplaの場合、以下のように処理を行う。
   * ・楽天登録済み商品については、現在販売していないものも含め、削除対象以外は全て最新をアップロードする。
   * ・楽天未登録の商品については、即納・一部即納のみアップロードする。
   * ・出力内容は楽天plusnaoと同一となる。ただし画像アドレスはmotto-motto|LaForest|dolcissimo|gekiplaのものとする。
   */
  private function exportCsvUpdateItems()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'エクスポート（３回目）';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');

    // '====================
    // 'item.csv ((CSV)itemFormat_include_end OR (CSV)itemFormat)
    // '====================
    $dbMain->query("SET SESSION group_CONCAT_max_len = 20480");

    // 表示画像コード（PC, SPともp001～p018 20枚 - 2枚の 18枚）
    $imageCodeListSql = '';
    $tmp = [];
    foreach(RakutenMallProcess::$IMAGE_CODE_LIST as $code) {
      $tmp[] = sprintf("%s", $dbMain->quote($code, \PDO::PARAM_STR));
    }
    if ($tmp) {
      $codes = implode(', ', $tmp);
      $imageCodeListSql = <<<EOD
      WHERE i.code IN ( {$codes} )
EOD;
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'データ取得開始'));

    $params = [];

    // 倉庫格納分を含まない設定の場合は、以下のいずれかに該当するもののみ。
    $addWhereIncludeEnd = '';
    if ( $commonUtil->getSettingValue($this->settingKeyPrefix . '_INCLUDE_WAREHOUSE') == '0'
        && (! $this->exportAll)
        ) {
          $addWhereIncludeEnd = <<<EOD
            AND (
              dl.`商品管理番号（商品URL）` IS NULL
              OR dl.倉庫指定 = '0'
              OR i.warehouse_stored_flg = 0
            )
EOD;
    }
    // motto-motto|LaForest|dolcissimo|gekiplaの場合
    if (
      $this->targetShop === self::EXPORT_TARGET_MOTTO
      || $this->targetShop === self::EXPORT_TARGET_LAFOREST
      || $this->targetShop === self::EXPORT_TARGET_DOLCISSIMO
      || $this->targetShop === self::EXPORT_TARGET_GEKIPLA
    ) {
      $addWhereIncludeEnd .= <<<EOD
            AND (
              dl.`商品管理番号（商品URL）` IS NOT NULL
              OR cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
            )
EOD;
      $params[':deliveryCodeReady'] = TbMainproductsCal::DELIVERY_CODE_READY;
      $params[':deliveryCodeReadyPartially'] = TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY;
    }

    $sql = <<<EOD
      SELECT
        CASE COALESCE(i.NE更新カラム, '')
          WHEN 'v'     THEN 'u'
          WHEN ''      THEN 'u'
          WHEN 'n'     THEN 'n'
        END                         AS コントロールカラム
      , LCASE(m.daihyo_syohin_code) AS 商品管理番号（商品URL）
      , m.daihyo_syohin_code        AS 商品番号
      , d.楽天ディレクトリID          AS 全商品ディレクトリID
      /* , COALESCE(tag.タグID, 0)      AS タグID */
      , ''                          AS タグID /* #47011 2018/09/03 しばらく手動運用のため、いったん空出力 */
      , i.PC用キャッチコピー          AS PC用キャッチコピー
      , i.モバイル用キャッチコピー     AS モバイル用キャッチコピー
      , i.商品名                     AS 商品名
      , i.baika_tanka               AS 販売価格
      , CASE i.NE更新カラム
          WHEN 'n' THEN NULL
          ELSE i.表示価格
        END                         AS 表示価格
      , '0'                         AS 消費税
      , '1'                         AS 送料
      , ''                          AS 個別送料 /* getkobetuPostageKbnRakutenCsv(m.送料設定) の中身は何も処理していなかった*/
      , NULL                        AS 送料区分1
      , NULL                        AS 送料区分2
      , 0                           AS 代引料
      , i.warehouse_stored_flg      AS 倉庫指定
      , '2'                         AS 商品情報レイアウト
      , '1'                         AS 注文ボタン
      , '0'                         AS 資料請求ボタン
      , '1'                         AS 商品問い合わせボタン
      , '1'                         AS 再入荷お知らせボタン
/*      , '1'                         AS モバイル表示 */
      , '0'                         AS のし対応
      , ''                          AS PC用商品説明文             /* e.RPC用商品説明文_PC */
/*      , i.Rモバイル用商品説明文      AS モバイル用商品説明文 */
      , ''                          AS スマートフォン用商品説明文 /* e.RPC用商品説明文_SP */
      , ''                          AS PC用販売説明文            /* e.RPC用販売説明文 */
      , i.商品画像URL               AS 商品画像URL
      , i.商品画像名（ALT）         AS 商品画像名（ALT）
      , NULL                        AS 動画
      , CASE i.NE更新カラム
          WHEN 'n' THEN NULL
          ELSE i.sales_period
        END                         AS 販売期間指定
      , CASE cal.maxbuynum
          WHEN 0 THEN '-1'
          ELSE cal.maxbuynum
        END                         AS 注文受付数
      , '2'                         AS 在庫タイプ
      , NULL                        AS 在庫数
      , NULL                        AS 在庫数表示
      , m.横軸項目名                AS 項目選択肢別在庫用横軸項目名
      , m.縦軸項目名                AS 項目選択肢別在庫用縦軸項目名
      , 20                          AS 項目選択肢別在庫用残り表示閾値
      , NULL                        AS RAC番号
      , NULL                        AS 闇市パスワード
      , NULL                        AS カタログID
      , NULL                        AS 在庫戻しフラグ
      , NULL                        AS 在庫切れ時の注文受付
      , NULL                        AS 在庫あり時納期管理番号
      , NULL                        AS 在庫切れ時納期管理番号
      , NULL                        AS 予約商品発売日
      , NULL                        AS ポイント変倍率
      , NULL                        AS ポイント変倍率適用期間
      , 'テラリズム２'              AS ヘッダー・フッター・レフトナビ
      , NULL                        AS 表示項目の並び順
      , NULL                        AS 共通説明文（小）
      , NULL                        AS 目玉商品
      , NULL                        AS 共通説明文（大）
      , i.レビュー本文表示          AS レビュー本文表示
      , NULL                        AS あす楽配送管理番号
      , CASE COALESCE(i.NE更新カラム, '')
          WHEN 'v'     THEN '0'
          WHEN ''      THEN '0'
          WHEN 'n'     THEN ''
        END                         AS 海外配送管理番号
      , '0'                         AS サイズ表リンク
      , NULL                        AS 医薬品説明文
      , NULL                        AS 医薬品注意事項
      , CASE i.NE更新カラム
          WHEN 'n' THEN NULL
          ELSE i.二重価格文言管理番号
        END                         AS 二重価格文言管理番号
      , '3'                         AS カタログIDなしの理由
      , NULL                        AS 配送方法セット管理番号
      , NULL                        AS 白背景画像URL
      , NULL                        AS メーカー提供情報表示


      /* 生CSVデータ ↑ まで。以下、整形用データ */
      , i.NE更新カラム
      , COALESCE(i.input_PC販売説明文, '') AS input_PC販売説明文
      , COALESCE(i.input_PC商品説明文, '') AS input_PC商品説明文
      , COALESCE(i.input_SP商品説明文, '') AS input_SP商品説明文

      , image.images                      AS images
      , images_amazon.image_amazon        AS image_amazon
      , i.旧楽天P説明
      , m.商品コメントPC
      , m.サイズについて
      , m.カラーについて
      , m.素材について
      , m.ブランドについて
      , m.使用上の注意
      , m.補足説明PC
      , m.`送料設定`
      , i.cat_list_html
      , m.価格非連動チェック
      , m.手動ゲリラSALE
      , e.ALT用商品名 /* これを（作成時の補正タイトルから）なくせば、_edit テーブルは不要となる、はず。 */

    FROM tb_mainproducts AS m
    INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
    INNER JOIN {$this->tableInformation}  AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
    INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
    INNER JOIN tb_title_parts             AS tp  ON m.daihyo_syohin_code = tp.daihyo_syohin_code
    INNER JOIN tb_rakuteninformation_edit AS e   ON m.daihyo_syohin_code = e.daihyo_syohin_code
    LEFT JOIN {$this->tableItemDl}   AS dl  ON m.daihyo_syohin_code = dl.商品管理番号（商品URL）
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
    LEFT JOIN (
      SELECT
        ia.daihyo_syohin_code ,
        CONCAT( ia.directory ,
        '/' ,
        ia.filename ) AS image_amazon
      FROM
        product_images_amazon ia
      WHERE
        ia.code = 'amazonMain'
    ) AS images_amazon ON m.daihyo_syohin_code = images_amazon.daihyo_syohin_code
    WHERE i.registration_flg <> 0
      AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
      AND cal.deliverycode <> :deliveryCodeTemporary
      AND COALESCE(i.baika_tanka, 0) > 0
    {$addWhereIncludeEnd}
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'データ取得終了'));

    // 出力
    if ($stmt->rowCount()) {

      /** @var \Twig_Environment $twig */
      $twig = $this->getContainer()->get('twig');

      $templateDescriptionPc = $twig->load("BatchBundle:ExportCsvRakuten:{$this->targetShop}-description-pc.html.twig");
      $templateDescriptionSp = $twig->load("BatchBundle:ExportCsvRakuten:{$this->targetShop}-description-sp.html.twig");
      $templateSalesDescriptionPc = $twig->load("BatchBundle:ExportCsvRakuten:{$this->targetShop}-sales-description-pc.html.twig");

      // 出力設定値
      $exportSettings = [
          $this->settingKeyPrefix . '_DESC_OUTLET' => $commonUtil->getSettingValue($this->settingKeyPrefix . '_DESC_OUTLET')
          , $this->settingKeyPrefix . '_GUERRILLA_SALE_URL' => $commonUtil->getSettingValue($this->settingKeyPrefix . '_GUERRILLA_SALE_URL')
          , $this->settingKeyPrefix . '_GUERRILLA_SALE_PIC_URL' => $commonUtil->getSettingValue($this->settingKeyPrefix . '_GUERRILLA_SALE_PIC_URL')
      ];

      // スマートフォン商品情報上部
      $topBannerHtml = '';
      $sql = <<<EOD
      SELECT * FROM tb_mall_design d WHERE d.code = '{$this->mallDesignTopBannerCode}'
EOD;
      $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);
      if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
        $topBannerHtml = $tmp['design_html'];
      }

      // ヘッダ
      $headers = [
          'コントロールカラム'
        , '商品管理番号（商品URL）'
        , '商品番号'
        , '全商品ディレクトリID'
        , 'タグID'
        , 'PC用キャッチコピー'
        , 'モバイル用キャッチコピー'
        , '商品名'
        , '販売価格'
        , '表示価格'
        , '消費税'
        , '送料'
        , '個別送料'
        , '送料区分1'
        , '送料区分2'
        , '代引料'
        , '倉庫指定'
        , '商品情報レイアウト'
        , '注文ボタン'
        , '資料請求ボタン'
        , '商品問い合わせボタン'
        , '再入荷お知らせボタン'
//        , 'モバイル表示'
        , 'のし対応'
        , 'PC用商品説明文'
//        , 'モバイル用商品説明文'
        , 'スマートフォン用商品説明文'
        , 'PC用販売説明文'
        , '商品画像URL'
        , '商品画像名（ALT）'
        , '動画'
        , '販売期間指定'
        , '注文受付数'
        , '在庫タイプ'
        , '在庫数'
        , '在庫数表示'
        , '項目選択肢別在庫用横軸項目名'
        , '項目選択肢別在庫用縦軸項目名'
        , '項目選択肢別在庫用残り表示閾値'
        , 'RAC番号'
        , '闇市パスワード'
        , 'カタログID'
        , '在庫戻しフラグ'
        , '在庫切れ時の注文受付'
        , '在庫あり時納期管理番号'
        , '在庫切れ時納期管理番号'
        , '予約商品発売日'
        , 'ポイント変倍率'
        , 'ポイント変倍率適用期間'
        , 'ヘッダー・フッター・レフトナビ'
        , '表示項目の並び順'
        , '共通説明文（小）'
        , '目玉商品'
        , '共通説明文（大）'
        , 'レビュー本文表示'
        , 'あす楽配送管理番号'
        , '海外配送管理番号'
        , 'サイズ表リンク'
        , '医薬品説明文'
        , '医薬品注意事項'
        , '二重価格文言管理番号'
        , 'カタログIDなしの理由'
        , '配送方法セット管理番号'
        , '白背景画像URL'
        , 'メーカー提供情報表示'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // なぜかダブルクォートで囲まないフィールド ※ Access出力との一致確認用。本番ではすべて囲っておいた方が無難かも。
      $noEncloseFields = [
        '販売価格'
      ];

      // データ
      $num = 0;
      $count = 0;
      // このファイルは指定行数で分割する
      $files = [];
      $fileIndex = 0;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $fileIndex++;
          $filePath = sprintf('%s/item-3rd-%02d.csv', $this->exportPath, $fileIndex);
          $files[] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);

          $logger->info('[' . $this->commandName . '] csv output: ' . $filePath);
        }

        // HTML作成、画像URL作成、各種変換処理
        $this->convertCsvContents($row, [
            'templateDescriptionPc'       => $templateDescriptionPc
          , 'templateDescriptionSp'       => $templateDescriptionSp
          , 'templateSalesDescriptionPc'  => $templateSalesDescriptionPc
          , 'settings'                    => $exportSettings
          , 'topBannerHtml'               => $topBannerHtml
        ]);

        $line = $stringUtil->convertArrayToCsvLine($row, $headers, $noEncloseFields);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $this->results['item-3rd.csv'] = $num;
      $logger->info('[' . $this->commandName . "] item-3rd.csv: $num 件 / ファイル数: $fileIndex");

      // item-3rd.csv (=> item.csv) FTPアップロード ※空になるのを待つ
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'item.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

    } else {
      $logger->info('[' . $this->commandName . '] item-3rd.csv: 件数が0のためファイルは作成しませんでした。');
    }

    // '====================
    // 'select.csv ( (CSV)selectFormat )
    // '====================
    // 即納のみの在庫数出力とし、予約在庫は0として出力するか　現時点ではmotto|laforest|dolcissimo|gekiplaは常に即納のみ
    $noReserveOnly = ($commonUtil->getSettingValue($this->settingKeyPrefix . '_CSV_NO_RESERVED_STOCK') != '0');

    // 楽天の即納のみ、motto|laforest|dolcissimo|gekipla
    if (
      $noReserveOnly
      || $this->targetShop == self::EXPORT_TARGET_MOTTO
      || $this->targetShop == self::EXPORT_TARGET_LAFOREST
      || $this->targetShop == self::EXPORT_TARGET_DOLCISSIMO
      || $this->targetShop == self::EXPORT_TARGET_GEKIPLA
    ) {
      $partSql = <<<EOD
        , pci.フリー在庫数                     AS 項目選択肢別在庫用在庫数
        , '1'                                 AS 在庫あり時納期管理番号
EOD;
      
      $partUpdateSql = <<<EOD
        k.stock = pci.フリー在庫数
        , k.在庫あり時納期管理番号 = 1
EOD;
      
      // 通常 ※現在こちらの切り替えを想定されていなそう　この選択肢は削除をご相談したほうが良いかもしれない
    } else {
      $partSql = <<<EOD
        , CASE WHEN (pci.受発注可能フラグ <> 0 OR cal.受発注可能フラグ退避F <> 0)
              THEN 9999
              ELSE pci.在庫数
          END                                AS 項目選択肢別在庫用在庫数
        , pci.在庫あり時納期管理番号           AS 在庫あり時納期管理番号
EOD;
      $partUpdateSql = <<<EOD
        k.stock = CASE WHEN (pci.受発注可能フラグ <> 0 OR cal.受発注可能フラグ退避F <> 0)
                    THEN 9999 ELSE pci.在庫数
                  END
        , k.在庫あり時納期管理番号 = pci.在庫あり時納期管理番号
EOD;
    }

    // ====================
    // 注記納期CSV 出力のため、SKU情報を同期（挿入）。ここで削除・挿入が完了し、CSVアップロード後の楽天とSKUが一致する。
    // また、これにより在庫差分および納期もチェックして出力する。
    // また、出品フラグOFFの商品は除外する。
    // まず通常商品を登録し、その次にセット商品を登録する（セット商品は、構成品のフリー在庫数を元にstockを計算する）
    // ====================
    
    // tb_rakutenselect_key 登録済みのデータ（楽天・plusnao双方に登録済み）について、在庫数を出力する内容に更新
    $sql = <<<EOD
       UPDATE tb_rakutenselect_key k
       JOIN tb_productchoiceitems pci ON k.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
       JOIN tb_mainproducts cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
       SET {$partUpdateSql}
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    
    // 更新商品SKU 挿入　楽天、plusnao双方で一致していないSKUはtb_rakutenselect_keyになので、ここで登録する

    // motto-motto|LaForest|dolcissimo|gekiplaの場合は即納・一部即納、または登録済み商品のみ
    $params = [];
    $addWhereIncludeEnd = '';
    $addJoinIncludeEnd = '';
    if (
      $this->targetShop === self::EXPORT_TARGET_MOTTO
      || $this->targetShop === self::EXPORT_TARGET_LAFOREST
      || $this->targetShop === self::EXPORT_TARGET_DOLCISSIMO
      || $this->targetShop === self::EXPORT_TARGET_GEKIPLA
    ) {
      $addJoinIncludeEnd = <<<EOD
        LEFT JOIN {$this->tableItemDl} dl ON dl.商品管理番号（商品URL） = m.daihyo_syohin_code
EOD;
      $addWhereIncludeEnd = <<<EOD
        AND (
            cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
            OR dl.商品管理番号（商品URL） IS NOT NULL
           )
EOD;
      $params[':deliveryCodeReady'] = TbMainproductsCal::DELIVERY_CODE_READY;
      $params[':deliveryCodeReadyPartially'] = TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY;
    }

    $sql = <<<EOD
      INSERT IGNORE INTO tb_rakutenselect_key (
          daihyo_syohin_code
        , ne_syohin_syohin_code
        , colname
        , rowname
        , stock
        , 在庫あり時納期管理番号
        , is_new
      )
      SELECT
          daihyo_syohin_code
        , ne_syohin_syohin_code
        , colname
        , rowname
        , 項目選択肢別在庫用在庫数 AS stock
        , 在庫あり時納期管理番号
        , -1 AS is_new
      FROM (
        SELECT
            m.daihyo_syohin_code
          , pci.ne_syohin_syohin_code
          , pci.colname
          , pci.rowname
          {$partSql} /* stock, 納期管理番号 */
        FROM tb_productchoiceitems     AS pci
        INNER JOIN tb_mainproducts     AS m   ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN {$this->tableInformation} AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        {$addJoinIncludeEnd}
        WHERE i.NE更新カラム IN ('v', 'n')
          AND i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          {$addWhereIncludeEnd}
      ) T
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    // セット商品の在庫数を更新する
    // セット商品は、セット商品としてpciに登録している在庫数ではなく、構成品の在庫数を元に計算する
    $sql = <<<EOD
    UPDATE tb_rakutenselect_key k
    JOIN (
      SELECT
          pci.daihyo_syohin_code                      AS daihyo_syohin_code
        , pci.ne_syohin_syohin_code                   AS set_sku
        , MIN(TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0)) AS creatable_num /* 内訳SKUフリー在庫からの作成可能数 */
      FROM tb_productchoiceitems pci
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
      INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
      WHERE m.set_flg <> 0
      GROUP BY daihyo_syohin_code, set_sku
    ) T ON k.ne_syohin_syohin_code = T.set_sku
    SET k.stock = T.creatable_num
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // CSVデータ出力

    $sql = <<<EOD
      SELECT
          CASE
            WHEN is_new = 0 THEN 'u'
            ELSE 'n'
          END AS 項目選択肢用コントロールカラム
        , LCASE(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
        , 'i'                         AS 選択肢タイプ
        , NULL                        AS `Select/Checkbox用項目名`
        , NULL                        AS `Select/Checkbox用選択肢`
        , pci.colname                 AS 項目選択肢別在庫用横軸選択肢
        , pci.colcode                 AS 項目選択肢別在庫用横軸選択肢子番号
        , pci.rowname                 AS 項目選択肢別在庫用縦軸選択肢
        , pci.rowcode                 AS 項目選択肢別在庫用縦軸選択肢子番号
        , '0'                         AS 項目選択肢別在庫用取り寄せ可能表示
        , k.stock                     AS 項目選択肢別在庫用在庫数
        , '0'                         AS 在庫戻しフラグ
        , '0'                         AS 在庫切れ時の注文受付
        , k.在庫あり時納期管理番号    AS 在庫あり時納期管理番号
        , NULL                        AS 在庫切れ時納期管理番号
        /* , COALESCE(tag.タグID, 0)      AS タグID */
        , ''                          AS タグID /* #47011 2018/09/03 しばらく手動運用のため、いったん空出力 */
      FROM tb_productchoiceitems     AS pci
      INNER JOIN tb_mainproducts     AS m   ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_rakutenselect_key AS k  ON pci.ne_syohin_syohin_code = k.ne_syohin_syohin_code
      INNER JOIN {$this->tableInformation} AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN {$this->tableSelectDl} dl ON dl.商品管理番号（商品URL） = m.daihyo_syohin_code 
        AND dl.項目選択肢別在庫用横軸選択肢子番号 = pci.colcode AND dl.項目選択肢別在庫用縦軸選択肢子番号 = pci.rowcode
      /* LEFT JOIN v_rakuten_tag_string_productchoiceitems tag ON pci.ne_syohin_syohin_code = tag.ne_syohin_syohin_code */
      WHERE (
               i.NE更新カラム IN ('v', 'n')
            OR dl.在庫あり時納期管理番号 <> 1 /* 現在は在庫あり時納期管理番号は1固定。予約販売が復活したら修正 */
            OR dl.項目選択肢別在庫用在庫数 <> k.stock
        )
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND i.warehouse_stored_flg = 0
      ORDER BY
          LCASE(m.daihyo_syohin_code)
        , pci.並び順No
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $stmt->execute();
    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
        '項目選択肢用コントロールカラム'
        , '商品管理番号（商品URL）'
        , '選択肢タイプ'
        , '項目選択肢項目名'
        , '項目選択肢'
//        , 'Select/Checkbox用項目名'
//        , 'Select/Checkbox用選択肢'
        , '項目選択肢別在庫用横軸選択肢'
        , '項目選択肢別在庫用横軸選択肢子番号'
        , '項目選択肢別在庫用縦軸選択肢'
        , '項目選択肢別在庫用縦軸選択肢子番号'
        , '項目選択肢別在庫用取り寄せ可能表示'
        , '項目選択肢別在庫用在庫数'
        , '在庫戻しフラグ'
        , '在庫切れ時の注文受付'
        , '在庫あり時納期管理番号'
        , '在庫切れ時納期管理番号'
        , 'タグID'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // このファイルは指定行数で分割する
      $files = [];
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/select-update-%02d.csv', $this->exportPath, $fileIndex++);
          $files[] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $this->results['select-update.csv'] = $num;
      $logger->info('[' . $this->commandName . "] select-update.csv: $num 件 / ファイル数: $fileIndex");

      // FTPアップロード ※空になるのを待つ
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'select.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

    } else {
      $logger->info('[' . $this->commandName . '] select.csv: 件数が0のためファイルは作成しませんでした。');
    }


    // '====================
    // 'item-cat.csv ((CSV)item-catFormat)
    // '====================
    // motto-motto|LaForest|dolcissimo|gekiplaの場合は即納・一部即納のみ
    $params = [];
    $addWhereIncludeEnd = '';
    $addFromIncludeEnd = '';
    if (
      $this->targetShop === self::EXPORT_TARGET_MOTTO
      || $this->targetShop === self::EXPORT_TARGET_LAFOREST
      || $this->targetShop === self::EXPORT_TARGET_DOLCISSIMO
      || $this->targetShop === self::EXPORT_TARGET_GEKIPLA
    ) {
      $addFromIncludeEnd = "LEFT JOIN {$this->tableItemDl} idl ON idl.商品管理番号（商品URL） = cal.daihyo_syohin_code";
      $addWhereIncludeEnd = <<<EOD
        AND (cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially) OR idl.商品管理番号（商品URL） IS NOT NULL)
EOD;
      $params[':deliveryCodeReady'] = TbMainproductsCal::DELIVERY_CODE_READY;
      $params[':deliveryCodeReadyPartially'] = TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY;
    }

    $sql = <<<EOD
      SELECT
          'n' AS コントロールカラム
        , LCASE(c.daihyo_syohin_code) AS 商品管理番号（商品URL）
        , c.表示先カテゴリ
        , 999999999 - cal.priority AS 優先度
        , c.1ページ複数形式
      FROM {$this->tableInformation}   AS i
      INNER JOIN tb_mainproducts_cal   AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_rakutencategory    AS c   ON c.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT  JOIN tb_delete_excluded_products e ON e.mall_id = :mallIdRakuten
                                              AND i.daihyo_syohin_code = e.syohin_code
      LEFT  JOIN {$this->tableCategoryDl} AS dl
                  ON c.表示先カテゴリ = dl.表示先カテゴリ
                  AND c.daihyo_syohin_code = dl.商品管理番号（商品URL）
      {$addFromIncludeEnd}
      WHERE (
                  i.registration_flg <> 0
               OR e.syohin_code IS NOT NULL /* 消さなければ本来こちらはいらないはずだが、消えちゃっているので復活のため。 */
            )
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND (
             c.表示先カテゴリ <> dl.表示先カテゴリ
          OR 999999999 - cal.priority <> dl.優先度
          OR dl.表示先カテゴリ IS NULL
        )
        {$addWhereIncludeEnd}
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallIdRakuten', DbCommonUtil::MALL_ID_RAKUTEN, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
        'コントロールカラム'
        , '商品管理番号（商品URL）'
        , '表示先カテゴリ'
        , '優先度'
        , '1ページ複数形式'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // item-cat-3rd.csvは指定行数で分割する
      $files = [];
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/item-cat-3rd-%02d.csv', $this->exportPath, $fileIndex++);
          $files[] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $this->results['item-cat-3rd.csv'] = $num;
      $logger->info('[' . $this->commandName . "] item-cat-3rd.csv: $num 件");

      // item-cat-3rd.csv(=> item-cat.csv) FTPアップロード  ※空になるのを待たない
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'item-cat.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

    } else {
      $logger->info('[' . $this->commandName . '] item-cat-3rd.csv: 件数が0のためファイルは作成しませんでした。');
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * 楽天モール別情報テーブル 更新
   * setRakutenInforamtion___()
   */
  private function updateRakutenInformation(BatchLogger $logger)
  {
    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');

    // ------------------------------
    // Call パンくずリスト作成___
    // ------------------------------
    $processor->createPankuzuList($this->targetShop, $logger);

    // ------------------------------
    // Call レビュー本文表示設定___
    // ------------------------------
    $processor->setReviewDisplay($this->targetShop);

    // ------------------------------
    // Call 販売期間指定___
    // ------------------------------
    if (!$this->skipRakutencommonProcess) {
      $processor->setSalePeriod();
    }
  }


  /**
   * エクスポート 2回目
   * item-cat.csv, item-2nd.csv アップロード処理
   * ・item-cat.csv 新商品カテゴリになく、楽天商品カテゴリにある組み合わせを削除する。ただし「その他」は削除しない。
   * ・item-2nd.csv SKUに変更があった商品について、いったん通常商品に戻すことでSKU情報をリセット（削除）する。
   * どちらも楽天登録済み商品の更新のため、deliverycodeによる絞り込みは不要
   */
  private function exportCsvUpdateCategory()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'エクスポート（２回目）';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');

    // '====================
    // 'item-cat.csv ((CSV)item-catFormat_Initialization)
    // '====================
    $sql = <<<EOD
      SELECT
          'd' AS コントロールカラム
        , dl.商品管理番号（商品URL）
        , dl.表示先カテゴリ
      FROM tb_mainproducts_cal AS cal
      INNER JOIN {$this->tableInformation} AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN (
          {$this->tableCategoryDl} AS dl
          LEFT JOIN tb_rakutencategory AS cate
              ON  dl.表示先カテゴリ = cate.表示先カテゴリ
              AND dl.`商品管理番号（商品URL）` = cate.daihyo_syohin_code
      ) ON cal.daihyo_syohin_code = dl.`商品管理番号（商品URL）`
      LEFT  JOIN tb_delete_excluded_products e ON e.mall_id = :mallIdRakuten
                                              AND cal.daihyo_syohin_code = e.syohin_code
      WHERE dl.表示先カテゴリ <> "その他"
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND (
             cate.daihyo_syohin_code IS NULL
          OR dl.優先度 <> (999999999 - cal.priority)
        )
        AND e.syohin_code IS NULL
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallIdRakuten', DbCommonUtil::MALL_ID_RAKUTEN, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
        'コントロールカラム'
        , '商品管理番号（商品URL）'
        , '表示先カテゴリ'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // item-cat.csvは指定行数で分割する
      $files = [];
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/item-cat-%02d.csv', $this->exportPath, $fileIndex++);
          $files[] = $filePath;
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      // item-cat.csv アップロード処理 （空になるまで待ってアップ）
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'item-cat.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

      $this->results['item-cat.csv'] = $num;
      $logger->info('[' . $this->commandName . "] item-cat.csv: $num 件");

    } else {
      $logger->info('[' . $this->commandName . '] item-cat.csv: 件数が0のためファイルは作成しませんでした。');
    }

    // '====================
    // 'item.csv ((CSV)itemFormat_Initialization)
    // この出力は、「在庫タイプ」を「1:通常在庫」にして、select群を一度リセットするための物。
    // 従って、項目はほぼ不要。
    // '====================

    $sql = <<<EOD
      SELECT
          'u'                         AS コントロールカラム
        , LCASE(m.daihyo_syohin_code) AS 商品管理番号（商品URL）
        , '1'                         AS 在庫タイプ
        , '0'                         AS 在庫数
        , '0'                         AS 在庫数表示
      FROM tb_mainproducts AS m
      INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
      INNER JOIN {$this->tableInformation}  AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_title_parts             AS tp  ON m.daihyo_syohin_code = tp.daihyo_syohin_code
      INNER JOIN tb_rakuteninformation_edit AS e   ON m.daihyo_syohin_code = e.daihyo_syohin_code

      WHERE i.NE更新カラム = 'v'
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND i.warehouse_stored_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
        'コントロールカラム'
        , '商品管理番号（商品URL）'
        /*
        , '商品番号'
        , '全商品ディレクトリID'
        , 'タグID'
        , 'PC用キャッチコピー'
        , 'モバイル用キャッチコピー'
        , '商品名'
        , '販売価格'
        , '表示価格'
        , '消費税'
        , '送料'
        , '個別送料'
        , '送料区分1'
        , '送料区分2'
        , '代引料'
        , '倉庫指定'
        , '商品情報レイアウト'
        , '注文ボタン'
        , '資料請求ボタン'
        , '商品問い合わせボタン'
        , '再入荷お知らせボタン'
        , 'モバイル表示'
        , 'のし対応'
        , 'PC用商品説明文'
        , 'モバイル用商品説明文'
        , 'スマートフォン用商品説明文'
        , 'PC用販売説明文'
        , '商品画像URL'
        , '商品画像名（ALT）'
        , '動画'
        , '販売期間指定'
        , '注文受付数'
        */
        , '在庫タイプ'
        , '在庫数'
        , '在庫数表示'
        /*
        , '項目選択肢別在庫用横軸項目名'
        , '項目選択肢別在庫用縦軸項目名'
        , '項目選択肢別在庫用残り表示閾値'
        , 'RAC番号'
        , '闇市パスワード'
        , 'カタログID'
        , '在庫戻しフラグ'
        , '在庫切れ時の注文受付'
        , '在庫あり時納期管理番号'
        , '在庫切れ時納期管理番号'
        , '予約商品発売日'
        , 'ポイント変倍率'
        , 'ポイント変倍率適用期間'
        , 'ヘッダー・フッター・レフトナビ'
        , '表示項目の並び順'
        , '共通説明文（小）'
        , '目玉商品'
        , '共通説明文（大）'
        , 'レビュー本文表示'
        , 'あす楽配送管理番号'
        , '海外配送管理番号'
        , 'サイズ表リンク'
        , '二重価格文言管理番号'
        */
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // なぜかダブルクォートで囲まないフィールド ※ Access出力との一致確認用。本番ではすべて囲っておいた方が無難かも。
      $noEncloseFields = [
        'タグID'
        , '販売価格'
      ];

      // データ
      $num = 0;
      $filePath = sprintf('%s/item-2nd.csv', $this->exportPath);
      $fp = fopen($filePath, 'wb');
      fputs($fp, $headerLine);

      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $line = $stringUtil->convertArrayToCsvLine($row, $headers, $noEncloseFields);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
      }
      fclose($fp);

      $this->results['item-2nd.csv'] = $num;
      $logger->info('[' . $this->commandName . '] item-2nd.csv: $num 件');

      // item-2nd.csv(=> item.csv) アップロード処理 ※こちらは空を待たない
      if ($this->doUpload) {
        $processor->enqueueUploadCsv($filePath, 'item.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }

      // ====================
      // 注記納期CSV 出力のため、SKU情報を同期（削除）
      // ====================
      // 更新商品SKU 削除
      $sql = <<<EOD
      DELETE k
      FROM tb_rakutenselect_key k
      INNER JOIN tb_mainproducts m ON k.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON k.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN {$this->tableInformation} i ON k.daihyo_syohin_code = i.daihyo_syohin_code
      /* item.csvと同一条件 */
      WHERE i.NE更新カラム = 'v'
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
      $stmt->execute();

    } else {
      $logger->info('[' . $this->commandName . '] item-2nd.csv: 件数が0のためファイルは作成しませんでした。');
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * rakuten NULL補正
   */
  private function fixRakutenNull()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'NULL補正';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $logger->info('[' . $this->commandName . '] 画像名(ALT)の\'\'をNULLに変換しています。'); // ※不要では？
    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 商品画像M1Caption = NULL
      WHERE 商品画像M1Caption = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 商品画像M2Caption = NULL
      WHERE 商品画像M2Caption = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 商品画像M3Caption = NULL
      WHERE 商品画像M3Caption = ''
EOD;
    $dbMain->query($sql);

    $logger->info('[' . $this->commandName . '] 説明文の\'\'をNULLに変換しています。'); // ※不要では？
    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 商品コメントPC = NULL
      WHERE 商品コメントPC = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 一言ポイント = NULL
      WHERE 一言ポイント = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 補足説明PC = NULL
      WHERE 補足説明PC = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 必要補足説明 = NULL
      WHERE 必要補足説明 = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET サイズについて = NULL
      WHERE サイズについて = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET カラーについて = NULL
      WHERE カラーについて = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 素材について = NULL
      WHERE 素材について = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET ブランドについて = NULL
      WHERE ブランドについて = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts
      SET 使用上の注意 = NULL
      WHERE 使用上の注意 = ''
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * rakutenカテゴリ登録
   */
  private function registerRakutenCategory()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    // 全対象フラグ設定
    $lcAll = $commonUtil->getSettingValue($this->settingKeyPrefix . '_CATEGORY_ALL');

    $logTitle = '価格帯カテゴリ登録';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dbMain->query('TRUNCATE tb_rakutencategory');

    $logger->info('[' . $this->commandName . '] 個別カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_rakutencategory (
          daihyo_syohin_code
        , 表示先カテゴリ
      )
      SELECT
          cal.daihyo_syohin_code
        , cal.rakutencategories_3 AS 表示先カテゴリ
      FROM tb_mainproducts_cal AS cal
      WHERE IFNULL(cal.rakutencategories_3, '') <> ''
EOD;
    if (!$lcAll && !$this->exportAll) {
      $sql .= " AND cal.endofavailability IS NULL ";
    }
    $dbMain->query($sql);

    $logger->info('[' . $this->commandName . '] 全商品送料無料カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_rakutencategory (
          daihyo_syohin_code
        , 表示先カテゴリ
      )
      SELECT
          m.daihyo_syohin_code
        ,   CONCAT('全商品送料無料\\\\', d.`rakutencategories_1`) AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d ON m.NEディレクトリID = d.NEディレクトリID
EOD;
    if (!$lcAll && !$this->exportAll) {
      $sql .= " WHERE cal.endofavailability IS NULL ";
    }
    $dbMain->query($sql);

    $logger->info('[' . $this->commandName . '] ゲリラSALEカテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_rakutencategory (
          daihyo_syohin_code
        , 表示先カテゴリ
      )
      SELECT
          m.daihyo_syohin_code
        , CONCAT('ゲリラSALE\\\\', d.rakutencategories_1) AS 表示先カテゴリ
      FROM tb_mainproducts                  AS m
      INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
      INNER JOIN tb_title_parts             AS t   ON m.daihyo_syohin_code = t.daihyo_syohin_code
      WHERE t.front_title LIKE '%【ゲリラSALE】%'
EOD;
    if (!$lcAll && !$this->exportAll) {
      $sql .= " AND cal.endofavailability IS NULL ";
    }
    $dbMain->query($sql);


    $logger->info('[' . $this->commandName . '] 新着カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_rakutencategory (
          daihyo_syohin_code
        , 表示先カテゴリ
      )
      SELECT
          m.daihyo_syohin_code
        , CONCAT('新着\\\\', d.rakutencategories_1) AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
      INNER JOIN {$this->tableInformation}  AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE m.登録日時 >= DATE_ADD(CURRENT_DATE, INTERVAL - 7 DAY)
EOD;
    if (!$lcAll && !$this->exportAll) {
      $sql .= " AND cal.endofavailability IS NULL ";
    }
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 楽天タイトル補正___ （臨時No 4 「臨時タイトル送料無料」のみを実装）
   */
  private function fixTitle()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '楽天タイトル補正';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('[' . $this->commandName . '] 楽天タイトルを補正しています。');

    // '補正タイトルにタイトルをコピー
    $dbMain->query('TRUNCATE tb_rakuteninformation_edit'); // このテーブルは1回の実行内で TRUNCATE ～ INSERT してSELECTするので、店舗ごとに分けない

    $sql = <<<EOD
      INSERT INTO tb_rakuteninformation_edit (
          daihyo_syohin_code
        , 補正楽天タイトル
      )
      SELECT
          i.daihyo_syohin_code
        , CASE
            WHEN i.楽天タイトル IS NULL OR i.楽天タイトル = '' THEN m.daihyo_syohin_name
            ELSE i.楽天タイトル
          END AS 補正楽天タイトル
      FROM {$this->tableInformation} i
      INNER JOIN tb_mainproducts m ON i.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
    $dbMain->query($sql);

    // 'PC用キャッチコピー、モバイル用キャッチコピー
    $sql = <<<EOD
      UPDATE {$this->tableInformation}      As i
      INNER JOIN tb_mainproducts            AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS pd ON m.NEディレクトリID = pd.NEディレクトリID
      SET PC用キャッチコピー =
        LEFT(
          CONCAT_WS(' '
            , フィールド1
            , フィールド2
            , フィールド3
            , フィールド4
            , フィールド5
            , フィールド6
          )
          , 87
        )
       , モバイル用キャッチコピー =
        LEFT(
          CONCAT_WS(' '
            , フィールド1
            , フィールド2
            , フィールド3
            , フィールド4
            , フィールド5
            , フィールド6
          )
          , 30
        )
EOD;
    $dbMain->query($sql);

    // '商品名
    $sql = <<<EOD
      UPDATE tb_rakuteninformation_edit     AS i
      INNER JOIN tb_mainproducts            AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_rakuteninformation_edit AS e  ON i.daihyo_syohin_code = e.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS pd ON m.NEディレクトリID = pd.NEディレクトリID
      SET i.商品名 =
        LEFT(CONCAT(
                  '送料無料 '
                , e.補正楽天タイトル
              )
          , 127
        )
        , i.ALT用商品名 =
        LEFT(
            REPLACE(REPLACE(CONCAT(
                  '送料無料'
                , e.補正楽天タイトル
            ), ' ', ''), '　', '')
          , 127
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // '商品名
    $sql = <<<EOD
      UPDATE {$this->tableInformation}      AS i
      INNER JOIN tb_rakuteninformation_edit AS e ON i.daihyo_syohin_code = e.daihyo_syohin_code
      SET i.商品名 = e.商品名
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * NE更新カラム補正
   */
  private function setNeUpdateColumn()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'NE更新カラム補正';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('[' . $this->commandName . '] NE更新カラムを補正しています。');
    
    // NE更新カラムリセット    
    $dbMain->query("UPDATE {$this->tableInformation} SET NE更新カラム = NULL");

    $availableDeliveryCodes = [
        ':deliveryCodeReady'           => TbMainproductsCal::DELIVERY_CODE_READY
      , ':deliveryCodeReadyPartially'  => TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY
      , ':deliveryCodePurchaseOnOrder' => TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER
      , ':deliveryCodeFinished'        => TbMainproductsCal::DELIVERY_CODE_FINISHED
    ];

    $addSqlDeliveryCode = implode(', ', array_keys($availableDeliveryCodes));

    $readyDeliveryCodes = [
        ':deliveryCodeReady'           => TbMainproductsCal::DELIVERY_CODE_READY
        , ':deliveryCodeReadyPartially'  => TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY
    ];

    $addSqlReadyDeliveryCode = implode(', ', array_keys($readyDeliveryCodes));

    // '更新(在庫表有)(v)
    // 'ProductChoiceItemsにあって楽天在庫表（tb_rakutenselect_key）になく、出品対象のものを出力する
    // motto|laforest|dolcissimo|gekiplaの場合は、「即納・一部即納、または代表商品が双方にあるもの（過去に即納・一部即納だったもの）」のみ。該当しないものを空に戻す
    // tb_rakutenselect_keyは、今回出力対象でないもの（出品OFF、アダルトチェック）は除外されている
    $sql = <<<EOD
      UPDATE tb_productchoiceitems     AS pci
      INNER JOIN tb_mainproducts_cal   AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN {$this->tableInformation} AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_rakutenselect_key   AS dl ON pci.ne_syohin_syohin_code = dl.ne_syohin_syohin_code
      SET i.NE更新カラム = 'v'
      WHERE dl.ne_syohin_syohin_code IS NULL
        AND i.registration_flg <> '0'
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND cal.deliverycode_pre IN ( {$addSqlDeliveryCode} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    foreach($availableDeliveryCodes as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_INT);
    }
    $stmt->execute();

    if (
      $this->targetShop === self::EXPORT_TARGET_MOTTO
      || $this->targetShop === self::EXPORT_TARGET_LAFOREST
      || $this->targetShop === self::EXPORT_TARGET_DOLCISSIMO
      || $this->targetShop === self::EXPORT_TARGET_GEKIPLA
    ) {
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal   AS cal
        INNER JOIN {$this->tableInformation} AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN tb_rakutenselect_key   AS dl ON cal.daihyo_syohin_code = dl.daihyo_syohin_code
        SET i.NE更新カラム = ''
        WHERE dl.daihyo_syohin_code IS NULL
          AND cal.deliverycode_pre NOT IN ( {$addSqlReadyDeliveryCode} )
EOD;
      $stmt = $dbMain->prepare($sql);
      foreach($readyDeliveryCodes as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_INT);
      }
      $stmt->execute();
    }

    // 'DLにあってProductChoiceItemsにない
    // あるいは 'DLとProductChoiceItemsで選択肢の名前が違う（統合）
    $sql = <<<EOD
      UPDATE tb_rakutenselect_key      AS dl
      INNER JOIN tb_mainproducts_cal   AS cal ON dl.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN {$this->tableInformation} AS i   ON cal.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_productchoiceitems  AS pci ON dl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      SET i.NE更新カラム = 'v'
      WHERE (
             pci.ne_syohin_syohin_code IS NULL
          OR pci.colname <> BINARY dl.colname
          OR pci.rowname <> BINARY dl.rowname
        )
        AND i.registration_flg <> '0'
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND cal.deliverycode_pre IN ( {$addSqlDeliveryCode} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    foreach($availableDeliveryCodes as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_INT);
    }
    $stmt->execute();

    // '新規(n)
    // tb_rakutenitem_dl にない代表商品コードなら新規
    if ($this->targetShop === self::EXPORT_TARGET_RAKUTEN) {
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal   AS cal 
        INNER JOIN {$this->tableInformation} AS i   ON cal.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN {$this->tableItemDl}   AS dl  ON cal.daihyo_syohin_code = dl.`商品管理番号（商品URL）`
        SET i.NE更新カラム = 'n'
        WHERE dl.`商品管理番号（商品URL）` IS NULL
          AND i.registration_flg <> '0'
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND cal.deliverycode_pre IN ( {$addSqlDeliveryCode} )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
      foreach($availableDeliveryCodes as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_INT);
      }
      $stmt->execute();
    } else {
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal   AS cal
        INNER JOIN {$this->tableInformation} AS i   ON cal.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN {$this->tableItemDl}   AS dl  ON cal.daihyo_syohin_code = dl.`商品管理番号（商品URL）`
        SET i.NE更新カラム = 'n'
        WHERE dl.`商品管理番号（商品URL）` IS NULL
          AND i.registration_flg <> '0'
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND cal.deliverycode_pre IN ( {$addSqlReadyDeliveryCode} )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
      foreach($readyDeliveryCodes as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_INT);
      }
      $stmt->execute();
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * エクスポート 1回目
   * 以下に該当するもので、まだ倉庫に入っていないものを倉庫へ追加するためのitem.csvを生成し、アップロード
   *  1) 楽天側に存在するが、plusnaoに存在しない（plusnao側の誤削除があるので、削除はしない）
   *  2) 出品OFFまたはアダルトチェック
   */
  private function exportCsvIntoWarehouse()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '[' . $this->shopName . '] エクスポート（１回目）';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // ====================
    // item.csv （tb_mainproducts に存在しない、出品OFF、またはアダルトチェックの商品コードを倉庫に入れるCSVを出力する）
    //            既に倉庫に入っているものは対象外
    // ====================
    $sql = <<<EOD
      SELECT
          'u' AS コントロールカラム
        , item.商品管理番号（商品URL）
        , '1' AS 倉庫指定
      FROM {$this->tableItemDl} item
      LEFT JOIN tb_mainproducts m ON item.商品管理番号（商品URL） = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal cal ON item.商品管理番号（商品URL） = cal.daihyo_syohin_code
      LEFT JOIN {$this->tableInformation} i ON item.商品管理番号（商品URL）  = i.daihyo_syohin_code
      WHERE (
          m.daihyo_syohin_code IS NULL /* plusnaoに存在せず、楽天にだけ存在する */
          OR i.registration_flg = 0 /* 出品OFF */
          OR cal.adult_check_status IN (:adultCheckStatusBlack, :adultCheckStatusNone) /* アダルトチェック */
        )
        AND item.倉庫指定 = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->execute();

    // 出力
    if ($stmt->rowCount()) {
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->getContainer()->get('misc.util.string');

      // ヘッダ
      $headers = [
          'コントロールカラム'
        , '商品管理番号（商品URL）'
        , '倉庫指定'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $filePath = sprintf('%s/item-warehouse.csv', $this->exportPath);
      $fp = fopen($filePath, 'wb');
      fputs($fp, $headerLine);

      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
      }
      fclose($fp);

      $this->results['item-warehouse.csv'] = $num;
      $logger->info('[' . $this->commandName . "] item-warehouse.csv: $num 件");


      // item.csv(削除用) アップロード処理
      /** @var RakutenMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.rakuten');

      if ($this->doUpload) {
        $processor->enqueueUploadCsv($filePath, 'item.csv', $this->getEnvironment(), $this->targetShop, self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    } else {
      $logger->info('[' . $this->commandName . "]  item-warehouse.csv: 件数が0のためファイルは作成しませんでした。");
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * Import___()
   * 商品データインポート処理
   */
  private function importDownloadData()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'インポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $fileNum = [
        'item-cat' => 0
      , 'item' => 0
      , 'select' => 0
    ];

    $fileUtil = $this->getFileUtil();

    // Call importCategory___ (dl-item-cat.csv 取込)
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'カテゴリー', '開始'));
    $logger->info('[' . $this->commandName . '] dl-item-cat.csvを取り込んでいます。');

    $sql = 'TRUNCATE ' . $this->tableCategoryDl;
    $dbMain->query($sql);

    $importDir = $this->getRakutenImportDir();
    $logger->info('[' . $this->commandName . '] ' . $importDir);

    $finder = new Finder();
    $finder = $finder->in($importDir)->name('/dl-item-cat\d+(\-\d+|\-\d+\-\d+)\.csv/');
    $finder = $this->sortRakutenImportCsv($finder);
    /** @var SplFileInfo $file */
    foreach($finder->files() AS $file) {

      $logger->info('[' . $this->commandName . '] ' . $file->getPathname());

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        INTO TABLE {$this->tableCategoryDl}
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      $fileNum['item-cat']++;
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'カテゴリー', '終了'));

    //  Call ImportItem___ (dl-item.csv 取込)
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '商品', '開始'));
    $logger->info('[' . $this->commandName . '] dl-item.csvを取り込んでいます。');

    $sql = 'TRUNCATE ' . $this->tableItemDl;
    $dbMain->query($sql);

    $finder = new Finder();
    $finder = $finder->in($importDir)->name('/dl-item\d+\-\d+\.csv/');
    $finder = $this->sortRakutenImportCsv($finder);

    /** @var SplFileInfo $file */
    foreach($finder->files() AS $file) {
      $logger->info('[' . $this->commandName . '] ' . $file->getPathname());

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        INTO TABLE {$this->tableItemDl}
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      fclose($tmpFile);
      $fileNum['item']++;
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '商品', '終了'));

    // Call importSelect___ (dl-select.csv 取込)
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '在庫', '開始'));
    $logger->info('[' . $this->commandName . '] dl-select.csvを取り込んでいます。');

    $sql = 'TRUNCATE ' . $this->tableSelectDl;
    $dbMain->query($sql);
    $dbMain->query('TRUNCATE tb_rakutenselect_key'); // このテーブルはこのCommandでしか使わないので、分けない

    $finder = new Finder();
    $finder = $finder->in($importDir)->name('/dl-select\d+\-\d+\.csv/');
    $finder = $this->sortRakutenImportCsv($finder);
    /** @var SplFileInfo $file */
    foreach($finder->files() AS $file) {
      $logger->info('[' . $this->commandName . '] ' . $file->getPathname());

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        INTO TABLE {$this->tableSelectDl}
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      fclose($tmpFile);
      $fileNum['select']++;
    }

    // もし、いずれかのファイルが0件であれば何かおかしいとしてエラーとする。
    foreach($fileNum as $type => $count) {
      if (!$count) {
        throw new \RuntimeException('[' . $this->shopName . ']' . sprintf('インポートする dl-%s ファイルが0件です。イレギュラーとして処理を中止しました。', $type));
      }
    }

    // 楽天の選択肢ファイルは ne_syohin_syohin_code がないため、比較などが出来るよう別テーブルを構築
    // このテーブルは、この楽天CSV出力のなかで楽天に存在するSKUに揃えられる。（削除 ＆ 挿入）
    $sql = <<<EOD
      INSERT INTO tb_rakutenselect_key (
          daihyo_syohin_code
        , ne_syohin_syohin_code
        , colname
        , rowname
        , stock
        , 在庫あり時納期管理番号
      )
      SELECT
          dl.`商品管理番号（商品URL）` AS daihyo_syohin_code
        , CONCAT(
            dl.`商品管理番号（商品URL）`
          , dl.`項目選択肢別在庫用横軸選択肢子番号`
          , dl.`項目選択肢別在庫用縦軸選択肢子番号`
        ) AS ne_syohin_syohin_code
        , dl.`項目選択肢別在庫用横軸選択肢`
        , dl.`項目選択肢別在庫用縦軸選択肢`
        , dl.項目選択肢別在庫用在庫数
        , dl.`在庫あり時納期管理番号`
      FROM {$this->tableSelectDl} dl
      JOIN tb_mainproducts m ON dl.商品管理番号（商品URL） = m.daihyo_syohin_code /* plusnaoに存在しない商品はそもそも登録しない */
      JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      JOIN {$this->tableInformation} i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE `選択肢タイプ` = 'i'
         AND i.registration_flg <> 0
         AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '在庫', '終了'));
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 楽天商品データファイル・ディレクトリ作成 （インポート用）
   */
  private function getRakutenImportDir()
  {
    if (!$this->importPath) {
      $fileUtil = $this->getFileUtil();

      $fs = new Filesystem();
      $this->importPath = sprintf('%s/%s/%s/%s', $fileUtil->getWebCsvDir(), self::IMPORT_PATH, (new \DateTime())->format('YmdHis'), $this->targetShop);
      if (!file_exists($this->importPath)) {
        $fs->mkdir($this->importPath);
      }
    }

    return $this->importPath;
  }

  /**
   * 楽天商品CSV ファイルリスト 枝番昇順
   */
  private function sortRakutenImportCsv(Finder $finder)
  {
    return $finder->sort(
      // ファイル作成日降順
      function (SplFileInfo $a, SplFileInfo $b)
      {
        $nameA = $a->getBasename();
        $nameB = $b->getBasename();

        $indexA = preg_match('/-(\d+)\.csv$/', $nameA, $match) ? intval($match[1]) : 0;
        $indexB = preg_match('/-(\d+)\.csv$/', $nameB, $match) ? intval($match[1]) : 0;

        return ($indexA == $indexB)
          ? 0
          : ($indexA < $indexB ? -1 : 1);
      }
    )->directories();
  }

  /**
   * インポート用CSVファイル ダウンロード処理
   * ※他で利用することがあれば、MallProcessへ移動する
   * @param \DateTime $baseDateTime ダウンロード基準日時 ファイル名の日付がこの日付より新しいデータを全て対象とする。
   */
  private function downloadImportCsvData($baseDateTime = null)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $commonUtil = $this->getDbCommonUtil();

    // 基準日時 最新キック日時
    if (!$baseDateTime) {
      $csvKicked = $commonUtil->getUpdateRecordLastUpdatedDateTime($this->updateRecordNumberKick);
      $lastProcessed = $commonUtil->getUpdateRecordLastUpdatedDateTime($this->updateRecordNumberProcess);
      // もしキックの記録がなかったり、キックが前回CSV出力時より古ければエラー
      if (!$csvKicked) {
        throw new \RuntimeException('[' . $this->shopName . ']' . '楽天CSVダウンロード 基準時間の指定がなく、キック処理の時間が保存されていません。処理を中止しました。');
      } else if ($lastProcessed && $csvKicked < $lastProcessed) {
        throw new \RuntimeException('[' . $this->shopName . ']' . '楽天CSVダウンロード 基準時間の指定がなく、キック処理の時間が前回出力時以前です。処理を中止しました。');
      }

      // 1分のみ余裕を持つ（同一時間対策）
      $csvKicked->modify('-1 minute');

      $baseDateTime = $csvKicked;
    }

    if (!$baseDateTime) {
      throw new \RuntimeException('[' . $this->shopName . ']' . 'ダウンロード基準日時が取得できませんでした。処理を中止します。');
    }

    $logTitle = 'インポート用CSVダウンロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('楽天 インポート用CSVダウンロード [' . $baseDateTime->format('Y-m-d H:i:s') . ' ～]');

    $importDir = $this->getRakutenImportDir();

    // FTP ダウンロード処理
    $ftpConfig = $this->getContainer()->getParameter($this->ftpParamRoot);
    $config = $ftpConfig['csv_download'];

    // 開発環境はパスワード決め打ち
    $env = $this->getEnvironment();
    $config['password'] = $commonUtil->getSettingValue($this->settingKeyPrefix . '_GOLD_FTP_PASSWORD', $env);

    /** @var RakutenMallProcess $processor */
    $processor = $container->get('batch.mall_process.rakuten');
    $result = $processor->downloadCsv($config, $this->shopName, $baseDateTime, $importDir);

    // セットが揃っていなければエラー
    $errorMessage = '';
    foreach($result['files'] as $type => $group) {
      if (!count($group)) {
        $errorMessage .= $type . ' のファイルがダウンロードされませんでした。' . "\n";
      }
    }
    if ($errorMessage) {
      $errorMessage = '[' . $this->shopName . ']' . sprintf("%s\n処理を中止します。\n(基準日時:%s)", $errorMessage, $baseDateTime->format('Y-m-d H:i:s'));
      throw new \RuntimeException($errorMessage);
    }

    $this->results['downloaded'] = [
        'times' => [
            'item' => $result['dateTimes']['item']->format('Y-m-d H:i:s')
          , 'select' => $result['dateTimes']['select']->format('Y-m-d H:i:s')
          , 'item-cat' => $result['dateTimes']['item-cat']->format('Y-m-d H:i:s')
        ]
      , 'files' => $result['files']
    ];
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
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
    $spImages = []; $spImagesMax =  ($row['価格非連動チェック'] != 0 && $row['手動ゲリラSALE'] != 0) ? 17 : 18; // 17 or 18枚（ゲリラSALE時17枚 それ以外は18枚（ゲリラSALE時はゲリラSALEバナーを入れる））
    $num = 1;
    foreach($tmp as $image) {
      if (strpos($image, ':') !== false) {
        list($code, $dirFile) = explode(':', $image);
        if ($code && $dirFile) {
          $images[$code] = $dirFile;
          if ($num <= $spImagesMax) {
            $spImages[$code] = $dirFile;
          }
          $num++;
        }
      }
    }

    $data = [
        'row' => $row
      , 'pcImages' => $images
      , 'spImages' => $spImages
      , 'settings' => $options['settings']
      , 'spTopBanner' => $options['topBannerHtml']
    ];

    // HTML作成：PC商品説明文
    $row['PC用商品説明文'] = $options['templateDescriptionPc']->render($data);

    // HTML作成：SP商品説明文
    $row['スマートフォン用商品説明文'] = $options['templateDescriptionSp']->render($data);

    // HTML作成：PC販売説明文
    $row['PC用販売説明文'] = trim($options['templateSalesDescriptionPc']->render($data));

    // HTML作成：MB商品説明文 -> 別テーブルでもあり、作成そのものが不要な可能性もあり、一旦改修は見送り

    // 商品画像URL, 商品画像名（ALT）
    // 新規の場合は単純に連結。更新時には削除指定 TODO: motto|laforest|dolcissimo|gekiplaの場合、白画像は初期バージョンでは常に0
    $imageUrls = [];
    $imageAlts = [];
    if ($row['NE更新カラム'] == 'n') {
      foreach($images as $code => $dirPath) {
        if (strlen($dirPath)) {
          $imageUrls[] = sprintf('%s%s', $this->cabinetUrl, $dirPath);
          $imageAlts[] = $row['ALT用商品名'];
        }
      }
      // Amazonメイン画像（白背景画像）
      // 該当カラムに値があればその値を出力
      if (isset($row['image_amazon']) && $this->targetShop === self::EXPORT_TARGET_RAKUTEN) {
        $row['白背景画像URL'] = sprintf('%s%s', $this->cabinetUrl, $row['image_amazon']);
      }
    } else {
      foreach(RakutenMallProcess::$IMAGE_CODE_LIST as $code) {
        if (isset($images[$code]) && strlen($images[$code])) {
          $imageUrls[] = sprintf('%s%s', $this->cabinetUrl, $images[$code]);
          $imageAlts[] = $row['ALT用商品名'];
        } else {
          $imageUrls[] = '0'; // 画像削除指定
          $imageAlts[] = '';
        }
      }
      // Amazonメイン画像（白背景画像）
      // 該当カラムに値があればその値、NULLであれば'0'を出力
      if (isset($row['image_amazon']) && $this->targetShop === self::EXPORT_TARGET_RAKUTEN) {
        $row['白背景画像URL'] = sprintf('%s%s', $this->cabinetUrl, $row['image_amazon']);
      } else {
        $row['白背景画像URL'] = '0';
      }
    }
    $row['商品画像URL'] = implode(' ', $imageUrls);
    $row['商品画像名（ALT）'] = implode(' ', $imageAlts);

  }

  private function downloadImportCsvDataTest($option = 'staging')
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $commonUtil = $this->getDbCommonUtil();

    $baseDateTime = (new \DateTime())->modify('-2 days');

    $logTitle = 'インポート用CSVダウンロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('楽天 インポート用CSVダウンロード [' . $baseDateTime->format('Y-m-d H:i:s') . ' ～]');
    $logger->debug("SFTP DOWNLOAD START");

    $importDir = $this->getRakutenImportDir();

    // FTP ダウンロード処理
    $ftpConfig = $this->getContainer()->getParameter('ftp_rakuten');
    $config = $ftpConfig['csv_download'];

    // 開発環境はパスワード決め打ち
    $env = $this->getEnvironment();
    $config['password'] = $commonUtil->getSettingValue('RAKUTEN_GOLD_FTP_PASSWORD', $env);

    if ($option == 'staging') {
      $config['host'] = 'upload.rakuten.ne.jp';
      $config['user'] = 'plusnao';
      $config['path'] = '/ritem/download';
      $config['password'] = 'Yoshiko9';
    }

    /** @var RakutenMallProcess $processor */
    $processor = $container->get('batch.mall_process.rakuten');
    $logger->debug("SFTP TEST START");
    $result = $processor->downloadCsv($config, $this->shopName, $baseDateTime, $importDir);

    // セットが揃っていなければエラー
    $errorMessage = '';
    foreach($result['files'] as $type => $group) {
      if (!count($group)) {
        $errorMessage .= $type . ' のファイルがダウンロードされませんでした。' . "\n";
      }
    }
    if ($errorMessage) {
      $errorMessage = '[' . $this->shopName . ']' . sprintf("%s\n処理を中止します。\n(基準日時:%s)", $errorMessage, $baseDateTime->format('Y-m-d H:i:s'));
      throw new \RuntimeException($errorMessage);
    }

    $this->results['downloaded'] = [
        'times' => [
            'item' => $result['dateTimes']['item']->format('Y-m-d H:i:s')
          , 'select' => $result['dateTimes']['select']->format('Y-m-d H:i:s')
          , 'item-cat' => $result['dateTimes']['item-cat']->format('Y-m-d H:i:s')
        ]
      , 'files' => $result['files']
    ];

    $logger->debug("SFTP TEST END");
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }
}
