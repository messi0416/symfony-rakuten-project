<?php
/**
 * 外部ベンダー認証なし用 コントローラ (/vendor/)
 */

namespace Plusnao\MainBundle\Controller;

use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\TbIndividualorderCommentRepository;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Entity\TbIndividualorderhistory;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbVendormasterdata;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use mysql_xdevapi\Exception;
use Plusnao\MainBundle\Form\Type\VendorOrderListDownloadCsvType;
use Plusnao\MainBundle\Form\Type\VendorOrderListUploadCsvType;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiscBundle\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

use Symfony\Component\Filesystem\Filesystem;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use PHPExcel_Worksheet_MemoryDrawing;
use PHPExcel_Style_Alignment;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use BatchBundle\Job\BaseJob;
use BatchBundle\Job\MainJob;
use MiscBundle\Entity\TbOrderListExport;
use MiscBundle\Entity\Repository\TbOrderListExportRepository;

class VendorController extends BaseAgentController
{
  const BULK_DISPLAY_STATUS_UPDATE_ORDERED  = 'すべて発注済にする';
  const BULK_DISPLAY_STATUS_UPDATE_ARRIVED  = 'すべて入荷済にする';
  const BULK_DISPLAY_STATUS_UPDATE_WAITED   = 'すべて出荷待にする';
  const BULK_DISPLAY_STATUS_UPDATE_SHIPPING = 'すべて出荷済にする';
  const BULK_DISPLAY_EXPORT_SHIPPING = '輸出書類を出力する';

  const BULK_STATUS_UPDATE_ORDERED  = 'STATUS_UPDATE_ORDERED';
  const BULK_STATUS_UPDATE_ARRIVED  = 'STATUS_UPDATE_ARRIVED';
  const BULK_STATUS_UPDATE_WAITED   = 'STATUS_UPDATE_WAITED';
  const BULK_STATUS_UPDATE_SHIPPING = 'STATUS_UPDATE_SHIPPING';
  const BULK_EXPORT_SHIPPING = 'EXPORT_SHIPPING';
  
  public static $REMAIN_STATUS = [
      self::BULK_STATUS_UPDATE_ORDERED  => TbIndividualorderhistoryRepository::REMAIN_STATUS_ORDERED
    , self::BULK_STATUS_UPDATE_ARRIVED  => TbIndividualorderhistoryRepository::REMAIN_STATUS_ARRIVED
    , self::BULK_STATUS_UPDATE_WAITED   => TbIndividualorderhistoryRepository::REMAIN_STATUS_WAITED
    , self::BULK_STATUS_UPDATE_SHIPPING => TbIndividualorderhistoryRepository::REMAIN_STATUS_SHIPPED
  ];

  const ALERT_ALL = 'ALL';
  const ALERT_REGULAR_NONE = 'REGULAR_NONE';
  const ALERT_UNSET_WEIGHT_SIZE = 'UNSET_WEIGHT_SIZE';
  const ALERT_UNSET_MATERIAL_DESCRIPTION = 'UNSET_MATERIAL_DESCRIPTION';
  const UPLOAD_CSV_HEADER = array(
    "発注伝票番号"
    ,"発注日"
    ,"商品コード"
    ,"商品サブコード"
    ,"数量"
    ,"注文番号"
    ,"SKUID"
    ,"同梱ID"
    ,"状態"
    ,"箱番号"
    ,"単価"
    ,"送料"
    ,"値引"
    ,"連絡事項"
    ,"支払日"
    ,"発送日"
    ,"検品日"
    ,"欠品日"
    ,"問合せ番号"
    ,"重量"
    ,"縦"
    ,"横"
    ,"高"
    ,"DESCRIPTION"
    ,"材質商品説明"
  );

  /**
   * 一括更新 翻訳キー取得
   */
  public static function getBulkTranslationKey($code)
  {
    $keys = [
      self::BULK_STATUS_UPDATE_ORDERED  => 'status_update_ordered',
      self::BULK_STATUS_UPDATE_ARRIVED  => 'status_update_arrived',
      self::BULK_STATUS_UPDATE_WAITED   => 'status_update_waited',
      self::BULK_STATUS_UPDATE_SHIPPING => 'status_update_shipping',
      self::BULK_EXPORT_SHIPPING => 'export_shipping'
    ];

    return isset($keys[$code]) ? $keys[$code] : null;
  }

  /**
   * 一括更新一覧取得(インスタンスメソッド)
   * @return array
   */
  public function getBulkListArray()
  {
    return self::getBulkList();
  }

  /**
   * 一括更新一覧取得
   * @return array
   */
  public static function getBulkList()
  {
    return [
      self::BULK_STATUS_UPDATE_ORDERED  => self::BULK_DISPLAY_STATUS_UPDATE_ORDERED ,
      self::BULK_STATUS_UPDATE_ARRIVED  => self::BULK_DISPLAY_STATUS_UPDATE_ARRIVED ,
      self::BULK_STATUS_UPDATE_WAITED   => self::BULK_DISPLAY_STATUS_UPDATE_WAITED  ,
      self::BULK_STATUS_UPDATE_SHIPPING => self::BULK_DISPLAY_STATUS_UPDATE_SHIPPING,
      self::BULK_EXPORT_SHIPPING => self::BULK_DISPLAY_EXPORT_SHIPPING
    ];
  }

  /**
   * 警告・注意取得
   * @return array
   */
  public static function getAlertListArray() {
    return [
      self::ALERT_ALL,
      self::ALERT_REGULAR_NONE,
      self::ALERT_UNSET_WEIGHT_SIZE,
      self::ALERT_UNSET_MATERIAL_DESCRIPTION
    ];
  }
  
  /**
   * 警告・注意翻訳キー取得
   */
  public static function getAlertTranslationKey($code)
  {
    $keys = [
      self::ALERT_ALL => 'all',
      self::ALERT_REGULAR_NONE => 'regular_none',
      self::ALERT_UNSET_WEIGHT_SIZE => 'unset_weight_size',
      self::ALERT_UNSET_MATERIAL_DESCRIPTION => 'unset_material_description'
    ];
    return isset($keys[$code]) ? $keys[$code] : null;
  }

  /**
   * 依頼先取得＆チェック
   */
  private function validateVendor()
  {
    $account = $this->getLoginUser();

    if (!$account) {
      throw new \RuntimeException('no login.');
    }

    $agent = $this->getAgent();
    if (!$agent) {
      throw new \RuntimeException('no agent');
    }

    if ($account->isClient()) {
      if (!$account->getAgent()) {
        throw new \RuntimeException('no account agent.');
      }
    }

    return $agent;
  }

  /**
   * @param Request $request
   * @param string $agentName
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function changeAgentNameAction(Request $request, $agentName = null)
  {
    $account = $this->getLoginUser();
    if ($agentName && $account && $account->isForestStaff()) {

      /** @var BaseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
      /** @var PurchasingAgent $agent */
      $agent = $repo->findOneBy(['login_name' => $agentName]);
      if (!$agent) {
        throw new \RuntimeException('invalid agent');
      }

      if ($request->isMethod(Request::METHOD_POST)) {
        $this->get('misc.util.batch_logger')->info('change agentName : ' . $agentName);

        // cookieをセットし直してリダイレクト
        $response = $this->redirectToRoute('plusnao_vendor_order_list', array_merge(['_locale' => 'ja', 'agentName' => $agentName], $request->query->all()));
        $response->headers->setCookie(new Cookie(
          'agentName'
          , $agentName
          , 0
          , '/'
          , $this->getParameter('auth_cookie_host')
          , (new \DateTime())->modify('+ 10 year')
        ));
        return $response;

      } else {

        // 画面表示
        return $this->render('PlusnaoMainBundle:Vendor:change-agent-name.html.twig', [
          'agent' => $agent
        ]);
      }
    }

    throw new \RuntimeException('invalid access.');
  }

  /**
   * 注残一覧画面 初期表示
   * @param Request $request
   * @return Response
   */
  public function orderListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // IDチェック
    $agent = $this->validateVendor();
    $locale = $request->getLocale();

    // URLにagentName が含まれていない場合にはリダイレクト
    if (!$request->get('agentName')) {
      return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $locale, 'agentName' => $agent->getLoginName()]);
    }

    /** @var TbIndividualorderhistoryRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    $agentCode = $agent->getid();
    
    $summary = $repo->findSummaryByAgentCode($agentCode);
    $lastUpdated = $repo->getLastUpdated($agentCode);

    $bulkList = [];
    foreach ($this->getBulkListArray() as $k => $v) {
      $bulkList[] = [
        'code' => $k
        , 'translated_value' => $this->get('translator')->trans('vendor.bulk.' . $this->getBulkTranslationKey($k))
        , 'value' => $v
      ];
    }

    $statusList = [];
    foreach ($repo->getRemainStatusListArray() as $k => $v) {
      $statusList[] = [
        'code' => $k
        , 'translated_value' => $this->get('translator')->trans('vendor.order_list.' . TbIndividualorderhistoryRepository::getRemainStatusTranslationKey($k))
        , 'value' => $v
      ];
    }

    $alertList = [];
    foreach ($this->getAlertListArray() as $k) {
      $alertList[] = [
        'code' => $k
        , 'translated_value' => $this->get('translator')->trans('vendor.search.alert.' . $this->getAlertTranslationKey($k))
      ];
    }

    // 画面初期表示
    return $this->render('PlusnaoMainBundle:Vendor:order-list.html.twig', [
      'account' => $this->getLoginUser()
      , 'agent' => $agent
      , 'locale' => $locale
      , 'searchConditions' => []
      , 'lastUpdated' => $lastUpdated
      , 'remainStatusList' => json_encode($statusList)
      , 'alertList' => json_encode($alertList)
      , 'bulkList' => json_encode($bulkList)
    ]);
  }
  
  /**
   * 注残一覧の検索を実行する。
   */
  public function findOrderListAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
    ];
    
    try {
      // IDチェック
      $agent = $this->validateVendor();
      $locale = $request->getLocale();
      
      // URLにagentName が含まれていない場合にはリダイレクト
      if (!$request->get('agentName')) {
        return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $locale, 'agentName' => $agent->getLoginName()]);
      }
      
      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $agentCode = $agent->getid();
      $page = $request->get('page', 1);
      $pageItemNum = $request->get('limit', 100);
      $searchConditions = $request->get('search');
      $searchConditions['sortKey'] = $request->get('sortKey');
      $searchConditions['sortOrder'] = $request->get('sortOrder');
      
      $pagination = $repo->getPageListByAgentCode($agentCode, $searchConditions, false, $pageItemNum, $page);
      $summary = $repo->findSummaryByAgentCode($agentCode, $searchConditions);
      
      $pageData = $this->createListData($pagination, $locale);
      
      // 商品情報を取得
      $productCodeList = array_column($pageData, 'syohin_code');
      $products = $repo->getProductSpecList($productCodeList);
      
      $result['list'] = $pageData;
      $result['count'] = $pagination->getTotalItemCount();
      $result['summary'] = $summary;
      $result['products'] = $products;
    } catch (\Exception $e) {
      $logger->error('注残一覧検索でエラー発生:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * ラベル印刷用の注残一覧の検索を実行する。
   * ・ページングせず、全件検索を行う
   * ・ラベル印刷に必要な項目のみ取得する
   */
  public function findOrderListForLabelAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
    ];
    
    try {
      // IDチェック
      $agent = $this->validateVendor();
      $locale = $request->getLocale();
      
      // URLにagentName が含まれていない場合にはリダイレクト
      if (!$request->get('agentName')) {
        return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $locale, 'agentName' => $agent->getLoginName()]);
      }
      
      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $agentCode = $agent->getid();
      $searchConditions = $request->get('search');
      $searchConditions['sortKey'] = $request->get('sortKey');
      $searchConditions['sortOrder'] = $request->get('sortOrder');
      
      $list = $repo->getListForLabelByAgentCode($agentCode, $searchConditions, false);
      $result['list'] = $list;
      $result['count'] = count($list);
    } catch (\Exception $e) {
      $logger->error('注残一覧検索でエラー発生:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * 注残一覧画面（オリジナル）
   * 一括取得verの注残一覧画面
   * 注残件数が多い依頼先は極めて重いので注意
   * @param Request $request
   * @return Response
   */
  public function orderListOriginalAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    
    // IDチェック
    $agent = $this->validateVendor();
    $locale = $request->getLocale();
    
    // URLにagentName が含まれていない場合にはリダイレクト
    if (!$request->get('agentName')) {
      return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $locale, 'agentName' => $agent->getLoginName()]);
    }
    
    /** @var TbIndividualorderhistoryRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    $agentCode = $agent->getid();
    
    // 件数が増えるとメモリエラーで落ちるので無制限とする
    // この処理がない場合、だいたい15000行超ぐらいで落ちる様子
    // 現在は全てのデータをブラウザに送り、ブラウザ側でページングなどしているので、
    // 将来的には都度サーバにリクエストする構成にした方が良さそう
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 60 * 60);
    set_time_limit(60 * 60);

    $lastUpdated = $repo->getLastUpdated($agentCode);

    $searchConditions = $request->get('search');
    $list = $repo->getListByAgentCode($agentCode, $searchConditions);
    $data = $this->createListData($list, $locale);

    // 商品諸元情報を取得
    $codeList = [];
    // 商品コードの抽出
    foreach ($data as $row) {
      if (!in_array($row['syohin_code'], $codeList)) {
        $codeList[] = $row['syohin_code'];
      }
    }
    // 一括取得
    $products = $repo->getProductSpecList($codeList);

    $bulkList = [];
    foreach ($this->getBulkListArray() as $k => $v) {
      $bulkList[] = [
        'code' => $k
        , 'translated_value' => $this->get('translator')->trans('vendor.bulk.' . $this->getBulkTranslationKey($k))
        , 'value' => $v
      ];
    }

    $statusList = [];
    foreach ($repo->getRemainStatusListArray() as $k => $v) {
      $statusList[] = [
        'code' => $k
        , 'translated_value' => $this->get('translator')->trans('vendor.order_list.' . TbIndividualorderhistoryRepository::getRemainStatusTranslationKey($k))
        , 'value' => $v
      ];
    }

    $alertList = [];
    foreach ($this->getAlertListArray() as $k) {
      $alertList[] = [
        'code' => $k
        , 'translated_value' => $this->get('translator')->trans('vendor.search.alert.' . $this->getAlertTranslationKey($k))
      ];
    }

    // アップロードフォーム
    $uploadForm = $this->createForm(new VendorOrderListUploadCsvType())->createView();

    // ダウンロードフォーム
    $downloadForm = $this->createForm(new VendorOrderListDownloadCsvType())->createView();

    // 品質レベル
    $qualityLevelList = [
      'none' => TbMainproductsCal::QUALITY_LEVEL_NONE
      , 'ng' => TbMainproductsCal::QUALITY_LEVEL_NG
      , 'ok' => TbMainproductsCal::QUALITY_LEVEL_OK
      , 'good' => TbMainproductsCal::QUALITY_LEVEL_GOOD
    ];
    
    // 画面表示
    return $this->render('PlusnaoMainBundle:Vendor:order-list-original.html.twig', [
      'account' => $this->getLoginUser()
      , 'agent' => $agent
      , 'locale' => $locale
      , 'searchConditions' => $searchConditions
      , 'data' => json_encode($data)
      , 'lastUpdated' => $lastUpdated
      , 'products' => json_encode($products)
      , 'remainStatusList' => json_encode($statusList)
      , 'uploadForm' => $uploadForm
      , 'downloadForm' => $downloadForm
      , 'qualityLevelList' => json_encode($qualityLevelList)
      , 'alertList' => json_encode($alertList)
      , 'bulkList' => json_encode($bulkList)
    ]);
  }

  /**
   * 未引当フラグ更新
   * @param Request $request
   * @return Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function reassessUnallocatedFlgAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'lastUpdated' => null
    ];

    $agent = $this->validateVendor();
    $agentCode = $agent->getid();
    $agentName = $request->get('agentName');
    $locale = $request->getLocale();
    $update = $request->get('update');
    $isAjax = $request->get('isAjax', 0);

    /** @var TbIndividualorderhistoryRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    
    try {
      if ($update == 'all' or $agentCode === -1) {
        //全拠点更新
        $repo->updateUnallocatedFlgByAgentCode();
      } else {
        //一部更新
        $repo->updateUnallocatedFlgByAgentCode($agentCode);
      } 
      $lastUpdated = $repo->getLastUpdated($agentCode); // 更新後の最終更新日を取得
      if ($lastUpdated) {
        $result['lastUpdated'] = $lastUpdated->format('Y-m-d H:i:s');
      }
    } catch (Exception $e) {
      $logger->error("未引当フラグ更新でエラー発生: agentName=$agentName, update=$update" . $e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    
    // ajax（ページング版）の場合はこちら
    if ($isAjax) {
      return new JsonResponse($result);
    }
    
    // 従来版
    if ($result['status'] == 'ng') {
      throw new Exception('更新の際にエラーが発生しました。');
    }
    return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $locale, 'agentName' => $agentName]);
  }

  /**
   * 注残一覧画面 一括更新処理
   * @param Request $request
   * @return Response
   */
  public function orderListBulkUpdateAction(Request $request)
  {
    $locale = $request->getLocale();

    $searchConditions = $request->get('search');
    $bulkTrigger = $request->get('bulkTrigger');
    $isAjax = $request->get('isAjax');

    /** @var Logger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // IDチェック
    $agent = $this->validateVendor();
    $agentCode = $agent->getid();
    $account = $this->getLoginUser();

    /** @var TbIndividualorderhistoryRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    $updateDate = date('Y-m-d H:i:s');
    $updatePerson = $account->getUsername();
    
    // バルクトリガーが正しいかどうかチェック
    // 特定値以外が入っていればエラー
    if(array_key_exists($bulkTrigger,self::$REMAIN_STATUS)){
      // アップデートステータス取得
      $updateStatus = self::$REMAIN_STATUS[$bulkTrigger];
      
      // データ詰め
      $updateData = array(
        'status' => $updateStatus,
        'date' => $updateDate,
        'person' => $updatePerson,
      );
      
      // 一括更新
      $repo->productListBulkUpdateStatus($agentCode, $searchConditions, $updateData);
    } else {
      throw new \RuntimeException('invalid status given. [' . $request->get('status') . ']');
    }
    
    // ajaxの場合はリダイレクトせずここで終了
    if ($isAjax) {
      $result = [
        'status' => 'ok'
        , 'message' => null
      ];
      return new JsonResponse($result);
    }
    
    $redirectSearch = array(
      'syohin_code' => $searchConditions['syohin_code'],
      'order_date_from' => $searchConditions['order_date_from'],
      'order_date_to' => $searchConditions['order_date_to'],
    );

    $param = array(
      '_locale' => $locale,
      'agentName' => $agent->getLoginName(),
      'search' => $redirectSearch
    );

    return $this->redirectToRoute('plusnao_vendor_order_list_original', $param);
  }

  /**
   * 輸出書類出力
   * @param Request $request
   * @return Response
   */
  public function orderListExportExcelAction(Request $request)
  {
    /** @var Logger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    try {
      // IDチェック
      $agent = $this->validateVendor();
      $account = $this->getLoginUser();

      $locale = $request->getLocale();

      $searchConditions = $request->get('search');
      $bulkTrigger = $request->get('bulkTrigger');

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      $key = $stringUtil->getUniqueId('ds');

      /** @var JobRequestRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:JobRequest');

      $expiredAt = (new \DateTime())->modify('+2 minutes'); // singletonのため、NEW状態での有効期限はできるだけ短く
      $options = [
        'agentId' => $agent->getId()
        , 'conditions' => json_encode($searchConditions)
        , 'account' => $this->getLoginUser()->getId()
        , 'isForestStaff' => $this->getLoginUser()->isForestStaff()
        , 'isClient' => $this->getLoginUser()->isClient()
        , 'isYahooAgent' => $this->getLoginUser()->isYahooAgent()
      ];

      $jobRequest = $repo->createJobRequest(
        $key
        , BaseJob::COMMAND_EXPORT_ORDER_LIST_TO_EXCEL
        , $expiredAt
        , $this->getLoginUser()->getClientName()
        , $options
        , false
      );

      $logger->info('Queue export order list to excel: ' . print_r($jobRequest, true));

      // jobRequest 実行処理へリダイレクト。
      return $this->redirectToRoute('api_check_job_request', ['key' => $key], Response::HTTP_FOUND);
    } catch (\Exception $e) {
      $logger->error('輸出書類出力のキュー登録でエラー発生：' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();

      return new JsonResponse($result);
    }
  }

  public function downloadExcelOrderListAction(Request $request)
  {
    // ini_set
    // 実行時間が長く、使うメモリが多ければ、設定したほうがいい
    ini_set('memory_limit', '2048M');
    ini_set('max_execution_time', 0);

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');
    $fileName = $request->query->get('filename');
    $filePath = $fileUtil->getDataDir() . $fileName;
    $fileId = $request->query->get('fileId');
    if (is_null($fileId)) {
      throw new \Exception('File not found.');
    }
    /** @var TbOrderListExportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbOrderListExport');
    $fileDownload = $repo->find($fileId);
    if (!$fileDownload) {
      throw new \Exception('File not found.');
    }
    $fileDownload->setLastDownload(new \DateTime());
    $em = $this->getDoctrine()->getManager('main');
    $em->flush();
    if (file_exists($filePath)) {
      $content = file_get_contents($filePath);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: ' . sprintf('attachment; filename="%s";', basename($filePath)));
      header('Cache-Control: max-age=0');
      header('Pragma: no-cache');
      header("Content-Type: application/force-download");
      header("Content-Type: application/octet-stream");
      header("Content-Type: application/download");
      echo $content;
      exit;
    }
    throw new \Exception('File not found.');
    exit;
  }

  public function listDownloadExportExcelAction($page)
  {
    $account = $this->getLoginUser();

    /** @var TbOrderListExportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbOrderListExport');
    $pagination = $repo->findByVendor([], [], $page);

    $linkDownloads = [];
    foreach ($pagination->getItems() as $item) {
      if (!is_null($item['file']) && $item['file'] != '') {
        $linkDownloads[$item['id']] = $this->generateUrl('plusnao_vendor_download_file_excel_order_list', array('fileId' => $item['id'], 'filename' => $item['file']));
      } else {
        $linkDownloads[$item['id']] = '';
      }
    }
    return $this->render('PlusnaoMainBundle:Vendor:list-download-excel.html.twig', [
      'account' => $account
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'linkDownloads' => $linkDownloads
    ]);
  }

  /**
   * 注残一覧画面 注残ステータス更新処理
   * @param Request $request
   * @return Response
   */
  public function orderListUpdateRemainStatusAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();
      $account = $this->getLoginUser();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $logger->info('voucher_id: ' . $voucherId);

      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update status). ' . ' / voucher_id: ' . $voucherId);
      }

      $updateDate = $request->get('flag') ? new \DateTime() : null;
      $updatePerson = $request->get('flag') ? $account->getUsername() : '';
      switch ($request->get('status')) {
        case 'ordered':
          $order->setRemainOrderedDate($updateDate);
          $order->setRemainOrderedPerson($updatePerson);
          break;
        case 'arrived':
          $order->setRemainArrivedDate($updateDate);
          $order->setRemainArrivedPerson($updatePerson);
          break;
        case 'waited':
          $order->setRemainWaitingDate($updateDate);
          $order->setRemainWaitingPerson($updatePerson);
          break;
        case 'shipped':
          $order->setRemainShippingDate($updateDate);
          $order->setRemainShippingPerson($updatePerson);
          break;
        case 'shortage':
          $order->setRemainStockoutDate($updateDate);
          $order->setRemainStockoutPerson($updatePerson);
          break;
        default:
          throw new \RuntimeException('invalid status given. [' . $request->get('status') . ']');
      }

      $order->updateRemainStatusByStatusDates();
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['result'] = [
        'remain_status' => $order->getRemainStatus()
        , 'remain_ordered_date' => $order->getRemainOrderedDate() ? $order->getRemainOrderedDate()->format('Y-m-d H:i:s') : null
        , 'remain_arrived_date' => $order->getRemainArrivedDate() ? $order->getRemainArrivedDate()->format('Y-m-d H:i:s') : null
        , 'remain_waiting_date' => $order->getRemainWaitingDate() ? $order->getRemainWaitingDate()->format('Y-m-d H:i:s') : null
        , 'remain_shipping_date' => $order->getRemainShippingDate() ? $order->getRemainShippingDate()->format('Y-m-d H:i:s') : null
        , 'remain_stockout_date' => $order->getRemainStockoutDate() ? $order->getRemainStockoutDate()->format('Y-m-d H:i:s') : null
        , 'remain_ordered_person' => $order->getRemainOrderedPerson()
        , 'remain_arrived_person' => $order->getRemainArrivedPerson()
        , 'remain_waiting_person' => $order->getRemainWaitingPerson()
        , 'remain_shipping_person' => $order->getRemainShippingPerson()
        , 'remain_stockout_person' => $order->getRemainStockoutPerson()
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * 注残一覧画面 発送伝票番号更新処理
   * @param Request $request
   * @return Response
   */
  public function orderListUpdateShippingNumberAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $logger->info('voucher_id: ' . $voucherId);

      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update shipping number). ' . ' / voucher_id: ' . $voucherId);
      }

      $order->setShippingNumber($request->get('shipping_number'));
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['result'] = [
        'shipping_number' => $order->getShippingNumber()
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * 注残一覧画面 受注番号更新処理
   * @param Request $request
   * @return Response
   */
  public function orderListUpdateReceiveOrderNumberAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $logger->info('voucher_id: ' . $voucherId);

      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update receive order number). ' . ' / voucher_id: ' . $voucherId);
      }

      $order->setReceiveOrderNumber($request->get('receive_order_number'));
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['result'] = [
        'receive_order_number' => $order->getReceiveOrderNumber()
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }


  /**
   * 注残一覧画面 入庫番号更新処理
   * @param Request $request
   * @return Response
   */
  public function orderListUpdateWarehousingNumberAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $logger->info('voucher_id: ' . $voucherId);

      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update warehousing number). ' . ' / voucher_id: ' . $voucherId);
      }

      $order->setWarehousingNumber($request->get('warehousing_number', ''));
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['result'] = [
        'warehousing_number' => $order->getWarehousingNumber()
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }


  /**
   * 連絡事項 取得処理
   */
  public function orderListGetVendorCommentAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update vendor comment). ' . 'voucherId: ' . $voucherId);
      }

      $result['result'] = [
        'vendor_comment' => $order->getVendorComment()
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * 連絡事項 更新処理
   */
  public function orderListUpdateVendorCommentAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update vendor comment). ' . 'id: ' . $voucherId);
      }

      $vendorComment = trim($request->get('vendor_comment'));
      if ($vendorComment != $order->getVendorComment()) {
        $order->setVendorComment($vendorComment);
        $order->setVendorCommentUpdated(new \DateTime());

        $em = $this->getDoctrine()->getManager('main');
        $em->flush();
      }

      $result['result'] = [
        'vendor_comment' => $order->getVendorComment()
        , 'vendor_comment_updated' => $order->getVendorCommentUpdated() ? $order->getVendorCommentUpdated()->format('Y-m-d H:i:s') : null
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }


  /**
   * 発注数 更新処理
   */
  public function orderListUpdateOrderNumAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.no_data') . 'voucher_id: ' . $voucherId;
        throw new \RuntimeException($result['message']);
      }

      $orderNum = intval($request->get('order_num'));
      if (!$orderNum) {
        $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.no_value');
        throw new \RuntimeException($result['message']);
      }
      if ($orderNum == $order->getOrderNum()) {
        $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.same_value');
        throw new \RuntimeException($result['message']);
      }
      if ($orderNum < $order->getOrderNum()) {
        $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.small_value');
        throw new \RuntimeException($result['message']);
      }

      $remainNumDiff = ($orderNum - $order->getOrderNum());
      if ($order->getRemainNum() + $remainNumDiff < 0) {
        $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.too_small');
        throw new \RuntimeException($result['message']);
      }

      // generated column実装により不要。
      // $order->setRemainNum($order->getRemainNum() + $remainNumDiff);
      $order->setOrderNum($orderNum);

      // productchoiceitems 注残数更新
      // トリガ実装により削除予定
      // $choiceItem = $order->getChoiceItem();
      // if (!$choiceItem) {
      //   $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.invalid_data');
      //   throw new \RuntimeException($result['message']);
      // }
      // $choiceItem->setOrderRemainNum($choiceItem->getOrderRemainNum() + $remainNumDiff);

      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['result'] = [
        'order_num' => $order->getOrderNum()
        , 'remain_num' => $order->getRemainNum()
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      if (!$result['message']) {
        $result['message'] = $this->get('translator')->trans('vendor.order_list.errors.update_order_num.server_busy');
      }
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * CSVダウンロード処理
   */
  public function orderListCsvDownloadAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');

    try {
      $now = new \DateTimeImmutable();

      // IDチェック
      $agent = $this->validateVendor();

      $locale = $request->getLocale();

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $rateCny = floatval($commonUtil->getSettingValue('EXCHANGE_RATE_CNY'));
      if (!$rateCny) {
        $rateCny = 17.00; // イレギュラーだがとにかく値を入れる
      }

      $searchConditions = $request->get('csv');
      if (!is_array($searchConditions)) {
        $searchConditions = [];
      }

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $stmt = $repo->getCsvDownloadDataByAgentCode($agent->getId(), $searchConditions);

      // CSV出力
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $translator = $this->get('translator');

      $headers = [
        'voucher_number' => $translator->trans('vendor.csv.header.voucher_number')
        , 'order_date' => $translator->trans('vendor.csv.header.order_date')
        , 'sire_name' => $translator->trans('vendor.csv.header.sire_name')
        , 'syohin_code' => $translator->trans('vendor.csv.header.syohin_code')
        , 'daihyo_syohin_label' => $translator->trans('vendor.csv.header.daihyo_syohin_label')
        , 'colname' => $translator->trans('vendor.csv.header.colname')
        , 'rowname' => $translator->trans('vendor.csv.header.rowname')
        , 'support_colname' => $translator->trans('vendor.csv.header.support_colname')
        , 'support_rowname' => $translator->trans('vendor.csv.header.support_rowname')
        , 'cost' => $translator->trans('vendor.csv.header.cost')
        , 'order_num' => $translator->trans('vendor.csv.header.order_num')
        , 'regular' => $translator->trans('vendor.csv.header.regular')
        , 'defective' => $translator->trans('vendor.csv.header.defective')
        , 'shortage' => $translator->trans('vendor.csv.header.shortage')
        , 'remain_num' => $translator->trans('vendor.csv.header.remain_num')
        , 'remain_ordered_date' => $translator->trans('vendor.csv.header.remain_ordered_date')
        , 'remain_arrived_date' => $translator->trans('vendor.csv.header.remain_arrived_date')
        , 'remain_waiting_date' => $translator->trans('vendor.csv.header.remain_waiting_date')
        , 'remain_shipping_date' => $translator->trans('vendor.csv.header.remain_shipping_date')
        , 'remain_stockout_date' => $translator->trans('vendor.csv.header.remain_stockout_date')
        , 'vendor_comment' => $translator->trans('vendor.csv.header.vendor_comment')
        , 'shipping_number' => $translator->trans('vendor.csv.header.shipping_number')
        , 'receive_order_number' => $translator->trans('vendor.csv.header.receive_order_number')
        , 'sire_adress' => $translator->trans('vendor.csv.header.sire_adress')
        , 'image_url' => $translator->trans('vendor.csv.header.image_url')
        , 'barcode' => $translator->trans('vendor.csv.header.barcode')
        , 'order_comment' => $translator->trans('vendor.csv.header.order_comment')
      ];
      if ($agent->getId() == PurchasingAgent::AGENT_ID_TNEKO) {
        $headers['weight'] = $translator->trans('vendor.csv.header.weight');
        $headers['depth'] = $translator->trans('vendor.csv.header.depth');
        $headers['width'] = $translator->trans('vendor.csv.header.width');
        $headers['height'] = $translator->trans('vendor.csv.header.height');
        $headers['description_en'] = $translator->trans('vendor.csv.header.description_en');
        $headers['hint_ja'] = $translator->trans('vendor.csv.header.hint_ja');
      }

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($stmt, $stringUtil, $headers, $locale, $rateCny) {
          $file = new \SplFileObject('php://output', 'w');
          $eol = "\n";

          // BOM付きUTF-8で出力すれば、Excelで開ける。
          $file->fwrite(pack('C*', 0xEF, 0xBB, 0xBF));

          // ヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
          $file->fwrite($header);

          while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            // 項目変換処理
            /*
            $row['remain_ordered_date']  = strlen($row['remain_ordered_date'])  ? 1 : 0;
            $row['remain_arrived_date']  = strlen($row['remain_arrived_date'])  ? 1 : 0;
            $row['remain_shipping_date'] = strlen($row['remain_shipping_date']) ? 1 : 0;
            $row['remain_stockout_date'] = strlen($row['remain_stockout_date']) ? 1 : 0;
            */

            // 原価 円 or 元
            if ($locale == 'cn') {
              $row['cost'] = round($row['cost'] / $rateCny, 2);
            }
            
            // 画像のリンク先
            $row['image_url'] = "https:".TbMainproductsRepository::createImageUrl($row['image_dir'], $row['image_name'], sprintf('//%s/images/', $this->getParameter('host_plusnao')));

            $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
            $file->fwrite($line);

            flush();
          }
        }
      );

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="order_list_%s.csv";', $now->format('YmdHis')));
      $response->send();

      return $response;

    } catch (\Exception $e) {
      // エラー時
      $logger->error($e->getMessage());
      $this->addFlash('danger', $this->get('translator')->trans('vendor.csv.error_message'));

      $agent = $this->validateVendor();
      if ($agent) {
        return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $request->getLocale(), 'agentName' => $agent->getLoginName()]);
      } else {
        return $this->redirectToRoute('plusnao_logout');
      }
    }
  }

  /**
   * CSVアップロード・商品リスト更新処理
   * 
   * @deprecated　おタオバオ様専用機能　お取引がないためページング版では導線なし　旧版削除時点で削除
   */
  public function orderListCsvUploadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('vendor list csv upload: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'info' => []
    ];

    try {
      $account = $this->getLoginUser();
      // IDチェック
      $agent = $this->validateVendor();

      // ファイル変換、モール判定（ヘッダチェック）

      /** @var SplFileInfo[] $files */
      $file = $request->files->get('upload');
      if (!$file) {
        $result['message'] = 'ファイルがアップロードされませんでした。';
        throw new \RuntimeException($result['message']);
      }

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'UTF-8', 'UTF-8');

      // ヘッダチェック
      rewind($tmpFile);
      $headers = fgetcsv($tmpFile, null, ',', '"');

      if ($headers != self::UPLOAD_CSV_HEADER) {
        $result['message'] = 'CSVファイルのヘッダが違います。';
        throw new \RuntimeException($result['message']);
      }

      // 取込処理
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      // 一時テーブル作成
      $tmpTableName = $this->createTemporaryTableOrderListCsv($dbMain, $agent->getId());

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        INTO TABLE {$tmpTableName}
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      $updateDate = new \DateTime();
      $updatePerson = $account->getUsername();

      $sql = <<<EOD
        UPDATE tb_individualorderhistory i
        INNER JOIN {$tmpTableName} t
          ON  i.発注伝票番号 = t.発注伝票番号
          AND i.発行日 = t.発注日
          AND i.商品コード = t.商品コード
        SET i.shipping_number = t.箱番号
          , i.vendor_comment  = CONCAT(IFNULL(i.vendor_comment,''), '\\r\\n', t.連絡事項)
          , i.remain_status =
            CASE 
              WHEN t.状態 = 3 THEN :ordered
              WHEN t.状態 = 8 THEN :arrived
              WHEN t.状態 = 12 THEN :waited
              WHEN t.状態 = 13 THEN :shipped
              WHEN t.状態 = 7 THEN :shortage
              ELSE i.remain_status
            END
          , i.remain_ordered_date = CASE WHEN t.状態 = 3 THEN :updateDate ELSE i.remain_ordered_date END
          , i.remain_arrived_date = CASE WHEN t.状態 = 8 THEN :updateDate ELSE i.remain_arrived_date END
          , i.remain_waiting_date = CASE WHEN t.状態 = 12 THEN :updateDate ELSE i.remain_waiting_date END
          , i.remain_shipping_date = CASE WHEN t.状態 = 13 THEN :updateDate ELSE i.remain_shipping_date END
          , i.remain_stockout_date = CASE WHEN t.状態 = 7 THEN :updateDate ELSE i.remain_stockout_date END
          , i.remain_ordered_person = CASE WHEN t.状態 = 3 THEN :updatePerson ELSE i.remain_ordered_person END
          , i.remain_arrived_person = CASE WHEN t.状態 = 8 THEN :updatePerson ELSE i.remain_arrived_person END
          , i.remain_waiting_person = CASE WHEN t.状態 = 12 THEN :updatePerson ELSE i.remain_waiting_person END
          , i.remain_shipping_person = CASE WHEN t.状態 = 13 THEN :updatePerson ELSE i.remain_shipping_person END
          , i.remain_stockout_person = CASE WHEN t.状態 = 7 THEN :updatePerson ELSE i.remain_stockout_person END
          , i.warehousing_number = t.問合せ番号
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':updateDate', $updateDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      $stmt->bindValue(':updatePerson', $updatePerson, \PDO::PARAM_STR);
      $stmt->bindValue(':ordered', TbIndividualorderhistoryRepository::REMAIN_STATUS_ORDERED, \PDO::PARAM_STR);
      $stmt->bindValue(':arrived', TbIndividualorderhistoryRepository::REMAIN_STATUS_ARRIVED, \PDO::PARAM_STR);
      $stmt->bindValue(':waited', TbIndividualorderhistoryRepository::REMAIN_STATUS_WAITED, \PDO::PARAM_STR);
      $stmt->bindValue(':shipped', TbIndividualorderhistoryRepository::REMAIN_STATUS_SHIPPED, \PDO::PARAM_STR);
      $stmt->bindValue(':shortage', TbIndividualorderhistoryRepository::REMAIN_STATUS_SHORTAGE, \PDO::PARAM_STR);
      $stmt->execute();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $repo->csvUploadUpdateTbProductchoiceitems($tmpTableName);

      fclose($tmpFile);

      $result['message'] = $this->get('translator')->trans('vendor.csv.upload_message');

    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : '処理を実行できませんでした。';
    }

    return new JsonResponse($result);
  }

  /**
   * DESCRIPTIONマスタCSVダウンロード処理
   * 
   * @deprecated　おタオバオ様専用機能　お取引がないためページング版では導線なし　旧版削除時点で削除
   */
  public function descriptionListCsvDownloadAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $agent = null;
    try {
      $now = new \DateTimeImmutable();
      // IDチェック
      $agent = $this->validateVendor();

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      $searchConditions = $request->get('csv');
      if (!is_array($searchConditions)) {
        $searchConditions = [];
      }

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $descriptionList = $repo->getDescriptionListCsvDownloadData();

      // CSV出力
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $headers = ['id' ,'description_en' ,'description_cn'];

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($descriptionList, $stringUtil, $headers) {
          $file = new \SplFileObject('php://output', 'w');
          $eol = "\n";

          // BOM付きUTF-8で出力すれば、Excelで開ける。
          $file->fwrite(pack('C*', 0xEF, 0xBB, 0xBF));

          // ヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
          $file->fwrite($header);

          foreach($descriptionList as $row) {
            $line = $stringUtil->convertArrayToCsvLine($row, array_values($headers), [], ",") . $eol;
            $file->fwrite($line);
            flush();
          }
        }
        );

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="description_list_%s.csv";', $now->format('YmdHis')));
      $response->send();

      return $response;

    } catch (\Exception $e) {
      // エラー時
      $logger->error($e->getMessage());
      $this->addFlash('danger', $this->get('translator')->trans('vendor.csv.error_message'));
      if ($agent) {
        return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $request->getLocale(), 'agentName' => $agent->getLoginName()]);
      } else {
        return $this->redirectToRoute('plusnao_logout');
      }
    }
  }

  /**
   * 材質商品説明マスタCSVダウンロード処理
   * 
   * @deprecated　おタオバオ様専用機能　お取引がないためページング版では導線なし　旧版削除時点で削除
   */
  public function hintListCsvDownloadAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $agent = null;
    try {
      $now = new \DateTimeImmutable();
      // IDチェック
      $agent = $this->validateVendor();

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      $searchConditions = $request->get('csv');
      if (!is_array($searchConditions)) {
        $searchConditions = [];
      }

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $hintList = $repo->getHintListCsvDownloadData();

      // CSV出力
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $headers = ['id', 'hint_ja', 'hint_cn'];

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($hintList, $stringUtil, $headers) {
          $file = new \SplFileObject('php://output', 'w');
          $eol = "\n";

          // BOM付きUTF-8で出力すれば、Excelで開ける。
          $file->fwrite(pack('C*', 0xEF, 0xBB, 0xBF));

          // ヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
          $file->fwrite($header);

          foreach($hintList as $row) {
            $line = $stringUtil->convertArrayToCsvLine($row, array_values($headers), [], ",") . $eol;
            $file->fwrite($line);
            flush();
          }
        }
        );

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="hint_list_%s.csv";', $now->format('YmdHis')));
      $response->send();

      return $response;

    } catch (\Exception $e) {
      // エラー時
      $logger->error($e->getMessage());
      $this->addFlash('danger', $this->get('translator')->trans('vendor.csv.error_message'));
      if ($agent) {
        return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => $request->getLocale(), 'agentName' => $agent->getLoginName()]);
      } else {
        return $this->redirectToRoute('plusnao_logout');
      }
    }
  }

  /**
   * CSV取込用一時テーブル作成 商品詳細CVS
   * @param \Doctrine\DBAL\Connection $db
   * @return String
   */
  public function createTemporaryTableOrderListCsv($db)
  {
    $tableName = 'tmp_work_vendor_order_list';

    $db->query("DROP TEMPORARY TABLE IF EXISTS " . $tableName);

    $sql = <<<EOD
      CREATE TEMPORARY TABLE {$tableName} (
         `発注伝票番号` int(10) unsigned NOT NULL
        ,`発注日` date DEFAULT NULL
        ,`商品コード` varchar(255) NOT NULL
        ,`商品サブコード` varchar(255) NOT NULL
        ,`数量` int(11) NOT NULL DEFAULT '0'
        ,`注文番号` int(11) NOT NULL DEFAULT '0'
        ,`SKUID` int(11) NOT NULL DEFAULT '0'
        ,`同梱ID` int(11) NOT NULL DEFAULT '0'
        ,`状態` int(11) NOT NULL DEFAULT '0'
        ,`箱番号` varchar(255) DEFAULT NULL 
        ,`単価` decimal(10,2) NOT NULL DEFAULT '0.00' 
        ,`送料` decimal(10,2) NOT NULL DEFAULT '0.00' 
        ,`値引` decimal(10,2) NOT NULL DEFAULT '0.00' 
        ,`連絡事項`  text
        ,`支払日` date
        ,`発送日` date
        ,`検品日` date
        ,`欠品日` date
        ,`問合せ番号` varchar(100) DEFAULT NULL 
        , `重量` int(11) NOT NULL DEFAULT '0' 
        , `縦` int(11) NOT NULL DEFAULT '0' 
        , `横` int(11) NOT NULL DEFAULT '0' 
        , `高` int(11) NOT NULL DEFAULT '0' 
        , `DESCRIPTION` int(11)
        , `材質商品説明` int(11)
      ) Engine=InnoDB DEFAULT CHARSET utf8
EOD;
    $db->query($sql);

    return $tableName;
  }

  /**
   * 注残一覧画面 伝票分割処理
   * @param Request $request
   * @return Response
   */
  public function orderListSplitVoucherAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => []
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      $parentId = $request->get('parent_id');
      $moveNum = $request->get('move_num');

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      /** @var TbIndividualorderhistory $parentOrder */
      $parentOrder = null;
      if ($parentId) {
        $parentOrder = $repo->find($parentId);
      }

      if (!$parentOrder) {
        $result['status'] = 'ng';
        throw new \RuntimeException('can not find individualhistory. (for update shipping number). ' . 'voucherId: ' . $parentId);
      }

      $newOrder = $repo->splitOrder($parentOrder, $moveNum);
      if (!$newOrder) {
        throw new \RuntimeException('分割明細の作成に失敗しました。');
      }

      // 双方ともデータ再取得
      $parentItems = $repo->getListByAgentCode($parentOrder->getAgentCode(), ['id' => $parentOrder->getId()], true); // 注残0も取得する
      $newItems = $repo->getListByAgentCode($newOrder->getAgentCode(), ['id' => $newOrder->getId()]);
      if (!$parentItems || !$newItems) {
        throw new \RuntimeException('分割明細の取得に失敗しました。');
      }

      // 一覧表示用に変換
      $locale = $request->getLocale();
      $parentItems = $this->createListData($parentItems, $locale);
      $newItems = $this->createListData($newItems, $locale);

      $result['result'] = [
        'parentOrder' => $parentItems[0]
        , 'newOrder' => $newItems[0]
      ];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * 注残一覧画面 商品諸元更新
   * @param Request $request
   * @return Response
   */
  public function orderListUpdateProductSpecAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => []
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      $updateData = $request->get('product');
      $logger->info(print_r($updateData, true));

      if (!is_array($updateData) || !isset($updateData['daihyo_syohin_code'])) {
        throw new \RuntimeException('送信されたデータが不正です。');
      }

      // 簡易バリデーション
      if (
        (!isset($updateData['weight']) || !is_numeric($updateData['weight']))
        || (!isset($updateData['depth']) || !is_numeric($updateData['depth']))
        || (!isset($updateData['width']) || !is_numeric($updateData['width']))
        || (!isset($updateData['height']) || !is_numeric($updateData['height']))
      ) {
        throw new \RuntimeException('invalid data.');
      }

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbMainproducts $product */
      $product = $repo->find($updateData['daihyo_syohin_code']);

      if (!$product) {
        throw new \RuntimeException('商品データが取得できませんでした。');
      }

      $product->setWeight($updateData['weight']);
      $product->setDepth($updateData['depth']);
      $product->setWidth($updateData['width']);
      $product->setHeight($updateData['height']);

      // ここで、値が変更されていれば自動で重厚計測チェックを復活させる？

//      if (isset($updateData['weight_check_need_flg'])) {
//        $product->setWeightCheckNeedFlg($updateData['weight_check_need_flg']);
//      }

      $this->getDoctrine()->getManager('main')->flush();

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * 注残一覧画面 品質レベル更新処理
   * @param Request $request
   * @return Response
   */
  public function orderListUpdateQualityLevelAction(Request $request)
  {
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];

    /** @var Logger $logger */
    $logger = $this->get('Logger');

    try {
      // IDチェック
      $agent = $this->validateVendor();

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $voucherId = $request->get('voucher_id');
      $level = $request->get('level', TbMainproductsCal::QUALITY_LEVEL_NONE);
      $logger->info('update quality level voucher_id: ' . $voucherId . ' / quality : ' . $level);

      $order = null;
      if ($voucherId) {
        /** @var TbIndividualorderhistory $order */
        $order = $repo->find($voucherId);
      }

      if (!$order) {
        throw new \RuntimeException('can not find individualhistory. (for update quality level). ' . ' / voucher_id: ' . $voucherId);
      }

      $cal = null;
      $choice = $order->getChoiceItem();
      $product = $choice ? $choice->getProduct() : null;
      $cal = $product ? $product->getCal() : null;
      if (!$cal) {
        throw new \RuntimeException('can not find product data. (for update quality level). ' . ' / voucher_id: ' . $voucherId);
      }

      $cal->setQualityLevel($level);
      $cal->setQualityLevelUpdated(new \DateTime());
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['result'] = [
        'daihyoSyohinCode' => $cal->getDaihyoSyohinCode()
        , 'qualityLevel' => $level
      ];

      // NGならRedmineチケット作成
      if ($level == TbMainproductsCal::QUALITY_LEVEL_NG) {
        // 本番環境であれば Redmineのチケットも作成する(parameters.yml)
        if ($this->getParameter('redmine_create_order_quality_check_ticket')) {
          try {
            $agent = $this->getAgent();
            $account = $this->getLoginUser();
            $now = new \DateTime();
            /** @var WebAccessUtil $webAccessUtil */
            $webAccessUtil = $this->get('misc.util.web_access');

            $body = <<<EOD
|処理        |注残一覧 品質チェック|
|依頼先      |{$agent->getName()}|
|担当者      |{$account->getUsername()}|
|日時        |{$now->format('Y-m-d H:i:s')}|
|発注伝票番号|{$order->getVoucherNumber()}|
|商品コード  |{$choice->getNeSyohinSyohinCode()}|

EOD;

            $ticket = [
              'issue' => [
                'subject' => '[注残一覧 品質チェック][NG] ' . $choice->getNeSyohinSyohinCode()
                , 'project_id' => $this->getParameter('redmine_create_order_quality_check_ticket_project')
                , 'priority_id' => $this->getParameter('redmine_create_order_quality_check_ticket_priority')
                , 'description' => $body
                , 'assigned_to_id' => $this->getParameter('redmine_create_order_quality_check_ticket_user')
                , 'tracker_id' => $this->getParameter('redmine_create_order_quality_check_ticket_tracker')
                // , 'category_id'     => ''
                // , 'status_id'       => ''
              ]
            ];

            $ret = $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
            $logger->info('redmine create ticket:' . $ret);

          } catch (\Exception $e) {
            // ここでのエラーはひとまず握り潰す。
            $logger->error($e->getMessage());
          }
        }
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }


  /**
   * 注残履歴集計画面 （AppBundle forward）
   * @param Request $request
   * @return Response
   */
  public function remainOrderStatusDateListAction(Request $request)
  {
    $agent = $this->validateVendor();
    // $locale = $request->getLocale();

    $params = $request->query->all();
    $params['agent'] = $agent->getId(); // AgentCode固定
    $params['all_flag'] = false;

    return $this->forward('AppBundle:Delivery:remainOrderStatusDateList', [], $params);
  }

  /**
   * 注残履歴集計画面 作業者別データ取得 （AppBundle forward）
   * @param Request $request
   * @return Response
   */
  public function remainOrderStatusDateListByPersonAction(Request $request)
  {
    $agent = $this->validateVendor();
    // $locale = $request->getLocale();

    $params = $request->query->all();
    $params['agent'] = $agent->getId(); // AgentCode固定

    return $this->forward('AppBundle:Delivery:remainOrderStatusDateListByPerson', [], $params);
  }


  /**
   * 一覧表示用配列作成処理
   * @param array $list
   * @param string $locale
   * @return array
   */
  private function createListData($list, $locale)
  {
    $data = [];

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    $rateCny = floatval($commonUtil->getSettingValue('EXCHANGE_RATE_CNY'));
    if (!$rateCny) {
      $rateCny = 17.00; // イレギュラーだがとにかく値を入れる
    }

    // 仕入先アドレス取得用
    $productCodes = [];

    foreach ($list as $row) {
      $row['image_list_page_url'] = $this->generateUrl('plusnao_vendor_goods_image', array('daihyoSyohinCode' => $row['daihyo_syohin_code']));
      $row['image_url'] = TbMainproductsRepository::createImageUrl($row['image_dir'], $row['image_name'], sprintf('//%s/images/', $this->getParameter('host_plusnao')));
      $row['yahoo_url'] = sprintf('http://store.shopping.yahoo.co.jp/plusnao/%s.html', $row['daihyo_syohin_code']);
      $row['mignonlindo_url'] = sprintf('https://store.shopping.yahoo.co.jp/mignonlindo/%s.html', $row['daihyo_syohin_code']);
      $row['rakuten_url'] = TbMainproductsRepository::getRakutenDetailUrl($row['daihyo_syohin_code']);

      $sireCode = $row['current_sire_code']; // 仕入先アドレスは該当商品の現時点の仕入先のものを取得
      if (!isset($productCodes[$sireCode])) {
        $productCodes[$sireCode] = [];
      }
      $productCodes[$sireCode][] = $row['daihyo_syohin_code'];

      // 原価 円 or 元
      if ($locale == 'cn') {
        $row['cost'] = round($row['cost'] / $rateCny, 2);
      }

      $data[] = $row;
    }

    // 仕入先アドレス取得
    /** @var TbVendoraddressRepository $vendorAddressRepo */
    $vendorAddressRepo = $this->getDoctrine()->getRepository('MiscBundle:TbVendoraddress');
    $vendorAddresses = $vendorAddressRepo->getVendorAddressListBySireCode($productCodes);
    foreach ($data as &$row) {
      $row['addresses'] = [];
      if (isset($vendorAddresses[$row['current_sire_code']][$row['daihyo_syohin_code']])) {
        $row['addresses'] = $vendorAddresses[$row['current_sire_code']][$row['daihyo_syohin_code']];
      }
    }

    return $data;
  }

  public function getRateAction(Request $request)
  {
    $data = [];
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $data = [
        'EXCHANGE_RATE_USD' => floatval($commonUtil->getSettingValue('EXCHANGE_RATE_USD'))
      ];
      $result['result'] = $data;
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  public function updateRateAction(Request $request)
  {
    $data = [];
    $result = [
      'status' => 'ok'
      , 'result' => null
    ];
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      $commonUtil->updateSettingValue('EXCHANGE_RATE_USD', $request->get('rateUsd'));
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $logger->error($e->getMessage());
    }

    return new JsonResponse($result);
  }

  /**
   * 発送種別変更Action
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function updateShippingTypeAction(Request $request)
  {
    $data = [];
    $result = [
        'status' => 'ok'
        , 'result' => null
    ];
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      $id = $request->get('voucher_id');
      $currentShippingType = $request->get('current_shipping_type');
      if (is_null($id) || ($currentShippingType != TbIndividualorderhistoryRepository::SHIPPING_TYPE_AIR
          && $currentShippingType != TbIndividualorderhistoryRepository::SHIPPING_TYPE_CONTAINER)) {
        $result = [
            'status' => 'ng'
            , 'result' => 'send data error!'
        ];
      }
      // 送信値の逆にする　他スレッドで更新済みの場合は、結果的に変更なし
      $newShippingType = $currentShippingType ==
          TbIndividualorderhistoryRepository::SHIPPING_TYPE_AIR
              ? TbIndividualorderhistoryRepository::SHIPPING_TYPE_CONTAINER
              : TbIndividualorderhistoryRepository::SHIPPING_TYPE_AIR;
              /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $obj = $repo->find($id);
      $obj->setShippingType($newShippingType);
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();
      $result['result'] = ['shipping_type' => $newShippingType];

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['result'] = $e->getMessage();
      $logger->error($e->getMessage());
    }
    return new JsonResponse($result);
  }

  /**
   * 商品画像表示画面
   * AppBundle\Controller\GoodsController::imageEditAction
   * から画像表示機能のみ移植して実装している（画面周りのソースも同）
   * 
   * @param string $daihyoSyohinCode
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function goodsImageAction(Request $request)
  {
    /** @var TbMainproductsRepository $repoProduct */
    $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $product = $repoProduct->find($request->get('daihyoSyohinCode'));

    /** @var ProductImagesRepository $repoImages */
    $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');

    $data = [];
    $images = $repoImages->findProductImages($request->get('daihyoSyohinCode'));

    /** @var ImageUtil $imageUtil */
    $imageUtil = $this->get('misc.util.image');

    foreach($images as $image) {
      $row = $image->toScalarArray('camel');
      $row['fileDirPath'] = $image->getFileDirPath();

      $info = $imageUtil->getImageSize($image);
      $row['size'] = $info['size'];
      $row['width'] = $info['width'];
      $row['height'] = $info['height'];

      $data[] = $row;
    }

    // 画面表示
    return $this->render('PlusnaoMainBundle:Vendor:goods-image.html.twig', [
        'account' => $this->getLoginUser()
      , 'agent' => $this->getAgent()
      , 'product' => $product
      , 'imageUrlParent' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'dataJson' => json_encode($data)
    ]);
  }

  /**
   * 伝票毎注残管理画面
   * @param Request $request
   * @return Response
   */
  public function remainOrderByVoucherAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // IDチェック
    $agent = $this->validateVendor();
    $locale = $request->getLocale();

    // URLにagentName が含まれていない場合にはリダイレクト
    if (! $request->get('agentName')) {
      return $this->redirectToRoute('plusnao_vendor_remain_order_by_voucher', ['_locale' => $locale, 'agentName' => $agent->getLoginName()]);
    }

    $remainOrderService = $this->container->get('misc.service.remain_order_by_voucher');
    // 伝票毎注残データ取得
    $remainOrderData = $remainOrderService->fetchRemainOrderData($agent);
    return $this->render('PlusnaoMainBundle:Vendor:remain-order-by-voucher.html.twig', [
      'account' => $this->getLoginUser()
      , 'agent' => $agent
      , 'locale' => $locale
      , 'remainOrderJson' => json_encode($remainOrderData)
    ]);
  }

  /**
   * 伝票度注文管理画面 コメント更新
   * @param Request $request
   * @return JsonResponse $result
   */
  public function remainOrderUpdateCommentAction(Request $request)
  {
    $result = [
      'status' => 'ok',
      'message' => '正常に更新しました。'
    ];

    try {
      if ($request->query->has('voucherNumber')) {
        throw new InvalidParameterException('伝票番号が存在しません。');
      }
      $voucherNumber = $request->request->get('voucherNumber');

      if ($request->query->has('agentCode')) {
        throw new InvalidParameterException('依頼先コードが存在しません。');
      }
      $agentCode = $request->request->get('agentCode');

      if ($request->query->has('comment')) {
        throw new InvalidParameterException('コメントが存在しません。');
      }
      $comment = $request->request->get('comment');

      /** @var TbIndividualorderCommentRepository $commentRepo */
      $commentRepo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderComment');
      $commentRepo->updateComment($voucherNumber, $agentCode, $comment, $this->getLoginUser()->getId());

    } catch (\Exception $error) {
      $result['status'] = 'ng';
      $result['message'] = $error->getMessage();
    }

    return new JsonResponse($result);
  }
}
