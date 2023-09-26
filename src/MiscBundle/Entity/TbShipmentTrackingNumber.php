<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShipmentTrackingNumber
 */
class TbShipmentTrackingNumber
{
  /** ステータス：未使用 */
  const STATUS_UNUSED = 0;
  /** ステータス：使用済 */
  const STATUS_USED = 1;
  /** ステータス：報告CSV作成済 */
  const STATUS_REPORTED_CSV = 2;
  /** ステータス：キャンセル */
  const STATUS_CANCELLED = 3;

  /**
   * @var string
   */
  private $trackingNumber;

  /**
   * @var integer
   */
  private $deliveryMethodId;

  /**
   * @var integer
   */
  private $voucherNumber;

  /**
   * @var integer
   */
  private $usedTrackingNumberReportId;

  /**
   * @var integer
   */
  private $status;

  /**
   * @var \DateTime
   */
  private $usedDatetime;


  /**
   * Set trackingNumber
   *
   * @param string $trackingNumber
   * @return TbShipmentTrackingNumber
   */
  public function setTrackingNumber($trackingNumber)
  {
    $this->trackingNumber = $trackingNumber;

    return $this;
  }

  /**
   * Get trackingNumber
   *
   * @return string
   */
  public function getTrackingNumber()
  {
    return $this->trackingNumber;
  }

  /**
   * Set deliveryMethodId
   *
   * @param integer $deliveryMethodId
   * @return TbShipmentTrackingNumber
   */
  public function setDeliveryMethodId($deliveryMethodId)
  {
    $this->deliveryMethodId = $deliveryMethodId;

    return $this;
  }

  /**
   * Get deliveryMethodId
   *
   * @return integer
   */
  public function getDeliveryMethodId()
  {
    return $this->deliveryMethodId;
  }

  /**
   * Set voucherNumber
   *
   * @param integer $voucherNumber
   * @return TbShipmentTrackingNumber
   */
  public function setVoucherNumber($voucherNumber)
  {
    $this->voucherNumber = $voucherNumber;

    return $this;
  }

  /**
   * Get voucherNumber
   *
   * @return integer
   */
  public function getVoucherNumber()
  {
    return $this->voucherNumber;
  }

  /**
   * Set usedTrackingNumberReportId
   *
   * @param integer $usedTrackingNumberReportId
   * @return TbShipmentTrackingNumber
   */
  public function setUsedTrackingNumberReportId($usedTrackingNumberReportId)
  {
    $this->usedTrackingNumberReportId = $usedTrackingNumberReportId;

    return $this;
  }

  /**
   * Get usedTrackingNumberReportId
   *
   * @return integer
   */
  public function getUsedTrackingNumberReportId()
  {
    return $this->usedTrackingNumberReportId;
  }

  /**
   * Set status
   *
   * @param integer $status
   * @return TbShipmentTrackingNumber
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
   * Set usedDatetime
   *
   * @param \DateTime $usedDatetime
   * @return TbShipmentTrackingNumber
   */
  public function setUsedDatetime($usedDatetime)
  {
    $this->usedDatetime = $usedDatetime;

    return $this;
  }

  /**
   * Get usedDatetime
   *
   * @return \DateTime
   */
  public function getUsedDatetime()
  {
    return $this->usedDatetime;
  }
}
