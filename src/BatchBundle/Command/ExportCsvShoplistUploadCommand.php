<?php
/**
 * SHOPLIST CSV出力処理 アップロード処理
 */

namespace BatchBundle\Command;

// use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExportCsvShoplistUploadCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  protected $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-shoplist-upload')
      ->setDescription('CSVエクスポート SHOPLIST アップロード処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null);
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
    $logger->info('SHOPLIST CSV出力アップロード処理を開始しました。');

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

      $this->results = [
        'message' => null
      ];

      $logExecTitle = sprintf('SHOPLIST CSV出力アップロード処理');
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

        $logger->info('SHOPLIST CSV出力アップロード処理を完了しました。');
        $this->stopwatch->stop('main');
        return 0;
      }

      $ftpConfig = $this->getContainer()->getParameter('ftp_shoplist');
      $config = $ftpConfig['csv_upload'];
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->getDbCommonUtil(); 
      $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_SHOPLIST_FTP_PASSWORD); 

      // $logger->info(print_r($config, true)); // FOR DEBUG


      // detail_new
      $finder = new Finder();
      $finder->in($this->exportPath)->name('product_detail_new.csv');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('detail_new: ' . $file->getPathname());
        $this->results['detail_new'][] = $file->getPathname();

        // アップロード
        $remotePath = 'product_detail.csv'; // 新規登録は名前を変更してアップロード（もともと別名で作成されるため）
        $this->uploadCsv($config, $file->getPathname(), $remotePath, true);
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('detail_new'));

      // detail
      $finder = new Finder();
      $finder->in($this->exportPath)->name('product_detail.csv');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('detail: ' . $file->getPathname());
        $this->results['detail'][] = $file->getPathname();

        // アップロード
        $this->uploadCsv($config, $file->getPathname(), $file->getBasename(), true);
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('detail'));

      // select
      $finder = new Finder();
      $finder->in($this->exportPath)->name('product_select.csv');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('select: ' . $file->getPathname());
        $this->results['select'][] = $file->getPathname();

        // アップロード
        $this->uploadCsv($config, $file->getPathname(), $file->getBasename(), true);
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('select'));

      // stock
      $finder = new Finder();
      $finder->in($this->exportPath)->name('stock.csv');
      /** @var SplFileInfo $file */
      foreach($finder->files() as $file){
        $logger->info('stock: ' . $file->getPathname());
        $this->results['stock'][] = $file->getPathname();

        // アップロード
        $this->uploadCsv($config, $file->getPathname(), $file->getBasename(), true);
      }
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('stock'));

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('SHOPLIST CSV出力アップロード処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('SHOPLIST CSV出力アップロード処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('SHOPLIST CSV出力アップロード処理', 'SHOPLIST CSV出力アップロード処理', 'エラー終了')->setInformation($e->getMessage())
        , true, 'SHOPLIST CSV出力アップロード処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }


  /**
   * CSVアップロード処理
   * ※ remoteDir にファイルが無くなるまで待ってアップロードする。
   * @param array $config FTP接続設定
   * @param string $filePath アップロードファイルパス
   * @param string $remotePath リモートパス
   * @param bool $waitEmpty FTPディレクトリが空になるまで待つか
   */
  public function uploadCsv($config, $filePath, $remotePath, $waitEmpty = true)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logTitle = 'CSVファイルアップロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, basename($filePath)));
    $logger->info('SHOPLIST CSVファイルアップロード ' . $filePath);

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    $ftp->connect($config['host']);

    try {
      $ftp->login($config['user'], $config['password']);
    } catch (\Exception $e) {
      $message = 'SHOPLISTのCSVファイルアップロード処理中、FTPにログインできませんでした。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path']);

    // ディレクトリが空になるまで待つ
    if ($waitEmpty) {
      $limit = new \DateTime(); // 開始時刻
      $limit->modify('+ 3 hour'); // 3時間で諦める
      do {

        $fileList = $ftp->nlist('./');

        if (!is_array($fileList)) {
          $logger->error('ftp files: ' . print_r($fileList, true));
          $message = 'SHOPLISTのCSVファイルアップロード処理中、FTPのファイル一覧が取得できませんでした。';
          throw new \RuntimeException($message);
        }

        $logger->info('SHOPLIST CSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true));
        sleep(5); // 5秒待つ

        if ($limit < new \DateTime()) {
          $logger->error('ftp files: ' . print_r($fileList, true));
          $message = 'SHOPLISTのCSVファイルアップロード処理中、FTPが空にならずアップロードできませんでした。処理を中止します。';
          throw new \RuntimeException($message);
        }

      } while(count($fileList)) ;
    }

    // throw new \RuntimeException('DEBUG STOP!!');

    $ftp->put($remotePath, $filePath, FTP_BINARY);
    $ftp->close();
  }


}
