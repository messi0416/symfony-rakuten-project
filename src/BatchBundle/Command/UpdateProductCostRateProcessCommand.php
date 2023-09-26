<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class UpdateProductCostRateProcessCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:update-product-cost-rate-process')
      ->setDescription('商品別原価率更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('商品別原価率更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = '商品別原価率更新';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
    $logger->setExecTitle($logExecTitle);

    try {

      $dbMain = $this->getDb('main');

      // 設定は、前回の再計算のものを利用しここでは更新しない。
      // ※Access流に、画面に表示されているもの（= 一時テーブルの内容）で更新

      $dbMain->beginTransaction(); // トランザクション

      // -------------------------------------
      // 一時テーブルに登録されていない商品 更新
      // -------------------------------------
      $logger->info('対象外商品更新');
      $logger->addDbLog($logger->makeDbLog(null, '対象外商品更新'));
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal cal
        LEFT JOIN tb_product_cost_rate_list p ON (cal.daihyo_syohin_code = p.daihyo_syohin_code)
        SET cal.cost_rate = 0
        WHERE cal.cost_rate > 0;
EOD;
      $dbMain->query($sql);

      // -------------------------------------
      // 一時テーブルに登録されている商品 更新
      // -------------------------------------
      $logger->info('対象商品更新');
      $logger->addDbLog($logger->makeDbLog(null, '対象商品更新'));

      $sql = <<<EOD
        UPDATE tb_mainproducts_cal cal
        INNER JOIN tb_product_cost_rate_list p ON (cal.daihyo_syohin_code = p.daihyo_syohin_code)
        SET cal.cost_rate = p.cost_rate_after
EOD;
      $dbMain->query($sql);

      $dbMain->commit();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('商品別原価率更新処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "商品別原価率更新処理でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
