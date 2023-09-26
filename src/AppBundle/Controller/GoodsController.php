<?php

namespace AppBundle\Controller;

use BatchBundle\Exception\LeveledException;
use BatchBundle\Job\BaseJob;
use BatchBundle\Job\NonExclusiveJob;
use BatchBundle\MallProcess\NextEngineMallProcess;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\Repository\ProductImagesAttentionImageRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbMixedProductRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbVendormasterdataRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbProductReviewsRepository;
use MiscBundle\Entity\Repository\TbSalesDetailSummaryItemYmRepository;
use MiscBundle\Entity\Repository\TbSkuDescriptionsRepository;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbMainproductsEnglish;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\TbSalesDetailSummaryItemYm;
use MiscBundle\Entity\TbSkuDescriptions;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbVendormasterdata;
use MiscBundle\Entity\TmpProductImages;
use MiscBundle\Entity\TbProductReviews;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Form\TbMainproductsSimpleExhibitType;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 商品管理画面
 * @package AppBundle\Controller
 */
class GoodsController extends BaseController
{

  /**
   * 商品一覧 表示
   */
  public function listAction()
  {
    $account = $this->getLoginUser();

    $deliverycodeList = TbMainproductsCal::$DELIVERY_CODE_LIST;

    // 画面表示
    return $this->render('AppBundle:Goods:list.html.twig', [
        'account' => $account,
        'deliverycodeList' => json_encode($deliverycodeList)
    ]);
  }

  /**
   * 商品一覧 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function findAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $userId = $this->getLoginUser()->getId();

    $result = [
      'status' => 'ok',
      'message' => null,
      'list' => [],
      'count' => 0,
    ];

    try {
      // JSからの情報を検索条件として、$conditionsに格納。
      // 配列が空の場合、キーごと削除されるので、空配列として再定義。
      $conditions =  $request->get('conditions');
      if (!array_key_exists('deliverycodes', $conditions)) {
        $conditions['deliverycodes'] = [];
      }
      $conditions['sortKey'] = $request->get('sortKey');
      $conditions['sortDesc'] = $request->get('sortDesc');

      $paging = $request->get('paginationObj');
      $limit = (int)$paging['initPageItemNum'];
      $page = (int)$paging['page'];

      $list = $repo->findDaihyoSyohinInfoForProductList($conditions, $userId, $limit, $page);
      foreach ($list->getItems() as $row) {
        $row[] = $row['mignonlindoUrl'] = sprintf(
          'https://store.shopping.yahoo.co.jp/mignonlindo/%s.html',
          $row['daihyoSyohinCode']
        );

        // ファイル名の拡張子を外す
        $row['imageFile'] = pathinfo($row['imageFile'])['filename'];

        $row['salesTransitionUrl'] = $this->generateUrl('goods_analyze_sales_transition', [
          'daihyoSyohinCode' => $row['daihyoSyohinCode']
        ]);
        $row['salesAccounts'] = $row['salesAccounts'] ? explode(',', $row['salesAccounts']) : [];

        $result['list'][] = $row;
        $result['count'] = $list->getTotalItemCount();
      }

    } catch (\Exception $e) {
      $logger->error('商品一覧 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 英語商品情報 一覧
   */
  public function listEnglishDataAction(Request $request)
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Goods:list-english-data.html.twig', [
        'account' => $account
    ]);
  }


  /**
   * 英語商品情報一覧 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findEnglishDataListAction(Request $request)
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

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

      $pagination = $repo->findEnglishDataList($conditions, $fixedOrders, $pageItemNum, $page);

      $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $list = [];
      foreach($pagination->getItems() as $row) {
        $row['image_url'] = TbMainproductsRepository::createImageUrl($row['image_dir'], $row['image_file'], $imageUrlParent);
        $list[] = $row;
      }

      $result['list'] = $list;
      $result['count'] = $pagination->getTotalItemCount();

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 商品 英語データ入力画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function editEnglishDataAction(Request $request)
  {
    $account = $this->getLoginUser();

    $product = null;
    $english = null;
    $isNew = false;

    $code = $request->get('code');

    if ($code) {
      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbMainproducts $product */
      $product = $repo->find($code);

      if ($product) {
        $english = $product->getEnglish();

        // 新規
        if ($product && !$english) {
          $english = new TbMainproductsEnglish();
          $english->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
          $english->setProduct($product);
          $english->setManualInput(-1);

          $isNew = true;
        }

      } else {
        $this->setFlash('warning', '商品がみつかりませんでした。');
      }
    }

    // 更新処理
    if ($product && $request->isMethod(Request::METHOD_POST)) {

      $em = $this->getDoctrine()->getManager('main');

      try {
        // 新規
        if ($isNew) {
          $em->persist($english);
        }

        $english->setTitle($request->get('title', null));
        $english->setDescription($request->get('description', null));
        $english->setAboutSize($request->get('about_size', null));
        $english->setAboutMaterial($request->get('about_material', null));
        $english->setAboutColor($request->get('about_color', null));
        $english->setAboutBrand($request->get('about_brand', null));
        $english->setUsageNote($request->get('usage_note', null));
        $english->setSupplementalExplanation($request->get('supplemental_explanation', null));
        $english->setShortDescription($request->get('short_description', null));
        $english->setShortSupplementalExplanation($request->get('short_supplemental_explanation', null));

        $english->setManualInput($request->get('manual_input') ? -1 : 0);
        $english->setCheckFlg($request->get('check_flg') ? -1 : 0);

        $em->flush();

        $this->setFlash('success', sprintf('英語情報を%sしました。', $isNew ? '作成' : '更新'));

      } catch (\Exception $e) {
        $logger = $this->get('misc.util.batch_logger');
        $logger->error($e->getMessage());

        $this->setFlash('danger', 'エラーが発生しました。 ' . $e->getMessage());
      }

      return $this->redirectToRoute('goods_edit_english', [ 'code' => $product->getDaihyoSyohinCode() ]);
    }

    // 画面表示
    return $this->render('AppBundle:Goods:edit-english-data.html.twig', [
        'account' => $account
      , 'code' => $code
      , 'product' => $product
      , 'english' => $english
    ]);
  }

  /**
   * 商品コピー画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function copyProductAction(Request $request)
  {
    $account = $this->getLoginUser();

    $fromCode = $request->get('from');
    $toCode = $request->get('to');
    $fromProduct = null;
    $toProduct = null;

    $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

    $repo = $this->getDoctrine()->getRepository(TbMainproducts::class);
    if ($fromCode) {
      /** @var TbMainproducts $fromProduct */
      $fromProduct = $repo->find($fromCode);

      if (!$fromProduct) {
        $this->setFlash('warning', 'コピー元商品が見つかりません。');
      }
    }
    if ($toCode) {
      $toProduct = $repo->find($toCode);
    }

    // 画面表示
    return $this->render('AppBundle:Goods:copy-product.html.twig', [
        'account' => $account
      , 'from' => $fromCode
      , 'to' => $toCode
      , 'fromProduct' => $fromProduct
      , 'toProduct' => $toProduct
      , 'imageUrlParent' => $imageUrlParent
    ]);
  }


  /**
   * 商品コピー処理
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function processCopyProductAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $account = $this->getLoginUser();
    $fromCode = $request->get('from');
    $toCode = $request->get('to');

    try {

      if (!$fromCode) {
        throw new \RuntimeException('コピー元商品コードが指定されていません。');
      }
      if (!$toCode) {
        throw new \RuntimeException('コピー先商品コードが指定されていません。');
      }

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository(TbMainproducts::class);
      $repo->copyProduct($fromCode, $toCode, $account);



      $this->setFlash('success', '商品をコピーしました。');

    } catch (\Exception $e) {

      $message = $e->getMessage();
      $logger->error($message);
      $logger->error($e->getTraceAsString());

      // コピー処理エラーであれば、DBログ保存＆WEB通知する
      if ($e instanceof LeveledException && $e->higherThanEqual(LeveledException::ERROR)) {
        $info = [
            'account' => $account->getId()
          , 'account_name' => $account->getUsername()
          , 'error' => $e->getMessage()
          , 'trace' => $e->getTraceAsString()
        ];

        $logger->addDbLog(
          $logger->makeDbLog('商品管理', '商品コピー処理', 'コピーエラー', '', '', sprintf('WEB(%s)', $account->getUsername()))
            ->setLogLevel(BatchLogger::ERROR)
            ->setInformation($info)
          , true
          , null
          , 'error'
        );
      }

      $this->setFlash('danger', $message);
    }

    // 画面表示
    return $this->redirectToRoute('goods_copy_product_input', [ 'from' => $fromCode, 'to' => $toCode ]);
  }


  /**
   * 商品複合出品設定
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function mixedProductListAction()
  {
    // 画面表示
    return $this->render('AppBundle:Goods:mixed-product-list.html.twig', [
        'account' => $this->getLoginUser()
    ]);
  }

  /**
   * 商品複合出品設定 一覧取得（Ajax）
   */
  public function findMixedProductListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      $conditions = [
          'mall_code' => DbCommonUtil::MALL_CODE_AMAZON // 現状、Amazon固定
        , 'parent' => $request->get('parent')
      ];

      /** @var TbMixedProductRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMixedProduct');
      $list = $repo->findList($conditions);

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      $data = [];

      $imageParentUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

      foreach($list as $row) {
        $parent = $row['parent'];
        if (!isset($data[$parent])) {
          $data[$parent] = [];
        }
        // 画像URL
        $row['image_url'] = TbMainproductsRepository::createImageUrl($row['child_image_directory'], $row['child_image_filename'], $imageParentUrl);
        $row['parent_image_url'] = TbMainproductsRepository::createImageUrl($row['parent_image_directory'], $row['parent_image_filename'], $imageParentUrl);

        // キャメルケース変換
        $tmp = $row;
        $row = [];
        foreach($tmp as $k => $v) {
          $k = $stringUtil->convertToCamelCase($k);
          $row[$k] = $v;
        }

        $data[$parent][] = $row;
      }

      // JS用に添え字配列化して格納
      $result['list'] = [];
      foreach($data as $parent => $list) {
        $result['list'][] = [
            'parent' => $parent
          , 'parentImageUrl' => $list[0]['parentImageUrl']
          , 'list' => $list
        ];
      }

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品複合出品 保存処理
   * @param Request $request
   * @return JsonResponse
   */
  public function saveMixedProductAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      $parent = $request->get('parent');
      $list = $request->get('list', []);

      $logger->info(print_r($parent, true));
      $logger->info(print_r($list, true));

      /** @var TbMixedProductRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMixedProduct');
      $repo->saveList(DbCommonUtil::MALL_CODE_AMAZON, $parent, $list); // 現状、Amazonのみ

      $result['message'] = '複合出品設定を保存しました。';

      $logger->info(print_r($result, true));

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 在庫確認画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function stockListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $page = $request->get('page', 1);
    $limit = $request->get('limit', 1000);

    $account = $this->getLoginUser();
    $indexId = $account->getId();

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    if ($request->isMethod(Request::METHOD_POST)) {

      try {
        /** @var UploadedFile $file */
        $file = $request->files->get('upload');

        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext != 'csv') {
          throw new \RuntimeException('CSVファイルをアップロードしてください。');
        }

        /** @var FileUtil $fileUtil */
        $fileUtil = $this->get('misc.util.file');

        $fs = new Filesystem();
        $uploadDir = sprintf('%s/Import/goods', $fileUtil->getWebCsvDir());
        if (!$fs->exists($uploadDir)) {
          $fs->mkdir($uploadDir, 0755);
        }

        $logger->info('uploaded : ' . print_r($file->getPathname(), true));

        // 1行目（最初のデータ）で文字コード判定 ＆ UTF-8変換
        $fp = fopen($file->getPathname(), 'rb');
        $firstLine = fgets($fp);
        fclose($fp);
        if (!$firstLine) { // 1行目がなければスルー（データが無いため処理不要）
          throw new \RuntimeException('データが空です。');
        }

        $newFilePath = tempnam($uploadDir, 'utf_');
        chmod($newFilePath, 0666);
        $fp = fopen($newFilePath, 'wb');
        $fileUtil->createConvertedCharsetTempFile($fp, $file->getPathname(), 'SJIS-WIN', 'UTF-8');
        fclose($fp);
        $newFile = new File($newFilePath);

        $beforeCount = $this->countStockListProducts($indexId);

        // 全削除
        $sql = <<<EOD
          DELETE l
          FROM tb_stock_list_product l
          WHERE l.index_id = :indexId
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':indexId', $indexId, \PDO::PARAM_INT);
        $stmt->execute();

        // インポート
        $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tb_stock_list_product
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n'
            ( ne_syohin_syohin_code )
            SET index_id = :indexId
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':importFilePath', $newFile->getPathname(), \PDO::PARAM_STR);
        $stmt->bindValue(':indexId', $indexId, \PDO::PARAM_INT);
        $stmt->execute();

        // 存在しない商品コードの削除
        $sql = <<<EOD
          DELETE s
          FROM tb_stock_list_product s
          LEFT JOIN tb_productchoiceitems pci ON s.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          WHERE s.index_id = :indexId
            AND pci.ne_syohin_syohin_code IS NULL
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':indexId', $indexId, \PDO::PARAM_INT);
        $stmt->execute();

        $afterCount = $this->countStockListProducts($indexId);

        $result = [
            'before' => $beforeCount
          , 'after' => $afterCount
          , 'diff' => $afterCount - $beforeCount
          , 'fileName' => $file->getClientOriginalName()
        ];

        $this->setFlash('success', sprintf('商品コードの一覧を更新しました。( %d 件 )', $result['after']) );

      } catch (\Exception $e) {
        $logger->error($e->getMessage());
        $logger->info($e->getTraceAsString());

        $this->setFlash('danger', $e->getMessage());
      }

      return $this->redirectToRoute('goods_stock_list');
    }

    // データ取得
    $sqlSelect = <<<EOD
      SELECT
          s.ne_syohin_syohin_code
        , pci.daihyo_syohin_code
        , pci.`在庫数`
        , COALESCE(vs.`受注数`, 0) AS 受注数
        , pci.`引当数`
        , COALESCE(vs.`受注数`, 0) - pci.`引当数` AS 未引当数
        , pci.`ピッキング引当数`
        , pci.`出荷予定取置数`
        , pci.`フリー在庫数`
        , pci.`発注残数`
EOD;
    $sqlBody = <<<EOD
      FROM tb_stock_list_product s
      INNER JOIN tb_productchoiceitems pci ON s.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      LEFT JOIN v_product_stock_sales vs ON s.ne_syohin_syohin_code = vs.ne_syohin_syohin_code
      WHERE s.index_id = :indexId
EOD;


    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');

    $rsm = new ResultSetMappingBuilder($em);
    $rsm->addScalarResult('ne_syohin_syohin_code', 'ne_syohin_syohin_code', 'string');
    $rsm->addScalarResult('daihyo_syohin_code', 'daihyo_syohin_code', 'string');
    $rsm->addScalarResult('在庫数', '在庫数', 'integer');
    $rsm->addScalarResult('受注数', '受注数', 'integer');
    $rsm->addScalarResult('引当数', '引当数', 'integer');
    $rsm->addScalarResult('未引当数', '未引当数', 'integer');
    $rsm->addScalarResult('ピッキング引当数', 'ピッキング引当数', 'integer');
    $rsm->addScalarResult('出荷予定取置数', '出荷予定取置数', 'integer');
    $rsm->addScalarResult('フリー在庫数', 'フリー在庫数', 'integer');
    $rsm->addScalarResult('発注残数', '発注残数', 'integer');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setParameter(':indexId', $indexId, \PDO::PARAM_INT);

    $orders = [];
    $defaultOrders = [
        's.id' => 'ASC'
    ];
    $query->setOrders(array_merge($defaultOrders, $orders));

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    // 画面表示
    return $this->render('AppBundle:Goods:stock-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
    ]);
  }


  /**
   * 在庫確認画面 CSVダウンロード
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function stockListCsvDownloadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $account = $this->getLoginUser();
    $indexId = $account->getId();

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // データ取得
    $sql = <<<EOD
      SELECT
          s.ne_syohin_syohin_code
        , pci.daihyo_syohin_code
        , pci.`在庫数`
        , COALESCE(vs.`受注数`, 0) AS 受注数
        , pci.`引当数`
        , COALESCE(vs.`受注数`, 0) - pci.`引当数` AS 未引当数
        , pci.`ピッキング引当数`
        , pci.`出荷予定取置数`
        , pci.`フリー在庫数`
        , pci.`発注残数`
      FROM tb_stock_list_product s
      INNER JOIN tb_productchoiceitems pci ON s.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      LEFT JOIN v_product_stock_sales vs ON s.ne_syohin_syohin_code = vs.ne_syohin_syohin_code
      WHERE s.index_id = :indexId
      ORDER BY s.id ASC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':indexId', $indexId, \PDO::PARAM_INT);
    $stmt->execute();


    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');

    // ヘッダ
    $headers = [
        'ne_syohin_syohin_code' => 'ne_syohin_syohin_code'
      , 'daihyo_syohin_code'    => 'daihyo_syohin_code'
      , '在庫数'                 => '在庫数'
      , '受注数'                 => '受注数'
      , '引当数'                 => '引当数'
      , '未引当数'               => '未引当数'
      , 'ピッキング引当数'       => 'ピッキング引当数'
      , '出荷予定取置数'         => '出荷予定取置数'
      , 'フリー在庫数'           => 'フリー在庫数'
      , '受注メールアドレス'     => '受注メールアドレス'
      , '発注残数'               => '発注残数'
    ];

    $fileName = sprintf('stock_list_%s.csv', (new \DateTime())->format('YmdHis'));
    $exportFile = null;

    // StreamedResponse
    $exportFile = new \SplFileObject('php://output', 'w');
    $exportProcess = function () use ($stmt, $exportFile, $stringUtil, $headers)
    {
      $eol = "\r\n";

      // ヘッダ
      $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
      $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
      $exportFile->fwrite($header);

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

        $exportFile->fwrite($line);

        flush();
      }
    };

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback($exportProcess);

    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));

    $logger->info('csv output: response done!');

    return $response;
  }

  /**
   * 商品 レビュー一覧
   */
  public function listReviewAction(Request $request)
  {
    $account = $this->getLoginUser();

    // 画面表示
    return $this->render('AppBundle:Goods:list-review.html.twig', [
      'account' => $account,
    ]);
  }

  /**
   * 商品レビュー情報一覧 データ取得処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function findReviewListAction(Request $request)
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
      if (isset($conditions['date_from']) && strlen($conditions['date_from'])) {
        $conditions['date_from'] = (new \DateTimeImmutable($conditions['date_from']))->setTime(0, 0, 0);
      }
      if (isset($conditions['date_to']) && strlen($conditions['date_to'])) {
        $conditions['date_to'] = (new \DateTimeImmutable($conditions['date_to']))->setTime(0, 0, 0);
      }
      /** @var TbProductReviewsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductReviews');
      $pagination = $repo->findReviewList($conditions, $pageItemNum, $page);
      $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $list = [];
      // 全てのページの平均値を取得
      $allAverage = $repo->getAllAverage($conditions, $pagination->getTotalItemCount());
      foreach($pagination->getItems() as $row) {
        $row['image_url'] = TbMainproductsRepository::createImageUrl($row['image_dir'], $row['image_file'], $imageUrlParent);
        $list[] = $row;
      }
      $result['list'] = $list;
      $result['count'] = $pagination->getTotalItemCount();
      $result['allAverage'] = $allAverage['all_average'];
    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * yahooレビューCSVアップロード処理
   * @param Request $request リクエスト
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function yahooReviewCsvUploadAction(Request $request) {
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');

    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    $yahooSites = [ // サイト宣言。orderPrevix はCSV記載の注文コードプレフィックス
        'yahoo_plusnao' => ["siteId" => "12", "siteName" => "Yahoo plusnao", "orderPrefix" => 'plusnao']
        , 'kawaemon' => ["siteId" => "14", "siteName" => "kawa-e-mon", "orderPrefix" => 'kawa-e-mon']
        , 'otoriyose' => ["siteId" => "20", "siteName" => "おとりよせ.com", "orderPrefix" => 'mignonlindo']
    ];

    $filePath = ''; // アップロードファイルの保存パス（ファイル名を含む）
    $fileName = ''; // アップロードファイルの保存ファイル名（パスを含まない）
    try {
      $uploadedFile = null;
      $originalReviewIdList = array(); // 投稿元モールレビューIDリスト
      foreach ($request->files as $fp) {
        $uploadedFile = $fp;
      }

      if (!$uploadedFile) {
        $result['status'] = 'ng';
        $result['message'] = 'CSVファイルが正しくアップロードされませんでした。';
        return new JsonResponse($result);
      }

      /* CSVファイル保存 */
      $fs = new FileSystem();
      $saveDir = $fileUtil->getDataDir() . '/review/csv';
      if (!$fs->exists($saveDir)) {
        $fs->mkdir($saveDir, 0755);
      }
      $fileName = sprintf('review_yahoo_%s_%s.csv', (new \DateTime())->format('YmdHis'), getmypid());
      $filePath = $saveDir . '/' . $fileName;
      if ($fs->exists($filePath)) {
        $logger->error('CSVアップロード時にファイル名が重複しました：' . $filePath);
        $result['status'] = 'ng';
        $result['message'] = 'CSVファイルアップロードでエラーが発生しました。再度アップロードしてください';
        return new JsonResponse($result);
      }
      // 文字コード変換
      $fp = fopen($filePath, 'w+');
      $fileUtil->createConvertedCharsetTempFile($fp, $uploadedFile->getPathname(), 'SJIS-WIN', 'UTF-8');

      // ヘッダチェック
      if (fgetcsv($fp) != self::$CSV_FIELDS_REVIEW_CSV) {
        $result['status'] = 'ng';
        $result['message'] = 'CSVファイルの書式が違います';
        return new JsonResponse($result);
      }

      // 注文番号からサイトを特定
      $data1 = fgetcsv($fp);
      $orderPrefix = preg_match('/(.+)-[0-9]+/', $data1[4], $match);
      $targetSite = null;
      foreach ($yahooSites as $site) {
        if ($site['orderPrefix'] === $match[1]) {
          $targetSite = $site;
        }
      }
      if ($targetSite == null) {
        $result['status'] = 'ng';
        $message = "登録対象のYahooサイトが判別できません。CSV掲載の注文番号が不正です。注文番号[" . $data1[4] . "]";
        $result['message'] = $message;
        $logger->info($message);
        return new JsonResponse($result);
      }
      
      // データの絞り込み状態チェック　デフォルト表示は★1、★2のみなので、★3以上が含まれていない場合、絞り込みを修正し忘れている可能性がある。アラート表示
      // 1件目は取得済みなのでそこから処理、2件目以降は新たにループ
      $validScore = ($data1[1] > 2);
      while ($row = fgetcsv($fp)) {
        if ($row[1] > 2) {
          $validScore = true;
          break;
        }
      }
      $message = "";
      if (!$validScore) {
        $message = "CSVに★3以上のデータがありません。絞り込み条件に間違いはありませんか？\r\n\r\n";
      }
      
      // データの日付チェック。重複日がない場合アラートを提示
      /** @var TbProductReviewsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductReviews');
      $lastReview = $repo->getLastReviewByShop($targetSite['siteId']); // 登録済みのなかで最も新しいレビュー なければチェックしない
      if ($lastReview) {
        $lastReviewDate = $lastReview->getReviewDatetime();
        $lastReviewDate->setTime(0, 0, 0);
        $checkLine = $fileUtil->tail($filePath, 1, 4096);
        $row = explode(',', $checkLine);
        $fileFirstDate = new \DateTime($row[0]); // ファイル内で最も古いレビュー日
        if ($lastReviewDate < $fileFirstDate) {
          $message .= "登録済みデータと重複がありません。登録済み最新データの日付はこちらです。[" . $lastReviewDate->format('Y-m-d') ."]\r\n\r\n";
        }
      }
      
      $result['fileName'] = $fileName;
      $result['status'] = 'ok';
      $result['reviewSiteName'] = $targetSite['siteName'];
      $result['reviewSiteId'] = $targetSite['siteId'];
      $message .= 'アップロードファイルを、' . $targetSite['siteName'] . "のデータとして登録します。\r\nよろしいですか？";
      $result['message'] = $message;
    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = 'システムエラーが発生しました';
    }

    return new JsonResponse($result);
  }

  /**
   * YahooレビューCSVデータ登録処理
   * @param Request $request リクエスト
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function yahooReviewCsvRegisterAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try {
      $resque = $this->getResque();
      $job = new NonExclusiveJob();
      $job->queue = 'nonExclusive'; // キュー名
      $job->args = [
        'command'          => BaseJob::COMMAND_KEY_UPDATE_YAHOO_REVIEW
        , 'account'        => $this->getLoginUser()->getId()      // ログインユーザ
        ,'fileName'        => $request->get('fileName')
        , 'reviewSiteName' => $request->get('reviewSiteName')
        , 'reviewSiteId'   => $request->get('reviewSiteId')
        , 'account'        => $this->getLoginUser()->getId()
      ];
      // リトライ設定（なし）
      $resque->enqueue($job);

      // 結果
      $result['message'] .= 'Yahoo商品レビューCSVデータ登録処理をキューに追加しました。';
      return new JsonResponse($result);

    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = 'システムエラーが発生しました';

      return new JsonResponse($result);
    }
  }


  /**
   * @param integer $indexId
   * @return int
   */
  private function countStockListProducts($indexId)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = "SELECT COUNT(*) FROM tb_stock_list_product WHERE index_id = :indexId";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':indexId', $indexId, \PDO::PARAM_INT);
    $stmt->execute();
    return intval($stmt->fetchColumn(0));
  }




  /**
   * ロケーション在庫原価一覧
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function locationPurchasePriceListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $page = $request->get('page', 1);
    $limit = $request->get('limit', 100);
    $codeList = [];

    $account = $this->getLoginUser();

    $pagination = null;
    if ($request->isMethod(Request::METHOD_POST)) {

      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $tmpCodeList = explode("\n", str_replace("\r", "\n", str_replace("\r\n", "\n", $request->get('location_codes', ''))));
      foreach($tmpCodeList as $i => $code) {
        $code = preg_replace('/\\s+/', '', $code);
        if (!strlen($code)) {
          continue;
        }
        $codeList[] = $repo->fixLocationCode($code);
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $whereSql = "";
      $wheres = [];
      if ($codeList) {
        $codeListEscaped = [];
        foreach($codeList as $code) {
          $code = $dbMain->quote($code, \PDO::PARAM_STR);
          $codeListEscaped[] = $code;
        }

        $wheres[] = sprintf(" l.location_code IN ( %s ) ", implode(', ', $codeListEscaped));
      }
      if ($wheres) {
        foreach($wheres as $i => $where) {
          $wheres[$i] = sprintf(' ( %s ) ', $where);
        }
        $whereSql = ' AND ' . implode(' AND ', $wheres);
      }

      // データ取得
      $sqlSelect = <<<EOD
      SELECT
          w.name AS warehouse_name
        , l.location_code
        , T.purchase_price
        , T.stock
EOD;
      $sqlBody = <<<EOD
      FROM (
        SELECT
            pl.location_id
          , l.warehouse_id
          , SUM(
              COALESCE(v.baika_genka, 0) * pl.stock
            ) AS purchase_price
          , SUM(pl.stock ) AS stock
        FROM tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
        INNER JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        LEFT JOIN v_product_price_base v ON pci.daihyo_syohin_code = v.daihyo_syohin_code
        WHERE 1
          {$whereSql}
        GROUP BY l.warehouse_id
               , pl.location_id
      ) T
      INNER JOIN tb_location l ON T.location_id = l.id
      INNER JOIN tb_warehouse w ON T.warehouse_id = w.id
EOD;

      /** @var EntityManager $em */
      $em = $this->getDoctrine()->getManager('main');

      $rsm = new ResultSetMappingBuilder($em);
      $rsm->addScalarResult('warehouse_name', 'warehouse_name', 'string');
      $rsm->addScalarResult('location_code', 'location_code', 'string');
      $rsm->addScalarResult('purchase_price', 'purchase_price', 'integer');
      $rsm->addScalarResult('stock', 'stock', 'integer');

      $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
      // $query->setParameter(':indexId', $indexId, \PDO::PARAM_INT);

      $orders = [];
      $defaultOrders = [
          'w.id' => 'ASC'
        , 'l.location_code' => 'ASC'
      ];
      $query->setOrders(array_merge($defaultOrders, $orders));

      /** @var \Knp\Component\Pager\Paginator $paginator */
      $paginator  = $this->get('knp_paginator');
      /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
      $pagination = $paginator->paginate(
          $query /* query NOT result */
        , $page
        , $limit
      );
    }

    // 画面表示
    return $this->render('AppBundle:Goods:location-purchase-price-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'pagination' => $pagination
      , 'codeList' => $codeList
    ]);
  }



  /**
   * 商品画像 操作画面
   * @param string $daihyoSyohinCode
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function imageEditAction($daihyoSyohinCode)
  {
    /** @var TbMainproductsRepository $repoProduct */
    $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $product = $repoProduct->find($daihyoSyohinCode);

    /** @var ProductImagesRepository $repoImages */
    $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');

    $data = [];
    $images = $repoImages->findProductImages($daihyoSyohinCode);

    /** @var ImageUtil $imageUtil */
    $imageUtil = $this->get('misc.util.image');

    foreach($images as $image) {
      $row = $image->toScalarArray('camel');
      $row['fileDirPath'] = $image->getFileDirPath();

      $info = $imageUtil->getImageSize($image);
      $row['size'] = $info['size'];
      $row['width'] = $info['width'];
      $row['height'] = $info['height'];

      $data[] = $row;
    }

    // 画面表示
    return $this->render('AppBundle:Goods:image-edit.html.twig', [
        'account' => $this->getLoginUser()
      , 'product' => $product
      , 'imageUrlParent' => sprintf('//%s/images/', $this->getParameter('host_plusnao'))
      , 'dataJson' => json_encode($data)
    ]);
  }

  /**
   * 商品画像 操作画面 保存処理
   * @param Request $request
   * @param string $daihyoSyohinCode
   * @return JsonResponse
   */
  public function imageSaveAction(Request $request, $daihyoSyohinCode)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var TbMainproductsRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbMainproducts $product */
      $product = $repoProduct->find($daihyoSyohinCode);
      if (!$product) {
        throw new \RuntimeException('商品データが取得できませんでした。');
      }

      $delete = $request->get('delete', []);
      $move = $request->get('move', []);
      $upload = $request->files->get('upload');

      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->get('misc.util.image');
      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');

      $em = $this->getDoctrine()->getManager('main');

      $imageDir = $this->getParameter('product_image_dir');
      $originalImageDir = $this->getParameter('product_image_original_dir');

      /** @var ProductImagesRepository $repoImages */
      $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      $images = $repoImages->findProductImages($daihyoSyohinCode);

      // 削除
      if ($delete) {
        $minCode = min($delete);
        foreach ($delete as $code) {
          foreach ($images as $image) {
            if ($image->getCode() === $code) {
              $imageUtil->deleteImage($image);
              $em->remove($image);
            }
          }
        }
        // 画像の繰り上げに対応。整合性が無くなるので、
        // 削除対象とそれより後の画像に紐づくサムネイル画像を全て削除。
        foreach ($images as $image) {
          if ($image->getCode() >= $minCode) {
            $imageUtil->deleteThumbnailImage($image);
          }
        }
      }
      $em->flush();

      // 再取得
      foreach($images as $image) {
        $em->detach($image);
      }
      $images = $repoImages->findProductImages($daihyoSyohinCode);

      // 移動
      // この処理は、画像の差し替えを画像アップロードだけで済ませる（すなわち、CSV出力を待たない）ためにのみほぼ実装されている。
      // （ファイル名によるモール表示管理）
      // モールの更新は常にCSVの更新とセットを前提とすれば、ファイルそれ自体固有のファイル名によるデータの更新のみが望ましい。はず。

      // 同名ファイルの上書きを避けるため、一時ファイルに全て移動し、それから改めて移動。
      // また、コードが主キーであるため、UPDATEでの更新は不可。一度削除して挿入。
      if ($move) {
        $fs = new Filesystem();
        $cacheDir = $fileUtil->getCacheDir();
        $now = new \DateTime();
        $tmpDir = sprintf('%s/%s/tmp_image', $cacheDir, $now->format('YmdHis'));
        $tmpDirOriginal = sprintf('%s/%s/tmp_image_original', $cacheDir, $now->format('YmdHis'));

        $fs->mkdir($tmpDir);
        $fs->mkdir($tmpDirOriginal);

        $tmpPathList = [];
        $tmpPathListOriginal = [];

        /** @var ProductImages[] $oldImages */
        $oldImages = [];
        /** @var ProductImages[] $newImages */
        $newImages = [];

        /** @var ProductImages[] $imageList */
        $imageList = [];
        foreach($images as $image) {
          $imageList[$image->getCode()] = $image;
        }

        foreach($move as $code => $newCode) {
          // 一時ファイルへ変換
          $image = isset($imageList[$code]) ? $imageList[$code] : null;
          if (!$image) {
            throw new \RuntimeException('該当コードの画像が見つかりません。' . $code);
          }

          // 加工済み・オリジナル共通
          $newFileName = $imageUtil->createMainImageFilename($image->getDaihyoSyohinCode(), $newCode);

          // 画像の移動（加工済み） => 一時
          $path = sprintf('%s/%s', $imageDir, $image->getFileDirPath());
          $tmpPath = sprintf('%s/%s', $tmpDir, $newFileName);
          if ($fs->exists($path)) {
            $file = new File($path);
            $file->move($tmpDir, $newFileName);
            $tmpPathList[$code] = $tmpPath;
          }

          // 画像の移動（オリジナル） => 一時
          $path = sprintf('%s/%s', $originalImageDir, $image->getFileDirPath());
          $tmpPath = sprintf('%s/%s', $tmpDirOriginal, $newFileName);
          if ($fs->exists($path)) {
            $file = new File($path);
            $file->move($tmpDirOriginal, $newFileName);
            $tmpPathListOriginal[$code] = $tmpPath;
          }

          $oldImages[] = $image;
        }

        foreach($move as $code => $newCode) {
          $image = isset($imageList[$code]) ? $imageList[$code] : null;
          if (!$image) {
            throw new \RuntimeException('該当コードの画像が見つかりません。' . $code);
          }

          $tmpFile = null;
          $tmpFileOriginal = null;

          if (isset($tmpPathList[$code])) {
            $tmpPath = $tmpPathList[$code];
            $tmpFile = new File($tmpPath);
            if (!$tmpFile || !$fs->exists($tmpPath)) {
              throw new \RuntimeException('一時ファイルが見つかりません。処理を終了します。[' . $tmpFile . ']');
            }
          } else {
            $logger->warning('一時ファイルが作成されていません。イレギュラーです。' . $code);
          }

          if (isset($tmpPathListOriginal[$code])) {
            $tmpPathOriginal = $tmpPathListOriginal[$code];
            $tmpFileOriginal = new File($tmpPathOriginal);
            if (!$tmpDirOriginal || !$fs->exists($tmpPathOriginal)) {
              throw new \RuntimeException('一時ファイル(オリジナル)が見つかりません。処理を終了します。[' . $tmpPathOriginal . ']');
            }
          } else {
            $logger->warning('一時ファイル（オリジナル）が作成されていません。イレギュラーです。' . $code);
          }

          $newImage = new ProductImages();
          $newImage->setDaihyoSyohinCode($image->getDaihyoSyohinCode());
          $newImage->setCode($newCode);
          $newImage->setFilename($tmpFile->getBasename());
          $newImage->setDirectory($image->getDirectory());
          $newImage->setAddress(str_replace($image->getFilename(), $tmpFile->getBasename(), $image->getAddress()));
          $newImage->setPhash($image->getPhash());
          $newImage->setUpdated(new \DateTime());
          $newImage->setCreated($image->getCreated());

          $newImages[$code] = $newImage;

          // 画像の移動（加工済み）
          $dir = sprintf('%s/%s', $imageDir, $newImage->getDirectory());
          $tmpFile->move($dir, $newImage->getFilename());

          // 画像の移動（オリジナル）
          $dir = sprintf('%s/%s', $originalImageDir, $newImage->getDirectory());
          $tmpFileOriginal->move($dir, $newImage->getFilename());

          $newFileFullPath = sprintf('%s/%s/%s', $originalImageDir, $newImage->getDirectory(), $newImage->getFilename());
          $newImage->setMd5hash(hash_file('md5', $newFileFullPath));

          // サムネイル画像ファイル削除
          $imageUtil->deleteThumbnailImage($image);
        }

        foreach($oldImages as $image) {
          $em->remove($image);
        }
        $em->flush();

        foreach($newImages as $image) {
          $em->persist($image);
        }

        $em->flush();
      }

      // 新規登録
      if ($upload) {
        $newDir = $imageUtil->findAvailableImageDirectory();
        foreach($upload as $code => $file) {
          // レコードの新規作成
          $productImage = new ProductImages();
          $productImage->setDaihyoSyohinCode($daihyoSyohinCode);
          $productImage->setCode($code);
          $em->persist($productImage);

          $productImage->setDirectory($newDir);
          $productImage->setFilename($imageUtil->createMainImageFilename($daihyoSyohinCode, $code));
          $productImage->setAddress(sprintf('https://image.rakuten.co.jp/plusnao/cabinet/%s', $productImage->getFileDirPath())); // 暫定処理
          $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

          // 画像ファイルの（上書き）保存
          $originalFilePath = $imageUtil->saveUploadedProductImageToOriginal($productImage, $file);
          if (!$originalFilePath) {
            throw new \RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getDirectory() . '/' . $productImage->getFilename() );
          }

          // 画像ファイルの加工処理
          $imageUtil->convertOriginalFileToFixedFile($originalFilePath);

          // オリジナル画像でmd5取得
          $productImage->setMd5hash(hash_file('md5', $originalFilePath));

          // 類似画像チェック用 文字列作成・格納（上書き） → なし
        }
        $em->flush();
      }

      // 再取得
      foreach($images as $image) {
        $em->detach($image);
      }
      $images = $repoImages->findProductImages($daihyoSyohinCode);

      // 1～9は mainproducts のデータも更新する。
      for ($i = 1; $i <= 9; $i++) {
        $code = sprintf('p%03d', $i);
        $found = false;
        $image = null;
        foreach($images as $image) {
          if ($image->getCode() == $code) {
            $found = true;
            break;
          }
        }

        $product->setImageFieldData('address'  , $code, ($found ? $image->getAddress()   : null));
        $product->setImageFieldData('directory', $code, ($found ? $image->getDirectory() : null));
        $product->setImageFieldData('filename' , $code, ($found ? $image->getFilename()  : null));
      }
      $em->flush();

      $result['message'] = '画像データを保存しました。';

    } catch (\Exception $e) {
      $logger->error("画像保存でエラー発生：" . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品画像 全更新処理（最終更新日時の更新）
   * @param $daihyoSyohinCode
   * @return JsonResponse
   */
  public function imageTouchAllAction($daihyoSyohinCode)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    $logger->info('商品画像全更新処理 : ' . $daihyoSyohinCode);

    try {

      /** @var TbMainproductsRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbMainproducts $product */
      $product = $repoProduct->find($daihyoSyohinCode);
      if (!$product) {
        throw new \RuntimeException('商品データが取得できませんでした。');
      }

      $now = new \DateTime();

      /** @var ProductImagesRepository $imageRepo */
      $imageRepo = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      $imageRepo->updateUpdated($daihyoSyohinCode, $now);

      $result['message'] = sprintf('画像の最終更新日時を %s に更新しました。', $now->format('Y-m-d H:i:s'));

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }



  /**
   * SKU別 重量・サイズ 編集画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function weightSizeEditAction(Request $request)
  {
    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.next_engine');

    // 画面表示
    return $this->render('AppBundle:Goods:weight-size-edit.html.twig', [
        'account' => $this->getLoginUser()
      , 'code' => $request->get('code')
      , 'wsLimits' => $mallProcess->getSkuWeightSizeLimits()
      , 'listDesc' => $mallProcess->getListDesc()
    ]);
  }
  /**
   * SKU別 重量・サイズ バーコード遷移
   * @param String $barcode
   * @return Response
   */
  public function weightSizeBarcodeAction($barcode)
  {
    if (preg_match('/^\d{13}$/', $barcode)) {
      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $choice = $repo->findByProductCode($barcode);

      if ($choice) {
        return $this->redirectToRoute('goods_weight_size_edit', ['code' => $choice->getNeSyohinSyohinCode()]);
      }
    }

    // 見つからなかった
    return $this->redirectToRoute('goods_weight_size_edit'); // 特になにもしない
  }


  /**
   * SKU別 重量・サイズ データ取得処理 (Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function weightSizeGetAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      $code = $request->get('code');
      if (!$code) {
        throw new \RuntimeException('コードが指定されていません。');
      }

      /** @var TbMainproductsRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      // まずバーコード or 商品コードで取得
      /** @var TbMainproducts $product */
      $product = null;
      /** @var TbProductchoiceitems $choice */
      $choice = null;
      if (preg_match('/^1000\d{9}$/', $code)) {
        $choice = $repoChoice->findByProductCode($code);
      }
      if (!$choice) {
        $choice = $repoChoice->find($code);
      }
      if ($choice) {
        $product = $choice->getProduct();
      } else {
        $product = $repoProduct->find($code);
      }
      if (!$product) {
        throw new \RuntimeException('商品データが取得できませんでした。[' . $code . ']');
      }

      $imageUrl = sprintf('//%s/images', $this->getParameter('host_plusnao'));
      /** @var ProductImagesRepository $repo */
      $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      /** @var ProductImages[] $images */
      $images = $repoImages->findBy(['daihyo_syohin_code' => $product->getDaihyoSyohinCode()], ['code' => 'ASC']);

      $result['product'] = [
          'daihyoSyohinCode' => $product->getDaihyoSyohinCode()
        , 'image' => $images ? TbMainproductsRepository::createImageUrl($images[0]->getDirectory(), $images[0]->getFilename(), $imageUrl) : ''
      ];
      $result['choice'] = $choice ? $choice->toScalarArray() : null;
      $result['choices'] = [];
      foreach($product->getChoiceItems() as $item) {
        $result['choices'][] = $item->toScalarArray('camel');
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
   * SKU別 重量・サイズ データ更新処理 (Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function weightSizeUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $skuList = $request->get('skuList', []);

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $choices = [];

      foreach($skuList as $sku) {
        /** @var TbProductchoiceitems $choice */
        $choice = $repoChoice->find($sku['neSyohinSyohinCode']);
        if ($choice) {
          $choice->setWeight($sku['weight']);
          $choice->setDepth($sku['depth']);
          $choice->setWidth($sku['width']);
          $choice->setHeight($sku['height']);
          $choice->setDescriptionEn($sku['descriptionEn']);
          $choice->setDescriptionCn($sku['descriptionCn']);
          $choice->setHintJa($sku['hintJa']);
          $choice->setHintCn($sku['hintCn']);
        }
        $choices[] = $choice;
      }

      if (!$choices) {
        throw new \RuntimeException('更新データが送信されませんでした。');
      }

      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      $result['message'] = sprintf('%d 件の重量・サイズを更新しました。', count($choices));

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * SKU別 重量・サイズ データ更新処理 (Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function weightSizeLimitSetAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    try {
      $wsLimits = $request->get('wsLimits', []);
      $keys = [
          ['ws_limits_weight_ubound','weight_ubound']
        , ['ws_limits_weight_lbound','weight_lbound']
        , ['ws_limits_side1_ubound','side1_ubound']
        , ['ws_limits_side2_ubound','side2_ubound']
        , ['ws_limits_side3_ubound','side3_ubound']
        , ['ws_limits_sides_ubound','sides_ubound']
        , ['ws_limits_weight_aubound','weight_aubound']
      ] ;
      $em = $this->getDoctrine()->getManager('main');
      $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      foreach($keys as $key) {
        $setting = $settingRepo->findOneBy(['settingKey'=>$key[0]]);
        $setting->setSettingVal($wsLimits[$key[1]]);
      }
      $em->flush();
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }



  /**
   * 倉庫在庫一覧画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function warehouseStockListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $page = $request->get('page', 1);
    $limit = $request->get('limit', 100);

    $account = $this->getLoginUser();

    $pagination = null;
    $warehouseStockKeys = [];
    $conditions = [
        's' => $request->get('s')
      , 'c' => $request->get('c') // 商品コード
      , 'm' => $request->get('m', 0) // 在庫数下限
    ];

    // 倉庫一覧取得
    /** @var TbWarehouseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbWarehouse[] $warehouses */
    $warehouses = $repo->getPullDownObjects();
    foreach($warehouses as $warehouse) {
      $warehouseStockKeys[$warehouse->getId()] = sprintf('stock_%d', $warehouse->getId());
    }


    // データ取得
    if ($request->get('s')) {
      $query = $this->getWarehouseStockListQuery($warehouseStockKeys, $conditions);

      /** @var \Knp\Component\Pager\Paginator $paginator */
      $paginator  = $this->get('knp_paginator');
      /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
      $pagination = $paginator->paginate(
          $query /* query NOT result */
        , $page
        , $limit
      );
    }

    // 画面表示
    return $this->render('AppBundle:Goods:warehouse-stock-list.html.twig', [
        'account' => $account
      , 'pagination' => $pagination
      , 'warehouses' => $warehouses
      , 'stockKeys' => $warehouseStockKeys
      , 'conditions' => $conditions
    ]);
  }

  /**
   * 倉庫在庫一覧画面 CSVダウンロード
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function warehouseStockListCsvDownloadAction(Request $request)
  {
    ini_set("memory_limit", "512M");

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $account = $this->getLoginUser();
    $pagination = null;
    $warehouseStockKeys = [];
    $conditions = [
        's' => $request->get('s')
      , 'c' => $request->get('c') // 商品コード
      , 'm' => $request->get('m', 0) // 在庫数下限
    ];

    // 倉庫一覧取得
    /** @var TbWarehouseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var TbWarehouse[] $warehouses */
    $warehouses = $repo->getPullDownObjects();
    foreach($warehouses as $warehouse) {
      $warehouseStockKeys[$warehouse->getId()] = sprintf('stock_%d', $warehouse->getId());
    }

    // データ取得
    $query = $this->getWarehouseStockListQuery($warehouseStockKeys, $conditions);
    $stmt = $query->select();

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');

    // ヘッダ
    $headers = [
        'ne_syohin_syohin_code' => 'ne_syohin_syohin_code'
      , 'daihyo_syohin_code'    => 'daihyo_syohin_code'
      , '在庫数'                 => '在庫数'
      , 'フリー在庫数'           => 'フリー在庫数'
      , '発注残数'               => '発注残数'
      , '受注数'                 => '受注数'
      , '引当数'                 => '引当数'
      , 'ピッキング引当数'       => 'ピッキング引当数'
      , '出荷予定取置数'         => '出荷予定取置数'
      , '移動中在庫数'           => '移動中在庫数'
      , '販売不可在庫数'         => '販売不可在庫数'
      , '総在庫数'               => '総在庫数'
    ];

    foreach($warehouseStockKeys as $id => $key) {
      $warehouse = $warehouses[$id];
      $headers[$key] = $warehouse->getName();
    }

    $fileName = sprintf('warehouse_stock_list_%s.csv', (new \DateTime())->format('YmdHis'));
    $exportFile = null;

    // StreamedResponse
    $exportFile = new \SplFileObject('php://output', 'w');
    $exportProcess = function () use ($stmt, $exportFile, $stringUtil, $headers)
    {
      $eol = "\r\n";

      // ヘッダ
      $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
      $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
      $exportFile->fwrite($header);

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

        $exportFile->fwrite($line);

        flush();
      }
    };

    // StreamedResponse
    $response = new StreamedResponse();
    $response->setCallback($exportProcess);

    $response->headers->set('Content-type', 'application/octet-stream');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));

    $logger->info('csv output: response done!');

    return $response;
  }

  /**
   * @param array $warehouseStockKeys
   * @param array $conditions
   * @return LimitableNativeQuery
   */
  private function getWarehouseStockListQuery($warehouseStockKeys, $conditions = [])
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');

    // 倉庫毎の在庫
    $warehouseStockSelects = [];
    $warehouseStockJoins = [];
    $joinFormat = <<<EOD
      LEFT JOIN (
        SELECT
            w.id AS warehouse_id
          , pl.ne_syohin_syohin_code
          , SUM(pl.stock) AS stock
        FROM tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
        INNER JOIN tb_warehouse w ON l.warehouse_id = w.id
        WHERE w.id = %d
        GROUP BY w.id, w.name, pl.ne_syohin_syohin_code
      ) %s ON pci.ne_syohin_syohin_code = %s.ne_syohin_syohin_code
EOD;

    foreach($warehouseStockKeys as $id => $key) {
      $alias = sprintf('W%d', $id);
      $warehouseStockKeys[] = $key;
      $warehouseStockSelects[] = sprintf('COALESCE(%s.stock, 0) AS %s', $alias, $key);
      $warehouseStockJoins[] = sprintf($joinFormat, $id, $alias, $alias);
    }
    $warehouseStockSelectsStr = implode("\n, ", $warehouseStockSelects);
    $warehouseStockJoinsStr = implode("\n", $warehouseStockJoins);

    $sqlSelect = <<<EOD
      SELECT
          pci.ne_syohin_syohin_code
        , pci.daihyo_syohin_code
        , pci.在庫数
        , pci.受注数
        , pci.引当数
        , pci.ピッキング引当数
        , pci.出荷予定取置数
        , pci.移動中在庫数
        , pci.販売不可在庫数
        , pci.発注残数
        , pci.総在庫数
        ,  (pci.在庫数 + (pci.出荷予定取置数 - pci.ピッキング引当数) - pci.受注数 - pci.販売不可在庫数) as フリー在庫数
        , {$warehouseStockSelectsStr}
EOD;
    $sqlBody = <<<EOD
      FROM tb_productchoiceitems pci
      {$warehouseStockJoinsStr}
      WHERE 1
EOD;
    if (strlen($conditions['c'])) {
      $sqlBody .= sprintf("  AND pci.ne_syohin_syohin_code LIKE %s", $dbMain->quote('%' . $commonUtil->escapeLikeString($conditions['c']) . '%', \PDO::PARAM_STR));
    }
    if (strlen($conditions['m']) && boolval($conditions['m'])) {
      $sqlBody .= sprintf(" AND pci.総在庫数 >= %d", intval($conditions['m']));
    }

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');

    $rsm = new ResultSetMappingBuilder($em);
    $rsm->addScalarResult('ne_syohin_syohin_code', 'ne_syohin_syohin_code', 'string');
    $rsm->addScalarResult('daihyo_syohin_code', 'daihyo_syohin_code', 'string');
    $rsm->addScalarResult('在庫数', '在庫数', 'integer');
    $rsm->addScalarResult('受注数', '受注数', 'integer');
    $rsm->addScalarResult('引当数', '引当数', 'integer');
    $rsm->addScalarResult('ピッキング引当数', 'ピッキング引当数', 'integer');
    $rsm->addScalarResult('出荷予定取置数', '出荷予定取置数', 'integer');
    $rsm->addScalarResult('移動中在庫数', '移動中在庫数', 'integer');
    $rsm->addScalarResult('販売不可在庫数', '販売不可在庫数', 'integer');
    $rsm->addScalarResult('発注残数', '発注残数', 'integer');
    $rsm->addScalarResult('総在庫数', '総在庫数', 'integer');
    $rsm->addScalarResult('フリー在庫数', 'フリー在庫数', 'integer');
    foreach($warehouseStockKeys as $key) {
      $rsm->addScalarResult($key, $key, 'integer');
    }

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);

    $orders = [];
    $defaultOrders = [
      'pci.ne_syohin_syohin_code' => 'ASC'
    ];
    $query->setOrders(array_merge($defaultOrders, $orders));

    return $query;
  }

  /**
   * DESCRIPTION保存処理
   * 重複チェック
   * ・英語表記、中国語表記両方の重複はエラーとする
   * ・英語表記、中国語表記いずれかの重複は、更新されていない登録済データ以外はエラーとする
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function updateDescAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    try {
      $data = $request->get('data', []);
      $data = json_decode($data, true);
      $em = $this->getDoctrine()->getManager('main');
      /** @var TbSkuDescriptionsRepository $descRepo */
      $descRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSkuDescriptions');
      $updateData = [];
      foreach($data as $item) {
        $en = trim($item['description_en']);
        $cn = trim($item['description_cn']);
        if (
          /*
            #223977 何故こんなに様々な型を考慮しないといけないのか非常に疑問だが、
            ログを埋め込んだ結果、真偽値もあるようなので追加。影響箇所の調査が面倒なのでひとまず他は触らない。
          */
          $item['description_delete_flg'] === true
          || $item['description_delete_flg'] === 'true'
          || $item['description_delete_flg'] === '1'
          || ($en === '' && $cn === '')) {
          continue;
        }
        // 画面入力内容の重複数
        $matchList = array_filter($data, function($description) use ($en , $cn) {
          return trim($description['description_en']) === $en && trim($description['description_cn']) === $cn && ($description['description_delete_flg'] === 'false' || $description['description_delete_flg'] === '0');
        });

        // DBの重複数
        $dbSamecount = $descRepo->getSameDescriptionCount($item['id'], trim($item['description_en']),trim($item['description_cn']));
        if (count($matchList) > 1 || $dbSamecount > 0) {
          $result['status'] = 'ng';
          $result['message'] = 'description_en:'.trim($item['description_en']).'　description_cn:'.trim($item['description_cn']).'　は重複しているため処理できません。';
          return new JsonResponse($result);
        }
        $updateData[] = [
          'id' => $item['id']
          , 'description_en' => $en
          , 'description_cn' => $cn
        ];
      }
      $enSameMessage = [];
      $cnSameMessage = [];
      $descriptionEnCountList = array_count_values(array_column($updateData,'description_en'));
      $descriptionCnCountList = array_count_values(array_column($updateData,'description_cn'));
      foreach($updateData as $item) {
        $itemDesc = $descRepo->find($item['id']);
        $sameEn = strcmp($item['description_en'],trim($itemDesc->getDescriptionEn()));
        $sameCn = strcmp($item['description_cn'],trim($itemDesc->getDescriptionCn()));
        // DESCRIPTION英語表記の重複チェック
        if ($sameEn != 0) {
          // 入力内容変更または新規登録の場合のみチェック
          $enSameCount = $descRepo->getEnSameDescriptionCount($item['id'],$item['description_en']);
          if ($enSameCount > 0 || $descriptionEnCountList[$item['description_en']] > 1) {
            // DBに既に登録済み、または画面入力内容の重複時はエラーメッセージをセットする
            $enSameMessage[] =  '　'.$item['description_en'].'は登録済です';
          }
        }
        // DESCRIPTION中国語表記の重複チェック
        if ($sameCn != 0) {
          // 入力内容変更または新規登録の場合のみチェック
          $cnSameCount = $descRepo->getCnSameDescriptionCount($item['id'],$item['description_cn']);
          if ($cnSameCount > 0 || $descriptionCnCountList[$item['description_cn']] > 1) {
            // DBに既に登録済み、または画面入力内容の重複時はエラーメッセージをセットする
            $cnSameMessage[] = '　'.$item['description_cn'].'は登録済です';
          }
        }
      }
      if (count($enSameMessage) !== 0) {
        $result['message'] = "英語表記が重複しています。以下のデータが登録済みです\r\n". implode("\r\n", array_unique($enSameMessage))."\r\n";
      }
      if (count($cnSameMessage) !== 0) {
        $result['message'] = $result['message']."中国表記が重複しています。以下のデータが登録済みです\r\n". implode("\r\n", array_unique($cnSameMessage));
      }
      if ($result['message'] !== null) {
        $result['status'] = 'ng';
        return new JsonResponse($result);
      }
      foreach($data as $item) {
        $itemDesc = $descRepo->find($item['id']);
        $itemDesc->setDescriptionEn(trim($item['description_en']));
        $itemDesc->setDescriptionCn(trim($item['description_cn']));
        $itemDesc->setHintJa($item['hint_ja']);
        $itemDesc->setHintCn($item['hint_cn']);
        $itemDesc->setUpdated(new \DateTime());
      }
      $em->flush();
      $mallProcess = $this->get('batch.mall_process.next_engine');
      $result['listDesc'] = $mallProcess->getListDesc();
    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  public function createDescAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $result = [
          'status' => 'ok'
          , 'message' => null
      ];
      try {
          $new_desc = new TbSkuDescriptions();
          $em = $this->getDoctrine()->getManager('main');
          $new_desc->setDescriptionEn('');
          $new_desc->setDescriptionCn('');
          $new_desc->setHintJa('');
          $new_desc->setHintCn('');
          $new_desc->setCreated(new \DateTime());
          $new_desc->setUpdated(new \DateTime());
          $em->persist($new_desc);
          $em->flush();
          $result['data'] = $new_desc->getId();;
      } catch (\Exception $e) {
          $logger->error($e->getMessage());
          $logger->error($e->getTraceAsString());
          $result['status'] = 'ng';
          $result['message'] = $e->getMessage();
      }

      return new JsonResponse($result);
  }

  public function createDescriptionAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $result = [
          'status' => 'ok'
          , 'message' => null
      ];
      try {
          $new_desc = new TbSkuDescriptions();
          $em = $this->getDoctrine()->getManager('main');
          $new_desc->setDescriptionEn('');
          $new_desc->setDescriptionCn('');
          $new_desc->setHintJa('');
          $new_desc->setHintCn('');
          $new_desc->setDescriptionDeleteFlg(0);
          $new_desc->setHintDeleteFlg(1);
          $new_desc->setCreated(new \DateTime());
          $new_desc->setUpdated(new \DateTime());
          $em->persist($new_desc);
          $em->flush();
          $result['data'] = $new_desc->getId();;
      } catch (\Exception $e) {
          $logger->error($e->getMessage());
          $logger->error($e->getTraceAsString());
          $result['status'] = 'ng';
          $result['message'] = $e->getMessage();
      }

      return new JsonResponse($result);
  }

    public function createHintAction(Request $request)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');
        $result = [
            'status' => 'ok'
            , 'message' => null
        ];
        try {
            $new_desc = new TbSkuDescriptions();
            $em = $this->getDoctrine()->getManager('main');
            $new_desc->setDescriptionEn('');
            $new_desc->setDescriptionCn('');
            $new_desc->setHintJa('');
            $new_desc->setHintCn('');
            $new_desc->setDescriptionDeleteFlg(1);
            $new_desc->setHintDeleteFlg(0);
            $new_desc->setCreated(new \DateTime());
            $new_desc->setUpdated(new \DateTime());
            $em->persist($new_desc);
            $em->flush();
            $result['data'] = $new_desc->getId();;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            $logger->error($e->getTraceAsString());
            $result['status'] = 'ng';
            $result['message'] = $e->getMessage();
        }

        return new JsonResponse($result);
    }

  public function deleteDescAction(Request $request)
  {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $result = [
          'status' => 'ok'
          , 'message' => null
      ];
      try {
          $data = $request->get('data', []);
          $em = $this->getDoctrine()->getManager('main');
          $descRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSkuDescriptions');
          $itemDesc = $descRepo->find($data['id']);
          $em->remove($itemDesc);
          $em->flush();
      } catch (\Exception $e) {
          $logger->error($e->getMessage());
          $logger->error($e->getTraceAsString());
          $result['status'] = 'ng';
          $result['message'] = $e->getMessage();
      }
      return new JsonResponse($result);
  }

    public function deleteDescriptionAction(Request $request)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');
        $result = [
            'status' => 'ok'
            , 'message' => null
        ];
        try {
            $data = $request->get('data', []);
            $em = $this->getDoctrine()->getManager('main');
            $descRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSkuDescriptions');
            $itemDesc = $descRepo->find($data['id']);
            $itemDesc->setDescriptionDeleteFlg(1);
            $em->persist($itemDesc);
            $em->flush();
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            $logger->error($e->getTraceAsString());
            $result['status'] = 'ng';
            $result['message'] = $e->getMessage();
        }
        return new JsonResponse($result);
    }

    public function deleteHintAction(Request $request)
    {
        /** @var BatchLogger $logger */
        $logger = $this->get('misc.util.batch_logger');
        $result = [
            'status' => 'ok'
            , 'message' => null
        ];
        try {
            $data = $request->get('data', []);
            $em = $this->getDoctrine()->getManager('main');
            $descRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSkuDescriptions');
            $itemDesc = $descRepo->find($data['id']);
            $itemDesc->setHintDeleteFlg(1);
            $em->persist($itemDesc);
            $em->flush();

        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            $logger->error($e->getTraceAsString());
            $result['status'] = 'ng';
            $result['message'] = $e->getMessage();
        }
        return new JsonResponse($result);
    }

  /**
   * 簡易商品登録画面
   * @param Request $request
   * @return Response
   */
  public function registerSimpleProductAction(Request $request)
  {
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var TbVendormasterdataRepository $vendorRepo */
    $data = array();
    $type = new TbMainproductsSimpleExhibitType();

    $vendorRepo = $this->getDoctrine()->getRepository('MiscBundle:TbVendormasterdata');
    $type->setSireCodeList($vendorRepo->fetchActiveSireData());
    $companyRepo = $this->getDoctrine()->getRepository('MiscBundle:TbCompany');
    $type->setCompanyList($companyRepo->getPullDown());

    $form = $this->createForm($type, $data, array('data_class' => null));

    // フォームデータ引き継ぎ用にここで取得
    $defaultSkuPart = [
      [
        'code' => ''
        , 'name' => ''
      ]
    ];

    $cols = $request->get('cols', $defaultSkuPart);
    $rows = $request->get('rows', $defaultSkuPart);

    $form->handleRequest($request);

    if ($form->isSubmitted()) {

      try {

        if ($form->isValid()) {
          /** @var EntityManager $em */
          $em = $this->getDoctrine()->getManager();

          /** @var \Doctrine\DBAL\Connection $dbMain */
          $dbMain = $em->getConnection();
          $dbMain->beginTransaction();

          /** @var TbMainproducts $product */
          $product = new TbMainproducts(); // 'data_class' => null　と　した為
          $product->setDaihyoSyohinCode($form->get('daihyoSyohinCode')->getData());
          $product->setDaihyoSyohinName($form->get('daihyo_syohin_name')->getData());
          $product->setGenkaTnk($form->get('genka_tnk')->getData());
          $product->setSireCode($form->get('sire_code')->getData());
          $product->setColTypeName($form->get('col_type_name')->getData());
          $product->setRowTypeName($form->get('row_type_name')->getData());
          $product->setCompanyCode($form->get('company')->getData());

          $logger->info($product->getCompanyCode());

          if (!preg_match('/^[a-z]{3,6}-[a-zA-Z0-9-]{1,13}$/', $product->getDaihyoSyohinCode())) {
            throw new \RuntimeException('代表商品コードは、任意の英小文字3~6文字で初めて重複のないように設定してください。(全17文字まで)');
          }

          if (!preg_match('/^.{5,17}$/', $product->getDaihyoSyohinCode())) {
            throw new \RuntimeException('代表商品コードは、全17文字以内で設定してください。');
          }

          $sdRepo =  $this->getDoctrine()->getRepository('MiscBundle:TbShippingdivision');
          $shippindivision = $sdRepo->find(DbCommonUtil::DELIVERY_TYPE_TAKUHAI_BETSU);
          $product->setSyohinKbn(10);
          $product->setNeDirectoryId('101801'); // TODO 確認
          $product->setYahooDirectoryId('1682'); // TODO 確認
          $product->setWeight(0);
          $product->setShippingdivision($shippindivision);

          if ($product->getColTypeName() == 'カラー') {
            $product->setColType('color');
          }elseif ($product->getColTypeName() == 'サイズ'){
            $product->setColType('size');
          }else{
            $product->setColType('');
          }

          if ($product->getRowTypeName() == 'カラー') {
            $product->setRowType('color');
          }elseif ($product->getRowTypeName() == 'サイズ'){
            $product->setRowType('size');
          }else{
            $product->setRowType('');
          }

          if ($product->getColTypeName() == 'カラー') {
            $product->setColorAxis('col');
          } else if ($product->getRowTypeName() == 'カラー') {
            $product->setColorAxis('row');
          }

          $product->setRegisteredDatetime(new \DateTime());
          $product->setSaleStartDate(null);

          if ($account) {
            $product->setPerson($account->getUsername());
          }

          $vendorRepo = $this->getDoctrine()->getRepository('MiscBundle:TbVendormasterdata');
          $vendor = $vendorRepo->find($product->getSireCode());
          if ($vendor) {
            $product->setVendor($vendor);
          }

          $cal = new TbMainproductsCal();
          $product->setCal($cal);
          $cal->setProduct($product);
          $cal->setAdultCheckStatus(TbMainproductsCal::ADULT_CHECK_STATUS_NONE); // 未審査
          $cal->setDeliverycode(TbMainproductsCal::DELIVERY_CODE_FINISHED);
          $cal->setDeliverycodePre(TbMainproductsCal::DELIVERY_CODE_FINISHED);
          $cal->setEndofavailability(new \DateTime());
          $cal->setGenkaTnkAve($product->getGenkaTnk());
          $cal->setWeightCheckNeedFlg(-1);

          $em->persist($product);
          $em->persist($cal);

          // 商品画像登録
          /** @var ImageUtil $imageUtil */
          $imageUtil = $this->get('misc.util.image');

          $imageCode = 'p001'; // メイン画像

          $productImage = new ProductImages();
          $productImage->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
          $productImage->setCode($imageCode);
          $em->persist($productImage);


          $imageDir = $imageUtil->findAvailableImageDirectory();
          $imageName = sprintf('%s.jpg', strtolower($product->getDaihyoSyohinCode()));
          $imageAddress = sprintf('https://image.rakuten.co.jp/plusnao/cabinet/%s/%s', $imageDir, $imageName);

          $productImage->setAddress($imageAddress);
          $productImage->setDirectory($imageDir);
          $productImage->setFilename($imageName);
          $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

          // 本体へも格納（後方互換）
          $product->setImageFieldData('address', 'p001', $imageAddress);
          $product->setImageFieldData('directory', 'p001', $imageDir);
          $product->setImageFieldData('filename', 'p001', $imageName);

          // 画像ファイルの（上書き）保存
          /** @var FileUtil $fileUtil */
          $fileUtil = $this->get('misc.util.file');
          $imageSource = sprintf('%s/product/no_image.jpg', $fileUtil->getDataDir());
          $tmpImage = new TmpProductImages();
          $tmpImage->setImage(file_get_contents($imageSource));
          $originalFilePath = $imageUtil->saveTmpProductImageToOriginal($productImage, $tmpImage);
          if (!$originalFilePath) {
            throw new \RuntimeException('ファイルの保存ができませんでした。 ' . $productImage->getDirectory() . '/' . $productImage->getFilename() );
          }

          // 画像ファイルの加工処理
          $imageUtil->convertOriginalFileToFixedFile($originalFilePath);

          // オリジナル画像でmd5取得
          $productImage->setMd5hash(hash_file('md5', $originalFilePath));

          // 類似画像チェック用 文字列作成・格納（上書き） → なし

          // SKU登録
          $logger->info(print_r($cols, true));
          $logger->info(print_r($rows, true));

          // チェック、整形および不要データ削除
          $fixedCols = [];
          foreach($cols as $col) {
            $code = $this->fixSkuCode($col['code']);
            $name = $this->fixSkuName($col['name']);
            if (strlen($code) && strlen($name)) {
              $fixedCols[$code] = $name;
            }
          }

          $fixedRows = [];
          foreach($rows as $row) {
            $code = $this->fixSkuCode($row['code']);
            $name = $this->fixSkuName($row['name']);
            if (strlen($code) && strlen($name)) {
              $fixedRows[$code] = $name;
            }
          }

          $logger->info(print_r($fixedCols, true));
          $logger->info(print_r($fixedRows, true));

          if (!$fixedCols || !$fixedRows) {
            throw new \RuntimeException('有効なSKUがありません。');
          }

          $displayOrder = 1;
          foreach($fixedCols as $colCode => $colName) {
            foreach($fixedRows as $rowCode => $rowName) {
              $choice = new TbProductchoiceitems();
              $choice->setProduct($product);
              $choice->setDaihyoSyohinCode($product->getDaihyoSyohinCode());
              $choice->setColcode('-' . $colCode);
              $choice->setColname($colName);
              $choice->setRowcode('-' . $rowCode);
              $choice->setRowname($rowName);
              $choice->setDisplayOrder($displayOrder++);
              $choice->setOrderEnabled(true); // ここが 1 でDBへ格納されてしまう。暫定的に、後から -1 で更新

              $syohinCode = sprintf('%s%s%s', $choice->getDaihyoSyohinCode(), $choice->getColcode(), $choice->getRowCode());
              $logger->info($syohinCode);
              $choice->setNeSyohinSyohinCode($syohinCode);

              $em->persist($choice);
            }
          }

          // 各モール information テーブル レコード登録
          $mallInformationTableList = array(
            'tb_amazoninfomation' => 'amazon_title' // amazon
          , 'tb_biddersinfomation' => 'bidders_title' // bidders
          , 'tb_qten_information' => 'q10_title' // q10
          , 'tb_rakuteninformation' => '楽天タイトル' // rakuten_plusnao
          , 'tb_rakuten_motto_information' => '楽天タイトル' // rakuten_motto
          , 'tb_rakuten_laforest_information' => '楽天タイトル' // rakuten_laforest
          , 'tb_rakuten_dolcissimo_information' => '楽天タイトル' // rakuten_dolcissimo
          , 'tb_rakuten_gekipla_information' => '楽天タイトル' // rakuten_gekipla
          , 'tb_cube_information' => 'title' // ec-cube
          , 'tb_yahoo_information' => 'yahoo_title' // plusnao_yahoo
          , 'tb_yahoo_kawa_information' => 'yahoo_title' // kawa_yahoo
          , 'tb_yahoo_otoriyose_information' => 'yahoo_title' // otoriyose_yahoo
          , 'tb_ss_information' => 'ss_title' // ss
          , 'tb_ppm_information' => 'ppm_title' // ppm
          , 'tb_shoplist_information' => 'title' // shoplist
          , 'tb_amazon_com_information' => '' // Amazon.com
          );

          // 商品は全て出品フラグOFFで登録　　
          foreach($mallInformationTableList as $table => $titleColumn) {

            if (!$titleColumn) {
              $format = <<<EOD
              INSERT INTO `%s` (
                  `daihyo_syohin_code`
                , `registration_flg`
              ) VALUES (
                  :daihyoSyohinCode
                , 0
              )
EOD;
              $sql = sprintf($format, $table);
              $stmt = $dbMain->prepare($sql);
              $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
              $stmt->execute();

            } else {
              // 商品は全て出品フラグOFFで登録
              $format = <<<EOD
              INSERT INTO `%s` (
                  `daihyo_syohin_code`
                , `registration_flg`
                , `%s`
              ) VALUES (
                  :daihyoSyohinCode
                , 0
                , :title
              )
EOD;
              $sql = sprintf($format, $table, $titleColumn);
              $stmt = $dbMain->prepare($sql);
              $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
              $stmt->bindValue(':title', $product->getDaihyoSyohinName());
              $stmt->execute();
            }

          }

          // シーズン設定。ORMでPK処理が上手くできなかったのでひとまず直接登録
          // DBのデフォルト値でレコード作成
          $sql = "INSERT INTO tb_product_season (daihyo_syohin_code) VALUES (:daihyoSyohinCode)";
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
          $stmt->execute();

          $em->flush();

          // 暫定対応。boolean 定義をしてしまったので、1 を -1 に強制置換
          $sql = <<<EOD
            UPDATE tb_productchoiceitems pci
            SET pci.受発注可能フラグ = -1
            WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
          $stmt->execute();

          $dbMain->commit();

          $this->addFlash(
            'success',
            '商品を簡易登録しました。[' . $product->getDaihyoSyohinCode() . ']'
          );

          return $this->redirectToRoute('goods_simple_product_register');

        } else {
          $this->addFlash(
            'warning',
            '入力エラーがあります。'
          );
        }

      } catch (\Exception $e) {
        $this->addFlash(
          'danger'
          , '商品登録ができませんでした。 (' . $e->getMessage() . ')'
        );

        if (isset($dbMain)) {
          $dbMain->rollback();
        }
      }

    }

    return $this->render('AppBundle:Goods:register-simple-product-exhibit.html.twig', [
      'account' => $account
      , 'form' => $form->createView()
      , 'skuDataJson' => json_encode([
        'cols' => $cols
        , 'rows' => $rows
      ])
    ]);
  }

  /**
   * 実店舗のものと同じ
   * SKU コード整形
   */
  private function fixSkuCode($code)
  {
    $code = mb_convert_kana($code, 'as', 'UTF-8');
    $code = preg_replace('/[^a-zA-Z0-9-]+/u', '', $code);

    return $code;
  }
  /**
   * 実店舗のものと同じ
   * SKU 名前整形
   */
  private function fixSkuName($name)
  {
    return preg_replace('/[\s　]+/u', '', $name);
  }

  public function fetchReplaceWordListAction(Request $request){
    $result = [
      'status'    => 'ok'
      , 'message' => null
      , 'list'    => ''
    ];
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $sql = "SELECT DISTINCT r.before_word, r.after_word FROM tb_stock_replace_word r";
      $stmt = $dbMain->query($sql);
      $stmt->execute();
      $result["list"] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
      $logger->error($e->getMessage());
    }
    return new JsonResponse($result);
  }

  /**
   * 商品CSVフォーマット　トップページ
   * @param Request $request
   * @return unknown
   */
  public function csvFormatAction(Request $request) {
    return $this->render('AppBundle:Goods:csv-format.html.twig', [
        'account' => $this->getLoginUser()
    ]);
  }

  /**
   * 商品CSVフォーマット：楽天 dl-select.csv ⇒ NE
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
   */
  public function csvFormatRakutenDlselectNeAction(Request $request) {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var UploadedFile $file */
    $uploadFile = $request->files->get('upload');

    try {
      if (is_null($uploadFile)) {
        throw new \RuntimeException('CSVファイルをアップロードしてください。');
      }
      $ext = strtolower($uploadFile->getClientOriginalExtension());
      if ($ext != 'csv') {
        throw new \RuntimeException('CSVファイルをアップロードしてください。');
      }
      // ヘッダチェック
      $fp = fopen($uploadFile->getPathname(), 'rb'); // アップロードファイルを読み込む
      $headerLine = fgetcsv($fp); // ヘッダ行のみ読み込む
      $headerLineUTF8 = array();
      foreach ($headerLine as $data) {
        $headerLineUTF8[] =  mb_convert_encoding($data, 'UTF-8', 'SJIS-WIN');
      }
      if ($headerLineUTF8 != self::$CSV_FIELDS_RAKUTEN_DL_SELECT_CSV) {
        throw new \RuntimeException('CSVファイルの書式が違います');
      }

      $response = new StreamedResponse();
      $response->setCallback(
          function () use ($uploadFile, $logger) {
            $outputFile = new \SplFileObject('php://output', 'w'); // レスポンス
            $fp = fopen($uploadFile->getPathname(), 'rb'); // アップロードファイルを読み込みなおし
            while ($line = fgets($fp)) {
              $data = explode(',', $line);
              if ($data[2] == '"s"' || $data[2] == 's') { // sの行はスキップ
                continue;
              }
              $outputFile->fwrite($line);
            }
            flush();
          }
          );
      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('inline; filename="%s";', $uploadFile->getClientOriginalName()));
      return $response;
    } catch (\Exception $e) {
      $this->setFlash('warning', $e->getMessage());
      return $this->render('AppBundle:Goods:csv-format.html.twig', [
          'account' => $this->getLoginUser()
      ]);
    }
  }

  private static $CSV_FIELDS_REVIEW_CSV = [
    '評価日'
    , '評価点数'
    , '商品名'
    , '商品コード'
    , '注文ID'
    , 'コメントタイトル'
    , 'コメント内容'
  ];

  private static $CSV_FIELDS_RAKUTEN_DL_SELECT_CSV = [
    '項目選択肢用コントロールカラム'
    , '商品管理番号（商品URL）'
    , '選択肢タイプ'
    , '項目選択肢項目名'
    , '項目選択肢'
    , '項目選択肢別在庫用横軸選択肢'
    , '項目選択肢別在庫用横軸選択肢子番号'
    , '項目選択肢別在庫用縦軸選択肢'
    , '項目選択肢別在庫用縦軸選択肢子番号'
    , '項目選択肢別在庫用取り寄せ可能表示'
    , '項目選択肢別在庫用在庫数'
    , '在庫戻しフラグ'
    , '在庫切れ時の注文受付'
    , '在庫あり時納期管理番号'
    , '在庫切れ時納期管理番号'
    , 'タグID'
    , '画像URL'
    , '項目選択肢選択必須'
  ];

  /**
   * 商品 縦横軸コード管理 初期表示
   */
  public function axisCodeIndexAction(Request $request)
  {
    return $this->render('AppBundle:Goods:axis-code.html.twig', array(
      'account' => $this->getLoginUser()
      , 'code' => $request->get('code')
    ));
  }

  /**
   * 商品 縦横軸コード管理 検索
   * @param Request $request
   */
  public function axisCodeSearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = array();

    try {
      $code = $request->get('code');
      $message = null;

      /** @var TbMainproductsRepository $tbMainproductsRepository */
      $tbMainproductsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbProductchoiceitemsRepository $tbProductchoiceitemsRepository */
      $tbProductchoiceitemsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      $mainResult = $tbMainproductsRepository->find($code);
      $colDataResult = $tbProductchoiceitemsRepository->findDistinctColDataByDaihyoSyohinCode($code);
      $rowDataResult = $tbProductchoiceitemsRepository->findDistinctRowDataByDaihyoSyohinCode($code);
      $usedColResult = $tbProductchoiceitemsRepository->findUsedColAxis($code);
      $usedRowResult = $tbProductchoiceitemsRepository->findUsedRowAxis($code);

      // 代表商品コード、商品商品、横軸項目名、縦軸項目名の取得
      if($mainResult == null) {
        $message[] = "指定された代表商品コードのSKU商品がありません";
      } else {
        $result['product'] = array(
          'daihyoSyohinCode' => $mainResult->getDaihyoSyohinCode()
          , 'daihyoSyohinName' => $mainResult->getDaihyoSyohinName()
          , 'rowTypeName' => $mainResult->getRowTypeName()
          , 'colTypeName' => $mainResult->getColTypeName()
          , 'isEnableColDelete' => false // 削除可能な横軸があるか
          , 'isEnableRowDelete' => false // 削除可能な縦軸があるか
        );
      }

      // 横軸項目の取得
      if ($colDataResult == null) {
        $message[] = "指定された代表商品コードのSKU商品がありません";
      } else {
        $result['product']['colList'] = $colDataResult;
        // 削除可能フラグをセット
        foreach ($result['product']['colList'] as &$data) {
          if (in_array($data['colcode'], $usedColResult)) {
            $data['enableDelFlg'] = false;
          } else {
            $data['enableDelFlg'] = true;
            $result['product']['isEnableColDelete'] = true;
          }
        }
        unset($data);
      }

      // 縦軸項目の取得
      if ($rowDataResult == null) {
        $message[] = "指定された代表商品コードのSKU商品がありません";
      } else {
        $result['product']['rowList'] = $rowDataResult;
        // 削除可能フラグをセット
        foreach ($result['product']['rowList'] as &$data) {
          if (in_array($data['rowcode'], $usedRowResult)) {
            $data['enableDelFlg'] = false;
          } else {
            $data['enableDelFlg'] = true;
            $result['product']['isEnableRowDelete'] = true;
          }
        }
        unset($data);
      }
      // 重複チェック
      $duplicateMessage = $this->validateDuplicateAxisCode($colDataResult, $rowDataResult);
      if ($duplicateMessage != null) {
        if($message == null) {
          $message = $duplicateMessage;
        } else {
          $message = array_merge($message, $duplicateMessage);
        }
      }
      if ($message != null) {
        $result['message'] = implode("\r\n", array_unique($message));
      }
      $result['status'] = 'ok';

    } catch (\Exception $e) {
      $logger->error("縦横軸コード検索でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品 縦横軸コード管理 更新
   * @param Request $request
   */
  public function axisCodeUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
    ];

    try {
      $message = null;
      /** @var TbProductchoiceitemsRepository $repository */
      $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $isUpdatable = true;
      $colList = $request->get('colList');
      $rowList = $request->get('rowList');
      $daihyoSyohinCode = $request->get('daihyoSyohinCode');
      // 重複しているコードがある場合アップデートしない
      // 重複している場合、更新ボタンを表示しないようにしているが念のため
      $message = $this->validateDuplicateAxisCode($colList, $rowList);
      if ($message != null) {
        $isUpdatable = false;
        $result['message'] = implode("\r\n", array_unique($message));
      }

      // 更新処理
      if ($isUpdatable) {
        foreach ($colList as $col) {
          // 横軸更新
          $repository->updateColAxisValue($col);
        }
        foreach ($rowList as $row) {
          // 縦軸更新
          $repository->updateRowAxisValue($row);
        }
        $repository->insertNewSkuNumber($daihyoSyohinCode);
        $result['message'] = '更新を実行しました';
      } else {
        $result['status'] = 'ng';
      }
    } catch (\Exception $e) {
      $logger->error("縦横軸コード更新でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品 縦横軸コード管理 追加
   * @param Request $request
   */
  public function axisCodeInsertAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
    ];
    $logger->debug("axisCodeInsertAction");

    try {
      $message = null;
      /** @var TbProductchoiceitemsRepository $repository */
      $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      // $isUpdatable = true;
      $colList = $request->get('colList');
      $rowList = $request->get('rowList');

      // 追加処理
      if (!empty($colList)){
        foreach ($colList as $col) {
          // 横軸更新
          $repository->insertColAxisValue($col);
        }
      }
      if (!empty($rowList)){
        foreach ($rowList as $row) {
          // 縦軸更新
          $repository->insertRowAxisValue($row);
        }
      }
      $result['message'] = '軸追加を実行しました';
    } catch (\Exception $e) {
      $logger->error("縦横軸コード追加でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品 縦横軸コード管理 削除
   * @param Request $request
   */
  public function axisCodeDeleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
    ];

    try {
      /** @var TbProductchoiceitemsRepository $repository */
      $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      // $isUpdatable = true;
      $col = $request->get('col');
      $row = $request->get('row');

      if (!empty($col)) {
        $repository->deleteColAxisValue($col);
      } else {
        $repository->deleteRowAxisValue($row);
      }
    } catch (BusinessException $e) {
      $logger->error("総在庫数、エア発注残あるいはコンテナ発注残があるため、軸コード削除が失敗しました");
      $result['status'] = 'ng';
      $result['message'] = '総在庫数、エア発注残あるいはコンテナ発注残があるため、軸コード削除が失敗しました';
    } catch (\Exception $e) {
      $logger->error("縦横軸コード削除でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 商品 縦横軸コード管理 並び順 更新
   * @param Request $request
   */
  public function axisCodeOrderUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
    ];

    try {
      /** @var TbProductchoiceitemsRepository $repository */
      $repository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $axes = $request->get('axes');

      // 並び順更新
      $repository->updateAxisOrderValue($axes);
      $result['message'] = '更新を実行しました';
    } catch (\Exception $e) {
      $logger->error("縦横軸コード並び順更新でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

    /**
   * 商品 商品売上推移
   * @param string $daihyoSyohinCode
   * @return Response
   */
  public function analyzeSalesTransitionAction($daihyoSyohinCode)
  {
    $account = $this->getLoginUser();
    $productData['code'] = $daihyoSyohinCode;

    /** @var TbMainProductsRepository $mRepo */
    $mRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $mainProduct = $mRepo->find($daihyoSyohinCode);

    if (!$mainProduct) {
      return $this->render('AppBundle:Goods:analyze-sales-transition.html.twig', [
        'account' => $account,
        'productData' => $productData,
      ]);
    }

    $productData['name'] = $mainProduct->getDaihyoSyohinName();

    /** @var TbSalesDetailSummaryItemYmRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailSummaryItemYm');
    $salesTransition = $repo->findBy(
      ['daihyoSyohinCode' => $daihyoSyohinCode],
      ['orderYM' => 'DESC']
    );

    $productData['salesTransition'] = [];
    foreach ($salesTransition as $record) {
      /** @var TbSalesDetailSummaryItemYm $record */
      $productData['salesTransition'][] = [
        'orderYm' => $record->getOrderYM(),
        'voucherQuantity' => $record->getVoucherQuantity(),
        'orderQuantity' => $record->getOrderQuantity(),
        'detailAmountIncludingCost' => $record->getDetailAmountIncludingCost(),
        'detailAmount' => $record->getDetailAmount(),
        'detailGrossProfit' => $record->getDetailGrossProfit(),
        'additionalAmount' => $record->getAdditionalAmount(),
        'subtractionAmount' => $record->getSubtractionAmount(),
      ];
    }

    // 画面表示
    return $this->render('AppBundle:Goods:analyze-sales-transition.html.twig', [
      'account' => $account,
      'productData' => $productData,
    ]);
  }

  /**
   * 重複している項目がないか判断する
   * @param array $array
   * @return boolean true：重複がない, false：重複がある
   */
  private function isUniqueArray($array)
  {
    $unique_array = array_unique($array);
    return count($unique_array) === count($array);
  }

  /**
   * コードの重複チェック
   * @param array $calList
   * @param array $rowList
   * @return array エラーメッセージを返す
   */
  private function validateDuplicateAxisCode($calList, $rowList)
  {
    $message = null;
    // 横軸コードの重複チェック
    if ($calList != null) {
      $colcodeList = array_column($calList, 'colcode');
      if (!$this->isUniqueArray($colcodeList)) {
        $message[] = "同じ横軸コード値で、横軸項目名・横軸項目英名・横軸補助項目名の値が異なるものがあるため、編集できません";
      }
    }

    // 縦軸コードの重複チェック
    if ($rowList != null) {
      $rowcodeList = array_column($rowList, 'rowcode');
      if (!$this->isUniqueArray($rowcodeList)){
        $message[] = "同じ縦軸コード値で、縦軸項目名・縦軸項目英名・縦軸補助項目名の値が異なるものがあるため、編集できません";
      }
    }

    return $message;
  }

  /**
   * 商品画像削除　フォルダ一覧画面表示Action
   * @param Request $request
   */
  public function imageDeleteFoldersAction(Request $request) {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:Goods:image-delete-folders.html.twig', [
        'account' => $account,
    ]);
  }

  /**
   * 商品画像フォルダ取得Action（ajax）
   * @param Request $request
   */
  public function imageDeleteFoldersFindAction(Request $request) {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'message' => null
        , 'list' => []
        , 'count' => 0
    ];

    $page = $request->get('page', 1);
    $pageItemNum = $request->get('limit', 100);
    $conditions = $request->get('conditions', []);

    /** @var RakutenService $service */
    $service = $this->get('misc.service.rakuten');

    $foldersResult = $service->getCabinetFoldersByApi($page, $pageItemNum);

    $list = [];
    foreach ($foldersResult->folders->folder as $folder) {
      $list[] = [
        'FolderId' => (string)$folder->FolderId,
        'FolderName' => (string)$folder->FolderName,
        'FolderPath' => (string)$folder->FolderPath,
        'FileCount' => (string)$folder->FileCount,
        'TimeStamp' => (string)$folder->TimeStamp,
        'url' => $this->generateUrl('goods_image_delete_files', [
          'folderId' => $folder->FolderId,
        ]),
      ];
    }

    $result['list'] = $list;
    $result['count'] = (int)$foldersResult->folderAllCount;

    return new JsonResponse($result);
  }

  /**
   * 商品画像削除　フォルダ内ファイル一覧画面表示Action
   * @param Request $request
   */
  public function imageDeleteFilesAction(Request $request, $folderId) {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:Goods:image-delete-files.html.twig', [
        'folderId' => $folderId,
        'account' => $account,
    ]);
  }

  /**
   * 商品画像フォルダ内ファイルデータ取得Action（ajax）
   * @param Request $request
   */
  public function imageDeleteFilesFindAction(Request $request) {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'message' => null
        , 'list' => []
        , 'count' => 0
    ];

    $folderId = $request->get('folderId');
    $page = $request->get('page', 1);
    $pageItemNum = $request->get('limit', 100);
    $conditions = $request->get('conditions', []);

    /** @var RakutenService $service */
    $service = $this->get('misc.service.rakuten');
    /** @var ProductImagesVariationRepository $productImageRepository */
    $repoImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');

    $filesResult = $service->getCabinetFolderFilesByApi($folderId, $page, $pageItemNum);

    $list = [];
    foreach ($filesResult->files->file as $file) {
      $list[] = [
        'FolderId' => (string)$file->FolderId,
        'FolderName' => (string)$file->FolderName,
        'FolderPath' => (string)$file->FolderPath,
        'FileId' => (string)$file->FileId,
        'FileName' => (string)$file->FileName,
        'FileUrl' => (string)$file->FileUrl,
        'FilePath' => (string)$file->FilePath,
        'FileAccessDate' => (string)$file->FileAccessDate,
        'TimeStamp' => (string)$file->TimeStamp,
      ];
    }

    $filenames = $repoImages->getExistFileNamesFromArray($list);

    foreach ($list as $index => $file) {
      $list[$index]['ExistsOnDb'] = in_array($file['FilePath'], $filenames);
    }

    $result['list'] = $list;
    $result['count'] = (int)$filesResult->fileAllCount;

    return new JsonResponse($result);
  }

  /**
   * 商品画像フォルダ内ファイルデータ削除Action（ajax）
   * @param Request $request
   */
  public function imageDeleteFilesDeleteAction(Request $request) {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'message' => null
    ];

    $fileId = $request->get('fileId');

    /** @var RakutenService $service */
    $service = $this->get('misc.service.rakuten');

    for ($i = 0; $i < count($fileId) ; $i ++) {
      $now = microtime();
      if ($i > 0) {
        $rap = $now - $time; usleep(1000000 - $rap); $time = $now;
      }

      $filesResult = $service->deleteCabinetFilesByApi($fileId[$i]);
    }

    return new JsonResponse($result);
  }

  /**
   * アテンション画像一覧表示Action
   * @param Request $request
   */
  public function attentionImageListAction(Request $request) {
    $account = $this->getLoginUser();
    return $this->render('AppBundle:Goods:attention-image-list.html.twig', [
        'account' => $account,
    ]);
  }

  /**
   * アテンション画像データ取得Action（ajax）
   * @param Request $request
   */
  public function attentionImageFindAction(Request $request) {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
        'status' => 'ok'
        , 'message' => null
        , 'list' => []
        , 'count' => 0
    ];

    $page = $request->get('page', 1);
    $pageItemNum = $request->get('limit', 100);
    $conditions = $request->get('conditions', []);

    /** @var ProductImagesAttentionImageRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesAttentionImage');
    $pagination = $repo->findAttentionImageList($conditions, $pageItemNum, $page);

    $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
    $list = [];
    foreach($pagination->getItems() as $row) {
      $splitImagePath = explode('/', $row['image_path']);
      $row['image_url'] = TbMainproductsRepository::createImageUrl($splitImagePath[0], $splitImagePath[1], $imageUrlParent);
      $list[] = $row;
    }
    $result['list'] = $list;
    $result['count'] = $pagination->getTotalItemCount();

    return new JsonResponse($result);
  }

  /**
   * アテンション画像データ更新Action（ajax）
   * @param Request $request
   */
  public function attentionImageUpdateAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();
    $attentionImageRepository = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesAttentionImage');
    $result = [
        'status' => 'ok'
        , 'message' => '更新を実行しました'
    ];

    // 送信データを元に更新を実行
    $data = json_decode($request->getContent(), true);
    $attentionFlgs = array_column($data, 'attentionFlg', 'md5hash'); // idをキー、反映ステータスを値とした連想配列
    $attentionImageRepository->updateAttentionFlg($attentionFlgs);

    return new JsonResponse($result);
  }

  /**
   * 商品情報・仕入備考 初期表示
   */
  public function goodsInfoIndexAction(Request $request)
  {
    return $this->render('AppBundle:Goods:info.html.twig', array(
      'account' => $this->getLoginUser()
      , 'code' => $request->get('code')
    ));
  }

  /**
   * 商品情報・仕入備考 検索
   * @param Request $request
   */
  public function goodsInfoSearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = array();

    try {
      $code = $request->get('code');
      $message = [];

      /** @var TbMainproductsRepository $tbMainproductsRepository */
      $tbMainproductsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $mainResult = $tbMainproductsRepository->find($code);
      
      /** @var TbMainproductsCalRepository $tbMainproductsCalRepository */
      $tbMainproductsCalRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproductsCal');
      $mainCalResult = $tbMainproductsCalRepository->find($code);

      // 代表商品コード、商品商品、横軸項目名、縦軸項目名の取得
      if($mainResult == null) {
        $message[] = "指定された代表商品コードのSKU商品がありません";
      } else {
        $result['product'] = array(
          'daihyoSyohinCode' => $mainResult->getDaihyoSyohinCode()
          , 'daihyoSyohinName' => $mainResult->getDaihyoSyohinName()
          , 'description' => $mainResult->getDescription()
          , 'aboutSize' => $mainResult->getAboutSize()
          , 'aboutColor' => $mainResult->getAboutColor()
          , 'aboutMaterial' => $mainResult->getAboutMaterial()
          , 'aboutBrand' => $mainResult->getAboutBrand()
          , 'usageNote' => $mainResult->getUsageNote()
          , 'supplementalExplanation' => $mainResult->getSupplementalExplanation()
          , 'shortDescription' => $mainResult->getShortDescription()
          , 'shortSupplementalExplanation' => $mainResult->getShortSupplementalExplanation()
          , 'sireDescription' => $mainResult->getOrderComment()
          , 'memo' => $mainCalResult->getMemo()
        );
      }

      if ($message != null) {
        $result['message'] = implode("\r\n", array_unique($message));
      }
      $result['status'] = 'ok';

    } catch (\Exception $e) {
      $logger->error("商品情報・仕入備考検索でエラー発生: $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 商品情報・仕入備考 更新
   * @param Request $request
   */
  public function goodsInfoUpdateAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
    ];

    try {

      $em = $this->getDoctrine()->getManager('main');

      $code = $request->get('code');

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      /** @var TbMainproducts $product */
      $product = $repo->find($code);

      $product->setDescription($request->get('description', null));
      $product->setAboutSize($request->get('aboutSize', null));
      $product->setAboutMaterial($request->get('aboutMaterial', null));
      $product->setAboutColor($request->get('aboutColor', null));
      $product->setAboutBrand($request->get('aboutBrand', null));
      $product->setUsageNote($request->get('usageNote', null));
      $product->setSupplementalExplanation($request->get('supplementalExplanation', null));
      $product->setShortDescription($request->get('shortDescription', null));
      $product->setShortSupplementalExplanation($request->get('shortSupplementalExplanation', null));
      $product->setOrderComment($request->get('sireDescription', null));
      
      $tbMainproductsCalRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproductsCal');
      /** @var TbMainproductsCal $mainCal */
      $mainCal = $tbMainproductsCalRepository->find($code);
      $mainCal->setMemo($request->get('memo', null));

      $em->flush();

      $result['message'] = "商品情報・仕入備考を更新しました";

    } catch (\Exception $e) {
      $logger->error("商品情報・仕入備考更新でエラー発生： $e");
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }
}
