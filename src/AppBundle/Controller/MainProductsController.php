<?php

namespace AppBundle\Controller;

use AppBundle\Entity\TbMainproducts;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use MiscBundle\Entity\Repository\TbMainproductsRepository;

class MainProductsController extends BaseController
{
  private $displayCount = 20;
  public function mainInfoAction(Request $request)
  {
    // 画面表示
    $account = $this->getLoginUser();

    /** @var TbMainproductsRepository $productRepository */
    $repository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $count = 0;
    $result = $repository->searchMallProductByCode( null, $this->displayCount, 1, $count );

    return $this->render('AppBundle:Products:mainInfo.html.twig', [
      'account' => $account,
      'dataJson' => json_encode($result),
      'count' => $count,
    ]);
  }

  /**
   * 商品一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function searchInfoAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var TbMainproductsRepository $repo */
    $repository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $result = [
      'status' => 'ok',
      'message' => null,
      'list' => [],
      'count' => 0,
    ];

    try {
      // JSからの情報を検索条件として、$conditionsに格納。
      // 配列が空の場合、キーごと削除されるので、空配列として再定義。
      $conditions =  $request->get('conditions');

      $paging = $request->get('paginationObj');
      $limit = (int)$paging['initPageItemNum'];
      $page = (int)$paging['page'];

      $daihyoSyohinCode = null;
      if (isset($conditions['daihyoSyohinCode']) && !empty($conditions['daihyoSyohinCode'])) {
        $daihyoSyohinCode = $conditions['daihyoSyohinCode'];
      }

      $result['list'] = $repository->searchMallProductByCode( $daihyoSyohinCode, $limit, $page, $result['count'] );
    } catch (\Exception $e) {
      $logger->error('商品情報検索でエラーが発生しました：' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
}