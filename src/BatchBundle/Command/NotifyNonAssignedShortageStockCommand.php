<?php
/**
 * バッチ処理 注残欠品未引当通知処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyNonAssignedShortageStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:notify-non-assigned-shortage-stock')
      ->setDescription('注残欠品未引当通知処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logger->info('注残欠品未引当通知処理 開始');

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

      $logExecTitle = sprintf('注残欠品未引当通知処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();

      $dbMain = $this->getDb('main');
      $commonUtil = $this->getDbCommonUtil();

      $now = new \DateTime();

      // 最終更新日時を取得
      $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_NOTIFY_NON_ASSIGNED_SHORTAGE_STOCK);
      if (!$lastUpdated) {
        $lastUpdated = new \DateTime();
        $lastUpdated->modify('-3 day')->setTime(0, 0, 0);
      }
      $logger->info('注残欠品未引当通知処理 最終更新: ' . $lastUpdated->format('Y-m-d H:i:s'));

      $sql = <<<EOD
        SELECT
           i.商品コード
         , SUM(a.`受注数` - a.`引当数`) AS 未引当数
         , i.欠品クリック最終日時
         , GROUP_CONCAT(a.`伝票番号` ORDER BY a.`伝票番号`) AS 伝票番号
        FROM (
            SELECT
               i.商品コード
               , MAX(i.remain_stockout_date) AS 欠品クリック最終日時
            FROM tb_individualorderhistory i
            WHERE i.remain_stockout_date >= :lastUpdated
            GROUP BY i.`商品コード`
        ) i
        INNER JOIN tb_sales_detail_analyze a ON i.`商品コード` = a.`商品コード（伝票）`
        WHERE a.`キャンセル区分` = '0'
          AND a.`明細行キャンセル` = '0'
          AND a.`受注状態` <> '出荷確定済（完了）'
          AND a.`受注数` - a.`引当数` > 0
        GROUP BY i.`商品コード`
        ORDER BY i.`商品コード`
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':lastUpdated', $lastUpdated->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      $stmt->execute();

      $logger->info('注残欠品未引当通知処理 件数: ' . $stmt->rowCount());

      // もし存在すればRedmineチケット作成
      if ($stmt->rowCount()) {
        $container = $this->getContainer();
        $logger->info($container->getParameter('redmine_non_assigned_shortage_stock_ticket'));

        if ($container->getParameter('redmine_non_assigned_shortage_stock_ticket')) { // 本番環境のみ

          $webAccessUtil = $this->getWebAccessUtil();

          $header = sprintf("|_. %s|%s|%s|%s|\n", '商品コード', '未引当数', '欠品クリック最終日時', '伝票番号');
          $items = [];
          foreach ($stmt as $row) {
            $items[] = sprintf("|_. %s|%s|%s|%s|", $row['商品コード'], $row['未引当数'], $row['欠品クリック最終日時'], $row['伝票番号']);
          }

          $ticket = [
            'issue' => [
                'subject' => sprintf('[注残未引当欠品] %d 件', count($items))
              , 'project_id' => $container->getParameter('redmine_non_assigned_shortage_stock_ticket_project')
              , 'priority_id' => $container->getParameter('redmine_non_assigned_shortage_stock_ticket_priority')
              , 'description' => $header . implode("\n", $items)
              , 'assigned_to_id' => $container->getParameter('redmine_non_assigned_shortage_stock_ticket_user')
              , 'tracker_id' => $container->getParameter('redmine_non_assigned_shortage_stock_ticket_tracker')
              // , 'category_id'     => ''
              // , 'status_id'       => ''
            ]
          ];

          $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);

          $logger->info('注残欠品未引当通知処理 チケット作成: ' . print_r($ticket, true));
        }
      }

      // 最終更新日時 更新
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_NOTIFY_NON_ASSIGNED_SHORTAGE_STOCK, $now);

      $logger->info('注残欠品未引当通知処理 終了');

    } catch (\Exception $e) {

      $logger->error('注残欠品未引当通知処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('注残欠品未引当通知処理 エラー', '注残欠品未引当通知処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '注残欠品未引当通知処理 でエラーが発生しました。', 'error'
      );
      $logger->logTimerFlush();

      return 1;
    }

    return 0;

  }
}


