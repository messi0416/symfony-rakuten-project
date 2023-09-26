<?php
/**
 * 指定した日数分の product_price_log レコードの作成
 * ※開発・テスト用
 */

namespace BatchBundle\Command;

use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;



class DevCreateDummyProductPriceLogCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  int */
  protected $days;

  protected function configure()
  {
    $this
      ->setName('dev:create-dummy-product-price-log')
      ->setDescription('開発用に、商品価格履歴レコードを作成。※--env=prod,dev で実行してはいけない。必ず、--env=test（デフォルト）で実行する。')
      ->addOption('days', null, InputOption::VALUE_OPTIONAL, '作成日数', 7)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $container = $this->getContainer();
    $env = $this->getEnvironment();
    if ($env !== 'test') {
      throw new \RuntimeException('the environment is not "test". aborted.');
    }

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->initLogTimer();
    $this->logger = $logger;

    $this->days = intval($input->getOption('days'));
    if ($this->days < 1) {
      $this->days = 1;
    }

    $currentDate = new \DateTime();
    $currentDate->modify(sprintf('-%d days', $this->days - 1)); // 本日を含めた日数のため、-1
    $currentDate->setTime(0, 0, 0);
    $endDate = new \DateTime();
    $endDate->setTime(0, 0, 0);

    $logger->info(sprintf('開発用 商品価格履歴データを作成します。(%d日分: %s => %s)', $this->days, $currentDate->format('Y-m-d'), $endDate->format('Y-m-d')));

    try {
      $dbMain = $this->getDb('main');

      // 実行する場合には必ず --env=test 状態で実行する
      $logDbName  = 'test_plusnao_log_db'; // ここは本当に危険（テーブルがDROPされる）なので、あえてベタ書き。

      // パーティション作成が日付順にしかできないため、毎回テーブルをドロップ（このために、この処理は危険。テスト環境でのみ実行する。）
      $sql = <<<EOD
          DROP TABLE IF EXISTS `{$logDbName}`.`tb_product_price_log`;
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        CREATE TABLE `{$logDbName}`.`tb_product_price_log` (
          `log_date` DATE NOT NULL COMMENT '日付',
          `daihyo_syohin_code` VARCHAR(30) NOT NULL,
          `sire_code` VARCHAR(10) NOT NULL DEFAULT '',
          `syohin_kbn` VARCHAR(10) NOT NULL DEFAULT '10',
          `genka_tnk` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `送料設定` TINYINT(3) NOT NULL DEFAULT '0',
          `手動ゲリラSALE` TINYINT(3) NOT NULL DEFAULT '0',
          `stockreview` TINYINT(3) NOT NULL DEFAULT '0',
          `productchoiceitems_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `check_price` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `weight` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '重量(g)',
          `additional_cost` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '仕入付加費用',
          `価格非連動チェック` TINYINT(3) NOT NULL DEFAULT '0',
          `価格変更チェック` TINYINT(3) NOT NULL DEFAULT '0',
          `総在庫数` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `総注残数` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `フリー在庫数` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `実勢価格` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `優先表示修正値` INT(10) NOT NULL DEFAULT '0',
          `優先表示順位` INT(10) NOT NULL DEFAULT '0',
          `endofavailability` DATETIME NULL DEFAULT NULL,
          `deliverycode` INT(11) NOT NULL DEFAULT '4',
          `genka_tnk_ave` INT(11) NOT NULL DEFAULT '0',
          `baika_tnk` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `base_baika_tanka` INT(11) NOT NULL DEFAULT '0' COMMENT '算出売価単価 ※値下げ・手動変更なしの自動計算値',
          `cost_tanka` INT(11) NOT NULL DEFAULT '0' COMMENT 'コスト単価\nv_product_priceより取得。(平均仕入れ価格 + 仕入付加費用 + 商品固定費) / (1 - (仕入先費用率(%) / 100)) + 圧縮コスト(圧縮商品でなければ0)。',
          `baika_genka` INT(11) NOT NULL DEFAULT '0' COMMENT '売価原価\nv_product_priceより取得。(基準原価(m.genka_tnk) + 仕入付加費用 + 商品固定費) / (1 - (仕入先費用率(%) / 100)) + 圧縮コスト(圧縮商品でなければ0)\n2020/03追加。それ以前のものはcost_tankaをコピー',
          `profit_rate` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '粗利率\n2020/03、#90989から計算式変更。(baika_tnk - baika_genka) / baika_tnk',
          `sunfactoryset` DATE NOT NULL DEFAULT '0000-00-00' COMMENT '出荷設定日',
          `priority` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `visible_flg` INT(10) UNSIGNED NOT NULL DEFAULT '1',
          `sales_volume` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `makeshop_Registration_flug` TINYINT(1) NOT NULL DEFAULT '0',
          `rakuten_Registration_flug` TINYINT(1) NOT NULL DEFAULT '0',
          `croozmall_Registration_flug` TINYINT(11) NOT NULL DEFAULT '0',
          `amazon_registration_flug` TINYINT(4) NOT NULL DEFAULT '0',
          `annual_sales` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `setnum` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `being_num` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `mall_price_flg` TINYINT(1) NOT NULL DEFAULT '0',
          `maxbuynum` INT(2) NOT NULL DEFAULT '0' COMMENT '最大購入可能数',
          `fixed_cost` INT(11) NOT NULL DEFAULT '0' COMMENT '商品固有固定費',
          `cost_rate` INT(11) NOT NULL DEFAULT '0' COMMENT '商品別原価率',
          `vendor_cost_rate` INT(11) NOT NULL DEFAULT '0' COMMENT '仕入先別原価率',
          `startup_flg` TINYINT(1) NOT NULL DEFAULT '-1' COMMENT '登録直後かどうか',
          `pricedown_flg` TINYINT(1) NOT NULL DEFAULT '-1' COMMENT 'デフォルトで値下げ許可するか否か',
          `red_flg` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '赤字販売フラグ',
          `受発注可能フラグ退避F` TINYINT(1) NOT NULL DEFAULT '0',
          `high_sales_rate_flg` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '高成約率フラグ',
          `bundle_num_average` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '平均同梱数 同一商品の平均同梱数',
          `rakuten_registration_flg` INT(1) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
          `rakuten_original_price` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
          `rakuten_baika_tanka` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '売価単価',
          `rakuten_rakuten_price` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `yahoo_registration_flg` TINYINT(3) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
          `yahoo_registration_flg_adult` TINYINT(1) NOT NULL DEFAULT '-1',
          `yahoo_original_price` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
          `yahoo_baika_tanka` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '売価単価',
          PRIMARY KEY (`log_date`, `daihyo_syohin_code`),
          INDEX `index_vendor` (`sire_code`) USING BTREE,
          INDEX `index_product_price` (`daihyo_syohin_code`, `sire_code`, `genka_tnk`, `additional_cost`, `fixed_cost`, `送料設定`, `genka_tnk_ave`)
        )
        ENGINE=InnoDB
        DEFAULT CHARSET utf8
        COMMENT='日別商品価格履歴'
        PARTITION BY RANGE  COLUMNS(log_date)
        (PARTITION pmax VALUES LESS THAN (MAXVALUE) ENGINE = InnoDB)
EOD;
      $dbMain->query($sql);

      while($endDate >= $currentDate) {

        $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          , sprintf('--date=%s', $currentDate->format('Y-m-d'))
        ];

        $logger->info('開発用 商品価格履歴データ作成 ' . $currentDate->format('Y-m-d'));
        $input = new ArgvInput($commandArgs);
        $output = new ConsoleOutput();

        $command = $this->getContainer()->get('batch.log_product_price');
        $exitCode = $command->run($input, $output);
        if ($exitCode != 0) {
          throw new \RuntimeException('失敗しました！');
        }

        $currentDate->modify('+1 days');
      }

      $logger->info('開発用 商品価格履歴データ作成終了。');

      return 0;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      return 1;
    }
  }

}
