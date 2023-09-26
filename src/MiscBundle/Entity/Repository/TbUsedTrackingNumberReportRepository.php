<?php

namespace MiscBundle\Entity\Repository;

use Exception;
use MiscBundle\Entity\TbShipmentTrackingNumber;
use MiscBundle\Exception\BusinessException;

/**
 * TbUsedTrackingNumberReportRepository
 */
class TbUsedTrackingNumberReportRepository extends BaseRepository
{
  /**
   * 指定した数の最新のレコードを除き、過去の発行済発送伝票番号報告CSV情報を削除する
   *
   * @param int $retainRecordCount 残すべき最新のレコード数
   */
  public function deleteOldRecordsExceptLatest($retainRecordCount)
  {
    $sql = <<<EOD
      DELETE FROM tb_used_tracking_number_report
      WHERE id NOT IN (
          SELECT id FROM (
              SELECT id
              FROM tb_used_tracking_number_report
              ORDER BY id DESC
              LIMIT :retainRecordCount
          ) tmp
      )
EOD;

    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':retainRecordCount', $retainRecordCount, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 発行済発送伝票番号報告CSV情報及び紐づく発送伝票番号数の情報を取得する
   *
   * @return array
   */
  public function findAllAndTrackingNumberCount()
  {
    $sql = <<<EOD
      SELECT
        r.id,
        r.created,
        r.delivery_method_id AS deliveryId,
        T.trackingNumberCount,
        r.download_count_edi downloadCountEdi,
        r.download_count_ne downloadCountNe
      FROM
        tb_used_tracking_number_report r
        JOIN (
          SELECT
            used_tracking_number_report_id reportId,
            COUNT(tracking_number) trackingNumberCount
          FROM
            tb_shipment_tracking_number
          GROUP BY
            used_tracking_number_report_id
        ) T
          ON r.id = T.reportId
      ORDER BY
        r.id DESC
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  /**
   * 発行済発送伝票番号報告CSVテーブルにレコードを追加し、発送伝票番号と紐づける
   *
   * @param string $deliveryMethodId 発送方法id
   * @param array $trackingNumbers 発行済発送伝票番号のリスト
   */
  public function generateUsedReport($deliveryMethodId, $trackingNumbers)
  {
    $dbMain = $this->getConnection('main');

    $trackingNumbersStr = "'" . implode("', '", $trackingNumbers) . "'";

    $sql = <<<EOD
      SELECT
        tracking_number,
        status
      FROM
        tb_shipment_tracking_number
      WHERE
        tracking_number IN ( {$trackingNumbersStr} )
      FOR UPDATE
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $targetCount = $stmt->rowCount();
    if (count($trackingNumbers) !== $targetCount) {
      throw new BusinessException('対象の発送伝票番号情報を正しく取得できませんでした。');
    }
    $targets = $stmt->fetchAll();
    

    // すべてのステータスが使用済であることを確認
    $allStatusIssued = true;
    foreach ($targets as $row) {
      if ($row['status'] != TbShipmentTrackingNumber::STATUS_USED) {
        $allStatusIssued = false;
        break;
      }
    }

    // ステータスがすべて0でない場合は、例外を投げる
    if (!$allStatusIssued) {
      throw new BusinessException('割込でデータの更新がありました。');
    }

    // 使用済発送伝票番号報告CSVテーブルにレコード追加
    $sql = <<<EOD
      INSERT INTO tb_used_tracking_number_report (delivery_method_id)
      VALUES ( :deliveryMethodId )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryMethodId', $deliveryMethodId, \PDO::PARAM_STR);
    $stmt->execute();
    $reportId = $dbMain->lastInsertId();

    // 発送伝票番号テーブルの該当発送伝票番号を報告CSV作成済みとし、
    // 先に作成した、使用済発送伝票番号報告CSVと紐づける。
    $sql = <<<EOD
      UPDATE
        tb_shipment_tracking_number
      SET
        used_tracking_number_report_id = :reportId,
        status = :statusReportedCsv
      WHERE
        tracking_number IN ( {$trackingNumbersStr} )
        AND status = :statusUsed
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':reportId', $reportId, \PDO::PARAM_INT);
    $stmt->bindValue(':statusReportedCsv', TbShipmentTrackingNumber::STATUS_REPORTED_CSV, \PDO::PARAM_INT);
    $stmt->bindValue(':statusUsed', TbShipmentTrackingNumber::STATUS_USED, \PDO::PARAM_INT);
    $stmt->execute();

    if ($targetCount !== $stmt->rowCount()) {
      throw new BusinessException('発送伝票番号ステータス更新対象を正しく取得できませんでした。');
    }
  }
}
