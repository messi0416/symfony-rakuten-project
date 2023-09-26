<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbShippingVoucher;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Doctrine\ORM\Query\Expr\Join;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryMethod;
use MiscBundle\Entity\TbShippingVoucherPacking;
use MiscBundle\Entity\TbShippingVoucherPackingGroup;
use MiscBundle\Entity\TbShoppingMall;

/**
 * TbShippingVoucherRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbShippingVoucherRepository extends BaseRepository
{
  /**
   * 一覧 データ取得
   * @param array $conditions
   * @param array $orders
   * @param int $page
   * @param int $limit
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findShippingList($conditions = [], $orders = [], $page = 1, $limit = 100)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $wheres = [];
    $params = [];

    $sqlSelect = <<<EOD
      SELECT
          v.id
        , COALESCE(s.username, '') AS account_name
        , COALESCE(w.name, '') AS warehouse_name
        , COALESCE(w.symbol, '') AS warehouse_symbol
        , v.imported
        , v.warehouse_daily_number
        , v.picking_list_date
        , v.picking_list_number
        , vp.syohin_num AS picking_syohin_num
        , vp.item_num AS picking_item_num
        , vp.status AS picking_status
        , p.delivery_method_id
        , dm.delivery_name
        , p.id AS packing_id
        , p.packing_comment AS packing_comment
        , NULL AS packing_num
        , p.name AS packing_name
EOD;
    $sqlBody = <<<EOD
      FROM tb_shipping_voucher v
      LEFT JOIN tb_shipping_voucher_packing_group p ON v.shipping_voucher_packing_group_id = p.id
      LEFT JOIN tb_delivery_method dm ON p.delivery_method_id = dm.delivery_id
      LEFT JOIN tb_warehouse   w ON v.warehouse_id = w.id
      LEFT JOIN symfony_users  s ON v.account = s.id
      LEFT JOIN v_delivery_picking_list_index vp ON v.picking_list_date = vp.`date`
                                                AND v.picking_list_number = vp.number
                                                AND v.warehouse_id = vp.warehouse_id
      WHERE 1
EOD;
    // 絞込条件：ステータス
    if (isset($conditions['status']) && strlen($conditions['status'])) {
      $wheres[] = " v.status = :status ";
      $params[':status'] = TbShippingVoucher::STATUS_WAIT_PICKING;
    }
    // 絞込条件：取込日（from）
    if (isset($conditions['dateFrom']) && $conditions['dateFrom'] instanceof \DateTimeInterface) {
      $wheres[] = " v.imported >= :dateFrom ";
      $params[':dateFrom'] =  $conditions['dateFrom']->format('Y-m-d');
    }
    // 絞込条件：取込日（to）
    if (isset($conditions['dateTo']) && $conditions['dateTo'] instanceof \DateTimeInterface) {
      $wheres[] = " v.imported <= :dateTo ";
      $params[':dateTo'] =  $conditions['dateTo']->modify('+1 day')->format('Y-m-d');
    }
    // 絞込条件：倉庫ID
    if (isset($conditions['warehouseId']) && $conditions['warehouseId'] !== "0") {
      $wheres[] = " v.warehouse_id = :warehouseId ";
      $params[':warehouseId'] =  $conditions['warehouseId'];
    }
    // 絞込条件：作成者
    if (($conditions['accountName']) !== '') {
      $wheres[] = " s.username like :accountName ";
      $params[':accountName'] =  "%" . $conditions['accountName'] . "%";
    }

    if (count($wheres)) {
      $sqlBody .= " AND ( " . implode(' AND ', $wheres) . " ) ";
    }

    $rsm =  new ResultSetMapping();
    $rsm->addScalarResult('id', 'id', 'integer');
    $rsm->addScalarResult('account_name', 'account_name', 'string');
    $rsm->addScalarResult('warehouse_name', 'warehouse_name', 'string');
    $rsm->addScalarResult('warehouse_symbol', 'warehouse_symbol', 'string');
    $rsm->addScalarResult('imported', 'imported', 'datetime');
    $rsm->addScalarResult('warehouse_daily_number', 'warehouse_daily_number', 'integer');
    $rsm->addScalarResult('picking_list_date', 'picking_list_date', 'datetime');
    $rsm->addScalarResult('picking_list_number', 'picking_list_number', 'integer');
    $rsm->addScalarResult('picking_syohin_num', 'picking_syohin_num', 'integer');
    $rsm->addScalarResult('picking_item_num', 'picking_item_num', 'integer');
    $rsm->addScalarResult('picking_status', 'picking_status', 'string');
    $rsm->addScalarResult('delivery_method_id', 'delivery_method_id', 'integer');
    $rsm->addScalarResult('delivery_name', 'delivery_name', 'string');
    $rsm->addScalarResult('packing_id', 'packing_id', 'integer');
    $rsm->addScalarResult('packing_comment', 'packing_comment', 'string');
    $rsm->addScalarResult('packing_num', 'packing_num', 'integer');
    $rsm->addScalarResult('packing_name', 'packing_name', 'string');
    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    foreach($params as $k => $v) {
      $query->setParameter($k, $v);
    }

    $resultOrders = [];
    $defaultOrders = [
        'v.imported' => 'DESC'
      , 'v.id' => 'DESC'
    ];

    if ($orders) {
      foreach($orders as $k => $v) {
        switch($k) {
          case 'daihyo_syohin_code':
//            $k = 'o.' . $k;
            break;
        }

        $resultOrders[$k] = $v;
        if (isset($defaultOrders[$k])) {
          unset($defaultOrders[$k]);
        }
      }
    }
    $query->setOrders(array_merge($resultOrders, $defaultOrders));

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );
    return $pagination;
  }




  /**
   * 詳細を含めた伝票一覧 取得
   * @param int $voucherId
   * @return array
   */
  public function getVoucherListByVoucherId($voucherId)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
          d.`店舗名`
        , d.`伝票番号`
        , d.`受注番号`
        , d.`受注日`
        , d.`取込日`
        , d.`受注状態`
        , d.`発送方法`
        , d.`支払方法`
        , d.`合計金額`
        , d.`税金`
        , d.`手数料`
        , d.`送料`
        , d.`その他`
        , d.`ポイント`
        , d.`承認金額`
        , d.`備考`
        , d.`入金金額`
        , d.`入金区分`
        , d.`入金日`
        , d.`納品書印刷指示日`
        , d.`納品書発行日`
        , d.`納品書備考`
        , d.`出荷日`
        , d.`出荷予定日`
        , d.`作業者欄`
        , d.`ピック指示内容`
        , d.`ラベル発行日`
        , d.`配送日`
        , d.`配送時間帯`
        , d.`配送伝票番号`
        , d.`クレジット区分`
        , d.`名義人`
        , d.`有効期限`
        , d.`承認番号`
        , d.`承認区分`
        , d.`承認日`
        , d.`購入者名`
        , d.`購入者カナ`
        , d.`購入者郵便番号`
        , d.`購入者住所1`
        , d.`購入者住所2`
        , d.`購入者電話番号`
        , d.`購入者ＦＡＸ`
        , d.`購入者メールアドレス`
        , d.`発送先名`
        , d.`発送先カナ`
        , d.`発送先郵便番号`
        , d.`発送先住所1`
        , d.`発送先住所2`
        , d.`発送先電話番号`
        , d.`発送先ＦＡＸ`
        , d.`配送備考`
        , d.`voucher_id`

        , T.`商品コード`
        , T.`商品名`
        , T.`受注数`
        , T.`商品単価`
        , T.`掛率`
        , T.`小計`
        , T.`商品オプション`
        , T.`引当数`
        , T.`引当日`
        , T.`id`

        , pci.colname
        , pci.rowname
      FROM (
        SELECT DISTINCT
            d.`店舗名`
          , d.`伝票番号`
          , d.`受注番号`
          , d.`受注日`
          , d.`取込日`
          , d.`受注状態`
          , d.`発送方法`
          , d.`支払方法`
          , d.`合計金額`
          , d.`税金`
          , d.`手数料`
          , d.`送料`
          , d.`その他`
          , d.`ポイント`
          , d.`承認金額`
          , d.`備考`
          , d.`入金金額`
          , d.`入金区分`
          , d.`入金日`
          , d.`納品書印刷指示日`
          , d.`納品書発行日`
          , d.`納品書備考`
          , d.`出荷日`
          , d.`出荷予定日`
          , d.`作業者欄`
          , d.`ピック指示内容`
          , d.`ラベル発行日`
          , d.`配送日`
          , d.`配送時間帯`
          , d.`配送伝票番号`
          , d.`クレジット区分`
          , d.`名義人`
          , d.`有効期限`
          , d.`承認番号`
          , d.`承認区分`
          , d.`承認日`
          , d.`購入者名`
          , d.`購入者カナ`
          , d.`購入者郵便番号`
          , d.`購入者住所1`
          , d.`購入者住所2`
          , d.`購入者電話番号`
          , d.`購入者ＦＡＸ`
          , d.`購入者メールアドレス`
          , d.`発送先名`
          , d.`発送先カナ`
          , d.`発送先郵便番号`
          , d.`発送先住所1`
          , d.`発送先住所2`
          , d.`発送先電話番号`
          , d.`発送先ＦＡＸ`
          , d.`配送備考`
          , d.`voucher_id`
        FROM tb_shipping_voucher_detail d
        WHERE d.voucher_id = :voucherId
      ) d
      INNER JOIN (
        SELECT
            d.`voucher_id`
          , d.`伝票番号`
          , d.`商品コード`
          , MAX(d.`商品名`) AS 商品名
          , SUM(d.`受注数`) AS 受注数
          , d.`商品単価`
          , d.`掛率`
          , SUM(d.`小計`) AS 小計
          , d.`商品オプション`
          , SUM(d.`引当数`) AS 引当数
          , MAX(d.`引当日`) AS 引当日
          , MIN(d.`id`) AS `id`
        FROM tb_shipping_voucher_detail d
        WHERE d.voucher_id = :voucherId
          AND d.`キャンセル` = 0
        GROUP BY d.`voucher_id`
               , d.`伝票番号`
               , d.`商品コード`
               , d.`商品単価`
               , d.`掛率`
               , d.`商品オプション`
      ) T ON d.`voucher_id` = T.`voucher_id` AND d.伝票番号 = T.伝票番号
      LEFT JOIN tb_productchoiceitems pci ON T.商品コード = pci.ne_syohin_syohin_code
      WHERE d.voucher_id = :voucherId
      ORDER BY T.id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':voucherId', $voucherId, \PDO::PARAM_INT);
    $stmt->execute();

    // 伝票毎にまとめる
    $result = [];
    foreach ($stmt as $detail) {
      $voucherNumber = $detail['伝票番号'];
      if (!isset($result[$voucherNumber])) {
        $voucher = $detail; // 受注伝票情報の代表情報として利用
        $voucher['小計合計'] = 0;
        $voucher['受注合計数量'] = 0;
        $result[$voucherNumber] = [
            'voucher' => $voucher
          , 'details' => []
        ];
      }
      $result[$voucherNumber]['details'][] = $detail;
      $result[$voucherNumber]['voucher']['小計合計'] += $detail['小計'];
      $result[$voucherNumber]['voucher']['受注合計数量'] += $detail['受注数'];
    }

    return $result;
  }

  /**
   * 伝票番号で詳細伝票から住所を取得（複数）
   * @param array $voucherNumbers 伝票番号一覧
   * @return array
   */
  public function getAddressesByVoucherNumbers(array $voucherNumbers) : array
  {
      $dbMain = $this->getConnection('main');

      $sql = <<<EOD
      SELECT
          d.`伝票番号`
        , d.`購入者名`
        , d.`購入者郵便番号`
        , d.`購入者住所1`
        , d.`購入者住所2`
        , d.`購入者電話番号`
        , d.`発送先住所1`
        , d.`発送先住所2`
      FROM tb_shipping_voucher_detail d
      WHERE d.伝票番号 IN(?);
EOD;
      $stmt = $dbMain->executeQuery($sql, array($voucherNumbers), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)); // このテーブルの伝票番号はvarchar
      $result = $stmt->fetchAll();

      $details = [];
      foreach($result as $detail) {
          $voucherNumber = $detail['伝票番号'];
          if (!isset($details[$voucherNumber])) {
              $details[$voucherNumber] = [];
          }
          $details[$voucherNumber] = $detail;
      }

      return $details;
  }

  /**
   * 伝票番号で詳細伝票から配送情報CSVに関わる情報を取得（複数）
   *
   * @param array $voucherNumbers 伝票番号一覧
   * @return array
   */
  public function getShippingInfoByVoucherNumbers(array $voucherNumbers) : array
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT DISTINCT
          d.`伝票番号`
        , d.`購入者名`
        , d.`購入者郵便番号`
        , d.`購入者住所1`
        , d.`購入者住所2`
        , d.`購入者電話番号`
        , d.`発送先名`
        , d.`発送先郵便番号`
        , d.`発送先住所1`
        , d.`発送先住所2`
        , d.`発送先電話番号`
      FROM tb_shipping_voucher_detail d
      WHERE d.伝票番号 IN(?);
EOD;
    $stmt = $dbMain->executeQuery($sql, array($voucherNumbers), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY));
    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
    return $result;
  }

  /**
   * 梱包グループIDをもとに、伝票番号のリストを返却する
   */
  public function getVoucherNumberByPackingId($packingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
          d.`伝票番号`
      FROM tb_shipping_voucher_detail d
      INNER JOIN tb_shipping_voucher v on v.id = d.voucher_id
      WHERE v.shipping_voucher_packing_group_id = :packingId
      ORDER BY d.voucher_id, d.id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':packingId', $packingId, \PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    return array_column($list, '伝票番号');
  }

  /**
   * IDの配列を元に梱包グループIDを更新する
   */
  public function updatePackingGroupByIds($shippingVoucherIds, $packingGroupId) {
    $dbMain = $this->getConnection('main');
    $idsStr = implode(', ', $shippingVoucherIds);
    $sql = <<<EOD
      UPDATE
      tb_shipping_voucher
      SET shipping_voucher_packing_group_id = :packingGroupId
      WHERE
        id IN ( {$idsStr} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':packingGroupId', $packingGroupId);
    $stmt->execute();
  }

  /**
   * 梱包グループ画面 表示のための出荷伝票グループIDが一致する出荷伝票梱包のリストを取得。
   * @param int $shippingVoucherPackingGroupId 出荷伝票グループID
   */
  public function findByGroupIdForPackingGroupIndex($shippingVoucherPackingGroupId)
  {
    $qb = $this->createQueryBuilder('v');
    $qb->select('v.id, v.status, s.username, w.symbol, v.warehouse_daily_number, v.warehouse_id, v.picking_list_date, v.picking_list_number, COUNT(p.id) AS amount');
    $qb->leftJoin(TbWarehouse::class, 'w', JOIN::WITH, 'v.warehouse_id = w.id');
    $qb->leftJoin(SymfonyUsers::class, 's', JOIN::WITH, 'v.packingAccountId = s.id');
    $qb->leftJoin(TbShippingVoucherPacking::class, 'p', JOIN::WITH, 'v.id = p.voucherId');
    $qb->andWhere('v.shippingVoucherPackingGroupId = :shippingVoucherPackingGroupId')
      ->setParameter('shippingVoucherPackingGroupId', $shippingVoucherPackingGroupId);
    $qb->groupBy('v.id');
    return $qb->getQuery()->getResult();
  }

  /**
   * 更新のために指定IDのレコードをロック。
   * @param array $ids
   */
  public function lockForUpdate($ids) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      SELECT
        *
      FROM
        tb_shipping_voucher
      WHERE
        id IN ({$idsStr})
      FOR UPDATE;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  /**
   * 出荷伝票グループIDが一致する出荷伝票グループ情報を取得。
   * @param int $warehouseId 倉庫ID
   * @param string $pickingListDate ピッキング日付
   * @param int $pickingListNumber ピッキング番号
   * @return array 以下7つのキーを持つ連想配列。IDに該当するものがなければnull
   *    'id' => int 出荷伝票グループID
   *    'shippingVoucherPackingId' => int 梱包グループID
   *    'symbol' => string 倉庫略称
   *    'warehouse_daily_number' => int 日別倉庫別連番
   *    'deliveryName' => string 発送方法名
   *    'status' => int 出荷伝票グループステータス
   *    'username' => string 梱包担当者名
   */
  public function findForPackingShippingVoucher($warehouseId, $pickingListDate, $pickingListNumber)
  {
    $qb = $this->createQueryBuilder('v');
    $qb->select('v.id, v.shippingVoucherPackingGroupId, w.symbol, v.warehouse_daily_number, d.deliveryName, v.status, s.username');
    $qb->innerJoin(TbWarehouse::class, 'w', JOIN::WITH, 'v.warehouse_id = w.id');
    $qb->leftJoin(TbShippingVoucherPackingGroup::class, 'g', JOIN::WITH, 'v.shippingVoucherPackingGroupId = g.id');
    $qb->leftJoin(TbDeliveryMethod::class, 'd', JOIN::WITH, 'g.deliveryMethodId = d.deliveryId');
    $qb->leftJoin(SymfonyUsers::class, 's', JOIN::WITH, 'v.packingAccountId = s.id');
    $qb->andWhere('v.warehouse_id = :warehouseId')
      ->setParameter('warehouseId', $warehouseId);
    $qb->andWhere('v.picking_list_date = :pickingListDate')
      ->setParameter('pickingListDate', $pickingListDate);
    $qb->andWhere('v.picking_list_number = :pickingListNumber')
      ->setParameter('pickingListNumber', $pickingListNumber);
    return $qb->getQuery()->getResult();
  }

  /**
   * 出荷伝票明細購入サマリ取得。
   *
   * 引数をもとに、紐づく出荷伝票明細の伝票番号、SKU、受注数を連想配列で返却。
   * @param int $id 出荷伝票グループID
   * @return array
   */
  public function findShippingVoucherDetailPurchaseSummary($shippingVoucherId)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        d.`伝票番号`
        , d.`商品コード`
        , d.`受注数`
        , d.`発送先名`
      FROM
        tb_shipping_voucher v
        INNER JOIN tb_shipping_voucher_detail d
          ON v.id = d.voucher_id
      WHERE
        v.id = :id
      ORDER BY
        d.id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':id', $shippingVoucherId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * ステータスと梱包担当者IDを更新し、更新件数を返却する。
   * @param int $id 出荷伝票グループID
   * @param int $userId ユーザID
   * @return int 更新件数
   */
  public function updateStatusAndPackingAccountId($id, $userId)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shipping_voucher
      SET
        status = :statusPacking
        , packing_account_id = :userId
      WHERE
        id = :id
        AND status = :statusUnprocessedPacking;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':statusPacking', TbShippingVoucher::STATUS_PACKING, \PDO::PARAM_INT);
    $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUnprocessedPacking', TbShippingVoucher::STATUS_UNPROCESSED_PACKAGING, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
  }

  /**
   * ステータスを更新する。
   * @param int $id 出荷伝票梱ID
   * @param int $status ステータス
   */
  public function updateStatus($id, $status)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher
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

  /**
   * 出荷伝票グループと紐づく出荷伝票梱包のIDと倉庫のシンボルを取得する。
   *
   * @param int $voucherId 出荷伝票グループID
   */
  public function findWithSymbolAndPackingIds($voucherId)
  {
    $sql = <<<EOD
      SELECT
        v.id AS id
        , w.symbol AS symbol
        , v.warehouse_daily_number AS warehouseDailyNumber
        , p.id AS packingId
        , p.voucher_number AS voucherNumber
        , d.発送先名 AS shippingAccountName
      FROM
        tb_shipping_voucher v
      INNER JOIN tb_shipping_voucher_packing p
        ON p.voucher_id = v.id
      INNER JOIN tb_warehouse w
        ON w.id = v.warehouse_id
      INNER JOIN tb_shipping_voucher_detail d
        ON d.voucher_id = p.voucher_id
        AND d.伝票番号 = p.voucher_number
      WHERE
        v.id = :id
      GROUP BY
        p.id
      ORDER BY
        d.id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $voucherId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * 指定期間に自社倉庫から出荷した受注の平均明細数を取得する。期間は出荷伝票グループのピッキング日で判定する。
   * ここで取得する平均明細数の計算式は、「明細数 / 伝票数」。受注数は考慮しない。
   *
   * 検索条件には取得期間を指定する。
   * 取得条件は以下の通り。
   * ・購入モールがSHOPLISTではない
   * ・出荷ステータスは問わない（梱包機能がまだ稼働していない）
   * ・同一伝票が、商品不備などで複数回出荷対象となっていても、1件と数える
   *
   * 戻り値は以下の項目の連想配列。
   * ・detail_average 平均明細数
   * 
   * @param array $condition 検索条件の連想配列 
   *         'pickingDateFrom' => ピッキング日の検索範囲（From） YYYY-MM-DD (NotNull)
   *         'pickingDateTo' => ピッキング日の検索範囲（To） YYYY-MM-DD (NotNull)
   * @return array 平均明細数の連想配列
   */
  public function getDetailNumAverage($condition) {
    $db = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        count(distinct 伝票番号, 商品コード) / count(distinct 伝票番号) as detail_average
      FROM tb_shipping_voucher sv
      JOIN tb_shipping_voucher_detail sd ON sv.id = sd.voucher_id
      WHERE sd.店舗名 <> :mallNameShoplist
      AND sv.picking_list_date >= :pickingDateFrom AND sv.picking_list_date <= :pickingDateTo
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':pickingDateFrom', $condition['pickingDateFrom'], \PDO::PARAM_STR);
    $stmt->bindValue(':pickingDateTo', $condition['pickingDateTo'], \PDO::PARAM_STR);
    $stmt->bindValue(':mallNameShoplist', TbShoppingMall::NE_MALL_NAME_SHOPLIST, \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $result;
  }
}
