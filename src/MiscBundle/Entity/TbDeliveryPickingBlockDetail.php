<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbDeliveryPickingBlockDetail
 */
class TbDeliveryPickingBlockDetail
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
   * @var string
   */
  private $pattern;

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
   * @return TbDeliveryPickingBlockDetail
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
   * @return TbDeliveryPickingBlockDetail
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
   * Set pattern
   *
   * @param string $pattern
   * @return TbDeliveryPickingBlockDetail
   */
  public function setPattern($pattern)
  {
    $this->pattern = $pattern;

    return $this;
  }

  /**
   * Get pattern
   *
   * @return string 
   */
  public function getPattern()
  {
    return $this->pattern;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbDeliveryPickingBlockDetail
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
   * @return TbDeliveryPickingBlockDetail
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
