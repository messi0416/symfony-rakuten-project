<?php
/**
 * バッチ処理 受注明細取込（差分更新）処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use MiscBundle\Entity\TbSalesDetail;

class UpdateDbByStockListNextEngineApiCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $results;

  private $apiClient;

  /** API取得件数制限（１リクエストあたり） */
  /** 増やしすぎると指数関数的に処理が重くなるとの記述がAPIにあるため、上限に注意する事。 */
  const API_LIMIT = 5000;

  protected function configure()
  {
    $this
      ->setName('batch:update-db-by-stock-list-next-engine-api')
      ->setDescription('最新在庫データを取得し、DBに保存する処理')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'client_id')
      ->addOption('secret', null, InputOption::VALUE_OPTIONAL, 'client_secret')
      ->addOption('url', null, InputOption::VALUE_OPTIONAL, 'redirect_url')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('最新在庫データを取得し、DBに保存する処理を開始しました。');

    $mode = $input->getOption('target-env');

    try {

      $dbMain = $this->getDb('main');
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
        'message' => null
      ];

      $logExecTitle = sprintf('最新在庫データを取得し、DBに保存する処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '開始', "モード[$mode]", ""));

      // 最終更新日時を取得
      $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_STOCK_LIST_UPDATE);
      if (!$lastUpdated) {
        $lastUpdated = new \DateTimeImmutable(); 
        $lastUpdated = $lastUpdated->modify("-30 minute"); 
      }
      if (!$lastUpdated) {
        throw new \RuntimeException('最新在庫データの最終更新日時が取得できませんでした。処理を中止します。');
      }

      $logger->info("在庫マスタ検索　最終更新: " . $lastUpdated->format('Y-m-d H:i:s'));

      // ---------------------------------------------
      /** API データ取得処理 開始 */

      // メモ：　この辺の ForestNeApiClient の初期化までの処理は、なにかの改修のタイミングで WebAccessUtil->getForestNeApiClient() 利用に切り替え
      $apiInfo = $this->getContainer()->getParameter('ne_api');
      
      $clientId = $apiInfo['client_id'] ?? '';
      $clientSecret = $apiInfo['client_secret'] ?? '';
      $redirectUrl = $apiInfo['redirect_url'] ?? '';

      // テストモードの場合
      if (!empty($value = $input->getOption('id'))) {
        $clientId = $value;
        $lastUpdated = new \DateTimeImmutable(); 
        $lastUpdated = $lastUpdated->modify("-30 minute"); 
      }
      if (!empty($value = $input->getOption('secret'))) {
        $clientSecret = $value;
      }
      if (!empty($value = $input->getOption('url'))) {
        $redirectUrl = $value;
      }

      $accessToken = $commonUtil->getSettingValue('NE_API_ACCESS_TOKEN');
      if (!$accessToken) {
        $accessToken = null;
      }
      $refreshToken = $commonUtil->getSettingValue('NE_API_REFRESH_TOKEN');
      if (!$refreshToken) {
        $refreshToken = null;
      }

      $this->apiClient = new \ForestNeApiClient($clientId, $clientSecret, $redirectUrl, $accessToken, $refreshToken);
      $this->apiClient->setLogger($logger);

      $loginId = $commonUtil->getSettingValue('NE_API_LOGIN_ID');
      $loginPassword = $commonUtil->getSettingValue('NE_API_LOGIN_PASSWORD');

      $this->apiClient->setUserAccount($loginId, $loginPassword);

      /** 在庫マスタを取得 */

      // 最初に最新在庫テーブルを空にする
      $dbMain->query("TRUNCATE tb_totalstock_latest");

      $total = 0; // 取得件数

      $data = $this->searchStockByApi($lastUpdated, $logger); // 在庫マスタ検索
      $total = sizeof($data);
      $logger->info("在庫マスタ検索取得件数：$total");

      $this->results['count'] = $total;
      $this->results['from'] = $lastUpdated->format('Y-m-d H:i:s');

      // 取得したデータをDBに保存
      if ($total > 0) {
        $this->insertStockList($data, $dbMain);
      }

      // アクセストークン・リフレッシュトークンの保存
      $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $this->apiClient->_access_token);
      $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $this->apiClient->_refresh_token);

      // 最終更新日時 更新
      $newLastUpdated = new \DateTimeImmutable(); // 処理成功後、最終更新日時とする日付。
      $newLastUpdated = $newLastUpdated->modify("-10 second"); // 時計のずれの可能性を考慮し、10秒ずらす
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_STOCK_LIST_UPDATE, $newLastUpdated);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('最新在庫データを取得し、DBに保存する処理を完了しました。');

    } catch (\Exception $e) {

      // 処理に失敗した場合にも、アクセストークンは更新する。（NULL更新でも更新）
      if (isset($commonUtil) && isset($this->apiClient) && $this->apiClient instanceof \ForestNeApiClient) {
        $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $this->apiClient->_access_token);
        $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $this->apiClient->_refresh_token);
      }

      $logger->error('最新在庫データを取得し、DBに保存する処理 エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog('最新在庫データを取得し、DBに保存する処理 エラー', '最新在庫データを取得し、DBに保存する処理 エラー', 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, '最新在庫データを取得し、DBに保存する処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }
    return 0;
  }

  /**
   * NEより在庫マスタ検索を行い、結果をCSVに出力する。
   *
   * @param string $searchType 最終更新日
   * @param BatchLogger $logger
   * @return string $results 取得データ
   */
  private function searchStockByApi($targetDate, $logger)
  {
    $count = 0; // 取得件数
    $offset = 0; // APIで取得するデータのオフセット（5000件ごと）および総取得件数
    $loopCount = 0; // ループの何周目かを表す。デバッグ用。

    $results = array();

    $logger->info('在庫マスタ検索API　実行');

    do {
      $stocks = $this->searchStock($targetDate, $offset, $logger); // 在庫一覧
      
      if (!$stocks) {
        break;
      }
      // 取得件数を取得
      $count = sizeof($stocks);
      $offset += $count;

      $results = array_merge($results, $stocks);

      $loopCount++;
    } while ($count >= self::API_LIMIT && $loopCount < 100); // 1回の取得件数が、self::API_LIMIT未満になるまで繰り返す。$loopCountによる制限は無限ループ避け。

    $logger->info('在庫データ検索成功。');

    return $results;
  }

  /**
   * NEより在庫マスタ検索を行い、結果を返却する。
   * 指定された検索タイプに従い、在庫情報の検索を行う。
   *
   * @param int $searchType 最終更新日
   * @param int $offset 1回で取得できない場合のoffset。0指定でスキップなし。1指定で2件目から取得。必須
   * @return array APIで渡されるレスポンスのdataフィールド
   *
   * @see https://developer.next-engine.com/api/api_v1_master_stock/search 在庫マスタ検索 API仕様
   */
  private function searchStock($targetDate, $offset, $logger)
  {
    $query = array() ;

    // 検索結果・絞り込み検索条件の整理
    $query['fields'] = implode(',', $this->receiveStockTargetField);
    $query['stock_last_modified_null_safe_date-gte'] = $targetDate->format('Y-m-d H:i:s');
    $query['offset'] = $offset;
    $query['limit'] = self::API_LIMIT;
    
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    // 検索実行
    $time = microtime(true);
    $stocks = null;
    $stocks = $this->apiClient->apiExecute('/api_v1_master_stock/search', $query) ; // 在庫マスタ
    $now = microtime(true); $rap = $now - $time; $logger->info('API-Log n-rap: ' . round($rap, 2));

    // エラー処理
    if ($stocks['result'] != 'success') {

      $errorlog = "検索, ";

      $message = 'NE APIエラー';
      if (isset($stocks['code'])) {
        $message .= sprintf('[%s] ', $stocks['code']);
      }
      if (isset($stocks['message'])) {
        $message .= $stocks['message'];
      }
      $message .= $errorlog;
      throw new \RuntimeException($message);
    }

    // エラーがなければ、dataフィールドのみ返却する
    return $stocks['data'];
  }

  /**
   * 在庫情報をtb_totalstock_latestに投入する。
   * INSERT IGNORE を利用し、重複があればスキップする。
   * @param array $datas APIから返却される $received['data'] 配下のデータ配列
   */
  private function insertStockList($dataList, $dbMain) {
    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_totalstock_latest", [
        'fields' => $this->stockListInsertColumn
        , 'prefix' => "INSERT IGNORE INTO"
    ]);
    $commonUtil = $this->getDbCommonUtil();
    $commonUtil->multipleInsert($insertBuilder, $dbMain, $dataList, function($row) {

      // CSVのLOAD DATA挙動と合わせるため、 null は全て空文字に変換
      foreach($row as $k => $v) {
        if (is_null($v)) {
          $row[$k] = '';
        }
      }

      $item = [
          '商品コード' => $row['stock_goods_id']
          , '在庫数' => intval($row['stock_quantity'])
          , '引当数'     => intval($row['stock_allocation_quantity'])
          , 'フリー在庫数' => intval($row['stock_free_quantity'])
          , '予約在庫数'     => intval($row['stock_advance_order_quantity'])
          , '予約引当数'     => intval($row['stock_advance_order_allocation_quantity'])
          , '予約フリー在庫数' => intval($row['stock_advance_order_free_quantity'])
          , '不良在庫数' => intval($row['stock_defective_quantity'])
      ];
      return $item;
    }, 'foreach');
  }

  //
  private $stockListInsertColumn = [
      '商品コード' => \PDO::PARAM_STR
      , '在庫数' => \PDO::PARAM_INT
      , '引当数' => \PDO::PARAM_INT
      , 'フリー在庫数' => \PDO::PARAM_INT
      , '予約在庫数' => \PDO::PARAM_INT
      , '予約引当数' => \PDO::PARAM_INT
      , '予約フリー在庫数' => \PDO::PARAM_INT
      , '不良在庫数' => \PDO::PARAM_INT
    ];

  /** NextEngineからの受注明細情報取得の対象フィールド */
  private $receiveStockTargetField = [
    'stock_goods_id' // 商品コード
    , 'stock_quantity' // 在庫数
    , 'stock_allocation_quantity' // 引当数
    , 'stock_free_quantity' // フリー在庫数
    , 'stock_advance_order_quantity' // 予約在庫数
    , 'stock_advance_order_allocation_quantity' // 予約引当数
    , 'stock_advance_order_free_quantity' // 予約フリー在庫数
    , 'stock_defective_quantity' // 不良在庫数
  ];
}