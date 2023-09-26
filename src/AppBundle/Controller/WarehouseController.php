<?php
namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\BatchLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WarehouseController extends BaseController
{
  /**
   * 倉庫一覧
   */
  public function listAction()
  {
    $account = $this->getLoginUser();

    /** @var TbWarehouseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbWarehouse[] $list */
    $data = $repo->getPullDownAll();

    // 画面表示
    return $this->render('AppBundle:Warehouse:list.html.twig', [
        'account' => $account
      , 'dataJson' => json_encode($data)
    ]);
  }

  /**
   * 1件更新処理
   * @param Request $request
   * @return JsonResponse
   */
  public function saveAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'item' => null
    ];

    try {

      $item = $request->get('item');

      if (!$item || !is_array($item) || !isset($item['id'])) {
        throw new \RuntimeException('更新情報の取得に失敗しました。');
      }

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');

      if ($item['id'] === 'new') {
        $warehouse = new TbWarehouse();
        $em->persist($warehouse);
      } else {
        $warehouse = $repo->find($item['id']);
        if (!$warehouse) {
          throw new \RuntimeException('更新対象がありませんでした。');
        }
      }

      // 簡易バリデーション
      $item['name'] = isset($item['name']) ? trim($item['name']) : '';
      $item['symbol'] = isset($item['symbol']) ? trim($item['symbol']) : '';

      if (!strlen($item['name'])) {
        throw new \RuntimeException('倉庫名を入力してください。');
      }
      if (!strlen($item['symbol'])) {
        throw new \RuntimeException('略号を入力してください。');
      }
      if ($repo->findBySymbol($item['symbol'], $item['id'])) { // id:newでも問題ないので一括
        throw new \RuntimeException('略号がすでに登録されています。');
      }
      if (!isset($item['displayOrder']) 
          || $item['displayOrder'] !== (string)intval($item['displayOrder'])){
        throw new \RuntimeException('表示順は整数値を入力してください');
      }

      $warehouse->setName($item['name']);
      $warehouse->setSymbol($item['symbol']);
      $warehouse->setDisplayOrder(intval($item['displayOrder']));
      $warehouse->setShipmentEnabled(isset($item['shipmentEnabled']) ? $item['shipmentEnabled'] : false);
      $warehouse->setShipmentPriority(isset($item['shipmentPriority']) ? intval($item['shipmentPriority']) : 0);
      $warehouse->setSaleEnabled(isset($item['saleEnabled']) ? $item['saleEnabled'] : false);
      $warehouse->setTransportPriority(isset($item['transportPriority']) ? intval($item['transportPriority']) : 0);
      $warehouse->setFbaTransportPriority(isset($item['fbaTransportPriority']) ? intval($item['fbaTransportPriority']) : 0);
      $warehouse->setShoplistFlag(isset($item['shoplistFlag']) ? intval($item['shoplistFlag']) : 0);
      $warehouse->setOwnFlg(isset($item['ownFlg']) ? intval($item['ownFlg']) : 0);
      $warehouse->setAssetFlg(isset($item['assetFlg']) ? intval($item['assetFlg']) : 0);
      $warehouse->setTerminateFlg(isset($item['terminateFlg']) ? intval($item['terminateFlg']) : 0);

      $em->flush();

      $result['message'] = sprintf('倉庫情報を更新しました。 [ %s : %s ]', $warehouse->getId(), $warehouse->getName());
      $result['item'] = $warehouse->toScalarArray();

    } catch (\Exception $e) {
      $logger->error("倉庫情報更新でエラーが発生しました。$e");

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }


  /**
   * 1件削除処理
   * @param Request $request
   * @return JsonResponse
   */
  public function removeAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'item' => null
    ];

    try {

      $id = $request->get('id');

      // 削除不可ID
      if (in_array($id, [
            TbWarehouseRepository::DEFAULT_WAREHOUSE_ID
          , TbWarehouseRepository::FBA_MULTI_WAREHOUSE_ID
      ])) {
        throw new \RuntimeException('この倉庫は削除できません。');
      }

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $warehouse = $repo->find($id);
      if (!$warehouse) {
        throw new \RuntimeException('削除対象がありませんでした。');
      }

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      $locationCount = $repoLocation->getWarehouseLocationCount($warehouse->getId());
      if ($locationCount > 0) {
        throw new \RuntimeException('倉庫にロケーションが登録されているため、削除できません。');
      }

      $em->remove($warehouse);
      $em->flush();

      $result['message'] = sprintf('倉庫情報を削除しました。 [ %s : %s ]', $id, $warehouse->getName());
      $result['id'] = $id;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }
}
