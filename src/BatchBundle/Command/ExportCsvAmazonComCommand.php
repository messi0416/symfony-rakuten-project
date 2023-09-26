<?php
/**
 * Amazon.com (アメリカAmazon) CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\Repository\TbDeleteExcludedProductsRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeleteExcludedProducts;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvAmazonComCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM = 30000; // 3万件で分割

  const EXPORT_PATH = 'AmazonCom/Export';

  protected $exportPath;

  private $skipCommonProcess = false;
  private $diffOnly = true;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-amazon-com')
      ->setDescription('CSVエクスポート Amazon.com')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理をスキップ', '0')
      ->addOption('diff-only', null, InputOption::VALUE_OPTIONAL, '差分のみ出力', '1')
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
    $logger->info('Amazon.com CSV出力処理を開始しました。');

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
      $fileUtil = $this->getFileUtil();

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
        , 'delete' => null
        , 'update' => null
      ];

      // 共通処理スキップフラグ
      $this->skipCommonProcess = boolval($input->getOption('skip-common-process'));
      // 差分のみ出力フラグ
      $this->diffOnly = boolval($input->getOption('diff-only'));

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

      $logExecTitle = sprintf('Amazon.com CSV出力処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // ====================================================
      // FBA在庫データ更新（FBA在庫情報 ダウンロード ＆ データ更新）
      // ====================================================
      // /** @var AmazonMallProcess $mallProcess */
      // $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');

      // $logger->addDbLog($logger->makeDbLog(null, 'FBA在庫更新処理', '開始'));
      // $mallProcess->updateFbaProductStock(AmazonMallProcess::SHOP_NAME_VOGUE);
      // $logger->addDbLog($logger->makeDbLog(null, 'FBA在庫更新処理', '終了'));

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0
      if (! $this->skipCommonProcess) {
        $commonUtil->exportCsvCommonProcess($logger, null);
        $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_AMAZON_COM);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }

      // CSV出力 データ作成処理 実装

      // 一時テーブル作成
      $dbMain = $this->getDb('main');

      // $temporaryKeyword = 'TEMPORARY';
      $temporaryKeyword = '';

      // 登録対象全件用
      $dbMain->query("DROP {$temporaryKeyword} TABLE IF EXISTS tmp_amazon_detail_target_all");
      $sql = <<<EOD
        CREATE {$temporaryKeyword} TABLE tmp_amazon_detail_target_all (
          daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET = utf8 COLLATE = 'utf8_bin';
EOD;
      $dbMain->query($sql);

      // 削除用
      $dbMain->query("DROP {$temporaryKeyword} TABLE IF EXISTS tmp_amazon_detail_delete;");
      $sql = <<<EOD
        CREATE {$temporaryKeyword} TABLE tmp_amazon_detail_delete (
          item_sku VARCHAR(50) NOT NULL PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET = utf8 COLLATE = 'utf8_bin';
EOD;
      $dbMain->query($sql);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));

      // --------------------------------------
      // Amazon出力対象全件抽出
      //
      // 1. Amazon.com 出品フラグON
      // 2. 商品英語情報が登録済み （タイトル・カラー名・サイズ名 チェック）
      // 3. Amazonメイン画像 登録済み
      // 4. カラー画像が 在庫あり商品について全登録済み
      // 5. ブラウズノード項目(item_type, department_name) 設定済み
      // 6. 権利侵害・アダルト審査が「ブラック」「未審査」ではない商品（「ホワイト」「グレー」）
      // 7. 下記いずれか
      //     Amazon.com 登録済みで販売終了から3年以内
      //     Amazon.com 未登録でフリー在庫がある商品
      // --------------------------------------
      $discardDay = $commonUtil->getSettingValue('AMAZON_COM_DISCARD_DAY'); // 販売終了後N日間は出品対象とする

      // 条件 2～5 チェックおよびチェックフラグ更新
      $sql = <<<EOD
        UPDATE tb_amazon_com_information i SET i.is_valid = 0
        WHERE i.is_valid <> 0
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        UPDATE
        tb_amazon_com_information i
        INNER JOIN tb_mainproducts m ON i.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_mainproducts_english e ON i.daihyo_syohin_code = e.daihyo_syohin_code
                                            AND e.title <> ''
        INNER JOIN product_images_amazon ai ON i.daihyo_syohin_code = ai.daihyo_syohin_code
                                           AND ai.code = 'amazonMain'
        INNER JOIN tb_plusnaoproductdirectory d ON m.`NEディレクトリID` = d.`NEディレクトリID`
                                               AND d.amazon_item_type <> ''
                                               AND d.amazon_department_name <> ''
        /* カラー画像の有無 */
        LEFT JOIN (
          SELECT
            DISTINCT pci.daihyo_syohin_code
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          LEFT JOIN product_images_variation v ON pci.daihyo_syohin_code = v.daihyo_syohin_code
                                              AND m.`カラー軸` = v.code
                                              AND (
                                                    m.`カラー軸` = 'row' AND pci.rowcode = v.variation_code
                                                 OR m.`カラー軸` = 'col' AND pci.colcode = v.variation_code
                                              )
          WHERE pci.`フリー在庫数` > 0
            AND v.variation_code IS NULL
        ) VI ON i.daihyo_syohin_code = VI.daihyo_syohin_code

        /* 英語カラー名、英語サイズ名 有無チェック */
        LEFT JOIN (
          SELECT
            DISTINCT pci.daihyo_syohin_code
          FROM tb_productchoiceitems pci
          WHERE pci.`フリー在庫数` > 0
            AND ( pci.colname_en = '' OR pci.rowname_en = '')
        ) EC ON i.daihyo_syohin_code = EC.daihyo_syohin_code
        SET i.is_valid = -1
        WHERE VI.daihyo_syohin_code IS NULL
          AND EC.daihyo_syohin_code IS NULL
EOD;
      $dbMain->query($sql);

      // 条件 1, 6, 7 および is_valid(2～5)
      $sql = <<<EOD
        INSERT INTO tmp_amazon_detail_target_all (
          daihyo_syohin_code
        )
        SELECT
          m.daihyo_syohin_code
        FROM tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_amazon_com_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN tb_amazon_com_product_stock stock ON m.daihyo_syohin_code = stock.sku
        WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
          AND i.registration_flg <> 0
          AND i.is_valid <> 0 /* 条件2～5 */
          AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack , :adultCheckStatusNone )
          /* 条件7 ※stock.asin = '' は削除済みだがFBAにASINが残っているデータ */
          AND (
            (
                    stock.sku IS NOT NULL
                AND stock.asin <> ''
                AND (
                      cal.endofavailability IS NULL
                   OR cal.endofavailability >= DATE_ADD(CURRENT_DATE, INTERVAL - :discardDay DAY)
                )
            )
            OR
            (
                    (stock.sku IS NULL OR stock.asin = '')
                AND cal.deliverycode_pre IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
            )
          )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
      $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
      $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
      $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
      $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
      $stmt->bindValue(':discardDay', intval($discardDay), \PDO::PARAM_INT);
      $stmt->execute();

      // --------------------------------------
      // 削除CSV データ作成
      // 現時点の差分更新用テーブルで比較する。（直前更新は別処理で行う）
      // --------------------------------------
      $this->prepareDeleteCsv();
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('prepare_delete'));

      // --------------------------------------
      // 'Prepare___
      // 更新CSVデータ作成
      // --------------------------------------
      $this->prepareData();
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('prepare'));

      // --------------------------------------
      // 'Export___
      // --------------------------------------
      $this->export();
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('export'));

      // --------------------------------------
      // 差分確認テーブル更新、更新フラグリセット
      // --------------------------------------
      $this->updateProductStockTable();
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('update_product_stock'));

      // '====================
      // Call NE更新カラムリセット
      // ここだけ、帳尻（処理が終わったらリセットされている、という出口）を合わせるため残す。
      // '====================
      $commonUtil->resetNextEngineUpdateColumn($logger);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('resetNextEngineUpdateColumn'));


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

      $logger->info('Amazon.com CSV出力処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('Amazon CSV Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazon CSV出力処理', 'Amazon CSV出力処理', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Amazon CSV出力処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * 削除用CSV 出力用意 （一時テーブルに保存）
   * 出力対象外商品は全て削除
   */
  private function prepareDeleteCsv()
  {
    $dbMain = $this->getDb('main');

    $dbMain->query('TRUNCATE tmp_amazon_detail_delete');
    $sql  =<<<EOD
      INSERT INTO tmp_amazon_detail_delete (
        item_sku
      )
      SELECT
        stock.sku
      FROM tb_amazon_com_product_stock stock
      WHERE stock.asin <> ''
EOD;
    $dbMain->query($sql);

    // 出品対象を削除対象から除外する。

    // 出力対象の子商品と一致しているレコードを除外
    $sql = <<<EOD
      DELETE d
      FROM tmp_amazon_detail_delete d
      INNER JOIN tb_productchoiceitems pci ON d.item_sku = pci.ne_syohin_syohin_code
      INNER JOIN tmp_amazon_detail_target_all t ON pci.daihyo_syohin_code = t.daihyo_syohin_code ;
EOD;
    $dbMain->query($sql);

    // 出力対象の親商品と一致しているレコードを除外
    $sql = <<<EOD
      DELETE d
      FROM tmp_amazon_detail_delete d
      INNER JOIN tmp_amazon_detail_target_all t ON d.item_sku = t.daihyo_syohin_code ;
EOD;
    $dbMain->query($sql);

    // 削除対象外商品を除外（ sku 前方一致で除外 ）
    $commonUtil = $this->getDbCommonUtil();
    $mallId = $commonUtil->getMallIdByMallCode(DbCommonUtil::MALL_CODE_AMAZON_COM);

    /** @var TbDeleteExcludedProductsRepository $repoDeleteExcluded */
    $repoDeleteExcluded = $this->getDoctrine()->getRepository(TbDeleteExcludedProducts::class);
    /** @var TbDeleteExcludedProducts[] $excludedList */
    $excludedList = $repoDeleteExcluded->findBy([ 'mall_id' => $mallId ]);
    foreach($excludedList as $exclude) {

      $this->getLogger()->info('amazon.com 削除除外: ' . $exclude->getSyohinCode());
      $sql = <<<EOD
      DELETE d
      FROM tmp_amazon_detail_delete d
      WHERE d.item_sku LIKE :excludedCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':excludedCode', $commonUtil->escapeLikeString($exclude->getSyohinCode()) . '%', \PDO::PARAM_STR);
      $stmt->execute();
    }
  }


  /**
   * 更新CSV出力準備
   */
  private function prepareData()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'Amazon.com CSV出力処理';
    $subTitle = 'Prepare___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 自社発送は国内の即納日付と同じ
    $fulfillmentLatency = $commonUtil->getDaysForImmediateShippingDate();

    // USD為替レート
    $exchangeRate = $commonUtil->getSettingValue('EXCHANGE_RATE_USD');

    // '====================
    // '商品データ
    // '====================
    $logger->info("SKU準備");

    $dbMain->query('TRUNCATE tb_amazon_com_csv_detail');

    // '親商品の登録（全件 or 差分）

    // 差分のみは親商品で絞り込む
    // 差分は SKU存否およびリードタイム、価格についてチェック
    // リードタイム・価格の差分はフリー在庫数があるもののみのチェック（全件更新にならないように）
    // 在庫数の変動チェックは、在庫更新CSVの出力処理で行うためここではチェックしない。
    if ($this->diffOnly) {

      // 差分のあるもののみ対象テーブルに残して削除
      $sql = <<<EOD
        DELETE t
        FROM tmp_amazon_detail_target_all t
        LEFT JOIN (
          SELECT
            DISTINCT pci.daihyo_syohin_code
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_amazon_com_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          LEFT JOIN tb_amazon_com_product_stock stock ON pci.ne_syohin_syohin_code = stock.sku
          WHERE
                /* 更新フラグ ON */
                i.update_flg <> 0

                /* 存在しないレコード */
             OR ( stock.sku IS NULL OR stock.asin = '' )

                /* リードタイム差分 */
             OR (
               pci.フリー在庫数 > 0
               AND stock.lead_time <> :fulfillmentLatency
             )
                /* 価格差分 */
             OR (
               pci.フリー在庫数 > 0
               AND stock.price <> TRUNCATE(i.baika_tanka / CAST(:exchangeRate AS DECIMAL(10, 0)), 2)
             )
        ) DIFF ON t.daihyo_syohin_code = DIFF.daihyo_syohin_code
        WHERE DIFF.daihyo_syohin_code IS NULL
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':exchangeRate', $exchangeRate, \PDO::PARAM_STR);
      $stmt->bindValue(':fulfillmentLatency', $fulfillmentLatency, \PDO::PARAM_INT); // リードタイム差分チェック用
      $stmt->execute();
    }

    $sql = <<<EOD
      INSERT INTO tb_amazon_com_csv_detail (
          item_sku
        , parent_sku
        , daihyo_syohin_code
        , parent_child
        , relationship_type

        /* 固定値 */
        , brand_name

        /* 商品情報 */
        , item_name
        , standard_price
        , quantity
        , variation_theme
        , bullet_point1
        , product_description

        , item_type
        , department_name

        , fulfillment_latency
        , fba_baika
        , fba_flg
      )
      SELECT
          m.daihyo_syohin_code      AS item_sku
        , ''                        AS parent_sku
        , m.daihyo_syohin_code      AS daihyo_syohin_code
        , 'parent'                  AS parent_child
        , ''                        AS relationship_type

        , 'plusnao'   AS brand_name

        , CONCAT('PlusNao ', e.title) AS item_name
        , TRUNCATE(i.baika_tanka / CAST(:exchangeRate AS DECIMAL(10, 0)), 2) AS standard_price
        , ''                        AS quantity
        , CASE
            WHEN (CONCAT(m.col_type, m.row_type) IN ('colorsize', 'sizecolor')) THEN 'SizeColor'
            WHEN (m.col_type = 'size'  OR m.row_type = 'size')  THEN 'Size'
            WHEN (m.col_type = 'color' OR m.row_type = 'color') THEN 'Color'
            ELSE 'SizeColor'
          END AS variation_theme
        , e.title              AS bullet_point1
        , CONCAT(
              COALESCE(e.description, '')
            , CASE
               WHEN COALESCE(e.about_size, '') <> '' THEN CONCAT('\n\n', e.about_size)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(e.`about_material`, '') <> '' THEN CONCAT('\n\n', e.`about_material`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(e.about_color, '') <> '' THEN CONCAT('\n\n', e.about_color)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(e.`about_brand`, '') <> '' THEN CONCAT('\n\n', e.`about_brand`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(e.`usage_note`, '') <> '' THEN CONCAT('\n\n', e.`usage_note`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(e.`supplemental_explanation`, '') <> '' THEN CONCAT('\n\n', e.`supplemental_explanation`)
               ELSE ''
            END
        ) AS product_description

        , d.amazon_item_type AS item_type
        , d.amazon_department_name  AS department_name

        , '' AS fulfillment_latency
        , TRUNCATE(i.fba_baika / CAST(:exchangeRate AS DECIMAL(10, 0)), 2) AS fba_baika
        , i.fba_flg

      FROM tb_mainproducts           AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_amazon_com_information AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_english AS e ON m.daihyo_syohin_code = e.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory AS d ON m.`NEディレクトリID` = d.`NEディレクトリID`
      INNER JOIN tmp_amazon_detail_target_all AS t ON m.daihyo_syohin_code = t.daihyo_syohin_code
      WHERE 1 /* 出品対象条件は tmp_amazon_detail_target_all 作成時に適用済み */
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':exchangeRate', $exchangeRate, \PDO::PARAM_STR);

    $stmt->execute();

    // '子商品の登録
    // すでに挿入されているdetailレコード（＝親商品レコード）にぶら下がるSKUのみ出力する。
    // ※親商品レコード挿入時点で販売対象商品の絞込を完了する意図
    $sql = <<<EOD
      INSERT INTO tb_amazon_com_csv_detail (
          item_sku
        , parent_sku
        , daihyo_syohin_code
        , parent_child
        , relationship_type

        /* 固定値 */
        , brand_name

        /* 商品情報 */
        , item_name
        , standard_price
        , quantity
        , variation_theme
        , bullet_point1
        , product_description

        , item_type
        , department_name

        , fulfillment_latency
        , fba_baika
        , fba_flg

        /* 子商品のみ */
        , size_name
        , color_name
      )
      SELECT
          pci.ne_syohin_syohin_code         AS item_sku
        , pci.daihyo_syohin_code            AS parent_sku
        , pci.daihyo_syohin_code            AS daihyo_syohin_code
        , 'child'                           AS parent_child
        , 'Variation'                       AS relationship_type

        , d.brand_name      AS brand_name

        , CONCAT(
              d.item_name
            , ' '
            , CASE
                WHEN m.col_type = 'color' THEN pci.colname_en
                WHEN m.row_type = 'color' THEN pci.rowname_en
                ELSE pci.rowname_en
              END
            , ' '
            , CASE
                WHEN m.col_type = 'size' THEN pci.colname_en
                WHEN m.row_type = 'size' THEN pci.rowname_en
                ELSE pci.colname_en
              END
          ) AS item_name
        , TRUNCATE(i.baika_tanka / CAST(:exchangeRate AS DECIMAL(10, 0)), 2) AS standard_price
        /* FBA在庫がある場合は、quantity は空欄で出力する */
        , CASE
            WHEN COALESCE(s.fba_quantity_fulfillable > 0) THEN NULL
            ELSE COALESCE(pci.フリー在庫数, 0)
          END AS quantity
        , d.variation_theme                 AS variation_theme
        , d.bullet_point1                   AS bullet_point1
        , d.product_description             AS product_description

        , d.item_type AS item_type
        , d.department_name AS department_name

        /* FBA在庫がある場合は、fulfillment_latency は空欄で出力する */
        , CASE
            WHEN COALESCE(s.fba_quantity_fulfillable > 0) THEN NULL
            ELSE :fulfillmentLatency
          END AS fulfillment_latency

        , TRUNCATE(i.fba_baika / CAST(:exchangeRate AS DECIMAL(10, 0)), 2) AS fba_baika
        , i.fba_flg

        /* 子商品のみ */
        , CASE
            WHEN m.col_type = 'size' THEN pci.colname_en
            WHEN m.row_type = 'size' THEN pci.rowname_en
            ELSE pci.colname_en
          END AS size_name
        , CASE
            WHEN m.col_type = 'color' THEN pci.colname_en
            WHEN m.row_type = 'color' THEN pci.rowname_en
            ELSE pci.rowname_en
          END AS color_name

      FROM tb_productchoiceitems            AS pci
      INNER JOIN tb_amazon_com_csv_detail   AS d ON pci.daihyo_syohin_code = d.daihyo_syohin_code
      INNER JOIN tb_mainproducts            AS m   ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal        AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_amazon_com_information  AS i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_amazon_com_product_stock AS s ON pci.ne_syohin_syohin_code = s.sku
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':exchangeRate', $exchangeRate, \PDO::PARAM_STR);
    $stmt->bindValue(':fulfillmentLatency', $fulfillmentLatency, \PDO::PARAM_INT);

    $stmt->execute();

    // '画像URL 更新
    $logger->info("画像URL 更新");
    $this->setPictureUrl();

//    // '商品タイプ、キーワード
//    $sql = <<<EOD
//      UPDATE tb_amazon_com_csv_detail           AS d
//      INNER JOIN tb_mainproducts            AS m   ON d.daihyo_syohin_code = m.daihyo_syohin_code
//      LEFT  JOIN tb_plusnaoproductdirectory AS DIR ON m.NEディレクトリID = DIR.NEディレクトリID
//      SET d.generic_keywords1         = ''
//EOD;
//    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }


  /**
   * CSV出力処理
   * @throws \Doctrine\DBAL\DBALException
   */
  private function export()
  {
    $logger = $this->getLogger();
    $db = $this->getDb('main');

    $logTitle = 'Amazon.com CSV出力処理';
    $subTitle = 'Export___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    /** @var AmazonMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');

    // ====================
    // Amazon_delete.txt
    // テンプレートは ConsumerElectronics で項目を削減
    // ※ConsumerElectronics は、 Amazonのテンプレートバージョン警告のリンクからダウンロードできるもの
    // ====================
    $logger->info('Amazon_delete.txt 作成中');

    $headerDescription = "TemplateType=clothing\tVersion=2016.0721\tThe top 3 rows are for Amazon.com use only. Do not modify or delete the top 3 rows.\t\tOffer-These attributes are required to make your item buyable for customers on the site\t\t";
    $headers = [
        'item_sku'                 => 'Seller SKU'
      , 'external_product_id'      => 'Product ID'
      , 'external_product_id_type' => 'Product ID Type'
      , 'update_delete'            => 'Update Delete'
      , 'standard_price'           => 'Standard Price'
      , 'quantity'                 => 'Quantity'
      , 'fulfillment_latency'      => 'Fulfillment Latency'
    ];
    $sql = <<<EOD
      SELECT
          d.item_sku AS `item_sku`
        , '' AS `external_product_id`
        , '' AS `external_product_id_type`
        , 'Delete' AS `update_delete`
        , '' AS `standard_price`
        , '' AS `quantity`
        , '' AS `fulfillment_latency`
      FROM tmp_amazon_detail_delete d
      ORDER BY d.item_sku DESC /* 子商品を先に出力するための指定 */
EOD;
    $stmt = $db->query($sql);

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
          $filePath = sprintf('%s/Amazon_delete_%d.txt', $this->exportPath, $fileNum);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $mallProcess->createCsvHeaderLines($headerDescription, $headers, 'com'));
          $files[] = $filePath;
        }

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), $noEncloseFields, "\t") . "\r\n";
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

      $this->results['delete'] = [
          'count' => $totalCount
        , 'file_num' => $fileNum
        , 'files' => $files
      ];
      $logger->info("Amazon CSV出力 Amazon_delete_x.txt: $totalCount 件 / $fileNum ファイル");

    } else {
      $logger->info("Amazon CSV出力 Amazon_delete_x.txt: 件数が0のためファイルは作成しませんでした。");
    }


    // '====================
    // 'Amazon.txt
    // テンプレートは 『在庫ファイルテンプレート 服＆ファッション小物／シューズ＆バッグ』
    // '====================
    $logger->info('Amazon.txt 作成中');

    $headerDescription = "TemplateType=clothing\tVersion=2016.0721\tThe top 3 rows are for Amazon.com use only. Do not modify or delete the top 3 rows.\t\t\t\t\t\t\tOffer-These attributes are required to make your item buyable for customers on the site\t\t\tDiscovery-These attributes have an effect on how customers can find your product on the site using browse or search\t\t\t\t\t\tImages-These attributes provide links to images for a product\t\t\t\t\t\t\t\t\t\tFulfillment-Use these columns to provide fulfillment-related information for either Amazon-fulfilled (FBA) or seller-fulfilled orders.\tVariation-Populate these attributes if your product is available in different variations (for example color or wattage)\t\t\t\tUngrouped - These attributes create rich product listings for your buyers.\t\t";
    $headers = [
        'item_sku'                  => 'Seller SKU'
      , 'item_name'                 => 'Product Name'
      , 'external_product_id'       => 'Product ID'
      , 'external_product_id_type'  => 'Product ID Type'
      , 'brand_name'                => 'Brand Name'
      , 'item_type'                 => 'Item Type Keyword'
      , 'model'                     => 'Style Number'
      , 'product_description'       => 'Product Description'
      , 'update_delete'             => 'Update Delete'
      , 'quantity'                  => 'Quantity'
      , 'fulfillment_latency'       => 'Fulfillment Latency'
      , 'standard_price' => 'Standard Price'
      , 'bullet_point1' => 'Key Product Features1'
      , 'bullet_point2' => 'Key Product Features2'
      , 'bullet_point3' => 'Key Product Features3'
      , 'bullet_point4' => 'Key Product Features4'
      , 'bullet_point5' => 'Key Product Features5'
      , 'generic_keywords' => 'Search Terms'
      , 'main_image_url' => 'Main Image URL'
      , 'swatch_image_url' => 'Swatch Image URL'
      , 'other_image_url1' => 'Other Image URL1'
      , 'other_image_url2' => 'Other Image URL2'
      , 'other_image_url3' => 'Other Image URL3'
      , 'other_image_url4' => 'Other Image URL4'
      , 'other_image_url5' => 'Other Image URL5'
      , 'other_image_url6' => 'Other Image URL6'
      , 'other_image_url7' => 'Other Image URL7'
      , 'other_image_url8' => 'Other Image URL8'
      , 'fulfillment_center_id' => 'Fulfillment Center ID'
      , 'parent_child' => 'Parentage'
      , 'parent_sku' => 'Parent SKU'
      , 'relationship_type' => 'Relationship Type'
      , 'variation_theme' => 'Variation Theme'
      , 'size_name' => 'Size'
      , 'color_name' => 'Color'
      , 'department_name' => 'Department'
    ];

    $sql = <<<EOD
      SELECT
          d.item_sku AS `item_sku`
        , LEFT(d.item_name, 80) AS `item_name`
        , CASE
            WHEN s.fba_asin <> '' AND s.asin <> s.fba_asin THEN s.fba_asin
            WHEN s.asin <> '' THEN s.asin
            ELSE ''
          END AS `external_product_id`
        , CASE
            WHEN s.fba_asin <> '' AND s.asin <> s.fba_asin THEN 'ASIN'
            WHEN s.asin <> '' THEN 'ASIN'
            ELSE ''
          END AS `external_product_id_type`
        , d.brand_name AS `brand_name`
        , d.item_type AS `item_type`
        , d.item_sku AS model
        , d.product_description AS `product_description`
        , 'Update' AS `update_delete`
        , d.quantity AS `quantity`
        , d.fulfillment_latency AS `fulfillment_latency`
        /* FBAフラグで区別 */
        , CASE
            WHEN d.fba_flg <> 0 AND d.fba_baika > 0 THEN d.fba_baika
            ELSE d.standard_price
          END AS `standard_price`
        , d.bullet_point1 AS `bullet_point1`
        , '' AS `bullet_point2`
        , '' AS `bullet_point3`
        , '' AS `bullet_point4`
        , '' AS `bullet_point5`
        , d.generic_keywords AS `generic_keywords`
        , d.main_image_url AS `main_image_url`
        , d.swatch_image_url AS `swatch_image_url`
        , d.other_image_url1 AS `other_image_url1`
        , d.other_image_url2 AS `other_image_url2`
        , d.other_image_url3 AS `other_image_url3`
        , d.other_image_url4 AS `other_image_url4`
        , d.other_image_url5 AS `other_image_url5`
        , d.other_image_url6 AS `other_image_url6`
        , d.other_image_url7 AS `other_image_url7`
        , d.other_image_url8 AS `other_image_url8`
        /* FBA在庫の有無で区別 */
        , CASE
            WHEN d.parent_child = 'parent' THEN ''
            WHEN COALESCE(s.fba_quantity_fulfillable > 0) THEN 'AMAZON_NA'
            ELSE 'DEFAULT'
          END AS `fulfillment_center_id`
        , d.parent_child AS `parent_child`
        , d.parent_sku AS `parent_sku`
        , d.relationship_type AS `relationship_type`
        , d.variation_theme AS `variation_theme`
        , d.size_name AS `size_name`
        , d.color_name AS `color_name`
        , d.department_name AS `department_name`
      FROM tb_amazon_com_csv_detail d
      LEFT JOIN tb_amazon_com_product_stock AS s ON d.item_sku = s.sku
      ORDER BY d.item_sku, d.parent_child DESC
EOD;
    $stmt = $db->query($sql);

    // 出力
    if ($stmt->rowCount()) {

      // ファイル番号
      $fileNum = 1;
      $files = [];
      $totalCount = 0;
      $parentCount = 0;
      $lineCount = 0;
      $fp = null;
      $noEncloseFields = [];

      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp)) {
          $filePath = sprintf('%s/Amazon_%d.txt', $this->exportPath, $fileNum);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $mallProcess->createCsvHeaderLines($headerDescription, $headers, 'com'));

          $files[] = $filePath;
        }

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), $noEncloseFields, "\t") . "\r\n";
        fputs($fp, $line);

        $totalCount++;
        $lineCount++;
        if ($row['parent_child'] == 'parent') {
          $parentCount++;
        }

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
        , 'parent_count' => $parentCount
        , 'file_num' => $fileNum
        , 'files' => $files
      ];

      $logger->info("Amazon CSV出力 Amazon_x.txt: $totalCount 件 / $fileNum ファイル");

    } else {
      $logger->info("Amazon CSV出力 Amazon_x.txt: 件数が0のためファイルは作成しませんでした。");
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }


  /**
   * 画像URL 更新
   */
  private function setPictureUrl()
  {
    $dbMain = $this->getDb('main');

    // オリジナル画像の実装は現在利用されていない。不要の確認が取れれば削除。

    // オリジナル画像数が0の場合はBatchSV02 URL(forest.plusnao.co.jp)
    $imageUrl = sprintf('https://%s/images', $this->getContainer()->getParameter('host_plusnao'));

    // (memo) INSERT 時にやってしまってはだめ？
    // また、amazonメイン画像が存在することを前提とする。（amazonメイン画像がなければ空）
    $sql = <<<EOD
      UPDATE tb_amazon_com_csv_detail     AS d
      INNER JOIN tb_amazon_com_information  AS i ON d.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts      AS m ON d.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN product_images_amazon pi_main ON m.daihyo_syohin_code = pi_main.daihyo_syohin_code AND pi_main.code = 'amazonMain'
      SET
          d.main_image_url   = pi_main.address /* Amazonメイン画像 */
        /* 以下、通常メイン画像P1 ～ P8 */
        , d.other_image_url1 = CASE
                                 WHEN COALESCE(m.picfolderP1, '') <> '' AND COALESCE(m.picnameP1, '') <> ''
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP1, '/', m.picnameP1)
                                 ELSE ''
                               END
        , d.other_image_url2 = CASE
                                 WHEN COALESCE(m.picfolderP2, '') <> '' AND COALESCE(m.picnameP2, '') <> ''
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP2, '/', m.picnameP2)
                                 ELSE ''
                               END
        , d.other_image_url3 = CASE
                                 WHEN COALESCE(m.picfolderP3, '') <> '' AND COALESCE(m.picnameP3, '') <> '' 
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP3, '/', m.picnameP3)
                                 ELSE ''
                               END
        , d.other_image_url4 = CASE
                                 WHEN COALESCE(m.picfolderP4, '') <> '' AND COALESCE(m.picnameP4, '') <> '' 
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP4, '/', m.picnameP4)
                                 ELSE ''
                               END
        , d.other_image_url5 = CASE
                                 WHEN COALESCE(m.picfolderP5, '') <> '' AND COALESCE(m.picnameP5, '') <> '' 
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP5, '/', m.picnameP5)
                                 ELSE ''
                               END
        , d.other_image_url6 = CASE
                                 WHEN COALESCE(m.picfolderP6, '') <> '' AND COALESCE(m.picnameP6, '') <> '' 
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP6, '/', m.picnameP6)
                                 ELSE ''
                               END
        , d.other_image_url7 = CASE
                                 WHEN COALESCE(m.picfolderP7, '') <> '' AND COALESCE(m.picnameP7, '') <> '' 
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP7, '/', m.picnameP7)
                                 ELSE ''
                               END
        , d.other_image_url8 = CASE
                                 WHEN COALESCE(m.picfolderP8, '') <> '' AND COALESCE(m.picnameP8, '') <> '' 
                                         THEN CONCAT(:imageUrl, '/', m.picfolderP8, '/', m.picnameP8)
                                 ELSE ''
                               END
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':imageUrl', $imageUrl, \PDO::PARAM_STR);
    $stmt->execute();

    // 商品バリエーション画像（メイン画像 および swatch（色見本）画像とも更新）
    $sql = <<<EOD
      UPDATE
      tb_amazon_com_csv_detail d
      INNER JOIN tb_productchoiceitems pci ON d.item_sku = pci.ne_syohin_syohin_code
                                          AND d.parent_child = 'child'
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN product_images_variation iv ON pci.daihyo_syohin_code = iv.daihyo_syohin_code
                                            AND m.`カラー軸` = iv.code
                                            AND (
                                                  (m.`カラー軸` = 'col' AND pci.colcode = iv.variation_code)
                                               OR (m.`カラー軸` = 'row' AND pci.rowcode = iv.variation_code)
                                            )
      SET d.main_image_url = iv.address
        , d.swatch_image_url = iv.address
EOD;
    $dbMain->query($sql);
  }

  /**
   * 差分確認テーブル更新 （価格、数量、リードタイム）
   */
  private function updateProductStockTable()
  {
    $dbMain = $this->getDb('main');

    // 削除SKU レコード削除
    $sql = <<<EOD
      DELETE s
      FROM tb_amazon_com_product_stock s
      INNER JOIN tmp_amazon_detail_delete d ON s.sku = d.item_sku
EOD;
    $dbMain->query($sql);

    // 更新SKU レコード追加・更新
    $sql = <<<EOD
      INSERT INTO tb_amazon_com_product_stock (
          sku
        , price
        , quantity
        , lead_time
      )
      SELECT
          d.item_sku
        , COALESCE(d.standard_price, 0)
        , COALESCE(d.quantity, 0)
        , COALESCE(d.fulfillment_latency, 0)
      FROM tb_amazon_com_csv_detail d
      ON DUPLICATE KEY UPDATE
          price     = VALUES(price)
        , quantity  = VALUES(quantity)
        , lead_time = VALUES(lead_time)
EOD;
    $dbMain->query($sql);

    // 更新フラグリセット
    $sql = <<<EOD
      UPDATE tb_amazon_com_information i
      SET i.update_flg = 0
      WHERE i.update_flg <> 0
EOD;
    $dbMain->query($sql);

    return;
  }


}
