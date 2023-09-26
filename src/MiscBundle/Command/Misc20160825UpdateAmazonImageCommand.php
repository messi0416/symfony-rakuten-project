<?php
/**
 * バッチ処理 Amazonメイン画像 上書き更新処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\ProductImagesAmazon;
use MiscBundle\Entity\Repository\ProductImagesAmazonRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbMainproducts;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Misc20160825UpdateAmazonImageCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:20160824-update-amazon-main-images')
      ->setDescription('Amazonメイン画像 上書き更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('dir', null, InputArgument::REQUIRED, 'コピー元画像ディレクトリ')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Amazonメイン画像 上書き処理を開始しました。');

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

      $dbMain = $this->getDb('main');
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
        , 'countBefore' => 0
        , 'countAfter'  => 0
        , 'fileNum'   => 0
        , 'ok'        => 0
        , 'ng'        => 0
        , 'overwrite' => 0
        , 'results' => [
            'ok' => []
          , 'ng' => []
          , 'overwrite' => []
        ]
      ];

      $logExecTitle = sprintf('Amazonメイン画像 上書き更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始')->setLogLevel(TbLog::DEBUG));

      $copyFileDir = $input->getArgument('dir');
      $fs = new Filesystem();
      if (!$copyFileDir || !$fs->exists($copyFileDir)) {
        throw new \RuntimeException('ディレクトリ指定がありません。');
      }

      $logger->info('dir : ' . $copyFileDir);

      /** @var TbMainproductsRepository $repo */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

      /** @var ProductImagesAmazonRepository $repoImage */
      $repoImage = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesAmazon');

      $amazonImageDir = $this->getContainer()->getParameter('product_image_amazon_dir');

      // 処理前件数
      $this->results['countBefore'] = $repoImage->getMainImageCount();

      $em = $this->getDoctrine()->getManager('main');
      $finder = new Finder();
      $files = $finder->in($copyFileDir)->files();
      /** @var SplFileInfo $file */
      foreach($files as $file) {
        $logger->info('file: ' . $file->getPathname());

        if (preg_match('/^([a-zA-Z0-9_-]+)\.(jpg|JPG)/', $file->getBasename(), $m)) {
          $daihyoSyohinCode = $m[1];
          /** @var TbMainproducts $product */
          $product = $repoProduct->find($daihyoSyohinCode);
          if ($product) {
            $daihyoSyohinCode = $product->getDaihyoSyohinCode(); // 大文字小文字が揃ってないため、マスタのコードに差し替え
            $fileName = sprintf('%s.jpg', strtolower($daihyoSyohinCode));

            /** @var ProductImagesAmazon $image */
            $image = $repoImage->findOneBy([ 'daihyo_syohin_code' => $daihyoSyohinCode, 'code' => 'amazonMain' ]);
            if (!$image) {
              $logger->info('new image: ' . $file->getBasename());

              $image = new ProductImagesAmazon();
              $image->setDaihyoSyohinCode($daihyoSyohinCode);
              $image->setCode('amazonMain');
              $image->setDirectory('');
              $image->setFilename($fileName);
              $image->setAddress(sprintf('https://%s/amazon_images/%s', $this->getContainer()->getParameter('host_plusnao'), $fileName));

              $em->persist($image);

              $this->results['ok']++;
              $this->results['results']['ok'][] = $file->getPathname();

            } else {
              $logger->info('update image: ' . $file->getBasename());

              // 元のファイルを削除
              $oldPath = sprintf('%s/%s', $amazonImageDir, $image->getFileDirPath());
              if ($fs->exists($oldPath) && is_file($oldPath)) {
                $fs->remove($oldPath);
              }

              $image->setFilename($fileName);

              $this->results['overwrite']++;
              $this->results['results']['overwrite'][] = $file->getPathname();
            }

            $newPath = sprintf('%s/%s', $amazonImageDir, $image->getFileDirPath());
            $logger->info('path: ' . $newPath);

            // ファイルコピー
            $fs->copy($file->getPathname(), $newPath);

            // 類似画像チェック用 文字列作成・格納（上書き） → 不要

            $em->flush();

          } else {
            $this->results['ng']++;
            $this->results['results']['ng'][] = $file->getPathname() . ' (no product)' ;
          }

        } else {
          $this->results['ng']++;
          $this->results['results']['ng'][] = $file->getPathname() . ' (invalid filename)' ;
        }

        $this->results['fileNum']++;
      }

      // 処理前件数
      $this->results['countAfter'] = $repoImage->getMainImageCount();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('Amazonメイン画像 上書き更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Amazonメイン画像 上書き更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazonメイン画像 上書き更新処理 エラー', 'Amazonメイン画像 上書き更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())->setLogLevel(TbLog::DEBUG)
        , true, 'Amazonメイン画像 上書き更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


