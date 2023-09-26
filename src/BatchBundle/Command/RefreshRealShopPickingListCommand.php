<?php
/**
 * バッチ処理 実店舗ピッキングリスト更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\TbRealShopPickingListRepository;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshRealShopPickingListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:refresh-real-shop-picking-list')
      ->setDescription('実店舗ピッキングリスト更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('実店舗ピッキングリスト更新処理を開始しました。');

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
      ];

      $logExecTitle = sprintf('実店舗ピッキングリスト更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var TbRealShopPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');
      $repo->refreshRealShopPickingList();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('実店舗ピッキングリスト更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('実店舗ピッキングリスト更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('実店舗ピッキングリスト更新処理 エラー', '実店舗ピッキングリスト更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '実店舗ピッキングリスト更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


