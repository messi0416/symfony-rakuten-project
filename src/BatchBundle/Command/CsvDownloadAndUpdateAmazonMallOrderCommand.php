<?php
/**
 * Amazon モール受注CSV取込処理
 */

namespace BatchBundle\Command;

use BatchBundle\Job\MainJob;
use BatchBundle\MallProcess\AmazonMallProcess;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;


class CsvDownloadAndUpdateAmazonMallOrderCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  private $results;

  const TARGET_SHOP = AmazonMallProcess::SHOP_NAME_VOGUE;

  /**
   * 1度に取得する最大レポート件数 (GetReportスロットリング => 最大リクエストクォータ:15 / 回復レート:1回/分 / 1時間あたり60リクエスト)
   * 15分ごとに実行すれば1つでよい想定だが、バッチ処理の抜けなどによる遅延のキャッチアップのため適当数のレポートを取得する。
   */
  const GET_REPORT_LIMIT = 5;
  // const GET_REPORT_LIMIT = 1;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-amazon-mall-order')
      ->setDescription('Amazon MWS から注文レポートをダウンロードし、モール受注CSV変換処理を行い、NextEngineへアップロードする')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'NextEngineへのアップロードを行うか', '0');
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->results = [
      'done' => []
    ];

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('Amazonモール受注CSV取込処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = 'Amazonモール受注CSV取込処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始')->setLogLevel(TbLog::DEBUG));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述 ※今のところ、Amazon APIのモックがでたらめなので必ず本番接続 要注意。

      // ====================================================
      // Amazon 注文レポートダウンロード & モール受注CSV変換 & アップロード処理
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '注文レポート一覧取得処理', '開始')->setLogLevel(TbLog::DEBUG));

      $parameters = array (
          'AvailableToDate' => new \DateTime('now', new \DateTimeZone('UTC'))
        , 'AvailableFromDate' => new \DateTime('-1 days', new \DateTimeZone('UTC'))
        , 'Acknowledged' => false
        , 'MaxCount' => 100
        , 'ReportTypeList' => [ 'Type' => '_GET_FLAT_FILE_ORDERS_DATA_' ]
        , 'ReportOptions' => 'ShowSalesChannel=true'
      );

      $reportList = $mallProcess->mwsGetReportList(AmazonMallProcess::SHOP_NAME_VOGUE, $parameters);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '注文レポート一覧取得処理', '終了')->setLogLevel(TbLog::DEBUG));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '注文レポート取得・取込処理', '開始')->setLogLevel(TbLog::DEBUG));

      // 古い順に並べ替え
      usort($reportList, function($a, $b) {
        $aDate = isset($a['AvailableDate']) ? $a['AvailableDate'] : null;
        $bDate = isset($b['AvailableDate']) ? $b['AvailableDate'] : null;
        if (!$aDate) {
          return -1;
        } else if (!$bDate) {
          return 1;
        }
        if ($aDate == $bDate) {
          return 0;
        } else {
          return $aDate < $bDate ? -1 : 1;
        }
      });

      // 取得ログ
      foreach($reportList as $report) {
        $logger->info(sprintf(
            'ReportId: %s / ReportType: %s / ReportRequestId: %s / AvailableDate: %s / Acknowledged : %s / AcknowledgedDate : %s'
          , isset($report['ReportId'])          ? $report['ReportId'] : '(none)'
          , isset($report['ReportType'])        ? $report['ReportType'] : '(none)'
          , isset($report['ReportRequestId'])   ? $report['ReportRequestId'] : '(none)'
          , isset($report['AvailableDate'])     ? $report['AvailableDate']->format('Y-m-d H:i:s') : '(none)'
          , isset($report['Acknowledged'])      ? ($report['Acknowledged'] ? 'true' : 'false') : '(none)'
          , isset($report['AcknowledgedDate'])  ? $report['AcknowledgedDate'] : '(none)'
        ));
      }

      // 古い順に、指定件数のレポートを取得
      $fileUtil = $this->getFileUtil();

      $fs = new Filesystem();
      $uploadDir = sprintf('%s/MallOrder/Import/%s', $fileUtil->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
      if (!$fs->exists($uploadDir)) {
        $fs->mkdir($uploadDir, 0755);
      }

      $maxNum = count($reportList) < self::GET_REPORT_LIMIT ? count($reportList) : self::GET_REPORT_LIMIT;
      for ($index = 0; $index < $maxNum; $index++) {
        $report = $reportList[$index];

        $logger->info(sprintf('%s : %s', $report['ReportId'], $report['AvailableDate']->format('Y-m-d H:i:s')));

        $tmpFilePath = tempnam($uploadDir, 'tmp_amazon_mall_order');
        $mallProcess->mwsGetReport(AmazonMallProcess::SHOP_NAME_VOGUE, $report['ReportId'], $tmpFilePath);

        $logger->info('download report: ' . $tmpFilePath);

        $file = new File($tmpFilePath);
        if (  ! $file->isFile()
           || ! $file->isReadable()
           || $file->getSize() <= 0
        ) {
          throw new \RuntimeException('注文レポートのダウンロードに失敗しました。' . print_r($report, true));
        }

        // 2行目（最初のデータ）で文字コード判定 ＆ UTF-8変換
        $fp = fopen($file->getPathname(), 'rb');
        fgets($fp); // 先頭行を捨てる
        $secondLine = fgets($fp);
        fclose($fp);
        if (!$secondLine) { // 2行目がなければスルー（データが無いため処理不要）
          continue;
        }

        $charset = mb_detect_encoding($secondLine, ['SJIS-WIN', 'UTF-8', 'EUCJP-WIN']);
        $logger->info(sprintf('%s : %s', $file->getPathname(), $charset));
        if (!$charset) {
          throw new \RuntimeException(sprintf('CSVファイルの文字コードが判定できませんでした。[%s]', $file->getPathname()));
        }

        $newFilePath = tempnam($uploadDir, 'utf_');
        chmod($newFilePath, 0666);
        $fp = fopen($newFilePath, 'wb');
        $fileUtil->createConvertedCharsetTempFile($fp, $file->getPathname(), $charset, 'UTF-8');
        fclose($fp);
        $newFile = new File($newFilePath);

        if (!$mallProcess->isValidOrderReport($newFile)) {
          throw new \RuntimeException('注文レポートの書式が違うようです。' . $newFile->getPathname());
        }

        // モール受注CSV変換処理
        /** @var NextEngineMallProcess $neMallProcess */
        $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
        $neMallProcess->importMallOrderAmazon([ $newFile ]);

        // Acknowledged 更新
        $mallProcess->mwsUpdateReportAcknowledgements(AmazonMallProcess::SHOP_NAME_VOGUE, $report['ReportId'], true);

        $this->results['done'][] = sprintf('%s : %s', $report['ReportId'], $report['AvailableDate']->format('Y-m-d H:i:s'));
      }

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '注文レポート取得・取込処理', '終了')->setLogLevel(TbLog::DEBUG));

      // 変換 & アップロードキュー追加処理
      // AmazonはもともとNextEngineに受注が無いため、受注明細差分更新の必要性は薄いので省略。（フリー在庫数の変動があるため、まったく不必要では無い。）
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command'   => MainJob::COMMAND_KEY_CONVERT_MALL_ORDER_CSV_DATA
        , 'mallCode'  => DbCommonUtil::MALL_CODE_AMAZON
        , 'account'   => $this->account ? $this->account->getId() : null
        , 'doUpload'  => $input->getOption('do-upload') ? true : false
      ];

      $resque = $this->getResque();
      $resque->enqueue($job); // リトライなし


      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'モール受注CSV変換キュー追加', '終了')->setLogLevel(TbLog::DEBUG));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results), false);
      $logger->logTimerFlush();

      $logger->info('Amazonモール受注CSV取込処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $this->results['message'] = $e->getMessage();
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($this->results)
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
