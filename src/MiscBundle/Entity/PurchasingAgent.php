<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * PurchasingAgent
 */
class PurchasingAgent
{
  use ArrayTrait;
  use FillTimestampTrait;

  /** 依頼先ID：全依頼先   */
  const AGNENT_ID_All = -1;
  /** 依頼先ID：おタオバオ様 */
  const AGENT_ID_TNEKO = 16;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $name = '';

  /**
   * @var integer
   */
  private $display_order = 0;

  /**
   * @var string
   */
  private $sire_code = '';

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


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
   * Set name
   *
   * @param string $name
   * @return PurchasingAgent
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name
   *
   * @return string 
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set display_order
   *
   * @param integer $displayOrder
   * @return PurchasingAgent
   */
  public function setDisplayOrder($displayOrder)
  {
    $this->display_order = $displayOrder;

    return $this;
  }

  /**
   * Get display_order
   *
   * @return integer 
   */
  public function getDisplayOrder()
  {
    return $this->display_order;
  }

  /**
   * Set sire_code
   *
   * @param string $sireCode
   * @return PurchasingAgent
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
   * Set created
   *
   * @param \DateTime $created
   * @return PurchasingAgent
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
   * @return PurchasingAgent
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
   * @var string
   */
  private $login_name = '';


  /**
   * Set login_name
   *
   * @param string $loginName
   * @return PurchasingAgent
   */
  public function setLoginName($loginName)
  {
    $this->login_name = $loginName;

    return $this;
  }

  /**
   * Get login_name
   *
   * @return string 
   */
  public function getLoginName()
  {
    return $this->login_name;
  }
}
