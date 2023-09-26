<?php
/**
 * バッチ処理 ロケーション 倉庫へ画面 在庫数更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\TbLocationWarehouseToListRepository;
use MiscBundle\Entity\SymfonyUsers;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshLocationWarehouseToListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:refresh-location-warehouse-to-list')
      ->setDescription('ロケーション 倉庫へ画面 在庫数更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('ロケーション 倉庫へ画面 在庫数更新処理を開始しました。');

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

      $logExecTitle = sprintf('ロケーション 倉庫へ画面 在庫数更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var TbLocationWarehouseToListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToList');
      $repo->refreshWarehouseToList();

      // 「倉庫へ」ピッキングリストの再作成
      $repo->refreshWarehouseToPickingList();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('ロケーション 倉庫へ画面 在庫数更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('ロケーション 倉庫へ画面 在庫数更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('ロケーション 倉庫へ画面 在庫数更新処理 エラー', 'ロケーション 倉庫へ画面 在庫数更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'ロケーション 倉庫へ画面 在庫数更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


