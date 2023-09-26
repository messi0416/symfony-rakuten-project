<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbSalesVoucherDeliveryInfo
 */
class TbSalesVoucherDeliveryInfo
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $voucher_number;

  /**
   * @var string
   */
  private $receive_order_hope_delivery_time_slot_id;

  /**
   * @var string
   */
  private $receive_order_temperature_id;

  /**
   * @var string
   */
  private $receive_order_business_office_stop_id;

  /**
   * @var string
   */
  private $receive_order_business_office_name;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set voucher_number
   *
   * @param string $voucherNumber
   * @return TbSalesVoucherDeliveryInfo
   */
  public function setVoucherNumber($voucherNumber)
  {
    $this->voucher_number = $voucherNumber;

    return $this;
  }

  /**
   * Get voucher_number
   *
   * @return string 
   */
  public function getVoucherNumber()
  {
    return $this->voucher_number;
  }

  /**
   * Set receive_order_hope_delivery_time_slot_id
   *
   * @param string $receiveOrderHopeDeliveryTimeSlotId
   * @return TbSalesVoucherDeliveryInfo
   */
  public function setReceiveOrderHopeDeliveryTimeSlotId($receiveOrderHopeDeliveryTimeSlotId)
  {
    $this->receive_order_hope_delivery_time_slot_id = $receiveOrderHopeDeliveryTimeSlotId;

    return $this;
  }

  /**
   * Get receive_order_hope_delivery_time_slot_id
   *
   * @return string 
   */
  public function getReceiveOrderHopeDeliveryTimeSlotId()
  {
    return $this->receive_order_hope_delivery_time_slot_id;
  }

  /**
   * Set receive_order_temperature_id
   *
   * @param string $receiveOrderTemperatureId
   * @return TbSalesVoucherDeliveryInfo
   */
  public function setReceiveOrderTemperatureId($receiveOrderTemperatureId)
  {
    $this->receive_order_temperature_id = $receiveOrderTemperatureId;

    return $this;
  }

  /**
   * Get receive_order_temperature_id
   *
   * @return string 
   */
  public function getReceiveOrderTemperatureId()
  {
    return $this->receive_order_temperature_id;
  }

  /**
   * Set receive_order_business_office_stop_id
   *
   * @param string $receiveOrderBusinessOfficeStopId
   * @return TbSalesVoucherDeliveryInfo
   */
  public function setReceiveOrderBusinessOfficeStopId($receiveOrderBusinessOfficeStopId)
  {
    $this->receive_order_business_office_stop_id = $receiveOrderBusinessOfficeStopId;

    return $this;
  }

  /**
   * Get receive_order_business_office_stop_id
   *
   * @return string 
   */
  public function getReceiveOrderBusinessOfficeStopId()
  {
    return $this->receive_order_business_office_stop_id;
  }

  /**
   * Set receive_order_business_office_name
   *
   * @param string $receiveOrderBusinessOfficeName
   * @return TbSalesVoucherDeliveryInfo
   */
  public function setReceiveOrderBusinessOfficeName($receiveOrderBusinessOfficeName)
  {
    $this->receive_order_business_office_name = $receiveOrderBusinessOfficeName;

    return $this;
  }

  /**
   * Get receive_order_business_office_name
   *
   * @return string 
   */
  public function getReceiveOrderBusinessOfficeName()
  {
    return $this->receive_order_business_office_name;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbSalesVoucherDeliveryInfo
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
   * @return TbSalesVoucherDeliveryInfo
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
