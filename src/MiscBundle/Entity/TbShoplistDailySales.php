<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShoplistDailySales
 */
class TbShoplistDailySales
{
  use FillTimestampTrait;

  /**
   * @var \DateTime
   */
  private $orderDate;

  /**
   * @var string
   */
  private $neSyohinSyohinCode;

  /**
   * @var string
   */
  private $daihyoSyohinCode;

  /**
   * @var string
   */
  private $janCode;

  /**
   * @var string
   */
  private $syohinTitle;

  /**
   * @var integer
   */
  private $numTotal;

  /**
   * @var integer
   */
  private $numNormal;

  /**
   * @var integer
   */
  private $numSpeedBin;

  /**
   * @var integer
   */
  private $salesAmount;

  /**
   * @var string
   */
  private $rate;

  /**
   * @var string
   */
  private $color;

  /**
   * @var string
   */
  private $size;

  /**
   * @var integer
   */
  private $stock;

  /**
   * @var \DateTime
   */
  private $salesStartDate;

  /**
   * @var integer
   */
  private $costTanka;

  /**
   * @var string
   */
  private $systemUsageCostRatio;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set orderDate
   *
   * @param \DateTime $orderDate
   * @return TbShoplistDailySales
   */
  public function setOrderDate($orderDate)
  {
    $this->orderDate = $orderDate;

    return $this;
  }

  /**
   * Get orderDate
   *
   * @return \DateTime 
   */
  public function getOrderDate()
  {
    return $this->orderDate;
  }

  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   * @return TbShoplistDailySales
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
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   * @return TbShoplistDailySales
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
   * Set janCode
   *
   * @param string $janCode
   * @return TbShoplistDailySales
   */
  public function setJanCode($janCode)
  {
    $this->janCode = $janCode;

    return $this;
  }

  /**
   * Get janCode
   *
   * @return string 
   */
  public function getJanCode()
  {
    return $this->janCode;
  }

  /**
   * Set syohinTitle
   *
   * @param string $syohinTitle
   * @return TbShoplistDailySales
   */
  public function setSyohinTitle($syohinTitle)
  {
    $this->syohinTitle = $syohinTitle;

    return $this;
  }

  /**
   * Get syohinTitle
   *
   * @return string 
   */
  public function getSyohinTitle()
  {
    return $this->syohinTitle;
  }

  /**
   * Set numTotal
   *
   * @param integer $numTotal
   * @return TbShoplistDailySales
   */
  public function setNumTotal($numTotal)
  {
    $this->numTotal = $numTotal;

    return $this;
  }

  /**
   * Get numTotal
   *
   * @return integer 
   */
  public function getNumTotal()
  {
    return $this->numTotal;
  }

  /**
   * Set numNormal
   *
   * @param integer $numNormal
   * @return TbShoplistDailySales
   */
  public function setNumNormal($numNormal)
  {
    $this->numNormal = $numNormal;

    return $this;
  }

  /**
   * Get numNormal
   *
   * @return integer 
   */
  public function getNumNormal()
  {
    return $this->numNormal;
  }

  /**
   * Set numSpeedBin
   *
   * @param integer $numSpeedBin
   * @return TbShoplistDailySales
   */
  public function setNumSpeedBin($numSpeedBin)
  {
    $this->numSpeedBin = $numSpeedBin;

    return $this;
  }

  /**
   * Get numSpeedBin
   *
   * @return integer 
   */
  public function getNumSpeedBin()
  {
    return $this->numSpeedBin;
  }

  /**
   * Set salesAmount
   *
   * @param integer $salesAmount
   * @return TbShoplistDailySales
   */
  public function setSalesAmount($salesAmount)
  {
    $this->salesAmount = $salesAmount;

    return $this;
  }

  /**
   * Get salesAmount
   *
   * @return integer 
   */
  public function getSalesAmount()
  {
    return $this->salesAmount;
  }

  /**
   * Set rate
   *
   * @param string $rate
   * @return TbShoplistDailySales
   */
  public function setRate($rate)
  {
    $this->rate = $rate;

    return $this;
  }

  /**
   * Get rate
   *
   * @return string 
   */
  public function getRate()
  {
    return $this->rate;
  }

  /**
   * Set color
   *
   * @param string $color
   * @return TbShoplistDailySales
   */
  public function setColor($color)
  {
    $this->color = $color;

    return $this;
  }

  /**
   * Get color
   *
   * @return string 
   */
  public function getColor()
  {
    return $this->color;
  }

  /**
   * Set size
   *
   * @param string $size
   * @return TbShoplistDailySales
   */
  public function setSize($size)
  {
    $this->size = $size;

    return $this;
  }

  /**
   * Get size
   *
   * @return string 
   */
  public function getSize()
  {
    return $this->size;
  }

  /**
   * Set stock
   *
   * @param integer $stock
   * @return TbShoplistDailySales
   */
  public function setStock($stock)
  {
    $this->stock = $stock;

    return $this;
  }

  /**
   * Get stock
   *
   * @return integer 
   */
  public function getStock()
  {
    return $this->stock;
  }

  /**
   * Set salesStartDate
   *
   * @param \DateTime $salesStartDate
   * @return TbShoplistDailySales
   */
  public function setSalesStartDate($salesStartDate)
  {
    $this->salesStartDate = $salesStartDate;

    return $this;
  }

  /**
   * Get salesStartDate
   *
   * @return \DateTime 
   */
  public function getSalesStartDate()
  {
    return $this->salesStartDate;
  }

  /**
   * Set costTanka
   *
   * @param integer $costTanka
   * @return TbShoplistDailySales
   */
  public function setCostTanka($costTanka)
  {
    $this->costTanka = $costTanka;

    return $this;
  }

  /**
   * Get costTanka
   *
   * @return integer 
   */
  public function getCostTanka()
  {
    return $this->costTanka;
  }

  /**
   * Set systemUsageCostRatio
   *
   * @param string $systemUsageCostRatio
   * @return TbShoplistDailySales
   */
  public function setSystemUsageCostRatio($systemUsageCostRatio)
  {
    $this->systemUsageCostRatio = $systemUsageCostRatio;

    return $this;
  }

  /**
   * Get systemUsageCostRatio
   *
   * @return string 
   */
  public function getSystemUsageCostRatio()
  {
    return $this->systemUsageCostRatio;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbShoplistDailySales
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
   * @return TbShoplistDailySales
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
}
