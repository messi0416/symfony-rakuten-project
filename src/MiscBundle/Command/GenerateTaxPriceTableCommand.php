<?php
/**
 * バッチ処理 税込みちょうど価格テーブル生成処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTaxPriceTableCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:generate-tax-price-table')
      ->setDescription('税込みちょうど価格テーブル生成処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('max', null, InputOption::VALUE_OPTIONAL, '最大値: 初期値 1000', 1000)
      ->addOption('tax-rate', null, InputOption::VALUE_OPTIONAL, '税率(%)',  null)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('税込みちょうど価格テーブル生成処理を開始しました。');

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

      $this->results = [
          'message' => null
      ];

      $max = $input->getOption('max');
      $taxRate = $input->getOption('tax-rate');
      $rate = is_null($taxRate) ? $commonUtil->getTaxRate() : (1 + ($taxRate / 100));

      $logExecTitle = sprintf('税込みちょうど価格テーブル生成処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $baseList = [];
      for ($i = 1; $i <= $max; $i++) {
        $baseList[] = $i;
      }

      // テーブルへ書き込み
      $dbMain = $this->getDb('main');

      $sql = <<<EOD
        CREATE TABLE IF NOT EXISTS tax_price (
            base INTEGER NOT NULL PRIMARY KEY
          , taxed_raw INTEGER NOT NULL
          , taxed INTEGER NOT NULL
          , fixed INTEGER NOT NULL
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
      $dbMain->query($sql);
      $dbMain->query("TRUNCATE tax_price");

      // 一括insert
      $insertBuilder = new MultiInsertUtil("tax_price", [
        'fields' => [
            'base' => \PDO::PARAM_INT
          , 'taxed_raw' => \PDO::PARAM_INT
          , 'taxed' => \PDO::PARAM_INT
          , 'fixed' => \PDO::PARAM_INT
        ]
        , 'prefix' => "INSERT INTO"
      ]);

      $commonUtil->multipleInsert($insertBuilder, $dbMain, $baseList, function($base) use ($rate) {

        $item = [
            'base' => $base
          , 'taxed_raw' => floor($base * $rate)
          , 'taxed' => 0
          , 'fixed' => 0
        ];

        $item['taxed'] = ceil($item['taxed_raw'] / 10) * 10;

        do {
          $fixed = $item['taxed'] / $rate;

          // まず切り捨て
          $result = floor(floor($fixed) * $rate);
          if ($result == $item['taxed']) {
            $item['fixed'] = floor($fixed);
            // 次に切り上げ
          } else {
            $result = floor(ceil($fixed) * $rate);
            if ($result == $item['taxed']) {
              $item['fixed'] = ceil($fixed);
            } else {
              // 10円あげてやり直し。
              // echo $item['taxed'] . "\n";
              $item['taxed'] += 10;
            }
          }

        } while (!$item['fixed']);

        return $item;

      }, 'foreach');

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('税込みちょうど価格テーブル生成処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('税込みちょうど価格テーブル生成処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('税込みちょうど価格テーブル生成処理 エラー', '税込みちょうど価格テーブル生成処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '税込みちょうど価格テーブル生成処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


