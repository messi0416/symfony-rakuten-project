<?php
namespace MiscBundle\Service;

use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Service\ServiceBaseTrait;

/**
 * セット商品受注情報案分Service
 * 
 * セット商品の売上等の案分に関する処理を管理する。
 * NextEngineMallProcessが肥大化しているため、案分処理だけ切り出し。
 */
class SetProductSalesDistributionService
{
  use ServiceBaseTrait;
  
  /**
   * 指定された伝票番号の、セット商品の売上の案分情報登録を実行する。
   * このメソッド内で、全てのprivateを順に呼び出し、処理を実行する。
   * DELETE-INSERT による更新のため、登録済みの伝票を再呼び出ししても問題ない。
   * 
   * @param $fromNumber 処理対象の伝票番号From（この値を含む）（NotNull）
   * @param $toNumber 処理対象の伝票番号To（この値を含む）。未指定の場合は登録済みの最大まで。（Nullable)
   * @param $useTmp 受注明細差分更新からの実行用。trueの場合はtb_sales_detail_tmpに存在する伝票番号のみ処理対象とする。
   */
  public function recalcurateSetDistributeInfo(int $fromNumber, int $toNumber = null,  bool $useTmp = null) {
    $logger = $this->getLogger();
    if (is_null($fromNumber) || $fromNumber <= 0) {
      $logger->debug("FromNumberの指定がありません。FromNumberは1以上の伝票番号を指定してください");
      throw new BusinessException("FromNumberの指定がありません。FromNumberは1以上の伝票番号を指定してください");
    }
    
    // 過去データ削除
    $this->deleteSetDistributeInfo($fromNumber, $toNumber, $useTmp);
    
    // 案分テーブル登録
    $this->insertSetDistributeInfo($fromNumber, $toNumber, $useTmp);
    
    // 価格情報の取得
    $setPriceInfo = $this->findSetProductPriceInfoByDate($fromNumber, $toNumber, $useTmp);
    
    // 1日・1セット商品単位でループ。案分のためのレート情報追加
    foreach ($setPriceInfo as &$skuList) {
      $this->addDistributeRate($skuList);
    }
    unset($skuList); // 参照渡し後は解放
    
    // セット商品の受注情報を取得
    $salesDetails = $this->findSalesDetailWithSetProduct($fromNumber, $toNumber, $useTmp);

    // 案分情報更新
    $this->updateDistributionInfo($setPriceInfo, $salesDetails);
  }
  
  /**
   * 登録済みの伝票番号のセット商品の売上案分情報を削除する。
   * @param $fromNumber 処理対象の伝票番号From（この値を含む）（NotNull）
   * @param $toNumber 処理対象の伝票番号To（この値を含む）。未指定の場合は登録済みの最大まで。（Nullable)
   * @param $useTmp 受注明細差分更新からの実行用。trueの場合はtb_sales_detail_tmpに存在する伝票番号のみ処理対象とする。
   */
  private function deleteSetDistributeInfo(int $fromNumber, int $toNumber = null, bool $useTmp = null) {
    $db = $this->getDb('main');
    
    $andFrom = '';
    $andWhere = '';
    $params = [];
    if ($fromNumber) {
      $andWhere = 'AND voucher_number >= :fromNumber';
      $params['fromNumber'] = $fromNumber;
    }
    if ($toNumber) {
      $andWhere .= ' AND voucher_number <= :toNumber';
      $params['toNumber'] = $toNumber;
    }
    if ($useTmp) {
      $andFrom = 'JOIN (SELECT distinct 伝票番号 FROM tb_sales_detail_tmp) tmp ON i.voucher_number = tmp.伝票番号';
    }
    
    // まず既存登録の情報を削除
    $sql = <<<EOD
      DELETE i FROM tb_sales_detail_set_distribute_info i
      {$andFrom}
      WHERE 1=1
      {$andWhere}
EOD;
    $stmt = $db->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_INT);
    }
    $stmt->execute();
  }
  
 
  /**
   * 指定された範囲の伝票番号の、セット商品受注の補足情報を登録する。
   * $toNumber は未指定の場合は最大までを処理する。
   * この処理はDELETE-INSERTである。明細行の番号がずれる場合があるため、指定範囲の伝票が登録済みの場合、いったん削除して再登録する。
   *
   * @param $fromNumber 処理対象の伝票番号From（この値を含む）（NotNull）
   * @param $toNumber 処理対象の伝票番号To（この値を含む）。未指定の場合は登録済みの最大まで。（Nullable)
   * @param $useTmp 受注明細差分更新からの実行用。trueの場合はtb_sales_detail_tmpに存在する伝票番号のみ処理対象とする。
   */
  private function insertSetDistributeInfo(int $fromNumber, int $toNumber = null, bool $useTmp = null) {
    $db = $this->getDb('main');
    
    $andFrom = '';
    $andWhere = '';
    $params = [];
    if ($fromNumber) {
      $andWhere = 'AND a.伝票番号 >= :fromNumber';
      $params['fromNumber'] = $fromNumber;
    }
    if ($toNumber) {
      $andWhere .= ' AND a.伝票番号 <= :toNumber';
      $params['toNumber'] = $toNumber;
    }
    if ($useTmp) {
      $andFrom = 'JOIN (SELECT distinct 伝票番号 FROM tb_sales_detail_tmp) tmp ON a.伝票番号 = tmp.伝票番号';
    }
    
    // 商品名冒頭に、セット商品のSKUと思われるものがついている明細を取得し、まず登録
    $sql = <<<EOD
      INSERT INTO tb_sales_detail_set_distribute_info (
        voucher_number
        , line_number
        , original_ne_syohin_syohin_code
        , original_quantity
      )
      SELECT
        a.伝票番号
        , a.明細行
        , SUBSTRING_INDEX(a.商品名（伝票）, ' ', 1)
        , CAST(a.受注数 / d.num AS UNSIGNED)  as セット受注数
      FROM tb_sales_detail_analyze a
      {$andFrom}
      JOIN tb_set_product_detail d ON SUBSTRING_INDEX(a.商品名（伝票）, ' ', 1)  = d.set_ne_syohin_syohin_code AND a.商品コード（伝票） = d.ne_syohin_syohin_code
      WHERE 1=1
      {$andWhere}
EOD;
      
    $stmt = $db->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_INT);
    }
    $stmt->execute();
  }
  
  /**
   * 指定された期間内の受注伝票をもとに、「受注日と代表商品コード」の組み合わせごとに、それぞれの構成品の価格情報を取得する。
   * 返り値は以下の通りの書式となる。
   * array =
   *   ['YYYY-MM-DD-${set_ne_syohin_syohin_code}' =>
   *     ['ne_sohin_syohin_code' = ['num' => ${num}, 'unit_price' => ${unit_price}],
   *      'ne_sohin_syohin_code' = ['num' => ${num}, 'unit_price' => ${unit_price}],
   *      'ne_sohin_syohin_code' = ['num' => ${num}, 'unit_price' => ${unit_price}]]
   *      ...
   *   ],
   *   ['YYYY-MM-DD-${set_ne_syohin_syohin_code}' =>
   *     ['ne_sohin_syohin_code' = ['num' => ${num}, 'unit_price' => ${unit_price}],
   *      'ne_sohin_syohin_code' = ['num' => ${num}, 'unit_price' => ${unit_price}]]
   *      ...
   *   ],
   *
   * @param $fromNumber 処理対象の伝票番号From（この値を含む）（NotNull）
   * @param $toNumber 処理対象の伝票番号To（この値を含む）。未指定の場合は登録済みの最大まで。（Nullable)
   * @param $useTmp 受注明細差分更新からの実行用。trueの場合はtb_sales_detail_tmpに存在する伝票番号のみ処理対象とする。
   */
  private function findSetProductPriceInfoByDate(int $fromNumber, int $toNumber = null, bool $useTmp = null) {
    $db = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logger = $this->getLogger();
    $logDbName = $dbLog->getDatabase();
    
    $andFrom = '';
    $andWhere = '';
    $params = [];
    if ($fromNumber) {
      $andWhere = 'AND a.伝票番号 >= :fromNumber';
      $params['fromNumber'] = $fromNumber;
    }
    if ($toNumber) {
      $andWhere .= ' AND a.伝票番号 <= :toNumber';
      $params['toNumber'] = $toNumber;
    }
    if ($useTmp) {
      $andFrom = 'JOIN (SELECT distinct 伝票番号 FROM tb_sales_detail_tmp) tmp ON a.伝票番号 = tmp.伝票番号';
    }
    
    $sql = <<<EOD
      SELECT
        base.受注日 as sales_date,
        base.original_ne_syohin_syohin_code,
        detail.ne_syohin_syohin_code,
        detail.num,
        CASE
          WHEN l.baika_tnk IS NOT NULL THEN l.baika_tnk
          ELSE cal.baika_tnk
        END as unit_price
      FROM (SELECT distinct a.受注日, d.original_ne_syohin_syohin_code
        FROM tb_sales_detail_set_distribute_info d
        JOIN tb_sales_detail_analyze a ON d.voucher_number = a.伝票番号 AND d.line_number = a.明細行
        {$andFrom}
        WHERE 1=1
        {$andWhere}
      ) base
      JOIN tb_set_product_detail detail ON base.original_ne_syohin_syohin_code = detail.set_ne_syohin_syohin_code
      JOIN tb_productchoiceitems pci ON detail.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT JOIN {$logDbName}.tb_product_price_log l ON base.受注日 = l.log_date AND cal.daihyo_syohin_code = l.daihyo_syohin_code
      ORDER BY base.受注日, base.original_ne_syohin_syohin_code
EOD;
      $stmt = $db->prepare($sql);
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_INT);
      }
      $stmt->execute();
      $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      $result = []; // 案分結果のリストを格納する　形式はドキュメンテーションコメント通り
      $processing = []; // 処理中の保留リスト
      $previous = null;
      foreach ($list as $data) {
        // 最初の1件
        if (is_null($previous)) {
          $previous = $data;
          $processing[$data['ne_syohin_syohin_code']] = $data;
          // 1セット分の区切り
        } else if ($previous['sales_date'] != $data['sales_date'] || $previous['original_ne_syohin_syohin_code'] != $data['original_ne_syohin_syohin_code']) {
          $result[$previous['sales_date'] . '-' . $previous['original_ne_syohin_syohin_code']] = $processing;
          $previous = $data;
          $processing = [];
          $processing[$data['ne_syohin_syohin_code']] = $data;
          // 1セット分途中
        } else {
          $processing[$data['ne_syohin_syohin_code']] = $data;
        }
      }
      // 最後のセットを格納
      $result[$previous['sales_date'] . '-' . $previous['original_ne_syohin_syohin_code']] = $processing;
      return $result;
  }
  
  /**
   * セット商品のSKU情報（単価付き）を受け取り、案分レートを追加する。引数の配列を直接更新する。
   * @param array $setProductSkuList num, unit_price の2項目を持つ連想配列の、セット商品の構成品分の配列。
   *                                 total_price, subtotal_rate, unit_price_rate を追加する
   */
  private function addDistributeRate(array &$setProductSkuList) {
    $total = 0;
    foreach ($setProductSkuList as $data) {
      $total += $data['num'] * $data['unit_price'];
    }
    foreach ($setProductSkuList as &$data) {
      $data['total_price'] = $total;
      $data['subtotal_rate'] = $data['num'] * $data['unit_price'] / $total;
      $data['unit_price_rate'] = $data['unit_price'] / $total;
    }
  }
  
  /**
   * セット商品の受注情報を取得し、「伝票番号と元商品コード」ごとにグループ化して返却する。
   * 返却される配列は、
   * $result[${伝票番号}-${セット商品コード}] = 
   *   ['sales_date' => ${受注日}, 'voucher_number' => ${伝票番号}, 'line_number' => ${明細行}, 
   *   　'subtotal' => ${小計}※案分前, 'unit_price' => ${元単価}※案分前, 
   *   　'original_ne_syohin_syohin_code' => "${セット商品コード}", 'ne_syohin_syohin_code' => ${商品コード（伝票）}※分割後  ]
   *   ...
   * 
   * @param $fromNumber 処理対象の伝票番号From（この値を含む）（NotNull）
   * @param $toNumber 処理対象の伝票番号To（この値を含む）。未指定の場合は登録済みの最大まで。（Nullable)
   * @param $useTmp 受注明細差分更新からの実行用。trueの場合はtb_sales_detail_tmpに存在する伝票番号のみ処理対象とする。
   */
  private function findSalesDetailWithSetProduct(int $fromNumber, int $toNumber = null, bool $useTmp = null) {
    $db = $this->getDb('main');
    $andFrom = '';
    $andWhere = '';
    $params = [];
    if ($fromNumber) {
      $andWhere = 'AND a.伝票番号 >= :fromNumber';
      $params['fromNumber'] = $fromNumber;
    }
    if ($toNumber) {
      $andWhere .= ' AND a.伝票番号 <= :toNumber';
      $params['toNumber'] = $toNumber;
    }
    if ($useTmp) {
      $andFrom = 'JOIN (SELECT distinct 伝票番号 FROM tb_sales_detail_tmp) tmp ON a.伝票番号 = tmp.伝票番号';
    }
    
    $sql = <<<EOD
      SELECT
        a.受注日 as sales_date,
        a.伝票番号 as voucher_number,
        a.明細行 as line_number,
        a.受注数 as amount,
        a.小計 as subtotal,
        a.売単価 as unit_price,
        d.original_ne_syohin_syohin_code,
        d.original_quantity,
        a.商品コード（伝票） as ne_syohin_syohin_code
      FROM tb_sales_detail_analyze a
      {$andFrom}
      JOIN tb_sales_detail_set_distribute_info d ON a.伝票番号 = d.voucher_number AND a.明細行 = d.line_number
      WHERE 1=1
      {$andWhere}
      ORDER BY a.受注日, a.伝票番号, a.明細行
EOD;
      $stmt = $db->prepare($sql);
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_INT);
      }
      $stmt->execute();
      $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      
      $result = []; // グループ化したリストを格納する。キーは[伝票番号-セット商品コード]
      $processing = []; // 処理中の保留リスト
      $previous = null;
      foreach ($list as $data) {
        // 最初の1件
        if (is_null($previous)) {
          $previous = $data;
          $processing[] = $data;
          // 1セット分の区切り
        } else if ($previous['voucher_number'] != $data['voucher_number'] || $previous['original_ne_syohin_syohin_code'] != $data['original_ne_syohin_syohin_code']) {
          $result[$previous['voucher_number'] . '-' . $previous['original_ne_syohin_syohin_code']] = $processing;
          $previous = $data;
          $processing = [];
          $processing[] = $data;
          // 1セット分途中
        } else {
          $processing[] = $data;
        }
      }
      // 最後のセットを格納
      $result[$previous['voucher_number'] . '-' . $previous['original_ne_syohin_syohin_code']] = $processing;
      return $result;
  }
  
  /**
   * 受注明細セット商品分割情報に、案分した小計、売単価を設定する。
   * 
   * 伝票番号と元SKUコードごとにグループ化して修正後の小計、売単価を取得（切り捨て）。
   * その後、端数は最初の1件に付与する。
   * キャンセルされた場合、受注数が0になる場合がある。この場合小計、売単価は0にならないが、もとの受注数がわからず、案分後小計、案分後売単価は出せないので0とする。
   * （セット販売については、明細単位でキャンセルはないと考えているが、確定ではない。念のため明細ごとに受注数チェックを行い、0でないものがあればそれは小計・売単価を出す）
   * 
   * @param array $rateInfo 日付とセット商品ごとの、構成品の案分比率の情報
   * @param array $salesDetailInfo 処理対象の受注情報の情報
   */
  private function updateDistributionInfo(array $rateInfoList, array $salesDetailInfoList) {
    $db = $this->getDb('main');
    $logger = $this->getLogger();
    $updateList = []; // 最終的なupdate文用のリスト
    
    foreach ($salesDetailInfoList as $setInfoList) { // 外側のループは1セット単位
      $beforeTotalSubtotal = 0; // 端数調整前の合計
      $beforeTotalUnitPrice = 0; // 端数調整前の売単価
      
      $salesSubtotal = 0; // 販売時の小計
      $firstFlg = true;
      
      // 後の端数調整で使用する為、処理済みのリストを確保
      $resultList = [];
      
      foreach ($setInfoList as $detail) { // セット内の明細情報
        if ($firstFlg) {
          $salesSubtotal = $detail['subtotal'];
          $firstFlg = false;
        }
        $updateData = ['voucher_number' => $detail['voucher_number'], 'line_number' => $detail['line_number'], 'amount' => $detail['amount']];
        $rateInfo = $rateInfoList[$detail['sales_date'] . '-' . $detail['original_ne_syohin_syohin_code']][$detail['ne_syohin_syohin_code']];
        
        $distributedUnitPrice = 0;
        if ($detail['original_quantity'] > 0) {
          $distributedUnitPrice = floor($salesSubtotal * $rateInfo['unit_price_rate'] / $detail['original_quantity']); // 小計をセット受注数で割り、1セットあたりの小計にしてから、案分レートを適用
        }
        $distributedSubtotal = $distributedUnitPrice * $detail['amount'];
        $updateData['distribution_subtotal'] = $distributedSubtotal;
        $updateData['distribution_unit_price'] = $distributedUnitPrice;
        $beforeTotalSubtotal += $distributedSubtotal;
        $beforeTotalUnitPrice += $distributedUnitPrice * $detail['amount'];
        $resultList[] = $updateData;
      }
      
      // 差分調整
      if ($resultList[0]['amount'] > 0 && ($beforeTotalSubtotal != $salesSubtotal || $beforeTotalUnitPrice != $salesSubtotal)) {
        $resultList[0]['distribution_subtotal'] += ($salesSubtotal - $beforeTotalSubtotal);
        $resultList[0]['distribution_unit_price'] += floor(($salesSubtotal - $beforeTotalUnitPrice) / $resultList[0]['amount']); // 1円未満切り捨て
      }
      $updateList = array_merge($updateList, $resultList);
    }
      
    $insertBuilder = null;
    $count = 0;
    foreach ($updateList as $update) {
      if (!$insertBuilder) {
        $insertBuilder = new MultiInsertUtil("tb_sales_detail_set_distribute_info", [
          'fields' => [
            'voucher_number'              => \PDO::PARAM_INT
            , 'line_number'               => \PDO::PARAM_INT
            , 'distribution_unit_price'   => \PDO::PARAM_INT
            , 'distribution_subtotal'     => \PDO::PARAM_INT
          ]
          , 'postfix' => "ON DUPLICATE KEY UPDATE distribution_unit_price = VALUES(distribution_unit_price) "
          . " , distribution_subtotal  = VALUES(distribution_subtotal) "
        ]);
      }
      $insertBuilder->bindRow($update);
       
      // 分割 INSERT（を利用したUPDATE） (バルクアップデート：1000件ずつ)
      if (++$count >= 1000) {
        if (count($insertBuilder->binds())) {
          $updateStmt = $db->prepare($insertBuilder->toQuery());
          $insertBuilder->bindValues($updateStmt);
          $updateStmt->execute();
        }
        unset($insertBuilder);
        $count = 0;
      }
    }
  
    // 残り
    if ($count && isset($insertBuilder) && count($insertBuilder->binds())) {
      $updateStmt = $db->prepare($insertBuilder->toQuery());
      $insertBuilder->bindValues($updateStmt);
      $updateStmt->execute();
    }
  }
}