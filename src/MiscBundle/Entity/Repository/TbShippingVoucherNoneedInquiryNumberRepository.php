<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbShippingVoucherNoneedInquiryNumber;
use MiscBundle\Entity\TbShippingVoucherPacking;

/**
 * TbShippingVoucherNoneedInquiryNumberRepository
 */
class TbShippingVoucherNoneedInquiryNumberRepository extends BaseRepository
{
  /**
   * 不要お問い合わせ番号の登録
   * @param int $packingId 出荷伝票梱包ID
   * @param int $deliveryMethodId 発送方法ID
   * @param string $inquiryNumber お問い合わせ番号
   * @param int $accountId 更新ユーザID
   */
  public function insertInquiryNumber($packingId, $deliveryMethodId, $inquiryNumber, $accountId)
  {
    $em = $this->getEntityManager();
    $entity = new TbShippingVoucherNoneedInquiryNumber();
    $entity->setShippingVoucherPackingId($packingId);
    $entity->setDeliveryMethodId($deliveryMethodId);
    $entity->setStatus(TbShippingVoucherNoneedInquiryNumber::STATUS_UNREGISTERED);
    $entity->setInquiryNumber($inquiryNumber);
    $entity->setUpdateAccountId($accountId);
    $em->persist($entity);
    $em->flush();
  }

  /**
   * 更新のために指定IDのレコードをロック。
   *
   * @param array $ids 不要お問い合わせ番号IDのリスト
   */
  public function lockForUpdate($ids) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      SELECT
        *
      FROM
        tb_shipping_voucher_noneed_inquiry_number
      WHERE
        id IN ({$idsStr})
      FOR UPDATE;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  /**
   * 不要お問い合わせ番号リストを取得する。
   *
   * 倉庫ID・梱包グループ名・梱包グループコメント・伝票番号・入力未完了限定の有無、
   * を格納した連想配列をもとに、不要お問い合わせ番号のリストを連想配列で返却。
   * （当日分且つ、出荷伝票梱包ステータスがOK・商品不足・出荷STOPのいずれかのものに限る。更新日時順。）
   * @param array $conditions 検索条件の連想配列
   * @return array 以下のキーを持つ連想配列の配列。
   *    'id' => int 不要お問い合わせ番号ID
   *    'deliveryMethodId' => int 発送方法
   *    'status' => int 不要お問い合わせ番号ステータス
   *    'inquiryNumber' => int お問い合わせ番号
   *    'voucherNumber' => int 伝票番号
   *    'warehouseId' => int 倉庫ID
   *    'pickingListDate' => date ピッキングリスト日付
   *    'pickingListNumber' => int ピッキングリストNo.
   *    'productQuantity' => int 受注数合計
   *    'symbol' => string 倉庫略称
   *    'warehouseDailyNumber' => int 日別倉庫別連番
   */
  public function findNoneedListByConditions($conditions) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    // 検索条件が指定されていた場合、WHERE句追加。
    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    if ($conditions['packingGroupName'] !== '') {
      $addWheres[] = "g.name = :name";
      $addParams[':name'] = $conditions['packingGroupName'];
    }
    if ($conditions['packingGroupComment'] !== '') {
      $addWheres[] = "g.packing_comment = :comment";
      $addParams[':comment'] = $conditions['packingGroupComment'];
    }
    if ($conditions['voucherNumber'] !== '') {
      $addWheres[] = "p.voucher_number = :voucherNumber";
      $addParams[':voucherNumber'] = (int)$conditions['voucherNumber'];
    }
    if ($conditions['onlyIncompleteInput'] === true) {
      $addWheres[] = "n.status = :status";
      $addParams[':status'] = TbShippingVoucherNoneedInquiryNumber::STATUS_UNREGISTERED;
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }

    $today = (new \DateTime())->setTime(0, 0, 0);
    $statusList = [
      TbShippingVoucherPacking::STATUS_OK,
      TbShippingVoucherPacking::STATUS_SHORTAGE,
      TbShippingVoucherPacking::STATUS_SHIPPING_STOP
    ];
    $statusListStr = implode(', ', $statusList);

    $sql = <<<EOD
      SELECT
        n.id
        , n.delivery_method_id AS deliveryMethodId
        , n.status
        , n.inquiry_number AS inquiryNumber
        , p.voucher_number AS voucherNumber
        , v.warehouse_id AS warehouseId
        , v.picking_list_date AS pickingListDate
        , v.picking_list_number AS pickingListNumber
        , SUM(d.受注数) AS productQuantity
        , w.symbol
        , v.warehouse_daily_number AS warehouseDailyNumber
      FROM
        tb_shipping_voucher_noneed_inquiry_number n
        INNER JOIN tb_shipping_voucher_packing p
          ON n.shipping_voucher_packing_id = p.id
        INNER JOIN tb_shipping_voucher v
          ON p.voucher_id = v.id
        INNER JOIN tb_shipping_voucher_detail d
          ON v.id = d.voucher_id
          AND p.voucher_number = d.伝票番号
        INNER JOIN tb_shipping_voucher_packing_group g
          ON v.shipping_voucher_packing_group_id = g.id
        INNER JOIN tb_warehouse w
          ON v.warehouse_id = w.id
      WHERE
        v.warehouse_id = :warehouseId
        AND v.picking_list_date = :today
        AND p.status IN ({$statusListStr})
        {$addWhereSql}
      GROUP BY
        n.id
      ORDER BY
        n.id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $conditions['warehouseId'], \PDO::PARAM_INT);
    $stmt->bindValue(':today', $today->format('Y-m-d'), \PDO::PARAM_STR);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 指定IDの内、ステータスが「未登録」のレコードを「登録済み」に更新する。
   *
   * @param array $ids 不要お問い合わせ番号IDの配列
   * @param int $accountId ログインユーザID
   * @return int 更新件数
   */
  public function updateStatusToComplete($ids, $accountId) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_noneed_inquiry_number n
      SET
        n.status = :registered,
        n.update_account_id = :accountId
      WHERE
        n.id IN ({$idsStr})
      AND
        n.status = :unregistered;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':registered', TbShippingVoucherNoneedInquiryNumber::STATUS_REGISTERED, \PDO::PARAM_INT);
    $stmt->bindValue(':accountId', $accountId, \PDO::PARAM_INT);
    $stmt->bindValue(':unregistered', TbShippingVoucherNoneedInquiryNumber::STATUS_UNREGISTERED, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
  }
}