<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductLocation
 * 商品ロケーション
 */
class TbProductLocation
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * 倉庫ロケーションか
   * @return bool
   */
  public function isWarehouse()
  {
    return boolval(preg_match('/^P/', $this->getLocation()->getLocationCode()));
  }



  // ---------------------------------
  
  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var int
   */
  private $location_id;

  /**
   * @var int
   */
  private $stock;

  /**
   * @var int
   */
  private $position;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;

  /**
   * @var \MiscBundle\Entity\TbProductchoiceitems
   */
  private $choiceItem;


  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   *
   * @return TbProductLocation
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get neSyohinSyohinCode
   *
   * @return string
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->ne_syohin_syohin_code;
  }

  /**
   * Set locationId
   *
   * @param int $locationId
   *
   * @return TbProductLocation
   */
  public function setLocationId($locationId)
  {
    $this->location_id = $locationId;

    return $this;
  }

  /**
   * Get locationId
   *
   * @return int
   */
  public function getLocationId()
  {
    return $this->location_id;
  }

  /**
   * Set stock
   *
   * @param int $stock
   *
   * @return TbProductLocation
   */
  public function setStock($stock)
  {
    $this->stock = $stock;

    return $this;
  }

  /**
   * Get stock
   *
   * @return int
   */
  public function getStock()
  {
    return $this->stock;
  }

  /**
   * Set position
   *
   * @param int $position
   *
   * @return TbProductLocation
   */
  public function setPosition($position)
  {
    $this->position = $position;

    return $this;
  }

  /**
   * Get position
   *
   * @return int
   */
  public function getPosition()
  {
    return $this->position;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbProductLocation
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
   * @return TbProductLocation
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
   * Set choiceItem
   *
   * @param \MiscBundle\Entity\TbProductchoiceitems $choiceItem
   *
   * @return TbProductLocation
   */
  public function setChoiceItem(\MiscBundle\Entity\TbProductchoiceitems $choiceItem = null)
  {
    $this->choiceItem = $choiceItem;

    return $this;
  }

  /**
   * Get choiceItem
   *
   * @return \MiscBundle\Entity\TbProductchoiceitems
   */
  public function getChoiceItem()
  {
    return $this->choiceItem;
  }

  /**
   * @var \MiscBundle\Entity\TbLocation
   */
  private $location;

  /**
   * Set location
   *
   * @param \MiscBundle\Entity\TbLocation $location
   *
   * @return TbProductLocation
   */
  public function setLocation(\MiscBundle\Entity\TbLocation $location = null)
  {
    $this->location = $location;

    return $this;
  }

  /**
   * Get location
   *
   * @return \MiscBundle\Entity\TbLocation
   */
  public function getLocation()
  {
    return $this->location;
  }
}
