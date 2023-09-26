<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbRealShopPickingReport
 */
class TbRealShopPickingReport
{
  use ArrayTrait;

  /**
   * @var \DateTime
   */
  private $picking_date;

  /**
   * @var integer
   */
  private $number = 0;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var integer
   */
  private $free_stock = 0;

  /**
   * @var integer
   */
  private $ordered_num = 0;

  /**
   * @var integer
   */
  private $move_num = 0;

  /**
   * @var integer
   */
  private $status = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set picking_date
   *
   * @param \DateTime $pickingDate
   * @return TbRealShopPickingReport
   */
  public function setPickingDate($pickingDate)
  {
    $this->picking_date = $pickingDate;

    return $this;
  }

  /**
   * Get picking_date
   *
   * @return \DateTime 
   */
  public function getPickingDate()
  {
    return $this->picking_date;
  }

  /**
   * Set number
   *
   * @param integer $number
   * @return TbRealShopPickingReport
   */
  public function setNumber($number)
  {
    $this->number = $number;

    return $this;
  }

  /**
   * Get number
   *
   * @return integer 
   */
  public function getNumber()
  {
    return $this->number;
  }

  /**
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbRealShopPickingReport
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get ne_syohin_syohin_code
   *
   * @return string 
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->ne_syohin_syohin_code;
  }

  /**
   * Set free_stock
   *
   * @param integer $freeStock
   * @return TbRealShopPickingReport
   */
  public function setFreeStock($freeStock)
  {
    $this->free_stock = $freeStock;

    return $this;
  }

  /**
   * Get free_stock
   *
   * @return integer 
   */
  public function getFreeStock()
  {
    return $this->free_stock;
  }

  /**
   * Set ordered_num
   *
   * @param integer $orderedNum
   * @return TbRealShopPickingReport
   */
  public function setOrderedNum($orderedNum)
  {
    $this->ordered_num = $orderedNum;

    return $this;
  }

  /**
   * Get ordered_num
   *
   * @return integer 
   */
  public function getOrderedNum()
  {
    return $this->ordered_num;
  }

  /**
   * Set move_num
   *
   * @param integer $moveNum
   * @return TbRealShopPickingReport
   */
  public function setMoveNum($moveNum)
  {
    $this->move_num = $moveNum;

    return $this;
  }

  /**
   * Get move_num
   *
   * @return integer 
   */
  public function getMoveNum()
  {
    return $this->move_num;
  }

  /**
   * Set status
   *
   * @param integer $status
   * @return TbRealShopPickingReport
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get status
   *
   * @return integer 
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbRealShopPickingReport
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
   * @return TbRealShopPickingReport
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
  private $create_account_id = 0;

  /**
   * @var string
   */
  private $create_account_name = '';


  /**
   * Set create_account_id
   *
   * @param integer $createAccountId
   * @return TbRealShopPickingReport
   */
  public function setCreateAccountId($createAccountId)
  {
    $this->create_account_id = $createAccountId;

    return $this;
  }

  /**
   * Get create_account_id
   *
   * @return integer 
   */
  public function getCreateAccountId()
  {
    return $this->create_account_id;
  }

  /**
   * Set create_account_name
   *
   * @param string $createAccountName
   * @return TbRealShopPickingReport
   */
  public function setCreateAccountName($createAccountName)
  {
    $this->create_account_name = $createAccountName;

    return $this;
  }

  /**
   * Get create_account_name
   *
   * @return string 
   */
  public function getCreateAccountName()
  {
    return $this->create_account_name;
  }
}
