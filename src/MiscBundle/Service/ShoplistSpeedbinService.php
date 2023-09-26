<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\Repository\TbShoplistSpeedbinShippingDetailRepository;
use MiscBundle\Entity\Repository\TbShoplistSpeedbinShippingRepository;
use MiscBundle\Entity\Repository\TbPostalZipsiwakeRepository;
use MiscBundle\Service\ServiceBaseTrait;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoplistSpeedbinShipping;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;

/**
 * SHOPLISTスピード便Service。
 *
 * 
 */
class ShoplistSpeedbinService
{
  use ServiceBaseTrait;
  
  /**
   * スピード便納品数算出処理。
   * 
   * SKUごとに以下を計算し、データをDBに保存し、最終的な納品数を保存する。
   *   1. SHOPLIST販売販売量からの予測販売量(A) - SHOPLISTスピード便在庫数(B) - SHOPLISTスピード便向けの移動中在庫数(C)
   *   2. 販売可能倉庫在庫数(D) - 未出荷受注数量(E) - 販売不可在庫(F) - 他店舗販売量からの販売予測量(G)
   *   3. SHOPLIST納品可能倉庫在庫(H) - SHOPLIST納品可能倉庫 最低保管数量(I)
   *   
   * @param int $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   */
  public function calculateSpeedbinDeliveryAmount($shoplistSpeedbinShippingId) {
    $logger = $this->getLogger();
    /** @var TbShoplistSpeedbinShippingDetailRepository $detailRepo */
    $detailRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistSpeedbinShippingDetail');
    $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
    $salesTotalingFromDateShoplist = $today->modify('-4 week'); // 販売量の集計開始日（SHOPLIST）
    $salesTotalingFromDateOther = $today->modify('-1 month'); // 販売量の集計開始日（その他）
    
    $logger->debug("SHOPLISTスピード便集計：販売量の集計開始日 SHOPLIST[" 
        . $salesTotalingFromDateShoplist->format('Y-m-d H:i:s') 
        . "] その他[" . $salesTotalingFromDateOther->format('Y-m-d H:i:s') . "]");
    
    // 明細データを構築する
    $targetCount = $detailRepo->insertSpeedbinBaseSku($shoplistSpeedbinShippingId, $salesTotalingFromDateShoplist);
    if ($targetCount == 0) {
      return;
    }
    
    $detailRepo->updateCurrentSpeedbinStockQuantity($shoplistSpeedbinShippingId);
    $detailRepo->updateTransportingQuantity($shoplistSpeedbinShippingId);
    $detailRepo->updateWarehouseStockQuantity($shoplistSpeedbinShippingId);
    $detailRepo->updateUnshippedSalesQuantity($shoplistSpeedbinShippingId);
    $detailRepo->updateNotForSaleQuantity($shoplistSpeedbinShippingId);
    $detailRepo->updateSalesQuantityOther($shoplistSpeedbinShippingId, $salesTotalingFromDateOther);
    $detailRepo->updateDeliverableQuantity($shoplistSpeedbinShippingId);
    
    // 今回の出荷予定数を計算する
    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();
    $keepStock = $commonUtil->getSettingValue(TbSetting::KEY_SHOPLIST_SPEEDBIN_KEEP_STOCK); // SHOPLIST納品可能倉庫 最低保管数量
    $shoplistSalesCoefficient = 0.25; // SHOPLIST販売予測量係数 0.25
    $detailRepo->updatePlannedQuantity($shoplistSpeedbinShippingId, $shoplistSalesCoefficient, $keepStock);
  }
  
  /**
   * SHOPLISTからダウンロードした確定ファイルCSVテンプレートを読み込み、以下の値を更新する。
   * ・発注番号
   * ・発注数
   * 
   * @param int $shoplistSpeedbinShippingId SHOPLISTスピード便出荷ID
   * @param resource $fp CSVファイルのポインタ fseek して先頭に戻してから渡すこと
   */
  public function importShoplistFixedCsv($shoplistSpeedbinShippingId, $fp) {
    $logger = $this->getLogger();
    $logger->debug("ID:[ $shoplistSpeedbinShippingId ]");
    $cnt = 0; // 現在の行数
    $shiplistOrderId = null; // SHOPLIST発注番号
    $dataList = []; // バルクアップデートする更新データ
    
    $orderIdPos = null;
    $skuCodePos = null;
    $orderNumPos = null;
    while ($row = fgetcsv($fp)) {
      $cnt++; // $cnｔは先に加算するので1行目が1
      if ($cnt === 1) {
        for ($i = 0; $i < count($row); $i++) {
          if ($row[$i] == '発注番号') {
            $orderIdPos = $i;
          } else if ($row[$i] == '卸品番') {
            $skuCodePos = $i;
          } else if ($row[$i] == '発注数') {
            $orderNumPos = $i;
          }
        }
      }
      if ($cnt === 2) {
        $shiplistOrderId = $row[$orderIdPos];
      }
      $data = [
        'shoplist_speedbin_shipping_id' => $shoplistSpeedbinShippingId
        , 'sku_code' => $row[$skuCodePos]
        , 'fixed_quantity' => $row[$orderNumPos]
      ];
      $dataList[] = $data;
    }
    
    // 発注数更新
    // 一括insertによるUPDATE
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();
    $insertBuilder = new MultiInsertUtil("tb_shoplist_speedbin_shipping_detail", [
      'fields' => [
        'shoplist_speedbin_shipping_id' => \PDO::PARAM_INT
        , 'sku_code' => \PDO::PARAM_STR
        , 'fixed_quantity' => \PDO::PARAM_INT
      ]
      , 'postfix' => " ON DUPLICATE KEY UPDATE "
        . "fixed_quantity = VALUES(fixed_quantity) "
    ]);
    $commonUtil->multipleInsert($insertBuilder, $dbMain, $dataList, function($row) {
      $item = $row;
      return $item;
    }, 'foreach');
    
    // 発注番号・ステータス更新
    /** @var TbShoplistSpeedbinShippingRepository $shippingRepo */
    $sRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistSpeedbinShipping');
    $shipping = $sRepo->find($shoplistSpeedbinShippingId);
    $shipping->setShoplistOrderId($shiplistOrderId);
    $shipping->setStatus(TbShoplistSpeedbinShipping::STATUS_IMPORTED);
    $em = $this->getDoctrine()->getManager('main');
    $em->flush();
  }

  /**
   * SHOPLISTラベル出力用CSVを、移動伝票単位で生成するデータを取得する。
   * 
   * Shoplistスピード便の出荷伝票単位で生成するためのデータ取得処理が、TbShoplistSpeedbinShippingDetailRepository::findLabelCsvData に存在する。
   * 取得内容を変更する際は合わせて変更すること。
   * 
   * @param unknown $transportId 移動伝票ID
   * @return array ラベル生成用データ
   * @see TbShoplistSpeedbinShippingDetailRepository::findLabelCsvData($shoplistSpeedbinShippingId)
   */
  public function findLabelCsvDataByTransportId($transportId) {
    $dbMain = $this->getDb('main');
    
    // このCSVは、もともとNextEngineで出力していたものなので
    // 原価はNextEngineに登録しているものに合わせて cost_tanka
    // 金額はSHOPLIST売価を入れる
    $sql = <<<EOD
      SELECT
        td.ne_syohin_syohin_code as '商品ｺｰﾄﾞ'
        , mp.daihyo_syohin_name as '商品名'
        , cal.cost_tanka as '原価'
        , '取扱中' as '取扱区分'
        , '予約' as '商品区分'
        , code.barcode as '型番'
        , si.baika_tanka as '金額'
        , td.amount as '受注数'
        , td.amount as '引当数'
        , 0 as '欠品数'
        , td.amount as '在庫数'
      FROM tb_stock_transport t
      JOIN tb_stock_transport_detail td ON td.transport_id = t.id
      JOIN tb_productchoiceitems pci ON pci.ne_syohin_syohin_code = td.ne_syohin_syohin_code
      JOIN tb_mainproducts mp ON pci.daihyo_syohin_code = mp.daihyo_syohin_code
      JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      JOIN tb_shoplist_information si ON pci.daihyo_syohin_code = si.daihyo_syohin_code
      JOIN tb_product_code code ON code.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      WHERE t.id = :id
        AND amount > 0
      ORDER BY td.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':id', $transportId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  // 納入予定CSVヘッダ
  public static $CSV_FIELDS_PLANNED = [
    '卸品番'
    , '個数'
  ];
  
  // 確定CSVヘッダ
  public static $CSV_FIELDS_FIXED = [
    '発注番号' => '発注番号'
    , '発注日' => '発注日'
    , '卸品番' => '卸品番'
    , '商品名' => '商品名'
    , '横軸項目' => '横軸項目'
    , '縦軸項目' => '縦軸項目'
    , '発注数' => '発注数'
    , '納品可能数' => '納品可能数'
    , '納品不可能数' => '納品不可能数'
    , '出荷予定日' => '出荷予定日'
    , '発注依頼停止 (停止したい場合は1)' => '発注依頼停止 (停止したい場合は1)'
    , '回答確定（確定の場合は1／未確定の場合は0）' => '回答確定（確定の場合は1／未確定の場合は0）'
  ];
  
  // ラベル生成用 （出荷実績報告用データ形式）
  public static $CSV_SHIPPING_REPORT = [
    "商品ｺｰﾄﾞ" => "商品ｺｰﾄﾞ"
    , "商品名" => "商品名"
    , "原価" => "原価"
    , "取扱区分" => "取扱区分"
    , "商品区分" => "商品区分"
    , "型番" => "型番"
    , "金額" => "金額"
    , "受注数" => "受注数"
    , "引当数" => "引当数"
    , "欠品数" => "欠品数"
    , "在庫数" => "在庫数"    
  ];
}