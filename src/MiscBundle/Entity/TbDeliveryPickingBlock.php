<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbDeliveryPickingBlock
 */
class TbDeliveryPickingBlock
{
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $warehouse_id;

  /**
   * @var string
   */
  private $block_code;

  /**
   * @var integer
   */
  private $display_order;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set warehouse_id
   *
   * @param integer $warehouseId
   * @return TbDeliveryPickingBlock
   */
  public function setWarehouseId($warehouseId)
  {
    $this->warehouse_id = $warehouseId;

    return $this;
  }

  /**
   * Get warehouse_id
   *
   * @return integer 
   */
  public function getWarehouseId()
  {
    return $this->warehouse_id;
  }

  /**
   * Set block_code
   *
   * @param string $blockCode
   * @return TbDeliveryPickingBlock
   */
  public function setBlockCode($blockCode)
  {
    $this->block_code = $blockCode;

    return $this;
  }

  /**
   * Get block_code
   *
   * @return string 
   */
  public function getBlockCode()
  {
    return $this->block_code;
  }

  /**
   * Set display_order
   *
   * @param integer $displayOrder
   * @return TbDeliveryPickingBlock
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
   * Set created
   *
   * @param \DateTime $created
   * @return TbDeliveryPickingBlock
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
   * @return TbDeliveryPickingBlock
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
