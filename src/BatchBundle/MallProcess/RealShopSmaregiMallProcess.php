<?php

namespace BatchBundle\MallProcess;

use MiscBundle\Util\StringUtil;
use Symfony\Component\BrowserKit\Response;

/**
 * Class RealShopSmaregiMallProcess
 */
class RealShopSmaregiMallProcess extends BaseMallProcess
{
  /**
   * 商品一括登録
   * @param array $neSyohinSyohinCodeList 商品コード配列
   */
  public function registerProducts($neSyohinSyohinCodeList)
  {
    $logger = $this->getLogger();

    $dbMain = $this->getDb('main');

    $codeList = [];
    foreach($neSyohinSyohinCodeList as $code) {
      $codeList[] = $dbMain->quote($code, \PDO::PARAM_STR);
    }
    if (!$codeList) {
      return;
    }
    $codeListStr = implode(', ', $codeList);

    $sql = <<<EOD
      SELECT
          code.id AS product_id
        , pci.ne_syohin_syohin_code
        , pci.daihyo_syohin_code
        , LEFT(m.daihyo_syohin_name, 85) AS name
        , i.baika_tanka AS price
        , CASE
            WHEN m.`カラー軸` = 'col' THEN colname
            ELSE rowname
          END AS color
        , CASE
            WHEN m.`カラー軸` = 'col' THEN rowname
            ELSE colname
          END AS size
        , v.cost_tanka AS cost
      FROM tb_productchoiceitems pci
      INNER JOIN tb_product_code code ON pci.ne_syohin_syohin_code = code.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_real_shop_information i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN v_product_price_base v ON pci.daihyo_syohin_code = v.daihyo_syohin_code
      WHERE pci.ne_syohin_syohin_code IN ( {$codeListStr} )
      ORDER BY pci.daihyo_syohin_code
             , pci.並び順No
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $webAccessUtil = $this->getWebAccessUtil();
    $url = $webAccessUtil->getSmaregiApiUrl();
    $client = $webAccessUtil->getSmaregiApiClient();

    $termMax = 500;
    $count = 0;
    $totalCount = $stmt->rowCount();
    $finished = false;

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    do {
      $data = [];

      // 商品マスタ
      $dataProducts = [
          'table_name' => 'Product'
        , 'rows' => []
      ];
      $dataShops = [
          'table_name' => 'ProductStore'
        , 'rows' => []
      ];

      $row = null;
      while (count($dataProducts['rows']) < $termMax && ($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
        // 商品マスタ
        $dataProducts['rows'][] = [
            'productId'         => $row['product_id']
          , 'categoryId'        => '1' /* TODO カテゴリの手当 */
          , 'productCode'       => $stringUtil->convertNumToJan13($row['product_id'])
          , 'productName'       => $row['name']
          , 'taxDivision'       => 1
          , 'price'             => $row['price']
          , 'size'              => $row['size']
          , 'color'             => $row['color']
          , 'groupCode'         => $row['daihyo_syohin_code']
          , 'supplierProductNo' => $row['ne_syohin_syohin_code']
          , 'cost'              => $row['cost']
        ];

        // 取扱店舗
        $dataShops['rows'][] = [
            'productId'       => $row['product_id']
          , 'storeId'         => 1 /* 店舗1: プラスナオ */
          , 'assignDivision'  => 0 /* 販売する */
        ];

        $count++;
      }
      if (!$row) {
        $finished = true;
      }

      $data[] = $dataProducts;
      $data[] = $dataShops;

      // $logger->info(print_r($dataProducts['rows'], true));
      // $logger->info(print_r($dataShops['rows'], true));
      // $logger->info(print_r($data, true));

      $params = [
        'proc_info' => [
          'proc_division' => 'U'
        ]
        , 'data' => $data
      ];

      $sendData = [
          'proc_name' => 'product_upd'
        , 'params' => json_encode($params)
      ];

      $client->request('POST', $url, $sendData);
      /** @var Response $response */
      $response  = $client->getResponse();

      // $logger->info(print_r($request->getParameters(), true));
      $logger->info(print_r($response->getHeaders(), true));
      $logger->info(print_r($response->getContent(), true));
      $logger->info(print_r(json_decode($response->getContent()), true));

    } while(($count < $totalCount) && !$finished);

  }

  /**
   * 在庫一括入庫
   * @param array $stockList 商品コード => 入庫数 の配列
   */
  public function storeStocks($stockList)
  {
    $logger = $this->getLogger();

    $dbMain = $this->getDb('main');

    $codeList = [];
    foreach($stockList as $code => $num) {
      $codeList[] = $dbMain->quote($code, \PDO::PARAM_STR);
    }
    if (!$codeList) {
      return;
    }
    $codeListStr = implode(', ', $codeList);

    $sql = <<<EOD
      SELECT
          code.id AS product_id
        , pci.ne_syohin_syohin_code
      FROM tb_productchoiceitems pci
      INNER JOIN tb_product_code code ON pci.ne_syohin_syohin_code = code.ne_syohin_syohin_code
      WHERE pci.ne_syohin_syohin_code IN ( {$codeListStr} )
      ORDER BY pci.daihyo_syohin_code
             , pci.並び順No
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $webAccessUtil = $this->getWebAccessUtil();
    $url = $webAccessUtil->getSmaregiApiUrl();
    $client = $webAccessUtil->getSmaregiApiClient();

    $termMax = 500;
    $count = 0;
    $totalCount = $stmt->rowCount();
    $finished = false;

    do {
      $data = [];

      $dataStocks = [
          'table_name' => 'Stock'
        , 'rows' => []
      ];

      $row = null;
      while (count($dataStocks['rows']) < $termMax && ($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {

        $syohinCode = $row['ne_syohin_syohin_code'];
        $moveNum = isset($stockList[$syohinCode]) ? $stockList[$syohinCode] : 0;

        // 商品マスタ
        $dataStocks['rows'][] = [
            'storeId' => 1 // 店舗1: プラスナオ
          , 'productId' => $row['product_id']
          , 'stockAmount' => $moveNum
          , 'stockDivision' => 15 // 在庫区分:WebAPI連携
        ];

        $count++;
      }
      if (!$row) {
        $finished = true;
      }

      $data[] = $dataStocks;

      // $logger->info(print_r($dataProducts['rows'], true));
      // $logger->info(print_r($dataShops['rows'], true));
      // $logger->info(print_r($data, true));

      $params = [
        'proc_info' => [
            'proc_division' => 'U'
          , 'proc_detail_division' => '2' // 相対値
        ]
        , 'data' => $data
      ];

      $sendData = [
          'proc_name' => 'stock_upd'
        , 'params' => json_encode($params)
      ];

      $client->request('POST', $url, $sendData); // FOR DEBUG

      /** @var Response $response */
      $response  = $client->getResponse();

      // $logger->info(print_r($request->getParameters(), true));
      $logger->info(print_r($response->getHeaders(), true));
      $logger->info(print_r($response->getContent(), true));
      $logger->info(print_r(json_decode($response->getContent()), true));

    } while(($count < $totalCount) && !$finished);

  }

}
