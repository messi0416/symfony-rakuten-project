<?php
/**
 * バッチ処理 アダルトチェック画像コピー圧縮処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\EntityInterface\ProductImagesInterface;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ArchiveAdultCheckImageToZipCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  const TMP_DIR = '/tmp';

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:archive-adult-check-image-to-zip')
      ->setDescription('アダルトチェック画像コピー圧縮処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('アダルトチェック画像コピー圧縮処理を開始しました。');

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

      $this->results = [
          'message' => null
        , 'ng' => []
      ];

      $logExecTitle = sprintf('アダルトチェック画像コピー圧縮処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');


      $fs = new FileSystem();
      $archiveDir = $this->getContainer()->getParameter('check_image_dir');
      if (!$archiveDir || !$fs->exists($archiveDir)) {
        throw new \RuntimeException('no dir (config or directory) : ' . $archiveDir );
      }

      // 1.「アダルトの登録される可能性のあるカテゴリの商品」で「ホワイト」設定になっているもの
      $dirName = '01_アダルト有りカテゴリのホワイト商品';
      $imageDir = $this->getContainer()->getParameter('product_image_dir');
      $images = $repo->findAdultCheckImagesAdultCategoryWhiteProductImages();
      $result = $this->copyAndArchive($dirName, $imageDir, $images);

      $this->results[$dirName] = $result;
      $logger->info(sprintf('%s : done. count: %d / ng: %d', $dirName, $result['count'], count($result['ng'])));

      // 2. すべてのブラックの商品
      $dirName = '02_すべてのブラックの商品';
      $imageDir = $this->getContainer()->getParameter('product_image_dir');
      $images = $repo->findAdultCheckImagesAllBlackProductImages();
      $result = $this->copyAndArchive($dirName, $imageDir, $images);

      $this->results[$dirName] = $result;
      $logger->info(sprintf('%s : done. count: %d / ng: %d', $dirName, $result['count'], count($result['ng'])));

      // 3. すべてのグレーの商品
      $dirName = '03_すべてのグレーの商品';
      $imageDir = $this->getContainer()->getParameter('product_image_dir');
      $images = $repo->findAdultCheckImagesAllGrayProductImages();
      $result = $this->copyAndArchive($dirName, $imageDir, $images);

      $this->results[$dirName] = $result;
      $logger->info(sprintf('%s : done. count: %d / ng: %d', $dirName, $result['count'], count($result['ng'])));

      // 4. 「Amazonメイン画像」で「Amazonへ登録されている商品」
      $dirName = '04_Amazonへ登録されている商品のAmazonメイン画像';
      $imageDir = $this->getContainer()->getParameter('product_image_amazon_dir'); // Amazon画像ディレクトリ
      $images = $repo->findAdultCheckImagesAllAmazonRegisteredProductImages();
      $result = $this->copyAndArchive($dirName, $imageDir, $images);

      $this->results[$dirName] = $result;
      $logger->info(sprintf('%s : done. count: %d / ng: %d', $dirName, $result['count'], count($result['ng'])));

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('アダルトチェック画像コピー圧縮処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('アダルトチェック画像コピー圧縮処理 エラー:' . $e->getMessage());
      $this->results['message'] = $e->getMessage();
      $logger->addDbLog(
        $logger->makeDbLog('アダルトチェック画像コピー圧縮処理 エラー', 'アダルトチェック画像コピー圧縮処理 エラー', 'エラー終了')->setInformation($this->results)
        , true, 'アダルトチェック画像コピー圧縮処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }

  /**
   * コピー＆圧縮処理
   * @param string $dirName
   * @param string $originalFileDir コピー元画像ルートディレクトリ
   * @param \Doctrine\ORM\Internal\Hydration\IterableResult $images
   * @return array
   */
  private function copyAndArchive($dirName, $originalFileDir, $images)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $result = [
        'count' => 0
      , 'ng' => []
      , 'archive' => null
      , 'fileSize' => 0
    ];

    $currentTmpDir = $this->prepareDirectory($dirName);
    $archiveFile = $this->createArchiveFileName($dirName);

    $fs = new FileSystem();
    /** @var ProductImagesInterface[] $row */
    foreach($images as $row) {
      $result['count']++;
      $image = $row[0];
      $filePath = sprintf('%s/%s', $originalFileDir, $image->getFileDirPath());
      if (!$fs->exists($filePath)) {
        $result['ng'][] = sprintf('no file: %s', $filePath);
        continue;
      }

      // tmpディレクトリには平たくコピー（同名ファイルは無いはず）
      $fs->copy($filePath, sprintf('%s/%s', $currentTmpDir, $image->getFilename()));
    }

    // -j : ディレクトリ構造を除く
    $command = sprintf('/usr/bin/zip -j "%s" %s/*', $archiveFile, $currentTmpDir);
    $logger->info('zip command: ' . $command);
    exec($command);

    if ($fs->exists($archiveFile)) {
      $result['archive'] = $archiveFile;
      $result['fileSize'] = filesize($archiveFile);
    } else {
      $result['message'] = 'no archive file';
    }

    return $result;
  }


  /**
   * 一時ディレクトリ掃除、過去ファイル掃除
   * @param $dirName
   * @return string 作成された一時ディレクトリ
   */
  private function prepareDirectory($dirName)
  {
    // 一時ディレクトリの削除・作成
    $fs = new FileSystem();
    $currentTmpDir = $this->createTmpDirName($dirName);
    if ($fs->exists($currentTmpDir)) {
      $fs->remove($currentTmpDir);
    }
    $fs->mkdir($currentTmpDir);

    $archiveFile = $this->createArchiveFileName($dirName);
    if ($fs->exists($archiveFile)) {
      $fs->remove($archiveFile);
    }

    return $currentTmpDir;
  }

  /**
   * 一時ディレクトリ名取得
   * @param $dirName
   * @return string
   */
  private function createTmpDirName($dirName)
  {
    return sprintf('%s/%s', self::TMP_DIR, $dirName);
  }

  /**
   * 圧縮ファイル名取得
   * @param $dirName
   * @return string
   */
  private function createArchiveFileName($dirName)
  {
    $archiveDir = $this->getContainer()->getParameter('check_image_dir');
    return sprintf('%s/%s.zip', $archiveDir, $dirName);
  }

}


