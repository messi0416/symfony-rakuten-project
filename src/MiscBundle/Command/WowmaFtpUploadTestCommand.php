<?php
/**
 * バッチ処理 Wowma FTP アップロードテスト処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\WowmaMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WowmaFtpUploadTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:wowma-ftp-upload-test')
      ->setDescription('Wowma FTP アップロードテスト処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('localPath', InputArgument::REQUIRED, 'from path')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Wowma FTP アップロードテスト処理を開始しました。');

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

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('Wowma FTP アップロードテスト処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      // do something
      /** @var WowmaMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.wowma');
      $processor->enqueueUploadCsv($input->getArgument('localPath'), 'item_stock.zip', $this->getEnvironment(), 'Wowma CSVアップロードテスト', ($this->account ? $this->account->getId() : null));

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Wowma FTP アップロードテスト処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Wowma FTP アップロードテスト処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Wowma FTP アップロードテスト処理 エラー', 'Wowma FTP アップロードテスト処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Wowma FTP アップロードテスト処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


