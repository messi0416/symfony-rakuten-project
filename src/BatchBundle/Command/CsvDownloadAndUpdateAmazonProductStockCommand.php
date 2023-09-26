<?php
/**
 * Amazon在庫比較テーブル更新処理
 * Amazon MWS 出品レポートのダウンロード＆取込（SKU, 価格, 在庫数）
 * ※リードタイムはダウンロードデータに含まれないため、前回出力時の値を保存・更新し続ける。
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\RuntimeException;


class CsvDownloadAndUpdateAmazonProductStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-amazon-product-stock')
      ->setDescription('Amazon MWS から出品レポートをダウンロードし、Amazon 在庫比較テーブルを更新する (.co.jp, .com 共通)')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイルパス指定（ダウンロードをskip）')
      ->addOption('report-id', null, InputOption::VALUE_OPTIONAL, 'リクエストおよび準備済みレポートID指定（リクエストをskip）')
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗: vogue|us_plusnao', AmazonMallProcess::SHOP_NAME_VOGUE)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('Amazon在庫比較テーブルの更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = 'Amazon在庫比較テーブル更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      $saveDir = null;

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述

      // 店舗指定
      $shop = $input->getOption('shop');
      if (!in_array($shop, [
          AmazonMallProcess::SHOP_NAME_VOGUE
        , AmazonMallProcess::SHOP_NAME_US_PLUSNAO
      ])) {
        throw new \RuntimeException('invalid shop name : [' . $shop . ']');
      }

      // ディレクトリ指定があればダウンロードはスキップ
      $outputPath = $input->getOption('data-path');
      if (!$outputPath) {

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

        $saveDir = $this->getFileUtil()->getWebCsvDir() . '/' . $mallProcess->getCsvDirName($shop) . '/Import/';
        $outputPath = $saveDir . '/' . (new \DateTimeImmutable())->format('YmdHis') . '.txt';

        $reportId = $input->getOption('report-id');
        if ($reportId) {
          // レポートID指定で取得
          $mallProcess->mwsGetReport($shop, $reportId, $outputPath);
        } else {
          // リクエスト～出力完了待ち～レポート取得
          $mallProcess->mwsGetStockReport($shop, $outputPath);
        }

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了'));
      }

      // ====================================================
      // 取込処理を実行 （ダウンロードが早いので分割せず処理してしまう）
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));

      $fs = new FileSystem();
      if (!$outputPath || !$fs->exists($outputPath)) {
        throw new \RuntimeException('Amazon出力レポートファイルが見つかりません。');
      }

      $info = $this->importCsvData($outputPath, $shop);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));

      // ====================================================
      // FBA在庫データ更新（FBA在庫情報 ダウンロード ＆ データ更新）
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '開始'));
      $result = $mallProcess->updateFbaProductStock($shop);
      if ($result['status'] == 'ng') {
        throw new \RuntimeException('FBA在庫更新処理中にエラー発生：' . $result['message']);
      }
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '終了'));

      // ====================================================
      // FBA仮想倉庫 在庫ロケーション更新
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '開始'));
      $result = $mallProcess->updateFbaMultiProductLocation($shop, $account);
      if($result['status'] === 'ng'){
        $logger->addDbLog(
            $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー')->setInformation($result['message'])
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
        );
      }
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '終了'));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Amazon在庫比較テーブルの更新処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param $importPath
   * @param string $shop
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importCsvData($importPath, $shop = AmazonMallProcess::SHOP_NAME_VOGUE)
  {
    $logger = $this->logger;
    $fs = new FileSystem();
    if (!$fs->exists($importPath)) {
      throw new RuntimeException('no data file!! [' . $importPath . ']');
    }

    // 書式チェック
    if (!$this->validateCsv($importPath, $shop)) {
      throw new \RuntimeException('ダウンロードされたファイルの書式が違います。処理を終了しました。');
    }

    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
    $stockTableName = $mallProcess->getProductStockTableName($shop);

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    // 新テーブルを作成し、データ挿入・リードタイム更新が済んだ後スワップ
    $dbMain->query("DROP TABLE IF EXISTS tb_amazon_product_stock_old");
    $dbMain->query("DROP TABLE IF EXISTS tb_amazon_product_stock_new");
    $dbMain->query("CREATE TABLE tb_amazon_product_stock_new LIKE `{$stockTableName}`");

    if ($shop === AmazonMallProcess::SHOP_NAME_VOGUE) {
      $dbMain->query("SET character_set_database=cp932"); // ダウンロードされるファイルがUTF-8の場合もある。（詳細不明）
      // ひとまずマルチバイトが入るのはスキップされる1行目のみなので、character_set の指定はなしでもよい？
    }
    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :importPath
      IGNORE INTO TABLE tb_amazon_product_stock_new
      FIELDS TERMINATED BY '\\t' ENCLOSED BY '' ESCAPED BY '"'
      LINES TERMINATED BY '\\r\\n'
      IGNORE 1 LINES
      (@出品者SKU,@ASIN,@価格,@数量,@法人価格,@数量割引のタイプ,@数量の下限1,@数量割引1,@数量の下限2,@数量割引2,@数量の下限3,@数量割引3,@数量の下限4,@数量割引4,@数量の下限5,@数量割引5,@ポイント)
      SET
          sku = @出品者SKU
        , asin = @ASIN
        , price = @価格
        , quantity = @数量
        , points = @ポイント
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $importPath);
    $stmt->execute();

    if ($shop === AmazonMallProcess::SHOP_NAME_VOGUE) {
      $dbMain->query("SET character_set_database=utf8");
    }

    // リードタイム更新
    $sql = <<<EOD
      UPDATE tb_amazon_product_stock_new N
      INNER JOIN `{$stockTableName}` S ON N.sku = S.sku
      SET N.lead_time = S.lead_time
EOD;
    $dbMain->query($sql);

    // 新旧テーブルスワップ
    $sql = <<<EOD
      RENAME TABLES
          `{$stockTableName}` TO tb_amazon_product_stock_old
        , tb_amazon_product_stock_new TO `{$stockTableName}`
EOD;
    $dbMain->query($sql);

    // 旧テーブル削除
    $dbMain->query("DROP TABLE IF EXISTS tb_amazon_product_stock_old");

    // ファイル削除 => しばらく無し。
//    try {
//      $fs->remove($importPath);
//    } catch (\Exception $e) {
//      $logger->error($e->getMessage());
//      // 握りつぶす
//    }

    // 実行後 行数
    $stmt = $dbMain->prepare("SELECT COUNT(*) FROM `{$stockTableName}`");
    $stmt->execute();
    $count = $stmt->fetchColumn(0);

    $info = [
      'count' => $count
    ];

    return $info;
  }


  /**
   * CSVデータ書式チェック
   * @param string $path
   * @param string $shop
   * @return bool
   */
  private function validateCsv($path, $shop)
  {
    // 一行目で判定
    $lines = [
        // AmazonMallProcess::SHOP_NAME_VOGUE => "出品者SKU\tasin\t価格\t数量\tポイント"
        //AmazonMallProcess::SHOP_NAME_VOGUE => "出品者SKU\tASIN\t価格\t数量\tポイント"
        //AmazonMallProcess::SHOP_NAME_VOGUE => "出品者SKU\tASIN\t価格\t数量\t法人価格\t数量割引のタイプ\t数量の下限1\t数量割引1\t数量の下限2\t数量割引2\t数量の下限3\t数量割引3\t数量の下限4\t数量割引4\t数量の下限5\t数量割引5\tポイント"
        AmazonMallProcess::SHOP_NAME_VOGUE => "出品者SKU\tASIN\t価格\t数量\t法人価格\t数量割引のタイプ\t数量の下限1\t数量割引1\t数量の下限2\t数量割引2\t数量の下限3\t数量割引3\t数量の下限4\t数量割引4\t数量の下限5\t数量割引5\tポイント\t累積購入割引価格タイプ\t累積購入割引下限1\t累積購入割引価格1\t累積購入割引下限2\t累積購入割引価格2\t累積購入割引下限3\t累積購入割引価格3"
      , AmazonMallProcess::SHOP_NAME_US_PLUSNAO => "sku\tasin\tprice\tquantity"
    ];

    $validLine = $lines[$shop];

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);

    if ($shop == AmazonMallProcess::SHOP_NAME_VOGUE) {
      $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-WIN');
    }

    $this->getLogger()->info('line : ' . $validLine . ' / ' . $line);

    return (trim($line) === $validLine);
  }

}
