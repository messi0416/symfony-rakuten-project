<?php
/**
 * バッチ処理 PPM CSVアップロードテスト処理
 */
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\PpmMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class PpmCsvUploadTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:ppm-csv-upload-test')
      ->setDescription('PPM CSVアップロードテスト処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $execTitle = 'PPM CSV出力処理';
    $logger->setExecTitle($execTitle);
    $logger->initLogTimer();

    $logger->info('PPM CSVアップロードテスト処理を開始しました。');

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

      $logger = $this->getLogger();

      $filePath = '/home/hirai/working/ne_api/WEB_CSV/Ppm/Import/20161101000000/category_20161101095937.csv';
      $remoteFileName = 'item.csv';
      if (!$remoteFileName) {
        $remoteFileName = basename($filePath);
      }

      $this->results['filePath'] = $filePath;
      $this->results['remoteFileName'] = $remoteFileName;
      $logger->info(print_r($this->results, true));

      $fs = new FileSystem();
      if (!$filePath || !$remoteFileName || !$fs->exists($filePath) || !is_file($filePath)) {
        throw new \RuntimeException(sprintf('アップロードファイルが正しく指定されていません。[%s][%s]', $filePath, $remoteFileName));
      }

      // FTPアップロード
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $container->get('misc.util.db_common');

      $config = $container->getParameter('ftp_ppm');

      $logger->info(print_r($config, true));
      if (!$config) {
        throw new \RuntimeException('no ftp config (PPM csv upload)');
      }
      $config['user'] = $commonUtil->getSettingValue(TbSetting::KEY_PPM_FTP_USER);
      $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_PPM_FTP_PASSWORD);
      $logger->info(print_r($config, true));

      /** @var PpmMallProcess $processor */
      $processor = $container->get('batch.mall_process.ppm');

      // FTPアップロード ※空になるのを待つ
      $processor->uploadCsv($config, $filePath, preg_replace('|/$|', '', $config['path_csv']) . '/' . $remoteFileName, true);

      $logger->logTimerFlush();

      $logger->info('PPM CSVアップロードテスト処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('PPM CSVアップロードテスト処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('PPM CSVアップロードテスト処理 エラー', 'PPM CSVアップロードテスト処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'PPM CSVアップロードテスト処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


