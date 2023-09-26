<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VProductMallPrice
 */
class VProductMallPrice
{
  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $sire_code;

  /**
   * @var string
   */
  private $daihyo_syohin_name;

  /**
   * @var integer
   */
  private $baika_tnk = 0;

  /**
   * @var integer
   */
  private $original_price_flg = 0;

  /**
   * @var integer
   */
  private $pricedown_flg = 0;

  /**
   * @var integer
   */
  private $base_baika_tanka = 0;

  /**
   * @var integer
   */
  private $shoplist_registration_flg = 0;

  /**
   * @var integer
   */
  private $shoplist_original_price_flg = 0;

  /**
   * @var integer
   */
  private $shoplist_price = 0;

  /**
   * @var integer
   */
  private $shoplist_current_price = 0;


  /**
   * Get daihyo_syohin_code
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set sire_code
   *
   * @param string $sireCode
   * @return VProductMallPrice
   */
  public function setSireCode($sireCode)
  {
    $this->sire_code = $sireCode;

    return $this;
  }

  /**
   * Get sire_code
   *
   * @return string
   */
  public function getSireCode()
  {
    return $this->sire_code;
  }

  /**
   * Set daihyo_syohin_name
   *
   * @param string $daihyoSyohinName
   * @return VProductMallPrice
   */
  public function setDaihyoSyohinName($daihyoSyohinName)
  {
    $this->daihyo_syohin_name = $daihyoSyohinName;

    return $this;
  }

  /**
   * Get daihyo_syohin_name
   *
   * @return string
   */
  public function getDaihyoSyohinName()
  {
    return $this->daihyo_syohin_name;
  }

  /**
   * Set baika_tnk
   *
   * @param integer $baikaTnk
   * @return VProductMallPrice
   */
  public function setBaikaTnk($baikaTnk)
  {
    $this->baika_tnk = $baikaTnk;

    return $this;
  }

  /**
   * Get baika_tnk
   *
   * @return integer
   */
  public function getBaikaTnk()
  {
    return $this->baika_tnk;
  }

  /**
   * Set original_price_flg
   *
   * @param integer $originalPriceFlg
   * @return VProductMallPrice
   */
  public function setOriginalPriceFlg($originalPriceFlg)
  {
    $this->original_price_flg = $originalPriceFlg;

    return $this;
  }

  /**
   * Get original_price_flg
   *
   * @return integer
   */
  public function getOriginalPriceFlg()
  {
    return $this->original_price_flg;
  }

  /**
   * Set pricedown_flg
   *
   * @param integer $pricedownFlg
   * @return VProductMallPrice
   */
  public function setPricedownFlg($pricedownFlg)
  {
    $this->pricedown_flg = $pricedownFlg;

    return $this;
  }

  /**
   * Get pricedown_flg
   *
   * @return integer
   */
  public function getPricedownFlg()
  {
    return $this->pricedown_flg;
  }

  /**
   * Set base_baika_tanka
   *
   * @param integer $baseBaikaTanka
   * @return VProductMallPrice
   */
  public function setBaseBaikaTanka($baseBaikaTanka)
  {
    $this->base_baika_tanka = $baseBaikaTanka;

    return $this;
  }

  /**
   * Get base_baika_tanka
   *
   * @return integer
   */
  public function getBaseBaikaTanka()
  {
    return $this->base_baika_tanka;
  }

  /**
   * Set shoplist_registration_flg
   *
   * @param integer $shoplistRegistrationFlg
   * @return VProductMallPrice
   */
  public function setShoplistRegistrationFlg($shoplistRegistrationFlg)
  {
    $this->shoplist_registration_flg = $shoplistRegistrationFlg;

    return $this;
  }

  /**
   * Get shoplist_registration_flg
   *
   * @return integer
   */
  public function getShoplistRegistrationFlg()
  {
    return $this->shoplist_registration_flg;
  }

  /**
   * Set shoplist_original_price_flg
   *
   * @param integer $shoplistOriginalPriceFlg
   * @return VProductMallPrice
   */
  public function setShoplistOriginalPriceFlg($shoplistOriginalPriceFlg)
  {
    $this->shoplist_original_price_flg = $shoplistOriginalPriceFlg;

    return $this;
  }

  /**
   * Get shoplist_original_price_flg
   *
   * @return integer
   */
  public function getShoplistOriginalPriceFlg()
  {
    return $this->shoplist_original_price_flg;
  }

  /**
   * Set shoplist_price
   *
   * @param integer $shoplistPrice
   * @return VProductMallPrice
   */
  public function setShoplistPrice($shoplistPrice)
  {
    $this->shoplist_price = $shoplistPrice;

    return $this;
  }

  /**
   * Get shoplist_price
   *
   * @return integer
   */
  public function getShoplistPrice()
  {
    return $this->shoplist_price;
  }

  /**
   * Set shoplist_current_price
   *
   * @param integer $shoplistCurrentPrice
   * @return VProductMallPrice
   */
  public function setShoplistCurrentPrice($shoplistCurrentPrice)
  {
    $this->shoplist_current_price = $shoplistCurrentPrice;

    return $this;
  }

  /**
   * Get shoplist_current_price
   *
   * @return integer
   */
  public function getShoplistCurrentPrice()
  {
    return $this->shoplist_current_price;
  }

  /**
   * @var integer
   */
  private $rakuten_original_price_flg = 0;

  /**
   * @var integer
   */
  private $rakuten_price = 0;


  /**
   * Set rakuten_original_price_flg
   *
   * @param integer $rakutenOriginalPriceFlg
   * @return VProductMallPrice
   */
  public function setRakutenOriginalPriceFlg($rakutenOriginalPriceFlg)
  {
    $this->rakuten_original_price_flg = $rakutenOriginalPriceFlg;

    return $this;
  }

  /**
   * Get rakuten_original_price_flg
   *
   * @return integer
   */
  public function getRakutenOriginalPriceFlg()
  {
    return $this->rakuten_original_price_flg;
  }

  /**
   * Set rakuten_price
   *
   * @param integer $rakutenPrice
   * @return VProductMallPrice
   */
  public function setRakutenPrice($rakutenPrice)
  {
    $this->rakuten_price = $rakutenPrice;

    return $this;
  }

  /**
   * Get rakuten_price
   *
   * @return integer
   */
  public function getRakutenPrice()
  {
    return $this->rakuten_price;
  }
}
