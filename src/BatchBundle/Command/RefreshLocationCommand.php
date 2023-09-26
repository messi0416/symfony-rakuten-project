<?php
/**
 * ロケーション更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbWarehouse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshLocationCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  private $doImportStock = false;

  /** @var TbWarehouse */
  private $currentWarehouse = null;

  protected function configure()
  {
    $this
      ->setName('batch:refresh-location')
      ->setDescription('ロケーション更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('do-import-stock', null, InputOption::VALUE_OPTIONAL, '在庫取込実行有無', true)
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('ロケーション更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // 在庫取込スキップフラグ
    $this->doImportStock = boolval($input->getOption('do-import-stock'));

    try {

      $dbMain = $this->getDb('main');
      $dbLog = $this->getDb('log');
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('ロケーション更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      // 対象倉庫 => 一旦固定。
      //    ※ ログインアカウントの選択倉庫の直接利用は、切り替え忘れの事故などがありうるので、改修時には注意。
      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $this->currentWarehouse = $repoWarehouse->find(TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);

      // 在庫取込処理
      if ($this->doImportStock) {
        $commandArgs = [
          'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        ];
        if ($this->account) {
          $commandArgs[] = sprintf('--account=%d', $this->account->getId());
        }
        if (!is_null($input->getOption('target-env'))) {
          $commandArgs[] = sprintf('--target-env=%s', $input->getOption('target-env'));
        }

        $input = new ArgvInput($commandArgs);
        $output = new ConsoleOutput();

        $logger->error('ロケーション更新 在庫取込開始');
        $command = $this->getContainer()->get('batch.csv_download_stock_command');
        $exitCode = $command->run($input, $output);

        if ($exitCode !== 0) { // コマンドが異常終了した
          throw new \RuntimeException('在庫CSV取込に失敗しました。');
        }
      }

      // 入荷入力NextEngine反映確認チェック （仮想商品在庫数チェック）
      if ($commonUtil->getSettingValue('NYUKA_HANNEI_KAKUNIN_STOCK') != $commonUtil->getSettingValue('NYUKA_HANNEI_KAKUNIN_NE_STOCK')) {
        throw new \RuntimeException('入荷入力反映確認用仮想商品の在庫数が、NextEngineからの取込データと一致していません。処理を終了します。');
      }

      // 一時テーブル作成、データコピー
      // TEMPORARY TABLE が自己結合できないことにより、実テーブルの一時テーブルを利用する。（他でも排他制御が必要であるため、まあ問題なし）
      $dbMain->query("DROP TABLE IF EXISTS tmp_product_location_update");
      $sql = <<<EOD
        CREATE TABLE `tmp_product_location_update` (
            `ne_syohin_syohin_code` VARCHAR(50) NOT NULL
          , `location_id`           INT(11) NOT NULL
          , `stock`                 INT(11) NOT NULL DEFAULT '0'
          , `stock_updated`         INT(11) NOT NULL DEFAULT '0'
          , `position`              TINYINT(4) NOT NULL DEFAULT '0'
          , `position_updated`      TINYINT(4) NOT NULL DEFAULT '0'
          , `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
          , `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
          , PRIMARY KEY (`ne_syohin_syohin_code`, `location_id`)
          , INDEX `tb_product_location_FK1` (`location_id`)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8
EOD;
      $dbMain->query($sql);

      // トランザクション開始
      $dbMain->beginTransaction();

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      $sql = <<<EOD
        INSERT INTO tmp_product_location_update (
            `ne_syohin_syohin_code`
          , `location_id`
          , `stock`
          , `position`
          , `created`
          , `updated`
        )
        SELECT
            `ne_syohin_syohin_code`
          , `location_id`
          , `stock`
          , `position`
          , `created`
          , `updated`
        FROM tb_product_location
        ORDER BY ne_syohin_syohin_code, position
EOD;
      $dbMain->query($sql);

      // 取込で在庫が増えている物があれば、まとめて新規ロケーションをセット
      $fetchIncreasedStockSql = <<<EOD
        FROM tb_productchoiceitems pci
        LEFT JOIN (
          SELECT
              pl.ne_syohin_syohin_code
            , SUM(pl.stock) AS location_stock_total
            , MAX(pl.position) AS max_position
          FROM tmp_product_location_update pl
          GROUP BY pl.ne_syohin_syohin_code
        ) T ON pci.ne_syohin_syohin_code = T.ne_syohin_syohin_code
        WHERE pci.`在庫数` > COALESCE(T.location_stock_total, 0)
EOD;
      $sql = "SELECT COUNT(*) AS cnt " . $fetchIncreasedStockSql;

      $count = $dbMain->query($sql)->fetchColumn(0);
      if ($count > 0) {

        $newLocation = $repoLocation->createAutoLocation('ne', 'NE_', $this->currentWarehouse);
        if (!$newLocation) {
          throw new \RuntimeException('新規ロケーションの作成に失敗しました。');
        }

        $sql = <<<EOD
          INSERT INTO tmp_product_location_update (
              ne_syohin_syohin_code
            , location_id
            , stock_updated
            , position
          )
          SELECT
              pci.ne_syohin_syohin_code
            , :locationId AS location_id
            , pci.在庫数 - COALESCE(T.location_stock_total, 0)
            , CASE
                 WHEN T.max_position IS NULL THEN 0
                 ELSE T.max_position + 1
              END
EOD;
        $sql .= $fetchIncreasedStockSql;

        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':locationId', $newLocation->getId());
        $stmt->execute();
      }

      // 取り込み済み在庫総数をロケーションに再分配（positionの大きいもの順）
      $sql = <<<EOD
        UPDATE tmp_product_location_update tmp
        INNER JOIN (
          SELECT
              pl.ne_syohin_syohin_code
            , pl.stock
            , pl.position
            , pci.在庫数
            , COALESCE(pl2.other_stock, 0) AS other_stock
            , pci.`在庫数` - COALESCE(pl2.other_stock, 0) AS remain_stock
            , CASE
                 WHEN pci.`在庫数` - COALESCE(pl2.other_stock, 0) >= stock THEN stock
                 WHEN pci.`在庫数` - COALESCE(pl2.other_stock, 0) < 0 THEN 0
                 ELSE pci.`在庫数` - COALESCE(pl2.other_stock, 0)
              END AS stock_updated
          FROM tb_product_location pl
          INNER JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          LEFT JOIN (
            SELECT
                 pl1.ne_syohin_syohin_code
               , pl1.position
               , SUM(pl2.stock) AS other_stock
            FROM tb_product_location pl1
            INNER JOIN tb_product_location pl2 ON pl1.ne_syohin_syohin_code = pl2.ne_syohin_syohin_code
            WHERE pl1.position >= 0
              AND pl2.position > pl1.position
            GROUP BY pl1.ne_syohin_syohin_code, pl1.position
          ) AS pl2 ON pl.ne_syohin_syohin_code = pl2.ne_syohin_syohin_code
                   AND pl.position = pl2.position
          ORDER BY pl.ne_syohin_syohin_code, pl.position ASC
        ) T ON tmp.ne_syohin_syohin_code = T.ne_syohin_syohin_code
           AND tmp.position = T.position

        SET tmp.stock_updated = T.stock_updated
EOD;
      $dbMain->query($sql);
      /* ------------ DEBUG LOG ------------ */  $logger->info($this->getLapTimeAndMemory('replace stocks', 'main'));

      // 空になったロケーションの移動処理
      // 上手いロジックが思いつかないため、position:0 の stock_updated が0の商品について最大6回スライドを繰り返す。
      for ($i = 0; $i < 6; $i++) {
        $sql = <<<EOD
          UPDATE tmp_product_location_update tmp
          INNER JOIN (
            SELECT
               DISTINCT ne_syohin_syohin_code
            FROM tmp_product_location_update
            WHERE position = 0
              AND stock_updated <= 0
          ) AS T ON tmp.ne_syohin_syohin_code = T.ne_syohin_syohin_code
          SET tmp.position = tmp.position - 1
EOD;
        $dbMain->query($sql);
      }
      /* ------------ DEBUG LOG ------------ */  $logger->info($this->getLapTimeAndMemory('slide positions', 'main'));

      // 過去ロケーションの削除（5件超過分）
      $dbMain->query("DELETE FROM tmp_product_location_update WHERE position < -5");

      // 一時テーブルから本テーブルへ書き戻し
      $dbMain->query("DELETE FROM tb_product_location"); // トランザクション中につき、TRUNCATEは利用不可
      $sql = <<<EOD
        INSERT INTO tb_product_location (
            `ne_syohin_syohin_code`
          , `location_id`
          , `stock`
          , `position`
          , `created`
          , `updated`
        )
        SELECT
            `ne_syohin_syohin_code`
          , `location_id`
          , `stock_updated`
          , `position`
          , `created`
          , `updated`
        FROM tmp_product_location_update
        ORDER BY ne_syohin_syohin_code, position
EOD;
      $dbMain->query($sql);

      // 商品との紐付きがなくなったロケーションの削除
      $repoLocation->deleteEmptyLocation();

      // pci.ロケーション 更新処理
      // -- カレントロケーション
      $sql = <<<EOD
        UPDATE
        tb_productchoiceitems pci
        INNER JOIN tb_product_location pl ON pci.ne_syohin_syohin_code = pl.ne_syohin_syohin_code
                                         AND pl.position = 0
        INNER JOIN tb_location l ON pl.location_id = l.id
        SET pci.location = l.location_code
        WHERE pci.location <> l.location_code
EOD;
      $dbMain->query($sql);

      // -- 過去ロケーション(position = -1)
      $sql = <<<EOD
        UPDATE
        tb_productchoiceitems pci
        INNER JOIN tb_product_location pl ON pci.ne_syohin_syohin_code = pl.ne_syohin_syohin_code
                                         AND pl.position = -1
        INNER JOIN tb_location l ON pl.location_id = l.id
        LEFT JOIN tb_product_location pl_current ON pci.ne_syohin_syohin_code = pl_current.ne_syohin_syohin_code
                                                AND pl_current.position = 0
        SET pci.previouslocation = l.location_code
          , pci.location = CASE
               WHEN pl_current.ne_syohin_syohin_code IS NULL THEN '_new'
               ELSE pci.location
            END
        WHERE pci.previouslocation <> l.location_code
EOD;
      $dbMain->query($sql);

      // ロケーション変更履歴 保存
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_REFRESH_LOCATION, $this->account ? $this->account->getUsername() : 'BatchSV02', $actionKey);
      /* ------------ DEBUG LOG ------------ */  $logger->info($this->getLapTimeAndMemory('create location logs', 'main'));

      $dbMain->commit();

      // 一時テーブル削除
      $dbMain->query("DROP TABLE tmp_product_location_update");

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('ロケーション更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('ロケーション更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('ロケーション更新処理 エラー', 'ロケーション更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'ロケーション更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


