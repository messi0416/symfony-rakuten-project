<?php

namespace AppBundle\Controller;
use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Entity\Repository\TbSettingRepository;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Service\PickingScoreService;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OperationScoreController extends BaseController
{

  /**
   * My箱詰めスコア画面 TOP
   */
  public function boxedScoreIndexAction()
  {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:OperationScore:boxed-score-index.html.twig', [
      'account' => $account
    ]);
  }

  /**
   * My箱詰めスコア情報取得
   * @param Request $request
   * @return JsonResponse
   */
  public function boxedScoreFindUserLogsAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status'  => 'ok'
      , 'message' => null
      , 'firstColumnRecords'  => 0
      , 'secondColumnRecords' => 0
      , 'thirdColumnRecords'  => 0
      , 'firstColumnMyAverageBoxedRefillTime'  => 0
      , 'secondColumnMyAverageBoxedRefillTime' => 0
      , 'thirdColumnMyAverageBoxedRefillTime'  => 0
      , 'thirdColumnOverallAverageBoxedRefillTime' => 0
      , 'thirdColumnFastestAverageBoxedRefillTime' => 0
    ];
    try {
      $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $result['firstColumnRecords']  = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_RECORD_LIMIT_1)->getSettingVal();
      $result['secondColumnRecords'] = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_RECORD_LIMIT_2)->getSettingVal();
      $result['thirdColumnRecords']  = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_RECORD_LIMIT_3)->getSettingVal();

      // 過去nか月分のデータを対象とする
      $limitMonth = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_LIMIT_MONTH)->getSettingVal();
      $modifyStr = sprintf('-%s months', $limitMonth);
      $now = new \DateTime();
      $targetDate = $now->modify($modifyStr)->format('Y/m/d');

      /** @var TbProductLocationLogRepository $locationLogRepo */
      $locationLogRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocationLog', 'log');
      $userStatisticsList = $locationLogRepo->fetchBoxedRefillScoreList($targetDate);

      $username = $this->getLoginUser()->getUsername();
      foreach ($userStatisticsList as $userStatistics) {
        if ($userStatistics['username'] != $username) {
          continue;
        }
        $result['firstColumnMyAverageBoxedRefillTime']  = $userStatistics['firstColumnAverageTime'];
        $result['secondColumnMyAverageBoxedRefillTime'] = $userStatistics['secondColumnAverageTime'];
        $result['thirdColumnMyAverageBoxedRefillTime']  = $userStatistics['thirdColumnAverageTime'];
        break;
      }

      $result['thirdColumnOverallAverageBoxedRefillTime'] = $locationLogRepo->solveOverallAverageTime($userStatisticsList);
      $result['thirdColumnFastestAverageBoxedRefillTime'] = $locationLogRepo->getFastestTime($userStatisticsList);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 全体 箱詰めスコア画面　TOP
   */
  public function boxedScoreListUserAction()
  {
    // 画面表示
    return $this->render('AppBundle:OperationScore:boxed-score-list-user.html.twig');
  }

  /**
   * 全体箱詰めスコア情報取得
   * @param Request $request
   * @return JsonResponse
   */
  public function boxedScoreFindUserListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status'  => 'ok'
      , 'message' => null
      , 'firstColumnRecords'  => 0
      , 'secondColumnRecords' => 0
      , 'thirdColumnRecords'  => 0
      , 'list'    => []
    ];
    try {
      $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $result['firstColumnRecords']  = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_RECORD_LIMIT_1)->getSettingVal();
      $result['secondColumnRecords'] = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_RECORD_LIMIT_2)->getSettingVal();
      $result['thirdColumnRecords']  = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_RECORD_LIMIT_3)->getSettingVal();

      // 過去nか月分のデータを対象とする
      $limitMonth = $settingRepo->find(TbSetting::KEY_BOXED_REFILL_LIMIT_MONTH)->getSettingVal();
      $modifyStr = sprintf('-%s months', $limitMonth);
      $now = new \DateTime();
      $targetDate = $now->modify($modifyStr)->format('Y/m/d');
      /** @var TbProductLocationLogRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocationLog', 'log');
      $list = $repo->fetchBoxedRefillScoreList($targetDate);

      $result['list'] = $list;
    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * Myピッキングスコア画面
   * @return Response
   */
  public function pickingScoreindexAction()
  {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:OperationScore:picking-score-index.html.twig', [
      'account' => $account
    ]);
  }

  /**
   * Myピッキングスコア情報取得
   * @param Request $request
   * @return JsonResponse
   */
  public function pickingScoreFindUserLogsAction(Request $request)
  {
    $result = [
        'status'  => 'ok'
      , 'message' => null
      , 'firstColumnRecords'  => 0
      , 'secondColumnRecords' => 0
      , 'thirdColumnRecords'  => 0
      , 'pickingScore' => [
          'SC' => [
            'firstColumnAverageTime'  => 0,
            'secondColumnAverageTime' => 0,
            'thirdColumnAverageTime'  => 0
          ],
          'V' => [
            'firstColumnAverageTime'  => 0,
            'secondColumnAverageTime' => 0,
            'thirdColumnAverageTime'  => 0
          ],
          'OTHERS' => [
            'firstColumnAverageTime'  => 0,
            'secondColumnAverageTime' => 0,
            'thirdColumnAverageTime'  => 0
          ]
        ]
      , 'averageTime' => ['SC' => 0, 'V' => 0, 'OTHERS' => 0]
      , 'fastestTime' => ['SC' => 0, 'V' => 0, 'OTHERS' => 0]
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $result['firstColumnRecords']  = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_LIMIT_1);
      $result['secondColumnRecords'] = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_LIMIT_2);
      $result['thirdColumnRecords']  = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_LIMIT_3);

      /** @var $pickingScoreService PickingScoreService */
      $pickingScoreService = $this->container->get('misc.service.picking_score');
      $pickingScoreData = $pickingScoreService->fetchUserPickingScore($this->getLoginUser()->getId());
      $result = array_merge($result, $pickingScoreData);
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('Myピッキングスコア情報取得: ' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 全体ピッキングスコア画面
   * @return Response
   */
  public function pickingScoreListUserAction()
  {
    $result = [
      'minSecond'           => 0
      , 'maxSecond'           => 0
    ];
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    $result['minSecond'] = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_SECOND_MIN);
    $result['maxSecond']  = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_SECOND_MAX);
    
    // 画面表示
    return $this->render('AppBundle:OperationScore:picking-score-list-user.html.twig', 
      $result);
  }

  /**
   * 全体ピッキングスコア情報取得
   * @param Request $request
   * @return JsonResponse
   */
  public function pickingScoreFindUserListAction(Request $request)
  {
    $result = [
        'status'  => 'ok'
      , 'message' => null
      , 'firstColumnRecords'  => 0
      , 'secondColumnRecords' => 0
      , 'thirdColumnRecords'  => 0
      , 'list' => []
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');
      $result['firstColumnRecords']  = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_LIMIT_1);
      $result['secondColumnRecords'] = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_LIMIT_2);
      $result['thirdColumnRecords']  = $commonUtil->getSettingValue(TbSetting::KEY_PICKING_RECORD_LIMIT_3);

      /** @var $service PickingScoreService */
      $service = $this->container->get('misc.service.picking_score');
      $result['list'] = $service->fetchPickingScoreList();
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('全体ピッキングスコア情報取得: ' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
}
