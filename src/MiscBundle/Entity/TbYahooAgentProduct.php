<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbYahooAgentProduct
 */
class TbYahooAgentProduct
{
  use FillTimestampTrait;

  /**
   * @return bool
   */
  public function isUpdateFlgOn()
  {
    return $this->getUpdateFlg() != 0;
  }

  /**
   * @return bool
   */
  public function isRegistrationFlgOn()
  {
    return $this->getRegistrationFlg() != 0;
  }


  // ----------------------------------
  // properties
  // ----------------------------------

  /**
   * @var string
   */
  private $shop_code;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $product_code;

  /**
   * @var string
   */
  private $product_name;

  /**
   * @var int
   */
  private $genka_tanka;

  /**
   * @var int
   */
  private $baika_tanka;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set shopCode
   *
   * @param string $shopCode
   *
   * @return TbYahooAgentProduct
   */
  public function setShopCode($shopCode)
  {
    $this->shop_code = $shopCode;

    return $this;
  }

  /**
   * Get shopCode
   *
   * @return string
   */
  public function getShopCode()
  {
    return $this->shop_code;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TbYahooAgentProduct
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set productCode
   *
   * @param string $productCode
   *
   * @return TbYahooAgentProduct
   */
  public function setProductCode($productCode)
  {
    $this->product_code = $productCode;

    return $this;
  }

  /**
   * Get productCode
   *
   * @return string
   */
  public function getProductCode()
  {
    return $this->product_code;
  }

  /**
   * Set productName
   *
   * @param string $productName
   *
   * @return TbYahooAgentProduct
   */
  public function setProductName($productName)
  {
    $this->product_name = $productName;

    return $this;
  }

  /**
   * Get productName
   *
   * @return string
   */
  public function getProductName()
  {
    return $this->product_name;
  }

  /**
   * Set genkaTanka
   *
   * @param int $genkaTanka
   *
   * @return TbYahooAgentProduct
   */
  public function setGenkaTanka($genkaTanka)
  {
    $this->genka_tanka = $genkaTanka;

    return $this;
  }

  /**
   * Get genkaTanka
   *
   * @return int
   */
  public function getGenkaTanka()
  {
    return $this->genka_tanka;
  }

  /**
   * Set baikaTanka
   *
   * @param int $baikaTanka
   *
   * @return TbYahooAgentProduct
   */
  public function setBaikaTanka($baikaTanka)
  {
    $this->baika_tanka = $baikaTanka;

    return $this;
  }

  /**
   * Get baikaTanka
   *
   * @return int
   */
  public function getBaikaTanka()
  {
    return $this->baika_tanka;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbYahooAgentProduct
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
   *
   * @return TbYahooAgentProduct
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
     * @var int
     */
    private $registration_flg;

    /**
     * @var int
     */
    private $update_flg;


    /**
     * Set registrationFlg
     *
     * @param int $registrationFlg
     *
     * @return TbYahooAgentProduct
     */
    public function setRegistrationFlg($registrationFlg)
    {
        $this->registration_flg = $registrationFlg;

        return $this;
    }

    /**
     * Get registrationFlg
     *
     * @return int
     */
    public function getRegistrationFlg()
    {
        return $this->registration_flg;
    }

    /**
     * Set updateFlg
     *
     * @param int $updateFlg
     *
     * @return TbYahooAgentProduct
     */
    public function setUpdateFlg($updateFlg)
    {
        $this->update_flg = $updateFlg;

        return $this;
    }

    /**
     * Get updateFlg
     *
     * @return int
     */
    public function getUpdateFlg()
    {
        return $this->update_flg;
    }
    /**
     * @var \MiscBundle\Entity\TbMainproducts
     */
    private $product;


    /**
     * Set product
     *
     * @param \MiscBundle\Entity\TbMainproducts $product
     *
     * @return TbYahooAgentProduct
     */
    public function setProduct(\MiscBundle\Entity\TbMainproducts $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \MiscBundle\Entity\TbMainproducts
     */
    public function getProduct()
    {
        return $this->product;
    }
}
