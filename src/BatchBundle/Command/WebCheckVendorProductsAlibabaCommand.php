<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\Exception\WebCheckCommandVendorSiteLogoutException;
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

class WebCheckVendorProductsAlibabaCommand extends ContainerAwareCommand
{
  use WebCheckTrait;

  const NO_DATA_LIMIT = 5;
  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 3000000;
  const WAIT          = 30000;

  const LOGIN_ACCOUNT_SETTING_NAME = 'web_checker';

  protected function configure()
  {
    $this
      ->setName('batch:web-check-vendor-products-alibaba')
      ->setDescription('WebChecker 巡回処理実装 新商品巡回 (阿里巴巴)')
      ->addOption('start-sire-code', null, InputOption::VALUE_OPTIONAL, '開始仕入れコード（スキップ・デバッグ用）')
      ->addOption('new-only', null, InputOption::VALUE_OPTIONAL, '新商品巡回のみ', 0)
      ->addOption('stock-only', null, InputOption::VALUE_OPTIONAL, '在庫確認巡回のみ', 0)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $batchLogger = $this->getLogger();
    $batchLogger->initLogTimer();

    // DB記録＆通知処理
    $logExecTitle = 'WEB巡回処理（阿里巴巴）';
    $batchLogger->setExecTitle($logExecTitle);
    $batchLogger->addDbLog($batchLogger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    // ファイルログ出力先
    $this->initFileLogger('web_checker_alibaba');

    // 新商品巡回のみ
    $doNewProducts = true;
    $doProductStocks = true;

    if (boolval($this->input->getOption('new-only'))) {
      $doProductStocks = false;
    }
    // 在庫確認巡回のみ
    if (boolval($this->input->getOption('stock-only'))) {
      $doNewProducts = false;
    }

    try {

      // 一時テーブル作成
      $this->createTmpVendoraddressTable();

      $webAccessUtil = $this->getWebAccessUtil();
      $client = $webAccessUtil->getWebClient();

      // 新商品巡回
      if ($doNewProducts) {
        $this->checkNewProducts($client);
      }
      // 在庫確認巡回
      if ($doProductStocks) {
        $this->checkProductStocks($client);
      }

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
   * 阿里巴巴 在庫確認巡回
   * 新商品巡回（＝ サイト側商品一覧総当り巡回）に漏れた登録済みアドレスの一斉巡回
   *  (新商品巡回しない仕入先、サイトの一覧から消滅、およびその他の理由で新商品巡回から漏れた仕入れアドレス)
   *
   * 件数を絞るため、checkdate および soldout_checkdate により一部ずつ順次巡回する
   *
   * [対象レコード (阿里巴巴)]
   *
   *  * checkdate < 最終新商品巡回開始日時 （ tb_updaterecord テーブル記録 ）
   *    => これにより、新商品巡回でチェックされたアドレスは除外される
   *
   *  かつ 下記のいずれか
   *
   *  * soldout = 0
   *    => soldout 判定されていないものは毎回チェック
   *
   *  * soldout = 1 かつ soldout_checkdate > 7日前
   *    => 1週間以内に soldout 判定されたアドレスは毎回チェック
   *
   *  * soldout = 1 かつ soldout_checkdate > 30日前 の 古い方から 最低1,000件 ～ 全件数の 1/7
   *    => 1か月以内に soldout 判定されたアドレスは 最大1週間程度後にはチェックされる
   *
   *  * soldout = 1 かつ soldout_checkdate > 365日前 の 古い方から 最低1,000件 ～ 全件数の 1/30
   *    => 1年以内に soldout 判定されたアドレスは 最大1か月程度後にはチェックされる
   *
   * ※ 1年以上前に soldout 判定されたアドレスはチェックしない。
   *
   * @param \Goutte\Client $client
   */
  private function checkProductStocks($client)
  {
    $batchLogger = $this->getLogger();
    $fileLogger = $this->getFileLogger();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '阿里巴巴 在庫確認巡回';
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '開始'));

    $now = new \DateTime();
    $fileLogger->info(sprintf('%s 開始 ', $logTitle, $now->format('Y-m-d H:i:s')));

    // 新商品巡回最終実行開始日時取得
    $lastCheckDate = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_WEB_CHECK_ALIBABA);
    if (!$lastCheckDate) {
      $lastCheckDate = new \DateTime();
      $lastCheckDate->setTime(0, 0, 0);
    }

    // 対象件数を条件ごとに取得(soldout判定 8～30日以内, 31日～365日以内)
    $stmtAddress = $this->getCheckProductStockAddresses($lastCheckDate, TbNetseaVendoraddress::ADDRESS_KEYWORD_ALIBABA);

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

        while (count($pageItems)) {
          try {
            // セット数を商品詳細ページから取得し、結果をsuccess, failed, notFound へ振り分ける
            $this->processPageItems($client, $pageItems, $successPageItems, $failedPageItems, $notFoundPageItems);

          } catch (WebCheckCommandVendorSiteLogoutException $e) {
            // do nothing
          }
        }

        // tb_vendoraddress セット数(after)更新（404もセット数、soldout更新のため含める）
        $this->updateSetAfter(array_merge($successPageItems, $notFoundPageItems));

        // tb_alibaba_vendoraddress セット数更新（404は除外）
        $this->updateNetseaVendorAddressSetNum($successPageItems);
      } else {
        break;
      }

    } while (true); // 次の100件へ

    $fileLogger->info(sprintf('%s 終了 ', $logTitle, $now->format('Y-m-d H:i:s')));
  }


  /**
   * 阿里巴巴 巡回処理
   * @param \Goutte\Client $client
   * @throws \Doctrine\DBAL\DBALException
   */
  private function checkNewProducts($client)
  {
    $batchLogger = $this->getLogger();
    $fileLogger = $this->getFileLogger();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '阿里巴巴 新商品巡回';
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '開始'));

    $now = new \DateTime();
    $fileLogger->info(sprintf('%s 開始 ', $logTitle, $now->format('Y-m-d H:i:s')));

    // 最終実行日時 保存
    $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_WEB_CHECK_ALIBABA, $now);

    $dbMain = $this->getDb('main');

    $vendors = $this->getCheckVendorList();
    $noDataCount = 0;

    // 中国元為替レート
    $exchangeRate = doubleval($commonUtil->getSettingValue('EXCHANGE_RATE_CNY'));

    // '====================
    // '仕入先ループ
    // '====================
    $skipped = false;
    $startSireCode = $this->input->getOption('start-sire-code');
    foreach($vendors as $vendor) {

      $fileLogger->info(sprintf('新商品巡回(阿里巴巴): %s %s (id: %s, max page: %d)', $vendor['sire_code'], $vendor['sire_name'], $vendor['alibaba_maker_id'], $vendor['max_pages']));
      $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '開始')->setLogLevel(TbLog::DEBUG));

      // 継続実行であれば、開始vendorまでスキップ
      if (strlen($startSireCode) && $vendor['sire_code'] !== $startSireCode) {
        if (!$skipped) {
          continue;
        }
      } else {
        $skipped = true; // ここまでスキップ。これ以降スキップしない
      }

      // 該当vendorの 巡回アドレス チェック済みフラグをOFF
      $this->webCheckPreProcess($vendor['sire_code'], TbNetseaVendoraddress::ADDRESS_KEYWORD_ALIBABA);

      $pageNum = 1;

      // 1ページ目で全件数を取得し、取得最大ページを算出する。（最大ページ設定）
      $retryCount = 0;
      RETRY_TOP_LIST_PAGE: // top page retry start -----------------------------------
      try {
        $crawler = $this->getListPageCrawler($client, $vendor, $pageNum);

        $lastPageNum = 0;
        $pageNavPageCount = $crawler->filter('ul li em.page-count');

        $fileLogger->info($pageNavPageCount->count());
        // $fileLogger->info($pageNavPageCount->text());

        if ($pageNavPageCount->count() && strlen($pageNavPageCount->text()) && is_numeric(trim($pageNavPageCount->text()))) {
          $lastPageNum = intval(trim($pageNavPageCount->text()));
        }

        $fileLogger->info($lastPageNum);

        if (!$lastPageNum) {
          // 商品がない場合には何もしない（エラーにするべき？）
          $fileLogger->info('no data. skip vendor.');
        }

        $fileLogger->info('last page: ' . $lastPageNum);

      } catch (\Exception $e) {
        $fileLogger->error($e->getMessage());

        if ($retryCount++ > self::RETRY_LIMIT) {
          $batchLogger->addDbLog(
            $batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '阿里巴巴 仕入れ先エラー（商品件数取得失敗）')->setInformation(['vendor' => $vendor, 'page' => $pageNum])
            , true
            , '阿里巴巴 新商品巡回 仕入れ先エラー（商品件数取得失敗）'
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
        $pageItems = []; // 一括登録・更新用

        RETRY_LIST_PAGE: // page retry start -----------------------------------
        $items = null;
        try {
          $crawler = $this->getListPageCrawler($client, $vendor, $pageNum);

          /** @var Response $response */
          $response = $client->getResponse();
          if ($response->getStatus() != 200) {
            throw new \RuntimeException('access failed. [' . $response->getStatus() . '] => ' . $response->getContent() );
          }

          $items = $crawler->filter('ul.part-offer-list-main-catalogs li');
          $fileLogger->info('list num: ' . $items->count());

          if (!$items || !$items->count()) {
            throw new \RuntimeException('no items');
          }

        } catch (\Exception $e) {
          $fileLogger->info(sprintf('error. 再試行 %d 回目. %s', $retryCount++, $e->getMessage()));
          if ($retryCount > self::RETRY_LIMIT) {
            $message = 'WEBチェック 新商品巡回(阿里巴巴) のエラーでリトライ回数をオーバーしました。（商品一覧画面）' . $e->getMessage();
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

        // 最大ページ設定は暫定的に実装なし
        foreach($items as $item) {
          $itemCrawler = new Crawler($item);
          $aTag = $itemCrawler->filter('div.title a');
          $url = $aTag->attr('href');
          if (preg_match('|^//|', $url)) {
            $url = 'https:' . $url;
          }

          $itemData = [];
          $itemData['ranking'] = ++$itemRanking;
          $itemData['setNum'] = 0;
          $itemData['retryCount'] = 0;
          $itemData['exchangeRate'] = $exchangeRate;

          $itemData['url'] = $url;
          $itemData['title'] = trim($aTag->text());
          $itemData['original_title'] = $itemData['title'];

          // URL中の数値（商品ID？）を32進数変換してタイトルに付記
          if (strlen($url) && preg_match('|/(\d+).html|', $url, $m)) {
            $itemData['title'] = sprintf('(%s) %s', base_convert($m[1], 10, 36), $itemData['title']);
          }

          $itemData['price'] = 0;
          try {
            $itemPriceStr = trim($itemCrawler->filter('div.price em')->first()->text());
            if (preg_match('/[0-9,\.]+/u', $itemPriceStr)) {
              $itemData['price'] = round(doubleval(str_replace(',', '', $itemPriceStr)) * $itemData['exchangeRate']);
            }
          } catch (\Exception $e) {
            $fileLogger->error('WEBチェッカー 卸価格取得失敗: ' . print_r($itemData, true) . ' => ' . $e->getMessage());
          }

          // $fileLogger->info(print_r($itemData, true));

          $pageItems[$itemData['url']] = $itemData;
        }

        $successPageItems = [];
        $failedPageItems = [];
        $notFoundPageItems = [];

        while (count($pageItems)) {
          try {
            // セット数を商品詳細ページから取得し、結果をsuccess, failed, notFound へ振り分ける
            $this->processPageItems($client, $pageItems, $successPageItems, $failedPageItems, $notFoundPageItems);

          } catch (WebCheckCommandVendorSiteLogoutException $e) {
            // do nothing
          }
        }

        // tb_vendoraddress セット数(after)更新
        $this->updateSetAfter($successPageItems);

        // tb_netsea_vendoraddress 挿入 or 更新
        $this->insertOrUpdateNetseaVendorAddress($vendor, $successPageItems);
      }

      // 件数取得失敗が多い場合は通知
      if ($noDataCount > self::NO_DATA_LIMIT) {
        $batchLogger->addDbLog($batchLogger->makeDbLog(null, '阿里巴巴 新商品巡回', '仕入れ先エラー（商品件数取得失敗） 多重発生')->setInformation(['count' => $noDataCount]));
        throw new \RuntimeException('仕入れ先エラー（商品件数取得失敗） 多重発生. もしかしたらメンテナンス中かもしれません');
      }

      // 仕入先別 チェック後処理
      $this->webCheckPostProcess($vendor['sire_code'], TbNetseaVendoraddress::ADDRESS_KEYWORD_ALIBABA);

      // 仕入先マスタ 最終チェック日時更新
      $sql = <<<EOD
         UPDATE tb_vendormasterdata m
         SET last_crawl_date = NOW()
         WHERE sire_code = :sireCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':sireCode', $vendor['sire_code']);
      $stmt->execute();

      // 仕入先終了
      $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '終了')->setLogLevel(TbLog::DEBUG));
    }

    // 阿里巴巴巡回終了
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
      $loggedOut = false;

      foreach($pageItems as $k => $item) {
        $requests[$k] = new Request('GET', str_replace('https://detail.1688.com/', '', $item['url']));
      }

      if ($requests) {
        $handler = new CurlMultiHandler();
        $stack = HandlerStack::create($handler);
        $baseUri = 'https://detail.1688.com/';
        // 共用クッキー
        $cookies = CookieJar::fromArray(
            $client->getCookieJar()->allRawValues($baseUri)
          , parse_url($baseUri, PHP_URL_HOST)
        );

        $itemClient = new Client([
            'base_uri' => $baseUri
          , 'timeout' => 30.0
          , 'handler' => $stack
          , 'delay'   => 300 // ms
          , 'curl' => [
            //   CURLOPT_RETURNTRANSFER => true
            // , CURLOPT_VERBOSE => true
          ]
          , 'cookies' => $cookies
        ]);

        $pool = new Pool($itemClient, $requests, array(
            'concurrency' => 2 // 並列数

          , 'fulfilled' => function($response, $index) use ($requests, &$pageItems, &$successPageItems, &$failedPageItems, &$notFoundItems, $fileLogger) {

            $isValid = false;
            /** @var \GuzzleHttp\Psr7\Response $response */
            if ($response->getStatusCode() === 200) {

              /** @var \GuzzleHttp\Psr7\Request $request */
              $request = $requests[$index];

              $crawler = new Crawler(null, $pageItems[$index]['url']);
              $crawler->addContent($response->getBody(), $response->getHeaderLine('Content-Type'));

              // 404
              $title = $crawler->filter('head title');
              if ($title->count() && preg_match('/^404-/', trim($title->text()))) {
                $fileLogger->info('404: ' . $pageItems[$index]['url']);
                $notFoundItems[$index] = $pageItems[$index];
                unset($pageItems[$index]);
                return;
              }

              $pageItems[$index]['setNum'] = 1;
              $isValid = true;

              // 最大価格取得
              $prices = $crawler->filter('div.mod-detail-price tr.price span.value');
              $maxPrice = null;
              if ($prices->count()) {
                /** @var \DOMElement $price */
                foreach($prices as $price) {
                  $priceStr = trim($price->textContent);
                  if (preg_match('/^[0-9,\.]+$/', $priceStr)) {
                    $priceVal = round(doubleval(str_replace(',', '', $priceStr)));
                    if (is_null($maxPrice) || $maxPrice < $priceVal) {
                      $maxPrice = $priceVal;
                    }
                  }
                }
                if (!is_null($maxPrice)) {
                  $pageItems[$index]['price'] = $maxPrice;
                }
              }

//              // 下記、Netsea コピペママ。現状、ページがあれば1とみなすためコメントアウト
//              // ログアウトチェック
//              if (strpos($body, '会員登録（ログイン）が必要です') !== false) {
//                throw new WebCheckCommandVendorSiteLogoutException('logged out');
//              }
//
//              // 件数取得成功(セット数 0でも。)
//              if (strpos($body, 'ご注文セット数') !== false) {
//                // セット数カウント
//                $pageItems[$index]['setNum'] = $crawler->filter('div#cartTable table td input[type="text"]')->reduce(
//                  function(Crawler $node, $i) {
//                    return preg_match('/item_num_\d+/', $node->attr('name'));
//                  }
//                )->count();
//
//                $isValid = true;
//
//                // 返ってきたページが違う場合、セット = 0として更新してよいパターン
//              }  else {
//                if (
//                     $body == '404'
//                  || strpos($body, '指定された商品アイテムIDの商品は存在しません') !== false
//                  || strpos($body, '当該商品は掲載中でないため表示できません。') !== false
//                  || strpos($body, '完売御礼 ： 申し訳ございません。こちらの商品は完売しました。') !== false
//                  || strpos($body, '販売終了 ： こちらの商品につきしまては現在販売を停止中です。') !== false
//                  || strpos($body, '当該商品は掲載期間内でないため表示できません') !== false
//                ) {
//                  $pageItems[$index]['setNum'] = 0;
//                  $isValid = true;
//                }
//              }
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
        ));

        // チェック開始
        try {
          $pool->promise()->wait();

        // ログアウト例外
        } catch (WebCheckCommandVendorSiteLogoutException $e) {
          // 並列実行からのログアウト例外は握りつぶす。（まとめて処理する）
          $loggedOut = true;
        }
      }

      $fileLogger->info('# round   =====================> ' . $round);
      $fileLogger->info('# remains =====================> ' . count($pageItems));
      $fileLogger->info('# success =====================> ' . count($successPageItems));
      $fileLogger->info('# failed  =====================> ' . count($failedPageItems));
      $fileLogger->info('# 404     =====================> ' . count($notFoundItems));

      // 処理中にログアウトしていた場合、再ログインをしてから再実行するために一度外に制御を移す
      if ($loggedOut) {
        $message = 'ログアウトしたようです。再ログインを試みるために例外送出します。';
        $fileLogger->warning($message);
        throw new WebCheckCommandVendorSiteLogoutException($message);
      }

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
        , alibaba_maker_id
        , 表示順
        , crawl_frequency
      FROM tb_vendormasterdata
      WHERE alibaba_maker_id IS NOT NULL
        AND crawl_frequency <> -1
        AND (
             last_crawl_date IS NULL
          OR crawl_frequency <= DATEDIFF(CURRENT_DATE(), last_crawl_date)
        )
      ORDER BY 表示順, sire_code
EOD;
    $result = $dbMain->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    return $result;
  }


  /**
   * 阿里巴巴 一覧ページURL取得
   */
  private function getListPageUrl($vendor, $pageNum)
  {
    $params = [
        'sortType' => 'tradenumdown'
      , 'showType' => 'catalog'
      , 'pageNum'  => $pageNum
    ];

    return sprintf('https://%s.1688.com/page/offerlist.htm?%s', $vendor['alibaba_maker_id'], http_build_query($params));
  }

  /**
   * 阿里巴巴 仕入先別 商品一覧ページ取得処理
   * @param \Goutte\Client $client
   * @param array $vendor
   * @param int $pageNum
   * @return Crawler
   */
  private function getListPageCrawler($client, $vendor, $pageNum)
  {
    $fileLogger = $this->getFileLogger();

    $listPageUrl = $this->getListPageUrl($vendor, $pageNum);
    $fileLogger->info(sprintf('page: %d / url: %s', $pageNum, $listPageUrl));

    // 一覧ページ（トップ）取得
    $crawler = $client->request('GET', $listPageUrl);

    /** @var Response $response */
    $response = $client->getResponse();
    /** @var Request $request */
    $request = $client->getRequest();

    if ($response->getStatus() != 200 || !preg_match('|text/html|', $response->getHeader('Content-Type'))) {
      throw new \RuntimeException('access error');
    }

    // ログインページヘリダイレクトされていれば、阿里巴巴TOPページでのCookie取得処理を
    // 最低限エミュレートしてみる

    // 1. https://log.mmstat.com/btob.100
    // 2. mmstat.com のクッキー cna を取得
    // 3. https://pcookie.1688.com/app.gif?&cna=クッキー値
    // 4. おもむろに一覧へ再アクセス
    $uri = $request->getUri();
    if (strpos($uri, 'login.1688.com/member/signin') !== false) {

      $fileLogger->info($uri);
      $fileLogger->info('... try again. (try to get cookie)');

      $client->request('GET', 'https://log.mmstat.com/btob.100');
      $fileLogger->info($client->getRequest()->getUri());

      $cnaCookie = $client->getCookieJar()->get('cna');
      $fileLogger->info($cnaCookie);

      if (!$cnaCookie) {
        throw new \RuntimeException('can not get cookie. [mmstat.com:cna]');
      }

      $fileLogger->info($cnaCookie->getRawValue());
      $client->request('GET', 'https://pcookie.1688.com/app.gif?&cna=' . $cnaCookie->getRawValue());

      // $fileLogger->info(print_r($client->getCookieJar()->all(), true));

      // 一覧画面へ再アクセス
      $crawler = $client->request('GET', $listPageUrl);

      /** @var Response $response */
      $response = $client->getResponse();
      /** @var Request $request */
      $request = $client->getRequest();

      if ($response->getStatus() != 200 || !preg_match('|text/html|', $response->getHeader('Content-Type'))) {
        throw new \RuntimeException('access error');
      }

      $uri = $request->getUri();
      $fileLogger->info($uri);

      if (strpos($uri, 'login.1688.com/member/signin') !== false) {
        throw new \RuntimeException('redirected sign-in page: ' . $uri);
      }
    }

    return $crawler;
  }


}

