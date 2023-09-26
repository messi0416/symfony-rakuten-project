<?php
/**
 * 商品画像アップロードキュー 起動管理ジョブ
 */

namespace BatchBundle\Job;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\DbCommonUtil;

class ProductImageUploadJob extends BaseJob
{

  public function run($args)
  {
    $logger = $this->getLogger();
    try {

      $logger->debug('product image upload job command: ' . $this->getCommand());

      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');

      // ここでバッチ処理 種類判定
      switch ($this->getCommand()) {

        // 商品画像FTPアップロード
        case self::COMMAND_KEY_PRODUCT_IMAGE_UPLOAD_FTP:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_PRODUCT_IMAGE);

          // CSVダウンロード
          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $logger->debug('Product Image Upload Ftp Start');
          $command = $this->getContainer()->get('batch.product_image_upload_ftp');
          $exitCode = $command->run($input, $output);
          $logger->debug('Product Image Upload Ftp Done. [' . $exitCode . ']');

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_PRODUCT_IMAGE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_PRODUCT_IMAGE);
          }
          $this->runningJobName = null;
          break;

        default:
          break;
      }
      return 0;
    } catch (JobException $e) {
      $logger->error('ProductImageUploadJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through
    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage(), TbProcessExecuteLog::QUEUE_NAME_PRODUCT_IMAGE);
    }
  }
}
