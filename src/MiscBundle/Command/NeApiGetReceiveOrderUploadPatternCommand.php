<?php
/**
 * バッチ処理 NextEngineAPI 受注一括登録パターン取得処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

class NeApiGetReceiveOrderUploadPatternCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:ne-api-get-receive-order-upload-pattern')
      ->setDescription('NextEngineAPI 受注一括登録パターン一覧取得 ※受注一括登録API利用に必要')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('NextEngineAPI 受注一括登録パターン取得処理を開始しました。');

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
          'status' => 'ok'
        , 'message' => null
        , 'result' => null
      ];

      $logExecTitle = sprintf('NextEngineAPI 受注一括登録パターン取得処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // ---------------------------------------------
      // API データ取得処理 開始
      /** @var NextEngineMallProcess $process */
      $process = $this->getContainer()->get('batch.mall_process.next_engine');

      $client = $process->getApiClient('api');
      $client->setLogger($logger);

      $newLastUpdated = new \DateTimeImmutable(); // 処理成功後、最終更新日時とする日付。

      // 情報を取得
      $query = [];
      // アクセス制限中はアクセス制限が終了するまで待つ。
      // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
      $query['wait_flag'] = '1' ;

      // 検索実行
      $receives = $client->apiExecute('/api_v1_receiveorder_uploadpattern/info', $query) ;
      $client->log($receives['result']);
      $client->log($receives['count']);

      if ($receives['result'] != 'success') {
        $logger->info(print_r($receives, true));
        $message = 'NE APIエラー';
        if (isset($receives['code'])) {
          $message = sprintf('[%s] ', $receives['code']);
        }
        if (isset($receives['message'])) {
          $message .= $receives['message'];
        }

        throw new \RuntimeException($message);
      }

      $this->results['result'] = $receives;

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('NextEngineAPI 受注一括登録パターン取得処理を完了しました。');

    } catch (\Exception $e) {

      $this->results['status'] = 'ng';
      $this->results['message'] = $e->getMessage();

      $logger->error('NextEngineAPI 受注一括登録パターン取得処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('NextEngineAPI 受注一括登録パターン取得処理 エラー', 'NextEngineAPI 受注一括登録パターン取得処理 エラー', 'エラー終了')->setInformation($this->results)
        , true, 'NextEngineAPI 受注一括登録パターン取得処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


