<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbShipmentTrackingNumber;
use MiscBundle\Exception\BusinessException;

/**
 * TbShipmentTrackingNumberRepository
 */
class TbShipmentTrackingNumberRepository extends BaseRepository
{
  /**
   * 指定された伝票番号のステータスをキャンセルに更新する。
   *
   * ただし、特定の発送方法IDが指定された場合、その発送方法のものは更新対象から除外する。
   * @param array $voucherNumbers 伝票番号の配列
   * @param int|null $excludeDeliveryMethodId 除外する発送方法ID
   * @return int 更新された行数
   */
  public function updateStatusToCancelled($voucherNumbers, $excludeDeliveryMethodId = null)
  {
    $voucherNumbersStr = "'" . implode("','", $voucherNumbers) . "'";
    $sql = <<<EOD
      UPDATE
        tb_shipment_tracking_number
      SET
        status = :statusCancelled
      WHERE
        voucher_number IN ( {$voucherNumbersStr} )
        AND status = :statusUsed
EOD;
    if ($excludeDeliveryMethodId !== null) {
      $sql .= " AND delivery_method_id <> :excludeDeliveryMethodId";
    }

    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':statusCancelled', TbShipmentTrackingNumber::STATUS_CANCELLED, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);

    if ($excludeDeliveryMethodId !== null) {
      $stmt->bindValue(':excludeDeliveryMethodId', $excludeDeliveryMethodId, \PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->rowCount();
  }

  /**
   * 指定伝票番号のうち、指定発送方法の発送伝票番号が割当済のものについて、その対応関係を返却する。
   * 
   * 伝票番号をキー、発送伝票番号を値とした連想配列の配列を返す。
   * @param array $voucherNumbers 伝票番号の配列
   * @return array
   */
  public function findAssignedTrackingNumbers($voucherNumbers, $deliveryMethodId)
  {
    $voucherNumbersStr = "'" . implode("','", $voucherNumbers) . "'";
    $sql = <<<EOD
      SELECT
        voucher_number,
        tracking_number
      FROM
        tb_shipment_tracking_number
      WHERE
        voucher_number IN ( {$voucherNumbersStr} )
        AND delivery_method_id = :deliveryMethodId
        AND status = :statusUsed
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryMethodId', $deliveryMethodId, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /**
   * 指定配送方法の、未発行の発送伝票番号を、指定件数分配列で返す
   *
   * @param int $count
   * @return array
   */
  public function findUnUsedTrackingNumbers($deliveryMethodId, $count)
  {
    $em = $this->getContainer()->get('doctrine');
    $repo = $em->getRepository('MiscBundle:TbShipmentTrackingNumber');
    $unUsedList = $repo->findBy(
      [
        'deliveryMethodId' => $deliveryMethodId,
        'status' => TbShipmentTrackingNumber::STATUS_UNUSED,
      ],
      ['trackingNumber' => 'ASC'],
      $count
    );

    return array_map(function ($item) {
      return $item->getTrackingNumber();
    }, $unUsedList);
  }

  /**
   * 伝票番号と発送伝票番号のマップ及び配送方法より発送伝票番号テーブルを一括更新する
   *
   * @param array $voucherTrackingMaps 伝票番号と発送伝票番号のマップの配列
   * @param int $deliveryMethodId 配送方法ID
   */
  public function updateTrackingNumbersWithVouchers($voucherTrackingMaps, $deliveryMethodId)
  {
    // コネクションを取得
    $dbMain = $this->getConnection('main');

    // 伝票番号と発送伝票番号の一覧を作成
    $voucherNumbersStr = "'" . implode("', '", array_keys($voucherTrackingMaps)) . "'";
    $trackingNumbersStr = "'" . implode("', '", array_values($voucherTrackingMaps)) . "'";

    // まず、関連する行をロックするためのSELECT文を実行
    $lockSql = <<<EOD
      SELECT
        *
      FROM
        tb_shipment_tracking_number
      WHERE
        tracking_number IN ( $trackingNumbersStr )
        OR voucher_number IN ( $voucherNumbersStr )
      FOR UPDATE
EOD;
    $stmt = $dbMain->prepare($lockSql);
    $stmt->execute();

    // ロックがかかった後でステータスをチェック
    $checkSql = <<<EOD
      SELECT
        COUNT(*)
      FROM
        tb_shipment_tracking_number
      WHERE
        (
          tracking_number IN ( $trackingNumbersStr )
          AND status <> :statusUnused
        )
        OR (
          voucher_number IN ( $voucherNumbersStr )
          AND status = :statusUsed
        )
EOD;
    $stmt = $dbMain->prepare($checkSql);
    $stmt->bindValue(':statusUnused', TbShipmentTrackingNumber::STATUS_UNUSED, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
      // 使用中のレコードが存在する場合、例外をスロー
      throw new BusinessException('割込でデータの更新がありました。');
    }

    // SQLクエリの準備
    $sql = "UPDATE tb_shipment_tracking_number SET voucher_number = CASE tracking_number ";

    $i = 1;
    $params = [];
    foreach ($voucherTrackingMaps as $voucherNumber => $trackingNumber) {
      $sql .= "WHEN :trackingNumber{$i} THEN :voucherNumber{$i} ";
      $params['trackingNumber' . $i] = $trackingNumber;
      $params['voucherNumber' . $i] = $voucherNumber;
      $i++;
    }

    $sql .= "END, delivery_method_id = :deliverMethodId, status = :statusUsed, used_datetime = :now ";
    $sql .= "WHERE tracking_number IN ({$trackingNumbersStr})";

    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $now = (new \DateTime())->format('Y-m-d H:i:s');
    $stmt->bindValue(':deliverMethodId', $deliveryMethodId, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);
    $stmt->bindValue(':now', $now, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 条件に一致する、使用済かつ出荷確定済の発送伝票番号リストを配送方法毎に返す
   *
   * @param array $conditions 検索条件
   * @return array
   */
  public function findUsedTrackingNumbers($conditions)
  {
    $wheres = [];
    $params = [];
    if ($conditions['targetFrom']) {
      $wheres[] = 'used_datetime >= :targetFrom';
      $params[':targetFrom'] = $conditions['targetFrom'];
    }
    if ($conditions['targetTo']) {
      $wheres[] = 'used_datetime <= :targetTo';
      $params[':targetTo'] = $conditions['targetTo'];
    }
    if ($conditions['voucherNumber']) {
      $wheres[] = 'voucher_number = :voucherNumber';
      $params[':voucherNumber'] = $conditions['voucherNumber'];
    }
    $addWhere = '';
    if ($wheres) {
      $addWhere = ' AND ' . implode(' AND ', $wheres);
    }

    $sql = <<<EOD
      SELECT
        t.delivery_method_id,
        GROUP_CONCAT(DISTINCT t.tracking_number)
      FROM
        tb_shipment_tracking_number t
        JOIN tb_sales_detail_analyze a
          ON t.voucher_number = a.伝票番号
      WHERE
        t.status = :statusUsed
        AND a.受注状態 = :orderStatusValueFix
        {$addWhere}
      GROUP BY
        t.delivery_method_id
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);
    $stmt->bindValue(':orderStatusValueFix', TbSalesDetail::ORDER_STATUS_VALUE_FIX, \PDO::PARAM_STR);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $result = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    $formatResult = [];
    foreach ($result as $shippingMethod => $trackingNumberStr) {
      $formatResult[$shippingMethod] = explode(',', $trackingNumberStr);
    }
    return $formatResult;
  }

  /**
   * NEと異なる発送方法の発送伝票番号が割り当てられた伝票番号とそのNE発送方法名を返す。
   *
   * 厳密には、NEの値として、tb_sales_detail_analyzeの値を参照している。
   * 最新のNEの値とは異なる可能性は有るので注意。
   * 返り値は以下の形の連想配列となる。
   * [
   *   '8027653' => 'ゆうパック',
   *   '8027457' => '定形外郵便',
   * ]
   * @param array $trackingNumbers 伝票番号の配列
   * @param int $deliveryMethodId 発送方法ID
   * @return array 伝票番号をキー、NE発送方法名を値とした連想配列の配列
   */
  public function findDiffDeliveryListFromNE($trackingNumbers, $deliveryMethodId)
  {
    $trackingNumbersStr = "'" . implode("','", $trackingNumbers) . "'";
    $sql = <<<EOD
      SELECT
        t.voucher_number AS voucherNumber,
        a.発送方法 AS neDeliveryName
      FROM
        tb_shipment_tracking_number t
        JOIN tb_sales_detail_analyze a
          ON t.voucher_number = a.伝票番号
      WHERE
        t.tracking_number IN ( {$trackingNumbersStr} )
        AND t.delivery_method_id = :deliveryMethodId
        AND t.status = :statusUsed
        AND t.delivery_method_id <> a.配送方法コード
      GROUP BY
        t.voucher_number
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryMethodId', $deliveryMethodId, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  /**
   * 使用済発送伝票番号報告CSVテーブルidに紐づく、伝票番号と発送伝票番号のペアを配列で返却
   *
   * @param int $reportId 使用済発送伝票番号報告CSVテーブルid
   * @return array
   */
  public function findVoucherAndTrackingNumberPairs($reportId)
  {
    $sql = <<<EOD
      SELECT
        t.voucher_number voucherNumber,
        t.tracking_number trackingNumber
      FROM
        tb_shipment_tracking_number t
        JOIN tb_used_tracking_number_report r
          ON t.used_tracking_number_report_id = r.id
      WHERE
        r.id = :reportId
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':reportId', $reportId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }
}
