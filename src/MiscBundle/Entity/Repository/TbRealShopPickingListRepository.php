<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;

/**
 * TbRealShopPickingListRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbRealShopPickingListRepository extends BaseRepository
{
  const DEFAULT_NUMBER = 1; // 現状、ピッキングリストは1本のみ作成。番号固定。

  // ピッキングステータス
  // 1: OK, 2: △, 3: PASS
  const PICKING_STATUS_NONE      = TbDeliveryPickingListRepository::PICKING_STATUS_NONE;
  const PICKING_STATUS_OK        = TbDeliveryPickingListRepository::PICKING_STATUS_OK;
  const PICKING_STATUS_INCORRECT = TbDeliveryPickingListRepository::PICKING_STATUS_INCORRECT;
  const PICKING_STATUS_PASS      = TbDeliveryPickingListRepository::PICKING_STATUS_PASS;

  /**
   * 最終更新日時取得
   * @return \DateTimeImmutable|null
   */
  public function getLastUpdated()
  {
    $qb = $this->createQueryBuilder('l');
    $qb->select('MAX(l.updated) AS last_updated');
    $result = $qb->getQuery()->getResult();

    return isset($result[0]['last_updated']) ? new \DateTimeImmutable($result[0]['last_updated']) : null;
  }


  /**
   * 実店舗 ピッキングリスト更新
   * @param TbWarehouse $warehouse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function refreshRealShopPickingList($warehouse)
  {
    // 実店舗ピッキングリストの再作成
    $dbMain = $this->getConnection('main');

    $dbMain->query('DELETE FROM tb_real_shop_picking_list'); // TRUNCATEはトリガが動かないので不可。

    // 店舗在庫が在庫依頼数未満の全商品から、ピッキング可能分を取得
    $sql = <<<EOD
      SELECT
          s.ne_syohin_syohin_code
        , pci.フリー在庫数 AS free_stock
        , s.order_num - ( s.stock + COALESCE(pr.report_num, 0) ) AS ordered_num
        , CASE
            WHEN s.order_num - ( s.stock + COALESCE(pr.report_num, 0) ) >
                 ( CASE WHEN v.stock_remain > pci.フリー在庫数 THEN pci.フリー在庫数 ELSE v.stock_remain END ) /* ピッキング可能上限 #22322 */
              THEN ( CASE WHEN v.stock_remain > pci.フリー在庫数 THEN pci.フリー在庫数 ELSE v.stock_remain END ) /* ピッキング可能上限 #22322 */
            ELSE s.order_num - ( s.stock + COALESCE(pr.report_num, 0) )
          END AS move_num
        , i.directory AS pict_directory
        , i.filename AS pict_filename
      FROM tb_real_shop_product_stock s
      INNER JOIN tb_productchoiceitems pci ON s.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      INNER JOIN v_product_stock_picking_assign v ON v.warehouse_id = :warehouseId
                                                 AND s.ne_syohin_syohin_code = v.ne_syohin_syohin_code
      INNER JOIN product_images i ON s.daihyo_syohin_code = i.daihyo_syohin_code
                                 AND i.code = 'p001'
      LEFT JOIN (
        SELECT
            pr.ne_syohin_syohin_code
          , SUM(pr.move_num) AS report_num
        FROM tb_real_shop_picking_report pr
        WHERE pr.status = :reportStatusNone
        GROUP BY pr.ne_syohin_syohin_code
      ) pr ON s.ne_syohin_syohin_code = pr.ne_syohin_syohin_code
      WHERE s.order_num > ( s.stock + COALESCE(pr.report_num, 0) )
        AND pci.フリー在庫数 > 0
      ORDER BY s.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':reportStatusNone', TbRealShopPickingReportRepository::REPORT_STATUS_NONE, \PDO::PARAM_INT);
    $stmt->execute();

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_real_shop_picking_list", [
      'fields' => [
          'date'                  => \PDO::PARAM_STR
        , 'number'                => \PDO::PARAM_INT
        , 'ne_syohin_syohin_code' => \PDO::PARAM_STR
        , 'free_stock'            => \PDO::PARAM_INT
        , 'ordered_num'           => \PDO::PARAM_INT
        , 'move_num'              => \PDO::PARAM_INT
        , 'status'                => \PDO::PARAM_INT
        , 'pict_directory'        => \PDO::PARAM_STR
        , 'pict_filename'         => \PDO::PARAM_STR
      ]
      , 'prefix' => "INSERT IGNORE INTO"
    ]);

    $today = new \DateTime();
    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($item) use ($today) {
      $item['date'] = $today->format('Y-m-d');
      $item['number'] = self::DEFAULT_NUMBER;
      $item['status'] = TbRealShopPickingListRepository::PICKING_STATUS_NONE;
      return $item;
    });

    // 並び順決定
    $this->refreshLocation($today->format('Y-m-d'), self::DEFAULT_NUMBER, $warehouse);
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
  private function refreshLocation($date, $number, $currentWarehouse)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE
      tb_real_shop_picking_list picking
      INNER JOIN (
        SELECT
            picking.ne_syohin_syohin_code
          , MIN(pl.position)  AS min_position
        FROM tb_real_shop_picking_list picking
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
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // 並び順更新
    $dbMain->exec("SET @i := 0");

    $sql = <<<EOD
      UPDATE tb_real_shop_picking_list
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
   * ピッキングリスト取得
   */
  public function getPickingList()
  {
    $sql = <<<EOD
      SELECT
          l.*
        , COALESCE(i.label_type, 'tag') AS label_type
      FROM tb_real_shop_picking_list l
      LEFT JOIN tb_real_shop_product_stock s ON l.ne_syohin_syohin_code = s.ne_syohin_syohin_code
      LEFT JOIN tb_real_shop_information i ON s.daihyo_syohin_code = i.daihyo_syohin_code
      ORDER BY l.picking_order
EOD;
    $stmt = $this->getConnection('main')->query($sql);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }


  /**
   * ピッキング確定処理
   * @param SymfonyUserInterface $account
   * @throws \Doctrine\DBAL\DBALException
   */
  public function convertPickingListToReport(SymfonyUserInterface $account)
  {
    $dbMain = $this->getConnection('main');

    // レポートテーブルへ流し込み
    $sql = <<<EOD
      INSERT IGNORE INTO tb_real_shop_picking_report (
          picking_date
        , number
        , ne_syohin_syohin_code
        , free_stock
        , ordered_num
        , move_num
        , create_account_id
        , create_account_name
      )
      SELECT
          CURRENT_DATE() AS picking_date
        , COALESCE(T.max_number + 1, 1) AS number
        , pl.ne_syohin_syohin_code
        , MAX(pl.free_stock) AS free_stock
        , MAX(pl.ordered_num) AS ordered_num
        , SUM(move_num) AS move_num
        , :accountId
        , :accountName
      FROM tb_real_shop_picking_list pl
      LEFT JOIN (
        SELECT
          MAX(number) AS max_number
        FROM tb_real_shop_picking_report pr
        WHERE pr.picking_date = CURRENT_DATE()
      ) AS T ON 1
      WHERE pl.status = :statusOk
      GROUP BY pl.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':statusOk', self::PICKING_STATUS_OK);
    $stmt->bindValue(':accountId', $account ? $account->getId() : 0, \PDO::PARAM_INT);
    $stmt->bindValue(':accountName', $account ? $account->getClientName() : '', \PDO::PARAM_STR);
    $stmt->execute();
  }


  /**
   * ピッキング済みのレコードの有無をチェック
   */
  public function getPickedCount()
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
        COUNT(*) AS cnt
      FROM tb_real_shop_picking_list pl
      WHERE pl.status = :statusOk
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':statusOk', self::PICKING_STATUS_OK, \PDO::PARAM_INT);
    $stmt->execute();

    return intval($stmt->fetchColumn(0));
  }

  /**
   * 全削除 （ただし、安全のため確定済みのものは残す。）
   */
  public function clearAll()
  {
    // 実店舗ピッキングリストの再作成
    $dbMain = $this->getConnection('main');

    // TRUNCATEはトリガが動かないので不可。
    $sql = <<<EOD
      DELETE pl
      FROM tb_real_shop_picking_list pl
      WHERE pl.status <> :statusOk
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':statusOk', self::PICKING_STATUS_OK, \PDO::PARAM_INT);
    $stmt->execute();
  }



}
