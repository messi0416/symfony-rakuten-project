<?php

namespace AppBundle\Controller;

use BatchBundle\Job\MainJob;
use BatchBundle\MallProcess\ShoplistMallProcess;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Entity\Repository\TbDeleteExcludedProductsRepository;
use MiscBundle\Entity\Repository\TbDiscountListRepository;
use MiscBundle\Entity\Repository\TbDiscountSettingRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbProductCostRateListRepository;
use MiscBundle\Entity\Repository\TbProductCostRateListSettingRepository;
use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Entity\Repository\TbShoppingMallRepository;
use MiscBundle\Entity\Repository\VProductMallPriceRepository;
use MiscBundle\Entity\TbDeleteExcludedProducts;
use MiscBundle\Entity\TbDiscountList;
use MiscBundle\Entity\TbProductCostRateList;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Form\TbDeleteExcludedProductsType;
use MiscBundle\Form\TbDiscountSettingCopyType;
use MiscBundle\Form\TbProductCostRateListSettingType;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MiscBundle\Entity\TbProductchoiceitemsShippingdivisionPending;
use MiscBundle\Service\RakutenService;

/**
 * 商品管理画面
 * @package AppBundle\Controller
 */
class ProductsController extends BaseController
{
  /** デフォルト値：値下げ一覧機能 - 販売完了残日数 */
  const DEFAULT_VALUE_DISCOUNT_LIST_REMAINING_DAYS = 999;

  /**
   * 商品別原価率一覧
   * @param Request $request
   * @param int $page
   * @return Response
   */
  public function costRateListAction(Request $request, $page)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $conditions = [];
    $orders = [];
    $limit = 20;

    if ($request->get('sort')) {
      $orders[$request->get('sort')] = $request->get('direction', 'ASC');
    }
    // 並び順初期値は対象期間平均伝票数 降順
    if (!$orders) {
      $orders['p.threshold_term_voucher_num_average'] = 'DESC';
    }

    // 設定値取得
    /** @var TbProductCostRateListSettingRepository $settingRepo */
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateListSetting');
    $setting = $settingRepo->getCurrentSetting();

    $settingForm = $this->createForm(new TbProductCostRateListSettingType(), $setting);

    /** @var TbProductCostRateListRepository $listRepo */
    $listRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateList');

    // 値引き一覧 再集計
    $settingForm->handleRequest($request);
    if ($settingForm->isSubmitted()) {
      if ($settingForm->isValid()) {
        // 設定保存処理
        $em = $this->getDoctrine()->getManager();
        $em->persist($setting);
        $em->flush();

        // 再計算処理
        $logger->info('商品別原価率再計算！');
        $listRepo->refreshCostRateList($setting);

        // リロード対応のため、リダイレクト
        return $this->redirectToRoute('products_cost_rate_list');

      } else {
        $this->addFlash('warning', '設定値が不正なため、計算できませんでした。設定を確認してください。');
      }
    }

    $pagination = $listRepo->getListPagination($conditions, $orders, $limit, $page);

    // sort 引き継ぎ
    if ($orders) {
      // ひとまず単一ソート前提
      foreach($orders as $field => $direction) {
        $pagination->setParam('sort', $field);
        $pagination->setParam('direction', $direction);
        break;
      }
    }

    // 表示用 全商品件数取得
    /** @var TbMainproductsRepository $productRepo */
    $productRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $allProductsNum = $productRepo->getAllProductCount();

    // 商品別原価率 一覧画面表示
    return $this->render('AppBundle:Products:CostRate/list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'allProductsNum' => $allProductsNum
      , 'settingForm' => $settingForm->createView()
      , 'setting' => $setting
      , 'imageUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
    ]);
  }

  /**
   * 一時テーブル 原価率増減再計算
   */
  public function updateCostRateListAction(Request $request)
  {
    // 設定値取得
    /** @var TbProductCostRateListSettingRepository $settingRepo */
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateListSetting');
    $setting = $settingRepo->getCurrentSetting();

    // 閾値・揺さぶり対象累積値 更新
    if ($request->get('threshold') !== null) {
      $setting->setMoveThresholdRate($request->get('threshold'));
    }
    if ($request->get('shake_border') !== null) {
      $setting->setShakeBorder($request->get('shake_border'));
    }
    if ($request->get('change_amount_up') !== null) {
      $setting->setChangeAmountUp($request->get('change_amount_up'));
    }
    if ($request->get('change_amount_down') !== null) {
      $setting->setChangeAmountDown($request->get('change_amount_down'));
    }
    if ($request->get('change_amount_additional') !== null) {
      $setting->setChangeAmountAdditional($request->get('change_amount_additional'));
    }
    $em = $this->getDoctrine()->getManager('main');
    $em->flush();

    /** @var TbProductCostRateListRepository $listRepo */
    $listRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateList');
    $listRepo->updateProductCostRateList($setting);

    $this->addFlash('success', '一覧の原価率を再計算しました。');
    return $this->redirectToRoute('products_cost_rate_list');
  }

  /**
   * 一時テーブル 原価率揺さぶり
   */
  public function unsettleCostRateListAction(Request $request)
  {
    // 設定値取得
    /** @var TbProductCostRateListSettingRepository $settingRepo */
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateListSetting');
    $setting = $settingRepo->getCurrentSetting();

    // 閾値・揺さぶり対象累積値 更新
    if ($request->get('threshold') !== null) {
      $setting->setMoveThresholdRate($request->get('threshold'));
    }
    if ($request->get('shake_border') !== null) {
      $setting->setShakeBorder($request->get('shake_border'));
    }
    if ($request->get('change_amount_up') !== null) {
      $setting->setChangeAmountUp($request->get('change_amount_up'));
    }
    if ($request->get('change_amount_down') !== null) {
      $setting->setChangeAmountDown($request->get('change_amount_down'));
    }
    if ($request->get('change_amount_additional') !== null) {
      $setting->setChangeAmountAdditional($request->get('change_amount_additional'));
    }
    $em = $this->getDoctrine()->getManager('main');
    $em->flush();

    /** @var TbProductCostRateListRepository $listRepo */
    $listRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateList');
    $listRepo->unsettleProductCostRateList($setting);

    $this->addFlash('success', '一覧の原価率に揺さぶり処理を行いました。');
    return $this->redirectToRoute('products_cost_rate_list');
  }

  /**
   * 一時テーブル 原価率リセット
   * 全ての原価率を初期値（期間平均）へ更新
   */
  public function resetCostRateListAction()
  {
    /** @var TbProductCostRateListRepository $listRepo */
    $listRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateList');
    $listRepo->resetProductCostRateList();

    $this->addFlash('info', '一覧の原価率を初期値にリセットしました。');
    return $this->redirectToRoute('products_cost_rate_list');
  }

  /**
   * 商品別原価率更新処理 ※Ajax キュー追加処理
   */
  public function updateCostRateProcessAction()
  {
    $logger = $this->get('misc.util.batch_logger');

    try {
      $rescue = $this->getResque();

      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command' => 'update_product_cost_rate_process'
        , 'account' => $this->getLoginUser()->getId()
      ];

      // リトライ設定
      $retrySeconds = [];
      for ($i = 1; $i <= 12; $i++) {  // 1分刻みで3回
        $retrySeconds[] = 60;
      }

      $rescue->setJobRetryStrategy([get_class($job) => $retrySeconds]);
      $rescue->enqueue($job);

      // 結果をJSONで返す
      return new JsonResponse(['message' => '商品別原価率更新処理のキューを追加しました。']);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * 一時テーブル 原価率更新
   * ※ テキストボックス変更のたびにAjaxで更新
   */
  public function updateListCostRateAction(Request $request)
  {
    // $logger = $this->get('misc.util.batch_logger');

    $result = [
      'valid' => false
    ];

    /** @var TbProductCostRateListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCostRateList');

    /** @var TbProductCostRateList $row */
    $row = $repo->find($request->get('daihyo_syohin_code'));

    if ($row) {
      $row->setCostRateAfter(intval($request->get('cost_rate_after')));
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['cost_rate'] = $row->getCostRateAfter();
      $result['valid'] = true;
    }

    return new JsonResponse($result);
  }



  /**
   * 削除除外商品設定
   * @return Response
   */
  public function deleteExclusionAction()
  {
    // データ取得
    /** @var TbDeleteExcludedProductsRepository  $repo */
    $repo = $this->getDoctrine()->getRepository(TbDeleteExcludedProducts::class);
    /** @var TbDeleteExcludedProducts[] $data */
    $data = $repo->findBy([], ['mall_id' => 'asc', 'syohin_code' => 'asc', 'id' => 'desc']);

    // モール一覧取得
    /** @var TbShoppingMallRepository $repoMall */
    $repoMall = $this->getDoctrine()->getRepository(TbShoppingMall::class);
    $mallList = $repoMall->getMallListByIds();

    // 削除除外商品設定 画面表示
    return $this->render('AppBundle:Products:DeleteExclusion/list.html.twig', [
        'account' => $this->getLoginUser()
      , 'data' => $data
      , 'mallList' => $mallList
    ]);
  }

  /**
   * 削除除外商品 登録画面
   * @param Request $request
   * @return Response
   */
  public function deleteExclusionCreateAction(Request $request)
  {
    /** @var TbShoppingMallRepository $repoMall */
    $repoMall = $this->getDoctrine()->getRepository(TbShoppingMall::class);
    $mallList = $repoMall->getMallListByIds([
        DbCommonUtil::MALL_ID_AMAZON
      , DbCommonUtil::MALL_ID_AMAZON_COM
      , DbCommonUtil::MALL_ID_YAHOO
      , DbCommonUtil::MALL_ID_YAHOOKAWA
      , DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE
      , DbCommonUtil::MALL_ID_RAKUTEN
    ]);

    $excludedProduct = new TbDeleteExcludedProducts();
    $type = new TbDeleteExcludedProductsType();
    $type->setMallList($mallList);
    $form = $this->createForm($type, $excludedProduct);

    $form->handleRequest($request);
    if ($form->isSubmitted()) {

      if ($form->isValid()) {
        $em = $this->getDoctrine()->getManager();
        $em->persist($excludedProduct);
        $em->flush();

        $this->addFlash(
          'success',
          '削除対象外商品コードを追加しました。[' . $excludedProduct->getSyohinCode() . ']'
        );

        return $this->redirectToRoute('products_delete_exclusion');
      } else {
        $this->addFlash(
          'warning',
          '入力エラーがあります。'
        );
      }
    }

    return $this->render('AppBundle:Products:DeleteExclusion/create.html.twig', [
        'form' => $form->createView()
      , 'backUrl' => $this->generateUrl('products_delete_exclusion')
    ]);
  }

  /**
   * 削除除外商品 編集画面
   * @param Request $request
   * @return Response
   */
  public function deleteExclusionEditAction($id, Request $request)
  {
    /** @var TbDeleteExcludedProductsRepository  $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeleteExcludedProducts');
    $excludedProduct = $repo->find($id);

    if (!$excludedProduct) {
      $this->addFlash(
        'danger',
        '該当するデータが取得できませんでした。'
      );
      return $this->redirectToRoute('products_delete_exclusion');
    }

    /** @var TbShoppingMallRepository $repoMall */
    $repoMall = $this->getDoctrine()->getRepository(TbShoppingMall::class);
    $mallList = $repoMall->getMallListByIds([
        DbCommonUtil::MALL_ID_AMAZON
      , DbCommonUtil::MALL_ID_AMAZON_COM
      , DbCommonUtil::MALL_ID_YAHOO
      , DbCommonUtil::MALL_ID_YAHOOKAWA
      , DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE
      , DbCommonUtil::MALL_ID_RAKUTEN
    ]);

    $type = new TbDeleteExcludedProductsType();
    $type->setMallList($mallList);
    $form = $this->createForm($type, $excludedProduct);
    $form->handleRequest($request);
    if ($form->isSubmitted()) {
      if ($form->isValid()) {
        $em = $this->getDoctrine()->getManager();
        $em->persist($excludedProduct);
        $em->flush();

        $this->addFlash(
          'success',
          '削除対象外商品コードを更新しました。[' . $excludedProduct->getSyohinCode() . ']'
        );

        return $this->redirectToRoute('products_delete_exclusion');
      } else {
        $this->addFlash(
          'warning',
          '入力エラーです。'
        );
      }
    }

    return $this->render('AppBundle:Products:DeleteExclusion/edit.html.twig', [
        'form' => $form->createView()
      , 'excludedProduct' => $excludedProduct
      , 'backUrl' => $this->generateUrl('products_delete_exclusion')
    ]);
  }

  /**
   * 削除除外商品 削除処理
   */
  public function deleteExclusionDeleteAction($id)
  {
    /** @var TbDeleteExcludedProductsRepository  $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeleteExcludedProducts');
    $excludedProduct = $repo->find($id);

    if (!$excludedProduct) {
      $this->addFlash(
        'danger',
        '該当するデータが取得できませんでした。'
      );
      return $this->redirectToRoute('products_delete_exclusion');
    }

    $em = $this->getDoctrine()->getManager();
    $em->remove($excludedProduct);
    $em->flush();

    $this->addFlash(
      'success',
      '削除対象外商品コードを1件削除しました。'
    );

    return $this->redirectToRoute('products_delete_exclusion');
  }

  /**
   * 値下確定処理 ※Ajax キュー追加処理
   */
  public function discountProcessAction()
  {
    $logger = $this->get('misc.util.batch_logger');

    try {
      $rescue = $this->getResque();

      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
          'command' => 'discount_process'
        , 'account' => $this->getLoginUser()->getId()
      ];

      // リトライ設定
      $retrySeconds = [];
      for ($i = 1; $i <= 12; $i++) {  // 1分刻みで3回
        $retrySeconds[] = 60;
      }

      $rescue->setJobRetryStrategy([get_class($job) => $retrySeconds]);
      $rescue->enqueue($job);

      // 結果をJSONで返す
      return new JsonResponse(['message' => '値下確定処理のキューを追加しました。']);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * 値下げ許可フラグ 更新（チェックボックスの反映ボタンで一括更新するようにしたのでもう不要？）
   * ※ チェックボックス変更のたびにAjaxで更新
   * @param Request $request
   * @return JsonResponse
   */
  public function updatePricedownFlgAction(Request $request)
  {
    // $logger = $this->get('misc.util.batch_logger');

    $result = [
      'valid' => false
    ];

    /** @var TbDiscountListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDiscountList');

    /** @var TbDiscountList $row */
    $row = $repo->find($request->get('daihyo_syohin_code'));

    if ($row) {
      $row->setPricedownFlg($request->get('pricedown_flg'));
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['valid'] = true;
    }

    return new JsonResponse($result);
  }

  /**
   * 値下げ設定 一括更新
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function updatePricedownSettingsAction(Request $request)
  {
    ini_set('memory_limit', '1024M');

    $result = [
      'valid' => false
    ];

    $data = json_decode($request->getContent(), true);

    $seasonRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSeason');
    $flgRepo    = $this->getDoctrine()->getRepository('MiscBundle:TbDiscountList');

    $em = $this->getDoctrine()->getManager();

    foreach($data as $product) {
      $daihyoSyohinCode = $product['daihyo_syohin_code'];
      $discountSeasonSetting = $product['discount_season_setting'];
      $pricedownFlg = $product['pricedown_flg'];

      // 値下げ月の更新
      $seasonRepo->setProductSeasonSetting($daihyoSyohinCode, $discountSeasonSetting);

      // 値下げ許可フラグの更新
      $flgRow = $flgRepo->find($daihyoSyohinCode);
      $flgRow->setPricedownFlg($pricedownFlg);
    }

    $em->flush();

    $result['valid'] = true;
    return new JsonResponse($result);
  }

  /**
   * 値下げ対象商品一覧
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
   */
  public function discountListCopyAction(Request $request)
  {
    ini_set('memory_limit', '1024M');

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var TbDiscountListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDiscountList');

    // 設定値取得
    /** @var TbDiscountSettingRepository $settingRepo */
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbDiscountSetting');
    $setting = $settingRepo->getCurrentSettingCopy();

    $settingForm = $this->createForm(new TbDiscountSettingCopyType(), $setting);

    // 値引き一覧 再集計
    $settingForm->handleRequest($request);

    if ($settingForm->isSubmitted()) {

      if ($settingForm->isValid()) {
        // 設定保存処理
        // 値引き制限は、1, 2, 3それぞれ、2項目揃っていなければその項目は削除
        if (empty($setting->getLimitRateForCost1()) || empty($setting->getLimitWithinDays1())) {
          $setting->setLimitRateForCost1(null);
          $setting->setLimitWithinDays1(null);
        }
        if (empty($setting->getLimitRateForCost2()) || empty($setting->getLimitWithinDays2())) {
          $setting->setLimitRateForCost2(null);
          $setting->setLimitWithinDays2(null);
        }
        if (empty($setting->getLimitRateForCost3()) || empty($setting->getLimitWithinDays3())) {
          $setting->setLimitRateForCost3(null);
          $setting->setLimitWithinDays3(null);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($setting);
        $em->flush();

        // 再計算処理
        $repo->refreshDiscountListCopy();

        // リロード対応のため、リダイレクト
        return $this->redirectToRoute('products_discount_list_copy');

      } else {
        $this->addFlash('warning', '設定値が不正なため、計算できませんでした。設定を確認してください。');
      }
    }

    $discountList = $repo->getDiscountList();

    // 値下げ月設定の取得して$discountListの要素に追加
    $seasonRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSeason');
    foreach($discountList as $idx => $product) {
      $discountList[$idx] += array('discount_season_setting' =>  $seasonRepo->getProductSeasonSetting($product['daihyo_syohin_code']));
    }

    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    // 画面表示用の追加項目を付与
    foreach($discountList as $idx => $product) {
      // estimated_sales_daysが0となると、 0 除算エラーとなる。0.00の場合0.01で計算
      if ($product['estimated_sales_days'] == 0) {
        $discountList[$idx]['sales_rate'] = round(($setting->getSellOutDays() / 0.01) - 0.005, 2); // 消化日数/販売完了日数 小数点以下3位を切り捨て
      } else {
        $discountList[$idx]['sales_rate'] = round(($setting->getSellOutDays() / $product['estimated_sales_days']) - 0.005, 2); // 消化日数/販売完了日数 小数点以下3位を切り捨て
      }
    }
    return $this->render('AppBundle:Products:discount_list_copy.html.twig', array(
        'data' => json_encode($discountList)
        , 'settingForm' => $settingForm->createView()
        , 'imageUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
        , 'rakutenDetailUrl' => 'http://item.rakuten.co.jp/plusnao/'
    ));
  }

  /**
   * Amazonメイン画像 未登録商品一覧
   * @param Request $request
   * @param $page
   * @return Response
   */
  public function productListNoAmazonMainImageAction(Request $request, $page)
  {
    $perPage = 20;
    $conditions = [];
    $orders = [];

    $conditions['image_photo_need_flg'] = $request->get('image_photo_need_flg');

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $pagination = $repo->searchMissingAmazonMainImageList($conditions, $orders, $perPage, $page);
    $pagination->setPageRange(3); // ページナビ横幅

    // 画面表示
    return $this->render('AppBundle:Products:List/missing-amazon-image-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'conditions' => $conditions
      , 'searchUrlParams' => [
        'page' => 1
      ]
      , 'linkUrl' => 'http://plus-nao.com/forests/TbMainproducts/registered_mainedit/'
    ]);
  }

  /**
   * 未審査・グレー・ブラック商品一覧画面
   * @param Request $request
   * @param $page
   * @return Response
   */
  public function productListNotWhiteAction(Request $request, $page)
  {
    $perPage = 20;
    $conditions = [];
    $orders = [];

    $conditions['adult_check_status'] = $request->get('adult_check_status');
    $conditions['date_start'] = $request->get('date_start');
    $conditions['date_end'] = $request->get('date_end');
    $conditions['deliverycode'] = $request->get('deliverycode', []);

    $searchParams = $conditions;
    if ($conditions['adult_check_status']) {
      $searchParams['adult_check_status'] = [
        $conditions['adult_check_status']
      ];
    }

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $pagination = $repo->searchNotWhiteList($searchParams, $orders, $perPage, $page);
    $pagination->setPageRange(3); // ページナビ横幅

    // 画面表示
    return $this->render('AppBundle:Products:List/not-white-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'conditions' => $conditions
      , 'searchUrlParams' => [
        'page' => 1
      ]
      , 'linkUrl' => 'http://plus-nao.com/forests/TbMainproducts/registered_mainedit/'
    ]);
  }

  /**
   * 商品計測 一覧画面
   * @param integer $page
   * @return Response
   */
  public function productSizeCheckListAction($page)
  {
    $perPage = 20;
    $conditions = [];
    $orders = [];

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $pagination = $repo->searchSizeCheckList($conditions, $orders, $perPage, $page);
    $pagination->setPageRange(3); // ページナビ横幅

    // 画面表示
    return $this->render('AppBundle:Products:List/size-check-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'conditions' => $conditions
      , 'searchUrlParams' => [
        'page' => 1
      ]
      , 'linkUrl' => 'http://plus-nao.com/forests/TbMainproducts/registered_mainedit/'
    ]);
  }


  /**
   * SHOPLIST 価格一覧 （モール価格一覧）
   * @param Request $request
   * @param int $page
   */
  public function mallPriceListAction(Request $request)
  {
    // 画面表示
    return $this->render('AppBundle:Products:mall-price-list.html.twig', [
        'account' => $this->getLoginUser()
      // , 'pagination' => $pagination
      // , 'paginationInfo' => $pagination->getPaginationData()
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      // , 'conditions' => $conditions
      // , 'searchUrlParams' => [
      //  'page' => 1
      // ]
    ]);
  }

  /**
   * SHOPLIST 価格一覧 （モール価格一覧） データ取得処理
   * Ajaxアクセス
   */
  public function mallPriceGetListDataAction()
  {
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'data' => []
    ];

    try {
      /** @var VProductMallPriceRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:VProductMallPrice');
      $result['data'] = $repo->getAll();

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * SHOPLIST 価格更新CSVアップロード
   * @param Request $request
   * @return JsonResponse
   */
  public function mallPriceShoplistUploadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('shoplist price csv upload: start.');

    $result = [
      'message' => null
      , 'info' => []
    ];

    try {
      // ファイル変換、モール判定（ヘッダチェック）

      /** @var SplFileInfo[] $files */
      $files = $request->files->get('upload');
      $result = [];

      $file = array_shift($files);
      if (!$file) {
        throw new \RuntimeException('ファイルがアップロードされませんでした。');
      }

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');

      // 文字コード変換
      $tmpFile = tmpfile();
      $tmpFilePath = $fileUtil->createConvertedCharsetTempFile($tmpFile, $file->getPathname(), 'SJIS-WIN', 'UTF-8');

      /** @var ShoplistMallProcess $mallProcess */
      $mallProcess = $this->get('batch.mall_process.shoplist');

      // ヘッダチェック
      rewind($tmpFile);
      $headers = fgetcsv($tmpFile, null, ',', '"');
      if ($headers != ShoplistMallProcess::$PRODUCT_DETAIL_CSV_HEADERS) {
        throw new \RuntimeException('CSVファイルのヘッダが違います。');
      }

      // 取込処理
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      // 一時テーブル作成
      $tmpTableName = $mallProcess->createTemporaryTableProductDetailCsv($dbMain);

      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFileName
        INTO TABLE {$tmpTableName}
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':importFileName', $tmpFilePath, \PDO::PARAM_STR);
      $stmt->execute();

      $sql = <<<EOD
        UPDATE tb_shoplist_information i
        INNER JOIN tmp_work_shoplist_product_detail t ON i.daihyo_syohin_code = t.商品番号
        SET i.current_price      = t.販売価格
          , i.current_list_price = t.表示価格
EOD;
      $dbMain->query($sql);

      fclose($tmpFile);

      $result['message'] = 'SHOPLIST登録価格情報の取込を完了しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['error'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * SHOPLIST価格修正CSVダウンロード
   * @param Request $request
   * @return StreamedResponse
   * @throws \Doctrine\DBAL\DBALException
   */
  public function mallPriceShoplistDownloadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('shoplist price csv download: start.');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    // 商品コード取得・一時テーブル保存
    $codeList = $request->get('daihyo_syohin_code_list', []);
    if (!is_array($codeList) || !$codeList) {
      throw new \RuntimeException('商品コードが指定されていません。');
    }

    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_shoplist_price_download_codes");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_work_shoplist_price_download_codes (
        daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
      ) Engine=InnoDB DEFAULT CHARSET utf8
EOD;
    $dbMain->query($sql);

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tmp_work_shoplist_price_download_codes", [
      'fields' => [
        'daihyo_syohin_code' => \PDO::PARAM_STR
      ]
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $codeList, function ($code) {

      $item['daihyo_syohin_code'] = $code;

      return $item;

    }, 'foreach');


    $sql = <<<EOD
      SELECT
          'u' AS `コントロールカラム`
        , LOWER(m.daihyo_syohin_code) AS `商品管理番号（商品URL）`
        , i.baika_tanka AS `販売価格`
        , i.baika_tanka AS `表示価格`
      FROM tb_mainproducts m
      INNER JOIN tmp_work_shoplist_price_download_codes c ON m.daihyo_syohin_code = c.daihyo_syohin_code
      INNER JOIN tb_shoplist_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');

    // ヘッダ
    $headers = [
      'コントロールカラム' => 'コントロールカラム'
      , '商品管理番号（商品URL）' => '商品管理番号（商品URL）'
      , '販売価格' => '販売価格'
      , '表示価格' => '表示価格'
    ];

    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($stmt, $stringUtil, $headers) {
        $file = new \SplFileObject('php://output', 'w');
        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        $file->fwrite($header);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          $file->fwrite($line);

          flush();
        }
      }
    );

    $fileName = sprintf('shoplist_price_%s.csv', (new \DateTime())->format('YmdHis'));

    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
    $response->send();

    return $response;
  }


  /**
   * 商品ロケーション履歴
   */
  public function locationLogListAction(Request $request)
  {
    // ログインアカウント一覧取得（プルダウン表示）
    /** @var SymfonyUsersRepository $repoUser */
    $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    $users = $repoUser->getActiveAccounts();

    $conditions = [];

    if ($request->getMethod() === Request::METHOD_POST) {
      $conditions['date_start'] = $request->get('date_start');
      $conditions['date_end'] = $request->get('date_end');
      $conditions['account'] = $request->get('account');
      $conditions['ne_syohin_syohin_code'] = $request->get('ne_syohin_syohin_code');

      /** @var TbProductLocationLogRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocationLog', 'log');
      $list = $repo->getLogCsvList($conditions);


      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      // ヘッダ
      $headers = [
          'record_id'             => 'id'
        , 'created'               => '日時'
        , 'account'               => 'アカウント'
        , 'operation'             => '処理名'
        , 'type'                  => 'type'
        , 'pre_location_code'     => '前ロケーション'
        , 'location_code'         => '後ロケーション'
        , 'ne_syohin_syohin_code' => '商品コード'
        , 'comment'               => 'コメント'
      ];

      $response = new StreamedResponse();
      $response->setCallback(
        function () use ($list, $stringUtil, $headers) {
          $file = new \SplFileObject('php://output', 'w');
          $eol = "\r\n";

          // ヘッダ
          $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
          $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
          $file->fwrite($header);

          foreach($list as $log) {

            $line = $stringUtil->convertArrayToCsvLine($log, array_keys($headers), [], ",") . $eol;
            $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

            $file->fwrite($line);

            flush();
          }
        }
      );

      $fileName = sprintf('location_log_%s.csv', (new \DateTime())->format('YmdHis'));

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
      $response->send();

      return $response;
    }

    // 画面表示
    return $this->render('AppBundle:Products:List/location-log-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'users' => $users
      , 'conditions' => $conditions
    ]);
  }

  /**
   * SKU別　送料設定画面 初期表示
   */
  public function skuShippingdivisionIndexAction() {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:Products:sku-shipping-setting.html.twig', array(
        'account' => $this->getLoginUser()
        , 'shippingGroupList' => json_encode(TbShippingdivision::getShippingGroupList())
    ));
  }

  /**
   * SKU別　送料設定画面 検索・確認
   */
  public function skuShippingdivisionSearchAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = array();

    try {
      $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $neSyohinSyohinCode = $request->get('neSyohinSyohinCode');
      $skuList = $repository->findSameAxisItems($neSyohinSyohinCode); // TbProductchoiceitemsのリスト
      if ($skuList != null) {
        $product = $skuList[0]->getProduct();
        $result['item']['product'] = array(
            'daihyoSyohinCode' => $product->getDaihyoSyohinCode()
            , 'daihyoSyohinName' => $product->getDaihyoSyohinName()
        );
        if ($skuList[0]->getShippingdivision() != null) {
          $result['item']['currentShippingGroupCode'] = $skuList[0]->getShippingdivision()->getShippingGroupCode();
          $result['item']['currentShippingdivisionName'] = $skuList[0]->getShippingdivision()->getName();
        }
        foreach ($skuList as $sku) {
          $result['item']['productchoiceitemsList'][] = array(
              "neSyohinSyohinCode" => $sku->getNeSyohinSyohinCode()
          );
        }
        $result['status'] = 'ok';
      } else {
        $result['status'] = 'ok';
        $result['message'] = "指定されたNE商品コード（SKUコード）のSKU商品がありません";
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * SKU別　送料設定画面 更新実行
   */
  public function skuShippingdivisionUpdateAction(Request $request) {
    $account = $this->getLoginUser();
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'message' => null
        , 'item' => null
    ];

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');
    $em->beginTransaction();

    try {
      $neSyohinSyohinCode = $request->get('neSyohinSyohinCode');
      $shippingGroupCode = $request->get('shippingGroupCode');
      if (is_null($shippingGroupCode)) {
        throw new InvalidArgumentException('配送方法が指定されていません。');
      }

      $logRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsShippingGroupLog');
      $log = $logRepo->insertLog($neSyohinSyohinCode, $shippingGroupCode, $account->getId(), true);

      $logInfo = array('message' => '正常に設定できました。'); // ログ出力用の文字列
      // 送料設定を更新
      $pciRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $pciRepo->updateSameAxisItems($log->getDaihyoSyohinCode(),
        $log->getShippingGroupCode(), $log->getBundleAxis(), $log->getAxisCode(), $log->getTargetNeSyohinSyohinCode(), null, true, null, $logInfo);
      $em->flush();
      $em->commit();

      $groupList = TbShippingdivision::getShippingGroupList();
      $logger->info(sprintf('SKU別送料設定 手動設定：　[%s] %s user[%s]', $log->getDaihyoSyohinCode(), $groupList[$log->getShippingGroupCode()], $account->getUsername()));
      $result['message'] = $logInfo['message'];
    } catch (\Exception $e) {
      try {
        $em->rollback();
      } catch (\Exception $e2) {
        $logger->info('SKU別送料設定 手動設定 更新時のrollbackでエラー: '. $e2->getMessage() . $e2->getTraceAsString());
      }

      $logger->error('SKU別送料設定 手動設定 エラー:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * SKU別送料設定 保留一覧画面 初期表示
   */
  public function skuShippingdivisionPendingIndexAction() {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    return $this->render('AppBundle:Products:sku-shipping-pending.html.twig', array(
        'account' => $this->getLoginUser()
    ));
  }

  /**
   * SKU別　送料設定保留一覧画面 商品情報取得（Ajax）
   */
  public function skuShippingdivisionPendingFindAction() {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'items' => null
    ];

    $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsShippingdivisionPending');
    $pendings = $repository->findPendingArray();
    $result['items'] = $pendings;
    return new JsonResponse($result);
  }

  /**
   * SKU別送料設定 保留一覧画面 更新実行
   */
  public function skuShippingdivisionPendingUpdateAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();
    $em = $this->getDoctrine()->getManager();
    $result = [
        'status' => 'ok'
        , 'message' => '更新を実行しました'
        , 'item' => null
    ];

    try {
      // 送信データ取得
      $data = json_decode($request->getContent(), true);
      $pendingRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsShippingdivisionPending');
      $pciRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $ids = array_column($data, 'id');
      $reflectStatuses = array_column($data, 'reflectStatus', 'id'); // idをキー、反映ステータスを値とした連想配列
      $pendings = $pendingRepository->findById($ids);
      foreach ($pendings as $pending) {
        // DB上が保留で、画面からの送信値が保留以外であれば更新処理
        if ($pending->getReflectStatus() == TbProductchoiceitemsShippingdivisionPending::REFLECT_STATUS_PENDING
            && $reflectStatuses[$pending->getId()] != TbProductchoiceitemsShippingdivisionPending::REFLECT_STATUS_PENDING) {
          $pending->setReflectStatus($reflectStatuses[$pending->getId()]); // 保留データのステータス更新
          $pending->setUpdSymfonyUsersId($account->getId());
          if ($reflectStatuses[$pending->getId()] == TbProductchoiceitemsShippingdivisionPending::REFLECT_STATUS_REFLECTED) { // 更新実行の場合
            // 更新対象のSKUを取得
            $skuList = null;
            if ($pending->getBundleAxis()) {
              $skuList = $pciRepository->findByDaihyoSyohinCodeAndAxis($pending->getDaihyoSyohinCode(), $pending->getBundleAxis(), $pending->getAxisCode());
            } else if ($pending->getTargetNeSyohinSyohinCode()) {
              $skuList[] = $pciRepository->find($pending->getTargetNeSyohinSyohinCode());
            } else {
              $skuList = $pciRepository->findByDaihyoSyohinCode($pending->getDaihyoSyohinCode());
            }
            foreach ($skuList as $sku) {
              $sku->setShippingdivisionId($pending->getPendingShippingdivisionId());
            }
          }
        }
      }
      $em->flush();
    } catch (\Exceptoin $e) {
      $logger->error('SKU別送料設定　保留データ更新でエラーが発生しました。' . $e->getMessage() . ' ' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品ディレクトリ集計画面
   */
  public function directoryCountAction(Request $request) {
    ini_set('memory_limit', '512M');
    $account = $this->getLoginUser();

    /** @var TbShippingVoucherRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbPlusnaoproductdirectory');

    $fields = $repo->getFieldList();

    // 取得条件
    $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
    if (is_null($request->get('import_date_from'))) {
      $importDateFrom = $today->setTime(0, 0, 0);
    } else if (strlen($request->get('import_date_from'))) {
      $importDateFrom = (new \DateTimeImmutable($request->get('import_date_from')))->setTime(0, 0, 0);
    } else {
      $importDateFrom = null;
    }

    if (is_null($request->get('import_date_to'))) {
      $importDateTo = $today;
    } else if (strlen($request->get('import_date_to'))) {
      $importDateTo = (new \DateTimeImmutable($request->get('import_date_to')))->setTime(0, 0, 0);
    } else {
      $importDateTo = null;
    }

    $field1 = null;
    if (!is_null($request->get('field1')) && strlen($request->get('field1'))) {
      $field1 = $request->get('field1');
    }

    $field2 = null;
    if (!is_null($request->get('field2')) && strlen($request->get('field2'))) {
      $field2 = $request->get('field2');
    }

    $field3 = null;
    if (!is_null($request->get('field3')) && strlen($request->get('field3'))) {
      $field3 = $request->get('field3');
    }

    $field4 = null;
    if (!is_null($request->get('field4')) && strlen($request->get('field4'))) {
      $field4 = $request->get('field4');
    }

    $field5 = null;
    if (!is_null($request->get('field5')) && strlen($request->get('field5'))) {
      $field5 = $request->get('field5');
    }

    $days = 7;
    if (!is_null($request->get('days')) && strlen($request->get('days'))) {
      $days = $request->get('days');
    }

    $conditions = [
      'dateFrom' => $importDateFrom
      , 'dateTo' => $importDateTo
      , 'field1' => $field1
      , 'field2' => $field2
      , 'field3' => $field3
      , 'field4' => $field4
      , 'field5' => $field5
      , 'days' => $days
    ];

    $result = $repo->findDirectoryCount($conditions);

    $data = $result['data'];
    $total = $result['total'];

    return $this->render('AppBundle:Products:directory-count.html.twig', array(
        'account' => $this->getLoginUser()
        //, 'shippingGroupList' => json_encode(TbShippingdivision::getShippingGroupList())
        , 'dataJson' => json_encode($data)
        , 'fields' => json_encode($fields)
        , 'conditions' => $conditions
        , 'total' => $total
    ));
  }

    /**
   * 注残ステータス設定日付 一覧 作業者別集計取得
   * @param Request $request
   * @return Response
   */
  public function directoryChildrenCountAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      /** @var TbShippingVoucherRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbPlusnaoproductdirectory');

      // 取得条件
      $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
      if (is_null($request->get('import_date_from'))) {
        $importDateFrom = $today->setTime(0, 0, 0);
      } else if (strlen($request->get('import_date_from'))) {
        $importDateFrom = (new \DateTimeImmutable($request->get('import_date_from')))->setTime(0, 0, 0);
      } else {
        $importDateFrom = null;
      }

      if (is_null($request->get('import_date_to'))) {
        $importDateTo = $today;
      } else if (strlen($request->get('import_date_to'))) {
        $importDateTo = (new \DateTimeImmutable($request->get('import_date_to')))->setTime(0, 0, 0);
      } else {
        $importDateTo = null;
      }

      $field1 = null;
      if (!is_null($request->get('field1')) && strlen($request->get('field1'))) {
        $field1 = $request->get('field1');
      }

      $conditions = [
        'dateFrom' => $importDateFrom
        , 'dateTo' => $importDateTo
        , 'field1' => $field1
      ];

      $result['list'] = $repo->findDirectoryCount($conditions);
      $logger->dump($result['list']);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 指定サイズで画像のサムネイルを作成し、バイナリを返す。
   * 作成した画像は、~/product_images_thumbnail/ に保存する。
   * @param Request $request
   * @param string $dir
   * @param string $resizeFile
   * @return Response
   */
  public function viewThumbnailAction($dir, $resizeFile)
  {
    // $resizeFileが「zak-12345_6_70_80.jpg」のような形式であることも考慮し、後ろから順に取得
    $explodeResizeFile = explode('_', $resizeFile);
    $maxHeight = array_pop($explodeResizeFile);
    $maxWidth = array_pop($explodeResizeFile);
    $file = implode("_", $explodeResizeFile);

    if ($maxWidth === '0' || $maxHeight === '0') {
      header('HTTP/1.1 400 Bad Request');
      exit;
    }

    $imageDir = $this->getParameter('product_image_dir');
    $basePath = $imageDir . '/' . $dir . '/' . $file . '.jpg';

    $fs = new FileSystem();
    // 変換＆書き出し（コピー処理を兼ねる）
    if (!$fs->exists($basePath)) {
      header('HTTP/1.1 404 Not Found');
      exit;
    }

    $thumbnailDir = $this->getParameter('product_image_thumbnail_dir');
    $convertedPath = $thumbnailDir . '/' . $dir . '/' . $file . '_' . $maxWidth . '_' . $maxHeight . '.jpg';

    if (!$fs->exists($convertedPath)) {
      // Exif情報削除
      $im = new \Imagick($basePath);
      $im->stripImage();

      // リサイズ処理
      $width = $im->getImageWidth();
      $height = $im->getImageHeight();
      $maxHeight = (int)$maxHeight;
      $maxWidth = (int)$maxWidth;
      if ($height > $maxHeight || $width > $maxWidth) {
        $im->resizeImage($maxWidth, $maxHeight, \Imagick::FILTER_POINT, 0, true);
      }

      // 変換＆書き出し（コピー処理を兼ねる）
      if (!$fs->exists($thumbnailDir . '/' . $dir)) {
        $fs->mkdir($thumbnailDir . '/' . $dir);
      }
      $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
      $im->setImageCompressionQuality(40);
      $im->writeImage($convertedPath);
      $im->destroy();
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $response = new Response();
    $response->headers->set('Content-type', $finfo->file($convertedPath));
    $response->sendHeaders();
    $response->setContent(readfile($convertedPath));
    return $response;
  }
}