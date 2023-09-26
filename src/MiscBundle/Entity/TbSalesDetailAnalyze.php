<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbSalesDetailAnalyze
 */
class TbSalesDetailAnalyze
{
  use ArrayTrait;

  /**
   * @var integer
   */
  private $voucher_number;

  /**
   * @var integer
   */
  private $line_number;


  /**
   * Set voucherNumber
   *
   * @param integer $voucherNumber
   *
   * @return TbSalesDetailAnalyze
   */
  public function setVoucherNumber($voucherNumber)
  {
    $this->voucher_number = $voucherNumber;

    return $this;
  }

  /**
   * Get voucherNumber
   *
   * @return integer
   */
  public function getVoucherNumber()
  {
    return $this->voucher_number;
  }

  /**
   * Set lineNumber
   *
   * @param integer $lineNumber
   *
   * @return TbSalesDetailAnalyze
   */
  public function setLineNumber($lineNumber)
  {
    $this->line_number = $lineNumber;

    return $this;
  }

  /**
   * Get lineNumber
   *
   * @return integer
   */
  public function getLineNumber()
  {
    return $this->line_number;
  }
  
  /**
   * @var string
   */
  private $order_number;

  /**
   * @var \DateTime
   */
  private $order_date;

  /**
   * @var \DateTime
   */
  private $shipping_date;

  /**
   * @var string
   */
  private $canceled = '0';

  /**
   * @var string
   */
  private $shop_name;

  /**
   * @var string
   */
  private $ne_mall_id;

  /**
   * @var string
   */
  private $shipping_method_code;

  /**
   * @var string
   */
  private $order_status = '';

  /**
   * @var string
   */
  private $special_note = '';
  
  /**
   * @var string
   */
  private $detail_canceled = '';

  /**
   * @var string
   */
  private $ne_syohin_syohin_code = '';

  /**
   * @var string
   */
  private $daihyo_syohin_code = '';

  /**
   * @var integer
   */
  private $ordered_num = 0;

  /**
   * @var integer
   */
  private $assigned_num = 0;

  /**
   * @var \DateTime
   */
  private $assigned_date;

  /**
   * @var string
   */
  private $customer_name = '';

  /**
   * @var \DateTime
   */
  private $shipping_planed_date;


  /**
   * Set order_number
   *
   * @param string $orderNumber
   * @return TbSalesDetailAnalyze
   */
  public function setOrderNumber($orderNumber)
  {
    $this->order_number = $orderNumber;

    return $this;
  }

  /**
   * Get order_number
   *
   * @return string 
   */
  public function getOrderNumber()
  {
    return $this->order_number;
  }

  /**
   * Set order_date
   *
   * @param \DateTime $orderDate
   * @return TbSalesDetailAnalyze
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
   * Set shipping_date
   *
   * @param \DateTime $shippingDate
   * @return TbSalesDetailAnalyze
   */
  public function setShippingDate($shippingDate)
  {
    $this->shipping_date = $shippingDate;

    return $this;
  }

  /**
   * Get shipping_date
   *
   * @return \DateTime 
   */
  public function getShippingDate()
  {
    return $this->shipping_date;
  }

  /**
   * Set canceled
   *
   * @param string $canceled
   * @return TbSalesDetailAnalyze
   */
  public function setCanceled($canceled)
  {
    $this->canceled = $canceled;

    return $this;
  }

  /**
   * Get canceled
   *
   * @return string 
   */
  public function getCanceled()
  {
    return $this->canceled;
  }

  /**
   * Set shop_name
   *
   * @param string $shopName
   * @return TbSalesDetailAnalyze
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
   * Set ne_mall_id
   *
   * @param string $neMallId
   * @return TbSalesDetailAnalyze
   */
  public function setNeMallId($neMallId)
  {
    $this->ne_mall_id = $neMallId;

    return $this;
  }

  /**
   * Get ne_mall_id
   *
   * @return string 
   */
  public function getNeMallId()
  {
    return $this->ne_mall_id;
  }

  /**
   * Set shipping_method_code
   *
   * @param string $shippingMethodCode
   * @return TbSalesDetailAnalyze
   */
  public function setShippingMethodCode($shippingMethodCode)
  {
    $this->shipping_method_code = $shippingMethodCode;

    return $this;
  }

  /**
   * Get shipping_method_code
   *
   * @return string 
   */
  public function getShippingMethodCode()
  {
    return $this->shipping_method_code;
  }

  /**
   * Set order_status
   *
   * @param string $orderStatus
   * @return TbSalesDetailAnalyze
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
   * Set special_note
   *
   * @param string $specialNote
   * @return TbSalesDetailAnalyze
   */
  public function setSpecialNote($specialNote)
  {
    $this->special_note = $specialNote;

    return $this;
  }

  /**
   * Get special_note
   *
   * @return string 
   */
  public function getSpecialNote()
  {
    return $this->special_note;
  }

  /**
   * Set detail_canceled
   *
   * @param string $detailCanceled
   * @return TbSalesDetailAnalyze
   */
  public function setDetailCanceled($detailCanceled)
  {
    $this->detail_canceled = $detailCanceled;

    return $this;
  }

  /**
   * Get detail_canceled
   *
   * @return string 
   */
  public function getDetailCanceled()
  {
    return $this->detail_canceled;
  }

  /**
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbSalesDetailAnalyze
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
   * Set daihyo_syohin_code
   *
   * @param string $daihyoSyohinCode
   * @return TbSalesDetailAnalyze
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyo_syohin_code
   *
   * @return string 
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set ordered_num
   *
   * @param integer $orderedNum
   * @return TbSalesDetailAnalyze
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
   * Set assigned_num
   *
   * @param integer $assignedNum
   * @return TbSalesDetailAnalyze
   */
  public function setAssignedNum($assignedNum)
  {
    $this->assigned_num = $assignedNum;

    return $this;
  }

  /**
   * Get assigned_num
   *
   * @return integer 
   */
  public function getAssignedNum()
  {
    return $this->assigned_num;
  }

  /**
   * Set assigned_date
   *
   * @param \DateTime $assignedDate
   * @return TbSalesDetailAnalyze
   */
  public function setAssignedDate($assignedDate)
  {
    $this->assigned_date = $assignedDate;

    return $this;
  }

  /**
   * Get assigned_date
   *
   * @return \DateTime 
   */
  public function getAssignedDate()
  {
    return $this->assigned_date;
  }

  /**
   * Set customer_name
   *
   * @param string $customerName
   * @return TbSalesDetailAnalyze
   */
  public function setCustomerName($customerName)
  {
    $this->customer_name = $customerName;

    return $this;
  }

  /**
   * Get customer_name
   *
   * @return string 
   */
  public function getCustomerName()
  {
    return $this->customer_name;
  }

  /**
   * Set shipping_planed_date
   *
   * @param \DateTime $shippingPlanedDate
   * @return TbSalesDetailAnalyze
   */
  public function setShippingPlanedDate($shippingPlanedDate)
  {
    $this->shipping_planed_date = $shippingPlanedDate;

    return $this;
  }

  /**
   * Get shipping_planed_date
   *
   * @return \DateTime 
   */
  public function getShippingPlanedDate()
  {
    return $this->shipping_planed_date;
  }

  /**
   * @var \DateTime
   */
  private $import_date;


  /**
   * Set import_date
   *
   * @param \DateTime $importDate
   * @return TbSalesDetailAnalyze
   */
  public function setImportDate($importDate)
  {
    $this->import_date = $importDate;

    return $this;
  }

  /**
   * Get import_date
   *
   * @return \DateTime
   */
  public function getImportDate()
  {
    return $this->import_date;
  }
  
  /**
   * @var string
   */
  private $shipping_method_name;


  /**
   * Set shipping_method_name
   *
   * @param string $shippingMethodName
   * @return TbSalesDetailAnalyze
   */
  public function setShippingMethodName($shippingMethodName)
  {
    $this->shipping_method_name = $shippingMethodName;

    return $this;
  }

  /**
   * Get shipping_method_name
   *
   * @return string 
   */
  public function getShippingMethodName()
  {
    return $this->shipping_method_name;
  }
}
