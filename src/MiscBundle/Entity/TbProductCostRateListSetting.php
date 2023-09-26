<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductCostRateListSetting
 */
class TbProductCostRateListSetting
{
  use ArrayTrait;
  use FillTimestampTrait;

  const TERM_A_START  = 'aStart'; // 比較期間 開始
  const TERM_A_END    = 'aEnd';   // 比較期間 終了
  const TERM_B_START  = 'bStart'; // 直近期間 開始
  const TERM_B_END    = 'bEnd';   // 直近期間 終了

  /**
   * 期間日付算出
   * @param string $key
   * @return \DateTimeImmutable
   */
  public function getTermDate($key)
  {
    $date = null;
    $today = new \DateTimeImmutable((new \DateTime())->format('Y-m-d 0:0:0'));

    switch ($key) {
      // 比較期間 開始
      case self::TERM_A_START:
        $date = $today->modify(sprintf('-%d day', $this->getSamplingDays() * 2));
        break;
      // 比較期間 終了
      case self::TERM_A_END:
        $date   = $today->modify(sprintf('-%d day', $this->getSamplingDays() + 1))->setTime(23, 59, 59);
        break;
      // 直近期間 開始
      case self::TERM_B_START:
        $date = $today->modify(sprintf('-%d day', $this->getSamplingDays()));
        break;
      // 直近期間 終了
      case self::TERM_B_END:
        $date   = $today->modify('-1 day')->setTime(23, 59, 59);
        break;
    }

    return $date;
  }


  // ------------------------------------------
  // field properties
  // ------------------------------------------
  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $threshold_voucher_num;

  /**
   * @var string
   */
  private $threshold_voucher_term;

  /**
   * @var int
   */
  private $sampling_days;

  /**
   * @var int
   */
  private $move_threshold_rate;

  /**
   * @var int
   */
  private $shake_border;

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
   * @return TbProductCostRateListSetting
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
   * Set thresholdVoucherNum
   *
   * @param string $thresholdVoucherNum
   *
   * @return TbProductCostRateListSetting
   */
  public function setThresholdVoucherNum($thresholdVoucherNum)
  {
    $this->threshold_voucher_num = $thresholdVoucherNum;

    return $this;
  }

  /**
   * Get thresholdVoucherNum
   *
   * @return string
   */
  public function getThresholdVoucherNum()
  {
    return $this->threshold_voucher_num;
  }

  /**
   * Set thresholdVoucherNum
   *
   * @param int $thresholdVoucherTerm
   *
   * @return TbProductCostRateListSetting
   */
  public function setThresholdVoucherTerm($thresholdVoucherTerm)
  {
    $this->threshold_voucher_term = $thresholdVoucherTerm;

    return $this;
  }

  /**
   * Get thresholdVoucherTerm
   *
   * @return string
   */
  public function getThresholdVoucherTerm()
  {
    return $this->threshold_voucher_term;
  }

  /**
   * Set samplingDays
   *
   * @param int $samplingDays
   *
   * @return TbProductCostRateListSetting
   */
  public function setSamplingDays($samplingDays)
  {
    $this->sampling_days = $samplingDays;

    return $this;
  }

  /**
   * Get samplingDays
   *
   * @return int
   */
  public function getSamplingDays()
  {
    return $this->sampling_days;
  }

  /**
   * Set moveThresholdRate
   *
   * @param int $moveThresholdRate
   *
   * @return TbProductCostRateListSetting
   */
  public function setMoveThresholdRate($moveThresholdRate)
  {
    $this->move_threshold_rate = $moveThresholdRate;

    return $this;
  }

  /**
   * Get moveThresholdRate
   *
   * @return int
   */
  public function getMoveThresholdRate()
  {
    return $this->move_threshold_rate;
  }

  /**
   * Set shakeBorder
   *
   * @param int $shakeBorder
   *
   * @return TbProductCostRateListSetting
   */
  public function setShakeBorder($shakeBorder)
  {
    $this->shake_border = $shakeBorder;

    return $this;
  }

  /**
   * Get shakeBorder
   *
   * @return int
   */
  public function getShakeBorder()
  {
    return $this->shake_border;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbProductCostRateListSetting
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
   * @return TbProductCostRateListSetting
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
   * Set changeAmountUp
   *
   * @param int $changeAmountUp
   *
   * @return TbProductCostRateListSetting
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
   * @return TbProductCostRateListSetting
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
   * @return TbProductCostRateListSetting
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
}
