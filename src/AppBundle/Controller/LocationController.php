<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\Repository\ProductImagesVariationRepository;
use MiscBundle\Entity\Repository\TbBoxCodeRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbLocationWarehouseToListRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbProductCodeRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Entity\Repository\TbProductLocationRepository;
use MiscBundle\Entity\Repository\TbProductReviewsRepository;
use MiscBundle\Entity\Repository\TbRfidReadingsRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbLocation;
use MiscBundle\Entity\TbLocationWarehouseToList;
use MiscBundle\Entity\TbLocationWarehouseToPickingList;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductLocation;
use MiscBundle\Entity\TbProductLocationLogComment;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Extend\Diff\ContextDiff;
use MiscBundle\Extend\Diff\DiffRendererProductLocationLog;
use MiscBundle\Form\TbMainproductsDeliveryInfoType;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StopWatchUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ロケーション管理画面 （スマホ対応）
 * @package AppBundle\Controller
 */
class LocationController extends BaseController
{
  /**
   * トップページ
   * @return Response
   */
  public function indexAction()
  {
    // 画面表示
    return $this->render('AppBundle:Location:index.html.twig', [
      'account' => $this->getLoginUser()
    ]);
  }

  /**
   * 選択倉庫切り替え処理
   * @param Request $request
   * @return JsonResponse
   */
  public function changeCurrentWarehouseAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      $account = $this->getLoginUser();
      if (!$account) {
        throw new \RuntimeException('予期せぬエラーです。（ログインアカウント取得エラー）');
      }

      $changeTo = $request->get('change_to');
      if (!$changeTo) {
        throw new \RuntimeException('切り替え先の倉庫が選択されていません。');
      }

      if ($account->getWarehouseId() == $changeTo) {
        throw new \RuntimeException('現在選択されいている倉庫です。');
      }

      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      $changeToWarehouse = $repo->find($changeTo);
      if (!$changeToWarehouse) {
        throw new \RuntimeException('切り替え先の倉庫が存在しません。');
      }

      $account->setWarehouseId($changeToWarehouse->getId());

      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['message'] = sprintf('倉庫を「%s」へ切り替えました。', $changeToWarehouse->getName());

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 会社一覧取得（Ajax）
   * @return JsonResponse
   */
  public function getCompanyListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'companys' => []
    ];

    try {
      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');
      $result['companys'] = $repo->getPullDownAll();

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 選択会社切り替え処理
   * @param Request $request
   * @return JsonResponse
   */
  public function changeCurrentCompanyAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      $account = $this->getLoginUser();
      if (!$account) {
        throw new \RuntimeException('予期せぬエラーです。（ログインアカウント取得エラー）');
      }

      $changeTo = $request->get('change_to');
      if (!$changeTo) {
        throw new \RuntimeException('切り替え先の会社が選択されていません。');
      }

      if ($account->getCompanyId() == $changeTo) {
        throw new \RuntimeException('現在選択されいている会社です。');
      }

      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');
      $changeToCompany = $repo->find($changeTo);
      if (!$changeToCompany) {
        throw new \RuntimeException('切り替え先の会社が存在しません。');
      }

      $account->setCompanyId($changeToCompany->getId());

      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['message'] = sprintf('会社を「%s」へ切り替えました。', $changeToCompany->getName());

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫へ
   */
  public function warehouseToAction(Request $request, $page)
  {
    $perPage = 100;
    $conditions = [];
    $orders = [];

    $conditions['syohin_code'] = $request->get('syohin_code');

    /** @var TbLocationWarehouseToListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToList');
    $pagination = $repo->getWarehouseToList($conditions, $orders, $perPage, $page);
    $pagination->setPageRange(3); // ページナビ横幅

    $lastUpdated = $repo->getLastUpdated();

    // 画面表示
    return $this->render('AppBundle:Location:warehouse-to.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'conditions' => $conditions
      , 'lastUpdated' => $lastUpdated
      , 'stockMonth' => TbLocationWarehouseToListRepository::STOCK_MONTHS
    ]);
  }

  /**
   * 倉庫へ ピッキングリスト
   */
  public function warehouseToPickingListAction()
  {
    /** @var TbLocationWarehouseToListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToList');
    $lastUpdated = $repo->getLastUpdated();

    // 画面表示
    return $this->render('AppBundle:Location:warehouse-to-picking-list.html.twig', [
      'account' => $this->getLoginUser()
      , 'lastUpdated' => $lastUpdated
    ]);
  }

  /**
   * 倉庫へ ピッキングリストデータ取得（Ajax）
   */
  public function warehouseToPickingListDataAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'conditions' => []
      , 'list' => []
    ];

    try {

      /** @var TbLocationWarehouseToListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToList');
      $data = $repo->getWarehouseToPickingList();

      $logger->info(sprintf('warehouse to picking : データ取得完了 %d件', count($data)));

      $imageParentUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

      $result['list'] = [];
      foreach ($data as $picking) {
        $row = $picking;
        $row['image_url'] = TbMainproductsRepository::createImageUrl($picking['pict_directory'], $picking['pict_filename'], $imageParentUrl);
        $result['list'][] = $row;
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫へ ピッキングリスト OK / PASS 処理
   * @param Request $request
   * @return JsonResponse
   */
  public function warehouseToPickingListSubmitAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'picking_status' => null
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $button = $request->get('button');
      $syohinCode = $request->get('syohin_code');
      $locationCode = $request->get('src_location');
      $newLocationCode = $request->get('dst_location');
      $moveNum = $request->get('move_num', 0);

      $logger->info(sprintf('倉庫へ ピッキングリスト: %s : %s => %s : OK/PASS: %s', $syohinCode, $locationCode, $newLocationCode, $button));

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      /** @var TbProductLocationRepository $repoProductLocation */
      $repoProductLocation = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');
      /** @var BaseRepository $repoProductLocation */
      $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToPickingList');

      /** @var TbLocation $location */
      $location = $repo->getByLocationCode($currentWarehouse->getId(), $locationCode);
      if (!$location) {
        throw new \RuntimeException('移動元のロケーションデータが見つかりません。');
      }

      // ピッキング情報取得
      /** @var TbLocationWarehouseToPickingList $picking */
      $picking = $repoPicking->findOneBy([
        'ne_syohin_syohin_code' => $syohinCode
        , 'location_id' => $location->getId()
      ]);
      if (!$picking) {
        throw new \RuntimeException('ピッキング情報が取得できませんでした。');
      }

      // OK or PASS
      switch ($button) {
        case 'ok':
          $status = TbLocationWarehouseToListRepository::PICKING_STATUS_OK;
          break;
        case 'pass':
          $status = TbLocationWarehouseToListRepository::PICKING_STATUS_PASS;
          break;
        default:
          $status = TbLocationWarehouseToListRepository::PICKING_STATUS_NONE;
          break;
      }
      $picking->setStatus($status);
      $result['picking_status'] = $status;

      // PASS の場合はステータスの変更のみ。
      if ($status !== TbLocationWarehouseToListRepository::PICKING_STATUS_OK) {
        $em->flush();
        return new JsonResponse($result);
      }

      // ロケーションコード変換
      $newLocationCode = $this->fixLocationCode($newLocationCode);
      // ロケーションコード
      if (!$this->isValidLocationCode($newLocationCode)) {
        throw new \RuntimeException('ロケーションコードが正しくありません');
      }

      // 同一ロケーションへの移動処理禁止実装
      if ($locationCode == $newLocationCode) {
        throw new \RuntimeException('移動元と移動先が同じロケーションです。');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // 移動先ロケーション取得or作成
      /** @var TbLocation $location */
      $newLocation = $repo->getByLocationCode($currentWarehouse->getId(), $newLocationCode);
      if (!$newLocation) {
        $newLocation = $repo->createNewLocation($currentWarehouse->getId(), $newLocationCode);
        if (!$newLocation) {
          throw new \RuntimeException('ロケーションの新規作成に失敗しました。[' . $newLocationCode . ']');
        }
      }

      // 移動元データ
      /** @var TbProductLocation $productLocation */
      $productLocation = $repoProductLocation->findOneBy([
        'ne_syohin_syohin_code' => $syohinCode
        , 'location_id' => $location->getId()
      ]);
      if (!$productLocation) {
        throw new \RuntimeException('移動元の商品ロケーションデータが見つかりません。');
      }
      $choiceItem = $productLocation->getChoiceItem();
      if (!$choiceItem) {
        throw new \RuntimeException('移動元の商品ロケーションデータが不正です。処理を中止します。');
      }

      // 移動先データ
      $newProductLocation = null;
      foreach ($choiceItem->getActiveLocations() as $row) {
        if ($row->getLocation()->getLocationCode() == $newLocationCode) {
          $newProductLocation = $row;
          break;
        }
      }
      if (!$newProductLocation) {
        $newProductLocation = new TbProductLocation();
        $newProductLocation->setNeSyohinSyohinCode($choiceItem->getNeSyohinSyohinCode());
        $newProductLocation->setLocationId($newLocation->getId());
        $newProductLocation->setPosition($choiceItem->getMaxLocationPosition($currentWarehouse) + 1); // 最も大きくなるように暫定値

        $newProductLocation->setChoiceItem($choiceItem);
        $choiceItem->addLocation($newProductLocation);
        $newProductLocation->setLocation($newLocation);

        $em->persist($newProductLocation);

        $logger->info('new location: ' . $newProductLocation->getNeSyohinSyohinCode());
      }

      // 在庫数移動
      if ($productLocation->getStock() < $moveNum) {
        // 移動数より在庫数が少なければエラーとする
        throw new \RuntimeException(sprintf('在庫数が移動数より少なくなっています。(現在庫: %s)。処理を中止します。', $productLocation->getStock()));
      }
      $productLocation->setStock($productLocation->getStock() - $moveNum);
      $newProductLocation->setStock($newProductLocation->getStock() + $moveNum);
      // 移動元の削除
      if ($productLocation->getStock() <= 0) {
        $choiceItem->removeLocation($productLocation);
        $em->remove($productLocation);
      }

      $em->flush();

      // ロケーション優先順位振り直し
      $repo->renumberPositionsByChoiceItem($choiceItem);

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WAREHOUSE_TO_PICKING, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '商品のロケーションを変更しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫へ 再集計
   */
  public function warehouseToRecalculateAction(Request $request)
  {
    $cleanUpFlag = boolval($request->get('cleanUpFlag', 0));

    /** @var TbLocationWarehouseToListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToList');
    $repo->refreshWarehouseToList($cleanUpFlag);

    // 「倉庫へ」ピッキングリストの再作成
    $repo->refreshWarehouseToPickingList();

    return $this->redirectToRoute('location_warehouse_to');
  }


  /**
   * 商品一覧
   * @param Request $request
   * @param string $keyword
   * @return Response
   */
  public function productListAction(Request $request, $keyword)
  {
    $searchNoStockProduct = $request->get('no_stock', '0');
    $searchLikeMode = $request->get('like_mode', 'forward');

    $data = [];

    $keyword = trim($keyword);
    if ($keyword) {
      $conditions = [];
      $conditions['keyword'] = $keyword;
      $conditions['include_no_stock_product'] = boolval($searchNoStockProduct);

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $data = $repo->searchByDaihyoSyohinCode($conditions, $searchLikeMode);

      if ($data) {
        // 画像URL取得
        $imageUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
        foreach ($data as $i => $row) {
          $row['image'] = $repo->createImageUrl($row['image_p1_directory'], $row['image_p1_filename'], $imageUrl);
          $data[$i] = $row;
        }

      // ヒットしなかった場合、商品コード OR バーコード直接検索もやってみる。
      } else {
        /** @var TbProductchoiceitemsRepository $repoChoice */
        $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

        if (preg_match('/^\d{13}$/', $keyword)) {
          /** @var TbProductchoiceitems $choice */
          $choice = $repoChoice->findByProductCode($keyword);
        } else {
          /** @var TbProductchoiceitems $choice */
          $choice = $repoChoice->find($keyword);
        }

        if ($choice) {
          return $this->redirectToRoute('location_product_detail', ['syohinCode' => $choice->getNeSyohinSyohinCode()]);
        }
      }

    }

    // 画面表示
    return $this->render('AppBundle:Location:product-list.html.twig', [
      'account' => $this->getLoginUser()
      , 'keyword' => $keyword
      , 'searchNoStockProduct' => $searchNoStockProduct
      , 'searchLikeMode' => $searchLikeMode
      , 'data' => $data
    ]);
  }

  /**
   * 商品SKU一覧
   * @param string $daihyoSyohinCode
   * @return Response
   */
  public function productSkuListAction($daihyoSyohinCode)
  {
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    $imageUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
    $product = null;

    if ($daihyoSyohinCode) {
      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $product = $repo->find($daihyoSyohinCode);
    }

    // 送料設定 更新フォーム
    $deliveryInfoForm = $this->createForm(new TbMainproductsDeliveryInfoType(), $product);

    // 画面表示
    return $this->render('AppBundle:Location:product-sku-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'daihyoSyohinCode' => $daihyoSyohinCode
      , 'imageUrl' => $imageUrl
      , 'product' => $product
      , 'currentWarehouse' => $currentWarehouse
      , 'deliveryInfoForm' => $deliveryInfoForm->createView()
    ]);
  }


  /**
   * 古市倉庫に移動するロケーション一覧
   * @param Request $request
   * @param int $page
   * @param string $keyword
   * @return Response
   */
  public function FuruichiWarehouseMoveListAction(Request $request, $page, $keyword)
  {
    $warehouseId = $request->get('warehouse_id'); // 現在は上書きして未使用。DEBUG用
    $likeMode = $request->get('mode', 'full');
    $stockMin = $request->get('stock_min', '');
    $stockMax = $request->get('stock_max', '');
    $limit = $request->get('limit', 50);

    $currentWarehouseId = $this->getLoginUser()->getWarehouseId();

    // 全倉庫
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDown();
    $furuichi_warehouses = $repoWarehouse->getFuruichiWarehouse();
    $furuichi_warehouse_id = null;
    foreach ($furuichi_warehouses as $furuichi_warehouse) {
        $furuichi_warehouse_id = $furuichi_warehouse->getId();
    }

    // 倉庫初期値。
    // 現在は固定。また、テンプレートのフォームでも現在は一旦固定。
    $warehouseId = $currentWarehouseId;

    $conditions = [
        'warehouse_id' => $warehouseId
      , 'location_code' => $keyword
      , 'stock_min' => $stockMin
      , 'stock_max' => $stockMax
      , 'move_furuichi_warehouse_flg' => 1
    ];

    $searchParams = [
        'warehouseId' => $warehouseId
      , 'keyword' => $keyword
      , 'mode' => $likeMode
      , 'stockMin' => $stockMin
      , 'stockMax' => $stockMax
      , 'limit' => $limit
    ];

    $sortParams = [
        'stock' => ''
      , 'warehouse' => ''
      , 'locationCode' => ''
    ];

    $orders = [];
    $sortKey = $request->get('o');
    $sortDirection = $request->get('od');

    if ($sortKey) {
      if ($sortDirection) {
        $orders = [
          $sortKey => $sortDirection
        ];
      }

      if (isset($sortParams[$sortKey])) {
        $sortParams[$sortKey] = $sortDirection;
      }
    }


    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

    $pagination = $repo->searchLocationMoveListByLocationCode($conditions, $orders, $likeMode, $page, $limit);
    $conditions = [
        'warehouse_id' => $warehouseId
        , 'location_code' => $keyword
        , 'stock_min' => $stockMin
        , 'stock_max' => $stockMax
        , 'move_furuichi_warehouse_flg' => 0
    ];
    $paginationNoMove = $repo->searchLocationMoveListByLocationCode($conditions, $orders, $likeMode, $page, $limit);

    // 画面表示
    return $this->render('AppBundle:Location:location-furuichi-warehouse-move-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'keyword' => $keyword
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'sortParamsJson' => json_encode($sortParams)
      , 'searchParamsJson' => json_encode($searchParams)
      , 'warehousesJson' => json_encode($warehouses)
      , 'paginationNoMove' => $paginationNoMove
      , 'paginationNoMoveInfo' => $paginationNoMove->getPaginationData()
      , 'locationsJson' => json_encode($pagination->getItems())
      , 'locationsNoMoveJson' => json_encode($paginationNoMove->getItems())
      , 'currentWarehouseId' => $currentWarehouseId
      , 'furuichiWarehouseId' => $furuichi_warehouse_id
    ]);
  }

    /**
     * ロケーション一覧
     * @param Request $request
     * @param int $page
     * @param string $keyword
     * @return Response
     */
    public function locationListAction(Request $request, $page, $keyword)
    {
        $warehouseId = $request->get('warehouse_id'); // 現在は上書きして未使用。DEBUG用
        $likeMode = $request->get('mode', 'full');
        $stockMin = $request->get('stock_min', '');
        $stockMax = $request->get('stock_max', '');

        $limit = $request->get('limit', 50);

        $currentWarehouseId = $this->getLoginUser()->getWarehouseId();

        // 全倉庫
        /** @var TbWarehouseRepository $repoWarehouse */
        $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
        $warehouses = $repoWarehouse->getPullDown();

        // 倉庫初期値。
        // 現在は固定。また、テンプレートのフォームでも現在は一旦固定。
        // if (!$warehouseId) {
        $warehouseId = $currentWarehouseId;
        // }

        $conditions = [
            'warehouse_id' => $warehouseId
            , 'location_code' => $keyword
            , 'stock_min' => $stockMin
            , 'stock_max' => $stockMax
        ];

        $searchParams = [
            'warehouseId' => $warehouseId
            , 'keyword' => $keyword
            , 'mode' => $likeMode
            , 'stockMin' => $stockMin
            , 'stockMax' => $stockMax
            , 'limit' => $limit
        ];

        $sortParams = [
            'stock' => ''
            , 'warehouse' => ''
            , 'locationCode' => ''
        ];

        $orders = [];
        $sortKey = $request->get('o');
        $sortDirection = $request->get('od');

        if ($sortKey) {
            if ($sortDirection) {
                $orders = [
                    $sortKey => $sortDirection
                ];
            }

            if (isset($sortParams[$sortKey])) {
                $sortParams[$sortKey] = $sortDirection;
            }
        }


        /** @var TbLocationRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

        $pagination = $repo->searchLocationListByLocationCode($conditions, $orders, $likeMode, $page, $limit);

        // 画面表示
        return $this->render('AppBundle:Location:location-list.html.twig', [
            'account' => $this->getLoginUser()
            , 'keyword' => $keyword
            , 'pagination' => $pagination
            , 'paginationInfo' => $pagination->getPaginationData()
            , 'sortParamsJson' => json_encode($sortParams)
            , 'searchParamsJson' => json_encode($searchParams)
            , 'warehousesJson' => json_encode($warehouses)
            , 'locationsJson' => json_encode($pagination->getItems())
            , 'currentWarehouseId' => $currentWarehouseId
        ]);
    }

  /**
   * 商品ロケーション 詳細・編集画面
   * @param Request $request
   * @param string $syohinCode 商品コード
   * @return Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function productDetailAction(Request $request, $syohinCode)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    $data = [];
    $otherWarehouseData = [
        'stockTotal' => 0
      , 'otherStockTotal' => 0
      , 'locations' => []
    ];

    /** @var BaseRepository $repo */
    $choiceRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

    /** @var TbProductchoiceitems $choiceItem */
    $choiceItem = $choiceRepo->find($syohinCode);

    if (!$choiceItem) {
      $this->setFlash('danger', '商品が見つかりませんでした。');
    } else {
      $data = $this->getProductDetailData($choiceItem, $currentWarehouse);

      // 別倉庫在庫取得
      $otherWarehouseData['stockTotal'] = $choiceItem->getStock();
      $otherWarehouseData['locations'] = $choiceItem->getOtherWarehouseLocations($currentWarehouse);
      foreach($otherWarehouseData['locations'] as $location) {
        $otherWarehouseData['otherStockTotal'] += $location->getStock();
      }
    }

    // 商品ロケーション履歴 取得処理
    /** @var TbProductLocationLogRepository $repoLog */
    $repoLog = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocationLog', 'log');
    $histories = $repoLog->getLocationRecordLogsBySyohinCode($syohinCode, 30);

    // 倉庫情報取得
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDownObjects();

    // diff版履歴
    if ($request->get('history')) {
      $oldHistories = [];
      $logs = $repoLog->getBySyohinCode($syohinCode);
      foreach($logs as $log) {

        $preArray = $repoLog->convertInfoToArray($log->getPreInfo());
        $postArray = $repoLog->convertInfoToArray($log->getPostInfo());

        foreach($preArray as $i => $row) {
          $preArray[$i] = sprintf("%s : %d", $row['location_code'], $row['stock']);
        }
        foreach($postArray as $i => $row) {
          $postArray[$i] = sprintf("%s : %d", $row['location_code'], $row['stock']);
        }

        $diff = new ContextDiff($preArray, $postArray, ['context' => 20]);
        $renderer = new DiffRendererProductLocationLog();

        $oldHistories[] = [
            'log' => $log
          , 'pre' => $preArray
          , 'post' => $postArray
          , 'html' => $diff->render($renderer)
        ];
      }
    }

    // 画面表示
    return $this->render('AppBundle:Location:product-detail.html.twig', [
        'account' => $this->getLoginUser()
      , 'data' => $data
      , 'currentWarehouseId' => $currentWarehouse->getId()
      , 'otherWarehouseData' => $otherWarehouseData
      , 'jsonData' => json_encode($data)
      , 'histories' => $histories
      , 'warehouses' => $warehouses
      , 'oldHistories' => isset($oldHistories) ? $oldHistories : null
    ]);
  }

  /**
   * 商品ロケーション詳細 更新確定処理
   * @param Request $request
   * @param string $syohinCode
   * @return JsonResponse
   */
  public function productDetailUpdateAction(Request $request, $syohinCode)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var BaseRepository $choiceRepo */
      $choiceRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems $choiceItem */
      $choiceItem = $choiceRepo->find($syohinCode);

      if (!$choiceItem) {
        throw new \RuntimeException('商品が見つかりませんでした。');
      }

      $productLocations = $request->get('locations');
      if (!$productLocations || !is_array($productLocations)) {
        $productLocations = [];
      }
      $logger->info('POST : ' . print_r($productLocations, true));

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 対象データ 他処理からの更新済みチェック
      $currentData = $this->getProductDetailData($choiceItem, $currentWarehouse);
      $logger->info($request->get('data_hash'));
      $logger->info($currentData['dataHash']);
      if ($request->get('data_hash') !== $currentData['dataHash']) {
        throw new \RuntimeException('データが他処理により変更されているため更新できません。再読み込み後、再度データを編集してください。');
      }

      // 諸々チェック ＆ ロケーションコードの半角英数大文字化（チェック前に）
      foreach ($productLocations as $i => $productLocation) {
        // ロケーションコード変換
        $productLocation['locationCode'] = $this->fixLocationCode($productLocation['locationCode']);

        // 在庫数変換
        $productLocation['stock'] = trim(mb_convert_kana($productLocation['stock'], 'as'));
        // データを修正したもので上書き
        $productLocations[$i] = $productLocation;

        // ロケーションコード
        if (!$this->isValidLocationCode($productLocation['locationCode'])) {
          throw new \RuntimeException('ロケーションコードが正しくありません');
        }

        // 在庫数
        if (!preg_match('/^\d+$/', $productLocation['stock'])) {
          throw new \RuntimeException('在庫は数値で入力してください。');
        }
      }

      // 現在の在庫数
      $beforeStockNum = $choiceItem->getStock();
      $prevLocations = [];
      foreach ($choiceItem->getActiveLocations() as $prev) {
        $prevLocations[$prev->getLocation()->getLocationCode()] = $prev->getStock();
      }

      // 更新処理
      $updateList = [];
      usort($productLocations, function ($a, $b) {
        if ($a['position'] == $b['position']) {
          return 0;
        }
        return ($a['position'] < $b['position']) ? -1 : 1;
      });

      $locationCodes = [];
      $stockTotal = 0;
      $position = 0;
      foreach ($productLocations as $productLocation) {
        // もし選択倉庫以外のロケーションが混じっていればエラーとする。※念のための実装
        if ($productLocation['warehouseId'] != $currentWarehouse->getId()) {
          throw new \RuntimeException('別倉庫のロケーションが混ざっています。処理を中止しました。 [' . print_r($productLocation, true) . ']');
        }

        // 在庫が無いものは削除
        if ($productLocation['stock'] <= 0) {
          continue;
        }
        // 過去分はスキップ
        if ($productLocation['position'] < 0) {
          continue;
        }

        $stockTotal += $productLocation['stock'];
        if (isset($locationCodes[$productLocation['locationCode']])) {
          throw new \RuntimeException('ロケーションコードが重複しています。[' . $productLocation['locationCode'] . ']');
        }
        $locationCodes[$productLocation['locationCode']] = true;
        $productLocation['position'] = $position++; // position振り直し

        $updateList[] = $productLocation;
      }

      // 更新処理
      /** @var StopWatchUtil $watch */
      $watch = $this->get('misc.util.stop_watch');
      $watch->start();

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $dbMain->beginTransaction();

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      //  ロケーションID取得（＆新規作成）
      $locationIds = $repoLocation->findIdsByLocationCodes($currentWarehouse->getId(), array_keys($locationCodes));
      foreach ($locationCodes as $code => $x) {
        if (isset($locationIds[$code])) {
          $locationId = $locationIds[$code];
        } else {
          $location = $repoLocation->createNewLocation($currentWarehouse->getId(), $code);
          if (!$location) {
            throw new \RuntimeException('ロケーションの新規作成に失敗しました。[' . $code . ']');
          }
          $locationId = $location->getId();
        }

        $locationCodes[$code] = $locationId;
      }

      // 同一倉庫のロケーションを全て削除（-5までの過去ロケーションは廃止）
      $sql = <<<EOD
        DELETE pl
        FROM tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
        WHERE l.warehouse_id = :warehouseId
          AND ne_syohin_syohin_code = :syohinCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':warehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':syohinCode', $syohinCode, \PDO::PARAM_STR);
      $stmt->execute();

      if ($locationCodes) {
        // 登録
        $sql = <<<EOD
        INSERT INTO tb_product_location (
            ne_syohin_syohin_code
          , location_id
          , stock
          , `position`
        ) VALUES (
            :newSyohinSyohinCode
          , :locationId
          , :stock
          , :position
        )
EOD;
        $stmt = $dbMain->prepare($sql);
        foreach ($updateList as $productLocation) {
          $stmt->bindValue(':newSyohinSyohinCode', $syohinCode);
          $stmt->bindValue(':locationId', $locationCodes[$productLocation['locationCode']]);
          $stmt->bindValue(':stock', $productLocation['stock']);
          $stmt->bindValue(':position', $productLocation['position']);

          $stmt->execute();
        }

        $repoLocation->renumberPositionsByChoiceItem($choiceItem);
      }

      // ロケーション変更履歴 保存
      $account = $this->getLoginUser();
      $created = new \DateTime();

      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_PRODUCT_DETAIL, $account->getUsername(), $actionKey);

      // 在庫数更新処理
      // → トリガ実装により削除

      $dbMain->commit();
      /* ---------------- */
      $logger->info(sprintf('total: トランザクション => ' . $watch->now()));
      $logger->info(print_r($updateList, true));

      // 修正コメントがあれば保存（現状、在庫変動時のみ）
      $comment = trim($request->get('comment', ''));
      if (strlen($comment)) {
        $emLog = $this->getDoctrine()->getManager('log');
        $logComment = new TbProductLocationLogComment();
        $logComment->setAccount($account->getUsername());
        $logComment->setOperation(TbLocationRepository::LOG_OPERATION_WEB_PRODUCT_DETAIL);
        $logComment->setActionKey($actionKey);
        $logComment->setComment($comment);
        $logComment->setCreated($created);

        $emLog->persist($logComment);
        $emLog->flush();
      }

      // 処理前から変動している場合には、チケットを作成
      $em = $this->getDoctrine()->getManager('main');
      $em->refresh($choiceItem);
      if ($choiceItem->getStock() != $beforeStockNum) {
        $modifiedType = 'irregularly changed';
        $modifiedTypeWord = '（不明）';
        if ($choiceItem->getStock() > $beforeStockNum) {
          $modifiedType = 'increased';
          $modifiedTypeWord = '在庫増加';
        } else if ($choiceItem->getStock() < $beforeStockNum) {
          $modifiedType = 'decreased';
          $modifiedTypeWord = '在庫減少';
        }
        $logger->info(sprintf('stock %s : %s : %d => %d', $modifiedType, $choiceItem->getNeSyohinSyohinCode(), $beforeStockNum, $choiceItem->getStock()));

        /** @var WebAccessUtil $webAccessUtil */
        $webAccessUtil = $this->container->get('misc.util.web_access');

        $now = new \DateTime();
        $body = <<<EOD
|処理      |商品ロケーション詳細編集|
|担当者    |{$account->getUsername()}|
|日時      |{$now->format('Y-m-d H:i:s')}|
|商品コード|{$choiceItem->getNeSyohinSyohinCode()}|
|在庫数 処理前|{$beforeStockNum}|
|在庫数 処理後|{$choiceItem->getStock()}|
|内容|{$modifiedTypeWord}|
|コメント|{$comment}|


EOD;

        // 詳細
        $body .= "h3. 処理前\n\n";
        foreach ($prevLocations as $code => $num) {
          $body .= sprintf("|%s|%d|\n", $code, $num);
        }

        $body .= "\n";
        $body .= "h3. 処理後\n\n";
        /** @var TbProductLocationRepository $repoProductLocation */
        $repoProductLocation = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');
        $newLocations = $repoProductLocation->getActiveLocations($choiceItem->getNeSyohinSyohinCode());
        foreach ($newLocations as $newLocation) {
          $body .= sprintf("|%s|%d|\n", $newLocation['location_code'], $newLocation['stock']);
        }

        $ticket = [
          'issue' => [
            'subject' => sprintf('[商品ロケーション詳細編集][%s][%s] %s (%s)', $modifiedTypeWord, $now->format('Y-m-d H:i:s'), $choiceItem->getNeSyohinSyohinCode(), $account->getUsername())
            , 'project_id' => $this->container->getParameter('redmine_location_edit_change_num_ticket_project')
            , 'priority_id' => $this->container->getParameter('redmine_location_edit_change_num_ticket_priority')
            , 'description' => $body
            , 'assigned_to_id' => $this->container->getParameter('redmine_location_edit_change_num_ticket_user')
            , 'tracker_id' => $this->container->getParameter('redmine_location_edit_change_num_ticket_tracker')
            // , 'category_id'     => ''
            // , 'status_id'       => ''
          ]
        ];

        $logger->info($body);

        if ($this->container->getParameter('redmine_location_edit_change_num_ticket')) {
          $ret = $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
          $logger->info('redmine create ticket:' . $ret);
        }
      }

      $result['status'] = 'ok';
      $result['message'] = '商品ロケーション情報を更新しました。';
      $result['redirect'] = $this->generateUrl('location_product_detail', ['syohinCode' => $syohinCode]);

      $this->setFlash('success', '商品ロケーション情報を更新しました。');

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }

  /**
   * 商品ロケーション詳細 バーコード遷移
   * @param string $barcode
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function productBarcodeDetailAction($barcode)
  {
    if (preg_match('/^\d{13}$/', $barcode)) {
      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $choice = $repo->findByProductCode($barcode);

      if ($choice) {
        return $this->redirectToRoute('location_product_detail', ['syohinCode' => $choice->getNeSyohinSyohinCode()]);
      }
    }

    // 見つからなかった
    return $this->redirectToRoute('location_product_list'); // 特になにもしない
  }


  /**
   * ロケーション 詳細・編集画面
   * @param integer $locationId ロケーションID
   * @return Response
   */
  public function locationDetailAction($locationId)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    $data = [];

    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

    /** @var TbLocation $location */
    $location = $repo->find($locationId);

    if (!$location) {
      $this->setFlash('danger', 'ロケーションが見つかりませんでした。');
    } else if ($location->getWarehouseId() != $currentWarehouse->getId()) {
      $this->setFlash('danger', '別倉庫のロケーションは操作できません。');
    } else {
      $data = $this->getLocationDetailData($location);
    }


    // 画面表示
    return $this->render('AppBundle:Location:location-detail.html.twig', [
      'account' => $this->getLoginUser()
      , 'data' => $data
      , 'jsonData' => json_encode($data)
    ]);
  }

  /**
   * ロケーション詳細 更新確定処理
   * @param Request $request
   * @param integer $locationId
   * @return JsonResponse
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function locationDetailUpdateAction(Request $request, $locationId)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      /** @var TbLocation $location */
      $location = $repo->find($locationId);

      if (!$location) {
        throw new \RuntimeException('移動元のロケーションデータが見つかりません。');
      }

      $newLocationCode = $request->get('new_location_code');

      // ロケーションコード変換
      $newLocationCode = $this->fixLocationCode($newLocationCode);
      // ロケーションコード
      if (!$this->isValidLocationCode($newLocationCode)) {
        throw new \RuntimeException('ロケーションコードが正しくありません');
      }

      $newProductLocations = $request->get('new_location_products');
      if (!$newProductLocations || !is_array($newProductLocations)) {
        // 最低1件はデータがあるはず。
        throw new \RuntimeException('移動する在庫情報が指定されていません。');
      }

      // 同一ロケーションへの移動処理禁止実装
      if ($location->getLocationCode() == $newLocationCode) {
        throw new \RuntimeException('移動元と移動先が同じロケーションです。');
      }

      $logger->info($newLocationCode);
      $logger->info(print_r($newProductLocations, true));

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 対象データ 他処理からの更新済みチェック
      $currentData = $this->getLocationDetailData($location);
      $logger->info($request->get('data_hash'));
      $logger->info($currentData['dataHash']);
      if ($request->get('data_hash') !== $currentData['dataHash']) {
        throw new \RuntimeException('データが他処理により変更されているため更新できません。再読み込み後、再度データを編集してください。');
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // 移動先ロケーション取得or作成 ※必ず移動元と同じ倉庫
      /** @var TbLocation $location */
      $newLocation = $repo->getByLocationCode($location->getWarehouseId(), $newLocationCode);
      if (!$newLocation) {
        $newLocation = $repo->createNewLocation($location->getWarehouseId(), $newLocationCode);
        if (!$newLocation) {
          throw new \RuntimeException('ロケーションの新規作成に失敗しました。[' . $newLocationCode . ']');
        }
      }

      // 新規ロケーションIDへの追加・更新処理
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_product_location_move_location");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_product_location_move_location (
            ne_syohin_syohin_code VARCHAR(50) NOT NULL
          , location_id INT(11) NOT NULL
          , stock INT(11) NOT NULL
          , position INT(11) NOT NULL
          , old_location_id INT(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        INSERT INTO tmp_product_location_move_location (
            ne_syohin_syohin_code
          , location_id
          , stock
          , position
          , old_location_id
        ) VALUES (
            :syohinCode
          , :newLocationId
          , :stock
          , :position
          , :oldLocationId
        )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':newLocationId', $newLocation->getId());
      $stmt->bindValue(':oldLocationId', $location->getId());
      foreach ($newProductLocations as $productLocation) {
        $stmt->bindValue(':syohinCode', $productLocation['ne_syohin_syohin_code']);
        $stmt->bindValue(':stock', $productLocation['stock']);
        $stmt->bindValue(':position', $productLocation['position']);
        $stmt->execute();
      }

      // 移動する商品ロケーションを削除（positionのユニークのため、先に削除）
      $sql = <<<EOD
        DELETE pl
        FROM tb_product_location pl
        INNER JOIN tmp_product_location_move_location t
           ON pl.ne_syohin_syohin_code = t.ne_syohin_syohin_code
          AND pl.location_id = t.old_location_id
EOD;
      $dbMain->query($sql);

      // 移動先ロケーションに同じ商品がないものは、position引き継ぎでINSERT、あれば合算
      $sql = <<<EOD
        INSERT INTO tb_product_location (
            ne_syohin_syohin_code
          , location_id
          , stock
          , `position`
        )
        SELECT
            t.ne_syohin_syohin_code
          , t.location_id
          , t.stock AS stock
          , t.position
        FROM tmp_product_location_move_location t
        ON DUPLICATE KEY UPDATE
            tb_product_location.stock = tb_product_location.stock + VALUES(stock)
EOD;
      $dbMain->query($sql);

      // 一時テーブル削除
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_product_location_move_location");

      // ロケーション優先順位振り直し
      foreach ($newProductLocations as $productLocation) {
        $repo->renumberPositions($productLocation['ne_syohin_syohin_code']);
      }

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_DETAIL, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      // リダイレクト先
      // ロケーション再取得
      /** @var TbLocation $checkLocation */
      $checkLocation = $repo->find($locationId);
      if ($checkLocation) {
        $em = $this->getDoctrine()->getManager('main');
        $em->refresh($checkLocation);
      }

      // ロケーションに商品がなくなって削除されればロケーション一覧へ、まだ残っていればロケーション詳細画面へ
      if ($checkLocation && $checkLocation->getProductLocations()->count()) {
        $redirectUrl = $this->generateUrl('location_location_detail', ['locationId' => $locationId]);
      } else {
        $redirectUrl = $this->generateUrl('location_location_list');
      }

      $result['status'] = 'ok';
      $result['message'] = 'ロケーション情報を更新しました。';
      $result['redirect'] = $redirectUrl;

      $this->setFlash('success', 'ロケーション情報を更新しました。');

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }


  /**
   * ロケーション詳細 ロケーション削除処理
   * ※出荷確定戻しロケーションの削除用として実装
   *
   * @param Request $request
   * @param integer $locationId
   * @return JsonResponse
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function locationDetailDeleteAction(Request $request, $locationId)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      /** @var TbLocation $location */
      $location = $repo->find($locationId);
      if (!$location) {
        throw new \RuntimeException('削除するロケーションデータが見つかりません。');
      }

      $locationCode = $location->getLocationCode();

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 対象データ 他処理からの更新済みチェック
      $currentData = $this->getLocationDetailData($location);
      $logger->info($request->get('data_hash'));
      $logger->info($currentData['dataHash']);
      if ($request->get('data_hash') !== $currentData['dataHash']) {
        throw new \RuntimeException('データが他処理により変更されているため更新できません。再読み込み後、再度データを編集してください。');
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // 商品ロケーションを削除
      $sql = <<<EOD
        DELETE pl
        FROM tb_product_location pl
        WHERE pl.location_id = :id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $locationId, \PDO::PARAM_INT);
      $stmt->execute();

      // ロケーション優先順位振り直し
      $productLocations = $currentData['productLocations'];
      foreach ($productLocations as $productLocation) {
        $repo->renumberPositions($productLocation['neSyohinSyohinCode']);
      }

      // 修正コメントがあれば保存（現状、在庫変動時のみ）
      $comment = trim($request->get('comment', ''));
      $account = $this->getLoginUser();
      if (strlen($comment)) {
        $emLog = $this->getDoctrine()->getManager('log');
        $logComment = new TbProductLocationLogComment();
        $logComment->setAccount($account->getUsername());
        $logComment->setOperation(TbLocationRepository::LOG_OPERATION_WEB_LOCATION_DETAIL_DELETE);
        $logComment->setActionKey($actionKey);
        $logComment->setComment($comment);
        $logComment->setCreated(new \DateTime());

        $emLog->persist($logComment);
        $emLog->flush();
      }

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_DETAIL_DELETE, $this->getLoginUser()->getUsername(), $actionKey);

      $dbMain->commit();

      // リダイレクト先
      // ロケーションに商品がなくなっているはずなので、一覧へ戻る
      $redirectUrl = $this->generateUrl('location_location_list');

      $result['status'] = 'ok';
      $result['message'] = sprintf('[%s] ロケーションの商品在庫を一括削除しました。', $locationCode);
      $result['redirect'] = $redirectUrl;

      $this->setFlash('success', $result['message']);

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }


  /**
   * RFID連動 ロケーション編集画面
   * @return Response
   */
  public function rfidLocationEditorAction()
  {
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    // 画面表示
    return $this->render('AppBundle:Location:rfid-location-editor.html.twig', [
      'account' => $this->getLoginUser()
    ]);
  }

  /**
   * RFID連動 ロケーション編集画面 検索
   */
  public function rfidLocationEditorSearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok',
      'locationCodes' => [],
    ];

    try {
      /** @var TbRfidReadingsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRfidReadings');
      /** @var TbBoxCodeRepository $boxCodeRepo */
      $boxCodeRepo = $this->getDoctrine()->getRepository('MiscBundle:TbBoxCode');
      /** @var TbProductCodeRepository $productCodeRepo */
      $productCodeRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCode');
      /** @var TbLocationRepository $locationRepo */
      $locationRepo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      /** @var BaseRepository $choiceRepo */
      $choiceRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      $readingId = $request->get('readingId');
      $locationCode = $request->get('locationCode');

      // 読取IDから、箱タグ・商品タグを取得
      $readings = $repo->findRfidReadingsByReadingId($readingId);

      // 商品タグから、商品バーコード部分を抜き出す
      // 箱タグから、箱バーコード部分を抜き出す
      $boxBarCode = '';
      $productBarCodes = [];
      $i = 0;
      foreach ($readings as $reading) {
        if ($i === 0) {
          $boxTag = $reading['box_tag'];
          if ($boxTag !== '') {
            $boxBarCode = str_replace('-', '', substr($boxTag, 5, 16));
          }
        }
        $productTag = $reading['product_tag'];
        $productBarCodes[] = str_replace('-', '', substr($productTag, 5, 16));
        $i++;
      }

      // 現在倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();

      // ロケーションコードを指定しての再検索でなければ、
      if (!$locationCode) {
        // 箱バーコードから、箱コードを取得
        $boxCode = $boxCodeRepo->findBoxCodeByBarCode($boxBarCode);

        // 箱コードが取得できれば、そこからロケーションコードを検索
        if ($boxCode) {
          // ロケーションコードを箱コードで後方一致検索
          $locationCodes = $locationRepo->findLocationCodesByBoxCode(
            $boxCode,
            $currentWarehouse->getId()
          );
          if (count($locationCodes) === 1) {
            $locationCode = $locationCodes[0];
          } else {
            // 複数件存在する場合も特定できないので、不明扱いとする
            $locationCode = '';
          }
        }
      }

      // ロケーションコードからロケーション取得して、情報を取得
      $locationData = [];
      if ($locationCode) {
        $location = $locationRepo->findOneBy(['location_code' => $locationCode]);
        if (!$location) {
          throw new BusinessException('ロケーションが見つかりませんでした。');
        } elseif ($location->getWarehouseId() != $currentWarehouse->getId()) {
          throw new BusinessException('別倉庫のロケーションは操作できません。');
        } else {
          // ロケーションデータを取得
          $locationData = $locationRepo->findLocationDetailDataForRfid($location);
        }
      }

      // 商品バーコードから、SKUの種類と数を取得
      $rfidProducts = $productCodeRepo->findRfidProductsByBarCode($productBarCodes);
      // 商品ロケーションとRFID商品リストを統合
      $result['products'] = $this->mergeProductLocationAndRfid(
        $locationData['productLocations'] ?? [],
        $rfidProducts
      );
      $result['locationData'] = $locationData;

      // SKU毎の、現在倉庫商品ロケーション詳細データ取得（更新処理用）
      $result['productsDetail'] = [];
      foreach ($result['products'] as $product) {
        $sku = $product['neSyohinSyohinCode'];
        /** @var TbProductchoiceitems $choiceItem */
        $choiceItem = $choiceRepo->find($sku);
        $result['productsDetail'][$sku] = $this->getProductDetailData(
          $choiceItem,
          $currentWarehouse
        );
      }

    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("RFID連動ロケーション編集画面 検索処理でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品ロケーションとRFID商品リストを統合する
   * @param array $productLocations
   * @param array $rfidProducts
   * @return array
   */
  private function mergeProductLocationAndRfid($productLocations, $rfidProducts) {
    /** @var BaseRepository $pciRepo */
    $pciRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

    $mergedData = [];
    $locationSkus = [];
    foreach ($productLocations as $location) {
      $location['rfidStock'] = $rfidProducts[$location['neSyohinSyohinCode']] ?? 0;
      $location['diff'] = $location['rfidStock'] - $location['stock'];
      unset($location['locationId']);
      unset($location['position']);
      unset($location['created']);
      unset($location['updated']);
      $mergedData[] = $location;
      $locationSkus[] = $location['neSyohinSyohinCode'];
    };

    foreach ($rfidProducts as $sku => $rfidStock) {
      if (!in_array($sku, $locationSkus, true)) {
        $image = $pciRepo->find($sku)->getProduct()->getImageUrl(
          sprintf('//%s/images/', $this->container->getParameter('host_plusnao'))
        );
        $mergedData[] = [
          'neSyohinSyohinCode' => $sku,
          'stock' => 0,
          'rfidStock' => $rfidStock,
          'diff' => $rfidStock,
          'image' => $image,
        ];
      }
    }

    // 差分の多い順に並べる
    usort($mergedData, function($a, $b) {
      return abs($b['diff']) - abs($a['diff']);
    });

    return $mergedData;
  }

  /**
   * 重量・メール便枚数未設定商品一覧画面
   * @param $page
   * @param Request $request
   * @return Response
   */
  public function missingWeightProductListAction($page, Request $request)
  {
//    $perPage = 10;
    $perPage = 20;
    $orders = [];

    $conditions = [
        'delivery_method' => $request->get('delivery_method')
//     , 'stock_only' => "1"
//      , 'stock_only' => "12"
      , 'stock_only' => $request->get('stock_only')
    ];


    $searchDeliveryMethods = [
        1 => 'ゆうパケット・クリックポスト・ネコポス'
      , 2 => '宅配便、定形外ほか（上記以外）'
    ];

    $currentWarehouse = $this->getLoginUser()->getWarehouse();
//print_r($currentWarehouse);

    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $pagination = $repo->searchMissingWeightList($currentWarehouse, $conditions, $orders, $perPage, $page);
    $pagination->setPageRange(3); // ページナビ横幅

    $imageParentUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

    // ひとまず親商品画像
    $images = [];
    foreach($pagination->getItems() as $row) {
      $images[$row['daihyo_syohin_code']] = TbMainproductsRepository::createImageUrl($row['image_directory'], $row['image_filename'], $imageParentUrl);
    }

    // 画面表示
    return $this->render('AppBundle:Location:missing-weight-product-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'imageParentUrl' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'images' => $images
      , 'conditions' => $conditions
      , 'searchDeliveryMethods' => $searchDeliveryMethods
    ]);
  }

  /**
   * 送料設定 更新処理
   * @param Request $request
   * @param $daihyoSyohinCode
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deliveryInfoUpdateAction(Request $request, $daihyoSyohinCode)
  {
    $product = null;

    try {
      if ($daihyoSyohinCode) {
        /** @var TbMainproductsRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
        $product = $repo->find($daihyoSyohinCode);
      }

      if (!$product) {
        throw new \RuntimeException('商品情報が取得できませんでした。');
      }

      // 送料設定 更新フォーム
      $deliveryInfoForm = $this->createForm(new TbMainproductsDeliveryInfoType(), $product);
      $deliveryInfoForm->handleRequest($request);

      if ($deliveryInfoForm->isSubmitted()) {
        if ($deliveryInfoForm->isValid()) {
          // 設定保存処理
          $em = $this->getDoctrine()->getManager();
          $em->persist($product);
          $em->flush();
          $this->addFlash('success', '送料設定を更新しました。');

        } else {
          $this->addFlash('warning', '入力エラーがあります。');
        }
      }

    } catch (\Exception $e) {
      $this->addFlash('danger', 'エラーが発生しました。');
    }

    if ($product) {
      $response = $this->redirectToRoute('location_product_sku_list', ['daihyoSyohinCode' => $daihyoSyohinCode]);
      // 編集画面タブ選択のためにhash追加
      $response->setTargetUrl($response->getTargetUrl() . '#locationProductWeightEdit');

    } else {
      $response = $this->redirectToRoute('location_product_list');
    }

    return $response;
  }


  /**
   * 倉庫移動処理
   * @param Request $request
   * @return JsonResponse
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function warehouseMoveAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $locations = $request->get('locations');
      $moveTo = $request->get('moveTo');
      $logger->info(sprintf('process move warehouse => %s', $request->get('moveTo')));

      if (!is_array($locations) || !$locations) {
        throw new \RuntimeException('移動ロケーションが正しく選択されていません。');
      }
      if (!$moveTo) {
        throw new \RuntimeException('移動先倉庫が正しく選択されていません。');
      }

      /** @var TbWarehouseRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      /** @var TbWarehouse $warehouse */
      $warehouse = $repo->find($moveTo);
      if (!$warehouse) {
        throw new \RuntimeException('移動先倉庫が取得できませんでした。');
      }

      $logger->info('locations: ' . print_r($locations, true));
      $logger->info('moveTo: ' . print_r($warehouse->toScalarArray(), true));

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $locationIds = [];
      foreach($locations as $location) {
        $locationIds[] = $location['id'];
      }
      $logger->info('target location ids: ' . print_r($locationIds, true));

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 移動先倉庫に同一ロケーション名があればエラー（ここでロケーションの合流などすると却って不便、なはず）

      // 事前に、空ロケーションで消え残っているものを掃除。（ユニーク制約でエラーとなるため）
      $dbMain->beginTransaction();
      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);
      // 空になったロケーションを削除
      $repoLocation->deleteEmptyLocation();
      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_DELETE_EMPTY_LOCATION,  $this->getLoginUser()->getUsername(), $actionKey);
      $dbMain->commit();

      // 重複チェック
      $existsLocations = $repoLocation->getDuplicatedWarehouseMoveLocations($locationIds, $warehouse);
      $logger->info(print_r($existsLocations, true));

      if ($existsLocations) {
        $duplications = [];
        foreach($existsLocations as $row) {
          $duplications[] = sprintf('%s : %s', $row['id'], $row['location_code']);

        }
        throw new \RuntimeException('移動先に重複するロケーションコードがあります。処理を中止しました。=> ' . implode("\n", $duplications));
      }

      // 更新処理
      $dbMain->beginTransaction();
      $em = $this->getDoctrine()->getManager('main');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      $targetLocations = $repoLocation->findByIds($locationIds);
      foreach($targetLocations as $location) {
        $location->setWarehouseId($warehouse->getId());
      }
      $em->flush(); // 一度更新が必要。

      // 移動した全商品についてロケーションの優先順位を更新
      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems[] $choicesList */
      $choicesList = [];
      foreach($targetLocations as $location) {
        $choices = $repoChoice->findItemsByLocationId($location->getId());
        foreach($choices as $choice) {
          if (!isset($choicesList[$choice->getNeSyohinSyohinCode()])) {
            $choicesList[$choice->getNeSyohinSyohinCode()] = $choice;
          }
        }
      }
      foreach($choicesList as $choice) {
        $repoLocation->renumberPositionsByChoiceItem($choice);
      }

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_WAREHOUSE_MOVE, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '倉庫移動を実行しました。';

      $this->setFlash('success', '倉庫移動を実行しました。');

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }


  /**
   * ロケーション統合処理
   * @param Request $request
   * @return JsonResponse
   */
  public function mergeLocationAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      /** @var TbProductLocationRepository $repoProductLocation */
      $repoProductLocation = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');

      $targetLocationId = $request->get('merge_target');
      $locations = $request->get('locations', []);

      $logger->info($targetLocationId);
      $logger->info(print_r($locations, true));

      /** @var TbLocation $targetLocation */
      $targetLocation = $repo->find($targetLocationId);
      if (!$targetLocation) {
        throw new \RuntimeException('統合先のロケーションデータが見つかりません。');
      }

      // 対象ロケーションから、統合先を除外する。
      foreach($locations as $i => $location) {
        if ($location['id'] == $targetLocationId) {
          unset($locations[$i]);
          break;
        }
      }
      $logger->info(print_r($locations, true));

      if (!$locations) {
        throw new \RuntimeException('統合するロケーションが指定されていません。');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // 移動対象ロケーション取得
      /** @var TbProductLocation[] $moveLocations */
      $moveLocations = [];
      foreach($locations as $location) {
        $list = $repoProductLocation->getProductsByLocationId($location['id']);
        if ($list) {
          $moveLocations = array_merge($moveLocations, $list);
        }
      }
      foreach($moveLocations as $pl) {
        $logger->info(sprintf('move : %s %s (%d : %s)', $pl->getLocationId(), $pl->getNeSyohinSyohinCode(), $pl->getStock(), $pl->getLocation()->getLocationCode()));
      }

      // 移動先IDへの追加・更新処理
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_product_location_move_location");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_product_location_move_location (
            ne_syohin_syohin_code VARCHAR(50) NOT NULL
          , location_id INT(11) NOT NULL
          , stock INT(11) NOT NULL
          , position INT(11) NOT NULL
          , old_location_id INT(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        INSERT INTO tmp_product_location_move_location (
            ne_syohin_syohin_code
          , location_id
          , stock
          , position
          , old_location_id
        ) VALUES (
            :syohinCode
          , :newLocationId
          , :stock
          , :position
          , :oldLocationId
        )
EOD;
      $stmt = $dbMain->prepare($sql);
      $logger->info($targetLocation->getId());

      // 削除予定ロケーションID
      $removeLocationIds = [];
      foreach($moveLocations as $productLocation) {
        $logger->info($productLocation->getLocationId());
        $logger->info($productLocation->getNeSyohinSyohinCode());

        $stmt->bindValue(':syohinCode', $productLocation->getNeSyohinSyohinCode());
        $stmt->bindValue(':newLocationId', $targetLocation->getId());
        $stmt->bindValue(':stock', $productLocation->getStock());
        $stmt->bindValue(':position', $productLocation->getPosition());
        $stmt->bindValue(':oldLocationId', $productLocation->getLocationId());
        $stmt->execute();

        $removeLocationIds[] = $productLocation->getLocationId();
      }

      // 移動する商品ロケーションを削除（positionのユニークのため、先に削除、だったが、ユニークはもうない。でも問題ないのでこのまま。）
      $sql = <<<EOD
        DELETE pl
        FROM tb_product_location pl
        INNER JOIN tmp_product_location_move_location t
           ON pl.ne_syohin_syohin_code = t.ne_syohin_syohin_code
          AND pl.location_id = t.old_location_id
EOD;
      $dbMain->query($sql);

      // 移動先ロケーションに同じ商品がないものは、position引き継ぎでINSERT、あれば合算
      $sql = <<<EOD
        INSERT INTO tb_product_location (
            ne_syohin_syohin_code
          , location_id
          , stock
          , `position`
        )
        SELECT
            t.ne_syohin_syohin_code
          , t.location_id
          , t.stock AS stock
          , t.position
        FROM tmp_product_location_move_location t
        ON DUPLICATE KEY UPDATE
            tb_product_location.stock = tb_product_location.stock + VALUES(stock)
EOD;
      $dbMain->query($sql);

      // ロケーションそのものも削除
      $repo->deleteEmptyLocation(null, $removeLocationIds);

      // 一時テーブル削除
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_product_location_move_location");

      // ロケーション優先順位振り直し
      foreach($moveLocations as $productLocation) {
        $repo->renumberPositions($productLocation->getNeSyohinSyohinCode());
      }

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_MERGE, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = 'ロケーションを統合しました。 [' . $targetLocation->getWarehouseId() . ' : ' . $targetLocation->getLocationCode() . ']';

      $this->setFlash('success', 'ロケーションを統合しました。 [' . $targetLocation->getWarehouseId() . ' : ' . $targetLocation->getLocationCode() . ']');

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }

  /**
   * 棚番号一括変更 確認
   * @param Request $request
   * @return JsonResponse
   */
  public function validateChangeRackCodeAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'from' => []
      , 'to' => []
      , 'fromCount' => 0
      , 'toCount' => 0
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $logger->info(sprintf('validate change rack code %s => %s', $request->get('from'), $request->get('to')));

      $result['from'] = $repo->findCodeListByRackCode($currentWarehouse->getId(), $request->get('from'), $request->get('targets', []));
      $result['fromCount'] = count($result['from']);

      $result['to'] = $repo->findCodeListByRackCode($currentWarehouse->getId(), $request->get('to'));
      $result['toCount'] = count($result['to']);

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());
    }

    return new JsonResponse($result);
  }

  /**
   * 棚番号一括変更 実行
   */
  public function changeRackCodeAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $logger->info(sprintf('process change rack code %s => %s', $request->get('from'), $request->get('to')));

      $targetIds = $request->get('targets', []);
      $fromRackCode = $request->get('from', $targetIds);
      $toRackCode = $request->get('to');
      $result['from'] = $repo->findCodeListByRackCode($currentWarehouse->getId(), $fromRackCode);
      $result['to'] = $repo->findCodeListByRackCode($currentWarehouse->getId(), $toRackCode);

      // ここで最終チェック
      if (count($result['to'])) {
        throw new \RuntimeException('変更先の棚番号がすでに存在します。処理を中止しました。');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      $repo->changeRackCode($currentWarehouse->getId(), $fromRackCode, $toRackCode, $targetIds);

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_CHANGE_RACK, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '棚番号を一括変更しました。';

      $this->setFlash('success', '棚番号を一括変更しました。');

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }



  /**
   * 棚番号・位置コード一括削除 確認
   * @param Request $request
   * @return JsonResponse
   */
  public function validateRemoveRackCodeAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'duplicated' => []
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $logger->info(sprintf('validate remove rack code.'));

      $targetList = $repo->findCodeListByLocationIds($currentWarehouse->getId(), $request->get('targets', []));

      $result['list'] = $targetList;

      $toList = [];
      foreach($targetList as $location) {
        if (!strlen($location['location_code']) || !strlen($location['box_code'])) {
          throw new \RuntimeException(sprintf('箱番号が取得できませんでした。id:%d / %s / %s', $location['id'], $location['location_code'],$location['box_code']));
        } else {
          $toList[] = $location['box_code'];
        }
      }

      $exists = $repo->getByLocationCodeList($currentWarehouse->getId(), $toList, true);
      if ($exists) {
        $result['duplicated'] = [];
        foreach($exists as $location) {
          $result['duplicated'][] = $location->getLocationCode();
        }

        throw new \RuntimeException(sprintf('すでに存在するロケーションコードです。[' . implode(' / ', $result['duplicated']) . ']'));
      }

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());
    }

    return new JsonResponse($result);
  }

  /**
   * 棚番号・位置コード一括削除 実行
   * @param Request $request
   * @return JsonResponse
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function removeRackCodeAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $logger->info(sprintf('process remove rack code'));

      $targetIds = $request->get('targets', []);
      $result['list'] = $repo->findCodeListByLocationIds($currentWarehouse->getId(), $targetIds);

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);


      $sql = <<<EOD
        UPDATE
        tb_location l
        SET l.location_code = :newLocationCode
        WHERE l.id = :id
          AND l.location_code = :oldLocationCode /* 念のため */
EOD;
      $stmt = $dbMain->prepare($sql);
      foreach($result['list'] as $location) {
        $stmt->bindValue(':newLocationCode', $location['box_code'], \PDO::PARAM_STR);
        $stmt->bindValue(':id', $location['id'], \PDO::PARAM_INT);
        $stmt->bindValue(':oldLocationCode', $location['location_code'], \PDO::PARAM_STR);
        $stmt->execute();
      }

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_REMOVE_RACK_CODE, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '棚番号・位置コードを一括削除しました。';

      $this->setFlash('success', '棚番号・位置コードを一括削除しました。');

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }



  /**
   * 入荷箱振り補助画面 バーコード遷移
   * @param String $barcode
   * @return Response
   */
  public function storeImportProductsWithBarcodeAction($barcode)
  {
    /** @var TbProductchoiceitemsRepository $repoChoice */
    $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

    // SHOPLISTバーコード
    if (preg_match('/^[A-Z]{2}\d{12}[A-Z]$/', $barcode)) {
      /** @var TbProductchoiceitems $choice */
      $choice = $repoChoice->findByShoplistBarcode($barcode);
    // Plusnaoバーコード
    } else {
      /** @var TbProductchoiceitems $choice */
      $choice = $repoChoice->findByProductCode($barcode);
    }

    return $this->storeImportProductsAction($choice ? $choice->getNeSyohinSyohinCode() : null);
  }

  /**
   * 入荷箱振り補助画面 商品コード指定
   * @param string $syohinCode
   * @return Response
   */
  public function storeImportProductsAction($syohinCode)
  {
    $variationImage = null;
    $images = [];
    $imageUrl = sprintf('//%s/images', $this->getParameter('host_plusnao'));
    $variationImageUrl = sprintf('//%s/variation_images', $this->getParameter('host_plusnao'));
    $hashData = [];
    $sireComment = null;
    $locationList = [];

    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    $choice = null;
    if ($syohinCode) {
      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems $choice */
      $choice = $repoChoice->find($syohinCode);
    }

    if ($choice) {
      // カラー画像
      /** @var ProductImagesVariationRepository $repoColorImages */
      $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');
      $variationImage = $repoColorImages->findByNeSyohinSyohinCode($choice->getNeSyohinSyohinCode());

      /** @var ProductImagesRepository $repo */
      $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      /** @var ProductImages[] $images */
      $images = $repoImages->findBy(['daihyo_syohin_code' => $choice->getDaihyoSyohinCode()], ['code' => 'ASC']);

      // ロケーション
      /** @var TbProductLocation[] $locations */
      $locations = $choice->getActiveLocations($currentWarehouse)->toArray();
      usort($locations, function($a, $b) {
        /** @var TbProductLocation $a */
        /** @var TbProductLocation $b */
        return $a->getPosition() == $b->getPosition()
          ? 0
          : (
            $a->getPosition() < $b->getPosition() ? -1 : 1
          );
      });

      // JavaScriptで利用するのでscalar配列に変換
      $locationList = [];
      foreach($locations as $location) {
        $loc = $location->toScalarArray('camel');
        $loc['locationCode'] = $location->getLocation()->getLocationCode();
        $locationList[] = $loc;
      }

      // 変更検知用データ取得（hashのみ利用）
      $hashData = $this->getProductDetailData($choice, $currentWarehouse);

      // 仕入備考を取得
      $sireComment = $choice->getProduct()->getOrderComment();

      // レビュー平均点取得
      /** @var TbProductReviewsRepository $repoReview */
      $repoReview = $this->getDoctrine()->getRepository('MiscBundle:TbProductReviews');
      $reviewDateFrom = (new \DateTimeImmutable())->modify('-1 year');
      $allAverage = $repoReview->getAllAverage([
        'date_from' => $reviewDateFrom,
        'daihyo_syohin_code' => $choice->getDaihyoSyohinCode(),
      ]);
    }
    $reviewAverage = isset($allAverage) ? (float)$allAverage['all_average'] : null;
    $reviewDateFromFormatted = isset($reviewDateFrom) ? date_format($reviewDateFrom, "Ymd") : null; // 商品一覧画面呼び出し用クエリパラメータ

    // バーコードリンク
    $url = $this->generateUrl('location_import_products_barcode', [ 'barcode' => '__DUMMY__' ]);
    $url = str_replace('__DUMMY__', '{CODE}', $url);
    $redirectUrl = sprintf('https://%s%s', $this->getParameter('host_main'), $url);
    $barcodeUrl = sprintf('zxing://scan/?ret=%s', urlencode($redirectUrl));

    return $this->render('AppBundle:Location:store-import-product.html.twig', array(
        'account' => $this->getLoginUser()
      , 'choice' => $choice
      , 'sireComment' => json_encode($sireComment)
      , 'reviewAverage' => json_encode($reviewAverage)
      , 'variationImage' => $variationImage
      , 'imageUrl' => $imageUrl
      , 'variationImageUrl' => $variationImageUrl
      , 'images' => $images
      , 'barcodeUrl' => $barcodeUrl
      , 'locationListJson' => json_encode($locationList)
      , 'hashData' => $hashData
      , 'reviewDateFrom' => $reviewDateFromFormatted
    ));
  }

  /**
   * ロケーション箱振り 実行処理
   * @param Request $request
   * @return JsonResponse
   */
  public function storeImportProductsSubmitAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'redirect' => null
    ];

    try  {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $syohinCode = $request->get('syohin_code');
      $locationCode = $request->get('move_from'); // ロケーションコード
      $moveTo = $request->get('move_to'); // ロケーションコード
      $moveNum = $request->get('move_num');

      $logger->info($syohinCode);
      $logger->info($locationCode);
      $logger->info($moveTo);
      $logger->info($moveNum);

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      $choice = null;
      if ($syohinCode) {
        /** @var TbProductchoiceitems $choice */
        $choice = $repoChoice->find($syohinCode);
      }

      if (!$choice) {
        throw new \RuntimeException('商品が見つかりませんでした。');
      }

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      /** @var TbLocation $location */
      $location = $repo->getByLocationCode($currentWarehouse->getId(), $locationCode);
      if (!$location) {
        throw new \RuntimeException('移動元のロケーションデータが見つかりません。');
      }

      $newLocationCode = $moveTo;

      // ロケーションコード変換
      $newLocationCode = $this->fixLocationCode($newLocationCode);
      // ロケーションコード
      if (!$this->isValidLocationCode($newLocationCode)) {
        throw new \RuntimeException('移動先のロケーションコードが正しくありません');
      }

      // 同一ロケーションへの移動処理禁止実装
      if ($locationCode == $newLocationCode) {
        throw new \RuntimeException('移動元と移動先が同じロケーションです。');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 対象データ 他処理からの更新済みチェック
      $currentData = $this->getProductDetailData($choice, $currentWarehouse);
      $logger->info($request->get('data_hash'));
      $logger->info($currentData['dataHash']);
      if ($request->get('data_hash') !== $currentData['dataHash']) {
        throw new \RuntimeException('データが他処理により変更されているため更新できません。再読み込み後、再度データを編集してください。');
      }

      $em = $this->getDoctrine()->getManager('main');

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // ロケーション一覧取得
      $productLocations = $choice->getActiveLocations($currentWarehouse);
      $fromProductLocation = null;
      $toProductLocation = null;
      foreach($productLocations as $pl) {
        if ($pl->getLocation()->getLocationCode() == $locationCode) { // 移動元 hit!
          $fromProductLocation = $pl;
        }
        if ($pl->getLocation()->getLocationCode() == $newLocationCode) { // 移動先 hit!
          $toProductLocation = $pl;
        }
      }
      if (!$fromProductLocation) {
        throw new \RuntimeException('移動元のロケーションに該当商品がありませんでした。');
      }
      if ($fromProductLocation->getStock() < $moveNum) {
        throw new \RuntimeException('移動数がロケーションの在庫数を超えています。');
      }

      // 同一商品で移動先にすでにある場合（簡単！）
      if ($toProductLocation) {
        $toProductLocation->setStock($toProductLocation->getStock() + $moveNum);
      // 同一商品でまだない場合。（分割）
      } else {

        // 移動先ロケーション取得or作成
        /** @var TbLocation $location */
        $newLocation = $repo->getByLocationCode($currentWarehouse->getId(), $newLocationCode);
        if (!$newLocation) {
          $newLocation = $repo->createNewLocation($currentWarehouse->getId(), $newLocationCode);
          if (!$newLocation) {
            throw new \RuntimeException('ロケーションの新規作成に失敗しました。[' . $newLocationCode . ']');
          }
        }

        $toProductLocation = new TbProductlocation();
        $toProductLocation->setChoiceItem($choice);
        $toProductLocation->setLocation($newLocation);
        $toProductLocation->setNeSyohinSyohinCode($choice->getNeSyohinSyohinCode()); // これがいる理由が今ひとつよくわからない。↑のsetChoiceItem()はなんなんだ
        $toProductLocation->setLocationId($newLocation->getId()); // 同上

        $toProductLocation->setStock($moveNum);
        $toProductLocation->setPosition($choice->getMaxLocationPosition($currentWarehouse) + 1);

        $em->persist($toProductLocation);
        $choice->addLocation($toProductLocation);
      }

      // 移動分減少
      $fromProductLocation->setStock($fromProductLocation->getStock() - $moveNum);

      // 全移動なら移動元を削除
      if ($fromProductLocation->getStock() <= 0) {
        $choice->removeLocation($fromProductLocation);
        $em->remove($fromProductLocation);
      }

      $em->flush();

      // ロケーション優先順位振り直し
      $repo->renumberPositionsByChoiceItem($choice);

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_STORE_IMPORT, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      // リダイレクト先
      $redirectUrl = $this->generateUrl('location_import_products_store', ['syohinCode' => $syohinCode]);

      $result['status'] = 'ok';
      $result['message'] = 'ロケーション情報を更新しました。';
      $result['redirect'] = $redirectUrl;

      $this->setFlash('success', 'ロケーション情報を更新しました。');

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

    }

    return new JsonResponse($result);
  }

  /**
   * 空棚一覧画面
   * @return Response
   */
  public function emptyRackListAction()
  {
     $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
    // 画面表示
    return $this->render('AppBundle:Location:empty-rack-list.html.twig', [
      'account' => $this->getLoginUser()
   ]);
  }

  /**
   * 空き棚場所コード一覧取得(Ajax)
   */
  public function getEmptyRackListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'listALL' => []
    ];

    try {
      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      $result['list'] = $repo->findEmptyRacks($this->getLoginUser()->getWarehouseId()); // 自分の倉庫のみ
      $result['listALL'] = $repo->countExistRacks2(); // 全倉庫　イニシャルのみ

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 空き棚 ロケーション移動 確認情報取得
   * @param Request $request
   * @return JsonResponse
   */
  public function emptyRackMoveLocationConfirmAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $rackCode = $request->get('rackCode');
      $placeCode = $request->get('placeCode');
      $moveBoxCode = $request->get('boxCode');

      $logger->info(sprintf('move confirm: %s - %s - %s', $rackCode, $placeCode, $moveBoxCode));

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // すでに場所が塞がっていればエラー
      $exists = $repo->findByRackPlaceCode($currentWarehouse->getId(), $rackCode, $placeCode);
      if (count($exists)) {
        $loc = $exists[0];
        throw new \RuntimeException(sprintf('すでにロケーションが存在します。 [ %s ]', $loc['location_code']));
      }

      // データ取得処理 実装
      $locations = $repo->findLocationsByBoxCode($currentWarehouse->getId(), $moveBoxCode);
      if (empty($locations)) {
        throw new \RuntimeException('該当するロケーションが見つかりませんでした。');
      }
      if (count($locations) > 1) {
        $codes = [];
        foreach($locations as $location) {
          $codes[] = $location['location_code'];
        }
        throw new \RuntimeException('該当するロケーションが複数見つかりました。この機能からは操作できません。 [ ' . implode(', ', $codes) . ' ]');
      }

      $targetLocation = $locations[0];

      // もしすでに棚・場所まで配置されていれば対象外
      // ※ひとまず場当たり的に、ハイフンの数でチェック
      $tmp = explode('-', $targetLocation['location_code']);
      if (count($tmp) > 2) {
        throw new \RuntimeException('このロケーションはすでに棚番号・位置番号が設定されています。 [' . $targetLocation['location_code'] . ']');
      }

      // ここまで来たらOK
      $targetLocation['move_to'] = sprintf('%s-%s-%s', $rackCode, $placeCode, strtoupper($moveBoxCode));

      $result['result'] = $targetLocation;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 空き棚 ロケーション移動 処理実行
   * @param Request $request
   * @return JsonResponse
   */
  public function emptyRackMoveLocationSubmitAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'result' => null
    ];

    try {
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $id = $request->get('id');
      $moveTo = $request->get('moveTo');

      $logger->info(sprintf('move submit: %s => %s', $id, $moveTo));

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      /** @var TbLocation $location */
      $location = $repo->find($id);
      if (!$location) {
        throw new \RuntimeException('移動元のロケーションが見つかりませんでした。');
      }

      // データ更新処理 実装

      // ロケーションコード変換
      $newLocationCode = $this->fixLocationCode($moveTo);
      // ロケーションコード
      if (!$this->isValidLocationCode($newLocationCode)) {
        throw new \RuntimeException('ロケーションコードが正しくありません');
      }

      // 同一ロケーションへの移動処理禁止実装
      if ($location->getLocationCode() == $newLocationCode) {
        throw new \RuntimeException('移動元と移動先が同じロケーションです。');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repo->setLocationLogActionKey($dbMain);

      // 移動先ロケーション取得or作成
      /** @var TbLocation $location */
      $newLocation = $repo->getByLocationCode($currentWarehouse->getId(), $newLocationCode);
      if ($newLocation) {
        throw new \RuntimeException('すでに移動先のロケーションが存在します。処理を中断しました。');
      }
      $newLocation = $repo->createNewLocation($currentWarehouse->getId(), $newLocationCode);
      if (!$newLocation) {
        throw new \RuntimeException('ロケーションの新規作成に失敗しました。[' . $newLocationCode . ']');
      }

      // 移動元データ
      /** @var TbProductLocation[] $productLocations */
      $productLocations = $location->getProductLocations();
      if (!$productLocations) {
        throw new \RuntimeException('移動元の商品ロケーションデータが見つかりません。');
      }

      $em = $this->getDoctrine()->getManager('main');
      foreach($productLocations as $productLocation) {
        $productLocation->setLocation($newLocation);
      }

      // 移動元ロケーション削除（バッチ処理で消えるが一応）
      $em->remove($location);

      $em->flush();

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_LOCATION_MOVE_EMPTY_RACK, $this->getLoginUser()->getUsername(), $actionKey);

      // 在庫数には変更なし、のはずなので実装もなし。

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '空き棚への箱移動を完了しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  // -------------------------------------------------------


  /**
   * 処理中の排他処理取得
   */
  private function getRunningExclusiveTasks()
  {
    $exclusiveTasks = [
        '仕入注残一覧入力確定'
      , '在庫データ取込'
      , 'ロケーション更新'
      , '商品ロケーション自動並べ替え処理'
    ];

    // 入荷入力確定 or 在庫取込 or 在庫取込＋ロケーション更新処理 or 商品ロケーション自動並べ替え処理 の実行中
    $runningTasks = $this->get('misc.util.db_common')->getRunningProcesses();
    $results = [];
    foreach($runningTasks as $task) {
      if (in_array($task['proc'], $exclusiveTasks)) {
        $results[] = $task['proc'];
      }
    }

    if ($results) {
      $this->get('misc.util.batch_logger')->info('LOCATION RUNNING TASK: ' . print_r($results, true));
    }
    return $results;
  }

  /**
   * 商品詳細画面 データ取得処理
   * @param TbProductchoiceitems $choiceItem
   * @param TbWarehouse $warehouse
   * @return array
   */
  private function getProductDetailData($choiceItem, $warehouse)
  {
    $data = array();

    $data['choiceItem'] = [
        'neSyohinSyohinCode' => $choiceItem->getNeSyohinSyohinCode()
      , 'stock' => $choiceItem->getStock()
      , 'daihyoSyohinCode' => $choiceItem->getDaihyoSyohinCode()
      , 'colname' => $choiceItem->getColname()
      , 'rowname' => $choiceItem->getRowname()
      , 'supportColname' => $choiceItem->getSupportColname()
      , 'supportRowname' => $choiceItem->getSupportRowname()
    ];

    // カラー画像
    /** @var ProductImagesVariationRepository $repoColorImages */
    $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');
    $variationImage = $repoColorImages->findByNeSyohinSyohinCode($choiceItem->getNeSyohinSyohinCode());

    if ($variationImage) {
      $image = sprintf('//%s/variation_images/', $this->getParameter('host_plusnao')) . $variationImage->getFileDirPath();
    } else {
      $image = $choiceItem->getProduct()->getImageUrl(sprintf('//%s/images/', $this->getParameter('host_plusnao')));
    }
    $data['image'] = $image;

    $data['rakutenUrl'] = $choiceItem->getProduct()->getRakutenDetailUrl();

    $data['locations'] = [];
    $data['warehouseStockTotal'] = 0;
    /** @var TbProductLocation[] $locations */
    $locations = $choiceItem->getActiveLocations($warehouse); // position昇順(schema.yml指定)
    foreach($locations as $productLocation) {
      $loc = $productLocation->toScalarArray('camel');
      $location = $productLocation->getLocation();
      $loc['locationCode'] = $location->getLocationCode();
      $loc['warehouseId'] = $location->getWarehouseId();
      $data['locations'][] = $loc;
      $data['warehouseStockTotal'] += $productLocation->getStock();
    }

    $data['dataHash'] = sha1(serialize($data));
    return $data;
  }

  /**
   * ロケーション詳細画面 データ取得
   * @param TbLocation $location
   * @return array
   */
  private function getLocationDetailData($location)
  {
    $data = [];

    $data['location'] = [
        'id'           => $location->getId()
      , 'locationCode' => $location->getLocationCode()
      , 'type'         => $location->getType()
    ];

    // 倉庫情報取得
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbWarehouse $warehouse */
    $warehouse = $repoWarehouse->find($location->getWarehouseId());

    $data['warehouse'] = $warehouse->toScalarArray();

    $data['productLocations'] = [];
    $productLocations = $location->getProductLocations()->filter(function($productLocation) {  // 商品コード昇順(schema.yml指定)
      /** @var TbProductLocation $productLocation */
      return $productLocation->getPosition() >= 0;
    });

    /** @var TbLocationWarehouseToListRepository $warehouseTo */
    $warehouseToRepo = $this->getDoctrine()->getRepository('MiscBundle:TbLocationWarehouseToList');

    foreach($productLocations as $productLocation) {
      /** @var TbProductLocation $productLocation */
      $loc = $productLocation->toScalarArray('camel');

      $choiceItem = $productLocation->getChoiceItem();
      $loc['image'] = $choiceItem->getProduct()->getImageUrl(sprintf('//%s/images/', $this->getParameter('host_plusnao')));

      // 倉庫へ 在庫数
      /** @var TbLocationWarehouseToList $warehouseTo */
      $warehouseTo = $warehouseToRepo->find($productLocation->getNeSyohinSyohinCode());
      $loc['moveNum'] = $warehouseTo ? $warehouseTo->getMoveNum() : 0;

      $data['productLocations'][] = $loc;
    }

    $data['dataHash'] = sha1(serialize($data));
    return $data;
  }

  /**
   * ロケーション文字列 入力補助変換
   * @param string $code
   * @return string
   */
  private function fixLocationCode($code)
  {
    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
    return $repo->fixLocationCode($code);
  }

  /**
   * ロケーション文字列 書式チェック
   * @param string $code
   * @return bool
   */
  private function isValidLocationCode($code)
  {
    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
    return $repo->isValidLocationCode($code);
  }

 /**
  * 倉庫状況の報告 一覧 
  */
  public function statusOfWarehouseListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // 表示する倉庫一覧、表示にも関わるので順序を入れ替えないこと
    $reportWarehouseIdList = [
      TbWarehouseRepository::MINAMI_KYOBATE_WAREHOUSE_ID,
      TbWarehouseRepository::FURUICHI_WAREHOUSE_ID,
      TbWarehouseRepository::KYUUMUKAI_WAREHOUSE_ID,
    ];
    
    // 無視するイニシャルリスト（棚なしで、箱を直接利用しているものなど）
    $ignoreInitial = 'PSVX';
    

    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
    
    // 現状存在する箱を全て取得
    $rawExistsBoxList = $repo->findExistsBoxPlaceCodeByWarehouseIds($reportWarehouseIdList);
    
    // 4個以下の箱リストを初期化。倉庫IDでまず初期化（廃止された倉庫がヘッダだけ出るようになる）

    $existsBoxList = [];
    foreach ($reportWarehouseIdList as $warehouseId) {
      $existsBoxList[$warehouseId] = null;
    }
    foreach ($rawExistsBoxList as $row) {
      if (preg_match("/[$ignoreInitial]/", $row['placeInitial']) // 無視するイニシャル
        || ! preg_match("/[0-9]/", $row['placeNum']) // 2文字目が数字でない場合も無視（[FOOD]のような特殊箱かも）
      ) {
        continue;
      }
      $existsBoxList[$row['warehouseId']][$row['placeInitial']][$row['placeInitial'] . $row['placeNum']] = 0;
    }

    // テーブルの最大行サイズ取得と（もし）データが存在しない倉庫の初期化
    $maxRowSize = 0;
    foreach ($existsBoxList as $warehouseId => $initials) {
      if (empty($initials)) {
        $existsBoxList[$warehouseId]['null'] = [];
        continue;
      }
      foreach ($initials as $boxNums) {
        $rowSize = count($boxNums);
        if ($maxRowSize < $rowSize) {
          $maxRowSize = $rowSize;
        }
      }
    }

    // 最大行数に満たない配列を埋める
    foreach ($existsBoxList as $warehouseId => $initials) {
      foreach ($initials as $initial => $boxNums) {
        $size = count($boxNums);
        if ($size < $maxRowSize) {
          $nullIndex = 0;
          for ($i = $size; $i < $maxRowSize; $i++) {
            $existsBoxList[$warehouseId][$initial][$warehouseId . 'null' . $nullIndex]
              = null;
            $nullIndex++;
          }
        }
      }
    }

    // 棚ごとの在庫数が4個以下の箱の数
    $lessBoxList = $repo->countNumOfBoxWithLessThan4InStockByWarehouseIds($reportWarehouseIdList);
    foreach ($lessBoxList as $row) {
      if (isset($existsBoxList[$row['warehouseId']][$row['placeInitial']][$row['placeInitial'] . $row['placeNum']])) {
        $existsBoxList[$row['warehouseId']][$row['placeInitial']][$row['placeInitial'] . $row['placeNum']]
          = $row['stockTotal'];
      }
    }

    // 列数の取得とテーブル用データの作成、行と列を入れ替える
    $boxNumList = [];
    $warehouseColSizeList = [];
    foreach ($existsBoxList as $warehouseId => $initials) {
      if (empty($initials)) {
        $warehouseColSizeList[$warehouseId] = 2;
        continue;
      } else {
        $warehouseColSizeList[$warehouseId] = count($initials) * 2;
      }
      foreach ($initials as $initials => $boxNums) {
        $i = 0;
        foreach ($boxNums as $code => $count) {
          $boxNumList[$i][] = ['code' => $code, 'count' => $count];
          $i++;
        }
      }
    }
    
    $tmp = $repo->countExistRacks(); // 空棚数
    $emptyRackList = [];
    foreach($tmp as $emptyRack){
      if (in_array($emptyRack['warehouseId'], $reportWarehouseIdList) // 表示対象倉庫である
          && preg_match("/[$ignoreInitial]/", $emptyRack['RackNo']) == 0) { // 表示対象外イニシャルではない
        $emptyRackList[$emptyRack['RackNo']] = $emptyRack['RackCo'];
      }
    }

    $tmp = $repo->countMovedBoxBetweenWarehouse(); // 倉庫間移動箱数
    $warehouseBoxList = [
      '古市から他へ'        => ['boxCount' => 0, 'lastUpdate' => ''] ,
      '詰替古市から他へ'    => ['boxCount' => 0, 'lastUpdate' => ''] ,
      '詰替MUGENから古市へ' => ['boxCount' => 0, 'lastUpdate' => ''] ,
      '南京終から古市へ'    => ['boxCount' => 0, 'lastUpdate' => ''] ,
      '旧ムカイから古市へ'  => ['boxCount' => 0, 'lastUpdate' => ''] ,
    ];
    foreach($tmp as $warehouseBox){
        if($warehouseBox['isMovedFuruichi'] == 0){
            if($warehouseBox['warehouse_id'] == TbWarehouseRepository::FURUICHI_WAREHOUSE_ID){
              $warehouseBoxList['古市から他へ']['boxCount'] = $warehouseBox['stockTotal'];
              $warehouseBoxList['古市から他へ']['lastUpdate'] = $warehouseBox['lastUpdate'];
            }
            if($warehouseBox['warehouse_id'] == TbWarehouseRepository::TUMEKAE_FURUICHI_WAREHOUSE_ID){
              $warehouseBoxList['詰替古市から他へ']['boxCount'] = $warehouseBox['stockTotal'];
              $warehouseBoxList['詰替古市から他へ']['lastUpdate'] = $warehouseBox['lastUpdate'];
            }
        }
        if($warehouseBox['isMovedFuruichi'] == 1){
            if($warehouseBox['warehouse_id'] == TbWarehouseRepository::TUMEKAE_MUGEN_WAREHOUSE_ID){
              $warehouseBoxList['詰替MUGENから古市へ']['boxCount'] = $warehouseBox['stockTotal'];
              $warehouseBoxList['詰替MUGENから古市へ']['lastUpdate'] = $warehouseBox['lastUpdate'];
            }
            if($warehouseBox['warehouse_id'] == TbWarehouseRepository::MINAMI_KYOBATE_WAREHOUSE_ID){
              $warehouseBoxList['南京終から古市へ']['boxCount'] = $warehouseBox['stockTotal'];
              $warehouseBoxList['南京終から古市へ']['lastUpdate'] = $warehouseBox['lastUpdate'];
            }
            if($warehouseBox['warehouse_id'] == TbWarehouseRepository::KYUUMUKAI_WAREHOUSE_ID){
              $warehouseBoxList['旧ムカイから古市へ']['boxCount'] = $warehouseBox['stockTotal'];
              $warehouseBoxList['旧ムカイから古市へ']['lastUpdate'] = $warehouseBox['lastUpdate'];
            }
        }
    }

    // 最終更新時刻を取得 開発では倉庫間箱移動処理のログがなくエラーになる場合があるので、取れない時はダミー文字列
    $logRepo = $this->getDoctrine()->getRepository('MiscBundle:TbLog');
    $logList = $logRepo->findBy(['exec_title' => '倉庫間箱移動処理'], ['id' => 'DESC'], 1 );
    $lastTime2MoveBox = '----';
    if (!empty($logList)) {
      $log = $logList[0];
      $lastTime2MoveBox = $log->getExecTimestamp()->format('Y-m-d h:m:s');
    }
    return $this->render('AppBundle:Location:status-of-warehouse-list.html.twig',[
          'account'          => $this->getLoginUser()
        , 'boxNumList'       => $boxNumList
        , 'maxRowSize'       => $maxRowSize - 1
        , 'warehouseColSizeList' => $warehouseColSizeList
        , 'emptyRackList'    => $emptyRackList
        , 'warehouseBoxList' => $warehouseBoxList
        , 'lastTime2MoveBox' => $lastTime2MoveBox
    ]);
  }
}
