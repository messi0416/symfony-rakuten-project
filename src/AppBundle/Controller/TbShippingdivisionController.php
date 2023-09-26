<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\TbShippingdivision;
use AppBundle\Form\Type\TbShippingdivisionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * TbShippingdivision controller.
 *
 */
class TbShippingdivisionController extends BaseController
{

    /**
     * Lists all TbShippingdivision entities.
     *
     */
    public function indexAction()
    {
        $account = $this->getLoginUser();
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingdivision');
        $list = $repo->findBy([], [ 'shippingGroupCode' => 'asc', 'terminateFlg' => 'asc', 'price' => 'asc' ]);
        $data = [];
        foreach($list as $shippingdivision) {
            $data[] = $shippingdivision->toScalarArray();
        }

      // 画面表示
      return $this->render('AppBundle:TbShippingdivision:index.html.twig', [
          'account' => $account
          , 'dataJson' => json_encode($data)
          , 'shippingGroupList' => json_encode(TbShippingdivision::getShippingGroupList())
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
        $account = $this->getLoginUser();
        $result = [
            'status' => 'ok'
            , 'message' => null
            , 'item' => null
        ];

        try {

            $entity = null;
            $item = $request->get('item');

            // 新規登録
            if ($item['id'] === "") {
                $entity = new TbShippingdivision();
                $form = $this->createForm(new TbShippingdivisionType(), $entity);
                $form->submit($item);

                if ($form->isValid()) {
                    $entity->setUpdSymfonyUsersId($account->getId()); // ログインユーザIDを追加設定
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($entity);
                    $em->flush();
                } else {
                  $firstMessage = $this->getFormFirstErrorMessage($form);
                  if ($firstMessage) {
                    throw new \RuntimeException($firstMessage);
                  }
                  throw new \RuntimeException('新規登録でエラーが発生しました');
                }

            // 更新
            } else {
                $em = $this->getDoctrine()->getManager();
                $entity = $em->getRepository('MiscBundle:TbShippingdivision')->find($item['id']);
                if (!$entity) {
                    throw new \RuntimeException('更新対象がありませんでした。');
                }
                $form = $this->createForm(new TbShippingdivisionType(), $entity);
                $form->submit($item);

                if ($form->isValid()) {
                    // 使用終了フラグがformだけでうまく処理されないので手動設定（submit時点では、true/falseに関わらず1が入ってしまう）
                    // -1にはできなかった（-1をsetしても1になってしまう）
                    $entity->setTerminateFlg($item['terminateFlg'] === "true" ? 1 : 0);
                    $entity->setUpdSymfonyUsersId($account->getId()); // ログインユーザIDを追加設定
                    $em->flush();
                } else {
                  $firstMessage = $this->getFormFirstErrorMessage($form);
                  if ($firstMessage) {
                    throw new \RuntimeException($firstMessage);
                  }
                  throw new \RuntimeException('更新でエラーが発生しました');
                }
            }

            $result['message'] = sprintf('送料設定を更新しました。 [ %s ]', $entity->getName());
            $result['item'] = $entity->toScalarArray();

        } catch (\RuntimeException $e) {
            // バリデーションエラーの場合はログは出さない
            $result['status'] = 'ng';
            $result['message'] = $e->getMessage();
        } catch (\Exception $e) {
          $logger->error($e->getMessage());
          $logger->error($e->getTraceAsString());
          $result['status'] = 'ng';
          $result['message'] = $e->getMessage();
        }

        return new JsonResponse($result);

    }

    public static function getFormFirstErrorMessage($form)
    {
      foreach ($form->getErrors(true, true) as $error) {
        $message = $error->getMessage();
        if ($message) {
          return $message;
        }
      }
      return null;
    }
}
