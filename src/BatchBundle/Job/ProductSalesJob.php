<?php
/**
 * 商品売上実績 Job
 */

namespace BatchBundle\Job;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\DbCommonUtil;

/**
 * Class ProductSalesJob
 * @package BatchBundle\Job
 */
class ProductSalesJob extends BaseJob
{
  /** 商品売上担当者集計予約テーブル、最大更新対象代表商品コード数 */
  const MAX_UPDATE_TARGET_CODE_COUNT_IN_RESERVATION_TABLE = 300;

  public function run($args)
  {
    try {
      $logger = $this->getLogger();

      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');

      // ここでバッチ処理 種類判定
      switch ($this->getCommand()) {

        // 商品売上実績集計
        case self::COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_PRODUCT_SALES);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('order_date_from'))) {
            $commandArgs[] = sprintf('--order_date_from=%s', $this->getArgv('order_date_from'));
          }
          if (!is_null($this->getArgv('order_date_to'))) {
            $commandArgs[] = sprintf('--order_date_to=%s', $this->getArgv('order_date_to'));
          }
          if (!is_null($this->getArgv('daihyo_syohin_code'))) {
            $commandArgs[] = sprintf('--daihyo_syohin_code=%s', $this->getArgv('daihyo_syohin_code'));
          }
          if (!is_null($this->getArgv('ne_updated_from'))) {
            $commandArgs[] = sprintf('--ne_updated_from=%s', $this->getArgv('ne_updated_from'));
          }
          if (!is_null($this->getArgv('is_reservation'))) {
            $commandArgs[] = sprintf('--is_reservation=%d', $this->getArgv('is_reservation'));
          }
          if ($this->queue) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }

          $logger->info('productSales job: aggregate_product_sales_account_result_history: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();
          $command = $this->getContainer()->get('batch.aggregate_product_sales_account_result_history');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_PRODUCT_SALES);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_PRODUCT_SALES);
          }
          $this->runningJobName = null;
          break;

        default:
          break;
      }
      return 0;

    } catch (JobException $e) {
      $logger = $this->getLogger();
      $logger->error('ProductSalesJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage(), TbProcessExecuteLog::QUEUE_NAME_PRODUCT_SALES);
    }

    return 0;
  }
}
