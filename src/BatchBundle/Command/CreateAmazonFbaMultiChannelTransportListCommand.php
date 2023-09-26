<?php
/**
 * バッチ処理 Amazon FBAマルチチャネル移動伝票作成処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\Repository\TbStockTransportRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbStockTransport;
use MiscBundle\Entity\TbStockTransportDetail;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAmazonFbaMultiChannelTransportListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:create-amazon-fba-multi-channel-transport-list')
      ->setDescription('Amazon FBAマルチチャネル移動伝票作成処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗: vogue|us_plusnao', AmazonMallProcess::SHOP_NAME_VOGUE)
      ->addOption('update-stock', null, InputOption::VALUE_OPTIONAL, '在庫を取得更新するか 0:更新しない 1:更新する', '0')
      ->addOption('update-stock-data-path', null, InputOption::VALUE_OPTIONAL, '在庫の取得更新ファイルパス（在庫取得更新をskip）', null)
      ->addOption('update-fba-stock', null, InputOption::VALUE_OPTIONAL, 'FBA在庫を取得更新するか 0:更新しない 1:更新する', '0')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Amazon FBAマルチチャネル移動伝票作成処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {
      $this->results = [
          'message' => null
        , 'transports' => []
      ];

      $logExecTitle = sprintf('Amazon FBAマルチチャネル移動伝票作成処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 店舗指定
      $shop = $input->getOption('shop');
      if (!in_array($shop, [
          AmazonMallProcess::SHOP_NAME_VOGUE
        , AmazonMallProcess::SHOP_NAME_US_PLUSNAO
      ])) {
        throw new \RuntimeException('invalid shop name : [' . $shop . ']');
      }


      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述

      // 在庫情報の更新処理
      if ($input->getOption('update-stock')) {

        $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        ];
        if ($account) {
          $commandArgs[] = sprintf('--account=%d', $account->getId());
        }
        if ($input->getOption('update-stock-data-path')) {
          $commandArgs[] = sprintf('--data-path=%s', $input->getOption('update-stock-data-path'));
        }
        if ($shop) {
          $commandArgs[] = sprintf('--shop=%s', $shop);
        }
        $commandInput = new ArgvInput($commandArgs);
        $commandOutput = new ConsoleOutput();

        $command = $this->getContainer()->get('batch.csv_down_load_and_update_amazon_product_stock');
        $exitCode = $command->run($commandInput, $commandOutput);

        if ($exitCode !== 0) { // コマンドが異常終了した
          throw new \RuntimeException('Amazon在庫取得処理でエラーが発生しました。');
        }
      }

      // FBA在庫情報の更新処理
      if ($input->getOption('update-fba-stock')) {
        // ====================================================
        // FBA在庫データ更新（FBA在庫情報 ダウンロード ＆ データ更新）
        // ====================================================
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '開始'));
        $mallProcess->updateFbaProductStock($shop);
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '終了'));

        // ====================================================
        // FBA仮想倉庫 在庫ロケーション更新
        // ====================================================
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '開始'));
        $result = $mallProcess->updateFbaMultiProductLocation($shop, $account);
        if($result['status'] === 'ng'){
          $logger->addDbLog(
              $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー')->setInformation($result['message'])
            , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );
        }
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '終了'));
      }



      $limit = 300; // ざっくり。1伝票300商品までとする。

      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');

      $fbaWarehouseId = TbWarehouseRepository::FBA_MULTI_WAREHOUSE_ID; // FBA倉庫
      $fbaWarehouse = $repoWarehouse->find($fbaWarehouseId);
      if (!$fbaWarehouse) {
        throw new \RuntimeException('no warehouse');
      }

      $temporaryWord = ' TEMPORARY ';
      // $temporaryWord = ''; // FOR DEBUG

      $dbMain = $this->getDb('main');

      $dbMain->beginTransaction();
      $em = $this->getDoctrine()->getManager('main');

      // 移動伝票作成処理
      // 暫定仕様：全モールの直近10日間の販売数を上限に、FBAマルチフラグONの商品について最低1はFBAに存在するように移動伝票を作成する。
      // ※未完了の移動伝票の在庫分に留意。

      // 一時テーブル：移動対象商品抽出
      $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_stock_transport_fba_list_required");
      $sql = <<<EOD
        CREATE {$temporaryWord} TABLE tmp_work_stock_transport_fba_list_required (
            ne_syohin_syohin_code VARCHAR(50) NOT NULL PRIMARY KEY 
          , required_num INTEGER NOT NULL DEFAULT 0 
          , exists_num INTEGER NOT NULL DEFAULT 0
          , transporting INTEGER NOT NULL DEFAULT 0
          , shortage INTEGER AS (required_num - exists_num - transporting) STORED
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8
EOD;
      $dbMain->exec($sql);


      $today = new \DateTime();
      $dateStart = (new \DateTime())->modify('-10 day');
      $seasonMonth = sprintf('s%d', $today->format('n'));

      $sql = <<<EOD
        INSERT INTO tmp_work_stock_transport_fba_list_required (
            ne_syohin_syohin_code
          , required_num
          , exists_num
          , transporting
        )
        SELECT
            pci.ne_syohin_syohin_code
          , CASE
              WHEN (s.`{$seasonMonth}` IS NOT NULL AND s.`{$seasonMonth}` = 0) THEN 0 /* 販売シーズンOFF */
              WHEN COALESCE(T.ordered_num, 0) = 0 THEN 1
              ELSE COALESCE(T.ordered_num, 0)
            END AS required_num
          , COALESCE(fba.fba_quantity_fulfillable, 0) AS exists_num
          , COALESCE(v.`fba_num`, 0) AS transporting
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN tb_product_season s ON m.daihyo_syohin_code = s.daihyo_syohin_code
        LEFT JOIN v_product_stock_transporting v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        LEFT JOIN tb_amazon_product_stock fba ON pci.ne_syohin_syohin_code = fba.sku
        LEFT JOIN (
          SELECT
              a.`商品コード（伝票）`  AS ne_syohin_syohin_code
            , SUM(a.`受注数`) AS ordered_num
          FROM tb_sales_detail_analyze a
          WHERE a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
            AND a.受注日 >= :dateStart
          GROUP BY a.`商品コード（伝票）`
        ) T ON pci.ne_syohin_syohin_code = T.ne_syohin_syohin_code
        WHERE m.fba_multi_flag <> 0
          AND (
               COALESCE(fba.fba_quantity_fulfillable, 0) + COALESCE(v.`fba_num`, 0) = 0
            OR COALESCE(T.ordered_num, 0) > (COALESCE(fba.fba_quantity_fulfillable, 0) + COALESCE(v.`fba_num`, 0))
          )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':dateStart', $dateStart->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->execute();

      // 一時テーブル：対象倉庫別 移動数量
      $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_stock_transport_fba_list_result");
      $sql = <<<EOD
        CREATE {$temporaryWord} TABLE tmp_work_stock_transport_fba_list_result (
            warehouse_id INTEGER NOT NULL
          , ne_syohin_syohin_code VARCHAR(50) NOT NULL 
          , move_num INTEGER NOT NULL DEFAULT 0 
          , PRIMARY KEY (warehouse_id, ne_syohin_syohin_code)
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
      $dbMain->exec($sql);

      // 移動対象倉庫取得
      $warehouses = $repoWarehouse->getTransportFbaFromWarehouses($fbaWarehouseId);

      foreach($warehouses as $warehouse) {
        $sql = <<<EOD
          INSERT INTO tmp_work_stock_transport_fba_list_result (
              warehouse_id
            , ne_syohin_syohin_code
            , move_num
          )
          SELECT 
              /*
              req.ne_syohin_syohin_code
            , req.required_num
            , req.exists_num
            , req.shortage  
              */
              S.warehouse_id  
            , S.ne_syohin_syohin_code
            /*
            , S.stock
            , COALESCE(M.move_num, 0) AS move_num
            , req.shortage - COALESCE(M.move_num, 0) AS current_shortage
            */
            , CASE 
                WHEN req.shortage >= S.stock THEN S.stock
                ELSE req.shortage 
              END AS current_move_num  
          FROM tmp_work_stock_transport_fba_list_required req
          INNER JOIN (
            SELECT 
                 w.id AS warehouse_id 
               , v.ne_syohin_syohin_code
               , SUM(v.stock_remain) AS stock
            FROM v_product_stock_picking_assign v
            INNER JOIN tb_warehouse w ON v.warehouse_id = w.id
            WHERE w.id = :warehouseId
            GROUP BY v.ne_syohin_syohin_code
          ) S ON req.ne_syohin_syohin_code = S.ne_syohin_syohin_code
          WHERE S.stock > 0
            AND req.shortage > 0
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        // 結果を移動伝票として作成する。
        $sql = <<<EOD
          SELECT 
            *
          FROM tmp_work_stock_transport_fba_list_result res
          WHERE res.warehouse_id = :warehouseId
          ORDER BY res.ne_syohin_syohin_code
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
          $transport = null;
          $i = 1;
          while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!isset($transport)) {
              $transport = new TbStockTransport();
              $transport->setAccount($account->getClientName());
              $transport->setDate($today);
              $transport->setTransportCode(TbStockTransportRepository::TRANSPORT_CODE_FBA_SEND);
              $transport->setDepartureWarehouseId($warehouse->getId());
              $transport->setDeparture($warehouse->getName());
              $transport->setDestinationWarehouseId($fbaWarehouse->getId());
              $transport->setDestination($fbaWarehouse->getName());

              $em->persist($transport);
              // IDが欲しいのでflush
              $em->flush();

              $this->results['transports'][] = $transport->getId();
            }

            $detail = new TbStockTransportDetail();
            $detail->setTransportId($transport->getId());
            $detail->setNeSyohinSyohinCode($row['ne_syohin_syohin_code']);
            $detail->setAmount($row['move_num']);
            $em->persist($detail);

            if ($i++ >= $limit) {
              $i = 1;
              unset($transport);
            }
          }

          $em->flush();
        }

        $logger->info(sprintf('FBA用移動伝票作成 倉庫ID: %d 終了', $warehouse->getId()));

        // 移動伝票作成分を更新し、それにより必要数も更新
        $sql = <<<EOD
          UPDATE tmp_work_stock_transport_fba_list_required r
          INNER JOIN v_product_stock_transporting v ON r.ne_syohin_syohin_code = v.ne_syohin_syohin_code 
          SET r.transporting = v.`fba_num`
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();
      }

      if ($this->results['transports']) {
        $this->results['message'] = sprintf('FBA用移動伝票を作成しました。(ID: %s)', implode(', ', $this->results['transports']));

      } else {
        $this->results['message'] = '移動対象の在庫がありませんでした。';
      }

      // choiceItems 移動中在庫数更新処理 （トリガも一括処理もふさわしく無さそうなので、移動伝票更新時に必ず行う。）
      /** @var TbStockTransportRepository $repoTransport */
      $repoTransport = $this->getDoctrine()->getRepository('MiscBundle:TbStockTransport');
      $repoTransport->updateChoiceItemTransportStocks();

      $dbMain->commit();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('Amazon FBAマルチチャネル移動伝票作成処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Amazon FBAマルチチャネル移動伝票作成処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazon FBAマルチチャネル移動伝票作成処理 エラー', 'Amazon FBAマルチチャネル移動伝票作成処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Amazon FBAマルチチャネル移動伝票作成処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


