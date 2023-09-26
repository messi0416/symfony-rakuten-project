<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbProductSalesAccountResultHistoryRepository
 */
class TbProductSalesAccountResultHistoryRepository extends BaseRepository
{
  /**
   * 検索条件に一致するTbProductSalesAccountResultHistoryを集計対象毎に合計したリストを返す。
   * @param array $conditions 検索条件
   * @param string $stockDate 在庫対象日
   * @param string $target 集計対象('account' || 'team')
   * @return array TbProductSalesAccountResultHistoryの集計対象ごとのリスト
   */
  public function findScoreByConditions($conditions, $stockDate, $target)
  {
    $addSelect = '';
    $addJoin = '';
    $addGroupBy = '';
    if ($target === 'account') {
      $addSelect = 'a.user_id userId, u.username userName,';
      $addJoin = 'symfony_users u ON a.user_id = u.id';
      $addGroupBy = 'a.user_id';
    } elseif ($target === 'team') {
      $addSelect = 'a.team_id teamId, t.team_name teamName,';
      $addJoin = 'tb_team t ON a.team_id = t.id';
      $addGroupBy = 'a.team_id';
    } else {
      return [];
    }
    $targetDateFrom = $conditions['targetDateFrom'];
    $targetDateTo = $conditions['targetDateTo'];
    $selectTask = isset($conditions['selectTask']) ? array_map('intval', $conditions['selectTask']) : [];
    $applyStartDateFrom = $conditions['applyStartDateFrom'];
    $applyStartDateTo = $conditions['applyStartDateTo'];

    $dbMain = $this->getConnection('main');
    $addWheres = '';
    $wheres = [];
    $params = [];
    if ($targetDateFrom) {
      $wheres[] = 'r.target_date >= :targetDateFrom';
      $params[':targetDateFrom'] = $targetDateFrom;
    }
    if ($targetDateTo) {
      $wheres[] = 'r.target_date <= :targetDateTo';
      $params[':targetDateTo'] = $targetDateTo;
    }
    if ($applyStartDateFrom) {
      $wheres[] = 'a.apply_start_date >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] = $applyStartDateFrom;
    }
    if ($applyStartDateTo) {
      $wheres[] = 'a.apply_start_date <= :applyStartDateTo';
      $params[':applyStartDateTo'] = $applyStartDateTo;
    }
    if (count($selectTask) > 0) {
      $selectTaskStr = implode(', ', $selectTask);
      $wheres[] = "a.product_sales_task_id IN ({$selectTaskStr})";
    }
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }
    $sql = <<<EOD
      SELECT
        {$addSelect}
        SUM(r.sales_amount) salesAmount,
        SUM(r.profit_amount) profitAmount,
        SUM(r.shoplist_sales_amount) shoplistSalesAmount,
        SUM(r.shoplist_profit_amount) shoplistProfitAmount,
        SUM(
          CASE
            WHEN r.target_date = :stockDate THEN r.stock_quantity
            ELSE 0
          END
        ) stockQuantity,
        SUM(
          CASE
            WHEN r.target_date = :stockDate THEN  r.stock_amount
            ELSE 0
          END
        ) stockAmount,
        SUM(
          CASE
            WHEN r.target_date = :stockDate THEN r.remain_quantity
            ELSE 0
          END
        ) remainQuantity,
        SUM(
          CASE
            WHEN r.target_date = :stockDate THEN r.remain_amount
            ELSE 0
          END
        ) remainAmount
      FROM
        tb_product_sales_account_result_history r
        INNER JOIN tb_product_sales_account a
          ON r.product_sales_account_id = a.id
        INNER JOIN {$addJoin}
      WHERE
        1
        {$addWheres}
      GROUP BY
        {$addGroupBy};
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->bindValue(':stockDate', $stockDate, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 商品売上担当者の、対象日時点での在庫・注残実績の合計を連想配列で返す。
   * 
   * @param string $stockDate 在庫対象日
   * @return array 以下のキーを持つ連想配列
   *  'stockQuantity' => int 商品売上担当者在庫数量合計,
   *  'stockAmount' => int 商品売上担当者在庫金額合計,
   *  'remainQuantity' => int 商品売上担当者注残数量合計,
   *  'remainAmount' => int 商品売上担当者注残金額合計,
   */
  public function findAccountStockResultTotal($stockDate)
  {
    $sql = <<<EOD
      SELECT
        SUM(stock_quantity) stockQuantity,
        SUM(stock_amount) stockAmount,
        SUM(remain_quantity) remainQuantity,
        SUM(remain_amount) remainAmount
      FROM
        tb_product_sales_account_result_history
      WHERE
        target_date = :stockDate
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':stockDate', $stockDate, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * 商品売上担当者の、指定期間の売上系実績の合計を連想配列で返す。
   *
   * @return array 以下のキーを持つ連想配列
   *  'salesAmount' => int 商品売上担当者売上額合計,
   *  'profitAmount' => int 商品売上担当者利益額合計,
   *  'shoplistSalesAmount' => int 商品売上担当者SHOPLIST売上額合計,
   *  'shoplistProfitAmount' => int 商品売上担当者SHOPLIST利益額合計,
   */
  public function findAccountSalesResultTotal($conditions)
  {
    $orderDateFrom = $conditions['targetDateFrom'];
    $orderDateTo = $conditions['targetDateTo'];

    // 条件：対象日時From
    if ($orderDateFrom) {
      $wheres[] = 'target_date >= :orderDateFrom';
      $params[':orderDateFrom'] = $orderDateFrom;
    }
    // 条件：対象日時To
    if ($orderDateTo) {
      $wheres[] = 'target_date <= :orderDateTo';
      $params[':orderDateTo'] = $orderDateTo;
    }
    $addWheres = ' AND ' . implode(' AND ', $wheres);

    $sql = <<<EOD
      SELECT
        sum(sales_amount) salesAmount,
        sum(profit_amount) profitAmount,
        sum(shoplist_sales_amount) shoplistSalesAmount,
        sum(shoplist_profit_amount) shoplistProfitAmount
      FROM
        tb_product_sales_account_result_history
      WHERE 1 {$addWheres};
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }
}
