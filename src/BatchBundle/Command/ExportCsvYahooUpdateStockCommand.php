<?php
/**
 * Yahoo CSV出力処理 在庫更新CSV
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvYahooUpdateStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  const EXPORT_PATH = 'Yahoo/Export';

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM = 100000; // 10万件で分割 （現状、quantity.csvのみ）
  const QUANTITY_MAX = 9999; // quantity 数量 最大値（NextEngineとの自動連携の数値に合わせたもの）

  protected $exportPath;

  protected $exportTarget;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-yahoo-update-stock')
      ->setDescription('CSVエクスポート Yahoo 在庫更新')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null)
      ->addOption('export-target', null, InputOption::VALUE_OPTIONAL, '出力対象', null)
      ->addOption('import-path', null, InputOption::VALUE_OPTIONAL, 'インポート在庫ファイルパス', null)
      ->addOption('include-reserved-stock', null, InputOption::VALUE_OPTIONAL, '予約在庫有無（おとりよせ.comなど）', 0)
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('Yahoo CSV出力処理（在庫更新）を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $now = new \DateTimeImmutable();
      $this->stopwatch->start('main');

      $this->results = [
        'quantity.csv' => null
      ];

      // 出力パス
      $this->exportPath = $input->getOption('export-dir');
      if (!$this->exportPath) {
        $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $now->format('YmdHis');
      }

      // 出力ディレクトリ 作成
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $fs->mkdir($this->exportPath, 0755);
      }

      $this->exportTarget = $input->getOption('export-target');
      if (
        !$this->exportTarget
        || !in_array($this->exportTarget , [
            ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO
          , ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON
          , ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
        ])
      ) {
        throw new \RuntimeException('unknown yahoo csv target : ' . $this->exportTarget);
      }

      $logExecTitle = sprintf('Yahoo CSV出力処理 在庫更新(%s)', $this->exportTarget);
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // CSV出力 データ作成処理 実装

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));

      // --------------------------------------
      // 在庫更新CSVデータ作成
      // --------------------------------------
      $this->exportStockCsv($input->getOption('import-path'), $input->getOption('include-reserved-stock'));
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('stock csv'));

      $finder = new Finder(); // 結果ファイル確認
      $fileNum = $finder->in($this->exportPath)->files()->count();
      if (!$fileNum) {
        $this->results['message'] = 'CSVファイルが作成されませんでした。処理を完了します。';
        // 空のディレクトリを削除
        $fs = new FileSystem();
        $fs->remove($this->exportPath);
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '終了')->setInformation($this->results));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Yahoo CSV出力処理（在庫更新）を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('Yahoo STOCK CSV Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Yahoo CSV出力処理 在庫更新', 'Yahoo CSV出力処理 在庫更新', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Yahoo CSV出力処理 在庫更新' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * 在庫更新CSV出力
   * @param string $importCsvPath
   * @param bool $includeReservedStock
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportStockCsv($importCsvPath, $includeReservedStock = false)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $logger->info('Yahoo 在庫更新CSV出力');

    // 販売不可在庫数 更新処理
    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    $neMallProcess->updateNotForSaleStock();

    $fs = new FileSystem();
    if (!$importCsvPath || !$fs->exists($importCsvPath)) {
      throw new \RuntimeException('在庫CSVファイルがないため、在庫更新を中止しました。[' . $importCsvPath . ']');
    }

    $logger->info('quantity.csv 作成中');
    $logger->info('Yahoo在庫データインポート');

    $fileTimeStamp = new \DateTime();
    $fileTimeStamp->modify('+5 minutes'); // 5分もあればアップロードまで完了していることを期待

    $commonUtil = $this->getDbCommonUtil();
    // 出品フラグ確認のための連結テーブル取得
    $informationTable = $commonUtil->getYahooTargetTableName($this->exportTarget);
    $productStockTable = $commonUtil->getYahooProductStockTableName($this->exportTarget);

    $dbMain->query("TRUNCATE {$productStockTable}");
    $sql = <<<EOD
      LOAD DATA LOCAL INFILE :importFilePath
      INTO TABLE {$productStockTable}
      FIELDS TERMINATED BY ',' ENCLOSED BY '' ESCAPED BY ''
      LINES TERMINATED BY '\n'
      IGNORE 1 LINES
      (`code`, `sub-code`, `quantity`)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':importFilePath', $importCsvPath);
    $stmt->execute();

    // 予約在庫込
    if ($includeReservedStock) {

      $sql = <<<EOD
      (
        /* 単一商品 */
        SELECT
            s.code
          , s.`sub-code`
          , T.quantity
        FROM {$productStockTable} s
        INNER JOIN {$informationTable} i ON s.code = i.daihyo_syohin_code
        INNER JOIN (
          SELECT
              pci.ne_syohin_syohin_code
            , CASE
                WHEN (pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0) THEN :quantityMax
                WHEN pci.フリー在庫数 + pci.予約フリー在庫数 > :quantityMax THEN :quantityMax
                ELSE pci.フリー在庫数 + pci.予約フリー在庫数
              END AS quantity
          FROM tb_mainproducts m
          INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
          WHERE m.set_flg = 0
        ) T ON s.`sub-code` = T.ne_syohin_syohin_code
        WHERE s.quantity <> T.quantity
          AND i.registration_flg <> 0
      )  
      UNION ALL
      (
        /* セット商品 */
        SELECT
            s.code
          , s.`sub-code`
          , T.creatable_num AS `quantity`
        FROM {$productStockTable} s
        INNER JOIN {$informationTable} i ON s.code = i.daihyo_syohin_code
        INNER JOIN (
          SELECT
              pci.ne_syohin_syohin_code AS set_sku
            , MIN(TRUNCATE((CASE
                WHEN (pci_detail.受発注可能フラグ <> 0 AND cal_detail.受発注可能フラグ退避F = 0) THEN :quantityMax
                WHEN pci_detail.フリー在庫数 + pci_detail.予約フリー在庫数 > :quantityMax THEN :quantityMax
                ELSE pci_detail.フリー在庫数 + pci_detail.予約フリー在庫数
              END / d.num), 0)) AS creatable_num
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
          INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
          INNER JOIN tb_mainproducts_cal AS cal_detail ON pci_detail.daihyo_syohin_code = cal_detail.daihyo_syohin_code  
          WHERE m.set_flg <> 0
          GROUP BY set_sku
        ) T ON s.`sub-code` = T.set_sku
        WHERE s.quantity <> T.creatable_num
        AND i.registration_flg <> 0
      )
      ORDER BY `sub-code`
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':quantityMax', ExportCsvYahooOtoriyoseCommand::QUANTITY_MAX, \PDO::PARAM_INT);
      $stmt->execute();

    // 即納在庫のみ
    } else {
      $sql = <<<EOD
      (
        /* 単一商品 */
        SELECT
            s.code
          , s.`sub-code`
          , pci.`フリー在庫数` AS quantity
        FROM {$productStockTable} s
        INNER JOIN tb_productchoiceitems pci ON s.`sub-code` = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts m ON s.code = m.daihyo_syohin_code
        INNER JOIN {$informationTable} i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
        WHERE s.quantity <> pci.`フリー在庫数`
          AND i.registration_flg <> 0
          AND m.set_flg = 0 
      )
      UNION ALL
      (
        /* セット商品 */
        SELECT 
            s.code
          , s.`sub-code`
          , T.creatable_num AS quantity
        FROM {$productStockTable} s
        INNER JOIN (
          SELECT
              pci.ne_syohin_syohin_code AS set_sku
            , MIN(TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0))  AS creatable_num /* 内訳SKUフリー在庫からの作成可能数 */
          FROM tb_productchoiceitems pci 
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN {$informationTable} i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
          INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
          WHERE i.registration_flg <> 0
            AND m.set_flg <> 0
          GROUP BY set_sku
        ) T ON s.`sub-code` = T.set_sku
        WHERE s.quantity <> T.creatable_num
      )
      ORDER BY `sub-code`
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
    }

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
          'code'
        , 'sub-code'
        , 'quantity'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // このファイルは10万件で分割する。（他のものも上限は10万件だが、現状制限内なのでこれだけ。）
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/quantity%s%02d00.csv', $this->exportPath, $fileTimeStamp->format('YmdH'), intval($fileTimeStamp->format('i')) + $fileIndex++);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $this->results['quantity.csv'] = $num;
      $logger->info("Yahoo CSV出力 quantity.csv: $num 件");

    } else {
      $logger->info("Yahoo CSV出力 quantity.csv: 件数が0のためファイルは作成しませんでした。");
    }

  }
}
