<?php
/**
 * バッチ処理 商品タイトルキーワードリスト作成処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshProductTitleKeywordListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:refresh-product-title-keyword-list')
      ->setDescription('商品タイトルキーワードリスト作成処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('商品タイトルキーワードリスト作成処理を開始しました。');

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

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('商品タイトルキーワードリスト作成処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      $dbMain = $this->getDb('main');

      $dbMain->query("DROP TABLE IF EXISTS tb_product_title_keyword");
      $sql = <<<EOD
        CREATE TABLE tb_product_title_keyword (
             keyword VARCHAR(255) NOT NULL PRIMARY KEY
          , `count` INTEGER NOT NULL DEFAULT 1    
          , `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
          , `updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時'
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4;
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        SELECT
          m.daihyo_syohin_name
        FROM tb_mainproducts m
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // 1万件（くらい）ずつ、テーブルへ挿入
      $limit = 10000;
      $loopTimes = 0;

      $insertWords = [];
      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $title = preg_replace('/\\s+/', ' ', trim(str_replace('　', ' ', $row['daihyo_syohin_name'])));
        $words = explode(' ', $title);
        foreach($words as $word) {
          $insertWords[] = $word;
        }

        if (count($insertWords) >= $limit) {

          // 挿入処理
          $this->insertWords($insertWords);

          $insertWords = [];

          $logger->info(sprintf('挿入 %d回目', ++$loopTimes));

          usleep(100000);
        }
      }

      if (count($insertWords) >= $limit) {
        $this->insertWords($insertWords);
        $logger->info(sprintf('挿入 %d回目', ++$loopTimes));
      }

      // 件数取得
      $count = $dbMain->query("SELECT COUNT(*) FROM tb_product_title_keyword;")->fetchColumn(0);
      $logger->info('件数: ' . $count);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation(['件数' => $count]));
      $logger->logTimerFlush();

      $logger->info('商品タイトルキーワードリスト作成処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('商品タイトルキーワードリスト作成処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('商品タイトルキーワードリスト作成処理 エラー', '商品タイトルキーワードリスト作成処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '商品タイトルキーワードリスト作成処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }


  private function insertWords($words)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $insertBuilder = new MultiInsertUtil("tb_product_title_keyword", [
      'fields' => [
         'keyword'  => \PDO::PARAM_STR
      ]
      , 'prefix' => "INSERT INTO"
      , 'postfix' => " ON DUPLICATE KEY UPDATE `count` = `count` + 1 "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $words, function($word) {
      $item = [
        'keyword'  => $word
      ];

      return $item;
    });
  }

}


