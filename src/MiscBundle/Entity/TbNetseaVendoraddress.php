<?php

namespace MiscBundle\Entity;

/**
 * TbNetseaVendoraddress
 */
class TbNetseaVendoraddress
{
  const ADDRESS_KEYWORD_NETSEA          = 'netsea.jp';
  const ADDRESS_KEYWORD_SUPER_DELIVERY  = 'superdelivery.com';
  const ADDRESS_KEYWORD_AKF             = 'akf-japan';
  const ADDRESS_KEYWORD_VIVICA_DUO      = 'vsvivica.com';
  const ADDRESS_KEYWORD_ALIBABA         = '1688.com';

  /**
   * @var string
   */
  private $netseaVendoraddress;

  /**
   * @var string
   */
  private $netseaVendorCode;

  /**
   * @var string
   */
  private $netseaTitle;

  /**
   * @var integer
   */
  private $netseaPrice;

  /**
   * @var integer
   */
  private $netseaSetCount;

  /**
   * @var boolean
   */
  private $netseaPass = '0';

  /**
   * @var boolean
   */
  private $lastCheck = '0';

  /**
   * @var integer
   */
  private $ranking = '0';

  /**
   * @var integer
   */
  private $displayOrder = '0';

  /**
   * @var string
   */
  private $sireCode;


  /**
   * Get netseaVendoraddress
   *
   * @return string
   */
  public function getNetseaVendoraddress()
  {
    return $this->netseaVendoraddress;
  }

  /**
   * Set netseaVendorCode
   *
   * @param string $netseaVendorCode
   *
   * @return TbNetseaVendoraddress
   */
  public function setNetseaVendorCode($netseaVendorCode)
  {
    $this->netseaVendorCode = $netseaVendorCode;

    return $this;
  }

  /**
   * Get netseaVendorCode
   *
   * @return string
   */
  public function getNetseaVendorCode()
  {
    return $this->netseaVendorCode;
  }

  /**
   * Set netseaTitle
   *
   * @param string $netseaTitle
   *
   * @return TbNetseaVendoraddress
   */
  public function setNetseaTitle($netseaTitle)
  {
    $this->netseaTitle = $netseaTitle;

    return $this;
  }

  /**
   * Get netseaTitle
   *
   * @return string
   */
  public function getNetseaTitle()
  {
    return $this->netseaTitle;
  }

  /**
   * Set netseaPrice
   *
   * @param integer $netseaPrice
   *
   * @return TbNetseaVendoraddress
   */
  public function setNetseaPrice($netseaPrice)
  {
    $this->netseaPrice = $netseaPrice;

    return $this;
  }

  /**
   * Get netseaPrice
   *
   * @return integer
   */
  public function getNetseaPrice()
  {
    return $this->netseaPrice;
  }

  /**
   * Set netseaSetCount
   *
   * @param integer $netseaSetCount
   *
   * @return TbNetseaVendoraddress
   */
  public function setNetseaSetCount($netseaSetCount)
  {
    $this->netseaSetCount = $netseaSetCount;

    return $this;
  }

  /**
   * Get netseaSetCount
   *
   * @return integer
   */
  public function getNetseaSetCount()
  {
    return $this->netseaSetCount;
  }

  /**
   * Set netseaPass
   *
   * @param boolean $netseaPass
   *
   * @return TbNetseaVendoraddress
   */
  public function setNetseaPass($netseaPass)
  {
    $this->netseaPass = $netseaPass;

    return $this;
  }

  /**
   * Get netseaPass
   *
   * @return boolean
   */
  public function getNetseaPass()
  {
    return $this->netseaPass;
  }

  /**
   * Set lastCheck
   *
   * @param boolean $lastCheck
   *
   * @return TbNetseaVendoraddress
   */
  public function setLastCheck($lastCheck)
  {
    $this->lastCheck = $lastCheck;

    return $this;
  }

  /**
   * Get lastCheck
   *
   * @return boolean
   */
  public function getLastCheck()
  {
    return $this->lastCheck;
  }

  /**
   * Set ranking
   *
   * @param integer $ranking
   *
   * @return TbNetseaVendoraddress
   */
  public function setRanking($ranking)
  {
    $this->ranking = $ranking;

    return $this;
  }

  /**
   * Get ranking
   *
   * @return integer
   */
  public function getRanking()
  {
    return $this->ranking;
  }

  /**
   * Set displayOrder
   *
   * @param integer $displayOrder
   *
   * @return TbNetseaVendoraddress
   */
  public function setDisplayOrder($displayOrder)
  {
    $this->displayOrder = $displayOrder;

    return $this;
  }

  /**
   * Get displayOrder
   *
   * @return integer
   */
  public function getDisplayOrder()
  {
    return $this->displayOrder;
  }

  /**
   * Set sireCode
   *
   * @param string $sireCode
   *
   * @return TbNetseaVendoraddress
   */
  public function setSireCode($sireCode)
  {
    $this->sireCode = $sireCode;

    return $this;
  }

  /**
   * Get sireCode
   *
   * @return string
   */
  public function getSireCode()
  {
    return $this->sireCode;
  }
}

