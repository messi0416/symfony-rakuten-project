<?php
/**
 * バッチ処理 倉庫別実績集計処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbWarehouseResultHistory;
use MiscBundle\Entity\Repository\TbDeliveryPickingListRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 倉庫別出荷実績集計処理
 */
class AggregateWarehouseResultHistoryCommand extends PlusnaoBaseCommand
{

  protected function configure()
  {
    $this
    ->setName('batch:aggregate-warehouse-result-history')
    ->setDescription('倉庫実績集計処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from_date', null, InputOption::VALUE_OPTIONAL, '集計対象日（開始）。この日を含む、これより後の履歴を処理する。未指定の場合前日')
    ->addOption('to_date', null, InputOption::VALUE_OPTIONAL, '集計対象日（終了）。この日を含む、これより前の履歴を処理する。from_date必須。未指定の場合前日')
    ->addOption('warehouse_id', null, InputOption::VALUE_OPTIONAL, '集計対象倉庫ID。未指定の場合全て')
    ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '倉庫実績集計';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    // tb_process_execute_log登録用 初期化
    $totalPickingSum = 0;
    $totalWarehousePickingSum = 0;

    $this->validate($input);
    $fromDate = new \Datetime('yesterday');
    if (! empty($input->getOption('from_date'))) {
      $fromDate = new \DateTime($input->getOption('from_date'));
    }
    $toDate = new \Datetime('yesterday');
    if (! empty($input->getOption('to_date'))) {
      $toDate = new \DateTime($input->getOption('to_date'));
    }
    $toDate->setTime(23, 59, 59);
    /** @var TbWarehouseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $warehouses = $repo->findAll();

    $this->processExecuteLog->setProcessNumber1(count($warehouses)); // 処理件数1 対象倉庫数
    $this->processExecuteLog->setVersion(1.0);

    $warehouseIdList = array();
    foreach ($warehouses as $warehouse) {
      $warehouseIdList[] = $warehouse->getId();
    }
    if (! empty($input->getOption('warehouse_id'))) {
      $warehouseIdList = array($input->getOption('warehouse_id'));
    }

    $em = $this->getDoctrine()->getManager('main');
    $emLog = $this->getDoctrine()->getManager('log');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');

    // 指定日内の日付を1日ずつループ
    for ($i = $fromDate; $i <= $toDate; $i->modify('+1 day')) {
      $targetDate = $i->format('Y-m-d');
      // 倉庫を1カ所ずつループ
      foreach ($warehouseIdList as $warehouseId) {
        // (1)ピッキング数取得
        $sql = <<<EOD
          SELECT
            SUM(L.`処理済み商品コード数`) AS `ピッキング`
          FROM (
            SELECT
                date,
                warehouse_id,
                COUNT(DISTINCT `商品コード`) AS `処理済み商品コード数`
            FROM tb_delivery_picking_list
            WHERE `picking_status` IN (:pickingStatusOk, :pickingStatusPass)
            GROUP BY date, number, warehouse_id
          ) L
          WHERE L.date = :date
            AND L.warehouse_id = :warehouseId;
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':pickingStatusOk', TbDeliveryPickingListRepository::PICKING_STATUS_OK, \PDO::PARAM_INT);
        $stmt->bindValue(':pickingStatusPass', TbDeliveryPickingListRepository::PICKING_STATUS_PASS, \PDO::PARAM_INT);
        $stmt->bindValue(':date', $targetDate);
        $stmt->bindValue(':warehouseId', $warehouseId);
        $stmt->execute();
        $pickingSum = intval($stmt->fetchColumn(0));

        // (2)倉庫在庫ピッキング数取得
        $sql = <<<EOD
          SELECT
            COUNT(*) AS `倉庫在庫ピッキング`
          FROM
            tb_product_location_record_log rl
            INNER JOIN tb_product_location_log pl
              ON pl.`type` = 'record'
              AND rl.action_key = pl.action_key
          WHERE
            rl.log_date = :date
          AND
            rl.pre_warehouse_id = :warehouseId
          AND
            pl.operation = :operation;
EOD;
        $stmt = $dbLog->prepare($sql);
        $stmt->bindValue(':date', $targetDate);
        $stmt->bindValue(':warehouseId', $warehouseId);
        $stmt->bindValue(':operation', TbLocationRepository::LOG_OPERATION_WAREHOUSE_STOCK_MOVE_PICKING, \PDO::PARAM_STR);
        $stmt->execute();
        $warehousePickingSum = intval($stmt->fetchColumn(0));

        // (3)出荷数取得
        $sql = <<<EOD
          SELECT
            COUNT(DISTINCT d.`伝票番号`) AS 出荷数
          FROM tb_shipping_voucher v
          INNER JOIN tb_shipping_voucher_detail d ON v.id = d.voucher_id
          WHERE v.warehouse_id = :warehouseId
            AND v.picking_list_date = :picking_list_date
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouseId', $warehouseId);
        $stmt->bindValue(':picking_list_date', $targetDate);
        $stmt->execute();
        $shippingSum = intval($stmt->fetchColumn(0));

        $shippingSumShoplist = 0;
        $shippingSumRsl = 0;
        $shippingSumSagawa = 0;
        $shippingSumYamato = 0;

        // 出荷数が0でないなら、
        if ($shippingSum) {
          // SHOPLIST購入伝票の出荷数取得
          $sql = <<<EOD
          SELECT
            COUNT(DISTINCT d.`伝票番号`) AS SHOPLIST購入伝票の出荷数
          FROM tb_shipping_voucher v
          INNER JOIN tb_shipping_voucher_detail d ON v.id = d.voucher_id
          INNER JOIN tb_sales_detail_analyze a ON CAST(d.`伝票番号` AS SIGNED) = a.`伝票番号`
          INNER JOIN tb_shopping_mall m ON a.`店舗コード` = m.ne_mall_id
          WHERE v.warehouse_id = :warehouseId
            AND v.picking_list_date = :picking_list_date
            AND m.mall_id = :mall_id
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':warehouseId', $warehouseId);
          $stmt->bindValue(':picking_list_date', $targetDate);
          $stmt->bindValue(':mall_id', DbCommonUtil::MALL_ID_SHOPLIST, \PDO::PARAM_INT);
          $stmt->execute();
          $shippingSumShoplist = intval($stmt->fetchColumn(0));

          // ゆうパック(RSL)での出荷数取得
          $sql = <<<EOD
          SELECT
            COUNT(DISTINCT d.`伝票番号`) AS ゆうパックRSLでの出荷数
          FROM tb_shipping_voucher v
          INNER JOIN tb_shipping_voucher_detail d ON v.id = d.voucher_id
          INNER JOIN tb_sales_detail_analyze a ON CAST(d.`伝票番号` AS SIGNED) = a.`伝票番号`
          INNER JOIN tb_shopping_mall m ON a.`店舗コード` = m.ne_mall_id
          WHERE v.warehouse_id = :warehouseId
            AND v.picking_list_date = :picking_list_date
            AND m.mall_id <> :mall_id
            AND a.`配送方法コード` = :shipping_method_code
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':warehouseId', $warehouseId);
          $stmt->bindValue(':picking_list_date', $targetDate);
          $stmt->bindValue(':mall_id', DbCommonUtil::MALL_ID_SHOPLIST, \PDO::PARAM_INT);
          $stmt->bindValue(':shipping_method_code', TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK_RSL, \PDO::PARAM_INT);
          $stmt->execute();
          $shippingSumRsl = intval($stmt->fetchColumn(0));

          // 佐川での出荷数取得
          $sql = <<<EOD
          SELECT
            COUNT(DISTINCT d.`伝票番号`) AS 佐川での出荷数
          FROM tb_shipping_voucher v
          INNER JOIN tb_shipping_voucher_detail d ON v.id = d.voucher_id
          INNER JOIN tb_sales_detail_analyze a ON CAST(d.`伝票番号` AS SIGNED) = a.`伝票番号`
          INNER JOIN tb_shopping_mall m ON a.`店舗コード` = m.ne_mall_id
          WHERE v.warehouse_id = :warehouseId
            AND v.picking_list_date = :picking_list_date
            AND m.mall_id <> :mall_id
            AND a.`配送方法コード` = :shipping_method_code
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':warehouseId', $warehouseId);
          $stmt->bindValue(':picking_list_date', $targetDate);
          $stmt->bindValue(':mall_id', DbCommonUtil::MALL_ID_SHOPLIST, \PDO::PARAM_INT);
          $stmt->bindValue(':shipping_method_code', TbSalesDetail::SHIPPING_METHOD_CODE_SAGAWA, \PDO::PARAM_INT);
          $stmt->execute();
          $shippingSumSagawa = intval($stmt->fetchColumn(0));
        }

          // ヤマト(発払い)B2v6での出荷数取得
          $sql = <<<EOD
          SELECT
            COUNT(DISTINCT d.`伝票番号`) AS `ヤマト(発払い)B2v6での出荷数`
          FROM tb_shipping_voucher v
          INNER JOIN tb_shipping_voucher_detail d ON v.id = d.voucher_id
          INNER JOIN tb_sales_detail_analyze a ON CAST(d.`伝票番号` AS SIGNED) = a.`伝票番号`
          INNER JOIN tb_shopping_mall m ON a.`店舗コード` = m.ne_mall_id
          WHERE v.warehouse_id = :warehouseId
            AND v.picking_list_date = :picking_list_date
            AND m.mall_id <> :mall_id
            AND a.`配送方法コード` = :shipping_method_code
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':warehouseId', $warehouseId);
          $stmt->bindValue(':picking_list_date', $targetDate);
          $stmt->bindValue(':mall_id', DbCommonUtil::MALL_ID_SHOPLIST, \PDO::PARAM_INT);
          $stmt->bindValue(':shipping_method_code', TbSalesDetail::SHIPPING_METHOD_CODE_YAMATO, \PDO::PARAM_INT);
          $stmt->execute();
          $shippingSumYamato = intval($stmt->fetchColumn(0));

        // 上記(1)～(3)の全てが0でなければ登録
        if (!($pickingSum === 0 && $warehousePickingSum === 0 && $shippingSum === 0)) {
          // 現在の倉庫と日付の組み合わせのデータを取得
          $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseResultHistory', 'log');
          $result = $repo->findOneBy(['warehouseId' => $warehouseId, 'targetDate' => $targetDate]);
          if (empty($result)) {
            $result = new TbWarehouseResultHistory();
            $result->setWarehouseId($warehouseId);
            $result->setTargetDate($targetDate);
          }
          $result->setPickingSum($pickingSum);
          $result->setWarehousePickingSum($warehousePickingSum);
          $result->setShippingSum($shippingSum);
          $result->setShippingSumShoplist($shippingSumShoplist);
          $result->setShippingSumRsl($shippingSumRsl);
          $result->setShippingSumSagawa($shippingSumSagawa);
          $result->setShippingSumYamato($shippingSumYamato);
          $result->setUpdateAccountId(0);
          $emLog->persist($result);
          $totalPickingSum += $pickingSum;
          $totalWarehousePickingSum += $warehousePickingSum;
        }
      }
    }
    $emLog->flush();
    $this->processExecuteLog->setProcessNumber2($totalPickingSum); // 処理件数2 ピッキング数合計
    $this->processExecuteLog->setProcessNumber3($totalWarehousePickingSum); // 処理件数3 倉庫在庫ピッキング数合計
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  function validate(InputInterface $input)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    if (! empty($input->getOption('from_date')) && $input->getOption('from_date') < date("Y-m-d", strtotime("-30 day"))) {
      throw new \RuntimeException('集計対象日（開始）は、本日より30日以内を指定してください[' . $input->getOption('from_date') . ']');
    }
    if (! empty($input->getOption('from_date')) && empty($input->getOption('to_date'))) {
      throw new \RuntimeException('集計対象日（開始）を指定する場合は集計対象日（終了）も指定が必要です');
    }
    if (! empty($input->getOption('to_date')) && empty($input->getOption('from_date'))) {
      throw new \RuntimeException('集計対象日（終了）を指定する場合は集計対象日（開始）も指定が必要です');
    }
    if (! empty($input->getOption('warehouse_id')) && (empty($input->getOption('from_date')) || empty($input->getOption('to_date')))) {
      throw new \RuntimeException('倉庫IDを指定する場合は、集計対象日（開始）及び集計対象日（終了）も指定してください');
    }
    if (! empty($input->getOption('from_date')) && ! strptime($input->getOption('from_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('集計対象日（開始）の形式がyyyy-mm-ddではありません[' . $input->getOption('from_date') . ']');
    }
    if (! empty($input->getOption('to_date')) && ! strptime($input->getOption('to_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('集計対象日（終了）の形式がyyyy-mm-ddではありません[' . $input->getOption('to_date') . ']');
    }
    if (! empty($input->getOption('warehouse_id')) && ! is_numeric($input->getOption('warehouse_id'))) {
      throw new \RuntimeException('倉庫IDの形式が正しくありません[' . $input->getOption('warehouse_id') . ']');
    }
  }
}
