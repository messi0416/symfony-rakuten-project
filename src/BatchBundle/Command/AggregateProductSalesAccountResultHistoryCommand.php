<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcess;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountAggregateReservationRepository;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 商品売上実績集計処理。
 * 
 * 指定された条件に基づき、商品の売上実績を集計する。また、画面で担当者設定を更新されたデータも、このバッチで実際の集計結果更新を行う（--is_reservation=1）。
 * 
 * 現在のバージョンは、集計サーバで起動し、分析DBで元データの取得や一次データの保存を行う。集計が終わった後の確定データはmainDBに書き込む。
 * mainDBと分析DBはレプリケーション関係なので、分析DB上で、レプリケーションしているテーブルに更新クエリを投げないよう実装時は注意すること。
 * （テンポラリテーブルや、レプリケーション対象外の plusnao_tmp_db は問題ない）
 * 開発・検証で起動する場合は、/ 直下に /this_is_agn_server というファイルを置くこと。
 */
class AggregateProductSalesAccountResultHistoryCommand extends PlusnaoBaseCommand
{

  protected function configure()
  {
    $this
    ->setName('batch:aggregate-product-sales-account-result-history')
    ->setDescription('商品売上実績集計')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('order_date_from', null, InputOption::VALUE_OPTIONAL, '受注日from yyyy-mm-dd。この日を含む、この日より受注日が後のものを集計。')
    ->addOption('order_date_to', null, InputOption::VALUE_OPTIONAL, '受注日to yyyy-mm-dd。この日を含む、この日より受注日が前のものを集計。')
    ->addOption('daihyo_syohin_code', null, InputOption::VALUE_OPTIONAL, '代表商品コード。指定する場合、受注日from及び受注日toの指定必須。')
    ->addOption('ne_updated_from', null, InputOption::VALUE_OPTIONAL, 'NE側更新日時from。この日を含む、この日よりNE更新日時が後のものを集計。')
    ->addOption('is_reservation', null, InputOption::VALUE_OPTIONAL, '担当者更新分の実行か。', 0)
    ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  /**
   * 履歴登録用のプロセスIDを取得する。
   * デフォルトではクラス名から取得する。
   * 同じクラスでも、大きく異なる複数の処理を行う機能では、オーバーライドして処理種別ごとの値を取得させる。
   * （定数定義は TbProcessクラスで行う）
   */
  protected function getProcessId(InputInterface $input) {
    if ($input->getOption('is_reservation')) {
      return TbProcess::PROCESS_ID_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY_RESERVED;
    } else {
      return TbProcess::PROCESS_ID_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY_NORMAL;
    }
  }

  /** 商品売上担当者集計予約テーブル、最大更新対象代表商品コード数 */
  const MAX_UPDATE_TARGET_CODE_COUNT_IN_RESERVATION_TABLE = 300;

  /** @var bool 速度測定ログを出すか。時間計測ログを出す場合 true。画面からの実行ではOFF、日次の集計処理ではON */
  private $isTimeDebug = true;

  /** @var bool 担当者更新分の実行か */
  private $isReservation = false;

  /** @var int 予約分のみ集計する際の、集計開始直前の予約テーブル最大ID */
  private $maxIdBeforeUpdated;

  /** @var string 集計結果保存CSV */
  private $aggregateResultCsv = 'product_sales_account_aggregate_result.csv';

  /** @var array 集計結果保存CSVヘッダー */
  private $aggregateResultCsvHeader = [
    'product_sales_account_id',
    'target_date',
    'sales_amount',
    'profit_amount',
    'shoplist_sales_amount',
    'shoplist_profit_amount',
    'stock_quantity',
    'stock_amount',
    'remain_quantity',
    'remain_amount',
  ];

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '商品売上実績集計';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    // #210668_2 BatchSV04で確認したところ128Mになっていたので、BatchSV02に合わせて無制限に。
    ini_set('memory_limit', '-1');

    if (!file_exists('/this_is_agn_server')) {
      throw new \RuntimeException('集計サーバー以外で起動することはできません');
    }

    $this->getStopwatch();
    $this->stopwatch->start('productSales');
    $logger = $this->getLogger();
    /** @var TbProductSalesAccountRepository $accountRepo */
    $accountRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
    /** @var TbProductSalesAccountAggregateReservationRepository $reserveRepo */
    $reserveRepo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductSalesAccountAggregateReservation');

    $this->validate($input);

    $this->isReservation = boolval($input->getOption('is_reservation'));

    $orderDateFrom = null;
    $orderDateTo = null;
    $daihyoSyohinCode = null;
    $neUpdatedFrom = null;
    if (! $this->isReservation) {
      if (! empty($input->getOption('order_date_from'))) {
        $orderDateFrom = new \DateTime($input->getOption('order_date_from'));
      }
      if (! empty($input->getOption('order_date_to'))) {
        $orderDateTo = new \DateTime($input->getOption('order_date_to'));
        $orderDateTo->setTime(23, 59, 59);
      }
      if (! empty($input->getOption('daihyo_syohin_code'))) {
        $daihyoSyohinCode = $input->getOption('daihyo_syohin_code');
        $this->isTimeDebug = false;
      }
      if (! empty($input->getOption('ne_updated_from'))) {
        $neUpdatedFrom = new \DateTime($input->getOption('ne_updated_from'));
      }
      // 全ての引数指定がない場合、3ヶ月前の1日から本日までを対象とする。
      if (! $orderDateFrom && ! $neUpdatedFrom) {
        $orderDateFromStr = (new \DateTime())->modify('-3 month')->format('Y-m-01');
        $orderDateFrom = new \DateTime($orderDateFromStr);
        $orderDateTo = new \DateTime();
      }
    }

    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 処理日付・商品取得', 'productSales'));

    // 次の形式に条件を整理。 $conditions = [[対象日(開始), 対象日(終了), 代表商品コード], ...]
    // NE側更新日が指定された場合でも、日付+代表商品コードの形式に変換する。
    /** @var \Doctrine\DBAL\Connection $agnMain */
    $agnMain = $this->getDoctrine()->getConnection('agnDBmain');
    $conditions = null;
    if ($this->isReservation) {
      $this->maxIdBeforeUpdated = $reserveRepo->findMaxId($agnMain);
      $reservations = $reserveRepo->findUnaggregatedData($this->maxIdBeforeUpdated, $agnMain);
      if (empty($reservations)) {
        $logger->info("{$this->commandName}（担当者更新分）: 対象無しのため終了");
        return 0;
      }
      $conditions = [];
      foreach ($reservations as $data) {
        $conditions[] = [
          'startDate' => $data['orderDateFrom'],
          'endDate' => $data['orderDateTo'],
          'daihyoSyohinCode' => $data['daihyoSyohinCode'],
        ];
      }
    } elseif ($neUpdatedFrom) {
      $sql = <<<EOD
        SELECT
          DATE_FORMAT(d.`受注日`, '%Y-%m-%d') AS startDate,
          DATE_FORMAT(d.`受注日`, '%Y-%m-%d') AS endDate,
          p.`daihyo_syohin_code` AS daihyoSyohinCode
        FROM
          tb_sales_detail d
          INNER JOIN tb_productchoiceitems p
            ON d.`商品コード（伝票）` = p.`ne_syohin_syohin_code`
          INNER JOIN (
            SELECT
              DISTINCT `daihyo_syohin_code`
            FROM
              tb_product_sales_account
            ) A
            ON p.`daihyo_syohin_code` = A.`daihyo_syohin_code`
        WHERE
          d.`NE側更新日時` >= :neUpdatedFrom
        GROUP BY
          DATE_FORMAT(d.`受注日`, '%Y-%m-%d'), p.`daihyo_syohin_code`
EOD;
      $stmt = $agnMain->prepare($sql);
      $stmt->bindValue(':neUpdatedFrom', $neUpdatedFrom->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->execute();
      $conditions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } else {
      $conditions = [[
        'startDate' => $orderDateFrom->format('Y-m-d'),
        'endDate' => $orderDateTo->format('Y-m-d'),
        'daihyoSyohinCode' => $daihyoSyohinCode
      ]];
    }
    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 処理対象日付・商品件数' . count($conditions), 'productSales'));

    // 代表商品コードをkeyにし、受注日毎の売上計・利益計、及び昨日の在庫計・注残計、を格納した連想配列を取得
    $salesDetailListGroup = $this->findDailySalesAndProfitAndStockByCodeInSalesAccount($conditions);

    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 担当者ごとに案分開始: 代表商品数' . count($salesDetailListGroup), 'productSales'));

    // 日付と代表商品コードの組み合わせ数を格納（初期化）
    $totalNumberDailyCode = 0;

    $this->createAggregateResultCsvAndAddHeader();

    foreach ($salesDetailListGroup as $daihyoSyohinCode => $salesDetailList)
    {
      $accountList = $accountRepo->findApplicableAccountListByCode($daihyoSyohinCode, $agnMain);

      if (empty($accountList)) {
        continue;
      }

      foreach ($salesDetailList as $salesDetail) {
        $targetDate = $salesDetail['対象日'];
        $totalSalesAmount = (int)$salesDetail['売上額合計'];
        $totalProfitAmount = (int)$salesDetail['利益額合計'];
        $totalShoplistSalesAmount = (int)$salesDetail['SHOPLIST売上額合計'];
        $totalShoplistProfitAmount = (int)$salesDetail['SHOPLIST利益額合計'];
        $totalStockQuantity = (int)$salesDetail['在庫数量'];
        $totalStockAmount = (int)$salesDetail['在庫金額'];
        $totalRemainQuantity = (int)$salesDetail['注残数量'];
        $totalRemainAmount = (int)$salesDetail['注残金額'];

        $applyAccountList = $this->extractTargetAccountList($accountList, $targetDate);

        if (empty($applyAccountList)) {
          continue;
        }

        $totalWorkAmount = array_reduce($applyAccountList, function($result, $item) {
          return $result + floatval($item['work_amount']);
        }, 0);

        // 端数調整前の合計を算出の為、初期化
        $beforeTotalSalesAmount = 0;
        $beforeTotalProfitAmount = 0;
        $beforeTotalShoplistSalesAmount = 0;
        $beforeTotalShoplistProfitAmount = 0;
        $beforeTotalStockQuantity = 0;
        $beforeTotalStockAmount = 0;
        $beforeTotalRemainQuantity = 0;
        $beforeTotalRemainAmount = 0;

        // 後の端数調整で使用する為、実績登録した担当者情報を格納していく配列を定義
        $resultHistoryList = [];

        $resultHistory = [
          'sales_amount' => 0,
          'profit_amount' => 0,
          'shoplist_sales_amount' => 0,
          'shoplist_profit_amount' => 0,
          'stock_quantity' => 0,
          'stock_amount' => 0,
          'remain_quantity' => 0,
          'remain_amount' => 0
        ];
        $resultHistory['target_date'] = $targetDate;

        // 受注日に適用されている担当者の実績を計算。(在庫系情報は、昨日以外は0なので計算しない。)
        foreach ($applyAccountList as $applyAccount) {
          $workAmount = $applyAccount['work_amount'];
          $resultHistory['product_sales_account_id'] = $applyAccount['id'];

          if ($totalSalesAmount !== 0) {
            $resultHistory['sales_amount'] = floor($workAmount / $totalWorkAmount * $totalSalesAmount);
            $beforeTotalSalesAmount += $resultHistory['sales_amount'];
          }
          if ($totalProfitAmount !== 0) {
            $resultHistory['profit_amount'] = floor($workAmount / $totalWorkAmount * $totalProfitAmount);
            $beforeTotalProfitAmount += $resultHistory['profit_amount'];
          }
          if ($totalShoplistSalesAmount !== 0) {
            $resultHistory['shoplist_sales_amount'] = floor($workAmount / $totalWorkAmount * $totalShoplistSalesAmount);
            $beforeTotalShoplistSalesAmount += $resultHistory['shoplist_sales_amount'];
          }
          if ($totalShoplistProfitAmount !== 0) {
            $resultHistory['shoplist_profit_amount'] = floor($workAmount / $totalWorkAmount * $totalShoplistProfitAmount);
            $beforeTotalShoplistProfitAmount += $resultHistory['shoplist_profit_amount'];
          }
          if ($totalStockQuantity !== 0) {
            $resultHistory['stock_quantity'] = floor($workAmount / $totalWorkAmount * $totalStockQuantity);
            $beforeTotalStockQuantity += $resultHistory['stock_quantity'];
          }
          if ($totalStockAmount !== 0) {
            $resultHistory['stock_amount'] = floor($workAmount / $totalWorkAmount * $totalStockAmount);
            $beforeTotalStockAmount += $resultHistory['stock_amount'];
          }
          if ($totalRemainQuantity !== 0) {
            $resultHistory['remain_quantity'] = floor($workAmount / $totalWorkAmount * $totalRemainQuantity);
            $beforeTotalRemainQuantity += $resultHistory['remain_quantity'];
          }
          if ($totalRemainAmount !== 0) {
            $resultHistory['remain_amount'] = floor($workAmount / $totalWorkAmount * $totalRemainAmount);
            $beforeTotalRemainAmount += $resultHistory['remain_amount'];
          }

          $resultHistoryList[] = $resultHistory;
        }

        // 端数取得
        $salesFraction = $totalSalesAmount - $beforeTotalSalesAmount;
        $profitFraction = $totalProfitAmount - $beforeTotalProfitAmount;
        $shoplistSalesFraction = $totalShoplistSalesAmount - $beforeTotalShoplistSalesAmount;
        $shoplistProfitFraction = $totalShoplistProfitAmount - $beforeTotalShoplistProfitAmount;
        $stockQuantityFraction = $totalStockQuantity - $beforeTotalStockQuantity;
        $stockAmountFraction = $totalStockAmount - $beforeTotalStockAmount;
        $remainQuantityFraction = $totalRemainQuantity - $beforeTotalRemainQuantity;
        $remainAmountFraction = $totalRemainAmount - $beforeTotalRemainAmount;

        // 端数追加対象取得（対象の担当者のうち、仕事量最大で登録日が最も早い）
        if ($salesFraction) {
          $resultHistoryList[0]['sales_amount'] += $salesFraction;
        }
        if ($profitFraction) {
          $resultHistoryList[0]['profit_amount'] += $profitFraction;
        }
        if ($shoplistSalesFraction) {
          $resultHistoryList[0]['shoplist_sales_amount'] += $shoplistSalesFraction;
        }
        if ($shoplistProfitFraction) {
          $resultHistoryList[0]['shoplist_profit_amount'] += $shoplistProfitFraction;
        }
        if ($stockQuantityFraction) {
          $resultHistoryList[0]['stock_quantity'] += $stockQuantityFraction;
        }
        if ($stockAmountFraction) {
          $resultHistoryList[0]['stock_amount'] += $stockAmountFraction;
        }
        if ($remainQuantityFraction) {
          $resultHistoryList[0]['remain_quantity'] += $remainQuantityFraction;
        }
        if ($remainAmountFraction) {
          $resultHistoryList[0]['remain_amount'] += $remainAmountFraction;
        }

        $this->addAggregateResultToCSV($resultHistoryList);
        $totalNumberDailyCode ++;
      }
    }
    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 担当者ごとに案分終了', 'productSales'));

    // 処理件数1 処理した「日付・代表商品」の組みあわせ数
    $this->processExecuteLog->setProcessNumber1($totalNumberDailyCode);
    $this->processExecuteLog->setVersion(3.0);
    
    // 再集計対象分のレコードを削除してから、集計結果のCSVデータを挿入する。
    $this->deleteRecordsForReaggregation($conditions);
    $this->importCsvToMainTable();

    if ($this->isReservation) {
      $reserveRepo->updateAggregatedFlagsBelowId($this->maxIdBeforeUpdated);
    }
  }

  /**
   * 集計結果を格納するCSVファイルを作成し、ヘッダーを追記する
   */
  private function createAggregateResultCsvAndAddHeader()
  {
    $filePath = $this->getFileUtil()->getWebCsvDir() . '/' . $this->aggregateResultCsv;
    $fp = fopen($filePath, 'wb');
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $headerLine = $stringUtil->convertArrayToCsvLine($this->aggregateResultCsvHeader);
    $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";
    fputs($fp, $headerLine);
    fclose($fp);
  }

  /**
   * 「日付・代表商品」の組みあわせ単位の集計結果を、CSVに追記する
   */
  private function addAggregateResultToCSV($resultList)
  {
    $filePath = $this->getFileUtil()->getWebCsvDir() . '/' . $this->aggregateResultCsv;
    $fp = fopen($filePath, 'a');
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    foreach ($resultList as $result) {
      $line = $stringUtil->convertArrayToCsvLine($result, $this->aggregateResultCsvHeader);
      $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
      fputs($fp, $line);
    }
    fclose($fp);
  }

  /**
   * 集計結果CSVを、メインテーブルに取り込む
   */
  private function importCsvToMainTable()
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $aggregateResultCsvHeaderStr = implode(', ', $this->aggregateResultCsvHeader);
    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :filePath
      INTO TABLE tb_product_sales_account_result_history
      FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
      LINES TERMINATED BY '\r\n'
      IGNORE 1 LINES
      ({$aggregateResultCsvHeaderStr})
EOD;
    $stmt = $dbMain->prepare($sql);
    $filePath = $this->getFileUtil()->getWebCsvDir() . '/' . $this->aggregateResultCsv;
    $stmt->bindValue(':filePath', $filePath, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 代表商品コード毎に、日付毎の売上・利益・在庫・注残の合計を取得（商品売上担当者に登録のある商品に限る）。
   * 
   * 売上・利益は、通常とSHOPLISTで別々に集計する。
   * セット商品として売れたものは、売上は100%セット商品の担当者につける。単品担当者がいて、セット商品担当者がいない場合、売上は誰にもつかない。
   * 一方、在庫の時点では全て単品商品のため、在庫・注残は100%単品担当者につける。このため、セット商品担当者は在庫リスクを抱えない（#161511時点。後日見直しの可能性）
   * 
   * @param array $conditions 集計対象の検索条件。3カラムを1セットにした連想配列の配列。[[対象日(開始), 対象日(終了), 代表商品コード], ...]。
   *                          NE更新日時を利用した集計の場合、集計対象の受注日がとびとびとなる（年単位の古い受注が混じる場合もある）。
   *                          このため、単なるFROM-TOではなく、1日単位で集計日を指定する。
   * @return array
   */
  private function findDailySalesAndProfitAndStockByCodeInSalesAccount($conditions)
  {
    $logger = $this->getLogger();
    /** @var \Doctrine\DBAL\Connection $agnMain */
    $agnMain = $this->getDoctrine()->getConnection('agnDBmain');
    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 通常売上取得', 'productSales'));

    $dbTmpName = $this->getDb('agnDBtmp')->getDatabase();

    $sql = <<<EOD
      CREATE TEMPORARY TABLE {$dbTmpName}.tmp_sales_detail_list_group (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `対象日` DATE NOT NULL,
        `代表商品コード` VARCHAR(30),
        `売上額合計` INT(11) DEFAULT 0,
        `利益額合計` INT(11) DEFAULT 0,
        `SHOPLIST売上額合計` INT(11) DEFAULT 0,
        `SHOPLIST利益額合計` INT(11) DEFAULT 0,
        `在庫数量` INT(11) DEFAULT 0,
        `在庫金額` INT(11) DEFAULT 0,
        `注残数量` INT(11) DEFAULT 0,
        `注残金額` INT(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        INDEX `index_対象日_代表商品コード` (`対象日`, `代表商品コード`)
      );
EOD;
    $stmt = $agnMain->prepare($sql);
    $stmt->execute();

    // 条件から、売上取得クエリ用と、在庫取得クエリ用のWHERE文をそれぞれ作成
    $addSalesWheres = []; // 通常・売上
    $addSetSalesWheres = []; // セット・売上
    $addShoplistSalesWheres = []; // SHOPLIST売上（通常・セットの区別なし）
    $addStocksWheres = []; // 在庫。在庫は通常のみ
    $addParams = [];
    $addSalesWhereSql = "";
    $addSetSalesWhereSql = "";
    $addShoplistSalesWhereSql = "";
    $addStocksWhereSql = "";
    $i = 1;
    foreach ($conditions as $condition) {
      $salesWhere = "(p.`受注年月日` BETWEEN :startDate{$i} AND :endDate{$i}";
      $setSalesWhere = "(p.`受注年月日` BETWEEN :startDate{$i} AND :endDate{$i}";
      $shoplistSalesWhere = "(s.`order_date` BETWEEN :startDate{$i} AND :endDate{$i}";
      $stocksWhere = "(pl.`log_date` BETWEEN :startDate{$i} AND :endDate{$i}";
      $addParams[":startDate{$i}"] = $condition['startDate'];
      $addParams[":endDate{$i}"] = $condition['endDate'];
      if ($condition['daihyoSyohinCode']) {
        $salesWhere .= " AND p.`代表商品コード` = :daihyoSyohinCode{$i})";
        $setSalesWhere .= " AND setpci.daihyo_syohin_code = :daihyoSyohinCode{$i})";
        $shoplistSalesWhere .= " AND s.`daihyo_syohin_code` = :daihyoSyohinCode{$i})";
        $stocksWhere .= " AND pl.`daihyo_syohin_code` = :daihyoSyohinCode{$i})";
        $addParams[":daihyoSyohinCode{$i}"] = $condition['daihyoSyohinCode'];
      } else {
        $salesWhere .= ')';
        $setSalesWhere .= ')';
        $shoplistSalesWhere .= ')';
        $stocksWhere .= ')';
      }
      $addSalesWheres[] = $salesWhere;
      $addSetSalesWheres[] = $setSalesWhere;
      $addShoplistSalesWheres[] = $shoplistSalesWhere;
      $addStocksWheres[] = $stocksWhere;
      $i++;
    }
    if ($addSalesWheres) {
      $addSalesWhereSql = sprintf(" AND ( %s ) ", implode(" OR ", $addSalesWheres));
      $addSetSalesWhereSql = sprintf(" AND ( %s ) ", implode(" OR ", $addSetSalesWheres));
      $addShoplistSalesWhereSql = sprintf(" AND ( %s ) ", implode(" OR ", $addShoplistSalesWheres));
      $addStocksWhereSql = sprintf(" AND ( %s ) ", implode(" OR ", $addStocksWheres));
    }

    // 1. 初めに、売上情報を取得・追加する。
    $sql = <<<EOD
      INSERT INTO {$dbTmpName}.tmp_sales_detail_list_group (
        `対象日`,
        `代表商品コード`,
        `売上額合計`,
        `利益額合計`
      )
      /* 通常商品 */
      SELECT
        p.`受注年月日` as 受注年月日, /* UNION ORDER BY用に別名 */
        p.`代表商品コード` as daihyo_syohin_code,
        SUM(p.`小計_伝票料金加算`),
        SUM(p.`明細粗利額_伝票費用除外`)
      FROM
        tb_sales_detail_profit p
        LEFT JOIN tb_sales_detail_set_distribute_info seti ON p.伝票番号 = seti.voucher_number AND p.明細行 = seti.line_number
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM
            tb_product_sales_account
        ) A
          ON p.`代表商品コード` = A.daihyo_syohin_code
      WHERE
        seti.voucher_number IS NULL
        {$addSalesWhereSql}
      GROUP BY
        p.`受注年月日`, p.`代表商品コード`
      UNION ALL
      /* セット商品 */
      SELECT
        p.`受注年月日`,
        setpci.daihyo_syohin_code,
        SUM(p.`小計_伝票料金加算`),
        SUM(p.`明細粗利額_伝票費用除外`)
      FROM
        tb_sales_detail_profit p
        JOIN tb_sales_detail_set_distribute_info seti ON p.伝票番号 = seti.voucher_number AND p.明細行 = seti.line_number
        JOIN tb_productchoiceitems setpci ON seti.original_ne_syohin_syohin_code = setpci.ne_syohin_syohin_code
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM
            tb_product_sales_account
        ) A
          ON p.`代表商品コード` = A.daihyo_syohin_code
      WHERE
        1
        {$addSetSalesWhereSql}
      GROUP BY
        p.`受注年月日`, setpci.daihyo_syohin_code 

      ORDER BY
        受注年月日, daihyo_syohin_code;
EOD;
    $stmt = $agnMain->prepare($sql);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] SHOPLIST売上取得', 'productSales'));
    
    // 2. 次に、SHOPLISTの売上情報を取得・追加する。SHOPLISTからの情報は、セット商品の明細分割がないはずなので、そのまま計算
    $sql = <<<EOD
      INSERT INTO {$dbTmpName}.tmp_sales_detail_list_group (
        `対象日`,
        `代表商品コード`,
        `SHOPLIST売上額合計`,
        `SHOPLIST利益額合計`
      )
      SELECT
        s.`order_date`,
        s.`daihyo_syohin_code`,
        SUM(s.`sales_amount`),
        SUM(
          s.`sales_amount` - (s.`cost_tanka` * s.`num_total`) - ROUND(
            s.`sales_amount` * (s.`system_usage_cost_ratio` / 100)
          )
        )
      FROM
        tb_shoplist_daily_sales s
        INNER JOIN (
          SELECT
            DISTINCT daihyo_syohin_code
          FROM
            tb_product_sales_account
        ) A
          ON s.`daihyo_syohin_code` = A.daihyo_syohin_code
      WHERE
        1
        {$addShoplistSalesWhereSql}
      GROUP BY
        s.`order_date`, s.`daihyo_syohin_code`
      ORDER BY
        s.`order_date`, s.`daihyo_syohin_code`;
EOD;
    $stmt = $agnMain->prepare($sql);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    
    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 在庫情報取得', 'productSales'));

    // 3. 最後に、在庫情報を取得・追加する。
    $dbLogName = $this->getDb('agnDBlog')->getDatabase();
    $sql = <<<EOD
      INSERT INTO {$dbTmpName}.tmp_sales_detail_list_group (
        `対象日`,
        `代表商品コード`,
        `在庫数量`,
        `在庫金額`,
        `注残数量`,
        `注残金額`
      )
      SELECT
        pl.`log_date`,
        pl.`daihyo_syohin_code`,
        pl.`総在庫数` - pl.not_asset_stock,
        (pl.`総在庫数` - pl.not_asset_stock) * pl.cost_tanka,
        pl.`総注残数`,
        pl.`総注残数` * pl.genka_tnk
      FROM
        {$dbLogName}.`tb_product_price_log` pl
        INNER JOIN (
          SELECT
            DISTINCT a.`daihyo_syohin_code`
          FROM
            tb_product_sales_account a
        ) A
          ON pl.`daihyo_syohin_code` = A.`daihyo_syohin_code`
      WHERE
        (pl.`総在庫数` <> 0 OR pl.`総注残数` <> 0)
        {$addStocksWhereSql}
      GROUP BY
        pl.`log_date`, pl.`daihyo_syohin_code`
      ORDER BY
        pl.`log_date`, pl.`daihyo_syohin_code`;
EOD;
    $stmt = $agnMain->prepare($sql);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    
    /* ------------ DEBUG LOG ------------ */ if ($this->isTimeDebug) $logger->debug($this->getLapTimeAndMemory('[商品売上実績] 売上・在庫情報統合', 'productSales'));

    // 3. 最後に、1の売上情報と2の在庫情報を統合する。
    $sql = <<<EOD
      SELECT
        `対象日`,
        `代表商品コード`,
        SUM(`売上額合計`) AS `売上額合計`,
        SUM(`利益額合計`) AS `利益額合計`,
        SUM(`SHOPLIST売上額合計`) AS `SHOPLIST売上額合計`,
        SUM(`SHOPLIST利益額合計`) AS `SHOPLIST利益額合計`,
        SUM(`在庫数量`) AS `在庫数量`,
        SUM(`在庫金額`) AS `在庫金額`,
        SUM(`注残数量`) AS `注残数量`,
        SUM(`注残金額`) AS `注残金額`
      FROM
        {$dbTmpName}.tmp_sales_detail_list_group
      GROUP BY
        `対象日`, `代表商品コード`
EOD;
    $stmt = $agnMain->prepare($sql);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $result = [];
    foreach ($list as $value) {
      $result[$value['代表商品コード']][] = $value;
    }
    return $result;
  }

  /**
   * 商品売上担当者実績テーブルから、再集計対象のレコード削除する
   * @param array $conditions
   */
  private function deleteRecordsForReaggregation($conditions)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 条件から、売上取得クエリ用と、在庫取得クエリ用のWHERE文をそれぞれ作成
    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    $i = 1;
    foreach ($conditions as $condition) {
      $where = "(r.`target_date` BETWEEN :startDate{$i} AND :endDate{$i}";
      $addParams[":startDate{$i}"] = $condition['startDate'];
      $addParams[":endDate{$i}"] = $condition['endDate'];
      if ($condition['daihyoSyohinCode']) {
        $where .= " AND a.`daihyo_syohin_code` = :daihyoSyohinCode{$i})";
        $addParams[":daihyoSyohinCode{$i}"] = $condition['daihyoSyohinCode'];
      } else {
        $where .= ')';
      }
      $addWheres[] = $where;
      $i++;
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" OR ", $addWheres));
    }

    // バッチ実行条件に該当する、商品売上実績テーブルのレコードidを取得
    $sql = <<<EOD
      DELETE r FROM
        tb_product_sales_account_result_history r
        INNER JOIN tb_product_sales_account a
          ON r.`product_sales_account_id` = a.`id`
      WHERE
        1 {$addWhereSql};
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
  }

  /**
   * 受注日に適用されている商品売上担当者リスト作成
   * @param array $accountList
   * @param string $targetDate
   * @return array
   */
  private function extractTargetAccountList($accountList, $targetDate) {
    $result = [];
    foreach ($accountList as $account) {
      if (($account['apply_start_date'] <= $targetDate)
          && (! $account['apply_end_date'] || ($account['apply_end_date'] >= $targetDate))
          && $account['work_amount'] > 0) {
        $result[] = $account;
      }
    }
    return $result;
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  function validate(InputInterface $input)
  {
    if (! empty($input->getOption('order_date_from')) && empty($input->getOption('order_date_to'))) {
      throw new \RuntimeException('受注日fromを指定する場合は、受注日toも指定が必要です');
    }
    if (! empty($input->getOption('order_date_to')) && empty($input->getOption('order_date_from'))) {
      throw new \RuntimeException('受注日toを指定する場合は、受注日fromも指定が必要です');
    }
    if (! empty($input->getOption('daihyo_syohin_code')) && empty($input->getOption('order_date_from'))) {
      throw new \RuntimeException('代表商品コードを指定する場合は、受注日の期間(受注日from + 受注日to)の指定が必要です');
    }
    if (! empty($input->getOption('ne_updated_from')) && ! empty($input->getOption('order_date_from'))) {
      throw new \RuntimeException('受注日の期間(受注日from + 受注日to)、と、NE側更新日時from、は同時に指定できません。');
    }
    if (! empty($input->getOption('ne_updated_from')) && $input->getOption('is_reservation')) {
      throw new \RuntimeException('受注日の期間(受注日from + 受注日to)、と、予約分実行ON、は同時に指定できません。');
    }
    if (! empty($input->getOption('order_date_from')) && $input->getOption('is_reservation')) {
      throw new \RuntimeException('NE側更新日時from、と、予約分実行ON、は同時に指定できません。');
    }
    if (! empty($input->getOption('order_date_from')) && ! strptime($input->getOption('order_date_from'), '%Y-%m-%d')) {
      throw new \RuntimeException('受注日fromの形式がyyyy-mm-ddではありません[' . $input->getOption('order_date_from') . ']');
    }
    if (! empty($input->getOption('order_date_to')) && ! strptime($input->getOption('order_date_to'), '%Y-%m-%d')) {
      throw new \RuntimeException('受注日toの形式がyyyy-mm-ddではありません[' . $input->getOption('order_date_to') . ']');
    }
    if (! empty($input->getOption('ne_updated_from')) && ! strptime($input->getOption('ne_updated_from'), '%Y-%m-%d')) {
      throw new \RuntimeException('NE側更新日時fromの形式がyyyy-mm-ddではありません[' . $input->getOption('ne_updated_from') . ']');
    }
  }
}
