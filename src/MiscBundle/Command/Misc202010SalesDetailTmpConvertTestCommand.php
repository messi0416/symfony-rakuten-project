<?php
namespace MiscBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;

class Misc202010SalesDetailTmpConvertTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
    ->setName('misc:202010-sales-detail-tmp-convert-test')
    ->setDescription('受注情報変換テスト')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  /**
   * 元々 tb_sales_detail_tmp にデータが入っていることが前提で、データの加工を実行する
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();
    $logExecTitle = '受注情報一時テーブルの顧客情報加工';

    $logger->info($logExecTitle . 'を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logger->setExecTitle($logExecTitle);
    $logger->addDbLog($logger->makeDbLog(null, '開始'));

    try {
      /** @var NextEngineMallProcess $neMallProcess */
      $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

      $neMallProcess->convertPersonalInfo($logger);
      $logger->addDbLog($logger->makeDbLog(null, '統計情報取得', '終了'));

      $neMallProcess->maskPersonalInfo();
      $logger->addDbLog($logger->makeDbLog(null, 'マスク処理', '終了'));

      $logger->addDbLog($logger->makeDbLog(null, '終了'));
      $logger->info($logExecTitle . 'を終了しました。');
      $logger->logTimerFlush();

    } catch (\Throwable $t) {
      $logger = $this->getLogger();
      $logger->error($logExecTitle . ':' . $t->getMessage() . $t->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了')->setInformation($t->getMessage() . ':' . $t->getTraceAsString())
          , true, $logExecTitle . 'でエラーが発生しました。', 'error'
          );
      $logger->logTimerFlush();
    }
  }
}