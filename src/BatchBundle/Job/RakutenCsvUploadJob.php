<?php
/**
 * 楽天CSVアップロード処理 キュー対応ジョブ
 * 専用キューのため、排他チェックは不要
 * User: hirai
 * Date: 2016/10/05
 */

namespace BatchBundle\Job;

use BatchBundle\MallProcess\RakutenMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use BatchBundle\Command\ExportCsvRakutenCommand;


/**
 * Class RakutenCsvUploadJob
 * @package BatchBundle\Job
 */
class RakutenCsvUploadJob extends BaseJob
{
  protected $account = null;
  private $results = [
      'message' => null
    , 'targetShop' => null
    , 'targetEnv' => null
    , 'filePath' => null
    , 'remoteFileName' => null
  ];

  /// override
  public function getCurrentCommandName()
  {
    return sprintf('楽天CSVアップロード : %s', basename($this->getArgv('filePath')));
  }

  public function run($args)
  {
    try {
      $container = $this->getContainer();

      // FTPアップロード
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $container->get('misc.util.db_common');

      $this->runningJobName = $this->getCurrentCommandName();
      $commonUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_RAKUTEN_CSV_UPLOAD);

      $execTitle = $this->getArgv('execTitle', '楽天CSVアップロード');

      $logger = $this->getLogger();
      $logger->setExecTitle($execTitle);
      $logger->initLogTimer();
      $logger->info('rakuten csv upload start.');

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
      $targetShop = $this->getArgv('target');
      $filePath = $this->getArgv('filePath');
      $remoteFileName = $this->getArgv('remoteFileName');
      $isGold = $this->getArgv('isGold');
      if (!$remoteFileName) {
        $remoteFileName = basename($filePath);
      }
      $ftpConfigKey = null;
      $ftpPasswordSettingKey = null;
      if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
        $ftpConfigKey = $isGold ? 'ftp_gold_rakuten' : 'ftp_rakuten';
        $ftpPasswordSettingKey = 'RAKUTEN_GOLD_FTP_PASSWORD';
      } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO) {
        $ftpConfigKey = $isGold ? 'ftp_gold_rakuten_motto' : 'ftp_rakuten_motto';
        $ftpPasswordSettingKey = 'MOTTO_GOLD_FTP_PASSWORD';
      } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST) {
        $ftpConfigKey = $isGold ? 'ftp_gold_rakuten_laforest' : 'ftp_rakuten_laforest';
        $ftpPasswordSettingKey = 'LAFOREST_GOLD_FTP_PASSWORD';
      } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO) {
        $ftpConfigKey = $isGold ? 'ftp_gold_rakuten_dolcissimo' : 'ftp_rakuten_dolcissimo';
        $ftpPasswordSettingKey = 'DOLCISSIMO_GOLD_FTP_PASSWORD';
      } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA) {
        $ftpConfigKey = $isGold ? 'ftp_gold_rakuten_gekipla' : 'ftp_rakuten_gekipla';
        $ftpPasswordSettingKey = 'GEKIPLA_GOLD_FTP_PASSWORD';
      } else {
        throw new \RuntimeException(sprintf($execTitle . ' 対象店舗が正しく指定されていません。[%s]', $targetShop));
      }
      $this->results['targetShop'] = $targetShop;
      $this->results['targetEnv'] = $targetEnv;
      $this->results['filePath'] = $filePath;
      $this->results['remoteFileName'] = $remoteFileName;

      $fs = new FileSystem();
      if (!$filePath || !$remoteFileName || !$fs->exists($filePath) || !is_file($filePath)) {
        throw new \RuntimeException(sprintf($execTitle . ' アップロードファイルが正しく指定されていません。[%s][%s]', $filePath, $remoteFileName));
      }

      /** @var RakutenMallProcess $processor */
      $processor = $container->get('batch.mall_process.rakuten');

      $ftpConfig = $this->getContainer()->getParameter($ftpConfigKey);
      $config = $ftpConfig['csv_upload'];

      // 開発環境はパスワード決め打ち
      $config['password'] = $commonUtil->getSettingValue($ftpPasswordSettingKey, $targetEnv);

      // FTPアップロード ※空になるのを待つ(楽天Gold CSV処理出力時は待たない)
      $processor->uploadCsv($config, $filePath, $config['path'] . '/' . $remoteFileName, $isGold);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($execTitle, 'CSVファイルアップロード', '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('楽天 CSV出力アップロード処理を完了しました。');

      $commonUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_RAKUTEN_CSV_UPLOAD);

      return 0;

    } catch (JobException $e) {
      $logger->error(TbProcessExecuteLog::QUEUE_NAME_RAKUTEN_CSV_UPLOAD . 'Job・' . $execTitle . 'でエラーが発生しました：' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage(), TbProcessExecuteLog::QUEUE_NAME_RAKUTEN_CSV_UPLOAD);
    }

    return 0;
  }
}
