<?php

namespace BatchBundle\MallProcess;

use MarketplaceWebService\MarketplaceWebService_Client;
use MarketplaceWebService\MarketplaceWebService_Interface;
use MarketplaceWebService\MarketplaceWebService_Mock;
use MarketplaceWebService\Model\MarketplaceWebService_Model_CancelReportRequestsRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportListRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportRequestListRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportRequestListResult;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportResponse;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportResult;
use MarketplaceWebService\Model\MarketplaceWebService_Model_IdList;
use MarketplaceWebService\Model\MarketplaceWebService_Model_ReportInfo;
use MarketplaceWebService\Model\MarketplaceWebService_Model_ReportRequestInfo;
use MarketplaceWebService\Model\MarketplaceWebService_Model_RequestReportRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_RequestReportResult;
use MarketplaceWebService\Model\MarketplaceWebService_Model_ResponseMetadata;
use MarketplaceWebService\Model\MarketplaceWebService_Model_SubmitFeedRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_UpdateReportAcknowledgementsRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_UpdateReportAcknowledgementsResult;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * モール別特殊処理 - Amazon
 */
class AmazonMallProcess extends BaseMallProcess
{

  const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

  const SHOP_NAME_VOGUE = 'vogue';
  const SHOP_NAME_US_PLUSNAO = 'us_plusnao';
  const SHOP_NAME_UPGRADE_PN = 'upgrade_pn';

  const FEED_TYPE_ITEM_LIST = '_POST_FLAT_FILE_LISTINGS_DATA_';
  const FEED_TYPE_ITEM_LIST_I = '_POST_FLAT_FILE_INVLOADER_DATA_';
  const FEED_TYPE_PRICE_AND_QUANTITY = '_POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA_';

  const RETRY_COUNT = 10;
  const RETRY_WAIT = 10;

  const REPORT_PROCESS_CHECK_SPAN = 30;
  const REPORT_PROCESS_CHECK_COUNT = 120; // 30秒 * 12回 で3600秒 = 60分

  const CSV_TYPE_BUSINESS_SESSION = 'business_session';
  const CSV_TYPE_SNL_STOCK = 'snl_stock';

  const SNL_ORDER_UNIT_NUM = 24;
  const CSV_NAME_SNL_NEXT_ENGINE_ORDER = 'next_engine.csv';
  const CSV_NAME_SNL_AMAZON_UPLOAD_ADD = 'amazon_add.txt';
  const CSV_NAME_SNL_AMAZON_UPLOAD_DELETE = 'amazon_delete.txt';


  /**
   * MWS 出品レポート ダウンロード処理
   * @param string $shop
   * @param string $outputPath
   * @throws \Exception
   */
  public function mwsGetStockReport($shop, $outputPath)
  {
    $logger = $this->getLogger();
    $logger->info('MWS 出品レポートダウンロード処理 開始');

    try {

      $logger->info('MWS 出品レポートダウンロード処理 レポート出力リクエスト 開始');

      $result = $this->requestReport($shop, '_GET_FLAT_FILE_OPEN_LISTINGS_DATA_');
      if (!$result || $result['status'] != 'ok') {
        $logger->error(print_r($result, true));
        throw new \RuntimeException('出品レポートのリクエストに失敗しました。' . print_r($result, true));
      }

      $logger->info('MWS 出品レポートダウンロード処理 レポート出力リクエスト 終了. ' . print_r($result, true));

      $logger->info('MWS 出品レポートダウンロード処理 レポート出力リクエスト ステータスチェック 開始');
      $count = 1;
      do {
        if (isset($reports)) {
          sleep(self::REPORT_PROCESS_CHECK_SPAN);
        }

        $reports = $this->getReportRequestList($shop, $result['ReportRequestId']);
        $target = null;
        foreach ($reports as $report) {
          if (
            isset($report['ReportProcessingStatus'])
            && $report['ReportProcessingStatus'] == '_DONE_'
            && isset($report['GeneratedReportId'])
            && strlen($report['GeneratedReportId'])
          ) {
            $target = $report;
            break;
          }
        }

        $logger->info('MWS 出品レポートダウンロード処理 レポート出力リクエスト ステータスチェック wait');

      } while (!$target && $count++ <= self::REPORT_PROCESS_CHECK_COUNT);

      $logger->info('MWS 出品レポートダウンロード処理 レポート出力リクエスト 終了. ' . print_r($target, true));

      if ($target) {
        $logger->info('MWS 出品レポートダウンロード処理 レポートダウンロード 開始');

        $this->mwsGetReport($shop, $target['GeneratedReportId'], $outputPath);

        $logger->info('MWS 出品レポートダウンロード処理 レポートダウンロード 終了');
      } else {
        throw new \RuntimeException('出品レポートが出力されませんでした。');
      }

      $logger->info('MWS 出品レポートダウンロード処理 終了');

    } catch (\Exception $e) {
      $logger->info('MWS 出品レポートダウンロード処理 エラー終了');
      throw $e;
    }
  }

  /**
   * MWS FBA 在庫管理レポート(非表示の商品を含む) ダウンロード処理
   * ※ UTF-8へ変更
   * @param string $shop
   * @param string $outputPath
   * @throws \Exception
   */
  public function mwsGetFBAStockReport($shop, $outputPath)
  {
    $logger = $this->getLogger();
    $logger->info('MWS FBAレポートダウンロード処理 開始');
    
    $result = [
        'status' => 'ok'
    ];

    try {

      $logger->info('MWS FBAレポートダウンロード処理 レポート出力リクエスト 開始');

      $result = $this->requestReport($shop, '_GET_FBA_MYI_ALL_INVENTORY_DATA_');
      if (!$result || $result['status'] != 'ok') {
        // $logger->error(print_r($result, true));

        if (isset($result['exception'])) {
          $logger->error($result['exception']->getMessage());

          $logger->error($result['exception']->getTraceAsString());
        }

        throw new \RuntimeException('出品レポートのリクエストに失敗しました。');
      }

      $logger->info('MWS FBAレポートダウンロード処理 レポート出力リクエスト 終了. ');

      $logger->info('MWS FBAレポートダウンロード処理 レポート出力リクエスト ステータスチェック 開始');
      $count = 1;
      do {
        if (isset($reports)) {
          sleep(self::REPORT_PROCESS_CHECK_SPAN);
        }

        $reports = $this->getReportRequestList($shop, $result['ReportRequestId']);
        $target = null;
        foreach ($reports as $report) {
          if (
            isset($report['ReportProcessingStatus'])
            && ($report['ReportProcessingStatus'] == '_DONE_')
            && isset($report['GeneratedReportId'])
            && strlen($report['GeneratedReportId'])
          ) {
            $target = $report;
            break;
          }

          // データ無し
          if ( isset($report['ReportProcessingStatus'])
            && $report['ReportProcessingStatus'] == '_DONE_NO_DATA_'
          ) {
            $target = $report;
            break;
          }

          // キャンセル
          if (
            isset($report['ReportProcessingStatus'])
            && $report['ReportProcessingStatus'] == '_CANCELLED_'
          ) {
            $target = $report;
            break;
          }

        }

        $logger->info('MWS FBAレポートダウンロード処理 レポート出力リクエスト ステータスチェック wait');

      } while (!$target && $count++ <= self::REPORT_PROCESS_CHECK_COUNT);

      $logger->info('MWS FBAレポートダウンロード処理 レポート出力リクエスト 終了. ');

      if ($target) {

        if ($target['ReportProcessingStatus'] == '_DONE_NO_DATA_') {
          $logger->info('MWS FBAレポートダウンロード処理 データがありません。レポートは作成されませんでした。');
        } else if ($target['ReportProcessingStatus'] == '_CANCELLED_') {
          $result = [
             'status' => 'ng'
            ,'message' => 'MWS FBAレポートダウンロード処理 レポート出力がキャンセルされました。'
          ];
          $logger->error('MWS FBAレポートダウンロード処理 レポート出力がキャンセルされました。');
        } else {
          $logger->info('MWS FBAレポートダウンロード処理 レポートダウンロード 開始');

          $tmpFilePath = tempnam('/tmp', 'tmp_amazon_fba_stock_');
          $this->mwsGetReport($shop, $target['GeneratedReportId'], $tmpFilePath);

          // 文字コード変換
          $fileUtil = $this->getFileUtil();
          $fp = fopen($outputPath, 'wb');
          $fromChar = $shop == AmazonMallProcess::SHOP_NAME_VOGUE ? 'SJIS-WIN' : 'UTF-8';
          $fileUtil->createConvertedCharsetTempFile($fp, $tmpFilePath, $fromChar, 'UTF-8'); // USの場合は不要だが同じ処理を通しておく
          fclose($fp);

          unlink($tmpFilePath);
          $logger->info('MWS FBAレポートダウンロード処理 レポートダウンロード 終了');
        }
      } else {
        throw new \RuntimeException('出品レポートが出力されませんでした。');
      }

      $logger->info('MWS FBAレポートダウンロード処理 終了');

    } catch (\Exception $e) {
      $logger->info('MWS FBAレポートダウンロード処理 エラー終了');
      throw $e;
    }
    
    return $result;
  }

  /**
   * 注文レポート
   * ※こちらはトラッキング用レポートであるため、購入者情報等は含まれない。
   *   モール受注CSV変換では、別途 _GET_FLAT_FILE_ORDERS_DATA_ のデータを取得する処理を実装
   * _GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_
   * ※ UTF-8へ変更
   * @param $shop
   * @param $outputPath
   * @param \DateTimeInterface $startDate
   * @param \DateTimeInterface $endDate
   * @throws \Exception
   */
  public function mwsGetOrderReport($shop, $outputPath, $startDate, $endDate)
  {
    $logger = $this->getLogger();
    $logger->info('MWS 注文レポートダウンロード処理 開始');

    try {

      $logger->info('MWS 注文レポートダウンロード処理 レポート出力リクエスト 開始');

      if (!$startDate || !$endDate) {
        throw new \RuntimeException('取得期間が指定されていません。');
      }

      $addParameters = [
          'StartDate' => $startDate->format('Y-m-d\\TH:i:s+0900')
        , 'EndDate' => $endDate->format('Y-m-d\\TH:i:s+0900')
      ];

      $logger->info(print_r($addParameters, true));

      $result = $this->requestReport($shop, '_GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_', $addParameters);
      if (!$result || $result['status'] != 'ok') {

        if (isset($result['exception'])) {
          $logger->error($result['exception']->getMessage());

          $logger->error($result['exception']->getTraceAsString());
        }

        throw new \RuntimeException('注文レポートのリクエストに失敗しました。');
      }

      $logger->info('MWS 注文レポートダウンロード処理 レポート出力リクエスト 終了. ');

      $logger->info('MWS 注文レポートダウンロード処理 レポート出力リクエスト ステータスチェック 開始');
      $count = 1;
      do {
        if (isset($reports)) {
          sleep(self::REPORT_PROCESS_CHECK_SPAN);
        }

        $reports = $this->getReportRequestList($shop, $result['ReportRequestId']);
        $target = null;
        foreach ($reports as $report) {
          if (
            isset($report['ReportProcessingStatus'])
            && $report['ReportProcessingStatus'] == '_DONE_'
            && isset($report['GeneratedReportId'])
            && strlen($report['GeneratedReportId'])
          ) {
            $target = $report;
            break;
          }
        }

        $logger->info('MWS 注文レポートダウンロード処理 レポート出力リクエスト ステータスチェック wait');

      } while (!$target && $count++ <= self::REPORT_PROCESS_CHECK_COUNT);

      $logger->info('MWS 注文レポートダウンロード処理 レポート出力リクエスト 終了. ');

      if ($target) {
        $logger->info('MWS 注文レポートダウンロード処理 レポートダウンロード 開始');

        $tmpFilePath = tempnam('/tmp', 'tmp_amazon_order_');
        $this->mwsGetReport($shop, $target['GeneratedReportId'], $tmpFilePath);

        // 文字コード変換
        $fileUtil = $this->getFileUtil();
        $fp = fopen($outputPath, 'wb');
        $fileUtil->createConvertedCharsetTempFile($fp, $tmpFilePath, 'SJIS-WIN', 'UTF-8');
        fclose($fp);

        unlink($tmpFilePath);

        $logger->info('MWS 注文レポートダウンロード処理 レポートダウンロード 終了');
      } else {
        throw new \RuntimeException('注文レポートが出力されませんでした。');
      }

      $logger->info('MWS 注文レポートダウンロード処理 終了');

    } catch (\Exception $e) {
      $logger->info('MWS 注文レポートダウンロード処理 エラー終了');
      throw $e;
    }
  }

  /// レポート取得
  public function mwsGetReport($shop, $reportId, $outputPath)
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    $parameters = array(
      'Merchant' => $account['merchant_id'],
      'Report' => fopen($outputPath, 'w+b'),
      'ReportId' => $reportId,
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );
    $request = new MarketplaceWebService_Model_GetReportRequest($parameters);
    try {
      ob_start();
      /** @var MarketplaceWebService_Model_GetReportResponse $response */
      $response = $service->getReport($request);
      $logger->info(ob_get_flush());

      $logger->info("Service Response\n");
      $logger->info("=============================================================================\n");

      $logger->info("        GetReportResponse\n");
      if ($response->isSetGetReportResult()) {
        /** @var MarketplaceWebService_Model_GetReportResult $getReportResult */
        $getReportResult = $response->getGetReportResult();
        $logger->info("            GetReport");

        if ($getReportResult->isSetContentMd5()) {
          $logger->info("                ContentMd5");
          $logger->info("                " . $getReportResult->getContentMd5() . "\n");
        }
      }
      if ($response->isSetResponseMetadata()) {
        $logger->info("            ResponseMetadata\n");
        /** @var MarketplaceWebService_Model_ResponseMetadata $responseMetadata */
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) {
          $logger->info("                RequestId\n");
          $logger->info("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      $logger->info("        Report Contents\n");
      $logger->info("  -- omitted --\n");
      $logger->info("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

      // md5チェック
      $md5 = base64_encode(md5(file_get_contents($outputPath), true));
      if (
        !isset($getReportResult)
        || !$getReportResult->isSetContentMd5()
        || $md5 !== $getReportResult->getContentMd5()
      ) {
        throw new \RuntimeException('invalid md5. download failed.');
      }

    } catch (\MarketplaceWebService\Exception $ex) {
      $logger->info("Caught Exception: " . $ex->getMessage() . "\n");
      $logger->info("Response Status Code: " . $ex->getStatusCode() . "\n");
      $logger->info("Error Code: " . $ex->getErrorCode() . "\n");
      $logger->info("Error Type: " . $ex->getErrorType() . "\n");
      $logger->info("Request ID: " . $ex->getRequestId() . "\n");
      $logger->info("XML: " . $ex->getXML() . "\n");
      $logger->info("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
    }

  }

  /// 02. レポート出力リクエスト一覧
  private function getReportRequestList($shop, $reportRequestId = null, $reportProcessingStatus = null)
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    $parameters = array(
      'Merchant' => $account['merchant_id'],
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    if ($reportRequestId) {
      $parameters['ReportRequestIdList'] = ['Id' => [$reportRequestId]];

      // IDが指定された場合には他の条件は全て無視されるため、else if.
    } else if ($reportProcessingStatus) {
      $parameters['ReportProcessingStatusList'] = ['Status' => [$reportProcessingStatus]];
    }

    $request = new MarketplaceWebService_Model_GetReportRequestListRequest($parameters);

    $results = [];

    try {
      ob_start();
      $response = $service->getReportRequestList($request);
      $logger->info(ob_get_flush());

      $logger->info("Service Response\n");
      $logger->info("=============================================================================\n");

      $logger->info("        GetReportRequestListResponse\n");
      if ($response->isSetGetReportRequestListResult()) {
        $logger->info("            GetReportRequestListResult\n");
        /** @var MarketplaceWebService_Model_GetReportRequestListResult $getReportRequestListResult */
        $getReportRequestListResult = $response->getGetReportRequestListResult();
        if ($getReportRequestListResult->isSetNextToken()) {
          $logger->info("                NextToken\n");
          $logger->info("                    " . $getReportRequestListResult->getNextToken() . "\n");
        }
        if ($getReportRequestListResult->isSetHasNext()) {
          $logger->info("                HasNext\n");
          $logger->info("                    " . $getReportRequestListResult->getHasNext() . "\n");
        }

        /** @var MarketplaceWebService_Model_ReportRequestInfo[] $reportRequestInfoList */
        $reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList();
        foreach ($reportRequestInfoList as $reportRequestInfo) {
          $item = [];

          $logger->info("                ReportRequestInfo\n");
          if ($reportRequestInfo->isSetReportRequestId()) {
            $logger->info("                    ReportRequestId\n");
            $logger->info("                        " . $reportRequestInfo->getReportRequestId() . "\n");
            $item['ReportRequestId'] = $reportRequestInfo->getReportRequestId();
          }
          if ($reportRequestInfo->isSetReportType()) {
            $logger->info("                    ReportType\n");
            $logger->info("                        " . $reportRequestInfo->getReportType() . "\n");
            $item['ReportType'] = $reportRequestInfo->getReportType();
          }
          if ($reportRequestInfo->isSetStartDate()) {
            $logger->info("                    StartDate\n");
            $logger->info("                        " . $reportRequestInfo->getStartDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetEndDate()) {
            $logger->info("                    EndDate\n");
            $logger->info("                        " . $reportRequestInfo->getEndDate()->format(self::DATE_FORMAT) . "\n");
          }
          // add start
          if ($reportRequestInfo->isSetScheduled()) {
            $logger->info("                    Scheduled\n");
            $logger->info("                        " . $reportRequestInfo->getScheduled() . "\n");
          }
          // add end
          if ($reportRequestInfo->isSetSubmittedDate()) {
            $logger->info("                    SubmittedDate\n");
            $logger->info("                        " . $reportRequestInfo->getSubmittedDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetReportProcessingStatus()) {
            $logger->info("                    ReportProcessingStatus\n");
            $logger->info("                        " . $reportRequestInfo->getReportProcessingStatus() . "\n");
            $item['ReportProcessingStatus'] = $reportRequestInfo->getReportProcessingStatus();
          }
          // add start
          if ($reportRequestInfo->isSetGeneratedReportId()) {
            $logger->info("                    GeneratedReportId\n");
            $logger->info("                        " . $reportRequestInfo->getGeneratedReportId() . "\n");
            $item['GeneratedReportId'] = $reportRequestInfo->getGeneratedReportId();
          }
          if ($reportRequestInfo->isSetStartedProcessingDate()) {
            $logger->info("                    StartedProcessingDate\n");
            $logger->info("                        " . $reportRequestInfo->getStartedProcessingDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetCompletedDate()) {
            $logger->info("                    CompletedDate\n");
            $logger->info("                        " . $reportRequestInfo->getCompletedDate()->format(self::DATE_FORMAT) . "\n");
          }
          // add end

          $results[] = $item;
        }
      }
      if ($response->isSetResponseMetadata()) {
        $logger->info("            ResponseMetadata\n");
        /** @var MarketplaceWebService_Model_ResponseMetadata $responseMetadata */
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) {
          $logger->info("                RequestId\n");
          $logger->info("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      $logger->info("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
    } catch (\MarketplaceWebService\Exception $ex) {
      $logger->info("Caught Exception: " . $ex->getMessage() . "\n");
      $logger->info("Response Status Code: " . $ex->getStatusCode() . "\n");
      $logger->info("Error Code: " . $ex->getErrorCode() . "\n");
      $logger->info("Error Type: " . $ex->getErrorType() . "\n");
      $logger->info("Request ID: " . $ex->getRequestId() . "\n");
      $logger->info("XML: " . $ex->getXML() . "\n");
      $logger->info("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
    }

    return $results;
  }


  /// 02'. 出力済みレポート一覧
  public function mwsGetReportList($shop, $addParameters = null)
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    $parameters = array(
        'Merchant' => $account['merchant_id']
      , 'AvailableToDate' => new \DateTime('now', new \DateTimeZone('UTC'))
      , 'AvailableFromDate' => new \DateTime('-1 days', new \DateTimeZone('UTC'))
      , 'Acknowledged' => false
    );

    if ($addParameters) {
      $parameters = array_merge($parameters, $addParameters);
    }

    $request = new MarketplaceWebService_Model_GetReportListRequest($parameters);

    $results = [];

    try {
      ob_start();
      $response = $service->getReportList($request);
      $logger->info(ob_get_flush());

      $logger->info("Service Response\n");
      $logger->info("=============================================================================\n");

      $logger->info("        GetReportListResponse\n");
      if ($response->isSetGetReportListResult()) {
        $logger->info("            GetReportListResult\n");
        $getReportListResult = $response->getGetReportListResult();
        if ($getReportListResult->isSetNextToken()) {
          $logger->info("                NextToken\n");
          $logger->info("                    " . $getReportListResult->getNextToken() . "\n");
        }
        if ($getReportListResult->isSetHasNext()) {
          $logger->info("                HasNext\n");
          $logger->info("                    " . $getReportListResult->getHasNext() . "\n");
        }
        $reportInfoList = $getReportListResult->getReportInfoList();
        foreach ($reportInfoList as $reportInfo) {
          $item = [];

          $logger->info("                ReportInfo\n");
          if ($reportInfo->isSetReportId()) {
            $logger->info("                    ReportId\n");
            $logger->info("                        " . $reportInfo->getReportId() . "\n");

            $item['ReportId'] = $reportInfo->getReportId();
          }
          if ($reportInfo->isSetReportType()) {
            $logger->info("                    ReportType\n");
            $logger->info("                        " . $reportInfo->getReportType() . "\n");

            $item['ReportType'] = $reportInfo->getReportType();
          }
          if ($reportInfo->isSetReportRequestId()) {
            $logger->info("                    ReportRequestId\n");
            $logger->info("                        " . $reportInfo->getReportRequestId() . "\n");

            $item['ReportRequestId'] = $reportInfo->getReportRequestId();
          }
          if ($reportInfo->isSetAvailableDate()) {
            $logger->info("                    AvailableDate\n");
            $logger->info("                        " . $reportInfo->getAvailableDate()->format(self::DATE_FORMAT) . "\n");

            $item['AvailableDate'] = $reportInfo->getAvailableDate();
            $item['AvailableDate']->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
          }
          if ($reportInfo->isSetAcknowledged()) {
            $logger->info("                    Acknowledged\n");
            $logger->info("                        " . $reportInfo->getAcknowledged() . "\n");

            $item['Acknowledged'] = $reportInfo->getAcknowledged();
          }
          if ($reportInfo->isSetAcknowledgedDate()) {
            $logger->info("                    AcknowledgedDate\n");
            $logger->info("                        " . $reportInfo->getAcknowledgedDate()->format(self::DATE_FORMAT) . "\n");

            $item['AcknowledgedDate'] = $reportInfo->getAcknowledgedDate();
            $item['AcknowledgedDate']->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
          }

          $results[] = $item;
        }
      }
      if ($response->isSetResponseMetadata()) {
        $logger->info("            ResponseMetadata\n");
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) {
          $logger->info("                RequestId\n");
          $logger->info("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      $logger->info("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
    } catch (MarketplaceWebService_Exception $ex) {
      $logger->info("Caught Exception: " . $ex->getMessage() . "\n");
      $logger->info("Response Status Code: " . $ex->getStatusCode() . "\n");
      $logger->info("Error Code: " . $ex->getErrorCode() . "\n");
      $logger->info("Error Type: " . $ex->getErrorType() . "\n");
      $logger->info("Request ID: " . $ex->getRequestId() . "\n");
      $logger->info("XML: " . $ex->getXML() . "\n");
      $logger->info("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
    }

    return $results;
  }

  /// レポート出力リクエスト
  // 'ReportType'         => '_GET_MERCHANT_LISTINGS_DATA_',  // 出品詳細レポート
  private function requestReport($shop, $reportType = '_GET_FLAT_FILE_OPEN_LISTINGS_DATA_', $addParameters = [])
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    $marketplaceIdArray = array("Id" => array($account['market_place_id']));

    $parameters = array(
      'Merchant' => $account['merchant_id'],
      'MarketplaceIdList' => $marketplaceIdArray,
      'ReportType' => $reportType,
      'ReportOptions' => 'ShowSalesChannel=true',
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    if ($addParameters) {
      $parameters = array_merge($parameters, $addParameters);
    }

    $request = new MarketplaceWebService_Model_RequestReportRequest($parameters);

    $result = null;
    try {
      ob_start();
      $response = $service->requestReport($request);
      $logger->info(ob_get_flush());

      $logger->info("Service Response\n");
      $logger->info("=============================================================================\n");

      $logger->info("        RequestReportResponse\n");
      if ($response->isSetRequestReportResult()) {
        $logger->info("            RequestReportResult\n");
        /** @var MarketplaceWebService_Model_RequestReportResult $requestReportResult */
        $requestReportResult = $response->getRequestReportResult();

        if ($requestReportResult->isSetReportRequestInfo()) {

          /** @var MarketplaceWebService_Model_ReportRequestInfo $reportRequestInfo */
          $reportRequestInfo = $requestReportResult->getReportRequestInfo();
          $logger->info("                ReportRequestInfo\n");
          if ($reportRequestInfo->isSetReportRequestId()) {
            $logger->info("                    ReportRequestId\n");
            $logger->info("                        " . $reportRequestInfo->getReportRequestId() . "\n");

            $result = [
              'status' => 'ok'
              , 'ReportRequestId' => $reportRequestInfo->getReportRequestId()
            ];
          }
          if ($reportRequestInfo->isSetReportType()) {
            $logger->info("                    ReportType\n");
            $logger->info("                        " . $reportRequestInfo->getReportType() . "\n");
            $result['ReportType'] = $reportRequestInfo->getReportType();
          }
          if ($reportRequestInfo->isSetStartDate()) {
            $logger->info("                    StartDate\n");
            $logger->info("                        " . $reportRequestInfo->getStartDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetEndDate()) {
            $logger->info("                    EndDate\n");
            $logger->info("                        " . $reportRequestInfo->getEndDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetSubmittedDate()) {
            $logger->info("                    SubmittedDate\n");
            $logger->info("                        " . $reportRequestInfo->getSubmittedDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetReportProcessingStatus()) {
            $logger->info("                    ReportProcessingStatus\n");
            $logger->info("                        " . $reportRequestInfo->getReportProcessingStatus() . "\n");
          }
        }
      }
      if ($response->isSetResponseMetadata()) {
        $logger->info("            ResponseMetadata\n");
        /** @var MarketplaceWebService_Model_ResponseMetadata $responseMetadata */
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) {
          $logger->info("                RequestId\n");
          $logger->info("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      $logger->info("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

      if (!$result) {
        throw new \RuntimeException('no result data!');
      }

    } catch (\MarketplaceWebService\Exception $ex) {
      $logger->info("Caught Exception: " . $ex->getMessage() . "\n");
      $logger->info("Response Status Code: " . $ex->getStatusCode() . "\n");
      $logger->info("Error Code: " . $ex->getErrorCode() . "\n");
      $logger->info("Error Type: " . $ex->getErrorType() . "\n");
      $logger->info("Request ID: " . $ex->getRequestId() . "\n");
      $logger->info("XML: " . $ex->getXML() . "\n");
      $logger->info("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");

      $result = [
        'status' => 'ng'
        , 'exception' => $ex
      ];

    } catch (\Exception $e) {
      $result = [
        'status' => 'ng'
        , 'exception' => $e
      ];
    }

    return $result;
  }

  /// レポート確認ステータス更新 UpdateReportAcknowledgements
  /**
   * @param $shop
   * @param int|array $reportIds
   * @param bool $acknowledged
   * @return array
   */
  public function mwsUpdateReportAcknowledgements($shop, $reportIds, $acknowledged = true)
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    $parameters = array(
      'Merchant' => $account['merchant_id']
    , 'Acknowledged' => $acknowledged
    );

    $request = new MarketplaceWebService_Model_UpdateReportAcknowledgementsRequest($parameters);
    $idList = new MarketplaceWebService_Model_IdList();
    $idList->setId($reportIds);
    $request->setReportIdList($idList);

    $results = [];

    try {
      ob_start();
      $response = $service->updateReportAcknowledgements($request);
      $logger->info(ob_get_flush());

      $logger->info("Service Response\n");
      $logger->info("=============================================================================\n");

      $logger->info("        UpdateReportAcknowledgementsResponse\n");
      if ($response->isSetUpdateReportAcknowledgementsResult()) {
        $logger->info("            UpdateReportAcknowledgementsResult\n");
        /** @var MarketplaceWebService_Model_UpdateReportAcknowledgementsResult $updateReportAcknowledgementsResult */
        $updateReportAcknowledgementsResult = $response->getUpdateReportAcknowledgementsResult();
        if ($updateReportAcknowledgementsResult->isSetCount())
        {
          $logger->info("                Count\n");
          $logger->info("                    " . $updateReportAcknowledgementsResult->getCount() . "\n");
        }
        /** @var MarketplaceWebService_Model_ReportInfo[] $reportInfoList */
        $reportInfoList = $updateReportAcknowledgementsResult->getReportInfoList();
        foreach ($reportInfoList as $reportInfo) {
          $logger->info("                ReportInfo\n");
          if ($reportInfo->isSetReportId())
          {
            $logger->info("                    ReportId\n");
            $logger->info("                        " . $reportInfo->getReportId() . "\n");
          }
          if ($reportInfo->isSetReportType())
          {
            $logger->info("                    ReportType\n");
            $logger->info("                        " . $reportInfo->getReportType() . "\n");
          }
          if ($reportInfo->isSetReportRequestId())
          {
            $logger->info("                    ReportRequestId\n");
            $logger->info("                        " . $reportInfo->getReportRequestId() . "\n");
          }
          if ($reportInfo->isSetAvailableDate())
          {
            $logger->info("                    AvailableDate\n");
            $logger->info("                        " . $reportInfo->getAvailableDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($reportInfo->isSetAcknowledged())
          {
            $logger->info("                    Acknowledged\n");
            $logger->info("                        " . $reportInfo->getAcknowledged() . "\n");
          }
          if ($reportInfo->isSetAcknowledgedDate())
          {
            $logger->info("                    AcknowledgedDate\n");
            $logger->info("                        " . $reportInfo->getAcknowledgedDate()->format(self::DATE_FORMAT) . "\n");
          }
        }
      }
      if ($response->isSetResponseMetadata()) {
        $logger->info("            ResponseMetadata\n");
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId())
        {
          $logger->info("                RequestId\n");
          $logger->info("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      $logger->info("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

    } catch (\MarketplaceWebService\Exception $ex) {
      $logger->info("Caught Exception: " . $ex->getMessage() . "\n");
      $logger->info("Response Status Code: " . $ex->getStatusCode() . "\n");
      $logger->info("Error Code: " . $ex->getErrorCode() . "\n");
      $logger->info("Error Type: " . $ex->getErrorType() . "\n");
      $logger->info("Request ID: " . $ex->getRequestId() . "\n");
      $logger->info("XML: " . $ex->getXML() . "\n");
      $logger->info("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
    }

    return $results;
  }



  /**
   * キャンセル
   */
  private function cancelReportRequest($shop, $reportRequestId)
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    $parameters = array(
      'Merchant' => $account['merchant_id'],
      'ReportRequestIdList' => array('Id' => array($reportRequestId)),
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    $request = new MarketplaceWebService_Model_CancelReportRequestsRequest($parameters);

    try {
      ob_start();
      $response = $service->cancelReportRequests($request);
      $logger->info(ob_get_flush());

      $logger->info("Service Response\n");
      $logger->info("=============================================================================\n");

      $logger->info("        CancelReportRequestsResponse\n");
      if ($response->isSetCancelReportRequestsResult()) {
        $logger->info("            CancelReportRequestsResult\n");
        $cancelReportRequestsResult = $response->getCancelReportRequestsResult();
        if ($cancelReportRequestsResult->isSetCount()) {
          $logger->info("                Count\n");
          $logger->info("                    " . $cancelReportRequestsResult->getCount() . "\n");
        }
        $reportRequestInfoList = $cancelReportRequestsResult->getReportRequestInfoList();
        foreach ($reportRequestInfoList as $reportRequestInfo) {
          $logger->info("                ReportRequestInfo\n");
          if ($reportRequestInfo->isSetReportRequestId()) {
            $logger->info("                    ReportRequestId\n");
            $logger->info("                        " . $reportRequestInfo->getReportRequestId() . "\n");
          }
          if ($reportRequestInfo->isSetReportType()) {
            $logger->info("                    ReportType\n");
            $logger->info("                        " . $reportRequestInfo->getReportType() . "\n");
          }
          if ($reportRequestInfo->isSetStartDate()) {
            $logger->info("                    StartDate\n");
            $logger->info("                        " . $reportRequestInfo->getStartDate()->format(DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetEndDate()) {
            $logger->info("                    EndDate\n");
            $logger->info("                        " . $reportRequestInfo->getEndDate()->format(DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetSubmittedDate()) {
            $logger->info("                    SubmittedDate\n");
            $logger->info("                        " . $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
          }
          if ($reportRequestInfo->isSetReportProcessingStatus()) {
            $logger->info("                    ReportProcessingStatus\n");
            $logger->info("                        " . $reportRequestInfo->getReportProcessingStatus() . "\n");
          }
        }
      }
      if ($response->isSetResponseMetadata()) {
        $logger->info("            ResponseMetadata\n");
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) {
          $logger->info("                RequestId\n");
          $logger->info("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      $logger->info("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
    } catch (\MarketplaceWebService\Exception $ex) {
      $logger->info("Caught Exception: " . $ex->getMessage() . "\n");
      $logger->info("Response Status Code: " . $ex->getStatusCode() . "\n");
      $logger->info("Error Code: " . $ex->getErrorCode() . "\n");
      $logger->info("Error Type: " . $ex->getErrorType() . "\n");
      $logger->info("Request ID: " . $ex->getRequestId() . "\n");
      $logger->info("XML: " . $ex->getXML() . "\n");
      $logger->info("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
    }

  }

  /**
   * フィード送信処理
   * @param $shop
   * @param $feedFilePath
   * @param $feedType
   */
  public function submitFeeds($shop, $feedFilePath, $feedType = self::FEED_TYPE_ITEM_LIST)
  {
    $logger = $this->getLogger();
    $account = $this->getMwsAccount($shop);
    $service = $this->getMwsService($account);

    // 本番環境以外はスキップ
    if ($this->getEnvironment() !== 'prod') {
      $logger->info('Amazon アップロード処理 ... テスト環境につきスキップ');
      return;
    }

    $marketplaceIdArray = array("Id" => array($account['market_place_id']));

    $feedHandle = fopen($feedFilePath, 'rb');
    $parameters = array(
      'Merchant' => $account['merchant_id'],
      'MarketplaceIdList' => $marketplaceIdArray,
      'FeedType' => $feedType,
      'FeedContent' => $feedHandle,
      'PurgeAndReplace' => false,
      'ContentMd5' => base64_encode(md5(file_get_contents($feedFilePath), true)),
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    $request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);

    try {
      $response = $service->submitFeed($request);

      echo("Service Response\n");
      echo("=============================================================================\n");

      echo("        SubmitFeedResponse\n");
      if ($response->isSetSubmitFeedResult()) {
        echo("            SubmitFeedResult\n");
        $submitFeedResult = $response->getSubmitFeedResult();
        if ($submitFeedResult->isSetFeedSubmissionInfo()) {
          echo("                FeedSubmissionInfo\n");
          $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
          if ($feedSubmissionInfo->isSetFeedSubmissionId()) {
            echo("                    FeedSubmissionId\n");
            echo("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n");
          }
          if ($feedSubmissionInfo->isSetFeedType()) {
            echo("                    FeedType\n");
            echo("                        " . $feedSubmissionInfo->getFeedType() . "\n");
          }
          if ($feedSubmissionInfo->isSetSubmittedDate()) {
            echo("                    SubmittedDate\n");
            echo("                        " . $feedSubmissionInfo->getSubmittedDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($feedSubmissionInfo->isSetFeedProcessingStatus()) {
            echo("                    FeedProcessingStatus\n");
            echo("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
          }
          if ($feedSubmissionInfo->isSetStartedProcessingDate()) {
            echo("                    StartedProcessingDate\n");
            echo("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format(self::DATE_FORMAT) . "\n");
          }
          if ($feedSubmissionInfo->isSetCompletedProcessingDate()) {
            echo("                    CompletedProcessingDate\n");
            echo("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format(self::DATE_FORMAT) . "\n");
          }
        }
      }
      if ($response->isSetResponseMetadata()) {
        echo("            ResponseMetadata\n");
        $responseMetadata = $response->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) {
          echo("                RequestId\n");
          echo("                    " . $responseMetadata->getRequestId() . "\n");
        }
      }

      echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
    } catch (MarketplaceWebService_Exception $ex) {
      echo("Caught Exception: " . $ex->getMessage() . "\n");
      echo("Response Status Code: " . $ex->getStatusCode() . "\n");
      echo("Error Code: " . $ex->getErrorCode() . "\n");
      echo("Error Type: " . $ex->getErrorType() . "\n");
      echo("Request ID: " . $ex->getRequestId() . "\n");
      echo("XML: " . $ex->getXML() . "\n");
      echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
    }

    fclose($feedHandle);
  }


  /**
   * @return MarketplaceWebService_Interface
   */
  private function getMwsService($account)
  {
    if ($this->getEnvironment() === 'prod') {
      $config = array(
        'ServiceURL' => $account['service_url'],
        'ProxyHost' => null,
        'ProxyPort' => -1,
        'MaxErrorRetry' => 10,
      );

      $service = new MarketplaceWebService_Client(
        $account['aws_access_key_id'],
        $account['aws_secret_access_key'],
        $config,
        $account['application_name'],
        $account['application_version']);

    } else {
      $service = new MarketplaceWebService_Mock();
    }

    return $service;
  }

  /**
   * MWS アクセス設定取得
   * @param $shop
   * @return array
   */
  public function getMwsAccount($shop)
  {
    $result = null;
    $config = $this->getContainer()->getParameter('amazon_mws_account');

    if (is_array($config) && isset($config[$shop])) {
      $result = $config[$shop];
    }

    return $result;
  }

  // ------------------------------------
  // データ更新
  // ------------------------------------
  /**
   * Amazon商品在庫情報 FBA在庫更新処理
   * @param string $shop
   * @return 結果ステータスの配列。中断した場合は $result['status'] => 'ng' を返却する。
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  public function updateFbaProductStock($shop)
  {
    $result = [
        'status' => 'ok'
    ];
    $dbMain = $this->getDb('main');

    $filePath = sprintf('%s/%s/Import/%s_fba_stock.txt', $this->getFileUtil()->getWebCsvDir(), $this->getCsvDirName($shop), (new \DateTime())->format('YmdHis'));
    $getReportResult = $this->mwsGetFBAStockReport($shop, $filePath);

    $fs = new FileSystem();
    if (!$fs->exists($filePath)) {
      $this->getLogger()->info('FBA在庫レポートがダウンロードされませんでした。FBA在庫更新処理を終了します。');
      $result['status'] = 'ng';
      $result['message'] = $getReportResult['message'];
      return $result;
    }

    // 一時テーブルへインポート
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_amazon_fba_stock_report");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_work_amazon_fba_stock_report (
          `sku` VARCHAR(50) NOT NULL DEFAULT 0 PRIMARY KEY
        , `fnsku` VARCHAR(50) NOT NULL DEFAULT 0
        , `asin` VARCHAR(20) NOT NULL DEFAULT 0
        , `product-name` VARCHAR(255) NOT NULL DEFAULT 0
        , `condition` VARCHAR(20) NOT NULL DEFAULT 0
        , `your-price` INTEGER NOT NULL DEFAULT 0
        , `mfn-listing-exists` VARCHAR(10) NOT NULL DEFAULT 0
        , `mfn-fulfillable-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-listing-exists` VARCHAR(10) NOT NULL DEFAULT 0
        , `afn-warehouse-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-fulfillable-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-unsellable-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-reserved-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-total-quantity` INTEGER NOT NULL DEFAULT 0
        , `per-unit-volume` DECIMAL(10, 2) NOT NULL DEFAULT 0
        , `afn-inbound-working-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-inbound-shipped-quantity` INTEGER NOT NULL DEFAULT 0
        , `afn-inbound-receiving-quantity` INTEGER NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET utf8
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :importPath
      IGNORE INTO TABLE tmp_work_amazon_fba_stock_report
      FIELDS TERMINATED BY '\\t' ENCLOSED BY '' ESCAPED BY '"'
      LINES TERMINATED BY '\\n'
      IGNORE 1 LINES
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $filePath, \PDO::PARAM_STR);
    $stmt->execute();

    $stockTableName = $this->getProductStockTableName($shop);

    // 値のリセット
    $sql = <<<EOD
      UPDATE `{$stockTableName}` s
      SET s.fba_quantity_total = 0
        , s.fba_quantity_fulfillable = 0
      WHERE s.fba_quantity_total <> 0
         OR s.fba_quantity_fulfillable <> 0
EOD;
    $dbMain->query($sql);

    // 更新
    $sql = <<<EOD
      UPDATE `{$stockTableName}` s
      INNER JOIN tmp_work_amazon_fba_stock_report t ON s.sku = t.sku
      SET s.fba_quantity_total = t.`afn-total-quantity`
        , s.fba_quantity_fulfillable = t.`afn-fulfillable-quantity`
      WHERE t.`afn-total-quantity` <> 0
         OR t.`afn-fulfillable-quantity` <> 0
EOD;
    $dbMain->query($sql);

    // FBA登録のASINを更新
    // ※一度登録し、削除後に同一SKUで商品再登録を行った場合にも、FBAには旧ASINコードが残り続ける。
    //   これによりFBAへの納品や出荷元切り替えがエラーとなるため、常にFBAのASINコードに合わせて商品登録を行う。
    //   そのために、ここで取得したASINを保存しておく。（通常商品としては削除済みのものも含まれるためINSERTで。）
    $sql = <<<EOD
      INSERT INTO `{$stockTableName}` (
          `sku`
        , `fba_asin`
      )
      SELECT
          `sku`
        , `asin`
      FROM tmp_work_amazon_fba_stock_report
      ON DUPLICATE KEY UPDATE
        `fba_asin` = VALUES(`asin`)
EOD;
    $dbMain->query($sql);

    return $result;
  }

  /**
   * Amazon商品在庫情報 FBA仮想倉庫 在庫更新処理
   * @param string $shop
   * @param SymfonyUsers|null $account
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  public function updateFbaMultiProductLocation($shop, $account = null)
  {
    //$locationCode = 'FBA_MULTI_CHANNEL';
    $locationCode = 'FBA-AUTO';
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $fbaMultiEnabled = $commonUtil->getSettingValue('FBA_MULTI_ENABLED');
    if ($fbaMultiEnabled == 0) {
      $this->getLogger()->info('各種設定 FBA_MULTI_ENABLED によりFBA仮想倉庫のロケーション更新はスキップ');
      return;
    }

    $dbMain->beginTransaction();

    /** @var TbLocationRepository $repoLocation */
    $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

    // （履歴用）アクションキー 作成＆セット
    $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbWarehouse $warehouse */
    $warehouse = $repoWarehouse->find(TbWarehouseRepository::FBA_MULTI_WAREHOUSE_ID); // Amazon FBA仮想倉庫
    if (!$warehouse) {
      throw new \RuntimeException('no FBA warehosue.');
    }

    $location = $repoLocation->getByLocationCode($warehouse->getId(), $locationCode);
    
    if(!empty($location)){
         // 値のリセット（FBA仮想倉庫 在庫全削除）
         $sql = <<<EOD
           DELETE pl
           FROM tb_product_location pl
           WHERE pl.location_id = :location_id
EOD;
         $stmt = $dbMain->prepare($sql);
         $stmt->bindValue(':location_id', $location->getId(), \PDO::PARAM_INT);
         $stmt->execute();

         $sql = <<<EOD
           DELETE l
           FROM tb_location l
           WHERE l.id = :location_id
EOD;
         $stmt = $dbMain->prepare($sql);
         $stmt->bindValue(':location_id', $location->getId(), \PDO::PARAM_INT);
         $stmt->execute();
    }

    // 新規ロケーション作成
    $location = $repoLocation->createNewLocation($warehouse->getId(), $locationCode, 'FBA');

    // FBA出荷商品のみが倉庫に在庫として入る。
    // productchoiceitems に存在するデータのみをINSERT する。
    // このとき、相互トリガによりエラーとなるため、一時テーブルを経由する。
    // 一時テーブルへインポート
    // fba_multi_flagの参照は削除
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_amazon_fba_multi_location_products");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_work_amazon_fba_multi_location_products (
          ne_syohin_syohin_code VARCHAR(50) NOT NULL DEFAULT 0 PRIMARY KEY
        , stock INTEGER NOT NULL DEFAULT 0
        , position INTEGER NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET utf8
      SELECT
          s.sku AS ne_syohin_syohin_code
        , s.fba_quantity_fulfillable AS stock
        , 999999 AS position
      FROM tb_amazon_product_stock s
      INNER JOIN tb_productchoiceitems pci ON s.sku = pci.ne_syohin_syohin_code
      WHERE s.fba_quantity_fulfillable > 0
      ORDER BY s.sku
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // 挿入
    $sql = <<<EOD
      INSERT INTO tb_product_location (
          location_id
        , ne_syohin_syohin_code
        , stock
        , position
      )
      SELECT
          :locationId AS location_id
        , ne_syohin_syohin_code
        , stock
        , position
      FROM tmp_work_amazon_fba_multi_location_products
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':locationId', $location->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // ロケーション変更履歴 保存
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_FBA_MULTI_UPDATE_LOCATION, ($account ? $account->getUsername(): 'BatchSV02'), $actionKey);

    $dbMain->commit();
  }

  /**
   * Amazon注文情報 更新処理
   * @param string $shop
   * @param \DateTimeInterface|null $startDate
   * @param \DateTimeInterface|null $endDate
   * @throws \Exception
   */
  public function updateOrder($shop, $startDate = null, $endDate = null)
  {
    $dbMain = $this->getDb('main');

    // 日付指定
    if (!$endDate) {
      $endDate = new \DateTime();
      $endDate->modify('-1 seconds');
      $endDate->setTime(0, 0, 0);
    }

    if (!$startDate) {
      $startDate = new \DateTime();
      $startDate->modify('-1 months');
      $startDate->setTime(0, 0, 0);
    }

    $filePath = sprintf('%s/Amazon/Import/%s_order_recent.txt', $this->getFileUtil()->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
    $this->mwsGetOrderReport($shop, $filePath, $startDate, $endDate);

    $fs = new FileSystem();
    if (!$fs->exists($filePath)) {
      throw new \RuntimeException('注文レポートがダウンロードされませんでした。');
    }
    $dbMain->query("TRUNCATE tb_amazon_order_recent");

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :importPath
      INTO TABLE tb_amazon_order_recent
      FIELDS TERMINATED BY '\\t' ENCLOSED BY '' ESCAPED BY '"'
      LINES TERMINATED BY '\\n'
      IGNORE 1 LINES
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $filePath, \PDO::PARAM_STR);
    $stmt->execute();

  }

  // ------------------------------------
  // CSV出力関連
  // ------------------------------------
  /**
   * Amazon CSVデータヘッダ行作成
   * @param $headerDescription
   * @param $headers
   * @param string $target
   * @return string
   */
  public function createCsvHeaderLines($headerDescription, $headers, $target = 'jp')
  {
    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $headerLines = '';
    // description
    $headerLines .= $headerDescription . "\r\n";
    // label
    $headerLines .= $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], "\t") . "\r\n";
    // fieldName
    $headerLines .= $stringUtil->convertArrayToCsvLine(array_keys($headers), [], [], "\t") . "\r\n";

    if ($target == 'jp') {
      $headerLines = mb_convert_encoding($headerLines, 'SJIS-WIN', 'UTF-8');
    }

    return $headerLines;
  }


  /**
   * Amazon FBA出荷用CSVファイル ディレクトリ
   */
  public function getFbaOrderCsvDir()
  {
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');
    $dir = $fileUtil->getWebCsvDir() . '/Amazon/FBA';
    return $dir;
  }

  /**
   * Amazon FBA出荷用CSVファイル名プリフィクス
   */
  public function getFbaOrderCsvPrefix()
  {
    return 'amazon_fba_order';
  }


  /**
   * Amazon FBA出荷用CSV ファイル一覧取得
   * @return SplFileInfo[]
   */
  public function getLastAmazonFbaOrderCsvFile()
  {
    // ファイル一覧取得
    $dir = $this->getFbaOrderCsvDir();

    $finder = new Finder();
    $files = $finder->in($dir)->name('/' . $this->getFbaOrderCsvPrefix() . '.*\.csv/')->files()->sort(
      function ($a, $b) {
        /**
         * @var \SplFileInfo $a
         * @var \SplFileInfo $b
         */
        return ($b->getMTime() - $a->getMTime());
      }
    );

    // 取得ファイル数だけ取得
    $results = [];
    $i = 0;
    foreach ($files as $file) {
      $results[] = $file;
      $i++;
      if ($i > 10) {
        break;
      }
    }

    return $results;
  }


  /**
   * Amazon S&L出荷用CSVファイル ディレクトリ
   */
  public function getSnlOrderCsvDir()
  {
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');
    $dir = $fileUtil->getWebCsvDir() . '/Amazon/SNL';
    return $dir;
  }

  /**
   * S&L出荷用CSV ファイル一覧取得
   * @return SplFileInfo[]
   */
  public function getLastSnlOrderCsvFile()
  {
    // ファイル一覧取得
    $csvDir = $this->getSnlOrderCsvDir();

    $finder = new Finder();
    $dirs = $finder->in($csvDir)->directories()->sort(
      function ($a, $b) {
        /**
         * @var \SplFileInfo $a
         * @var \SplFileInfo $b
         */
        return ($b->getMTime() - $a->getMTime());
      }
    );

    // 取得ファイル数だけ取得
    $results = [];
    $i = 0;

    $fs = new FileSystem();
    /** @var SplFileInfo $dir */
    foreach($dirs as $dir) {
      $dirName = $dir->getBasename();
      $results[$dirName] = [
          'ne' => null
        , 'amazon_add' => null
        , 'amazon_delete' => null
      ];

      $nextEnginePath = sprintf('%s/%s', $dir->getPathname(), self::CSV_NAME_SNL_NEXT_ENGINE_ORDER);
      if ($fs->exists($nextEnginePath)) {
        $results[$dirName]['ne'] = $nextEnginePath;
      }
      $amazonAddPath = sprintf('%s/%s', $dir->getPathname(), self::CSV_NAME_SNL_AMAZON_UPLOAD_ADD);
      if ($fs->exists($amazonAddPath)) {
        $results[$dirName]['amazon_add'] = $amazonAddPath;
      }
      $amazonDeletePath = sprintf('%s/%s', $dir->getPathname(), self::CSV_NAME_SNL_AMAZON_UPLOAD_DELETE);
      if ($fs->exists($amazonDeletePath)) {
        $results[$dirName]['amazon_delete'] = $amazonDeletePath;
      }

      if (++$i >= 10) {
        break;
      }
    }

    return $results;
  }




  /**
   * アップロードされたファイルを全てをUTF-8へ変換し、種類別にファイルを仕分け
   * @param UploadedFile[] $files
   * @return array
   */
  public function processUploadedCsvFiles($files)
  {
    $logger = $this->getLogger();

    $result = [];
    $logger->info('件数 : ' . count($files));

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();

    $fs = new Filesystem();
    $uploadDir = sprintf('%s/Amazon/Import/%s', $fileUtil->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
    if (!$fs->exists($uploadDir)) {
      $fs->mkdir($uploadDir, 0755);
    }

    foreach($files as $file) {
      $logger->info('uploaded : ' . print_r($file->getPathname(), true));

      try {
        // 2行目（最初のデータ）で文字コード判定 ＆ UTF-8変換
        $fp = fopen($file->getPathname(), 'rb');
        fgets($fp); // 先頭行を捨てる
        $secondLine = fgets($fp);
        fclose($fp);
        if (!$secondLine) { // 2行目がなければ処理不要
          continue;
        }
        $charset = mb_detect_encoding($secondLine, ['SJIS-WIN', 'UTF-8']);
        $logger->info(sprintf('%s : %s', $file->getClientOriginalName(), $charset));
        if (!$charset) {
          throw new \RuntimeException(sprintf('CSVファイルの文字コードが判定できませんでした。[%s]', $file->getClientOriginalName()));
        }

        $newFilePath = tempnam($uploadDir, 'amazon_utf_');
        chmod($newFilePath, 0666);
        $fp = fopen($newFilePath, 'wb');
        $fileUtil->createConvertedCharsetTempFile($fp, $file->getPathname(), $charset, 'UTF-8', function($line) {
          // BOMがついていれば　問答無用で削除
          $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
          return $line;
        });
        fclose($fp);
        $newFile = new File($newFilePath);
        $csvType = $this->guessCsvTypeByCsvHeader($newFile);
        // TAB区切りかも
        if (!$csvType) {
          $csvType = $this->guessCsvTypeByCsvHeader($newFile, "\t");
        }
        if (!$csvType) {
          throw new \RuntimeException(sprintf('CSV種別が特定できませんでした。[%s]', $file->getClientOriginalName()));
        }

        if (!isset($result[$csvType])) {
          $result[$csvType] = [];
        }

        $result[$csvType][] = $newFile;

      } catch (\Exception $e) {
        throw new \RuntimeException(sprintf('%s [%s]', $e->getMessage(), $file->getClientOriginalName()));
      }
    }

    // $logger->info(print_r($result, true));
    return $result;
  }


  /**
   * ヘッダ行（およびデータ１行目）からCSV種別判定
   * ※valid チェックも兼ねる
   * @param \SplFileInfo $file
   * @param string $delimiter
   * @return string
   */
  private function guessCsvTypeByCsvHeader($file, $delimiter = ',')
  {
    $fObj = $file->openFile('rb');

    // ヘッダ行を配列に分解
    $fields = $fObj->fgetcsv($delimiter, '"', '\\');

    $this->getLogger()->dump($fields);

    if (!is_array($fields)) {
      throw new \RuntimeException('ヘッダ行が取得できませんでした。');
    }

    $type = null;
    switch ($fields) {
      case self::$CSV_FIELDS_BUSINESS_SESSION:
        $type = self::CSV_TYPE_BUSINESS_SESSION;
        break;
      case self::$CSV_FIELDS_SNL_STOCK:
        $type = self::CSV_TYPE_SNL_STOCK;
        break;
      default:
        break;
    }

    return $type;
  }

  /**
   * Amazon.com モール別価格更新処理
   * 共通処理が済んでいることが前提
   * * 消費税なし
   * * 国際郵便送料を加算
   * * モールシステム料率を加算
   */
  public function updateAmazonComPrice()
  {
    $db = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    // モール別設定
    $shoppingMallAmazonCom = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_AMAZON_COM);

    // 0 更新
    $sql = <<<EOD
      UPDATE tb_amazon_com_information i
      INNER JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.baika_tanka = 0
      WHERE i.baika_tanka > 0
        AND i.original_price = 0
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
    $stmt->execute();

    $sql = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , m.baika_tnk
        , m.type
        , m.method
        /*
        , (
            SELECT MIN(p.weight)
            FROM tb_postage_international p
            WHERE p.type = m.type
              AND p.method = m.method
              AND p.area_code = m.area_code
              AND p.weight >= m.weight
         ) AS postage_weight
        */
        , (
            SELECT MIN(postage)
            FROM tb_postage_international p
            WHERE p.type = m.type
              AND p.method = m.method
              AND p.area_code = m.area_code
              AND p.weight >= m.weight
         ) AS postage
      FROM (
          SELECT
              m.daihyo_syohin_code
            , m.weight
            , COALESCE(s.type, 'EMS') AS type
            , CASE WHEN COALESCE(s.type, 'EMS') = 'EMS' THEN 'EMS' ELSE 'SAL便' END AS method
            , CASE WHEN COALESCE(s.type, 'EMS') = 'EMS' THEN '2-2' ELSE '2' END AS area_code
            , cal.baika_tnk
          FROM tb_mainproducts m
          INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN tb_amazon_com_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
          LEFT JOIN v_postage_international_size s ON m.daihyo_syohin_code = s.daihyo_syohin_code
          WHERE m.weight > 0
            AND i.original_price = 0
            AND cal.deliverycode_pre <> :deliveryCodeTemporary
            AND cal.deliverycode_pre <> :deliveryCodeFinished
      ) m
      ;
EOD;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
    $stmt->execute();

    // 一括更新

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_amazon_com_information", [
      'fields' => [
          'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'baika_tanka' => \PDO::PARAM_INT
        , 'packet_type' => \PDO::PARAM_STR
        , 'shipping_method' => \PDO::PARAM_STR
        , 'postage' => \PDO::PARAM_INT
      ]
      , 'postfix' => " ON DUPLICATE KEY UPDATE "
                   . "     baika_tanka = VALUES(baika_tanka) "
                   . "   , packet_type = VALUES(packet_type) "
                   . "   , shipping_method = VALUES(shipping_method) "
                   . "   , postage = VALUES(postage) "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $db, $stmt, function ($row) use ($shoppingMallAmazonCom) {

      $item = [];
      $item['daihyo_syohin_code'] = $row['daihyo_syohin_code'];
      $item['baika_tanka'] = floor(($row['baika_tnk'] + $row['postage']) * ((100 + $shoppingMallAmazonCom->getAdditionalCostRatio()) / 100));

      $item['packet_type']     = $row['type'];
      $item['shipping_method'] = $row['method'];
      $item['postage']         = $row['postage'];

      return $item;

    }, 'foreach');

  }

  /**
   * 商品在庫テーブル名取得
   * @param string $shop
   * @return
   */
  public function getProductStockTableName($shop)
  {
    $tables = [
        self::SHOP_NAME_VOGUE => 'tb_amazon_product_stock'
      , self::SHOP_NAME_US_PLUSNAO => 'tb_amazon_com_product_stock'
    ];

    if (!isset($tables[$shop])) {
      throw new \RuntimeException('invalid amazon shop name : ' . $shop);
    }

    return $tables[$shop];
  }

  /**
   * CSV出力ディレクトリ取得
   * @param $shop
   * @return null|string
   */
  public function getCsvDirName($shop)
  {
    $saveDirName = null;
    switch ($shop) {
      case AmazonMallProcess::SHOP_NAME_US_PLUSNAO:
        $saveDirName = 'AmazonCom';
        break;

      case AmazonMallProcess::SHOP_NAME_VOGUE: // fallthrough
      default:
        $saveDirName = 'Amazon';
        break;
    }

    return $saveDirName;
  }


  /**
   * アップロードCSVデータ取込（FBA出荷用）
   * @param File[][] $fileList
   * @param array $result
   */
  public function importBusinessSessionFiles($fileList, &$result)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    foreach($fileList as $type => $files) {
      switch($type) {
        case self::CSV_TYPE_BUSINESS_SESSION:
          $dbMain->query("TRUNCATE tb_amazon_business_report_session");

          foreach($files as $i => $file) {

            $sql = <<<EOD
              LOAD DATA LOCAL INFILE :importFilePath
              IGNORE INTO TABLE tb_amazon_business_report_session
              FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
              LINES TERMINATED BY '\n'
              IGNORE 1 LINES
              (
                  `parent_asin`
                , `child_asin`
                , `商品名`
                , @session
                , `セッションのパーセンテージ`
                , @page_view
                , `ページビュー率`
                , `カートボックス獲得率`
                , @order_num
                , `ユニットセッション率`
                , `注文商品売上`
                , @order_item_num
              )
              /* 数値にカンマが入っているので除去して格納 */
              SET
                  `セッション`         = CAST(REPLACE(@session, ',', '') AS DECIMAL)
                , `ページビュー`       = CAST(REPLACE(@page_view, ',', '') AS DECIMAL)
                , `注文された商品点数` = CAST(REPLACE(@order_num, ',', '') AS DECIMAL)
                , `注文品目総数`       = CAST(REPLACE(@order_item_num, ',', '') AS DECIMAL)

EOD;
            $stmt = $dbMain->prepare($sql);
            $stmt->bindValue(':importFilePath', $file->getPathname());
            $stmt->execute();
          }

          break;
      }
    }
  }


  /**
   * アップロードCSVデータ取込（S&L出荷用）
   * @param File[][] $fileList
   * @param array $result
   */
  public function importSnlStockFiles($fileList, &$result)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    foreach($fileList as $type => $files) {
      switch($type) {
        case self::CSV_TYPE_SNL_STOCK:
          $dbMain->query("TRUNCATE tb_amazon_snl_stock");

          foreach($files as $i => $file) {

            $sql = <<<EOD
              LOAD DATA LOCAL INFILE :importFilePath
              IGNORE INTO TABLE tb_amazon_snl_stock
              FIELDS TERMINATED BY '\t' ENCLOSED BY '"' ESCAPED BY ''
              LINES TERMINATED BY '\n'
              IGNORE 1 LINES
              (
                  `SKU`
                , `FNSKU`
                , `ASIN`
                , `商品名`
                , `FBA小型軽量商品ですか？`
                , `マーケットプレイス`
                , @price
                , @stock
                , @other_stock
              )
              /* 数値にカンマが入っている場合は除去して格納 */
              SET
                  `出品者のFBA小型軽量商品の価格`          = CAST(REPLACE(@price, ',', '') AS DECIMAL)
                , `FBA小型軽量商品のFCにある在庫(点数)`    = CAST(REPLACE(@stock, ',', '') AS DECIMAL)
                , `FBA小型軽量商品のFC以外にある在庫(点数)` = CAST(REPLACE(@other_stock, ',', '') AS DECIMAL)
EOD;
            $stmt = $dbMain->prepare($sql);
            $stmt->bindValue(':importFilePath', $file->getPathname());
            $stmt->execute();
          }

          break;
      }
    }
  }

  /**
   * S&L CSV出力 （NextEngine用、Amazon追加用、Amazon削除用の3件を出力）
   */
  public function exportSlnCsv()
  {
    $now = new \DateTime();
    $fs = new Filesystem();
    $exportDir = sprintf('%s/%s', $this->getSnlOrderCsvDir(), $now->format('YmdHis'));
    $fs->mkdir($exportDir, 0777);

    // NextEngine汎用CSV、SHOPLISTアップロードCSV出力
    $this->exportSlnNextEngineOrderCsv($exportDir);

    // Amazon追加用TSV出力
    $this->exportSlnAmazonAddTsv($exportDir);

    // Amazon削除用TSV出力
    $this->exportSlnAmazonDeleteTsv($exportDir);
  }


  /**
   * Amazon S&L CSV出力 NextEngine汎用受注CSV
   * @param $exportDir
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportSlnNextEngineOrderCsv($exportDir)
  {
    $commonUtil = $this->getDbCommonUtil();

    // CSV出力処理
    $now = new \DateTime();
    $filePath = sprintf('%s/%s', $exportDir, self::CSV_NAME_SNL_NEXT_ENGINE_ORDER);

    $dbMain = $this->getDb('main');

    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_amazon_snl_next_engine_order");
    $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_amazon_snl_next_engine_order (
            sku VARCHAR(50) NOT NULL PRIMARY KEY
          , quantity INTEGER NOT NULL DEFAULT 0
          , price  INTEGER NOT NULL DEFAULT 0
          , tax INTEGER NOT NULL DEFAULT 0
          , title VARCHAR(255) NOT NULL DEFAULT ''
        ) Engine=InnoDB DEFAULT CHARSET utf8;
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
        INSERT INTO tmp_work_amazon_snl_next_engine_order (
            sku
          , quantity
          , price
          , tax
          , title
        )
        SELECT
             pci.ne_syohin_syohin_code
           , :unitNum AS stock
           , i.snl_baika AS price
           , TRUNCATE(i.snl_baika * CAST(:taxRate AS DECIMAL(10, 2)), 0) AS tax
           , i.amazon_title
        FROM tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
        INNER JOIN tb_amazoninfomation i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN tb_amazon_snl_stock s ON pci.ne_syohin_syohin_code = s.SKU
        WHERE cal.deliverycode <> 4
          AND i.registration_flg <> 0
          AND i.snl_flg <> 0
          AND i.snl_baika > 0
          AND pci.`フリー在庫数` >= :unitNum
          AND (
               s.SKU IS NULL
            OR s.`FBA小型軽量商品のFCにある在庫(点数)` < (:unitNum / 2)
          )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':taxRate', $commonUtil->getTaxRate() - 1, \PDO::PARAM_STR);
    $stmt->bindValue(':unitNum', self::SNL_ORDER_UNIT_NUM, \PDO::PARAM_INT);
    $stmt->execute();

    // ----------------------------------------
    // CSV出力
    // ----------------------------------------

    // 集計値取得
    $sql = <<<EOD
        SELECT
            SUM(t.price * t.quantity)                           AS price_sum
          , SUM(t.tax * t.quantity)                             AS tax_sum
          , SUM(t.price * t.quantity) + SUM(t.tax * t.quantity) AS price_total
        FROM tmp_work_amazon_snl_next_engine_order t
        WHERE t.quantity > 0
EOD;
    $sum = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);

    // データ取得
    $sql = <<<EOD
        SELECT
            :voucherNumber            AS `店舗伝票番号`
          , :orderDate                AS `受注日`
          , '3550157'                 AS `受注郵便番号`
          , '埼玉県比企郡吉見町西吉見480 GLP吉見'    AS `受注住所1`
          , ''           AS `受注住所2`
          , 'VJUN'       AS `受注名`
          , ''           AS `受注名カナ`
          , ''           AS `受注電話番号`
          , 'welcome@plusnao.co.jp'   AS `受注メールアドレス`
          , '3550157'                 AS `発送郵便番号`
          , '埼玉県比企郡吉見町西吉見480 GLP吉見'   AS `発送先住所１`
          , ''      AS `発送先住所２`
          , 'VJUN'  AS `発送先名`
          , ''      AS `発送先カナ`
          , ''      AS `発送電話番号`
          , :paymentMethod        AS `支払方法`
          , :deliveryMethod       AS `発送方法`
          , :priceSum             AS `商品計`
          , :taxSum               AS `税金`
          , 0 AS `発送料`
          , 0 AS `手数料`
          , 0 AS `ポイント`
          , 0 AS `その他費用`
          , :priceTotal           AS `合計金額`
          , 1 AS `ギフトフラグ`
          , '' AS `時間帯指定`
          , '' AS `日付指定`
          , '' AS `作業者欄`
          , '' AS `備考`
          , t.title               AS `商品名`
          , t.sku                 AS `商品コード`
          , t.price               AS `商品価格`
          , t.quantity            AS `受注数量`
          , '' AS `商品オプション`
          , '' AS `出荷済フラグ`
          , '' AS `顧客区分`
          , '' AS `顧客コード`
        FROM tmp_work_amazon_snl_next_engine_order t
        WHERE t.quantity > 0
        ORDER BY t.sku
EOD;
    $stmt = $dbMain->prepare($sql);

    $stmt->bindValue(':voucherNumber', sprintf('AMAZON_SNL_%s', $now->format('YmdHis')), \PDO::PARAM_STR);
    $stmt->bindValue(':orderDate', $now->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':paymentMethod', DbCommonUtil::PAYMENT_METHOD_DONE, \PDO::PARAM_STR); // 支払済
    $stmt->bindValue(':deliveryMethod', DbCommonUtil::DELIVERY_METHOD_TAKUHAI, \PDO::PARAM_STR); // 佐川急便(e飛伝2)

    $stmt->bindValue(':priceSum', $sum['price_sum'], \PDO::PARAM_INT);
    $stmt->bindValue(':taxSum', $sum['tax_sum'], \PDO::PARAM_INT);
    $stmt->bindValue(':priceTotal', $sum['price_total'], \PDO::PARAM_INT);
    $stmt->execute();

    $results = [
        'message' => null
      , 'count' => null
      , 'filename' => null
    ];

    if ($stmt->rowCount() > 0) {

      $results['count'] = 0;
      $results['filename'] = basename($filePath);

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->getContainer()->get('misc.util.string');

      // ヘッダ
      $headers = [
          '店舗伝票番号'  => '店舗伝票番号'
        , '受注日'        => '受注日'
        , '受注郵便番号'  => '受注郵便番号'
        , '受注住所1'     => '受注住所1'
        , '受注住所2'     => '受注住所2'
        , '受注名'       => '受注名'
        , '受注名カナ'   => '受注名カナ'
        , '受注電話番号'  => '受注電話番号'
        , '受注メールアドレス' => '受注メールアドレス'
        , '発送郵便番号'  => '発送郵便番号'
        , '発送先住所１'  => '発送先住所１'
        , '発送先住所２'  => '発送先住所２'
        , '発送先名'      => '発送先名'
        , '発送先カナ'   => '発送先カナ'
        , '発送電話番号'  => '発送電話番号'
        , '支払方法'      => '支払方法'
        , '発送方法'      => '発送方法'
        , '商品計'        => '商品計'
        , '税金'          => '税金'
        , '発送料'        => '発送料'
        , '手数料'        => '手数料'
        , 'ポイント'      => 'ポイント'
        , 'その他費用'     => 'その他費用'
        , '合計金額'      => '合計金額'
        , 'ギフトフラグ'  => 'ギフトフラグ'
        , '時間帯指定'     => '時間帯指定'
        , '日付指定'      => '日付指定'
        , '作業者欄'      => '作業者欄'
        , '備考'          => '備考'
        , '商品名'       => '商品名'
        , '商品コード'   => '商品コード'
        , '商品価格'      => '商品価格'
        , '受注数量'      => '受注数量'
        , '商品オプション' => '商品オプション'
        , '出荷済フラグ'   => '出荷済フラグ'
        , '顧客区分'      => '顧客区分'
        , '顧客コード'    => '顧客コード'
      ];


      $fp = fopen($filePath, 'wb');

      // ヘッダ
      $eol = "\r\n";
      $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
      $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
      fwrite($fp, $header);

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

        fwrite($fp, $line);

        $results['count']++;
      }

      fclose($fp);

    } else {
      $results['message'] = '出力するデータがありませんでした。';
    }

    $this->getLogger()->info(print_r($results, true));
  }


  /**
   * Amazon S&L CSV出力 Amazon追加用
   *
   * @param $exportDir
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportSlnAmazonAddTsv($exportDir)
  {
    $filePath = sprintf('%s/%s', $exportDir, self::CSV_NAME_SNL_AMAZON_UPLOAD_ADD);

    $dbMain = $this->getDb('main');

    // ----------------------------------------
    // CSV出力
    // ----------------------------------------
    // データ取得
    $sql = <<<EOD
      SELECT
         pci.ne_syohin_syohin_code
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      INNER JOIN tb_amazoninfomation i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_amazon_snl_stock s ON s.SKU = pci.ne_syohin_syohin_code
      WHERE cal.deliverycode <> 4
        AND i.registration_flg <> 0
        AND i.snl_flg <> 0
        AND i.snl_baika > 0
        AND s.SKU IS NULL
      ORDER BY m.daihyo_syohin_code
             , pci.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $results = [
        'message' => null
      , 'count' => null
      , 'filename' => null
    ];

    if ($stmt->rowCount() > 0) {

      $results['count'] = 0;
      $results['filename'] = basename($filePath);

      $headers = [
          "COUNTRY\tJP\t"
        , "\t\t"
        , "MSKU\t\t"
      ];

      $fp = fopen($filePath, 'wb');

      // ヘッダ
      $eol = "\r\n";
      $header = implode($eol, $headers) . $eol;
      $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
      fwrite($fp, $header);

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $line = $row['ne_syohin_syohin_code'] . $eol;
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

        fwrite($fp, $line);

        $results['count']++;
      }

      fclose($fp);

    } else {
      $results['message'] = '出力するデータがありませんでした。';
    }
  }

  /**
   * Amazon S&L CSV出力 Amazon削除用
   *
   * @param $exportDir
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportSlnAmazonDeleteTsv($exportDir)
  {
    $filePath = sprintf('%s/%s', $exportDir, self::CSV_NAME_SNL_AMAZON_UPLOAD_DELETE);

    $dbMain = $this->getDb('main');

    // ----------------------------------------
    // CSV出力
    // ----------------------------------------
    // データ取得
    $sql = <<<EOD
      SELECT
        s.SKU
      FROM tb_amazon_snl_stock s
      LEFT JOIN (
        SELECT
           pci.ne_syohin_syohin_code
        FROM tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
        INNER JOIN tb_amazoninfomation i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        WHERE cal.deliverycode <> 4
          AND i.registration_flg <> 0
          AND i.snl_flg <> 0
          AND i.snl_baika > 0
      ) T ON s.SKU = T.ne_syohin_syohin_code
      WHERE T.ne_syohin_syohin_code IS NULL
      ORDER BY s.SKU
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $results = [
        'message' => null
      , 'count' => null
      , 'filename' => null
    ];

    if ($stmt->rowCount() > 0) {

      $results['count'] = 0;
      $results['filename'] = basename($filePath);

      $headers = [
        "COUNTRY\tJP\t"
        , "\t\t"
        , "MSKU\t\t"
      ];

      $fp = fopen($filePath, 'wb');

      // ヘッダ
      $eol = "\r\n";
      $header = implode($eol, $headers) . $eol;
      $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
      fwrite($fp, $header);

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $line = $row['SKU'] . $eol;
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

        fwrite($fp, $line);

        $results['count']++;
      }

      fclose($fp);

    } else {
      $results['message'] = '出力するデータがありませんでした。';
    }
  }


  // Amazon モール受注CSV（注文レポート） 書式チェック
  /**
   * ヘッダ行（およびデータ１行目）からモール判定
   * ※valid チェックも兼ねる
   * @param \SplFileInfo $file
   * @return string
   */
  public function isValidOrderReport($file)
  {
    $fObj = $file->openFile('rb');

    $firstLine = $fObj->fgets();
    $secondLine = $fObj->fgets();

    if (!$firstLine || !$secondLine) {
      throw new \RuntimeException('invalid file. (no 2 lines)');
    }

    // ヘッダ行を配列に分解

    // まずタブ区切りを試してみる
    $tmpFields = explode("\t", $firstLine);

    $fObj->rewind();
    // おそらくタブ区切り
    if (count($tmpFields) > 10) {
      $fields = $fObj->fgetcsv("\t", '"', '\\');
      // でなければカンマ区切り
    } else {
      $fields = $fObj->fgetcsv(',', '"', '\\');
    }

    if (!is_array($fields) || count($fields) < 2) {
      throw new \RuntimeException('ヘッダ行が取得できませんでした。');
    }

    return $fields == AmazonMallProcess::$CSV_FIELDS_MALL_ORDER;
  }


  // ASIN別 詳細ページ 売上・トラフィック
  protected static $CSV_FIELDS_BUSINESS_SESSION = [
      '(親)ASIN'
    , '(子)ASIN'
    , '商品名'
    , 'セッション'
    , 'セッションのパーセンテージ'
    , 'ページビュー'
    , 'ページビュー率'
    , 'カートボックス獲得率'
    , '注文された商品点数'
    , 'ユニットセッション率'
    , '注文商品売上'
    , '注文品目総数'
  ];

  // S&L在庫データ
  protected static $CSV_FIELDS_SNL_STOCK = [
      'SKU'
    , 'FNSKU'
    , 'ASIN'
    , '商品名'
    , 'FBA小型軽量商品ですか？'
    , 'マーケットプレイス'
    , '出品者のFBA小型軽量商品の価格'
    , 'FBA小型軽量商品のFCにある在庫(点数)'
    , 'FBA小型軽量商品のFC以外にある在庫(点数)'
  ];


  // モール受注CSV（注文レポート） フィールド定義
  public static $CSV_FIELDS_MALL_ORDER = [
      'order-id'
    , 'order-item-id'
    , 'purchase-date'
    , 'payments-date'
    , 'buyer-email'
    , 'buyer-name'
    , 'buyer-phone-number'
    , 'sku'
    , 'product-name'
    , 'quantity-purchased'
    , 'currency'
    , 'item-price'
    , 'item-tax'
    , 'shipping-price'
    , 'shipping-tax'
    , 'gift-wrap-price'
    , 'gift-wrap-tax'
    , 'ship-service-level'
    , 'recipient-name'
    , 'ship-address-1'
    , 'ship-address-2'
    , 'ship-address-3'
    , 'ship-city'
    , 'ship-state'
    , 'ship-postal-code'
    , 'ship-country'
    , 'ship-phone-number'
    , 'gift-wrap-type'
    , 'gift-message-text'
    , 'item-promotion-discount'
    , 'item-promotion-id'
    , 'ship-promotion-discount'
    , 'ship-promotion-id'
    , 'delivery-start-date'
    , 'delivery-end-date'
    , 'delivery-time-zone'
    , 'delivery-Instructions'
    , 'is-prime'
  ];


}
