<?php
namespace AppBundle\Controller;

use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\SymfonyUserClientRepository;
use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUserClient;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\BatchLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchasingAgentController extends BaseController
{
  // ------------------------------
  // 依頼先
  /**
   * 発注依頼先一覧
   */
  public function listAction()
  {
    $account = $this->getLoginUser();

    /** @var BaseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
    /** @var PurchasingAgent[] $list */
    $list = $repo->findBy([], [ 'display_order' => 'asc' ]);
    $data = [];
    foreach($list as $agent) {
      $data[] = $agent->toScalarArray();
    }

    // 画面表示
    return $this->render('AppBundle:PurchasingAgent:list.html.twig', [
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

      /** @var BaseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');

      if ($item['id'] === 'new') {
        $agent = new PurchasingAgent();
        $em->persist($agent);
      } else {
        $agent = $repo->find($item['id']);
        if (!$agent) {
          throw new \RuntimeException('更新対象がありませんでした。');
        }
      }

      // 簡易バリデーション
      $item['name'] = isset($item['name']) ? trim($item['name']) : '';
      $item['loginName'] = isset($item['loginName']) ? trim($item['loginName']) : '';
      $item['displayOrder'] = isset($item['displayOrder']) ? trim($item['displayOrder']) : '';

      if (!strlen($item['name'])) {
        throw new \RuntimeException('依頼先名を入力してください。');
      }
      if (!strlen($item['loginName'])) {
        throw new \RuntimeException('ログイン名を入力してください。');
      }
      if (!preg_match('/[a-zA-Z0-9_-]+/', $item['loginName'])) {
        throw new \RuntimeException('ログイン名は半角英数字で入力してください。');
      }
      if (!strlen($item['displayOrder'])) {
        throw new \RuntimeException('表示順を入力してください。');
      }

      $agent->setName($item['name']);
      $agent->setLoginName($item['loginName']);
      $agent->setDisplayOrder(intval($item['displayOrder']));

      $em->flush();

      $result['message'] = sprintf('依頼先情報を更新しました。 [ %s : %s ]', $agent->getId(), $agent->getName());
      $result['item'] = $agent->toScalarArray();

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

      /** @var BaseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
      $agent = $repo->find($id);
      if (!$agent) {
        throw new \RuntimeException('削除対象がありませんでした。');
      }

      /** @var SymfonyUserClientRepository $repoUser */
      $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserClient');
      $repoUser->removeByAgentId($agent->getId());

      $em->remove($agent);
      $em->flush();

      $result['message'] = sprintf('発注先情報を削除しました。 [ %s : %s ]', $id, $agent->getName());
      $result['id'] = $id;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }

  // ------------------------------
  // 依頼先 ユーザ情報
  /**
   * 発注依頼先ユーザ一覧
   * @param int $agentId
   * @return Response
   */
  public function userListAction($agentId)
  {
    $account = $this->getLoginUser();

    /** @var BaseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
    $agent = $repo->find($agentId);

    /** @var SymfonyUserClientRepository $repoUser */
    $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserClient');

    /** @var SymfonyUserClient[] $list */
    $list = $repoUser->findBy(['agent_id' => $agentId]);
    $data = [];
    foreach($list as $user) {
      $data[] = $user->toScalarArray();
    }

    // 画面表示
    return $this->render('AppBundle:PurchasingAgent:user-list.html.twig', [
        'account' => $account
      , 'agentId' => $agentId
      , 'agent' => $agent
      , 'dataJson' => json_encode($data)
    ]);
  }

  /**
   * 1件更新処理
   * @param int $agentId
   * @param Request $request
   * @return JsonResponse
   */
  public function userSaveAction($agentId, Request $request)
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

      /** @var BaseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
      $agent = $repo->find($agentId);

      /** @var SymfonyUserClientRepository $repoUser */
      $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserClient');

      if ($item['id'] === 'new') {
        $user = new SymfonyUserClient();
        $user->setRoles('ROLE_CLIENT'); // 権限はひとまず決め打ち

        $em->persist($user);
      } else {
        /** @var SymfonyUserClient $user */
        $user = $repoUser->find($item['id']);
        if (!$user) {
          throw new \RuntimeException('更新対象がありませんでした。');
        }
        if ($user->getAgentId() != $agentId) {
          throw new \RuntimeException('更新対象の依頼先が違います。');
        }
      }

      // 簡易バリデーション
      $item['username'] = isset($item['username']) ? trim($item['username']) : '';
      if (!strlen($item['username'])) {
        throw new \RuntimeException('ユーザー名を入力してください。');
      }

      $user->setAgentId($agent ? $agent->getId() : 0);
      $user->setUsername(trim($item['username']));
      $user->setIsActive($item['isActive']);

      // パスワードは設定されていれば更新する
      $password = trim($item['password']);
      if (strlen($password)) {
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($user, $password);
        $user->setPassword($encoded);
      } else {
        // 新規の場合にはパスワードは必須
        if ($item['id'] === 'new') {
          throw new \RuntimeException('新規ユーザのパスワードを入力してください。');
        }
      }

      $em->flush();

      $result['message'] = sprintf('ユーザー情報を更新しました。 [ %s : %s ]', $user->getId(), $user->getUsername());
      $result['item'] = $user->toScalarArray();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }


  /**
   * 1件削除処理
   * @param int $agentId
   * @param Request $request
   * @return JsonResponse
   */
  public function userRemoveAction($agentId, Request $request)
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

      /** @var SymfonyUserClientRepository $repoUser */
      $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserClient');
      $user = $repoUser->find($id);
      if (!$user) {
        throw new \RuntimeException('削除対象がありませんでした。');
      }
      if ($user->getAgentId() != $agentId) {
        throw new \RuntimeException('更新対象の依頼先が違います。');
      }

      $em->remove($user);
      $em->flush();

      $result['message'] = sprintf('ユーザー情報を削除しました。 [ %s : %s ]', $id, $user->getUsername());
      $result['id'] = $id;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }





}
