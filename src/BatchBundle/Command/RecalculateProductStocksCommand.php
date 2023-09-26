<?php
/**
 * バッチ処理 商品在庫再集計処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecalculateProductStocksCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:recalculate-product-stock')
      ->setDescription('商品在庫再集計処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('商品在庫再集計処理を開始しました。');

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
        , 'no_match' => []
      ];

      $logExecTitle = sprintf('商品在庫再集計処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 現在の数値のズレを確認 （0になっていなければ調査・修正する）
      $dbMain = $this->getDb('main');
      $sql = <<<EOD
        SELECT
            pci.ne_syohin_syohin_code
          , pci.在庫数 AS 在庫数
          , v.在庫数   AS v_在庫数
          , pci.受注数 AS 受注数
          , v.受注数   AS v_受注数
          , pci.引当数 AS 引当数
          , v.引当数   AS v_引当数
          , pci.ピッキング引当数 AS ピッキング引当数
          , v.ピッキング引当数 AS v_ピッキング引当数
          , pci.出荷予定取置数 AS 出荷予定取置数
          , v.出荷予定取置数 AS v_出荷予定取置数
          , pci.移動中在庫数 AS 移動中在庫数
          , v.移動中在庫数 AS v_移動中在庫数
          , pci.販売不可在庫数 AS 販売不可在庫数
          , v.販売不可在庫数 AS v_販売不可在庫数
          , pci.発注残数 AS 発注残数
          , v.発注残数 AS v_発注残数

          , pci.総在庫数 AS 総在庫数
          , v.総在庫数 AS v_総在庫数
          , pci.フリー在庫数 AS フリー在庫数
          , (CASE WHEN COALESCE(v.フリー在庫数, 0) <= 0 THEN 0 ELSE v.フリー在庫数 END) AS v_フリー在庫数
        FROM tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        WHERE pci.在庫数 <> COALESCE(v.在庫数, 0)
           OR pci.受注数 <> COALESCE(v.受注数, 0)
           OR pci.引当数 <> COALESCE(v.引当数, 0)
           OR pci.ピッキング引当数 <> COALESCE(v.ピッキング引当数, 0)
           OR pci.出荷予定取置数 <> COALESCE(v.出荷予定取置数, 0)
           OR pci.移動中在庫数 <> COALESCE(v.移動中在庫数, 0)
           OR pci.販売不可在庫数 <> COALESCE(v.販売不可在庫数, 0)
           OR pci.発注残数 <> COALESCE(v.発注残数, 0)

           OR pci.総在庫数 <> COALESCE(v.総在庫数, 0)
           OR pci.フリー在庫数 <> (CASE WHEN COALESCE(v.フリー在庫数, 0) <= 0 THEN 0 ELSE v.フリー在庫数 END)
        ORDER BY pci.ne_syohin_syohin_code
EOD;
      $stmt = $dbMain->query($sql);
      if ($stmt->rowCount()) {
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $this->results['no_match'][] = $row;
        }
      }

      // 数値の更新
      // 在庫数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.在庫数 = COALESCE(v.在庫数, 0)
        WHERE pci.在庫数 <> COALESCE(v.在庫数, 0)
EOD;
      $dbMain->query($sql);

      // 受注数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.受注数 = COALESCE(v.受注数, 0)
        WHERE pci.受注数 <> COALESCE(v.受注数, 0)
EOD;
      $dbMain->query($sql);

      // 引当数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.引当数 = COALESCE(v.引当数, 0)
        WHERE pci.引当数 <> COALESCE(v.引当数, 0)
EOD;
      $dbMain->query($sql);

      // ピッキング引当数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.ピッキング引当数 = COALESCE(v.ピッキング引当数, 0)
        WHERE pci.ピッキング引当数 <> COALESCE(v.ピッキング引当数, 0)
EOD;
      $dbMain->query($sql);

      // 出荷予定取置数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.出荷予定取置数 = COALESCE(v.出荷予定取置数, 0)
        WHERE pci.出荷予定取置数 <> COALESCE(v.出荷予定取置数, 0)
EOD;
      $dbMain->query($sql);

      // 移動中在庫数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.移動中在庫数 = COALESCE(v.移動中在庫数, 0)
        WHERE pci.移動中在庫数 <> COALESCE(v.移動中在庫数, 0)
EOD;
      $dbMain->query($sql);

      // 販売不可在庫数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.販売不可在庫数 = COALESCE(v.販売不可在庫数, 0)
        WHERE pci.販売不可在庫数 <> COALESCE(v.販売不可在庫数, 0)
EOD;
      $dbMain->query($sql);

      // 発注残数
      $sql = <<<EOD
        UPDATE tb_productchoiceitems pci
        LEFT JOIN v_product_stock_total v ON pci.ne_syohin_syohin_code = v.ne_syohin_syohin_code
        SET pci.発注残数 = COALESCE(v.発注残数, 0)
        WHERE pci.発注残数 <> COALESCE(v.発注残数, 0)
EOD;
      $dbMain->query($sql);

      // 数値に差異があればチケットを作成
      $container = $this->getContainer();
      if ($this->results['no_match'] && $container->getParameter('redmine_illegal_stock_ticket')) { // 本番環境のみ

        /** @var WebAccessUtil $webAccessUtil */
        $webAccessUtil = $container->get('misc.util.web_access');
        if ($this->account) {
          $webAccessUtil->setAccount($this->account);
        }

        $messages = [];
        $messages[] = sprintf('|%s|', implode('|', [
            'ne_syohin_syohin_code'
          , '在庫数'
          , 'v_在庫数'
          , '受注数'
          , 'v_受注数'
          , '引当数'
          , 'v_引当数'
          , 'ピッキング引当数'
          , 'v_ピッキング引当数'

          , '出荷予定取置数'
          , 'v_出荷予定取置数'
          , '移動中在庫数'
          , 'v_移動中在庫数'
          , '販売不可在庫数'
          , 'v_販売不可在庫数'

          , '発注残数'
          , 'v_発注残数'

          , '総在庫数'
          , 'v_総在庫数'
          , 'フリー在庫数'
          , 'v_フリー在庫数'
        ]));

        foreach($this->results['no_match'] as $row) {
          $messages[] = sprintf('|%s|', implode('|', $row));
        }

        $ticket = [
          'issue' => [
              'subject'         => '[Plusnao Notice] 商品在庫数差異通知'
            , 'project_id'      => $container->getParameter('redmine_illegal_stock_ticket_project')
            , 'priority_id'     => $container->getParameter('redmine_illegal_stock_ticket_priority')
            , 'description'     => implode("\n", $messages)
            , 'assigned_to_id'  => $container->getParameter('redmine_illegal_stock_ticket_user')
            , 'tracker_id'      => $container->getParameter('redmine_illegal_stock_ticket_tracker')
          ]
        ];

        $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('商品在庫再集計処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('商品在庫再集計処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('商品在庫再集計処理 エラー', '商品在庫再集計処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '商品在庫再集計処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


