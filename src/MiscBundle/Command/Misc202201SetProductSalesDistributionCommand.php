<?php

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Service\SetProductSalesDistributionService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * セット商品受注の案分情報作成コマンド。
 *
 * 引数で指定された伝票番号の範囲で、セットアイテムの受注をピックアップし、案分情報 tb_sales_detail_set_distribute_info を生成して格納する。
 * @package BatchBundle\Command
 */
class Misc202201SetProductSalesDistributionCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;
  
  /** @var SymfonyUsers */
  private $account;
  
  protected function configure()
  {
    $this
    ->setName('misc:202201-set-product-sales-distribution')
    ->setDescription('セット商品受注　案分情報作成')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from-no', null, InputOption::VALUE_OPTIONAL, '伝票番号FROM（この番号を含む）')
    ->addOption('to-no', null, InputOption::VALUE_OPTIONAL, '伝票番号TO（この番号を含む）')
    ->addOption('use-tmp', null, InputOption::VALUE_OPTIONAL, 'テンポラリテーブル tb_sales_detail_tmp に入っているデータのみ処理する。デバッグ用。本番では受注明細差分更新で使用されるため使えない', 0)
    ;
  }
  
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logExecTitle = 'セット商品受注　案分情報作成';
    $logger->initLogTimer();
    $logger->info("$logExecTitle 開始");
    
    $fromNumber = $input->getOption('from-no');
    $toNumber = $input->getOption('to-no');
    $useTmp = (bool) $input->getOption('use-tmp');
    
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }
    $logger->addDbLog($logger->makeDbLog($logExecTitle, '開始', "$fromNumber", "$toNumber"));
    $logger->info("$logExecTitle 終了");
    
    try {
      /** @var $service SetProductSalesDistritubionService */
      $service = $this->getContainer()->get('misc.service.set_product_sales_distribution');
      $service->recalcurateSetDistributeInfo($fromNumber, $toNumber, $useTmp);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '終了'));
      
    } catch (\Exception $e) {
      $logger->error($logExecTitle . ':' . $e->getMessage() . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog(null, 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, 'セット商品受注　案分情報作成でエラーが発生しました。', 'error'
        );
      $logger->logTimerFlush();
      return 1;
    }
    return 0;
  }
}