<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbWarehouse;


/**
 * TbSetProductPickingListRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbSetProductPickingListRepository extends BaseRepository
{
  // ピッキングステータス
  // 1: OK, 2: △, 3: PASS
  const PICKING_STATUS_NONE      = TbDeliveryPickingListRepository::PICKING_STATUS_NONE;
  const PICKING_STATUS_OK        = TbDeliveryPickingListRepository::PICKING_STATUS_OK;
  const PICKING_STATUS_INCORRECT = TbDeliveryPickingListRepository::PICKING_STATUS_INCORRECT;
  const PICKING_STATUS_PASS      = TbDeliveryPickingListRepository::PICKING_STATUS_PASS;


  /**
   * 作成済みピッキングリスト 1セット取得
   * @param string $date
   * @param integer $number
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getPickingList($date, $number)
  {
    $sql = <<<EOD
      SELECT
        l.*
      FROM tb_set_product_picking_list l
      WHERE l.`date` = :date
        AND l.number = :number
      ORDER BY l.picking_order ASC
             , l.current_location ASC
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * ピッキングリスト最終更新日時取得
   * @param string $date
   * @param int $number
   * @return \DateTimeImmutable|null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getLastUpdated($date, $number)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        MAX(l.updated) AS last_updated
      FROM tb_set_product_picking_list l
      WHERE l.date   = :date
        AND l.number = :number
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn(0);

    return $result ? new \DateTimeImmutable($result) : null;
  }

  /**
   * ピッキングリスト 1件取得
   * @param string $neSyohinSyohinCode
   * @param integer $locationId
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function findPickingList($neSyohinSyohinCode, $locationId)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        *
      FROM tb_set_product_picking_list l
      WHERE l.ne_syohin_syohin_code = :neSyohinSyohinCode
        AND l.location_id = :locationId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neSyohinSyohinCode', $neSyohinSyohinCode, \PDO::PARAM_STR);
    $stmt->bindValue(':locationId', $locationId, \PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $result;
  }


  /**
   * ピッキングリスト ロケーション更新
   * current_location を設定し、並び順を決める。
   *
   * @param string $date
   * @param integer $number
   * @param TbWarehouse $currentWarehouse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function refreshLocation($date, $number, $currentWarehouse)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE
      tb_set_product_picking_list picking
      INNER JOIN (
        SELECT
            picking.ne_syohin_syohin_code
          , MIN(pl.position)  AS min_position
        FROM tb_set_product_picking_list picking
        LEFT JOIN (
          tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
        ) ON picking.ne_syohin_syohin_code = pl.ne_syohin_syohin_code
         AND l.warehouse_id = :warehouseId
        GROUP BY picking.ne_syohin_syohin_code
      ) T ON picking.ne_syohin_syohin_code = T.ne_syohin_syohin_code
         AND picking.date   = :date
         AND picking.number = :number
      LEFT JOIN (
        tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
      ) ON T.ne_syohin_syohin_code = pl.ne_syohin_syohin_code
       AND l.warehouse_id = :warehouseId
       AND T.min_position = pl.position
      SET picking.current_location = COALESCE(l.location_code, '')
      /* WHERE picking.status IN ( :pickingStatusNone, :pickingStatusPass ) */ /* これは不要か */
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
    // $stmt->bindValue(':pickingStatusNone', self::PICKING_STATUS_NONE);
    // $stmt->bindValue(':pickingStatusPass', self::PICKING_STATUS_PASS);
    $stmt->execute();

    // 並び順更新
    $dbMain->exec("SET @i := 0");

    $sql = <<<EOD
      UPDATE tb_set_product_picking_list
      SET picking_order = CASE
                            WHEN current_location = '' THEN 999999
                            ELSE (@i := @i + 1)
                          END
      WHERE `date`   = :date
        AND number = :number
      ORDER BY current_location
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * ピッキング済み在庫数 一覧取得
   * @param string $date
   * @param int $number
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getPickedStocksTotal($date, $number)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
          l.ne_syohin_syohin_code
        , SUM(l.move_num) AS move_num
      FROM tb_set_product_picking_list l
      WHERE l.date = :date
        AND l.number = :number
        AND l.`status` = :pickingStatusOk
      GROUP BY l.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':pickingStatusOk', TbSetProductPickingListRepository::PICKING_STATUS_OK, \PDO::PARAM_INT);
    $stmt->execute();

    $result = [];
    foreach($stmt as $row) {
      $result[$row['ne_syohin_syohin_code']] = $row['move_num'];
    }

    return $result;
  }


}
