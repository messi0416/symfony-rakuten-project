<?php

namespace BatchBundle\MallProcess;
use BatchBundle\Job\PpmCsvUploadJob;
use Goutte\Client;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * モール別特殊処理 - 楽天
 */
class PpmMallProcess extends BaseMallProcess
{
  // PPM処理中にCSVダウンロードができなくなるため、ダウンロードリクエストに失敗した場合にはリトライを行う。
  const CSV_DOWNLOAD_RETRY_COUNT = 20; // 最大20回実行
  const CSV_DOWNLOAD_RETRY_WAIT = 60; // 60秒待つ

  /**
   * PPM CSVアップロード処理
   * ※ remoteDir にファイルが無くなるまで待ってアップロードする。
   * @param array $config FTP接続設定
   * @param string $filePath アップロードファイルパス
   * @param string $remotePath リモートパス
   * @param bool $waitEmpty FTPディレクトリの同名ファイルが無くなるまで待つか。
   */
  public function uploadCsv($config, $filePath, $remotePath, $waitEmpty = true)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logTitle = 'CSVファイルアップロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, basename($filePath)));
    $logger->info('PPM CSVファイルアップロード ' . $filePath);


    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    try {
      if ($config['is_ssl']) {
        // PPM は FTPS接続。connectで失敗しても例外が飛ばないので login の成否で判定
        $ftp->ssl_connect($config['host']);
      } else {
        $ftp->connect($config['host']);
      }

      $ret = $ftp->login($config['user'], $config['password']);
      if (!$ret) {
        throw new \RuntimeException('ftp login failed.');
      }
    } catch (\Exception $e) {
      $message = 'PPMのCSVアップロード処理中、PPMのFTPにログインできませんでした。';

      $logger->error(print_r($message, true));
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path_csv']);

    $dirs = $ftp->nlist('.');
    $logger->info('ppm csv path: (search)');
    $logger->info(print_r($dirs, true));
    if (!is_array($dirs)) {
      throw new \RuntimeException('アップロード先 ファイル一覧の取得に失敗しました。');
    }

    // ディレクトリが空になるまで待つ
    if ($waitEmpty) {
      $limit = new \DateTime(); // 開始時刻
      $limit->modify('+ 6 hour'); // 6時間で諦める
      do {

        $fileList = $ftp->nlist('.');
        $remoteFileName = basename($remotePath);

        $logger->info('PPM CSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true) . ' => ' . $remoteFileName);

        if (!is_array($fileList)) {
          $message = 'PPMのCSVファイルアップロード処理中、PPMのFTPのファイル一覧が取得できませんでした。';
          throw new \RuntimeException($message);
        }

        $isExists = false;
        foreach ($fileList as $file) {
          if (basename($file) == $remoteFileName) {
            $isExists = true;
            break;
          }
        }
        if (!$isExists) {
          break;
        }

        sleep(5); // 5秒待つ

        if ($limit < new \DateTime()) {
          $message = 'PPMのCSVファイルアップロード処理中、PPMのFTPが空にならずアップロードできませんでした。処理を中止します。';
          throw new \RuntimeException($message);
        }

      } while (count($fileList));
    }

    $ftp->put($remotePath, $filePath, FTP_BINARY);
    $ftp->close();
  }

  /**
   * PPM CSVアップロード キュー追加処理
   * @param $filePath
   * @param $remoteFileName
   * @param string $execTitle
   * @param integer $accountId
   */
  public function enqueueUploadCsv($filePath, $remoteFileName, $execTitle = '', $accountId = null)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->info(sprintf('enqueue PPM csv upload : %s / %s', $filePath, $remoteFileName));

    /** @var \BCC\ResqueBundle\Resque $resque */
    $resque = $container->get('bcc_resque.resque');

    $job = new PpmCsvUploadJob();
    $job->queue = 'ppmCsvUpload'; // キュー名
    $job->args = [
        'filePath' => $filePath
      , 'remoteFileName' => $remoteFileName
      , 'execTitle' => $execTitle
    ];
    if (!is_null($accountId)) {
      $job->args['account'] = $accountId;
    }

    $resque->enqueue($job); // リトライなし
  }

  /**
   * CSVダウンロード リクエスト処理
   * @param Client $client
   * @return boolean 成功 or 失敗
   */
  public function requestProductCsvDownload(Client $client)
  {
    $logger = $this->getLogger();

    $tryCount = 0;
    $success = false;
    while ($success === false && ++$tryCount <= self::CSV_DOWNLOAD_RETRY_COUNT) {

      try {

        $requestUrl = 'https://shop.ponparemall.com/item/csvDownload/';

        $crawler = $client->request('GET', $requestUrl);
        /** @var Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        $logger->info(sprintf('request PPM csv download : %s : %s', $status, $uri));
        if ($status != '200' || $uri !== 'https://shop.ponparemall.com/item/csvDownload/') {
          throw new \RuntimeException(sprintf('PPM CSVダウンロードリクエスト処理 ダウンロードURLアクセス失敗 (%s : %s)', $status, $uri));
        }

        if ($crawler->filter('input[id="tsbmt"][name="downloadItemCsv"]')->count()) {
          $form = $crawler->selectButton('CSVファイルをダウンロード')->form();
          $logger->info('ダウンロードフォーム 表示成功');
        } else {
          $logger->debug($response->getContent());
          throw new \RuntimeException('PPM CSVダウンロードリクエスト処理 ダウンロードボタンがありません。');
        }

        // リクエスト処理
        $submitUrl = 'https://shop.ponparemall.com/item/csvDownload/downloadItemCsv'; // 商品ダウンロード・カテゴリ共用

        // 商品CSV
        $params = $form->getValues(); // hiddenパラメータ取得

        $params['csvKind'] = '1'; // 商品登録用
        $params['condition'] = '1'; // 抽出条件

        $params['saleStsChkFlg'] = '1'; // 販売ステータス
        $params['itemIdDispChkFlg'] = '1'; // 商品ID
        $params['itemNameChkFlg'] = '1'; // 商品名
        $params['catchCopyChkFlg'] = '1'; // キャッチコピー

        $params['salePriceChkFlg'] = '1'; // 販売価格
        $params['dispPriceChkFlg'] = '1'; // 表示価格
        $params['incTaxChkFlg'] = '1'; // 消費税
        $params['incShippingChkFlg'] = '1'; // 送料

        $params['dlvGrp1ItemNoChkFlg'] = '1'; // 独自送料グループ(1)
        $params['dlvGrp2ItemNoChkFlg'] = '1'; // 独自送料グループ(2)
        $params['sepaShippingFeeChkFlg'] = '1'; // 個別送料
        $params['incCodFeeChkFlg'] = '1'; // 代引料
        $params['noshiChkFlg'] = '1'; // のし対応
        $params['orderEnableChkFlg'] = '1'; // 注文ボタン
        $params['inquiryEnableChkFlg'] = '1'; // 商品問い合わせボタン
        $params['salePeriodChkFlg'] = '1'; // 販売期間指定
        $params['orderAcceptCntKindChkFlg'] = '1'; // 注文受付数
        $params['invKindChkFlg'] = '1'; // 在庫タイプ
        $params['invCntChkFlg'] = '1'; // 在庫数
        $params['invDispKindChkFlg'] = '1'; // 在庫表示
        $params['itemDescription1ChkFlg'] = '1'; // 商品説明(1)
        $params['itemDescription2ChkFlg'] = '1'; // 商品説明(2)
        $params['ItemDescriptionTextChkFlg'] = '1'; // 商品説明(テキストのみ)
        $params['itemImgUrlChkFlg'] = '1'; // 商品画像URL
        $params['genreIdChkFlg'] = '1'; // モールジャンルID
        $params['secretSalesPwChkFlg'] = '1'; // シークレットセールパスワード
        $params['pointRateChkFlg'] = '1'; // ポイント率
        $params['pointRatePeriodChkFlg'] = '1'; // ポイント率適用期間
        $params['itemDescriptionSpChkFlg'] = '1'; // 商品説明(スマートフォン用)
        $params['janCodeChkFlg'] = '1'; // JANコード
        $params['hfsNameChkFlg'] = '1'; // ヘッダー・フッター・サイドバー
        $params['msgNameChkFlg'] = '1'; // お知らせ枠
        $params['freeNoteNameChkFlg'] = '1'; // 自由告知枠
        $params['restockReqEnableChkFlg'] = '1'; // 再入荷リクエストボタン
        $params['dualPriceNameTypeChkFlg'] = '1'; // 二重価格文言タイプ

        $params['skuHaxisNameChkFlg'] = '1'; // 横軸名
        $params['skuVaxisNameChkFlg'] = '1'; // 縦軸名
        $params['skuMarkDispBorderChkFlg'] = '1'; // 在庫表示閾値

        $params['skuInvSetChkFlg'] = '1'; // SKU在庫情報一式
        $params['prchsOptSetChkFlg'] = '1'; // 購入オプション情報一式

        $params['zipArchiveFlg'] = '1'; // CSVファイルをzip形式でダウンロードする

        $params['reserveComment'] = sprintf('%s_auto_item', (new \DateTime())->format('Ymd'));
        $params['conf'] = 'ALL';

        $crawler = $client->request('POST', $submitUrl, $params);

        /** @var Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        $logger->info(sprintf('request PPM csv download (submit item) : %s : %s', $status, $uri));

        if ($status != '200' || $crawler->filter('title')->text() == 'エラー') {
          $logger->info($response->getContent());
          throw new \RuntimeException(sprintf('PPM CSVダウンロードリクエスト処理 ダウンロードURLアクセス失敗 (item : %s : %s)', $status, $uri));
        }

        // $logger->info($response->getContent());

        // 同一時刻だと同一ファイル名になるため、waitが必要。
        sleep(3);

        // カテゴリCSV
        if ($crawler->filter('input[id="tsbmt"][name="downloadItemCsv"]')->count()) {
          $form = $crawler->selectButton('CSVファイルをダウンロード')->form();
          $logger->info('ダウンロードフォーム 表示成功');
        } else {
          $logger->debug($response->getContent());
          throw new \RuntimeException('PPM CSVダウンロードリクエスト処理 ダウンロードボタンがありません。');
        }

        $params = $form->getValues(); // hiddenパラメータ取得
        $logger->info(print_r($params, true));

        $params['csvKind'] = '2'; // カテゴリ登録用
        $params['conditionCategory'] = '1'; // 全商品
        $params['zipArchiveFlg'] = '1'; // CSVファイルをzip形式でダウンロードする
        $params['reserveComment'] = sprintf('%s_auto_category', (new \DateTime())->format('Ymd'));

        $crawler = $client->request('POST', $submitUrl, $params);

        /** @var Response $response */
        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        $logger->info(sprintf('request PPM csv download (submit category) : %s : %s', $status, $uri));

        if ($status != '200' || $crawler->filter('title')->text() == 'エラー') {
          $logger->info($response->getContent());
          throw new \RuntimeException(sprintf('PPM CSVダウンロードリクエスト処理 ダウンロードURLアクセス失敗 (category : %s : %s)', $status, $uri));
        }

        // ダウンロードリクエスト成功
        $success = true;

      } catch (\Exception $e) {

        $logger->error('PPM CSVダウンロードリクエスト処理 エラー:' . $e->getMessage());

        // リトライ待ち
        sleep(self::CSV_DOWNLOAD_RETRY_WAIT);
        // 次のループへ
      }
    }

    return $success;
  }

  /**
   * 出力済み CSVダウンロード処理
   * @param Client $client
   * @param string $exportDir
   * @param \DateTime $limitDateTime
   * @param int $tryCount
   * @param int $interval
   * @return array|null
   */
  public function downloadCsv($client, $exportDir, $limitDateTime = null, $tryCount = 60, $interval = 60)
  {
    $logger = $this->getLogger();
    $requestUrl = 'https://shop.ponparemall.com/item/downloadList/';

    $try = 0;

    while ($tryCount >= $try) {

      $crawler = $client->request('GET', $requestUrl);

      /** @var Response $response */
      $response =  $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();

      $logger->info(sprintf('download PPM csv : %s : %s', $status, $uri));
      if ($status != '200' || $uri !== 'https://shop.ponparemall.com/item/downloadList/') {
        throw new \RuntimeException(sprintf('PPM CSVダウンロード処理 CSV一覧URLアクセス失敗 (%s : %s)', $status, $uri));
      }

      // 一覧テーブル
      $tables = $crawler->filter('.sectionLv2');
      if (!$tables->count()) {
        throw new \RuntimeException(sprintf('PPM CSVダウンロード処理 一覧テーブル無し (%s : %s)', $status, $uri));
      }

      /** @var Crawler $table */
      $table = null;
      $tables->each(function($ele) use (&$table, $logger) {

        /** @var Crawler $ele */
        $logger->info('header: ' . $ele->filter('h2.hdLv2')->text());

        if (trim($ele->filter('h2.hdLv2')->text()) == 'ダウンロードファイル（商品）') {
          $table = $ele->filter('table.tblType01');
          return false;
        }
        return true;
      });

      // $logger->info($table ? get_class($table) : '(no table!!)');
      if (!$table || $table->count() == 0) {
        throw new \RuntimeException('PPM CSVダウンロード処理 商品CSVテーブル無し');
      }

      // 一覧取得
      $csvFiles = [];
      $table->filter('tbody tr')->each(function($row) use (&$csvFiles, $logger) {

        /** @var Crawler $row */
        $csvFiles[] = [
            'created'  => new \DateTimeImmutable(trim($row->filter('td')->getNode(0)->textContent))
          , 'filename' => trim($row->filter('td')->getNode(1)->textContent)
          , 'comment'  => trim($row->filter('td')->getNode(2)->textContent)
          , 'url'      => $row->filter('td a')->attr('href')
        ];
      });

      // $logger->info(print_r($csvFiles, true));

      // 下限時刻があれば足切り
      if ($limitDateTime) {
        $tmp = [];
        foreach($csvFiles as $file) {
          $logger->info(sprintf('%s (%s) / created: %s / limit: %s', $file['filename'], $file['comment'], $file['created']->format('Y-m-d H:i:s'), $limitDateTime->format('Y-m-d H:i:s')));

          if ($file['created'] >= $limitDateTime) {
            $tmp[] = $file;
          }
        }
        $csvFiles = $tmp;
      }

      // 自動作成分のみ、またitem, category が同一日で揃っているものの最新をダウンロード
      $targetFiles = [];
      foreach($csvFiles as $file) {

        // URLがなければイレギュラー
        if (!$file['url']) {
          continue;
        }

        if (preg_match('/^(\d+)_auto_(.*)/', $file['comment'], $m)) {
          $date = $m[1];
          $type = $m[2];

          if (!isset($targetFiles[$date])) {
            $targetFiles[$date] = [
                'item' => null
              , 'category' => null
            ];
          }
          // すでに同じ日付のファイルがあればスキップ（新しい順にループしている前提。最新のもののみでよい。）
          if (isset($targetFiles[$date][$type])) {
            continue;
          }

          $targetFiles[$date][$type] = $file;
        }
      }

      $logger->info(print_r($targetFiles, true));
      $downloadTarget = null;
      foreach($targetFiles as $date => $files) {
        if (isset($files['item']) && isset($files['category'])) {
          $downloadTarget = $files;
        }
      }

      // ダウンロード対象が見つかればダウンロード
      if ($downloadTarget) {

        $fs = new Filesystem();
        if (!$fs->exists($exportDir)) {
          $fs->mkdir($exportDir, 0755);
        }

        // 商品
        $itemUrl = $requestUrl . $downloadTarget['item']['url'];
        $itemFilePath = sprintf('%s/item.zip', $exportDir);
        $this->doDownloadCsv($client, $itemUrl, $itemFilePath);

        // カテゴリ
        $categoryUrl = $requestUrl . $downloadTarget['category']['url'];
        $categoryFilePath = sprintf('%s/category.zip', $exportDir);
        $this->doDownloadCsv($client, $categoryUrl, $categoryFilePath);

        if ($fs->exists($itemFilePath) && $fs->exists($categoryFilePath)) {

          $zip = new \ZipArchive();

          if (!$zip->open($itemFilePath)) {
            throw new \RuntimeException(sprintf('PPM CSVダウンロード処理 zipファイルopen失敗 (%s)', $itemFilePath));
          }
          $zip->extractTo($exportDir);
          $zip->close();

          if (!$zip->open($categoryFilePath)) {
            throw new \RuntimeException(sprintf('PPM CSVダウンロード処理 zipファイルopen失敗 (%s)', $categoryFilePath));
          }
          $zip->extractTo($exportDir);
          $zip->close();

          // zip ファイルは削除
          $fs->remove($itemFilePath);
          $fs->remove($categoryFilePath);

          $files = [];
          $finder = new Finder();
          /** @var SplFileInfo $file */
          foreach($finder->in($exportDir)->files() as $file) {
            $logger->info($file->getPathname());
            $files[] = $file->getPathname();
          }

          return $files;
        }
      }

      // ダウンロードできていなければ、再試行
      $logger->info(sprintf('PPM CSVダウンロード処理 ダウンロード試行 ( %d / %d )', $try, $tryCount));
      $try++;
      sleep($interval);
    }

    return null;
  }

  /**
   * CSVファイルダウンロード処理
   * @param Client $client
   * @param string $url
   * @param string $filePath
   * @return array
   */
  private function doDownloadCsv($client, $url, $filePath)
  {
    $logger = $this->getLogger();

    $client->request('GET', $url);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    // $contentLength = intval($response->getHeader('Content-Length'));

    if ($status !== 200 || strpos($response->getHeader('Content-Type'), 'application/x-csv') === false ) {
      throw new \RuntimeException('can not download csv error!! [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . ']');
    }
    $logger->info('PPM CSVダウンロードレスポンス取得');

    $fs = new FileSystem();
    if ($fs->exists($filePath)) {
      throw new \RuntimeException('same csv name exists error!! [' . $filePath . ']');
    }

    $file = new \SplFileObject($filePath, 'w'); // 上書き
    $bytes = $file->fwrite($response->getContent());

    if (!$fs->exists($filePath)) {
      @$fs->remove($filePath);
      throw new \RuntimeException('can not save csv file. [ ' . $filePath . ' ][' . $bytes . ']');
    }

    $logger->info('PPM CSVダウンロード 出力成功。[' . $filePath . ']');

    return $filePath;
  }



}
