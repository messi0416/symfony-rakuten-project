<?php

namespace BatchBundle\MallProcess;
use BatchBundle\Job\WowmaCsvUploadJob;
use InvalidArgumentException;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * モール別特殊処理 - Wowma
 */
class WowmaMallProcess extends BaseMallProcess
{

  const CSV_TYPE_LOT_NUMBER = 'lot_number';
  const IMAGE_URL = 'https://image.wowma.jp/10402816/';

  public static $IMAGE_CODE_LIST = [
      'p001'
    , 'p002'
    , 'p003'
    , 'p004'
    , 'p005'
    , 'p006'
    , 'p007'
    , 'p008'
    , 'p009'
    , 'p010'
    , 'p011'
    , 'p012'
    , 'p013'
    , 'p014'
    , 'p015'
    , 'p016'
    , 'p017'
    , 'p018'
    , 'p019'
    , 'p020'
  ];


  /**
   * Wowma CSV FTPアップロード処理
   * ※ remoteDir にファイルが無くなるまで待ってアップロードする。
   * @param array $config FTP接続設定
   * @param string $filePath アップロードファイルパス
   * @param string $remotePath リモートパス
   * @param bool $waitEmpty FTPディレクトリの同名ファイルが無くなるまで待つか。
   * @param int $waitMinutes $waitEmpty=trueの時の待機時間（分）。デフォルト6時間
   * @throws \Exception
   */
  public function uploadCsv($config, $filePath, $remotePath, $waitEmpty = true, $waitMinutes = 360)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logTitle = 'CSVファイルアップロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, basename($filePath)));
    $logger->info('Wowma CSVファイルアップロード ' . $filePath);


    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    $ftp->connect($config['host']);

    try {
      $ftp->login($config['user'], $config['password']);
    } catch (\Exception $e) {
      $message = 'WowmaのCSVファイルアップロード処理中、WowmaのFTPにログインできませんでした。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path']);

    // ディレクトリが空になるまで待つ
    if ($waitEmpty) {
      $limit = new \DateTime(); // 開始時刻
      $limit->modify("+ $waitMinutes minute"); // 指定時間で諦める
      do {

        $fileList = $ftp->nlist('./');
        $remoteFileName = basename($remotePath);

        $logger->info('WowmaCSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true) . ' => ' . $remoteFileName);

        if (!is_array($fileList)) {
          $message = 'WowmaのCSVファイルアップロード処理中、WowmaのFTPのファイル一覧が取得できませんでした。';
          throw new \RuntimeException($message);
        }

        $isExists = false;
        foreach($fileList as $file) {
          if (basename($file) == $remoteFileName) {
            $isExists = true;
            break;
          }
        }
        if (!$isExists) {
          break;
        }

        $logger->info('WowmaCSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true));
        sleep(60); // 1分待つ

        if ($limit < new \DateTime()) {
          $message = 'WowmaのCSVファイルアップロード処理中、WowmaのFTPが空にならずアップロードできませんでした。処理を中止します。';
          throw new \RuntimeException($message);
        }

      } while(count($fileList)) ;
    }

    // どうもこのサーバは、別名でアップロード（あるいはアップロード時の名前指定）すると反応してくれない？
    // ローカルでファイルをファイル名を合わせてからアップロードしてみる。
    $fileUtil = $this->getFileUtil();
    $tmpDir = sprintf('%s/ftp/wowma_upload', $fileUtil->getCacheDir());
    $fs = new FileSystem();
    if (!$fs->exists($tmpDir)) {
      $fs->mkdir($tmpDir);
    }
    $finder = new Finder();
    $fs->remove($finder->in($tmpDir)->files());
    $tmpFilePath = sprintf('%s/%s', $tmpDir, basename($remotePath));
    $fs->copy($filePath, $tmpFilePath);

    $ftp->put($remotePath, $tmpFilePath, FTP_BINARY);
    $ftp->close();
  }

  /**
   * Wowma CSV FTPアップロード キュー追加処理
   * @param $filePath
   * @param $remoteFileName
   * @param $targetEnv
   * @param string $execTitle
   * @param integer $accountId
   * @throws \Exception
   */
  public function enqueueUploadCsv($filePath, $remoteFileName, $targetEnv, $execTitle = '', $accountId = null)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->info(sprintf('enqueue wowma csv upload : %s / %s / %s', $filePath, $remoteFileName, $targetEnv));

    /** @var \BCC\ResqueBundle\Resque $resque */
    $resque = $container->get('bcc_resque.resque');

    $job = new WowmaCsvUploadJob();
    $job->queue = 'wowmaCsvUpload'; // キュー名
    $job->args = [
        'filePath' => $filePath
      , 'remoteFileName' => $remoteFileName
      , 'targetEnv' => $targetEnv
      , 'execTitle' => $execTitle
    ];
    if (!is_null($accountId)) {
      $job->args['account'] = $accountId;
    }

    $resque->enqueue($job); // リトライなし
  }


  /**
   * アップロードされたファイルを全てをUTF-8へ変換し、種類別にファイルを仕分け
   * @param File[] $files
   * @return array
   */
  public function processUploadedCsvFiles($files)
  {
    $logger = $this->getLogger();

    $result = [];
    $logger->info('件数 : ' . count($files));

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();

    $fs = new Filesystem();
    $uploadDir = sprintf('%s/Wowma/Import/%s', $fileUtil->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
    if (!$fs->exists($uploadDir)) {
      $fs->mkdir($uploadDir, 0755);
    }

    foreach($files as $file) {
      $logger->info('uploaded : ' . print_r($file->getPathname(), true));

      try {
        // 2行目（最初のデータ）で文字コード判定 ＆ UTF-8変換
        $fp = fopen($file->getPathname(), 'rb');
        fgets($fp); // 先頭行を捨てる
        $secondLine = fgets($fp);
        fclose($fp);
        if (!$secondLine) { // 2行目がなければ処理不要
          continue;
        }
        $charset = mb_detect_encoding($secondLine, ['SJIS-WIN', 'UTF-8']);
        $logger->info(sprintf('%s : %s', $file->getFilename(), $charset));
        if (!$charset) {
          throw new \RuntimeException(sprintf('CSVファイルの文字コードが判定できませんでした。[%s]', $file->getFilename()));
        }

        $newFilePath = tempnam($uploadDir, 'wowma_utf_');
        chmod($newFilePath, 0666);
        $fp = fopen($newFilePath, 'wb');
        $fileUtil->createConvertedCharsetTempFile($fp, $file->getPathname(), $charset, 'UTF-8', function($line) {
          // BOMがついていれば　問答無用で削除
          $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
          return $line;
        });
        fclose($fp);
        $newFile = new File($newFilePath);
        $csvType = $this->guessCsvTypeByCsvHeader($newFile);

        $logger->info(sprintf('CSV TYPE: %s', $csvType));

        // TAB区切りかも
        if (!$csvType) {
          $csvType = $this->guessCsvTypeByCsvHeader($newFile, "\t");
        }
        if (!$csvType) {
          throw new \RuntimeException(sprintf('CSV種別が特定できませんでした。[%s]', $file->getClientOriginalName()));
        }

        if (!isset($result[$csvType])) {
          $result[$csvType] = [];
        }

        $result[$csvType][] = $newFile;

      } catch (\Exception $e) {
        throw new \RuntimeException(sprintf('%s [%s]', $e->getMessage(), $file->getClientOriginalName()));
      }
    }

    // $logger->info(print_r($result, true));
    return $result;
  }

  /**
   * ロットナンバー アップロードCSVデータ取込
   * @param File[][] $fileList
   * @throws \Doctrine\DBAL\DBALException
   */
  public function importLotNumberCsv($fileList)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    foreach($fileList as $type => $files) {
      switch($type) {
        case self::CSV_TYPE_LOT_NUMBER:

          // 空にする
          if (count($files)) {
            $dbMain->exec("TRUNCATE tb_wowma_lot_number;");
          }

          foreach($files as $i => $file) {

            $logger = $this->getLogger();
            $logger->info('import file: ' . $file->getPathname());

            // 先頭行チェック
            $fp = fopen($file->getPathname(), 'rb');
            $line = trim(fgets($fp));
            if (explode(',', $line) != [
                  'ctrlCol'
                , 'lotNumber'
                , 'itemCode'
                , 'itemPrice'
              ]) {
              throw new \RuntimeException('取り込むファイルの書式が違います。');
            }

            $sql = <<<EOD
              LOAD DATA LOCAL INFILE :importFilePath
              IGNORE INTO TABLE tb_wowma_lot_number
              FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
              LINES TERMINATED BY '\n' IGNORE 1 LINES
              (
                  ctrlCol
                , lotNumber
                , itemCode
                , itemPrice
              )
              SET created = NOW()
                , updated = NOW()
EOD;
            $stmt = $dbMain->prepare($sql);
            $stmt->bindValue(':importFilePath', $file->getPathname());
            $stmt->execute();

            $logger->info('import done: ' . $file->getPathname());

            // FIXME ここで、商品検索用のデータを更新してもよいが省略

            return;

          }

          break;
      }
    }
  }



  /**
   * ヘッダ行（およびデータ１行目）からCSV種別判定
   * ※valid チェックも兼ねる
   * @param \SplFileInfo $file
   * @param string $delimiter
   * @return string
   */
  private function guessCsvTypeByCsvHeader($file, $delimiter = ',')
  {
    $fObj = $file->openFile('rb');

    // ヘッダ行を配列に分解
    $fields = $fObj->fgetcsv($delimiter, '"', '\\');

    $this->getLogger()->dump($fields);
    $this->getLogger()->dump(self::$CSV_FIELDS_LOT_NUMBER);
    $this->getLogger()->dump(self::$CSV_FIELDS_LOT_NUMBER == $fields ? 'ok' : 'ng');


    if (!is_array($fields)) {
      throw new \RuntimeException('ヘッダ行が取得できませんでした。');
    }

    $type = null;
    switch ($fields) {
      case self::$CSV_FIELDS_LOT_NUMBER:
        $type = self::CSV_TYPE_LOT_NUMBER;
        break;
      default:
        break;
    }

    return $type;
  }


  private static $CSV_FIELDS_LOT_NUMBER = [
      'ctrlCol'
    , 'lotNumber'
    , 'itemCode'
    , 'itemPrice'
  ];


}


