<?php
namespace MiscBundle\Util;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Milon\Barcode\DNS1D;
use MiscBundle\Entity\EntityInterface\ProductImagesInterface;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\ProductImagesAmazon;
use MiscBundle\Entity\ProductImagesVariation;
use MiscBundle\Entity\TmpProductImages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 画像関連ユーティリティ
 */
class ImageUtil
{
  /** @var ContainerInterface */
  private $container;

  /** @var BatchLogger */
  private $logger;

  /** @var FileSystem */
  private $fs;

  private $imageDir;
  private $originalImageDir;
  private $imageHost;

  /**
   * @param ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * @return ContainerInterface
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * @param BatchLogger $logger
   */
  public function setLogger(BatchLogger $logger = null)
  {
    $this->logger = $logger;
  }

  /**
   * @return BatchLogger
   */
  public function getLogger()
  {
    return $this->logger;
  }

  /**
   * @return FileSystem
   */
  public function getFileSystem()
  {
    if (!$this->fs) {
      $this->fs = new Filesystem();
    }

    return $this->fs;
  }

  // --------------------------------------
  // DI setters & getters
  // --------------------------------------
  public function setImageDir($imageDir)
  {
    $this->imageDir = $imageDir;
  }
  public function getImageDir()
  {
    return $this->imageDir;
  }
  public function setOriginalImageDir($originalImageDir)
  {
    $this->originalImageDir = $originalImageDir;
  }
  public function getOriginalImageDir()
  {
    return $this->originalImageDir;
  }
  public function setImageHost($imageHost)
  {
    $this->imageHost = $imageHost;
  }
  public function getImageHost()
  {
    return $this->imageHost;
  }


  /**
   * 商品画像テーブル 全更新
   * DBにデータがある（＝存在する必要がある）画像一覧
   */
  public function createProductImagesTable()
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getContainer()->get('doctrine')->getConnection('main');

    $sql = <<<EOD
      INSERT INTO product_images (
            daihyo_syohin_code
          , code
          , address
          , directory
          , filename
      )
      SELECT
          daihyo_syohin_code
        , 'p001' AS code
        , 商品画像P1Adress AS address
        , picfolderP1 AS directory
        , picnameP1 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P1Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p002' AS code
        , 商品画像P2Adress AS address
        , picfolderP2 AS directory
        , picnameP2 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P2Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p003' AS code
        , 商品画像P3Adress AS address
        , picfolderP3 AS directory
        , picnameP3 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P3Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p004' AS code
        , 商品画像P4Adress AS address
        , picfolderP4 AS directory
        , picnameP4 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P4Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p005' AS code
        , 商品画像P5Adress AS address
        , picfolderP5 AS directory
        , picnameP5 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P5Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p006' AS code
        , 商品画像P6Adress AS address
        , picfolderP6 AS directory
        , picnameP6 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P6Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p007' AS code
        , 商品画像P7Adress AS address
        , picfolderP7 AS directory
        , picnameP7 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P7Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p008' AS code
        , 商品画像P8Adress AS address
        , picfolderP8 AS directory
        , picnameP8 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P8Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'p009' AS code
        , 商品画像P9Adress AS address
        , picfolderP9 AS directory
        , picnameP9 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像P9Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'm1' AS code
        , 商品画像M1Adress AS address
        , picfolderM1 AS directory
        , picnameM1 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像M1Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'm2' AS code
        , 商品画像M2Adress AS address
        , picfolderM2 AS directory
        , picnameM2 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像M2Adress, '') <> ''
      UNION ALL
      SELECT
          daihyo_syohin_code
        , 'm3' AS code
        , 商品画像M3Adress AS address
        , picfolderM3 AS directory
        , picnameM3 AS filename
      FROM tb_mainproducts
      WHERE COALESCE(商品画像M3Adress, '') <> ''

    ON DUPLICATE KEY UPDATE address = VALUES(address), directory = VALUES(directory), filename = VALUES(filename)
EOD;
    $dbMain->query($sql);

    // 消去された画像データを削除
    $sql = <<<EOD
      DELETE images
      FROM product_images images
      WHERE EXISTS (
        SELECT * FROM tb_mainproducts m
        WHERE images.daihyo_syohin_code = m.daihyo_syohin_code
          AND (
               (images.code = 'p001' AND COALESCE(m.商品画像P1Adress, '') = '')
            OR (images.code = 'p002' AND COALESCE(m.商品画像P2Adress, '') = '')
            OR (images.code = 'p003' AND COALESCE(m.商品画像P3Adress, '') = '')
            OR (images.code = 'p004' AND COALESCE(m.商品画像P4Adress, '') = '')
            OR (images.code = 'p005' AND COALESCE(m.商品画像P5Adress, '') = '')
            OR (images.code = 'p006' AND COALESCE(m.商品画像P6Adress, '') = '')
            OR (images.code = 'p007' AND COALESCE(m.商品画像P7Adress, '') = '')
            OR (images.code = 'p008' AND COALESCE(m.商品画像P8Adress, '') = '')
            OR (images.code = 'p009' AND COALESCE(m.商品画像P9Adress, '') = '')
            OR (images.code = 'm1' AND COALESCE(m.商品画像M1Adress, '') = '')
            OR (images.code = 'm2' AND COALESCE(m.商品画像M2Adress, '') = '')
            OR (images.code = 'm3' AND COALESCE(m.商品画像M3Adress, '') = '')
          )
      )
EOD;
    $dbMain->query($sql);
  }



  /**
   * 更新された商品画像オリジナルファイルの変換処理を一括で行う
   * （ product_images_original => product_images へコピー）
   *
   * この処理では、実ファイルのタイムスタンプを元に処理を行う
   * ※FTPへの直接アップロードがなくなったため現在利用なし
   *
   * @param \DateTime $lastUpdated
   * @return array $results
   */
  public function convertNewOriginalImages($lastUpdated)
  {
    throw new RuntimeException('this method is not used!!!');

//
//    $results = [];
//
//    $container = $this->getContainer();
//
//    $originalImageDir = $container->getParameter('product_image_original_dir');
//
//    $finder = new Finder();
//
//    // 更新ファイル検索ディレクトリ（オリジナル）
//    $finder->in($originalImageDir);
//    $finder->notPath('/unchecked/');
//
//    // 最終チェック日時以降
//    if ($lastUpdated) {
//      $finder->date(sprintf('>= %s', $lastUpdated->format('Y-m-d H:i:s')));
//    }
//
//    // 更新ファイル一覧取得
//    /** @var SplFileInfo $file */
//    foreach($finder->files() as $file) {
//
//      try {
//        $results[] = $this->convertOriginalFileToFixedFile($file->getPathname());
//
//      } catch (\Exception $e) {
//        $this->logger->error($e->getMessage());
//        $this->logger->error($e->getTraceAsString());
//
//        // エラーファイルがあってもそのまま続ける
//      }
//
//    }
//
//    return $results;
  }

  /**
   * 商品画像オリジナルファイルの変換処理
   * （ product_images_original => product_images へコピー）
   * @param string $originalPath オリジナル画像 フルパス
   * @return string 加工後画像パス（画像ディレクトリ配下の相対パス）
   */
  public function convertOriginalFileToFixedFile($originalPath)
  {
    $container = $this->getContainer();

    $imageDir = $container->getParameter('product_image_dir');
    $originalImageDir = $container->getParameter('product_image_original_dir');

    $maxWidth  = $container->getParameter('product_image_max_width');
    $maxHeight = $container->getParameter('product_image_max_height');

    $fs = $this->getFileSystem();
    $convertedPath = str_replace($originalImageDir, $imageDir, $originalPath);

    // Exif情報削除
    $im = new \Imagick($originalPath);
    $im->stripImage();

    // リサイズ処理
    $width = $im->getImageWidth();
    $height = $im->getImageHeight();
    if ($width > $maxWidth || $height > $maxHeight) {
      if ($width >= $height) { // 横長
        $im->resizeImage($maxWidth, 0, \Imagick::FILTER_POINT, 0);
      } else { // 縦長
        $im->resizeImage(0, $maxHeight, \Imagick::FILTER_POINT, 0);
      }
    }

    // 変換＆書き出し（コピー処理を兼ねる）
    if (!$fs->exists(dirname($convertedPath))) {
      $fs->mkdir(dirname($convertedPath));
    }

    $im->writeImage($convertedPath);
    $im->destroy();

    return sprintf('%s/%s', basename(dirname($convertedPath)), basename($convertedPath));
  }




  /**
   * 画像ファイルの削除
   * @param ProductImagesInterface $image
   */
  public function deleteImage(ProductImagesInterface $image)
  {
    $container = $this->getContainer();

    $fs = new FileSystem();

    // 通常商品画像削除
    if ($image->getType() == 'main') {
      $imageDir = $container->getParameter('product_image_dir');
      $originalImageDir = $container->getParameter('product_image_original_dir');

      // 画像の削除（加工済み）
      $path = sprintf('%s/%s', $imageDir, $image->getFileDirPath());
      if ($fs->exists($path)) {
        $fs->remove($path);
      }

      // 画像の削除（オリジナル）
      $path = sprintf('%s/%s', $originalImageDir, $image->getFileDirPath());
      if ($fs->exists($path)) {
        $fs->remove($path);
      }

    // Amazon画像削除
    } else if ($image->getType() == 'amazon') {
      $imageDir = $container->getParameter('product_image_amazon_dir');

      // 画像の削除
      $path = sprintf('%s/%s', $imageDir, $image->getFileDirPath());
      if ($fs->exists($path)) {
        $fs->remove($path);
      }
    // バリエーション画像削除
    } else if ($image->getType() == 'variation') {
      $imageDir = $container->getParameter('product_image_variation_dir');

      // 画像の削除
      $path = sprintf('%s/%s', $imageDir, $image->getFileDirPath());
      if ($fs->exists($path)) {
        $fs->remove($path);
      }
    }
  }

  /**
   * 商品画像に紐づく、サムネイル画像ファイルの削除
   * @param ProductImages $image
   */
  public function deleteThumbnailImage($image) {
    $dir = $image->getDirectory();
    $file = $image->getFilename();
    $thumbnailDir = $this->getContainer()->getParameter('product_image_thumbnail_dir');

    $fileName = pathinfo($file, PATHINFO_FILENAME);

    $finder = new Finder;
    $fs = new FileSystem();
    $targetDir = $thumbnailDir . '/' . $dir;
    if ($fs->exists($targetDir)) {
      $finder->in($targetDir);
      $finder->files()->path("/^{$fileName}_\d+_\d+.jpg$/");

      foreach ($finder as $file) {
        $fs->remove($file->getRealPath());
      }
    }
  }

  /**
   * アップロード画像データからオリジナル画像へ保存
   * @param ProductImages $image
   * @param UploadedFile $tmpImage
   * @return string 保存ファイルパス
   */
  public function saveUploadedProductImageToOriginal(ProductImages $image, UploadedFile $tmpImage)
  {
    $container = $this->getContainer();
    $originalImageDir = $container->getParameter('product_image_original_dir');

    $fs = new Filesystem();
    $dir = sprintf('%s/%s', $originalImageDir, $image->getDirectory());
    if (!$fs->exists($dir)) {
      $fs->mkdir($dir);
    }

    $tmpImage->move($dir, $image->getFilename());
    return sprintf('%s/%s', $originalImageDir, $image->getFileDirPath());
  }

  /**
   * 一時画像データからオリジナル画像へ保存
   * @param ProductImages $image
   * @param TmpProductImages $tmpImage
   * @return string 保存ファイルパス
   */
  public function saveTmpProductImageToOriginal(ProductImages $image, TmpProductImages $tmpImage)
  {
    $container = $this->getContainer();
    $originalImageDir = $container->getParameter('product_image_original_dir');

    if (!strlen($tmpImage->getImage())) {
      return null;
    }

    $fs = new Filesystem();
    $dir = sprintf('%s/%s', $originalImageDir, $image->getDirectory());
    if (!$fs->exists($dir)) {
      $fs->mkdir($dir);
    }

    $path = sprintf('%s/%s', $originalImageDir, $image->getFileDirPath());
    file_put_contents($path, $tmpImage->getImage());

    return $path;
  }

  /**
   * 一時画像データからAmazon画像へ保存
   * @param ProductImagesAmazon $image
   * @param TmpProductImages $tmpImage
   * @return string 保存ファイルパス
   */
  public function saveTmpProductImageToAmazon(ProductImagesAmazon $image, TmpProductImages $tmpImage)
  {
    $container = $this->getContainer();
    $amazonImageDir = $container->getParameter('product_image_amazon_dir');

    if (!strlen($tmpImage->getImage())) {
      return null;
    }

    $fs = new Filesystem();
    if (strlen($image->getDirectory())) {
      $dir = sprintf('%s/%s', $amazonImageDir, $image->getDirectory());
      if (!$fs->exists($dir)) {
        $fs->mkdir($dir);
      }
    }

    $path = sprintf('%s/%s', $amazonImageDir, $image->getFileDirPath());
    file_put_contents($path, $tmpImage->getImage());

    return $path;
  }


  /**
   * 一時画像データからVariation画像へ保存
   * @param ProductImagesVariation $image
   * @param TmpProductImages $tmpImage
   * @return string 保存ファイルパス
   */
  public function saveTmpProductImageToVariation(ProductImagesVariation $image, TmpProductImages $tmpImage)
  {
    $container = $this->getContainer();
    $variationImageDir = $container->getParameter('product_image_variation_dir');

    if (!strlen($tmpImage->getImage())) {
      return null;
    }

    $fs = new Filesystem();
    if (strlen($image->getDirectory())) {
      $dir = sprintf('%s/%s', $variationImageDir, $image->getDirectory());
      if (!$fs->exists($dir)) {
        $fs->mkdir($dir);
      }
    }

    $path = sprintf('%s/%s', $variationImageDir, $image->getFileDirPath());
    file_put_contents($path, $tmpImage->getImage());

    return $path;
  }


  /**
   * 新規保存画像フォルダ 確認
   * 楽天R-Cabinetの制限で 2,000画像まで
   */
  public function findAvailableImageDirectory($type = 'normal')
  {
    /** @var Registry $doctrine */
    $doctrine = $this->getContainer()->get('doctrine');
    /** @var Connection $dbMain */
    $dbMain = $doctrine->getConnection('main');

    // 最大ディレクトリ番号を取得
    if ($type === 'amazon_main') {
      $tableName = 'product_images_amazon';
      $directoryPrefix = 'white';

    } else {
      $tableName = 'product_images';
      $directoryPrefix = 'itempic';
    }

    $sql = <<<EOD
      SELECT MAX(CAST(REPLACE(directory, '{$directoryPrefix}', '') AS SIGNED)) AS max_directory
      FROM {$tableName}
      WHERE directory LIKE '{$directoryPrefix}%'
EOD;
    $max = $dbMain->query($sql)->fetchColumn(0);
    $directoryNumber = $max ? $max : 1;
    $maxDirectory = sprintf('%s%04d', $directoryPrefix, $directoryNumber);

    $sql = <<<EOD
      SELECT count(*) AS file_count
      FROM {$tableName}
      WHERE directory = :dir
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':dir', $maxDirectory);
    $stmt->execute();

    $count = intval($stmt->fetchColumn(0));

    if ($count > 1500) { // 今回登録分 ＋ α の余裕を見ておく。また、ギリギリにする必要はない。
      $directoryNumber++;
    }

    return sprintf('%s%04d', $directoryPrefix, $directoryNumber);
  }

  /**
   * 画像名作成
   * @param $daihyoSyohinCode
   * @param $code
   * @return string
   */
  public function createMainImageFilename($daihyoSyohinCode, $code)
  {
    // p1～p9まで
    if (preg_match('/p(\d+)/', $code, $match)) {
      $num = $match[1];
    } else {
      $num = 1; // 暫定
    }
    $imageName = $num == 1
      ? sprintf('%s.jpg'   , strtolower($daihyoSyohinCode))
      : sprintf('%s_%d.jpg', strtolower($daihyoSyohinCode), $num - 1);

    return $imageName;
  }


  /**
   * バーコード出力取得 (SVG)
   * @param $code
   * @param string $type
   * @param int $w
   * @param int $h
   * @param bool $withXml
   * @return string
   */
  public function getBarcodeSVG($code, $type = 'C128', $withXml = false, $w = 1, $h = 24, $color = 'black')
  {
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');
    $cacheDir = $fileUtil->getCacheDir();

    $d = new DNS1D();
    $d->setStorPath($cacheDir);
    $svg = $d->getBarcodeSVG($code, $type, $w, $h);

    // 先頭についているXMLタグを外すかどうか
    if (!$withXml) {
      if (preg_match('/^.*?(<svg .*)$/ims', $svg, $m)) {
        $svg = $m[1];
      }
    }

    return $svg;
  }

  /**
   * 画像容量・縦横ピクセルサイズ取得
   * @param ProductImages $image
   * @param bool $isOriginal
   * @return array
   */
  public function getImageSize($image, $isOriginal = false)
  {
    $container = $this->getContainer();
    $imageDir = $isOriginal
              ? $container->getParameter('product_image_original_dir')
              : $container->getParameter('product_image_dir');

    $filePath = sprintf('%s/%s', $imageDir, $image->getFileDirPath());

    $result = [
        'size' => null
      , 'width' => null
      , 'height' => null
    ];

    $fs = new Filesystem();
    if ($fs->exists($filePath)) {
      $result['size'] = filesize($filePath);

      $info = getimagesize($filePath);
      if ($info) {
        $result['width'] = $info[0];
        $result['height'] = $info[1];
      }
    }

    return $result;
  }


}
