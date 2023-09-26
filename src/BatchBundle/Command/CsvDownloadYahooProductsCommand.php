<?php
/**
 * Yahoo CSVダウンロード処理
 *
 * [利用API]
 * ダウンロード要求API
 * http://developer.yahoo.co.jp/webapi/shopping/downloadRequest.html
 * ダウンロード準備完了通知API
 * http://developer.yahoo.co.jp/webapi/shopping/downloadList.html
 * ダウンロード実行API
 * http://developer.yahoo.co.jp/webapi/shopping/downloadSubmit.html
 *
 * User: hirai
 */

namespace BatchBundle\Command;

use BatchBundle\Job\MainJob;
use MiscBundle\Entity\Repository\SymfonyUserYahooAgentRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use MiscBundle\Entity\TbMaintenanceSchedule;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use \RuntimeException;

use GuzzleHttp\Client as GuzzleClient;

class CsvDownloadYahooProductsCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  // Yahoo 店舗コード
  const SELLER_ID_PLUSNAO = 'plusnao';
  const SELLER_ID_KAWAEMON = 'kawa-e-mon';
  const SELLER_ID_OTORIYOSE = 'mignonlindo'; // おとりよせ.com

  const READY_WAIT_WAIT_SECONDS = 60; // 1分ごとに準備確認
  const READY_WAIT_MAX_SECONDS = 600; // ダウンロード準備 最大待ち時間
  
  // API完了、CSV出力完了、Yahoo側取込完了 が全て終わるまでの予想時間（分）（mainキューが滞留している場合があるので夜間実行の商品CSVは長めに確保） メンテナンスチェック用
  // enqueue-csv-export-type=0であればチェックなし
  /** 関連処理完了までの予想時間(在庫） */
  const EXPORT_CSV_FINISH_MIN_STOCK = 120;
  /** 関連処理完了までの予想時間(商品） */
  const EXPORT_CSV_FINISH_MIN_PRODUCT = 300;
  


  /**
   * ダウンロード用ファイルを生成する対象のファイルタイプ
   */
  const CSV_TYPE_PRODUCTS       = 1; // 商品
  const CSV_TYPE_STOCK          = 2; // 在庫
  const CSV_TYPE_STORE_CATEGORY = 3; // ストアカテゴリ

  public static $SELLER_IDS = [
      ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO => self::SELLER_ID_PLUSNAO
    , ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON => self::SELLER_ID_KAWAEMON
    // おとりよせ
    , ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE => self::SELLER_ID_OTORIYOSE
  ];

  // CSV出力設定
  const ENQUEUE_EXPORT_CSV_TYPE_NONE = 0; // 出力しない
  const ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS = 1; // 商品追加・削除・在庫更新CSV（日次バッチ）
  const ENQUEUE_EXPORT_CSV_TYPE_STOCK = 2; // 在庫差分更新

  private $skipRequest = false;
  private $enqueueCsvExportType = self::ENQUEUE_EXPORT_CSV_TYPE_NONE;

  // おとりよせ店舗（Agent店舗）フラグ
  private $isOtoriyose = false;
  
  /** 店舗ID */
  private $sellerId = null;


  protected function configure()
  {
    $this
      ->setName('batch:csv-download-yahoo-products')
      ->setDescription('Yahoo APIを利用したYahoo商品情報のダウンロード処理')
      ->addOption('export-target', null, InputOption::VALUE_REQUIRED, 'ダウンロード対象', NULL) // plusnao,kawaemon
      ->addOption('export-type', null, InputOption::VALUE_REQUIRED, 'ダウンロードCSV種別 1:商品 2:在庫 3:ストアカテゴリ カンマ区切りで複数指定', self::CSV_TYPE_STOCK)
      ->addOption('skip-request', null, InputOption::VALUE_REQUIRED, 'ダウンロードリクエストスキップ（すでにリクエスト済みの時に利用）', '0')
      ->addOption('enqueue-csv-export-type', null, InputOption::VALUE_OPTIONAL, 'CSV出力 キュー追加種別 0:出力しない, 1:商品, 2:在庫更新', 0) // 後続処理として実行するCSV出力の種別
      ->addOption('enqueue-csv-export-do-upload', null, InputOption::VALUE_OPTIONAL, 'CSV出力 アップロード有無 1:する 0:しない', 0)
      ->addOption('enqueue-csv-export-skip-common-process', null, InputOption::VALUE_OPTIONAL, 'CSV出力 共通処理スキップ 1:する 0:しない', 1)
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
     */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger->initLogTimer();

    $logger->info('ヤフーCSVダウンロード処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = 'ヤフーCSV出力 CSVダウンロード';
    $logger->setExecTitle($logExecTitle);
    $logger->addDbLog($logger->makeDbLog(null, '開始'));

    try {      
      // 対象モール
      $exportTarget = $input->getOption('export-target');
      if (!$exportTarget) {
        throw new RuntimeException('CSVダウンロード対象が選択されていません。(' . implode('|', self::$SELLER_IDS) . ')');
      }
      if (!isset(self::$SELLER_IDS[$exportTarget])) {
        throw new RuntimeException('CSVダウンロード対象が正しくありません。(' . implode('|', self::$SELLER_IDS) . ') : ' . $exportTarget);
      }

      // おとりよせ店舗判定
      if ($exportTarget === ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE) { // おとりよせ.com
        $this->isOtoriyose = true;
      }

      // 取得店舗ID
      $this->sellerId = self::$SELLER_IDS[$exportTarget];
      // テスト環境
      if ($this->getEnvironment() == 'test') {
        $this->sellerId = $this->getContainer()->getParameter('yahoo_api_test_seller_id');
      }

      // 対象ファイル形式（カンマ区切り）
      $exportTypeStr = $input->getOption('export-type');
      $exportTypes = explode(',', $exportTypeStr);
      
      // 各フラグ
      $this->skipRequest = boolval($input->getOption('skip-request'));
      $this->enqueueCsvExportType = intval($input->getOption('enqueue-csv-export-type'));
      
      // YahooはFTPメンテ中はダウンロードも、その後のアップロードも出来ないため、ここで即時終了する。
      $needMinutes = 0;
      if ($this->enqueueCsvExportType == self::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS) {
        $needMinutes = self::EXPORT_CSV_FINISH_MIN_PRODUCT;
      } else if ($this->enqueueCsvExportType == self::ENQUEUE_EXPORT_CSV_TYPE_STOCK) {
        $needMinutes = self::EXPORT_CSV_FINISH_MIN_STOCK;
      }
      /** @var \MiscBundle\Entity\Repository\TbMaintenanceScheduleRepository $repo */
      $mainteRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMaintenanceSchedule');
      if ($mainteRepo->isMaintenance(array(TbMaintenanceSchedule::MAINTENANCE_TYPE_YAHOO_SCHEDULED), $needMinutes)) {
        throw new BusinessException("メンテナンス中のため処理スキップ");
      }
      
      $logger->info("$logExecTitle 対象: $exportTarget [{$this->sellerId}][$exportTypeStr] リクエスト:[{$this->skipRequest}]  後続処理: {$this->enqueueCsvExportType}" );

      // ファイルダウンロードを実施する
      $fileUtil = $this->getFileUtil();
      $outputDir = sprintf('%s/Yahoo/Import/%s/%s', $fileUtil->getWebCsvDir(), $exportTarget, (new \DateTime())->format('YmdHis'));
      $resultInfo = [];
      $resultInfo["outputDir"] = $outputDir;
      foreach ($exportTypes as $exportType) {
        $result = $this->executeDownloadProcess($exportTarget, $exportType, $outputDir, $logger);
        $resultInfo = array_merge($resultInfo, $result);
      }
      $logger->addDbLog($logger->makeDbLog(null, '終了')->setInformation($resultInfo));

      // CSV出力 キュー追加
      
      // 在庫更新CSV （おとりよせも共通）
      if ($this->enqueueCsvExportType == self::ENQUEUE_EXPORT_CSV_TYPE_STOCK) {

        $resque = $this->getResque();
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command'              => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO_UPDATE_STOCK
          , 'doUpload'             => ($input->getOption('enqueue-csv-export-do-upload') ? true : false)    // Yahoo アップロードフラグ
          , 'exportTarget'         => $exportTarget
          , 'importPath'           => $outputDir . '/quantity.csv'  // 在庫比較用 インポートファイル // #193184対応暫定：　現在は在庫しか使っていないので在庫きめうち
          , 'includeReservedStock' => ($this->isOtoriyose ? '1' : '0')
        ];
        if ($this->account) {
          $job->args['account'] = $this->account->getId();
        }
        $resque->enqueue($job);

        $logger->addDbLog($logger->makeDbLog(null, 'Yahoo 在庫更新CSV出力処理キュー追加'));

      // おとりよせ
      } else if ($this->isOtoriyose) {

        $resque = $this->getResque();
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command' => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO_OTORIYOSE
          , 'doUpload' => ($input->getOption('enqueue-csv-export-do-upload') ? true : false)    // Yahoo アップロードフラグ
          , 'skipCommonProcess' => ($input->getOption('enqueue-csv-export-skip-common-process') ? true : false) // 共通処理「スキップ」フラグ
          , 'exportTarget' => $exportTarget
          , 'importPath' => $outputDir . '/quantity.csv' // 削除CSV作成用 インポートファイル // #193184対応暫定：　現在は在庫しか使っていないので在庫きめうち
        ];
        if ($this->account) {
          $job->args['account'] = $this->account->getId();
        }
        $resque->enqueue($job);

        $logger->addDbLog($logger->makeDbLog(null, 'Yahoo CSV（おとりよせ）出力処理キュー追加'));

      } else if ($this->enqueueCsvExportType == self::ENQUEUE_EXPORT_CSV_TYPE_PRODUCTS) {

        $resque = $this->getResque();
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_YAHOO
          , 'doUpload'         => ($input->getOption('enqueue-csv-export-do-upload') ? true : false)    // Yahoo アップロードフラグ
          , 'exportTarget'     => $exportTarget
          , 'importPath'       => $outputDir
        ];
        if ($this->account) {
          $job->args['account'] = $this->account->getId();
        }
        $resque->enqueue($job);

        $logger->addDbLog($logger->makeDbLog(null, 'Yahoo CSV出力処理キュー追加'));
      }
      $logger->logTimerFlush();
      return 0;

    } catch (BusinessException $e) {
      $logger->info("$logExecTitle 業務エラーにより終了:" . " " . $e->getMessage());
      $logger->addDbLog($logger->makeDbLog(null, $e->getMessage()));
      $logger->logTimerFlush();
      return 1;
    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog(null, 'エラー終了')->setInformation($e->getMessage())
        , true, "ヤフーCSV出力 CSVダウンロードでエラーが発生しました。", 'error'
      );
      $logger->logTimerFlush();
      return 1;
    }
  }
  
  /**
   * Yahoo APIのアクセストークンを取得し、ヘッダに設定する client を生成し、返却する。
   * @throws \RuntimeException
   * @deprecated WebAccessUtil#getClientWithYahooAccessToken に移行すること
   */
  private function getClientWithAccessToken($exportTarget) 
  {  
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }
    $client = $webAccessUtil->getWebClient();
    
    // Yahoo API アクセストークン取得
    // おとりよせ
    if ($this->isOtoriyose) {
      
      /** @var SymfonyUserYahooAgentRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserYahooAgent');
      
      /** @var SymfonyUserYahooAgent $shopAccount */
      $shopAccount = $repo->getActiveShopAccountByShopCode($exportTarget);
      if (!$shopAccount) {
        throw new \RuntimeException('no account');
      }
      
      $auth = $webAccessUtil->getYahooAccessTokenWithRefresh($shopAccount);
      
      // 通常
    } else {
      $auth = $webAccessUtil->getYahooAccessTokenWithRefresh();
    }
    
    if (!$auth) {
      throw new \RuntimeException('Yahoo API のアクセストークンが取得できませんでした(WEBからの認証が必要です)。処理を終了します。');
    }
    $client->setHeader('Authorization', sprintf('Bearer %s', $auth->getAccessToken()));
    return $client;
  }
  
  /**
   * ファイルダウンロードの一連の流れを実施する。
   * 1. ダウンロード要求API
   * 2. ダウンロード準備完了通知API （成功するまで呼び出す）
   * 3. ダウンロード実行
   * 
   * @param $exportTarget 対象店舗
   * @param $exportType 対象ファイル種別
   * @param $outputDir 出力先ディレクトリ　全ファイルをここに格納する
   * @param $logger Logger
   */
  private function executeDownloadProcess($exportTarget, $exportType, $outputDir, $logger)
  {
    $logTitle = "CSVダウンロード[$exportTarget][$exportType]";
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $client = $this->getClientWithAccessToken($exportTarget);
    
    // ダウンロード要求 送信
    $requestTime = new \DateTimeImmutable();
    if ($this->skipRequest) {
      $requestTime = $requestTime->modify('-1 day'); // リクエストを送信しない場合には1日前の日付とする

    } else {
      $url = $this->getContainer()->getParameter('yahoo_api_url_shopping_download_request');
      $params = [
          'seller_id' => $this->sellerId
        , 'type' => $exportType
      ];

      $client->request('POST', $url, $params);
      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();

      $logger->info('Yahoo api response: ' . $response->getStatus() . ' : ' . $response->getContent());

      // 成功時： status code 200 ※それ以外(401, 403)はエラー。これだけで判定して問題ない。
      $dom = simplexml_load_string($response->getContent());
      if ($response->getStatus() != 200) {
        $errorMessage = '';
        foreach($dom->xpath('/Error') as $error) {
          $errorMessage .= $error->asXML();
        }

        throw new RuntimeException('エラー: ' . $errorMessage);
      }
      $logger->info(sprintf('Yahoo [%s] CSVダウンロードリクエスト送信成功', $exportTarget));
    }

    // ダウンロード準備待ち
    $limit = $requestTime->modify(sprintf('+ %d second', self::READY_WAIT_MAX_SECONDS));

    $logger->info(sprintf('Yahoo api wait download : %s - %s', $requestTime->format('Y-m-d H:i:s'), $limit->format('Y-m-d H:i:s')));
    $downloadFileName = null;
    $tryCount = 0;
    do {

      $now = new \DateTimeImmutable();
      $logger->info('Yahoo api request: ダウンロード準備完了通知API ' . ++$tryCount . '回目');

      $url = $this->getContainer()->getParameter('yahoo_api_url_shopping_download_list');
      $params = [
          'seller_id' => $this->sellerId
        , 'type' => $exportType
      ];

      $client->request('GET', $url . '?' . http_build_query($params));
      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();

      $logger->info('Yahoo api response: ' . $response->getStatus() . ' : ' . $response->getContent());

      // 成功時： status code 200 ※それ以外(401, 403)はエラー。これだけで判定して問題ない。
      $dom = simplexml_load_string($response->getContent());
      if (!$dom) { // 連続実行したら getContent() が空になったので、空の時は他と同じく保留
        sleep(self::READY_WAIT_WAIT_SECONDS);
        continue;
      }
      if ($response->getStatus() != 200) {
        $errorMessage = '';
        foreach($dom->xpath('/Error') as $error) {
          $errorMessage .= $error->asXML();
        }
        throw new RuntimeException('エラー: ' . $errorMessage);
      }

/*
        // FOR DEBUG
        $debugXml = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<ResultSet totalResultsAvailable="1" totalResultsReturned="1" firstResultPosition="1">
    <Result>
        <Type>1</Type>
        <FileName>data.csv</FileName>
        <CreateTime>2016-09-05T16:12:51+09:00</CreateTime>
    </Result>
</ResultSet>
EOD;
        $dom = simplexml_load_string($debugXml);
*/

      // ダウンロード準備完了APIは、Resultを複数持てる構成だが、1度の要求の結果ファイルが分割されたりはしない様子。
      // （おそらく過去の要求のものが残っている）
      // このため、以後の処理は1ファイルヒットしたら終了、で問題ない
      $list = $dom->xpath('/ResultSet/Result');
      if ($list) {

        foreach($list as $info) {
          if (!(string)$info->Type) {
            continue;
          }

          $logger->info(sprintf('%s : %s : %s', (string)$info->Type, (string)$info->CreateTime, (string)$info->FileName));
          // 違う種類のCSVならスキップ
          if ((string)$info->Type != $exportType) {
            continue;
          }

          // リクエスト日時より古いデータならスキップ
          $createTime = new \DateTime((string)$info->CreateTime);
          $createTime->setTimezone(new \DateTimeZone('Asia/Tokyo')); // タイムゾーン付き書式で来るため、念のためJSTへ変換。（現状は、最初からJST）
          if ($createTime < $requestTime) {
            continue;
          }

          $downloadFileName = (string)$info->FileName;
          $logger->info('download is ready. : ' . $downloadFileName);
          break;
        }
      }

      // wait
      if (!$downloadFileName) {
        sleep(self::READY_WAIT_WAIT_SECONDS);
      }

    } while (!$downloadFileName && $limit > $now);

    if (!$downloadFileName) {
      throw new RuntimeException('ダウンロードファイルの準備が確認できませんでした。');
    }

    // ダウンロード実行
    $fileUtil = $this->getFileUtil();
    $fs = new FileSystem();
    if (!$fs->exists($outputDir)) {
      $fs->mkdir($outputDir);
    }

    $outputPath = sprintf('%s/%s', $outputDir, $downloadFileName);
    $fp = fopen($outputPath, 'wb');
    $guzzleClient = new GuzzleClient(
      [
        'curl' => [ CURLOPT_FILE => $fp ]
      ]
    );
    $client->setClient($guzzleClient);

    $logger->info('Yahoo api request: ダウンロード実行 ');

    $url = $this->getContainer()->getParameter('yahoo_api_url_shopping_download_submit');
    $params = [
        'seller_id' => $this->sellerId
      , 'type' => $exportType
    ];

    $client->request('GET', $url . '?' . http_build_query($params));
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $logger->info('Yahoo api response: ' . $response->getStatus());

    // 成功時： status code 200 ※それ以外(401, 403)はエラー。これだけで判定して問題ない。
    if ($response->getStatus() != 200) {
      $dom = simplexml_load_file($outputPath);
      $errorMessage = '';
      foreach($dom->xpath('/Error') as $error) {
        $errorMessage .= $error->asXML();
      }

      // エラーであれば作成されたファイル・ディレクトリを削除
      if ($fs->exists($outputPath)) {
        $fs->remove($outputPath);
        $finder = new Finder();
        if ($finder->in(dirname($outputPath))->files()->count() == 0) {
          $fs->remove(dirname($outputPath));
        }
      }
      throw new RuntimeException('エラー: ' . $errorMessage);
    }
    // DB記録＆通知処理
    // チェック機能のため、サブ2にファイル名、サブ3に行数、ファイルサイズを登録(JSON)
    $fileInfo = $fileUtil->getTextFileInfo($outputPath);
    $result = [];
    $result[] = 
        [
          'サイズ' => $fileInfo['size']
          , '行数' => $fileInfo['lineCount']
          , 'ファイル名' => $fileInfo['basename']
        ];
    $logger->info(sprintf('Yahoo [%s] : CSVファイルのダウンロードを完了しました。 %s', $exportTarget, $outputPath));
    
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    return $result;
  }


}
