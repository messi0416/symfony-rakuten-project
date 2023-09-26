<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\ForestMailtemplates;
use MiscBundle\Entity\Repository\ForestMailtemplatesRepository;
use MiscBundle\Entity\Repository\TbConciergeOperationTaskRepository;
use MiscBundle\Entity\Repository\TbOrderDataMainaddRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbProductLocationRepository;
use MiscBundle\Entity\Repository\TbSalesDetailAnalyzeRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbConciergeOperationLog;
use MiscBundle\Entity\TbConciergeOperationLogTarget;
use MiscBundle\Entity\TbConciergeOperationTask;
use MiscBundle\Entity\TbOrderDataMainadd;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Service\ShippingVoucherService;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * コンシェルジュ関連
 * @package AppBundle\Controller
 */
class ConciergeController extends BaseController
{
  /**
   * 作業ログ 表示
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function operationLogAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Concierge:operation-log.html.twig', [
        'account' => $account
    ]);
  }

  /**
   * 作業ログ タスクリスト取得
   * @return JsonResponse
   */
  public function operationLogFindTaskAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbConciergeOperationTaskRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbConciergeOperationTask');
      /** @var TbConciergeOperationTask[] $taskList */
      $taskList = $repo->findBy(['deleteFlg' => 0], ['displayOrder' => 'ASC']);

      $result['list'] = array_map(function($row) {
        $list['id'] = $row->getId();
        $list['name'] = $row->getName();
        return $list;
      }, $taskList);

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("タスクリスト取得でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 作業ログ タスク追加
   * @param Request $request
   * @return JsonResponse
   */
  public function operationLogAddTaskAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbConciergeOperationTaskRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbConciergeOperationTask');
      $em = $this->getDoctrine()->getManager('main');

      $taskName = trim($request->get('taskName'));

      /** @var TbConciergeOperationTask[] $beforeTaskList */
      $beforeTaskList = $repo->findBy(['deleteFlg' => 0], ['displayOrder' => 'DESC']);
      $displayOrder = 1;
      if ($beforeTaskList) {
        $displayOrder = $beforeTaskList[0]->getDisplayOrder() + 1;
      }

      /* 追加予定のタスクについて。
          1. 過去に同じ名前で登録している場合、論理削除を解除してリストの最後に表示
          2. 過去に同じ名前で登録していない場合、追加してリストの最後に表示 */
      /** @var TbConciergeOperationTask $sameTask */
      $sameTask = $repo->findOneBy(['name' => $taskName], []);
      if ($sameTask) {
        $sameTask->setDisplayOrder($displayOrder);
        $sameTask->setDeleteFlg(0);
        $em->flush();
      } else {
        $newTask = new TbConciergeOperationTask();
        $newTask->setName($taskName);
        $newTask->setDisplayOrder($displayOrder);
        $newTask->setDeleteFlg(0);
        $em->persist($newTask);
        $em->flush();
      }

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("タスク追加でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 作業ログ タスク削除
   * @param Request $request
   * @return JsonResponse
   */
  public function operationLogDeleteTaskAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbConciergeOperationTaskRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbConciergeOperationTask');

      /** @var TbConciergeOperationTask $targetTask */
      $targetTask = $repo->find($request->get('id'));
      $targetTask->setDisplayOrder(0);
      $targetTask->setDeleteFlg(1);
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("タスク削除でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 作業ログ タスク並び替え
   * @param Request $request
   * @return JsonResponse
   */
  public function operationLogSortTaskAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbConciergeOperationTaskRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbConciergeOperationTask');

      $ids = $request->get('ids');
      $displayOrder = 1;
      foreach ($ids as $id) {
        /** @var TbConciergeOperationTask $targetTask */
        $targetTask = $repo->find($id);
        $targetTask->setDisplayOrder($displayOrder);
        $displayOrder ++;
      }
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("タスク並び替えでエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 作業ログ 登録
   * @param Request $request
   * @return JsonResponse
   */
  public function operationLogRegisterAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $dbMain->beginTransaction();

      $em = $this->getDoctrine()->getManager('main');
      $form = $request->get('form');
      $accountId = $this->getLoginUser()->getId();

      $operationLog = new TbConciergeOperationLog();
      $operationLog->setTbConciergeOperationTaskId($form['taskId']);
      $operationLog->setNote($form['note'] === '' ? null : $form['note']);
      $operationLog->setCreateAccountId($accountId);
      $em->persist($operationLog);
      $em->flush();

      if ($form['targetType'] !== '') {
        foreach ($form['targetValues'] as $targetValue) {
          $operationLogTarget = new TbConciergeOperationLogTarget();
          $operationLogTarget->setConciergeOperationLogId($operationLog->getId());
          $operationLogTarget->setTargetType($form['targetType']);
          $operationLogTarget->setTargetValue($targetValue);
          $em->persist($operationLogTarget);
        }
        $em->flush();
      }

      $dbMain->commit();

      $result['status'] = 'ok';

    } catch (\Exception $e) {
      $logger->error("作業ログ登録でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $em->rollback();
      }
    }

    return new JsonResponse($result);
  }

  /**
   * 未入金一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function unpaidListAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Concierge:unpaid-list.html.twig', [
        'account' => $account
    ]);
  }

  /**
   * 未入金一覧 データ取得処理(Ajax)
   * @return JsonResponse
   */
  public function findUnpaidListAction()
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

      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');

      /** @var TbOrderDataMainaddRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbOrderDataMainadd');
      $data = $repo->getAllUnpaidList();

      $result['list'] = $data;
      $result['count'] = count($data);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 入金催促日更新
   * @param Request $request
   * @return JsonResponse
   */
  public function updateUnpaidListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'date' => null
    ];

    try {

      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');

      $voucherNumber = $request->get('voucher_number');
      $unpaidReminderDate = $request->get('date');

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbOrderDataMainaddRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbOrderDataMainadd');
      /** @var TbOrderDataMainadd $voucher */
      $voucher = $repo->find($voucherNumber);
      if (!$voucher) {
        // なければレコード作成。他のカラムはここでは空のままとする。 (2017/04/10)
        // ※（前提条件） Accessのメール処理サポートは基本的に利用しない。
        //    夜間の日次受注取込処理で更新されるため、一応は大丈夫。（ただ、利用しない）
        $voucher = new TbOrderDataMainadd();
        $voucher->setVoucherNumber($voucherNumber);
        $em->persist($voucher);
      }

      $date = $unpaidReminderDate ? new \DateTime($unpaidReminderDate) : null;
      $voucher->setSunPaymentReminder($date);

      $em->flush();

      $result['date'] = $date ? $date->format('Y-m-d') : null;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }


  /**
   * 欠品商品一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function shortageListAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Concierge:shortage-list.html.twig', [
      'account' => $account
    ]);
  }

  /**
   * 欠品商品一覧 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findShortageListAction(Request $request)
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

      $page = $request->get('page', 1);
      $pageItemNum = $request->get('limit', 20);

      $conditions = $request->get('conditions', []);

      $orders = $request->get('orders', []);
      $fixedOrders = [];
      foreach($orders as $k => $v) {
        if ($v) {
          $fixedOrders[$k] = $v > 0 ? 'ASC' : 'DESC';
        }
      }

      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');

      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $pagination = $repo->findShortageProducts($conditions, $fixedOrders, $page, $pageItemNum);

      foreach($pagination->getItems() as $order) {
        $result['list'][] = $order;
      }

      $logger->info(print_r($result, true));

      $result['count'] = $pagination->getTotalItemCount();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷遅延一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function shippingDelayListAction()
  {
    $account = $this->getLoginUser();
    $selectShopNames = $this->getMallList();

    // 画面表示
    return $this->render('AppBundle:Concierge:shipping-delay-list.html.twig', [
        'account' => $account
      , 'borderDate' => new \DateTime()
      , 'selectShopNames' => $selectShopNames
    ]);
  }

  /**
   * 出荷遅延一覧 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findShippingDelayListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
      , 'alert_message' => ''
    ];

    try {

      $page = $request->get('page', 1);
      $pageItemNum = $request->get('limit', 20);

      $conditions = $request->get('conditions', []);
      $orders = $request->get('orders', []);
      $fixedOrders = [];
      foreach($orders as $k => $v) {
        if ($v) {
          $fixedOrders[$k] = $v > 0 ? 'ASC' : 'DESC';
        }
      }

      /** @var TbOrderDataMainaddRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbOrderDataMainadd');
      // #14092 （即納販売前提）受注日を基準に取得する方式に修正
      // $pagination = $repo->findShippingDelayList($conditions, $fixedOrders, $page, $pageItemNum);
      $pagination = $repo->findShippingDelayByOrderDateList($conditions, $fixedOrders, $page, $pageItemNum);

      foreach($pagination->getItems() as $order) {
        $result['list'][] = $order;
      }

      $result['count'] = $pagination->getTotalItemCount();

      // 特定店舗 1件の抽出時のみ、指定件数以上で警告表示
      // ※ 岩本さん曰く、通常の処理のフローは単店舗検索。そのときに作業者が「報告を忘れる」から注意喚起が必要、とのこと。
      if (
           isset($conditions['shop_ne_ids'])
        && is_array($conditions['shop_ne_ids'])
        && count($conditions['shop_ne_ids']) == 1
      ) {
        /** @var DbCommonUtil $commonUtil */
        $commonUtil = $this->get('misc.util.db_common');

        $alertShops = [
            DbCommonUtil::MALL_ID_RAKUTEN => 3
          , DbCommonUtil::MALL_ID_AMAZON  => 3
          , DbCommonUtil::MALL_ID_Q10     => 3

          // , DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE => 3 // 開発時確認用
        ];

        $neMallId = $conditions['shop_ne_ids'][0];
        $mallId = $commonUtil->getMallIdByMallCode($commonUtil->getMallCodeByNeMallId($neMallId));
        if (isset($alertShops[$mallId]) && $alertShops[$mallId] > 0) {
          // 件数取得のため、条件を変更して再検索（ 「入力出荷予定日が入っていない」 ）
          $alertConditions = [
              'shop_ne_ids' => $conditions['shop_ne_ids']
            , 'input_shipping_date_exists' => true
          ];
          if (isset($conditions['border_date'])) {
            $alertConditions['border_date'] = $conditions['border_date'];
          }

          $alertPagination = $repo->findShippingDelayByOrderDateList($alertConditions, [], 1, 1);

          $logger->info($alertPagination->getTotalItemCount());

          if ($alertPagination->getTotalItemCount() >= $alertShops[$mallId]) {
            $result['alert_message'] = '出荷遅延が多数発生しています。チームコンシェルジュへ報告してください。';
          }
        }
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * Accessにない商品一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function unknownProductListAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Concierge:unknown-product-list.html.twig', [
      'account' => $account
    ]);
  }

  /**
   * Accessにない商品一覧 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findUnknownProductListAction(Request $request)
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

      $page = $request->get('page', 1);
      $pageItemNum = $request->get('limit', 20);

      $conditions = $request->get('conditions', []);
      $orders = $request->get('orders', []);
      $fixedOrders = [];
      foreach($orders as $k => $v) {
        if ($v) {
          $fixedOrders[$k] = $v > 0 ? 'ASC' : 'DESC';
        }
      }

      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');

      /** @var TbOrderDataMainaddRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbOrderDataMainadd');
      $pagination = $repo->findUnknownProductList($conditions, $fixedOrders, $page, $pageItemNum);

      foreach($pagination->getItems() as $order) {
        $result['list'][] = $order;
      }

      $result['count'] = $pagination->getTotalItemCount();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 楽天未処理問い合わせ件数
   * ポップアップ画面
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function rakutenInquiryAction()
  {
    // 画面表示
    return $this->render('AppBundle:Concierge:rakuten-inquiry.html.twig');
  }

  /**
   * 楽天未処理問い合わせ件数
   * 指定店舗の未処理問い合わせ件数を取得
   * @param Request $request
   * @return JsonResponse
   */
  public function findRakutenInquiryAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'item' => ['past90daysCount' => 0, 'past1dayCount' => 0]
    ];

    try {
      $targetShop = $request->get('targetShop', 'rakuten');

      /** @var \MiscBundle\Util\WebAccessUtil $webAccessUtil */
      $webAccessUtil = $this->get('misc.util.web_access'); 

      // RMS ログイン
      $client = $webAccessUtil->getWebClient();
      $crawler = $webAccessUtil->rmsLogin($client, 'api', $targetShop);

      // 未処理件数取得
      $filterdCrawler = $crawler->selectLink("(未返信)");
      $linkText = $filterdCrawler->text();
      $tmpText = trim($linkText);
      $tmpText = preg_replace('/\s+/', ' ', $tmpText);
      $splitText = explode(' ', $tmpText);
  
      $result['item']['past90daysCount'] = $splitText[2];
      $result['item']['past1dayCount'] = $splitText[4];
  
    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * メールテンプレート
   * ポップアップ画面
   * @param string $type
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function mailTemplateAction($type)
  {
    $titles = [
        ForestMailtemplatesRepository::TYPE_DEFAULT => 'メールテンプレート'
      , ForestMailtemplatesRepository::TYPE_YAHOO => 'メールテンプレート(Y!)'
    ];
    if (!isset($titles[$type])) {
      throw new \RuntimeException('unknown template type.');
    }

    // 画面表示
    return $this->render('AppBundle:Concierge:mail-template-list.html.twig', [
        'type' => $type
      , 'title' => $titles[$type]
    ]);
  }

  /**
   * メールテンプレート 一覧取得
   * @param string $type
   * @return JsonResponse
   */
  public function findMailTemplateAction($type)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'choiceList' => []
    ];

    try {
      /** @var ForestMailtemplatesRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:ForestMailtemplates');
      $result['list'] = $repo->getAllMailTemplateList($type);
      $result['choiceList'] = $repo->getFilterChoiceList($type);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * メールテンプレート 一件取得
   * @param Request $request
   * @param string $type
   * @return JsonResponse
   */
  public function findMailTemplateOneAction(Request $request, $type)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'item' => null
    ];

    try {

      /** @var ForestMailtemplatesRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:ForestMailtemplates');
      /** @var ForestMailTemplates $item */
      $item = $repo->find($request->get('id'));
      $result['item'] = $item ? $item->toScalarArray() : null;

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }

  /**
   * メールテンプレート 一件保存
   * @param Request $request
   * @param string $type
   * @return JsonResponse
   */
  public function saveMailTemplateAction(Request $request, $type)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'item' => null
    ];

    try {
      /** @var ForestMailtemplatesRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:ForestMailtemplates');
      $em = $this->getDoctrine()->getManager('main');

      $id = $request->get('id');
      // 新規登録
      if (!$id) {
        $item = new ForestMailtemplates();
        $item->setType($type);
        $em->persist($item);

      // 更新
      } else {
        /** @var ForestMailTemplates $item */
        $item = $repo->find($request->get('id'));
        if (!$item) {
          throw new \RuntimeException('データが見つかりませんでした。');
        }
      }

      // 気持ち程度のバリデーション
      if (!$request->get('title') || !$request->get('body')) {
        throw new \RuntimeException('タイトルと本文は必須です。');
      }

      $item->setChoices1($repo->fixChoiceText($request->get('choices1', '')));
      $item->setChoices2($repo->fixChoiceText($request->get('choices2', '')));
      $item->setChoices3($repo->fixChoiceText($request->get('choices3', '')));
      $item->setChoices4($repo->fixChoiceText($request->get('choices4', '')));
      $item->setChoices5($repo->fixChoiceText($request->get('choices5', '')));
      $item->setChoices6($repo->fixChoiceText($request->get('choices6', '')));
      $item->setChoices7($repo->fixChoiceText($request->get('choices7', '')));
      $item->setChoices8($repo->fixChoiceText($request->get('choices8', '')));
      $item->setChoices9($repo->fixChoiceText($request->get('choices9', '')));

      $item->setTitle($request->get('title'));
      $item->setBody($request->get('body'));
      $item->setActive($request->get('active', 0));

      $em->flush();

      $result['message'] = sprintf('メールテンプレートを更新しました。 %d : %s', $item->getId(), $item->getTitle());
      $result['item'] = $item->toScalarArray();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 同梱漏れチェック 一覧画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function uncombinedOrderListAction(Request $request)
  {
    $selectShopNames = $this->getMallList();

//    // Qoo10は除外 ... と思わせてやっぱり含む。
//    /** @var DbCommonUtil $commonUtil */
//    $commonUtil = $this->get('misc.util.db_common');
//    $q10Id = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_Q10)->getNeMallId();
//    unset($selectShopNames[$q10Id]);

    $conditions = [
        'shop' => $request->get('shop', null)
      , 'readyOnly' => $request->get('ready', 0)
    ];

    /** @var TbSalesDetailAnalyzeRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');
    $data = $repo->getUncombinedOrderList($conditions);
    foreach($data as $i => $row) {
      $list = explode("\n", $row['伝票番号']);
      $numbers = [];
      foreach($list as $j => $line) {
        if (preg_match('/^(\d+) (.*)/', $line, $m)) {
          $numbers[] = [
              'number' => $m[1]
            , 'shop' => $m[2]
          ];
        }
      }
      $data[$i]['伝票番号'] = $numbers;
    }

    // 画面表示
    return $this->render('AppBundle:Concierge:uncombined-order-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'data' => $data
      , 'selectShopNames' => $selectShopNames
      , 'conditions' => $conditions
    ]);

  }

  /**
   * FBAマルチ商品 混合受注一覧
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function fbaMultiIncludedOrderListAction(Request $request)
  {
//    $selectShopNames = $this->getMallList();
//
//    $conditions = [
//        'shop' => $request->get('shop', null)
//    ];
    $conditions = [];

    /** @var TbSalesDetailAnalyzeRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');
    $data = $repo->getFbaMultiIncludedOrderList($conditions);

    // 画面表示
    return $this->render('AppBundle:Concierge:fba-multi-included-order-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'data' => $data
//      , 'selectShopNames' => $selectShopNames
      , 'conditions' => $conditions
    ]);

  }


  /**
   * 緊急出荷可否判定 在庫一覧画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function urgentShippingStockListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $voucherNumber = trim($request->get('num', ''));
    $data = [];

    /** @var TbSalesDetailRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
    $voucher = $repo->getVoucherByVoucherNumber($voucherNumber);

    if (strlen($voucherNumber)) {
      if (!$voucher) {
        $this->setFlash('warning', sprintf('伝票が見つかりませんでした。 [伝票番号: %s]', $voucherNumber));

      } else {

        /** @var TbProductchoiceitemsRepository $repoChoice */
        $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

        /** @var TbProductLocationRepository $repoLocation */
        $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');

        foreach ($voucher->getDetails() as $detail) {
          $syohinCode = $detail->getNeSyohinSyohinCode();
          /** @var TbProductchoiceitems $choice */
          $choice = $repoChoice->find($syohinCode);
          $data[$syohinCode] = [
            'choice' => $choice
          ];

          // 倉庫別在庫
          $data[$syohinCode]['stocks'] = $repoLocation->getStocksWithWarehouses($syohinCode);

          // ピッキングリスト
          $data[$syohinCode]['pickings'] = $repoLocation->getPickingListWithWarehouses($syohinCode);

          // 倉庫在庫ピッキング
          $data[$syohinCode]['warehouse_pickings'] = $repoLocation->getWarehouseStockMovePickingListWithWarehouses($syohinCode);

          // 移動伝票の倉庫在庫ピッキング未作成分（表示：移動在庫）
          $data[$syohinCode]['transport_pickings'] = $repoLocation->getTransportAssignWithWarehouses($syohinCode);
        }
      }
    }

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');

    // 画面表示
    return $this->render('AppBundle:Concierge:urgent-shipping-stock-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'voucherNumber' => $voucherNumber
      , 'voucher' => $voucher
      , 'data' => $data
      , 'warehouses' => $repoWarehouse->getPullDownObjects()
    ]);

  }

  /**
   * 出荷STOP画面 表示
   */
  public function shippingStopIndexAction()
  {
    $mall = $this->getMallList();

    return $this->render('AppBundle:Concierge:shipping-stop-list.html.twig', [
      'account' => $this->getLoginUser(),
      'mall' => $mall
    ]);
  }

  /**
   * 出荷STOP画面 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingStopFindAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var ShippingVoucherService $service */
    $service = $this->get('misc.service.shipping_voucher');

    try {
      $conditions = $request->get('conditions');
      // 検索条件の伝票番号・NEモールIDは空で無ければ、数値に変換する。
      $voucherNumber = '';
      $neMallId = '';
      if ($conditions['voucherNumber'] !== '') {
        $voucherNumber = (int)$conditions['voucherNumber'];
      }
      if ($conditions['neMallId'] !== '') {
        $neMallId = (int)$conditions['neMallId'];
      }

      // 検索処理
      $result['list'] = $service->findSalesDetailList($voucherNumber, $conditions['orderNumber'], $neMallId);
      if (empty($result['list'])) {
        $result['status'] = 'ng';
        $result['message'] = '指定された受注明細がありません。';
        return new JsonResponse($result);
      }

      $result['status'] = 'ok';

    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("出荷STOP検索機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷STOP画面 出荷STOP
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingStopWaitingAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var ShippingVoucherService $service */
    $service = $this->get('misc.service.shipping_voucher');

    try {
      $packingId = $request->get('id');
      $accountId = $this->getLoginUser()->getId();

      // 出荷STOP処理
      $result['list'] = $service->shippingStopWaiting($packingId, $accountId);

      $result['status'] = 'ok';

    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("出荷STOP機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 店舗一覧取得
   */
  private function getMallList()
  {
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    // 店舗一覧取得
    $selectMallIds = [
        DbCommonUtil::MALL_ID_RAKUTEN
      , DbCommonUtil::MALL_ID_BIDDERS
      , DbCommonUtil::MALL_ID_AMAZON
      , DbCommonUtil::MALL_ID_YAHOO
      , DbCommonUtil::MALL_ID_YAHOOKAWA
      , DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE
      , DbCommonUtil::MALL_ID_PPM
      , DbCommonUtil::MALL_ID_Q10
      , DbCommonUtil::MALL_ID_SHOPLIST
      , DbCommonUtil::MALL_ID_RAKUTEN_MINNA
      , DbCommonUtil::MALL_ID_EC01
      , DbCommonUtil::MALL_ID_EC02
      , DbCommonUtil::MALL_ID_RAKUTEN_MOTTO
      , DbCommonUtil::MALL_ID_RAKUTEN_LAFOREST
      , DbCommonUtil::MALL_ID_RAKUTEN_DOLCISSIMO
      , DbCommonUtil::MALL_ID_RAKUTEN_GEKIPLA
    ];
    $selectShopNames = [];
    foreach($selectMallIds as $id) {
      $mall = $commonUtil->getShoppingMall($id);
      if ($mall) {
        $selectShopNames[$mall->getNeMallId()] = $mall->getMallName();
      }
    }

    return $selectShopNames;
  }

  /**
   * 伝票番号検索画面表示Action
   */
  public function voucherNumberListAction() {
    $account = $this->getLoginUser();
    
    // 画面表示
    return $this->render('AppBundle:Concierge:voucher-number-list.html.twig', [
      'account' => $account,
    ]);
  }
  
  /**
   * 伝票番号検索Action
   */
  public function voucherNumberFindAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => ''
      , 'list' => []
      , 'count' => 0
    ];
    try {
      $condition = $request->get('condition', []);
      
      /** @var TbSalesDetailRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      
      // 検索中ロックしてしまうようなので分析DBで集計
      /** @var \Doctrine\DBAL\Connection $agnMain */
      $agnMain = $this->getDoctrine()->getConnection('agnDBmain');
      $result['list'] = $repo->findVoucherInfo($agnMain, $condition, 101);
      if (! $result['list']) {
        $result['status'] = 'ng';
        $result['message'] = '検索条件に該当するデータが見つかりません';
      } else if (count($result['list']) > 100) {
        array_pop($result['list']);
        $result['message'] = '該当データが100件を超えました。伝票番号が新しいほうから100件を表示しています。検索条件を絞り込んでください。';
      }
      
    } catch (\Exception $e) {
      $logger->error("伝票番号検索でエラー発生　$e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
}
