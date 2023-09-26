<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MiscBundle\Entity\TbUsedTrackingNumberReport;
use MiscBundle\Entity\Repository\TbDeliveryMethodRepository;
use MiscBundle\Entity\Repository\TbShipmentTrackingNumberRepository;
use MiscBundle\Entity\Repository\TbUsedTrackingNumberReportRepository;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Service\PackingService;
use MiscBundle\Service\ShippingInquiryNumberService;
use MiscBundle\Util\BatchLogger;

/**
 * 配送ラベル再発行伝票・不使用問い合わせ番号関連のコントローラー
 * @package AppBundle\Controller
 */
class ShippingInquiryNumberController extends BaseController
{
  /**
   * 使用済み発送伝票報告CSV 表示
   */
  public function usedReportCsvListAction()
  {
    /** @var TbDeliveryMethodRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryMethod');
    $deliveryMethodAll = $repo->findBy([], ['deliveryId' => 'ASC']);
    $deliveryMethods = [];
    foreach ($deliveryMethodAll as $delivery) {
      $deliveryMethods[$delivery->getDeliveryId()] = $delivery->getDeliveryName();
    }

    return $this->render(
      'AppBundle:ShippingInquiryNumber:shipping-inquiry-number-used-report-csv-list.html.twig',
      [
        'account' => $this->getLoginUser(),
        'deliveryMethods' => json_encode($deliveryMethods),
      ]
    );
  }

  /**
   * 使用済み発送伝票報告CSV 検索
   * @return JsonResponse
   */
  public function usedReportCsvFindAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var TbUsedTrackingNumberReportRepository $repoReport */
    $repoReport = $this->getDoctrine()->getRepository('MiscBundle:TbUsedTrackingNumberReport');

    try {
      $maxDisplayNum = 30;
      $repoReport->deleteOldRecordsExceptLatest($maxDisplayNum);
      $result['reportList'] = $repoReport->findAllAndTrackingNumberCount();
      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("使用済み発送伝票報告CSV 検索機能でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 使用済み発送伝票報告CSV 絞込
   * @param Request $request
   * @return JsonResponse
   */
  public function usedReportCsvFilterAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var TbShipmentTrackingNumberRepository $repoTracking */
    $repoTracking = $this->getDoctrine()->getRepository('MiscBundle:TbShipmentTrackingNumber');

    try {
      // 配送方法毎に使用済の発送伝票番号リストを取得する
      $conditions = $request->get('conditions');
      $result['filterResults'] = $repoTracking->findUsedTrackingNumbers($conditions);

      // NE側と異なる発送方法の発送伝票番号を使用している伝票番号と、
      // その伝票番号のNE側の発送方法名を、発送方法毎に連想配列で取得する
      $result['mismatchedDeliveryList'] = [];
      foreach ($result['filterResults'] as $deliveryId => $trackingNumbers) {
        $mismatchedList = $repoTracking->findDiffDeliveryListFromNE(
          $trackingNumbers,
          $deliveryId
        );
        $result['mismatchedDeliveryList'][$deliveryId] = $mismatchedList;
      }

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("使用済み発送伝票報告CSV 絞込機能でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 使用済み発送伝票報告CSV 生成
   * @param Request $request
   * @return JsonResponse
   */
  public function usedReportCsvGenerateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var TbUsedTrackingNumberReportRepository $repoReport */
    $repoReport = $this->getDoctrine()->getRepository('MiscBundle:TbUsedTrackingNumberReport');
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // トランザクション開始
    $dbMain->beginTransaction();

    try {
      $deliveryId = $request->get('deliveryId');
      $trackingNumbers = $request->get('trackingNumbers');

      $repoReport->generateUsedReport($deliveryId, $trackingNumbers);
      $result['status'] = 'ok';

      // トランザクションをコミット
      $dbMain->commit();
    } catch (\Exception $e) {
      // エラーが発生した場合は、トランザクションをロールバック
      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      $logger->error("使用済み発送伝票報告CSV 生成機能でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 使用済み発送伝票報告CSV ダウンロード
   * @param Request $request
   * @return JsonResponse
   */
  public function usedReportCsvDownloadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var TbShipmentTrackingNumberRepository $repoTracking */
    $repoTracking = $this->getDoctrine()->getRepository('MiscBundle:TbShipmentTrackingNumber');
    /** @var TbUsedTrackingNumberReportRepository $repoReport */
    $repoReport = $this->getDoctrine()->getRepository('MiscBundle:TbUsedTrackingNumberReport');
    /** @var ShippingInquiryNumberService $service */
    $service = $this->get('misc.service.shippingInquiryNumber');

    try {
      $id = $request->get('id');
      $type = $request->get('type');

      // 伝票番号と発送伝票番号の組み合わせを取得
      $voucherAndTrackingNumberPairs = $repoTracking->findVoucherAndTrackingNumberPairs($id);

      // CSVをダウンロードする
      $csvContent = '';
      if ($type === 'edi') {
        $csvContent = $service->downloadEdiUsedReportCsv($voucherAndTrackingNumberPairs);
      } elseif ($type === 'ne') {
        $csvContent = $service->downloadNeUsedReportCsv($voucherAndTrackingNumberPairs);
      }

      // CSVデータをBase64エンコード
      $result['csvData'] = base64_encode($csvContent);

      // DL回数を1増やす
      /** @var TbUsedTrackingNumberReport $report */
      $report = $repoReport->find($id);
      if ($type === 'edi') {
        $report->setDownloadCountEdi($report->getDownloadCountEdi() + 1);
      } elseif ($type === 'ne') {
        $report->setDownloadCountNe($report->getDownloadCountNe() + 1);
      }
      $em = $this->getDoctrine()->getManager();
      $em->persist($report);
      $em->flush();

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("使用済み発送伝票報告CSV ダウンロード機能でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 使用済み発送伝票報告CSV 出荷破棄伝票報告
   * @param Request $request
   * @return JsonResponse
   */
  public function usedReportCsvCancelReportAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var TbShipmentTrackingNumberRepository $repoTracking */
    $repoTracking = $this->getDoctrine()->getRepository('MiscBundle:TbShipmentTrackingNumber');

    try {
      $voucherNumbers = $request->get('voucherNumbers');
      $repoTracking->updateStatusToCancelled($voucherNumbers);

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("使用済み発送伝票報告CSV 出荷破棄伝票報告機能でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 配送ラベル再発行伝票一覧 表示
   */
  public function reissueListAction()
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');

    $warehouseId = $this->getLoginUser()->getWarehouseId();
    $pullDown = $service->findPackingGroupGroupingList($warehouseId);

    return $this->render('AppBundle:ShippingInquiryNumber:shipping-inquiry-number-reissue-list.html.twig', [
      'account' => $this->getLoginUser(),
      'pullDown' => $pullDown,
      'savedConditions' => json_encode(''),
      'savedMessage' => json_encode('')
    ]);
  }

  /**
   * 配送ラベル再発行伝票一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function reissueFindAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var ShippingInquiryNumberService $service */
    $service = $this->get('misc.service.shippingInquiryNumber');

    try {
      $conditions = $request->get('conditions');
      // CSV未ダウンロード限定の有無、文字列（'true'）→真偽値(true)に戻す。
      $conditions['onlyNotCsvDownload'] = json_decode($conditions['onlyNotCsvDownload']);

      // 検索処理
      $list = $service->findReissueList($conditions);
      if (empty($list)) {
        $result['status'] = 'ng';
        $result['message'] = '指定された条件の配送ラベル再発行伝票がありません';
        return new JsonResponse($result);
      }

      // 検索結果から、ユニークな発送方法IDの配列を作成。
      $deliveryMethodIds = array_values(array_unique(array_column($list, "deliveryMethodId")));
      // 検索結果に存在する配送方法の情報を取得
      $result['deliveryMethodList'] = $service->findDeliveryMethodListByIds($deliveryMethodIds);

      $result['list'] = $this->addDetailUrl($list);

      $result['status'] = 'ok';

    } catch (\Exception $e) {
      $logger->error("配送ラベル再発行伝票一覧検索機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 配送ラベル再発行伝票一覧 CSVダウンロード
   * @param Request $request
   * @return StreamedResponse
   */
  public function reissueDownloadAction(Request $request)
  {
    /** @var ShippingInquiryNumberService $service */
    $service = $this->get('misc.service.shippingInquiryNumber');

    try {
      $account = $this->getLoginUser();
      $accountId = $account ? $account->getId() : 0;

      $unissuedIds = [];
      $unissuedPackingIds = [];
      if (!empty($request->get('unissuedIds'))) {
        // 更新対象（未発行）の配送ラベル再発行伝票IDを、数値の配列にする。
        $unissuedIds = explode(",", $request->get('unissuedIds'));
        $unissuedIds = array_map('intval', $unissuedIds);

        // 更新対象（未発行）の出荷伝票梱包IDを、ユニークな数値の配列にする。
        $unissuedPackingIds = explode(",", $request->get('unissuedPackingIds'));
        $unissuedPackingIds = array_values(array_unique($unissuedPackingIds));
        $unissuedPackingIds = array_map('intval', $unissuedPackingIds);
      }

      // CSVダウンロード対象（全ステータス）の伝票番号を、ユニークな数値の配列にする。
      $voucherNumbers = explode(",", $request->get('voucherNumbers'));
      $voucherNumbers = array_values(array_unique($voucherNumbers));
      $voucherNumbers = array_map('intval', $voucherNumbers);

      $deliveryMethodId = $request->get('activeMethodId');

      $response = $service->csvDownload($unissuedIds, $unissuedPackingIds, $voucherNumbers, $deliveryMethodId, $accountId);
      $response->send();

      return $response;

    } catch (BusinessException $e) {
      /** @var PackingService $packingService */
      $packingService = $this->get('misc.service.packing');

      // JSONで送られてきた検索条件を、連想配列に変換する。
      $conditions = json_decode($request->get('conditions'), true);

      $pullDown = $packingService->findPackingGroupGroupingList($conditions['warehouseId']);

      return $this->render('AppBundle:ShippingInquiryNumber:shipping-inquiry-number-reissue-list.html.twig', [
        'account' => $this->getLoginUser(),
        'pullDown' => $pullDown,
        'savedConditions' => $request->get('conditions'),
        'savedMessage' => json_encode($e->getMessage())
      ]);

    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * 不使用問い合わせ番号一覧 表示
   */
  public function noneedListAction()
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');

    $warehouseId = $this->getLoginUser()->getWarehouseId();
    $pullDown = $service->findPackingGroupGroupingList($warehouseId);

    return $this->render('AppBundle:ShippingInquiryNumber:shipping-inquiry-number-noneed-list.html.twig', [
      'account' => $this->getLoginUser(),
      'pullDown' => $pullDown
    ]);
  }

  /**
   * 不使用問い合わせ番号一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function noneedFindAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var ShippingInquiryNumberService $service */
    $service = $this->get('misc.service.shippingInquiryNumber');

    try {
      $conditions = $request->get('conditions');
      // 入力未完了限定の有無、文字列（'true'）→真偽値(true)に戻す。
      $conditions['onlyIncompleteInput'] = json_decode($conditions['onlyIncompleteInput']);

      // 検索処理
      $list = $service->findNoneedList($conditions);
      if (empty($list)) {
        $result['status'] = 'ng';
        $result['message'] = '指定された条件の不使用問い合わせ番号がありません';
        return new JsonResponse($result);
      }

      // 検索結果から、ユニークな発送方法IDの配列を作成。
      $deliveryMethodIds = array_values(array_unique(array_column($list, "deliveryMethodId")));
      // 検索結果に存在する配送方法の情報を取得
      $result['deliveryMethodList'] = $service->findDeliveryMethodListByIds($deliveryMethodIds);

      $result['list'] = $this->addDetailUrl($list);

      $result['status'] = 'ok';

    } catch (\Exception $e) {
      $logger->error("不使用問い合わせ番号一覧検索機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 不使用問い合わせ番号一覧 入力完了
   * @param Request $request
   * @return JsonResponse
   */
  public function noneedCompleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var ShippingInquiryNumberService $service */
    $service = $this->get('misc.service.shippingInquiryNumber');

    try {
      $account = $this->getLoginUser();
      $accountId = $account ? $account->getId() : 0;

      $unregisteredIds = array_map('intval', $request->get('unregisteredIds'));

      $service->noneedComplete($unregisteredIds, $accountId);

      $result['status'] = 'ok';

    } catch (\Exception $e) {
      $logger->error("不使用問い合わせ番号一覧入力完了機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面へのURLを追加
   *
   * 倉庫ID、ピッキングリスト日付、ピッキングリストNo.、伝票番号を持つ連想配列の配列から、
   * 伝票詳細画面へのURLを各連想配列ごとに作成し、追加したものを、
   * 新しい連想配列の配列として返却する。
   * @param array $list
   * @return array
   */
  private function addDetailUrl($list) {
    $newList = array_map(function($row) {
      $row['detailUrl'] = $this->generateUrl('packing_shipping_voucher_detail', [
        'warehouseId' => $row['warehouseId'],
        'pickingListDate' => $row['pickingListDate'],
        'pickingListNumber' => $row['pickingListNumber'],
        'voucherNumber' => $row['voucherNumber']
      ]);
      return $row;
    }, $list);
    return $newList;
  }
}
