<?php
/**
 * バッチ処理 受注明細取込（差分更新）ループテスト処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
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

class UpdateDbByOrderListNextEngineApiLoopTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  private $apiClient;

  /** 検索タイプ：更新日 */
  const SEARCH_TYPE_UPDATE_DATE = 1;
  /** 検索タイプ：引当日 */
  const SEARCH_TYPE_ALLOCATE_DATE = 2;
  /** 検索タイプ：伝票番号指定 */
  const SEARCH_TYPE_VOUCHER_NUMBER = 3;

  /** 検索データタイプ：受注明細 */
  const SEARCH_DATA_TYPE_DETAIL = 11;
  /** 検索データタイプ：配送情報 */
  const SEARCH_DATA_TYPE_DELIVERY = 12;

  private $searchTypeStringArray = [
      self::SEARCH_TYPE_UPDATE_DATE => '更新日'
      , self::SEARCH_TYPE_ALLOCATE_DATE => '引当日'
      , self::SEARCH_TYPE_VOUCHER_NUMBER => '伝票番号指定'
  ];

  private $searchDataTypeStringArray = [
      self::SEARCH_DATA_TYPE_DETAIL => '受注明細'
      , self::SEARCH_DATA_TYPE_DELIVERY => '配送情報'
  ];

  protected function configure()
  {
    $this
      ->setName('misc:update-db-by-order-list-next-engine-api-loop-test')
      ->setDescription('受注明細取込（差分更新）ループテスト処理')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'モード。default:通常の差分更新、past3、past6など:数字の分のHだけ前回実行と重複、target:伝票番号指定', 'default')
      ->addOption('number', null, InputOption::VALUE_OPTIONAL, '指定伝票番号。mode=targetの場合に利用')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('受注明細取込（差分更新）ループテスト処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $mode = $input->getOption('mode');
    $voucherNumber = $input->getOption('number');
    $logger->info('mode:'.$mode.' number:'.$voucherNumber);

    try {

      $dbMain = $this->getDb('main');
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
        'message' => null
      ];

      $logExecTitle = sprintf('受注明細取込（差分更新）ループテスト処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '開始', "モード[$mode]", "伝票番号[$voucherNumber]"));

      // 最終更新日時を取得
      $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_ORDER_DETAIL_INCREMENTAL_UPDATE);
      if (!$lastUpdated) {
        $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_ORDER_DETAIL);
      }
      if (!$lastUpdated) {
        throw new \RuntimeException('受注明細の最終更新日時が取得できませんでした。処理を中止します。');
      }

      $logger->info("受注明細差分更新ループテスト　最終更新: " . $lastUpdated->format('Y-m-d H:i:s'));

      if(strpos ( $mode , 'past') !== false){
        $past_hour = preg_replace("/\D/",'',$mode);
        $lastUpdated->modify("-".$past_hour." hour");
        $logger->info("受注明細差分更新ループテスト　変更後基準日時: " . $lastUpdated->format('Y-m-d H:i:s'));
      }

      $newLastUpdated = new \DateTimeImmutable(); // 処理成功後、最終更新日時とする日付。
      $newLastUpdated = $newLastUpdated->modify("-10 second"); // 時計のずれの可能性を考慮し、10秒ずらす

      // ---------------------------------------------
      /** API データ取得処理 開始 */

      $apiInfo = $this->getContainer()->getParameter('ne_api');
      $clientId = $apiInfo['client_id'];
      $clientSecret = $apiInfo['client_secret'];
      $redirectUrl = $apiInfo['redirect_url'];

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

      /** 受注情報を取得 */

      // 最初に一時テーブルを空にする
//      $dbMain->query("TRUNCATE tb_sales_detail_tmp");

      $total = 0; // 取得件数

      // 時間指定か、伝票指定かで検索内容が変わる
      if($mode === 'target' && $voucherNumber){
        $dataList = $this->searchReceiveOrder(
            self::SEARCH_TYPE_VOUCHER_NUMBER, self::SEARCH_DATA_TYPE_DETAIL, null, $voucherNumber, null, 0);
        $total = count($dataList);
        if ($total) {
          $this->insertSalesOrderTmp($dataList, $dbMain);
        }
      } else {
        $total += $this->searchAndUpdateDB(self::SEARCH_TYPE_UPDATE_DATE, self::SEARCH_DATA_TYPE_DETAIL, $lastUpdated, $dbMain, $logger); // 更新日ベース
        $total += $this->searchAndUpdateDB(self::SEARCH_TYPE_ALLOCATE_DATE, self::SEARCH_DATA_TYPE_DETAIL, $lastUpdated, $dbMain, $logger); // 更新日ベース
      }
      $logger->info("受注明細差分更新ループテスト：明細取得件数：$total");

      $this->results['count'] = $total;
      if ($this->results['count'] != 0) {

        /** @var NextEngineMallProcess $neMallProcess */
        $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

//         // 店舗名補完
//         // -- 存在しない店舗IDの有無をチェック
//         $sql = <<<EOD
//           SELECT
//             COUNT(DISTINCT t.店舗コード) AS CNT
//           FROM tb_sales_detail_tmp t
//           LEFT JOIN tb_ne_shop s ON t.`店舗コード` = s.shop_id
//           WHERE s.shop_id IS NULL
// EOD;
//         $stmt = $dbMain->query($sql);
//         if ($stmt->fetchColumn(0) > 0) {
//           // もし存在しない店舗コードがあれば、店舗一覧を更新
//           $commandArgs = [
//             'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
//           ];
//           if (!is_null($this->account)) {
//             $commandArgs[] = sprintf('--account=%d', $this->account->getId());
//           }

//           $input = new ArgvInput($commandArgs);
//           $output = new ConsoleOutput();

//           $command = $this->getContainer()->get('batch.update_next_engine_api_shop_list');
//           $command->run($input, $output);
//           $logger->info('NextEngine店舗一覧更新');
//         }

//         // 店舗名を補完
//         $sql = <<<EOD
//           UPDATE
//           tb_sales_detail_tmp t
//           INNER JOIN tb_ne_shop s ON t.`店舗コード` = s.shop_id
//           SET t.`店舗名` = s.shop_name
// EOD;
//         $dbMain->query($sql);


//         // 受注明細更新処理
//         $importInfo = $neMallProcess->createImportInfo();
//         $neMallProcess->updateSalesDetailWithSalesDetailTmp($importInfo);

//         // 受注明細分析用テーブル(Analyze)更新処理
//         $neMallProcess->updateSalesDetailAnalyze('use_tmp');

//         $this->results['count']   =  $importInfo->importCount;
//         $this->results['min']     =  $importInfo->importMinCode;
//         $this->results['max']     =  $importInfo->importMaxCode;
//         $this->results['minDate'] =  ($importInfo->importMinDate ? $importInfo->importMinDate->format('Y-m-d') : '');
//         $this->results['maxDate'] =  ($importInfo->importMaxDate ? $importInfo->importMaxDate->format('Y-m-d') : '');

//         // productchoiceitems 引当数・フリー在庫数更新
//         $neMallProcess->updateProductchoiceitemsAssignedNum();

//         // 最終受注日を更新
//         $neMallProcess->updateLastOrderDateWithTmp();

        // tb_order_data_mainadd 更新処理
        // 2017/04/10 Accessのメール処理サポートのweb版への移行に伴い、差分更新時にはmainaddは更新しない。
        // 日次処理の受注明細取り込み時には、従来通り行う。（ただ、それも不要となれば削除する。）
//        // 受注データ取り込みテーブルへの取り込み準備（一時テーブルへインサート。更新対象特定のためなので、伝票番号のみでよい）
//        $neMallProcess->renewTableTbOrderDataTmp();
//        $sql = <<<EOD
//          INSERT INTO tb_order_data_tmp (
//              `伝票番号`
//          )
//          SELECT
//              `伝票番号`
//          FROM tb_sales_detail_tmp
//EOD;
//        $dbMain->query($sql);
//        $neMallProcess->updateOrderDataMainaddWithSalesDetailAnalyze();
//
//        $logger->info('(受注データ) メール処理補助用テーブル更新');
        $logger->info('(受注データ) メール処理補助用テーブル更新 => skip');
      }

      // -----------------------------------------------------
      // 配送関連情報 取得＆更新処理。（受注「伝票」APIなので別取得）
      // -----------------------------------------------------
      // 時間指定か、伝票指定かで検索内容が変わる
      $total = 0; // 取得件数
      if($mode === 'target' && $voucherNumber){
        $dataList = $this->searchReceiveOrder(
            self::SEARCH_TYPE_VOUCHER_NUMBER, self::SEARCH_DATA_TYPE_DELIVERY, null, $voucherNumber, null, 0);
        $total = count($dataList);
        if ($total) {
          $this->insertDeliveryInfo($dataList, $dbMain);
        }
      } else {
        $total = $this->searchAndUpdateDB(self::SEARCH_TYPE_UPDATE_DATE, self::SEARCH_DATA_TYPE_DELIVERY, $lastUpdated, $dbMain, $logger); // 更新日ベース
      }

      $logger->info("受注明細差分更新ループテスト：受注伝票　配送情報取得件数：$total");

      // アクセストークン・リフレッシュトークンの保存
      $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $this->apiClient->_access_token);
      $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $this->apiClient->_refresh_token);

      // 最終更新日時 更新
//       if($mode != 'target'){
//         $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_ORDER_DETAIL_INCREMENTAL_UPDATE, $newLastUpdated);
//       }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('受注明細取込（差分更新）ループテスト処理を完了しました。');

    } catch (\Exception $e) {

      // 処理に失敗した場合にも、アクセストークンは更新する。（NULL更新でも更新）
      if (isset($commonUtil) && isset($this->apiClient) && $this->apiClient instanceof \ForestNeApiClient) {
        $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $this->apiClient->_access_token);
        $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $this->apiClient->_refresh_token);
      }

      $logger->error('受注明細取込（差分更新）ループテスト処理 エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog('受注明細取込（差分更新）ループテスト処理 エラー', '受注明細取込（差分更新）ループテスト処理 エラー', 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, '受注明細取込（差分更新）ループテスト処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }
    return 0;
  }

  /**
   * NEより受注伝票検索を行い、結果を返却する。
   * 指定された検索タイプ、検索データタイプに従い、明細または配送情報に関して、更新日ベース、引当日ベース、伝票番号ベースの検索を行う。
   * データの取得順は伝票番号の昇順。
   * 検索タイプ、検索データタイプはこのクラスのconstから取得する事。
   * なお、引当日ベースの検索はAPIからのレスポンスが極めて遅い。おそらくNE側にインデックスがない
   * （テスト実行では、1検索当たり50秒程度かかっている）
   *
   * @param int $searchType 検索タイプ。SEARCH_TYPE_UPDATE_DATE、SEARCH_TYPE_ALLOCATE_DATE、SEARCH_TYPE_VOUCHER_NUMBERのいずれかを指定する。※配送情報検索の場合は無視される
   * @param int $searchDataType 検索データタイプ。SEARCH_DATA_TYPE_DETAIL、SEARCH_DATA_TYPE_DELIVERYのいずれかを指定する。
   * @param \DateTime $targetDate 検索対象日From。この日時以降に更新されたデータを取得する。更新日検索、引当日検索で利用
   * @param int $targetVoucherNumber 伝票番号。伝票番号検索で利用する。
   * @param int $limit 1回に取得する件数。増やしすぎると指数関数的に処理が重くなるとの記述がAPIにあるため、上限に注意する事。未指定の場合はAPI仕様で10000となる
   * @param int $offset 1回で取得できない場合のoffset。0指定でスキップなし。1指定で2件目から取得。必須
   * @param BatchLogger $logger Logger
   * @return array APIで渡されるレスポンスのdataフィールド
   *
   * @see https://developer.next-engine.com/api/api_v1_receiveorder_base/search 受注伝票検索 API仕様
   * @see https://developer.next-engine.com/api/api_v1_receiveorder_row/search 受注明細検索 API仕様
   * @see https://developer.next-engine.com/questions/1043 取得順について
   */
  private function searchReceiveOrder($searchType, $searchDataType, $targetDate, $targetVoucherNumber, $limit, $offset) {
    $query = array() ;

    // 明細検索
    if ($searchDataType === self::SEARCH_DATA_TYPE_DETAIL) {
      $query['fields'] = implode(',', $this->receiveOrderTargetField);
      if ($searchType === self::SEARCH_TYPE_VOUCHER_NUMBER){ //
        $query['receive_order_row_receive_order_id-eq'] = $targetVoucherNumber;
      } else if ($searchType === self::SEARCH_TYPE_UPDATE_DATE) {
        $query['receive_order_row_last_modified_newest_date-gte'] = $targetDate->format('Y-m-d H:i:s');
      } else if ($searchType === self::SEARCH_TYPE_ALLOCATE_DATE) {
        $query['receive_order_order_status_id-neq'] = 50;
        $query['receive_order_row_stock_allocation_date-gte'] = $targetDate->format('Y-m-d H:i:s');
      } else {
        throw new \InvalidArgumentException("Invalid search type:" . $searchType);
      }
      // 伝票の配送状況検索
    } else if ($searchDataType === self::SEARCH_DATA_TYPE_DELIVERY) {
      $query['fields'] = implode(',', $this->deliveryInfoTargetField);
      if ($searchType === self::SEARCH_TYPE_VOUCHER_NUMBER){ //
        $query['receive_order_id-eq'] = $targetVoucherNumber;
      } else if ($searchType === self::SEARCH_TYPE_UPDATE_DATE) {
        $query['receive_order_last_modified_null_safe_date-gte'] = $targetDate->format('Y-m-d H:i:s');
      } else {
        throw new \InvalidArgumentException("Invalid search type:" . $searchType);
      }
    } else {
      throw new \InvalidArgumentException("Invalid data type:" . $searchDataType);
    }
    $query['offset'] = $offset;
    if ($limit) {
      $query['limit'] = $limit;
    }
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    // 検索実行
    $receives = null;
    if ($searchDataType === self::SEARCH_DATA_TYPE_DETAIL) {
      $receives = $this->apiClient->apiExecute('/api_v1_receiveorder_row/search', $query) ; // 受注明細
    } else {
      $receives = $this->apiClient->apiExecute('/api_v1_receiveorder_base/search', $query); // 受注伝票
    }

    // エラー処理
    if ($receives['result'] != 'success') {

      $errorlog = "検索種別[" . $this->searchTypeStringArray[$searchType] . "], ";
      $errorlog .= "検索データタイプ[" . $this->searchDataTypeStringArray[$searchDataType] . "], ";

      if($searchType === self::SEARCH_TYPE_VOUCHER_NUMBER){
        $errorlog .= "対象伝票番号[$targetVoucherNumber]";
      } else if ($searchDataType === self::SEARCH_DATA_TYPE_DELIVERY || $searchType === self::SEARCH_TYPE_UPDATE_DATE || $searchType === self::SEARCH_TYPE_ALLOCATE_DATE) {
        $errorlog .= "対象日付[" . $targetDate->format('Y-m-d H:i:s') . ']';
      }
      $message = 'NE APIエラー';
      if (isset($receives['code'])) {
        $message = sprintf('[%s] ', $receives['code']);
      }
      if (isset($receives['message'])) {
        $message .= $receives['message'];
      }
      $message .= $errorlog;
      throw new \RuntimeException($message);
    }

    // エラーがなければ、dataフィールドのみ返却する
    return $receives['data'];
  }

  /**
   * 受注情報をtb_sales_order_tmpに投入する。
   * INSERT IGNORE を利用し、重複があればスキップする。
   * @param array $datas APIから返却される $received['data'] 配下のデータ配列
   */
  private function insertSalesOrderTmp($dataList, $dbMain) {
//     // 一括insert
//     $insertBuilder = new MultiInsertUtil("tb_sales_detail_tmp", [
//         'fields' => $this->salesDetailTmpInsertColumn
//         , 'prefix' => "INSERT IGNORE INTO"
//     ]);

//     $commonUtil->multipleInsert($insertBuilder, $dbMain, $dataList, function($row) {

//       // CSVのLOAD DATA挙動と合わせるため、 null は全て空文字に変換
//       foreach($row as $k => $v) {
//         if (is_null($v)) {
//           $row[$k] = '';
//         }
//       }

//       $item = [
//           '伝票番号' => intval($row['receive_order_id'])
//           , '受注番号' => $row['receive_order_shop_cut_form_id']
//           , '受注日'     => $row['receive_order_date']
//           , '出荷確定日' => $row['receive_order_send_date']
//           , '取込日'     => $row['receive_order_import_date']
//           , '入金日'     => $row['receive_order_deposit_date']
//           , '配達希望日' => $row['receive_order_hope_delivery_date']
//           , '出荷予定日' => $row['receive_order_send_plan_date']
//           , '納品書印刷指示日' => $row['receive_order_statement_delivery_instruct_printing_date']
//           , 'キャンセル日' => $row['receive_order_cancel_date']
//           , 'キャンセル区分' => $row['receive_order_cancel_type_id']
//           , '入金額' => $row['receive_order_deposit_amount']
//           , '発送伝票番号' => $row['receive_order_delivery_cut_form_id']
//           , '店舗名' => '' // 後で補完
//           , '店舗コード' => $row['receive_order_shop_id']
//           , '発送方法' => $row['receive_order_delivery_name']
//           , '配送方法コード' => $row['receive_order_delivery_id']
//           , '支払方法' => $row['receive_order_payment_method_name']
//           , '支払方法コード' => $row['receive_order_payment_method_id']
//           , '総合計' => $row['receive_order_total_amount']
//           , '商品計' => $row['receive_order_goods_amount']
//           , '税金' => $row['receive_order_tax_amount']
//           , '発送代' => $row['receive_order_delivery_fee_amount']
//           , '手数料' => $row['receive_order_charge_amount']
//           , '他費用' => $row['receive_order_other_amount']
//           , 'ポイント数' => $row['receive_order_point_amount']
//           , '受注状態' => $row['receive_order_order_status_name']
//           , '受注担当者' => $row['receive_order_pic_name']
//           , '受注分類タグ' => $row['receive_order_gruoping_tag']
//           , '確認チェック' => $row['receive_order_confirm_check_id']
//           , '作業用欄' => $row['receive_order_worker_text']
//           , '発送伝票備考欄' => $row['receive_order_delivery_cut_form_note']
//           , 'ピッキング指示' => $row['receive_order_picking_instruct']
//           , '納品書特記事項' => $row['receive_order_statement_delivery_text']
//           , '備考' => $row['receive_order_note']
//           , '配送時間帯' => $row['receive_order_hope_delivery_time_slot_name']
//           , '購入者名' => $row['receive_order_purchaser_name']
//           , '購入者カナ' => $row['receive_order_purchaser_kana']
//           , '購入者電話番号' => $row['receive_order_purchaser_tel']
//           , '購入者郵便番号' => $row['receive_order_purchaser_zip_code']
//           , '購入者住所1' => $row['receive_order_purchaser_address1']
//           , '購入者住所2' => $row['receive_order_purchaser_address2']
//           , '購入者（住所1+住所2）' => $row['receive_order_purchaser_address1'] . ' ' . $row['receive_order_purchaser_address2']
//           , '購入者メールアドレス' => $row['receive_order_purchaser_mail_address']
//           , '顧客cd' => $row['receive_order_customer_id']
//           , '顧客区分' => $row['receive_order_customer_type_id']
//           , '送り先名' => $row['receive_order_consignee_name']
//           , '送り先カナ' => $row['receive_order_consignee_kana']
//           , '送り先電話番号' => $row['receive_order_consignee_tel']
//           , '送り先郵便番号' => $row['receive_order_consignee_zip_code']
//           , '送り先住所1' => $row['receive_order_consignee_address1']
//           , '送り先住所2' => $row['receive_order_consignee_address2']
//           , '送り先（住所1+住所2）' => $row['receive_order_consignee_address1'] . ' ' . $row['receive_order_consignee_address2']
//           , 'ギフト' => $row['receive_order_gift_flag']
//           , '入金状況' => $row['receive_order_deposit_type_id']
//           , '名義人' => '' // これは仕様として仕方ないとする。
//           , '承認状況' => $row['receive_order_credit_approval_type_id']
//           , '承認額' => $row['receive_order_credit_approval_amount']
//           , '納品書発行日' => $row['receive_order_statement_delivery_printing_date']
//           , '重要チェック' => $row['receive_order_important_check_id']
//           , '重要チェック者' => $row['receive_order_important_check_name']
//           , '明細行' => intval($row['receive_order_row_no'])
//           , '明細行キャンセル' => $row['receive_order_row_cancel_flag']
//           , '商品コード（伝票）' => $row['receive_order_row_goods_id']
//           , '商品名（伝票）' => $row['receive_order_row_goods_name']
//           , '商品オプション' => $row['receive_order_row_goods_option']
//           , '受注数' => $row['receive_order_row_quantity']
//           , '引当数' => $row['receive_order_row_stock_allocation_quantity']
//           , '引当日' => $row['receive_order_row_stock_allocation_date']
//           , '売単価' => $row['receive_order_row_unit_price']
//           , '小計' => $row['receive_order_row_sub_total_price']
//           , '元単価' => $row['receive_order_row_received_time_first_cost']
//           , '掛率' => $row['receive_order_row_wholesale_retail_ratio']
//           , 'NE側更新日時' => $row['receive_order_row_last_modified_newest_date']
//       ];
//      return $item;
//    }, 'foreach');
  }

  /**
   * 配送情報ををtb_sales_voucher_delivery_infoに投入する。
   * ON DUPLICATE KEY UPDATEを利用し、重複があれば上書きする。
   * @param array $datas APIから返却される $receives['data'] 配下のデータ配列
   */
  private function insertDeliveryInfo($dataList, $dbMain) {
//     // 一括insertによる更新
//     $insertBuilder = new MultiInsertUtil("tb_sales_voucher_delivery_info", [
//         'fields' => [
//             'voucher_number'                            => \PDO::PARAM_STR
//             , 'receive_order_hope_delivery_time_slot_id'  => \PDO::PARAM_STR
//             , 'receive_order_temperature_id'              => \PDO::PARAM_STR
//             , 'receive_order_business_office_stop_id'     => \PDO::PARAM_STR
//             , 'receive_order_business_office_name'        => \PDO::PARAM_STR
//         ]
//         , 'postfix' => " ON DUPLICATE KEY UPDATE "
//         . "     receive_order_hope_delivery_time_slot_id = VALUES(receive_order_hope_delivery_time_slot_id) "
//         . "   , receive_order_temperature_id             = VALUES(receive_order_temperature_id) "
//         . "   , receive_order_business_office_stop_id    = VALUES(receive_order_business_office_stop_id) "
//         . "   , receive_order_business_office_name       = VALUES(receive_order_business_office_name) "
//     ]);

//     $commonUtil->multipleInsert($insertBuilder, $dbMain, $dataList, function ($row) {
//       $item = $row;
//       $item['voucher_number'] = $row['receive_order_id'];
//       $item['receive_order_hope_delivery_time_slot_id'] = isset($row['receive_order_hope_delivery_time_slot_id']) ? $row['receive_order_hope_delivery_time_slot_id']  : '';
//       $item['receive_order_temperature_id']             = isset($row['receive_order_temperature_id'])             ? $row['receive_order_temperature_id']              : '';
//       $item['receive_order_business_office_stop_id']    = isset($row['receive_order_business_office_stop_id'])    ? $row['receive_order_business_office_stop_id']     : '';
//       $item['receive_order_business_office_name']       = isset($row['receive_order_business_office_name'])       ? $row['receive_order_business_office_name']        : '';
//       return $item;
//     }, 'foreach');
  }

  /**
   * データ取得とDB更新を、繰り返し処理を行いながら呼び出すためのラッパー処理。
   * 更新日による検索と、引当日による検索、配送情報検索は、検索件数上限を超える可能性があるためこちら経由で検索を行う。
   *
   * 繰り返し処理中に更新が発生し、取得位置がずれる場合があるが、増える方向なので気にしない（増えた分はこのプロセスではなく、次の差分更新処理で取得する）
   * [イメージ]
   * 以下を3件ずつ取得する（2周目まで取得した状態。数字は各ループでどれを取得するかを表す）
   *   〇〇〇□□□△△
   *   １１１２２２
   *
   * ここで途中で1件増えると、3周目はoffset=6なのでこうなる
   * 　〇〇▲〇□□□△△
   * 　１１　１２２２
   * 　　　　　　　３３３
   * 　　　　　　　↑この□は重複して取得するが、特に問題ない
   * 　　　　　　　　▲は次の起動時に取得するので無視する
   *
   * @param int $searchType 検索タイプ。SEARCH_TYPE_UPDATE_DATE、SEARCH_TYPE_ALLOCATE_DATEのいずれかを指定する。
   * @param int $searchDataType 検索データタイプ。SEARCH_DATA_TYPE_DETAIL、SEARCH_DATA_TYPE_DELIVERYのいずれかを指定する。
   * @param \DateTime $targetDate 検索対象日From。この日時以降に更新されたデータを取得する。更新日検索、引当日検索で利用
   * @return 取得した総データ数
   */
  private function searchAndUpdateDB($searchType, $searchDataType, $targetDate, $dbMain, $logger) {
    $limit = 10; // 一度に取得する上限
    $offset = 0; // offset。最初は0から
    $count = 0; // 1回の実行での取得件数
    $total = 0; // 総取得件数。DB logに出力する。
    $roopCount = 0; // ループの何周目かを表す。デバッグ用。
    $logger->info("受注明細検索ループテスト　ループ検索開始: $searchType, $searchDataType");

    // 伝票・明細 最終更新日以降の更新を取得 limit ずつ繰り返し取得し、一時テーブルに投入
    do {
      $roopCount++; // 検索前に加算
      $dataList = $this->searchReceiveOrder($searchType, $searchDataType, $targetDate, null, $limit, $offset);
      $count = count($dataList);
      $total += $count;

      // 取得したデータをtmpに投入
      if ($count) {
        if ($searchDataType === self::SEARCH_DATA_TYPE_DETAIL) {
          $this->insertSalesOrderTmp($dataList, $dbMain);
        } else if ($searchDataType === self::SEARCH_DATA_TYPE_DELIVERY) {
          $this->insertDeliveryInfo($dataList, $dbMain);
        } else {
          throw new \InvalidArgumentException("データタイプの指定が正しくありません");
        }
      }

      $logger->info("受注明細検索ループテスト　取得した伝票番号：" . print_r(array_column($dataList, 'receive_order_id'), true));

      // 次の1件のためにoffsetを進める
      $offset = $offset + $limit;

    } while ($count >= $limit && $roopCount < 2); // 1回の取得件数が、$limit 未満になるまで繰り返す。$roopCountによる制限は無限ループ避け。
    $logger->info("受注明細検索ループテスト　1ループ文の呼び出し完了: $searchType, $searchDataType:　取得件数 $total");
    return $total;
  }

  /** NextEngineからの受注明細情報取得の対象フィールド */
  private $receiveOrderTargetField = [
      'receive_order_id'
      , 'receive_order_shop_cut_form_id'
      , 'receive_order_date'
      , 'receive_order_send_date'
      , 'receive_order_import_date'
      , 'receive_order_deposit_date'
      , 'receive_order_hope_delivery_date'
      , 'receive_order_send_plan_date'
      , 'receive_order_statement_delivery_instruct_printing_date'
      , 'receive_order_cancel_date'
      , 'receive_order_cancel_type_id'
      , 'receive_order_deposit_amount'
      , 'receive_order_delivery_cut_form_id'
      , 'receive_order_shop_id'
      , 'receive_order_delivery_name'
      , 'receive_order_delivery_id'
      , 'receive_order_payment_method_name'
      , 'receive_order_payment_method_id'
      , 'receive_order_total_amount'
      , 'receive_order_goods_amount'
      , 'receive_order_tax_amount'
      , 'receive_order_delivery_fee_amount'
      , 'receive_order_charge_amount'
      , 'receive_order_other_amount'
      , 'receive_order_point_amount'
      , 'receive_order_order_status_name'
      , 'receive_order_pic_name'
      , 'receive_order_gruoping_tag'
      , 'receive_order_confirm_check_id'
      , 'receive_order_worker_text'
      , 'receive_order_delivery_cut_form_note'
      , 'receive_order_picking_instruct'
      , 'receive_order_statement_delivery_text'
      , 'receive_order_note'
      , 'receive_order_hope_delivery_time_slot_name'
      , 'receive_order_purchaser_name'
      , 'receive_order_purchaser_kana'
      , 'receive_order_purchaser_tel'
      , 'receive_order_purchaser_zip_code'
      , 'receive_order_purchaser_address1'
      , 'receive_order_purchaser_address2'
      , 'receive_order_purchaser_mail_address'
      , 'receive_order_customer_id'
      , 'receive_order_customer_type_id'
      , 'receive_order_consignee_name'
      , 'receive_order_consignee_kana'
      , 'receive_order_consignee_tel'
      , 'receive_order_consignee_zip_code'
      , 'receive_order_consignee_address1'
      , 'receive_order_consignee_address2'
      , 'receive_order_gift_flag'
      , 'receive_order_deposit_type_id'
      , 'receive_order_credit_approval_type_id'
      , 'receive_order_credit_approval_amount'
      , 'receive_order_statement_delivery_printing_date'
      , 'receive_order_important_check_id'
      , 'receive_order_important_check_name'
      , 'receive_order_row_no'
      , 'receive_order_row_cancel_flag'
      , 'receive_order_row_goods_id'
      , 'receive_order_row_goods_name'
      , 'receive_order_row_goods_option'
      , 'receive_order_row_quantity'
      , 'receive_order_row_stock_allocation_quantity'
      , 'receive_order_row_stock_allocation_date'
      , 'receive_order_row_unit_price'
      , 'receive_order_row_sub_total_price'
      , 'receive_order_row_received_time_first_cost'
      , 'receive_order_row_wholesale_retail_ratio'

      , 'receive_order_hope_delivery_time_slot_id' // 時間帯指定区分
      , 'receive_order_temperature_id' // 温度指定
      , 'receive_order_business_office_stop_id' // 営業所止め区分
      , 'receive_order_business_office_name' // 営業所名
      , 'receive_order_row_last_modified_newest_date' //最終更新日
  ];

  /** NextEngineからの受注明細情報取得の対象フィールド */
  private $deliveryInfoTargetField = [
      'receive_order_id'
      , 'receive_order_hope_delivery_time_slot_id' // 時間帯指定区分
      , 'receive_order_temperature_id' // 温度指定
      , 'receive_order_business_office_stop_id' // 営業所止め区分
      , 'receive_order_business_office_name' // 営業所名
  ];

  //
  private $salesDetailTmpInsertColumn = [
      '伝票番号' => \PDO::PARAM_INT
      , '受注番号' => \PDO::PARAM_STR
      , '受注日' => \PDO::PARAM_STR
      , '出荷確定日' => \PDO::PARAM_STR
      , '取込日' => \PDO::PARAM_STR
      , '入金日' => \PDO::PARAM_STR
      , '配達希望日' => \PDO::PARAM_STR
      , '出荷予定日' => \PDO::PARAM_STR
      , '納品書印刷指示日' => \PDO::PARAM_STR
      , 'キャンセル日' => \PDO::PARAM_STR
      , 'キャンセル区分' => \PDO::PARAM_STR
      , '入金額' => \PDO::PARAM_STR
      , '発送伝票番号' => \PDO::PARAM_STR
      , '店舗名' => \PDO::PARAM_STR
      , '店舗コード' => \PDO::PARAM_STR
      , '発送方法' => \PDO::PARAM_STR
      , '配送方法コード' => \PDO::PARAM_STR
      , '支払方法' => \PDO::PARAM_STR
      , '支払方法コード' => \PDO::PARAM_STR
      , '総合計' => \PDO::PARAM_STR
      , '商品計' => \PDO::PARAM_STR
      , '税金' => \PDO::PARAM_STR
      , '発送代' => \PDO::PARAM_STR
      , '手数料' => \PDO::PARAM_STR
      , '他費用' => \PDO::PARAM_STR
      , 'ポイント数' => \PDO::PARAM_STR
      , '受注状態' => \PDO::PARAM_STR
      , '受注担当者' => \PDO::PARAM_STR
      , '受注分類タグ' => \PDO::PARAM_STR
      , '確認チェック' => \PDO::PARAM_STR
      , '作業用欄' => \PDO::PARAM_STR
      , '発送伝票備考欄' => \PDO::PARAM_STR
      , 'ピッキング指示' => \PDO::PARAM_STR
      , '納品書特記事項' => \PDO::PARAM_STR
      , '備考' => \PDO::PARAM_STR
      , '配送時間帯' => \PDO::PARAM_STR
      , '購入者名' => \PDO::PARAM_STR
      , '購入者カナ' => \PDO::PARAM_STR
      , '購入者電話番号' => \PDO::PARAM_STR
      , '購入者郵便番号' => \PDO::PARAM_STR
      , '購入者住所1' => \PDO::PARAM_STR
      , '購入者住所2' => \PDO::PARAM_STR
      , '購入者（住所1+住所2）' => \PDO::PARAM_STR
      , '購入者メールアドレス' => \PDO::PARAM_STR
      , '顧客cd' => \PDO::PARAM_STR
      , '顧客区分' => \PDO::PARAM_STR
      , '送り先名' => \PDO::PARAM_STR
      , '送り先カナ' => \PDO::PARAM_STR
      , '送り先電話番号' => \PDO::PARAM_STR
      , '送り先郵便番号' => \PDO::PARAM_STR
      , '送り先住所1' => \PDO::PARAM_STR
      , '送り先住所2' => \PDO::PARAM_STR
      , '送り先（住所1+住所2）' => \PDO::PARAM_STR
      , 'ギフト' => \PDO::PARAM_STR
      , '入金状況' => \PDO::PARAM_STR
      , '名義人' => \PDO::PARAM_STR
      , '承認状況' => \PDO::PARAM_STR
      , '承認額' => \PDO::PARAM_STR
      , '納品書発行日' => \PDO::PARAM_STR
      , '重要チェック' => \PDO::PARAM_STR
      , '重要チェック者' => \PDO::PARAM_STR
      , '明細行' => \PDO::PARAM_INT
      , '明細行キャンセル' => \PDO::PARAM_STR
      , '商品コード（伝票）' => \PDO::PARAM_STR
      , '商品名（伝票）' => \PDO::PARAM_STR
      , '商品オプション' => \PDO::PARAM_STR
      , '受注数' => \PDO::PARAM_STR
      , '引当数' => \PDO::PARAM_STR
      , '引当日' => \PDO::PARAM_STR
      , '売単価' => \PDO::PARAM_STR
      , '小計' => \PDO::PARAM_STR
      , '元単価' => \PDO::PARAM_STR
      , '掛率' => \PDO::PARAM_STR
      , 'NE側更新日時' => \PDO::PARAM_STR
    ];
}