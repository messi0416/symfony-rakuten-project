<?php


namespace MiscBundle\Service;

use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;

class RemainOrderByVoucherService
{
  use ServiceBaseTrait;

  /**
   * 伝票毎注残データ取得
   *
   * 表示する拠点コード
   * @param $agent
   * @return array
   */
  public function fetchRemainOrderData($agent)
  {
    $repo = $this->getDoctrine()->getRepository("MiscBundle:TbIndividualorderhistory");
    $remainOrderList = $repo->fetchRemainOrderList($agent);
    $remainOrderPerVoucher = $this->generateRemainOrderPerVoucher($remainOrderList);
    return $this->setImageUrl($remainOrderPerVoucher);
  }

  /**
   * 伝票ごとの注残を作成する
   * @param array $remainOrderList
   * @return array $result
   */
  private function generateRemainOrderPerVoucher($remainOrderList)
  {
    if (count($remainOrderList) === 0) {
      return [];
    }

    $oldestRemainOrderPerVoucher = $this->generateOldestRemainOrder($remainOrderList);
    $remainNumPerVoucher         = $this->generateRemainNumPerVoucher($remainOrderList);
    $agentsPerVoucher            = $this->generateAgentsPerVoucher($remainOrderList);
    $remainNumPerProductCode     = $this->generateRemainNumPerProductCode($remainOrderList);
    $productImages               = $this->generateProductImages($remainOrderList);

    $result = [];
    foreach ($oldestRemainOrderPerVoucher as $voucherNumber => $remainOrder) {
      $remainOrder['voucherNumber']    = $voucherNumber;
      $remainOrder['totalRemainNum']   = $remainNumPerVoucher[$voucherNumber]['totalRemainNum'];
      $remainOrder['agents']           = $agentsPerVoucher[$voucherNumber];

      $remainOrder['oldestUpdateDate'] = $remainOrder['oldestUpdateDate'] ? $remainOrder['oldestUpdateDate']->format('Y/m/d H:i:s') : null;
      $remainOrder['productCode']      = $this->getMaxRemainNumProductCode($remainNumPerProductCode, $voucherNumber);
      $remainOrder['imageDir']         = $productImages[$remainOrder['productCode']]['imageDir'];
      $remainOrder['imageName']        = $productImages[$remainOrder['productCode']]['imageName'];

      $result[] = $remainOrder;
    }

    return $result;
  }

  /**
   * 伝票ごとの注残情報を取得
   * @param $remainOrderList
   * @return array $oldestRemainOrderPerVoucher
   */
  private function generateOldestRemainOrder($remainOrderList)
  {
    $oldestRemainOrderPerVoucher = [];
    foreach ($remainOrderList as $remainOrder) {
      $voucherNumber = $remainOrder['voucher_number'];

      if (! isset($oldestRemainOrderPerVoucher[$voucherNumber])) {
        $oldestRemainOrderPerVoucher[$voucherNumber] = [];

        $oldestRemainOrderPerVoucher[$voucherNumber]['shippingType']     = $remainOrder['shipping_type'];
        $oldestRemainOrderPerVoucher[$voucherNumber]['orderDate']        = $remainOrder['order_date']->format('Y/m/d');
        // 伝票ステータスは古い順に並んでいるので最初のステータスを選択
        $oldestRemainOrderPerVoucher[$voucherNumber]['minRemainStatus']  = $remainOrder['remain_status'];
        // ステータスに該当するremain_dateがnullの場合、現状はnullのままでよいと判断
        $oldestRemainOrderPerVoucher[$voucherNumber]['oldestUpdateDate'] = $this->getOldestUpdateDate($remainOrder['remain_status'], $remainOrder);
      }

      // 伝票ステータスが一番古いのと同じ
      if ($oldestRemainOrderPerVoucher[$voucherNumber]['minRemainStatus'] === $remainOrder['remain_status']) {
        // 最古更新日時を取得
        $oldestRemainOrderPerVoucher[$voucherNumber]['oldestUpdateDate'] = min($oldestRemainOrderPerVoucher[$voucherNumber]['oldestUpdateDate'],
          $this->getOldestUpdateDate($oldestRemainOrderPerVoucher[$voucherNumber]['minRemainStatus'], $remainOrder));
      }
    }
    return $oldestRemainOrderPerVoucher;
  }

  /**
   * 最遅ステータス最古更新日を取得する
   * @param $remainStatus
   * @param $remainOrder
   * @return \DateTime | null
   */
  private function getOldestUpdateDate($remainStatus, $remainOrder)
  {
    // 最遅ステータスに該当する時間がNULLならNULLで返す
    $oldestUpdateDate = '';
    switch ($remainStatus) {
      case TbIndividualorderhistoryRepository::REMAIN_STATUS_UNTREATED:
        $oldestUpdateDate = $remainOrder['min_order_date'];
        break;
      case TbIndividualorderhistoryRepository::REMAIN_STATUS_ORDERED:
        $oldestUpdateDate = $remainOrder['min_remain_ordered_date'];
        break;
      case TbIndividualorderhistoryRepository::REMAIN_STATUS_ARRIVED:
        $oldestUpdateDate = $remainOrder['min_remain_arrived_date'];
        break;
      case TbIndividualorderhistoryRepository::REMAIN_STATUS_WAITED:
        $oldestUpdateDate = $remainOrder['min_remain_waiting_date'];
        break;
      case TbIndividualorderhistoryRepository::REMAIN_STATUS_SHIPPED:
        $oldestUpdateDate = $remainOrder['min_remain_shipping_date'];
        break;
      case TbIndividualorderhistoryRepository::REMAIN_STATUS_SHORTAGE:
      default:
        $oldestUpdateDate = $remainOrder['min_remain_stockout_date'];
        break;
    }
    return $oldestUpdateDate !== null ? new \DateTime($oldestUpdateDate) : null;
  }

  /**
   * 伝票ごとの合計注残情報を取得
   * @param $remainOrderList
   * @return array $remainNumPerVoucher
   */
  private function generateRemainNumPerVoucher($remainOrderList)
  {
    $remainNumPerVoucher = [];
    foreach ($remainOrderList as $remainOrder) {
      $voucherNumber = $remainOrder['voucher_number'];
      if (! isset($remainNumPerVoucher[$voucherNumber])) {
        $remainNumPerVoucher[$voucherNumber] = [];
      }

      if (! isset($remainNumPerVoucher[$voucherNumber]['totalRemainNum'])) {
        $remainNumPerVoucher[$voucherNumber]['totalRemainNum'] = 0;
      }
      $remainNumPerVoucher[$voucherNumber]['totalRemainNum'] += $remainOrder['total_remain_num'];
    }
    return $remainNumPerVoucher;
  }

  /**
   * 伝票ごとの拠点情報を取得
   * @param $remainOrderList
   * @return array $agentsPerVoucher
   */
  private function generateAgentsPerVoucher($remainOrderList)
  {
    $agentsPerVoucher = [];
    foreach ($remainOrderList as $remainOrder) {
      $voucherNumber = $remainOrder['voucher_number'];
      if (! isset($agentsPerVoucher[$voucherNumber])) {
        $agentsPerVoucher[$voucherNumber] = [];
      }

      $agentCode = $remainOrder['agent_code'];
      $agentsPerVoucher[$voucherNumber][$agentCode] = ['loginName' => $remainOrder['login_name'], 'comment' => $remainOrder['note']];
    }
    return $agentsPerVoucher;
  }

  /**
   * 伝票の商品コードごとの注残情報を取得
   * @param $remainOrderList
   * @return array $remainNumPerProductCode
   */
  private function generateRemainNumPerProductCode($remainOrderList)
  {
    $remainNumPerProductCode = [];
    foreach ($remainOrderList as $remainOrder) {
      $voucherNumber = $remainOrder['voucher_number'];
      if (! isset($remainNumPerProductCode[$voucherNumber])) {
        $remainNumPerProductCode[$voucherNumber] = [];
      }

      $productCode = $remainOrder['daihyoSyohinCode'];
      if (! isset($remainNumPerProductCode[$voucherNumber][$productCode])) {
        $remainNumPerProductCode[$voucherNumber][$productCode] = 0;
      }
      $remainNumPerProductCode[$voucherNumber][$productCode] += $remainOrder['total_remain_num'];
    }
    return $remainNumPerProductCode;
  }

  /**
   * 画像情報を取得
   * @param $remainOrderList
   * @return array $productImages
   */
  private function generateProductImages($remainOrderList)
  {
    $productImages = [];
    foreach ($remainOrderList as $remainOrder) {
      $productCode  = $remainOrder['daihyoSyohinCode'];
      $productImage = ['imageDir' => $remainOrder['image_dir'], 'imageName' => $remainOrder['image_name']];
      $productImages[$productCode] = $productImage;
    }
    return $productImages;
  }


  /**
   * 発注残数が一番多い商品の代表商品コードを取得
   * @param array $remainNumList
   * @param integer $voucherNumber
   * @return string
   */
  private function getMaxRemainNumProductCode($remainNumList, $voucherNumber)
  {
    $productCodes = array_keys($remainNumList[$voucherNumber], max($remainNumList[$voucherNumber]));
    return current($productCodes);
  }

  /**
   * 注残情報から画像URLを作成しセットする
   * @param $remainOrderPerVoucher
   * @return mixed
   */
  private function setImageUrl($remainOrderPerVoucher) {
    $parentPath = sprintf('//%s/images/', $this->container->getParameter('host_plusnao'));
    foreach ($remainOrderPerVoucher as &$row) {
      // 画像パス作成
      $row['imageUrl'] = TbMainproductsRepository::createImageUrl($row['imageDir'], $row['imageName'],$parentPath);
    }
    return $remainOrderPerVoucher;
  }
}
