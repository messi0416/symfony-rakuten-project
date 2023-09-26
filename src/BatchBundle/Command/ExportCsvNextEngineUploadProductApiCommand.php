<?php
/**
 * バッチ処理 NextEngine商品マスタ一括更新API処理
 *
 * ※実際にはこのCommandとしては利用していないサンプルじっす。
 *   アップロード処理の手動実行用途として利用する必要に応じては維持する。
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExportCsvNextEngineUploadProductApiCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-next-engine-upload-product-api')
      ->setDescription('NextEngine商品マスタ一括更新API処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('path', InputArgument::REQUIRED, 'アップロードCSVファイルpath (Shift_JIS)')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('NextEngine商品マスタ一括更新API処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $this->results = [
        'message' => null
      , 'path' => null
      , 'result' => null
    ];

    try {

      $logExecTitle = sprintf('NextEngine商品マスタ一括更新API処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $path = $input->getArgument('path');
      $this->results['path'] = $path;

      $logger->info('next engine upload product (api) : ' . $path);

      /** @var NextEngineMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

      $file = new SplFileInfo($path);
      if (!$file || !$file->isReadable() || !$file->isFile()) {
        throw new \RuntimeException('アップロードファイルが取得できませんでした。 ' . ($file ? $file->getPathname() : '(none)'));
      }

      // アップロード処理
      $result = $mallProcess->apiUploadProductCsv($file);
      if ($result['status'] != 'ok') {
        throw new \RuntimeException('アップロードエラー: ' . $result['message']);
      }
      $this->results['result'] = $result;

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('NextEngine商品マスタ一括更新API処理を完了しました。');

    } catch (\Exception $e) {
      if (!$this->results['message']) {
        $this->results['message'] = $e->getMessage();
      }

      $logger->error('NextEngine商品マスタ一括更新API処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog('NextEngine商品マスタ一括更新API処理 エラー', 'NextEngine商品マスタ一括更新API処理 エラー', 'エラー終了')->setInformation($this->results)
        , true, 'NextEngine商品マスタ一括更新API処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }
}


