<?php

namespace AppBundle\Controller;

use BatchBundle\Job\BaseJob;
use BatchBundle\Job\ProductSalesJob;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Entity\TbProductSalesAccountAggregateReservation;
use MiscBundle\Entity\TbProductSalesAccountHistory;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbProductReviewsRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountHistoryRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountResultHistoryRepository;
use MiscBundle\Entity\Repository\TbProductSalesTaskRepository;
use MiscBundle\Entity\Repository\TbSalesDetailSummaryYmdRepository;
use MiscBundle\Entity\Repository\TbShoplistDailySalesRepository;
use MiscBundle\Entity\Repository\TbStockHistoryRepository;
use MiscBundle\Entity\Repository\TbTeamRepository;
use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Util\BatchLogger;

/**
 * 商品売上管理画面
 * @package AppBundle\Controller
 */
class ProductSalesController extends BaseController
{
  /**
   * 担当者別売上一覧画面 表示
   */
  public function indexAction()
  {
    $account = $this->getLoginUser();
    /** @var TbProductSalesTaskRepository $taskRep */
    $taskRep = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesTask');
    $tasks = array_map(function($task) {
      return [
        'id' => $task->getId(),
        'taskName' => $task->getTaskName(),
      ];
    }, $taskRep->findAll());

    /** @var TbProductSalesAccountRepository $aRepo */
    $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
    $salesAccounts = $aRepo->getUserList();

    /** @var SymfonyUsersRepository $uRepo */
    $uRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $defaultDisplays = $uRepo->findUsersWithRole(['ROLE_SALES_PRODUCT_DEFAULT_DISPLAY']);

    return $this->render('AppBundle:ProductSales:index.html.twig', [
      'account' => $account,
      'tasks' => json_encode($tasks),
      'salesAccounts' => json_encode($salesAccounts),
      'defaultDisplays' => json_encode($defaultDisplays),
    ]);
  }

  /**
   * 担当者別売上一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function findAction(Request $request)
  {
    ini_set('memory_limit', "768M");
    $result = [];
    try {
      /** @var TbProductSalesAccountResultHistoryRepository $repository */
      $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccountResultHistory');
      /** @var TbProductSalesAccountRepository $aRepo */
      $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
      /** @var TbMainproductsRepository $mRepo */
      $mRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbShoplistDailySalesRepository $sdRepo */
      $sdRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistDailySales');
      /** @var TbSalesDetailSummaryYmdRepository $ssRepo */
      $ssRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailSummaryYmd');
      /** @var TbStockHistoryRepository $shRepo */
      $shRepo = $this->getDoctrine()->getRepository('MiscBundle:TbStockHistory');
      /** @var TbProductReviewsRepository $rRepo */
      $rRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductReviews');

      $form = $request->get('form');
      $selectType = $request->get('selectType');

      // チェック
      $this->validateFindAction($form);
      if (!($selectType === 'account' || $selectType === 'team')) {
        throw new ValidationException('集計単位の指定(アカウント別/チーム別)が正しくありません: ' . $selectType);
      }

      // 全体・担当者なしの在庫・注残情報を取得
      $stocks = $shRepo->findStockAndRemainInPeriod($form);

      // 全体・担当者なしの期間内関連商品数を取得
      $productNum = $mRepo->findProductNumInPeriod($form);

      // 全体・担当者なしの期間内関連商品数（即納商品のみ）を取得
      $conditionWithImmediateProducts = $form;
      $conditionWithImmediateProducts['immediateProducts'] = ""; // 即納商品のみに絞り込むフラグを追加
      $immediateProductNum = $mRepo->findProductNumInPeriod($conditionWithImmediateProducts);

      // 担当者なし実績を、全体 - 担当者実績合計 で算出するために、売上系の担当者実績合計を取得
      // 売上情報・SHOPLIST情報を別々に取得するのを避けるため、ここで予め纏めて取得する。
      // タスク適用開始日の指定が有る時は、直接担当者なし実績を求めるので不要。
      $accountResultTotal = [];
      if (!$form['applyStartDateFrom'] && !$form['applyStartDateTo']) {
        $accountResultTotal = $repository->findAccountSalesResultTotal($form);
      }

      // 全体・担当者なしの期間内売上情報を取得
      $sales = $ssRepo->findSalesInPeriod($form, $accountResultTotal);

      // 全体・担当者なしの期間内SHOPLIST売上情報を取得
      $shoplistSales = $sdRepo->findShoplistSalesInPeriod($form, $accountResultTotal);

      // 全体・担当者なしのレビュー合計と、レビュー数を取得する
      $reviews = $rRepo->findReviewsInPeriod($form);

      $result['total'] = [
        'stockDate' => $stocks['stockDate'],
        'stockQuantity' => $stocks['stockQuantity'],
        'stockAmount' => $stocks['stockAmount'],
        'remainQuantity' => $stocks['remainQuantity'],
        'remainAmount' => $stocks['remainAmount'],
        'stockQuantityAvg' => $stocks['stockQuantityAvg'],
        'stockAmountAvg' => $stocks['stockAmountAvg'],
        'remainQuantityAvg' => $stocks['remainQuantityAvg'],
        'remainAmountAvg' => $stocks['remainAmountAvg'],
        'noAccountStockQuantity' => $stocks['noAccountStockQuantity'],
        'noAccountStockAmount' => $stocks['noAccountStockAmount'],
        'noAccountRemainQuantity' => $stocks['noAccountRemainQuantity'],
        'noAccountRemainAmount' => $stocks['noAccountRemainAmount'],
        'productCount' => $productNum['total'],
        'noAccountProductCount' => $productNum['noAccount'],
        'immediateProductCount' => $immediateProductNum['total'],
        'noAccountImmediateProductCount' => $immediateProductNum['noAccount'],
        'totalSales' => $sales['totalSales'],
        'totalGrossProfit' => $sales['totalGrossProfit'],
        'noAccountSales' => $sales['noAccountSales'],
        'noAccountGrossProfit' => $sales['noAccountGrossProfit'],
        'totalShoplistSales' => $shoplistSales['totalSales'],
        'totalShoplistProfit' => $shoplistSales['totalProfit'],
        'noAccountShoplistSales' => $shoplistSales['noAccountSales'],
        'noAccountShoplistProfit' => $shoplistSales['noAccountProfit'],
        'reviews' => $reviews['all'],
        'noAccountReviews' => $reviews['noAccount'],
      ];

      // 商品売上担当者の期間内関連商品数を取得する
      $result['productCountList'] = $aRepo->findProductCountByConditions($form, $selectType);

      // 集計対象毎に、1年間のレビュー合計とレビュー数を取得する
      $result['targetReviews'] = $rRepo->findTotalReviewsAndCountByTarget($form, $selectType);

      // 商品売上担当者別実績を取得する
      $result['score'] = array_map(function($item) {
        $item['salesAmount'] = (int)$item['salesAmount'];
        $item['profitAmount'] = (int)$item['profitAmount'];
        $item['shoplistSalesAmount'] = (int)$item['shoplistSalesAmount'];
        $item['shoplistProfitAmount'] = (int)$item['shoplistProfitAmount'];
        $item['stockQuantity'] = (int)$item['stockQuantity'];
        $item['stockAmount'] = (int)$item['stockAmount'];
        $item['remainQuantity'] = (int)$item['remainQuantity'];
        $item['remainAmount'] = (int)$item['remainAmount'];
        return $item;
      }, $repository->findScoreByConditions($form, $stocks['stockDate'], $selectType));

      $result['success'] = true;

    } catch (ValidationException $e) {
      $result['success'] = false;
      $result['error'] = $e->getMessage();
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('担当者別売上一覧 検索でエラー発生: ' . $e->getMessage() . $e->getTraceAsString());
      $result['success'] = false;
      $result['error'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  private function validateFindAction($form)
  {
    $targetDateFrom = $form['targetDateFrom'];
    $targetDateTo = $form['targetDateTo'];
    $applyStartDateFrom = $form['applyStartDateFrom'];
    $applyStartDateTo = $form['applyStartDateTo'];

    // チェック
    if (!$targetDateFrom && !$targetDateTo) {
      throw new ValidationException('売上日を入力してください。');
    }
    if ($targetDateFrom) {
      if (!strptime($targetDateFrom, '%Y-%m-%d')) {
        throw new ValidationException('売上日Fromの形式がyyyy-mm-ddではありません');
      }
    }
    if ($targetDateTo) {
      if (!strptime($targetDateTo, '%Y-%m-%d')) {
        throw new ValidationException('売上日Toの形式がyyyy-mm-ddではありません');
      }
    }

    if ($applyStartDateFrom) {
      if (!strptime($applyStartDateFrom, '%Y-%m-%d')) {
        throw new ValidationException('タスク適用開始日Fromの形式がyyyy-mm-ddではありません');
      }
    }
    if ($applyStartDateTo) {
      if (!strptime($applyStartDateTo, '%Y-%m-%d')) {
        throw new ValidationException('タスク適用開始日Toの形式がyyyy-mm-ddではありません');
      }
    }

  }

  /**
   * 担当者別売上明細情報取得
   * @param Request $request
   */
  public function userDetailAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var SymfonyUsersRepository $uRepo */
    $uRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    /** @var TbProductSalesTaskRepository $tRepo */
    $tRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesTask');
    /** @var TbProductSalesAccountRepository $aRepo */
    $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
    /** @var TbProductReviewsRepository $rRepo */
    $rRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductReviews');

    try {
      $selectTaskStr = $request->get('selectTask');
      $selectTaskList = $selectTaskStr ? explode(',', $selectTaskStr) : [];
      $conditions =[
        'userId' => $request->get('userId'),
        'userName' => $uRepo->find($request->get('userId'))->getUserName(),
        'stockDate' => $request->get('stockDate'),
        'targetDateFrom' => $request->get('targetDateFrom') ?: null,
        'targetDateTo' => $request->get('targetDateTo') ?: null,
        'selectTask' => $selectTaskList,
        'selectTaskName' => array_map(function($taskId) use ($tRepo){
          return $tRepo->find($taskId)->getTaskName();
        }, $selectTaskList),
        'applyStartDateFrom' => $request->get('applyStartDateFrom') ?: null,
        'applyStartDateTo' => $request->get('applyStartDateTo') ?: null,
        'sireName' => $request->get('sireName') ?: null,
      ];
      $result = [];
      $result['message'] = '';
      $result['conditions'] = $conditions;

      $result['total'] = [
        'salesAmount' => 0,
        'profitAmount' => 0,
        'shoplistSalesAmount' => 0,
        'shoplistProfitAmount' => 0,
        'stockQuantity' => 0,
        'stockAmount' => 0,
        'remainQuantity' => 0,
        'remainAmount' => 0,
      ];
      $result['list'] = [];
      $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $daihyoSyohinCodeList = []; // 集計対象となった商品リスト
      foreach($aRepo->getProductSalesByUserList($conditions) as $row) {
        // 代表商品毎の行データ格納
        $row['imageUrl'] = TbMainproductsRepository::createImageUrl($row['imageDir'], $row['imageFile'], $imageUrlParent);
        $result['list'][] = $row;
        // 対象全商品の集計行データ格納（累積）
        $result['total']['salesAmount'] += $row['salesAmount'];
        $result['total']['profitAmount'] += $row['profitAmount'];
        $result['total']['shoplistSalesAmount'] += $row['shoplistSalesAmount'];
        $result['total']['shoplistProfitAmount'] += $row['shoplistProfitAmount'];
        $result['total']['stockQuantity'] += $row['stockQuantity'];
        $result['total']['stockAmount'] += $row['stockAmount'];
        $result['total']['remainQuantity'] += $row['remainQuantity'];
        $result['total']['remainAmount'] += $row['remainAmount'];
        // 集計対象の代表商品コードを集計（レビュー平均取得に使用）
        $daihyoSyohinCodeList[] = $row['daihyoSyohinCode'];
      }

      $productCountList = $aRepo->findProductCountByConditions($conditions, 'account');
      $result['total']['productCount'] = $productCountList[$conditions['userId']]['productCount'];

      // 各商品レビュー平均点、点数を取得
      $rConditions = [
        'date_from' => (new \DateTime())->modify('-1 year'),
        'daihyo_syohin_code_list' => $daihyoSyohinCodeList,
      ];
      $reviewsResultArr = $rRepo->findProductReviewSummaryByCondition($rConditions);
      // 結果リストに商品レビュー平均点、点数を格納
      foreach($result['list'] as &$list) { // $listに対する参照を設定して変更を可能にする
        if (isset($reviewsResultArr[$list['daihyoSyohinCode']])) {
          $list['reviewPointAve'] = $reviewsResultArr[$list['daihyoSyohinCode']]['review_point_ave'];
          $list['reviewPointNum'] = $reviewsResultArr[$list['daihyoSyohinCode']]['review_point_num'];
        }
      }
      unset($list);// $listに対する参照を解除
      // 全商品レビュー平均点を取得、格納
      $result['total']['reviewAllAve'] = ($rRepo->getAllAverage($rConditions))['all_average'];
    } catch (\Exception $e) {
      $errorMessage = $e->getMessage();
      $logger->error('担当者別売上明細取得機能でエラー発生: ' . $errorMessage . $e->getTraceAsString());
      $result['message'] = $errorMessage;
    }

    return $this->render('AppBundle:ProductSales:user-detail.html.twig', [
      'account' => $this->getLoginUser(),
      'result' => json_encode($result),
    ]);
  }

  /**
   * 商品売上担当者一覧 表示
   */
  public function accountAction()
  {
    // 画面表示
    return $this->render('AppBundle:ProductSales:account.html.twig', [
      'account' => $this->getLoginUser()
    ]);
  }

  /**
   * 商品売上担当者一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function accountFindAction(Request $request)
  {
    /** @var TbMainproductsRepository $productRepo */
    $productRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    /** @var TbProductSalesAccountRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'product' => null
      , 'list' => []
    ];

    try {
      $daihyoSyohinCode = $request->get('daihyoSyohinCode');

      /** @var TbMainproducts $product */
      $product = $productRepo->find($daihyoSyohinCode);
      if (!$product) {
        throw new \RuntimeException('商品データが取得できませんでした。');
      }
      $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $imageUrl = $product->getImageUrl($imageUrlParent);

      $result['product'] = [
        'daihyoSyohinCode' => $product->getDaihyoSyohinCode()
        , 'daihyoSyohinName' => $product->getDaihyoSyohinName()
        , 'imageUrl' => $imageUrl
      ];

      $productSalesAccounts = $repo->findBy(['daihyoSyohinCode' => $daihyoSyohinCode], ['created' => 'ASC']);

      foreach($productSalesAccounts as $productSalesAccount) {
        if ($productSalesAccount->getApplyEndDate()) {
          $applyEndDate = $productSalesAccount->getApplyEndDate()->format('Y-m-d');
        } else {
          $applyEndDate = null;
        }
        $result['list'][] = [
          'id' => $productSalesAccount->getId()
          , 'created' => $productSalesAccount->getCreated()->format('Y-m-d')
          , 'applyStartDate' => $productSalesAccount->getApplyStartDate()->format('Y-m-d')
          , 'applyEndDate' => $applyEndDate
          , 'userName' => $productSalesAccount->getUser()->getUsername()
          , 'teamName' => $productSalesAccount->getTeam()->getTeamName()
          , 'taskName' => $productSalesAccount->getProductSalesTask()->getTaskName()
          , 'detail' => $productSalesAccount->getDetail()
          , 'workAmount' => $productSalesAccount->getWorkAmount()
          , 'status' => $productSalesAccount->getStatus()
          , 'updated' => $productSalesAccount->getUpdated()->format('Y-m-d')
        ];
      }

    } catch (\Exception $e) {
      $logger->error("商品売上担当者取得機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者一覧 変更
   * @param Request $request
   * @return JsonResponse
   */
  public function accountChangeAction(Request $request)
  {
    /** @var TbProductSalesAccountRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $list = $request->get('list');
      $messages = [];
      if (!$list) {
        $messages[] = '変更されていません';
      } else {
        foreach ($list as $item) {
          $validateMessage = $this->validateAccountChange($item);
          if (count($validateMessage) > 0) {
            $messages = array_merge($messages, $validateMessage);
          }
        }
      }
      if ($messages) {
        throw new ValidationException();
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');
      $dbMain->beginTransaction();
      $startDateList = []; // 修正前、修正後の適用開始日を集める。もっとも小さいものがバッチ再集計の開始日
      $endDateList = [];   // 修正前、修正後の適用終了日を集める。もっとも大きいものがバッチ再集計の最終日。nullは本日。
      foreach ($list as $item) {
        // 更新処理
        $account = $repo->find(intval($item['id']));
        $beforeAccount = [
          'applyStartDate' => $account->getApplyStartDate()->format('Y-m-d'),
          'applyEndDate' => $account->getApplyEndDate() ? $account->getApplyEndDate()->format('Y-m-d') : null,
          'detail' => $account->getDetail(),
          'workAmount' => $account->getWorkAmount()
        ];

        $account->setApplyStartDate((new \DateTime($item['applyStartDate']))->setTime(0, 0, 0));
        if (strlen($item['applyEndDate'])) {
          $account->setApplyEndDate((new \DateTime($item['applyEndDate']))->setTime(23, 59, 59));
        } else {
          $account->setApplyEndDate(null);
        }
        $account->setDetail($item['detail']);
        $account->setWorkAmount($item['workAmount']);
        $em->flush();

        $note = $this->generateNoteDifference($beforeAccount, $item);
        $accountHistory = new TbProductSalesAccountHistory();
        $accountHistory->setProcessType(TbProductSalesAccountHistory::PROCESS_TYPE_CHANGE);
        $accountHistory->setNote($note);
        $accountHistory->setUpdateAccount($this->getLoginUser());
        $accountHistory->addProductSalesAccount($account);
        $em->persist($accountHistory);
        $em->flush();

        $startDateList[] = $account->getApplyStartDate(); // 修正後
        $startDateList[] = new \DateTime($beforeAccount['applyStartDate']); // 修正前
        $endDateList[] = ($account->getApplyEndDate() ?: new \DateTime()); // 修正後
        $endDateList[] = ($beforeAccount['applyEndDate'] ? new \DateTime($beforeAccount['applyEndDate']) : new \DateTime()); // 修正前
      }
      $dbMain->commit();

      $minStartDate = min($startDateList);
      $maxEndDate = max($endDateList);

      $aggregateReservation = new TbProductSalesAccountAggregateReservation();
      $aggregateReservation->setOrdrerDateFrom($minStartDate);
      $aggregateReservation->setOrdrerDateTo($maxEndDate);
      $aggregateReservation->setDaihyoSyohinCode($request->get('daihyoSyohinCode'));
      $aggregateReservation->setAggregatedFlg(0);
      $em->persist($aggregateReservation);
      $em->flush();

    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = implode("\r\n", array_unique($messages));
    } catch (\Exception $e) {
      $logger->error("商品売上担当者変更機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $em->rollback();
      }
    }
    return new JsonResponse($result);
  }

  /**
   *
   * @param array $productSalesAccount 商品売上担当者の連想配列
   * @return array メッセージリスト
   */
  private function validateAccountChange($productSalesAccount)
  {
    $messages = [];
    $startDate = null;
    $endDate = null;
    // 適用開始日のチェック
    if (strlen($productSalesAccount['applyStartDate'])) {
      if (! strptime($productSalesAccount['applyStartDate'], '%Y-%m-%d')) {
        $messages[] = sprintf('担当者：%s 適用開始日がY-m-d形式になっていません。', $productSalesAccount['userName']);
      } else {
        $startDate = (new \DateTime($productSalesAccount['applyStartDate']))->setTime(0, 0, 0);
      }
    } else {
      $messages[] = sprintf('担当者：%s 適用開始日を入力してください', $productSalesAccount['userName']);
    }

    // 適用終了日のチェック
    if (strlen($productSalesAccount['applyEndDate'])) {
      if (! strptime($productSalesAccount['applyEndDate'], '%Y-%m-%d')) {
        $messages[] = sprintf('担当者：%s 適用終了日がY-m-d形式になっていません', $productSalesAccount['userName']);
      } else {
        $endDate = (new \DateTime($productSalesAccount['applyEndDate']))->setTime(23, 59, 59);
      }
    }

    // 適用開始日と適用終了日の期間のチェック
    if ($startDate && $endDate) {
      if ($startDate > $endDate) {
        $messages[] = sprintf('担当者：%s 適用期間が不適切です', $productSalesAccount['userName']);
      }
    }

    // 仕事量のチェック
    if (!strlen($productSalesAccount['workAmount'])) {
      $messages[] = sprintf('担当者：%s 仕事量を入力してください', $productSalesAccount['userName']);
    }
    return $messages;
  }

  /**
   * 商品売上担当者更新履歴の備考を作成
   * @param array $account 商品売上担当者の連想配列
   * @return string 備考
   */
  private function generateCurrentNote($account) {
    $endDateStr = $account['applyEndDate'] ?: '未設定';
    $note[] = sprintf('適用日：%s ～ %s', $account['applyStartDate'], $endDateStr);
    $detail = $account['detail'] ?: '未設定';
    $note[] = sprintf('詳細：%s', $detail);
    $note[] = sprintf('仕事量：%s', $account['workAmount']);
    return implode(', ', $note);
  }

  /**
   * 商品売上担当者更新履歴の更新差分の備考を作成
   * @param array $beforeAccount 変更前の商品売上担当者の連想配列
   * @param array $afterAccount 変更後の商品売上担当者の連想配列
   * @return string 備考
   */
  private function generateNoteDifference($beforeAccount, $afterAccount) {
    $note = [];
    // 適用開始日
    if ($beforeAccount['applyStartDate'] != $afterAccount['applyStartDate']) {
      $note[] = sprintf('適用開始日：%s ⇒ %s', $beforeAccount['applyStartDate'], $afterAccount['applyStartDate']);
    }

    // 適用終了日
    if ($beforeAccount['applyEndDate'] != $afterAccount['applyEndDate']) {
      $beforeEndDateStr = $beforeAccount['applyEndDate'] ?: '未設定';
      $afterEndDateStr = $afterAccount['applyEndDate'] ?: '未設定';
      $note[] = sprintf('適用終了日：%s ⇒ %s', $beforeEndDateStr, $afterEndDateStr);
    }

    // 詳細
    if ($beforeAccount['detail'] != $afterAccount['detail']) {
      $note[] = sprintf('詳細：%s ⇒ %s', $beforeAccount['detail'], $afterAccount['detail']);
    }

    // 仕事量
    if ($beforeAccount['workAmount'] != $afterAccount['workAmount']) {
      $note[] = sprintf('仕事量：%s ⇒ %s', $beforeAccount['workAmount'], $afterAccount['workAmount']);
    }

    return implode(', ', $note);
  }

  /**
   * 商品売上担当者一覧 削除
   * @param Request $request
   * @return JsonResponse
   */
  public function accountDeleteAction(Request $request)
  {
    /** @var TbProductSalesAccountRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $em = $this->getDoctrine()->getManager('main');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $em->beginTransaction();

      $id = $request->get('id');

      // 商品売上担当者 更新
      /** @var TbProductSalesAccount $account */
      $account = $repo->find($id);
      $account->setStatus(TbProductSalesAccount::STATUS_DELETE);
      $em->flush();

      // アカウント情報から履歴に追加する備考 作成
      $currentAccount = [
        'applyStartDate' => $account->getApplyStartDate()->format('Y-m-d'),
        'applyEndDate' => $account->getApplyEndDate() ? $account->getApplyEndDate()->format('Y-m-d') : null,
        'detail' => $account->getDetail(),
        'workAmount' => $account->getWorkAmount()
      ];
      $note = $this->generateCurrentNote($currentAccount);

      // 商品売上担当者更新履歴 作成
      /** @var TbProductSalesAccountHistory $accountHistory */
      $salesAcccount = $em->getRepository('MiscBundle:TbProductSalesAccount')->find($id);
      $accountHistory = new TbProductSalesAccountHistory();
      $accountHistory->setProcessType(TbProductSalesAccountHistory::PROCESS_TYPE_DELETE);
      $accountHistory->setNote($note);
      $accountHistory->setUpdateAccount($this->getLoginUser());
      $accountHistory->addProductSalesAccount($salesAcccount);
      $em->persist($accountHistory);
      $em->flush();

      $orderDateFrom = $account->getApplyStartDate();
      if ($account->getApplyEndDate()) {
        $orderDateTo = $account->getApplyEndDate();
      } else {
        $orderDateTo = new \DateTime();
      }

      $aggregateReservation = new TbProductSalesAccountAggregateReservation();
      $aggregateReservation->setOrdrerDateFrom($orderDateFrom);
      $aggregateReservation->setOrdrerDateTo($orderDateTo);
      $aggregateReservation->setDaihyoSyohinCode($account->getDaihyoSyohinCode());
      $aggregateReservation->setAggregatedFlg(0);
      $em->persist($aggregateReservation);
      $em->flush();

      $em->commit();

    } catch (\Exception $e) {
      $logger->error("商品売上担当者削除機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($em) && $em->isTransactionActive()) {
        $em->rollback();
      }
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者一覧 戻す
   * @param Request $request
   * @return JsonResponse
   */
  public function accountRestoreAction(Request $request)
  {
    /** @var TbProductSalesAccountRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $em = $this->getDoctrine()->getManager('main');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $em->beginTransaction();

      $id = $request->get('id');

      // 商品売上担当者 更新
      /** @var TbProductSalesAccount $account */
      $account = $repo->find($id);
      $account->setStatus(TbProductSalesAccount::STATUS_REGISTRATION);
      $em->flush();

      // 商品売上担当者更新履歴 作成
      /** @var TbProductSalesAccountHistory $accountHistory */
      $salesAccount = $em->getRepository('MiscBundle:TbProductSalesAccount')->find($id);
      $accountHistory = new TbProductSalesAccountHistory();
      $accountHistory->setProcessType(TbProductSalesAccountHistory::PROCESS_TYPE_ADD);

      // アカウント情報から履歴に追加する備考 作成
      $currentAccount = [
        'applyStartDate' => $salesAccount->getApplyStartDate()->format('Y-m-d'),
        'applyEndDate' => $salesAccount->getApplyEndDate() ? $salesAccount->getApplyEndDate()->format('Y-m-d') : null,
        'detail' => $salesAccount->getDetail(),
        'workAmount' => $salesAccount->getWorkAmount()
      ];
      $note = $this->generateCurrentNote($currentAccount);
      $accountHistory->setNote($note);
      $accountHistory->setUpdateAccount($this->getLoginUser());
      $accountHistory->addProductSalesAccount($salesAccount);
      $em->persist($accountHistory);
      $em->flush();

      $orderDateFrom = $account->getApplyStartDate();
      if ($account->getApplyEndDate()) {
        $orderDateTo = $account->getApplyEndDate();
      } else {
        $orderDateTo = new \DateTime();
      }

      $aggregateReservation = new TbProductSalesAccountAggregateReservation();
      $aggregateReservation->setOrdrerDateFrom($orderDateFrom);
      $aggregateReservation->setOrdrerDateTo($orderDateTo);
      $aggregateReservation->setDaihyoSyohinCode($account->getDaihyoSyohinCode());
      $aggregateReservation->setAggregatedFlg(0);
      $em->persist($aggregateReservation);
      $em->flush();

      $em->commit();

    } catch (\Exception $e) {
      $logger->error("商品売上担当者削除取消機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($em) && $em->isTransactionActive()) {
        $em->rollback();
      }
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者更新履歴 表示
   */
  public function accountHistoryAction()
  {
    $account = $this->getLoginUser();
    /** @var SymfonyUsersRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $users = [];
    foreach ($repo->findAll() as $user) {
      $users[] = [
        'id' => $user->getId(),
        'userName' => $user->getUsername()
      ];
    }
    return $this->render('AppBundle:ProductSales:account-history.html.twig', [
      'account' => $account,
      'users' => json_encode($users)
    ]);
  }

  /**
   * 商品売上担当者更新履歴 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function accountHistoryFindAction(Request $request)
  {
    $result = [];
    try {
      $form = $request->get('form');
      // 入力チェック
      if (!$form['updatedFrom'] && !$form['updatedTo'] && !$form['code'] && !$form['userId']) {
        throw new \RuntimeException('検索条件を入力してください。');
      }
      $updatedFrom = $form['updatedFrom'];
      $updatedTo = $form['updatedTo'];
      if ($updatedFrom) {
        if (!strptime($updatedFrom, '%Y-%m-%d')) {
          throw new \RuntimeException('更新日FROMの形式がyyyy-mm-ddではありません');
        }
      }
      if ($updatedTo) {
        if (!strptime($updatedTo, '%Y-%m-%d')) {
          throw new \RuntimeException('更新日Toの形式がyyyy-mm-ddではありません');
        }
      }

      // 検索処理
      /** @var TbProductSalesAccountHistoryRepository $historyRepo */
      $historyRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccountHistory');
      $allHistory = $historyRepo->findByDaihyoSyohinCodeAndUserIdAndUpdatedFromTo($form);

      if (empty($allHistory)) {
        throw new \RuntimeException('対象データがありません。');
      }
      foreach ($allHistory as $history) {
        $products = [];
        foreach (explode(',', $history['codes']) as $code) {
          $products[] = ['code' => $code];
        }

        // 処理名を取得
        $process = '';
        switch ($history['processType']) {
          case TbProductSalesAccountHistory::PROCESS_TYPE_ADD:
            $process = '追加';
            break;
          case TbProductSalesAccountHistory::PROCESS_TYPE_CHANGE:
            $process = '変更';
            break;
          case TbProductSalesAccountHistory::PROCESS_TYPE_DELETE:
            $process = '削除';
            break;
          default:
            $process = '';
            break;
        }

        // 代表商品名を取得、紐づく商品が複数ある場合は「<省略>」と表示する
        $daihyoSyohinName = count($products) === 1 ? $history['daihyoSyohinName'] : '<省略>';

        $result['list'][] = [
          "id" => $history['id'],
          "updated" => $history['updated'],
          "products" => $products,
          "daihyoSyohinName" => $daihyoSyohinName,
          "userName" => $history['username'],
          "teamName" => $history['teamName'],
          "taskName" => $history['taskName'],
          "process" => $process,
          "note" => $history['note']
        ];
      }

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('商品売上担当者更新履歴検索機能でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者追加 表示
   * @param Request $request
   */
  public function addAccountAction(Request $request)
  {
    $teams = [];
    $tasks = [];
    /** @var SymfonyUsersRepository $userRep */
    $userRep = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $users = $userRep->findRoleSalesProductAccountIdAndName();
    /** @var TbTeamRepository $teamRep */
    $teamRep = $this->getDoctrine()->getRepository('MiscBundle:TbTeam');
    foreach ($teamRep->findNotDeleteTeams() as $team) {
      $teams[] = [
        'id' => $team->getId(),
        'teamName' => $team->getTeamName()
      ];
    }
    /** @var TbProductSalesTaskRepository $taskRep */
    $taskRep = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesTask');
    foreach ($taskRep->findNotDeleteTasks() as $task) {
      $tasks[] = [
        'id' => $task->getId(),
        'taskName' => $task->getTaskName(),
        'multiProductRegisterFlg' => $task->getMultiProductRegisterFlg()
      ];
    }

    $account = $this->getLoginUser();
    return $this->render('AppBundle:ProductSales:add-account.html.twig', [
      'account' => $account,
      'users' => json_encode($users),
      'teams' => json_encode($teams),
      'tasks' => json_encode($tasks)
    ]);
  }

  /**
   * 商品売上担当者追加 対象商品確認
   * @param Request $request
   * @return JsonResponse
   */
  public function addAccountConfirmAction(Request $request)
  {
    $result = [];
    try {
      $products = $request->get('products');
      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $mainProducts = $repo->findByDaihyoSyohinCodes($products);
      if (count($mainProducts) === 0) {
        throw new \RuntimeException('対象データがありません。');
      }
      foreach ($mainProducts as $mainProduct) {
        $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
        $imageUrl = $mainProduct->getImageUrl($imageUrlParent);
        $result['list'][] = [
          'daihyoSyohinCode' => $mainProduct->getDaihyoSyohinCode(),
          'daihyoSyohinName' => $mainProduct->getDaihyoSyohinName(),
          'imageUrl' => $imageUrl
        ];
      }
      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者追加 登録
   * @param Request $request
   * @return JsonResponse
   */
  public function addAccountRegisterAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [];
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    try {
      $form = $request->get('form');

      // 入力チェック
      if (!strptime($form['applyStartDate'], '%Y-%m-%d')) {
        throw new \RuntimeException('適用開始日の形式がyyyy-mm-ddではありません');
      }
      if ($form['applyEndDate']) {
        if (!strptime($form['applyEndDate'], '%Y-%m-%d')) {
          throw new \RuntimeException('適用終了日の形式がyyyy-mm-ddではありません');
        }
      }

      // 登録処理
      $taskRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesTask');
      $task = $taskRepo->find($form['taskId']);

      $dbMain->beginTransaction();
      if ($task->getMultiProductRegisterFlg()) {
        // タスクの複数対象商品登録フラグがtrueなら複数対象商品を登録する処理
        $this->insertMultiProductAndHistory($form);
      } else {
        // タスクの複数対象商品登録フラグがfalseなら単体対象商品を一括登録する処理
        $this->bulkInsertProductAndHistory($form);
      }
      $dbMain->commit();

      $em = $this->getDoctrine()->getManager('main');
      foreach ($form['list'] as $item) {
        $orderDateFrom = new \DateTime($form['applyStartDate']);
        $orderDateTo = $form['applyEndDate'] ? new \DateTime($form['applyEndDate']) : new \DateTime();

        $aggregateReservation = new TbProductSalesAccountAggregateReservation();
        $aggregateReservation->setOrdrerDateFrom($orderDateFrom);
        $aggregateReservation->setOrdrerDateTo($orderDateTo);
        $aggregateReservation->setDaihyoSyohinCode($item['daihyoSyohinCode']);
        $aggregateReservation->setAggregatedFlg(0);
        $em->persist($aggregateReservation);
        $em->flush();
      }

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error("商品売上担当者追加登録機能でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者向け 在庫定数設定 表示
   */
  public function inventoryConstantIndexAction()
  {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:ProductSales:inventory-constant.html.twig', [
      'account' => $account,
    ]);
  }

  /**
   * 商品売上担当者向け 在庫定数設定 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function inventoryConstantListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      /** @var TbMainproductsRepository $mRepo */
      $mRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

      $daihyoSyohinCode = $request->get('daihyoSyohinCode');
      $product = $mRepo->findDaihyoSyohinInfoForInventoryConstant($daihyoSyohinCode);

      $list = [];
      if ($product !== false) {
        // 担当者チェック
        /** @var TbProductSalesAccountRepository $aRepo */
        $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
        $staffList = array_map(function($userId) {
          return (int)$userId;
        }, $aRepo->findValidUserIdsByDaihyoSyohinCode($daihyoSyohinCode));
        $product['hasStaff'] = (bool)$staffList;
        $product['isStaff'] = in_array($this->getLoginUser()->getId(), $staffList, true);

        // SKU情報取得
        /** @var TbProductchoiceitemsRepository $pRepo */
        $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
        $list = $pRepo->findForInventoryConstant($daihyoSyohinCode);

        $product['deliverycodeName'] = TbMainproductsCal::$DELIVERY_CODE_LIST[(int)$product['deliverycode']];
        $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
        $product['imageUrl'] = TbMainproductsRepository::createImageUrl(
          $product['picfolderP1'],
          $product['picnameP1'],
          $imageUrlParent
        );
        $product['setFlg'] = (int)$product['setFlg'];
        $product['genkaTnk'] = (int)$product['genkaTnk'];
        $product['baikaTnk'] = (int)$product['baikaTnk'];
        $product['isOperating'] = $this->checkIsOperating($list);
        $product['sireAddresses'] = $product['sireAddresses'] ? explode(',', $product['sireAddresses']) : [];
      }

      $result['product'] = $product;
      $result['list'] = array_map(function($row) {
        $row['inventoryConstant'] = (int)$row['inventoryConstant'];
        $row['orderScore'] = (int)$row['orderScore'];
        $row['seasonInventoryConstant'] = (int)$row['seasonInventoryConstant'];
        $row['stockQuantity'] = (int)$row['stockQuantity'];
        $row['freeInventoryQuantity'] = (int)$row['freeInventoryQuantity'];
        $row['airOrderRemaining'] = (int)$row['airOrderRemaining'];
        $row['containerOrderRemaining'] = (int)$row['containerOrderRemaining'];
        return $row;
      }, $list);

      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error('商品売上担当者向け 在庫定数設定 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者向け 在庫定数設定 在庫定数更新
   * @param Request $request
   * @return JsonResponse
   */
  public function inventoryConstantSaveAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [];
    $title = '商品売上担当者向け 在庫定数設定';
    try {
      $modifyList = $request->get('modifyList');
      $needInterruptCheck = json_decode($request->get('needInterruptCheck'));

      // 稼働中かの簡易チェック(トランザクションまでは行わない)
      if ($needInterruptCheck) {
        /** @var TbProductchoiceitemsRepository $pRepo */
        $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

        $daihyoSyohinCode = $request->get('daihyoSyohinCode');
        $list = $pRepo->findForInventoryConstant($daihyoSyohinCode);
        $isOperating = $this->checkIsOperating($list);
        if ($isOperating) {
          throw new ValidationException('既に稼働中の為、更新できません。再検索してご確認ください');
        }
      }

      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $logger->info($title . ' 実行: "user_id": ' . $this->getLoginUser()->getId() . ',' . json_encode($modifyList));
      $repo->updateInventoryConstant($modifyList);
      $result['status'] = 'ok';

    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error($title . ' 在庫定数更新でエラー発生: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品売上担当者向け 在庫定数設定 在庫定数リセット日更新
   * @param Request $request
   * @return JsonResponse
   */
  public function inventoryConstantResetDateSaveAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [];
    $title = '商品売上担当者向け 在庫定数設定';
    try {
      /** @var TbMainproductsRepository $mRepo */
      $mRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $daihyoSyohinCode = $request->get('daihyoSyohinCode');
      $resetDate = $request->get('resetDate');
      $this->resetDateValidate($resetDate);
      $resetDate = $resetDate ? new \DateTime($resetDate) : NULL;
      $mRepo->updateInventoryConstantResetDate($daihyoSyohinCode, $resetDate);
      $result['status'] = 'ok';

    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error($title . ' 在庫定数リセット日更新でエラー発生: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 複数対象商品を登録する処理。
   * 複数商品に対して1履歴登録する。。
   * @param array $form 登録時に画面から送られるform
   */
  private function insertMultiProductAndHistory($form)
  {
    $em = $this->getDoctrine()->getManager('main');

    /** @var SymfonyUsersRepository $userRepo */
    $userRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $user = $userRepo->find($form['userId']);
    /** @var TbTeamRepository $teamRepo */
    $teamRepo = $this->getDoctrine()->getRepository('MiscBundle:TbTeam');
    $team = $teamRepo->find($form['teamId']);
    /** @var TbProductSalesTaskRepository $taskRepo */
    $taskRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesTask');
    $task = $taskRepo->find($form['taskId']);

    $history = new TbProductSalesAccountHistory();
    $workAmount = 0;
    foreach ($form['list'] as $item) {
      /** @var TbMainproductsRepository $productRepo */
      $productRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $product = $productRepo->find($item['daihyoSyohinCode']);
      $productSalesAccount = new TbProductSalesAccount();
      $productSalesAccount->setProduct($product);
      $productSalesAccount->setUser($user);
      $productSalesAccount->setTeam($team);
      $productSalesAccount->setProductSalesTask($task);
      $productSalesAccount->setStatus(TbProductSalesAccount::STATUS_REGISTRATION);
      $workAmount = $item['workAmount'];
      $productSalesAccount->setWorkAmount($workAmount);
      if ($form['detail']) {
        $productSalesAccount->setDetail($form['detail']);
      }
      $productSalesAccount->setApplyStartDate((new \DateTime($form['applyStartDate']))->setTime(0, 0, 0));
      if ($form['applyEndDate']) {
        $productSalesAccount->setApplyEndDate((new \DateTime($form['applyEndDate']))->setTime(0, 0, 0));
      }
      $em->persist($productSalesAccount);
      $em->flush();
      $history->addProductSalesAccount($productSalesAccount);
    }

    $history->setProcessType(TbProductSalesAccountHistory::PROCESS_TYPE_ADD);
    // アカウント情報から履歴に追加する備考 作成
    $currentAccount = [
      'applyStartDate' => $form['applyStartDate'],
      'applyEndDate' => $form['applyEndDate'],
      'detail' => $form['detail'],
      'workAmount' => number_format($workAmount, 2)
    ];
    $note = $this->generateCurrentNote($currentAccount);
    $history->setNote($note);
    $history->setUpdateAccount($this->getLoginUser());
    $em->persist($history);
    $em->flush();
  }

  /**
   * 単体対象商品を一括登録する処理。
   * 1商品に対して1履歴で登録をする。
   * @param array $form 登録時に画面から送られるform
   */
  private function bulkInsertProductAndHistory($form)
  {
    $em = $this->getDoctrine()->getManager('main');

    /** @var SymfonyUsersRepository $userRepo */
    $userRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $user = $userRepo->find($form['userId']);
    /** @var TbTeamRepository $teamRepo */
    $teamRepo = $this->getDoctrine()->getRepository('MiscBundle:TbTeam');
    $team = $teamRepo->find($form['teamId']);
    /** @var TbProductSalesTaskRepository $taskRepo */
    $taskRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesTask');
    $task = $taskRepo->find($form['taskId']);

    foreach ($form['list'] as $item) {
      $history = new TbProductSalesAccountHistory();
      /** @var TbMainproductsRepository $productRepo */
      $productRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $product = $productRepo->find($item['daihyoSyohinCode']);
      $productSalesAccount = new TbProductSalesAccount();
      $productSalesAccount->setProduct($product);
      $productSalesAccount->setUser($user);
      $productSalesAccount->setTeam($team);
      $productSalesAccount->setProductSalesTask($task);
      $productSalesAccount->setStatus(TbProductSalesAccount::STATUS_REGISTRATION);
      $productSalesAccount->setWorkAmount($item['workAmount']);
      if ($form['detail']) {
        $productSalesAccount->setDetail($form['detail']);
      }
      $productSalesAccount->setApplyStartDate((new \DateTime($form['applyStartDate']))->setTime(0, 0, 0));
      if ($form['applyEndDate']) {
        $productSalesAccount->setApplyEndDate((new \DateTime($form['applyEndDate']))->setTime(0, 0, 0));
      }
      $em->persist($productSalesAccount);
      $history->addProductSalesAccount($productSalesAccount);
      $history->setProcessType(TbProductSalesAccountHistory::PROCESS_TYPE_ADD);
      // アカウント情報から履歴に追加する備考 作成
      $currentAccount = [
        'applyStartDate' => $form['applyStartDate'],
        'applyEndDate' => $form['applyEndDate'],
        'detail' => $form['detail'],
        'workAmount' => number_format($item['workAmount'], 2)
      ];
      $note = $this->generateCurrentNote($currentAccount);
      $history->setNote($note);
      $history->setUpdateAccount($this->getLoginUser());
      $em->persist($history);
    }
    $em->flush();
  }

  /**
   * SKU情報から、商品が稼働中であるかを判定する。
   *
   * @param array $list SKU情報
   * @return bool
   */
  public static function checkIsOperating($list)
  {
    $isOperating = false;
    foreach ($list as $row) {
      if (
        (int)$row['inventoryConstant'] !== 0 ||
        (int)$row['orderScore'] !== 0 ||
        (int)$row['seasonInventoryConstant'] !== 0 ||
        (int)$row['stockQuantity'] !== 0 ||
        (int)$row['airOrderRemaining'] !== 0 ||
        (int)$row['containerOrderRemaining'] !== 0
      ) {
        $isOperating = true;
        break;
      }
    }
    return $isOperating;
  }

  /**
   * 在庫定数リセット日のチェック
   * @param string $resetDate
   */
  private function resetDateValidate($resetDate)
  {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if ($resetDate !== '') {
      if (! preg_match($datePattern, $resetDate)) {
        throw new ValidationException('yyyy-mm-dd形式ではありません [' . $resetDate . ']');
      }
      list($year, $month, $day) = explode('-', $resetDate);
      if (!checkdate($month, $day, $year)) {
        throw new ValidationException('正しい日付ではありません [' . $resetDate . ']');
      }
      if (new \DateTime($resetDate) < (new \DateTime())->setTime(0, 0)) {
        throw new ValidationException('本日以降の日付にしてください [' . $resetDate . ']');
      }
      if ((new \DateTime())->setTime(0, 0)->modify('+1 year') < new \DateTime($resetDate)) {
        throw new ValidationException('最大で本日から1年以内の日付にしてください [' . $resetDate . ']');
      }
    }
  }
}
