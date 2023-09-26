<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbSetProductCreateListRepository;
use MiscBundle\Entity\Repository\TbSetProductPickingListRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductLocation;
use MiscBundle\Entity\TbSetProductCreateDetail;
use MiscBundle\Entity\TbSetProductCreateList;
use MiscBundle\Entity\TbSetProductDetail;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * セット商品関連
 * @package AppBundle\Controller
 */
class SetProductController extends BaseController
{
  /**
   * セット商品一覧
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function listAction()
  {
    $account = $this->getLoginUser();

    // 存在しない tb_set_product_sku レコードについて、自動作成
    // Accessで増減した時など、存在しない可能性あり。
    // triggerにするには、mainproducts.set_flg を見なければならず、そちらも微妙。
    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $repo->createEmptySetProductSkuRecords();

    // 画面表示
    return $this->render('AppBundle:SetProduct:list.html.twig', [
        'account' => $account
    ]);
  }

  /**
   * セット商品一覧 データ取得処理(Ajax)
   * @return JsonResponse
   */
  public function findListAction()
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

      /** @var TbMainproductsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

      // セット商品関連情報のクリーンアップ（タイミングがここで悪ければ、日時処理などへ移動する。）
      $repo->clearInvalidSetDetailRecords();

      $data = $repo->getAllSetProductList();

      $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
      $list = [];
      foreach($data as $row) {
        $row['image_url'] = TbMainproductsRepository::createImageUrl($row['image_dir'], $row['image_file'], $imageUrlParent);
        $list[] = $row;
      }

      $result['list'] = $list;
      $result['count'] = count($data);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 詳細画面 表示
   * @param string $daihyoSyohinCode
   * @return Response
   */
  public function detailAction($daihyoSyohinCode)
  {
    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    /** @var TbMainproducts $product */
    $product = $repo->find($daihyoSyohinCode);

    $imageUrlParent = sprintf('//%s/images/', $this->getParameter('host_plusnao'));
    $imageUrl = $product->getImageUrl($imageUrlParent);

    // 在庫設定数 取得
    $requiredStocks = $repo->getSetRequiredStocks($product);

    // 画面表示
    return $this->render('AppBundle:SetProduct:detail.html.twig', [
        'account' => $this->getLoginUser()
      , 'product' => $product
      , 'requiredStocks' => $requiredStocks
      , 'imageUrl' => $imageUrl
    ]);
  }

  /**
   * セット商品 SKU内訳設定 一覧取得
   * @param $syohinCode
   * @return JsonResponse
   */
  public function getSkuDetailAction($syohinCode)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'list' => []
      , 'count' => 0
      , 'freeStock' => 0
      , 'orderNum' => 0
    ];

    try {

      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems $choice */
      $choice = $repo->find($syohinCode);

      if (!$choice) {
        throw new \RuntimeException('no productchoiceitems');
      }

      /** @var TbMainproductsRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $product = $repoProduct->find($choice->getDaihyoSyohinCode());
      if (!$product) {
        throw new \RuntimeException('not set product');
      }

      // 登録済みSKU内訳一覧取得
      // 縦軸名・横軸名を取得したいために、Doctrineのリレーション機能は使わない。
      $details = $repoProduct->getSetProductSkuDetails($choice);

      // キャメルケースへ変換
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      foreach($details as $i => $tmp) {
        $detail = [];

        foreach($tmp as $k => $v) {
          $k = $stringUtil->convertToCamelCase($k);
          $detail[$k] = $v;
        }

        $details[$i] = $detail;
      }

      $result['list'] = $details;
      $result['count'] = count($details);
      $result['freeStock'] = $choice->getFreeStock();
      $result['orderNum'] = $repo->getActiveOrderNum($choice);

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * SKU内訳 一括更新
   * @param Request $request
   * @return JsonResponse
   */
  public function updateSkuDetailAction(Request $request)
  {
    // , $setSyohinCode, $detailSyohinCode

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $setSyohinCode = $request->get('setSyohinCode');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'list' => []
    ];

    try {

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      /** @var TbProductchoiceitems $choice */
      $choice = $repoChoice->find($setSyohinCode);

      if (!$choice) {
        throw new \RuntimeException('商品SKUが見つかりませんでした。');
      }

      $logger->info($choice->getNeSyohinSyohinCode());

      /** @var TbMainproductsRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $product = $repoProduct->find($choice->getDaihyoSyohinCode());
      if (!$product) {
        throw new \RuntimeException('セット商品ではありません。');
      }

      $list = $request->get('list', []);
      $logger->info(print_r($list, true));

      // 空データをスキップ
      $tmp = $list;
      $list = [];
      foreach($tmp as $row) {
        if (empty($row) || empty($row['neSyohinSyohinCode'])) {
          continue;
        }

        $list[] = $row;
      }

      // 重複チェック
      $duplicateCheck = [];
      foreach($list as $row) {
        if (isset($duplicateCheck[$row['neSyohinSyohinCode']])) {
          throw new \RuntimeException('商品コードが重複しています。');
        }
        $duplicateCheck[$row['neSyohinSyohinCode']] = true;
      }

      // 登録済みSKU内訳一覧取得
      /** @var EntityManager $em */
      $em = $this->getDoctrine()->getManager('main');

      $em->beginTransaction(); // ------------------------------- トランザクション


      // 全て削除
      $repoProduct->deleteSetProductSkuDetails($choice);

      // 全て挿入
      $logger->info('set code : ' . $setSyohinCode);
      $count = 0;
      foreach($list as $row) {
        $logger->info('detail code : ' . $row['neSyohinSyohinCode']);

        // 自分自身は不可
        if ($row['neSyohinSyohinCode'] == $choice->getNeSyohinSyohinCode()) {
          throw new \RuntimeException('自身をセットの内訳にはできません。 ' . $row['neSyohinSyohinCode']);
        }

        // SKU存在確認
        /** @var TbProductchoiceitems $detailChoice */
        $detailChoice = $repoChoice->find($row['neSyohinSyohinCode']);
        if (!$detailChoice) {
          throw new \RuntimeException('存在しない商品コードです。 ' . $row['neSyohinSyohinCode']);
        }

        // セットSKUは不可（セットの入れ子は複雑すぎる）
        if ($detailChoice->isSetSku()) {
          throw new \RuntimeException('セット商品はセットの内訳に設定できません。 ' . $detailChoice->getNeSyohinSyohinCode());
        }

        // 件数がない場合にはスルー
        if (empty($row['num'])) {
          continue;
        }

        $targetDetail = new TbSetProductDetail();
        $targetDetail->setSetNeSyohinSyohinCode($setSyohinCode);
        $targetDetail->setNeSyohinSyohinCode($row['neSyohinSyohinCode']);
        $targetDetail->setNum($row['num']);
        $targetDetail->setChoiceItem($choice); // これをしないと外部キーをNULLで強制（上書き）登録しようとする。Doctrine2...

        $em->persist($targetDetail);

        $count++;
      }

      $em->flush();
      $em->refresh($choice);

      $em->commit(); // ------------------------------- トランザクション

      $result['list'] = [];
      /** @var TbSetProductDetail[] $resultList */
      $resultList = $repoProduct->getSetProductSkuDetails($choice);
      foreach($resultList as $target) {
        $result['list'][] = $target;
      }

      $logger->info(print_r($result, true));

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      if (isset($em)) {
        $logger->debug('rollback!');
        $em->rollback();
      }

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * セット商品 作成対象一覧画面
   */
  public function requiredListAction()
  {
    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbSetProductCreateListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductCreateList');
    $products = $repo->getRequiredList($currentWarehouse);

    // 画面表示
    return $this->render('AppBundle:SetProduct:required-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'productsJson' => json_encode($products)
    ]);
  }

  /**
   * セット商品 作成リスト追加処理 (Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function addCreateListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      $list = $request->get('list', []);

      if (!$list) {
        throw new \RuntimeException('データが送信されませんでした。');
      }

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      // データを取得し直し、作成可能数の範囲かチェック。

      /** @var TbSetProductCreateListRepository $repoList */
      $repoList = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductCreateList');

      $products = $repoList->getRequiredList($currentWarehouse);

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      $today = new \DateTime();

      $dbMain->beginTransaction();
      $newNumber = $repoList->getMaxNumber($dbMain, $today) + 1;

      foreach($list as $row) {
        $hit = false;
        foreach($products as $product) {
          if ($product['set_sku'] === $row['setSku']) {
            $logger->info($row['setSku']);

            if ($row['createNum'] > $product['creatable_num']) {
              throw new \RuntimeException(sprintf('%s は現在 %d 個までしか作成できません。データを再読込してください。', $row['setSku'], $product['creatable_num']));
            }

            $createList = new TbSetProductCreateList();
            $createList->setDate($today);
            $createList->setNumber($newNumber);
            $createList->setSetSku($row['setSku']);
            $createList->setRequiredNum($product['required_stock']);
            $createList->setCreatableNum($product['creatable_num']);
            $createList->setCreateNum($row['createNum']);

            $em->persist($createList);
            $hit = true;
            break;
          }
        }

        if (!$hit) {
          throw new \RuntimeException(sprintf('%s の作成情報が取得できませんでした。', $row['setSku']));
        }
      }

      $em->flush();

      // 内訳詳細テーブル レコード作成
      $sql = <<<EOD
        INSERT INTO tb_set_product_create_detail (
            list_id
          , detail_sku
          , detail_free_stock
          , detail_num
          , create_num
          , picking_num
        )
        SELECT
            l.id AS list_id
          , d.ne_syohin_syohin_code AS detail_sku
          , pci.`フリー在庫数` AS detail_free_stock
          , d.num AS detail_num
          , l.create_num
          , l.create_num * d.num AS picking_num
        FROM tb_set_product_create_list l
        INNER JOIN tb_set_product_detail d ON l.set_sku = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci ON d.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        WHERE l.date = :date
          AND l.number = :number
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $today->format('Y-m-d'));
      $stmt->bindValue(':number', $newNumber);
      $stmt->execute();

      // 在庫数量 不足チェック
      $stockShortageList = $repoList->getFreeStockShortageList($today, $newNumber);
      if ($stockShortageList) {
        $shortages = [];
        foreach ($stockShortageList as $shortage) {
          $shortages[] = sprintf('%s : %d / %d', $shortage['ne_syohin_syohin_code'], $shortage['picking_num_total'], $shortage['free_stock']);
        }

        throw new \RuntimeException("下記フリー在庫が不足しています。\n\n" . implode("\n", $shortages));
      }

      // ピッキングリスト 作成
      // トリガの関係でtb_productchoiceitemsを参照できないので、そちらは後で更新
      $sql = <<<EOD
        REPLACE INTO tb_set_product_picking_list (
            `date`
          , number
          , ne_syohin_syohin_code
          , free_stock
          , ordered_num
          , move_num
        )
        SELECT
            l.date
          , l.number
          , d.detail_sku AS ne_syohin_syohin_code
          , 0 AS free_stock
          , SUM(d.picking_num) AS ordered_num
          , SUM(d.picking_num) AS move_num
        FROM tb_set_product_create_list l
        INNER JOIN tb_set_product_create_detail d ON l.id = d.list_id
        WHERE l.date = :date
          AND l.number = :number
        GROUP BY d.detail_sku
        ORDER BY d.detail_sku
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $today->format('Y-m-d'));
      $stmt->bindValue(':number', $newNumber);
      $stmt->execute();

      // ピッキングリストレコードのフリー在庫数・画像パスを補完
      $sql = <<<EOD
        UPDATE tb_set_product_picking_list pl
        INNER JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        INNER JOIN product_images i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
                                   AND i.code = 'p001'
        SET pl.free_stock = (pci.フリー在庫数 + pl.move_num) /* すでにトリガで減ってしまった自分自身の分を加算 */
          , pl.pict_directory = i.directory
          , pl.pict_filename = i.filename
        WHERE pl.date = :date
          AND pl.number = :number
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $today->format('Y-m-d'));
      $stmt->bindValue(':number', $newNumber);
      $stmt->execute();

      // ピッキングリスト ロケーション並び順 初期更新
      // ※本来は作業開始時に「ロケーション更新」してもらう想定だが、よく抜かされるので、せめて最初に1回やっておく
      /** @var TbSetProductPickingListRepository $repoPicking */
      $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductPickingList');
      $repoPicking->refreshLocation($today->format('Y-m-d'), $newNumber, $currentWarehouse);

      $dbMain->commit();

      $result['message'] = sprintf('選択された%d商品の作成リストを追加しました。 [No: %d]', count($list), $newNumber);
      $this->setFlash('success', $result['message']); // redirect用

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }

  /**
   * 作成リスト セット商品作成確定
   * @param string $date
   * @param int $number
   * @return JsonResponse
   */
  public function submitCreateListAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $account = $this->getLoginUser();

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbSetProductCreateListRepository $repoCreateList */
      $repoCreateList = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductCreateList');
      /** @var TbSetProductPickingListRepository $repoPicking */
      $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductPickingList');
      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      // 対象倉庫 => 一旦固定。
      //    ※ ログインアカウントの選択倉庫の直接利用は、切り替え忘れの事故などがありうるので、改修時には注意。
      /** @var TbWarehouseRepository $repoWarehouse */
      $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
      /** @var TbWarehouse $currentWarehouse */
      $currentWarehouse = $repoWarehouse->find(TbWarehouseRepository::DEFAULT_WAREHOUSE_ID);

      // トランザクション開始
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      // ピッキング済み商品一覧取得
      $pickedItems = $repoPicking->getPickedStocksTotal($date, $number);

      // 作成リスト一覧
      $createList = $repoCreateList->findListWithDetails($date, $number);

      $newLocation = null;
      $ok = [];
      $ng = [];
      /** @var TbProductLocation[] $remains */
      $remains = [];

      // 1件ずつ在庫を割り当てて作成
      foreach($createList as $item) {
        /** @var TbSetProductCreateList $list */
        $list = $repoCreateList->find($item['id']);
        if (!$list) {
          throw new \RuntimeException('作成リストデータが見つかりませんでした。処理を中断します。 [' . $item['id'] . ']');
        }

        // 在庫数をチェック
        $valid = true;
        $stockList = [];
        /** @var TbSetProductCreateDetail $detail */
        foreach($item['details'] as $detail) {
          $detailSku = $detail->getDetailSku();
          // 残りピッキング済み在庫数が足りない場合、作成を中止して次の商品へスキップ。（status : skipped）
          if (!isset($pickedItems[$detailSku]) || $pickedItems[$detailSku] < $detail->getPickingNum()) {
            $valid = false;
            break;
          }

          $stockList[$detailSku] = $detail->getPickingNum(); // 減算用に保持。
        }

        // 作成中止＆スキップ
        if (!$valid) {
          $list->setStatus(TbSetProductCreateListRepository::CREATE_LIST_STATUS_SKIPPED);
          $ng[] = $list;
          continue; // 次の商品へ
        }

        // 材料在庫を減算
        foreach($stockList as $detailSku => $num) {
          $pickedItems[$detailSku] -= $num;
        }

        // セット商品ロケーション作成
        if (!$newLocation) {
          $newLocation = $repoLocation->createAutoLocation('ne', 'NE_SET_', $currentWarehouse);
          if (!$newLocation) {
            throw new \RuntimeException('新規ロケーションの作成に失敗しました。');
          }
        }

        $productLocation = new TbProductLocation();
        $productLocation->setNeSyohinSyohinCode($list->getSetSku());
        $productLocation->setLocation($newLocation);
        $productLocation->setLocationId($newLocation->getId());
        $productLocation->setStock($list->getCreateNum());
        $productLocation->setPosition(999998); // MAXくらいで

        $choice = $repoChoice->find($list->getSetSku());
        if (!$choice) {
          throw new \RuntimeException('SKUデータが見つかりませんでした。処理を中断します。 [' . $list->getSetSku() . ']');
        }
        $productLocation->setChoiceItem($choice); // これをしないとDoctrineがエラー (-o-ﾒ)

        $em->persist($productLocation);

        $list->setStatus(TbSetProductCreateListRepository::CREATE_LIST_STATUS_CREATED);
        $ok[] = $list;
      }

      // もし、ピッキング済みで利用されていない商品があれば、残り物ロケーションを作成して全て登録
      $newLocationRemains = null;
      foreach($pickedItems as $detailSku => $stock) {
        if ($stock > 0) {
          if (!$newLocationRemains) {
            $newLocationRemains = $repoLocation->createAutoLocation('ne', 'NE_SRE_', $currentWarehouse);
            if (!$newLocationRemains) {
              throw new \RuntimeException('新規ロケーション（残り物格納）の作成に失敗しました。');
            }
          }

          $productLocation = new TbProductLocation();
          $productLocation->setNeSyohinSyohinCode($detailSku);
          $productLocation->setLocation($newLocationRemains);
          $productLocation->setLocationId($newLocationRemains->getId());
          $productLocation->setStock($stock);
          $productLocation->setPosition(999999); // MAXで

          $choice = $repoChoice->find($detailSku);
          if (!$choice) {
            throw new \RuntimeException('SKUデータが見つかりませんでした。処理を中断します。 [' . $detailSku . ']');
          }
          $productLocation->setChoiceItem($choice); // これをしないとDoctrineがエラー (-o-ﾒ)
          $em->persist($productLocation);

          $remains[] = $productLocation;
        }
      }

      // ロケーション優先順位振り直し
      foreach($ok as $list) {
        $repoLocation->renumberPositions($list->getSetSku());
      }
      foreach($remains as $productLocation) {
        $repoLocation->renumberPositions($productLocation->getNeSyohinSyohinCode());
      }

      $em->flush();

      // ロケーション変更履歴 保存
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_CREATE_SET_PRODUCT_ALL, $this->getLoginUser() ? $this->getLoginUser()->getUsername() : 'BatchSV02', $actionKey);

      // 在庫数更新処理
      // → トリガ実装により削除

      $dbMain->commit();

      $result['message'] = sprintf('%d件のセット商品作成を実行しました。 [number: %d => OK: %d / NG: %d / 残った材料: %d SKU]', (count($ok) + count($ng)), $number, count($ok), count($ng), count($remains));
      $this->setFlash('success', $result['message']); // redirect用

    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      $this->setFlash('warning', $e->getMessage());
    }

    return $this->redirectToRoute('picking_set_product_create_list_detail', [ 'date' => $date, 'number' => $number ]  );
  }


}
