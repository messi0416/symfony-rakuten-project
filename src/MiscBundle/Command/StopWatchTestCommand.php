<?php
/**
 * ストップウォッチテスト処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use forestlib\AlibabaSdk\Plusnao\Facade\OfferFacade;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\StopWatchUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopWatchTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:stop-watch-test')
      ->setDescription('ストップウォッチテスト')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('ストップウォッチテストを開始しました。');

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

      /** @var StopWatchUtil $watch */
      $watch = $this->getContainer()->get('misc.util.stop_watch');

      $watch->start();

      sleep(1);
      $logger->info('1 秒 => ' . $watch->now());
      sleep(2);
      $logger->info('3 秒 => ' . $watch->now());
      sleep(5);
      $logger->info('8 秒 => ' . $watch->now());

      $logger->info('8 秒(終了) => ' . $watch->stop());
      sleep(1);
      $logger->info('9 秒(停止中) => ' . $watch->now());
      sleep(1);
      $logger->info('10 秒(停止中) => ' . $watch->now());

      $watch->start();
      $logger->info('start: => ' . $watch->now());

      sleep(1);
      $logger->info('11 秒(再開で9秒) => ' . $watch->now());

      $watch->reset();
      $logger->info('reset => ' . $watch->now());

      $watch->start();
      sleep(1);
      $logger->info('1 秒 => ' . $watch->now());

      $logger->info('ストップウォッチテストを完了しました。');

    } catch (\Exception $e) {

      $logger->error('ストップウォッチテスト エラー:' . $e->getMessage());

      return 1;
    }

    return 0;

  }
}


