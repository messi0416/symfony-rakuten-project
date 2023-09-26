<?php
/**
 * バッチ処理 在庫移動一覧更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\TbDeliveryStatementDetailNumOrderListInfoRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbWarehouseStockMovePickingListRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshWarehouseStockMoveListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:refresh-warehouse-stock-move-list')
      ->setDescription('在庫移動一覧更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('skip-refresh-statement-detail-list', null, InputOption::VALUE_OPTIONAL, '納品書印刷待ち伝票一覧の再集計をスキップするか', 0)
      ->addOption('picking-list-unit-num', null, InputOption::VALUE_OPTIONAL, 'ピッキングリスト区切り件数', 20)
      ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'ロケーション未作成残留チェックをスキップ', 0)
      ->addOption('target-warehouse-id', null, InputOption::VALUE_OPTIONAL, '在庫移動先倉庫', 12)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('在庫移動一覧更新処理を開始しました。');

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

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('在庫移動一覧更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 通常は、受注明細差分更新をこの前に実行しておく想定

      if ($input->getOption('skip-refresh-statement-detail-list')) {
        $logger->info('納品書印刷待ち一覧再集計をスキップしました。');

      } else {
        // 納品書印刷待ち伝票一覧 更新処理 設定ID:3 で実行。全引当済み伝票を対象に処理を行い、結果を利用する。
        $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          , sprintf('--setting-id=%d', TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_ASSIGNED_ALL)
          , sprintf('--shipping-date=%s', (new \DateTime())->modify('+1 year')->format('Y-m-d'))
          , sprintf('--page-item-num=%d', 40)
        ];
        if ($this->account) {
          $commandArgs[] = sprintf('--account=%d', $this->account->getId());
        }

        $commandInput = new ArgvInput($commandArgs);
        $commandOutput = new ConsoleOutput();

        $command = $this->getContainer()->get('batch.refresh_delivery_statement_detail_product_num_list');
        $exitCode = $command->run($commandInput, $commandOutput);
        if ($exitCode !== 0) { // コマンドが異常終了した
          throw new \RuntimeException('納品書印刷待ち一覧再集計の自動実行でエラーが発生しました。処理を中止します。');
        }
      }

      // 実行チェック処理
      // 倉庫在庫ピッキングでロケーション未作成のものが残っていればエラー。
      /** @var TbWarehouseStockMovePickingListRepository $repoWarehouseStockMovePicking */
      $repoWarehouseStockMovePicking = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $unlocated = $repoWarehouseStockMovePicking->findUnlocatedPickedCount((new \DateTime())->format('Y-m-d'));
      if ($unlocated) {
        $list = [];
        foreach($unlocated as $row) {
          $list[] = sprintf('%s (%d件)', $row['warehouse_name'], $row['num']);
        }
        $message = sprintf("ロケーション未作成の倉庫在庫ピッキングがあります。 \n\n%s", implode("\n", $list));

        if ($input->getOption('force')) {
          $logger->warning($message);
        } else {
          throw new \RuntimeException($message);
        }
      }


      // 以下、出荷不能商品を在庫移動先倉庫へ集める処理の実装
      $dbMain = $this->getDb('main');

      // 0. 在庫移動先倉庫 決定： 暫定：初期倉庫
      $targetWarehouseId = $input->getOption('target-warehouse-id');
      $logger->info(sprintf('在庫移動先倉庫:' . $targetWarehouseId));

      // 0.1. 倉庫在庫移動ピッキングリスト 自動作成分削除（計数に影響を与えるため、先に削除する必要あり。）
      $sql = <<<EOD
        DELETE FROM tb_warehouse_stock_move_picking_list
        WHERE type = :type
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':type', TbWarehouseStockMovePickingListRepository::TYPE_WAREHOUSE, \PDO::PARAM_STR);
      $stmt->execute();

      $commonUtil = $this->getDbCommonUtil();
      $commonUtil->updateSettingValue(TbSetting::KEY_STOCK_MOVE_WAREHOUSE_ID, $targetWarehouseId);

      // 1. 各倉庫の在庫表作成 （ピッキング引当分を除外した残数）
      $dbMain->query("TRUNCATE tb_shipping_product_list");

      // まず出荷商品の表を作成（在庫移動先倉庫へ出荷不能伝票分を加算して集計）
      $sql = <<<EOD
        INSERT INTO tb_shipping_product_list (
            warehouse_id
          , ne_syohin_syohin_code
          , stock
          , shipping_num
        )
        SELECT
            CASE
              WHEN r.warehouse_id = :impossibleWarehouseId THEN :targetWarehouseId
              ELSE r.warehouse_id
            END AS warehouse_id
          , a.`商品コード（伝票）` AS ne_syohin_syohin_code
          , 0 AS stock
          , SUM(a.`受注数`) AS shipping_num
        FROM tb_sales_detail_analyze a
        INNER JOIN tb_delivery_statement_detail_num_order_list_result r ON a.`伝票番号` = r.`伝票番号`
        WHERE a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
        GROUP BY r.warehouse_id, a.`商品コード（伝票）`
        ORDER BY r.warehouse_id, a.`商品コード（伝票）`

        ON DUPLICATE KEY UPDATE
           shipping_num = shipping_num + VALUES(shipping_num)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':impossibleWarehouseId', TbDeliveryStatementDetailNumOrderListInfoRepository::SHIPPING_IMPOSSIBLE_WAREHOUSE_ID, \PDO::PARAM_INT);
      $stmt->bindValue(':targetWarehouseId', $targetWarehouseId, \PDO::PARAM_INT);
      $stmt->execute();

      // おもむろに在庫数を更新
      $sql = <<<EOD
        UPDATE
        tb_shipping_product_list l
        INNER JOIN (
          SELECT
               spa.warehouse_id
             , spa.ne_syohin_syohin_code
             , spa.stock_remain AS stock
          FROM v_product_stock_picking_assign spa
          WHERE spa.stock_remain > 0
        ) STOCK ON l.warehouse_id = STOCK.warehouse_id AND l.ne_syohin_syohin_code = STOCK.ne_syohin_syohin_code
        SET l.stock = l.stock + STOCK.stock

EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $logger->info(sprintf('1. 各倉庫の在庫表作成 完了'));

      // 2. 各倉庫の在庫表から各倉庫の出荷予定分を除外
      // => テーブルの generated column で済み。

      // 3. 在庫移動先倉庫から、出荷不能分の在庫を全て除外（マイナス在庫）
      // => ON DUPLICATE KEY UPDATE で済み。

      // 4. 他倉庫より、在庫移動先倉庫へのマイナス補完リストを作成（優先順位：出荷優先重みづけ 大 -> 小）
      $dbMain->query("TRUNCATE tb_shipping_product_move_list");

      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $warehouses = $repoWarehouse->getStockMoveWarehouses($targetWarehouseId);
      foreach($warehouses as $warehouse) {

        $sql = <<<EOD
          INSERT INTO tb_shipping_product_move_list (
              from_warehouse_id
            , to_warehouse_id
            , ne_syohin_syohin_code
            , num
          )
          SELECT
              T.warehouse_id AS from_warehouse_id
            , l.warehouse_id AS to_warehouse_id
            , l.ne_syohin_syohin_code
            , CASE
                WHEN T.movable_num + (l.remained_num + COALESCE(MOVE.move_num, 0)) > 0 THEN -(l.remained_num + COALESCE(MOVE.move_num, 0))
                ELSE T.movable_num
              END AS move_num
          FROM tb_shipping_product_list l
          INNER JOIN (
            SELECT
                T1.warehouse_id
              , T1.ne_syohin_syohin_code
              , CASE
                  WHEN T2.remained_num IS NOT NULL THEN
                    CASE
                      WHEN T2.remained_num > 0 THEN T2.remained_num
                      ELSE 0
                    END
                  ELSE T1.stock
                END AS movable_num
            FROM ( /* 全商品（出荷対象外の時、在庫をまるまる移動可能として利用） */
              SELECT
                   spa.warehouse_id
                 , spa.ne_syohin_syohin_code
                 , spa.stock_remain AS stock
              FROM v_product_stock_picking_assign spa
              WHERE spa.warehouse_id = :fromWarehouseId
                AND spa.stock_remain > 0
            ) T1
            LEFT JOIN ( /* 出荷対象商品（出荷予定分を引いて残っていれば移動可能） */
              SELECT
                   l2.ne_syohin_syohin_code
                 , l2.remained_num
              FROM tb_shipping_product_list l2
              WHERE l2.warehouse_id = :fromWarehouseId
            ) T2 ON T1.ne_syohin_syohin_code = T2.ne_syohin_syohin_code
          ) T ON l.ne_syohin_syohin_code = T.ne_syohin_syohin_code
          LEFT JOIN (
            SELECT
                ml.ne_syohin_syohin_code
              , SUM(ml.num) AS move_num
            FROM tb_shipping_product_move_list ml
            WHERE ml.to_warehouse_id = :targetWarehouseId
            GROUP BY ml.ne_syohin_syohin_code
          ) MOVE ON l.ne_syohin_syohin_code = MOVE.ne_syohin_syohin_code
          WHERE l.warehouse_id = :targetWarehouseId
            AND (l.remained_num + COALESCE(MOVE.move_num, 0)) < 0
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':fromWarehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(':targetWarehouseId', $targetWarehouseId, \PDO::PARAM_INT);
        $stmt->execute();
      }

      // 移動数 0 のレコードも作成されるため削除。（ざっくり実装）
      $sql = <<<EOD
      DELETE l
      FROM tb_shipping_product_move_list l
      WHERE l.num <= 0
EOD;
      $dbMain->exec($sql);

      $logger->info(sprintf('2,3スキップ。4. 他倉庫より、在庫移動先倉庫へのマイナス保管リストを作成（優先順位：出荷優先重みづけ 大 -> 小） 完了'));

      // 5. 在庫移動ピッキングリスト作成
      $unitNum = $input->getOption('picking-list-unit-num');

      foreach($warehouses as $warehouse) {
        $emergencyLimit = 100; // 100回繰り返されるとさすがにおかしい

        $logger->info('在庫移動ピッキングリスト作成開始 : ' . $warehouse->getId() . '(' . $warehouse->getName() . ')');

        // ピッキングリスト番号取得
        $sql = <<<EOD
          SELECT
            COALESCE(MAX(number), 0) + 1
          FROM tb_warehouse_stock_move_picking_list
          WHERE `date` = CURRENT_DATE
            AND `warehouse_id` = :fromWarehouseId
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':fromWarehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $number = $stmt->fetchColumn(0);

        do {
          $sql = <<<EOD
          INSERT INTO tb_warehouse_stock_move_picking_list (
              `warehouse_id`
            , `date`
            , `number`
            , `ne_syohin_syohin_code`
            , `move_num`
            , `pict_directory`
            , `pict_filename`
            , `type`
          )
          SELECT
              l.from_warehouse_id
            , CURRENT_DATE
            , :number
            , l.ne_syohin_syohin_code
            , l.num
            , COALESCE(i.`directory`, '') AS pict_directory
            , COALESCE(i.`filename`, '') AS pict_filename
            , :type AS type
          FROM tb_shipping_product_move_list l
          INNER JOIN tb_productchoiceitems pci ON l.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          LEFT JOIN product_images i ON pci.daihyo_syohin_code = i.daihyo_syohin_code AND i.code = 'p001'
          LEFT JOIN (
            SELECT
                 pl.ne_syohin_syohin_code
               , GROUP_CONCAT(loc.location_code ORDER BY pl.position) AS location_code
            FROM tb_product_location pl
            INNER JOIN tb_location loc ON pl.location_id = loc.id
            WHERE pl.stock > 0
              AND loc.warehouse_id = :fromWarehouseId
            GROUP BY pl.ne_syohin_syohin_code
          ) T ON l.ne_syohin_syohin_code = T.ne_syohin_syohin_code
          WHERE l.from_warehouse_id = :fromWarehouseId
            AND l.fetched = 0
          ORDER BY COALESCE(T.location_code, 'ZZZ'), l.ne_syohin_syohin_code
          LIMIT :limit
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':number', $number, \PDO::PARAM_INT);
          $stmt->bindValue(':fromWarehouseId', $warehouse->getId(), \PDO::PARAM_INT);
          $stmt->bindValue(':type', TbWarehouseStockMovePickingListRepository::TYPE_WAREHOUSE, \PDO::PARAM_STR);
          $stmt->bindValue(':limit', $unitNum, \PDO::PARAM_INT);
          $stmt->execute();

          $currentUpdateCount = $stmt->rowCount(); // デバッグ用。この周回での更新件数

          $sql = <<<EOD
            UPDATE
            tb_shipping_product_move_list pl
            INNER JOIN tb_warehouse_stock_move_picking_list picking
                 ON pl.from_warehouse_id = picking.warehouse_id
                AND pl.ne_syohin_syohin_code = picking.ne_syohin_syohin_code
                AND picking.type = :type
            SET pl.fetched = -1
            WHERE pl.from_warehouse_id = :fromWarehouseId
              AND pl.fetched = 0
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':fromWarehouseId', $warehouse->getId(), \PDO::PARAM_INT);
          $stmt->bindValue(':type', TbWarehouseStockMovePickingListRepository::TYPE_WAREHOUSE, \PDO::PARAM_STR);
          $stmt->execute();

          $currentFetchedCount = $stmt->rowCount(); // デバッグ用。今回fetchedに変えた件数（updateと一致するべき）

          $sql = <<<EOD
            SELECT
              COUNT(*) AS cnt
            FROM tb_shipping_product_move_list
            WHERE from_warehouse_id = :fromWarehouseId
              AND fetched = 0
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':fromWarehouseId', $warehouse->getId(), \PDO::PARAM_INT);
          $stmt->execute();
          $remains = $stmt->fetchColumn(0);

          $logger->info('在庫移動ピッキング[' . $warehouse->getName() . "]-[$number] 更新: $currentUpdateCount, fetched: $currentFetchedCount, 残数: $remains");

          $number++;

        } while ($remains > 0 && $emergencyLimit-- > 0);

        if ($emergencyLimit == 0) {
          $logger->addDbLog($logger->makeDbLog(null, 'ピッキングリスト数が上限を超えたため処理を中断します', "上限：100"));
        }

        $logger->info('在庫移動ピッキングリスト作成終了 : ' . $warehouse->getId() . '(' . $warehouse->getName() . ')');
      }
      $logger->info(sprintf('5. 在庫移動ピッキングリスト作成 完了'));

      // 出荷量テーブル更新処理
      $this->updateCaluculatedShipmentAmount();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('在庫移動一覧更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('在庫移動一覧更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog(null, 'エラー終了', 'エラー終了')->setInformation($e->getMessage())
        , true, '在庫移動一覧更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }

  private function updateCaluculatedShipmentAmount(){
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    // 出荷対象倉庫一覧取得
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getShipmentEnabledWarehouses();
    $warehouseIds = array();
    foreach ($warehouses as $warehouse) {
      $warehouseIds[] = $warehouse->getId();
    }
    $warehouseIdsStr = implode(',', $warehouseIds);
    if (!$warehouseIdsStr) {
      // 出荷対象倉庫が存在しない場合、処理終了
      return;
    }

    $now = new \DateTime();

    // 倉庫在庫ピッキングの計算値を出荷量に反映
    $sql = <<<EOD
    UPDATE tb_calculated_shipment_amount t
    , ( 
      SELECT
        CASE warehouse_id 
          WHEN 20 THEN 18                              -- 詰替ムカイを旧ムカイとして扱う
          WHEN 13 THEN 12                              -- 詰替古市を古市として扱う
          ELSE warehouse_id 
          END 
                AS aggregate_warehouse_id
        , count(*) * 0.3 AS amount 
      FROM
        tb_warehouse_stock_move_picking_list 
      WHERE
        date = :nowDate
      GROUP BY
        aggregate_warehouse_id
    ) w 
    SET
      t.stock_move_amount = w.amount 
    WHERE
        t.warehouse_id = w.aggregate_warehouse_id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':nowDate', $now->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();
  }
}


