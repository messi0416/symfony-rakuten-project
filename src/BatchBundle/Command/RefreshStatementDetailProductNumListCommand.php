<?php
/**
 * バッチ処理 納品書印刷待ち伝票一覧再集計処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\JobRequest;
use MiscBundle\Entity\TbProcess;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\Repository\TbDeliveryStatementDetailNumOrderListInfoRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshStatementDetailProductNumListCommand extends PlusnaoBaseCommand
{

  private $results;

  private $doChangeLocationOrder = false;

  /** @var JobRequest */
  private $jobRequest;

  protected function configure()
  {
    $this
      ->setName('batch:refresh-statement-detail-product-num-list')
      ->setDescription('納品書印刷待ち伝票一覧再集計処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('job-request', null, InputOption::VALUE_OPTIONAL, '（JobRequestからの実行時）jobKey ※進捗更新に利用')
      ->addOption('shipping-date', null, InputOption::VALUE_OPTIONAL, '出荷予定日')
      ->addOption('page-item-num', null, InputOption::VALUE_OPTIONAL, '明細区切り件数', TbDeliveryStatementDetailNumOrderListInfoRepository::STATEMENT_DETAIL_PRODUCT_NUM_LIST_PAGE_ITEM_NUM)
      ->addOption('change-location-order', null, InputOption::VALUE_OPTIONAL, 'ロケーションの並べ替えを行うか', 0)
      ->addOption('setting-id', null, InputOption::VALUE_OPTIONAL, '集計対象 1:通常 2:SHOPLIST 3:未引当全件', TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID)
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $settingId = intval($input->getOption('setting-id'));
    $settingName = "";
    if ($settingId == TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_SHOPLIST) { // SHOPLIST
      $settingName = "SHOPLIST";
    } else if ($settingId == TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_ASSIGNED_ALL) { // 移動在庫抽出用
      $settingName = "移動在庫抽出用";
    } else { // 通常
      $settingName = "通常";
    }
    $this->commandName = sprintf('納品書印刷待ち伝票一覧再集計処理 (%s)', $settingName);
  }

  /**
   * 履歴登録用のプロセスIDを取得する。
   * デフォルトではクラス名から取得する。
   * 同じクラスでも、大きく異なる複数の処理を行う機能では、オーバーライドして処理種別ごとの値を取得させる。
   * （定数定義は TbProcessクラスで行う）
   */
  protected function getProcessId(InputInterface $input) {
    $settingId = intval($input->getOption('setting-id'));
    if ($settingId == TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_SHOPLIST) { // SHOPLIST
      return TbProcess::PROCESS_ID_REFRESH_STATEMENT_DETAIL_PRODUCT_NUM_LIST_SHOPLIST;
    } else if ($settingId == TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_ASSIGNED_ALL) { // 移動在庫抽出用
      return TbProcess::PROCESS_ID_REFRESH_STATEMENT_DETAIL_PRODUCT_NUM_LIST_MOVING;
    } else { // 通常
      return TbProcess::PROCESS_ID_REFRESH_STATEMENT_DETAIL_PRODUCT_NUM_LIST_NORMAL;
    }
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $this->setInput($input);
    $this->setOutput($output);
    $settingId = intval($input->getOption('setting-id'));

    // JobRequest 登録
    // 進捗保存
    if ($jobKey = $input->getOption('job-request')) {
      /** @var JobRequest $account */
      $jobRequest = $container->get('doctrine')->getRepository('MiscBundle:JobRequest')->find($jobKey);
      if ($jobRequest) {
        $this->jobRequest = $jobRequest;
      }
    }

    try {
      $this->results = [
          'message' => null
      ];

      $this->updateJobRequestMessage([
          'caption' => $this->commandName
        , 'message' => '処理を開始しました。'
        , 'messageType' => 'info'
      ]);

      $borderDate = $input->getOption('shipping-date');
      if (!$borderDate || !preg_match('/^\d+-\d+-\d+$/', $borderDate)) {
        $borderDate = (new \DateTimeImmutable())->setTime(0, 0, 0);
      } else {
        $borderDate = (new \DateTimeImmutable($borderDate))->setTime(0, 0, 0);
      }

      $pageItemNum = intval($input->getOption('page-item-num'));
      if (!$pageItemNum) {
        $pageItemNum = TbDeliveryStatementDetailNumOrderListInfoRepository::STATEMENT_DETAIL_PRODUCT_NUM_LIST_PAGE_ITEM_NUM;
      }

      $this->doChangeLocationOrder = boolval($input->getOption('change-location-order'));
      $logger->info(sprintf("納品書印刷待ち伝票一覧再集計 ロケーション優先順位変更 => %s", $this->doChangeLocationOrder ? 'する' : 'しない'));

      // 一時テーブル作成・集計
      $this->updateJobRequestMessage([
          'message' => '集計処理を開始します。'
        , 'messageType' => 'info'
      ]);

      // 出荷量テーブル削除
      $this->deleteCaluculatedShipmentAmount();

      if ($settingId == TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_ASSIGNED_ALL) { // 移動在庫抽出用
        $this->refreshDetailNumOrderList($borderDate, 'assigned_all');
      } else { // 通常
        $this->refreshDetailNumOrderList($borderDate);
      }

      // 出荷量テーブル更新
      $this->insertCaluculatedShipmentAmount();

      $this->updateJobRequestMessage([
          'message' => '集計処理を完了しました。'
        , 'messageType' => 'info'
      ]);

      // 設定取得
      /** @var TbDeliveryStatementDetailNumOrderListInfoRepository $repoSetting */
      $repoSetting = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryStatementDetailNumOrderListInfo');
      $settingInfo = $repoSetting->getSettingInfo($settingId);
      if (!$settingInfo) {
        throw new \RuntimeException('設定情報の取得に失敗しました。');
      }

      /** @var \DateTime $lastUpdate */
      $lastUpdate = $settingInfo['last_updated'];
      if (!$lastUpdate || $lastUpdate->format('Y-m-d') != (new \DateTime())->format('Y-m-d') ) {
        $settingInfo['update_number'] = 1;
      } else {
        $settingInfo['update_number']++;
      }

      $settingInfo['shipping_date'] = $borderDate;
      $settingInfo['page_item_num'] = $pageItemNum;
      $settingInfo['account_name'] = $this->account ? $this->account->getUsername() : 'BatchSV02';
      $settingInfo['last_updated'] = new \DateTime();

      $repoSetting->updateSettingInfo($settingInfo, $settingId);

      $this->setPageToDetailNumOrderList();

      $this->updateJobRequestMessage([
          'message' => '全ての処理を完了しました。'
        , 'messageType' => 'success'
      ]);
    } catch (\Exception $e) {
      $this->updateJobRequestMessage([
          'message' => 'エラーが発生しました。' . $e->getMessage()
        , 'messageType' => 'danger'
      ]);
      throw $e;
    }
  }


  /**
   * 商品件数順一覧 取得用一時テーブル 更新
   * @param \DateTimeInterface $borderDate
   * @param string $baseView
   * @throws \Doctrine\DBAL\DBALException
   */
  private function refreshDetailNumOrderList(\DateTimeInterface $borderDate, $baseView = null)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    $logger = $this->getLogger();
    $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d')));

    // 対象とする受注取得VIEW
    switch ($baseView) {
      case 'assigned_all':
        $baseViewName = 'v_sales_detail_shipping_date_assigned_all';
        break;
      default:
        $baseViewName = 'v_sales_detail_shipping_date_total';
        break;
    }

    // この一連の処理は、トランザクション処理はなし。（TRUNCATE・DROP・CREATE やりたい放題。※主に開発時の事情で）
    // $temporaryWord = ' TEMPORARY ';
    $temporaryWord = ''; // FOR DEBUG

    // 一時テーブル作成
    $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_statement_detail_num_order_list");

    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_statement_detail_num_order_list (
          伝票番号 VARCHAR(20) NOT NULL
        , 商品コード VARCHAR(50) NOT NULL
        , 発送方法 VARCHAR(255) NOT NULL DEFAULT ''
        , 受注数 INT NOT NULL DEFAULT 0
        , 明細数 INT NOT NULL DEFAULT 0
        , 伝票明細数 INT NOT NULL DEFAULT 0
        , 伝票内同一商品明細数 INT NOT NULL DEFAULT 0
        , 同一商品明細数 INT NOT NULL DEFAULT 0
        , 印刷予定日 DATE NOT NULL DEFAULT '0000-00-00'
        , fetched TINYINT NOT NULL DEFAULT 0
        , PRIMARY KEY (`伝票番号`, `商品コード`)
      ) Engine=InnoDB DEFAULT CHARSET utf8
EOD;
    $dbMain->query($sql);

    // ロケーションブロック用
    // -- ブロック判定用 先頭ロケーション在庫表（1回チェックした後、ロケーションの並べ替えの対象抽出も行うためのテーブル）
    $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_statement_detail_first_location_stock");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_statement_detail_first_location_stock (
            location_id INTEGER NOT NULL
          , ne_syohin_syohin_code VARCHAR(50) NOT NULL
          , stock INTEGER NOT NULL DEFAULT 0
          , warehouse_id INTEGER NOT NULL /* 冗長 */
          , location_code VARCHAR(30) NOT NULL /* 冗長 */
          , PRIMARY KEY (location_id, ne_syohin_syohin_code)
          , INDEX index_location_code ( location_code )
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
    $dbMain->query($sql);

    // -- ブロック該当伝票番号格納
    $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_statement_detail_location_block_target");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_statement_detail_location_block_target (
          voucher_number VARCHAR(20) NOT NULL PRIMARY KEY
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
    $dbMain->query($sql);


    $this->updateJobRequestMessage([
        'message' => '一時テーブルを作成しました。'
      , 'messageType' => 'info'
    ]);

    $today = (new \DateTime())->setTime(0, 0, 0);
    $sql = <<<EOD
      INSERT INTO tmp_work_delivery_statement_detail_num_order_list (
           伝票番号
         , 商品コード
         , 発送方法
         , 受注数
         , 明細数
         , 伝票明細数
         , 伝票内同一商品明細数
         , 同一商品明細数
         , 印刷予定日
      )
      SELECT
           T1.伝票番号
         , T1.商品コード
         , T1.発送方法
         , T1.受注数合計
         , T1.明細数
         , T2.伝票明細数
         , T3.伝票内同一商品明細数
         , T4.同一商品明細数
         , T1.印刷予定日
      FROM (
        SELECT
            v.`伝票番号`
          , v.`商品コード`
          , v.代表商品コード
          , v.発送方法
          , SUM(v.`受注数`) AS 受注数合計
          , COUNT(*) AS 明細数
          , MIN(v.印刷予定日) AS 印刷予定日 /* 伝票内で単一のはずだが一応（WHERE句の条件的に）MINを取る */
        FROM `{$baseViewName}` v
        WHERE (v.納品書印刷指示日フラグ <> 0 AND v.納品書印刷指示日 <= :today )
           OR (v.納品書印刷指示日フラグ = 0 AND v.印刷予定日 <= :borderDate)
        GROUP BY v.`伝票番号`
               , v.`商品コード`
               , v.代表商品コード
               , v.発送方法
      ) T1
      INNER JOIN (
        SELECT
            v.`伝票番号`
          , COUNT(*) AS 伝票明細数
        FROM `{$baseViewName}` v
        WHERE (v.納品書印刷指示日フラグ <> 0 AND v.納品書印刷指示日 <= :today )
           OR (v.納品書印刷指示日フラグ = 0 AND v.印刷予定日 <= :borderDate)
        GROUP BY v.`伝票番号`
      ) T2 ON T1.伝票番号 = T2.伝票番号
      INNER JOIN (
        SELECT
            v.`伝票番号`
          , v.代表商品コード
          , COUNT(*) AS 伝票内同一商品明細数
        FROM `{$baseViewName}` v
        WHERE (v.納品書印刷指示日フラグ <> 0 AND v.納品書印刷指示日 <= :today )
           OR (v.納品書印刷指示日フラグ = 0 AND v.印刷予定日 <= :borderDate)
        GROUP BY v.`伝票番号`
               , v.代表商品コード
      ) T3 ON T1.伝票番号 = T3.伝票番号 AND T1.代表商品コード = T3.代表商品コード
      INNER JOIN (
        SELECT
            v.発送方法
          , v.代表商品コード
          , COUNT(*) AS 同一商品明細数
        FROM `{$baseViewName}` v
        WHERE (v.納品書印刷指示日フラグ <> 0 AND v.納品書印刷指示日 <= :today )
           OR (v.納品書印刷指示日フラグ = 0 AND v.印刷予定日 <= :borderDate)
        GROUP BY v.`発送方法`
               , v.代表商品コード
      ) T4 ON T1.発送方法 = T4.発送方法 AND T1.代表商品コード = T4.代表商品コード
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':borderDate', $borderDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':today', $today->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();

    $this->processExecuteLog->setProcessNumber1($stmt->rowCount()); // 処理件数1: 一時テーブルへ登録する総件数（明細数）
    $this->processExecuteLog->setVersion(1.0);

    $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 一時テーブルデータ登録完了', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d')));
    $this->updateJobRequestMessage([
        'message' => '一時テーブルへのデータ登録が完了しました。'
      , 'messageType' => 'info'
    ]);


    // 出荷可能倉庫毎に、 伝票の順位付けをした結果テーブルのデータを作成する。
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getShipmentEnabledWarehouses();
    $warehousesInfo = [];
    foreach($warehouses as $warehouse) {
      $warehousesInfo[$warehouse->getId()] = [
          'warehouseId' => $warehouse->getId()
        , 'warehouseName' => $warehouse->getName()
        , 'checkVouchers' => [
            'total' => 0
          , 'done' => 0
        ]
        , 'makeResult' => [
            'total' => 0
          , 'done' => 0
        ]
      ];
    }
    $this->updateJobRequestMessage([
      'warehousesInfo' => $warehousesInfo
    ]);

    // 結果格納テーブルを空に
    $dbMain->query("TRUNCATE tb_delivery_statement_detail_num_order_list_result");

    // -- 発送方法取得
    $deliveryMethods = [];
    $sql = <<<EOD
      SELECT
          発送方法 AS method
        , COUNT(*) AS num
      FROM tmp_work_delivery_statement_detail_num_order_list
      GROUP BY 発送方法
      ORDER BY num DESC
EOD;
    $stmt = $dbMain->query($sql);
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $deliveryMethods[] = $row;
    }

    $commonUtil = $this->getDbCommonUtil();
    $mallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);

    // 出荷可能倉庫毎に処理
    // 処理されず残った伝票が、出荷不能伝票。（倉庫単体、あるいは全体での在庫不足）
    foreach($warehouses as $warehouse) {

      $this->updateJobRequestMessage([
          'message' => sprintf('倉庫：%s 集計開始', $warehouse->getName())
        , 'messageType' => 'info'
      ]);
      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 集計開始', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));

      // 一時テーブル作成：商品コード・伝票番号 写し
      // ※TEMPORARY TABLEが自己結合できないための写しテーブル
      //   だったが、複数倉庫を処理するために、1倉庫分のデータを格納するテーブルにも兼用される。
      $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_statement_detail_num_order_list_code_match");
      $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_statement_detail_num_order_list_code_match (
          伝票番号 VARCHAR(20) NOT NULL
        , 商品コード VARCHAR(50) NOT NULL DEFAULT ''
        , 受注数 INTEGER NOT NULL DEFAULT 0
        , PRIMARY KEY (`商品コード`, `伝票番号`)
      ) Engine=InnoDB DEFAULT CHARSET utf8
EOD;
      $dbMain->query($sql);

      // 処理対象伝票番号取得SQL

      // FBA倉庫なら、SHOPLIST受注は含まない
      if ($warehouse->isFbaVirtualWarehouse()) {
        $sql = <<<EOD
          SELECT
             l.伝票番号
          FROM tmp_work_delivery_statement_detail_num_order_list l
          LEFT JOIN tmp_work_delivery_statement_detail_num_order_list_code_match t  
            ON l.伝票番号 = t.伝票番号 AND l.商品コード = t.商品コード
          LEFT JOIN (
            SELECT
              l2.伝票番号 
            FROM tmp_work_delivery_statement_detail_num_order_list l2
            INNER JOIN tb_sales_detail_analyze a ON l2.伝票番号 = a.伝票番号
            WHERE a.店舗コード = :neMallIdShoplist
            GROUP BY l2.伝票番号
          ) SHOPLIST ON l.伝票番号 = SHOPLIST.伝票番号
          WHERE l.fetched = 0
            AND t.伝票番号 IS NULL
            AND SHOPLIST.伝票番号 IS NULL
          GROUP BY l.伝票番号
          ORDER BY MIN(l.印刷予定日)
EOD;
        $stmtVoucherNumber = $dbMain->prepare($sql);
        $stmtVoucherNumber->bindValue(':neMallIdShoplist', $mallShoplist->getNeMallId());

      } else {
        $sql = <<<EOD
          SELECT
             l.伝票番号
          FROM tmp_work_delivery_statement_detail_num_order_list l
          LEFT JOIN tmp_work_delivery_statement_detail_num_order_list_code_match t  ON l.伝票番号 = t.伝票番号 AND l.商品コード = t.商品コード
          WHERE l.fetched = 0
            AND t.伝票番号 IS NULL
          GROUP BY l.伝票番号
          ORDER BY MIN(l.印刷予定日)
EOD;
        $stmtVoucherNumber = $dbMain->prepare($sql);

      }

      $stmtVoucherNumber->execute();

      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 伝票一覧取得(%d件)', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName(), $stmtVoucherNumber->rowCount()));
      $this->updateJobRequestMessage([
          'warehousesInfo' => [
            $warehouse->getId() => [
              'checkVouchers' => [
                  'total' => $stmtVoucherNumber->rowCount()
                , 'done' => 0
              ]
            ]
          ]
      ]);

      // ------------------------------------------------
      // 効率化のため、まず全受注でまとめてチェック
      // 在庫OK（= Not NG）な伝票はそのまま合格。（#29199 優先順位上位の伝票が出荷されない可能性を許容。）

      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 一括チェック開始', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));

      // どうも Can't reopen table に引っかかるので、やけくそで一時テーブル作成連打 その１
      $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_short_stock");
      $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_short_stock (
          商品コード VARCHAR(50) NOT NULL DEFAULT ''
        , PRIMARY KEY (`商品コード`)
      ) Engine=InnoDB DEFAULT CHARSET utf8
      SELECT
          T.商品コード
      FROM ( /* 未取得出荷予定伝票 受注商品数 */
        SELECT
            l.`商品コード`
          , SUM(l.`受注数`) AS 受注数
        FROM tmp_work_delivery_statement_detail_num_order_list l
        WHERE l.fetched = 0
        GROUP BY l.`商品コード`
      ) T

      LEFT JOIN ( /* 倉庫在庫数 */
        SELECT
            v.ne_syohin_syohin_code
          , v.stock_remain AS 在庫数
        FROM v_product_stock_picking_assign v
        WHERE v.warehouse_id = :warehouseId
      ) S ON T.`商品コード` = S.ne_syohin_syohin_code

      WHERE T.受注数 > COALESCE(S.在庫数, 0)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      // 連打その２
      $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_short_voucher");
      $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_short_voucher (
          伝票番号 VARCHAR(20) NOT NULL
        , PRIMARY KEY (`伝票番号`)
      ) Engine=InnoDB DEFAULT CHARSET utf8
      SELECT
        DISTINCT l.`伝票番号`
      FROM tmp_work_delivery_statement_detail_num_order_list l
      INNER JOIN tmp_work_delivery_short_stock AS NOT_ENOUGH ON l.`商品コード` = NOT_ENOUGH.商品コード
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // ようやっと本丸。全体チェック＆挿入 実行
      $sql = <<<EOD
        INSERT IGNORE INTO tmp_work_delivery_statement_detail_num_order_list_code_match (
            伝票番号
          , 商品コード
          , 受注数
        )
        SELECT
            l.伝票番号
          , l.商品コード
          , l.受注数
        FROM tmp_work_delivery_statement_detail_num_order_list l
        LEFT JOIN tmp_work_delivery_short_voucher NG ON l.伝票番号 = NG.伝票番号
        WHERE NG.伝票番号 IS NULL
EOD;
      $stmtCheckAllAndInsert = $dbMain->prepare($sql);
      $stmtCheckAllAndInsert->execute();

      // FBA倉庫なら、SHOPLIST受注は含まない（= SHOPLIST受注分削除）
      if ($warehouse->isFbaVirtualWarehouse()) {
        $sql = <<<EOD
          DELETE t
          FROM tmp_work_delivery_statement_detail_num_order_list_code_match t
          INNER JOIN (
            SELECT
              l2.伝票番号 
            FROM tmp_work_delivery_statement_detail_num_order_list l2
            INNER JOIN tb_sales_detail_analyze a ON l2.伝票番号 = a.伝票番号
            WHERE a.店舗コード = :neMallIdShoplist
            GROUP BY l2.伝票番号
          ) SHOPLIST ON t.伝票番号 = SHOPLIST.伝票番号
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':neMallIdShoplist', $mallShoplist->getNeMallId());
        $stmt->execute();
      }

      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 一括チェック完了', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));
      $this->updateJobRequestMessage([
        'warehousesInfo' => [
          $warehouse->getId() => [
            'checkVouchers' => [
              'done' => $dbMain->query("SELECT COUNT(DISTINCT 伝票番号) FROM tmp_work_delivery_statement_detail_num_order_list_code_match")->fetchColumn(0)
            ]
          ]
        ]
      ]);
      // ------------------------------------------------ / 全体チェック＆合格移動処理

      // 倉庫に在庫がない商品を含む伝票を除いて伝票番号取得
      // FBA倉庫なら、SHOPLIST受注は含まない
      if ($warehouse->isFbaVirtualWarehouse()) {
        $sql = <<<EOD
          SELECT
             l.伝票番号
             , SUM(
                CASE WHEN S.ne_syohin_syohin_code IS NULL THEN 1 ELSE 0 END
             ) AS no_stock_product 
          FROM tmp_work_delivery_statement_detail_num_order_list l
          LEFT JOIN tmp_work_delivery_statement_detail_num_order_list_code_match t  ON l.伝票番号 = t.伝票番号 AND l.商品コード = t.商品コード
          LEFT JOIN (
              SELECT 
                pl.ne_syohin_syohin_code
              FROM tb_product_location pl 
              INNER JOIN tb_location l ON pl.location_id = l.id
              WHERE l.warehouse_id = :warehouseId
                AND pl.stock > 0
                AND pl.position >= 0
              GROUP BY pl.ne_syohin_syohin_code
          ) S ON l.`商品コード` = S.ne_syohin_syohin_code        
          LEFT JOIN (
            SELECT
              l2.伝票番号 
            FROM tmp_work_delivery_statement_detail_num_order_list l2
            INNER JOIN tb_sales_detail_analyze a ON l2.伝票番号 = a.伝票番号
            WHERE a.店舗コード = :neMallIdShoplist
            GROUP BY l2.伝票番号
          ) SHOPLIST ON l.伝票番号 = SHOPLIST.伝票番号
          WHERE l.fetched = 0
            AND t.伝票番号 IS NULL
            AND SHOPLIST.`伝票番号` IS NULL
          GROUP BY l.伝票番号
          HAVING no_stock_product = 0
          ORDER BY MIN(l.印刷予定日), l.伝票番号
EOD;
        $stmtVoucherNumber = $dbMain->prepare($sql);
        $stmtVoucherNumber->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        $stmtVoucherNumber->bindValue(':neMallIdShoplist', $mallShoplist->getNeMallId(), \PDO::PARAM_INT);

      } else {
        $sql = <<<EOD
          SELECT
             l.伝票番号
             , SUM(
                CASE WHEN S.ne_syohin_syohin_code IS NULL THEN 1 ELSE 0 END
             ) AS no_stock_product 
          FROM tmp_work_delivery_statement_detail_num_order_list l
          LEFT JOIN tmp_work_delivery_statement_detail_num_order_list_code_match t  ON l.伝票番号 = t.伝票番号 AND l.商品コード = t.商品コード
          LEFT JOIN (
              SELECT 
                pl.ne_syohin_syohin_code
              FROM tb_product_location pl 
              INNER JOIN tb_location l ON pl.location_id = l.id
              WHERE l.warehouse_id = :warehouseId
                AND pl.stock > 0
                AND pl.position >= 0
              GROUP BY pl.ne_syohin_syohin_code
          ) S ON l.`商品コード` = S.ne_syohin_syohin_code        
          WHERE l.fetched = 0
            AND t.伝票番号 IS NULL
          GROUP BY l.伝票番号
          HAVING no_stock_product = 0
          ORDER BY MIN(l.印刷予定日), l.伝票番号
EOD;
        $stmtVoucherNumber = $dbMain->prepare($sql);
        $stmtVoucherNumber->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
      }
      $stmtVoucherNumber->execute();
      $voucherNumberResultList = $stmtVoucherNumber->fetchAll(\PDO::FETCH_ASSOC);

      // 残ったもの（在庫不足商品を含み、在庫無し商品を含まない伝票）を1伝票ずつ、倉庫の在庫残数が足りているかチェックを行いながら一時テーブルへコピー

      // DB側で処理させているこの部分が極めて重いようなので、データを取得してPHPで処理方式に変更してみる

      // 残ったものが必要としている在庫不足SKUについて、全てこの倉庫での在庫数を全て取得する。
      // 次に、1伝票ずつOK/NG判定を行い、都度在庫数をPHPのメモリ上で減らしていく。
      // 最後の伝票まで処理したらこの倉庫の処理が完了

      if (count($voucherNumberResultList) > 0) {
        // 必要なSKU一覧取得
        $voucherNumberList = array_column($voucherNumberResultList, '伝票番号');

        // 伝票番号は1000件ごとに分割（PDOのパラメータ数が1000までなので）
        $searchVoucherNumberChunkList = array_chunk($voucherNumberList, 1000, false);
        $skuList = array();
        foreach ($searchVoucherNumberChunkList as $searchVoucherNumberChunk) {
          $skuList = array_merge($skuList, $this->getVoucherOrderSkuList($searchVoucherNumberChunk));
        }
        $skuList = array_unique($skuList); // 重複整理

        // SKUリストは999件ごとに分割（PDOのパラメータ数が1000までなので。1000件目は倉庫ID）
        $searchSkuChunkList = array_chunk($skuList, 999, false);
        $stockList = array();
        foreach ($searchSkuChunkList as $searchSkuChunk) {
          $stockList = array_merge($stockList, $this->getSkuStockList($warehouse->getId(), $searchSkuChunk));
        }

        $sql = <<<EOD
          SELECT
            l.`商品コード` as ne_syohin_syohin_code
            , SUM(l.`受注数`) AS amount
          FROM tmp_work_delivery_statement_detail_num_order_list l
          WHERE l.`伝票番号` = :voucherNumber
          GROUP BY l.`商品コード`
EOD;
        $selectVoucherDetailStmt = $dbMain->prepare($sql);

        $sql = <<<EOD
        INSERT IGNORE INTO tmp_work_delivery_statement_detail_num_order_list_code_match (
            伝票番号
          , 商品コード
          , 受注数
        )
        SELECT
            伝票番号
          , 商品コード
          , 受注数
        FROM tmp_work_delivery_statement_detail_num_order_list
        WHERE 伝票番号 = :voucherNumber
EOD;
        $stmtInsert = $dbMain->prepare($sql);

        // 伝票毎に、全ての明細の在庫が足りているか、不足しているかをチェックする
        $num = 0;
        foreach ($voucherNumberList as $voucherNumber) {
          $selectVoucherDetailStmt->bindValue(':voucherNumber', $voucherNumber, \PDO::PARAM_INT);
          $selectVoucherDetailStmt->execute();
          $selectVoucherDetailResult = $selectVoucherDetailStmt->fetchAll(\PDO::FETCH_ASSOC);
          $isStockOk = true; // 全明細で在庫が足りていればtrue、不足ならfalse
          foreach ($selectVoucherDetailResult as $detail) {
            if ($stockList[$detail['ne_syohin_syohin_code']] - $detail['amount'] < 0) {
              $isStockOk = false;
              break;
            }
          }
          // 全明細の在庫が足りていたので、在庫を減算しOKに登録
          if ($isStockOk) {
            foreach ($selectVoucherDetailResult as $detail) {
              $stockList[$detail['ne_syohin_syohin_code']] = $stockList[$detail['ne_syohin_syohin_code']] - $detail['amount'];
            }
            $stmtInsert->bindValue(':voucherNumber', $voucherNumber, \PDO::PARAM_INT);
            $stmtInsert->execute();

            if ($num++ % 10 == 0) {
              $this->updateJobRequestMessage([
                  'warehousesInfo' => [
                      $warehouse->getId() => [
                          'checkVouchers' => [
                              'done' => $dbMain->query("SELECT COUNT(DISTINCT 伝票番号) FROM tmp_work_delivery_statement_detail_num_order_list_code_match")->fetchColumn(0)
                          ]
                      ]
                  ]
              ]);
            }
          }
        }
      }

      // この倉庫での出荷可能伝票数
      $targetVoucherNum = $dbMain->query("SELECT COUNT(DISTINCT 伝票番号) FROM tmp_work_delivery_statement_detail_num_order_list_code_match")->fetchColumn(0);
      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 出荷可能伝票抽出完了(%d 件)', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName(), $targetVoucherNum));
      $this->updateJobRequestMessage([
          'message' => sprintf('倉庫：%s 出荷可能伝票抽出完了', $warehouse->getName())
        , 'messageType' => 'info'
        , 'warehousesInfo' => [
          $warehouse->getId() => [
            'checkVouchers' => [
              'done' => $targetVoucherNum
            ]
            , 'makeResult' => [
                'total' => $targetVoucherNum
              , 'done' => 0
            ]
          ]
        ]
      ]);


      // 並べ替え ＆ 結果格納

      // ロケーションブロック取得
      /** @var TbDeliveryPickingBlockRepository $repoBlock */
      $repoBlock = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingBlock');
      $blocks = $repoBlock->getBlocks($warehouse);

      // 並べ替え方法その3：先頭ロケーションのみチェック 用に、
      // 先頭ロケーションで足りていない商品のロケーション優先順位を変更
      if ($this->doChangeLocationOrder) {
        $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s ロケーション優先順位変更開始', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));
        $this->checkFirstLocationAndPickupOtherLocation($warehouse);
        $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s ロケーション優先順位変更終了', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));
      }

      $productCount = 0;
      // -- 発送方法ごとに順位・ページ付け
      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 発送方法毎に順位・ページ付け　開始', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));

        // 並べ替え方法：ロケーションブロック順
        $this->createResultByLocationBlockOrderByTopLocationStocks($warehouse, $deliveryMethods, $blocks, $productCount);


      $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 倉庫：%s 発送方法毎に順位・ページ付け　終了', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d'), $warehouse->getName()));

      // 処理済み数（完了時）
      $done = $this->countResult($warehouse);

      $this->updateJobRequestMessage([
        'warehousesInfo' => [
          $warehouse->getId() => [
            'makeResult' => [
              'done' => $done
            ]
          ]
        ]
      ]);
    }

    $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 出荷可能伝票 集計完了', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d')));
    $this->updateJobRequestMessage([
        'message' => '出荷可能伝票 集計完了'
      , 'messageType' => 'info'
    ]);

    // 一時テーブルに残った伝票が出荷不能伝票。
    $sql = <<<EOD
          INSERT INTO tb_delivery_statement_detail_num_order_list_result (
              warehouse_id
            , 伝票番号
            , 発送方法
            , 対象商品コード
            , 対象商品明細数
            , 対象商品受注数
            , 明細数
            , 受注数
            , 印刷予定日
          )
          SELECT
              :warehouseId
            , l.伝票番号
            , '（出荷不能）' AS 発送方法
            , LEFT(GROUP_CONCAT(l.商品コード), 50) AS 商品コード
            , 0 AS 対象商品明細数
            , 0 AS 対象商品受注数
            , COUNT(*)     AS 明細数
            , SUM(l.受注数) AS 受注数
            , MIN(l.印刷予定日) AS 印刷予定日
          FROM tmp_work_delivery_statement_detail_num_order_list l
          WHERE l.fetched = 0 /* 未取得の伝票から */
          GROUP BY l.伝票番号
          ORDER BY 明細数 ASC
                 , 対象商品受注数 DESC
                 , l.伝票番号
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', TbDeliveryStatementDetailNumOrderListInfoRepository::SHIPPING_IMPOSSIBLE_WAREHOUSE_ID, \PDO::PARAM_INT);
    $stmt->execute();

    $this->processExecuteLog->setProcessNumber2($stmt->rowCount()); // 処理件数2: 出荷不能伝票件数

    $logger->info(sprintf('納品書印刷待ち伝票一覧集計 (通常)(%s) : %s : 出荷不能伝票 集計完了', ($this->account ? $this->account->getClientName() : '-'), $borderDate->format('Y-m-d')));
    $this->updateJobRequestMessage([
        'message' => '出荷不能伝票 集計完了'
      , 'messageType' => 'info'
    ]);

    return;
  }

  /**
   * 各伝票で必要としているSKUのリスト取得。
   *
   * @param unknown $voucherNumberList 伝票番号のリスト。1000件まで（パラメータ1001件以上はエラーとなる）
   */
  private function getVoucherOrderSkuList($voucherNumberList) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    $inStr = substr(str_repeat(',?', count($voucherNumberList)), 1);
    $sql = <<<EOD
          SELECT distinct 商品コード as ne_syohin_syohin_code
          FROM tmp_work_delivery_statement_detail_num_order_list
          WHERE 伝票番号 IN ({$inStr});
EOD;
    $stmtSkuList = $dbMain->prepare($sql);
    $stmtSkuList->execute($voucherNumberList);
    $skuResultList = $stmtSkuList->fetchAll(\PDO::FETCH_ASSOC);
    $skuList = array_column($skuResultList, 'ne_syohin_syohin_code');
    return $skuList;
  }

  /**
   * 各SKUの在庫数取得（ロケーションの在庫数 - この処理で引当済みの在庫数）。
   * キーはSKUコード、値が現在の在庫数の連想配列を返却する。
   *
   * @param integer $warehouseId 倉庫ID
   * @param array $skuList SKUリスト。999件まで（パラメータ1001件以上はエラーとなる。1000件目は倉庫ID）
   */
  private function getSkuStockList($warehouseId, $skuList) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    $inStr = substr(str_repeat(',?', count($skuList)), 1);
    $sql = <<<EOD
          SELECT v.ne_syohin_syohin_code, v.stock_remain - IFNULL(SUM(tmp.受注数), 0) as stock_remain
          FROM v_product_stock_picking_assign v
          LEFT JOIN tmp_work_delivery_statement_detail_num_order_list_code_match tmp ON tmp.商品コード = v.ne_syohin_syohin_code
          WHERE ne_syohin_syohin_code IN ({$inStr})
            AND v.warehouse_id = ?
          GROUP BY v.ne_syohin_syohin_code
EOD;
    $skuList[] = $warehouseId;
    $stmtStockList = $dbMain->prepare($sql);
    $stmtStockList->execute($skuList);
    $stockResultList = $stmtStockList->fetchAll(\PDO::FETCH_ASSOC);
    $stockList = array_column($stockResultList, 'stock_remain', 'ne_syohin_syohin_code');
    return $stockList;

  }


  /**
   * JobRequest メッセージ更新
   * @param $data
   */
  private function updateJobRequestMessage($data)
  {
    if (!$this->jobRequest) {
      return;
    }

    $this->jobRequest->setInfoMerge($data);
    $em = $this->getDoctrine()->getManager('main');
    $em->flush();

    usleep(100000); // 0.1秒休憩。
  }

  /**
   * ロケーションブロックによる順位付け＆結果格納処理
   * 各商品の先頭ロケーションのみで全受注数が足りているもののみを対象にしてブロック分け
   * → 1点しか買われていない商品が多いため、現時点では意外と有効
   * @param TbWarehouse $warehouse
   * @param array $deliveryMethods 今回の配送方法リスト
   * @param array $blocks
   * @param int &$productCount
   * @throws \Doctrine\DBAL\DBALException
   */
  private function createResultByLocationBlockOrderByTopLocationStocks($warehouse, $deliveryMethods, $blocks, &$productCount)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();
    $logger = $this->getLogger();

    $borderNum = TbDeliveryStatementDetailNumOrderListInfoRepository::STATEMENT_DETAIL_PRODUCT_NUM_LIST_BORDER_DETAIL_NUM;

    // 最後に空ブロック（残り全部に対応）を追加
    $blocks[] = [
        'block' => null
      , 'details' => []
    ];

    foreach($blocks as $block) {

      // ブロック該当伝票を一時テーブルに格納
      $dbMain->query("TRUNCATE tmp_work_delivery_statement_detail_location_block_target");

      $blockName = $block['block'] ? $block['block']->getBlockCode() : '-';
      $blockPattern = implode(',', array_reduce($block['details'], function($result, $detail){ $result[] = $detail->getPattern(); return $result; }, []));


      if ($block['details']) {

        // ブロック該当伝票を取得
        $otherLocationWheres = [];
        $params = [];

        /** @var TbDeliveryPickingBlockDetail $detail */
        foreach($block['details'] as $i => $detail) {
          $patternKey = sprintf(":pattern%03d", $i);
          $otherLocationWheres[] = sprintf("S.location_code LIKE %s", $patternKey);
          $params[$patternKey] = $commonUtil->escapeLikeString($detail->getPattern()) . '%';
        }

        $otherLocationWhereSql = implode(" OR ", $otherLocationWheres);

        $sql = <<<EOD
          INSERT INTO tmp_work_delivery_statement_detail_location_block_target (
            voucher_number
          )
          SELECT
              ol.`伝票番号` AS voucher_number
          FROM tmp_work_delivery_statement_detail_num_order_list ol
          INNER JOIN tmp_work_delivery_statement_detail_num_order_list_code_match m
                          ON ol.伝票番号 = m.伝票番号
                         AND ol.商品コード = m.商品コード
          LEFT JOIN (

            SELECT
                S.warehouse_id
              , S.ne_syohin_syohin_code
              , S.location_code
              , S.stock
              , O.order_num
              , A.assign_total
              , (
                    S.stock
                  - A.assign_total
                  - O.order_num
                ) AS stock_remain -- 全在庫の残数ではなく、先頭ロケーションで確実に残る数を取得
            FROM v_product_first_location S
            INNER JOIN v_product_stock_picking_assign A
                         ON S.warehouse_id = A.warehouse_id
                        AND S.ne_syohin_syohin_code = A.ne_syohin_syohin_code
            INNER JOIN ( /* この倉庫で出荷する伝票の受注数 */
              SELECT
                  ol.`商品コード` AS ne_syohin_syohin_code
                , SUM(ol.`受注数`) AS order_num
              FROM tmp_work_delivery_statement_detail_num_order_list ol
              INNER JOIN tmp_work_delivery_statement_detail_num_order_list_code_match m ON ol.伝票番号 = m.伝票番号 AND ol.商品コード = m.商品コード
              GROUP BY ol.`商品コード`
            ) O ON S.ne_syohin_syohin_code = O.ne_syohin_syohin_code

            -- 絞り込み条件：　現在倉庫で、先頭ロケーションで出荷数が足りるもの、かつ先頭ロケーションが指定のブロックのもの
            WHERE S.warehouse_id = :warehouseId
              AND (
                    S.stock
                  - A.assign_total
                  - O.order_num
              ) >= 0

              AND ( {$otherLocationWhereSql} )

          ) T ON ol.`商品コード` = T.ne_syohin_syohin_code
          WHERE  ol.fetched = 0 /* 未取得の伝票から */
          GROUP BY ol.`伝票番号`
          HAVING SUM(
               CASE WHEN T.ne_syohin_syohin_code IS NULL THEN 1 ELSE 0 END
            ) = 0
          ORDER BY ol.伝票番号
          ;
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
        foreach($params as $k => $v) {
          $stmt->bindValue($k, $v, \PDO::PARAM_STR);
        }
        $stmt->execute();

        // 最後に全件
      } else {

        $sql = <<<EOD
          INSERT INTO tmp_work_delivery_statement_detail_location_block_target (
            voucher_number
          )
          SELECT
              ol.`伝票番号` AS voucher_number
          FROM tmp_work_delivery_statement_detail_num_order_list ol
          INNER JOIN tmp_work_delivery_statement_detail_num_order_list_code_match m ON ol.伝票番号 = m.伝票番号 AND ol.商品コード = m.商品コード
          WHERE ol.fetched = 0 /* 未取得の伝票から */
          GROUP BY ol.`伝票番号`
          ;
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();

      }

      // 商品を優先順位で取得し、順に結果テーブルへ挿入
      foreach ($deliveryMethods as $deliveryMethod) {
        do {
          $logger->debug("商品を優先順位で取得し、順に結果テーブルへ挿入: deliveryMethod[" . $deliveryMethod['method'] . "], [$borderNum]");
          $sql = <<<EOD
            SELECT
                l.発送方法
              , l.商品コード
              , SUM(l.明細数) AS 明細数合計
              , AVG(l.`伝票内同一商品明細数`) AS 同一商品指数
              , MAX(l.同一商品明細数) AS 同一商品明細数
            FROM tmp_work_delivery_statement_detail_num_order_list l
            INNER JOIN tmp_work_delivery_statement_detail_num_order_list_code_match l2 ON l.伝票番号 = l2.伝票番号 AND l.商品コード = l2.商品コード
            INNER JOIN tmp_work_delivery_statement_detail_location_block_target t ON l.伝票番号 = t.voucher_number
            WHERE l.発送方法 = :deliveryMethod
              AND l.fetched = 0 /* 未取得の伝票から */
            GROUP BY l.商品コード
            HAVING 明細数合計 >= :borderNum
            ORDER BY 明細数合計 DESC
                   , l.同一商品明細数 DESC
                   , 同一商品指数 DESC
            LIMIT 1
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':deliveryMethod', $deliveryMethod['method'], \PDO::PARAM_STR);
          $stmt->bindValue(':borderNum', $borderNum, \PDO::PARAM_INT);
          $stmt->execute();

          $row = $stmt->fetch(\PDO::FETCH_ASSOC);

          // $this->getLogger()->info(print_r($row, true)); // 無限ループ時 チェック
          // 該当商品がなくなったら終了
          if (!$row) {
            break;
          }

          $productCount++;

          // 結果テーブルへ挿入
          $logger->debug("結果テーブルへ挿入：warehouseId[" . $warehouse->getId() . "], deliveryMethod[" . $deliveryMethod['method'] . "], targetProduct[" . $row['商品コード'] . "]");
          $sql = <<<EOD
          INSERT INTO tb_delivery_statement_detail_num_order_list_result (
              warehouse_id
            , 伝票番号
            , 発送方法
            , 対象商品コード
            , 対象商品明細数
            , 対象商品受注数
            , 明細数
            , 受注数
            , 印刷予定日
          )
          SELECT
              :warehouseId
            , l.伝票番号
            , l.発送方法
            , :targetProductDisplay AS 対象商品コード
            , SUM(
                CASE WHEN l.商品コード = :targetProduct THEN 1 ELSE 0 END
              ) AS 対象商品明細数
            , SUM(
                CASE WHEN l.商品コード = :targetProduct THEN l.受注数 ELSE 0 END
              ) AS 対象商品受注数
            , COUNT(*)     AS 明細数
            , SUM(l.受注数) AS 受注数
            , MIN(l.印刷予定日) AS 印刷予定日

          FROM tmp_work_delivery_statement_detail_num_order_list l
          WHERE l.発送方法 = :deliveryMethod
            AND l.fetched = 0 /* 未取得の伝票から */
            AND EXISTS (
              SELECT
                *
              FROM tmp_work_delivery_statement_detail_num_order_list_code_match l2
              INNER JOIN tmp_work_delivery_statement_detail_location_block_target t ON l2.伝票番号 = t.voucher_number
              WHERE l.伝票番号 = l2.伝票番号
                AND l2.商品コード = :targetProduct
            )
          GROUP BY l.伝票番号
                 , l.発送方法
          ORDER BY 明細数 ASC
                 , 対象商品受注数 DESC
                 , l.伝票番号
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
          $stmt->bindValue(':deliveryMethod', $deliveryMethod['method'], \PDO::PARAM_STR);
          $stmt->bindValue(':targetProduct', $row['商品コード'], \PDO::PARAM_STR);
          $stmt->bindValue(':targetProductDisplay', sprintf('%s:%s', ($blockName ? $blockName : '-'), $row['商品コード']), \PDO::PARAM_STR);
          $stmt->execute();

          // 取得済み伝票にチェック
          $sql = <<<EOD
          UPDATE
          tmp_work_delivery_statement_detail_num_order_list l
          INNER JOIN tb_delivery_statement_detail_num_order_list_result r ON l.伝票番号 = r.伝票番号
          SET l.fetched = -1
EOD;
          $dbMain->query($sql);

          if ($productCount % 20 == 0) {
            // 処理済み数
            $done = $this->countResult($warehouse);

            $this->updateJobRequestMessage([
              'warehousesInfo' => [
                $warehouse->getId() => [
                  'makeResult' => [
                    'done' => $done
                  ]
                ]
              ]
            ]);
          }

        } while (1);
      }
    }
  }

  /**
   * ブロック並べ替え 先頭ロケーション判定補完 漏れた商品のロケーションの優先順位変更処理
   * ここだけはロケーション履歴ログに残すために、トランザクションで実行。
   * つまりは、本当のTEMPORARYテーブルじゃないとだめ。（開発用に、$temporaryWord を別個に利用する。）
   * @param TbWarehouse $warehouse
   */
  private function checkFirstLocationAndPickupOtherLocation(TbWarehouse $warehouse)
  {
    $dbMain = $this->getDb('main');

    // この処理に限ってはトランザクション内で実行。そのために、独立したtemporaryWord
    $temporaryWord = 'TEMPORARY';
    // $temporaryWord = ''; // FOR DEBUG

    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

    // 更新処理
    $dbMain->beginTransaction();

    // （履歴用）アクションキー 作成＆セット
    $actionKey = $repo->setLocationLogActionKey($dbMain);

    // 新しい並べ替え用テーブル （tb_product_location と連結してのposition更新テーブル）
    $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_statement_detail_change_location_order");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_statement_detail_change_location_order (
          id INTEGER NOT NULL AUTO_INCREMENT
        , location_id INTEGER NOT NULL
        , ne_syohin_syohin_code VARCHAR(50) NOT NULL
        , PRIMARY KEY (id)
        , UNIQUE INDEX uniq_location (`ne_syohin_syohin_code`, `location_id`)
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
    $dbMain->query($sql);

    // 先頭ロケーションで足りていない商品のロケーションから、足りているロケーションを取得して、position更新突き合わせ用テーブルへINSERT
    // これが新しい先頭ロケーションとなる
    $sql = <<<EOD
      INSERT INTO tmp_work_delivery_statement_detail_change_location_order (
          location_id
        , ne_syohin_syohin_code
      )
      SELECT
          pl.location_id
        , pl.ne_syohin_syohin_code
      FROM tb_product_location pl
      INNER JOIN tb_location l ON ( pl.location_id = l.id AND l.warehouse_id = :warehouseId)
      INNER JOIN (
        SELECT
            pl.ne_syohin_syohin_code
          , MIN(position) AS position
        FROM tb_product_location pl
        INNER JOIN tb_location l ON ( pl.location_id = l.id AND l.warehouse_id = :warehouseId)
        INNER JOIN (
            SELECT
                S.ne_syohin_syohin_code
              , O.order_num
              , A.assign_total
            FROM v_product_first_location S
            INNER JOIN v_product_stock_picking_assign A
                         ON S.warehouse_id = A.warehouse_id
                        AND S.ne_syohin_syohin_code = A.ne_syohin_syohin_code
            INNER JOIN ( /* この倉庫で出荷する伝票の受注数 */
              SELECT
                  ol.`商品コード` AS ne_syohin_syohin_code
                , SUM(ol.`受注数`) AS order_num
              FROM tmp_work_delivery_statement_detail_num_order_list ol
              INNER JOIN tmp_work_delivery_statement_detail_num_order_list_code_match m ON ol.伝票番号 = m.伝票番号 AND ol.商品コード = m.商品コード
              GROUP BY ol.`商品コード`
            ) O ON S.ne_syohin_syohin_code = O.ne_syohin_syohin_code

            WHERE S.warehouse_id = :warehouseId
              AND (
                    S.stock
                  - A.assign_total
                  - O.order_num
              ) < 0
        ) T ON pl.ne_syohin_syohin_code = T.ne_syohin_syohin_code
        WHERE (
                    pl.stock
                  - T.assign_total
                  - T.order_num
              ) >= 0
        GROUP BY pl.ne_syohin_syohin_code
      ) AS P ON pl.ne_syohin_syohin_code = P.ne_syohin_syohin_code
            AND pl.position = P.position

EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // （ざっくり実装。うまい方法がすぐに出ないため、もう一つテーブル作成：並び順通りに格納するための一時テーブル）
    $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_delivery_statement_detail_change_location_order_tmp");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_delivery_statement_detail_change_location_order_tmp (
           id INTEGER NOT NULL AUTO_INCREMENT
         , ne_syohin_syohin_code VARCHAR(50) NOT NULL
         , location_id INTEGER NOT NULL
         , PRIMARY KEY (id)
         , UNIQUE INDEX uniq_location (ne_syohin_syohin_code, location_id)
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8
EOD;
    $dbMain->query($sql);

    // position更新対象商品の一覧として、格納一時テーブルへINSERT（対象商品の先頭ロケーションとするロケーション）
    $sql = <<<EOD
      INSERT INTO tmp_work_delivery_statement_detail_change_location_order_tmp (
          ne_syohin_syohin_code
        , location_id
      )
      SELECT
          ne_syohin_syohin_code
        , location_id
      FROM tmp_work_delivery_statement_detail_change_location_order
EOD;
    $dbMain->query($sql);

    // position更新対象商品の残りのロケーションを、その後の並び順として全て格納一時テーブルへINSERT
    $sql = <<<EOD
      INSERT IGNORE INTO tmp_work_delivery_statement_detail_change_location_order_tmp (
          location_id
        , ne_syohin_syohin_code
      )
      SELECT
           pl.location_id
         , pl.ne_syohin_syohin_code
      FROM tb_product_location pl
      INNER JOIN tb_location l ON pl.location_id = l.id
      INNER JOIN tmp_work_delivery_statement_detail_change_location_order t
                           ON pl.ne_syohin_syohin_code = t.ne_syohin_syohin_code
                          AND pl.location_id <> t.location_id
      WHERE l.warehouse_id = :warehouseId
      ORDER BY pl.ne_syohin_syohin_code, pl.position
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // position更新テーブル を空にし、一時テーブルからデータ全コピー
    // ※ INSERT時のクエリで並び順を作成したいが、一時テーブルのCan't reopenが回避できないのでコピーテーブルを利用。
    $dbMain->query("TRUNCATE tmp_work_delivery_statement_detail_change_location_order");
    $sql = <<<EOD
      INSERT INTO tmp_work_delivery_statement_detail_change_location_order (
          id
        , location_id
        , ne_syohin_syohin_code
      )
      SELECT
          id
        , location_id
        , ne_syohin_syohin_code
      FROM tmp_work_delivery_statement_detail_change_location_order_tmp t
      ORDER BY t.id
EOD;
    $dbMain->query($sql);

    // position更新
    $sql = <<<EOD
      UPDATE
      tb_product_location pl
      INNER JOIN (
        SELECT
            o.location_id
          , o.ne_syohin_syohin_code
          , (
             SELECT
                COUNT(*)
             FROM tmp_work_delivery_statement_detail_change_location_order_tmp t
             WHERE o.ne_syohin_syohin_code = t.ne_syohin_syohin_code
               AND o.id > t.id
           ) AS new_position
        FROM tmp_work_delivery_statement_detail_change_location_order o
      ) T ON pl.location_id = T.location_id
         AND pl.ne_syohin_syohin_code = T.ne_syohin_syohin_code

      SET pl.position = T.new_position
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // ロケーション変更履歴 保存
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_LOCATION_SORT_ORDER_BY_DELIVERY_STATEMENT_DETAILS, ($this->account ? $this->account->getUsername() : 'BatchSV02:CRON'), $actionKey);

    $dbMain->commit();
  }



  /**
   * 完了件数取得
   * @param TbWarehouse $warehouse
   * @return int
   */
  private function countResult($warehouse)
  {
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
          SELECT
            COUNT(伝票番号)
          FROM tb_delivery_statement_detail_num_order_list_result
          WHERE warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouse->getId());
    $stmt->execute();
    $done = $stmt->fetchColumn(0);

    return $done;
  }

  /**
   * 納品書印刷待ち集計データにページを設定する
   */
  private function setPageToDetailNumOrderList()
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 設定値取得
    /** @var TbDeliveryStatementDetailNumOrderListInfoRepository $repoSetting */
    $repoSetting = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryStatementDetailNumOrderListInfo');
    $settingInfo = $repoSetting->getSettingInfo();

    // 倉庫ID、会社ID、発送方法の重複を除いた組み合わせを取得
    $sql = <<<EOD
      SELECT STRAIGHT_JOIN distinct
          r.warehouse_id AS warehouseId
        , c.id AS companyId
        , r.発送方法 AS method
      FROM tb_delivery_statement_detail_num_order_list_result r
      INNER JOIN tb_productchoiceitems pci ON
          (pci.ne_syohin_syohin_code = substr(r.対象商品コード, instr(r.対象商品コード,':')+1,char_length(r.対象商品コード))
          OR (instr(r.対象商品コード,':') = 0 AND instr(r.対象商品コード,',') >= 1 AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード,',')-1)))
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_company c ON m.company_code = c.code;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($list as $data) {
      $sql = <<<EOD
        SELECT STRAIGHT_JOIN
          r.*
        FROM tb_delivery_statement_detail_num_order_list_result r
        INNER JOIN tb_productchoiceitems pci ON
          (pci.ne_syohin_syohin_code = substr(r.対象商品コード, instr(r.対象商品コード,':')+1,char_length(r.対象商品コード))
          OR (instr(r.対象商品コード,':') = 0 AND instr(r.対象商品コード,',') >= 1 AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード,',')-1)))
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_company c ON m.company_code = c.code
        WHERE r.発送方法 = :deliveryMethod
          AND r.warehouse_id = :warehouseId
          AND c.id = :companyId
        ORDER BY r.id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryMethod', $data['method']);
      $stmt->bindValue(':warehouseId', $data['warehouseId'], \PDO::PARAM_INT);
      $stmt->bindValue(':companyId', $data['companyId'], \PDO::PARAM_INT);
      $stmt->execute();

      $detailNum = 0; // ページ明細件数
      $page = 1;
      $pageIds = [];
      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        // ページ切り替え
        if (! isset($pageIds[$page])) {
          $pageIds[$page] = [];
        }
        $limitNum = $data['method'] == 'SHOPLIST' ? '200' : $settingInfo['page_item_num'];
        $detailNum += $row['明細数'];
        $pageIds[$page][] = $row['id'];

        if ($detailNum >= $limitNum) {
          $page++;
          $detailNum = 0;
        }
      }

      foreach ($pageIds as $page => $ids) {
        $idsStr = implode(', ', $ids);
        $sql = <<<EOD
          UPDATE tb_delivery_statement_detail_num_order_list_result
            SET page = :page
            WHERE id IN ({$idsStr});
EOD;
        $statement = $dbMain->prepare($sql);
        $statement->bindValue(':page', $page);
        $statement->execute();
      }
    }
  }

  private function deleteCaluculatedShipmentAmount(){
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
    DELETE FROM tb_calculated_shipment_amount
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  private function insertCaluculatedShipmentAmount(){
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 出荷対象倉庫のレコードを追加
    $sql = <<<EOD
    INSERT 
      INTO tb_calculated_shipment_amount(warehouse_id) 
      SELECT
        id 
      FROM
        tb_warehouse 
      WHERE
        shipment_enabled <> 0 
      ORDER BY
        shipment_priority DESC
        , display_order ASC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $commonUtil = $this->getDbCommonUtil();
    // RSL、佐川、shoplistの係数を取得
    $coefficientShoplist = $commonUtil->getSettingValue(TbSetting::KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_SHOPLIST);
    $coefficientRslSagawaYamato = $commonUtil->getSettingValue(TbSetting::KEY_WAREHOUSE_RESULT_HISTORY_COEFFICIENT_RSL_SAGAWA_YAMATO);

    // 出荷不能以外の納品書印刷待ちの計算値を格納
    $sql = <<<EOD
    UPDATE tb_calculated_shipment_amount t
    , ( 
      SELECT
        warehouse_id
        , sum(amount) AS amount 
      FROM
        ( 
          SELECT
            warehouse_id
            , `発送方法`
            , CASE `発送方法` 
              WHEN :deliveryMethodSagawa THEN count(*) * :coefficientRslSagawaYamato 
              WHEN :deliveryMethodRsl THEN count(*) * :coefficientRslSagawaYamato
              WHEN :deliveryMethodYamato THEN count(*) * :coefficientRslSagawaYamato 
              WHEN :deliveryMethodShoplist THEN count(*) * :coefficientShoplist 
              ELSE count(*) 
              END AS amount 
          FROM
            tb_delivery_statement_detail_num_order_list_result 
          GROUP BY
            warehouse_id
            , `発送方法`
        ) w 
      GROUP BY
        warehouse_id
    ) w_sum 
    SET
      t.order_amount = w_sum.amount 
    WHERE
      t.warehouse_id = w_sum.warehouse_id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryMethodSagawa', DbCommonUtil::DELIVERY_METHOD_TAKUHAI);
    $stmt->bindValue(':deliveryMethodRsl', DbCommonUtil::DELIVERY_METHOD_YUU_PACK_RSL);
    $stmt->bindValue(':deliveryMethodYamato', DbCommonUtil::DELIVERY_METHOD_YAMATO_HATSUBARAI);
    $stmt->bindValue(':deliveryMethodShoplist', DbCommonUtil::DELIVERY_METHOD_SHOPLIST);
    $stmt->bindValue(':coefficientRslSagawaYamato', $coefficientRslSagawaYamato, \PDO::PARAM_STR);
    $stmt->bindValue(':coefficientShoplist', $coefficientShoplist, \PDO::PARAM_STR);
    $stmt->execute();

    // 出荷不能の納品書印刷待ちの計算値を格納
    // refreshDetailNumOrderList()の最後で、tmp_work_delivery_statement_detail_num_order_listに出荷不能の伝票が残っているのでこれを利用する
    // これは本処理がrefreshDetailNumOrderList()の直後で、tmp_work_delivery_statement_detail_num_order_listに操作がされていないことが前提の処理となる
    $sql = <<<EOD
    UPDATE tb_calculated_shipment_amount t
    , ( 
      SELECT
        sum(amount) AS amount 
      FROM
        ( 
          SELECT
            `発送方法`
            , CASE `発送方法` 
              WHEN :deliveryMethodSagawa THEN count(distinct `伝票番号`) * :coefficientRslSagawaYamato 
              WHEN :deliveryMethodRsl THEN count(distinct `伝票番号`) * :coefficientRslSagawaYamato
              WHEN :deliveryMethodYamato THEN count(distinct `伝票番号`) * :coefficientRslSagawaYamato
              WHEN :deliveryMethodShoplist THEN count(distinct `伝票番号`) * :coefficientShoplist 
              ELSE count(distinct `伝票番号`) 
              END AS amount 
          FROM
            tmp_work_delivery_statement_detail_num_order_list 
          WHERE
            fetched = 0 /* 未取得の伝票から */
          GROUP BY
            `発送方法`
        ) w 
    ) w_sum 
    SET
      t.order_amount = t.order_amount + w_sum.amount 
    WHERE
      t.warehouse_id = 12  -- 古市倉庫に加算
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryMethodSagawa', DbCommonUtil::DELIVERY_METHOD_TAKUHAI);
    $stmt->bindValue(':deliveryMethodRsl', DbCommonUtil::DELIVERY_METHOD_YUU_PACK_RSL);
    $stmt->bindValue(':deliveryMethodYamato', DbCommonUtil::DELIVERY_METHOD_YAMATO_HATSUBARAI);
    $stmt->bindValue(':deliveryMethodShoplist', DbCommonUtil::DELIVERY_METHOD_SHOPLIST);
    $stmt->bindValue(':coefficientRslSagawaYamato', $coefficientRslSagawaYamato, \PDO::PARAM_STR);
    $stmt->bindValue(':coefficientShoplist', $coefficientShoplist, \PDO::PARAM_STR);
    $stmt->execute();
  }

}


