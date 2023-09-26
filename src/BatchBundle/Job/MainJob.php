<?php
/**
 * メインプロセス 起動管理ジョブ
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Job;

use BatchBundle\Job\ProductSalesJob;
use BatchBundle\MallProcess\AmazonMallProcess;
use BatchBundle\MallProcess\RakutenMallProcess;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\ProcessLockWaitException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;


class MainJob extends BaseJob
{
  public function run($args)
  {
    try {
      $logger = $this->getLogger();
      $logger->info('[main job] kicked.');
      $logger->info(print_r($this->args, true));

      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');

      // 排他制御判定
      // Job実処理フローの外側で判定し、処理中は途中終了を避けるためチェックしない
      $lockWaitLogger = $this->getContainer()->get('misc.util.file_logger')->setFileName('lock_process');
      $lockWaitInterval = 5; // 5秒間隔でロック取得試行
      $lockWaitLimit = (new \DateTime())->modify('+1 hour'); // ロック待ち最大1時間

      // バッチ処理 分岐
      switch ($this->getCommand()) {
        // 在庫取込
        case self::COMMAND_KEY_IMPORT_STOCK_LIST:
          $this->processImportStock($lockWaitLogger, $lockWaitInterval, $lockWaitLimit);
          break;

        // 受注明細取込
        case self::COMMAND_KEY_IMPORT_ORDER_LIST:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          // 実行期限時刻
          if (!is_null($this->getArgv('limitTime')) && preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})$/', $this->getArgv('limitTime'), $m)) {
            $now = new \DateTime();
            $limitTime = new \DateTime();
            $limitTime->setTime($m[1], $m[2], $m[3]);
            if ($now > $limitTime) {
              $this->exitError(100, sprintf('%s が実行期限時刻(%s : %s)超過でエラー終了', $this->runningJobName, $limitTime->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')));
            }
          }

          // 取得範囲指定
          $startDate = (new \DateTime())->modify('-3 month')->format('Y-m-01'); // デフォルト
          $endDate = (new \DateTime())->format('Y-m-d'); // デフォルト
          // 開始
          if (isset($this->args) && isset($this->args['startDate'])) {
            $startDate = (new \DateTime($this->args['startDate']))->format('Y-m-d');
          }
          // 終了
          if (isset($this->args) && isset($this->args['endDate'])) {
            $endDate = (new \DateTime($this->args['endDate']))->format('Y-m-d');
          }

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--start-date=%s', $startDate)
            , sprintf('--end-date=%s', $endDate)
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }

          $logger->info('main job: import_order_list: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.csv_download_order_command');
          $exitCode = $command->run($input, $output);

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 受注明細差分更新
        case self::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('mode'))) {
            $commandArgs[] = sprintf('--mode=%s', $this->getArgv('mode'));
          }
          if (!is_null($this->getArgv('number'))) {
            $commandArgs[] = sprintf('--number=%d', $this->getArgv('number'));
          }

          $logger->info('main job: import_order_list_incremental: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_db_by_order_list_next_engine_api');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName); // 受注明細取込
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 入出庫データ取込
        case self::COMMAND_KEY_IMPORT_STOCK_IN_OUT:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_stock_in_out');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 閲覧ランキング取込
        case self::COMMAND_KEY_IMPORT_VIEW_RANKING:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_view_ranking');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 楽天レビュー取込
        case self::COMMAND_KEY_IMPORT_RAKUTEN_REVIEW:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_rakuten_review');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // Amazon在庫取込
        case self::COMMAND_KEY_IMPORT_AMAZON_STOCK:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: import_amazon_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.import_amazon_stock');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // Amazon在庫取込
        case self::COMMAND_KEY_IMPORT_YABUYOSHI_STOCK:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: import_yabuyoshi_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.import_yabuyoshi_stock');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // Amazon在庫取込
        case self::COMMAND_KEY_IMPORT_RSL_STOCK:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: import_rsl_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.import_rsl_stock');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // Amazon在庫取込
        case self::COMMAND_KEY_IMPORT_SHOPLIST_STOCK:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: import_shoplist_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.import_shoplist_stock');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // ロケーション更新
        case self::COMMAND_KEY_REFRESH_LOCATION:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.refresh_location');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // ロケーション 倉庫へ画面 在庫数更新
        case self::COMMAND_KEY_REFRESH_LOCATION_WAREHOUSE_TO_LIST:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.refresh_location_warehouse_to_list');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 納品書印刷待ち伝票一覧 再集計
        case self::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST:
          $this->runningJobName = $this->getCurrentCommandName();

          $jobRequest = $this->startJobRequest('納品書印刷待ち伝票一覧 再集計を開始しました。'); // JobRequest 開始

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if ($jobRequest) {
            $commandArgs[] = sprintf('--job-request=%s', $jobRequest->getJobKey());
          }
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('settingId'))) {
            $commandArgs[] = sprintf('--setting-id=%d', $this->getArgv('settingId'));
          }
          if (!is_null($this->getArgv('shippingDate'))) {
            $commandArgs[] = sprintf('--shipping-date=%s', $this->getArgv('shippingDate'));
          }
          if (!is_null($this->getArgv('pageItemNum'))) {
            $commandArgs[] = sprintf('--page-item-num=%d', $this->getArgv('pageItemNum'));
          }
          if (!is_null($this->getArgv('changeLocationOrder'))) {
            $commandArgs[] = sprintf('--change-location-order=%d', $this->getArgv('changeLocationOrder'));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }

          $logger->info('main job: refresh_delivery_statement_detail_product_num_list: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.refresh_delivery_statement_detail_product_num_list');
          $exitCode = $command->run($input, $output);
          $logger->info('Refresh delivery statement detail product num list Done. [' . $exitCode . ']');

          $this->finishJobRequest($exitCode, "納品書印刷待ち伝票一覧 再集計を完了しました。"); // JobRequest 終了

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;
        // Queue export order list to excel
        case self::COMMAND_EXPORT_ORDER_LIST_TO_EXCEL:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $jobRequest = $this->startJobRequest('Start run queue export order list to excel'); // JobRequest 開始

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if ($jobRequest) {
            $commandArgs[] = sprintf('--job-request=%s', $jobRequest->getJobKey());
          }
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('agentId'))) {
            $commandArgs[] = sprintf('--agent-id=%d', $this->getArgv('agentId'));
          }
          if (!is_null($this->getArgv('conditions'))) {
            $commandArgs[] = sprintf('--conditions=%s', $this->getArgv('conditions'));
          }
          if (!is_null($this->getArgv('isForestStaff'))) {
            $commandArgs[] = sprintf('--isForestStaff=%d', $this->getArgv('isForestStaff'));
          }
          if (!is_null($this->getArgv('isClient'))) {
            $commandArgs[] = sprintf('--isClient=%d', $this->getArgv('isClient'));
          }
          if (!is_null($this->getArgv('isYahooAgent'))) {
            $commandArgs[] = sprintf('--isYahooAgent=%d', $this->getArgv('isYahooAgent'));
          }

          $logger->info('main job: export_order_list_to_excel: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_order_list_to_excel');
          $exitCode = $command->run($input, $output);
          $logger->info('Export order list to excel is Done. [' . $exitCode . ']');
          $this->finishJobRequest($exitCode, "Export order list to excel is Done."); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 在庫移動一覧 更新処理
        case self::COMMAND_KEY_REFRESH_WAREHOUSE_STOCK_MOVE_LIST:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('skipRefreshStatementDetail'))) {
            $commandArgs[] = sprintf('--skip-refresh-statement-detail-list=%d', $this->getArgv('skipRefreshStatementDetail'));
          }
          if (!is_null($this->getArgv('force'))) {
            $commandArgs[] = sprintf('--force=%d', $this->getArgv('force'));
          }
          if (!is_null($this->getArgv('targetWarehouseId'))) {
            $commandArgs[] = sprintf('--target-warehouse-id=%d', $this->getArgv('targetWarehouseId'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.refresh_warehouse_stock_move_list');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 商品ロケーション並べ替え
        case self::COMMAND_KEY_PRODUCT_LOCATION_SORT_ORDER:

          // 2018/02/27 #33338 ロケーション優先順位意味を持たせる方向にする（FIFOなど）方向のため、一旦停止。
          throw new \RuntimeException('商品ロケーション並べ替え処理は現状、停止中です。');

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.product_location_sort_order');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 伝票毎利益再集計
        case self::COMMAND_KEY_AGGREGATE_SALES_DETAIL:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('startDate'))) {
            $commandArgs[] = sprintf('--start-date=%s', $this->getArgv('startDate'));
          }
          if (!is_null($this->getArgv('endDate'))) {
            $commandArgs[] = sprintf('--end-date=%s', $this->getArgv('endDate'));
          }
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $logger->info('get aggregate command!');
          $command = $this->getContainer()->get('batch.aggregate_sales_detail');
          $exitCode = $command->run($input, $output);

          if ($exitCode !== 0) {
            $this->exitError($exitCode, 'app/console がエラー終了');
          } else {
            $dbUtil->deleteRunningLog($this->runningJobName);

            if (is_null($this->getArgv('aggregateProductSales')) || !$this->getArgv('aggregateProductSales')) {
              $this->runningJobName = null;
              break;
            }

            $this->runningJobName = '商品売上実績集計';
            $dbUtil->insertRunningLog($this->runningJobName);

            $rescue = $this->getContainer()->get('bcc_resque.resque');
            $job = new ProductSalesJob();
            $job->queue = 'productSales'; // キュー名
            
            // 伝票毎利益再集計が、
            // 期間指定有りなら、3カ月+今月分を固定して集計。
            // 期間指定無し(=全期間)なら、1年+今月分を集計。
            $orderDateFrom = (new \DateTime())->modify('-3 month')->format('Y-m-01');
            if (is_null($this->getArgv('startDate'))) {
              $orderDateFrom = (new \DateTime())->modify('-12 month')->format('Y-m-01');
            }
            $job->args = [
                'command' => self::COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY
              , 'order_date_from' => $orderDateFrom
              , 'order_date_to' => (new \DateTime())->format('Y-m-d')
            ];
            if (!is_null($this->getArgv('account'))) {
              $job->args['account'] = $this->getArgv('account');
            }
            $rescue->enqueue($job);

            $logger->info('商品売上実績集計 キュー追加');

            $exitCode = 0;
          }

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // NextEngine CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE:

          $this->processExportCsvNextEngine($lockWaitLogger, $lockWaitInterval, $lockWaitLimit);
          break;

          // NextEngine モール商品CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_MALL_PRODUCT:

          $this->processExportCsvNextEngineMallProduct($lockWaitLogger, $lockWaitInterval, $lockWaitLimit);
          break;

          // NextEngine セット商品CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_SET_PRODUCT:

          $this->processExportCsvNextEngineSetProduct($lockWaitLogger, $lockWaitInterval, $lockWaitLimit);
          break;

        // NextEngine在庫同期 CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK:

          $this->runningJobName = self::getCommandName(self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK);
          $logger = $this->getLogger();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil = $this->getContainer()->get('misc.util.db_common');
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--do-upload=%d', ($this->getArgv('doUpload') ? '1' : '0'))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }
          if (isset($this->args['doDownload'])) { // falseだとnullになるようなのでisset 画面からだと false = nul 一方cronからは設定なし
            $commandArgs[] = sprintf('--do-download=%d', ($this->getArgv('doDownload') ? '1' : '0'));
          }

          $logger->info('main job: export_csv_next_engine_update_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_next_engine_update_stock');
          $exitCode = $command->run($input, $output);
          $logger->info('NextEngine Update Stock Done. [' . $exitCode . ']');

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          return $exitCode;

          break;

        // ヤフーCSV出力
        case self::COMMAND_KEY_EXPORT_CSV_YAHOO:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportParentPath = $this->createYahooExportDirectory();

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , $exportParentPath // 出力先親ディレクトリ
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $targets = $this->getArgv('exportTarget');
          $exportTarget = [];
          if (!is_null($targets)) {
            if (!is_array($targets)) {
              $targets = [ $targets ];
            }
            foreach($targets as $target) {
              $exportTarget[] = trim($target);
            }
            $commandArgs[] = sprintf('--export-target=%s', implode(',', $exportTarget));
          }
          // ダウンロード済みCSVファイル
          if (!is_null($this->getArgv('importPath'))) {
            $commandArgs[] = sprintf('--import-path=%s', $this->getArgv('importPath'));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }

          $logger->info('main job: export_csv_yahoo: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_yahoo');
          $exitCode = $command->run($input, $output);
          $logger->info('Yahoo CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          if ($this->getArgv('doUpload')) {
            // 画像チェック および CSV・画像 FTPアップロード処理 キュー予約
            $rescue = $this->getContainer()->get('bcc_resque.resque');

            foreach($exportTarget as $target) {
              $job = new NonExclusiveJob();
              $job->queue = 'nonExclusive'; // キュー名
              $job->args = [
                  'command' => self::COMMAND_KEY_YAHOO_IMAGE_CHECK_AND_UPLOAD
                , 'exportParentPath' => $exportParentPath
                , 'doUpload'         => $this->getArgv('doUpload')
                , 'uploadTarget'     => $target
              ];
              if (!is_null($this->getArgv('account'))) {
                $job->args['account'] = $this->getArgv('account');
              }

              $rescue->enqueue($job);
            }

            $logger->info('Yahoo画像チェックおよびCSVアップロード キュー追加');
          }
          break;

        // FBAマルチチャネル 移動伝票作成処理
        case self::COMMAND_KEY_CREATE_AMAZON_FBA_MULTI_CHANNEL_TRANSPORT_LIST:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('updateStock'))) {
            $commandArgs[] = sprintf('--update-stock=%d', $this->getArgv('updateStock'));
          }
          if (!is_null($this->getArgv('updateFbaStock'))) {
            $commandArgs[] = sprintf('--update-fba-stock=%d', $this->getArgv('updateFbaStock'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.create_amazon_fba_multi_channel_transport_list');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;


        // ヤフー（おとりよせ）CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportParentPath = $this->createYahooExportDirectory();

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , $exportParentPath // 出力先親ディレクトリ
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          // 出力対象
          $exportTarget = $this->getArgv('exportTarget');
          $commandArgs[] = sprintf('--export-target=%s', $exportTarget);

          // ダウンロード済みCSVファイル
          if (!is_null($this->getArgv('importPath'))) {
            $commandArgs[] = sprintf('--import-path=%s', $this->getArgv('importPath'));
          }

          // 共通処理をスキップ
          if (!is_null($this->getArgv('skipCommonProcess'))) {
            $commandArgs[] = sprintf('--skip-common-process=%d', ($this->getArgv('skipCommonProcess') ? 1 : 0));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }

          $logger->info('main job: export_csv_yahoo_otoriyose: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_yahoo_otoriyose');
          $exitCode = $command->run($input, $output);
          $logger->info('Yahoo CSV (otoriyose) Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          if ($this->getArgv('doUpload')) {

            // 画像チェック および CSV・画像 FTPアップロード処理 キュー予約
            $rescue = $this->getContainer()->get('bcc_resque.resque');

            $job = new NonExclusiveJob();
            $job->queue = 'nonExclusive'; // キュー名
            $job->args = [
                'command' => self::COMMAND_KEY_YAHOO_IMAGE_CHECK_AND_UPLOAD
              , 'exportParentPath' => $exportParentPath
              , 'doUpload'         => $this->getArgv('doUpload')
              , 'uploadTarget'     => $exportTarget
            ];
            if (!is_null($this->getArgv('account'))) {
              $job->args['account'] = $this->getArgv('account');
            }

            $rescue->enqueue($job);

            $logger->info('Yahoo画像チェックおよびCSVアップロード キュー追加');
          }
          break;

        // ヤフー在庫更新CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_YAHOO_UPDATE_STOCK:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportTarget = $this->getArgv('exportTarget');

          $exportParentPath = $this->createYahooExportDirectory();
          $exportPath = $exportParentPath . '/' . $exportTarget;

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--export-target=%s', $exportTarget) // 出力対象
            , sprintf('--export-dir=%s', $exportPath) // 出力先ディレクトリ
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          // ダウンロード済みCSVファイル
          if (!is_null($this->getArgv('importPath'))) {
            $commandArgs[] = sprintf('--import-path=%s', $this->getArgv('importPath'));
          }
          // 予約在庫を含めるか（おとりよせ.com）
          if (!is_null($this->getArgv('includeReservedStock'))) {
            $commandArgs[] = sprintf('--include-reserved-stock=%d', $this->getArgv('includeReservedStock') ? 1 : 0);
          }

          $logger->info('main job: export_csv_yahoo_update_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_yahoo_update_stock');
          $exitCode = $command->run($input, $output);
          $logger->info('Yahoo CSV Update Stock Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          if ($this->getArgv('doUpload')) {

            // 出力がなければディレクトリが削除される
            if (!file_exists($exportPath)) {
              $logger->info('在庫更新CSVが出力されませんでした。アップロードの予約は行いません。');
              throw new JobExitException('Yahoo 在庫更新CSVが出力されませんでした。アップロードの予約は行いません。');
            }

            // 画像チェック および CSV・画像 FTPアップロード処理 キュー予約
            $rescue = $this->getContainer()->get('bcc_resque.resque');

            $job = new NonExclusiveJob();
            $job->queue = 'nonExclusive'; // キュー名
            $job->args = [
                'command' => self::COMMAND_KEY_YAHOO_IMAGE_CHECK_AND_UPLOAD
              , 'exportParentPath' => $exportParentPath
              , 'doUpload'         => $this->getArgv('doUpload')
              , 'uploadTarget'     => $exportTarget
            ];
            if (!is_null($this->getArgv('account'))) {
              $job->args['account'] = $this->getArgv('account');
            }

            $rescue->enqueue($job);

            $logger->info('Yahoo画像チェックおよびCSVアップロード キュー追加');
          }
          break;

        // Amazon CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_AMAZON:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportTarget = $this->getArgv('exportTarget');
          $exitCode = 1; // どこも処理を通らなければ引数エラー

          /** @var \MiscBundle\Util\FileUtil $fileUtil */
          $fileUtil = $this->getContainer()->get('misc.util.file');

          // 出力ディレクトリ（同じディレクトリに delete, update, sotck を出力）
          $now = new \DateTimeImmutable();
          $exportDir = sprintf('%s/Amazon/Export/%s', $fileUtil->getWebCsvDir(), $now->format('YmdHis'));

          // 商品データ更新処理 実行
          if ($exportTarget == 'all' || $exportTarget == 'diffAndStocks') {

            $commandArgs = [
                'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
              , sprintf('--export-dir=%s', $exportDir)
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }
            switch ($exportTarget) {
              case 'all':
                $commandArgs[] = sprintf('--diff-only=0');
                break;
              case 'diffAndStocks':
                $commandArgs[] = sprintf('--diff-only=1');
                break;
            }

            $logger->info('main job: export_csv_amazon: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.export_csv_amazon');
            $exitCode = $command->run($input, $output);
            $logger->info('Amazon CSV Export Done. [' . $exitCode . ']');
          }

          // 引き続き、価格・在庫更新CSV出力
          if ($exportTarget == 'diffAndStocks' || $exportTarget == 'stocks') {

            // Amazon在庫取込
            $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }
            $logger->info('main job: import_amazon_stock: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.import_amazon_stock');
            $exitCode = $command->run($input, $output);
            $logger->info('Amazon CSV (Update Stock) Export Done. [' . $exitCode . ']');

            if ($exitCode !== 0) { // コマンドが異常終了した
              $this->exitError($exitCode, 'app/console がエラー終了');
            }

            // 在庫CSV出力
            $commandArgs = [
                'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
              , sprintf('--export-dir=%s', $exportDir)
              , sprintf('--shop=%s', AmazonMallProcess::SHOP_NAME_VOGUE)
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }

            $logger->info('main job: export_csv_amazon_update_stock: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.export_csv_amazon_update_stock');
            $exitCode = $command->run($input, $output);
            $logger->info('Amazon CSV (Update Stock) Export Done. [' . $exitCode . ']');

          }

          // 排他制御はここまで。
          $dbUtil->deleteRunningLog($this->runningJobName);

          // アップロード処理
          if ( $this->getArgv('doUpload')) {
            $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
              , sprintf('--export-dir=%s', $exportDir)
              , sprintf('--shop=%s', AmazonMallProcess::SHOP_NAME_VOGUE)
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }

            $logger->info('main job: export_csv_amazon_upload: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.export_csv_amazon_upload');
            $exitCode = $command->run($input, $output);
            $logger->info('Amazon CSV Upload Done. [' . $exitCode . ']');
          }

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;


        // 2018/01/29 Amazon.com 販売休止
//        // Amazon.com CSV出力
//        case self::COMMAND_KEY_EXPORT_CSV_AMAZON_COM:
//          $this->runningJobName = $this->getCurrentCommandName();
//
//          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
//          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);
//
//          $exportTarget = $this->getArgv('exportTarget');
//          $exitCode = 1; // どこも処理を通らなければ引数エラー
//
//          /** @var \MiscBundle\Util\FileUtil $fileUtil */
//          $fileUtil = $this->getContainer()->get('misc.util.file');
//
//          // 出力ディレクトリ（同じディレクトリに delete, update, sotck を出力）
//          $now = new \DateTimeImmutable();
//          $exportDir = sprintf('%s/AmazonCom/Export/%s', $fileUtil->getWebCsvDir(), $now->format('YmdHis'));
//
//          // 商品データ更新処理 実行
//          if ($exportTarget == 'all' || $exportTarget == 'diffAndStocks') {
//
//            $commandArgs = [
//              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
//              , sprintf('--export-dir=%s', $exportDir)
//            ];
//            if (!is_null($this->getArgv('account'))) {
//              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
//            }
//            switch ($exportTarget) {
//              case 'all':
//                $commandArgs[] = sprintf('--diff-only=0');
//                break;
//              case 'diffAndStocks':
//                $commandArgs[] = sprintf('--diff-only=1');
//                break;
//            }
//
//            $logger->info('main job: export_csv_amazon_com: ' . print_r($commandArgs, true));
//            $input = new ArgvInput($commandArgs);
//            $output = new ConsoleOutput();
//
//            $command = $this->getContainer()->get('batch.export_csv_amazon_com');
//            $exitCode = $command->run($input, $output);
//            $logger->info('Amazon.com CSV Export Done. [' . $exitCode . ']');
//          }
////
//          // 引き続き、価格・在庫更新CSV出力
//          if ($exportTarget == 'diffAndStocks' || $exportTarget == 'stocks') {
//
//            $commandArgs = [
//              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
//              , sprintf('--export-dir=%s', $exportDir)
//              , sprintf('--shop=%s', AmazonMallProcess::SHOP_NAME_US_PLUSNAO)
//            ];
//            if (!is_null($this->getArgv('account'))) {
//              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
//            }
//
//            $logger->info('main job: export_csv_amazon_update_stock: ' . print_r($commandArgs, true));
//            $input = new ArgvInput($commandArgs);
//            $output = new ConsoleOutput();
//
//            $command = $this->getContainer()->get('batch.export_csv_amazon_update_stock');
//            $exitCode = $command->run($input, $output);
//            $logger->info('Amazon CSV (Update Stock) Export Done. [' . $exitCode . ']');
//
//          }
//
//          // 排他制御はここまで。
//          $dbUtil->deleteRunningLog($this->runningJobName);
//          $this->runningJobName = null;
//
//          // アップロード処理
//          if ( $this->getArgv('doUpload')) {
//            $commandArgs = [
//                'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
//              , sprintf('--export-dir=%s', $exportDir)
//              , sprintf('--shop=%s', AmazonMallProcess::SHOP_NAME_US_PLUSNAO)
//            ];
//            if (!is_null($this->getArgv('account'))) {
//              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
//            }
//
//            $logger->info('main job: export_csv_amazon_upload: ' . print_r($commandArgs, true));
//            $input = new ArgvInput($commandArgs);
//            $output = new ConsoleOutput();
//
//            $command = $this->getContainer()->get('batch.export_csv_amazon_upload');
//            $exitCode = $command->run($input, $output);
//            $logger->info('Amazon CSV Upload Done. [' . $exitCode . ']');
//          }
//
//          if ($exitCode !== 0) { // コマンドが異常終了した
//            $this->exitError($exitCode, 'app/console がエラー終了');
//          }
//
//          break;

        // SHOPLIST CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_SHOPLIST:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportTarget = $this->getArgv('exportTarget');
          /** @noinspection PhpUnusedLocalVariableInspection */
          $exitCode = 1; // どこも処理を通らなければ引数エラー

          /** @var \MiscBundle\Util\FileUtil $fileUtil */
          $fileUtil = $this->getContainer()->get('misc.util.file');

          // 出力ディレクトリ（同じディレクトリに delete, update, sotck を出力）
          $now = new \DateTimeImmutable();
          $exportDir = sprintf('%s/Shoplist/Export/%s', $fileUtil->getWebCsvDir(), $now->format('YmdHis'));

          // 差分確認テーブル 更新処理 実行
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('main job: update_shoplist_product_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_shoplist_product_stock');
          $exitCode = $command->run($input, $output);
          $logger->info('SHOPLIST UPDATE PRODUCT STOCK Done. [' . $exitCode . ']');

          // 商品データ更新処理 実行
          if ($exportTarget == 'diffAndStocks') {

            $commandArgs = [
                'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
              , sprintf('--export-dir=%s', $exportDir)
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }

            $logger->info('main job: export_csv_shoplist: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.export_csv_shoplist');
            $exitCode = $command->run($input, $output);
            $logger->info('SHOPLIST CSV Export Done. [' . $exitCode . ']');

            if ($exitCode !== 0) { // コマンドが異常終了した
              $this->exitError($exitCode, 'app/console がエラー終了');
            }
          }

          // 引き続き、在庫更新CSV出力
          if ($exportTarget == 'diffAndStocks' || $exportTarget == 'stocks') {

            $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
              , sprintf('--export-dir=%s', $exportDir)
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }

            $logger->info('main job: export_csv_shoplist_update_stock: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.export_csv_shoplist_update_stock');
            $exitCode = $command->run($input, $output);
            $logger->info('SHOPLIST CSV (Update Stock) Export Done. [' . $exitCode . ']');

            if ($exitCode !== 0) { // コマンドが異常終了した
              $this->exitError($exitCode, 'app/console がエラー終了');
            }
          }

          // アップロード処理
          if ( $this->getArgv('doUpload')) {

            // CSV・画像 FTPアップロード処理 キュー予約
            $rescue = $this->getContainer()->get('bcc_resque.resque');

            $job = new NonExclusiveJob();
            $job->queue = 'nonExclusive'; // キュー名
            $job->args = [
                'command' => self::COMMAND_KEY_EXPORT_CSV_SHOPLIST_UPLOAD
              , 'exportDir' => $exportDir
            ];
            if (!is_null($this->getArgv('account'))) {
              $job->args['account'] = $this->getArgv('account');
            }
            if ($this->getArgv('exportTarget')) {
              $job->args['exportTarget'] = $this->getArgv('exportTarget');
            }

            $rescue->enqueue($job);

            $logger->info('SHOPLIST CSV・画像アップロード キュー追加');
          }

          // 排他制御はここまで。
          $dbUtil->deleteRunningLog($this->runningJobName);
          $this->runningJobName = null;

          break;
          
        // SHOPLISTスピード便出荷数集計処理
        case self::COMMAND_KEY_AGGREGATE_SHOPLIST_SPEEDBIN_DELIVERY:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);
          
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('targetId'))) {
            $commandArgs[] = sprintf('--target-id=%d', $this->getArgv('targetId'));
          }
          
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();
          
          $command = $this->getContainer()->get('batch.aggregate_shoplist_speedbin_delivery');
          $exitCode = $command->run($input, $output);
          
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          
          break;

        // 楽天CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_RAKUTEN:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportParentPath = $this->createRakutenExportDirectory($this->getArgv('targetShop'));

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , $exportParentPath // 出力先親ディレクトリ
            , sprintf('--do-upload=%d', ($this->getArgv('doUpload') ? '1' : '0'))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('importPath'))) {
            $commandArgs[] = sprintf('--import-dir=%s', $this->getArgv('importPath'));
          }
          if (!is_null($this->getArgv('targetShop'))) {
            $commandArgs[] = sprintf('--target-shop=%s', $this->getArgv('targetShop'));
          }
          if (!is_null($this->getArgv('skipCommonProcess'))) {
            $commandArgs[] = sprintf('--skip-common-process=%d', ($this->getArgv('skipCommonProcess') ? 1 : 0));
          }
          if (!is_null($this->getArgv('skipRakutencommonProcess'))) {
            $commandArgs[] = sprintf('--skip-rakutencommon-process=%d', ($this->getArgv('skipRakutencommonProcess') ? 1 : 0));
          }
          if (!is_null($this->getArgv('exportAll'))) {
            $commandArgs[] = sprintf('--export-all=%d', ($this->getArgv('exportAll') ? 1 : 0));
          }
          if (!is_null($this->getArgv('test'))) {
            $commandArgs[] = sprintf('--test=%s', $this->getArgv('test'));
          }
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }

          $logger->info('main job: export_csv_rakuten: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_rakuten');
          $exitCode = $command->run($input, $output);
          $logger->info('楽天 CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 楽天在庫更新CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPDATE_STOCK:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $exportParentPath = $this->createRakutenExportDirectory();

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , $exportParentPath // 出力先親ディレクトリ
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('main job: export_csv_rakuten_update_stock: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_rakuten_update_stock');
          $exitCode = $command->run($input, $output);
          $logger->info('楽天 在庫更新CSV Export Done. [' . $exitCode . ']');

          // アップロード処理
          if ( $this->getArgv('doUpload')) {

            // CSV・画像 FTPアップロード処理 キュー予約
            /** @var RakutenMallProcess $processor */
            $processor = $this->getContainer()->get('batch.mall_process.rakuten');

            $finder = new Finder();
            $finder->in($exportParentPath)->name('select.csv');
            /** @var SplFileInfo $file */
            foreach($finder->files() as $file){
              $processor->enqueueUploadCsv($file->getPathname(), 'select.csv', $processor->getEnvironment(), '楽天在庫更新CSV出力処理', $this->getArgv('account'));
            }

            $logger->info('楽天 CSVアップロード キュー追加');
          }

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 楽天RPP除外CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_RPP_EXCLUDE:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('main job: export_csv_rakuten_rpp_export: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_rakuten_rpp_exclude');
          $exitCode = $command->run($input, $output);
          $logger->info('楽天 RPP除外CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 楽天GOLD CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_GOLD:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('shop'))) {
            $commandArgs[] = sprintf('--shop=%s', $this->getArgv('shop'));
          }
          if (!is_null($this->getArgv('csv'))) {
            $commandArgs[] = sprintf('--csv=%s', $this->getArgv('csv'));
          }
          if (!is_null($this->getArgv('doUpload'))) {
            $commandArgs[] = sprintf('--do-upload=%d', $this->getArgv('doUpload'));
          }
          if (!is_null($this->getArgv('aggregateDate'))) {
            $commandArgs[] = sprintf('--aggregate-date=%d', $this->getArgv('aggregateDate'));
          }
          if (!is_null($this->getArgv('minReviewPoint'))) {
            $commandArgs[] = sprintf('--min-review-point=%s', $this->getArgv('minReviewPoint'));
          }
          if (!is_null($this->getArgv('maxPetitPrice'))) {
            $commandArgs[] = sprintf('--max-petit-price=%d', $this->getArgv('maxPetitPrice'));
          }
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('main job: export_csv_rakuten_gold: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_rakuten_gold');
          $exitCode = $command->run($input, $output);
          $logger->info('楽天GOLD CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // Wowma CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_WOWMA:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--do-upload-csv=%d', ($this->getArgv('doUploadCsv') ? '1' : '0'))
            , sprintf('--do-upload-image=%d', ($this->getArgv('doUploadImage') ? '1' : '0'))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          // 共通処理をスキップ
          if (!is_null($this->getArgv('skipCommonProcess'))) {
            $commandArgs[] = sprintf('--skip-common-process=%d', ($this->getArgv('skipCommonProcess') ? 1 : 0));
          }

          $logger->info('main job: export_csv_wowma: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_wowma');
          $exitCode = $command->run($input, $output);
          $logger->info('Wowma CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // Q10 CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_Q10:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--do-upload-csv=%d', ($this->getArgv('doUploadCsv') ? '1' : '0'))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          // 共通処理をスキップ
          if (!is_null($this->getArgv('skipCommonProcess'))) {
            $commandArgs[] = sprintf('--skip-common-process=%d', ($this->getArgv('skipCommonProcess') ? 1 : 0));
          }

          $logger->info('main job: export_csv_q10: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_q10');
          $exitCode = $command->run($input, $output);
          $logger->info('Q10 CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // PPM CSV出力
        case self::COMMAND_KEY_EXPORT_CSV_PPM:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--do-upload=%d', ($this->getArgv('doUpload') ? '1' : '0'))
            , sprintf('--import-dir=%s', $this->getArgv('importPath'))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('main job: export_csv_ppm: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_ppm');
          $exitCode = $command->run($input, $output);
          $logger->info('PPM CSV Export Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // モール受注CSV変換
        case self::COMMAND_KEY_CONVERT_MALL_ORDER_CSV_DATA:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          // 受注明細差分更新 実行
          if ($this->getArgv('updateOrder')) {
            $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }
            $logger->info('main job: import_order_list_incremental: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.update_db_by_order_list_next_engine_api');
            $exitCode = $command->run($input, $output);

            if ($exitCode !== 0) { // コマンドが異常終了した
              $this->exitError($exitCode, 'app/console がエラー終了');
            }
          }

          // モール受注CSV変換 実行
          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--do-upload=%d', ($this->getArgv('doUpload') ? 1 : 0))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('mallCode'))) {
            $commandArgs[] = sprintf('--mall-code=%s', $this->getArgv('mallCode'));
          }
          if (!is_null($this->getArgv('force'))) {
            $commandArgs[] = sprintf('--force=%s', $this->getArgv('force'));
          }

          $logger->info('main job: convert_mall_order_csv_data: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.convert_mall_order_csv_data');
          $exitCode = $command->run($input, $output);
          $logger->info('Convert Mall Order CSV Done. [' . $exitCode . ']');

          // 排他制御はここまで。
          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 値下確定
        case self::COMMAND_KEY_DISCOUNT_PROCESS:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.discount_process');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 商品別原価率更新
        case self::COMMAND_KEY_UPDATE_PRODUCT_COST_RATE_PROCESS:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_product_cost_rate_process');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 発注再計算
        case self::COMMAND_KEY_RECALCULATE_PURCHASE_ORDER:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $this->startJobRequest('処理を開始しました。'); // JobRequest 開始

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if ($this->getArgv('rateDay')) { // 発注点計算期間
            $commandArgs[] = sprintf('--rate-day=%s', $this->getArgv('rateDay'));
          }
          if ($this->getArgv('ratePoint')) { // 発注点倍率
            $commandArgs[] = sprintf('--rate-point=%s', $this->getArgv('ratePoint'));
          }
          if (!is_null($this->getArgv('filterProfit'))) { // 利益率フィルタ
            $commandArgs[] = sprintf('--filter-profit=%s', $this->getArgv('filterProfit'));
          }
          if (!is_null($this->getArgv('filterAccessTerm'))) { // 発注点アクセス数判定期間フィルタ（足切り）
            $commandArgs[] = sprintf('--filter-access-term=%d', $this->getArgv('filterAccessTerm'));
          }
          if (!is_null($this->getArgv('filterAccessPerson'))) { // 発注点アクセス数判定人数フィルタ（足切り）
            $commandArgs[] = sprintf('--filter-access-person=%d', $this->getArgv('filterAccessPerson'));
          }
          if (!is_null($this->getArgv('filterSeasonAccessTerm'))) { // 季節在庫アクセス数判定期間フィルタ（足切り）
            $commandArgs[] = sprintf('--filter-season-access-term=%d', $this->getArgv('filterSeasonAccessTerm'));
          }
          if (!is_null($this->getArgv('filterSeasonAccessPerson'))) { // 季節在庫アクセス数判定人数フィルタ（足切り）
            $commandArgs[] = sprintf('--filter-season-access-person=%d', $this->getArgv('filterSeasonAccessPerson'));
          }
          if (!is_null($this->getArgv('calcOrderPoint'))) { // 発注点有効無効 0:無効 1:有効
            $commandArgs[] = sprintf('--calc-order-point=%d', $this->getArgv('calcOrderPoint'));
          }
          if (!is_null($this->getArgv('settingSeasonOrderBase'))) { // 季節在庫定数基準値
            $commandArgs[] = sprintf('--setting-season-order-base=%d', $this->getArgv('settingSeasonOrderBase'));
          }
          if (!is_null($this->getArgv('settingContainerFrom'))) { // コンテナ発注計算期間From
            $commandArgs[] = sprintf('--setting-container-from=%s', $this->getArgv('settingContainerFrom'));
          }
          if (!is_null($this->getArgv('settingContainerTo'))) { // コンテナ発注計算期間To
            $commandArgs[] = sprintf('--setting-container-to=%s', $this->getArgv('settingContainerTo'));
          }
          if (!is_null($this->getArgv('settingContainerPoint'))) { // コンテナ発注計算倍率
            $commandArgs[] = sprintf('--setting-container-point=%s', $this->getArgv('settingContainerPoint'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.recalculate_purchase_order');
          $exitCode = $command->run($input, $output);

          $this->finishJobRequest($exitCode, "発注再計算を完了しました。"); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // SKU別送料設定自動設定
        case self::COMMAND_KEY_SKU_SHIPPINGDIVISION_AUTO_SETTING:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $this->startJobRequest('処理を開始しました。'); // JobRequest 開始

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('from_date'))) {
            $commandArgs[] = sprintf('--from_date=%d', $this->getArgv('from_date'));
          }
          if (!is_null($this->getArgv('to_date'))) {
            $commandArgs[] = sprintf('--to_date=%d', $this->getArgv('to_date'));
          }
          if (!is_null($this->getArgv('from_no'))) {
            $commandArgs[] = sprintf('--from_no=%d', $this->getArgv('from_no'));
          }
          if (!is_null($this->getArgv('to_no'))) {
            $commandArgs[] = sprintf('--to_no=%d', $this->getArgv('to_no'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.sku_shippingdivision_auto_setting');
          $exitCode = $command->run($input, $output);

          $this->finishJobRequest($exitCode, "SKU別送料設定自動設定を完了しました。"); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // SKU別送料設定の商品マスタ反映処理
        case self::COMMAND_KEY_SKU_SHIPPINGDIVISION_REFLECT_MAINPRODUCT:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $this->startJobRequest('処理を開始しました。'); // JobRequest 開始

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('from_date'))) {
            $commandArgs[] = sprintf('--from_date=%d', $this->getArgv('from_date'));
          }
          if (!is_null($this->getArgv('to_date'))) {
            $commandArgs[] = sprintf('--to_date=%d', $this->getArgv('to_date'));
          }
          if (!is_null($this->getArgv('daihyo_syohin_code'))) {
            $commandArgs[] = sprintf('--daihyo_syohin_code=%d', $this->getArgv('daihyo_syohin_code'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.sku_shippingdivision_reflect_mainproduct');
          $exitCode = $command->run($input, $output);

          $this->finishJobRequest($exitCode, "SKU別送料設定の商品マスタ反映を完了しました。"); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 代表商品サイズ更新
        case self::COMMAND_KEY_UPDATE_PRODUCT_SIZE:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $this->startJobRequest('処理を開始しました。'); // JobRequest 開始

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          if (!is_null($this->getArgv('from_date'))) {
            $commandArgs[] = sprintf('--from_date=%d', $this->getArgv('from_date'));
          }
          if (!is_null($this->getArgv('to_date'))) {
            $commandArgs[] = sprintf('--to_date=%d', $this->getArgv('to_date'));
          }
          if (!is_null($this->getArgv('daihyo_syohin_code'))) {
            $commandArgs[] = sprintf('--daihyo_syohin_code=%d', $this->getArgv('daihyo_syohin_code'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_product_size');
          $exitCode = $command->run($input, $output);

          $this->finishJobRequest($exitCode, "商品マスタサイズ更新処理を完了しました。"); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // SKUのサイズ変更に伴う更新処理
        case self::COMMAND_KEY_SKU_SIZE_CHANGE_RELATED_UPDATE:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $this->startJobRequest('処理を開始しました。'); // JobRequest 開始

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.sku_size_change_related_update');
          $exitCode = $command->run($input, $output);

          $this->finishJobRequest($exitCode, "SKUのサイズ変更に伴う更新処理を完了しました。"); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        /// 発送方法変更 受注明細差分更新＆変更反映
        case self::COMMAND_KEY_UPDATE_VOUCHER_CHANGE_SHIPPING_METHODS:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $logger->addDbLog(
              $logger->makeDbLog($this->runningJobName, $this->runningJobName, '開始')
            , true
            , '発送方法変更 受注明細差分更新＆変更反映を開始しました。'
            , 'info'
          );

          // 受注明細差分更新 実行
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: import_order_list_incremental: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_db_by_order_list_next_engine_api');
          $exitCode = $command->run($input, $output);

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }

          // 配送情報 補完処理
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: update_change_shipping_method_order: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_change_shipping_method_order');
          $exitCode = $command->run($input, $output);

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }

          $dbUtil->deleteRunningLog($this->runningJobName);
          $this->runningJobName = null;
          break;

        // 共通日次バッチ処理
        case self::COMMAND_KEY_DAILY_BATCH:
          $this->runningJobName = $this->getCurrentCommandName();
          $logger->info($this->runningJobName);

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          // 受注明細差分更新 実行
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $logger->info('main job: daily_batch: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.daily_batch');
          $exitCode = $command->run($input, $output);


          $logger->info($this->runningJobName);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 倉庫間箱移動バッチ処理
        case self::COMMAND_WAREHOUSE_BOX_MOVE:
            $this->runningJobName = $this->getCurrentCommandName();
            $logger->info($this->runningJobName);

            // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
            $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

            // 受注明細差分更新 実行
            $commandArgs = [
                'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            ];
            if (!is_null($this->getArgv('account'))) {
                $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }
            if (!is_null($this->getArgv('stocks'))) {
                $commandArgs[] = sprintf('--stocks=%d', $this->getArgv('stocks'));
            }
            if (!is_null($this->getArgv('order_date'))) {
                $commandArgs[] = sprintf('--order_date=%d', $this->getArgv('order_date'));
            }
            if (!is_null($this->getArgv('magnification_percent'))) {
                $commandArgs[] = sprintf('--magnification_percent=%d', $this->getArgv('magnification_percent'));
            }
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定

            $logger->info('main job: warehouse_box_move: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.warehouse_box_move');
            $exitCode = $command->run($input, $output);


            $logger->info($this->runningJobName);

            if ($exitCode === 0) {
              $dbUtil->deleteRunningLog($this->runningJobName);
            } else {  // コマンドが異常終了した
              $this->exitError($exitCode, 'app/console がエラー終了');
            }
            $this->runningJobName = null;
            break;
        // 発送方法一括変換
        case self::COMMAND_KEY_DELIVERY_METHOD_CONVERSION:

          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.order_method_change');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 倉庫実績集計処理
        case self::COMMAND_KEY_AGGREGATE_WAREHOUSE_RESULT_HISTORY:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('from_date'))) {
            $commandArgs[] = sprintf('--from_date=%d', $this->getArgv('from_date'));
          }
          if (!is_null($this->getArgv('to_date'))) {
            $commandArgs[] = sprintf('--to_date=%d', $this->getArgv('to_date'));
          }
          if (!is_null($this->getArgv('warehouse_id'))) {
            $commandArgs[] = sprintf('--warehouse_id=%d', $this->getArgv('warehouse_id'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.aggregate_warehouse_result_history');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 代表商品販売ステータス更新処理
        case self::COMMAND_KEY_UPDATE_PRODUCT_SALES_STATUS:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_product_sales_status');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        case self::COMMAND_KEY_UPDATE_SKU_COLOR:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_sku_color');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 商品売上担当者適用終了処理
        case self::COMMAND_KEY_UPDATE_PRODUCT_SALES_ACCOUNT_APPLY_END:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_product_sales_account_apply_end');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        // 楽天商品属性項目マスタ更新処理
        case self::COMMAND_KEY_UPDATE_RAKUTEN_GENRE_ATTRIBUTE:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_rakuten_genre_attribute');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // SKU別楽天商品属性項目更新処理
        case self::COMMAND_KEY_UPDATE_SKU_RAKUTEN_ATTRIBUTE:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_sku_rakuten_attribute');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // SHOPLISTスピード便移動伝票一括作成
        case self::COMMAND_KEY_CREATE_TRANSPORT_LIST_SHOPLIST_SPEED_BIN:
          $this->runningJobName = $this->getCurrentCommandName();

          $jobRequest = $this->startJobRequest('SHOPLISTスピード便移動伝票一括作成を開始しました。'); // JobRequest 開始
          if (!is_null($this->queue)) {
            $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定
          }
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('departure-date'))) {
            $commandArgs[] = sprintf('--departure-date=%s', $this->getArgv('departure-date'));
          }
          if (!is_null($this->getArgv('arrival-date'))) {
            $commandArgs[] = sprintf('--arrival-date=%s', $this->getArgv('arrival-date'));
          }
          if (!is_null($this->getArgv('shipping-method'))) {
            $commandArgs[] = sprintf('--shipping-method=%s', $this->getArgv('shipping-method'));
          }
          if (!is_null($this->getArgv('transport-number'))) {
            $commandArgs[] = sprintf('--transport-number=%s', $this->getArgv('transport-number'));
          }
          if (!is_null($this->getArgv('upload-filepath'))) {
            $commandArgs[] = sprintf('--upload-filepath=%s', $this->getArgv('upload-filepath'));
          }

          $logger->info('main job: create_transport_list_shoplist_speed_bin: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.create_transport_list_shoplist_speed_bin');
          $exitCode = $command->run($input, $output);
          $logger->info('Create transport list shoplist speed bin Done. [' . $exitCode . ']');

          $this->finishJobRequest($exitCode, "SHOPLISTスピード便移動伝票一括作成を完了しました。"); // JobRequest 終了

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 楽天SKU属性情報値取込処理
        case self::COMMAND_KEY_IMPORT_SKU_RAKUTEN_ATTRIBUTE_VALUE:
          $this->runningJobName = $this->getCurrentCommandName();
          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($this->getArgv('file-path'))) {
            $commandArgs[] = sprintf('--file-path=%s', $this->getArgv('file-path'));
          }

          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.import_rakuten_sku_attribute_value');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName);
          } else {  // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;
          break;

        // 最新在庫データ更新
        case self::COMMAND_KEY_UPDATE_STOCK_LIST:
          $this->runningJobName = $this->getCurrentCommandName();

          // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
          $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('targetEnv'))) {
            $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
          }

          $logger->info('main job: update_stock_list: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.update_db_by_stock_list_next_engine_api');
          $exitCode = $command->run($input, $output);

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName); // 受注明細取込
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          $this->runningJobName = null;

          break;

        default:
          break;
      }

      $this->getLogger()->info('コマンド成功');
      return 0;

    // 排他終了の場合には分岐（一定時間待って駄目だった場合にどうするかの仕様を検討する）
    } catch (ProcessLockWaitException $e) {
      $this->exitError(self::ERROR_CODE_GET_PROCESS_LOCK_FAIL);

    // 終了シグナル
    } catch (JobExitException $e) {
      $logger->error('MainJobで例外発生:' . $e->getTraceAsString());
      throw $e;

    // その他のエラー
    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage());
    }

    return 0;
  }

  /**
   * Yahoo 出力ディレクトリ 作成 （＆掃除）
   */
  private function createYahooExportDirectory()
  {
    /** @var \MiscBundle\Util\FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');

    // 過去データ・ディレクトリ削除
    $csvPath = sprintf('%s/Yahoo/Export', $fileUtil->getWebCsvDir());
    // 2日以上前のファイルはディレクトリ毎削除
    $ago = new \DateTime();
    $ago->modify('-2 day');
    $limitStr = $ago->format('YmdHis');

    $finder = new Finder();
    $dirs = $finder->in($csvPath)->directories();
    $removeDirs = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $dir */
    foreach ($dirs as $dir) {
      $dirTime = $dir->getBasename();
      if ($dirTime < $limitStr) {
        $removeDirs[] = $dir;
      }
    }

    $fs = new FileSystem();
    foreach ($removeDirs as $dir) { // 上のforeach の中でディレクトリを削除するとエラーになるため、別処理で削除。
      $fs->remove($dir);
    }

    // 出力親ディレクトリ
    $exportDateTime = new \DateTime();
    $exportParentPath = sprintf('%s/%s', $csvPath, $exportDateTime->format('YmdHis'));
    $fs->mkdir($exportParentPath);

    return $exportParentPath;
  }

  /**
   * 在庫ダウンロード Job処理
   * @param LoggerInterface $lockWaitLogger
   * @param $lockWaitInterval
   * @param $lockWaitLimit
   */
  private function processImportStock($lockWaitLogger, $lockWaitInterval, $lockWaitLimit)
  {
    // 在庫データ取込
    $this->runningJobName = self::getCommandName(self::COMMAND_KEY_IMPORT_STOCK_LIST);

    $logger = $this->getLogger();

    // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
    $dbUtil = $this->getContainer()->get('misc.util.db_common');
    $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

    // CSVダウンロード処理
    $fileName = sprintf('data%s.csv', date('YmdHis00000000'));

    $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      , sprintf('--output-file=%s', $fileName)
    ];
    if (!is_null($this->getArgv('account'))) {
      $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
    }
    if (!is_null($this->getArgv('targetEnv'))) {
      $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
    }
    $logger->info('main job: import_stock_list: ' . print_r($commandArgs, true));
    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.csv_download_stock_command');
    $exitCode = $command->run($input, $output);

    if ($exitCode !== 0) {
      throw new \RuntimeException('CSVダウンロードエラー.');
    }

    // 引き続き、DB更新（開発用実装）
    // → 本来は、これを別Jobとして別のキューで呼び出せば、排他でのリトライを独立して行うことも可能
    $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよい。
      , $fileName
    ];
    if ($this->account) {
      $commandArgs[] = sprintf('--account=%d', $this->account->getId());
    }
    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.update_db_by_stock_list_csv');
    $exitCode = $command->run($input, $output);
    if ($exitCode !== 0) { // コマンドが異常終了した
      throw new \RuntimeException('can not update db.');
    }

    if ($exitCode === 0) {
      $dbUtil->deleteRunningLog($this->runningJobName);
    } else {  // コマンドが異常終了した
      $this->exitError($exitCode, 'app/console がエラー終了');
    }
    $this->runningJobName = null;

    return $exitCode;
  }

  /**
   * NextEngine CSV出力
   * @param $lockWaitLogger
   * @param $lockWaitInterval
   * @param $lockWaitLimit
   * @return
   */
  private function processExportCsvNextEngine($lockWaitLogger, $lockWaitInterval, $lockWaitLimit)
  {
    $this->runningJobName = self::getCommandName(self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE);
    $logger = $this->getLogger();

    // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
    $dbUtil = $this->getContainer()->get('misc.util.db_common');
    $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

    $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      , sprintf('--do-upload=%d', ($this->getArgv('doUpload') ? '1' : '0'))
    ];
    if (!is_null($this->getArgv('ignorePriceDiff'))) {
      $commandArgs[] = sprintf('--ignore-price-diff=%d', $this->getArgv('ignorePriceDiff'));
    }
    if (!is_null($this->getArgv('account'))) {
      $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
    }
    if (!is_null($this->getArgv('targetEnv'))) {
      $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
    }
    if (isset($this->args['doDownload'])) { // falseだとnullになるようなのでisset 画面からだと false = nul 一方cronからは設定なし
      $commandArgs[] = sprintf('--do-download=%d', ($this->getArgv('doDownload') ? '1' : '0'));
    }

    $logger->info('main job: export_csv_next_engine: ' . print_r($commandArgs, true));
    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.export_csv_next_engine');
    $exitCode = $command->run($input, $output);
    $logger->info('NextEngine Done. [' . $exitCode . ']');

    if ($exitCode === 0) {
      $dbUtil->deleteRunningLog($this->runningJobName);
    } else {  // コマンドが異常終了した
      $this->exitError($exitCode, 'app/console がエラー終了');
    }
    $this->runningJobName = null;

    return $exitCode;
  }

  /**
   * NextEngine モール商品CSV出力
   * @param $lockWaitLogger
   * @param $lockWaitInterval
   * @param $lockWaitLimit
   * @return
   */
  private function processExportCsvNextEngineMallProduct($lockWaitLogger, $lockWaitInterval, $lockWaitLimit)
  {
    $this->runningJobName = self::getCommandName(self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_MALL_PRODUCT);
    $logger = $this->getLogger();

    // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
    $dbUtil = $this->getContainer()->get('misc.util.db_common');
    $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

    $commandArgs = [
      'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
    ];
    if (!is_null($this->getArgv('shop'))) {
      $commandArgs[] = sprintf('--shop=%s', $this->getArgv('shop'));
    }
    if (!is_null($this->getArgv('isOnlyDiff'))) {
      $commandArgs[] = sprintf('--is-only-diff=%s', $this->getArgv('isOnlyDiff'));
    }
    if (!is_null($this->getArgv('doUpload'))) {
      $commandArgs[] = sprintf('--do-upload=%d', $this->getArgv('doUpload'));
    }
    if (!is_null($this->getArgv('account'))) {
      $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
    }
    if (!is_null($this->getArgv('targetEnv'))) {
      $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
    }
    $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定

    $logger->info('main job: export_csv_next_engine_mall_product: ' . print_r($commandArgs, true));
    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.export_csv_next_engine_mall_product');
    $exitCode = $command->run($input, $output);
    $logger->info($this->runningJobName . ' Done. [' . $exitCode . ']');

    if ($exitCode === 0) {
      $dbUtil->deleteRunningLog($this->runningJobName);
    } else {  // コマンドが異常終了した
      $this->exitError($exitCode, 'app/console がエラー終了');
    }
    $this->runningJobName = null;

    return $exitCode;
  }

  /**
   * NextEngine セット商品CSV出力
   * @param $lockWaitLogger
   * @param $lockWaitInterval
   * @param $lockWaitLimit
   * @return
   */
  private function processExportCsvNextEngineSetProduct($lockWaitLogger, $lockWaitInterval, $lockWaitLimit)
  {
    $this->runningJobName = self::getCommandName(self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_SET_PRODUCT);
    $logger = $this->getLogger();

    // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
    $dbUtil = $this->getContainer()->get('misc.util.db_common');
    $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

    $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        , sprintf('--do-upload=%d', ($this->getArgv('doUpload') ? '1' : '0'))
    ];
    if (!is_null($this->getArgv('account'))) {
      $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
    }
    if (!is_null($this->getArgv('targetEnv'))) {
      $commandArgs[] = sprintf('--target-env=%s', $this->getArgv('targetEnv'));
    }
    $commandArgs[] = sprintf('--queue-name=%s', $this->queue); // キュー名を指定

    $logger->info('main job: export_csv_next_engine_set_product: ' . print_r($commandArgs, true));
    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.export_csv_next_engine_set_product');
    $exitCode = $command->run($input, $output);
    $logger->info('NextEngine Done. [' . $exitCode . ']');

    if ($exitCode === 0) {
      $dbUtil->deleteRunningLog($this->runningJobName);
    } else {  // コマンドが異常終了した
      $this->exitError($exitCode, 'app/console がエラー終了');
    }
    $this->runningJobName = null;

    return $exitCode;
  }

  /**
   * 楽天 出力ディレクトリ 作成 （＆掃除）
   *
   * $targetShopごとにディレクトリを分ける
   */
  private function createRakutenExportDirectory($targetShop = null)
  {
    /** @var \MiscBundle\Util\FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');

    // 過去データ・ディレクトリ削除
    $csvPath = sprintf('%s/Rakuten/Export', $fileUtil->getWebCsvDir());
    // 2日以上前のファイルはディレクトリ毎削除
    $ago = new \DateTime();
    $ago->modify('-2 day');
    $limitStr = $ago->format('YmdHis');

    $finder = new Finder();
    $dirs = $finder->in($csvPath)->directories();
    $removeDirs = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $dir */
    foreach ($dirs as $dir) {
      $dirTime = $dir->getBasename();
      if ($dirTime < $limitStr) {
        $removeDirs[] = $dir;
      }
    }

    $fs = new FileSystem();
    foreach ($removeDirs as $dir) { // 上のforeach の中でディレクトリを削除するとエラーになるため、別処理で削除。
      $fs->remove($dir);
    }

    // 出力親ディレクトリ
    $exportDateTime = new \DateTime();
    if ($targetShop) {
      $exportParentPath = sprintf('%s/%s/%s', $csvPath, $targetShop, $exportDateTime->format('YmdHis'));
    } else {
      $exportParentPath = sprintf('%s/%s', $csvPath, $exportDateTime->format('YmdHis'));
    }
    $fs->mkdir($exportParentPath);

    return $exportParentPath;
  }

}
