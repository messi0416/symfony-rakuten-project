<?php
/**
 * バッチ処理 発注再計算処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecalculatePurchaseOrderCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:recalculate-purchase-order')
      ->setDescription('発注再計算処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('rate-day', null, InputOption::VALUE_OPTIONAL, '発注点計算期間', 1)
      ->addOption('rate-point', null, InputOption::VALUE_OPTIONAL, '発注点倍率', 1)
      ->addOption('filter-profit', null, InputOption::VALUE_OPTIONAL, '利益率フィルタ', -1)
      ->addOption('filter-access-term', null, InputOption::VALUE_OPTIONAL, '発注点アクセス数判定期間フィルタ（足切り）', 7)
      ->addOption('filter-access-person', null, InputOption::VALUE_OPTIONAL, '発注点アクセス数判定人数フィルタ（足切り）', 40)
      ->addOption('filter-season-access-term', null, InputOption::VALUE_OPTIONAL, '季節在庫アクセス数判定期間フィルタ（足切り）', 7)
      ->addOption('filter-season-access-person', null, InputOption::VALUE_OPTIONAL, '季節在庫アクセス数判定人数フィルタ（足切り）', 40)
      ->addOption('calc-order-point', null, InputOption::VALUE_OPTIONAL, '発注点有効無効 0:無効 1:有効', 1)
      ->addOption('setting-season-order-base', null, InputOption::VALUE_OPTIONAL, '季節在庫定数基準値。tb_settingのSEASON_ORDER_BASEを更新', 0)

      ->addOption('setting-container-from', null, InputOption::VALUE_OPTIONAL, 'コンテナ発注計算期間From。yyyy/mm/dd', '')
      ->addOption('setting-container-to', null, InputOption::VALUE_OPTIONAL, 'コンテナ発注計算期間To。yyyy/mm/dd', '')
      ->addOption('setting-container-point', null, InputOption::VALUE_OPTIONAL, 'コンテナ発注計算倍率', 0)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('発注再計算処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $commonUtil = $this->getDbCommonUtil();
      $dbMain = $this->getDb('main');
      $dbLog = $this->getDb('log');
      $logDbName = $dbLog->getDatabase();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('発注再計算処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

//     '====================
//     '処理開始
//     '====================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '発注点再計算', '開始'));

      $now = new \DateTime();
      $time = microtime(true);

      // 季節在庫定数基準値　現在のセッションは送信値で処理する。tb_settingを更新するため、Access発注画面を開きなおすと反映される
      $commonUtil->updateSettingValue('SEASON_ORDER_BASE', $input->getOption('setting-season-order-base'));
      $seasonConstantBase = intval($input->getOption('setting-season-order-base'));
      // 発注点計算期間倍率
      $rateDay = floatval($input->getOption('rate-day'));
      // 発注点倍率
      $ratePoint = floatval($input->getOption('rate-point'));
      // 利益率フィルタ
      $filterProfit = floatval($input->getOption('filter-profit'));
      // 発注点アクセス数判定期間フィルタ（足切り）
      $filterAccessTerm = intval($input->getOption('filter-access-term'));
      // 発注点アクセス数判定人数フィルタ（足切り）
      $filterAccessPerson = intval($input->getOption('filter-access-person'));
      // 季節在庫アクセス数判定期間フィルタ（足切り）
      $filterSeasonAccessTerm = intval($input->getOption('filter-season-access-term'));
      // 季節在庫アクセス数判定人数フィルタ（足切り）
      $filterSeasonAccessPerson = intval($input->getOption('filter-season-access-person'));
      // コンテナ発注計算期間From
      $settingContainerFrom = $input->getOption('setting-container-from') ? new \DateTime($input->getOption('setting-container-from')) : null;
      // コンテナ発注計算期間To
      $settingContainerTo = $input->getOption('setting-container-to') ? new \DateTime($input->getOption('setting-container-to')) : null;
      // コンテナ発注計算倍率
      $settingContainerPoint = floatval($input->getOption('setting-container-point'));

      $logger->info(sprintf('[発注再計算]季節在庫定数基準値 : %s', $seasonConstantBase));
      $logger->info(sprintf('[発注再計算]発注点計算期間倍率 : %s', $rateDay));
      $logger->info(sprintf('[発注再計算]発注点倍率 : %s', $ratePoint));
      $logger->info(sprintf('[発注再計算]利益率フィルタ : %s', $filterProfit));
      $logger->info(sprintf('[発注再計算]発注点アクセス数判定期間フィルタ（足切り） : %s', $filterAccessTerm));
      $logger->info(sprintf('[発注再計算]発注点アクセス数判定人数フィルタ（足切り） : %s', $filterAccessPerson));
      $logger->info(sprintf('[発注再計算]季節在庫アクセス数判定期間フィルタ（足切り） : %s', $filterSeasonAccessTerm));
      $logger->info(sprintf('[発注再計算]季節在庫アクセス数判定人数フィルタ（足切り） : %s', $filterSeasonAccessPerson));
      $logger->info(sprintf('[発注再計算]コンテナ発注計算期間From : %s', $settingContainerFrom != null ? $settingContainerFrom->format('Y-m-d') : 'null'));
      $logger->info(sprintf('[発注再計算]コンテナ発注計算期間To : %s', $settingContainerTo != null ? $settingContainerTo->format('Y-m-d') : null));
      $logger->info(sprintf('[発注再計算]コンテナ発注計算期間Point : %s', $settingContainerPoint));

      // シーズンカラム名
      $seasonColumnName = sprintf('m%d', $now->format('n')); // 発注点
      $seasonColumnSeasonName = sprintf('c%d', $now->format('n')); // 季節在庫定数
      $logger->info("[発注再計算]：　シーズンカラム　発注点[ $seasonColumnName ] 季節在庫定数 [ $seasonColumnSeasonName ]");

      // 発注点計算起点算出 （※パフォーマンスのための余剰実装）
      // '発注点計算起点計算 一時テーブル作成
      $dbMain->exec(" DROP TEMPORARY TABLE IF EXISTS tmp_work_vendor_calculation_term ");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_vendor_calculation_term (
            sire_code VARCHAR(10) NOT NULL PRIMARY KEY
          , 発注点計算起点 DATE NOT NULL
          , 発注点倍率 DOUBLE(2,1) NOT NULL DEFAULT '1.0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
      $dbMain->exec($sql);

      $sql = <<<EOD
         INSERT INTO tmp_work_vendor_calculation_term (
             sire_code
           , 発注点計算起点
           , 発注点倍率
         )
         SELECT
             sire_code
           , DATE_ADD(
                  CURDATE()
                , INTERVAL ROUND(- 1 * CAST(:rateDay AS DECIMAL(10, 2)) * v.`発注点計算期間`, 0) DAY
             )
           , 発注点倍率
         FROM tb_vendormasterdata v
         ORDER BY sire_code
EOD;
      $stmt = $dbMain->prepare($sql);
      // 発注点計算期間
      $stmt->bindValue(':rateDay', $rateDay, \PDO::PARAM_STR);
      $stmt->execute();

      // 最小発注点計算起点取得（パフォーマンス対応）
      $sql = <<<EOD
        SELECT
          MIN(発注点計算起点) AS 発注点計算起点
        FROM tmp_work_vendor_calculation_term
EOD;
      $baseMinimumDate = $dbMain->query($sql)->fetchColumn(0);
      $baseMinimumDate = $baseMinimumDate
                       ? new \DateTimeImmutable(sprintf('%s 00:00:00', $baseMinimumDate))
                       : (new \DateTimeImmutable())->modify('-30 DAY'); // イレギュラーだが、30日としてみる。

      $logger->info('発注点計算起点: ' . $baseMinimumDate->format('Y-m-d H:i:s'));
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 発注点計算起点');

      // 再計算処理開始

      // セット商品受発注可能フラグ更新
      $dbMain->exec("CALL PROC_UPDATE_SET_PRODUCT_PURCHASABLE_FLAGS");
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品受発注可能フラグ更新');

      // 発注点をリセット
      $dbMain->exec("TRUNCATE tb_product_order_calculation");

      // ====================
      // 期間アクセス数更新
      // ====================

      // 発注点と季節在庫定数、集計期間の長いほうをINNER JOIN、短いほうをLEFT JOINで結合して投入するため、変数定義
      $innerTargetColumn = '発注点期間アクセス数';
      $leftTargetColumn = '季節在庫期間アクセス数';
      if ($filterSeasonAccessTerm > $filterAccessTerm) {
        $innerTargetColumn = '季節在庫期間アクセス数';
        $leftTargetColumn = '発注点期間アクセス数';
      }

      $sql = <<<EOD
       INSERT INTO tb_product_order_calculation (
           ne_syohin_syohin_code
         , {$innerTargetColumn}
         , {$leftTargetColumn}
       )
       SELECT
           pci.ne_syohin_syohin_code
         , a1.期間アクセス数
         , a2.期間アクセス数
       FROM tb_productchoiceitems pci
       INNER JOIN (
           SELECT
               a.syohin_code AS daihyo_syohin_code
             , SUM(a.access_person_count) AS 期間アクセス数
           FROM tb_rakuten_access_count a
           LEFT JOIN `{$logDbName}`.tb_product_price_log pl ON a.log_date = pl.log_date
                                                           AND a.syohin_code = pl.daihyo_syohin_code
           WHERE 1
             AND a.log_date >= DATE_ADD(CURRENT_DATE, INTERVAL -1 * :filterAccessTerm1 DAY)
             AND a.log_date <= CURRENT_DATE
             AND a.`type` = 'p'
             AND (pl.profit_rate IS NULL OR pl.profit_rate > :filterProfit)
           GROUP BY a.syohin_code
       ) a1 ON pci.daihyo_syohin_code = a1.daihyo_syohin_code
       LEFT JOIN (
           SELECT
               a.syohin_code AS daihyo_syohin_code
             , SUM(a.access_person_count) AS 期間アクセス数
           FROM tb_rakuten_access_count a
           LEFT JOIN `{$logDbName}`.tb_product_price_log pl ON a.log_date = pl.log_date
                                                           AND a.syohin_code = pl.daihyo_syohin_code
           WHERE 1
             AND a.log_date >= DATE_ADD(CURRENT_DATE, INTERVAL -1 * :filterAccessTerm2 DAY)
             AND a.log_date <= CURRENT_DATE
             AND a.`type` = 'p'
             AND (pl.profit_rate IS NULL OR pl.profit_rate > :filterProfit)
           GROUP BY a.syohin_code
       ) a2 ON pci.daihyo_syohin_code = a2.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      if ($filterAccessTerm >= $filterSeasonAccessTerm) {
        $stmt->bindValue('filterAccessTerm1', $filterAccessTerm, \PDO::PARAM_INT);
        $stmt->bindValue('filterAccessTerm2', $filterSeasonAccessTerm, \PDO::PARAM_INT);
      } else {
        $stmt->bindValue('filterAccessTerm1', $filterSeasonAccessTerm, \PDO::PARAM_INT);
        $stmt->bindValue('filterAccessTerm2', $filterAccessTerm, \PDO::PARAM_INT);
      }
      $stmt->bindValue('filterProfit', $filterProfit, \PDO::PARAM_STR);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 期間アクセス数更新');

      // ====================
      // 発注点再計算
      // 発注点シーズン設定ONの商品にのみセット
      // ====================

      $mallShoplist = $commonUtil->getShoppingMall($commonUtil->getMallIdByMallCode(DbCommonUtil::MALL_CODE_SHOPLIST));

      $sql = <<<EOD
       INSERT INTO tb_product_order_calculation (
           ne_syohin_syohin_code
         , 発注点
       )
       SELECT
           od.`商品コード`
         , TRUNCATE(
             (SUM(CASE WHEN si.voucher_number IS NULL THEN 1 ELSE od.受注数 END) - 1) -- 通常商品は明細数、セット商品は受注数を加算し、最後に-1
             * CAST(:ratePoint AS DECIMAL(10, 2)) * v.発注点倍率, 0
           ) AS 発注点
       FROM tb_sales_detail_profit AS od
       INNER JOIN tb_productchoiceitems AS pci ON od.商品コード = pci.ne_syohin_syohin_code
       INNER JOIN tb_mainproducts AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
       INNER JOIN tb_mainproducts_cal AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
       INNER JOIN tb_product_season s ON pci.daihyo_syohin_code = s.daihyo_syohin_code
       INNER JOIN tmp_work_vendor_calculation_term AS v ON m.sire_code = v.sire_code
       LEFT JOIN tb_product_order_calculation AS pcal ON od.商品コード = pcal.ne_syohin_syohin_code
       LEFT JOIN tb_sales_detail_set_distribute_info si ON od.伝票番号 = si.voucher_number AND od.明細行 = si.line_number
       WHERE 受注年月日 >= :baseMinimumDate
         AND 受注年月日 >= v.発注点計算起点
         AND キャンセル区分 = '0'
         AND 明細行キャンセル = '0'
         AND pci.受発注可能フラグ <> 0
         AND cal.adult_check_status <> :adultCheckStatusBlack
         AND s.`{$seasonColumnName}` <> 0

         AND od.明細粗利率 > :filterProfit
         AND COALESCE(pcal.発注点期間アクセス数, 0) > :filterAccessPerson

         AND od.店舗コード <> :neMallIdShoplist
       GROUP BY pci.ne_syohin_syohin_code

       ON DUPLICATE KEY UPDATE 発注点 = 発注点 + VALUES(発注点)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':ratePoint', $ratePoint, \PDO::PARAM_STR);
      $stmt->bindValue(':baseMinimumDate', $baseMinimumDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':filterProfit', $filterProfit, \PDO::PARAM_STR);
      $stmt->bindValue(':filterAccessPerson', $filterAccessPerson, \PDO::PARAM_INT);
      $stmt->bindValue(':neMallIdShoplist', $mallShoplist->getNeMallId(), \PDO::PARAM_INT);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 発注点再計算');

      // --------------------
      // SHOPLIST受注数加算（スピード便対応）
      // --------------------
      // 重いので、まず一時テーブルにデータを準備
      $dbMain->exec("DROP TEMPORARY TABLE IF EXISTS tmp_work_tmp_point");
      $sql = <<<EOD
       CREATE TEMPORARY TABLE tmp_work_tmp_point (
           `ne_syohin_syohin_code` VARCHAR(50) NOT NULL PRIMARY KEY
         , 発注点 INTEGER NOT NULL DEFAULT 0
       ) Engine=InnoDB Default CHARACTER SET utf8;
EOD;
      $dbMain->exec($sql);

      $sql = <<<EOD
        INSERT INTO tmp_work_tmp_point
        SELECT
            od.`ne_syohin_syohin_code`
          , od.発注点
        FROM (
            SELECT
                od.ne_syohin_syohin_code
              , SUM(od.num_total) * 1 * v.発注点倍率 AS 発注点
            FROM tb_shoplist_daily_sales od
            INNER JOIN tb_mainproducts AS m ON od.daihyo_syohin_code = m.daihyo_syohin_code
            INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
            INNER JOIN tb_product_season s ON m.daihyo_syohin_code = s.daihyo_syohin_code
            INNER JOIN tmp_work_vendor_calculation_term AS v ON m.sire_code = v.sire_code
            WHERE  od.order_date >= :baseMinimumDate
              AND od.order_date >= v.発注点計算起点
              AND cal.adult_check_status <> :adultCheckStatusBlack
              AND s.`{$seasonColumnName}` <> 0
              AND (
                od.sales_amount - (od.cost_tanka * od.num_total) - ROUND(
                  od.sales_amount * od.system_usage_cost_ratio / 100
                )
              ) / od.sales_amount * 100 > :filterProfit
            GROUP BY od.ne_syohin_syohin_code
        ) AS od
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':baseMinimumDate', $baseMinimumDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':filterProfit', $filterProfit, \PDO::PARAM_STR);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': SHOPLIST受注数加算 一時テーブル作成');

      // 一時テーブルからデータを追加
      $sql = <<<EOD
        INSERT INTO tb_product_order_calculation (
            ne_syohin_syohin_code
          , 発注点
        )
        SELECT
            od.`ne_syohin_syohin_code`
          , od.発注点
        FROM tmp_work_tmp_point od
        INNER JOIN tb_productchoiceitems AS pci ON od.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        LEFT JOIN tb_product_order_calculation AS pcal ON od.ne_syohin_syohin_code = pcal.ne_syohin_syohin_code
        WHERE pci.受発注可能フラグ <> 0
        AND COALESCE(pcal.発注点期間アクセス数, 0) > :filterAccessPerson
        ON DUPLICATE KEY UPDATE 発注点 = pcal.発注点 + VALUES(発注点)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filterAccessPerson', $filterAccessPerson, \PDO::PARAM_INT);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': SHOPLIST受注数加算 一時テーブルからデータを追加');

      // ====================
      //  季節在庫定数再計算
      //  シーズン設定ONの商品にのみセット
      // ====================
      $filterSqlProfit = "";
      $filterSqlAccessPerson = "";
      $filterSqlAccessPersonJoinPhrase = " LEFT JOIN ";

      if (strlen($filterSeasonAccessPerson)) {
        // パフォーマンスのため、期間アクセス数での足切りがあるならINNER JOINとする。
        if ($filterSeasonAccessPerson > 0) {
          $filterSqlAccessPersonJoinPhrase = " INNER JOIN ";
        }
        $filterSqlAccessPerson = sprintf(" AND COALESCE(pcal.季節在庫期間アクセス数, 0) > %d ", $filterSeasonAccessPerson);
      }

      $seasonConstantDate = (new \DateTimeImmutable())->modify('-365 day')->setTime(0, 0, 0);
      $seasonConstantDateLimit = $seasonConstantDate->modify('+30 day');

      $sql = <<<EOD
        INSERT INTO tb_product_order_calculation (
            ne_syohin_syohin_code
          , 季節在庫定数
        )
        SELECT
            pci.ne_syohin_syohin_code
          , (
              CASE
                WHEN ( COALESCE(T.明細数, 0) = 0 OR TRUNCATE(T.明細数 / 5, 0) < :seasonConstantBase ) THEN :seasonConstantBase
                ELSE TRUNCATE(T.明細数 / 5, 0)
              END
            ) AS 季節在庫定数
        FROM tb_productchoiceitems pci
        INNER JOIN tb_product_season s ON pci.daihyo_syohin_code = s.daihyo_syohin_code
        LEFT JOIN (
          SELECT
              商品コード
            , COUNT(*) AS 明細数
          FROM tb_sales_detail_profit p
          WHERE p.`受注年月日` >= :seasonConstantDate
            AND p.`受注年月日` <= :seasonConstantDateLimit
            AND p.`キャンセル区分` = '0'
            AND p.`明細行キャンセル` = '0'
            AND p.明細粗利率 > :filterProfit

          GROUP BY p.`商品コード`
        ) T ON pci.ne_syohin_syohin_code = T.商品コード
        {$filterSqlAccessPersonJoinPhrase} tb_product_order_calculation AS pcal ON pci.ne_syohin_syohin_code = pcal.ne_syohin_syohin_code
        WHERE s.`{$seasonColumnSeasonName}` <> 0
        {$filterSqlAccessPerson}

        ON DUPLICATE KEY UPDATE 季節在庫定数 = VALUES(季節在庫定数)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':seasonConstantBase', $seasonConstantBase, \PDO::PARAM_INT);
      $stmt->bindValue(':seasonConstantDate', $seasonConstantDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':seasonConstantDateLimit', $seasonConstantDateLimit->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':filterProfit', $filterProfit, \PDO::PARAM_STR);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 季節在庫定数再計算');

      // ====================
      //  実店鋪在庫依頼数 追加
      // ====================
      $sql = <<<EOD
        INSERT INTO tb_product_order_calculation (
            ne_syohin_syohin_code
          , 実店鋪在庫依頼
        )
        SELECT
            pci.ne_syohin_syohin_code
          , s.order_num AS 実店鋪在庫依頼
        FROM tb_productchoiceitems pci
        INNER JOIN tb_real_shop_product_stock s ON pci.ne_syohin_syohin_code = s.ne_syohin_syohin_code
        WHERE s.order_num > 0
        ON DUPLICATE KEY UPDATE 実店鋪在庫依頼 = VALUES(実店鋪在庫依頼)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 実店鋪在庫依頼数追加');

      // ====================
      //  コンテナ発注数計算
      // ====================
      // 開始日、終了日が未指定の場合と、倍率が0以下の場合は処理しない
      if ($settingContainerFrom != null && $settingContainerTo != null && $settingContainerPoint > 0) {

        $sql = <<<EOD
          INSERT INTO tb_product_order_calculation (
              ne_syohin_syohin_code
            , コンテナ便発注数
          )
          SELECT
              pci.ne_syohin_syohin_code
            , TRUNCATE(T.明細数 * :settingContainerPoint, 0) AS コンテナ便発注数
          FROM tb_productchoiceitems pci
          INNER JOIN tb_product_season s ON pci.daihyo_syohin_code = s.daihyo_syohin_code
          LEFT JOIN (
            SELECT
                商品コード
              , COUNT(*) AS 明細数
            FROM tb_sales_detail_profit p
            WHERE p.`受注年月日` >= :settingContainerFrom
              AND p.`受注年月日` <= :settingContainerTo
              AND p.`キャンセル区分` = '0'
              AND p.`明細行キャンセル` = '0'
              AND p.明細粗利率 > :filterProfit
            GROUP BY p.`商品コード`
          ) T ON pci.ne_syohin_syohin_code = T.商品コード
          LEFT JOIN tb_product_order_calculation AS pcal ON pci.ne_syohin_syohin_code = pcal.ne_syohin_syohin_code

          ON DUPLICATE KEY UPDATE コンテナ便発注数 = VALUES(コンテナ便発注数)
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':settingContainerFrom', $settingContainerFrom->format('Y-m-d'), \PDO::PARAM_STR);
        $stmt->bindValue(':settingContainerTo', $settingContainerTo->format('Y-m-d'), \PDO::PARAM_STR);
        $stmt->bindValue(':settingContainerPoint', $settingContainerPoint, \PDO::PARAM_STR);
        $stmt->bindValue(':filterProfit', $filterProfit, \PDO::PARAM_STR);
        $stmt->execute();
        $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': コンテナ発注数再計算');
      }

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '発注点再計算', '終了'));
      // データ再表示
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'データ再表示', '開始'));

      // ' セット商品改修
      // ' ほぼほぼ同じ処理を2回通ることになり、すっきりした形に実装したい。どうしてもうまくできないのでベタベタ実装

      // ' セット商品のデータを先に集計
      $dbMain->exec("TRUNCATE tb_order_request_work_set");

      $sql = <<<EOD
        INSERT INTO tb_order_request_work_set (
            商品コード
          , genka_tnk
          , 未引当受注数
          , 発注点
          , 季節在庫定数
          , 実店鋪在庫依頼
          , 在庫定数
          , 発注残数
          , 発注再計算用フリー在庫数
          , 受発注可能フラグ
          , sire_code
          , 受注日
          , 発注点期間アクセス数
          , 季節在庫期間アクセス数
          , メーカー商品コード
          , 価格非連動チェック
          , 手動ゲリラSALE
          , daihyo_syohin_code
          , support_colname
          , support_rowname
          , colcode
          , rowcode
        )
        SELECT
            pci.ne_syohin_syohin_code AS 商品コード
          , m.genka_tnk
          , COALESCE(件数.未引当数, 0) AS 未引当受注数
          , COALESCE(pcal.発注点, 0) AS 発注点
          , COALESCE(pcal.季節在庫定数, 0) AS 季節在庫定数
          , COALESCE(pcal.実店鋪在庫依頼, 0) AS 実店鋪在庫依頼
          , pci.zaiko_teisu AS 在庫定数
          , pci.発注残数
          , ((pci.総在庫数 + pci.`出荷予定取置数`) - pci.引当数 - pci.`ピッキング引当数`) AS 発注再計算用フリー在庫数
          , pci.受発注可能フラグ AS 受発注可能フラグ
          , sire.sire_code
          , DATE_FORMAT(COALESCE(件数.最小受注日, NOW()), '%Y-%m-%d') AS 受注日
          , COALESCE(pcal.発注点期間アクセス数, 0) AS 発注点期間アクセス数
          , COALESCE(pcal.季節在庫期間アクセス数, 0) AS 季節在庫期間アクセス数
          , pci.maker_syohin_code AS メーカー商品コード
          , m.価格非連動チェック
          , m.手動ゲリラSALE
          , pci.daihyo_syohin_code
          , pci.support_colname
          , pci.support_rowname
          , pci.colcode
          , pci.rowcode
        FROM tb_productchoiceitems     pci
        INNER JOIN tb_mainproducts     m    ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal cal  ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_vendormasterdata sire ON sire.sire_code = m.sire_code
        LEFT JOIN tb_product_order_calculation pcal ON pci.ne_syohin_syohin_code = pcal.ne_syohin_syohin_code
        LEFT JOIN (
            SELECT
                `商品コード（伝票）` AS 商品コード
              , SUM(受注数 - 引当数) AS 未引当数
              , MIN(受注日) AS 最小受注日
            FROM tb_sales_detail_analyze
            WHERE キャンセル区分 = '0'
              AND 明細行キャンセル = '0'
              AND (出荷確定日 IS NULL OR 出荷確定日 = '0000-00-00')
              AND 引当数 < 受注数
            GROUP BY `商品コード（伝票）`
        ) AS 件数 ON pci.ne_syohin_syohin_code = 件数.商品コード
        WHERE m.set_flg <> 0
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品 発注数集計');

      // ' 在庫定数計算
      $sql = <<<EOD
        UPDATE tb_order_request_work_set
        SET order_request =
            未引当受注数
          + (CASE WHEN 受発注可能フラグ = 0 THEN 0 ELSE 在庫定数 END)
          + (CASE WHEN 受発注可能フラグ = 0 THEN 0 ELSE 実店鋪在庫依頼 END)
          - ( 発注残数 + 発注再計算用フリー在庫数 )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品 在庫定数計算');

      // ' 発注点、季節在庫定数の加算（アクセス数による足切り対象）
      if ($input->getOption('calc-order-point')) {

        // 発注点・季節在庫を加算
        $filterSqlAccessPerson = '';
        $filterSqlSeasonAccessPerson = '';
        if ($filterAccessPerson) {
          $filterSqlAccessPerson = sprintf(" AND 発注点期間アクセス数 <= %d ", $filterAccessPerson);
        }
        if ($filterSeasonAccessPerson) {
          $filterSqlSeasonAccessPerson = sprintf(" OR 季節在庫期間アクセス数 <= %d ", $filterSeasonAccessPerson);
        }

        $sql = <<<EOD
          UPDATE tb_order_request_work_set
          SET order_request = order_request
            + (CASE WHEN 受発注可能フラグ = 0 {$filterSqlSeasonAccessPerson} THEN 0 ELSE 季節在庫定数 END)
            + (CASE WHEN 1 = 0 {$filterSqlAccessPerson} THEN 0 ELSE 発注点 END)
EOD;

        $stmt = $dbMain->prepare($sql);
        $stmt->execute();

        $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品 発注点、季節在庫定数の加算');
      }

      // セット商品の発注点＋在庫定数を、在庫設定としてセット商品SKUテーブルへ格納する
      // mysql = " UPDATE tb_set_product_sku s SET s.required_stock = 0; " ' 0リセット
      $dbMain->exec(" UPDATE tb_set_product_sku s SET s.required_stock = 0 ");
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品 セット商品SKU設定数リセット');

      // 定数部分更新
      $sql = <<<EOD
        UPDATE tb_set_product_sku s
        INNER JOIN tb_order_request_work_set r ON s.ne_syohin_syohin_code = r.`商品コード`
        SET s.required_stock =
            r.未引当受注数
          + (CASE WHEN r.`受発注可能フラグ` = 0 THEN 0 ELSE r.在庫定数 END)
          + (CASE WHEN r.`受発注可能フラグ` = 0 THEN 0 ELSE r.実店鋪在庫依頼 END)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品 セット商品SKU定数部分更新');

      // 発注点部分更新
      if ($input->getOption('calc-order-point')) {

        // 発注点・季節在庫を加算
        $filterSqlAccessPerson = '';
        $filterSqlSeasonAccessPerson = '';
        if ($filterAccessPerson) {
          $filterSqlAccessPerson = sprintf(" AND 発注点期間アクセス数 <= %d ", $filterAccessPerson);
        }
        if ($filterSeasonAccessPerson) {
          $filterSqlSeasonAccessPerson = sprintf(" OR 季節在庫期間アクセス数 <= %d ", $filterSeasonAccessPerson);
        }

        $sql = <<<EOD
          UPDATE tb_set_product_sku s
          INNER JOIN tb_order_request_work_set r ON s.ne_syohin_syohin_code = r.`商品コード`
          SET s.required_stock = s.required_stock
            + (CASE WHEN r.受発注可能フラグ = 0 {$filterSqlSeasonAccessPerson} THEN 0 ELSE r.季節在庫定数 END)
            + (CASE WHEN 1 = 0 {$filterSqlAccessPerson} THEN 0 ELSE r.発注点 END)
EOD;

        $stmt = $dbMain->prepare($sql);
        $stmt->execute();
        $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': セット商品 セット商品SKU発注点部分更新');
      }

      // ' -------------------------------------------------
      // ' 以下、正式な通常商品集計
      // ' -------------------------------------------------
      $dbMain->exec("TRUNCATE tb_order_request_work");
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 通常商品集計 リセット');

      // 発注点有効無効
      $addSqlPoint = '';
      if ($input->getOption('calc-order-point')) {
        $addSqlPoint = <<<EOD
            OR TT.発注点 > 0
            OR TT.季節在庫定数 > 0
            OR TT.コンテナ便発注数 > 0
EOD;
      }

      $sql = <<<EOD
        INSERT INTO tb_order_request_work (
            商品コード
          , genka_tnk
          , 未引当受注数
          , 発注点
          , 季節在庫定数
          , 実店鋪在庫依頼
          , 在庫定数
          , コンテナ便発注数
          , エア便発注残数
          , コンテナ便発注残数
          , 発注再計算用フリー在庫数
          , 受発注可能フラグ
          , sire_code
          , 受注日
          , 発注点期間アクセス数
          , 季節在庫期間アクセス数
          , メーカー商品コード
          , 価格非連動チェック
          , 手動ゲリラSALE
          , daihyo_syohin_code
          , support_colname
          , support_rowname
          , colcode
          , rowcode
        )
        SELECT
            TT.商品コード
          , m.genka_tnk
          , TT.未引当受注数
          , TT.発注点
          , TT.季節在庫定数
          , TT.実店鋪在庫依頼
          , TT.在庫定数
          , TT.コンテナ便発注数
          , pci.発注残数 - pci.コンテナ便発注残数 AS エア便発注残数
          , pci.コンテナ便発注残数
          , ((pci.総在庫数 + pci.`出荷予定取置数`) - pci.引当数 - pci.`ピッキング引当数`) AS 発注再計算用フリー在庫数
          , pci.受発注可能フラグ
          , m.sire_code
          , DATE_FORMAT(TT.最小受注日, '%Y-%m-%d') AS 受注日
          , TT.発注点期間アクセス数
          , TT.季節在庫期間アクセス数
          , pci.maker_syohin_code AS メーカー商品コード
          , m.価格非連動チェック
          , m.手動ゲリラSALE
          , pci.daihyo_syohin_code
          , pci.support_colname
          , pci.support_rowname
          , pci.colcode
          , pci.rowcode
        FROM (
          SELECT
              T.商品コード
            , SUM(T.未引当受注数) AS 未引当受注数
            , SUM(T.発注点) AS 発注点
            , SUM(T.季節在庫定数) AS 季節在庫定数
            , SUM(T.実店鋪在庫依頼) AS 実店鋪在庫依頼
            , SUM(T.在庫定数) AS 在庫定数
            , COALESCE(MIN(最小受注日), NOW()) AS 最小受注日
            , SUM(T.発注点期間アクセス数) AS 発注点期間アクセス数
            , SUM(T.季節在庫期間アクセス数) AS 季節在庫期間アクセス数
            , SUM(T.コンテナ便発注数) AS コンテナ便発注数
          FROM (
            SELECT
                pci.ne_syohin_syohin_code AS 商品コード
              , 0 AS 未引当受注数
              , 0 AS 発注点
              , 0 AS 季節在庫定数
              , 0 AS 実店鋪在庫依頼
              , pci.zaiko_teisu AS 在庫定数
              , 0 AS コンテナ便発注数
              , NULL AS 最小受注日
              , 0 AS 発注点期間アクセス数
              , 0 AS 季節在庫期間アクセス数
            FROM tb_productchoiceitems pci
            WHERE pci.zaiko_teisu > 0
            UNION ALL
            SELECT
                pcal.ne_syohin_syohin_code AS 商品コード
              , 0 AS 未引当受注数
              , pcal.発注点 AS 発注点
              , pcal.季節在庫定数 AS 季節在庫定数
              , pcal.実店鋪在庫依頼 AS 実店鋪在庫依頼
              , 0 AS 在庫定数
              , pcal.コンテナ便発注数 AS コンテナ便発注数
              , NULL AS 最小受注日
              , pcal.発注点期間アクセス数 AS 発注点期間アクセス数
              , pcal.季節在庫期間アクセス数 AS 季節在庫期間アクセス数
            FROM tb_product_order_calculation pcal
            UNION ALL
            SELECT
                `商品コード（伝票）` AS 商品コード
              , SUM(受注数 - 引当数) AS 未引当受注数
              , 0 AS 発注点
              , 0 AS 季節在庫定数
              , 0 AS 実店鋪在庫依頼
              , 0 AS 在庫定数
              , 0 AS コンテナ便発注数
              , MIN(受注日) AS 最小受注日
              , 0 AS 発注点期間アクセス数
              , 0 AS 季節在庫期間アクセス数
            FROM tb_sales_detail_analyze
            WHERE (出荷確定日 IS NULL OR 出荷確定日 = '0000-00-00')
              AND キャンセル区分 = '0'
              AND 明細行キャンセル = '0'
              AND 引当数 < 受注数
            GROUP BY `商品コード（伝票）`
          ) T
          GROUP BY 商品コード
        ) TT
        INNER JOIN tb_productchoiceitems pci ON TT.商品コード = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        WHERE (
               TT.未引当受注数 > 0
            {$addSqlPoint}
            /*
            OR TT.発注点 > 0
            OR TT.季節在庫定数 > 0
            OR TT.コンテナ便発注数 > 0
            */
            OR TT.実店鋪在庫依頼 > 0
            OR TT.在庫定数 > 0
        )
        AND m.set_flg = 0
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 通常商品集計 発注数集計');

      // ' -----------------------------------------------------------------------
      // ' セット商品対応
      // ' ・発注に上がっているセット商品を、各内訳SKUに分配する（order_request の結果を加算）
      // ' ・セット商品の発注レコードを削除する
      // ' -----------------------------------------------------------------------
      //
      // ' 発注に上がっているセット商品を各内訳SKUに分配して加算
      // INSERT ～ SELECTのSELECT側にorder_requestがあると、ON DUPLICATE KEY UPDATE で ambiguous のエラーが出てしまうためサブクエリでカラム名をいったん数量に変更
      $sql = <<<EOD
        INSERT INTO tb_order_request_work (
            商品コード
          , genka_tnk
          , order_request
          , 未引当受注数
          , 発注点
          , 季節在庫定数
          , 実店鋪在庫依頼
          , 在庫定数
          , コンテナ便発注数
          , エア便発注残数
          , 発注再計算用フリー在庫数
          , 受発注可能フラグ
          , sire_code
          , 受注日
          , 発注点期間アクセス数
          , 季節在庫期間アクセス数
          , メーカー商品コード
          , 価格非連動チェック
          , 手動ゲリラSALE
          , daihyo_syohin_code
          , support_colname
          , support_rowname
          , colcode
          , rowcode
        )
        SELECT
            request_sum.商品コード
            , request_sum.genka_tnk
            , request_sum.数量
            , request_sum.未引当受注数
            , request_sum.発注点
            , request_sum.季節在庫定数
            , request_sum.実店鋪在庫依頼
            , request_sum.在庫定数
            , request_sum.コンテナ便発注数
            , request_sum.発注残数
            , request_sum.発注再計算用フリー在庫数
            , request_sum.受発注可能フラグ
            , request_sum.sire_code
            , request_sum.受注日
            , request_sum.発注点期間アクセス数
            , request_sum.季節在庫期間アクセス数
            , request_sum.メーカー商品コード
            , request_sum.価格非連動チェック
            , request_sum.手動ゲリラSALE
            , request_sum.daihyo_syohin_code
            , request_sum.support_colname
            , request_sum.support_rowname
            , request_sum.colcode
            , request_sum.rowcode
        FROM
            (SELECT
                sku_detail.ne_syohin_syohin_code AS 商品コード
              , m_detail.genka_tnk AS genka_tnk
              , r.order_request * d.num AS 数量
              , 0 AS 未引当受注数
              , 0 AS 発注点
              , 0 AS 季節在庫定数
              , 0 AS 実店鋪在庫依頼
              , 0 AS 在庫定数
              , 0 AS コンテナ便発注数
              , sku_detail.発注残数 AS 発注残数
          , ((sku_detail.総在庫数 + sku_detail.`出荷予定取置数`) - sku_detail.引当数 - sku_detail.`ピッキング引当数`) AS 発注再計算用フリー在庫数
          , sku_detail.`受発注可能フラグ` AS 受発注可能フラグ
          , m_detail.sire_code AS sire_code
          , r.`受注日` AS 受注日
          , r.`発注点期間アクセス数` AS 発注点期間アクセス数
          , r.`季節在庫期間アクセス数` AS 季節在庫期間アクセス数
          , sku_detail.maker_syohin_code AS メーカー商品コード
          , m_detail.`価格非連動チェック` AS 価格非連動チェック
          , m_detail.`手動ゲリラSALE` AS 手動ゲリラSALE
          , sku_detail.daihyo_syohin_code
          , sku_detail.support_colname
          , sku_detail.support_rowname
          , sku_detail.colcode
          , sku_detail.rowcode
        FROM tb_order_request_work_set    r
        INNER JOIN tb_productchoiceitems  pci         ON r.`商品コード` = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts        m           ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_set_product_detail  d           ON d.set_ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems  sku_detail  ON d.ne_syohin_syohin_code = sku_detail.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts        m_detail    ON sku_detail.daihyo_syohin_code = m_detail.daihyo_syohin_code
        WHERE r.order_request > 0) request_sum
    ON DUPLICATE KEY UPDATE
        order_request  = order_request + VALUES(order_request)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 通常商品集計 セット商品内訳加算');
      // ' / セット商品対応 ここまで
      // ' -----------------------------------------------------------------------

      // ' 在庫定数計算
      $sql = <<<EOD
        UPDATE tb_order_request_work
        SET order_request =
            order_request
          + 未引当受注数
          + (CASE WHEN 受発注可能フラグ = 0 THEN 0 ELSE 在庫定数 END)
          + (CASE WHEN 受発注可能フラグ = 0 THEN 0 ELSE 実店鋪在庫依頼 END)
          - ( エア便発注残数 + 発注再計算用フリー在庫数 )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 通常商品集計 在庫定数計算');

      // ' 発注点、季節在庫定数の加算（アクセス数による足切り対象）
      // 発注点有効無効
      if ($input->getOption('calc-order-point')) {

        // 発注点・季節在庫を加算
        $filterSqlAccessPerson = '';
        $filterSqlSeasonAccessPerson = '';
        if ($filterAccessPerson) {
          $filterSqlAccessPerson = sprintf(" AND 発注点期間アクセス数 <= %d ", $filterAccessPerson);
        }
        if ($filterSeasonAccessPerson) {
          $filterSqlSeasonAccessPerson = sprintf(" OR 季節在庫期間アクセス数 <= %d ", $filterSeasonAccessPerson);
        }

        $sql = <<<EOD
          UPDATE tb_order_request_work
          SET order_request =
                order_request
                + (CASE WHEN 受発注可能フラグ = 0 {$filterSqlSeasonAccessPerson} THEN 0 ELSE 季節在庫定数 END)
                + (CASE WHEN 1 = 0 {$filterSqlAccessPerson} THEN 0 ELSE 発注点 END)
          WHERE 1
EOD;

        $stmt = $dbMain->prepare($sql);
        $stmt->execute();
        $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 通常商品集計 発注点、季節在庫定数の加算');
      }

      // 既に在庫があるなど、マイナスのものは0とする（コンテナ便計算の前に調整）
      $sql = <<<EOD
          UPDATE tb_order_request_work SET order_request = 0 WHERE order_request < 0
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // コンテナ便数量の加算
      // 発注点有効無効
      if ($input->getOption('calc-order-point')) {

        // マイナスの場合は0とする
        $sql = <<<EOD
          UPDATE tb_order_request_work
          SET container_order_request =
                CASE
                  WHEN 受発注可能フラグ = 0 THEN 0
                  ELSE
                    CASE
                      WHEN (コンテナ便発注数 - ( エア便発注残数 + 発注再計算用フリー在庫数 ) - コンテナ便発注残数 - order_request) < 0 THEN 0
                      ELSE コンテナ便発注数 - ( エア便発注残数 + 発注再計算用フリー在庫数 ) - コンテナ便発注残数 - order_request
                    END
                END
          WHERE 1
EOD;

        $stmt = $dbMain->prepare($sql);
        $stmt->execute();
        $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 通常商品集計 発注点、季節在庫定数の加算');
      }


      // ' 発注数 <= 0 を削除
      $dbMain->exec(" DELETE FROM tb_order_request_work WHERE order_request <= 0 AND container_order_request <= 0 ");
      $current = microtime(true); $rap = $current - $time; $time = $current; $logger->info('発注再計算処理 rap: ' . round($rap, 2) . ': 発注数 <= 0 を削除');

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'データ再表示', '終了'));

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('発注再計算処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('発注再計算処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('発注再計算処理 エラー', '発注再計算処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '発注再計算処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


