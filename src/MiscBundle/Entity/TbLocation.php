<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbLocation
 */
class TbLocation
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var int
   */
  private $id;

  /**
   * @var int
   */
  private $warehouse_id = 1;

  /**
   * @var string
   */
  private $location_code;

  /**
   * @var string
   */
  private $type = '';

  /**
   * @var \DateTime
   */
  private $auto_location_date;

  /**
   * @var int
   */
  private $auto_location_number = 0;

  /**
   * @var boolean
   */
  private $move_furuichi_warehouse_flg;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;

  /**
   * Constructor
   */
  public function __construct()
  {
  }

  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set warehouseId
   *
   * @param string $warehouseId
   *
   * @return TbLocation
   */
  public function setWarehouseId($warehouseId)
  {
    $this->warehouse_id = $warehouseId;

    return $this;
  }

  /**
   * Get warehouseId
   *
   * @return string
   */
  public function getWarehouseId()
  {
    return $this->warehouse_id;
  }  


  /**
   * Set locationCode
   *
   * @param string $locationCode
   *
   * @return TbLocation
   */
  public function setLocationCode($locationCode)
  {
    $this->location_code = $locationCode;

    return $this;
  }

  /**
   * Get locationCode
   *
   * @return string
   */
  public function getLocationCode()
  {
    return $this->location_code;
  }

  /**
   * Set type
   *
   * @param string $type
   *
   * @return TbLocation
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /**
   * Get type
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set autoLocationDate
   *
   * @param \DateTime $autoLocationDate
   *
   * @return TbLocation
   */
  public function setAutoLocationDate($autoLocationDate)
  {
    $this->auto_location_date = $autoLocationDate;

    return $this;
  }

  /**
   * Get autoLocationDate
   *
   * @return \DateTime
   */
  public function getAutoLocationDate()
  {
    return $this->auto_location_date;
  }

  /**
   * Set autoLocationNumber
   *
   * @param int $autoLocationNumber
   *
   * @return TbLocation
   */
  public function setAutoLocationNumber($autoLocationNumber)
  {
    $this->auto_location_number = $autoLocationNumber;

    return $this;
  }

  /**
   * Get moveFuruichiWarehouseFlg
   *
   * @return int
   */
  public function getMoveFuruichiWarehouseFlg()
  {
    return $this->move_furuichi_warehouse_flg;
  }

  /**
   * Set moveFuruichiWarehouseFlg
   *
   * @param int $moveFuruichiWarehouseFlg
   *
   * @return TbLocation
   */
  public function setMoveFuruichiWarehouseFlg($moveFuruichiWarehouseFlg)
  {
      $this->move_furuichi_warehouse_flg = $moveFuruichiWarehouseFlg;

      return $this;
  }

  /**
   * Get autoLocationNumber
   *
   * @return int
   */
  public function getAutoLocationNumber()
  {
      return $this->auto_location_number;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbLocation
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
   *
   * @return TbLocation
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
   * @var \Doctrine\Common\Collections\Collection
   */
  private $productLocations;


  /**
   * Add productLocation
   *
   * @param \MiscBundle\Entity\TbProductLocation $productLocation
   *
   * @return TbLocation
   */
  public function addProductLocation(\MiscBundle\Entity\TbProductLocation $productLocation)
  {
    $this->productLocations[] = $productLocation;

    return $this;
  }

  /**
   * Remove productLocation
   *
   * @param \MiscBundle\Entity\TbProductLocation $productLocation
   *
   * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
   */
  public function removeProductLocation(\MiscBundle\Entity\TbProductLocation $productLocation)
  {
    return $this->productLocations->removeElement($productLocation);
  }

  /**
   * Get productLocations
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getProductLocations()
  {
    return $this->productLocations;
  }

}
