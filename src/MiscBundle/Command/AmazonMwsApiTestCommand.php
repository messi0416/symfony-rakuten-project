<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\AmazonMallProcess;
use MarketplaceWebService\MarketplaceWebService_Client;
use MarketplaceWebService\MarketplaceWebService_Interface;
use MarketplaceWebService\Model\MarketplaceWebService_Model_CancelReportRequestsRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportListRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportRequestListRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_GetReportResponse;
use MarketplaceWebService\Model\MarketplaceWebService_Model_ReportRequestInfo;
use MarketplaceWebService\Model\MarketplaceWebService_Model_RequestReportRequest;
use MarketplaceWebService\Model\MarketplaceWebService_Model_RequestReportResult;
use MarketplaceWebService\Model\MarketplaceWebService_Model_ResponseMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AmazonMwsApiTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  const DATE_FORMAT =  'Y-m-d\TH:i:s\Z';

  const APPLICATION_NAME        = 'forest';
  const APPLICATION_VERSION     = '0.1';

  // Amazon.co.jp ( welcome@plusnao.co.jp )
  const AWS_ACCESS_KEY_ID       = 'AKIAIFFJKG3R53RAUIVA';
  const AWS_SECRET_ACCESS_KEY   = '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';
  const MERCHANT_ID             = 'A3IIBRV6ST98CC';
  const MARKET_PLACE_ID = 'A1VC38T7YXB528';
  const SERVICE_URL = 'https://mws.amazonservices.jp'; // JP

//  // Amazon.com ( naoya.ishida.plusnao@gmail.com )
//  const AWS_ACCESS_KEY_ID       = 'AKIAJMAB6XGAJ3Y7YDNQ';
//  const AWS_SECRET_ACCESS_KEY   = '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';
//  const MERCHANT_ID             = 'A2808G83LJ3XBH';
//  const MARKET_PLACE_ID = 'ATVPDKIKX0DER';
//  const SERVICE_URL = 'https://mws.amazonservices.com'; // US
//
  protected function configure()
  {
    $this
      ->setName('misc:amazon-api-test')
      ->setDescription('Amazon MWS API クライアントライブラリ試験');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // $this->requestReport();

    // $this->getReportRequestList();

    // $this->getReportList();

//    $reportId = '5307216564017319'; // FOR TEST
//    $fileUtil = $this->getFileUtil();
//    $outputPath = $fileUtil->getWebCsvDir() . '/Amazon/Import/test_report.txt';
//
//    $this->getReport($reportId, $outputPath);

    // 連続実行試験
    // $this->process();


    // フィード送信（商品登録。成功すると店舗に実際に登録されるので注意）
    // $this->submitForm();

    // FBA 在庫レポート取得
    // $this->getFbaStockReport();

    // Amazon商品在庫データ FBA在庫数更新処理（FBA在庫ダウンロード ＆ 更新）
    // $this->updateFbaProductStock();

    // リクエストキャンセル
    // $cancelReportId = '69874016895';
    // $this->cancelReportRequest($cancelReportId);

    // 注文レポート一覧取得
    // $this->getOrderReportList();

    // 注文レポート 確認済みチェック
    // $this->mwsUpdateReportAcknowledgements(['1036982529017445']);
    $this->mwsUpdateReportAcknowledgements([
        '1039708969017445'
      , '1036940389017445'
      , '1035959166017445'
      , '1037087273017445'
      , '1036545494017445'
      , '1037011498017445'
      , '1038042332017445'
      , '1038365338017445'
      , '1035928048017445'
      , '1037642988017445'
      , '1034479148017445'
      , '1036458468017445'
      , '1037335370017445'
      , '1036066101017445'
      , '1035398041017445'
      , '1035457591017445'
      , '1037334155017445'
      , '1037826280017445'
    ]);

    // echo "なにもしない！";

    $output->writeln('done!');
  }

  /// 注文レポート 確認済みチェック更新
  private function mwsUpdateReportAcknowledgements($reportId)
  {
    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
    $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述 ※今のところ、Amazon APIのモックがでたらめなので必ず本番接続 要注意。

    $mallProcess->mwsUpdateReportAcknowledgements('vogue', $reportId, true);
  }


  /// 注文レポート 一覧取得テスト
  private function getOrderReportList()
  {
    $parameters = array (
        'Merchant' => self::MERCHANT_ID
      , 'AvailableToDate' => new \DateTime('now', new \DateTimeZone('UTC'))
      , 'AvailableFromDate' => new \DateTime('-1 days', new \DateTimeZone('UTC'))

      , 'Acknowledged' => false
      , 'MaxCount' => 100
      , 'ReportTypeList' => [ 'Type' => '_GET_FLAT_FILE_ORDERS_DATA_' ]
    );

    $this->getReportList($parameters);
  }


  /// 注文レポート リクエスト （60日固定）
  private function requestOrderReportTest()
  {
    $result = $this->requestReport('_GET_FLAT_FILE_ORDERS_DATA_');

    var_dump($result);

    $result = $this->getReportRequestList();

    var_dump($result);

    $this->getReportList();

    var_dump($result);
  }



  /// Amazon商品在庫データ FBA在庫数更新処理（ダウンロード ＆ 更新）
  private function updateFBAProductStock()
  {
    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
    $mallProcess->setEnvironment('prod'); // 開発環境でもMWS本番への接続を行う。

    $mallProcess->updateFbaProductStock(AmazonMallProcess::SHOP_NAME_VOGUE);
  }

  /// FBA在庫レポート取得
  private function getFbaStockReport()
  {
    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
    $mallProcess->setEnvironment('prod'); // 開発環境でもMWS本番への接続を行う。

    $filePath = sprintf('%s/Amazon/Import/fba_stock_%s.txt', $this->getFileUtil()->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
    $mallProcess->mwsGetFBAStockReport(AmazonMallProcess::SHOP_NAME_VOGUE, $filePath);
  }

  /// 101. フィード送信
  private function submitForm()
  {
    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
    $mallProcess->setEnvironment('prod'); // 開発環境でもMWS本番への接続を行う。

    //;$config = $mallProcess->getMwsAccount(AmazonMallProcess::SHOP_NAME_UPGRADE_PN);
    // var_dump($config);
    // exit;
    $filePath = $this->getFileUtil()->getDataDir() . '/dev_test/amazon_feed_sample.txt';
    // $filePath = $this->getFileUtil()->getDataDir() . '/dev_test/amazon_feed_sample_stock.txt';
    $mallProcess->submitFeeds(AmazonMallProcess::SHOP_NAME_VOGUE, $filePath);


  }



  /// 99. 連続実行試験
  private function process()
  {
    $result = $this->requestReport();
    if (!$result || $result['status'] != 'ok') {
      echo "リクエスト失敗！";
      return;
    }

    $count = 1;
    do {
      if (isset($reports)) {
        sleep(5);
      }

      $reports = $this->getReportRequestList($result['ReportRequestId']);
      $target = null;
      foreach($reports as $report) {
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

    } while (!$target && $count++ <= 12);
    var_dump($target);

    if ($target) {
      $fileUtil = $this->getFileUtil();
      $outputPath = $fileUtil->getWebCsvDir() . '/Amazon/Import/test_report_mini.txt';
      $this->getReport($target['GeneratedReportId'], $outputPath);
    }
  }



  /// 03. レポート取得
  private function getReport($reportId, $outputPath)
  {
    // $service = new MarketplaceWebService_Mock();
    $service = $this->getService();

    /************************************************************************
     * Setup request parameters and uncomment invoke to try out
     * sample for Get Report Action
     ***********************************************************************/
    // @TODO: set request. Action can be passed as MarketplaceWebService_Model_GetReportRequest
    // object or array of parameters

    $parameters = array (
      'Merchant' => self::MERCHANT_ID,
      // 'Report'   => @fopen('php://memory', 'rw+'),
      'Report' => fopen($outputPath, 'w+b'),
      'ReportId' => $reportId,
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );
    $request = new MarketplaceWebService_Model_GetReportRequest($parameters);

    /**
     * Get Report Action Sample
     * The GetReport operation returns the contents of a report. Reports can potentially be
     * very large (>100MB) which is why we only return one report at a time, and in a
     * streaming fashion.
     *
     * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
     * @param mixed $request MarketplaceWebService_Model_GetReport or array of parameters
     */
    $invoke = function (MarketplaceWebService_Interface $service, $request)
    {
      try {
        /** @var MarketplaceWebService_Model_GetReportResponse $response */
        $response = $service->getReport($request);

        echo ("Service Response\n");
        echo ("=============================================================================\n");

        echo("        GetReportResponse\n");
        if ($response->isSetGetReportResult()) {
          $getReportResult = $response->getGetReportResult();
          echo ("            GetReport");

          if ($getReportResult->isSetContentMd5()) {
            echo ("                ContentMd5");
            echo ("                " . $getReportResult->getContentMd5() . "\n");
          }
        }
        if ($response->isSetResponseMetadata()) {
          echo("            ResponseMetadata\n");
          $responseMetadata = $response->getResponseMetadata();
          if ($responseMetadata->isSetRequestId())
          {
            echo("                RequestId\n");
            echo("                    " . $responseMetadata->getRequestId() . "\n");
          }
        }

        echo ("        Report Contents\n");
        echo ("  -- omitted --\n");
        // echo (stream_get_contents($request->getReport()) . "\n");

        echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
      } catch (\MarketplaceWebService\Exception $ex) {
        echo("Caught Exception: " . $ex->getMessage() . "\n");
        echo("Response Status Code: " . $ex->getStatusCode() . "\n");
        echo("Error Code: " . $ex->getErrorCode() . "\n");
        echo("Error Type: " . $ex->getErrorType() . "\n");
        echo("Request ID: " . $ex->getRequestId() . "\n");
        echo("XML: " . $ex->getXML() . "\n");
        echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
      }
    };

    $invoke($service, $request);

    // md5チェック
    var_dump(base64_encode(md5(file_get_contents($outputPath), true)));
  }




  /// 02. レポート出力リクエスト一覧
  private function getReportRequestList($reportRequestId = null, $reportProcessingStatus = null)
  {
    // $service = new MarketplaceWebService_Mock();
    $service = $this->getService();

    /************************************************************************
     * Setup request parameters and uncomment invoke to try out
     * sample for Get Report List Action
     ***********************************************************************/
    // @TODO: set request. Action can be passed as MarketplaceWebService_Model_GetReportListRequest
    // object or array of parameters

    $parameters = array (
      'Merchant' => self::MERCHANT_ID,
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    if ($reportRequestId) {
      $parameters['ReportRequestIdList'] = [ 'Id' => [ $reportRequestId ] ];

    // IDが指定された場合には他の条件は全て無視されるため、else if.
    } else if ($reportProcessingStatus) {
      $parameters['ReportProcessingStatusList'] = [ 'Status' => [ $reportProcessingStatus ] ];
    }

    $request = new MarketplaceWebService_Model_GetReportRequestListRequest($parameters);

    /**
     * Get Report List Action Sample
     * returns a list of reports; by default the most recent ten reports,
     * regardless of their acknowledgement status
     *
     * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
     * @param mixed $request MarketplaceWebService_Model_GetReportList or array of parameters
     */
    $invoke = function(MarketplaceWebService_Interface $service, $request)
    {
      $results = [];

      try {
        $response = $service->getReportRequestList($request);

        echo ("Service Response\n");
        echo ("=============================================================================\n");

        echo("        GetReportRequestListResponse\n");
        if ($response->isSetGetReportRequestListResult()) {
          echo("            GetReportRequestListResult\n");
          $getReportRequestListResult = $response->getGetReportRequestListResult();
          if ($getReportRequestListResult->isSetNextToken())
          {
            echo("                NextToken\n");
            echo("                    " . $getReportRequestListResult->getNextToken() . "\n");
          }
          if ($getReportRequestListResult->isSetHasNext())
          {
            echo("                HasNext\n");
            echo("                    " . $getReportRequestListResult->getHasNext() . "\n");
          }
          $reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList();
          foreach ($reportRequestInfoList as $reportRequestInfo) {
            $item = [];

            echo("                ReportRequestInfo\n");
            if ($reportRequestInfo->isSetReportRequestId())
            {
              echo("                    ReportRequestId\n");
              echo("                        " . $reportRequestInfo->getReportRequestId() . "\n");
              $item['ReportRequestId'] = $reportRequestInfo->getReportRequestId();
            }
            if ($reportRequestInfo->isSetReportType())
            {
              echo("                    ReportType\n");
              echo("                        " . $reportRequestInfo->getReportType() . "\n");
              $item['ReportType'] = $reportRequestInfo->getReportType();
            }
            if ($reportRequestInfo->isSetStartDate())
            {
              echo("                    StartDate\n");
              echo("                        " . $reportRequestInfo->getStartDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetEndDate())
            {
              echo("                    EndDate\n");
              echo("                        " . $reportRequestInfo->getEndDate()->format(self::DATE_FORMAT) . "\n");
            }
            // add start
            if ($reportRequestInfo->isSetScheduled())
            {
              echo("                    Scheduled\n");
              echo("                        " . $reportRequestInfo->getScheduled() . "\n");
            }
            // add end
            if ($reportRequestInfo->isSetSubmittedDate())
            {
              echo("                    SubmittedDate\n");
              echo("                        " . $reportRequestInfo->getSubmittedDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetReportProcessingStatus())
            {
              echo("                    ReportProcessingStatus\n");
              echo("                        " . $reportRequestInfo->getReportProcessingStatus() . "\n");
              $item['ReportProcessingStatus'] = $reportRequestInfo->getReportProcessingStatus();
            }
            // add start
            if ($reportRequestInfo->isSetGeneratedReportId())
            {
              echo("                    GeneratedReportId\n");
              echo("                        " . $reportRequestInfo->getGeneratedReportId() . "\n");
              $item['GeneratedReportId'] = $reportRequestInfo->getGeneratedReportId();
            }
            if ($reportRequestInfo->isSetStartedProcessingDate())
            {
              echo("                    StartedProcessingDate\n");
              echo("                        " . $reportRequestInfo->getStartedProcessingDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetCompletedDate())
            {
              echo("                    CompletedDate\n");
              echo("                        " . $reportRequestInfo->getCompletedDate()->format(self::DATE_FORMAT) . "\n");
            }
            // add end

            $results[] = $item;
          }
        }
        if ($response->isSetResponseMetadata()) {
          echo("            ResponseMetadata\n");
          $responseMetadata = $response->getResponseMetadata();
          if ($responseMetadata->isSetRequestId())
          {
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

      return $results;
    };

    $results = $invoke($service, $request);

    return $results;
  }


  /// 02'. 出力済みレポート一覧 ※レポートの特定が面倒なため、通常は利用しない
  private function getReportList($parameters = null)
  {
    // for mock
    // $service = new MarketplaceWebService_Mock();

    $service = $this->getService();

    /************************************************************************
     * Setup request parameters and uncomment invoke to try out
     * sample for Get Report List Action
     ***********************************************************************/
     // @TODO: set request. Action can be passed as MarketplaceWebService_Model_GetReportListRequest
     // object or array of parameters

    if (!$parameters) {
      $parameters = array (
        'Merchant' => self::MERCHANT_ID,
        'AvailableToDate' => new \DateTime('now', new \DateTimeZone('UTC')),
        'AvailableFromDate' => new \DateTime('-1 days', new \DateTimeZone('UTC')),
        'Acknowledged' => false,
        // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
      );
    }

     $request = new MarketplaceWebService_Model_GetReportListRequest($parameters);

    /**
     * Get Report List Action Sample
     * returns a list of reports; by default the most recent ten reports,
     * regardless of their acknowledgement status
     *
     * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
     * @param mixed $request MarketplaceWebService_Model_GetReportList or array of parameters
     */
    $invoke = function(MarketplaceWebService_Interface $service, $request)
    {
      try {
        $response = $service->getReportList($request);

        echo ("Service Response\n");
        echo ("=============================================================================\n");

        echo("        GetReportListResponse\n");
        if ($response->isSetGetReportListResult()) {
          echo("            GetReportListResult\n");
          $getReportListResult = $response->getGetReportListResult();
          if ($getReportListResult->isSetNextToken())
          {
            echo("                NextToken\n");
            echo("                    " . $getReportListResult->getNextToken() . "\n");
          }
          if ($getReportListResult->isSetHasNext())
          {
            echo("                HasNext\n");
            echo("                    " . $getReportListResult->getHasNext() . "\n");
          }
          $reportInfoList = $getReportListResult->getReportInfoList();
          foreach ($reportInfoList as $reportInfo) {
            echo("                ReportInfo\n");
            if ($reportInfo->isSetReportId())
            {
              echo("                    ReportId\n");
              echo("                        " . $reportInfo->getReportId() . "\n");
            }
            if ($reportInfo->isSetReportType())
            {
              echo("                    ReportType\n");
              echo("                        " . $reportInfo->getReportType() . "\n");
            }
            if ($reportInfo->isSetReportRequestId())
            {
              echo("                    ReportRequestId\n");
              echo("                        " . $reportInfo->getReportRequestId() . "\n");
            }
            if ($reportInfo->isSetAvailableDate())
            {
              echo("                    AvailableDate\n");
              echo("                        " . $reportInfo->getAvailableDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportInfo->isSetAcknowledged())
            {
              echo("                    Acknowledged\n");
              echo("                        " . $reportInfo->getAcknowledged() . "\n");
            }
            if ($reportInfo->isSetAcknowledgedDate())
            {
              echo("                    AcknowledgedDate\n");
              echo("                        " . $reportInfo->getAcknowledgedDate()->format(self::DATE_FORMAT) . "\n");
            }
          }
        }
        if ($response->isSetResponseMetadata()) {
          echo("            ResponseMetadata\n");
          $responseMetadata = $response->getResponseMetadata();
          if ($responseMetadata->isSetRequestId())
          {
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
    };


    $invoke($service, $request);
  }



  /// 01. レポート出力リクエスト
  private function requestReport($reportType = '_GET_FLAT_FILE_OPEN_LISTINGS_DATA_')
  {
    // 'ReportType'         => '_GET_MERCHANT_LISTINGS_DATA_',  // 出品詳細レポート

    /************************************************************************
     * Uncomment to configure the client instance. Configuration settings
     * are:
     *
     * - MWS endpoint URL
     * - Proxy host and port.
     * - MaxErrorRetry.
     ***********************************************************************/
    $service = $this->getService();

    /************************************************************************
     * Uncomment to try out Mock Service that simulates MarketplaceWebService
     * responses without calling MarketplaceWebService service.
     *
     * Responses are loaded from local XML files. You can tweak XML files to
     * experiment with various outputs during development
     *
     * XML files available under MarketplaceWebService/Mock tree
     *
     ***********************************************************************/
    // $service = new MarketplaceWebService_Mock();

    /************************************************************************
     * Setup request parameters and uncomment invoke to try out
     * sample for Report Action
     ***********************************************************************/
// Constructing the MarketplaceId array which will be passed in as the the MarketplaceIdList
// parameter to the RequestReportRequest object.
    $marketplaceIdArray = array("Id" => array(self::MARKET_PLACE_ID));

    // @TODO: set request. Action can be passed as MarketplaceWebService_Model_ReportRequest
    // object or array of parameters

    $parameters = array (
     'Merchant'           => self::MERCHANT_ID,
     'MarketplaceIdList'  => $marketplaceIdArray,
     'ReportType'         => $reportType,
     'ReportOptions'      => 'ShowSalesChannel=true',
     // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    $request = new MarketplaceWebService_Model_RequestReportRequest($parameters);

    /**
     * Get Report List Action Sample
     * returns a list of reports; by default the most recent ten reports,
     * regardless of their acknowledgement status
     *
     * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
     * @param mixed $request MarketplaceWebService_Model_GetReportList or array of parameters
     * @return array $result
     */
    $invoke = function (MarketplaceWebService_Interface $service, $request)
    {
      $result = null;

      try {
        $response = $service->requestReport($request);

        echo ("Service Response\n");
        echo ("=============================================================================\n");

        echo("        RequestReportResponse\n");
        if ($response->isSetRequestReportResult()) {
          echo("            RequestReportResult\n");
          /** @var MarketplaceWebService_Model_RequestReportResult $requestReportResult */
          $requestReportResult = $response->getRequestReportResult();

          if ($requestReportResult->isSetReportRequestInfo()) {

            /** @var MarketplaceWebService_Model_ReportRequestInfo $reportRequestInfo */
            $reportRequestInfo = $requestReportResult->getReportRequestInfo();
            echo("                ReportRequestInfo\n");
            if ($reportRequestInfo->isSetReportRequestId())
            {
              echo("                    ReportRequestId\n");
              echo("                        " . $reportRequestInfo->getReportRequestId() . "\n");

              $result = [
                  'status' => 'ok'
                , 'ReportRequestId' => $reportRequestInfo->getReportRequestId()
              ];
            }
            if ($reportRequestInfo->isSetReportType())
            {
              echo("                    ReportType\n");
              echo("                        " . $reportRequestInfo->getReportType() . "\n");
              $result['ReportType'] = $reportRequestInfo->getReportType();
            }
            if ($reportRequestInfo->isSetStartDate())
            {
              echo("                    StartDate\n");
              echo("                        " . $reportRequestInfo->getStartDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetEndDate())
            {
              echo("                    EndDate\n");
              echo("                        " . $reportRequestInfo->getEndDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetSubmittedDate())
            {
              echo("                    SubmittedDate\n");
              echo("                        " . $reportRequestInfo->getSubmittedDate()->format(self::DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetReportProcessingStatus())
            {
              echo("                    ReportProcessingStatus\n");
              echo("                        " . $reportRequestInfo->getReportProcessingStatus() . "\n");
            }
          }
        }
        if ($response->isSetResponseMetadata()) {
          echo("            ResponseMetadata\n");
          /** @var MarketplaceWebService_Model_ResponseMetadata $responseMetadata */
          $responseMetadata = $response->getResponseMetadata();
          if ($responseMetadata->isSetRequestId())
          {
            echo("                RequestId\n");
            echo("                    " . $responseMetadata->getRequestId() . "\n");
          }
        }

        echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

        if (!$result) {
          throw new \RuntimeException('no result data!');
        }

      } catch (\MarketplaceWebService\Exception $ex) {
        echo("Caught Exception: " . $ex->getMessage() . "\n");
        echo("Response Status Code: " . $ex->getStatusCode() . "\n");
        echo("Error Code: " . $ex->getErrorCode() . "\n");
        echo("Error Type: " . $ex->getErrorType() . "\n");
        echo("Request ID: " . $ex->getRequestId() . "\n");
        echo("XML: " . $ex->getXML() . "\n");
        echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");

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
    ;

    $result = $invoke($service, $request);

    // var_dump($result);
    return $result;
  }

  /**
   * キャンセル
   */
  private function cancelReportRequest($reportRequestId)
  {
    // $service = new MarketplaceWebService_Mock();
    $service = $this->getService();

    /************************************************************************
     * Setup request parameters and uncomment invoke to try out
     * sample for Cancel Reports Action
     ***********************************************************************/
    // @TODO: set request. Action can be passed as MarketplaceWebService_Model_CancelReportsRequest
    // object or array of parameters

    // Request objects can be constructed with an array of parameters.
    $parameters = array (
      'Merchant' => self::MERCHANT_ID,
      'ReportRequestIdList' => array ( 'Id' => array ($reportRequestId)),
      // 'MWSAuthToken' => '<MWS Auth Token>', // Optional
    );

    $request = new MarketplaceWebService_Model_CancelReportRequestsRequest($parameters);

    /**
     * Cancel Report Requests Action Sample
     * cancels report requests - by default all of the submissions of the
     * last 30 days that have not started processing
     *
     * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
     * @param mixed $request MarketplaceWebService_Model_CancelFeedSubmissions or array of parameters
     */
    $invoke = function(MarketplaceWebService_Interface $service, $request)
    {
      try {
        $response = $service->cancelReportRequests($request);

        echo ("Service Response\n");
        echo ("=============================================================================\n");

        echo("        CancelReportRequestsResponse\n");
        if ($response->isSetCancelReportRequestsResult()) {
          echo("            CancelReportRequestsResult\n");
          $cancelReportRequestsResult = $response->getCancelReportRequestsResult();
          if ($cancelReportRequestsResult->isSetCount())
          {
            echo("                Count\n");
            echo("                    " . $cancelReportRequestsResult->getCount() . "\n");
          }
          $reportRequestInfoList = $cancelReportRequestsResult->getReportRequestInfoList();
          foreach ($reportRequestInfoList as $reportRequestInfo) {
            echo("                ReportRequestInfo\n");
            if ($reportRequestInfo->isSetReportRequestId())
            {
              echo("                    ReportRequestId\n");
              echo("                        " . $reportRequestInfo->getReportRequestId() . "\n");
            }
            if ($reportRequestInfo->isSetReportType())
            {
              echo("                    ReportType\n");
              echo("                        " . $reportRequestInfo->getReportType() . "\n");
            }
            if ($reportRequestInfo->isSetStartDate())
            {
              echo("                    StartDate\n");
              echo("                        " . $reportRequestInfo->getStartDate()->format(DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetEndDate())
            {
              echo("                    EndDate\n");
              echo("                        " . $reportRequestInfo->getEndDate()->format(DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetSubmittedDate())
            {
              echo("                    SubmittedDate\n");
              echo("                        " . $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
            }
            if ($reportRequestInfo->isSetReportProcessingStatus())
            {
              echo("                    ReportProcessingStatus\n");
              echo("                        " . $reportRequestInfo->getReportProcessingStatus() . "\n");
            }
          }
        }
        if ($response->isSetResponseMetadata()) {
          echo("            ResponseMetadata\n");
          $responseMetadata = $response->getResponseMetadata();
          if ($responseMetadata->isSetRequestId())
          {
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
    };

    $invoke($service, $request);
  }


  /**
   * @return MarketplaceWebService_Client
   */
  private function getService()
  {
    // Japan
    $serviceUrl = self::SERVICE_URL;

    $config = array (
      'ServiceURL'    => $serviceUrl,
      'ProxyHost'     => null,
      'ProxyPort'     => -1,
      'MaxErrorRetry' => 3,
    );


    /************************************************************************
     * Instantiate Implementation of MarketplaceWebService
     *
     * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants
     * are defined in the .config.inc.php located in the same
     * directory as this sample
     ***********************************************************************/
    $service = new MarketplaceWebService_Client(
      self::AWS_ACCESS_KEY_ID,
      self::AWS_SECRET_ACCESS_KEY,
      $config,
      self::APPLICATION_NAME,
      self::APPLICATION_VERSION);

    return $service;
  }


}
