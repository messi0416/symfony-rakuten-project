<?php

namespace AppBundle\Controller;

use AppBundle\Security\User\SymfonyUserProductEditorProvider;
use BatchBundle\Command\ExportCsvNextEngineCommand;
use BatchBundle\Job\BaseJob;
use BatchBundle\Job\MainJob;
use BatchBundle\Job\NonExclusiveJob;
use BatchBundle\MallProcess\AmazonMallProcess;
use BatchBundle\MallProcess\ShoplistMallProcess;
use BatchBundle\MallProcess\NextEngineMallProcess;
use BCC\ResqueBundle\Queue;
use BCC\ResqueBundle\Worker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MiscBundle\Entity;
use MiscBundle\Entity\BatchLock;
use MiscBundle\Entity\JobRequest;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\ProductImagesAmazon;
use MiscBundle\Entity\ProductImagesVariation;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\BatchLockRepository;
use MiscBundle\Entity\Repository\JobRequestRepository;
use MiscBundle\Entity\Repository\ProductImagesAmazonRepository;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\Repository\ProductImagesVariationRepository;
use MiscBundle\Entity\Repository\SymfonyUserYahooAgentRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbProductLocationRepository;
use MiscBundle\Entity\Repository\TbRakutenTagRepository;
use MiscBundle\Entity\Repository\TbShoplistSpeedbinShippingRepository;
use MiscBundle\Entity\Repository\TbStopWorkerRepository;
use MiscBundle\Entity\Repository\TbVendorCostRateListSettingRepository;
use MiscBundle\Entity\Repository\TbVendorCostRateLogRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TmpProductImagesRepository;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbRakutenTag;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TmpProductImages;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ApiController extends BaseController
{
  /**
   * ログインアカウント ユーザ取得
   */
  public function getLoginUserAction()
  {
    $result = [];

    // ログインアカウント
    $account = $this->getLoginUser();
    if ($account) {
      $result = $account->toArray();
    }

    return new JsonResponse($result);
  }


  /**
   * ログ一覧取得処理 （Ajaxアクセス）
   */
  public function getNotificationListAction(Request $request)
  {
    $lastID = $request->get('last_id');

    // ログ出力しないように改修
    $this->getDoctrine()->getConnection('main')->getConfiguration()->setSQLLogger(null);

    // ログ履歴取得処理
    /** @var DbCommonUtil $dbUtil */
    $dbUtil = $this->get('misc.util.db_common');
    $logList = $dbUtil->getLastLogList($lastID, 100); // 安全のため100件で制限

    return new JsonResponse($logList);
  }

  /**
   * ログ一覧取得処理 （Ajaxアクセス）
   */
  public function getNotificationListMoreAction(Request $request)
  {
    $firstId = $request->get('first_id');

    // ログ出力しないように改修
    $this->getDoctrine()->getConnection('main')->getConfiguration()->setSQLLogger(null);

    // ログ履歴取得処理
    /** @var DbCommonUtil $dbUtil */
    $dbUtil = $this->get('misc.util.db_common');
    $logList = $dbUtil->getLastLogListMore($firstId, 100);

    return new JsonResponse($logList);
  }

  /**
   * ログ１件取得処理 （Ajaxアクセス）
   */
  public function getNotificationAction(Request $request)
  {
    $id = $request->get('id');

    // ログ履歴取得処理
    /** @var EntityRepository $repo */
    $repo = $this->get('doctrine')->getRepository('MiscBundle:TbLog');
    $log = $repo->find($id);
    $result = $log ? $log->toArray(true, true) : [];

    return new JsonResponse($result);
  }

  /**
   * ログ一覧取得絞込(リアルタイムOFF)処理 （Ajaxアクセス）
   */
  public function getNotificationSearchListAction(Request $request)
  {
    $limit = 1000; // 最大取得件数

    $dateFrom = $request->get('target_date_from'); // 対象日時From
    $dateTo = $request->get('target_date_to'); // 対象日時To
    $pcName = $request->get('pc_name'); // PC名
    $execTitle = $request->get('exec_title'); // 処理名
    $logTitle = $request->get('log_title'); // ログ名
    $sub = $request->get('sub'); // サブ

    $searchInfoArray = array(); // 絞込条件情報配列
    // 検索条件格納
    if (! empty($dateFrom)) {
      try {
        $searchInfoArray['dateFrom'] = new \DateTime($dateFrom.':00');
      } catch (\Exception $e) {
        return new JsonResponse(['message' => '対象日時Fromはyyyy/mm/dd hh:mm形式で入力してください。', 'error' => 1]);
      }
    } else {
      // インデックスの効かない検索があるため、フルスキャンを避けるため対象日時Fromを必須とする
      return new JsonResponse(['message' => '対象日時Fromは必須入力です。', 'error' => 1]);
    }
    if (! empty($dateTo)) {
      try {
        $searchInfoArray['dateTo'] = new \DateTime($dateTo.':59');
      } catch (\Exception $e) {
        return new JsonResponse(['message' => '対象日時Toはyyyy/mm/dd hh:mm形式で入力してください。', 'error' => 1]);
      }
    }
    if (! empty($pcName)) {
      $searchInfoArray['pcName'] = preg_split('/[\s]+/', $pcName, -1, PREG_SPLIT_NO_EMPTY);
    }
    if (! empty($execTitle)) {
      $searchInfoArray['execTitle'] =preg_split('/[\s]+/', $execTitle, -1, PREG_SPLIT_NO_EMPTY);
    }
    if (! empty($logTitle)) {
      $searchInfoArray['logTitle'] =preg_split('/[\s]+/', $logTitle, -1, PREG_SPLIT_NO_EMPTY);
    }
    if (! empty($sub)) {
      $searchInfoArray['sub'] =preg_split('/[\s]+/', $sub, -1, PREG_SPLIT_NO_EMPTY);
    }
    
    // ログ出力しないように改修
    $this->getDoctrine()->getConnection('main')->getConfiguration()->setSQLLogger(null);
    
    // ログ履歴取得処理
    /** @var DbCommonUtil $dbUtil */
    $dbUtil = $this->get('misc.util.db_common');
    $logList = $dbUtil->getLogSearchList($searchInfoArray, $limit);
    return new JsonResponse($logList);
  }

  /**
   * バッチロック一覧取得処理
   */
  public function batchLockListAction(Request $request)
  {
    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'list' => []
    ];

    // ログインアカウント
    $account = $this->getLoginUser();

    $logger = $this->get('misc.util.batch_logger');
    $logger->info('get batch lock list. [' . $account->getUsername() . ']');

    try {
      /** @var BatchLock[] $locks */
      $locks = $this->getDoctrine()->getRepository('MiscBundle:BatchLock')->findBy([], ['locked' => 'DESC']);
      foreach($locks as $lock) {
        $result['list'][] = $lock->toScalarArray();
      }

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * バッチロック解除処理
   */
  public function unlockBatchLockAction(Request $request)
  {
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    // ログインアカウント
    $account = $this->getLoginUser();

    $logger = $this->get('misc.util.batch_logger');
    $logger->info('unlock batch lock. [' . $account->getUsername() . ']');

    try {

      /** @var BatchLockRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:BatchLock');
      $repo->unlockAll();

      return new JsonResponse($result);

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['error'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * Job Worker 再起動処理
   */
  public function jobWorkerRebootAction(Request $request)
  {
    set_time_limit(120);

    $result = [
        'error' => null
      , 'output' => null
    ];

    // ログインアカウント
    $account = $this->getLoginUser();

    $logger = $this->get('misc.util.batch_logger');
    $logger->info('reboot job worker. [' . $account->getUsername() . ']');

    $fileUtil = $this->get('misc.util.file');
    $rootDir = $fileUtil->getRootDir();

    $env = $this->get('kernel')->getEnvironment();
    $command = sprintf('%s/bin/restart_resque_worker.sh', dirname($rootDir));

    $logger->info($command);
    $logger->info($env);

    try {

      // Processクラスもsystemもexecもついでにproc_openもダメ（帰ってこない）なため、popen。
      // 更に、"w" の書き込みモードで開かないと動かないという情報あり。
      $tmpLogFile = tempnam('/tmp/', 'reboot_job_worker_log.');
      $pp = popen(sprintf('/bin/bash %s/bin/restart_resque_worker.sh -e %s > "%s" 2>&1', dirname($rootDir), $env, $tmpLogFile), 'w');
      if (!is_resource($pp)) {
        throw new RuntimeException('再起動プロセスの起動に失敗しました。');
      }
      pclose($pp);

      return new JsonResponse([
        'output' => file_get_contents($tmpLogFile)
      ]);

      /* ↓ これが動かない。おそらく入出力のパイプがごにょごにょということだと思われる。
      $builder = new ProcessBuilder(['/bin/bash', $command, "-e $env"]);
      $process = $builder->getProcess();
      $process->setTimeout(120);

      $process->run();
      $result['output'] = $process->getOutput();
      $logger->info($result['output']);

      $logger->info($process->getExitCode());
      if ($process->getExitCode() != 0) {
        $result['error'] = $process->getErrorOutput();
        $logger->error($result['error']);
      }
      */

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $result['error'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * Job Worker 停止処理
   */
  public function jobWorkerStopAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'error' => null
      , 'output' => null
    ];

    $stopMinute = $request->get('stopTime');
    $selectedWorkerName = $request->get('selectedWorkerName');

    $executedUser = $this->getLoginUser()->getUsername();
    $logger->info('stop job worker. [' . $executedUser . ']');
    try {
      $resque = $this->getResque();
      /** @var Worker[] $workers */
      $workers = $resque->getWorkers();
      foreach ($workers as $worker){
        // 選択したワーカーのみ停止
        if(current($worker->getQueues()[0]) == $selectedWorkerName){
          $worker->stop();

          // 停止時間と停止者を登録
          /** @var \Doctrine\DBAL\Connection $dbMain */
          $dbMain = $this->getDoctrine()->getConnection('main');
          $sql = <<<EOD
        INSERT INTO tb_stop_worker(
          username, stop_worker, stop_time, is_active, created_at
        )VALUES(
          :account, :stop_worker, :stop_time, 0 , :created_at
        );
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':account', $executedUser, \PDO::PARAM_STR);
          $stmt->bindValue(':stop_worker', $selectedWorkerName, \PDO::PARAM_STR);
          $stmt->bindValue(':stop_time', $stopMinute , \PDO::PARAM_INT);
          $stmt->bindValue(':created_at', date("Y-m-d H:i:s"),\PDO::PARAM_STR);
          $stmt->execute();
          $logger->info('正常に停止しました。[' . $worker->getId() . ']');

          return new JsonResponse([
            'output' => '正常に停止しました。[' . $worker->getId() . ']'
          ]);
        }
      }
      throw new RuntimeException('ワーカーはすでに停止しているか、存在しません。');

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['error'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * ワーカーの停止時間に達しているかを確認
   *
   * @param Request $request
   * @return JsonResponse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function workerCheckStatusAction(Request $request){
    $logger = $this->get('misc.util.batch_logger');

    $resque = $this->getResque();

    /** @var Worker[] $workers */
    $workers = $resque->getWorkers();
    // 起動しているワーカ－がジョブ実行中か  ジョブ実行中ならば終わるまでワーカーが停止しないためレコードを更新させない
    $isProcessingWorker = [];
    foreach ($workers as $worker){
      $isProcessingWorker[current($worker->getQueues()[0])] = $worker->getCurrentJob() != null;
    }

    /** @var Queue[] $queues */
    $queues = $resque->getQueues();
    $queueName = [];
    foreach ($queues as $queue){
      array_push($queueName,$queue->getName());
    }
    
    // ログ出力しないように改修
    $this->getDoctrine()->getConnection('main')->getConfiguration()->setSQLLogger(null);

    /** @var TbStopWorkerRepository $stopRepo */
    $stopRepo = $this->getDoctrine()->getRepository('MiscBundle:TbStopWorker');
    $stopRepo->disableUnnecessaryStopFlg($isProcessingWorker,$queueName);

    $fileUtil = $this->get('misc.util.file');
    $rootDir = $fileUtil->getRootDir();
    $env = $this->get('kernel')->getEnvironment();
    // 止まっているワーカーが停止させられたものならば起動させる
    $stoppedWorkers = $stopRepo->checkWorkerRemainingTime($rootDir,$env,$isProcessingWorker);

    if(!empty($stoppedWorkers['output'])){
      for($idx = 0;$idx < count($stoppedWorkers['output']);$idx++){
        $worker = $stoppedWorkers['output'][$idx];
        $stoppedWorkers['output'][$idx]['is_running'] = array_key_exists($worker['stop_worker'],$isProcessingWorker) ? $isProcessingWorker[$worker['stop_worker']] : null;
      }
    }
    return new JsonResponse($stoppedWorkers);
  }

  /**
   * Job Worker 一時停止キャンセル処理
   * 対象レコードを無効にして起動コマンド実行
   */
  public function jobWorkerCancelAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'info' => null
      , 'error' => null
    ];

    $username = $this->getLoginUser()->getUsername();
    $id = $request->get('id');
    $workerName = $request->get('workerName');


    $resque = $this->getResque();
    /** @var Worker[] $workers */
    $workers = $resque->getWorkers();
    foreach ($workers as $worker){
      // すでに起動していたら
      if(current($worker->getQueues()[0]) === $workerName){
         return new JsonResponse([
           'info' => 'このワーカーはすでに起動しています。'
         ]);
      }
    }

    try {
      // 起動処理を占有させるために先にレコード非活性化させる
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $sql = <<<EOD
        UPDATE tb_stop_worker
          SET is_active = -1,
            cancel_user = :username
          WHERE id = :id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
      $stmt->bindValue(':username', $username, \PDO::PARAM_STR);
      $stmt->execute();

      $fileUtil = $this->get('misc.util.file');
      $rootDir = $fileUtil->getRootDir();
      $env = $this->get('kernel')->getEnvironment();

      /** @var TbStopWorkerRepository $stopRepo */
      $stopRepo = $this->getDoctrine()->getRepository('MiscBundle:TbStopWorker');
      // 起動コマンド実行処理
      $status = $stopRepo->startWorker($rootDir,$workerName,$env);
      if($status['error']){
        // ロールバック
        $sql = <<<EOD
        UPDATE tb_stop_worker
          SET is_active = 0,
            cancel_user = ''
          WHERE id = :id
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        throw new RuntimeException($status['error']);
      }
      $logger->info('正常にキャンセルしました。[' . $workerName . ']');
      return new JsonResponse([
        'info' => '正常にキャンセルしました。[' . $workerName . ']'
      ]);
    }catch (\Exception $e){
      $logger->error($e->getMessage());
      $result['info'] = $e->getMessage();
      $result['error'] = true;
      return new JsonResponse($result);
    }
  }

  /**
   * キュー登録前チェック
   */
  public function verifyEnqueueAction(Request $request)
  {
    $result = [
        'valid' => true
      , 'message' => ''
      , 'notices' => []
    ];

    /** @var DbCommonUtil $dbUtil */
    $dbUtil = $this->get('misc.util.db_common');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $command = $request->get('command');
    $confirm = (bool)$request->get('confirm', 0); // 実行前確認

    // 同一処理の予約不可
    $reservedCommands = $this->findQueuesByCommandName($request->get('queue'), $command);
    if ($reservedCommands) {
      $result['valid'] = false;
      $result['message'] = 'すでに同じ処理が登録されています。完了するまで予約できません。';
      $result['notices'] = array_map(function($job) {
        return sprintf(
          "%s : %s",
          $job->getQueueDateTime()->format('Y-m-d H:i:s'),
          $job->getCurrentCommandName()
        );
      }, $reservedCommands);
      return new JsonResponse($result);
    }

    // 他処理実行中
    // 非排他処理
    $nonExclusiveCommands = [
        'refresh_picking_marks'
      , 'import_picking_list'
      , 'import_shipping_voucher'
      , 'create_picking_list_by_order_number'
      , 'export_csv_amazon_fba_order'
      , 'export_csv_amazon_snl_order'
      , 'import_wowma_lot_number_csv'
    ];

    if (!in_array($command, $nonExclusiveCommands) && $dbUtil->isProcessRunning()) {
      $processes = $dbUtil->getRunningProcesses();
      $processNotices = [];
      foreach ($processes as $process) {
        $processNotices[] = sprintf("%s : %s", $process['start_datetime'], $process['proc']);
      }

      // 予約不可の処理
      $unReservableCommands = [
          //   'export_csv_next_engine'
          // , 'export_csv_yahoo'
      ];
      if (in_array($command, $unReservableCommands)) {
        $result['valid'] = false;
        $result['message'] = '現在、別の処理が実行中です。この処理は予約できません。時間を置いて再度実行してください。';
        $result['notices'] = $processNotices;
        return new JsonResponse($result);
      } else {
        $result['notices'][] = '現在、別の処理が実行中です。この処理の後に処理を予約することになります。';
        $result['notices'] = array_merge($result['notices'], $processNotices);
      }
    }

    // メッセージ
    switch ($command) {
      case 'import_stock_list':
        $result['message'] = '在庫CSVダウンロードおよび在庫の取込処理を予約してよろしいですか？';
        if ($confirm) {
          // TODO 自動ダウンロード or 手動FTPファイル チェック

        }

        break;
      case 'import_order_list':
        $result['message'] = '受注CSVダウンロードおよび受注明細の取込処理を予約してよろしいですか？';
        if ($confirm) {
          // TODO 自動ダウンロード or 手動FTPファイル チェック

        }

        break;

      case 'import_order_list_incremental':
        $result['message'] = '受注明細の差分更新処理を予約してよろしいですか？ （NextEngine API 利用）';
        break;

      case 'import_stock_in_out':
        $result['message'] = '入出庫データCSVダウンロードおよび取込処理を予約してよろしいですか？';
        if ($confirm) {
          // TODO 自動ダウンロード or 手動FTPファイル チェック

        }

        break;
      case 'import_view_ranking':
        $result['message'] = '閲覧ランキングCSVダウンロードおよび取込処理を予約してよろしいですか？';
        if ($confirm) {
          // TODO 自動ダウンロード or 手動FTPファイル チェック

        }
        break;
      case 'import_rakuten_review':
        $result['message'] = '楽天レビューCSVダウンロードおよび取込処理を予約してよろしいですか？';
        break;

      case 'import_amazon_stock':
        $result['message'] = 'Amazonの登録在庫およびFBA納品済み在庫を取得します。よろしいですか？';
        break;

      case 'refresh_location':
        $result['message'] = 'ロケーション更新処理を予約してよろしいですか？';
        break;

      case 'sort_location_order':
        $result['message'] = 'ロケーション並べ替え処理を予約してよろしいですか？';
        break;

      case 'import_picking_list':
        $result['message'] = 'ピッキングリストCSVファイルをアップロードして、WEB用ピッキングリストを作成します。';
        break;

      case 'create_picking_list_by_order_number':
        $result['message'] = '伝票番号を改行区切りで入力してください。';
        break;

      case 'import_shipping_voucher':
        $result['message'] = '納品書CSVファイルをアップロードして、出荷リストおよびWEB用ピッキングリストを作成します。';
        break;

      case 'aggregate_sales_detail':
        $result['message'] = '伝票毎利益再集計処理を行います。よろしいですか？';
        break;

      case 'export_csv_next_engine':
        // ファイル存在チェック(アップロード中か確認)
        $webCsvDir = $this->container->get('misc.util.file')->getWebCsvDir();
        $dirname = $webCsvDir . '//NextEngine//' . ExportCsvNextEngineCommand::CURRENT_UPLOAD_DIRECTORY_NAME;
        $fs = new FileSystem();
        $finder = new Finder();
        $logger->info($dirname);
        $result['isUploading'] = false;
        if ($fs->exists($dirname) && $finder->in($dirname)->files('/*.csv')->count() > 0) {
          $result['isUploading'] = true;
        }

        $result['message'] = 'NextEngine CSV出力（およびアップロード）処理を開始してよろしいですか？';
        break;

      case 'export_csv_next_engine_update_stock':
        $result['message'] = 'NextEngine 在庫同期処理を開始してよろしいですか？';

        $lastProcessed = $dbUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK);
        $result['lastProcessed'] = $lastProcessed ? $lastProcessed->format('Y-m-d H:i:s') : '';

        // 棚卸CSVファイル
        $inventoryCsvFile = $this->getLastNextEngineInventoryCsvFile();
        $result['inventoryCsvFile'] = $inventoryCsvFile
                                    ? [
                                          'path' => $inventoryCsvFile->getPathname()
                                        , 'name' => $inventoryCsvFile->getBasename()
                                        , 'date' => (new \DateTime('@' . $inventoryCsvFile->getMTime()))->setTimezone(new \DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s')
                                      ]
                                    : null;
        break;

      case 'export_csv_next_engine_mall_product':
        $result['message'] = 'NextEngineモール商品CSV出力処理を開始してよろしいですか？';
        break;

      // TODO 削除
      case 'download_stock_and_export_csv_next_engine':
        $result['message'] = '在庫データの取り込み および NextEngine CSV出力（およびアップロード）処理を開始してよろしいですか？';
        break;

      case 'export_csv_yahoo':
        $immediateShippingDate = $dbUtil->getImmediateShippingDate();
        $result['immediateShippingDate'] = $immediateShippingDate ? $immediateShippingDate->format('Y-m-d') : '';
        $result['message'] = 'ヤフーCSV出力処理を開始してよろしいですか？';
        break;

      case 'export_csv_yahoo_otoriyose':
        $result['message'] = 'ヤフーおとりよせCSV出力処理を開始してよろしいですか？';
        break;

      case 'export_csv_amazon':
        $immediateShippingDate = $dbUtil->getImmediateShippingDate();
        $result['immediateShippingDate'] = $immediateShippingDate ? $immediateShippingDate->format('Y-m-d') : '';
        $result['message'] = 'Amazon CSV出力（およびアップロード）処理を開始してよろしいですか？';
        break;

      case 'export_csv_amazon_com':
        $immediateShippingDate = $dbUtil->getImmediateShippingDate();
        $result['immediateShippingDate'] = $immediateShippingDate ? $immediateShippingDate->format('Y-m-d') : '';
        $result['message'] = 'Amazon.com(US) CSV出力（およびアップロード）処理を開始してよろしいですか？';
        break;

      case 'export_csv_shoplist':
        $result['message'] = 'SHOPLIST CSV出力（およびアップロード）処理を開始してよろしいですか？';
        break;

      case 'export_csv_rakuten':
        $immediateShippingDate = $dbUtil->getImmediateShippingDate();
        $result['immediateShippingDate'] = $immediateShippingDate ? $immediateShippingDate->format('Y-m-d') : '';
        $result['message'] = '楽天CSV出力処理を開始してよろしいですか？';
        break;

      case 'export_csv_rakuten_update_stock':
        $result['message'] = '楽天在庫更新CSV出力処理を開始してよろしいですか？';
        break;

      case 'export_csv_rakuten_rpp_exclude':
        $result['message'] = 'RPP対象商品を記載してください。記載のないものは除外されます。';
        break;

      case 'export_csv_rakuten_gold':
        $result['message'] = '楽天GOLD CSV出力処理を開始してよろしいですか？';
        break;

      case 'export_csv_amazon_fba_order':

        // 出力済みCSVファイル

        /** @var AmazonMallProcess $mallProcess */
        $mallProcess = $this->get('batch.mall_process.amazon');

        $csvFiles = $mallProcess->getLastAmazonFbaOrderCsvFile();
        $result['csvFiles'] = [];
        foreach ($csvFiles as $file) {
          $result['csvFiles'][] = [
            'path' => $file->getPathname()
            , 'name' => $file->getBasename()
            , 'date' => (new \DateTime('@' . $file->getMTime()))->setTimezone(new \DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s')
          ];
        }

        if ($result['csvFiles']) {
          $result['message'] = '出力済みのAmazon FBA出荷用CSV一覧です。';
        } else {
          $result['message'] = '出力済みのAmazon FBA出荷用CSVはありません。';
        }

        break;

      case 'export_csv_amazon_snl_order':

        // 出力済みCSVファイル

        /** @var AmazonMallProcess $mallProcess */
        $mallProcess = $this->get('batch.mall_process.amazon');

        $csvFiles = $mallProcess->getLastSnlOrderCsvFile();
        $logger->dump($csvFiles);

        $result['csvFiles'] = [];
        foreach ($csvFiles as $dirName => $files) {
          $result['csvFiles'][] = [
              'nextEngine' => basename($files['ne'])
            , 'amazonAdd' => basename($files['amazon_add'])
            , 'amazonDelete' => basename($files['amazon_delete'])
            , 'date' => (new \DateTime($dirName))->setTimezone(new \DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s')
            , 'dirName' => $dirName
          ];
        }

        if ($result['csvFiles']) {
          $result['message'] = null; // '出力済みのAmazon S&L出荷用CSV一覧です。';
        } else {
          $result['message'] = '出力済みのAmazon S&L出荷用CSVはありません。';
        }

        break;

      case 'aggregate_shoplist_speedbin_delivery':

        // 過去の出力予約を取得
        
        /** @var TbShoplistSpeedbinShippingRepository $shoplistSpeedbinRepo */
        $shoplistSpeedbinRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistSpeedbinShipping');
        $result['dataList'] = $shoplistSpeedbinRepo->findShippingArray(3);
        
        // 各種設定を取得
        $keepStock = $dbUtil->getSettingValue(TbSetting::KEY_SHOPLIST_SPEEDBIN_KEEP_STOCK);
        $result['keepStock'] = $keepStock;
        break;

      case 'import_wowma_lot_number_csv':
        $result['message'] = null;
        break;

      case 'delivery_method_conversion':
        $result['message'] = '発送方法一括変換を開始します、よろしいですか？';
        break;

      default:
        $result['message'] = '処理を予約しますか？';
        break;
    }

    // 追加処理
    // Yahoo 削除CSV作成時 API アクセストークン取得チェック
    if ($command == 'export_csv_yahoo') {

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      $logger->info($fileUtil->getLogDir());

      $logFile = sprintf('%s/%s', $fileUtil->getLogDir(), 'yahoo_api.log');
      /** @noinspection PhpParamsInspection */
      /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
      \YConnect\Util\Logger::setLogPath($logFile);
      /** @noinspection PhpParamsInspection */
      /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
      \YConnect\Util\Logger::setLogLevel(\YConnect\Util\Logger::DEBUG);

      /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
      $webAccessUtil = $this->get('misc.util.web_access');
      $result['yahoo_api_enabled'] = $webAccessUtil->isEnabledYahooApi();
    }

    // Yahoo おとりよせCSV APIアクセストークン取得チェック
    if ($command == 'export_csv_yahoo_otoriyose') {
      /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
      $webAccessUtil = $this->get('misc.util.web_access');

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      $logger->info($fileUtil->getLogDir());

      $logFile = sprintf('%s/%s', $fileUtil->getLogDir(), 'yahoo_api.log');
      /** @noinspection PhpParamsInspection */
      /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
      \YConnect\Util\Logger::setLogPath($logFile);
      /** @noinspection PhpParamsInspection */
      /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
      \YConnect\Util\Logger::setLogLevel(\YConnect\Util\Logger::DEBUG);

      /** @var SymfonyUserYahooAgentRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserYahooAgent');
      $agents = $repo->getActiveAccounts();

      $result['agents'] = [];
      foreach ($agents as $agent) {
        $result['agents'][$agent->getShopCode()] = [
          'code' => $agent->getShopCode()
          , 'name' => $agent->getClientName()
          , 'authUrl' => $this->generateUrl('service_auth_yahoo_agent', [
            'shopCode' => $agent->getShopCode()
          ])
          , 'isApiEnabled' => $webAccessUtil->isEnabledYahooAgentYahooApi($agent)
        ];
      }
    }

    return new JsonResponse($result);
  }

  /**
   * 原価率一覧画面 揺さぶり対象仕入先 取得処理
   */
  public function getSettledCostRateVendorsAction(Request $request)
  {
    /** @var TbVendorCostRateLogRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbVendorCostRateLog');

    try {
      $threshold  = $request->get('threshold');
      $dateAStart = $request->get('dateAStart') ? new \DateTime($request->get('dateAStart')) : null;
      $dateAEnd   = $request->get('dateAEnd') ? new \DateTime($request->get('dateAEnd')) : null;
      $dateBStart = $request->get('dateBStart') ? new \DateTime($request->get('dateBStart')) : null;
      $dateBEnd   = $request->get('dateBEnd') ? new \DateTime($request->get('dateBEnd')) : null;

      if (is_null($threshold) || !$dateAStart || !$dateAEnd || !$dateBStart || !$dateBEnd) {
        throw new RuntimeException('no enough date parameters');
      }

      // A期間・B期間 の大きい方が閾値未満の場合には対象とする
      $changesA = $repo->getCostRateChangeSum($dateAStart, $dateAEnd);
      $changesB = $repo->getCostRateChangeSum($dateBStart, $dateBEnd);

      $sireCodes = array_unique(array_merge(array_keys($changesA), array_keys($changesB)));

      $result = [];
      foreach($sireCodes as $code) {
        $a = isset($changesA[$code]) ? $changesA[$code] : 0;
        $b = isset($changesB[$code]) ? $changesB[$code] : 0;
        $change = $a > $b ? $a : $b;

        if ($change < $threshold) {
          $result[] = $code;
        }
      }

      return new JsonResponse($result);

    } catch (\Exception $e) {
      $logger = $this->get('misc.util.batch_logger');
      $logger->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * 原価率一覧画面 設定値保存処理
   */
  public function saveCostRateVendorsSettingAction(Request $request)
  {
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $changeAmountUp         = $request->get('change_amount_up');
      $changeAmountDown       = $request->get('change_amount_down');
      $changeAmountAdditional = $request->get('change_amount_additional');
      $changeThreshold        = $request->get('change_threshold');
      $minimumVoucher         = $request->get('minimum_voucher');
      $settledThreshold       = $request->get('settled_threshold');

      if ( is_null($changeAmountUp)
        || is_null($changeAmountDown)
        || is_null($changeAmountAdditional)
        || is_null($changeThreshold)
        || is_null($minimumVoucher)
        || is_null($settledThreshold)
      ) {
        throw new RuntimeException('no enough date parameters');
      }

      /** @var TbVendorCostRateListSettingRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbVendorCostRateListSetting');
      $setting = $repo->getCurrentSetting();

      $setting->setChangeAmountUp($changeAmountUp);
      $setting->setChangeAmountDown($changeAmountDown);
      $setting->setChangeAmountAdditional($changeAmountAdditional);
      $setting->setChangeThreshold($changeThreshold);
      $setting->setMinimumVoucher($minimumVoucher);
      $setting->setSettledThreshold($settledThreshold);

      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

    } catch (\Exception $e) {
      $logger = $this->get('misc.util.batch_logger');
      $logger->error($e->getMessage());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * CakePHP 商品登録・編集時 画像更新処理
   * ※ XServer CakePHPからの内部実行用API
   *    認証は省略し、接続元IPでアクセスを制限する。
   */
  public function processTmpProductImagesAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
    ];

    try {
      $allowedIps = $this->getParameter('process_tmp_product_images_allowed_ips');
      if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
        throw new AccessDeniedHttpException('can not access this url.');
      }

      // 一時画像キーから情報を取得する
      $imageKey = $request->get('image_key');
      $daihyoSyohinCode = $request->get('daihyo_syohin_code');

      if (!$imageKey || !$daihyoSyohinCode) {
        throw new RuntimeException('パラメータが正しくありません');
      }

      // EntityManager ... ※なぜ Repository から取得できない仕様なのか不明。
      /** @var EntityManager $emMain */
      $emMain = $this->getDoctrine()->getManager('main');

      // 商品マスタ
      /** @var BaseRepository $productRepository */
      $productRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts', 'main');
      /** @var TbMainproducts $product */
      $product = $productRepository->find($daihyoSyohinCode);
      if (!$product) {
        throw new RuntimeException('該当の商品が見つかりませんでした。');
      }

      /** @var TmpProductImagesRepository $tmpImageRepo */
      $tmpImageRepo = $this->getDoctrine()->getRepository('MiscBundle:TmpProductImages', 'tmp');
      $tmpImages = $tmpImageRepo->findByImageKey($imageKey, $daihyoSyohinCode);

      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->get('misc.util.image');

      $variFlag = false;

      /** @var TmpProductImages $image */
      foreach($tmpImages as $image) {

        // バリエーション画像
        if ($image->isVariation()) {

          $variFlag = true;

          // 画像レコードの取得
          /** @var ProductImagesVariationRepository $productImageRepository */
          $productImageRepository = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation', 'main');
          /** @var ProductImagesVariation $productImage */
          $productImage = $productImageRepository->findOneBy([
              'daihyo_syohin_code' => $daihyoSyohinCode
            , 'code' => $product->getColorAxis()
            , 'variation_code' => $image->getImageCode()
          ]);

          $colorAxis = $product->getColorAxis();

          // 画像アドレス削除
          if ($image->isDeleteFlgDeleted()) {

            if (!$productImage) { // 画像レコードがなければスルー（更新がかち合った、など）
              continue;
            }

            // 画像ファイル・画像レコード削除
            $imageUtil->deleteImage($productImage);
            $emMain->remove($productImage);

            // 新規・更新
          } else {

            // レコードの新規作成
            if (!$productImage) {
              $productImage = new ProductImagesVariation();
              $productImage->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
              $productImage->setCode($product->getColorAxis());
              $productImage->setVariationCode($image->getImageCode());
              $emMain->persist($productImage);
            }

            $fileName = sprintf('%s-sw-%s%s.jpg', strtolower($product->getDaihyoSyohinCode()), $product->getColorAxis(), $image->getImageCode());
            $dirName = sprintf('%s/%s', strtolower(substr($product->getDaihyoSyohinCode(), 0, '1')), $product->getDaihyoSyohinCode());
            $productImage->setDirectory($dirName);
            $productImage->setFilename($fileName);
            $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

            // 画像URLはplusnaoホストの画像ディレクトリ
            $productImage->setAddress(sprintf('https://%s/variation_images/%s', $this->getParameter('host_plusnao'), $productImage->getFileDirPath()));

            // 画像ファイルの（上書き）保存
            $filePath = $imageUtil->saveTmpProductImageToVariation($productImage, $image);
            if (!$filePath) {
              throw new RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getFileDirPath() );
            }

            // 画像ファイルの加工処理 -> 無し
          }

        // Amazonメイン画像
        } else if ($image->isAmazonMain()) {
          // 画像レコードの取得
          /** @var ProductImagesAmazonRepository $productImageRepository */
          $productImageRepository = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesAmazon', 'main');
          /** @var ProductImagesAmazon $productImage */
          $productImage = $productImageRepository->findOneBy([
              'daihyo_syohin_code' => $daihyoSyohinCode
            , 'code' => $image->getImageCode()
          ]);

          // 画像アドレス削除
          if ($image->isDeleteFlgDeleted()) {

            if (!$productImage) { // 画像レコードがなければスルー（更新がかち合った、など）
              continue;
            }

            // 画像ファイル・画像レコード削除
            $imageUtil->deleteImage($productImage);
            $emMain->remove($productImage);

            // 新規・更新
          } else {

            // レコードの新規作成
            if (!$productImage) {
              // R-Cabinet制限に合わせて格納ディレクトリ取得
              $directory = $imageUtil->findAvailableImageDirectory('amazon_main');
              $fileName = sprintf('%s.jpg', strtolower($product->getDaihyoSyohinCode()));

              $productImage = new ProductImagesAmazon();
              $productImage->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
              $productImage->setCode($image->getImageCode());
              $productImage->setDirectory($directory);
              $productImage->setFilename($fileName);

              $emMain->persist($productImage);
            }

            // 画像URLはplusnaoホストの画像ディレクトリ
            $productImage->setAddress(sprintf('https://%s/amazon_images/%s', $this->getParameter('host_plusnao'), $productImage->getFileDirPath()));
            $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

            // 画像ファイルの（上書き）保存
            $filePath = $imageUtil->saveTmpProductImageToAmazon($productImage, $image);
            if (!$filePath) {
              throw new RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getFileDirPath() );
            }

            // 画像ファイルの加工処理 -> 無し

            // 類似画像チェック用 文字列作成・格納（上書き） → なし

          }
        // 通常画像
        } else {

          // 画像レコードの取得
          /** @var ProductImagesRepository $productImageRepository */
          $productImageRepository = $this->getDoctrine()->getRepository('MiscBundle:ProductImages', 'main');
          /** @var ProductImages $productImage */
          $productImage = $productImageRepository->findOneBy([
            'daihyo_syohin_code' => $daihyoSyohinCode
            , 'code' => $image->getImageCode()
          ]);

          // 画像アドレス削除
          if ($image->isDeleteFlgDeleted()) {
            $product->setImageFieldData('caption'   , $image->getImageCode(), '');
            $product->setImageFieldData('address'   , $image->getImageCode(), '');
            $product->setImageFieldData('directory' , $image->getImageCode(), '');
            $product->setImageFieldData('filename'  , $image->getImageCode(), '');

            $emMain->persist($product);

            if (!$productImage) { // 画像レコードがなければスルー（更新がかち合った、など）
              continue;
            }

            // 画像ファイル・画像レコード削除
            $imageUtil->deleteImage($productImage);
            $emMain->remove($productImage);

            // サムネイル画像ファイル削除
            $imageUtil->deleteThumbnailImage($productImage);

            // 新規・更新
          } else {

            // レコードの新規作成
            if (!$productImage) {
              $productImage = new ProductImages();
              $productImage->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
              $productImage->setCode($image->getImageCode());
              $emMain->persist($productImage);
            } else {
              // サムネイル画像ファイル削除
              $imageUtil->deleteThumbnailImage($productImage);
            }

            $productImage->setAddress($product->getImageFieldData('address'     , $image->getImageCode()));
            $productImage->setDirectory($product->getImageFieldData('directory' , $image->getImageCode()));
            $productImage->setFilename($product->getImageFieldData('filename'   , $image->getImageCode()));
            $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

            // 画像ファイルの（上書き）保存
            $originalFilePath = $imageUtil->saveTmpProductImageToOriginal($productImage, $image);
            if (!$originalFilePath) {
              throw new RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getDirectory() . '/' . $productImage->getFilename() );
            }

            // 画像ファイルの加工処理
            $imageUtil->convertOriginalFileToFixedFile($originalFilePath);

            // オリジナル画像でmd5取得
            $productImage->setMd5hash(hash_file('md5', $originalFilePath));

            // 類似画像チェック用 文字列作成・格納（上書き） → なし
          }
        }
      }

      // 一時画像削除
      $tmpImageRepo->deleteByImageKey($imageKey);

      $emMain->flush();

      // 画像IDの反映
      if($variFlag){
        $tbProductChoiceItemsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems', 'main');
        $logger->info(print_r($daihyoSyohinCode, true));
        $logger->info(print_r($colorAxis, true));
        $tbProductChoiceItemsRepository->setColorImageId($daihyoSyohinCode,$colorAxis);
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * CakePHP 商品登録・編集時 タグ一覧取得処理
   * ※ XServer CakePHPからの内部実行用API
   *    認証は省略し、接続元IPでアクセスを制限する。
   */
  public function findRakutenTagInfoAction(Request $request)
  {
    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'data' => null
    ];

    $logger = $this->get('misc.util.batch_logger');

    try {
      $allowedIps = $this->getParameter('process_tmp_product_images_allowed_ips');
      if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
        throw new AccessDeniedHttpException('can not access this url.');
      }

      $code = $request->get('code');

      /** @var TbMainproducts $product */
      $product = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts')->find($code);

      /** @var TbRakutenTagRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenTag');
      $tags = $repo->findByProduct($product);

      $data = [
          'daihyoSyohinCode' => $code
        , 'product' => []
        , 'choice' => []
        , 'current' => [
            'product' => []
          , 'choice' => []
        ]
      ];

      foreach($tags as $tag) {
        // 商品用タグ
        $data['product'][] = $tag->toScalarArray();
        // 選択肢用タグ
        if ($tag->isSelectEnabled()) {
          $data['choice'][] = $tag->toScalarArray();
        }
      }

      // 商品・SKUに登録済みのタグ一覧取得
      $productTags = $repo->findProductRelatedTags($product);
      $choicesTags = $repo->findProductSkuRelatedTags($product);

      foreach($productTags as $tag) {
        // 商品用タグ
        $data['current']['product'][] = $tag->toScalarArray();
      }
      /**
       * @var string $neSyohinCode
       * @var TbRakutenTag[] $tags
       */
      foreach($choicesTags as $neSyohinCode => $tags) {
        // 選択肢用タグ
        $data['current']['choice'][$neSyohinCode] = [];
        foreach($tags as $tag) {
          $data['current']['choice'][$neSyohinCode][] = $tag->toScalarArray();
        }
      }

      $result['data'] = $data;

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 仕入先検索
   */
  public function searchVendorAddressAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'results' => []
    ];

    try {

      if ($request->get('address')) {

        $logger->info($request->get('address'));

        /** @var \Doctrine\DBAL\Connection $db */
        $db = $this->get('doctrine')->getConnection('main');
        /** @var DbCommonUtil $commonUtil */
        $commonUtil = $this->get('misc.util.db_common');
        $sql = <<<EOD
          SELECT
               daihyo_syohin_code
             , sire_adress
          FROM tb_vendoraddress
          WHERE sire_adress LIKE :address_http OR sire_adress LIKE :address_https
          ORDER BY checkdate DESC
                 , daihyo_syohin_code
          LIMIT 100
EOD;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':address_http', '%' . $commonUtil->escapeLikeString(str_replace('https://', 'http://', $request->get('address'))) . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':address_https', '%' . $commonUtil->escapeLikeString(str_replace('http://', 'https://', $request->get('address'))) . '%', \PDO::PARAM_STR);

        $stmt->execute();

        foreach($stmt as $row) {
          $result['results'][] = $row;
        }

        if (!count($result['results'])) {
          $result['status'] = 'ng';
          $result['message'] = '該当するアドレスはありませんでした。';
        }

      } else {
        $result['status'] = 'ng';
        $result['message'] = '仕入先アドレス（の一部）を入力して下さい。';
      }

      $logger->info(print_r($result, true));

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * ログインなし 仕入れ検索画面
   * @param Request $request
   * @return Response
   */
  public function searchAddressAction(Request $request)
  {
    $account = '';
    // ログインしている場合のヘッダー表示用
    if($this->isGranted('ROLE_USER')){
      $account = $this->getLoginUser();
    }
    return $this->render('AppBundle:Default:api_vendor_address.html.twig', [
      'account' => $account
    ]);
  }

  /**
   * 楽天納期管理番号 一覧取得
   */
  public function getRakutenNokiKanriAction()
  {
    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'results' => []
      , 'lastUpdated' => null
    ];

    try {

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $sql = <<<EOD
        SELECT
            納期管理番号   AS number
          , DATE_FORMAT(出荷日, '%Y/%m/%d') AS shipping_date
          , 見出し         AS subject
          , 出荷までの日数  AS lead_time
        FROM tb_rakuten_nokikanri
        ORDER BY 納期管理番号
EOD;
      $stmt = $dbMain->query($sql);
      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $result['results'][] = $row;
      }

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_UPDATE_RAKUTEN_NOKI_KANRI);
      $result['lastUpdated'] = $lastUpdated ? $lastUpdated->format('Y-m-d H:i:s') : null;

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 仕入先 注残一覧URL取得処理
   */
  public function getVendorOrderListUrlsAction(Request $request)
  {
    try {
      $result = [
          'status'    => 'ok'
        , 'message' => ''
        , 'results' => []
      ];

      /** @var BaseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');

      /** @var PurchasingAgent[] $agents */
      $agents = $repo->findBy([
      ], [
        'display_order' => 'ASC'
      ]);

      foreach($agents as $agent) {
        $result['results'][] = [
            'code' => $agent->getId()
          , 'name' => $agent->getName()
//          , 'url' => $this->generateUrl('plusnao_vendor_order_list_change_agent_name', [
//              'agentName' => $agent->getLoginName()
//          ])
          , 'url' => $this->generateUrl('plusnao_vendor_order_list', [
            'agentName' => $agent->getLoginName()
          ])
        ];
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  public function getYahooAgentListUrlsAction()
  {
    try {
      $result = [
          'status'  => 'ok'
        , 'message' => ''
        , 'results' => []
      ];

      /** @var SymfonyUserYahooAgentRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserYahooAgent');

      $agents = $repo->getActiveAccounts();

      foreach($agents as $agent) {

        $result['results'][] = [
            'code' => $agent->getShopCode()
          , 'name' => $agent->getClientName()
          , 'url' => $this->generateUrl('yahoo_product_list', [
            'shopCode' => $agent->getShopCode()
          ])
        ];
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品一覧取得
   * @param Request $request
   * @return JsonResponse
   */
  public function findProductListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
    ];

    try {
      $code = $request->get('code');
      $likeMode = $request->get('like_mode', 'forward');
      $limit = $request->get('limit', 100);

      $conditions = [
          'keyword' => $code
        , 'include_no_stock_product' => true
      ];

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $data = $repo->searchByDaihyoSyohinCode($conditions, $likeMode, $limit);

      $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $list = [];
      foreach($data as $row) {
        $row['image_url'] = TbMainproductsRepository::createImageUrl($row['image_p1_directory'], $row['image_p1_filename'], $imageUrlParent);
        unset($row['image_p1_directory']); // データ量削減
        unset($row['image_p1_filename']); // データ量削減
        $list[] = $row;
      }

      $result['list'] = $list;
      $result['count'] = count($data);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品SKU一覧取得
   * @param Request $request
   * @return JsonResponse
   */
  public function findProductSkuListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
    ];

    try {
      $code = $request->get('code');

      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $data = $repo->findByDaihyoSyohinCode($code);

      $list = [];
      foreach($data as $choice) {
        $row = $choice->toScalarArray('camel');
        $list[] = $row;
      }

      $result['list'] = $list;
      $result['count'] = count($data);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 商品SKU一件取得
   * @param Request $request
   * @return JsonResponse
   */
  public function findProductSkuOneAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'data' => null
    ];

    try {
      $code = $request->get('code');

      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems $choice */
      $choice = $repo->find($code);


      $result['data'] = $choice ? $choice->toScalarArray('camel') : null;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫一覧取得（Ajax）
   * @return JsonResponse
   */
  public function findWarehouseListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'warehouses' => []
    ];

    try {
      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $result['warehouses'] = $repo->getPullDown();

    } catch (\Exception $e) {
      $logger->error('倉庫プルダウンデータ取得でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫在庫一覧取得処理
   * @param Request $request
   * @return JsonResponse
   */
  public function findProductWarehouseStockListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'data' => null
    ];

    try {
      $daihyoSyohinCode = $request->get('daihyoSyohinCode');
      $warehouseId = $request->get('warehouseId');

      /** @var TbProductLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');
      $list = $repo->getProductWarehouseStockList($daihyoSyohinCode, $warehouseId);

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      $result['data'] = [];
      foreach($list as $row) {
        $item = [];
        foreach($row as $k => $v) {
          $item[$stringUtil->convertToCamelCase($k)] = $v;
        }
        $result['data'][] = $item;
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }



  /**
   * 超簡易 メルカリ用価格計算処理
   * @param Request $request
   * @return Response
   * @internal param $code
   * @internal param $profits
   */
  public function calculateSimplePriceAction(Request $request)
  {

    $result = [
        'status' => 'ok'
      , 'code' => null
      , 'profitRate' => null
      , 'chargeRate' => null
      , 'base_price' => null
      , 'fixed_price' => null
      , 'price_with_tax' => null
    ];

    try {

      $code = $request->get('c');
      $result['code'] = $code;

      $profitRate = intval($request->get('p', 30));
      $result['profitRate'] = $profitRate;

      if (!$code || !$profitRate) {
        throw new \RuntimeException('no code');
      }

      $chargeRate = intval($request->get('ch', 10));
      $result['chargeRate'] = $chargeRate;

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $sql = <<<EOD
          SELECT
              m.daihyo_syohin_code
            , cal.base_price AS base_price
            , COALESCE(tp.fixed, cal.base_price) AS fixed_price
            , TRUNCATE(COALESCE(tp.fixed, cal.base_price) * CAST(:taxRate AS DECIMAL(10, 2)), 0) AS price_with_tax
          FROM tb_mainproducts m
          INNER JOIN (
            SELECT
                cal.daihyo_syohin_code
              , TRUNCATE(
                  (
                    (
                        price.baika_genka
                      + IFNULL(s.price, 0)
                    ) / (100 - :profitRate) * 100
                  ) / (100 - :chargeRate) * 100
                , 0) AS base_price
            FROM tb_mainproducts_cal cal
            INNER JOIN tb_mainproducts m ON cal.daihyo_syohin_code = m.daihyo_syohin_code
            INNER JOIN v_product_price_base price ON cal.daihyo_syohin_code = price.daihyo_syohin_code
            LEFT JOIN tb_shippingdivision s ON m.送料設定 = s.id
            WHERE cal.deliverycode <> 4
          ) cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN v_product_price_base price ON m.daihyo_syohin_code = price.daihyo_syohin_code
          LEFT JOIN tax_price tp ON cal.base_price = tp.base
          WHERE m.daihyo_syohin_code = :code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':code', $code);

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);

      $stmt->bindValue(':profitRate', $profitRate);
      $stmt->bindValue(':chargeRate', $chargeRate);


      $stmt->execute();

      if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $result['base_price'] = $row['base_price'];
        $result['fixed_price'] = $row['fixed_price'];
        $result['price_with_tax'] = $row['price_with_tax'];
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new Response(sprintf('<html><body><pre>%s</pre></body></html>', print_r($result, true)));

    // return new JsonResponse($result);
  }

  /**
   * 入力無しログイン等
   * @param Request $request
   * @return Response
   */
  public function apiLoginAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'login' => 0
      , 'roles' => []
      , 'user' => null
    ];

    try {
      /** @var TokenStorageInterface $storage */
      $storage = $this->get('security.token_storage');
      $token = $storage->getToken();
      $user = $this->getLoginUser();

      if (!$token) {
        throw new \RuntimeException('invalid access.');
      }

      $logger->info(sprintf('API LOGIN: [%s] %s => %s', $request->getMethod(), $request->getClientIp(), $request->getHost()));
      $logger->info(sprintf('API LOGIN: %s', $user ? sprintf('already done as %s (%s) ', $user->getClientName(), get_class($user)) : '(none)' ));

      if (!$user) {
        $logger->info(sprintf('API LOGIN TYPE: %s', $request->get('t', '(none)')));

        // ログイン種別：pe => 商品編集者
        if ($request->get('t', '') === 'pe' && strlen($request->get('k', ''))) {

          // 一時トークンを確認し、一致しかつ有効期限内のトークンであればOK
          $providerKey = 'main';

          /** @var SymfonyUserProductEditorProvider $provider */
          $provider = $this->get('app.symfony_user_product_editor_provider');
          $user = $provider->loadUserByCakeUserByKey($request->get('k'));

          if ($user) {
            // エンティティとキーから認証トークンを作る
            $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());
            // 認証トークンをセット
            $storage->setToken($token);

            $logger->info(sprintf('API LOGIN: %s', sprintf('logged in as %s (%s) ', $user->getClientName(), get_class($user))));
          }
        }
      }

      if ($user) {
        $result['login'] = 1;
        foreach($user->getRoles() as $role) {
          $result['roles'][] = $role->getRole();
        }
        $result['user'] = $user->getClientName();
      }

    } catch (\Exception $e) {
      $logger->error(sprintf('API LOGIN ERROR: %s (%s)', $e->getMessage(), $request->getQueryString()));
      $result = [
        'status' => 'ng'
      ];
    }

    $logger->info(sprintf('API LOGIN RESULT: %s', print_r($result, true)));

    return new JsonResponse($result);
  }

  /**
   * Flashメッセージのセット
   * @param Request $request
   * @return JsonResponse
   */
  public function setFlashAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $message = $request->get('message');
      $type = $request->get('type');

      $this->setFlash($type, $message);

    } catch (\Exception $e) {
      $logger->error(sprintf('SET FLASH ERROR: %s (%s)', $e->getMessage(), $request->getQueryString()));
      $result = [
        'status' => 'ng'
      ];
    }

    return new JsonResponse($result);
  }



  /**
   * Job Request 処理
   * @param string $key
   * @param Request $request
   * @return Response
   */
  public function processJobRequestAction($key, Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'key' => $key
    ];

    try {

      $logger->info(sprintf('JOB REQUEST: [%s] %s => %s', $request->getMethod(), $request->getClientIp(), $request->getHost()));

      /** @var JobRequestRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:JobRequest');
      /** @var JobRequest $jobRequest */
      $jobRequest = $repo->find($key);
      if (!$jobRequest) {
        throw new \RuntimeException('no request key');
      }
      if (!$jobRequest->isValid()) {
        throw new \RuntimeException('no valid request key');
      }
      $logger->info(sprintf('JOB REQUEST KEY: [%s] %s %s', $jobRequest->getJobKey(), $jobRequest->getOperator(), $jobRequest->getProcess()));

      $resque = $this->getResque();

      // enqueue job
      $queued = null;
      switch ($jobRequest->getProcess()) {
        // 発注再計算
        case MainJob::COMMAND_KEY_RECALCULATE_PURCHASE_ORDER:
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = array_merge([
              'command' => MainJob::COMMAND_KEY_RECALCULATE_PURCHASE_ORDER
            , 'jobKey' => $jobRequest->getJobKey()
          ], $jobRequest->getOptionsArray());

          // リトライ設定（なし）
          $resque->enqueue($job);
          $queued = new \DateTime();
          break;
        // Export order list to excel
        case BaseJob::COMMAND_EXPORT_ORDER_LIST_TO_EXCEL:
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = array_merge([
              'command' => BaseJob::COMMAND_EXPORT_ORDER_LIST_TO_EXCEL
            , 'jobKey' => $jobRequest->getJobKey()
          ], $jobRequest->getOptionsArray());

          // リトライ設定（なし）
          $resque->enqueue($job);
          $queued = new \DateTime();
          break;

        // 納品書印刷待ち伝票一覧 再集計処理
        case BaseJob::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST:
          $job = new NonExclusiveJob();
          $job->queue = 'nonExclusive'; // キュー名
          $job->args = array_merge([
              'command' => BaseJob::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST
            , 'jobKey' => $jobRequest->getJobKey()
          ], $jobRequest->getOptionsArray());

          // リトライ設定（なし）
          $resque->enqueue($job);
          $queued = new \DateTime();
          break;

        default:
          break;
      }

      if ($queued) {
        $jobRequest->setQueued($queued);
        $jobRequest->setStatus(JobRequestRepository::STATUE_QUEUED);
        $em = $this->getDoctrine()->getManager('main');
        $em->flush();
      }

    } catch (\Exception $e) {
      $logger->error(sprintf('JOB REQUEST ERROR: %s (%s)', $e->getMessage(), $request->getQueryString()));
      $result = [
          'status' => 'ng'
        , 'message' => $e->getMessage()
      ];
    }

    $logger->info(sprintf('JOB REQUEST RESULT: %s', print_r($result, true)));

    return new JsonResponse($result);
  }

  /**
   * JobRequest 進捗取得処理
   * @param string $key
   * @return JsonResponse
   */
  public function jobRequestStatusAction($key)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'key' => $key
      , 'jobRequest' => null
    ];

    try {
      /** @var JobRequestRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:JobRequest');
      /** @var JobRequest $jobRequest */
      $jobRequest = $repo->find($key);
      $result['jobRequest'] = $jobRequest->toScalarArray('camel');
      $result['jobRequest']['info'] = $jobRequest->getInfoArray();
      // var_dump($result);die;
      if (!$jobRequest) {
        throw new \RuntimeException('no request key');
      }

    } catch (\Exception $e) {
      $result = [
          'status' => 'ng'
        , 'message' => $e->getMessage()
      ];
    }
    // $logger->dump($result);

    return new JsonResponse($result);
  }

  // ===================================================================
  // 内部メソッド
  // ===================================================================

  /**
   * NextEngine 在庫同期 棚卸CSVファイル取得処理
   * 最後の在庫動機処理と同じ時刻かそれ以降の物を1件だけ取得
   * @return SplFileInfo|null
   */
  private function getLastNextEngineInventoryCsvFile()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var DbCommonUtil $dbUtil */
    $dbUtil = $this->get('misc.util.db_common');

    // ファイル一覧取得
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');
    $dir = $fileUtil->getWebCsvDir() . '/NextEngineUpdateStock';

    // 最終処理日時取得
    $lastProcessed = $dbUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK);
    $logger->info('last processed: ' . ($lastProcessed ? $lastProcessed->format('Y-m-d H:i:s') : 'no process'));

    $finder = new Finder();
    $files = $finder->in($dir)->name('/NE_Inventory.*\.csv/')->files()->sort(
      function ($a, $b) {
        /**
         * @var \SplFileInfo $a
         * @var \SplFileInfo $b
         */
        return ($b->getMTime() - $a->getMTime());
      }
    )->filter(function($file) use ($lastProcessed) {
      /** @var SplFileInfo $file*/
      $result = true;
      if ( $lastProcessed
        && preg_match('/NE_Inventory_(\d{14})\.csv/', $file->getBasename(), $m)
      ) {
        $inventoryTime = $m[1];
        if (strcmp($lastProcessed->format('YmdHis'), $inventoryTime) > 0) {
          $result = false;
        }
      }
      return $result;
    });

    $it = $files->getIterator();
    $it->rewind();

    return $it->current();
  }


  /**
   * Amazon FBA出荷用CSVファイル取得処理
   * 直近より10件取得
   * @return SplFileInfo[]
   */
  private function getLastAmazonFbaOrderCsvFile()
  {
    // ファイル一覧取得
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');
    $dir = $fileUtil->getWebCsvDir() . '/Amazon/FBA';

    $finder = new Finder();
    $files = $finder->in($dir)->name('/amazon_fba_order.*\.csv/')->files()->sort(
      function ($a, $b) {
        /**
         * @var \SplFileInfo $a
         * @var \SplFileInfo $b
         */
        return ($b->getMTime() - $a->getMTime());
      }
    );

    // 取得ファイル数だけ取得
    $results = [];
    $i = 0;
    foreach ($files as $file) {
      $results[] = $file;
      $i++;
      if ($i > 10) {
        break;
      }
    }

    return $results;
  }


  // 現在利用しない。実装の参考のためにコメントアウトしてます。（Symfony2 および Doctrine2 になれるまで）
//  /**
//   * ログ記録処理
//   * @param Request $request
//   * @return Response
//   */
//  public function addLogAction(Request $request)
//  {
//    /** @var BatchLogger $logger */
//    $logger = $this->get('misc.util.batch_logger');
//
//    $log = new Entity\TbLog();
//    $log->setPc($request->get('PC'));
//    $log->setExecTitle($request->get('EXEC_TITLE'));
//    $log->setExecTimestamp($request->get('EXEC_TIMESTAMP'));
//    $log->setLogLevel($request->get('LOG_LEVEL'));
//    $log->setLogTitle($request->get('LOG_TITLE'));
//    $log->setLogSubtitle1($request->get('LOG_SUBTITLE1'));
//    $log->setLogSubtitle2($request->get('LOG_SUBTITLE2'));
//    $log->setLogSubtitle3($request->get('LOG_SUBTITLE3'));
//    // $log->setLogTimestamp($request->get('LOG_TIMESTAMP')); // DBのdefaultで保存
//    $log->setLogInterval($request->get('LOG_INTERVAL'));
//    $log->setLogElapse($request->get('LOG_ELAPSE'));
//
//    $logger->addDbLog($log);
//
//    return new Response(0);
//  }

  /**
   * キュー登録前チェック
   */
  public function checkPrintDiffAction(Request $request)
  {
    $result = [
        'message' => ''
      , 'warning' => true
      , 'notices' => []
    ];

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->get('misc.util.web_access');
    
    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->get('batch.mall_process.next_engine');

    // 在庫同期時にNEと比較
    $client = $webAccessUtil->getWebClient();
      
    // NEログイン・メインページへの遷移
    $webAccessUtil->neLogin($client, 'api', 'prod'); // 必要なら、アカウント名を追加して切り替える
    $neCount = $webAccessUtil->getNePrintCount($client); // NE側カウント取得
    $dbCount = $neMallProcess->getPrintCount();
    
    if ($dbCount !== $neCount ) {
      $result['message'] = "差分あり（NextEngine:".$neCount."、データベース:".$dbCount."）";
    } else {
      $result['message'] = "差分なし（NextEngine:".$neCount."、データベース:".$dbCount."）";
      $result['warning'] = false;
    }

    return new JsonResponse($result);
  }

}
