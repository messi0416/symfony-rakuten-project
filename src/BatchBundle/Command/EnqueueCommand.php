<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\Command\ExportCsvNextEngineMallProductCommand;
use BatchBundle\Command\ExportCsvRakutenGoldCommand;
use BatchBundle\Job\MainJob;
use BatchBundle\Job\NonExclusiveJob;
use BatchBundle\Job\ProductSalesJob;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\Repository\TbDeliveryStatementDetailNumOrderListInfoRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BatchBundle\Job\ProductImageUploadJob;

class EnqueueCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  // private $account;

  protected function configure()
  {
    $this
      ->setName('batch:enqueue')
      ->setDescription('バッチキュー追加')
      // ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('command', null, InputOption::VALUE_REQUIRED, '実行コマンド名')

      ->addOption('import-order-list-months', null, InputOption::VALUE_OPTIONAL, '受注明細取込 期間指定（月）', 6)
      ->addOption('limit-time', null, InputOption::VALUE_OPTIONAL, '実行期限時刻', null) // 共通利用（受注明細取込）
      ->addOption('import-path', null, InputOption::VALUE_OPTIONAL, 'インポートファイルパス', null) // 共通利用（Yahoo おとりよせ.com）
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理スキップ', null) // 共通利用(CSV出力)
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'アップロードするかしないか', null) // 共通利用(CSV出力)
      ->addOption('export-all', null, InputOption::VALUE_OPTIONAL, '販売終了も出力', null) // 楽天CSV出力
      ->addOption('target', null, InputOption::VALUE_OPTIONAL, '処理対象', null)
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    try {
      $resque = $this->getResque();

      switch($input->getOption('command')) {

        // 受注明細差分更新
        case 'update_order_list_incremental':
          // 受注明細差分更新（引当数更新 => フリー在庫数の更新）
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
          ];
          $resque->enqueue($job); // リトライなし
          break;

          // 受注明細取込・受注データ取込
        case 'import_order_list':

          // nか月分+今月分
          $startDate = (new \DateTime())->modify(sprintf('-%d months', $input->getOption('import-order-list-months')));
          $startDate->setDate($startDate->format('Y'), $startDate->format('m'), 1);
          $endDate = new \DateTime();
          $limitTime = $input->getOption('limit-time');

          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST
            , 'startDate' => $startDate->format('Y-m-d')
            , 'endDate'   => $endDate->format('Y-m-d')
            , 'limitTime' => $limitTime
          ];

          // リトライなし
          $resque->enqueue($job);

          $logExecTitle = 'キュー追加処理 (受注明細取込・受注データ取込)';
          $logger->setExecTitle($logExecTitle);
          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          break;

        // 伝票毎利益再集計 & 商品売上実績集計
        case 'aggregate_sales_detail':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_AGGREGATE_SALES_DETAIL,
            'aggregateProductSales' => 1,
          ];
          // 何か月前の1日からを集計するか。オプションを指定しなければ全期間。
          $target = $input->getOption('target');
          if (isset($target)) {
            if (!preg_match('/^0$|^[1-9][0-9]{0,2}$/', $target)) {
              throw new \RuntimeException("--targetには、2桁以下の半角数値で、何か月前の1日からを集計するか指定してください。[{$target}]");
            }
            $job->args['startDate'] = (new \DateTime())->modify('-' . $target . ' month')->format('Y-m-01');
          }
          $resque->enqueue($job);
          break;
          
        // 伝票毎利益再集計のみ
        case 'aggregate_sales_detail_only':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_AGGREGATE_SALES_DETAIL,
            'aggregateProductSales' => 0,
          ];
          // 何か月前の1日からを集計するか。オプションを指定しなければ全期間。
          $target = $input->getOption('target');
          $logger->info(gettype($target). ': ' . $target);
          if (isset($target)) {
            if (!preg_match('/^0$|^[1-9][0-9]{0,2}$/', $target)) {
              throw new \RuntimeException("--targetには、2桁以下の半角数値で、何か月前の1日からを集計するか指定してください。[{$target}]");
            }
            $job->args['startDate'] = (new \DateTime())->modify('-' . $target . ' month')->format('Y-m-01');
          }
          $resque->enqueue($job);
          break;

        // Yahoo CSV出力 ＆ アップロード
        case 'export_csv_yahoo':

          $exportTargets = [
              ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO
            , ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON
            , ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
          ];

          // 出力対象ごとにキューを追加
          foreach($exportTargets as $exportTarget) {

            // 削除CSV出力のため、CSVダウンロードタスクからの実行
            $job = new NonExclusiveJob();
            $job->queue = 'nonExclusive'; // キュー名
            $job->args = [
                'command'       => MainJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
              , 'doUpload'      => true    // Yahoo アップロードフラグ
              , 'exportTarget'  => $exportTarget
              , 'exportCsvType' => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS // 商品CSV出力
            ];

            $resque->enqueue($job);

            sleep(3); // 若干のウェイト
          }

          $logExecTitle = 'キュー追加処理 (Yahoo CSV出力 & アップロード)';
          $logger->setExecTitle($logExecTitle);
          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          break;
          
          // Yahoo CSV出力 ＆ アップロード ダウンロードスキップ版
        case 'export_csv_yahoo_export_only':
          
          $logExecTitle = 'キュー追加処理 (Yahoo CSV出力 & アップロード（ダウンロードなし）)';
          $logger->setExecTitle($logExecTitle);
          
          // 現在のバージョンではtarget必須
          $exportTargets = null;
          if ($input->getOption('target')) {
            $exportTargets = [$input->getOption('target')];
          } else {
            throw new \RuntimeException('targetを指定してください。target は plusnao、kawaemon、otoriyoseのいずれかです');
          }
          
          // import-pathも必須
          $importPath = '';
          if ($input->getOption('import-path')) {
            $importPath = $input->getOption('import-path');
          } else {
            throw new \RuntimeException('import-pathを指定してください。');
          }
          
          // 出力対象ごとにキューを追加 -- 将来一括実行できるようにループを残す
          foreach ($exportTargets as $exportTarget) {
            
            // plusnao、kawa-e-mon
            if ($exportTarget == ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO 
                || $exportTarget == ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON) {
              $job = new MainJob();
              $job->queue = 'main'; // キュー名
              $job->args = [
                'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO
                , 'doUpload'         => ($input->getOption('do-upload') ? true : false)    // Yahoo アップロードフラグ
                , 'exportTarget'     => $exportTarget
                , 'importPath'       => $importPath
              ];
              $resque->enqueue($job);
              
            // おとりよせ
            } else {
              $job = new MainJob();
              $job->queue = 'main'; // キュー名
              $job->args = [
                'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE
                , 'doUpload'         => ($input->getOption('do-upload') ? true : false)    // Yahoo アップロードフラグ
                , 'exportTarget'     => $exportTarget
                , 'importPath'       => $importPath
              ];
              $resque->enqueue($job);
            }
            $logger->addDbLog($logger->makeDbLog($logExecTitle, '終了', "target=[$exportTarget]"));
          }
          break;

        // Yahoo otoriyose CSV出力 ＆ アップロード
        // ※↑ に含めたいが、現状APIによるダウンロードができないため分離
        case 'export_csv_yahoo_otoriyose':

          // 現状APIによるダウンロードができないため、削除CSV出力はなし。
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'       => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE
            , 'doUpload'      => true    // Yahoo アップロードフラグ
            , 'exportTarget'  => ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
            , 'exportCsvType' => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS // 商品CSV出力
            , 'skipCommonProcess' => true // 共通処理スキップ
          ];
          if ($input->getOption('import-path')) {
            $job->args['importPath'] = $input->getOption('import-path');
          }

          $resque->enqueue($job);

          $logExecTitle = 'キュー追加処理 (Yahoo otoriyose CSV出力 & アップロード)';
          $logger->setExecTitle($logExecTitle);
          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          break;

        // Amazon CSV出力 ＆ アップロード
        case 'export_csv_amazon':

          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON
            , 'doUpload'         => true
            , 'exportTarget' => 'all'
          ];

          // リトライなし
          /*
          $retrySeconds = [];
          for ($i = 1; $i <= 3; $i++) {  // 10分刻みで3回
            $retrySeconds[] = 60 * 10;
          }
          $rescue->setJobRetryStrategy([get_class($job) => $retrySeconds]);
          */

          $resque->enqueue($job);
          $logExecTitle = 'キュー追加処理 (Amazon CSV出力 & アップロード)';
          $logger->setExecTitle($logExecTitle);
          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          break;

        // 2018/01/29 Amazon.com 販売休止
//        // Amazon.com CSV出力 ＆ アップロード
//        case 'export_csv_amazon_com':
//
//          $job = new MainJob();
//          $job->queue = 'main'; // キュー名
//          $job->args = [
//              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON_COM
//            , 'doUpload'         => true
//            , 'exportTarget'     => 'all'
//          ];
//
//          // リトライなし
//          /*
//          $retrySeconds = [];
//          for ($i = 1; $i <= 3; $i++) {  // 10分刻みで3回
//            $retrySeconds[] = 60 * 10;
//          }
//          $rescue->setJobRetryStrategy([get_class($job) => $retrySeconds]);
//          */
//
//          $rescue->enqueue($job);
//          $logExecTitle = 'キュー追加処理 (Amazon.com CSV出力 & アップロード)';
//          $logger->setExecTitle($logExecTitle);
//          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
//          break;

        // SHOPLIST CSV出力 ＆ アップロード
        case 'export_csv_shoplist':

          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_SHOPLIST
            , 'doUpload'         => true  // アップロードフラグ
            , 'updatePrice'      => false // 価格更新フラグ
            , 'exportTarget'     => 'diffAndStocks'
          ];

          // リトライなし
          $resque->enqueue($job);
          break;

        // 楽天CSV出力 ＆ アップロード
        // こちらはCSVダウンロード処理がキックされていることが前提の処理。
        case 'export_csv_rakuten':

          $exportTargets = [
            ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN
            , ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO
            , ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST
            , ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO
            , ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA
          ];
          if ($input->getOption('target')) {
            $exportTargets = [$input->getOption('target')];
          }

          // SKU別楽天商品属性項目更新処理
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command' => MainJob::COMMAND_KEY_UPDATE_SKU_RAKUTEN_ATTRIBUTE,
          ];
          $resque->enqueue($job);

          // 出力対象ごとにキューを追加
          // 2回目は共通処理、楽天共通処理skip
          for ($i = 0; $i < count($exportTargets); $i++) {
            $exportTarget = $exportTargets[$i];
            $job = new MainJob();
            $job->queue = 'main'; // キュー名
            $job->args = [
                'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN
              , 'doUpload'         => ! is_null($input->getOption('do-upload')) ? boolval($input->getOption('do-upload')) : true  // アップロードフラグ
            ];
            if (!is_null($input->getOption('skip-common-process'))) {
              $job->args['skipCommonProcess'] = boolval($input->getOption('skip-common-process'));
            }
            if (!is_null($input->getOption('do-upload'))) {
              $job->args['doUpload'] = boolval($input->getOption('do-upload'));
            }
            if (!is_null($input->getOption('export-all'))) {
              $job->args['exportAll'] = boolval($input->getOption('export-all'));
            }
            $job->args['targetShop'] = $exportTarget;
            if ($i >= 1) {
              $job->args['skipCommonProcess'] = 1;
              $job->args['skipRakutencommonProcess'] = 1;
            }

            // インポートディレクトリ
            if ($input->getOption('import-path')) {
              $job->args['importPath'] = $input->getOption('import-path');
            }

            // リトライなし
            $resque->enqueue($job);
          }
          break;

        // 楽天CSV出力SFTPテスト
        case 'export_csv_rakuten_sftp_test':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN
            , 'test'             => 'staging'
          ];
          if (!is_null(($input->getOption('target')))) {
            $job->args['test'] = $input->getOption('target');
          }

          // リトライなし
          $resque->enqueue($job);
          break;

        case 'export_csv_wowma':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_WOWMA
            , 'doUploadCsv'      => true  // アップロードフラグ
            , 'doUploadImage'    => true  // アップロードフラグ
          ];

          // リトライなし
          $resque->enqueue($job);
          break;

        case 'export_csv_q10':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_Q10
            , 'doUploadCsv'      => true  // アップロードフラグ
          ];

          // リトライなし
          $resque->enqueue($job);
          break;

        // PPM CSVダウンロード ＆ CSV出力 ＆ アップロード
        case 'export_csv_ppm':

          $job = new NonExclusiveJob();
          $job->queue = 'nonExclusive'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_DOWNLOAD_CSV_PPM
            , 'doUpload'         => true  // アップロードフラグ
          ];

          // リトライなし
          $resque->enqueue($job);
          break;


        // 即納在庫 各モール一括更新処理
        case 'update_product_stock_each_mall':

          // 受注明細差分更新（引当数更新 => フリー在庫数の更新）
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
          ];
          $resque->enqueue($job); // リトライなし

//          // 楽天 即納のみ在庫更新
//          $job = new MainJob();
//          $job->queue = 'main'; // キュー名
//          $job->args = [
//              'command'  => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPDATE_STOCK
//            , 'doUpload' => true
//          ];
//          $rescue->enqueue($job); // リトライなし

          // Amazon 在庫更新
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'      => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON
            , 'doUpload'     => true
            , 'exportTarget' => 'stocks'
          ];
          $resque->enqueue($job); // リトライなし

          // 2018/01/29 Amazon.com 販売休止
//          // Amazon.com 在庫更新
//          $job = new MainJob();
//          $job->queue = 'main'; // キュー名
//          $job->args = [
//              'command'      => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON_COM
//            , 'doUpload'     => true
//            , 'exportTarget' => 'stocks'
//          ];
//          $rescue->enqueue($job); // リトライなし

          // SHOPLIST 在庫更新
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_SHOPLIST
            , 'doUpload'         => true
            , 'updatePrice'      => false
            , 'exportTarget'     => 'stocks'
          ];
          $resque->enqueue($job); // リトライなし

//          // Yahoo(plusnao) 在庫更新
//          $job= new NonExclusiveJob();
//          $job->queue = 'nonExclusive'; // キュー名
//          $job->args = [
//            'command'          => MainJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
//            , 'doUpload'       => true
//            , 'exportTarget'   => ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO
//            , 'exportCsvType'  => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_STOCK // 在庫CSV出力
//          ];
//          $rescue->enqueue($job); // リトライなし
//
//          // Yahoo(kawaemon) 在庫更新
//          $job= new NonExclusiveJob();
//          $job->queue = 'nonExclusive'; // キュー名
//          $job->args = [
//              'command'        => MainJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
//            , 'doUpload'       => true
//            , 'exportTarget'   => ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON
//            , 'exportCsvType'  => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_STOCK // 在庫CSV出力
//          ];
//          $rescue->enqueue($job); // リトライなし

          // Yahoo(otoriyose) 在庫更新
          $job= new NonExclusiveJob();
          $job->queue = 'nonExclusive'; // キュー名
          $job->args = [
              'command'        => MainJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
            , 'doUpload'       => true
            , 'exportTarget'   => ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
            , 'exportCsvType'  => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_STOCK // 在庫CSV出力
          ];
          $resque->enqueue($job); // リトライなし

          $logExecTitle = 'キュー追加処理 (各モール在庫更新 CSV出力 & アップロード: 楽天（即納のみ）・Amazon・SHOPLIST, Yahoo)';
          $logger->setExecTitle($logExecTitle);
          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          break;

        // 納品書印刷待ち伝票一覧集計、在庫移動一覧更新処理 キュー追加
        case 'refresh_location_and_stock_move_list':

          // 2018/02/27 #33338 ロケーション優先順位意味を持たせる方向にする（FIFOなど）方向のため、一旦停止。
//          // 倉庫ロケーションを並べ替え
//          $job = new MainJob();
//          $job->queue = 'main'; // キュー名
//          $job->args = [
//            'command' => MainJob::COMMAND_KEY_PRODUCT_LOCATION_SORT_ORDER
//          ];
//          // $retrySeconds = [];
//          // $rescue->setJobRetryStrategy([get_class($job) => $retrySeconds]);
//          $rescue->enqueue($job); // リトライなし

          // 受注明細差分更新（引当数更新）
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
          ];
          $resque->enqueue($job); // リトライなし

//          // 倉庫へ 在庫数更新
//          $job = new MainJob();
//          $job->queue = 'main'; // キュー名
//          $job->args = [
//            'command'  => MainJob::COMMAND_KEY_REFRESH_LOCATION_WAREHOUSE_TO_LIST
//          ];
//          $rescue->enqueue($job); // リトライなし

          // 在庫移動一覧 更新
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'  => MainJob::COMMAND_KEY_REFRESH_WAREHOUSE_STOCK_MOVE_LIST
            , 'force' => 1
          ];
          $resque->enqueue($job); // リトライなし


          // 納品書印刷待ち一覧 再集計（通常版）
          $job = new MainJob();
          $job->queue = 'main'; // キュー名 ※こちらは確実に順番で行うためにMainJob
          $job->args = [
                'command'  => MainJob::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST
              , 'settingId' => TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID
              , 'shippingDate' => (new \DateTimeImmutable())->modify('+ 30 day')->format('Y-m-d')
              , 'changeLocationOrder' => 1 /* 朝一なので並べ替えON */
          ];
          $resque->enqueue($job); // リトライなし

          $logExecTitle = 'キュー追加処理 ロケーショ並べ替え、在庫移動一覧更新処理';
          $logger->setExecTitle($logExecTitle);
          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          break;

        case 'export_csv_next_engine_update_stock':

          // 受注明細差分更新
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
          ];
          $resque->enqueue($job); // リトライなし


          // Amazon 在庫取込 ※実行しすぎでAmazonに怒られる可能性。でもひとまずやれとのことでやる
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_IMPORT_AMAZON_STOCK
          ];
          // リトライなし
          $retrySeconds = [];
          $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
          $resque->enqueue($job);


          // NextEngine在庫同期処理
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK
            , 'doUpload'  => true
            , 'targetEnv' => 'prod'
          ];

          $resque->enqueue($job); // リトライなし
          break;

        // 共通日次処理
        case 'daily_batch':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_DAILY_BATCH
          ];
          $resque->enqueue($job); // リトライなし
          break;

        case 'warehouse_box_move':
            $job = new MainJob();
            $job->queue = 'main'; // キュー名
            $job->args = [
                'command'   => MainJob::COMMAND_WAREHOUSE_BOX_MOVE
            ];
            $resque->enqueue($job); // リトライなし
            break;

        // 発送方法一括変換
        case 'import_order_list_incremental':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_DELIVERY_METHOD_CONVERSION
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // SKU別送料設定自動設定
        case 'sku_shippingdivision_auto_setting':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_SKU_SHIPPINGDIVISION_AUTO_SETTING
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // SKU別送料設定の商品マスタ反映
        case 'sku_shippingdivision_reflect_mainproduct':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_SKU_SHIPPINGDIVISION_REFLECT_MAINPRODUCT
          ];
          $resque->enqueue($job); // リトライなし
          break;

          // 代表商品サイズ更新
        case 'update_product_size':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_UPDATE_PRODUCT_SIZE
          ];
          $resque->enqueue($job); // リトライなし
          break;

          // SKUのサイズ変更に伴う更新処理
        case 'sku_size_change_related_update':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_SKU_SIZE_CHANGE_RELATED_UPDATE
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // 商品画像 アップロード処理（楽天・Yahoo・PPM）
        case 'product_image_upload_ftp':
          $job = new ProductImageUploadJob();
          $job->queue = 'productImage'; // キュー名
          $job->args = [
              'command'   => MainJob::COMMAND_KEY_PRODUCT_IMAGE_UPLOAD_FTP
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // 商品売上実績集計
        case 'aggregate_product_sales_account_result_history':
          $job = new ProductSalesJob();
          $job->queue = 'productSales'; // キュー名
          $job->args = [
              'command'   => ProductSalesJob::COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY
          ];
          $target = $input->getOption('target');
          // targetの指定がある場合、指定月前の1日から本日までの受注分を集計
          if (isset($target)) {
            if (!preg_match('/^0$|^[1-9][0-9]{0,2}$/', $target)) {
              throw new \RuntimeException("--targetには、2桁以下の半角数値で、何か月前の1日からを集計するか指定してください。[{$target}]");
            }
            $job->args['order_date_from'] = (new \DateTime())->modify('-' . $target . ' month')->format('Y-m-01');
            $job->args['order_date_to'] = (new \DateTime())->format('Y-m-d');
          }
          $resque->enqueue($job); // リトライなし
          break;

        // 商品売上実績集計（担当者更新分の集計）
        case 'aggregate_product_sales_account_result_history_reserved':
          $job = new ProductSalesJob();
          $job->queue = 'productSales'; // キュー名
          $job->args = [
            'command' => ProductSalesJob::COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY,
            'is_reservation' => 1,
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // 代表商品販売ステータス更新処理
        case 'update_product_sales_status':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_UPDATE_PRODUCT_SALES_STATUS
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // SKU別カラー種別更新処理
        case 'update_sku_color':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_UPDATE_SKU_COLOR
          ];
          $resque->enqueue($job); // リトライなし
          break;

        // 楽天GOLD CSV出力処理
        case 'export_csv_rakuten_gold':
          foreach(ExportCsvRakutenGoldCommand::ENQUEUE_SHOP_LIST as $shop) {
            $job = new MainJob();
            $job->queue = 'main'; // キュー名
            $job->args = [
              'command' => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN_GOLD,
              'shop' => $shop,
              'csv' => implode(',', ExportCsvRakutenGoldCommand::CSV_LIST),
              'doUpload' => $input->getOption('do-upload') ?? true, // 特別指定が無ければアップロードする
            ];
            $resque->enqueue($job);
            sleep(3); // 若干のウェイト
          }
          break;

        // NextEngineモール商品CSV出力処理
        case 'export_csv_next_engine_mall_product':
          foreach (ExportCsvNextEngineMallProductCommand::ENQUEUE_SHOP_LIST as $shop) {
            $job = new MainJob();
            $job->queue = 'main'; // キュー名
            $job->args = [
              'command' => MainJob::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_MALL_PRODUCT,
              'shop' => $shop,
              'doUpload' => $input->getOption('do-upload') ?? true, // 特別指定が無ければアップロードする
              'targetEnv' => 'prod',
            ];
            $resque->enqueue($job);
            sleep(3); // 若干のウェイト
          }
          break;

        // 楽天ジャンル別商品属性項目マスタ更新処理
        case 'update_rakuten_genre_attribute':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command' => MainJob::COMMAND_KEY_UPDATE_RAKUTEN_GENRE_ATTRIBUTE,
          ];
          $resque->enqueue($job);
          break;

        // SKU別楽天商品属性項目更新処理
        case 'update_sku_rakuten_attribute':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command' => MainJob::COMMAND_KEY_UPDATE_SKU_RAKUTEN_ATTRIBUTE,
          ];
          $resque->enqueue($job);
          break;

        // 最新在庫データを取得し、DBに保存する処理
        case 'update_stock_list_next_engine_api':
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
            'command'   => MainJob::COMMAND_KEY_UPDATE_STOCK_LIST,
          ];
          $resque->enqueue($job); // リトライなし
          break;

        default:
          throw new \RuntimeException('キューを追加する処理が指定されませんでした。');
          break;
      }

    } catch (\Exception $e) {

      $logger->error('キュー追加処理 エラー:' . $e->getMessage() . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog('キュー追加処理 エラー', 'キュー追加処理 エラー', 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, 'キュー追加処理 でエラーが発生しました。', 'error'
      );
      return 1;
    }

    return 0;
  }
}
