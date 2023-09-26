<?php

namespace MiscBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Entity\Repository\TbDeliveryMethodRepository;
use MiscBundle\Entity\Repository\TbShippingReissueLabelRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherNoneedInquiryNumberRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingGroupRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbShippingVoucher;
use MiscBundle\Entity\TbShippingVoucherPackingGroup;
use MiscBundle\Entity\TbShippingVoucherPacking;
use MiscBundle\Exception\BusinessException;

use MiscBundle\Service\ServiceBaseTrait;
use MiscBundle\Entity\TbShippingReissueLabel;
use MiscBundle\Entity\Repository\SymfonyUsersRepository;

/**
 * 梱包Service。
 *
 * loggerやdoctrineなどはコンストラクタで定義しているため各メソッドでそのまま使用可能。
 */
class PackingService
{
  use ServiceBaseTrait;

  /**
   * 梱包グループ一覧取得。
   *
   * 引数をもとに、一致する梱包グループのID、梱包グループ名、ステータスを配列で返却。
   * @param int $warehouseId 対象倉庫
   * @param string $fromDate この日付以降のデータを取得
   * @param boolean $isUnfinishOnly 0:すべて/ 1:未完了のみ
   * @return array TbShippingVoucherPackingGroup 梱包グループのリスト
   */
  public function findPackingGroupList($warehouseId, $fromDate, $isUnfinishOnly)
  {
    /** @var TbShippingVoucherPackingGroupRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
    $list = $repo->findPackingGroupList($warehouseId, $fromDate, $isUnfinishOnly);
    return $list;
  }

  /**
   * 保留の伝票データ取得
   * @param int $page ページ数
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findHoldShippingVoucherPaging($page)
  {
    /** @var TbShippingVoucherPackingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    return $repo->findHoldShippingVoucherPaging($page);
  }

  /**
   * 梱包グループ取得。
   *
   * 引数をもとに、一致する梱包グループと、
   * IDに紐づく出荷伝票グループを連想配列で返却。
   * @param int $packingGroupId 梱包グループID
   * @return array 以下の2つのキーを持つ連想配列。IDに該当するものがなければnull
   *    'packing_group' => TbShippingVoucherPackingGroup 梱包グループ
   *    'shipping_voucher_list' => array TbShippingVoucher 出荷伝票グループのリスト
   */
  public function findPackingGroup($packingGroupId)
  {
    $result = [] ;
    /** @var TbShippingVoucherPackingGroupRepository $groupRepo */
    $groupRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
    $packingGroup = $groupRepo->find($packingGroupId);
    if(!$packingGroup) {
      return null;
    }
    $result['packing_group'] = [
      'id' => $packingGroup->getId(),
      'name' => $packingGroup->getName(),
      'comment' => $packingGroup->getPackingComment()
    ];
    /** @var TbShippingVoucherRepository $voucherRepo */
    $voucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $shippingVoucherList = $voucherRepo->findByGroupIdForPackingGroupIndex($packingGroupId);
    $result['shipping_voucher_list'] = [];
    foreach ($shippingVoucherList as $shippingVoucher) {
      $symbol = $shippingVoucher['symbol'] . '-' . $shippingVoucher['warehouse_daily_number'];
      $result['shippingVoucherList'][] = [
        'id' => $shippingVoucher['id'],
        'symbol' => $symbol,
        'status' => $shippingVoucher['status'],
        'statusName' => TbShippingVoucher::getDescription($shippingVoucher['status']),
        'packingAccount' => $shippingVoucher['username'],
        'amount' => $shippingVoucher['amount'],
        'warehouseId' => $shippingVoucher['warehouse_id'],
        'pickingListDate' => $shippingVoucher['picking_list_date']->format('Y-m-d'),
        'pickingListNumber' => $shippingVoucher['picking_list_number']
      ];
    }

    return $result;
  }

  /**
   * 出荷伝票一覧取得。
   *
   * 引数をもとに、一致する出荷伝票グループと、
   * 紐づく出荷伝票梱包情報のリストを連想配列で返却。
   * 　（出荷伝票梱包情報の順序は、出荷伝票明細テーブルの登録順。）
   * @param int $warehouseId 倉庫ID
   * @param string $pickingListDate ピッキング日付
   * @param int $pickingListNumber ピッキング番号
   * @return array 以下の3つのキーを持つ連想配列。IDに該当するものがなければnull
   *    'shippingVoucher' => TbShippingVoucher 出荷伝票グループ
   *    'packingList' => array TbShippingVoucherPacking 出荷伝票梱包のリスト
   *    'detailList' => array 出荷伝票明細のリスト
   */
  public function findShippingVoucherList($warehouseId, $pickingListDate, $pickingListNumber)
  {
    $result = [] ;
    /** @var TbShippingVoucherRepository $voucherRepo */
    $voucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $voucherList = $voucherRepo->findForPackingShippingVoucher($warehouseId, $pickingListDate, $pickingListNumber);
    if (empty($voucherList)) {
      return null;
    }
    $result['shippingVoucher'] = [
      'id' => $voucherList[0]['id'],
      'shippingVoucherPackingGroupId' => $voucherList[0]['shippingVoucherPackingGroupId'],
      'name' => $voucherList[0]['symbol'] . '-' . $voucherList[0]['warehouse_daily_number'],
      'deliveryName' => $voucherList[0]['deliveryName'],
      'status' => $voucherList[0]['status'],
      'packingAccountName' => $voucherList[0]['username']
    ];
    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    $packingList = $packingRepo->findByVoucherIdForPackingShippingVoucherList($voucherList[0]['id']);
    $result['packingList'] = [];
    foreach ($packingList as $packing) {
      $result['packingList'][] = [
        'id' => $packing['id'],
        'voucherNumber' => $packing['voucher_number'],
        'status' => $packing['status'],
        'labelReissueFlg' => $packing['label_reissue_flg'],
        'deliveryName' => $packing['delivery_name'],
        'productQuantity' => $packing['商品数']
      ];
    }
    $detailList = $voucherRepo->findShippingVoucherDetailPurchaseSummary($voucherList[0]['id']);
    $result['detailList'] = [];
    $beforeVoucherNumber = '';
    foreach ($detailList as $detail) {
      $voucherNumber = '';
      $accountName = '';
      if ($beforeVoucherNumber !== $detail['伝票番号']) {
        $voucherNumber = $detail['伝票番号'];
        $beforeVoucherNumber = $voucherNumber;
        $accountName = $detail['発送先名'];
      }
      $displayDetail = [
        'voucherNumber' => $voucherNumber,
        'accountName' => $accountName,
        'sku' => $detail['商品コード'],
        'quantity' => $detail['受注数']
      ];
      $result['detailList'][] = $displayDetail;
    }
    return $result;
  }

  /**
   * 出荷伝票グループ梱包開始。
   *
   * パラメータで指定された出荷伝票グループの梱包開始処理を行う。
   * 出荷伝票グループのステータスを「梱包中」とし、梱包担当者IDを登録する。
   * さらに、紐づく出荷伝票梱包グループの中で最初の梱包開始の場合は、そちらも「処理中」に更新する。
   * 指定された出荷伝票グループのステータスが「梱包未処理」でない場合は、BusinessExceptionをthrowする。
   * 更新後に、更新情報を、連想配列で返却する。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $shippingVoucherId 出荷伝票グループID
   * @param int $userId ユーザID
   * @throws BusinessException 既に梱包が開始されていた
   * @return array 以下の2つのキーを持つ連想配列。
   *  'status' => int 出荷伝票グループのステータス
   *  'packingAccount' => string 梱包担当者名
   */
  public function packingStart($shippingVoucherId, $userId)
  {
    // 引数をもとに、IDに紐づく(1)梱包グループ・(2)出荷伝票グループについて、
    // (1)> (2) の順でロックを行い、逆順でステータスを更新する。

    /** @var TbShippingVoucherRepository $voucherRepo */
    $vRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    /** @var TbShippingVoucherPackingGroupRepository $groupRepo */
    $pgRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
    /** @var SymfonyUsersRepository $uRepo */
    $uRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');

    $em = $this->getDoctrine()->getManager('main');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    try {
      $dbMain->beginTransaction();

      $packingGroupId = $vRepo->find($shippingVoucherId)->getShippingVoucherPackingGroupId();
      $pgRepo->lockForUpdate(array($packingGroupId));
      $vRepo->lockForUpdate(array($shippingVoucherId));

      $rowCount = $vRepo->updateStatusAndPackingAccountId($shippingVoucherId, $userId);
      if ($rowCount === 0) {
        throw new BusinessException('既に梱包が開始されています。');
      }

      $pgStatus = $pgRepo->find($packingGroupId)->getStatus();
      if ($pgStatus === TbShippingVoucherPackingGroup::STATUS_NONE) {
        $pgRepo->updateStatusToOnGoing($packingGroupId);
      }
      $dbMain->commit();

      $em->clear();
      $voucher = $vRepo->find($shippingVoucherId);
      $updateInfo = [];
      $updateInfo['status'] = $voucher->getStatus();
      $packingAccountId = $voucher->getPackingAccountId();
      $updateInfo['packingAccount'] = $uRepo->find($packingAccountId)->getUsername();

      return $updateInfo;

    } catch (BusinessException $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
  }

  /**
   * 出荷伝票グループのステータスが梱包中(2)かつ出荷伝票梱包のステータスが未着手(0)の場合
   * 出荷伝票梱包のステータスを進行中に更新する。
   * @param int $warehouseId 倉庫ID
   * @param string $pickingListDate ピッキング日付
   * @param int $pickingListNumber ピッキング番号
   * @param int $voucherNumber 伝票番号
   * @param int $userId ユーザーID
   */
  public function updateShippingVoucherPackingStatus($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber, $userId)
  {
    $logger = $this->getLogger();

    /** @var TbShippingVoucherPackingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');

    $dbMain = $this->getDoctrine()->getConnection('main');
    try {
      // 出荷伝票梱包IDを取得
      $packingId = $repo->findIdByVoucherInfo($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber);

      // トランザクション開始
      $dbMain->beginTransaction();

      // ロック
      $repo->lockForUpdate([$packingId]);

      $packing = $repo->findStatusWithShippingVoucherStatus($packingId);

      if(intval($packing['voucherStatus']) === TbShippingVoucher::STATUS_PACKING && intval($packing['packingStatus']) === TbShippingVoucherPacking::STATUS_NONE) {
        $repo->updateStatus($packingId, TbShippingVoucherPacking::STATUS_PROCESSING, $userId);
      }

      // コミット
      $dbMain->commit();

    } catch (\Exception $e) {
      $logger->error('梱包処理 画面表示時のステータス更新: ' . $e->getTraceAsString());
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
  }

  /**
   * 出荷伝票情報取得。
   *
   * 引数をもとに、それに紐づく以下を連想配列として返却する。
   *  (1) 出荷伝票明細情報
   *  (2) 出荷伝票梱包情報
   *  (3) 出荷伝票グループ名（ex. 古-4）
   *    ※このメソッド内で情報の正規化などを行う。
   * @param int $warehouseId 倉庫ID
   * @param string $pickingListDate ピッキング日付
   * @param int $pickingListNumber ピッキング番号
   * @param int $voucherNumber 伝票番号
   * @return array 以下の3つのキーを持つ連想配列。IDに該当するものがなければnull
   *    'shippingVoucherDetail' => 出荷伝票明細情報
   *    'packing' => TbShippingVoucherPacking 出荷伝票梱包
   *    'shippingVoucherName' => string 出荷伝票グループ名
   */
  public function findShippingVoucher($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber)
  {
    $result = [];
    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    /** @var TbShippingVoucherRepository $voucherRepo */
    $voucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');

    $packingId = $packingRepo->findIdByVoucherInfo($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber);

    $list = $packingRepo->findWithDetail($packingId);
    $current = current($list);
    $isUpdatable = intval($current['voucherStatus']) === TbShippingVoucher::STATUS_PACKING; // 出荷伝票グループが「梱包中」の場合は更新可能
    $isChangableDelivery = in_array(intval($current['voucherStatus']), [TbShippingVoucher::STATUS_PACKING, TbShippingVoucher::STATUS_FINISHED]); // 出荷伝票グループが「梱包中」か「完了」の場合は更新可能
    $voucherId = intval($current['voucherId']);

    $result['shippingVoucherName'] = $current['symbol'] . '-' . $current['warehouseDailyNumber'];
    $voucherList = $voucherRepo->findWithSymbolAndPackingIds($current['voucherId']);
    $endVoucher = end($voucherList);
    $isLast = $endVoucher['packingId'] === $current['id'];
    $index = array_search($current['id'], array_column($voucherList, 'packingId'));
    $nextVoucherNumber = $isLast ? null : intval($voucherList[$index + 1]['voucherNumber']);
    $nextShippingAccountName = $isLast ? null : $voucherList[$index + 1]['shippingAccountName'];
    $packingGroupId = intval($current['packingGroupId']);
    $nextShippingVoucherList = array_filter($voucherRepo->findByGroupIdForPackingGroupIndex($packingGroupId), function($shippingVoucher) use($voucherId) {
      return $shippingVoucher['id'] !== $voucherId &&
      in_array(intval($shippingVoucher['status']), [TbShippingVoucher::STATUS_WAIT_PICKING, TbShippingVoucher::STATUS_UNPROCESSED_PACKAGING]);
    });
    $nextPickingListNumber = $nextShippingVoucherList ? current($nextShippingVoucherList)['warehouse_daily_number'] : ''; // TODO pickingListNumberでいけるかも


    $result['packing'] = [
      'id' => intVal($current['id']),
      'voucherNumber' => $current['voucherNumber'],
      'totalAmount' => array_sum(array_map('intval', array_column($list, 'requiredAmount'))),
      'shippingAccountName' => $current['shippingAccountName'],
      'notices' => $current['notices'],
      'labelReissueFlg' => boolval($current['labelReissueFlg']),
      'deliveryName' => $current['deliveryName'],
      'isValidInquiryNumberStatusExist' => intval($current['validInquiryNumberStatus']) === TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_EXIST,
      'updated' => $current['updated'],
      'isWaitShippingStop' => intval($current['packingStatus']) === TbShippingVoucherPacking::STATUS_WAIT_SHIPPING_STOP,
      'isShippingStop' => intval($current['packingStatus']) === TbShippingVoucherPacking::STATUS_SHIPPING_STOP,
      'isLast' => $isLast,
      'nextVoucherNumber' => $nextVoucherNumber,
      'nextShippingAccountName' => $nextShippingAccountName,
      'isUpdatable' => $isUpdatable,
      'voucherId' => $voucherId,
      'warehouseId' => intval($current['warehouseId']),
      'pickingListDate' => $current['pickingListDate'],
      'pickingListNumber' => intval($current['pickingListNumber']),
      'isDisplayDelivery' => intval($current['groupDeliveryMethodId']) === 0, // 梱包グループの配送方法IDが0なら画面上に表示する
      'pickingAccountName' => $current['pickingAccountName'],
      'isChangableDelivery' => $isChangableDelivery,
      'nextPickingListNumber' => $nextPickingListNumber,
      'packingGroupId' => $packingGroupId,
    ];
    $imageUrlParent = sprintf('//%s/images/', $this->getContainer()->getParameter('host_plusnao'));
    foreach ($list as $row) {
      $imageUrl = $row['variationImagePath'] ? $row['variationImagePath']
        : TbMainproductsRepository::createImageUrl($row['imageDir'], $row['imageFile'], $imageUrlParent);
        $locationProductDetailUrl = $this->getContainer()->get('router')->generate('location_product_detail', ['syohinCode' => $row['skucode']]);
      $result['shippingVoucherDetail'][] = [
        'id' => $row['detailId'],
        'skucode' => $row['skucode'],
        'imageUrl' => $imageUrl, // '//d2-f.dev.plusnao.co.jp/images/itempic0688/a-zak-21642.jpg',
        'isOk' => intVal($row['detailStatus']) === 1,
        'isHold' => intVal($row['detailStatus']) === 2,
        'isShortage' =>intVal($row['detailStatus']) === 3,
        'requiredAmount' => intVal($row['requiredAmount']),
        'assignNum' => intVal($row['assignNum']),
        'updated' => $row['detailUpdated'],
        'isAbleShortage' => intval($row['emptyLocationCount']) > 0, // locationが空のものがあれば不足の可能性あり
        'locationProductDetailUrl' => $locationProductDetailUrl,
      ];
    }
    return $result;
  }

  /**
   * 紐づくtb_delivery_split_ruleがあるデータを取得
   * @return array 配送方法のIDとnameの連想配列を返す。
   */
  public function findDeliveryMethodList() {
    /** @var TbDeliveryMethodRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryMethod');
    $deliveryMethodAll = $repo->findForPacking();
    $result = [];
    foreach ($deliveryMethodAll as $deliveryMethod) {
      $result[] = [
        'id' => $deliveryMethod['deliveryId'],
        'name' => $deliveryMethod['deliveryName']
      ];
    }
    return $result;
  }

  /**
   * 出荷伝票明細データOK状態更新処理。
   *
   * 引数をもとに、対象の明細のステータスを更新する。
   * 他の人がステータスを更新していた場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $id 出荷伝票明細ID
   * @param boolean $isPackingOk false:OK/ true:保留
   * @param string $detailUpdated 出荷伝票明細の更新日時
   * @throws BusinessException 既に明細が更新されていた
   * @return array 以下のキーを持つ連想配列。
   *    'updated'    => 出荷伝票明細の更新日
   *    'isHold'     => 保留ステータスかどうか
   *    'isShortage' => 不足ステータスかどうか
   *    'isOk'       => OKステータスかどうか
   */
  public function updateDetailOk($id, $isPackingOk, $detailUpdated)
  {
    $status = $isPackingOk ? 1 : 0; // $isPackingOkがtrueなら1(OK)、falseなら0(登録済み)をセットする
    return $this->updateDetailStatusProcess($id, $status, $detailUpdated);
  }
  /**
   * 出荷伝票明細データ保留状態更新処理。
   *
   * 引数をもとに、対象の明細のステータスを更新する。
   * 他の人がステータスを更新していた場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $id 出荷伝票明細ID
   * @param boolean $isPackingHold false:登録済み/ true:保留
   * @param string $detailUpdated 出荷伝票明細の更新日時
   * @throws BusinessException 既に明細が更新されていた
   * @return array 以下のキーを持つ連想配列。
   *    'updated'    => 出荷伝票明細の更新日
   *    'isHold'     => 保留ステータスかどうか
   *    'isShortage' => 不足ステータスかどうか
   *    'isOk'       => OKステータスかどうか
   */
  public function updateDetailHold($id, $isPackingHold, $detailUpdated)
  {
    $status = $isPackingHold ? 2 : 0; // $isPackingHoldがtrueなら2(梱包保留)、falseなら0(登録済み)をセットする
    return $this->updateDetailStatusProcess($id, $status, $detailUpdated);
  }

  /**
   * 出荷伝票明細データステータス更新処理。
   *
   * 引数をもとに、対象の明細のステータスを更新する。
   * ステータスを1(梱包OK)に更新する際には梱包割り当て数を受注数と同じ値に更新する
   * 他の人がステータスを更新していた場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $id 出荷伝票明細ID
   * @param int $status ステータス
   * @param string $detailUpdated 出荷伝票明細の更新日時
   * @throws BusinessException 既に明細が更新されていた
   * @return array 以下のキーを持つ連想配列。
   *    'updated'    => 出荷伝票明細の更新日
   *    'isHold'     => 保留ステータスかどうか
   *    'isShortage' => 不足ステータスかどうか
   *    'isOk'       => OKステータスかどうか
   */
  private function updateDetailStatusProcess($id, $status, $detailUpdated)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDoctrine()->getConnection('main');
    $result = [];

    try {
      // トランザクション開始
      $dbMain->beginTransaction();

      // ロック
      $this->detailLockForUpdate([intval($id)]);

      // tb_shipping_voucher_detailはRepositoryもEntityもないのでqueryをServiceに記載する
      // tb_shipping_voucher_detail取得
      $sql = <<<EOD
        SELECT
            id
            , 受注数 AS requiredAmount
            , updated
        FROM
          tb_shipping_voucher_detail
        WHERE
          id = :id;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
      $stmt->execute();
      $detail = $stmt->fetch(\PDO::FETCH_ASSOC);

      // 更新日が異なる場合、他で更新されている可能性があるためエラー出力
      if ($detail['updated'] != $detailUpdated) {
        throw new BusinessException('既に更新されています。');
      }

      // ステータスの更新
      $this->updateDetailStatus($id, $status);
      // OKへ更新の場合は梱包割り当て数も更新する
      if ($status === 1) {
        $this->updateDetailAssignNum($id, intval($detail['requiredAmount']));
      }

      // 画面上の出荷伝票明細の更新日を更新するため出荷伝票明細取得
      $sql = <<<EOD
        SELECT
            id
            , updated
        FROM
          tb_shipping_voucher_detail
        WHERE
          id = :id;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
      $stmt->execute();

      $updatedDetail = $stmt->fetch(\PDO::FETCH_ASSOC);
      $result['updated'] = $updatedDetail['updated'];
      $result = [
        'updated' => $updatedDetail['updated'],
        'isHold' => $status === 2,
        'isShortage' => $status === 3,
        'isOk' => $status === 1,
      ];

      // コミット
      $dbMain->commit();
    } catch (BusinessException $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    } catch (\Exception $e) {
      $logger->error('梱包処理 保留ステータス変更時にエラー発生: ' . $e->getTraceAsString());
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
    return $result;
  }

  /**
   * 出荷伝票明細データ不足状態更新処理。
   *
   * 引数をもとに、対象の明細の梱包割り当て数と、ステータスを更新する。
   * 他の人が梱包割り当て数を更新していた場合は、BusinessExceptionをthrowする。
   * また、不要お問い合わせ番号にレコードを追加する。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $packingId 出荷伝票梱包ID
   * @param int $id 出荷伝票明細ID
   * @param int $assignNum 確保件数
   * @param string $inquiryNumber お問い合わせ番号
   * @param string $detailUpdated 出荷伝票明細の更新日時
   * @param string $packingUpdated 出荷伝票梱包の更新日時
   * @param int $userId ユーザーID
   * @throws BusinessException 既に明細が更新されていた
   * @return array 以下のキーを持つ連想配列。
   *    'updated'    => 出荷伝票明細の更新日
   *    'isHold'     => 保留ステータスかどうか
   *    'isShortage' => 不足ステータスかどうか
   *    'isOk'       => OKステータスかどうか
   */
  public function updateDetailShortage($packingId, $detailId, $assignNum, $inquiryNumber, $detailUpdated, $packingUpdated, $userId)
  {
    $logger = $this->getLogger();

    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    /** @var TbShippingVoucherNoneedInquiryNumberRepository $inquiryNumberRepo */
    $inquiryNumberRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherNoneedInquiryNumber');
    /** @var TbShippingReissueLabelRepository $labelRepo */
    $labelRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingReissueLabel');

    $dbMain = $this->getDoctrine()->getConnection('main');
    $em = $this->getDoctrine()->getManager('main');
    $result = [];

    try {
      $labelListForLock = $labelRepo->findBy(['shippingVoucherPackingId' => $packingId, 'status' => TbShippingReissueLabel::STATUS_UNISSUED], ['id' => 'ASC']);
      $labelIdList = array_map(function($labelForLock){
        return $labelForLock->getId();
      }, $labelListForLock);

      // トランザクション開始
      $dbMain->beginTransaction();
      // ロック
      $packingRepo->lockForUpdate([$packingId]);
      if (count($labelIdList) > 0) {
        $labelRepo->lockForUpdate($labelIdList);
      }
      $em->clear();
      $this->detailLockForUpdate([intval($detailId)]);

      // tb_shipping_voucher_detailはRepositoryもEntityもないのでqueryをServiceに記載する
      // tb_shipping_voucher_detail取得
      $sql = <<<EOD
      SELECT
          id
          , updated
          , 受注数 AS requiredAmount
      FROM
        tb_shipping_voucher_detail
      WHERE
        id = :id;
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $detailId, \PDO::PARAM_INT);
      $stmt->execute();
      $detail = $stmt->fetch(\PDO::FETCH_ASSOC);

      // 更新日が異なる場合、他で更新されている可能性があるためエラー出力
      if ($detail['updated'] != $detailUpdated) {
        throw new BusinessException('既に更新されています。');
      }

      $status = intval($detail['requiredAmount']) === intval($assignNum) ? 0 : 3;
      $sql = <<<EOD
      UPDATE tb_shipping_voucher_detail
      SET
          status = :status
          , assign_num = :assignNum
      WHERE
          id = :id;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':assignNum', $assignNum, \PDO::PARAM_INT);
      $stmt->bindValue(':id', $detailId, \PDO::PARAM_INT);
      $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
      $stmt->execute();

      // 有効なお問い合わせ番号ステータスの値による処理
      $packing = $packingRepo->findWithDeliveryMethod($packingId);
      switch (intval($packing['validInquiryNumberStatus'])) {
        case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE: // 有効なお問い合わせ番号がない
          // 何もしない
        case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_WAIT: // ラベル再発行待ち
          // 何もしない
          break;
        case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_EXIST: // 有効なお問い合わせ番号がある
          if ($inquiryNumber) {
            // 不要お問い合わせ番号テーブルに登録
            $inquiryNumberRepo->insertInquiryNumber($packingId, $packing['deliveryMethodId'], $inquiryNumber, $userId);
          } else {
            throw new BusinessException('お問い合わせ番号は必須です。');
          }
          break;
        default:
          // 想定外のステータスなのでエラーを投げる
          $logger->error('有効なお問い合わせ番号ステータスの値が不適切 packingId: ' . $packingId . ' validInquiryNumberStatus: ' . $packing['validInquiryNumberStatus']);
          throw new \RuntimeException('想定外のステータスのため更新できません。');
          break;
      }
      // 発送ラベル再発行伝票テーブルの「未発行」のデータを「削除」に更新
      $labels = $labelRepo->findBy(['shippingVoucherPackingId' => $packingId, 'status' => TbShippingReissueLabel::STATUS_UNISSUED], ['id' => 'ASC']);
      foreach ($labels as $label) {
        $label->setStatus(TbShippingReissueLabel::STATUS_DELETE);
      }
      $em->flush();

      if (intval($packing['validInquiryNumberStatus']) !== TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE) {
        // 出荷伝票梱包の有効なお問い合わせ番号ステータスが0(有効なお問い合わせ番号がない)に更新
        $updatedCount = $packingRepo->updateValidInquiryNumberStatus($packingId, TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE, $packingUpdated, $userId);
        if ($updatedCount === 0) {
          throw new BusinessException('既に更新されています。');
        }
      }

      // 画面上の出荷伝票明細の更新日を更新するため出荷伝票明細取得
      $sql = <<<EOD
      SELECT
          id
          , updated
      FROM
        tb_shipping_voucher_detail
      WHERE
        id = :id;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $detailId, \PDO::PARAM_INT);
      $stmt->execute();

      $updatedDetail = $stmt->fetch(\PDO::FETCH_ASSOC);
      $updatedPacking = $packingRepo->findUpdated($packingId);
      $result = [
        'updated' => $updatedDetail['updated'],
        'isHold' => $status === 2,
        'isShortage' => $status === 3,
        'isOk' => $status === 1,
        'packingUpdated' => $updatedPacking['updated']
      ];

      // コミット
      $dbMain->commit();
    } catch (BusinessException $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
    return $result;
  }

  /**
   * 出荷伝票発送方法変更処理。
   *
   * 引数をもとに、出荷伝票梱包の発送方法を更新する。
   * 他の人が発送方法を更新していた場合は、BusinessExceptionをthrowする。
   * また、発送ラベル再発行伝票及び不要お問い合わせ番号にレコードを追加する。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $packingId 出荷伝票梱包ID
   * @param int $deliveryMethodId 配送方法ID
   * @param string $inquiryNumber お問い合わせ番号
   * @param string $packingUpdated 出荷伝票梱包の更新日時
   * @param int $userId ユーザーID
   * @throws BusinessException 既に発送方法が更新されていた
   * @return array 以下のキーを持つ連想配列。
   *    'updated' => 出荷伝票梱包の更新日
   */
  public function changeDeliveryMethod($packingId, $deliveryMethodId, $inquiryNumber, $packingUpdated, $userId)
  {
    $logger = $this->getLogger();

    /** @var TbShippingVoucherPackingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    /** @var TbShippingVoucherNoneedInquiryNumberRepository $inquiryNumberRepo */
    $inquiryNumberRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherNoneedInquiryNumber');
    /** @var TbShippingReissueLabelRepository $labelRepo */
    $labelRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingReissueLabel');
    /** @var TbDeliveryMethodRepository $deliveryRepo */
    $deliveryRepo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryMethod');

    $em = $this->getDoctrine()->getManager('main');
    $dbMain = $this->getDoctrine()->getConnection('main');
    $result = [];
    try {
      $labelListForLock = $labelRepo->findBy(['shippingVoucherPackingId' => $packingId, 'status' => TbShippingReissueLabel::STATUS_UNISSUED], ['id' => 'ASC']);
      $labelIdList = array_map(function($labelForLock){
        return $labelForLock->getId();
      }, $labelListForLock);

      // トランザクション開始
      $dbMain->beginTransaction();
      // ロック
      $repo->lockForUpdate([$packingId]);
      if (count($labelIdList) > 0) {
        $labelRepo->lockForUpdate($labelIdList);
      }
      $em->clear();

      // 処理
      $packing = $repo->findWithDeliveryMethod($packingId);
      switch (intval($packing['validInquiryNumberStatus'])) {
        case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE: // 有効なお問い合わせ番号がない
          // 何もしない
        case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_WAIT: // ラベル再発行待ち
          // 何もしない
          break;
        case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_EXIST: // 有効なお問い合わせ番号がある
          if ($inquiryNumber) {
            // 不要お問い合わせ番号テーブルに登録
            $inquiryNumberRepo->insertInquiryNumber($packingId, $packing['deliveryMethodId'], $inquiryNumber, $userId);
          } else {
            throw new BusinessException('お問い合わせ番号は必須です。');
          }
          break;
        default:
          // 想定外のステータスなのでエラーを投げる
          $logger->error('有効なお問い合わせ番号ステータスの値が不適切 packingId: ' . $packingId . ' validInquiryNumberStatus: ' . $packing['validInquiryNumberStatus']);
          throw new \RuntimeException('想定外のステータスのため更新できません。');
          break;
      }
      // 発送ラベル再発行伝票テーブルの「未発行」のデータを「削除」に更新
      $labels = $labelRepo->findBy(['shippingVoucherPackingId' => $packingId, 'status' => TbShippingReissueLabel::STATUS_UNISSUED], ['id' => 'ASC']);
      foreach ($labels as $label) {
        $label->setStatus(TbShippingReissueLabel::STATUS_DELETE);
      }
      $em->flush();

      // 発送ラベル再発行伝票テーブルに登録
      $labelRepo->insertReissueLabel($packingId, $deliveryMethodId, $userId);

      // 発送方法と有効なお問い合わせ番号ステータスを更新
      $delivery = $deliveryRepo->find($deliveryMethodId);
      $updatedCount = 0;
      if ($delivery->getInquiryNumberNeedFlg()) {
        // お問い合わせ番号が必要な発送方法の場合有効なお問い合わせ番号ステータスを「ラベル再発行待ち」に更新
        $updatedCount = $repo->updateDeliveryMethodAndValidInquiryNumberStatus($packingId, $deliveryMethodId, TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_WAIT, $packingUpdated, $userId);
      } else {
        // お問い合わせ番号が不要な発送方法の場合有効なお問い合わせ番号ステータスを「有効なお問い合わせ番号がない」に更新
        $updatedCount = $repo->updateDeliveryMethodAndValidInquiryNumberStatus($packingId, $deliveryMethodId, TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE, $packingUpdated, $userId);
      }
      if ($updatedCount === 0) {
        throw new BusinessException('既に更新されています。');
      }

      // 画面上の出荷伝票梱包の更新日を更新するため出荷伝票梱包取得
      $updatedPacking = $repo->findUpdated($packingId);
      $result['updated'] = $updatedPacking['updated'];

      // コミット
      $dbMain->commit();

    } catch (BusinessException $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
    return $result;
  }

  /**
   * 出荷伝票梱包完了。
   *
   * パラメータで指定された出荷伝票梱包の完了処理を行う。
   * 出荷伝票梱包のステータスを、出荷伝票明細の状態を元に更新し、
   * さらに、紐づく出荷伝票グループの中で最後の梱包完了の場合は、そちらも「完了」に更新する。
   * 指定された出荷伝票グループのステータスが「梱包中」でない場合は、BusinessExceptionをthrowする。
   * さらに、紐づく梱包グループの中で最後の梱包完了の場合は、そちらも「完了」に更新する。
   * 指定された梱包グループのステータスが「処理中」でない場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う。
   * @param int $packingId 出荷伝票梱包ID
   * @param string $packingUpdated 出荷伝票梱包の更新日時
   * @param int $userId ユーザーID
   * @param boolean $stopFlg 出荷STOPにするか否か
   * @param string $inquiryNumber お問い合わせ番号
   * @throws BusinessException 既に梱包ステータスが更新されていた
   */
  public function updateShippingVoucherComplete($packingId, $packingUpdated, $userId, $stopFlg, $inquiryNumber)
  {
    /** @var TbShippingVoucherPackingGroupRepository $groupRepo */
    $groupRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
    /** @var TbShippingVoucherRepository $voucherRepo */
    $voucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    /** @var TbShippingReissueLabelRepository $labelRepo */
    $labelRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingReissueLabel');

    $dbMain = $this->getDoctrine()->getConnection('main');
    try {

      // ロックのためにID取得
      $ids = $packingRepo->findPackingRelationId($packingId);
      $currentIds = current($ids);
      $labelIds = [];
      $detailIds = [];
      if ($stopFlg) {
        $labelIds = array_column($ids, 'labelId');
      } else {
        $detailIds = array_column($ids, 'detailId');
      }

      // トランザクション開始
      $dbMain->beginTransaction();

      // ロック
      $groupRepo->lockForUpdate([intVal($currentIds['groupId'])]);
      $voucherRepo->lockForUpdate([intVal($currentIds['voucherId'])]);
      $packingRepo->lockForUpdate([intVal($currentIds['packingId'])]);
      if ($stopFlg) {
        if ($labelIds[0] !== null) {
          $labelRepo->lockForUpdate($labelIds);
        }
      } else {
        $this->detailLockForUpdate($detailIds);
      }

      $packing = $packingRepo->findUpdated($packingId);
      // 更新日が異なる場合、他で更新されている可能性があるためエラー出力
      if ($packing['updated'] != $packingUpdated) {
        throw new BusinessException('既に更新されています。');
      }

      if ($stopFlg) {
        // 発送お問い合わせ番号関連・出荷伝票梱包の更新処理
        $deliveryId = $packingRepo->find($packingId)->getLatestDeliveryMethodId();
        $validInquiryNumberStatus = $packingRepo->find($packingId)->getValidInquiryNumberStatus();
        $this->updateshippingInquiryNumberAndPackingForStop($labelIds, $deliveryId, $inquiryNumber, $validInquiryNumberStatus, intVal($currentIds['packingId']), $userId);
      } else {
        // 出荷伝票明細・出荷伝票梱包の更新処理
        $this->updateDetailAndPackingForComplete($detailIds, intval($currentIds['packingId']), $userId);
      }

      // 出荷伝票グループの更新処理
      $voucherStatus = $this->calcShippingVoucherStatus(intVal($currentIds['voucherId']));
      if ($voucherStatus === TbShippingVoucher::STATUS_FINISHED) {
        $voucherRepo->updateStatus(intVal($currentIds['voucherId']), $voucherStatus);
      }
      // 梱包グループの更新処理
      $groupStatus = $this->calcPackingGroupStatus(intVal($currentIds['groupId']));
      if ($groupStatus === TbShippingVoucherPackingGroup::STATUS_DONE) {
        $groupRepo->updateStatus(intVal($currentIds['groupId']), $groupStatus);
      }

      // コミット
      $dbMain->commit();
    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
  }

  /**
   * 梱包グループグループ化情報取得。
   *
   * 引数をもとに、梱包グループから以下の情報をそれぞれユニーク化したリストとして返却する。
   * （紐づく出荷伝票グループのピッキングリスト日付が本日のものに限る。）
   * @param int $warehouseId 倉庫ID
   * @return array 以下の2つのキーを持つ連想配列。IDに該当するものがなければnull
   *    'nameList' => array 梱包グループ名
   *    'commentList' => array 梱包グループコメント
   */
  public function findPackingGroupGroupingList($warehouseId)
  {
    $result = [
      'nameList' => []
    , 'commentList' => []
    ];

    $today = (new \DateTime())->setTime(0, 0, 0);

    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
      SELECT
        g.name
        , g.packing_comment
      FROM
        tb_shipping_voucher_packing_group g
        INNER JOIN tb_shipping_voucher v
          ON g.id = v.shipping_voucher_packing_group_id
      WHERE
        v.warehouse_id = :warehouseId
        AND v.picking_list_date = :today
      ORDER BY
        g.id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouseId, \PDO::PARAM_INT);
    $stmt->bindValue(':today', $today->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $result['nameList'] = array_column($list, 'name');
    $result['commentList'] = array_unique(array_column($list, 'packing_comment'));

    return $result;
  }

  /**
   * 梱包グループ名を取得する。
   *
   * 引数をもとに、倉庫略称、倉庫連番、発送方法を参照して、
   * 梱包グループの新しい名前を返却。
   * @param int $packingGroupId 梱包グループID
   * @return string 梱包グループの新しい名前
   */
  public function calcPackingGroupName($packingGroupId)
  {
    /** @var TbShippingVoucherRepository $svRepo */
    $svRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    /** @var TbWarehouseRepository $wRepo */
    $wRepo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbShippingVoucherPackingGroupRepository $pgRepo */
    $pgRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
    /** @var TbDeliveryMethodRepository $dmRepo */
    $dmRepo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryMethod');

    $shippingVouchers = $svRepo->findBy(
      array('shippingVoucherPackingGroupId' => $packingGroupId)
      , array('picking_list_number' => 'ASC')
    );

    $symbol = $wRepo->find($shippingVouchers[0]->getWarehouseId())->getSymbol();
    $minWarehouseDailyNumber = $shippingVouchers[0]->getWarehouseDailyNumber();
    $maxWarehouseDailyNumber = $shippingVouchers[count($shippingVouchers) - 1]->getWarehouseDailyNumber();
    $packingGroupId = $shippingVouchers[0]->getShippingVoucherPackingGroupId();
    $deliveryMethodId = $pgRepo->find($packingGroupId)->getDeliveryMethodId();
    $deliveryMethod = $dmRepo->findOneBy(['deliveryId' => $deliveryMethodId]);
    $deliveryMethodName = $deliveryMethod ? $deliveryMethod->getDeliveryName() : '';
    $name = $symbol.' '.$minWarehouseDailyNumber.'-'.$maxWarehouseDailyNumber.' '.$deliveryMethodName;

    return $name;
  }

  /**
   * 梱包グループステータスを取得する。
   *
   * 引数をもとに、紐づく出荷伝票グループのステータスを参照して、
   * 梱包グループの新しいステータスを返却。
   * @param int $packingGroupId 梱包グループID
   * @return int 梱包グループの新しいステータス
   */
  public function calcPackingGroupStatus($packingGroupId)
  {
    /** @var TbShippingVoucherRepository $svRepo */
    $svRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');

    $shippingVouchers = $svRepo->findBy(
      array('shippingVoucherPackingGroupId' => $packingGroupId)
      , array('picking_list_number' => 'ASC')
    );
    $statusList = array_map(function($shippingVoucher) {
      return $shippingVoucher->getStatus();
    }, $shippingVouchers);
    $status = '';
    if (min($statusList) === TbShippingVoucher::STATUS_FINISHED) {
      $status = TbShippingVoucherPackingGroup::STATUS_DONE;
    } elseif (max($statusList) <= TbShippingVoucher::STATUS_UNPROCESSED_PACKAGING) {
      $status = TbShippingVoucherPackingGroup::STATUS_NONE;
    } else {
      $status = TbShippingVoucherPackingGroup::STATUS_ONGOING;
    }
    return $status;
  }

  /**
   * 出荷伝票グループステータスを取得する。
   *
   * 引数をもとに、紐づく出荷伝票梱包のステータスを参照して、
   * 出荷伝票グループの新しいステータスを返却する。
   * @param int $shippingVoucherId 出荷伝票グループID
   * @return int 出荷伝票グループの新しいステータス
   */
  private function calcShippingVoucherStatus($shippingVoucherId)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
        SELECT
            p.status
        FROM
            tb_shipping_voucher v
            INNER JOIN tb_shipping_voucher_packing p
                ON v.id = p.voucher_id
        WHERE
            v.id = :shippingVoucherId;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingVoucherId', $shippingVoucherId, \PDO::PARAM_INT);
    $stmt->execute();
    $statusList = array_map(function($detail) {
      return intval($detail['status']);
    }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    $resultStatus = 0;
    // ステータスがOK、不足、出荷STOP以外の配列
    $statusNotOkList = array_filter($statusList, function($status) {
      return !in_array($status, [TbShippingVoucherPacking::STATUS_OK, TbShippingVoucherPacking::STATUS_SHORTAGE, TbShippingVoucherPacking::STATUS_SHIPPING_STOP], true);
    });
    if (count($statusNotOkList) === 0) {
      // ステータスがOK、不足、出荷STOPのみなら完了ステータス
      $resultStatus = TbShippingVoucher::STATUS_FINISHED;
    } else {
      $resultStatus = TbShippingVoucher::STATUS_PACKING;
    }
    return $resultStatus;
  }

  /**
   * 出荷伝票梱包のステータスを取得する。
   *
   * 引数をもとに、紐づく出荷伝票明細のステータスを参照して、
   * 出荷伝票梱包の新しいステータスを返却する。
   * @param int $packingId 出荷伝票梱包ID
   * @return int 出荷伝票梱包の新しいステータス
   */
  private function calcShippingVoucherPackingStatus($packingId)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
        SELECT
            d.status
        FROM
            tb_shipping_voucher_packing p
            INNER JOIN tb_shipping_voucher_detail d
                ON p.voucher_id = d.voucher_id
                AND d.伝票番号 = CAST(p.voucher_number AS CHAR)
        WHERE
            p.id = :packingId;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':packingId', $packingId, \PDO::PARAM_INT);
    $stmt->execute();
    $statusList = array_map(function($detail) {
      return intval($detail['status']);
    }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    $status = 0;
    if (in_array(0, $statusList)) {
      // 登録済み(0)のステータスがある場合
      $status = TbShippingVoucherPacking::STATUS_PROCESSING;
    } elseif(in_array(3, $statusList)) {
      // 梱包不足(3)のステータスがある場合
      $status = TbShippingVoucherPacking::STATUS_SHORTAGE;
    } elseif (in_array(2, $statusList)) {
      // 梱包保留(2)のステータスがある場合
      $status = TbShippingVoucherPacking::STATUS_ON_HOLD;
    } else {
      // すべてのステータスが梱包OK(1)の場合
      $status = TbShippingVoucherPacking::STATUS_OK;
    }
    return $status;
  }

  /**
   * 出荷伝票明細の更新のため引数のIDのレコードをロックする。
   * @param array $ids 出荷伝票明細IDの配列
   */
  private function detailLockForUpdate($ids)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      SELECT
        *
      FROM
        tb_shipping_voucher_detail
      WHERE
        id IN ({$idsStr}) FOR UPDATE;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }
  /**
   * 出荷伝票明細のステータスを更新する
   * @param int $id 出荷伝票明細ID
   * @param int $status 出荷伝票明細のステータス
   */
  private function updateDetailStatus($id, $status)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
        UPDATE tb_shipping_voucher_detail
        SET
            status = :status
        WHERE
            id = :id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 出荷伝票明細の梱包割り当て数を更新する
   * @param int $id 出荷伝票明細ID
   * @param int $assignNum 梱包割り当て数
   */
  private function updateDetailAssignNum($id, $assignNum)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
        UPDATE tb_shipping_voucher_detail
        SET
          assign_num = :assignNum
        WHERE
          id = :id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':assignNum', $assignNum, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 出荷伝票明細と出荷伝票梱包の完了処理を行う。
   *
   * @param array $detailIds 出荷伝票明細IDの配列
   * @param int $packingId 出荷伝票梱包ID
   * @param int $userId ユーザーID
   */
  private function updateDetailAndPackingForComplete($detailIds, $packingId, $userId)
  {
    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');

    $dbMain = $this->getDoctrine()->getConnection('main');

    // 出荷伝票明細の全ステータスを取得
    $detailIdsStr = implode(', ', $detailIds);
    $sql = <<<EOD
      SELECT
        id
        , status
        , 受注数 AS requiredAmount
      FROM
        tb_shipping_voucher_detail
      WHERE
        id IN ({$detailIdsStr});
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $details = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    // 出荷伝票明細の更新処理
    foreach ($details as $detail) {
      if (intval($detail['status']) === 0) {
        // 梱包OKのステータスに更新する
        $this->updateDetailStatus(intval($detail['id']), 1);
        $this->updateDetailAssignNum(intval($detail['id']), intval($detail['requiredAmount']));
      }
    }

    $packingStatus = $this->calcShippingVoucherPackingStatus($packingId);
    // ・ステータスを「OK」に更新 かつお問い合わせ番号が必要な配送 かつ有効なお問い合わせ番号ステータスがない場合はエラー
    $checkPacking = $packingRepo->findWithDeliveryMethod($packingId);
    if ($packingStatus === TbShippingVoucherPacking::STATUS_OK &&
      boolval($checkPacking['inquiryNumberNeedFlg']) &&
      intval($checkPacking['validInquiryNumberStatus']) === TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE) {
        throw new BusinessException("発送方法の変更が必要です。");
    }

    // 出荷伝票梱包の更新処理
    $packingRepo->updateStatus($packingId, $packingStatus, $userId);
  }

  /**
   * 発送お問い合わせ番号関連と出荷伝票梱包の出荷STOP処理を行う。
   *
   * @param array $labelIds 発送ラベル再発行伝票IDの配列
   * @param int $deliveryId 発送方法ID
   * @param string $inquiryNumber お問い合わせ番号
   * @param int $validInquiryNumberStatus 有効なお問い合わせ番号ステータス
   * @param int $packingId 出荷伝票梱包ID
   * @param int $userId ユーザーID
   * @throws BusinessException お問い合わせ番号が入力されていない
   */
  private function updateshippingInquiryNumberAndPackingForStop($labelIds, $deliveryId, $inquiryNumber, $validInquiryNumberStatus, $packingId, $userId)
  {
    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    /** @var TbShippingReissueLabelRepository $labelRepo */
    $labelRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingReissueLabel');
    /** @var TbShippingVoucherNoneedInquiryNumberRepository $noneedRepo */
    $noneedRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherNoneedInquiryNumber');

    switch ($validInquiryNumberStatus) {
      case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_EXIST:
        if (empty($inquiryNumber)) {
          throw new BusinessException('お問い合わせ番号は必須です。');
        } else {
          // 不要お問い合わせ番号の登録
          $noneedRepo->insertInquiryNumber($packingId, $deliveryId, $inquiryNumber, $userId);
        }
        break;
      case TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_WAIT:
        // 配送ラベル再発行伝票テーブルの更新処理
        if ($labelIds[0] !== null) {
          $labelRepo->updateStatusOnlyUnissued($labelIds, TbShippingReissueLabel::STATUS_DELETE, $userId);
        }
        break;
    }

    // 出荷伝票梱包の更新処理
    $packingRepo->updateStatus($packingId, TbShippingVoucherPacking::STATUS_SHIPPING_STOP, $userId);
  }
}
