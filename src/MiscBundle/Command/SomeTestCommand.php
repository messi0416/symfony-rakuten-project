<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Query;
use BatchBundle\MallProcess\RakutenMallProcess;
use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\Repository\BatchLockRepository;
use MiscBundle\Entity\Repository\TbDiscountListRepository;
use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Entity\TbDiscountList;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Util\FileLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;


class SomeTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:some-test')
      ->setDescription('なんだかんだテスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $output->writeln($this->getFileUtil()->getRootDir());

    $container = $this->getContainer();

    $this->processTest();

    // $this->entityFlushTest();

    // $this->rmsLoginTest();

    // $this->dbRepositoryManagerConnectionTest();

    // $this->getSokunouHiduke();

    // $this->testShippingDate();

    // $this->getRakutenCategories();

    // $this->guzzleMulti();

    // $this->runUploadYahooCsv();

    // $this->raiseError();

    // $this->similarCheck();

    // $this->redmineRestApiTest();

    // $this->ppmFtpConnect();

    // $this->fileLoggerTest();

    // $this->processLock();

    // $this->batchLock();

    // $this->iteratingFind();

    // $this->hydrateEntity();

    // $this->batchLock();

    // 楽天CSV出力 テスト
    // $this->rakutenExportTest();

    // repoテスト（vendoraddress）
    // $this->repoTestVendoraddress();

    // NETSEA ログイン状態テスト
    // $this->netseaLoginTest();


    $output->writeln('done!');
  }

  /**
   *
   */
  private function entityFlushTest()
  {
    $repo = $this->getDoctrine()->getRepository(TbProductchoiceitems::class);
    /** @var TbProductchoiceitems $choice */
    $choice = $repo->findOneBy(['neSyohinSyohinCode' => 'akf-WD01-M-WH']);

    var_dump(get_class($choice));
    $ware = $choice->getWarehouseTo();
    var_dump($ware ? get_class($ware) : 'none!');

    $em = $this->getDoctrine()->getManager('main');

    $m = new TbMainproducts();
    $m->setDaihyoSyohinCode('HOGE-PAGE');
    $m->setWeight(0);
    $em->persist($m);
    $em->flush();

  }


  /**
   * RMS ログイン
   */
  private function rmsLoginTest()
  {
    $logger = $this->getLogger();
    $webAccessUtil = $this->getWebAccessUtil();

    $client = $webAccessUtil->getWebClient();
    $crawler = $webAccessUtil->rmsLogin($client, 'api'); // 必要なら、アカウント名を追加して切り替える

    $logger->info('done!!');
  }

  /**
   * manager connection test
   */
  private function dbRepositoryManagerConnectionTest()
  {
//    /** @var TbLocationRepository $repo */
//    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
//    var_dump($repo->findIdsByLocationCodes(['_new', 'K']));
//    exit;

    /** @var \Doctrine\ORM\EntityManager $em1 */
    $em1 = $this->getDoctrine()->getManager('main');
    /** @var TbProductLocationLogRepository $repo1 */
    $repo1 = $em1->getRepository('MiscBundle:TbProductLocationLog');

    /** @var \Doctrine\ORM\EntityManager $em2 */
    $em2 = $this->getDoctrine()->getManager('log');
    /** @var TbProductLocationLogRepository $repo2 */
    $repo2 = $em2->getRepository('MiscBundle:TbProductLocationLog');

    var_dump(get_class($em1));
    var_dump(get_class($em2));

    var_dump($repo1->getDatabaseName());
    var_dump($repo2->getDatabaseName());

  }

  /**
   *
   */
  private function netseaLoginTest()
  {
    $logger = $this->getLogger();
    $webAccessUtil = $this->getWebAccessUtil();

    /*
    $client = $webAccessUtil->getWebClient();
    $url = 'http://www.netsea.jp/shop/279019/ven-2064041';

    // 未ログイン
    $crawler = $client->request('get', $url);

    // ログインボタンの存在チェック → JavaScriptで差し替えられている。つかえない
    $logger->info($crawler->filter('#login')->count());
    $logger->info($crawler->filter('#logout')->count());

    $webAccessUtil->netseaLogin($client, 'web_checker');

    $crawler = $client->request('get', $url);
    // ログインボタンの存在チェック
    $logger->info($crawler->filter('#login')->count());
    $logger->info($crawler->filter('#logout')->count());
    */

    // 文言
    $client = $webAccessUtil->getWebClient();
    $url = 'http://www.netsea.jp/shop/279019/ven-2064041';

    // 未ログイン
    $crawler = $client->request('get', $url);

    // 文言 '会員登録（ログイン）が必要です' の存在チェック
    $cartTable = $crawler->filter('#cartTable');
    if ($cartTable && $cartTable->count()) {
      $logger->info(strpos($cartTable->text(), '会員登録（ログイン）が必要です') !== false ? 'logged-out' : 'logged-in');
    } else {
      $logger->info('(no-cart)');
    }

    $webAccessUtil->netseaLogin($client, 'web_checker');

    $crawler = $client->request('get', $url);

    // 文言 '会員登録（ログイン）が必要です' の存在チェック
    $cartTable = $crawler->filter('#cartTable');
    if ($cartTable && $cartTable->count()) {
      $logger->info(strpos($cartTable->text(), '会員登録（ログイン）が必要です') !== false ? 'logged-out' : 'logged-in');
    } else {
      $logger->info('(no-cart)');
    }

    // ログアウトしてみる
    $crawler = $webAccessUtil->netseaLogout($client);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    /** @var \Symfony\Component\BrowserKit\Request $request */
    $request = $client->getRequest();
    $response->getStatus();
    $logger->info($response->getStatus() . ' : ' . $request->getUri());

    // 商品詳細ページ
    $crawler = $client->request('get', $url);
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    /** @var \Symfony\Component\BrowserKit\Request $request */
    $request = $client->getRequest();
    $response->getStatus();
    $logger->info($response->getStatus() . ' : ' . $request->getUri());

    // 文言 '会員登録（ログイン）が必要です' の存在チェック
    $cartTable = $crawler->filter('#cartTable');
    if ($cartTable && $cartTable->count()) {
      $logger->info(strpos($cartTable->text(), '会員登録（ログイン）が必要です') !== false ? 'logged-out' : 'logged-in');
    } else {
      $logger->info('(no-cart)');
    }
  }


  /**
   *
   */
  private function repoTestVendoraddress()
  {
    /** @var TbVendoraddressRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbVendoraddress');
    $urls = [
    ];

    $result = $repo->filterActiveUrls($urls);
    var_dump($result);

    $urls = [
      'http://test'
    ];
    $result = $repo->filterActiveUrls($urls);
    var_dump($result);

    $urls = [
        'http://www.netsea.jp/shop/11827/0-200a'
      , 'http://hogehoge'
      , 'http://www.netsea.jp/shop/40737/00-5998013'
      , 'http://www.superdelivery.com/p/r/pd_p/3299236/'
      , 'http://mogemoge'
      , 'てすとてすと'
    ];
    $result = $repo->filterActiveUrls($urls);
    var_dump($result);

    echo "done!!";
    return;
  }


  /**
   * hydrate テスト → 失敗。断念。
   */
  private function hydrateEntity()
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
    $doctrine = $this->getContainer()->get('doctrine');

    /** @var EntityManager $em */
    $em = $doctrine->getManager();

    /** @var \Doctrine\DBAL\Connection[] */
    $dbMain = $doctrine->getConnection('main');

    $unitOfWork = $em->getUnitOfWork();
    $persister = $unitOfWork->getEntityPersister('MiscBundle:TbDiscountList');

    var_dump(get_class($persister));
    var_dump(get_class($persister->getResultSetMapping()));

    $hydrator = $em->newHydrator(Query::HYDRATE_OBJECT);
    var_dump(get_class($hydrator));

    // ※ この辺で、結局 SQLを発行する？流れになり、ただの配列をhydrateする、
    // という処理がなぜか protected なため挫折。
    // public メソッドはとにかく SQLを発行する謎仕様？



    // それでも適当こいてやってみる。

    $test = new TestHydrator($em);
    $test->setHydrator($hydrator);

    $row = [
        "daihyo_syohin_code"=> "0958702"
      , "stock_amount"=> "1"
      , "last_orderdate"=> "2015-11-11"
      , "sales_start_date"=> "2015-07-11"
      , "discount_base_date"=> "2015-11-11"
      , "discount_terminal_date"=>NULL
      , "sales_amount"=> "1"
      , "expected_daily_sales_amount"=> "0.0111"
      , "estimated_sales_days"=> "0"
      , "sell_out_days"=> "90"
      , "sell_out_date"=>NULL
      , "sell_out_over_days"=> "0"
      , "genka_tnk"=> "500"
      , "genka_tnk_ave"=> "500"
      , "base_price"=> "0"
      , "current_price"=> "580"
      , "discount_price"=> "0"
      , "discount_rate"=> "0.00"
      , "pricedown_flg"=> "-1"
      , "pricedown_flg_pre"=> "-1"
      , "additional_cost"=> "0"
      , "fixed_cost"=> "0"
      , "cost_rate"=> "65"
      , 'created' => (new \DateTime())->format('Y-m-d H:i:s')
      , 'updated' => (new \DateTime())->format('Y-m-d H:i:s')
    ];

    $obj = $test->hydrate($row);

    // $hydrator->hydrateAll($stmt, $this->currentPersisterContext->rsm, array(Query::HINT_REFRESH => true));

    var_dump($obj);
  }

  /**
   * １件ずつ取得処理
   */
  private function iteratingFind()
  {
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
    $doctrine = $this->getContainer()->get('doctrine');

    /** @var EntityManager $em */
    $em = $doctrine->getManager();

    /** @var \Doctrine\DBAL\Connection[] */
    $dbMain = $doctrine->getConnection('main');

    // var_dump(get_class($doctrine));
    // var_dump(get_class($em));
    // var_dump(get_class($dbMain));

    /** @var TbDiscountListRepository $repo */
    $repo = $doctrine->getRepository('MiscBundle:TbDiscountList');

    $qb = $repo->createQueryBuilder('d');
    // $i = $qb->getQuery()->iterate(null, Query::HYDRATE_OBJECT); // default
    $i = $qb->getQuery()->iterate(null, Query::HYDRATE_ARRAY); // array

    foreach($i as $row) {
      /** @var TbDiscountList $d */
      $d = $row[0];
      // var_dump($d->toScalarArray());
      var_dump($row);
      break;
    }
  }

  /**
   * 楽天CSV出力テスト
   */
  private function rakutenExportTest()
  {
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');
    $processor->createPankuzuList($logger);
  }

  /**
   * バッチ緊急停止処理 テスト
   */
  private function batchLock()
  {
    /** @var BatchLockRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:BatchLock');

    $webAccessUtil = $this->getWebAccessUtil();

    // $lock = $repo->lock(BatchLock::BATCH_CODE_RMS_LOGIN);

    // 1回目
    try {
      $client = $webAccessUtil->getWebClient();
      $crawler = $webAccessUtil->rmsLogin($client, 'api'); // 必要なら、アカウント名を追加して切り替える

    } catch (\Exception $e) {
      var_dump($e->getMessage());
      throw $e;
    }

    echo "done!";

    var_dump($lock);
  }

  /**
   * 処理排他制御 テスト
   */
  private function processLock()
  {
    $util = $this->commonUtil;

    /** @var FileLogger $logger */
    $logger = $this->getContainer()->get('misc.util.file_logger')->setFileName('process_lock');

    $processName = 'wait process lock test';
    $limit = (new \DateTime())->modify('+30 second');
    $ret = $util->waitRunningProcessLock($processName, 3, $limit, $logger);
    var_dump($ret);

    $util->deleteRunningLog($processName);
    return;
  }


  /**
   * ファイルロガーテスト
   */
  private function fileLoggerTest()
  {
    /** @var FileLogger $logger */
    $logger = $this->getContainer()->get('misc.util.file_logger')->setFileName('misc');

    $logger->info('テストー');
    return;
  }

  /**
   * PPM FTP接続テスト
   */
  private function ppmFtpConnect()
  {
    $container = $this->getContainer();
    $logger = $this->logger;

    /*
    ftp_ppm:
        host: ftps.ponparemall.com
        user: F1082746
        password: Yoshiko88+
        path: /imageUpload/images
     */

    $config = $container->getParameter('ftp_ppm');
    /**/
    $config = [
        'host'     => 'ftps.ponparemall.com'
      , 'user'     => 'F1082746'
      , 'password' => 'Yoshiko88+'
      , 'path'     => '/imageUpload/images'
    ];
    /**/
    $logger->info(print_r($config, true));

    if (!$config) {
      throw new \RuntimeException('no ftp config (PPM image upload)');
    }

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    $ftp->ssl_connect($config['host']);
    try {
      $ret = $ftp->login($config['user'], $config['password']);
      if (!$ret) {
        throw new \RuntimeException('ftp login failed.');
      }
    } catch (\Exception $e) {
      $message = 'PPMの画像アップロード処理中、PPMのFTPにログインできませんでした。';

      $logger->error(print_r($message, true));
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    // $logger->info(print_r($ftp->nlist('./'), true));
    // $logger->info(print_r($ftp->rawlist('./'), true));

    $ftp->chdir($config['path']); // /imageUpload/images へ移動

    // $logger->info(print_r($ftp->nlist('./'), true));
    // $logger->info(print_r($ftp->rawlist('./'), true));

    $logger->info('done!');
  }

  /**
   * 類似画像チェック テスト
   */
  private function similarCheck()
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $db = $this->getContainer()->get('doctrine')->getConnection('main');

    // puzzle_set_lambdas(16);
    puzzle_set_lambdas($this->getContainer()->getParameter('product_image_similar_check_lambda'));

    $base = '/home/workuser/product_images_original/itempic499/ven-2011908.jpg';
    $baseSign = puzzle_fill_cvec_from_file($base);

    $sql = <<<EOD
       SELECT
         directory,
         filename,
         similar_sign
       FROM product_images
       WHERE similar_sign <> ''
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $minDiff = null;
    $minDiffPath = '';
    $count = 0;
    $sampleList = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $path = $row['directory'] . '/' . $row['filename'];
      $this->output->writeln($path);

      $sign = $stringUtil->reverseBinaryToBase64WithDeflate($row['similar_sign']);
      $d = puzzle_vector_normalized_distance($baseSign, $sign);
      echo ++$count . ': ' . $d . "\n";
      if ($d > 0 && (is_null($minDiff) || $d < $minDiff)) {
        $minDiff = $d;
        $minDiffPath = $path;

        $sampleList[$path] = $d;
      }
    }

    asort($sampleList, SORT_NUMERIC);
    $this->output->writeln('count: ' . $count);
    $this->output->writeln($minDiff);
    $this->output->writeln($minDiffPath);
    $this->output->writeln('----------------------');
    foreach($sampleList as $path => $d) {
      $this->output->writeln(sprintf('%s : %.2f', $path, $d));
    }
    return 0;
  }


  /**
   *
   */
  private function raiseError()
  {
    $logger = $this->logger;
    $logger->addDbLog(
        $logger->makeDbLog(
            'テストエラー'
          , 'テストエラー'
          , 'サブタイトル'
        )->setInformation('')
      , true
      , 'Redmineチケット自動作成 テストエラーです。'
      , 'error'
    );
  }

  /**
   * RedMine Rest API 試験
   */
  private function redmineRestApiTest()
  {
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    var_dump($webAccessUtil->requestRedmineApi('GET', '/issues.json'));

    return;
  }


  /**
   * Yahoo CSVアップロード 非同期起動テスト
   */
  private function runUploadYahooCsv()
  {
    $logger = $this->logger;

    // FTPアップロード 実行
    // 非同期に実行し、このプロセスは終了する。（排他状態をMainJobに解除させるため。）
      $logger->info('Yahoo CSV Export : FTPアップロード 起動開始');

      $command = $this->getContainer()->get('misc.util.file')->getRootDir() . "/console batch:export-csv-yahoo-upload";
      $arguments = [];
      $arguments[] = sprintf("--account=%s", '10');
      $arguments[] = sprintf('"%s"', '/home/hirai/working/ne_api/WEB_CSV/Yahoo/Export/20151211110536');

      $command .= ' ' . implode(' ', $arguments);

      $process = new Process($command);
      $process->start();

      $logger->info('Yahoo CSV Export : FTPアップロード  起動終了');
  }


  /**
   * Guzzle テスト
   * @return int
   */
  private function guzzleMulti()
  {
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', true);

    $db = $this->getContainer()->get('doctrine')->getConnection('main');

    // yahoo から適当に商品コードを取得
    $sql = "SELECT daihyo_syohin_code FROM tb_yahoo_information ORDER BY daihyo_syohin_code limit 10";
    $codes = array_reduce($db->query($sql)->fetchAll(), function($result = [], $item) {
      $result[] = $item['daihyo_syohin_code'];
      return $result;
    });

    $urlBase = 'https://item-shopping.c.yimg.jp/i/l/';
    $handler = new CurlMultiHandler();
    $stack = HandlerStack::create($handler);
    $client = new Client([
        'base_uri' => $urlBase
      , 'timeout' => 2.0
      , 'handler' => $stack
      , 'curl' => [
        //   CURLOPT_RETURNTRANSFER => true
        // , CURLOPT_VERBOSE => true
      ]
    ]);

    $requests = function($codes) {
      foreach($codes as $code) {
        $url = sprintf('https://item-shopping.c.yimg.jp/i/l/plusnao_%s', $code);
        yield new Request('GET', $url);
      }
    };

    $pool = new Pool($client, $requests($codes), [
        'concurrency' => 4
      , 'fulfilled' => function($response, $index) {
        file_put_contents(sprintf('./tmp/%s.jpg', $index), $response->getBody());
        printf("got %d\n", $index);
      },
      'rejected' => function ($reason, $index) {
        var_dump($index);
        var_dump(get_class($reason));
        var_dump($reason->getMessage());
      }
    ]);

    $start = microtime(true);
    $promise = $pool->promise();
    $promise->wait();

    var_dump(microtime(true) - $start);
    var_dump('done!!');
  }

  private function getRakutenCategories()
  {
    /** @var TbRakutenCategoryForSalesRankingRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbRakutenCategoryForSalesRanking');

    // $repo->renewRakutenCategoryForSalesRanking();

    $categories = $repo->getAllForPullDown();
    var_dump($categories);

    $this->output->writeln('done!');
    return 1;
  }


  ///
  private function getSokunouHiduke()
  {
    $this->output->writeln($this->commonUtil->getImmediateShippingDate()->format('Y-m-d'));
  }

  private function testShippingDate()
  {
    $sql = <<<EOD

              SELECT
                  c.calendar_date
              FROM tb_calendar as c
              WHERE c.workingday = - 1
                AND c.calendar_date >= '2015-11-20'
              ORDER BY
                c.calendar_date ASC
              LIMIT
                :alertDays - 1, 1
EOD;
    $db = $this->getContainer()->get('doctrine')->getConnection('main');
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':alertDays', 1);

    $this->output->writeln(print_r($stmt->execute()->fetch(\PDO::FETCH_ASSOC), true));
  }

  ///
  Private function isExistsYahooImage($url)
  {
    $fileUtil = $this->getContainer()->get('misc.util.file');
    var_dump($fileUtil->getRootDir());

    $templateImage = file_get_contents(dirname($fileUtil->getRootDir()) . '/data/yahoo/yahoo_no_image.gif');
    $targetImage = file_get_contents($url);

    return $templateImage !== $targetImage; // 一致しない = 「画像がありません」ではない = 商品画像が存在する
  }

  private function processTest()
  {
    $today = new \DateTime();

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');

    $fs = new Filesystem();
    $logDir = sprintf('%s/variation_images', $fileUtil->getLogDir());
    if (!$fs->exists($logDir)) {
      $fs->mkdir($logDir, 0755);
    }
    $logFilePath = sprintf('%s/variation_images.%s.gz', $logDir, $today->format('Ymd'));
    $imageDir = $this->getContainer()->getParameter('product_image_variation_dir');

    $command = '/usr/bin/find "' . $imageDir . '" -type f -printf "%TY-%Tm-%Td %TH:%TM:%TS %p %k\\n"'
             . ' | sed -e "s|' . $imageDir . '/||"'
             . ' | sort -k 3 '
             . ' | gzip -c > "' . $logFilePath . '"';

    echo $command . "\n";
    $process = new Process($command);

    try {
      $process->mustRun();

    } catch (\Exception $e) {
      var_dump($e->getMessage());
      echo $e->getTraceAsString();
    }

    return;
  }

}


class TestHydrator extends ObjectHydrator
{
  /** @var  ObjectHydrator */
  protected $parent;

  public function setHydrator($hydrator)
  {
    $this->parent = $hydrator;
  }

  public function hydrate($row)
  {
    $result = [];
    $this->parent->hydrateRowData($row, $result);

    return $result;
  }
}
