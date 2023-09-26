<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\TbSettingType;
use MiscBundle\Entity\Repository\TbTeamRepository;
use MiscBundle\Entity\TbMaintenanceSchedule;
use MiscBundle\Entity\TbTeam;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\ValidationException;

/**
 * TbShippingdivision controller.
 *
 */
class SettingController extends BaseController
{
  /**
   * 各種設定一覧
   */
  public function settingIndexAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
    
    $param = array(
      'nonDisplayFlag' => 0 // 表示のみ
    );
    
    /** @var AuthorizationChecker $authChecker */
    $authChecker = $this->get('security.authorization_checker');
    if (!$authChecker->isGranted('ROLE_ADMIN')) {
      $param['adminOnlyFlg'] = 0;
    }
    
    $list = $repo->findBy($param);
    $data = [];
    foreach($list as $setting) {
      $data[] = $setting->toScalarArray();
    }
    // 画面表示
    return $this->render('AppBundle:Setting:settingIndex.html.twig', [
        'account' => $account
        , 'dataJson' => json_encode($data)
    ]);
  }

  /**
   * 各種設定1件更新処理
   * @param Request $request
   * @return JsonResponse
   */
  public function settingSaveAction(Request $request)
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
      
      // 更新
      $em = $this->getDoctrine()->getManager();
      $entity = $em->getRepository('MiscBundle:TbSetting')->find($item['settingKey']);
      if (!$entity) {
        throw new \RuntimeException('更新対象がありませんでした。');
      }
      $form = $this->createForm(new TbSettingType(), $entity);
      $form->submit($item);
      if ($form->isValid()) {
        $entity->setSettingVal($item['settingVal']);
        $entity->setSettingDesc($item['settingDesc']);
        $entity->setupdateAccountId($account->getId());
        $em->flush();
      } else {
        $firstMessage = $this->getFormFirstErrorMessage($form);
        if ($firstMessage) {
          throw new \RuntimeException($firstMessage);
        }
        throw new \RuntimeException('更新でエラーが発生しました');
      }
      
      $result['message'] = sprintf('各種設定を更新しました。 [ %s ]', $entity->getSettingKey());
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

  private static function getFormFirstErrorMessage($form)
  {
    foreach ($form->getErrors(true, true) as $error) {
      $message = $error->getMessage();
      if ($message) {
        return $message;
      }
    }
    return null;
  }

  /**
   * 店舗情報一覧表示
   */
  public function mallIndexAction()
  {
    $account = $this->getLoginUser();
    /** @var TbShoppingMallRepository $rep */
    $rep = $this->getDoctrine()->getRepository('MiscBundle:TbShoppingMall');
    /** @var TbShoppingMall[] $malls */
    $malls = $rep->getAllMalls();

    return $this->render('AppBundle:Setting:mallIndex.html.twig', [
      'account' => $account,
      'mallsJson' => json_encode($malls)
    ]);
  }

  /**
   * 店舗情報の保存
   * @param Request $request
   * @return JsonResponse
   */
  public function mallSaveAction(Request $request)
  {
    $account = $this->getLoginUser();
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'message' => null,
      'item' => null
    ];

    try {
      $em = $this->getDoctrine()->getManager();

      $item = $request->get('item');
      
      // ID
      $mallId = $item['mallId'];
      // 付加費用率(%)
      if (!isset($item['additionalCostRatio']) 
          || $item['additionalCostRatio'] !== (string)intval($item['additionalCostRatio'])){
        throw new ValidationException('付加費用率(%)は整数値を入力してください');
      }else{
        $additionalCostRatio = intval($item['additionalCostRatio']);
        if($additionalCostRatio < 0){
          throw new ValidationException('付加費用率(%)は0以上の値を入力してください');
        }
      }
      // システム利用料(%)
      if (!isset($item['systemUsageCostRatio']) 
          || $item['systemUsageCostRatio'] !== (string)floatval($item['systemUsageCostRatio'])){
        throw new ValidationException('システム利用料(%)は整数または小数値を入力してください');
      }else{
        $systemUsageCostRatio = floatval($item['systemUsageCostRatio']);
        if($systemUsageCostRatio < 0){
          throw new ValidationException('システム利用料(%)は0以上の値を入力してください');
        }
      }
      // 送料設定に従う
      $obeyPostageSetting = ($item['obeyPostageSetting'] === 'YES')? -1: 0;
      // 表示順
      if (!isset($item['mallSort']) 
          || $item['mallSort'] !== (string)intval($item['mallSort'])){
        throw new \RuntimeException('表示順は整数値を入力してください');
      }else{
        $mallSort = intval($item['mallSort']);
      }
      // モール説明文
      $mallDesc = $item['mallDesc'];

      if ($mallId) {
        /** @var TbShoppingMall $mall */
        $mall = $this->getDoctrine()->getRepository('MiscBundle:TbShoppingMall')->find($mallId);
        $mall->setAdditionalCostRatio($additionalCostRatio);
        $mall->setSystemUsageCostRatio($systemUsageCostRatio);
        $mall->setObeyPostageSetting($obeyPostageSetting);
        $mall->setMallDesc($mallDesc);
        $mall->setMallSort($mallSort);
        $mall->setUpdateAccountId($account->getId());
      } else {
        throw new \RuntimeException('更新でエラーが発生しました');
      }
      $em->flush();
      $result['message'] = sprintf('店舗情報を更新しました。 [ %s : %s ]', $mallId, $mall->getMallName());
      $result['item'] = $mall->toScalarArray();
    } catch (ValidationException $e) {
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
   * チーム一覧表示
   */
  public function teamIndexAction()
  {
    $account = $this->getLoginUser();
    /** @var TbTeamRepository $rep */
    $rep = $this->getDoctrine()->getRepository('MiscBundle:TbTeam');
    $teams = [];
    foreach ($rep->findAll() as $team) {
      $teams[] = [
        'id' => $team->getId(),
        'teamName' => $team->getTeamName(),
        'comment' => $team->getComment()
      ];
    }
    return $this->render('AppBundle:Setting:team.html.twig', [
      'account' => $account
      , 'teamsJson' => json_encode($teams)
    ]);
  }

  /**
   * チーム編集の保存
   * @param Request $request
   * @return JsonResponse
   */
  public function teamSaveAction(Request $request)
  {
    $result = [];
    try {
      $em = $this->getDoctrine()->getManager();

      $id = $request->get('id');
      $teamName = $request->get('teamName');
      $comment = $request->get('comment');
      if (!$teamName) {
        throw new \RuntimeException('更新でエラーが発生しました');
      }
      if ($id) {
        /** @var TbTeamRepository */
        $team = $this->getDoctrine()->getRepository('MiscBundle:TbTeam')->find($id);
        $team->setTeamName($teamName);
        $team->setComment($comment ? $comment : null);
      } else {
        $team = new TbTeam();
        $team->setTeamName($teamName);
        $team->setComment($comment ? $comment : null);
        $team->setDeleteFlg(false);
        $em->persist($team);
        $result['is_new'] = true;
      }
      $em->flush();
    } catch (\Exception $e) {
      $result['error'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * メンテナンススケジュール画面表示
   * @param Request $request
   */
  public function maintenanceScheduleIndexAction(Request $request)
  {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:Setting:maintenanceScheduleIndex.html.twig', [
      'account' => $account
      , 'maintenanceTypeList' => json_encode(TbMaintenanceSchedule::MAINTENANCE_TYPE_LIST)
    ]);
  }
  
  /**
   * メンテナンススケジュール検索
   * @param Request $request
   */
  public function maintenanceScheduleFindAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
    ];
    
    try {
      // 最新10件のみ（あまり古いものを出さないため。未来のものの管理が10件で足りなくなったら改めて調整）
      // 条件を増やす場合は「終了日が現在よりあとのもの」を取るよう注意（開始日だと、現在期間内のものが出ない）
      /** @var TbMaintenanceSchedule[] $schedules */
      $schedules = $this->getDoctrine()->getRepository('MiscBundle:TbMaintenanceSchedule')->findBy([], ['startDatetime' => 'DESC'], 10);
      foreach($schedules as $schedule) {
        $result['list'][] = $schedule->toScalarArray();
      }
    } catch (\Exception $e) {
      $logger->error('メンテナンススケジュール検索でエラー発生:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * メンテナンススケジュール保存
   * @param Request $request
   */
  public function maintenanceScheduleSaveAction(Request $request) {
    $account = $this->getLoginUser();
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'message' => null,
      'item' => null
    ];
    
    try {
      $em = $this->getDoctrine()->getManager();
      $item = $request->get('item');
      
      /** @var TbMaintenanceScheduleRepository $rep */
      $rep = $this->getDoctrine()->getRepository('MiscBundle:TbMaintenanceSchedule');
      $schedule = $rep->find($item['id']);
      $this->validateMaintenanceSchedule($request);
      
      $isNew = false;
      if (!$schedule) {
        $schedule = new TbMaintenanceSchedule();
        $isNew = true;
      }
      $schedule->setMaintenanceType($item['maintenanceType']);
      // 時刻設定。startの秒は00、endの秒は59
      $startDatetime = new \DateTime($item['startDatetime']);
      $startDatetimeStr = $startDatetime->format('Y-m-d H:i:00');
      $endDatetime = new \DateTime($item['endDatetime']);
      $endDatetimeStr = $endDatetime->format('Y-m-d H:i:59');
      $schedule->setStartDatetime(new \DateTime($startDatetimeStr));
      $schedule->setEndDatetime(new \DateTime($endDatetimeStr));
      if (isset($item['note'])) $schedule->setNote($item['note']);
      $delFlg = $item['deleteFlg'] ? true : false;
      $schedule->setDeleteFlg($delFlg);
      $schedule->setUpdateAccountId($account->getId());
      if ($isNew) {
        $em->persist($schedule);
      }
      $em->flush();
      $result['message'] = 'メンテナンススケジュールを保存しました';
      $result['item'] = $schedule->toScalarArray();
    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("メンテナンススケジュール保存でエラーが発生しました" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * メンテナンススケジュールのバリデーションを行い、エラーがあればメッセージを返却する。
   * @param Request $request
   */
  private function validateMaintenanceSchedule(Request $request) {
    $item = $request->get('item');
    /** @var DateTimeUtil $dateUtil */
    $dateUtil = $this->get('misc.util.datetime');
    
    // From、Toが未設定か不正な値ならエラー
    if (!isset($item['startDatetime']) || !isset($item['startDatetime'])) {
      throw new ValidationException("メンテナンス期間が設定されていません");
    }
    $dateUtil->validateYmdHisDate($item['startDatetime']);
    $dateUtil->validateYmdHisDate($item['endDatetime']);
    
    // From < To でなければエラー
    $startDate = new \DateTime($item['startDatetime']);
    $endDate = new \DateTime($item['endDatetime']);
    if ($startDate >= $endDate) {
      throw new ValidationException("終了日は開始日よりあとの必要があります");
    }
    // 期間が1週間以上でもエラー
    $startDate->modify('+7 day');
    if ($startDate < $endDate) {
      throw new ValidationException("期間が長すぎます。1つの設定の期間は1週間以内としてください。");
    }
    
    // 種別が未登録
    if (!$item['maintenanceType']) {
      throw new ValidationException("メンテナンス種別を選択してください");
    }
  }
}