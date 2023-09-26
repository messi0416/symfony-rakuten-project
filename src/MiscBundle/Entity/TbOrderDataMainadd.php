<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbOrderDataMainadd
 */
class TbOrderDataMainadd
{
  use ArrayTrait;

  /**
   * @var integer
   */
  private $voucher_number;

  /**
   * @var string
   */
  private $order_status = '';

  /**
   * @var string
   */
  private $payment_method = '';

  /**
   * @var string
   */
  private $payment_status = '';

  /**
   * @var \DateTime
   */
  private $sun_payment_reminder;

  /**
   * @var \DateTime
   */
  private $order_date;

  /**
   * @var integer
   */
  private $purchase_quantity = 0;


  /**
   * Set voucher_number
   *
   * @param integer $voucherNumber
   * @return TbOrderDataMainadd
   */
  public function setVoucherNumber($voucherNumber)
  {
    $this->voucher_number = $voucherNumber;

    return $this;
  }

  /**
   * Get voucher_number
   *
   * @return integer 
   */
  public function getVoucherNumber()
  {
    return $this->voucher_number;
  }

  /**
   * Set order_status
   *
   * @param string $orderStatus
   * @return TbOrderDataMainadd
   */
  public function setOrderStatus($orderStatus)
  {
    $this->order_status = $orderStatus;

    return $this;
  }

  /**
   * Get order_status
   *
   * @return string 
   */
  public function getOrderStatus()
  {
    return $this->order_status;
  }

  /**
   * Set payment_method
   *
   * @param string $paymentMethod
   * @return TbOrderDataMainadd
   */
  public function setPaymentMethod($paymentMethod)
  {
    $this->payment_method = $paymentMethod;

    return $this;
  }

  /**
   * Get payment_method
   *
   * @return string 
   */
  public function getPaymentMethod()
  {
    return $this->payment_method;
  }

  /**
   * Set payment_status
   *
   * @param string $paymentStatus
   * @return TbOrderDataMainadd
   */
  public function setPaymentStatus($paymentStatus)
  {
    $this->payment_status = $paymentStatus;

    return $this;
  }

  /**
   * Get payment_status
   *
   * @return string 
   */
  public function getPaymentStatus()
  {
    return $this->payment_status;
  }

  /**
   * Set sun_payment_reminder
   *
   * @param \DateTime $sunPaymentReminder
   * @return TbOrderDataMainadd
   */
  public function setSunPaymentReminder($sunPaymentReminder)
  {
    $this->sun_payment_reminder = $sunPaymentReminder;

    return $this;
  }

  /**
   * Get sun_payment_reminder
   *
   * @return \DateTime 
   */
  public function getSunPaymentReminder()
  {
    return $this->sun_payment_reminder;
  }

  /**
   * Set order_date
   *
   * @param \DateTime $orderDate
   * @return TbOrderDataMainadd
   */
  public function setOrderDate($orderDate)
  {
    $this->order_date = $orderDate;

    return $this;
  }

  /**
   * Get order_date
   *
   * @return \DateTime 
   */
  public function getOrderDate()
  {
    return $this->order_date;
  }

  /**
   * Set purchase_quantity
   *
   * @param integer $purchaseQuantity
   * @return TbOrderDataMainadd
   */
  public function setPurchaseQuantity($purchaseQuantity)
  {
    $this->purchase_quantity = $purchaseQuantity;

    return $this;
  }

  /**
   * Get purchase_quantity
   *
   * @return integer 
   */
  public function getPurchaseQuantity()
  {
    return $this->purchase_quantity;
  }
}
