<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbWarehouseStockMovePickingListRepository;

/**
 * TbWarehouseStockMovePickingList
 */
class TbWarehouseStockMovePickingList
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   */
  public function getStatusLabelCssClass()
  {
    $cssClass = '';
    switch ($this->getStatus()) {
      case TbWarehouseStockMovePickingListRepository::PICKING_STATUS_OK:
        $cssClass = 'label-success';
        break;
      case TbWarehouseStockMovePickingListRepository::PICKING_STATUS_INCORRECT:
        $cssClass = 'label-warning';
        break;
      case TbWarehouseStockMovePickingListRepository::PICKING_STATUS_PASS:
        $cssClass = 'label-danger';
        break;
    }

    return $cssClass;
  }

  public function getStatusDisplay()
  {
    $result = isset(TbWarehouseStockMovePickingListRepository::$PICKING_STATUS_DISPLAYS[$this->getStatus()])
            ? TbWarehouseStockMovePickingListRepository::$PICKING_STATUS_DISPLAYS[$this->getStatus()]
            : '';

    // OK の場合は、ロケーション作成チェック
    if ($this->getStatus() === TbWarehouseStockMovePickingListRepository::PICKING_STATUS_OK) {
      if (!strlen($this->getNewLocationCode())) {
        $result = $result = TbWarehouseStockMovePickingListRepository::INDEX_STATUS_UNLOCATED;
      }
    }

    return $result;
  }

  /**
   * ステータス判定： OK
   */
  public function isStatusOk()
  {
    return $this->getStatus() === TbWarehouseStockMovePickingListRepository::PICKING_STATUS_OK;
  }

  /**
   * ステータス判定： NG
   */
  public function isStatusNg()
  {
    return $this->getStatus() === TbWarehouseStockMovePickingListRepository::PICKING_STATUS_INCORRECT;
  }

  /**
   * ステータス判定： NG
   */
  public function isStatusPass()
  {
    return $this->getStatus() === TbWarehouseStockMovePickingListRepository::PICKING_STATUS_PASS;
  }

  // ----------------------------


  /**
   * @var integer
   */
  private $id;

  /**
   * @var integer
   */
  private $warehouse_id = '1';

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
   * @var integer
   */
  private $picking_order = 0;

  /**
   * @var string
   */
  private $current_location = '';

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
   * Set warehouse_id
   *
   * @param integer $warehouseId
   * @return TbWarehouseStockMovePickingList
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
   * Set date
   *
   * @param \DateTime $date
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * Set picking_order
   *
   * @param integer $pickingOrder
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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

  /**
   * Set pict_directory
   *
   * @param string $pictDirectory
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @return TbWarehouseStockMovePickingList
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
   * @var string
   */
  private $new_location_code = '';


  /**
   * Set new_location_code
   *
   * @param string $newLocationCode
   * @return TbWarehouseStockMovePickingList
   */
  public function setNewLocationCode($newLocationCode)
  {
    $this->new_location_code = $newLocationCode;

    return $this;
  }

  /**
   * Get new_location_code
   *
   * @return string 
   */
  public function getNewLocationCode()
  {
    return $this->new_location_code;
  }
  /**
   * @var integer
   */
  private $account_id = 0;

  /**
   * @var string
   */
  private $account_name = '';

  /**
   * @var integer
   */
  private $picking_account_id = 0;

  /**
   * @var string
   */
  private $picking_account_name = '';


  /**
   * Set account_id
   *
   * @param integer $accountId
   * @return TbWarehouseStockMovePickingList
   */
  public function setAccountId($accountId)
  {
    $this->account_id = $accountId;

    return $this;
  }

  /**
   * Get account_id
   *
   * @return integer 
   */
  public function getAccountId()
  {
    return $this->account_id;
  }

  /**
   * Set account_name
   *
   * @param string $accountName
   * @return TbWarehouseStockMovePickingList
   */
  public function setAccountName($accountName)
  {
    $this->account_name = $accountName;

    return $this;
  }

  /**
   * Get account_name
   *
   * @return string 
   */
  public function getAccountName()
  {
    return $this->account_name;
  }

  /**
   * Set picking_account_id
   *
   * @param integer $pickingAccountId
   * @return TbWarehouseStockMovePickingList
   */
  public function setPickingAccountId($pickingAccountId)
  {
    $this->picking_account_id = $pickingAccountId;

    return $this;
  }

  /**
   * Get picking_account_id
   *
   * @return integer 
   */
  public function getPickingAccountId()
  {
    return $this->picking_account_id;
  }

  /**
   * Set picking_account_name
   *
   * @param string $pickingAccountName
   * @return TbWarehouseStockMovePickingList
   */
  public function setPickingAccountName($pickingAccountName)
  {
    $this->picking_account_name = $pickingAccountName;

    return $this;
  }

  /**
   * Get picking_account_name
   *
   * @return string 
   */
  public function getPickingAccountName()
  {
    return $this->picking_account_name;
  }
  /**
   * @var string
   */
  private $type = 'warehouse';


  /**
   * Set type
   *
   * @param string $type
   * @return TbWarehouseStockMovePickingList
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
     * @var integer
     */
    private $picked_num = 0;

    /**
     * @var integer
     */
    private $shortage = 0;


    /**
     * Set picked_num
     *
     * @param integer $pickedNum
     * @return TbWarehouseStockMovePickingList
     */
    public function setPickedNum($pickedNum)
    {
        $this->picked_num = $pickedNum;

        return $this;
    }

    /**
     * Get picked_num
     *
     * @return integer 
     */
    public function getPickedNum()
    {
        return $this->picked_num;
    }

    /**
     * Set shortage
     *
     * @param integer $shortage
     * @return TbWarehouseStockMovePickingList
     */
    public function setShortage($shortage)
    {
        $this->shortage = $shortage;

        return $this;
    }

    /**
     * Get shortage
     *
     * @return integer 
     */
    public function getShortage()
    {
        return $this->shortage;
    }
}
