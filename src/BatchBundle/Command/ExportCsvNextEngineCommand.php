<?php

namespace BatchBundle\Command;

use BatchBundle\Job\NextEngineUploadJob;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * NextEngine CSVエクスポート処理。
 *
 * NextEngineに登録できるSKU数には限りがある（2023/03現在50万件）。
 * plusnaoで取り扱う商品数はそれを超えているため、受注が少ない商品はNEから一時的に削除し、
 * 『モールでは販売中だがNEには商品情報未登録』という状態とする。
 * その場合、モールで受注が発生すると、商品情報なしとして受注情報（tb_sales_detail）自体は作られるので、
 * それを元に後付けでNEへ商品情報を登録する。
 * 
 * SKUの登録対象は以下の通り。
 * ・有効な受注で、出荷確定済みではない受注にあるSKUはすべて残す。
 * ・販売可能在庫、移動中商品があるSKUは全て残す。
 * ・注残が残っているSKUはすべて残す。
 * ・セット商品の構成品は全て残す。
 * ・以降、自動登録上限数に達するまで、現在NextEngineに登録済みのSKUを、最終受注日が近い順に追加する。ただし販売終了品フィルタに該当するものは最終受注日が近くても削除する。
 * 
 * なお、アップロードは、削除ファイルは即時アップロードされるが、商品ファイルは別の処理により、1.5hに1ファイルの頻度でアップロードする。
 */
class ExportCsvNextEngineCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;
  
  /** 手動で在庫データ（オリジナル）をダウンロードし、それをもとにCSV出力を行う場合の、WEB_CSV以下のファイルの置き先 */
  const FILE_DIR_MANUAL_DOWNLOAD_STOCK_DATA_ORIGINAL = "/NextEngine/Downloaded/OriginalStocks/All";

  private $account;

  private $exportFiles = [
      'product'             => []
    , 'delete_reservation'  => []
    , 'delete_master'       => []
  ];

  // 自動登録上限 NexｔEngintの上限は50万件だが、急ぎの手動登録を考慮して5000件程度空きを作る。
  const REGIST_SKU_LIMIT = 495000;
  
  // アップロードファイルの分割設定サイズ
  const UPLOAD_CSV_MAX_SIZE = 2000000; // 2MBで分割 （商品マスタのみ）
  
  // アップロードワークディレクトリの名前
  const CURRENT_UPLOAD_DIRECTORY_NAME = 'CurrentUpload';

  /** 販売終了後 削除対象日 */
  private $delDateFromSalesEnd = null;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-next-engine')
      ->setDescription('CSVエクスポート NextEngine')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'NextEngineへのアップロードを行うか', '0') // デフォルト OFF
      ->addOption('ignore-price-diff', null, InputOption::VALUE_OPTIONAL, '原価・売価の差分を無視するか', '0')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, '対象のNE環境', 'test') // デフォルト test
      ->addOption('do-download', null, InputOption::VALUE_OPTIONAL, 'NextEngineから在庫データダウンロード（オリジナルCSV）を行うか。行わない場合手動で配置が必要', '1'); // デフォルト ON
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    $logExecTitle = 'NextEngineCSV出力処理';
    $logger->info($logExecTitle . 'を開始しました。');

    $logger->setExecTitle($logExecTitle);

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {
      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
      
      // 変数化
      $doUpload = (bool)$input->getOption('do-upload');
      $ignorePriceDiff = $input->getOption('ignore-price-diff');
      if (isset($ignorePriceDiff)) {
        $ignorePriceDiff = (bool)$ignorePriceDiff;
      } else {
        $ignorePriceDiff = (bool)$commonUtil->getSettingValue(TbSetting::KEY_NE_PRODUCT_IGNORE_PRICE_DIFF);
      }
      $ignorePriceDiffMsg = $ignorePriceDiff ? '原価・売価の差分は無視する' : '原価・売価の差分は無視しない';
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, $ignorePriceDiffMsg ));
      
      // ワークディレクトリ確認
      $fs = new FileSystem();
      $finder = new Finder(); // 結果ファイル確認
      $currentDir = $this->getFileUtil()->getWebCsvDir() . '/NextEngine/' . self::CURRENT_UPLOAD_DIRECTORY_NAME;
      if (!$fs->exists($currentDir)) {
        $fs->mkdir($currentDir, 0755);
      }
      
      $fileNum = $finder->in($currentDir)->name('*.csv')->files()->count();
      if ($fileNum > 0 && $doUpload) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'アップロード中のため終了'));
        return 0;
      }

      $dbMain = $this->getDb('main');

      /** @var NextEngineMallProcess $neMallProcess */
      $neMallProcess = $container->get('batch.mall_process.next_engine');

      // NextEngine在庫データダウンロード（差分確認用）
      // CSVダウンロード処理
      $importDir = null;
      $doDownload = (bool)$input->getOption('do-download');
      if ($doDownload) {
        $importDir = $this->getFileUtil()->getDataDir() . '/stocks/' . (new \DateTime())->format('YmdHis');
        $neMallProcess->downloadNextEngineStockDataOriginal($importDir, $this->account, $input->getOption('target-env'), true, 'all');
      } else {
        $importDir = $this->getFileUtil()->getWebCsvDir() . self::FILE_DIR_MANUAL_DOWNLOAD_STOCK_DATA_ORIGINAL;
        if (!$fs->exists($importDir) || $finder->in($importDir)->name('*.csv')->files()->count() === 0) {
          throw new \RuntimeException('在庫データ（オリジナルCSV）の自動ダウンロードをしない場合、' . self::FILE_DIR_MANUAL_DOWNLOAD_STOCK_DATA_ORIGINAL . 'にファイルが必要です');
        }
        $neMallProcess->downloadNextEngineStockDataOriginal($importDir, $this->account, $input->getOption('target-env'), false, 'all');
      }

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0
      $rakutenCsvOutputDir = $this->getFileUtil()->getWebCsvDir() . '/RakutenNokiKanri';
      $commonUtil->exportCsvCommonProcess($logger, $rakutenCsvOutputDir);

      // locationバックアップ___
      // locationをpreviouslocationにバックアップ
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, 'locationバックアップ___', '開始'));
      $sql = <<<EOD
        UPDATE tb_productchoiceitems
        SET tb_productchoiceitems.previouslocation = location
        WHERE tb_productchoiceitems.location <> '_new'
EOD;
      $dbMain->query($sql);
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, 'locationバックアップ___', '終了'));

      $delDaysFromSalesEnd = $commonUtil->getSettingValue('DEL_DAYS_FROM_SALES_END'); // 削除対象とする販売終了日からの日数
      if ($delDaysFromSalesEnd) {
        $delDateFromSalesEnd = new \DateTime();
        $delDateFromSalesEnd->modify("-${delDaysFromSalesEnd} day"); // 日数を元に日付を計算
        $this->delDateFromSalesEnd = $delDateFromSalesEnd;
        $logger->debug("販売終了日から $delDaysFromSalesEnd 日経過していれば削除。削除基準日：" . $delDateFromSalesEnd->format('Y-m-d'));
      }

      // Call prepareData___
      $this->prepareData($ignorePriceDiff);

      // Call Export___
      $exportDir = $this->export($doUpload, $ignorePriceDiff);

      $finder = new Finder(); // 結果ファイル確認
      $message = '';
      $fileNum = $finder->in($exportDir)->files()->count();
      if (!$fileNum) {
        $message = 'CSVファイルが作成されませんでした。処理を完了します。';
      } else {
        if ((bool)$input->getOption('do-upload')) {
          $message = 'NextEngineアップロード処理を予約します。';
        }
      }

      // 最終出力日時更新
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE, new \DateTime());

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($message));
      $logger->logTimerFlush();

      $logger->info('NextEngine CSV Export 完了');

      // 引き続き、NextEngineへのアップロード
      // → 別Jobとして別のキューで呼び出す（=> 排他でのリトライを独立して行うため）
      // → 2016/11/16 仕様変更：30ごとのスケジュールキューで実行する。（アップロード中、NextEngineの引当処理が止まることを回避するため。）
      if ($fileNum > 0 && $doUpload) { // 引数で制御
        $rescue = $this->getResque();

        // テスト環境ではNextEngineテスト環境へアップロード
        $env = $input->getOption('target-env');
        if ($env !== 'prod') {
          $targetEnv = 'test';

          $logger->info('NextEngine CSVアップロードはテスト環境！');
        } else {
          $targetEnv = 'prod';

          $logger->info('NextEngine CSVアップロードは本番環境！！！！！');
        }

        $count = 0; // 通し番号

        $uploadInterval = $commonUtil->getSettingValue('NE_PRODUCT_UPLOAD_INTERVAL');
        if (is_null($uploadInterval) || !is_numeric($uploadInterval)) {
          $uploadInterval = 300; // デフォルト
        }

        // product
        // 2021/03 登録・更新のキュー追加を廃止、ExportCsvNextEngineProductEnqueueCommandで管理
        foreach($this->exportFiles['delete_master'] as $filePath) {

          $interval = $uploadInterval * $count++;

          $job = new NextEngineUploadJob();

          $job->queue = 'neUpload'; // キュー名
          $job->args = [
              'command' => NextEngineUploadJob::COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS
            , 'dataDir' => $exportDir
            , 'file' => basename($filePath)
            , 'targetEnv' => $targetEnv
          ];
          if ($this->account) {
            $job->args['account'] = $this->account->getId();
          }

          $rescue->enqueueIn($interval, $job);
        }

        // delete_reservation
        foreach($this->exportFiles['delete_reservation'] as $filePath) {

          $interval = $uploadInterval * $count++;

          $job = new NextEngineUploadJob();

          $job->queue = 'neUpload'; // キュー名
          $job->args = [
            'command' => NextEngineUploadJob::COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS
            , 'dataDir' => $exportDir
            , 'file' => basename($filePath)
            , 'targetEnv' => $targetEnv
          ];
          if ($this->account) {
            $job->args['account'] = $this->account->getId();
          }

          $rescue->enqueueIn($interval, $job);
        }

        $logger->info('NextEngine CSVアップロード キュー追加');
      }

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }


  /// データ準備
  private function prepareData($ignorePriceDiff = false)
  {
    $logger = $this->getLogger();
    $db = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    $logTitle = 'NextEngineCSV出力';
    $subTitle = 'prepareData___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 商品データの登録。SKU単位で登録を行う。
    // 登録基準
    //  ・在庫・移動中商品・注残・未出荷の有効な受注のいずれかが存在する
    //  ・セット商品の構成品である
    //　追加登録基準
    //  登録基準のSKUだけだと上限に達しない。このため以下の基準で追加登録を行う。
    //  ・自動登録上限数に達するまで、最終受注日が近いSKUを順に追加する。ただし販売終了品フィルタに該当するものは最終受注日が近くても削除する。
    // 
    // tb_ne_productsには、いったん「NextEngineにあるべきもの」を全て出力する。差分のみの出力の絞り込みは export() 内で出力時に行う。
    $db->query("TRUNCATE tb_ne_products");

    // まず登録基準に従って登録
    $sqlBase = <<<EOD
      INSERT INTO
      tb_ne_products (
          syohin_code
        , sire_code
        , jan_code
        , kataban
        , syohin_name
        , syohin_kbn
        , toriatukai_kbn
        , genka_tnk
        , baika_tnk
        , daihyo_syohin_code
        , tag
        , location
        , org_select2
        , org_select3
        , org_select4
        , visible_flg
        , yoyaku_zaiko_su
      )
      SELECT
          pci.ne_syohin_syohin_code AS syohin_code
        , mp.sire_code
        , code.barcode AS jan_code
        , code.barcode AS kataban
        , concat(
          mp.daihyo_syohin_name
          , ' ['
          , COALESCE(pci.colname, '')
          , '] ['
          , COALESCE(pci.rowname, '')
          , ']'
        ) AS syohin_name
        , CASE
          WHEN mp.syohin_kbn = '10'
          THEN '10'
          ELSE '0'
          END AS syohin_kbn
        , pci.toriatukai_kbn
        , cal.cost_tanka AS genka_tnk
        , IFNULL(cal.baika_tnk, 0) AS baika_tnk
        , pci.daihyo_syohin_code
        , pci.tag
        , pci.location
        , 0 AS org_select2
        , 0 AS org_select3
        , 0 AS org_select4
        , 1 AS visible_flg /* 1固定 */
        , 0 AS yoyaku_zaiko_su /* #209576 予約在庫数は使われていないと思われるため、0固定 */
      FROM
        tb_productchoiceitems as pci
        INNER JOIN tb_mainproducts as mp USING (daihyo_syohin_code)
        INNER JOIN tb_mainproducts_cal as cal USING (daihyo_syohin_code)
        INNER JOIN tb_vendormasterdata as v USING (sire_code)
        LEFT JOIN tb_product_code code USING (ne_syohin_syohin_code)
EOD;

    $sql = $sqlBase . <<<EOD
        LEFT JOIN ( -- 登録対象であるセット商品の構成品
          SELECT distinct set_detail.ne_syohin_syohin_code
          FROM tb_mainproducts set_mp 
          JOIN tb_productchoiceitems set_pci ON set_pci.daihyo_syohin_code = set_mp.daihyo_syohin_code
          JOIN tb_set_product_detail set_detail ON set_detail.set_ne_syohin_syohin_code = set_pci.ne_syohin_syohin_code
          WHERE set_mp.set_flg <> 0
        ) set_sku ON set_sku.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        LEFT JOIN ( -- 受注が存在する
          SELECT distinct 商品コード（伝票） ne_syohin_syohin_code
          FROM tb_sales_detail_analyze
          WHERE 受注状態 NOT IN ('出荷確定済（完了）')
            AND キャンセル区分 = 0
            AND 明細行キャンセル = 0
        ) sales_sku ON sales_sku.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        LEFT JOIN ( -- 注残が存在する
          SELECT distinct 商品コード as ne_syohin_syohin_code
          FROM tb_individualorderhistory
          WHERE 注残計 > 0
        ) ioh_sku ON ioh_sku.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        LEFT JOIN ( -- 在庫が存在する
          SELECT distinct pl.ne_syohin_syohin_code
          FROM tb_product_location pl
          JOIN tb_location l ON pl.location_id = l.id
          JOIN tb_warehouse w ON l.warehouse_id = w.id
          WHERE pl.stock > 0
        ) stock_sku ON stock_sku.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        LEFT JOIN ( -- 移動中商品が存在する。総在庫のあるものを抽出したいだけで、重複可なのでピッキング状況判定はしない（未ピッキングなら在庫で計上される）
          SELECT distinct d.ne_syohin_syohin_code
          FROM tb_stock_transport t 
          JOIN tb_stock_transport_detail d ON d.transport_id = t.id
          WHERE t.status in (0, 10, 20, 30)
        ) transport_sku ON transport_sku.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      WHERE 
        /* セット商品は別途アップするため、商品CSVには掲載しない */
        mp.set_flg = 0
        /* 
         * NE側のデータの有無を問わず、共通の条件
         * ・在庫・移動中商品・注残・受注のいずれかが存在する
         * ・セット商品の構成品である
         */
        AND (
          stock_sku.ne_syohin_syohin_code IS NOT NULL
          OR transport_sku.ne_syohin_syohin_code IS NOT NULL
          OR ioh_sku.ne_syohin_syohin_code IS NOT NULL
          OR sales_sku.ne_syohin_syohin_code IS NOT NULL
          OR set_sku.ne_syohin_syohin_code IS NOT NULL
        )
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $updateCount = $stmt->rowCount(); // 登録対象件数
    $logger->debug($logTitle . "登録件数: $updateCount");
    
    // この時点で上限を超えていたらエラーを通知　ただし実際の上限までは猶予があるのでCSV出力へ進む
    // このエラーが出たら、「在庫・注残・受注・セット商品の構成品は登録」という基準の見直しが必要
    if ($updateCount > self::REGIST_SKU_LIMIT) {
      $logger->addDbLog(
        $logger->makeDbLog(null, $logTitle, $subTitle, "登録件数上限に到達($updateCount)")->setInformation("NextEngineCSV出力で在庫・注残・受注・セット商品の合計SKUが上限到達。SKU数： $updateCount")
        , true, "NextEngineCSV出力でSKU数が上限到達", 'error'
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '在庫・注残・受注・セット商品の登録で終了'));
      return;
    }
    
    // 残りの件数分は、自動登録上限数に達するまで、最終受注日が近いSKUを順に追加する。ただし販売終了品フィルタに該当するものは最終受注日が近くても削除する。
    $addLimit = self::REGIST_SKU_LIMIT - $updateCount;
    
    $sql = $sqlBase . <<<EOD
      JOIN tb_sales_detail_analyze a ON a.商品コード（伝票） = pci.ne_syohin_syohin_code
      LEFT JOIN tb_ne_products nep ON a.商品コード（伝票） = nep.syohin_code
      WHERE 
        /* セット商品は別途アップするため、商品CSVには掲載しない */
        mp.set_flg = 0
        AND nep.syohin_code IS NULL
        AND (cal.endofavailability IS NULL OR cal.endofavailability >= :delDateFromSalesEnd)
      GROUP BY pci.ne_syohin_syohin_code
      ORDER BY MAX(受注日) DESC, ne_syohin_syohin_code ASC
      LIMIT {$addLimit}
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':delDateFromSalesEnd', $this->delDateFromSalesEnd->format('Y-m-d 00:00:00'));
    $stmt->execute();
    
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /// 出力処理
  private function export($doUpload = false, $ignorePriceDiff = false)
  {
    $logger = $this->getLogger();
    $db = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'NextEngineCSV出力';
    $subTitle = 'Export___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $fs = new FileSystem();
    $outputDir = $this->getFileUtil()->getWebCsvDir() . '/NextEngine';
    if (!$fs->exists($outputDir)) {
      $fs->mkdir($outputDir, 0755);
    }

    // 2日以上前のファイルはディレクトリ毎削除
    $ago = new \DateTime();
    $ago->modify('-2 day');
    $limitStr = $ago->format('YmdHis');

    $finder = new Finder();
    $dirs = $finder->in($outputDir)->directories();
    $removeDirs = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $dir */
    foreach ($dirs as $dir) {
      $dirTime = $dir->getBasename();
      if ($dirTime < $limitStr) {
        $removeDirs[] = $dir;
      }
    }
    foreach($removeDirs as $dir) { // 上のforeach の中でディレクトリを削除するとエラーになるため、別処理で削除。
      $fs->remove($dir);
    }

    // 保存ディレクトリ作成
    $saveDir = $outputDir . '/' . (new \DateTime())->format('YmdHis');
    if ($fs->exists($saveDir)) {
      throw new RuntimeException('duplicate save directory.');
    }
    $fs->mkdir($saveDir, 0755);
    
    // ワークディレクトリ
    $currentDir = $outputDir . '/' . self::CURRENT_UPLOAD_DIRECTORY_NAME;

    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $result = [
        'NE_Products' => 0
      , 'NE_DeleteMaster' => 0
    ];

    // 出力処理

    // ====================
    // NE_Products
    // ====================
    // 既存ファイルを削除（ディレクトリを分けているからおそらく不要だが一応）
    $fs = new FileSystem();
    $finder = new Finder();
    $files = $finder->in($saveDir)->name('/NE_Products_\d+.csv/')->files();
    $fs->remove($files);

    $addWhereSql = '';
    if (! $ignorePriceDiff) { // 価格の差分を無視しない
      $addWhereSql = <<<EOD
        OR IFNULL(cal.baika_tnk, 0) <> IFNULL(mp_former.baika_tnk, 0)
        OR cal.cost_tanka           <> IFNULL(mp_former.genka_tnk_ave, 0)
EOD;
    }
    
    // 抽出対象のデータ取得。差分のみの絞り込みもここで行う
    $sql = <<<EOD
      SELECT
            nep.syohin_code
          , nep.sire_code
          , nep.jan_code
          , nep.kataban
          , nep.syohin_name
          , nep.syohin_kbn
          , nep.toriatukai_kbn
          , nep.genka_tnk
          , nep.baika_tnk
          , nep.daihyo_syohin_code
          , nep.tag
          , nep.location
          , nep.org_select2
          , nep.org_select3
          , nep.org_select4
          , nep.visible_flg
          , nep.yoyaku_zaiko_su
      FROM tb_ne_products nep
      INNER JOIN tb_productchoiceitems pci ON nep.syohin_code = pci.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts_cal as cal ON cal.daihyo_syohin_code = pci.daihyo_syohin_code
      INNER JOIN tb_mainproducts mp ON mp.daihyo_syohin_code = pci.daihyo_syohin_code
      LEFT JOIN tb_totalstock_dl as dl ON pci.ne_syohin_syohin_code = dl.商品コード
      LEFT JOIN tb_mainproducts_former as mp_former ON mp_former.daihyo_syohin_code = pci.daihyo_syohin_code
      LEFT JOIN tb_productchoiceitems_former as pci_former ON pci.ne_syohin_syohin_code = pci_former.ne_syohin_syohin_code
      /*
       * 差分抽出。NE側にデータがない、またはNE側と指定項目に差分がある
       */
      WHERE (
        dl.商品コード IS NULL -- NE側にデータがない
        OR ( -- 差分あり (差分の基準は #122372)
          nep.sire_code <> IFNULL(mp_former.sire_code, '')
          OR pci.colname <> IFNULL(pci_former.colname, '')
          OR pci.rowname <> IFNULL(pci_former.rowname, '')
          OR mp.daihyo_syohin_name <> IFNULL(mp_former.daihyo_syohin_name, '')
          {$addWhereSql}
        )
      ) AND COALESCE(nep.baika_tnk, 0) > 0
      ORDER BY nep.syohin_code
EOD;

    $stmt = $db->query($sql);

    // 出力
    $fileNum = 1;
    if ($stmt->rowCount()) {

      // ヘッダ
      $headers = [
         'syohin_code'
        ,'sire_code'
        ,'jan_code'
        ,'kataban'
        ,'syohin_name'
        ,'syohin_kbn'
        ,'toriatukai_kbn'
        ,'genka_tnk'
        ,'baika_tnk'
        ,'daihyo_syohin_code'
        ,'tag'
        ,'location'
        ,'org_select2'
        ,'org_select3'
        ,'org_select4'
        ,'visible_flg'
        ,'yoyaku_zaiko_su'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $fp = null;
      $filePath = '';
      $currentPath = '';
      $fileSize = 0;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp)) {
          $filePath = sprintf('%s/NE_Products_%02d.csv', $saveDir, $fileNum);
          $currentPath = sprintf('%s/NE_Products_%02d.csv', $currentDir, $fileNum);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
          $fileSize = strlen($headerLine); // ファイルサイズリセット
          $fileNum++;

          $this->exportFiles['product'][] = $filePath;

          $logger->info($filePath . '('.$fileSize.')');
        }

        // locationの長さ制限オーバーが稀にあるため対応
        $row['location'] = str_replace(["\r", "\n"], '', $row['location']);
        if (mb_strlen($row['location'], 'UTF-8') >= 10) {
          $row['location'] = mb_substr($row['location'], 0, 10, 'UTF-8');
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $fileSize += strlen($line);
        $num++;

        // 出力ファイル切り替え
        if ($fileSize > self::UPLOAD_CSV_MAX_SIZE) {
          fclose($fp);
          
          // アップロード時は作業フォルダにもコピー
          if ($doUpload) {
            $fs->copy($filePath, $currentPath);
          }
          unset($fp);
        }
      }
      if (isset($fp)) {
        fclose($fp);
        
        // アップロード時は作業フォルダにもコピー
        if ($doUpload) {
          $fs->copy($filePath, $currentPath);
        }
      }

      $result['NE_Products'] = $num;
      $logger->info("NextEngine CSV出力 NE_Products: $num 件");

      // アップロード時はtb_mainproductsとtb_productchoiceitemsを退避
      // アップロードOFFでも手動アップロードされる場合や、アップロード時にエラーになる場合もあるので完全ではないが、だいたいの状況を保持
      // 厳密な差分チェックはNEから商品情報をダウンロードして比較が必要だが、plusnao_log_db.tb_product_price_log も多少参考とできる
      if ($doUpload) {
        $this->backupExportedProducts($ignorePriceDiff);
      }
    } else {
      $logger->info("NextEngine CSV出力 NE_Products: 件数が0のためファイルは作成しませんでした。");
    }


    // =========================================
    // NE_DeleteMaster　削除対象 抽出
    // ・NE側にあり、prepareで組み立てた tb_ne_products にないもの
    // ・入荷入力反映確認用仮想商品は削除対象外
    // =========================================
    $virtualCode = $commonUtil->getSettingValue('NYUKA_HANNEI_KAKUNIN_CODE');

    $filePath = $saveDir . '/NE_DeleteMaster.csv';
    // ファイルがあれば削除
    if ($fs->exists($filePath)) {
      $fs->remove($filePath);
    }

    // データ取得
    $sql = <<<EOD
      SELECT
        dl.商品コード
      FROM tb_totalstock_dl dl
      LEFT JOIN tb_ne_products p ON dl.商品コード = p.syohin_code
      WHERE p.syohin_code IS NULL
        AND dl.商品コード <> :virtualCode
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':virtualCode', $virtualCode);
    $stmt->execute();
    
    // 出力
    if ($stmt->rowCount()) {
      $fp = fopen($filePath, 'wb'); // 上書き
      // ヘッダ
      $headers = [
        '商品コード'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      fputs($fp, mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n");

      // データ
      $num = 0;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $line = $stringUtil->convertArrayToCsvLine($row);
        fputs($fp, mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n");
        $num++;
      }
      fclose($fp);

      $this->exportFiles['delete_master'][] = $filePath;
      $result['NE_DeleteMaster'] = $num;
      $logger->info("NextEngine CSV出力 NE_DeleteMaster: $num 件");

    } else {
      $logger->info("NextEngine CSV出力 NE_DeleteMaster: 件数が0のためファイルは作成しませんでした。");
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了')->setInformation($result));

    return $saveDir;
  }


  /**
   * 差分出力用に、出力の元データを保存する。
   * 
   * ・tb_mainproducts_former、tb_productchoiceitems_former には、現在NextEngineに登録されているはずのデータが入る。
   * ・tb_mainproducts_former_pre、tb_productchoiceitems_former_pre はさらにそのバックアップ。
   */ 
  private function backupExportedProducts($ignorePriceDiff = false)
  {
    $logger = $this->getLogger();
    $logger->debug('tb_mainproducts_former、tb_productchoiceitems_formerの退避処理');
    $db = $this->getDb('main');

    //'tb_mainproductsの現在の状態を保存
    $db->query("TRUNCATE tb_mainproducts_former_pre");
    /** @noinspection SqlDialectInspection */
    $db->query("INSERT INTO tb_mainproducts_former_pre SELECT * FROM tb_mainproducts_former");

    $db->query("TRUNCATE tb_mainproducts_former");
    $sql = <<<EOD
      INSERT INTO tb_mainproducts_former (
          daihyo_syohin_code
        , sire_code
        , jan_code
        , syohin_kbn
        , genka_tnk
        , genka_tnk_ave
        , additional_cost
        , baika_tnk
        , daihyo_syohin_name
        , visible_flg
      )
      SELECT 
          m.daihyo_syohin_code
        , m.sire_code
        , m.jan_code
        , IF (m.syohin_kbn = '10', '10', '0')
        , m.genka_tnk
        , cal.cost_tanka
        , m.additional_cost
        , cal.baika_tnk
        , m.daihyo_syohin_name
        , 1 AS visible_flg
      FROM
        tb_mainproducts as m
        INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN (
          SELECT distinct pci.daihyo_syohin_code
          FROM tb_ne_products nep 
          JOIN tb_productchoiceitems pci ON nep.syohin_code = pci.ne_syohin_syohin_code
        ) nep ON nep.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();

    // 原価・売価の差分を無視する時は、新規SKUでなければ、価格関連のみ、書き戻し。
    if ($ignorePriceDiff) {
      $sql = <<<EOD
        UPDATE tb_mainproducts_former f
          INNER JOIN tb_mainproducts_former_pre p
            ON f.daihyo_syohin_code = p.daihyo_syohin_code
        SET
          f.genka_tnk_ave = p.genka_tnk_ave,
          f.baika_tnk = p.baika_tnk;
EOD;
      $stmt = $db->prepare($sql);
      $stmt->execute();
    }

    // 'tb_productchoiceitemsの現在の状態を保存
    $db->query("TRUNCATE tb_productchoiceitems_former_pre");
    $sql = <<<EOD
      INSERT INTO tb_productchoiceitems_former_pre
      SELECT
          `ne_syohin_syohin_code`
        , `並び順No`
        , `colname`
        , `colcode`
        , `rowname`
        , `rowcode`
        , `受発注可能フラグ`
        , `toriatukai_kbn`
        , `zaiko_teisu`
        , `hachu_ten`
        , `lot`
        , `daihyo_syohin_code`
        , `tag`
        , `location`
        , `フリー在庫数`
        , `予約フリー在庫数`
        , `予約在庫修正値`
        , `在庫数`
        , `発注残数`
        , `最古発注伝票番号`
        , `最古発注日`
        , `previouslocation`
        , `予約引当数`
        , `引当数`
        , `予約在庫数`
        , `不良在庫数`
        , `label_application`
        , `check_why`
        , `gmarket_copy_check`
        , `temp_shortage_date`
        , `maker_syohin_code`
        , `在庫あり時納期管理番号`
        , `created`
        , `updated`
      FROM tb_productchoiceitems_former
EOD;
    $db->query($sql);

    $db->query("TRUNCATE tb_productchoiceitems_former");
    $sql = <<<EOD
      INSERT INTO tb_productchoiceitems_former
      SELECT
          pci.`ne_syohin_syohin_code`
        , pci.`並び順No`
        , pci.`colname`
        , pci.`colcode`
        , pci.`rowname`
        , pci.`rowcode`
        , pci.`受発注可能フラグ`
        , pci.`toriatukai_kbn`
        , pci.`zaiko_teisu`
        , pci.`hachu_ten`
        , pci.`lot`
        , pci.`daihyo_syohin_code`
        , pci.`tag`
        , pci.`location`
        , pci.`フリー在庫数`
        , pci.`予約フリー在庫数`
        , pci.`予約在庫修正値`
        , pci.`在庫数`
        , pci.`発注残数`
        , pci.`最古発注伝票番号`
        , pci.`最古発注日`
        , pci.`previouslocation`
        , pci.`予約引当数`
        , pci.`引当数`
        , pci.`予約在庫数`
        , pci.`不良在庫数`
        , pci.`label_application`
        , pci.`check_why`
        , pci.`gmarket_copy_check`
        , pci.`temp_shortage_date`
        , pci.`maker_syohin_code`
        , pci.`在庫あり時納期管理番号`
        , pci.`created`
        , pci.`updated`
      FROM tb_productchoiceitems pci
      INNER JOIN tb_mainproducts_cal as cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ne_products nep ON nep.syohin_code = pci.ne_syohin_syohin_code
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();
  }

}
