<?php
/**
 * Jobは常駐するわけではなく1処理1スレッド（1プロセス）がforkされるだけなので、
 * 特にプロパティ変数などに気を使う必要はない模様
 */

namespace BatchBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob;
use MiscBundle\Entity\JobRequest;
use MiscBundle\Entity\Repository\JobRequestRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\HttpKernel\KernelInterface;

class BaseJob extends ContainerAwareJob
{
  /** @var $logger BatchLogger */
  protected $logger;

  /** @var SymfonyUserInterface */
  protected $account;

  /** @var string|null $runningJobName */
  protected $runningJobName = null;

  // エラーコード
  const ERROR_CODE_GET_PROCESS_LOCK_FAIL = 900; // 処理排他ロック取得失敗

  // コマンドキー
  const COMMAND_KEY_IMPORT_STOCK_LIST                       = 'import_stock_list';
  const COMMAND_KEY_IMPORT_ORDER_LIST                       = 'import_order_list';
  const COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL           = 'import_order_list_incremental';
  const COMMAND_KEY_IMPORT_STOCK_IN_OUT                     = 'import_stock_in_out';
  const COMMAND_KEY_IMPORT_VIEW_RANKING                     = 'import_view_ranking';
  const COMMAND_KEY_IMPORT_RAKUTEN_REVIEW                   = 'import_rakuten_review';
  const COMMAND_KEY_IMPORT_AMAZON_STOCK                     = 'import_amazon_stock';
  const COMMAND_KEY_IMPORT_YABUYOSHI_STOCK                  = 'import_yabuyoshi_stock';
  const COMMAND_KEY_IMPORT_RSL_STOCK                        = 'import_rsl_stock';
  const COMMAND_KEY_IMPORT_SHOPLIST_STOCK                   = 'import_shoplist_stock';
  const COMMAND_KEY_REFRESH_LOCATION                        = 'refresh_location';
  const COMMAND_KEY_REFRESH_LOCATION_WAREHOUSE_TO_LIST      = 'refresh_location_warehouse_to_list';
  const COMMAND_KEY_PRODUCT_LOCATION_SORT_ORDER             = 'product_location_sort_order';
  const COMMAND_KEY_AGGREGATE_SALES_DETAIL                  = 'aggregate_sales_detail';

  const COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST = 'refresh_delivery_statement_detail_product_num_list';
  const COMMAND_EXPORT_ORDER_LIST_TO_EXCEL                  = 'export_order_list_to_excel';
  const COMMAND_KEY_REFRESH_WAREHOUSE_STOCK_MOVE_LIST                  = 'refresh_warehouse_stock_move_list';
  const COMMAND_KEY_UPDATE_VOUCHER_CHANGE_SHIPPING_METHODS  = 'update_voucher_change_shipping_methods';

  const COMMAND_KEY_DOWNLOAD_CSV_YAHOO                      = 'download_csv_yahoo';
  // const COMMAND_KEY_DOWNLOAD_CSV_YAHOO_OTORIYOSE            = 'download_csv_yahoo_otoriyose';
  const COMMAND_KEY_DOWNLOAD_CSV_PPM                        = 'download_ppm_products';

  const COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE                  = 'export_csv_next_engine';
  const COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_MALL_PRODUCT     = 'export_csv_next_engine_mall_product';
  const COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_SET_PRODUCT      = 'export_csv_next_engine_set_product';
  const COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK     = 'export_csv_next_engine_update_stock';
  const COMMAND_KEY_EXPORT_CSV_YAHOO                        = 'export_csv_yahoo';
  const COMMAND_KEY_EXPORT_CSV_YAHOO_UPDATE_STOCK           = 'export_csv_yahoo_update_stock';
  const COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE              = 'export_csv_yahoo_otoriyose';
  const COMMAND_KEY_EXPORT_CSV_AMAZON                       = 'export_csv_amazon';
  const COMMAND_KEY_EXPORT_CSV_AMAZON_COM                   = 'export_csv_amazon_com';
  const COMMAND_KEY_EXPORT_CSV_SHOPLIST                     = 'export_csv_shoplist';
  const COMMAND_KEY_EXPORT_CSV_RAKUTEN                      = 'export_csv_rakuten';
  const COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPLOAD               = 'export_csv_rakuten_upload';
  const COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPDATE_STOCK         = 'export_csv_rakuten_update_stock';
  const COMMAND_KEY_EXPORT_CSV_RAKUTEN_RPP_EXCLUDE          = 'export_csv_rakuten_rpp_exclude';
  const COMMAND_KEY_EXPORT_CSV_RAKUTEN_GOLD                 = 'export_csv_rakuten_gold';
  const COMMAND_KEY_EXPORT_CSV_PPM                          = 'export_csv_ppm';
  const COMMAND_KEY_EXPORT_CSV_AMAZON_FBA_ORDER             = 'export_csv_amazon_fba_order';
  const COMMAND_KEY_EXPORT_CSV_WOWMA                        = 'export_csv_wowma';
  const COMMAND_KEY_EXPORT_CSV_Q10                          = 'export_csv_q10';

  const COMMAND_KEY_DISCOUNT_PROCESS                        = 'discount_process';
  const COMMAND_KEY_UPDATE_PRODUCT_COST_RATE_PROCESS        = 'update_product_cost_rate_process';

  const COMMAND_KEY_CONVERT_MALL_ORDER_CSV_DATA             = 'convert_mall_order_csv_data';

  const COMMAND_KEY_RECALCULATE_PURCHASE_ORDER              = 'recalculate_purchase_order';
  const COMMAND_KEY_SKU_SHIPPINGDIVISION_AUTO_SETTING       = 'sku_shippingdivision_auto_setting';
  const COMMAND_KEY_SKU_SHIPPINGDIVISION_REFLECT_MAINPRODUCT = 'sku_shippingdivision_reflect_mainproduct';
  const COMMAND_KEY_UPDATE_PRODUCT_SIZE                     = 'update_product_size';
  const COMMAND_KEY_SKU_SIZE_CHANGE_RELATED_UPDATE          = 'sku_size_change_related_update';

  const COMMAND_KEY_SUBMIT_PURCHASE_ORDER_LIST              = 'submit_purchase_order_list';
  const COMMAND_KEY_CREATE_AMAZON_FBA_MULTI_CHANNEL_TRANSPORT_LIST = 'create_amazon_fba_multi_channel_transport_list';


  // NextEngineUpload
  const COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS     = 'upload_products_and_reservations';
  const COMMAND_KEY_NE_UPLOAD_MALL_PRODUCTS                 = 'upload_next_engine_mall_products';
  const COMMAND_KEY_NE_UPLOAD_SET_PRODUCTS                  = 'upload_next_engine_set_products';

  // NonExclusive
  const COMMAND_KEY_YAHOO_IMAGE_CHECK_AND_UPLOAD            = 'yahoo_image_check_and_upload';
  const COMMAND_KEY_EXPORT_CSV_SHOPLIST_UPLOAD              = 'export_csv_shoplist_upload';

  // AlibabaProductCheck
  const COMMAND_KEY_ALIBABA_PRODUCT_CHECK                   = 'alibaba_product_check';

  // 共通日次処理
  const COMMAND_KEY_DAILY_BATCH                             = 'daily_batch';

  const COMMAND_WAREHOUSE_BOX_MOVE                          = 'warehouse_box_move';

  // 発送方法一括変換
  const COMMAND_KEY_DELIVERY_METHOD_CONVERSION              = 'delivery_method_conversion';

  // 商品画像アップロード
  const COMMAND_KEY_PRODUCT_IMAGE_UPLOAD_FTP                = 'product_image_upload_ftp';

  // YahooレビューCSVデータ登録
  const COMMAND_KEY_UPDATE_YAHOO_REVIEW                     = 'update_yahoo_review';

  // 倉庫実績集計
  const COMMAND_KEY_AGGREGATE_WAREHOUSE_RESULT_HISTORY      = 'aggregate_warehouse_result_history';

  // 商品売上実績集計処理
  const COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY = 'aggregate_product_sales_account_result_history';

  // 商品売上実績集計処理（予約分）
  const COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY_RESERVED = 'aggregate_product_sales_account_result_history_reserved';

  // 出荷リスト自動生成
  const COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER    = 'csv_download_and_update_shipipng_voucher';

  // 代表商品販売ステータス更新処理
  const COMMAND_KEY_UPDATE_PRODUCT_SALES_STATUS             =  'update_product_sales_status';

  // SKU別カラー種別更新処理
  const COMMAND_KEY_UPDATE_SKU_COLOR                        = 'update_sku_color';

  // 商品売上担当者適用終了処理
  const COMMAND_KEY_UPDATE_PRODUCT_SALES_ACCOUNT_APPLY_END = 'update_product_sales_account_apply_end';

  // 楽天ジャンル別商品属性項目マスタ更新処理
  const COMMAND_KEY_UPDATE_RAKUTEN_GENRE_ATTRIBUTE = 'update_rakuten_genre_attribute';

  // SKU別楽天商品属性項目更新処理
  const COMMAND_KEY_UPDATE_SKU_RAKUTEN_ATTRIBUTE = 'update_sku_rakuten_attribute';

  // SHOPLISTスピード便移動伝票一括作成
  const COMMAND_KEY_CREATE_TRANSPORT_LIST_SHOPLIST_SPEED_BIN = 'create_transport_list_shoplist_speed_bin';
  // SHOPLISTスピード便出荷数集計処理
  const COMMAND_KEY_AGGREGATE_SHOPLIST_SPEEDBIN_DELIVERY = 'aggregate_shoplist_speedbin_delivery';

  // 楽天SKU属性情報値取込処理
  const COMMAND_KEY_IMPORT_SKU_RAKUTEN_ATTRIBUTE_VALUE = 'import_rakuten_sku_attribute_value';

  // 最新在庫データを取得し、DBに保存する処理
  const COMMAND_KEY_UPDATE_STOCK_LIST = 'update_stock_list';

  // 処理名
  public static $COMMAND_NAMES = [
      self::COMMAND_KEY_IMPORT_STOCK_LIST                       => '在庫データ取込'
    , self::COMMAND_KEY_IMPORT_ORDER_LIST                       => '受注明細取込'
    , self::COMMAND_KEY_IMPORT_ORDER_LIST_INCREMENTAL           => '受注明細差分更新'
    , self::COMMAND_KEY_IMPORT_STOCK_IN_OUT                     => '入出庫データ取込'
    , self::COMMAND_KEY_IMPORT_VIEW_RANKING                     => '閲覧ランキング取込'
    , self::COMMAND_KEY_IMPORT_RAKUTEN_REVIEW                   => '楽天レビュー取込'
    , self::COMMAND_KEY_IMPORT_AMAZON_STOCK                     => 'Amazon在庫取込'
    , self::COMMAND_KEY_IMPORT_YABUYOSHI_STOCK                  => '藪吉在庫取込'
    , self::COMMAND_KEY_IMPORT_RSL_STOCK                        => 'RSL在庫取込'
    , self::COMMAND_KEY_IMPORT_SHOPLIST_STOCK                   => 'SHOPLIST在庫取込'
    , self::COMMAND_KEY_REFRESH_LOCATION                        => 'ロケーション更新'
    , self::COMMAND_KEY_REFRESH_LOCATION_WAREHOUSE_TO_LIST      => 'ロケーション 倉庫へ画面 在庫数更新'
    , self::COMMAND_KEY_PRODUCT_LOCATION_SORT_ORDER             => '商品ロケーション並べ替え'
    , self::COMMAND_KEY_AGGREGATE_SALES_DETAIL                  => '伝票毎利益再集計'

    , self::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST => '納品書印刷待ち一覧再集計'
    , self::COMMAND_EXPORT_ORDER_LIST_TO_EXCEL                  => '輸出書類出力'
    , self::COMMAND_KEY_REFRESH_WAREHOUSE_STOCK_MOVE_LIST       => '在庫移動一覧更新処理'
    , self::COMMAND_KEY_UPDATE_VOUCHER_CHANGE_SHIPPING_METHODS  => '発送方法変更ダウンロード・反映'

    , self::COMMAND_KEY_DOWNLOAD_CSV_YAHOO                      => 'Yahoo CSVダウンロード'
    // , self::COMMAND_KEY_DOWNLOAD_CSV_YAHOO_OTORIYOSE            => 'YahooおとりよせCSVダウンロード'

    , self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE                  => 'NextEngineCSV出力処理'
    , self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_MALL_PRODUCT     => 'NextEngineモール商品CSV出力処理'
    , self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_SET_PRODUCT      => 'NextEngineセット商品CSV出力処理'
    , self::COMMAND_KEY_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK     => 'NextEngine在庫同期処理'
    , self::COMMAND_KEY_EXPORT_CSV_YAHOO                        => 'ヤフーCSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_YAHOO_UPDATE_STOCK           => 'ヤフー在庫更新CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE              => 'ヤフーおとりよせCSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_AMAZON                       => 'Amazon CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_AMAZON_COM                   => 'Amazon.com CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_SHOPLIST                     => 'SHOPLIST CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_RAKUTEN                      => '楽天CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPLOAD               => '楽天CSV出力 アップロード'
    , self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_UPDATE_STOCK         => '楽天在庫更新CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_RPP_EXCLUDE          => '楽天RPP除外CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_RAKUTEN_GOLD                 => '楽天GOLD CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_PPM                          => 'PPM CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_AMAZON_FBA_ORDER             => 'Amazon FBA出荷用CSV出力'
    , self::COMMAND_KEY_EXPORT_CSV_WOWMA                        => 'Wowma CSV出力処理'
    , self::COMMAND_KEY_EXPORT_CSV_Q10                          => 'Q10 CSV出力処理'

    , self::COMMAND_KEY_DISCOUNT_PROCESS                        => '値下確定'
    , self::COMMAND_KEY_UPDATE_PRODUCT_COST_RATE_PROCESS        => '商品別原価率更新'
    , self::COMMAND_KEY_CONVERT_MALL_ORDER_CSV_DATA             => 'モール受注CSV変換'

    , self::COMMAND_KEY_RECALCULATE_PURCHASE_ORDER              => '発注再計算'
    , self::COMMAND_KEY_SKU_SHIPPINGDIVISION_AUTO_SETTING       => 'SKU別送料設定自動設定'
    , self::COMMAND_KEY_SKU_SHIPPINGDIVISION_REFLECT_MAINPRODUCT => 'SKU別送料設定の商品マスタ反映処理'
    , self::COMMAND_KEY_UPDATE_PRODUCT_SIZE                     => '代表商品サイズ更新'
    , self::COMMAND_KEY_SKU_SIZE_CHANGE_RELATED_UPDATE          => 'SKUのサイズ変更に伴う更新処理'
    , self::COMMAND_KEY_SUBMIT_PURCHASE_ORDER_LIST              => '仕入注残一覧入力確定'
    , self::COMMAND_KEY_CREATE_AMAZON_FBA_MULTI_CHANNEL_TRANSPORT_LIST => 'FBAマルチチャネル移動伝票作成'

    , self::COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS     => 'NextEngine商品マスタ一括登録CSVアップロード'
    , self::COMMAND_KEY_NE_UPLOAD_MALL_PRODUCTS                 => 'NextEngineモール商品CSVアップロード'
    , self::COMMAND_KEY_NE_UPLOAD_SET_PRODUCTS                  => 'NextEngineセット商品CSVアップロード'

    , self::COMMAND_KEY_YAHOO_IMAGE_CHECK_AND_UPLOAD            => 'Yahoo画像チェックおよびCSV・画像FTPアップロード処理'
    , self::COMMAND_KEY_EXPORT_CSV_SHOPLIST_UPLOAD              => 'SHOPLIST CSV・画像FTPアップロード処理'

    , self::COMMAND_KEY_ALIBABA_PRODUCT_CHECK                   => 'Alibaba商品巡回'

    , self::COMMAND_KEY_DAILY_BATCH                             => '共通日次バッチ処理'
    , self::COMMAND_WAREHOUSE_BOX_MOVE                          => '倉庫間箱移動処理'
    , self::COMMAND_KEY_DELIVERY_METHOD_CONVERSION              => '発送方法一括変換'
    , self::COMMAND_KEY_PRODUCT_IMAGE_UPLOAD_FTP                => '商品画像 アップロード処理（楽天・Yahoo・PPM）'
    , self::COMMAND_KEY_UPDATE_YAHOO_REVIEW                     => 'Yahoo商品レビューCSVデータ登録処理'

    , self::COMMAND_KEY_AGGREGATE_WAREHOUSE_RESULT_HISTORY      => '倉庫実績集計処理'
    , self::COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY => '商品売上実績集計処理'
    , self::COMMAND_KEY_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY_RESERVED => '商品売上実績集計処理（予約分）'
    , self::COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER    => '出荷リスト自動生成'
    , self::COMMAND_KEY_UPDATE_PRODUCT_SALES_STATUS             => '代表商品販売ステータス更新処理'
    , self::COMMAND_KEY_UPDATE_SKU_COLOR                        => 'SKU別カラー種別更新処理'
    , self::COMMAND_KEY_UPDATE_PRODUCT_SALES_ACCOUNT_APPLY_END  => '商品売上担当者適用終了日登録処理'

    , self::COMMAND_KEY_UPDATE_RAKUTEN_GENRE_ATTRIBUTE          => '楽天ジャンル別商品属性項目マスタ更新処理'
    , self::COMMAND_KEY_UPDATE_SKU_RAKUTEN_ATTRIBUTE            => 'SKU別楽天商品属性項目更新処理'
    , self::COMMAND_KEY_CREATE_TRANSPORT_LIST_SHOPLIST_SPEED_BIN   => 'SHOPLISTスピード便移動伝票一括作成'
    , self::COMMAND_KEY_IMPORT_SKU_RAKUTEN_ATTRIBUTE_VALUE      => '楽天SKU属性情報値取込処理'
    , self::COMMAND_KEY_AGGREGATE_SHOPLIST_SPEEDBIN_DELIVERY        => 'SHOPLISTスピード便出荷数集計処理'
    , self::COMMAND_KEY_UPDATE_STOCK_LIST                       => '最新在庫データ更新'
  ];


  /// 処理名取得(static)
  public static function getCommandName($key)
  {
    return isset(self::$COMMAND_NAMES[$key]) ? self::$COMMAND_NAMES[$key] : null;
  }

  /// 処理名取得(instance)
  public function getCurrentCommandName()
  {
    return self::getCommandName($this->getCommand());
  }

  /// override
  public function run($args)
  {
  }

  /**
   * override
   * 環境の初期値を 'test'にするためのoverride
   * @return KernelInterface
   */
  protected function createKernel()
  {
    // 環境の初期値を 'test'にする
    if (!isset($this->args['kernel.environment'])) {
      $this->args['kernel.environment'] = 'test'; // 基底クラスでは dev が初期値
    }

    return parent::createKernel();
  }

  /**
   * 'command' 引数を取得
   * @return string
   */
  public function getCommand()
  {
    return   (isset($this->args) && isset($this->args['command']))
      ? $this->args['command']
      : null;
  }

  /**
   * 'account' 引数からaccountオブジェクトを取得
   * @return SymfonyUserInterface
   */
  public function getAccount()
  {
    if (!$this->account) {
      // Job登録経路によって情報の取得元が変わる
      $accountId = $this->getArgv('account');
      $isClient = $this->getArgv('isClient');
      $isYahooAgent = $this->getArgv('isYahooAgent');

      // 発注依頼先ユーザ
      if ($accountId && $isClient) {
        $this->account = $account = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:SymfonyUserClient')->find($accountId);
      // Yahooエージェント
      } else if ($accountId && $isYahooAgent) {
        $this->account = $account = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:SymfonyUserYahooAgent')->find($accountId);
      // plusnaoメンバー
      } else if ($accountId) {
        $this->account = $account = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      }
    }
    return $this->account;
  }


  /**
   * 引数オプション取得
   * @param string $key
   * @param mixed $default
   * @return string|null
   */
  protected function getArgv($key, $default = null)
  {
    $result = null;
    // 空文字・空配列、その他 boolでfalse判定される場合、引数未設定の null として扱う。
    //（今となっては理由は不明だが影響範囲が大きいのでそのままとする）
    if (isset($this->args) && isset($this->args[$key])) {
      if (is_string($this->args[$key])) {
        $isExists = strlen($this->args[$key]) > 0;
      } else if (is_array($this->args[$key])) {
        $isExists = count($this->args[$key]) > 0;
      } else {
        $isExists = (bool)$this->args[$key];
      }

      if ($isExists) {
        $result = $this->args[$key];
      }
    }
    if ($result === null && $default !== null) {
      $result = $default;
    }
    return $result;
  }

  /**
   * jobインスタンス取得
   */
  public function getJob()
  {
    return $this->job;
  }


  /**
   * キュー時刻取得
   * @return \DateTime
   */
  public function getQueueDateTime()
  {
    $queueTime = $this->getJob()->payload['queue_time'];
    $dateTime = (new \DateTime('@' . ceil($queueTime)))->setTimezone(new \DateTimeZone('Asia/Tokyo'));
    return $dateTime;
  }


  /**
   * @return BatchLogger
   */
  protected function getLogger()
  {
    if (!$this->logger) {
      $this->logger = $this->getContainer()->get('misc.util.batch_logger');
    }

    return $this->logger;
  }


  /**
   * JobRequest 開始処理
   * @param string $message
   * @return JobRequest|null
   */
  protected function startJobRequest($message = null)
  {
    $jobRequest = $this->findJobRequest();
    if (!$jobRequest) {
      return null;
    }

    $jobRequest->setStarted(new \DateTime());
    $jobRequest->setStatus(JobRequestRepository::STATUE_STARTED);
    if ($message) {
      $jobRequest->setMessage($message);
    }
    $this->getContainer()->get('doctrine')->getManager('main')->flush();

    return $jobRequest;
  }

  /**
   * JobRequest 終了処理
   * @param int $exitCode
   * @param string $message
   */
  protected function finishJobRequest($exitCode = 0, $message = null)
  {
    $jobRequest = $this->findJobRequest();
    if (!$jobRequest) {
      return;
    }

    $jobRequest->setFinished(new \DateTime());
    if ($exitCode == 0) {
      $jobRequest->setStatus(JobRequestRepository::STATUE_FINISHED);
    } else {
      $jobRequest->setStatus(JobRequestRepository::STATUE_ERROR);
    }

    if ($message) {
      $jobRequest->setMessage($message);
    } else {
      $jobRequest->setMessage($exitCode);
    }

    $this->getContainer()->get('doctrine')->getManager('main')->flush();
  }

  /**
   * JobRequest データ取得
   * @return JobRequest
   */
  protected function findJobRequest()
  {
    $jobRequest = null;
    $jobKey = $this->getArgv('jobKey');
    if ($jobKey) {
      /** @var JobRequestRepository $repo */
      $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:JobRequest');
      $jobRequest = $repo->find($jobKey);
    }

    return $jobRequest;
  }

  /// エラー処理 主には実行中排他レコードの削除漏れ回避
  protected function exitError($exitCode, $message = '', $queueName = 'main')
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    if ($this->runningJobName) {
      /** @var DbCommonUtil $dbUtil */
      $dbUtil = $this->getContainer()->get('misc.util.db_common');
      $dbUtil->deleteRunningLog($this->runningJobName, $queueName);
    }

    // ロック取得失敗エラーの場合、DBログをここで作成し通知する
    if ($exitCode == self::ERROR_CODE_GET_PROCESS_LOCK_FAIL) {
      $logger->addDbLog(
        $logger->makeDbLog($this->runningJobName, 'エラー終了', '排他ロック取得失敗')
        , true
        , '排他ロックの取得に失敗しました。'
        , 'error'
      );
    }
    $this->runningJobName = null;

    // その他の実際のエラー処理（メール通知など）は 現状 BatchLogger で行っている
    $error = sprintf('コマンド失敗 [%d] %s', $exitCode, $message);
    $logger->error($error);

    throw new JobExitException('error exit');
  }
}

class JobException extends \RuntimeException {}
class JobExitException extends \RuntimeException {}
