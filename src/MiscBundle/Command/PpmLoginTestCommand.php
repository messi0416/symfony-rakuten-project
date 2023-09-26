<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\PpmMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PpmLoginTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:ppm-login-test')
      ->setDescription('PPMログインの確認')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    // PPM ログイン
    $client = $webAccessUtil->getWebClient();
    $crawler = $webAccessUtil->ppmLogin($client);

    $logger->info($crawler ? get_class($crawler) : 'FAILED !! (no crawler)');

    /** @var PpmMallProcess $processor */
    $processor = $container->get('batch.mall_process.ppm');

    // CSVダウンロードリクエスト
    $processor->requestProductCsvDownload($client);

    // CSV一覧 スクレイピング＆ダウンロード （当日リクエスト分の最新を取得する）
    $exportDir = sprintf('%s/Ppm/Import/%s', $this->getFileUtil()->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
    $limitDateTime = new \DateTime(); // 現在以降
    $limitDateTime->setTime($limitDateTime->format('H'), $limitDateTime->format('i'), 0); // 作成日時の秒は表示がないので00で合わせる
    $processor->downloadCsv($client, $exportDir, $limitDateTime);

    return 1;

  }

}
