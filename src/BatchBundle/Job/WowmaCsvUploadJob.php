<?php
/**
 * Wowma CSVアップロード処理 キュー対応ジョブ
 * 専用キューのため、排他チェックは不要
 * User: hirai
 * Date: 2016/10/05
 */

namespace BatchBundle\Job;

use BatchBundle\MallProcess\WowmaMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class WowmaCsvUploadJob
 * @package BatchBundle\Job
 */
class WowmaCsvUploadJob extends BaseJob
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
    return sprintf('Wowma CSVアップロード : %s', basename($this->getArgv('filePath')));
  }

  public function run($args)
  {
    try {
      $execTitle = $this->getArgv('execTitle', 'Wowma CSVアップロード');

      $logger = $this->getLogger();
      $logger->setExecTitle($execTitle);
      $logger->initLogTimer();
      $logger->info('wowma csv upload start.');

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

      /** @var WowmaMallProcess $processor */
      $processor = $container->get('batch.mall_process.wowma');

      $ftpConfig = $this->getContainer()->getParameter('ftp_wowma');
      $config = $ftpConfig['csv_upload'];
      $config['user'] = $commonUtil->getSettingValue(TbSetting::KEY_WOWMA_FTP_USER);
      $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_WOWMA_FTP_PASSWORD);

      $logger->info(print_r($config, true));

      // FTPアップロード ※空になるのを待つ
      $processor->uploadCsv($config, $filePath, $remoteFileName, true);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($execTitle, 'CSVファイルアップロード', '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('Wowma CSV出力アップロード処理を完了しました。');

      return 0;

    } catch (JobException $e) {
      $logger->error('WowmaCsvUploadJobで例外発生:' . $e->getMessage() . ':' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $message = $e->getMessage();

      if ($message) {
        $this->results['message'] = $message;
      }
      $logger->addDbLog(
        $logger->makeDbLog('Wowma CSVアップロード', 'CSVファイルアップロード', 'エラー終了')->setInformation($this->results)
        , true
        , 'Wowma CSVアップロード処理 でエラーが発生しました。'
        , 'error'
      );

      $this->exitError(1, $message . ':' . $e->getTraceAsString());
    }

    return 0;
  }
}
