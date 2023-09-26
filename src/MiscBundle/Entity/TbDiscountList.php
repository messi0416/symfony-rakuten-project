<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbDiscountList
 */
class TbDiscountList
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var int
   */
  private $stock_amount;

  /**
   * @var \DateTime
   */
  private $last_orderdate;

  /**
   * @var \DateTime
   */
  private $sales_start_date;

  /**
   * @var \DateTime
   */
  private $discount_base_date;

  /**
   * @var \DateTime
   */
  private $discount_terminal_date;

  /**
   * @var int
   */
  private $sales_amount;

  /**
   * @var string
   */
  private $expected_daily_sales_amount;

  /**
   * @var int
   */
  private $estimated_sales_days;

  /**
   * @var int
   */
  private $sell_out_days;

  /**
   * @var \DateTime
   */
  private $sell_out_date;

  /**
   * @var int
   */
  private $sell_out_over_days;

  /**
   * @var int
   */
  private $genka_tnk;

  /**
   * @var int
   */
  private $genka_tnk_ave;

  /**
   * @var int
   */
  private $cost_total;

  /**
   * @var int
   */
  private $base_price;

  /**
   * @var int
   */
  private $current_price;

  /**
   * @var int
   */
  private $discount_price;

  /**
   * @var int
   */
  private $discount_destination_price;

  /**
   * @var string
   */
  private $discount_rate;

  /**
   * @var int
   */
  private $pricedown_flg;

  /**
   * @var string
   */
  private $pic_directory;

  /**
   * @var string
   */
  private $pic_filename;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TbDiscountList
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set stockAmount
   *
   * @param int $stockAmount
   *
   * @return TbDiscountList
   */
  public function setStockAmount($stockAmount)
  {
    $this->stock_amount = $stockAmount;

    return $this;
  }

  /**
   * Get stockAmount
   *
   * @return int
   */
  public function getStockAmount()
  {
    return $this->stock_amount;
  }

  /**
   * Set lastOrderdate
   *
   * @param \DateTime $lastOrderdate
   *
   * @return TbDiscountList
   */
  public function setLastOrderdate($lastOrderdate)
  {
    $this->last_orderdate = $lastOrderdate;

    return $this;
  }

  /**
   * Get lastOrderdate
   *
   * @return \DateTime
   */
  public function getLastOrderdate()
  {
    return $this->last_orderdate;
  }

  /**
   * Set salesStartDate
   *
   * @param \DateTime $salesStartDate
   *
   * @return TbDiscountList
   */
  public function setSalesStartDate($salesStartDate)
  {
    $this->sales_start_date = $salesStartDate;

    return $this;
  }

  /**
   * Get salesStartDate
   *
   * @return \DateTime
   */
  public function getSalesStartDate()
  {
    return $this->sales_start_date;
  }

  /**
   * Set discountBaseDate
   *
   * @param \DateTime $discountBaseDate
   *
   * @return TbDiscountList
   */
  public function setDiscountBaseDate($discountBaseDate)
  {
    $this->discount_base_date = $discountBaseDate;

    return $this;
  }

  /**
   * Get discountBaseDate
   *
   * @return \DateTime
   */
  public function getDiscountBaseDate()
  {
    return $this->discount_base_date;
  }

  /**
   * Set discountTerminalDate
   *
   * @param \DateTime $discountTerminalDate
   *
   * @return TbDiscountList
   */
  public function setDiscountTerminalDate($discountTerminalDate)
  {
    $this->discount_terminal_date = $discountTerminalDate;

    return $this;
  }

  /**
   * Get discountTerminalDate
   *
   * @return \DateTime
   */
  public function getDiscountTerminalDate()
  {
    return $this->discount_terminal_date;
  }

  /**
   * Set salesAmount
   *
   * @param int $salesAmount
   *
   * @return TbDiscountList
   */
  public function setSalesAmount($salesAmount)
  {
    $this->sales_amount = $salesAmount;

    return $this;
  }

  /**
   * Get salesAmount
   *
   * @return int
   */
  public function getSalesAmount()
  {
    return $this->sales_amount;
  }

  /**
   * Set expectedDailySalesAmount
   *
   * @param string $expectedDailySalesAmount
   *
   * @return TbDiscountList
   */
  public function setExpectedDailySalesAmount($expectedDailySalesAmount)
  {
    $this->expected_daily_sales_amount = $expectedDailySalesAmount;

    return $this;
  }

  /**
   * Get expectedDailySalesAmount
   *
   * @return string
   */
  public function getExpectedDailySalesAmount()
  {
    return $this->expected_daily_sales_amount;
  }

  /**
   * Set estimatedSalesDays
   *
   * @param int $estimatedSalesDays
   *
   * @return TbDiscountList
   */
  public function setEstimatedSalesDays($estimatedSalesDays)
  {
    $this->estimated_sales_days = $estimatedSalesDays;

    return $this;
  }

  /**
   * Get estimatedSalesDays
   *
   * @return int
   */
  public function getEstimatedSalesDays()
  {
    return $this->estimated_sales_days;
  }

  /**
   * Set sellOutDays
   *
   * @param int $sellOutDays
   *
   * @return TbDiscountList
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
   * Set sellOutDate
   *
   * @param \DateTime $sellOutDate
   *
   * @return TbDiscountList
   */
  public function setSellOutDate($sellOutDate)
  {
    $this->sell_out_date = $sellOutDate;

    return $this;
  }

  /**
   * Get sellOutDate
   *
   * @return \DateTime
   */
  public function getSellOutDate()
  {
    return $this->sell_out_date;
  }

  /**
   * Set sellOutOverDays
   *
   * @param int $sellOutOverDays
   *
   * @return TbDiscountList
   */
  public function setSellOutOverDays($sellOutOverDays)
  {
    $this->sell_out_over_days = $sellOutOverDays;

    return $this;
  }

  /**
   * Get sellOutOverDays
   *
   * @return int
   */
  public function getSellOutOverDays()
  {
    return $this->sell_out_over_days;
  }

  /**
   * Set genkaTnk
   *
   * @param int $genkaTnk
   *
   * @return TbDiscountList
   */
  public function setGenkaTnk($genkaTnk)
  {
    $this->genka_tnk = $genkaTnk;

    return $this;
  }

  /**
   * Get genkaTnk
   *
   * @return int
   */
  public function getGenkaTnk()
  {
    return $this->genka_tnk;
  }

  /**
   * Set genkaTnkAve
   *
   * @param int $genkaTnkAve
   *
   * @return TbDiscountList
   */
  public function setGenkaTnkAve($genkaTnkAve)
  {
    $this->genka_tnk_ave = $genkaTnkAve;

    return $this;
  }

  /**
   * Get genkaTnkAve
   *
   * @return int
   */
  public function getGenkaTnkAve()
  {
    return $this->genka_tnk_ave;
  }

  /**
   * Set costTotal
   *
   * @param int $costTotal
   *
   * @return TbDiscountList
   */
  public function setCostTotal($costTotal)
  {
    $this->cost_total = $costTotal;

    return $this;
  }

  /**
   * Get costTotal
   *
   * @return int
   */
  public function getCostTotal()
  {
    return $this->cost_total;
  }

  /**
   * Set basePrice
   *
   * @param int $basePrice
   *
   * @return TbDiscountList
   */
  public function setBasePrice($basePrice)
  {
    $this->base_price = $basePrice;

    return $this;
  }

  /**
   * Get basePrice
   *
   * @return int
   */
  public function getBasePrice()
  {
    return $this->base_price;
  }

  /**
   * Set currentPrice
   *
   * @param int $currentPrice
   *
   * @return TbDiscountList
   */
  public function setCurrentPrice($currentPrice)
  {
    $this->current_price = $currentPrice;

    return $this;
  }

  /**
   * Get currentPrice
   *
   * @return int
   */
  public function getCurrentPrice()
  {
    return $this->current_price;
  }

  /**
   * Set discountPrice
   *
   * @param int $discountPrice
   *
   * @return TbDiscountList
   */
  public function setDiscountPrice($discountPrice)
  {
    $this->discount_price = $discountPrice;

    return $this;
  }

  /**
   * Get discountPrice
   *
   * @return int
   */
  public function getDiscountPrice()
  {
    return $this->discount_price;
  }

  /**
   * Set discountDestinationPrice
   *
   * @param int $discountDestinationPrice
   *
   * @return TbDiscountList
   */
  public function setDiscountDestinationPrice($discountDestinationPrice)
  {
    $this->discount_destination_price = $discountDestinationPrice;

    return $this;
  }

  /**
   * Get discountDestinationPrice
   *
   * @return int
   */
  public function getDiscountDestinationPrice()
  {
    return $this->discount_destination_price;
  }

  /**
   * Set discountRate
   *
   * @param string $discountRate
   *
   * @return TbDiscountList
   */
  public function setDiscountRate($discountRate)
  {
    $this->discount_rate = $discountRate;

    return $this;
  }

  /**
   * Get discountRate
   *
   * @return string
   */
  public function getDiscountRate()
  {
    return $this->discount_rate;
  }

  /**
   * Set pricedownFlg
   *
   * @param int $pricedownFlg
   *
   * @return TbDiscountList
   */
  public function setPricedownFlg($pricedownFlg)
  {
    $this->pricedown_flg = $pricedownFlg;

    return $this;
  }

  /**
   * Get pricedownFlg
   *
   * @return int
   */
  public function getPricedownFlg()
  {
    return $this->pricedown_flg;
  }

  /**
   * Set picDirectory
   *
   * @param string $picDirectory
   *
   * @return TbDiscountList
   */
  public function setPicDirectory($picDirectory)
  {
    $this->pic_directory = $picDirectory;

    return $this;
  }

  /**
   * Get picDirectory
   *
   * @return string
   */
  public function getPicDirectory()
  {
    return $this->pic_directory;
  }

  /**
   * Set picFilename
   *
   * @param string $picFilename
   *
   * @return TbDiscountList
   */
  public function setPicFilename($picFilename)
  {
    $this->pic_filename = $picFilename;

    return $this;
  }

  /**
   * Get picFilename
   *
   * @return string
   */
  public function getPicFilename()
  {
    return $this->pic_filename;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbDiscountList
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
   * @return TbDiscountList
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
    private $season_flg;


    /**
     * Set seasonFlg
     *
     * @param int $seasonFlg
     *
     * @return TbDiscountList
     */
    public function setSeasonFlg($seasonFlg)
    {
        $this->season_flg = $seasonFlg;

        return $this;
    }

    /**
     * Get seasonFlg
     *
     * @return int
     */
    public function getSeasonFlg()
    {
        return $this->season_flg;
    }
    /**
     * @var integer
     */
    private $default_warehouse_stock_amount;


    /**
     * Set default_warehouse_stock_amount
     *
     * @param integer $defaultWarehouseStockAmount
     * @return TbDiscountList
     */
    public function setDefaultWarehouseStockAmount($defaultWarehouseStockAmount)
    {
        $this->default_warehouse_stock_amount = $defaultWarehouseStockAmount;

        return $this;
    }

    /**
     * Get default_warehouse_stock_amount
     *
     * @return integer 
     */
    public function getDefaultWarehouseStockAmount()
    {
        return $this->default_warehouse_stock_amount;
    }
}
