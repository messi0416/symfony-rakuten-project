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


class CsvDownloadOrderDataCommand extends ContainerAwareCommand
{
  /** @var  \MiscBundle\Util\FileUtil */
  private $fileUtil;

  /** @var SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-order-data')
      ->setDescription('login to NextEngine Web site and download order data.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '取得開始年月日')
      ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '取得終了年月日')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return bool
     */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->fileUtil = $container->get('misc.util.file');

    /** @var BatchLogger $logger */
    $logger = $container->get('misc.util.batch_logger');
    $logger->initLogTimer();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $logger->info('受注取込 CSVデータダウンロードを開始しました。');

    // DB記録＆通知処理
    $logExecTitle = '受注取込 CSVデータダウンロード';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      $client = $webAccessUtil->getWebClient();

      // データ取得範囲
      $startDate = new \DateTime($input->getOption('start-date'));
      $endDate = new \DateTime($input->getOption('end-date'));
      $logger->info(sprintf('受注取込 範囲： %s - %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));

      // NEログイン
      $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

      $mainHost = null;
      $uri = $client->getRequest()->getUri();
      if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
        $mainHost = $match[1];
      } else {
        throw new RuntimeException('メイン機能URLのホスト名の取得に失敗しました。');
      }

      // 月別 照合用データの取得処理
      // チェック用 CSV ダウンロード
      // → CSV元データがAjaxにより画面表示されているものであるため、データソースへ直接アクセス（JSON）
      $nextUrl = $mainHost . '/Userinspection/geturiage';
      $params = [
        'id' => '10' // ID:10「分析」>「売上情報」＞ 「受注ベース 総合計分析」
      ];
      $crawler = $client->request('post', $nextUrl, $params);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $requestUri = $client->getRequest()->getUri();
      $contentLength = intval($response->getHeader('Content-Length'));
      $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);

      $jsonData = @json_decode($response->getContent(), true);
      if ($status !== 200
        || $isInvalidAccess
        // || $response->getHeader('Content-Type') !== 'application/json; charset=UTF-8' // Content-Typeは text/html と application/json で不安定なので、チェックには使わない
        || !$contentLength
        || !is_array($jsonData)
        || !count($jsonData)
      ) {
        $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
        file_put_contents($scrapingResponseDir . '/next_engine_csv_download_order_data.html', $response->getContent());
        // どうも夜間バッチでNextEngineでこのデータが取得できなくなったっぽい。（あるいはできないタイミングが増えたか。）
        // だめなら駄目で、一旦スルーする。
        $message = $isInvalidAccess ? '不正アクセスエラー' : '';
        $logger->warning("can not download verify csv error!! $message [ $status ][ $requestUri ][" . $response->getHeader('Content-Type') . ']');

        // throw new RuntimeException('can not download verify csv error!! [' . $status . '][' . $requestUri . '][' . $response->getHeader('Content-Type') . ']');
      }
      $logger->info("$logExecTitle 照合用データ レスポンス取得");

      if ($jsonData) {
        // このCSV取込処理は1テーブルへのデータ格納のみなので、ファイル化はスキップする。
        $this->importCheckCsvData($jsonData);
        $logger->info("$logExecTitle チェック用CSV 取込成功");
      } else {
        $logger->info("$logExecTitle チェック用CSV 取込失敗");
      }


      // CSVファイルダウンロードリンク クリック
      // ※「重要なお知らせ」が差し込まれる場合があるため、直接URLを叩くことにします。
      $uri = $mainHost . '/Userinspection2';
      $crawler = $client->request('get', $uri);

      $status = $client->getResponse()->getStatus();
      $uri = $client->getRequest()->getUri();
      if ($status !== 200 || !preg_match('!.next-engine.(?:org|com)/Userinspection2!', $uri)) {
        throw new RuntimeException('move to csv download page error!! [' . $status . '][' . $uri . ']');
      }
      $logger->info("$logExecTitle CSVダウンロード画面へ遷移成功");

      // 「【オリジナル】売上集計(日時指定)」ダウンロード処理
      $xpath = sprintf('descendant-or-self::option[contains(concat(\' \', normalize-space(string(.)), \' \'), %s)]', Crawler::xpathLiteral('【オリジナル】売上集計(日時指定)'));
      $selectOption = $crawler->filter('select[name="s_id"]')->filterXPath($xpath);
      if (!$selectOption->attr('value')) {
        throw new RuntimeException('受注データCSVが選択できませんでした。');
      }

      $logger->info('プルダウンID' . $selectOption->attr('value'));

      // $form = $crawler->selectButton('ダウンロード')->form();

      // 画面からフォームパラメータを取得する
      // ※ フォームが JavaScriptで変更されるため、Goutte機能の form()->submit()はできない。

      $sessionCookie = $client->getCookieJar()->get('company_login');
      if (!$sessionCookie) {
        throw new RuntimeException('ログインセッションのCookie取得に失敗しました。');
      }

      $params = [
          's_id' => $selectOption->attr('value') // CSV種類
        , 'moji_code' => 'SJIS'
        , 'sea_syohin_search_field49_from2[1]' => $startDate->format('Y/m/d')
        , 'sea_syohin_search_field49_to2[1]' => $endDate->format('Y/m/d')
        , 'company_login' => $sessionCookie->getValue()
      ];

      $action = $mainHost . '/Userinspection2/oddl'; // URLもJavaScritpによる書き換え。直接指定する。

      // 直接 URLを指定してPOST
      $crawler = $client->request('POST', $action, $params);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $requestUri = $client->getRequest()->getUri();
      $contentType = $response->getHeader('Content-Type');
      $contentLength = intval($response->getHeader('Content-Length'));
      $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);

      if ($status !== 200 || $isInvalidAccess) {
        $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
        file_put_contents($scrapingResponseDir . '/ne_order_download_page.html', $response->getContent());
        $message = $isInvalidAccess ? '不正アクセスエラー' : '';
        throw new RuntimeException("can not download csv error!! $message [ $status ][ $requestUri ][" . $response->getHeader('Content-Type') . ']');
      }

      // 取得件数に応じて、直接ダウンロードになるかプルダウン選択になるか分岐する。
      // ただし、現在は件数的にプルダウン決め打ち（5,000件以上）
      if (strpos($contentType, 'application/octet-stream') !== false) {
        throw new RuntimeException('現在、5,000件未満の受注CSVダウンロードは未実装です。');

      } else if (strpos($contentType, 'text/html') !== false) {
        try {
          $csvSelectOptions = $crawler->filter('#file_name option');
          $logger->info($csvSelectOptions->html());

        } catch (\Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      } else {
        throw new RuntimeException('unknown response');
      }

      // ここもJavaScriptでフォーム値がりがりされてる
      $sessionCookie = $client->getCookieJar()->get('company_login');
      if (!$sessionCookie) {
        throw new RuntimeException('ログインセッションのCookie取得に失敗しました。');
      }

      $form = $crawler->selectButton('上記の内容でダウンロード')->form();
      $action = $mainHost . '/Userinspection2/oddlexe';
      $params = [
          'moji' => $form->get('moji')->getValue()
        , 'params' => $form->get('params')->getValue()
        , 'company_login' => $sessionCookie->getValue()
      ];

      // 1件ずつダウンロード
      $logger->info($logExecTitle . ' ' . 'ダウンロード開始');
      $fs = new FileSystem();

      $rootDir = $container->get('kernel')->getRootDir();
      $dataDir = dirname($rootDir) . '/data/orders';
      if (!$fs->exists($dataDir)) {
        $fs->mkdir($dataDir, 0755);
      }

      $saveDir = $dataDir . '/' . (new \DateTime())->format('YmdHis');
      if ($fs->exists($saveDir)) {
        throw new RuntimeException('duplicate save directory.');
      }
      $fs->mkdir($saveDir, 0755);

      $downloadResults = [];

      $csvSelectOptions->each(function(Crawler $option) use ($logger, $client, $fs, $action, $params, $saveDir, $logExecTitle, &$downloadResults) {
        $params['file_name'] = $option->attr('value');

        $logger->info($logExecTitle . ' ' . $option->html());

        // 直接 URLを指定してPOST
        $crawler = $client->request('POST', $action, $params);

        /** @var \Symfony\Component\BrowserKit\Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $requestUri = $client->getRequest()->getUri();
        $contentType = $response->getHeader('Content-Type');
        $contentLength = intval($response->getHeader('Content-Length'));
        $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);

        if ($status !== 200 || $isInvalidAccess || strpos($contentType, 'application/octet-stream') === false) {
          $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
          file_put_contents($scrapingResponseDir . '/ne_order_download_csv.html', $response->getContent());
          $message = $isInvalidAccess ? '不正アクセスエラー' : '';
          throw new RuntimeException("can not download csv error!! $message [ $status ][ $requestUri ][" . $response->getHeader('Content-Type') . ']');
        }

        $fileName = preg_match('/filename="([a-zA-Z0-9_-]+.csv)"/', $response->getHeader('Content-Disposition'), $match)
          ? $match[1]
          : sprintf('data%s.csv', date('YmdHis00000000'));


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

        $fileInfo = $this->fileUtil->getTextFileInfo($path);
        $downloadResults[] = [
            'dir' => $saveDir
          , 'file' =>  $fileName
          , 'bytes' => $bytes
          , 'lines' => $fileInfo['lineCount']
        ];
      });

      $logger->info($logExecTitle . 'ダウンロード完了');

      $resultCount = count($downloadResults);
      $resultLines = 0;
      foreach($downloadResults as $info) {
        $resultLines += $info['lines'];
      }
      $resultStr = sprintf('file: %d / lines: %d', $resultCount, $resultLines);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($resultStr));
      $logger->logTimerFlush();

      // 引き続き、DB更新（開発用実装）
      // → 本来は、これは別Jobとして別のキューで呼び出す予定（=> 排他でのリトライを独立して行うため）
      $commandArgs = [
          'dummy'  // 引数を並べていく。最初の引数は何でもよい。
        , $saveDir
      ];
      if ($this->account) {
        $commandArgs[] = sprintf('--account=%d', $this->account->getId());
      }
      $newInput = new ArgvInput($commandArgs);
      $output = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.update_db_by_order_list_csv');
      $exitCode = $command->run($newInput, $output);
      if ($exitCode !== 0) { // コマンドが異常終了した
        throw new RuntimeException('can not update db.');
      }

      return 0;

    } catch (\Exception $e) {

      $logger->error($logExecTitle . ':' . $e->getMessage() . ':' .  $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "受注データ CSVダウンロードでエラーが発生しました。", 'error'
      );

      return 1;
    }
  }


  /**
   * 受注データ取込：照合データ取込処理 実装
   * @param $data
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importCheckCsvData($data)
  {
    /* (元実装 Access VBA)
     *
                '照合データの場合
                Call trunc("T_SALES_ANA_MATCH_TMP")
                DoCmd.TransferText acImportDelim, "", "T_SALES_ANA_MATCH_TMP", lc_path & lc_csvName, True

                Call Sql_Truncate("tb_sales_ana_match")
                CurrentDb.Execute "Q_INS_SALES_ANA_MATCH"

                'インポート済みのCSVを削除
                Kill lc_path & lc_csvName


        (Q_INS_SALES_ANA_MATCH)
        INSERT
        INTO tb_SALES_ANA_MATCH(受注年, 受注月, 総合計)
        SELECT
          Left ([dt], InStr([dt], "/") - 1) AS 式1
          , Mid([dt], InStr([dt], "/") + 1) AS 式2
          , T_SALES_ANA_MATCH_TMP.合計
        FROM
          T_SALES_ANA_MATCH_TMP;
     *
     */

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getContainer()->get('doctrine')->getConnection('main');
    $dbMain->query('TRUNCATE tb_sales_ana_match');

    $sql  = " INSERT INTO tb_sales_ana_match ( ";
    $sql .= "     受注年 ";
    $sql .= "   , 受注月 ";
    $sql .= "   , 総合計 ";
    $sql .= " ) VALUES ( ";
    $sql .= "     :year ";
    $sql .= "   , :month ";
    $sql .= "   , :total ";
    $sql .= " ) ";

    $stmt = $dbMain->prepare($sql);

    foreach($data as $row) {
      list($year, $month) = explode('/', $row['dt']);
      $stmt->bindValue(':year', intval($year), \PDO::PARAM_INT);
      $stmt->bindValue(':month', intval($month), \PDO::PARAM_INT);
      $stmt->bindValue(':total', intval(str_replace(',', '', $row['合計'])), \PDO::PARAM_INT);

      $stmt->execute();
    }

    return;
  }

}
