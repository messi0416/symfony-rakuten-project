<?php
/**
 * SHOPLIST スピード便在庫ロケーション更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateShoplistProductStockSpeedCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-shoplist-product-stock-speed')
      ->setDescription('SHOPLIST から在庫一覧をダウンロードし、スピード便在庫の商品ロケーションを更新する')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイルパス指定（ダウンロードをskip）')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('SHOPLISTスピード便在庫のロケーション更新処理を開始しました。');

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
    $logExecTitle = 'SHOPLISTスピード便在庫更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      $saveDir = null;

      // ディレクトリ指定があればダウンロードはスキップ
      $outputPath = $input->getOption('data-path');
      if (!$outputPath) {

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

        $saveDir = $this->getFileUtil()->getWebCsvDir() . '/Shoplist/Import';

        $client = $webAccessUtil->getWebClient();
        $webAccessUtil->shoplistLogin($client);

        // 商品在庫検索画面
        $logger->info($logExecTitle . ' SHOPLIST 商品在庫検索画面へ遷移');
        $crawler = $client->request('GET', '/shopadmin/inventory/InventoryProductSearch/');
        $status = $client->getResponse()->getStatus();
        $uri = $client->getRequest()->getUri();
        if ($status !== 200) {
          throw new RuntimeException($logExecTitle . ' login error!! [' . $status . '][' . $uri . ']');
        }

        // ダウンロード
        $logger->info($logExecTitle . ' SHOPLIST 商品在庫検索画面 フォーム取得');
        $form = $crawler->selectButton('検索')->form();

        // 販売中 在庫一覧ダウンロード
        $form['publish_flg_with_only'] = '0';
        $form['speed_stock'] = 'on'; // 「スピード便在庫あり」
        $form['is_csv_output'] = 'on'; // 「CSVに出力する」

        $logger->info($logExecTitle . ' SHOPLIST 商品在庫検索画面 CSVダウンロード実行');
        $client->submit($form);

        /** @var Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();
        $logger->info($logExecTitle . ' SHOPLIST 商品在庫検索画面 CSVダウンロードレスポンス取得');
        $contentType = $response->getHeader('Content-Type');
        if ($status !== 200 || strpos($contentType, 'application/vnd.ms-excel') === false) {
          throw new RuntimeException('shoplist csv download error!! [' . $status . '][' . $uri . '][' . $contentType . ']');
        }

        // ファイル保存
        $fileName = sprintf('stock_speed_%s.csv', date('YmdHis'));
        $path = $saveDir . '/' . $fileName;

        $fs = new FileSystem();
        $file = new \SplFileObject($path, 'w'); // 上書き
        $bytes = $file->fwrite(mb_convert_encoding($response->getContent(), 'UTF-8', 'SJIS-WIN'));

        if (!$fs->exists($path) || ! $bytes) {
          @$fs->remove($path);
          throw new RuntimeException($logExecTitle . ' can not save csv file. [ ' . $path . ' ][' . $bytes . ']');
        }
        $logger->info($logExecTitle . ' SHOPLIST 商品在庫検索画面 CSVダウンロード成功 [' . $path . '][' . $bytes . ']');

        // DB記録＆通知処理
        $fileInfo = $this->getFileUtil()->getTextFileInfo($path);
        $info = [
            'size' => $fileInfo['size']
          , 'lineCount' => $fileInfo['lineCount']
        ];

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了')->setInformation($info));

        $outputPath = $path;
      }

      // ====================================================
      // 取込処理を実行
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));

      $fs = new FileSystem();
      if (!$outputPath || !$fs->exists($outputPath)) {
        throw new \RuntimeException($logExecTitle . ' SHOPLIST 在庫一覧ファイルが見つかりません。');
      }

      $info = [
          'count_on' => 0
        , 'count_off' => 0
      ];

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $dbMain->query("TRUNCATE tb_shoplist_product_stock_speed");

      $info['count'] = $this->importCsvData($outputPath, 'on');
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));

      // ====================================================
      // FBA仮想倉庫 在庫ロケーション更新
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'SHOPLIST倉庫ロケーション更新処理', '開始'));
      $this->updateShoplistProductSpeedLocation($this->account);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'SHOPLIST倉庫ロケーション更新処理', '終了'));


      $logger->addDbLog($logger->makeDbLog($logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('SHOPLISTスピード便在庫のロケーション更新処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param $importPath
   * @param string $display 倉庫設定 'on'|'off'
   * @return int
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importCsvData($importPath, $display = 'on')
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
      IGNORE INTO TABLE tb_shoplist_product_stock_speed
      FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
      LINES TERMINATED BY '\\n'
      IGNORE 1 LINES
      (
        @1 -- 商品公開日
        , @2 -- ブランドコード
        , @3 -- 商品管理番号
        , @4 -- 商品番号
        , @5 -- 卸品番
        , @6 -- JANコード
        , @7 -- ブランドバーコード
        , @8 -- 商品名
        , @9 -- 横軸
        , @10 -- 横軸子番号
        , @11 -- 縦軸
        , @12 -- 縦軸子番号
        , @13 -- メーカー在庫数
        , @14 -- CROOZ倉庫在庫数
        , @15 -- 引当数
        , @16 -- 未入荷数
        , @17 -- 販売可能数
        , @18 -- スピード便在庫数
        , @19 -- 未公開スピード便在庫数
        , @20 -- 納入予定数
        , @21 -- 納入予定日
        , @22 -- 予約商品入荷時期
        , @23 -- 表示価格（税抜）
        , @24 -- 状態
        , @25 -- 倉庫指定
        , @26 -- 承認状態
      )
      SET 
        商品公開日 = @1
        , 商品管理番号 = @3
        , 商品番号 = @4
        , 卸品番 = @5
        , JANコード = @6
        , 商品名 = @8
        , 横軸 = @9
        , 縦軸 = @11
        , メーカー在庫数 = @13
        , CROOZ倉庫在庫数 = @14
        , 引当数 = @15
        , 未入荷数 = @16
        , 販売可能数 = @17
        , スピード便在庫数 = @18
        , 未公開スピード便在庫数 = @19
        , 納入予定数 = @20
        , 納入予定日 = @21
        , 予約商品入荷時期 = @22
        , 販売価格（税抜） = @23;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $importPath);
    $stmt->execute();

    // 実行後 行数
    $stmt = $dbMain->prepare('SELECT COUNT(*) FROM tb_shoplist_product_stock_speed');
    $stmt->execute();
    $count = $stmt->fetchColumn(0);
    return $count;
  }

  /**
   * 商品在庫情報 SHOPLIST倉庫 スピード便在庫更新処理
   * @param SymfonyUsers|null $account
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  public function updateShoplistProductSpeedLocation($account = null)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();
    $locationCode = 'SHOPLIST-AUTO';

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
    $warehouse = $repoWarehouse->find(TbWarehouseRepository::SHOPLIST_WAREHOUSE_ID); // SHOPLISTロジ（SPEED便）
    if (!$warehouse) {
      throw new \RuntimeException('no shoplist_speed warehouse.');
    }
    $location = $repoLocation->getByLocationCode($warehouse->getId(), $locationCode);
    
    if(!empty($location)){
         // 値のリセット（SHOPLIST仮想倉庫 在庫全削除）
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
    // ※ここで作成されたロケーションの更新日時を、SHOPLISTスピード便在庫数取込日時としてSHOPLISTスピード便出荷数集計処理で参照
    $location = $repoLocation->createNewLocation($warehouse->getId(), $locationCode, 'SHOPLIST');

    // productchoiceitems に存在するデータのみをINSERT する。
    // このとき、相互トリガによりエラーとなるため、一時テーブルを経由する。
    // 一時テーブルへインポート
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_shoplist_speed_location_products");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_work_shoplist_speed_location_products (
          ne_syohin_syohin_code VARCHAR(50) NOT NULL DEFAULT 0 PRIMARY KEY
        , stock INTEGER NOT NULL DEFAULT 0
        , position INTEGER NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET utf8
      SELECT
          s.卸品番 AS ne_syohin_syohin_code
        , SUM(s.スピード便在庫数) AS stock
        , 999999 AS position
      FROM tb_shoplist_product_stock_speed s
      INNER JOIN tb_productchoiceitems pci ON s.卸品番 = pci.ne_syohin_syohin_code
      WHERE s.スピード便在庫数 > 0
      GROUP BY s.卸品番
      ORDER BY s.卸品番
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
      FROM tmp_work_shoplist_speed_location_products
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':locationId', $location->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // ロケーション変更履歴 保存
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $repoLocation->saveLocationChangeLogSummary(
        $dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_SHOPLIST_UPDATE_LOCATION
        , ($account ? $account->getUsername(): 'BatchSV02'), $actionKey);

    $dbMain->commit();
  }

  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '"商品公開日","ブランドコード","商品管理番号","商品番号","卸品番","JANコード","ブランドバーコード","商品名",'
        . '"横軸","横軸子番号","縦軸","縦軸子番号","メーカー在庫数","CROOZ倉庫在庫数","引当数","未入荷数","販売可能数",'
        . '"スピード便在庫数","未公開スピード便在庫数","納入予定数","納入予定日","予約商品入荷時期","表示価格（税抜）","状態","倉庫指定",'
        . '"承認状態"';

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);
    return (trim($line) === $validLine);
  }

}
