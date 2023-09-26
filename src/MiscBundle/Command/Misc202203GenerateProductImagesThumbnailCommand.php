<?php

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * 販売中商品のサムネイル画像を生成する（アテンション画像は除く）
 */
class Misc202203GenerateProductImagesThumbnailCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;
  
  /** @var SymfonyUsers */
  private $account;
  
  protected function configure()
  {
    $this
    ->setName('misc:202203-generate-product-images-thumbnail')
    ->setDescription('商品画像　サムネイル生成')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('directory', null, InputOption::VALUE_OPTIONAL, '指定したディレクトリ以降を処理')
    ;
  }
  
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

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
    $directory = $input->getOption('directory');

    $logExecTitle = '商品画像　サムネイル生成';
    $logger->initLogTimer();
    $logger->info("$logExecTitle 開始");
    $logger->addDbLog($logger->makeDbLog($logExecTitle, '開始'));

    $fs = new FileSystem();

    // サムネイル画像の縦横最大サイズを指定
    $maxWidth = 80;
    $maxHeight = 80;

    try {
      $imageDir = $this->getContainer()->getParameter('product_image_dir');
      $thumbnailDir = $this->getContainer()->getParameter('product_image_thumbnail_dir');

      $imageInfo = $this->findProductImageInfoForSale($directory);

      // 変換＆書き出し（コピー処理を兼ねる）
      if (!$fs->exists($thumbnailDir)) {
        $fs->mkdir($thumbnailDir);
      }

      $count = 0;
      foreach ($imageInfo as $dir => $files) {
        if ($fs->exists($imageDir . '/' . $dir) && !$fs->exists($thumbnailDir . '/' . $dir)) {
          $fs->mkdir($thumbnailDir . '/' . $dir);
        }

        foreach ($files as $file) {
          $basePath = $imageDir . '/' . $dir . '/' . $file;

          $fileName = pathinfo($file, PATHINFO_FILENAME) . '_' . $maxWidth . '_' . $maxHeight . '.jpg';

          // 元画像が存在しない、または、既にサムネイル作成済みならスキップ
          if (!$fs->exists($basePath) || $fs->exists($thumbnailDir . '/' . $dir . '/' . $fileName)) {
            continue;
          }

          // Exif情報削除
          $im = new \Imagick($basePath);
          $im->stripImage();
          // リサイズ処理
          $width = $im->getImageWidth();
          $height = $im->getImageHeight();
          if ($height > $maxHeight || $width > $maxWidth) {
            $im->resizeImage($maxWidth, $maxHeight, \Imagick::FILTER_POINT, 0, true);
          }

          $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
          $im->setImageCompressionQuality(40);
          $im->writeImage($thumbnailDir . '/' . $dir . '/' . $fileName);
          $im->destroy();

          $count++;
          if ($count % 10000 === 0) {
            $logger->info("$logExecTitle {$count}件完了 最新の処理画像: {$dir}/{$file}");
          }
        }
      }

      $logger->info("$logExecTitle 終了 $count 件 サムネイル作成");
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '終了'));

    } catch (\Exception $e) {
      $logger->error($logExecTitle . 'エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
    }
  }

  private function findProductImageInfoForSale($directory) {
    $addWhere = '';
    $params = [];
    if ($directory) {
      $addWhere = 'AND i.directory >= :directory';
      $params[':directory'] = $directory;
    }

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT
        i.directory,
        i.filename
      FROM
        product_images i
        INNER JOIN tb_mainproducts_cal c
          ON i.daihyo_syohin_code = c.daihyo_syohin_code
        LEFT JOIN product_images_attention_image a
          ON i.md5hash = a.md5hash
      WHERE
        c.deliverycode IN (0, 1, 2)
        AND (a.attention_flg IS NULL OR a.attention_flg = 0)
        {$addWhere}
      ORDER BY
        i.directory, i.filename
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
  }
}
