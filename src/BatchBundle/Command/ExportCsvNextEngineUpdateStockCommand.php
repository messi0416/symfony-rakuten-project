<?php
/**
 * NextEngine 在庫同期CSV出力
 * （増加分）商品一括登録CSV出力＆アップロード
 * （減少分）クイック棚卸リスト一括登録CSV 出力のみ
 */

namespace BatchBundle\Command;

use BatchBundle\Job\NextEngineUploadJob;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvNextEngineUpdateStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;
  
  /** 手動で在庫データ（オリジナル）をダウンロードし、それをもとにCSV出力を行う場合の、WEB_CSV以下のファイルの置き先 */
  const FILE_DIR_MANUAL_DOWNLOAD_STOCK_DATA_ORIGINAL = "/NextEngine/Downloaded/OriginalStocks/InStock";

  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-next-engine-update-stock')
      ->setDescription('CSVエクスポート NextEngine在庫同期')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('do-download', null, InputOption::VALUE_OPTIONAL, 'NextEngineから在庫データダウンロード（オリジナルCSV）を行うか。行わない場合手動で配置が必要', '1') // デフォルト ON
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'NextEngineへのアップロードを行うか', '0') // デフォルト OFF
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, '対象のNE環境', 'test'); // デフォルト test
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    $logExecTitle = 'NextEngineCSV在庫同期処理';
    $logger->info($logExecTitle . 'を開始しました。');

    $logger->setExecTitle($logExecTitle);

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
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      $dbMain = $this->getDb('main');

      /** @var NextEngineMallProcess $neMallProcess */
      $neMallProcess = $container->get('batch.mall_process.next_engine');

      // 在庫同期時にNEと比較
      $client = $webAccessUtil->getWebClient();
      
      // NEログイン・メインページへの遷移
      $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える
      $neCount = $webAccessUtil->getNePrintCount($client); // NE側カウント取得
      $dbCount = $neMallProcess->getPrintCount();
        
      if ($dbCount !== $neCount ) {
        $logger->error('NextEngineCSV在庫同期処理 印刷済み件数不整合 DB:'.$dbCount.' NE:'.$neCount);
        $logger->addDbLog(
          $logger->makeDbLog('NextEngineCSV在庫同期処理 エラー', 'NextEngineCSV在庫同期処理 エラー', '印刷済み件数不整合 DB側:'.$dbCount.' NE側:'.$neCount)->setInformation('DB側:'.$dbCount.' NE側:'.$neCount)
          , true, 'NextEngineCSV在庫同期処理 印刷済み件数集計エラー DB側:'.$dbCount.' NE側:'.$neCount, 'error'
        );
      }

      $result = [
          'NE_UpdateStock' => 0
        , 'NE_Inventory' => 0
      ];

      // NextEngine CSVアップロード状態取得処理 実行
      // ※重複実行防止用。加減の数値のみアップロード可能であるため、未反映時の再実行データが重複反映されると在庫が狂う。
      //   そのくせ処理の遅延・詰まりはままある。(NextEngine... --ﾒ)
      $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      ];
      if (!is_null($input->getOption('account'))) {
        $commandArgs[] = sprintf('--account=%d', $input->getOption('account'));
      }
      if (!is_null($input->getOption('target-env'))) {
        $commandArgs[] = sprintf('--target-env=%s', $input->getOption('target-env'));
      }
      $commandInput = new ArgvInput($commandArgs);
      $commandOutput = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.update_ne_upload_status');
      $ret = $command->run($commandInput, $commandOutput);

      $logger->info('ne csv upload status check: ' . $ret);
      // CSVアップロード状態取得処理 エラー
      if ($ret != 0) {
        throw new \RuntimeException('NextEngine在庫同期処理：実行前 CSVアップロード状態取得処理でエラーが発生しました。処理を中止しました。');
      }

      // CSVアップロード状態チェック 「処理中」「処理待ち」のレコードがあれば、処理を中止。
      $sql = <<<EOD
        SELECT
            ステータス
          , 登録日
          , ファイル名
          , 登録者
          , メッセージ
        FROM tb_ne_upload_check
        WHERE ステータス IN ('処理中', '処理待ち' /* , '処理成功' */ )
          AND 登録日 >= DATE_ADD(CURRENT_DATE, INTERVAL -3 DAY)
        ORDER BY 登録日, id
EOD;
      $result['unfinished'] = $this->getDb('main')->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

      if ($result['unfinished']) {
        throw new \RuntimeException('NextEngine在庫同期処理：実行前 CSVアップロード状態取得処理で「処理中」「処理待ち」レコードが確認されました。処理を中止しました。 ' . print_r($result, true));
      }
      
      $fs = new FileSystem();
      $finder = new Finder();

      // NextEngine在庫データダウンロード（差分確認用）
      // CSVダウンロード処理
      $importDir = null;
      $doDownload = (bool)$input->getOption('do-download');
      if ($doDownload) {
        $importDir = $this->getFileUtil()->getDataDir() . '/stocks/' . (new \DateTime())->format('YmdHis');
        $neMallProcess->downloadNextEngineStockDataOriginal($importDir, $this->account, $input->getOption('target-env'), true, 'stock');
      } else {
        $importDir = $this->getFileUtil()->getWebCsvDir() . self::FILE_DIR_MANUAL_DOWNLOAD_STOCK_DATA_ORIGINAL;
        if (!$fs->exists($importDir) || $finder->in($importDir)->files('/*.csv')->count() === 0) {
          throw new \RuntimeException('在庫データ（オリジナルCSV）の自動ダウンロードをしない場合、' . self::FILE_DIR_MANUAL_DOWNLOAD_STOCK_DATA_ORIGINAL . 'にファイルが必要です');
        }
        $neMallProcess->downloadNextEngineStockDataOriginal($importDir, $this->account, $input->getOption('target-env'), false, 'stock');
      }

      // 差分CSV出力（増加分）
      $outputDir = $this->getFileUtil()->getWebCsvDir() . '/NextEngineUpdateStock';
      if (!$fs->exists($outputDir)) {
        $fs->mkdir($outputDir, 0755);
      }

      // 7日以上前のファイルは削除
      $now = new \DateTime();
      $ago = clone $now;
      $ago->modify('-7 day');

      $deleteFiles = $finder->in($outputDir)->date('<= ' . $ago->format('Y-m-d 00:00:00'));
      foreach($deleteFiles as $file) {
        $fs->remove($file);
      }

      /** @var \MiscBundle\Util\StringUtil $stringUtil */
      $stringUtil = $this->getContainer()->get('misc.util.string');

      $filePathUpdateStock = sprintf('%s/NE_UpdateStock_%s.csv', $outputDir, $now->format('YmdHis'));
      $filePathInventory = sprintf('%s/NE_Inventory_%s.csv', $outputDir, $now->format('YmdHis'));

      $filePathUpdateStockLog = sprintf('%s/NE_UpdateStock_%s_debug_log.csv', $outputDir, $now->format('YmdHis'));

      $logger->info($filePathUpdateStock);
      $logger->info($filePathInventory);
      $logger->info($filePathUpdateStockLog);

      // NextEngine CSV出力最終日時
      // ※これ以降の登録SKUはNextEngine商品マスタ登録がないはずなので出力しない。
      // （出力および反映の時差として、1時間を減算）
      $commonUtil = $this->getDbCommonUtil();
      $lastNeCsvExported = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE);
      if (!$lastNeCsvExported) {
        $lastNeCsvExported = new \DateTime();
      }
      $lastNeCsvExported->modify('-1 hour');

      // 販売不可在庫数 更新処理
      $neMallProcess->updateNotForSaleStock();

      $mallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);

      // 増加・減少分 出力
      $sql = <<<EOD
        SELECT
            T.ne_syohin_syohin_code AS syohin_code
          , T.stock - COALESCE(dl.`在庫数`, 0) AS zaiko_su
          
          , dl.`在庫数` AS NE_在庫数
          , T.stock AS アップロード在庫数
          , pci_出荷予定取置数
          , pci_ピッキング引当数
          , pci_販売不可在庫数
          , shoplist_order
        FROM (
          SELECT
              pci.ne_syohin_syohin_code
            , pci.`在庫数` AS total_stock
            , (
                   pci.在庫数 
                + (pci.出荷予定取置数 - pci.ピッキング引当数)
                - pci.販売不可在庫数
                + COALESCE(SO.shoplist_order, 0) 
              ) AS stock
            
            , pci.出荷予定取置数   AS pci_出荷予定取置数 
            , pci.ピッキング引当数 AS pci_ピッキング引当数 
            , pci.販売不可在庫数   AS pci_販売不可在庫数
            , COALESCE(SO.shoplist_order, 0) AS shoplist_order
          FROM tb_productchoiceitems pci
          LEFT JOIN (
            SELECT
                a.`商品コード（伝票）` AS ne_syohin_syohin_code
              , SUM(a.`受注数`) AS shoplist_order
            FROM tb_sales_detail_analyze a
            INNER JOIN tb_mainproducts m ON a.daihyo_syohin_code = m.daihyo_syohin_code
            WHERE m.fba_multi_flag <> 0
              AND a.`受注状態` <> '出荷確定済（完了）'
              AND a.`明細行キャンセル` = '0'
              AND a.`キャンセル区分` = '0'
              AND a.`店舗コード` = :neMallIdShoplist
            GROUP BY a.`商品コード（伝票）`   
          ) SO ON pci.ne_syohin_syohin_code = SO.ne_syohin_syohin_code
          WHERE pci.created < :lastNeCsvExported
        ) T
        LEFT JOIN tb_totalstock_dl dl ON T.ne_syohin_syohin_code = dl.`商品コード`
        WHERE T.stock <> COALESCE(dl.`在庫数`, 0)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':neMallIdShoplist', $mallShoplist->getNeMallId(), \PDO::PARAM_INT);
      $stmt->bindValue(':lastNeCsvExported', $lastNeCsvExported->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->rowCount()) {

        $syohinCodeList = [];
        $logData = [];

        // ヘッダ
        $headers = [
            'syohin_code'
          , 'zaiko_su'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        // データ
        $num = 0;

        $fp = fopen($filePathUpdateStock, 'wb');
        fputs($fp, $headerLine);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          $syohinCodeList[] = $dbMain->quote($row['syohin_code']);
          $logData[$row['syohin_code']] = $row;

          $num++;
        }
        fclose($fp);

        $result['NE_UpdateStock'] = $num;
        $logger->info("NextEngine 在庫同期処理 NE_UpdateStock: $num 件");

        // 不具合調査のため、ログ出力
        $syohinCodeListStr = implode(', ', $syohinCodeList);

        $sql = <<<EOD
          SELECT 
            v.*
          FROM v_product_stock_total v
          WHERE v.ne_syohin_syohin_code IN (
            {$syohinCodeListStr}
          )
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();

        // ヘッダ
        $headers = [
            'syohin_code'
          , 'zaiko_su'

          , 'NE_在庫数'
          , 'アップロード在庫数'
          , 'pci_出荷予定取置数'
          , 'pci_ピッキング引当数'
          , 'pci_販売不可在庫数'
          , 'shoplist_order'

          , 'ne_syohin_syohin_code'
          , 'daihyo_syohin_code'
          , '在庫数'
          , '受注数'
          , '引当数'
          , '未引当数'
          , 'ピッキング引当数'
          , '出荷予定取置数'
          , '移動中在庫数'
          , '販売不可在庫数'
          , '発注残数'
          , '総在庫数'
          , 'フリー在庫数'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers) . "\n";

        $fp = fopen($filePathUpdateStockLog, 'wb');
        fputs($fp, $headerLine);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $row = array_merge($row, $logData[$row['ne_syohin_syohin_code']]);

          $line = $stringUtil->convertArrayToCsvLine($row, $headers) . "\n";
          fputs($fp, $line);
        }
        fclose($fp);
      }

      // 減少分は、棚卸しファイルとしても出力（引当回収のため。必要のあるものに限定せずにひとまず減少分はすべて出力）
      $sql = <<<EOD
        SELECT
            T.ne_syohin_syohin_code AS 商品コード
          , CASE
               WHEN T.stock <= 0 THEN 0
               ELSE T.stock
            END AS 棚卸在庫数
        FROM (
          SELECT
              pci.ne_syohin_syohin_code
            , (
                   pci.在庫数 
                + (pci.出荷予定取置数 - pci.ピッキング引当数)
                - pci.販売不可在庫数
                + COALESCE(SO.shoplist_order, 0) 
              ) AS stock
          FROM tb_productchoiceitems pci
          LEFT JOIN (
            SELECT
                a.`商品コード（伝票）` AS ne_syohin_syohin_code
              , SUM(a.`受注数`) AS shoplist_order
            FROM tb_sales_detail_analyze a
            INNER JOIN tb_mainproducts m ON a.daihyo_syohin_code = m.daihyo_syohin_code
            WHERE m.fba_multi_flag <> 0
              AND a.`受注状態` <> '出荷確定済（完了）'
              AND a.`明細行キャンセル` = '0'
              AND a.`キャンセル区分` = '0'
              AND a.`店舗コード` = :neMallIdShoplist
            GROUP BY a.`商品コード（伝票）`   
          ) SO ON pci.ne_syohin_syohin_code = SO.ne_syohin_syohin_code
          WHERE pci.created < :lastNeCsvExported
        ) T 
        LEFT JOIN tb_totalstock_dl dl ON T.ne_syohin_syohin_code = dl.`商品コード`
        WHERE T.stock < COALESCE(dl.`在庫数`, 0)
        ;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':neMallIdShoplist', $mallShoplist->getNeMallId(), \PDO::PARAM_INT);
      $stmt->bindValue(':lastNeCsvExported', $lastNeCsvExported->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->rowCount()) {
        // ヘッダ
        $headers = [
            '商品コード'
          , '棚卸在庫数'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        // データ
        $num = 0;

        $fp = fopen($filePathInventory, 'wb');
        fputs($fp, $headerLine);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          $num++;
        }
        fclose($fp);

        $result['NE_Inventory'] = $num;
        $logger->info("NextEngine 在庫同期処理 NE_Inventory: $num 件");
      }

      // 差分CSVアップロード処理
      $finder = new Finder(); // 結果ファイル確認
      $message = '';
      $fileNum = $finder->in($outputDir)->name(basename($filePathUpdateStock))->files()->count();

      $doUpload = (bool)$input->getOption('do-upload');
      $logger->info('do-upload : ' . ($doUpload ? 'yes' : 'no') . ' / num: ' . $fileNum);

      if (!$fileNum) {
        $message = 'CSVファイルが作成されませんでした。処理を完了します。';
      } else {
        if ($doUpload) {
          $message = 'NextEngineアップロード処理を予約します。';
        }
      }
      $result['message'] = $message;

      // 最終処理日時 更新
      // ※棚卸CSVファイルの取得条件とするため、必ずファイル文字列に利用する日時で保存する。
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK, $now);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($result));
      $logger->logTimerFlush();

      $logger->info('NextEngine 在庫同期処理 CSV Export 完了');

      // 引き続き、NextEngineへのアップロード
      // → 別Jobとして別のキューで呼び出す（=> 排他でのリトライを独立して行うため）
      if ($fileNum > 0 && $doUpload) { // 引数で制御
        $rescue = $this->getResque();

        $job = new NextEngineUploadJob();

        $job->queue = 'neUpload'; // キュー名
        $job->args = [
              'command' => NextEngineUploadJob::COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS
            , 'dataDir' => $outputDir
            , 'file' => basename($filePathUpdateStock)
        ];
        if ($this->account) {
          $job->args['account'] = $this->account->getId();
        }

        // テスト環境ではNextEngineテスト環境へアップロード
        $env = $input->getOption('target-env');
        if ($env !== 'prod') {
          $job->args['targetEnv'] = 'test';

          $logger->info('NextEngine CSVアップロードはテスト環境！');
        } else {
          $job->args['targetEnv'] = 'prod';

          $logger->info('NextEngine CSVアップロードは本番環境！！！！！');
        }

        $rescue->enqueue($job);

        $logger->info('NextEngine CSVアップロード キュー追加');
      }

      return 0;

    } catch (\Exception $e) {

      $result['message'] = $e->getMessage();

      $logger->error(print_r($result, true));
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($result)
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
