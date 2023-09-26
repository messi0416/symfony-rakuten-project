<?php
/**
 * 藪吉倉庫 在庫テーブル更新処理
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

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateYabuyoshiProductStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-yabuyoshi-product-stock')
      ->setDescription('藪吉倉庫から在庫一覧をダウンロードし、藪吉在庫比較テーブルを更新する')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイルパス指定（ダウンロードをskip）')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('藪吉在庫比較テーブルの更新処理を開始しました。');

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
    $logExecTitle = '藪吉在庫比較テーブル更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

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

        $saveDir = $this->getFileUtil()->getWebCsvDir() . '/Yabuyoshi/Import';

        $client = $webAccessUtil->getWebClient();
        $webAccessUtil->yabuyoshiLogin($client);

        // CSVダウンロード画面
        $logger->info('藪吉 CSVダウンロード画面へ遷移');

        $url = 'https://webat101.lisa-c.jp/yabuyoshi/Wst051.html';
        $crawler = $client->request('get', $url);
        $status = $client->getResponse()->getStatus();
        $uri = $client->getRequest()->getUri();
        if ($status !== 200) {
          throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
        }

        // ダウンロード
        $logger->info('藪吉 CSVダウンロード画面 フォーム取得');
        $form = $crawler->filter('#Wst051');
        if (!$form || $form->count() === 0) {
          throw new \RuntimeException('no csv download form');
        }

        $form = $form->form();

        // キー類 捜索＆追加
        $oFwSessKey = $crawler->filter('[name=fwSessKey]');
        $oFwActId = $crawler->filter('[name=fwReqId]');
        $oFwCldId = $crawler->filter('[name=fwCldId]');

        $url = '/yabuyoshi/Wst051!download.html';
        if ($oFwSessKey->count()) {
          $url .= ';jsessionid=' . $oFwSessKey->attr('value');
        }

        // action 差し替え
        $form->getFormNode()->setAttribute('action', $url);

        $logger->info(sprintf('sess: %d', $oFwSessKey->count()));
        $logger->info(sprintf('req: %d', $oFwActId->count()));
        $logger->info(sprintf('cld: %d', $oFwCldId->count()));

        if ($oFwActId->count()) {
          $domDocument = new \DOMDocument;
          $input = $domDocument->createElement('input');
          $input->setAttribute('name', 'fwReqId');
          $input->setAttribute('value', $oFwActId->attr('value'));
          $input = new InputFormField($input);
          $form->set($input);
        }

        if ($oFwCldId->count()) {
          $domDocument = new \DOMDocument;
          $input = $domDocument->createElement('input');
          $input->setAttribute('name', 'fwCldId');
          $input->setAttribute('value', $oFwCldId->attr('value'));
          $input = new InputFormField($input);
          $form->set($input);
        }

        $form['outputType'] = '2'; // 「在庫リスト（商品順）」
        // $form['downloadFlg'] = 'true';

        foreach($form->all() as $field) {
          $logger->info(sprintf('%s : %s', $field->getName(), $field->getValue()));
        }

        $crawler = $client->submit($form); // ダウンロード実行

        /** @var Response $response */
        $response = $client->getResponse();
        $request = $client->getRequest();

        $headers = $response->getHeaders();
        $status = $response->getStatus();
        $uri = $request->getUri();

        $logger->info(print_r($headers, true));
        $logger->info(print_r($status, true));
        $logger->info(print_r($uri, true));
        $logger->info(print_r($request->getCookies(), true));

        if ($status !== 200 || $uri !== 'https://webat101.lisa-c.jp/yabuyoshi/Wst051!download.html') {
          throw new \RuntimeException('ダウンロードの実行に失敗しました。');
        }

        if (!isset($headers['Content-Disposition'])) {
          throw new \RuntimeException('ダウンロード時のレスポンスではありませんでした。(Content-Disposition)', print_r($headers, true));
        }

        $fileName = null;
        foreach($headers['Content-Disposition'] as $header) {
          if (preg_match('/^attachment;filename="([^"]+)"/', $header, $match)) {
            $fileName = $match[1];
          }
        }
        if (!$fileName) {
          throw new \RuntimeException('ダウンロードのレスポンスではありませんでした。', print_r($headers, true));
        }

        $fs = new FileSystem();
        /** @var FileUtil $fileUtil */
        $fileUtil = $this->getContainer()->get('misc.util.file');
        $exportDir = sprintf('%s/Yabuyoshi/Import', $fileUtil->getWebCsvDir());
        if (!$fs->exists($exportDir)) {
          $fs->mkdir($exportDir, 0755);
        }

        $exportPath = sprintf('%s/stock_%s.csv', $exportDir, (new \DateTime())->format('YmdHis'));
        $file = new \SplFileObject($exportPath,'wb');
        $bytes = $file->fwrite(mb_convert_encoding($response->getContent(), 'UTF-8', 'SJIS-WIN'));
        if (!$fs->exists($exportPath) || ! $bytes) {
          @$fs->remove($exportPath);
          throw new RuntimeException('can not save csv file. [ ' . $exportPath . ' ][' . $bytes . ']');
        }
        $logger->info('藪吉倉庫 CSVダウンロード画面 CSVダウンロード成功 [' . $exportPath . '][' . $bytes . ']');
        unset($file);

        // DB記録＆通知処理
        $fileInfo = $this->getFileUtil()->getTextFileInfo($exportPath);
        $info = [
            'size' => $fileInfo['size']
          , 'lineCount' => $fileInfo['lineCount']
        ];

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了')->setInformation($info));

        $outputPath = $exportPath;
      }

      // ====================================================
      // 取込処理を実行
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));




      $fs = new FileSystem();
      if (!$outputPath || !$fs->exists($outputPath)) {
        throw new \RuntimeException('藪吉倉庫 在庫一覧ファイルが見つかりません。');
      }

      $info = [
          'count' => 0
      ];

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $dbMain->query("TRUNCATE tb_yabuyoshi_product_stock");

      $info['count'] = $this->importCsvData($outputPath);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));

      // ====================================================
      // FBA仮想倉庫 在庫ロケーション更新
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '藪吉倉庫ロケーション更新処理', '開始'));
      $this->updateYabuyoshiProductLocation($this->account);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '藪吉倉庫ロケーション更新処理', '終了'));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));

      $logger->logTimerFlush();

      $logger->info('藪吉倉庫 在庫比較テーブルの更新処理を終了しました。');

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
      IGNORE INTO TABLE tb_yabuyoshi_product_stock
      FIELDS TERMINATED BY ',' ENCLOSED BY '' ESCAPED BY ''
      LINES TERMINATED BY '\\r\\n'
      IGNORE 1 LINES
      (
          `荷主コード`
        , `荷主名`
        , `センターコード`
        , `センター名`
        , `ベンダーコード`
        , `ベンダー名`
        , `倉庫`
        , `商品コード`
        , `商品名`
        , `規格`
        , `商品名２`
        , `商品名３`
        , `UPCコード`
        , `入数（パレット）`
        , `入数（ケース）`
        , `入数（ボール）`
        , `ロケーション`
        , `在庫状態`
        , `鮮度日付区分`
        , `鮮度日付`
        , `入庫日`
        , `ロット番号`
        , `在庫数（パレット）`
        , `単位名（パレット）`
        , `在庫数（ケース）`
        , `単位名（ケース）`
        , `在庫数（ボール）`
        , `単位名（ボール）`
        , `在庫数（ピース）`
        , `単位名（ピース）`
        , `在庫数（総ピース）`
        , `引当可能数（パレット）`
        , `引当可能数（ケース）`
        , `引当可能数（ボール）`
        , `引当可能数（ピース）`
        , `引当可能数（総ピース）`
        , `補充引当数（パレット）`
        , `補充引当数（ケース）`
        , `補充引当数（ボール）`
        , `補充引当数（ピース）`
        , `補充引当数（総ピース）`
        , `引当数（パレット）`
        , `引当数（ケース）`
        , `引当数（ボール）`
        , `引当数（ピース）`
        , `引当数（総ピース）`
        , `登録日時`
        , `登録ユーザー`
        , `更新日時`
        , `更新ユーザー`
      )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importPath', $importPath);
    $stmt->execute();

    // 商品コード補完、自社に存在しない商品コード => is_valid_stock = 0
    $sql = <<<EOD
      UPDATE tb_yabuyoshi_product_stock s 
      LEFT JOIN tb_productchoiceitems pci ON s.商品コード = pci.ne_syohin_syohin_code
      SET s.ne_syohin_syohin_code = COALESCE(pci.ne_syohin_syohin_code, '')
        , s.is_valid_stock = CASE WHEN pci.ne_syohin_syohin_code IS NULL THEN 0 ELSE -1 END
        , s.stock = CASE WHEN pci.ne_syohin_syohin_code IS NULL THEN 0 ELSE s.`在庫数（ピース）` END
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // セット商品補正
    // 現在の使用： xxxx-1/2, xxxx-2/2 など、最後が分数表記で終わる商品
    // また、なぜか xxxx の部分は代表商品コードまで。
    // このため、SKUへの変換は、その代表商品の先頭SKUとして反映する。
    $sql = <<<EOD
      SELECT 
          s.id
        , s.`商品コード`
        , SUM(s.`在庫数（ピース）`) AS 在庫数
        , s.is_valid_stock
        , s.ne_syohin_syohin_code
        , s.stock
      FROM tb_yabuyoshi_product_stock s
      WHERE s.is_valid_stock = 0
        AND s.`商品コード` REGEXP '-[0-9]+/[0-9]+$'
      GROUP BY s.`商品コード`
      ORDER BY s.`商品コード`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $sets = [];
    foreach($stmt as $row) {
      if (!preg_match('|^(.*)-([0-9]+)/([0-9]+)$|', $row['商品コード'], $m)) {
        continue;
      }
      $daihyoSyohinCode = $m[1];
      $index = $m[2];
      $num = $m[3];
      $row['index'] = $index;
      if (!isset($sets[$daihyoSyohinCode])) {
        $sets[$daihyoSyohinCode] = [
            'setNum' => $num
          , 'updateId' => null
          , 'stock' => null
          , 'parts' => []
          , 'choice' => null
        ];
      }
      $sets[$daihyoSyohinCode]['parts'][] = $row;
      // index: 1 のものに在庫を持たせ、他は無効レコードのままとする。
      if ($row['index'] == 1) {
        $sets[$daihyoSyohinCode]['updateId'] = $row['id'];
      }
      // セットの在庫数は最も少ない在庫数に合わせる
      if (!isset($sets[$daihyoSyohinCode]['stock']) || $sets[$daihyoSyohinCode]['stock'] > $row['在庫数']) {
        $sets[$daihyoSyohinCode]['stock'] = $row['在庫数'];
      }

      /** @var TbMainproducts $product */
      $product = $repoProduct->find($daihyoSyohinCode);
      if ($product) {
        $choices = $product->getChoiceItems();
        if (count($choices) > 0) {
          $sets[$daihyoSyohinCode]['choice'] = $choices[0];
        }
      }
    }

    foreach($sets as $daihyoSyohinCode => $set) {
      // 分割口数が合っていなければエラー
      $expectedIndexesStr = implode(',', range(1, $set['setNum'], 1));
      $indexes = array_map(function($ele) { return intval($ele['index']); }, $set['parts']);
      sort($indexes);
      $indexesStr = implode(',', $indexes);

      if (
           !isset($set['updateId']) // index:1 が無かった
        || $expectedIndexesStr !== $indexesStr // 1...setNum まで揃っていない
        || !isset($set['choice']) // SKUが取得できなかった
      ) {
        unset($sets[$daihyoSyohinCode]);
      }
    }

    // 更新処理
    foreach($sets as $set) {
      $sql = <<<EOD
        UPDATE tb_yabuyoshi_product_stock s
        SET s.is_valid_stock = -1
          , s.ne_syohin_syohin_code = :neSyohinSyohinCode 
          , s.stock = :stock
        WHERE id = :id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':neSyohinSyohinCode', $set['choice']->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
      $stmt->bindValue(':stock', $set['stock'], \PDO::PARAM_INT);
      $stmt->bindValue(':id', $set['updateId'], \PDO::PARAM_INT);
      $stmt->execute();
    }

    // 実行後 行数
    $stmt = $dbMain->prepare('SELECT COUNT(*) FROM tb_yabuyoshi_product_stock');
    $stmt->execute();
    $count = $stmt->fetchColumn(0);

    return $count;
  }

  /**
   * 商品在庫情報 藪吉倉庫 在庫更新処理
   * @param string $shop
   * @param SymfonyUsers|null $account
   * @throws \Doctrine\DBAL\DBALException
   * @throws \Exception
   */
  public function updateYabuyoshiProductLocation($account = null)
  {
    $locationCode = 'YABU-AUTO';
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

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
    $warehouse = $repoWarehouse->find(TbWarehouseRepository::YABUYOSHI_WAREHOUSE_ID); // 藪吉倉庫
    if (!$warehouse) {
      throw new \RuntimeException('no FBA warehosue.');
    }

    $location = $repoLocation->getByLocationCode($warehouse->getId(), $locationCode);
    
    if(!empty($location)){
         // 値のリセット（藪良倉庫 在庫全削除）
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
    $location = $repoLocation->createNewLocation($warehouse->getId(), $locationCode, 'YABU');

    // productchoiceitems に存在するデータのみをINSERT する。
    // このとき、相互トリガによりエラーとなるため、一時テーブルを経由する。
    // 一時テーブルへインポート
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_yabuyoshi_location_products");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_work_yabuyoshi_location_products (
          ne_syohin_syohin_code VARCHAR(50) NOT NULL DEFAULT 0 PRIMARY KEY
        , stock INTEGER NOT NULL DEFAULT 0
        , position INTEGER NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET utf8
      SELECT
          s.ne_syohin_syohin_code AS ne_syohin_syohin_code
        , SUM(s.stock) AS stock
        , 999999 AS position
      FROM tb_yabuyoshi_product_stock s
      INNER JOIN tb_productchoiceitems pci ON s.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      GROUP BY s.ne_syohin_syohin_code
      ORDER BY s.ne_syohin_syohin_code
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
      FROM tmp_work_yabuyoshi_location_products
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':locationId', $location->getId(), \PDO::PARAM_INT);
    $stmt->execute();

    // ロケーション変更履歴 保存
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_YABUYOSHI_UPDATE_LOCATION, ($account ? $account->getUsername(): 'BatchSV02'), $actionKey);

    $dbMain->commit();
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '荷主コード,荷主名,センターコード,センター名,ベンダーコード,ベンダー名,倉庫,商品コード,商品名,規格,商品名２,商品名３,UPCコード,入数（パレット）,入数（ケース）,入数（ボール）,ロケーション,在庫状態,鮮度日付区分,鮮度日付,入庫日,ロット番号,在庫数（パレット）,単位名（パレット）,在庫数（ケース）,単位名（ケース）,在庫数（ボール）,単位名（ボール）,在庫数（ピース）,単位名（ピース）,在庫数（総ピース）,引当可能数（パレット）,引当可能数（ケース）,引当可能数（ボール）,引当可能数（ピース）,引当可能数（総ピース）,補充引当数（パレット）,補充引当数（ケース）,補充引当数（ボール）,補充引当数（ピース）,補充引当数（総ピース）,引当数（パレット）,引当数（ケース）,引当数（ボール）,引当数（ピース）,引当数（総ピース）,登録日時,登録ユーザー,更新日時,更新ユーザー';

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);

    return (trim($line) === $validLine);
  }

}
