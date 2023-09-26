<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateStockInOutCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  /** @var TbWarehouse */
  private $currentWarehouse = null;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-stock-in-out')
      ->setDescription('login to NextEngine Web site and download stock in-out data and update db.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return bool
     */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $logger->info('入出庫データ取込を開始しました。');

    // DB記録＆通知処理
    $logExecTitle = '入出庫データ取込';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    $results = [];

    try {

      // 対象倉庫 => 一旦固定。
      //    ※ ログインアカウントの選択倉庫の直接利用は、切り替え忘れの事故などがありうるので、改修時には注意。
      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $this->currentWarehouse = $repoWarehouse->find(TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

      $client = $webAccessUtil->getWebClient();

      // NEログイン
      $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

      $mainHost = null;
      $uri = $client->getRequest()->getUri();
      if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
        $mainHost = $match[1];
      } else {
        throw new RuntimeException('メイン機能URLのホスト名の取得に失敗しました。');
      }

      // 分析 ＞ 「各種情報」 画面へ遷移(kcd 取得の必要がある)
      $nextUrl = $mainHost . '/Userinspection';
      $crawler = $client->request('get', $nextUrl);
      $status = $client->getResponse()->getStatus();
      $uri = $client->getRequest()->getUri();
      if ($status !== 200 || !preg_match('!.next-engine.(?:org|com)/Userinspection!', $uri)) {
        throw new RuntimeException('move to main top page error!! [' . $status . '][' . $uri . ']');
      }
      $logger->info('各種情報画面へ遷移成功');

      // 入出庫 CSV ダウンロード
      // → CSV元データがAjaxにより画面表示されているものであるため、データソースへ直接アクセス（JSON）
      $nextUrl = $mainHost . '/Userinspection/get';
      $kcd = $crawler->filter('input[type="hidden"]#kcd')->attr('value');
      $params = [
          'id' => '75' // ID:75「分析」>「各種情報」＞ 「入出庫一覧」
        , 'kcd' => $kcd
        , 't' => substr((new \DateTime())->format('U'), 0, 9) // NextEngineの謎ロジック
      ];

      $nextUrl .= '?' . http_build_query($params);

      $client->request('get', $nextUrl);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $requestUri = $client->getRequest()->getUri();
      $contentLength = intval($response->getHeader('Content-Length'));
      
      $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
      // 過去に「商品名に不正バイナリが混じっておりJSON解析できずエラー」ということがあったので、念のため最新はファイルに残し、デバッグ可能としておく
      file_put_contents($scrapingResponseDir . '/next_engine_stock_in_out.json', $response->getContent());
      
      $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
      $jsonData = @json_decode($response->getContent(), true);
      $logger->info($logExecTitle 
          . " URI[$nextUrl]"
          . (is_array($jsonData) ? ' is_array ok, ' : ' is_array ng, ')
          . (count($jsonData) ? 'count ok ' : 'count ng '));

      if ($status !== 200
        || $isInvalidAccess
        // || $response->getHeader('Content-Type') !== 'application/json; charset=UTF-8' // Content-Typeは text/html と application/json で不安定なので、チェックには使わない
        // || !$contentLength // Content-Length は返らない。
        || !is_array($jsonData)
      ) {
        $message = $isInvalidAccess ? '不正アクセスエラー' : '';
        throw new RuntimeException("can not download verify csv error!! $message [" . $status . '][' . $requestUri . '][' . $response->getHeader('Content-Type') . '][' . $contentLength . ']');
      }
      $logger->info('入出庫データ レスポンス取得');

      if (!count($jsonData)) {
        throw new \RuntimeException('入出庫履歴データが0件です。不具合の可能性があります。');
      }

      // 取込処理
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');

      // 一時テーブル取込
      $this->importInOutCsvData($dbMain, $jsonData);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了'));

      $logger->info('入出庫データCSV 取込成功');

      $sql = <<<EOD
        INSERT INTO tb_stockreturn
        SELECT tb_stockreturn_dl.*, 0
        FROM tb_stockreturn_dl
        WHERE tb_stockreturn_dl.入出庫日 > IFNULL((SELECT MAX(tb_stockreturn.入出庫日) FROM tb_stockreturn), 0000-00-00)
EOD;
      $dbMain->query($sql);
      $logger->info('入出庫データ 取込');

      // 追加件数取得
      $newCount = $dbMain->fetchColumn("SELECT COUNT(*) FROM tb_stockreturn WHERE inventory = 0");

      $dbMain->query('DROP TABLE tb_stockreturn_dl');

      // productchoiceitems.check_why 更新（）
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        INNER JOIN tb_stockreturn s ON pci.ne_syohin_syohin_code = s.商品コード
        SET pci.check_why = CONCAT(
              s.入出庫日
            , ' '
            , s.入出1
            , s.入出2
            , '('
            , s.入出庫数
            , ')'
            , s.理由
          )
        WHERE inventory = 0
EOD;
      $dbMain->query($sql);
      $logger->info('在庫表データ 棚卸し理由更新');

      $sql = <<<EOD
        UPDATE tb_productchoiceitems
        SET check_why = CONCAT('★', check_why)
        WHERE check_why <> '\0'
        AND check_why NOT LIKE '★%';
EOD;
      $dbMain->query($sql);

      // 返送品処理・出荷確定戻しによるロケーション作成、在庫更新処理

      // 返送品一覧取得
      $stockAddSelectSql = <<<EOD
        SELECT
            io.`商品コード` AS ne_syohin_syohin_code
          , SUM(入出庫数) AS stock_add
        FROM tb_stockreturn io
        WHERE io.inventory = 0
          AND io.`入出1` = '入庫'
          AND io.`入出2` = '売上返品'
        GROUP BY io.`商品コード`
        ORDER BY io.入出庫日 DESC
            , io.商品コード ASC
EOD;
      $stmt = $dbMain->query($stockAddSelectSql);
      $returnCount = $stmt->rowCount();
      $returnStocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      $results['return'] = [
          'count' => $returnCount
        , 'data' => $returnStocks
      ];

      // 出荷確定戻し一覧取得
      $stockAddSelectSql = <<<EOD
        SELECT
            io.`商品コード` AS ne_syohin_syohin_code
          , SUM(入出庫数) AS stock_add
        FROM tb_stockreturn io
        WHERE io.inventory = 0
          AND io.`入出1` = '入庫'
          AND io.`入出2` = '出荷確定戻し'
        GROUP BY io.`商品コード`
        ORDER BY io.入出庫日 DESC
            , io.商品コード ASC
EOD;
      $stmt = $dbMain->query($stockAddSelectSql);
      $takeBackCount = $stmt->rowCount();
      $takeBackStocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      $results['take_back'] = [
          'count' => $takeBackCount
        , 'data' => $takeBackStocks
      ];

      $logger->info('入出庫データ取込 返送品件数: ' . $returnCount . ' / 出荷確定戻し件数: ' . $takeBackCount);

      if ($returnCount || $takeBackCount) {

        $dbLog = $this->getDb('log');

        // トランザクション開始
        $dbMain->beginTransaction();

        // FOR DEBUG
        // $dbMain->query("SET FOREIGN_KEY_CHECKS = 0");

        /** @var TbLocationRepository $repoLocation */
        $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

        // （履歴用）アクションキー 作成＆セット
        $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

        // ※テスト環境では存在しない商品によく当たる。そろえるのは手間なので、ここでスキップしてしまう。
        if ($input->getOption('target-env') === 'test' && file_exists('/this_is_dev_server')) {
          $sql =<<<EOD
          UPDATE
          tb_stockreturn r
          LEFT JOIN tb_productchoiceitems pci ON r.商品コード = pci.ne_syohin_syohin_code
          SET r.inventory = 1
          WHERE pci.ne_syohin_syohin_code IS NULL
EOD;
          $dbMain->exec($sql);
        }

        // 返品
        if ($returnCount) {
          $newLocation = $repoLocation->createAutoLocation('ne', 'NE_H_', $this->currentWarehouse);
          if (!$newLocation) {
            throw new \RuntimeException('新規ロケーションの作成に失敗しました。');
          }

          // 新規ロケーションへ格納
          $sql = <<<EOD
            INSERT INTO tb_product_location (
                `ne_syohin_syohin_code`
              , `location_id`
              , `stock`
              , `position`
            )
            SELECT
                IO.ne_syohin_syohin_code
              , :locationId AS location_code
              , IO.stock
              , CASE
                  WHEN COALESCE(T.max_position, -1) < 0 THEN 0
                  ELSE T.max_position + 1
                END AS position
            FROM
            (
              SELECT
                  io.`商品コード` AS ne_syohin_syohin_code
                , SUM(入出庫数) AS stock
              FROM tb_stockreturn io
              WHERE io.inventory = 0
                AND io.`入出1` = '入庫'
                AND io.`入出2` = '売上返品'
              GROUP BY io.`商品コード`
            ) IO
            LEFT JOIN (
              SELECT
                  pl.ne_syohin_syohin_code
                , MAX(pl.position) AS max_position
              FROM tb_product_location pl
              GROUP BY pl.ne_syohin_syohin_code
            ) T ON IO.ne_syohin_syohin_code = T.ne_syohin_syohin_code
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':locationId', $newLocation->getId(), \PDO::PARAM_INT);
          $stmt->execute();
        }

        // 確定戻し
        if ($takeBackCount) {
          $newLocation = $repoLocation->createAutoLocation('ne', 'NE_K_', $this->currentWarehouse);
          if (!$newLocation) {
            throw new \RuntimeException('新規ロケーションの作成に失敗しました。');
          }

          // 新規ロケーションへ格納
          $sql = <<<EOD
            INSERT INTO tb_product_location (
                `ne_syohin_syohin_code`
              , `location_id`
              , `stock`
              , `position`
            )
            SELECT
                IO.ne_syohin_syohin_code
              , :locationId AS location_code
              , IO.stock
              , CASE
                  WHEN COALESCE(T.max_position, -1) < 0 THEN 0
                  ELSE T.max_position + 1
                END AS position
            FROM
            (
              SELECT
                  io.`商品コード` AS ne_syohin_syohin_code
                , SUM(入出庫数) AS stock
              FROM tb_stockreturn io
              WHERE io.inventory = 0
                AND io.`入出1` = '入庫'
                AND io.`入出2` = '出荷確定戻し'
              GROUP BY io.`商品コード`
            ) IO
            LEFT JOIN (
              SELECT
                  pl.ne_syohin_syohin_code
                , MAX(pl.position) AS max_position
              FROM tb_product_location pl
              GROUP BY pl.ne_syohin_syohin_code
            ) T ON IO.ne_syohin_syohin_code = T.ne_syohin_syohin_code
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':locationId', $newLocation->getId(), \PDO::PARAM_INT);
          $stmt->execute();
        }

        // pci.ロケーション・過去ロケーション 更新処理
        $sql = <<<EOD
          UPDATE
          tb_productchoiceitems pci
          INNER JOIN tb_product_location pl ON pci.ne_syohin_syohin_code = pl.ne_syohin_syohin_code
                                           AND pl.position = 0
          INNER JOIN tb_location l ON pl.location_id = l.id
          SET pci.previouslocation = pci.location
            , pci.location = l.location_code
          WHERE pci.location <> l.location_code
EOD;
        $dbMain->query($sql);

        // ロケーション変更履歴 保存
        $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_IMPORT_STOCK_IN_OUT_RETURN, $this->account ? $this->account->getUsername() : 'BatchSV02', $actionKey);
        /* ------------ DEBUG LOG ------------ */  $logger->info($this->getLapTimeAndMemory('create location logs', 'main'));

        // 在庫数更新処理
        // → トリガ実装により削除

        $dbMain->commit();
      }

      // 処理済みフラグ ON
      $dbMain->query("UPDATE tb_stockreturn SET inventory = 1 WHERE inventory = 0");

      // '最終更新日時をセット
      $commonUtil = $this->getDbCommonUtil();
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_INOUT);

      $results['update'] = $newCount;

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($results));
      $logger->logTimerFlush();

      $logger->info('入出庫取込処理 終了');

      return 0;

    } catch (\Exception $e) {

      $logger->error($logExecTitle . ':' . $e->getMessage() . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "入出庫取込処理でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }


  /**
   * 受注データ取込：照合データ取込処理 実装
   * @param \Doctrine\DBAL\Connection $dbMain
   * @param array $data
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importInOutCsvData($dbMain, $data)
  {
    $dbMain->query('DROP TABLE IF EXISTS tb_stockreturn_dl');

    $sql = <<<EOD
      CREATE TABLE tb_stockreturn_dl (
        入出庫日 DATETIME NOT NULL,
        入出1 VARCHAR(255),
        入出2 VARCHAR(255),
        商品コード VARCHAR(255),
        商品名 VARCHAR(255),
        原価 VARCHAR(255),
        入出庫数 INT(10) UNSIGNED,
        担当者名 VARCHAR(255),
        理由 VARCHAR(255)
      );
EOD;
    $dbMain->query($sql);

    // 件数がそれなりなので、CSVを作成して LOAD DATA で挿入
    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $fs = new FileSystem();

    $rootDir = $this->getContainer()->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data/stocks';
    if (!$fs->exists($dataDir)) {
      $fs->mkdir($dataDir, 0755);
    }

    $fileName = sprintf('stock-in-out-%s.csv', (new \DateTime())->format('YmdHis'));
    $filePath = $dataDir . '/' . $fileName;
    $fp = fopen($filePath, 'wb');

    // CSVの順番確定用
    $fieldsList = [
        '入出庫日'
      , ''
      , '入出'
      , '商品コード'
      , '商品名'
      , '原価'
      , '入出庫数'
      , '担当者名'
      , '理由'
    ];

    $isValid = null; // 項目チェック
    foreach($data as $row) {
      // JSONのキーに書式が付いているひどい形。補正する。
      $tmp = [];
      foreach($row as $k => $v) {
        $k = preg_replace('/__[^_]*__/', '', $k);
        $tmp[$k] = $v;
      }
      $row = $tmp;

      if (is_null($isValid)) {
        $fields = array_keys($row);
        $validFields = $fieldsList;
        sort($fields);
        sort($validFields);
        if ($fields !== $validFields) {
          throw new RuntimeException('入出庫データの項目が一致しません。 [' . implode(',', $fields), '][' . implode(',', $validFields) . ']');
        }
      }

      fputs($fp, $stringUtil->convertArrayToCsvLine($row, $fieldsList) . "\n");
    }

    fclose($fp);

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE '{$filePath}'
      INTO TABLE tb_stockreturn_dl
      FIELDS ENCLOSED BY '"'
             ESCAPED BY '\\\\' TERMINATED BY ','
      LINES TERMINATED BY '\n'
EOD;
    $dbMain->query($sql);

    // 件数確認
    $count = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_stockreturn_dl');
    if ($count != 3000) {
      throw new RuntimeException('入出庫CSVの件数が3,000件ではありません。[' . $count . ']');
    }

    return;
  }

}
