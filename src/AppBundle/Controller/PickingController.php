<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\ProductImagesVariationRepository;
use MiscBundle\Entity\Repository\TbDeliveryPickingListRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbProductLocationRepository;
use MiscBundle\Entity\Repository\TbRealShopPickingListRepository;
use MiscBundle\Entity\Repository\TbSetProductCreateListRepository;
use MiscBundle\Entity\Repository\TbSetProductPickingListRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbWarehouseStockMovePickingListRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingGroupRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryPickingList;
use MiscBundle\Entity\TbLocation;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductLocation;
use MiscBundle\Entity\TbSetProductPickingList;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShippingVoucher;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\TbWarehouseStockMovePickingList;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StopWatchUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * ロケーション管理画面 （スマホ対応）
 * @package AppBundle\Controller
 */
class PickingController extends BaseController
{
  const PICKING_MODE_DELIVERY = 'd';
  const PICKING_MODE_WAREHOUSE_STOCK_MOVE = 'w';


  /**
   * ピッキングリスト一覧画面
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function listAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    $conditions = [
        'date' => 'today'
      , 'status' => 'incomplete'
      , 'warehouse_id' => $currentWarehouse->getId()
    ];
    $conditions = array_merge($conditions, $request->get('search', []));

    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

    /** @var TbDeliveryPickingList[] $list */
    $list = $repo->findListIndex($conditions, 300);

    // 表示用CSS
    foreach ($list as $i => $item) {
      switch ($item['status']) {
        case '未処理':
          $item['css'] = '';
          if($item['old_number'] > 0) $item['css'] = 'list-group-item-warning';

          // 開始者名が入っていれば、「開始済み」に変更
          if ($item['picking_account_name']) {
            $item['status'] = '開始済み';
            if($item['empty_location'] > 0) $item['css'] = 'list-group-item-danger';
          }
          break;
        case '未完了':
          $item['css'] = 'list-group-item-info';
          if($item['empty_location'] > 0) $item['css'] = 'list-group-item-danger';
          break;
        case '要チェック': // 現在は未使用
          $item['css'] = 'list-group-item-danger';
          break;
        case '完了':
          $item['css'] = 'list-group-item-success';
          if($item['empty_location'] > 0) $item['css'] = 'list-group-item-danger';
          break;
        default:
          $item['css'] = '';
          break;
      }
      $picking_block = array_unique(explode(',',$item['picking_block']));
      if(array_search('',$picking_block)) unset($picking_block[array_search('',$picking_block)]);
      if(count($picking_block) > 0) {
        $item['picking_block'] = implode(',',$picking_block);
      } else {
        $item['picking_block'] = '';
      }
      $list[$i] = $item;
    }

    // 画面表示
    return $this->render('AppBundle:Picking:list.html.twig', [
        'account' => $this->getLoginUser()
      , 'list' => $list
      , 'conditions' => $conditions
    ]);
  }

  /**
   * ピッキングリスト 商品一覧画面
   * @param string $date
   * @param int $number
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function productListAction($date, $number, Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

    $listIndex = $repo->findListIndexOne($date, $number, $currentWarehouse);

    /** @var TbDeliveryPickingList[] $list */
    $list = $repo->findPickingProductList($date, $number, $currentWarehouse);

    /** @var TbShippingVoucherPackingGroupRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
    $comment = $repo->getPickingListComment($number, $date, $currentWarehouse->getId());

    // 画面表示
    return $this->render('AppBundle:Picking:product-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'listIndex' => $listIndex
      , 'list' => $list
      , 'comment' => $comment
      , 'confirm' => $request->get('confirm', 0)
      , 'forceStart' => $request->get('forceStart', 0)
    ]);
  }


  /**
   * ピッキングリスト ピッキング開始画面
   * @param string $date
   * @param int $number
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function productStartAction($date, $number, Request $request)
  {
    $account = $this->getLoginUser();

    // 対象倉庫取得
    $currentWarehouse = $account->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

    $listIndex = $repo->findListIndexOne($date, $number, $currentWarehouse);

    if ($listIndex) {

      // すでに開始済みなら確認表示
      if ($listIndex['picking_account_name'] && ! $request->get('confirmed')) {
        $response = $this->redirectToRoute('picking_product_list', ['date' => $date, 'number' => $number, 'confirm' => 1]);
        return $response;
      }

      // 開始処理
      $forceStart = false;
      if ($request->get('forceStart')) {
        $forceStart = true;
      }
      // 出荷伝票明細とピッキングリストの差異チェック
      $diffMessage = $repo->checkDeliveryPickingListAndShippingVoucher($date, $number, $currentWarehouse);
      if (!empty($diffMessage)) {
        // 差異があった場合はredmineにチケット登録
        $this->addShippingVoucherQuantityDiffLog($diffMessage);
      }
      if($repo->setPickingOrder($date, $number, $currentWarehouse, $forceStart) == false){
        // ピッキングリストが作成できなかった場合
        $this->setFlash('danger', 'ピッキングリストが正しく作成されませんでした。もう一度開始してください。');
        if ($request->get('confirmed')){
          $response = $this->redirectToRoute('picking_product_list', ['date' => $date, 'number' => $number, 'forceStart' => 1, 'confirm' => 1]);
        }else{
          $response = $this->redirectToRoute('picking_product_list', ['date' => $date, 'number' => $number, 'forceStart' => 1]);
        }
        return $response;
      }
      $repo->updatePickingAccount($date, $number, $account, $currentWarehouse);
    }

    /** @var TbDeliveryPickingList[] $list */
    $list = $repo->findPickingProductList($date, $number, $currentWarehouse);

    // 先頭の商品詳細 or ピッキングリスト商品一覧へリダイレクト
    if ($list) {
      $response = $this->redirectToRoute('picking_product_detail', ['id' => $list[0]->getId()]);
    } else {
      $this->setFlash('danger', 'ピッキング対象の商品がありません。');
      $response = $this->redirectToRoute('picking_product_list', ['date' => $date, 'number' => $number]);
    }

    return $response;
  }

  /**
   * ピッキングリスト 商品詳細画面
   * @param int $id
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function productDetailAction($id)
  {
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    // 対象倉庫取得
    $currentWarehouse = $account->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

    /** @var TbDeliveryPickingList $item */
    $item = $repo->findOneBy(['id' => $id]);

    $listIndex = null;
    $data = null;
    $otherWarehouseData = [
        'stockTotal' => 0
      , 'locations' => []
    ];

    if ($item) {
      $listIndex = $repo->findListIndexOne($item->getDate()->format('Y-m-d'), $item->getNumber(), $currentWarehouse);
      $listIndex['old_date'] = $item->getOldDate();
      $listIndex['old_date_short'] = $item->getOldDateShort();
      $listIndex['old_number'] = $item->getOldNumber();

      $data = $this->getProductDetailData($item);

      $choiceItem = $item->getChoiceItem();
      if ($choiceItem) {
        $otherLocation = array();

        $locations = $data['locations'];
        $locCount = 0;

        // ピッキング倉庫以外を非表示
        foreach ($locations as $key => $loc) {
          if($loc['locationCode'] == $item->getCurrentLocation()) {
            $locations[$key] = $loc;
            $locCount = $loc['stock'];
          } else {
            unset($locations[$key]);
          }
        }

        if($item->getCurrentLocation() != "" && $item->getItemNum() > $locCount){
          $repo->reSetPickingOrder($item->getDate()->format('Y-m-d'), $item->getNumber(), $currentWarehouse, $item->getSyohinCode());
          $this->setFlash('danger', '対象ロケーションに在庫が不足していたため、ピッキング情報を更新しました。');
          $response = $this->redirectToRoute('picking_product_list', ['date' => $item->getDate()->format('Y-m-d'), 'number' => $item->getNumber()]);
        }

        $data['locations'] = $locations;

        // 別倉庫在庫取得
        $otherWarehouseData['stockTotal'] = $choiceItem->getStock();
        $otherWarehouseData['locations'] = $choiceItem->getAllLocations($currentWarehouse, $item->getCurrentLocation());
      }
    }

    // 倉庫情報取得
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDownObjects();

    // 画面表示
    return $this->render('AppBundle:Picking:product-detail.html.twig', [
        'account' => $account
      , 'listIndex' => $listIndex
      , 'item' => $item
      , 'data' => $data
      , 'otherWarehouseData' => $otherWarehouseData
      , 'warehouses' => $warehouses
    ]);
  }


  /**
   * ピッキングリスト 商品詳細 OK/NG 決定画面
   * @param Request $request
   * @param int $id
   * @param string $button
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function productDetailSubmitAction(Request $request, $id, $button)
  {
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      /** @var TbDeliveryPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

      /** @var TbDeliveryPickingList $item */
      $item = $repo->findOneBy(['id' => $id]);
      if (!$item) {
        $result['message'] = 'ピッキングリストのデータ取得に失敗しました。';
        throw new \RuntimeException();
      }
      $choiceItem = $item->getChoiceItem();
      if (!$choiceItem) {
        $result['message'] = 'ピッキングリストのデータ取得に失敗しました。(choice item)';
        throw new \RuntimeException();
      }
      $warehouse = $item->getWarehouse();
      if (!$warehouse) {
        throw new \RuntimeException('ピッキング対象の倉庫が見つかりませんでした。');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        $result['message'] = "現在下記の処理が実行中のため、更新できません。" . "\n\n" . implode("\n", $runningTasks);
        $result['message'] .= "\n\n もう一度ボタンを選択してください。";
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // データハッシュ変更チェック
      $data = $this->getProductDetailData($item);

      $dataHash = $request->get('data_hash');
      $comment = $request->get('comment');

      if ($dataHash !== $data['dataHash']) {
        $result['message'] = '商品あるいはロケーションのデータが更新されています。再読み込み後、もう一度ボタンを選択して下さい。';
        throw new \RuntimeException('incorrect hash error.');
      }

      // ロケーション・在庫更新処理
      /** @var StopWatchUtil $watch */
      $watch = $this->get('misc.util.stop_watch');
      $watch->start();

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $dbMain->beginTransaction();

      // OK / NGなら在庫更新
      if ($button == 'ok' || $button == 'ng') {

        /** @var TbLocationRepository $repoLocation */
        $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

        $watch->lapStart();

        // （履歴用）アクションキー 作成＆セット
        $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

        $locations = $choiceItem->getActiveLocations($warehouse);
        $pickingRemain = $item->getItemNum();

        /** @var TbProductLocation $prevLocation */
        $prevLocation = null;
        foreach ($locations as $location) {
          if($location->getLocation()->getLocationCode() == $item->getCurrentLocation()) {
            if ($location->getStock() > $pickingRemain) {
              $location->setStock($location->getStock() - $pickingRemain);
              $pickingRemain = 0;
            } else {
              $pickingRemain -= $location->getStock();
              $location->setStock(0);

              // ロケーションが空になったのでposition移動 （複数倉庫化により、ここではゼロ在庫positionをマイナス化するのみ）
              $location->setPosition((abs($location->getPosition()) * -1) - 1);
            }

            if ($pickingRemain <= 0) {
              // pci ロケーション更新
              $choiceItem->setLocation($location->getLocation()->getLocationCode());
              if ($prevLocation) {
                $choiceItem->setPreviouslocation($prevLocation->getLocation()->getLocationCode());
              }

              break;
            }

            $prevLocation = $location;
          }
        }

        // ロケーションそのものの削除用に一覧作成（デッドロック対応）
        $locationIds = [];
        foreach ($choiceItem->getLocations() as $location) {
          $locationIds[] = $location->getLocationId();
        }

        $em = $this->getDoctrine()->getManager('main');
        $em->flush();
        /* ---------------- */ $logger->info(sprintf('lap: %.4f 在庫減算', $watch->lapStopGo()));

        // 過去ロケーションの削除
        $sql = <<<EOD
          DELETE FROM tb_product_location
          WHERE ne_syohin_syohin_code = :syohinCode
            AND position < :minPosition
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':syohinCode', $choiceItem->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
        $stmt->bindValue(':minPosition', TbProductLocationRepository::MIN_POSITION, \PDO::PARAM_INT);
        $stmt->execute();
        /* ---------------- */ $logger->info(sprintf('lap: %.4f 過去ロケーション削除', $watch->lapStopGo()));

        // ロケーション優先順位振り直し
        $repoLocation->renumberPositionsByChoiceItem($choiceItem);

        // ロケーション変更履歴 保存
        /** @var \Doctrine\DBAL\Connection $dbLog */
        $dbLog = $this->getDoctrine()->getConnection('log');
        $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WEB_PICKING, $this->getLoginUser()->getUsername(), $actionKey);
        /* ---------------- */ $logger->info(sprintf('lap: %.4f ロケーション変更履歴 保存', $watch->lapStopGo()));

        // 在庫数更新処理
        // → トリガ実装により削除
      }

      // ピッキングステータス更新
      switch ($button) {
        case 'ok':
          $status = TbDeliveryPickingListRepository::PICKING_STATUS_OK;
          break;
        case 'ng':
          $status = TbDeliveryPickingListRepository::PICKING_STATUS_INCORRECT;
          break;
        case 'pass':
          $status = TbDeliveryPickingListRepository::PICKING_STATUS_PASS;
          break;
        default:
          throw new \RuntimeException('OK / ロケ違い / 在庫無し のいずれも選択されていません。（システムエラー）');
      }

      // 同一出荷伝票グループに紐付くピッキングリスト全てを取得。
      $pickingListDate = $item->getDate();
      $pickingListNumber = $item->getNumber();

      // ピッキングリスト更新。
      $item->setPickingStatus($status);
      $item->setUpdateAccountId($account->getId());
      $item->setUpdateAccountName($account->getUsername());
      $em->flush();

      $sql = <<<EOD
        SELECT
          status
        FROM
          v_delivery_picking_list_index
        WHERE
          date = :date
        AND
          number= :number
        AND
          warehouse_id = :warehouseId;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':date', $pickingListDate, \PDO::PARAM_STR);
      $stmt->bindValue(':number', $pickingListNumber, \PDO::PARAM_INT);
      $stmt->bindValue(':warehouseId', $item->getWarehouseId(), \PDO::PARAM_INT);
      $stmt->execute();
      $totalPickingStatus = $stmt->fetchColumn(0);

      if ($totalPickingStatus === TbDeliveryPickingListRepository::INDEX_STATUS_DONE) {
        /** @var TbShippingVoucherRepository $svRepo */
        $svRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
        /** @var TbShippingVoucher $voucher */
        $voucher = $svRepo->findOneBy([
          'picking_list_date' => $pickingListDate,
          'picking_list_number' => $pickingListNumber
        ]);
        $voucher->setStatus(TbShippingVoucher::STATUS_UNPROCESSED_PACKAGING);
      }

      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      // ピッキング引当数・フリー在庫数更新処理
      // トリガ実装により削除

      $dbMain->commit();
      /* ---------------- */ $logger->info('total: トランザクション => ' . $watch->now());
      /* ---------------- */ $logger->info(sprintf('lap: %.4f トランザクション終了', $watch->lapStopGo()));

      // 処理結果
      $result['status'] = 'ok';
      $result['button'] = $button;

      // 次がある場合
      if ($data['listInfo']['nextId']) {
        $result['message'] = '次の商品へ移動します。';
        $result['redirect'] = $this->generateUrl('picking_product_detail', ['id' => $data['listInfo']['nextId']]);
      // 完了
      } else {
        $result['message'] = 'ピッキング完了です。';
        $result['redirect'] = $this->generateUrl('picking_product_list', ['date' => $item->getDate()->format('Y-m-d'), 'number' => $item->getNumber()]);
        $this->setFlash('success', 'ピッキングを完了しました。');
      }
      /* ---------------- */ $logger->info(sprintf('lap: %.4f URL作成', $watch->lapStopGo()));

      // ボタン「NG」「PASS」の場合、Redmineへチケット作成
      // 本番環境であれば Redmineのチケットも作成する(parameters.yml)
      if (in_array($button, ['ng', 'pass']) && $this->container->getParameter('redmine_picking_ng_ticket')) {

        $buttonWord = '';
        switch ($button) {
          case 'ok':
            $buttonWord = 'OK';
            break;
          case 'pass':
            $buttonWord = '在庫無し';
            break;
          case 'ng':
            $buttonWord = 'ロケ違い';
            break;
          default:
            $buttonWord = '不明';
        }

        /** @var WebAccessUtil $webAccessUtil */
        $webAccessUtil = $this->container->get('misc.util.web_access');
        $host = $this->getParameter('host_main');
        $url = $this->generateUrl('location_product_detail_with_slash', [ 'syohinCode' => $item->getSyohinCode() ]);

        $now = new \DateTime();
        $body = <<<EOD
|処理      |{$buttonWord} ($button)|
|担当者    |{$account->getUsername()}|
|日時      |{$now->format('Y-m-d H:i:s')}|
|倉庫・リスト日付・No. |{$item->getWarehouse()->getName()} : {$item->getDate()->format('Y/m/d')} - {$item->getNumber()}|
|商品コード|{$item->getSyohinCode()}|
|ピッキング数|{$item->getItemNum()}|
|コメント|{$comment}|
|商品ロケーションURL|https://{$host}{$url}|
EOD;

        $ticket = [
          'issue' => [
              'subject'         => sprintf('[ピッキング][%s][%s] %s (%s)', strtoupper($buttonWord), $now->format('Y-m-d H:i:s'), $item->getSyohinCode(), $account->getUsername())
            , 'project_id'      => $this->container->getParameter('redmine_picking_ng_ticket_project')
            , 'priority_id'     => $this->container->getParameter('redmine_picking_ng_ticket_priority')
            , 'description'     => $body
            , 'assigned_to_id'  => $this->container->getParameter('redmine_picking_ng_ticket_user')
            , 'tracker_id'      => $this->container->getParameter('redmine_picking_ng_ticket_tracker')
            // , 'category_id'     => ''
            // , 'status_id'       => ''
          ]
        ];

        $ret = $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
        $logger->info('redmine create ticket:' . $ret);

        /* ---------------- */ $logger->info(sprintf('lap: %.4f Redmineチケット作成', $watch->lapStopGo()));
      }

    } catch (\Exception $e) {
      $logger->error("ピッキング処理でエラーが発生" . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : 'エラーが発生しました。再度実行してください。';

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
      }
    }

    return new JsonResponse($result);
  }

  /**
   * ピッキングリスト削除処理
   */
  public function listDeleteAction(Request $request)
  {
    $date = $request->get('date');
    $number = $request->get('number');

    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    $logger = $this->get('misc.util.batch_logger');
    $logger->info('delete picking list: ' . $currentWarehouse->getName() . ' : ' . $date . ' - ' . $number);

    try {

      /** @var TbDeliveryPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

      $listIndex = $repo->findListIndexOne($date, $number, $currentWarehouse);

      if (!$listIndex) {
        $result['message'] = 'ピッキングリストが見つかりませんでした。';
        throw new \RuntimeException('no picking list');
      }

      $repo->deletePickingList($listIndex);

      // ピッキング引当数・フリー在庫数更新処理
      // →トリガ実装により削除

      $this->setFlash('success', 'ピッキングリストを削除しました。');

      $result = [
          'status' => 'ok'
        , 'message' => sprintf('ピッキングリストを削除しました。再読み込みします。（%s - %d）', $date, $number)
      ];

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : 'ピッキングリストが削除できませんでした。';
    }

    return new JsonResponse($result);
  }

  /**
   * ピッキングリスト削除処理
   */
  public function listMargeAction(Request $request)
  {
    $margeList = $request->get('margeList');
    $logger = $this->get('misc.util.batch_logger');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    // 統合した日の日付に統一
    $today = (new \DateTime())->setTime(0, 0, 0);

    // 当日出力連番取得
    $sql = <<<EOD
          SELECT
            MAX(number) AS number
          FROM tb_delivery_picking_list dpl
          WHERE dpl.`date` = :pickingDate
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':pickingDate', $today->format('Y-m-d'));
    $stmt->execute();

    $pickingListNumber = intval($stmt->fetchColumn(0)) + 1;

    // 当日出力倉庫連番取得
    $sql = <<<EOD
          SELECT
            MAX(warehouse_daily_number)
          FROM tb_shipping_voucher
          WHERE picking_list_date = :pickingDate AND warehouse_id = :warehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':pickingDate', $today->format('Y-m-d'));
    $stmt->bindValue(':warehouseId', $currentWarehouse->getId());
    $stmt->execute();

    $warehouseDailyNumber = intval($stmt->fetchColumn(0)) + 1;


    try {

      /** @var TbDeliveryPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

      foreach($margeList as $val){
        $val = explode('_',$val);
        $date = $val[0];
        $number = $val[1];

        $listIndex = $repo->findListIndexOne($date, $number, $currentWarehouse);

        if (!$listIndex) {
          $result['message'] = 'ピッキングリストが見つかりませんでした。';
          throw new \RuntimeException('no picking list');
        }

        $repo->margePickingList($listIndex, $today->format('Y-m-d'), $pickingListNumber, $warehouseDailyNumber);
      }
      $this->setFlash('success', 'ピッキングリストを統合しました。');
    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollBack();
        $logger->info('ROLLBACK!!');
      }

      $this->setFlash('danger', 'ピッキングリスト統合に失敗しました。:' . $e->getMessage());
    }

    $response = $this->redirectToRoute('picking_list', []);
    return $response;
  }

  /**
   * ピッキング残件数取得
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function pickingListRemainNumberAction(Request $request)
  {
    // 暫定対応：指定時間の範囲はDBへアクセスしない
    $now = new \DateTime();
    $skipTimeFromStr = '06:00'; // 処理対象外時間 FROM
    $skipTimeToStr = '14:00'; // 処理対象外時間 TO
    
    preg_match('/(\d{2}):(\d{2})/', $skipTimeFromStr, $skipFromStringArray);
    preg_match('/(\d{2}):(\d{2})/', $skipTimeToStr, $skipToStringArray);
    $skipTimeFrom = clone $now;
    $skipTimeTo = clone $now;
    $skipTimeFrom->setTime($skipFromStringArray[1], $skipFromStringArray[2]);
    $skipTimeTo->setTime($skipToStringArray[1], $skipToStringArray[2]);
    
    if ($now > $skipTimeFrom && $now < $skipTimeTo) { // 無効化時間内であればエラーを返却して終了
      $result = ['status' => 'ng'];
      return new JsonResponse($result);
    }
    
    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

    // 全倉庫ピッキング残件数取得
    $conditions = [];
    /** @var TbDeliveryPickingList[] $currentWarehouseList */
    $result['remain_number'] = $repo->getPickingListRemainNumber($conditions);

    // 対象倉庫ピッキング残件数取得
    $conditions = [
      'warehouse_id' => $currentWarehouse->getId()
    ];
    /** @var TbDeliveryPickingList[] $currentWarehouseList */
    $result['current_warehouse_remain_number'] = $repo->getPickingListRemainNumber($conditions);

    return new JsonResponse($result);
  }

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
    foreach ($runningTasks as $task) {
      if (in_array($task['proc'], $exclusiveTasks)) {
        $results[] = $task['proc'];
      }
    }

    return $results;
  }

  /**
   * 商品詳細画面 データ取得処理
   * @param TbDeliveryPickingList $item
   * @return array
   */
  private function getProductDetailData($item)
  {
    $data = array();

    $choiceItem = $item->getChoiceItem();
    $data['choiceItem'] = [
        'neSyohinSyohinCode' => $choiceItem->getNeSyohinSyohinCode()
      , 'stock' => $choiceItem->getStock()
      , 'daihyoSyohinCode' => $choiceItem->getDaihyoSyohinCode()
    ];

    // カラー画像
    /** @var ProductImagesVariationRepository $repoColorImages */
    $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');
    $variationImage = $repoColorImages->findByNeSyohinSyohinCode($choiceItem->getNeSyohinSyohinCode());

    // 商品一覧での位置取得
    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

    if ($variationImage) {
      $image = sprintf('//%s/variation_images/%s', $this->getParameter('host_plusnao'), $variationImage->getFileDirPath());
    } else {
      $image = $choiceItem->getProduct()->getImageUrl(sprintf('//%s/images/', $this->getParameter('host_plusnao')));
    }
    $data['image'] = $image;

    $data['locations'] = [];
    /** @var TbProductLocation[] $locations */
    $locations = $choiceItem->getActiveLocations($item->getWarehouse());
    foreach ($locations as $productLocation) {
      $loc = $productLocation->toScalarArray('camel');
      $loc['locationCode'] = $productLocation->getLocation()->getLocationCode();
      $data['locations'][] = $loc;
    }

    /** @var TbDeliveryPickingList[] $list */
    $list = $repo->findPickingProductList($item->getDate()->format('Y-m-d'), $item->getNumber(), $item->getWarehouse());
    $listInfo = [
        'count' => count($list)
      , 'current' => null
      , 'prev' => null
      , 'next' => null
      , 'prevId' => null
      , 'nextId' => null
      , 'nextSyohinCode' => null
      , 'nextLocationCode' => null
      , 'nextItemNum' => null
    ];
    for ($i = 0; $i < count($list); $i++) {
      if ($list[$i]->getId() == $item->getId()) {
        $listInfo['current'] = $i + 1;
        if ($i > 0) {
          $listInfo['prev'] = $listInfo['current'] - 1;
          $listInfo['prevId'] = $list[$i - 1]->getId();
        }
        if ($i < (count($list) - 1)) {
          $listInfo['next'] = $listInfo['current'] + 1;
          $listInfo['nextId'] = $list[$i + 1]->getId();

          $listInfo['nextSyohinCode'] = $list[$i + 1]->getSyohinCode();
          $listInfo['nextLocationCode'] = $list[$i + 1]->getCurrentLocation();
          $listInfo['nextItemNum'] = $list[$i + 1]->getItemNum();
        }
        break;
      }
    }
    $data['listInfo'] = $listInfo;

    $data['dataHash'] = sha1(serialize($data));
    return $data;
  }




  /**
   * 実店舗 ピッキングリスト
   */
  public function realShopPickingListAction()
  {
    /** @var TbRealShopPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');
    $lastUpdated = $repo->getLastUpdated();

    // 画面表示
    return $this->render('AppBundle:Picking:real-shop-picking-list.html.twig', [
        'account'        => $this->getLoginUser()
      , 'lastUpdated'    => $lastUpdated
    ]);
  }

  /**
   * 実店舗 ピッキングリストデータ取得（Ajax）
   * @param Request $request
   * @return JsonResponse
   */
  public function realShopPickingDataAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'conditions' => []
      , 'list' => []
    ];

    try  {

      /** @var TbRealShopPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');
      $data = $repo->getPickingList();

      $logger->info(sprintf('実店舗 picking : データ取得完了 %d件', count($data)));

      $imageParentUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

      $result['list'] = [];
      foreach($data as $picking) {
        $row = $picking;
        $row['image_url'] = TbMainproductsRepository::createImageUrl($picking['pict_directory'], $picking['pict_filename'], $imageParentUrl);
        $row['link_url'] = $this->generateUrl('plusnao_pub_product_color_image_list', [ 'syohinCode' => $picking['ne_syohin_syohin_code'] ]);
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
   * 実店舗 ピッキングリスト OK / PASS 処理
   * @param Request $request
   * @return JsonResponse
   */
  public function realShopPickingSubmitAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'picking_status' => null
    ];

    try  {
      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $pickingId = $request->get('id');
      $button = $request->get('button');
      $syohinCode = $request->get('syohin_code');
      $moveNum = $request->get('move_num', 0);
      $dataHash = $request->get('data_hash');

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      /** @var TbProductchoiceitems $choiceItem */
      $choiceItem = $repoChoice->find($syohinCode);

      $locationData = $this->getPickingLocationList($choiceItem, $currentWarehouse);
      if ($dataHash !== $locationData['dataHash']) {
        throw new \RuntimeException('商品あるいはロケーションのデータが更新されています。再読み込み後、もう一度 OK / PASS を選択して下さい。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      /** @var BaseRepository $repoPicking */
      $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');

      // ピッキング情報取得
      /** @var TbSetProductPickingList $picking */
      $picking = $repoPicking->find($pickingId);
      if (!$picking) {
        throw new \RuntimeException('ピッキング情報が取得できませんでした。');
      }

      // OK or PASS
      switch ($button) {
        case 'ok':
          $status = TbRealShopPickingListRepository::PICKING_STATUS_OK;
          break;
        case 'pass':
          $status = TbRealShopPickingListRepository::PICKING_STATUS_PASS;
          break;
        default:
          $status = TbRealShopPickingListRepository::PICKING_STATUS_NONE;
          break;
      }
      $picking->setStatus($status);
      $result['picking_status'] = $status;

      // PASS の場合はステータスの変更のみ。
      if ($status !== TbSetProductPickingListRepository::PICKING_STATUS_OK) {
        $em->flush();
        return new JsonResponse($result);
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      $locations = $choiceItem->getActiveLocations($currentWarehouse);
      $pickingRemain = $picking->getMoveNum();

      $logger->info(print_r($locationData['locations'], true));
      $logger->info(count($choiceItem->getActiveLocations()));

      /** @var TbProductLocation $prevLocation */
      $prevLocation = null;
      foreach ($locations as $location) {
        if ($location->getStock() > $pickingRemain) {
          $location->setStock($location->getStock() - $pickingRemain);
          $pickingRemain = 0;
        } else {
          $pickingRemain -= $location->getStock();
          $location->setStock(0);

          // ロケーションが空になったのでposition移動 （複数倉庫化により、ここではゼロ在庫positionをマイナス化するのみ）
          $location->setPosition((abs($location->getPosition()) * -1) - 1);
        }

        if ($pickingRemain <= 0) {
          // pci ロケーション更新
          $choiceItem->setLocation($location->getLocation()->getLocationCode());
          if ($prevLocation) {
            $choiceItem->setPreviouslocation($prevLocation->getLocation()->getLocationCode());
          }

          break;
        }

        $prevLocation = $location;
      }

      // ロケーションそのものの削除用に一覧作成（デッドロック対応）
      $locationIds = [];
      foreach ($choiceItem->getLocations() as $location) {
        $locationIds[] = $location->getLocationId();
      }

      $em->flush();

      // 過去ロケーションの削除
      $sql = <<<EOD
          DELETE FROM tb_product_location
          WHERE ne_syohin_syohin_code = :syohinCode
            AND position < :minPosition
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':syohinCode', $choiceItem->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
      $stmt->bindValue(':minPosition', TbProductLocationRepository::MIN_POSITION, \PDO::PARAM_INT);
      $stmt->execute();

      // ロケーション優先順位振り直し
      $repoLocation->renumberPositionsByChoiceItem($choiceItem);

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_REAL_SHOP_PICKING, $this->getLoginUser()->getUsername(), $actionKey);

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '商品在庫数を更新しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

    }

    return new JsonResponse($result);
  }

  /**
   * 実店舗 ピッキングリスト再生成
   */
  public function realShopRefreshPickingListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('実店舗ピッキングリスト更新処理を開始しました。');

    $account = $this->getLoginUser();
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($account) {
      $logger->setAccount($account);
    }

    try {
      $logExecTitle = sprintf('実店舗ピッキングリスト更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // すでにピッキング済みのデータがあれば、再集計できない。（PASSはスルー）
      /** @var TbRealShopPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');
      if ($repo->getPickedCount() > 0) {
        throw new \RuntimeException('すでにピッキングされたデータがあります。再集計の前にピッキング確定を行って下さい。');
      }

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      // スマレジ在庫取込
      $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      ];
      if ($account) {
        $commandArgs[] = sprintf('--account=%d', $account->getId());
      }

      $logger->info('real shop: batch:real_shop_import_smaregi_stock: ' . print_r($commandArgs, true));
      $input = new ArgvInput($commandArgs);
      $output = new ConsoleOutput();

      $command = $this->get('batch.real_shop_import_smaregi_stock');
      $exitCode = $command->run($input, $output);
      if ($exitCode !== 0) { // コマンドが異常終了した
        throw new \RuntimeException('スマレジ在庫取込でエラーが発生しました。 : ' . $exitCode);
      }

      // 再生成実行
      $repo->refreshRealShopPickingList($currentWarehouse);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('実店舗ピッキングリスト更新処理を完了しました。');
      $this->setFlash('success', '実店舗ピッキングリストを更新しました。');

    } catch (\Exception $e) {

      $logger->error('実店舗ピッキングリスト更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('実店舗ピッキングリスト更新処理 エラー', '実店舗ピッキングリスト更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '実店舗ピッキングリスト更新処理 でエラーが発生しました。', 'error'
      );

      $this->setFlash('danger', '実店舗ピッキングリスト更新処理エラー:' . $e->getMessage());

    }

    return $this->redirectToRoute('picking_real_shop_picking_list');
  }

  /**
   * 実店舗 ピッキングリスト削除処理
   */
  public function realShopClearPickingListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('実店舗ピッキングリスト削除処理を開始しました。');

    $account = $this->getLoginUser();
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($account) {
      $logger->setAccount($account);
    }

    try {
      $logExecTitle = sprintf('実店舗ピッキングリスト削除処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // すでにピッキング済みのデータがあれば、削除できない。（PASSはスルー）
      /** @var TbRealShopPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');
      if ($repo->getPickedCount() > 0) {
        throw new \RuntimeException('すでにピッキングされたデータがあります。再集計の前にピッキング確定を行って下さい。');
      }

      $repo->clearAll();

    } catch (\Exception $e) {

      $logger->error('実店舗ピッキングリスト削除処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('実店舗ピッキングリスト削除処理 エラー', '実店舗ピッキングリスト削除処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '実店舗ピッキングリスト削除処理 でエラーが発生しました。', 'error'
      );

      $this->setFlash('danger', '実店舗ピッキングリスト削除処理エラー:' . $e->getMessage());

    }

    return $this->redirectToRoute('picking_real_shop_picking_list');
  }


  /**
   * 実店舗 ピッキング確定処理
   */
  public function realShopFinishPickingListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('実店舗ピッキング確定処理を開始しました。');

    $account = $this->getLoginUser();
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($account) {
      $logger->setAccount($account);
    }

    try {
      $logExecTitle = sprintf('実店舗ピッキング確定処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbRealShopPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');

      // ピッキング確定処理
      $repo->convertPickingListToReport($account);

      // ピッキングリスト更新処理
      $repo->refreshRealShopPickingList($currentWarehouse);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('実店舗ピッキング確定処理を完了しました。');
      $this->setFlash('success', '実店舗ピッキング確定処理を行いました。ピッキングリストを再集計しました。');

    } catch (\Exception $e) {

      $logger->error('実店舗ピッキング確定処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('実店舗ピッキング確定処理 エラー', '実店舗ピッキング確定処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '実店舗ピッキング確定処理 でエラーが発生しました。', 'error'
      );

      $this->setFlash('danger', '実店舗ピッキング確定処理エラー:' . $e->getMessage());

    }

    return $this->redirectToRoute('picking_real_shop_picking_list');
  }

  /**
   * セット商品 作成リスト一覧
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function setProductCreateListAction(Request $request)
  {
    /** @var TbSetProductCreateListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductCreateList');

    $conditions = [
        'date' => null
      , 'status' => $request->get('status', 'incomplete')
    ];

    $date = $request->get('date');
    if ($date) {
      $conditions['date'] = new \DateTime($date);
    }

    $list = $repo->findListIndex($conditions, 1000);
    // 表示用CSS
    foreach ($list as $i => $item) {
      switch ($item['status']) {
        case TbSetProductCreateListRepository::INDEX_STATUS_NONE:
          $item['css'] = 'btn-default';
          break;
        case TbSetProductCreateListRepository::INDEX_STATUS_ONGOING:
          $item['css'] = 'btn-info';
          break;
        case TbSetProductCreateListRepository::INDEX_STATUS_DONE:
          $item['css'] = 'btn-success';
          break;
        default:
          $item['css'] = 'btn-default';
          break;
      }
      $list[$i] = $item;
    }

    // 画面表示
    return $this->render('AppBundle:Picking:set-product-create-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'list'    => $list
      , 'conditions' => $conditions
    ]);
  }

  /**
   * セット商品 作成リスト詳細
   * @param $date
   * @param $number
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function setProductCreateListDetailAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $logger->info('set product create detail => date: ' . $date . ' / number: ' . $number);

    /** @var TbSetProductCreateListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductCreateList');
    $products = $repo->findListWithDetails($date, $number);

    // リスト状態を取得
    $conditions = [
        'date' => new \DateTime($date)
      , 'number' => $number
    ];
    $indexList = $repo->findListIndex($conditions, 1);
    $indexInfo = $indexList ? array_shift($indexList) : null;

    // ラベル印刷用データ
    $labelList = [];
    foreach($products as $product) {
      $labelList[] = [
          'neSyohinSyohinCode' => $product['set_sku']
        , 'num' => $product['create_num']
      ];
    }

    // 画面表示
    return $this->render('AppBundle:Picking:set-product-create-detail.html.twig', [
        'account'  => $this->getLoginUser()
      , 'date' => $date
      , 'number' => $number
      , 'products' => $products
      , 'indexInfo' => $indexInfo
      , 'labelListJson' => json_encode($labelList)
      , 'repo' => $repo
    ]);
   }


  /**
   * セット商品 作成リスト削除
   * @param Request $request
   * @return JsonResponse
   */
  public function setProductDeleteCreateListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
    ];

    $date = $request->get('date');
    $number = $request->get('number');

    $logger->info('delete set product create list: ' . $date . ' - ' . $number);

    try {

      /** @var TbSetProductCreateListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductCreateList');
      $count = $repo->deleteCreateList($date, $number);

      $this->setFlash('success', sprintf('セット商品作成リストを削除しました。(%d 商品)', $count));

      $result = [
          'status' => 'ok'
        , 'message' => sprintf('セット商品作成リストを削除しました。再読み込みします。（%s - %d : %d 商品）', $date, $number, $count)
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * セット商品 ピッキングリスト
   * @param $date
   * @param $number
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function setProductPickingListAction($date, $number)
  {
    /** @var TbSetProductPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductPickingList');
    $lastUpdated = $repo->getLastUpdated($date, $number);

    // 画面表示
    return $this->render('AppBundle:Picking:set-product-picking-list.html.twig', [
        'account'     => $this->getLoginUser()
      , 'lastUpdated' => $lastUpdated
      , 'date'        => $date
      , 'number'      => $number
    ]);
  }

  /**
   * セット商品 ピッキングリストデータ取得（Ajax）
   * @param string $date
   * @param int $number
   * @return JsonResponse
   */
  public function setProductPickingDataAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'list' => []
    ];

    try  {

      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      /** @var TbSetProductPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductPickingList');
      $data = $repo->getPickingList($date, $number);

      /** @var ProductImagesVariationRepository $repoColorImages */
      $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $imageParentUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

      $result['list'] = [];
      foreach($data as $picking) {
        $row = $picking;
        $row['link_url'] = $this->generateUrl('plusnao_pub_product_color_image_list', [ 'syohinCode' => $picking['ne_syohin_syohin_code'] ]);

        // カラー画像取得
        $row['image_url'] = TbMainproductsRepository::createImageUrl($picking['pict_directory'], $picking['pict_filename'], $imageParentUrl);
        // もしバリエーション画像があれば差し替え
        $choiceItem = $repoChoice->find($picking['ne_syohin_syohin_code']);
        if ($choiceItem) {
          $variationImage = $repoColorImages->findByNeSyohinSyohinCode($choiceItem->getNeSyohinSyohinCode());
          if ($variationImage) {
            $row['image_url'] = sprintf('//%s/variation_images/%s', $this->getParameter('host_plusnao'), $variationImage->getFileDirPath());
          }
        }

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
   * セット商品 ピッキングリスト ロケーション更新（Ajax）
   * @param string $date
   * @param int $number
   * @return JsonResponse
   */
  public function setProductRefreshLocationAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
    ];

    try  {
      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbSetProductPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductPickingList');
      $repo->refreshLocation($date, $number, $currentWarehouse);

      $result['message'] = 'ロケーション情報を更新しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 実店舗・セット商品・倉庫在庫 ピッキングリスト ロケーション詳細 取得
   * @param Request $request
   * @return JsonResponse
   */
  public function getPickingLocationListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'data' => null
    ];

    try  {

      /** @var TbProductchoiceitemsRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbProductchoiceitems $choiceItem */
      $syohinCode = $request->get('code');
      $choiceItem = $repo->find($syohinCode);

      $result['data'] = $this->getPickingLocationList($choiceItem, $currentWarehouse);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }



  /**
   * セット商品 ピッキングリスト OK / PASS 処理
   * @param Request $request
   * @return JsonResponse
   */
  public function setProductPickingSubmitAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'picking_status' => null
    ];

    try  {

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $pickingId = $request->get('id');
      $button = $request->get('button');
      $syohinCode = $request->get('syohin_code');
      $moveNum = $request->get('move_num', 0);
      $dataHash = $request->get('data_hash');

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      /** @var TbProductchoiceitems $choiceItem */
      $choiceItem = $repoChoice->find($syohinCode);

      $locationData = $this->getPickingLocationList($choiceItem, $currentWarehouse);
      if ($dataHash !== $locationData['dataHash']) {
        throw new \RuntimeException('商品あるいはロケーションのデータが更新されています。再読み込み後、もう一度 OK / PASS を選択して下さい。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      /** @var BaseRepository $repoPicking */
      $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbSetProductPickingList');

      // ピッキング情報取得
      /** @var TbSetProductPickingList $picking */
      $picking = $repoPicking->find($pickingId);
      if (!$picking) {
        throw new \RuntimeException('ピッキング情報が取得できませんでした。');
      }

      // OK or PASS
      switch ($button) {
        case 'ok':
          $status = TbSetProductPickingListRepository::PICKING_STATUS_OK;
          break;
        case 'pass':
          $status = TbSetProductPickingListRepository::PICKING_STATUS_PASS;
          break;
        default:
          $status = TbSetProductPickingListRepository::PICKING_STATUS_NONE;
          break;
      }
      $picking->setStatus($status);
      $result['picking_status'] = $status;

      // PASS の場合はステータスの変更のみ。
      if ($status !== TbSetProductPickingListRepository::PICKING_STATUS_OK) {
        $em->flush();
        return new JsonResponse($result);
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      $locations = $choiceItem->getActiveLocations($currentWarehouse);
      $pickingRemain = $picking->getMoveNum();

      /** @var TbProductLocation $prevLocation */
      $prevLocation = null;
      foreach ($locations as $location) {
        if ($location->getStock() > $pickingRemain) {
          $location->setStock($location->getStock() - $pickingRemain);
          $pickingRemain = 0;
        } else {
          $pickingRemain -= $location->getStock();
          $location->setStock(0);

          // ロケーションが空になったのでposition移動 （複数倉庫化により、ここではゼロ在庫positionをマイナス化するのみ）
          $location->setPosition((abs($location->getPosition()) * -1) - 1);
        }

        if ($pickingRemain <= 0) {
          // pci ロケーション更新
          $choiceItem->setLocation($location->getLocation()->getLocationCode());
          if ($prevLocation) {
            $choiceItem->setPreviouslocation($prevLocation->getLocation()->getLocationCode());
          }

          break;
        }

        $prevLocation = $location;
      }

      // ロケーションそのものの削除用に一覧作成（デッドロック対応）
      $locationIds = [];
      foreach ($choiceItem->getLocations() as $location) {
        $locationIds[] = $location->getLocationId();
      }

      $em->flush();

      // 過去ロケーションの削除
      $sql = <<<EOD
          DELETE FROM tb_product_location
          WHERE ne_syohin_syohin_code = :syohinCode
            AND position < :minPosition
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':syohinCode', $choiceItem->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
      $stmt->bindValue(':minPosition', TbProductLocationRepository::MIN_POSITION, \PDO::PARAM_INT);
      $stmt->execute();

      // ロケーション優先順位振り直し
      $repoLocation->renumberPositionsByChoiceItem($choiceItem);

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_SET_PRODUCT_PICKING, $this->getLoginUser()->getUsername(), $actionKey);

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = '商品在庫数を更新しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

    }

    return new JsonResponse($result);
  }


  /**
   * 倉庫在庫 ピッキングリスト一覧
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function warehouseStockMoveListAction(Request $request)
  {
    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbWarehouseStockMovePickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');

    // 絞込条件
    $conditions = [
        'date' => null
      , 'status' => $request->get('status', 'incomplete')
    ];
    $date = $request->get('date');
    if ($date) {
      $conditions['date'] = new \DateTime($date);
    }

    $list = $repo->findListIndex($currentWarehouse->getId(), $conditions, 1000);
    // 表示用CSS
    foreach ($list as $i => $item) {
      switch ($item['status']) {
        case TbWarehouseStockMovePickingListRepository::INDEX_STATUS_NONE:
          $item['css'] = 'btn-default';
          break;
        case TbWarehouseStockMovePickingListRepository::INDEX_STATUS_ONGOING:
          $item['css'] = 'btn-info';
          break;
        case TbWarehouseStockMovePickingListRepository::INDEX_STATUS_UNLOCATED:
          $item['css'] = 'btn-warning';
          break;
        case TbWarehouseStockMovePickingListRepository::INDEX_STATUS_DONE:
          $item['css'] = 'btn-success';
          break;
        default:
          $item['css'] = 'btn-default';
          break;
      }
      $list[$i] = $item;
    }

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    $warehouseId = $commonUtil->getSettingValue(TbSetting::KEY_STOCK_MOVE_WAREHOUSE_ID);
    $warehouseRepo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouseName = $warehouseRepo->find($warehouseId)->getName();

    // 画面表示
    return $this->render('AppBundle:Picking:warehouse-stock-move-picking-list.html.twig', [
        'account' => $this->getLoginUser()
      , 'list'    => $list
      , 'conditions' => $conditions
      , 'warehouseName' => $warehouseName
    ]);
  }

  /**
   * 倉庫在庫ピッキングリスト 削除処理
   * @param Request $request
   * @return JsonResponse
   */
  public function warehouseStockMoveListRemoveAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'list' => []
    ];

    try  {
      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $date = $request->get('date');
      $number = $request->get('number');
      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      /** @var TbWarehouseStockMovePickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $repo->removePickingList($currentWarehouse->getId(), $date, $number);

      $result['message'] = sprintf('%s - %d のピッキングリストを削除しました。', $date, $number);

      $this->setFlash('success', $result['message']);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫在庫 ピッキングリスト
   * @param $date
   * @param $number
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function warehouseStockMovePickingListAction($date, $number)
  {
    // 対象倉庫取得
    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    /** @var TbWarehouseStockMovePickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
    $lastUpdated = $repo->getLastUpdatedAndAccount($currentWarehouse->getId(), $date, $number);

    // 画面表示
    return $this->render('AppBundle:Picking:warehouse-stock-move-picking-list-detail.html.twig', [
        'account'     => $this->getLoginUser()
      , 'lastUpdated' => $lastUpdated
      , 'date'        => $date
      , 'number'      => $number
    ]);
  }

  /**
   * 倉庫在庫 ピッキングリストデータ取得（Ajax）
   * @param string $date
   * @param int $number
   * @return JsonResponse
   */
  public function warehouseStockMovePickingDataAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'list' => []
    ];

    try  {
      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      /** @var TbWarehouseStockMovePickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $data = $repo->getPickingList($currentWarehouse->getId(), $date, $number);

      $logger->info(sprintf('倉庫在庫 picking : データ取得完了 %d件', count($data)));


      /** @var ProductImagesVariationRepository $repoColorImages */
      $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $imageParentUrl = sprintf('//%s/images/', $this->getParameter('host_plusnao'));

      $result['list'] = [];
      foreach($data as $picking) {
        $row = $picking;
        $row['link_url'] = $this->generateUrl('plusnao_pub_product_color_image_list', [ 'syohinCode' => $picking['ne_syohin_syohin_code'] ]);

        // カラー画像取得
        $row['image_url'] = TbMainproductsRepository::createImageUrl($picking['pict_directory'], $picking['pict_filename'], $imageParentUrl);
        // もしバリエーション画像があれば差し替え
        $choiceItem = $repoChoice->find($picking['ne_syohin_syohin_code']);
        if ($choiceItem) {
          $variationImage = $repoColorImages->findByNeSyohinSyohinCode($choiceItem->getNeSyohinSyohinCode());
          if ($variationImage) {
            $row['image_url'] = sprintf('//%s/variation_images/%s', $this->getParameter('host_plusnao'), $variationImage->getFileDirPath());
          }
        }

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
   * 倉庫在庫 ピッキングリスト ロケーション更新（Ajax）
   * @param string $date
   * @param int $number
   * @return JsonResponse
   */
  public function warehouseStockMoveRefreshLocationAction($date, $number)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => ''
    ];

    try  {
      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbWarehouseStockMovePickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $repo->refreshLocation($date, $number, $currentWarehouse, $this->getLoginUser());

      $logger->info(sprintf('倉庫在庫 picking : ロケーション更新完了'));
      $result['message'] = 'ロケーション情報を更新しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫在庫 ピッキングリスト OK / PASS 処理
   * @param Request $request
   * @return JsonResponse
   */
  public function warehouseStockMovePickingSubmitAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
      , 'picking_status' => null
      , 'picked'
      , 'shortage'
    ];

    try  {
      $account = $this->getLoginUser();

      // 対象倉庫取得
      $currentWarehouse = $account->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $pickingId = $request->get('id');
      $button = $request->get('button');
      $syohinCode = $request->get('syohin_code');
      $moveNum = $request->get('move_num', 0);
      $dataHash = $request->get('data_hash');

      // ロケーションIDが指定されたOKは部分OK
      $locationId = $request->get('location_id');

      /** @var TbProductchoiceitemsRepository $repoChoice */
      $repoChoice = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      /** @var TbProductchoiceitems $choiceItem */
      $choiceItem = $repoChoice->find($syohinCode);

      $locationData = $this->getPickingLocationList($choiceItem, $currentWarehouse);
      if ($dataHash !== $locationData['dataHash']) {
        throw new \RuntimeException('商品あるいはロケーションのデータが更新されています。再読み込み後、もう一度 OK / PASS を選択して下さい。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      /** @var TbWarehouseStockMovePickingListRepository $repoPicking */
      $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');

      // ピッキング情報取得
      /** @var TbWarehouseStockMovePickingList $picking */
      $picking = $repoPicking->find($pickingId);
      if (!$picking) {
        throw new \RuntimeException('ピッキング情報が取得できませんでした。');
      }

      $picking->setPickingAccountId($account->getId());
      $picking->setPickingAccountName($account->getUsername());

      // OK or PASS
      switch ($button) {
        case 'ok':
          $status = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_OK;
          break;
        case 'pass':
          $status = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_PASS;
          break;
        default:
          $status = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_NONE;
          break;
      }

      // PASS の場合はステータス・ピッキングアカウントの変更のみ。
      if ($status !== TbSetProductPickingListRepository::PICKING_STATUS_OK) {
        $picking->setStatus($status);
        $result['picked_num'] = $picking->getPickedNum();
        $result['shortage'] = $picking->getShortage();
        $result['picking_status'] = $status;

        $em->flush();
        return new JsonResponse($result);
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      // 更新処理
      $dbMain->beginTransaction();

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      $locations = $choiceItem->getActiveLocations($currentWarehouse);
      if ($locationId) {
        $logger->info('部分OK : ' . $locationId);

        $tmp = $locations;
        $locations = [];
        foreach($tmp as $location) {
          $logger->info('check: ' . $location->getLocationId() . ($location->getLocationId() == $locationId ? ' hit!' : ' ...'));

          if ($location->getLocationId() == $locationId) {
            $locations[] = $location;
          }
        }
      }
      if (!$locations) {
        throw new \RuntimeException('ピッキング対象ロケーションが取得できませんでした。');
      }

      $pickingRemain = $picking->getShortage();
      $picked = 0;

      /** @var TbProductLocation $prevLocation */
      $prevLocation = null;
      foreach ($locations as $location) {
        if ($location->getStock() > $pickingRemain) {
          $picked += $pickingRemain;
          $location->setStock($location->getStock() - $pickingRemain);
          $pickingRemain = 0;
        } else {
          $picked += $location->getStock();
          $pickingRemain -= $location->getStock();
          $location->setStock(0);

          // ロケーションが空になったのでposition移動 （複数倉庫化により、ここではゼロ在庫positionをマイナス化するのみ）
          $location->setPosition((abs($location->getPosition()) * -1) - 1);
        }

        if ($pickingRemain <= 0) {
          // pci ロケーション更新
          $choiceItem->setLocation($location->getLocation()->getLocationCode());
          if ($prevLocation) {
            $choiceItem->setPreviouslocation($prevLocation->getLocation()->getLocationCode());
          }

          break;
        }

        $prevLocation = $location;
      }

      $picking->setPickedNum($picking->getPickedNum() + $picked);

      // 未ピッキングがなければOK
      if ($pickingRemain <= 0) {
        $status = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_OK;

      // 未ピッキングがあるのに在庫がもう無ければ「不足」
      } else {

        $stockRemain = 0;
        foreach($choiceItem->getActiveLocations($currentWarehouse) as $location) {
          $stockRemain += $location->getStock();
        }
        if ($stockRemain <= 0) {
          $status = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_INCORRECT;
        } else {
          // それ以外はNONEに戻す（PASSからでも）
          $status = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_NONE;
        }
      }
      $picking->setStatus($status);

      $result['picked_num'] = $picking->getPickedNum();
      $result['shortage'] = $picking->getMoveNum() - $picking->getPickedNum(); // ここは再読み込みをしていないため、現場で計算
      $result['picking_status'] = $status;

      // ロケーションそのものの削除用に一覧作成（デッドロック対応）
      $locationIds = [];
      foreach ($choiceItem->getLocations() as $location) {
        $locationIds[] = $location->getLocationId();
      }

      $em->flush();

      // 過去ロケーションの削除
      $sql = <<<EOD
          DELETE FROM tb_product_location
          WHERE ne_syohin_syohin_code = :syohinCode
            AND position < :minPosition
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':syohinCode', $choiceItem->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
      $stmt->bindValue(':minPosition', TbProductLocationRepository::MIN_POSITION, \PDO::PARAM_INT);
      $stmt->execute();

      // ロケーション優先順位振り直し
      $repoLocation->renumberPositionsByChoiceItem($choiceItem);

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WAREHOUSE_STOCK_MOVE_PICKING, $this->getLoginUser()->getUsername(), $actionKey);

      $dbMain->commit();

      $result['status'] = 'ok';
      $result['message'] = 'ピッキングを実行しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫在庫 ピッキングリスト ロケーション作成（完了処理）（Ajax）
   * @param string $date
   * @param int $number
   * @param Request $request
   * @return JsonResponse
   */
  public function warehouseStockMoveCreateLocationAction($date, $number, Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
    ];

    try  {
      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      $newLocationCode = $request->get('newLocationCode');
      $confirm = boolval($request->get('confirm', false));

      $logger->info(sprintf('倉庫在庫ピッキング ロケーション作成: %s:%d => %s', $date, $number, $newLocationCode));

      // ロケーションコード変換
      $newLocationCode = $repoLocation->fixLocationCode($newLocationCode);
      // ロケーションコード
      if (!$repoLocation->isValidLocationCode($newLocationCode)) {
        throw new \RuntimeException('ロケーションコードが正しくありません');
      }

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      /** @var TbWarehouseStockMovePickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $pickingList = $repo->findUnlocatedPickedList($currentWarehouse->getId(), $date, $number);
      if (!$pickingList) {
        throw new \RuntimeException('ピッキングリストが見つかりませんでした。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      // 更新処理
      $dbMain->beginTransaction();

      // （履歴用）アクションキー 作成＆セット
      $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

      // 移動先ロケーション 作成
      /** @var TbLocation $location */
      $newLocation = $repoLocation->getByLocationCode($currentWarehouse->getId(), $newLocationCode, true);
      // 存在するロケーションなら確認。
      if ($newLocation) {
        if (!$confirm) {
          // 確認レスポンス
          $result['status'] = 'confirm';
          $result['message'] = sprintf("すでに存在するロケーションコードです。(%s)\n\nこのロケーションに在庫を追加してよろしいですか？", $newLocation->getLocationCode());

          return new JsonResponse($result);
        } else {
          // 確認済みなら追加。
        }
      } else {
        $newLocation = $repoLocation->createNewLocation($currentWarehouse->getId(), $newLocationCode);
        if (!$newLocation) {
          throw new \RuntimeException('ロケーションの新規作成に失敗しました。[' . $newLocationCode . ']');
        }
        $logger->info('ロケーション作成成功: ' . $newLocation->getLocationCode());
      }

      // 商品ロケーション作成処理
      $repo->createLocationWithUnlocatedPickedList($newLocation, $currentWarehouse, $date, $number);

      $em->flush(); // 一応。

      // ロケーション変更履歴 保存
      /** @var \Doctrine\DBAL\Connection $dbLog */
      $dbLog = $this->getDoctrine()->getConnection('log');
      $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_WAREHOUSE_STOCK_MOVE_PICKING_CREATE, $this->getLoginUser()->getUsername(), $actionKey);

      $dbMain->commit();

      $logger->info(sprintf('倉庫在庫 picking : ロケーション作成完了'));

      $result['status'] = 'ok';
      $result['message'] = 'ロケーション情報を作成しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * 倉庫在庫 ピッキングリスト 移動伝票充当処理（完了処理）（Ajax）
   * @param string $date
   * @param int $number
   * @param Request $request
   * @return JsonResponse
   */
  public function warehouseStockMoveApplyTransportDetailAction($date, $number, Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
    ];

    try  {
      if (!$date || !$number) {
        throw new \RuntimeException('no date or number.');
      }

      // 対象倉庫取得
      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }

      $logger->info(sprintf('倉庫在庫ピッキング 移動伝票充当 %s:%s', $date, $number));

      // 関連処理中ブロックチェック
      if ($runningTasks = $this->getRunningExclusiveTasks()) {
        throw new \RuntimeException("現在下記の処理が実行中のため、\n更新できません。" . "\n\n" . implode("\n", $runningTasks));
      }

      /** @var TbWarehouseStockMovePickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $pickingList = $repo->findUnlocatedPickedList($currentWarehouse->getId(), $date, $number);
      if (!$pickingList) {
        throw new \RuntimeException('ピッキングリストが見つかりませんでした。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      // 更新処理
      $dbMain->beginTransaction();

      // 移動伝票充当処理
      $repo->applyTransportDetailWithUnlocatedPickedList($currentWarehouse, $date, $number);

      $dbMain->commit();

      $logger->info(sprintf('倉庫在庫 picking : 移動伝票充当処理 完了'));

      $result['status'] = 'ok';
      $result['message'] = '移動伝票へ反映しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * ピッキング（共通） ロケーション在庫修正処理
   * @param Request $request
   * @return JsonResponse
   */
  public function updateStockAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => ''
    ];
    $messages = [];

    try  {

      $mode = $request->get('mode');

      if (!in_array($mode, [
          self::PICKING_MODE_DELIVERY
        , self::PICKING_MODE_WAREHOUSE_STOCK_MOVE
      ])) {
        throw new \RuntimeException('パラメータが不正です。(mode)');
      }

      // 在庫更新処理
      $neSyohinSyohinCode = $request->get('neSyohinSyohinCode');
      $locationId = $request->get('locationId');
      $newStock = $request->get('newStock', null);

      if (is_null($newStock)) {
        throw new \RuntimeException('修正在庫数が入力されませんでした。');
      }

      /** @var TbProductLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');
      /** @var TbProductLocation $productLocation */
      $productLocation = $repo->findOneBy([
          'ne_syohin_syohin_code' => $neSyohinSyohinCode
        , 'location_id' => $locationId
      ]);

      if (!$productLocation) {
        throw new \RuntimeException('ロケーションデータが見つかりませんでした。');
      }

      /** @var TbLocationRepository $repoLocation */
      $repoLocation = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

      // 更新処理
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      $dbMain->beginTransaction();

      // 在庫数修正
      if ($productLocation->getStock() != $newStock) {
        // （履歴用）アクションキー 作成＆セット
        $actionKey = $repoLocation->setLocationLogActionKey($dbMain);

        $productLocation->setStock($newStock);

        $em->flush(); // 一応。

        $messages[] = sprintf('%s : %s の在庫を%d個に修正しました。', $productLocation->getNeSyohinSyohinCode(), $productLocation->getLocation()->getLocationCode(), $productLocation->getStock());

        // ロケーション変更履歴 保存
        /** @var \Doctrine\DBAL\Connection $dbLog */
        $dbLog = $this->getDoctrine()->getConnection('log');
        $repoLocation->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_PICKING_UPDATE_STOCK, $this->getLoginUser()->getUsername(), $actionKey);
      }

      $dbMain->commit();


      $logger->info(sprintf('picking : 在庫修正・一部OK処理'));
      $logger->dump($messages);

      $result['status'] = 'ok';
      $result['message'] = $messages ? implode("\n", $messages) : '処理を完了しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }



  // ---------------------------------------------------


  /**
   * 実店舗・セット商品・倉庫在庫 ピッキングロケーション一覧取得処理
   * @param TbProductchoiceitems $choiceItem
   * @param TbWarehouse $warehouse
   * @return array
   */
  private function getPickingLocationList($choiceItem, $warehouse)
  {
    if (!$choiceItem) {
      throw new \RuntimeException('ロケーションデータが取得できませんでした。');
    }

    $data = array();
    $data['choiceItem'] = [
        'neSyohinSyohinCode' => $choiceItem->getNeSyohinSyohinCode()
      , 'stock' => $choiceItem->getStock()
      , 'warehouseStock' => $choiceItem->getWarehouseStock($warehouse)
      , 'daihyoSyohinCode' => $choiceItem->getDaihyoSyohinCode()
    ];

    $data['locations'] = [];
    /** @var TbProductLocation[] $locations */
    $locations = $choiceItem->getActiveLocations($warehouse);
    foreach ($locations as $productLocation) {
      $loc = $productLocation->toScalarArray('camel');
      $loc['locationCode'] = $productLocation->getLocation()->getLocationCode();
      $data['locations'][] = $loc;
    }

    $data['dataHash'] = sha1(serialize($data));

    return $data;
  }

    /**
   * 数量差異のログを登録する
   * @param string $diffMessage
   */
  private function addShippingVoucherQuantityDiffLog($diffMessage)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {
      $logger->addDbLog(
        $logger->makeDbLog('ピッキング開始', 'ピッキング数チェック', '数量差異発生')
                ->setInformation($diffMessage)
        , true
        , '出荷伝票明細とピッキングリストで数量差異が発生しました。'
        , 'error'
      );
    } catch (\Exception $e) {
      $logger->error('ピッキング開始：　エラー通知メール送信失敗');
    }
  }

}
