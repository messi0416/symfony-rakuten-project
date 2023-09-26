<?php

namespace AppBundle\Controller;

use BatchBundle\Command\CsvDownloadYahooProductsCommand;
use BatchBundle\Command\ExportCsvNextEngineCommand;
use BatchBundle\Command\ExportCsvRakutenCommand;
use BatchBundle\Command\ExportCsvYahooOtoriyoseCommand;
use BatchBundle\Job\MainJob;
use BatchBundle\Job\CheckEnvJob;
use BatchBundle\Job\NonExclusiveJob;
use BatchBundle\MallProcess\AmazonMallProcess;
use BatchBundle\MallProcess\ShoplistMallProcess;
use BatchBundle\MallProcess\WowmaMallProcess;
use BatchBundle\MallProcess\RakutenMallProcess;
use http\Exception\RuntimeException;
use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Entity\Repository\TbCronProcessScheduleRepository;
use MiscBundle\Entity\Repository\TbShoplistSpeedbinShippingDetailRepository;
use MiscBundle\Entity\Repository\TbYahooOtoriyoseAccessLogRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbCronProcessSchedule;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoplistSpeedbinShipping;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Service\MallYahooOtoriyoseService;
use MiscBundle\Service\ShoplistSpeedbinService;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\LockedException;

class DefaultController extends BaseController
{
  /**
   * ログイン画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function loginAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $authenticationUtils = $this->get('security.authentication_utils');
    $lastUsername = $authenticationUtils->getLastUsername();
    $error = $authenticationUtils->getLastAuthenticationError();
    $errorMessage = '';
    if ($error instanceof LockedException) {
      $errorMessage = 'このアカウントはロック中です。管理者にご連絡ください。';
    }
    elseif ($error instanceof BadCredentialsException) {
      if ($request->getSession()->get('errorCount') >= SymfonyUsers::LIMIT_ERROR_TIME) {
        $errorMessage = 'ログインエラー回数が既定の回数を超えたため、アカウントをロックします。管理者までご連絡ください。';
      } else {
        $errorMessage = 'パスワードが異なります。';
      }
    }

    // ログインアカウント一覧取得（プルダウン表示）
    /** @var SymfonyUsersRepository $repoUser
     */

    $em = $this->getDoctrine()->getManager('main');

    $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $users = $repoUser->getActiveAccountsNameAndCd();

    //ユーザIDとユーザの最終選択倉庫IDを取得
    $userWarehouseIds = $repoUser->getLoginAccountsWarehouseId();

    //倉庫のID取得
    /**
     * @var TbWarehouseRepository $repoUser
     */
    $repoWarehouseIds = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouseIds = $repoWarehouseIds->getWarehouse_Id();

    //倉庫IDを配列化　
    $arrayWarehouseId = [];
    foreach ($warehouseIds as $warehouseId) {
      array_push($arrayWarehouseId,$warehouseId['id']);
    }
    //すべてのアカウントの選択している倉庫が存在しなければ1(南京終倉庫ID)に変更
    foreach ($userWarehouseIds as $userWarehouseId) {
      if(!in_array($userWarehouseId['warehouse_id'],$arrayWarehouseId))
      {
        $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($userWarehouseId['id']);
        $repoUser->setWarehouseId('1');
        $em->flush();
      }
    }
    return $this->render('AppBundle:Default:login.html.twig', array(
        'last_username' => $lastUsername
      , 'errorMessage' => $errorMessage
      , 'users' => $users
      , 'usersJson' => json_encode($users)
    ));
  }

  /**
   * ユーザ名取得 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function getUsernameAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $userCd = $request->get('userCd');
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'userName' => null
    ];

    try {
      /** @var SymfonyUsersRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
      $userName = $repo->getUserName($userCd);
      if ($userName != null) {
        $result['userName'] = $userName[0]['username'];
      }
    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * トップページ（通知ページ）
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function indexAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    // 販売終了後の削除日数 未設定ならばデフォルト10日（NE登録商品数が溢れるため削除は必須）
    $delDaysFromSalesEnd = $commonUtil->getSettingValue('DEL_DAYS_FROM_SALES_END')
                         ? $commonUtil->getSettingValue('DEL_DAYS_FROM_SALES_END') : '10';

    $immediateShippingDateStr =  $commonUtil->getSettingValue('IMMEDIATE_SHIPPING_DATE');
    $immediateShippingDate = ($immediateShippingDateStr)
                           ? new \DateTime($immediateShippingDateStr)
                           : new \DateTime();

    // NextEngineCSV出力：原価・売価の差分を無視するか
    $ignorePriceDiff = $commonUtil->getSettingValue(TbSetting::KEY_NE_PRODUCT_IGNORE_PRICE_DIFF);

    // トップページ表示
    return $this->render('AppBundle:Default:index.html.twig', array(
        'account' => $this->getLoginUser()
      , 'resque'  => $this->getResque()
      , 'notificationSocketUrl' => $this->getParameter('app_notification_url')
      , 'notificationSocketPath' => $this->getParameter('app_notification_path')
      , 'isProduction' => $this->get('kernel')->getEnvironment() === 'prod'
      , 'delDaysFromSalesEnd' => $delDaysFromSalesEnd
      , 'immediateShippingDate' => $immediateShippingDate->format('Y-m-d')
      , 'ignorePriceDiff' => $ignorePriceDiff
    ));
  }

  /**
   * 在庫CSV取込処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueUpdateStockListAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command' => MainJob::COMMAND_KEY_IMPORT_STOCK_LIST
      , 'account' => $this->getLoginUser()->getId()
    ];

    // テスト環境ではNextEngineテスト環境からのダウンロードを行う。
    if ($this->get('kernel')->getEnvironment() === 'test') {
      $job->args['targetEnv'] = 'test';
    }

    // リトライ設定
    $retrySeconds = [];
    for ($i = 1; $i <= 12; $i++) {  // 5分刻みで12回
      $retrySeconds[] = 60 * 5;
    }

    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す TODO: 即時実行 or キュー待ち数など、情報を追加する。
    return new JsonResponse(['message' => '在庫データ取込処理をキューに追加しました。']);
  }

  /**
   * 受注CSV取込処理 キュー追加 (Ajaxアクセス)
   */
  public function queueUpdateOrderListAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'message' => ''
    ];

    try{
      $setting = $request->get('setting');

      // 即時実行時は実行期限なし
      //$setting['import_order_list_months'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['limit_time_hour'], 'as')));
      //$setting['limit_time_hour'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['limit_time_hour'], 'as')));
      //$setting['limit_time_minute'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['limit_time_minute'], 'as')));

      // nか月分+今月分
      $startDate = (new \DateTime())->modify(sprintf('-%d months', $setting['import_order_list_months']));
      $startDate->setDate($startDate->format('Y'), $startDate->format('m'), 1);
      $endDate = new \DateTime();
      //$limitTime = sprintf('%02d', intval($setting['limit_time_hour'])).':'.sprintf('%02d', intval($setting['limit_time_minute'])).':00';

      $resque = $this->getResque();

      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST
        , 'startDate' => $startDate->format('Y-m-d')
        , 'endDate'   => $endDate->format('Y-m-d')
        , 'account'   => $this->getLoginUser()->getId()
        //, 'limitTime' => $limitTime
      ];

      // テスト環境ではNextEngineテスト環境からのダウンロードを行う。
      if ($this->get('kernel')->getEnvironment() === 'test') {
        $job->args['targetEnv'] = 'test';
      }

      // リトライなし
      $resque->enqueue($job);
      $result['message'] = "受注明細取込処理をキューに追加しました。\n (取得範囲開始: " . $request->get('start-date') . ")";

    }catch(\Exception $e){
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }

    // 結果をJSONで返す TODO: 即時実行 or キュー待ち数など、情報を追加する。
    return new JsonResponse($result);
  }


  /**
   * 受注明細差分更新処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueUpdateOrderListIncrementalAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();
    $mode = $request->get('mode');
    $numbers = explode(',',$request->get('slipNumber'));

    if($mode === 'target'){
      foreach($numbers as $number){
        if(!empty($number) && $number !== ""){
          $job = new MainJob();
          $job->queue = 'main'; // キュー名

          $job->args = [
              'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
            , 'mode'      => $mode
            , 'number'    => $number
            , 'account'   => $this->getLoginUser()->getId()
          ];

          // リトライ設定
          $retrySeconds = [];
          for ($i = 1; $i <= 12; $i++) {  // 5分刻みで12回
            $retrySeconds[] = 60 * 5;
          }

          $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

          $resque->enqueue($job);
        }
      }
    }

    $job = new MainJob();
    $job->queue = 'main'; // キュー名

    $job->args = [
        'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
      , 'mode'      => $mode
      , 'account'   => $this->getLoginUser()->getId()
    ];

    // リトライ設定
    $retrySeconds = [];
    for ($i = 1; $i <= 12; $i++) {  // 5分刻みで12回
      $retrySeconds[] = 60 * 5;
    }

    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "受注明細差分更新のキューを追加しました。"]);
  }


  /**
   * 伝票毎利益再集計処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueAggregateSalesDetailAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $type = $request->get('type');
    $months = $request->get('months');
    $onlySave = $request->get('onlySave');

    if (($onlySave || $type === 'part') && !preg_match('/^0$|^[1-9][0-9]{0,2}$/', $months)) {
      $result['status'] = 'ng';
      $result['message'] = "指定期間が不正です。半角数値を入力してください。[${months}]";
      return new JsonResponse($result);
    }

    if ($onlySave) {
      /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $commonUtil->updateSettingValue(TbSetting::KEY_AGGREGATE_SALES_DETAIL_TYPE, $type);
      $commonUtil->updateSettingValue(TbSetting::KEY_AGGREGATE_SALES_DETAIL_MONTHS, $months);

      $result['status'] = 'ok';
      $result['message'] = '設定を保存しました。';
      return new JsonResponse($result);
    }

    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_AGGREGATE_SALES_DETAIL
      , 'account'   => $this->getLoginUser()->getId()
    ];
    if ($type === 'part') {
      if ($months === '0') {
        $job->args['startDate'] = (new \DateTime())->format('Y-m-01');
      } else {
        $job->args['startDate'] = (new \DateTime())->modify('-' . $months . ' month')->format('Y-m-01');
      }
    }

    // リトライなし
    $resque->enqueue($job);

    $result['status'] = 'ok';
    $result['message'] = '伝票毎利益再集計のキューを追加しました。';

    // 結果をJSONで返す
    return new JsonResponse($result);
  }

  /**
   * 伝票毎利益再集計設定 取得
   * @return JsonResponse
   */
  public function getAggregateSalesDetailSettingAction()
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'setting' => [],
    ];

    try {
      /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $result['setting'] = [
        'type' => $commonUtil->getSettingValue(TbSetting::KEY_AGGREGATE_SALES_DETAIL_TYPE),
        'months' => $commonUtil->getSettingValue(TbSetting::KEY_AGGREGATE_SALES_DETAIL_MONTHS),
      ];
    } catch (\Exception $e) {
      $logger->error('伝票毎利益再集計設定取得エラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 入出庫データCSV取込処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueUpdateStockInOutAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_IMPORT_STOCK_IN_OUT
      , 'account'   => $this->getLoginUser()->getId()
    ];

    // リトライ設定
    $retrySeconds = [];
    for ($i = 1; $i <= 12; $i++) {  // 5分刻みで12回
      $retrySeconds[] = 60 * 5;
    }

    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "入出庫データ取込処理をキューに追加しました。"]);
  }


  /**
   * 閲覧ランキングCSV取込処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueUpdateViewRankingAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_IMPORT_VIEW_RANKING
      , 'account'   => $this->getLoginUser()->getId()
    ];

//    $retrySeconds = [];
//    for ($i = 1; $i <= 6; $i++) {  // 30分刻みで6回
//      $retrySeconds[] = 60 * 30;
//    }
//    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "閲覧ランキング取込処理をキューに追加しました。"]);
  }


  /**
   * 楽天レビュー取込処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueUpdateRakutenReviewAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_IMPORT_RAKUTEN_REVIEW
      , 'account'   => $this->getLoginUser()->getId()
    ];

    // リトライ設定
    $retrySeconds = [];
    for ($i = 1; $i <= 12; $i++) {  // 5分刻みで12回
      $retrySeconds[] = 60 * 5;
    }
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "楽天レビュー取込処理をキューに追加しました。"]);
  }

  /**
   * Amazon在庫取込
   * @param Request $request
   * @return JsonResponse
   */
  public function queueImportAmazonStockAction(Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'          => MainJob::COMMAND_KEY_IMPORT_AMAZON_STOCK
      , 'account'          => $this->getLoginUser()->getId()
    ];

    // リトライなし
    $retrySeconds = [];
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "Amazon在庫取込処理をキューに追加しました。"]);
  }

  /**
   * ロケーション更新処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueRefreshLocationAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_REFRESH_LOCATION
      , 'account'   => $this->getLoginUser()->getId()
    ];
    // テスト環境ではNextEngineテスト環境からのダウンロードを行う。（在庫取込）
    if ($this->get('kernel')->getEnvironment() === 'test') {
      $job->args['targetEnv'] = 'test';
    }

    // リトライなし
    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "ロケーション更新処理をキューに追加しました。"]);
  }

  /**
   * ロケーション並べ替え処理 キュー追加 (Ajaxアクセス)
   * @return JsonResponse
   */
  public function queueSortLocationOrderAction()
  {
    $resque = $this->getResque();

    // 倉庫ロケーションを末尾へ一括移動
    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command' => MainJob::COMMAND_KEY_PRODUCT_LOCATION_SORT_ORDER
      , 'account'   => $this->getLoginUser()->getId()
    ];
    // $retrySeconds = [];
    // $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
    $resque->enqueue($job); // リトライなし

    // 結果をJSONで返す
    return new JsonResponse(['message' => "ロケーション並べ替え処理をキューに追加しました。"]);
  }


  /**
   * NextEngine CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvNextEngineAction(Request $request)
  {
    $resque = $this->getResque();

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    $commonUtil->updateSettingValue(TbSetting::KEY_NE_PRODUCT_IGNORE_PRICE_DIFF, $request->get('ignorePriceDiff'));
    if ($request->get('onlySave') ? true : false) {
      $result['message'] = '原価・売価の差分についての設定を保存しました。';
      return new JsonResponse(['error' => 0]);
    }

    // 最初に削除対象 販売終了日設定と販売開始日＆最終受注日を更新する。失敗したら処理中断
    $delDaysFromSalesEnd = $request->get('delDaysFromSalesEnd');
    if (preg_match('/^[0-9]+$/', $delDaysFromSalesEnd)) {
      $commonUtil->updateSettingValue('DEL_DAYS_FROM_SALES_END', $delDaysFromSalesEnd);
    } else {
      return new JsonResponse(['message' => "削除対象となる販売終了後の日数設定が不正です。", 'error' => 1]);
    }

    $result = array('message' => '');

    // 受注明細差分更新を実行する場合は、キューに追加
    if ($request->get('doUpdateOrderListIncremental')) {

      // キューに既に登録されていればスキップ
      if (!$this->findQueuesByCommandName($request->get('queue'), MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL)) {
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
            , 'account'  => $this->getLoginUser()->getId()
        ];
        $resque->enqueue($job); // リトライなし
        $result['message'] = '受注明細差分更新処理をキューに追加しました。';
      } else {
        $result['message'] = '受注明細差分更新処理は既にキューに追加されているため、キュー追加はスキップします。';
      }
    }

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'  => MainJob::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE
      , 'doUpload' => ($request->get('upload') ? true : false) // NextEngine アップロードフラグ
      , 'doDownload' => ($request->get('doDownload') ? true : false)
      , 'ignorePriceDiff' => ($request->get('ignorePriceDiff') ? true : false) // NextEngine 売上単価差分無視フラグ
      , 'account'  => $this->getLoginUser()->getId()
    ];

    // 本番環境でのみNextEngine本番環境へのアップロードを行う。
    if ($this->get('kernel')->getEnvironment() === 'prod') {
      $job->args['targetEnv'] = 'prod';
    }
    $resque->enqueue($job); // リトライなし

    // セット商品も出力する場合
    if ($request->get('createSetProduct')) {
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command'  => MainJob::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_SET_PRODUCT
          , 'doUpload' => ($request->get('upload') ? true : false) // NextEngine アップロードフラグ
          , 'account'  => $this->getLoginUser()->getId()
      ];
      if ($this->get('kernel')->getEnvironment() === 'prod') {
        $job->args['targetEnv'] = 'prod';
      }
      $resque->enqueue($job); // リトライなし
    }

    // 結果をJSONで返す
    $result['message'] .= 'NextEngine CSV出力処理をキューに追加しました。';
    return new JsonResponse($result);
  }

  /**
   * NextEngine 在庫同期処理
   */
  public function queueExportCsvNextEngineUpdateStockAction(Request $request)
  {
    $resque = $this->getResque();

    // 受注明細差分更新
    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL
      , 'account'  => $this->getLoginUser()->getId()
    ];
    $resque->enqueue($job); // リトライなし


    // Amazon 在庫取込 ※実行しすぎでAmazonに怒られる可能性。でもひとまずやれとのことでやる
    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'          => MainJob::COMMAND_KEY_IMPORT_AMAZON_STOCK
      , 'account'          => $this->getLoginUser()->getId()
    ];
    // リトライなし
    $retrySeconds = [];
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
    $resque->enqueue($job);


    // NextEngine在庫同期処理
    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'  => MainJob::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK
      , 'doUpload' => ($request->get('upload') ? true : false) // NextEngine アップロードフラグ
      , 'doDownload' => ($request->get('doDownload') ? true : false)
      , 'account'  => $this->getLoginUser()->getId()
    ];

    if ($this->get('kernel')->getEnvironment() === 'prod') {
      $job->args['targetEnv'] = 'prod';
    }

    // リトライなし
    /*
    $retrySeconds = [];
    for ($i = 1; $i <= 3; $i++) {  // 10分刻みで3回
      $retrySeconds[] = 60 * 10;
    }
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
    */

    $resque->enqueue($job);

    return new JsonResponse(['message' => "NextEngine 在庫同期処理をキューに追加しました。"]);

  }

  /**
   * YahooCSV出力 設定値取得Action
   */
  public function getYahooCsvSettingAction()
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'setting' => []
    ];

    try {
      // kawa-e-monの設定値取得
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $result['setting'] = $repo->findUnpublishedPageSettingByArray('kawaemon');
      
      // 優良配送対応フラグを取得
      /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $result['setting'][TbSetting::KEY_YAHOO_EXCELLENT_DELIVERY_AVAILABLE] = $commonUtil->getSettingValue(TbSetting::KEY_YAHOO_EXCELLENT_DELIVERY_AVAILABLE);
      
    } catch (\Exception $e) {
      $logger->error("Yahoo CSV出力設定値取得エラー：" . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * Yahoo 設定値更新Action
   * @param Request $request
   * @return JsonResponse
   */
  public function updateCsvYahooSettingAction(Request $request)
  {
    $result = [
        'status' => 'ok'
        , 'settings' => []
    ];

    try {
      $settingArray = $request->get('setting');

      /* 入力チェック */
      // 販売数量
      if (! $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY_DAYS]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY_DAYS])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY_DAYS]) < 0) {
            throw new ValidationException('ページ非公開設定の販売基準日数が正しくありません。');
      }
      if (! $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_QUANTITY]) < 0) {
            throw new ValidationException('ページ非公開設定の販売基準が正しくありません。');
      }

      // 販売金額
      if (! $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES_DAYS]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES_DAYS])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES_DAYS]) < 0) {
            throw new ValidationException('ページ非公開設定の売上基準日数が正しくありません。');
      }
      if (! $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_KAWA_E_MON_SALES]) < 0) {
            throw new ValidationException('ページ非公開設定の売上基準が正しくありません。');
      }
      if (! ($settingArray[TbSetting::KEY_YAHOO_EXCELLENT_DELIVERY_AVAILABLE] === '0'
          || $settingArray[TbSetting::KEY_YAHOO_EXCELLENT_DELIVERY_AVAILABLE] === '1')) {
        throw new ValidationException('優良配送設定が正しくありません。');
      }

      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $repo->updateSettings($settingArray);
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の保存に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * Yahoo CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvYahooAction(Request $request)
  {
    $resque = $this->getResque();

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    // 最初に即納予定日設定を更新する。失敗したら処理中断
    $date = $request->get('immediate_shipping_date');
    if (preg_match('/^(\d+)-(\d+)-(\d+)$/', $date, $match)) {
      $date = sprintf('%04d%02d%02d', $match[1], $match[2], $match[3]);
      $commonUtil->updateSettingValue('IMMEDIATE_SHIPPING_DATE', $date);

    } else {
      return new JsonResponse(['message' => "即納予定日設定が更新できませんでした。", 'error' => 1]);
    }

    // 出力対象ごとにキューを追加
    foreach($request->get('export_target') as $exportTarget) {

      // 在庫更新のみ
      if ($request->get('export_type') == 'stock') {

        // batch.export_csv_yahoo_update_stock
        $job= new NonExclusiveJob();
        $job->queue = 'nonExclusive'; // キュー名
        $job->args = [
            'command'          => MainJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
          , 'doUpload'         => ($request->get('upload') ? true : false)    // Yahoo アップロードフラグ
          , 'exportTarget'     => $exportTarget
          , 'exportCsvType'    => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_STOCK // 在庫CSV出力
          , 'account'          => $this->getLoginUser()->getId()
        ];

      } else {
        // 全て出力・更新
        // 削除が必要な場合には、CSVダウンロードタスクからの実行
        if ($request->get('make_delete')) {
          $job= new NonExclusiveJob();
          $job->queue = 'nonExclusive'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
            , 'doUpload'         => ($request->get('upload') ? true : false)    // Yahoo アップロードフラグ
            , 'exportTarget'     => $exportTarget
            , 'exportCsvType'    => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS // 商品CSV出力
            , 'account'          => $this->getLoginUser()->getId()
          ];

          // 削除しない場合には直接、CSV出力Job追加
        } else {
          $job = new MainJob();
          $job->queue = 'main'; // キュー名
          $job->args = [
              'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO
            , 'doUpload'         => ($request->get('upload') ? true : false)    // Yahoo アップロードフラグ
            , 'exportTarget'     => $exportTarget
            , 'account'          => $this->getLoginUser()->getId()
          ];
        }
      }

      $resque->enqueue($job);
    }

    // 結果をJSONで返す
    return new JsonResponse(['message' => "Yahoo CSV出力処理をキューに追加しました。"]);
  }

  /**
   * Yahooおとりよせ設定値取得Action
   */
  public function getCsvYahooOtoriyoseSettingAction()
  {

    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'setting' => []
    ];

    try {
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $settingsArray = $repo->findCsvYahooOtoriyoseSettingByArray();
    } catch (\Exception $e) {
      $logger->error("Yahooおとりよせ設定値取得エラー：" . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }
    $result['setting'] = $settingsArray;
    return new JsonResponse($result);
  }

  /**
   *Yahooおとりよせ設定値更新Action
   * @param Request $request
   * @return JsonResponse
   */
  public function updateCsvYahooOtoriyoseSettingAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
        , 'settings' => []
    ];

    try {
      $settingArray = $request->get('setting');

      /* 入力チェック */

      // 新着商品
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_DAYS]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_DAYS])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_DAYS]) < 0) {
        throw new \RuntimeException('商品登録後の日数が正しくありません。');
      }
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_PER]
          || ! preg_match('/^\d{1,2}\.\d{1}$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_PER])) {
        throw new \RuntimeException('商品登録後のPR率が正しくありません。');
      }
      // その他商品
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_OTHER_PER]
          || ! preg_match('/^\d{1,2}\.\d{1}$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_OTHER_PER])) {
            throw new \RuntimeException('その他商品のPR率が正しくありません。');
      }
      // 季節外商品
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_OFF_PER]
          || ! preg_match('/^\d{1,2}\.\d{1}$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_OFF_PER])) {
            throw new \RuntimeException('季節外商品のPR率が正しくありません。');
      }
      // 即納商品
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_READY_PER]
          || ! preg_match('/^\d{1,2}\.\d{1}$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_READY_PER])) {
            throw new \RuntimeException('即納商品のPR率が正しくありません。');
      }
      // 販売数量
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_DAYS]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_DAYS])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_DAYS]) < 0) {
            throw new \RuntimeException('販売数量の基準日数が正しくありません。');
      }
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_NUM]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_NUM])
          || ((int)$settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_NUM]) < 0) {
            throw new \RuntimeException('販売数量の基準個数が正しくありません。');
      }
      // 季節外商品
      if (! $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_PER]
          || ! preg_match('/^\d{1,2}\.\d{1}$/', $settingArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_PER])) {
            throw new \RuntimeException('販売数量が基準数以上の場合のPR率が正しくありません。');
      }

      /* DB UPDATE */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $settings = array();
      foreach($settingArray as $key => $val) {
        $setting = $repo->find($key);
        $setting->setSettingVal($val);
      }
      $this->getDoctrine()->getManager()->flush();

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の保存に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * Yahoo おとりよせ.com CSV出力処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueExportCsvYahooOtoriyoseAction(Request $request)
  {

    $resque = $this->getResque();

    // CSVダウンロードから
    if ($request->get('download')) {
      // CSVダウンロードからなのでnonExclusiveキュー
      $job = new NonExclusiveJob();
      $job->queue = 'nonExclusive'; // キュー名
      $job->args = [
          'command' => NonExclusiveJob::COMMAND_KEY_DOWNLOAD_CSV_YAHOO
        , 'doUpload' => ($request->get('upload') ? true : false)    // Yahoo アップロードフラグ
        , 'skipCommonProcess' => ($request->get('doCommonProcess') ? false : true)    // 共通処理「スキップ」フラグ（値が逆転する）
        , 'exportTarget' => ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
        , 'exportCsvType'    => CsvDownloadYahooProductsCommand::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS // 商品CSV出力
        , 'account' => $this->getLoginUser()->getId()
      ];

      $resque->enqueue($job);

      // ダウンロードなし（削除CSVなし）
    } else {
      $resque = $this->getResque();
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
        'command' => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE
        , 'doUpload' => ($request->get('upload') ? true : false)    // Yahoo アップロードフラグ
        , 'skipCommonProcess' => ($request->get('doCommonProcess') ? false : true)    // 共通処理「スキップ」フラグ（値が逆転する）
        , 'exportTarget' => ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
        , 'importPath' => null
        , 'account' => $this->getLoginUser()->getId()
      ];

      $resque->enqueue($job);
    }

    // 結果をJSONで返す
    return new JsonResponse(['message' => "YahooおとりよせCSV出力処理をキューに追加しました。"]);
  }

  /**
   * CSV入力 Yahoo おとりよせ.com 購買データCSVアップロード
   * @param Request $request
   * @return JsonResponse
   */
  public function uploadYahooOtoriyosePurchaseCsvAction(Request $request)
  {
    $result = [
      'message' => null,
      'error' => '',
      'forcible' => false
    ];

    try {
      $service = $this->container->get('misc.service.yahoo_otoriyose');

      $purchaseList = [];
      $files = $request->files->get('upload');
      $forceUpload = $request->get('forceUpload') === 'true';

      $deleteDateList = [];
      foreach ($files as $file) {
        // 行数確認
        $limit = 500;
        $dateAndHeaderRowCount = 2;
        if (! $forceUpload && $limit <= count(file($file->getPathname())) - $dateAndHeaderRowCount) {
          $result['forcible'] = true;
          $result['message'] = '一日分の購買データファイルではない可能性がありますがアップロードしますか？';
          throw new ValidationException('1ファイルに500件以上あります。');
        }

        $fp = fopen($file->getPathname(), 'rb');
        // フォーマット確認
        $firstLine = mb_convert_encoding(fgets($fp), "UTF-8", "SJIS-WIN");
        if (preg_match('/^(20\d{2})年([01][1-9])月([0-3][1-9])日 合算値/', $firstLine, $matches) !== 1) {
          if ($forceUpload) {
            // 一週間分以上用
            preg_match('/^(20\d{2})年([01][1-9])月([0-3][1-9])日/', $firstLine, $matches);
          } else {
            $result['forcible'] = true;
            $result['message'] = '一日分の購買データファイルではない可能性がありますがアップロードしますか？';
            throw new ValidationException('ファイルの一行目がフォーマットと違います。');
          }
        }
        $targetDate = new \DateTime(sprintf('%s/%s/%s', $matches[1], $matches[2], $matches[3]));

        // 削除してしまう可能性があるので先にヘッダーチェック
        $header = str_replace(["\n", "\r\n"], '', mb_convert_encoding(fgets($fp), "UTF-8", "SJIS-WIN"));
        $headers = explode(',', $header);
        if ($headers != MallYahooOtoriyoseService::$YAHOO_OTORIYOSE_PURCHASE_CSV_HEADERS) {
          throw new ValidationException('CSVファイルのヘッダーが違います。');
        }

        // 登録済みか確認
        /** @var TbYahooOtoriyoseAccessLogRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbYahooOtoriyoseAccessLog');
        if ($repo->isUploaded($targetDate)) {
          if ($forceUpload) {
            array_push($deleteDateList, $targetDate);
          } else {
            $result['forcible'] = true;
            $result['message'] = 'ファイルの内容で上書きしますか？';
            throw new ValidationException('既にアップロード済みのファイルです。');
          }
        }

        fclose($fp);

        // CSVから必要データを抽出
        $purchaseList = array_merge($purchaseList, $service->extractDataFromCsv($file, $headers, $targetDate));
      }

      if (! $purchaseList) {
        throw new ValidationException('取り込めるデータがありませんでした。');
      }

      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbYahooOtoriyoseAccessLog');
      if (count($deleteDateList) > 0) {
        $repo->deleteAllByDates($deleteDateList);
      }
      // アップロードファイルの日付と重複する日付は削除
      $repo->storeList($purchaseList);

      $result['message'] = 'Yahooおとりよせ 購買CSVファイルの取込を完了しました。';
    } catch (ValidationException $e) {
      $result['error'] = $e->getMessage();
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('Yahooおとりよせ 購買データCSV取り込み :'. $e->getMessage(). $e->getTraceAsString());
      $result['error'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }


  /**
   * Amazon CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvAmazonAction(Request $request)
  {
    $resque = $this->getResque();

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    // 最初に即納予定日設定を更新する。失敗したら処理中断
    $date = $request->get('immediate_shipping_date');
    if (preg_match('/^(\d+)-(\d+)-(\d+)$/', $date, $match)) {
      $date = sprintf('%04d%02d%02d', $match[1], $match[2], $match[3]);
      $commonUtil->updateSettingValue('IMMEDIATE_SHIPPING_DATE', $date);

    } else {
      return new JsonResponse(['message' => "即納予定日設定が更新できませんでした。", 'error' => 1]);
    }

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON
      , 'doUpload'         => boolval($request->get('upload'))    // Amazon アップロードフラグ
      , 'exportTarget'     => $request->get('export_target')
      , 'account'          => $this->getLoginUser()->getId()
    ];

    // リトライなし
    /*
    $retrySeconds = [];
    for ($i = 1; $i <= 3; $i++) {  // 10分刻みで3回
      $retrySeconds[] = 60 * 10;
    }
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
    */

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "Amazon CSV出力処理をキューに追加しました。"]);
  }

  /**
   * 楽天 motto-motto設定値取得Action
   */
  public function getCsvRakutenMottoSettingAction()
  {
    $result = [
      'status' => 'ok'
      , 'setting' => []
    ];

    try {
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      // 現時点では、laforestはmottoの各設定値+レビュー平均点数なので、laforestを指定
      $result['setting'] = $repo->findUnpublishedPageSettingByArray('laforest');
    } catch (\Exception $e) {
      $logger = $this->get('misc.util.batch_logger');
      $logger->error("楽天 motto-motto設定値取得エラー：" . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 楽天 motto-motto設定値更新Action
   * @param Request $request
   * @return JsonResponse
   */
  public function updateCsvRakutenMottoSettingAction(Request $request)
  {
    $result = [
      'status' => 'ok'
    ];

    try {
      $settingArray = $request->get('setting');

      /* 入力チェック */
      // 販売数量
      if (! $settingArray[TbSetting::KEY_MOTTO_QUANTITY_DAYS]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_MOTTO_QUANTITY_DAYS])
          || ((int)$settingArray[TbSetting::KEY_MOTTO_QUANTITY_DAYS]) < 0) {
            throw new ValidationException('ページ非公開設定の販売基準日数が正しくありません。');
      }
      if (! $settingArray[TbSetting::KEY_MOTTO_QUANTITY]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_MOTTO_QUANTITY])
          || ((int)$settingArray[TbSetting::KEY_MOTTO_QUANTITY]) < 0) {
            throw new ValidationException('ページ非公開設定の販売基準が正しくありません。');
      }

      // 販売金額
      if (! $settingArray[TbSetting::KEY_MOTTO_SALES_DAYS]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_MOTTO_SALES_DAYS])
          || ((int)$settingArray[TbSetting::KEY_MOTTO_SALES_DAYS]) < 0) {
            throw new ValidationException('ページ非公開設定の売上基準日数が正しくありません。');
      }
      if (! $settingArray[TbSetting::KEY_MOTTO_SALES]
          || ! preg_match('/^\d+$/', $settingArray[TbSetting::KEY_MOTTO_SALES])
          || ((int)$settingArray[TbSetting::KEY_MOTTO_SALES]) < 0) {
            throw new ValidationException('ページ非公開設定の売上基準が正しくありません。');
      }

      // (LaForest)
      // レビュー平均
      if (! $settingArray[TbSetting::KEY_LAFOREST_REVIEW_POINT]
          || ! is_numeric($settingArray[TbSetting::KEY_LAFOREST_REVIEW_POINT])
          || ((float)$settingArray[TbSetting::KEY_LAFOREST_REVIEW_POINT]) < 0
          || ((float)$settingArray[TbSetting::KEY_LAFOREST_REVIEW_POINT]) > 5) {
            throw new ValidationException('ページ非公開設定のレビュー平均点が正しくありません。');
      }

      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $repo->updateSettings($settingArray);
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の保存に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 楽天GOLD設定値取得
   */
  public function getCsvRakutenGoldSettingAction()
  {
    $result = [
      'status' => 'ok'
      , 'setting' => []
    ];

    try {
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $result['setting'] = $repo->findRakutenGoldSetting();
    } catch (\Exception $e) {
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('楽天Gold設定値取得エラー: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 楽天GOLD設定値更新Action
   * @param Request $request
   * @return JsonResponse
   */
  public function updateCsvRakutenGoldSettingAction(Request $request)
  {
    $result = [
      'status' => 'ok'
    ];

    try {
      $settingArray = $request->get('setting');
      /* 入力チェック */
      $this->validateRakutenGoldSetting($settingArray);

      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $repo->updateSettings($settingArray);
    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の更新に失敗しました。 ' . $e->getMessage();
    } catch (\Exception $e) {
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('楽天Gold設定値更新エラー: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = '設定の更新に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 楽天GOLD CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvRakutenGoldAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
    ];

    try {
      $title = '楽天GOLD CSV出力キュー追加';
      // 出力対象
      $shop = $request->get('shop');
      $csv = $request->get('csv');
      $settingArray = $request->get('setting');
      /* 入力チェック */
      $this->validateRakutenGoldSetting($settingArray);
  
      for ($i = 0; $i < count($shop); $i++) {
        $currentShop = $shop[$i];
        $logger->debug($title . ': 処理中店舗=' . $currentShop);
  
        $csvStr = implode(",", $csv);
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
          'command'        => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN_GOLD,
          'shop'           => $currentShop,
          'csv'            => $csvStr,
          'doUpload'       => $request->get('upload'),
          'aggregateDate'  => $settingArray[TbSetting::KEY_RAKUTEN_GOLD_AGGREGATE_DAYS],
          'minReviewPoint' => $settingArray[TbSetting::KEY_RAKUTEN_GOLD_MIN_REVIEW_POINT],
          'maxPetitPrice'  => $settingArray[TbSetting::KEY_RAKUTEN_GOLD_MAX_PETIT_PRICE],
          'account'        => $this->getLoginUser()->getId(),
        ];
  
        // リトライなし
        $retrySeconds = [];
        $resque = $this->getResque();
        $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
        $resque->enqueue($job);
      }

    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $title . 'に失敗しました。 ' . $e->getMessage();
    } catch (\Exception $e) {
      $logger->error($title . 'エラー: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $title . 'に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  private function validateRakutenGoldSetting($settingArray)
  {
    if (
      !preg_match('/^\d+$/', $settingArray[TbSetting::KEY_RAKUTEN_GOLD_AGGREGATE_DAYS])
      || ((int)$settingArray[TbSetting::KEY_RAKUTEN_GOLD_AGGREGATE_DAYS]) < 1
    ) {
      throw new ValidationException('集計期間が正しくありません。');
    }
    if (
      !is_numeric($settingArray[TbSetting::KEY_RAKUTEN_GOLD_MIN_REVIEW_POINT])
      || ((float)$settingArray[TbSetting::KEY_RAKUTEN_GOLD_MIN_REVIEW_POINT]) < 0
      || ((float)$settingArray[TbSetting::KEY_RAKUTEN_GOLD_MIN_REVIEW_POINT]) > 5
    ) {
      throw new ValidationException('レビュー下限が正しくありません。');
    }
    if (
      !preg_match('/^\d+$/', $settingArray[TbSetting::KEY_RAKUTEN_GOLD_MAX_PETIT_PRICE])
      || ((int)$settingArray[TbSetting::KEY_RAKUTEN_GOLD_MAX_PETIT_PRICE]) < 1
    ) {
      throw new ValidationException('プチプライス価格上限が正しくありません。');
    }
  }

  /**
   * Amazon.com CSV出力処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueExportCsvAmazonComAction(Request $request)
  {
    $resque = $this->getResque();

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON_COM
      , 'doUpload'         => boolval($request->get('upload'))    // Amazon アップロードフラグ
      , 'exportTarget'     => $request->get('export_target')
      , 'account'          => $this->getLoginUser()->getId()
    ];

    // リトライなし
    /*
    $retrySeconds = [];
    for ($i = 1; $i <= 3; $i++) {  // 10分刻みで3回
      $retrySeconds[] = 60 * 10;
    }
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
    */

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "Amazon.com CSV出力処理をキューに追加しました。"]);
  }

  /**
   * SHOPLIST CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvShoplistAction(Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_SHOPLIST
      , 'doUpload'         => boolval($request->get('upload'))    // アップロードフラグ
      , 'exportTarget'     => $request->get('export_target')
      , 'account'          => $this->getLoginUser()->getId()
    ];

    // リトライなし
    /*
    $retrySeconds = [];
    for ($i = 1; $i <= 3; $i++) {  // 10分刻みで3回
      $retrySeconds[] = 60 * 10;
    }
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
    */

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "SHOPLIST CSV出力処理をキューに追加しました。"]);
  }

  /**
   * NextEngineモール商品 CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvNeMallProductAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = ['status' => 'ok'];

    try {
      $title = 'NextEngineモール商品 CSV出力キュー追加';
      // 出力対象
      $shops = $request->get('shops');
      $isOnlyDiff = $request->get('isOnlyDiff');
      $doUpload = $request->get('doUpload');
  
      for ($i = 0; $i < count($shops); $i++) {
        $currentShop = $shops[$i];
        $logger->debug($title . ': 処理中店舗=' . $currentShop);
  
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
          'command'        => MainJob::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_MALL_PRODUCT,
          'shop'           => $currentShop,
          'isOnlyDiff'     => $isOnlyDiff,
          'doUpload'       => $doUpload,
          'account'        => $this->getLoginUser()->getId(),
        ];
        if ($this->get('kernel')->getEnvironment() === 'prod') {
          $job->args['targetEnv'] = 'prod';
        }

        // リトライなし
        $retrySeconds = [];
        $resque = $this->getResque();
        $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
        $resque->enqueue($job);
      }
    } catch (\Exception $e) {
      $logger->error($title . 'エラー: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $title . 'に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 楽天 CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvRakutenAction(Request $request)
  {
    $resque = $this->getResque();
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    // 最初に即納予定日設定を更新する。失敗したら処理中断
    $date = $request->get('immediate_shipping_date');
    if (preg_match('/^(\d+)-(\d+)-(\d+)$/', $date, $match)) {
      $date = sprintf('%04d%02d%02d', $match[1], $match[2], $match[3]);
      $commonUtil->updateSettingValue('IMMEDIATE_SHIPPING_DATE', $date);

    } else {
      return new JsonResponse(['message' => "即納予定日設定が更新できませんでした。", 'error' => 1]);
    }

    // SKU別楽天商品属性項目更新処理
    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
      'command' => MainJob::COMMAND_KEY_UPDATE_SKU_RAKUTEN_ATTRIBUTE,
    ];
    $resque->enqueue($job);

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');

    // 出力対象（RakutenPlusnao、motto-motto、LaForest、dolcissimo、gekipla）ごとに別々に登録 WEBからの実行は必ず手動ダウンロード
    $exportTargets = $request->get('export_target');
    $logger->debug("楽天CSV出力キュー追加： export_target=" . print_r($exportTargets, true));

    // 2回目は共通処理、楽天共通処理skip
    for ($i = 0; $i < count($exportTargets); $i++) {
      $currentTarget = $exportTargets[$i];
      $logger->debug("楽天CSV出力キュー追加： current target=" . $currentTarget);
      $importPath = '';
      $target = '';
      if ($currentTarget === 'rakutenPlusnao') {
        $importPath = $fileUtil->getWebCsvDir() . '/' . ExportCsvRakutenCommand::IMPORT_DOWNLOADED_PATH_RAKUTEN_PLUSNAO;
        $target = ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN;
      } else if ($currentTarget === 'rakutenMotto') {
        $importPath = $fileUtil->getWebCsvDir() . '/' . ExportCsvRakutenCommand::IMPORT_DOWNLOADED_PATH_RAKUTEN_MOTTO;
        $target = ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO;
      } else if ($currentTarget === 'rakutenLaforest') {
        $importPath = $fileUtil->getWebCsvDir() . '/' . ExportCsvRakutenCommand::IMPORT_DOWNLOADED_PATH_RAKUTEN_LAFOREST;
        $target = ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST;
      } else if ($currentTarget === 'rakutenDolcissimo') {
        $importPath = $fileUtil->getWebCsvDir() . '/' . ExportCsvRakutenCommand::IMPORT_DOWNLOADED_PATH_RAKUTEN_DOLCISSIMO;
        $target = ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO;
      } else if ($currentTarget === 'rakutenGekipla') {
        $importPath = $fileUtil->getWebCsvDir() . '/' . ExportCsvRakutenCommand::IMPORT_DOWNLOADED_PATH_RAKUTEN_GEKIPLA;
        $target = ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA;
      } else {
        return new JsonResponse(['status' => 'ng', 'message' => "出力対象が正しくありません"]);
      }

      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN
        , 'targetShop'      => $target
        , 'doUpload'         => ($request->get('upload') ? true : false)    // 楽天 アップロードフラグ
        , 'importPath'       => $importPath
        , 'account'          => $this->getLoginUser()->getId()
      ];
      if ($i >= 1) {
        $job->args['skipCommonProcess'] = 1;
        $job->args['skipRakutencommonProcess'] = 1;
      }

      // リトライなし
      $retrySeconds = [];
      $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);
      $resque->enqueue($job);
    }

    // 結果をJSONで返す
    return new JsonResponse(['message' => "楽天CSV出力処理をキューに追加しました。"]);
  }

  /**
   * 楽天 在庫更新CSV出力処理 キュー追加 (Ajaxアクセス)
   */
  public function queueExportCsvRakutenUpdateStockAction(Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPDATE_STOCK
      , 'doUpload'         => ($request->get('upload') ? true : false)    // 楽天 アップロードフラグ
      , 'account'          => $this->getLoginUser()->getId()
    ];

    // リトライなし
    $retrySeconds = [];
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "楽天在庫更新CSV出力処理をキューに追加しました。"]);
  }

  /**
   * 楽天 RPP除外CSV登録処理　即時実行
   */
  public function queueExportCsvRakutenRppExcludeAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
        , 'message' => ''
    ];

    try {
      $this->saveRakutenRppData($request->get('target'));

      $resque = $this->getResque();

      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_RAKUTEN_RPP_EXCLUDE
          , 'account'          => $this->getLoginUser()->getId()
      ];

      // リトライなし
      $retrySeconds = [];
      $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

      $resque->enqueue($job);
      $result['message'] = "楽天RPP除外CSV登録処理をキューに追加しました。";
    } catch (\Exception $e) {
      $logger->error('楽天RPP除外CSV登録処理をキューに追加する際、エラーが発生しました：' . $e->getMessage() . ':' . $e->getTraceAsString());
      $result = [
          'status' => 'ng'
          , 'message' => 'エラーが発生しました。' . $e->getMessage()
      ];
    }

    // 結果をJSONで返す
    return new JsonResponse($result);
  }

  /**
   * 楽天 RPP除外設定保存処理
   * @param Request $request
   */
  public function updateRakutenRppExcludeAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
        , 'message' => ''
    ];

    try {
      $this->saveRakutenRppData($request->get('target'));
      $result['message'] = "設定を保存しました";
    } catch (\Exception $e) {
      $logger->error('楽天RPP設定を保存する際、エラーが発生しました：' . $e->getMessage() . ':' . $e->getTraceAsString());
      $result = [
          'status' => 'ng'
          , 'message' => 'エラーが発生しました。' . $e->getMessage()
      ];
    }

    // 結果をJSONで返す
    return new JsonResponse($result);
  }

  /**
   * 画面から受け取った値を解析してデータを更新する。
   */
  private function saveRakutenRppData(String $targetDataStr) {

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    /** @var RakutenMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.rakuten');
    $daihyoSyohinCodeList = explode("\n", $targetDataStr);
    try {
      $dbMain->beginTransaction();
      $mallProcess->updateRppFlg($daihyoSyohinCodeList, $dbMain);
      $dbMain->commit();
    } catch (\Exception $e) {
      $dbMain->rollback();
      throw $e;
    }
  }

  /**
   * 楽天 RPP除外設定取得処理
   * @param Request $request
   */
  public function getRakutenRppExcludeListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
      SELECT
        daihyo_syohin_code
      FROM tb_rakuteninformation i
      WHERE i.rpp_flg = 1;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $dataStr = implode("\n", array_column($list, 'daihyo_syohin_code'));

    // 結果をJSONで返す
    return new JsonResponse(['status' => 'ok', 'message' => 'RPP対象商品を記載してください。記載のないものは除外されます', 'data' => $dataStr]);
  }

  /**
   * Amazon FBA出荷用CSV出力処理 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueExportCsvAmazonFbaOrderAction()
  {
    $resque = $this->getResque();

    $job = new NonExclusiveJob();
    $job->queue = 'nonExclusive'; // キュー名
    $job->args = [
        'command' => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON_FBA_ORDER
      , 'account' => $this->getLoginUser()->getId()
    ];

    // リトライなし
    $retrySeconds = [];
    $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "Amazon FBA出荷用CSV出力処理をキューに追加しました。"]);
  }

  /**
   * モール受注CSV変換 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueConvertMallOrderCsvAction(Request $request)
  {
    $result = [
      'error' => 0
      , 'message' => null
    ];

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    try {

      $shopCode = $request->get('shop_code');
      if (!$shopCode) {
        throw new \RuntimeException('モールが正しく指定されませんでした。');
      }

      $mallCode = $commonUtil->getMallCodeByNeMallId($shopCode);

      $resque = $this->getResque();

      // モールごとの変換キュー追加処理
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
        'command' => MainJob::COMMAND_KEY_CONVERT_MALL_ORDER_CSV_DATA
        , 'mallCode' => $mallCode
        , 'account' => $this->getLoginUser()->getId()
        , 'force' => $request->get('force', 0)
      ];
      $resque->enqueue($job); // リトライなし

      // リトライなし
      $retrySeconds = [];
      $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

      $resque->enqueue($job);

      $result['message'] = "モール受注CSV変換処理をキューに追加しました。";

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $result['error'] = 1;
      $result['message'] = $e->getMessage();
    }

    // 結果をJSONで返す
    return new JsonResponse($result);
  }



  /**
   * NextEngine在庫同期 棚卸CSVダウンロード処理
   */
  public function exportCsvNextEngineUpdateStockDownloadCsvAction(Request $request)
  {
    $path = $request->get('path');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $logger->info('csv download: ' . $path);

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');
    $webCsvDir = $fileUtil->getWebCsvDir();
    if (!$path || !file_exists($path) || !is_file($path) || !strpos($path, $webCsvDir) === false) {
      throw new \RuntimeException('no file.');
    }

    $downloadName = preg_replace('/\.csv$/', sprintf('_%s.csv', (new \DateTime())->format('Ymd')), basename($path));

    $response = new Response();
    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $downloadName));
    $response->setContent(file_get_contents($path));

    return $response;
  }

  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function uploadAmazonFbaOrderSessionCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('amazon fba order csv upload session csv: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
        'message' => null
      , 'info' => []
    ];

    try {

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->get('batch.mall_process.amazon');

      // ファイル変換、モール判定（ヘッダチェック）
      $csvFiles = $mallProcess->processUploadedCsvFiles($request->files->get('upload'));
      $logger->info(print_r($csvFiles, true));

      if (!$csvFiles) {
        throw new \RuntimeException('取り込めるデータがありませんでした。');
      }
      $expectedTypes = [
          AmazonMallProcess::CSV_TYPE_BUSINESS_SESSION
      ];
      foreach($expectedTypes as $type) {
        if (!isset($csvFiles[$type])) {
          throw new \RuntimeException(sprintf('CSVファイルが不足しているか、書式が正しくありません。[ %s ]', $type));
        }
      }

      $mallProcess->importBusinessSessionFiles($csvFiles, $result);

      // CSV更新処理 キュー追加
      return $this->queueExportCsvAmazonFbaOrderAction();

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['error'] = $e->getMessage();

      return new JsonResponse($result);
    }
  }

  /**
   * Amazon FBA出荷用CSVダウンロード処理
   * @param Request $request
   * @return Response
   */
  public function exportCsvAmazonFbaOrderDownloadCsvAction(Request $request)
  {
    $fileName = $request->get('name');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $logger->info('csv download: ' . $fileName);

    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.amazon');
    $files = $mallProcess->getLastAmazonFbaOrderCsvFile();

    $downloadFile = null;
    foreach ($files as $file) {
      if ($file->getBasename() === $fileName) {
        $downloadFile = $file;
        break;
      }
    }

    if (!$downloadFile) {
      throw new \RuntimeException('no file : [' . $fileName . ']');
    }

    $response = new Response();
    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $downloadFile->getBasename()));
    $response->setContent(file_get_contents($downloadFile->getPathname()));

    return $response;
  }


  /**
   * Amazon S&L出荷用CSV出力 在庫データアップロード
   * @param Request $request
   * @return JsonResponse
   */
  public function uploadAmazonSnlOrderStockCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('amazon snl order csv upload stock csv: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
        'error' => null
      , 'message' => null
      , 'info' => []
    ];

    try {

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->get('batch.mall_process.amazon');

      // ファイル変換、モール判定（ヘッダチェック）
      $csvFiles = $mallProcess->processUploadedCsvFiles($request->files->get('upload'));
      if (!$csvFiles) {
        throw new \RuntimeException('取り込めるデータがありませんでした。');
      }
      $expectedTypes = [
        AmazonMallProcess::CSV_TYPE_SNL_STOCK
      ];
      foreach($expectedTypes as $type) {
        if (!isset($csvFiles[$type])) {
          throw new \RuntimeException(sprintf('CSVファイルが不足しているか、書式が正しくありません。[ %s ]', $type));
        }
      }

      $mallProcess->importSnlStockFiles($csvFiles, $result);

      // CSV作成処理
      $mallProcess->exportSlnCsv();

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['error'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * Amazon S&L出荷用CSVダウンロード処理
   * @param Request $request
   * @return Response
   */
  public function exportCsvAmazonSnlOrderDownloadCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $dirName = $request->get('dir');
    $fileName = $request->get('name');

    $logger->info(sprintf('csv download: %s : %s', $dirName, $fileName));

    $fileNames = [
        AmazonMallProcess::CSV_NAME_SNL_NEXT_ENGINE_ORDER => 'ne'
      , AmazonMallProcess::CSV_NAME_SNL_AMAZON_UPLOAD_ADD => 'amazon_add'
      , AmazonMallProcess::CSV_NAME_SNL_AMAZON_UPLOAD_DELETE => 'amazon_delete'
    ];

    if (!isset($fileNames[$fileName])) {
      throw new \RuntimeException('invalid file name');
    }

    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.amazon');
    $dirs = $mallProcess->getLastSnlOrderCsvFile();

    $downloadFile = null;
    if (isset($dirs[$dirName])) {
      $key = $fileNames[$fileName];
      if (isset($dirs[$dirName][$key])) {
        $downloadFile = $dirs[$dirName][$key];
      }
    }

    if (!$downloadFile) {
      throw new \RuntimeException(sprintf('no file : %s %s', $dirName, $fileName));
    }

    $response = new Response();
    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', basename($downloadFile)));
    $response->setContent(file_get_contents($downloadFile));

    return $response;
  }



  /**
   * SHOPLIST スピード便出荷用CSV生成用　集計実行
   */
  public function aggregateShoplistSpeedbinDeliveryAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'message' => null
      , 'info' => []
    ];
    try {
      // 最低保管数量更新
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $commonUtil->updateSettingValue(TbSetting::KEY_SHOPLIST_SPEEDBIN_KEEP_STOCK, $request->get('keepStock'));
      
      
      // SHOPLISTスピード便出荷データを登録
      $shoplistSpeedbinShipping = new TbShoplistSpeedbinShipping();
      $shoplistSpeedbinShipping->setStatus(TbShoplistSpeedbinShipping::STATUS_NONE);
      $shoplistSpeedbinShipping->setCreateUserId($this->getLoginUser()->getId());
      $shoplistSpeedbinShipping->setCreated(new \DateTime());     
      $em = $this->getDoctrine()->getManager('main');
      $em->persist($shoplistSpeedbinShipping);
      $em->flush();
      $id = $shoplistSpeedbinShipping->getId();
      
      // 生成したIDをパラメータにコマンドを起動
      $resque = $this->getResque();
      
      // モールごとの変換キュー追加処理
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
        'command' => MainJob::COMMAND_KEY_AGGREGATE_SHOPLIST_SPEEDBIN_DELIVERY
        , 'account' => $this->getLoginUser()->getId()
        , 'targetId' => $id
        , 
      ];
      $resque->enqueue($job); // リトライなし      
      $result['message'] = "SHOPLISTスピード便出荷用CSV生成用集計処理をキューに追加しました。";
      
      
    } catch (\Exception $e) {
      $logger->error("SHOPLISTスピード便出荷用CSV生成用集計キュー追加: $e");
      $result['error'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * SHOPLISTからダウンロードした確定ファイルテンプレートCSVをアップロードし、発注番号と発注数を取り込む。
   */
  public function uploadShoplistSpeedbinApprovedCsvAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');
    $id = $request->get('id');
    
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    
    $uploadedFile = null;
    try {
      foreach ($request->files as $file) {
        if ($file instanceof UploadedFile && ($file->getMimeType() == 'text/plain' || strpos($file->getMimeType(), 'csv') !== false)) {
          $uploadedFile = $file;
        }
      }
      if (!$uploadedFile) {
        throw new ValidationException('CSVファイルが正しくアップロードされませんでした。');
      }
        
      // SJIS => UTF-8
      $tmpFile = tmpFile();
      $fp = fopen($uploadedFile->getPathname(), 'rb');
      while ($line = fgets($fp)) {
        $line = mb_convert_encoding(trim($line), 'UTF-8', 'SJIS-win') . "\n";
        fputs($tmpFile, $line);
      }
      
      $meta = stream_get_meta_data($tmpFile);
      $tmpFileName = $meta['uri'];
      if (!$tmpFileName) {
        throw new \RuntimeException('一時ファイルの作成に失敗しました。');
      }
      
      // 書式確認
      fseek($tmpFile, 0);
      $headers = fgetcsv($tmpFile, null, ',', '"', '"');
      if ($headers != array_values(ShoplistSpeedbinService::$CSV_FIELDS_FIXED)) {
        throw new ValidationException('CSVファイルの書式が違います');
      }
      
      // 値を読み込んでデータ更新
      $service = $this->container->get('misc.service.shoplist_speedbin');
      fseek($tmpFile, 0);
      $service->importShoplistFixedCsv($id, $tmpFile);
      
    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("スピード便確定ファイルアップロードエラー: $e");
      $result['status'] = 'ng';
      $result['message'] = 'スピード便確定ファイルアップロードに失敗しました。' . $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * SHOPLIST スピード便出荷用CSVダウンロード処理。
   * 通常はトップページから呼ばれるが、type: labelFromTransport だけは移動伝票から呼ばれるので注意
   * @param Request $request
   * @return Response
   */
  public function downloadShoplistSpeedbinDeliveryCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var TbShoplistSpeedbinShippingDetailRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistSpeedbinShippingDetail');

    $id = $request->get('id');
    $type = $request->get('type');
    $shippingDate = $request->get('shippingDate');
    $headers = '';
    $list = null;
    $fileName = '';
    if ($type == 'planned') { // 納品予定ファイル
      $headers = ShoplistSpeedbinService::$CSV_FIELDS_PLANNED;
      $list = $repo->findPlannedCsvData($id);
      $fileName = sprintf('shoplist_speedbin_planned_%s.csv', (new \DateTime())->format('YmdHis'));
    } else if ($type == 'fixed') { // 確定ファイル
      $headers = ShoplistSpeedbinService::$CSV_FIELDS_FIXED;
      $list = $repo->findFixedCsvData($id, new \DateTime($shippingDate));
      $fileName = sprintf('shoplist_speedbin_approved_%s.csv', (new \DateTime())->format('YmdHis'));
    } else if ($type == 'label') {
      $headers = ShoplistSpeedbinService::$CSV_SHIPPING_REPORT;
      $list = $repo->findLabelCsvData($id);
      $fileName = sprintf('shoplist_speedbin_label_%s.csv', (new \DateTime())->format('YmdHis'));
    } else if ($type == 'labelFromTransport') { // 移動伝票から呼ばれる。
      /** @var ShoplistSpeedbinService $service */
      $service = $this->get('misc.service.shoplist_speedbin');
      $headers = ShoplistSpeedbinService::$CSV_SHIPPING_REPORT;
      $list = $service->findLabelCsvDataByTransportId($id);
      $fileName = sprintf('shoplist_speedbin_label_%s.csv', (new \DateTime())->format('YmdHis'));
    } else {
      throw new ValidationException("指定された形式が不正です[$type]");
    }
    
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($list, $stringUtil, $headers) {
        $file = new \SplFileObject('php://output', 'w');
        $eol = "\r\n";
        
        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers), ",") . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $file->fwrite($header);
        flush();
        
        foreach($list as $index => $row) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers, $headers, ",") . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
          $file->fwrite($line);
          flush();
        }
      }
    );
    
    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
    $response->send();
    
    return $response;
  }

  /**
   * ラベルCSVダウンロード
   * ・ normal: ラベル屋さん用CSV出力 バーコード付き
   * ・ realShop: 実店舗用
   *
   * @param Request $request
   * @return StreamedResponse
   */
  public function downloadLabelCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      $data = $request->get('data');

      // $logger->info(print_r($data, true));

      $type = $request->get('type');

      if (!$data || !is_array($data)) {
        throw new \RuntimeException('データが正しく渡されませんでした。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $codeList = [];
      foreach($data as $row) {
        $codeList[] = $dbMain->quote($row['code'], \PDO::PARAM_STR);
      }
      $codeListStr = implode(',', $codeList);
      if (!$codeListStr) {
        throw new \RuntimeException('商品コードが渡されませんでした。');
      }

      // 実店舗用
      if ($type == 'realShop') {
        $sql = <<<EOD
          SELECT
              pci.ne_syohin_syohin_code
            , code.id AS product_code
            , pci.colname
            , pci.rowname
            , i.baika_tanka
          FROM tb_productchoiceitems           pci
          LEFT JOIN tb_product_code            code ON pci.ne_syohin_syohin_code = code.ne_syohin_syohin_code
          LEFT JOIN tb_real_shop_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          WHERE pci.ne_syohin_syohin_code IN ( {$codeListStr} )
          ORDER BY pci.ne_syohin_syohin_code
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();

        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // ヘッダ
        $headers = [
            'ne_syohin_syohin_code' => '品番'
          , 'barcode'               => '商品コード'
          , 'colname'               => '横軸'
          , 'rowname'               => '縦軸'
          , 'baika_tanka_taxed'     => '販売価格'
          , 'baika_tanka'           => '本体価格'
        ];

        // 通常
      } else {

        $sql = <<<EOD
        SELECT
            CONCAT(
                T.メール便送料無料マーク
              , T.ne_syohin_syohin_code
            )                       AS label1
          , T.備考2                 AS label2
          , T.daihyo_syohin_label   AS label3
          , T.ディレクトリ           AS label4
          , T.product_code
          , T.ne_syohin_syohin_code
        FROM (
          SELECT
              (
                CASE
                  WHEN (cal.label_remark_flg <> 0)  THEN '■'
                  ELSE ''
                END
              ) AS メール便送料無料マーク
            , pci.ne_syohin_syohin_code
            , CONCAT(
                '['
                , pci.colname
                , '] '
                , '['
                , pci.rowname
                , '] '
                , '['
                , m.sire_code
                , ']'
              ) AS 備考2
            , cal.daihyo_syohin_label
            , CONCAT(
                  dir.フィールド2
                , '>'
                , dir.フィールド3
                , '>'
                , dir.フィールド4
                , '>'
                , dir.フィールド5
                , '>'
                , dir.フィールド6
              ) AS ディレクトリ
            , COALESCE(code.id, '') AS product_code
          FROM tb_productchoiceitems           pci
          LEFT JOIN tb_mainproducts_cal        cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
          LEFT JOIN tb_mainproducts            m   ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          LEFT JOIN tb_plusnaoproductdirectory dir ON m.NEディレクトリID = dir.NEディレクトリID
          LEFT JOIN tb_product_code            code ON pci.ne_syohin_syohin_code = code.ne_syohin_syohin_code
          WHERE pci.ne_syohin_syohin_code IN ( {$codeListStr} )
          ORDER BY pci.ne_syohin_syohin_code
        ) T
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();

        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // ヘッダ
        $headers = [
            'label1' => 'label1'
          , 'label2' => 'label2'
          , 'label3' => 'label3'
          , 'label4' => 'label4'
          , 'barcode' => 'barcode'
        ];
      }



      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($list, $data, $stringUtil, $headers, $logger) {
          $file = new \SplFileObject('php://output', 'w');
          $eol = "\r\n";

          // ヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
          $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
          // $file->fwrite($header); // ヘッダは出力しない

          foreach($list as $index => $row) {

            // 枚数を取得。無ければ1枚
            $num = 1;
            foreach($data as $item) {
              if ($row['ne_syohin_syohin_code'] == $item['code']) {
                $num = intval($item['num']);
                break;
              }
            }

            // バーコード作成
            $row['barcode'] = strlen($row['product_code']) ? $stringUtil->convertNumToJan13($row['product_code']) : '';

            if (isset($row['baika_tanka'])) {
              $row['baika_tanka_taxed'] = floor($row['baika_tanka'] * (100 + DbCommonUtil::CURRENT_TAX_RATE_PERCENT) / 100);
            }

            for($i = 0; $i < $num; $i++) {
              $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
              $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

              // 最終行は、改行コード不要。
              if ($index == (count($list) - 1) && $i == ($num - 1)) {
                $line = str_replace($eol, '', $line);
              }

              $file->fwrite($line);
            }

            flush();
          }
        }
      );

      $fileName = sprintf('label_%s.csv', (new \DateTime())->format('Ymd_His'));

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
      $response->send();

      return $response;

    } catch (\Exception $e) {
      // エラー時
      $logger->error($e->getMessage());
      $this->addFlash('danger', 'label csv error.');

      if ($request->get('redirect')) {
        return $this->redirect($request->get('redirect'));
      } else {
        return $this->redirectToRoute('homepage');
      }
    }
  }


  /**
   * Wowma ロットナンバーCSV　アップロード
   * @param Request $request
   * @return JsonResponse
   */
  public function uploadWowmaLotNumberCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('wowma lot number csv upload csv: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
         'message' => null
      , 'info' => []
    ];

    try {

      /** @var WowmaMallProcess $mallProcess */
      $mallProcess = $this->get('batch.mall_process.wowma');

      // ファイル変換、モール判定（ヘッダチェック）
      $csvFiles = $mallProcess->processUploadedCsvFiles($request->files->get('upload'));
      $logger->info(print_r($csvFiles, true));

      if (!$csvFiles) {
        throw new \RuntimeException('取り込めるデータがありませんでした。');
      }
      $expectedTypes = [
        WowmaMallProcess::CSV_TYPE_LOT_NUMBER
      ];
      foreach($expectedTypes as $type) {
        if (!isset($csvFiles[$type])) {
          throw new \RuntimeException(sprintf('CSVファイルが不足しているか、書式が正しくありません。[ %s ]', $type));
        }
      }

      $mallProcess->importLotNumberCsv($csvFiles);

      // 結果をJSONで返す
      return new JsonResponse(['message' => "Wowma ロットナンバーファイル取込を完了しました。"]);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['error'] = $e->getMessage();

      return new JsonResponse($result);
    }
  }

  /**
   * CSV出力 PPM 画像削除用 CSV出力
   * 深夜のバッチ処理でCSVは作成される
   */
  public function getDeleteImagesCsvPpmAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try{
      $now = new \DateTime();
      if($now->format('h') == 23 || $now->format('h') == 0) {
        throw new \RuntimeException('23,0時はCSVファイル更新のため実行できません。');
      }

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      $exportDir = $fileUtil->getWebCsvDir() . '/Ppm/Downloaded';
      $files = scandir($exportDir, SCANDIR_SORT_DESCENDING);

      if(count($files) === 2) { // . と ..の分で2個
        throw new \RuntimeException('ファイルが存在しません。');
      }

      $fileName = $files[0]; // 最新ファイル
      $filePath = sprintf('%s/%s', $exportDir, $fileName);
      $response = $fileUtil->downloadFile($filePath);
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $response = new Response($e->getMessage(), 500);
    }

    return $response;
  }

  public function updateIsUploadImagePpmAction()
  {
    $result = [
      'status' => 'ok'
      , 'updateRowNum' => 0
    ];

    try {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $sql = <<<EOD
      UPDATE tb_ppm_information ppm
      INNER JOIN product_images img ON ppm.daihyo_syohin_code = img.daihyo_syohin_code
      SET ppm.is_uploaded_images = 0
      WHERE ppm.is_sold = 0
      AND ppm.is_uploaded_images = -1
      AND ppm.`商品画像URL` <> ''
EOD;
      $stmt = $dbMain->query($sql);
      $result['updateRowNum'] = $stmt->rowCount();
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 外部倉庫在庫取得設定 取得
   */
  public function getExternalWarehouseStockFetchSettingAction()
  {
    $result = [
      'status' => 'ok'
      , 'settings' => []
    ];

    try {
      /** @var TbCronProcessScheduleRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
      $schedules = $repo->findStockImportCodeList();

      foreach($schedules as $schedule) {
        $result['settings'][] = $schedule->toScalarArray();
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

    /**
     * 倉庫間箱移動設定 取得
     */
    public function getExternalWarehouseBoxMoveSettingAction()
    {
        $result = [
            'status' => 'ok'
            , 'setting' => []
        ];

        try {
            /** @var TbCronProcessScheduleRepository $repo */
            $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
            $schedules = $repo->findExternalWarehouseBoxMoveSetting();
            $em = $this->getDoctrine()->getManager('main');
            if (count($schedules) == 0) {
                $schedules = new TbCronProcessSchedule();
                $schedules->setCode('WAREHOSE_BOX_MOVE_SETTING');
                $schedules->setType('warehouse_box_move_setting');
                $schedules->setName('倉庫間箱移動');
                $schedules->setHours('');
                $schedules->setMinutes('');
                $em->persist($schedules);
                $em->flush();
                $schedules = $repo->findExternalWarehouseBoxMoveSetting();
            }
            foreach($schedules as $schedule) {
                $result['setting'][] = $schedule->toScalarArray();
            }

        } catch (\Exception $e) {
            $result['status'] = 'ng';
            $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
        }

        return new JsonResponse($result);
    }

    /**
     * 受注明細取込設定 取得
     */
    public function getOrderListSettingAction()
    {
        $result = [
            'status' => 'ok'
            , 'setting' => []
        ];

        try {
            /** @var TbCronProcessScheduleRepository $repo */
            $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
            // 全データ取得
            $schedules = $repo->findOrderListSetting();
            $em = $this->getDoctrine()->getManager('main');

            // 一つでも曜日がなければ
            if(count($schedules) != 7){
              $gotDayOfTheWeek = [];
              foreach ($schedules as $schedule){
                $gotDayOfTheWeek[] = $schedule->getCode();
              }

              // ないものは初期設定を登録
              foreach (TbCronProcessScheduleRepository::$ORDER_LIST_SETTING_LIST as $dayOfTheWeek) {
                if(!in_array($dayOfTheWeek,$gotDayOfTheWeek)){
                  $schedule = new TbCronProcessSchedule();
                  $schedule->setCode($dayOfTheWeek);
                  $schedule->setType('order_list');
                  $schedule->setName('受注取込明細');
                  $schedule->setHours('03');
                  $schedule->setMinutes('15');
                  $schedule->setImportOrderListMonths('3');
                  $schedule->setLimitTimeHour('05');
                  $schedule->setLimitTimeMinute('00');
                  $em->persist($schedule);
                  $em->flush();
                }
              }
              $schedules = $repo->findOrderListSetting();
            }

            foreach($schedules as $schedule) {
              $dayOfTheWeek = explode('_',$schedule->getCode());
              $result['setting'][mb_strtolower(array_pop($dayOfTheWeek))] = $schedule->toScalarArray();
            }
        } catch (\Exception $e) {
            $result['status'] = 'ng';
            $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
        }

        return new JsonResponse($result);
    }
    
    /**
     * 現在と過去1ヵ月の平均明細数を取得し、JSONを返却する。
     * @param Request $request
     */
    public function getDetailNumAverageAction(Request $request) {
      $result = [
        'status' => 'ok'
        , 'currentDetailNum' => ''
        , 'pastDetailNum' => ''
      ];
      
      try {
        /** @var TbSalesDetailAnalyzeRepository $aRepo */
        $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');
        /** @var TbShippingVoucherRepository $sRepo */
        $sRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
        
        // 検索期間を取得
        $date = new \DateTime();
        $todayStr = $date->format('Y-m-d');
        $pickingDateFromStr = $date->modify('-1 month')->format('Y-m-d');
        $condition = [
          'pickingDateFrom' => $pickingDateFromStr
          , 'pickingDateTo' => $todayStr
        ];
        
        // 現在の平均明細数
        $currentDetail = $aRepo->getDetailNumAverage();
        // 過去の平均明細数
        $pastDetail = $sRepo->getDetailNumAverage($condition);
        $result['currentDetailNum'] = $currentDetail['detail_average'];
        $result['pastDetailNum'] = $pastDetail['detail_average'];
      } catch (\Exception $e) {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');
        $logger->error("平均明細数取得でエラー発生" . $e->getMessage() . $e->getTraceAsString());
        $result['status'] = 'ng';
        $result['message'] = '設定の取得に失敗しました。 ' . $e->getMessage();
      }
      return new JsonResponse($result);
    }

    /**
     * 倉庫間箱移動設定 更新
     * @param Request $request
     * @return JsonResponse
     */
    public function updateExternalWarehouseBoxMoveSettingAction(Request $request)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');

        $result = [
            'status' => 'ok'
            , 'settings' => []
        ];

        try {
            $setting = $request->get('setting');

            // 入力チェック
            // 一応補正しておく
            $setting['hour'] = preg_replace('/\s+/', '', mb_convert_kana($setting['hours'], 'as'));
            $setting['minutes'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['minutes'], 'as')));
            $setting['active'] = (boolean)($setting['active']) ? -1 : 0;

            if ($setting['active'] !== 0) {
                if (!strlen($setting['hours'])) {
                    throw new \RuntimeException(sprintf('%s の「時」が入力されていません。', $setting['name']));
                }
            }

            $hours = explode(',', $setting['hours']);
            foreach($hours as $hour) {
                if (!preg_match('/^\d{1,2}$/', $hour)) {
                    throw new \RuntimeException(sprintf('%s の「時」が正しくありません。', $setting['name']));
                }
                if ($hour < 0 || $hour > 23) {
                    throw new \RuntimeException(sprintf('%s の「時」は0～23で設定してください。', $setting['name']));
                }
            }
            if (count($hours) != count(array_unique($hours))) {
                throw new \RuntimeException(sprintf('%s の「時」に重複があります。', $setting['name']));
            }

            if ($setting['minutes'] < 0 || $setting['minutes'] > 59) {
                throw new \RuntimeException(sprintf('%s の「分」は0～59で設定してください。', $setting['name']));
            }

            if ($setting['stocks'] < 0) {
                throw new \RuntimeException(sprintf('%s の「在庫数」は入力してください。', $setting['name']));
            }

            /** @var TbCronProcessScheduleRepository $repo */
            $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
            $schedules = $repo->findExternalWarehouseBoxMoveSetting();

            $em = $this->getDoctrine()->getManager('main');
            if (isset($schedules[$setting['code']])) {
                $cmd = "";
                $active = $setting['active'];

                switch($setting['code']){
                    case 'WAREHOSE_BOX_MOVE_SETTING' :
                        $cmd = 'batch.warehouse_box_move';
                        break;
                    default :
                        $active = 0;
                        break;
                }

                $schedule = $schedules[$setting['code']];

                $schedule->setHours($setting['hours']);
                $schedule->setMinutes(sprintf('%02d', intval($setting['minutes'])));
                $schedule->setActive($setting['active']);
                $schedule->setStocks($setting['stocks']);
                $schedule->setMagnificationPercent($setting['magnification_percent']);
                $schedule->setOrderDate($setting['order_date']);
                $schedule->setArrivalDate($setting['arrival_date']);
                $schedule->setCommand($cmd);
            }

            // crontab 出力
            /** @var \Twig_Environment $twig */
            $twig = $this->get('twig');
            $cronTemplate = $twig->load('AppBundle:Default:misc/cron_process_move_warehouse_schedule.cron.twig');

            /** @var FileUtil $fileUtil */
            $fileUtil = $this->get('misc.util.file');
            $filePath = sprintf('%s/cron/auto/process_move_warehouse_schedule', $fileUtil->getDataDir());

            $fs = new FileSystem();
            $fs->remove($filePath); // エラーチェックのため、まず消す。

            $file = new \SplFileObject($filePath, 'wb');
            $file->fwrite($cronTemplate->render([ 'schedules' => $schedules, 'env' => $this->get('kernel')->getEnvironment() ]));
            unset($file);

            if (!$fs->exists($filePath)) {
                throw new \RuntimeException('cron設定ファイルが生成されませんでした。');
            }

            // crontab 設定読み込み
            $command = sprintf('/bin/bash %s/bin/apply_crontab.sh', dirname($fileUtil->getRootDir()));
            $logger->info($command);
            $process = new Process($command, $fileUtil->getRootDir());
            $statusCode = $process->run();

            $logger->info($statusCode . ' : ' . $filePath);
            $em->flush();


            // 画面戻り値
            foreach($schedules as $schedule) {
                $result['setting'][] = $schedule->toScalarArray();
            }
        } catch (\Exception $e) {
            $result['status'] = 'ng';
            $result['message'] = '設定の保存に失敗しました。 ' . $e->getMessage();
        }

        return new JsonResponse($result);
    }

    /**
     * 受注明細取込設定 更新
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOrderListSettingAction(Request $request)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');

        $result = [
            'status' => 'ok'
            , 'setting' => []
        ];

        try {
            $settings = $request->get('setting');

            // 入力チェック
            // 一応補正しておく
            foreach($settings as $dayOfTheWeek => $setting){
              $setting['hour'] = preg_replace('/\s+/', '', mb_convert_kana($setting['hours'], 'as'));
              $setting['minutes'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['minutes'], 'as')));
              $setting['active'] = (boolean)($setting['active']) ? -1 : 0;
              $setting['import_order_list_months'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['import_order_list_months'], 'as')));
              $setting['limit_time_hour'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['limit_time_hour'], 'as')));
              $setting['limit_time_minute'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['limit_time_minute'], 'as')));

              if ($setting['active'] !== 0) {
                if (!strlen($setting['hours'])) {
                  throw new \RuntimeException('「時」が入力されていません。');
                }
              }

              $hours = explode(',', $setting['hours']);
              foreach($hours as $hour) {
                if (!preg_match('/^\d{1,2}$/', $hour)) {
                  throw new \RuntimeException('「時」が正しくありません。');
                }
                if ($hour < 0 || $hour > 23) {
                  throw new \RuntimeException('「時」は0～23で設定してください。');
                }
              }
              if (count($hours) != count(array_unique($hours))) {
                throw new \RuntimeException('「時」に重複があります。');
              }

              if ($setting['minutes'] < 0 || $setting['minutes'] > 59) {
                throw new \RuntimeException('「分」は0～59で設定してください。');
              }

              if ($setting['import_order_list_months'] < 0) {
                throw new \RuntimeException('「取込期間」は0以上の値で設定してください。');
              }

              if ($setting['limit_time_hour'] < 0 || $setting['limit_time_hour'] > 23) {
                throw new \RuntimeException('「実行期限・時」は0～23で設定してください。');
              }

              if ($setting['limit_time_minute'] < 0 || $setting['limit_time_minute'] > 59) {
                throw new \RuntimeException('「実行期限・分」は0～59で設定してください。');
              }

              $settings[$dayOfTheWeek] = $setting;
            }

            /** @var TbCronProcessScheduleRepository $repo */
            $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
            $schedules = $repo->findOrderListSetting();
            $em = $this->getDoctrine()->getManager('main');

            foreach($settings as $dayOfTheWeek => $setting) {
              if (isset($schedules[$setting['code']])) {
                $cmd = sprintf('batch:enqueue --command=import_order_list --import-order-list-months=%d --limit-time=%02d:%02d:00 ', intval($setting['import_order_list_months']), intval($setting['limit_time_hour']), intval($setting['limit_time_minute']));
                $dayOfTheWeek = '';
                $active = $setting['active'];

                switch ($setting['code']) {
                  case 'ORDER_LIST_SETTING_SUNDAY' :
                    $dayOfTheWeek = '0';
                    break;
                  case 'ORDER_LIST_SETTING_MONDAY' :
                    $dayOfTheWeek = '1';
                    break;
                  case 'ORDER_LIST_SETTING_TUESDAY' :
                    $dayOfTheWeek = '2';
                    break;
                  case 'ORDER_LIST_SETTING_WEDNESDAY' :
                    $dayOfTheWeek = '3';
                    break;
                  case 'ORDER_LIST_SETTING_THURSDAY' :
                    $dayOfTheWeek = '4';
                    break;
                  case 'ORDER_LIST_SETTING_FRIDAY' :
                    $dayOfTheWeek = '5';
                    break;
                  case 'ORDER_LIST_SETTING_SATURDAY' :
                    $dayOfTheWeek = '6';
                    break;
                  default :
                    $cmd = "";
                    $active = 0;
                    break;
                }

                $schedule = $schedules[$setting['code']];

                $schedule->setHours($setting['hours']);
                $schedule->setMinutes(sprintf('%02d', intval($setting['minutes'])));
                $schedule->setActive($setting['active']);
                $schedule->setImportOrderListMonths(sprintf('%01d', intval($setting['import_order_list_months'])));
                $schedule->setLimitTimeHour(sprintf('%02d', intval($setting['limit_time_hour'])));
                $schedule->setLimitTimeMinute(sprintf('%02d', intval($setting['limit_time_minute'])));
                $schedule->setDayOfWeek($dayOfTheWeek);
                $schedule->setCommand($cmd);
              }
            }

            // crontab 出力
            /** @var \Twig_Environment $twig */
            $twig = $this->get('twig');
            $cronTemplate = $twig->load('AppBundle:Default:misc/cron_process_order_list_setting.cron.twig');

            /** @var FileUtil $fileUtil */
            $fileUtil = $this->get('misc.util.file');
            $filePath = sprintf('%s/cron/auto/process_order_list_schedule', $fileUtil->getDataDir());

            $fs = new FileSystem();
            $fs->remove($filePath); // エラーチェックのため、まず消す。

            $dayOfWeek = ['日曜日','月曜日','火曜日','水曜日','木曜日','金曜日','土曜日'];
            $file = new \SplFileObject($filePath, 'wb');
            $file->fwrite($cronTemplate->render(['schedules' => $schedules, 'dayOfWeek' => $dayOfWeek, 'env' => $this->get('kernel')->getEnvironment()]));
            unset($file);

            if (!$fs->exists($filePath)) {
              throw new \RuntimeException('cron設定ファイルが生成されませんでした。');
            }

            // crontab 設定読み込み
            $command = sprintf('/bin/bash %s/bin/apply_crontab.sh', dirname($fileUtil->getRootDir()));
            $logger->info($command);
            $process = new Process($command, $fileUtil->getRootDir());
            $statusCode = $process->run();

            $logger->info($statusCode . ' : ' . $filePath);

            $em->flush();

            // 画面戻り値
            foreach ($schedules as $schedule) {
              $dayOfTheWeek = explode('_',$schedule->getCode());
              $result['setting'][mb_strtolower(array_pop($dayOfTheWeek))] = $schedule->toScalarArray();
            }
        } catch (\Exception $e) {
            $result['status'] = 'ng';
            $result['message'] = '設定の保存に失敗しました。 ' . $e->getMessage();
        }

        return new JsonResponse($result);
    }

  /**
   * 外部倉庫在庫取得設定 更新
   * @param Request $request
   * @return JsonResponse
   */
  public function updateExternalWarehouseStockFetchSettingAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'settings' => []
    ];

    try {
      $settings = $request->get('settings');

      // 入力チェック
      foreach($settings as $i => $setting) {
        // 一応補正しておく
        $setting['hour'] = preg_replace('/\s+/', '', mb_convert_kana($setting['hours'], 'as'));
        $setting['minutes'] = intval(preg_replace('/\s+/', '', mb_convert_kana($setting['minutes'], 'as')));
        $setting['active'] = (boolean)($setting['active']) ? -1 : 0;
        $settings[$i] = $setting;

        if ($setting['active'] !== 0) {
          if (!strlen($setting['hours'])) {
            throw new \RuntimeException(sprintf('%s の「時」が入力されていません。', $setting['name']));
          }
        }

        $hours = explode(',', $setting['hours']);
        foreach($hours as $hour) {
          if (!preg_match('/^\d{1,2}$/', $hour)) {
            throw new \RuntimeException(sprintf('%s の「時」が正しくありません。', $setting['name']));
          }
          if ($hour < 0 || $hour > 23) {
            throw new \RuntimeException(sprintf('%s の「時」は0～23で設定してください。', $setting['name']));
          }
        }
        if (count($hours) != count(array_unique($hours))) {
          throw new \RuntimeException(sprintf('%s の「時」に重複があります。', $setting['name']));
        }

        if ($setting['minutes'] < 0 || $setting['minutes'] > 59) {
          throw new \RuntimeException(sprintf('%s の「分」は0～59で設定してください。', $setting['name']));
        }
      }

      /** @var TbCronProcessScheduleRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
      $schedules = $repo->findStockImportCodeList();

      $em = $this->getDoctrine()->getManager('main');
      foreach($settings as $setting) {
        if (isset($schedules[$setting['code']])) {
          $cmd = "";
          $active = $setting['active'];

          switch($setting['code']){
            case 'IMPORT_STOCK_FBA' :
              $cmd = 'batch:csv-download-and-update-amazon-fba-stock';
              break;
            case 'IMPORT_STOCK_YABUYOSHI' :
              $cmd = 'batch:csv-download-and-update-yabuyoshi-product-stock';
              break;
            case 'IMPORT_STOCK_RSL' :
              $cmd = 'batch:csv-download-and-update-rsl-product-stock';
              break;
            case 'IMPORT_STOCK_SHOPLIST' :
              $cmd = 'batch:csv-download-and-update-shoplist-product-stock-speed';
              break;
            default :
              $active = 0;
              break;
          }

          $schedule = $schedules[$setting['code']];

          $schedule->setHours($setting['hours']);
          $schedule->setMinutes(sprintf('%02d', intval($setting['minutes'])));
          $schedule->setActive($active);
          $schedule->setCommand($cmd);
        }
      }

      // crontab 出力
      /** @var \Twig_Environment $twig */
      $twig = $this->get('twig');
      $cronTemplate = $twig->load('AppBundle:Default:misc/cron_process_schedule.cron.twig');

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      $filePath = sprintf('%s/cron/auto/process_schedule', $fileUtil->getDataDir());

      $fs = new FileSystem();
      $fs->remove($filePath); // エラーチェックのため、まず消す。

      $file = new \SplFileObject($filePath, 'wb');
      $file->fwrite($cronTemplate->render([ 'schedules' => $schedules, 'env' => $this->get('kernel')->getEnvironment() ]));
      unset($file);

      if (!$fs->exists($filePath)) {
        throw new \RuntimeException('cron設定ファイルが生成されませんでした。');
      }

      // crontab 設定読み込み
      $command = sprintf('/bin/bash %s/bin/apply_crontab.sh', dirname($fileUtil->getRootDir()));
      $logger->info($command);
      $process = new Process($command, $fileUtil->getRootDir());
      $statusCode = $process->run();

      $logger->info($statusCode . ' : ' . $filePath);

      $em->flush();

      // 画面戻り値
      foreach($schedules as $schedule) {
        $result['settings'][] = $schedule->toScalarArray();
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = '設定の保存に失敗しました。 ' . $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 外部在庫取込
   * @param Request $request
   * @return JsonResponse
   */
  public function queueExternalWarehouseStockAction(Request $request)
  {

    $resque = $this->getResque();
    $setting = $request->get('setting');
    $command = "";
    switch($setting['code']){
        case 'IMPORT_STOCK_FBA' :
          $command = MainJob::COMMAND_KEY_IMPORT_AMAZON_STOCK;
          break;
        case 'IMPORT_STOCK_YABUYOSHI' :
          $command = MainJob::COMMAND_KEY_IMPORT_YABUYOSHI_STOCK;
          break;
        case 'IMPORT_STOCK_RSL' :
          $command = MainJob::COMMAND_KEY_IMPORT_RSL_STOCK;
          break;
        case 'IMPORT_STOCK_SHOPLIST' :
          $command = MainJob::COMMAND_KEY_IMPORT_SHOPLIST_STOCK;
          break;
        default :
          break;
    }

    if($command !== ""){
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command'          => $command
        , 'account'          => $this->getLoginUser()->getId()
      ];

      // リトライなし
      $retrySeconds = [];
      $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

      $resque->enqueue($job);
    }

    // 結果をJSONで返す
    return new JsonResponse(['message' => ""]);
  }

    /**
     * 外部在庫取込
     * @param Request $request
     * @return JsonResponse
     */
    public function queueExternalWarehouseBoxMoveAction(Request $request)
    {
        try {
            $resque = $this->getResque();
            $setting = $request->get('setting');
            if ($setting['stocks'] < 0) {
                throw new \RuntimeException(sprintf('%s の「在庫数」は入力してください。', $setting['name']));
            }
            $command = "";
            switch($setting['code']){
                case 'WAREHOSE_BOX_MOVE_SETTING' :
                    $command = MainJob::COMMAND_WAREHOUSE_BOX_MOVE;
                    break;
                default :
                    break;
            }

            if($command !== ""){
                $job = new MainJob();
                $job->queue = 'main'; // キュー名
                $job->args = [
                    'command'          => $command
                    , 'account'        => $this->getLoginUser()->getId()
                    , 'stocks'         => $setting['stocks']
                    , 'order_date'     => $setting['order_date']
                    , 'magnification_percent'         => $setting['magnification_percent']
                    , 'queue-name'     => 'main'
                ];

                // リトライなし
                $retrySeconds = [];
                $resque->setJobRetryStrategy([get_class($job) => $retrySeconds]);

                $resque->enqueue($job);
            }

            // 結果をJSONで返す
            return new JsonResponse(['status' => "ok", 'message' => ""]);
        } catch (\Exception $e) {
            $result['status'] = 'ng';
            $result['message'] = '倉庫間箱移動処理を開始できませんでした。 ' . $e->getMessage();
            return new JsonResponse($result);
        }
    }

  /**
   * 発送方法一括変換 キュー追加 (Ajaxアクセス)
   * @param Request $request
   * @return JsonResponse
   */
  public function queueDeliveryMethodConversionAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $resque = $this->getResque();

    $job = new MainJob();
    $job->queue = 'main'; // キュー名
    $job->args = [
        'command'   => MainJob::COMMAND_KEY_DELIVERY_METHOD_CONVERSION
      , 'account'   => $this->getLoginUser()->getId()
    ];

    // リトライなし
    $resque->enqueue($job);

    // 結果をJSONで返す
    return new JsonResponse(['message' => "発送方法一括変換のキューを追加しました。5秒後にリロードします。"]);
  }

  /**
   * テストでキューを入れてみる(接続確認版)
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function checkConnectionAction(/** @noinspection PhpUnusedParameterInspection */ Request $request)
  {
    $job = new CheckEnvJob();
    $job->queue = 'check_env'; // キュー名
    $job->args = [];

    $this->get('bcc_resque.resque')->enqueue($job);

    return $this->redirect($this->generateUrl('BCCResqueBundle_homepage'));
  }
//
//  /**
//   * テストDB接続
//   */
//  public function testDbConnectionAction(Request $request)
//  {
//    /** @var \Doctrine\DBAL\Connection $db */
//    $dbMain = $this->getDoctrine()->getConnection('main');
//    $dbMain->connect();
//
//    /** @var \Doctrine\DBAL\Connection $db */
//    $dbBatch = $this->getDoctrine()->getConnection('batch');
//    $dbBatch->connect();
//
//    $sql = "SELECT count(*) FROM tb_mainproducts";
//    $stmt = $dbMain->prepare($sql);
//    $stmt->execute();
//    var_dump($stmt->fetchAll());
//
//    $sql = "SHOW TABLES";
//    $stmt = $dbBatch->prepare($sql);
//    $stmt->execute();
//    var_dump($stmt->fetchAll());
//
//    return new Response('done!');
//  }
//
//  /**
//   * Form テスト
//   */
//  public function testFormAction(Request $request)
//  {
//    return $this->render('AppBundle:Default:test_form.html.twig', array());
//  }

  /**
   * vue-router テスト
   */
  public function testVueRouterAction()
  {
    return $this->render('AppBundle:Default:router-test.html.twig', [
    ]);
  }

  /**
   * phpinfo
   */
  public function testInfoAction()
  {
    ob_start();
    phpinfo();
    $info = ob_get_contents();
    ob_end_clean();

    return new Response($info);
  }

  /**
   * その他テスト表示（開発用）
   */
  public function testViewAction()
  {
    return $this->render('AppBundle:Default:test-view.html.twig', [
    ]);
  }

  /**
   * node.js サーバ通知テスト
   */
  public function testNotifyAction()
  {
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->get('misc.util.web_access');
    $client = $webAccessUtil->getWebClient();


    // 通知サーバへPOST処理
    // $config = array('useragent' => 'forest batch logger ua/1.0');
    $data = [
        'notify' => true
      , 'notification_level' => 'info'
      , 'notification_message' => 'テストです！！'
    ];

    $url = $this->getParameter('app_notification_url') . $this->getParameter('app_notification_path');
    $client->request('post', $url, $data);

    return new Response('done!! : ' . $url);
  }
}
