<?php
/**
 * SHOPLIST 販売実績テーブル更新処理
 *
 * 取得URL https://service.shop-list.com/shopadmin/summary/DailySalesProductDetail/
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateShoplistSalesCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  private $doNotify = true;


  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-shoplist-sales')
      ->setDescription('SHOPLIST から日次販売データをダウンロードし、SHOPLIST販売実績テーブルを更新する')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('data-dir', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイルのあるディレクトリのパスを指定（ダウンロードをskip） 配下に対象日に応じた「sales_Ymd.csv」ファイルがあること')
      ->addOption('from-date', null, InputOption::VALUE_OPTIONAL, '集計対象日（開始） YYYY-mm-dd ※指定がなければ前日か登録済み日時の翌日の過去の方')
      ->addOption('to-date', null, InputOption::VALUE_OPTIONAL, '集計対象日（終了） YYYY-mm-dd ※指定がなければ前日')
      ->addOption('no-notify', null, InputOption::VALUE_OPTIONAL, 'デスクトップ通知をしない。', '0')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('SHOPLIST販売実績テーブルの更新処理を開始しました。');

    $this->validate($input);

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // 通知有無
    $this->doNotify = ! ((bool)$input->getOption('no-notify'));

    // DB記録＆通知処理
    $logExecTitle = 'SHOPLIST販売実績テーブル更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'), $this->doNotify);

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      // from-date
      $fromDateStr = $input->getOption('from-date');
      // to-date
      $toDateStr = $input->getOption('to-date');

      $commonUtil = $this->getDbCommonUtil();

      $fromDate =  '';
      if ($fromDateStr) {
        $fromDate = new \DateTimeImmutable($fromDateStr);
      } else {
        // 指定が無ければ、実行日の前日か、登録済み日時の翌日の過去の方
        $fromDate = (new \DateTimeImmutable())->modify('-1 day');
        $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(
          DbCommonUtil::UPDATE_RECORD_NUMBER_SHOPLIST_SALES
        );
        if ($lastUpdated) {
          $lastUpdateNextDay = $lastUpdated->modify('+1 day');
          if ($lastUpdateNextDay < $fromDate) {
            $fromDate = $lastUpdateNextDay;
          }
        }
      }
      $fromDate->setTime(0, 0, 0);
      $fromDateStr = $fromDate->format('Y-m-d');
      $toDate = $toDateStr
        ? new \DateTimeImmutable($toDateStr)
        : (new \DateTimeImmutable())->modify('-1 day'); // 指定が無ければ、実行日の前日
      $toDate->setTime(23, 59, 59);
      $toDateStr = $toDate->format('Y-m-d');

      $outputPathList = [];
      // ディレクトリ指定があればダウンロードはスキップ
      $outputDir = $input->getOption('data-dir');

      if ($outputDir) {
        $targetDate = $fromDate;
        while ($targetDate <= $toDate) {
          $fileName = 'sales_' . $targetDate->format('Ymd') . '.csv';
          $filePath = $outputDir . '/' . $fileName;
          if (! file_exists($filePath)) {
            throw new \RuntimeException('指定ディレクトリに、対象日に該当するファイルがありません[' . $filePath . ']');
          }
          $outputPathList[$targetDate->format('Y-m-d')] = $filePath;
          $targetDate = $targetDate->modify('+1 day');
        }
      } else {
        $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始', $fromDateStr, $toDateStr),
          $this->doNotify
        );

        $client = $webAccessUtil->getWebClient();
        $webAccessUtil->shoplistLogin($client);

        // データは、指定期間1日ずつ取得する（纏めると、各日ではなくその期間の合計値が返るため）
        $targetDate = $fromDate;
        $totalInfo = [];
        while ($targetDate <= $toDate) {
          $info = $this->csvDownload($client, $targetDate);
          $targetDateStr = $targetDate->format('Y-m-d');
          $outputPathList[$targetDateStr] = $info['path'];
          $totalInfo[$targetDateStr] = [$info['size'], $info['lineCount']];
          $targetDate = $targetDate->modify('+1 day');
        }

        $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了')->setInformation($totalInfo),
          $this->doNotify
        );
      }

      // ====================================================
      // 取込処理を実行
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'), $this->doNotify);

      $info = ['count' => 0];

      $result = $this->importCsvData($outputPathList);
      $info['count'] = $result['count'];

      $commonUtil->updateUpdateRecordTable(
        DbCommonUtil::UPDATE_RECORD_NUMBER_SHOPLIST_SALES,
        $result['lastUpdated']
      );

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info), $this->doNotify);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'), $this->doNotify);
      $logger->logTimerFlush();

      $logger->info('SHOPLIST販売実績テーブルの更新処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true // notify
        , $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * CSVダウンロード処理
   * @param WebClient $client
   * @param \DateTimeImmutable|\DateTime $targetDate
   * @return array 保存パス、ファイルサイズ、行数の情報を配列で返す
   */
  private function csvDownload($client, $targetDate)
  {
    $logger = $this->getLogger();

    // CSVダウンロード画面
    $logger->info('SHOPLIST CSVダウンロード画面へ遷移');
    $crawler = $client->request('GET', '/shopadmin/summary/DailySalesProductDetail/');
    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200) {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }

    // ダウンロード
    $logger->info('SHOPLIST CSVダウンロード画面 フォーム取得 : ' . $uri);
    $form = $crawler->filter('form')->form();

    $targetDateStr = $targetDate->format('Y-m-d');
    $form['startDate'] = $targetDateStr;
    $form['endDate'] = $targetDateStr;
    $form['brandIds'] = [ '129', '649' ]; // ブランド plusnao, plusnaokids
    $form['group_type'] = 'sku'; // SKU単位

    $form['csv_flg'] = '1';
    $form['condition'] = 'search';

    $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロード実行');
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
    $fileName = sprintf('sales_%s.csv', $targetDate->format('Ymd'));

    $fs = new Filesystem();
    $saveDir = sprintf(
      '%s/Shoplist/Import/%s',
      $this->getFileUtil()->getWebCsvDir(),
      (new \DateTime())->format('YmdHis')
    );
    if (!$fs->exists($saveDir)) {
      $fs->mkdir($saveDir, 0777);
    }
    $path = $saveDir . '/' . $fileName;

    $file = new \SplFileObject($path, 'w'); // 上書き
    $bytes = $file->fwrite(mb_convert_encoding(str_replace("\r\n", "\n", $response->getContent()), 'UTF-8', 'SJIS-WIN'));

    if (!$fs->exists($path) || ! $bytes) {
      @$fs->remove($path);
      throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . ']');
    }
    $logger->info('SHOPLIST CSVダウンロード画面 CSVダウンロード成功 [' . $path . '][' . $bytes . ']');

    // DB記録＆通知処理
    $fileInfo = $this->getFileUtil()->getTextFileInfo($path);
    $info = [
        'path' => $path
      , 'size' => $fileInfo['size']
      , 'lineCount' => $fileInfo['lineCount']
    ];

    $logger->info($targetDateStr . '分CSVダウンロード完了');

    return $info;
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param array $importPathList
   * @return array 実行件数、登録済み日時
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importCsvData($importPathList)
  {
    $logger = $this->logger;

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log'); // DB名取得のためにだけ利用
    $logDbName = $dbLog->getDatabase();

    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_shoplist_daily_sales_dl");

    // 取込一時テーブル
    $sql = <<<EOD
    CREATE TEMPORARY TABLE tmp_work_shoplist_daily_sales_dl (
        `対象日` DATETIME NOT NULL
      , `順位` INTEGER NOT NULL DEFAULT 0
      , `商品番号` VARCHAR(50) NOT NULL DEFAULT ''
      , `JANコード` VARCHAR(100) NOT NULL DEFAULT ''
      , `商品名` VARCHAR(255) NOT NULL DEFAULT ''
      , `販売個数(合計)` INTEGER NOT NULL DEFAULT 0
      , `販売個数(通常販売)` INTEGER NOT NULL DEFAULT 0
      , `販売個数(スピード便)` INTEGER NOT NULL DEFAULT 0
      , `受注高` INTEGER NOT NULL DEFAULT 0
      , `比率` DECIMAL(10, 2) NOT NULL DEFAULT 0.00
      , `カラー` VARCHAR(100) NOT NULL DEFAULT ''
      , `サイズ` VARCHAR(100) NOT NULL DEFAULT ''
      , `在庫数` INTEGER NOT NULL DEFAULT 0
      , `販売開始日` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
      , PRIMARY KEY (`対象日`, `商品番号`)
    ) Engine=InnoDB DEFAULT CHARSET utf8mb4
    ;
EOD;
    $dbMain->query($sql);

    $fs = new FileSystem();

    // 現在の登録済み日時
    $commonUtil = $this->getDbCommonUtil();
    $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(
      DbCommonUtil::UPDATE_RECORD_NUMBER_SHOPLIST_SALES
    );

    // 1ファイルずつtmp_work_shoplist_daily_sales_dlに追加。
    foreach ($importPathList as $targetDateStr => $importPath) {

      if (!$fs->exists($importPath)) {
        throw new RuntimeException('no data file!! [' . $importPath . ']');
      }

      // 書式チェック
      if (!$this->validateCsv($importPath)) {
        throw new \RuntimeException('ダウンロードされたファイルの書式が違います。処理を終了しました。');
      }

      $targetDate = (new \DateTimeImmutable($targetDateStr))->setTime(23, 59, 59);
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importPath
        IGNORE INTO TABLE tmp_work_shoplist_daily_sales_dl
        FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
        LINES TERMINATED BY '\\n'
        IGNORE 1 LINES
        (
            `順位`
          , @dummy
          , `商品番号`  -- "卸品番"を「商品番号」カラムに
          , `JANコード`
          , `商品名`
          , `販売個数(合計)`
          , `販売個数(通常販売)`
          , `販売個数(スピード便)`
          , @dummy
          , `受注高`  -- "受注高(税抜)"を「受注高」カラムに
          , `比率`
          , `カラー`
          , `サイズ`
          , `在庫数`  -- "在庫数(合計)"を「在庫数」カラムに
          , @dummy
          , @dummy
          , @dummy
          , `販売開始日`
        )
        SET `対象日` = :targetDate
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importPath', $importPath);
      $stmt->bindValue(':targetDate', $targetDateStr);
      $stmt->execute();
      if (! $lastUpdated || $lastUpdated < $targetDate) {
        $lastUpdated = $targetDate;
      }

      // ファイル削除
      try {
        $fs->remove($importPath);
      } catch (\Exception $e) {
        $logger->error($e->getMessage());
        // 握りつぶす
      }
    }

    $shoppingMallShoplist = $this->getDbCommonUtil()->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);
    // 販売実績テーブルへの流し込み
    $sql = <<<EOD
      REPLACE INTO tb_shoplist_daily_sales (
          `order_date`
        , `ne_syohin_syohin_code`
        , `daihyo_syohin_code`
        , `jan_code`
        , `syohin_title`
        , `num_total`
        , `num_normal`
        , `num_speed_bin`
        , `sales_amount`
        , `rate`
        , `color`
        , `size`
        , `stock`
        , `sales_start_date`
        , `cost_tanka`
        , `system_usage_cost_ratio`
      )
      SELECT
          dl.`対象日` AS `order_date`
        , dl.`商品番号` AS `ne_syohin_syohin_code`
        , COALESCE(pci.daihyo_syohin_code, '') AS `daihyo_syohin_code`
        , dl.`JANコード` AS `jan_code`
        , dl.`商品名` AS `syohin_title`
        , dl.`販売個数(合計)` AS `num_total`
        , dl.`販売個数(通常販売)` AS `num_normal`
        , dl.`販売個数(スピード便)` AS `num_speed_bin`
        , dl.`受注高` AS `sales_amount`
        , dl.`比率` AS `rate`
        , dl.`カラー` AS `color`
        , dl.`サイズ` AS `size`
        , dl.`在庫数` AS `stock`
        , dl.`販売開始日` AS `sales_start_date`
        , COALESCE(pl.baika_genka, 0) AS cost_tanka
        , COALESCE(mall.system_usage_cost_ratio, 40) AS system_usage_cost_ratio
      FROM tmp_work_shoplist_daily_sales_dl dl
      LEFT JOIN tb_shopping_mall      mall ON mall.ne_mall_id = :shoplistMallNeId
      LEFT JOIN tb_productchoiceitems pci  ON dl.商品番号 = pci.ne_syohin_syohin_code
      LEFT JOIN tb_mainproducts_cal   cal  ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT JOIN {$logDbName}.tb_product_price_log pl ON pl.log_date = dl.`対象日`
                                                    AND pci.daihyo_syohin_code = pl.daihyo_syohin_code
      ORDER BY dl.商品番号
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistMallNeId', ($shoppingMallShoplist ? $shoppingMallShoplist->getNeMallId() : 18), \PDO::PARAM_INT);
    $stmt->execute();

    // 実行後 行数
    $sql = <<<EOD
      SELECT DATE_FORMAT(dl.`対象日`, '%Y-%m-%d'), COUNT(*)
      FROM tmp_work_shoplist_daily_sales_dl dl
      GROUP BY dl.`対象日`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $count = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

    return [
      'count' => $count,
      'lastUpdated' => $lastUpdated
    ];
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '"順位","商品管理番号","卸品番","JANコード","商品名","販売個数(合計)","販売個数(通常販売)","販売個数(スピード便)","販売個数(予約)","受注高(税抜)","比率","カラー","サイズ","在庫数(合計)","在庫数(通常販売)","在庫数(スピード便)","在庫数(予約販売)","販売開始日"';

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);

    return (trim($line) === $validLine);
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  function validate(InputInterface $input)
  {
    $fromDate = $input->getOption('from-date');
    $toDate = $input->getOption('to-date');
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (! empty($fromDate)) {
      if (! preg_match($datePattern, $fromDate)) {
        throw new \RuntimeException('集計対象日（開始）の形式がYYYY-mm-ddではありません[' . $fromDate . ']');
      }
      list($year, $month, $day) = explode('-', $fromDate);
      if (!checkdate($month, $day, $year)) {
        throw new \RuntimeException('集計対象日（開始）が正しい日付ではありません[' . $fromDate . ']');
      }
    }
    if (! empty($toDate)) {
      if (! preg_match($datePattern, $toDate)) {
        throw new \RuntimeException('集計対象日（終了）の形式がYYYY-mm-ddではありません[' . $toDate . ']');
      }
      list($year, $month, $day) = explode('-', $toDate);
      if (!checkdate($month, $day, $year)) {
        throw new \RuntimeException('集計対象日（終了）が正しい日付ではありません[' . $toDate . ']');
      }
    }
  }
}
