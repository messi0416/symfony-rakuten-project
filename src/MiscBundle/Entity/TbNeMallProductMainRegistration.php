<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbNeMallProductMainRegistration
 */
class TbNeMallProductMainRegistration
{
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $daihyoSyohinCode;

  /**
   * @var integer
   */
  private $neMallId;

  /**
   * @var string
   */
  private $shopDaihyoSyohinCode;

  /**
   * @var boolean
   */
  private $registrationFlg;


  /**
   * Get id
   *
   * @return integer 
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   * @return TbNeMallProductMainRegistration
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyoSyohinCode = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string 
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyoSyohinCode;
  }

  /**
   * Set neMallId
   *
   * @param integer $neMallId
   * @return TbNeMallProductMainRegistration
   */
  public function setNeMallId($neMallId)
  {
    $this->neMallId = $neMallId;

    return $this;
  }

  /**
   * Get neMallId
   *
   * @return integer 
   */
  public function getNeMallId()
  {
    return $this->neMallId;
  }

  /**
   * Set shopDaihyoSyohinCode
   *
   * @param string $shopDaihyoSyohinCode
   * @return TbNeMallProductMainRegistration
   */
  public function setShopDaihyoSyohinCode($shopDaihyoSyohinCode)
  {
    $this->shopDaihyoSyohinCode = $shopDaihyoSyohinCode;

    return $this;
  }

  /**
   * Get shopDaihyoSyohinCode
   *
   * @return string 
   */
  public function getShopDaihyoSyohinCode()
  {
    return $this->shopDaihyoSyohinCode;
  }

  /**
   * Set registrationFlg
   *
   * @param boolean $registrationFlg
   * @return TbNeMallProductMainRegistration
   */
  public function setRegistrationFlg($registrationFlg)
  {
    $this->registrationFlg = $registrationFlg;

    return $this;
  }

  /**
   * Get registrationFlg
   *
   * @return boolean 
   */
  public function getRegistrationFlg()
  {
    return $this->registrationFlg;
  }
}
