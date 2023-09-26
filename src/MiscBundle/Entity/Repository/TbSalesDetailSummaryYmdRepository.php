<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Entity\TbProductSalesAccount;

/**
 * TbSalesDetailSummaryYmdRepository
 */
class TbSalesDetailSummaryYmdRepository extends AnalyzedSalesDetailRepository
{
  /**
   * 指定期間中の、会社全体と担当者なしの売上・利益を返す。
   * @param array $conditions 検索条件
   * @param array $accountResultTotal 売上期間中の担当者実績合計（適用開始日指定が有る時は空配列）
   * @return array 以下のキーを持つ連想配列
   *  'totalSales' => int 会社全体売上額
   *  'totalGrossProfit' => int 会社全体利益額
   *  'noAccountSales' => int 担当者なし全体売上額
   *  'noAccountGrossProfit' => int 担当者なし全体利益額
   */
  public function findSalesInPeriod($conditions, $accountResultTotal)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $orderDateFrom = $conditions['targetDateFrom'];
    $orderDateTo = $conditions['targetDateTo'];

    // 条件：対象日時From
    if ($orderDateFrom) {
      $wheres[] = 's.order_date >= :orderDateFrom';
      $params[':orderDateFrom'] = $orderDateFrom;
    }
    // 条件：対象日時To
    if ($orderDateTo) {
      $wheres[] = 's.order_date <= :orderDateTo';
      $params[':orderDateTo'] = $orderDateTo;
    }
    $addWheres = ' AND ' . implode(' AND ', $wheres);

    $sql = <<<EOD
      SELECT
        sum(s.total_sales) sales,
        sum(s.total_gross_profit) grossProfit
      FROM
        tb_sales_detail_summary_ymd s
      WHERE 1 {$addWheres};
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $totalSales = $stmt->fetch(\PDO::FETCH_ASSOC);

    // タスク適用開始日の指定有無に応じて、担当者なし実績を求める
    $noAccountResult = [];
    if ($conditions['applyStartDateFrom'] || $conditions['applyStartDateTo']) {
      // 有: 指定期間の担当者なし実績を直接取得する
      /** @var TbProductSalesAccountRepository $aRepo */
      $aRepo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductSalesAccount');
      $noAccountResult = $aRepo->findNoAccountSalesResultLimitSaleStart($conditions);
    } else {
      // 無: 全体から前もって取得していた担当者実績合計を差し引くことで算出する
      $noAccountResult = [
        'sales' => $totalSales['sales'] - $accountResultTotal['salesAmount'],
        'grossProfit' => $totalSales['grossProfit'] - $accountResultTotal['profitAmount'],
      ];
    }

    return [
      'totalSales' => $totalSales['sales'],
      'totalGrossProfit' => $totalSales['grossProfit'],
      'noAccountSales' => $noAccountResult['sales'],
      'noAccountGrossProfit' => $noAccountResult['grossProfit'],
    ];
  }
}
