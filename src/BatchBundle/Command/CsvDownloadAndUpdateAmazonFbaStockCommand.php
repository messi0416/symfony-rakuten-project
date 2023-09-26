<?php
/**
 * Amazon在庫比較テーブル FBA在庫数更新 処理
 */

namespace BatchBundle\Command;

use BatchBundle\Job\MainJob;
use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class CsvDownloadAndUpdateAmazonFbaStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  const TARGET_SHOP = AmazonMallProcess::SHOP_NAME_VOGUE;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-amazon-fba-stock')
      ->setDescription('Amazon MWS から出品レポートをダウンロードし、Amazon 在庫比較テーブルのFBA在庫数を更新する')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('update-product', null, InputOption::VALUE_OPTIONAL, '商品CSVの出力＆アップロードを行うか', '0');
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('Amazon在庫比較テーブルFBAの更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = 'Amazon在庫比較テーブルFBA在庫更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述

      // ====================================================
      // FBA在庫データ更新（FBA在庫情報 ダウンロード ＆ データ更新）
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '開始'));
      $mallProcess->updateFbaProductStock(AmazonMallProcess::SHOP_NAME_VOGUE);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '終了'));

      // ====================================================
      // FBA仮想倉庫 在庫ロケーション更新
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '開始'));
      $result = $mallProcess->updateFbaMultiProductLocation(AmazonMallProcess::SHOP_NAME_VOGUE, $account);
      if($result['status'] === 'ng'){
        $logger->addDbLog(
            $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー')->setInformation($result['message'])
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
        );
      }
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '終了'));


      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Amazon在庫比較テーブル FBA在庫数の更新処理を終了しました。');

      // 商品CSV出力 (FBA or 出品者出荷の切替のため。ひとまずざっくり全件出力（差分出力がFBA在庫の変動まで対応していないため）)
      if ($input->getOption('update-product')) {

        $resque = $this->getResque();

        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command'          => MainJob::COMMAND_KEY_EXPORT_CSV_AMAZON
          , 'doUpload'         => true
          , 'exportTarget'     => 'all'
        ];

        $resque->enqueue($job);
        $logExecTitle = 'キュー追加処理 (Amazon CSV出力 & アップロード)';
        $logger->setExecTitle($logExecTitle);
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));

      }

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
