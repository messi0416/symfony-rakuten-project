<?php
namespace MiscBundle\Command;

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

class Misc202010CustomerInfoProcessCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
    ->setName('misc:202010-customer-info-process')
    ->setDescription('既存受注伝票番号の顧客情報加工')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from', null, InputOption::VALUE_OPTIONAL, '開始伝票番号（この値を含む）')
    ->addOption('to', null, InputOption::VALUE_OPTIONAL, '終了伝票番号（この値を含む)')
    ;
  }

  /**
   * 処理フロー
   * (1) 開始番号から伝票情報を、1000件ずつ取得する
   * (2) 加工した情報を、購入者情報テーブルに格納する
   * ※特にトランザクションは必要ないので、伝票番号順に処理を行う事
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();
    $logExecTitle = '既存受注伝票番号の顧客情報加工';

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

      // 処理対象の伝票番号
      $from = $input->getOption('from');
      $to = $input->getOption('to');
      $start_pos = $from ? $from : 0;
      $limit = 1000; // 1回のリミット
      $offset = 0; // 最初は0
      $end_pos = $to ? $to : 99999999; // 適当に大きな値

      // 伝票取得開始　トランザクション管理は行わない
      $offset = 0;
      $sql = 'SELECT distinct 伝票番号 as voucher_number, 購入者電話番号 as tel, 購入者住所1 as address FROM tb_sales_detail ';
      $sql .= 'WHERE 伝票番号 >= :start_pos AND 伝票番号 <= :end_pos ';
      $sql .= 'ORDER BY 伝票番号 ASC ';
      $sql .= 'LIMIT :offset, :limit';

      /** @var EntityManager $em */
      $em = $this->getDoctrine()->getManager('main');
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      // $dbMain->beginTransaction();
      $stmt = $dbMain->prepare($sql);

      /** @var TbPrefectureRepository $prefRepo */
      $prefRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');
      $prefectureMap = $prefRepo->getPrefectureNameMap();

      /** @var TbSalesVoucherCustomerStatisticsInfoRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesVoucherCustomerStatisticsInfo');

      for ($i = 0; $i < 100000; $i++) { // 無限ループ避け
        $stmt->bindValue(':start_pos', $start_pos, \PDO::PARAM_INT);
        $stmt->bindValue(':end_pos', $end_pos, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (! $list) { // 対象データがなくなったら終了
          $logger->debug("対象無しのため終了");
          break;
        }
        foreach ($list as $data) {
          $repo->replaceData($data, $prefectureMap);
        }
        // $dbMain->commit();
        // $dbMain->beginTransaction();
        $offset = $offset + $limit;
      }
      // $dbMain->commit();
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