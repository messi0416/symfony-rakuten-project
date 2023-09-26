<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2017/09/07
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Util\FileUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;


class Misc20171115AggregateImagesToZipYahoo20Command extends ContainerAwareCommand
{
  use CommandBaseTrait;

  public $results = [];

  const MAX_UPLOAD_SIZE = 2000000000; // 2GB

  protected function configure()
  {
    $this
      ->setName('misc:20171115-aggregate-images-to-zip-yahoo-20')
      ->setDescription('臨時 Yahoo 商品画像zip圧縮処理');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $logger = $this->getLogger();

    $this->results = [];

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');

    try {
      $fileList = [];

      $logTitle = sprintf('商品画像収集(Yahoo)');
      $logger->info($logTitle . ': ファイル取得');
      $uploadSize = 0;
      $uploadCount = 0;

      $results = [
          'count' => 0
        , 'size' => 0
        , 'file_missing' => []
        , 'archives' => []
      ];

      $imageDir = $this->getContainer()->getParameter('product_image_dir');

      // 一時ディレクトリ作成
      $fs = new FileSystem();

      $now = new \DateTime();
      $tmpDir = sprintf('%s/upload_image_yahoo/%s', $fileUtil->getDataDir(), $now->format('YmdHis'));
      $fs->mkdir($tmpDir);

      $logger->info(sprintf('Yahoo: image temporary dir: %s', $tmpDir));

      // 画像一覧取得 (p007 ～ p020 の Yahoo未アップロードの画像のみ収集)
      $imageFiles = array();

      $dbMain = $this->getDb('main');
      $sql = <<<EOD
        SELECT
            i.daihyo_syohin_code
          , i.code
          , i.`directory`
          , i.filename
          , i.phash
          , i.created
          , i.updated
        FROM product_images i
        INNER JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        WHERE (
             cal.deliverycode IN (0, 1, 2)
          OR cal.endofavailability >= DATE_ADD(CURRENT_DATE, INTERVAL -3 YEAR)
        )
        AND i.code IN (
            'p007'
          , 'p008'
          , 'p009'
          , 'p010'
          , 'p011'
          , 'p012'
          , 'p013'
          , 'p014'
          , 'p015'
          , 'p016'
          , 'p017'
          , 'p018'
          , 'p019'
          , 'p020'
        )
        ORDER BY i.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      $maxDateTime = null;
      $zipFiles = [];
      $currentSize = 0;

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        /** @var ProductImages $image */
        $image = new ProductImages();
        $image->setDaihyoSyohinCode($row['daihyo_syohin_code']);
        $image->setCOde($row['code']);
        $image->setDirectory($row['directory']);
        $image->setFilename($row['filename']);
        $image->setPhash($row['phash']);
        $image->setCreated($row['created']);
        $image->setUpdated(new \DateTime($row['updated']));


        $dir = $image->getDirectory();
        $filename = $image->getFilename();
        $filePath = sprintf('%s/%s/%s', $imageDir, $dir, $filename);

        // $logger->info($filePath);

        if ($fs->exists($filePath)) {
          $file = [
              'path' => $filePath
            , 'filename' => basename($filePath)
          ];

          // Yahoo形式になっていなければここでリネームしてアップロード
          // ※従来の実装の流れを引き継いでの仕様
          //   既存画像差し替え時に前のファイル名を引き継いでいるため、
          //   アップロード時のこの変換処理も必要。
          //   → 理想は、すべての画像ファイル名の付け直し＆再アップロード
          if (!$image->isValidYahooImageName()) {
            $file['filename'] = $image->getYahooImageName();
          }
          $imageFiles[] = $file;

          $uploadCount++;
          $uploadSize += filesize($filePath);
          $currentSize += filesize($filePath);

          if ($currentSize >= self::MAX_UPLOAD_SIZE) {

            if ($imageFiles) {
              $zipFileIndex = count($zipFiles) + 1;
              $zipFiles = array_merge($zipFiles, $this->archiveImageFiles($imageFiles, $tmpDir, $zipFileIndex));
            }

            $logger->info($logTitle . ': zipファイルを作成しました。' . count($imageFiles) . ' images => ' . count($zipFiles) . ' zips');

            // 引き続きGO
            $imageFiles = [];
            $currentSize = 0;
          }

        } else {
          $results['file_missing'][] = sprintf('%s : %s (local)', $image->getDaihyoSyohinCode(), $filePath);
        }
      }

      // ラストのzip作成
      if ($imageFiles) {
        $zipFileIndex = count($zipFiles) + 1;
        $zipFiles = array_merge($zipFiles, $this->archiveImageFiles($imageFiles, $tmpDir, $zipFileIndex));
      }
      $logger->info($logTitle . ': zipファイルを作成しました。' . count($imageFiles) . ' images => ' . count($zipFiles) . ' zips');



//
//      // zip ファイル作成
//      // 上限 50MB
//      if ($imageFiles) {
//        $zipFiles = $this->archiveImageFiles($imageFiles, $tmpDir);
//
//        if ($zipFiles) {
//          $uploaded = [];
//          $ftpConfig = $container->getParameter('ftp_yahoo');
//          $config = isset($ftpConfig[$target]) ? $ftpConfig[$target] : null;
//          if (!$config) {
//            throw new \RuntimeException('no target config (yahoo ftp) !!');
//          }
//
//          /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
//          $ftp = $container->get('ijanki_ftp');
//          $ftp->connect($config['host']);
//          $ftp->login($config['user'], $config['password']);
//          $ftp->pasv(true);
//          $ftp->chdir('/');
//
//          // YahooのFTP仕様により、ファイル名で取込順を指定できる。
//          // 時刻は現在より未来の予定時刻を指定
//          $baseDateTime = new \DateTime();
//          $baseDateTime->modify('+5 minutes'); // 5分 余裕を見る
//
//          // アップロード先 ファイル存在チェック
//          $existsFiles = $ftp->nlist('/');
//          if (!is_array($existsFiles)) {
//            $e = new ExportCsvYahooUploadException(sprintf('[%s] FTP接続に失敗しました。処理を中止します。', $target));
//            $e->setResults([
//              'success' => $uploaded
//              , 'error' => [ $target => $existsFiles ]
//            ]);
//            throw $e;
//          } else if (count($existsFiles)) {
//
//            // もし、すでにアップロードされているファイルがあれば、今回のアップロードはその最終取込指定時間より後の時刻を指定。
//            // ※ NextEngineからの在庫連携もこのFTPを利用している
//            $dateForName = null;
//            foreach($existsFiles as $fileName) {
//              if (preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $fileName, $match)) {
//                $date = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]));
//                if (!$dateForName || ($dateForName < $date)) {
//                  $dateForName = $date;
//                }
//              }
//            }
//
//            if ($dateForName && ($dateForName > $baseDateTime)) {
//              $baseDateTime = clone $maxDateTime;
//              $baseDateTime->modify('+1 minutes');
//            }
//          }
//
//          $index = 1;
//          foreach($zipFiles as $path) {
//            $name = basename($path);
//            if (preg_match('/^img(\d+)\.zip$/', $name)) {
//              $newName = sprintf('img%s%02d.zip', $baseDateTime->format('YmdHi'), $index++);
//
//              $ftp->put($newName, $path, FTP_BINARY);
//              $results['archives'][] = sprintf('%s => %s/%s', $path, dirname($path), $newName);
//
//            } else {
//              $logger->warning('unknown file is exists! (yahoo ftp upload). ' . $path);
//              continue; // ひとまずスルー
//            }
//          }
//
//          $ftp->close();
//        }
//      }

      // いずれにせよ一時ディレクトリは削除 => NO! それを手動でアップする！
      // $fs->remove($tmpDir);

      $results['count'] = $uploadCount;
      $results['size'] = $uploadSize;
      $results['archives'] = $zipFiles;

    } catch (\Exception $e) {
      $logger->error('臨時 yahoo 商品画像zip圧縮処理 エラー:' . $e->getMessage());

      $results['message'] = $e->getMessage();
    }

    $logger->info(print_r($results, true));
    $logger->info('臨時 yahoo 商品画像zip圧縮処理 完了');
    $output->writeln('done!');
  }


  private function archiveImageFiles($fileList, $distDir, $startIndex = 1)
  {
    $logger = $this->getLogger();

    $zipFiles = [];
    $totalSize = 0;
    $num = 0;

    if ($fileList) {
      $dateTime = new \DateTime();
      $dateTime->modify('+5 minutes'); // 5分後に取り込まれる様に。
      $index = $startIndex;
      $limitSize = 49000000; // 50MB 余裕を見て 50MiB (-1MiB)で制限
      $currentSize = 0;

      foreach($fileList as $file) {
        if (!isset($zip)) {
          $zip = new \ZipArchive();
          $fileName = sprintf('%s/img%s%02d.zip', $distDir, $dateTime->format('YmdHi'), $index++);
          if (! $zip->open($fileName, \ZipArchive::CREATE)) {
            throw new \RuntimeException('can not create image zip file. aborted. [' . $fileName . ']');
          }

          $zipFiles[] = $fileName;

          $logger->info('create zip file: ' . $fileName);
        }

        $filePath = $file['path'];
        $localName = $file['filename'];

        $zip->addFile($filePath,  $localName);
        $currentSize += filesize($filePath);
        $totalSize += filesize($filePath);
        $num++;

        $logger->info('add file: ' . $localName . ' : zip total ' . $currentSize . ' bytes');

        // 閉じてオブジェクト削除（次のファイル作成の判定のため）
        if ($currentSize >= $limitSize) {
          $zip->close();
          $currentSize = 0;
          unset($zip);
        }
      }

      // 最後のファイルを閉じる
      if (isset($zip)) {
        $zip->close();
      }
    }

    return $zipFiles;
  }

}
