<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Entity\TbShippingVoucherPackingGroup;

/**
 * TbShippingVoucherPackingGroupRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbShippingVoucherPackingGroupRepository extends BaseRepository
{

  /**
   * コメント更新
   * @param int $id
   * @param String $comment
   */
  public function updateComment($id, $comment)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $em = $this->getEntityManager();
    $packing = $this->findOneBy(['id' => $id]);

    if ($packing) {
      $packing->setPackingComment($comment);
    }
    $em->flush();

  }

  /**
   * 統合したデータで、梱包グループを更新する。
   *
   * id指定した梱包グループについて、
   * マージの為に統合したデータで、更新する。
   * @param int $id
   * @param array $mergedDatas 以下3つのキーを持つ連想配列。
   *    'name' => string 梱包グループ名（例:「ム 1-4 ゆうパケット」）
   *    'status' => int ステータス
   *    'comment' => string コメント
   */
  public function updatePackingGroupWithMergedDatas($id, $mergedDatas)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $em = $this->getEntityManager();
    $packing = $this->findOneBy(['id' => $id]);

    if ($packing) {
      $packing->setName($mergedDatas['name']);
      $packing->setStatus($mergedDatas['status']);
      $packing->setPackingComment($mergedDatas['comment']);
    }
    $em->flush();
  }

  /**
   * ピッキングリストのコメント取得
   * @param int $number
   * @param string $date
   * @param int $warehouseId 対象倉庫ID
   * @return string コメント
   */
  public function getPickingListComment($number, $date, $warehouseId)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
        p.packing_comment
      FROM
        tb_shipping_voucher_packing_group p
        INNER JOIN tb_shipping_voucher v
          ON v.shipping_voucher_packing_group_id = p.id
      WHERE
        v.picking_list_number = :number
        AND v.picking_list_date = :date
        AND v.warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':warehouseId', $warehouseId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn(0);
  }

  /**
   * 梱包グループIDの配列を元にデータを削除する
   *
   * @param array $ids
   * @return void
   */
  public function deleteByIds($ids) {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $idsStr = implode(', ', $ids);
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
    DELETE
      p
    FROM
      tb_shipping_voucher_packing_group p
    WHERE
      p.id IN( {$idsStr} )
EOD;
  $stmt = $dbMain->prepare($sql);
  $stmt->execute();
  }

  /**
   * 梱包グループ一覧取得。
   *
   * 引数をもとに、一致する梱包グループのID、梱包グループ名、ステータスを配列で返却。
   * @param int $warehouseId 対象倉庫ID
   * @param string $fromDate この日付以降のデータを取得
   * @param boolean $isUnfinishOnly 0:すべて/ 1:未完了のみ
   * @return array TbShippingVoucherPackingGroup 梱包グループのリスト
   */
  public function findPackingGroupList($warehouseId, $fromDate, $isUnfinishOnly)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');
    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    if ($isUnfinishOnly) {
      $addWheres[] = "pg.status <> :status";
      $addParams[':status'] = TbShippingVoucherPackingGroup::STATUS_DONE;
    } else {
      $addWheres[] = "pg.status IS NOT NULL";
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }
    $sql = <<<EOD
      SELECT
        pg.id,
        pg.name,
        pg.status
      FROM
        tb_shipping_voucher_packing_group pg
        INNER JOIN tb_shipping_voucher v
          ON pg.id = v.shipping_voucher_packing_group_id
      WHERE
        v.warehouse_id = :warehouseId
        AND
          v.picking_list_date >= :fromDate
        {$addWhereSql}
      GROUP BY
        pg.id
      ORDER BY
        pg.id DESC;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouseId);
    $stmt->bindValue(':fromDate', $fromDate);
    foreach($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $list;
  }

  /**
   * 更新のために指定IDのレコードをロック。
   * @param array $ids
   */
  public function lockForUpdate($ids)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      SELECT
        *
      FROM
        tb_shipping_voucher_packing_group
      WHERE
        id IN ({$idsStr})
      FOR UPDATE;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  /**
   * 指定IDのステータスを処理中に更新する。
   * @param int $id 梱包グループID
   */
  public function updateStatusToOnGoing($id)
  {
    $em = $this->getEntityManager();
    $packingGroup = $this->find($id);
    $packingGroup->setStatus(TbShippingVoucherPackingGroup::STATUS_ONGOING);
    $em->flush();
  }

  /**
   * ステータスを更新する。
   * @param int $id 出荷伝票梱ID
   * @param int $status ステータス
   */
  public function updateStatus($id, $status)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_packing_group
      SET
        status = :status
      WHERE
        id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }
}