<?php

namespace BatchBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use MiscBundle\Entity\SymfonyUsers;

/**
 * 楽天RPP除外ファイル生成コマンド。
 *
 * 楽天から、現在の除外ファイルをダウンロードし、それを元に新しい除外設定を作成して楽天にアップロードする。
 * ダウンロード、アップロードはスクレイピングとなる。
 * 通常、楽天CSV出力の完了後、楽天側の取り込みが終わった直後に実行し、最新の登録データに合わせて処理を行う。
 *
 * @package BatchBundle\Command
 */
class ExportCsvRakutenRppExcludeCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  const EXPORT_PATH = 'Rakuten/Export/Rpp'; // アップロードファイル生成パス
  const IMPORT_DOWNLOADED_PATH = 'Rakuten/Downloaded_Rpp'; // ダウンロードファイル格納パス

  // アップロードファイルの分割行数
  const UPLOAD_CSV_MAX_NUM = 10000;

  // ダウンロード指示からのスリープ秒数
  const DOWNLOAD_SLEEP_SEC = 3;
  
  // 最大リトライ回数
  const DOWNLOAD_RETRY_MAX = 10;

  /** @var SymfonyUsers */
  private $account;

  /** @var GoutteClientCustom クライアント。メソッド間で引き回すためここで宣言。このクラス内でも直接取得せず、 getClient()で取得すること */
  private $client;

  /** @var string 現在のRPP除外ファイル  */
  private $importFile = null;

  /** @var string アップロードファイル出力パス */
  private $exportPath = null;

  /** @var array 生成ファイルのリスト */
  private $uploadFiles = null;

  /** @var bool アップロードを行うかどうか  */
  private $doUpload = true;

  protected function configure()
  {
    $this
    ->setName('batch:export-csv-rakuten-rpp-exclude')
    ->setDescription('CSVエクスポート 楽天RPP除外')
    ->addOption('import-file', null, InputOption::VALUE_OPTIONAL, 'サーバからダウンロードする代わりに、指定されたファイルを読み込む', null)
    ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ', 1)
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('楽天RPP除外ファイル出力処理を開始しました。');

    $this->importFile = $input->getOption('import-file');
    $this->doUpload = (bool)$input->getOption('do-upload');

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

    $logExecTitle = sprintf('楽天RPP除外CSV出力');
    $logger->setExecTitle($logExecTitle);
    $logger->initLogTimer();

    $logger->addDbLog($logger->makeDbLog($logExecTitle, '開始'));

    try {
      if (! $this->importFile) {
        $this->downloadCurrentFile($logger);
      }

      $this->importCurrentFile($logger);

      $this->createNewFile($logger);

      if ($this->doUpload) {
        $this->uploadNewFile($logger);
      }

      $logger->info('楽天RPP除外ファイル出力処理が完了しました。');
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '終了'));
      $logger->logTimerFlush();

    } catch (\Exception $e) {
      $logger->error('楽天RPP除外ファイル出力処理 エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog(null, '楽天RPP除外ファイル出力処理 エラー', 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
          , true, '楽天RPP除外ファイル出力処理 でエラーが発生しました。', 'error'
          );
      return 1;
    }
    return 0;
  }

  /**
   * 楽天にログイン済みのClientインスタンスを取得する。
   * まだインスタンスがなければインスタンスを生成し、ログインする。
   */
  private function getClient() {
    if ($this->client != null) {
      return $this->client;
    }
    $container = $this->getContainer();
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }
    // RMS ログイン
    $this->client = $webAccessUtil->getWebClient();
    $crawler = $webAccessUtil->rmsLogin($this->client, 'api'); // 必要なら、アカウント名を追加して切り替える
    return $this->client;
  }

  /**
   * 現時点のRPP除外ファイルをダウンロードする
   * @param unknown $logger
   */
  private function downloadCurrentFile($logger) {
    $logger->debug('楽天RPP除外ファイル出力処理 ダウンロード開始');
    $container = $this->getContainer();
    $client = $this->getClient();

    // RPP除外商品画面へ移動
    $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/exclude?page=1&sortBy=-updatedAt';
    $crawler = $client->request('get', $nextUrl);

    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200) {
      throw new \RuntimeException('RPP画面遷移エラー [' . $status . '][' . $uri . ']');
    }

    $resHeaders = $client->getInternalResponse()->getHeaders();
    preg_match('/XSRF-TOKEN=(.*); Path=/', $resHeaders['set-cookie'][1], $match);
    if (empty($match)) {
      throw new \RuntimeException('XSRF-TOKEN取得エラー') ;
    }
    $xsrfToken = $match[1];

    // この時点でのタイムスタンプを取得
    $timeStamp = time();
    
    // 全件ダウンロードを実行
    $client->setHeader('X-XSRF-TOKEN', $xsrfToken);
    $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/exclude/downloadAll';
    $crawler = $client->request('post', $nextUrl);
    $csvInfo = null;
    $counter = 0;

    // 確実にダウンロードするためにチェック
    while($counter <= self::DOWNLOAD_RETRY_MAX){
      // 一覧ページAPIから最新のものを取得
      $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/download/list';
      $crawler = $client->request('get', $nextUrl);
      $response = $client->getResponse();
      $json = json_decode($response->getContent(), true);
      
      // 一覧をチェック
      foreach($json['data']['userHistoryList'] as $row){
        // 完了以外は無視
        if($row['status'] !== 2) continue;
        
        // 全除外商品ダウンロード以外は無視
        if($row['reportType'] !== 9) continue;
        
        // リクエスト日時がタイムスタンプを下回ったらそこで確認中止
        if(strtotime($row['requestDate']) < $timeStamp) break;
        
        // チェックOKならデータセットしてループを脱出
        $csvInfo = $row;
        break 2;
      }
      
      // リトライカウンターをチェックしてスリープ
      $counter++;
      sleep(self::DOWNLOAD_SLEEP_SEC);
    }
    
    // CSVinfo未設定で例外スロー
    if(is_null($csvInfo)){
      throw new \RuntimeException('rpp exclude csv download error!! リトライ上限に達しました。');
    }
    
    // レスポンスからCSVダウンロードURLを構築
    $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/download/report?downloadId=' . $csvInfo['id'] . '&reportType=' . $csvInfo['reportType'];
    $crawler = $client->request('get', $nextUrl);

    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    $contentType = $response->getHeader('Content-Type');
    $contentLength = intval($response->getHeader('Content-Length'));

    if ($status !== 200 || strpos($contentType, 'text/csv') === false) {
      throw new \RuntimeException('rpp exclude csv download error!! [' . $status . '][' . $uri . '][' . $contentType . ']');
    }

    $logger->debug("RPP: CSV取得成功");

    // CSVファイル保存ディレクトリ
    $fs = new FileSystem();
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');
    $saveDir = $fileUtil->getWebCsvDir() . '/' . self::IMPORT_DOWNLOADED_PATH;
    if (!$fs->exists($saveDir)) {
      $fs->mkdir($saveDir, 0755);
    }

    // ファイル保存 末尾に / があるのでそれを除去してファイル名とする
    $fileName = sprintf('RPP_exclude_items_plusnao_data%s.csv', date('YmdHis00000000'));
    $this->importFile = $saveDir . '/' . $fileName;

    $file = new \SplFileObject($this->importFile, 'w'); // 上書き
    $bytes = $file->fwrite($response->getContent());

    if (!$fs->exists($this->importFile) || ! $bytes) {
      @$fs->remove($this->importFile);
      throw new \RuntimeException('can not save csv file. [ ' . $this->importFile . ' ][' . $bytes . '][' . $contentLength . ']');
    }
    $logger->info('楽天RPP除外ファイル出力処理 CSV取得成功。[' . $this->importFile . ']');
  }

  /**
   * 取り込んだRPP除外ファイルをテンポラリテーブルへ取り込む
   * @param unknown $logger
   */
  private function importCurrentFile($logger) {
    $logger->debug('楽天RPP除外ファイル出力処理 インポート開始：ファイル名=' . $this->importFile);

    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    $dbTmp->exec("DROP TABLE IF EXISTS tmp_rakuten_rpp_exclude;");
    $sql = <<<EOD
    CREATE TABLE tmp_rakuten_rpp_exclude (
      control_column varchar(1) NOT NULL DEFAULT '' COMMENT 'コントロールカラム',
      daihyo_syohin_code varchar(255) NOT NULL DEFAULT '' COMMENT '商品管理番号',
      PRIMARY KEY (daihyo_syohin_code)
    ) ENGINE=InnoDB COMMENT='RPP除外ファイル一時取込テーブル';
EOD;
    $dbTmp->exec($sql);

    $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        INTO TABLE tmp_rakuten_rpp_exclude
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
    $stmt = $dbTmp->prepare($sql);
    $stmt->bindValue(':importFileName', $this->importFile, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 新しくアップロードするRpp除外ファイルを生成する。
   * 出力対象は以下の通り。
   *
   * (1) 倉庫・RPP除外どちらにも未登録で、RPP対象の商品（出力対象外）
   * (2) 倉庫・RPP除外どちらにも未登録で、RPP除外対象の商品（n,NEWで登録対象）
   * (3) RPP除外に登録済みで、新たにRPP対象とする商品（d,DELで出力対象）
   * (4) 倉庫に登録済みで、RPP除外に登録済みの商品（d.DELで出力対象）
   * @param unknown $logger
   */
  private function createNewFile($logger) {
    $logger->debug('楽天RPP除外ファイル出力処理 生成開始');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    $dbTmpName = $dbTmp->getDatabase();

    // 出力パス
    $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . (new \Datetime())->format('YmdHis');
    $fs = new FileSystem();
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }

    $sql = <<<EOD
        -- (4) 倉庫に登録済みで、RPP除外に登録済みの商品（d.DELで出力対象）
        SELECT 'd' as コントロールカラム, i.daihyo_syohin_code as 商品管理番号
        FROM tb_rakuteninformation i
        JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        JOIN {$dbTmpName}.tmp_rakuten_rpp_exclude e ON i.daihyo_syohin_code = e.daihyo_syohin_code
        WHERE i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND i.warehouse_stored_flg = 1

        UNION
        -- (3) RPP除外に登録済みで、新たにRPP対象とする商品（d,DELで出力対象）
        SELECT 'd' as コントロールカラム, i.daihyo_syohin_code as 商品管理番号
        FROM tb_rakuteninformation i
        JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        JOIN {$dbTmpName}.tmp_rakuten_rpp_exclude e ON i.daihyo_syohin_code = e.daihyo_syohin_code
        WHERE i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND i.warehouse_stored_flg = 0
          AND i.rpp_flg = 1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->execute();

    $files1 = $this->output($logger, $stmt, 'del');

    $sql = <<<EOD
        -- (2) 倉庫・RPP除外どちらにも未登録で、RPP除外対象の商品（n,NEWで登録対象）
        SELECT 'n' as コントロールカラム, i.daihyo_syohin_code as 商品管理番号
        FROM tb_rakuteninformation i
        JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN {$dbTmpName}.tmp_rakuten_rpp_exclude e ON i.daihyo_syohin_code = e.daihyo_syohin_code
        WHERE e.daihyo_syohin_code IS NULL
          AND i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND i.warehouse_stored_flg = 0
          AND i.rpp_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->execute();

    $files2 = $this->output($logger, $stmt, 'add');

    $this->uploadFiles = array_merge($files1, $files2);
  }

  /**
   * execute後、未fetchのstmtを受け取り、指定件数毎に分割してファイルに出力する。
   * @param BatchLogger $logger Logger
   * @param Statement $stmt 出力対象のSelect実行後のStatement。これをfetchして出力する
   * @param string $suffix ファイル名の末尾（さらにこのうしろに連番）
   */
  private function output($logger, $stmt, $suffix) {

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // ヘッダ
    $headers = [
        'コントロールカラム'
        , '商品管理番号'
    ];
    $headerLine = implode(',', $headers);
    $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

    $fp = null;
    $files = [];
    $fileIndex = 1;
    $num = 0; // 全体の件数
    $count = 0; // 1ファイル内の行数
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      if (!isset($fp) || !$fp) {
        $filePath = sprintf('%s/exclude_rpp_%s_%02d.csv', $this->exportPath, $suffix, $fileIndex++);
        $files[] = $filePath;
        $fp = fopen($filePath, 'wb');
        fputs($fp, $headerLine);
      }

      $line = strtolower(implode(',', $row));
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
    $logger->info("楽天RPP除外ファイル出力処理： $suffix - $num 件出力");
    return $files;
  }

  /**
   * アップロードを実行し、結果を取得する。
   * 個別のファイルで例外やデータ不正が発生しても、ひとまず各ファイルの完了まで処理を継続する。
   * （楽天CSV出力で表示対象商品が増えているはずなので、少しでも抑制するため）
   *
   * @param BatchLogger $logger Logger
   * @param $filePath アップロード対象ファイルの配列
   */
  private function uploadNewFile($logger) {
    if (empty($this->uploadFiles)) {
      $logger->addDbLog($logger->makeDbLog(null, 'アップロード対象データなし'));
      return;
    }
    $client = $this->getClient();

    // RPP除外商品画面へ移動
    $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/exclude?page=1&sortBy=-updatedAt';
    $crawler = $client->request('get', $nextUrl);

    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200) {
      throw new \RuntimeException('RPP画面遷移エラー [' . $status . '][' . $uri . ']');
    }

    $resHeaders = $client->getInternalResponse()->getHeaders();
    preg_match('/XSRF-TOKEN=(.*); Path=/', $resHeaders['set-cookie'][0], $match);
    if (empty($match)) {
      throw new \RuntimeException('XSRF-TOKEN取得エラー') ;
    }
    $xsrfToken = $match[1];

    $hasError = false;
    foreach ($this->uploadFiles as $uploadFilePath) {
      $uploadFile = new UploadedFile(
          $uploadFilePath,
          basename($uploadFilePath),
          'text/csv',
          null
          );
      try {
        $resultInfo = $this->uploadOneFile($uploadFile, $xsrfToken);
        if ($resultInfo['status'] != 'ok') {
          $hasError = true;
        }
        $result[basename($uploadFilePath)] = [
            'message' => $resultInfo['message']
            , 'detail' => $resultInfo['detail']
        ];
      } catch (Exception $e) {
        $hasError = true;
        $result[basename($uploadFilePath)] = [
            'message' => $e->getMessage()
        ];
      }
    }
    if ($hasError) {
      $logger->addDbLog(
          $logger->makeDbLog(null, 'アップロード完了', 'エラー、不正データあり')->setInformation($result)
          , true, "楽天RPP除外データアップロードでエラーが発生しました。", 'error'
          );
    } else {
      $logger->addDbLog($logger->makeDbLog(null, 'アップロード完了')->setInformation($result));
    }
  }
  private function uploadOneFile(UploadedFile $uploadFile, $xsrfToken) {
    $container = $this->getContainer();
    $client = $this->getClient();
    $client->setHeader('X-XSRF-TOKEN', $xsrfToken);
    $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/exclude/upload';
    try {
      $client->request('post', $nextUrl, array(), array('file' => $uploadFile));
    } catch (\InvalidArgumentException $e) {
      throw new \RuntimeException("ファイルアップロードエラー". $e->getCode());
    }

    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 201) {
      throw new \RuntimeException('ファイルアップロードエラー[' . $status . '][' . $uri . ']');
    }

    $csvUploadInfo = json_decode($response->getContent(), true);

    $resultInfo = ['status' => 'ok'];
    // 失敗
    if ($csvUploadInfo['status'] != 'SUCCESS') {
      $resultInfo['status'] = 'ng';
      $resultInfo['message'] = 'アップロード失敗';
      $resultInfo['detail'] = '';
      return $resultInfo;
    }

    // 成功（エラーなし）
    if (empty($csvUploadInfo['data']['errorSheet'])) {
      $resultInfo['message'] = 'RPP更新成功';
      $resultInfo['detail'] = '';
      return $resultInfo;
    }

    // 成功（エラーデータあり）
    $resultInfo['status'] = 'ng';
    $resultInfo['message'] = '成功（エラーデータあり）';

    $nextUrl = 'https://ad.rms.rakuten.co.jp/rpp/api/exclude/errorsheet?filename=' . $csvUploadInfo['data']['errorSheet']['fileName'] . '&storagecode=' . $csvUploadInfo['data']['errorSheet']['storage'];
    try {
      $crawler = $client->request('get', $nextUrl);
    } catch (\InvalidArgumentException $e) {
      throw new \RuntimeException("エラー情報シートダウンロードエラー：" . print_r($resultInfo, true));
    }
    $response = $client->getResponse();
    $status = $response->getStatus();
    if ($status !== 200) {
      throw new \RuntimeException('エラー情報シートダウンロードエラー：' . $status . '][' . $client->getRequest()->getUri() . ']');
    }

    // CSVファイル保存ディレクトリ
    $fileUtil = $this->getFileUtil();
    $fs = new FileSystem();
    $saveDir = $fileUtil->getWebCsvDir() . '/' . self::IMPORT_DOWNLOADED_PATH;
    if (!$fs->exists($saveDir)) {
      $fs->mkdir($saveDir, 0755);
    }
    // ファイル保存 末尾に / があるのでそれを除去してファイル名とする
    $fileName = sprintf('RPP_exclude_upload_error_data%s.csv', date('YmdHis00000000'));
    $errorFile = $saveDir . '/' . $fileName;

    $file = new \SplFileObject($errorFile, 'w'); // 上書き
    $bytes = $file->fwrite($response->getContent());

    if (!$fs->exists($errorFile) || ! $bytes) {
      @$fs->remove($errorFile);
      $resultInfo['detail'] = 'エラー情報シートの取得に失敗しました';
    } else {
      $resultInfo['detail'] = ['errorFile' => $errorFile];
    }
    return $resultInfo;
  }
}