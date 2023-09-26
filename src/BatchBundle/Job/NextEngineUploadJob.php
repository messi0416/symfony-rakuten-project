<?php
/**
 * NextEngine アップロード処理 キュー対応ジョブ
 * 専用キューのため、排他チェックは不要
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Job;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\DbCommonUtil;


/**
 * Class NextEngineUploadJob
 * @package BatchBundle\Job
 */
class NextEngineUploadJob extends BaseJob
{
  public function run($args)
  {
    try {
      $logger = $this->getLogger();

      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');

      // ここでバッチ処理 種類判定
      switch ($this->getCommand()) {

        // 'NextEngine商品マスタ一括登録CSVアップロード'
        case self::COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS:

          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , $this->getArgv('dataDir') // data-dir
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }
          if (!is_null($this->getArgv('file'))) {
            $commandArgs[] = sprintf('--file=%s', $this->getArgv('file'));
          }

          $logger->info('neUpload job: upload_products_and_reservations: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_next_engine_upload');
          $exitCode = $command->run($input, $output);


          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);
          }
          $this->runningJobName = null;

          break;

        // 'NextEngineモール商品CSVアップロード'
        case self::COMMAND_KEY_NE_UPLOAD_MALL_PRODUCTS:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('shop'))) {
            $commandArgs[] = sprintf('--shop=%s', $this->getArgv('shop'));
          }
          if (!is_null($this->getArgv('filePath'))) {
            $commandArgs[] = sprintf('--file-path=%s', $this->getArgv('filePath'));
          }
          if (!is_null($this->getArgv('fileType'))) {
            $commandArgs[] = sprintf('--file-type=%s', $this->getArgv('fileType'));
          }
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }
          
          $logger->info('neUpload job: upload_mall_products: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_next_engine_upload_mall_product');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);
          }
          $this->runningJobName = null;

          break;

        // 'NextEngineセット商品マスターCSVアップロード'
        case self::COMMAND_KEY_NE_UPLOAD_SET_PRODUCTS:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }
          if (!is_null($this->getArgv('filePath'))) {
            $commandArgs[] = sprintf('--file-path=%s', $this->getArgv('filePath'));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }

          $logger->info('neUpload job: upload_set_products: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_next_engine_upload_set_product');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD);
          }
          $this->runningJobName = null;

          break;

        default:
          break;
      }
      return 0;

    } catch (JobException $e) {
      $logger->error('NextEngineUploadJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage());
    }

    return 0;
  }
}
