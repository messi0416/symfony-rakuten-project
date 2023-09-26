<?php
namespace forestlib\AlibabaSdk\Plusnao\Entity;

class Offer extends AbstractEntity
{
  public $offerId;
  public $detailsUrl;
  public $memberId;
  public $type;
  public $offerStatus;
  public $subject;
  public $qualityLevel;
  public $tradeType;
  public $postCategryId;
  public $imageList;
  public $skuPics;

  public $unit;
  public $priceUnit;
  public $amount;
  public $amountOnSale;
  public $saledCount;
  public $retailPrice;
  public $unitPrice;

  public $productUnitWeight;
  public $freightType;
  public $termOfferProcess;

  public $isPrivate;
  public $isPrivateOffer;
  public $isPriceAuthOffer;
  public $isPicAuthOffer;
  public $isOfferSupportOnlineTrade;
  public $isSkuOffer;
  public $isSkuTradeSupported;
  public $isSupportMix;

  public $gmtCreate;
  public $gmtModified;
  public $gmtLastRepost;
  public $gmtApproved;
  public $gmtExpire;

  public $skuArray;

  /*

  public $details;
  public $tradingType;
  public $productFeatureList;
  public $priceRanges;
  public $freightTemplateId;
  public $sendGoodsId;
  */

  /**
   * 必ず配列で返す
   */
  public function getSkuArray()
  {
    return $this->skuArray ? $this->skuArray : [];
  }

  /**
   * SKU数取得
   * @param callable $filter
   * @return int 件数
   */
  public function getSkuNum($filter = null)
  {
    $num = 0;

    // 簡単のため、単品販売の場合はフィルターは無効とし、購入可能(1) or 不能(0)を返す。（項目がちがいすぎる）
    if (!$this->isSkuOffer) {
      return $this->amountOnSale > 0 ? 1 : 0;
    }

    // SKU も、一軸 or 二軸で構成が違う
    foreach($this->getSkuArray() as $parent) {
      $list = isset($parent->children) && is_array($parent->children)
            ? $parent->children
            : [ $parent ]
      ;

      if ($filter) {
        $num += count(array_filter($list, $filter));
      } else {
        $num += count($list);
      }
    }

    return $num;
  }


  /**
   * 日本時刻へ変換
   * @return \DateTime
   */
  public function getGmtCreateJst()
  {
    return $this->convertDateStringToJst($this->gmtCreate);
  }
  /**
   * 日本時刻へ変換
   * @return \DateTime
   */
  public function getGmtModifiedJst()
  {
    return $this->convertDateStringToJst($this->gmtModified);
  }
  /**
   * 日本時刻へ変換
   * @return \DateTime
   */
  public function getGmtLastRepostJst()
  {
    return $this->convertDateStringToJst($this->gmtLastRepost);
  }
  /**
   * 日本時刻へ変換
   * @return \DateTime
   */
  public function getGmtApprovedJst()
  {
    return $this->convertDateStringToJst($this->gmtApproved);
  }
  /**
   * 日本時刻へ変換
   * @return \DateTime
   */
  public function getGmtExpireJst()
  {
    return $this->convertDateStringToJst($this->gmtExpire);
  }

  /**
   * 日本時刻への変換処理
   * @param $str
   * @return \DateTime|null
   */
  private function convertDateStringToJst($str)
  {
    $dt = null;
    if (strlen($str)) {
      if (preg_match('/^(\d{8})(\d{6})(?:\d{3})(\+\d{4})/', $str, $m)) {
        $dt = new \DateTime(sprintf('%sT%s%s', $m[1], $m[2], $m[3]));
        $dt->setTimezone(new \DateTimeZone('Asia/Tokyo'));

      } else {
        throw new \RuntimeException('unexpected format datetime: [' . $str . ']');
      }
    }

    return $dt;
  }

}
?>
