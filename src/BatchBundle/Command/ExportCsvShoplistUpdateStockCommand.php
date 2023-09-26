<?php
/**
 * SHOPLIST CSV出力処理 在庫更新CSV
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvShoplistUpdateStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  CONST CSV_FILENAME_STOCK  = 'stock.csv';

  const EXPORT_PATH = 'Shoplist/Export';

  protected $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-shoplist-update-stock')
      ->setDescription('CSVエクスポート SHOPLIST 在庫更新')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null);
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
    $logger->info('SHOPLIST CSV出力処理（在庫更新）を開始しました。');

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
          'message' => null
        , 'stock' => null
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

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0

      $logExecTitle = sprintf('SHOPLIST CSV出力処理 在庫更新');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // CSV出力 データ作成処理 実装

      $dbMain = $this->getDb('main');

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));

      $stockPath = $this->exportPath . '/' . self::CSV_FILENAME_STOCK;

      // --------------------------------------
      // 在庫更新CSVデータ作成
      // --------------------------------------
      $this->exportStockCsv($stockPath);
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

      $logger->info('SHOPLIST CSV出力処理（在庫更新）を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('SHOPLIST STOCK CSV Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('SHOPLIST CSV出力処理 在庫更新', 'SHOPLIST CSV出力処理 在庫更新', 'エラー終了')->setInformation($e->getMessage())
        , true, 'SHOPLIST CSV出力処理 在庫更新' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * 在庫更新CSV出力
   * @param string $stockPath
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportStockCsv($stockPath)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $logger->info('SHOPLIST 在庫更新CSV出力');

    // 販売不可在庫数 更新処理
    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    $neMallProcess->updateNotForSaleStock();

    $commonUtil = $this->getDbCommonUtil();
    $mallShoplist = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_SHOPLIST);

    // 在庫更新用一時テーブル
    $dbMain->query("TRUNCATE tmp_shoplist_update_stock");

    // SHOPLISTフリー在庫 FBA在庫の扱いが逆転 @see http://tk2-217-18298.vs.sakura.ne.jp/projects/wikipro/wiki/在庫の定義（案）
    $sql = <<<EOD
      INSERT INTO tmp_shoplist_update_stock (
          ne_syohin_syohin_code
        , daihyo_syohin_code
        , colname
        , colcode
        , rowname
        , rowcode
        , `フリー在庫数`
      )
      SELECT
          pci.ne_syohin_syohin_code
        , pci.daihyo_syohin_code
        , pci.colname
        , pci.colcode
        , pci.rowname
        , pci.rowcode
        , GREATEST(COALESCE(STOCK.`SHOPLISTフリー在庫数`, 0), 0) AS `フリー在庫数`
      FROM tb_productchoiceitems pci
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_shoplist_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_shoplist_product_stock s ON pci.ne_syohin_syohin_code = s.sku
      LEFT JOIN (
        /* -- SHOPLISTフリー在庫 --------- */
        SELECT
            pci.ne_syohin_syohin_code
          , pci.`フリー在庫数`
          , GREATEST(COALESCE(FBA_ASSIGN.SHOPLIST以外引当数, 0) - COALESCE(FBA_STOCK.stock, 0), 0) AS SHOPLIST以外FBA引当_FBA倉庫不足分
          
            /* 2018/09/03 非販売倉庫のFBA商品在庫が加算されていたため、単純化。
            // 特定倉庫（南京終倉庫）の在庫のみとすることで、下記を自動的にクリア
            // その後の減算は全倉庫分が対象になるため引きすぎの可能性はあるが、引かなすぎよりマシなのでそのまま
            pci.在庫数
          + pci.出荷予定取置数
          - pci.販売不可在庫数
          - COALESCE(FBA_STOCK.stock, 0) / * - FBA倉庫在庫 * /
          + COALESCE(FBA_UNSENT.stock, 0)  / * + FBA倉庫以外のFBA商品在庫 * /
            */
          , (COALESCE(WS.stock, 0) /* => 特定倉庫在庫 */
            - COALESCE(NON_FBA_ASSIGN.引当数, 0) /* - FBA商品以外の受注引当 */
            - COALESCE(FBA_ASSIGN.SHOPLIST引当数, 0) /* -FBA商品のSHOPLISTの受注引当 */
            - GREATEST(COALESCE(FBA_ASSIGN.SHOPLIST以外引当数, 0) - COALESCE(FBA_STOCK.stock, 0), 0)) AS `SHOPLISTフリー在庫数`  /* -FBA商品のSHOPLIST以外の受注引当のうち、FBA倉庫に足りない数量 */
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_shoplist_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_shoplist_product_stock s ON pci.ne_syohin_syohin_code = s.sku
        /* 特定倉庫在庫 */
        LEFT JOIN (
          SELECT
              pl.ne_syohin_syohin_code
            , SUM(pl.stock) AS stock
          FROM tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
          INNER JOIN tb_warehouse w ON l.warehouse_id = w.id
          WHERE w.shoplist_flag = 1
          GROUP BY pl.ne_syohin_syohin_code 
        ) AS WS ON pci.ne_syohin_syohin_code = WS.ne_syohin_syohin_code
        /* FBA倉庫在庫 */
        LEFT JOIN (
          SELECT
             pl.ne_syohin_syohin_code
           , SUM(pl.stock) AS stock
          FROM tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
          WHERE l.warehouse_id = 6
          GROUP BY pl.ne_syohin_syohin_code
        ) AS FBA_STOCK ON pci.ne_syohin_syohin_code = FBA_STOCK.ne_syohin_syohin_code
        /* FBA倉庫以外のFBA商品在庫 */
        LEFT JOIN (
          SELECT
             pl.ne_syohin_syohin_code
           , SUM(pl.stock) AS stock
          FROM tb_product_location pl
          INNER JOIN tb_location l ON pl.location_id = l.id
          INNER JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          WHERE l.warehouse_id <> 6
            AND m.fba_multi_flag <> 0
          GROUP BY pl.ne_syohin_syohin_code
        ) AS FBA_UNSENT ON pci.ne_syohin_syohin_code = FBA_UNSENT.ne_syohin_syohin_code
        /* FBA商品以外の受注引当 */
        LEFT JOIN (
          SELECT
             a.`商品コード（伝票）` AS ne_syohin_syohin_code
           , SUM(a.`引当数`) AS 引当数
          FROM tb_sales_detail_analyze a FORCE INDEX (`index_有効受注`)
          INNER JOIN tb_productchoiceitems pci ON a.`商品コード（伝票）` = pci.ne_syohin_syohin_code
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          WHERE m.fba_multi_flag = 0
            AND a.受注状態 <> '出荷確定済（完了）'
            AND a.キャンセル区分 = '0'
            AND a.明細行キャンセル = '0'
          GROUP BY a.`商品コード（伝票）`
        ) NON_FBA_ASSIGN ON pci.ne_syohin_syohin_code = NON_FBA_ASSIGN.ne_syohin_syohin_code
        /* FBA商品の受注引当 */
        LEFT JOIN (
          SELECT
             a.`商品コード（伝票）` AS ne_syohin_syohin_code
           , SUM(CASE WHEN a.店舗コード = :shoplistNeId THEN a.`引当数` ELSE 0 END) AS SHOPLIST引当数
           , SUM(CASE WHEN a.店舗コード <> :shoplistNeId THEN a.`引当数` ELSE 0 END) AS SHOPLIST以外引当数
          FROM tb_sales_detail_analyze a FORCE INDEX (`index_有効受注`)
          INNER JOIN tb_productchoiceitems pci ON a.`商品コード（伝票）` = pci.ne_syohin_syohin_code
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          WHERE m.fba_multi_flag <> 0
            AND a.受注状態 <> '出荷確定済（完了）'
            AND a.キャンセル区分 = '0'
            AND a.明細行キャンセル = '0'
          GROUP BY a.`商品コード（伝票）`
        ) AS FBA_ASSIGN ON pci.ne_syohin_syohin_code = FBA_ASSIGN.ne_syohin_syohin_code
        WHERE pci.`在庫数` > 0
        /* --------------------------------------------------------------------------- */
      ) STOCK ON pci.ne_syohin_syohin_code = STOCK.ne_syohin_syohin_code
      ORDER BY pci.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistNeId', $mallShoplist->getNeMallId(), \PDO::PARAM_INT);
    $stmt->execute();

    // SHOPLIST セット商品 フリー在庫
    // 単品商品で在庫関係なく追加したので重複する
    $sql = <<<EOD
      INSERT INTO tmp_shoplist_update_stock (
        ne_syohin_syohin_code
        , daihyo_syohin_code
        , colname
        , colcode
        , rowname
        , rowcode
        , `フリー在庫数`
      )
      SELECT * 
      FROM (
        SELECT 
          pci.ne_syohin_syohin_code
          , pci.daihyo_syohin_code
          , pci.colname
          , pci.colcode
          , pci.rowname
          , pci.rowcode
          , MIN(TRUNCATE((COALESCE(tmp.`フリー在庫数`, 0) / d.num), 0)) AS creatable_num
        /* セット商品 */
        FROM tb_mainproducts m
        INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
  
        /* セット商品の構成品 */
        INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        LEFT JOIN tmp_shoplist_update_stock tmp ON pci_detail.ne_syohin_syohin_code = tmp.ne_syohin_syohin_code
        WHERE m.set_flg <> 0
        GROUP BY pci.ne_syohin_syohin_code
      ) STOCK
      ON DUPLICATE KEY UPDATE `フリー在庫数` = STOCK.creatable_num;
EOD;
    $dbMain->query($sql);

    // 在庫数の変わらない商品と未登録の商品は削除
    // #50128 出品フラグが外れているものは更新しない、でいったん統一。現状、出品フラグOFF = 0 更新だが、これを外して問題がないかは仕様不明。
   $sql = <<<EOD
      DELETE tmp
      FROM tmp_shoplist_update_stock tmp
      INNER JOIN tb_shoplist_product_stock s ON tmp.ne_syohin_syohin_code = s.sku
      INNER JOIN tb_shoplist_information i ON tmp.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE i.registration_flg = 0
        OR tmp.`フリー在庫数` = s.`項目選択肢別在庫用在庫数`
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      SELECT * FROM tmp_shoplist_update_stock
      ORDER BY ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->query($sql);

    $count = $stmt->rowCount();
    $logger->info('SHOPLIST 在庫更新CSV出力 : ' . $count);

    if ($count) {
      $headers = $this->getCsvHeadersStock();

      $fs = new FileSystem();
      $fileExists = $fs->exists($stockPath);
      $fp = fopen($stockPath, 'ab');

      if (!$fileExists) {
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $data = [
            '項目選択肢用コントロールカラム'     => 'u'
          , '商品管理番号（商品URL）'           => strtolower($row['daihyo_syohin_code'])
          , '選択肢タイプ'                     => 'i'
          , '項目選択肢別在庫用横軸選択肢'       => $row['colname']
          , '項目選択肢別在庫用横軸選択肢子番号' => $row['colcode']
          , '項目選択肢別在庫用縦軸選択肢'       => $row['rowname']
          , '項目選択肢別在庫用縦軸選択肢子番号' => $row['rowcode']
          , '項目選択肢別在庫用在庫数'          => $row['フリー在庫数']
        ];
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      fclose($fp);
    }

    // 在庫確認用テーブル 更新（在庫数）
    $sql = <<<EOD
      UPDATE tb_shoplist_product_stock s
      INNER JOIN tmp_shoplist_update_stock t ON s.sku = t.ne_syohin_syohin_code
      SET s.項目選択肢別在庫用在庫数 = t.フリー在庫数
EOD;
    $dbMain->query($sql);

    $this->results['stock'] = [
        'count' => $count
    ];
  }

  /**
   * CSVヘッダ取得（在庫更新）
   */
  private function getCsvHeadersStock()
  {
    $headers = [
        '項目選択肢用コントロールカラム'
      , '商品管理番号（商品URL）'
      , '選択肢タイプ'
      , '項目選択肢別在庫用横軸選択肢'
      , '項目選択肢別在庫用横軸選択肢子番号'
      , '項目選択肢別在庫用縦軸選択肢'
      , '項目選択肢別在庫用縦軸選択肢子番号'
      , '項目選択肢別在庫用在庫数'
    ];

    return $headers;
  }

}
