<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryPickingList;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\BatchLogger;

/**
 * TbDeliveryPickingListRepository
 */
class TbDeliveryPickingListRepository extends BaseRepository
{
  // ピッキングステータス
  // 1: OK, 2: △, 3: PASS
  const PICKING_STATUS_NONE      = 0;
  const PICKING_STATUS_OK        = 1;
  const PICKING_STATUS_INCORRECT = 2;
  const PICKING_STATUS_PASS      = 3;

  // ピッキングステータス 文言
  public static $PICKING_STATUS_DISPLAYS = [
      self::PICKING_STATUS_NONE => ''
    , self::PICKING_STATUS_OK => 'OK'
    , self::PICKING_STATUS_INCORRECT => 'ロケ違い'
    , self::PICKING_STATUS_PASS => '在庫無し'
  ];

  // 見出しステータス
  const INDEX_STATUS_NONE    = '未処理';
  const INDEX_STATUS_ONGOING = '未完了';
  const INDEX_STATUS_CHECK   = '要チェック';
  const INDEX_STATUS_DONE    = '完了';

  const TEMPORARY_DATABASE_NAME = "TEMPORARY"; // 一時テーブルを作成するデータベース（スキーマ）名

  // debug用にsetPickingOrderが常にfalse(ピッキング数が異なる)を返す
  const DEBUG_SET_PICKING_ORDER_RETURN_FALSE = false; 

  /**
   * ピッキングリスト 見出し一覧取得
   * @param array $conditions
   * @param int $limit
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function findListIndex($conditions, $limit = 100)
  {
    $dbMain = $this->getConnection('main');

    $wheres = [];
    $params = [];

    // 倉庫
    if (empty($conditions['warehouse_id'])) {
      throw new \RuntimeException('倉庫が選択されていません。');
    }
    $wheres[] = '( v.`warehouse_id` = :warehouseId )';
    $params[':warehouseId'] = $conditions['warehouse_id'];
    // 日付
    if (isset($conditions['date'])) {
      if ($conditions['date'] == 'today') {
        $wheres[] = '( v.`date` = :date )';
        $params[':date'] = (new \DateTime())->format('Y-m-d');
      }
    }
    // 状態
    if (isset($conditions['status'])) {
      if ($conditions['status'] == 'incomplete') {
        $wheres[] = '( v.`status` IN ( :statusNone, :statusOngoing ) )';
        $params[':statusNone'] = self::INDEX_STATUS_NONE;
        $params[':statusOngoing'] = self::INDEX_STATUS_ONGOING;
      }
    }

    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $sql = <<<EOD
      SELECT DISTINCT
          v.`datetime`
        , v.`date`
        , DATE_FORMAT(v.`date`, '%m/%d') AS date_short
        , v.number
        , v.`old_date`
        , v.old_number
        , w.symbol
        , sv.warehouse_daily_number
        , v.account
        , v.picking_account_name
        , v.syohin_num
        , v.item_num
        , v.status
        , GROUP_CONCAT(COALESCE(sv.picking_block_pattern, '')) AS picking_block
        , COALESCE(dpl.empty_location, 0) AS empty_location
        , p.packing_comment AS packing_comment
      FROM v_delivery_picking_list_index v
      LEFT JOIN tb_shipping_voucher sv ON v.`date` = sv.picking_list_date AND v.number = sv.picking_list_number AND v.warehouse_id = sv.warehouse_id
      LEFT JOIN tb_warehouse         w ON v.`warehouse_id` = w.id
      LEFT JOIN (
        SELECT
          `date`
          ,number
          ,warehouse_id
          ,count(current_location) as empty_location
        FROM
          tb_delivery_picking_list
        WHERE
          current_location = ''
        GROUP BY
          `date`
          ,number
          ,warehouse_id
      ) dpl ON v.`date` = dpl.`date` AND v.number = dpl.number AND v.warehouse_id=dpl.warehouse_id
      LEFT JOIN tb_shipping_voucher_packing_group p ON sv.shipping_voucher_packing_group_id = p.id
      WHERE 1
      {$addWheres}
	  GROUP BY v.date
	         , v.number
      ORDER BY v.`date` DESC
             , v.number DESC
      LIMIT :limit
EOD;
    $stmt = $dbMain->prepare($sql);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->bindValue(':limit', intval($limit), \PDO::PARAM_INT);
    $stmt->execute();

    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }

    return $result;
  }

  /**
   * ピッキングリスト 見出し１件取得（完了・未完了を問わない）
   * @param $date
   * @param $number
   * @param TbWarehouse $warehouse
   * @return array|null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function findListIndexOne($date, $number, $warehouse)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
          vp.`datetime`
        , vp.`date`
        , DATE_FORMAT(vp.`date`, '%m/%d') AS date_short
        , vp.number
        , v.warehouse_daily_number
        , vp.warehouse_id
        , w.symbol
        , vp.account
        , vp.picking_account_name
        , vp.syohin_num
        , vp.item_num
        , vp.status
      FROM v_delivery_picking_list_index vp
      LEFT JOIN tb_shipping_voucher v ON vp.number = v.picking_list_number AND vp.`date` = v.picking_list_date AND v.warehouse_id = :warehouseId
      LEFT JOIN tb_warehouse        w ON vp.warehouse_id = w.id
      WHERE vp.`date` = :date
        AND vp.number = :number
        AND vp.warehouse_id = :warehouseId
      ORDER BY `date` DESC
             , number DESC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', intval($number), \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', intval($warehouse->getId()), \PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $result;
  }

  /**
   * ピッキングリスト商品一覧取得
   * @param string $date
   * @param integer $number
   * @param TbWarehouse $warehouse
   * @return \MiscBundle\Entity\TbDeliveryPickingList[]
   */
  public function findPickingProductList($date, $number, $warehouse)
  {
    $qb = $this->createQueryBuilder('p');
    $qb->andWhere('p.date = :date')->setParameter('date', $date, \PDO::PARAM_STR);
    $qb->andWhere('p.number = :number')->setParameter('number', $number, \PDO::PARAM_INT);
    $qb->andWhere('p.warehouse_id = :warehouseId')->setParameter('warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $qb->addOrderBy('p.picking_order', 'ASC');
    $qb->addOrderBy('p.id', 'ASC');

    return $qb->getQuery()->getResult();
  }

  /**
   * ピッキング担当者更新
   * 同一ピッキングリスト 全更新
   * @param string $date
   * @param int $number
   * @param SymfonyUsers $account
   * @param TbWarehouse $warehouse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updatePickingAccount($date, $number, $account, $warehouse)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE
      tb_delivery_picking_list
      SET picking_account_id = :accountId
        , picking_account_name = :accountName
      WHERE `date` = :date
        AND number = :number
        AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':accountId', $account->getId());
    $stmt->bindValue(':accountName', $account->getUsername());
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':number', $number);
    $stmt->bindValue(':warehouseId', $warehouse->getId());
    $stmt->execute();

    return;
  }

  /**
   * ピッキング順 設定処理（「開始」時処理）
   * @param string $date
   * @param int $number
   * @param TbWarehouse $warehouse
   * @param bool $forceStart trueの場合、ピッキング数が開始前後で異なっていてもそのまま開始
   * @return bool T/F=正常終了/異常終了(ピッキング数が開始前後で相違あり)
   * @throws \Doctrine\DBAL\DBALException
   */
  public function setPickingOrder($date, $number, $warehouse, $forceStart = false)
  {
    $dbMain = $this->getConnection('main');
    $em = $this->getEntityManager();
    
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    // debug用。ピッキング数相違がある場合をシュミレート
    if (self::DEBUG_SET_PICKING_ORDER_RETURN_FALSE) {
      return false;
    }

    if($forceStart == false){
      // ピッキングリスト退避用一時テーブル作成
      $this->createTmpDelivelyPickingListCopy();
      // 退避用一時テーブルに対象ピッキングリストを退避
      $this->copyDeliveryPickingList2tmp($date, $number, $warehouse);
    }

    // カレントロケーション更新
    // 倉庫内で最も優先順の高いロケーションをセット。
    // 余剰がある(他の引当を除いて在庫が>0)中で最も優先順が高いロケーションを本体にセット
    $sql = <<<EOD
      UPDATE
      tb_delivery_picking_list dpl
      LEFT JOIN (
        tb_product_location pl
        INNER JOIN (
          SELECT
              pl.ne_syohin_syohin_code
            , MIN(pl.position) AS min_position
          FROM tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
          LEFT JOIN (
                SELECT
                  `商品コード` as ne_syohin_code
                  ,`current_location`
                  ,SUM(`総ピッキング数`) as 'incomplete'
                FROM
                  tb_delivery_picking_list
                WHERE
                    picking_status = 0
                AND current_location <> ""
                AND NOT (
                      date = :date
                  AND number = :number
                  AND warehouse_id = :warehouseId
                )
                GROUP BY `商品コード`,`current_location`
          ) dpl ON dpl.current_location = l.location_code
          AND dpl.ne_syohin_code = pl.ne_syohin_syohin_code
          WHERE l.warehouse_id = :warehouseId
          AND pl.stock - ifnull(dpl.incomplete,0) > 0
          GROUP BY pl.ne_syohin_syohin_code
        ) T ON pl.ne_syohin_syohin_code = T.ne_syohin_syohin_code
           AND pl.position = T.min_position
        INNER JOIN tb_location l ON pl.location_id = l.id
                                AND l.warehouse_id = :warehouseId
      ) ON dpl.`商品コード` = pl.ne_syohin_syohin_code
      SET dpl.current_location = COALESCE(l.location_code, '')
      WHERE dpl.date = :date
        AND dpl.number = :number
        AND dpl.warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // 処理前に削除
    // 引数で指定されたデータをtempから削除（loop処理の中で消してるはずだけど。。。）
    $sql = <<<EOD
      DELETE
      FROM
          tmp_work_picking_stock
      WHERE
          date = :date
      AND number = :number
      AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    $tempCnt = 0;
    do {
      // WEBピッキングリスト データ作成
      // tempに本体からデータを移しこむ。ただしロケーションストックに余力があり、そのストックで総ピッキング数を賄えない場合のみ
      // ストックで総ピッキング数を賄える場合は、前段の処理のロケーションセットだけでよいので、対象外
      $sql = <<<EOD
        INSERT INTO tmp_work_picking_stock (
           `日時`,
           `商品コード`,
           `商品名`,
           `フリー在庫数`,
           `在庫数`,
           `総ピッキング数`,
           `ロケーションコード`,
           `型番`,
           `janコード`,
           `仕入先コード`,
           `仕入先名`,
           `date`,
           `old_date`,
           `file_hash`,
           `number`,
           `old_number`,
           `account`,
           `picking_status`,
           `picking_account_id`,
           `picking_account_name`,
           `update_account_id`,
           `update_account_name`,
           `picking_order`,
           `current_location`,
           `warehouse_id`
        )
        SELECT 
            dpl.`日時`,
            dpl.`商品コード`,
            dpl.`商品名`,
            dpl.`フリー在庫数`,
            dpl.`在庫数`,
            dpl.`総ピッキング数` - (pl.stock - ifnull(dpl2.incomplete,0)),
            dpl.`ロケーションコード`,
            dpl.`型番`,
            dpl.`janコード`,
            dpl.`仕入先コード`,
            dpl.`仕入先名`,
            dpl.`date`,
            dpl.`old_date`,
            dpl.`file_hash`,
            dpl.`number`,
            dpl.`old_number`,
            dpl.`account`,
            dpl.`picking_status`,
            dpl.`picking_account_id`,
            dpl.`picking_account_name`,
            dpl.`update_account_id`,
            dpl.`update_account_name`,
            dpl.`picking_order`,
            '',
            dpl.`warehouse_id`
        FROM tb_delivery_picking_list dpl
            INNER JOIN tb_location l ON dpl.current_location = l.location_code
            LEFT JOIN tb_product_location pl ON dpl.商品コード = pl.ne_syohin_syohin_code AND l.id = pl.location_id
            LEFT JOIN (
                SELECT
                  `商品コード` as ne_syohin_code
                  ,`current_location`
                  ,SUM(`総ピッキング数`) as 'incomplete'
                FROM
                  tb_delivery_picking_list
                WHERE
                    picking_status = 0
                AND current_location <> ""
                AND NOT (
                      date = :date
                  AND number = :number
                  AND warehouse_id = :warehouseId
                )
                GROUP BY `商品コード`,`current_location`
            ) dpl2 ON dpl.current_location = dpl2.current_location
            AND dpl2.ne_syohin_code = pl.ne_syohin_syohin_code
        WHERE dpl.date = :date
        AND   dpl.number = :number
        AND   dpl.warehouse_id = :warehouseId
        AND   dpl.総ピッキング数 > pl.stock - ifnull(dpl2.incomplete,0)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      // 本体の総ピッキング数をストックから他のロケ割当済引当合計を除いた分減算する。残る数値は未割当のピッキング数となる
      $sql = <<<EOD
        UPDATE
        tb_delivery_picking_list dpl
            INNER JOIN tb_location l ON dpl.current_location = l.location_code
            LEFT JOIN tb_product_location pl ON dpl.商品コード = pl.ne_syohin_syohin_code AND l.id = pl.location_id
            LEFT JOIN (
                SELECT
                  `商品コード` as ne_syohin_code
                  ,`current_location`
                  ,SUM(`総ピッキング数`) as 'incomplete'
                FROM
                  tb_delivery_picking_list
                WHERE
                    picking_status = 0
                AND current_location <> ""
                AND NOT (
                      date = :date
                  AND number = :number
                  AND warehouse_id = :warehouseId
                )
                GROUP BY `商品コード`,`current_location`
            ) dpl2 ON dpl.current_location = dpl2.current_location
          AND dpl2.ne_syohin_code = pl.ne_syohin_syohin_code
        SET dpl.総ピッキング数 = (pl.stock - ifnull(dpl2.incomplete,0))
        WHERE dpl.date = :date
        AND   dpl.number = :number
        AND   dpl.warehouse_id = :warehouseId
        AND   dpl.総ピッキング数 > pl.stock - ifnull(dpl2.incomplete,0)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      // カレントロケーション更新
      // 倉庫内で最も優先順の高いロケーションをセット。
      // 引当可能なストックがあるうち優先度が一番高いロケーションをtempにセットする
      $sql = <<<EOD
        UPDATE
        tmp_work_picking_stock dpl
        LEFT JOIN (
          tb_product_location pl
          INNER JOIN (
            SELECT
                pl.ne_syohin_syohin_code
              , MIN(pl.position) AS min_position
            FROM tb_product_location pl
            INNER JOIN tb_location l ON pl.location_id = l.id
            LEFT JOIN (
                  SELECT
                    `商品コード` as ne_syohin_code
                    ,`current_location`
                    ,SUM(`総ピッキング数`) as 'incomplete'
                  FROM
                    tb_delivery_picking_list
                  WHERE
                      picking_status = 0
                  AND current_location <> ""
                  GROUP BY `商品コード`,`current_location`
            ) dpl ON dpl.current_location = l.location_code
            AND dpl.ne_syohin_code = pl.ne_syohin_syohin_code
            WHERE l.warehouse_id = :warehouseId
            AND pl.stock - ifnull(dpl.incomplete,0) > 0
            GROUP BY pl.ne_syohin_syohin_code
          ) T ON pl.ne_syohin_syohin_code = T.ne_syohin_syohin_code
             AND pl.position = T.min_position
          INNER JOIN tb_location l ON pl.location_id = l.id
                                  AND l.warehouse_id = :warehouseId
        ) ON dpl.`商品コード` = pl.ne_syohin_syohin_code
        SET dpl.current_location = COALESCE(l.location_code, '')
        WHERE dpl.date = :date
          AND dpl.number = :number
          AND dpl.warehouse_id = :warehouseId
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      // WEBピッキングリスト データ作成
      $sql = <<<EOD
        INSERT INTO tb_delivery_picking_list (
           `日時`,
           `商品コード`,
           `商品名`,
           `フリー在庫数`,
           `在庫数`,
           `総ピッキング数`,
           `ロケーションコード`,
           `型番`,
           `janコード`,
           `仕入先コード`,
           `仕入先名`,
           `date`,
           `old_date`,
           `file_hash`,
           `number`,
           `old_number`,
           `account`,
           `picking_status`,
           `picking_account_id`,
           `picking_account_name`,
           `update_account_id`,
           `update_account_name`,
           `picking_order`,
           `current_location`,
           `warehouse_id`
        )
        SELECT
           `日時`,
           `商品コード`,
           `商品名`,
           `フリー在庫数`,
           `在庫数`,
           `総ピッキング数`,
           `ロケーションコード`,
           `型番`,
           `janコード`,
           `仕入先コード`,
           `仕入先名`,
           `date`,
           `old_date`,
           `file_hash`,
           `number`,
           `old_number`,
           `account`,
           `picking_status`,
           `picking_account_id`,
           `picking_account_name`,
           `update_account_id`,
           `update_account_name`,
           `picking_order`,
           `current_location`,
           `warehouse_id`
        FROM tmp_work_picking_stock p
        WHERE
            date = :date
        AND number = :number
        AND warehouse_id = :warehouseId
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      $sql = <<<EOD
        SELECT
            COUNT(*) as cnt
        FROM
            tmp_work_picking_stock
        WHERE
            date = :date
        AND number = :number
        AND warehouse_id = :warehouseId
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();
      if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $tempCnt = intval($row['cnt']);
      }
      
      $logger->info(strval($tempCnt));

      // 一時テーブルを削除
      $sql = <<<EOD
        DELETE
        FROM
            tmp_work_picking_stock
        WHERE
            date = :date
        AND number = :number
        AND warehouse_id = :warehouseId
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      $em = $this->getEntityManager();
    }while($tempCnt > 0);

    // 総ピッキング数比較
    if($forceStart == false 
    && $this->isTotalPickingsEqual($date, $number, $warehouse) == false){
      // 異なる場合
      // ピッキングリストを開始前に戻す
      $this->undoDeliveryPickingList($date, $number, $warehouse);
      return false;
    };

    // 並び順 更新
    $dbMain->query("SET @i = 0");
    $sql = <<<EOD
      UPDATE
      tb_delivery_picking_list dpl
      SET dpl.picking_order = (@i := @i + 1)
      WHERE `date` = :date
        AND number = :number
        AND warehouse_id = :warehouseId
      ORDER BY ( CASE WHEN dpl.current_location = '' THEN 1 ELSE 0 END ) ASC
           , dpl.current_location
           , dpl.商品コード
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':number', $number);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    return true;
  }

  /**
   * ピッキング順 再設定処理（倉庫在庫切れ時）
   * @param string $date
   * @param int $number
   * @param TbWarehouse $warehouse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function reSetPickingOrder($date, $number, $warehouse, $syohin_code)
  {
    $dbMain = $this->getConnection('main');
    $em = $this->getEntityManager();
    
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    // 一時的にピッキング情報を統合
    $sql = <<<EOD
      INSERT INTO tmp_work_picking_stock (
         `日時`,
         `商品コード`,
         `商品名`,
         `フリー在庫数`,
         `在庫数`,
         `総ピッキング数`,
         `ロケーションコード`,
         `型番`,
         `janコード`,
         `仕入先コード`,
         `仕入先名`,
         `date`,
         `file_hash`,
         `number`,
         `old_number`,
         `account`,
         `picking_status`,
         `picking_account_id`,
         `picking_account_name`,
         `update_account_id`,
         `update_account_name`,
         `picking_order`,
         `current_location`,
         `warehouse_id`
      )
      SELECT 
          dpl.`日時`,
          dpl.`商品コード`,
          dpl.`商品名`,
          dpl.`フリー在庫数`,
          dpl.`在庫数`,
          SUM(dpl.`総ピッキング数`) as `総ピッキング数`,
          dpl.`ロケーションコード`,
          dpl.`型番`,
          dpl.`janコード`,
          dpl.`仕入先コード`,
          dpl.`仕入先名`,
          dpl.`date`,
          dpl.`file_hash`,
          dpl.`number`,
          dpl.`old_number`,
          dpl.`account`,
          dpl.`picking_status`,
          dpl.`picking_account_id`,
          dpl.`picking_account_name`,
          dpl.`update_account_id`,
          dpl.`update_account_name`,
          0,
          '',
          dpl.`warehouse_id`
      FROM tb_delivery_picking_list dpl
      WHERE dpl.date = :date
      AND   dpl.number = :number
      AND   dpl.warehouse_id = :warehouseId
      AND   dpl.商品コード = :syohinCode
      AND   dpl.picking_status = 0
      GROUP BY
        `商品コード`,old_date,old_number
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
    $stmt->execute();

    // 旧データを削除
    $sql = <<<EOD
      DELETE
      FROM
          tb_delivery_picking_list
      WHERE
          date = :date
      AND number = :number
      AND warehouse_id = :warehouseId
      AND 商品コード = :syohinCode
      AND picking_status = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
    $stmt->execute();
    
    // 統合データ挿入
    $sql = <<<EOD
      INSERT INTO tb_delivery_picking_list (
         `日時`,
         `商品コード`,
         `商品名`,
         `フリー在庫数`,
         `在庫数`,
         `総ピッキング数`,
         `ロケーションコード`,
         `型番`,
         `janコード`,
         `仕入先コード`,
         `仕入先名`,
         `date`,
         `old_date`,
         `file_hash`,
         `number`,
         `old_number`,
         `account`,
         `picking_status`,
         `picking_account_id`,
         `picking_account_name`,
         `update_account_id`,
         `update_account_name`,
         `picking_order`,
         `current_location`,
         `warehouse_id`
      )
      SELECT
         `日時`,
         `商品コード`,
         `商品名`,
         `フリー在庫数`,
         `在庫数`,
         `総ピッキング数`,
         `ロケーションコード`,
         `型番`,
         `janコード`,
         `仕入先コード`,
         `仕入先名`,
         `date`,
         `old_date`,
         `file_hash`,
         `number`,
         `old_number`,
         `account`,
         `picking_status`,
         `picking_account_id`,
         `picking_account_name`,
         `update_account_id`,
         `update_account_name`,
         `picking_order`,
         `current_location`,
         `warehouse_id`
      FROM tmp_work_picking_stock p
      WHERE
          date = :date
      AND number = :number
      AND warehouse_id = :warehouseId
      AND 商品コード = :syohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
    $stmt->execute();

    // カレントロケーション更新
    // 倉庫内で最も優先順の高いロケーションをセット。
    $sql = <<<EOD
      UPDATE
      tb_delivery_picking_list dpl
      LEFT JOIN (
        tb_product_location pl
        INNER JOIN (
          SELECT
              pl.ne_syohin_syohin_code
            , MIN(pl.position) AS min_position
          FROM tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
          LEFT JOIN (
                SELECT
                  `商品コード` as ne_syohin_code
                  ,`current_location`
                  ,SUM(`総ピッキング数`) as 'incomplete'
                FROM
                  tb_delivery_picking_list
                WHERE
                    picking_status = 0
                AND current_location <> ""
                AND NOT (
                      date = :date
                  AND number = :number
                  AND warehouse_id = :warehouseId
                )
                GROUP BY `商品コード`,`current_location`
          ) dpl ON dpl.current_location = l.location_code
          AND dpl.ne_syohin_code = pl.ne_syohin_syohin_code
          WHERE l.warehouse_id = :warehouseId
          AND pl.stock - ifnull(dpl.incomplete,0) > 0
          GROUP BY pl.ne_syohin_syohin_code
        ) T ON pl.ne_syohin_syohin_code = T.ne_syohin_syohin_code
           AND pl.position = T.min_position
        INNER JOIN tb_location l ON pl.location_id = l.id
                                AND l.warehouse_id = :warehouseId
      ) ON dpl.`商品コード` = pl.ne_syohin_syohin_code
      SET dpl.current_location = COALESCE(l.location_code, '')
      WHERE dpl.date = :date
        AND dpl.number = :number
        AND dpl.warehouse_id = :warehouseId
        AND dpl.商品コード = :syohinCode
        AND dpl.picking_status = 0

EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
    $stmt->execute();

    // 処理前に削除
    $sql = <<<EOD
      DELETE
      FROM
          tmp_work_picking_stock
      WHERE
          date = :date
      AND number = :number
      AND warehouse_id = :warehouseId
      AND 商品コード = :syohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
    $stmt->execute();

    $tempCnt = 0;
    do {
      // WEBピッキングリスト データ作成
      $sql = <<<EOD
        INSERT INTO tmp_work_picking_stock (
           `日時`,
           `商品コード`,
           `商品名`,
           `フリー在庫数`,
           `在庫数`,
           `総ピッキング数`,
           `ロケーションコード`,
           `型番`,
           `janコード`,
           `仕入先コード`,
           `仕入先名`,
           `date`,
           `old_date`,
           `file_hash`,
           `number`,
           `old_number`,
           `account`,
           `picking_status`,
           `picking_account_id`,
           `picking_account_name`,
           `update_account_id`,
           `update_account_name`,
           `picking_order`,
           `current_location`,
           `warehouse_id`
        )
        SELECT 
            dpl.`日時`,
            dpl.`商品コード`,
            dpl.`商品名`,
            dpl.`フリー在庫数`,
            dpl.`在庫数`,
            dpl.`総ピッキング数` - (pl.stock - ifnull(dpl2.incomplete,0)),
            dpl.`ロケーションコード`,
            dpl.`型番`,
            dpl.`janコード`,
            dpl.`仕入先コード`,
            dpl.`仕入先名`,
            dpl.`date`,
            dpl.`old_date`,
            dpl.`file_hash`,
            dpl.`number`,
            dpl.`old_number`,
            dpl.`account`,
            dpl.`picking_status`,
            dpl.`picking_account_id`,
            dpl.`picking_account_name`,
            dpl.`update_account_id`,
            dpl.`update_account_name`,
            dpl.`picking_order`,
            '',
            dpl.`warehouse_id`
        FROM tb_delivery_picking_list dpl
            INNER JOIN tb_location l ON dpl.current_location = l.location_code
            LEFT JOIN tb_product_location pl ON dpl.商品コード = pl.ne_syohin_syohin_code AND l.id = pl.location_id
            LEFT JOIN (
                SELECT
                  `商品コード` as ne_syohin_code
                  ,`current_location`
                  ,SUM(`総ピッキング数`) as 'incomplete'
                FROM
                  tb_delivery_picking_list
                WHERE
                    picking_status = 0
                AND current_location <> ""
                AND NOT (
                      date = :date
                  AND number = :number
                  AND warehouse_id = :warehouseId
                )
                GROUP BY `商品コード`,`current_location`
            ) dpl2 ON dpl.current_location = dpl2.current_location
            AND dpl2.ne_syohin_code = pl.ne_syohin_syohin_code
        WHERE dpl.date = :date
        AND   dpl.number = :number
        AND   dpl.warehouse_id = :warehouseId
        AND   dpl.商品コード = :syohinCode
        AND   dpl.picking_status = 0
        AND   dpl.総ピッキング数 > pl.stock - ifnull(dpl2.incomplete,0)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
      $stmt->execute();

      // カレントロケーション更新
      // 倉庫内で最も優先順の高いロケーションをセット。
      $sql = <<<EOD
        UPDATE
        tb_delivery_picking_list dpl
            INNER JOIN tb_location l ON dpl.current_location = l.location_code
            LEFT JOIN tb_product_location pl ON dpl.商品コード = pl.ne_syohin_syohin_code AND l.id = pl.location_id
            LEFT JOIN (
                SELECT
                  `商品コード` as ne_syohin_code
                  ,`current_location`
                  ,SUM(`総ピッキング数`) as 'incomplete'
                FROM
                  tb_delivery_picking_list
                WHERE
                    picking_status = 0
                AND current_location <> ""
                AND NOT (
                      date = :date
                  AND number = :number
                  AND warehouse_id = :warehouseId
                )
                GROUP BY `商品コード`,`current_location`
            ) dpl2 ON dpl.current_location = dpl2.current_location
          AND dpl2.ne_syohin_code = pl.ne_syohin_syohin_code
        SET dpl.総ピッキング数 = (pl.stock - ifnull(dpl2.incomplete,0))
        WHERE dpl.date = :date
        AND   dpl.number = :number
        AND   dpl.warehouse_id = :warehouseId
        AND   dpl.商品コード = :syohinCode
        AND   dpl.picking_status = 0
        AND   dpl.総ピッキング数 > pl.stock - ifnull(dpl2.incomplete,0)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
      $stmt->execute();

      // カレントロケーション更新
      // 倉庫内で最も優先順の高いロケーションをセット。
      $sql = <<<EOD
        UPDATE
        tmp_work_picking_stock dpl
        LEFT JOIN (
          tb_product_location pl
          INNER JOIN (
            SELECT
                pl.ne_syohin_syohin_code
              , MIN(pl.position) AS min_position
            FROM tb_product_location pl
            INNER JOIN tb_location l ON pl.location_id = l.id
            LEFT JOIN (
                  SELECT
                    `商品コード` as ne_syohin_code
                    ,`current_location`
                    ,SUM(`総ピッキング数`) as 'incomplete'
                  FROM
                    tb_delivery_picking_list
                  WHERE
                      picking_status = 0
                  AND current_location <> ""
                  GROUP BY `商品コード`,`current_location`
            ) dpl ON dpl.current_location = l.location_code
            AND dpl.ne_syohin_code = pl.ne_syohin_syohin_code
            WHERE l.warehouse_id = :warehouseId
            AND pl.stock - ifnull(dpl.incomplete,0) > 0
            GROUP BY pl.ne_syohin_syohin_code
          ) T ON pl.ne_syohin_syohin_code = T.ne_syohin_syohin_code
             AND pl.position = T.min_position
          INNER JOIN tb_location l ON pl.location_id = l.id
                                  AND l.warehouse_id = :warehouseId
        ) ON dpl.`商品コード` = pl.ne_syohin_syohin_code
        SET dpl.current_location = COALESCE(l.location_code, '')
        WHERE dpl.date = :date
          AND dpl.number = :number
          AND dpl.warehouse_id = :warehouseId
          AND dpl.商品コード = :syohinCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
      $stmt->execute();

      // WEBピッキングリスト データ作成
      $sql = <<<EOD
        INSERT INTO tb_delivery_picking_list (
           `日時`,
           `商品コード`,
           `商品名`,
           `フリー在庫数`,
           `在庫数`,
           `総ピッキング数`,
           `ロケーションコード`,
           `型番`,
           `janコード`,
           `仕入先コード`,
           `仕入先名`,
           `date`,
           `old_date`,
           `file_hash`,
           `number`,
           `old_number`,
           `account`,
           `picking_status`,
           `picking_account_id`,
           `picking_account_name`,
           `update_account_id`,
           `update_account_name`,
           `picking_order`,
           `current_location`,
           `warehouse_id`
        )
        SELECT
           `日時`,
           `商品コード`,
           `商品名`,
           `フリー在庫数`,
           `在庫数`,
           `総ピッキング数`,
           `ロケーションコード`,
           `型番`,
           `janコード`,
           `仕入先コード`,
           `仕入先名`,
           `date`,
           `old_date`,
           `file_hash`,
           `number`,
           `old_number`,
           `account`,
           `picking_status`,
           `picking_account_id`,
           `picking_account_name`,
           `update_account_id`,
           `update_account_name`,
           `picking_order`,
           `current_location`,
           `warehouse_id`
        FROM tmp_work_picking_stock p
        WHERE
            date = :date
        AND number = :number
        AND warehouse_id = :warehouseId
        AND 商品コード = :syohinCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
      $stmt->execute();

      $sql = <<<EOD
        SELECT
            COUNT(*) as cnt
        FROM
            tmp_work_picking_stock
        WHERE
            date = :date
        AND number = :number
        AND warehouse_id = :warehouseId
        AND 商品コード = :syohinCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
      $stmt->execute();
      if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $tempCnt = intval($row['cnt']);
      }
      
      $logger->info(strval($tempCnt));

      // 一時テーブルを削除
      $sql = <<<EOD
        DELETE
        FROM
            tmp_work_picking_stock
        WHERE
            date = :date
        AND number = :number
        AND warehouse_id = :warehouseId
        AND 商品コード = :syohinCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohin_code, \PDO::PARAM_STR);
      $stmt->execute();

      $em = $this->getEntityManager();
    }while($tempCnt > 0);

    // 並び順 更新
    $dbMain->query("SET @i = 0");
    $sql = <<<EOD
      UPDATE
      tb_delivery_picking_list dpl
      SET dpl.picking_order = (@i := @i + 1)
      WHERE `date` = :date
        AND number = :number
        AND warehouse_id = :warehouseId
      ORDER BY ( CASE WHEN dpl.current_location = '' THEN 1 ELSE 0 END ) ASC
           , dpl.current_location
           , dpl.商品コード
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':number', $number);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * ピッキングリスト削除処理
   * @param array $listIndex
   */
  public function deletePickingList($listIndex)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      DELETE
      FROM tb_delivery_picking_list
      WHERE `date` = :date
        AND number = :number
        AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $listIndex['date']);
    $stmt->bindValue(':number', $listIndex['number']);
    $stmt->bindValue(':warehouseId', $listIndex['warehouse_id']);
    $stmt->execute();

    // もし出荷リストがあれば、紐付けを削除
    $sql = <<<EOD
      UPDATE
        tb_shipping_voucher v
      SET v.picking_list_date = NULL
        , v.picking_list_number = 0
        , v.warehouse_daily_number = 0
      WHERE v.warehouse_id = :warehouseId
        AND v.picking_list_date = :date
        AND v.picking_list_number = :number
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $listIndex['warehouse_id']);
    $stmt->bindValue(':date', $listIndex['date']);
    $stmt->bindValue(':number', $listIndex['number']);
    $stmt->execute();

    return;
  }

  /**
   * ピッキングリスト統合処理
   * @param array $listIndex
   */
  public function margePickingList($listIndex, $date, $number, $warehouseDailyNumber)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE
        tb_delivery_picking_list
      SET `date` = :newDate
        , `number` = :newNumber
        , `old_date` = CASE  WHEN ifnull(old_date,'') = '' THEN :date ELSE old_date END
        , `old_number` = CASE  WHEN ifnull(old_number,0) = '' THEN :number ELSE old_number END
      WHERE `date` = :date
        AND number = :number
        AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':newDate', $date);
    $stmt->bindValue(':newNumber', $number);
    $stmt->bindValue(':date', $listIndex['date']);
    $stmt->bindValue(':number', $listIndex['number']);
    $stmt->bindValue(':warehouseId', $listIndex['warehouse_id']);
    $stmt->execute();

    // もし出荷リストがあれば、紐付けを削除
    $sql = <<<EOD
      UPDATE
        tb_shipping_voucher v
      SET v.picking_list_date = :newDate
        , v.picking_list_number = :newNumber
        , v.warehouse_daily_number = :newWarehouseDailyNumber
      WHERE v.warehouse_id = :warehouseId
        AND v.picking_list_date = :date
        AND v.picking_list_number = :number
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':newDate', $date);
    $stmt->bindValue(':newNumber', $number);
    $stmt->bindValue(':newWarehouseDailyNumber', $warehouseDailyNumber);
    $stmt->bindValue(':warehouseId', $listIndex['warehouse_id']);
    $stmt->bindValue(':date', $listIndex['date']);
    $stmt->bindValue(':number', $listIndex['number']);
    $stmt->execute();

    return;
  }

  /**
   * 過去 未完了取得ピッキングリスト
   *
   * @return TbDeliveryPickingList[]
   */
  public function getUnfinishedPickingList()
  {
    $qb = $this->createQueryBuilder('pl');
    $qb->andWhere('pl.date < CURRENT_DATE()');
    $qb->andWhere('pl.picking_status = :pickingStatusNone')->setParameter(':pickingStatusNone', self::PICKING_STATUS_NONE);

    $result = $qb->getQuery()->getResult();

    return $result;
  }

  /**
   * 未ピッキング商品の抽出
   * @param int $id
   * @param string $code
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getIncompleteList($id, $code)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
          `current_location`
        , SUM(`総ピッキング数`) as 'incomplete'
      FROM
          tb_delivery_picking_list
      WHERE
          picking_status = 0
      AND 商品コード = :code
      AND id <> :id
      GROUP BY `商品コード`,`current_location`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':code', $code, \PDO::PARAM_STR);
    $stmt->bindValue(':id', intval($id), \PDO::PARAM_INT);
    $stmt->execute();
    
    $ret = array();
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
        $ret[$row['current_location']] = $row['incomplete'];
    }
    
    return $ret;
  }

  /**
   * ピッキング残件数取得
   * @param array $conditions
   * @return bool|string
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getPickingListRemainNumber($conditions)
  {
    $dbMain = $this->getConnection('main');
    
    $wheres = [];
    $params = [];

    // $conditionsにデータがあれば絞込
    if (isset($conditions['warehouse_id'])) {
      $wheres[] = '( v.`warehouse_id` = :warehouseId )';
      $params[':warehouseId'] = $conditions['warehouse_id'];
    }
    // 未完了
    $wheres[] = '( v.`status` IN ( :statusNone, :statusOngoing ) )';
    $params[':statusNone'] = self::INDEX_STATUS_NONE;
    $params[':statusOngoing'] = self::INDEX_STATUS_ONGOING;

    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }
    
    $sql = <<<EOD
      SELECT COUNT(*) AS REMAIN_NUMBER FROM
      (
        SELECT v.`date`
        FROM v_delivery_picking_list_index v
        WHERE 1
        {$addWheres}
      ) cnt
EOD;
      $stmt = $dbMain->prepare($sql);
      if ($params) {
        foreach($params as $k => $v) {
          $stmt->bindValue($k, $v, \PDO::PARAM_STR);
        }
      }
      $stmt->execute();
      $count = $stmt->fetchColumn(0);

      return $count;
  }


  private function createTmpDelivelyPickingListCopy(){
    $dbMain = $this->getConnection('main');

    $temporaryWord = self::TEMPORARY_DATABASE_NAME;
    // 一時テーブルWEBピッキングリストコピー
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_delivery_picking_list_copy");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_delivery_picking_list_copy   (
        `日時` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `商品コード` varchar(50) NOT NULL DEFAULT '',
        `商品名` varchar(255) NOT NULL DEFAULT '' COMMENT '実際の出力時にはAccess側で楽天タイトルに上書きされる（2016/07/03時点）',
        `フリー在庫数` int(11) NOT NULL DEFAULT '0',
        `在庫数` int(11) NOT NULL DEFAULT '0',
        `総ピッキング数` int(11) NOT NULL DEFAULT '0',
        `ロケーションコード` varchar(255) NOT NULL DEFAULT '',
        `型番` varchar(255) NOT NULL DEFAULT '',
        `janコード` varchar(255) NOT NULL DEFAULT '',
        `仕入先コード` varchar(255) NOT NULL DEFAULT '',
        `仕入先名` varchar(255) NOT NULL DEFAULT '',
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `date` date NOT NULL,
        `old_date` date DEFAULT NULL,
        `file_hash` varchar(40) NOT NULL DEFAULT '' COMMENT 'ピッキングリストCSV ファイルSHA1ハッシュ値',
        `number` int(11) NOT NULL DEFAULT '0' COMMENT '当日取込連番',
        `old_number` int(11) NOT NULL DEFAULT '0' COMMENT '当日取込連番',
        `account` varchar(30) NOT NULL DEFAULT '' COMMENT '作成者名(or PC名)',
        `picking_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'ピッキングステータス (0:未完了, 1: OK, 2: △, 3: PASS)',
        `picking_account_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ピッキング担当者ID',
        `picking_account_name` varchar(24) NOT NULL DEFAULT '' COMMENT 'ピッキング担当者名',
        `update_account_id` int(11) NOT NULL DEFAULT '0' COMMENT '最終更新者ID',
        `update_account_name` varchar(24) NOT NULL DEFAULT '' COMMENT '最終更新者名',
        `picking_order` int(11) NOT NULL DEFAULT '0' COMMENT 'ピッキング順 「開始」時のカレントロケーション昇順',
        `current_location` varchar(30) NOT NULL DEFAULT '' COMMENT 'ピッキング開始時のカレントロケーション',
        `warehouse_id` int(11) NOT NULL DEFAULT '1',
        `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='WEBピッキングリストコピー。ピッキングリスト作成前の状態保持用'
EOD;
    $dbMain->exec($sql);
  }

  private function copyDeliveryPickingList2tmp($date, $number, $warehouse){
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
    INSERT INTO tmp_delivery_picking_list_copy (
       `日時`,
       `商品コード`,
       `商品名`,
       `フリー在庫数`,
       `在庫数`,
       `総ピッキング数`,
       `ロケーションコード`,
       `型番`,
       `janコード`,
       `仕入先コード`,
       `仕入先名`,
       `date`,
       `old_date`,
       `file_hash`,
       `number`,
       `old_number`,
       `account`,
       `picking_status`,
       `picking_account_id`,
       `picking_account_name`,
       `update_account_id`,
       `update_account_name`,
       `picking_order`,
       `current_location`,
       `warehouse_id`
    )
    SELECT 
        dpl.`日時`,
        dpl.`商品コード`,
        dpl.`商品名`,
        dpl.`フリー在庫数`,
        dpl.`在庫数`,
        dpl.`総ピッキング数`,
        dpl.`ロケーションコード`,
        dpl.`型番`,
        dpl.`janコード`,
        dpl.`仕入先コード`,
        dpl.`仕入先名`,
        dpl.`date`,
        dpl.`old_date`,
        dpl.`file_hash`,
        dpl.`number`,
        dpl.`old_number`,
        dpl.`account`,
        dpl.`picking_status`,
        dpl.`picking_account_id`,
        dpl.`picking_account_name`,
        dpl.`update_account_id`,
        dpl.`update_account_name`,
        dpl.`picking_order`,
        dpl.`current_location`,
        dpl.`warehouse_id`
    FROM tb_delivery_picking_list dpl
    WHERE dpl.date = :date
    AND   dpl.number = :number
    AND   dpl.warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();
  }

  private function isTotalPickingsEqual($date, $number, $warehouse){
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
    SELECT
      coalesce(a.`商品コード`, b.`商品コード`) AS `商品コード` 
    FROM
      ( 
        SELECT
          `商品コード`
          , sum(`総ピッキング数`) AS `total` 
        FROM
          `tmp_delivery_picking_list_copy` 
            WHERE
              date = :date 
              AND number = :number 
              AND warehouse_id = :warehouseId 
        GROUP BY
          `商品コード`
      ) AS a 
      LEFT JOIN ( 
        SELECT
          `商品コード`
          , sum(`総ピッキング数`) AS `total` 
        FROM
          `tb_delivery_picking_list` 
            WHERE
              date = :date 
              AND number = :number 
              AND warehouse_id = :warehouseId 
        GROUP BY
          `商品コード`
      ) AS b 
        ON a.`商品コード` = b.`商品コード` 
    WHERE
      b.`商品コード` IS NULL OR a.`total` != b.`total`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    return empty($results)? true:false;
  }

  private function undoDeliveryPickingList($date, $number, $warehouse){
    $dbMain = $this->getConnection('main');

    // 作成したピッキングリストを全て削除
    $sql = <<<EOD
    DELETE
    FROM tb_delivery_picking_list
    WHERE `date` = :date
      AND number = :number
      AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // copyからピッキングリストを元に戻す
    $sql = <<<EOD
    INSERT INTO tb_delivery_picking_list (
       `日時`,
       `商品コード`,
       `商品名`,
       `フリー在庫数`,
       `在庫数`,
       `総ピッキング数`,
       `ロケーションコード`,
       `型番`,
       `janコード`,
       `仕入先コード`,
       `仕入先名`,
       `date`,
       `old_date`,
       `file_hash`,
       `number`,
       `old_number`,
       `account`,
       `picking_status`,
       `picking_account_id`,
       `picking_account_name`,
       `update_account_id`,
       `update_account_name`,
       `picking_order`,
       `current_location`,
       `warehouse_id`
    )
    SELECT 
      dplc.`日時`,
      dplc.`商品コード`,
      dplc.`商品名`,
      dplc.`フリー在庫数`,
      dplc.`在庫数`,
      dplc.`総ピッキング数`,
      dplc.`ロケーションコード`,
      dplc.`型番`,
      dplc.`janコード`,
      dplc.`仕入先コード`,
      dplc.`仕入先名`,
      dplc.`date`,
      dplc.`old_date`,
      dplc.`file_hash`,
      dplc.`number`,
      dplc.`old_number`,
      dplc.`account`,
      dplc.`picking_status`,
      dplc.`picking_account_id`,
      dplc.`picking_account_name`,
      dplc.`update_account_id`,
      dplc.`update_account_name`,
      dplc.`picking_order`,
      dplc.`current_location`,
      dplc.`warehouse_id`
    FROM tmp_delivery_picking_list_copy dplc
      WHERE dplc.date = :date
      AND   dplc.number = :number
      AND   dplc.warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * ピッキングリスト.総ピッキング数と出荷伝票明細.受注数を比較して、差異があればログ用メッセージを作成する。
   *
   * @param string $date
   * @param int $number
   * @param TbWarehouse $warehouse
   * @return string
   */
  public function checkDeliveryPickingListAndShippingVoucher($date, $number, $warehouse){
    $dbMain = $this->getConnection('main');

    // 出荷伝票明細、ピッキングリスト数量チェック
    $sql = <<<EOD
    SELECT
    dpl.`商品コード`
    , dpl.`総ピッキング数` 
    , vd.`受注数`
  FROM
    ( 
      SELECT
        商品コード
        , sum(`総ピッキング数`) `総ピッキング数` 
      FROM
        tb_delivery_picking_list 
      WHERE
        warehouse_id = :warehouseId 
        AND date = :date 
        AND number = :number 
      GROUP BY
        `商品コード`
    ) dpl 
    INNER JOIN ( 
      SELECT
        d.商品コード
        , sum(d.`受注数`) `受注数` 
      FROM
        tb_shipping_voucher_detail d 
        INNER JOIN tb_shipping_voucher v 
          ON d.voucher_id = v.id 
      WHERE
        v.warehouse_id = :warehouseId 
        AND v.picking_list_date = :date 
        AND v.picking_list_number = :number 
      GROUP BY
        d.`商品コード`
    ) vd 
      ON dpl.`商品コード` = vd.`商品コード` 
  WHERE
    vd.`受注数` <> dpl.`総ピッキング数`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchall(\PDO::FETCH_ASSOC);
    $diffMessage = '';
    if ($data) {
      $diffMessage = "次の商品コードについて、「出荷伝票明細」と「ピッキングリスト」で数量差異が生じました。 　 ";
      foreach ($data as $index => $value) {
        $diffMessage .= "\n" . sprintf('(%s) 【 %s 】 出荷伝票明細: %d, ピッキングリスト: %d 　 '
          , $index + 1, $value['商品コード'], $value['受注数'], $value['総ピッキング数']
        );
      }
    }
    return $diffMessage;
  }
}
