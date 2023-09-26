<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use Goutte\Client;
use MiscBundle\Entity\Repository\TbRakutenKeywordRankingLogRepository;
use MiscBundle\Entity\TbRakutenKeywordRankingLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

class ScrapeRakutenKeywordRankingItemLogCommand extends ContainerAwareCommand
{
  /** @var BatchLogger */
  private $logger;

  /** @var WebAccessUtil */
  private $webAccessUtil;

  const FETCH_ITEM_NUM = 1000; // 各1000件ずつ取得する
  const FETCH_FULL_TITLE_NUM = 100; // 完全版商品タイトルを取得する件数（各ランクごとの件数）

  const PAGE_WAIT      = 500000; // 1ページアクセスするたびの wait 初期値。臆病目に 0.5秒
  const PAGE_WAIT_ITEM = 100000; // 商品詳細へアクセスするたびの wait。やや強気に0.1秒

  protected function configure()
  {
    $this
      ->setName('batch:scrape-rakuten-keyword-ranking-item-log')
      ->setDescription('楽天 キーワードランキングページの各ランキングの商品一覧を取得・保存する')
      ->addOption('date', null, InputOption::VALUE_OPTIONAL, '対象日付', null)
      ->addOption('start-rank', null, InputOption::VALUE_OPTIONAL, '開始順位', 1)
      ->addOption('end-rank', null, InputOption::VALUE_OPTIONAL, '終了順位', 1000)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger = $this->logger;
    $logger->initLogTimer();

    $logger->info('楽天キーワードランキング 商品一覧履歴保存処理を開始しました。');

    // DB記録＆通知処理
    $logExecTitle = '楽天キーワードランキング 商品一覧履歴保存';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始', '', '', 'BatchSV01:CRON'));

    $this->webAccessUtil = $container->get('misc.util.web_access');

    $pageWait = self::PAGE_WAIT;

    try {

      $logDate = new \DateTime();
      if ($input->getOption('date')) {
        $logDate = new \DateTime($input->getOption('date'));
      }
      $logDate->setTime(0 ,0, 0);

      $startRank = intval($input->getOption('start-rank'));
      if (!$startRank) {
        $startRank = 1;
      }
      $endRank = intval($input->getOption('end-rank'));
      if (!$endRank) {
        $endRank = 1000;
      }

      // ランキング取得
      /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
      $doctrine = $container->get('doctrine');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbLog = $doctrine->getConnection('log');
      $dbLog->getConfiguration()->setSQLLogger(null); // メモリリーク対策

      // パーティション存在確認
      // ※日付を指定して実行した際に、ややこしくなる可能性あり。日付指定は現状、開発のみで利用する。
      $logTableName = 'tb_rakuten_keyword_ranking_item_log';
      $partitionName = sprintf('p%s', $logDate->format('Ymd'));

      $sql  = " SELECT ";
      $sql .= "     TABLE_SCHEMA ";
      $sql .= "   , TABLE_NAME ";
      $sql .= "   , PARTITION_NAME ";
      $sql .= "   , PARTITION_ORDINAL_POSITION ";
      $sql .= "   , TABLE_ROWS  ";
      $sql .= " FROM ";
      $sql .= "   INFORMATION_SCHEMA.PARTITIONS  ";
      $sql .= " WHERE TABLE_NAME = :tableName  ";
      $sql .= "   AND PARTITION_NAME = :partitionName ";

      $stmt = $dbLog->prepare($sql);
      $stmt->execute([
          ':tableName' => $logTableName
        , ':partitionName' => $partitionName
      ]);
      $partition = $stmt->fetch();

      // 該当日パーティション作成
      if (empty($partition)) {
        $sql  = " ALTER TABLE `{$logTableName}` REORGANIZE PARTITION pmax INTO ( ";
        $sql .= "     PARTITION `{$partitionName}` VALUES LESS THAN (:limitDate), ";
        $sql .= "     PARTITION pmax VALUES LESS THAN MAXVALUE ";
        $sql .= " ); ";

        $partitionDate = new \DateTime($logDate->format('Y-m-d 00:00:00'));
        $partitionDate->modify('+1 day');
        $stmt = $dbLog->prepare($sql);
        $stmt->bindValue(':limitDate', $partitionDate->format('Y-m-d 00:00:00'));
        $stmt->execute();
      }

      /** @var TbRakutenKeywordRankingLogRepository $repo */
      $repo = $doctrine->getRepository('MiscBundle:TbRakutenKeywordRankingLog', 'log');

      /** @var TbRakutenKeywordRankingLog[] $keywordLogs */
      $keywordLogs = $repo->findByLogDate($logDate, array('rank' => 'ASC'));

      // 楽天 商品検索画面
      $client = $this->webAccessUtil->getWebClient();

      // それぞれ指定回数ずつ取得
      foreach($keywordLogs as $log) {

        // 開始ランクまでスキップ
        if ($log->getRank() < $startRank) {
          continue;
        }
        // 終了ランクを超えていれば終了
        if ($log->getRank() > $endRank) {
          break;
        }

        $logger->info(sprintf('楽天キーワードランキング商品 rank:%s / keyword:%s', $log->getRank(), $log->getKeyword()));

        $remains = self::FETCH_ITEM_NUM;
        $page = 1;

        // 規定件数を取得するまで繰り返す
        while ($remains > 0) {

          // 一覧ページへアクセス
          $url = sprintf('http://search.rakuten.co.jp/search/mall/%s/?p=%d', $log->getKeyword(), $page);
          $crawler = $client->request('get', $url);

          $response = $client->getResponse();
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();

          $data = null;
          try {
            $data = $crawler->filter('div.rsrSResultSect');
          } catch (\InvalidArgumentException $e) {
            // do nothing
          }
          // 503 を食らった場合、アクセスをしばし見合わせる。wait も増やしてみる
          if ($status == 503) {
            $pageWait += 100000; // wait を 0.1秒増やす
            sleep(30); // 30秒待ってみる

            $logger->info('503 was returned!. increase wait, current wait is ' . round($pageWait / 1000000, 2) . ' sec.');
            continue; // 同じページをもう一度。

          } else if ($status !== 200 || !$data ) {
            throw new RuntimeException('move to rakuten search result page error!! [' . $status . '][' . $uri . ']');
          // 検索キーワードに一致する商品なし。（ランキングに乗っているのに。期間限定キーワードなど）
          } else if (!$data->count()) {
            $logger->info('no result. go to next rank');
            break; // 次のキーワードへ
          }

          // 一括登録ユーティリティ
          $builder = new MultiInsertUtil("tb_rakuten_keyword_ranking_item_log", [
            'fields' => [
                'ranking_log_id'      => \PDO::PARAM_INT
              , 'log_date'            => \PDO::PARAM_STR
              , 'rank'                => \PDO::PARAM_INT
              , 'keyword'             => \PDO::PARAM_STR
              , 'product_code'        => \PDO::PARAM_STR
              , 'product_title'       => \PDO::PARAM_STR
              , 'product_title_full'  => \PDO::PARAM_STR
              , 'catch_copy'          => \PDO::PARAM_STR
              , 'shop_code'           => \PDO::PARAM_STR
              , 'price'               => \PDO::PARAM_INT
              , 'delivery_cost_flag'  => \PDO::PARAM_INT
              , 'display_order'       => \PDO::PARAM_INT
            ]
          ]);

          // 1ページ分 ループ処理
          $data->each(function($ele) use ($log, &$remains, $builder)
          {
            // 残り件数
            if ($remains <= 0) {
              $remains = 0;
              return;
            }

            $item = [
              'ranking_log_id'  => $log->getId()
              , 'log_date'      => $log->getLogDate()->format('Y-m-d')
              , 'rank'          => $log->getRank()
              , 'keyword'       => $log->getKeyword()
              , 'product_code'  => ''
              , 'product_title' => ''
              , 'product_title_full'  => ''
              , 'catch_copy'    => ''
              , 'shop_code'     => ''
              , 'price'         => ''
              , 'delivery_cost_flag' => 0
              , 'display_order' => self::FETCH_ITEM_NUM - $remains + 1
            ];

            try {
              $item['product_title'] = trim($ele->filter('div.rsrSResultItemTxt h2 a')->text());
            } catch (\InvalidArgumentException $e) { /* do nothing */ }

            try {
              $item['catch_copy'] = trim($ele->filter('p.copyTxt')->text());
            } catch (\InvalidArgumentException $e) { /* do nothing */ }

            $itemUrl = null;
            try {
              $itemUrl = $ele->filter('div.rsrSResultItemTxt h2 a')->attr('href');
              if (preg_match('|http://item.rakuten.co.jp/([^/]+)/([^/]+)/|', $itemUrl, $match)) {
                $item['shop_code'] = $match[1];
                $item['product_code'] = $match[2];
              }
            } catch (\InvalidArgumentException $e) { /* do nothing */ }

            try {
              $price = $ele->filter('div.rsrSResultItemInfo p.price a')->text();
              $price = intval(str_replace([',', '円'], '', $price));
              $item['price'] = $price;
            } catch (\InvalidArgumentException $e) { /* do nothing */ }

            try {
              $priceText = $ele->filter('span.priceAssistText')->text();
              switch ($priceText) {
                case '送料込':
                  $item['delivery_cost_flag'] = -1;
                  break;
                case '送料別':
                  $item['delivery_cost_flag'] = 0;
                  break;
                default:
                  break;
              }
            } catch (\InvalidArgumentException $e) { /* do nothing */ }

            // 上位○件以内は、完全な商品タイトルを取得する。
            if ($remains > self::FETCH_ITEM_NUM - self::FETCH_FULL_TITLE_NUM && strlen($itemUrl)) {

              // ここについては、取れないなら取れないで仕方ない。エラー時はスキップする。
              try {
                $localClient = $this->webAccessUtil->getWebClient();
                $localCrawler = $localClient->request('get', $itemUrl);

                $response = $localClient->getResponse();
                $status = $response->getStatus();
                if ($status == 200) {
                  $item['product_title_full'] = $localCrawler->filter('#pagebody span.item_name')->text();
                }
              } catch (\Exception $e) {
                $this->logger->warn('rakuten item log: fetch item error: ' . $e->getMessage());
                // 握り潰す
              }

              // ディレイ
              usleep(self::PAGE_WAIT_ITEM);
            }

            $builder->bindRow($item);

            if ($remains % 200 == 0) { // FOR DEBUG
              // $this->logger->info('keyword: ' . $log->getKeyword() . ' / item: ' . $item['product_title'] . ' / remains: ' . $remains);
            }

            $remains--;
          });

          // 1ページ分 INSERT
          if (count($builder->binds())) {
            $stmt = $dbLog->prepare($builder->toQuery());
            $builder->bindValues($stmt);
            $stmt->execute();
          } else {
            $logger->info('rakuten_keyword_ranking_item: no bind data. something wrong ... ?');
          }

          // 次のページへ
          $logger->info('keyword: ' . $log->getKeyword() . ' / page: ' . $page . ' / remains: ' . $remains . ' / memory: ' . round(memory_get_usage() / 1024 / 1024, 4));
          if ($remains > 0) {
            $page++;

            // ディレイ
            usleep($pageWait);
          }
        }
      }

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了', '', '', 'BatchSV01:CRON'));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了', '', '', 'BatchSV01:CRON')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
