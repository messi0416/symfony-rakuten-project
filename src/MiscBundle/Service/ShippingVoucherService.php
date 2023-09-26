<?php
namespace MiscBundle\Service;

use AppBundle\Controller\DeliveryController;
use BatchBundle\Job\MainJob;
use BatchBundle\Job\NonExclusiveJob;
use DateTimeInterface;
use Doctrine\ORM\Query\Expr\Select;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\TbDeliveryMethodRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherAutoGenerateRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingGroupRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbCompany;
use MiscBundle\Entity\TbDeliveryChangeShippingMethod;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShippingVoucher;
use MiscBundle\Entity\TbShippingVoucherAutoGenerate;
use MiscBundle\Entity\TbShippingVoucherPacking;
use MiscBundle\Entity\TbShippingVoucherPackingGroup;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Service\ServiceBaseTrait;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use mysql_xdevapi\Exception;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * 共通処理
 */
class ShippingVoucherService
{
  use ServiceBaseTrait;
  
  /**
   *　指定された納品書印刷待ちの受注について、API経由で納品書印刷待ちに更新を行い、最新の受注情報を元に納品書情報のCSVを生成する。
   *
   * この処理は、もともとスクレイピングで実施していた処理のAPI置き換え版である。
   * 受注情報は、tb_sales_detail に保存しているものは一部データにマスクがかかっており、発送情報が取れないことと、
   * タイミング次第では最新ステータスではないことから、このタイミングで改めてAPIで最新を取得する。
   * 
   * 処理順は、
   * (1) 対象の伝票から、ステータス違いなど納品書印刷できないデータを除外して最新データを受け取る
   *     （※スクレイピングでも、最初に伝票番号で「検索」を行い、印刷可能なものだけを抽出しているのでそれに倣う。なので指定伝票全件印刷ではない）
   * (2) 納品書印刷が可能な伝票のみ、ステータスを更新
   * (3) CSVを出力
   * 
   * 受注明細検索API： https://developer.next-engine.com/api/api_v1_receiveorder_row/search
   * 納品書印刷済み一括更新API: https://developer.next-engine.com/api/api_v1_receiveorder_base/bulkupdatereceipted
   * 
   * @param SynfomyUsers $account 実行ユーザ
   * @param string $outputDir 出力先ディレクトリ
   * @param array $voucherNumbers 出力対象の伝票番号群
   * @return string ファイル名。印刷可能伝票がなく、ファイル生成しない場合は nullを返却する。直前に更新されたなどによる更新エラーも同じ。
   */
  public function updateReceiptedAndDownloadShippingVoucher($outputDir, $voucherNumbers) {
    $logger = $this->getLogger();
    $logger->debug("納品書印刷済みへの更新＆CSV生成開始。対象伝票番号：" . implode(',', $voucherNumbers));
    $commonUtil = $this->getDbCommonUtil();
    $apiClient = $this->getWebAccessUtil()->getForestNeApiClient();
    
    // (1) 対象の伝票から、ステータス違いなど納品書印刷できないデータを除外して最新データを受け取る
    $dataList = $this->searchReceiveorderForReceipt($apiClient, $voucherNumbers);
    if (count($dataList) === 0) {
      return null;
    }
    
    // (2) 納品書印刷が可能な伝票のみ、ステータスを更新。更新出来たデータのリストを改めて受け取る
    $receiptedDataList = $this->updateReceipted($apiClient, $dataList, $voucherNumbers);
    if (count($receiptedDataList) === 0) {
      return null;
    }
    
    // (3) CSVを出力
    $filePath = $this->createReceiptCsv($outputDir, $receiptedDataList, $voucherNumbers);
    
    // アクセストークン・リフレッシュトークンの保存
    $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $apiClient->_access_token);
    $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $apiClient->_refresh_token);
    
    return $filePath;
  }
  
  /**
   *　指定された伝票番号のうち、ステータスが納品書印刷待ちで、キャンセルされていないものの、納品書CSVに必要な情報を取得して返却する。
   * @param \ForestNeApiClient $apiClient APIクライアント
   * @param array $voucherNumbers 処理対象の伝票番号
   */
  public function searchReceiveorderForReceipt($apiClient, $voucherNumbers) {
    $logger = $this->getLogger();
    $limit = 5000; // 一度に取得する上限。伝票数300件までなので5000はまず超えないが、念のため
    $offset = 0; // offset。最初は0から
    $count = 0; // 1回の実行での取得件数
    $loopCount = 0;
    $query = [
      'fields' => implode(',', $this->RECEIVE_ORDER_FOR_RECEIPT_TARGET_FEILD)
      , 'receive_order_row_receive_order_id-in' => implode(',', $voucherNumbers)
      // 受注状態区分「納品書印刷待ち」 https://developer.next-engine.com/api/api_v1_system_orderstatus/info
      , 'receive_order_order_status_id-eq' => '20' 
      // 受注キャンセル区分「有効な受注です」 // https://developer.next-engine.com/api/api_v1_system_canceltype/info
      , 'receive_order_cancel_type_id-eq' => '0'
      , 'receive_order_row_cancel_flag-eq' => '0' // 明細未キャンセル
    ];
    $dataList = [];
    
    // 指定された条件に合致する受注明細を limit ずつ繰り返し取得
    // 基本的にループはしない想定。ループした場合、処理中にキャンセルなどが出ると欠ける可能性があるが、既に納品書印刷待ちである伝票番号指定なので大丈夫を期待
    do {
      $query['limit'] = $limit;
      $query['offset'] = $offset;
      $query['wait_flag'] = '1' ;
      $loopCount++;
      
      $receives = $apiClient->apiExecute('/api_v1_receiveorder_row/search', $query) ; // 受注明細
      // エラー処理
      if ($receives['result'] != 'success') {
        $errorLog = '[対象伝票番号(' . implode(',', $voucherNumbers) . ")][limit=$limit, offset=$offset]";
        $message = '納品書CSV生成 NE APIエラー:';
        if (isset($receives['code'])) {
          $message .= sprintf('[%s] ', $receives['code']);
        }
        if (isset($receives['message'])) {
          $message .= $receives['message'];
        }
        $message .= $errorLog;
        throw new \RuntimeException($message);
      }
      
      // エラーがなければ、dataフィールドのみ確保
      $dataList = array_merge($dataList, $receives['data']);
      
      $count = count($dataList);
      
      // 次の1件のためにoffsetを進める
      $offset = $offset + $limit;
      
    } while ($count >= $limit && $loopCount < 100); // 1回の取得件数が、$limit 未満になるまで繰り返す。$roopCountによる制限は無限ループ避け。
    return $dataList;
  }
  
  /**
   * 指定された伝票を納品書印刷済みに更新する。
   * ここでは、受注明細APIで取得したデータを受け取り、データ内の伝票番号とNE側更新日時を元に処理を実行する。
   * 
   * 指定された中に納品書印刷が出来ないデータ（キャンセル済、納品書印刷済みなど）が含まれている場合、resultはerrorとなるが、
   * 印刷可能な伝票は印刷済みとなる。
   * このため、元データから、印刷が行われた伝票データだけを抽出して返却する。
   * @param unknown $apiClient NextEngineへ接続するAPI Client。
   * @param array $dataList 更新対象データリスト
   * @param array $voucherNumbers 対象伝票リスト（ログ用）
   * @return array パラメータ $dataList で渡されたデータのうち、納品書印刷済みに更新されたデータのリスト
   */
  public function updateReceipted($apiClient, $dataList, $voucherNumbers) {
    // リクエストデータ準備 XMLデータを作成する
    $logger = $this->getLogger();
    $receiveorders = ["receiveorder" => []];
    $previousNumber = ''; // 前の伝票番号。同じ伝票が続いた場合には2件目はスキップ
    $i = 0;
    foreach ($dataList as $data) {
      if ($data['receive_order_id'] == $previousNumber) {
        continue;
      }
      $receiveorders["receiveorder"][$i] = [];
      $receiveorders["receiveorder"][$i]["@receive_order_id"] = $data['receive_order_id']; // @を付けると属性
      $receiveorders["receiveorder"][$i]["@receive_order_last_modified_date"] = $data['receive_order_last_modified_null_safe_date'];
      $receiveorders["receiveorder"][$i]["receive_order_label_print_flag"] = "0";
      $previousNumber = $data['receive_order_id'];
      $i++;
    }
    
    $encoders = array(new XmlEncoder(), new XmlEncoder());
    $normalizers = array(new GetSetMethodNormalizer());
    $serializer = new Serializer($normalizers, $encoders);
    
    $context = [
      'xml_root_node_name' => 'root'
      , 'xml_format_output' => true
      , 'xml_encoding' => 'UTF-8'
    ];
    
    $updateXml = $serializer->serialize($receiveorders, 'xml', $context);
    $query = [];
    
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;
    
    // データ形式XML
    $query['data_type'] = 'xml';
    $query['data'] = $updateXml;
    
    $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
    
    // 更新実行
    $updateResult = $apiClient->apiExecute('/api_v1_receiveorder_base/bulkupdatereceipted', $query) ;
    file_put_contents($scrapingResponseDir . '/next_engine_bulkupdatereceipted_result', print_r($updateResult, true));
    
    // エラーの場合は
    // ・全件エラーのとき：　例外をthrowし、出荷リスト自動生成の1ページ分全体をエラー終了する。
    // ・伝票単位の個別エラーのとき：　エラーの出なかった受注は印刷済みに更新される
    // 　エラーになった伝票だけログに残し、$dataList から除去して、続行。出荷リスト自動生成的には、エラーになったものは完了件数に含まれない
    $errorList = []; // エラーになった伝票番号リスト
    if ($updateResult['result'] != 'success') {
      if (! is_array($updateResult['message'])) { // message が配列ではない時は全件エラー 
        throw new BusinessException('納品書印刷済み一括更新で全件エラーが発生。メッセージ[' 
          . $updateResult['message'] . '], 対象伝票番号[' . print_r($voucherNumbers, true) . ']');
      }
      $messages = [];
      foreach ($updateResult['message'] as $dataInfo) {
        $errorList[$dataInfo['receive_order_id']] = $dataInfo['receive_order_id'];
        // 例：　1234567 は 納品書印刷指示日 [2023/05/27]が指定されてます。納品書印刷指示日がAPI実行日以前でない場合は更新できません。
        $messages[] = $dataInfo['receive_order_id'] . ' は ' . $dataInfo['message']; 
      }
      $logger->warn("納品書印刷済み一括更新で処理できない伝票がありました。: " . implode(',', $messages));
    }
    $receiptedDataList = [];
    foreach ($dataList as $data) {
      if (!isset($errorList[$data['receive_order_id']])) { // エラーリストに伝票番号があればスキップ
        $receiptedDataList[] = $data;
      }
    }
    return $receiptedDataList; // 処理が完了した伝票データだけを返却
  }
  
  /**
   * 引き渡されたデータを元に納品書CSVを生成し、CSVファイル名を返却する。
   * @param string $outputDir 出力先ディレクトリ
   * @param array $dataList 納品書CSVデータ。不足データはこのメソッド内で補完する。
   * @param array $voucherNumbers 伝票番号のリスト。店舗名の取得に利用する
   */
  public function createReceiptCsv($outputDir, $dataList, $voucherNumbers) {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    
    // 各伝票の店舗名を取得
    $voucherNumberString = implode(',', $voucherNumbers);
    $sql = <<<EOD
      SELECT distinct 伝票番号, 店舗名 FROM tb_sales_detail_analyze
      WHERE 伝票番号 IN ( {$voucherNumberString} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $shopData = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    
    // CSVを出力
    
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    
    $fileName = 'data' .  (new \Datetime())->format('YmdHis') . rand(100, 999) . '.csv';
    $filePath = $outputDir . '/' . $fileName;
    $fp = fopen($filePath, 'wb');
    
    // ヘッダ
    $headerLine = $stringUtil->convertArrayToCsvLine(DeliveryController::$CSV_FIELDS_NE_SHIPPING_VOUCHER);
    $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";
    fputs($fp, $headerLine);
    
    // 本体
    foreach ($dataList as $data) {
      $item = [
        '店舗名' => $shopData[$data['receive_order_id']]
        , '伝票番号' => $data['receive_order_id']
        , '受注番号' => $data['receive_order_shop_cut_form_id']
        , '受注日' => $data['receive_order_date']
        , '取込日' => $data['receive_order_import_date']
        , '受注状態' => $data['receive_order_order_status_name']
        , '発送方法' => $data['receive_order_delivery_name']
        , '支払方法' => $data['receive_order_payment_method_name']
        , '合計金額' => $data['receive_order_total_amount']
        , '税金' => $data['receive_order_tax_amount']
        , '手数料' => $data['receive_order_charge_amount']
        , '送料' => $data['receive_order_delivery_fee_amount']
        , 'その他' => $data['receive_order_other_amount']
        , 'ポイント' => $data['receive_order_point_amount']
        , '承認金額' => $data['receive_order_credit_approval_amount']
        , '備考' => $data['receive_order_note']
        , '入金金額' => $data['receive_order_deposit_amount']
        , '入金区分' => $data['receive_order_deposit_type_name']
        , '入金日' => $data['receive_order_deposit_date']
        , '納品書印刷指示日' => $data['receive_order_statement_delivery_instruct_printing_date']
        , '納品書発行日' => $data['receive_order_statement_delivery_printing_date']
        , '納品書備考' => $data['receive_order_statement_delivery_text']
        , '出荷日' => ''
        , '出荷予定日' => $data['receive_order_send_plan_date']
        , '作業者欄' => $data['receive_order_worker_text']
        , 'ピック指示内容' => $data['receive_order_picking_instruct']
        , 'ラベル発行日' => $data['receive_order_label_print_date']
        , '配送日' => $data['receive_order_hope_delivery_date']
        , '配送時間帯' => $data['receive_order_hope_delivery_time_slot_name']
        , '配送伝票番号' => $data['receive_order_delivery_cut_form_id']
        , 'クレジット区分' => ''
        , '名義人' => ''
        , '有効期限' => ''
        , '承認番号' => ''
        , '承認区分' => ''
        , '承認日' => ''
        , '購入者名' => $data['receive_order_purchaser_name']
        , '購入者カナ' => $data['receive_order_purchaser_kana']
        , '購入者郵便番号' => $data['receive_order_purchaser_zip_code']
        , '購入者住所1' => $data['receive_order_purchaser_address1']
        , '購入者住所2' => $data['receive_order_purchaser_address2']
        , '購入者電話番号' => $data['receive_order_purchaser_tel']
        , '購入者ＦＡＸ' => $data['receive_order_purchaser_fax']
        , '購入者メールアドレス' => $data['receive_order_purchaser_mail_address']
        , '発送先名' => $data['receive_order_consignee_name']
        , '発送先カナ' => $data['receive_order_consignee_kana']
        , '発送先郵便番号' => $data['receive_order_consignee_zip_code']
        , '発送先住所1' => $data['receive_order_consignee_address1']
        , '発送先住所2' => $data['receive_order_consignee_address2']
        , '発送先電話番号' => $data['receive_order_consignee_tel']
        , '発送先ＦＡＸ' => $data['receive_order_consignee_fax']
        , '配送備考' => $data['receive_order_delivery_cut_form_note']
        , '商品コード' => $data['receive_order_row_goods_id']
        , '商品名' => $data['receive_order_row_goods_name']
        , '受注数' => $data['receive_order_row_quantity']
        , '商品単価' => $data['receive_order_row_unit_price']
        , '掛率' => $data['receive_order_row_wholesale_retail_ratio']
        , '小計' => $data['receive_order_row_sub_total_price']
        , '商品オプション' => $data['receive_order_row_goods_option']
        , 'キャンセル' => $data['receive_order_row_cancel_flag']
        , '引当数' => $data['receive_order_row_stock_allocation_quantity']
        , '引当日' => $data['receive_order_row_stock_allocation_date']
        , '消費税率' => $data['receive_order_row_tax_rate']
      ];
      $line = $stringUtil->convertArrayToCsvLine($item);
      $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
      fputs($fp, $line);
    }
    fclose($fp);
    return $filePath;
  }

  /**
   * 納品書登録管理
   * @param array $account nullable
   * @param array $currentWarehouse
   * @param string $filehash
   * @param string $tmpFileName
   * @param int $shippingVoucherPackingGroupId 梱包グループID
   * @return array $result
   */
  public function manageShippingVoucherImport($account = null, $currentWarehouse, $fileHash, $tmpFileName, $shippingVoucherPackingGroupId = null)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $em = $this->getDoctrine()->getManager('main');
    $result = [];

    // インポート処理：トランザクション開始
    $dbMain->beginTransaction();

    try {

      $result = $this->createShippingVoucher($account, $currentWarehouse, $fileHash, $tmpFileName, $shippingVoucherPackingGroupId);
      $voucher = $result['voucher'];

      $diffMessage = $this->checkShippingVoucher($voucher);
      if ($diffMessage !== '') {
        $this->addShippingVoucherQuantityDiffLog(1, $diffMessage);
        // ロールバック処理
        $dbMain->rollback();
        // インポート処理：トランザクション開始
        $dbMain->beginTransaction();
        $result = $this->createShippingVoucher($account, $currentWarehouse, $fileHash, $tmpFileName, $shippingVoucherPackingGroupId);
        $voucher = $result['voucher'];
        $diffMessage = $this->checkShippingVoucher($voucher);
        if ($diffMessage !== '') {
          $this->addShippingVoucherQuantityDiffLog(2, $diffMessage, 'error');
        }
      }

      // インポート処理：トランザクション終了
      $dbMain->commit();

      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      /** @var TbWarehouse $warehouse */
      $warehouse = $repoWarehouse->find($voucher->getWarehouseId());

      $result['status'] = 'ok';
      $result['message'] = sprintf(
          '納品書CSVの取込を完了し、出荷リストおよびピッキングリストを作成しました。（ピッキングリスト番号: %s-%d [%d]）'
          , $warehouse->getSymbol()
          , $voucher->getWarehouseDailyNumber()
          , $voucher->getPickingListNumber());
      if (isset($result['warning'])) {
        $result['warning'] .= $diffMessage;
      } else if ($diffMessage !== '') {
        $result['warning'] = $diffMessage;
      }
    } catch (\Exception $e) {
      $logger->error('納品書CSVアップロードでエラー発生' . $e->getMessage() . $e->getTraceAsString());
      // ロールバック処理
      if ($dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }

    return $result;
  }

  /**
   * 出荷リスト、ピッキングリスト、梱包情報登録。
   * @param array $account nullable
   * @param array $currentWarehouse
   * @param string $filehash
   * @param string $tmpFileName
   * @param int $shippingVoucherPackingGroupId 梱包グループID
   * @return array $result
   */
  private function createShippingVoucher($account = null, $currentWarehouse, $fileHash, $tmpFileName, $shippingVoucherPackingGroupId = null)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $em = $this->getDoctrine()->getManager('main');

    // 親レコード作成
    $voucher = new TbShippingVoucher();
    if ($account) {
      $voucher->setAccount($account->getId());
    }
    $voucher->setWarehouseId($currentWarehouse->getId());
    $voucher->setFileHash($fileHash);
    $voucher->setImported(new \DateTime());
    $em->persist($voucher);
    $em->flush();
    $result = array(
      'voucher' => $voucher
    );

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :filePath
      INTO TABLE tb_shipping_voucher_detail
      FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
      LINES TERMINATED BY '\n'
      IGNORE 1 LINES
      SET voucher_id = :voucherId
        , created = NOW()
        , updated = NOW()
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':filePath', $tmpFileName, \PDO::PARAM_STR);
    $stmt->bindValue(':voucherId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // Qoo10の場合購入者情報が入らないので、送り先情報を購入者情報とする
    $sql = <<<EOD
      UPDATE
        tb_shipping_voucher_detail d
      INNER JOIN
        tb_sales_detail_analyze a ON CAST(d.`伝票番号` AS SIGNED) = a.`伝票番号`
      INNER JOIN
        tb_shopping_mall m ON a.`店舗コード` = m.ne_mall_id
      SET
        d.`購入者名` = d.`発送先名`
        , d.`購入者カナ` = d.`発送先カナ`
        , d.`購入者郵便番号` = d.`発送先郵便番号`
        , d.`購入者住所1` = d.`発送先住所1`
        , d.`購入者住所2` = d.`発送先住所2`
        , d.`購入者電話番号` = d.`発送先電話番号`
      WHERE d.voucher_id = :voucherId
        AND m.mall_id = :mall_id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':voucherId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':mall_id', DbCommonUtil::MALL_ID_Q10, \PDO::PARAM_INT);
    $stmt->execute();

    // 同一伝票番号存在確認 ステータスが未処理の伝票の重複は不可
    // チェックは当日分の伝票のみ
    $sql = <<<EOD
      SELECT DISTINCT
        d.`伝票番号`
      FROM tb_shipping_voucher_detail d
      INNER JOIN (
        SELECT
            d.`伝票番号`
          , v.id
          , v.`status`
        FROM tb_shipping_voucher_detail d
        INNER JOIN tb_shipping_voucher v ON d.voucher_id = v.id
        WHERE v.id <> :currentId
        AND v.created >= CURRENT_DATE
      ) T ON d.`伝票番号` = T.伝票番号
      WHERE d.voucher_id = :currentId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':currentId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    $duplications = [];
    while ($num = $stmt->fetchColumn(0)) {
      $duplications[] = $num;
    }
    if ($duplications) {
      $result['warning'] = '次の伝票番号は、すでに取り込まれているものと重複しています。 (' . implode(', ', $duplications) . ') 　 ';
    }

    // ピッキングリスト作成・日別倉庫別連番設定
    $this->createPickingListByShippingVoucher($voucher, $voucher->getPickingListDate(), $account, $currentWarehouse);
    $em->flush();

    // 梱包グループ作成
    $sql = <<<EOD
      SELECT
        distinct dm.delivery_id as delivery_id
      FROM tb_shipping_voucher_detail d
      LEFT JOIN tb_delivery_method dm ON d.発送方法 = dm.delivery_name
      WHERE d.voucher_id = :currentId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':currentId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();
    $deliveryMethods = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $deliveryMethod = 0; // 2件以上の配送方法コードがあるか、データがない場合は0
    if (count($deliveryMethods) == 1 && $deliveryMethods[0]['delivery_id'] != null) {
      $deliveryMethod = $deliveryMethods[0]['delivery_id'];
    }

    $packingGroup = null;
    $grougId = $shippingVoucherPackingGroupId;
    if (!$grougId) {
      $packingGroup = new TbShippingVoucherPackingGroup();
      $packingGroup->setDeliveryMethodId($deliveryMethod);
      $packingGroup->setName("");
      $packingGroup->setStatus(TbShippingVoucherPackingGroup::STATUS_NONE);
      $packingGroup->setShippingVoucherPdfFilename("");
      $packingGroup->setPackingComment("");
      $em->persist($packingGroup);
      $em->flush();
      $grougId = $packingGroup->getId();
    }

    // 出荷伝票グループに梱包グループのIDを設定
    $voucher->setShippingVoucherPackingGroupId($grougId);
    $em->flush();

    // 梱包グループ名更新
    $packingService = $this->getContainer()->get('misc.service.packing');
    $pgName = $packingService->calcPackingGroupName($grougId);

    // 数量差異発生のエラーがあった場合、2回目の処理でentityのsetterでのupdateが通らないので直接SQLで更新する(原因が不明)
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_packing_group SET name=:name WHERE id = :groupId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':groupId', $grougId, \PDO::PARAM_INT);
    $stmt->bindValue(':name', $pgName, \PDO::PARAM_STR);
    $stmt->execute();

    $result['packingCount'] = $this->createShippingVoucherPacking($account, $voucher);

    // ピッキングブロック取得（現在の集計データから無理矢理作成。きっとたぶんこの集計のはず実装）
    $sql = <<<EOD
      SELECT
          VD.voucher_id
        , GROUP_CONCAT(
            DISTINCT
              CASE
                WHEN INSTR(r.`対象商品コード`, ':') = 0 THEN NULL
                WHEN SUBSTRING_INDEX(r.`対象商品コード`, ':', 1) = '-' THEN NULL
                ELSE SUBSTRING_INDEX(r.`対象商品コード`, ':', 1)
              END
            ORDER BY r.`対象商品コード`
          ) AS pattern
      FROM tb_delivery_statement_detail_num_order_list_result r
      INNER JOIN (
        SELECT
            d.voucher_id
          , d.`伝票番号`
        FROM tb_shipping_voucher_detail d
        WHERE d.voucher_id = :currentId
        GROUP BY d.voucher_id, d.`伝票番号`
      ) VD ON r.`伝票番号` = VD.伝票番号
      GROUP BY VD.voucher_id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':currentId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    $pattern = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($pattern) {
      $voucher->setPickingBlockPattern($pattern['pattern']);
      $em->flush();
    }

    return $result;
  }

  /**
   * 取り込み済み納品書データからピッキングリスト作成
   * 新規作成、再作成ともに兼ねる
   * @param TbShippingVoucher $voucher
   * @param DateTimeInterface $date
   * @param SymfonyUsers|null $account
   * @throws \Doctrine\DBAL\DBALException
   */
  public function createPickingListByShippingVoucher(TbShippingVoucher $voucher, $date, $account = null, $currentWarehouse)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    // ピッキングリスト作成
    // もしすでに作成した日付を持っていればその日のピッキングリストとして作成する。
    if ($date) {
      $pickingDate = new \DateTime($date->format('Y-m-d 00:00:00'));
    } else {
      $pickingDate = new \DateTime($voucher->getImported()->format('Y-m-d 00:00:00'));
    }

    // 当日出力連番取得
    $sql = <<<EOD
          SELECT
            MAX(number) AS number
          FROM tb_delivery_picking_list dpl
          WHERE dpl.`date` = :pickingDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':pickingDate', $pickingDate->format('Y-m-d'));
    $stmt->execute();

    $pickingListNumber = intval($stmt->fetchColumn(0)) + 1;

    // 当日出力倉庫連番取得
    $sql = <<<EOD
          SELECT
            MAX(warehouse_daily_number)
          FROM tb_shipping_voucher
          WHERE picking_list_date = :pickingDate AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':pickingDate', $pickingDate->format('Y-m-d'));
    $stmt->bindValue(':warehouseId', $currentWarehouse->getId());
    $stmt->execute();

    $warehouseDailyNumber = intval($stmt->fetchColumn(0)) + 1;

    $voucher->setPickingListDate($pickingDate->setTime(0, 0, 0));
    $voucher->setPickingListNumber($pickingListNumber);
    $voucher->setWarehouseDailyNumber($warehouseDailyNumber);

    // WEBピッキングリスト データ作成
    $sql = <<<EOD
          INSERT INTO tb_delivery_picking_list (
              `日時`
            , `商品コード`
            , `商品名`
            , `フリー在庫数`
            , `在庫数`
            , `総ピッキング数`
            , `ロケーションコード`
            , `型番`
            , `janコード`
            , `仕入先コード`
            , `仕入先名`
            , `date`
            , `number`
            , `account`
            , `warehouse_id`
          )
          SELECT
              :now
            , d.`商品コード` AS `商品コード`
            , MAX(d.`商品名`) AS `商品名`
            , 0 AS `フリー在庫数`
            , 0 AS `在庫数`
            , SUM(d.`引当数`) AS `総ピッキング数`
            , '' AS `ロケーションコード`
            , '' AS `型番`
            , '' AS `janコード`
            , '' AS `仕入先コード`
            , '' AS `仕入先名`
            , :pickingListDate    AS `date`
            , :pickingListNumber  AS number
            , :account            AS `account`
            , v.warehouse_id      AS `warehouse_id`
        FROM tb_shipping_voucher_detail d
        INNER JOIN tb_shipping_voucher v ON d.voucher_id = v.id
        WHERE d.voucher_id = :currentId
        GROUP BY d.`商品コード`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':now', $voucher->getImported()->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':pickingListDate', $voucher->getPickingListDate()->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':pickingListNumber',  $voucher->getPickingListNumber(), \PDO::PARAM_INT);
    $stmt->bindValue(':account', $account ? $account->getUsername() : 'BatchSV01', \PDO::PARAM_STR);
    $stmt->bindValue(':currentId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 納品書PDF、ピッキングリスト数量チェック
   * @param TbShippingVoucher $voucher
   * @return array $result
   */
  private function checkShippingVoucher($voucher)
  {
    $dbMain = $this->getDb('main');

    // 納品書PDF、ピッキングリスト数量チェック
    $sql = <<<EOD
      SELECT
          dpl.`商品コード`
          , VD.`受注数`
          , dpl.`総ピッキング数`
      FROM tb_delivery_picking_list dpl
      INNER JOIN (
        SELECT
          d.商品コード
          , SUM(d.`受注数`) `受注数`
          , v.picking_list_date
          , v.picking_list_number
        FROM tb_shipping_voucher_detail d
        INNER JOIN tb_shipping_voucher v ON d.voucher_id = v.id
        WHERE d.voucher_id = :voucherId
        GROUP BY d.`商品コード`
      ) VD  ON dpl.`商品コード` = VD.`商品コード`
          AND dpl.date        = VD.picking_list_date
          AND dpl.number      = VD.picking_list_number
      WHERE VD.`受注数` <> dpl.`総ピッキング数`;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':voucherId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchall(\PDO::FETCH_ASSOC);
    $diffMessage = '';
    if ($data) {
      $diffMessage = "次の商品コードについて、「納品書PDF」と「ピッキングリスト」で数量差異が生じました。 　 ";
      foreach ($data as $index => $value) {
        $diffMessage .= "\n" . sprintf('(%s) 【 %s 】 納品書PDF: %d, ピッキングリスト: %d 　 '
          , $index + 1, $value['商品コード'], $value['受注数'], $value['総ピッキング数']
        );
      }
    }
    return $diffMessage;
  }

  /**
   * 数量差異のログを登録する
   * @param int $diffCount
   * @param string $diffMessage
   * @param string $notificationLevel
   */
  private function addShippingVoucherQuantityDiffLog($diffCount, $diffMessage, $notificationLevel = null)
  {
    $logger = $this->getLogger();

    try {
      $logger->addDbLog(
        $logger->makeDbLog('納品書CSV取込', '数量差異発生', $diffCount . '回目')
                ->setInformation($diffMessage)
        , true
        , '納品書CSV取込で数量差異が発生しました。'
        , $notificationLevel
      );
    } catch (\Exception $e) {
      $logger->error('納品書CSV登録処理：　エラー通知メール送信失敗');
    }
  }

  /**
   * 梱包グループと出荷伝票グループを悲観ロック。
   *
   * 指定した梱包グループIDリスト内のIDを持つ梱包グループと、
   * それに紐付く出荷伝票グループのレコードをselect for updateによってロックする。
   * @param array $packingIds
   */
  public function lockPackingGroupAndVoucher($packingIds)
  {
    /** @var TbShippingVoucherRepository $vRepo */
    $vRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    /** @var TbShippingVoucherPackingGroupRepository $pgRepo */
    $pgRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');

    /** @var TbShippingVoucher[] $shippingVouchers */
    $shippingVouchers = $vRepo->findBy(
      array('shippingVoucherPackingGroupId' => $packingIds)
      , array('picking_list_number' => 'ASC')
    );

    $voucherIds = array();
    foreach ($shippingVouchers as $voucher) {
      $voucherIds[] = $voucher->getId();
    }

    $pgRepo->lockForUpdate($packingIds);
    $vRepo->lockForUpdate($voucherIds);
  }

  /**
   * 出荷伝票梱包作成。
   *
   * CSVの伝票番号毎に、出荷伝票梱包テーブルにデータを登録する。
   * @param SymfonyUsers $account
   * @param TbShippingVoucher $voucher
   * @return int 登録した出荷伝票梱包の数
   */
  private function createShippingVoucherPacking($account, $voucher)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $em = $this->getDoctrine()->getManager('main');

    $sql = <<<EOD
      SELECT
        d.`伝票番号`
        , m.delivery_id
        , m.inquiry_number_need_flg
      FROM
        tb_shipping_voucher_detail d
        LEFT JOIN tb_delivery_method m
          ON d.`発送方法` = m.delivery_name
      WHERE
        d.voucher_id = :voucherId
      GROUP BY
        d.`伝票番号`;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':voucherId', $voucher->getId(), \PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $voucherId = $voucher->getId();
    $accountId = $account ? $account->getId() : 0;
    foreach ($list as $item) {
      // お問い合わせ番号が必要な配送の場合、「有効なお問い合わせ番号がある」を設定　お問い合わせ番号が不要な配送の場合、「有効なお問い合わせ番号がない」を設定
      $validInquiryNumberStatus = boolval($item['inquiry_number_need_flg']) ? TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_EXIST : TbShippingVoucherPacking::VALID_INQUIRY_NUMBER_STATUS_NONE;

      $packing = new TbShippingVoucherPacking();
      $packing->setVoucherId($voucherId);
      $packing->setVoucherNumber($item['伝票番号']);
      $packing->setStatus(TbShippingVoucherPacking::STATUS_NONE);
      $packing->setLabelReissueFlg(0);
      // 発送方法が特定できない場合は、nullで登録
      $packing->setLatestDeliveryMethodId($item['delivery_id']);
      $packing->setValidInquiryNumberStatus($validInquiryNumberStatus);
      $packing->setUpdateAccountId($accountId);
      $em->persist($packing);
    }
    $em->flush();
    return count($list);
  }

  /**
   * 受注明細リストを取得する
   *
   * @param int $voucherNumber 伝票番号
   * @param string $orderNumber 受注番号
   * @param int $neMallId NEモールID
   * @return array 以下のキーを持つ連想配列の配列
   *    'voucherNumber' => int 伝票番号
   *    'orderNumber' => string 受注番号
   *    'orderDate' => date 受注日
   *    'mall' => string モール
   *    'statusName' => string ステータス名
   *    'shippingStopPossibleFlg' => boolean 出荷STOP可能フラグ
   *    'warehouseName' => string 倉庫名
   *    'productQuantity' => int 商品数
   *    'totalAmount' => int 総額
   *    'remarks' => string 備考
   *    'packingId' => int 出荷伝票梱包ID
   * @throws BusinessException 検索結果が多すぎる
   */
  public function findSalesDetailList($voucherNumber, $orderNumber, $neMallId)
  {
    /** @var TbSalesDetailRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');

    // 表示する検索結果の上限を設定
    $limit = 100;

    // 伝票番号の指定が無く、受注番号が指定されている場合、予め件数チェック
    if ($voucherNumber === '' && $orderNumber !== '') {
      $count = $repo->countDistinctOrderNumber($orderNumber, $neMallId);
      if ($count === 0) { return []; }
      if ($count > $limit) {
        throw new BusinessException("検索結果が{$limit}件を超えるため、検索条件を見直してください。");
      }
    }

    $list = $repo->findSalesDetailList($voucherNumber, $orderNumber, $neMallId);

    // 条件毎に、ステータス名・出荷STOP可能フラグ・備考の追加。受注日のフォーマット変更。モール追加。
    $list = array_map(function($item) {

      // ステータス名・出荷STOP可能フラグ・備考追加
      $orderStatus = $item['orderStatus'];
      $ownFlg = (int)$item['ownFlg'];
      $packingStatus = $item['packingStatus'] === null ? null : (int)$item['packingStatus'];
      $voucherStatus = $item['voucherStatus'] === null ? null : (int)$item['voucherStatus'];
      $result = $this->generateShippingStopInfo($orderStatus, $ownFlg, $packingStatus, $voucherStatus);

      $item['statusName'] = $result['statusName'];
      $item['shippingStopPossibleFlg'] = $result['shippingStopPossibleFlg'];
      $item['remarks'] = $result['remarks'];

      // 日付形式変更
      $item['orderDate'] = date('Y/m/d', strtotime($item['orderDate']));

      // モール追加（例.「20: Yahoo(Otoriyose)」）
      $item['mall'] = $item['mallCode'] . ': ' . $item['mallName'];

      unset($item['orderStatus'], $item['ownFlg'], $item['mallCode'], $item['mallName'], $item['voucherStatus'], $item['packingStatus']);
      return $item;

    }, $list);

    return $list;
  }

  /**
   * 出荷伝票梱包のステータスを出荷STOP待ちにする
   *
   * 引数をもとに、出荷伝票梱包のステータスを出荷STOP待ちに更新する。
   * 対象が割り込みで更新されていた場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う
   * @param int $packingId 出荷伝票梱包ID
   * @param int $accountId ログインユーザID
   * @throws BusinessException 対象データが既に更新されていた
   */
  public function shippingStopWaiting($packingId, $accountId)
  {
    /** @var TbShippingVoucherPackingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');

    $dbMain = $this->getDoctrine()->getConnection('main');

    try {
      // トランザクション開始
      $dbMain->beginTransaction();

      // ロック
      $repo->lockForUpdate([$packingId]);

      // 出荷伝票梱包のステータスが未発行でなければ、割り込みが有ったものと見なす。
      $status = $repo->find($packingId)->getStatus();
      if ($status !== TbShippingVoucherPacking::STATUS_NONE) {
        throw new BusinessException('対象データが既に更新されています。');
      }

      // ステータスを出荷STOP待ちに更新し、更新件数を取得する。
      $repo->updateStatus($packingId, TbShippingVoucherPacking::STATUS_WAIT_SHIPPING_STOP, $accountId);

      // コミット
      $dbMain->commit();

    } catch (\Exception $e) {
      if (isset($dbMain)) {
        $dbMain->rollback();
      }
      throw $e;
    }
  }

  /**
   * 出荷STOP情報を作成する。
   *
   * 引数をもとに、ステータス名・出荷STOP可能フラグ・備考を作成する。
   * @param string $orderStatus 受注状態
   * @param int $ownFlg 自社倉庫フラグ
   * @param int $packingStatus 出荷伝票梱包ステータス
   * @param int $voucherStatus 出荷伝票グループステータス
   * @return array 以下のキーを持つ連想配列
   *    'statusName' => string ステータス名
   *    'shippingStopPossibleFlg' => boolean 出荷STOP可能フラグ
   *    'remarks' => string 備考
   */
  private function generateShippingStopInfo($orderStatus, $ownFlg, $packingStatus, $voucherStatus) {
    // 基本は、ステータス名＝受注状態・出荷STOPは不可・備考は空文字。
    $result = [
      'statusName' => $orderStatus,
      'shippingStopPossibleFlg' => false,
      'remarks' => ''
    ];

    /* 受注状態で条件分け
      1. 取込情報不足/受注メール取込済/起票済(CSV/手入力)
      2. 納品書印刷待ち/納品書印刷済
      3. 出荷確定済（完了）*/
    if (in_array($orderStatus, [
        TbSalesDetail::ORDER_STATUS_CAPTURE_INFO_LACK,
        TbSalesDetail::ORDER_STATUS_MAIL_IMPORTED,
        TbSalesDetail::ORDER_STATUS_DRAFTED], true)) {
      $result['remarks'] = 'こちらの伝票は納品書印刷前です。 NextEngineでキャンセル処理を実施してください。';

    } elseif (in_array($orderStatus, [
        TbSalesDetail::ORDER_STATUS_WAIT_PRINT,
        TbSalesDetail::ORDER_STATUS_PRINTED], true)) {

      // 納品書印刷待ちで、出荷伝票梱包のレコードが無い場合
      if ($packingStatus === null) {
        $result['remarks'] = 'こちらの伝票は、梱包準備が開始されている可能性があります。 ロジ担当者へのご連絡と、NextEngineでキャンセル処理を実施した上で、再度この画面をご確認ください。';
      // 自社倉庫の場合
      } elseif ($ownFlg <> 0) {
        switch ($packingStatus) {
          case TbShippingVoucherPacking::STATUS_NONE:
            if ($voucherStatus === TbShippingVoucher::STATUS_WAIT_PICKING) {
              $result['statusName'] = 'ピッキング待ち';
            } elseif ($voucherStatus === TbShippingVoucher::STATUS_UNPROCESSED_PACKAGING) {
              $result['statusName'] = 'ピッキング完了';
            } else {
              $result['statusName'] = '梱包未着手';
            }
            $result['shippingStopPossibleFlg'] = true;
            break;
          case TbShippingVoucherPacking::STATUS_PROCESSING:
            $result['statusName'] = '梱包中';
            $result['remarks'] = '梱包中のため、出荷STOPは出来ません。';
            break;
          case TbShippingVoucherPacking::STATUS_OK:
            $result['statusName'] = '出荷待ち';
            $result['remarks'] = '出荷待ちのため、出荷STOPは出来ません。';
            break;
          case TbShippingVoucherPacking::STATUS_SHORTAGE:
            $result['statusName'] = '商品不足';
            $result['remarks'] = '商品不足で現在出荷が止まっています。 担当者にご連絡のうえ、NextEngineでキャンセル処理を実施してください。';
            break;
          case TbShippingVoucherPacking::STATUS_ON_HOLD:
            $result['statusName'] = '梱包中(保留)';
            $result['remarks'] = '梱包中のため、出荷STOPは出来ません。 ただし在庫確認中のため、商品不足により出荷されない場合があります。';
            break;
          case TbShippingVoucherPacking::STATUS_WAIT_SHIPPING_STOP:
            $result['statusName'] = '出荷STOP待';
            break;
          case TbShippingVoucherPacking::STATUS_SHIPPING_STOP:
            $result['statusName'] = '出荷STOP';
            break;
          }
      // 他社倉庫の場合
      } else {
        $result['statusName'] = $orderStatus;
        $result['remarks'] = '他社倉庫からの出荷のため、出荷STOP出来ません。';
      }

    } elseif ($orderStatus === TbSalesDetail::ORDER_STATUS_VALUE_FIX) {
      $result['remarks'] = '出荷が完了しています。';
    }

    return $result;
  }

  /**
   * 出荷リスト自動生成一覧取得。
   *
   * 出荷リスト自動生成のデータを返す。
   * 出荷リスト自動生成に紐づく納品書印刷待ち集計データの伝票番号も一緒に返す。
   * @param int $warehouseId 倉庫ID || null
   * @param int $status ステータス || null
   * @return array 出荷リスト自動生成と紐づく伝票番号の連想配列
   */
  public function findShippingVoucherAutoGenerateList($warehouseId, $status)
  {
    /** @var TbShippingVoucherAutoGenerateRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');

    $autoGenerateList = $repo->findShippingVoucherAutoGenerateList($warehouseId, $status);

    $vouchersPerPage = $this->divideCreatingShippingVoucherTargetNumberList();

    return array_map(function($autoGenerate) use($vouchersPerPage) {
      $autoGenerate['status'] = TbShippingVoucherAutoGenerate::STATUS_LIST[$autoGenerate['status']];
      $autoGenerate['created'] = date('H:i', strtotime($autoGenerate['created']));
      $autoGenerate['updated'] = date('H:i', strtotime($autoGenerate['updated']));
      $autoGenerate['voucherNumbers'] = $vouchersPerPage[
          "{$autoGenerate['companyId']}-{$autoGenerate['warehouseId']}-{$autoGenerate['deliveryMethod']}-{$autoGenerate['page']}"];
      return $autoGenerate;
    }, $autoGenerateList);
  }

  /**
   * 納品書印刷待ち集計データから、出荷伝票の生成対象である伝票番号取得。
   *
   * @return array 以下の5つのキーを持つ連想配列の配列。
   *    'companyId' => 会社ID
   *    'warehouseId' => 倉庫ID
   *    'deliveryMethod' => 発送方法
   *    'page' => 納品書印刷待ちのページ番号
   *    'voucherNumber' => 伝票番号
   */
  private function findCreatingShippingVoucherTargetNumberList()
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
      SELECT
        c.id AS companyId,
        r.warehouse_id AS warehouseId,
        r.発送方法 AS deliveryMethod,
        r.page,
        r.伝票番号 AS voucherNumber
      FROM tb_delivery_statement_detail_num_order_list_result r
      INNER JOIN tb_productchoiceitems pci
        ON (
          pci.ne_syohin_syohin_code = substr(
            r.対象商品コード
            , instr(r.対象商品コード, ':') + 1
            , char_length(r.対象商品コード)
          )
          OR (
            instr(r.対象商品コード, ':') = 0
            AND instr(r.対象商品コード, ',') >= 1
            AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード, ',') - 1)
          )
        )
      INNER JOIN tb_mainproducts m
        ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_company c
        ON m.company_code = c.code;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 納品書印刷待ち集計データから、ページごとの伝票番号リストを生成する。
   *
   * @return array 以下のキーを持つ連想配列。
   *    '会社ID-倉庫ID-発送方法-ページ番号' => 伝票番号の配列
   */
  private function divideCreatingShippingVoucherTargetNumberList() {
    $list = $this->findCreatingShippingVoucherTargetNumberList();
    $vouchersPerPage = [];
    foreach ($list as $data) {
      $key = "{$data['companyId']}-{$data['warehouseId']}-{$data['deliveryMethod']}-{$data['page']}";
      if (!array_key_exists($key, $vouchersPerPage)) {
        $vouchersPerPage[$key] = [];
      }
      $vouchersPerPage[$key][] = $data['voucherNumber'];
    }
    return $vouchersPerPage;
  }
  
  /** NextEngineからの受注明細情報取得の対象フィールド（納品書CSV用）。クレカ系と出荷日は使わないので除外、店舗名はないので別途取得 */
  private $RECEIVE_ORDER_FOR_RECEIPT_TARGET_FEILD = [
    'receive_order_shop_id' // 店舗コード
    , 'receive_order_id' // 伝票番号
    , 'receive_order_shop_cut_form_id' // 受注番号
    , 'receive_order_date' // 受注日
    , 'receive_order_import_date' // 取込日
    , 'receive_order_order_status_name' // 受注状態
    , 'receive_order_delivery_name' // 発送方法
    , 'receive_order_payment_method_name' // 支払方法
    , 'receive_order_total_amount' // 合計金額
    , 'receive_order_tax_amount' // 税金
    , 'receive_order_charge_amount' // 手数料
    , 'receive_order_delivery_fee_amount' // 送料
    , 'receive_order_other_amount' // その他
    , 'receive_order_point_amount' // ポイント
    , 'receive_order_credit_approval_amount' // 承認金額
    , 'receive_order_note' // 備考
    , 'receive_order_deposit_amount' // 入金金額
    , 'receive_order_deposit_type_name' // 入金区分
    , 'receive_order_deposit_date' // 入金日
    , 'receive_order_statement_delivery_instruct_printing_date' // 納品書印刷指示日
    , 'receive_order_statement_delivery_printing_date' // 納品書発行日
    , 'receive_order_statement_delivery_text' // 納品書備考
    , 'receive_order_send_plan_date' // 出荷予定日
    , 'receive_order_worker_text' // 作業者欄
    , 'receive_order_picking_instruct' // ピック指示内容
    , 'receive_order_label_print_date' // ラベル発行日
    , 'receive_order_hope_delivery_date' // 配送日
    , 'receive_order_hope_delivery_time_slot_name' // 配送時間帯
    , 'receive_order_delivery_cut_form_id' // 配送伝票番号
    , 'receive_order_purchaser_name' // 購入者名
    , 'receive_order_purchaser_kana' // 購入者カナ
    , 'receive_order_purchaser_zip_code' // 購入者郵便番号
    , 'receive_order_purchaser_address1' // 購入者住所1
    , 'receive_order_purchaser_address2' // 購入者住所2
    , 'receive_order_purchaser_tel' // 購入者電話番号
    , 'receive_order_purchaser_fax' // 購入者ＦＡＸ
    , 'receive_order_purchaser_mail_address' // 購入者メールアドレス
    , 'receive_order_consignee_name' // 発送先名
    , 'receive_order_consignee_kana' // 発送先カナ
    , 'receive_order_consignee_zip_code' // 発送先郵便番号
    , 'receive_order_consignee_address1' // 発送先住所1
    , 'receive_order_consignee_address2' // 発送先住所2
    , 'receive_order_consignee_tel' // 発送先電話番号
    , 'receive_order_consignee_fax' // 発送先ＦＡＸ
    , 'receive_order_delivery_cut_form_note' // 配送備考
    , 'receive_order_row_goods_id' // 商品コード
    , 'receive_order_row_goods_name' // 商品名
    , 'receive_order_row_quantity' // 受注数
    , 'receive_order_row_unit_price' // 商品単価
    , 'receive_order_row_wholesale_retail_ratio' // 掛率
    , 'receive_order_row_sub_total_price' // 小計
    , 'receive_order_row_goods_option' // 商品オプション
    , 'receive_order_row_cancel_flag' // キャンセル
    , 'receive_order_row_stock_allocation_quantity' // 引当数
    , 'receive_order_row_stock_allocation_date' // 引当日
    , 'receive_order_row_tax_rate' // 消費税率
    , 'receive_order_last_modified_null_safe_date' // 最終更新日
  ];
}
