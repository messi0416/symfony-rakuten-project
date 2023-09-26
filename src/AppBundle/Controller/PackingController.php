<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MiscBundle\Entity\TbShippingVoucher;
use MiscBundle\Entity\TbShippingVoucherPackingGroup;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Service\PackingService;
use MiscBundle\Util\BatchLogger;


/**
 * 梱包関連
 * @package AppBundle\Controller
 */
class PackingController extends BaseController
{
  /**
   * 梱包グループリスト画面 表示
   */
  public function packingGroupListAction()
  {
    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    // 画面表示
    return $this->render('AppBundle:Packing:packing-group-list.html.twig', [
      'account' => $this->getLoginUser()
    ]);
  }

  /**
   * 梱包グループリスト画面 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function packingGroupFindAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      $warehouseId = $request->get('warehouseId');
      $isTodayOnly = boolval($request->get('isTodayOnly'));
      $isUnfinishOnly = boolval($request->get('isUnfinishOnly'));
      $today = new \DateTime();
      $fromDate = $isTodayOnly ? $today->format('Y-m-d') : $today->modify('-7 day')->format('Y-m-d');

      /** @var PackingService $packingService */
      $packingService = $this->get('misc.service.packing');
      $result['list'] = $packingService->findPackingGroupList($warehouseId, $fromDate, $isUnfinishOnly);
      // 梱包グループ画面へのURLを作成
      $result['list'] = array_map(function($row) {
        $row['packingGroupUrl'] = $this->generateUrl('packing_packing_group_index', ['id' => $row['id']]);
        return $row;
      }, $result['list']);

    } catch (\Exception $e) {
      $logger->error("梱包グループリスト検索機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 保留伝票リスト
   * @param int $page ページ数
   */
  public function holdShippingVoucherListAction($page)
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $pagination = $service->findHoldShippingVoucherPaging($page);

    return $this->render('AppBundle:Packing:hold-shipping-voucher-list.html.twig', [
      'account' => $this->getLoginUser(),
      'pagination' => $pagination,
      'paginationInfo' => $pagination->getPaginationData()
    ]);
  }

  /**
   * 梱包グループ画面 表示
   * @param int $id
   */
  public function packingGroupIndexAction($id)
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $item = $service->findPackingGroup($id);

    // 出荷伝票リスト画面へのURLを作成
    $item['shippingVoucherList'] = array_map(function($row) {
      $row['url'] = $this->generateUrl('packing_shipping_voucher_list', [
        'warehouseId' => $row['warehouseId'],
        'pickingListDate' => $row['pickingListDate'],
        'pickingListNumber' => $row['pickingListNumber']
      ]);
      return $row;
    }, $item['shippingVoucherList']);
    return $this->render('AppBundle:Packing:packing-group.html.twig', [
      'account' => $this->getLoginUser(),
      'item' => json_encode($item)
    ]);
  }

  /**
   * 出荷伝票リスト画面 表示
   * @param int $warehouseId
   * @param string $pickingListDate
   * @param int $pickingListNumber
   */
  public function shippingVoucherListAction($warehouseId, $pickingListDate, $pickingListNumber)
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $item = $service->findShippingVoucherList($warehouseId, $pickingListDate, $pickingListNumber);
    // 伝票詳細画面へのURLを作成
    $item['packingList'] = array_map(function($row) use($warehouseId, $pickingListDate, $pickingListNumber) {
      $row['detailUrl'] = $this->generateUrl('packing_shipping_voucher_detail', [
        'warehouseId' => $warehouseId,
        'pickingListDate' => $pickingListDate,
        'pickingListNumber' => $pickingListNumber,
        'voucherNumber' => $row['voucherNumber']
      ]);
      return $row;
    }, $item['packingList']);
    $groupId = $item['shippingVoucher']['shippingVoucherPackingGroupId'];
    return $this->render('AppBundle:Packing:packing-shipping-voucher-list.html.twig', [
      'account' => $this->getLoginUser(),
      'item' => json_encode($item),
      'groupId' => json_encode($groupId)
    ]);
  }

  /**
   * 出荷伝票リスト画面 梱包開始
   * @param Request $request
   * @return JsonResponse
   */
  public function packingStartAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $id = $request->get('id');
      $userId = $this->getLoginUser()->getId();
      /** @var PackingService $service */
      $service = $this->get('misc.service.packing');
      $result['updateInfo'] = $service->packingStart($id, $userId);
    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("梱包開始機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面 表示
   * @param int $warehouseId
   * @param string $pickingListDate
   * @param int $pickingListNumber
   * @param int $voucherNumber
   */
  public function shippingVoucherDetailAction($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber)
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $service->updateShippingVoucherPackingStatus($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber, $this->getLoginUser()->getId());
    $item = $service->findShippingVoucher($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber);
    $deliveryMethodList = $service->findDeliveryMethodList();

    // 出荷伝票リスト画面へのURLを作成
    $item['shippingVoucherListUrl'] = $this->generateUrl('packing_shipping_voucher_list', [
      'warehouseId' => $item['packing']['warehouseId'],
      'pickingListDate' => $item['packing']['pickingListDate'],
      'pickingListNumber' => $item['packing']['pickingListNumber']
    ]);
    return $this->render('AppBundle:Packing:shipping-voucher-detail.html.twig', [
      'account' => $this->getLoginUser(),
      'item' => json_encode($item),
      'deliveryMethodList' => json_encode($deliveryMethodList)
    ]);
  }

  /**
   * 伝票詳細画面 OK
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherDetailOkAction(Request $request)
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $form = $request->get('form');
    $result = [];
    try {
      $result = $service->updateDetailOk($form['id'], filter_var($form['isOk'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE), $form['updated']);
      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面 保留
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherDetailHoldAction(Request $request)
  {
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $form = $request->get('form');
    $result = [];
    try {
      $result = $service->updateDetailHold($form['id'], filter_var($form['isHold'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE), $form['updated']);
      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面 不足
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherDetailShortageAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $form = $request->get('form');
    $result = [];
    try {
      $userId = $this->getLoginUser()->getId();
      $result = $service->updateDetailShortage($form['packingId'], $form['detailId'], $form['assignNum'], $form['inquiryNumber'], $form['updated'], $form['packingUpdated'],  $userId);
      $result['status'] = 'ok';
    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error('梱包処理 梱包割り当て数更新: ' . $e->getMessage(). $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面 配送方法変更
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherDetailChangeDeliveryAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $form = $request->get('form');
    $result = [];
    try {
      $userId = $this->getLoginUser()->getId();
      $result = $service->changeDeliveryMethod($form['packingId'], $form['deliveryMethodId'], $form['inquiryNumber'], $form['updated'], $userId);
      $result['status'] = 'ok';
    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error('梱包処理 発送方法変更: ' . $e->getMessage(). $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面 完了して次へ
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherDetailCompleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $form = $request->get('form');
    $result = [];
    try {
      if (! json_decode($form['onlyNextFlg'])) {
        $userId = $this->getLoginUser()->getId();
        $service->updateShippingVoucherComplete($form['packingId'], $form['updated'], $userId, json_decode($form['stopFlg']), $form['inquiryNumber']);
      }

      $result['status'] = 'ok';
      if (filter_var($form['isLast'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
        if ($form['nextPickingListNumber']) {
          // 次の出荷伝票リストへ遷移
          $result['redirect'] = $this->generateUrl('packing_shipping_voucher_list', [
            'warehouseId' => $form['warehouseId'],
            'pickingListDate' => $form['pickingListDate'],
            'pickingListNumber' => $form['nextPickingListNumber']
          ]);
        } elseif ($form['packingGroupId']) {
          // 梱包グループ画面へ遷移
          $result['redirect'] = $this->generateUrl('packing_packing_group_index', [
            'id' => $form['packingGroupId']
          ]);
        } else {
          // 現在の出荷伝票リストへ遷移
          $result['redirect'] = $this->generateUrl('packing_shipping_voucher_list', [
            'warehouseId' => $form['warehouseId'],
            'pickingListDate' => $form['pickingListDate'],
            'pickingListNumber' => $form['pickingListNumber']
          ]);
        }
      } else {
        $result['redirect'] = $this->generateUrl('packing_shipping_voucher_detail', [
          'warehouseId' => $form['warehouseId'],
          'pickingListDate' => $form['pickingListDate'],
          'pickingListNumber' => $form['pickingListNumber'],
          'voucherNumber' => $form['nextVoucherNumber']
        ]);
      }
    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }catch (\Exception $e) {
      $logger->error('梱包処理 出荷伝票梱包完了: ' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 伝票詳細画面 配送方法変更して次へ
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherDetailChangeDeliveryAndCompleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var PackingService $service */
    $service = $this->get('misc.service.packing');
    $form = $request->get('form');
    $result = [];
    try {
      $userId = $this->getLoginUser()->getId();
      // 発送方法変更
      $changeDeliveryResult = $service->changeDeliveryMethod($form['packingId'], $form['deliveryMethodId'], $form['inquiryNumber'], $form['updated'], $userId);
      // 出荷伝票梱包完了
      $service->updateShippingVoucherComplete($form['packingId'], $changeDeliveryResult['updated'], $userId, false, null);

      if (filter_var($form['isLast'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
        $result['redirect'] = $this->generateUrl('packing_shipping_voucher_list', [
          'warehouseId' => $form['warehouseId'],
          'pickingListDate' => $form['pickingListDate'],
          'pickingListNumber' => $form['pickingListNumber']
        ]);
      } else {
        $result['redirect'] = $this->generateUrl('packing_shipping_voucher_detail', [
          'warehouseId' => $form['warehouseId'],
          'pickingListDate' => $form['pickingListDate'],
          'pickingListNumber' => $form['pickingListNumber'],
          'voucherNumber' => $form['nextVoucherNumber']
        ]);
      }
      $result['status'] = 'ok';
    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error('梱包処理 発送方法変更して次へ: ' . $e->getMessage(). $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
}
