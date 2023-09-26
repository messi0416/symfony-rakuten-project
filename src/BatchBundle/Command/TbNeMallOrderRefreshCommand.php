<?php
/**
 * バッチ処理 モール受注リフレッシュ処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TbNeMallOrderRefreshCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:ne-mall-order-refresh')
      ->setDescription('モール受注リフレッシュ処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('モール受注リフレッシュ処理を開始しました。');

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
    
    $em = $this->getDoctrine()->getManager();
    $qb=$em->createQueryBuilder();

    try {
      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('モール受注リフレッシュ処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $date = new \DateTime();
      $date->modify('-2 week');

      $qb
          ->delete('MiscBundle:TbNeMallOrder', 'nmo')
          ->where('nmo.converted < :date')
          ->setparameter('date', $date->format('Y-m-d 00:00:00'))
          ;
      $result = $qb->getQuery()->getResult();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('モール受注リフレッシュ処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('モール受注リフレッシュ処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('モール受注リフレッシュ処理 エラー', 'モール受注リフレッシュ処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'モール受注リフレッシュ処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


