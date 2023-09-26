<?php
/**
 * バッチ処理 WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理
 */

namespace BatchBundle\Command;

use AlibabaCheckStatuses;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WebCheckAlibabaScrapingUpdateProductStatusCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:web-check-alibaba-scraping-update-product-status')
      ->setDescription('WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-date', null, InputOption::VALUE_OPTIONAL, '対象日指定 YYYY-mm-dd')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理を開始しました。');

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
      $this->results = [
          'message' => null
        , '商品売切れ（受発注 全OFF）' => 0
        , '在庫更新' => 0
        , '価格更新' => 0
        , '全修正' => 0
      ];

      $logExecTitle = sprintf('WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      $targetDate = new \DateTimeImmutable();
      if ($input->getOption('target-date')) {
        $targetDate = new \DateTimeImmutable($input->getOption('target-date'));
      }

      // 購入可能SKU数の更新（全商品：最初に行う）
      $this->results['購入可能SKU数更新'] = $this->updateVendorAddressSetAfter();

      // 在庫更新フラグ
      $this->results['在庫更新'] = $this->updateProductStatusSku($targetDate);

      // 価格更新フラグ
      $this->results['価格更新'] = $this->updateProductStatusPrice($targetDate);

      // 全修正フラグ
      $this->results['全修正'] = $this->updateProductStatusAll($targetDate);

      // 商品売切れ
      $this->results['商品売切れ（受発注 全OFF）'] = $this->updateProductStatusSoldOut($targetDate);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理 エラー', 'WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }

  /**
   * 商品フラグ更新処理 「在庫変動チェックフラグ」
   * @param \DateTimeImmutable $date
   * @return int
   */
  private function updateProductStatusSku($date)
  {
    $dbMain = $this->getDb('main');
    $count = 0;

    $statuses = [
        AlibabaCheckStatuses::CHANGE_TYPE_SKU_SOLDOUT
      , AlibabaCheckStatuses::CHANGE_TYPE_SKU_ADDED
      , AlibabaCheckStatuses::CHANGE_TYPE_NAME_CHANGED
    ];

    $targetStatuses = implode(', ', $statuses);

    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :dateStart
          AND pl.check_time < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
          AND pl.amount_before <> 0
      ) T ON va.sire_adress = T.offerurl
      SET m.`在庫変動チェックフラグ` = -1
      WHERE cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    $count += $stmt->rowCount();

    // 商品追加判定でも、過去に巡回履歴があれば復活とみなして在庫変動チェックフラグ ON
    // AlibabaCheckStatuses::CHANGE_TYPE_ADDED
    $statuses = [
      AlibabaCheckStatuses::CHANGE_TYPE_ADDED
    ];

    $targetStatuses = implode(', ', $statuses);

    $sql = <<<EOD
      UPDATE
      tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      /* 「商品追加」ログ */
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :dateStart
          AND pl.check_time < :dateEnd
          AND pl.change_type IN ( {$targetStatuses} )
      ) T ON va.sire_adress = T.offerurl
      /* 過去1ヶ月以内に巡回履歴がある */
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :logCheckDateStart
          AND pl.check_time < :logCheckDateEnd
      ) T2 ON va.sire_adress = T2.offerurl
      SET m.`在庫変動チェックフラグ` = -1
      WHERE va.stop = 0
        AND va.sire_adress IS NOT NULL
        AND va.setafter > 0
        AND cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':logCheckDateStart', $date->modify('-30 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':logCheckDateEnd', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    $count += $stmt->rowCount();

    return $count;
  }

  /**
   * 商品フラグ更新処理 「価格変更チェック」
   * @param \DateTimeImmutable $date
   * @return int
   */
  private function updateProductStatusPrice($date)
  {
    $dbMain = $this->getDb('main');

    $statuses = [
      AlibabaCheckStatuses::CHANGE_TYPE_PRICE_CHANGED
    ];

    $targetStatuses = implode(', ', $statuses);

    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :dateStart
          AND pl.check_time < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.offerurl
      SET m.`価格変更チェック` = -1
      WHERE cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    return $stmt->rowCount();
  }

  /**
   * 商品フラグ更新処理 「バリエーション変更チェック」
   * @param \DateTimeImmutable $date
   * @return int
   */
  private function updateProductStatusAll($date)
  {
    // 現在、対象なし
    return 0;
  }

  /**
   * 商品フラグ更新処理 商品売切れ（受発注 全OFF）
   * @param \DateTimeImmutable $date
   * @return int
   */
  private function updateProductStatusSoldOut($date)
  {
    $dbMain = $this->getDb('main');

    $statuses = [
        AlibabaCheckStatuses::CHANGE_TYPE_DELETED
      , AlibabaCheckStatuses::CHANGE_TYPE_SOLDOUT
    ];

    $targetStatuses = implode(', ', $statuses);

    // 2017/05/09 受発注可能フラグ 強制OFFをやめ。（精度が悪い）
    // 人力チェックに切り替えるため、在庫変動チェックフラグをONにするのみとする。
    // 周りくどくOFFにしてONにする処理になっているが、後付け処理をわかりやすくするため、既存処理には触らずに実装を上乗せする

    // 各チェックフラグがもしついていれば戻す。（人力チェックのスキップ）
    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :dateStart
          AND pl.check_time < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.offerurl
      INNER JOIN (
        SELECT
            va.daihyo_syohin_code
          , COUNT(*) AS address_count
        FROM tb_vendoraddress va
        WHERE va.`stop` = 0
        GROUP BY va.daihyo_syohin_code
        HAVING address_count = 1
      ) VA_NUM ON m.daihyo_syohin_code = VA_NUM.daihyo_syohin_code
      SET m.`在庫変動チェックフラグ` = 0
        , m.`価格変更チェック` = 0
      WHERE cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    /* // 2017/05/09 受発注可能フラグ 強制OFFをやめ。 の実装部分
    // 全SKUを受発注不可へ更新
    $sql = <<<EOD
      UPDATE tb_productchoiceitems pci
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :dateStart
          AND pl.check_time < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.offerurl
      INNER JOIN (
        SELECT
            va.daihyo_syohin_code
          , COUNT(*) AS address_count
        FROM tb_vendoraddress va
        WHERE va.`stop` = 0
        GROUP BY va.daihyo_syohin_code
        HAVING address_count = 1
      ) VA_NUM ON m.daihyo_syohin_code = VA_NUM.daihyo_syohin_code
      SET pci.受発注可能フラグ = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();
    */

    // 2017/05/09 受発注可能フラグ 強制OFFをやめ。 の実装部分
    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT pl.offerurl
        FROM tb_1688_product_change_log pl
        WHERE pl.check_time >= :dateStart
          AND pl.check_time < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.offerurl
      INNER JOIN (
        SELECT
            va.daihyo_syohin_code
          , COUNT(*) AS address_count
        FROM tb_vendoraddress va
        WHERE va.`stop` = 0
        GROUP BY va.daihyo_syohin_code
        HAVING address_count = 1
      ) VA_NUM ON m.daihyo_syohin_code = VA_NUM.daihyo_syohin_code
      SET m.`在庫変動チェックフラグ` = -1
      WHERE cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    return $stmt->rowCount();
  }

  /**
   * 購入可能SKU数の更新（全商品）
   * @return int
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateVendorAddressSetAfter()
  {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      UPDATE
      tb_vendoraddress va
      LEFT JOIN (
        SELECT
            i.offerurl
          , T.*
        FROM tb_1688_good_inform i
        INNER JOIN (
          SELECT
               offerid
             , COUNT(*) AS sku_num
             , SUM(sku.canBookCount) AS can_book_num
          FROM tb_1688_good_sku_detail_inform sku
          WHERE sku.canBookCount > 0
          GROUP BY sku.offerid
        ) T ON i.offerid = T.offerid
      ) T ON va.sire_adress = T.offerurl
      SET va.setbefore = va.setafter
        , va.setafter = COALESCE(T.sku_num, 0)
        , va.checkdate = NOW()
      WHERE va.sire_adress LIKE 'https://detail.1688.com%'
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    return $stmt->rowCount();
  }

}
