<?php
/**
 * Amazon CSV出力処理 MWSアップロード（submitFeed）処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExportCsvAmazonUploadCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  protected $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-amazon-upload')
      ->setDescription('CSVエクスポート Amazon アップロード処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null)
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗 vogue|us_plusnao', AmazonMallProcess::SHOP_NAME_VOGUE)
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('AmazonCSV出力アップロード処理を開始しました。');

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
      $this->stopwatch->start('main');

      $shop = $input->getOption('shop');
      if (!in_array($shop, [
          AmazonMallProcess::SHOP_NAME_VOGUE
        , AmazonMallProcess::SHOP_NAME_US_PLUSNAO
      ])) {
        throw new \RuntimeException('invalid amazon shop : ' . $shop);
      }

      $this->results = [
          'message' => null
        , 'delete' => []
        , 'update' => []
        , 'stock' => []
        , 'shop' => $shop
      ];

      $logExecTitle = sprintf('AmazonCSV出力アップロード処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 出力パス
      $this->exportPath = $input->getOption('export-dir');
      if (!$this->exportPath) {
        throw new \RuntimeException('アップロードファイルのディレクトリが指定されていません。');
      }

      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation('アップロードファイルのディレクトリが存在しません。処理を終了しました。'));
        $logger->logTimerFlush();

        $logger->info('AmazonCSV出力アップロード処理を完了しました。');
        $this->stopwatch->stop('main');
        return 0;
      }

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      // $mallProcess->setEnvironment('prod'); // 開発環境からAmazon本番へアクセス。

      // 削除
      $finder = new Finder();
      $finder->in($this->exportPath)->name('/Amazon_delete_\d+.txt/');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('delete: ' . $file->getPathname());
        $this->results['delete'][] = $file->getPathname();

        // フィード送信
        $mallProcess->submitFeeds($shop, $file->getPathname());
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('delete'));

      // 商品データ更新
      $finder = new Finder();
      $finder->in($this->exportPath)->name('/Amazon_\d+\.txt/');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('update: ' . $file->getPathname());
        $this->results['update'][] = $file->getPathname();

        // フィード送信
        $mallProcess->submitFeeds($shop, $file->getPathname());
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('update'));

      // FBA切り替え アップロード（出品ファイル(I)）
      $finder = new Finder();
      $finder->in($this->exportPath)->name('/Amazon_FBA_\d+\.txt/');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('change_fba: ' . $file->getPathname());
        $this->results['change_fba'][] = $file->getPathname();

        // フィード送信
        $mallProcess->submitFeeds($shop, $file->getPathname(), AmazonMallProcess::FEED_TYPE_ITEM_LIST_I);
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('change_fba'));

      // 在庫更新
      $finder = new Finder();
      $finder->in($this->exportPath)->name('/Amazon_stock_\d+.txt/');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('stock: ' . $file->getPathname());
        $this->results['stock'][] = $file->getPathname();

        // フィード送信
        $feedType = $shop == AmazonMallProcess::SHOP_NAME_US_PLUSNAO
                  ? AmazonMallProcess::FEED_TYPE_PRICE_AND_QUANTITY
                  : AmazonMallProcess::FEED_TYPE_ITEM_LIST;
        $mallProcess->submitFeeds($shop, $file->getPathname(), $feedType);
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('stock'));

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('AmazonCSV出力アップロード処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('Amazon CSV出力アップロード処理 エラー:' . $e->getMessage());
      $this->results['message'] = $e->getMessage();
      $logger->addDbLog(
        $logger->makeDbLog('Amazon CSV出力アップロード処理', 'Amazon CSV出力アップロード処理', 'エラー終了')->setInformation($this->results)
        , true, 'Amazon CSV出力アップロード処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }
}
