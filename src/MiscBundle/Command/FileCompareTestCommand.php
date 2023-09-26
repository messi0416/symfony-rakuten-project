<?php
/**
 * ファイル一覧比較処理 速度その他テスト
 */

namespace MiscBundle\Command;


use Doctrine\ORM\QueryBuilder;
use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\Repository\TbPlusnaoproductdirectoryRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;


class FileCompareTestCommand extends ContainerAwareCommand
{
  /** @var InputInterface */
  protected $input;

  /** @var OutputInterface */
  protected $output;

  /** @var BatchLogger */
  protected $logger;

  /** @var DbCommonUtil  */
  protected $commonUtil;

  protected function configure()
  {
    $this
      ->setName('misc:file-compare-test')
      ->setDescription('なんだかんだテスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $container = $this->getContainer();

    $this->logger = $container->get('misc.util.batch_logger');
    $logger = $this->logger;

    $doctrine = $container->get('doctrine');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $doctrine->getConnection('main');

    $fileUtil = $container->get('misc.util.file');
    $fs = new FileSystem();

    $dbMain->query("DROP TABLE IF EXISTS product_images_exist_files");
    $dbMain->query("DROP TABLE IF EXISTS product_images_original_exist_files");

    $sql = <<<EOD
      CREATE TABLE product_images_exist_files (
          directory VARCHAR(20) not null default ''
        , filename VARCHAR(50) not null default ''
        , path VARCHAR(255) not null default ''
        , PRIMARY KEY (`directory`, `filename`)
      ) ENGINE=InnoDB CHARSET=utf8 COMMENT '画像パス格納一時テーブル（加工済み）'
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      CREATE TABLE product_images_original_exist_files (
          directory VARCHAR(20) not null default ''
        , filename VARCHAR(50) not null default ''
        , path VARCHAR(255) not null default ''
        , PRIMARY KEY (`directory`, `filename`)
      ) ENGINE=InnoDB CHARSET=utf8 COMMENT '画像パス格納一時テーブル（オリジナル）'
EOD;
    $dbMain->query($sql);

    // ファイル一覧テーブル挿入

    // オリジナル
    $originalDir = $fileUtil->getOriginalImageDir();
    $builder = new ProcessBuilder(array('find', $originalDir, '-type', 'f'));
    $process = $builder->getProcess();
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $files = explode("\n", $process->getOutput());
    $count = 0;
    foreach ($files as $path) {
      if (!strlen($path)) {
        continue;
      }

      $parts = explode('/', $path);
      if (count($parts) != 6) {
        $logger->warning('invalid path? : ' . $path);
        continue;
      }

      $fileName = array_pop($parts);
      $dirName = array_pop($parts);
      if (!$fileName || !$dirName) {
        $logger->warning('invalid path? : ' . $path);
        continue;
      }

      if (!isset($insertBuilder)) {
        // 一括登録
        $insertBuilder = new MultiInsertUtil("product_images_original_exist_files", [
          'fields' => [
              'directory' => \PDO::PARAM_STR
            , 'filename' => \PDO::PARAM_STR
            , 'path' => \PDO::PARAM_STR
          ]
        ]);
      }

      $item = [
          'directory'     => $dirName
        , 'filename'      => $fileName
        , 'path'          => $path
      ];

      $insertBuilder->bindRow($item);

      // 分割 INSERT (1000件ずつ)
      if ($count++ >= 1000) {
        if (count($insertBuilder->binds())) {
          $stmt = $dbMain->prepare($insertBuilder->toQuery());
          $insertBuilder->bindValues($stmt);
          $stmt->execute();
        } else {
          $logger->info('product_images_original_exist_files: no bind data. something wrong ... ?');
        }

        unset($insertBuilder);
        $count = 0;
      }
    }
    // INSERT 残り
    if ($count && isset($insertBuilder) && count($insertBuilder->binds())) {
      $stmt = $dbMain->prepare($insertBuilder->toQuery());
      $insertBuilder->bindValues($stmt);
      $stmt->execute();

    }
    unset($insertBuilder);
    $count = 0;


    // ----------------------------------------------------------
    // 加工済み
    $imageDir = $fileUtil->getImageDir();
    $builder = new ProcessBuilder(array('find', $imageDir, '-type', 'f'));
    $process = $builder->getProcess();
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $files = explode("\n", $process->getOutput());
    $count = 0;
    foreach ($files as $path) {
      if (!strlen($path)) {
        continue;
      }

      $parts = explode('/', $path);
      if (count($parts) != 6) {
        $logger->warning('invalid path? : ' . $path);
        continue;
      }

      $fileName = array_pop($parts);
      $dirName = array_pop($parts);
      if (!$fileName || !$dirName) {
        $logger->warning('invalid path? : ' . $path);
        continue;
      }

      if (!isset($insertBuilder)) {
        // 一括登録
        $insertBuilder = new MultiInsertUtil("product_images_exist_files", [
          'fields' => [
              'directory' => \PDO::PARAM_STR
            , 'filename' => \PDO::PARAM_STR
            , 'path' => \PDO::PARAM_STR
          ]
        ]);
      }

      $item = [
          'directory'     => $dirName
        , 'filename'      => $fileName
        , 'path'          => $path
      ];

      $insertBuilder->bindRow($item);

      // 分割 INSERT (1000件ずつ)
      if ($count++ >= 1000) {
        if (count($insertBuilder->binds())) {
          $stmt = $dbMain->prepare($insertBuilder->toQuery());
          $insertBuilder->bindValues($stmt);
          $stmt->execute();
        } else {
          $logger->info('product_images_exist_files: no bind data. something wrong ... ?');
        }

        unset($insertBuilder);
        $count = 0;
      }
    }
    // INSERT 残り
    if ($count && isset($insertBuilder) && count($insertBuilder->binds())) {
      $stmt = $dbMain->prepare($insertBuilder->toQuery());
      $insertBuilder->bindValues($stmt);
      $stmt->execute();
    }
    unset($insertBuilder);
    $count = 0;




    /*
    // DBに登録がないファイルを削除
    $fs = new FileSystem();
    $sql = <<<EOD
      SELECT
           path
         , path_original
      FROM product_images_exist_files E
      WHERE NOT EXISTS (
        SELECT * FROM product_images I
        WHERE I.directory = E.directory
          AND I.filename = E.filename
      )
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $logger->info('delete unregistered image: ' . $file['path']);
      $fs->remove($file['path']);
      if ($fs->exists($file['path_original'])) {
        $fs->remove($file['path_original']);
      }
      $this->results['DB未登録削除'][] = $file['path'];
    }

    // product_images_exist_files 更新
    $sql = <<<EOD
      DELETE E
      FROM product_images_exist_files E
      WHERE NOT EXISTS (
        SELECT * FROM product_images I
        WHERE I.directory = E.directory
          AND I.filename = E.filename
      )
EOD;
    $dbMain->query($sql);
    */


    $output->writeln('done!');

  }


}
