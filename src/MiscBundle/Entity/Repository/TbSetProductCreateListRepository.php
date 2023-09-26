<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbSetProductCreateDetail;
use MiscBundle\Entity\TbSetProductCreateList;
use MiscBundle\Entity\TbWarehouse;

/**
 * TbSetProductCreateListRepository
 */
class TbSetProductCreateListRepository extends BaseRepository
{
  // 作成対象ステータス
  const CREATE_LIST_STATUS_NONE = 0;
  const CREATE_LIST_STATUS_CREATED = 1;
  const CREATE_LIST_STATUS_SKIPPED = 2;

  // 見出しステータス
  const INDEX_STATUS_NONE    = '未処理';
  const INDEX_STATUS_ONGOING = '未完了';
  const INDEX_STATUS_DONE    = '完了';

  public static $CREATE_LIST_STATUS_DISPLAY = [
        self::CREATE_LIST_STATUS_NONE => '未処理'
      , self::CREATE_LIST_STATUS_CREATED => '作成済み'
      , self::CREATE_LIST_STATUS_SKIPPED => 'スキップ'
  ];

  public static $CREATE_LIST_STATUS_DISPLAY_CSS = [
      self::CREATE_LIST_STATUS_NONE => ''
    , self::CREATE_LIST_STATUS_CREATED => 'label-success'
    , self::CREATE_LIST_STATUS_SKIPPED => 'label-warning'
  ];

  /**
   * 作成必要SKU一覧 取得
   * @param TbWarehouse $warehouse
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getRequiredList($warehouse)
  {
    $db = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
          pci.daihyo_syohin_code                      AS daihyo_syohin_code
        , pci.ne_syohin_syohin_code                   AS set_sku
        , m.daihyo_syohin_name                        AS daihyo_syohin_name
        , pci.colname                                 AS colname
        , pci.rowname                                 AS rowname
        , pci.`フリー在庫数`                           AS set_free_stock  /* セット商品フリー在庫 */
        , sku.required_stock                          AS required_stock  /* 在庫設定数 */
        , sku.required_stock - pci.`フリー在庫数`      AS short_num       /* 必要作成数 */
        , d.ne_syohin_syohin_code                     AS detail_sku      /* 内訳SKU */
        , d.num                                       AS detail_num      /* 内訳SKU指定数 */
        , CASE
            WHEN pci_detail.`フリー在庫数` >= COALESCE(location_stock.stock, 0) THEN COALESCE(location_stock.stock, 0)
            ELSE pci_detail.`フリー在庫数`
          END AS detail_free_stock /* 内訳SKUフリー在庫数（倉庫内） */
        , sku.required_stock * d.num              AS detail_required_stock /* 設定在庫数による内訳設定在庫数 */
        , TRUNCATE(
            (
              CASE
                WHEN pci_detail.`フリー在庫数` >= COALESCE(location_stock.stock, 0) THEN COALESCE(location_stock.stock, 0)
                ELSE pci_detail.`フリー在庫数`
              END
            )
             /
            d.num, 0
          ) AS creatable_num /* 内訳SKUフリー在庫からの作成可能数 */
        , 0                                           AS create_num      /* 作成予定数 */
        , COALESCE(CL.list_num, 0)                    AS list_num        /* 作成予定 未作成数 */
      FROM tb_productchoiceitems pci
      INNER JOIN tb_set_product_sku sku ON pci.ne_syohin_syohin_code = sku.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
      INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
      LEFT JOIN (
        SELECT
            v.ne_syohin_syohin_code
          , v.stock_remain AS stock
        FROM v_product_stock_picking_assign v
        WHERE v.warehouse_id = :warehouseId
      ) AS location_stock ON d.ne_syohin_syohin_code = location_stock.ne_syohin_syohin_code
      LEFT JOIN (
        SELECT
            l.set_sku
          , SUM(l.create_num) AS list_num
        FROM tb_set_product_create_list l
        WHERE l.`status` = :createListStatusNone
        GROUP BY l.set_sku
      ) CL ON pci.ne_syohin_syohin_code = CL.set_sku
      WHERE m.set_flg <> 0
        AND pci.`フリー在庫数` < sku.required_stock /* 作成必要 */
      ORDER BY pci.daihyo_syohin_code, pci.ne_syohin_syohin_code
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':createListStatusNone', TbSetProductCreateListRepository::CREATE_LIST_STATUS_NONE, \PDO::PARAM_INT);
    $stmt->execute();

    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // 対象商品毎に配列にまとめる
    $products = [];
    $details = [];
    foreach($list as $detail) {
      if (!isset($details[$detail['set_sku']])) {
        $details[$detail['set_sku']] = [];
        $products[] = $detail;
      }
      $details[$detail['set_sku']][] = $detail;
    }

    // 詳細配列を格納する。また、作成可能数をSKU毎に最小のものに置き換え、実作成数を計算
    foreach($products as $index => $product) {
      $product['details'] = [];

      if (isset($details[$product['set_sku']])) {
        $product['details'] = $details[$product['set_sku']];

        foreach($product['details'] as $detail) {
          if ($product['creatable_num'] > $detail['creatable_num']) {
            $product['creatable_num'] = $detail['creatable_num'];
          }
        }
      }

      // 内訳項目の削除
      unset($product['detail_sku']);
      unset($product['detail_num']);
      unset($product['detail_free_stock']);
      unset($product['detail_required_stock']);

      // 実作成数
      $realShort = $product['short_num'] - $product['list_num'];
      $realCreatable = $product['creatable_num'] - $product['list_num'];
      $realShort = $realShort < 0 ? 0 : $realShort;
      $realCreatable = $realCreatable < 0 ? 0 : $realCreatable;

      $product['create_num'] = $realShort <= $realCreatable ? $realShort : $realCreatable;

      $products[$index] = $product;
    }

    return $products;
  }

  /**
   * 連番作成
   * FOR UPDATEのロックを利用するため、トランザクションの開始が必須。
   *
   * @param \Doctrine\DBAL\Connection $dbMain
   * @param \DateTimeInterface $date
   * @return int 最大番号
   */
  public function getMaxNumber($dbMain, $date = null)
  {
    if (!$date) {
      $date = new \DateTime();
    }

    if (!$dbMain->isTransactionActive()) {
      throw new \RuntimeException('トランザクション中のみ実行できます。');
    }

    $sql = <<<EOD
      SELECT
          MAX(number) AS max_num
      FROM tb_set_product_create_list l
      WHERE l.`date` = :date
      FOR UPDATE
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date->format('Y-m-d'));
    $stmt->execute();

    $num = intval($stmt->fetchColumn(0));

    return $num;
  }

  /**
   * 作成予定フリー在庫 不足抽出
   * @param \DateTime $date
   * @param int $number
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getFreeStockShortageList($date, $number)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
          pci.ne_syohin_syohin_code
        , pci.`フリー在庫数` AS free_stock
        , T.picking_num_total
      FROM tb_productchoiceitems pci
      INNER JOIN (
        SELECT
            d.detail_sku
          , SUM(d.picking_num) AS picking_num_total
        FROM tb_set_product_create_detail d
        INNER JOIN tb_set_product_create_list l ON d.list_id = l.id
        /* チェック対象商品は、指定のリスト内商品に限定 */
        INNER JOIN (
          SELECT
            DISTINCT d.detail_sku
          FROM tb_set_product_create_detail d
          INNER JOIN tb_set_product_create_list l ON d.list_id = l.id
          WHERE l.`status` = 0
            AND l.date = :date
            AND l.number = :number
        ) T ON d.detail_sku = T.detail_sku
        WHERE l.`status` = 0
        GROUP BY d.detail_sku
      ) T ON pci.ne_syohin_syohin_code = T.detail_sku
      WHERE pci.`フリー在庫数` < T.picking_num_total
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date->format('Y-m-d'));
    $stmt->bindValue(':number', $number);
    $stmt->execute();

    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $result;
  }

  /**
   * 作成リスト 見出し一覧取得
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

    if (isset($conditions['date']) && $conditions['date']) {
      $wheres[] = '( `date` = :date )';
      $params[':date'] = $conditions['date']->format('Y-m-d');
    }
    if (isset($conditions['status'])) {
      if ($conditions['status'] == 'incomplete') {
        $wheres[] = '( `status` = :statusNone )';
        $params[':statusNone'] = TbSetProductCreateListRepository::CREATE_LIST_STATUS_NONE;
      }
    }
    if (isset($conditions['number'])) {
      $wheres[] = '( `number` = :number )';
      $params[':number'] = intval($conditions['number']);
    }

    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $sql = <<<EOD
      SELECT
          `date`
        , number
        , COUNT(DISTINCT set_sku) AS syohin_num
        , SUM(create_num) AS item_num
        , DATE_FORMAT(`date`, '%m/%d') AS date_short
        , CASE
            WHEN MAX(status) = :createStatusNone THEN :indexStatusNone
            WHEN MAX(status) > :createStatusNone AND MIN(status) < :createStatusCreated THEN :indexStatusOngoing
            WHEN MIN(status) > :createStatusNone THEN :indexStatusDone
            ELSE ''
          END AS status
      FROM tb_set_product_create_list
      WHERE 1
      {$addWheres}
      GROUP BY `date`, `number`
      ORDER BY `date` DESC
             , number DESC
      LIMIT :limit
EOD;
    $stmt = $dbMain->prepare($sql);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->bindValue(':createStatusNone', self::CREATE_LIST_STATUS_NONE, \PDO::PARAM_INT);
    $stmt->bindValue(':createStatusCreated', self::CREATE_LIST_STATUS_CREATED, \PDO::PARAM_INT);
    $stmt->bindValue(':indexStatusNone', self::INDEX_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':indexStatusOngoing', self::INDEX_STATUS_ONGOING, \PDO::PARAM_STR);
    $stmt->bindValue(':indexStatusDone', self::INDEX_STATUS_DONE, \PDO::PARAM_STR);
    $stmt->bindValue(':limit', intval($limit), \PDO::PARAM_INT);
    $stmt->execute();

    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }

    return $result;
  }

  /**
   * リスト 1セット取得
   * @param $date
   * @param $number
   * @return array
   */
  private function findList($date, $number)
  {
    $qb = $this->createQueryBuilder('l');
    $qb->andWhere('l.date = :date')->setParameter(':date', $date, \PDO::PARAM_STR);
    $qb->andWhere('l.number = :number')->setParameter(':number', $number, \PDO::PARAM_INT);

    $qb->addOrderBy('l.set_sku', 'ASC');

    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
          l.`id`
        , l.`date`
        , l.`number`
        , l.`set_sku`
        , l.`required_num`
        , l.`creatable_num`
        , l.`create_num`
        , l.`status`
        , l.`created`
        , l.`updated`
        , pci.colname
        , pci.rowname
        , pci.フリー在庫数 AS free_stock
      FROM tb_set_product_create_list l
      INNER JOIN tb_productchoiceitems pci ON l.set_sku = pci.ne_syohin_syohin_code
      WHERE l.`date` = :date
        AND l.number = :number
      ORDER BY l.set_sku ASC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
    $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
    $stmt->execute();

    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    return $list;
  }

  /**
   * リスト 1セット 詳細付き取得
   */
  public function findListWithDetails($date, $number)
  {
    $list = $this->findList($date, $number);

    $listIds = [];
    foreach($list as $product) {
      $listIds[] = $product['id'];
    }

    // 詳細取得
    $qb = $this->getEntityManager()->createQueryBuilder();
    $qb->select('d')->from('MiscBundle:TbSetProductCreateDetail', 'd');
    $qb->andWhere($qb->expr()->in('d.list_id', $listIds));

    /** @var TbSetProductCreateDetail[] $tmp */
    $tmp = $qb->getQuery()->getResult();
    $detailsList = [];
    foreach($tmp as $detail) {
      $listId = $detail->getListId();
      if (!isset($detailsList[$listId])) {
        $detailsList[$listId] = [];
      }
      $detailsList[$listId][] = $detail;
    }

    foreach($list as $i => $product) {
      $listId = $product['id'];
      $details = isset($detailsList[$listId]) ? $detailsList[$listId] : [];
      $list[$i]['details'] = $details;
    }

    return $list;
  }


  /**
   * リスト 1セット削除
   * @param $date
   * @param $number
   * @return int
   * @throws \Doctrine\DBAL\ConnectionException
   * @throws \Exception
   */
  public function deleteCreateList($date, $number)
  {
    $list = $this->findList($date, $number);
    if (!$list) {
      return 0;
    }

    $dbMain = $this->getConnection('main');
    $dbMain->beginTransaction();

    try {

      // ピッキングリスト削除
      $sql = <<<EOD
          DELETE pl
          FROM tb_set_product_picking_list pl
          WHERE pl.date = :date
            AND pl.number = :number
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->execute();

      // 詳細レコード削除
      $sql = <<<EOD
          DELETE d
          FROM tb_set_product_create_detail d
          INNER JOIN tb_set_product_create_list l ON d.list_id = l.id
          WHERE l.date = :date
            AND l.number = :number
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
      $stmt->execute();

      $listIds = [];
      foreach($list as $item) {
        $listIds[] = $item['id'];
      }
      $listIdsStr = implode(', ', $listIds);

      $sql = <<<EOD
        DELETE l
        FROM tb_set_product_create_list l
        WHERE l.id IN ( {$listIdsStr} )
EOD;
      $dbMain->exec($sql);

      $dbMain->commit();

      return count($list);

    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }

      throw $e;
    }
  }

  /**
   * ステータス文言
   */
  public function getListStatusDisplay($status)
  {
    $result = isset(self::$CREATE_LIST_STATUS_DISPLAY[$status])
      ? self::$CREATE_LIST_STATUS_DISPLAY[$status]
      : '';
    return $result;
  }

  /**
   * ステータス文言表示CSS
   */
  public function getListStatusDisplayCss($status)
  {
    $result = isset(self::$CREATE_LIST_STATUS_DISPLAY_CSS[$status])
      ? self::$CREATE_LIST_STATUS_DISPLAY_CSS[$status]
      : '';
    return $result;
  }






}
