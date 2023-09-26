<?php
/**
 * Yahoo CSV出力処理 画像チェック＆楽天からの取得処理
 *
 * 従来のAccess実装の写し処理。
 * 画像の自動定期アップロード稼働後は、利用しないが、
 * 単純な存否処理に改修して利用される可能性があるため実装をそのまま残す
 *
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ImageCheckYahooCreateListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $noImageData;

  /** @var  FileLogger */
  private $imageCheckLogger;

  const EXPORT_TARGET_PLUSNAO = 'plusnao';
  const EXPORT_TARGET_KAWAEMON = 'kawaemon';

  protected function configure()
  {
    $this
      ->setName('batch:image-check-yahoo-create-list')
      ->setDescription('画像チェック Yahoo （リスト生成）')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-target', null, InputOption::VALUE_REQUIRED, '出力対象(コンマ区切り)', NULL) // plusnao,kawaemon
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('ヤフー画像チェック処理を開始しました。');

    $this->imageCheckLogger = $container->get('misc.util.file_logger')->setFileName('yahoo_image_check');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {
      // 対象モールごとに処理
      $exportTargetList = $input->getOption('export-target')
        ? explode(',', $input->getOption('export-target'))
        : null;

      if (!$exportTargetList) {
        throw new RuntimeException('画像チェック対象が選択されていません。(plusnao|kawaemon)');
      }
      $logger->info('ヤフー画像チェック対象: ' . implode(', ', $exportTargetList));

      // モール別ループ
      foreach ($exportTargetList as $currentTarget) {

        $this->stopwatch->start($currentTarget);

        // ログ出力 EXEC_TITLE 切り替え
        // plusnao_yahoo : [plusnao]
        // kawa_yahoo : [kawaemon]
        $logExecTitle = sprintf('ヤフー画像チェック処理[%s]', $currentTarget);
        $logger->setExecTitle($logExecTitle);
        $logger->initLogTimer();

        // DB記録＆通知処理
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

        try {
          $this->checkYahooImage($currentTarget);
          /* ------------ DEBUG LOG ------------ */
          $logger->debug($this->getLapTimeAndMemory('checkYahooImage', $currentTarget));


          $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
          $logger->logTimerFlush();

          $event = $this->stopwatch->stop($currentTarget);
          $logger->info(sprintf('%s: duration: %.02f / memory: %s', $currentTarget, $event->getDuration() / 1000000, number_format($event->getMemory())));

        } catch (\Exception $e) {

          $event = $this->stopwatch->stop('main');

          $logger->error($e->getMessage());
          $logger->addDbLog(
            $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
            , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );

          return 1;
        }
      }

      $logger->info('Yahoo 画像チェック 完了');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('Yahoo画像チェック エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('ヤフー画像チェック処理', 'ヤフー画像チェック処理', 'エラー終了')->setInformation($e->getMessage())
        , true, 'ヤフー画像チェック処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }


  /// 画像チェック処理
  /**
   * @param $exportTarget
   * @param bool|false $checkAll
   * @throws \Doctrine\DBAL\DBALException
   */
  private function checkYahooImage($exportTarget)
  {
    $logger = $this->getLogger();
    $logTitle = '画像チェック';

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $imageCheckLogger = $this->imageCheckLogger;

    $noImageCheckUrl = 'https://item-shopping.c.yimg.jp/i/j/plusnao_hogehogemogemoge';
    if ($this->isExistYahooImage(file_get_contents($noImageCheckUrl))) {
      throw new RuntimeException('Yahooの「画像がありません」のイメージが変更されています。画像チェックを終了します。');
    }

    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();
    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);

    $urlKeys = [
        self::EXPORT_TARGET_PLUSNAO => 'plusnao'
      , self::EXPORT_TARGET_KAWAEMON => 'kawa-e-mon'
    ];

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '存在チェック', '開始'));

    $sql = <<<EOD
        SELECT
              i.daihyo_syohin_code
            , p.code
            , p.filename
        FROM tb_mainproducts m
        INNER JOIN product_images AS p ON m.daihyo_syohin_code = p.daihyo_syohin_code
        INNER JOIN {$targetTable} AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        WHERE i.registration_flg <> 0
          AND (
                 cal.endofavailability IS NULL
             OR  cal.endofavailability >= DATE_ADD(NOW(), INTERVAL -1 YEAR)
          )
          AND cal.deliverycode <>  :deliveryCodeTemporary
          AND p.code IN ( 'p001', 'p002', 'p003', 'p004', 'p005', 'p006' )
        ORDER BY p.daihyo_syohin_code, p.code
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    $fs = new FileSystem();

    $handler = new CurlMultiHandler();
    $stack = HandlerStack::create($handler);
    $rakutenClient = new Client([
        'timeout' => 30.0
      , 'handler' => $stack
      , 'delay'   => 100 // 100ms
    ]);

    // 商品1000件ごと区切って実行。（メモリ節約）
    $start = microtime(true);
    $productsNum = 0;
    $downloadNum = 0;
    $errors = []; // エラーメッセージ（403, 404が異様に多すぎる）
    do {
      $count = 0;
      $data = [];
      $requests = [];
      while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) && $count < 1000) {

        if (!$row['filename']) {
          continue;
        }

        // 過去の小細工のツケはこんなところにも... o_oﾒ
        $url = null;
        if ($row['code'] == 'p001') {
          $url = sprintf('%s_%s', $urlKeys[$exportTarget], strtolower($row['daihyo_syohin_code']));
        } else if (preg_match('/^p(\d+)$/', $row['code'], $m)) {
          $num = intval($m[1]) - 1;
          $url = sprintf('%s_%s_%d', $urlKeys[$exportTarget], strtolower($row['daihyo_syohin_code']), $num);
        }

        if (!$url) {
          $logger->info('error!! ', print_r($row, true));
          continue;
        }

        $row['url'] = $url;

        $requests[] = new Request('GET', $url);

        $data[] = $row;

        $count++;
        $productsNum++;
      }
      $logger->info(sprintf('yahoo image check : %d', $count));

      if ($requests) {
        $handler = new CurlMultiHandler();
        $stack = HandlerStack::create($handler);
        $client = new Client([
          'base_uri' => 'https://item-shopping.c.yimg.jp/i/j/' // 「j」の方
          , 'timeout' => 30.0
          , 'handler' => $stack
          , 'delay'   => 100 // 100ms
          , 'curl' => [
            //   CURLOPT_RETURNTRANSFER => true
            // , CURLOPT_VERBOSE => true
          ]
        ]);

        $pool = new Pool($client, $requests, [
            'concurrency' => 8 // 並列数

          /** @var \GuzzleHttp\Psr7\Response $response */
          , 'fulfilled' => function($response, $index) use ($data, $exportTarget, $requests, $targetTable, $rakutenClient, &$downloadNum, &$downloadLimitCount, &$errors, $commonUtil, $logger, $imageCheckLogger) {

            $row = $data[$index];

            if ($response->getStatusCode() === 200 && $this->isExistYahooImage($response->getBody())) {
              // 画像あり。何もしない

            } else {
              // 画像なし。=> ログ
              $imageCheckLogger->info(sprintf("404\t%s\t%s\t%s\t%s\t%s", $exportTarget, $row['daihyo_syohin_code'], $row['code'], $row['filename'], $row['url']));
            }
          },
          'rejected' => function ($reason, $index) use ($logger, $row, $exportTarget, &$errors, $imageCheckLogger) {
            $imageCheckLogger->info(sprintf("error\t%s\t%s\t%s\t%s\t%s", $exportTarget, $row['daihyo_syohin_code'], $row['code'], $row['filename'], $row['url']));
            $logger->warn(sprintf("%s\t%s\t%s\t%s\t%s", $reason, $row['daihyo_syohin_code'], $row['code'], $row['filename'], $row['url']));
          }
        ]);

        // チェック開始
        $pool->promise()->wait();
      }
    } while ($row);

    $info = [
        '対象件数' => $productsNum
      , 'ダウンロード対象商品数' => $downloadNum
      , 'エラー' => $errors
    ];
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '存在チェック', '終了')->setInformation($info));

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('check done. ' . round(microtime(true) - $start, 4) . ' sec.');
  }

  /// Yahoo 「画像がありません」のイメージチェック
  private function isExistYahooImage($imageData)
  {
    if (!isset($this->noImageData)) {
      $fileUtil = $this->getFileUtil();
      $this->noImageData = file_get_contents(dirname($fileUtil->getRootDir()) . '/data/yahoo/yahoo_no_image.gif');
    }

    if (!strlen($this->noImageData)) {
      throw new RuntimeException('「画像がありません」の画像がありません。');
    }

    return strlen($imageData) > 0 && strcmp($this->noImageData, $imageData) !== 0; // 一致しない = 「画像がありません」ではない = 商品画像が存在する
  }


}
