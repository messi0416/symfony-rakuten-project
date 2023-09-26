<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbMainproductsImportability;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;

/**
 * TbMaintenanceScheduleRepository
 */
class TbMaintenanceScheduleRepository extends BaseRepository
{
  
  /**
   * 現在から、指定分数までの間にメンテがあるかチェックする。isMaintenanceWithTermのショートカット。
   * @param array $typeList 影響を受けるメンテナンス種別の配列
   * @param int $withinMinutes 指定分数。例） 30: 現在から30分以内に、
   */
  public function isMaintenance($typeList, $withinMinutes) {
    $now = new \DateTimeImmutable();
    $to = $now->modify("+ $withinMinutes minute");
    return $this->isMaintenanceWithTerm($typeList, $now, $to);
  }
  
  
  /**
   * 指定期間内にメンテナンスがあるかどうかを判定し、あれば true、なければ false を返却する
   * 例えば1時間程度かかるバッチを実行する場合、startに現在、endに1時間後を指定し、typeには影響を受けるメンテナンス種別のリストを指定して呼び出す。
   * 開始時刻・終了時刻は、メンテナンスの開始・終了ではなく、バッチ等の開始・終了（予定）。
   * 
   * @param array $typeList 影響を受けるメンテナンス種別の配列
   * @param \DateTime $startDate チェック開始日時
   * @param \DateTime $endDate チェック終了日時
   */
  public function isMaintenanceWithTerm($typeList, $dateFrom, $dateTo) {
    $dbMain = $this->getConnection('main');
    $typeListStr = implode(',', $typeList);
    $sql = <<<EOD
      SELECT
        count(*)
      FROM
        tb_maintenance_schedule ms
      WHERE 
        start_datetime <= :checkEndDatetime
        AND end_datetime >= :checkStartDatetime
        AND delete_flg = 0
        AND maintenance_type IN ( {$typeListStr} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':checkEndDatetime', $dateTo->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':checkStartDatetime', $dateFrom->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $cnt = intval($stmt->fetchColumn(0));
    return $cnt ? true : false;
  }
  
}