<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;


class DailyDbBackupCheckCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:daily-db-backup-check')
      ->setDescription('DBバックアップ日次処理 処理結果通知＆確認コマンド')
      ->addOption('mode', null, InputOption::VALUE_REQUIRED, '処理選択', null)
      ->addOption('path', null, InputOption::VALUE_REQUIRED, '保存ディレクトリ', null)
      ->addOption('filename', null, InputOption::VALUE_REQUIRED, '保存ファイル名ベース', null)
      ->addOption('last-log', null, InputOption::VALUE_OPTIONAL, '前回ログID')
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
     */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $lastLogId = '';
    if ($lastId = $input->getOption('last-log')) {
      $lastLog = $this->getContainer()->get('doctrine')
                    ->getRepository('MiscBundle:TbLog')
                    ->find($lastId);
      $logger->initLogTimer($lastLog);
    }

    $path = $input->getOption('path');
    $fileNameBase = $input->getOption('filename');

    $logExecTitle = 'バッチ:DBバックアップ';
    $logPcName = 'DBSV02:CRON';

    try {
      $mode = $input->getOption('mode');
      switch($mode) {
        case 'start':
          $lastLogId = $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始', '', '', $logPcName));
          $lastLogId = $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '日次バックアップ取得', '開始', '', $logPcName));
          break;
        case 'daily_end':

          // 日次処理 結果確認
          $targetPath = $path . '/' . $fileNameBase . '.gz';
          $targetPathLog = $path . '/' . $fileNameBase . '_log.gz';

          if ($this->isValidBackup($targetPath) && $this->isValidBackup($targetPathLog)) {
            // 成功
            $lastLogId = $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '日次バックアップ取得', '終了', '', $logPcName));
          } else {
            $log = $logger->makeDbLog($logExecTitle, $logExecTitle, '日次バックアップ取得', 'エラー終了', 'バックアップファイルが作成されていません。', $logPcName);
            $error = new DailyDbBackupCheckCommandException('日次バックアップに失敗しました。');
            $error->setErrorDbLog($log);
            throw $error;
          }
          break;

        case 'weekly_end':

          // 週次処理 結果確認
          $targetPath = $path . '/' . $fileNameBase . '.gz';
          $targetPathLog = $path . '/' . $fileNameBase . '_log.gz';

          if ($this->isValidBackup($targetPath) && $this->isValidBackup($targetPathLog)) {
            // 成功
            $lastLogId = $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '週次バックアップ取得', '完了', '', $logPcName));
          } else {
            $log = $logger->makeDbLog($logExecTitle, $logExecTitle, '週次バックアップ取得', 'エラー終了', 'バックアップファイルが作成されていません。', $logPcName);
            $error = new DailyDbBackupCheckCommandException('週次バックアップに失敗しました。');
            $error->setErrorDbLog($log);
            throw $error;
          }

          break;

        case 'monthly_end':

          // 月次処理 結果確認
          $targetPath = $path . '/' . $fileNameBase . '.gz';
          $targetPathLog = $path . '/' . $fileNameBase . '_log.gz';

          if ($this->isValidBackup($targetPath) && $this->isValidBackup($targetPathLog)) {
            // 成功
            $lastLogId = $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '月次バックアップ取得', '完了', '', $logPcName));
          } else {
            $log = $logger->makeDbLog($logExecTitle, $logExecTitle, '月次バックアップ取得', 'エラー終了', 'バックアップファイルが作成されていません。', $logPcName);
            $error = new DailyDbBackupCheckCommandException('月次バックアップに失敗しました。');
            $error->setErrorDbLog($log);
            throw $error;
          }

          break;

        case 'end':
          $lastLogId = $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了', '', '', $logPcName));
          break;

        default:
          throw new RuntimeException('no mode selected. aborted.');
      }

      $output->write($lastLogId);

      return 0;

    } catch (DailyDbBackupCheckCommandException $e) {
      $log = $e->getErrorDbLog();
      $logger->addDbLog($log, 1, $e->getMessage(), 'error');
      return 1;

    // 補足できないエラー
    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了', '', '', $logPcName)->setInformation($e->getMessage()), 1, $logExecTitle . 'に失敗しました。', 'error');
      return 1;
    }

  }
  
  
  private function isValidBackup($path)
  {
    return file_exists($path) && is_file($path) && is_readable($path) && filesize($path) > 0;
  }
  
}

class DailyDbBackupCheckCommandException extends RuntimeException
{
  /** @var  TbLog */
  private $errorLog;

  public function setErrorDbLog(TbLog $errorLog) {
    $this->errorLog = $errorLog;
  }
  public function getErrorDbLog()
  {
    return $this->errorLog;
  }

}
