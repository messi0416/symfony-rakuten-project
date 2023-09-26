<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;

class Misc202006ScrapeOldRakutenKeywordRankingCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
    ->setName('misc:misc-202006-scrape-old-rakuten-keyword-ranking')
    ->setDescription('楽天検索キーワードをclimb-factoryから取得する')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from_date', null, InputOption::VALUE_OPTIONAL, '処理対象日（開始日）。この日を含む、これより後の履歴を処理する。未指定の場合エラー')
    ->addOption('to_date', null, InputOption::VALUE_OPTIONAL, '処理対象日（終了日）。この日を含む、これより前の履歴を処理する。未指定の場合エラー')
    ;
  }

  /**
   *
   *
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('楽天検索キーワード過去データ取得処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = '楽天検索キーワード過去データ取得処理';
    $logger->setExecTitle($logExecTitle);
    $logger->addDbLog($logger->makeDbLog(null, '開始'));

    try {
      $this->validate($input);

      $fromDate = new \DateTime($input->getOption('from_date'));
      $fromDate->setTime(00, 00, 00);
      $toDate = new \DateTime($input->getOption('to_date'));
      $toDate->setTime(23, 59, 59);

      // 30日ぶんごとに取得する。$fromDateを書き換えながらループ
      while ($fromDate <= $toDate) {
        $currentToDate = clone($fromDate);
        $currentToDate->add(new \DateInterval('P29D'));
        if ($currentToDate > $toDate) {
          $currentToDate = $toDate;
        }
        $this->doExecute($logger, $fromDate, $currentToDate);
        $fromDate->add(new \DateInterval('P30D'));
        sleep(10); // アクセス先負荷を考慮し、アクセス間に10秒の間隔を入れる
      }

      $logger->addDbLog($logger->makeDbLog(null, '終了'));
      $logger->logTimerFlush();

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了', 'エラー終了')->setInformation(['error' => $e->getMessage(), 'stacktrace' => $e->getTraceAsString()])
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );
      return 1;
    }
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  private function validate(InputInterface $input) {
    if (empty($input->getOption('to_date')) || empty($input->getOption('from_date'))) {
      throw new \RuntimeException('開始日、終了日は必須です');
    }
  }

  private function doExecute($logger, $fromDate, $toDate) {
    $logger->info('楽天過去データ取得 [' . $fromDate->format('Y-m-d') . ']-[' . $toDate->format('Y-m-d') . '] 開始');

    $crawler = $this->getContent($logger, $fromDate, $toDate);

    $dateList = array();
    $rankList = array();

    // 日付行を取得
    $tds = $crawler->filter('th.period');
    $tds->each(function($td) use (&$dateList, $logger) {
      $dateList[] = $td->text();
    });

    // ランクを取得
    // 表をそのまま二次元配列とする
    $trs = $crawler->filter('#ranktbl tr');
    // 1行目は無視、2行目から
    $isFirstTr = true;
    $trs->each(function($tr) use (&$rankList, &$isFirstTr, $logger) {
      if ($isFirstTr) {
        $isFirstTr = false;
        return;
      }
      $rank = null;
      if (preg_match('/ (\d*) 位/', $tr->filter('th')->text(), $match)) {
        $rank = $match[1];
      }

      $tds = $tr->filter('td');
      $tds->each(function($td) use (&$rankList, &$rank, $logger) {
        $rankList[$rank][] = trim($td->text());
      });
    });

    // 取得したランクを登録
    /** @var TbRakutenSearchKeywordRankingRepository $rankingRepository */
    $rankingRepository = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbRakutenSearchKeywordRanking');

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');
    $em->beginTransaction();

    // 取得した日付のデータが既に登録済みなら削除
    for ($i = 0; $i < count($dateList); $i++) {
      $targetDate = new \DateTime($dateList[$i]);
      $rankingRepository->deleteByDate($targetDate);
      for ($j = 1; $j <= count($rankList); $j++) { // ランクは1～1000なので1からループ
        $rankingRepository->addRanking($targetDate, $j, $rankList[$j][$i], $logger);
      }

      $em->commit(); // 1日ぶんごとにコミット
      $em->beginTransaction();
    }
    $em->commit();
    $logger->info('楽天過去データ取得 [' . $fromDate->format('Y-m-d') . ']-[' . $toDate->format('Y-m-d') . '] 終了');
  }

  private function getContent($logger, $fromDate, $toDate) {

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    $config = [
        'HTTP_CONNECTION' => 'keep-alive'
        , 'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
        , 'HTTP_ACCEPT_LANGUAGE' => 'ja,en-US;q=0.9,en;q=0.8'
        , 'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,br'
    ];
    $client = $webAccessUtil->getWebClient($config);

    $url = 'https://climb-factory.co.jp/rrnk_keyword/index.php';
    $params = ['maxrank' => 1000, 'period_s' => $fromDate->format('Y-m-d'), 'period_e'=> $toDate->format('Y-m-d'), 'jikkou' => '集計'];
    $crawler = $client->request('POST', $url, $params);

    /** @var BrowserKitResponse $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();

    $table = null;
    try {
      $table = $crawler->filter('.ranktbl');
    } catch (\InvalidArgumentException $e) {
      // do nothing
    }
    if ($status !== 200 || !$table) {
      throw new RuntimeException('move to rakuten keyword ranking page error!! [' . $status . '][' . $uri . ']');
    }

    try {
      file_put_contents('/tmp/rakuten-keyword-old-ranking.tmp.html', $response->getContent());
    } catch (\InvalidArgumentException $e) {
      $logger->error('データ取得に失敗しました');
    }
    if ($status !== 200) {
      throw new RuntimeException('move to rakuten keyword ranking page error!! [' . $status . '][' . $uri . ']');
    }
    $logger->info('楽天キーワード過去データ画面表示成功');
    return $crawler;
  }
}
