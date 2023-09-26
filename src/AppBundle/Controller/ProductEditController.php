<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use MiscBundle\Entity\MappedSuperClassTbMainproducts;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbMainproductsImportabilityRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRakutenAttributeRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Entity\Repository\TbProductSeasonRepository;
use MiscBundle\Entity\Repository\TbRakutenGenreAttributeRepository;
use MiscBundle\Entity\Repository\TbRakutenGenreRepository;
use MiscBundle\Entity\TbMainproductsImportability;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Service\ProductDeleteService;
use MiscBundle\Service\RakutenService;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Entity\ProductNgWord;
use MiscBundle\Entity\Repository\ProductNgWordRepository;

/**
 * 商品情報編集画面
 * @package AppBundle\Controller
 */
class ProductEditController extends BaseController
{
  /**
   * 楽天属性設定 表示
   */
  public function rakutenAttributeAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:ProductEdit:rakuten-attribute.html.twig', [
      'account' => $account,
    ]);
  }

  /**
   * 楽天属性設定 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function rakutenAttributeSearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbProductchoiceitemsRepository $pciRepo */
      $pciRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbRakutenGenreRepository $gRepo */
      $gRepo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenGenre');
      /** @var TbRakutenGenreAttributeRepository $gaRepo */
      $gaRepo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenGenreAttribute');
      /** @var TbProductchoiceitemsRakutenAttributeRepository $aRepo */
      $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsRakutenAttribute');
      /** @var RakutenService $service */
      $service = $this->get('misc.service.rakuten');

      $daihyoSyohinCode = $request->get('daihyoSyohinCode');
      $skipUpdateProcess = (bool)$request->get('skipUpdateProcess');

      $list['axis'] = [
        'col' => $pciRepo->findDistinctColDataByDaihyoSyohinCode($daihyoSyohinCode),
        'row' => $pciRepo->findDistinctRowDataByDaihyoSyohinCode($daihyoSyohinCode),
      ];

      $list['daihyoSyohin'] = $gRepo->findRakutenAttributeDaihyoSyohinInfo($daihyoSyohinCode);
      if (empty($list['daihyoSyohin'])) {
        throw new BusinessException('代表商品情報が存在しません');
      }
      $rakutenGenreId = $list['daihyoSyohin']['rakutenGenreId'];

      /*
        findGenreAttributesDetailByApi() は、findGenreAttributesByApi() の返り値と比べて、
        推奨値関係の情報量が増えるだけで、全体構造は変わらない（代用が効く）。
        そのため、下記(1)(2)のために別々にAPIを叩く必要はないので、ここで詳細の方を取得し共有する。
      */
      $genreAttributesDetails = $service->findGenreAttributesDetailByApi($rakutenGenreId);

      // (1) 楽天商品属性項目マスタを、現在のAPIの内容に更新
      $gaRepo->upsertRakutenGenreAttribute([
        $rakutenGenreId => $genreAttributesDetails['genre']['attributes']
      ]);

      $isAutoUpdated = false;
      if (!$skipUpdateProcess) {
        // SKU別必須属性自動登録処理
        // （初期は必須属性のみ。任意属性も自動登録させるなら、第二引数を省略かfalse指定。）
        $autoUpdatedCount = $aRepo->autoUpsertSkuAttribute($daihyoSyohinCode, true);
        $isAutoUpdated = $autoUpdatedCount > 0;
      }

      // 設定画面に表示する属性情報取得
      // （初期は必須属性のみ。任意属性も表示させるなら、第二引数を省略かfalse指定。）
      $list['genreAttributes'] = $gaRepo->findGenreAttributes($rakutenGenreId, true);

      // (2) 属性情報に、APIで取得した推奨値関連の情報を付与
      foreach ($genreAttributesDetails['genre']['attributes'] as $attribute) {
        $id = $attribute['id'];
        if (array_key_exists($id, $list['genreAttributes'])) {
          // 選択式かどうか
          $list['genreAttributes'][$id]['isSelective'] =
            $attribute['properties']['rmsInputMethod'] === 'SELECTIVE';
          // 推奨値有無
          $list['genreAttributes'][$id]['hasRecommend'] = $attribute['properties']['rmsRecommend'];
          // 推奨値一覧
          $list['genreAttributes'][$id]['recommends'] = $attribute['dictionaryValues'];
        }
      }

      $list['skuAttributes'] = $aRepo->findSkuAttributes(
        $daihyoSyohinCode,
        array_keys($list['genreAttributes'])
      );

      $result = [
        'status' => 'ok',
        'message' => null,
        'list' => $list,
        'isAutoUpdated' => $isAutoUpdated,
      ];
    } catch (BusinessException $e) {
      $logger->error('楽天属性設定 検索でエラー発生:' . $e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
      $result['list'] = $list;
    } catch (\Exception $e) {
      $logger->error('楽天属性設定 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 楽天属性設定 更新
   * @param Request $request
   * @return JsonResponse
   */
  public function rakutenAttributeUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbProductchoiceitemsRakutenAttributeRepository $aRepo */
      $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsRakutenAttribute');
      $aRepo->upsertSkuAttributes($request->get('modifiedList'));
      $result['status'] = 'ok';
    } catch (\Exception $e) {
      $logger->error('楽天属性設定 更新でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * モール商品 表示
   * @param $daihyoSyohinCode
   */
  public function mallProductAction($daihyoSyohinCode)
  {
    $account = $this->getLoginUser();

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $mallProduct['main'] = $repo->findMallProductMainInfo($daihyoSyohinCode);
    $mallProduct['byShop'] = $repo->findMallProductByShopInfo($daihyoSyohinCode);
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    $taxRate = $commonUtil->getTaxRate();

    // 画面表示
    return $this->render('AppBundle:ProductEdit:mall-product.html.twig', [
      'account' => $account,
      'mallProduct' => json_encode($mallProduct),
      'taxRate' => round($taxRate, 2),
    ]);
  }

  /**
   * モール商品 更新
   * @param Request $request
   * @return JsonResponse
   */
  public function mallProductUpdateAction(Request $request)
  {
    $result = [
      'status' => 'ok',
      'message' => null,
    ];

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    try {
      $dbMain->beginTransaction();

      $daihyoSyohinCode = $request->get('daihyoSyohinCode');
      $mainModifiedList = $request->get('mainModifiedList') ?? [];
      $byShopModifiedList = $request->get('byShopModifiedList') ?? [];
      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      if (count($mainModifiedList) > 0) {
        $repo->updateMallProductMainInfo($daihyoSyohinCode, $mainModifiedList);
      }
      if (count($byShopModifiedList) > 0) {
        $repo->updateMallProductByShopInfo($daihyoSyohinCode, $byShopModifiedList);
      }

      $dbMain->commit();

      // 更新後データ取得
      // 表示Actionはあくまで最低限の表示のみにし、検索Actionを別に設けた方が良いようにも思うが、
      // 少し時間かかりそうなので、一旦更新&検索処理を切り分けずに対応。
      $result['mallProduct'] = [
        'main' => $repo->findMallProductMainInfo($daihyoSyohinCode),
        'byShop' => $repo->findMallProductByShopInfo($daihyoSyohinCode),
      ];
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('モール商品 更新でエラー発生: ' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['error'] = $e->getMessage();
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
    }
    return new JsonResponse($result);
  }

  /**
   * サイズ登録 表示
   */
  public function sizeRegisterAction()
  {
    return $this->render('AppBundle:ProductEdit:size-register.html.twig', [
      'account' => $this->getLoginUser(),
    ]);
  }

  /**
   * サイズ登録 検索・確認
   */
  public function sizeRegisterSearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'item' => null,
    ];

    try {
      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var BaseRepository $codeRepo */
      $codeRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductCode');
      $barcode = $request->get('barcode');
      // バーコードの指定がある場合は、それで検索。なければ指定されたSKUで検索。
      $neSyohinSyohinCode = '';
      if ($barcode) {
        $procuctCode = $codeRepo->findOneBy(['barcode' => $barcode]);
        $neSyohinSyohinCode = $procuctCode ? $procuctCode->getNeSyohinSyohinCode() : '';
      } else {
        $neSyohinSyohinCode = $request->get('neSyohinSyohinCode');
      }
      $skuList = $repo->findSameAxisItems($neSyohinSyohinCode);
      if (!empty($skuList)) {
        /** @var MappedSuperClassTbMainproducts $product */
        $product = $skuList[0]->getProduct();
        $result['item'] = [
          'barcode' => $barcode ?: $codeRepo->findOneBy(['ne_syohin_syohin_code' => $neSyohinSyohinCode])->getBarcode(),
          'neSyohinSyohinCode' => $neSyohinSyohinCode,
          'daihyoSyohinName' => $product->getDaihyoSyohinName(),
          'imageDir' => $product->getImageP1Directory(),
          'imageFile' => pathinfo($product->getImageP1Filename())['filename'],
          'width' => $skuList[0]->getWidth(),
          'height' => $skuList[0]->getHeight(),
          'depth' => $skuList[0]->getDepth(),
          'weight' => $skuList[0]->getWeight(),
        ];
        foreach ($skuList as $sku) {
          $result['item']['skuList'][] = $sku->getNeSyohinSyohinCode();
        }
      }
    } catch (\Exception $e) {
      $logger->error('サイズ登録 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * サイズ登録 更新
   */
  public function sizeRegisterUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = ['status' => 'ok'];
    try {
      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $skuList = $request->get('skuList');
      $width = $request->get('width');
      $height = $request->get('height');
      $depth = $request->get('depth');
      $weight = $request->get('weight');
      $repo->updateSize($skuList, $width, $height, $depth, $weight);
    } catch (\Exception $e) {
      $logger->error('サイズ登録 更新でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 輸出入可否 表示
   */
  public function importabilityAction()
  {
    $account = $this->getLoginUser();

    // ステータス一覧を取得
    $statusList = TbMainproductsImportability::STATUS_LIST;

    // 注残ステータス一覧を取得
    $remainStatusList = TbIndividualorderhistoryRepository::REMAIN_STATUS_LIST;

    // 画面表示
    return $this->render('AppBundle:ProductEdit:importability.html.twig', [
      'account' => $account,
      'statusList' => json_encode($statusList),
      'remainStatusList' => json_encode($remainStatusList),
    ]);
  }

  /**
   * 輸出入可否 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function importabilitySearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = ['status' => 'ok', 'message' => null, 'count' => 0, 'list' => []];

    try {
      $conditions =  $request->get('conditions');
      $this->dateYmdValidate($conditions['settingDateFrom'], '設定日From');
      $this->dateYmdValidate($conditions['settingDateTo'], '設定日To');

      $paging = $request->get('paginationObj');
      $limit = (int)$paging['initPageItemNum'];
      $page = (int)$paging['page'];

      /** @var TbMainproductsImportabilityRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproductsImportability');
      $list = $repo->findList($conditions, $limit, $page);
      $result['count'] = $list['count'];

      // 画像表示用のパス追加
      $enlargedImageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $result['list'] = array_map(function ($row) use ($enlargedImageUrlParent) {
        $row['thumbList'] = [];
        $row['srcList'] = [];
        foreach ($row['imagePaths'] as $image) {
          if (!$image) {
            continue;
          }
          $pathinfo = pathinfo($image);
          // サムネイル用パス（拡張子を外す）
          $row['thumbList'][] = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
          // 拡大画像用URL
          $row['srcList'][] = TbMainproductsRepository::createImageUrl(
            $pathinfo['dirname'],
            $pathinfo['basename'],
            $enlargedImageUrlParent
          );
        }
        unset($row['imagePaths']);
        return $row;
      }, $list['list']);
    } catch (\Exception $e) {
      $logger->error('商品輸出入可否管理 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 輸出入可否 更新
   * @param Request $request
   * @return JsonResponse
   */
  public function importabilityUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = ['status' => 'ok', 'message' => null, 'data' => null];

    try {
      $target = $request->get('target');
      $conditions = $request->get('conditions');
      $daihyoSyohinCode = $conditions['daihyoSyohinCode'];

      /** @var EntityManager $em */
      $em = $this->getDoctrine()->getManager();
      /** @var TbMainproductsImportabilityRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproductsImportability');
      /** @var TbMainproductsImportability $importability */
      $importability = $repo->find($daihyoSyohinCode);
      $isNew = false;
      if (!$importability) {
        $importability = new TbMainproductsImportability();
        $importability->setDaihyoSyohinCode($daihyoSyohinCode);
        $isNew = true;
      }
      $userId = $this->getLoginUser()->getId();
      switch ($target) {
        case 'status':
          $importability->setImportabilityStatus($conditions['value']);
          $importability->setStatusUpdateAccountId($userId);
          $importability->setStatusUpdated(new \DateTime());
          $importability->setUpdateAccountId($userId);
          if ($isNew) {
            $em->persist($importability);
          }
          $em->flush();
          break;
        case 'note':
          $importability->setNote($conditions['value']);
          $importability->setUpdateAccountId($userId);
          if ($isNew) {
            $importability->setImportabilityStatus(0);
            $importability->setStatusUpdateAccountId(0);
            $em->persist($importability);
          }
          $em->flush();
          break;
        default:
          throw new ValidationException('更新対象が正しくありません。');
      }
    } catch (\Exception $e) {
      $logger->error('商品輸出入可否管理 更新でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * シーズン設定 表示
   */
  public function seasonSettingAction()
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:ProductEdit:season-setting.html.twig', [
      'account' => $account,
    ]);
  }

  /**
   * シーズン設定 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function seasonSettingSearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbProductSeasonRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSeason');

      $daihyoSyohinCode = $request->get('daihyoSyohinCode');

      // シーズン設定、代表商品情報（商品名、画像パス等）を取得
      $list = $repo->findProductSeasonWithMainProductInfo($daihyoSyohinCode);

      // 担当者チェック
      /** @var TbProductSalesAccountRepository $aRepo */
      $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
      $staffList = array_map(function($userId) {
        return (int)$userId;
      }, $aRepo->findValidUserIdsByDaihyoSyohinCode($daihyoSyohinCode));
      $list['mainProduct']['hasStaff'] = (bool)$staffList;
      $list['mainProduct']['isStaff'] = in_array(
        $this->getLoginUser()->getId(),
        $staffList,
        true
      );

      $result = [
        'status' => 'ok',
        'message' => null,
        'list' => $list,
      ];
    } catch (BusinessException $e) {
      $logger->error("シーズン設定 検索でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("シーズン設定 検索でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * シーズン設定 更新
   * @param Request $request
   * @return JsonResponse
   */
  public function seasonSettingUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var TbProductSeasonRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSeason');

      $daihyoSyohinCode = $request->get('daihyoSyohinCode');

      // 担当者チェック
      /** @var TbProductSalesAccountRepository $aRepo */
      $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
      $staffList = array_map(function($userId) {
        return (int)$userId;
      }, $aRepo->findValidUserIdsByDaihyoSyohinCode($daihyoSyohinCode));
      $hasStaff = (bool)$staffList;
      $isStaff = in_array(
        $this->getLoginUser()->getId(),
        $staffList,
        true
      );

      // 自身が担当者でない場合に、現在も更新可能な状況かの簡易チェック
      // (トランザクションまでは行わない)
      if (!$isStaff) {
        // 他の担当者が不在かのチェック
        if ($hasStaff) {
          throw new ValidationException(
            '既に他の担当者が登録されている為、更新できません。再検索してご確認ください'
          );
        }
      }

      // 空配列の項目は、なぜかキー毎削除されるので再定義。
      $months = $request->get('months') ?? [];  // 全設定全月OFFだとnullになるので、空配列に再変換
      $keys = ['hattyuten', 'nesage', 'kisetsuzaikoteisu'];
      foreach ($keys as $key) {
        if (!array_key_exists($key, $months)) {
          $months[$key] = [];
        }
      }

      $repo->updateProductSeason($daihyoSyohinCode, $months);
      $result['status'] = 'ok';
      $result['message'] = null;

    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("シーズン設定 更新でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * 商品削除　画面表示
   * @param Request $request
   */
  public function deleteAction(Request $request) {
    $account = $this->getLoginUser();
    // 画面表示
    return $this->render('AppBundle:ProductEdit:delete.html.twig', [
      'account' => $account,
    ]);
  }
  
  /**
   * 商品削除　対象データ検索
   * @param Request $request
   */
  public function deleteSearchAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $daihyoSyohinCode = $request->get('daihyoSyohinCode');
    $result = [
      'status' => 'ok',
      'message' => null,
      'productDeleteInfo' => null
    ];
    
    try {
      /** @var ProductDeleteService $deleteService */
      $deleteService = $this->get('misc.service.product_delete');
      $result['productDeleteInfo'] = $deleteService->findProductInfoForDelete($daihyoSyohinCode);

    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("商品削除　対象データ検索でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }
  
  /**
   * 商品削除　削除実行
   * @param Request $request
   */
  public function deleteExecuteAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $daihyoSyohinCode = $request->get('daihyoSyohinCode');
    $result = [
      'status' => 'ok',
      'message' => null,
    ];
    
    try {
      // まず削除不可項目がないか、改めてチェック
      /** @var ProductDeleteService $deleteService */
      $deleteService = $this->get('misc.service.product_delete');
      $productDeleteInfo = $deleteService->findProductInfoForDelete($daihyoSyohinCode);
      if (!$productDeleteInfo['product'] || !$productDeleteInfo['canDelete']) {
        $logger->debug("対象商品削除エラー:" . print_r($productDeleteInfo, true));
        throw new BusinessException("商品が存在しないか、削除不可項目があるため、削除できません。$daihyoSyohinCode");
      }
      $deleteResult = $deleteService->deleteProduct($daihyoSyohinCode, $this->getLoginUser()->getId());
      $result = array_merge($result, $deleteResult);
      
    } catch (BusinessException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error("商品削除　対象データ削除でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
    
  }

  /**
   * 日付がYmd形式であり、且つ適切な日付であるかチェックする。
   * @param string $date
   * @param string $dateName
   */
  public function dateYmdValidate($date, $dateName)
  {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!empty($date)) {
      if (!preg_match($datePattern, $date)) {
        throw new ValidationException($dateName . ' がyyyy-mm-dd形式ではありません [' . $date . ']');
      }
      list($year, $month, $day) = explode('-', $date);
      if (!checkdate($month, $day, $year)) {
        throw new ValidationException($dateName . ' が正しい日付ではありません [' . $date . ']');
      }
    }
  }
  /**
   * 商品NGワード一覧 表示
   */
  public function ngWordIndexAction()
  {
    $account = $this->getLoginUser();
    // 画面表示
    return $this->render('AppBundle:ProductEdit:ngword-list.html.twig', [
        'account' => $account,
    ]);
  }

  /**
   * 商品NGワード一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function ngWordSearchAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductNgWord');

      $result = [
          'status' => 'ok',
          'message' => null,
          'list' => [],
          'count' => 0,
      ];

      try {
          $conditions['keyword'] = $request->get('keyword', null);
          $conditions['sortKey'] = $request->get('sortKey', null);
          $conditions['sortVal'] = $request->get('sortVal', null);
          
          $paging = $request->get('paginationObj');
          $limit = (int) $paging['initPageItemNum'];
          $page = (int) $paging['page'];

          $list = $repo->findKeywordForNgWordList($conditions, $limit, $page);
          $result['list'] = $list->getItems();
          $result['count'] = $list->getTotalItemCount();
      } catch (\Exception $e) {
        $logger->error("商品NGワード一覧 検索でエラー発生: $e");
        $result['status'] = 'ng';
        $result['message'] = $e->getMessage();
      }
      return new JsonResponse($result);
  }

  /**
   * 商品NGワード新規登録
   * チェック
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function ngWordCreateAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $result = [
          'status' => 'ok',
          'message' => null,
      ];
      try {
          $content = $request->get('content', null);
          $em = $this->getDoctrine()->getManager('main');
          /** @var ProductNgWordRepository $repo */
          $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductNgWord');
          $result = $this->ngWordValidateContent($result, $repo, $content, null);
          if($result['status'] == 'ng') {
              return new JsonResponse($result);
          }
          $record = new ProductNgWord();
          $record->setContent(trim($content));
          $record->setCreated(new \DateTime());
          $em->persist($record);
          $em->flush();
          $result['status'] = 'ok';
      } catch (\Exception $e) {
        $logger->error("商品NGワード一覧 新規登録でエラー発生: $e");
        $result['status'] = 'ng';
        $result['message'] = $e->getMessage();
      }
      return new JsonResponse($result);
  }

  /**
   * 商品NGワード更新
   * チェック
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function ngWordUpdateAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $result = [
          'status' => 'ok',
          'message' => null,
      ];
      try {
          $id = $request->get('id', null);
          $content = $request->get('content', null);
          $em = $this->getDoctrine()->getManager('main');
          /** @var ProductNgWordRepository $repo */
          $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductNgWord');
          $result = $this->ngWordValidateContent($result, $repo, $content, $id);
          if($result['status'] == 'ng') {
              return new JsonResponse($result);
          }
          $record = $repo->find($id);
          $record->setContent(trim($content));
          $em->persist($record);
          $em->flush();
          $result['status'] = 'ok';
      } catch (\Exception $e) {
          $logger->error("商品NGワード一覧 更新でエラー発生: $e");
          $result['status'] = 'ng';
          $result['message'] = $e->getMessage();
      }
      return new JsonResponse($result);
  }

  /**
   * 商品NGワード削除
   * チェック
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function ngWordDeleteAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $result = [
          'status' => 'ok',
          'message' => null,
      ];
      try {
          $id = $request->get('id', null);
          $em = $this->getDoctrine()->getManager('main');
          /** @var ProductNgWordRepository $repo */
          $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductNgWord');
          $record = $repo->find($id);
          $em->remove($record);
          $em->flush();
          $result['status'] = 'ok';
      } catch (\Exception $e) {
        $logger->error("商品NGワード一覧 削除でエラー発生: $e");
        $result['status'] = 'ng';
        $result['message'] = $e->getMessage();
      }
      return new JsonResponse($result);
  }

  /**
   * 重複チェック
   * 文字サイズチェック
   */
  public function ngWordValidateContent($result, $repo, $content, $id = null)
  {
      if (empty($content)) {
          $result['status'] = 'ng';
          $result['message'] = 'NGワードを入力してください。';
          return $result;
      }
      if ($content && mb_strlen($content) > 30) {
          $result['status'] = 'ng';
          $result['message'] = '30文字以下のNGワードを入力してください。';
          return $result;
      }
      $record = $repo->findOneByContent($content);
      if ($record && $record->getId() != $id) {
          $result['status'] = 'ng';
          $result['message'] = trim($content) . 'は重複しているため処理できません。';
          return $result;
      }
  }
}
