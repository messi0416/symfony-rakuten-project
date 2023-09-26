<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbRealShopProductStock
 */
class TbRealShopProductStock
{
  use ArrayTrait;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var string
   */
  private $daihyo_syohin_code = '';

  /**
   * @var integer
   */
  private $stock = 0;

  /**
   * @var integer
   */
  private $order_num = 0;

  /**
   * @var \DateTime
   */
  private $last_ordered;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


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
   * Set daihyo_syohin_code
   *
   * @param string $daihyoSyohinCode
   * @return TbRealShopProductStock
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
      $this->daihyo_syohin_code = $daihyoSyohinCode;

      return $this;
  }

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
   * Set stock
   *
   * @param integer $stock
   * @return TbRealShopProductStock
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
   * Set order_num
   *
   * @param integer $orderNum
   * @return TbRealShopProductStock
   */
  public function setOrderNum($orderNum)
  {
      $this->order_num = $orderNum;

      return $this;
  }

  /**
   * Get order_num
   *
   * @return integer 
   */
  public function getOrderNum()
  {
      return $this->order_num;
  }

  /**
   * Set last_ordered
   *
   * @param \DateTime $lastOrdered
   * @return TbRealShopProductStock
   */
  public function setLastOrdered($lastOrdered)
  {
      $this->last_ordered = $lastOrdered;

      return $this;
  }

  /**
   * Get last_ordered
   *
   * @return \DateTime 
   */
  public function getLastOrdered()
  {
      return $this->last_ordered;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbRealShopProductStock
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
   * @return TbRealShopProductStock
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
   * @ORM\PrePersist
   */
  public function fillTimestamps()
  {
      // Add your code here
  }
}
