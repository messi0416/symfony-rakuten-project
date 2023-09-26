<?php

namespace MiscBundle\Entity;

use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbCronProcessSchedule
 */
class TbCronProcessSchedule
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $code;

  /**
   * @var string
   */
  private $type = '';

  /**
   * @var string
   */
  private $name = '';

  /**
   * @var string
   */
  private $hours = '';

  /**
   * @var string
   */
  private $minutes = '';

  /**
   * @var string
   */
  private $command = '';

  /**
   * @var string
   */
  private $day_of_week = '';

  /**
   * @var integer
   */
  private $active = 0;

  /**
   * @var integer
   */
  private $stocks;

  /**
   * @var integer
   */
  private $order_date;

  /**
   * @var integer
   */
  private $arrival_date;

  /**
   * @var float
   */
  private $magnification_percent;

  /**
   * @var string
   */
  private $import_order_list_months = '';

  /**
   * @var string
   */
  private $limit_time_hour = '';

  /**
   * @var string
   */
  private $limit_time_minute = '';

    /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set code
   *
   * @param string $code
   * @return TbCronProcessSchedule
   */
  public function setCode($code)
  {
    $this->code = $code;

    return $this;
  }

  /**
   * Get code
   *
   * @return string
   */
  public function getCode()
  {
    return $this->code;
  }

  /**
   * Set type
   *
   * @param string $type
   * @return TbCronProcessSchedule
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /**
   * Get type
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set name
   *
   * @param string $name
   * @return TbCronProcessSchedule
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set hours
   *
   * @param string $hours
   * @return TbCronProcessSchedule
   */
  public function setHours($hours)
  {
    $this->hours = $hours;

    return $this;
  }

  /**
   * Get hours
   *
   * @return string
   */
  public function getHours()
  {
    return $this->hours;
  }

  /**
   * Set minutes
   *
   * @param string $minutes
   * @return TbCronProcessSchedule
   */
  public function setMinutes($minutes)
  {
    $this->minutes = $minutes;

    return $this;
  }

  /**
   * Get minutes
   *
   * @return string
   */
  public function getMinutes()
  {
    return $this->minutes;
  }

  /**
   * Set command
   *
   * @param string $command
   * @return TbCronProcessSchedule
   */
  public function setCommand($command)
  {
    $this->command = $command;

    return $this;
  }

  /**
   * Get command
   *
   * @return string
   */
  public function getCommand()
  {
    return $this->command;
  }

  /**
   * Set day_of_week
   *
   * @param string $dayOfWeek
   * @return TbCronProcessSchedule
   */
  public function setDayOfWeek($dayOfWeek)
  {
    $this->day_of_week = $dayOfWeek;

    return $this;
  }

  /**
   * Get day_of_week
   *
   * @return string
   */
  public function getDayOfWeek()
  {
    return $this->day_of_week;
  }

  /**
   * Set active
   *
   * @param integer $active
   * @return TbCronProcessSchedule
   */
  public function setActive($active)
  {
    $this->active = $active;

    return $this;
  }

  /**
   * Get stocks
   *
   * @return integer
   */
  public function getStocks()
  {
    return $this->stocks;
  }

  /**
   * Set stocks
   *
   * @param integer $stocks
   * @return TbCronProcessSchedule
   */
  public function setStocks($stocks)
  {
      $this->stocks = $stocks;

      return $this;
  }

  /**
   * Get magnification_percent
   *
   * @return float
   */
  public function getMagnificationPercent()
  {
      return $this->magnification_percent;
  }

  /**
   * Set magnification_percent
   *
   * @param float $magnification_percent
   * @return TbCronProcessSchedule
   */
  public function setMagnificationPercent($magnification_percent)
  {
      $this->magnification_percent = $magnification_percent;

      return $this;
  }


  /**
   * Get order_date
   *
   * @return int
   */
  public function getOrderDate()
  {
      return $this->order_date;
  }

  /**
   * Set order_date
   *
   * @param float $order_date
   * @return TbCronProcessSchedule
   */
  public function setOrderDate($order_date)
  {
      $this->order_date = $order_date;

      return $this;
  }


  /**
   * Get arrival_date
   *
   * @return int
   */
  public function getArrivalDate()
  {
      return $this->arrival_date;
  }

  /**
   * Set arrival_date
   *
   * @param float $arrival_date
   * @return TbCronProcessSchedule
   */
  public function setArrivalDate($arrival_date)
  {
      $this->arrival_date = $arrival_date;

      return $this;
  }

  /**
   * Get active
   *
   * @return integer
   */
  public function getActive()
  {
      return $this->active;
  }

/**
   * Set import_order_list_months
   *
   * @param string $import_order_list_months
   * @return TbCronProcessSchedule
   */
  public function setImportOrderListMonths($import_order_list_months)
  {
    $this->import_order_list_months = $import_order_list_months;

    return $this;
  }

  /**
   * Get import_order_list_months
   *
   * @return string
   */
  public function getImportOrderListMonths()
  {
    return $this->import_order_list_months;
  }

/**
   * Set limit_time_hour
   *
   * @param string $limit_time_hour
   * @return TbCronProcessSchedule
   */
  public function setLimitTimeHour($limit_time_hour)
  {
    $this->limit_time_hour = $limit_time_hour;

    return $this;
  }

  /**
   * Get limit_time_hour
   *
   * @return string
   */
  public function getLimitTimeHour()
  {
    return $this->limit_time_hour;
  }

/**
   * Set limit_time_minute
   *
   * @param string $limit_time_minute
   * @return TbCronProcessSchedule
   */
  public function setLimitTimeMinute($limit_time_minute)
  {
    $this->limit_time_minute = $limit_time_minute;

    return $this;
  }

  /**
   * Get limit_time_minute
   *
   * @return string
   */
  public function getLimitTimeMinute()
  {
    return $this->limit_time_minute;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbCronProcessSchedule
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
   * @return TbCronProcessSchedule
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
