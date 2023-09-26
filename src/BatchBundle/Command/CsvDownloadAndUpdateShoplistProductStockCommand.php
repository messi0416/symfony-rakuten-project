<?php
/**
 * SHOPLIST 在庫比較テーブル更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateShoplistProductStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-shoplist-product-stock')
      ->setDescription('SHOPLIST から在庫一覧をダウンロードし、SHOPLIST在庫比較テーブルを更新する')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイルパス指定（ダウンロードをskip）')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('SHOPLIST在庫比較テーブルの更新処理を開始しました。');

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
    $logExecTitle = 'SHOPLIST在庫比較テーブル更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      $saveDir = null;

      // ディレクトリ指定があればダウンロードはスキップ
      $outputPathOn = $input->getOption('data-path');
      if (!$outputPathOn) {

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

        $saveDir = $this->getFileUtil()->getWebCsvDir() . '/Shoplist/Import';

        $client = $webAccessUtil->getWebClient();
        $webAccessUtil->shoplistLogin($client);

        // CSVダウンロード画面
        $logger->info('SHOPLIST CSVダウンロード画面へ遷移');
        $crawler = $client->request('GET', '/shopadmin/csv/ProductCsvDlTop/');
        $status = $client->getResponse()->getStatus();
        $uri = $client->getRequest()->getUri();
        if ($status !== 200) {
          throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
        }

        // ダウンロード
        $logger->info('SHOPLIST CSVダウンロード画面 フォーム取得');
        $form = $crawler->selectButton('ダウンロード')->form();

        // 販売中 在庫一覧ダウンロード
        $form['csvType'] = '6';
        $form['shipping_flg'] = '0'; // 「販売中」

        $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロード実行 表示設定ON');
        $client->submit($form);

        /** @var Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();
        if ($status !== 200) {
          throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
        }

        $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロードレスポンス取得');

        $contentType = $response->getHeader('Content-Type');
        if ($status !== 200 || strpos($contentType, 'application/vnd.ms-excel') === false) {
          throw new RuntimeException('shoplist csv download error!! [' . $status . '][' . $uri . '][' . $contentType . ']');
        }

        // ファイル保存
        $fileName = sprintf('stock_on_%s.csv', date('YmdHis'));
        $path = $saveDir . '/' . $fileName;

        $fs = new FileSystem();
        $file = new \SplFileObject($path, 'w'); // 上書き
        $bytes = $file->fwrite(mb_convert_encoding($response->getContent(), 'UTF-8', 'SJIS-WIN'));

        if (!$fs->exists($path) || ! $bytes) {
          @$fs->remove($path);
          throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . ']');
        }
        $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロード成功 [' . $path . '][' . $bytes . ']');

        $outputPathOn = $path;

        // 倉庫に入れる 在庫一覧ダウンロード
        $form['csvType'] = '6';
        $form['shipping_flg'] = '1'; // 「倉庫に入れる」

        $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロード実行 表示設定OFF');
        $client->submit($form);

        /** @var Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();
        if ($status !== 200) {
          throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
        }

        $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロードレスポンス取得');

        $contentType = $response->getHeader('Content-Type');
        if ($status !== 200 || strpos($contentType, 'application/vnd.ms-excel') === false) {
          throw new RuntimeException('shoplist csv download error!! [' . $status . '][' . $uri . '][' . $contentType . ']');
        }

        // ファイル保存
        $fileName = sprintf('stock_off_%s.csv', date('YmdHis'));
        $path = $saveDir . '/' . $fileName;

        $fs = new FileSystem();
        $file = new \SplFileObject($path, 'w'); // 上書き
        $bytes = $file->fwrite(mb_convert_encoding($response->getContent(), 'UTF-8', 'SJIS-WIN'));

        if (!$fs->exists($path) || ! $bytes) {
          @$fs->remove($path);
          throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . ']');
        }
        $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロード成功 [' . $path . '][' . $bytes . ']');

        // DB記録＆通知処理
        $fileInfo = $this->getFileUtil()->getTextFileInfo($path);
        $info = [
            'size' => $fileInfo['size']
          , 'lineCount' => $fileInfo['lineCount']
        ];

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了')->setInformation($info));

        $outputPathOff = $path;
      }

      // ====================================================
      // 取込処理を実行
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));

      $fs = new FileSystem();
      if (!$outputPathOn || !$fs->exists($outputPathOn)) {
        throw new \RuntimeException('SHOPLIST 在庫一覧ファイルが見つかりません。');
      }

      $info = [
          'count_on' => 0
        , 'count_off' => 0
      ];

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $dbMain->query("TRUNCATE tb_shoplist_product_stock");

      $info['count_on'] = $this->importCsvData($outputPathOn, 'on');
      if (isset($outputPathOff)) {
        $info['count_off'] = $this->importCsvData($outputPathOff, 'off');
      }

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('SHOPLIST在庫比較テーブルの更新処理を終了しました。');

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
      IGNORE INTO TABLE tb_shoplist_product_stock
      FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
      LINES TERMINATED BY '\\n'
      IGNORE 1 LINES
      (
           `項目選択肢用コントロールカラム`
         , `商品管理番号（商品URL）`
         , `選択肢タイプ`
         , `Select/Checkbox用項目名`
         , `Select/Checkbox用選択肢`
         , `項目選択肢別在庫用横軸選択肢`
         , `項目選択肢別在庫用横軸選択肢子番号`
         , `項目選択肢別在庫用縦軸選択肢`
         , `項目選択肢別在庫用縦軸選択肢子番号`
         , `項目選択肢別在庫用取り寄せ可能表示`
         , `項目選択肢別在庫用在庫数`
         , `在庫戻しフラグ`
         , `在庫切れ時の注文受付`
         , `在庫あり時納期管理番号`
         , `在庫切れ時納期管理番号`
         , `予約販売`
         , `予約商品入荷時期`
         , `JANコード`
      )
      SET `hidden` = :hidden
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $importPath);
    $stmt->bindValue(':hidden', ($display == 'on' ? 0 : 1), \PDO::PARAM_INT); // 0:販売中 1:倉庫（非表示）
    $stmt->execute();

    // 横軸・縦軸選択肢が無いものを一括削除
    // ※当初の登録時エラーか？ 20,000件ほど選択肢が無いレコードが作成されるので削除
    $sql = <<<EOD
      DELETE FROM tb_shoplist_product_stock
      WHERE 項目選択肢別在庫用横軸選択肢 = ''
         OR 項目選択肢別在庫用縦軸選択肢 = ''
EOD;
    $dbMain->query($sql);

//    // ファイル削除
//    try {
//      $fs->remove($importPath);
//    } catch (\Exception $e) {
//      $logger->error($e->getMessage());
//      // 握りつぶす
//    }

    // 実行後 行数
    $stmt = $dbMain->prepare('SELECT COUNT(*) FROM tb_shoplist_product_stock');
    $stmt->execute();
    $count = $stmt->fetchColumn(0);

    return $count;
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '"項目選択肢用コントロールカラム","商品管理番号（商品URL）","選択肢タイプ","Select/Checkbox用項目名","Select/Checkbox用選択肢","項目選択肢別在庫用横軸選択肢","項目選択肢別在庫用横軸選択肢子番号","項目選択肢別在庫用縦軸選択肢","項目選択肢別在庫用縦軸選択肢子番号","項目選択肢別在庫用取り寄せ可能表示","項目選択肢別在庫用在庫数","在庫戻しフラグ","在庫切れ時の注文受付","在庫あり時納期管理番号","在庫切れ時納期管理番号","タグID","画像URL","予約販売","予約商品入荷時期","JANコード","CROOZ用カラー","CROOZ用サイズ"';
    // $validLine = '"項目選択肢用コントロールカラム","商品管理番号（商品URL）","選択肢タイプ","項目選択肢項目名","項目選択肢","項目選択肢別在庫用横軸選択肢","項目選択肢別在庫用横軸選択肢子番号","項目選択肢別在庫用縦軸選択肢","項目選択肢別在庫用縦軸選択肢子番号","項目選択肢別在庫用取り寄せ可能表示","項目選択肢別在庫用在庫数","在庫戻しフラグ","在庫切れ時の注文受付","在庫あり時納期管理番号","在庫切れ時納期管理番号","タグID","画像URL","予約販売","予約商品入荷時期","JANコード","CROOZ用カラー","CROOZ用サイズ"';

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);

    return (trim($line) === $validLine);
  }

}
