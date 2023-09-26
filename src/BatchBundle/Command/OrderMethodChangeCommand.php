<?php
/**
 * User: GB)Ito
 * Date: 2018/09/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryMethod;
use MiscBundle\Entity\TbDeliverySplitRule;
use MiscBundle\Entity\TbPrefecture;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Entity\Repository\TbPrefectureRepository;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;


class OrderMethodChangeCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:order-method-change')
      ->setDescription('Order Method Change')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    $container = $this->getContainer();
    $logger = $this->getLogger();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('発送方法一括変換処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '事前処理', '開始'));
      
      // ルール取得
      $dbMain = $this->getDb('main');
      $sql = <<<EOD
        SELECT 
          tb_delivery_split_rule.*, 
          tb_delivery_method.delivery_name 
        FROM 
          tb_delivery_split_rule 
        INNER JOIN tb_delivery_method
          ON tb_delivery_split_rule.delivery_id = tb_delivery_method.delivery_id 
        WHERE 
          tb_delivery_split_rule.groupid = 1 
        ORDER BY 
          tb_delivery_split_rule.checkorder;
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      
      $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      // 対象都道府県情報を付与
      $rules = array_map(function ($rule) use ($pRepo){
        $rule['prefectures'] = [];
        if ($rule['prefecture_check_column']) {
          $rule['prefectures'] = $pRepo->findCheckColumnAvailabilityPrefectures(
            $rule['prefecture_check_column']
          );
        }
        return $rule;
      }, $rules);

      // 最大発送コード取得
      $sql = <<<EOD
        SELECT 
            tb_delivery_split_rule.delivery_id, 
            tb_delivery_method.delivery_name 
        FROM 
            tb_delivery_split_rule 
        INNER JOIN tb_delivery_method
            ON tb_delivery_split_rule.delivery_id = tb_delivery_method.delivery_id 
        WHERE 
            tb_delivery_split_rule.groupid = 1
        AND tb_delivery_split_rule.maxflg = 1;
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      
      $row = $stmt->fetch(\PDO::FETCH_ASSOC);
      $maxId = $row['delivery_id'];
      $maxName = $row['delivery_name'];
      
      // $logger->info($maxId);
      // $logger->info($maxName);
      
      // 一時テーブル削除
      $stmt = $dbMain->prepare("DELETE FROM tb_progress_order;");
      $stmt->execute();
      
      $stmt = $dbMain->prepare("DELETE FROM tb_voucher_list;");
      $stmt->execute();
      
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '事前処理', '終了'));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '受注データ取り込み', '開始'));
      
      // 受注データ取り込み
      $sql = <<<EOD
        INSERT INTO 
               tb_progress_order ( 
               伝票番号, 明細行, キャンセル区分, 明細行キャンセル, 
               受注日, 店舗コード, 店舗名, 配送方法コード, 発送方法, 受注状態, 
               商品コード（伝票）, 受注数, width, height, depth, weight, 明細数, 全品計測OK, 
               郵便番号頭二桁, ゆうパック対象,送料設定グループ,代表商品送料グループ設定 )
        SELECT 
               tb_sales_detail.伝票番号, 
               tb_sales_detail.明細行, 
               tb_sales_detail.キャンセル区分, 
               tb_sales_detail.明細行キャンセル, 
               tb_sales_detail.受注日, 
               tb_sales_detail.店舗コード, 
               tb_sales_detail.店舗名, 
               tb_sales_detail.配送方法コード, 
               tb_sales_detail.発送方法, 
               tb_sales_detail.受注状態, 
               tb_sales_detail.商品コード（伝票）, 
               tb_sales_detail.受注数, 
               IF(IFNULL(tb_package_type.longlength, 0)>0, tb_package_type.longlength, tb_productchoiceitems.width) AS 横, 
               IF(IFNULL(tb_package_type.middlelength, 0)>0, tb_package_type.middlelength, tb_productchoiceitems.height) AS 縦, 
               IF(IFNULL(tb_package_type.shortlength, 0)>0, tb_package_type.shortlength, tb_productchoiceitems.depth) AS 高さ, 
               tb_productchoiceitems.weight + IFNULL(tb_package_type.weight, 0) AS 重さ, 
               0 AS 明細数, 
               '' AS 全品計測OK, 
               Left(IFNULL(tb_sales_detail.送り先郵便番号, ''), 2) AS 郵便番号頭二桁, 
               0 AS ゆうパック対象,
               IFNULL(tb_shippingdivision.shipping_group_code, 0) AS 送料設定グループ,
               IFNULL(tb_shippingdivision_daihyo.shipping_group_code, 0) AS 代表商品送料グループ設定
        FROM 
                tb_sales_detail 
                INNER JOIN tb_productchoiceitems ON 
                          tb_sales_detail.商品コード（伝票） = tb_productchoiceitems.ne_syohin_syohin_code
                INNER JOIN tb_mainproducts ON
                          tb_productchoiceitems.daihyo_syohin_code = tb_mainproducts.daihyo_syohin_code
                LEFT JOIN tb_package_type ON 
                          tb_productchoiceitems.package_id = tb_package_type.package_id
                LEFT JOIN tb_shippingdivision ON
                          tb_productchoiceitems.shippingdivision_id = tb_shippingdivision.id
                LEFT JOIN tb_shippingdivision AS tb_shippingdivision_daihyo ON
                          tb_mainproducts.送料設定 = tb_shippingdivision_daihyo.id
        WHERE tb_sales_detail.キャンセル区分='0' AND 
               tb_sales_detail.明細行キャンセル='0' AND 
               tb_sales_detail.店舗コード In ('1', '2', '9', '12', '13', '14', '16', '20', '22', '23', '27', '31', '32', '35') AND 
               (tb_sales_detail.受注状態='起票済(CSV/手入力)' Or tb_sales_detail.受注状態='納品書印刷待ち') AND 
               tb_sales_detail.発送方法<>'店頭渡し' AND 
               tb_sales_detail.受注数 > 0 
        ORDER BY 
               tb_sales_detail.伝票番号, 
               tb_sales_detail.明細行 ;
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // ワークテーブルデータ格納
      $sql = <<<EOD
INSERT INTO
       tb_voucher_list
       ( 伝票番号,
         明細数,
         width,
         height,
         depth,
         weight,
         全品計測OK )
SELECT
       伝票番号,
       COUNT(明細行) AS 明細行のカウント,
       MIN(width) AS 横の最小,
       MIN(height) AS 縦の最小,
       MIN(depth) AS 高さの最小,
       MIN(weight) AS 重量の最小,
       IF(MIN(width)*MIN(height)*MIN(depth)*MIN(weight)=0, 'OUT', 'OK') AS 全品計測OK
FROM
       tb_progress_order
GROUP BY
       伝票番号;
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      
      // 作成した【tbl伝票番号リスト】を使って【tbl進行中受注】の明細数と全品計測OKを更新する
      $sql = <<<EOD
UPDATE
  tb_progress_order INNER JOIN tb_voucher_list ON tb_progress_order.伝票番号 = tb_voucher_list.伝票番号
SET
  tb_progress_order.明細数 = tb_voucher_list.明細数,
  tb_progress_order.全品計測OK = tb_voucher_list.全品計測OK
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '受注データ取り込み', '終了'));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '振り分け処理', '開始'));
      
      // 作成した受注データの読み込み
      $sql = <<<EOD
SELECT
  po.*,
  d.送り先住所1,
  COALESCE(pci.shippingdivision_id, m.送料設定) AS 送料設定
FROM
  tb_progress_order po
  JOIN tb_sales_detail d
    ON po.伝票番号 = d.伝票番号
    AND po.明細行 = d.明細行
  JOIN tb_productchoiceitems pci
    ON d.商品コード（伝票） = pci.ne_syohin_syohin_code
  JOIN tb_mainproducts m ON
    pci.daihyo_syohin_code = m.daihyo_syohin_code
ORDER BY
  po.伝票番号, po.明細行
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // 発送方法ごとの情報を取得
      $dmRepository = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbDeliveryMethod');
      $deliveryMethods = $dmRepository->getTbDeliveryMethodsWithShippingGroupCode();

      // 直前チェック
      $first = true;
      $beforeVoucher = "";
      $beforeItemCount = "";
      $beforeOK = "";
      $beforeAddress = "";
      
      // 確定フラグ
      $confirmFlag = 0;
      
      // 同梱関連
      $bundleCheck = 0;
      $totalBundledWeight = 0;
      $totalBundledVolume = 0;
      
      // 判定フラグ
      $judgeFlag = "";
      
      // サイズ
      $sizeArray = array();
      $short = 0;
      $middle = 0;
      $long = 0;
      $volume = 0;
      $totalSize = 0;
      
      // 数
      $overlapCount = 0;
      $areaCount = 0;
      $boxedCount = 0;
      $volumeCount = 0;
      
      // 発送方法
      $beforeDeliveryName = "";
      $beforeDeliveryId   = 0;
      $beforeDeliveryCost = 0;

      $deliveryName = "";
      $deliveryId   = 0;
      $deliveryCost = 0;
      
      // ■メインループ(tbl受注)
      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        // 初期処理
        $row['明細数'] = intval($row['明細数']);
        
        if($first){
          $beforeVoucher = $row['伝票番号'];
          $beforeItemCount = $row['明細数'];
          $beforeOK = $row['全品計測OK'];
          $beforeAddress = $row['送り先住所1'];
          $first = false;
        }
        
        // ◇伝票番号が変わったら(同梱)
        if($beforeVoucher != $row['伝票番号']){
          // ◇同梱処理
          // 直前の伝票番号が複数明細(=同梱)
          if($beforeItemCount > 1){
          
            // 直前の伝票番号の全品計測OKがOKだったら
            if($beforeOK == "OK"){
              // 確定フラグ
              $confirmFlag = false;
              
              // ルールをチェック
              foreach($rules as $rule){
              
                // ◇暫定チェック順の方が大きいならチェックを飛ばす
                if($rule['checkorder'] >= $bundleCheck){
                  // 重量チェック
                  if($totalBundledWeight > $rule['weight']){
                    continue;
                  }
                  
                  // 体積チェック
                  if($totalBundledVolume > $rule['volume']){
                    continue;
                  }

                  // 都道府県チェック
                  if ($rule['prefecture_check_column']) {
                    // 受注情報から発注先都道府県を取得
                    $shippingPrefecture = $this->extractPrefectureFromAddress($beforeAddress);

                    // 利用可フラグ確認
                    if (!in_array($shippingPrefecture, $rule['prefectures'], true)) {
                      continue;
                    }
                  }

                  // 確定したら更新
                  $this->setShippingMethodAll($rule['delivery_id'],$rule['delivery_name'],$beforeVoucher);
                  $confirmFlag = true;
                  break;
                }
              }
              
              if(!$confirmFlag){
                // 最大をセット
                $this->setShippingMethodAll($maxId,$maxName,$beforeVoucher);
              }
            // 直前の伝票番号の全品計測OKがNGだったら、最大の送料設定を設定
            } else {
                $this->setShippingMethodAll($deliveryId,$deliveryName,$beforeVoucher);
            }
          }
          
          // リセット
          $bundleCheck = 0;
          $totalBundledWeight = 0;
          $totalBundledVolume = 0;
          $short = 0;
          $middle = 0;
          $long = 0;
          $totalSize = 0;
          $volume = 0;

          // 発送方法
          $beforeDeliveryName = "";
          $beforeDeliveryId   = 0;
          $beforeDeliveryCost = 0;

          $deliveryName = "";
          $deliveryId   = 0;
          $deliveryCost = 0;
        }

        $sizeArray = $this->getSizeArray($row);
        
        if($short < $sizeArray[0])$short = $sizeArray[0];
        if($middle < $sizeArray[1])$middle = $sizeArray[1];
        if($long < $sizeArray[2])$long = $sizeArray[2];
        if($totalSize < $short + $middle + $long)$totalSize = $short + $middle + $long;
        
        $volume = $short * $middle * $long;
        
        // ◇単品 = 1つの商品だけを買っている(個数は複数買っているかもしれない)
        if($row['明細数'] == 1){

          // 備考: サイズ計測値が全部入っているかどうか調べるために、以下のようなIF文を書いていたが、
          // 処理速度に影響があったのと、この後にで出てくる同梱のチェック部分では、
          // 伝票番号に紐づいている他のレコードの商品もすべてサイズが計測されているのが条件になってくるので、
          // <全品計測OK>の項目を追加して対応することにした。
          // 参考1: If 受注RS!Width <> 0 And 受注RS!Height <> 0 And 受注RS!depth <> 0 And 受注RS!weight <> 0 Then 'すべての計測値を設定していたら
          // 参考2: If (受注RS!Width * 受注RS!Height * 受注RS!depth * 受注RS!weight) <> 0 Then 'すべての計測値を設定していたら
          
          // 単商品
          if($row['受注数'] == 1){
            $yamatoAvailableFlg = 0;
            $sagawaAvailableFlg = 0;
            if (
              $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60
              || $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80
              || $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_100
              || $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_140
              || $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_160
            ) {
              $yamatoAvailableFlg = $this->checkYamatoAvailability($row);
            }

            if ($yamatoAvailableFlg === 0) {
              if (
                $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60
                || $row['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80
              ) {
                $sagawaAvailableFlg = $this->checkSagawaAvailability($row);
              }
            }

            if ($yamatoAvailableFlg === 1) {
              $deliveryId = TbDeliverySplitRule::DELIVERY_ID_YAMATO;
              $deliveryName = DbCommonUtil::DELIVERY_METHOD_YAMATO_HATSUBARAI;
            } elseif ($sagawaAvailableFlg === 1) {
              $deliveryId = TbDeliverySplitRule::DELIVERY_ID_SAGAWA;
              $deliveryName = DbCommonUtil::DELIVERY_METHOD_TAKUHAI;
            } else {
              // SKU実績があれば取得
              if($row['送料設定グループ'] > 0){
                $shippingGroupId = "".$row['送料設定グループ'];
                $deliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupId]['name'];
                $deliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupId]['id'];
              // 無ければ代表商品の送料グループ設定
              } else {
                // 代表商品の送料グループ設定を取得
                $daihyoshippingGroupId = "".$row['代表商品送料グループ設定'];
                $deliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$daihyoshippingGroupId]['name'];
                $deliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$daihyoshippingGroupId]['id'];
              }
            }
            $this->setShippingMethod($deliveryId,$deliveryName,"1明細1商品",$row['伝票番号'],$row['明細行']);

          // 複数商品
          } else {
          
            // 全商品の全計測値を設定していたら
            if($row['全品計測OK'] == "OK"){
              
              // 確定フラグ
              $confirmFlag = false;
              
              // 明細種別
              foreach($rules as $rule){
      
                // 短辺チェック
                if($rule['shortlength'] > 0 && $short > $rule['shortlength']){
                  continue;
                }

                // 中辺チェック
                if($rule['middlelength'] > 0 && $middle > $rule['middlelength']){
                  continue;
                }

                // 長辺チェック
                if($rule['longlength'] > 0 && $long > $rule['longlength']){
                  continue;
                }

                // サイズチェック
                if($rule['sizecheck'] > 0 && $totalSize > $rule['totallength']){
                  continue;
                }
                
                // 重量チェック
                if($row['weight'] * $row['受注数'] > $rule['weight']){
                  continue;
                }
                
                // 複数購入チェック
                if($rule['sizecheck'] == 0){
                  
                  // サイズチェックなし
                    
                  // 重ね数（短辺）のチェック
                  $overlapCount = floor($rule['shortlength'] / $short);
                  if($overlapCount == 0){
                    $overlapCount = 1;
                  }
                  
                  // 平面的にいくつ入るか
                  $areaCount = floor(($rule['middlelength'] * $rule['longlength']) / ($middle * $long));
                  $boxedCount = $overlapCount * $areaCount;
                  
                  // 入る数でチェック
                  if($row['受注数'] > $boxedCount){
                    continue;
                  }
                } else {
                  // サイズチェックあり
                  // 体積チェック
                  $volumeCount = floor($rule['volume'] / $volume);
                  
                  // 入る数でチェック
                  if($row['受注数'] > $volumeCount){
                    continue;
                  }
                }

                // 都道府県チェック
                if ($rule['prefecture_check_column']) {
                  // 受注情報から発注先都道府県を取得
                  $shippingPrefecture = $this->extractPrefectureFromAddress($row['送り先住所1']);

                  // 利用可フラグ確認
                  if (!in_array($shippingPrefecture, $rule['prefectures'], true)) {
                    continue;
                  }
                }
                
                // 決定したらセット
                $this->setShippingMethod($rule['delivery_id'],$rule['delivery_name'],"1明細複数商品",$row['伝票番号'],$row['明細行']);
                $confirmFlag = true;
                break;
              }
              
              // 決定しなかったら最大をセット
              if(!$confirmFlag){
                // 最大をセット
                $this->setShippingMethod($maxId,$maxName,"1明細複数商品",$row['伝票番号'],$row['明細行']);
              }
            } else {

              // SKU実績があれば取得
              if($row['送料設定グループ'] > 0){
                $shippingGroupId = "".$row['送料設定グループ'];
                $deliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupId]['name'];
                $deliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupId]['id'];
              // 無ければ代表商品の送料グループ設定
              } else {
                // 代表商品の送料グループ設定を取得
                $daihyoshippingGroupId = "".$row['代表商品送料グループ設定'];
                $deliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$daihyoshippingGroupId]['name'];
                $deliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$daihyoshippingGroupId]['id'];
              }
              
              $this->setShippingMethod($deliveryId,$deliveryName,"1明細複数商品",$row['伝票番号'],$row['明細行']);

            }
          }
          
          // リセット
          $bundleCheck = 0;
          $totalBundledWeight = 0;
          $totalBundledVolume = 0;
          $short = 0;
          $middle = 0;
          $long = 0;
          $totalSize = 0;
          $volume = 0;

        }
        
        // ◇同梱 = 複数の商品を買っていて明細が複数ある
        if($row['明細数'] > 1){
        
          // 全商品の全計測値を設定していたら
          if($row['全品計測OK'] == "OK"){

            // 確定フラグ
            $confirmFlag = false;
            
            foreach($rules as $rule){
              // 短辺チェック
              if($rule['shortlength'] > 0 && $short > $rule['shortlength']){
                continue;
              }

              // 中辺チェック
              if($rule['middlelength'] > 0 && $middle > $rule['middlelength']){
                continue;
              }

              // 長辺チェック
              if($rule['longlength'] > 0 && $long > $rule['longlength']){
                continue;
              }

              // サイズチェック
              if($rule['sizecheck'] > 0 && $totalSize > $rule['totallength']){
                continue;
              }
              
              // 重量チェック
              if($row['weight'] * $row['受注数'] > $rule['weight']){
                continue;
              }

              // 都道府県チェック
              if ($rule['prefecture_check_column']) {
                // 受注情報から発注先都道府県を取得
                $shippingPrefecture = $this->extractPrefectureFromAddress($row['送り先住所1']);

                // 利用可フラグ確認
                if (!in_array($shippingPrefecture, $rule['prefectures'], true)) {
                  continue;
                }
              }
              
              // 決定したらセット
              if($rule['checkorder'] > $bundleCheck){
                $bundleCheck = $rule['checkorder'];
              }
              $confirmFlag = true;
              break;
            }
            
            // 決定しなかったら最大をセット
            if(!$confirmFlag){
              // 最大をセット
              $bundleCheck = $maxId;
            }
            
            $totalBundledVolume += $row['width'] * $row['height'] * $row['depth'] * $row['受注数'];
            $totalBundledWeight += $row['weight'] * $row['受注数'];

          } else {

            // SKU実績があれば取得
            if($row['送料設定グループ'] > 0){
              $shippingGroupId = "".$row['送料設定グループ'];
              $beforeDeliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupId]['name'];
              $beforeDeliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupId]['id'];
              $beforeDeliveryCost = $deliveryMethods[$beforeDeliveryId]->getDeliveryCost();
            // 無ければ代表商品の送料グループ設定
            } else {
              // 代表商品の送料グループ設定を取得
              $daihyoshippingGroupId = "".$row['代表商品送料グループ設定'];
              $beforeDeliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$daihyoshippingGroupId]['name'];
              $beforeDeliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$daihyoshippingGroupId]['id'];
              $beforeDeliveryCost = $deliveryMethods[$beforeDeliveryId]->getDeliveryCost();
            }
            
            // 既存より送料が高ければ再設定
            if($deliveryCost < $beforeDeliveryCost){
              $deliveryName = $beforeDeliveryName;
              $deliveryId   = $beforeDeliveryId;
              $deliveryCost = $beforeDeliveryCost;
            }
          }
        }
        
        $beforeVoucher = $row['伝票番号'];
        $beforeItemCount = $row['明細数'];
        $beforeOK = $row['全品計測OK'];
        $beforeAddress = $row['送り先住所1'];
      }
      
      // ◇同梱処理
      // 直前の伝票番号が複数明細(=同梱)
      if($beforeItemCount > 1){
      
        // 直前の伝票番号の全品計測OKがOKだったら
        if($beforeOK == "OK"){
          // 確定フラグ
          $confirmFlag = false;
          
          // ルールをチェック
          foreach($rules as $rule){
          
            // ◇暫定チェック順の方が大きいならチェックを飛ばす
            if($rule['checkorder'] >= $bundleCheck){
              // 重量チェック
              if($totalBundledWeight > $rule['weight']){
                continue;
              }
              
              // 体積チェック
              if($totalBundledVolume > $rule['volume']){
                continue;
              }

              // 都道府県チェック
              if ($rule['prefecture_check_column']) {
                // 受注情報から発注先都道府県を取得
                $shippingPrefecture = $this->extractPrefectureFromAddress($beforeAddress);

                // 利用可フラグ確認
                if (!in_array($shippingPrefecture, $rule['prefectures'], true)) {
                  continue;
                }
              }

              // 確定したら更新
              $this->setShippingMethodAll($rule['delivery_id'],$rule['delivery_name'],$beforeVoucher);
              $confirmFlag = true;
              break;
            }
          }
          
          if(!$confirmFlag){
            // 最大をセット
            $this->setShippingMethodAll($maxId,$maxName,$beforeVoucher);
          }
        // 直前の伝票番号の全品計測OKがNGだったら、最大の送料設定を設定
        } else {
            $this->setShippingMethodAll($deliveryId,$deliveryName,$beforeVoucher);
        }
      }
      
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '振り分け処理', '終了'));

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();
    } catch (\Exception $e) {

      $logger->error('発送方法一括変換処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('発送方法一括変換処理 エラー', '発送方法一括変換処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '発送方法一括変換処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }
  
  /**
   * 発送方法セット（同梱）
   * @param string $filePath
   * @return array
   */
  private function setShippingMethodAll($id, $name, $voucher)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
UPDATE
  tb_progress_order
SET
  推奨配送方法コード = :id
  ,推奨発送方法 = :name
  ,明細種別 = '同梱'
WHERE
  伝票番号 = :voucher
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':voucher', $voucher);
    $stmt->execute();

    return;
  }
  
  /**
   * サイズ配列取得
   * @param string $filePath
   * @return array
   */
  private function getSizeArray($row)
  {
    $sizeArray = array(
      intval($row['width']),
      intval($row['height']),
      intval($row['depth']),
    );

    sort($sizeArray, SORT_NUMERIC);

    return $sizeArray;
  }
  
  /**
   * 発送方法セット
   * @return array
   */
  private function setShippingMethod($id, $name, $type, $voucher, $row)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
UPDATE
    tb_progress_order
SET
    推奨配送方法コード = :id
    ,推奨発送方法 = :name
    ,明細種別 = :type
WHERE
    伝票番号 = :voucher
AND 明細行 = :row
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':type', $type);
    $stmt->bindValue(':voucher', $voucher);
    $stmt->bindValue(':row', $row);
    $stmt->execute();

    return;
  }

  /**
   * ヤマトに変更可能か判定する
   * @param array $voucher
   * @return int 1|0 tb_prefecture.〇〇_available_flg
   */
  private function checkYamatoAvailability($voucher)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    if ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60) {
      $prefectureCheckColumn = 'yamato60_available_flg';
    } elseif ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80) {
      $prefectureCheckColumn = 'yamato80_available_flg';
    } elseif ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_100) {
      $prefectureCheckColumn = 'yamato140_available_flg';
    } elseif ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_140) {
      $prefectureCheckColumn = 'yamato140_available_flg';
    } elseif ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_160) {
      $prefectureCheckColumn = 'yamato160_available_flg';
    } else {
      return 0;
    }

    $shippingPrefecture = $this->extractPrefectureFromAddress($voucher['送り先住所1']);
    return $pRepo->checkPrefectureCheckColumnAvailability($prefectureCheckColumn, $shippingPrefecture);
  }

  /**
   * 佐川に変更可能か判定する
   * @param array $voucher
   * @return int 1|0 tb_prefecture.〇〇_available_flg
   */
  private function checkSagawaAvailability($voucher)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    if ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60) {
      $prefectureCheckColumn = 'sagawa60_available_flg';
    } elseif ($voucher['送料設定'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80) {
      $prefectureCheckColumn = 'sagawa80_available_flg';
    } else {
      return 0;
    }

    $shippingPrefecture = $this->extractPrefectureFromAddress($voucher['送り先住所1']);
    return $pRepo->checkPrefectureCheckColumnAvailability($prefectureCheckColumn, $shippingPrefecture);
  }

  /**
   * 住所文字列から都道府県情報を抜き出して返す。
   * @param string $address 住所情報
   * @return string 都道府県情報
   */
  private function extractPrefectureFromAddress($address)
  {
    foreach (TbPrefecture::PREFECTURE_NAMES as $prefecture) {
      if (preg_match("/$prefecture/u", $address, $matches)) {
        return $matches[0];
      }
    }
    // 該当がなければ、空文字を返却
    return '';
  }
}
