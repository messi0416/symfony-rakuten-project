<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbVendormasterdata
 */
class TbVendormasterdata
{
  use ArrayTrait;

  // ===================================
  // properties
  // ===================================

  /**
   * @var string
   */
  private $sireCode;


  /**
   * Get sireCode
   *
   * @return string
   */
  public function getSireCode()
  {
    return $this->sireCode;
  }
  /**
   * @var string
   */
  private $sireName;

  /**
   * @var int
   */
  private $costRate = 0;


  /**
   * Set sireName
   *
   * @param string $sireName
   *
   * @return TbVendormasterdata
   */
  public function setSireName($sireName)
  {
    $this->sireName = $sireName;

    return $this;
  }

  /**
   * Get sireName
   *
   * @return string
   */
  public function getSireName()
  {
    return $this->sireName;
  }

  /**
   * Set costRate
   *
   * @param int $costRate
   *
   * @return TbVendormasterdata
   */
  public function setCostRate($costRate)
  {
    $this->costRate = $costRate;

    return $this;
  }

  /**
   * Get costRate
   *
   * @return int
   */
  public function getCostRate()
  {
    return $this->costRate;
  }
  /**
   * @var string
   */
  private $remainingOrderUrlString;


  /**
   * Set remainingOrderUrlString
   *
   * @param string $remainingOrderUrlString
   *
   * @return TbVendormasterdata
   */
  public function setRemainingOrderUrlString($remainingOrderUrlString)
  {
    $this->remainingOrderUrlString = $remainingOrderUrlString;

    return $this;
  }

  /**
   * Get remainingOrderUrlString
   *
   * @return string
   */
  public function getRemainingOrderUrlString()
  {
    return $this->remainingOrderUrlString;
  }
  /**
   * @var int
   */
  private $status = 0;

  /**
   * @var int
   */
  private $displayOrder = 0;


  /**
   * Set status
   *
   * @param int $status
   *
   * @return TbVendormasterdata
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get status
   *
   * @return int
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set displayOrder
   *
   * @param int $displayOrder
   *
   * @return TbVendormasterdata
   */
  public function setDisplayOrder($displayOrder)
  {
    $this->displayOrder = $displayOrder;

    return $this;
  }

  /**
   * Get displayOrder
   *
   * @return int
   */
  public function getDisplayOrder()
  {
    return $this->displayOrder;
  }
}
