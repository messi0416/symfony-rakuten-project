<?php
/**
 * tb_sales_detail_analyze 再生成処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Misc20161011UpdateSalesDetailAnalyzeCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:20161011-update-sales-detail-analyze')
      ->setDescription('tb_sales_detail_analyze 再生成処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('tb_sales_detail_analyze 再生成処理を開始しました。');

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

      /** @var NextEngineMallProcess $neMallProcess */
      $neMallProcess = $container->get('batch.mall_process.next_engine');

      $neMallProcess->updateSalesDetailAnalyze('all');

      $logger->info('tb_sales_detail_analyze 再生成処理を終了しました。');

    } catch (\Exception $e) {

      $logger->error('tb_sales_detail_analyze 再生成処理 エラー:' . $e->getMessage());
      /*
      $logger->addDbLog(
        $logger->makeDbLog('tb_sales_detail_analyze 再生成処理 エラー', 'tb_sales_detail_analyze 再生成処理 エラー', 'エラー終了')->setInformation($e->getMessage())->setLogLevel(TbLog::DEBUG)
        , true, 'tb_sales_detail_analyze 再生成処理 でエラーが発生しました。', 'error'
      );
      */

      throw $e;
    }

    return 0;

  }
}


