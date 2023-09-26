<?php
namespace forestlib\AlibabaSdk\Plusnao\Param;

class OfferGetParam extends AbstractParam
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
    , 'skuArray'

    /*

    , 'details'
    , 'tradingType'
    , 'productFeatureList'
    , 'priceRanges'
    , 'freightTemplateId'
    , 'sendGoodsId'
    */
  ];

  /**
   * @return string
   */
  public function getOfferId()
  {
    return isset($this->sdkStdResult['offerId']) ? $this->sdkStdResult['offerId'] : null;
  }

  /**
   * @param string $offerId
   */
  public function setOfferId($offerId)
  {
    $this->sdkStdResult['offerId'] = $offerId;
  }

  /**
   * @return string
   */
  public function getReturnFields()
  {
    return isset($this->sdkStdResult['returnFields']) ? $this->sdkStdResult['returnFields'] : null;
  }

  /**
   * @param string $returnFields
   */
  public function setReturnFields($returnFields)
  {
    $this->sdkStdResult['returnFields'] = $returnFields;
  }

  public function setDefaultReturnFields()
  {
    $this->setReturnFields($this->defaultReturnFields);
  }

}

?>
