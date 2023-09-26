<?php
/**
 * 認証なしURL用 コントローラ (/pub/)
 */

namespace Plusnao\MainBundle\Controller;

use Milon\Barcode\DNS1D;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\Repository\ProductImagesVariationRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductCode;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiscBundle\Entity;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends BaseController
{

  /**
   * NextEngine コールバックURL
   * 汎用処理　（何もしない）
   * @param Request $request
   * @return JsonResponse
   */
  public function neCallbackDefaultAction(Request $request)
  {
    return new JsonResponse($request->query->all());
  }

  /**
   * Alibaba コールバックURL
   * 汎用処理　（何もしない）
   * @param Request $request
   * @return JsonResponse
   */
  public function alibabaCallbackDefaultAction(Request $request)
  {
    $config =  $this->getParameter('alibaba_api');

    return $this->render('PlusnaoMainBundle:Public:alibaba-callback-default.html.twig', array(
        'code' => $request->get('code')
      , 'config' => $config
    ));

    // return new JsonResponse($request->query->all());
  }

  /**
   * 商品画像一覧画面 表示
   * ※Access画像一覧用
   * @param string $daihyoSyohinCode
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function showProductImageListAction($daihyoSyohinCode)
  {
    /** @var TbMainproducts $product */
    $product = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts')->find($daihyoSyohinCode);
    if (!$product) {
      throw $this->createNotFoundException('no product');
    }

    /** @var ProductImagesRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');

    /** @var ProductImages[] $images */
    $images = $repo->findBy(['daihyo_syohin_code' => $product->getDaihyoSyohinCode()], ['code' => 'ASC']);

    $imageUrl = sprintf('//%s/images', $this->getParameter('host_plusnao'));

    return $this->render('PlusnaoMainBundle:Public:product-image-list.html.twig', array(
        'imageUrl' => $imageUrl
      , 'images' => $images
    ));

  }

  /**
   * 商品コードバーコード表示
   * @param Request $request
   * @param string $code
   * @return Response
   */
  public function barcodeProductCodeAction(Request $request, $code)
  {
    $type = $request->get('type', 'C128');
    $w = $request->get('w', 1);
    $h = $request->get('h', 24);

    /** @var ImageUtil $imageUtil */
    $imageUtil = $this->get('misc.util.Image');
    $svg = $imageUtil->getBarcodeSVG($code, $type, true, $w, $h);

    $headers = [
      'Content-Type' => 'image/svg+xml'
    ];
    $response = new Response($svg, 200, $headers);

    return $response;
  }

  /**
   * 商品カラー画像表示 バーコード遷移
   * @param String $barcode
   * @return Response
   */
  public function showProductImageWithBarcodeAction($barcode)
  {
    /** @var TbProductchoiceitemsRepository $repoChoice */
    $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
    /** @var TbProductchoiceitems $choice */
    $choice = $repoChoice->findByProductCode($barcode);

    return $this->showProductColorImageAction($choice ? $choice->getNeSyohinSyohinCode() : null);
  }

  /**
   * 商品カラー画像 商品コード指定
   */
  public function showProductColorImageAction($syohinCode)
  {
    $variationImage = null;
    $images = [];
    $imageUrl = sprintf('//%s/images', $this->getParameter('host_plusnao'));
    $variationImageUrl = sprintf('//%s/variation_images', $this->getParameter('host_plusnao'));

    $choice = null;
    if ($syohinCode) {
      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems $choice */
      $choice = $repoChoice->find($syohinCode);
    }

    if ($choice) {
      // カラー画像
      /** @var ProductImagesVariationRepository $repoColorImages */
      $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');
      $variationImage = $repoColorImages->findByNeSyohinSyohinCode($choice->getNeSyohinSyohinCode());

      /** @var ProductImagesRepository $repo */
      $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      /** @var ProductImages[] $images */
      $images = $repoImages->findBy(['daihyo_syohin_code' => $choice->getDaihyoSyohinCode()], ['code' => 'ASC']);
    }

    return $this->render('PlusnaoMainBundle:Public:product-variation-image-list.html.twig', array(
        'choice' => $choice
      , 'variationImage' => $variationImage
      , 'imageUrl' => $imageUrl
      , 'variationImageUrl' => $variationImageUrl
      , 'images' => $images
    ));
  }

  /**
   * サーバ生成ランダムID作成
   * @param string $prefix
   * @return string
   */
  public function getUniqueIdAction($prefix)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');
    return new Response($stringUtil->getUniqueId($prefix), Response::HTTP_OK, [ 'ContentType' => 'text/plain' ]);
  }

}
