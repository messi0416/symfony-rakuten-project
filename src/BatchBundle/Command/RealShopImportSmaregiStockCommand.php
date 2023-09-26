<?php
/**
 * バッチ処理 スマレジ在庫取込処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RealShopImportSmaregiStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:real-shop-import-smaregi-stock')
      ->setDescription('スマレジ在庫取込処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('スマレジ在庫取込処理を開始しました。');

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

      $commonUtil = $this->getDbCommonUtil();
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $this->results = [
          'message' => null
        , 'removed' => 0
        , 'updated' => 0
      ];

      $logExecTitle = sprintf('スマレジ在庫取込処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 不要データ削除処理
      // 店舗在庫・在庫依頼ともに0のSKUしかない商品の在庫データを一括削除（商品情報は保持しておく）
      $sql = <<<EOD
        DELETE s
        FROM tb_real_shop_product_stock s
        INNER JOIN (
          SELECT
              s.daihyo_syohin_code
            , SUM(s.stock)  AS stock
            , SUM(s.order_num)  AS order_num
          FROM tb_real_shop_product_stock s
          GROUP BY s.daihyo_syohin_code
          HAVING stock <= 0
             AND order_num <= 0
        ) T ON s.daihyo_syohin_code = T.daihyo_syohin_code
EOD;
      $count = $dbMain->exec($sql);
      $this->results['removed'] = $count;

      // データ取込処理（全件取得）
      $webAccessUtil = $this->getWebAccessUtil();
      $client = $webAccessUtil->getSmaregiApiClient();
      $url = $webAccessUtil->getSmaregiApiUrl();

      // 在庫データ一時テーブル
      $dbMain->exec("DROP TEMPORARY TABLE IF EXISTS tmp_work_smaregi_stock_dl");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_smaregi_stock_dl (
            store_id INTEGER NOT NULL DEFAULT 0
          , product_id BIGINT NOT NULL DEFAULT 0
          , stock_amount INTEGER NOT NULL DEFAULT 0
          , upd_date_time DATETIME
          , PRIMARY KEY (store_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8
EOD;
      $dbMain->exec($sql);

      $dataCount = 0;
      $limit = 1000; // スマレジAPIの上限 1,000件
      $page = 1;

      do {
        // 在庫データ取得
        $fields = [
            'storeId'
          , 'productId'
          , 'stockAmount'
          , 'updDateTime'
        ];

        $params = [
            'proc_name' => 'stock_ref'
          , 'params' => json_encode([
              'fields' => $fields
            , 'table_name' => 'Stock'
            , 'conditions' => [
              'storeId' => '1' /* ひとまず、店舗1のみ */
            ]
            , 'order' => [
                'storeId'
              , 'productId'
            ]
            , 'limit' => $limit
            , 'page' => $page
          ])
        ];
        $client->request('POST', $url, $params);

        /** @var Response $response */
        $response  = $client->getResponse();
        $status = $response->getStatus();
        if ($status != 200) {
          throw new \RuntimeException($response->getContent());
        }

        $data = json_decode($response->getContent(), true);
        $totalCount = $data['total_count'];
        $list = $data['result'];
        $dataCount += count($list);

        // 一括insert
        $insertBuilder = new MultiInsertUtil("tmp_work_smaregi_stock_dl", [
          'fields' => [
              'store_id'  => \PDO::PARAM_INT
            , 'product_id' => \PDO::PARAM_INT
            , 'stock_amount' => \PDO::PARAM_INT
            , 'upd_date_time' => \PDO::PARAM_STR
          ]
        ]);

        $commonUtil->multipleInsert($insertBuilder, $dbMain, $list, function ($row) use ($logger) {

          $item = [
              'store_id' => $row['storeId']
            , 'product_id' => $row['productId']
            , 'stock_amount' => $row['stockAmount']
            , 'upd_date_time' => $row['updDateTime']
          ];

          return $item;

        }, 'foreach');

        $page++;

        sleep(1); // 連打を避けるため

      } while ($dataCount < $totalCount);

      // 店舗在庫テーブル更新
      $sql = <<<EOD
        INSERT INTO tb_real_shop_product_stock (
            ne_syohin_syohin_code
          , daihyo_syohin_code
          , stock
        )
        SELECT
            pc.ne_syohin_syohin_code
          , pci.daihyo_syohin_code
          , dl.stock_amount AS stock
        FROM tmp_work_smaregi_stock_dl dl
        INNER JOIN tb_product_code pc ON dl.product_id = pc.id
        INNER JOIN tb_productchoiceitems pci ON pc.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        ON DUPLICATE KEY UPDATE stock = VALUES(stock)
EOD;
      $count = $dbMain->exec($sql);
      $result['updated'] = $count;

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('スマレジ在庫取込処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('スマレジ在庫取込処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('スマレジ在庫取込処理 エラー', 'スマレジ在庫取込処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'スマレジ在庫取込処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


