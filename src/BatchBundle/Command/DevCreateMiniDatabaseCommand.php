<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Util\BatchLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\RuntimeException;


class DevCreateMiniDatabaseCommand extends ContainerAwareCommand
{
  /** @var BatchLogger */
  private $logger;

  private $doMirror;

  protected function configure()
  {
    $this
      ->setName('dev:create-mini-db')
      ->setDescription('開発用に小さなデータベースを作成する。※--env=prod,dev で実行してはいけない。必ず、--env=test（デフォルト）で実行する。')
      ->addOption('do-mirror', null, InputOption::VALUE_OPTIONAL, 'バックアップからミラー処理を行うか', 0)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \Symfony\Bundle\FrameworkBundle\Console\Application $application */
    $application = $this->getApplication();
    $env = $application->getKernel()->getEnvironment();
    if ($env !== 'test') {
      throw new \RuntimeException('the environment is not "test". aborted.');
    }

    /** @var BatchLogger $logger */
    $logger = $container->get('misc.util.batch_logger');
    $logger->initLogTimer();
    $this->logger = $logger;

    $this->doMirror = (bool)$input->getOption('do-mirror');

    $logger->info('開発用DBを作成します。');
    $time = microtime(true);

    try {
      /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
      $doctrine = $container->get('doctrine');

      // main 接続を利用するが、実行する場合には必ず --env=test 状態で実行する（デフォルト状態）
      // つまりバッチサーバの test_plusnao_db から取得してINSERT、という処理を想定。
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $container->get('doctrine')->getConnection('main');

      $devDbName  = 'test_plusnao_db'; // ここは本当に危険（空にされる）なので、あえてベタ書き。
      $miniDBName = 'test_plusnao_db_mini';

      // バックアップファイルパス
      $rootDir = $container->get('kernel')->getRootDir();
      $backupDir = dirname(dirname($rootDir)) . '/backup/daily';

      $fs = new FileSystem();
      $fileDate = new \DateTime();
      $filename = sprintf("%s.gz", $fileDate->format('Ymd'));
      $filenameSchema = sprintf("%s_schema.sql", $fileDate->format('Ymd'));
      $filenameStored = sprintf("%s_stored.sql", $fileDate->format('Ymd'));
      $filenameLogSchema = sprintf("%s_log_schema_with_stored.sql", $fileDate->format('Ymd'));

      if (!$fs->exists($backupDir) || !$fs->exists($backupDir . '/' . $filename)) {
        throw new RuntimeException('no backup file nor dir!! [' . $backupDir . '][' . $filename . ']');
      }

      $username = $dbMain->getUsername();
      $password = $dbMain->getPassword();
      $command = sprintf('mysql -h%s -u%s -p%s', $dbMain->getHost(), $username, $password);

      if ($this->doMirror) {

        // ミラー作成
        $logger->info('開発用DB ミラーデータ作成 開始');
        $time = microtime(true);

        // スキーマの更新（＆データクリア）
        system("cat ${backupDir}/${filenameSchema} | ${command} ${devDbName}" );
        // データの流し込み
        system("gunzip -c ${backupDir}/${filename} | ${command} ${devDbName}");

        // stored (トリガ含む) の流し込み
        // トリガ内の plusnao_log_db => test_plusnao_log_db 文言置換
        $fileNameMiniDbStored = sprintf('%s/mini_db.stored.sql', $backupDir);
        system(sprintf('cp %s/%s %s', $backupDir, $filenameStored, $fileNameMiniDbStored));
        system("sed -i -e 's/plusnao_log_db/test_plusnao_log_db/g' {$fileNameMiniDbStored}");
        system("cat {$fileNameMiniDbStored} | ${command} ${devDbName}" );

        $logger->info('開発用DB ミラーデータ作成 終了');
      }

      // 開発用のミニ DB作成
      // データだけあればよいので、ルーティン、トリガは不要。
      $logger->info('開発用DB ミニDB 初期化');
      $dbMain->query("DROP DATABASE IF EXISTS ${miniDBName}");
      $dbMain->query("CREATE DATABASE ${miniDBName} DEFAULT CHARACTER SET utf8");
      system("cat ${backupDir}/${filenameSchema} | ${command} ${miniDBName}" );

      // 当座の基準
      // 直近1ヶ月以内に登録された商品（仮登録含む）

      // --------------------------------------------------
      // 商品関連 ： 直近登録1ヶ月分
      // --------------------------------------------------
      $limitDate = new \DateTime();
      $limitDate->modify('-1 month');

      // 商品関連 登録日時で取得
      $logger->info('開発用DB: 商品関連');

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_mainproducts
SELECT
  m.*
FROM tb_mainproducts m
INNER JOIN (
  SELECT main_id, MIN(created) AS created
  FROM product_registration_logs
  GROUP BY main_id
) PLOG ON m.daihyo_syohin_code = PLOG.main_id
WHERE PLOG.created >= ?
EOD;
      $dbMain->prepare($sql)->execute([$limitDate->format('Y-m-d')]);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // セット商品を追加
      $sql = <<<EOD
        INSERT IGNORE INTO `$miniDBName`.tb_mainproducts
        SELECT 
          DISTINCT set_m.*
        FROM
          tb_mainproducts set_m
          JOIN tb_mainproducts_cal set_cal
            ON set_m.daihyo_syohin_code = set_cal.daihyo_syohin_code
          JOIN tb_productchoiceitems set_pci
            ON set_m.daihyo_syohin_code = set_pci.daihyo_syohin_code
          JOIN tb_set_product_detail set_detail
            ON set_detail.set_ne_syohin_syohin_code = set_pci.ne_syohin_syohin_code
          JOIN tb_productchoiceitems pci
            ON set_detail.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          JOIN tb_mainproducts m
            ON pci.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // セット商品の構成品を追加
      $sql = <<<EOD
        INSERT IGNORE INTO `$miniDBName`.tb_mainproducts
        SELECT
          DISTINCT m.*
        FROM
          tb_mainproducts m
          JOIN tb_mainproducts_cal cal 
            ON m.daihyo_syohin_code = cal.daihyo_syohin_code
          JOIN tb_productchoiceitems pci
            ON m.daihyo_syohin_code = pci.daihyo_syohin_code
          JOIN tb_set_product_detail d
            ON d.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // あとは登録したminiDB商品マスタに紐付けて取得

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_mainproducts_cal (
    `daihyo_syohin_code`
  , `endofavailability`
  , `deliverycode`
  , `genka_tnk_ave`
  , `baika_tnk`
  , `base_baika_tanka`
  , `cost_tanka`
  , `profit_rate`
  , `sunfactoryset`
  , `list_some_instant_delivery`
  , `priority`
  , `earliest_order_date`
  , `delay_days`
  , `visible_flg`
  , `sales_volume`
  , `makeshop_Registration_flug`
  , `rakuten_Registration_flug`
  , `croozmall_Registration_flug`
  , `amazon_registration_flug`
  , `annual_sales`
  , `rakuten_Registration_flug_date`
  , `setnum`
  , `rakutencategory_tep`
  , `being_num`
  , `mall_price_flg`
  , `daihyo_syohin_label`
  , `maxbuynum`
  , `outlet`
  , `adult`
  , `adult_image_flg`
  , `adult_check_status`
  , `big_size`
  , `viewrank`
  , `reviewrequest`
  , `last_review_date`
  , `review_point_ave`
  , `review_num`
  , `search_code`
  , `fixed_cost`
  , `cost_rate`
  , `DENA画像チェック区分`
  , `dena_pic_check_datetime`
  , `dena_pic_check_datetime_sort`
  , `notfound_image_no_rakuten`
  , `notfound_image_no_dena`
  , `startup_flg`
  , `pricedown_flg`
  , `discount_base_date`
  , `red_flg`
  , `last_orderdate`
  , `wang_status`
  , `受発注可能フラグ退避F`
  , `soldout_check_flg`
  , `label_remark_flg`
  , `size_check_need_flg`
  , `weight_check_need_flg`
  , `compress_flg`
  , `image_photo_need_flg`
  , `image_gradeup_need_flg`
  , `image_erase_character_need_flg`
  , `image_photo_comment`
  , `deliverycode_pre`
  , `high_sales_rate_flg`
  , `mail_send_nums`
  /* , `nekoposu_send_nums` -- generated column につきスキップ  */
  , `bundle_num_average`
  , `memo`
  , `timestamp`
  , `rakutencategories_3`
  , `zaiko_teisu_reset_date`
  , `quality_level`
  , `quality_level_updated`
  , `work_check_01`
  , `work_check_02`
  , `work_check_03`
  , `work_check_04`
)
SELECT
    `cal`.`daihyo_syohin_code`
  , `cal`.`endofavailability`
  , `cal`.`deliverycode`
  , `cal`.`genka_tnk_ave`
  , `cal`.`baika_tnk`
  , `cal`.`base_baika_tanka`
  , `cal`.`cost_tanka`
  , `cal`.`profit_rate`
  , `cal`.`sunfactoryset`
  , `cal`.`list_some_instant_delivery`
  , `cal`.`priority`
  , `cal`.`earliest_order_date`
  , `cal`.`delay_days`
  , `cal`.`visible_flg`
  , `cal`.`sales_volume`
  , `cal`.`makeshop_Registration_flug`
  , `cal`.`rakuten_Registration_flug`
  , `cal`.`croozmall_Registration_flug`
  , `cal`.`amazon_registration_flug`
  , `cal`.`annual_sales`
  , `cal`.`rakuten_Registration_flug_date`
  , `cal`.`setnum`
  , `cal`.`rakutencategory_tep`
  , `cal`.`being_num`
  , `cal`.`mall_price_flg`
  , `cal`.`daihyo_syohin_label`
  , `cal`.`maxbuynum`
  , `cal`.`outlet`
  , `cal`.`adult`
  , `cal`.`adult_image_flg`
  , `cal`.`adult_check_status`
  , `cal`.`big_size`
  , `cal`.`viewrank`
  , `cal`.`reviewrequest`
  , `cal`.`last_review_date`
  , `cal`.`review_point_ave`
  , `cal`.`review_num`
  , `cal`.`search_code`
  , `cal`.`fixed_cost`
  , `cal`.`cost_rate`
  , `cal`.`DENA画像チェック区分`
  , `cal`.`dena_pic_check_datetime`
  , `cal`.`dena_pic_check_datetime_sort`
  , `cal`.`notfound_image_no_rakuten`
  , `cal`.`notfound_image_no_dena`
  , `cal`.`startup_flg`
  , `cal`.`pricedown_flg`
  , `cal`.`discount_base_date`
  , `cal`.`red_flg`
  , `cal`.`last_orderdate`
  , `cal`.`wang_status`
  , `cal`.`受発注可能フラグ退避F`
  , `cal`.`soldout_check_flg`
  , `cal`.`label_remark_flg`
  , `cal`.`size_check_need_flg`
  , `cal`.`weight_check_need_flg`
  , `cal`.`compress_flg`
  , `cal`.`image_photo_need_flg`
  , `cal`.`image_gradeup_need_flg`
  , `cal`.`image_erase_character_need_flg`
  , `cal`.`image_photo_comment`
  , `cal`.`deliverycode_pre`
  , `cal`.`high_sales_rate_flg`
  , `cal`.`mail_send_nums`
  /* , `cal`.`nekoposu_send_nums` -- generated column につきスキップ  */
  , `cal`.`bundle_num_average`
  , `cal`.`memo`
  , `cal`.`timestamp`
  , `cal`.`rakutencategories_3`
  , `cal`.`zaiko_teisu_reset_date`
  , `cal`.`quality_level`
  , `cal`.`quality_level_updated`
  , `cal`.`work_check_01`
  , `cal`.`work_check_02`
  , `cal`.`work_check_03`
  , `cal`.`work_check_04`
FROM tb_mainproducts_cal cal
INNER JOIN `$miniDBName`.tb_mainproducts m ON cal.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_productchoiceitems (
    `ne_syohin_syohin_code`
  , `並び順No`
  , `colname`
  , `colcode`
  , `rowname`
  , `rowcode`
  , `受発注可能フラグ`
  , `toriatukai_kbn`
  , `zaiko_teisu`
  , `hachu_ten`
  , `lot`
  , `daihyo_syohin_code`
  , `tag`
  , `location`
  /* , `フリー在庫数` -- generated column につきスキップ  */
  , `予約フリー在庫数`
  , `予約在庫修正値`
  , `在庫数`
  , `発注残数`
  , `最古発注伝票番号`
  , `最古発注日`
  , `previouslocation`
  , `予約引当数`
  , `引当数`
  , `ピッキング引当数`
  , `予約在庫数`
  , `不良在庫数`
  , `label_application`
  , `check_why`
  , `gmarket_copy_check`
  , `temp_shortage_date`
  , `maker_syohin_code`
  , `在庫あり時納期管理番号`
  , `support_colname`
  , `support_rowname`
  , `created`
  , `updated`
)
SELECT
    `p`.`ne_syohin_syohin_code`
  , `p`.`並び順No`
  , `p`.`colname`
  , `p`.`colcode`
  , `p`.`rowname`
  , `p`.`rowcode`
  , `p`.`受発注可能フラグ`
  , `p`.`toriatukai_kbn`
  , `p`.`zaiko_teisu`
  , `p`.`hachu_ten`
  , `p`.`lot`
  , `p`.`daihyo_syohin_code`
  , `p`.`tag`
  , `p`.`location`
  /* , `p`.`フリー在庫数` -- generated column につきスキップ  */
  , `p`.`予約フリー在庫数`
  , `p`.`予約在庫修正値`
  , `p`.`在庫数`
  , `p`.`発注残数`
  , `p`.`最古発注伝票番号`
  , `p`.`最古発注日`
  , `p`.`previouslocation`
  , `p`.`予約引当数`
  , `p`.`引当数`
  , `p`.`ピッキング引当数`
  , `p`.`予約在庫数`
  , `p`.`不良在庫数`
  , `p`.`label_application`
  , `p`.`check_why`
  , `p`.`gmarket_copy_check`
  , `p`.`temp_shortage_date`
  , `p`.`maker_syohin_code`
  , `p`.`在庫あり時納期管理番号`
  , `p`.`support_colname`
  , `p`.`support_rowname`
  , `p`.`created`
  , `p`.`updated`
FROM tb_productchoiceitems p
INNER JOIN `$miniDBName`.tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 商品SKU IDテーブル
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_product_code (
    id
  , ne_syohin_syohin_code
)
SELECT
    p.id
  , p.ne_syohin_syohin_code
FROM tb_product_code p
INNER JOIN `$miniDBName`.tb_productchoiceitems pci ON p.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 商品シーズン設定
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_product_season
SELECT
  p.*
FROM tb_product_season p
INNER JOIN `$miniDBName`.tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 商品レビュー
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_product_reviews
SELECT
  pr.*
FROM tb_product_reviews pr
WHERE pr.review_datetime >= ?
EOD;
      $dbMain->prepare($sql)->execute([$limitDate->format('Y-m-d')]);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_stockreturn
SELECT
  r.*
FROM tb_stockreturn r
INNER JOIN `$miniDBName`.tb_productchoiceitems p ON r.商品コード = p.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      // tb_productchoiceitems トリガによる更新不可回避のため、商品コードの一時テーブルを作成
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS {$miniDBName}.tmp_work_mini_db_syohin_code");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE  `$miniDBName`.tmp_work_mini_db_syohin_code (
          ne_syohin_syohin_code VARCHAR(50) NOT NULL PRIMARY KEY
        ) Engine=InnoDB DEFAULT CHARSET utf8
        SELECT
          ne_syohin_syohin_code
        FROM `$miniDBName`.tb_productchoiceitems
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_individualorderhistory (
    `id`
  , `発注伝票番号`
  , `明細行`
  , `商品コード`
  , `発注数`
  /* , `注残計` -- generated column につきスキップ */
  , `予定納期`
  , `備考`
  , `商品区分`
  , `受注伝票番号`
  , `仕入先cd`
  , `依頼先cd`
  , `商品区分値`
  , `発行日`
  , `option`
  , `regular`
  , `defective`
  , `shortage`
  , `quantity_price`
  , `remain_status`
  , `remain_ordered_date`
  , `remain_ordered_person`
  , `remain_arrived_date`
  , `remain_arrived_person`
  , `remain_shipping_date`
  , `remain_shipping_person`
  , `remain_stockout_date`
  , `remain_stockout_person`
  , `shipping_number`
  , `support_colname`
  , `support_rowname`
  , `vendor_comment`
  , `vendor_comment_updated`
  , `vendor_comment_processed`
)
SELECT
    `ih`.`id`
  , `ih`.`発注伝票番号`
  , `ih`.`明細行`
  , `ih`.`商品コード`
  , `ih`.`発注数`
  /* , `ih`.`注残計` -- generated column につきスキップ */
  , `ih`.`予定納期`
  , `ih`.`備考`
  , `ih`.`商品区分`
  , `ih`.`受注伝票番号`
  , `ih`.`仕入先cd`
  , `ih`.`依頼先cd`
  , `ih`.`商品区分値`
  , `ih`.`発行日`
  , `ih`.`option`
  , `ih`.`regular`
  , `ih`.`defective`
  , `ih`.`shortage`
  , `ih`.`quantity_price`
  , `ih`.`remain_status`
  , `ih`.`remain_ordered_date`
  , `ih`.`remain_ordered_person`
  , `ih`.`remain_arrived_date`
  , `ih`.`remain_arrived_person`
  , `ih`.`remain_shipping_date`
  , `ih`.`remain_shipping_person`
  , `ih`.`remain_stockout_date`
  , `ih`.`remain_stockout_person`
  , `ih`.`shipping_number`
  , `ih`.`support_colname`
  , `ih`.`support_rowname`
  , `ih`.`vendor_comment`
  , `ih`.`vendor_comment_updated`
  , `ih`.`vendor_comment_processed`
FROM tb_individualorderhistory ih
INNER JOIN `$miniDBName`.tmp_work_mini_db_syohin_code p ON ih.商品コード = p.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_inventory_interval
SELECT
  ii.*
FROM tb_inventory_interval ii
INNER JOIN `$miniDBName`.tb_productchoiceitems p ON ii.ne_syohin_syohin_code = p.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      $sql = <<<EOD
INSERT INTO `$miniDBName`.product_registration_logs
SELECT
  plog.*
FROM product_registration_logs plog
INNER JOIN `$miniDBName`.tb_mainproducts m ON plog.main_id = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_plusnaoproductdirectory はひとまず全部
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_plusnaoproductdirectory SELECT * FROM tb_plusnaoproductdirectory")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 商品画像
      $sql = <<<EOD
INSERT INTO `$miniDBName`.product_images
SELECT
  image.*
FROM product_images image
INNER JOIN `$miniDBName`.tb_mainproducts m ON image.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // Amazon商品画像
      $sql = <<<EOD
INSERT INTO `$miniDBName`.product_images_amazon
SELECT
  image.*
FROM product_images_amazon image
INNER JOIN `$miniDBName`.tb_mainproducts m ON image.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // アテンション画像
      $sql = <<<EOD
INSERT INTO `$miniDBName`.product_images_attention_image
SELECT
  image.*
FROM product_images_attention_image image
INNER JOIN `$miniDBName`.tb_mainproducts m ON image.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 出荷関連
      $logger->info('開発用DB: 出荷関連');

      /* tb_productchoiceitemsにSKUの登録が無いと、伝票詳細画面が開けないので
          出荷伝票明細テーブルを基準に、商品情報がある伝票を登録する */
      // tb_shipping_voucher_detail
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_shipping_voucher_detail`
        SELECT
          d.*
        FROM
          tb_shipping_voucher_detail d
        WHERE
          d.受注日 >= ?
          AND EXISTS (
            SELECT
              *
            FROM
              test_plusnao_db_mini.`tb_productchoiceitems` p
            WHERE
              p.ne_syohin_syohin_code = d.商品コード
          )
EOD;
      $dbMain->prepare($sql)->execute([$limitDate->format('Y-m-d')]);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_shipping_voucher
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_shipping_voucher`
        SELECT
          v.*
        FROM
          tb_shipping_voucher v
          INNER JOIN (
            SELECT DISTINCT
              d.voucher_id
            FROM
              `$miniDBName`.`tb_shipping_voucher_detail` d
          ) D
            ON v.id = D.voucher_id
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_shipping_voucher_packing_group
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_shipping_voucher_packing_group`
        SELECT
          g.*
        FROM
          tb_shipping_voucher_packing_group g
          INNER JOIN (
            SELECT DISTINCT
              v.shipping_voucher_packing_group_id
            FROM
              `$miniDBName`.`tb_shipping_voucher` v
            GROUP BY
              v.shipping_voucher_packing_group_id
          ) V
            ON g.id = V.shipping_voucher_packing_group_id
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_shipping_voucher_packing
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_shipping_voucher_packing`
        SELECT
          p.*
        FROM
          tb_shipping_voucher_packing p
          INNER JOIN `$miniDBName`.`tb_shipping_voucher` v
            ON p.voucher_id = v.id
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_productchoiceitemsにSKUの登録が無いと、ピッキング商品 ﾛｹｰｼｮﾝ画面が開けない
      // tb_delivery_picking_list
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_delivery_picking_list`
        SELECT
          pl.*
        FROM
          tb_delivery_picking_list pl
          INNER JOIN `$miniDBName`.`tb_productchoiceitems` p
            ON pl.商品コード = p.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_delivery_split_rule
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_delivery_split_rule`
        SELECT
          r.*
        FROM
          tb_delivery_split_rule r
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_progress_order
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_progress_order`
        SELECT
          o.*
        FROM
          tb_progress_order o
          INNER JOIN `$miniDBName`.tb_productchoiceitems pci
            ON o.商品コード（伝票） = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_progress_order 発送方法一括変換用
      // 動作確認用に5件 推奨配送方法コード と 配送方法コード が異なるようにする
      $sql = <<<EOD
        UPDATE `$miniDBName`.`tb_progress_order` o
        SET o.`推奨配送方法コード` = o.`推奨配送方法コード` - 1
        LIMIT 5 
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_productchoiceitems_shippingdivision_pending
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.`tb_productchoiceitems_shippingdivision_pending`
        SELECT
          sp.*
        FROM
          tb_productchoiceitems_shippingdivision_pending sp
          INNER JOIN `$miniDBName`.tb_mainproducts m
            ON sp.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // symfony_usersに存在するidにしないと、出荷リストが表示されない
      $sql = <<<EOD
        UPDATE `$miniDBName`.`tb_shipping_voucher`
        SET
          account = 1
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 仕入れ関連
      $logger->info('開発用DB: 仕入先関連');

      // vendoraddress だけ絞ってあとは全件
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_vendoraddress
SELECT
  va.*
FROM tb_vendoraddress va
INNER JOIN `$miniDBName`.tb_mainproducts m ON va.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_vendormasteraddress SELECT * FROM tb_vendormasteraddress")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_vendormasterdata SELECT * FROM tb_vendormasterdata")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_vendormasterdata_order SELECT * FROM tb_vendormasterdata_order")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      $logger->info('開発用DB: sales_detail関連');

      // やっぱりsales_detail 生データをベースに。
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_sales_detail
SELECT
  s.*
FROM tb_sales_detail s
INNER JOIN `$miniDBName`.tb_productchoiceitems p ON s.商品コード（伝票） = p.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 受注明細 分析用。というかこちらがメイン
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_sales_detail_analyze
SELECT
  sa.*
FROM tb_sales_detail_analyze sa
INNER JOIN `$miniDBName`.tb_sales_detail s ON sa.伝票番号 = s.伝票番号 AND  sa.明細行 = s.明細行
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_sales_detail_set_distribute_info
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_sales_detail_set_distribute_info
SELECT
  d.*
FROM tb_sales_detail_set_distribute_info d
INNER JOIN `$miniDBName`.tb_sales_detail_analyze sa ON sa.伝票番号 = d.voucher_number AND sa.明細行 = d.line_number
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 受注明細 初回出荷予定日テーブル
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_sales_detail_first_shipping_date
SELECT
  ss.*
FROM tb_sales_detail_first_shipping_date ss
INNER JOIN `$miniDBName`.tb_sales_detail s ON ss.伝票番号 = s.伝票番号 AND  ss.明細行 = s.明細行
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      // ひとまず全件（手抜き：どのみち（使うのであれば）再集計が必要なものが多いため、それは入れない方がよいかもしれない。）
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_buycount SELECT * FROM tb_sales_detail_buycount")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_cate_directory SELECT * FROM tb_sales_detail_voucher_cate_directory")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_cate_order_ym_trasition12 SELECT * FROM tb_sales_detail_voucher_cate_order_ym_trasition12")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_cate_order_ym_trasition24 SELECT * FROM tb_sales_detail_voucher_cate_order_ym_trasition24")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_item_order_ym_total SELECT * FROM tb_sales_detail_voucher_item_order_ym_total")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_item_shipping_ym SELECT * FROM tb_sales_detail_voucher_item_shipping_ym")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_shipping_ym SELECT * FROM tb_sales_detail_voucher_shipping_ym")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_sire_order_ym_trasition12 SELECT * FROM tb_sales_detail_voucher_sire_order_ym_trasition12")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_sire_order_ym_trasition24 SELECT * FROM tb_sales_detail_voucher_sire_order_ym_trasition24")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_cate_order_ym SELECT * FROM tb_sales_detail_voucher_cate_order_ym")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_order_ym SELECT * FROM tb_sales_detail_voucher_order_ym")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_order_ym_repeater SELECT * FROM tb_sales_detail_voucher_order_ym_repeater")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_repeater SELECT * FROM tb_sales_detail_voucher_repeater")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_repeater_shop SELECT * FROM tb_sales_detail_voucher_repeater_shop")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_sire_order_ym SELECT * FROM tb_sales_detail_voucher_sire_order_ym")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_ym_shop SELECT * FROM tb_sales_detail_voucher_ym_shop")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_detail_voucher_ym_shop_repeater SELECT * FROM tb_sales_detail_voucher_ym_shop_repeater")->execute();

      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' 受注 全件テーブル ');


      // 商品コードで絞れるものは絞る
      $dbMain->prepare("
        INSERT INTO `$miniDBName`.tb_sales_detail_profit SELECT s.* FROM tb_sales_detail_profit s
        INNER JOIN `$miniDBName`.tb_mainproducts m ON s.代表商品コード = m.daihyo_syohin_code
      ")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $dbMain->prepare("
        INSERT INTO `$miniDBName`.tb_sales_detail_voucher_item_order_ym SELECT s.* FROM tb_sales_detail_voucher_item_order_ym s
        INNER JOIN `$miniDBName`.tb_mainproducts m ON s.daihyo_syohin_code = m.daihyo_syohin_code
      ")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 伝票ベース
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_sales_detail_voucher
SELECT
  v.*
FROM tb_sales_detail_voucher v
WHERE EXISTS (
  SELECT * FROM tb_sales_detail s
  WHERE v.伝票番号 = s.伝票番号
)
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_sales_detail_voucher_ym_a
SELECT
  v.*
FROM tb_sales_detail_voucher_ym_a v
WHERE EXISTS (
  SELECT * FROM tb_sales_detail s
  WHERE v.伝票番号 = s.伝票番号
)
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // SHOPLIST 販売実績
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.tb_shoplist_daily_sales
        SELECT
          s.*
        FROM tb_shoplist_daily_sales s
        INNER JOIN `$miniDBName`.tb_mainproducts m ON s.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 履歴関連
      $logger->info('開発用DB: 履歴関連');

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_purchasedocument
SELECT
  pd.*
FROM tb_purchasedocument pd
INNER JOIN `$miniDBName`.tb_productchoiceitems p ON pd.商品コード = p.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute([$limitDate->format('Y-m-d')]);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_stock_history SELECT * FROM tb_stock_history")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_stock_history_ioh SELECT * FROM tb_stock_history_ioh")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_reviews SELECT * FROM tb_rakuten_reviews")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_shop_reviews SELECT * FROM tb_rakuten_shop_reviews")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 閲覧ランキング
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_viewranking
SELECT
  vr.*
FROM tb_viewranking vr
INNER JOIN `$miniDBName`.tb_mainproducts m ON vr.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // モール別情報
      $logger->info('開発用DB: モール別情報');

      // Amazon
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_amazoninfomation
SELECT
  ia.*
FROM tb_amazoninfomation ia
INNER JOIN `$miniDBName`.tb_mainproducts m ON ia.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' Amazon');

      // bidders
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_biddersinfomation
SELECT
  ib.*
FROM tb_biddersinfomation ib
INNER JOIN `$miniDBName`.tb_mainproducts m ON ib.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_biddersmobilelink SELECT * FROM tb_biddersmobilelink")->execute();

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_bidders_au_main
SELECT
  v.*
FROM tb_bidders_au_main v
WHERE EXISTS (
  SELECT * FROM tb_sales_detail s
  WHERE v.伝票番号 = s.伝票番号
)
EOD;
      $dbMain->prepare($sql)->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_bidders_category SELECT * FROM tb_bidders_category")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' bidders');

      // tb_bidders_folog 検索コード設定で利用されている意外と使われているテーブル
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_bidders_folog
SELECT
  b.*
FROM tb_bidders_folog b
INNER JOIN `$miniDBName`.tb_mainproducts m ON b.Code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();


      // makeshop
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_makeshop_infomation
SELECT
  im.*
FROM tb_makeshop_infomation im
INNER JOIN `$miniDBName`.tb_mainproducts m ON im.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' makeshop');


      // PPM
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_ppm_information
SELECT
  ip.*
FROM tb_ppm_information ip
INNER JOIN `$miniDBName`.tb_mainproducts m ON ip.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' PPM main');

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_ppm_category
SELECT
  ip.*
FROM tb_ppm_category ip
INNER JOIN `$miniDBName`.tb_mainproducts m ON ip.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' PPM cate');


      // Q10
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_qten_information
SELECT
  iq.*
FROM tb_qten_information iq
INNER JOIN `$miniDBName`.tb_mainproducts m ON iq.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' Q10 main');

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_qten_itemcode
SELECT
  iq.*
FROM tb_qten_itemcode iq
INNER JOIN `$miniDBName`.tb_mainproducts m ON iq.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' Q10 item');


      // rakuten
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_rakuteninformation
SELECT
  ir.*
FROM tb_rakuteninformation ir
INNER JOIN `$miniDBName`.tb_mainproducts m ON ir.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakutenmobilelink SELECT * FROM tb_rakutenmobilelink")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' rakuten');

      // rakuten motto
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_rakuten_motto_information
SELECT
  ir.*
FROM tb_rakuten_motto_information ir
INNER JOIN `$miniDBName`.tb_mainproducts m ON ir.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      // rakuten laforest
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_rakuten_laforest_information
SELECT
  ir.*
FROM tb_rakuten_laforest_information ir
INNER JOIN `$miniDBName`.tb_mainproducts m ON ir.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      // rakuten dolcissimo
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_rakuten_dolcissimo_information
SELECT
  ir.*
FROM tb_rakuten_dolcissimo_information ir
INNER JOIN `$miniDBName`.tb_mainproducts m ON ir.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      // rakuten gekipla
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_rakuten_gekipla_information
SELECT
  ir.*
FROM tb_rakuten_gekipla_information ir
INNER JOIN `$miniDBName`.tb_mainproducts m ON ir.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      // ec-cube
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_cube_information
SELECT
  ir.*
FROM tb_cube_information ir
INNER JOIN `$miniDBName`.tb_mainproducts m ON ir.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      // yahoo
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_yahoo_information
SELECT
  iy.*
FROM tb_yahoo_information iy
INNER JOIN `$miniDBName`.tb_mainproducts m ON iy.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' yahoo');


      // yahoo(kawa-e-mon)
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_yahoo_kawa_information
SELECT
  iyk.*
FROM tb_yahoo_kawa_information iyk
INNER JOIN `$miniDBName`.tb_mainproducts m ON iyk.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' yahoo(kawa-e-mon)');

      // yahoo(otoriyose)
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_yahoo_otoriyose_information
SELECT
  iyo.*
FROM tb_yahoo_otoriyose_information iyo
INNER JOIN `$miniDBName`.tb_mainproducts m ON iyo.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' yahoo(otoriyose)');


      // ss
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_ss_information
SELECT
  iss.*
FROM tb_ss_information iss
INNER JOIN `$miniDBName`.tb_mainproducts m ON iss.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_ss_category_list SELECT * FROM tb_ss_category_list")->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' SS');

      // GMO
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_gmo_infomation
SELECT
  ig.*
FROM tb_gmo_infomation ig
INNER JOIN `$miniDBName`.tb_mainproducts m ON ig.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' GMO');

      // SHOPLIST
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_shoplist_information
SELECT
  isl.*
FROM tb_shoplist_information isl
INNER JOIN `$miniDBName`.tb_mainproducts m ON isl.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' SHOPLIST');

      // Amazon.com
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_amazon_com_information
SELECT
  iac.*
FROM tb_amazon_com_information iac
INNER JOIN `$miniDBName`.tb_mainproducts m ON iac.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' SHOPLIST');

      // ロケーション関連

      // 外部キー制約は無いが、気持ち 倉庫マスタから。
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_warehouse
SELECT
  w.*
FROM tb_warehouse w
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' warehouse');


      // 外部キー制約があるのでロケーションマスタから。
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_location
SELECT
  DISTINCT l.*
FROM tb_location l
INNER JOIN tb_product_location pl ON pl.location_id = l.id
INNER JOIN `$miniDBName`.tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' location');

      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_product_location
SELECT
  pl.*
FROM tb_product_location pl
INNER JOIN `$miniDBName`.tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' product_location');

      // セット商品 SKU
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_set_product_sku
SELECT
  sps.*
FROM tb_set_product_sku sps
INNER JOIN `$miniDBName`.tb_productchoiceitems pci ON sps.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' tb_set_product_sku');

      // セット商品 内訳
      // tb_set_product_detail
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_set_product_detail
SELECT
  spd.*
FROM tb_set_product_detail spd
INNER JOIN `$miniDBName`.tb_productchoiceitems pci ON spd.set_ne_syohin_syohin_code = pci.ne_syohin_syohin_code
INNER JOIN `$miniDBName`.tb_productchoiceitems pci2 ON spd.ne_syohin_syohin_code = pci2.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' tb_set_product_detail');



      // 巡回テーブル
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_netsea_vendoraddress
SELECT
  va.*
FROM tb_netsea_vendoraddress va
ORDER BY display_order
LIMIT 1000
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' netsea_vendoraddress');

      // 在庫確認テーブル:Amazon
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.tb_amazon_product_stock
        SELECT
          s.*
        FROM tb_amazon_product_stock s
        INNER JOIN $miniDBName.tb_mainproducts m ON s.sku = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' amazon_product_stock(daihyo)');

      $sql = <<<EOD
        INSERT INTO `$miniDBName`.tb_amazon_product_stock
        SELECT
          s.*
        FROM tb_amazon_product_stock s
        INNER JOIN $miniDBName.tb_productchoiceitems pci ON s.sku = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' amazon_product_stock(sku)');


      // 在庫確認テーブル:Amazon.com
      // 削除除外商品のテストなどのためにはJOINによる絞込も微妙だが、テスト時には手で追加すればよい、ということでやっぱりJOIN
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.tb_amazon_com_product_stock
        SELECT
          s.*
        FROM tb_amazon_com_product_stock s
        INNER JOIN $miniDBName.tb_mainproducts m ON s.sku = m.daihyo_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' amazon_com_product_stock(daihyo)');

      $sql = <<<EOD
        INSERT INTO `$miniDBName`.tb_amazon_com_product_stock
        SELECT
          s.*
        FROM tb_amazon_com_product_stock s
        INNER JOIN $miniDBName.tb_productchoiceitems pci ON s.sku = pci.ne_syohin_syohin_code
EOD;
      $dbMain->prepare($sql)->execute();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2) . ' amazon_com_product_stock(sku)');

      // 全件コピーテーブル
      $logger->info('開発用DB: その他、全件コピーテーブル');

      // $dbMain->prepare("INSERT INTO `$miniDBName`.tb_log SELECT * FROM tb_log")->execute(); # => 外部向けに、削除
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_setting SELECT * FROM tb_setting")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_recordsini SELECT * FROM tb_recordsini")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_updaterecord SELECT * FROM tb_updaterecord")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_shopping_mall SELECT * FROM tb_shopping_mall")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_mall_payment_method SELECT * FROM tb_mall_payment_method")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_calendar SELECT * FROM tb_calendar")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_member SELECT * FROM tb_member")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.cake_sessions SELECT * FROM cake_sessions")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.symfony_users SELECT * FROM symfony_users")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.symfony_user_client SELECT * FROM symfony_user_client")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_product_sales_task SELECT * FROM tb_product_sales_task")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.sequence SELECT * FROM sequence")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.users SELECT * FROM users")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.groups SELECT * FROM groups")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.users_addinfos SELECT * FROM users_addinfos")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.users_salaries SELECT * FROM users_salaries")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.users_salary_classes SELECT * FROM users_salary_classes")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.yours_item_deliveries SELECT * FROM yours_item_deliveries")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.yours_item_masters SELECT * FROM yours_item_masters")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.forest_mailtemplates SELECT * FROM forest_mailtemplates")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_ana_match SELECT * FROM tb_sales_ana_match")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_category_list SELECT * FROM tb_rakuten_category_list")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_category_list_save SELECT * FROM tb_rakuten_category_list_save")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_gold_category SELECT * FROM tb_rakuten_gold_category")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_ngword SELECT * FROM tb_rakuten_ngword")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_delivery_method SELECT * FROM tb_delivery_method")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_free_email SELECT * FROM tb_free_email")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_gmo_category SELECT * FROM tb_gmo_category")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_gmo_upload_category SELECT * FROM tb_gmo_upload_category")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_item_class SELECT * FROM tb_item_class")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_shippingdivision SELECT * FROM tb_shippingdivision")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_sales_promotion_cost SELECT * FROM tb_sales_promotion_cost")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_discount_setting SELECT * FROM tb_discount_setting")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_product_cost_rate_list_setting SELECT * FROM tb_product_cost_rate_list_setting")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_rakuten_nokikanri SELECT * FROM tb_rakuten_nokikanri")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tax_price SELECT * FROM tax_price")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_1688_product SELECT * FROM tb_1688_product")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_1688_company SELECT * FROM tb_1688_company")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_mall_design SELECT * FROM tb_mall_design")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.purchasing_agent SELECT * FROM purchasing_agent")->execute();

      // NextEngine 区分値
      $dbMain->prepare("INSERT INTO `$miniDBName`.ne_kubun_payment_method SELECT * FROM ne_kubun_payment_method")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.ne_kubun_delivery_method SELECT * FROM ne_kubun_delivery_method")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.ne_payment_delivery_convert_setting SELECT * FROM ne_payment_delivery_convert_setting")->execute();


      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_delivery_picking_block SELECT * FROM tb_delivery_picking_block")->execute();
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_delivery_picking_block_detail SELECT * FROM tb_delivery_picking_block_detail")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_postage_international SELECT * FROM tb_postage_international")->execute();

      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_stock_replace_word SELECT * FROM tb_stock_replace_word")->execute();
      
      // 2020/11 開発用追加
      
      $limitDate2 = new \DateTime();
      $limitDate2->modify('-2 month');

      // キーワードランキング
      $sql = <<<EOD
INSERT INTO `$miniDBName`.tb_rakuten_search_keyword_ranking
SELECT
  rskr.*
FROM tb_rakuten_search_keyword_ranking rskr
WHERE rskr.ranking_date >= ?
EOD;
      $dbMain->prepare($sql)->execute([$limitDate2->format('Y-m-d')]);

      // 楽天キーワード
      $sql = <<<EOD
        INSERT INTO `$miniDBName`.tb_rakuten_search_keyword
        SELECT
          rsk.*
        FROM tb_rakuten_search_keyword rsk
        INNER JOIN (
          SELECT keyword_id FROM `$miniDBName`.tb_rakuten_search_keyword_ranking GROUP BY keyword_id
        ) rskr ON rsk.id = rskr.keyword_id
EOD;
      $dbMain->prepare($sql)->execute();
      
      $dbMain->prepare("INSERT INTO `$miniDBName`.tb_company SELECT * FROM tb_company")->execute();

      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      $logger->info('開発用DB: 商品売上担当者関連テーブル');
      $this->createProductSalesTable();

      // TODO 入れる必要があるが、見合わせているもの
      // tb_rakuten_tag
      // tb_rakuten_tag_mainproducts
      // tb_rakuten_tag_productchoiceitems


/* その他、入れる必要があるか微妙なもの。
donna_item_mains        8
donna_item_masters      78
donna_item_receivings   172
donna_suppliers 7
tb_access_analyze_petitprice    11
tb_access_analyze_ranking       11
tb_amazon_keyword2      536
tb_biddersmobilelink    14
tb_chat 84
tb_exclusion    12
tb_freestock    984
tb_image_check  275
tb_infomation   1
tb_mainproducts_random  100
tb_makeshop_upload_category     52
tb_mall_input_check     13
tb_member       12
tb_ne_delete_master     74
tb_order        35
tb_order_request        709
tb_order_request_summary        335
tb_order_summary_a      288
tb_order_summary_b      40
tb_payment_method       24
tb_ppm_image_error_tmp  41
tb_ppm_itemlist_del     277
tb_pricedown_ishida     819
tb_purchase_quantity    3
tb_purchasedocument_print       44
tb_qten_delivery        228
tb_qten_delivery_date   88
tb_qten_delivery_o      114
tb_qten_delivery_x      4
tb_qten_tracking        118
tb_rakuten_shop_reviews 723
tb_rakutenmobilelink    10
tb_rb_torihiki_meisai_tekiyo1   239
tb_rb_torihiki_meisai_tekiyo2   74
tb_rb_torihiki_meisai_tmp       317
tb_rb_visadebit_meisai_tmp      123
tb_related_buy_main_item        201
tb_shipping_fixdate     7
tb_soldout_check        3
tb_sudden_sales_diff    61
tb_sudden_sales_diff_new        26
tb_sudden_sales_diff_pre        62
tb_title_parts_target   545
tb_wc_setting   4
tb_workname     3
tmp_tyumon      281
tmp_tyumon2     112
*/

/* 不要なもの（網羅はしていない。メモ程度に）
[スクリプトで再作成]
calendar
[洗い替え更新があるため空で良い]
tb_discount_list (値下げ一覧 一時テーブル)
tb_rakuten_product_stock （楽天（即納のみ）在庫更新CSV 差分確認テーブル）
chouchou_clair_product （シュシュクレール在庫連携 商品テーブル）
chouchou_clair_product_log（同 CSVアップ・ダウン履歴）
tb_picking ピッキングリスト印刷データ
*/

      // 外部提供用 情報マスク
      $logger->info('開発用DB: 外部提供用情報マスク処理');
      $this->hideInformation();
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      $logger->info('開発用DB: 作成完了');

      $logger->info('開発用DB: ダンプ開始');
      $dumpPath = $backupDir . '/' . 'mini_db.sql.gz';

      $options = [];
      $options[] = '-h' . $dbMain->getHost();
      $options[] = '-u' . $username;
      $options[] = '-p' . $password;

      // schemaで作成されたトリガが消えてしまうため、add-drop-tableや create-info は無し。
      $options[] = '--skip-add-drop-table';
      $options[] = '--no-create-info';

      // 権限エラーとなるため、VIEW定義はダンプしない（schemaに出力されているため問題なし）
      $sql = <<<EOD
        SELECT
          TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE table_schema = '${miniDBName}'
          AND TABLE_TYPE = 'VIEW'
          ORDER BY TABLE_NAME
EOD;
      $stmt = $dbMain->query($sql);

      while($viewName = $stmt->fetchColumn(0)) {
        $options[] = sprintf('--ignore-table=%s.%s', $miniDBName, $viewName);
      }

      system(sprintf('mysqldump %s %s | gzip -c > "%s"', implode(' ', $options), $miniDBName, $dumpPath));

      // スキーマ, ストアドファイルも名前を変更してコピー
      system(sprintf('cp %s/%s %s/mini_db.schema.sql', $backupDir, $filenameSchema, $backupDir));
      // ※ストアドファイルは、置換のためのすでに作成済み。

      system(sprintf('cp %s/%s %s/mini_log_db.schema.sql', $backupDir, $filenameLogSchema, $backupDir));


      $logger->info('開発用DB: ダンプ終了 [' . $dumpPath . ']');

      return 0;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      return 1;
    }

  }

  /**
   * 商品売上担当者関連テーブル作成
   */
  private function createProductSalesTable()
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getContainer()->get('doctrine')->getConnection('main');

    $miniDBName = 'test_plusnao_db_mini';

    // tb_team
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`tb_team` (
        `id`
        , `team_name`
        , `delete_flg`
      )
      VALUES
        (1, 'テストチーム1', 0)
      , (2, 'テストチーム2', 0)
      ;
EOD;
      $dbMain->exec($sql);

    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code
      FROM
        `$miniDBName`.tb_mainproducts m
        INNER JOIN `$miniDBName`.tb_sales_detail_profit p
          ON m.daihyo_syohin_code = p.代表商品コード
        LEFT JOIN `$miniDBName`.tb_shoplist_daily_sales s
          ON m.daihyo_syohin_code = s.daihyo_syohin_code
      WHERE
        s.daihyo_syohin_code IS NULL
        AND p.小計_伝票料金加算 > 0
        AND p.明細粗利額_伝票費用除外 > 0
      GROUP BY
        m.daihyo_syohin_code
      ORDER BY
        count(p.受注年月日) DESC
      LIMIT 2;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $codeList = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    $date1 = (new \DateTIme())->modify('-20 days')->format('Y-m-d H:i:s');
    $date2 = (new \DateTIme())->modify('-15 days')->format('Y-m-d H:i:s');
    $date3 = (new \DateTIme())->modify('-10 days')->format('Y-m-d H:i:s');
    $updated = (new \DateTime())->format('Y-m-d H:i:s');

    // tb_shoplist_daily_sales
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`tb_shoplist_daily_sales` (
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
        , `created`
        , `updated`
      )
      VALUES
        (:date1, 'hoge', :code1, 'hoge', 'hogehoge', 1, 1, 0, 800, 0.15, '-', 'ブラック', 0, '2021/10/15 13:30:10', 300, 40.00, :updated, :updated)
      , (:date2, 'huga', :code2, 'huga', 'hugahuga', 1, 1, 0, 2500, 0.55, '-', 'ブルー', 0, '2021/10/15 13:30:10', 1000, 40.00, :updated, :updated)
      , (:date3, 'hoge', :code1, 'hoge', 'hogehoge', 1, 1, 0, 800, 0.15, '-', 'ブラック', 0, '2021/10/15 13:30:10', 300, 40.00, :updated, :updated)
      , (:date3, 'huga', :code2, 'huga', 'hugahuga', 1, 1, 0, 2500, 0.55, '-', 'ブルー', 0, '2021/10/15 13:30:10', 1000, 40.00, :updated, :updated)
      ;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':code1', $codeList[0], \PDO::PARAM_STR);
    $stmt->bindValue(':code2', $codeList[1], \PDO::PARAM_STR);
    $stmt->bindValue(':date1', $date1, \PDO::PARAM_STR);
    $stmt->bindValue(':date2', $date2, \PDO::PARAM_STR);
    $stmt->bindValue(':date3', $date3, \PDO::PARAM_STR);
    $stmt->bindValue(':updated', $updated, \PDO::PARAM_STR);
    $stmt->execute();

    // tb_product_sales_account
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`tb_product_sales_account` (
          `id`
        , `daihyo_syohin_code`
        , `user_id`
        , `team_id`
        , `product_sales_task_id`
        , `status`
        , `work_amount`
        , `apply_start_date`
        , `apply_end_date`
        , `created`
        , `updated`
      )
      VALUES
        (1, :code1, 1, 1, 1, 1, 1.0, :date1, NULL, :updated, :updated)
      , (2, :code1, 1, 1, 2, 1, 1.5, :date2, NULL, :updated, :updated)
      , (3, :code1, 2, 2, 1, 1, 0.8, :date3, NULL, :updated, :updated)
      , (4, :code2, 1, 1, 1, 1, 1.0, :date1, NULL, :updated, :updated)
      , (5, :code2, 2, 1, 1, 1, 1.2, :date2, NULL, :updated, :updated)
      , (6, :code2, 2, 2, 2, 1, 0.6, :date3, NULL, :updated, :updated)
      ;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':code1', $codeList[0], \PDO::PARAM_STR);
    $stmt->bindValue(':code2', $codeList[1], \PDO::PARAM_STR);
    $stmt->bindValue(':date1', $date1, \PDO::PARAM_STR);
    $stmt->bindValue(':date2', $date2, \PDO::PARAM_STR);
    $stmt->bindValue(':date3', $date3, \PDO::PARAM_STR);
    $stmt->bindValue(':updated', $updated, \PDO::PARAM_STR);
    $stmt->execute();

    // tb_product_sales_account_history
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`tb_product_sales_account_history` (
          `id`
        , `process_type`
        , `updated`
        , `update_account_id`
      )
      VALUES
        (1, 1, :updated, 1)
      , (2, 1, :updated, 1)
      , (3, 1, :updated, 2)
      , (4, 1, :updated, 1)
      , (5, 1, :updated, 2)
      , (6, 1, :updated, 2)
      ;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':updated', $updated, \PDO::PARAM_STR);
    $stmt->execute();

    // tb_product_sales_account_history_mapping
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`tb_product_sales_account_history_mapping` (
          `product_sales_account_history_id`
        , `product_sales_account_id`
      ) 
      VALUES 
        (1, 1) , (2, 2) , (3, 3) , (4, 4) , (5, 5) , (6, 6);
EOD;
    $dbMain->exec($sql);

    // tb_product_sales_account_result_history
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`tb_product_sales_account_result_history` (
          `id`
        , `product_sales_account_id`
        , `target_date`
        , `sales_amount`
        , `profit_amount`
        , `shoplist_sales_amount`
        , `shoplist_profit_amount`
        , `stock_quantity`
        , `stock_amount`
        , `remain_quantity`
        , `remain_amount`
      ) 
      VALUES 
        (1, 1, :date1, 1000, 400, 400, 100, 7, 2000, 2, 300)
      , (2, 2, :date1, 1500, 600, 500, 150, 8, 2200, 5, 330)
      , (3, 3, :date1, 800, 300, 300, 80, 8, 2500, 4, 300)
      , (4, 4, :date1, 1000, 300, 400, 100, 6, 2100, 3, 320)
      , (5, 5, :date2, 1200, 400, 500, 120, 5, 1800, 2, 300)
      , (6, 6, :date2, 600, 300, 300, 60, 8, 1900, 3, 340)
      , (7, 1, :date2, 2000, 800, 400, 100, 12, 2000, 6, 400)
      , (8, 2, :date2, 3000, 1200, 500, 150, 11, 1900, 2, 300)
      , (9, 3, :date3, 1600, 600, 300, 80, 7, 1800, 2, 300)
      , (10, 4, :date3, 2000, 800, 400, 100, 10, 2000, 4, 360)
      , (11, 5, :date3, 2400, 1000, 500, 120, 15, 2700, 2, 310)
      , (12, 6, :date3, 1200, 500, 300, 60, 13, 2300, 1, 170)
      ;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':date1', $date1, \PDO::PARAM_STR);
    $stmt->bindValue(':date2', $date2, \PDO::PARAM_STR);
    $stmt->bindValue(':date3', $date3, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 外部提供用に、隠すデータは隠す。
   */
  private function hideInformation()
  {
    $logger = $this->logger;

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getContainer()->get('doctrine')->getConnection('main');

    $devDbName  = 'test_plusnao_db'; // ここは本当に危険（空にされる）なので、あえてベタ書き。
    $miniDBName = 'test_plusnao_db_mini';


    $sql = <<<EOD
UPDATE 
`$miniDBName`.tb_setting s
SET s.setting_val = ''
WHERE s.setting_key = :key
EOD;
    $stmt = $dbMain->prepare($sql);

    // tb_setting
    $logger->info('開発用DB: hide information : tb_setting');
    $keys = [
        'NE_API_ACCESS_TOKEN'
      , 'NE_API_LOGIN_ID'
      , 'NE_API_LOGIN_PASSWORD'
      , 'NE_API_REFRESH_TOKEN'
      , 'PPM_SHOP_LOGIN_ACCOUNT'
      , 'PPM_SHOP_LOGIN_PASSWORD'
      , 'RAKUTEN_GOLD_FTP_PASSWORD'
      , 'RAKUTEN_R_LOGIN_ACCOUNT'
      , 'RAKUTEN_R_LOGIN_PASSWORD'
    ];
    foreach ($keys as $key) {
      $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
      $stmt->execute();
    }

    // symfony_users
    $dbMain->exec("TRUNCATE `$miniDBName`.symfony_users");
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`symfony_users` (
          `id`
        , `username`
        , `password`
        , `email`
        , `is_active`
        , `ne_account`
        , `ne_password`
        , `roles`
        , `warehouse_id`
        , `company_id`
        , `buyer_order`
        , `created_at`
        , `updated_at`
      ) 
      VALUES 
        (1,'テスト管理者','\$2y\$13\$FxK4J892lMxKRxXt6wKVIeHhkJJd3eUmOLc3sLUtJ6NSA/lGG2r72','',-1,'','','ROLE_SYSTEM_ADMIN|ROLE_USER|ROLE_SALES_PRODUCT_ACCOUNT|ROLE_PRODUCT_MANAGEMENT_BROWSING|ROLE_PRODUCT_MANAGEMENT_UPDATING',12, -1, 0,'0000-00-00 00:00:00','2018-07-12 09:03:00')
      , (2,'テストユーザー','\$2y\$13\$WpNQhJ.ORVRDuc7mV2wL2OYIM4qca43sLH9ZmSHSydlQBF5AbuSsa','',-1,'','','ROLE_USER|ROLE_SALES_PRODUCT_ACCOUNT',12, -1, 0,'2018-07-12 00:00:00','2018-07-12 00:00:00')
      ;
EOD;
    $dbMain->exec($sql);

    // symfony_user_client
    // ひとまず全削除
    $dbMain->exec("TRUNCATE `$miniDBName`.symfony_user_client");

    // users
    $dbMain->exec("TRUNCATE `$miniDBName`.users");
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`users` (`id`, `report_output`, `created`, `modified`, `username`, `password`, `group_id`, `name`, `prefix`, `default_make_group_id`, `users_status_names`, `auth_token`, `auth_token_expires`) VALUES (1, 0, '2011-04-25 04:11:12', '2012-03-27 15:39:17', 'admin', '30eaec8a5b77ca84d8bd938a97fec5640b1d6951', 1, '管理者', 'admin', 9, '0', NULL, NULL);
EOD;
    $dbMain->exec($sql);

    // users_addinfos
    $dbMain->exec("TRUNCATE `$miniDBName`.users_addinfos");
    $sql = <<<EOD
      INSERT INTO `$miniDBName`.`users_addinfos` (`id`, `created`, `modified`, `tel`, `tel_terminal`, `tel_sub`, `tel_sub_terminal`, `tel_emergency`, `tel_emergency_name`, `postcode`, `address`, `email`, `email_sub`) VALUES (1, '2011-04-30 01:38:33', '2011-05-04 16:59:43', '000-0000-0000', '', '', '', '', '', '100-0001', '東京都千代田区', 'workuser.forest@gmail.com', '');
EOD;
    $dbMain->exec($sql);

    // users_salaries
    $dbMain->exec("TRUNCATE `$miniDBName`.users_salaries");

    // tb_sales_detail
    $sql = <<<EOD
      UPDATE `$miniDBName`.`tb_sales_detail` d
      SET  
          `発送伝票番号` = CONCAT('00000000', RIGHT(d.伝票番号, 4))
        , `受注担当者` = '担当者'
        , `作業用欄`    = NULL
        , `発送伝票備考欄` = NULL
        , `ピッキング指示` = NULL
        , `納品書特記事項` = NULL
        , `備考` = NULL
        , `購入者名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4))
        , `購入者カナ` = CONCAT('プラス タロウ', RIGHT(d.伝票番号, 4))
        , `購入者電話番号` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
        , `購入者郵便番号` = CONCAT(LEFT(`購入者郵便番号`, 3), '0000')
        , `購入者住所1` = LEFT(`購入者住所1`, 8)
        , `購入者住所2` = LEFT(`購入者住所2`, 2)
        , `購入者（住所1+住所2）` = CONCAT(LEFT(`購入者住所1`, 8), ' ', LEFT(`購入者住所2`, 2))
        , `購入者メールアドレス` = CONCAT('workuser.forest+', RIGHT(d.伝票番号, 4), '@gmail.com')
        , `送り先名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4)) 
        , `送り先カナ` = CONCAT('プラス タロウ', RIGHT(d.伝票番号, 4))
        , `送り先電話番号` = CONCAT('0000-01-', RIGHT(d.伝票番号, 4))
        , `送り先郵便番号` = CONCAT(LEFT(`送り先郵便番号`, 3), '0000')
        , `送り先住所1` = LEFT(`送り先住所1`, 8)
        , `送り先住所2` = LEFT(`送り先住所2`, 2)
        , `送り先（住所1+住所2）` = CONCAT(LEFT(`送り先住所1`, 8), ' ', LEFT(`送り先住所2`, 2))
      ;      
EOD;
    $dbMain->exec($sql);

    // tb_sales_detail_analyze
    $sql = <<<EOD
      UPDATE `$miniDBName`.`tb_sales_detail_analyze` d
      SET  
          `発送伝票番号` = CONCAT('00000000', RIGHT(d.伝票番号, 4))
        , `発送伝票備考欄` = NULL
        , `ピッキング指示` = NULL
        , `納品書特記事項` = NULL
        , `購入者名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4))
        , `購入者電話番号` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
      ;      
EOD;
    $dbMain->exec($sql);

    // tb_shipping_voucher_detail
    $sql = <<<EOD
      UPDATE `$miniDBName`.`tb_shipping_voucher_detail` d
      SET
          `名義人` = ''
        , `有効期限` = ''
        , `承認番号` = ''
        , `購入者名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4))
        , `購入者カナ` = CONCAT('プラス タロウ', RIGHT(d.伝票番号, 4))
        , `購入者郵便番号` = CONCAT(LEFT(`購入者郵便番号`, 3), '0000')
        , `購入者住所1` = LEFT(`購入者住所1`, 8)
        , `購入者住所2` = LEFT(`購入者住所2`, 2)
        , `購入者電話番号` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
        , `購入者ＦＡＸ` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
        , `購入者メールアドレス` = CONCAT('workuser.forest+', RIGHT(d.伝票番号, 4), '@gmail.com')
        , `発送先名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4))
        , `発送先カナ` = CONCAT('プラス タロウ', RIGHT(d.伝票番号, 4))
        , `発送先郵便番号` = CONCAT(LEFT(`発送先郵便番号`, 3), '0000')
        , `発送先住所1` = LEFT(`発送先住所1`, 8)
        , `発送先住所2` = LEFT(`発送先住所2`, 2)
        , `発送先電話番号` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
        , `発送先ＦＡＸ` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
      ;
EOD;
    $dbMain->exec($sql);

    // tb_sales_detail_voucher
    $sql = <<<EOD
      UPDATE `$miniDBName`.`tb_sales_detail_voucher` d
      SET  
          `購入者名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4))
        , `購入者電話番号` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
      ;      
EOD;
    $dbMain->exec($sql);

    // tb_sales_detail_buycount
    $sql = <<<EOD
      UPDATE `$miniDBName`.`tb_sales_detail_buycount` d
      SET  
          `購入者名` = CONCAT('ぷらす 太郎', RIGHT(d.伝票番号, 4))
        , `購入者電話番号` = CONCAT('0000-00-', RIGHT(d.伝票番号, 4))
      ;      
EOD;
    $dbMain->exec($sql);
  }


}
