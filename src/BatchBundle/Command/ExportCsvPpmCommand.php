<?php
/**
 * PPM CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\PpmMallProcess;
use Doctrine\DBAL\Driver\Statement;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExportCsvPpmCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  private $results;

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM = 10000; // 1万件で分割

  // #13981 zl2030 を、商品コード p-zl2030 としても販売するための一時的な改修
  private static $SPECIAL_PRODUCT_CODE = [
//    'p-zl2030' => 'ZL2030'
//    'hw-P-ONPI' => 'hw-P-ONPI'
    'hw8079a' => 'hw8079a'
  ];

  // CVSファイル仕様
  // http://www.pa-solution.net/alphascope/recruit-cap/close/Detail.aspx?id=168&page=0&listNo=0&category=0

  // ファイル名
  // item.csv
  // category.csv
  // option.csv

  // (エラーファイル)
  // item_errorYYYYMMDhhmmss.csv
  // category_errorYYYYMMDhhmmss.csv
  // option_errorYYYYMMDhhmmss.csv

  const IMPORT_PATH = 'Ppm/Import';
  const EXPORT_PATH = 'Ppm/Export';

  const UPLOAD_EXEC_TITLE = 'PPM CSV出力処理';

  protected $exportPath;
  protected $importPath;

  protected $skipCommonProcess = false;
  protected $doUpload = true;

  protected $itemMaxCount = 50000; // 最大登録件数: tb_setting ('PPM_ITEM_MAX') / 初期値は 2016/11/01現在の設定値50,000

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-ppm')
      ->setDescription('CSVエクスポート PPM')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('import-dir', null, InputOption::VALUE_OPTIONAL, 'インポートディレクトリ', null)
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null)
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理をスキップ', '0')
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ', 1)
      ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('PPM CSV出力処理を開始しました。');

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

      $now = new \DateTimeImmutable();
      $this->stopwatch->start('main');

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
        , 'item' => [
            'delete' => null
          , 'item_update_del_select' => null
          , 'add' => null
        ]
        , 'select' => [
            'delete' => null
          , 'add_s' => null
          , 'add_o' => null
        ]
        , 'category' => [
            'delete' => null
          , 'add' => null
        ]
        , 'files' => []
      ];

      // 最大商品登録件数
      $itemMaxCount = intval($commonUtil->getSettingValue('PPM_ITEM_MAX'));
      if ($itemMaxCount) {
        $this->itemMaxCount = $itemMaxCount;
      }

      // 出力パス
      $this->exportPath = $input->getOption('export-dir');
      if (!$this->exportPath) {
        $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $now->format('YmdHis');
      }

      // 出力ディレクトリ 作成
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $fs->mkdir($this->exportPath, 0755);
      }

      // インポート元ディレクトリ
      $this->importPath = $input->getOption('import-dir');
      if ($this->importPath && !$fs->exists($this->importPath)) {
        $fs->mkdir($this->importPath);
      }

      if (!$fs->exists($this->importPath)) {
        throw new \RuntimeException('インポートディレクトリがありません。');
      }

      // CSVアップロードフラグ
      $this->doUpload = (bool)$input->getOption('do-upload');

      // 共通処理スキップフラグ
      $this->skipCommonProcess = boolval($input->getOption('skip-common-process'));

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0

      $logExecTitle = sprintf('PPM CSV出力処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      if (! $this->skipCommonProcess) {
        $commonUtil->exportCsvCommonProcess($logger, null);
        $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_PPM);
      }

      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // CSV出力 データ作成処理 実装

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));


      // CSV出力処理 実装

      // '====================
      // 'エクスポート1回目
      // '====================
      // CSVファイルインポート
      $this->importDownloadData($this->importPath);

      // 商品データ 準備
      $this->prepareItem();

      // 商品削除CSV出力
      $this->exportDeleteItemCsv();

      // PPM画像削除用CSV 作成
      $this->createDeleteImagesCsv();
      // '====================
      // 'エクスポート2回目
      // '====================
      // NE更新カラムリセット → tb_ppm_itemlist_add へ移動したため不要。

      // PPM_NE更新カラム補正
      $this->updatePpmNextEngineUpdateColumn();

      // 商品項目削除CSV出力
      $this->exportUpdateItemDeleteSelectCsv();

      // '====================
      // 'エクスポート3回目
      // '====================
      //    Call setPpmInformation___(False)
      $this->createItemData();

      // 商品追加・更新CSV出力
      $this->exportUpdateItemCsv();

      // '====================
      // 'エクスポート4回目
      // '====================

      // 選択肢データ準備
      $this->prepareSelect();

      // ppmカテゴリ登録
      $this->createCategories();

      // 削除対象カテゴリ一覧 準備
      $this->prepareCategory();

      // 購入オプション削除CSV出力
      $this->exportDeleteSelectCsv();

      // カテゴリ削除CSV出力
      $this->exportDeleteCategoryCsv();

      // '====================
      // 'エクスポート5回目
      // '====================
      // SKU在庫登録CSV, 購入オプション登録CSV 出力
      $this->exportAddSelectCsv();

      // カテゴリ登録CSV出力
      $this->exportAddCategoryCsv();

      // '====================
      // Call NE更新カラムリセット
      // ここだけ、帳尻（処理が終わったらリセットされている、という出口）を合わせるため残す。
      // '====================
      // NE更新カラムリセット → tb_ppm_itemlist_add へ移動したため不要。

      $finder = new Finder(); // 結果ファイル確認
      $fileNum = $finder->in($this->exportPath)->files()->count();
      if (!$fileNum) {
        $this->results['message'] = 'CSVファイルが作成されませんでした。処理を完了します。';
        // 空のディレクトリを削除
        $fs = new FileSystem();
        $fs->remove($this->exportPath);
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '終了')->setInformation($this->results));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('PPM CSV出力処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('PPM CSV Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('PPM CSV出力処理', 'PPM CSV出力処理', 'エラー終了')->setInformation($e->getMessage())
        , true, 'PPM CSV出力処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * カテゴリー登録CSV出力
   */
  private function exportAddCategoryCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'カテゴリー追加CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // '====================
    // 'category_add.csv
    // '====================
    $logger->info('category_add.csv を作成中です');

    $sql = <<<EOD
      SELECT
         'n'                      AS コントロールカラム
        , c.商品管理番号（商品URL） AS 商品管理ID（商品URL）
        , c.商品名                 AS 商品名
        , c.表示先カテゴリ          AS ショップ内カテゴリ
        , c.優先度                 AS 表示順位
      FROM tb_ppm_itemlist_add l
      INNER JOIN tb_ppm_category c ON l.商品番号 = c.daihyo_syohin_code
      ORDER BY c.商品管理番号（商品URL）
EOD;
    $stmt = $dbMain->query($sql);

    $updateCount = $stmt->rowCount();
    $logger->info('PPM カテゴリ追加 category追加CSV : ' . $updateCount);

    $this->results['category']['add'] = $updateCount;
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , '商品名'
        , 'ショップ内カテゴリ'
        , '表示順位'
      ];

      // 出力処理
      $files = $this->exportCsv($stmt, $this->exportPath, 'category_add', $headers, function($row) use ($headers) {

        $item = [];
        foreach($headers as $header) {
          $item[$header] = isset($row[$header]) ? $row[$header] : '';
        }

        return $item;
      });

      // FTPアップロード ※空になるのを待つ
      /** @var PpmMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.ppm');
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'category.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * SKU在庫登録CSV, 購入オプション登録CSV 出力
   */
  private function exportAddSelectCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'SKU在庫登録CSV & 購入オプション登録CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // '====================
    // 'select_add_s.csv
    // '====================
    $logger->info('select_add_s.csv を作成中です');

    $sql = <<<EOD
      SELECT
          s.コントロールカラム
        , s.商品管理ID（商品URL）
        , s.選択肢タイプ
        , s.購入オプション名
        , s.オプション項目名
        , s.SKU横軸項目ID
        , s.SKU横軸項目名
        , s.SKU縦軸項目ID
        , s.SKU縦軸項目名
        , s.SKU在庫数
      FROM tb_ppm_itemlist_add l
      INNER JOIN tb_ppm_select_add s ON l.商品番号 = s.商品管理ID（商品URL）
      WHERE s.選択肢タイプ = 's'
      ORDER BY l.ID
             , s.ID
EOD;
    $stmt = $dbMain->query($sql);

    $updateCount = $stmt->rowCount();
    $logger->info('PPM SKU在庫登録 option追加CSV : ' . $updateCount);

    $this->results['select']['add_s'] = $updateCount;
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , '選択肢タイプ'
        , '購入オプション名'
        , 'オプション項目名'
        , 'SKU横軸項目ID'
        , 'SKU横軸項目名'
        , 'SKU縦軸項目ID'
        , 'SKU縦軸項目名'
        , 'SKU在庫数'
      ];

      // 出力処理
      $files = $this->exportCsv($stmt, $this->exportPath, 'select_add_s', $headers, function($row) use ($headers) {

        $item = [];
        foreach($headers as $header) {
          $item[$header] = isset($row[$header]) ? $row[$header] : '';
        }

        return $item;
      });

      // FTPアップロード ※空になるのを待つ
      /** @var PpmMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.ppm');
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'option.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    // '====================
    // 'select_add_o.csv
    // '====================
    $logger->info('select_add_o.csv を作成中です');

    $sql = <<<EOD
      SELECT
          s.コントロールカラム
        , s.商品管理ID（商品URL）
        , s.選択肢タイプ
        , s.購入オプション名
        , s.オプション項目名
        , s.SKU横軸項目ID
        , s.SKU横軸項目名
        , s.SKU縦軸項目ID
        , s.SKU縦軸項目名
        , s.SKU在庫数
      FROM tb_ppm_itemlist_add l
      INNER JOIN tb_ppm_select_add s ON l.商品番号 = s.商品管理ID（商品URL）
      WHERE s.選択肢タイプ = 'o'
      ORDER BY l.ID
             , s.ID
EOD;
    $stmt = $dbMain->query($sql);

    $updateCount = $stmt->rowCount();
    $logger->info('PPM 購入オプション追加 option追加CSV : ' . $updateCount);

    $this->results['select']['add_o'] = $updateCount;
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , '選択肢タイプ'
        , '購入オプション名'
        , 'オプション項目名'
        , 'SKU横軸項目ID'
        , 'SKU横軸項目名'
        , 'SKU縦軸項目ID'
        , 'SKU縦軸項目名'
        , 'SKU在庫数'
      ];

      // 出力処理
      $files = $this->exportCsv($stmt, $this->exportPath, 'select_add_o', $headers, function($row) use ($headers) {

        $item = [];
        foreach($headers as $header) {
          $item[$header] = isset($row[$header]) ? $row[$header] : '';
        }

        return $item;
      });

      // FTPアップロード ※空になるのを待つ
      /** @var PpmMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.ppm');
      if ($this->doUpload) {
        foreach($files as $filePath) {
          $processor->enqueueUploadCsv($filePath, 'option.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
        }
      }

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * カテゴリー削除CSV出力
   */
  private function exportDeleteCategoryCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'カテゴリー削除CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('category_del.csv を作成中です');

    // '====================
    // 'category_del.csv
    // '====================
    $sql = <<<EOD
      SELECT
          'd' AS コントロールカラム
        , dl.商品管理ID（商品URL）
        , dl.ショップ内カテゴリ
      FROM tb_ppm_category_del_target t
      INNER JOIN tb_ppm_category_dl dl ON t.daihyo_syohin_code = dl.商品管理ID（商品URL）
      WHERE dl.商品管理ID（商品URL） <> ''
        AND dl.ショップ内カテゴリ <> ''
      ORDER BY dl.商品管理ID（商品URL）
EOD;
    $stmt = $dbMain->query($sql);

    $updateCount = $stmt->rowCount();
    $logger->info('PPM カテゴリ削除CSV category削除 : ' . $updateCount);

    $this->results['category']['delete'] = $updateCount;
    $files = [];
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , 'ショップ内カテゴリ'
      ];

      // 出力処理
      $files = $this->exportCsv($stmt, $this->exportPath, 'category_del', $headers, function($row) use ($headers) {

        $item = [];
        foreach($headers as $header) {
          $item[$header] = isset($row[$header]) ? $row[$header] : '';
        }

        return $item;
      });

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    // FTPアップロード ※空になるのを待つ
    /** @var PpmMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.ppm');
    if ($this->doUpload) {
      foreach($files as $filePath) {
        $processor->enqueueUploadCsv($filePath, 'category.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * 購入オプション削除CSV出力
   */
  private function exportDeleteSelectCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '購入オプション削除CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('select_del.csv を作成中です');

    // '====================
    // 'select_del.csv
    // '====================
    $sql = <<<EOD
      SELECT
          'd'  AS コントロールカラム
        , dl.商品管理ID（商品URL）
        , dl.選択肢タイプ
        , dl.購入オプション名
        , dl.オプション項目名
        , dl.SKU横軸項目ID
        , dl.SKU横軸項目名
        , dl.SKU縦軸項目ID
        , dl.SKU縦軸項目名
        , NULL AS SKU在庫数
      FROM tb_ppm_select_del_target t
      INNER JOIN tb_ppm_select_dl dl ON t.daihyo_syohin_code = dl.商品管理ID（商品URL）
      WHERE dl.選択肢タイプ = 'o'
      ORDER BY
          dl.商品管理ID（商品URL）
        , dl.購入オプション名
        , dl.SKU横軸項目ID
        , dl.SKU縦軸項目ID
EOD;
    $stmt = $dbMain->query($sql);

    $updateCount = $stmt->rowCount();
    $logger->info('PPM 購入オプション削除 select削除CSV : ' . $updateCount);

    $this->results['select']['delete'] = $updateCount;
    $files = [];
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , '選択肢タイプ'
        , '購入オプション名'
        , 'オプション項目名'
        , 'SKU横軸項目ID'
        , 'SKU横軸項目名'
        , 'SKU縦軸項目ID'
        , 'SKU縦軸項目名'
        , 'SKU在庫数'
      ];

      // 出力処理
      $files = $this->exportCsv($stmt, $this->exportPath, 'select_del', $headers, function($row) use ($headers) {

        $item = [];
        foreach($headers as $header) {
          $item[$header] = isset($row[$header]) ? $row[$header] : '';
        }

        return $item;
      });

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    // FTPアップロード ※空になるのを待つ
    /** @var PpmMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.ppm');
    if ($this->doUpload) {
      foreach($files as $filePath) {
        $processor->enqueueUploadCsv($filePath, 'option.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * カテゴリデータ準備
   */
  private function prepareCategory()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logger->info('カテゴリデータの準備中です');

    $dbMain->query("TRUNCATE tb_ppm_category_del_target");

    // '削除対象カテゴリリストを作成
    // （というか、全削除）
    $sql = <<<EOD
      INSERT IGNORE INTO tb_ppm_category_del_target (
        daihyo_syohin_code
      )
      SELECT
          dl.商品管理ID（商品URL）
      FROM tb_ppm_category_dl AS dl
      WHERE dl.`商品管理ID（商品URL）` <> ''
        AND dl.`ショップ内カテゴリ` <> ''
EOD;
    $dbMain->query($sql);

    // 'ただし出品フラグオフの商品は削除せずに残す(delテーブルから削除する)
    // → 意図が忘れられ不明となっており、この処理はスキップ。（カテゴリを削除する）
//    $sql = <<<EOD
//      DELETE t
//      FROM tb_ppm_category_del_target AS t
//      INNER JOIN tb_ppm_information AS i ON t.daihyo_syohin_code = i.daihyo_syohin_code
//      WHERE i.registration_flg = 0
//EOD;
//    $dbMain->query($sql);
  }


  /**
   * カテゴリ登録
   */
  private function createCategories()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'ppmカテゴリ登録___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dbMain->query("TRUNCATE tb_ppm_category");

    $logger->info('個別カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_ppm_category (
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          cal.daihyo_syohin_code
        , LOWER(cal.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , cal.`rakutencategories_3` AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE cal.endofavailability IS NULL
        AND cal.rakutencategories_3 <> ''
EOD;
    $dbMain->query($sql);

    $logger->info('全商品カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_ppm_category (
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          m.daihyo_syohin_code
        , LOWER(m.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , CONCAT('全商品\\\\', d.`rakutencategories_1`) AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d ON m.NEディレクトリID = d.NEディレクトリID
      WHERE cal.endofavailability IS NULL
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      INSERT INTO tb_ppm_category(
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          m.daihyo_syohin_code
        , LOWER(m.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , LEFT(
            d.rakutencategories_1
          , INSTR(d.rakutencategories_1, '\\\\') - 1
        ) AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d ON m.NEディレクトリID = d.NEディレクトリID
      WHERE INSTR(d.rakutencategories_1, '\\\\') <> 0
        AND cal.endofavailability IS NULL
EOD;
    $dbMain->query($sql);

    $logger->info('送料無料カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_ppm_category (
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          cal.daihyo_syohin_code
        , LOWER(cal.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , '送料無料' AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d ON m.NEディレクトリID = d.NEディレクトリID
      WHERE cal.endofavailability IS NULL
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();


    $logger->info('ゲリラSALEカテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_ppm_category(
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          cal.daihyo_syohin_code
        , LOWER(cal.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , 'ゲリラSALE' AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information         AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_title_parts             AS tp  ON m.daihyo_syohin_code = tp.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
      WHERE cal.endofavailability IS NULL
        AND tp.front_title LIKE '%【ゲリラSALE】%'

EOD;
    $dbMain->query($sql);

    $logger->info('新着カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_ppm_category (
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          cal.daihyo_syohin_code
        , LOWER(cal.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , '新着' AS 表示先カテゴリ
      FROM tb_ppm_information AS i
      INNER JOIN tb_mainproducts            AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
      WHERE m.登録日時 >= DATE_ADD(CURRENT_DATE, INTERVAL - 7 DAY)
        AND cal.endofavailability IS NULL
EOD;
    $dbMain->query($sql);

    $logger->info('即納・一部即納カテゴリ を登録しています。');
    $sql = <<<EOD
      INSERT INTO tb_ppm_category (
          daihyo_syohin_code
        , `商品管理番号（商品URL）`
        , 商品名
        , 表示先カテゴリ
      )
      SELECT
          cal.daihyo_syohin_code
        , LOWER(cal.daihyo_syohin_code)
        , m.daihyo_syohin_name
        , '即納・一部即納' AS 表示先カテゴリ
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d ON m.NEディレクトリID = d.NEディレクトリID
      WHERE cal.endofavailability IS NULL
        AND cal.deliverycode_pre IN (
            :deliveryCodeReady
          , :deliveryCodeReadyPartially
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->execute();

    $logger->info('カテゴリ優先度を設定しています');
    $sql = <<<EOD
      UPDATE tb_ppm_category AS c
      INNER JOIN tb_mainproducts_cal AS cal ON c.daihyo_syohin_code = cal.daihyo_syohin_code
      SET `優先度` = 1000 - cal.priority
      WHERE cal.priority < 1000
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_ppm_category AS c
      INNER JOIN tb_mainproducts_cal AS cal ON c.daihyo_syohin_code = cal.daihyo_syohin_code
      SET `優先度` = 1
      WHERE cal.priority >= 1000
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * 選択肢準備
   */
  private function prepareSelect()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '選択肢・オプションデータ作成';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('選択肢・オプションデータを作成しています。');

    $dbMain->query("TRUNCATE tb_ppm_select_add");

    $sql = <<<EOD
      INSERT INTO tb_ppm_select_add (
          `コントロールカラム`
        , `商品管理ID（商品URL）`
        , `選択肢タイプ`
        , `購入オプション名`
        , `オプション項目名`
        , `SKU横軸項目ID`
        , `SKU横軸項目名`
        , `SKU縦軸項目ID`
        , `SKU縦軸項目名`
        , `SKU在庫数`
      )
      SELECT
          'n' AS コントロールカラム
        , LOWER(pc.daihyo_syohin_code) AS 商品管理ID（商品URL）
        , 's' AS 選択肢タイプ
        , NULL AS 購入オプション名
        , NULL AS オプション項目名
        , pc.colcode AS SKU横軸項目ID
        , pc.colname AS SKU横軸項目名
        , pc.rowcode AS SKU縦軸項目ID
        , pc.rowname AS SKU縦軸項目名
        /* こちらは受発注販売時の計算
        , CASE
            WHEN pc.予約フリー在庫数 = 99999 THEN 99999
            ELSE pc.予約フリー在庫数 + pc.フリー在庫数
          END AS SKU在庫数
        */
        , pc.フリー在庫数 AS SKU在庫数
      FROM tb_ppm_itemlist_add AS l
      INNER JOIN tb_mainproducts       AS m  ON l.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_productchoiceitems AS pc ON m.daihyo_syohin_code = pc.daihyo_syohin_code
      WHERE l.NE更新カラム IN ('n', 'v', 'u')
      ORDER BY l.ID
             , pc.並び順No
EOD;
    $dbMain->query($sql);

//    // #13981 特別商品 p-zl2030
//    foreach(self::$SPECIAL_PRODUCT_CODE as $code => $originalCode) {
//
//      $sql = <<<EOD
//      INSERT INTO tb_ppm_select_add (
//          `コントロールカラム`
//        , `商品管理ID（商品URL）`
//        , `選択肢タイプ`
//        , `購入オプション名`
//        , `オプション項目名`
//        , `SKU横軸項目ID`
//        , `SKU横軸項目名`
//        , `SKU縦軸項目ID`
//        , `SKU縦軸項目名`
//        , `SKU在庫数`
//      )
//      SELECT
//          'n' AS コントロールカラム
//        , LOWER(:code) AS 商品管理ID（商品URL）
//        , 's' AS 選択肢タイプ
//        , NULL AS 購入オプション名
//        , NULL AS オプション項目名
//        , pc.colcode AS SKU横軸項目ID
//        , pc.colname AS SKU横軸項目名
//        , pc.rowcode AS SKU縦軸項目ID
//        , pc.rowname AS SKU縦軸項目名
//        , CASE
//            WHEN pc.予約フリー在庫数 = 99999 THEN 99999
//            ELSE pc.予約フリー在庫数 + pc.フリー在庫数
//          END AS SKU在庫数
//      FROM tb_ppm_itemlist_add AS l
//      INNER JOIN tb_mainproducts       AS m  ON l.商品番号 = m.daihyo_syohin_code
//      INNER JOIN tb_productchoiceitems AS pc ON m.daihyo_syohin_code = pc.daihyo_syohin_code
//      WHERE l.NE更新カラム IN ('n', 'v', 'u')
//        AND
//      ORDER BY l.ID
//             , pc.並び順No
//EOD;
//      $dbMain->query($sql);
//
//    }

    // '削除対象選択肢リストを作成
    // 注記を全て削除？
    $dbMain->query("TRUNCATE tb_ppm_select_del_target");

    $sql = <<<EOD
      INSERT IGNORE INTO tb_ppm_select_del_target (
        daihyo_syohin_code
      )
      SELECT
        dl.`商品管理ID（商品URL）`
      FROM tb_ppm_select_dl AS dl
      WHERE dl.選択肢タイプ = 'o'
EOD;
    $dbMain->query($sql);

    // 'ただし出品フラグオフの商品は削除せずに残す(delテーブルから削除する)
    // → 意図が忘れられ不明となっており、この処理はスキップ。（商品を削除する）
//    $sql = <<<EOD
//      DELETE t
//      FROM  tb_ppm_select_del_target AS t
//      INNER JOIN tb_ppm_information AS i ON t.daihyo_syohin_code = i.daihyo_syohin_code
//      WHERE i.registration_flg = 0
//EOD;
//    $dbMain->query($sql);


    // ---------------------------------------------
    // '注記作成
    // ---------------------------------------------

    // '====================
    // '追加用注記データ
    // '====================
    $logger->info("追加用注記データの準備中です");

    $settings = [
        '宅配便別出力可否'          => $commonUtil->getSettingValue('PPM_SELECT_EXCLUDE_POSTAGE')
      , '宅配便込出力可否'          => $commonUtil->getSettingValue('PPM_SELECT_INCLUDE_POSTAGE')
      , '定形外送料込出力可否'      => $commonUtil->getSettingValue('PPM_SELECT_ABNORMAL')
      , 'メール便送料込出力可否'    => $commonUtil->getSettingValue('PPM_SELECT_MAIL')
      , 'アウトレット出力可否'      => $commonUtil->getSettingValue('PPM_SELECT_OUTLET')
      , '商品到着後レビュー出力可否' => $commonUtil->getSettingValue('PPM_SELECT_REVIEW')
    ];

    // '====================
    // 'アウトレット
    // '====================
    if ($settings['アウトレット出力可否']) {
      $sql = <<<EOD
        INSERT INTO tb_ppm_select_add (
            `コントロールカラム`
          , `商品管理ID（商品URL）`
          , `選択肢タイプ`
          , `購入オプション名`
          , `オプション項目名`
        )
        SELECT
            'n' AS 項目選択肢用コントロールカラム
          , LOWER(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
          , 'o' AS 選択肢タイプ
          , 'アウトレット商品について' AS `購入オプション名`
          , '確認・了承しました。' AS `オプション項目名`
        FROM tb_ppm_itemlist_add       AS l
        INNER JOIN tb_mainproducts     AS m   ON l.商品番号 = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        WHERE cal.outlet <> 0
          AND (l.NE更新カラム <> 'n' OR l.NE更新カラム IS NULL)
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
    }

    // '====================
    // '商品到着後レビュー
    // '====================
    if ($settings['商品到着後レビュー出力可否']) {

      $addRate = 1.1; // レビュー書かない 加算率

      $sql = <<<EOD
        INSERT INTO tb_ppm_select_add (
            `コントロールカラム`
          , `商品管理ID（商品URL）`
          , `選択肢タイプ`
          , `購入オプション名`
          , `オプション項目名`
        )
        SELECT
            'n' AS 項目選択肢用コントロールカラム
          , LOWER(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
          , 'o' AS 選択肢タイプ
          , '商品到着後レビューを' AS `購入オプション名`
          , CONCAT(
              '書くのでPlusNao価格'
            , TRUNCATE (
              (IFNULL(cal.baika_tnk, 0) * CAST(:taxRate AS DECIMAL))
              , 0
            )
            , '円で購入'
          ) AS `オプション項目名`
        FROM tb_ppm_itemlist_add       AS l
        INNER JOIN tb_mainproducts     AS m   ON l.商品番号 = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        WHERE cal.outlet = 0
          AND (l.NE更新カラム <> 'n' OR l.NE更新カラム IS NULL)
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
      $stmt->execute();

      $sql = <<<EOD
        INSERT INTO tb_ppm_select_add (
            `コントロールカラム`
          , `商品管理ID（商品URL）`
          , `選択肢タイプ`
          , `購入オプション名`
          , `オプション項目名`
        )
        SELECT
            'n' AS 項目選択肢用コントロールカラム
          , LOWER((m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
          , 'o' AS 選択肢タイプ
          , '商品到着後レビューを' AS `購入オプション名`
          , CONCAT(
              '書かないので'
            , TRUNCATE (
              (
                TRUNCATE (
                  (IFNULL(cal.baika_tnk, 0) * CAST(:taxRate AS DECIMAL))
                  , 0
                ) * CAST(:addRate AS DECIMAL)
              )
              , - 1
            )
            , '円で購入'
          ) AS `オプション項目名`
        FROM tb_ppm_itemlist_add       AS l
        INNER JOIN tb_mainproducts     AS m   ON l.商品番号 = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        WHERE cal.outlet = 0
          AND (l.NE更新カラム <> 'n' OR l.NE更新カラム IS NULL)
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
      $stmt->bindValue(':addRate', $addRate, \PDO::PARAM_STR);
      $stmt->execute();
    }

    // セット商品の在庫数を更新する
    // セット商品は、セット商品としてpciに登録している在庫数ではなく、構成品の在庫数を元に計算する
    $sql = <<<EOD
      UPDATE tb_ppm_select_add s
      INNER JOIN (
        SELECT
            pci.ne_syohin_syohin_code AS set_sku
          , MIN(TRUNCATE((COALESCE(pci_detail.フリー在庫数, 0)/ d.num), 0)) AS creatable_num
        FROM tb_productchoiceitems pci 
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        WHERE m.set_flg <> 0
        GROUP BY set_sku
      ) STOCK ON CONCAT(s.`商品管理ID（商品URL）`, s.`SKU横軸項目ID`, s.`SKU縦軸項目ID`) = STOCK.set_sku
      SET s.`SKU在庫数` = STOCK.creatable_num
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 商品追加・更新CSV出力
   * item_add.csv
   */
  private function exportUpdateItemCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品追加・更新CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // '====================
    // 'item_add.csv
    // '====================
    $sql = <<<EOD
      SELECT
          CASE WHEN dl.商品管理ID（商品URL） IS NULL THEN 'n' ELSE 'u' END AS コントロールカラム
        , LOWER(m.daihyo_syohin_code)                                    AS `商品管理ID（商品URL）`
        , CASE
            WHEN (
                 m.楽天削除 <> 0
              OR (m.syohin_kbn <> '10' AND cal.endofavailability IS NOT NULL)
              OR (dl.`在庫数` = 0)
            ) THEN '1'
            ELSE '2'
          END                                                           AS 販売ステータス
        , m.daihyo_syohin_code                                          AS 商品ID

        /* 商品名は組み合わせで文字数制限を考慮して作成されるため、部品を個別に取得 */
        , tp.front_title                    AS 商品名_front_title
        , i.ppm_title                       AS 商品名_ppm_title
        , CONCAT('【',  m.sire_code,  '】')  AS 商品名_sire_code
        , tp.back_title                     AS 商品名_back_title

        , i.キャッチコピー                                                AS キャッチコピー
        , i.baika_tanka                                                 AS 販売価格
        , m.実勢価格                                                     AS 表示価格
        , '1'                                                           AS 消費税
        , '1'                                                           AS 送料
        , NULL                                                          AS `独自送料グループ(1)`
        , NULL                                                          AS `独自送料グループ(2)`
        , ''                                                            AS 個別送料 /* getkobetuPostageKbnRakutenCsv(m.送料設定) の中身は何も処理していなかった*/
        , 0                                                             AS 代引料
        , '0'                                                           AS のし対応
        , '1'                                                           AS 注文ボタン
        , '1'                                                           AS 商品問い合わせボタン
        , NULL                                                          AS 販売期間指定
        , CASE
            WHEN cal.maxbuynum = 0 THEN 'clear'
            ELSE cal.maxbuynum
          END                                                           AS 注文受付数
        , '2'                                                           AS 在庫タイプ
        , NULL                                                          AS 在庫数
        , '2'                                                           AS 在庫表示
        , i.商品説明1                                                    AS `商品説明(1)`
        , i.商品説明2                                                    AS `商品説明(2)`
        , i.商品説明テキストのみ                                           AS `商品説明(テキストのみ)`
        , i.商品画像URL                                                  AS 商品画像URL
        , d.PPMディレクトリID                                             AS モールジャンルID
        , NULL                                                          AS シークレットセールパスワード
        , NULL                                                          AS ポイント率
        , NULL                                                          AS ポイント率適用期間
        , m.横軸項目名                                                    AS SKU横軸項目名
        , m.縦軸項目名                                                    AS SKU縦軸項目名
        , 3                                                             AS SKU在庫用残り表示閾値
        , i.商品説明スマートフォン用                                       AS `商品説明(スマートフォン用)`
        , NULL                                                          AS JANコード
        , NULL                                                          AS ヘッダー・フッター・サイドバー
        , NULL                                                          AS お知らせ枠
        , NULL                                                          AS 自由告知枠
        , '1'                                                           AS 再入荷リクエストボタン
      FROM tb_ppm_itemlist_add              l
      INNER JOIN tb_mainproducts            m   ON l.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal        cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory d   ON m.NEディレクトリID = d.NEディレクトリID
      INNER JOIN tb_ppm_information         i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_title_parts             tp  ON m.daihyo_syohin_code = tp.daihyo_syohin_code
      LEFT  JOIN tb_ppm_item_dl             dl  ON m.daihyo_syohin_code = dl.商品管理ID（商品URL）
      WHERE m.daihyo_syohin_code <> ''
        AND COALESCE(i.baika_tanka, 0) > 0
      ORDER BY CASE WHEN dl.商品管理ID（商品URL） IS NULL THEN 'n' ELSE 'u' END
          , LOWER(m.daihyo_syohin_code)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $updateCount = $stmt->rowCount();
    $logger->info('PPM 商品追加・更新 item追加・更新CSV : ' . $updateCount);

    $this->results['item']['add'] = $updateCount;

    $files = [];
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , '販売ステータス'
        , '商品ID'
        , '商品名'
        , 'キャッチコピー'
        , '販売価格'
        , '表示価格'
        , '消費税'
        , '送料'
        , '独自送料グループ(1)'
        , '独自送料グループ(2)'
        , '個別送料'
        , '代引料'
        , 'のし対応'
        , '注文ボタン'
        , '商品問い合わせボタン'
        , '販売期間指定'
        , '注文受付数'
        , '在庫タイプ'
        , '在庫数'
        , '在庫表示'
        , '商品説明(1)'
        , '商品説明(2)'
        , '商品説明(テキストのみ)'
        , '商品画像URL'
        , 'モールジャンルID'
        , 'シークレットセールパスワード'
        , 'ポイント率'
        , 'ポイント率適用期間'
        , 'SKU横軸項目名'
        , 'SKU縦軸項目名'
        , 'SKU在庫用残り表示閾値'
        , '商品説明(スマートフォン用)'
        , 'JANコード'
        , 'ヘッダー・フッター・サイドバー'
        , 'お知らせ枠'
        , '自由告知枠'
        , '再入荷リクエストボタン'
      ];

      $rakutenAdditionalTitle = $this->getDbCommonUtil()->getSettingValue('RAKUTEN_ADDITIONAL_TITLE');

      // 出力処理
      $files = $this->exportCsv($stmt, $this->exportPath, 'item_add', $headers, function($row) use ($headers, $rakutenAdditionalTitle, $logger) {

        $item = [];
        foreach($headers as $header) {
          $item[$header] = isset($row[$header]) ? $row[$header] : '';
        }

        // 商品名組み立て
        /*
          , tp.front_title & cutStringByWidth(
              i.ppm_title
            , 255 - getStringWidth(
              tp.front_title & GetSetting('rakuten_additional_title') & '【' & m.sire_code & '】' & tp.back_title
            )
          ) & GetSetting('rakuten_additional_title') & '【' & m.sire_code & '】' & tp.back_title     AS 商品名
        */
        $titleWidth = 220 // => 制限は255文字幅までだが、mb_strwidth, mv_strimwidth が一部の全角記号を文字幅1で判定するため、余裕を持たせる。
                    - mb_strwidth($row['商品名_front_title'], 'UTF-8')
                    - mb_strwidth($rakutenAdditionalTitle, 'UTF-8')
                    - mb_strwidth($row['商品名_sire_code'], 'UTF-8')
                    // - mb_strwidth($row['商品名_back_title'], 'UTF-8') // 2016/12/01 しばらく即納のみで行くため納期情報を除去
                    ;
        $item['商品名'] = $row['商品名_front_title']
                       . mb_strimwidth($row['商品名_ppm_title'], 0, $titleWidth, '', 'UTF-8')
                       . $rakutenAdditionalTitle
                       . $row['商品名_sire_code']
                       // . $row['商品名_back_title'] // 2016/12/01 しばらく即納のみで行くため納期情報を除去
                       ;

        // $logger->info(sprintf('%03d : %03d : %s', $titleWidth, mb_strwidth($item['商品名']), $item['商品名']));
        return $item;
      });

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    // FTPアップロード ※空になるのを待つ
    /** @var PpmMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.ppm');
    if ($this->doUpload) {
      foreach($files as $filePath) {
        $processor->enqueueUploadCsv($filePath, 'item.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 商品CSVデータ作成
   */
  private function createItemData()
  {
    $logger = $this->getLogger();
    $logTitle = '商品情報の設定';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 'PPM_キャッチコピー再構築(lc_catchcopy) '★NE更新カラムを補正した後で呼び出すこと！
    $this->createItemCatchCopy();

    // 'PPM_商品説明1とスマートフォン用___
    $this->createItemDescription();
    // 'PPM_商品説明テキストのみ___
    $this->createItemDescriptionText();
    // 'PPM_商品説明2___
    $this->createItemDescription2();

    // 'PPM商品画像URL設定___
    $this->createItemImageUrl();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * 商品項目削除CSV出力
   * 項目を削除(空に更新する)のであって商品を削除するわけではない
   * item_update_del_select.csv
   */
  private function exportUpdateItemDeleteSelectCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品項目削除CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // '====================
    // 'item_update_del_select.csv
    // '====================
    $sql = <<<EOD
      SELECT
          'u'                          AS コントロールカラム
        , LOWER(m.daihyo_syohin_code)  AS 商品管理ID（商品URL）
        , m.daihyo_syohin_code         AS 商品ID
        , '1'                          AS 在庫タイプ
      FROM tb_ppm_itemlist_add       AS l
      INNER JOIN tb_mainproducts     AS m   ON l.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information  AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_ppm_item_dl      AS dl  ON m.daihyo_syohin_code = dl.商品管理ID（商品URL）
      WHERE l.NE更新カラム = 'v'
      ORDER BY LOWER(m.daihyo_syohin_code)
EOD;
    $stmt = $dbMain->query($sql);

    $updateCount = $stmt->rowCount();
    $logger->info('PPM 商品項目削除 item更新CSV : ' . $updateCount);

    $this->results['item']['item_update_del_select'] = $updateCount;

    $files = [];
    if ($updateCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
        , '商品ID'
        , '在庫タイプ'
      ];

      $files = $this->exportCsv($stmt, $this->exportPath, 'item_update_del_select', $headers, function($row) {
        return $row;
      });

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    // FTPアップロード ※空になるのを待つ
    /** @var PpmMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.ppm');
    if ($this->doUpload) {
      foreach($files as $filePath) {
        $processor->enqueueUploadCsv($filePath, 'item.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }


  /**
   * NE更新フラグ 更新
   * tb_ppm_itemlist_add を更新する （出品対象商品のみが対象となる）
   */
  private function updatePpmNextEngineUpdateColumn()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'PPM_NE更新カラム補正___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $logger->info('PPM_NE更新カラムを補正しています。');

    // '更新(在庫表有)(v)
    $dbMain->query("TRUNCATE tb_ppm_select_dl_s");

    $sql = <<<EOD
      INSERT  INTO tb_ppm_select_dl_s
      SELECT
          *
      FROM tb_ppm_select_dl
      WHERE 選択肢タイプ = 's'
EOD;
    $dbMain->query($sql);

    // 'ProductChoiceItemsにあってDLにない
    $sql = <<<EOD
      UPDATE tb_ppm_itemlist_add       AS l
      INNER JOIN tb_mainproducts       AS m   ON l.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal   AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information    AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_productchoiceitems AS pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      LEFT JOIN tb_ppm_select_dl_s     AS dl  ON pci.daihyo_syohin_code = dl.`商品管理ID（商品URL）`
                                             AND pci.colcode = dl.SKU横軸項目ID
                                             AND pci.rowcode = dl.SKU縦軸項目ID
      SET l.NE更新カラム = 'v'
      WHERE dl.`商品管理ID（商品URL）` IS NULL
EOD;
    $dbMain->query($sql);

    // 'ただし1件もDLにないものはvにしてはいけない
    // （ 要 'v' と 'u' の使い分けの意図の確認 )
    // → おそらく、'v' はitemの更新（在庫タイプ:1）でざっくり選択肢を削除するがそれは不要、とかそれくらいの理由か。
    //   「いけない」というほどの理由があるかは不明。
    $sql = <<<EOD
      UPDATE tb_ppm_itemlist_add l
      SET l.NE更新カラム = 'u'
      WHERE NOT EXISTS (
          SELECT
              `商品管理ID（商品URL）`
          FROM tb_ppm_select_dl_s AS dl
          WHERE dl.`商品管理ID（商品URL）` = l.商品番号
        )
EOD;
    $dbMain->query($sql);

    // 'DLにあってProductChoiceItemsにないか、項目名が違っている
    $sql = <<<EOD
      UPDATE tb_ppm_itemlist_add      AS l
      INNER JOIN tb_ppm_select_dl     AS dl ON l.商品番号 = dl.商品管理ID（商品URL）
      INNER JOIN tb_mainproducts      AS m ON l.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal  AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information   AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_productchoiceitems AS pci ON pci.daihyo_syohin_code = dl.`商品管理ID（商品URL）`
                                            AND pci.colcode = dl.SKU横軸項目ID
                                            AND pci.rowcode = dl.SKU縦軸項目ID
      SET l.NE更新カラム = 'v'
      WHERE dl.`選択肢タイプ` = 's'
        AND (
              pci.ne_syohin_syohin_code IS NULL
           OR pci.colname <> dl.SKU横軸項目名
           OR pci.rowname <> dl.SKU縦軸項目名
        )
EOD;
    $dbMain->query($sql);

    // '新規(n)
    $sql = <<<EOD
      UPDATE tb_ppm_itemlist_add     AS l
      INNER JOIN tb_mainproducts     AS m   ON l.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_ppm_information  AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_ppm_select_dl     AS dl  ON m.daihyo_syohin_code = dl.`商品管理ID（商品URL）`
      SET l.NE更新カラム = 'n'
      WHERE dl.`SKU横軸項目ID` IS NULL
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }



  /**
   * 商品削除CSV出力
   * item_del.csv
   */
  private function exportDeleteItemCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品削除CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // '====================
    // 'item_del.csv
    // '====================
    $sql = <<<EOD
      SELECT
        del.商品番号 AS `商品管理ID（商品URL）`
      FROM tb_ppm_itemlist_del del
      WHERE del.商品番号 <> ''
      ORDER BY del.商品番号
EOD;
    $stmt = $dbMain->query($sql);

    $deleteDetailCount = $stmt->rowCount();
    $logger->info('PPM 商品削除 item削除CSV : ' . $deleteDetailCount);

    $this->results['item']['delete'] = $deleteDetailCount;
    $files = [];
    if ($deleteDetailCount) {
      $headers = [
          'コントロールカラム'
        , '商品管理ID（商品URL）'
      ];

      $files = $this->exportCsv($stmt, $this->exportPath, 'item_del', $headers, function($row) {
        return [
            'コントロールカラム' => 'd'
          , '商品管理ID（商品URL）' => $row['商品管理ID（商品URL）']
        ];
      });

      $this->results['files'] = array_merge($this->results['files'], $files);
    }

    // FTPアップロード ※空になるのを待つ
    /** @var PpmMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.ppm');
    if ($this->doUpload) {
      foreach($files as $filePath) {
        $processor->enqueueUploadCsv($filePath, 'item.csv', self::UPLOAD_EXEC_TITLE, ($this->account ? $this->account->getId() : null));
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 画像削除用CSV 作成
   * 現在はCSVを作成するだけで、アップロードは手作業
   * ファイルを分ける必要がないためCSV作成はexportCSVを使っていない
   */
  public function createDeleteImagesCsv()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '画像削除用CSVエクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $exportDir = $this->getFileUtil()->getWebCsvDir() . '/Ppm/Downloaded';

    // フォルダ内ファイル削除
    $fs = new FileSystem();
    $finder = new Finder();
    $fs->remove($finder->in($exportDir)->files());

    $now = new \DateTime();
    $fileName = sprintf('del_ppm%s.csv',$now->format('Ymd'));
    $filePath = sprintf('%s/%s', $exportDir, $fileName);
    $exportFile = new \SplFileObject($filePath, 'w');

    $header = [
        'パス' => 'パス'
      , 'ファイル名' => 'ファイル名'
      , 'ファイルサイズ' => 'ファイルサイズ'
      , '更新日時' => '更新日時'
      , '画像名' => '画像名'
      , '画像URL' => '画像URL'
    ];
    $eol = "\r\n";
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $headerLine = $stringUtil->convertArrayToCsvLine($header, [], array_keys($header), ",");
    $headerLine = mb_convert_encoding($headerLine, 'SJIS-WIN', 'UTF-8') . $eol;
    $exportFile->fwrite($headerLine);

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 削除対象画像URL出力
    // https://img.ponparemall.net/imgmgr/21/00105821/itemroot/ は 固定値だから問題ないと思うが...
    $sql = <<<EOD
    SELECT 
        CONCAT('itemroot/',img.`directory`,'/') AS 'パス'
      , img.filename AS 'ファイル名'
      , '1' AS 'ファイルサイズ'
      , REPLACE(img.updated,'-','/') AS '更新日時'
      , SUBSTRING_INDEX(img.filename,'.',1) AS '画像名'
      , CONCAT('https://img.ponparemall.net/imgmgr/21/00105821/itemroot/',img.`directory`,'/',img.filename) AS '画像URL'
    FROM tb_ppm_information ppm
    INNER JOIN product_images img ON ppm.daihyo_syohin_code = img.daihyo_syohin_code
    WHERE ppm.is_sold = 0
    AND ppm.is_uploaded_images = -1
    AND ppm.`商品画像URL` <> ''
    ORDER BY CONCAT('itemroot/',img.`directory`,'/')
EOD;
    $stmt = $dbMain->query($sql);

    $deleteImageCount = $stmt->rowCount();    
    $logger->info('PPM image削除CSV : ' . $deleteImageCount);

    if($deleteImageCount){
      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($header), array_keys($header), ",");
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . $eol;
        $exportFile->fwrite($line);
      }
    }
  }

  /**
   * 商品データ 準備 (PpmPrepareItem)
   */
  private function prepareItem()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '対象商品の抽出';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $logger->info('対象商品データを作成中です');
    $dbMain->query('TRUNCATE tb_ppm_itemlist_add');

    // '特定カテゴリ商品については出品フラグをオフにする
    $sql = <<<EOD
      UPDATE tb_mainproducts AS m
      INNER JOIN tb_ppm_information AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS ppd ON m.NEディレクトリID = ppd.NEディレクトリID
      SET i.registration_flg = 0
      WHERE ppd.フィールド1 IN ('ダイエット・健康', '家電・AV・カメラ', '美容・コスメ・香水')
        AND i.registration_flg <> 0
EOD;
    $dbMain->query($sql);

    // '共通処理で計算した優先順位等に基づき出品対象を決定する
    $sql = <<<EOD
      INSERT  INTO tb_ppm_itemlist_add (
        商品番号
      )
      SELECT
          m.daihyo_syohin_code
      FROM tb_ppm_information        AS i
      INNER JOIN tb_mainproducts     AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.deliverycode_pre IN (
            :deliveryCodeReady
          , :deliveryCodeReadyPartially
          , :deliveryCodePurchaseOnOrder
        )
        AND i.registration_flg <> 0
        AND (
          cal.adult_check_status IN (
              :adultCheckStatusWhite
            , :adultCheckStatusGray
          )
        )
        AND IFNULL(i.ppm_title, '') <> ''
      ORDER BY cal.priority DESC
             , m.登録日時 DESC
      LIMIT :itemMaxCount
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':itemMaxCount', $this->itemMaxCount, \PDO::PARAM_INT);

    $stmt->execute();

    // 一度すべての販売ステータスを0,商品画像URLは空にする
    $sql = <<<EOD
      UPDATE tb_ppm_information AS p
      SET p.is_sold = 0 
      , p.`商品画像URL` = '' 
EOD;
    $dbMain->query($sql);

    // 在庫があるもののみ販売中にする
    $sql = <<<EOD
      UPDATE tb_ppm_information AS p
      INNER JOIN tb_ppm_item_dl AS dl ON dl.`商品ID` = p.daihyo_syohin_code
      SET p.is_sold = CASE WHEN dl.`在庫数` > 0 THEN -1 ELSE 0 END
      , p.`商品画像URL` = dl.`商品画像URL`
EOD;
    $dbMain->query($sql);

    // '====================
    // '削除商品データ
    // '====================
    $logger->info('削除商品データを作成中です');
    $dbMain->query("TRUNCATE tb_ppm_itemlist_del");

    // '対象商品に含まれない出品中の商品
    $sql = <<<EOD
      INSERT INTO tb_ppm_itemlist_del (
        商品番号
      )
      SELECT
          dl.`商品管理ID（商品URL）`
      FROM tb_ppm_item_dl AS dl
      LEFT JOIN tb_ppm_itemlist_add AS a ON dl.`商品管理ID（商品URL）` = a.商品番号
      WHERE a.商品番号 IS NULL
EOD;
    $dbMain->query($sql);

    // #13981 特定商品コード群は、削除しない。（2017/04/05 暫定改修）
    if (count(self::$SPECIAL_PRODUCT_CODE)) {
      $tmp = [];
      foreach(self::$SPECIAL_PRODUCT_CODE as $code => $originalCode) {
        $tmp[] = $dbMain->quote($code, \PDO::PARAM_STR);
      }
      $specialProductCodeListStr = implode(', ', $tmp);

      $sql = <<<EOD
      DELETE del FROM tb_ppm_itemlist_del del
      WHERE del.`商品番号` IN ( {$specialProductCodeListStr} )
EOD;
      $dbMain->query($sql);
    }

    // 'ただし出品フラグオフの商品は削除せずに残す (delテーブルから削除する)
    // → 意図が忘れられ不明となっており、この処理はスキップ。（商品を削除する）
//    $sql = <<<EOD
//      DELETE del
//      FROM tb_ppm_itemlist_del del
//      INNER JOIN tb_ppm_information AS i ON del.`商品番号` = i.daihyo_syohin_code
//      WHERE i.registration_flg = 0
//EOD;
//    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 商品データインポート処理
   * @param string $importDir
   */
  private function importDownloadData($importDir)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'インポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dataNum = [
        'item' => 0
      , 'option' => 0
      , 'category' => 0
    ];

    // tb_ppm_item_dl
    // tb_ppm_select_dl
    // tb_ppm_category_dl

    $fileUtil = $this->getFileUtil();

    // 商品データ 取込
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'item', '開始'));
    $logger->info('item.csvを取り込んでいます。 [' . $importDir . ']');

    $dbMain->query('TRUNCATE tb_ppm_item_dl');

    /** @var Finder $finder */
    $finder = new Finder();
    $finder = $finder->in($importDir)->name('/item_\d+\.csv/');

    // 1ファイルだけのはずだが一応。
    /** @var SplFileInfo $file */
    foreach($finder->files() AS $file) {

      $logger->info($file->getPathname());

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      // 先頭行チェック
      $fp = fopen($tmpFilePath, 'rb');
      $headerFields = fgetcsv($fp);
      fclose($fp);
      if ($headerFields != $this->importCsvFieldsItem) {

        $logger->error(print_r($headerFields, true));
        $logger->error(print_r($this->importCsvFieldsItem, true));

        throw new \RuntimeException('インポート: item.csv のヘッダが一致しません。');
      }

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        IGNORE INTO TABLE tb_ppm_item_dl
        FIELDS OPTIONALLY ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      $count = $dbMain->query("SELECT COUNT(*) FROM tb_ppm_item_dl")->fetchColumn(0);

      fclose($tmpFile);
      $dataNum['item'] += $count;
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'item', '終了'));


    // 在庫データ 取込
    // Accessでは一度 _tmp に取り込んでいるが、波ダッシュ処理と商品コードなしデータ（不正データ）の除去以降は
    // 利用していないため、直接 _dl へ取り込む
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'option', '開始'));
    $logger->info('option.csvを取り込んでいます。 [' . $importDir . ']');

    $dbMain->query('TRUNCATE tb_ppm_select_dl');

    /** @var Finder $finder */
    $finder = new Finder();
    $finder = $finder->in($importDir)->name('/option_\d+\.csv/');

    // 1ファイルだけのはずだが一応。
    /** @var SplFileInfo $file */
    foreach($finder->files() AS $file) {

      $logger->info($file->getPathname());

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      // 先頭行チェック
      $fp = fopen($tmpFilePath, 'rb');
      $headerFields = fgetcsv($fp);
      fclose($fp);
      if ($headerFields != $this->importCsvFieldsOption) {

        $logger->error(print_r($headerFields, true));
        $logger->error(print_r($this->importCsvFieldsOption, true));

        throw new \RuntimeException('インポート: option.csv のヘッダが一致しません。');
      }

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        IGNORE INTO TABLE tb_ppm_select_dl
        FIELDS OPTIONALLY ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      $count = $dbMain->query("SELECT COUNT(*) FROM tb_ppm_select_dl")->fetchColumn(0);

      fclose($tmpFile);
      $dataNum['option'] += $count;
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'option', '終了'));


    // カテゴリデータ 取込
    // Accessでは一度 _tmp に取り込んでいるが、波ダッシュ処理と商品コードなしデータ（不正データ）の除去以降は
    // 利用していないため、直接 _dl へ取り込む
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'category', '開始'));
    $logger->info('category.csvを取り込んでいます。 [' . $importDir . ']');

    $dbMain->query('TRUNCATE tb_ppm_category_dl');

    /** @var Finder $finder */
    $finder = new Finder();
    $finder = $finder->in($importDir)->name('/category_\d+\.csv/');

    // 1ファイルだけのはずだが一応。
    /** @var SplFileInfo $file */
    foreach($finder->files() AS $file) {

      $logger->info($file->getPathname());

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      // 先頭行チェック
      $fp = fopen($tmpFilePath, 'rb');
      $headerFields = fgetcsv($fp);
      fclose($fp);
      if ($headerFields != $this->importCsvFieldsCategory) {

        $logger->error(print_r($headerFields, true));
        $logger->error(print_r($this->importCsvFieldsCategory, true));

        throw new \RuntimeException('インポート: category.csv のヘッダが一致しません。');
      }

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        IGNORE INTO TABLE tb_ppm_category_dl
        FIELDS OPTIONALLY ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      $count = $dbMain->query("SELECT COUNT(*) FROM tb_ppm_category_dl")->fetchColumn(0);

      fclose($tmpFile);
      $dataNum['category'] += $count;
    }
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'category', '終了'));

    // もし、いずれかのファイルが0件であれば何かおかしいとしてエラーとする。
    foreach($dataNum as $type => $count) {
      if (!$count) {
        throw new \RuntimeException(sprintf('インポートする %s ファイルが0件です。イレギュラーとして処理を中止しました。', $type));
      }
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了')->setInformation($dataNum));
  }


  /**
   * CSVファイル出力処理
   * @param Statement $stmt
   * @param string $exportDir
   * @param string $fileName
   * @param array $headers
   * @param callable $itemProcess
   *
   * @return array 出力ファイルパス配列
   */
  private function exportCsv(Statement $stmt, $exportDir, $fileName, $headers, $itemProcess)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $exportedFiles = [];
    $fileIndex = 1;
    $lineNum = 0;

    $fp = null;
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      if (!isset($fp)) {
        $filePath = sprintf('%s/' . $fileName . '_%02d.csv', $exportDir, $fileIndex++);
        $fp = fopen($filePath, 'wb');
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");

        $exportedFiles[] = $filePath;
      }

      $data = $itemProcess($row);

      fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      $lineNum++;

      if ($lineNum >= self::UPLOAD_CSV_MAX_NUM) {
        fclose($fp);
        unset($fp);
        $lineNum = 0;
      }
    }

    if (isset($fp)) {
      fclose($fp);
    }

    return $exportedFiles;
  }



//
//
//  /**
//   * 削除CSV出力
//   * @param string $detailPath
//   * @param string $selectPath
//   * @throws \Doctrine\DBAL\DBALException
//   */
//  private function exportDeleteCsv($detailPath, $selectPath)
//  {
//    $logger = $this->getLogger();
//    $dbMain = $this->getDb('main');
//
//    /** @var StringUtil $stringUtil */
//    $stringUtil = $this->getContainer()->get('misc.util.string');
//
//    $logTitle = 'PPM CSV出力処理';
//    $subTitle = '削除CSV出力';
//    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));
//    $logger->info('PPM 削除CSV出力');
//
//    // 削除商品用一時テーブル
//    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_shoplist_delete_code");
//    $sql = <<<EOD
//      CREATE TEMPORARY TABLE tmp_shoplist_delete_code (
//        daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
//      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
//EOD;
//    $dbMain->query($sql);
//
//    // 削除商品
//    $sql = <<<EOD
//      INSERT INTO tmp_shoplist_delete_code (
//        daihyo_syohin_code
//      )
//      SELECT
//        DISTINCT s.`商品管理番号（商品URL）`
//      FROM tb_shoplist_product_stock s
//      LEFT JOIN tb_mainproducts m ON s.`商品管理番号（商品URL）` = m.daihyo_syohin_code
//      WHERE m.daihyo_syohin_code IS NULL
//EOD;
//    $dbMain->query($sql);
//
//    $sql = <<<EOD
//      SELECT
//        daihyo_syohin_code
//      FROM tmp_shoplist_delete_code
//      ORDER BY daihyo_syohin_code
//EOD;
//    $stmt = $dbMain->query($sql);
//
//    $deleteDetailCount = $stmt->rowCount();
//    $logger->info('PPM 削除CSV出力（詳細） : ' . $deleteDetailCount);
//
//    if ($deleteDetailCount) {
//      $headers = $this->getCsvHeadersDetail();
//
//      $fs = new FileSystem();
//      $fileExists = $fs->exists($detailPath);
//      $fp = fopen($detailPath, 'ab');
//
//      if (!$fileExists) {
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//
//        $data = [
//            'コントロールカラム'      => 'd'
//          , '商品管理番号（商品URL）' => strtolower($row['daihyo_syohin_code'])
//          , '商品番号'               => $row['daihyo_syohin_code']
//        ];
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      fclose($fp);
//    }
//
//    // 削除SKU（すでにDBに存在しないSKUで、詳細で削除するもの以外）
//    $sql = <<<EOD
//      SELECT
//          s.`商品管理番号（商品URL）`
//        , s.`項目選択肢別在庫用横軸選択肢`
//        , s.`項目選択肢別在庫用横軸選択肢子番号`
//        , s.`項目選択肢別在庫用縦軸選択肢`
//        , s.`項目選択肢別在庫用縦軸選択肢子番号`
//      FROM tb_shoplist_product_stock s
//      LEFT JOIN tb_productchoiceitems pci ON s.`商品管理番号（商品URL）` = pci.daihyo_syohin_code
//                                         AND s.`項目選択肢別在庫用横軸選択肢子番号` = pci.colcode
//                                         AND s.`項目選択肢別在庫用縦軸選択肢子番号` = pci.rowcode
//      LEFT JOIN tmp_shoplist_delete_code t ON s.`商品管理番号（商品URL）` = t.daihyo_syohin_code
//      WHERE pci.ne_syohin_syohin_code IS NULL
//        AND t.daihyo_syohin_code IS NULL
//EOD;
//    $stmt = $dbMain->query($sql);
//
//    $deleteSelectCount = $stmt->rowCount();
//    $logger->info('PPM 削除CSV出力（SKU） : ' . $deleteSelectCount);
//
//    if ($deleteSelectCount) {
//      $headers = $this->getCsvHeadersSelect();
//
//      $fs = new FileSystem();
//      $fileExists = $fs->exists($selectPath);
//      $fp = fopen($selectPath, 'ab');
//
//      if (!$fileExists) {
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//        $data = [
//            '項目選択肢用コントロールカラム'    => 'd'
//          , '商品管理番号（商品URL）'          => $row['商品管理番号（商品URL）']
//          , '選択肢タイプ'                    => 'i'
//          , '項目選択肢別在庫用横軸選択肢'      => $row['項目選択肢別在庫用横軸選択肢']
//          , '項目選択肢別在庫用横軸選択肢子番号' => $row['項目選択肢別在庫用横軸選択肢子番号']
//          , '項目選択肢別在庫用縦軸選択肢'      => $row['項目選択肢別在庫用縦軸選択肢']
//          , '項目選択肢別在庫用縦軸選択肢子番号' => $row['項目選択肢別在庫用縦軸選択肢子番号']
//        ];
//
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      fclose($fp);
//    }
//
//    // 在庫確認用テーブル 更新（削除）
//    $sql = <<<EOD
//      DELETE s
//      FROM tb_shoplist_product_stock s
//      INNER JOIN tmp_shoplist_delete_code t ON s.`商品管理番号（商品URL）`  = t.daihyo_syohin_code
//EOD;
//    $dbMain->query($sql);
//
//    $sql = <<<EOD
//      DELETE s
//      FROM tb_shoplist_product_stock s
//      LEFT JOIN tb_productchoiceitems pci ON s.`商品管理番号（商品URL）` = pci.daihyo_syohin_code
//                                         AND s.`項目選択肢別在庫用横軸選択肢子番号` = pci.colcode
//                                         AND s.`項目選択肢別在庫用縦軸選択肢子番号` = pci.rowcode
//      WHERE pci.ne_syohin_syohin_code IS NULL
//EOD;
//    $dbMain->query($sql);
//
//    $this->results['delete'] = [
//        'detail' => $deleteDetailCount
//      , 'select' => $deleteSelectCount
//    ];
//
//    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
//  }
//
//  /**
//   * 更新CSV出力
//   * 'n' と 'u' でフォーマットが別。
//   * → 画像カラムをつけるつけない、販売価格をつけるつけないなどで、統一は諦めて別ファイルに変更
//   *
//   * @param string $detailNewPath
//   * @param string $detailPath
//   * @param string $selectPath
//   * @throws \Doctrine\DBAL\DBALException
//   */
//  private function exportUpdateCsv($detailNewPath, $detailPath, $selectPath)
//  {
//    $logger = $this->getLogger();
//    $dbMain = $this->getDb('main');
//
//    /** @var StringUtil $stringUtil */
//    $stringUtil = $this->getContainer()->get('misc.util.string');
//
//    $logTitle = 'PPM CSV出力処理';
//    $subTitle = '更新CSV出力';
//    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));
//
//    // 登録・更新商品用一時テーブル
//    $dbMain->query("DROP TABLE IF EXISTS tmp_shoplist_update_code");
//    $sql = <<<EOD
//      CREATE TABLE tmp_shoplist_update_code (
//          daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
//        , control_column VARCHAR(1) NOT NULL DEFAULT 'n'
//        , hidden TINYINT NOT NULL DEFAULT 0
//      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
//EOD;
//    $dbMain->query($sql);
//
//    $dbMain->query("DROP TABLE IF EXISTS tmp_shoplist_update_sku");
//    $sql = <<<EOD
//      CREATE TEMPORARY TABLE tmp_shoplist_update_sku (
//          ne_syohin_syohin_code VARCHAR(50) NOT NULL PRIMARY KEY
//        , control_column VARCHAR(1) NOT NULL DEFAULT 'n'
//        , daihyo_syohin_code VARCHAR(30) NOT NULL
//        , colname VARCHAR(50) NOT NULL DEFAULT ''
//        , colcode VARCHAR(50) NOT NULL DEFAULT ''
//        , rowname VARCHAR(50) NOT NULL DEFAULT ''
//        , rowcode VARCHAR(50) NOT NULL DEFAULT ''
//        , `フリー在庫数` INTEGER NOT NULL DEFAULT 0
//      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
//EOD;
//    $dbMain->query($sql);
//
//    // 出力対象 全挿入
//    $sql = <<<EOD
//      INSERT INTO tmp_shoplist_update_code (
//          daihyo_syohin_code
//        , control_column
//        , hidden
//      )
//      SELECT
//          m.daihyo_syohin_code
//        , CASE
//            WHEN s.code IS NULL THEN 'n'
//            ELSE 'u'
//          END AS control_column
//        , 0 AS hidden
//      FROM tb_mainproducts m
//      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
//      INNER JOIN tb_shoplist_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
//      LEFT JOIN (
//        SELECT
//            s.`商品管理番号（商品URL）` AS code
//          , COUNT(*) AS num
//        FROM tb_shoplist_product_stock s
//        GROUP BY s.`商品管理番号（商品URL）`
//      ) s ON m.daihyo_syohin_code = s.code
//      WHERE i.registration_flg <> 0
//        AND cal.deliverycode <> :deliveryCodeTemporary
//        AND cal.adult_check_status IN ( :adultCheckStatusWhite , :adultCheckStatusGray )
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
//    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
//    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
//    $stmt->execute();
//
//    // 非表示対象 差分挿入
//    // ※出力対象ではなく、PPMに存在する商品で、倉庫設定になっていないもの
//    $sql = <<<EOD
//      INSERT INTO tmp_shoplist_update_code (
//          daihyo_syohin_code
//        , control_column
//        , hidden
//      )
//      SELECT
//          DISTINCT
//          s.`商品管理番号（商品URL）`
//        , 'u' AS control_column
//        , 1 AS hidden
//      FROM tb_shoplist_product_stock s
//      LEFT JOIN  tmp_shoplist_update_code t ON s.`商品管理番号（商品URL）` = t.daihyo_syohin_code
//      WHERE t.daihyo_syohin_code IS NULL
//        AND s.hidden = 0
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->execute();
//
//    // 差分がなく更新しない行を除去
//    $sql = <<<EOD
//      DELETE t
//      FROM tmp_shoplist_update_code t
//      INNER JOIN tb_mainproducts m ON t.daihyo_syohin_code = m.daihyo_syohin_code
//      INNER JOIN tb_mainproducts_cal cal ON t.daihyo_syohin_code = cal.daihyo_syohin_code
//      INNER JOIN tb_shoplist_information i ON t.daihyo_syohin_code = i.daihyo_syohin_code
//      LEFT JOIN (
//        SELECT
//            s.`商品管理番号（商品URL）` AS code
//          , COUNT(*) AS num
//        FROM tb_shoplist_product_stock s
//        GROUP BY s.`商品管理番号（商品URL）`
//      ) s ON t.daihyo_syohin_code = s.code
//      WHERE t.hidden = 0 /* 「倉庫設定: 1」のレコードは除外せず必ず更新（すでに差分挿入されている） */
//        AND (
//              /* 在庫ありのみ更新 */
//              cal.deliverycode NOT IN ( :deliveryCodeReady, :deliveryCodeReadyPartially ) /* 即納 or 一部即納 */
//              /* 新規 or 更新あり でなければ更新しない */
//           OR (s.code IS NOT NULL AND i.update_flg = 0)
//        )
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
//    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
//    $stmt->execute();
//
//    // CSV出力
//
//    // detail出力
//    $sql = <<<EOD
//      SELECT
//          t.control_column
//        , t.hidden  AS 倉庫指定
//        , m.daihyo_syohin_code
//        , d.`楽天ディレクトリID`
//        , SUBSTR(i.title, 1, 127) AS title /* 文字数制限 */
//        , i.baika_tanka
//        , m.`横軸項目名`
//        , m.`縦軸項目名`
//
//        , m.`商品コメントPC`
//        , m.サイズについて
//        , m.カラーについて
//        , m.素材について
//        , m.ブランドについて
//        , m.使用上の注意
//        , m.補足説明PC
//
//        , m.picnameP1
//        , m.picnameP2
//        , m.picnameP3
//        , m.picnameP4
//        , m.picnameP5
//        , m.picnameP6
//        , m.picnameP7
//        , m.picnameP8
//        , m.picnameP9
//        , m.picfolderP1
//        , m.picfolderP2
//        , m.picfolderP3
//        , m.picfolderP4
//        , m.picfolderP5
//        , m.picfolderP6
//        , m.picfolderP7
//        , m.picfolderP8
//        , m.picfolderP9
//
//        , CASE
//            WHEN d.rakutencategories_1 LIKE '%キッズ%' THEN 'plusnaokids'
//            ELSE 'plusnao'
//          END AS `ブランドコード`
//
//      FROM tb_mainproducts m
//      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
//      INNER JOIN tb_shoplist_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
//      INNER JOIN tb_plusnaoproductdirectory d ON m.`NEディレクトリID` = d.`NEディレクトリID`
//      INNER JOIN tmp_shoplist_update_code t ON m.daihyo_syohin_code = t.daihyo_syohin_code
//EOD;
//    $stmt = $dbMain->query($sql);
//
//    $detailCount = $stmt->rowCount();
//    $logger->info('PPM 更新CSV出力（詳細） : ' . $detailCount);
//
//    if ($detailCount) {
//
//      // 新規登録CSV (detail_new)
//      $headersNew = $this->getCsvHeadersDetailForNew();
//
//      $fs = new FileSystem();
//      $fileExists = $fs->exists($detailNewPath);
//      $fpNew = fopen($detailNewPath, 'ab');
//
//      if (!$fileExists) {
//        fputs($fpNew, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headersNew), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      // データ更新CSV
//      // 価格更新フラグによって、「販売価格」項目の有無が変更される
//      $headers = $this->getCsvHeadersDetail();
//
//      $fs = new FileSystem();
//      $fileExists = $fs->exists($detailPath);
//      $fp = fopen($detailPath, 'ab');
//
//      if (!$fileExists) {
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//
//        $data = [
//            'コントロールカラム'       => $row['control_column']
//          , '商品管理番号（商品URL）'  => strtolower($row['daihyo_syohin_code'])
//          , '商品番号'                => $row['daihyo_syohin_code']
//          , '全商品ディレクトリID'     => $row['楽天ディレクトリID']
//          , '商品名'                  => $row['title']
//          , '販売価格'                => $row['baika_tanka']
//          , '消費税'                  => 0
//          , '倉庫指定'                => $row['倉庫指定']
//          , 'モバイル用商品説明文'     => ''
//          // , '商品画像URL'             => ''
//          , '在庫タイプ'              => 2
//          , '項目選択肢別在庫用横軸項目名' => $row['横軸項目名']
//          , '項目選択肢別在庫用縦軸項目名' => $row['縦軸項目名']
//
//          , 'ブランドコード' => $row['ブランドコード']
//        ];
//
//        // 商品説明文 作成
//        $description = '';
//        $columns = [
//            '商品コメントPC'
//          , 'サイズについて'
//          , 'カラーについて'
//          , '素材について'
//          , 'ブランドについて'
//          , '使用上の注意'
//          , '補足説明PC'
//        ];
//        foreach($columns as $col) {
//          $row[$col] = str_replace("\n", '<br>', str_replace("\r", '', trim($row[$col])));
//        }
//        if (strlen($row['商品コメントPC'])) {
//          $description .= $row['商品コメントPC'] . '<br>';
//        }
//        if (strlen($row['サイズについて'])) {
//          $description .= '<br><br>【サイズについて】<br>';
//          $description .= $row['サイズについて'] . '<br>';
//        }
//        if (strlen($row['カラーについて'])) {
//          $description .= '<br><br>【カラーについて】<br>';
//          $description .= $row['カラーについて'] . '<br>';
//        }
//        if (strlen($row['素材について'])) {
//          $description .= '<br><br>【素材について】<br>';
//          $description .= $row['素材について'] . '<br>';
//        }
//        if (strlen($row['ブランドについて'])) {
//          $description .= '<br><br>【ブランド】<br>';
//          $description .= $row['ブランドについて'] . '<br>';
//        }
//        if (strlen($row['使用上の注意'])) {
//          $description .= '<br><br>【使用上の注意】<br>';
//          $description .= $row['使用上の注意'] . '<br>';
//        }
//        if (strlen($row['補足説明PC'])) {
//          $description .= '<br><br>【補足説明】<br>';
//          $description .= $row['補足説明PC'] . '<br>';
//        }
//
//        $data['モバイル用商品説明文'] = $description;
//
////        // 商品画像URL 作成
////        $images = [];
////        for ($i = 1; $i <= 9; $i++) {
////          $columnDir  = sprintf('picfolderP%d', $i);
////          $columnFile = sprintf('picnameP%d', $i);
////
////          // 画像設定 有無確認
////          if (strlen($row[$columnDir]) && strlen($row[$columnFile])) {
////            $images[] = sprintf(
////                          'http://img.shop-list.com/res/up/shoplist/shp/plusnao/%s/%d.jpg'
////                          , $data['商品管理番号（商品URL）']
////                          , $i
////                        );
////          } else{
////            $images[] = ''; // （暫定実装）もし画像がない番号があっても間を詰めない
////          }
////        }
////        $data['商品画像URL'] = implode(' ', $images);
//
//        // 出力
//
//        // 新規
//        if ($row['control_column'] == 'n') {
//          fputs($fpNew, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headersNew), 'SJIS-WIN', 'UTF-8') . "\r\n");
//        } else {
//          fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//        }
//      }
//
//      fclose($fpNew);
//      fclose($fp);
//    }
//
//    // select出力
//    // 在庫確認テーブルの更新のため、一時テーブルに全て投入してからSELECTする
//    /*
//    （対象）
//    ・product_detail の n or u に含まれ、在庫確認テーブルに存在しない => n
//    OR
//    ・在庫確認テーブルに代表商品コードが存在し、在庫確認テーブルに存在しない => n
//    OR
//    ・在庫確認テーブルと表示名が違う => u
//    */
//    $sql = <<<EOD
//      INSERT INTO tmp_shoplist_update_sku
//      SELECT
//          pci.ne_syohin_syohin_code
//        , CASE
//            WHEN (
//                    s.`商品管理番号（商品URL）` IS NOT NULL
//                AND (pci.colname <> s.`項目選択肢別在庫用横軸選択肢` OR pci.rowname <> s.`項目選択肢別在庫用横軸選択肢` )
//              ) THEN 'u'
//            ELSE 'n'
//          END AS control_column
//        , pci.daihyo_syohin_code
//        , pci.colname
//        , pci.colcode
//        , pci.rowname
//        , pci.rowcode
//        , pci.`フリー在庫数`
//      FROM tb_productchoiceitems pci
//      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
//      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
//      LEFT JOIN tb_shoplist_product_stock s ON pci.daihyo_syohin_code = s.`商品管理番号（商品URL）`
//                                           AND pci.colcode = s.`項目選択肢別在庫用横軸選択肢子番号`
//                                           AND pci.rowcode = s.`項目選択肢別在庫用縦軸選択肢子番号`
//      LEFT JOIN (
//        SELECT
//          DISTINCT s.`商品管理番号（商品URL）` AS code
//        FROM tb_shoplist_product_stock s
//      ) SCODE ON m.daihyo_syohin_code = SCODE.code
//      LEFT JOIN tmp_shoplist_update_code t ON pci.daihyo_syohin_code = t.daihyo_syohin_code
//      WHERE
//            ( t.daihyo_syohin_code IS NOT NULL AND s.`商品管理番号（商品URL）` IS NULL )
//         OR ( SCODE.code IS NOT NULL AND s.`商品管理番号（商品URL）` IS NULL )
//         OR (
//               s.`商品管理番号（商品URL）` IS NOT NULL
//           AND (pci.colname <> s.`項目選択肢別在庫用横軸選択肢` OR pci.rowname <> s.`項目選択肢別在庫用縦軸選択肢` )
//         )
//      ORDER BY pci.ne_syohin_syohin_code
//EOD;
//    $dbMain->query($sql);
//
//    // CSV出力(SKU)
//    $sql = <<<EOD
//      SELECT * FROM tmp_shoplist_update_sku
//      ORDER BY ne_syohin_syohin_code
//EOD;
//    $stmt = $dbMain->query($sql);
//
//    $selectCount = $stmt->rowCount();
//    $logger->info('PPM 更新CSV出力（SKU） : ' . $selectCount);
//
//    if ($selectCount) {
//      $headers = $this->getCsvHeadersSelect();
//
//      $fs = new FileSystem();
//      $fileExists = $fs->exists($selectPath);
//      $fp = fopen($selectPath, 'ab');
//
//      if (!$fileExists) {
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//        $data = [
//            '項目選択肢用コントロールカラム'    => $row['control_column']
//          , '商品管理番号（商品URL）'          => strtolower($row['daihyo_syohin_code'])
//          , '選択肢タイプ'                    => 'i'
//          , '項目選択肢別在庫用横軸選択肢'      => $row['colname']
//          , '項目選択肢別在庫用横軸選択肢子番号' => $row['colcode']
//          , '項目選択肢別在庫用縦軸選択肢'      => $row['rowname']
//          , '項目選択肢別在庫用縦軸選択肢子番号' => $row['rowcode']
//          , '項目選択肢別在庫用在庫数'          => $row['フリー在庫数']
//        ];
//
//        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
//      }
//
//      fclose($fp);
//    }
//
//    // 在庫確認テーブル更新
//    // あくまでSKU情報の更新のみで、在庫情報は更新してはいけない
//    // このproduct_select.csv では在庫数の更新はされず、この後いつか行われるstock.csvで更新しなければならず（PPM仕様）
//    // そこまで在庫数の差分は維持する必要がある。
//    // 新規SKUは在庫数を更新してもどちらでもよいはずだが、簡単のためやはり0で更新する
//    $sql = <<<EOD
//      INSERT INTO tb_shoplist_product_stock (
//          `商品管理番号（商品URL）`
//        , `選択肢タイプ`
//        , `項目選択肢別在庫用横軸選択肢`
//        , `項目選択肢別在庫用横軸選択肢子番号`
//        , `項目選択肢別在庫用縦軸選択肢`
//        , `項目選択肢別在庫用縦軸選択肢子番号`
//      )
//      SELECT
//          t.daihyo_syohin_code
//        , 'i'
//        , t.colname
//        , t.colcode
//        , t.rowname
//        , t.rowcode
//      FROM tmp_shoplist_update_sku t
//      ON DUPLICATE KEY UPDATE
//          `項目選択肢別在庫用横軸選択肢` = VALUES(`項目選択肢別在庫用横軸選択肢`)
//        , `項目選択肢別在庫用縦軸選択肢` = VALUES(`項目選択肢別在庫用縦軸選択肢`)
//EOD;
//    $dbMain->query($sql);
//
//    // 倉庫設定 更新
//    $sql = <<<EOD
//      UPDATE
//      tb_shoplist_product_stock s
//      INNER JOIN tmp_shoplist_update_code t ON s.`商品管理番号（商品URL）` = t.daihyo_syohin_code
//      SET s.hidden = t.hidden
//      WHERE s.hidden <> t.hidden
//EOD;
//    $dbMain->query($sql);
//
//    // 新規商品について、最終画像アップロード日時をNULLに更新
//    // （同一商品の削除後の復活はイレギュラーなので本来不要ではあるが、わかりやすさのために実施）
//    $sql = <<<EOD
//      UPDATE tb_shoplist_information i
//      INNER JOIN tmp_shoplist_update_code t ON i.daihyo_syohin_code = t.daihyo_syohin_code
//      SET i.last_image_upload_datetime = NULL
//      WHERE t.control_column = 'n'
//EOD;
//    $dbMain->query($sql);
//
//    // 更新フラグ OFF
//    $dbMain->query("UPDATE tb_shoplist_information SET update_flg = 0");
//
//    $this->results['update'] = [
//        'detail' => $detailCount
//      , 'select' => $selectCount
//    ];
//
//    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
//  }
//
//  /**
//   * CSVヘッダ取得（詳細：NEW）
//   */
//  private function getCsvHeadersDetailForNew()
//  {
//    $headers = array_unique(array_merge($this->getCsvHeadersDetail(), [
//        'コントロールカラム'
//      , '商品管理番号（商品URL）'
//      , '商品番号'
//      , '全商品ディレクトリID'
//      , 'PC用キャッチコピー'        // for new
//      , 'モバイル用キャッチコピー'  // for new
//      , '商品名'
//      , '販売価格' // for new
//      , '表示価格' // for new
//      , '消費税'
//      , '倉庫指定'
//      , 'PC用商品説明文'        // for new
//      , 'モバイル用商品説明文'
//      , '商品画像URL'           // for new
//      , '商品画像名（ALT）'     // for new
//      , '販売期間指定'          // for new
//      , '在庫タイプ'
//      , '在庫数'               // for new
//      , '在庫数表示'           // for new
//      , '項目選択肢別在庫用横軸項目名'
//      , '項目選択肢別在庫用縦軸項目名'
//      , '項目選択肢別在庫用残り表示閾値' // for new
//      , '予約商品発売日'               // for new
//      , 'ブランドコード' // for new
//    ]));
//
//    return $headers;
//  }
//
//  /**
//   * CSVヘッダ取得（詳細）
//   */
//  private function getCsvHeadersDetail()
//  {
//    $headers = [
//        'コントロールカラム'
//      , '商品管理番号（商品URL）'
//      , '商品番号'
//      , '全商品ディレクトリID'
//      // , 'PC用キャッチコピー'
//      // , 'モバイル用キャッチコピー'
//      , '商品名'
//      , '消費税'
//      , '倉庫指定'
//      , 'モバイル用商品説明文'
//      // , '商品画像URL'
//      , '在庫タイプ'
//      , '項目選択肢別在庫用横軸項目名'
//      , '項目選択肢別在庫用縦軸項目名'
//    ];
//
//    return $headers;
//  }
//
//  /**
//   * CSVヘッダ取得（項目選択肢）
//   */
//  private function getCsvHeadersSelect()
//  {
//    $headers = [
//        '項目選択肢用コントロールカラム'
//      , '商品管理番号（商品URL）'
//      , '選択肢タイプ'
//      , '項目選択肢別在庫用横軸選択肢'
//      , '項目選択肢別在庫用横軸選択肢子番号'
//      , '項目選択肢別在庫用縦軸選択肢'
//      , '項目選択肢別在庫用縦軸選択肢子番号'
//      , '項目選択肢別在庫用取り寄せ可能表示'
//      , '項目選択肢別在庫用在庫数'
//    ];
//
//    return $headers;
//  }


  /**
   * 商品CSVデータ作成： キャッチコピー
   * 元の PPM_キャッチコピー再構築() の引数 lc_catchcopy はFALSE固定のため省略
   * （ true: BuildPpmTitleVariationAll, false: BuildPpmTitleVariationAllFirst ）
   */
  private function createItemCatchCopy()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品情報の設定';
    $logSubTitle = 'キャッチコピー___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '開始'));
    $logger->info('PPMタイトルを補正しています');

    $dbMain->query('TRUNCATE tb_title_parts_target');

    // '在庫更新したものが対象
    $sql = <<<EOD
      INSERT INTO tb_title_parts_target (
        daihyo_syohin_code
      )
      SELECT
          m.daihyo_syohin_code
      FROM tb_mainproducts AS m
      INNER JOIN tb_ppm_itemlist_add AS l ON m.daihyo_syohin_code = l.商品番号
      INNER JOIN tb_plusnaoproductdirectory ON m.NEディレクトリID = tb_plusnaoproductdirectory.NEディレクトリID
      WHERE l.NE更新カラム IN ('n', 'v', 'u')
      GROUP BY m.daihyo_syohin_code
EOD;
    $dbMain->query($sql);

    // 'タイトル用のディレクトリを組み立てる
    // ※ ↑で対象を絞っているのにここでは全件更新するのは、BuildPpmTitleVariationAllFirst を利用するかの分岐があったためか。
    $sql = <<<EOD
      UPDATE  tb_title_parts tp
      INNER JOIN tb_mainproducts AS m ON tp.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory d ON m.NEディレクトリID = d.NEディレクトリID
      SET tp.directory = CONCAT(
            CASE
              WHEN IFNULL(フィールド1, '') = '' THEN ''
              ELSE CONCAT('', フィールド1)
            END
          , CASE
              WHEN IFNULL(フィールド2, '') = '' THEN ''
              ELSE CONCAT(' ', フィールド2)
            END
          , CASE
              WHEN IFNULL(フィールド3, '') = '' THEN ''
              ELSE CONCAT(' ', フィールド3)
            END
          , CASE
              WHEN IFNULL(フィールド4, '') = '' THEN ''
              ELSE CONCAT(' ', フィールド4)
            END
          , CASE
              WHEN IFNULL(フィールド5, '') = '' THEN ''
              ELSE CONCAT(' ', フィールド5)
            END
          , CASE
              WHEN IFNULL(フィールド6, '') = '' THEN ''
              ELSE CONCAT(' ', フィールド6)
            END
        )
EOD;
    $dbMain->query($sql);

    // 'ディレクトリを項目制限長に合わせて切り詰める
    $sql = <<<EOD
      UPDATE tb_title_parts
      SET directory_ex = ''
        , directory_ex2 = ''
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      UPDATE tb_title_parts
      SET directory_ex =
            CASE
              WHEN CHAR_LENGTH(CONCAT(front_title, back_title)) > 87  THEN ''
              ELSE LEFT( directory, 87 - (CHAR_LENGTH(CONCAT(front_title, back_title))))
            END
        , directory_ex2 = LEFT(directory, 87)
      WHERE directory <> ''
EOD;
    $dbMain->query($sql);

    // 'タイトル用のバリエーションを組み立てる
    $dbMain->query("CALL BuildPpmTitleVariationAllFirst()");

    // 'バリエーションを項目制限長に合わせて切り詰める
    $sql = <<<EOD
      UPDATE tb_ppm_information
      SET variation_ex = ''
        , variation_ex2 = ''
EOD;
    $dbMain->query($sql);


    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_title_parts AS tp ON i.daihyo_syohin_code = tp.daihyo_syohin_code
      SET i.variation_ex =
            CASE
              WHEN CHAR_LENGTH(CONCAT(tp.front_title, tp.back_title, tp.directory_ex)) > 87 THEN ''
              ELSE LEFT(i.variation, 87 - (CHAR_LENGTH(CONCAT(tp.front_title, tp.back_title, tp.directory_ex))))
            END
        , i.variation_ex2 =
            CASE
              WHEN CHAR_LENGTH(tp.directory_ex2) > 87 THEN ''
              ELSE LEFT(i.variation, 87 - (CHAR_LENGTH(tp.directory_ex2)))
            END
      WHERE i.variation <> ''
EOD;
    $dbMain->query($sql);

    // 'キャッチコピー
    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_title_parts AS tp ON i.daihyo_syohin_code = tp.daihyo_syohin_code
      SET i.キャッチコピー =
        LEFT(
          CONCAT(
              tp.directory_ex2
            , ' '
            , i.variation_ex2
            , i.ppm_title
          )
          , 87
        )
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '終了'));
  }

  /**
   * 商品CSVデータ作成： 商品説明1とスマートフォン用
   */
  private function createItemDescription()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品情報の設定';
    $logSubTitle = '商品説明文を再作成';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '開始'));
    $logger->info('商品説明文を再作成しています');

    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      SET i.商品説明文_共通 = ''
EOD;
    $dbMain->query($sql);

    // 商品説明文_共通 の先頭は画像 1～9
    $sql = <<<EOD
      UPDATE tb_ppm_information      AS i
      INNER JOIN tb_mainproducts     AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET
        i.`商品説明文_共通` = CONCAT(
            CASE
              WHEN COALESCE(m.商品画像P1Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P1Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P2Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P2Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P3Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P3Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P4Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P4Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P5Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P5Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P6Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P6Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P7Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P7Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P8Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P8Adress
                )
            END
          , CASE
              WHEN COALESCE(m.商品画像P9Adress, '') = '' THEN ''
              ELSE
                REPLACE(
                  '<a href="__ADDRESS__" target="_blank"><img width =710 align=baseline border=0 hspace=0 src="__ADDRESS__" alt=""></a><br>'
                  , '__ADDRESS__'
                  , m.商品画像P9Adress
                )
            END

          , i.`商品説明文_共通`
        )
      WHERE i.registration_flg <> 0
EOD;
    $dbMain->query($sql);

    // 2016/12/01 しばらく即納のみで行くため、納期情報除去
//    // 発送時期について
//    $sql = <<<EOD
//      UPDATE tb_ppm_information AS i
//      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
//      SET i.商品説明文_共通 = CONCAT(i.商品説明文_共通, '<br><br>【発送時期について】<br>')
//      WHERE i.registration_flg <> 0
//EOD;
//    $dbMain->query($sql);
//
//    // -- 一部即納 即納可能バリエ
//    $sql = <<<EOD
//      UPDATE tb_ppm_information      AS i
//      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
//      INNER JOIN tb_title_parts      AS tp  ON i.daihyo_syohin_code = tp.daihyo_syohin_code
//      SET i.商品説明文_共通 = CONCAT(
//              i.商品説明文_共通
//            , '即納可能バリエ'
//            , cal.list_some_instant_delivery
//            , '<br>その他は'
//            , tp.back_title
//        )
//      WHERE i.registration_flg <> 0
//        AND cal.deliverycode = :deliveryCodeReadyPartially
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
//    $stmt->execute();
//
//    // -- 即納
//    $sql = <<<EOD
//      UPDATE tb_ppm_information      AS i
//      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
//      SET i.商品説明文_共通 = CONCAT(i.商品説明文_共通, '<br>こちらの商品は即納です。')
//      WHERE i.registration_flg <> 0
//        AND cal.deliverycode = :deliveryCodeReady
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
//    $stmt->execute();
//
//    // -- 受発注のみ
//    $sql = <<<EOD
//      UPDATE tb_ppm_information      AS i
//      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
//      INNER JOIN tb_title_parts      AS tp  ON i.daihyo_syohin_code = tp.daihyo_syohin_code
//      SET i.商品説明文_共通 = CONCAT(i.商品説明文_共通, tp.back_title)
//      WHERE i.registration_flg <> 0
//        AND cal.deliverycode = :deliveryCodePurchaseOnOrder
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
//    $stmt->execute();

    // '画像URLの補正
    $start = microtime(true);
    $logger->info(sprintf('PPM 画像URL補正 start : %.4f', $start));
    // ※画像URLを作成するときにやっても良さそうだが、なぜここでやる？（激しく重い可能性）
    //   list_some_instant_delivery やら title_parts に含まれる可能性があるのか？（ないはず）
    //   ひとまずは元実装ママとする
    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      SET i.`商品説明文_共通` = REPLACE (
            `商品説明文_共通`
          , 'https://image.rakuten.co.jp/plusnao/cabinet/'
          , 'https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'
        )
EOD;
    $dbMain->query($sql);

    $end = microtime(true);
    $past = $end - $start;
    $logger->info(sprintf('PPM 画像URL補正 end: %.4f / past : %.4f (sec)', $end, $past));


    // '商品説明１←商品説明文_共通
    // '<画面で入力されている場合はそれを優先する> → なぜか入っていた仮登録判定および販売終了日判定は特に意味がなさそうなので除去。
    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      SET i.`商品説明1`              = CASE
                                        WHEN IFNULL(i.input_商品説明1, '') <> '' THEN i.input_商品説明1
                                        ELSE i.`商品説明文_共通`
                                      END
        , i.`商品説明スマートフォン用` = CASE
                                        WHEN IFNULL(i.input_商品説明スマートフォン用, '') <> '' THEN i.input_商品説明スマートフォン用
                                        ELSE i.`商品説明文_共通`
                                      END
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '終了'));
  }

  /**
   * 商品CSVデータ作成： 商品説明2
   */
  private function createItemDescription2()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品情報の設定';
    $logSubTitle = '商品説明2を作成';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '開始'));
    $logger->info('商品説明2を作成しています');

    $dbMain->query("UPDATE tb_ppm_information AS i SET i.`商品説明2` = ''");

    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      SET i.商品説明2 =
        CASE
          WHEN i.input_商品説明2 <> '' THEN i.input_商品説明2 /* 画面で入力されている場合はそれを優先する */
          ELSE
            CONCAT(
                i.商品説明2

              /* 商品コメントPC */
              , CASE
                  WHEN m.商品コメントPC IS NOT NULL THEN REPLACE(REPLACE(m.商品コメントPC, CHAR(13), ''), CHAR(10), '<br>')
                  ELSE ''
                END
              /* サイズについて */
              , CASE
                  WHEN m.サイズについて IS NOT NULL THEN CONCAT('<br><br>【サイズについて】<br>', REPLACE(REPLACE(m.サイズについて, CHAR(13), ''), CHAR(10), '<br>'))
                  ELSE ''
                END
              /* カラーについて */
              , CASE
                  WHEN m.カラーについて IS NOT NULL THEN CONCAT('<br><br>【カラーについて】<br>', REPLACE(REPLACE(m.カラーについて, CHAR(13), ''), CHAR(10), '<br>'))
                  ELSE ''
                END
              /* 素材について */
              , CASE
                  WHEN m.素材について IS NOT NULL THEN CONCAT('<br><br>【素材について】<br>', REPLACE(REPLACE(m.素材について, CHAR(13), ''), CHAR(10), '<br>'))
                  ELSE ''
                END
              /* ブランドについて */
              , CASE
                  WHEN m.ブランドについて IS NOT NULL THEN CONCAT('<br><br>【ブランド】<br>', REPLACE(REPLACE(m.ブランドについて, CHAR(13), ''), CHAR(10), '<br>'))
                  ELSE ''
                END
              /* 補足説明 */
              , CASE
                  WHEN m.補足説明PC IS NOT NULL THEN CONCAT('<br><br>【補足説明】<br>', REPLACE(REPLACE(m.補足説明PC, CHAR(13), ''), CHAR(10), '<br>'))
                  ELSE ''
                END
            )
        END

      WHERE i.registration_flg <> 0
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '終了'));
  }

  /**
   * 商品CSVデータ作成： 商品説明テキストのみ
   */
  private function createItemDescriptionText()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '商品情報の設定';
    $logSubTitle = '商品説明テキストのみを作成';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '開始'));
    $logger->info('商品説明テキストのみを作成しています');

    $dbMain->query("UPDATE tb_ppm_information AS i SET i.`商品説明テキストのみ` = ''");

    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`商品説明テキストのみ` = REPLACE(
            '<center><br>Plus Naoスペシャルプライス<br><FONT COLOR=""#FF0000"">◆____PRICE____yen◆</FONT><br></center>'
          , '____PRICE____'
          , CAST(
              TRUNCATE (
                (
                  IF (i.baika_tanka > 0, i.baika_tanka, cal.baika_tnk) * CAST(:taxRate AS DECIMAL)
                )
                , 0
              )
              AS CHAR
            )
        )
      WHERE i.registration_flg <> 0
        AND cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      SET i.`商品説明テキストのみ` = CONCAT(
                i.`商品説明テキストのみ`
              , '補足説明<br>'
              , m.必要補足説明
            )
      WHERE i.`商品説明テキストのみ` <> ''
        AND m.必要補足説明 IS NOT NULL
EOD;
    $dbMain->query($sql);

    // 2016/12/01 しばらく即納のみで行くため、納期情報除去
//    // 一部即納
//    $sql = <<<EOD
//      UPDATE tb_mainproducts_cal AS cal
//      INNER JOIN tb_ppm_information AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
//      INNER JOIN tb_title_parts AS tp ON cal.daihyo_syohin_code = tp.daihyo_syohin_code
//      SET i.`商品説明テキストのみ` = CONCAT(
//            i.`商品説明テキストのみ`
//          , '<br><br>即納可能バリエ'
//          , cal.list_some_instant_delivery
//          , '<br>その他は、'
//          , tp.back_title
//        )
//      WHERE cal.deliverycode = :deliveryCodeReadyPartially
//        AND cal.list_some_instant_delivery IS NOT NULL
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
//    $stmt->execute();
//
//    // 受発注のみ
//    $sql = <<<EOD
//      UPDATE tb_mainproducts_cal AS cal
//      INNER JOIN tb_ppm_information AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
//      INNER JOIN tb_title_parts AS tp ON cal.daihyo_syohin_code = tp.daihyo_syohin_code
//      SET i.`商品説明テキストのみ` = CONCAT(
//                i.`商品説明テキストのみ`
//              , '<br><br>'
//              , tp.back_title
//            )
//      WHERE cal.deliverycode = :deliveryCodePurchaseOnOrder
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
//    $stmt->execute();
//
//    // 即納
//    $sql = <<<EOD
//      UPDATE tb_mainproducts_cal AS cal
//      INNER JOIN tb_ppm_information AS i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
//      SET i.`商品説明テキストのみ` = CONCAT(
//                i.`商品説明テキストのみ`
//              , '<br><br>こちらの商品は即納です'
//            )
//      WHERE cal.deliverycode = :deliveryCodeReady
//EOD;
//    $stmt = $dbMain->prepare($sql);
//    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
//    $stmt->execute();

    // '画面で入力されている場合はそれを優先する
    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`商品説明テキストのみ` = `input_商品説明テキストのみ`
      WHERE i.input_商品説明テキストのみ <> ''
EOD;
    $dbMain->query($sql);

  }

  /**
   * 商品CSVデータ作成： 商品画像URL設定
   */
  private function createItemImageUrl()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = '商品情報の設定';
    $logSubTitle = '商品画像URLを再作成';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '開始'));
    $logger->info('商品画像URLを再作成しています');

    $sql = <<<EOD
      UPDATE tb_ppm_information AS i
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      SET
        商品画像URL = CONCAT(
            CASE
              WHEN (COALESCE(m.商品画像P1Adress, '') = '') THEN ''
              ELSE REPLACE(m.商品画像P1Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/')
            END
          , CASE
              WHEN (COALESCE(m.商品画像P2Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P2Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P3Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P3Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P4Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P4Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P5Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P5Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P6Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P6Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P7Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P7Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P8Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P8Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
          , CASE
              WHEN (COALESCE(m.商品画像P9Adress, '') = '') THEN ''
              ELSE CONCAT(',', REPLACE(m.商品画像P9Adress,'https://image.rakuten.co.jp/plusnao/cabinet/','https://img.ponparemall.net/imgmgr/21/00105821/itemroot/'))
            END
        )
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $logSubTitle, '終了'));
  }



  /// インポートCSV項目 item.csv
  private $importCsvFieldsItem = [
      'コントロールカラム'
    , '商品管理ID（商品URL）'
    , '販売ステータス'
    , '商品ID'
    , '商品名'
    , 'キャッチコピー'
    , '販売価格'
    , '表示価格'
    , '消費税'
    , '送料'
    , '独自送料グループ(1)'
    , '独自送料グループ(2)'
    , '個別送料'
    , '代引料'
    , 'のし対応'
    , '注文ボタン'
    , '商品問い合わせボタン'
    , '販売期間指定'
    , '注文受付数'
    , '在庫タイプ'
    , '在庫数'
    , '在庫表示'
    , '商品説明(1)'
    , '商品説明(2)'
    , '商品説明(テキストのみ)'
    , '商品画像URL'
    , 'モールジャンルID'
    , 'シークレットセールパスワード'
    , 'ポイント率'
    , 'ポイント率適用期間'
    , 'SKU横軸項目名'
    , 'SKU縦軸項目名'
    , 'SKU在庫用残り表示閾値'
    , '商品説明(スマートフォン用)'
    , 'JANコード'
    , 'ヘッダー・フッター・サイドバー'
    , 'お知らせ枠'
    , '自由告知枠'
    , '再入荷リクエストボタン'
    , '二重価格文言タイプ'
  ];

  /// インポートCSV項目 option.csv
  private $importCsvFieldsOption = [
      'コントロールカラム'
    , '商品管理ID（商品URL）'
    , '選択肢タイプ'
    , '購入オプション名'
    , 'オプション項目名'
    , 'SKU横軸項目ID'
    , 'SKU横軸項目名'
    , 'SKU縦軸項目ID'
    , 'SKU縦軸項目名'
    , 'SKU在庫数'
  ];

  /// インポートCSV項目 item.csv
  private $importCsvFieldsCategory = [
      'コントロールカラム'
    , '商品管理ID（商品URL）'
    , '商品名'
    , 'ショップ内カテゴリ'
    , '表示順位'
  ];

}
