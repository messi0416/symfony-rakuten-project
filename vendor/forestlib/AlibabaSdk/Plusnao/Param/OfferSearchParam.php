<?php
namespace forestlib\AlibabaSdk\Plusnao\Param;

class OfferSearchParam extends AbstractParam
{
  protected $defaultReturnFields = [
      'offerId'
    , 'detailsUrl'
    , 'memberId'
    , 'type'
    , 'offerStatus'
    , 'subject'
    , 'qualityLevel'
    , 'tradeType'
    , 'postCategryId'
    , 'imageList'
    , 'skuPics'
    , 'unit'
    , 'priceUnit'
    , 'amount'
    , 'amountOnSale'
    , 'saledCount'
    , 'retailPrice'
    , 'unitPrice'
    , 'productUnitWeight'
    , 'freightType'
    , 'termOfferProcess'
    , 'isPrivate'
    , 'isPrivateOffer'
    , 'isPriceAuthOffer'
    , 'isPicAuthOffer'
    , 'isOfferSupportOnlineTrade'
    , 'isSkuOffer'
    , 'isSkuTradeSupported'
    , 'isSupportMix'
    , 'gmtCreate'
    , 'gmtModified'
    , 'gmtLastRepost'
    , 'gmtApproved'
    , 'gmtExpire'

    /*
    , 'skuArray'

    , 'details'
    , 'tradingType'
    , 'productFeatureList'
    , 'priceRanges'
    , 'freightTemplateId'
    , 'sendGoodsId'
    */
  ];

  public function setDefaultReturnFields()
  {
    $this->setReturnFields($this->defaultReturnFields);
  }


  public function getPageNo()
  {
    return $this->sdkStdResult['pageNo'];
  }

  public function setPageNo($pageNo)
  {
    $this->sdkStdResult['pageNo'] = $pageNo;
  }

  public function getReturnFields()
  {
    return $this->sdkStdResult['returnFields'];
  }

  public function setReturnFields($returnFields)
  {
    $this->sdkStdResult['returnFields'] = $returnFields;
  }

  public function getPageSize()
  {
    return $this->sdkStdResult['pageSize'];
  }

  public function setPageSize($pageSize)
  {
    $this->sdkStdResult['pageSize'] = $pageSize;
  }

  public function getOrderBy()
  {
    return $this->sdkStdResult['orderBy'];
  }

  public function setOrderBy($orderBy)
  {
    // 数値（日時含む？）以外はエラー？でデータ0件になる模様。
    $this->sdkStdResult['orderBy'] = $orderBy;
  }

  public function getOfferId()
  {
    return $this->sdkStdResult['offerId'];
  }

  public function setOfferId($offerId)
  {
    $this->sdkStdResult['offerId'] = $offerId;
  }

  public function getQ()
  {
    return $this->sdkStdResult['q'];
  }

  public function setQ($q)
  {
    $this->sdkStdResult['q'] = $q;
  }

  public function getIsTradeOffer()
  {
    return $this->sdkStdResult['isTradeOffer'];
  }

  public function setIsTradeOffer($isTradeOffer)
  {
    $this->sdkStdResult['isTradeOffer'] = $isTradeOffer;
  }

  public function getCategory()
  {
    return $this->sdkStdResult['category'];
  }

  public function setCategory($category)
  {
    $this->sdkStdResult['category'] = $category;
  }

  public function getGmtModifiedBegin()
  {
    return $this->sdkStdResult['gmtModifiedBegin'];
  }

  public function setGmtModifiedBegin($gmtModifiedBegin)
  {
    $this->sdkStdResult['gmtModifiedBegin'] = $gmtModifiedBegin;
  }

  public function getGmtModifiedEnd()
  {
    return $this->sdkStdResult['gmtModifiedEnd'];
  }

  public function setGmtModifiedEnd($gmtModifiedEnd)
  {
    $this->sdkStdResult['gmtModifiedEnd'] = $gmtModifiedEnd;
  }

  public function getAddress()
  {
    return $this->sdkStdResult['address'];
  }

  public function setAddress($address)
  {
    $this->sdkStdResult['address'] = $address;
  }

  public function getMemberId()
  {
    return $this->sdkStdResult['memberId'];
  }

  public function setMemberId($memberId)
  {
    $this->sdkStdResult['memberId'] = $memberId;
  }

  public function getTpType()
  {
    return $this->sdkStdResult['tpType'];
  }

  public function setTpType($tpType)
  {
    $this->sdkStdResult['tpType'] = $tpType;
  }

  public function getTpYear()
  {
    return $this->sdkStdResult['tpYear'];
  }

  public function setTpYear($tpYear)
  {
    $this->sdkStdResult['tpYear'] = $tpYear;
  }

  public function getCreditMoney()
  {
    return $this->sdkStdResult['creditMoney'];
  }

  public function setCreditMoney($creditMoney)
  {
    $this->sdkStdResult['creditMoney'] = $creditMoney;
  }

  public function getTradeType()
  {
    return $this->sdkStdResult['tradeType'];
  }

  public function setTradeType($tradeType)
  {
    $this->sdkStdResult['tradeType'] = $tradeType;
  }

  public function getSoldQuantity()
  {
    return $this->sdkStdResult['soldQuantity'];
  }

  public function setSoldQuantity($soldQuantity)
  {
    $this->sdkStdResult['soldQuantity'] = $soldQuantity;
  }

  public function getShowType()
  {
    return $this->sdkStdResult['showType'];
  }

  public function setShowType($showType)
  {
    $this->sdkStdResult['showType'] = $showType;
  }

  public function getBizType()
  {
    return $this->sdkStdResult['bizType'];
  }

  public function setBizType($bizType)
  {
    $this->sdkStdResult['bizType'] = $bizType;
  }

  public function getProvince()
  {
    return $this->sdkStdResult['province'];
  }

  public function setProvince($province)
  {
    $this->sdkStdResult['province'] = $province;
  }

  public function getCity()
  {
    return $this->sdkStdResult['city'];
  }

  public function setCity($city)
  {
    $this->sdkStdResult['city'] = $city;
  }

  public function getPrice()
  {
    return $this->sdkStdResult['price'];
  }

  public function setPrice($price)
  {
    $this->sdkStdResult['price'] = $price;
  }

  public function getQualityLevel()
  {
    return $this->sdkStdResult['qualityLevel'];
  }

  public function setQualityLevel($qualityLevel)
  {
    $this->sdkStdResult['qualityLevel'] = $qualityLevel;
  }

  public function getQuantityBegin()
  {
    return $this->sdkStdResult['quantityBegin'];
  }

  public function setQuantityBegin($quantityBegin)
  {
    $this->sdkStdResult['quantityBegin'] = $quantityBegin;
  }

  public function getOnline()
  {
    return $this->sdkStdResult['online'];
  }

  public function setOnline($online)
  {
    $this->sdkStdResult['online'] = $online;
  }

  public function getGroupIds()
  {
    return $this->sdkStdResult['groupIds'];
  }

  public function setGroupIds($groupIds)
  {
    $this->sdkStdResult['groupIds'] = $groupIds;
  }

  public function getStatus()
  {
    return $this->sdkStdResult['status'];
  }

  public function setStatus($status)
  {
    $this->sdkStdResult['status'] = $status;
  }


}

?>
