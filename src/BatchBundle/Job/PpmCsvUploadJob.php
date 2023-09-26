<?php
/**
 * PPM CSVアップロード処理 キュー対応ジョブ
 * 専用キューのため、排他チェックは不要
 * User: hirai
 * Date: 2016/11/07
 */

namespace BatchBundle\Job;

use BatchBundle\MallProcess\PpmMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class RakutenCsvUploadJob
 * @package BatchBundle\Job
 */
class PpmCsvUploadJob extends BaseJob
{
  protected $account = null;
  private $results = [
      'message' => null
    , 'filePath' => null
    , 'remoteFileName' => null
  ];

  /// override
  public function getCurrentCommandName()
  {
    return sprintf('PPM CSVアップロード : %s', basename($this->getArgv('filePath')));
  }

  public function run($args)
  {
    try {
      $container = $this->getContainer();

      // FTPアップロード
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $container->get('misc.util.db_common');

      $this->runningJobName = $this->getCurrentCommandName();
      $commonUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_PPM_CSV_UPLOAD);

      $execTitle = $this->getArgv('execTitle', 'PPM CSVアップロード');

      $logger = $this->getLogger();
      $logger->setExecTitle($execTitle);
      $logger->initLogTimer();
      $logger->info('PPM csv upload start.');

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

      $filePath = $this->getArgv('filePath');
      $remoteFileName = $this->getArgv('remoteFileName');
      if (!$remoteFileName) {
        $remoteFileName = basename($filePath);
      }

      $this->results['filePath'] = $filePath;
      $this->results['remoteFileName'] = $remoteFileName;
      $logger->info(print_r($this->results, true));

      $fs = new FileSystem();
      if (!$filePath || !$remoteFileName || !$fs->exists($filePath) || !is_file($filePath)) {
        throw new \RuntimeException(sprintf('アップロードファイルが正しく指定されていません。[%s][%s]', $filePath, $remoteFileName));
      }

      $config = $container->getParameter('ftp_ppm');
      if (!$config) {
        throw new \RuntimeException('no ftp config (PPM csv upload)');
      }
      $config['user'] = $commonUtil->getSettingValue(TbSetting::KEY_PPM_FTP_USER);
      $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_PPM_FTP_PASSWORD);

      /** @var PpmMallProcess $processor */
      $processor = $container->get('batch.mall_process.ppm');

      // FTPアップロード ※空になるのを待つ
      $processor->uploadCsv($config, $filePath, preg_replace('|/$|', '', $config['path_csv']) . '/' . $remoteFileName, true);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($execTitle, 'CSVファイルアップロード', '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('PPM CSV出力アップロード処理を完了しました。');

      $commonUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_PPM_CSV_UPLOAD);

      return 0;

    } catch (JobException $e) {
      $logger->error('PpmCsvUploadJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage(), TbProcessExecuteLog::QUEUE_NAME_PPM_CSV_UPLOAD);
    }

    return 0;
  }
}
