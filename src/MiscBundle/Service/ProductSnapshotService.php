<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\TbMainproductsRepository;
use MiscBundle\Util\StringUtil;


/**
 * 商品スナップショットサービス
 */
class ProductSnapshotService
{
  use ServiceBaseTrait;
  
  /**
   * 指定された代表商品のの現在の商品情報テーブルデータを、スナップショットとして取得し、
   * plusnao_log_db.tb_product_snapshot に保存する。
   * @param string $daihyoSyohinCode 代表商品コード
   * @param array $targetTables 対象テーブル名（物理名）の配列。
   * @param int $userId ユーザID
   * @param string $action 処理名。呼び出し処理を一意に特定する文字列。画面からの呼び出しならばURI、Commandならクラス名など
   */
  public function saveSnapshot($daihyoSyohinCode, $targetTables, $userId, $action) {
    $logger = $this->getLogger();
    $logger->debug("商品情報のスナップショット取得。代表商品[$daihyoSyohinCode] 対象テーブル：" . print_r($targetTables, true));
    
    // 空のサマリを構築
    $summary = [];
    foreach ($targetTables as $table) {
      $summary[$table] = 0;
    }
    
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    
    // 各テーブルを処理。データのないテーブルは無視
    $body = [];
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getStringUtil();
    foreach ($targetTables as $table) {
      // テーブル名をキャメルケースに変換し、メソッド名を作成してリフレクション
      $findMethodName = 'find' . ucfirst($stringUtil->convertToCamelCase($table));
      $data = $this->$findMethodName($daihyoSyohinCode, $dbMain);
       
      // $dataが空ならスキップ
      if (!$data) {
        continue;
      }
      
      // サマリ取得 tb_mainproductsとtb_mainproducts_calはここまで来れば1。それ以外はcount()
      if ($table == 'tb_mainproducts' || $table == 'tb_mainproducts_cal') {
        $summary[$table] = 1;
      } else {
        $summary[$table] = count($data);
      }
      
      // body確保
      $body[$table] = $data;
    }
    $summaryJson = json_encode($summary);
    $bodyJson = json_encode($body);
    $this->insertSnapshot($daihyoSyohinCode, $action, $summaryJson, $bodyJson, $userId, $dbMain);
    return $summary;
  }
  
  /**
   * tb_mainproducts を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データ（1レコードなので連想配列そのまま）
   */
  private function findTbMainproducts($daihyoSyohinCode, $dbMain) {
     $list = $this->findResult('tb_mainproducts', $daihyoSyohinCode, $dbMain);
     return $list ? $list[0] : null;
  }
  
  /**
   * tb_mainproducts_cal を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データ（1レコードなので連想配列そのまま）
   */
  private function findTbMainproductsCal($daihyoSyohinCode, $dbMain) {
    $list = $this->findResult('tb_mainproducts_cal', $daihyoSyohinCode, $dbMain);
    return $list ? $list[0] : null;
  }
  
  /**
   * tb_vendoraddress を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データのリスト
   */
  private function findTbVendoraddress($daihyoSyohinCode, $dbMain) {
    return $this->findResult('tb_vendoraddress', $daihyoSyohinCode, $dbMain);
  }
  
  /**
   * tb_productchoiceitems を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データのリスト
   */
  private function findTbProductchoiceitems($daihyoSyohinCode, $dbMain) {
    return $this->findResult('tb_productchoiceitems', $daihyoSyohinCode, $dbMain);
  }
  
  /**
   * tb_set_product_detail を取得（この商品がセット商品の親商品の場合）
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データのリスト
   */
  private function findTbSetProductDetail($daihyoSyohinCode, $dbMain) {
    $sql = <<<EOD
      SELECT s.*
      FROM tb_productchoiceitems pci
      JOIN tb_set_product_detail s ON pci.ne_syohin_syohin_code = s.set_ne_syohin_syohin_code
      WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * product_images を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データのリスト
   */
  private function findProductImages($daihyoSyohinCode, $dbMain) {
    return $this->findResult('product_images', $daihyoSyohinCode, $dbMain);
  }
  
  /**
   * product_images_variation を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データのリスト
   */
  private function findProductImagesVariation($daihyoSyohinCode, $dbMain) {
    return $this->findResult('product_images_variation', $daihyoSyohinCode, $dbMain);
  }
  
  /**
   * product_images_amazon を取得
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データのリスト
   */
  private function findProductImagesAmazon($daihyoSyohinCode, $dbMain) {
    return $this->findResult('product_images_amazon', $daihyoSyohinCode, $dbMain);
  }
  
  /**
   * 指定されたテーブルへ、daihyo_syohin_codeを条件に検索を行い、結果を取得する。
   * @param string $tableName テーブル名
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array FETCH_ASSOCした結果データ
   */
  private function findResult($tableName, $daihyoSyohinCode, $dbMain) {
    $sql = "SELECT * FROM $tableName WHERE daihyo_syohin_code = :daihyoSyohinCode";
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  private function insertSnapshot($daihyoSyohinCode, $action, $summary, $body, $userId, $dbMain) {
    $dbLogName = $this->getDb('log')->getDatabase();
    $sql = <<<EOD
      INSERT INTO {$dbLogName}.tb_product_shapshot(
        daihyo_syohin_code
        , action
        , summary
        , body
        , create_user_id
      ) VALUES (
        :daihyoSyohinCode
        , :action
        , :summary
        , :body
        , :userId
      )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->bindValue(':action', $action, \PDO::PARAM_STR);
    $stmt->bindValue(':summary', $summary, \PDO::PARAM_STR);
    $stmt->bindValue(':body', $body, \PDO::PARAM_STR);
    $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
    $stmt->execute();
  }
  
}