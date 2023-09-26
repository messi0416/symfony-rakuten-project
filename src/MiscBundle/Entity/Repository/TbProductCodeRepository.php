<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbProductCodeRepository
 */
class TbProductCodeRepository extends BaseRepository
{
  /**
   * 商品バーコードリストから、対応するSKUをキー、その個数を値とした連想配列の配列を返す。
   * @param array $productBarCodes
   * @return array
   */
  public function findRfidProductsByBarCode($productBarCodes)
  {
    // 該当のバーコード一覧から、対応するSKUコードをまとめて取得する
    $params = [];
    $i = 1;
    if (count($productBarCodes) === 0) {
      return [];
    } else {
      foreach (array_unique($productBarCodes) as $barcode) {
        $params[":barcode{$i}"] = $barcode;
        $i++;
      }
    }
    $addWhere = implode(', ', array_keys($params));

    // バーコードをキー、SKUコードを値にした連想配列として取得する
    $sql = <<<EOD
      SELECT
        barcode, ne_syohin_syohin_code
      FROM
        tb_product_code
      WHERE
        barcode IN ( {$addWhere} )
      GROUP BY
        ne_syohin_syohin_code
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $barcodeSkuPairs = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

    // SKUコードをキー、件数を値とした形に整形して返却する
    $skus = array_map(function($barcode) use ($barcodeSkuPairs) {
      return $barcodeSkuPairs[$barcode];
    }, $productBarCodes);
    return array_count_values($skus);
  }
}
