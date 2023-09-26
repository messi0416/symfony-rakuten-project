<?php

namespace AppBundle\Controller;

use BatchBundle\Job\MainJob;
use MiscBundle\Entity\Repository\Tb1688VendorRepository;
use MiscBundle\Entity\Tb1688Vendor;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 仕入先関連
 * @package AppBundle\Controller
 */
class VendorController extends BaseController
{
  /**
   * アリババ（・タオバオ） 登録済み店舗一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function foreignVendorListAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Vendor:foreign-vendor-list.html.twig', [
        'account' => $account
    ]);
  }

  /**
   * 一覧画面 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findForeignVendorListAction(Request $request)
  {
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
    ];

    $page = $request->get('page', 1);
    $pageItemNum = $request->get('limit', 20);

    /** @var Tb1688VendorRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:Tb1688Vendor');
    $pagination = $repo->findVendorList([], [], $page, $pageItemNum);

    $this->get('misc.util.batch_logger')->info('page : ' . $page);


    /** @var Tb1688Vendor $vendor */
    foreach($pagination->getItems() as $vendor) {
      $result['list'][] = $vendor->toScalarArray();
    }

    $result['count'] = $pagination->getTotalItemCount();

    return new JsonResponse($result);
  }


  // -----------------------------------------------
  // private methods
  // -----------------------------------------------


}
