<?php
/**
 * Yahoo CSV出力処理
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\SymfonyUsers;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

class CheckProductImageCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:check-product-image')
      ->setDescription('CSVエクスポート Yahoo')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $webAccessUtil = $container->get('misc.util.web_access');
    
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('楽天画像存在チェックを開始しました。');

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
    $logExecTitle = '楽天画像存在チェック';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    try {
      $client = $webAccessUtil->getWebClient();
      $result = array();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '画像情報取得', '開始'));

      /** @var ProductImagesRepository $repoImages */
      $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      $data = $repoImages->findProductImagesAll();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '画像情報取得', '終了'));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '画像存在確認', '開始'));

      $stringUtil = $this->getContainer()->get('misc.util.string');

      // ヘッダ
      $headers = [
          'daihyo_syohin_code'
        , 'address'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      
      $fs = new FileSystem();
      $exportDir = sprintf('%s/Rakuten', $this->getFileUtil()->getWebCsvDir());
      if (!$fs->exists($exportDir)) {
        $fs->mkdir($exportDir, 0755);
      }

      $exportPath = sprintf('%s/product_image_ng.csv', $exportDir);
      if ($fs->exists($exportPath)) {
        $fs->remove($exportPath);
      }
        
      $fp = fopen($exportPath, 'wb');
      fputs($fp, $headerLine. "\r\n");
      
      $cnt = 0;
      
      foreach($data as $row){
        $url = $row['address'];
        $crawler = $client->request('head', $url);
        $response = $client->getResponse();
        $status = $response->getStatus();
        
        $logger->info($url.' : '.$status);
        
        if ($status !== 200 ) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          fputs($fp, $line. "\r\n");
          $cnt++;
        }
      }

      $info = array(
        'NG件数' => $cnt,
      );
      $logger->info('NG件数:'.$cnt);
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '画像存在確認', '終了')->setInformation($info));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));

    } catch (\Exception $e) {

      $logger->error('楽天画像確認 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );
      return 1;
    }

    return 0;
  }

}
