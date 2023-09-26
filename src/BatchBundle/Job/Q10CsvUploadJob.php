<?php
/**
 * Q10 CSVアップロード処理 キュー対応ジョブ
 * 専用キューのため、排他チェックは不要
 * User: hirai
 * Date: 2016/10/05
 */

namespace BatchBundle\Job;

use BatchBundle\MallProcess\Q10MallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class Q10CsvUploadJob
 * @package BatchBundle\Job
 */
class Q10CsvUploadJob extends BaseJob
{
  protected $account = null;
  private $results = [
      'message' => null
    , 'targetEnv' => null
    , 'filePath' => null
    , 'remoteFileName' => null
  ];

  /// override
  public function getCurrentCommandName()
  {
    return sprintf('Q10 CSVアップロード : %s', basename($this->getArgv('filePath')));
  }

  public function run($args)
  {
    try {
      $execTitle = $this->getArgv('execTitle', 'Q10 CSVアップロード');

      $logger = $this->getLogger();
      $logger->setExecTitle($execTitle);
      $logger->initLogTimer();
      $logger->info('Q10 csv upload start.');

      $container = $this->getContainer();

      // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
      if ($accountId = $this->getArgv('account')) {
        /** @var SymfonyUsers $account */
        $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
        if ($account) {
          $this->account = $account;
          $logger->setAccount($account);
        }
      }

      $logger->addDbLog($logger->makeDbLog($execTitle, 'CSVファイルアップロード', '開始'));

      $targetEnv = $this->getArgv('targetEnv', 'test');
      $filePath = $this->getArgv('filePath');
      $remoteFileName = $this->getArgv('remoteFileName');
      if (!$remoteFileName) {
        $remoteFileName = basename($filePath);
      }

      $this->results['targetEnv'] = $targetEnv;
      $this->results['filePath'] = $filePath;
      $this->results['remoteFileName'] = $remoteFileName;
      $logger->info(print_r($this->results, true));

      $fs = new FileSystem();
      if (!$filePath || !$remoteFileName || !$fs->exists($filePath) || !is_file($filePath)) {
        throw new \RuntimeException(sprintf('アップロードファイルが正しく指定されていません。[%s][%s]', $filePath, $remoteFileName));
      }

      // FTPアップロード
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $container->get('misc.util.db_common');

      /** @var Q10MallProcess $processor */
      $processor = $container->get('batch.mall_process.q10');

      $ftpConfig = $this->getContainer()->getParameter('ftp_q10');
      $config = $ftpConfig['csv_upload'];

      $logger->info(print_r($config, true));

      // FTPアップロード ※空になるのを待つ
      $processor->uploadCsv($config, $filePath, $remoteFileName, true);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($execTitle, 'CSVファイルアップロード', '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('Q10 CSV出力アップロード処理を完了しました。');

      return 0;

    } catch (JobException $e) {
      $logger->error('Q10CsvUploadJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $message = $e->getMessage();
      if ($message) {
        $this->results['message'] = $message;
      }
      $logger->addDbLog($logger->makeDbLog(null, 'CSVファイルアップロード')->setInformation($this->results));

      $this->exitError(1, $message);
    }

    return 0;
  }
}
