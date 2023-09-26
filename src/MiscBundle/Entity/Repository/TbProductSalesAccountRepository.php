<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Exception\BusinessException;

/**
 * TbProductSalesAccountRepository
 */
class TbProductSalesAccountRepository extends BaseRepository
{
  /**
   * 商品売上担当者一覧を取得
   * @return array [userId1 => userName1, userId2 => userName2, ...] 形式の連想配列
   */
  public function getUserList()
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT DISTINCT
        u.id,
        u.username
      FROM
        tb_product_sales_account a
        JOIN symfony_users u
          ON a.user_id = u.id
      ORDER BY
        user_id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /**
   * 担当者別売上リスト取得
   * @param array $conditions
   * @return array 担当者別売上リスト
   */
  public function getProductSalesByUserList($conditions = [])
  {
    $dbMain = $this->getConnection('main');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $historyOns = [];
    $wheres = [];
    $params = [];
    if ($conditions['targetDateFrom'] != null && isset($conditions['targetDateFrom'])) {
      $historyOns[] = 'SARH.target_date >= :targetDateFrom';
      $params[':targetDateFrom'] =  $conditions['targetDateFrom'];
    }
    if ($conditions['targetDateTo'] != null && isset($conditions['targetDateTo'])) {
      $historyOns[] = 'SARH.target_date <= :targetDateTo';
      $params[':targetDateTo'] =  $conditions['targetDateTo'];
    }
    $addHistoryOns = '';
    if ($historyOns) {
      $addHistoryOns = ' AND ' . implode(' AND ', $historyOns);
    }

    $wheres[] = 'PSA.user_id = :userId';
    $params[':userId'] =  $conditions['userId'];
    $wheres[] = 'PSA.status <> :status';
    $params[':status'] =  TbProductSalesAccount::STATUS_DELETE;
    // 売上期間内に適用日が存在するタスクは、売上0円でも表示するための条件
    if ($conditions['targetDateFrom'] != null && isset($conditions['targetDateFrom']) && $conditions['targetDateTo'] != null && isset($conditions['targetDateTo'])) {
      // 売上期間内に適用日が存在すること
      $wheres[] = <<<EOD
        (
          (
            PSA.apply_start_date <= :targetDateTo
            AND PSA.apply_end_date IS NULL
          )
          OR (
            PSA.apply_end_date >= :targetDateFrom
            AND PSA.apply_start_date <= :targetDateTo
          )
        )
EOD;
    } else if ($conditions['targetDateFrom'] != null && isset($conditions['targetDateFrom'])) {
      // 売上日Fromのみの場合、適用日終了日より売上日Fromが前であるもしくは適用終了日がNULLであること
      $wheres[] = '(PSA.apply_end_date >= :targetDateFrom OR PSA.apply_end_date IS NULL)';
    } else if ($conditions['targetDateTo'] != null && isset($conditions['targetDateTo'])) {
      // 売上日Toのみの場合、適用日開始日より売上日Toが後であること
      $wheres[] = 'PSA.apply_start_date <= :targetDateTo';
    }
    // 適用開始日も指定されている場合、「売上期間内に適用日が存在し、かつ適用開始日が検索条件に合致するものを取得する
    if ($conditions['applyStartDateFrom'] != null && isset($conditions['applyStartDateFrom'])) {
      $wheres[] = 'PSA.apply_start_date >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] =  $conditions['applyStartDateFrom'];
    }
    if ($conditions['applyStartDateTo'] != null && isset($conditions['applyStartDateTo'])) {
      $wheres[] = 'PSA.apply_start_date <= :applyStartDateTo';
      $params[':applyStartDateTo'] =  $conditions['applyStartDateTo'];
    }
    if (isset($conditions['selectTask']) && count($conditions['selectTask']) > 0) {
      $taskIdStr = implode(', ', array_map('intval', $conditions['selectTask']));
      $wheres[] = sprintf('PSA.product_sales_task_id IN (%s)', $taskIdStr);
    }
    if ($conditions['sireName'] != null && isset($conditions['sireName'])) {
      $wheres[] = 'V.sire_name = :sireName';
      $params[':sireName'] =  $conditions['sireName'];
    }

    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }
    $sql = <<<EOD
      SELECT
        PSA.daihyo_syohin_code AS daihyoSyohinCode
        , PSA.user_id AS userId
        , COALESCE(SUM(SARH.sales_amount), 0) AS salesAmount
        , COALESCE(SUM(SARH.profit_amount), 0) AS profitAmount
        , COALESCE(SUM(SARH.shoplist_sales_amount), 0) AS shoplistSalesAmount
        , COALESCE(SUM(SARH.shoplist_profit_amount), 0) AS shoplistProfitAmount
        , SUM(
            CASE
              WHEN SARH.target_date = :stockDate THEN SARH.stock_quantity
              ELSE 0
            END
          ) AS stockQuantity
        , SUM(
            CASE
              WHEN SARH.target_date = :stockDate THEN SARH.stock_amount
              ELSE 0
            END
          ) AS stockAmount
        , SUM(
            CASE
              WHEN SARH.target_date = :stockDate THEN SARH.remain_quantity
              ELSE 0
            END
          ) AS remainQuantity
        , SUM(
            CASE
              WHEN SARH.target_date = :stockDate THEN SARH.remain_amount
              ELSE 0
            END
          ) AS remainAmount
        , M.picnameP1 AS imageFile
        , M.picfolderP1 AS imageDir
        , SUBSTRING_INDEX(D.rakutencategories_1, '\\\\', 1) AS bigCategory
        , REPLACE (
          SUBSTRING(
            SUBSTRING_INDEX(D.rakutencategories_1, '\\\\', 2)
            , CHAR_LENGTH(
              SUBSTRING_INDEX(D.rakutencategories_1, '\\\\', 1)
            ) + 1
          )
          , '\\\\'
          , ''
        ) AS midCategory
        , V.sire_code AS sireCode
        , V.sire_name AS sireName
        , PSA.product_sales_task_id AS taskId 
        , PST.task_name AS taskName
        , PSA.apply_start_date AS applyStartDate
        , PSA.apply_end_date AS applyEndDate
        , PSA.work_amount AS workAmount
      FROM
        tb_product_sales_account PSA
        LEFT JOIN tb_product_sales_account_result_history SARH
          ON (PSA.id = SARH.product_sales_account_id {$addHistoryOns})
        LEFT JOIN tb_product_sales_task PST
          ON PSA.product_sales_task_id = PST.id
        LEFT OUTER JOIN tb_mainproducts M
          ON PSA.daihyo_syohin_code = M.daihyo_syohin_code
        LEFT OUTER JOIN tb_plusnaoproductdirectory D
          ON M.NEディレクトリID = D.NEディレクトリID
        LEFT OUTER JOIN tb_vendormasterdata V
          ON M.sire_code = V.sire_code
      WHERE
        1 = 1
        {$addWheres}
      GROUP BY
        PSA.id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':stockDate', $conditions['stockDate'], \PDO::PARAM_STR);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->execute();
    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }
    return $result;
  }

  /**
   * 検索条件に一致する商品売上担当者の期間内関連商品数のリストを返す。
   * 返り値は、以下の形式で、アカウント毎、チーム毎の商品数を返す。
   * ● 検索対象が'account'の場合
   * [
   *    'userId1' => [
   *       'userName' => {ユーザ名},
   *       'productCount' => {期間内関連商品数}
   *    ]
   *    'userId2' => [
   *       'userName' => {ユーザ名},
   *       'productCount' => {期間内関連商品数}
   *    ]
   *    , ...
   * ]
   * ● 検索対象が'team'の場合
   * [
   *    'teamId1' => [
   *       'teamName' => {チーム名},
   *       'productCount' => {期間内関連商品数}
   *    ]
   *    'teamId2' => [
   *       'teamName' => {チーム名},
   *       'productCount' => {期間内関連商品数}
   *    ]
   *    , ...
   *]
   * ];
   * @param array $conditions 検索条件
   * @param string $target 検索対象 'account'|'team'
   * @return array 商品売上担当者の期間内関連商品数のリスト
   */
  public function findProductCountByConditions($conditions, $target)
  {
    $addSelect = '';
    $addJoin = '';
    $addGroupBy = '';
    if ($target === 'account') {
      $addSelect = 'a.user_id userId, u.username userName, ';
      $addJoin = 'symfony_users u ON a.user_id = u.id';
      $addGroupBy = 'a.user_id';
    } elseif ($target === 'team') {
      $addSelect = 'a.team_id teamId, t.team_name teamName, ';
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

    if ($targetDateFrom && $targetDateTo && $targetDateFrom > $targetDateTo) {
      return [];
    }
    if ($applyStartDateFrom && $applyStartDateTo && $applyStartDateFrom > $applyStartDateTo) {
      return [];
    }

    $addWheres = '';
    $wheres = [];
    $params = [];
    if ($targetDateFrom) {
      $wheres[] = '(a.apply_end_date IS NULL OR a.apply_end_date >= :targetDateFrom)';
      $wheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateTimeFrom)';
      $params[':targetDateFrom'] = $targetDateFrom;
      $params[':targetDateTimeFrom'] = $targetDateFrom . ' 00:00:00';
    }
    if ($targetDateTo) {
      $wheres[] = 'a.apply_start_date <= :targetDateTo';
      $wheres[] = 'm.販売開始日 <= :targetDateTo';
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
        COUNT(DISTINCT a.daihyo_syohin_code) productCount
      FROM
        tb_product_sales_account a
        INNER JOIN
          {$addJoin}
        INNER JOIN tb_mainproducts m
          ON a.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal c
          ON a.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        a.status = :registration
        {$addWheres}
      GROUP BY
        {$addGroupBy}
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
  }

  /**
   * 指定した代表商品に関して、本日時点で担当者であるユーザIDの配列を返す。（重複除く）
   * @param array $daihyoSyohinCode 代表商品コード
   * @return array user_idのリスト
   */
  public function findValidUserIdsByDaihyoSyohinCode($daihyoSyohinCode)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        DISTINCT a.user_id
      FROM
        tb_product_sales_account a
      WHERE
        a.daihyo_syohin_code = :daihyoSyohinCode
        AND a.status = :registration
        AND a.apply_start_date <= :today
        AND (a.apply_end_date IS NULL OR a.apply_end_date >= :today)
      ORDER BY
        a.user_id ASC;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    $stmt->bindValue(':today', (new \DateTime())->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * 販売開始日で限定した担当者不在商品の、対象日時点での在庫・注残合計を連想配列で返す。
   *
   * タスク適用開始日に指定した期間中に販売開始した商品の、
   * 在庫対象日時点での、在庫数・在庫金額・注残数・注残金額各合計のうち、
   * 適用状態の担当者が存在する分を差し引き、その結果の各値を連想配列で返す。
   * @param string $stockDate 在庫対象日
   * @param array $conditions 検索条件
   * @return array 以下のキーを持つ連想配列
   *  'stockQuantity' => int 担当者なし在庫数量合計,
   *  'stockAmount' => int 担当者なし在庫金額合計,
   *  'remainQuantity' => int 担当者なし注残数量合計,
   *  'remainAmount' => int 担当者なし注残金額合計,
   */
  public function findNoAccountStockResultLimitSaleStart($stockDate, $conditions)
  {
    $salesStartWheres = [];
    $params = [];
    // 条件：タスク適用開始日From
    if ($conditions['applyStartDateFrom']) {
      $salesStartWheres[] = 'm.販売開始日 >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] = $conditions['applyStartDateFrom'];
    }
    // 条件：タスク適用開始日To
    if ($conditions['applyStartDateTo']) {
      $salesStartWheres[] = 'm.販売開始日 <= :applyStartDateTo';
      $params[':applyStartDateTo'] = $conditions['applyStartDateTo'];
    }
    $logDbName = $this->getConnection('log')->getDatabase();
    $addSalesStartWheres = ' AND ' . implode(' AND ', $salesStartWheres);
    $sql = <<<EOD
      SELECT
        SUM(pl.総在庫数) stockQuantity,
        SUM(pl.総在庫数 * pl.cost_tanka) stockAmount,
        SUM(pl.総注残数) remainQuantity,
        SUM(pl.総注残数 * pl.genka_tnk) remainAmount
      FROM
        {$logDbName}.tb_product_price_log pl
        INNER JOIN tb_mainproducts m
          ON pl.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal c
          ON m.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        pl.log_date = :stockDate
        {$addSalesStartWheres}
        AND NOT EXISTS (
          /* 同商品について、同日に適用状態の担当者が存在するか */
          SELECT
            *
          FROM
            tb_product_sales_account a
          WHERE
            m.daihyo_syohin_code = a.daihyo_syohin_code
            AND a.apply_start_date <= pl.log_date
            AND (a.apply_end_date IS NULL OR a.apply_end_date >= pl.log_date)
            AND a.status = :registration
        )
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':stockDate', $stockDate, \PDO::PARAM_STR);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * 販売開始日で限定した担当者不在商品の、期間中の関連商品数を返す。
   *
   * タスク適用開始日に指定した期間中に販売開始した商品で、
   * 売上日に指定した期間中に1日でも販売していた商品のうち、
   * 適用状態の担当者が存在する分を差し引き、その結果の商品数を返す。
   * （※但し、販売期間は更新されることがあるので、担当者の有無は特別に、
   * 「『現在の』販売期間中に、適用状態の担当者がいたか」ではなく、
   * 「売上日の指定期間中に、適用状態の担当者がいたか」で広く判定することとする）
   * @param array $conditions 検索条件
   * @return int 担当者なし期間内関連商品数,
   */
  public function findNoAccountProductNumLimitSaleStart($conditions)
  {
    $wheres = [];
    $applyDateWheres = [];
    $params = [];
    // 条件：対象日時From
    if ($conditions['targetDateFrom']) {
      $wheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateTimeFrom)';
      $applyDateWheres[] = '(a.apply_end_date IS NULL OR a.apply_end_date >= :targetDateFrom)';
      $params[':targetDateTimeFrom'] = $conditions['targetDateFrom'] . ' 00:00:00';
      $params[':targetDateFrom'] = $conditions['targetDateFrom'];
    }
    // 条件：対象日時To
    if ($conditions['targetDateTo']) {
      $wheres[] = 'm.販売開始日 <= :targetDateTo';
      $applyDateWheres[] = 'a.apply_start_date <= :targetDateTo';
      $params[':targetDateTo'] = $conditions['targetDateTo'];
    }
    // 条件：タスク適用開始日From
    if ($conditions['applyStartDateFrom']) {
      $wheres[] = 'm.販売開始日 >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] = $conditions['applyStartDateFrom'];
    }
    // 条件：タスク適用開始日To
    if ($conditions['applyStartDateTo']) {
      $wheres[] = 'm.販売開始日 <= :applyStartDateTo';
      $params[':applyStartDateTo'] = $conditions['applyStartDateTo'];
    }
    // 条件：即納商品(0:即納 1:一部即納)
    if (isset($conditions['immediateProducts'])) {
      $wheres[] = 'c.deliverycode in ( :deliveryCodeReady, :deliveryCodeReadyPartially)';
      $params[':deliveryCodeReady'] = TbMainproductsCal::DELIVERY_CODE_READY;
      $params[':deliveryCodeReadyPartially'] = TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY;
    }
    $addWheres = ' AND ' . implode(' AND ', $wheres);
    $addApplyDateWheres = ' AND ' . implode(' AND ', $applyDateWheres);

    $sql = <<<EOD
      SELECT
        count(m.daihyo_syohin_code)
      FROM
        tb_mainproducts m
        INNER JOIN tb_mainproducts_cal c
          ON m.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        1
        {$addWheres}
        AND NOT EXISTS (
          /* 同商品について、売上日指定期間中に適用状態の担当者が存在するか */
          SELECT
            *
          FROM
            tb_product_sales_account a
          WHERE
            m.daihyo_syohin_code = a.daihyo_syohin_code
            {$addApplyDateWheres}
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
    return $stmt->fetchColumn();
  }

  /**
   * 指定期間内に販売していた代表商品の内、1日でも担当者が存在した商品数を返す。
   *
   * @param array $conditions 検索条件
   * @return int 担当者が存在した期間内関連商品数
   */
  public function findAccountProductNumTotal($conditions)
  {
    $targetDateFrom = $conditions['targetDateFrom'];
    $targetDateTo = $conditions['targetDateTo'];
    $immediateProducts = isset($conditions['immediateProducts']);

    if ($targetDateTo !== '' && $targetDateFrom > $targetDateTo) {
      return 0;
    }

    $wheres = [];
    $params = [];
    // 条件：対象日時From
    if ($targetDateFrom) {
      $wheres[] = '(a.apply_end_date IS NULL OR a.apply_end_date >= :targetDateFrom)';
      $wheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateTimeFrom)';
      $params[':targetDateFrom'] = $targetDateFrom;
      $params[':targetDateTimeFrom'] = $targetDateFrom . ' 00:00:00';
    }
    // 条件：対象日時To
    if ($targetDateTo) {
      $wheres[] = 'a.apply_start_date <= :targetDateTo';
      $wheres[] = 'm.販売開始日 <= :targetDateTo';
      $params[':targetDateTo'] = $targetDateTo;
    }
    // 条件：即納商品(0:即納 1:一部即納)
    if ($immediateProducts) {
      $wheres[] = 'c.deliverycode in ( :deliveryCodeReady, :deliveryCodeReadyPartially)';
      $params[':deliveryCodeReady'] = TbMainproductsCal::DELIVERY_CODE_READY;
      $params[':deliveryCodeReadyPartially'] = TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY;
    }
    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $sql = <<<EOD
      SELECT
        count(distinct a.daihyo_syohin_code)
      FROM
        tb_product_sales_account a
        INNER JOIN tb_mainproducts m
          ON a.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal c
          ON a.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        a.status = :registration
        {$addWheres};
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  /**
   * 販売開始日で限定した担当者不在商品の、期間中の売上額・利益額合計を返す。
   *
   * タスク適用開始日に指定した期間中に販売開始した商品で、
   * 売上日に指定した期間中に受注の有った日の、売上額・利益額各合計のうち、
   * 適用状態の担当者が存在する分を差し引き、その結果の各値を連想配列で返す。
   * @param array $conditions 検索条件
   * @return array 以下のキーを持つ連想配列
   *  'salesAmount' => int 担当者なし売上額合計,
   *  'profitAmount' => int 担当者なし利益額合計,
   */
  public function findNoAccountSalesResultLimitSaleStart($conditions)
  {
    $orderDateFrom = $conditions['targetDateFrom'];
    $orderDateTo = $conditions['targetDateTo'];
    $applyStartDateFrom = $conditions['applyStartDateFrom'];
    $applyStartDateTo = $conditions['applyStartDateTo'];

    $wheres = [];
    $params = [];
    // 条件：対象日時From
    if ($orderDateFrom) {
      $wheres[] = 'p.受注年月日 >= :orderDateFrom';
      $params[':orderDateFrom'] = $orderDateFrom;
    }
    // 条件：対象日時To
    if ($orderDateTo) {
      $wheres[] = 'p.受注年月日 <= :orderDateTo';
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

    // 通常商品とセット商品の売上額・利益額を別クエリで取得する
    // 外部結合&WHERE句CASE式で1回のクエリで取得も試みたが、分けた方が速い
    // 通常商品
    $sql = <<<EOD
      SELECT
        SUM(p.小計_伝票料金加算) sales,
        SUM(p.明細粗利額_伝票費用除外) grossProfit
      FROM
        tb_sales_detail_profit p
        INNER JOIN tb_mainproducts m
          ON p.代表商品コード = m.daihyo_syohin_code
        LEFT JOIN tb_sales_detail_set_distribute_info seti
          ON p.伝票番号 = seti.voucher_number AND p.明細行 = seti.line_number
      WHERE
        1
        {$addWheres}
        AND seti.voucher_number IS NULL
        AND NOT EXISTS (
          /* 同商品について、同日に適用状態の担当者が存在するか */
          SELECT
            *
          FROM
            tb_product_sales_account a
          WHERE
            p.代表商品コード = a.daihyo_syohin_code
            AND a.apply_start_date <= p.受注年月日
            AND (a.apply_end_date IS NULL OR a.apply_end_date >= p.受注年月日)
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
    $regularProduct = $stmt->fetch(\PDO::FETCH_ASSOC);

    // セット商品
    $sql = <<<EOD
      SELECT
        SUM(p.小計_伝票料金加算) sales,
        SUM(p.明細粗利額_伝票費用除外) grossProfit
      FROM
        tb_sales_detail_profit p
        INNER JOIN tb_mainproducts m
          ON p.代表商品コード = m.daihyo_syohin_code
        INNER JOIN tb_sales_detail_set_distribute_info seti
          ON p.伝票番号 = seti.voucher_number AND p.明細行 = seti.line_number
        INNER JOIN tb_productchoiceitems setpci
          ON seti.original_ne_syohin_syohin_code = setpci.ne_syohin_syohin_code
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
            p.代表商品コード = a.daihyo_syohin_code
            AND a.apply_start_date <= p.受注年月日
            AND (a.apply_end_date IS NULL OR a.apply_end_date >= p.受注年月日)
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
    $setProduct = $stmt->fetch(\PDO::FETCH_ASSOC);

    return [
      'sales' => $regularProduct['sales'] + $setProduct['sales'],
      'grossProfit' => $regularProduct['grossProfit'] + $setProduct['grossProfit'],
    ];
  }

  /**
   * 代表商品コードから登録状態にある担当者リスト(id, 適用開始日・終了日・仕事量の配列)を返す
   *
   * @param string $daihyoSyohinCode 代表商品コード
   * @param null|Connection $connection
   * @return array
   */
  public function findApplicableAccountListByCode($daihyoSyohinCode, $connection = null)
  {
    $db = $connection ?? $this->getEntityManager()->getConnection();
    $sql = <<<EOD
      SELECT
        id,
        apply_start_date,
        apply_end_date,
        work_amount
      FROM
        tb_product_sales_account
      WHERE
        daihyo_syohin_code = :daihyoSyohinCode
        AND status = :status
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->bindValue(':status', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
