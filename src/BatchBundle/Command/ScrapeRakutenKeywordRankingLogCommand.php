<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use Goutte\Client;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response AS BrowserKitResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * 楽天 キーワードランキングページの情報を取得し、保存する
 * @author hirai original
 * @auther a-jinno Ver.2.0 取得ページをSP版に変更、新楽天検索キーワードランキングテーブルに対応
 */
class ScrapeRakutenKeywordRankingLogCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:scrape-rakuten-keyword-ranking-log')
      ->setDescription('楽天 キーワードランキングページの情報を取得し、保存する')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '楽天キーワードランキング履歴保存処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->fileUtil = $this->getContainer()->get('misc.util.file');
    $logger = $this->getLogger();

    $crawler = $this->getBaseData($logger);

    /** @var TbRakutenSearchKeywordRankingRepository $rankingRepository */
    $rankingRepository = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenSearchKeywordRanking');

    $commonUtil = $this->getDbCommonUtil();
    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');
    $em->beginTransaction();

    $logDate = new \DateTime();

    // 当日分をすべて削除（１日１セット）
    $stmt = $dbMain->prepare("DELETE FROM ${logDbName}.tb_rakuten_keyword_ranking_log WHERE log_date = :logDate ");
    $stmt->bindValue(':logDate', $logDate->format('Y-m-d'));
    $stmt->execute();
    $rankingRepository->deleteByDate($logDate);

    // 挿入SQL
    $sql  = " INSERT INTO ${logDbName}.tb_rakuten_keyword_ranking_log ( ";
    $sql .= "     log_date ";
    $sql .= "   , rank ";
    $sql .= "   , keyword ";
    $sql .= " ) VALUES ( ";
    $sql .= "     :logDate ";
    $sql .= "   , :rank ";
    $sql .= "   , :keyword ";
    $sql .= " ) ";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':logDate', $logDate->format('Y-m-d'), \PDO::PARAM_STR);

    // トップ5
    $divTop5 = $crawler->filter('div#kWdTop5 ul li');
    $divTop5->each(function($li) use ($stmt, $rankingRepository, $logDate, $logger) {
      $rank = $li->filter('.kWdRnk')->text();
      $word = $li->filter('.kWdWrd')->text();

      $stmt->bindValue(':rank', intval($rank), \PDO::PARAM_INT);
      $stmt->bindValue(':keyword', $word, \PDO::PARAM_STR); // UTF-8 になっている、はず
      $stmt->execute();

      $rankingRepository->addRanking($logDate, $rank, $word, $logger);
    });

    // 残り
    $divRest = $crawler->filter('div.kWdRest ul li');
    $divRest->each(function($li) use ($stmt, $rankingRepository, $logDate, $logger) {
      $rank = $li->filter('.kWdRestRnk')->text();
      $word = $li->filter('.kWdRestWrd')->text();

      $stmt->bindValue(':rank', intval($rank), \PDO::PARAM_INT);
      $stmt->bindValue(':keyword', $word, \PDO::PARAM_STR); // UTF-8 になっている、はず
      $stmt->execute();

      $rankingRepository->addRanking($logDate, $rank, $word, $logger);
    });

    $em->commit();

    // 処理実行ログの登録
    $this->processExecuteLog->setProcessNumber1(1000); // 常に1000
    $this->processExecuteLog->setVersion(1.0);
  }

  /**
   * return
   */
  private function getBaseData($logger) {
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');

    // 楽天から一部圧縮されたようなレスポンスが返るようになったため、Option追加（全て、HTTP REQUEST ヘッダ）
    $config = [
        'HTTP_CONNECTION' => 'keep-alive'
        , 'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
        , 'HTTP_ACCEPT_LANGUAGE' => 'ja,en-US;q=0.9,en;q=0.8'
        , 'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,br'
    ];
    $client = $webAccessUtil->getWebClient($config);

    // 楽天キーワードランキング画面へ移動 // 2020.06 PC版だと body の中身が取れないため、sp版で処理
    $url = 'https://search.rakuten.co.jp/search/keyword/smart/';
    $crawler = $client->request('get', $url);

    /** @var BrowserKitResponse $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();

    $header = null;
    try {
      $header = $crawler->filter('#kWdMainTtl h2');
      file_put_contents('/tmp/rakuten-keyword-ranking.tmp.html', $response->getContent());

    } catch (\InvalidArgumentException $e) {
      // do nothing
    }
    if ($status !== 200 || !$header || strpos($header->text(), '注目キーワード一覧') === false ) {
      throw new RuntimeException('move to rakuten keyword ranking page error!! [' . $status . '][' . $uri . ']');
    }
    $logger->info('楽天キーワード一覧画面へ遷移成功');
    return $crawler;
  }

}
