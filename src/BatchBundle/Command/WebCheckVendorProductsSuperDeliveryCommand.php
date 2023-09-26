<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\CurlMultiHandler;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbNetseaVendoraddress;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use \RuntimeException;

class WebCheckVendorProductsSuperDeliveryCommand extends ContainerAwareCommand
{
  use WebCheckTrait;

  const NO_DATA_LIMIT = 5;
  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 3000000;
  const WAIT          = 30000;

  const NUM_PER_PAGE  = 60;

  protected function configure()
  {
    $this
      ->setName('batch:web-check-vendor-products-super-delivery')
      ->setDescription('WebChecker 巡回処理実装 新商品巡回 (SUPER DELIVERY)')
      ->addOption('start-sire-code', null, InputOption::VALUE_OPTIONAL, '開始仕入れコード（スキップ・デバッグ用）')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $batchLogger = $this->getLogger();
    $batchLogger->initLogTimer();

    // DB記録＆通知処理
    $logExecTitle = 'WEB巡回処理（SUPER DELIVERY）';
    $batchLogger->setExecTitle($logExecTitle);
    $batchLogger->addDbLog($batchLogger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    // ファイルログ出力先
    $this->initFileLogger('web_checker_super_delivery');

    try {

      // 一時テーブル作成
      $this->createTmpVendoraddressTable();

      $webAccessUtil = $this->getWebAccessUtil();
      $client = $webAccessUtil->getWebClient();

      // SUPER DELIVERY ログイン試行
      $webAccessUtil->superDeliveryLogin($client, 'web_checker');

      // 新商品巡回
      $this->checkNewProducts($client);

      // 在庫確認巡回
      $this->checkProductStocks($client);

      $batchLogger->addDbLog($batchLogger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $batchLogger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $batchLogger->error($e->getMessage());
      $batchLogger->error($e->getTraceAsString());

      $this->fileLogger->error($e->getMessage());
      $this->fileLogger->error($e->getTraceAsString());

      $batchLogger->addDbLog(
          $batchLogger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation(['message' => $e->getMessage()])
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * SUPER DELIVERY 在庫確認巡回
   * 新商品巡回（＝ サイト側商品一覧総当り巡回）に漏れた登録済みアドレスの一斉巡回
   *  (新商品巡回しない仕入先、サイトの一覧から消滅、およびその他の理由で新商品巡回から漏れた仕入れアドレス)
   *
   * 件数を絞るため、checkdate および soldout_checkdate により一部ずつ順次巡回する
   * @param \Goutte\Client $client
   */
  private function checkProductStocks($client)
  {
    $batchLogger = $this->getLogger();
    $fileLogger = $this->getFileLogger();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'SUPER DELIVERY 在庫確認巡回';
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '開始'));

    $now = new \DateTime();
    $fileLogger->info(sprintf('%s 開始 ', $logTitle, $now->format('Y-m-d H:i:s')));

    // 新商品巡回最終実行開始日時取得
    $lastCheckDate = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_WEB_CHECK_SUPER_DELIVERY);
    if (!$lastCheckDate) {
      $lastCheckDate = new \DateTime();
      $lastCheckDate->setTime(0, 0, 0);
    }

    // 対象件数を条件ごとに取得(soldout判定 8～30日以内, 31日～365日以内)
    $stmtAddress = $this->getCheckProductStockAddresses($lastCheckDate, 'superdelivery');

    // '====================
    // 'アドレスループ
    // '====================
    do {
      $count = 0;
      $pageItems = [];

      // 100件ずつ処理
      while ($address = $stmtAddress->fetch(\PDO::FETCH_ASSOC)) {
        $pageItems[$address['sire_adress']] = [
            'url'        => $address['sire_adress']
          , 'price'      => $address['price'] // 在庫確認巡回では価格は取得していないので、もとの値のまま更新される。
          , 'setNum'     => 0
          , 'retryCount' => 0
        ];

        if (++$count >= 100) {
          break;
        }
      }

      if ($pageItems) {
        $successPageItems = [];
        $failedPageItems = [];
        $notFoundPageItems = [];

        // セット数を商品詳細ページから取得し、結果をsuccess, failed, notFound へ振り分ける
        $this->processPageItems($client, $pageItems, $successPageItems, $failedPageItems, $notFoundPageItems);

        /*
        // FOR DEBUG
        foreach($successPageItems as $item) {
          $fileLogger->info(sprintf('success: %s : %s : %s', $item['url'], $item['setNum'], $item['price']));
        }
        */

        // tb_vendoraddress セット数(after)更新（404もセット数、soldoutフラグの更新のため含める）
        $this->updateSetAfter(array_merge($successPageItems, $notFoundPageItems));

        // tb_netsea_vendoraddress セット数更新(404は除外)
        $this->updateNetseaVendorAddressSetNum($successPageItems);
      } else {
        break;
      }

    } while (true); // 次の100件へ

    $fileLogger->info(sprintf('%s 終了 ', $logTitle, $now->format('Y-m-d H:i:s')));
  }

  /**
   * SUPER DELIVERY 巡回処理
   * @param \Goutte\Client $client
   * @throws \Doctrine\DBAL\DBALException
   */
  private function checkNewProducts($client)
  {
    $batchLogger = $this->getLogger();
    $fileLogger = $this->getFileLogger();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'SUPER DELIVERY 新商品巡回';
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '開始'));

    $now = new \DateTime();
    $fileLogger->info(sprintf('%s 開始 ', $logTitle, (new \DateTime())->format('Y-m-d H:i:s')));

    // 最終実行日時 保存
    $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_WEB_CHECK_SUPER_DELIVERY, $now);

    $dbMain = $this->getDb('main');

    $vendors = $this->getCheckVendorList();
    $noDataCount = 0;

    // '====================
    // '仕入先ループ
    // '====================
    $skipped = false;
    $startSireCode = $this->input->getOption('start-sire-code');
    foreach($vendors as $vendor) {

      $fileLogger->info(sprintf('新商品巡回(SUPER DELIVERY): %s %s (id: %s, max page: %d)', $vendor['sire_code'], $vendor['sire_name'], $vendor['superdelivery_maker_id'], $vendor['max_pages']));

      $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '開始')->setLogLevel(TbLog::DEBUG));

      // 継続実行であれば、開始vendorまでスキップ
      if (strlen($startSireCode) && $vendor['sire_code'] !== $startSireCode) {
        if (!$skipped) {
          continue;
        }
      } else {
        $skipped = true;
      }

      $this->webCheckPreProcess($vendor['sire_code'], TbNetseaVendoraddress::ADDRESS_KEYWORD_SUPER_DELIVERY);

      $pageNum = 1;

      // 1ページ目で全件数を取得し、取得最大ページを算出する。（最大ページ設定）
      $listPageUrl = $this->getListPageUrl($vendor, $pageNum);
      $fileLogger->info($listPageUrl);

      $retryCount = 0;
      RETRY_TOP_LIST_PAGE: // top page retry start -----------------------------------
      try {
        // 一覧ページ（トップ）取得
        $crawler = $client->request('GET', $listPageUrl);

        /** @var Response $response */
        $response = $client->getResponse();
        if ($response->getStatus() != 200 || !preg_match('|text/html|', $response->getHeader('Content-Type'))) {
          throw new \RuntimeException('access error');
        }

        $itemNum = 0;
        $lastPageNum = 0;

        $listBlock = $crawler->filter('div#jsp-tiles-productlist-c-dealer-image-list');

        if (preg_match('|（全(\d+)件）|u', $listBlock->text(), $m)) {
          $itemNum = intval($m[1]);
          $lastPageNum = ceil($itemNum / self::NUM_PER_PAGE);
        }

        if (!$itemNum || !$lastPageNum) {
          // 商品がない場合には何もしない（エラーにするべき？）
          $fileLogger->info('no data. skip vendor.');
        }

      } catch (\Exception $e) {
        $fileLogger->error($e->getMessage());

        if ($retryCount++ > self::RETRY_LIMIT) {
          $batchLogger->addDbLog(
              $batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], 'SUPER DELIVERY 仕入れ先エラー（商品件数取得失敗）')->setInformation(['vendor' => $vendor, 'url' => $listPageUrl])
            , true
            , 'SUPER DELIVERY 新商品巡回 仕入れ先エラー（商品件数取得失敗）'
            , 'error'
          );

          $noDataCount++;

          // Accessではここで再試行先に登録しているが、一旦省略

          continue; // 次の仕入先へ
        }

        usleep(self::RETRY_WAIT);
        goto RETRY_TOP_LIST_PAGE; // top page retry start -----------------------------------
      }

      $itemRanking = 0;
      // '====================
      // 'ページループ
      // '====================
      for ($pageNum = 1; $pageNum <= $lastPageNum; $pageNum++) {

        $retryCount = 0;
        $listPageUrl = $this->getListPageUrl($vendor, $pageNum);

        $fileLogger->info(sprintf('page: %d / %d : %s', $pageNum, $lastPageNum, $listPageUrl));

        $pageItems = []; // 一括登録・更新用

        RETRY_LIST_PAGE: // page retry start -----------------------------------
        $items = null;
        try {
          $crawler = $client->request('GET', $listPageUrl);
          /** @var Response $response */
          $response = $client->getResponse();
          if ($response->getStatus() != 200) {
            throw new \RuntimeException('access failed. [' . $response->getStatus() . '] => ' . $response->getContent() );
          }

          $listBlock = $crawler->filter('div#jsp-tiles-productlist-c-dealer-image-list');
          $items = $listBlock->filter('div.maincolumn-wrap div.itembox');
          if (!$items || !$items->count()) {
            throw new \RuntimeException('no items');
          }

        } catch (\Exception $e) {
          $fileLogger->info(sprintf('error. 再試行 %d 回目. %s', $retryCount++, $e->getMessage()));
          if ($retryCount > self::RETRY_LIMIT) {
            $message = 'WEBチェック 新商品巡回(SUPER DELIVERY) のエラーでリトライ回数をオーバーしました。（商品一覧画面）' . $e->getMessage();
            $fileLogger->error($message);
            throw new \RuntimeException($message);

          } else {
            usleep(self::RETRY_WAIT);
            // 同一ページをもう一度
            goto RETRY_LIST_PAGE; // page retry -----------------------------------
          }
        }

        // '====================
        // '商品ループ
        // '====================

        // [仕様]最大ページ設定より後のページは、出品依頼に登録しない。
        // → [実装]最大ページ設定がありそれを超えたページの場合には、すでにvendoraddressに存在するURLのみをチェックする。（在庫変動・商品生存チェックのみ）
        $validUrls = null;
        if ($vendor['max_pages'] && $pageNum > $vendor['max_pages']) {
          $urls = [];
          foreach($items as $item) {
            $itemCrawler = new Crawler($item);
            $aTag = $itemCrawler->filter('div.thumbnail a');
            $urls[] = sprintf('http://www.superdelivery.com%s', $aTag->attr('href'));
          }

          // チェック対象のURL
          $validUrls = $this->getVendorAddressRepo()->filterActiveUrls($urls);
        }
        $fileLogger->info('valid urls: ' . is_array($validUrls) ? count($validUrls) : '-'); // FOR DEBUG

        foreach($items as $item) {
          $itemCrawler = new Crawler($item);

          $aTag = $itemCrawler->filter('div.thumbnail a');
          $url = sprintf('http://www.superdelivery.com%s', $aTag->attr('href'));

          if (is_array($validUrls) && !in_array($url, $validUrls)) {
            $fileLogger->info('unregistered skip url: ' . $url); // FOR DEBUG
            continue;
          }

          $itemData = [];
          $itemData['ranking'] = ++$itemRanking;
          $itemData['setNum'] = 0;
          $itemData['retryCount'] = 0;

          $itemData['url'] = $url;
          $itemData['title'] = trim($aTag->filter('img')->attr('title'));

          $itemData['price'] = 0;
          try {
            $itemPriceStr = trim($itemCrawler->filter('.dealer-status .trading')->first()->text());
            if (preg_match('!\xa5([0-9,]+)/!u', $itemPriceStr, $m)) { // \xa5 => &yen; (c2a5)
              $itemData['price'] = intval(str_replace(',', '', $m[1]));
            }
          } catch (\Exception $e) {
            $fileLogger->error('WEBチェッカー 卸価格取得失敗: ' . print_r($itemData, true) . ' => ' . $e->getMessage());
          }

          $pageItems[$itemData['url']] = $itemData;
        }

        $successPageItems = [];
        $failedPageItems = [];
        $notFoundPageItems = [];

        // セット数を商品詳細ページから取得し、結果をsuccess, failed, notFound へ振り分ける
        $this->processPageItems($client, $pageItems, $successPageItems, $failedPageItems, $notFoundPageItems);

        // tb_vendoraddress セット数(after)更新
        $this->updateSetAfter($successPageItems);

        // tb_netsea_vendoraddress 挿入 or 更新
        $this->insertOrUpdateNetseaVendorAddress($vendor, $successPageItems);
      }

      // 件数取得失敗が多い場合は通知
      if ($noDataCount > self::NO_DATA_LIMIT) {
        $batchLogger->addDbLog($batchLogger->makeDbLog(null, 'SUPER DELIVERY 新商品巡回', '仕入れ先エラー（商品件数取得失敗） 多重発生')->setInformation(['count' => $noDataCount]));
        throw new \RuntimeException('仕入れ先エラー（商品件数取得失敗） 多重発生. もしかしたらメンテナンス中かもしれません');
      }

      // 仕入先別 チェック後処理
      $this->webCheckPostProcess($vendor['sire_code'], 'superdelivery');

      // 最終チェック日時 更新
      $sql = <<<EOD
         UPDATE tb_vendormasterdata m
         SET last_crawl_date_superdelivery = NOW()
         WHERE sire_code = :sireCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':sireCode', $vendor['sire_code']);
      $stmt->execute();

      // 仕入先終了
      $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '終了')->setLogLevel(TbLog::DEBUG));
    }

    // SUPER DELIVERY巡回終了
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '終了'));

    $fileLogger->info(sprintf('%s 終了 ', $logTitle, (new \DateTime())->format('Y-m-d H:i:s')));
  }


  /**
   * 商品詳細ページからsetNumを取得し配列を更新する
   * @param \Goutte\Client $client
   * @param array &$pageItems
   * @param array &$successPageItems
   * @param array &$failedPageItems
   * @param array &$notFoundItems
   */
  private function processPageItems($client, &$pageItems, &$successPageItems, &$failedPageItems, &$notFoundItems)
  {
    $fileLogger = $this->getFileLogger();
    $round = 0;

    do {
      $requests = [];

      foreach($pageItems as $k => $item) {
        $requests[$k] = new Request('GET', str_replace('http://www.superdelivery.com/', '', $item['url']));
      }

      if ($requests) {
        $handler = new CurlMultiHandler();
        $stack = HandlerStack::create($handler);
        $baseUri = 'http://www.superdelivery.com';
        $itemClient = new Client([
            'base_uri' => $baseUri
          , 'timeout' => 30.0
          , 'handler' => $stack
          , 'delay'   => 600 // ms
          , 'curl' => [
            //   CURLOPT_RETURNTRANSFER => true
            // , CURLOPT_VERBOSE => true
          ]
          , 'cookies' => CookieJar::fromArray(
            $client->getCookieJar()->allRawValues($baseUri)
            , parse_url($baseUri, PHP_URL_HOST)
          )
        ]);

        $pool = new Pool($itemClient, $requests, [
          'concurrency' => 1 // 並列数

          , 'fulfilled' => function($response, $index) use ($requests, &$pageItems, &$successPageItems, &$failedPageItems, $fileLogger) {

            $isValid = false;
            /** @var \GuzzleHttp\Psr7\Response $response */
            if ($response->getStatusCode() === 200) {

              $crawler = new Crawler(null, $pageItems[$index]['url']);
              $crawler->addContent($response->getBody(), $response->getHeaderLine('Content-Type'));

              $body = $crawler->text();
              $html = $crawler->html();

              // 件数取得成功(セット数 0でも。)
              if (strpos($html, 'jsp-tiles-productdetail-c') !== false) {

                // セット数カウント
                $pageItems[$index]['setNum'] = $crawler->filter('form#addCartItemForm td input[type="text"][name="quantities[]"]')->count();
                $isValid = true;

                // 返ってきたページが違う場合、セット = 0として更新してよいパターン
              }  else {
                if (
                  $body == '404'
                  || strpos($html, '<span class="sold-out-big">SOLD&nbsp;OUT</span>') !== false
                  || strpos($body, '現在､この商品は販売しておりません｡') !== false
                  || strpos($html, '<span class="appear-end-big">掲載終了</span>') !== false
                  || strpos($body, '古い情報からのアクセスの場合は、そのページが存在しない場合がございます') !== false
                  || strpos($body, '販売終了 ： こちらの商品につきしまては現在販売を停止中です。') !== false
                ) {
                  $pageItems[$index]['setNum'] = 0;
                  $isValid = true;
                }
              }
            }

            if ($isValid) {
              $successPageItems[$index] = $pageItems[$index];
              unset($pageItems[$index]);

            } else {
              $fileLogger->info('error!! : ' . $response->getBody());
              if ($pageItems[$index]['retryCount']++ > self::RETRY_LIMIT) {
                $failedPageItems[$index] = $pageItems[$index];
                unset($pageItems[$index]);
              }
            }
          },

          'rejected' => function ($reason, $index) use (&$pageItems, &$successPageItems, &$failedPageItems, &$notFoundItems, $fileLogger) {

            /** @var \GuzzleHttp\Exception\ClientException $reason */

            // 404なら、該当商品なし。
            $response = $reason->getResponse();
            if ($response && $response->getStatusCode() == '404') {
              $fileLogger->info('404: ' . $pageItems[$index]['url']);
              $notFoundItems[$index] = $pageItems[$index];
              unset($pageItems[$index]);

            } else {
              $fileLogger->info('rejected:' . $index . ' => ' . $reason->getMessage());
              if ($pageItems[$index]['retryCount']++ > self::RETRY_LIMIT) {
                $failedPageItems[$index] = $pageItems[$index];
                unset($pageItems[$index]);
              }
            }
          }
        ]);

        // チェック開始
        $pool->promise()->wait();
      }

      $fileLogger->info('round   =====================> ' . $round);
      $fileLogger->info('remains =====================> ' . count($pageItems));
      $fileLogger->info('success =====================> ' . count($successPageItems));
      $fileLogger->info('failed  =====================> ' . count($failedPageItems));
      $fileLogger->info('# 404   =====================> ' . count($notFoundItems));

      if (count($pageItems)) {
        usleep(self::RETRY_WAIT);
      }

    } while (count($pageItems) && $round++ < 10);

  }


  /**
   * 巡回先取得
   *
   * @return array
   */
  private function getCheckVendorList()
  {
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      SELECT
          sire_code
        , sire_name
        , max_pages
        , superdelivery_maker_id
        , 表示順
        , crawl_frequency
      FROM tb_vendormasterdata
      WHERE superdelivery_maker_id IS NOT NULL
        AND crawl_frequency <> -1
        AND (
             last_crawl_date_superdelivery IS NULL
          OR crawl_frequency <= DATEDIFF(CURRENT_DATE(), last_crawl_date_superdelivery)
        )
      ORDER BY 表示順, sire_code
EOD;
    $result = $dbMain->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    return $result;
  }

  /**
   * SUPER DELIVERY 一覧ページURL取得
   */
  private function getListPageUrl($vendor, $pageNum)
  {
    $params = [
        'so' => 'popularity'
      , 'vi' => 0
    ];

    return sprintf(
        "http://www.superdelivery.com/p/do/dpsl/%s/all/%d/?%s"
      , $vendor['superdelivery_maker_id']
      , $pageNum
      , http_build_query($params)
    );
  }

}


