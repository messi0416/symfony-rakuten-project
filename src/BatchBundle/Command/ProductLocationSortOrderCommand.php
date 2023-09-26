<?php
/**
 * バッチ処理  商品ロケーション自動並べ替え処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProductLocation;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductLocationSortOrderCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:product-location-sort-order')
      ->setDescription('商品ロケーション自動並べ替え処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('command', null, InputOption::VALUE_REQUIRED, '実行コマンド名');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('商品ロケーション自動並べ替え処理を開始しました。');

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

      $logExecTitle = sprintf('商品ロケーション自動並べ替え処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $dbMain = $this->getDb('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // 並べ替え処理
      $repo->sortLocationOrder();

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_LOCATION_SORT_ORDER, ($this->account ? $this->account->getUsername() : 'BatchSV02:CRON'), $actionKey);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('商品ロケーション自動並べ替え処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('商品ロケーション自動並べ替え処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('商品ロケーション自動並べ替え処理 エラー', '商品ロケーション自動並べ替え処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '商品ロケーション自動並べ替え処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


