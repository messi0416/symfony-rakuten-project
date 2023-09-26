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
use MiscBundle\Entity\TbNetseaVendoraddress;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use \RuntimeException;

class WebCheckVendorProductsAkfCommand extends ContainerAwareCommand
{
  use WebCheckTrait;

  const NO_DATA_LIMIT = 5;
  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 3000000;
  const WAIT          = 30000;

  const SIRE_CODE     = '0290';

  protected function configure()
  {
    $this
      ->setName('batch:web-check-vendor-products-akf')
      ->setDescription('WebChecker 巡回処理実装 新商品巡回 (AKF)')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $batchLogger = $this->getLogger();
    $batchLogger->initLogTimer();

    // DB記録＆通知処理
    $logExecTitle = 'WEB巡回処理（AKF）';
    $batchLogger->setExecTitle($logExecTitle);
    $batchLogger->addDbLog($batchLogger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    // ファイルログ出力先
    $this->initFileLogger('web_checker_akf');

    try {
      $dbMain = $this->getDb('main');

      // 一時テーブル作成
      $this->createTmpVendoraddressTable();

      $webAccessUtil = $this->getWebAccessUtil();
      $client = $webAccessUtil->getWebClient();

      // AKF ログイン試行
      $webAccessUtil->akfLogin($client, 'web_checker');

      $vendor = $this->getCheckVendorBySireCode(self::SIRE_CODE);

      // 前処理: 該当vendorの 巡回アドレス チェック済みフラグをOFF
      $this->webCheckPreProcess($vendor['sire_code'], TbNetseaVendoraddress::ADDRESS_KEYWORD_AKF);

      // 在庫確認巡回
      // AKFでは新商品巡回はなし。全番号を順にチェックを行う。
      // ※ = 新商品チェック + 在庫確認 + 抜け番チェック
      $this->checkProductStocks($vendor, $client);

      // 仕入先別 チェック後処理
      $this->webCheckPostProcess($vendor['sire_code']);

      // 最終チェック日時 更新
      $sql = <<<EOD
       UPDATE tb_vendormasterdata m
       SET last_crawl_date = NOW()
       WHERE sire_code = :sireCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':sireCode', $vendor['sire_code']);
      $stmt->execute();

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
   * AKF 在庫確認巡回
   * @param array $vendor
   * @param \Goutte\Client $goutteClient
   * @throws \Doctrine\DBAL\DBALException
   */
  private function checkProductStocks($vendor, $goutteClient)
  {
    $batchLogger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'AKF 在庫確認巡回';
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '開始'));

    $fileLogger = $this->getFileLogger();
    $fileLogger->info(sprintf('在庫確認巡回(AKF): %s %s (max page: %d)', $vendor['sire_code'], $vendor['sire_name'], $vendor['max_pages']));

    $webAccessUtil = $this->getWebAccessUtil();

    // 最大商品番号取得
    $sql = <<<EOD
      SELECT
        MAX(max_product_number) AS max_product_number
      FROM  (
        SELECT
          MAX(CAST(REPLACE(na.netsea_vendoraddress, 'http://www.akf-japan.jp/product/', '') AS UNSIGNED)) AS max_product_number
        FROM tb_netsea_vendoraddress na
        WHERE na.sire_code = :sireCode
        UNION ALL
        SELECT
          MAX(CAST(REPLACE(va.sire_adress, 'http://www.akf-japan.jp/product/', '') AS UNSIGNED)) AS max_product_number
        FROM tb_vendoraddress va
        WHERE va.sire_code = :sireCode
      ) T
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':sireCode', $vendor['sire_code'], \PDO::PARAM_STR);
    $stmt->execute();
    $maxProductNumber = intval($stmt->fetchColumn(0));
    if (!$maxProductNumber) {
      $maxProductNumber = 23100; // FOR TEST 実行
    }

    // 全番号巡回。最大商品番号 + 200 から 1に向けて巡回
    $maxNumber = $maxProductNumber + 200;

    // 巡回対象商品番号
    // ページがあることがわかっている番号 = 抜け番テーブルに存在しない番号
    $sql = <<<EOD
      SELECT
          SEQ.seq AS product_number
      FROM (
         /* 連番仮想表。利用するテーブルは tb_mainproducts でなくとも件数が足りているテーブルなら何でもよい */
         SELECT @seq := 1 AS seq
         UNION
         SELECT @seq := @seq + 1 AS seq FROM tb_mainproducts
         LIMIT :maxNumber
      ) AS SEQ
      LEFT JOIN (
         SELECT
           number
         FROM tb_netsea_vendoraddress_akf_missing_number n
      ) AS MISSING ON SEQ.seq = MISSING.number
      WHERE MISSING.number IS NULL
      ORDER BY SEQ.seq DESC
EOD;
    $stmtProductNumbers = $dbMain->prepare($sql);
    $stmtProductNumbers->bindValue(':maxNumber', $maxNumber, \PDO::PARAM_INT);
    $stmtProductNumbers->execute();

    $itemRanking = 0; // 最後に商品番号が大きい順に、既存の最大ランキングの後に振るので、ランキングは暫定
    $totalCount = 0;

    do {

      $count = 0;
      $checkItems = [];

      // 100件ずつ処理
      while ($num = $stmtProductNumbers->fetchColumn(0)) {

        $totalCount++;

        $itemData = [];
        $itemData['productNumber'] = $num;
        $itemData['ranking'] = ++$itemRanking;
        $itemData['title'] = '';
        $itemData['price'] = 0;
        $itemData['setNum'] = 0;
        $itemData['retryCount'] = 0;

        $itemData['url'] = sprintf('http://www.akf-japan.jp/product/%d', $num);
        $itemData['stockUrl'] = sprintf('http://www.akf-japan.jp/stocklist/%d', $num);

        $checkItems[$itemData['url']] = $itemData;
        if (++$count >= 100) {
          break;
        }
      }

      // 全件完了でループ脱出
      if (!count($checkItems)) {
        break;
      }

      $fileLogger->info(sprintf('total: %d / %d', $totalCount, $maxNumber));

      $successItems = [];
      $failedItems = [];
      $notFoundNumbers = [];
      $round = 0;

      do {
        $requests = [];

        foreach($checkItems as $k => $item) {
          // 商品詳細ページを取得
          $requests[$k] = new Request('GET', str_replace('http://www.akf-japan.jp/', '', $item['url']));
        }

        if ($requests) {
          $handler = new CurlMultiHandler();
          $stack = HandlerStack::create($handler);
          $baseUri = 'http://www.akf-japan.jp/';
          $cookies = clone $goutteClient->getCookieJar();
          $itemClient = new Client([
              'base_uri' => $baseUri
            , 'timeout' => 30.0
            , 'handler' => $stack
            , 'delay'   => 400 // ms
            , 'curl' => [
              //   CURLOPT_RETURNTRANSFER => true
              // , CURLOPT_VERBOSE => true
            ]
            , 'cookies' => CookieJar::fromArray(
                $cookies->allRawValues($baseUri)
              , parse_url($baseUri, PHP_URL_HOST)
            )
          ]);

          $pool = new Pool($itemClient, $requests, [
              'concurrency' => 2 // 並列数

            , 'fulfilled' => function($response, $index) use ($requests, &$checkItems, &$successItems, &$failedItems, &$notFoundNumbers, $webAccessUtil, $cookies, $fileLogger) {

              $isValid = false;
              $itemData = $checkItems[$index];

              /** @var \GuzzleHttp\Psr7\Response $response */

              if ($response->getStatusCode() === 200) {

                // 商品詳細ページから商品情報を取得
                $itemCrawler = new Crawler(null, $itemData['url']);
                $itemCrawler->addContent($response->getBody(), $response->getHeaderLine('Content-Type'));

                $itemData = $this->scrapeItemInfoFromPageCrawler($itemCrawler, $itemData);

                // 在庫表ページからセット数取得取得
                if ($itemData['stockStr'] === '在庫確認はこちら') {

                  // 外側の$checkItemsループでリトライを行っているため、在庫ページ取得エラー単体はリトライしない（商品詳細から取り直しになるが簡単のため。）
                  try {
                    $setNum = null;

                    $stockClient = $webAccessUtil->getWebClient([], $cookies);
                    $stockCrawler = $stockClient->request('GET', $itemData['stockUrl']);

                    /** @var Response $stockResponse */
                    $stockResponse = $stockClient->getResponse();

                    if ($stockResponse->getStatus() === 404) { // 404 なら在庫表なし。（ありえるのか？）
                      $fileLogger->warning('AKF stock 404: ' . $itemData['stockUrl']);
                      $setNum = 0;

                    } else if ($stockResponse->getStatus() != 200 || !preg_match('|text/html|', $stockResponse->getHeader('Content-Type'))) {
                      throw new \RuntimeException('access error (stock page)');
                    } else {

                      // 件数取得成功(セット数 0でも。)
                      $stocks = null;
                      $div = $stockCrawler->filter('div.stock_data');
                      if ($div->count()) {
                        $stocks = $div->filter('table.data_table tbody td .stocklist_quantity');
                      }

                      if ($stocks && $stocks->count()) {

                        // セット数カウント
                        $setNum = $stocks->reduce(function ($node, $i) {
                          /** @var Crawler $node */
                          return in_array(trim($node->text()), [
                              '在庫あり'
                            , 'あり'
                            , '在庫有'
                            , '在庫わずか'
                            , '在庫僅'
                          ]) ? true : false;
                        })->count();

                        // 返ってきたページが違う場合、セット = 0として更新してよいパターンはあるか？ 暫定で全て失敗とする。 TODO 確認中
                      } else {
                        $fileLogger->warning($itemCrawler->html());
                        $fileLogger->warning(print_r($checkItems[$index], true));
                        throw new WebCheckVendorProductsAkfNonExpectedHtmlException('AKF 在庫表 想定外の表 確認中');
                      }
                    }

                    if (is_null($setNum)) {
                      throw new \RuntimeException('在庫表ページ取得エラー');
                    }

                    // ここまで来たら成功
                    $itemData['setNum'] = $setNum;
                    $checkItems[$index] = $itemData;
                    $isValid = true;

                    // status code 200で予期しないHTMLが取得された場合。何度取得しても同じだと仮定してリトライなしで終了
                  } catch (WebCheckVendorProductsAkfNonExpectedHtmlException $e) {

                    $failedItems[$index] = $checkItems[$index];
                    unset($checkItems[$index]);
                    return;

                  } catch (\Exception $e) {

                    $isValid = false; // 外側のリトライへ。
                  }

                // TODO 在庫文言からセット数取得 確認
                } else {

                  try {

                    if (
                         $itemData['stockStr'] == '在庫あり'
                      || $itemData['stockStr'] == '在庫数 あり'
                      || $itemData['stockStr'] == '在庫数 在庫わずか'
                    ) {
                      $fileLogger->info('no stock page: ' . $itemData['url'] . ':' . $itemData['stockStr']);
                      $itemData['setNum'] = 1;
                    } else if (strpos($itemData['stockStr'], '注文になる') !== false) {
                      $fileLogger->info('no stock page: ' . $itemData['url'] . ':' . $itemData['stockStr']);
                      $itemData['setNum'] = 0;

                    // 在庫表付き
                    } else if ($itemCrawler->filter('.variation_stock_list')->count()) {

                      $fileLogger->info('no stock page: ' . $itemData['url'] . ':（在庫表付き）');

                      $stockTable = $itemCrawler->filter('.variation_stock_list');
                      $stocks = $stockTable->filter('td span.stocklist_quantity');
                      if ($stocks && $stocks->count()) {
                        // セット数カウント ※文言は在庫表ページと同じと仮定
                        $itemData['setNum'] = $stocks->reduce(function ($node, $i) {
                          /** @var Crawler $node */
                          return in_array(trim($node->text()), [
                              '在庫あり'
                            , 'あり'
                            , '在庫有'
                            , '在庫わずか'
                            , '在庫僅'
                          ]) ? true : false;
                        })->count();
                      } else {
                        $fileLogger->info('no stock page: ' . $itemData['url'] . ':（在庫表付き => 在庫取得失敗）');
                      }

                    } else {
                      // 予期しない結果取得。ログをとり、リトライしない
                      $message = 'no stock page (unexpected): ' . $itemData['url'] . ':' . $itemData['stockStr'];
                      $fileLogger->warning($message);
                      // $fileLogger->info('error!! : ' . $response->getBody());
                      throw new WebCheckVendorProductsAkfNonExpectedHtmlException($message);
                    }

                    $checkItems[$index] = $itemData;
                    $isValid = true;

                    // status code 200で予期しないHTMLが取得された場合。何度取得しても同じだと仮定してリトライなしで終了
                  } catch (WebCheckVendorProductsAkfNonExpectedHtmlException $e) {

                    $failedItems[$index] = $checkItems[$index];
                    unset($checkItems[$index]);
                    return;

                  } catch (\Exception $e) {

                    $isValid = false; // 外側のリトライへ。
                  }
                }
              }

              if ($isValid) {

                $fileLogger->info($checkItems[$index]['url'] . ':' . $checkItems[$index]['setNum']);

                $successItems[$index] = $checkItems[$index];
                unset($checkItems[$index]);

              } else {
                $fileLogger->info('error!! : ' . $response->getBody());
                if ($checkItems[$index]['retryCount']++ > self::RETRY_LIMIT) {
                  $failedItems[$index] = $checkItems[$index];
                  unset($checkItems[$index]);
                }
              }
            },

            'rejected' => function ($reason, $index) use (&$checkItems, &$successItems, &$failedItems, &$notFoundNumbers, $fileLogger) {

              /** @var \GuzzleHttp\Exception\ClientException $reason */

              // 404なら、該当商品なし。
              if ($reason && $reason->getResponse() && $reason->getResponse()->getStatusCode() == '404') {
                $fileLogger->info('404: ' . $checkItems[$index]['url']);
                $notFoundNumbers[$index] = $checkItems[$index]['productNumber'];
                unset($checkItems[$index]);

              } else {
                $fileLogger->info('rejected:' . $index . ' => ' . ($reason ? $reason->getMessage() : '(no reason object)'));

                if ($checkItems[$index]['retryCount']++ > self::RETRY_LIMIT) {
                  $failedItems[$index] = $checkItems[$index];
                  unset($checkItems[$index]);
                }
              }
            }
          ]);

          // チェック開始
          $pool->promise()->wait();
        }

        $fileLogger->info('# round   =====================> ' . $round);
        $fileLogger->info('# remains =====================> ' . count($checkItems));
        $fileLogger->info('# success =====================> ' . count($successItems));
        $fileLogger->info('# failed  =====================> ' . count($failedItems));
        $fileLogger->info('# 404     =====================> ' . count($notFoundNumbers));

        if (count($checkItems)) {
          usleep(self::RETRY_WAIT);
        }

      } while (count($checkItems) && $round++ < 10);

      // tb_vendoraddress セット数(after), soldout, soldout_checkdate 更新
      $this->updateSetAfter($successItems);

      // tb_netsea_vendoraddress 挿入 or 更新
      // failed も、404でないという意味で挿入実行。これをしないと途中ログアウトのデータが全て0更新されてしまう。
      $this->insertOrUpdateNetseaVendorAddress($vendor, array_merge($successItems, $failedItems));

      // 抜け番保存処理
      $this->insertNotFoundNumbers($notFoundNumbers, $maxProductNumber);
    } while (true); // 次の100件へ

    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '終了'));

    $fileLogger->info(sprintf('在庫確認巡回(AKF): 終了 %s count: %d', (new \DateTime())->format('Y-m-d H:i:s'), $totalCount));
  }

  /**
   * 仕入先情報 1件取得
   *
   * @param string  $sireCode
   * @return array
   */
  private function getCheckVendorBySireCode($sireCode)
  {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT
          sire_code
        , sire_name
        , max_pages
        , 表示順
        , crawl_frequency
      FROM tb_vendormasterdata
      WHERE sire_code = :sireCode
      ORDER BY 表示順, sire_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':sireCode', $sireCode);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    return $result;
  }

  /**
   * AKF 商品一覧ページURL取得
   */
  private function getListPageUrl($pageNum = 1)
  {
    $params =[];
    if ($pageNum > 1) {
      $params['page'] = $pageNum;
    }
    return 'http://www.akf-japan.jp/product-list/0/0/photo?keyword=&num=100&img=120&order=featured' . ($params ? ('&' . http_build_query($params)) : '');
  }

  /**
   * 商品詳細ページから商品情報を取得する
   * @param Crawler $crawler
   * @param array $itemData
   * @return array
   */
  private function scrapeItemInfoFromPageCrawler($crawler, $itemData)
  {
    // 商品詳細ブロック
    $detail = $crawler->filter('#main_container .itemdetail .detail_item_data');

    if ($detail->count()) {
      // 商品名
      $name = $detail->filter('.goods_name');
      if ($name->count()) {
        $itemData['title'] = trim($name->text());
      }

      // 卸価格
      $price = $detail->filter('.selling_price #pricech');
      if ($price->count() && preg_match('/([\d,]+)円/', $price->text(), $m)) {
        $itemData['price'] = str_replace(',', '', $m[1]);
      }

      // 在庫文言
      // 「在庫確認はこちら」リンクあるいは在庫文言を探す。
      $stockLink = $crawler->selectLink('在庫確認はこちら');
      $itemData['stockStr'] = '';
      if ($stockLink->count()) {
        $itemData['stockStr'] = '在庫確認はこちら';
        $itemData['stockUrl'] = $stockLink->attr('href');
      } else {
        $stockStrBlock = $detail->filter('.detail_section.stock');
        if ($stockStrBlock->count()) {
          $itemData['stockStr'] = trim($stockStrBlock->text());
        }
      }
    }

    return $itemData;
  }


  /**
   * 商品ページが見つからなかった番号を保存
   * @param array $notFoundNumbers
   * @param int $maxProductNumber 存在する商品番号最大値。この値より大きな番号は保存しない。（将来存在する可能性がある）
   */
  private function insertNotFoundNumbers($notFoundNumbers, $maxProductNumber)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    // 一括INSERT OR UPDATE
    $insertBuilder = new MultiInsertUtil("tb_netsea_vendoraddress_akf_missing_number", [
        'fields' => [
          'number' => \PDO::PARAM_INT
      ]
      , 'prefix' => 'INSERT IGNORE INTO'
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $notFoundNumbers, function($item) use ($maxProductNumber) {

      $num = intval($item);

      if ($num > $maxProductNumber) {
        return false; // 最大商品番号より大きければスキップ
      }

      $row = [
        'number' => $num
      ];
      return $row;

    }, 'foreach');

  }
}

/// 例外
class WebCheckVendorProductsAkfNonExpectedHtmlException extends \RuntimeException {}

/*
-- 巡回済み抜け番号テーブル
CREATE TABLE tb_netsea_vendoraddress_akf_missing_number (
    number INTEGER NOT NULL PRIMARY KEY
  , check_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'WEB巡回 AKF 抜け番号 ※一度404になったので巡回しない番号リスト';
*/


