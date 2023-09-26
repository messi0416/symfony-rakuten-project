<?php
/**
 * Amazon CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\Repository\TbDeleteExcludedProductsRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeleteExcludedProducts;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvAmazonCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM = 30000; // 3万件で分割

  const EXPORT_PATH = 'Amazon/Export';

  protected $exportPath;

  private $skipCommonProcess = false;
  private $diffOnly = true;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-amazon')
      ->setDescription('CSVエクスポート Amazon')
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
    $logger->info('AmazonCSV出力処理を開始しました。');

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

      $logExecTitle = sprintf('AmazonCSV出力処理');
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
        $rakutenCsvOutputDir = $fileUtil->getWebCsvDir() . '/RakutenNokiKanri';
        $commonUtil->exportCsvCommonProcess($logger, $rakutenCsvOutputDir);
        $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_AMAZON);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
      }

      // CSV出力 データ作成処理 実装

      // 一時テーブル作成
      $dbMain = $this->getDb('main');

      // 登録対象全件用
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_amazon_detail_target_all");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_amazon_detail_target_all (
          daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET = utf8 COLLATE = 'utf8_bin';
EOD;
      $dbMain->query($sql);

      // 削除用
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_amazon_detail_delete;");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_amazon_detail_delete (
          item_sku VARCHAR(50) NOT NULL PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET = utf8 COLLATE = 'utf8_bin';
EOD;
      $dbMain->query($sql);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));

      // --------------------------------------
      // Amazon出力対象全件抽出
      // 1 出品フラグがon
      // 2 Amazonメイン画像が登録されている
      // 3 権利侵害・アダルト審査が「ブラック」「未審査」ではない
      // および、
      // 4 Amazonへ登録済みの全商品で1、2、3に該当しない完売3年以内の商品
      //   または
      // 5 Amazon未登録でフリー在庫のある商品
      // --------------------------------------
      $discardDay = $commonUtil->getSettingValue('AMAZON_DISCARD_DAY'); // 販売終了後N日間は出品対象とする

      // 条件 1～4
      $sql = <<<EOD
        INSERT INTO tmp_amazon_detail_target_all (
          daihyo_syohin_code
        )
        SELECT
          m.daihyo_syohin_code
        FROM tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_amazoninfomation i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN product_images_amazon pi ON m.daihyo_syohin_code = pi.daihyo_syohin_code AND pi.code = 'amazonMain'
        LEFT JOIN tb_amazon_product_stock stock ON m.daihyo_syohin_code = stock.sku
        WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
          AND i.registration_flg <> 0
          AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack , :adultCheckStatusNone )
          AND (
            /* 4 Amazonへ登録済みの全商品で1、2、3に該当しない完売3年以内の商品 */
            (
                    stock.sku IS NOT NULL
                AND stock.asin <> ''
                AND (
                      cal.endofavailability IS NULL
                   OR cal.endofavailability >= DATE_ADD(CURRENT_DATE, INTERVAL - :discardDay DAY)
                )
            )
            OR
            /* 5 Amazon未登録でフリー在庫のある商品 (※stock.asin = '' は削除済みだがFBAにASINが残っているデータ) */
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

      // Amazon販売フラグ更新
      // このフラグが立っていない商品はFBAマルチチャネル販売のためだけに登録される
      $sql = <<<EOD
        UPDATE tb_amazoninfomation i SET i.sell_in_amazon = 0
        WHERE i.sell_in_amazon <> 0
EOD;
      $dbMain->exec($sql);
      $sql = <<<EOD
        UPDATE tb_amazoninfomation i
        INNER JOIN tmp_amazon_detail_target_all t ON i.daihyo_syohin_code = t.daihyo_syohin_code
        SET i.sell_in_amazon = -1  
EOD;
      $dbMain->exec($sql);

      if (intval($commonUtil->getSettingValue('FBA_MULTI_ENABLED')) != 0) {
        // FBAマルチチャネル販売データ追加
        $sql = <<<EOD
        INSERT IGNORE INTO tmp_amazon_detail_target_all (
          daihyo_syohin_code
        )
        SELECT
          m.daihyo_syohin_code
        FROM tb_mainproducts m
        WHERE m.fba_multi_flag <> 0
EOD;
        $dbMain->exec($sql);
      }

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

      $logger->info('AmazonCSV出力処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('Amazon CSV Export エラー:' . $e->getMessage() . $e->getTraceAsString());
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
      FROM tb_amazon_product_stock stock
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
    $mallId = $commonUtil->getMallIdByMallCode(DbCommonUtil::MALL_CODE_AMAZON);

    /** @var TbDeleteExcludedProductsRepository $repoDeleteExcluded */
    $repoDeleteExcluded = $this->getDoctrine()->getRepository(TbDeleteExcludedProducts::class);
    /** @var TbDeleteExcludedProducts[] $excludedList */
    $excludedList = $repoDeleteExcluded->findBy([ 'mall_id' => $mallId ]);
    foreach($excludedList as $exclude) {

      $this->getLogger()->info('amazon 削除除外: ' . $exclude->getSyohinCode());
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

    $logTitle = 'AmazonCSV出力処理';
    $subTitle = 'Prepare___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $narrowSpaceChars = explode(',', $commonUtil->getSettingValue('AMAZON_NARROW_SPACE_CHAR')); // 空白へ変換する文字列

    $fulfillmentLatency = $commonUtil->getDaysForImmediateShippingDate();

    // '====================
    // 'タイトル補正
    // 'AMAZON_NARROW_SPACE_CHAR にカンマ区切りで補正対象文字を設定する
    // '====================
    $logger->info("タイトル補正");

    $replaceQuery = '';
    foreach ($narrowSpaceChars as $char) {
      if (!$replaceQuery) {
        $replaceQuery = 'i.amazon_title';
      }
      $replaceQuery = sprintf("REPLACE(%s, %s, ' ')", $replaceQuery, $dbMain->quote($char, \PDO::PARAM_STR));
    }

    if ($replaceQuery) {
      $sql = <<<EOD
        UPDATE tb_amazoninfomation AS i
        SET i.amazon_title = ${replaceQuery}
        WHERE i.amazon_title IS NOT NULL /* 仮登録のものはNULLで入っている */
EOD;
      $dbMain->query($sql);
    }
    $logger->info('タイトル補正 query: ' . $replaceQuery);

    // '====================
    // '商品データ
    // '====================
    $logger->info("SKU準備");

    $dbMain->query('TRUNCATE tb_amazoninfo_detail');

    // '親商品の登録（全件 or 差分）

    // 差分のみは親商品で絞り込む
    // 差分は SKU存否およびリードタイム、価格についてチェック
    // リードタイム・価格の差分はフリー在庫数があるもののみのチェック（全件更新にならないように）
    // 在庫数の変動チェックは、在庫更新CSVの出力処理で行うためここではチェックしない。
    if ($this->diffOnly) {

      // セット商品、または通常商品で差分のあるもののみ対象テーブルに残して削除
      $sql = <<<EOD
        DELETE t
        FROM tmp_amazon_detail_target_all t
        LEFT JOIN (
          SELECT
            DISTINCT pci.daihyo_syohin_code
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_amazoninfomation i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          LEFT JOIN tb_amazon_product_stock stock ON pci.ne_syohin_syohin_code = stock.sku

          WHERE
             /* セット商品である */
             m.set_flg = -1 
             
                /* 更新フラグ ON */
             OR i.update_flg <> 0

                /* 存在しないレコード */
             OR ( stock.sku IS NULL OR stock.asin = '' )

                /* リードタイム差分 */
             OR (
               -- ここでは定形外・定形郵便でも、実際には在庫があるならば差分とする（どこかの経路で在庫が入ってしまった時のため）
               pci.フリー在庫数 > 0
               AND stock.lead_time <> :fulfillmentLatency
             )
                /* 価格差分 */
             OR (
               -- ここでは定形外・定形郵便でも、実際には在庫があるならば差分とする（どこかの経路で在庫が入ってしまった時のため）
               pci.フリー在庫数 > 0
               AND stock.price <> TRUNCATE(i.baika_tanka * CAST(:taxRate AS DECIMAL(10, 2)), -1)
              )
        ) DIFF ON t.daihyo_syohin_code = DIFF.daihyo_syohin_code
        WHERE DIFF.daihyo_syohin_code IS NULL
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
      $stmt->bindValue(':fulfillmentLatency', $fulfillmentLatency, \PDO::PARAM_INT); // リードタイム差分チェック用
      $stmt->execute();
      
      // 通常商品、またはセット商品で差分のあるもののみ対象テーブルに残して削除
      $sql = <<<EOD
        DELETE t
        FROM tmp_amazon_detail_target_all t
        LEFT JOIN (
          SELECT
              pci.ne_syohin_syohin_code AS set_sku
            , pci.daihyo_syohin_code
          FROM tb_productchoiceitems pci 
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_amazoninfomation i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
          LEFT JOIN tb_amazon_product_stock stock ON pci.ne_syohin_syohin_code = stock.sku
          
          /* セット商品の構成品 */
          LEFT JOIN (
            SELECT 
              d.set_ne_syohin_syohin_code
            , MIN(TRUNCATE(CASE WHEN i_detail.sell_in_amazon = 0 THEN 0 ELSE COALESCE(pci_detail.フリー在庫数, 0) END / d.num, 0)) AS creatable_num
            FROM tb_set_product_detail d 
            INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
            INNER JOIN tb_amazoninfomation i_detail ON pci_detail.daihyo_syohin_code = i_detail.daihyo_syohin_code
            GROUP BY d.set_ne_syohin_syohin_code
            ORDER BY d.set_ne_syohin_syohin_code
          ) set_detail ON pci.ne_syohin_syohin_code = set_detail.set_ne_syohin_syohin_code
          WHERE 
           /* セット商品でない */
            m.set_flg <> -1

              /* 更新フラグ ON */
           OR i.update_flg <> 0

              /* 存在しないレコード */
           OR ( stock.sku IS NULL OR stock.asin = '' )

              /* リードタイム差分 */
           OR (
             -- ここでは定形外・定形郵便でも、実際には在庫があるならば差分とする（どこかの経路で在庫が入ってしまった時のため）
             set_detail.creatable_num > 0 
             AND stock.lead_time <> :fulfillmentLatency
           )
              /* 価格差分 */
           OR (
             -- ここでは定形外・定形郵便でも、実際には在庫があるならば差分とする（どこかの経路で在庫が入ってしまった時のため）
             set_detail.creatable_num > 0
             AND stock.price <> TRUNCATE(i.baika_tanka * CAST(:taxRate AS DECIMAL(10, 2)), -1)
            )
          ) DIFF ON t.daihyo_syohin_code = DIFF.daihyo_syohin_code
        WHERE DIFF.daihyo_syohin_code IS NULL
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
      $stmt->bindValue(':fulfillmentLatency', $fulfillmentLatency, \PDO::PARAM_INT); // リードタイム差分チェック用
      $stmt->execute();
    }

    $sql = <<<EOD
      INSERT INTO tb_amazoninfo_detail (
          item_sku
        , parent_sku
        , daihyo_syohin_code
        , parent_child
        , relationship_type

        /* 固定値 */
        , brand_name
        , condition_type
        , currency
        , department_name

        /* 商品情報 */
        , part_number
        , item_name
        , standard_price
        , quantity
        , variation_theme
        , bullet_point1
        , product_description

        , package_length
        , package_width
        , package_height
        , package_weight

        , fulfillment_latency
        , fba_baika
        , fba_flg
        , snl_baika
        , snl_flg
        , merchant_shipping_group_name
      )
      SELECT
          m.daihyo_syohin_code      AS item_sku
        , NULL                      AS parent_sku
        , m.daihyo_syohin_code      AS daihyo_syohin_code
        , 'parent'                  AS parent_child
        , NULL                      AS relationship_type

        , 'Plus Nao'  AS brand_name
        , 'New'       AS condition_type
        , 'JPY'       AS currency
        , 'レディーズ' AS department_name

        , m.daihyo_syohin_code        AS part_number
        , CONCAT('Plus Nao(プラスナオ) ', i.amazon_title) AS item_name
        , TRUNCATE(i.baika_tanka * CAST(:taxRate AS DECIMAL(10, 2)), -1) AS standard_price
        , NULL                        AS quantity
        , CASE
            WHEN (CONCAT(m.col_type, m.row_type) IN ('colorsize', 'sizecolor')) THEN 'SizeColor'
            WHEN (m.col_type = 'size'  OR m.row_type = 'size')  THEN 'Size'
            WHEN (m.col_type = 'color' OR m.row_type = 'color') THEN 'Color'
            ELSE 'SizeColor'
          END AS variation_theme
        , i.amazon_title              AS bullet_point1
        , CONCAT(
              COALESCE(m.商品コメントPC, '')
            , CASE
               WHEN COALESCE(m.`サイズについて`, '') <> '' THEN CONCAT('(サイズについて)', m.`サイズについて`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(m.`素材について`, '') <> '' THEN CONCAT('(素材について)', m.`素材について`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(m.`カラーについて`, '') <> '' THEN CONCAT('(カラーについて)', m.`カラーについて`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(m.`ブランドについて`, '') <> '' THEN CONCAT('(ブランドについて)', m.`ブランドについて`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(m.`使用上の注意`, '') <> '' THEN CONCAT('(使用上の注意)', m.`使用上の注意`)
               ELSE ''
            END
            , CASE
               WHEN COALESCE(m.`補足説明PC`, '') <> '' THEN CONCAT('(補足説明)', m.`補足説明PC`)
               ELSE ''
            END
        ) AS product_description

        /* cm単位。長い順にそろえるためVIEWを経由 */
        , size.side1  AS package_length
        , size.side2  AS package_width
        , size.side3  AS package_height
        , size.weight AS package_weight

        , NULL AS fulfillment_latency
        , TRUNCATE(i.fba_baika * CAST(:taxRate AS DECIMAL(10, 2)), -1) AS fba_baika
        , i.fba_flg
        , TRUNCATE(i.snl_baika * CAST(:taxRate AS DECIMAL(10, 2)), -1) AS snl_baika
        , i.snl_flg
        , COALESCE(sg.code, '') AS merchant_shipping_group_name

      FROM tb_mainproducts           AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_amazoninfomation AS i   ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tmp_amazon_detail_target_all AS t ON m.daihyo_syohin_code = t.daihyo_syohin_code
      LEFT JOIN v_product_size size ON m.daihyo_syohin_code = size.daihyo_syohin_code
      LEFT JOIN tb_amazon_shipping_group AS sg ON i.shipping_group_id = sg.id
      WHERE 1 /* 出品対象条件は tmp_amazon_detail_target_all 作成時に適用済み */
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);

    $stmt->execute();

    // 親商品のタイトルは「全角65文字」まで。（楽天ならともかくAmazonまでこんな面倒な指定しなくても... 文字数orバイト数でいいじゃん...）
    // 65文字を超えていなければ、全て全角でも大丈夫。超過分のみ、チェックして更新
    $sql = <<<EOD
      SELECT
          item_sku
        , item_name
      FROM tb_amazoninfo_detail
      WHERE CHAR_LENGTH(item_name) > 65
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // 一括insertによるUPDATE
    $insertBuilder = new MultiInsertUtil("tb_amazoninfo_detail", [
      'fields' => [
          'item_sku' => \PDO::PARAM_STR
        , 'item_name' => \PDO::PARAM_STR
      ]
      , 'postfix' => "ON DUPLICATE KEY UPDATE "
                   . "   item_name = VALUES(item_name) "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function ($item) use ($logger) {

      if (mb_strwidth($item['item_name'], 'UTF-8') > 130) {
        $item['item_name'] = mb_strimwidth($item['item_name'], 0, 130, null, 'UTF-8');
        return $item;

      } else {
        return null; // スキップ
      }

    }, 'foreach');


    // '子商品の登録
    // すでに挿入されているdetailレコード（＝親商品レコード）にぶら下がるSKUのみ出力する。
    // ※親商品レコード挿入時点で販売対象商品の絞込を完了する意図
    $sql = <<<EOD
      INSERT INTO tb_amazoninfo_detail (
          item_sku
        , parent_sku
        , daihyo_syohin_code
        , parent_child
        , relationship_type

        /* 固定値 */
        , brand_name
        , condition_type
        , currency
        , department_name

        /* 商品情報 */
        , part_number
        , item_name
        , standard_price
        , quantity
        , variation_theme
        , bullet_point1
        , product_description

        , package_length
        , package_width
        , package_height
        , package_weight

        , fulfillment_latency
        , fba_baika
        , fba_flg
        , snl_baika
        , snl_flg
        , merchant_shipping_group_name

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
        , d.condition_type  AS condition_type
        , d.currency        AS currency
        , d.department_name AS department_name

        , pci.ne_syohin_syohin_code         AS part_number
        , CONCAT(d.item_name, ' ', pci.colname, ' ', pci.rowname) AS item_name
        , TRUNCATE(i.baika_tanka * CAST(:taxRate AS DECIMAL(10, 2)), -1) AS standard_price
        /* S&L出荷商品 あるいは FBA在庫がある場合は、quantity は空欄で出力する */
        /* => FBAはあとで切り替えるように仕様変更されたため、常に出品者出荷の在庫数 */
        /* => FBAマルチチャネル用の登録商品は 0 で固定 */
        , CASE
            WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                OR pci_sd.shipping_group_code IS NULL AND (
                  mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                ) THEN 0
            WHEN i.sell_in_amazon = 0 THEN 0
            ELSE COALESCE(pci.フリー在庫数, 0) -- セット商品が、親商品のpciに設定されているフリー在庫数で出力されているが、親商品は常に0なので #189441 的には支障がないので現時点では無視　この仕様で良いのかは後日確認
          END AS quantity
        , d.variation_theme                 AS variation_theme
        , d.bullet_point1                   AS bullet_point1
        , d.product_description             AS product_description

        , COALESCE(size.side1, d.package_length) AS package_length
        , COALESCE(size.side2, d.package_width) AS package_width
        , COALESCE(size.side3, d.package_height) AS package_height
        , COALESCE(size.weight, d.package_weight) AS package_weight

        /* S&L出荷商品 あるいは FBA在庫がある場合は、fulfillment_latency は空欄で出力する */
        /* => FBAはあとで切り替えるように仕様変更されたため、常に出品者出荷の数値 */
        /* => FBAマルチチャネル用の登録商品は空欄で固定 */
        , CASE
            WHEN i.sell_in_amazon = 0 THEN NULL
            ELSE :fulfillmentLatency
          END AS fulfillment_latency
        , TRUNCATE(i.fba_baika * CAST(:taxRate AS DECIMAL(10, 2)), -1) AS fba_baika
        , i.fba_flg
        , TRUNCATE(i.snl_baika * CAST(:taxRate AS DECIMAL(10, 2)), -1) AS snl_baika
        , i.snl_flg
        , COALESCE(sg.code, '') AS merchant_shipping_group_name 

        /* 子商品のみ */
        , CASE
            WHEN m.col_type = 'size' THEN pci.colname
            WHEN m.row_type = 'size' THEN pci.rowname
            ELSE pci.colname
          END AS size_name
        , CASE
            WHEN m.col_type = 'color' THEN pci.colname
            WHEN m.row_type = 'color' THEN pci.rowname
            ELSE pci.rowname
          END AS color_name

      FROM tb_productchoiceitems        AS pci
      INNER JOIN tb_amazoninfo_detail   AS d ON pci.daihyo_syohin_code = d.daihyo_syohin_code
      INNER JOIN tb_mainproducts        AS m   ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal    AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_amazoninfomation    AS i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_amazon_product_stock AS s ON pci.ne_syohin_syohin_code = s.sku
      LEFT JOIN v_product_sku_size      AS size ON pci.ne_syohin_syohin_code = size.ne_syohin_syohin_code
      LEFT JOIN tb_amazon_shipping_group AS sg ON i.shipping_group_id = sg.id
      LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
      INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
    $stmt->bindValue(':fulfillmentLatency', $fulfillmentLatency, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);

    $stmt->execute();

    // '画像URL 更新
    $logger->info("画像URL 更新");
    $this->setPictureUrl();

    // '商品タイプ、キーワード
    $sql = <<<EOD
      UPDATE tb_amazoninfo_detail           AS d
      INNER JOIN tb_mainproducts            AS m   ON d.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_amazoninfomation        AS i   ON d.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT  JOIN tb_plusnaoproductdirectory AS DIR ON m.NEディレクトリID = DIR.NEディレクトリID
      SET d.product_subtype           = IF(AMAZON商品タイプ <> '', AMAZON商品タイプ, 'Shirt')
        , d.recommended_browse_nodes1 = COALESCE(AMAZON推奨ブラウズノード1, '')
        , d.generic_keywords1         = LEFT(
                                          CONCAT(
                                              IF(DIR.`フィールド1` <> '', CONCAT(' ', DIR.`フィールド1`) , '')
                                            , IF(DIR.`フィールド2` <> '', CONCAT(' ', DIR.`フィールド2`) , '')
                                            , IF(DIR.`フィールド3` <> '', CONCAT(' ', DIR.`フィールド3`) , '')
                                            , IF(DIR.`フィールド4` <> '', CONCAT(' ', DIR.`フィールド4`) , '')
                                            , IF(DIR.`フィールド5` <> '', CONCAT(' ', DIR.`フィールド5`) , '')
                                            , IF(DIR.`フィールド6` <> '', CONCAT(' ', DIR.`フィールド6`) , '')
                                            , IF(DIR.AMAZON検索キーワード1 <> '', CONCAT(' ', DIR.`AMAZON検索キーワード1`), '')
                                            , i.amazon_title
                                          )
                                          , 333
                                        )
EOD;
    $dbMain->query($sql);

    // 複合出品商品対応。
    // 複合出品の子商品となるSKUは、レコードを一度待避させて再挿入を行う。
    // * 1. 元々の親レコードの除去
    // * 2. 親SKUの変更
    // * 3. 出力順を、付け替え先の親SKUより後にするために、削除して再挿入する。

    // -- 待避テーブル作成
    // $temporaryWord = ' TEMPORARY ';
    $temporaryWord = ' '; // FOR DEBUG
    $dbMain->query("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_mixed_product_amazoninfo_detail");
    $dbMain->query("CREATE {$temporaryWord} TABLE tmp_work_mixed_product_amazoninfo_detail LIKE tb_amazoninfo_detail");

    // -- 待避テーブルへ待避
    $sql = <<<EOD
      INSERT IGNORE INTO tmp_work_mixed_product_amazoninfo_detail (
          `item_sku`
        , `item_name`
        , `brand_name`
        , `product_subtype`
        , `part_number`
        , `product_description`
        , `quantity`
        , `fulfillment_latency`
        , `condition_type`
        , `standard_price`
        , `currency`
        , `missing_keyset_reason`
        , `bullet_point1`
        , `generic_keywords1`
        , `generic_keywords2`
        , `recommended_browse_nodes1`
        , `main_image_url`
        , `swatch_image_url`
        , `other_image_url1`
        , `other_image_url2`
        , `other_image_url3`
        , `other_image_url4`
        , `other_image_url5`
        , `other_image_url6`
        , `other_image_url7`
        , `other_image_url8`
        , `parent_child`
        , `parent_sku`
        , `daihyo_syohin_code`
        , `relationship_type`
        , `variation_theme`
        , `size_name`
        , `color_name`
        , `department_name`
        , `fba_baika`
        , `fba_flg`
      )
      SELECT
          d.`item_sku`
        , d.`item_name`
        , d.`brand_name`
        , d.`product_subtype`
        , d.`part_number`
        , d.`product_description`
        , d.`quantity`
        , d.`fulfillment_latency`
        , d.`condition_type`
        , d.`standard_price`
        , d.`currency`
        , d.`missing_keyset_reason`
        , d.`bullet_point1`
        , d.`generic_keywords1`
        , d.`generic_keywords2`
        , d.`recommended_browse_nodes1`
        , d.`main_image_url`
        , d.`swatch_image_url`
        , d.`other_image_url1`
        , d.`other_image_url2`
        , d.`other_image_url3`
        , d.`other_image_url4`
        , d.`other_image_url5`
        , d.`other_image_url6`
        , d.`other_image_url7`
        , d.`other_image_url8`
        , d.`parent_child`
        , mp.parent AS `parent_sku`
        , d.`daihyo_syohin_code`
        , d.`relationship_type`
        , d.`variation_theme`
        , d.`size_name`
        , d.`color_name`
        , d.`department_name`
        , d.`fba_baika`
        , d.`fba_flg`
      FROM tb_amazoninfo_detail d
      INNER JOIN tb_mixed_product mp ON d.daihyo_syohin_code = mp.daihyo_syohin_code
      INNER JOIN tb_productchoiceitems pci ON d.item_sku = pci.ne_syohin_syohin_code
      WHERE mp.mall_code = 'amazon'
        AND d.parent_child = 'child'
      ORDER BY mp.parent
             , mp.display_order
             , pci.`並び順No`
EOD;
    $dbMain->exec($sql);

    // 出力用テーブルから該当商品コードのSKUを全削除（元の親商品レコードもこのときまとめて消える）
    $sql = <<<EOD
      DELETE d
      FROM tb_amazoninfo_detail d
      INNER JOIN (
        SELECT
         DISTINCT d.daihyo_syohin_code
        FROM tmp_work_mixed_product_amazoninfo_detail d
      ) T ON d.daihyo_syohin_code = T.daihyo_syohin_code
EOD;
    $dbMain->exec($sql);

    // 出力用テーブルへ書き戻す。
    // 条件： New親がAmazonに登録されている（or される予定である）こと。
    // ※ 差分更新出力時に、New親SKUが含まれない可能性がある。ただ、Amazonに存在するのであれば、子のみ出力してもよい。はず。
    //   * tb_amazoninfo_detail にNew親SKUが存在する。
    //   * あるいは、tb_amazon_product_stock にNew親SKUが存在し、tmp_amazon_detail_delete にNew親SKUが存在しない。

    $sql = <<<EOD
      INSERT IGNORE INTO tb_amazoninfo_detail
      SELECT
        d.*
      FROM tmp_work_mixed_product_amazoninfo_detail d
      WHERE d.parent_sku IN (
              SELECT
                d.item_sku
              FROM tb_amazoninfo_detail d
              WHERE d.parent_child = 'parent'
            )
        OR (
          d.parent_sku IN (
              SELECT
                s.sku
              FROM tb_amazon_product_stock s
              INNER JOIN tb_mainproducts m ON s.sku = m.daihyo_syohin_code
              WHERE s.asin <> ''
          )
          AND
          d.parent_sku NOT IN (
              SELECT
                d.item_sku
              FROM tmp_amazon_detail_delete d
          )
        )
EOD;
    $dbMain->exec($sql);

    // セット商品の在庫数を更新する
    // セット商品は、セット商品としてpciに登録している在庫数ではなく、構成品の在庫数を元に計算する
    $sql = <<<EOD
      UPDATE tb_amazoninfo_detail d
      INNER JOIN (
        SELECT
            pci.ne_syohin_syohin_code AS set_sku
          , CASE -- 定形外・定形はAmazonで販売しないため、在庫0に
              WHEN pci_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                OR pci_sd.shipping_group_code IS NULL AND (
                  mp_sd.shipping_group_code IN (:shippingGroupCodeTeikeigai, :shippingGroupCodeTeikei)
                ) THEN 0
              ELSE MIN(TRUNCATE((CASE WHEN i.sell_in_amazon = 0 THEN 0 ELSE COALESCE(pci_detail.フリー在庫数, 0) END / d.num), 0)) /* 内訳SKUフリー在庫からの作成可能数 */
              END
            AS creatable_num
        FROM tb_productchoiceitems pci 
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        INNER JOIN tb_amazoninfomation AS i ON pci_detail.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN tb_shippingdivision pci_sd ON pci.shippingdivision_id = pci_sd.id -- SKUの送料は設定されているとは限らない
        INNER JOIN tb_shippingdivision mp_sd ON m.送料設定 = mp_sd.id -- 代表商品の送料はあるはず
        WHERE m.set_flg <> 0
        GROUP BY set_sku
      ) T ON d.item_sku = T.set_sku AND d.parent_child = 'child'
      SET d.quantity = T.creatable_num
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->execute();

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

    $logTitle = 'AmazonCSV出力処理';
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

    $headerDescription = "TemplateType=fptcustomcustom\tVersion=2019.0322\tTemplateSignature=T1VURVJXRUFSLFNXRUFURVIsU0tJUlQsU0hPUlRTLFVOREVSV0VBUixTTEVFUFdFQVIsU09DS1NIT1NJRVJZLE9CSSxCTEFaRVIsU0hJUlQsS0lNT05PLEJBRyxTVUlULFlVS0FUQSxTV0lNV0VBUixCUkEsRFJFU1MsQUNDRVNTT1JZLEpJTkJFSSxIQVQsUEFOVFMsU0hPRVMsQ0hBTkNIQU5LTw==\t上3行は Amazon.com 記入用です。上3行は変更または削除しないでください。.\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t画像\t\t\t\t\t\t\t\tバリエーション\t\t\t\t商品基本情報\t\t\t\t\t\t\t\t\t\t\t\t商品検索情報\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t推奨ブラウズノード別の情報\t\t\t\t\t\t\t\t\t\t\t\t寸法\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t出荷関連情報\t\t\t\t\t\t\t\tコンプライアンス情報\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t出品情報\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tb2b\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";

    $headers = [
       'feed_product_type' => 'feed_product_type',
       'item_sku' => '出品者SKU',
       'brand_name' => 'ブランド名',
       'item_name' => '商品名',
       'external_product_id' => '商品コード(JANコード等)',
       'external_product_id_type' => '商品コードのタイプ',
       'outer_material_type' => '表地素材',
       'recommended_browse_nodes' => '推奨ブラウズノード',
       'size_name' => 'サイズ',
       'color_name' => 'カラー',
       'color_map' => 'カラーマップ',
       'style_name' => 'スタイル名',
       'material_composition1' => '素材構成',
       'material_composition2' => '素材構成',
       'material_composition3' => '素材構成',
       'material_composition4' => '素材構成',
       'material_composition5' => '素材構成',
       'material_composition6' => '素材構成',
       'material_composition7' => '素材構成',
       'material_composition8' => '素材構成',
       'material_composition9' => '素材構成',
       'material_composition10' => '素材構成',
       'item_length_description' => '長さ',
       'special_features1' => '機能性',
       'special_features2' => '機能性',
       'special_features3' => '機能性',
       'special_features4' => '機能性',
       'special_features5' => '機能性',
       'lifestyle1' => 'ライフスタイル',
       'lifestyle2' => 'ライフスタイル',
       'department_name' => '対象年齢・性別',
       'size_map' => 'サイズマップ',
       'band_size_num' => 'バンドサイズ',
       'band_size_num_unit_of_measure' => 'バンドサイズの単位',
       'is_adult_product' => 'アダルト商品',
       'quantity' => '在庫数',
       'standard_price' => '商品の販売価格',
       'item_package_quantity' => 'item_package_quantity',
       'main_image_url' => '商品メイン画像URL',
       'other_image_url1' => '商品のサブ画像URL1',
       'other_image_url2' => '商品のサブ画像URL2',
       'other_image_url3' => '商品のサブ画像URL3',
       'other_image_url4' => '商品のサブ画像URL4',
       'other_image_url5' => '商品のサブ画像URL5',
       'other_image_url6' => '商品のサブ画像URL6',
       'other_image_url7' => '商品のサブ画像URL7',
       'other_image_url8' => '商品のサブ画像URL8',
       'parent_child' => '親子指定',
       'parent_sku' => '親商品のSKU(商品管理番号)',
       'relationship_type' => '親子関係のタイプ',
       'variation_theme' => 'バリエーションテーマ',
       'update_delete' => 'アップデート・削除',
       'part_number' => 'メーカー型番',
       'product_description' => '商品説明文',
       'heel_type' => 'ヒールのタイプ',
       'closure_type' => '留め具のタイプ',
       'model_year' => 'モデル年(発売年・発表年)',
       'inner_material_type1' => 'inner_material_type1',
       'inner_material_type2' => 'inner_material_type2',
       'inner_material_type3' => 'inner_material_type3',
       'inner_material_type4' => 'inner_material_type4',
       'inner_material_type5' => 'inner_material_type5',
       'sole_material' => 'sole_material',
       'bullet_point1' => '商品説明の箇条書き',
       'bullet_point2' => '商品説明の箇条書き',
       'bullet_point3' => '商品説明の箇条書き',
       'bullet_point4' => '商品説明の箇条書き',
       'bullet_point5' => '商品説明の箇条書き',
       'generic_keywords' => '検索キーワード',
       'style_keywords' => 'スタイルキーワード',
       'seasons' => 'シーズン',
       'platinum_keywords1' => 'プラチナキーワード',
       'platinum_keywords2' => 'プラチナキーワード',
       'platinum_keywords3' => 'プラチナキーワード',
       'platinum_keywords4' => 'プラチナキーワード',
       'platinum_keywords5' => 'プラチナキーワード',
       'specific_uses_keywords1' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords2' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords3' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords4' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords5' => 'コート・ワンピース・チュニック着丈',
       'fit_type' => 'fit_type',
       'strap_type' => 'ストラップのタイプ',
       'toe_shape' => 'つま先の形状(トゥシェープ)',
       'waist_style' => 'ウエストのスタイル',
       'opacity' => '素材不透明度',
       'sleeve_type' => '袖のタイプ',
       'collar_style' => 'シャツカラースタイル',
       'neck_style' => '首のタイプ',
       'bottom_style' => 'ボトムススタイル',
       'cup_size' => 'カップサイズ',
       'material_type' => 'material_type',
       'fit_to_size_description' => '収納可能サイズ',
       'shaft_style_type' => 'シャフトスタイルタイプ',
       'shoe_width' => '靴の幅',
       'shaft_height' => 'シャフト(軸)の丈',
       'waist_size_unit_of_measure' => 'ウエストサイズの単位',
       'waist_size' => 'ウエストサイズ',
       'inseam_length_unit_of_measure' => '仕立ての長さの単位',
       'inseam_length' => '仕立ての長さ',
       'sleeve_length_unit_of_measure' => '袖の長さの単位',
       'sleeve_length' => '袖の長さ',
       'neck_size_unit_of_measure' => '首のサイズの単位',
       'neck_size' => '首のサイズ',
       'chest_size_unit_of_measure' => '胸囲サイズの単位',
       'chest_size' => '胸囲サイズ',
       'website_shipping_weight_unit_of_measure' => '配送重量の単位',
       'website_shipping_weight' => '配送重量',
       'shaft_height_unit_of_measure' => 'シャフトの高さの測定単位',
       'item_width' => 'item_width',
       'item_height' => 'item_height',
       'shoe_width_unit_of_measure' => 'shoe_width_unit_of_measure',
       'item_dimensions_unit_of_measure' => '商品の寸法の単位',
       'item_length' => 'item_length',
       'package_length_unit_of_measure' => '商品パッケージの長さの単位',
       'package_length' => '商品パッケージの長さ',
       'package_width' => '商品パッケージの幅',
       'package_height' => '商品パッケージの高さ',
       'package_weight_unit_of_measure' => '商品パッケージの重量の単位',
       'package_weight' => '商品パッケージの重量',
       'fulfillment_center_id' => 'フルフィルメントセンターID',
       'package_dimensions_unit_of_measure' => '商品パッケージの寸法の単位',
       'fabric_type' => '素材または繊維',
       'batteries_required' => 'この商品は電池本体ですか？または電池を使用した商品ですか？',
       'are_batteries_included' => '電池付属',
       'battery_cell_composition' => '電池の組成',
       'battery_type1' => '電池の種類、サイズ',
       'battery_type2' => '電池の種類、サイズ',
       'battery_type3' => '電池の種類、サイズ',
       'number_of_batteries1' => '電池の数',
       'number_of_batteries2' => '電池の数',
       'number_of_batteries3' => '電池の数',
       'battery_weight' => '電池の重量(グラム)',
       'battery_weight_unit_of_measure' => '電池の重量の測定単位',
       'number_of_lithium_metal_cells' => 'リチウムメタル電池単数',
       'number_of_lithium_ion_cells' => 'リチウムイオン電池単数',
       'lithium_battery_packaging' => 'リチウム電池パッケージ',
       'lithium_battery_energy_content' => '電池当たりのワット時',
       'lithium_battery_energy_content_unit_of_measure' => 'リチウム電池のエネルギー量測定単位',
       'lithium_battery_weight' => 'リチウム含有量(グラム)',
       'lithium_battery_weight_unit_of_measure' => 'リチウム電池の重量の測定単位',
       'supplier_declared_dg_hz_regulation1' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation2' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation3' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation4' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation5' => '適用される危険物関連法令',
       'hazmat_united_nations_regulatory_id' => '国連(UN)番号',
       'safety_data_sheet_url' => '安全データシート(SDS) URL',
       'item_weight' => '商品の重量',
       'item_weight_unit_of_measure' => '商品の重量の単位',
       'item_volume' => '体積',
       'item_volume_unit_of_measure' => '商品の容量の単位',
       'country_of_origin' => '原産国/地域',
       'flash_point' => '引火点(°C)',
       'ghs_classification_class1' => '分類／危険物ラベル(適用されるものをすべて選択)',
       'ghs_classification_class2' => '分類／危険物ラベル(適用されるものをすべて選択)',
       'ghs_classification_class3' => '分類／危険物ラベル(適用されるものをすべて選択)',
       'fulfillment_latency' => '出荷作業日数',
       'condition_type' => '商品のコンディション',
       'condition_note' => '商品のコンディション説明',
       'standard_price_points' => 'ポイント',
       'sale_price_points' => 'セール時ポイント',
       'product_site_launch_date' => '商品の公開日',
       'merchant_release_date' => '予約商品の販売開始日',
       'list_price' => 'メーカー希望価格',
       'optional_payment_type_exclusion' => '使用しない支払い方法',
       'delivery_schedule_group_id' => '配送日時指定SKUリスト',
       'sale_price' => 'セール価格',
       'sale_from_date' => 'セール開始日',
       'sale_end_date' => 'セール終了日',
       'restock_date' => '商品の入荷予定日',
       'max_order_quantity' => '最大注文個数',
       'offering_can_be_gift_messaged' => 'ギフトメッセージ',
       'offering_can_be_giftwrapped' => 'ギフト包装',
       'is_discontinued_by_manufacturer' => 'メーカー製造中止',
       'merchant_shipping_group_name' => '配送パターン',
       'distribution_designation' => '販売形態(並行輸入品)',
       'offering_end_date' => '廃盤日',
       'offering_start_date' => '販売開始日',
       'product_tax_code' => '商品タックスコード',
       'business_price' => '法人価格',
       'quantity_price_type' => '数量割引のタイプ',
       'quantity_lower_bound1' => '数量の下限1',
       'quantity_price1' => '数量割引1',
       'quantity_lower_bound2' => '数量の下限2',
       'quantity_price2' => '数量割引2',
       'quantity_lower_bound3' => '数量の下限3',
       'quantity_price3' => '数量割引3',
       'quantity_lower_bound4' => '数量の下限4',
       'quantity_price4' => '数量割引4',
       'quantity_lower_bound5' => '数量の下限5',
       'quantity_price5' => '数量割引5',
       'pricing_action' => '価格設定操作',
       'unspsc_code' => 'unspsc_code',
       'national_stock_number' => 'national_stock_number',
    ];

    $sql = <<<EOD
      SELECT
          '' as `feed_product_type`
        ,  d.item_sku AS `item_sku`
        , '' AS `brand_name`
        , '' AS `item_name`
        , '' AS `external_product_id`
        , '' AS `external_product_id_type`
        , '' AS `outer_material_type`
        , '' AS `recommended_browse_nodes`
        , '' AS `size_name`
        , '' AS `color_name`
        , '' AS `color_map`
        , '' AS `style_name`
        , '' AS `material_composition1`
        , '' AS `material_composition2`
        , '' AS `material_composition3`
        , '' AS `material_composition4`
        , '' AS `material_composition5`
        , '' AS `material_composition6`
        , '' AS `material_composition7`
        , '' AS `material_composition8`
        , '' AS `material_composition9`
        , '' AS `material_composition10`
        , '' AS `item_length_description`
        , '' AS `special_features1`
        , '' AS `special_features2`
        , '' AS `special_features3`
        , '' AS `special_features4`
        , '' AS `special_features5`
        , '' AS `lifestyle1`
        , '' AS `lifestyle2`
        , '' AS `department_name`
        , '' AS `size_map`
        , '' AS `band_size_num`
        , '' AS `band_size_num_unit_of_measure`
        , '' AS `is_adult_product`
        , '' AS `quantity`
        , '' AS `standard_price`
        , '' AS `item_package_quantity`
        , '' AS `main_image_url`
        , '' AS `other_image_url1`
        , '' AS `other_image_url2`
        , '' AS `other_image_url3`
        , '' AS `other_image_url4`
        , '' AS `other_image_url5`
        , '' AS `other_image_url6`
        , '' AS `other_image_url7`
        , '' AS `other_image_url8`
        , '' AS `parent_child`
        , '' AS `parent_sku`
        , '' AS `relationship_type`
        , '' AS `variation_theme`
        , 'Delete' AS `update_delete`
        , '' AS `part_number`
        , '' AS `product_description`
        , '' AS `heel_type`
        , '' AS `closure_type`
        , '' AS `model_year`
        , '' AS `inner_material_type1`
        , '' AS `inner_material_type2`
        , '' AS `inner_material_type3`
        , '' AS `inner_material_type4`
        , '' AS `inner_material_type5`
        , '' AS `sole_material`
        , '' AS `bullet_point1`
        , '' AS `bullet_point2`
        , '' AS `bullet_point3`
        , '' AS `bullet_point4`
        , '' AS `bullet_point5`
        , '' AS `generic_keywords`
        , '' AS `style_keywords`
        , '' AS `seasons`
        , '' AS `platinum_keywords1`
        , '' AS `platinum_keywords2`
        , '' AS `platinum_keywords3`
        , '' AS `platinum_keywords4`
        , '' AS `platinum_keywords5`
        , '' AS `specific_uses_keywords1`
        , '' AS `specific_uses_keywords2`
        , '' AS `specific_uses_keywords3`
        , '' AS `specific_uses_keywords4`
        , '' AS `specific_uses_keywords5`
        , '' AS `fit_type`
        , '' AS `strap_type`
        , '' AS `toe_shape`
        , '' AS `waist_style`
        , '' AS `opacity`
        , '' AS `sleeve_type`
        , '' AS `collar_style`
        , '' AS `neck_style`
        , '' AS `bottom_style`
        , '' AS `cup_size`
        , '' AS `material_type`
        , '' AS `fit_to_size_description`
        , '' AS `shaft_style_type`
        , '' AS `shoe_width`
        , '' AS `shaft_height`
        , '' AS `waist_size_unit_of_measure`
        , '' AS `waist_size`
        , '' AS `inseam_length_unit_of_measure`
        , '' AS `inseam_length`
        , '' AS `sleeve_length_unit_of_measure`
        , '' AS `sleeve_length`
        , '' AS `neck_size_unit_of_measure`
        , '' AS `neck_size`
        , '' AS `chest_size_unit_of_measure`
        , '' AS `chest_size`
        , '' AS `website_shipping_weight_unit_of_measure`
        , '' AS `website_shipping_weight`
        , '' AS `shaft_height_unit_of_measure`
        , '' AS `item_width`
        , '' AS `item_height`
        , '' AS `shoe_width_unit_of_measure`
        , '' AS `item_dimensions_unit_of_measure`
        , '' AS `item_length`
        , '' AS `package_length_unit_of_measure`
        , '' AS `package_length`
        , '' AS `package_width`
        , '' AS `package_height`
        , '' AS `package_weight_unit_of_measure`
        , '' AS `package_weight`
        , '' AS `fulfillment_center_id`
        , '' AS `package_dimensions_unit_of_measure`
        , '' AS `fabric_type`
        , '' AS `batteries_required`
        , '' AS `are_batteries_included`
        , '' AS `battery_cell_composition`
        , '' AS `battery_type1`
        , '' AS `battery_type2`
        , '' AS `battery_type3`
        , '' AS `number_of_batteries1`
        , '' AS `number_of_batteries2`
        , '' AS `number_of_batteries3`
        , '' AS `battery_weight`
        , '' AS `battery_weight_unit_of_measure`
        , '' AS `number_of_lithium_metal_cells`
        , '' AS `number_of_lithium_ion_cells`
        , '' AS `lithium_battery_packaging`
        , '' AS `lithium_battery_energy_content`
        , '' AS `lithium_battery_energy_content_unit_of_measure`
        , '' AS `lithium_battery_weight`
        , '' AS `lithium_battery_weight_unit_of_measure`
        , '' AS `supplier_declared_dg_hz_regulation1`
        , '' AS `supplier_declared_dg_hz_regulation2`
        , '' AS `supplier_declared_dg_hz_regulation3`
        , '' AS `supplier_declared_dg_hz_regulation4`
        , '' AS `supplier_declared_dg_hz_regulation5`
        , '' AS `hazmat_united_nations_regulatory_id`
        , '' AS `safety_data_sheet_url`
        , '' AS `item_weight`
        , '' AS `item_weight_unit_of_measure`
        , '' AS `item_volume`
        , '' AS `item_volume_unit_of_measure`
        , '' AS `country_of_origin`
        , '' AS `flash_point`
        , '' AS `ghs_classification_class1`
        , '' AS `ghs_classification_class2`
        , '' AS `ghs_classification_class3`
        , '' AS `fulfillment_latency`
        , '' AS `condition_type`
        , '' AS `condition_note`
        , '' AS `standard_price_points`
        , '' AS `sale_price_points`
        , '' AS `product_site_launch_date`
        , '' AS `merchant_release_date`
        , '' AS `list_price`
        , '' AS `optional_payment_type_exclusion`
        , '' AS `delivery_schedule_group_id`
        , '' AS `sale_price`
        , '' AS `sale_from_date`
        , '' AS `sale_end_date`
        , '' AS `restock_date`
        , '' AS `max_order_quantity`
        , '' AS `offering_can_be_gift_messaged`
        , '' AS `offering_can_be_giftwrapped`
        , '' AS `is_discontinued_by_manufacturer`
        , '' AS `merchant_shipping_group_name`
        , '' AS `distribution_designation`
        , '' AS `offering_end_date`
        , '' AS `offering_start_date`
        , '' AS `product_tax_code`
        , '' AS `business_price`
        , '' AS `quantity_price_type`
        , '' AS `quantity_lower_bound1`
        , '' AS `quantity_price1`
        , '' AS `quantity_lower_bound2`
        , '' AS `quantity_price2`
        , '' AS `quantity_lower_bound3`
        , '' AS `quantity_price3`
        , '' AS `quantity_lower_bound4`
        , '' AS `quantity_price4`
        , '' AS `quantity_lower_bound5`
        , '' AS `quantity_price5`
        , '' AS `pricing_action`
        , '' AS `unspsc_code`
        , '' AS `national_stock_number`
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
          fputs($fp, $mallProcess->createCsvHeaderLines($headerDescription, $headers));
          $files[] = $filePath;
        }

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), $noEncloseFields, "\t");
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . "\r\n";
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

//    $headerDescription = "TemplateType=Clothing\tVersion=2015.1209\t上3行は Amazon.com 記入用です。上3行は変更または削除しないでください。\t\t\t\t\t\t\t\t出品情報 - 商品をサイト上で販売可能にする際に必要な項目\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t寸法 - 商品のサイズや重量を入力する項目\t\t商品検索情報 - サーチ上で商品を検索されやすくするために必要な項目\t\t\t\t\t\t\t\t\t画像 - 商品画像を表示させるために必要な項目。詳しくは画像説明タブを参照\t\t\t\t\t\t\t\t\t\t出荷関連情報 - フルフィルメント by Amazon (FBA) の利用、あるいは自社出荷の注文に関する出荷関連情報を、この項目に記入してください。FBA を利用する場合には必須の項目。\t\t\t\t\t\t\tバリエーション - 商品の色・サイズなどのバリエーションを作成する際に必須の項目\t\t\t\tProductTypeによって必須となる項目\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
    $headerDescription = "TemplateType=fptcustomcustom\tVersion=2019.0322\tTemplateSignature=T1VURVJXRUFSLFNXRUFURVIsU0tJUlQsU0hPUlRTLFVOREVSV0VBUixTTEVFUFdFQVIsU09DS1NIT1NJRVJZLE9CSSxCTEFaRVIsU0hJUlQsS0lNT05PLEJBRyxTVUlULFlVS0FUQSxTV0lNV0VBUixCUkEsRFJFU1MsQUNDRVNTT1JZLEpJTkJFSSxIQVQsUEFOVFMsU0hPRVMsQ0hBTkNIQU5LTw==\t上3行は Amazon.com 記入用です。上3行は変更または削除しないでください。.\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t画像\t\t\t\t\t\t\t\tバリエーション\t\t\t\t商品基本情報\t\t\t\t\t\t\t\t\t\t\t\t商品検索情報\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t推奨ブラウズノード別の情報\t\t\t\t\t\t\t\t\t\t\t\t寸法\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t出荷関連情報\t\t\t\t\t\t\t\tコンプライアンス情報\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t出品情報\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\tb2b\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";

    $headers = [
       'feed_product_type' => 'feed_product_type',
       'item_sku' => '出品者SKU',
       'brand_name' => 'ブランド名',
       'item_name' => '商品名',
       'external_product_id' => '商品コード(JANコード等)',
       'external_product_id_type' => '商品コードのタイプ',
       'outer_material_type' => '表地素材',
       'recommended_browse_nodes' => '推奨ブラウズノード',
       'size_name' => 'サイズ',
       'color_name' => 'カラー',
       'color_map' => 'カラーマップ',
       'style_name' => 'スタイル名',
       'material_composition1' => '素材構成',
       'material_composition2' => '素材構成',
       'material_composition3' => '素材構成',
       'material_composition4' => '素材構成',
       'material_composition5' => '素材構成',
       'material_composition6' => '素材構成',
       'material_composition7' => '素材構成',
       'material_composition8' => '素材構成',
       'material_composition9' => '素材構成',
       'material_composition10' => '素材構成',
       'item_length_description' => '長さ',
       'special_features1' => '機能性',
       'special_features2' => '機能性',
       'special_features3' => '機能性',
       'special_features4' => '機能性',
       'special_features5' => '機能性',
       'lifestyle1' => 'ライフスタイル',
       'lifestyle2' => 'ライフスタイル',
       'department_name' => '対象年齢・性別',
       'size_map' => 'サイズマップ',
       'band_size_num' => 'バンドサイズ',
       'band_size_num_unit_of_measure' => 'バンドサイズの単位',
       'is_adult_product' => 'アダルト商品',
       'quantity' => '在庫数',
       'standard_price' => '商品の販売価格',
       'item_package_quantity' => 'item_package_quantity',
       'main_image_url' => '商品メイン画像URL',
       'other_image_url1' => '商品のサブ画像URL1',
       'other_image_url2' => '商品のサブ画像URL2',
       'other_image_url3' => '商品のサブ画像URL3',
       'other_image_url4' => '商品のサブ画像URL4',
       'other_image_url5' => '商品のサブ画像URL5',
       'other_image_url6' => '商品のサブ画像URL6',
       'other_image_url7' => '商品のサブ画像URL7',
       'other_image_url8' => '商品のサブ画像URL8',
       'parent_child' => '親子指定',
       'parent_sku' => '親商品のSKU(商品管理番号)',
       'relationship_type' => '親子関係のタイプ',
       'variation_theme' => 'バリエーションテーマ',
       'update_delete' => 'アップデート・削除',
       'part_number' => 'メーカー型番',
       'product_description' => '商品説明文',
       'heel_type' => 'ヒールのタイプ',
       'closure_type' => '留め具のタイプ',
       'model_year' => 'モデル年(発売年・発表年)',
       'inner_material_type1' => 'inner_material_type1',
       'inner_material_type2' => 'inner_material_type2',
       'inner_material_type3' => 'inner_material_type3',
       'inner_material_type4' => 'inner_material_type4',
       'inner_material_type5' => 'inner_material_type5',
       'sole_material' => 'sole_material',
       'bullet_point1' => '商品説明の箇条書き',
       'bullet_point2' => '商品説明の箇条書き',
       'bullet_point3' => '商品説明の箇条書き',
       'bullet_point4' => '商品説明の箇条書き',
       'bullet_point5' => '商品説明の箇条書き',
       'generic_keywords' => '検索キーワード',
       'style_keywords' => 'スタイルキーワード',
       'seasons' => 'シーズン',
       'platinum_keywords1' => 'プラチナキーワード',
       'platinum_keywords2' => 'プラチナキーワード',
       'platinum_keywords3' => 'プラチナキーワード',
       'platinum_keywords4' => 'プラチナキーワード',
       'platinum_keywords5' => 'プラチナキーワード',
       'specific_uses_keywords1' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords2' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords3' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords4' => 'コート・ワンピース・チュニック着丈',
       'specific_uses_keywords5' => 'コート・ワンピース・チュニック着丈',
       'fit_type' => 'fit_type',
       'strap_type' => 'ストラップのタイプ',
       'toe_shape' => 'つま先の形状(トゥシェープ)',
       'waist_style' => 'ウエストのスタイル',
       'opacity' => '素材不透明度',
       'sleeve_type' => '袖のタイプ',
       'collar_style' => 'シャツカラースタイル',
       'neck_style' => '首のタイプ',
       'bottom_style' => 'ボトムススタイル',
       'cup_size' => 'カップサイズ',
       'material_type' => 'material_type',
       'fit_to_size_description' => '収納可能サイズ',
       'shaft_style_type' => 'シャフトスタイルタイプ',
       'shoe_width' => '靴の幅',
       'shaft_height' => 'シャフト(軸)の丈',
       'waist_size_unit_of_measure' => 'ウエストサイズの単位',
       'waist_size' => 'ウエストサイズ',
       'inseam_length_unit_of_measure' => '仕立ての長さの単位',
       'inseam_length' => '仕立ての長さ',
       'sleeve_length_unit_of_measure' => '袖の長さの単位',
       'sleeve_length' => '袖の長さ',
       'neck_size_unit_of_measure' => '首のサイズの単位',
       'neck_size' => '首のサイズ',
       'chest_size_unit_of_measure' => '胸囲サイズの単位',
       'chest_size' => '胸囲サイズ',
       'website_shipping_weight_unit_of_measure' => '配送重量の単位',
       'website_shipping_weight' => '配送重量',
       'shaft_height_unit_of_measure' => 'シャフトの高さの測定単位',
       'item_width' => 'item_width',
       'item_height' => 'item_height',
       'shoe_width_unit_of_measure' => 'shoe_width_unit_of_measure',
       'item_dimensions_unit_of_measure' => '商品の寸法の単位',
       'item_length' => 'item_length',
       'package_length_unit_of_measure' => '商品パッケージの長さの単位',
       'package_length' => '商品パッケージの長さ',
       'package_width' => '商品パッケージの幅',
       'package_height' => '商品パッケージの高さ',
       'package_weight_unit_of_measure' => '商品パッケージの重量の単位',
       'package_weight' => '商品パッケージの重量',
       'fulfillment_center_id' => 'フルフィルメントセンターID',
       'package_dimensions_unit_of_measure' => '商品パッケージの寸法の単位',
       'fabric_type' => '素材または繊維',
       'batteries_required' => 'この商品は電池本体ですか？または電池を使用した商品ですか？',
       'are_batteries_included' => '電池付属',
       'battery_cell_composition' => '電池の組成',
       'battery_type1' => '電池の種類、サイズ',
       'battery_type2' => '電池の種類、サイズ',
       'battery_type3' => '電池の種類、サイズ',
       'number_of_batteries1' => '電池の数',
       'number_of_batteries2' => '電池の数',
       'number_of_batteries3' => '電池の数',
       'battery_weight' => '電池の重量(グラム)',
       'battery_weight_unit_of_measure' => '電池の重量の測定単位',
       'number_of_lithium_metal_cells' => 'リチウムメタル電池単数',
       'number_of_lithium_ion_cells' => 'リチウムイオン電池単数',
       'lithium_battery_packaging' => 'リチウム電池パッケージ',
       'lithium_battery_energy_content' => '電池当たりのワット時',
       'lithium_battery_energy_content_unit_of_measure' => 'リチウム電池のエネルギー量測定単位',
       'lithium_battery_weight' => 'リチウム含有量(グラム)',
       'lithium_battery_weight_unit_of_measure' => 'リチウム電池の重量の測定単位',
       'supplier_declared_dg_hz_regulation1' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation2' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation3' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation4' => '適用される危険物関連法令',
       'supplier_declared_dg_hz_regulation5' => '適用される危険物関連法令',
       'hazmat_united_nations_regulatory_id' => '国連(UN)番号',
       'safety_data_sheet_url' => '安全データシート(SDS) URL',
       'item_weight' => '商品の重量',
       'item_weight_unit_of_measure' => '商品の重量の単位',
       'item_volume' => '体積',
       'item_volume_unit_of_measure' => '商品の容量の単位',
       'country_of_origin' => '原産国/地域',
       'flash_point' => '引火点(°C)',
       'ghs_classification_class1' => '分類／危険物ラベル(適用されるものをすべて選択)',
       'ghs_classification_class2' => '分類／危険物ラベル(適用されるものをすべて選択)',
       'ghs_classification_class3' => '分類／危険物ラベル(適用されるものをすべて選択)',
       'fulfillment_latency' => '出荷作業日数',
       'condition_type' => '商品のコンディション',
       'condition_note' => '商品のコンディション説明',
       'standard_price_points' => 'ポイント',
       'sale_price_points' => 'セール時ポイント',
       'product_site_launch_date' => '商品の公開日',
       'merchant_release_date' => '予約商品の販売開始日',
       'list_price' => 'メーカー希望価格',
       'optional_payment_type_exclusion' => '使用しない支払い方法',
       'delivery_schedule_group_id' => '配送日時指定SKUリスト',
       'sale_price' => 'セール価格',
       'sale_from_date' => 'セール開始日',
       'sale_end_date' => 'セール終了日',
       'restock_date' => '商品の入荷予定日',
       'max_order_quantity' => '最大注文個数',
       'offering_can_be_gift_messaged' => 'ギフトメッセージ',
       'offering_can_be_giftwrapped' => 'ギフト包装',
       'is_discontinued_by_manufacturer' => 'メーカー製造中止',
       'merchant_shipping_group_name' => '配送パターン',
       'distribution_designation' => '販売形態(並行輸入品)',
       'offering_end_date' => '廃盤日',
       'offering_start_date' => '販売開始日',
       'product_tax_code' => '商品タックスコード',
       'business_price' => '法人価格',
       'quantity_price_type' => '数量割引のタイプ',
       'quantity_lower_bound1' => '数量の下限1',
       'quantity_price1' => '数量割引1',
       'quantity_lower_bound2' => '数量の下限2',
       'quantity_price2' => '数量割引2',
       'quantity_lower_bound3' => '数量の下限3',
       'quantity_price3' => '数量割引3',
       'quantity_lower_bound4' => '数量の下限4',
       'quantity_price4' => '数量割引4',
       'quantity_lower_bound5' => '数量の下限5',
       'quantity_price5' => '数量割引5',
       'pricing_action' => '価格設定操作',
       'unspsc_code' => 'unspsc_code',
       'national_stock_number' => 'national_stock_number',
    ];

    $sql = <<<EOD
      SELECT
          d.product_subtype AS `feed_product_type`
        , d.item_sku AS `item_sku`
        , d.brand_name AS `brand_name`
        , d.item_name AS `item_name`
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
        , '' as `outer_material_type`
        , d.recommended_browse_nodes1 AS `recommended_browse_nodes`
        , d.size_name AS `size_name`
        , d.color_name AS `color_name`
        , '' as `color_map`
        , '' as `style_name`
        , '' as `material_composition1`
        , '' as `material_composition2`
        , '' as `material_composition3`
        , '' as `material_composition4`
        , '' as `material_composition5`
        , '' as `material_composition6`
        , '' as `material_composition7`
        , '' as `material_composition8`
        , '' as `material_composition9`
        , '' as `material_composition10`
        , '' as `item_length_description`
        , '' as `special_features1`
        , '' as `special_features2`
        , '' as `special_features3`
        , '' as `special_features4`
        , '' as `special_features5`
        , '' as `lifestyle1`
        , '' as `lifestyle2`
        , d.department_name AS `department_name`
        , '' as `size_map`
        , '' as `band_size_num`
        , '' as `band_size_num_unit_of_measure`
        , '' as `is_adult_product`
        /* FBA対象は在庫を0に */
        , CASE
            WHEN d.snl_flg <> 0 AND d.snl_baika > 0 THEN 0
            WHEN COALESCE(s.fba_quantity_fulfillable > 0) THEN 0
            ELSE d.quantity
          END AS `quantity`
        /* S&Lフラグ あるいは FBAフラグで決定。（在庫有無・出荷元にかかわらず固定） */
        , CASE
            WHEN d.snl_flg <> 0 AND d.snl_baika > 0 THEN d.snl_baika
            WHEN d.fba_flg <> 0 AND d.fba_baika > 0 THEN d.fba_baika
            ELSE d.standard_price
          END AS `standard_price`
        , '' as `item_package_quantity`
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
        , d.parent_child AS `parent_child`
        , d.parent_sku AS `parent_sku`
        , d.relationship_type AS `relationship_type`
        , d.variation_theme AS `variation_theme`
        , 'PartialUpdate' AS `update_delete`
        , d.part_number AS `part_number`
        , d.product_description AS `product_description`
        , '' as `heel_type`
        , '' as `closure_type`
        , '' as `model_year`
        , '' as `inner_material_type1`
        , '' as `inner_material_type2`
        , '' as `inner_material_type3`
        , '' as `inner_material_type4`
        , '' as `inner_material_type5`
        , '' as `sole_material`
        , d.bullet_point1 AS `bullet_point1`
        , '' AS `bullet_point2`
        , '' AS `bullet_point3`
        , '' AS `bullet_point4`
        , '' AS `bullet_point5`
        , d.generic_keywords1 AS `generic_keywords`
        , '' AS `style_keywords`
        , '' AS `style_keywords`
        , '' AS `seasons`
        , '' AS `platinum_keywords1`
        , '' AS `platinum_keywords2`
        , '' AS `platinum_keywords3`
        , '' AS `platinum_keywords4`
        , '' AS `platinum_keywords5`
        , '' AS `specific_uses_keywords1`
        , '' AS `specific_uses_keywords2`
        , '' AS `specific_uses_keywords3`
        , '' AS `specific_uses_keywords4`
        , '' AS `specific_uses_keywords5`
        , '' AS `fit_type`
        , '' AS `strap_type`
        , '' AS `toe_shape`
        , '' AS `waist_style`
        , '' AS `opacity`
        , '' AS `sleeve_type`
        , '' AS `collar_style`
        , '' AS `neck_style`
        , '' AS `bottom_style`
        , '' AS `cup_size`
        , '' AS `material_type`
        , '' AS `fit_to_size_description`
        , '' AS `shaft_style_type`
        , '' AS `shoe_width`
        , '' AS `shaft_height`
        , '' AS `waist_size_unit_of_measure`
        , '' AS `waist_size`
        , '' AS `inseam_length_unit_of_measure`
        , '' AS `inseam_length`
        , '' AS `sleeve_length_unit_of_measure`
        , '' AS `sleeve_length`
        , '' AS `neck_size_unit_of_measure`
        , '' AS `neck_size`
        , '' AS `chest_size_unit_of_measure`
        , '' AS `chest_size`
        , '' AS `website_shipping_weight_unit_of_measure`
        , '' AS `website_shipping_weight`
        , '' AS `shaft_height_unit_of_measure`
        , '' AS `item_width`
        , '' AS `item_height`
        , '' AS `shoe_width_unit_of_measure`
        , '' AS `item_dimensions_unit_of_measure`
        , '' AS `item_length`
        , 'CM' AS `package_length_unit_of_measure`
        , CASE WHEN COALESCE(d.package_length, 0) = 0 THEN '' ELSE d.package_length END AS `package_length`
        , CASE WHEN COALESCE(d.package_width, 0) = 0 THEN '' ELSE d.package_width END AS `package_width`
        , CASE WHEN COALESCE(d.package_height, 0) = 0 THEN '' ELSE d.package_height END AS `package_height`
        , 'GR' AS `package_weight_unit_of_measure`
        , CASE WHEN COALESCE(d.package_weight, 0) = 0 THEN '' ELSE d.package_weight END AS `package_weight`
        /* このファイル（L）では出品者出荷固定。ファイル(I) でFBAを切り替える */
        , CASE
            WHEN d.parent_child = 'parent' THEN ''
            WHEN d.snl_flg <> 0 AND d.snl_baika > 0 THEN 'AMAZON_JP'
            WHEN COALESCE(s.fba_quantity_fulfillable > 0) THEN 'AMAZON_JP'
            ELSE 'DEFAULT'
          END AS `fulfillment_center_id`
        , '' AS `package_dimensions_unit_of_measure`
        , '' AS `fabric_type`
        , '' AS `batteries_required`
        , '' AS `are_batteries_included`
        , '' AS `battery_cell_composition`
        , '' AS `battery_type1`
        , '' AS `battery_type2`
        , '' AS `battery_type3`
        , '' AS `number_of_batteries1`
        , '' AS `number_of_batteries2`
        , '' AS `number_of_batteries3`
        , '' AS `battery_weight`
        , '' AS `battery_weight_unit_of_measure`
        , '' AS `number_of_lithium_metal_cells`
        , '' AS `number_of_lithium_ion_cells`
        , '' AS `lithium_battery_packaging`
        , '' AS `lithium_battery_energy_content`
        , '' AS `lithium_battery_energy_content_unit_of_measure`
        , '' AS `lithium_battery_weight`
        , '' AS `lithium_battery_weight_unit_of_measure`
        , '' AS `supplier_declared_dg_hz_regulation1`
        , '' AS `supplier_declared_dg_hz_regulation2`
        , '' AS `supplier_declared_dg_hz_regulation3`
        , '' AS `supplier_declared_dg_hz_regulation4`
        , '' AS `supplier_declared_dg_hz_regulation5`
        , '' AS `hazmat_united_nations_regulatory_id`
        , '' AS `safety_data_sheet_url`
        , '' AS `item_weight`
        , '' AS `item_weight_unit_of_measure`
        , '' AS `item_volume`
        , '' AS `item_volume_unit_of_measure`
        , '' AS `country_of_origin`
        , '' AS `flash_point`
        , '' AS `ghs_classification_class1`
        , '' AS `ghs_classification_class2`
        , '' AS `ghs_classification_class3`
        , d.fulfillment_latency AS `fulfillment_latency`
        , d.condition_type AS `condition_type`
        , '' AS `condition_note`
        , '' AS `standard_price_points`
        , '' AS `sale_price_points`
        , '' AS `product_site_launch_date`
        , '' AS `merchant_release_date`
        , '' AS `list_price`
        , '' AS `optional_payment_type_exclusion`
        , '' AS `delivery_schedule_group_id`
        , '' AS `sale_price`
        , '' AS `sale_from_date`
        , '' AS `sale_end_date`
        , '' AS `restock_date`
        , '' AS `max_order_quantity`
        , '' AS `offering_can_be_gift_messaged`
        , '' AS `offering_can_be_giftwrapped`
        , '' AS `is_discontinued_by_manufacturer`
        , '' AS `merchant_shipping_group_name`
        , '' AS `distribution_designation`
        , '' AS `offering_end_date`
        , '' AS `offering_start_date`
        , '' AS `product_tax_code`
        , '' AS `business_price`
        , '' AS `quantity_price_type`
        , '' AS `quantity_lower_bound1`
        , '' AS `quantity_price1`
        , '' AS `quantity_lower_bound2`
        , '' AS `quantity_price2`
        , '' AS `quantity_lower_bound3`
        , '' AS `quantity_price3`
        , '' AS `quantity_lower_bound4`
        , '' AS `quantity_price4`
        , '' AS `quantity_lower_bound5`
        , '' AS `quantity_price5`
        , '' AS `pricing_action`
        , '' AS `unspsc_code`
        , '' AS `national_stock_number`
      FROM tb_amazoninfo_detail d
      LEFT JOIN tb_amazon_product_stock AS s ON d.item_sku = s.sku
      WHERE COALESCE(standard_price, 0) > 0
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
          fputs($fp, $mallProcess->createCsvHeaderLines($headerDescription, $headers));

          $files[] = $filePath;
        }

        // キーワードは250バイトまで。安全のためUTF-8の状態で切り捨て
        $row['generic_keywords'] = mb_strcut($row['generic_keywords'], 0, 249, 'UTF-8');

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), $noEncloseFields, "\t");
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . "\r\n";
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


    // ====================
    // FBA切り替え用 (Amazon_FBA.txt)
    // テンプレートは 出品用ファイル(I)
    // FBAマルチチャネル用にのみ登録された商品は、FBA在庫があっても切り替えないように注意。
    // 電池有りの商品も、切り替えないように注意。（暫定仕様）
    // ====================
    $logger->info('Amazon_FBA.txt 作成中');

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
      SELECT
          d.item_sku AS `sku`
        , ''         AS `price`
        , ''         AS `standard-price-points`
        , ''         AS `quantity`
        , ''         AS `currency`
        , ''         AS `sale-price`
        , ''         AS `sale-price-points`
        , ''         AS `sale-from-date`
        , ''         AS `sale-through-date`
        , ''         AS `restock-date`
        , ''         AS `minimum-seller-allowed-price`
        , ''         AS `maximum-seller-allowed-price`
        , 'amazon'   AS `fulfillment-channel`
        , ''         AS `handling-time`
      FROM tb_amazoninfo_detail d
      INNER JOIN tb_mainproducts m ON d.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_amazoninfomation i ON d.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN tb_amazon_product_stock AS s ON d.item_sku = s.sku
      WHERE d.parent_child = 'child'
        AND (
             (d.snl_flg <> 0 AND d.snl_baika > 0)       /* S&L */
          OR (COALESCE(s.fba_quantity_fulfillable > 0)) /* FBA */
        )
        AND i.sell_in_amazon <> 0 /* Amazon販売している商品のみが切り替え対象(= FBAマルチチャネルのみの商品は絶対に切り替えない) */
        AND m.batteries_required = 0 /* 電池あり商品は切り替えない ※暫定仕様 */
      ORDER BY d.item_sku
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
          $filePath = sprintf('%s/Amazon_FBA_%d.txt', $this->exportPath, $fileNum);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $mallProcess->createCsvHeaderLines($headerDescription, $headers));

          $files[] = $filePath;
        }

        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), $noEncloseFields, "\t");
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . "\r\n";
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

      $logger->info("Amazon CSV出力 Amazon_FBA_x.txt: $totalCount 件 / $fileNum ファイル");

    } else {
      $logger->info("Amazon CSV出力 Amazon_FBA_x.txt: 件数が0のためファイルは作成しませんでした。");
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
    $imageHost = $this->getContainer()->getParameter('host_plusnao');
    $imageUrl = sprintf('https://%s/images', $imageHost);
    $noImageUrl = sprintf('https://%s/img/amazon-no-image.png', $imageHost);

    // (memo) INSERT 時にやってしまってはだめ？
    // また、amazonメイン画像が存在することを前提とする。（amazonメイン画像がなければ固定画像）
    $sql = <<<EOD
      UPDATE tb_amazoninfo_detail     AS d
      INNER JOIN tb_amazoninfomation  AS i ON d.daihyo_syohin_code = i.daihyo_syohin_code
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
      WHERE i.org_pic_num = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':imageUrl', $imageUrl, \PDO::PARAM_STR);
    $stmt->execute();

    // メイン画像がない商品（= FBAマルチチャネル用登録）
    $sql = <<<EOD
      UPDATE tb_amazoninfo_detail     AS d
      INNER JOIN tb_amazoninfomation  AS i ON d.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts      AS m ON d.daihyo_syohin_code = m.daihyo_syohin_code
      SET d.main_image_url = :noImageUrl
      WHERE COALESCE(d.main_image_url, '') = ''
      AND i.sell_in_amazon = 0  
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':noImageUrl', $noImageUrl, \PDO::PARAM_STR);
    $stmt->execute();


    // オリジナル画像数が1以上の場合はオリジナル画像のURL（連番）
    $sql = <<<EOD
      UPDATE tb_amazoninfo_detail d
      INNER JOIN tb_amazoninfomation AS i ON d.daihyo_syohin_code = i.daihyo_syohin_code
      SET
          d.main_image_url   = CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-1.jpg', NULL)
        , d.other_image_url1 = IF(i.org_pic_num >= 2, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-2.jpg'), NULL)
        , d.other_image_url2 = IF(i.org_pic_num >= 3, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-3.jpg'), NULL)
        , d.other_image_url3 = IF(i.org_pic_num >= 4, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-4.jpg'), NULL)
        , d.other_image_url4 = IF(i.org_pic_num >= 5, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-5.jpg'), NULL)
        , d.other_image_url5 = IF(i.org_pic_num >= 6, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-6.jpg'), NULL)
        , d.other_image_url6 = IF(i.org_pic_num >= 7, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-7.jpg'), NULL)
        , d.other_image_url7 = IF(i.org_pic_num >= 8, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-8.jpg'), NULL)
        , d.other_image_url8 = IF(i.org_pic_num >= 9, CONCAT('http://plus-nao.com/PIC/amazon/', i.daihyo_syohin_code, '-9.jpg'), NULL)
      WHERE i.org_pic_num > 0
EOD;
    $dbMain->query($sql);

    // 商品バリエーション画像（メイン画像 および swatch（色見本）画像とも更新）
    $sql = <<<EOD
      UPDATE
      tb_amazoninfo_detail d
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
      FROM tb_amazon_product_stock s
      INNER JOIN tmp_amazon_detail_delete d ON s.sku = d.item_sku
EOD;
    $dbMain->query($sql);

    // 更新SKU レコード追加・更新
    $sql = <<<EOD
      INSERT INTO tb_amazon_product_stock (
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
      FROM tb_amazoninfo_detail d
      ON DUPLICATE KEY UPDATE
          price     = VALUES(price)
        , quantity  = VALUES(quantity)
        , lead_time = VALUES(lead_time)
EOD;
    $dbMain->query($sql);

    // 更新フラグリセット
    $sql = <<<EOD
      UPDATE tb_amazoninfomation i
      SET i.update_flg = 0
      WHERE i.update_flg <> 0
EOD;
    $dbMain->query($sql);

    return;
  }


}
