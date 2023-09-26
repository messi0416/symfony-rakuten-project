<?php

namespace Plusnao\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MiscBundle\Entity\TbRakutenReviews;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiscBundle\Entity;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends BaseController
{
  /**
   * 商品レビュー一覧取得処理
   */
  public function getProductReviewsAction(Request $request)
  {
    $result = [];
    if ($request->get('daihyo_syohin_code')) {
      /** @var EntityRepository $repo */
      $repo = $this->get('doctrine')->getRepository('MiscBundle:TbRakutenReviews');
      $tmp = $repo->findBy([ 'daihyo_syohin_code' =>  $request->get('daihyo_syohin_code')], ['post_datetime' => 'DESC', 'order_datetime' => 'DESC']);
      /** @var TbRakutenReviews $row */
      foreach($tmp as $row) {
        $result[] = $row->toArray();
      }
    }

    return new JsonResponse($result);
  }

}
