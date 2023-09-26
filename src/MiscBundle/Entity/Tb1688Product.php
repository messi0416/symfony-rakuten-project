<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * Tb1688Product
 */
class Tb1688Product 
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $offer_id;

  /**
   * @var string
   */
  private $details_url = '';

  /**
   * @var string
   */
  private $member_id = '';

  /**
   * @var string
   */
  private $type = '';

  /**
   * @var string
   */
  private $offer_status = '';

  /**
   * @var string
   */
  private $subject = '';

  /**
   * @var integer
   */
  private $quality_level = 0;

  /**
   * @var integer
   */
  private $trade_type = 0;

  /**
   * @var integer
   */
  private $post_categry_id = 0;

  /**
   * @var string
   */
  private $unit = '';

  /**
   * @var string
   */
  private $price_unit = '';

  /**
   * @var integer
   */
  private $amount = 0;

  /**
   * @var integer
   */
  private $amount_on_sale = 0;

  /**
   * @var integer
   */
  private $saled_count = 0;

  /**
   * @var string
   */
  private $product_unit_weight = 0;

  /**
   * @var string
   */
  private $freight_type = '';

  /**
   * @var integer
   */
  private $term_offer_process = 0;

  /**
   * @var integer
   */
  private $is_private = 0;

  /**
   * @var integer
   */
  private $is_private_offer = 0;

  /**
   * @var integer
   */
  private $is_price_auth_offer = 0;

  /**
   * @var integer
   */
  private $is_pic_auth_offer = 0;

  /**
   * @var integer
   */
  private $is_offer_support_online_trade = 0;

  /**
   * @var integer
   */
  private $is_sku_offer = 0;

  /**
   * @var integer
   */
  private $is_sku_trade_supported = 0;

  /**
   * @var integer
   */
  private $is_support_mix = 0;

  /**
   * @var \DateTime
   */
  private $gmt_create;

  /**
   * @var \DateTime
   */
  private $gmt_modified;

  /**
   * @var \DateTime
   */
  private $gmt_last_repost;

  /**
   * @var \DateTime
   */
  private $gmt_approved;

  /**
   * @var \DateTime
   */
  private $gmt_expire;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set offer_id
   *
   * @param integer $offerId
   * @return Tb1688Product
   */
  public function setOfferId($offerId)
  {
    $this->offer_id = $offerId;

    return $this;
  }

  /**
   * Get offer_id
   *
   * @return integer 
   */
  public function getOfferId()
  {
    return $this->offer_id;
  }

  /**
   * Set details_url
   *
   * @param string $detailsUrl
   * @return Tb1688Product
   */
  public function setDetailsUrl($detailsUrl)
  {
    $this->details_url = is_null($detailsUrl) ? '' : $detailsUrl;

    return $this;
  }

  /**
   * Get details_url
   *
   * @return string 
   */
  public function getDetailsUrl()
  {
    return $this->details_url;
  }

  /**
   * Set member_id
   *
   * @param string $memberId
   * @return Tb1688Product
   */
  public function setMemberId($memberId)
  {
    $this->member_id = is_null($memberId) ? '' : $memberId;

    return $this;
  }

  /**
   * Get member_id
   *
   * @return string 
   */
  public function getMemberId()
  {
    return $this->member_id;
  }

  /**
   * Set type
   *
   * @param string $type
   * @return Tb1688Product
   */
  public function setType($type)
  {
    $this->type = is_null($type) ? '' : $type;

    return $this;
  }

  /**
   * Get type
   *
   * @return string 
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set offer_status
   *
   * @param string $offerStatus
   * @return Tb1688Product
   */
  public function setOfferStatus($offerStatus)
  {
    $this->offer_status = is_null($offerStatus) ? '' : $offerStatus;

    return $this;
  }

  /**
   * Get offer_status
   *
   * @return string 
   */
  public function getOfferStatus()
  {
    return $this->offer_status;
  }

  /**
   * Set subject
   *
   * @param string $subject
   * @return Tb1688Product
   */
  public function setSubject($subject)
  {
    $this->subject = is_null($subject) ? '' : $subject;

    return $this;
  }

  /**
   * Get subject
   *
   * @return string 
   */
  public function getSubject()
  {
    return $this->subject;
  }

  /**
   * Set quality_level
   *
   * @param integer $qualityLevel
   * @return Tb1688Product
   */
  public function setQualityLevel($qualityLevel)
  {
    $this->quality_level = is_null($qualityLevel) ? 0 : $qualityLevel;

    return $this;
  }

  /**
   * Get quality_level
   *
   * @return integer 
   */
  public function getQualityLevel()
  {
    return $this->quality_level;
  }

  /**
   * Set trade_type
   *
   * @param integer $tradeType
   * @return Tb1688Product
   */
  public function setTradeType($tradeType)
  {
    $this->trade_type = is_null($tradeType) ? 0 : $tradeType;

    return $this;
  }

  /**
   * Get trade_type
   *
   * @return integer 
   */
  public function getTradeType()
  {
    return $this->trade_type;
  }

  /**
   * Set post_categry_id
   *
   * @param integer $postCategryId
   * @return Tb1688Product
   */
  public function setPostCategryId($postCategryId)
  {
    $this->post_categry_id = is_null($postCategryId) ? 0 : $postCategryId;

    return $this;
  }

  /**
   * Get post_categry_id
   *
   * @return integer 
   */
  public function getPostCategryId()
  {
    return $this->post_categry_id;
  }

  /**
   * Set unit
   *
   * @param string $unit
   * @return Tb1688Product
   */
  public function setUnit($unit)
  {
    $this->unit = is_null($unit) ? '' : $unit;

    return $this;
  }

  /**
   * Get unit
   *
   * @return string 
   */
  public function getUnit()
  {
    return $this->unit;
  }

  /**
   * Set price_unit
   *
   * @param string $priceUnit
   * @return Tb1688Product
   */
  public function setPriceUnit($priceUnit)
  {
    $this->price_unit = is_null($priceUnit) ? '' : $priceUnit;

    return $this;
  }

  /**
   * Get price_unit
   *
   * @return string 
   */
  public function getPriceUnit()
  {
    return $this->price_unit;
  }

  /**
   * Set amount
   *
   * @param integer $amount
   * @return Tb1688Product
   */
  public function setAmount($amount)
  {
    $this->amount = is_null($amount) ? 0 : $amount;

    return $this;
  }

  /**
   * Get amount
   *
   * @return integer 
   */
  public function getAmount()
  {
    return $this->amount;
  }

  /**
   * Set amount_on_sale
   *
   * @param integer $amountOnSale
   * @return Tb1688Product
   */
  public function setAmountOnSale($amountOnSale)
  {
    $this->amount_on_sale = is_null($amountOnSale) ? 0 : $amountOnSale;

    return $this;
  }

  /**
   * Get amount_on_sale
   *
   * @return integer 
   */
  public function getAmountOnSale()
  {
    return $this->amount_on_sale;
  }

  /**
   * Set saled_count
   *
   * @param integer $saledCount
   * @return Tb1688Product
   */
  public function setSaledCount($saledCount)
  {
    $this->saled_count = is_null($saledCount) ? 0 : $saledCount;

    return $this;
  }

  /**
   * Get saled_count
   *
   * @return integer 
   */
  public function getSaledCount()
  {
    return $this->saled_count;
  }

  /**
   * Set product_unit_weight
   *
   * @param string $productUnitWeight
   * @return Tb1688Product
   */
  public function setProductUnitWeight($productUnitWeight)
  {
    $this->product_unit_weight = is_null($productUnitWeight) ? 0 : $productUnitWeight;

    return $this;
  }

  /**
   * Get product_unit_weight
   *
   * @return string 
   */
  public function getProductUnitWeight()
  {
    return $this->product_unit_weight;
  }

  /**
   * Set freight_type
   *
   * @param string $freightType
   * @return Tb1688Product
   */
  public function setFreightType($freightType)
  {
    $this->freight_type = is_null($freightType) ? '' : $freightType;

    return $this;
  }

  /**
   * Get freight_type
   *
   * @return string 
   */
  public function getFreightType()
  {
    return $this->freight_type;
  }

  /**
   * Set term_offer_process
   *
   * @param integer $termOfferProcess
   * @return Tb1688Product
   */
  public function setTermOfferProcess($termOfferProcess)
  {
    $this->term_offer_process = is_null($termOfferProcess) ? '' : $termOfferProcess;

    return $this;
  }

  /**
   * Get term_offer_process
   *
   * @return integer 
   */
  public function getTermOfferProcess()
  {
    return $this->term_offer_process;
  }

  /**
   * Set is_private
   *
   * @param integer $isPrivate
   * @return Tb1688Product
   */
  public function setIsPrivate($isPrivate)
  {
    $this->is_private = $isPrivate ? 1 : 0;

    return $this;
  }

  /**
   * Get is_private
   *
   * @return integer 
   */
  public function getIsPrivate()
  {
    return $this->is_private;
  }

  /**
   * Set is_private_offer
   *
   * @param integer $isPrivateOffer
   * @return Tb1688Product
   */
  public function setIsPrivateOffer($isPrivateOffer)
  {
    $this->is_private_offer = $isPrivateOffer ? 1 : 0;

    return $this;
  }

  /**
   * Get is_private_offer
   *
   * @return integer 
   */
  public function getIsPrivateOffer()
  {
    return $this->is_private_offer;
  }

  /**
   * Set is_price_auth_offer
   *
   * @param integer $isPriceAuthOffer
   * @return Tb1688Product
   */
  public function setIsPriceAuthOffer($isPriceAuthOffer)
  {
    $this->is_price_auth_offer = $isPriceAuthOffer ? 1 : 0;

    return $this;
  }

  /**
   * Get is_price_auth_offer
   *
   * @return integer 
   */
  public function getIsPriceAuthOffer()
  {
    return $this->is_price_auth_offer;
  }

  /**
   * Set is_pic_auth_offer
   *
   * @param integer $isPicAuthOffer
   * @return Tb1688Product
   */
  public function setIsPicAuthOffer($isPicAuthOffer)
  {
    $this->is_pic_auth_offer = $isPicAuthOffer ? 1 : 0;

    return $this;
  }

  /**
   * Get is_pic_auth_offer
   *
   * @return integer 
   */
  public function getIsPicAuthOffer()
  {
    return $this->is_pic_auth_offer;
  }

  /**
   * Set is_offer_support_online_trade
   *
   * @param integer $isOfferSupportOnlineTrade
   * @return Tb1688Product
   */
  public function setIsOfferSupportOnlineTrade($isOfferSupportOnlineTrade)
  {
    $this->is_offer_support_online_trade = $isOfferSupportOnlineTrade ? 1 : 0;

    return $this;
  }

  /**
   * Get is_offer_support_online_trade
   *
   * @return integer 
   */
  public function getIsOfferSupportOnlineTrade()
  {
    return $this->is_offer_support_online_trade;
  }

  /**
   * Set is_sku_offer
   *
   * @param integer $isSkuOffer
   * @return Tb1688Product
   */
  public function setIsSkuOffer($isSkuOffer)
  {
    $this->is_sku_offer = $isSkuOffer ? 1 : 0;

    return $this;
  }

  /**
   * Get is_sku_offer
   *
   * @return integer 
   */
  public function getIsSkuOffer()
  {
    return $this->is_sku_offer;
  }

  /**
   * Set is_sku_trade_supported
   *
   * @param integer $isSkuTradeSupported
   * @return Tb1688Product
   */
  public function setIsSkuTradeSupported($isSkuTradeSupported)
  {
    $this->is_sku_trade_supported = $isSkuTradeSupported ? 1 : 0;

    return $this;
  }

  /**
   * Get is_sku_trade_supported
   *
   * @return integer 
   */
  public function getIsSkuTradeSupported()
  {
    return $this->is_sku_trade_supported;
  }

  /**
   * Set is_support_mix
   *
   * @param integer $isSupportMix
   * @return Tb1688Product
   */
  public function setIsSupportMix($isSupportMix)
  {
    $this->is_support_mix = $isSupportMix ? 1 : 0;

    return $this;
  }

  /**
   * Get is_support_mix
   *
   * @return integer 
   */
  public function getIsSupportMix()
  {
    return $this->is_support_mix;
  }

  /**
   * Set gmt_create
   *
   * @param \DateTime $gmtCreate
   * @return Tb1688Product
   */
  public function setGmtCreate($gmtCreate)
  {
    $this->gmt_create = $gmtCreate;

    return $this;
  }

  /**
   * Get gmt_create
   *
   * @return \DateTime 
   */
  public function getGmtCreate()
  {
    return $this->gmt_create;
  }

  /**
   * Set gmt_modified
   *
   * @param \DateTime $gmtModified
   * @return Tb1688Product
   */
  public function setGmtModified($gmtModified)
  {
    $this->gmt_modified = $gmtModified;

    return $this;
  }

  /**
   * Get gmt_modified
   *
   * @return \DateTime 
   */
  public function getGmtModified()
  {
    return $this->gmt_modified;
  }

  /**
   * Set gmt_last_repost
   *
   * @param \DateTime $gmtLastRepost
   * @return Tb1688Product
   */
  public function setGmtLastRepost($gmtLastRepost)
  {
    $this->gmt_last_repost = $gmtLastRepost;

    return $this;
  }

  /**
   * Get gmt_last_repost
   *
   * @return \DateTime 
   */
  public function getGmtLastRepost()
  {
    return $this->gmt_last_repost;
  }

  /**
   * Set gmt_approved
   *
   * @param \DateTime $gmtApproved
   * @return Tb1688Product
   */
  public function setGmtApproved($gmtApproved)
  {
    $this->gmt_approved = $gmtApproved;

    return $this;
  }

  /**
   * Get gmt_approved
   *
   * @return \DateTime 
   */
  public function getGmtApproved()
  {
    return $this->gmt_approved;
  }

  /**
   * Set gmt_expire
   *
   * @param \DateTime $gmtExpire
   * @return Tb1688Product
   */
  public function setGmtExpire($gmtExpire)
  {
    $this->gmt_expire = $gmtExpire;

    return $this;
  }

  /**
   * Get gmt_expire
   *
   * @return \DateTime 
   */
  public function getGmtExpire()
  {
    return $this->gmt_expire;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return Tb1688Product
   */
  public function setCreated($created)
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return \DateTime 
   */
  public function getCreated()
  {
    return $this->created;
  }

  /**
   * Set updated
   *
   * @param \DateTime $updated
   * @return Tb1688Product
   */
  public function setUpdated($updated)
  {
    $this->updated = $updated;

    return $this;
  }

  /**
   * Get updated
   *
   * @return \DateTime 
   */
  public function getUpdated()
  {
    return $this->updated;
  }
    /**
     * @var integer
     */
    private $sku_num = 0;

    /**
     * @var integer
     */
    private $sku_active_num = 0;

    /**
     * @var \DateTime
     */
    private $last_checked;

    /**
     * Set sku_num
     *
     * @param integer $skuNum
     * @return Tb1688Product
     */
    public function setSkuNum($skuNum)
    {
        $this->sku_num = $skuNum;

        return $this;
    }

    /**
     * Get sku_num
     *
     * @return integer 
     */
    public function getSkuNum()
    {
        return $this->sku_num;
    }

    /**
     * Set last_checked
     *
     * @param \DateTime $lastChecked
     * @return Tb1688Product
     */
    public function setLastChecked($lastChecked)
    {
        $this->last_checked = $lastChecked;

        return $this;
    }

    /**
     * Get last_checked
     *
     * @return \DateTime 
     */
    public function getLastChecked()
    {
        return $this->last_checked;
    }

    /**
     * Set sku_active_num
     *
     * @param integer $skuActiveNum
     * @return Tb1688Product
     */
    public function setSkuActiveNum($skuActiveNum)
    {
        $this->sku_active_num = $skuActiveNum;

        return $this;
    }

    /**
     * Get sku_active_num
     *
     * @return integer 
     */
    public function getSkuActiveNum()
    {
        return $this->sku_active_num;
    }
}
