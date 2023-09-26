<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbNetseaVendoraddress;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use \RuntimeException;

class WebCheckVendorProductsVivicaDuoCommand extends ContainerAwareCommand
{
  use WebCheckTrait;

  const NO_DATA_LIMIT = 5;
  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 3000000;
  const WAIT          = 30000;

  const NUM_PER_PAGE  = 30;

  const SIRE_CODE     = '0440';

  protected function configure()
  {
    $this
      ->setName('batch:web-check-vendor-products-vivica-duo')
      ->setDescription('WebChecker 巡回処理実装 新商品巡回 (Vivica Duo)')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $batchLogger = $this->getLogger();
    $batchLogger->initLogTimer();

    // DB記録＆通知処理
    $logExecTitle = 'WEB巡回処理（Vivica Duo）';
    $batchLogger->setExecTitle($logExecTitle);
    $batchLogger->addDbLog($batchLogger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    // ファイルログ出力先
    $this->initFileLogger('web_checker_vivica_duo');

    try {
      $dbMain = $this->getDb('main');

      // 一時テーブル作成
      $this->createTmpVendoraddressTable();

      $webAccessUtil = $this->getWebAccessUtil();
      $client = $webAccessUtil->getWebClient();

      // Vivica Duo ログイン試行
      $webAccessUtil->vivicaDuoLogin($client, 'web_checker');

      $vendor = $this->getCheckVendorBySireCode(self::SIRE_CODE);

      // 前処理: 該当vendorの 巡回アドレス チェック済みフラグをOFF
      $this->webCheckPreProcess($vendor['sire_code'], TbNetseaVendoraddress::ADDRESS_KEYWORD_VIVICA_DUO);

      // 新商品巡回
      $this->checkNewProducts($vendor, $client);

      // 在庫確認巡回 => 全商品ページを当たるため不要

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
   * Vivica Duo 巡回処理
   * @param array $vendor
   * @param \Goutte\Client $client
   * @throws \Doctrine\DBAL\DBALException
   */
  private function checkNewProducts($vendor, $client)
  {
    $batchLogger = $this->getLogger();

    $logTitle = 'Vivica Duo 新商品巡回';
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '開始'));

    $dbMain = $this->getDb('main');

    $fileLogger = $this->getFileLogger();
    $fileLogger->info(sprintf('新商品巡回(Vivica Duo): %s %s (max page: %d)', $vendor['sire_code'], $vendor['sire_name'], $vendor['max_pages']));

    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '開始'));

    // 該当vendorの 巡回アドレス チェック済みフラグをOFF
    $this->webCheckPreProcess($vendor['sire_code'], TbNetseaVendoraddress::ADDRESS_KEYWORD_VIVICA_DUO);

    $pageNum = 1;

    // 1ページ目で全件数を取得し、取得最大ページを算出する。（最大ページ設定）
    $listPageUrl = $this->getListPageUrl($pageNum);
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

      $listBlock = $crawler->filter('div.main');

      if (preg_match('|全 \[(\d+)\] 商品|u', $listBlock->text(), $m)) {
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
          $batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], 'Vivica Duo 仕入れ先エラー（商品件数取得失敗）')->setInformation(['vendor' => $vendor, 'url' => $listPageUrl])
          , true
          , 'Vivica Duo 新商品巡回 仕入れ先エラー（商品件数取得失敗）'
          , 'error'
        );

        throw new \RuntimeException('トップページ取得エラー');
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
      $listPageUrl = $this->getListPageUrl($pageNum);

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

        $items = $crawler->filter('div.product-area01 > table > tr > td > table > tr')->reduce(function($node, $i){
          /** @var Crawler $node */
          // class="name" のdivがあれば対象
          return $node->filter('div.name')->count() > 0;
        });

        if (!$items || !$items->count()) {
          throw new \RuntimeException('no items');
        }

      } catch (\Exception $e) {
        $fileLogger->info(sprintf('error. 再試行 %d 回目. %s', $retryCount++, $e->getMessage()));
        if ($retryCount > self::RETRY_LIMIT) {
          $message = 'WEBチェック 新商品巡回(Vivica Duo) のエラーでリトライ回数をオーバーしました。（商品一覧画面）' . $e->getMessage();
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
          $aTag = $itemCrawler->filter('div.name a');
          $urls[] = sprintf('http://www.vsvivica.com/%s', $aTag->attr('href'));
        }

        // チェック対象のURL
        $validUrls = $this->tbVendoraddressRepo->filterActiveUrls($urls);
      }

      foreach($items as $item) {
        $itemCrawler = new Crawler($item);

        $aTag = $itemCrawler->filter('div.name a');
        $url = sprintf('http://www.vsvivica.com/%s', $aTag->attr('href'));

        if (is_array($validUrls) && !in_array($url, $validUrls)) {
          $fileLogger->info('unregistered skip url: ' . $url); // FOR DEBUG
          continue;
        }

        $itemData = [];
        $itemData['ranking'] = ++$itemRanking;
        $itemData['setNum'] = 0;
        $itemData['retryCount'] = 0;

        $itemData['url'] = $url;
        $itemData['title'] = trim($aTag->text());

        $itemData['price'] = 0;
        try {
          $itemPriceStr = trim($itemCrawler->filter('div.price .price_search')->text());
          if (preg_match('!([0-9,]+)円!u', $itemPriceStr, $m)) {
            $itemData['price'] = intval(str_replace(',', '', $m[1]));
          }
        } catch (\Exception $e) {
          $fileLogger->error('WEBチェッカー 卸価格取得失敗: ' . print_r($itemData, true) . ' => ' . $e->getMessage());
        }

        // 一覧に「SOLD OUT」があれば setNum = 0、 なければ setNum = 1 で固定（Access実装ママ）
        // → ここでは、詳細まで見に行く必要なし
        $itemData['setNum'] = strpos($itemCrawler->filter('div.price')->text(), 'SOLD OUT') !== false
          ? 0
          : 1;

        $pageItems[$itemData['url']] = $itemData;
      }

      // tb_vendoraddress セット数(after)更新
      $this->updateSetAfter($pageItems);

      // tb_netsea_vendoraddress 挿入 or 更新
      $this->insertOrUpdateNetseaVendorAddress($vendor, $pageItems);
    }

    // 最終チェック日時 更新
    $sql = <<<EOD
       UPDATE tb_vendormasterdata m
       SET last_crawl_date = NOW()
       WHERE sire_code = :sireCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':sireCode', $vendor['sire_code']);
    $stmt->execute();

    // 仕入先終了
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, $vendor['sire_name'], '終了'));

    // Vivica Duo巡回終了
    $batchLogger->addDbLog($batchLogger->makeDbLog(null, $logTitle, '終了'));
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
   * Vivica Duo 一覧ページURL取得
   */
  private function getListPageUrl($pageNum)
  {
    $params =[
      'mode' => 'srh'
    ];

    if ($pageNum > 1) {
      $params['page'] = $pageNum;
    }

    return 'http://www.vsvivica.com/?' . http_build_query($params);
  }

}
