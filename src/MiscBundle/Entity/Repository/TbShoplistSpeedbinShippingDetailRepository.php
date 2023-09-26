<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Entity\Repository\TbWarehouseRepository;


class TbShoplistSpeedbinShippingDetailRepository extends BaseRepository
{
  
  /**
   * 指定されたIDの、納入予定ファイル用のデータを取得する。
   */
  public function findPlannedCsvData($shoplistSpeedbinShippingId) {
    $sql = <<<EOD
      SELECT
        sku_code as '卸品番'
        , planned_quantity as '個数'
      FROM tb_shoplist_speedbin_shipping_detail
      WHERE shoplist_speedbin_shipping_id = :id
        AND planned_quantity > 0
      ORDER BY sku_code
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $shoplistSpeedbinShippingId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * 指定されたIDの、確定ファイル用のデータを取得する。
   * 
   * 日付は月／日の先頭にゼロをつけない
   * 
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   * @param \DateTime|\DateTimeImmutable $shippingDate 出荷予定日（時分秒は無視）
   * @return array 確定ファイル用のデータ
   */
  public function findFixedCsvData($shoplistSpeedbinShippingId, $shippingDate) {
    $sql = <<<EOD
      SELECT
        s.shoplist_order_id as '発注番号'
        , DATE_FORMAT(s.created, '%Y/%c/%e') as '発注日'
        , sd.sku_code as '卸品番'
        , mp.daihyo_syohin_name as '商品名'
        , pci.colname as '横軸項目'
        , pci.rowname as '縦軸項目'
        , sd.fixed_quantity as '発注数'
        , sd.fixed_quantity as '納品可能数'
        , 0 as '納品不可能数'
        , :shippingDate as '出荷予定日'
        , 0 as '発注依頼停止 (停止したい場合は1)'
        , 1 as '回答確定（確定の場合は1／未確定の場合は0）'
      FROM tb_shoplist_speedbin_shipping s
      JOIN tb_shoplist_speedbin_shipping_detail sd ON sd.shoplist_speedbin_shipping_id = s.id
      JOIN tb_productchoiceitems pci ON pci.ne_syohin_syohin_code = sd.sku_code
      JOIN tb_mainproducts mp ON pci.daihyo_syohin_code = mp.daihyo_syohin_code
      WHERE shoplist_speedbin_shipping_id = :id
        AND fixed_quantity > 0
      ORDER BY sku_code
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':shippingDate', $shippingDate->format('Y/n/j'), \PDO::PARAM_STR);
    $stmt->bindValue(':id', $shoplistSpeedbinShippingId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * 指定されたIDの、ラベル生成用のデータを取得する。
   * 形式は、SHOPLISTで「出荷実績報告用データ」と呼ばれているもの。
   * 
   * ラベル出力に関係のない部分（価格や在庫数）はダミー値とする。
   * 
   * 移動伝票単位で生成するためのデータ取得処理が、ShoplistSpeedbinService::findLabelCsvDataByTransportId に存在する。
   * 取得内容を変更する際は合わせて変更すること。
   *
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   * @return array ラベル生成用データ
   */
  public function findLabelCsvData($shoplistSpeedbinShippingId) {
    
    // このCSVは、もともとNextEngineで出力していたものなので
    // 原価はNextEngineに登録しているものに合わせて cost_tanka
    // 金額はSHOPLIST売価を入れる
    $sql = <<<EOD
      SELECT
        sd.sku_code as '商品ｺｰﾄﾞ'
        , mp.daihyo_syohin_name as '商品名'
        , cal.cost_tanka as '原価'
        , '取扱中' as '取扱区分'
        , '予約' as '商品区分'
        , code.barcode as '型番'
        , si.baika_tanka as '金額' 
        , sd.fixed_quantity as '受注数'
        , sd.fixed_quantity as '引当数'
        , 0 as '欠品数'
        , sd.fixed_quantity as '在庫数'   
      FROM tb_shoplist_speedbin_shipping s
      JOIN tb_shoplist_speedbin_shipping_detail sd ON sd.shoplist_speedbin_shipping_id = s.id
      JOIN tb_productchoiceitems pci ON pci.ne_syohin_syohin_code = sd.sku_code
      JOIN tb_mainproducts mp ON pci.daihyo_syohin_code = mp.daihyo_syohin_code
      JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      JOIN tb_shoplist_information si ON pci.daihyo_syohin_code = si.daihyo_syohin_code
      JOIN tb_product_code code ON code.ne_syohin_syohin_code = sd.sku_code
      WHERE shoplist_speedbin_shipping_id = :id
        AND fixed_quantity > 0
      ORDER BY sku_code
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':id', $shoplistSpeedbinShippingId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * スピード便の出荷対象となり得る、基準SKUとその期間内販売数をDBに登録する。
   * 集計開始日 $fromDate 後、SHOPLISTで販売されたSKUが対象となる。
   * 
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   * @param \DateTime|\DateTimeImmutable $fromDate 集計開始日。この日を含む。時分秒は無視。
   * @return int 登録件数
   */
  public function insertSpeedbinBaseSku($shoplistSpeedbinShippingId, $fromDate) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      INSERT INTO tb_shoplist_speedbin_shipping_detail
      SELECT 
        :shoplistSpeedbinShippingId shoplist_speedbin_shipping_id
        , ne_syohin_syohin_code sku_code
        , SUM(num_total) sales_quantity_shoplist
        , 0 current_speedbin_stock_quantity
        , 0 transporting_quantity
        , 0 warehouse_stock_quantity
        , 0 unshipped_sales_quantity
        , 0 not_for_sale_quantity
        , 0 sales_quantity_other
        , 0 deliverable_quantity
        , 0 planned_quantity
        , 0 fixed_quantity
      FROM tb_shoplist_daily_sales
      WHERE order_date >= :fromDate
      GROUP BY ne_syohin_syohin_code 
      ORDER BY ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d'));
    $stmt->execute();
    return $stmt->rowCount();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、現在のスピード便在庫数を集計してSHOPLISTスピード便出荷明細に反映する。
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function updateCurrentSpeedbinStockQuantity($shoplistSpeedbinShippingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN tb_product_location pl ON d.sku_code = pl.ne_syohin_syohin_code
      JOIN tb_location l ON pl.location_id = l.id
      SET d.current_speedbin_stock_quantity = pl.stock
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
        AND l.warehouse_id = :shoplistWarehouseId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':shoplistWarehouseId', TbWarehouseRepository::SHOPLIST_WAREHOUSE_ID);
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、スピード便向け移動中在庫数を集計してSHOPLISTスピード便出荷明細に反映する。
   * ここでいう移動中在庫数の条件は以下の通り
   * 移動伝票のうち
   *   ・倉庫在庫ピッキング作成済み
   *   ・移動伝票（tb_stock_transport）ステータスが未処理、準備済み、輸送中、到着
   * 
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function updateTransportingQuantity($shoplistSpeedbinShippingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT d.ne_syohin_syohin_code
          , CASE 
            WHEN t.status = 0 THEN d.amount
            ELSE d.picked
          END as quantity
        FROM tb_stock_transport t
        JOIN tb_stock_transport_detail d ON t.id = d.transport_id
        WHERE t.status IN (:statusNone, :statusReady, :statusShipping, :statusArrived)
          AND destination_warehouse_id = :shoplistWarehouseId
          AND picking_list_date IS NOT NULL
          AND picking_list_number IS NOT NULL
      ) t ON d.sku_code = t.ne_syohin_syohin_code
      SET d.transporting_quantity = t.quantity
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':shoplistWarehouseId', TbWarehouseRepository::SHOPLIST_WAREHOUSE_ID);
    $stmt->bindValue(':statusNone', TbStockTransportRepository::STATUS_NONE);
    $stmt->bindValue(':statusReady', TbStockTransportRepository::STATUS_READY);
    $stmt->bindValue(':statusShipping', TbStockTransportRepository::STATUS_SHIPPING);
    $stmt->bindValue(':statusArrived', TbStockTransportRepository::STATUS_ARRIVED);
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、販売可能倉庫在庫数を集計してSHOPLISTスピード便出荷明細に反映する。
   * ここでいう販売可能倉庫在庫数の条件は以下の通り
   *  ・販売可能倉庫の在庫数
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function updateWarehouseStockQuantity($shoplistSpeedbinShippingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT pl.ne_syohin_syohin_code
          , SUM(pl.stock) as stock
        FROM tb_shoplist_speedbin_shipping_detail d 
        JOIN tb_product_location pl ON pl.ne_syohin_syohin_code = d.sku_code
        JOIN tb_location l ON l.id = pl.location_id
        JOIN tb_warehouse w ON w.id = l.warehouse_id AND w.sale_enabled <> 0
        WHERE  d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
        GROUP BY pl.ne_syohin_syohin_code
      ) pl ON pl.ne_syohin_syohin_code = d.sku_code
      SET warehouse_stock_quantity = pl.stock
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、未出荷受注数量を集計してSHOPLISTスピード便出荷明細に反映する。
   * ここでいう未出荷受注数量の条件は以下の通り
   *  ・受注状態 <> 出荷確定済（完了）
   *  ・キャンセル区分 = 0
   *  ・明細行キャンセル = 0
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function updateUnshippedSalesQuantity($shoplistSpeedbinShippingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT a.商品コード（伝票） ne_syohin_syohin_code
          , SUM(a.受注数) as quantity
        FROM tb_shoplist_speedbin_shipping_detail d
        JOIN tb_sales_detail_analyze a USE INDEX(index_有効受注) ON a.商品コード（伝票） = d.sku_code 
        WHERE  d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
          AND a.受注状態 <> '出荷確定済（完了）'
          AND a.キャンセル区分 = 0
          AND a.明細行キャンセル = 0
        GROUP BY a.商品コード（伝票）
      ) a ON a.ne_syohin_syohin_code = d.sku_code
      SET unshipped_sales_quantity = a.quantity
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':shoplistId', TbShoppingMall::NE_MALL_ID_SHOPLIST);
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、販売不可在庫を集計してSHOPLISTスピード便出荷明細に反映する。
   * ここでいう販売不可在庫の条件は以下の通り
   *  ・販売可能倉庫に存在する販売不可在庫。
   *   （fba_multi_flag <> 0 の商品が販売不可在庫に該当する。この考え方は v_product_stock_not_for_sale に合わせている）
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function updateNotForSaleQuantity($shoplistSpeedbinShippingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT pl.ne_syohin_syohin_code
          , SUM(pl.stock) as stock
        FROM tb_shoplist_speedbin_shipping_detail d
        JOIN tb_product_location pl ON pl.ne_syohin_syohin_code = d.sku_code
        JOIN tb_location l ON l.id = pl.location_id
        JOIN tb_warehouse w ON w.id = l.warehouse_id AND w.sale_enabled <> 0
        JOIN tb_productchoiceitems pci ON pci.ne_syohin_syohin_code = d.sku_code
        JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code AND fba_multi_flag <> 0
        WHERE  d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
          AND warehouse_id <> :fba
        GROUP BY pl.ne_syohin_syohin_code
      ) pl ON pl.ne_syohin_syohin_code = d.sku_code
      SET not_for_sale_quantity = pl.stock
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':fba', TbWarehouseRepository::FBA_MULTI_WAREHOUSE_ID);
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、他店舗販売量を集計してSHOPLISTスピード便出荷明細に反映する。
   * ここでいう他店舗販売量の条件は以下の通り
   * ・以下を除いた店舗の1か月間の販売数量合計（IDはNE店舗ID）
   *   3: Plus Nao本店
   *   4: Plus Naoフリーオーダー
   *   5: IAM1号店
   *   11: Yours1号店
   *   17: フリマ
   *   18: SHOPLIST PLUSNAO
   *   19: 在庫引き抜き用
   *   22: Club Plus Nao
   *   23: Club Forest
   *   24: SUPER DELIVERY店
   *   25: 楽天ロジ
   *   26: 楽天市場SHANZE店
   *   28: CRESTWOOD
   *   30: (仮)WEBで注文、倉庫で受け取り
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   * @param \DateTime|\DateTimeImmutable $fromDate 集計開始日。この日を含む。時分秒は無視。
   */
  public function updateSalesQuantityOther($shoplistSpeedbinShippingId, $fromDate) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT a.商品コード（伝票） ne_syohin_syohin_code
          , SUM(a.受注数) as quantity
        FROM tb_shoplist_speedbin_shipping_detail d
        JOIN tb_sales_detail_analyze a USE INDEX(index_受注日) ON a.商品コード（伝票） = d.sku_code AND a.受注日 >= :fromDate
        WHERE  d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
          AND a.キャンセル区分 = 0
          AND a.明細行キャンセル = 0
          AND a.店舗コード NOT IN (
            3, 4, 5, 11, 17, 18, 19, 22, 23, 24, 25, 26, 28, 30
          )
        GROUP BY a.商品コード（伝票）
      ) a ON a.ne_syohin_syohin_code = d.sku_code
      SET sales_quantity_other = a.quantity
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d'));
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、SHOPLIST納品可能倉庫在庫を集計してSHOPLISTスピード便出荷明細に反映する。
   * ここでいう販売可能倉庫在庫数の条件は以下の通り
   *  ・販売可能倉庫の在庫数
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function updateDeliverableQuantity($shoplistSpeedbinShippingId) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT pl.ne_syohin_syohin_code
          , SUM(pl.stock) as stock
        FROM tb_shoplist_speedbin_shipping_detail d
        JOIN tb_product_location pl ON pl.ne_syohin_syohin_code = d.sku_code
        JOIN tb_location l ON l.id = pl.location_id
        JOIN tb_warehouse w ON w.id = l.warehouse_id AND w.shoplist_flag <> 0
        WHERE  d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
        GROUP BY pl.ne_syohin_syohin_code
      ) pl ON pl.ne_syohin_syohin_code = d.sku_code
      SET deliverable_quantity = pl.stock
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->execute();
  }
  
  /**
   * 指定されたSHOPLISTスピード便出荷IDの、出荷予定数を集計してSHOPLISTスピード便出荷明細に反映する。
   * 
   * SKUごとに以下1, 2, 3を計算し、もっとも小さい数が今回の納品数となる。
   *   1. SHOPLIST販売販売量からの予測販売量(A) - SHOPLISTスピード便在庫数(B) - SHOPLISTスピード便向けの移動中在庫数(C)
   *   2. 販売可能倉庫在庫数(D) - 未出荷受注数量(E) - 販売不可在庫(F) - 他店舗販売量からの販売予測量(G)
   *   3. SHOPLIST納品可能倉庫在庫(H) - SHOPLIST納品可能倉庫 最低保管数量(I)
   * 
   * @param integer $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   * @param float $shoplistSalesCoefficient SHOPLIST販売予測量算出のための係数。実際の販売量にこの係数をかけて予測量を算出する。
   * @param integer $keepStock SHOPLIST出荷倉庫最低保管数量
   */
  public function updatePlannedQuantity($shoplistSpeedbinShippingId, $shoplistSalesCoefficient, $keepStock) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_speedbin_shipping_detail d
      JOIN (
        SELECT sku_code, (CASE WHEN LEAST(A, B, C) > 0 THEN LEAST(A, B, C) ELSE 0 END) as planned_quantity
        FROM (
          SELECT sku_code 
            , (TRUNCATE(sales_quantity_shoplist * :shoplistSalesCoefficient, 0) 
                - current_speedbin_stock_quantity 
                - transporting_quantity) as A
            , (warehouse_stock_quantity - unshipped_sales_quantity - not_for_sale_quantity - sales_quantity_other) as B
            , (deliverable_quantity - :keepStock) AS C
          FROM tb_shoplist_speedbin_shipping_detail
          WHERE shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
            AND TRUNCATE(sales_quantity_shoplist * :shoplistSalesCoefficient, 0) 
                - current_speedbin_stock_quantity 
                - transporting_quantity > 0
        ) S
      ) calc ON d.sku_code = calc.sku_code
      SET d.planned_quantity = calc.planned_quantity
      WHERE d.shoplist_speedbin_shipping_id = :shoplistSpeedbinShippingId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shoplistSpeedbinShippingId', $shoplistSpeedbinShippingId);
    $stmt->bindValue(':shoplistSalesCoefficient', $shoplistSalesCoefficient);
    $stmt->bindValue(':keepStock', $keepStock);
    $stmt->execute();
  }
}

