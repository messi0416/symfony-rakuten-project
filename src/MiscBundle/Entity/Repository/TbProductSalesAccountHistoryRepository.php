<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbProductSalesAccountHistoryRepository
 */
class TbProductSalesAccountHistoryRepository extends BaseRepository
{
  /**
   * 代表コード、ユーザーID、更新日(FROM, TO)で商品売上担当者更新履歴のリストを取得
   * @param array $form (code, userId, targetDateFrom, targetDateTo)
   * @return mixed
   */
  public function findByDaihyoSyohinCodeAndUserIdAndUpdatedFromTo($form = [])
  {
    $daihyoSyohinCode = $form['code'];
    $userId = $form['userId'];
    $updatedFrom = $form['updatedFrom'];
    $updatedTo = $form['updatedTo'];

    $wheres = [];
    $params = [];
    if ($updatedFrom) {
      $target = (new \DateTimeImmutable($updatedFrom))->setTime(0, 0, 0);
      $wheres[] = 'h.updated >= :updatedFrom';
      $params[':updatedFrom'] = $target->format('Y-m-d H:i:s');
    }
    if ($updatedTo) {
      $target = (new \DateTimeImmutable($updatedTo))->setTime(23, 59, 59);
      $wheres[] = 'h.updated <= :updatedTo';
      $params[':updatedTo'] = $target->format('Y-m-d H:i:s');
    }
    if ($daihyoSyohinCode) {
      $wheres[] = 'a.daihyo_syohin_code LIKE :daihyoSyohinCode';
      $params[':daihyoSyohinCode'] = $daihyoSyohinCode . '%';
    }
    if ($userId) {
      $wheres[] = 'a.user_id = :userId';
      $params[':userId'] = $userId;
    }
    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $sql = <<<EOD
      SELECT
        h.id,
        DATE_FORMAT(h.updated, '%Y-%m-%d') AS updated,
        GROUP_CONCAT(a.daihyo_syohin_code SEPARATOR ',') AS codes,
        m.daihyo_syohin_name AS daihyoSyohinName,
        u.username,
        t.team_name AS teamName,
        pt.task_name AS taskName,
        h.process_type AS processType,
        h.note
      FROM
        tb_product_sales_account_history h
        INNER JOIN tb_product_sales_account_history_mapping ah
          ON h.id = ah.product_sales_account_history_id
        INNER JOIN tb_product_sales_account a
          ON ah.product_sales_account_id = a.id
        INNER JOIN symfony_users u
          ON a.user_id = u.id
        INNER JOIN tb_team t
          ON a.team_id = t.id
        INNER JOIN tb_product_sales_task pt
          ON a.product_sales_task_id = pt.id
        LEFT JOIN tb_mainproducts m
          ON a.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE
        1 {$addWheres}
      GROUP BY
        h.id
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 代表コード(前方一致)、ユーザーIDの商品売上担当者に紐づくで商品売上担当者更新履歴のIDのリストを返す
   * 0件の場合エラーを返す。
   * @param $daihyoSyohinCode
   * @param $userId
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  private function findIdsByDaihyoSyohinCodeAndUserId($daihyoSyohinCode, $userId)
  {
    $qb = $this->createQueryBuilder('h')
      ->innerJoin('h.productSalesAccounts', 'a')
      ->select('h.id');
    if ($daihyoSyohinCode) {
      $qb->andWhere('a.daihyoSyohinCode LIKE :daihyoSyohinCode')
        ->setParameter('daihyoSyohinCode', sprintf('%s%%', $daihyoSyohinCode));
    }
    if ($userId) {
      $qb->andWhere('a.userId = :userId')
        ->setParameter('userId', $userId);
    }
    $ids = [];

    foreach ($qb->getQuery()->getResult() as $history) {
      $ids[] = $history['id'];
    }
    if (count($ids) === 0) {
      throw new \RuntimeException("対象データがありません。");
    }
    return $ids;
  }
}
