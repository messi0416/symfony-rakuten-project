<?php

namespace MiscBundle\Entity;

/**
 * VSalesVoucher
 */
class VSalesVoucher extends TbSalesDetail
{
  /**
   * @var TbSalesDetail[]
   */
  private $details = [];

  /**
   * 配送情報の補完データ
   * @var TbSalesVoucherDeliveryInfo
   */
  private $deliveryInfo = null;

  /**
   * 明細セット＆初期化
   * @param $details
   */
  public function setDetails($details)
  {
    $this->details = $details;
    $this->initializeVoucherInfo();
  }

  /**
   * 明細取得
   */
  public function getDetails()
  {
    return $this->details;
  }

  /**
   * 配列化
   * @param string $keyFormat
   * @param string $baseClassName
   * @return array
   */
  public function toScalarArray($keyFormat = null, $baseClassName = null)
  {
    if (!$baseClassName) {
      $baseClassName = parent::class;
    }
    return parent::toScalarArray($keyFormat, $baseClassName);
  }

  /**
   * 受注数量合計取得（配送情報CSVに存在）
   */
  public function getOrderedNumTotal()
  {
    $total = 0;
    foreach($this->getDetails() as $detail) {
      $total += $detail->isActive() ? $detail->getOrderedNum() : 0;
    }
    return $total;
  }

  /// setter
  /**
   * @param TbSalesVoucherDeliveryInfo $info
   */
  public function setDeliveryInfo($info)
  {
    $this->deliveryInfo = $info;
  }
  /// getter
  /**
   * @return TbSalesVoucherDeliveryInfo
   */
  public function getDeliveryInfo()
  {
    return $this->deliveryInfo;
  }



  // ------------------------------------------------------------
  // private methods
  // ------------------------------------------------------------

  /**
   * 伝票情報初期化
   */
  private function initializeVoucherInfo()
  {
    $this->setVoucherNumber(null);
    $this->setLineNumber(null);
    $this->setOrderNumber(null);
    $this->setOrderDate(null);
    $this->setImportDate(null);
    $this->setShippingDate(null);
    $this->setCanceled('0');
    $this->setShopName(null);
    $this->setNeMallId(null);
    $this->setShippingMethodName(null);
    $this->setShippingMethodCode(null);
    $this->setOrderStatus('');
    $this->setDetailCanceled('');
    $this->setNeSyohinSyohinCode('');
    $this->setOrderedNum(0);
    $this->setAssignedNum(0);
    $this->setAssignedDate(null);

    $this->setPaymentMethodName('');
    $this->setPaymentMethodCode('');
    $this->setPaymentTotal(0);
    $this->setTax(0);
    $this->setPointSize(0);
    $this->setDeliveryCharge(0);
    $this->setDiscountedAmount(0);


    $this->setCustomerName('');
    $this->setCustomerNameKana('');
    $this->setCustomerTel('');
    $this->setCustomerZipcode('');
    $this->setCustomerAddress1('');
    $this->setCustomerAddress2('');
    $this->setCustomerMail('');

    $this->setDeliveryTimeZone('');
    $this->setDeliveryName('');
    $this->setDeliveryNameKana('');
    $this->setDeliveryTel('');
    $this->setDeliveryZipcode('');
    $this->setDeliveryAddress1('');
    $this->setDeliveryAddress2('');
    $this->setVoucherSyohinName('');
    $this->setShippingPlanedDateManual(null);
    $this->setShippingOrderedDate(null);

    if ($this->details) {
      $detail = $this->details[0];

      $this->setVoucherNumber($detail->getVoucherNumber());
      $this->setLineNumber($detail->getLineNumber());
      $this->setOrderNumber($detail->getOrderNumber());
      $this->setOrderDate($detail->getOrderDate());
      $this->setImportDate($detail->getImportDate());
      $this->setShippingDate($detail->getShippingDate());
      $this->setCanceled($detail->getCanceled());
      $this->setShopName($detail->getShopName());
      $this->setNeMallId($detail->getNeMallId());
      $this->setShippingMethodName($detail->getShippingMethodName());
      $this->setShippingMethodCode($detail->getShippingMethodCode());
      $this->setOrderStatus($detail->getOrderStatus());
      $this->setDetailCanceled($detail->getDetailCanceled());
      $this->setNeSyohinSyohinCode($detail->getNeSyohinSyohinCode());
      $this->setOrderedNum($detail->getOrderedNum());
      $this->setAssignedNum($detail->getAssignedNum());
      $this->setAssignedDate($detail->getAssignedDate());

      $this->setPaymentMethodName($detail->getPaymentMethodName());
      $this->setPaymentMethodCode($detail->getPaymentMethodCode());
      $this->setPaymentTotal($detail->getPaymentTotal());
      $this->setTax($detail->getTax());
      $this->setPointSize($detail->getPointSize());
      $this->setDeliveryCharge($detail->getDeliveryCharge());
      $this->setDiscountedAmount($detail->getDiscountedAmount());

      $this->setCustomerName($detail->getCustomerName());
      $this->setCustomerNameKana($detail->getCustomerNameKana());
      $this->setCustomerTel($detail->getCustomerTel());
      $this->setCustomerZipcode($detail->getCustomerZipcode());
      $this->setCustomerAddress1($detail->getCustomerAddress1());
      $this->setCustomerAddress2($detail->getCustomerAddress2());
      $this->setCustomerMail($detail->getCustomerMail());

      $this->setDeliveryTimeZone($detail->getDeliveryTimeZone());
      $this->setDeliveryName($detail->getDeliveryName());
      $this->setDeliveryNameKana($detail->getDeliveryNameKana());
      $this->setDeliveryTel($detail->getDeliveryTel());
      $this->setDeliveryZipcode($detail->getDeliveryZipcode());
      $this->setDeliveryAddress1($detail->getDeliveryAddress1());
      $this->setDeliveryAddress2($detail->getDeliveryAddress2());
      $this->setVoucherSyohinName($detail->getVoucherSyohinName());
      $this->setShippingPlanedDateManual($detail->getShippingPlanedDateManual());
      $this->setShippingOrderedDate($detail->getShippingOrderedDate());
    }

  }

}
