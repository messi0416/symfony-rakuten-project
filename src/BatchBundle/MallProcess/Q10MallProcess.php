<?php

namespace BatchBundle\MallProcess;

use BatchBundle\Job\Q10CsvUploadJob;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * モール別特殊処理 - Q10
 */
class Q10MallProcess extends BaseMallProcess
{

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
  ];

  /**
   * Q10 CSV FTPアップロード処理
   * ※ remoteDir にファイルが無くなるまで待ってアップロードする。
   * @param array $config FTP接続設定
   * @param string $filePath アップロードファイルパス
   * @param string $remotePath リモートパス
   * @param bool $waitEmpty FTPディレクトリの同名ファイルが無くなるまで待つか。
   * @throws \Exception
   */
  public function uploadCsv($config, $filePath, $remotePath, $waitEmpty = true)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logTitle = 'CSVファイルアップロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, basename($filePath)));
    $logger->info('Q10 CSVファイルアップロード ' . $filePath);


    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    $ftp->connect($config['host']);

    try {
      $ftp->login($config['user'], $config['password']);
    } catch (\Exception $e) {
      $message = 'Q10のCSVファイルアップロード処理中、Q10のFTPにログインできませんでした。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path']);

    // ディレクトリが空になるまで待つ
    if ($waitEmpty) {
      $limit = new \DateTime(); // 開始時刻
      $limit->modify('+ 6 hour'); // 6時間で諦める
      do {

        $fileList = $ftp->nlist('./');
        $remoteFileName = basename($remotePath);

        $logger->info('Q10 CSV FTPアップロード FTP空き確認: ' . print_r($fileList, true) . ' => ' . $remoteFileName);

        if (!is_array($fileList)) {
          $message = 'Q10のCSVファイルアップロード処理中、Q10のFTPのファイル一覧が取得できませんでした。';
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

        $logger->info('Q10 CSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true) . ' => ' . $remoteFileName);
        sleep(60); // 1分待つ

        if ($limit < new \DateTime()) {
          $message = 'Q10のCSVファイルアップロード処理中、Q10のFTPが空にならずアップロードできませんでした。処理を中止します。';
          throw new \RuntimeException($message);
        }

      } while(count($fileList)) ;
    }

    $ftp->put($remotePath, $filePath, FTP_BINARY);
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

    $job = new Q10CsvUploadJob();
    $job->queue = 'q10CsvUpload'; // キュー名
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

}


