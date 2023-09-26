<?php

namespace AppBundle\Controller;

use BatchBundle\Job\BaseJob;
use BatchBundle\Job\MainJob;
use BatchBundle\MallProcess\NextEngineMallProcess;
use DateTimeInterface;
use Doctrine\ORM\Query\Expr\Select;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\JobRequestRepository;
use MiscBundle\Entity\Repository\TbDeliveryChangeShippingMethodRepository;
use MiscBundle\Entity\Repository\TbDeliveryPickingListRepository;
use MiscBundle\Entity\Repository\TbDeliveryStatementDetailNumOrderListInfoRepository;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;
use MiscBundle\Entity\Repository\TbSalesDetailAnalyzeRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherAutoGenerateRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingGroupRepository;
use MiscBundle\Entity\Repository\TbShoppingMallRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\Repository\TbWarehouseStockMovePickingListRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryChangeShippingMethod;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShippingVoucher;
use MiscBundle\Entity\TbShippingVoucherAutoGenerate;
use MiscBundle\Entity\TbShippingVoucherPackingGroup;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\VSalesVoucher;
use MiscBundle\Service\PackingService;
use MiscBundle\Service\ShippingVoucherService;
use MiscBundle\Service\ShippingLabelService;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringUtil;
use mysql_xdevapi\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use BatchBundle\Job\NonExclusiveJob;

/**
 * 梱包・出荷関連
 * @package AppBundle\Controller
 */
class DeliveryController extends BaseController
{

  /**
   * 納品書印刷 件数上位順
   * @throws \Doctrine\DBAL\DBALException
   */
  public function statementDetailProductNumListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $account = $this->getLoginUser();

    $data = [
      'monthly' => []
      , 'daily' => []
      , 'results' => []
    ];

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 設定値取得
    /** @var TbDeliveryStatementDetailNumOrderListInfoRepository $repoSetting */
    $repoSetting = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryStatementDetailNumOrderListInfo');
    $settingInfo = $repoSetting->getSettingInfo();

    $currentWarehouse = $this->getLoginUser()->getWarehouse();
    if (!$currentWarehouse) {
      throw new \RuntimeException('選択倉庫が取得できませんでした。');
    }

    // 発送方法取得
    // -- 発送方法取得
    $deliveryMethods = [];
    $sql = <<<EOD
      SELECT STRAIGHT_JOIN
          r.発送方法 AS method
        , COUNT(*) AS num
      FROM tb_delivery_statement_detail_num_order_list_result r
      INNER JOIN tb_productchoiceitems pci ON
          (pci.ne_syohin_syohin_code = substr(r.対象商品コード, instr(r.対象商品コード,':')+1,char_length(r.対象商品コード))
          OR (instr(r.対象商品コード,':') = 0 AND instr(r.対象商品コード,',') >= 1 AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード,',')-1)))
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_company c ON m.company_code = c.code
      WHERE r.warehouse_id IN ( :currentWarehouseId, :impossibleWarehouseId )
        AND c.id = :companyId
      GROUP BY r.発送方法
      ORDER BY
            CASE
              WHEN r.発送方法 = 'SHOPLIST' THEN 1
              WHEN r.発送方法 = '（出荷不能）' THEN 2
              ELSE 0
            END
          , num DESC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':currentWarehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
    $stmt->bindValue(':impossibleWarehouseId', TbDeliveryStatementDetailNumOrderListInfoRepository::SHIPPING_IMPOSSIBLE_WAREHOUSE_ID, \PDO::PARAM_INT);
    $stmt->bindValue(':companyId', $account->getCompanyId(), \PDO::PARAM_INT);
    $stmt->execute();
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $row['hash'] = md5($row['method']);
      $deliveryMethods[] = $row;
    }

    // 順位付けした伝票一覧を取得
    $result = [];
    foreach($deliveryMethods as $method) {
      $methodName = $method['method'];

      $sql = <<<EOD
        SELECT STRAIGHT_JOIN
          r.*
        FROM tb_delivery_statement_detail_num_order_list_result r
        INNER JOIN tb_productchoiceitems pci ON
          (pci.ne_syohin_syohin_code = substr(r.対象商品コード, instr(r.対象商品コード,':')+1,char_length(r.対象商品コード))
          OR (instr(r.対象商品コード,':') = 0 AND instr(r.対象商品コード,',') >= 1 AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード,',')-1)))
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_company c ON m.company_code = c.code
        WHERE r.発送方法 = :deliveryMethod
          AND r.warehouse_id IN ( :currentWarehouseId, :impossibleWarehouseId )
          AND c.id = :companyId
        ORDER BY r.id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryMethod', $methodName);
      $stmt->bindValue(':currentWarehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
      $stmt->bindValue(':impossibleWarehouseId', TbDeliveryStatementDetailNumOrderListInfoRepository::SHIPPING_IMPOSSIBLE_WAREHOUSE_ID, \PDO::PARAM_INT);
      $stmt->bindValue(':companyId', $account->getCompanyId(), \PDO::PARAM_INT);
      $stmt->execute();

      $result[$methodName] = [];
      $orderListResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      $result[$methodName] = array_reduce($orderListResult, function($subResult, $row) {
        $subResult[$row['page']][] = $row;
        return $subResult;
      }, []);
    }

    $data['result'] = $result;

    // 集計データ取得
    // 月次データ
    $sql = <<<EOD
      SELECT
          DATE_FORMAT(v.印刷予定日, '%Y-%m') AS 出荷予定年月
        , DATE_FORMAT(v.印刷予定日, '%Y')    AS year
        , DATE_FORMAT(v.印刷予定日, '%m')    AS month
        , COUNT(*) AS 明細件数
        , COUNT(DISTINCT v.`伝票番号`) AS 伝票件数
        , COUNT(DISTINCT v.代表商品コード) AS 代表商品コード数
        , COUNT(DISTINCT v.`商品コード`) AS SKU数
        , SUM(v.`受注数`) AS 商品個数
      FROM v_sales_detail_shipping_date v
      INNER JOIN tb_mainproducts m ON v.代表商品コード = m.daihyo_syohin_code
      INNER JOIN tb_company c ON m.company_code = c.code
      WHERE c.id = :companyId
      GROUP BY DATE_FORMAT(v.印刷予定日, '%Y-%m')
      ORDER BY DATE_FORMAT(v.印刷予定日, '%Y-%m')
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':companyId', $account->getCompanyId(), \PDO::PARAM_INT);
    $stmt->execute();

    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $data['monthly'][$row['出荷予定年月']] = $row;
    }

    // 日次データ
    $sql = <<<EOD
      SELECT
          v.印刷予定日 AS 出荷予定年月日
        , DATE_FORMAT(v.印刷予定日, '%Y') AS year
        , DATE_FORMAT(v.印刷予定日, '%m') AS month
        , COUNT(*) AS 明細件数
        , COUNT(DISTINCT v.`伝票番号`) AS 伝票件数
        , COUNT(DISTINCT v.代表商品コード) AS 代表商品コード数
        , COUNT(DISTINCT v.`商品コード`) AS SKU数
        , SUM(v.`受注数`) AS 商品個数
      FROM v_sales_detail_shipping_date v
      INNER JOIN tb_mainproducts m ON v.代表商品コード = m.daihyo_syohin_code
      INNER JOIN tb_company c ON m.company_code = c.code
      WHERE c.id = :companyId
      GROUP BY v.印刷予定日
      ORDER BY v.印刷予定日
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':companyId', $account->getCompanyId(), \PDO::PARAM_INT);
    $stmt->execute();

    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $data['daily'][$row['year']][$row['month']][] = $row;
    }

    // 出荷量
    $sql = <<<EOD
    SELECT
      order_amount
      , stock_move_amount
      , order_amount + stock_move_amount AS total 
    FROM
      tb_calculated_shipment_amount 
    WHERE
      warehouse_id = :currentWarehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':currentWarehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
    $stmt->execute();
    $calcResult = $stmt->fetch(\PDO::FETCH_ASSOC);

    // 画面表示
    return $this->render('AppBundle:Delivery:statement-detail-product-num-list.html.twig', [
      'account' => $account
      , 'borderNum' => TbDeliveryStatementDetailNumOrderListInfoRepository::STATEMENT_DETAIL_PRODUCT_NUM_LIST_BORDER_DETAIL_NUM
      , 'borderDate' => $settingInfo['shipping_date']
      , 'pageItemNum' => $settingInfo['page_item_num']
      , 'lastUpdatedAccountName' => $settingInfo['account_name']
      , 'lastUpdated'  => $settingInfo['last_updated']
      , 'updateNumber' => $settingInfo['update_number']
      , 'data' => $data
      , 'deliveryMethods' => $deliveryMethods
      , 'today' => (new \DateTime())->setTime(0, 0, 0)
      , 'now' => (new \DateTime())
      , 'calcResult' => $calcResult

      , 'settingId' => TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID
    ]);
  }


  /**
   * SHOPLIST 有効化伝票一覧 画面
   * 取得条件： 「受注メール取込済」で出荷予定日（即納1営業日）が指定日以前
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function statementDetailProductNumListShoplistAction(Request $request)
  {
    $account = $this->getLoginUser();

    $data = [
      'results' => []
    ];

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // 設定値取得
    /** @var TbDeliveryStatementDetailNumOrderListInfoRepository $repoSetting */
    $repoSetting = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryStatementDetailNumOrderListInfo');
    $settingInfo = $repoSetting->getSettingInfo(TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_SHOPLIST);

    // 発送方法取得
    // -- 発送方法取得
    $deliveryMethods = [];
    $sql = <<<EOD
      SELECT
          発送方法 AS method
        , COUNT(*) AS num
      FROM tb_delivery_statement_detail_num_order_list_result_shoplist
      GROUP BY 発送方法
      ORDER BY num DESC
EOD;
    $stmt = $dbMain->query($sql);
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $row['hash'] = md5($row['method']);
      $deliveryMethods[] = $row;
    }

    // 順位付けした伝票一覧を取得
    $result = [];
    foreach($deliveryMethods as $method) {
      $methodName = $method['method'];

      $sql = <<<EOD
        SELECT
          *
        FROM tb_delivery_statement_detail_num_order_list_result_shoplist r
        WHERE r.発送方法 = :deliveryMethod
        ORDER BY r.id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':deliveryMethod', $methodName);
      $stmt->execute();

      $result[$methodName] = [];
      $detailNum = 0; // ページ明細件数
      $page = 1;
      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($result[$methodName][$page])) {
          $result[$methodName][$page] = [];
        }
        $result[$methodName][$page][] = $row;

        // ページ切り替え
        $detailNum += $row['明細数'];
        if ($detailNum >= $settingInfo['page_item_num']) {
          $page++;
          $detailNum = 0;
        }
      }
    }

    $data['result'] = $result;

    // 画面表示
    return $this->render('AppBundle:Delivery:statement-detail-product-num-list-shoplist.html.twig', [
      'account' => $account
      , 'borderNum' => TbDeliveryStatementDetailNumOrderListInfoRepository::STATEMENT_DETAIL_PRODUCT_NUM_LIST_BORDER_DETAIL_NUM
      , 'borderDate' => $settingInfo['shipping_date']
      , 'pageItemNum' => $settingInfo['page_item_num']
      , 'lastUpdatedAccountName' => $settingInfo['account_name']
      , 'lastUpdated'  => $settingInfo['last_updated']
      , 'updateNumber' => $settingInfo['update_number']
      , 'data' => $data
      , 'deliveryMethods' => $deliveryMethods
      , 'today' => (new \DateTime())->setTime(0, 0, 0)

      , 'settingId' => TbDeliveryStatementDetailNumOrderListInfoRepository::SETTING_ID_SHOPLIST
    ]);
  }

  /**
   * 納品書印刷待ち伝票一覧 再集計処理 キュー追加(Ajax) ※ 進捗確認のため、jobRequestを利用
   * @param int $settingId
   * @param Request $request
   * @return Response
   */
  public function enqueueRefreshStatementDetailProductNumListAction($settingId, Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    /**  @var TbShippingVoucherAutoGenerateRepository $autoRepo */
    $autoRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');

    try {
      $autoRepo->truncate();

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      $key = $stringUtil->getUniqueId('ds');

      /** @var JobRequestRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:JobRequest');

      $expiredAt = (new \DateTime())->modify('+2 minutes'); // singletonのため、NEW状態での有効期限はできるだけ短く
      $options = [
        'settingId'           => $settingId
        , 'shippingDate'        => $request->get('shippingDate')
        , 'pageItemNum'         => $request->get('pageItemNum')
        , 'changeLocationOrder' => $request->get('changeLocationOrder')
        , 'account'             => $this->getLoginUser()->getId()
      ];

      $jobRequest = $repo->createJobRequest(
        $key
        , BaseJob::COMMAND_KEY_REFRESH_DELIVERY_STATEMENT_DETAIL_PRODUCT_NUM_LIST
        , $expiredAt
        , $this->getLoginUser()->getClientName()
        , $options
        , $singleton = true
      );

      // jobRequest 実行処理へリダイレクト。
      return $this->redirectToRoute('api_job_request', [ 'key' => $key ], Response::HTTP_FOUND);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();

      return new JsonResponse($result);
    }
  }

  /**
   * 倉庫在庫ピッキングリスト 更新キュー追加
   */
  public function enqueueRefreshWarehouseStockMoveListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try {

      // 実行チェック処理
      // 倉庫在庫ピッキングでロケーション未作成のものが残っていればエラー。
      /** @var TbWarehouseStockMovePickingListRepository $repoWarehouseStockMovePicking */
      $repoWarehouseStockMovePicking = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $unlocated = $repoWarehouseStockMovePicking->findUnlocatedPickedCount((new \DateTime())->format('Y-m-d'));
      if ($unlocated) {
        $list = [];
        foreach($unlocated as $row) {
          $list[] = sprintf('%s (%d件)', $row['warehouse_name'], $row['num']);
        }
        $message = sprintf("ロケーション未作成の倉庫在庫ピッキングがあります。 \n\n%s", implode("\n", $list));

        throw new \RuntimeException($message);
      }

      $resque = $this->getResque();

      // 在庫移動一覧 更新
      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
        'command'  => MainJob::COMMAND_KEY_REFRESH_WAREHOUSE_STOCK_MOVE_LIST
        , 'account' => $this->getLoginUser()->getId()
        , 'skipRefreshStatementDetail' => 1 /* 納品書印刷待ち伝票一覧の再集計はスキップ */
        , 'targetWarehouseId' => $request->get('warehouseId')
      ];
      $resque->enqueue($job); // リトライなし

      $logger->info('倉庫在庫ピッキングリスト 更新キュー追加');
      $result['message'] = '倉庫在庫ピッキングリストの更新処理をキューに追加しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 納品書印刷 出荷リストを一括生成
   * @param Request $request
   * @return JsonResponse
   */
  public function enqueueCsvDownloadAndUpdateShippingVoucherAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $dbMain = $this->getDoctrine()->getConnection('main');
    $em = $this->getDoctrine()->getManager('main');
    $form = $request->get('form');
    /**  @var TbShippingVoucherAutoGenerateRepository $autoRepo */
    $autoRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');

    $result = [];
    try {
      // トランザクション開始　出荷リスト自動生成時は
      $dbMain->beginTransaction();

      $deliveryId = $this->fetchDeliveryMethodId($form);
      // 梱包グループを登録
      $packingGroup = new TbShippingVoucherPackingGroup();
      $packingGroup->setDeliveryMethodId($deliveryId);
      $packingGroup->setName('');
      $packingGroup->setStatus(TbShippingVoucherPackingGroup::STATUS_NONE);
      $packingGroup->setShippingVoucherPdfFilename('');
      $packingGroup->setPackingComment('');
      $em->persist($packingGroup);
      $em->flush();

      // 出荷リスト自動生成登録(バルクインサート)
      $warehouseId = intval($form['warehouseId']);
      $deliveryMethod = $form['deliveryMethod'];
      $packingGroupId = $packingGroup->getId();
      $companyId = $this->getLoginUser()->getCompanyId();
      $shippingVoucherAutoList = array_map(function($page) use($warehouseId, $deliveryMethod, $packingGroupId, $companyId) {
        $targetNum = $this->fetchTargetNum($warehouseId, $deliveryMethod, $page);
        $shippingVoucherAuto = new TbShippingVoucherAutoGenerate();
        $shippingVoucherAuto->setPackingGroupId($packingGroupId);
        $shippingVoucherAuto->setCompanyId($companyId);
        $shippingVoucherAuto->setWarehouseId($warehouseId);
        $shippingVoucherAuto->setDeliveryMethod($deliveryMethod);
        $shippingVoucherAuto->setPage($page);
        $shippingVoucherAuto->setStatus(TbShippingVoucherAutoGenerate::STATUS_REGISTERED);
        $shippingVoucherAuto->setTargetNum($targetNum);
        $shippingVoucherAuto->setAccountId($this->getLoginUser()->getId());
        return $shippingVoucherAuto;
      }, range(intval($form['startPage']), intval($form['endPage'])));
      $autoRepo->bulkInsert($shippingVoucherAutoList);

      // 出荷リスト自動生成バッチが動作していない場合、バッチ呼び出し
      if (!$this->findQueuesByCommandName('nonExclusive', NonExclusiveJob::COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER)) {
        $resque = $this->getResque();
        $job = new NonExclusiveJob();
        $job->queue = 'nonExclusive'; // キュー名
        $job->args = [
        'command'   => NonExclusiveJob::COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER
        , 'target-env' => $this->get('kernel')->getEnvironment()
        , 'account'   => $this->getLoginUser()->getId()
        ];
        $resque->enqueue($job);
        $result['message'] = '出荷リスト自動生成をキューに追加しました。';
      }

      $dbMain->commit();
      $result['message'] = sprintf(
        '%s～%sページを自動生成に登録しました。進捗は出荷リスト自動生成履歴画面をご覧ください。'
        , $form['startPage']
        , $form['endPage']
        );
      $result['status'] = 'ok';
    } catch (Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      $logger->error('出荷リストを一括生成時エラー発生' . $e->getMessage() . $e->getTraceAsString());
    }
    return new JsonResponse($result);
  }

  /**
   * 入力フォームの値から配送方法IDを取得する。
   * 条件に一致した配送方法IDが複数ある場合またはnullだった場合は0を返す。
   * @param array $form 入力フォームの連想配列
   * @return int 配送方法ID
   */
  private function fetchDeliveryMethodId($form)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');
    $account = $this->getLoginUser();

    $deliveryId = 0;
    $sql = <<<EOD
        SELECT STRAIGHT_JOIN
          DISTINCT a.配送方法コード AS deliveryId
        FROM
          tb_delivery_statement_detail_num_order_list_result r
          INNER JOIN tb_productchoiceitems pci
            ON (
              pci.ne_syohin_syohin_code = substr(
                r.対象商品コード
                , instr(r.対象商品コード, ':') + 1
                , char_length(r.対象商品コード)
              )
              OR (
                instr(r.対象商品コード, ':') = 0
                AND instr(r.対象商品コード, ',') >= 1
                AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード, ',') - 1)
              )
            )
          INNER JOIN tb_mainproducts m
            ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_company c
            ON m.company_code = c.code
          INNER JOIN tb_sales_detail_analyze a
            ON a.伝票番号 = CAST(r.伝票番号 AS SIGNED)
        WHERE
          r.発送方法 = :deliveryMethod
          AND r.warehouse_id = :warehouseId
          AND c.id = :companyId
          AND r.page >= :startPage
          AND r.page <= :endPage
          AND a.受注状態 <> :orderStatusMailImported
          AND a.配送方法コード <> ''
        ORDER BY
          r.id;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryMethod', $form['deliveryMethod']);
    $stmt->bindValue(':warehouseId', $form['warehouseId'], \PDO::PARAM_INT);
    $stmt->bindValue(':companyId', $account->getCompanyId(), \PDO::PARAM_INT);
    $stmt->bindValue(':startPage', $form['startPage'], \PDO::PARAM_INT);
    $stmt->bindValue(':endPage', $form['endPage'], \PDO::PARAM_INT);
    $stmt->bindValue(':orderStatusMailImported', TbSalesDetail::ORDER_STATUS_MAIL_IMPORTED);
    $stmt->execute();

    $deliveryIdList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if (count($deliveryIdList) === 1 && current($deliveryIdList)['deliveryId'] != null) {
      $deliveryId = intval(current($deliveryIdList)['deliveryId']);
    }
    return $deliveryId;
  }

  /**
   * 引数が一致する納品書印刷待ち集計データの数を返す。
   * @param int $warehouseId 倉庫ID
   * @param string $deliveryMethod 発送方法
   * @param int $page ページ
   * @return int 納品書印刷待ち集計データの数
   */
  private function fetchTargetNum($warehouseId, $deliveryMethod, $page)
  {
    $account = $this->getLoginUser();
    $dbMain =  $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
        SELECT STRAIGHT_JOIN
          r.伝票番号 AS voucehrNumber
        FROM
          tb_delivery_statement_detail_num_order_list_result r
          INNER JOIN tb_productchoiceitems pci
            ON (
              pci.ne_syohin_syohin_code = substr(
                r.対象商品コード
                , instr(r.対象商品コード, ':') + 1
                , char_length(r.対象商品コード)
              )
              OR (
                instr(r.対象商品コード, ':') = 0
                AND instr(r.対象商品コード, ',') >= 1
                AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード, ',') - 1)
              )
            )
          INNER JOIN tb_mainproducts m
            ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_company c
            ON m.company_code = c.code
        WHERE
          r.warehouse_id = :warehouseId
          AND r.発送方法 = :deliveryMethod
          AND r.page = :page
          AND c.id = :companyId;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouseId, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryMethod', $deliveryMethod, \PDO::PARAM_STR);
    $stmt->bindValue(':page', $page, \PDO::PARAM_INT);
    $stmt->bindValue(':companyId', $account->getCompanyId(), \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
  }

  /**
   * 出荷リスト自動生成履歴 表示
   */
  public function shippingVoucherAutoGenerateHistoryListAction()
  {
    // 出荷可能倉庫一覧を取得
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDown(true);

    // ステータス一覧を取得
    $statuses = TbShippingVoucherAutoGenerate::STATUS_LIST;

    return $this->render('AppBundle:Delivery:shipping-voucher-auto-generate-history.html.twig', [
      'warehouses' => $warehouses,
      'statuses' => $statuses
    ]);
  }

  /**
   * 出荷リスト自動生成履歴 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherAutoGenerateHistoryFindAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok',
      'message' => null,
      'list' => []
    ];

    $conditions = $request->get('conditions');
    $warehouseId = $conditions['warehouseId'] === '' ? null : (int)$conditions['warehouseId'];
    $status = $conditions['status'] === '' ? null : (int)$conditions['status'];

    try {
      /** @var ShippingVoucherService $service */
      $service = $this->get('misc.service.shipping_voucher');
      $result['list'] = $service->findShippingVoucherAutoGenerateList($warehouseId, $status);
      // 再実行用URLを生成して、listに追加。
      $result['list'] = array_map(function($row) {
        $row['retryUrl'] = $this->generateUrl('delivery_shipping_voucher_auto_generate_history_retry', ['id' => $row['id']]);
        return $row;
      }, $result['list']);

    } catch (\Exception $e) {
      $logger->error("出荷リスト自動生成履歴 検索処理でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷リスト自動生成履歴 再実行
   * @param int $id
   * @return JsonResponse
   */
  public function shippingVoucherAutoGenerateHistoryRetryAction($id)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var TbShippingVoucherAutoGenerateRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');
    /** @var TbDeliveryMethodRepository $dmRepo */
    $pgRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');

    $result = [
      'status' => 'ok',
      'message' => null
    ];

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    $accountId = $this->getLoginUser()->getId();

    try {
      // トランザクション開始
      $dbMain->beginTransaction();

      $em = $this->getDoctrine()->getManager('main');

      /** @var TbShippingVoucherAutoGenerate $autoGenerate */
      $autoGenerate = $repo->find($id);

      $packingGroupId = $autoGenerate->getPackingGroupId();
      $companyId = $autoGenerate->getCompanyId();
      $warehouseId = $autoGenerate->getWarehouseId();
      $deliveryMethod = $autoGenerate->getDeliveryMethod();
      $page = $autoGenerate->getPage();

      // 新規の梱包グループを作成。
      $packingGroup = new TbShippingVoucherPackingGroup();
      $packingGroup->setDeliveryMethodId($pgRepo->find($packingGroupId)->getDeliveryMethodId());
      $packingGroup->setName('');
      $packingGroup->setStatus(TbShippingVoucherPackingGroup::STATUS_NONE);
      $packingGroup->setShippingVoucherPdfFilename('');
      $packingGroup->setPackingComment('');
      $em->persist($packingGroup);
      $em->flush();

      // 新規の出荷リスト自動生成を作成。(上で作成の梱包グループIDを使用。)
      $newAutoGenerate = new TbShippingVoucherAutoGenerate;
      $newAutoGenerate->setPackingGroupId($packingGroup->getId());
      $newAutoGenerate->setCompanyId($companyId);
      $newAutoGenerate->setWarehouseId($warehouseId);
      $newAutoGenerate->setDeliveryMethod($deliveryMethod);
      $newAutoGenerate->setPage($page);
      $newAutoGenerate->setStatus(TbShippingVoucherAutoGenerate::STATUS_REGISTERED);
      $newAutoGenerate->setTargetNum($autoGenerate->getTargetNum());
      $newAutoGenerate->setFileName($autoGenerate->getFileName());
      $newAutoGenerate->setAccountId($autoGenerate->getAccountId());
      $em->persist($newAutoGenerate);
      $em->flush();

      // ステータスを、エラー(再生成済)に更新。
      $repo->updateStatus($id, TbShippingVoucherAutoGenerate::STATUS_ERROR_REGENERATED);

      // コミット
      $dbMain->commit();

      // 出荷リスト自動生成バッチが動作していない場合、バッチ呼び出し
      if (!$this->findQueuesByCommandName('nonExclusive', NonExclusiveJob::COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER)) {
        $rescue = $this->getResque();
        $job = new NonExclusiveJob();
        $job->queue = 'nonExclusive'; // キュー名
        $job->args = [
          'command' => NonExclusiveJob::COMMAND_CSV_DOWNLOAD_AND_UPDATE_SHIPPING_VOUCHER,
          'account' => $accountId,
        ];
        $rescue->enqueue($job);
      }

    } catch (\Exception $e) {
      // ロールバック
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }

      $logger->error("出荷リスト自動生成履歴 再実行処理でエラー発生" . ':' . $e->getMessage() . $e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * ピッキングリストインポート処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function importPickingListAction(Request $request)
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
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $uploadedFile = null;
      foreach ($request->files as $file) {
        if ($file instanceof UploadedFile && ($file->getMimeType() == 'text/plain' || strpos($file->getMimeType(), 'csv') !== false)) {
          $uploadedFile = $file;
        }
      }
      if (!$uploadedFile) {
        $result['status'] = 'ng';
        $result['message'] = 'CSVファイルが正しくアップロードされませんでした。';
      } else {

        // SJIS => UTF-8
        $tmpFile = tmpFile();
        $fp = fopen($uploadedFile->getPathname(), 'rb');
        while ($line = fgets($fp)) {
          $line = mb_convert_encoding(trim($line), 'UTF-8', 'SJIS-win') . "\n";
          fputs($tmpFile, $line);
        }

        $meta = stream_get_meta_data($tmpFile);
        $tmpFileName = isset($meta['uri']) ? $meta['uri'] : null;
        if (!$tmpFileName) {
          throw new \RuntimeException('一時ファイルの作成に失敗しました。');
        }

        // 書式確認
        fseek($tmpFile, 0);
        $header = fgets($tmpFile);
        if (!preg_match('/^"日時","商品コード","商品名","フリー在庫数","在庫数","総ピッキング数","ロケーションコード","型番","janコード","仕入先コード","仕入先名"$/', $header)) {
          $result['message'] = 'CSVの書式が違うようです。';
          throw new \RuntimeException($result['message']);
        }

        // 同一ファイル確認
        $fileHash = sha1(file_get_contents($tmpFileName));

        $sql = <<<EOD
          SELECT
            COUNT(*) AS cnt
          FROM tb_delivery_picking_list
          WHERE `date` = CURRENT_DATE
            AND file_hash = :fileHash
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':fileHash', $fileHash);
        $stmt->execute();
        $count = $stmt->fetchColumn(0);
        if ($count > 0) {
          $result['message'] = 'すでに本日アップロードされた内容です。';
          throw new \RuntimeException($result['message']);
        }

        // テーブル更新処理
        $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_picking_dl");

        $sql = <<<EOD
          CREATE TEMPORARY TABLE tmp_work_picking_dl (
              日時 DATETIME NOT NULL
            , 商品コード VARCHAR (255)
            , 商品名 VARCHAR (255)
            , フリー在庫数 INT (10) UNSIGNED
            , 在庫数 INT (10) UNSIGNED
            , 総ピッキング数 INT (10) UNSIGNED
            , ロケーションコード VARCHAR (255)
            , 型番 VARCHAR (255)
            , janコード VARCHAR (255)
            , 仕入先コード VARCHAR (255)
            , 仕入先名 VARCHAR (255)
          );
EOD;
        $dbMain->query($sql);

        $sql = <<<EOD
          LOAD DATA LOCAL INFILE :filePath
          INTO TABLE tmp_work_picking_dl
          FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
          LINES TERMINATED BY '\n'
          IGNORE 1 LINES
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':filePath', $tmpFileName);
        $stmt->execute();

        // 当日出力連番取得
        $sql = <<<EOD
          SELECT
            MAX(number) AS number
          FROM tb_delivery_picking_list dpl
          WHERE dpl.`date` = CURRENT_DATE
EOD;
        $pickingListNumber = intval($dbMain->query($sql)->fetchColumn(0)) + 1;

        // WEBピッキングリスト データ作成
        $sql = <<<EOD
          INSERT INTO tb_delivery_picking_list (
              `日時`
            , `商品コード`
            , `商品名`
            , `フリー在庫数`
            , `在庫数`
            , `総ピッキング数`
            , `ロケーションコード`
            , `型番`
            , `janコード`
            , `仕入先コード`
            , `仕入先名`
            , `date`
            , `file_hash`
            , `number`
            , `account`
          )
          SELECT
              `日時`
            , `商品コード`
            , `商品名`
            , `フリー在庫数`
            , `在庫数`
            , `総ピッキング数`
            , `ロケーションコード`
            , `型番`
            , `janコード`
            , `仕入先コード`
            , `仕入先名`
            , CURRENT_DATE AS `date`
            , :fileHash AS `file_hash`
            , :pickingListNumber AS number
            , :account AS `account`
          FROM tmp_work_picking_dl p
EOD;

        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':fileHash', $fileHash);
        $stmt->bindValue(':pickingListNumber', $pickingListNumber);
        $stmt->bindValue(':account', $account->getUsername());
        $stmt->execute();

        $result = [
          'status' => 'ok'
          , 'message' => sprintf('ピッキングリストの取込を完了しました。（番号: %d）', $pickingListNumber)
        ];
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : 'ピッキングリスト取込処理に失敗しました。';
    }

    return new JsonResponse($result);
  }


  /**
   * ピッキングリスト 作成処理(Ajax)
   * @param Request $request
   * @return JsonResponse
   */
  public function createPickingListByOrderNumberAction(Request $request)
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
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $numbersStr = $request->get('numbers');
      $numbersStr = trim(str_replace("\r", "\n", str_replace("\r\n", "\n", $numbersStr)));
      $logger->info($numbersStr);

      $numbers = explode("\n", $numbersStr);

      if (!strlen($numbersStr) || !$numbers) {
        $result['status'] = 'ng';
        $result['message'] = '伝票番号を入力してください。';
        throw new \RuntimeException('no numbers');
      }

      foreach($numbers as $i => $number) {
        $number = mb_convert_kana(trim($number), 'a');
        if (!preg_match('/^\d+$/', $number)) {
          throw new \RuntimeException('数字以外の文字が入力されています。[' . $number . ']');
        }

        $numbers[$i] = $number;
      }

      $orderNumbersConditionStr = implode(',', $numbers);

      // 仮のfileHash作成（一応入れておく）
      $fileHash = sha1($numbersStr . microtime());

      // テーブル更新処理
      // tb_productchoiceitems のトリガの影響で、INSERT ～ SELECTができないため、一時テーブルを介する。
      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_picking_dl");

      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_picking_dl (
            日時 DATETIME NOT NULL
          , 商品コード VARCHAR (255)
          , 商品名 VARCHAR (255)
          , フリー在庫数 INT (10) UNSIGNED
          , 在庫数 INT (10) UNSIGNED
          , 総ピッキング数 INT (10) UNSIGNED
          , ロケーションコード VARCHAR (255)
          , 型番 VARCHAR (255)
          , janコード VARCHAR (255)
          , 仕入先コード VARCHAR (255)
          , 仕入先名 VARCHAR (255)
        );
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        INSERT INTO tmp_work_picking_dl (
            日時
          , 商品コード
          , 商品名
          , フリー在庫数
          , 在庫数
          , 総ピッキング数
          , ロケーションコード
          , 型番
          , janコード
          , 仕入先コード
          , 仕入先名
        )
        SELECT
              NOW() AS 日時
            , T.ne_syohin_syohin_code AS `商品コード`
            , m.daihyo_syohin_name AS `商品名`
            , pci.`フリー在庫数` AS `フリー在庫数`
            , pci.`在庫数` AS `在庫数`
            , T.受注数 AS `総ピッキング数`
            , pci.location AS `ロケーションコード`
            , '' AS `型番`
            , '' AS `janコード`
            , m.sire_code AS `仕入先コード`
            , v.sire_name AS `仕入先名`
        FROM (
          SELECT
              a.`商品コード（伝票）`  AS ne_syohin_syohin_code
            , SUM(a.`受注数`) AS 受注数
          FROM tb_sales_detail_analyze a
          WHERE a.伝票番号 IN (
              {$orderNumbersConditionStr}
            )
            AND a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
          GROUP BY a.`商品コード（伝票）`
        ) T
        INNER JOIN tb_productchoiceitems pci ON T.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_vendormasterdata v ON m.sire_code = v.sire_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // 当日出力連番取得
      $sql = <<<EOD
        SELECT
          MAX(number) AS number
        FROM tb_delivery_picking_list dpl
        WHERE dpl.`date` = CURRENT_DATE
EOD;
      // 現在の選択倉庫
      $currentWarehouse = $this->getLoginUser()->getWarehouse()->getId();
      $pickingListNumber = intval($dbMain->query($sql)->fetchColumn(0)) + 1;

      // WEBピッキングリスト データ作成
      $sql = <<<EOD
        INSERT INTO tb_delivery_picking_list (
            `日時`
          , `商品コード`
          , `商品名`
          , `フリー在庫数`
          , `在庫数`
          , `総ピッキング数`
          , `ロケーションコード`
          , `型番`
          , `janコード`
          , `仕入先コード`
          , `仕入先名`
          , `date`
          , `file_hash`
          , `number`
          , `account`
          , `warehouse_id`
        )
        SELECT
            `日時`
          , `商品コード`
          , `商品名`
          , `フリー在庫数`
          , `在庫数`
          , `総ピッキング数`
          , `ロケーションコード`
          , `型番`
          , `janコード`
          , `仕入先コード`
          , `仕入先名`
          , CURRENT_DATE AS `date`
          , :fileHash AS `file_hash`
          , :pickingListNumber AS number
          , :account AS `account`
          , :warehouseId AS `warehouse_id`
        FROM tmp_work_picking_dl p
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':fileHash', $fileHash);
      $stmt->bindValue(':pickingListNumber', $pickingListNumber);
      $stmt->bindValue(':account', $account->getUsername());
      $stmt->bindValue(':warehouseId', $currentWarehouse);
      $stmt->execute();

      $result = [
        'status' => 'ok'
        , 'message' => sprintf('ピッキングリストの取込を完了しました。（番号: %d）', $pickingListNumber)
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 倉庫在庫移動残件数取得(Ajax)
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function stockMoveWarehouseRemainNumberAction(Request $request)
  {
    /** @var TbWarehouseStockMovePickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');

    // 全倉庫移動ピッキング残件数取得
    $conditions = [];
    $conditions['status'] = TbWarehouseStockMovePickingListRepository::PICKING_STATUS_NONE;
    $result = [
        'status' => 'ok'
    ];
    $result['remain_list'] = $repo->getRemainNumberEachWarehouse($conditions);
    return new JsonResponse($result);
  }

  /**
   * 注残ステータス設定日付 一覧
   * @param Request $request
   * @return Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function remainOrderStatusDateListAction(Request $request)
  {
    /** @var TbIndividualorderhistoryRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

    // これは forest.plusnao.co.jp 側からの依頼先ユーザアクセス
    if (is_null($request->get('all_flag'))) {
      $allFlag = true;
    } else {
      $allFlag = boolval($request->get('all_flag'));
    }

    // 取得条件
    $agentCode = "";
    if(strlen($request->get('agent'))){
      $agentCode = $request->get('agent');
    }

    $dateStart = "";
    if(strlen($request->get('date_start'))){
      $dateStart = $request->get('date_start');
    }

    $dateEnd = "";
    if(strlen($request->get('date_end'))){
      $dateEnd = $request->get('date_end');
    }

    $conditions = [
      'dateStart' => $dateStart
      , 'dateEnd' => $dateEnd
      , 'agentCode' => $agentCode
    ];

    $data = array();
    if(strlen($dateStart) && strlen($dateEnd)){
      $data = $repo->getRemainDateTotalCountList($conditions);
    }

    /** @var BaseRepository $repoAgent */
    $repoAgent = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
    /** @var PurchasingAgent[] $list */
    $agentList = $repoAgent->findBy([], [ 'display_order' => 'asc' ]);
    /** @var PurchasingAgent agent */
    $agent = $agentCode ? $repoAgent->find($agentCode) : null;

    // 画面表示
    return $this->render('AppBundle:Delivery:remain-order-status-date-list.html.twig', [
      'account' => $this->getLoginUser()
      , 'dataJson' => json_encode($data)
      , 'allFlag' => $allFlag
      , 'agentCode' => $agentCode
      , 'agentList' => $agentList
      , 'conditions' => $conditions
      , 'submitUrl' => (
          (!$allFlag && $agent)
        ? $this->generateUrl('plusnao_vendor_remain_order_status_date_list', [ 'agentName' => $agent->getLoginName() ])
        : $this->generateUrl('delivery_remain_order_status_date_list')
      )
      , 'loadPersonUrl' => (
          (!$allFlag && $agent)
        ? $this->generateUrl('plusnao_vendor_remain_order_status_person_list', [ 'agentName' => $agent->getLoginName() ])
        : $this->generateUrl('delivery_remain_order_status_person_list')
      )
    ]);
  }

  /**
   * 注残ステータス設定日付 一覧 作業者別集計取得
   * @param Request $request
   * @return Response
   */
  public function remainOrderStatusDateListByPersonAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'list' => []
    ];

    try {
      $agentCode = $request->get('agent');
      $dateStr = $request->get('date');
      $date = $dateStr ? new \DateTime($dateStr) : null;
      $dateFrom = null;
      $dateTo = null;

      if (!$agentCode) {
        throw new \RuntimeException('依頼先が取得できませんでした。');
      }
      // 全取得
      if (!$date) {
        $dateFrom = $request->get('dateFrom') ? new \DateTime($request->get('dateFrom')) : null;
        $dateTo = $request->get('dateTo') ? new \DateTime($request->get('dateTo')) : null;
      }

      /** @var TbIndividualorderhistoryRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $result['list'] = $repo->getRemainDateTotalCountListByPerson($agentCode, $date, $dateFrom, $dateTo);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷リスト 一覧
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Exception
   */
  public function shippingVoucherListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    /** @var TbShippingVoucherRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');

    // ログイン中のユーザ情報を取得
    $account = $this->getLoginUser();
    // ログイン中の倉庫情報を取得
    $currentWarehouse = $account->getWarehouse();

    // 出荷可能倉庫一覧を取得（プルダウン作成のため）
    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDown(true);

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

    if (is_null($request->get('import_warehouse_id'))) {
      $importWarehouseId = $currentWarehouse->getId();
    } else if (strlen($request->get('import_warehouse_id'))) {
      $importWarehouseId = $request->get('import_warehouse_id');
    } else {
      $importWarehouseId = null;
    }

    if (is_null($request->get('page_limit'))) {
      $pagingLimit = 100;
    } else if (strlen($request->get('page_limit'))) {
      $pagingLimit = $request->get('page_limit');
    } else {
      $pagingLimit = 100;
    }

    if (is_null($request->get('import_account_name'))) {
      $importAccountName = $account->getUsername();
    } else if (strlen($request->get('import_account_name'))) {
      $importAccountName = $request->get('import_account_name');
    } else {
      $importAccountName = null;
    }

    $conditions = [
      'dateFrom' => $importDateFrom
      , 'dateTo' => $importDateTo
      , 'warehouseId' => $importWarehouseId
      , 'accountName' => $importAccountName
      , 'status' => null
      , 'pagingLimit' => $pagingLimit
    ];

    $pagination = $repo->findShippingList($conditions, [], $request->get('page', 1), $pagingLimit);
    $pagination->getItems();
    // array_count_values()がNULLを扱えないので空文字に変換
    $rawItems = $pagination->getItems();
    $parseItems = array_map(function($row) {
      if (is_null($row['packing_id'])) {
        $row['packing_id'] = '';
      }
      return $row;
    }, $rawItems);

    // 紐づく梱包グループIDの数を付与、NULLは1とする
    $packingIdGroups = array_count_values(array_column($parseItems, 'packing_id'));
    foreach ($parseItems as $key => $row) {
      $packingNum = 1;
      if (!empty($row['packing_id'])) {
        $packingNum = $packingIdGroups[$row['packing_id']];
      }
      $parseItems[$key]['packing_num'] = $packingNum;
    }
    $pagination->setItems($parseItems);

    // 画面表示
    return $this->render('AppBundle:Delivery:shipping-voucher-list.html.twig', [
      'account' => $account
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'conditions' => $conditions
      , 'warehouses' => $warehouses
    ]);
  }


  public function shippingVoucherListInitialDataAction(Request $request) {
    $logger = $this->get('misc.util.batch_logger');

    /* @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDown(true);
    $currentWarehouseId = $this->getLoginUser()->getWarehouse()->getId();

    // 絞込条件
    $conditions = $request->get('search', []);

    if (is_null($conditions['warehouseId'])) {
      $warehouseId = $currentWarehouseId;
    } else {
      $warehouseId = $conditions['warehouseId'];
    }

    // 結果をJSONで返す
    return new JsonResponse(
      [
        'status' => 'ok',
        'warehouseList' => $warehouses,
        'warehouseId' => $warehouseId,
      ]
    );
  }


  /**
   * 納品書CSV取り込み処理
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherImportCsvAction(Request $request)
  {
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'warning' => null
    ];

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $em = $this->getDoctrine()->getManager('main');

      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }


      // 出荷可能倉庫でなければエラー
      if (!$currentWarehouse->isShipmentEnabled()) {
        throw new \RuntimeException(sprintf('現在の倉庫 [%s] は出荷可能倉庫ではありません。', $currentWarehouse->getName()));
      }

      $uploadedFile = null;
      foreach ($request->files as $file) {
        // if ($file instanceof UploadedFile && ($file->getMimeType() == 'text/plain' || strpos($file->getMimeType(), 'csv') !== false)) {
        $uploadedFile = $file;
        // }
      }
      if (!$uploadedFile) {
        $result['status'] = 'ng';
        $result['message'] = 'CSVファイルが正しくアップロードされませんでした。';
        return new JsonResponse($result);
      }
      // SJIS => UTF-8
      $tmpFile = tmpFile();
      $fp = fopen($uploadedFile->getPathname(), 'rb');
      while ($line = fgets($fp)) {
        $line = mb_convert_encoding(trim($line), 'UTF-8', 'SJIS-win') . "\n";
        fputs($tmpFile, $line);
      }

      $meta = stream_get_meta_data($tmpFile);
      $tmpFileName = isset($meta['uri']) ? $meta['uri'] : null;
      if (!$tmpFileName) {
        throw new \RuntimeException('一時ファイルの作成に失敗しました。');
      }

      // 書式確認
      fseek($tmpFile, 0);
      $headers = fgetcsv($tmpFile, null, ',', '"', '"');
      if ($headers != self::$CSV_FIELDS_NE_SHIPPING_VOUCHER) {
        $result['message'] = 'CSVの書式が違うようです。';
        throw new \RuntimeException($result['message']);
      }

      // 同一ファイル確認
      $fileHash = sha1(file_get_contents($tmpFileName));
      $logger->info($fileHash);

      $sql = <<<EOD
        SELECT
          COUNT(*) AS cnt
        FROM tb_shipping_voucher
        WHERE `created` >= CURRENT_DATE
          AND file_hash = :fileHash
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':fileHash', $fileHash);
      $stmt->execute();
      $count = $stmt->fetchColumn(0);
      if ($count > 0) {
        $result['status'] = 'ng';
        $result['message'] = 'すでに本日アップロードされた内容です。';
        throw new \RuntimeException($result['message']);
      }

      $result = $this->manageShippingVoucherImport($account, $currentWarehouse, $fileHash, $tmpFileName);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : $e->getMessage();
    }

    return new JsonResponse($result);
  }

  public function shippingVoucherEditCommentAction(Request $request) {
    $logger = $this->get('misc.util.batch_logger');
    try {
      $result = [
        'status' => 'ok'
        , 'message' => null
      ];
      $packingId = $request->get('packing_id');
      $comment = $request->get('comment');

      $commentLen = mb_strlen($comment, 'UTF-8');
      if ($commentLen > 255) {
        $result['status'] = 'ng';
        $result['message'] = 'コメントは255文字以内で入力してください。';
        return new JsonResponse($result);
      }
      /** @var TbShippingVoucherPackingGroupRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');
      $repo->updateComment($packingId,$comment);
      // 結果をJSONで返す
      return new JsonResponse($result);
    } catch (\Exception $e) {
      $logger->error($e->getMessage() . ':' . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = 'システムエラーが発生しました。';

      return new JsonResponse($result);
    }
  }

  /**
   * 納品書登録管理
   * @param array $account nullable
   * @param array $currentWarehouse
   * @param string $filehash
   * @param string $tmpFileName
   * @return array $result
   */
  private function manageShippingVoucherImport($account = null, $currentWarehouse, $fileHash, $tmpFileName)
  {
    $service = $this->get('misc.service.shipping_voucher');
    $result = $service->manageShippingVoucherImport($account, $currentWarehouse, $fileHash, $tmpFileName);
    return $result;
  }

  /**
   * 出荷リスト画面　梱包グループごとの配送業者ラベル生成用CSVダウンロード
   * @param Request $request
   */
  public function shippinVoucherDownloadLabelCsvAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $packingId = $request->get('packing_id');
    $deliveryMethodId = $request->get('delivery_method_id');
    if (!$packingId || !$deliveryMethodId) {
      throw new \RuntimeException('梱包IDと配送方法コードを送信してください');
    }

    $now = $request->get('now'); // ファイル名を画面と合わせるためだけに利用。あまり意味ない。
    if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $now, $match)) {
      $now = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]));
    } else {
      $now = new \DateTime($now);
    }

    /** @var TbShippingVoucherRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $voucherNumbers = $repo->getVoucherNumberByPackingId($packingId);

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->get('batch.mall_process.next_engine');
    $response = $neMallProcess->generateShippingLabelCsv($deliveryMethodId, $voucherNumbers, $now);
    $response->send();
    return $response;
  }

  /**
   * 出荷リスト画面　梱包グループごとの配送業者ラベル生成用PDFダウンロード
   * @param Request $request
   */
  public function shippinVoucherDownloadLabelPdfAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $packingId = $request->get('packing_id');
    $deliveryMethodId = $request->get('delivery_method_id');
    if (!$packingId || !$deliveryMethodId) {
      throw new \RuntimeException('梱包IDと配送方法コードを送信してください');
    }

    /** @var TbShippingVoucherRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $voucherNumbers = $repo->getVoucherNumberByPackingId($packingId);
    
    /** @var ShippingLabelService $service */
    $service = $this->get('misc.service.shipping_label');
    $isDebug = $request->get('html');
    if ($isDebug === 'false') {
      $isDebug = false;
    }
    $response = $service->makeShippingLabelPdf($voucherNumbers, $deliveryMethodId, $isDebug);

    return $response;

  }

  /**
   * 納品書PDFダウンロード
   * @param Request $request
   * @return Response
   */
  public function shippingVoucherDownloadPdfAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      $dbMain = $this->getDoctrine()->getConnection('main');
      $now = new \DateTimeImmutable();

      $voucherId = $request->get('id');
      if (!$voucherId) {
        throw new \RuntimeException('no voucher id');
      }

      // データ取得
      $sql = <<<EOD
      SELECT
        w.symbol
        , v.warehouse_daily_number
      FROM tb_shipping_voucher v
      LEFT JOIN tb_warehouse   w ON v.warehouse_id = w.id
      WHERE v.id = :id;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':id', $voucherId);
      $stmt->execute();
      $warehouseSerialNumber = $stmt->fetch(\PDO::FETCH_ASSOC);

      /** @var TbShippingVoucherRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
      /** @var TbShippingVoucher $voucher */
      $voucher = $repo->find($voucherId);
      $data = $repo->getVoucherListByVoucherId($voucherId);
      if (!$data) {
        throw new \RuntimeException('no data');
      }

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      /** @var ImageUtil $imageUtil ※バーコード用 */
      $imageUtil = $this->get('misc.util.image');

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      $footerFilePath = dirname($fileUtil->getRootDir()) . '/src/AppBundle/Resources/views/Delivery/pdf/shipping-voucher-pdf-footer.html';

      /** @var LoggableGenerator $pdf */
      $pdf = $this->get('knp_snappy.pdf');
      $options = [
        'encoding' => 'utf-8'
        , 'page-size' => 'A4'
        , 'margin-top'    => '10mm'
        , 'margin-bottom' => '10mm'
        , 'margin-left'   => '10mm'
        , 'margin-right'  => '10mm'

        , 'footer-html' => $footerFilePath

        // , 'no-outline' => true
        // , 'disable-smart-shrinking' => true // これを設定すると、table中のページ送りで調節が不可能か
      ];
      foreach($options as $k => $v) {
        $pdf->setOption($k, $v);
      }

      // モール情報（納品書記述文言設定）全件取得
      /** @var TbShoppingMallRepository $repoMall */
      $repoMall = $this->getDoctrine()->getRepository('MiscBundle:TbShoppingMall');
      $mallInfoList = [];
      /** @var TbShoppingMall[] $malls */
      $malls = $repoMall->findAll();
      foreach($malls as $mall) {
        $mallInfoList[$mall->getNeMallName()] = $mall;
      }

      // フッターのページ数を伝票別にするために、1伝票ずつPDFを主力して結合する。
      $fs = new Filesystem();
      $tmpDir = null;
      $count = 0;
      while (true) {
        $tmpDir = $this->get('kernel')->getCacheDir() . '/delivery/voucher_' . microtime(true);
        if ($fs->exists($tmpDir)) {
          if ($count++ > 1000) {
            throw new \RuntimeException('PDFの一時ディレクトリが作成できませんでした。処理を終了します。' . $tmpDir);
          }

          $tmpDir = null;
          usleep(200000); // 0.2秒
          continue;
        }

        $logger->info('pdf tmp dir: ' . $tmpDir);
        $fs->mkdir($tmpDir, 0777);
        break;
      }

      // 表紙を出力。（連続印刷の区切り用）
      $shippingMethods = [];
      foreach($data as $voucherNumber => $list) {
        $method = $list['voucher']['発送方法'];
        if (!isset($shippingMethods[$method])) {
          $shippingMethods[$method] = [];
        }
        $shippingMethods[$method][] = $voucherNumber;
      }
      //foreach($shippingMethods as $voucherNumber => $methods) {
      //  sort($shippingMethods[$voucherNumber]);
      //}

      // 表紙を出力。（連続印刷の区切り用）
      $patterns = "";

      // ピッキングブロック取得（現在の集計データから無理矢理作成。きっとたぶんこの集計のはず実装）
      $sql = <<<EOD
        SELECT
          GROUP_CONCAT(tmp.block_code) as pattern
        FROM (
          SELECT
            VR.block_code as block_code
           ,count(*) as cnt
           ,pb.display_order
          FROM (
            SELECT
              DISTINCT
                CASE
                  WHEN INSTR(r.`対象商品コード`, ':') = 0 THEN NULL
                  WHEN SUBSTRING_INDEX(r.`対象商品コード`, ':', 1) = '-' THEN NULL
                  ELSE SUBSTRING_INDEX(r.`対象商品コード`, ':', 1)
                END
              as block_code
             ,r.`伝票番号`
          	FROM
              tb_delivery_statement_detail_num_order_list_result r
          ) VR
          INNER JOIN (
            SELECT
              d.voucher_id
             ,d.`伝票番号`
            FROM
              tb_shipping_voucher_detail d
            WHERE
              d.voucher_id = :currentId
            GROUP BY d.voucher_id, d.`伝票番号`
          ) VD ON VR.`伝票番号` = VD.伝票番号
          LEFT JOIN tb_delivery_picking_block pb on VR.block_code = pb.block_code
          WHERE VR.block_code IS NOT NULL AND VR.block_code <> ""
          GROUP BY VR.block_code
          ORDER BY cnt desc, pb.display_order desc, VR.block_code
        ) tmp
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':currentId', $voucherId, \PDO::PARAM_INT);
      $stmt->execute();

      $result = $stmt->fetch(\PDO::FETCH_ASSOC);
      if ($result) {
        $patterns = trim(trim($result['pattern']),",");
      }

      $html = $this->renderView('AppBundle:Delivery:pdf/shipping-voucher-cover-pdf.html.twig', [
          'voucher' => $voucher
        , 'warehouseSerialNumber' => $warehouseSerialNumber
        , 'shippingMethods' => $shippingMethods
        , 'patterns' => $patterns
      ]);
      $pdf->setOption('replace', [
        'voucher_number' => '-'
      ]);
      $fileName = sprintf('%s/shipping_voucher_%03d.pdf', $tmpDir, 0);
      $pdf->generateFromHtml($html, $fileName);

      // 納品書本体出力
      $index = 1;
      foreach($data as $voucherNumber => $list) {
        $overrideOptions = [
          'replace' => [
            'voucher_number' => $voucherNumber
          ]
        ];
        foreach($overrideOptions as $k => $v) {
          $pdf->setOption($k, $v);
        }

        // モール特定
        if (isset($mallInfoList[$list['voucher']['店舗名']])) {
          $mall = $mallInfoList[$list['voucher']['店舗名']];
        } else {
          $mall = $mallInfoList['Plus Nao 本店']; // デフォルトとして利用する。
        }

        // 明細は商品コード昇順
        usort($list['details'], function($a, $b) {
          return strcmp($a['商品コード'], $b['商品コード']);
        });

        // 特記事項置換
        $list['voucher']['納品書備考'] = htmlspecialchars($list['voucher']['納品書備考'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
        // タグ装飾
        $attentionWords = [
          '返金'
          , '同封返金'
          , '同梱返金'
          , '交換商品'
          , '領収書'
        ];
        $list['voucher']['納品書備考'] = preg_replace('/('  . implode('|', $attentionWords) . ')/', '<span class="voucherAttentionWord">$1</span>', $list['voucher']['納品書備考']);

        $html = $this->renderView('AppBundle:Delivery:pdf/shipping-voucher-pdf.html.twig', [
          'voucher' => $list['voucher']
          , 'details' => $list['details']
          , 'mall'    => $mall
        ]);

        // デバッグ用 HTML出力
        if ($request->get('html')) {
          return new Response($html, 200, [ 'Content-Type' => 'text/html' ]);
          // end of debug HTML OUTPUT
        }

        $fileName = sprintf('%s/shipping_voucher_%03d.pdf', $tmpDir, $index++);
        $pdf->generateFromHtml($html, $fileName);
      }

      // 結合
      $finder = new Finder();
      $files = $finder->in($tmpDir)->name('*.pdf')->files();
      if (!$files->count()) {
        throw new \RuntimeException('PDFファイルが作成されませんでした。（一時ファイルエラー）');
      }

      $outputFileName = sprintf('%s/shipping_voucher_%s_%06d.pdf', $tmpDir, $now->format('Ymd'), $voucherId);
      $tmpFileNames = [];
      /** @var SplFileInfo $file */
      foreach($files->sortByName() as $file) {
        $tmpFileNames[] = $file->getBasename();
      }

      // 結合実行
      $command = sprintf('/usr/bin/pdftk %s cat output %s', implode(' ', $tmpFileNames), $outputFileName);
      $logger->info($command . '(in : ' . $tmpDir . ')');
      $process = new Process($command, $tmpDir);
      $statusCode = $process->run();

      $logger->info($statusCode . ' : ' . $outputFileName);
      if (!$fs->exists($outputFileName)) {
        throw new \RuntimeException('PDFファイルが作成されませんでした。（結合エラー）' . implode($tmpFileNames) . ' => ' . $outputFileName);
      }

      $response = new Response(
        file_get_contents($outputFileName)
        , 200
        , array(
          'Content-Type'          => 'application/pdf'
//        , 'Content-Disposition'   => sprintf('attachment; filename="%s"', basename($outputFileName))
        )
      );

      // 一時ファイル群削除
      $fs->remove($tmpDir);

      return $response;

    } catch (\Exception $e) {
      // エラー時
      $logger->error($e->getMessage());

      // 一応、エラーの旨のみ出力しておく。
      return new Response(
        'pdf output error.'
        , 500
        , [
          'Content-Type' => 'text/plain'
        ]
      );
    }
  }

  /**
   * ピッキングリスト再作成処理
   * @param Request $request
   * @return JsonResponse
   */
  public function shippingVoucherRecreatePickingListAction(Request $request)
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
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $id = $request->get('id');

      /** @var TbShippingVoucherRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
      /** @var TbShippingVoucher $voucher */
      $voucher = $repo->find($id);
      if (!$voucher) {
        $result['message'] = '伝票が見つかりませんでした。 [' . $id . ']';
        throw new \RuntimeException($result['message']);
      }

      // 再作成処理：トランザクション開始
      $dbMain->beginTransaction();
      $em = $this->getDoctrine()->getManager('main');

      /** @var TbDeliveryPickingListRepository $repoPickingList */
      $repoPickingList = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');

      // ピッキングリスト作成済みなら、チェック＆削除
      $voucherPickingListDate = $voucher->getPickingListDate();
      if ($voucherPickingListDate && $voucher->getPickingListNumber()) {
        /** @var TbWarehouseRepository $repoWarehouse */
        $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
        /** @var TbWarehouse $warehouse */
        $warehouse = $repoWarehouse->find($voucher->getWarehouseId());
        $pickingListIndex = $repoPickingList->findListIndexOne($voucherPickingListDate->format('Y-m-d'), $voucher->getPickingListNumber(), $warehouse);

        // 未処理でないピッキングリストは再生成不可。
        if ($pickingListIndex) {
          if ($pickingListIndex['status'] !== TbDeliveryPickingListRepository::INDEX_STATUS_NONE) {
            $result['message'] = 'ピッキングリストが未処理ではないため、再作成できませんでした。';
            throw new \RuntimeException($result['message']);
          }

          $repoPickingList->deletePickingList($pickingListIndex);
          $em->refresh($voucher); // これをしないと、同じ日付・同じ番号（1など）でピッキングリストが作成されたときに $em->flush() で更新されない。Doctrineは不便。
        }
      }

      $this->createPickingListByShippingVoucher($voucher, $voucher->getPickingListDate(), $account, $this->getLoginUser()->getWarehouse());
      $logger->info(sprintf(
        '再作成 ピッキングリスト => %s : %d [%s-%d]'
        , $voucher->getPickingListDate()->format('Y-m-d H:i:s')
        , $voucher->getPickingListNumber()
        , $warehouse->getSymbol()
        , $voucher->getWarehouseDailyNumber()
      ));

      $em->persist($voucher); // ピッキングリスト番号の変更反映
      $em->flush();

      $dbMain->commit();

      $result = [
        'status' => 'ok'
        , 'message' => sprintf('ピッキングリストを再作成しました（ピッキングリスト番号: %d [%s-%d]）'
          , $voucher->getPickingListNumber()
          , $warehouse->getSymbol()
          , $voucher->getWarehouseDailyNumber()
        )
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : 'ピッキングリスト再作成処理に失敗しました。';
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷在庫移動一覧
   */
  public function shippingStockMoveListAction()
  {
    $today = (new \DateTime())->setTime(0, 0, 0);

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repoWarehouse->getPullDownObjects();

    /** @var TbWarehouseStockMovePickingListRepository $repoPicking */
    $repoPicking = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');

    $pickingList = $repoPicking->getAllOfADay();
    $activeWarehouseId = null;
    foreach($pickingList as $id => $list) { // 先頭がactive
      $activeWarehouseId = $id;
      break;
    }

    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->get('misc.util.db_common');
    $warehouseId = $commonUtil->getSettingValue(TbSetting::KEY_STOCK_MOVE_WAREHOUSE_ID);
    $warehouseRepo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $stockMoveWarehouseName = $warehouseRepo->find($warehouseId)->getName();

    // 画面表示
    return $this->render('AppBundle:Delivery:shipping-stock-move-list.html.twig', [
      'account' => $this->getLoginUser()
      , 'lastUpdated'  => $repoPicking->getLastUpdatedAndAccount(TbWarehouseRepository::DEFAULT_WAREHOUSE_ID, $today->format('Y-m-d'), 1)
      , 'pickingList' => $pickingList
      , 'warehouses' => $warehouses
      , 'activeWarehouseId' => $activeWarehouseId
      , 'stockMoveWarehouseName' => $stockMoveWarehouseName
    ]);
  }

  /**
   * 発送方法変更 一覧画面
   * @param Request $request
   * @return Response
   * @throws \Doctrine\DBAL\DBALException
   */
  public function changeShippingMethodListAction(Request $request)
  {
    // 発送方法配列取得（対応分のみ）
    $shippingMethodCodeList = [
      TbSalesDetail::SHIPPING_METHOD_CODE_SAGAWA
      , TbSalesDetail::SHIPPING_METHOD_CODE_MAILBIN
      , TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK
      , TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACKET
      , TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI_DAIBIKI
      , TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI
      , TbSalesDetail::SHIPPING_METHOD_CODE_NEKOPOSU
    ];

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->get('batch.mall_process.next_engine');
    $shippingMethods = [];
    foreach($shippingMethodCodeList as $code) {
      $shippingMethods[] = [
        'code' => $code
        , 'name' => $neMallProcess->getDeliveryMethodName($code)
      ];
    }

    // 画面表示
    return $this->render('AppBundle:Delivery:change-shipping-method.html.twig', [
      'account' => $this->getLoginUser()
      , 'shippingMethodsJson' => json_encode($shippingMethods)
    ]);
  }

  /**
   * 発送方法変更 一覧取得処理
   * @param Request $request
   * @return JsonResponse
   */
  public function getChangeShippingMethodListAction(Request $request)
  {
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'list' => null
    ];

    try {

      $today = new \DateTime();

      /** @var TbDeliveryChangeShippingMethodRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryChangeShippingMethod');
      $list = $repo->getList();

      $result = [
        'status' => 'ok'
        , 'message' => null
        , 'list' => $list
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : '伝票一覧の取得に失敗しました。';
    }

    return new JsonResponse($result);
  }

  /**
   * 出荷量 一覧取得処理
   * @param Request $request
   * @return JsonResponse
   */
  public function getCalculateShipmentListAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'calculateShipment' => []
    ];

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      // 出荷量
      $sql = <<<EOD
      SELECT
        tw.name
        , tcpa.order_amount
        , tcpa.stock_move_amount
        , tcpa.order_amount + tcpa.stock_move_amount AS total 
      FROM
        tb_calculated_shipment_amount tcpa 
        INNER JOIN tb_warehouse tw 
          ON tcpa.warehouse_id = tw.id 
      ORDER BY
        tw.display_order ASC
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $result['calculateShipment'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }

  /**
   * 発送方法変更 追加確認
   * @param Request $request
   * @return JsonResponse
   */
  public function changeShippingMethodAddConfirmAction(Request $request)
  {
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'voucher' => null
    ];

    try {
      $voucherNumber = $request->get('voucherNumber');

      /** @var TbSalesDetailRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      $voucher = $this->getVoucherAndCheck($voucherNumber, $result);

      $result = [
        'status' => 'ok'
        , 'message' => sprintf("[%s : %s]\n\nこの伝票を追加します。よろしいですか？", $voucher->getVoucherNumber(), $voucher->getDeliveryName())
        , 'voucher' => $voucher->toScalarArray('camel')
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : '伝票の取得に失敗しました。';
    }

    return new JsonResponse($result);
  }

  /**
   * 発送方法変更 追加処理
   */
  public function changeShippingMethodAddAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
      , 'voucher' => null
    ];

    try {
      $voucherNumber = $request->get('voucherNumber');
      $voucher = $this->getVoucherAndCheck($voucherNumber, $result);

      $today = new \DateTime();
      // 伝票追加
      $changeMethod = new TbDeliveryChangeShippingMethod();
      $changeMethod->setDate($today);
      $changeMethod->setVoucherNumber($voucher->getVoucherNumber());
      $changeMethod->setPurchaser($voucher->getCustomerName());
      $changeMethod->setAddressee($voucher->getDeliveryName());
      $changeMethod->setShopName($voucher->getShopName());
      $changeMethod->setShippingMethod($voucher->getShippingMethodName());
      $changeMethod->setReceiveOrderDeliveryId($voucher->getShippingMethodCode());
      $changeMethod->setNewShippingMethod('');
      $changeMethod->setNewReceiveOrderDeliveryId('');
      $changeMethod->setCurrentShippingMethod($voucher->getShippingMethodName());
      $changeMethod->setCurrentReceiveOrderDeliveryId($voucher->getShippingMethodCode());
      $changeMethod->setStatus(TbDeliveryChangeShippingMethodRepository::STATUS_NONE);

      $em = $this->getDoctrine()->getManager('main');
      $em->persist($changeMethod);
      $em->flush();

      $result = [
        'status' => 'ok'
        , 'message' => sprintf("伝票を追加しました。[%s / %s / %s]", $changeMethod->getVoucherNumber(), $changeMethod->getAddressee(), $changeMethod->getShippingMethod())
        , 'voucher' => $voucher->toScalarArray()
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : '伝票の取得に失敗しました。';
    }

    return new JsonResponse($result);

  }

  /**
   * 発送方法変更 更新反映処理（キュー追加）
   * @return JsonResponse
   */
  public function changeShippingMethodUpdateVoucherListAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try {
      $resque = $this->getResque();

      $job = new MainJob();
      $job->queue = 'main'; // キュー名
      $job->args = [
        'command'   => MainJob::COMMAND_KEY_UPDATE_VOUCHER_CHANGE_SHIPPING_METHODS
      ];
      $resque->enqueue($job); // リトライなし

      $result = [
        'status' => 'ok'
        , 'message' => sprintf("NextEngine受注明細差分更新および発送方法変更反映処理をキューに追加しました。")
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : 'キューへの処理追加に失敗しました。';
    }

    return new JsonResponse($result);
  }

  /**
   * 発送方法変更 削除処理
   * @param Request $request
   * @return JsonResponse
   */
  public function changeShippingMethodDeleteAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try {
      $voucherNumber = $request->get('voucherNumber');

      /** @var TbDeliveryChangeShippingMethodRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryChangeShippingMethod');

      $change = $repo->findActiveOneByVoucherNumber($voucherNumber);
      if (!$change) {
        throw new \RuntimeException('発送方法変更登録がありません。');
      }

      $em = $this->getDoctrine()->getManager('main');
      $em->remove($change);
      $em->flush();

      $result = [
        'status' => 'ok'
        , 'message' => sprintf("発送方法変更登録を削除しました。[" . $change->getVoucherNumber() . ']')
      ];

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = strlen($result['message']) ? $result['message'] : '登録の削除に失敗しました。';
    }

    return new JsonResponse($result);
  }

  /**
   * 伝票取得＆受注状態チェック
   * @param string $voucherNumber
   * @param array &$result
   * @return VSalesVoucher
   */
  private function getVoucherAndCheck($voucherNumber, &$result)
  {
    /** @var TbSalesDetailRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
    /** @var VSalesVoucher $voucher */
    $voucher = $repo->getVoucherByVoucherNumber($voucherNumber);
    if (!$voucher) {
      $result['message'] = '伝票が見つかりませんでした。 [' . $voucherNumber . ']';
      throw new \RuntimeException($result['message']);
    }

    if (!in_array($voucher->getOrderStatus(), [
      '納品書印刷済'
      , '出荷確定済（完了）'
    ])) {
      $result['message'] = sprintf('受注状態が%sです。この伝票は追加できません。', $voucher->getOrderStatus());
      throw new \RuntimeException($result['message']);
    }

    /** @var TbDeliveryChangeShippingMethodRepository $repoChange */
    $repoChange = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryChangeShippingMethod');
    $change = $repoChange->findActiveOneByVoucherNumber($voucherNumber);
    if ($change) {
      $result['message'] = sprintf('すでに登録されています。[%s / %s / %s => %s]', $change->getVoucherNumber(), $change->getAddressee(), $change->getShippingMethod(), $change->getCurrentShippingMethod());
      throw new \RuntimeException($result['message']);
    }

    return $voucher;
  }

  /**
   * 発送方法変更 配送情報CSVダウンロード（宛名CSVダウンロード）
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   * @throws \Exception
   */
  public function changeShippingMethodDownloadCsvAction(Request $request)
  {
    $shippingMethodCode = $request->get('method');
    $now = $request->get('now'); // ファイル名を画面と合わせるためだけに利用。あまり意味ない。
    if (!$shippingMethodCode || !$now) {
      throw new \RuntimeException('CSVの指定が正しくありません。');
    }

    // 日付整形＆DateTime化（整形しなくていい気もしないでもない）
    if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $now, $match)) {
      $now = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]));
    } else {
      $now = new \DateTime($now);
    }

    // データ取得

    /** @var TbDeliveryChangeShippingMethodRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryChangeShippingMethod');
    $changeList = $repo->findList(null, $shippingMethodCode);

    $voucherNumbers = [];
    foreach($changeList as $change) {
      $voucherNumbers[] = $change->getVoucherNumber();
    }

    /** @var TbSalesDetailRepository $repoVoucher */
    $repoVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
    $vouchers = $repoVoucher->getVoucherByVoucherNumbers($voucherNumbers, true);

    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.next_engine');

    $response = $mallProcess->generateShippingLabelCsv($shippingMethodCode, $vouchers, $now);
    $response->send();

    // 暫定的に、ここでステータス 完了としてしまう。
    foreach($changeList as $change) {
      $change->setStatus(TbDeliveryChangeShippingMethodRepository::STATUS_DONE);
    }

    $em = $this->getDoctrine()->getManager('main');
    $em->flush();

    return $response;
  }

  /**
   * 配送情報CSVダウンロード画面表示
   * @param Request $request
   */
  public function createShippingInfoCsvAction(Request $request) {
    // 発送方法配列取得
    $deliveryMethodCodeList = [
      TbSalesDetail::SHIPPING_METHOD_CODE_YAMATO,
      TbSalesDetail::SHIPPING_METHOD_CODE_SAGAWA,
      TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK_RSL,
      TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK,
      TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACKET,
      TbSalesDetail::SHIPPING_METHOD_CODE_NEKOPOSU,
      TbSalesDetail::SHIPPING_METHOD_CODE_MAILBIN,
      TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEI,
      TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI,
    ];

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->get('batch.mall_process.next_engine');
    $deliveryMethods = [];
    foreach($deliveryMethodCodeList as $code) {
      $deliveryMethods[] = [
          'code' => $code
          , 'name' => $neMallProcess->getDeliveryMethodName($code)
      ];
    }

    // 画面表示
    return $this->render('AppBundle:Delivery:create-shipping-info.html.twig', [
        'account' => $this->getLoginUser()
        , 'deliveryMethodsJson' => json_encode($deliveryMethods)
    ]);
  }

  /**
   * 配送情報CSVダウンロード。
   * 指定の伝票番号と発送方法の組み合わせで配送情報CSVを出力する。
   * @param Request $request
   */
  public function createShippingInfoCsvDownloadAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    try {
      $paramVoucherNumbers = $request->get('voucherNumbers');
      $deliveryMethodId = $request->get('deliveryMethodId');

      if (!$paramVoucherNumbers || !$deliveryMethodId) {
        throw new \RuntimeException('伝票番号と配送方法コードを送信してください');
      }
      $voucherNumbers = explode("\n", str_replace(array("\r\n", "\r", "\n"), "\n", $paramVoucherNumbers)); // 改行で分割

      // 念のため生成する伝票番号・発送方法を出力する。NE側受注情報の更新漏れなどあった場合の追跡用
      $logger->info("配送方法CSVダウンロード: アカウント[" . $account->getId() . "], 配送方法コード[$deliveryMethodId], 対象伝票番号[" . implode(',', $voucherNumbers) . "]");

      /** @var NextEngineMallProcess $mallProcess */
      $mallProcess = $this->get('batch.mall_process.next_engine');

      $response = $mallProcess->generateShippingLabelCsv($deliveryMethodId, $voucherNumbers);
      $response->send();

      return $response;

    } catch (\Exception $e) {
      $this->addFlash('danger', $e->getMessage());
      return $this->redirectToRoute('delivery_create_shipping_info_csv');
    }
  }

  /**
   * 配送情報PDFダウンロード。
   * 指定の伝票番号と発送方法の組み合わせで配送情報CSVを出力する。
   * @param Request $request
   */
  public function createShippingInfoPdfDownloadAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var SymfonyUsers $account */
    $account = $this->getLoginUser();

    try {
      $paramVoucherNumbers = $request->get('voucherNumbers');
      $deliveryMethodId = $request->get('deliveryMethodId');

      if (!$paramVoucherNumbers || !$deliveryMethodId) {
        throw new \RuntimeException('伝票番号と配送方法コードを送信してください');
      }
      $voucherNumbers = explode("\n", str_replace(array("\r\n", "\r", "\n"), "\n", $paramVoucherNumbers)); // 改行で分割

      // 念のため生成する伝票番号・発送方法を出力する。NE側受注情報の更新漏れなどあった場合の追跡用
      $logger->info("配送方法CSVダウンロード: アカウント[" . $account->getId() . "], 配送方法コード[$deliveryMethodId], 対象伝票番号[" . implode(',', $voucherNumbers) . "]");

      // データ取得

      /** @var TbSalesDetailRepository $repoVoucher */
      $repoVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      $vouchers = $repoVoucher->getVoucherByVoucherNumbers($voucherNumbers, true);

      /** @var ShippingLabelService $service */
      $service = $this->get('misc.service.shipping_label');
      $isDebug = $request->get('html');
      if ($isDebug === 'false') {
        $isDebug = false;
      }
      $response = $service->makeShippingLabelPdf($voucherNumbers, $deliveryMethodId, $isDebug);

      
      return $response;

    } catch (\Exception $e) {
      $this->addFlash('danger', $e->getMessage());
      return $this->redirectToRoute('delivery_create_shipping_info_csv');
    }
  }

  /**
   * FBAマルチチャネル 出荷依頼CSV出力
   */
  public function fbaMultiDownloadServiceRequestCsvAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {

      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }
      if (!$currentWarehouse->isFbaVirtualWarehouse()) {
        throw new \RuntimeException('この倉庫はFBA仮想倉庫ではありません。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $now = new \DateTime();

      // お急ぎ便 指定不可都道府県（通常のFBA納品先であるNRT5 基準と仮定し、表が空白が不可と想定した一覧）
      // https://sellercentral.amazon.co.jp/gp/help/help.html?ie=UTF8&itemID=G201301780
      $standardDeliveryPrefectureList = [
        '北海道'
        , '広島県'
        , '岡山県'
        , '鳥取県'
        , '島根県'
        , '香川県'
        , '徳島県'
        , '愛媛県'
        , '高知県'
        , '福岡県'
        , '佐賀県'
        , '長崎県'
        , '大分県'
        , '熊本県'
        , '宮崎県'
        , '鹿児島県'

        , '沖縄県'
      ];

      // 伝票一覧を取得
      $sql = <<<EOD
        SELECT
          *
        FROM tb_delivery_statement_detail_num_order_list_result r
        WHERE r.warehouse_id = ( :currentWarehouseId )
        ORDER BY r.id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':currentWarehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      $listResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      $voucherNumbers = [];
      foreach ($listResults as $list) {
        $voucherNumbers[] = $list['伝票番号'];
      }

      // 出荷情報取得
      /** @var TbSalesDetailRepository $repoSalesVoucher */
      $repoSalesVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      $vouchers = $repoSalesVoucher->getVoucherByVoucherNumbers($voucherNumbers);

      // 出力
      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      /** @var \MiscBundle\Util\StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $exportDir = $fileUtil->getWebCsvDir() . '/Amazon/FBAServiceRequest';

      // 1ファイル 100伝票までとのことで、zip固定実装
      $limit = 100;
      $eol = "\r\n";

      // ヘッダ
      $headers = [
        'MerchantFulfillmentOrderID' => 'MerchantFulfillmentOrderID'
        , 'DisplayableOrderID' => 'DisplayableOrderID'
        , 'DisplayableOrderDate' => 'DisplayableOrderDate'
        , 'MerchantSKU' => 'MerchantSKU'
        , 'Quantity' => 'Quantity'
        , 'MerchantFulfillmentOrderItemID' => 'MerchantFulfillmentOrderItemID'
        , 'GiftMessage' => 'GiftMessage'
        , 'DisplayableComment' => 'DisplayableComment'
        , 'DisplayableOrderComment' => 'DisplayableOrderComment'
        , 'DeliverySLA' => 'DeliverySLA'
        , 'AddressName' => 'AddressName'
        , 'AddressFieldOne' => 'AddressFieldOne'
        , 'AddressFieldTwo' => 'AddressFieldTwo'
        , 'AddressCountryCode' => 'AddressCountryCode'
        , 'AddressStateOrRegion' => 'AddressStateOrRegion'
        , 'AddressPostalCode' => 'AddressPostalCode'
        , 'AddressPhoneNumber' => 'AddressPhoneNumber'
        , 'NotificationEmail' => 'NotificationEmail'
        , 'IsCod' => 'IsCod'
        , 'OrderCodCharge' => 'OrderCodCharge'
        , 'OrderCodChargeTax' => 'OrderCodChargeTax'
        , 'OrderShipCharge' => 'OrderShipCharge'
        , 'OrderShipChargeTax' => 'OrderShipChargeTax'
        , 'PerUnitPrice' => 'PerUnitPrice'
        , 'PerUnitTax' => 'PerUnitTax'
      ];

      $headerLine = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers), "\t") . $eol;

      $fileIndex = 1;
      $voucherIndex = 1;
      $exportFile = null;
      foreach ($vouchers as $voucher) {
        if (!isset($exportFile)) {
          $fileName = sprintf('FBAServiceRequest_%s_%02d.txt', $now->format('YmdHis'), $fileIndex++);
          $filePath = sprintf('%s/%s', $exportDir, $fileName);

          $exportFile = new \SplFileObject($filePath, 'w');
          $exportFile->fwrite($headerLine);
        }

        // 代引き計算（総合計につじつまを合わせる。また、手数料でひとくくりにしてしまう。）
        $shipCharge = 0;
        if ($voucher->isDaibiki()) {
          $subTotal = 0;
          foreach ($voucher->getDetails() as $detail) {
            $subTotal += $detail->getUnitPriceWithTax();
          }

          $shipCharge = $voucher->getPaymentTotal() - $subTotal;
        }

        $address = $voucher->getDeliveryAddress1() . ' ' . $voucher->getDeliveryAddress2();
        $address1 = mb_strcut($address, 0, 60, 'UTF-8');
        $address2 = strlen($address) > 60 ? mb_strcut($address, 60, 60, 'UTF-8') : '';
        $prefecture = $voucher->getDeliveryPrefecture();

        foreach ($voucher->getDetails() as $detail) {
          $row = [
            'MerchantFulfillmentOrderID' => $voucher->getVoucherNumber()
            , 'DisplayableOrderID' => $voucher->getVoucherNumber()
            , 'DisplayableOrderDate' => $voucher->getOrderDate()->format('Y-m-d')
            , 'MerchantSKU' => $detail->getNeSyohinSyohinCode()
            , 'Quantity' => $detail->getOrderedNum()
            , 'MerchantFulfillmentOrderItemID' => $detail->getNeSyohinSyohinCode()
            , 'GiftMessage' => ''
            , 'DisplayableComment' => ''
            , 'DisplayableOrderComment' => 'この度はご注文誠に有難うございます。返品につきましては一週間以内にご連絡をお願いいたします。'
            , 'DeliverySLA' => in_array($prefecture, $standardDeliveryPrefectureList) ? 'Standard' : 'Expedited'
            , 'AddressName' => $voucher->getDeliveryName()
            , 'AddressFieldOne' => $address1
            , 'AddressFieldTwo' => $address2
            , 'AddressCountryCode' => 'JP'
            , 'AddressStateOrRegion' => $voucher->getDeliveryPrefecture()
            , 'AddressPostalCode' => $voucher->getDeliveryZipcodeWithHyphen()
            , 'AddressPhoneNumber' => $voucher->getDeliveryTel()
            , 'NotificationEmail' => ''

            // 代引き
            , 'IsCod' => $voucher->isDaibiki() ? 'yes' : ''
            , 'OrderCodCharge' => $voucher->isDaibiki() ? '0' : ''
            , 'OrderCodChargeTax' => $voucher->isDaibiki() ? '0' : ''
            , 'OrderShipCharge' => $voucher->isDaibiki() ? $shipCharge : ''
            , 'OrderShipChargeTax' => $voucher->isDaibiki() ? '0' : ''
            , 'PerUnitPrice' => $voucher->isDaibiki() ? $detail->getUnitPriceWithTax() : ''
            , 'PerUnitTax' => $voucher->isDaibiki() ? '0' : ''
          ];

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), array_keys($headers), "\t");
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . $eol;
          $exportFile->fwrite($line);
        }

        if (($voucherIndex++) % $limit === 0) {
          unset($exportFile); // close & save
        }
      }

      unset($exportFile); // close & save

      // zipファイル作成
      $finder = new Finder();
      $files = $finder->in($exportDir)->name(sprintf('/FBAServiceRequest_%s_.*.txt/', $now->format('YmdHis')))->files();

      if ($files->count() > 0) {
        $downloadFilePath = sprintf(sprintf('%s/FBAServiceRequest_%s.zip', $exportDir, $now->format('YmdHis')));

        $zip = new \ZipArchive();
        if (!$zip->open($downloadFilePath, \ZipArchive::CREATE)) {
          throw new \RuntimeException('can not create zip file. aborted. [' . $downloadFilePath . ']');
        }
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
          $zip->addFile($file->getPathname(), $file->getBasename());
        }

      } else {
        $downloadFilePath = sprintf(sprintf('%s/no-data.txt', $exportDir));
        $exportFile = new \SplFileObject($downloadFilePath, 'w');
        $exportFile->fwrite('');
        unset($exportFile);
      }

      // ダウンロード出力
      $response = new StreamedResponse();
      $response->setCallback(

        function () use ($downloadFilePath) {
          // $exportFile = new \SplFileObject($downloadFilePath, 'r');
          $outputFile = new \SplFileObject('php://output', 'w');

          $outputFile->fwrite(file_get_contents($downloadFilePath));
          flush();
        }
      );

      $logger->info('file path: ' . $downloadFilePath);

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', basename($downloadFilePath)));

      $logger->info('csv output: response done!');

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $response = new Response($e->getMessage(), 500);

    }

    return $response;

  }

  public function yabuyoshiDownloadServiceRequestCsvAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    try {

      $currentWarehouse = $this->getLoginUser()->getWarehouse();
      if (!$currentWarehouse) {
        throw new \RuntimeException('選択倉庫が取得できませんでした。');
      }
      if (!$currentWarehouse->isYabuyoshiWarehouse()) {
        throw new \RuntimeException('この倉庫は藪吉倉庫ではありません。');
      }

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $now = new \DateTime();
      $tomorrow = new \DateTime();
      $tomorrow->modify('+1 days');

      // 伝票一覧を取得
      $sql = <<<EOD
        SELECT
          *
        FROM tb_delivery_statement_detail_num_order_list_result r
        WHERE r.warehouse_id = ( :currentWarehouseId )
        ORDER BY r.id
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':currentWarehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
      $stmt->execute();

      $listResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      $voucherNumbers = [];

      foreach ($listResults as $list) {
        $voucherNumbers[] = $list['伝票番号'];
      }
      // 出荷情報取得
      /** @var TbSalesDetailRepository $repoSalesVoucher */
      $repoSalesVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      $vouchers = $repoSalesVoucher->getVoucherByVoucherNumbers($voucherNumbers);

      // 出力
      /** @var FileUtil $fileUtil */
      $fileUtil = $this->get('misc.util.file');
      /** @var \MiscBundle\Util\StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $exportDir = $fileUtil->getWebCsvDir() . '/YabuyoshiServiceRequest';

      $eol = "\r\n";

      // ヘッダ
      $headers = [
        '荷主コード'             => '荷主コード'
        ,'センターコード'        => 'センターコード'
        ,'作業予定日'            => '作業予定日'
        ,'出庫予定日'            => '出庫予定日'
        ,'納品予定日'            => '納品予定日'
        ,'伝票番号'              => '伝票番号'
        ,'倉庫コード'            => '倉庫コード'
        , '備考'                  => '備考'
        ,'商品コード'            => '商品コード'
        ,'商品名'                => '商品名'
        , '在庫状態コード '       => '在庫状態コード '
        , '鮮度日付'              => '鮮度日付'
        , '入庫日'                => '入庫日'
        , 'ロット番号'            => 'ロット番号'
        , 'ベンダーコード'        => 'ベンダーコード'
        ,'出庫予定数（ピース）'  => '出庫予定数（ピース）'
        ,'ECサイト店舗コード'    => 'ECサイト店舗コード'
        , 'ECサイト店舗名'        => 'ECサイト店舗名'
        , 'ECサイトサイトオーダー番号'=> 'ECサイトサイトオーダー番号'
        ,'送り先名'              => '送り先名'
        , '送り先フリガナ'        => '送り先フリガナ'
        ,'送り先郵便番号'        => '送り先郵便番号'
        ,'送り先住所1'           => '送り先住所1'
        ,'送り先住所2'           => '送り先住所2'
        , '送り先住所3'           => '送り先住所3'
        ,'送り先電話番号'        => '送り先電話番号'
        , 'ピッキング指示'        => 'ピッキング指示'
        ,'配達希望日'            => '配達希望日'
        ,'時間指定コード'        => '時間指定コード'
        ,'発送代'                => '発送代'
        ,'手数料'                => '手数料'
        ,'税金'                  => '税金'
        ,'ポイント数'            => 'ポイント数'
        , '獲得ポイント数'        => '獲得ポイント数'
        ,'総合計'                => '総合計'
        ,'売単価'                => '売単価'
        ,'値引き金額'            => '値引き金額'
        ,'支払い区分'            => '支払い区分'
        ,'発送区分'              => '発送区分'
        ,'購入者名'              => '購入者名'
        ,'購入者フリガナ'        => '購入者フリガナ'
        ,'購入者郵便番号'        => '購入者郵便番号'
        ,'購入者住所1'           => '購入者住所1'
        ,'購入者住所2'           => '購入者住所2'
        , '購入者住所3'           => '購入者住所3'
        ,'購入者電話番号'        => '購入者電話番号'
        , '購入者メール'          => '購入者メール'
        , '納品書特記事項'        => '納品書特記事項'
        , '商品オプション'        => '商品オプション'
        , '発送伝票備考欄'        => '発送伝票備考欄'
        ,'納品明細区分'          => '納品明細区分'
      ];

      $headerLine = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers), ",");
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-WIN', 'UTF-8') . $eol;

      $fileIndex = 1;

      $exportFile = null;
      $fileName = sprintf('YabuyoshiServiceRequest_%s_%02d.csv', $now->format('YmdHis'), $fileIndex++);
      $filePath = sprintf('%s/%s', $exportDir, $fileName);
      $exportFile = new \SplFileObject($filePath, 'w');
//      $exportFile->fwrite($headerLine);
      foreach ($vouchers as $voucher) {

        //値引き金額がマイナス値であればプラス値に
        $discountedAmount = $voucher->getDiscountedAmount();
        $discountedAmount *= $discountedAmount < 0 ? -1 : 1;

        //配達希望日がなければ空欄で出力
        $shippingOrderedDate = $voucher->getShippingOrderedDate();
        $shippingOrderedDate = !$shippingOrderedDate ? '' : $shippingOrderedDate->format('Ymd');

        //時間指定コード
        $deliveryTimeZone = $voucher->getDeliveryTimeZone();
        switch ($deliveryTimeZone){
          case "午前中":
            $deliveryTimeZone = '01';
            break;
          case "１２時～１４時":
            $deliveryTimeZone = '12';
            break;
          case "１４時～１６時":
            $deliveryTimeZone = '14';
            break;
          case "１６時～１８時":
            $deliveryTimeZone = '16';
            break;
          case "１８時～２０時":
          case "１８時～２１時":
          case "１９時～２１時":
          case "２０時～２１時":
            $deliveryTimeZone = '04';
            break;
          //指定なし.希望なし.その他上記以外のもの
          default:
            $deliveryTimeZone = '00';
            break;
        }

        // 出力内容のデータ
        foreach ($voucher->getDetails() as $detail) {
          $voucherSyohinName = $detail->getVoucherSyohinName(); // UTF-8
          $voucherSyohinName = mb_convert_encoding($voucherSyohinName, "SJIS-WIN", "UTF-8");
          $voucherSyohinName = mb_strcut($voucherSyohinName, 0, 200, "SJIS-WIN");
          $voucherSyohinName = mb_convert_encoding($voucherSyohinName, 'UTF-8', 'SJIS-WIN');

          $row = [
            '荷主コード'              => 'plusnao'
            , 'センターコード'        => '001'
            , '作業予定日'            => $now->format('Ymd')
            , '出庫予定日'            => $now->format('Ymd')
            , '納品予定日'            => $tomorrow->format('Ymd')
            , '伝票番号'              => $voucher->getVoucherNumber()
            , '倉庫コード'            => '001'
            , '備考'                  => ''
            , '商品コード'            => $detail->getNeSyohinSyohinCode()
            , '商品名'                => $voucherSyohinName
            , '在庫状態コード '       => ''
            , '鮮度日付'              => ''
            , '入庫日'                => ''
            , 'ロット番号'            => ''
            , 'ベンダーコード'        => ''
            , '出庫予定数（ピース）'  => $detail->getOrderedNum()
            , 'ECサイト店舗コード'    => 'plusnao'
            , 'ECサイト店舗名'        => ''
            , 'ECサイトサイトオーダー番号'=> ''
            , '送り先名'              => $voucher->getDeliveryName()
            , '送り先フリガナ'        => ''
            , '送り先郵便番号'        => $voucher->getDeliveryZipcodeWithHyphen()
            , '送り先住所1'           => $voucher->getDeliveryAddress1()
            , '送り先住所2'           => $voucher->getDeliveryAddress2()
            , '送り先住所3'           => ''
            , '送り先電話番号'        => $voucher->getDeliveryTel()
            , 'ピッキング指示'        => ''
            , '配達希望日'            => $shippingOrderedDate
            , '時間指定コード'        => $deliveryTimeZone
            , '発送代'                => $voucher->getDeliveryCharge()
            , '手数料'                => $voucher->getPaymentCharge()
            , '税金'                  => $voucher->getTax()
            , 'ポイント数'            => $voucher->getPointSize()
            , '獲得ポイント数'        => ''
            , '総合計'                => $voucher->getPaymentTotal()
            , '売単価'                => $detail->getUnitPrice()
            , '値引き金額'            => $discountedAmount
            , '支払い区分'            => '0'
            , '発送区分'              => '13'
            , '購入者名'              => $voucher->getCustomerName()
            , '購入者フリガナ'        => $voucher->getCustomerNameKana()
            , '購入者郵便番号'        => $voucher->getCustomerZipcode()
            , '購入者住所1'           => $voucher->getCustomerAddress1()
            , '購入者住所2'           => $voucher->getCustomerAddress2()
            , '購入者住所3'           => ''
            , '購入者電話番号'        => $voucher->getCustomerTel()
            , '購入者メール'          => ''
            , '納品書特記事項'        => ''
            , '商品オプション'        => ''
            , '発送伝票備考欄'        => ''
            , '納品明細区分'          => '0'
          ];

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), array_keys($headers), ",");
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . $eol;
          $exportFile->fwrite($line);
        }
      }
      // ダウンロード出力
      $response = new StreamedResponse();
      $response->setCallback(

        function () use ($filePath) {
          // $exportFile = new \SplFileObject($downloadFilePath, 'r');
          $outputFile = new \SplFileObject('php://output', 'w');

          $outputFile->fwrite(file_get_contents($filePath));
          flush();
        }
      );

      $logger->info('file path: ' . $filePath);

      $response->headers->set('Content-type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', basename($filePath)));

      $logger->info('csv output: response done!');

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $response = new Response($e->getMessage(), 500);

    }

    return $response;

  }



  /**
   * 取り込み済み納品書データからピッキングリスト作成
   * 新規作成、再作成ともに兼ねる
   * @param TbShippingVoucher $voucher
   * @param DateTimeInterface $date
   * @param SymfonyUsers|null $account
   * @throws \Doctrine\DBAL\DBALException
   */
  private function createPickingListByShippingVoucher(TbShippingVoucher $voucher, $date, $account = null, $currentWarehouse)
  {
    /** @var BatchLogger $logger */
    $service = $this->get('misc.service.shipping_voucher');
    $result = $service->createPickingListByShippingVoucher($voucher, $date, $account, $currentWarehouse);
    return $result;
  }

  public static $CSV_FIELDS_NE_SHIPPING_VOUCHER = [
    '店舗名'
    , '伝票番号'
    , '受注番号'
    , '受注日'
    , '取込日'
    , '受注状態'
    , '発送方法'
    , '支払方法'
    , '合計金額'
    , '税金'
    , '手数料'
    , '送料'
    , 'その他'
    , 'ポイント'
    , '承認金額'
    , '備考'
    , '入金金額'
    , '入金区分'
    , '入金日'
    , '納品書印刷指示日'
    , '納品書発行日'
    , '納品書備考'
    , '出荷日'
    , '出荷予定日'
    , '作業者欄'
    , 'ピック指示内容'
    , 'ラベル発行日'
    , '配送日'
    , '配送時間帯'
    , '配送伝票番号'
    , 'クレジット区分'
    , '名義人'
    , '有効期限'
    , '承認番号'
    , '承認区分'
    , '承認日'
    , '購入者名'
    , '購入者カナ'
    , '購入者郵便番号'
    , '購入者住所1'
    , '購入者住所2'
    , '購入者電話番号'
    , '購入者ＦＡＸ'
    , '購入者メールアドレス'
    , '発送先名'
    , '発送先カナ'
    , '発送先郵便番号'
    , '発送先住所1'
    , '発送先住所2'
    , '発送先電話番号'
    , '発送先ＦＡＸ'
    , '配送備考'
    , '商品コード'
    , '商品名'
    , '受注数'
    , '商品単価'
    , '掛率'
    , '小計'
    , '商品オプション'
    , 'キャンセル'
    , '引当数'
    , '引当日'
    , '消費税率'
  ];

  /**
   * 梱包グループをマージ処理(Ajax)
   */
  public function ShippingVoucherMergePackingGroupAction(Request $request) {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $em = $this->getDoctrine()->getManager();
    $dbMain = $this->getDoctrine()->getConnection('main');
    $packingIds = $request->get('mergePackingIdList');
    $result = [
      'status' => 'ok'
      , 'message' => ''
    ];

    try {
      $dbMain->beginTransaction();

      // 梱包グループIDを元に対象の出荷伝票グループを古い順で取得
      /** @var TbShippingVoucherRepository $svRepo */
      $svRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');

      $service = $this->get('misc.service.shipping_voucher');
      $service->lockPackingGroupAndVoucher($packingIds);

      /** @var TbShippingVoucher[] $shippingVouchers */
      $shippingVouchers = $svRepo->findBy(
        array('shippingVoucherPackingGroupId' => $packingIds)
        , array('picking_list_number' => 'ASC')
      );

      $voucherIds = array();
      foreach ($shippingVouchers as $voucher) {
        $voucherIds[] = $voucher->getId();
      }

      // マージ対象の梱包グループを取得
      /** @var TbShippingVoucherPackingGroupRepository $pgRepo */
      $pgRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPackingGroup');

      /** @var TbShippingVoucherPackingGroup[] $packingGroups */
      $packingGroups = $pgRepo->findBy(array('id' => $packingIds), array('id' => 'ASC'));

      // バリデート
      $errorMsg = $this->validateMergePackingGroup($shippingVouchers, $packingGroups);
      if (!empty($errorMsg)) {
        $result['status'] = 'ng';
        $result['message'] = $errorMsg;
        return new JsonResponse($result);
      }

      // 一番古い出荷伝票グループの梱包グループで出荷伝票グループをマージ
      $targetGroupId = $shippingVouchers[0]->getshippingVoucherPackingGroupId();

      $svRepo->updatePackingGroupByIds($voucherIds, $targetGroupId);
      $em->clear();

      $service = $this->get('misc.service.packing');
      $mergedName = $service->calcPackingGroupName($targetGroupId);
      $mergedStatus = $service->calcPackingGroupStatus($targetGroupId);
      // コメントは一番新しいもので上書き（異なるコメントについては画面側でバリデート）
      $mergedComment = $packingGroups[count($packingGroups) - 1]->getPackingComment();

      $mergedDatas = [
        'name' => $mergedName,
        'status' => $mergedStatus,
        'comment' => $mergedComment
      ];
      $pgRepo->updatePackingGroupWithMergedDatas($targetGroupId, $mergedDatas);

      // マージ済みの梱包グループを削除
      $targetPackingGroupIds = array();
      foreach ($shippingVouchers as $row) {
        $packingGroupId = $row->getshippingVoucherPackingGroupId();
        if ($packingGroupId == $targetGroupId) {
          continue;
        }
          $targetPackingGroupIds[] = $packingGroupId;
      }
      $pgRepo->deleteByIds($targetPackingGroupIds);

      $dbMain->commit();

    } catch (\Exception $e) {
      $logger->error('梱包グループマージ処理' . ':' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();

      if (isset($dbMain)) {
        $dbMain->rollback();
      }
    }

    return new JsonResponse($result);
  }

  /**
   * 梱包グループマージ処理（Ajax）のバリデート処理
   *
   * @param array $shippingVouchers
   * @param array $packingGroups
   * @return string
   */
  private function validateMergePackingGroup($shippingVouchers, $packingGroups) {
    $errors['pickError'] = '';
    $errors['warehouseError'] = '';
    $errors['delivaryError'] = '';

    // ピッキングリストNoが連番ではない、倉庫番号が一致しないデータがある
    foreach ($shippingVouchers as $key => $shippingVoucher) {
      $currentWarehouseDailyNumber = $shippingVoucher->getWarehouseDailyNumber();
      if ($key == 0) {
        continue;
      }
      $prevWarehouseDailyNumber = $shippingVouchers[$key - 1]->getWarehouseDailyNumber();
      if ($errors['pickError'] == ''
            && ($currentWarehouseDailyNumber - $prevWarehouseDailyNumber) != 1) {
        $errors['pickError'] = 'ピッキングリストが連番ではないためマージできません';
      }

      $currentWarehouseId = $shippingVoucher->getWarehouseId();
      $prevWarehouseId = $shippingVouchers[$key - 1]->getWarehouseId();
      if ($errors['warehouseError'] == ''
            && $currentWarehouseId != $prevWarehouseId) {
        $errors['warehouseError'] = '倉庫が異なるためマージできません';
      }
    }

    // 配送方法が異なるものがある
    foreach ($packingGroups as $key => $packingGroup) {
      if ($key == 0) {
        continue;
      }
      $currentDelivaryMethod = $packingGroup->getDeliveryMethodId();
      $prevDelivaryMethod = $packingGroups[$key - 1]->getDeliveryMethodId();
      if ($errors['delivaryError'] == ''
            && $currentDelivaryMethod != $prevDelivaryMethod) {
        $errors['delivaryError'] = '発送方法が異なるためマージできません';
      }
    }

    $errorMsg = '';
    $errors = array_filter($errors);
    if (!empty($errors)) {
      $errorMsg =  implode("\r\n", $errors);
    }
    return $errorMsg;
  }
}
