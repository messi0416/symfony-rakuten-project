<?php

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\Command\ExportCsvNextEngineMallProductCommand;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 決算日在庫集計バッチ。
 * 【#189708 過去の決算期の在庫状況・滞留状況を確認する資料作成（政策金融公庫向け）】の仕様に従い、
 * 指定された日付の在庫集計を実施する。
 * 
 * 1度の実行で集計できるのは1年分。
 * オプションで、
 *   --target-process=all: 決算期在庫テーブルのDROP・CREATE・基礎データ投入を行い、そのうえで履歴不足分の個別集計を実施
 *   --target-process=continue: 履歴不足分の個別集計のみ実施。
 * のどちらかを選択する。all以外はcontinue。
 * 
 * 入荷扱いとなるのは良品（tb_individualorderhistory.regular）のみ。
 */
class Misc202209AggregateStockHistoryCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;
  
  const ARRIVAL_DAYS = 14; // 出荷日から何日後を到着日とするか。現在はエア／コンテナとも14で計算。
  
  const LOG_EXEC_TITLE = '決算日在庫集計';
  
  private $stockTableName = null;
  private $historyTableName = null;
  private $targetDate = null; // 日付型
  private $targetDateStr = null; // 文字列型。ログなど向け
  
  protected function configure()
  {
    $this
      ->setName('misc:202209-aggregate-stock-history')
      ->setDescription('決算日在庫集計バッチ。')
      ->addOption('target-process', null, InputOption::VALUE_OPTIONAL, '対象処理。all: 集計テーブル作成・基礎データ投入も含めて実施　continue: 不足分の個別集計のみ実施', 'continue')
      ->addOption('target-date', null, InputOption::VALUE_OPTIONAL, '集計対象日。yyyymmdd')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }
  
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /* 初期化処理 */
    
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->initLogTimer();
    $logger->info(self::LOG_EXEC_TITLE . " 開始");
    
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
    
    if (!$input->getOption('target-date')) {
      throw new \RuntimeException(self::LOG_EXEC_TITLE . ' 処理対象日は必須です');
    }
    
    try {
    
      // 処理範囲
      $targetProcess =  $input->getOption('target-process');
      $this->targetDate = (new \DateTimeImmutable($input->getOption('target-date')))->setTime(0, 0, 0);
      $this->targetDateStr = $this->targetDate->format('Ymd');
      $logger->info(self::LOG_EXEC_TITLE . " 開始. 処理対象[$targetProcess - {$this->targetDateStr}");
      
      // 変数宣言
      $this->stockTableName = "tmp_accounting_period_stock_" . $this->targetDate->format('Ymd');
      $this->historyTableName = "tmp_accounting_period_stock_arrival_history_" . $this->targetDate->format('Ymd');
      
      /* 集計テーブル作成・基礎データ投入*/
      if ($targetProcess == 'all') {
        $this->intializeAggregateTable();
      }
      
      // 初期データで履歴が足りていないものを処理する。
      // 対象SKUの取得は200件ずつ。履歴構築は1件ずつで、200件終わる都度historyへのバルクインサートを行い、終わったら履歴取得済みフラグを1にする
      $roopCount = 0;
      $count = 0;
      do {
        $targetSkuList = $this->getTargetSku(200);
        if (empty($targetSkuList)) { // 対象がなくなったら終了
          $logger->debug(self::LOG_EXEC_TITLE . " 未処理データなしのため履歴追加ループ終了");
          break;
        }
        $count += count($targetSkuList);
        $stockHistoryList = [];
        foreach ($targetSkuList as $sku) {
          $stockHistoryList = array_merge($stockHistoryList, $this->buildStockHistories($sku['sku_code'], $sku['stock'])); 
        }
        $this->insertHistories($stockHistoryList);
        $this->updateHistoryCompleteFlg($targetSkuList);
        $roopCount++;
        if ($roopCount % 5 == 0) { // 1000件ごとにログ
          $logger->debug(self::LOG_EXEC_TITLE . "$count 件処理");
        }
      } while (1);
      
      // 全ての履歴が揃ったら、平均在庫日数を計算する
      $this->updateStockAverageDays();
      
      $logger->info(self::LOG_EXEC_TITLE . " 終了. 処理対象[$targetProcess - {$this->targetDateStr}");
    } catch (\Exception $e) {
      $logger->error(self::LOG_EXEC_TITLE . ' エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      return 1;
    }
    return 0;
  }
  
  /**
   * 集計テーブル作成・基礎データ投入を実施する
   * ・対象テーブルのDROP & CREATE。
   * ・各SKUについて、過去2年内の最新の入荷履歴を投入する。これで 2/3 のSKUは履歴がまかなえる
   * ・履歴が足りたものについては、平均在庫日数を計算し、履歴取得済みフラグを1にする。
   * 
   * この一連の処理で、おおよそ1～2分を想定。
   */
  private function intializeAggregateTable() {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    $dbLogName = $this->getDb('log')->getDatabase();
    
    $logger->debug(self::LOG_EXEC_TITLE . '初期処理 開始' . $this->targetDate->format('Y-m-d'));
    
    // DROP & CREATE 
    
    $dbMain->query("DROP TABLE IF EXISTS {$dbTmpName}.{$this->stockTableName}");
    $sql = <<<EOD
      CREATE TABLE {$dbTmpName}.{$this->stockTableName} (
        `sku_code` varchar(255) NOT NULL COMMENT 'SKUコード',
        `genka_tnk` int(10) DEFAULT NULL COMMENT '基準原価',
        `additional_cost` int(10) DEFAULT NULL COMMENT '仕入付加費用',
        `fixed_cost` varchar(255) DEFAULT NULL COMMENT '商品固有固定費',
        `additional_cost_rate` int(10) DEFAULT NULL COMMENT '仕入先費用率',
        `additional_cost_rate_without_tax` int(10) DEFAULT NULL COMMENT '仕入先費用率（関税除外）',
        `compress_cost` int(10) DEFAULT NULL COMMENT '圧縮コスト',
        `baika_genka` int(10) DEFAULT NULL COMMENT '売価原価',
        `baika_genka_without_tax` int(10) DEFAULT NULL COMMENT '売価原価（関税除外）',
        `baika_tnk` int(10) DEFAULT NULL COMMENT '売価単価',
        `stock` int(10) NOT NULL DEFAULT 0 COMMENT '在庫数',
        `weight` int(10) DEFAULT NULL COMMENT '重量(g)',
        `stock_average_days` int(10) DEFAULT NULL COMMENT '平均在庫日数',
        `history_complete_flg` tinyint(1) NOT NULL DEFAULT 0 COMMENT '履歴取得済みフラグ\n履歴が集まったら1。0のものは過去をさらにさかのぼる',
        PRIMARY KEY (`sku_code`)
      ) ENGINE=InnoDB COMMENT='決算期在庫$this->targetDateStr';
EOD;
    $dbMain->query($sql);
    
    $dbMain->query("DROP TABLE IF EXISTS {$dbTmpName}.{$this->historyTableName}");
    $sql = <<<EOD
      CREATE TABLE {$dbTmpName}.{$this->historyTableName} (
        `sku_code` varchar(255) NOT NULL COMMENT 'SKUコード',
        `individualorderhistory_id` int NOT NULL COMMENT '注残ID\ntb_individualorderhistory.id',
        `arrival_date` datetime NOT NULL COMMENT '入荷日時\nntb_individualorderhistory.remain_shipping_date の2週間後',
        `regular` int NOT NULL COMMENT '良品数',
        PRIMARY KEY (`sku_code`, individualorderhistory_id), 
        KEY individualorderhistory_id (individualorderhistory_id)
      ) ENGINE=InnoDB COMMENT='決算期在庫入荷履歴{$this->targetDateStr}';
EOD;
    $dbMain->query($sql);
    
    /** @var DbCommonUtil $dbUtil */
    $commonUtil = $this->getDbCommonUtil();
    $compressCost = $commonUtil->getSettingValue('COMPRESS_COST'); // 圧縮コストは当時のものはないので現在値
    
    // SKU情報投入。決算日時点で在庫があったSKUのレコードを作成する。これが今後の集計対象となる
    $sql = <<<EOD
      INSERT INTO {$dbTmpName}.{$this->stockTableName}
      SELECT 
        stock.ne_syohin_syohin_code sku_code
        , log.genka_tnk
        , log.additional_cost
        , log.fixed_cost
        , v.additional_cost_rate
        , CASE WHEN v.additional_cost_rate > 10 THEN v.additional_cost_rate - 10 ELSE v.additional_cost_rate END as additional_cost_rate_without_tax 
        , CASE WHEN cal.compress_flg <> 0 THEN :compressCost ELSE 0 END as compress_cost 
        , log.baika_genka
        , null
        , log.baika_tnk
        , stock.total_stock as stock
        , CASE WHEN pci.weight >= 1 THEN pci.weight ELSE m.weight END as weight
        , null
        , 0
      FROM (
        SELECT 
          ne_syohin_syohin_code,
          SUM(stock) total_stock
        FROM {$dbLogName}.tb_product_location_snapshot
        WHERE log_date = :targetDate
        GROUP BY ne_syohin_syohin_code) stock
      LEFT JOIN tb_productchoiceitems pci ON stock.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      LEFT JOIN {$dbLogName}.tb_product_price_log log ON log.log_date = :targetDate AND log.daihyo_syohin_code = pci.daihyo_syohin_code
      LEFT JOIN tb_mainproducts m ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      LEFT JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code 
      LEFT JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':compressCost', $compressCost, \PDO::PARAM_INT);
    $stmt->bindValue(':targetDate', $this->targetDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();
    $targetSkuNum = $stmt->rowCount();

    // 売価原価（関税除外）を計算
    $sql = <<<EOD
      UPDATE {$dbTmpName}.{$this->stockTableName} 
      SET baika_genka_without_tax = TRUNCATE ( 
          ( 
            genka_tnk + additional_cost + fixed_cost
          ) / (1 - (additional_cost_rate_without_tax / 100))
          + compress_cost
        , 0
      )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // tb_individualorderhistory は出荷日はわかるが入荷日はないため、入荷日は出荷日 + ARRIVAL_DAYS とする。
    // 決算日時点で入荷済みのSKUについて、入荷履歴の最新1件を登録する（これで全体の2/3のSKUは履歴が足りる）
    $sql = <<<EOD
      INSERT INTO {$dbTmpName}.{$this->historyTableName} 
      SELECT 
        i.商品コード as sku_code
        , id as individualorderhistory_id
        , DATE_ADD(remain_shipping_date, INTERVAL :arrivalDays DAY) as arrival_date
        , regular
      FROM (
        -- 最新出荷日のレコードのなかで、最大のidのものを、最新出荷データとして取得
        SELECT i.商品コード, MAX(id) as max_id
        FROM (
          -- 一番内側のサブクエリでは、最新出荷日時を取得。同一のものがある場合がある
          SELECT i.商品コード, MAX(i.remain_shipping_date) as max_date
          FROM tb_individualorderhistory i
          WHERE i.remain_shipping_date < DATE_SUB(:targetDate, INTERVAL :arrivalDays DAY) -- 出荷のARRIVAL_DAYS後が入荷なので、決算日ぎりぎりの出荷のものは到着していないので除外
          AND i.remain_shipping_date >= DATE_SUB(:targetDate, INTERVAL 2 YEAR) -- ひとまず2年以内を抽出対象とする。それ以前は個別集計
          AND i.regular >= 1
          GROUP BY i.商品コード
        ) latest_day
        JOIN tb_individualorderhistory i ON i.商品コード = latest_day.商品コード AND i.remain_shipping_date = latest_day.max_date
        WHERE i.regular >= 1
        GROUP BY i.商品コード
      ) latest
      JOIN tb_individualorderhistory i ON i.id = latest.max_id
      JOIN {$dbTmpName}.{$this->stockTableName} s ON i.商品コード = s.sku_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':targetDate', $this->targetDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':arrivalDays', self::ARRIVAL_DAYS, \PDO::PARAM_INT);
    $stmt->execute();
    
    $logger->debug(self::LOG_EXEC_TITLE . "　初期データ在庫数調整");
    // 最新の履歴だけで在庫が満たされたものは入荷数を在庫分だけ残して調整し、履歴取得済みに更新
    $sql = <<<EOD
      UPDATE {$dbTmpName}.{$this->stockTableName}  s
      JOIN {$dbTmpName}.{$this->historyTableName} h ON s.sku_code = h.sku_code
      SET h.regular = s.stock
      WHERE h.regular > s.stock
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    
    $sql = <<<EOD
      UPDATE {$dbTmpName}.{$this->stockTableName} s
      JOIN {$dbTmpName}.{$this->historyTableName} h ON s.sku_code = h.sku_code
      SET s.history_complete_flg = 1
      WHERE h.regular >= s.stock
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $completeHistoryNum = $stmt->rowCount();
    
    $logger->debug(self::LOG_EXEC_TITLE . "初期処理 終了 " . $this->targetDate->format('Y-m-d') . "SKU数[$targetSkuNum] 完了履歴数[$completeHistoryNum]");
  }
  
  /**
   * 在庫数未処理のSKUを指定件数分取得し、SKUコードを一次元配列で返却する。
   * @param $limit 取得件数
   */
  private function getTargetSku($limit) {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    $sql = <<<EOD
      SELECT
        sku_code
        , stock
      FROM {$dbTmpName}.{$this->stockTableName} s
      WHERE s.history_complete_flg = 0
      LIMIT :limit;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * SKUコードと在庫数を引数として取得し、該当のSKUの tb_individualorderhistory（発注履歴）データを取得して
   * 現在在庫数に達するまで発注履歴をさかのぼり、現在在庫の入庫履歴を作成し、返却する。
   * このメソッドは再帰する。初回の取得で発注履歴が足りなかった場合、自分自身を呼び出してデータを追加する。
   * @param unknown $skuCode
   * @param unknown $stock
   */
  private function buildStockHistories($skuCode, $stock, $limit = 10, $offset = 0) {
    $logger = $this->getLogger();
    $resultList = [];
    $orderList = $this->findIndividualorderhistories($skuCode, $limit, $offset);
    // 履歴がなければ終了
    if (empty($orderList)) {
      return $resultList;
    }
    
    $count = 0; // 累計
    $compFlg = 0; // 累計が必要数に達したフラグ
    foreach ($orderList as $data) {
      $result = $data;
      if (($count + $data['regular']) >= $stock) { // 累計が現在在庫数を超えたら、その履歴の入荷数は足切り
        $result['regular'] = $stock - $count;
        $compFlg = 1;
      }
      $count += $result['regular'];
      $resultList[] = $result;
      if ($compFlg) { // 在庫履歴が足りていれば終了
        break;
      }
    }
    if (! $compFlg) { // 足りない場合は再帰呼び出し
      $addResult = $this->buildStockHistories($skuCode, $stock - $count, $limit, $offset + $limit); // 取得済み分の件数は減らす
      $resultList = array_merge($resultList, $addResult);
    }
    return $resultList;
  }
  
  /**
   * 指定されたSKUコードの発注履歴を取得する
   * @param string $skuCode SKUコード1件
   * @param int $limit 取得件数
   */
  private function findIndividualorderhistories($skuCode, $limit = 10, $offset = 0) {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT 
        i.商品コード as sku_code
        , id as individualorderhistory_id
        , DATE_ADD(i.remain_shipping_date, INTERVAL :arrivalDays DAY) as arrival_date
        , i.regular
      FROM tb_individualorderhistory i
      WHERE i.商品コード = :skuCode
        AND i.regular > 0
        AND i.remain_shipping_date < DATE_SUB(:targetDate, INTERVAL :arrivalDays DAY)
      ORDER BY i.remain_shipping_date DESC, i.id DESC
      LIMIT :limit OFFSET :offset
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':skuCode', $skuCode, \PDO::PARAM_STR);
    $stmt->bindValue(':arrivalDays', self::ARRIVAL_DAYS, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->bindValue(':targetDate', $this->targetDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * 履歴をバルクインサートする。
   * @param $list 履歴データのリスト。キー名はカラム名と一致させること。
   */
  private function insertHistories($list) {
    /** @var DbCommonUtil $dbUtil */
    $commonUtil = $this->getDbCommonUtil();
    $dbTmp = $this->getDb('tmp');
    $insertBuilder = new MultiInsertUtil($this->historyTableName, [
      'fields' => [
          'sku_code' => \PDO::PARAM_STR
        , 'individualorderhistory_id' => \PDO::PARAM_INT
        , 'arrival_date' => \PDO::PARAM_STR
        , 'regular' => \PDO::PARAM_INT
      ]
      , 'prefix' => 'INSERT IGNORE INTO ' // 既に1件目データが入っているので、重複したら IGNORE
    ]);
    
    $commonUtil->multipleInsert(
      $insertBuilder,
      $dbTmp,
      $list,
      function($data) {
        return $data;
      },
      'foreach'
    );
  }
  
  /**
   * 指定されたSKUの履歴取得済みフラグを済にする
   * @param unknown $skuList SKUリスト（二次元配列）
   */ 
  private function updateHistoryCompleteFlg($skuList) {
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    $skuCodeList = [];
    foreach($skuList as $sku) {
      $skuCodeList[] = $dbMain->quote($sku['sku_code']);
    }
    $codeListStr = implode(',', $skuCodeList);
    
    $sql = <<<EOD
      UPDATE {$dbTmpName}.{$this->stockTableName} s
      SET s.history_complete_flg = 1
      WHERE s.sku_code IN ( {$codeListStr} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }
  
  /**
   * 平均在庫日数を計算する
   */
  private function updateStockAverageDays() {
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    $sql = <<<EOD
      UPDATE {$dbTmpName}.{$this->stockTableName} s
      JOIN (
        SELECT 
          sku_code
          , SUM(regular) as total_stock
          , SUM(regular * DATEDIFF(:targetDate, arrival_date)) as total_day
        FROM {$dbTmpName}.{$this->historyTableName}
        GROUP BY sku_code
      )  t ON s.sku_code = t.sku_code
      SET stock_average_days = TRUNCATE(t.total_day / t.total_stock, 0)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':targetDate', $this->targetDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();
  }
}