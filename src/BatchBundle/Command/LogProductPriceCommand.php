<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class LogProductPriceCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('batch:log-product-price')
      ->setDescription('毎日の商品価格 保存バッチ処理')
      ->addOption('date', null, InputOption::VALUE_OPTIONAL, '日付指定 yyyy-mm-dd', null)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();

    $logExecTitle = 'バッチ:商品価格履歴 保存処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始', '', '', 'BatchSV01:CRON'));

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $mainDbName = $dbMain->getDatabase();

      /** @var \Doctrine\DBAL\Connection $db */
      $db = $this->getDb('log');

      if ($input->getOption('date')) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $input->getOption('date'))) {
          $logDate = new \DateTime($input->getOption('date'));
        } else {
          throw new \RuntimeException('日付指定の書式が正しくありません。 ' . $input->getOption('date'));
        }
      } else {
        $logDate = new \DateTime();
      }

      // パーティション存在確認
      $logTableName = 'tb_product_price_log';
      $partitionName = sprintf('p%s', $logDate->format('Ym'));

      $sql  = " SELECT ";
      $sql .= "     TABLE_SCHEMA ";
      $sql .= "   , TABLE_NAME ";
      $sql .= "   , PARTITION_NAME ";
      $sql .= "   , PARTITION_ORDINAL_POSITION ";
      $sql .= "   , TABLE_ROWS  ";
      $sql .= " FROM ";
      $sql .= "   INFORMATION_SCHEMA.PARTITIONS  ";
      $sql .= " WHERE TABLE_NAME = :tableName  ";
      $sql .= "   AND PARTITION_NAME = :partitionName ";

      $stmt = $db->prepare($sql);
      $stmt->execute([
          ':tableName' => $logTableName
        , ':partitionName' => $partitionName
      ]);
      $partition = $stmt->fetch();

      // 該当月パーティション作成
      if (empty($partition)) {
        $sql  = " ALTER TABLE `{$logTableName}` REORGANIZE PARTITION pmax INTO ( ";
        $sql .= "     PARTITION `{$partitionName}` VALUES LESS THAN (:limitDate), ";
        $sql .= "     PARTITION pmax VALUES LESS THAN MAXVALUE ";
        $sql .= " ); ";

        $partitionDate = new \DateTime($logDate->format('Y-m-t 00:00:00'));
        $partitionDate->modify('+1 day');
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limitDate', $partitionDate->format('Y-m-d 00:00:00'));
        $stmt->execute();
      }

      // 処理前件数取得
      $stmt = $db->query("SELECT COUNT(*) FROM tb_product_price_log");
      $preCount = $stmt->fetchColumn(0);

      $sql  = " INSERT IGNORE INTO tb_product_price_log ( ";
      $sql .= "       log_date ";
      $sql .= "     , daihyo_syohin_code ";
      $sql .= "     , sire_code ";
      $sql .= "     , syohin_kbn ";
      $sql .= "     , genka_tnk ";
      $sql .= "     , 送料設定 ";
      $sql .= "     , 手動ゲリラSALE ";
      $sql .= "     , stockreview ";
      $sql .= "     , productchoiceitems_count ";
      $sql .= "     , check_price ";
      $sql .= "     , weight ";
      $sql .= "     , additional_cost ";
      $sql .= "     , 価格非連動チェック ";
      $sql .= "     , 価格変更チェック ";
      $sql .= "     , 総在庫数 ";
      $sql .= "     , 総注残数 ";
      $sql .= "     , フリー在庫数 ";
      $sql .= "     , not_asset_stock ";
      $sql .= "     , 実勢価格 ";
      $sql .= "     , 優先表示修正値 ";
      $sql .= "     , 優先表示順位 ";
      $sql .= "     , endofavailability ";
      $sql .= "     , deliverycode ";
      $sql .= "     , genka_tnk_ave ";
      $sql .= "     , baika_tnk ";
      $sql .= "     , base_baika_tanka ";
      $sql .= "     , cost_tanka ";
      $sql .= "     , baika_genka ";
      $sql .= "     , profit_rate ";
      $sql .= "     , sunfactoryset ";
      $sql .= "     , priority ";
      $sql .= "     , visible_flg ";
      $sql .= "     , sales_volume ";
      $sql .= "     , makeshop_Registration_flug ";
      $sql .= "     , rakuten_Registration_flug ";
      $sql .= "     , croozmall_Registration_flug ";
      $sql .= "     , amazon_registration_flug ";
      $sql .= "     , annual_sales ";
      $sql .= "     , setnum ";
      $sql .= "     , being_num ";
      $sql .= "     , mall_price_flg ";
      $sql .= "     , maxbuynum ";
      $sql .= "     , fixed_cost ";
      $sql .= "     , cost_rate ";
      $sql .= "     , vendor_cost_rate ";
      $sql .= "     , startup_flg ";
      $sql .= "     , pricedown_flg ";
      $sql .= "     , red_flg ";
      $sql .= "     , 受発注可能フラグ退避F ";
      $sql .= "     , high_sales_rate_flg  ";
      $sql .= "     , bundle_num_average  ";
      $sql .= "     , rakuten_registration_flg ";
      $sql .= "     , rakuten_original_price ";
      $sql .= "     , rakuten_baika_tanka ";
      $sql .= "     , rakuten_rakuten_price ";
      $sql .= "     , yahoo_registration_flg ";
      $sql .= "     , yahoo_registration_flg_adult ";
      $sql .= "     , yahoo_original_price ";
      $sql .= "     , yahoo_baika_tanka   ";
      $sql .= " )  ";
      $sql .= " SELECT  ";
      $sql .= "       :logDate AS log_Date ";
      $sql .= "     , COALESCE(m.daihyo_syohin_code, '') ";
      $sql .= "     , COALESCE(m.sire_code, '') ";
      $sql .= "     , COALESCE(m.syohin_kbn, '')  ";
      $sql .= "     , COALESCE(m.genka_tnk, 0) ";
      $sql .= "     , COALESCE(m.送料設定, 0) ";
      $sql .= "     , COALESCE(m.手動ゲリラSALE, 0) ";
      $sql .= "     , COALESCE(m.stockreview, 0)  ";
      $sql .= "     , COALESCE(m.productchoiceitems_count, 0)  ";
      $sql .= "     , COALESCE(m.check_price, 0)  ";
      $sql .= "     , COALESCE(m.weight, 0)  ";
      $sql .= "     , COALESCE(m.additional_cost, 0)  ";
      $sql .= "     , COALESCE(m.価格非連動チェック, 0)  ";
      $sql .= "     , COALESCE(m.価格変更チェック, 0)  ";
      $sql .= "     , COALESCE(T.総在庫数, 0)  ";
      $sql .= "     , COALESCE(T.発注残数, 0)  ";
      $sql .= "     , COALESCE(m.総在庫数, 0) AS フリー在庫数 ";
      $sql .= "     , COALESCE(NA.not_asset_stock, 0) AS not_asset_stock ";
      $sql .= "     , COALESCE(m.実勢価格, 0) ";
      $sql .= "     , COALESCE(m.優先表示修正値, 0)  ";
      $sql .= "     , COALESCE(m.優先表示順位, 0)  ";

      $sql .= "     , COALESCE(cal.endofavailability, 0)  ";
      $sql .= "     , COALESCE(cal.deliverycode, 0)  ";
      $sql .= "     , COALESCE(cal.genka_tnk_ave, 0)  ";
      $sql .= "     , COALESCE(cal.baika_tnk, 0)  ";
      $sql .= "     , COALESCE(base_baika_tanka, 0) ";
      $sql .= "     , COALESCE(cal.cost_tanka, 0) ";
      $sql .= "     , COALESCE(pp.baika_genka, 0) ";
      $sql .= "     , COALESCE(profit_rate, 0) ";
      $sql .= "     , COALESCE(cal.sunfactoryset, '0000-00-00')  ";
      $sql .= "     , COALESCE(cal.priority, 0)  ";
      $sql .= "     , COALESCE(cal.visible_flg, 0)  ";
      $sql .= "     , COALESCE(cal.sales_volume, 0) ";
      $sql .= "     , COALESCE(cal.makeshop_Registration_flug, 0)  ";
      $sql .= "     , CASE WHEN ir.warehouse_stored_flg = 0 THEN -1 ELSE 0 END as rakuten_registration_flug"; // 2021/12より処理変更　倉庫フラグ0のものを楽天出品中とする
      $sql .= "     , COALESCE(cal.croozmall_Registration_flug, 0)  ";
      $sql .= "     , COALESCE(cal.amazon_registration_flug, 0)  ";
      $sql .= "     , COALESCE(cal.annual_sales, 0)  ";
      $sql .= "     , COALESCE(cal.setnum, 0) ";
      $sql .= "     , COALESCE(cal.being_num, 0) ";
      $sql .= "     , COALESCE(cal.mall_price_flg, 0)  ";
      $sql .= "     , COALESCE(cal.maxbuynum, 0)  ";
      $sql .= "     , COALESCE(cal.fixed_cost, 0)  ";
      $sql .= "     , COALESCE(cal.cost_rate, 0)  ";
      $sql .= "     , COALESCE(v.cost_rate, 0)  ";
      $sql .= "     , COALESCE(cal.startup_flg, 0)  ";
      $sql .= "     , COALESCE(cal.pricedown_flg, 0)  ";
      $sql .= "     , COALESCE(cal.red_flg, 0)  ";
      $sql .= "     , COALESCE(cal.受発注可能フラグ退避F, 0)  ";
      $sql .= "     , COALESCE(cal.high_sales_rate_flg, 0) ";
      $sql .= "     , COALESCE(cal.bundle_num_average, 0) ";

      $sql .= "     , COALESCE(ir.registration_flg, 0) AS rakuten_registration_flg ";
      $sql .= "     , COALESCE(ir.original_price, 0)  AS rakuten_original_price ";
      $sql .= "     , COALESCE(ir.baika_tanka, 0) AS rakuten_baika_tanka ";
      $sql .= "     , COALESCE(ir.rakuten_price, 0)  AS rakuten_rakuten_price ";
      $sql .= "     , COALESCE(iy.registration_flg, 0) AS yahoo_registration_flg  ";
      $sql .= "     , COALESCE(iy.registration_flg_adult, 0)  AS yahoo_registration_flg_adult ";
      $sql .= "     , COALESCE(iy.original_price, 0) AS yahoo_original_price ";
      $sql .= "     , COALESCE(iy.baika_tanka, 0) AS yahoo_baika_tanka ";

      $sql .= " FROM `{$mainDbName}`.tb_mainproducts m  ";
      $sql .= " INNER JOIN `{$mainDbName}`.tb_mainproducts_cal cal  ON m.daihyo_syohin_code = cal.daihyo_syohin_code  ";
      $sql .= " INNER JOIN `{$mainDbName}`.tb_vendormasterdata v    ON m.sire_code          = v.sire_code  ";
      $sql .= " INNER JOIN `{$mainDbName}`.tb_rakuteninformation ir ON m.daihyo_syohin_code = ir.daihyo_syohin_code  ";
      $sql .= " INNER JOIN `{$mainDbName}`.tb_yahoo_information iy  ON m.daihyo_syohin_code = iy.daihyo_syohin_code  ";
      $sql .= " LEFT JOIN `{$mainDbName}`.v_product_price pp ON m.daihyo_syohin_code = pp.daihyo_syohin_code  ";
      $sql .= <<<EOD
        LEFT JOIN (
          SELECT
              pci.daihyo_syohin_code
            , SUM(pci.総在庫数) AS 総在庫数
            , SUM(pci.発注残数) AS 発注残数
          FROM `{$mainDbName}`.tb_productchoiceitems pci
          WHERE pci.総在庫数 > 0 OR pci.発注残数 > 0
          GROUP BY pci.daihyo_syohin_code
        ) AS T ON m.daihyo_syohin_code = T.daihyo_syohin_code
        LEFT JOIN (
          -- 資産計上しない仮想在庫のある商品はほとんどない見込みなので、倉庫側から集計していくほうが早いと考え、二重サブクエリ　預かりが大幅に増えたら見直し
          SELECT 
              pci.daihyo_syohin_code
            , SUM(not_asset_stock) not_asset_stock
          FROM (
            SELECT 
                pl.ne_syohin_syohin_code
              , SUM(pl.stock) AS not_asset_stock
            FROM `{$mainDbName}`.tb_warehouse w
            JOIN `{$mainDbName}`.tb_location l ON w.id = l.warehouse_id
            JOIN `{$mainDbName}`.tb_product_location pl ON l.id = pl.location_id
            WHERE w.asset_flg = 0
            GROUP BY pl.ne_syohin_syohin_code
          ) NAP 
          JOIN `{$mainDbName}`.tb_productchoiceitems pci ON pci.ne_syohin_syohin_code = NAP.ne_syohin_syohin_code
          GROUP BY pci.daihyo_syohin_code
        ) AS NA ON NA.daihyo_syohin_code = m.daihyo_syohin_code
EOD;
      $sql .= " ORDER BY m.daihyo_syohin_code  ";

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':logDate', $logDate->format('Y-m-d'));
      $stmt->execute();

      // 処理後件数取得 ＆ チェック ＆ ログ保存
      $stmt = $db->query("SELECT COUNT(*) FROM tb_product_price_log");
      $postCount = $stmt->fetchColumn(0);
      $info = sprintf('%s 件 => %s 件 (保存: %s 件)'
                , number_format($preCount)
                , number_format($postCount)
                , number_format($postCount - $preCount)
      );

      $logger->addDbLog($logger->makeDbLog(
                          $logExecTitle
                        , $logExecTitle
                        , '終了'
                        , ''
                        , ''
                        , 'BatchSV01:CRON'
                        )->setInformation($info));

      $logger->info('[daily] batch:log-product-price DONE.');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage()), 1, $logExecTitle . 'に失敗しました。', 'error');
      return 1;
    }
  }

}
