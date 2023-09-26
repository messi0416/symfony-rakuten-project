<?php
/**
 * 仕入入力確定
 * main jobで回すが、データの引き渡しが必要なため Commandまで持って行かずJobで処理
 * User: hirai
 * Date: 2017/07/18
 */

namespace BatchBundle\Job;

use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;

/**
 * Class SubmitPurchaseOrderListJob
 * @package BatchBundle\Job
 */
class SubmitPurchaseOrderListJob extends BaseJob
{
  public $queue = 'main';

  /** @var SymfonyUserInterface */
  protected $account;

  public function run($args)
  {
    try {
      $logger = $this->getLogger();
      $logger->initLogTimer();

      $logger->info('[submit purchase order list job] kicked.');
      $logger->info(print_r($this->args, true));

      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');

      // 排他制御判定
      // Job実処理フローの外側で判定し、処理中は途中終了を避けるためチェックしない
      $lockWaitLogger = $this->getContainer()->get('misc.util.file_logger')->setFileName('lock_process');
      $lockWaitInterval = 5; // 5秒間隔でロック取得試行
      $lockWaitLimit = (new \DateTime())->modify('+1 hour'); // ロック待ち最大1時間

      $this->runningJobName = $this->getCurrentCommandName();

      // 排他ロック取得処理 ※失敗時、 ProcessLockWaitException 送出
      $dbUtil->waitRunningProcessLock($this->runningJobName, $lockWaitInterval, $lockWaitLimit, $lockWaitLogger);

      // ---------------------------------------------------
      $logExecTitle = '仕入注残入力確定処理';
      $logger->setExecTitle($logExecTitle);
      $logger->info(sprintf('%sを開始しました。', $logExecTitle));

      $doctrine = $this->getContainer()->get('doctrine');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $doctrine->getConnection('main');

      // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
      if ($accountId = $this->getArgv('account')) {
        /** @var SymfonyUsers $account */
        $account = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
        if ($account) {
          $this->account = $account;
          $logger->setAccount($account);
        }
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $targetList = $this->getArgv('targetList');
      if (!$targetList) {
        throw new JobException('no target list');
      }

      // 一時テーブル作成
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_purchase_input");

      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_purchase_input (
            id INTEGER NOT NULL
          , input_regular INTEGER NOT NULL DEFAULT 0
          , input_defective INTEGER NOT NULL DEFAULT 0
          , input_shortage INTEGER NOT NULL DEFAULT 0
          , input_Quantity INTEGER NOT NULL DEFAULT 0
          , PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET utf8
EOD;
      $dbMain->query($sql);

      // 一時テーブルへデータ挿入
      $insertBuilder = new MultiInsertUtil("tmp_work_purchase_input", [
        'fields' => [
            'id' => \PDO::PARAM_STR
          , 'input_regular' => \PDO::PARAM_INT
          , 'input_defective' => \PDO::PARAM_INT
          , 'input_shortage' => \PDO::PARAM_INT
          , 'input_Quantity' => \PDO::PARAM_INT
        ]
        , 'prefix' => "INSERT INTO"
      ]);

      $dbUtil->multipleInsert($insertBuilder, $dbMain, $targetList, function($target) {
        $item = [
            'id'              => $target['id']
          , 'input_regular'   => (isset($target['inputRegularNum'])   ? intval($target['inputRegularNum']) : 0)
          , 'input_defective' => (isset($target['inputDefectiveNum']) ? intval($target['inputDefectiveNum']) : 0)
          , 'input_shortage'  => (isset($target['inputShortageNum'])  ? intval($target['inputShortageNum']) : 0)
          , 'input_Quantity'  => 0 // 現状、発注数の入力はなし
        ];

        return $item;
      }, 'foreach');

      // 更新処理開始
      $dbMain->beginTransaction();

      // 入力値をデータに反映
      $sql = <<<EOD
        UPDATE tb_individualorderhistory i
        INNER JOIN tmp_work_purchase_input W ON i.id = W.id
        SET
            i.発注数 = i.発注数 + W.input_Quantity
          , i.regular = i.regular + W.input_regular
          , i.defective = i.defective + W.input_defective
          , i.shortage = i.shortage + W.input_shortage
EOD;
      $dbMain->query($sql);

      // Call 平均仕入単価再計算(localCN)
      // → CSV出力共通処理で実行されるため、ここではスキップ （※必要になれば、処理を切り出してここでも実行）

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $doctrine->getRepository('MiscBundle:TbLocation');
      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $doctrine->getRepository('MiscBundle:TbWarehouse');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      $warehouseId = $this->getArgv('warehouseId', TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);
      /** @var TbWarehouse $warehouse */
      $warehouse = $repoWarehouse->find($warehouseId);
      $prefix = 'NEW_';
      $inputPrefix = trim($this->getArgv('prefix', ''));
      $inputPrefix = preg_replace('/[^A-Z0-9-_]/', '', strtoupper($inputPrefix));
      if (strlen($inputPrefix)) {
        $prefix = $inputPrefix . '-' . $prefix;
      }
      $newLocation = $repoLocation->createAutoLocation('auto', $prefix, $warehouse);

      if (!$newLocation) {
        throw new \RuntimeException('新規ロケーションの作成に失敗しました。 [' . $prefix . ' : ' . $warehouse->getName() . ']');
      }

      $sql = <<<EOD
        INSERT INTO tb_product_location (
            ne_syohin_syohin_code
          , location_id
          , stock
          , position
        )
        SELECT
            W.商品コード AS ne_syohin_syohin_code
          , :newLocationId AS location_id
          , W.stock
          , CASE WHEN COALESCE(T.max_position, -1) < 0 THEN  0 ELSE T.max_position + 1 END AS position
        FROM (
          SELECT
             i.商品コード
           , SUM(t.input_regular) AS stock
          FROM tmp_work_purchase_input t
          INNER JOIN tb_individualorderhistory i ON t.id = i.id
          WHERE t.input_regular > 0
          GROUP BY i.商品コード
        ) W
        LEFT JOIN (
          SELECT
               ne_syohin_syohin_code
             , MAX(position) AS max_position
          FROM tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
          WHERE l.warehouse_id = :warehouseId
          GROUP BY l.warehouse_id
                 , pl.ne_syohin_syohin_code
        ) T ON W.商品コード = T.ne_syohin_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':newLocationId', $newLocation->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $newLocation->getWarehouseId(), \PDO::PARAM_INT);
      $stmt->execute();

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $doctrine->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_SIRE, ($this->account ? $this->account->getUsername() : ''), $actionKey);

      // ラベル印字用データ挿入
      // ピッキングリスト印刷の最終入荷日のデータとして利用されていた。現在は、入荷入力の履歴として追加を行う。
      $sql = <<<EOD
        INSERT INTO tb_purchasedocument (
            仕入先コード
          , 商品コード
          , 仕入数
          , pre_label_output
          , 仕入単価, 発注伝票番号
          , 最終更新日
        )
        SELECT
            i.仕入先cd
          , i.商品コード
          , W.input_regular
          , W.input_regular
          , i.quantity_price
          , i.発注伝票番号
          , NOW()
        FROM tb_individualorderhistory i
        INNER JOIN tmp_work_purchase_input W ON i.id = W.id
        WHERE W.input_regular > 0
EOD;
      $dbMain->exec($sql);

      $dbMain->commit();

      // 暫定欠品が入力された商品に関して暫定欠品日付をセットし発注不可とする
      // → 現状、暫定欠品の入力欄がないためスキップ
      //      $sql = <<<EOD
      //UPDATE tb_productchoiceitems pci
      //INNER JOIN W_productchoiceitems_syohin_label ON tb_productchoiceitems.ne_syohin_syohin_code = W_productchoiceitems_syohin_label.ne_syohin_code
      //SET
      //  tb_productchoiceitems.temp_shortage_date = now()
      //  , 受発注可能フラグ = 0
      //WHERE
      //  W_productchoiceitems_syohin_label.発注停止F = TRUE
      //EOD;
      // => 実行（実装略）
      //
      // mysql = "update W_productchoiceitems_syohin_label set 発注停止F=false where 発注停止F=true"
      // => 実行（実装略）

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $dbUtil->deleteRunningLog($this->runningJobName);
      $this->runningJobName = null;

      $logger->info(sprintf('%sを終了しました。', $logExecTitle));

      return 0;

    } catch (JobException $e) {
      $logger->error('SubmitPurchaseOrderListJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through
    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }

      /** @var BatchLogger $logger */
      $logger = $this->getLogger();

      $logger->addDbLog(
        $logger->makeDbLog('' , '', 'エラー終了')->setInformation(
          ['message' => $e->getMessage()]
        )
      );

      $this->exitError(1, $e->getMessage());
      $this->runningJobName = null;
    }

    return 0;
  }
}
