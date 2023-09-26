<?php

namespace Plusnao\YahooAgentBundle\Controller;

use MiscBundle\Entity\Repository\TbYahooAgentProductRepository;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig_Environment;

class DefaultController extends BaseController
{
  /// トップページ
  public function indexAction()
  {
    // var_dump($this->getShopAccount()->getUsername());
    // var_dump(get_class($this->getLoginUser()));

    return $this->redirectToRoute('yahoo_product_list', [ 'shopCode' => $this->getShopAccount()->getShopCode() ]);
  }

  /// 商品一覧
  public function productAction(Request $request, $page)
  {
    $conditions = [];
    $conditions['shop_code'] = $this->getShopAccount()->getShopCode();
    $conditions['product_code'] = $request->get('sp');
    $conditions['update_flg'] = $request->get('su');
    $conditions['registration_flg'] = $request->get('sr');

    $orders = $request->get('sort') ? [ $request->get('sort') => $request->get('direction') ] : [];

    $pageLimit = 20; // ひとまず固定

    /** @var TbYahooAgentProductRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbYahooAgentProduct');
    $pagination = $repo->searchProductList($conditions, $orders, $pageLimit, $page);

    // Yahoo認証チェック
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->get('misc.util.web_access');

    return $this->render('PlusnaoYahooAgentBundle:Default:product-list.html.twig', [
        'account' => $this->getShopAccount()
      , 'pagination'     => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'searchUrlParams' => [
          'page' => 1
        , 'shopCode' => $this->getShopAccount()->getShopCode()
      ]
      , 'conditions' => $conditions
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'isApiEnabled' => $webAccessUtil->isEnabledYahooAgentYahooApi($this->getShopAccount())
    ]);
  }

  /**
   * 商品フラグ一括更新処理
   * @param Request $request
   * @return JsonResponse
   */
  public function productListUpdateCheckedAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      // 対象フラグ
      $action = $request->get('action');
      if (!in_array($action, ['update_flg', 'registration_flg'])) {
        throw new \RuntimeException('更新対象が正しくありません。');
      }

      $value = $request->get('value');
      if (!strlen($value) || ! in_array(intval($value), [0, -1])) {
        throw new \RuntimeException('更新値が正しくありません。');
      }

      $targets = $request->get('targets');
      if (!is_array($targets)) {
        throw new \RuntimeException('商品が選択されていません。');
      }

      $quotedTargets = [];
      foreach($targets as $target) {
        $quotedTargets[] = $dbMain->quote($target, \PDO::PARAM_STR);
      }
      $quotedTargetsStr = implode(', ', $quotedTargets);

      $sql = <<<EOD
        UPDATE tb_yahoo_agent_product yap
        SET {$action} = :value
        WHERE yap.shop_code = :shopCode
          AND yap.product_code IN ( {$quotedTargetsStr} )
EOD;
      $logger->info($sql);
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':value', $value, \PDO::PARAM_INT);
      $stmt->bindValue(':shopCode', $this->getShopAccount()->getShopCode());
      $stmt->execute();

      $actionName = $action == 'update_flg' ? '同期設定' : '出品設定';

      $result['status'] = 'ok';
      $result['message'] = sprintf('%d 件の商品の %s を更新しました。', count($targets), $actionName);

      $this->setFlash('success', $result['message']);

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }

}
