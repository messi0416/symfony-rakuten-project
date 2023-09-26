<?php

namespace AppBundle\Controller;

use BatchBundle\Job\MainJob;
use BatchBundle\Job\SubmitPurchaseOrderListJob;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\TbClickpostConversionRepository;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbStockTransportRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbWarehouseStockMovePickingListRepository;
use MiscBundle\Entity\TbClickpostConversion;
use MiscBundle\Entity\TbIndividualorderhistory;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductLocation;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbStockTransport;
use MiscBundle\Entity\TbStockTransportDetail;
use MiscBundle\Entity\TbVendormasterdata;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\TbWarehouseResultHistory;
use MiscBundle\Service\ShoplistSpeedbinService;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringUtil;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
use PHPExcel_Worksheet_MemoryDrawing;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MiscBundle\Entity\Repository\TbRakutenExpressConversionRepository;
use Symfony\Component\Validator\Constraints\Date;

/**
 * ロジ関連
 * @package AppBundle\Controller
 */
class LogisticsController extends BaseController
{
  const SUBMIT_MAX_NUM = 300; // 2018/05/15時点で最大は 209件/発送番号

  const TRANSPORT_DATA_DIR_PATH = "/Transport";

  /**
   * 仕入・注残一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function purchaseListAction()
  {
    // 簡易版を先行実装。こちらは現状利用無し。
//    $account = $this->getLoginUser();
//
//    /** @var TbIndividualorderhistoryRepository $repoOrder */
//    $repoOrder = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
//    $statuses = $repoOrder->getRemainStatusList();
//
//    // 画面表示
//    return $this->render('AppBundle:Logistics:purchase-list.html.twig', [
//        'account' => $account
//      , 'statuses' => $statuses
//    ]);
  }

  /**
   * 仕入・注残一覧 簡易版
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function purchaseListLightAction()
  {
    $account = $this->getLoginUser();

    /** @var TbIndividualorderhistoryRepository $repoOrder */
    $repoOrder = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    $statuses = $repoOrder->getRemainStatusList();

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDown();

    // 画面表示
    return $this->render('AppBundle:Logistics:purchase-list-light.html.twig', [
        'account' => $account
      , 'statuses' => $statuses
      , 'warehouses' => $warehouses
    ]);
  }

  /**
   * 仕入・注残一覧 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findPurchaseListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'vendorList' => []
      , 'agentList' => []
      , 'pageInfo' => []
      , 'count' => 0
    ];

    try {
      $page = $request->get('page', 1);
      $limit = $request->get('limit', 100);

      // 絞込条件
      $defaultConditions = [
        'remainNum' => 1
      ];
      $conditions = $request->get('search', []);
      $conditions = array_merge($defaultConditions, $conditions);

      $orders = [];
      $sortKey = $request->get('o');
      $sortDirection = $request->get('od');

      if ($sortKey) {
        if ($sortDirection) {
          $orders = [
            $sortKey => $sortDirection
          ];
        }
      }

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $pagination = $repo->findIndividualOrders($conditions, $orders, $page, $limit);

      $result['list'] = $pagination->getItems();
      $result['pageInfo'] = $pagination->getPaginationData();

      // 仕入先一覧取得（全件）
      /** @var BaseRepository $repoVendor */
      $repoVendor = $this->getDoctrine()->getRepository('MiscBundle:TbVendormasterdata');
      /** @var TbVendormasterdata[] $vendors */
      $vendors = $repoVendor->findBy(['status' => '0'], ['displayOrder' => 'ASC']);
      foreach($vendors as $vendor) {
        $result['vendorList'][] = $vendor->toScalarArray('camel');
      }

      // 依頼先一覧取得（全件）
      /** @var BaseRepository $repoAgent */
      $repoAgent = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
      /** @var PurchasingAgent[] $agents */
      $agents = $repoAgent->findBy([], ['display_order' => 'ASC']);
      foreach($agents as $agent) {
        $result['agentList'][] = $agent->toScalarArray('camel');
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 仕入・注残 入力確定処理 簡易版
   */
  public function submitPurchaseListLightAction(Request $request)
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
      set_time_limit(60 * 60); // 処理キュー待ち 1時間

      $targetList = [];
      $submitMode = $request->get('submitMode', 'regular');

      // 絞込条件
      $defaultConditions = [
        'remainNum' => 1
      ];
      $conditions = $request->get('search', []);
      $conditions = array_merge($defaultConditions, $conditions);

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $pagination = $repo->findIndividualOrders($conditions, [ 'orderNumber' => 'ASC', 'syohinCode' => 'ASC' ], 1, 10000);

      $shippingNumberCount = [];
      foreach($pagination->getItems() as $item) {
        $targetList[] = [
            'id'                => $item['id']
          , 'orderNumber'       =>  $item['発注伝票番号']
          , 'lineNumber'        =>  $item['明細行']
          , 'syohinCode'        =>  $item['商品コード']
          , 'remainNum'         =>  $item['注残計']
          , 'inputRegularNum'   => ($submitMode === 'shortage' ? 0 : $item['注残計'])
          , 'inputDefectiveNum' => 0
          , 'inputShortageNum'  => ($submitMode === 'shortage' ? $item['注残計'] : 0)
          , 'shippingNumber'    => $item['shipping_number']
        ];

        $shippingNumber = strlen(trim($item['shipping_number'])) ? trim($item['shipping_number']) : '(none)'; // 空白の差は一致として扱う。
        if (!isset($shippingNumberCount[$shippingNumber])) {
          $shippingNumberCount[$shippingNumber] = 0;
        }
        $shippingNumberCount[$shippingNumber]++;
      }

      if (count($shippingNumberCount) > 1) {
        throw new \RuntimeException('複数の発送番号が含まれています。一括で入力確定はできません。[' . implode(' / ', array_keys($shippingNumberCount)) . ']');
      }

      if (count($targetList) > self::SUBMIT_MAX_NUM) {
        throw new \RuntimeException(self::SUBMIT_MAX_NUM . '件以上の発注明細が含まれています。一括で入力確定はできません。[' . count($targetList) . '件]');
      }

      // Jobをmainキューに登録
      $rescue = $this->getResque();
      $job = new SubmitPurchaseOrderListJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command' => MainJob::COMMAND_KEY_SUBMIT_PURCHASE_ORDER_LIST
        , 'account' => $this->getLoginUser()->getId()
        , 'targetList' => $targetList
        , 'prefix' => $request->get('prefix')
        , 'warehouseId' => $request->get('warehouseId', $this->getLoginUser()->getWarehouseId())
      ];

      $jobStatus = $rescue->enqueue($job, true); // リトライなし

      // 完了チェック処理
      if ($jobStatus->isTracking()) {
        $limitSec = 60 * 55; // webサーバタイムアウトが60分であるため、それまでに終了する。
        $interval = 3;
        $status = null;
        while (true) {
          $status = $jobStatus->get();
          if (
            !in_array($status, [
                \Resque_Job_Status::STATUS_WAITING
              , \Resque_Job_Status::STATUS_RUNNING
            ])
            || ($limitSec <= 0)
          ) {
            break;
          }

          sleep($interval);
          $limitSec -= $interval;
        }

        switch ($status) {
          case \Resque_Job_Status::STATUS_COMPLETE:
            $result['message'] = '仕入入力確定処理を完了しました。';
            break;
          case \Resque_Job_Status::STATUS_WAITING:
            throw new \RuntimeException('仕入入力確定処理が開始されませんでした。システムの状態を確認してください。');
            break;
          case \Resque_Job_Status::STATUS_FAILED:
            throw new \RuntimeException('仕入入力確定処理に失敗しました。エラー内容を確認してください。');
            break;
          case \Resque_Job_Status::STATUS_RUNNING:
            throw new \RuntimeException('仕入入力確定処理が終了しません。処理の状態を確認してください。');
            break;
          default:
            throw new \RuntimeException('イレギュラーが発生しました。データおよび処理の状態を確認してください。');
            break;
        }

      } else {
        $result['message'] = '仕入注残入力確定処理をキューに追加しました。';
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 注残ダウンロード Excelファイル
   * @param Request $request
   * @return Response
   */
  public function downloadXlsAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      set_time_limit(60 * 60);
      $page = 1;
      $limit = 50000;

      // 絞込条件
      $defaultConditions = [
        'remainNum' => 1
      ];
      $conditions = $request->get('search', []);
      $conditions = array_merge($defaultConditions, $conditions);

      $orders = [];
      $sortKey = $request->get('o');
      $sortDirection = $request->get('od');

      if ($sortKey) {
        if ($sortDirection) {
          $orders = [
            $sortKey => $sortDirection
          ];
        }
      }

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $stmt = $repo->findIndividualOrders($conditions, $orders, $page, $limit, 'stmt');

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      // ヘッダ
      $headers = [
          '発行日' => '日付'
        , '商品コード' => '品番'
        , 'image' => '画像'
        , '発注数' => 'ご注文 枚数'
        , 'quantity_price_cny' => '単品の値段 （元）'
        , 'quantity_price' => '単品の値段 （円）'
        , 'total_price' => '合計円'
        , 'size_color' => 'サイズ・カラー'
        , 'inspection' => '検品 有・無'
        , 'url' => '店URL'

        // 以下、空出力列
        , '出荷枚数' => '出荷枚数'
        , '価格合計（円)' => '価格合計（円)'
        , '店から検品所までの送料(元)' => '店から検品所までの送料(元)'
        , '店から検品所までの送料(円)' => '店から検品所までの送料(円)'
        , '請求合計' => '請求合計'
        , '出荷日' => '出荷日'
        , 'DHL番号' => 'DHL番号'
        , '箱' => '箱'
        , '着日' => '着日'
      ];


      // Excelファイル作成、出力
      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->get('misc.util.image');
      $imageDir = $imageUtil->getImageDir();

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      $templatePath = sprintf('%s/templates/Logistics/purchasing_order_template.xlsx', $fileUtil->getDataDir());
      $fs = new Filesystem();
      if (!$fs->exists($templatePath)) {
        throw new \RuntimeException('no template file [' . $templatePath . ']');
      }

      /** @var PHPExcel $objPHPExcel */
      $objPHPExcel = $this->get('phpexcel')->createPHPExcelObject($templatePath);

      // Set document properties
      $objPHPExcel->getProperties()->setCreator("Forest Inc.")
        ->setLastModifiedBy("Forest Inc.")
        ->setTitle("代行発注書")
        ->setSubject("")
        ->setDescription("")
        ->setKeywords("")
        ->setCategory("");

      // Rename worksheet
      $workSheet = $objPHPExcel->setActiveSheetIndex(0);
      $workSheet->setTitle('受注分');

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $cnyRate = floatval($commonUtil->getSettingValue('EXCHANGE_RATE_CNY'));
      if (!$cnyRate) {
        $cnyRate = '18.00'; // イレギュラー
      }

      // データ追記
      $columns = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'
      ];

      $line = 2; // 1行目はヘッダ行
      foreach($stmt as $row) {
        // 行の高さ設定
        $workSheet->getRowDimension( $line )->setRowHeight( 90 );

        // A: 日付
        $workSheet->setCellValue(sprintf('A%d', $line), $row['発行日']);
        // B: 品番
        $workSheet->setCellValue(sprintf('B%d', $line), $row['商品コード']);
        // C: 画像 => あとで
        // D: ご注文枚数
        $workSheet->setCellValue(sprintf('D%d', $line), $row['発注数']);
        // E: 単品の値段（元）
        $workSheet->setCellValue(sprintf('E%d', $line), sprintf('=ROUND(F%d/%.2f, 2)', $line, $cnyRate));
        // F: 単品の値段（円）
        $workSheet->setCellValue(sprintf('F%d', $line), $row['quantity_price']);
        // G: 合計円 =F2*D2
        $workSheet->setCellValue(sprintf('G%d', $line), sprintf('=F%d*D%d', $line, $line));
        // H: サイズ・カラー
        $workSheet->setCellValue(sprintf('H%d', $line), sprintf("%s\r%s", $row['colname'], $row['rowname']));
        $workSheet->getStyle(sprintf('H%d', $line))->getAlignment()->setWrapText(true);
        // I: 検品 有・無
        $workSheet->setCellValue(sprintf('I%d', $line), '無'); // 固定？
        // J: 店URL
        $workSheet->setCellValue(sprintf('J%d', $line), str_replace(" ", "\r", $row['urls']));
        $workSheet->getStyle(sprintf('J%d', $line))->getAlignment()->setWrapText(true);
        // K: (※空)出荷枚数
        $workSheet->setCellValue(sprintf('K%d', $line), '');
        // L: 価格合計（円） =F2*K2
        $workSheet->setCellValue(sprintf('L%d', $line), sprintf('=F%d*K%d', $line, $line));
        // M: (※空)店から検品所までの送料（元）
        $workSheet->setCellValue(sprintf('M%d', $line), '');
        // N: 店から検品所までの送料（円） =M2*Rate
        $workSheet->setCellValue(sprintf('N%d', $line), sprintf('=ROUND(M%d*%.2f, 0)', $line, $cnyRate));
        // O: 請求合計 =L2+N2
        $workSheet->setCellValue(sprintf('O%d', $line), sprintf('=L%d+N%d', $line, $line));
        // P: (※空)出荷日
        $workSheet->setCellValue(sprintf('P%d', $line), '');
        // Q: (※空)DHL番号
        $workSheet->setCellValue(sprintf('Q%d', $line), '');
        // R: (※空)箱
        $workSheet->setCellValue(sprintf('R%d', $line), '');
        // S: (※空)着日
        $workSheet->setCellValue(sprintf('S%d', $line), '');

        // 罫線
        // ※ setBorderStyle ではダメだった。
        $borderStyle = array(
          'borders' => array(
              'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
            , 'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
            , 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
            , 'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
          )
        );
        foreach ($columns as $col) {
          $cellName = sprintf('%s%d', $col, $line);
          $style = $workSheet->getCell($cellName)->getStyle();
          $style->applyFromArray($borderStyle);
        }

        // 画像ファイル縮小 ＆ 追加
        $fs = new Filesystem();
        if ($row['image_directory'] && $row['image_filename']) {
          $imagePath = sprintf('%s/%s/%s', $imageDir, $row['image_directory'], $row['image_filename']);

          if ($fs->exists($imagePath)) {

            $im = new \Imagick($imagePath);
            $im->stripImage(); // EXIF削除

            // リサイズ処理
            $height = $im->getImageHeight();
            if ($height > 68) {
              $im->resizeImage(0, 68, \Imagick::FILTER_POINT, 0);
            }

            $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
            $objDrawing->setName($row['daihyo_syohin_code']);
            $objDrawing->setDescription('');
            $objDrawing->setImageResource(imagecreatefromstring($im->getImageBlob()));
            $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
            $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setHeight(68);
            $objDrawing->setOffsetX(30);
            $objDrawing->setOffsetY(24);
            $objDrawing->setCoordinates(sprintf('C%d', $line));
            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
          }

        }


        // 次の行へ
        $line++;
      }

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($stmt, $objPHPExcel) {
          $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
          $objWriter->save('php://output');
        }
      );

      $fileName = sprintf('purchasing_order_%s.xlsx', (new \DateTime())->format('YmdHis'));

      $response->headers->set('Content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
      $response->headers->set('Cache-Control', 'max-age=0');
      $response->send();

      return $response;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());
      $fileName = sprintf('ERROR_purchasing_order_%s.txt', (new \DateTime())->format('YmdHis'));

      // エラーテキストファイル作成、出力
      $response = new Response($e->getMessage());
      $response->headers->set('Content-type', 'text/plain');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
      $response->headers->set('Cache-Control', 'max-age=0');

      return $response;
    }
  }

  /**
   * 移動伝票一覧
   * @param Request $request
   * @return Response
   */
  public function stockTransportListAction(Request $request)
  {
    /** @var TbStockTransportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');

//    // 取得条件
//    $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
//    if (is_null($request->get('date_from'))) {
//      $dateFrom = $today->setTime(0, 0, 0);
//    } else if (strlen($request->get('date_from'))) {
//      $dateFrom = (new \DateTimeImmutable($request->get('date_from')))->setTime(0, 0, 0);
//    } else {
//      $dateFrom = null;
//    }
//
//    if (is_null($request->get('date_to'))) {
//      $dateTo = $today;
//    } else if (strlen($request->get('date_to'))) {
//      $dateTo = (new \DateTimeImmutable($request->get('date_to')))->setTime(0, 0, 0);
//    } else {
//      $dateTo = null;
//    }

    $conditions = [
        'dateFrom' => null // $dateFrom
      , 'dateTo' => null // $dateTo
      , 'status' => $request->get('status', TbStockTransportRepository::STATUS_NONE) // 初期値: 未処理
      , 'transportCode' => $request->get('transport_code')
      , 'transportNumber' => $request->get('transport_number')
    ];

    $pagination = $repo->findTransportList($conditions, [], $request->get('page', 1));

    // 画面表示
    return $this->render('AppBundle:Logistics:stock-transport-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()

      , 'statusList' => TbStockTransportRepository::$STATUS_DISPLAYS
      , 'transportCodeList' => TbStockTransportRepository::$TRANSPORT_CODE_DISPLAYS
      , 'conditions' => $conditions
    ]);
    
  }

  /**
   * 移動伝票作成・編集
   * @param $id
   * @return Response
   */
  public function stockTransportEditAction($id)
  {
    /** @var TbStockTransportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');

    /** @var TbStockTransport $transport */
    $transport = null;
    if ($id == 'new') {
      $transport = new TbStockTransport();
      $transport->setDate(new \DateTime());
      $transport->setTransportCode(TbStockTransportRepository::TRANSPORT_CODE_WAREHOUSE); // 初期値
    } else {
      $transport = $repo->getOne($id);
    }

    if (!$transport) {
      throw new \RuntimeException('データがありません。');
    }

    $details = [];
    foreach($transport->getDetails() as $detail) {
      $details[] = $detail->toScalarArray('camel');
    }
    $statusList = [];
    foreach(TbStockTransportRepository::$STATUS_DISPLAYS as $status => $name) {
      $statusList[] = [
          'status' => $status
        , 'name' => $name
      ];
    }
    $transportCodeList = [];
    foreach(TbStockTransportRepository::$TRANSPORT_CODE_DISPLAYS as $code => $name) {
      $transportCodeList[] = [
          'code' => $code
        , 'name' => $name
      ];
    }

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');


    // 画面表示
    return $this->render('AppBundle:Logistics:stock-transport-edit.html.twig', [
        'account' => $this->getLoginUser()
      , 'transport' => $transport
      , 'transportJson' => json_encode($transport->toScalarArray('camel'))
      , 'details' => json_encode($details)
      , 'statusList' => json_encode($statusList)
      , 'transportCodeList' => json_encode($transportCodeList)
      , 'warehouseList' => json_encode($repoWarehouse->getPullDownAll())
    ]);
  }

  /**
   * 移動伝票更新処理(Ajax)
   * @param $id
   * @param Request $request
   * @return JsonResponse
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function stockTransportUpdateAction($id, Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var TbStockTransportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $dbMain->beginTransaction();

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $warehouses = $repoWarehouse->getPullDownObjects();

      /** @var TbStockTransport $transport */
      $transport = null;
      if ($id == 'new') {
        $transport = new TbStockTransport();
        if ($this->getLoginUser()) {
          $transport->setAccount($this->getLoginUser()->getUsername());
        }

        $em->persist($transport);
      } else {
        $transport = $repo->getOne($id);
      }
      if (!$transport) {
        throw new \RuntimeException('データがありません。');
      }

      $inputTransport = $request->get('transport');
      $inputDetails = $request->get('details', []);

      // $logger->dump($transport);
      $logger->dump($inputTransport);
      $logger->dump($inputDetails);

      $transport->setDate(new \DateTime($inputTransport['date']));
      $transport->setStatus($inputTransport['status']);
      $transport->setTransportCode($inputTransport['transportCode']);
      $transport->setTransportNumber($inputTransport['transportNumber']);
      $transport->setShippingMethod($inputTransport['shippingMethod']);
      $transport->setShippingNumber($inputTransport['shippingNumber']);

      $transport->setDepartureWarehouseId($inputTransport['departureWarehouseId']);
      $departure = '';
      if ($transport->getDepartureWarehouseId() && isset($warehouses[$transport->getDepartureWarehouseId()])) {
        $departure = $warehouses[$transport->getDepartureWarehouseId()]->getName();
      }
      $transport->setDeparture($departure);

      $transport->setDestinationWarehouseId($inputTransport['destinationWarehouseId']);
      $destination = '';
      if ($transport->getDestinationWarehouseId() && isset($warehouses[$transport->getDestinationWarehouseId()])) {
        $destination = $warehouses[$transport->getDestinationWarehouseId()]->getName();
      }
      $transport->setDestination($destination);

      
      $transport->setDepartureDate($inputTransport['departureDate'] ? new \DateTime($inputTransport['departureDate']) : null);
      $transport->setEstimatedDate($inputTransport['estimatedDate'] ? new \DateTime($inputTransport['estimatedDate']) : null);
      $transport->setArrivalDate($inputTransport['arrivalDate'] ? new \DateTime($inputTransport['arrivalDate']) : null);

      // ID確保のため、一旦ここでflush
      if ($id == 'new') {
        $em->flush();
        $em->refresh($transport);
      }

      $details = $transport->getDetails();
      // 扱いやすさのため、連想配列化
      $inputDetailsList = [];
      foreach($inputDetails as $inputDetail) {
        $inputDetailsList[$inputDetail['neSyohinSyohinCode']] = $inputDetail;
      }

      // 更新
      foreach($details as $detail) {
        $syohinCode = $detail->getNeSyohinSyohinCode();
        if (isset($inputDetailsList[$syohinCode])) {
          $item = $inputDetailsList[$syohinCode];
          $detail->setAmount($item['amount']);
          $detail->setPicked($item['picked']);

          unset($inputDetailsList[$syohinCode]); // 更新されたので除去。

        } else {
          $logger->info('deleted: ' . $syohinCode);
          $em->remove($detail);
          $em->detach($detail);
        }
      }
      // 新規
      foreach($inputDetailsList as $item) {
        $detail = new TbStockTransportDetail();
        $detail->setTransportId($transport->getId());
        $detail->setNeSyohinSyohinCode($item['neSyohinSyohinCode']);
        $detail->setAmount($item['amount']);
        $detail->setPicked($item['picked']);

        $em->persist($detail);
      }

      $em->flush();

      // detail 取得のため、再読み込み
      $transport = $repo->getOne($transport->getId());

      // choiceItems 移動中在庫数更新処理 （トリガも一括処理もふさわしく無さそうなので、移動伝票更新時に必ず行う。）
      $repo->updateChoiceItemTransportStocks();

      $dbMain->commit();

      $this->setFlash('success', sprintf('データを更新しました。(ID: %d)', $transport->getId()));
      $result['redirect'] = $this->generateUrl('logistics_stock_transport_edit', [ 'id' => $transport->getId() ]);

    } catch (\Exception $e) {

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }

  /**
   * 移動伝票削除
   * @param Request $request
   * @return JsonResponse
   */
  public function stockTransportDeleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      /** @var TbStockTransportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');

      /** @var TbStockTransport $transport */
      $id = $request->get('id');
      $transport = $repo->getOne($id);
      if (!$transport) {
        throw new \RuntimeException('データがありません。');
      }

      if (!in_array($transport->getStatus(), [
        TbStockTransportRepository::STATUS_NONE
      ])) {
        throw new \RuntimeException('未処理でない伝票は削除できません。');
      }

      $em = $this->getDoctrine()->getManager('main');
      foreach($transport->getDetails() as $detail) {
        $em->remove($detail);
      }

      $transport->setDetails([]);
      // choiceItems 移動中在庫数更新処理 （トリガも一括処理もふさわしく無さそうなので、移動伝票更新時に必ず行う。）
      $repo->updateChoiceItemTransportStocks();

      $em->remove($transport);

      $em->flush();

      $result['message'] = sprintf('ID: %d の移動伝票を削除しました。', $id);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * FBAマルチチャネル 移動伝票作成
   */
  public function stockTransportCreateFbaListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      // Jobをmainキューに登録
      $rescue = $this->getResque();
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command' => MainJob::COMMAND_KEY_CREATE_AMAZON_FBA_MULTI_CHANNEL_TRANSPORT_LIST
        , 'account' => $this->getLoginUser()->getId()
        , 'updateStock' => 0
        , 'updateFbaStock' => 1
      ];

      $rescue->enqueue($job); // リトライなし

      $result['message'] = sprintf('FBA移動伝票作成処理をキューに追加しました。');

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * FBA納品ラベル ファイルアップロード処理
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function stockTrasnportFbaLabelUploadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('stock transport fba label csv upload: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'csv' => []
    ];

    try {
      $account = $this->getLoginUser();

      /** @var UploadedFile $files */
      $files = $request->files->get('upload');
      $logger->info(print_r($files, true));

      if (!$files) {
        throw new \RuntimeException('アップロードされたファイルがありません。');
      }

      // ファイル変換、モール判定（ヘッダチェック）
      $logger->info('件数 : ' . count($files));

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');

      $fs = new Filesystem();
      $uploadDir = sprintf('%s/Amazon/FBALabel', $fileUtil->getWebCsvDir());

      // 一時テーブル取込
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      // $temporaryWord = ' TEMPORARY ';
      $temporaryWord = ' '; // FOR DEBUG
      $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_amazon_fba_label");
      $sql = <<<EOD
        CREATE {$temporaryWord} TABLE tmp_work_amazon_fba_label (
            `SKU` VARCHAR(255) NOT NULL DEFAULT '' 
          , `商品名` VARCHAR(255) NOT NULL DEFAULT '' 
          , `ASIN` VARCHAR(50) NOT NULL DEFAULT '' 
          , `FNSKU` VARCHAR(50) NOT NULL DEFAULT '' 
          , `製品コード` VARCHAR(255) NOT NULL DEFAULT '' 
          , `コンディション` VARCHAR(255) NOT NULL DEFAULT '' 
          , `梱包` VARCHAR(255) NOT NULL DEFAULT '' 
          , `梱包種類` VARCHAR(255) NOT NULL DEFAULT '' 
          , `ラベル貼付` VARCHAR(255) NOT NULL DEFAULT '' 
          , `同梱` VARCHAR(255) NOT NULL DEFAULT '' 
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8
        ;
EOD;
      $dbMain->exec($sql);

      foreach($files as $file) {
        $logger->info('uploaded : ' . print_r($file->getPathname(), true));

        // 先頭ブロック除去 （ヘッダ行が出るまでスキップ）
        $fp = fopen($file->getPathname(), 'rb');
        $i = 0;
        while ($line = fgets($fp)) {
          $logger->info($i++);
          if (preg_match('/^SKU\t商品名\tASIN\t/', $line)) {
            break;
          }
        }
        $header = $line;
        if (!$header) {
          throw new \RuntimeException('ファイルのデータがありませんでした。');
        }

//        $charset = mb_detect_encoding($header, ['SJIS-WIN', 'UTF-8', 'EUCJP-WIN']);
//        $logger->info(sprintf('%s : %s', $file->getClientOriginalName(), $charset));
//        if (!$charset) {
//          throw new \RuntimeException(sprintf('CSVファイルの文字コードが判定できませんでした。[%s]', $file->getClientOriginalName()));
//        }

        $headerRows = explode("\t", trim($header));
        $logger->dump($headerRows);
        $logger->dump(array_diff($headerRows, [
            'SKU'
          , '商品名'
          , 'ASIN'
          , 'FNSKU'
          , '製品コード'
          , 'コンディション'
          , '梱包'
          , '梱包種類'
          , 'ラベル貼付'
          , '同梱'
        ]));

        if (array_diff($headerRows, [
              'SKU'
            , '商品名'
            , 'ASIN'
            , 'FNSKU'
            , '製品コード'
            , 'コンディション'
            , '梱包'
            , '梱包種類'
            , 'ラベル貼付'
            , '同梱'
          ])) {
          throw new \RuntimeException('ヘッダ行の項目が違います。');
        }

        $newFilePath = tempnam($uploadDir, 'utf_');
        chmod($newFilePath, 0666);
        $fpNew = fopen($newFilePath, 'wb');
        while($line = fgets($fp)) {
          fwrite($fpNew, $line);
        }
        fclose($fpNew);
        fclose($fp);

        $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tmp_work_amazon_fba_label
            FIELDS TERMINATED BY '\t' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n'
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':importFilePath', $newFilePath);
        $stmt->execute();
      }

      // CSVデータ変数格納処理
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $csv = '';
      $sql = <<<EOD
        SELECT
            t.`SKU`
          , t.`商品名`
          , t.`FNSKU`
          , t.`コンディション`
          , t.`同梱`
        FROM tmp_work_amazon_fba_label t
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      $fileName = sprintf('fba_label_%s.csv', (new \DateTime())->format('YmdHis'));
      $logger->info('csv name: ' . $fileName . ' ( ' . $stmt->rowCount() . ' ) ');

      if ($stmt->rowCount()) {
        // ヘッダ
        $headers = [
            'SKU' => 'SKU'
          , '商品名' => '商品名'
          , 'FNSKU' => 'FNSKU'
          , 'コンディション' => 'コンディション'
          , '商品名短縮' => '商品名短縮'
        ];

        $eol = "\r\n";

        // ヘッダ
        $header = mb_convert_encoding($stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ","), 'SJIS-WIN', 'UTF-8') . $eol;

        $csv .= $header;
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $productNameParts = explode(" ", $row['商品名']);
          $tailParts = array_splice($productNameParts, -2);
          $head = implode(" ", $productNameParts);
          $tail = implode(" ", $tailParts);

          $maxLength = 30; // なんとなく
          $remain = $maxLength - (mb_strlen($tail, 'UTF-8') - 4);
          if ($remain < 0) {
            $remain = 0;
          }
          $head = mb_substr($head, 0, $remain, 'UTF-8');
          $row['商品名短縮'] = $head . ' ' . $tail;

          for ($i = 0; $i < $row['同梱']; $i++) {
            $line = mb_convert_encoding($stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ","), 'SJIS-WIN', 'UTF-8') . $eol;
            $csv .= $line;
          }
        }

        $result['csv'] = [
            'name' => $fileName
          , 'data' => base64_encode($csv)
        ];
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['error'] = $e->getMessage();
    }

    // $logger->info(print_r($result, true));
    return new JsonResponse($result);
  }

  /**
   * SHOPLISTスピード便移動伝票作成
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function stockTrasnportCreateShoplistSpeedBinListAction(Request $request){
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok',
      'message' => '',
    ];
    try {
      $departureDate = $_POST['departureDate'];
      if ($departureDate) {
        $departureDate = (new \DateTime($departureDate))->format('Ymd');
      }
      $arrivalDate = $_POST['arrivalDate'];
      if ($arrivalDate) {
        $arrivalDate = (new \DateTime($arrivalDate))->format('Ymd');
      }
      $shippingMethod = $_POST['shippingMethod'];
      $transportNumber = $_POST['transportNumber'];
      $uploadedTmpFileName = $_FILES['reservefile']['tmp_name'];
      $uploadedFileName = $_FILES['reservefile']['name'];

      // ファイルチェック
      if (!(is_uploaded_file($uploadedTmpFileName))) {
        throw new \RuntimeException('ファイルが選択されていません。');
      }
      if (pathinfo($uploadedFileName, PATHINFO_EXTENSION) != 'csv') {
        throw new \RuntimeException('CSVファイルを選択してください。');
      }

      // Commandに引き渡すため実ファイル
      /** @var FileUtil $fileUtil */
      $fileUtil = $this->container->get('misc.util.file');
      $currentDatetime = (new \DateTime())->format('YmdHis');
      $uploadDirPath = sprintf('%s%s', $fileUtil->getDataDir(), self::TRANSPORT_DATA_DIR_PATH);
      $fs = new Filesystem();
      if (!$fs->exists($uploadDirPath)) {
        $fs->mkdir($uploadDirPath, 0755);
      }
      // ${datadir}/transport/ファイル名_yyyymmddhhmmss.csv
      $uploadFilePath = sprintf('%s/%s%s%s%s', $uploadDirPath, basename($uploadedFileName,'csv'), '_', $currentDatetime, '.csv');
      if (!(move_uploaded_file($uploadedTmpFileName, $uploadFilePath))) {
        throw new \RuntimeException('ファイルをアップロードできませんでした。');
      }
      
      // ヘッダチェック
      $fp = fopen($uploadFilePath, 'rb'); // アップロードファイルを読み込む
      $headerLine = fgetcsv($fp); // ヘッダ行のみ読み込む
      $headerLineUTF8 = array();
      foreach ($headerLine as $data) {
        $headerLineUTF8[] =  mb_convert_encoding($data, 'UTF-8', 'SJIS-WIN');
      }
      if ($headerLineUTF8 != array_values(ShoplistSpeedbinService::$CSV_FIELDS_FIXED)) {
        throw new \RuntimeException('CSVファイルの書式が違います。SHOPLISTスピード便確定ファイルをアップロードしてください。');
      }

      // Jobをmainキューに登録
      $rescue = $this->getResque();
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command' => MainJob::COMMAND_KEY_CREATE_TRANSPORT_LIST_SHOPLIST_SPEED_BIN
        , 'account' => $this->getLoginUser()->getId()
        , 'departure-date' => $departureDate
        , 'arrival-date' => $arrivalDate
        , 'shipping-method' => $shippingMethod
        , 'transport-number' => $transportNumber
        , 'upload-filepath' => $uploadFilePath
      ];

      $rescue->enqueue($job); // リトライなし

      $result['message'] = sprintf('SHOPLISTスピード便移動伝票作成をキューに追加しました。');

    } catch (\Exception $e) {
      $logger->error('SHOPLISTスピード便移動伝票作成でエラー発生: ' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 南京終用 移動伝票作成
   * @throws \Doctrine\DBAL\DBALException
   */
  public function stockTransportCreateMainWarehouseListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $account = $this->getLoginUser();

      $limit = 20; // ざっくり。1伝票20商品までとする。
      $minimumNum = 1;

        /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');

      $targetWarehouseId = TbWarehouseRepository::DEFAULT_WAREHOUSE_ID; // 南京終
      $targetWarehouse = $repoWarehouse->find($targetWarehouseId);
      if (!$targetWarehouse) {
        throw new \RuntimeException('no warehouse');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      $temporaryWord = ' TEMPORARY ';
      // $temporaryWord = ''; // FOR DEBUG

      $today = new \DateTime();
      $borderDate = (new \DateTime())->setTime(0, 0, 0)->modify('-30 day');

      // 一時テーブル：移動対象商品抽出
      $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_stock_transport_main_warehouse_list_required");
      $sql = <<<EOD
        CREATE {$temporaryWord} TABLE tmp_work_stock_transport_main_warehouse_list_required (
            ne_syohin_syohin_code VARCHAR(50) NOT NULL PRIMARY KEY 
          , required_num INTEGER NOT NULL DEFAULT 0 
          , exists_num INTEGER NOT NULL DEFAULT 0
          , transporting INTEGER NOT NULL DEFAULT 0
          , shortage INTEGER AS (required_num - exists_num - transporting) STORED
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8
EOD;
      $dbMain->exec($sql);

      $sql = <<<EOD
        INSERT INTO tmp_work_stock_transport_main_warehouse_list_required (
            ne_syohin_syohin_code
          , required_num
          , exists_num
          , transporting
        )
        SELECT 
            R.ne_syohin_syohin_code
          , R.required_num
          , COALESCE(E.stock, 0) AS exists_num
          , COALESCE(T.transporting, 0) AS transporting 
        FROM (
          SELECT 
              a.`商品コード（伝票）` AS ne_syohin_syohin_code
            , SUM(a.`受注数`) + :minimumNum AS required_num
          FROM tb_sales_detail_analyze a
          WHERE a.`受注日` >= :borderDate
            AND a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
          GROUP BY a.`商品コード（伝票）`
        ) AS R 
        LEFT JOIN (
          SELECT 
              v.ne_syohin_syohin_code
            , SUM(v.stock_remain) AS stock
          FROM v_product_stock_picking_assign v
          INNER JOIN tb_warehouse w ON v.warehouse_id = w.id
          WHERE w.id = :targetWarehouseId
          GROUP BY v.ne_syohin_syohin_code
        ) AS E ON R.ne_syohin_syohin_code = E.ne_syohin_syohin_code
        LEFT JOIN (
          SELECT 
               d.ne_syohin_syohin_code
             , SUM(
                 CASE 
                   WHEN t.status = :transportStatusNone THEN d.amount
                   ELSE d.picked 
                 END
               ) AS transporting  
          FROM tb_stock_transport_detail d
          INNER JOIN tb_stock_transport t ON d.transport_id = t.id
          WHERE t.`status` IN (
                    :transportStatusNone
                  , :transportStatusReady
                  , :transportStatusShipping
                  , :transportStatusArrived
                ) 
            AND t.destination_warehouse_id = :targetWarehouseId
          GROUP BY d.ne_syohin_syohin_code 
        ) T ON R.ne_syohin_syohin_code = T.ne_syohin_syohin_code
        WHERE R.required_num - (COALESCE(E.stock, 0) + COALESCE(T.transporting, 0)) > 0
        ;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':minimumNum', $minimumNum, \PDO::PARAM_INT);
      $stmt->bindValue(':borderDate', $borderDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':targetWarehouseId', $targetWarehouseId, \PDO::PARAM_INT);

      $stmt->bindValue(':transportStatusNone', TbStockTransportRepository::STATUS_NONE, \PDO::PARAM_INT);
      $stmt->bindValue(':transportStatusReady', TbStockTransportRepository::STATUS_READY, \PDO::PARAM_INT);
      $stmt->bindValue(':transportStatusShipping', TbStockTransportRepository::STATUS_SHIPPING, \PDO::PARAM_INT);
      $stmt->bindValue(':transportStatusArrived', TbStockTransportRepository::STATUS_ARRIVED, \PDO::PARAM_INT);

      $stmt->execute();


      // 一時テーブル：対象倉庫別 移動数量
      $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_stock_transport_main_warehouse_list_result");
      $sql = <<<EOD
        CREATE {$temporaryWord} TABLE tmp_work_stock_transport_main_warehouse_list_result (
            warehouse_id INTEGER NOT NULL
          , ne_syohin_syohin_code VARCHAR(50) NOT NULL 
          , move_num INTEGER NOT NULL DEFAULT 0 
          , PRIMARY KEY (warehouse_id, ne_syohin_syohin_code)
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
      $dbMain->exec($sql);

      // 移動対象倉庫取得
      $warehouses = $repoWarehouse->getTransportFromWarehouses($targetWarehouseId);

      foreach($warehouses as $warehouse) {
        $sql = <<<EOD
          INSERT INTO tmp_work_stock_transport_main_warehouse_list_result (
              warehouse_id
            , ne_syohin_syohin_code
            , move_num
          )
          SELECT 
              /*
              req.ne_syohin_syohin_code
            , req.required_num
            , req.exists_num
            , req.shortage  
              */
              S.warehouse_id  
            , S.ne_syohin_syohin_code
            /*
            , S.stock
            , COALESCE(M.move_num, 0) AS move_num
            , req.shortage - COALESCE(M.move_num, 0) AS current_shortage
            */
            , CASE 
                WHEN req.shortage >= S.stock THEN S.stock
                ELSE req.shortage 
              END AS current_move_num  
          FROM tmp_work_stock_transport_main_warehouse_list_required req
          INNER JOIN (
            SELECT 
                 w.id AS warehouse_id 
               , v.ne_syohin_syohin_code
               , SUM(v.stock_remain) AS stock
            FROM v_product_stock_picking_assign v
            INNER JOIN tb_warehouse w ON v.warehouse_id = w.id
            WHERE w.id = :warehouseId
            GROUP BY v.ne_syohin_syohin_code
          ) S ON req.ne_syohin_syohin_code = S.ne_syohin_syohin_code
          WHERE S.stock > 0
            AND req.shortage > 0
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        // 結果を移動伝票として作成する。
        $sql = <<<EOD
          SELECT 
            *
          FROM tmp_work_stock_transport_main_warehouse_list_result res
          WHERE res.warehouse_id = :warehouseId
          ORDER BY res.ne_syohin_syohin_code
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
          $transport = null;
          $i = 1;
          while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!isset($transport)) {
              $transport = new TbStockTransport();
              $transport->setAccount($account->getClientName());
              $transport->setDate($today);
              $transport->setTransportCode(TbStockTransportRepository::TRANSPORT_CODE_WAREHOUSE);
              $transport->setDepartureWarehouseId($warehouse->getId());
              $transport->setDeparture($warehouse->getName());
              $transport->setDestinationWarehouseId($targetWarehouse->getId());
              $transport->setDestination($targetWarehouse->getName());

              $em->persist($transport);
              // IDが欲しいのでflush
              $em->flush();
            }

            $detail = new TbStockTransportDetail();
            $detail->setTransportId($transport->getId());
            $detail->setNeSyohinSyohinCode($row['ne_syohin_syohin_code']);
            $detail->setAmount($row['move_num']);
            $em->persist($detail);

            if ($i++ >= $limit) {
              $i = 0;
              unset($transport);
            }
          }

          $em->flush();
        }

        // 移動伝票作成分を更新し、それにより必要数も更新
        $sql = <<<EOD
          UPDATE tmp_work_stock_transport_main_warehouse_list_required r
          INNER JOIN (
            SELECT 
                 d.ne_syohin_syohin_code
               , SUM(
                   CASE 
                     WHEN t.status = :transportStatusNone THEN d.amount
                     ELSE d.picked 
                   END
                 ) AS transporting  
            FROM tb_stock_transport_detail d
            INNER JOIN tb_stock_transport t ON d.transport_id = t.id
            WHERE t.`status` IN (
                      :transportStatusNone
                    , :transportStatusReady
                    , :transportStatusShipping
                    , :transportStatusArrived
                  ) 
              AND t.destination_warehouse_id = :targetWarehouseId
            GROUP BY d.ne_syohin_syohin_code 
          ) T ON r.ne_syohin_syohin_code = T.ne_syohin_syohin_code
          SET r.transporting = T.transporting
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':targetWarehouseId', $targetWarehouseId, \PDO::PARAM_INT);
        $stmt->bindValue(':transportStatusNone', TbStockTransportRepository::STATUS_NONE, \PDO::PARAM_INT);
        $stmt->bindValue(':transportStatusReady', TbStockTransportRepository::STATUS_READY, \PDO::PARAM_INT);
        $stmt->bindValue(':transportStatusShipping', TbStockTransportRepository::STATUS_SHIPPING, \PDO::PARAM_INT);
        $stmt->bindValue(':transportStatusArrived', TbStockTransportRepository::STATUS_ARRIVED, \PDO::PARAM_INT);
        $stmt->execute();
      }

      // choiceItems 移動中在庫数更新処理 （トリガも一括処理もふさわしく無さそうなので、移動伝票更新時に必ず行う。）
      /** @var TbStockTransportRepository $repoTransport */
      $repoTransport = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');
      $repoTransport->updateChoiceItemTransportStocks();

      $result['message'] = sprintf('南京終用移動伝票作成処理を完了しました。');
      $result['redirect'] = $this->generateUrl('logistics_stock_transport_list');

      $this->setFlash('success', $result['message']);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 移動伝票 ピッキングリスト作成
   * @param Request $request
   * @return JsonResponse
   */
  public function stockTransportCreatePickingListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      /** @var TbStockTransportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');

      /** @var TbStockTransport $transport */
      $id = $request->get('id');
      $transport = $repo->getOne($id);
      if (!$transport) {
        throw new \RuntimeException('データがありません。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      // 出発倉庫の指定がなければひとまず京終倉庫固定
      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $warehouseId = $transport->getDepartureWarehouseId();
      $warehouse = $repoWarehouse->find($warehouseId ? $warehouseId : TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);
      if (!$warehouse) {
        throw new \RuntimeException('出発倉庫が取得できませんでした。');
      }

      $date = (new \DateTime())->setTime(0, 0, 0);

      // もしすでにピッキングリストがあれば削除
      if ($transport->getPickingListDate() && $transport->getPickingListNumber()) {
        $sql = <<<EOD
        DELETE l
        FROM tb_warehouse_stock_move_picking_list l
        WHERE l.warehouse_id = :warehouseId
          AND l.date = :date
          AND l.number = :number
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $transport->getDepartureWarehouseId(), \PDO::PARAM_INT);
        $stmt->bindValue(':date', $transport->getPickingListDate()->format('Y-m-d'), \PDO::PARAM_STR);
        $stmt->bindValue(':number', $transport->getPickingListNumber(), \PDO::PARAM_INT);
        $stmt->execute();
      }

      // ピッキングリスト作成
      // ピッキングリスト番号取得
      $sql = <<<EOD
          SELECT
            COALESCE(MAX(number), 0) + 1
          FROM tb_warehouse_stock_move_picking_list
          WHERE `warehouse_id` = :warehouseId
            AND `date` = :date
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':date', $date->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->execute();
      $number = $stmt->fetchColumn(0);

      $sql = <<<EOD
          INSERT INTO tb_warehouse_stock_move_picking_list (
              `warehouse_id`
            , `date`
            , `number`
            , `ne_syohin_syohin_code`
            , `move_num`
            , `pict_directory`
            , `pict_filename`
            , `type`
          )
          SELECT
              :warehouseId
            , :date
            , :number AS number
            , d.ne_syohin_syohin_code
            , CASE
                WHEN (d.amount - d.picked) > (v.stock_remain + v.transport_assign) THEN (v.stock_remain + v.transport_assign)
                ELSE d.amount - d.picked
              END AS move_num
            , COALESCE(i.`directory`, '') AS pict_directory
            , COALESCE(i.`filename`, '') AS pict_filename
            , :type AS type
          FROM tb_stock_transport_detail d
          INNER JOIN tb_stock_transport t ON d.transport_id = t.id
          INNER JOIN v_product_stock_picking_assign v ON v.warehouse_id = :warehouseId
                                                     AND d.ne_syohin_syohin_code = v.ne_syohin_syohin_code
          INNER JOIN tb_productchoiceitems pci ON d.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          LEFT JOIN product_images i ON pci.daihyo_syohin_code = i.daihyo_syohin_code AND i.code = 'p001'

          WHERE d.transport_id = :id
            AND v.stock_remain + v.transport_assign > 0
            AND d.amount - d.picked > 0
          ORDER BY d.ne_syohin_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':date', $date->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
      $stmt->bindValue(':type', TbWarehouseStockMovePickingListRepository::TYPE_FBA_SEND, \PDO::PARAM_STR);
      $stmt->execute();

      // 作成確認。（在庫がなければ作成されない）
      $sql = <<<EOD
        SELECT 
          COUNT(*) 
        FROM tb_warehouse_stock_move_picking_list pl
        WHERE pl.warehouse_id = :warehouseId
          AND pl.date = :date
          AND pl.number = :number
          AND pl.type = :type
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':date', $date->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':type', TbWarehouseStockMovePickingListRepository::TYPE_FBA_SEND, \PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->fetchColumn(0) > 0) {
        $transport->setPickingListDate($date);
        $transport->setPickingListNumber($number);

        $result['message'] = sprintf('在庫移動ピッキング[ %s : No.%d ] を作成しました。', $warehouse->getName(), $number);
      } else {
        $transport->setPickingListDate(null);
        $transport->setPickingListNumber(0);

        $result['message'] = sprintf('倉庫に在庫が無いため、在庫移動ピッキングは作成されませんでした。');
      }

      $em->flush();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 移動伝票 完了処理 ロケーション作成（倉庫移動、FBA引き上げ）/ 何もしない（FBA納品）
   * @param $id
   * @param Request $request
   * @return JsonResponse
   */
  public function stockTransportCompleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      /** @var TbStockTransportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');
      $id = $request->get('id');
      $transport = $repo->getOne($id);
      if (!$transport) {
        throw new \RuntimeException('データがありません。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      $date = (new \DateTime())->setTime(0, 0, 0);

      $logger->info(sprintf('移動伝票完了処理 : %d : %s', $transport->getId(), $transport->getTransportCode()));

      // ロケーション作成
      if (in_array($transport->getTransportCode(), [
          TbStockTransportRepository::TRANSPORT_CODE_WAREHOUSE
        , TbStockTransportRepository::TRANSPORT_CODE_FBA_RETURN
      ])) {

        // 出発倉庫の指定がなければひとまず京終倉庫固定
        /** @var TbWarehouseRepository $repoWarehouse */
        $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
        $warehouseId = $transport->getDestinationWarehouseId();
        /** @var TbWarehouse $warehouse */
        $warehouse = $repoWarehouse->find($warehouseId ? $warehouseId : TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);
        if (!$warehouse) {
          throw new \RuntimeException('目的倉庫が取得できませんでした。');
        }

        /** @var TbLocationRepository $repoLocation */
        $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

        // 更新処理
        $dbMain->beginTransaction();

        // （履歴用）アクションキー 作成＆セット
        $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

        $locationCode = $repoLocation->fixLocationCode($request->get('location_code'));
        if ($locationCode) {
          if (!$repoLocation->isValidLocationCode($locationCode)) {
            throw new \RuntimeException('ロケーションコードが不正です。 [' . $locationCode . ']');
          }
          $existsLocation = $repoLocation->getByLocationCode($warehouse->getId(), $locationCode);
          if ($existsLocation) {
            throw new \RuntimeException('存在するロケーションは指定できません。 [' . $locationCode . ']');
          }

          $location = $repoLocation->createNewLocation($warehouse->getId(), $locationCode);

        } else {
          $location = $repoLocation->createAutoLocation('transport', 'NE_T_', $warehouse);
        }

        if (!$location) {
          throw new \RuntimeException('ロケーションの作成に失敗しました。 [' . $locationCode . ']');
        }

        /** @var TbProductchoiceitemsRepository $repoChoice */
        $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

        foreach($transport->getDetails() as $detail) {
          // 0件は追加しない
          if ($detail->getPicked() === 0) {
            continue;
          }

          $choice = null;
          /** @var TbProductchoiceitems $choice */
          $choice = $repoChoice->find($detail->getNeSyohinSyohinCode());

          $productLocation = new TbProductLocation();
          $productLocation->setChoiceItem($choice);
          $productLocation->setLocation($location);
          $productLocation->setNeSyohinSyohinCode($choice->getNeSyohinSyohinCode()); // これがいる理由が今ひとつよくわからない。↑のsetChoiceItem()はなんなんだ
          $productLocation->setLocationId($location->getId()); // 同上

          $productLocation->setStock($detail->getPicked());
          $productLocation->setPosition($choice->getMaxLocationPosition($warehouse) + 1);

          $em->persist($productLocation);
        }

        $transport->setStatus(TbStockTransportRepository::STATUS_DONE);
        if (!$transport->getArrivalDate()) {
          $transport->setArrivalDate($date);
        }

        $em->flush();

        // ロケーション変更履歴 保存
        /** @var \Doctrine\DBAL\Connection $dbLog */
        $dbLog = $this->getDoctrine()->getConnection('log');
        $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_STOCK_TRANSPORT_COMPLETE, $this->getLoginUser()->getUsername(), $actionKey);

        $dbMain->commit();

        $result['message'] = sprintf('ロケーション[ %s : %s ] を作成し、移動伝票 %d のステータスを完了にしました。', $warehouse->getName(), $location->getLocationCode(), $transport->getId());

      // その他はただステータスの変更のみ
      } else {
        $transport->setStatus(TbStockTransportRepository::STATUS_DONE);
        if (!$transport->getArrivalDate()) {
          $transport->setArrivalDate($date);
        }

        $em->flush();

        $result['message'] = sprintf('移動伝票 %d のステータスを完了にしました。', $transport->getId());
      }

      $this->setFlash('success', $result['message']);
      $result['redirect'] = $this->generateUrl('logistics_stock_transport_edit', [ 'id' => $transport->getId() ]);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 移動伝票 FBA納品プラン ダウンロード処理
   * @param $id
   * @return Response
   */
  public function stockTransportDownloadFbaPlanAction($id)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {

      /** @var TbStockTransportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');
      $transport = $repo->getOne($id);
      if (!$transport) {
        throw new \RuntimeException('データがありません。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $fileName = sprintf('FBA納品プラン_%d.txt', $transport->getId());

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($transport)
        {
          $eol = "\r\n";
          $exportFile = new \SplFileObject('php://output', 'w');

          // ヘッダ
          $headers = [
              'PlanName' =>	sprintf('plan-%d', $transport->getId())
            , 'AddressName' => 'ヴォーグ'
            , 'AddressFieldOne' =>	'南京終町778-2'
            , 'AddressFieldTwo' =>	''
            , 'AddressCity' =>	'奈良市'
            , 'AddressCountryCode' =>	'JP'
            , 'AddressStateOrRegion' =>	'奈良県'
            , 'AddressPostalCode' =>	'6308141'
          ];

          // ヘッダ
          $header = "";
          foreach($headers as $k => $v) {
            $line = sprintf("%s\t%s%s", $k, $v, $eol);
            $header .= mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
          }
          $exportFile->fwrite($header);

          $body = "";
          $body .= $eol;
          $body .= "MerchantSKU\tQuantity" . $eol;
          $exportFile->fwrite($body);
          flush();

          foreach($transport->getDetails() as $detail) {
            if ($detail->getPicked() <= 0) {
              continue;
            }

            $line = sprintf("%s\t%s%s", $detail->getNeSyohinSyohinCode(), $detail->getPicked(), $eol);
            $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
            $exportFile->fwrite($line);

            flush();
          }

        }
      );

      $logger->info('csv name: ' . $fileName);

      $response->headers->set('Content-type', 'plain/text');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));

      $logger->info('csv output: response done!');

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $response = new Response($e->getMessage(), 500);
    }
    return $response;
  }

  /**
   * 未引当 発送伝票番号リスト画面
   */
  public function unallocatedShippingSlipNumberListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    $listData = $repo->fetchUnallocatedShippingList();

    // 画面ではG.W（梱包済み重量）で表示するため、N.W（実重量）に +200g する
    foreach ($listData as $key => $data) {
        $listData[$key]['checkListGw'] = $data['checkListNw'] > 0 ? number_format((float) $data['checkListNw'] + 0.2, 2, '.', '') : $data['checkListNw'];
    }

    return $this->render('AppBundle:Logistics:unallocated-shipping-slip-number.html.twig', [
      'account' => $account,
      'dataJson' => json_encode($listData)
    ]);
  }

  /**
   * 発送伝票画面リスト画面 連絡事項更新
   */
  public function updateUnallocatedListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    try{
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
      $tbIndividualorderhistories = $repo->findBy(['shipping_number' => $request->request->get('shippingNumber')]);
      $em = $this->getDoctrine()->getManager('main');
      foreach($tbIndividualorderhistories as $tbIndividualorderhistory) {
        $escapeComment = htmlspecialchars($request->request->get('checklistComment'), ENT_QUOTES, "UTF-8");
        $tbIndividualorderhistory->setChecklistComment($escapeComment);

        // 画面側からはG.W（梱包済み重量）で渡ってくるので -200g して実重量でDBに格納する
        $tbIndividualorderhistory->setChecklistNw($request->request->get('checklistGw') ? ($request->request->get('checklistGw') - 0.2) : null);
        $tbIndividualorderhistory->setChecklistMeas($request->request->get('checklistMeas') ?: null);
        $tbIndividualorderhistory->setShippingOperationNumber($request->request->get('shippingOperationNumber') ?: null);
        $em->persist($tbIndividualorderhistory);
      }
      $em->flush();
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 未引当CSVダウンロード（Ajax）
   * 
   * @param Request $request
   * @return StreamedResponse
   */
  public function downloadUnallocatedCsvAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $shippingSlipNums = explode(',', $request->get('ssn'));

    /** @var TbIndividualorderhistoryRepository $iohRepo */
    $iohRepo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');
    $ioHistories = $iohRepo->fetchUnallocatedShippingList($shippingSlipNums);

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');

    $headers = [
      '発送伝票番号' => '発送伝票番号',
      '連絡事項' => '連絡事項',
      'G.W' => 'G.W',
      'MEAS' => 'MEAS',
      '出庫番号' => '出庫番号',
      '出荷待ち日' => '出荷待ち日',
      '出荷日' => '出荷日',
      '依頼先' => '依頼先',
    ];

    $response = new StreamedResponse();
    try {
      $response->setCallback(
        function () use ($ioHistories, $stringUtil, $headers)
        {
          $eol = "\r\n";
          $exportFile = new \SplFileObject('php://output', 'w');
          
          // CSVヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ',') . $eol;
          $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
          $exportFile->fwrite($header);
          
          foreach ($ioHistories as $history) {
            $row = [];
            $row['発送伝票番号'] = $history['shippingSlipNumber'];
            $row['連絡事項'] = $history['checkListComment'];
            $nw = $history['checkListNw'];
            if (0 < $nw) {
              $nw = number_format((float) $nw + 0.2, 2, '.', '');
            }
            $row['G.W'] = $nw;
            $row['MEAS'] = $history['checkListMeas'];
            $row['出庫番号'] = $history['shippingOperationNumber'];
            $row['出荷待ち日'] = $history['waitDate'];
            $row['出荷日'] = $history['shipDate'];
            $row['依頼先'] = $history['requesterName'];
            
            $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ',') . $eol;
            $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
            
            $exportFile->fwrite($line);
            
            flush();
          }
        }
      );

      $fileName = sprintf('unllocated_%s.csv', (new \DateTime())->format('YmdHis'));
      $response->headers->set('Content-Type', "application/octet-stream;");
      $response->headers->set('Content-Disposition', "attachment; filename=${fileName}");
      $response->send();

    } catch (\Exception $e) {
      $this->addFlash('danger', "CSVダウンロード時にエラーが発生しました。\n\r" . $e->getMessage() . $e->getTraceAsString());
      return $this->redirectToRoute('logistics_unallocated_shipping_slip_number_list');
    }
    return $response;
  }

  /**
   * 楽天Express用配送情報CSV変換 一覧
   * @param Request $request
   * @return Response
   */
  public function rakutenExpressConvertCsvListAction(Request $request)
  {
    /** @var TbRakutenExpressConversionRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenExpressConversion');
    // 出力ディレクトリ作成
    $repo->rakutenExpresstMakedir();

    // 不必要なファイルやテーブルデータを削除する
    $repo->refresh();

    $today = new \DateTime();
    $from = preg_match("/\A\d{4}-\d{2}-\d{2}\z/",$request->get('from')) ? new \DateTime($request->get('from')) : $today;
    $to = preg_match("/\A\d{4}-\d{2}-\d{2}\z/",$request->get('to')) ? new \DateTime($request->get('to')) : $today;
    $listData = $repo->fetchListByDate($from, $to);
    return $this->render('AppBundle:Logistics:rakuten-express-csv-list.html.twig', [
      'account' => $this->getLoginUser()
      , 'listData' => json_encode($listData)
      , 'baseUrl' => json_encode($request->getBaseUrl())
    ]);
  }

  /**
   * 楽天Express用配送情報CSV変換
   * @return JsonResponse $result
   */
  public function rakutenExpressConvertCsvAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'data' => []
    ];
    try{
      $expectedShippingDate = $request->get('expectedShippingDate');
      if ($expectedShippingDate) {
        $expectedShippingDate = (new \DateTime($expectedShippingDate))->format('Ymd');
      }
      $uploadedTmpFileName = $_FILES['addrfile']['tmp_name'];
      $uploadedFileName = $_FILES['addrfile']['name'];

      /** @var TbRakutenExpressConversionRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenExpressConversion');
      $convertResult = $repo->convertRakutenExpressCsv($uploadedTmpFileName, $uploadedFileName, $expectedShippingDate);
      $repo->insertConvertedFileData($convertResult);
      if (!empty($convertResult['変換スキップ住所一覧'])) {
        // 変換できなかった住所が1件以上存在する場合
        $result['status'] = 'warn';
        $result['skipedaddresses'] =  $convertResult['変換スキップ住所一覧']; // 伝票番号=> ['ご依頼主'=>ご依頼主住所, 'お届け先' =>お届け先住所]
      }
      $result['data'] = [$convertResult];
    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 楽天Express変換CSVダウンロード
   * @param Request $request
   * @return Response $response
   */
  public function rakutenExpressDownloadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      /** @var TbRakutenExpressConversionRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenExpressConversion');
      $date = $request->get('date');
      $baseFile = $request->get('base');

      $filePath = $repo->findConversionCsvPath($date, $baseFile);
      $repo->updateDownloadCount($date, $baseFile);
      $response = FileUtil::downloadFile($filePath);
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $response = new Response($e->getMessage(), 500);
    }
    return $response;
  }

  /**
   * 楽天Express変換CSVダウンロード回数データ取得
   * @param Request $request
   * @return Response $response
   */
  public function getRakutenExpressdDownloadCountAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var TbRakutenExpressConversionRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenExpressConversion');
      $date = $request->get('date');
      $baseFile = $request->get('base');
      $downloadCount = $repo->getDownloadCount($date, $baseFile);
      $result['downloadCount'] = $downloadCount;
    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 出荷実績レビュー 一覧
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Exception
   */
  public function warehouseResultHistoryListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $account = $this->getLoginUser();

    $jsonWarehouses = [];

    /** @var TbWarehouseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repo->getResultHistoryDisplayWarehouses();
    foreach($warehouses as $warehouse) {
      $jsonWarehouses[] = $warehouse->toScalarArray();
    }

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    $coefficientShoplist = $commonUtil->getSettingValue(TbSetting::KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_SHOPLIST);
    $coefficientRslSagawaYamato = $commonUtil->getSettingValue(TbSetting::KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_RSL_SAGAWA_YAMATO);

    // 画面表示
    return $this->render('AppBundle:Logistics:warehouse-result-history-list.html.twig', [
      'account' => $account
      , 'warehouses' => $warehouses
      , 'jsonWarehouses' => json_encode($jsonWarehouses)
      , 'coefficientShoplist' => $coefficientShoplist
      , 'coefficientRslSagawaYamato' => $coefficientRslSagawaYamato
    ]);
  }

  /**
   * 出荷実績レビュー データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findWarehouseResultHistoryListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      $commonUtil->updateSettingValue(TbSetting::KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_SHOPLIST, $request->get('coefficientShoplist'));
      $commonUtil->updateSettingValue(TbSetting::KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_RSL_SAGAWA_YAMATO, $request->get('coefficientRslSagawaYamato'));

      $firstDate = new \Datetime(date("Y-m-01"));
      $fromDate = $firstDate->modify('-3 month');
      if (strlen($request->get('filterDateStart'))) {
        if (! strptime($request->get('filterDateStart'), '%Y-%m-%d')) {
          return new JsonResponse($result);
        }
        $fromDate = (new \DateTime($request->get('filterDateStart')))->setTime(0, 0, 0);
      }
      $toDate = new \Datetime('yesterday');
      if (strlen($request->get('filterDateEnd'))) {
        if (! strptime($request->get('filterDateEnd'), '%Y-%m-%d')) {
          return new JsonResponse($result);
        }
        $toDate = (new \DateTime($request->get('filterDateEnd')))->setTime(0, 0, 0);
      }

      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $sql = <<<EOD
        SELECT
          DISTINCT target_date
        FROM tb_warehouse_result_history
        WHERE target_date >= :fromDate
          AND target_date <= :toDate
        ORDER BY target_date DESC
EOD;
      $stmt = $dbLog->prepare($sql);
      $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d'));
      $stmt->bindValue(':toDate', $toDate->format('Y-m-d'));
      $stmt->execute();
      $targetDateList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      if (empty($targetDateList)) {
        return new JsonResponse($result);
      }

      foreach ($targetDateList as $targetDateData) {
        $targetDate = $targetDateData['target_date'];

        $sql = <<<EOD
          SELECT
            warehouse_id
            , picking_sum
            , warehouse_picking_sum
            , shipping_sum
            , shipping_sum_shoplist
            , shipping_sum_rsl
            , shipping_sum_sagawa
            , shipping_sum_yamato
            , operation_time_sum
          FROM tb_warehouse_result_history
          WHERE target_date = :targetDate
          ORDER BY target_date DESC
EOD;
        $stmt = $dbLog->prepare($sql);
        $stmt->bindValue(':targetDate', $targetDate);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $list[$targetDate] = ['targetDate' => $targetDate];

        foreach ($data as $value) {
          $warehouseKey = 'warehouse' . $value['warehouse_id'];

          $list[$targetDate][$warehouseKey] = [
              'warehouseId' => $value['warehouse_id']
              , 'pickingSum' => number_format($value['picking_sum'])
              , 'warehousePickingSum' => number_format($value['warehouse_picking_sum'])
              , 'shippingSum' => $value['shipping_sum']
              , 'shippingSumShoplist' => $value['shipping_sum_shoplist']
              , 'shippingSumRsl' => $value['shipping_sum_rsl']
              , 'shippingSumSagawa' => $value['shipping_sum_sagawa']
              , 'shippingSumYamato' => $value['shipping_sum_yamato']
              , 'operationTimeSum' => $value['operation_time_sum']
          ];
        }
      }
      $result['list'] = $list;

    } catch (\Exception $e) {
      $logger->error("出荷実績取得機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷実績レビュー データ一括更新処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function updateWarehouseResultHistoryListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    /** @var TbWarehouseResultHistoryRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseResultHistory', 'log');
    $emLog = $this->getDoctrine()->getManager('log');

    $account = $this->getLoginUser();

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $modifiedList = $request->get('modifiedList');

      // 更新処理
      $dbMain->beginTransaction();

      foreach ($modifiedList as $modified) {
        $targetDate = (new \DateTime($modified['targetDate']))->setTime(0, 0, 0);
        $data = $repo->findOneBy(['warehouseId' => $modified['warehouseId'], 'targetDate' => $targetDate->format('Y-m-d')]);

        if (empty($data)) {
          $data = new TbWarehouseResultHistory();
          $data->setWarehouseId($modified['warehouseId']);
          $data->setTargetDate($targetDate->format('Y-m-d'));
          $data->setPickingSum(0);
          $data->setWarehousePickingSum(0);
          $data->setShippingSum(0);
          $data->setShippingSumShoplist(0);
          $data->setShippingSumRsl(0);
          $emLog->persist($data);
        }
        $data->setOperationTimeSum($modified['operationTimeSum']);
        $data->setUpdateAccountId($account->getId());
      }
      $emLog->flush();

      $dbMain->commit();

    } catch (\Exception $e) {
      $logger->error("出荷実績更新機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }
}
