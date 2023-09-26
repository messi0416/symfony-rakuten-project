<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateAccessCountCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  // 商品分析データ最大ダウンロード件数。2022/09/13 20万件に変更
  const MAX_DL_COUNT = 200000;
  // 最大DLリトライ回数
  const MAX_DL_RETRY_TIMES = 10;

  /** 対象店舗文字列：楽天 */
  const TARGET_SHOP_RAKUTEN = 'rakuten';
  /** 対象店舗文字列：motto-motto */
  const TARGET_SHOP_MOTTO = 'motto';
  /** 対象店舗文字列：LaForest */
  const TARGET_SHOP_LAFOREST = 'laforest';
  /** 対象店舗文字列：dolcissimo */
  const TARGET_SHOP_DOLCISSIMO = 'dolcissimo';
  /** 対象店舗文字列：gekipla */
  const TARGET_SHOP_GEKIPLA = 'gekipla';

  const SHOP_LIST = [
    self::TARGET_SHOP_RAKUTEN,
    self::TARGET_SHOP_MOTTO,
    self::TARGET_SHOP_LAFOREST,
    self::TARGET_SHOP_DOLCISSIMO,
    self::TARGET_SHOP_GEKIPLA
  ];

  /** @var  SymfonyUsers */
  private $account;

  // 店舗名
  private $targetShopName;
  // モールID(ne_mall_id)
  private $neMallId;
  // 登録日処理ID
  private $updateRecordNumber;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-access-count')
      ->setDescription('login to RMS(rakuten) Web site and download access count CSV file, and update DB.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('downloaded-data-dir', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みディレクトリ指定（ダウンロードをskip）')
      ->addOption('target-shop-name', null, InputOption::VALUE_OPTIONAL, '店舗名指定（rakuten/motto/laforest/dolcissimo/gekipla）', 'rakuten')
      ->addOption('from-date', null, InputOption::VALUE_OPTIONAL, '集計対象日（開始） YYYY-mm-dd ※指定がなければ前日か登録済み日時の翌日の過去の方')
      ->addOption('to-date', null, InputOption::VALUE_OPTIONAL, '集計対象日（終了） YYYY-mm-dd ※指定がなければ前日')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logger->info('楽天アクセス数更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // 店舗に関係する情報取得
    try{
      // 店舗名チェック
      $this->validate($input);
      $this->targetShopName = $input->getOption('target-shop-name');
      switch ($this->targetShopName) {
        case self::TARGET_SHOP_RAKUTEN:
          $this->neMallId           = TbShoppingMall::NE_MALL_ID_RAKUTEN;
          $this->updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN;
          break;
        case self::TARGET_SHOP_MOTTO:
          $this->neMallId           = TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
          $this->updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_MOTTO;
          break;
        case self::TARGET_SHOP_LAFOREST:
          $this->neMallId           = TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
          $this->updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_LAFOREST;
          break;
        case self::TARGET_SHOP_DOLCISSIMO:
          $this->neMallId           = TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
          $this->updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_DOLTI;
          break;
        case self::TARGET_SHOP_GEKIPLA:
          $this->neMallId           = TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
          $this->updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_GEKIPLA;
          break;
      }
    } catch (ValidationException $e) {
      $logger->info('楽天アクセス数更新処理でパラメーターエラーが発生しました。' . $e->getMessage());
      $logger->logTimerFlush();
      return 1;
    }

    // DB記録＆通知処理
    $logExecTitle = '楽天アクセス数更新処理(' . $this->targetShopName . ')';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      $saveDir = null;

      // ディレクトリ指定があればダウンロードはスキップ
      $downloadedDir = $input->getOption('downloaded-data-dir');
      if (!$downloadedDir) {

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

        // RMS ログイン
        $client = $webAccessUtil->getWebClient();
        $webAccessUtil->rmsLogin($client, 'api', $this->targetShopName); // 必要なら、アカウント名を追加して切り替える

        // アクセス分析画面へ移動
        $nextUrl = 'https://datatool.rms.rakuten.co.jp/access/item/';
        $client->request('get', $nextUrl);

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        // rdatatool の認証に失敗していれば、ここでエラー画面にリダイレクトされる。(2015/10/27時点の挙動)
        if ($status !== 200 || $uri != $nextUrl) {
          throw new RuntimeException('move to rdatatool page error!! [' . $status . '][' . $uri . ']');
        }

        // 更新日情報取得用（CommandBaseTraitトレイト）
        $this->commonUtil = $this->getDbCommonUtil();
        // getUpdateRecordLastUpdatedDateTimeの戻り値はDateTimeクラスである。ミュータブルクラスのため変換する。
        $lastUpdatedDateTime = $this->commonUtil->getUpdateRecordLastUpdatedDateTime($this->updateRecordNumber);
        $lastUpdated = \DateTimeImmutable::createFromMutable($lastUpdatedDateTime);
        // from-date
        $fromDateStr = $input->getOption('from-date');
        // to-date
        $toDateStr = $input->getOption('to-date');
        // 前日
        // 日付の完全一致比較（==/!=）における基準時刻は0時0分0秒で統一する
        $yesterday = (new \DateTimeImmutable())->setTime(0, 0, 0)->modify('-1 day');

        // リトライ開始日
        $fromDate =  '';
        if ($fromDateStr) {
          $fromDate = new \DateTimeImmutable($fromDateStr);
        } else {
          // 指定が無ければ、実行日の前日か、登録済み日時の翌日の過去の方
          $fromDate = $yesterday; // 実行日の前日
          if ($lastUpdated) {
            $lastUpdateNextDay = $lastUpdated->setTime(0, 0, 0)->modify('+1 day'); // 登録済み日時の翌日(DBの基準時刻は23:59:59のため再設定)
            if ($lastUpdateNextDay < $fromDate) {
              $fromDate = $lastUpdateNextDay;
            }
          }
        }
        $fromDate = $fromDate->setTime(0, 0, 0);

        // リトライ終了日
        $toDate = '';
        if ($toDateStr) {
          $toDate = new \DateTimeImmutable($toDateStr); // to-dateの指定がある場合
        } else {
          $toDate = $yesterday;
        }
        $toDate = $toDate->setTime(23, 59, 59);  // whileの終了条件に使うため確実にwhileが終わるように最終時刻とする

        // fromDate-toDateの間1日ずつ繰り返す
        $targetDate = $fromDate;
        try{
          while ($targetDate <= $toDate) {
            // CSV保存ディレクトリ作成(data/access_count/Ymd_店舗名)
            $saveDir = $this->makeSaveDir();
            // csvDownloadで例外が発生するとCSVの取り込み処理が実行されず登録済み日時も更新されない
            $this->csvDownload($client, $targetDate, $saveDir);
            // CSV取り込み
            $info = $this->importCsvData($saveDir);
            // tb_updaterecord更新処理
            $this->updateRecord($targetDate);
            // 取得日を一日進める
            $targetDate = $targetDate->modify('+1 day');
          }
        } catch (\RuntimeException $e) {
          // 例外が発生した日が前日の場合は例外を握りつぶす（前日以外は再throw）
          // DateTimeImmutableクラスの日付一致比較するときは ==/!= を使う
          if($targetDate != $yesterday){ // 基準時刻は0時0分0秒で日付不一致比較
            throw new RuntimeException('楽天アクセスカウント数の処理に失敗しました。' . '[エラー：' . $targetDate->format('Ymd') . '] '. $e->getMessage());
          }
        }

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了'));
      }else{
        // ディレクトリ指定(downloaded-data-dir)があればダウンロードはスキップしてCSV取り込みからスタート
        $saveDir = $downloadedDir;

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));

        // CSV取り込み
        $info = $this->importCsvData($saveDir);

        // ダウンロードをSkipしたときはCSV保存ディレクトリの日付のよりも前にエラーなく取り込めていることを確認できないため
        // tb_updaterecordの更新処理は行わない

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));
      }

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "楽天アクセスカウント数CSV取込処理でエラーが発生しました。", 'error'
      );
      $logger->logTimerFlush();

      return 1;
    }
  }

  /**
   * CSVダウンロード処理
   * @param \MiscBundle\Util\GoutteClientCustom $client Webクライアント
   * @param \DateTimeImmutable $targetDate 処理日
   * @param string $saveDir CSV保存ディレクトリ
   */
  private function csvDownload($client, $targetDate, $saveDir)
  {
    $logger = $this->getLogger();

    // 各種類 取得
    $devices = [
      'pc' => 'PC',
      'sdApp' => '楽天市場アプリ',
      'sdWeb' => 'スマートフォン',
    ];

    // ダウンロード処理本体
    $targetDateYmd = $targetDate->format('Ymd');

    // JSONダウンロード 実行（リトライ処理含む）
    $json = $this->getJsonData($client, $targetDate);

    // DLしたJSONファイルから PC/アプリ/スマホ の３種類のCSVファイルを構築する
    foreach($devices as $deviceKey => $device) {

      // ファイル名
      $fileName = "{$targetDateYmd}_{$this->targetShopName}_{$device}.csv";
      $path = $saveDir . '/' . $fileName;

      // CSVファイルオープン
      $fp = new \SplFileObject($path, 'w'); // 上書き
      // CSVのヘッダ出力（従来のヘッダと全く同じにする必要はないが、２、３、５行目は取り込み処理に使うため合わせておく）
      // LOAD DATA LOCAL INFILE の改行コード \n(LF) に合わせる。fputcsvはデフォルトで LF 出力。
      $fp->fwrite("※この情報は店舗様および楽天市場での重要な情報となります。データの取扱には十分にご注意ください。\n");
      $fp->fwrite("商品分析\n"); // validateCsv関数で使用
      $fp->fputcsv(['表示期間', $targetDate->format('Y年m月d日からY年m月d日')]);
      $fp->fwrite("キーワード\n");
      $fp->fputcsv(['端末', $device]);
      $fp->fputcsv(['#','ジャンル','カタログID','商品ID','商品名','商品管理番号','商品番号','アクセス人数', 'ユニークユーザー数']);
      // JSON -> CSV変換
      $bytes = 0;
      $num = 0;
      $visitKey = ['PC' => 'visitPc', '楽天市場アプリ' => 'visitSdApp', 'スマートフォン' => 'visitSdWeb'];
      $uuKey    = ['PC' => 'uuPc'   , '楽天市場アプリ' => 'uuSdApp'   , 'スマートフォン' => 'uuSdWeb'];
      foreach ($json as $item) {
          $contents = array();
          $contents[] = $num ++;
          $contents[] = $item['item']['genre'];
          $contents[] = $item['item']['catalogId'];
          $contents[] = $item['item']['itemId'];
          $contents[] = $item['item']['itemName'];
          $contents[] = $item['item']['mngNumber'];
          $contents[] = $item['item']['itemNumber'];
          $contents[] = $item['salesFormula'][$visitKey[$device]];
          $contents[] = $item['salesFormula'][$uuKey[$device]];
          // アクセス数がゼロではない商品のみ出力
          if(intval($item['salesFormula'][$visitKey[$device]]) !== 0){
            $bytes += $fp->fputcsv($contents);
          }
      }

      $fs = new FileSystem();
      if (!$fs->exists($path) || ! $bytes) {
        @$fs->remove($path);
        throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . ']');
      }
      $logger->info('楽天アクセス数(' . $this->targetShopName . ') CSV出力成功。[' . $path . ']');
    }
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param string $saveDir
   * @return array
   */
  private function importCsvData($saveDir)
  {
    $logger = $this->logger;

    // 楽天アクセスカウント数取込
    $fs = new FileSystem();
    $finder = new Finder();

    if (!$fs->exists($saveDir)) {
      throw new RuntimeException('no data dir!! [' . $saveDir . ']');
    }
    $files = $finder->in($saveDir)->name('*.csv')->files();
    if (! $files->count()) {
      throw new RuntimeException('no data files!! [' . $saveDir . ']');
    }

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
    $doctrine = $this->getContainer()->get('doctrine');
    $commonUtil = new DbCommonUtil($doctrine);

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $doctrine->getConnection('main');

    // 一時テーブル 作成
    $dbMain->query("DROP TABLE IF EXISTS tb_rakuten_access_count_tmp");
    $sql = <<<EOD
        CREATE TEMPORARY TABLE tb_rakuten_access_count_tmp (
          syohin_code VARCHAR(255) NOT NULL DEFAULT ''
          , access_count INTEGER NOT NULL DEFAULT 0
          , access_person_count INTEGER NOT NULL DEFAULT 0
          , PRIMARY KEY (`syohin_code`)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8
EOD;
    $dbMain->query($sql);


    /** @var \SplFileInfo $file */
    foreach($files as $file) {

      $filePath = $file->getPath() . '/' . $file->getFilename();

      $dbMain->query("TRUNCATE tb_rakuten_access_count_tmp");

      // '１行目が「"全ページデータ"」
      // '６行目がCSVヘッダ行
      // 改行コード: LF
      // 文字コード: シフトJIS

      // 'CSVの内容が正しいかチェック

      if (! $this->validateCsv($filePath)) {
        throw new RuntimeException('楽天アクセスカウント数のCSVファイルが正常に取得できていません。処理を中断します。');
      }

      $fileInfo = $this->convertCsvForImport($filePath);
      // $logger->info(print_r($fileInfo, true));

      // 日付
      $date = explode(',', $fileInfo['headers'][2])[1]; // lc_date
      if (preg_match('/(\d+)年(\d+)月(\d+)日/', $date, $match)) {
        $date = sprintf('%d-%d-%d', $match[1], $match[2], $match[3]);
      }

      // 媒体
      $carrier = explode(',', $fileInfo['headers'][4])[1];
      if ($carrier == 'PC') {
        $carrier = 'PC';
      } else if ($carrier == 'スマートフォン') {
        $carrier = 'SP';
      } else if ($carrier == '楽天市場アプリ') {
        $carrier = 'APP';
      }

      // 一時テーブルへの取り込み
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :tmpFilePath
        INTO TABLE tb_rakuten_access_count_tmp
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
        (@1, @2, @3, @4, @5, @item_control_number, @7, @access_count, @unique_user_count)
        SET
          syohin_code = @item_control_number,
          access_count = @access_count,
          access_person_count = @unique_user_count;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':tmpFilePath', $fileInfo['tmp_path'], \PDO::PARAM_STR);
      $stmt->execute();

      $sql = "DELETE FROM tb_rakuten_access_count WHERE carrier = :carrier AND log_date = :date AND ne_mall_id = :ne_mall_id";
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':carrier', $carrier);
      $stmt->bindValue(':date', $date);
      $stmt->bindValue(':ne_mall_id', $this->neMallId);
      $stmt->execute();


      $sql = <<<EOD
        INSERT INTO tb_rakuten_access_count (
            log_date
          , ne_mall_id
          , type
          , carrier
          , category_name
          , syohin_code
          , url
          , access_count
          , access_person_count
        )
        SELECT
            :date
          , :ne_mall_id
          , 'p' AS type
          , :carrier
          , '' AS category_name
          , syohin_code
          , '' AS url
          , access_count
          , access_person_count
        FROM tb_rakuten_access_count_tmp
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date);
      $stmt->bindValue(':ne_mall_id', $this->neMallId);
      $stmt->bindValue(':carrier', $carrier);
      $stmt->execute();

      $logger->info('楽天アクセスカウント数 ファイル取込 [' . $carrier . ']');
    }

    // 一時テーブル 削除
    $dbMain->query("DROP TABLE tb_rakuten_access_count_tmp");

    // ファイル削除
    try {
      $finder = new Finder();
      $files = $finder->in($saveDir)->files(); // *.csv および 一時ファイルもすべて削除
      $fs->remove($files);
      $fs->remove($saveDir);
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      // 握りつぶす
    }

    // 実行後 行数
    $stmt = $dbMain->prepare('SELECT COUNT(*) FROM tb_rakuten_access_count WHERE log_date = :date');
    $stmt->bindValue(':date', $date);
    $stmt->execute();
    $count = $stmt->fetchColumn(0);

    $info = [
        'date'  => $date
      , 'count' => $count
    ];

    return $info;
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 二行目で判定
    $validLine = '商品分析';

    $fp = fopen($path, 'r');
    fgets($fp);
    $line = fgets($fp);
    fclose($fp);

    $line = str_replace(array("\r\n", "\r", "\n"), '', $line);

    return ($line === $validLine);
  }

  /**
   * ヘッダを除去したCSVを作成する
   * JSONから構築したCSVは最初からUTF-8のため文字コード変換処理は不要
   * 手動DLしたCSVもUTF-8なので変換は不要
   *
   * @param string $path
   * @return array
   */
  private function convertCsvForImport($path)
  {
    $result = [
        'tmp_path' => null
      , 'headers' => []
    ];

    // 一時ファイルパス
    $tmpPath = tempnam(dirname($path), 'tmp_');

    $fp = fopen($path, 'r');
    $fpOut = fopen($tmpPath, 'w');

    $lineNum = 0;
    while(($line = fgets($fp)) !== false) {

      $lineNum++;

      // 5行目までは付加情報
      if ($lineNum <= 5) {
        $result['headers'][] = trim(str_replace('"', '', $line));
        continue;

      // 6行目がCSVヘッダ。何もしない
      } else if ($lineNum == 6) {
        // do nothing
      }

      fputs($fpOut, $line);
    }

    fclose($fp);
    fclose($fpOut);

    $result['tmp_path'] = $tmpPath;

    return $result;
  }

  /**
   * 店舗名チェック
   * 
   * @param InputInterface $input
  */
  private function validate(InputInterface $input)
  {
    if (!in_array($input->getOption('target-shop-name'), self::SHOP_LIST, true)) {
      throw new ValidationException('対象店舗は、' . implode(', ', self::SHOP_LIST) . 'から指定してください [' . $input->getOption('target-shop-name') . ']');
    }
  }

  /**
   * CSVの保存ディレクトリを作成する
   *
   * @return string CSV保存ディレクトリへのパス
   */
  private function makeSaveDir()
  {
    $container = $this->getContainer();

    // ファイル処理関連
    $rootDir = $container->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data';

    // CSVファイル保存ディレクトリ
    $fs = new FileSystem();
    $accessCountDir = $dataDir . '/access_count';
    if (!$fs->exists($accessCountDir)) {
      $fs->mkdir($accessCountDir, 0755);
    }

    $saveDir = $accessCountDir . '/' . (new \DateTime())->format('Ymd') . '_' . $this->targetShopName;
    if ($fs->exists($saveDir)) {
      throw new RuntimeException('duplicate save directory.');
    }
    $fs->mkdir($saveDir, 0755);

    return $saveDir;
  }

  /**
   * tb_updaterecordの更新処理を行う
   * CSVディレクトリをオプション指定した場合は本関数は使用しない
   *
   * @param \DateTimeImmutable $targetDate
   */
  private function updateRecord($targetDate){
    if($targetDate !== null){
      // 最終時刻でDB登録する
      $executedDate = $targetDate->setTime(23, 59, 59);
      // 登録済み日時の取得（DBは最終時刻）
      $lastUpdated = $this->commonUtil->getUpdateRecordLastUpdatedDateTime($this->updateRecordNumber);
      if (!$lastUpdated || $lastUpdated < $executedDate) {
        $lastUpdated = $executedDate;
        $this->commonUtil->updateUpdateRecordTable($this->updateRecordNumber, $lastUpdated);  
      }
    }
  }

  /**
   * JSONをDLする
   *
   * @param \DateTimeImmutable $targetDate
   * @return array
   */
  private function getJsonData($client, $targetDate){
    $logger = $this->logger;

    // JSONダウンロード用URL
    $url = 'https://datatool.rms.rakuten.co.jp/access/item/get-item-list/';

    // 日付文字列へ変換
    $targetDateYmd = $targetDate->format('Ymd');
    // 分割DL用サイクル数
    $cycleNumber = 0;
    // 全件データ
    $allJsonData = [];

    // JSONダウンロードに成功したり失敗したりするので、複数回試行する
    $retryTimes = 0;
    $retryInterval = 5;

    do {
      // DL試行回数
      $logger->info("楽天アクセス数(" . $this->targetShopName . ") JSONダウンロードレスポンス取得 [" . ($cycleNumber + 1) . "回目]　リトライ[{$retryTimes}回目]");

      $params = [
        'period' => 'daily',
        'device' => 'deviceAll',
        'startDate' => $targetDateYmd,
        'endDate' => $targetDateYmd,
        'recordsNumber' => self::MAX_DL_COUNT,
        'itemId' => 'null',
        'dateType' => 'daily',
        'cycleNumber' => $cycleNumber
      ];
      // リクエスト発行
      $client->request('get', $url . '?' . http_build_query($params));

      // レスポンス取得
      $response = $client->getResponse();
      $status = $response->getStatus();
      $contentType = $response->getHeader('Content-Type');
      $requestUri = $client->getRequest()->getUri(); // ログ用

      $jsonData = @json_decode($response->getContent(), true);

      // 通信エラー、文字コードチェック
      if ($status !== 200 || !preg_match('!application/json!', $contentType) || !is_array($jsonData)) {
        // エラー情報
        $errorInfo = '[' . $status . '][' . $requestUri . '][' . $contentType . '][' . is_array($jsonData) . ']';
        // エラーコードが404もしくは500のときは例外
        if($status === 404 || $status === 500){
          throw new RuntimeException('楽天アクセスカウント取得処理でサーバーエラー：' . $errorInfo);
        }else{
          // その他のエラー（タイムアウト等）は再DLを試みるためfalseを返す
          $logger->info("楽天アクセスカウント取得処理で404/500以外のレスポンス：" . $errorInfo);
          
          // JSONデータの取得に失敗した場合はインターバルを取って再実行
          $doRetry = (++$retryTimes <= self::MAX_DL_RETRY_TIMES);
          if ($doRetry) {
            $logger->info(sprintf('楽天アクセス数(%s) JSONダウンロード 待機(%d秒)', $this->targetShopName, $retryInterval));
            sleep($retryInterval);
            continue; // 再DL
          }else{
            // リトライ（再DL）が MAX_DL_RETRY_TIMES まで繰り返された場合は、JSONの取得失敗とみなし例外を投げる
            throw new RuntimeException('楽天アクセスカウント数のJSONデータ取得に失敗しました。');
          }
        }
      }

      // 通信が正常の場合
      $jsonData = $jsonData["data"]; // metaデータは使わない
      if($jsonData === null && $cycleNumber === 0){ // 初回1000件が空の場合はエラー
        throw new RuntimeException('楽天アクセスカウント取得データが空です');
      }
      // 空データではないときに結合する
      if($jsonData !== null){
        $allJsonData = array_merge($allJsonData, $jsonData);
      }
      // 次の1000件DL
      $cycleNumber++;

    }while($jsonData !== null && $cycleNumber < 51); // データが空もしくは最大販売数５万件（＋１０００件）まで取得するまでDLを繰り返す
    
    return $allJsonData;
  }

}

