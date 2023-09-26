<?php
/**
 * バッチ処理 Amazonメイン画像ディレクトリ内移動作業処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\ProductImagesAmazon;
use MiscBundle\Entity\Repository\ProductImagesAmazonRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\ImageUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Misc20180913MoveAmazonImagesIntoDirectoryCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:20180913-move-amazon-images-into-directory')
      ->setDescription('Amazonメイン画像ディレクトリ内移動作業処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Amazonメイン画像ディレクトリ内移動作業処理を開始しました。');

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

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('Amazonメイン画像ディレクトリ内移動作業処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->getContainer()->get('misc.util.image');
      $hostPlusnao = $this->getContainer()->getParameter('host_plusnao');
      $amazonImageDir = $this->getContainer()->getParameter('product_image_amazon_dir');


      /** @var ProductImagesAmazonRepository $repoImages */
      $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesAmazon');
      /** @var ProductImagesAmazon[] $images */
      $images = $repoImages->findBy([ 'directory' => '' ], [ 'daihyo_syohin_code' => 'ASC' ]);

      $fs = new FileSystem();
      $em = $this->getDoctrine()->getManager('main');

      foreach($images as $image) {

        $currentFilePath = sprintf('%s/%s', $amazonImageDir, $image->getFileDirPath());

        $dir = $imageUtil->findAvailableImageDirectory('amazon_main');

        $image->setDirectory($dir);

        $image->setAddress(sprintf('https://%s/amazon_images/%s', $hostPlusnao, $image->getFileDirPath()));
        $image->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

        $em->flush();

        $output->writeln($image->getFileDirPath());
        $output->writeln($image->getAddress());

        // ファイルコピー
        $newFilePath = sprintf('%s/%s', $amazonImageDir, $image->getFileDirPath());
        if ($fs->exists($currentFilePath) && $currentFilePath !== $newFilePath) {
          $dirPath = $amazonImageDir . '/' . $image->getDirectory();
          if (!$fs->exists($dirPath)) {
            $fs->mkdir($dirPath);
          }

          $fs->rename($currentFilePath, $newFilePath);
          $output->writeln(sprintf('move: %s => %s', $currentFilePath, $newFilePath));
        } else {
          $output->writeln('no file : ' . $currentFilePath);
        }

      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Amazonメイン画像ディレクトリ内移動作業処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Amazonメイン画像ディレクトリ内移動作業処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazonメイン画像ディレクトリ内移動作業処理 エラー', 'Amazonメイン画像ディレクトリ内移動作業処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Amazonメイン画像ディレクトリ内移動作業処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


