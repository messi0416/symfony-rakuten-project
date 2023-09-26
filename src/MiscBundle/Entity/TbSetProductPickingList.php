<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbSetProductPickingList
 */
class TbSetProductPickingList
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $date;

  /**
   * @var integer
   */
  private $number;

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
   * @var string
   */
  private $pict_directory = '';

  /**
   * @var string
   */
  private $pict_filename = '';

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
   * Set date
   *
   * @param \DateTime $date
   * @return TbSetProductPickingList
   */
  public function setDate($date)
  {
    $this->date = $date;

    return $this;
  }

  /**
   * Get date
   *
   * @return \DateTime 
   */
  public function getDate()
  {
    return $this->date;
  }

  /**
   * Set number
   *
   * @param integer $number
   * @return TbSetProductPickingList
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
   * @return TbSetProductPickingList
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
   * @return TbSetProductPickingList
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
   * @return TbSetProductPickingList
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
   * @return TbSetProductPickingList
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
   * @return TbSetProductPickingList
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
   * Set pict_directory
   *
   * @param string $pictDirectory
   * @return TbSetProductPickingList
   */
  public function setPictDirectory($pictDirectory)
  {
    $this->pict_directory = $pictDirectory;

    return $this;
  }

  /**
   * Get pict_directory
   *
   * @return string 
   */
  public function getPictDirectory()
  {
    return $this->pict_directory;
  }

  /**
   * Set pict_filename
   *
   * @param string $pictFilename
   * @return TbSetProductPickingList
   */
  public function setPictFilename($pictFilename)
  {
    $this->pict_filename = $pictFilename;

    return $this;
  }

  /**
   * Get pict_filename
   *
   * @return string 
   */
  public function getPictFilename()
  {
    return $this->pict_filename;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbSetProductPickingList
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
   * @return TbSetProductPickingList
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
  private $picking_order = 0;

  /**
   * @var string
   */
  private $current_location = '';


  /**
   * Set picking_order
   *
   * @param integer $pickingOrder
   * @return TbSetProductPickingList
   */
  public function setPickingOrder($pickingOrder)
  {
    $this->picking_order = $pickingOrder;

    return $this;
  }

  /**
   * Get picking_order
   *
   * @return integer 
   */
  public function getPickingOrder()
  {
    return $this->picking_order;
  }

  /**
   * Set current_location
   *
   * @param string $currentLocation
   * @return TbSetProductPickingList
   */
  public function setCurrentLocation($currentLocation)
  {
    $this->current_location = $currentLocation;

    return $this;
  }

  /**
   * Get current_location
   *
   * @return string 
   */
  public function getCurrentLocation()
  {
    return $this->current_location;
  }
}
