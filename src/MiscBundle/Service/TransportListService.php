<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbStockTransport;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\Repository\TbStockTransportRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use UnexpectedValueException;

/**
 * 倉庫移動伝票一括作成サービス
 */
class TransportListService
{
  use ServiceBaseTrait;

  const TEMPORARY_DATABASE_NAME = "TEMPORARY"; // 一時テーブルを作成するデータベース（スキーマ）名

  /**
   * 移動伝票作成メイン処理
   * 
   * ＜他倉庫対応をする場合＞
   * ・tb_stock_transportを作成する際のdestinationとdestination_warehouse_idを差し替える
   * ・移動元倉庫取得を差し替える（スピード便は、getDepartureWarehousesForShoplistSpeedBinを使用）
   * ・その倉庫特有の設定を行なう
   * 
   * @param string $departureDate
   * @param string $arrivalDate
   * @param string $shippingMethod
   * @param string $transportNumber
   * @param string $uploadFilepath
   * @param string $clientName
   * @return void
   */
  public function createShoplistTransportList($departureDate, $arrivalDate, $shippingMethod, $transportNumber, $uploadFilepath, $clientName){
    // temporaryテーブルと作業データ準備
    $this->setupShoplistTemporaryTableAndData($uploadFilepath);

    // 移動伝票を作成
    $this->createShoplistStockTranport($departureDate, $arrivalDate, $shippingMethod, $transportNumber, $clientName);
  }

  // temporaryテーブルと作業データ準備
  private function setupShoplistTemporaryTableAndData($uploadFilepath){
    // 作業用テーブル(一時テーブル倉庫在庫移動一括生成、一時テーブル倉庫別引当可能在庫)作成
    $this->createTempTable();

    // 作業用テーブル一時テーブル倉庫在庫移動一括生成へアップロードファイル読込
    $this->loadDataFromShoplistUploadFile($uploadFilepath);
  }

  // 移動伝票作成。SHOPLISTスピード便用に固定。
  private function createShoplistStockTranport($departureDate, $arrivalDate, $shippingMethod, $transportNumber, $clientName){
    $today = new \DateTime();

    /** @var TbWarehouseRepository $repoWarehouse */
    $repoWarehouse = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    $destinationWarehouse = $repoWarehouse->find(TbWarehouseRepository::SHOPLIST_WAREHOUSE_ID);
    
    // 明細分割件数取得
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
    $stockTransportDetailLimit = (int)$settingRepo->find(TbSetting::KEY_TRANSPORT_LIST_DETAIL_LIMIT)->getSettingVal();

    // shoplistスピード便用納品移動元倉庫を優先順に取得する
    $warehouseList = $this->getDepartureWarehousesForShoplistSpeedBin();

    $stockTransportIdList = []; // 作成したtb_stock_transport.id。後で削除する場合などに使用する
    // 優先順倉庫毎に引当と在庫移動伝票を作成
    foreach($warehouseList as $warehouseId => $warehouseName){
      // 一時テーブル倉庫在庫移動一括生成.確保フラグがすべてのskuで立っていたらループを抜ける
      if (empty($this->getSkuCodeListNotEnough())) break;

      // このループでの倉庫の一時テーブル倉庫別引当可能在庫.引当可能在庫を、この時点でのv_product_stock_picking_assign.stock_remain相当の値でセットする
      $this->upsertStockRemainTargetWarehouses($warehouseId);

      // 一時テーブル倉庫在庫移動一括生成.引当数量累積に該当するskuの一時テーブル倉庫別引当可能在庫.引当可能在庫を加算する
      $this->updateStockRemainStack($warehouseId);

      // 移動伝票insert
      $this->createStockTransportImpl($warehouseId, $clientName, $today
        , $departureDate, $arrivalDate, $transportNumber, $warehouseName, $shippingMethod
        , $stockTransportDetailLimit, $stockTransportIdList, $destinationWarehouse);

      // 一時テーブル倉庫在庫移動一括生成.必要数<=一時テーブル倉庫在庫移動一括生成.引当数量累積のskuの一時テーブル倉庫在庫移動一括生成.確保フラグ=1に更新
      $this->updateEnoughFlg();
    }

    $this->getLogger()->info("作成したtb_stock_transport.id:".implode(",", $stockTransportIdList));

    // 明細が作成されなかったtb_stock_transportを削除する
    $this->deleteStockTransportNotCreateDetail($stockTransportIdList);

    // 在庫が不足している(enough_flg=0)の場合、例外を発生させる
    $skuCodeListStockNotEnough = $this->getSkuCodeListNotEnough();
    if (!empty($skuCodeListStockNotEnough)) {
      // 作成したtb_stock_transport、tb_stock_transport_detailを削除する
      $this->deleteStockTransport($stockTransportIdList);
      throw new UnexpectedValueException("必要数に対して在庫が不足しているskuがありました。対象sku:".implode(",", $skuCodeListStockNotEnough));
    }

    // 最終チェック
    // 倉庫とskuの組み合わせ毎の在庫数を取得して、一時テーブル倉庫別引当可能在庫にセットする。
    $this->upsertStockRemainTargetWarehouses();

    // 移動伝票により引当可能在庫が異常値(一時テーブル倉庫別引当可能在庫.stock_remain<0)になっているsku抽出
    $skuCodeListAmountShortage = $this->skuCodeListAmountShortage();
    if (!empty($skuCodeListAmountShortage)) {
      // 作成したtb_stock_transport、tb_stock_transport_detailを削除する
      $this->deleteStockTransport($stockTransportIdList);
      throw new UnexpectedValueException("作成した移動伝票に対して在庫が不足しているskuがありました。対象sku:".implode(",", $skuCodeListAmountShortage));
    }
  }

  private function createStockTransportImpl($warehouseId, $clientName, $today
  , $departureDate, $arrivalDate, $transportNumber, $warehouseName, $shippingMethod
  , $stockTransportDetailLimit, &$stockTransportIdList, $destinationWarehouse){

    // tb_stock_transportを作成。ここではとりあえず作成して後で明細を作成しなかった場合削除する
    $em = $this->getDoctrine()->getManager('main');

    $expectedDetailCount = $this->getExpectedDetailCount($warehouseId);
    $insertedDetailCount = 0;
    // 同一倉庫で明細上限件数毎に区切って移動伝票を作成する
    while($expectedDetailCount > $insertedDetailCount){
      $transport = new TbStockTransport();
      $transport->setAccount($clientName);
      $transport->setDate($today);
      $transport->setDepartureDate(new \DateTime($departureDate));
      $transport->setArrivalDate(new \DateTime($arrivalDate));
      $transport->setTransportNumber($transportNumber);
      $transport->setTransportCode(TbStockTransportRepository::TRANSPORT_CODE_WAREHOUSE);
      $transport->setDepartureWarehouseId($warehouseId);
      $transport->setDeparture($warehouseName);
      /** @var TbWarehouse $destinationWarehouse */
      $transport->setDestinationWarehouseId($destinationWarehouse->getId()); 
      $transport->setDestination($destinationWarehouse->getName());
      $transport->setShippingMethod($shippingMethod);
      $transport->setShippingNumber("");

      $em->persist($transport);
      // IDが欲しいのでflush
      $em->flush();

      // tb_stock_transport.id配列に保存
      $stockTransportIdList[] = $transport->getId();

      // tb_stock_transport_detailを作成(offset、limit指定で、明細上限件数まで作成する)
      $this->createStockTranportDetail($transport->getId(), $warehouseId, $insertedDetailCount, $stockTransportDetailLimit);
    
      $insertedDetailCount += $stockTransportDetailLimit;
    }
  }

  // 明細が作成されなかったtb_stock_transportを削除する
  private function deleteStockTransportNotCreateDetail($idList){ 
    $dbMain = $this->getDoctrine()->getConnection('main');
    $idListStr = sprintf("'%s'", implode("','", $idList));
    $sql = <<<EOD
    DELETE t 
    FROM
      tb_stock_transport t 
      LEFT JOIN tb_stock_transport_detail td 
        ON t.id = td.transport_id 
    WHERE
      t.id IN ({$idListStr}) 
      AND td.transport_id IS NULL
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  // idで指定したtb_stock_transport、tb_stock_transport_detailを削除する
  private function deleteStockTransport($idList){
    $dbMain = $this->getDoctrine()->getConnection('main');
    $idListStr = sprintf("'%s'", implode("','", $idList));
    // tb_stock_transport_detail削除
    $sql = <<<EOD
    DELETE 
    FROM
      tb_stock_transport_detail 
    WHERE
      transport_id IN ({$idListStr})
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    // tb_stock_transport削除
    $sql = <<<EOD
    DELETE 
    FROM
      tb_stock_transport 
    WHERE
      id IN ({$idListStr})
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  // 引当未完了のsku一覧を取得(確保フラグ=0)
  private function getSkuCodeListNotEnough(){
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
    SELECT
        ne_syohin_syohin_code 
    FROM
        tmp_work_stock_transport_create_list  
    WHERE
        enough_flg = 0 
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }

  // 作成した移動伝票に対して在庫が不足しているskuを抽出
  // 作成した移動伝票分はviewのクエリで拾われるため、単純にtmp_work_stock_transport_stock_remain.stock_remainが0未満を抽出
  private function skuCodeListAmountShortage(){
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
    SELECT
      sr.ne_syohin_syohin_code 
    FROM
      tmp_work_stock_transport_stock_remain sr 
    WHERE
      sr.stock_remain < 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }

  // shoplistスピード便用納品対象倉庫を優先順に取得する
  private function getDepartureWarehousesForShoplistSpeedBin(){
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
    SELECT
        id
        , name
    FROM
        tb_warehouse 
    WHERE
        shoplist_flag <> 0 
        AND transport_priority > 0
    ORDER BY
        transport_priority DESC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /**
   * 引当可能在庫累積を更新する
   * 一時テーブル倉庫在庫移動一括生成.引当可能在庫累積に、一時テーブル倉庫別引当可能在庫.引当可能在庫を加算した値で更新
   * 
   * @param int $targetWarehouseId 対象倉庫
   * @return void
   */
  private function updateStockRemainStack($targetWarehouseId){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
    UPDATE tmp_work_stock_transport_create_list list 
      INNER JOIN tmp_work_stock_transport_stock_remain stock_remain 
        ON list.ne_syohin_syohin_code = stock_remain.ne_syohin_syohin_code 
    SET
      list.stock_remain_stack = list.stock_remain_stack + stock_remain.stock_remain 
    WHERE
      list.enough_flg = 0 -- 一時テーブル倉庫在庫移動一括生成.確保フラグが立っていないskuを対象とする
      AND stock_remain.warehouse_id = :targetWarehouseId;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':targetWarehouseId', $targetWarehouseId, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 確保フラグを更新する
   * 
   * @return void
   */
  private function updateEnoughFlg(){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
    UPDATE tmp_work_stock_transport_create_list list 
    SET
      list.enough_flg = 1 
    WHERE
      list.required_num <= list.stock_remain_stack
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  // tb_stock_transport_detail作成
  private function createStockTranportDetail($transportId, $warehouseId, $offset, $limit){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
    INSERT INTO tb_stock_transport_detail  (
      transport_id
      ,ne_syohin_syohin_code
      ,amount
    )
    SELECT
      :transportId
      , sr.ne_syohin_syohin_code
      , CASE 
        WHEN cl.required_num < cl.stock_remain_stack -- 割り当てられる量が必要数より多い場合
          THEN sr.stock_remain + cl.required_num - cl.stock_remain_stack -- 必要な数だけ移動する(stock_remainより少ない数となる)
        ELSE sr.stock_remain 
        END AS amount 
    FROM
      tmp_work_stock_transport_stock_remain sr 
      INNER JOIN tmp_work_stock_transport_create_list cl 
        ON sr.ne_syohin_syohin_code = cl.ne_syohin_syohin_code 
    WHERE
      cl.enough_flg = 0
      AND sr.stock_remain > 0 
      AND sr.warehouse_id = :warehouseId
    ORDER BY
      sr.ne_syohin_syohin_code ASC
    LIMIT
      :limit OFFSET :offset
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':transportId', $transportId, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouseId', $warehouseId, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();
  }

  // tb_stock_transport_detail作成予定件数取得
  private function getExpectedDetailCount($warehouseId){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
    SELECT
      COUNT(sr.ne_syohin_syohin_code) AS 'tb_stock_transport_detail作成予定件数'
    FROM
      tmp_work_stock_transport_stock_remain sr 
      INNER JOIN tmp_work_stock_transport_create_list cl 
        ON sr.ne_syohin_syohin_code = cl.ne_syohin_syohin_code 
    WHERE
      cl.enough_flg = 0
      AND sr.stock_remain > 0 
      AND sr.warehouse_id = :warehouseId
    ORDER BY
      sr.ne_syohin_syohin_code ASC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouseId, \PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn(0);
    return $result;
  }


  /**
   * 一時テーブルshoplistスピード便用倉庫別移動数量にファイルを取り込む。
   * 想定しているファイルレイアウトは ShoplistSpeedbinService::$CSV_FIELDS_FIXED。
   *
   * @param ファイルパス
   */
  private function loadDataFromShoplistUploadFile($uploadFilepath){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
    LOAD DATA LOCAL INFILE :uploadFilepath
    IGNORE INTO TABLE tmp_work_stock_transport_create_list
    CHARACTER SET cp932
    FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
    LINES TERMINATED BY '\r\n' IGNORE 1 LINES
    (@1, @2, @3, @4, @5, @6, @7, @8, @9, @10, @11, @12)
    SET
    ne_syohin_syohin_code = @3,
    required_num = @7
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':uploadFilepath', $uploadFilepath);
    $stmt->execute();
  }


  /**
   * tmp_work_stock_transport_stock_remainへv_product_stock_picking_assignの値を投入する
   * キーが無ければinsert、既にあればupdateする。
   * また対象倉庫が指定されれば、その倉庫のskuのみを対象とする。
   * <他倉庫対応する場合>
   * shoplist用に倉庫抽出条件を固定にしているので、倉庫id配列をin句指定できるように変更する
   *
   * @param int $targetWarehouseId 対象
   * @return void
   */
  private function upsertStockRemainTargetWarehouses($targetWarehouseId = null){
    $dbMain = $this->getDoctrine()->getConnection('main');

    // view(v_product_stock_picking_assign)はindexが効かないので、view作成クエリを改修してデータ取得する。
    // shoplistフラグや倉庫優先順は最後のwhere句、order句で指定する
    // 対象skuへの絞り込みは一時テーブルとの内部結合で行なう
    $sql = <<<EOD
    INSERT INTO tmp_work_stock_transport_stock_remain  (
      warehouse_id
      , ne_syohin_syohin_code
      , stock_remain
    )
    SELECT
      `T`.`warehouse_id` AS `warehouse_id`
      , `T`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
      , ( 
        ( 
          ( 
            ( 
              (`T`.`stock` - `T`.`shipping_assign`) - `T`.`real_shop_assign`
            ) - `T`.`set_product_assign`
          ) - `T`.`warehouse_move_assign`
        ) - `T`.`transport_assign`
      ) AS `stock_remain` 
    FROM
      ( 
        SELECT
          `S`.`warehouse_id` AS `warehouse_id`
          , `S`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
          , `S`.`stock` AS `stock`
          , coalesce(`DP`.`出荷ピッキング引当`, 0) AS `shipping_assign`
          , coalesce(`JP`.`実店舗ピッキング引当`, 0) AS `real_shop_assign`
          , coalesce(`SP`.`セット商品ピッキング引当`, 0) AS `set_product_assign`
          , coalesce(`SM`.`倉庫在庫ピッキング引当`, 0) AS `warehouse_move_assign`
          , coalesce(`TR`.`transport_assign`, 0) AS `transport_assign` 
        FROM
          ( 
            ( 
              ( 
                ( 
                  ( 
                    ( 
                      ( 
                        SELECT
                          `pl`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
                          , `l`.`warehouse_id` AS `warehouse_id`
                          , sum(`pl`.`stock`) AS `stock` 
                        FROM
                          ( 
                            `tb_product_location` `pl` JOIN `tb_location` `l` 
                              ON ((`pl`.`location_id` = `l`.`id`))
                          ) 
                        WHERE
                          `l`.`warehouse_id` = :targetWarehouseId
                        GROUP BY
                          `l`.`warehouse_id`
                          , `pl`.`ne_syohin_syohin_code`
                      )
                    ) `S` 
                      LEFT JOIN ( 
                        SELECT
                          `pl`.`商品コード` AS `商品コード`
                          , `pl`.`warehouse_id` AS `warehouse_id`
                          , sum(`pl`.`総ピッキング数`) AS `出荷ピッキング引当` 
                        FROM
                          `tb_delivery_picking_list` `pl` 
                        WHERE
                          (`pl`.`picking_status` = 0) 
                            AND `pl`.`warehouse_id` = :targetWarehouseId
                        GROUP BY
                          `pl`.`warehouse_id`
                          , `pl`.`商品コード`
                      ) `DP` 
                        ON ( 
                          ( 
                            (`S`.`warehouse_id` = `DP`.`warehouse_id`) 
                            AND (`S`.`ne_syohin_syohin_code` = `DP`.`商品コード`)
                          )
                        )
                  ) 
                    LEFT JOIN ( 
                      SELECT
                        `pl`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
                        , 1 AS `warehouse_id`
                        , sum(`pl`.`move_num`) AS `実店舗ピッキング引当` 
                      FROM
                        `tb_real_shop_picking_list` `pl` 
                      WHERE
                        (`pl`.`status` = 0) 
                      GROUP BY
                        `pl`.`ne_syohin_syohin_code`
                    ) `JP` 
                      ON ( 
                        ( 
                          (`S`.`warehouse_id` = `JP`.`warehouse_id`) 
                          AND ( 
                            `S`.`ne_syohin_syohin_code` = `JP`.`ne_syohin_syohin_code`
                          )
                        )
                      )
                ) 
                  LEFT JOIN ( 
                    SELECT
                      `pl`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
                      , 1 AS `warehouse_id`
                      , sum(`pl`.`move_num`) AS `セット商品ピッキング引当` 
                    FROM
                      `tb_set_product_picking_list` `pl` 
                    WHERE
                      (`pl`.`status` = 0) 
                    GROUP BY
                      `pl`.`ne_syohin_syohin_code`
                  ) `SP` 
                    ON ( 
                      ( 
                        (`S`.`warehouse_id` = `SP`.`warehouse_id`) 
                        AND ( 
                          `S`.`ne_syohin_syohin_code` = `SP`.`ne_syohin_syohin_code`
                        )
                      )
                    )
              ) 
                LEFT JOIN ( 
                  SELECT
                    `pl`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
                    , `pl`.`warehouse_id` AS `warehouse_id`
                    , sum(`pl`.`shortage`) AS `倉庫在庫ピッキング引当` 
                  FROM
                    `tb_warehouse_stock_move_picking_list` `pl` 
                  WHERE
                    (`pl`.`status` = 0) 
                    AND `pl`.`warehouse_id` = :targetWarehouseId
                  GROUP BY
                    `pl`.`ne_syohin_syohin_code`
                ) `SM` 
                  ON ( 
                    ( 
                      (`S`.`warehouse_id` = `SM`.`warehouse_id`) 
                      AND ( 
                        `S`.`ne_syohin_syohin_code` = `SM`.`ne_syohin_syohin_code`
                      )
                    )
                  )
            ) 
              LEFT JOIN ( 
                SELECT
                  `t`.`departure_warehouse_id` AS `warehouse_id`
                  , `d`.`ne_syohin_syohin_code` AS `ne_syohin_syohin_code`
                  , sum((`d`.`amount` - coalesce(`pl`.`move_num`, 0))) AS `transport_assign` 
                FROM
                  ( 
                    ( 
                      `tb_stock_transport_detail` `d` JOIN `tb_stock_transport` `t` 
                        ON ((`d`.`transport_id` = `t`.`id`))
                    ) 
                      LEFT JOIN `tb_warehouse_stock_move_picking_list` `pl` 
                        ON ( 
                          ( 
                            ( 
                              `t`.`departure_warehouse_id` = `pl`.`warehouse_id`
                            ) 
                            AND (`t`.`picking_list_date` = `pl`.`date`) 
                            AND (`t`.`picking_list_number` = `pl`.`number`) 
                            AND ( 
                              `d`.`ne_syohin_syohin_code` = `pl`.`ne_syohin_syohin_code`
                            ) 
                            AND (`pl`.`status` = 0)
                          )
                        )
                  ) 
                WHERE
                  ( 
                    (`t`.`departure_warehouse_id` = :targetWarehouseId) 
                    AND (`t`.`status` = 0)
                  ) 
                GROUP BY
                  `t`.`departure_warehouse_id`
                  , `d`.`ne_syohin_syohin_code`
              ) `TR` 
                ON ( 
                  ( 
                    (`S`.`warehouse_id` = `TR`.`warehouse_id`) 
                    AND ( 
                      `S`.`ne_syohin_syohin_code` = `TR`.`ne_syohin_syohin_code`
                    )
                  )
                )
          )
      ) `T`
    INNER JOIN tb_warehouse w ON `T`.`warehouse_id` = w.id
    INNER JOIN tmp_work_stock_transport_create_list list ON `T`.`ne_syohin_syohin_code` = list.ne_syohin_syohin_code
    WHERE
        w.shoplist_flag <> 0
        AND w.transport_priority > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':targetWarehouseId', $targetWarehouseId, \PDO::PARAM_INT);
    $stmt->execute();
  }

  // temporary table create
  private function createTempTable(){ 
    $dbMain = $this->getDoctrine()->getConnection('main');

    $temporaryWord = self::TEMPORARY_DATABASE_NAME;

    // 一時テーブル倉庫在庫移動一括生成
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_stock_transport_create_list");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_stock_transport_create_list   (
        ne_syohin_syohin_code VARCHAR(255) NOT NULL PRIMARY KEY 
        , required_num INTEGER NOT NULL DEFAULT 0 
        , stock_remain_stack INTEGER NOT NULL DEFAULT 0 
        , enough_flg TINYINT NOT NULL DEFAULT 0
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8
EOD;
    $dbMain->exec($sql);

    // 一時テーブル倉庫別引当可能在庫
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_stock_transport_stock_remain");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_stock_transport_stock_remain    (
        warehouse_id INTEGER NOT NULL
        , ne_syohin_syohin_code VARCHAR(255) NOT NULL 
        , stock_remain INTEGER NOT NULL DEFAULT 0 
        , PRIMARY KEY (warehouse_id, ne_syohin_syohin_code)
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8
EOD;
    $dbMain->exec($sql);
  }
}
