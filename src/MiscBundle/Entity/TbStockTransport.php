<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbStockTransportRepository;

/**
 * TbStockTransport
 */
class TbStockTransport
{
  use ArrayTrait;
  use FillTimestampTrait;

  /** @var TbStockTransportDetail[]  */
  public $details = [];


  /// 表示用文言 status
  public function getStatusDisplay() {
    $status = $this->getStatus();
    return isset(TbStockTransportRepository::$STATUS_DISPLAYS[$status]) ? TbStockTransportRepository::$STATUS_DISPLAYS[$status] : '';
  }

  /// 表示用文言 status
  public function getTransportName() {
    $code = $this->getTransportCode();
    return isset(TbStockTransportRepository::$TRANSPORT_CODE_DISPLAYS[$code]) ? TbStockTransportRepository::$TRANSPORT_CODE_DISPLAYS[$code] : '';
  }

  public function setDetails($details)
  {
    if (!$details) {
      $details = [];
    }

    $this->details = $details;
  }
  public function getDetails()
  {
    return $this->details;
  }

  // --------------------------------------------------
  // properties
  // --------------------------------------------------

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $account = '';

  /**
   * @var integer
   */
  private $status = 0;

  /**
   * @var \DateTime
   */
  private $date;

  /**
   * @var \DateTime
   */
  private $departure_date;

  /**
   * @var \DateTime
   */
  private $estimated_date;

  /**
   * @var \DateTime
   */
  private $arrival_date;

  /**
   * @var string
   */
  private $transport_code = '';

  /**
   * @var string
   */
  private $transport_number = '';

  /**
   * @var string
   */
  private $shipping_method = '';

  /**
   * @var string
   */
  private $shipping_number = '';

  /**
   * @var string
   */
  private $departure = '';

  /**
   * @var string
   */
  private $destination = '';

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
   * @return   int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set account
   *
   * @param string $account
   * @return TbStockTransport
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
   * Set status
   *
   * @param integer $status
   * @return TbStockTransport
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
   * Set date
   *
   * @param \DateTime $date
   * @return TbStockTransport
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
   * Set departure_date
   *
   * @param \DateTime $departureDate
   * @return TbStockTransport
   */
  public function setDepartureDate($departureDate)
  {
    $this->departure_date = $departureDate;

    return $this;
  }

  /**
   * Get departure_date
   *
   * @return \DateTime 
   */
  public function getDepartureDate()
  {
    return $this->departure_date;
  }

  /**
   * Set estimated_date
   *
   * @param \DateTime $estimatedDate
   * @return TbStockTransport
   */
  public function setEstimatedDate($estimatedDate)
  {
    $this->estimated_date = $estimatedDate;

    return $this;
  }

  /**
   * Get estimated_date
   *
   * @return \DateTime 
   */
  public function getEstimatedDate()
  {
    return $this->estimated_date;
  }

  /**
   * Set arrival_date
   *
   * @param \DateTime $arrivalDate
   * @return TbStockTransport
   */
  public function setArrivalDate($arrivalDate)
  {
    $this->arrival_date = $arrivalDate;

    return $this;
  }

  /**
   * Get arrival_date
   *
   * @return \DateTime 
   */
  public function getArrivalDate()
  {
    return $this->arrival_date;
  }

  /**
   * Set transport_code
   *
   * @param string $transportCode
   * @return TbStockTransport
   */
  public function setTransportCode($transportCode)
  {
    $this->transport_code = $transportCode;

    return $this;
  }

  /**
   * Get transport_code
   *
   * @return string 
   */
  public function getTransportCode()
  {
    return $this->transport_code;
  }

  /**
   * Set transport_number
   *
   * @param string $transportNumber
   * @return TbStockTransport
   */
  public function setTransportNumber($transportNumber)
  {
    $this->transport_number = $transportNumber;

    return $this;
  }

  /**
   * Get transport_number
   *
   * @return string 
   */
  public function getTransportNumber()
  {
    return $this->transport_number;
  }

  /**
   * Set shipping_method
   *
   * @param string $shippingMethod
   * @return TbStockTransport
   */
  public function setShippingMethod($shippingMethod)
  {
    $this->shipping_method = $shippingMethod;

    return $this;
  }

  /**
   * Get shipping_method
   *
   * @return string 
   */
  public function getShippingMethod()
  {
    return $this->shipping_method;
  }

  /**
   * Set shipping_number
   *
   * @param string $shippingNumber
   * @return TbStockTransport
   */
  public function setShippingNumber($shippingNumber)
  {
    $this->shipping_number = $shippingNumber;

    return $this;
  }

  /**
   * Get shipping_number
   *
   * @return string 
   */
  public function getShippingNumber()
  {
    return $this->shipping_number;
  }

  /**
   * Set departure
   *
   * @param string $departure
   * @return TbStockTransport
   */
  public function setDeparture($departure)
  {
    $this->departure = $departure;

    return $this;
  }

  /**
   * Get departure
   *
   * @return string 
   */
  public function getDeparture()
  {
    return $this->departure;
  }

  /**
   * Set destination
   *
   * @param string $destination
   * @return TbStockTransport
   */
  public function setDestination($destination)
  {
    $this->destination = $destination;

    return $this;
  }

  /**
   * Get destination
   *
   * @return string 
   */
  public function getDestination()
  {
    return $this->destination;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbStockTransport
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
   * @return TbStockTransport
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
   * @var \DateTime
   */
  private $picking_list_date;

  /**
   * @var integer
   */
  private $picking_list_number = 0;


  /**
   * Set picking_list_date
   *
   * @param \DateTime $pickingListDate
   * @return TbStockTransport
   */
  public function setPickingListDate($pickingListDate)
  {
    $this->picking_list_date = $pickingListDate;

    return $this;
  }

  /**
   * Get picking_list_date
   *
   * @return \DateTime 
   */
  public function getPickingListDate()
  {
    return $this->picking_list_date;
  }

  /**
   * Set picking_list_number
   *
   * @param integer $pickingListNumber
   * @return TbStockTransport
   */
  public function setPickingListNumber($pickingListNumber)
  {
    $this->picking_list_number = $pickingListNumber;

    return $this;
  }

  /**
   * Get picking_list_number
   *
   * @return integer 
   */
  public function getPickingListNumber()
  {
    return $this->picking_list_number;
  }

  /**
   * @var integer
   */
  private $departure_warehouse_id = 0;

  /**
   * @var integer
   */
  private $destination_warehouse_id = 0;


  /**
   * Set departure_warehouse_id
   *
   * @param integer $departureWarehouseId
   * @return TbStockTransport
   */
  public function setDepartureWarehouseId($departureWarehouseId)
  {
    $this->departure_warehouse_id = $departureWarehouseId;

    return $this;
  }

  /**
   * Get departure_warehouse_id
   *
   * @return integer 
   */
  public function getDepartureWarehouseId()
  {
    return $this->departure_warehouse_id;
  }

  /**
   * Set destination_warehouse_id
   *
   * @param integer $destinationWarehouseId
   * @return TbStockTransport
   */
  public function setDestinationWarehouseId($destinationWarehouseId)
  {
    $this->destination_warehouse_id = $destinationWarehouseId;

    return $this;
  }

  /**
   * Get destination_warehouse_id
   *
   * @return integer 
   */
  public function getDestinationWarehouseId()
  {
    return $this->destination_warehouse_id;
  }
}
