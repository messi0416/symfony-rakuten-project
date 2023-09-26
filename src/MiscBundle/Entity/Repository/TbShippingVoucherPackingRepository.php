<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbShippingReissueLabel;
use MiscBundle\Entity\TbShippingVoucherPacking;
use Doctrine\ORM\Query\ResultSetMapping;
use forestlib\Doctrine\ORM\LimitableNativeQuery;

/**
 * TbShippingVoucherPackingRepository
 */
class TbShippingVoucherPackingRepository extends BaseRepository
{
  /**
   * 出荷伝票リスト画面 表示のための出荷伝票グループIDが一致する出荷伝票梱包のリストを取得。
   * @param int $shippingVoucherPackingGroupId 出荷伝票グループID
   * @return array
   */
  public function findByVoucherIdForPackingShippingVoucherList($shippingVoucherId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        p.id
        , p.voucher_number
        , p.status
        , p.label_reissue_flg
        , m.delivery_name
        , SUM(d.受注数) AS 商品数
      FROM
        tb_shipping_voucher_packing p
        LEFT JOIN tb_delivery_method m
          ON p.latest_delivery_method_id = m.delivery_id
        INNER JOIN tb_shipping_voucher_detail d
          ON p.voucher_id = d.voucher_id
          AND CAST(p.voucher_number AS CHAR) = d.`伝票番号`
      WHERE
        p.voucher_id = :shippingVoucherId
      GROUP BY
        p.id
      ORDER BY
        d.id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingVoucherId', $shippingVoucherId, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  /**
   *
   * @param int $warehouseId 倉庫ID
   * @param string $pickingListDate ピッキング日付
   * @param int $pickingListNumber ピッキング番号
   * @param int $voucherNumber 伝票番号
   */
  public function findIdByVoucherInfo($warehouseId, $pickingListDate, $pickingListNumber, $voucherNumber)
  {
    $sql = <<<EOD
      SELECT
        p.id AS id
      FROM
        tb_shipping_voucher_packing p
        INNER JOIN tb_shipping_voucher v
          ON p.voucher_id = v.id
      WHERE
        v.warehouse_id = :warehouseId
        AND v.picking_list_date = :pickingListDate
        AND v.picking_list_number = :pickingListNumber
        AND p.voucher_number = :voucherNumber;

EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouseId);
    $stmt->bindValue(':pickingListDate', $pickingListDate);
    $stmt->bindValue(':pickingListNumber', $pickingListNumber);
    $stmt->bindValue(':voucherNumber', $voucherNumber);
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC)['id'];
  }

  /**
   * 出荷伝票梱包のステータスと出荷伝票グループのステータスを取得
   * @param int $id 出荷伝票梱包ID
   * @return array 出荷伝票梱包のステータスと出荷伝票グループのステータスの連想配列
   */
  public function findStatusWithShippingVoucherStatus($id)
  {
    $sql = <<<EOD
      SELECT
          p.id AS id
          , p.status AS packingStatus
          , v.status AS voucherStatus
      FROM
          tb_shipping_voucher_packing p
          INNER JOIN tb_shipping_voucher v
              ON p.voucher_id = v.id
      WHERE
          p.id = :id;

EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }
  /**
   * 紐づいている出荷伝票明細と一緒に出荷伝票梱包を取得する。
   * @param int $id 出荷伝票梱包ID
   * @return array 出荷伝票梱包と出荷伝票明細の連想配列
   */
  public function findWithDetail($id)
  {
    $sql = <<<EOD
      SELECT
          p.id AS id
          , p.voucher_id AS voucherId
          , p.voucher_number AS voucherNumber
          , d.発送先名 AS shippingAccountName
          , sd.納品書特記事項 AS notices
          , p.label_reissue_flg AS labelReissueFlg
          , m.delivery_name AS deliveryName
          , p.valid_inquiry_number_status AS validInquiryNumberStatus
          , p.updated AS updated
          , p.status AS packingStatus
          , v.status AS voucherStatus
          , d.id AS detailId
          , d.商品コード AS skucode
          , mp.picfolderP1 AS imageDir
          , mp.picnameP1   AS imageFile
          , piv.address AS variationImagePath
          , d.status AS detailStatus
          , d.受注数 AS requiredAmount
          , d.assign_num AS assignNum
          , d.updated AS detailUpdated
          , COALESCE(dpl.emptyLocationCount, 0) AS emptyLocationCount
          , v.warehouse_id AS warehouseId
          , v.picking_list_date AS pickingListDate
          , v.picking_list_number AS pickingListNumber
          , g.delivery_method_id AS groupDeliveryMethodId
          , g.id AS packingGroupId
          , li.picking_account_name AS pickingAccountName
          , w.symbol AS symbol
          , v.warehouse_daily_number AS warehouseDailyNumber
      FROM
          tb_shipping_voucher_packing p
          INNER JOIN tb_shipping_voucher_detail d
              ON p.voucher_id = d.voucher_id
              AND CAST(p.voucher_number AS CHAR) = d.伝票番号
          INNER JOIN tb_shipping_voucher v
              ON p.voucher_id = v.id
          INNER JOIN tb_shipping_voucher_packing_group g
              ON v.shipping_voucher_packing_group_id = g.id
          INNER JOIN tb_productchoiceitems i
              ON d.商品コード = i.ne_syohin_syohin_code
          INNER JOIN tb_mainproducts mp
              ON i.daihyo_syohin_code = mp.daihyo_syohin_code
          INNER JOIN tb_sales_detail sd
              ON d.伝票番号 = sd.伝票番号
              AND d.商品コード = sd.商品コード（伝票）
          INNER JOIN v_delivery_picking_list_index li
              ON li.warehouse_id = v.warehouse_id
              AND li.number = v.picking_list_number
              AND li.date = v.picking_list_date
          INNER JOIN tb_warehouse w
              ON w.id = v.warehouse_id
          LEFT JOIN product_images_variation piv
              ON piv.daihyo_syohin_code = i.daihyo_syohin_code
              AND CASE
                WHEN mp.カラー軸 = 'row'
                  THEN i.rowcode = piv.variation_code
                AND piv.code = 'row'
                WHEN mp.カラー軸 = 'col'
                  THEN i.colcode = piv.variation_code
                AND piv.code = 'col'
                END
          LEFT JOIN tb_delivery_method m
              ON p.latest_delivery_method_id = m.delivery_id
          LEFT JOIN (
            SELECT
                date
                , number
                , warehouse_id
                , 商品コード
                , count(current_location) AS emptyLocationCount
            FROM
                tb_delivery_picking_list
            WHERE
                current_location = ''
            GROUP BY
                `date`
                , number
                , warehouse_id
                , 商品コード
          ) dpl
            ON v.warehouse_id = dpl.warehouse_id
            AND v.picking_list_number = dpl.number
            AND v.picking_list_date = dpl.date
            AND d.商品コード = dpl.商品コード
      WHERE
          p.id = :id;

EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 更新のため引数のIDのレコードをロックする。
   * @param array $ids 出荷伝票梱包のID
   */
  public function lockForUpdate($ids)
  {
    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      SELECT
          *
      FROM
          tb_shipping_voucher_packing
      WHERE
          id IN ({$idsStr}) FOR UPDATE;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->execute();
  }

  /**
   * 配送方法と有効なお問い合わせ番号ステータスを変更する。
   * @param int $id 出荷伝票梱包のID
   * @param int $deliveryMethodId 発送方法ID
   * @param int $validInquiryNumberStatus 有効なお問い合わせ番号ステータス
   * @param string 出荷伝票梱包の更新日時
   * @param int $userId ユーザーID
   * @return int 更新件数
   */
  public function updateDeliveryMethodAndValidInquiryNumberStatus($id, $deliveryMethodId, $validInquiryNumberStatus, $packingUpdated, $userId) {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_packing
      SET
        label_reissue_flg = true
        , latest_delivery_method_id = :deliveryMethodId
        , valid_inquiry_number_status = :validInquiryNumberStatus
        , update_account_id = :userId
      WHERE
        id = :id;
        AND updated = :updated
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':deliveryMethodId', $deliveryMethodId);
    $stmt->bindValue(':validInquiryNumberStatus', $validInquiryNumberStatus);
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':updated', $packingUpdated);
    $stmt->execute();
    return $stmt->rowCount();
  }

  /**
   * 出荷伝票梱包の更新日を取得
   * @param int $id 出荷伝票梱包ID
   * @return array 出荷伝票梱包の連想配列
   */
  public function findUpdated($id)
  {
    $sql = <<<EOD
      SELECT
          id
          , updated
      FROM
        tb_shipping_voucher_packing
      WHERE
        id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * 梱包関連のテーブルの各IDを取得する。
   * @param int $id
   * @return array 連想配列
   *    'packingId' => 出荷伝票梱包ID
   *    'voucherId' => 出荷伝票グループID
   *    'groupId'   => 梱包グループID
   *    'detailId'  => 出荷伝票明細ID
   */
  public function findPackingRelationId($id)
  {
    $sql = <<<EOD
      SELECT
          p.id AS packingId
          , v.id AS voucherId
          , g.id AS groupId
          , d.id AS detailId
          , l.id AS labelId
      FROM
          tb_shipping_voucher_packing p
          INNER JOIN tb_shipping_voucher v
              ON v.id = p.voucher_id
          INNER JOIN tb_shipping_voucher_packing_group g
              ON g.id = v.shipping_voucher_packing_group_id
          INNER JOIN tb_shipping_voucher_detail d
              ON d.voucher_id = p.voucher_id
              AND d.伝票番号 = CAST(p.voucher_number AS CHAR)
          LEFT JOIN tb_shipping_reissue_label l
              ON l.shipping_voucher_packing_id = p.id
      WHERE
          p.id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * ステータスを更新する。
   * @param int $id 出荷伝票梱ID
   * @param int $status ステータス
   * @param int $userId ユーザーID
   */
  public function updateStatus($id, $status, $userId)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_packing
      SET
        status = :status
        , update_account_id = :userId
      WHERE
        id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
    $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * IDで出荷伝票梱包と紐づく発送方法を返す
   * @param int $id 出荷伝票梱包Id
   * @return array 出荷伝票梱包と発送方法の連想配列
   */
  public function findWithDeliveryMethod($id)
  {
    $sql = <<<EOD
      SELECT
        p.id AS id
        , p.status AS status
        , p.latest_delivery_method_id AS deliveryMethodId
        , p.label_reissue_flg AS labelReissueFlg
        , p.valid_inquiry_number_status AS validInquiryNumberStatus
        , m.inquiry_number_need_flg AS inquiryNumberNeedFlg
      FROM
        tb_shipping_voucher_packing p
        LEFT JOIN tb_delivery_method m
          ON p.latest_delivery_method_id = m.delivery_id
      WHERE
        p.id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * 有効なお問い合わせ番号ステータスを変更する。
   * @param int $id 出荷伝票梱包のID
   * @param int $validInquiryNumberStatus 有効なお問い合わせ番号ステータス
   * @param string 出荷伝票梱包の更新日時
   * @param int $userId ユーザーID
   * @return int 更新件数
   */
  public function updateValidInquiryNumberStatus($id, $validInquiryNumberStatus, $packingUpdated, $userId)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_packing
      SET
        valid_inquiry_number_status = :validInquiryNumberStatus
        , update_account_id = :userId
      WHERE
        id = :id
        AND updated = :updated
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':validInquiryNumberStatus', $validInquiryNumberStatus);
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':updated', $packingUpdated);
    $stmt->execute();
    return $stmt->rowCount();
  }

  /**
   * ラベル再発行のための有効なお問い合わせ番号ステータス更新。
   *
   * 引数をもとに、有効なお問い合わせ番号ステータスについて、「ラベル発行待ち」で、
   * 且つ、配送方法のお問い合わせ番号利用フラグが「お問い合わせ番号が不要な配送」ではない場合、
   * 「有効なお問い合わせ番号がある」に更新する。
   * @param array $ids 出荷伝票梱包IDの配列
   * @param int $accountId ログインユーザID
   */
  public function updateValidInquiryNumberStatusForReissueLabel($ids, $accountId)
  {
    $idsStr = implode(', ', $ids);
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_packing p
        INNER JOIN tb_delivery_method d
          ON p.latest_delivery_method_id = d.delivery_id
      SET
        p.update_account_id = :accountId,
        p.valid_inquiry_number_status = :exist
      WHERE
        p.id IN ({$idsStr})
        AND p.valid_inquiry_number_status = :wait
        AND d.inquiry_number_need_flg <> 0;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':accountId', $accountId, \PDO::PARAM_INT);
    $stmt->bindValue(':exist', TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_EXIST, \PDO::PARAM_INT);
    $stmt->bindValue(':wait', TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_WAIT, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 保留のステータスの1週間以内のデータの出荷伝票梱包のページングをしたものを返す。
   * 取得件数は50件固定。
   * @param int $page ページ数
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findHoldShippingVoucherPaging($page)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();
    $sqlSelect = <<<EOD
      SELECT
        p.voucher_number AS voucherNumber
        , v.warehouse_id AS warehouseId
        , v.picking_list_number AS pickingListNumber
        , v.picking_list_date as pickingListDate
        , w.symbol AS symbol
        , v.warehouse_daily_number AS warehouseDailyNumber
        , d.発送先名 AS shippingAccountName
        , sd.納品書特記事項 AS notices
EOD;
    $sqlBody = <<<EOD
      FROM
        tb_shipping_voucher_packing p
        INNER JOIN tb_shipping_voucher v
          ON p.voucher_id = v.id
        INNER JOIN tb_warehouse w
          ON w.id = v.warehouse_id
        INNER JOIN tb_sales_detail sd
          ON p.voucher_number = sd.伝票番号
          AND sd.明細行 = 1 /* 納品書特記事項は共通なので、先頭だけ取ればOK */
        INNER JOIN tb_shipping_voucher_detail d
          ON CAST(p.voucher_number AS CHAR) = d.伝票番号
          AND d.商品コード = sd.商品コード（伝票）
      WHERE
        p.status = :status
        AND v.picking_list_date >= :date
EOD;
    $rsm =  new ResultSetMapping();
    $rsm->addScalarResult('voucherNumber', 'voucherNumber', 'integer');
    $rsm->addScalarResult('warehouseId', 'warehouseId', 'integer');
    $rsm->addScalarResult('pickingListNumber', 'pickingListNumber', 'integer');
    $rsm->addScalarResult('pickingListDate', 'pickingListDate', 'datetime');
    $rsm->addScalarResult('symbol', 'symbol', 'string');
    $rsm->addScalarResult('warehouseDailyNumber', 'warehouseDailyNumber', 'string');
    $rsm->addScalarResult('shippingAccountName', 'shippingAccountName', 'string');
    $rsm->addScalarResult('notices', 'notices', 'string');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setParameter(':status', TbShippingVoucherPacking::STATUS_ON_HOLD, \PDO::PARAM_INT);
    $query->setParameter(':date', (new \DateTime())->modify('-7 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $query->setOrders(['p.id' => 'DESC']);

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    return $paginator->paginate($query, $page, 50);
  }
}
