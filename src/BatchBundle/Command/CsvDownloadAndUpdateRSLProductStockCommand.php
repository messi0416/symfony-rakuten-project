<?php
/**
 * 楽天スーパーロジ倉庫 在庫テーブル更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateRSLProductStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-rsl-product-stock')
      ->setDescription('楽天スーパーロジ倉庫から在庫一覧をダウンロードし、楽天スーパーロジ在庫比較テーブルを更新する')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイルパス指定（ダウンロードをskip）')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('楽天スーパーロジ在庫比較テーブルの更新処理を開始しました。');

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
    $logExecTitle = '楽天スーパーロジ在庫比較テーブル更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      // ディレクトリ指定があればダウンロードはスキップ
      $outputPath = $input->getOption('data-path');
      if (!$outputPath) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

        $client = $webAccessUtil->getWebClient();

        // NEログイン・メインページへの遷移
        $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

        // CSVファイルダウンロードリンク クリック
        try {
          $csvLink = $crawler->filter('ul#ne_topmenu')->selectLink('全在庫情報')->link();
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
        // csrfトークン取得
        $csrfTokenInfo = $webAccessUtil->getNeCsrfTokenInfo($crawler);

        $status = $client->getResponse()->getStatus();
        $uri = $client->getRequest()->getUri();
        $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($client->getResponse());
        if ($status !== 200 || $isInvalidAccess || !preg_match('!.next-engine.(?:org|com)/Userrfc/inventoryPageView!', $uri)) {
          /** @var \Symfony\Component\BrowserKit\Response $response */
          $response = $client->getResponse();
          $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
          file_put_contents($scrapingResponseDir . '/csvDownloadRSLStockUserrfcInventoryPageView.html', $response->getContent());
          $message = $isInvalidAccess ? '不正アクセスエラー' : '';
          throw new RuntimeException("move to csv download page error!! $message [" . $status . '][' . $uri . ']');
        }
        $logger->info('全在庫情報画面へ遷移成功');
        // $logger->info(print_r($crawler->html(), true));

        $button = $crawler->selectButton('全在庫情報をダウンロード');
        if (!$button->count()) {
          throw new \RuntimeException('現在、NextEngine CSVダウンロードができない状態です。時間をおいて再度実行してみてください。');
        }

        // csrfトークンをform追加するために、用意したhtmlからcrawlerを新規作成する
        $downloadFileKickCrawler = new Crawler($this->createHtmlDownloadFileKick(), $uri);
        $form = $downloadFileKickCrawler -> selectButton('全在庫情報をダウンロード') -> form();

        $form['hid_inventory_type'] = 'search_inventory';
        $form['csrf_token'] = $csrfTokenInfo['value']; // responseそのままだとform内にcsrftokenが書き込まれていないため追記

        $client->submit($form);

        /** @var \Symfony\Component\BrowserKit\Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();
        $contentLength = intval($response->getHeader('Content-Length'));
        $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
        if ($status !== 200 || $isInvalidAccess || strpos($response->getHeader('Content-Type'), 'application/octet-stream') === false || !$contentLength) {
          $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
          file_put_contents($scrapingResponseDir . '/next_engine_rsl_product_stock.html', $response->getContent());
          $message = $isInvalidAccess ? '不正アクセスエラー' : '';
          $logger->warn("楽天スーパーロジ在庫比較テーブル更新処理 $message [" . $status . '][' . $uri . ']');
          
          $crawler = $client->click($csvLink);
          $button = $crawler->selectButton('結果をダウンロード');
          if (!$button->count()) {
            throw new \RuntimeException('現在、NextEngine CSV結果ダウンロードができない状態です。時間をおいて再度実行してみてください。');
          }
          // csrfトークン取得
          $csrfTokenInfo = $webAccessUtil->getNeCsrfTokenInfo($crawler);
          $client->request('post', 'https://main.next-engine.com/Userrfc/searchInventoryres', ['hid_request_id' => $button->attr('name'), 'csrf_token' => $csrfTokenInfo['value']]);
          $response = $client->getResponse();
          $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();
          $contentLength = intval($response->getHeader('Content-Length'));
          if ($status !== 200 || $isInvalidAccess || strpos($response->getHeader('Content-Type'), 'application/octet-stream') === false || !$contentLength) {
            file_put_contents($scrapingResponseDir . '/next_engine_rsl_product_stock2.html', $response->getContent());
            $message = $isInvalidAccess ? '不正アクセスエラー' : '';
            throw new RuntimeException("can not download csv error!! $message [" . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . ']');
          }

        }
        $logger->info('在庫一覧CSVダウンロードレスポンス取得');

        $fs = new FileSystem();
        $exportDir = sprintf('%s/RSL/Import', $this->getFileUtil()->getWebCsvDir());
        if (!$fs->exists($exportDir)) {
          $fs->mkdir($exportDir, 0755);
        }

        $exportPath = sprintf('%s/stock_%s.csv', $exportDir, (new \DateTime())->format('YmdHis'));
        if ($fs->exists($exportPath)) {
          throw new RuntimeException('same csv name exists error!! [' . $exportPath . ']');
        }

        $file = new \SplFileObject($exportPath,'wb');
        $bytes = $file->fwrite(mb_convert_encoding($response->getContent(), 'UTF-8', 'SJIS-WIN'));
        if (!$fs->exists($exportPath) || ! $bytes) {
          @$fs->remove($exportPath);
          throw new RuntimeException('can not save csv file. [ ' . $exportPath . ' ][' . $bytes . '][' . $contentLength . ']');
        }

        $logger->info('在庫一覧CSV出力成功。[' . $exportPath . ']');

        // DB記録＆通知処理
        // チェック機能のため、サブ2にファイル名、サブ3に行数、ファイルサイズを登録(JSON)
        $fileInfo = $this->getFileUtil()->getTextFileInfo($exportPath);
        $info = [
            'サイズ' => $fileInfo['size']
          , '行数' => $fileInfo['lineCount']
          , 'ファイル名' => $fileInfo['basename']
        ];
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了')->setInformation($info));
        $logger->logTimerFlush();

        $outputPath = $exportPath;
      }

      // ====================================================
      // 取込処理を実行
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));

      $fs = new FileSystem();
      if (!$outputPath || !$fs->exists($outputPath)) {
        throw new \RuntimeException('楽天スーパーロジ倉庫 在庫一覧ファイルが見つかりません。');
      }

      $info = [
          'count' => 0
      ];

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $dbMain->query("TRUNCATE tb_rsl_product_stock");

      $info['count'] = $this->importCsvData($outputPath);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));

      // ====================================================
      // FBA仮想倉庫 在庫ロケーション更新
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '楽天スーパーロジ倉庫ロケーション更新処理', '開始'));
      $this->updateRSLProductLocation($this->account);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '楽天スーパーロジ倉庫ロケーション更新処理', '終了'));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));

      $logger->logTimerFlush();

      $logger->info('楽天スーパーロジ倉庫 在庫比較テーブルの更新処理を終了しました。');

      return 0;

    } catch (\Exception $e) {
      $logger->error("楽天スーパーロジ在庫比較テーブル更新処理エラー： $e");
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e)
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param $importPath
   * @return int
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importCsvData($importPath)
  {
    $logger = $this->logger;
    $fs = new FileSystem();
    if (!$fs->exists($importPath)) {
      throw new RuntimeException('no data file!! [' . $importPath . ']');
    }

    // 書式チェック
    if (!$this->validateCsv($importPath)) {
      throw new \RuntimeException('ダウンロードされたファイルの書式が違います。処理を終了しました。');
    }

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :importPath
      IGNORE INTO TABLE tb_rsl_product_stock
      FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
      LINES TERMINATED BY '\\r\\n'
      IGNORE 1 LINES
      (
        `商品コード`
       ,`販売可能在庫数`
       ,`非販売在庫数`
       ,`ベンダーダメージ在庫数`
       ,`カスタマダメージ在庫数`
       ,`倉庫過失ダメージ在庫数`
       ,`配送ダメージ在庫数`
       ,`初期不良ダメージ在庫数`
       ,`出荷期限切れダメージ在庫数`
       ,`その他ダメージ在庫数`
       ,`作業中在庫数`
       ,`その他在庫数`
      )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $importPath);
    $stmt->execute();

    // 実行後 行数
    $stmt = $dbMain->prepare('SELECT COUNT(*) FROM tb_rsl_product_stock');
    $stmt->execute();
    $count = $stmt->fetchColumn(0);

    return $count;
  }

  /**
   * 商品在庫情報 楽天スーパーロジ倉庫 在庫更新処理
   * @param string $shop
   * @param SymfonyUsers|null $account
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  public function updateRSLProductLocation($account = null)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();
    $locationCode = 'RSL-AUTO';
/*
    // いらない？
    $fbaMultiEnabled = $commonUtil->getSettingValue('FBA_MULTI_ENABLED');
    if ($fbaMultiEnabled == 0) {
      $this->getLogger()->info('各種設定 FBA_MULTI_ENABLED によりFBA仮想倉庫のロケーション更新はスキップ');
      return;
    }
*/

    $dbMain->beginTransaction();

    /** @var TbLocationRepository $repoLocation */
    $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

    // （履歴用）アクションキー 作成＆セット
    $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbWarehouse $warehouse */
    $warehouse = $repoWarehouse->find(TbWarehouseRepository::RSL_WAREHOUSE_ID); // 楽天スーパーロジ
    if (!$warehouse) {
      throw new \RuntimeException('no RSL warehouse.');
    }

    $location = $repoLocation->getByLocationCode($warehouse->getId(), $locationCode);
    
    if(!empty($location)){
         // 値のリセット（RSL仮想倉庫 在庫全削除）
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
    $location = $repoLocation->createNewLocation($warehouse->getId(), $locationCode, 'RSL');

    // productchoiceitems に存在するデータのみをINSERT する。
    // このとき、相互トリガによりエラーとなるため、一時テーブルを経由する。
    // 一時テーブルへインポート
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_rsl_location_products");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_work_rsl_location_products (
          ne_syohin_syohin_code VARCHAR(50) NOT NULL DEFAULT 0 PRIMARY KEY
        , stock INTEGER NOT NULL DEFAULT 0
        , position INTEGER NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET utf8
      SELECT
          s.商品コード AS ne_syohin_syohin_code
        , SUM(s.販売可能在庫数) AS stock
        , 999999 AS position
      FROM tb_rsl_product_stock s
      INNER JOIN tb_productchoiceitems pci ON s.商品コード = pci.ne_syohin_syohin_code
      WHERE s.販売可能在庫数 > 0
      GROUP BY s.商品コード
      ORDER BY s.商品コード
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
      FROM tmp_work_rsl_location_products
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':locationId', $location->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // ロケーション変更履歴 保存
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_RSL_UPDATE_LOCATION, ($account ? $account->getUsername(): 'BatchSV02'), $actionKey);

    $dbMain->commit();
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '"商品コード","販売可能在庫数","非販売在庫数","ベンダーダメージ在庫数","カスタマダメージ在庫数","倉庫過失ダメージ在庫数","配送ダメージ在庫数","初期不良ダメージ在庫数","出荷期限切れダメージ在庫数","その他ダメージ在庫数","作業中在庫数","その他在庫数"';

    $fp = fopen($path, 'r');
    $line = fgets($fp);

    fclose($fp);

    return (trim($line) === $validLine);
  }

    /**
   * 2023/1よりcsrf_tokenがhtml描画後、jsによってform内に追加されるようになった。
   * responseへの要素追加がcrawlerやformのfunctionで行えないため、htmlを直接作成する形で対応する
   */
  private function createHtmlDownloadFileKick()
  {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
    <title></title>
    <meta charset="utf-8">
    <base href="https://main.next-engine.com/">
    </head>
    <body>
    <form id="search_inventory_form" name="search_inventory_form" action="/Userrfc/searchInventory" method="post" class="form-horizontal">
        <div class="row-fluid">
            <div class="block span12" id="out">
                <p class="block-heading">&nbsp;全在庫情報のダウンロード</p>
                <div class="block-body">
                    <div class="row-fluid">
                        <div class="block span12">
                            <p class="block-heading">全在庫情報CSVファイルのダウンロード</p>
                            <div class="block-body">
                                                                                                                                
                                <div class="alert alert-error" id="msg">
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    <p>【処理中】<br>在庫情報要求中です。<br>結果一覧から結果取得して下さい。</p>
                                </div>
                                
                                <p>楽天スーパーロジ（倉庫）にある商品の在庫情報をCSV形式でダウンロードします。</p>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-primary" id="search_inventory_btn" onclick="search_inventory_dl();">
                                        全在庫情報をダウンロード<i class="icon-download-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="hid_request_id" name="hid_request_id" value="">
        <input type="hidden" name="hid_inventory_type" id="hid_inventory_type" value="">
        <input type="hidden" name="csrf_token" value="1234567890">
    </form>
    </body>
    </html>
HTML;
    
    return $html;
  }
}
