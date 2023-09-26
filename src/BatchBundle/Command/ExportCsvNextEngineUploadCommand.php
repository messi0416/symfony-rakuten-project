<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class ExportCsvNextEngineUploadCommand extends ContainerAwareCommand
{
  /** @var  FileUtil */
  private $fileUtil;

  /** @var  SymfonyUsers */
  private $account;

  const DELETE_MASTER_UPLOAD_MAX = 2000; // deleteMaster のアップロード上限制限

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-next-engine-upload')
      ->setDescription('login to NextEngine Web site and UPLOAD products and reservation CSV files.')
      ->addArgument('data-dir', InputArgument::REQUIRED, 'アップロードCSVファイル格納ディレクトリ(ディレクトリ内の該当.csvを処理)')
      ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'アップロードファイル名（オプション。指定があれば1ファイルだけアップロード）', null)
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'test') // 危険なのでデフォルトはtest
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

    $logger->info('NextEngineCSV出力処理 アップロードを開始しました。');

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
    $logExecTitle = 'NextEngineCSV出力処理 アップロード';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    try {

      // 対象ファイル格納ディレクトリ
      $dataDir = $input->getArgument('data-dir');
      $logger->info($dataDir);

      // 受注明細取込
      $fs = new FileSystem();
      $finder = new Finder();

      if (!$dataDir || !$fs->exists($dataDir)) {
        throw new RuntimeException('no data dir!! [' . $dataDir . ']');
      }

      if ($input->getOption('file')) {
        $files = $finder->in($dataDir)->name($input->getOption('file'))->files()->sortByName();
      } else {
        $files = $finder->in($dataDir)->files()->sortByName();
      }
      if (! $files->count()) {
        throw new RuntimeException('CSVファイルが出力されていませんでした。処理を終了します。 [' . $dataDir . ']');
      }

      $uploadFiles = [
          'product'             => []
        , 'delete_reservation'  => null
        , 'delete_master'       => null
        , 'update_stock'        => null
      ];
      $results = [
          'product'             => null
        , 'delete_reservation'  => null
        , 'delete_master'       => null
        , 'update_stock'        => null
      ];

      /** @var \Symfony\Component\Finder\SplFileInfo $file */
      foreach($files as $file) {
        $logger->info(print_r($file->getFileName(), true));

        switch ($file->getFileName()) {
          case 'NE_DeleteReservation.csv':
            $uploadFiles['delete_reservation'] = $file;
            break;
          case 'NE_DeleteMaster.csv':
            $uploadFiles['delete_master'] = $file;
            break;
          default:
            if (preg_match('/NE_Products(_\d+)?.csv/', $file->getFileName())) {
              $uploadFiles['product'][] = $file;
            } else if (preg_match('/NE_UpdateStock.*\.csv/', $file->getFilename())) {
              $uploadFiles['update_stock'][] = $file;
            }
            break;
        }
      }

      /** @var NextEngineMallProcess $mallProcess */
      $mallProcess = $container->get('batch.mall_process.next_engine');

      // HTMLフォーム版。 一旦おやすみ。
//      /** @var WebAccessUtil $webAccessUtil */
//      $webAccessUtil = $container->get('misc.util.web_access');
//      if ($this->account) {
//        $webAccessUtil->setAccount($this->account);
//      }
//
//      $client = $webAccessUtil->getWebClient();
//
//      // NEログイン
//      $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える
//
//      // CSVファイルアップロード
//      $uri = $client->getRequest()->getUri();
//
//      // CSVファイルアップロード画面へ遷移
//      // 「重要なお知らせ」が差し込まれる場合があり、直接URLを叩くことにします。
//      $hostName = null;
//      if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
//        $host = $match[1];
//        $uri = $host . '/Usercsvupque';
//        $crawler = $client->request('get', $uri);
//      } else {
//        throw new \RuntimeException('can not move top page error');
//      }
//
//      $logger->info($hostName);
//      $logger->info($uri);
//
//      $status = $client->getResponse()->getStatus();
//      $uri = $client->getRequest()->getUri();
//      if ($status !== 200 || !preg_match('!.next-engine.(?:org|com)/Usercsvupque!', $uri)) {
//        throw new RuntimeException('move to csv upload page error!! [' . $status . '][' . $uri . ']');
//      }
//      $logger->info('商品マスタ一括登録へ遷移成功');
//
//      $form = $crawler->selectButton('ファイルをアップロード')->form();

      // 「NE_Products」アップロード処理
      // このファイルは分割されているが、どれかでエラーが出ても最後のファイルまで上げる。
      if ($uploadFiles['product']) {
        $logger->info('商品マスタCSV アップロード試行');

        $results['product'] = [
            'success' => []
          , 'error'   => []
        ];

        $filePath = "";
        foreach($uploadFiles['product'] as $file) {
          $filePath = $file->getPathname();
          $logger->info($filePath);

          $ret = $mallProcess->apiUploadProductCsv($file);
          if ($ret['status'] == 'ok') {
            $results['product']['success'][] = $ret;
            
            // 成功時にファイルを削除
            $fs->remove($filePath);
          } else {
            $results['product']['error'][] = $ret;
          }
//          $form['csv_file']->upload($filePath);
//          $crawler = $client->submit($form);
//
//          /** @var \Symfony\Component\BrowserKit\Response $response */
//          $response = $client->getResponse();
//          $status = $response->getStatus();
//          $uri = $client->getRequest()->getUri(); // /Usercsvupque/upload
//          $header = null;
//          $message = null;
//
//          try {
//            $header = $crawler->filter('div.container-fluid div.row-fluid h3');
//            $message = $crawler->filter('div#msg.alert');
//          } catch (\Exception $e) {
//            $logger->info(get_class($e));
//            $logger->error($e->getMessage());
//            // do nothing
//          }
//          if ($status !== 200 || $response->getHeader('Content-Type') !== 'text/html; charset=UTF-8'
//            || strpos($uri, 'Usercsvupque/upload') === false
//            || strpos($header->text(), '商品マスタ一括登録') === false
//            || !$message
//          ) {
//            throw new RuntimeException('アップロード完了画面に遷移しませんでした。 [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . '][' . $filePath . ']');
//          }
//
//          $logger->info($message->text());
//
//          // 成功時
//          if (
//               preg_match('/正常に\[\d+\]件、取り込みました。/', $message->text())
//            || preg_match('/商品マスタ一括登録を予約しました。/', $message->text())
//            || preg_match('/件取り込みましたが、以下をご確認下さい/', $message->text())
//          ) {
//            $results['product']['success'][$file->getFilename()] = $message->text();
//
//            // HTML上のエラー出力取得
//          } else {
//            $results['product']['error'][$file->getFilename()] = $message->text();
//
//            $errorMessages = $this->parseHtmlErrorMessage($message->html());
//            $logger->info(print_r($errorMessages, true));
//          }
        }

        // NE_Productsにエラーがあればそこで終了
        if (count($results['product']['error'])) {
          $e = new ExportCsvNextEngineUploadException("NE_Products.csv のアップロードに失敗しました。[$filePath]");
          $e->setResults($results);
          throw $e;
        }
      }

      // 「NE_DeleteReservation」アップロード処理
      if ($uploadFiles['delete_reservation']) {
        $logger->info('予約在庫削除CSV アップロード試行');

        $filePath = $uploadFiles['delete_reservation']->getPathname();
        $logger->info($filePath);

        $ret = $mallProcess->apiUploadProductCsv($uploadFiles['delete_reservation']);
        $results['delete_reservation'][] = $ret;
        if ($ret['status'] == 'ng') {
          throw new RuntimeException('NE_DeleteReservation.csv のアップロードに失敗しました。' . $ret['message']);
        }

//        $form['csv_file']->upload($filePath);
//        $crawler = $client->submit($form);
//
//        /** @var \Symfony\Component\BrowserKit\Response $response */
//        $response = $client->getResponse();
//        $status = $response->getStatus();
//        $uri = $client->getRequest()->getUri(); // /Usercsvupque/upload
//        $header = null;
//        $message = null;
//
//        try {
//          $header = $crawler->filter('div.container-fluid div.row-fluid h3');
//          $message = $crawler->filter('div#msg.alert');
//        } catch (\Exception $e) {
//          $logger->info(get_class($e));
//          $logger->error($e->getMessage());
//          // do nothing
//        }
//        if ($status !== 200 || $response->getHeader('Content-Type') !== 'text/html; charset=UTF-8'
//          || strpos($uri, 'Usercsvupque/upload') === false
//          || strpos($header->text(), '商品マスタ一括登録') === false
//          || !$message
//        ) {
//          throw new RuntimeException('アップロード完了画面に遷移しませんでした。 [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . '][' . $filePath . ']');
//        }
//
//        $logger->info($message->text());
//
//        // 成功時
//        if (
//             preg_match('/正常に\[\d+\]件、取り込みました。/', $message->text())
//          || preg_match('/予約しました。/', $message->text()) // ざっくり
//        ) {
//          $results['delete_reservation'] = ['result' => 'success'];
//
//        // HTML上のエラー出力取得
//        } else {
//          $errorMessages = $this->parseHtmlErrorMessage($message->html());
//          $logger->info(print_r($errorMessages, true));
//          throw new RuntimeException('NE_DeleteReservation.csv のアップロードに失敗しました。[' . $filePath . '][' . count($errorMessages) . ' errors]////' . "\n\n" . $message->text());
//        }
      }

      // 「NE_UpdateStock」アップロード処理
      if ($uploadFiles['update_stock']) {
        $logger->info('在庫更新CSV アップロード試行');

        $results['update_stock'] = [
            'success' => []
          , 'error'   => []
        ];

        foreach($uploadFiles['update_stock'] as $file) {
          $filePath = $file->getPathname();
          $logger->info($filePath);

          $ret = $mallProcess->apiUploadProductCsv($file);
          if ($ret['status'] == 'ok') {
            $results['update_stock']['success'][] = $ret;
          } else {
            $results['update_stock']['error'][] = $ret;
          }

//          $form['csv_file']->upload($filePath);
//          $crawler = $client->submit($form);
//
//          /** @var \Symfony\Component\BrowserKit\Response $response */
//          $response = $client->getResponse();
//          $status = $response->getStatus();
//          $uri = $client->getRequest()->getUri(); // /Usercsvupque/upload
//          $header = null;
//          $message = null;
//
//          try {
//            $header = $crawler->filter('div.container-fluid div.row-fluid h3');
//            $message = $crawler->filter('div#msg.alert');
//          } catch (\Exception $e) {
//            $logger->info(get_class($e));
//            $logger->error($e->getMessage());
//            // do nothing
//          }
//          if ($status !== 200 || $response->getHeader('Content-Type') !== 'text/html; charset=UTF-8'
//            || strpos($uri, 'Usercsvupque/upload') === false
//            || strpos($header->text(), '商品マスタ一括登録') === false
//            || !$message
//          ) {
//            throw new RuntimeException('アップロード完了画面に遷移しませんでした。 [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . '][' . $filePath . ']');
//          }
//
//          $logger->info($message->text());
//
//          // 成功時
//          if (
//            preg_match('/正常に\[\d+\]件、取り込みました。/', $message->text())
//            || preg_match('/商品マスタ一括登録を予約しました。/', $message->text())
//            || preg_match('/件取り込みましたが、以下をご確認下さい/', $message->text())
//          ) {
//            $results['update_stock']['success'][$file->getFilename()] = $message->text();
//
//            // HTML上のエラー出力取得
//          } else {
//            $results['update_stock']['error'][$file->getFilename()] = $message->text();
//
//            $errorMessages = $this->parseHtmlErrorMessage($message->html());
//            $logger->info(print_r($errorMessages, true));
//          }
        }
      }




      // 「NE_DeleteMaster」アップロード処理
      if ($uploadFiles['delete_master']) {

          // HTMLフォーム版。ここは必要。
        /** @var WebAccessUtil $webAccessUtil */
        $webAccessUtil = $container->get('misc.util.web_access');
        if ($this->account) {
          $webAccessUtil->setAccount($this->account);
        }

        $client = $webAccessUtil->getWebClient();

        // NEログイン
        $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

        // CSVファイルアップロード
        $uri = $client->getRequest()->getUri();

        // CSVファイルアップロード画面へ遷移
        // 「重要なお知らせ」が差し込まれる場合があり、直接URLを叩くことにします。
        $hostName = null;
        if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
          $host = $match[1];
          $uri = $host . '/Usercsvupque';
          $crawler = $client->request('get', $uri);
        } else {
          throw new \RuntimeException('can not move top page error');
        }

        // マスタ削除画面へ遷移
        $uri = $hostName . '/usersyohindel';
        $crawler = $client->request('get', $uri);
        $csrfTokenInfo = $webAccessUtil->getNeCsrfTokenInfo($crawler);

        /** @var \Symfony\Component\BrowserKit\Response $response */
        $response = $client->getResponse();
        $status = $client->getResponse()->getStatus();
        $uri = $client->getRequest()->getUri();
        $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
        if ( $status !== 200 || $isInvalidAccess || !preg_match('/\.next-engine\.(?:org|com)\/usersyohindel/', $uri)) {
          $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
          file_put_contents($scrapingResponseDir . '/next_engine_product_delete.html', $response->getContent());
          $message = $isInvalidAccess ? '不正アクセスエラー' : '';
          throw new RuntimeException("move to csv upload page (master) error!! $message [ $status ][ $uri ]");
        }
        $logger->info('商品マスタ削除画面へ遷移成功');

        // csrfトークンをform追加するために、用意したhtmlからcrawlerを新規作成する
        $uploadHtmlCrawler = new Crawler($this->createHtmlUploadMasterDelete(), $uri);
        $form = $uploadHtmlCrawler->selectButton('削除するCSVファイルをアップロード') -> form();

        // 削除対象選択
        $xpath = sprintf('descendant-or-self::option[contains(concat(\' \', normalize-space(string(.)), \' \'), %s)]', Crawler::xpathLiteral('商品マスタ'));
        $selectOption = $crawler->filter('select[name="mst"]')->filterXPath($xpath);
        if (!$selectOption->attr('value')) {
          throw new RuntimeException('商品マスタ削除が選択できませんでした。');
        }
        $logger->info('商品マスタ削除CSV アップロード試行');

        $filePath = $uploadFiles['delete_master']->getPathname();
        $logger->info($filePath);
        $logger->info(filesize($filePath));

        // 多すぎるとエラーになるため、制限つきでアップロード
        $fp = fopen($filePath, 'rb');
        $tempFilePath = $filePath . '.tmp.csv';
        $tmpFp = fopen($tempFilePath, 'wb');
        $num = 0;
        while (($line = fgets($fp)) && $num++ < (self::DELETE_MASTER_UPLOAD_MAX + 1)) { // ヘッダ分 +1
          fputs($tmpFp, $line);
        }
        fclose($tmpFp);
        fclose($fp);

        $form['_n_file2']->upload($tempFilePath);
        $form['mst'] = $selectOption->attr('value');
        $form['csrf_token'] = $csrfTokenInfo['value'];

        // submit!
        $crawler = $client->submit($form);

        /** @var \Symfony\Component\BrowserKit\Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri(); // /Usercsvupque/upload
        $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
        
        $header = null;
        $message = null;

        try {
          $header = $crawler->filter('div.container-fluid div.row-fluid h3');
          $message = $crawler->filter('div.alert p');
        } catch (\Exception $e) {
          // do nothing
        }
        if ($status !== 200 || $isInvalidAccess || $response->getHeader('Content-Type') !== 'text/html; charset=UTF-8'
          || strpos($uri, 'Usersyohindel/input') === false
          || strpos($header->text(), 'マスタ削除') === false
          || !$message
        ) {
          $scrapingResponseDir = $this->fileUtil->getScrapingResponseDir();
          file_put_contents($scrapingResponseDir . '/next_engine_product_delete_upload.html', $response->getContent());
          $errorMessage = $isInvalidAccess ? '不正アクセスエラー' : '';
          throw new RuntimeException("アップロード完了画面に遷移しませんでした。 $errorMessage [ $status ][ $uri ][" . $response->getHeader('Content-Type') . '][' . $filePath . ']');
        }

        $logger->info($message->text());
        $messageText = $message->html();
        $messageText = str_replace('<br>', "\n", $messageText);
        $messageText = str_replace('&amp;', "&", $messageText);
        $messageText = str_replace('&nbsp', " ", $messageText);

        // 結果をパース
        $info = [
            'total'   => 0
          , 'success' => 0
          , 'error'   => 0
          , 'message' => $messageText
        ];
        $lines = preg_split('/[\r\n]/', $messageText);
        foreach($lines as $line) {
          $line = trim($line);
          if (preg_match('/処理件数：(\d+)件/', $line, $match)) {
            $info['total'] = $match[1];
          } else if (preg_match('/正常件数：(\d+)件/', $line, $match)) {
            $info['success'] = $match[1];
          } else if (preg_match('/異常件数：(\d+)件/', $line, $match)) {
            $info['error'] = $match[1];
          }
        }

        // エラー時
        if ($info['error']) {
          $logger->info(print_r($info, true));
          $logger->info($message->html());
          throw new RuntimeException('NE_Delete.csv のアップロードに失敗しました。[' . $filePath . '][' . $info['error'] . ' errors]' . "\n\n" . $messageText);

          // 成功時
        } else {
          $results['delete_master'] = $info;

        }
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($results));
      $logger->logTimerFlush();

      // NextEngine CSVアップロード状態取得処理 実行
      $commandArgs = [
          'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      ];
      if (!is_null($input->getOption('account'))) {
        $commandArgs[] = sprintf('--account=%d', $input->getOption('account'));
      }
      if (!is_null($input->getOption('target-env'))) {
        $commandArgs[] = sprintf('--target-env=%s', $input->getOption('target-env'));
      }
      $input = new ArgvInput($commandArgs);
      $output = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.update_ne_upload_status');
      $command->run($input, $output);

      return 0;

    } catch (ExportCsvNextEngineUploadException $e) {

      // エラー情報
      $logger->error('NextEngineCSV出力処理 アップロード:' . $e->getMessage() . ':' . print_r($e->getResults(), true)); // messageから発生行は特定できるので、スタックトレースは出さない

      // 50万件越えエラーの場合対応 50万件越えの場合は、「[006005] 内部エラー（原因不明）が発生しました。お手数ですがサポートまでご連絡下さい。」が返る
      $errorResult = $e->getResults();
      $errorMessage =  $e->getMessage();
      if (isset($errorResult['product']['error'][0]['result']['code']) && '006005' == $errorResult['product']['error'][0]['result']['code']) {
        $errorMessage = $errorMessage . "\n商品（SKU）数上限越えエラーの可能性があります。\n"
            . "手動アップロードするとエラー詳細が見られる場合があります。\n"
            . "https://main.next-engine.com/User_Syohin_Upload";
      }
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation([ 'message' => $errorMessage, 'results' => $e->getResults()])
        , true, "NextEngineCSV出力処理 アップロードでエラーが発生しました。", 'error'
      );

      return 1;

    } catch (\Exception $e) {

      // エラー情報
      $logger->error('NextEngineCSV出力処理 アップロード:' . $e->getMessage() . $e->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "NextEngineCSV出力処理 アップロードでエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * 商品マスタ削除CSVファイルをアップロード用htmlを作成する。
   * 2023/1よりcsrf_tokenがhtml描画後、jsによってform内に追加されるようになった。
   * responseへの要素追加がcrawlerやformのfunctionで行えないため、htmlを直接作成する形で対応する
   */
  private function createHtmlUploadMasterDelete()
  {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
    <title>CSV取り込み</title>
    <meta charset="utf-8">
    <base href="https://main.next-engine.com/">
    </head>
    <body>
    <form id="npout" action="/Usersyohindel/input" method="POST" enctype="multipart/form-data" class="form-horizontal">
      <input type="hidden" name="MAX_FILE_SIZE" value="1048576">
          <div id="ne_t">
              <fieldset>
                  <div class="control-group">
                      <label class="control-label">削除するマスタ</label>
                      <div class="controls">
                          <select name="mst" class="span12" style="display: block;">
                              <option value=""></option>
                              <option value="商品">商品マスタ</option>
                              <option value="セット商品">セット商品マスタ</option>
                              <option value="仕入先">仕入先マスタ</option>
                              <option value="卸先">卸先マスタ</option>
                          </select>
                      </div>
                  </div>
                  <div class="control-group">
                      <label class="control-label">コードCSVファイル</label>
                      <div class="controls">
                          <input type="file" id="_n_file2" name="_n_file2" class="span12">
                      </div>
                  </div>
                  <div class="controls">
                      <div class="alert alert-error">
                          <strong>注意事項</strong>
                          <ul>
                              <li>削除した各マスタに紐づくデータの整合性が取れなくなる恐れがございます</li>
                              <li>ご利用になられているアプリによってはデータの整合性が取れなくなる恐れがございます</li>
                              <li>代表商品コード・代表商品コードのない商品コード・セット商品コードのいずれにも紐付いていないページは削除されますのでご注意ください</li>
                          </ul>
                      </div>
                  </div>
                  <div class="controls">
                      <label class="checkbox"><input type="checkbox" id="confirm" onclick="">上記の注意事項に了承します</label>
                  </div>
              </fieldset>
              <div class="form-actions">
                  <button type="button" class="btn btn-primary" id="delete_csv_up" disabled="" onclick="">
                      削除するCSVファイルをアップロード<i class="icon-upload-alt"></i>
                  </button>
              </div>
          </div>
      <input type="hidden" name="csrf_token" value="token_value">
    </form>
    </body>
    </html>
HTML;
    
    return $html;
  }


  /**
   * HTMLエラーメッセージ パース
   */
  private function parseHtmlErrorMessage($html)
  {
    $result = [];
    $lines = preg_split('/([\r\n]|<br>)/', $html);
    foreach($lines as $line) {
      $line = trim($line);
      if (preg_match('/CSVの\[(\d+)\]行目：(.*)(?:\(商品コード:([a-zA-Z0-9_-]+)\))?<br>/', $line, $match)) {
        $result[] = [
            'line'    => $match[1]
          , 'message' => $match[2]
          , 'code'    => isset($match[3]) ? $match[3] : null
          , 'text'    => $line
        ];
      }
    }

    return $result;
  }

}

class ExportCsvNextEngineUploadException extends \RuntimeException
{
  protected $results = [];

  public function setResults($results)
  {
    $this->results = $results;
  }

  public function getResults()
  {
    return $this->results;
  }
};
