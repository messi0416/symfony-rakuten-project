<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbNeMallProductRegistration
 */
class TbNeMallProductSkuRegistration
{
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $neSyohinSyohinCode;

  /**
   * @var integer
   */
  private $neMallId;

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
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   * @return TbNeMallProductRegistration
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->neSyohinSyohinCode = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get neSyohinSyohinCode
   *
   * @return string 
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->neSyohinSyohinCode;
  }

  /**
   * Set neMallId
   *
   * @param integer $neMallId
   * @return TbNeMallProductRegistration
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
   * Set registrationFlg
   *
   * @param boolean $registrationFlg
   * @return TbNeMallProductRegistration
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
