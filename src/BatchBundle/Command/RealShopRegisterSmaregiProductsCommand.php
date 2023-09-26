<?php
/**
 * バッチ処理 スマレジ商品一括登録処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\RealShopSmaregiMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RealShopRegisterSmaregiProductsCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:real-shop-register-smaregi-products')
      ->setDescription('スマレジ商品一括登録処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('スマレジ商品一括登録処理を開始しました。');

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

      $commonUtil = $this->getDbCommonUtil();
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $this->results = [
          'message' => null
        , 'code' => []
      ];

      $logExecTitle = sprintf('スマレジ商品一括登録処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /// 商品テーブルのレコードが欠けている商品を補完する。
      $sql = <<<EOD
        INSERT INTO tb_real_shop_information (
            daihyo_syohin_code
          , original_price
          , baika_tanka
        )
        SELECT
            m.daihyo_syohin_code
          , -1 AS original_price
          , v.price AS baika_tanka
        FROM tb_mainproducts  m
        INNER JOIN v_product_price_real_shop v ON m.daihyo_syohin_code = v.daihyo_syohin_code
        INNER JOIN (
          SELECT
           DISTINCT s.daihyo_syohin_code
          FROM tb_real_shop_product_stock s
          WHERE s.stock > 0
             OR s.order_num > 0
        ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
        LEFT JOIN tb_real_shop_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        WHERE i.daihyo_syohin_code IS NULL
EOD;
      $dbMain->exec($sql);

      $productCodeList = [];
      $sql = <<<EOD
        SELECT
          s.ne_syohin_syohin_code
        FROM tb_real_shop_product_stock s
        INNER JOIN tb_productchoiceitems pci ON s.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        WHERE s.stock > 0
           OR s.order_num > 0
        ORDER BY s.daihyo_syohin_code, pci.`並び順No`
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      while($code = $stmt->fetchColumn(0)) {
        $productCodeList[] = $code;
      }

      $this->results['code'] = $productCodeList;

      /** @var RealShopSmaregiMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.smaregi');
      $mallProcess->registerProducts($productCodeList);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('スマレジ商品一括登録処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('スマレジ商品一括登録処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('スマレジ商品一括登録処理 エラー', 'スマレジ商品一括登録処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'スマレジ商品一括登録処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


