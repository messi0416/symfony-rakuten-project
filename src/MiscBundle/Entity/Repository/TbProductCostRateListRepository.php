<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProductCostRateList;
use MiscBundle\Entity\TbProductCostRateListSetting;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;

/**
 * TbProductCostRateListRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbProductCostRateListRepository extends BaseRepository
{

  /**
   * 商品別原価率一覧 再計算処理
   * @param TbProductCostRateListSetting $setting
   */
  public function refreshCostRateList(TbProductCostRateListSetting $setting)
  {
    $dbMain = $this->getConnection('main');

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    // 直近期間
    $dateBStart = $setting->getTermDate(TbProductCostRateListSetting::TERM_B_START);
    $dateBEnd = $setting->getTermDate(TbProductCostRateListSetting::TERM_B_END);

    // 比較期間
    $dateAStart = $setting->getTermDate(TbProductCostRateListSetting::TERM_A_START);
    $dateAEnd = $setting->getTermDate(TbProductCostRateListSetting::TERM_A_END);

    $logger->info(sprintf('商品別原価率 直近期間: %s - %s', $dateBStart->format('Y-m-d H:i:s'), $dateBEnd->format('Y-m-d H:i:s')));
    $logger->info(sprintf('商品別原価率 比較期間: %s - %s', $dateAStart->format('Y-m-d H:i:s'), $dateAEnd->format('Y-m-d H:i:s')));

    // 全削除
    $dbMain->query("TRUNCATE tb_product_cost_rate_list");

    $logDbName = $this->getConnection('log')->getDataBase();

    // 除外モール設定
    $shoppingMallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);
    $freeOrderMallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_FREE_ORDER);
    if (!$shoppingMallShoplist || !$freeOrderMallShoplist) {
      throw new \RuntimeException('除外モール設定が取得できませんでした。');
    }

    // 対象商品 一括INSERT
    $sql = <<<EOD
      INSERT INTO tb_product_cost_rate_list (
          daihyo_syohin_code
        , cost_rate_after

        , log_cost_rate_average_b
        , log_profit_b
        , log_voucher_num_b

        , log_cost_rate_average_a
        , log_profit_a
        , log_voucher_num_a

        , threshold_term_voucher_num_average
      )
      SELECT
          m.daihyo_syohin_code
        , 0                                 AS cost_rate_after
        , 0                                 AS log_cost_rate_average_b
        , COALESCE(PROFIT_B.profit, 0)      AS log_profit_b
        , COALESCE(PROFIT_B.voucher_num, 0) AS log_voucher_num_b

        , 0                                 AS log_cost_rate_average_a
        , COALESCE(PROFIT_A.profit, 0)      AS log_profit_a
        , COALESCE(PROFIT_A.voucher_num, 0) AS log_voucher_num_a

        , ROUND(COALESCE(THRESHOLD.threshold_voucher_num, 0) / :thresholdVoucherTerm, 2) AS threshold_term_voucher_num_average

      FROM tb_mainproducts            AS m
      INNER JOIN tb_mainproducts_cal  AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata  AS v   ON m.sire_code = v.sire_code

      /* B期間 原価率平均 */
      INNER JOIN (
        SELECT
            m.daihyo_syohin_code
          , AVG(COALESCE(PL.cost_rate, v.cost_rate, 0)) AS cost_rate_average
        FROM tb_mainproducts m
        INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
        INNER JOIN calendar c
        LEFT JOIN (
             SELECT
                  pl.log_date
                , pl.daihyo_syohin_code
                , IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) AS cost_rate
             FROM {$logDbName}.tb_product_price_log pl
             WHERE (pl.log_date BETWEEN :dateBStart AND :dateBEnd)
               AND IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) > 0
        ) PL ON c.date = PL.log_date
              AND m.daihyo_syohin_code = PL.daihyo_syohin_code
        WHERE (c.date BETWEEN :dateBStart AND :dateBEnd)
        GROUP BY m.daihyo_syohin_code
      ) AS RATE_B ON m.daihyo_syohin_code = RATE_B.daihyo_syohin_code

      /* A期間 原価率平均 */
      INNER JOIN (
        SELECT
            m.daihyo_syohin_code
          , AVG(COALESCE(PL.cost_rate, v.cost_rate, 0)) AS cost_rate_average
        FROM tb_mainproducts m
        INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
        INNER JOIN calendar c
        LEFT JOIN (
             SELECT
                  pl.log_date
                , pl.daihyo_syohin_code
                , IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) AS cost_rate
             FROM {$logDbName}.tb_product_price_log pl
             WHERE (pl.log_date BETWEEN :dateAStart AND :dateAEnd)
               AND IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) > 0
        ) PL ON c.date = PL.log_date
              AND m.daihyo_syohin_code = PL.daihyo_syohin_code
        WHERE (c.date BETWEEN :dateAStart AND :dateAEnd)
        GROUP BY m.daihyo_syohin_code
      ) AS RATE_A ON m.daihyo_syohin_code = RATE_A.daihyo_syohin_code

      /* B期間（直近期間） 受注集計 */
      LEFT JOIN (
        SELECT
            p.代表商品コード AS daihyo_syohin_code
          , SUM(p.明細粗利額_伝票費用除外) AS profit
          , COUNT(DISTINCT p.伝票番号) AS voucher_num
        FROM tb_sales_detail_profit p
        WHERE (p.受注年月日 BETWEEN :dateBStart AND :dateBEnd)
          AND p.店舗コード <> :shoplistMallNeId /* SHOPLISTは固定価格のため除外する */
          AND p.店舗コード <> :freeOrderMallNeId /* フリーオーダーはFBA等同梱出荷を含むため除外する */
        GROUP BY p.代表商品コード
      ) AS PROFIT_B ON m.daihyo_syohin_code = PROFIT_B.daihyo_syohin_code

      /* A期間（比較期間） 受注集計 */
      LEFT JOIN (
        SELECT
            p.代表商品コード AS daihyo_syohin_code
          , SUM(p.明細粗利額_伝票費用除外) AS profit
          , COUNT(DISTINCT p.伝票番号) AS voucher_num
        FROM tb_sales_detail_profit p
        WHERE (p.受注年月日 BETWEEN :dateAStart AND :dateAEnd)
          AND p.店舗コード <> :shoplistMallNeId /* SHOPLISTは固定価格のため除外する */
          AND p.店舗コード <> :freeOrderMallNeId /* フリーオーダーはFBA等同梱出荷を含むため除外する */
        GROUP BY p.代表商品コード
      ) AS PROFIT_A ON m.daihyo_syohin_code = PROFIT_A.daihyo_syohin_code

      /* 対象商品 絞込 */
      INNER JOIN (
        SELECT
            p.代表商品コード AS daihyo_syohin_code
          , COUNT(DISTINCT p.伝票番号) AS threshold_voucher_num
        FROM tb_sales_detail_profit p
        WHERE p.受注年月日 >= DATE_ADD(CURRENT_DATE, INTERVAL -1 * :thresholdVoucherTerm DAY)
          AND p.店舗コード <> :shoplistMallNeId /* SHOPLISTは固定価格のため除外する */
          AND p.店舗コード <> :freeOrderMallNeId /* フリーオーダーはFBA等同梱出荷を含むため除外する */
        GROUP BY p.代表商品コード
        HAVING threshold_voucher_num >= :thresholdVoucherNum
      ) THRESHOLD ON m.daihyo_syohin_code = THRESHOLD.daihyo_syohin_code
      WHERE cal.endofavailability IS NULL
        AND cal.deliverycode <> :deliveryCodeTemporary
        AND m.価格非連動チェック = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistMallNeId', $shoppingMallShoplist->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':freeOrderMallNeId', $freeOrderMallShoplist->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':thresholdVoucherTerm', $setting->getThresholdVoucherTerm(), \PDO::PARAM_INT);
    $stmt->bindValue(':thresholdVoucherNum', $setting->getThresholdVoucherTerm() * $setting->getThresholdVoucherNum(), \PDO::PARAM_STR); // 対象期間内件数

    $stmt->bindValue(':dateBStart', $dateBStart->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateBEnd', $dateBEnd->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateAStart', $dateAStart->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateAEnd', $dateAEnd->format('Y-m-d H:i:s'), \PDO::PARAM_STR);

    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    // 平均原価率 取得＆更新（絞りこまれた状態でJOINしないとDBが死ねる）
    // -- B期間
    $sql = <<<EOD
      UPDATE tb_product_cost_rate_list r
      INNER JOIN (
        SELECT
            r.daihyo_syohin_code
          , AVG(COALESCE(PL.cost_rate, v.cost_rate, 0)) AS cost_rate_average
        FROM tb_product_cost_rate_list r
        INNER JOIN tb_mainproducts m ON r.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
        INNER JOIN calendar c
        LEFT JOIN (
             SELECT
                  pl.log_date
                , pl.daihyo_syohin_code
                , IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) AS cost_rate
             FROM {$logDbName}.tb_product_price_log pl
             WHERE (pl.log_date BETWEEN :dateBStart AND :dateBEnd)
               AND IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) > 0
        ) PL ON c.date = PL.log_date
            AND m.daihyo_syohin_code = PL.daihyo_syohin_code
        WHERE (c.date BETWEEN :dateBStart AND :dateBEnd)
        GROUP BY m.daihyo_syohin_code
      ) T ON r.daihyo_syohin_code = T.daihyo_syohin_code
      SET r.cost_rate_after = ROUND(T.cost_rate_average, 0) /* 初期値 */
        , r.log_cost_rate_average_b = ROUND(T.cost_rate_average, 2)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateBStart', $dateBStart->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateBEnd', $dateBEnd->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();

    // -- A期間
    $sql = <<<EOD
      UPDATE tb_product_cost_rate_list r
      INNER JOIN (
        SELECT
            r.daihyo_syohin_code
          , AVG(COALESCE(PL.cost_rate, v.cost_rate, 0)) AS cost_rate_average
        FROM tb_product_cost_rate_list r
        INNER JOIN tb_mainproducts m ON r.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
        INNER JOIN calendar c
        LEFT JOIN (
             SELECT
                  pl.log_date
                , pl.daihyo_syohin_code
                , IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) AS cost_rate
             FROM {$logDbName}.tb_product_price_log pl
             WHERE (pl.log_date BETWEEN :dateAStart AND :dateAEnd)
               AND IF(pl.cost_rate > 0, pl.cost_rate, pl.vendor_cost_rate) > 0
        ) PL ON c.date = PL.log_date
            AND m.daihyo_syohin_code = PL.daihyo_syohin_code
        WHERE (c.date BETWEEN :dateAStart AND :dateAEnd)
        GROUP BY m.daihyo_syohin_code
      ) T ON r.daihyo_syohin_code = T.daihyo_syohin_code
      SET r.log_cost_rate_average_a = ROUND(T.cost_rate_average, 2)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateAStart', $dateAStart->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateAEnd', $dateAEnd->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();

    return;
  }

  /**
   * 商品別原価率 一覧表取得
   */
  public function getListPagination($conditions = [], $orders = [], $limit = 20, $page = 1)
  {
    $em = $this->getEntityManager();
    $qb = $this->createQueryBuilder('p');

    // sort
    foreach($orders as $field => $direction) {
      $qb->addOrderBy($field, $direction);
    }
    // 並び順固定のためのデフォルトorder
    if (!isset($orders['p.daihyo_syohin_code'])) {
      $qb->addOrderBy('p.daihyo_syohin_code', 'ASC');
    }

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator = $this->getContainer()->get('knp_paginator');

    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $qb->getQuery() /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }

  /**
   * 商品別原価率 一覧表リセット
   * 原価率を初期値に更新
   */
  public function resetProductCostRateList()
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_product_cost_rate_list
      SET cost_rate_after = log_cost_rate_average_b
        , shaken = 0
EOD;
    $dbMain->query($sql);
    return;
  }

  /**
   * 商品別原価率 一覧表 再計算処理
   * @param TbProductCostRateListSetting $setting
   */
  public function updateProductCostRateList(TbProductCostRateListSetting $setting)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $dbMain = $this->getConnection('main');
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');

    // 原価率を初期値にリセット
    $this->resetProductCostRateList();

    // 一括更新
    $insertBuilder = new MultiInsertUtil("tb_product_cost_rate_list", [
      'fields' => [
          'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'cost_rate_after' => \PDO::PARAM_INT
      ]
      , 'postfix' => "ON DUPLICATE KEY UPDATE cost_rate_after = VALUES(cost_rate_after)"
    ]);

    $listData = $this->findAll();
    $commonUtil->multipleInsert($insertBuilder, $dbMain, $listData, function($row) use ($setting, $logger) {
      /** @var TbProductCostRateList $row */

      $threshold = $setting->getMoveThresholdRate();
      $changeAmountUp = $setting->getChangeAmountUp();
      $changeAmountDown = $setting->getChangeAmountDown();
      $changeAmountAdditional = $setting->getChangeAmountAdditional();

      // 閾値計算 Aが基準
      $diff = $row->getLogProfitB() - $row->getLogProfitA();
      $diffRate = $row->getLogProfitA() ? ($diff / abs($row->getLogProfitA()) * 100) : 999999;

      $item = false;

      // 閾値判定
      if (abs($diffRate) > $threshold) {

        // 固定変動値
        $row->setCostRateAfter($row->getCostRateAfter() + $changeAmountAdditional);

        // 粗利がマイナスであれば、原価率を下げる（応急対応指示仕様 ※どんどん安くしても仕方がない？）
        if ($row->getLogProfitB() < 0) {

          // 下げる
          $newRate = $row->getCostRateAfter() - $changeAmountDown;
          // 下限は40%
          if ($newRate < 40) {
            $newRate = 40;
          }
          $row->setCostRateAfter($newRate);

        // 粗利が増えていれば b平均へ近づける方向で原価率を更新
        } else if ($diffRate > 0) {
          if ($row->getLogCostRateAverageB() > $row->getLogCostRateAverageA()) {
            // 上げる
            $row->setCostRateAfter($row->getCostRateAfter() + $changeAmountUp);

          } else if ($row->getLogCostRateAverageB() < $row->getLogCostRateAverageA()) {
            // 下げる
            $row->setCostRateAfter($row->getCostRateAfter() - $changeAmountDown);

          } else {
            // 平均が変わっていなければ変えない
          }

          // 粗利が減っていれば、a平均へ近づける方向で原価率を更新
        } else {
          if ($row->getLogCostRateAverageB() > $row->getLogCostRateAverageA()) {
            // 下げる
            $row->setCostRateAfter($row->getCostRateAfter() - $changeAmountDown);

          } else if ($row->getLogCostRateAverageB() < $row->getLogCostRateAverageA()) {
            // 上げる
            $row->setCostRateAfter($row->getCostRateAfter() + $changeAmountUp);

          } else {
            // 平均が変わっていなければ変えない
          }
        }

        $item = [
            'daihyo_syohin_code' => $row->getDaihyoSyohinCode()
          , 'cost_rate_after' => $row->getCostRateAfter()
        ];
      }

      return $item;

    }, 'foreach');

    return;
  }

  /**
   * 商品別原価率 一覧表 揺さぶり処理
   * @param TbProductCostRateListSetting $setting
   */
  public function unsettleProductCostRateList(TbProductCostRateListSetting $setting)
  {
    $dbMain = $this->getConnection('main');
    $logDbName = $this->getConnection('log')->getDataBase();

    // 直近期間
    $dateBStart = $setting->getTermDate(TbProductCostRateListSetting::TERM_B_START);
    $dateBEnd = $setting->getTermDate(TbProductCostRateListSetting::TERM_B_END);

    // 累積変動値 更新
    $sql = <<<EOD
      UPDATE tb_product_cost_rate_list p
      INNER JOIN (
        SELECT
            p.daihyo_syohin_code
          , SUM(ABS(
              AVERAGE.cost_rate_average
               -
              CASE
                 WHEN COALESCE(pl.cost_rate, 0) > 0 THEN pl.cost_rate
                 WHEN COALESCE(pl.vendor_cost_rate, 0) > 0 THEN pl.vendor_cost_rate
                 ELSE v.cost_rate
              END
            )) AS accumulated_cost_rate_change
        FROM tb_product_cost_rate_list p
        INNER JOIN tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
        INNER JOIN calendar c ON c.date BETWEEN :dateBStart AND :dateBEnd
        LEFT JOIN {$logDbName}.tb_product_price_log pl
                ON p.daihyo_syohin_code = pl.daihyo_syohin_code
                AND c.date = pl.log_date
        INNER JOIN (
          SELECT
              p.daihyo_syohin_code
            , AVG(
                CASE
                   WHEN COALESCE(pl.cost_rate, 0) > 0 THEN pl.cost_rate
                   WHEN COALESCE(pl.vendor_cost_rate, 0) > 0 THEN pl.vendor_cost_rate
                   ELSE v.cost_rate
                END
              ) AS cost_rate_average
          FROM tb_product_cost_rate_list p
          INNER JOIN tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
          INNER JOIN calendar c ON c.date BETWEEN :dateBStart AND :dateBEnd
          LEFT JOIN {$logDbName}.tb_product_price_log pl
                  ON p.daihyo_syohin_code = pl.daihyo_syohin_code
                  AND c.date = pl.log_date
          GROUP BY p.daihyo_syohin_code
        ) AS AVERAGE ON p.daihyo_syohin_code = AVERAGE.daihyo_syohin_code
        GROUP BY p.daihyo_syohin_code
      ) T ON p.daihyo_syohin_code = T.daihyo_syohin_code
      SET p.accumulated_cost_rate_change = T.accumulated_cost_rate_change
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateBStart', $dateBStart->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateBEnd', $dateBEnd->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();

    // 揺さぶり対象累積値未満の原価率・揺さぶり済みフラグを更新
    $sql = <<<EOD
      UPDATE tb_product_cost_rate_list p
      SET p.cost_rate_after = p.cost_rate_after
             +
             CASE
               WHEN FLOOR(RAND() * 2) = 0 THEN  (-1 * :moveCostRateDown)
               ELSE :moveCostRateUp
             END
        , p.shaken = -1
      WHERE p.accumulated_cost_rate_change < :shakeBorder
        AND p.cost_rate_after = ROUND(p.log_cost_rate_average_b, 0)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shakeBorder', intval($setting->getShakeBorder()), \PDO::PARAM_INT);
    $stmt->bindValue(':moveCostRateUp', $setting->getChangeAmountUp(), \PDO::PARAM_INT);
    $stmt->bindValue(':moveCostRateDown', $setting->getChangeAmountDown(), \PDO::PARAM_INT);
    $stmt->execute();

    return;
  }


}