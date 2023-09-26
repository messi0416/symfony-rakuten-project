<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\Repository\TmpProductImagesRepository;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TmpProductImages;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\ImageUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class SaveTmpImageCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:save-tmp-image')
      ->setDescription('メール送信の確認')
      ->addArgument('daihyo-syohin-code', InputArgument::REQUIRED, '代表商品コード')
      ->addArgument('image-key', InputArgument::REQUIRED, '一時画像キー')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $daihyoSyohinCode = $input->getArgument('daihyo-syohin-code');
    $imageKey = $input->getArgument('image-key');

    var_dump($daihyoSyohinCode);
    var_dump($imageKey);

    // EntityManager ... ※なぜ Repository から取得できない仕様なのか不明。
    /** @var EntityManager $emMain */
    $emMain = $this->getDoctrine()->getManager('main');

    /** @var ImageUtil $imageUtil */
    $imageUtil = $this->getContainer()->get('misc.util.image');

    // 商品マスタ
    /** @var BaseRepository $productRepository */
    $productRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts', 'main');
    /** @var TbMainproducts $product */
    $product = $productRepository->find($daihyoSyohinCode);
    if (!$product) {
      throw new \RuntimeException('該当の商品が見つかりませんでした。');
    }

    // 一時画像
    /** @var TmpProductImagesRepository $tmpImageRepo */
    $tmpImageRepo = $this->getDoctrine()->getRepository('MiscBundle:TmpProductImages', 'tmp');
    $tmpImages = $tmpImageRepo->findByImageKey($imageKey, $daihyoSyohinCode);

    if (!$tmpImages) {
      echo "no tmp images. abort.";
      $logger->info('no tmp images');
      return 0;
    }

    /** @var TmpProductImages $image */
    foreach($tmpImages as $image) {

      // 画像レコードの取得
      /** @var ProductImagesRepository $productImageRepository */
      $productImageRepository = $this->getDoctrine()->getRepository('MiscBundle:ProductImages', 'main');
      /** @var ProductImages $productImage */
      $productImage = $productImageRepository->findOneBy([
          'daihyo_syohin_code' => $daihyoSyohinCode
        , 'code' => $image->getImageCode()
      ]);

      // 新規・更新
      // レコードの新規作成
      if (!$productImage) {
        $productImage = new ProductImages();
        $productImage->setDaihyoSyohinCode($daihyoSyohinCode);
        $productImage->setCode($image->getImageCode());
        $emMain->persist($productImage);
      }

      $productImage->setAddress($product->getImageFieldData('address'     , $image->getImageCode()));
      $productImage->setDirectory($product->getImageFieldData('directory' , $image->getImageCode()));
      $productImage->setFilename($product->getImageFieldData('filename'   , $image->getImageCode()));
      $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

      // 画像ファイルの（上書き）保存
      $originalFilePath = $imageUtil->saveTmpProductImageToOriginal($productImage, $image);
      if (!$originalFilePath) {
        throw new \RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getDirectory() . '/' . $productImage->getFilename() );
      }

      // 画像ファイルの加工処理
      $imageUtil->convertOriginalFileToFixedFile($originalFilePath);

      // オリジナル画像でmd5取得
      $productImage->setMd5hash(hash_file('md5', $originalFilePath));

      // 類似画像チェック用 文字列作成・格納（上書き） → 不要

    }

    echo "done!";
    return 0;
  }

}
