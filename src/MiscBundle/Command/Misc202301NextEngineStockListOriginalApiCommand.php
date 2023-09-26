<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\WebAccessUtil;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Misc202301NextEngineStockListOriginalApiCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** 検索タイプ：在庫一覧_在庫あり */
  const SEARCH_TYPE_IN_STOCK = 1;
  /** 検索タイプ：在庫一覧 */
  const SEARCH_TYPE_ALL_STOCKS = 2;

  private $searchTypeStringArray = [
      self::SEARCH_TYPE_IN_STOCK => '在庫一覧_在庫あり'
      , self::SEARCH_TYPE_ALL_STOCKS => '在庫一覧'
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

  protected function configure()
  {
    $this
    ->setName('misc:202301-next-engine-stock-list-original-api')
    ->setDescription('NEの在庫マスタ検索API、商品マスタ検索APIに接続するコマンド')
    ;
  }

  /**
   * NEのAPIに接続するためのテストプログラム
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('NextEngineのAPI接続テストを開始しました。');

    $result = $this->searchStock(self::SEARCH_TYPE_IN_STOCK, 3, 0);
    var_dump($result);
    $result = $this->searchGoodsName('1nQP2r---BK', 1, 0);
    var_dump($result);

    return 0;
  }


  /**
   * NEより在庫マスタ検索を行い、結果を返却する。
   * 指定された検索タイプに従い、在庫情報の検索を行う。
   *
   * @param int $searchType 検索タイプ。SEARCH_TYPE_IN_STOCK、SEARCH_TYPE_ALL_STOCKS、のどちらかを指定する。
   * @param int $limit 1回に取得する件数。増やしすぎると指数関数的に処理が重くなるとの記述がAPIにあるため、上限に注意する事。未指定の場合はAPI仕様で10000となる
   * @param int $offset 1回で取得できない場合のoffset。0指定でスキップなし。1指定で2件目から取得。必須
   * @return array APIで渡されるレスポンスのdataフィールド
   *
   * @see https://developer.next-engine.com/api/api_v1_master_stock/search 在庫マスタ検索 API仕様
   */
  private function searchStock($searchType, $limit, $offset) {
    $query = array() ;

    // 検索結果・絞り込み検索条件の整理

    $query['fields'] = implode(',', $this->receiveStockTargetField);
    if ($searchType === self::SEARCH_TYPE_IN_STOCK){ // 在庫一覧_在庫あり
      $query['stock_quantity-neq'] = 0;
    }
    
    $query['offset'] = $offset;
    if ($limit) {
      $query['limit'] = $limit;
    }
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    // 検索実行
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    $apiClient = $webAccessUtil->getForestNeApiClient();
    $stocks = null;
    $stocks = $apiClient->apiExecute('/api_v1_master_stock/search', $query) ; // 在庫マスタ

    // エラー処理
    if ($stocks['result'] != 'success') {

      $errorlog = "検索種別[" . $this->searchTypeStringArray[$searchType] . "], ";

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
   * NEより商品マスタ検索を行い、商品名を返却する。
   *
   * @param string $goods_id 商品コード
   * @param int $limit 1回に取得する件数。増やしすぎると指数関数的に処理が重くなるとの記述がAPIにあるため、上限に注意する事。未指定の場合はAPI仕様で10000となる
   * @param int $offset 1回で取得できない場合のoffset。0指定でスキップなし。1指定で2件目から取得。必須
   * @return array APIで渡されるレスポンスのdataフィールド
   *
   * @see https://developer.next-engine.com/api/api_v1_master_goods/search 商品マスタ検索 API仕様
   */
  private function searchGoodsName($goods_id, $limit, $offset) {
    $query = array();

    // 検索結果・絞り込み検索条件の整理

    $query['fields'] = 'goods_name';
    $query['goods_id-eq'] = $goods_id;

    $query['offset'] = $offset;
    if ($limit) {
      $query['limit'] = $limit;
    }
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    // 検索実行
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    $apiClient = $webAccessUtil->getForestNeApiClient();
    $goods = null;
    $goods = $apiClient->apiExecute('/api_v1_master_goods/search', $query) ; // 在庫マスタ

    // エラー処理
    if ($goods['result'] != 'success') {

      $message = 'NE APIエラー';
      if (isset($goods['code'])) {
        $message .= sprintf('[%s] ', $goods['code']);
      }
      if (isset($goods['message'])) {
        $message .= $goods['message'];
      }
      throw new \RuntimeException($message);
    }

    // エラーがなければ、dataフィールドのみ返却する
    return $goods['data'];
  }

}
