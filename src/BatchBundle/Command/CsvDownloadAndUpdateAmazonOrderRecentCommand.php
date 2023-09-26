<?php
/**
 * Amazon注文情報 更新処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class CsvDownloadAndUpdateAmazonOrderRecentCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  const TARGET_SHOP = AmazonMallProcess::SHOP_NAME_VOGUE;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-amazon-order-recent')
      ->setDescription('Amazon MWS から注文レポートをダウンロードし、Amazon注文情報を更新する')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('Amazon注文情報の更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = 'Amazon注文情報更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      $saveDir = null;

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述

      // ====================================================
      // 注文データ更新（注文情報 ダウンロード ＆ データ更新）
      // ====================================================
      $startDate = new \DateTime();
      $startDate->modify('-14 day')->setTime(0, 0, 0);
      $endDate = new \DateTime();
      $endDate->setTime(0, 0, 0);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'Amazon注文情報更新処理', '開始', sprintf('%s ～ %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d'))));
      $mallProcess->updateOrder(AmazonMallProcess::SHOP_NAME_VOGUE, $startDate, $endDate);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'Amazon注文情報更新処理', '終了'));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Amazon注文情報の更新処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
