<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbVendorCostRateListSetting
 */
class TbVendorCostRateListSetting
{
  use FillTimestampTrait;

  /**
   * @var int
   */
  private $id;

  /**
   * @var int
   */
  private $minimum_voucher;

  /**
   * @var int
   */
  private $change_threshold;

  /**
   * @var int
   */
  private $settled_threshold;

  /**
   * @var int
   */
  private $change_amount_up;

  /**
   * @var int
   */
  private $change_amount_down;

  /**
   * @var int
   */
  private $change_amount_additional;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set id
   *
   * @param int $id
   *
   * @return TbVendorCostRateListSetting
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

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
   * Set minimumVoucher
   *
   * @param int $minimumVoucher
   *
   * @return TbVendorCostRateListSetting
   */
  public function setMinimumVoucher($minimumVoucher)
  {
    $this->minimum_voucher = $minimumVoucher;

    return $this;
  }

  /**
   * Get minimumVoucher
   *
   * @return int
   */
  public function getMinimumVoucher()
  {
    return $this->minimum_voucher;
  }

  /**
   * Set changeThreshold
   *
   * @param int $changeThreshold
   *
   * @return TbVendorCostRateListSetting
   */
  public function setChangeThreshold($changeThreshold)
  {
    $this->change_threshold = $changeThreshold;

    return $this;
  }

  /**
   * Get changeThreshold
   *
   * @return int
   */
  public function getChangeThreshold()
  {
    return $this->change_threshold;
  }

  /**
   * Set settledThreshold
   *
   * @param int $settledThreshold
   *
   * @return TbVendorCostRateListSetting
   */
  public function setSettledThreshold($settledThreshold)
  {
    $this->settled_threshold = $settledThreshold;

    return $this;
  }

  /**
   * Get settledThreshold
   *
   * @return int
   */
  public function getSettledThreshold()
  {
    return $this->settled_threshold;
  }

  /**
   * Set changeAmountUp
   *
   * @param int $changeAmountUp
   *
   * @return TbVendorCostRateListSetting
   */
  public function setChangeAmountUp($changeAmountUp)
  {
    $this->change_amount_up = $changeAmountUp;

    return $this;
  }

  /**
   * Get changeAmountUp
   *
   * @return int
   */
  public function getChangeAmountUp()
  {
    return $this->change_amount_up;
  }

  /**
   * Set changeAmountDown
   *
   * @param int $changeAmountDown
   *
   * @return TbVendorCostRateListSetting
   */
  public function setChangeAmountDown($changeAmountDown)
  {
    $this->change_amount_down = $changeAmountDown;

    return $this;
  }

  /**
   * Get changeAmountDown
   *
   * @return int
   */
  public function getChangeAmountDown()
  {
    return $this->change_amount_down;
  }

  /**
   * Set changeAmountAdditional
   *
   * @param int $changeAmountAdditional
   *
   * @return TbVendorCostRateListSetting
   */
  public function setChangeAmountAdditional($changeAmountAdditional)
  {
    $this->change_amount_additional = $changeAmountAdditional;

    return $this;
  }

  /**
   * Get changeAmountAdditional
   *
   * @return int
   */
  public function getChangeAmountAdditional()
  {
    return $this->change_amount_additional;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbVendorCostRateListSetting
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
   *
   * @return TbVendorCostRateListSetting
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
