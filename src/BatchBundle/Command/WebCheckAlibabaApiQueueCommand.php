<?php
/**
 * バッチ処理 アリババ(1688.com) 巡回処理 在庫巡回キュー追加
 */

namespace BatchBundle\Command;

use BatchBundle\Job\AlibabaProductCheckJob;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WebCheckAlibabaApiQueueCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  const ACTIVE_LIMIT = 2000;
  const SOLD_OUT_LIMIT = 200;

  protected function configure()
  {
    $this
      ->setName('batch:web-check-alibaba-api-queue')
      ->setDescription('アリババ在庫巡回キュー追加処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('アリババ在庫巡回キュー追加処理を開始しました。');

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
        , 'active' => 0
        , 'soldOut' => 0
      ];

      $logExecTitle = sprintf('アリババ在庫巡回キュー追加処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $dbMain = $this->getDb('main');

      // 販売中 巡回対象取得
      $offerIdsActive = [];
      $sql = <<<EOD
        SELECT DISTINCT
            p.offer_id
        FROM tb_1688_product p
        INNER JOIN tb_vendoraddress va ON p.details_url = va.sire_adress
        WHERE va.stop = 0
          /* AND va.soldout = 0 */
          /* AND (p.offer_status IN ('online', 'outdated')) */
          AND (p.last_checked IS NULL OR p.last_checked < CURRENT_DATE)
        ORDER BY CASE WHEN p.last_checked IS NULL THEN '0000-00-00 00:00:00' ELSE p.last_checked END
               , p.offer_id
        LIMIT :limit
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':limit', self::ACTIVE_LIMIT, \PDO::PARAM_INT);
      $stmt->execute();

      foreach($stmt as $row) {
        $offerIdsActive[] = $row['offer_id'];
      }
      $this->results['active'] = count($offerIdsActive);

      // 販売終了 巡回対象取得 ※いったんまとめてGO
      $offerIdsSoldOut = [];
//      $sql = <<<EOD
//        SELECT DISTINCT
//            p.offer_id
//        FROM tb_1688_product p
//        INNER JOIN tb_vendoraddress va ON p.details_url = va.sire_adress
//        WHERE va.stop = 0
//          AND va.soldout <> 0
//          AND (p.last_checked IS NULL OR p.last_checked < CURRENT_DATE)
//        ORDER BY CASE WHEN p.last_checked IS NULL THEN '0000-00-00 00:00:00' ELSE p.last_checked END
//               , p.offer_id
//        LIMIT :limit
//EOD;
//      $stmt = $dbMain->prepare($sql);
//      $stmt->bindValue(':limit', self::SOLD_OUT_LIMIT, \PDO::PARAM_INT);
//      $stmt->execute();
//
//      foreach($stmt as $row) {
//        $offerIdsSoldOut[] = $row['offer_id'];
//      }
      $this->results['soldOut'] = count($offerIdsSoldOut);


      // キュー追加
      $offerIds = array_merge($offerIdsActive, $offerIdsSoldOut);

      $rescue = $this->getResque();
      foreach($offerIds as $offerId) {
        $job = new AlibabaProductCheckJob();
        $job->queue = 'alibabaApi'; // キュー名
        $job->args = [
          'offerId' => $offerId
        ];

        $rescue->enqueue($job); // リトライなし
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('アリババ在庫巡回キュー追加処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('アリババ在庫巡回キュー追加処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('アリババ在庫巡回キュー追加処理 エラー', 'アリババ在庫巡回キュー追加処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'アリババ在庫巡回キュー追加処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }


}


