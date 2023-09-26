<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\DBAL\Connection;

/**
 * TbProductSalesAccountAggregateReservationRepository
 */
class TbProductSalesAccountAggregateReservationRepository extends BaseRepository
{
  /**
   * 最大IDを返却。
   * @param null|Connection $connection
   * @return int
   */
  public function findMaxId($connection = null)
  {
    $db = $connection ?? $this->getEntityManager()->getConnection();
    $sql = <<<EOD
      SELECT
        MAX(id) maxId
      FROM
        tb_product_sales_account_aggregate_reservation
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    return intval($result['maxId']);
  }

  /**
   * 未集計のデータを、代表商品毎に受注日From・受注日Toを広くとって配列で返却する。
   * @param int $maxId 最大id
   * @param null|Connection $connection
   * @return array
   */
  public function findUnaggregatedData($maxId = 0, $connection = null)
  {
    $db = $connection ?? $this->getEntityManager()->getConnection();
    $addWhere = $maxId > 0 ? "AND id <= {$maxId}" : '';
    $sql = <<<EOD
      SELECT
        MIN(ordrer_date_from) orderDateFrom,
        MAX(ordrer_date_to) orderDateTo,
        daihyo_syohin_code daihyoSyohinCode,
        GROUP_CONCAT(id) ids
      FROM
        tb_product_sales_account_aggregate_reservation
      WHERE
        aggregated_flg = 0
        {$addWhere}
      GROUP BY
        daihyo_syohin_code
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 指定したid以下を全て集計済に更新する
   * @param int $maxId 更新対象の最大id
   */
  public function updateAggregatedFlagsBelowId($maxId)
  {
    $db = $this->getEntityManager()->getConnection();
    $sql = <<<EOD
      UPDATE
        tb_product_sales_account_aggregate_reservation
      SET
        aggregated_flg = 1
      WHERE
        aggregated_flg = 0
        AND id <= :maxId
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':maxId', $maxId, \PDO::PARAM_INT);
    $stmt->execute();
  }
}
