<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbNeMallOrder
 */
class TbNeMallOrder
{
  /**
   * @var integer
   */
  private $shop_code;

  /**
   * @var integer
   */
  private $mall_order_id;

  /**
   * @var string
   */
  private $mall_order_number;

  /**
   * @var \DateTime
   */
  private $order_date;

  /**
   * @var string
   */
  private $zipcode;

  /**
   * @var string
   */
  private $payment_method;

  /**
   * @var string
   */
  private $shipping_method;

  /**
   * @var integer
   */
  private $products_total;

  /**
   * @var integer
   */
  private $tax;

  /**
   * @var integer
   */
  private $shipping_charge;

  /**
   * @var integer
   */
  private $handling_charge;

  /**
   * @var integer
   */
  private $point;

  /**
   * @var integer
   */
  private $other_charge;

  /**
   * @var integer
   */
  private $total;

  /**
   * @var string
   */
  private $specified_shippiing_time;

  /**
   * @var \DateTime
   */
  private $specified_shippiing_date;

  /**
   * @var string
   */
  private $working_comment;

  /**
   * @var string
   */
  private $comment;

  /**
   * @var string
   */
  private $item_name;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var string
   */
  private $price;

  /**
   * @var string
   */
  private $quantity;

  /**
   * @var integer
   */
  private $voucher_number;

  /**
   * @var integer
   */
  private $line_number;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $imported;

  /**
   * @var string
   */
  private $converted;

  /**
   * @var string
   */
  private $downloaded;


  /**
   * Set shop_code
   *
   * @param integer $shopCode
   * @return TbNeMallOrder
   */
  public function setShopCode($shopCode)
  {
    $this->shop_code = $shopCode;

    return $this;
  }

  /**
   * Get shop_code
   *
   * @return integer 
   */
  public function getShopCode()
  {
    return $this->shop_code;
  }

  /**
   * Set mall_order_id
   *
   * @param integer $mallOrderId
   * @return TbNeMallOrder
   */
  public function setMallOrderId($mallOrderId)
  {
    $this->mall_order_id = $mallOrderId;

    return $this;
  }

  /**
   * Get mall_order_id
   *
   * @return integer 
   */
  public function getMallOrderId()
  {
    return $this->mall_order_id;
  }

  /**
   * Set mall_order_number
   *
   * @param string $mallOrderNumber
   * @return TbNeMallOrder
   */
  public function setMallOrderNumber($mallOrderNumber)
  {
    $this->mall_order_number = $mallOrderNumber;

    return $this;
  }

  /**
   * Get mall_order_number
   *
   * @return string 
   */
  public function getMallOrderNumber()
  {
    return $this->mall_order_number;
  }

  /**
   * Set order_date
   *
   * @param \DateTime $orderDate
   * @return TbNeMallOrder
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
   * Set zipcode
   *
   * @param string $zipcode
   * @return TbNeMallOrder
   */
  public function setZipcode($zipcode)
  {
    $this->zipcode = $zipcode;

    return $this;
  }

  /**
   * Get zipcode
   *
   * @return string 
   */
  public function getZipcode()
  {
    return $this->zipcode;
  }

  /**
   * Set payment_method
   *
   * @param string $paymentMethod
   * @return TbNeMallOrder
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
   * Set shipping_method
   *
   * @param string $shippingMethod
   * @return TbNeMallOrder
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
   * Set products_total
   *
   * @param integer $productsTotal
   * @return TbNeMallOrder
   */
  public function setProductsTotal($productsTotal)
  {
    $this->products_total = $productsTotal;

    return $this;
  }

  /**
   * Get products_total
   *
   * @return integer 
   */
  public function getProductsTotal()
  {
    return $this->products_total;
  }

  /**
   * Set tax
   *
   * @param integer $tax
   * @return TbNeMallOrder
   */
  public function setTax($tax)
  {
    $this->tax = $tax;

    return $this;
  }

  /**
   * Get tax
   *
   * @return integer 
   */
  public function getTax()
  {
    return $this->tax;
  }

  /**
   * Set shipping_charge
   *
   * @param integer $shippingCharge
   * @return TbNeMallOrder
   */
  public function setShippingCharge($shippingCharge)
  {
    $this->shipping_charge = $shippingCharge;

    return $this;
  }

  /**
   * Get shipping_charge
   *
   * @return integer 
   */
  public function getShippingCharge()
  {
    return $this->shipping_charge;
  }

  /**
   * Set handling_charge
   *
   * @param integer $handlingCharge
   * @return TbNeMallOrder
   */
  public function setHandlingCharge($handlingCharge)
  {
    $this->handling_charge = $handlingCharge;

    return $this;
  }

  /**
   * Get handling_charge
   *
   * @return integer 
   */
  public function getHandlingCharge()
  {
    return $this->handling_charge;
  }

  /**
   * Set point
   *
   * @param integer $point
   * @return TbNeMallOrder
   */
  public function setPoint($point)
  {
    $this->point = $point;

    return $this;
  }

  /**
   * Get point
   *
   * @return integer 
   */
  public function getPoint()
  {
    return $this->point;
  }

  /**
   * Set other_charge
   *
   * @param integer $otherCharge
   * @return TbNeMallOrder
   */
  public function setOtherCharge($otherCharge)
  {
    $this->other_charge = $otherCharge;

    return $this;
  }

  /**
   * Get other_charge
   *
   * @return integer 
   */
  public function getOtherCharge()
  {
    return $this->other_charge;
  }

  /**
   * Set total
   *
   * @param integer $total
   * @return TbNeMallOrder
   */
  public function setTotal($total)
  {
    $this->total = $total;

    return $this;
  }

  /**
   * Get total
   *
   * @return integer 
   */
  public function getTotal()
  {
    return $this->total;
  }

  /**
   * Set specified_shippiing_time
   *
   * @param string $specifiedShippiingTime
   * @return TbNeMallOrder
   */
  public function setSpecifiedShippiingTime($specifiedShippiingTime)
  {
    $this->specified_shippiing_time = $specifiedShippiingTime;

    return $this;
  }

  /**
   * Get specified_shippiing_time
   *
   * @return string 
   */
  public function getSpecifiedShippiingTime()
  {
    return $this->specified_shippiing_time;
  }

  /**
   * Set specified_shippiing_date
   *
   * @param \DateTime $specifiedShippiingDate
   * @return TbNeMallOrder
   */
  public function setSpecifiedShippiingDate($specifiedShippiingDate)
  {
    $this->specified_shippiing_date = $specifiedShippiingDate;

    return $this;
  }

  /**
   * Get specified_shippiing_date
   *
   * @return \DateTime 
   */
  public function getSpecifiedShippiingDate()
  {
    return $this->specified_shippiing_date;
  }

  /**
   * Set working_comment
   *
   * @param string $workingComment
   * @return TbNeMallOrder
   */
  public function setWorkingComment($workingComment)
  {
    $this->working_comment = $workingComment;

    return $this;
  }

  /**
   * Get working_comment
   *
   * @return string 
   */
  public function getWorkingComment()
  {
    return $this->working_comment;
  }

  /**
   * Set comment
   *
   * @param string $comment
   * @return TbNeMallOrder
   */
  public function setComment($comment)
  {
    $this->comment = $comment;

    return $this;
  }

  /**
   * Get comment
   *
   * @return string 
   */
  public function getComment()
  {
    return $this->comment;
  }

  /**
   * Set item_name
   *
   * @param string $itemName
   * @return TbNeMallOrder
   */
  public function setItemName($itemName)
  {
    $this->item_name = $itemName;

    return $this;
  }

  /**
   * Get item_name
   *
   * @return string 
   */
  public function getItemName()
  {
    return $this->item_name;
  }

  /**
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbNeMallOrder
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
   * Set price
   *
   * @param string $price
   * @return TbNeMallOrder
   */
  public function setPrice($price)
  {
    $this->price = $price;

    return $this;
  }

  /**
   * Get price
   *
   * @return string 
   */
  public function getPrice()
  {
    return $this->price;
  }

  /**
   * Set quantity
   *
   * @param string $quantity
   * @return TbNeMallOrder
   */
  public function setQuantity($quantity)
  {
    $this->quantity = $quantity;

    return $this;
  }

  /**
   * Get quantity
   *
   * @return string 
   */
  public function getQuantity()
  {
    return $this->quantity;
  }

  /**
   * Set voucher_number
   *
   * @param integer $voucherNumber
   * @return TbNeMallOrder
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
   * Set line_number
   *
   * @param integer $lineNumber
   * @return TbNeMallOrder
   */
  public function setLineNumber($lineNumber)
  {
    $this->line_number = $lineNumber;

    return $this;
  }

  /**
   * Get line_number
   *
   * @return integer 
   */
  public function getLineNumber()
  {
    return $this->line_number;
  }

  /**
   * Set daihyo_syohin_code
   *
   * @param string $daihyoSyohinCode
   * @return TbNeMallOrder
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
   * Set imported
   *
   * @param string $imported
   * @return TbNeMallOrder
   */
  public function setImported($imported)
  {
    $this->imported = $imported;

    return $this;
  }

  /**
   * Get imported
   *
   * @return string 
   */
  public function getImported()
  {
    return $this->imported;
  }

  /**
   * Set converted
   *
   * @param string $converted
   * @return TbNeMallOrder
   */
  public function setConverted($converted)
  {
    $this->converted = $converted;

    return $this;
  }

  /**
   * Get converted
   *
   * @return string 
   */
  public function getConverted()
  {
    return $this->converted;
  }

  /**
   * Set downloaded
   *
   * @param string $downloaded
   * @return TbNeMallOrder
   */
  public function setDownloaded($downloaded)
  {
    $this->downloaded = $downloaded;

    return $this;
  }

  /**
   * Get downloaded
   *
   * @return string 
   */
  public function getDownloaded()
  {
    return $this->downloaded;
  }

}
