<?php
/**
 * Amazon 在庫更新CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbShippingdivision;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvAmazonUpdateStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM = 30000; // 3万件で分割

  protected $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-amazon-update-stock')
      ->setDescription('CSVエクスポート Amazon 在庫更新')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null)
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗 vogue|us_plusnao', AmazonMallProcess::SHOP_NAME_VOGUE)
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
    $logger->info('Amazon 在庫更新CSV出力処理を開始しました。');

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

      $shop = $input->getOption('shop');
      if (!in_array($shop, [
          AmazonMallProcess::SHOP_NAME_VOGUE
        , AmazonMallProcess::SHOP_NAME_US_PLUSNAO
      ])) {
        throw new \RuntimeException('invalid amazon shop : ' . $shop);
      }

      $this->results = [
          'message' => null
        , 'update' => null
        , 'shop' => $shop
      ];

      $logExecTitle = sprintf('Amazon 在庫更新CSV出力処理(' . $shop . ')');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 入力・出力パス
      // 出力パス
      $this->exportPath = $input->getOption('export-dir');
      if (!$this->exportPath) {
        /** @var AmazonMallProcess $mallProcess */
        $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
        $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . $mallProcess->getCsvDirName($shop) . '/Export/' . $now->format('YmdHis');
      }

      // 出力ディレクトリ 作成
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $fs->mkdir($this->exportPath, 0755);
      }


      // ====================================================
      // FBA在庫データ更新（FBA在庫情報 ダウンロード ＆ データ更新）
      // ====================================================
      // /** @var AmazonMallProcess $mallProcess */
      // $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');

      // $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '開始'));
      // $mallProcess->updateFbaProductStock(AmazonMallProcess::SHOP_NAME_VOGUE);
      // $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '終了'));

      // CSV出力 データ作成処理 実装

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));

      // --------------------------------------
      // 'Export___
      // --------------------------------------
      $this->export($shop);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('export'));

      // --------------------------------------
      // 差分確認テーブル更新、更新フラグリセット
      // --------------------------------------
      $this->updateProductStockTable($shop);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('update_product_stock'));

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

      $logger->info('Amazon 在庫更新CSV出力処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('Amazon CSV Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazon 在庫更新CSV出力処理', 'Amazon 在庫更新CSV出力処理', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Amazon 在庫更新CSV出力処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * CSV出力処理
   * @param string $shop
   * @throws \Doctrine\DBAL\DBALException
   */
  private function export($shop)
  {
    $logger = $this->getLogger();
    $db = $this->getDb('main');

    $logTitle = 'Amazon 在庫更新CSV出力処理';
    $subTitle = 'Export___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $commonUtil = $this->getDbCommonUtil();

    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');

    // ====================
    // Amazon_stock.txt
    // JP: テンプレートは 『価格と数量変更ファイル(汎用版)』
    // COM: テンプレートは『価格と数量変更テンプレート』 (https://sellercentral.amazon.com/gp/help/201576480/ref=ag_201576480_cont_201576430)
    // ====================
    $logger->info('Amazon_stock.txt 作成中');

    $stmt = null;
    if ($shop == AmazonMallProcess::SHOP_NAME_VOGUE) {
/*
      $headerDescription = "TemplateType=ConsumerElectronics\tVersion=2015.1214\t上3行は Amazon.com 記入用です。上3行は変更または削除しないでください。\t\t出品情報 - 商品をサイト上で販売可能にする際に必要な項目\t\t\t";
      $headers = [
          'item_sku'                 => '出品者SKU'
        , 'external_product_id'      => '商品コード(JANコード等)'
        , 'external_product_id_type' => '商品コードのタイプ'
        , 'update_delete'            => 'アップデート・削除'
        , 'standard_price'           => '商品の販売価格'
        , 'standard_price_points'    => 'ポイント'
        , 'quantity'                 => '在庫数'
        , 'fulfillment_latency'      => 'リードタイム(出荷までにかかる作業日数)'
      ];
*/

      $headerDescription = "TemplateType=PriceInventory\tVersion=2018.0924\tこの行はAmazonが使用しますので変更や削除しないでください。\t\t\t\t\t\t\t\t\t\t\t";
      $headers = [
          'sku' => '商品管理番号',
          'price' => '販売価格',
          'standard-price-points' => 'ポイント',
          'quantity' => '在庫数',
          'currency' => '通貨コード',
          'sale-price' => 'セール価格',
          'sale-price-points' => 'セール時ポイント',
          'sale-from-date' => 'セール開始日',
          'sale-through-date' => 'セール終了日',
          'restock-date' => '商品の入荷予定日',
          'minimum-seller-allowed-price' => '販売価格の下限設定',
          'maximum-seller-allowed-price' => '販売価格の上限設定',
          'fulfillment-channel' => '出荷経路',
          'handling-time' => '出荷作業日数',
      ];

      $sql = <<<EOD
      /* 通常商品 */
      (
        SELECT
            stock.sku        AS `sku`
          , ''               AS `price`
          , ''               AS `standard-price-points`
          , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
              WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                OR (pci_sd.shipping_group_code IS NULL AND 
                  mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                ) THEN 0
              ELSE pci.フリー在庫数 
              END
            AS `quantity`
          , ''               AS `sale-price`
          , ''               AS `sale-price-points`
          , ''               AS `sale-from-date`
          , ''               AS `sale-through-date`
          , ''               AS `restock-date`
          , ''               AS `minimum-seller-allowed-price`
          , ''               AS `maximum-seller-allowed-price`
          , ''               AS `fulfillment-channel`
          , :fulfillmentLatency AS `handling-time`
        FROM tb_amazon_product_stock stock
        INNER JOIN tb_productchoiceitems pci ON stock.sku = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_amazoninfomation i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts m ON  pci.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
        INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
        WHERE stock.fba_quantity_fulfillable = 0 /* FBA商品は出力しない ※出力すると、非FBAに戻るAmazonの挙動あり */
          AND m.set_flg = 0 /* 通常商品 */
          AND (
            -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分
            (
              ((pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                OR (pci_sd.shipping_group_code IS NULL AND mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)))
                AND stock.quantity > 0)
              OR 
              ((pci_sd.shipping_group_code NOT IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) 
               OR (pci_sd.shipping_group_code IS NULL AND mp_sd.shipping_group_code NOT IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)))
               AND stock.quantity <> pci.`フリー在庫数`)
            )
            OR (
              -- 定形外・定形でも、在庫があるものは念のためリードタイム変更は反映する
                  pci.フリー在庫数 > 0
              AND stock.lead_time <> :fulfillmentLatency 
            )
          )
          AND i.registration_flg <> 0
      )
      UNION ALL
      /* セット商品 */
      (
        SELECT
            stock.sku           AS `sku`
          , ''                  AS `price`
          , ''                  AS `standard-price-points`
          , set_detail.creatable_num AS `quantity`
          , ''                  AS `sale-price`
          , ''                  AS `sale-price-points`
          , ''                  AS `sale-from-date`
          , ''                  AS `sale-through-date`
          , ''                  AS `restock-date`
          , ''                  AS `minimum-seller-allowed-price`
          , ''                  AS `maximum-seller-allowed-price`
          , ''                  AS `fulfillment-channel`
          , :fulfillmentLatency AS `handling-time`
        FROM tb_amazon_product_stock stock
        INNER JOIN (
          SELECT 
            pci.ne_syohin_syohin_code
            , i.registration_flg
            , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
                WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                  OR (pci_sd.shipping_group_code IS NULL AND 
                    mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                  ) THEN 0
                ELSE MIN(TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0)) /* 内訳SKUフリー在庫からの作成可能数 */
                END
              AS creatable_num
            -- 配送グループコードが定形外・定形郵便になるなら返却 それ以外はnull
            , CASE 
                WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) THEN pci_sd.shipping_group_code
                WHEN (pci_sd.shipping_group_code IS NULL AND 
                    mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                  ) THEN mp_sd.shipping_group_code
                ELSE NULL
                END
              AS shipping_group_code
          /* セット商品 */
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN tb_amazoninfomation i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          INNER JOIN tb_mainproducts m ON  pci.daihyo_syohin_code = m.daihyo_syohin_code
          LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
          INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
          
          /* セット商品の構成品 */
          INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
          INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
          WHERE m.set_flg <> 0 /* セット商品 */
          GROUP BY pci.ne_syohin_syohin_code
        ) set_detail ON stock.sku = set_detail.ne_syohin_syohin_code
        WHERE stock.fba_quantity_fulfillable = 0 /* FBA商品は出力しない ※出力すると、非FBAに戻るAmazonの挙動あり */
          AND (
            -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分　set_detail.shipping_group_codeは定形外・定形だけ返却、その他はnull
            (
              (set_detail.shipping_group_code IS NOT NULL AND stock.quantity > 0)
              OR
              (set_detail.shipping_group_code IS NULL AND stock.quantity <> set_detail.creatable_num) 
            )
            OR (
                  set_detail.creatable_num > 0
              AND stock.lead_time <> :fulfillmentLatency 
            )
          )
          AND set_detail.registration_flg <> 0
      )
      ORDER BY `sku`
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':fulfillmentLatency', $commonUtil->getDaysForImmediateShippingDate(), \PDO::PARAM_INT);
      $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
      $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
      $stmt->execute();

    } else if ($shop == AmazonMallProcess::SHOP_NAME_US_PLUSNAO) {

      $headerDescription = '';
      $headers = [
          'sku'                 => 'sku'
        , 'price'               => 'price'
        , 'quantity'            => 'quantity'
        , 'leadtime'            => 'leadtime-to-ship'
        , 'fulfillment_channel' => 'fulfillment-channel'
      ];

      $sql = <<<EOD
      /* 通常商品 */
      (
        SELECT
          stock.sku           AS `sku`
        , ''                  AS `price` /* 価格は更新しない */
        , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
            WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
              OR pci_sd.shipping_group_code IS NULL AND (
                mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
              ) THEN 0
            ELSE pci.フリー在庫数 
            END
          AS `quantity`
        , :fulfillmentLatency AS `leadtime`
        , 'default'           AS `fulfillment_channel` /* FBA商品は出力しないため、default固定 */
        FROM tb_amazon_com_product_stock stock
        INNER JOIN tb_productchoiceitems pci ON stock.sku = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_amazon_com_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
        INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
        WHERE stock.fba_quantity_fulfillable = 0 /* FBA商品は出力しない ※US版はここで切り替え可能だが、差分出力が困難。JP版と同じとする */
          AND m.set_flg = 0 /* 通常商品 */
          AND (
            -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分
            (
              (pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) AND stock.quantity > 0)
              OR
              (pci_sd.shipping_group_code NOT IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) AND stock.quantity <> pci.`フリー在庫数`)
            )
            OR (
                  pci.フリー在庫数 > 0
              AND stock.lead_time <> :fulfillmentLatency
            )
          )
          AND i.registration_flg <> 0
      )
      UNION ALL
      /* セット商品 */
      (
        SELECT
            stock.sku           AS `sku`
          , ''                  AS `price` /* 価格は更新しない */
          , set_detail.creatable_num AS `quantity`
          , :fulfillmentLatency AS `leadtime`
          , 'default'           AS `fulfillment_channel` /* FBA商品は出力しないため、default固定 */
        FROM tb_amazon_com_product_stock stock
        INNER JOIN (
          SELECT 
            pci.ne_syohin_syohin_code
            , i.registration_flg
            , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
                WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                  OR pci_sd.shipping_group_code IS NULL AND (
                    mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                  ) THEN 0
                ELSE MIN(TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0)) /* 内訳SKUフリー在庫からの作成可能数 */
                END
              AS creatable_num
            -- 配送グループコードが定形外・定形郵便になるなら返却 それ以外はnull
            , CASE 
                WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) THEN pci_sd.shipping_group_code
                WHEN (pci_sd.shipping_group_code IS NULL AND 
                    mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                  ) THEN mp_sd.shipping_group_code
                ELSE NULL
                END
              AS shipping_group_code
          /* セット商品 */
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
          INNER JOIN tb_amazon_com_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          INNER JOIN tb_mainproducts m ON  pci.daihyo_syohin_code = m.daihyo_syohin_code
          LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
          INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
          
          /* セット商品の構成品 */
          INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
          INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
          WHERE m.set_flg <> 0 /* セット商品 */
          GROUP BY pci.ne_syohin_syohin_code
        ) set_detail ON stock.sku = set_detail.ne_syohin_syohin_code
        WHERE stock.fba_quantity_fulfillable = 0 /* FBA商品は出力しない ※出力すると、非FBAに戻るAmazonの挙動あり */
          AND (
            -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分　set_detail.shipping_group_codeは定形外・定形だけ返却、その他はnull
            (
              (set_detail.shipping_group_code IS NOT NULL AND stock.quantity > 0)
              OR
              (set_detail.shipping_group_code IS NULL AND stock.quantity <> set_detail.creatable_num) 
            )
            OR (
                  set_detail.creatable_num > 0
              AND stock.lead_time <> :fulfillmentLatency 
            )
          )
          AND set_detail.registration_flg <> 0
      )
      ORDER BY `sku`
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':fulfillmentLatency', $commonUtil->getDaysForImmediateShippingDate(), \PDO::PARAM_INT);
      $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
      $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
      $stmt->execute();

    } else {
      throw new \RuntimeException('unreachable code');
    }

    // 出力
    if ($stmt->rowCount()) {

      // ファイル番号
      $fileNum = 1;
      $files = [];
      $totalCount = 0;
      $lineCount = 0;
      $fp = null;
      $noEncloseFields = [];

      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp)) {
          $filePath = sprintf('%s/Amazon_stock_%d.txt', $this->exportPath, $fileNum);
          $fp = fopen($filePath, 'wb');

          if ($shop == AmazonMallProcess::SHOP_NAME_VOGUE) {
            fputs($fp, $mallProcess->createCsvHeaderLines($headerDescription, $headers));
          } else {
            fputs($fp, $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], "\t") . "\r\n");
          }
          $files[] = $filePath;
        }

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), $noEncloseFields, "\t") . "\r\n";
        if ($shop == AmazonMallProcess::SHOP_NAME_VOGUE) {
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
        }
        fputs($fp, $line);

        $totalCount++;
        $lineCount++;

        // 制限を超えれば次のファイルへ。
        if ($lineCount >= self::UPLOAD_CSV_MAX_NUM) {
          fclose($fp);
          $lineCount = 0;
          unset($fp);
          $fileNum++;
        }
      }

      $this->results['update'] = [
          'count' => $totalCount
        , 'file_num' => $fileNum
        , 'files' => $files
      ];
      $logger->info("Amazon CSV出力 Amazon_stock_x.txt: $totalCount 件 / $fileNum ファイル");

    } else {
      $logger->info("Amazon CSV出力 Amazon_stock_x.txt: 件数が0のためファイルは作成しませんでした。");
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 差分確認テーブル更新 （数量、リードタイム）。
   * tb_amazon_product_stock は、CsvDownloadAndUpdateAmazonProductStockCommand で取り込まれる。
   * このため、ここでINSERTされるのは、基本的に新規商品のみ。
   * @param $shop
   * @throws \Doctrine\DBAL\DBALException
   */
  private function updateProductStockTable($shop)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');

    $stockTableName = $mallProcess->getProductStockTableName($shop);

    // 通常商品 更新SKU レコード追加・更新
    $sql = <<<EOD
      INSERT INTO `{$stockTableName}` (
          sku
        , quantity
        , lead_time
      )
      SELECT
          stock.sku AS `sku`
        , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
            WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
              OR pci_sd.shipping_group_code IS NULL AND (
                mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
              ) THEN 0
            ELSE pci.フリー在庫数 
            END
          AS `quantity`
        , :fulfillmentLatency AS `leadtime-to-ship`
      FROM `{$stockTableName}` stock
      INNER JOIN tb_productchoiceitems pci ON stock.sku = pci.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts m ON  pci.daihyo_syohin_code = m.daihyo_syohin_code
      LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
      INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
      WHERE m.set_flg = 0
        AND (
          -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分
            -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分
            (
              ((pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                OR (pci_sd.shipping_group_code IS NULL AND mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)))
                AND stock.quantity > 0)
              OR 
              ((pci_sd.shipping_group_code NOT IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) 
               OR (pci_sd.shipping_group_code IS NULL AND mp_sd.shipping_group_code NOT IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)))
               AND stock.quantity <> pci.`フリー在庫数`)
            )
          OR (
            pci.フリー在庫数 > 0 AND stock.lead_time <> :fulfillmentLatency
          )
        )
      ON DUPLICATE KEY UPDATE
          quantity  = VALUES(quantity)
        , lead_time = VALUES(lead_time)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fulfillmentLatency', $commonUtil->getDaysForImmediateShippingDate(), \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->execute();

    // セット商品 更新SKU レコード追加・更新
    $sql = <<<EOD
      INSERT INTO `{$stockTableName}` (
          sku
        , quantity
        , lead_time
      )
      SELECT
          stock.sku AS `sku`
        , set_detail.creatable_num AS `quantity`
        , :fulfillmentLatency AS `leadtime-to-ship`
      FROM `{$stockTableName}` stock
      INNER JOIN (
        SELECT 
          pci.ne_syohin_syohin_code
          , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
              WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                OR pci_sd.shipping_group_code IS NULL AND (
                  mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                ) THEN 0
              ELSE MIN(TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0)) /* 内訳SKUフリー在庫からの作成可能数 */
              END
            AS creatable_num
          -- 配送グループコードが定形外・定形郵便になるなら返却 それ以外はnull
          , CASE 
              WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei) THEN pci_sd.shipping_group_code
              WHEN (pci_sd.shipping_group_code IS NULL AND 
                  mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                ) THEN mp_sd.shipping_group_code
              ELSE NULL
              END
            AS shipping_group_code
        /* セット商品 */
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_mainproducts m ON  pci.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
        INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
        
        /* セット商品の構成品 */
        INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        WHERE m.set_flg <> 0
        GROUP BY pci.ne_syohin_syohin_code
      ) set_detail ON stock.sku = set_detail.ne_syohin_syohin_code
      WHERE 
        -- 定形外・定形は在庫数0として扱うので、現在在庫0でないものが差分　set_detail.shipping_group_codeは定形外・定形だけ返却、その他はnull
        (
          (set_detail.shipping_group_code IS NOT NULL AND stock.quantity > 0)
          OR
          (set_detail.shipping_group_code IS NULL AND stock.quantity <> set_detail.creatable_num) 
        )
        OR (
          set_detail.creatable_num > 0 AND stock.lead_time <> :fulfillmentLatency
        )
      ON DUPLICATE KEY UPDATE
          quantity  = VALUES(quantity)
        , lead_time = VALUES(lead_time)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':fulfillmentLatency', $commonUtil->getDaysForImmediateShippingDate(), \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->execute();
  }
}
