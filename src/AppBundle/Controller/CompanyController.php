<?php
namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbCompanyRepository;
use MiscBundle\Entity\TbCompany;
use MiscBundle\Util\BatchLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CompanyController extends BaseController
{
  /**
   * 会社一覧
   */
  public function listAction()
  {
    $account = $this->getLoginUser();

    /** @var TbCompanyRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');
    /** @var TbCompany[] $list */
    $data = $repo->getPullDownAll();

    // 画面表示
    return $this->render('AppBundle:Company:list.html.twig', [
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

      /** @var TbCompanyRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');

      if ($item['id'] === 'new') {
        $Company = new TbCompany();
        $em->persist($Company);
      } else {
        $Company = $repo->find($item['id']);
        if (!$Company) {
          throw new \RuntimeException('更新対象がありませんでした。');
        }
      }

      // 簡易バリデーション
      $item['name'] = isset($item['name']) ? trim($item['name']) : '';
      $item['code'] = isset($item['code']) ? trim($item['code']) : '';

      if (!strlen($item['name'])) {
        throw new \RuntimeException('会社名を入力してください。');
      }
      if (!strlen($item['code'])) {
        throw new \RuntimeException('会社コードを入力してください。');
      }

      $Company->setName($item['name']);
      $Company->setCode($item['code']);
      $Company->setDisplayOrder($item['displayOrder']);
      $Company->setStatus($item['status']);

      $em->flush();

      $result['message'] = sprintf('会社情報を更新しました。 [ %s : %s ]', $Company->getId(), $Company->getName());
      $result['item'] = $Company->toScalarArray();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

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

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbCompanyRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');
      $Company = $repo->find($id);
      if (!$Company) {
        throw new \RuntimeException('削除対象がありませんでした。');
      }

      $em->remove($Company);
      $em->flush();

      $result['message'] = sprintf('会社情報を削除しました。 [ %s : %s ]', $id, $Company->getName());
      $result['id'] = $id;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }
}
