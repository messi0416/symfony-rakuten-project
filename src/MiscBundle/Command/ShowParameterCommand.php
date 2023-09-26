<?php
/**
 * バッチ処理 パラメータ取得表示処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowParameterCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:show-parameter')
      ->setDescription('パラメータ取得表示処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('parameter_name', null, InputArgument::REQUIRED, 'パラメータ名')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('パラメータ取得表示処理を開始しました。');

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

      $logExecTitle = sprintf('パラメータ取得表示処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      $param = $this->getContainer()->getParameter($input->getArgument('parameter_name'));

      var_dump(print_r($param, true));
      $logger->info(print_r($param, true));


      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('パラメータ取得表示処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('パラメータ取得表示処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('パラメータ取得表示処理 エラー', 'パラメータ取得表示処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'パラメータ取得表示処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


