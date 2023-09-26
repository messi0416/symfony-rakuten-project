<?php
/**
 * バッチ処理 WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TaobaoCheckStatuses;

class WebCheckTaobaoScrapingUpdateProductStatusCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:web-check-taobao-scraping-update-product-status')
      ->setDescription('WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-date', null, InputOption::VALUE_OPTIONAL, '対象日指定 YYYY-mm-dd')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理を開始しました。');

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

      $logExecTitle = sprintf('WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      $targetDate = new \DateTimeImmutable();
      if ($input->getOption('target-date')) {
        $targetDate = new \DateTimeImmutable($input->getOption('target-date'));
      }

      // 購入可能SKU数の更新（全商品）
      $this->results['購入可能SKU数更新'] = $this->updateVendorAddressSetAfter();

      // 在庫更新フラグ
      $this->results['在庫更新'] = $this->updateProductStatusSku($targetDate);

      // 価格更新フラグ
      $this->results['価格更新'] = $this->updateProductStatusPrice($targetDate);

      // 全修正フラグ
      $this->results['全修正'] = $this->updateProductStatusAll($targetDate);

      // 商品売切れ
      $this->results['商品売切れ'] = $this->updateProductStatusSoldOut($targetDate);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理 エラー', 'WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映処理 でエラーが発生しました。', 'error'
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

    $statuses = [
        TaobaoCheckStatuses::CHANGE_TYPE_SKU_SOLDOUT
      , TaobaoCheckStatuses::CHANGE_TYPE_SKU_ADDED
      , TaobaoCheckStatuses::CHANGE_TYPE_NAME_CHANGED
    ];

    $targetStatuses = implode(', ', $statuses);

    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT p.orig_url
        FROM tb_taobao_change_logs pl
        INNER JOIN tb_taobao_products p ON pl.product_id = p.id
        WHERE pl.created_at >= :dateStart
          AND pl.created_at < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.orig_url
      SET m.`在庫変動チェックフラグ` = -1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->rowCount();
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
      TaobaoCheckStatuses::CHANGE_TYPE_PRICE_CHANGED
    ];

    $targetStatuses = implode(', ', $statuses);

    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT p.orig_url
        FROM tb_taobao_change_logs pl
        INNER JOIN tb_taobao_products p ON pl.product_id = p.id
        WHERE pl.created_at >= :dateStart
          AND pl.created_at < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.orig_url
      SET m.`価格変更チェック` = -1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
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
        TaobaoCheckStatuses::CHANGE_TYPE_DELETED
      , TaobaoCheckStatuses::CHANGE_TYPE_SOLDOUT
    ];

    $targetStatuses = implode(', ', $statuses);

    // 全SKUを受発注不可へ更新 => 誤判定が多いため、在庫変動チェックフラグのONのみにとどめる。
    // → これはWEBチェッカーの反映確認->反映処理で、setbefore, setafter を元に行われる。ので、不要。
    // → つまりは何もしない。
//    $sql = <<<EOD
//      UPDATE tb_mainproducts m
//      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
//      INNER JOIN (
//        SELECT
//          DISTINCT p.orig_url
//        FROM tb_taobao_change_logs pl
//        INNER JOIN tb_taobao_products p ON pl.product_id = p.id
//        WHERE pl.created_at >= :dateStart
//          AND pl.created_at < :dateEnd
//          AND pl.change_type IN ( $targetStatuses )
//      ) T ON va.sire_adress = T.orig_url
//      INNER JOIN (
//        SELECT
//            va.daihyo_syohin_code
//          , COUNT(*) AS address_count
//        FROM tb_vendoraddress va
//        WHERE va.`stop` = 0
//        GROUP BY va.daihyo_syohin_code
//        HAVING address_count = 1
//      ) VA_NUM ON m.daihyo_syohin_code = VA_NUM.daihyo_syohin_code
//      SET m.`在庫変動チェックフラグ` = -1
//EOD;
    // 件数だけ取得しておく
    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code
      FROM tb_mainproducts m
      INNER JOIN tb_vendoraddress va ON m.daihyo_syohin_code = va.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT p.orig_url
        FROM tb_taobao_change_logs pl
        INNER JOIN tb_taobao_products p ON pl.product_id = p.id
        WHERE pl.created_at >= :dateStart
          AND pl.created_at < :dateEnd
          AND pl.change_type IN ( $targetStatuses )
      ) T ON va.sire_adress = T.orig_url
      INNER JOIN (
        SELECT
            va.daihyo_syohin_code
          , COUNT(*) AS address_count
        FROM tb_vendoraddress va
        WHERE va.`stop` = 0
        GROUP BY va.daihyo_syohin_code
        HAVING address_count = 1
      ) VA_NUM ON m.daihyo_syohin_code = VA_NUM.daihyo_syohin_code
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dateStart', $date->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':dateEnd', $date->modify('+1 day')->format('Y-m-d'), \PDO::PARAM_STR);
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
            i.orig_url
          , T.*
        FROM tb_taobao_products i
        INNER JOIN (
          SELECT
               product_id
             , COUNT(*) AS sku_num
             , SUM(sku.stock) AS can_book_num
          FROM tb_taobao_variants sku
          WHERE sku.stock > 0
          GROUP BY sku.product_id
        ) T ON i.id = T.product_id
      ) T ON va.sire_adress = T.orig_url
      SET va.setbefore = va.setafter
        , va.setafter = COALESCE(T.sku_num, 0)
        , va.checkdate = NOW()
      WHERE (
            va.sire_adress LIKE '%taobao.com%'
         OR va.sire_adress LIKE '%tmall.com%'
      )
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    return $stmt->rowCount();
  }

}
