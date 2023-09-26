<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use Goutte\Client;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\GoutteClientCustom;
use MiscBundle\Util\WebAccessUtil;
use MiscBundle\Util\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;


class CsvDownloadStockListOriginalCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  /** 検索タイプ：在庫一覧_在庫あり */
  const SEARCH_TYPE_IN_STOCK = 1;
  /** 検索タイプ：在庫一覧 */
  const SEARCH_TYPE_ALL_STOCKS = 2;

  /** API取得件数制限（１リクエストあたり） */
  /** 増やしすぎると指数関数的に処理が重くなるとの記述がAPIにあるため、上限に注意する事。 */
  const API_LIMIT = 5000;

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
      ->setName('batch:csv-download-stock-list-original')
      ->setDescription('login to NextEngine Web site and download ORIGINAL stock CSV file.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ名')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('type', null, InputOption::VALUE_OPTIONAL, '取得データ。all:全件, stock:在庫ありのみ', 'all')
      ;
  }

  /**
   * NEの在庫情報を取得する
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->fileUtil = $this->getContainer()->get('misc.util.file');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger->initLogTimer();

    $logger->info('在庫更新処理（オリジナルCSV）を開始しました。');

    // DB記録＆通知処理
    $logExecTitle = '在庫データ取込（オリジナルCSV） CSVダウンロード';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
        
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      // 保存ディレクトリの準備
      $fs = new FileSystem();
  
      $saveDir = $input->getOption('export-dir');
      if (!$saveDir) {
        $container = $this->getContainer();
        $rootDir = $container->get('kernel')->getRootDir();
        $dataDir = dirname($rootDir) . '/data/stocks';
        if (!$fs->exists($dataDir)) {
          $fs->mkdir($dataDir, 0755);
        }
        $saveDir = $dataDir . '/' . (new \DateTime())->format('YmdHis');
      }
  
      if ($fs->exists($saveDir)) {
        throw new RuntimeException('duplicate save directory.');
      }
      $fs->mkdir($saveDir, 0755);

      // 検索タイプの取得
      $searchType = $input->getOption('type');
      // ターゲット環境の取得
      $targetEnv = $input->getOption('target-env');

      // 「各種設定」参照
      $commonUtil = $this->getDbCommonUtil();
      $isScrapingEnable = $commonUtil->getSettingValue('NE_SCRAPING_ENABLE_STOCK_LIST');

      if($isScrapingEnable){
        // スクレイピング実行
        $resultStr = $this->searchStockByScraping($saveDir, $targetEnv, $webAccessUtil, $logger);
      }else{
        // API実行
        $resultStr = $this->searchStockByApi($saveDir, $searchType, $logger);
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($resultStr));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "在庫データ オリジナルCSVダウンロードでエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * NEの在庫情報をスクレイピングで取得する
   *
   * @param string $saveDir 保存ディレクトリ
   * @param string $targetEnv ターゲット環境（prod|test）
   * @param WebAccessUtil $webAccessUtil Webクライアント
   * @param BatchLogger $logger
   * @return string $resultStr 取得件数の情報
   */
  private function searchStockByScraping($saveDir, $targetEnv, $webAccessUtil, $logger) {
    $client = $webAccessUtil->getWebClient();

    // NEログイン・メインページへの遷移
    $crawler = $webAccessUtil->neLogin($client, 'api', $targetEnv); // 必要なら、アカウント名を追加して切り替える

    // CSVファイルダウンロードリンク クリック
    try {
      $csvLink = $crawler->filter('ul#ne_topmenu')->selectLink('CSVファイル')->link();
      $crawler = $client->click($csvLink);

    } catch (\InvalidArgumentException $e) {
      $uri = $client->getRequest()->getUri();

      // 「重要なお知らせ」が差し込まれる場合があり、直接URLを叩くことにします。
      if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
        $uri = $match[1] . '/Userinspection2';
        $crawler = $client->request('get', $uri);
      } else {
        throw $e;
      }
    }

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($client->getResponse());
    if ($status !== 200 || $isInvalidAccess || !preg_match('!.next-engine.(?:org|com)/Userinspection2!', $uri)) {
      $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/next_engine_stock_list_original.html', $response->getContent());
      $message = $isInvalidAccess ? '不正アクセスエラー' : '';
      throw new RuntimeException("move to csv download page error!! $message [ $status ][ $uri ]");
    }
    $logger->info('CSVダウンロード画面へ遷移成功');
    // $logger->info(print_r($crawler->html(), true));

    $button = $crawler->selectButton('ダウンロード');
    if (!$button->count()) {
      throw new \RuntimeException('現在、NextEngine CSVダウンロードができない状態です。時間をおいて再度実行してみてください。');
    }

    // 「在庫一覧」ダウンロード処理
    $csvTypeName = $input->getOption('type') == 'stock' ? '【オリジナル】在庫一覧_在庫あり' : '【オリジナル】在庫一覧';
    $xpath = sprintf('descendant-or-self::option[contains(concat(\' \', normalize-space(string(.)), \' \'), %s)]', Crawler::xpathLiteral($csvTypeName));
    $selectOption = $crawler->filter('select[name="s_id"]')->filterXPath($xpath);
    if (!$selectOption->attr('value')) {
      throw new RuntimeException('在庫一覧CSV（オリジナル）が選択できませんでした。');
    }
    $logger->info('在庫一覧CSV（オリジナル）選択成功、ダウンロード試行 : ' . $csvTypeName);


    $logger->info('プルダウンID' . $selectOption->attr('value'));
    $logger->info('受注データCSV選択成功、ダウンロード試行');

    // 画面からフォームパラメータを取得する
    // ※ フォームが JavaScriptで変更されるため、Goutte機能の form()->submit()はできない。

    $sessionCookie = $client->getCookieJar()->get('company_login');
    if (!$sessionCookie) {
      throw new RuntimeException('ログインセッションのCookie取得に失敗しました。');
    }

    $params = [
        's_id' => $selectOption->attr('value') // CSV種類
      , 'moji_code' => 'SJIS'
      , 'company_login' => $sessionCookie->getValue()
    ];


    $mainHost = null;
    $uri = $client->getRequest()->getUri();
    if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
      $mainHost = $match[1];
    } else {
      throw new RuntimeException('メイン機能URLのホスト名の取得に失敗しました。');
    }
    $action = $mainHost . '/Userinspection2/oddl'; // URLもJavaScritpによる書き換え。直接指定する。

    // 直接 URLを指定してPOST
    $crawler = $client->request('POST', $action, $params);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $requestUri = $client->getRequest()->getUri();
    $contentType = $response->getHeader('Content-Type');
    $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($client->getResponse());

    if ($status !== 200 || $isInvalidAccess) {
      $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/next_engine_stock_list_original.html', $response->getContent());
      $message = $isInvalidAccess ? '不正アクセスエラー' : '';
      throw new RuntimeException("can not download csv error!! $message [ $status ][ $requestUri ][" . $response->getHeader('Content-Type') . ']');
    }


    // 1件ずつダウンロード
    $logger->info('CSVダウンロード開始');

    $downloadResults = [];

    // 取得件数に応じて、直接ダウンロードになるかプルダウン選択になるか分岐する。
    // ただし、現在は件数的にプルダウン決め打ち（5,000件以上）
    if (strpos($contentType, 'application/octet-stream') !== false) {
      $logger->info('ダウンロード開始： 1ファイルのみ保存処理');

      // ファイルをダウンロード保存
      $this->saveDownloadFile($logger, $client, $crawler, $fs, $saveDir, $downloadResults, 'single file');

    } else if (strpos($contentType, 'text/html') !== false) {
      try {
        $logger->info('ダウンロード開始： 分割ファイル全保存');
        $csvSelectOptions = $crawler->filter('#file_name option');
        $logger->info($csvSelectOptions->html());

      } catch (\Exception $e) {
        throw new RuntimeException($e->getMessage());
      }

      $logger->info('件数選択 CSVダウンロード画面へ遷移成功');

      // ここもJavaScriptでフォーム値がりがりされてる
      $sessionCookie = $client->getCookieJar()->get('company_login');
      if (!$sessionCookie) {
        throw new RuntimeException('ログインセッションのCookie取得に失敗しました。');
      }

      $form = $crawler->selectButton('上記の内容でダウンロード')->form();
      $action = $mainHost . '/Userinspection2/oddlexe';
      $params = [
          'moji' => $form->get('moji')->getValue()
        , 'params' => $form->get('params')->getValue()
        , 'company_login' => $sessionCookie->getValue()
      ];

      $csvSelectOptions->each(function(Crawler $option) use ($logger, $client, $fs, $action, $params, $saveDir, &$downloadResults) {
        sleep(1);
        $params['file_name'] = $option->attr('value');

        $logger->info($action);
        $logger->info(print_r($params, true));
        $logger->info('... ' . $params['file_name']);

        // 直接 URLを指定してPOSTし、結果ファイルをダウンロード保存。「暫くしてから再度実行してください。」の場合は最大5回ループ
        $i = 0;
        do {
          $i++;
          $crawler = $client->request('POST', $action, $params);
          try {
            $this->saveDownloadFile($logger, $client, $crawler, $fs, $saveDir, $downloadResults, $params['file_name']);
            break;
          } catch (\RuntimeException $e) {
            if ((strpos($e->getMessage(), '暫くしてから再度実行してください。') === false) || $i > 5) {
              throw $e;
            }
            $logger->info("CSVダウンロードが他処理待ちのため、リトライ:" . $params['file_name']);
            sleep(10); // 10秒待機
          }
        } while (true);
      });

    } else {
      throw new RuntimeException('unknown response');
    }

    $logger->info('在庫データ（オリジナル）CSV出力成功。[' . $saveDir . ']');

    $resultCount = count($downloadResults);
    $resultLines = 0;
    foreach($downloadResults as $info) {
      $resultLines += $info['lines'];
    }
    $resultStr = sprintf('file: %d / lines: %d', $resultCount, $resultLines);

    return $resultStr;
  }

  /**
   * ファイル1件ダウンロード＆保存
   * @param BatchLogger $logger
   * @param GoutteClientCustom $client
   * @param Crawler $crawler
   * @param FileSystem $fs
   * @param $saveDir
   * @param $downloadResults
   * @param $fileName エラー時のログ用。データを特定する情報が入る。 [175000, 5000] といった形の文字列が入る
   */
  private function saveDownloadFile($logger, $client, $crawler, $fs, $saveDir, &$downloadResults, $fileName)
  {
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $requestUri = $client->getRequest()->getUri();
    $contentType = $response->getHeader('Content-Type');
    $contentLength = intval($response->getHeader('Content-Length'));

    if ($status !== 200 || strpos($contentType, 'application/octet-stream') === false || !$contentLength) {
      if (strpos($contentType, 'text/html') !== false) {
        $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
        file_put_contents($scrapingResponseDir . '/next_engine_stock_list_original_download.html', $crawler->html());
        $logger->error("在庫CSVダウンロードでエラー発生[$fileName]");
        if (strpos($crawler->html(), '暫くしてから再度実行してください。') !== false) {
          throw new RuntimeException("暫くしてから再度実行してください。 [$status][$requestUri][$contentType][$fileName]");
        }
      }
      throw new RuntimeException("can not download csv error!! [$status][$requestUri][$contentType][$fileName]");
    }

    $logger->info('在庫データ（オリジナル） CSVダウンロードレスポンス取得');
    $logger->info($response->getHeader('Content-Disposition'));

    $fileName = preg_match('/filename="([a-zA-Z0-9_-]+.csv)"/', $response->getHeader('Content-Disposition'), $match)
      ? $match[1]
      : sprintf('orig_stock_%s.csv', date('YmdHis00000000'));


    $path = $saveDir . '/' . $fileName;
    if ($fs->exists($path)) {
      throw new RuntimeException("same csv name exists error!! [$path][[$fileName]]");
    }

    $file = new \SplFileObject($path, 'w'); // 上書き
    $bytes = $file->fwrite($response->getContent());

    if (!$fs->exists($path) || $bytes !== $contentLength) {
      @$fs->remove($path);
      throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . '][' . $contentLength . ']');
    }

    $fileInfo = $this->fileUtil->getTextFileInfo($path);
    $downloadResults[] = [
        'dir' => $saveDir
      , 'file' =>  $fileName
      , 'bytes' => $bytes
      , 'lines' => $fileInfo['lineCount']
    ];
  }

  /**
   * NEより在庫マスタ検索を行い、結果をCSVに出力する。
   *
   * @param string $saveDir 保存ディレクトリ
   * @param string $searchType 検索タイプ（stock:在庫あり, stock以外:全件）
   * @param BatchLogger $logger
   * @return string $resultStr 取得件数の情報
   */
  private function searchStockByApi($saveDir, $searchType, $logger)
  {
    $lines = 0; // 取得件数
    $offsetLines = 0; // APIで取得するデータのオフセット（5000件ごと）および総取得件数
    $fileNum = 0; // 出力したCSVファイル数

    $logger->info('在庫マスタ検索API　実行');

    do{
      // 「在庫一覧」ダウンロード処理
      $csvTypeName = $searchType === 'stock' ? self::SEARCH_TYPE_IN_STOCK : self::SEARCH_TYPE_ALL_STOCKS;
      $csvTypeName =  '【オリジナル】' . $csvTypeName; // ログフォーマットをスクレイピング側に揃える
      $logger->info($csvTypeName);

      if($searchType === 'stock'){
        $stocks = $this->searchStock(self::SEARCH_TYPE_IN_STOCK, $offsetLines); // 在庫一覧_在庫あり
      }else{
        $stocks = $this->searchStock(self::SEARCH_TYPE_ALL_STOCKS, $offsetLines); // 在庫一覧
      }
      
      // 例えば登録済みSKU数が 495,000 件など、前のリクエストでぴったりSKUが終わっていると、取得行のないリクエストが発生する。その場合ループ終了
      if (!$stocks) {
        break;
      }
      // CSVファイルに保存した取得件数を取得
      $lines = $this->saveStock($saveDir, $stocks);
      $offsetLines += $lines;
      // CSVファイル数カウント
      $fileNum ++;
    }while($lines >= self::API_LIMIT && $fileNum < 1000); // 1回の取得件数が、self::API_LIMIT未満になるまで繰り返す。$fileNumによる制限は無限ループ避け。

    $logger->info('在庫データ（オリジナル）CSV出力成功。');

    $resultStr = sprintf('file: %d / lines: %d', $fileNum, $offsetLines);
    return $resultStr;
  }

  /**
   * NEより在庫マスタ検索を行い、結果を返却する。
   * 指定された検索タイプに従い、在庫情報の検索を行う。
   *
   * @param int $searchType 検索タイプ。SEARCH_TYPE_IN_STOCK、SEARCH_TYPE_ALL_STOCKS、のどちらかを指定する。
   * @param int $offset 1回で取得できない場合のoffset。0指定でスキップなし。1指定で2件目から取得。必須
   * @return array APIで渡されるレスポンスのdataフィールド
   *
   * @see https://developer.next-engine.com/api/api_v1_master_stock/search 在庫マスタ検索 API仕様
   */
  private function searchStock($searchType, $offset)
  {
    $query = array() ;

    // 検索結果・絞り込み検索条件の整理

    $query['fields'] = implode(',', $this->receiveStockTargetField);
    if ($searchType === self::SEARCH_TYPE_IN_STOCK){ // 在庫一覧_在庫あり
      $query['stock_quantity-neq'] = 0;
    }
    $query['offset'] = $offset;
    $query['limit'] = self::API_LIMIT;
    
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
   * APIで取得した在庫データをCSVファイルに保存する。
   *
   * @param string $saveDir 保存ディレクトリ
   * @param array $stocks 在庫データ
   * @return integer $lines 取得件数
   */
  private function saveStock($saveDir, $stocks)
  {  
      // CSVファイルオープン('od_data'はスクレイピングのファイル名に合わせている)
      // ファイル名末尾の８桁の数字は出力ファイルが重複して消えない様にするための乱数です
      $fileName = sprintf('od_data%s_%s.csv', date('YmdHis'), str_pad((string)mt_rand(0,99999999), 8, "0", STR_PAD_LEFT));
      $path = $saveDir . '/' . $fileName;
      $fp = fopen($path, 'wb');
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->getContainer()->get('misc.util.string');      
      $bytes = 0;
      // ヘッダ
      $headerLine = $stringUtil->convertArrayToCsvLine(['商品コード','商品名','在庫数','引当数','フリー在庫数','予約在庫数','予約引当数','予約フリー在庫数','不良在庫数']);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";
      $bytes += fputs($fp, $headerLine);      // JSON -> CSV変換

      $lines = 0;
      foreach ($stocks as $item) {
        $contents = [
          '商品コード' => $item['stock_goods_id']
          , '商品名' => ''
          , '在庫数' => $item['stock_quantity']
          , '引当数' => $item['stock_allocation_quantity']
          , 'フリー在庫数' => $item['stock_free_quantity']
          , '予約在庫数' => $item['stock_advance_order_quantity']
          , '予約引当数' => $item['stock_advance_order_allocation_quantity']
          , '予約フリー在庫数' => $item['stock_advance_order_free_quantity']
          , '不良在庫数' => $item['stock_defective_quantity']
        ];
        $line = $stringUtil->convertArrayToCsvLine($contents);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        $bytes += fputs($fp, $line);

        // 行数
        $lines ++;
      }
      fclose($fp);

      $fs = new FileSystem();
      if (!$fs->exists($path) || ! $bytes) {
        @$fs->remove($path);
        throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . ']');
      }

      return $lines;
  }
}
