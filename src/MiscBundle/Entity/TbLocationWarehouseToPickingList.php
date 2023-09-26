<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbMainproductsRepository;

/**
 * TbLocationWarehouseToPickingList
 */
class TbLocationWarehouseToPickingList
{
  use FillTimestampTrait;
  use ArrayTrait;

  /**
   * 画像URL取得（サムネイル用）
   */
  public function getImageUrl($parentPath = '')
  {
    return TbMainproductsRepository::createImageUrl($this->getPictDirectory(), $this->getPictFilename(), $parentPath);
  }


  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var int
   */
  private $location_id = 0;

  /**
   * @var string
   */
  private $location_code = '';

  /**
   * @var int
   */
  private $position = 0;

  /**
   * @var int
   */
  private $stock = 0;

  /**
   * @var int
   */
  private $move_num = 0;

  /**
   * @var int
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
   * @var \MiscBundle\Entity\TbProductchoiceitems
   */
  private $choiceItem;


  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   *
   * @return TbLocationWarehouseToPickingList
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
   * @return TbLocationWarehouseToPickingList
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
   * Set locationCode
   *
   * @param string $locationCode
   *
   * @return TbLocationWarehouseToPickingList
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
   * Set position
   *
   * @param int $position
   *
   * @return TbLocationWarehouseToPickingList
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
   * Set stock
   *
   * @param int $stock
   *
   * @return TbLocationWarehouseToPickingList
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
   * Set moveNum
   *
   * @param int $moveNum
   *
   * @return TbLocationWarehouseToPickingList
   */
  public function setMoveNum($moveNum)
  {
    $this->move_num = $moveNum;

    return $this;
  }

  /**
   * Get moveNum
   *
   * @return int
   */
  public function getMoveNum()
  {
    return $this->move_num;
  }

  /**
   * Set status
   *
   * @param int $status
   *
   * @return TbLocationWarehouseToPickingList
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
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbLocationWarehouseToPickingList
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
   * @return TbLocationWarehouseToPickingList
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
   * @return TbLocationWarehouseToPickingList
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
     * @var string
     */
    private $pict_directory = '';

    /**
     * @var string
     */
    private $pict_filename = '';


    /**
     * Set pictDirectory
     *
     * @param string $pictDirectory
     *
     * @return TbLocationWarehouseToPickingList
     */
    public function setPictDirectory($pictDirectory)
    {
        $this->pict_directory = $pictDirectory;

        return $this;
    }

    /**
     * Get pictDirectory
     *
     * @return string
     */
    public function getPictDirectory()
    {
        return $this->pict_directory;
    }

    /**
     * Set pictFilename
     *
     * @param string $pictFilename
     *
     * @return TbLocationWarehouseToPickingList
     */
    public function setPictFilename($pictFilename)
    {
        $this->pict_filename = $pictFilename;

        return $this;
    }

    /**
     * Get pictFilename
     *
     * @return string
     */
    public function getPictFilename()
    {
        return $this->pict_filename;
    }
}
