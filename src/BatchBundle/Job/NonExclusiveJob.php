<?php
/**
 * 非排他制御 Job
 * 画像チェック、Yahooアップロードなど。
 * 排他する必要はないが、同時実行を避けて順番待ちにするJobキュー
 */

namespace BatchBundle\Job;

use BatchBundle\Command\CsvDownloadYahooProductsCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\DbCommonUtil;


/**
 * Class NonExclusiveJob
 * @package BatchBundle\Job
 */
class NonExclusiveJob extends BaseJob
{
  public function run($args)
  {
    try {
      $logger = $this->getLogger();
      $logger->info('non exclusive job command: ' . $this->getCommand());

      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');

      // ここでバッチ処理 種類判定
      switch ($this->getCommand()) {

        // Yahoo CSVダウンロード処理
        // １店舗ずつしかダウンロードできなくなるがむしろ怒られにくいだろうからキューにしてしまう
        case self::COMMAND_KEY_DOWNLOAD_CSV_YAHOO:

          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

          // CSVダウンロード
          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--export-target=%s', $this->getArgv('exportTarget'))
            , sprintf('--enqueue-csv-export-type=%d', $this->getArgv('exportCsvType')) // 商品CSV出力 OR 在庫CSV出力
            , sprintf('--enqueue-csv-export-do-upload=%d', ($this->getArgv('doUpload') ? 1 : 0))
            , sprintf('--enqueue-csv-export-skip-common-process=%d', ($this->getArgv('skipCommonProcess') ? 1 : 0))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          // ダウンロード対象CSV 商品CSV出力ならば商品と在庫、在庫CSV出力ならば在庫のみ
          if ($this->getArgv('exportCsvType') == CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS) {
            $commandArgs[] = sprintf('--export-type=%d,%d'
                , CsvDownloadYahooProductsCommand::CSV_TYPE_PRODUCTS
                , CsvDownloadYahooProductsCommand::CSV_TYPE_STOCK);
          } else {
            $commandArgs[] = sprintf('--export-type=%d', CsvDownloadYahooProductsCommand::CSV_TYPE_STOCK);
          }
          
          $logger->info('nonExclusive job: csv_download_yahoo_products: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.csv_download_yahoo_products');
          $exitCode = $command->run($input, $output);
          $logger->info('Yahoo CSV Download Done. [' . $exitCode . ']');

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

//        // Yahoo CSVダウンロード処理（おとりよせ）
//        // １店舗ずつしかダウンロードできなくなるがむしろ怒られにくいだろうからキューにしてしまう
//        case self::COMMAND_KEY_DOWNLOAD_CSV_YAHOO_OTORIYOSE:
//
//          $this->runningJobName = $this->getCurrentCommandName();
//
//          // CSVダウンロード
//          $commandArgs = [
//              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
//            , sprintf('--export-target=%s', $this->getArgv('exportTarget'))
//            , sprintf('--enqueue-csv-export-type=%d', $this->getArgv('exportCsvType')) // 商品CSV出力 OR 在庫CSV出力
//            , sprintf('--enqueue-csv-export-do-upload=%d', ($this->getArgv('doUpload') ? 1 : 0))
//            , sprintf('--enqueue-csv-export-skip-common-process=%d', ($this->getArgv('skipCommonProcess') ? 1 : 0))
//          ];
//          if (!is_null($this->getArgv('account'))) {
//            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
//          }
//
//          $logger->info('nonExclusive job: csv_download_yahoo_products: ' . print_r($commandArgs, true));
//          $input = new ArgvInput($commandArgs);
//          $output = new ConsoleOutput();
//
//          $command = $this->getContainer()->get('batch.csv_download_yahoo_products');
//          $exitCode = $command->run($input, $output);
//          $logger->info('Yahoo CSV Download Done. [' . $exitCode . ']');
//
//          $this->runningJobName = null;
//          if ($exitCode !== 0) { // コマンドが異常終了した
//            $this->exitError($exitCode, 'app/console がエラー終了');
//          }
//          break;

        // Yahoo画像チェックおよびCSV・画像FTPアップロード処理
        case self::COMMAND_KEY_YAHOO_IMAGE_CHECK_AND_UPLOAD:

          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

          $exportParentPath = $this->getArgv('exportParentPath');

          /* 画像の定期自動アップロードが稼働しているので、この処理はスキップする。
          // 画像チェック
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , $exportParentPath // 出力先親ディレクトリ
            , sprintf('--do-check-all-images=%d', ($this->getArgv('doCheckAllImages') ? '1' : '0'))
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          $targets = $this->getArgv('exportTarget');
          if (!is_null($targets) && is_array($targets)) {
            $exportTarget = [];
            foreach($targets as $target) {
              $exportTarget[] = trim($target);
            }
            $commandArgs[] = sprintf('--export-target=%s', implode(',', $exportTarget));
          }

          $logger->info('nonExclusive job: image_check_yahoo: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.image_check_yahoo');
          $exitCode = $command->run($input, $output);
          $logger->info('Yahoo Image Check Done. [' . $exitCode . ']');

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了');
          }
          */

          $exitCode = 0;

          // FTPアップロード
          if ((bool)$this->getArgv('doUpload') === true) {
            $commandArgs = [
                'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
              , $exportParentPath // 出力先親ディレクトリ
              , sprintf('--upload-target=%s', $this->getArgv('uploadTarget'))
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }

            $logger->info('nonExclusive job: export_csv_yahoo_upload: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.export_csv_yahoo_upload');
            $exitCode = $command->run($input, $output);
            $logger->info('Yahoo CSV, Image Upload Done. [' . $exitCode . ']');

            if ($exitCode !== 0) { // コマンドが異常終了した
              $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
            }
          }

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;

          break;

        // SHOPLIST CSV・画像FTPアップロード処理
        case self::COMMAND_KEY_EXPORT_CSV_SHOPLIST_UPLOAD:

          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

          $exportTarget = $this->getArgv('exportTarget');
          $exportDir = $this->getArgv('exportDir');

          // CSVアップロード処理
          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , sprintf('--export-dir=%s', $exportDir)
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('main job: export_csv_shoplist_upload: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_shoplist_upload');
          $exitCode = $command->run($input, $output);
          $logger->info('SHOPLIST CSV Upload Done. [' . $exitCode . ']');

          if ($exitCode !== 0) { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }

          // 画像アップロード処理（商品データ更新時のみ）
          if ($exportTarget == 'diffAndStocks') {
            $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            ];
            if (!is_null($this->getArgv('account'))) {
              $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
            }

            $logger->info('main job: product_image_upload_ftp_shoplist: ' . print_r($commandArgs, true));
            $input = new ArgvInput($commandArgs);
            $output = new ConsoleOutput();

            $command = $this->getContainer()->get('batch.product_image_upload_ftp_shoplist');
            $exitCode = $command->run($input, $output);
            $logger->info('SHOPLIST Image Upload Done. [' . $exitCode . ']');
          }

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

        // PPM CSVダウンロード処理 => 完了後、CSV出力＆アップロード
        case self::COMMAND_KEY_DOWNLOAD_CSV_PPM:

          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

          // CSVダウンロード
          $commandArgs = [
              'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , '--output-csv=1'
            , sprintf('--upload-output-csv=%d', (bool)$this->getArgv('doUpload') ? 1 : 0)
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('nonExclusive job: download_ppm_products: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.csv_download_ppm_products');
          $exitCode = $command->run($input, $output);
          $logger->info('PPM CSV Download Done. [' . $exitCode . ']');

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

        // Amazon FBA出荷用CSV作成処理
        case self::COMMAND_KEY_EXPORT_CSV_AMAZON_FBA_ORDER:

          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

          // CSVダウンロード
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
            , '--update-order=1'
            , '--update-fba-stock=1'
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }

          $logger->info('nonExclusive job: export_csv_amazon_fba_order: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.export_csv_amazon_fba_order');
          $exitCode = $command->run($input, $output);
          $logger->info('Export Amazon FBA order CSV Done. [' . $exitCode . ']');

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

        // 納品書印刷待ち伝票一覧 再集計
        case self::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

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

          $logger->info('nonExclusive job: refresh_delivery_statement_detail_product_num_list: ' . print_r($commandArgs, true));
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();

          $command = $this->getContainer()->get('batch.refresh_delivery_statement_detail_product_num_list');
          $exitCode = $command->run($input, $output);
          $logger->info('Refresh delivery statement detail product num list Done. [' . $exitCode . ']');

          $this->finishJobRequest($exitCode, "納品書印刷待ち伝票一覧 再集計を完了しました。"); // JobRequest 終了

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

        // 商品レビューCSVデータ登録処理
        case self::COMMAND_KEY_UPDATE_YAHOO_REVIEW:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);

          $fileName = $this->getArgv('fileName');
          $reviewSiteName = $this->getArgv('reviewSiteName');
          $reviewSiteId = $this->getArgv('reviewSiteId');
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          if (!is_null($this->getArgv('account'))) {
            $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
          }
          if (!is_null($fileName)) {
            $commandArgs[] = '--file-name='. $fileName;
          }
          if (!is_null($reviewSiteName)) {
            $commandArgs[] = '--review-site-name='. $reviewSiteName;
          }
          if (!is_null($reviewSiteName)) {
            $commandArgs[] = '--review-site-id='. $reviewSiteId;
          }
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();
          $command = $this->getContainer()->get('batch.update_yahoo_review');
          $exitCode = $command->run($input, $output);
          $logger->info('Update Yahoo review CSV register Done. [' . $exitCode . ']');

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

        // 出荷リスト自動生成
        case self::COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER:
          $this->runningJobName = $this->getCurrentCommandName();
          $dbUtil->insertRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          ];
          $input = new ArgvInput($commandArgs);
          $output = new ConsoleOutput();
          $command = $this->getContainer()->get('batch.csv_download_and_update_shipping_voucher');
          $exitCode = $command->run($input, $output);
          $logger->info('nonExclusive job: csv_download_and_update_shipping_voucher: ' . print_r($commandArgs, true));

          if ($exitCode === 0) {
            $dbUtil->deleteRunningLog($this->runningJobName, TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          } else { // コマンドが異常終了した
            $this->exitError($exitCode, 'app/console がエラー終了', TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
          }
          $this->runningJobName = null;
          break;

        default:
          break;
      }

      return 0;

    } catch (JobException $e) {
      $logger->error('NonExclusiveJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage(), TbProcessExecuteLog::QUEUE_NAME_NON_EXCLUSIVE);
    }

    return 0;
  }
}
