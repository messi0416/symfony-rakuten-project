<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbProductSalesAccount;

/**
 * TbShoplistDailySalesRepository
 */
class TbShoplistDailySalesRepository extends BaseRepository
{
  
  /**
   * 取込済みの最終受注日を取得する。どこまでのデータが取込済みか取得する。
   */
  public function findLatestOrderDate() {
    $dbMain = $this->getConnection('main');
    $dateStr = $dbMain->query('SELECT MAX(order_date) FROM tb_shoplist_daily_sales;')->fetchColumn(0);
    return (new \DateTimeImmutable($dateStr))->setTime(0, 0, 0);
  }
  
  /**
   * 指定期間中の、会社全体と担当者なしのSHOPLIST売上額・SHOPLIST利益額を返す。
   * @param array $conditions 検索条件
   * @param array $accountResultTotal 売上期間中の担当者実績合計（適用開始日指定が有る時は空配列）
   * @return array 以下のキーを持つ連想配列
   *  'totalSales' => int 会社全体SHOPLIST売上額
   *  'totalProfit' => int 会社全体SHOPLIST利益額
   *  'noAccountSales' => int 担当者なしSHOPLIST全体売上額
   *  'noAccountProfit' => int 担当者なしSHOPLIST全体利益額
   */
  public function findShoplistSalesInPeriod($conditions, $accountResultTotal)
  {
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
        sum(s.sales_amount) salesAmount,
        sum(
          s.sales_amount - (s.cost_tanka * s.num_total) - ROUND(
            s.sales_amount * (s.system_usage_cost_ratio / 100)
          )
        ) AS profitAmount
      FROM
        tb_shoplist_daily_sales s
      WHERE 1 {$addWheres};
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $shoplistSales = $stmt->fetch(\PDO::FETCH_ASSOC);

    // タスク適用開始日の指定有無に応じて、担当者なし実績を求める
    $noAccountResult = [];
    if ($conditions['applyStartDateFrom'] || $conditions['applyStartDateTo']) {
      // 有: 指定期間の担当者なし実績を直接取得する
      $noAccountResult = $this->findNoAccountShoplistSalesResultLimitSaleStart($conditions);
    } else {
      // 無: 全体から前もって取得していた担当者実績合計を差し引くことで算出する
      $noAccountResult = [
        'salesAmount' => $shoplistSales['salesAmount'] - $accountResultTotal['shoplistSalesAmount'],
        'profitAmount' => $shoplistSales['profitAmount'] - $accountResultTotal['shoplistProfitAmount'],
      ];
    }

    return [
      'totalSales' => $shoplistSales['salesAmount'],
      'totalProfit' => $shoplistSales['profitAmount'],
      'noAccountSales' => $noAccountResult['salesAmount'],
      'noAccountProfit' => $noAccountResult['profitAmount'],
    ];
  }

  /**
   * 販売開始日で限定した担当者不在商品の、期間中のSHOPLIST売上額・利益額合計を返す。
   *
   * タスク適用開始日に指定した期間中に販売開始された商品のうち、
   * 登録状態の商品売上担当者が存在しない商品について、
   * 売上日に指定した期間中の、SHOPLIST売上額・SHOPLIST利益額各合計を連想配列で返す。
   * @param array $conditions 検索条件
   * @return array 以下のキーを持つ連想配列
   *  'salesAmount' => int 担当者なしSHOPLIST売上額合計,
   *  'profitAmount' => int 担当者なしSHOPLIST利益額合計,
   */
  private function findNoAccountShoplistSalesResultLimitSaleStart($conditions)
  {
    $orderDateFrom = $conditions['targetDateFrom'];
    $orderDateTo = $conditions['targetDateTo'];
    $applyStartDateFrom = $conditions['applyStartDateFrom'];
    $applyStartDateTo = $conditions['applyStartDateTo'];

    $wheres = [];
    $params = [];
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
    // 条件：タスク適用開始日From
    if ($applyStartDateFrom) {
      $wheres[] = 'm.販売開始日 >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] = $applyStartDateFrom;
    }
    // 条件：タスク適用開始日To
    if ($applyStartDateTo) {
      $wheres[] = 'm.販売開始日 <= :applyStartDateTo';
      $params[':applyStartDateTo'] = $applyStartDateTo;
    }
    $addWheres = ' AND ' . implode(' AND ', $wheres);

    $sql = <<<EOD
      SELECT
        sum(s.sales_amount) salesAmount,
        sum(
          s.sales_amount - (s.cost_tanka * s.num_total) - ROUND(
            s.sales_amount * (s.system_usage_cost_ratio / 100)
          )
        ) AS profitAmount
      FROM
        tb_shoplist_daily_sales s
        INNER JOIN tb_mainproducts m
          ON s.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE
        1
        {$addWheres}
        AND NOT EXISTS (
          /* 同商品について、同日に適用状態の担当者が存在するか */
          SELECT
            *
          FROM
            tb_product_sales_account a
          WHERE
            m.daihyo_syohin_code = a.daihyo_syohin_code
            AND a.apply_start_date <= s.order_date
            AND (a.apply_end_date IS NULL OR a.apply_end_date >= s.order_date)
            AND a.status = :registration
        )
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }
}
