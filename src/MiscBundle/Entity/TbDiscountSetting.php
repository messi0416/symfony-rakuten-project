<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbDiscountSetting
 */
class TbDiscountSetting
{
  use FillTimestampTrait;
  use ArrayTrait;

  /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $discount_excluded_days;

    /**
     * @var int
     */
    private $sales_term_days;

    /**
     * @var int
     */
    private $sales_sampling_days;

    /**
     * @var int
     */
    private $sell_out_days;

    /**
     * @var int
     */
    private $allowed_sell_out_over_days;

    /**
     * @var int
     */
    private $max_discount_rate;

    /**
     * @var integer
     */
    private $limitWithinDays1;

    /**
     * @var integer
     */
    private $limitRateForCost1;

    /**
     * @var integer
     */
    private $limitWithinDays2;

    /**
     * @var integer
     */
    private $limitRateForCost2;

    /**
     * @var integer
     */
    private $limitWithinDays3;

    /**
     * @var integer
     */
    private $limitRateForCost3;

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
     * @return TbDiscountSetting
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
     * Set discountExcludedDays
     *
     * @param int $discountExcludedDays
     *
     * @return TbDiscountSetting
     */
    public function setDiscountExcludedDays($discountExcludedDays)
    {
        $this->discount_excluded_days = $discountExcludedDays;

        return $this;
    }

    /**
     * Get discountExcludedDays
     *
     * @return int
     */
    public function getDiscountExcludedDays()
    {
        return $this->discount_excluded_days;
    }

    /**
     * Set salesTermDays
     *
     * @param int $salesTermDays
     *
     * @return TbDiscountSetting
     */
    public function setSalesTermDays($salesTermDays)
    {
        $this->sales_term_days = $salesTermDays;

        return $this;
    }

    /**
     * Get salesTermDays
     *
     * @return int
     */
    public function getSalesTermDays()
    {
        return $this->sales_term_days;
    }

    /**
     * Set salesSamplingDays
     *
     * @param int $salesSamplingDays
     *
     * @return TbDiscountSetting
     */
    public function setSalesSamplingDays($salesSamplingDays)
    {
        $this->sales_sampling_days = $salesSamplingDays;

        return $this;
    }

    /**
     * Get salesSamplingDays
     *
     * @return int
     */
    public function getSalesSamplingDays()
    {
        return $this->sales_sampling_days;
    }

    /**
     * Set sellOutDays
     *
     * @param int $sellOutDays
     *
     * @return TbDiscountSetting
     */
    public function setSellOutDays($sellOutDays)
    {
        $this->sell_out_days = $sellOutDays;

        return $this;
    }

    /**
     * Get sellOutDays
     *
     * @return int
     */
    public function getSellOutDays()
    {
        return $this->sell_out_days;
    }

    /**
     * Set allowedSellOutOverDays
     *
     * @param int $allowedSellOutOverDays
     *
     * @return TbDiscountSetting
     */
    public function setAllowedSellOutOverDays($allowedSellOutOverDays)
    {
        $this->allowed_sell_out_over_days = $allowedSellOutOverDays;

        return $this;
    }

    /**
     * Get allowedSellOutOverDays
     *
     * @return int
     */
    public function getAllowedSellOutOverDays()
    {
        return $this->allowed_sell_out_over_days;
    }

    /**
     * Set maxDiscountRate
     *
     * @param int $maxDiscountRate
     *
     * @return TbDiscountSetting
     */
    public function setMaxDiscountRate($maxDiscountRate)
    {
        $this->max_discount_rate = $maxDiscountRate;

        return $this;
    }

    /**
     * Get maxDiscountRate
     *
     * @return int
     */
    public function getMaxDiscountRate()
    {
        return $this->max_discount_rate;
    }


    /**
     * Set limitWithinDays1
     *
     * @param integer $limitWithinDays1
     * @return TbDiscountSetting
     */
    public function setLimitWithinDays1($limitWithinDays1)
    {
      $this->limitWithinDays1 = $limitWithinDays1;

      return $this;
    }

    /**
     * Get limitWithinDays1
     *
     * @return integer
     */
    public function getLimitWithinDays1()
    {
      return $this->limitWithinDays1;
    }

    /**
     * Set limitRateForCost1
     *
     * @param integer $limitRateForCost1
     * @return TbDiscountSetting
     */
    public function setLimitRateForCost1($limitRateForCost1)
    {
      $this->limitRateForCost1 = $limitRateForCost1;

      return $this;
    }

    /**
     * Get limitRateForCost1
     *
     * @return integer
     */
    public function getLimitRateForCost1()
    {
      return $this->limitRateForCost1;
    }

    /**
     * Set limitWithinDays2
     *
     * @param integer $limitWithinDays2
     * @return TbDiscountSetting
     */
    public function setLimitWithinDays2($limitWithinDays2)
    {
      $this->limitWithinDays2 = $limitWithinDays2;

      return $this;
    }

    /**
     * Get limitWithinDays2
     *
     * @return integer
     */
    public function getLimitWithinDays2()
    {
      return $this->limitWithinDays2;
    }

    /**
     * Set limitRateForCost2
     *
     * @param integer $limitRateForCost2
     * @return TbDiscountSetting
     */
    public function setLimitRateForCost2($limitRateForCost2)
    {
      $this->limitRateForCost2 = $limitRateForCost2;

      return $this;
    }

    /**
     * Get limitRateForCost2
     *
     * @return integer
     */
    public function getLimitRateForCost2()
    {
      return $this->limitRateForCost2;
    }

    /**
     * Set limitWithinDays3
     *
     * @param integer $limitWithinDays3
     * @return TbDiscountSetting
     */
    public function setLimitWithinDays3($limitWithinDays3)
    {
      $this->limitWithinDays3 = $limitWithinDays3;

      return $this;
    }

    /**
     * Get limitWithinDays3
     *
     * @return integer
     */
    public function getLimitWithinDays3()
    {
      return $this->limitWithinDays3;
    }

    /**
     * Set limitRateForCost3
     *
     * @param integer $limitRateForCost3
     * @return TbDiscountSetting
     */
    public function setLimitRateForCost3($limitRateForCost3)
    {
      $this->limitRateForCost3 = $limitRateForCost3;

      return $this;
    }

    /**
     * Get limitRateForCost3
     *
     * @return integer
     */
    public function getLimitRateForCost3()
    {
      return $this->limitRateForCost3;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return TbDiscountSetting
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
     * @return TbDiscountSetting
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
