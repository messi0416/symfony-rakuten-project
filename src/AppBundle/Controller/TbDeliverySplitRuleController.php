<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\TbDeliverySplitRule;
use MiscBundle\Entity\Repository\TbPrefectureRepository;
use AppBundle\Form\Type\TbDeliverySplitRuleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * TbDeliverySplitRule controller.
 *
 */
class TbDeliverySplitRuleController extends BaseController
{

    /**
     * Lists all TbDeliverySplitRule entities.
     *
     */
    public function indexAction()
    {
        /** @var TbPrefectureRepository $pRepo */
        $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

        $logger = $this->get('misc.util.batch_logger');
        $account = $this->getLoginUser();
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliverySplitRule');
        $list = $repo->findBy([], ['checkorder' => 'asc']);
        
        $data = array_map(function ($rule) use ($pRepo) {
          $item = $rule->toScalarArray();

          $prefectures = [];
          $prefectureCheckColumn = $item['prefectureCheckColumn'];
          if ($prefectureCheckColumn) {
            $prefectures = $pRepo->findCheckColumnAvailabilityPrefectures($prefectureCheckColumn);
          }
          $item['prefectures'] = $prefectures;
          return $item;
        }, $list);

        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryMethod');
        $list = $repo->findBy([], ['deliveryId' => 'asc']);

        $methodList = [];
        foreach($list as $method) {
            $methodList[$method->getDeliveryId()] = $method->getDeliveryId().":".$method->getDeliveryName();
        }

      // 画面表示
      return $this->render('AppBundle:TbDeliverySplitRule:index.html.twig', [
          'account' => $account
          , 'dataJson' => json_encode($data)
          , 'methodList' => json_encode($methodList)
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
            $logger->info($item['id']);
            
            if(!isset($item['sizecheck'])){
              $item['sizecheck'] = 0;
            }

            if(!isset($item['maxflg'])){
              $item['maxflg'] = 0;
            }
            
            $logger->info($item['sizecheck']);
            $logger->info($item['maxflg']);

            // 新規登録
            if ($item['id'] === "") {
                $item['prefectureCheckColumn'] = '';  // なぜか空文字指定してもnullになる。
                $entity = new TbDeliverySplitRule();
                $form = $this->createForm(new TbDeliverySplitRuleType(), $entity);
                $form->submit($item);

                if ($form->isValid()) {
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
                $entity = $em->getRepository('MiscBundle:TbDeliverySplitRule')->find($item['id']);
                if (!$entity) {
                    throw new \RuntimeException('更新対象がありませんでした。');
                }
                $form = $this->createForm(new TbDeliverySplitRuleType(), $entity);
                $form->submit($item);

                if ($form->isValid()) {
                    // 使用終了フラグがformだけでうまく処理されないので手動設定（submit時点では、true/falseに関わらず1が入ってしまう）
                    // -1にはできなかった（-1をsetしても1になってしまう）
                    $em->flush();
                } else {
                  $firstMessage = $this->getFormFirstErrorMessage($form);
                  if ($firstMessage) {
                    throw new \RuntimeException($firstMessage);
                  }
                  throw new \RuntimeException('更新でエラーが発生しました');
                }
            }

            $result['message'] = sprintf('送料設定を更新しました。 [ %s ]', $entity->getRulename());
            $item = $entity->toScalarArray();

            // 対象都道府県情報追加
            $prefectureCheckColumn = $item['prefectureCheckColumn'];
            if ($prefectureCheckColumn) {
              /** @var TbPrefectureRepository $pRepo */
              $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');
              $item['prefectures'] = $pRepo->findCheckColumnAvailabilityPrefectures($prefectureCheckColumn);
            } else {
              $item['prefectures'] = [];
            }
            $result['item'] = $item;

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

        $id = intval($request->get('id'));
        $logger->info($id);

        $em = $this->getDoctrine()->getManager('main');

        /** @var BaseRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliverySplitRule');
        $rule = $repo->find($id);
        if (!$rule) {
          throw new \RuntimeException('削除対象がありませんでした。');
        }

        $em->remove($rule);
        $em->flush();

        $result['message'] = sprintf('発送方法変換ルールを削除しました。 [ %s : %s ]', $id, $rule->getRulename());
        $result['id'] = $id;

      } catch (\Exception $e) {
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
