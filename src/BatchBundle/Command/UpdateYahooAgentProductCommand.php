<?php
/**
 * バッチ処理 Yahoo代理店商品更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\EntityInterface\SymfonyUserClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateYahooAgentProductCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:update-yahoo-agent-product')
      ->setDescription('Yahoo代理店商品更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $this->getStopwatch();
    $this->stopwatch->start('main');

    $logger->info('Yahoo代理店商品更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUserClientInterface $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $dbMain = $this->getDb('main');

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('Yahoo代理店商品更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      // 商品データコピー・更新処理
      $sql = <<<EOD
        INSERT IGNORE INTO tb_yahoo_agent_product (
            shop_code
          , daihyo_syohin_code
          , product_name
          , genka_tanka
          , baika_tanka
        )
        SELECT
            T.shop_code
          , m.daihyo_syohin_code
          , i.yahoo_title
          , m.genka_tnk
          , i.baika_tanka
        FROM tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_yahoo_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN (
          SELECT
              YA.shop_code
            , m.daihyo_syohin_code
          FROM tb_mainproducts m
          INNER JOIN (
            SELECT
                daihyo_syohin_code
              , SUM(pci.フリー在庫数) AS フリー在庫数合計
            FROM tb_productchoiceitems pci
            WHERE pci.フリー在庫数 > 0
            GROUP BY pci.daihyo_syohin_code
          ) P ON m.daihyo_syohin_code = P.daihyo_syohin_code
          INNER JOIN (
            SELECT
              DISTINCT ya.shop_code
            FROM symfony_user_yahoo_agent ya
            WHERE ya.is_active <> 0
          ) YA
          UNION
          SELECT
              ap.shop_code
            , ap.daihyo_syohin_code
          FROM tb_yahoo_agent_product ap
        ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
        LEFT JOIN tb_yahoo_agent_product ap ON T.shop_code = ap.shop_code AND T.daihyo_syohin_code = ap.daihyo_syohin_code
        WHERE i.registration_flg <> 0
          AND ( ap.daihyo_syohin_code IS NULL OR ap.update_flg <> 0 )
        ORDER BY T.shop_code
          , m.daihyo_syohin_code
        ON DUPLICATE KEY UPDATE
              product_name = VALUES(product_name)
            , genka_tanka = VALUES(genka_tanka)
            , baika_tanka = VALUES(baika_tanka)
EOD;
      $dbMain->query($sql);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('商品データコピー・更新処理'));

      // 代理店用商品コード作成処理
      /*
      一時テーブル作成
      商品コード作成対象 一覧取得、挿入
      商品コード作成
      重複コード削除
      商品テーブルへ書き戻し

      最初に戻る
      */
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_yahoo_agent_product_code");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_yahoo_agent_product_code (
           shop_code VARCHAR(20) NOT NULL
         , daihyo_syohin_code VARCHAR(30) NOT NULL
         , product_code VARCHAR(30) NOT NULL DEFAULT ''
        ) Engine=InnoDB DEFAULT CHARSET utf8
EOD;
      $dbMain->query($sql);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('代理店用商品コード作成処理 一時テーブル作成'));

      // 商品コードに空文字がなくなるまでループ
      $safety = 100; // 100回やって終わらなければ何かおかしい。
      do {
        $sql = <<<EOD
        SELECT
          COUNT(*) AS cnt
        FROM tb_yahoo_agent_product
        WHERE product_code = ''
EOD;
        $newProducts = $dbMain->query($sql)->fetchColumn(0);
        /* ------------ DEBUG LOG ------------ */
        $logger->debug($this->getLapTimeAndMemory('代理店用商品コード作成処理 新商品件数取得'));
        $logger->debug('新商品件数: ' . $newProducts);

        if ($newProducts > 0) {

          // 一時テーブルから全削除
          $dbMain->query("DELETE FROM tmp_work_yahoo_agent_product_code");

          // 一時テーブルに、商品コードがない商品の商品コードを作成
          $sql = <<<EOD
            INSERT INTO tmp_work_yahoo_agent_product_code (
                shop_code
              , daihyo_syohin_code
              , product_code
            )
            SELECT
                ap.shop_code
              , ap.daihyo_syohin_code
              , CREATE_RANDOM_CODE(
                    /* 設定ミスによる無限ループを避けるため、最低限の数値を補完 */
                    CASE WHEN a.product_code_alphabet_num < 2 THEN 2 ELSE a.product_code_alphabet_num END
                  , CASE WHEN a.product_code_number_num < 3 THEN 3 ELSE a.product_code_number_num END
                )
            FROM tb_yahoo_agent_product ap
            INNER JOIN symfony_user_yahoo_agent a ON ap.shop_code = a.shop_code
            WHERE ap.product_code = ''
            ORDER BY shop_code
                   , daihyo_syohin_code
EOD;
          $dbMain->query($sql);
          /* ------------ DEBUG LOG ------------ */
          $logger->debug($this->getLapTimeAndMemory('代理店用商品コード作成処理 店舗用コード作成'));

          // 一時テーブルから、すでに存在する商品コードを除外
          $sql = <<<EOD
            DELETE t
            FROM tmp_work_yahoo_agent_product_code t
            INNER JOIN tb_yahoo_agent_product ap ON t.shop_code = ap.shop_code
                                                AND t.product_code = ap.product_code
EOD;
          $dbMain->query($sql);

          // 重複を除いて商品テーブルへ商品コードをセット
          $sql = <<<EOD
            UPDATE tb_yahoo_agent_product ap
            INNER JOIN (
              SELECT
                  MIN(shop_code) AS shop_code
                , MIN(daihyo_syohin_code) AS daihyo_syohin_code
                , product_code
              FROM tmp_work_yahoo_agent_product_code
              GROUP BY product_code
            ) T ON ap.shop_code = T.shop_code
               AND ap.daihyo_syohin_code = T.daihyo_syohin_code
            SET ap.product_code = T.product_code
            WHERE ap.product_code = ''
EOD;
          $dbMain->query($sql);
          /* ------------ DEBUG LOG ------------ */
          $logger->debug($this->getLapTimeAndMemory('代理店用商品コード作成処理 店舗用コード書き戻し'));
        }

      } while ($newProducts > 0 && $safety-- > 0);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Yahoo代理店商品更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Yahoo代理店商品更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Yahoo代理店商品更新処理 エラー', 'Yahoo代理店商品更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Yahoo代理店商品更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


