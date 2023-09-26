<?php

namespace AppBundle\Controller;

use BatchBundle\MallProcess\RealShopSmaregiMallProcess;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbRealShopInformationRepository;
use MiscBundle\Entity\Repository\TbRealShopPickingReportRepository;
use MiscBundle\Entity\Repository\TbRealShopProductStockRepository;
use MiscBundle\Entity\Repository\TbRealShopReturnReportRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\Tb1688Vendor;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbRealShopInformation;
use MiscBundle\Entity\TbRealShopPickingReport;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\TmpProductImages;
use MiscBundle\Form\TbMainproductsSimpleType;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringifyDateTime;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 実店舗関連
 * @package AppBundle\Controller
 */
class RealShopController extends BaseController
{
  /**
   * 実店舗管理 商品一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function productStockListAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:RealShop:product-stock-list.html.twig', [
        'account' => $account
    ]);
  }

  /**
   * 一覧画面 データ取得処理(Ajax)
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
      , 'tax_rate' => DbCommonUtil::CURRENT_TAX_RATE_PERCENT
    ];

    try {

      $page = $request->get('page', 1);
      $pageItemNum = $request->get('limit', 20);

      $conditions = $request->get('conditions', []);

      $orders = $request->get('orders', []);
      $fixedOrders = [];
      foreach($orders as $k => $v) {
        if ($v) {
          $fixedOrders[$k] = $v > 0 ? 'ASC' : 'DESC';
        }
      }

      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      // $logger->info(print_r($conditions, true));
      // $logger->info(print_r($fixedOrders, true));

      /** @var TbRealShopProductStockRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopProductStock');
      $pagination = $repo->findProductList($conditions, $fixedOrders, $page, $pageItemNum);

      $this->get('misc.util.batch_logger')->info('page : ' . $page);

      $imageUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

      /** @var Tb1688Vendor $vendor */
      foreach($pagination->getItems() as $product) {
        $product['image'] = TbMainproductsRepository::createImageUrl($product['directory'], $product['filename'], $imageUrl);
        $result['list'][] = $product;
      }

      $result['count'] = $pagination->getTotalItemCount();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 一覧画面 SKUデータ取得処理(Ajax)
   * @return JsonResponse
   * @internal param Request $request
   */
  public function findProductSkuListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      $daihyoSyohinCode = $request->get('daihyo_syohin_code');

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->get('misc.util.image');

      /** @var TbRealShopProductStockRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopProductStock');
      $result['list'] = [];
      $list = $repo->findProductSkuList($daihyoSyohinCode);
      foreach($list as $row) {
        $row['barcode'] = $stringUtil->convertNumToJan13($row['product_code']);
        $row['barcodeSVG'] = $imageUtil->getBarcodeSVG($row['barcode'], 'EAN13', false, 1.6, 20);
        $result['list'][] = $row;
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 一覧画面 SKU一覧 保存処理
   */
  public function updateProductSkuListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $item = $request->get('item');
      $list = $request->get('list');

      $newPrice = $request->get('new_price');

      /** @var TbRealShopProductStockRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopProductStock');
      $repo->updateProductSkuList($list);

      // 未登録商品であれば、スマレジ並びにスマレジ商品テーブルへ新規登録
      // 同時に、販売価格も保存
      /** @var TbRealShopInformationRepository $repo */
      $repoInfo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopInformation');
      $info = $repoInfo->find($item['daihyoSyohinCode']);
      if (!$info) {

        // スマレジ商品テーブルへ新規登録
        $info = new TbRealShopInformation();
        $info->setDaihyoSyohinCode($item['daihyoSyohinCode']);
        $info->setOriginalPrice(-1);
        $info->setBaikaTanka($newPrice ? $newPrice : $item['basePrice']);
        $em = $this->getDoctrine()->getManager('main');
        $em->persist($info);
        $em->flush();

        // スマレジへ商品登録 実装
        // ひとまず、店舗在庫あるいは在庫依頼数のあるskuのみ登録
        $registerSkuList = [];
        foreach($list as $row) {
          if ($row['stock'] || $row['order_num']) {
            $registerSkuList[] = $row['ne_syohin_syohin_code'];
          }
        }

        $logger->info('------- new products --------');
        $logger->info(print_r($registerSkuList, true));
        $logger->info('------- / new products --------');

        $doUpdate = $this->getParameter('smaregi_api_do_update');
        if (!$doUpdate) {
          $logger->info('※開発環境につき、スマレジへの更新処理はスキップ');

        } else if ($registerSkuList) {
          /** @var RealShopSmaregiMallProcess $process */
          $process = $this->get('batch.mall_process.smaregi');
          $process->registerProducts($registerSkuList);
        }
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 一覧画面 価格更新処理
   * @param Request $request
   * @return JsonResponse
   */
  public function updateProductPriceAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $daihyoSyohinCode = $request->get('daihyo_syohin_code');
      $price = $request->get('price');

      // 未登録商品であれば、スマレジ商品テーブルへ新規登録。
      // 同時に、スマレジの価格一括更新
      $em = $this->getDoctrine()->getManager('main');

      /** @var TbRealShopInformationRepository $repo */
      $repoInfo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopInformation');
      $info = $repoInfo->find($daihyoSyohinCode);
      if (!$info) {
        // スマレジ商品テーブルへ新規登録
        $info = new TbRealShopInformation();
        $info->setDaihyoSyohinCode($daihyoSyohinCode);
        $info->setOriginalPrice(-1);
        $em->persist($info);
      }

      $info->setBaikaTanka($price);
      $em->flush();

      /** @var TbRealShopProductStockRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopProductStock');
      $list = $repo->findProductSkuList($daihyoSyohinCode);

      // スマレジへ商品登録 実装
      // ひとまず、店舗在庫あるいは在庫依頼数のあるskuのみ登録
      $registerSkuList = [];
      foreach($list as $row) {
        if ($row['shop_stock'] || $row['order_num']) {
          $registerSkuList[] = $row['ne_syohin_syohin_code'];
        }
      }

      // $logger->info('------- new products --------');
      // $logger->info(print_r($registerSkuList, true));
      // $logger->info('------- / new products --------');

      $doUpdate = $this->getParameter('smaregi_api_do_update');
      if (!$doUpdate) {
        $logger->info('※開発環境につき、スマレジへの更新処理はスキップ');

      } else if ($registerSkuList) {
        /** @var RealShopSmaregiMallProcess $process */
        $process = $this->get('batch.mall_process.smaregi');
        $process->registerProducts($registerSkuList);
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 一覧画面 ラベル種別更新処理
   * @param Request $request
   * @return JsonResponse
   */
  public function updateProductLabelTypeAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $daihyoSyohinCode = $request->get('daihyo_syohin_code');
      $labelType = $request->get('label_type');

      // $logger->info(print_r($labelType, true));

      // 未登録商品であれば、スマレジ商品テーブルへ新規登録。
      $em = $this->getDoctrine()->getManager('main');

      /** @var TbRealShopInformationRepository $repo */
      $repoInfo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopInformation');
      $info = $repoInfo->find($daihyoSyohinCode);
      if (!$info) {
        // スマレジ商品テーブルへ新規登録
        $info = new TbRealShopInformation();
        $info->setDaihyoSyohinCode($daihyoSyohinCode);
        $info->setOriginalPrice(-1);
        $em->persist($info);
      }

      $info->setLabelType($labelType);
      $em->flush();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }



  /**
   * 配送リスト
   * @param int $page
   * @return Response
   */
  public function pickingReportListAction($page)
  {
    $account = $this->getLoginUser();

    /** @var TbRealShopPickingReportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingReport');
    $pagination = $repo->getListData([], [], $page);

    // 画面表示
    return $this->render('AppBundle:RealShop:picking-report-list.html.twig', [
        'account' => $account
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'labelTypes' => [
          'tag'     => [ 'name' => '下げ札', 'icon' => 'fa-tag' ]
        , 'sticker' => [ 'name' => 'シール', 'icon' => 'fa-sticky-note-o' ]
      ]
    ]);
  }

  /**
   * 配送リスト 詳細
   * @param string $date
   * @param int $number
   * @return Response
   */
  public function pickingReportDetailAction($date, $number)
  {
    $account = $this->getLoginUser();

    /** @var TbRealShopPickingReportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingReport');
    $pickingReport = $repo->getReportDetail($date, $number);

    // 画面表示
    return $this->render('AppBundle:RealShop:picking-report-detail.html.twig', [
        'account' => $account
      , 'pickingDate' => $date
      , 'number' => $number
      , 'pickingReportJson' => json_encode($pickingReport)
    ]);
  }

  /**
   * ピッキング数更新処理
   * @param Request $request
   * @param string $date
   * @param integer $number
   * @return JsonResponse
   */
  public function pickingReportUpdateAction(Request $request, $date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $neSyohinSyohinCode = $request->get('ne_syohin_syohin_code');

      /** @var TbRealShopPickingReportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingReport');
      /** @var TbRealShopPickingReport $report */
      $report = $repo->find(['picking_date' => new StringifyDateTime($date . ' 00:00:00'), 'number' => $number, 'ne_syohin_syohin_code' => $neSyohinSyohinCode]);

      if (!$report) {
        throw new \RuntimeException('更新データが取得できませんでした。');
      }

      $report->setMoveNum($request->get('move_num'));
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 入庫確定処理
   * @param string $date
   * @param int $number
   * @return Response
   */
  public function pickingReportSubmitImportAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    $account = $this->getLoginUser();
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($account) {
      $logger->setAccount($account);
    }

    $logExecTitle = sprintf('実店舗入庫確定処理');

    try {
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var TbRealShopPickingReportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingReport');

      // スマレジ 在庫登録（加算）
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'スマレジ在庫登録（加算）', '開始'));

      $pickingReport = $repo->getReportDetail($date, $number);
      $stocks = [];
      foreach($pickingReport as $report) {
        $stocks[$report['ne_syohin_syohin_code']] = $report['move_num'];
      }

      /** @var RealShopSmaregiMallProcess $process */
      $process = $this->get('batch.mall_process.smaregi');

      // 商品を一括登録
      $doUpdate = $this->getParameter('smaregi_api_do_update');
      if (!$doUpdate) {
        $logger->info('※開発環境につき、スマレジへの更新処理はスキップ');

      } else {
        $process->registerProducts(array_keys($stocks));
        $process->storeStocks($stocks);
      }

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'スマレジ在庫登録（加算）', '終了')->setInformation($stocks));

      // ピッキングデータ ステータス更新
      /** @var TbRealShopPickingReport $report */
      $repo->submitImport($date, $number);

      // スマレジ在庫取込
      $this->runImportSmaregiStockCommand($account);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($logExecTitle . ' : ' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . ' でエラーが発生しました。', 'error'
      );
    }

    return new JsonResponse($result);
  }


  /**
   * ラベルCSVダウンロード
   * @param string $date
   * @param int $number
   * @param string $type
   * @return StreamedResponse
   */
  public function downloadLabelCsvAction($date, $number, $type)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    if (!$date || !$number) {
      throw new \RuntimeException('CSVの指定が正しくありません。');
    }

    // 返送品
    if ($type == 'return') {

      return $this->downloadLabelCsvReturnAction($date, $number);

//      /** @var TbRealShopReturnReportRepository $repo */
//      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopReturnReport');
//      $reportList = $repo->getReportLabelList($date, $number);

    // ピッキング
    } else {
      /** @var TbRealShopPickingReportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingReport');
      $reportList = $repo->getReportLabelList($date, $number, $type);
    }


    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');
    // ヘッダ
    $headers = [
        // 'move_num'              => '片数'
        'ne_syohin_syohin_code' => '品番'
      , 'product_code'          => '商品コード'
      , 'colname'               => '横軸'
      , 'rowname'               => '縦軸'
      , 'baika_tanka_taxed'     => '販売価格'
      , 'baika_tanka'           => '本体価格'
    ];

    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($reportList, $stringUtil, $headers, $logger) {
        $file = new \SplFileObject('php://output', 'w');
        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        // $file->fwrite($header); // 「合わせ名人4」のヘッダ行に関する挙動が固定できないため、ヘッダは出力しない

        foreach($reportList as $index => $row) {

          if (strlen($row['product_code'])) {
            $row['product_code'] = $stringUtil->convertNumToJan13($row['product_code']);
          }

          $row['baika_tanka_taxed'] = floor($row['baika_tanka'] * (100 + DbCommonUtil::CURRENT_TAX_RATE_PERCENT) / 100);

          for($i = 0; $i < $row['move_num']; $i++) {
            $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
            $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

            // 最終行は、改行コード不要。（「合わせ名人4」のデータ取込仕様）
            if ($index == (count($reportList) - 1) && $i == ($row['move_num'] - 1)) {
              $line = str_replace($eol, '', $line);
            }

            $file->fwrite($line);
          }

          flush();
        }
      }
    );

    $fileName = sprintf('real_shop_label_%s_%d_%s.csv', $date, $number, $type);

    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
    $response->send();

    return $response;
  }


  /**
   * ラベルCSVダウンロード（単品）
   * @param Request $request
   * @return StreamedResponse
   */
  public function downloadLabelCsvEachProductAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $syohinCode = $request->get('code');
    $num = $request->get('num');
    $type = $request->get('type');

    if (!$syohinCode || !$type) {
      throw new \RuntimeException('CSVの指定が正しくありません。');
    }

    /** @var TbRealShopPickingReportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingReport');
    $data = $repo->getProductLabel($syohinCode);

    $list = [];
    if ($data) {
      $data['move_num'] = $num;
      $list[] = $data;
    }

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');
    // ヘッダ
    $headers = [
        'ne_syohin_syohin_code' => '品番'
      , 'product_code'          => '商品コード'
      , 'colname'               => '横軸'
      , 'rowname'               => '縦軸'
      , 'baika_tanka_taxed'     => '販売価格'
      , 'baika_tanka'           => '本体価格'
    ];

    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($list, $stringUtil, $headers, $logger) {
        $file = new \SplFileObject('php://output', 'w');
        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
        // $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        // $file->fwrite($header); // 「合わせ名人4」のヘッダ行に関する挙動が固定できないため、ヘッダは出力しない

        foreach($list as $index => $row) {

          if (strlen($row['product_code'])) {
            $row['product_code'] = $stringUtil->convertNumToJan13($row['product_code']);
          }

          $row['baika_tanka_taxed'] = floor($row['baika_tanka'] * (100 + DbCommonUtil::CURRENT_TAX_RATE_PERCENT) / 100);

          for($i = 0; $i < $row['move_num']; $i++) {
            $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
            $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

            // 最終行は、改行コード不要。（「合わせ名人4」のデータ取込仕様）
            if ($index == (count($list) - 1) && $i == ($row['move_num'] - 1)) {
              $line = str_replace($eol, '', $line);
            }

            $file->fwrite($line);
          }

          flush();
        }
      }
    );

    $fileName = sprintf('real_shop_label_%s_%s.csv', $syohinCode, $type);

    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
    $response->send();

    return $response;
  }


  /**
   * ラベルCSV 返送品用ラベルダウンロード（ラベル屋さん）
   * @param string $date
   * @param int $number
   * @return StreamedResponse
   */
  public function downloadLabelCsvReturnAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    if (!$date || !$number) {
      throw new \RuntimeException('CSVの指定が正しくありません。');
    }

    // 返送品
    /** @var TbRealShopReturnReportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopReturnReport');
    $reportList = $repo->getReportLabelListForRaberuyasan($date, $number);

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');
    // ヘッダ
    $headers = [
        'label1' => 'label1'
      , 'label2' => 'label2'
      , 'label3' => 'label3'
      , 'label4' => 'label4'
    ];

    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($reportList, $stringUtil, $headers, $logger) {
        $file = new \SplFileObject('php://output', 'w');
        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        // $file->fwrite($header); // ヘッダは出力しない

        foreach($reportList as $index => $row) {

          for($i = 0; $i < $row['num']; $i++) {
            $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
            $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

            // 最終行は、改行コード不要。
            if ($index == (count($reportList) - 1) && $i == ($row['num'] - 1)) {
              $line = str_replace($eol, '', $line);
            }

            $file->fwrite($line);
          }

          flush();
        }
      }
    );

    $fileName = sprintf('real_shop_label_%s_%d_return.csv', $date, $number);

    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
    $response->send();

    return $response;
  }


  /**
   * スマレジ在庫取込処理
   */
  public function importSmaregiStockAction()
  {
    // スマレジ在庫取込
    $this->runImportSmaregiStockCommand($this->getLoginUser());

    return $this->redirectToRoute('real_shop_product_stock_list');
  }


  /**
   * 簡易商品登録画面
   * @param Request $request
   * @return Response
   */
  public function registerSimpleProductAction(Request $request)
  {
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $form = $this->createForm(new TbMainproductsSimpleType());

    // フォームデータ引き継ぎ用にここで取得
    $defaultSkuPart = [
      [
          'code' => ''
        , 'name' => ''
      ]
    ];
    $cols = $request->get('cols', $defaultSkuPart);
    $rows = $request->get('rows', $defaultSkuPart);

    $form->handleRequest($request);
    if ($form->isSubmitted()) {

      try {

        if ($form->isValid()) {

          /** @var DbCommonUtil $commonUtil */
          $commonUtil = $this->get('misc.util.db_common');

          /** @var EntityManager $em */
          $em = $this->getDoctrine()->getManager();
          /** @var \Doctrine\DBAL\Connection $dbMain */
          $dbMain = $em->getConnection();
          $dbMain->beginTransaction();

          /** @var TbMainproducts $product */
          $product = $form->getData();

          // 商品コードは 'mnn-' 限定
          if (!preg_match('/^mnn-[a-zA-Z0-9-]{1,13}$/', $product->getDaihyoSyohinCode())) {
            throw new \RuntimeException('代表商品コードは、「mnn-」から初めて重複のないように設定してください。(全17文字まで)');
          }

          $sdRepo =  $this->getDoctrine()->getRepository('MiscBundle:TbShippingdivision');
          $shippindivision = $sdRepo->find(DbCommonUtil::DELIVERY_TYPE_TAKUHAI_BETSU);
          $product->setSireCode($commonUtil->getSettingValue('REAL_SHOP_SIRE_CODE'));
          $product->setSyohinKbn(10);
          $product->setNeDirectoryId('101801'); // TODO 確認
          $product->setYahooDirectoryId('1682'); // TODO 確認
          $product->setWeight(0);
          $product->setShippingdivision($shippindivision);

          // カラー軸
          if ($product->getColTypeName() == 'カラー') {
            $product->setColorAxis('col');
          } else if ($product->getRowTypeName() == 'カラー') {
            $product->setColorAxis('row');
          }

          $product->setRegisteredDatetime(new \DateTime());
          $product->setSaleStartDate(null);

          if ($account) {
            $product->setPerson($account->getUsername());
          }

          // 仕入先
          $vendorRepo = $this->getDoctrine()->getRepository('MiscBundle:TbVendormasterdata');
          $vendor = $vendorRepo->find($product->getSireCode());
          if ($vendor) {
            $product->setVendor($vendor);
          }

//
//          // SetProduct relationを無理に設定したせいで、新規オブジェクトの作成ができない（relation先のオブジェクトをセットしなきゃいけない）
//          // ひとまず緊急避難
//          $sql = <<<EOD
//            INSERT IGNORE INTO tb_mainproducts_cal (
//              daihyo_syohin_code
//            ) VALUES (
//              :daihyoSyohinCode
//            )
//EOD;
//          $stmt = $dbMain->prepare($sql);
//          $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
//          $stmt->execute();
//          $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproductsCal');
//          /** @var TbMainproductsCal $cal */
//          $cal = $repo->find($product->getDaihyoSyohinCode());

          $cal = new TbMainproductsCal();
          $product->setCal($cal);
          $cal->setProduct($product);
          $cal->setAdultCheckStatus(TbMainproductsCal::ADULT_CHECK_STATUS_NONE); // 未審査
          $cal->setDeliverycode(TbMainproductsCal::DELIVERY_CODE_FINISHED);
          $cal->setDeliverycodePre(TbMainproductsCal::DELIVERY_CODE_FINISHED);
          $cal->setEndofavailability(new \DateTime());
          $cal->setGenkaTnkAve($product->getGenkaTnk());
          $cal->setWeightCheckNeedFlg(-1);

          $em->persist($product);
          $em->persist($cal);

          // 商品画像登録
          /** @var ImageUtil $imageUtil */
          $imageUtil = $this->get('misc.util.image');

          $imageCode = 'p001'; // メイン画像

          $productImage = new ProductImages();
          $productImage->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
          $productImage->setCode($imageCode);
          $em->persist($productImage);

          $imageDir = $imageUtil->findAvailableImageDirectory();
          $imageName = sprintf('%s.jpg', strtolower($product->getDaihyoSyohinCode()));
          $imageAddress = sprintf('https://image.rakuten.co.jp/plusnao/cabinet/%s/%s', $imageDir, $imageName);

          $productImage->setAddress($imageAddress);
          $productImage->setDirectory($imageDir);
          $productImage->setFilename($imageName);
          $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

          // 本体へも格納（後方互換）
          $product->setImageFieldData('address', 'p001', $imageAddress);
          $product->setImageFieldData('directory', 'p001', $imageDir);
          $product->setImageFieldData('filename', 'p001', $imageName);

          // 画像ファイルの（上書き）保存
          /** @var FileUtil $fileUtil */
          $fileUtil = $this->get('misc.util.file');
          $imageSource = sprintf('%s/product/no_image.jpg', $fileUtil->getDataDir());
          $tmpImage = new TmpProductImages();
          $tmpImage->setImage(file_get_contents($imageSource));
          $originalFilePath = $imageUtil->saveTmpProductImageToOriginal($productImage, $tmpImage);
          if (!$originalFilePath) {
            throw new \RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getDirectory() . '/' . $productImage->getFilename() );
          }

          // 画像ファイルの加工処理
          $imageUtil->convertOriginalFileToFixedFile($originalFilePath);

          // オリジナル画像でmd5取得
          $productImage->setMd5hash(hash_file('md5', $originalFilePath));

          // 類似画像チェック用 文字列作成・格納（上書き） → なし

          // SKU登録
          $logger->info(print_r($cols, true));
          $logger->info(print_r($rows, true));

          // チェック、整形および不要データ削除
          $fixedCols = [];
          foreach($cols as $col) {
            $code = $this->fixSkuCode($col['code']);
            $name = $this->fixSkuName($col['name']);
            if (strlen($code) && strlen($name)) {
              $fixedCols[$code] = $name;
            }
          }
          $fixedRows = [];
          foreach($rows as $row) {
            $code = $this->fixSkuCode($row['code']);
            $name = $this->fixSkuName($row['name']);
            if (strlen($code) && strlen($name)) {
              $fixedRows[$code] = $name;
            }
          }

          $logger->info(print_r($fixedCols, true));
          $logger->info(print_r($fixedRows, true));

          if (!$fixedCols || !$fixedRows) {
            throw new \RuntimeException('有効なSKUがありません。');
          }

          $displayOrder = 1;
          foreach($fixedCols as $colCode => $colName) {
            foreach($fixedRows as $rowCode => $rowName) {
              $choice = new TbProductchoiceitems();
              $choice->setProduct($product);
              $choice->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
              $choice->setColcode('-' . $colCode);
              $choice->setColname($colName);
              $choice->setRowcode('-' . $rowCode);
              $choice->setRowname($rowName);
              $choice->setDisplayOrder($displayOrder++);
              $choice->setOrderEnabled(true); // ここが 1 でDBへ格納されてしまう。暫定的に、後から -1 で更新

              $syohinCode = sprintf('%s%s%s', $choice->getDaihyoSyohinCode(), $choice->getColcode(), $choice->getRowCode());
              $logger->info($syohinCode);
              $choice->setNeSyohinSyohinCode($syohinCode);

              $em->persist($choice);
            }
          }

          // 各モール information テーブル レコード登録
          $mallInformationTableList = array(
              'tb_amazoninfomation' => 'amazon_title' // amazon
            , 'tb_biddersinfomation' => 'bidders_title' // bidders
            , 'tb_qten_information' => 'q10_title' // q10
            , 'tb_rakuteninformation' => '楽天タイトル' // rakuten_plusnao
            , 'tb_rakuten_motto_information' => '楽天タイトル' // rakuten_motto
            , 'tb_rakuten_laforest_information' => '楽天タイトル' // rakuten_laforest
            , 'tb_rakuten_dolcissimo_information' => '楽天タイトル' // rakuten_dolcissimo
            , 'tb_rakuten_gekipla_information' => '楽天タイトル' // rakuten_gekipla
            , 'tb_cube_information' => 'title' // ec-cube
            , 'tb_yahoo_information' => 'yahoo_title' // plusnao_yahoo
            , 'tb_yahoo_kawa_information' => 'yahoo_title' // kawa_yahoo
            , 'tb_yahoo_otoriyose_information' => 'yahoo_title' // otoriyose_yahoo
            , 'tb_ss_information' => 'ss_title' // ss
            , 'tb_ppm_information' => 'ppm_title' // ppm
            , 'tb_shoplist_information' => 'title' // shoplist
            , 'tb_amazon_com_information' => '' // Amazon.com
          );

          // mnn- 商品は全て出品フラグOFFで登録
          foreach($mallInformationTableList as $table => $titleColumn) {

            if (!$titleColumn) {
              $format = <<<EOD
              INSERT INTO `%s` (
                  `daihyo_syohin_code`
                , `registration_flg`
              ) VALUES (
                  :daihyoSyohinCode
                , 0
              )
EOD;
              $sql = sprintf($format, $table);
              $stmt = $dbMain->prepare($sql);
              $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
              $stmt->execute();

            } else {
              // mnn- 商品は全て出品フラグOFFで登録
              $format = <<<EOD
              INSERT INTO `%s` (
                  `daihyo_syohin_code`
                , `registration_flg`
                , `%s`
              ) VALUES (
                  :daihyoSyohinCode
                , 0
                , :title
              )
EOD;
              $sql = sprintf($format, $table, $titleColumn);
              $stmt = $dbMain->prepare($sql);
              $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
              $stmt->bindValue(':title', $product->getDaihyoSyohinName());
              $stmt->execute();
            }

          }

          $em->flush();

          // 暫定対応。boolean 定義をしてしまったので、1 を -1 に強制置換
          $sql = <<<EOD
            UPDATE tb_productchoiceitems pci
            SET pci.受発注可能フラグ = -1
            WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
          $stmt->execute();

          $dbMain->commit();

          $this->addFlash(
            'success',
            '商品を簡易登録しました。[' . $product->getDaihyoSyohinCode() . ']'
          );

          return $this->redirectToRoute('real_shop_register_simple_product');
        } else {
          $this->addFlash(
            'warning',
            '入力エラーがあります。'
          );
        }

      } catch (\Exception $e) {
        $this->addFlash(
            'danger'
          , '商品登録ができませんでした。 (' . $e->getMessage() . ')'
        );

        if (isset($dbMain)) {
          $dbMain->rollback();
        }
      }

    }

    return $this->render('AppBundle:RealShop:register-simple-product.html.twig', [
        'account' => $account
      , 'form' => $form->createView()
      , 'skuDataJson' => json_encode([
          'cols' => $cols
        , 'rows' => $rows
      ])
    ]);
  }

  /**
   * 返品入力画面
   * @param Request $request
   * @param int $page
   * @return Response
   */
  public function returnGoodsListAction(Request $request, $page)
  {
    $account = $this->getLoginUser();

    /** @var TbRealShopReturnReportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopReturnReport');
    $pagination = $repo->getListData([], [], $page);

    // 画面表示
    return $this->render('AppBundle:RealShop:return-goods-list.html.twig', [
        'account' => $account
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
    ]);
  }

  /**
   * 返品入力画面
   * @param Request $request
   * @return Response
   */
  public function returnGoodsInputAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // POSTなら確定処理
    if ($request->isMethod(Request::METHOD_POST)) {

      $result = [
          'status' => 'ok'
        , 'message' => null
      ];

      try {
        $list = $request->get('list');

        /** @var TbRealShopReturnReportRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopReturnReport');

        // 返品データ登録処理＆在庫依頼更新処理
        $date = new \DateTime();
        $number = $repo->getMaxNumber($date) + 1;
        $repo->registerReport($date, $number, $list, $this->getLoginUser());

        // スマレジ在庫更新処理（減算）
        $logExecTitle = '実店舗返品確定処理';
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'スマレジ在庫登録（減算）', '開始'));

        $returnReport = $repo->getReportDetail($date->format('Y-m-d'), $number);
        $stocks = [];
        foreach($returnReport as $report) {
          $stocks[$report['ne_syohin_syohin_code']] = $report['move_num'] * -1; // 減算
        }

        /** @var RealShopSmaregiMallProcess $process */
        $process = $this->get('batch.mall_process.smaregi');

        // 商品を一括登録
        $doUpdate = $this->getParameter('smaregi_api_do_update');
        if (!$doUpdate) {
          $logger->info('※開発環境につき、スマレジへの更新処理はスキップ');
        } else {
          $process->storeStocks($stocks);
        }

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'スマレジ在庫登録（減算）', '終了')->setInformation($stocks));

        // スマレジ在庫取込
        $this->runImportSmaregiStockCommand($this->getLoginUser());

      } catch (\Exception $e) {
        $logger->error($e->getTraceAsString());

        $result['status'] = 'ng';
        $result['message'] = $e->getMessage();
      }

      return new JsonResponse($result);

    } else {
      // 店舗在庫全データ取得
      /** @var TbRealShopProductStockRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopProductStock');
      $shopStocks = $repo->getAllShopStocks();

      return $this->render('AppBundle:RealShop:return-goods-input.html.twig', [
        'account' => $this->getLoginUser()
        , 'shopStockJson' => json_encode($shopStocks)
      ]);
    }
  }


  /**
   * 返品リスト 詳細
   * @param string $date
   * @param int $number
   * @return Response
   */
  public function returnGoodsDetailAction($date, $number)
  {
    $account = $this->getLoginUser();

    /** @var TbRealShopReturnReportRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopReturnReport');
    $returnReport = $repo->getReportDetail($date, $number);

    // 画面表示
    return $this->render('AppBundle:RealShop:return-goods-detail.html.twig', [
        'account' => $account
      , 'returnDate' => $date
      , 'number' => $number
      , 'returnReportJson' => json_encode($returnReport)
    ]);
  }


  /**
   * 返品確定処理
   * @param string $date
   * @param int $number
   * @return Response
   */
  public function returnGoodsSubmitImportAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    $account = $this->getLoginUser();
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($account) {
      $logger->setAccount($account);
    }

    $logExecTitle = sprintf('実店舗返品確定処理');

    try {
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var TbRealShopReturnReportRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopReturnReport');


      // 対象倉庫 => 一旦固定。
      //    ※ ログインアカウントの選択倉庫の直接利用は、切り替え忘れの事故などがありうるので、改修時には注意。
      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      /** @var TbWarehouse $currentWarehouse */
      $currentWarehouse = $repoWarehouse->find(TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);

      // ロケーション作成、ステータス更新
      $newLocation = $repo->submitImport($date, $number, $account, $currentWarehouse);
      $result['location_code'] = $newLocation->getLocationCode();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($logExecTitle . ' : ' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . ' でエラーが発生しました。', 'error'
      );
    }

    return new JsonResponse($result);
  }


  // -----------------------------------------------
  // private methods
  // -----------------------------------------------
  /**
   * スマレジ在庫取込 コマンド実行
   * @param SymfonyUsers $account
   */
  private function runImportSmaregiStockCommand($account = null)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // スマレジ在庫取込
    $commandArgs = [
      'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
    ];
    if ($account) {
      $commandArgs[] = sprintf('--account=%d', $account->getId());
    }

    $logger->info('real shop: batch:real_shop_import_smaregi_stock: ' . print_r($commandArgs, true));
    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->get('batch.real_shop_import_smaregi_stock');
    $exitCode = $command->run($input, $output);
    if ($exitCode !== 0) { // コマンドが異常終了した
      throw new \RuntimeException('スマレジ在庫取込でエラーが発生しました。 : ' . $exitCode);
    }
  }

  /**
   * SKU コード整形
   */
  private function fixSkuCode($code)
  {
    $code = mb_convert_kana($code, 'as', 'UTF-8');
    $code = preg_replace('/[^a-zA-Z0-9-]+/u', '', $code);

    return $code;
  }
  /**
   * SKU 名前整形
   */
  private function fixSkuName($name)
  {
    return preg_replace('/[\s　]+/u', '', $name);
  }

}
