<?php

namespace MiscBundle\Entity\Repository;

/**
 * Class
 */
class AnalyzedSalesDetailRepository extends BaseRepository
{
  /**
   * @param \DateTime $startDate
   * @param \DateTime $endDate
   * @param \MiscBundle\Util\BatchLogger $logger
   * @throws \Doctrine\DBAL\DBALException
   */
  public function deleteByDateRange($startDate, $endDate, $logger)
  {
    // getContainer がコンテキストによってはうまく動かない。
    // $logger = $this->getContainer()->get('misc.util.batch_logger');

    $logTitle = '伝票毎集計　過去データ削除：';
    $logStartDate = $startDate ? $startDate->format('Y-m-d') : '';
    $logEndDate = $endDate ? $endDate->format('Y-m-d') : '';
    $logger->info($logTitle . '対象期間[' . $logStartDate . '～' . $logEndDate . ']');

    $db = $this->getEntityManager()->getConnection();

    // 受注日が数値型（int）のテーブル一覧
    $numOrderDateTables = [
      'tb_sales_detail_summary_item_ym',
    ];
    $meta = $this->getClassMetadata();
    $tableName = $meta->getTableName();
    $isNumOrderDate = in_array($tableName, $numOrderDateTables, true);

    // 全削除
    if (!$startDate && !$endDate) {

      $logger->info($logTitle . 'ALL TRUNCATE (1)!!');

      $db->query(sprintf('TRUNCATE `%s`', $tableName));

    } else {

      // QueryBuilder 再挑戦。必要なところのみSchema定義
      $qb = $this->createQueryBuilder('t');
      $qb->delete();

      if ($meta->hasField('orderDate')) {

        $logger->info($logTitle . 'delete by day range!');

        $fieldName = 't.orderDate';

        if ($startDate) {
          $qb->andWhere(sprintf('%s >= :startDate', $fieldName))
              ->setParameter('startDate', $startDate->format('Y-m-d'));
        }
        if ($endDate) {
          $qb->andWhere(sprintf('%s <= :endDate', $fieldName))
              ->setParameter('endDate', $endDate->format('Y-m-d'));
        }

        $logger->info($logTitle . $qb->getDQL());
        // $qb->getQuery()->execute();
        $result = $qb->getQuery()->getResult();
        $logger->info($logTitle . print_r($result, true));

      } else if ($meta->hasField('orderYM') || $meta->hasField('yyyymm')) {

        $logger->info($logTitle . 'delete by month range!');

        $fieldName = $meta->hasField('orderYM') ? 't.orderYM' : 't.yyyymm';

        if ($startDate) {
          $startParam = $isNumOrderDate ? (int)($startDate->format('Ym')) : $startDate->format('Ym');
          $qb->andWhere(sprintf('%s >= :startMonth', $fieldName))
              ->setParameter('startMonth', $startParam);
        }
        if ($endDate) {
          $endParam = $isNumOrderDate ? (int)($endDate->format('Ym')) : $endDate->format('Ym');
          $qb->andWhere(sprintf('%s >= :endMonth', $fieldName))
              ->setParameter('endMonth', $endParam);
        }

        $logger->info($logTitle . $qb->getDQL());
        // $qb->getQuery()->execute();
        $result = $qb->getQuery()->getResult();
        $logger->info($logTitle . print_r($result, true));

        // 全削除 （出荷関連）
      } else {
        $logger->info($logTitle . 'ALL TRUNCATE (2)!!');

        $db->query(sprintf('TRUNCATE `%s`', $tableName));

      }
    }
  }
}
