<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbDeliveryChangeShippingMethodRepository;

/**
 * TbDeliveryChangeShippingMethod
 */
class TbDeliveryChangeShippingMethod
{
  use ArrayTrait;
  use FillTimestampTrait;

  public function getStatusDisplay()
  {
    return isset(TbDeliveryChangeShippingMethodRepository::$STATUS_DISPLAYS[$this->getStatus()])
      ? TbDeliveryChangeShippingMethodRepository::$STATUS_DISPLAYS[$this->getStatus()]
      : '';
  }


  /**
   * @var integer
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $date;

  /**
   * @var string
   */
  private $voucher_number;

  /**
   * @var string
   */
  private $purchaser;

  /**
   * @var string
   */
  private $addressee;

  /**
   * @var string
   */
  private $shop_name;

  /**
   * @var string
   */
  private $shipping_method;

  /**
   * @var string
   */
  private $new_shipping_method;

  /**
   * @var string
   */
  private $receive_order_delivery_id;

  /**
   * @var \DateTime
   */
  private $shipping_method_changed;

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
   * @return TbDeliveryChangeShippingMethod
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
   * Set voucher_number
   *
   * @param string $voucherNumber
   * @return TbDeliveryChangeShippingMethod
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
   * Set purchaser
   *
   * @param string $purchaser
   * @return TbDeliveryChangeShippingMethod
   */
  public function setPurchaser($purchaser)
  {
    $this->purchaser = $purchaser;

    return $this;
  }

  /**
   * Get purchaser
   *
   * @return string 
   */
  public function getPurchaser()
  {
    return $this->purchaser;
  }

  /**
   * Set addressee
   *
   * @param string $addressee
   * @return TbDeliveryChangeShippingMethod
   */
  public function setAddressee($addressee)
  {
    $this->addressee = $addressee;

    return $this;
  }

  /**
   * Get addressee
   *
   * @return string 
   */
  public function getAddressee()
  {
    return $this->addressee;
  }

  /**
   * Set shop_name
   *
   * @param string $shopName
   * @return TbDeliveryChangeShippingMethod
   */
  public function setShopName($shopName)
  {
    $this->shop_name = $shopName;

    return $this;
  }

  /**
   * Get shop_name
   *
   * @return string 
   */
  public function getShopName()
  {
    return $this->shop_name;
  }

  /**
   * Set shipping_method
   *
   * @param string $shippingMethod
   * @return TbDeliveryChangeShippingMethod
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
   * Set new_shipping_method
   *
   * @param string $newShippingMethod
   * @return TbDeliveryChangeShippingMethod
   */
  public function setNewShippingMethod($newShippingMethod)
  {
    $this->new_shipping_method = $newShippingMethod;

    return $this;
  }

  /**
   * Get new_shipping_method
   *
   * @return string 
   */
  public function getNewShippingMethod()
  {
    return $this->new_shipping_method;
  }

  /**
   * Set receive_order_delivery_id
   *
   * @param string $receiveOrderDeliveryId
   * @return TbDeliveryChangeShippingMethod
   */
  public function setReceiveOrderDeliveryId($receiveOrderDeliveryId)
  {
    $this->receive_order_delivery_id = $receiveOrderDeliveryId;

    return $this;
  }

  /**
   * Get receive_order_delivery_id
   *
   * @return string 
   */
  public function getReceiveOrderDeliveryId()
  {
    return $this->receive_order_delivery_id;
  }

  /**
   * Set shipping_method_changed
   *
   * @param \DateTime $shippingMethodChanged
   * @return TbDeliveryChangeShippingMethod
   */
  public function setShippingMethodChanged($shippingMethodChanged)
  {
    $this->shipping_method_changed = $shippingMethodChanged;

    return $this;
  }

  /**
   * Get shipping_method_changed
   *
   * @return \DateTime 
   */
  public function getShippingMethodChanged()
  {
    return $this->shipping_method_changed;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbDeliveryChangeShippingMethod
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
   * @return TbDeliveryChangeShippingMethod
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
  private $new_receive_order_delivery_id;


  /**
   * Set new_receive_order_delivery_id
   *
   * @param string $newReceiveOrderDeliveryId
   * @return TbDeliveryChangeShippingMethod
   */
  public function setNewReceiveOrderDeliveryId($newReceiveOrderDeliveryId)
  {
    $this->new_receive_order_delivery_id = $newReceiveOrderDeliveryId;

    return $this;
  }

  /**
   * Get new_receive_order_delivery_id
   *
   * @return string 
   */
  public function getNewReceiveOrderDeliveryId()
  {
    return $this->new_receive_order_delivery_id;
  }
  /**
   * @var integer
   */
  private $status;


  /**
   * Set status
   *
   * @param integer $status
   * @return TbDeliveryChangeShippingMethod
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
   * @var string
   */
  private $current_shipping_method;

  /**
   * @var string
   */
  private $current_receive_order_delivery_id;


  /**
   * Set current_shipping_method
   *
   * @param string $currentShippingMethod
   * @return TbDeliveryChangeShippingMethod
   */
  public function setCurrentShippingMethod($currentShippingMethod)
  {
    $this->current_shipping_method = $currentShippingMethod;

    return $this;
  }

  /**
   * Get current_shipping_method
   *
   * @return string 
   */
  public function getCurrentShippingMethod()
  {
    return $this->current_shipping_method;
  }

  /**
   * Set current_receive_order_delivery_id
   *
   * @param string $currentReceiveOrderDeliveryId
   * @return TbDeliveryChangeShippingMethod
   */
  public function setCurrentReceiveOrderDeliveryId($currentReceiveOrderDeliveryId)
  {
    $this->current_receive_order_delivery_id = $currentReceiveOrderDeliveryId;

    return $this;
  }

  /**
   * Get current_receive_order_delivery_id
   *
   * @return string 
   */
  public function getCurrentReceiveOrderDeliveryId()
  {
    return $this->current_receive_order_delivery_id;
  }

}
