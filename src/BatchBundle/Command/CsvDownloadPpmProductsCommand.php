<?php
/**
 * バッチ処理 PPM 商品CSVダウンロード処理
 */

namespace BatchBundle\Command;

use BatchBundle\Job\MainJob;
use BatchBundle\MallProcess\PpmMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CsvDownloadPpmProductsCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  private $outputCsv = true;
  private $uploadOutputCsv = true;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-ppm-products')
      ->setDescription('PPM 商品CSVダウンロード処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('output-csv', null, InputOption::VALUE_OPTIONAL, 'CSV出力を実行するか', '1')
      ->addOption('upload-output-csv', null, InputOption::VALUE_OPTIONAL, 'CSV出力結果をPPM FTPへアップロードするか', '1')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('PPM 商品CSVダウンロード処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $this->outputCsv = boolval($input->getOption('output-csv'));
    $this->uploadOutputCsv = boolval($input->getOption('upload-output-csv'));

    try {

      $this->results = [
          'message' => null
        , 'files' => null
      ];

      $logExecTitle = sprintf('PPM 商品CSVダウンロード処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var WebAccessUtil $webAccessUtil */
      $webAccessUtil = $this->getWebAccessUtil();
      if ($this->account) {
        $webAccessUtil->setAccount($this->account);
      }

      // PPM ログイン
      $client = $webAccessUtil->getWebClient();
      $crawler = $webAccessUtil->ppmLogin($client);

      $logger->info($crawler ? get_class($crawler) : 'FAILED !! (no crawler)');

      /** @var PpmMallProcess $processor */
      $processor = $container->get('batch.mall_process.ppm');

      // CSVダウンロードリクエスト
      $requestSuccess = $processor->requestProductCsvDownload($client);
      if (!$requestSuccess) {
        throw new \RuntimeException('PPM 商品CSVダウンロードのリクエストに失敗しました。処理を中止します。');
      }

      // CSV一覧 スクレイピング＆ダウンロード （当日リクエスト分の最新を取得する）
      $importDir = sprintf('%s/Ppm/Import/%s', $this->getFileUtil()->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
      $limitDateTime = new \DateTime(); // 現在以降
      $limitDateTime->setTime($limitDateTime->format('H'), $limitDateTime->format('i'), 0); // 作成日時の秒は表示がないので00で合わせる
      $this->results['files'] = $processor->downloadCsv($client, $importDir, $limitDateTime);

      // CSV出力 キュー追加
      if ($this->outputCsv) {

        $resque = $this->getResque();
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_PPM
          , 'doUpload'         => ($this->uploadOutputCsv)
          , 'importPath'       => $importDir // インポートディレクトリ
        ];
        if ($this->account) {
          $job->args['account'] = $this->account->getId();
        }
        $resque->enqueue($job);

        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'PPM CSV出力処理キュー追加'));
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('PPM 商品CSVダウンロード処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('PPM 商品CSVダウンロード処理 エラー:' . $e->getMessage());

      if (!$this->results['message']) {
        $this->results['message'] = $e->getMessage();
      }

      $logger->addDbLog(
        $logger->makeDbLog('PPM 商品CSVダウンロード処理 エラー', 'PPM 商品CSVダウンロード処理 エラー', 'エラー終了')->setInformation($this->results)
        , true, 'PPM 商品CSVダウンロード処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


