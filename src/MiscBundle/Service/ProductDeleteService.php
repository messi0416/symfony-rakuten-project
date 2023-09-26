<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRepository;
use MiscBundle\Entity\Repository\TbSalesDetailAnalyzeRepository;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Util\StringUtil;
use Symfony\Component\DependencyInjection\Container;


/**
 * 商品削除サービス
 */
class ProductDeleteService
{
  use ServiceBaseTrait;
  
  /**
   * 削除のために必要な情報を取得する。
   * 
   * 商品情報と、削除不可な要件がないかを収集し、結果を配列で返却する。
   * 現在は以下を取得する。
   * ・product 画面表示用の商品情報
   * ・stock 総在庫
   * ・remain 注残
   * ・sales 受注
   * ・setProduct 指定された商品を構成品とするセット商品
   * ・productSalesAccount 有効な商品売上担当者（適用期間外も有効）
   * ・deleteExcluded 削除除外商品
   * ・otoriyose おとりよせ販売中か否か（おとりよせはあっても削除は可能。警告レベル）
   * ・canDelete 上記を元に、削除可能かどうかの判定
   */
  public function findProductInfoForDelete($daihyoSyohinCode) {
    $productDeleteInfo = [
      'product' => null 
      , 'stock' => null 
      , 'remain' => null
      , 'sales' => null
      , 'setProduct' => null
      , 'productSalesAccount' => null
      , 'deleteExcluded' => null
      , 'otoriyose' => null 
      , 'canDelete' => false
    ];
    
    /** @var TbMainproductsRepository $mainProductsRepo */
    $mainProductsRepo = $this->getDoctrine()->getRepository("MiscBundle:TbMainproducts");
    $productDeleteInfo['product'] = $mainProductsRepo->findDaihyoSyohinBaseInfoForEdit($daihyoSyohinCode);
     /** @var TbProductchoiceitemsRepository $pciRepo */
    $pciRepo = $this->getDoctrine()->getRepository("MiscBundle:TbProductchoiceitems");
    $skuList = $pciRepo->findSkuCodeOnlyByDaihyoSyohinCode($daihyoSyohinCode);
    $productDeleteInfo['stock'] = $this->existTotalStock($skuList);
    $productDeleteInfo['remain'] = $this->existRemainOrder($skuList);
    $productDeleteInfo['sales'] = $this->existSalesHistory($skuList);
    $productDeleteInfo['setProduct'] = $this->findUsedInSetProduct($daihyoSyohinCode);
    $productDeleteInfo['productSalesAccount'] = $this->existProductSalesAccount($daihyoSyohinCode);
    $productDeleteInfo['deleteExcluded'] = $this->existDeleteExcludedProduct($daihyoSyohinCode);
    $productDeleteInfo['otoriyose'] = $this->isOnSaleOtoriyose($daihyoSyohinCode);
    
    $productDeleteInfo['canDelete'] = $this->canDelete($productDeleteInfo);
    return $productDeleteInfo;
  }
  
  /**
   * 商品が削除できるかチェックする。
   * 
   * @param array $productDeleteInfo
   */
  private function canDelete($productDeleteInfo) {
    if ($productDeleteInfo['stock']
        || $productDeleteInfo['remain']
        || $productDeleteInfo['sales']
        || $productDeleteInfo['setProduct']
        || $productDeleteInfo['productSalesAccount']
        || $productDeleteInfo['deleteExcluded']) {
      return false;
    }
    return true;
  }
  
  /**
   * 総在庫があるかチェックする。あれば true、なければ falseを返却する。
   * @param array $skuList SKUのリスト
   * @return boolean 総在庫があるSKUが存在すれば true、全てなければ false
   */
  private function existTotalStock($skuList) {
    /** @var TbProductchoiceitemsRepository $pciRepo */
    $pciRepo = $this->getDoctrine()->getRepository("MiscBundle:TbProductchoiceitems");
    $totalStockInfo = $pciRepo->findTotalStock($skuList);
    foreach($totalStockInfo as $k => $v) {
      if ($v > 0) {
        return true;
      }
    }
    return false;
  }
  
  /**
   * 注残があるかチェックする。あれば true、なければ falseを返却する。
   * @param array $skuList SKUのリスト
   * @return boolean 注残があるSKUが存在すれば true、全てなければ false
   */
  private function existRemainOrder($skuList) {
    /** @var TbIndividualorderhistoryRepository $idhRepo */
    $idhRepo = $this->getDoctrine()->getRepository("MiscBundle:TbIndividualorderhistory");
    $orderRemainInfo = $idhRepo->findOrderRemain($skuList);
    foreach($orderRemainInfo as $k => $v) {
      if ($v > 0) {
        return true;
      }
    }
    return false;
  }
  
  /**
   * 受注履歴があるかチェックする。あれば true、なければ falseを返却する。
   * @param array $skuList SKUのリスト
   * @return boolean 受注履歴があるSKUが存在すれば true、全てなければ false
   */
  private function existSalesHistory($skuList) {
    /** @var TbSalesDetailAnalyzeRepository $salesRepo */
    $salesRepo = $this->getDoctrine()->getRepository("MiscBundle:TbSalesDetailAnalyze");
    $salesInfo = $salesRepo->findSalesQuantity($skuList);
    foreach($salesInfo as $k => $v) {
      if ($v > 0) {
        return true;
      }
    }
    return false;
  }
  
  /**
   * パラメータで引き渡された代表商品のSKUが、何らかのセット商品の構成品にあたるかを調べ、該当するものがあればセット商品のSKU名を返却する。
   * （現在はセット商品設定になっておらず、使われていないものでも、tb_set_product_detailにあれば念のため該当ありとする）
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array パラメータで引き渡された代表商品のSKUが、何らかのセット商品の構成品であれば、親のセット商品のSKUコードの配列
   */
  private function findUsedInSetProduct($daihyoSyohinCode) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
      SELECT distinct d.set_ne_syohin_syohin_code
      FROM tb_set_product_detail d
      JOIN tb_productchoiceitems pci ON d.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
      ORDER BY d.set_ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }
  
  /**
   * 指定された代表商品の、削除されていない商品売上担当者が存在するかを確認する。
   * （適用期間外であっても存在する扱い。削除されていれば存在しない扱い）
   * @param string $daihyoSyohinCode
   */
  private function existProductSalesAccount($daihyoSyohinCode) {
    $psaRepo = $this->getDoctrine()->getRepository("MiscBundle:TbProductSalesAccount");
    $list = $psaRepo->findBy(['daihyoSyohinCode' => $daihyoSyohinCode]);
    foreach ($list as $productSalesAccount) {
      if ($productSalesAccount->getStatus() == TbProductSalesAccount::STATUS_REGISTRATION) {
        return true;
      }
    }
    return false;
  }
  
  /**
   * 指定された代表商品が、削除除外商品に設定されているか確認する。
   * @param string $daihyoSyohinCode 代表商品コード
   * @return boolean 削除除外商品が存在すれば true、全てなければ false
   */
  private function existDeleteExcludedProduct($daihyoSyohinCode) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
      SELECT syohin_code as daihyo_syohin_code
      FROM tb_delete_excluded_products
      WHERE syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if ($list) {
      return true;
    }
    return false;
  }
  
  /**
   * おとりよせで販売中かどうか。
   * おとりよせの購入が、受注明細差分更新で取り込まれていない可能性があるため、警告のためチェックする。購入が可能な状態であれば trueを返す。
   * 
   * 購入可能かどうかは、以下で判定する。
   * ・おとりよせのregistration_flg <> 0
   * ・deliverycode = 即納, 一部即納, 受発注のみ
   * @param string $daihyoSyohinCode 代表商品コード
   * @return boolean おとりよせで販売可能であれば true
   */
  private function isOnSaleOtoriyose($daihyoSyohinCode) {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = <<<EOD
      SELECT cal.daihyo_syohin_code
      FROM tb_mainproducts_cal cal
      JOIN tb_yahoo_otoriyose_information i ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.daihyo_syohin_code = :daihyoSyohinCode
        AND i.registration_flg <> 0
        AND cal.deliverycode IN (:deliveryCodeReady , :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    if ($list) {
      return true;
    }
    return false;
  }
  
  /**
   * 一部テーブルのスナップショットを取得したうえで、商品を削除する
   * @param string $daihyoSyohinCode 代表商品コード
   */
  public function deleteProduct($daihyoSyohinCode, $userId) {
    $result = [
      'snapshot' => ''
      , 'delete' => ''
    ];
    
    // まずスナップショットを取得する
    /** @var ProductSnapshotService $snapshotService */
    $snapshotService = $this->getContainer()->get('misc.service.product_snapshot');
    $targetTable = [
      'tb_mainproducts'
      , 'tb_mainproducts_cal'
      , 'tb_vendoraddress'
      , 'tb_productchoiceitems'
      , 'tb_set_product_detail'
      , 'product_images'
      , 'product_images_variation'
      , 'product_images_amazon'
    ];
    $result['snapshot'] = $snapshotService->saveSnapshot($daihyoSyohinCode, $targetTable, $userId, 'deleteProduct');
    $result['delete'] = $this->doDeleteProduct($daihyoSyohinCode);
    return $result;
  }
  
  /**
   * 削除処理を実行する
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array 削除したテーブルと行数を格納した配列
   */
  private function doDeleteProduct($daihyoSyohinCode) {
    
    // 削除対象テーブルを削除順に定義　MSAccessから転記 +α
    $targetTables = [
      // tb_product_location 	tb_mainproductsを削除すればCUSCADEで消える。MSAccessはこれを利用しているが、そもそも在庫がある間はエラーとする
      // , tb_set_product_sku MSAccessでは削除していたが、テーブル構造が難しい＆NextEngine側で分解されるようになり、既に使っていないはずなので省略
      'tb_set_product_detail' // MSAccessでは子の場合に削除していたように見えるが、自分が親の場合のみ削除。子を消すとセットから商品が欠けてしまう
      // , tb_set_product_picking_list MSAccessでは削除していたが、テーブル構造が難しい＆NextEngine側で分解されるようになり、既に使っていないはずなので省略
      // , tb_set_product_create_detail NextEngine側で分解されるようになり、既に使っていないはずなので省略
      , 'tb_mainproducts'
      , 'tb_mainproducts_cal'
      , 'tb_vendoraddress'
      , 'tb_mainproducts_importability'
      , 'tb_mainproducts_english'
      // , 'tb_delete_excluded_products' 削除除外テーブルなので、値があればエラー
      , 'tb_productchoiceitems_color'
      , 'tb_productchoiceitems_shippingdivision_pending'
      // , 'tb_productchoiceitems_shipping_group_log' // 操作ログ系なので残す　害はない
      , 'tb_productchoiceitems'
      , 'tb_qten_information'
      , 'tb_yahoo_information'
      , 'tb_yahoo_kawa_information'
      , 'tb_yahoo_otoriyose_information'
      , 'tb_amazoninfomation'
      , 'tb_biddersinfomation'
      , 'tb_croozmallinformation'
      , 'tb_makeshop_infomation' // 既に出品していないのでほぼデータはない
      , 'tb_gmo_infomation' // 既に出品していないのでほぼデータはない
      , 'tb_rakuteninformation'
      , 'tb_rakuten_motto_information'
      , 'tb_rakuten_dolcissimo_information'
      , 'tb_rakuten_laforest_information'
      , 'tb_rakuten_gekipla_information'
      , 'tb_ss_information' // 既に出品していないのでほぼデータはない
      , 'tb_ppm_information'
      , 'tb_shoplist_information'
      , 'tb_amazon_com_information' // 既に出品していないのでほぼデータはない
      , 'tb_cube_information'
      , 'tb_product_season'
      , 'product_images'
      , 'product_images_variation'
      , 'product_images_amazon'
      // , 'tb_product_sales_account' // 操作ログを兼ねているので残す
    ];
    
    $logger = $this->getLogger();
    $logger->debug("商品を削除。代表商品[$daihyoSyohinCode] 対象テーブル：" . print_r($targetTables, true));
    
    // トランザクションを使用する
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    // 削除結果
    $deleteResult = [];
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getStringUtil();
    try {
      $dbMain->beginTransaction();
      foreach ($targetTables as $table) {
        // テーブル名をキャメルケースに変換し、メソッド名を作成してリフレクション
        $deleteMethodName = 'delete' . ucfirst($stringUtil->convertToCamelCase($table));
        $rowCount = $this->$deleteMethodName($daihyoSyohinCode, $dbMain);
        $deleteResult[$table] = $rowCount;
      }
      $dbMain->commit(); 
    } catch (\Exception $e) {
      $dbMain->rollBack();
      throw $e;
    }
    return $deleteResult;
  }
  
  private function deleteTbSetProductDetail($daihyoSyohinCode, $dbMain) {
    $sql = <<<EOD
      DELETE d.*
      FROM tb_productchoiceitems pci
      JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
      WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount();
  }
  
  private function deleteTbMainproducts($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_mainproducts', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbMainproductsCal($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_mainproducts_cal', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbVendoraddress($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_vendoraddress', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbMainproductsImportability($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_mainproducts_importability', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbMainproductsEnglish($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_mainproducts_english ', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbProductchoiceitems($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_productchoiceitems', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbProductchoiceitemsColor($daihyoSyohinCode, $dbMain) {
    $sql = <<<EOD
      DELETE c.*
      FROM tb_productchoiceitems pci
      JOIN tb_productchoiceitems_color c ON pci.ne_syohin_syohin_code = c.ne_syohin_syohin_code
      WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount();
  }
  private function deleteTbProductchoiceitemsShippingdivisionPending($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_productchoiceitems_shippingdivision_pending', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbQtenInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_qten_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbYahooInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_yahoo_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbYahooKawaInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_yahoo_kawa_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbYahooOtoriyoseInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_yahoo_otoriyose_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbAmazoninfomation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_amazoninfomation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbBiddersinfomation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_biddersinfomation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbCroozmallinformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_croozmallinformation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbMakeshopInfomation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_makeshop_infomation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbGmoInfomation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_gmo_infomation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbRakuteninformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_rakuteninformation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbRakutenMottoInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_rakuten_motto_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbRakutenDolcissimoInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_rakuten_dolcissimo_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbRakutenLaforestInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_rakuten_laforest_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbRakutenGekiplaInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_rakuten_gekipla_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbSsInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_ss_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbPpmInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_ppm_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbShoplistInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_shoplist_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbAmazonComInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_amazon_com_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbCubeInformation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_cube_information', $daihyoSyohinCode, $dbMain);
  }
  private function deleteTbProductSeason($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('tb_product_season', $daihyoSyohinCode, $dbMain);
  }
  private function deleteProductImages($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('product_images', $daihyoSyohinCode, $dbMain);
  }
  private function deleteProductImagesVariation($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('product_images_variation', $daihyoSyohinCode, $dbMain);
  }
  private function deleteProductImagesAmazon($daihyoSyohinCode, $dbMain) {
    return $this->deleteByDaihyoSyohinCode('product_images_amazon', $daihyoSyohinCode, $dbMain);
  }
  
  /**
   *　指定されたテーブルの、daihyo_syohin_codeが一致するレコードを全て削除する。
   * @param string $tableName テーブル名
   * @param string $daihyoSyohinCode 代表商品コード
   * @return int 削除行数
   */
  private function deleteByDaihyoSyohinCode($tableName, $daihyoSyohinCode, $dbMain) {
    $sql = "DELETE FROM {$tableName} WHERE daihyo_syohin_code = :daihyoSyohinCode";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount();
  }
  
}