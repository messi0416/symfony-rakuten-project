<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use Goutte\Client;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;


class CsvDownloadStockListCommand extends ContainerAwareCommand
{
  /** @var  FileUtil */
  private $fileUtil;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-stock-list')
      ->setDescription('login to NextEngine Web site and download stock CSV file.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('output-file', null, InputOption::VALUE_OPTIONAL, '出力ファイル名')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->fileUtil = $this->getContainer()->get('misc.util.file');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger->initLogTimer();

    $logger->info('在庫更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = '在庫データ取込 CSVダウンロード';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    $rootDir = $container->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data';

    $fileName = $input->getOption('output-file');
    if (!$fileName) {
      $fileName = sprintf('data%s.csv', date('YmdHis00000000'));
    }

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      $client = $webAccessUtil->getWebClient();

      // NEログイン・メインページへの遷移
      $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

      // CSVファイルダウンロードリンク クリック
      try {
        $csvLink = $crawler->filter('ul#ne_topmenu')->selectLink('CSVファイル')->link();
        $crawler = $client->click($csvLink);

      } catch (\InvalidArgumentException $e) {
        $uri = $client->getRequest()->getUri();

        // 「重要なお知らせ」が差し込まれる場合があり、直接URLを叩くことにします。
        if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
          $uri = $match[1] . '/Userinspection2';
          $crawler = $client->request('get', $uri);
        } else {
          throw $e;
        }
      }

      $status = $client->getResponse()->getStatus();
      $uri = $client->getRequest()->getUri();
      $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($client->getResponse());
      if ($status !== 200 || $isInvalidAccess || !preg_match('!.next-engine.(?:org|com)/Userinspection2!', $uri)) {
        $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
        file_put_contents($scrapingResponseDir . '/next_engine_download_stock_list.html', $client->getResponse()->getContent());
        $message = $isInvalidAccess ? '不正アクセスエラー' : '';
        throw new RuntimeException("move to csv download page error!! $message [ $status ][ $uri ]");
      }
      $logger->info('CSVダウンロード画面へ遷移成功');
      // $logger->info(print_r($crawler->html(), true));

      $button = $crawler->selectButton('ダウンロード');
      if (!$button->count()) {
        throw new \RuntimeException('現在、NextEngine CSVダウンロードができない状態です。時間をおいて再度実行してみてください。');
      }
      $form = $crawler->selectButton('ダウンロード')->form();

      // 「在庫一覧」ダウンロード処理
      $xpath = sprintf('descendant-or-self::option[contains(concat(\' \', normalize-space(string(.)), \' \'), %s)]', Crawler::xpathLiteral('在庫一覧'));
      $selectOption = $crawler->filter('select[name="s_id"]')->filterXPath($xpath);
      if (!$selectOption->attr('value')) {
        throw new RuntimeException('在庫一覧CSVが選択できませんでした。');
      }
      $logger->info('在庫一覧CSV選択成功、ダウンロード試行');

      $form['s_id'] = $selectOption->attr('value');
      $form['moji_code'] = 'SJIS';

      $client->submit($form);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();
      $contentLength = intval($response->getHeader('Content-Length'));
      $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
      if ($status !== 200 || $isInvalidAccess || strpos($response->getHeader('Content-Type'), 'application/octet-stream') === false || !$contentLength) {
        $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
        file_put_contents($scrapingResponseDir . '/next_engine_download_stock_list_download.html', $client->getResponse()->getContent());
        $message = $isInvalidAccess ? '不正アクセスエラー' : '';
        throw new RuntimeException("can not download csv error!! $message [ $status ][ $uri ][" . $response->getHeader('Content-Type') . ']');
      }
      $logger->info('在庫一覧CSVダウンロードレスポンス取得');

      $fs = new FileSystem();
      $saveDir = $dataDir . '/stocks';
      if (!$fs->exists($saveDir)) {
        $fs->mkdir($saveDir, 0755);
      }

      $path = $saveDir . '/' . $fileName;
      if ($fs->exists($path)) {
        throw new RuntimeException('same csv name exists error!! [' . $path . ']');
      }

      $file = new \SplFileObject($path, 'w'); // 上書き
      $bytes = $file->fwrite($response->getContent());

      if (!$fs->exists($path) || $bytes !== $contentLength) {
        @$fs->remove($path);
        throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . '][' . $contentLength . ']');
      }

      // テスト環境の在庫一覧はなぜか最後に「在庫金額」が付いているため、本番に合わせるためにこれを落とす。
      if ($input->getOption('target-env') == 'test'){
        // SJIS, CRLF, ダブルクォート
        $tmpFile = $path . '_tmp';

        $tmpFp = fopen($tmpFile, 'w+b');
        $fp = fopen($path, 'r');
        while(($line = fgets($fp)) !== false) {
          fputs($tmpFp, mb_convert_encoding($line, 'UTF-8', 'SJIS-WIN')); // UTF-8に変換してCSVを扱う
        }
        fclose($fp);

        $fp = fopen($path, 'wb'); // 書き換え。ここで空になる。

        /** @var \MiscBundle\Util\StringUtil $stringUtil */
        $stringUtil = $this->getContainer()->get('misc.util.string');

        setlocale(LC_ALL,'ja_JP.UTF-8');
        fseek($tmpFp, 0);
        while($row = fgetcsv($tmpFp, null, ',', '"', '\\')) {
          unset($row[count($row) - 1]);
          $line = $stringUtil->convertArrayToCsvLine($row);
          fputs($fp, mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . "\r\n");
        }

        fclose($fp);
        fclose($tmpFp);

        @$fs->remove($tmpFile);
      }

      $logger->info('在庫一覧CSV出力成功。[' . $path . ']');


      // DB記録＆通知処理
      // チェック機能のため、サブ2にファイル名、サブ3に行数、ファイルサイズを登録(JSON)
      $fileInfo = $this->fileUtil->getTextFileInfo($path);
      $info = [
          'サイズ' => $fileInfo['size']
        , '行数' => $fileInfo['lineCount']
        , 'ファイル名' => $fileInfo['basename']
      ];
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($info));
      $logger->logTimerFlush();


//      // 引き続き、DB更新（開発用実装）
//      // → 本来は、これは別Jobとして別のキューで呼び出す予定（=> 排他でのリトライを独立して行うため）
//      $commandArgs = [
//          'dummy' // 引数を並べていく。最初の引数は何でもよい。
//        , $file->getBasename()
//      ];
//      if ($this->account) {
//        $commandArgs[] = sprintf('--account=%d', $this->account->getId());
//      }
//      $input = new ArgvInput($commandArgs);
//      $output = new ConsoleOutput();
//
//      $command = $this->getContainer()->get('batch.update_db_by_stock_list_csv');
//      $exitCode = $command->run($input, $output);
//      if ($exitCode !== 0) { // コマンドが異常終了した
//        throw new RuntimeException('can not update db.');
//      }

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "在庫データ CSVダウンロードでエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
