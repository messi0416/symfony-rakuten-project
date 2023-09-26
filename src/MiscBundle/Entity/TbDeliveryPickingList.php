<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbDeliveryPickingListRepository;

/**
 * TbDeliveryPickingList
 */
class TbDeliveryPickingList
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   */
  public function getPickingStatusLabelCssClass()
  {
    $cssClass = '';
    switch ($this->getPickingStatus()) {
      case TbDeliveryPickingListRepository::PICKING_STATUS_OK:
        $cssClass = 'label-success';
        break;
      case TbDeliveryPickingListRepository::PICKING_STATUS_INCORRECT:
        $cssClass = 'label-warning';
        break;
      case TbDeliveryPickingListRepository::PICKING_STATUS_PASS:
        $cssClass = 'label-danger';
        break;
    }

    return $cssClass;
  }

  /**
   */
  public function getPickingStatusDisplay()
  {
    return isset(TbDeliveryPickingListRepository::$PICKING_STATUS_DISPLAYS[$this->getPickingStatus()])
         ? TbDeliveryPickingListRepository::$PICKING_STATUS_DISPLAYS[$this->getPickingStatus()]
         : '';
  }

  /**
   * ステータス判定： OK
   */
  public function isPickingStatusOk()
  {
    return $this->getPickingStatus() === TbDeliveryPickingListRepository::PICKING_STATUS_OK;
  }

  /**
   * ステータス判定： NG
   */
  public function isPickingStatusNg()
  {
    return $this->getPickingStatus() === TbDeliveryPickingListRepository::PICKING_STATUS_INCORRECT;
  }

  /**
   * ステータス判定： NG
   */
  public function isPickingStatusPass()
  {
    return $this->getPickingStatus() === TbDeliveryPickingListRepository::PICKING_STATUS_PASS;
  }

  // ----------------------------

  /**
   * @var int
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $date;

  /**
   * @var string
   */
  private $syohin_code;

  /**
   * @var string
   */
  private $syohin_name;

  /**
   * @var int
   */
  private $free_stock = 0;

  /**
   * @var int
   */
  private $stock = 0;

  /**
   * @var string
   */
  private $type_number = '';

  /**
   * @var string
   */
  private $jan = '';

  /**
   * @var string
   */
  private $sire_code = '';

  /**
   * @var string
   */
  private $sire_name;

  /**
   * @var string
   */
  private $file_hash = '';

  /**
   * @var int
   */
  private $number = 0;

  /**
   * @var string
   */
  private $account = '';

  /**
   * @var int
   */
  private $picking_status = 0;

  /**
   * @var \DateTime
   */
  private $created;


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
   * Set date
   *
   * @param \DateTime $date
   *
   * @return TbDeliveryPickingList
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
   * Set syohinCode
   *
   * @param string $syohinCode
   *
   * @return TbDeliveryPickingList
   */
  public function setSyohinCode($syohinCode)
  {
    $this->syohin_code = $syohinCode;

    return $this;
  }

  /**
   * Get syohinCode
   *
   * @return string
   */
  public function getSyohinCode()
  {
    return $this->syohin_code;
  }

  /**
   * Set syohinName
   *
   * @param string $syohinName
   *
   * @return TbDeliveryPickingList
   */
  public function setSyohinName($syohinName)
  {
    $this->syohin_name = $syohinName;

    return $this;
  }

  /**
   * Get syohinName
   *
   * @return string
   */
  public function getSyohinName()
  {
    return $this->syohin_name;
  }

  /**
   * Set freeStock
   *
   * @param int $freeStock
   *
   * @return TbDeliveryPickingList
   */
  public function setFreeStock($freeStock)
  {
    $this->free_stock = $freeStock;

    return $this;
  }

  /**
   * Get freeStock
   *
   * @return int
   */
  public function getFreeStock()
  {
    return $this->free_stock;
  }

  /**
   * Set stock
   *
   * @param int $stock
   *
   * @return TbDeliveryPickingList
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
   * Set typeNumber
   *
   * @param string $typeNumber
   *
   * @return TbDeliveryPickingList
   */
  public function setTypeNumber($typeNumber)
  {
    $this->type_number = $typeNumber;

    return $this;
  }

  /**
   * Get typeNumber
   *
   * @return string
   */
  public function getTypeNumber()
  {
    return $this->type_number;
  }

  /**
   * Set jan
   *
   * @param string $jan
   *
   * @return TbDeliveryPickingList
   */
  public function setJan($jan)
  {
    $this->jan = $jan;

    return $this;
  }

  /**
   * Get jan
   *
   * @return string
   */
  public function getJan()
  {
    return $this->jan;
  }

  /**
   * Set sireCode
   *
   * @param string $sireCode
   *
   * @return TbDeliveryPickingList
   */
  public function setSireCode($sireCode)
  {
    $this->sire_code = $sireCode;

    return $this;
  }

  /**
   * Get sireCode
   *
   * @return string
   */
  public function getSireCode()
  {
    return $this->sire_code;
  }

  /**
   * Set sireName
   *
   * @param \DateTime $sireName
   *
   * @return TbDeliveryPickingList
   */
  public function setSireName($sireName)
  {
    $this->sire_name = $sireName;

    return $this;
  }

  /**
   * Get sireName
   *
   * @return \DateTime
   */
  public function getSireName()
  {
    return $this->sire_name;
  }

  /**
   * Set fileHash
   *
   * @param string $fileHash
   *
   * @return TbDeliveryPickingList
   */
  public function setFileHash($fileHash)
  {
    $this->file_hash = $fileHash;

    return $this;
  }

  /**
   * Get fileHash
   *
   * @return string
   */
  public function getFileHash()
  {
    return $this->file_hash;
  }

  /**
   * Set number
   *
   * @param int $number
   *
   * @return TbDeliveryPickingList
   */
  public function setNumber($number)
  {
    $this->number = $number;

    return $this;
  }

  /**
   * Get number
   *
   * @return int
   */
  public function getNumber()
  {
    return $this->number;
  }

  /**
   * Set account
   *
   * @param string $account
   *
   * @return TbDeliveryPickingList
   */
  public function setAccount($account)
  {
    $this->account = $account;

    return $this;
  }

  /**
   * Get account
   *
   * @return string
   */
  public function getAccount()
  {
    return $this->account;
  }

  /**
   * Set pickingStatus
   *
   * @param int $pickingStatus
   *
   * @return TbDeliveryPickingList
   */
  public function setPickingStatus($pickingStatus)
  {
    $this->picking_status = $pickingStatus;

    return $this;
  }

  /**
   * Get pickingStatus
   *
   * @return int
   */
  public function getPickingStatus()
  {
    return $this->picking_status;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbDeliveryPickingList
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
   * @var \MiscBundle\Entity\TbProductchoiceitems
   */
  private $choiceItem;


  /**
   * Set choiceItem
   *
   * @param \MiscBundle\Entity\TbProductchoiceitems $choiceItem
   *
   * @return TbDeliveryPickingList
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
   * @var \DateTime
   */
  private $datetime;


  /**
   * Set datetime
   *
   * @param \DateTime $datetime
   *
   * @return TbDeliveryPickingList
   */
  public function setDatetime($datetime)
  {
    $this->datetime = $datetime;

    return $this;
  }

  /**
   * Get datetime
   *
   * @return \DateTime
   */
  public function getDatetime()
  {
    return $this->datetime;
  }
  /**
   * @var string
   */
  private $location_code = 0;


  /**
   * Set locationCode
   *
   * @param string $locationCode
   *
   * @return TbDeliveryPickingList
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
   * @var int
   */
  private $item_num = 0;


  /**
   * Set itemNum
   *
   * @param int $itemNum
   *
   * @return TbDeliveryPickingList
   */
  public function setItemNum($itemNum)
  {
    $this->item_num = $itemNum;

    return $this;
  }

  /**
   * Get itemNum
   *
   * @return int
   */
  public function getItemNum()
  {
    return $this->item_num;
  }

  /**
   * @var string
   */
  private $update_account_name = '';

  /**
   * @var \DateTime
   */
  private $updated;

  /**
   * Set updateAccountName
   *
   * @param string $updateAccountName
   *
   * @return TbDeliveryPickingList
   */
  public function setUpdateAccountName($updateAccountName)
  {
    $this->update_account_name = $updateAccountName;

    return $this;
  }

  /**
   * Get updateAccountName
   *
   * @return string
   */
  public function getUpdateAccountName()
  {
    return $this->update_account_name;
  }

  /**
   * Set updated
   *
   * @param \DateTime $updated
   *
   * @return TbDeliveryPickingList
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
     * @var int
     */
    private $picking_account_id = 0;

    /**
     * @var string
     */
    private $picking_account_name = '';

    /**
     * @var int
     */
    private $update_account_id = 0;


    /**
     * Set pickingAccountId
     *
     * @param int $pickingAccountId
     *
     * @return TbDeliveryPickingList
     */
    public function setPickingAccountId($pickingAccountId)
    {
        $this->picking_account_id = $pickingAccountId;

        return $this;
    }

    /**
     * Get pickingAccountId
     *
     * @return int
     */
    public function getPickingAccountId()
    {
        return $this->picking_account_id;
    }

    /**
     * Set pickingAccountName
     *
     * @param string $pickingAccountName
     *
     * @return TbDeliveryPickingList
     */
    public function setPickingAccountName($pickingAccountName)
    {
        $this->picking_account_name = $pickingAccountName;

        return $this;
    }

    /**
     * Get pickingAccountName
     *
     * @return string
     */
    public function getPickingAccountName()
    {
        return $this->picking_account_name;
    }

    /**
     * Set updateAccountId
     *
     * @param int $updateAccountId
     *
     * @return TbDeliveryPickingList
     */
    public function setUpdateAccountId($updateAccountId)
    {
        $this->update_account_id = $updateAccountId;

        return $this;
    }

    /**
     * Get updateAccountId
     *
     * @return int
     */
    public function getUpdateAccountId()
    {
        return $this->update_account_id;
    }
    /**
     * @var int
     */
    private $picking_order = 0;

    /**
     * @var string
     */
    private $current_location = '';


    /**
     * Set pickingOrder
     *
     * @param int $pickingOrder
     *
     * @return TbDeliveryPickingList
     */
    public function setPickingOrder($pickingOrder)
    {
        $this->picking_order = $pickingOrder;

        return $this;
    }

    /**
     * Get pickingOrder
     *
     * @return int
     */
    public function getPickingOrder()
    {
        return $this->picking_order;
    }

    /**
     * Set currentLocation
     *
     * @param string $currentLocation
     *
     * @return TbDeliveryPickingList
     */
    public function setCurrentLocation($currentLocation)
    {
        $this->current_location = $currentLocation;

        return $this;
    }

    /**
     * Get currentLocation
     *
     * @return string
     */
    public function getCurrentLocation()
    {
        return $this->current_location;
    }
    /**
     * @var integer
     */
    private $warehouse_id = '1';


    /**
     * Set warehouse_id
     *
     * @param integer $warehouseId
     * @return TbDeliveryPickingList
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
     * @var \MiscBundle\Entity\TbWarehouse
     */
    private $warehouse;


    /**
     * Set warehouse
     *
     * @param \MiscBundle\Entity\TbWarehouse $warehouse
     * @return TbDeliveryPickingList
     */
    public function setWarehouse(\MiscBundle\Entity\TbWarehouse $warehouse = null)
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * Get warehouse
     *
     * @return \MiscBundle\Entity\TbWarehouse 
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }
    
    /**
   * @var \DateTime
   */
  private $old_date;

  /**
   * @var int
   */
  private $old_number = 0;
  
    /**
   * Set old_date
   *
   * @param \DateTime $old_date
   *
   * @return TbDeliveryPickingList
   */
  public function setOldDate($old_date)
  {
    $this->old_date = $old_date;

    return $this;
  }

  /**
   * Get old_date
   *
   * @return \DateTime
   */
  public function getOldDate()
  {
    return $this->old_date;
  }
  
  /**
   * Get old_date
   *
   * @return \DateTime
   */
  public function getOldDateShort()
  {
    return is_null($this->old_date)
         ? ''
         : $this->old_date->format('m/d');
  }
  
    /**
   * Set old_number
   *
   * @param int $number
   *
   * @return TbDeliveryPickingList
   */
  public function setOldNumber($old_number)
  {
    $this->old_number = $old_number;

    return $this;
  }

  /**
   * Get old_number
   *
   * @return int
   */
  public function getOldNumber()
  {
    return $this->old_number;
  }


}
