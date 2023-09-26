<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbShippingReissueLabel;
use MiscBundle\Entity\TbShippingVoucherPacking;

/**
 * TbShippingReissueLabelRepository
 */
class TbShippingReissueLabelRepository extends BaseRepository
{

  /**
   * 更新のため引数のIDのレコードをロックする。
   * @param array $ids 発送ラベル再発行伝票のID
   */
  public function lockForUpdate($ids)
  {
    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      SELECT
        *
      FROM
        tb_shipping_reissue_label
      WHERE
        id IN ({$idsStr}) FOR UPDATE;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->execute();
  }

  /**
   * 発送ラベル再発行伝票の登録
   * @param int $packingId 出荷伝票梱包ID
   * @param int $deliveryMethodId 発送方法ID
   * @param int $accountId 更新ユーザID
   */
  public function insertReissueLabel($packingId, $deliveryMethodId, $accountId)
  {
    $em = $this->getEntityManager();
    $entity = new TbShippingReissueLabel();
    $entity->setShippingVoucherPackingId($packingId);
    $entity->setDeliveryMethodId($deliveryMethodId);
    $entity->setStatus(TbShippingReissueLabel::STATUS_UNISSUED);
    $entity->setUpdateAccountId($accountId);
    $em->persist($entity);
    $em->flush();
  }

  /**
   * 配送ラベル再発行リストを取得する。
   *
   * 倉庫ID・梱包グループ名・梱包グループコメント・伝票番号・CSV未ダウンロード限定の有無、
   * を格納した連想配列をもとに、配送ラベル再発行伝票のリストを連想配列で返却。
   * （当日分且つ、出荷伝票梱包ステータスがOKのものに限る。更新日時順。）
   * @param array $conditions 検索条件の連想配列
   * @return array 以下のキーを持つ連想配列の配列。
   *    'id' => int 発送ラベル再発行伝票ID
   *    'shippingVoucherPackingId' => int 出荷伝票梱包ID
   *    'deliveryMethodId' => int 発送方法
   *    'status' => int 発送ラベル再発行伝票ステータス
   *    'voucherNumber' => int 伝票番号
   *    'warehouseId' => int 倉庫ID
   *    'pickingListDate' => date ピッキングリスト日付
   *    'pickingListNumber' => int ピッキングリストNo.
   *    'productQuantity' => int 受注数合計
   *    'symbol' => string 倉庫略称
   *    'warehouseDailyNumber' => int 日別倉庫別連番
   */
  public function findReissueListByConditions($conditions)
  {
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
    if ($conditions['onlyNotCsvDownload'] === true) {
      $addWheres[] = "r.status = :status";
      $addParams[':status'] = TbShippingReissueLabel::STATUS_UNISSUED;
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }

    $today = (new \DateTime())->setTime(0, 0, 0);

    $sql = <<<EOD
      SELECT
        r.id
        , r.shipping_voucher_packing_id AS shippingVoucherPackingId
        , r.delivery_method_id AS deliveryMethodId
        , r.status
        , p.voucher_number AS voucherNumber
        , v.warehouse_id AS warehouseId
        , v.picking_list_date AS pickingListDate
        , v.picking_list_number AS pickingListNumber
        , SUM(d.受注数) AS productQuantity
        , w.symbol
        , v.warehouse_daily_number AS warehouseDailyNumber
      FROM
        tb_shipping_reissue_label r
        INNER JOIN tb_shipping_voucher_packing p
          ON r.shipping_voucher_packing_id = p.id
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
        AND p.status = :ok
        AND p.label_reissue_flg <> 0
        {$addWhereSql}
      GROUP BY
        r.id
      ORDER BY
        r.id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $conditions['warehouseId'], \PDO::PARAM_INT);
    $stmt->bindValue(':today', $today->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':ok', TbShippingVoucherPacking::STATUS_OK, \PDO::PARAM_INT);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 引数をもとに、ステータスが「未発行」のものに限り、ステータスを更新する。
   *
   * @param array $ids 発送ラベル再発行伝票IDの配列
   * @param int $status 更新後ステータス
   * @param int $accountId ログインユーザID
   * @return int 更新件数
   */
  public function updateStatusOnlyUnissued($ids, $status, $accountId)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      UPDATE tb_shipping_reissue_label r
      SET
        r.status = :status,
        r.update_account_id = :accountId
      WHERE
        r.id IN ({$idsStr})
      AND
        r.status = :unIssued;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
    $stmt->bindValue(':accountId', $accountId, \PDO::PARAM_INT);
    $stmt->bindValue(':unIssued', TbShippingReissueLabel::STATUS_UNISSUED, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
  }
}