<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductCostRateList
 */
class TbProductCostRateList
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * 揺さぶり対象済み
   */
  public function isShaken()
  {
    return $this->shaken != 0;
  }


  /**
   * 原価率変動方向（対 現設定）
   */
  public function getCostRateDirection()
  {
    $direction = '';

    $base = $this->getCal()->getCostRate()
          ? $this->getCal()->getCostRate()
          : $this->getProduct()->getVendor()->getCostRate();
    $value = $this->getCostRateAfter();

    if ($value > $base) {
      $direction = 'up';
    } else if ($value < $base) {
      $direction = 'down';
    }

    return $direction;
  }

  /**
   * 原価率変動方向（対 変動基準値）
   */
  public function getCostRateDirectionByAverage()
  {
    $direction = '';

    $base = $this->getLogCostRateAverageB();
    $value = $this->getCostRateAfter();

    if ($value > $base) {
      $direction = 'up';
    } else if ($value < $base) {
      $direction = 'down';
    }

    return $direction;
  }


  /**
   * 粗利額変動方向
   */
  public function getProfitDirection()
  {
    $direction = '';

    $base = $this->getLogProfitA();
    $value = $this->getLogProfitB();

    if ($value > $base) {
      $direction = 'up';
    } else if ($value < $base) {
      $direction = 'down';
    }

    return $direction;
  }
  /**
   * 伝票数変動方向
   */
  public function getVoucherNumDirection()
  {
    $direction = '';

    $base = $this->getLogVoucherNumA();
    $value = $this->getLogVoucherNumB();

    if ($value > $base) {
      $direction = 'up';
    } else if ($value < $base) {
      $direction = 'down';
    }

    return $direction;
  }










  /**
   * @var \MiscBundle\Entity\TbMainproducts
   */
  private $product;

  /**
   * @var \MiscBundle\Entity\TbMainproductsCal
   */
  private $cal;

  /**
   * Set product
   *
   * @param \MiscBundle\Entity\TbMainproducts $product
   *
   * @return TbProductCostRateList
   */
  public function setProduct(\MiscBundle\Entity\TbMainproducts $product = null)
  {
    $this->product = $product;

    return $this;
  }

  /**
   * Get product
   *
   * @return \MiscBundle\Entity\TbMainproducts
   */
  public function getProduct()
  {
    return $this->product;
  }

  /**
   * Set cal
   *
   * @param \MiscBundle\Entity\TbMainproductsCal $cal
   *
   * @return TbProductCostRateList
   */
  public function setCal(\MiscBundle\Entity\TbMainproductsCal $cal = null)
  {
    $this->cal = $cal;

    return $this;
  }

  /**
   * Get cal
   *
   * @return \MiscBundle\Entity\TbMainproductsCal
   */
  public function getCal()
  {
    return $this->cal;
  }

  // ------------------------------------
  // field properties
  // ------------------------------------
  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $cost_rate_after = 0;

  /**
   * @var double
   */
  private $log_cost_rate_average_a = 0;

  /**
   * @var int
   */
  private $log_profit_a = 0;

  /**
   * @var int
   */
  private $log_voucher_num_a = 0;

  /**
   * @var double
   */
  private $log_cost_rate_average_b = 0;

  /**
   * @var int
   */
  private $log_profit_b = 0;

  /**
   * @var int
   */
  private $log_voucher_num_b = 0;

  /**
   * @var int
   */
  private $accumulated_cost_rate_change = 0;

  /**
   * @var double
   */
  private $threshold_term_voucher_num_average = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


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
   * Set costRateAfter
   *
   * @param int $costRateAfter
   *
   * @return TbProductCostRateList
   */
  public function setCostRateAfter($costRateAfter)
  {
    $this->cost_rate_after = $costRateAfter;

    return $this;
  }

  /**
   * Get costRateAfter
   *
   * @return int
   */
  public function getCostRateAfter()
  {
    return $this->cost_rate_after;
  }

  /**
   * Set logCostRateAverageA
   *
   * @param string $logCostRateAverageA
   *
   * @return TbProductCostRateList
   */
  public function setLogCostRateAverageA($logCostRateAverageA)
  {
    $this->log_cost_rate_average_a = $logCostRateAverageA;

    return $this;
  }

  /**
   * Get logCostRateAverageA
   *
   * @return string
   */
  public function getLogCostRateAverageA()
  {
    return $this->log_cost_rate_average_a;
  }


  /**
   * Set logProfitA
   *
   * @param int $logProfitA
   *
   * @return TbProductCostRateList
   */
  public function setLogProfitA($logProfitA)
  {
    $this->log_profit_a = $logProfitA;

    return $this;
  }

  /**
   * Get logProfitA
   *
   * @return int
   */
  public function getLogProfitA()
  {
    return $this->log_profit_a;
  }

  /**
   * Set logVoucherNumA
   *
   * @param int $logVoucherNumA
   *
   * @return TbProductCostRateList
   */
  public function setLogVoucherNumA($logVoucherNumA)
  {
    $this->log_voucher_num_a = $logVoucherNumA;

    return $this;
  }

  /**
   * Get logVoucherNumA
   *
   * @return int
   */
  public function getLogVoucherNumA()
  {
    return $this->log_voucher_num_a;
  }

  /**
   * Set logCostRateAverageB
   *
   * @param string $logCostRateAverageB
   *
   * @return TbProductCostRateList
   */
  public function setLogCostRateAverageB($logCostRateAverageB)
  {
    $this->log_cost_rate_average_b = $logCostRateAverageB;

    return $this;
  }

  /**
   * Get logCostRateAverageB
   *
   * @return string
   */
  public function getLogCostRateAverageB()
  {
    return $this->log_cost_rate_average_b;
  }

  /**
   * Set logProfitB
   *
   * @param int $logProfitB
   *
   * @return TbProductCostRateList
   */
  public function setLogProfitB($logProfitB)
  {
    $this->log_profit_b = $logProfitB;

    return $this;
  }

  /**
   * Get logProfitB
   *
   * @return int
   */
  public function getLogProfitB()
  {
    return $this->log_profit_b;
  }

  /**
   * Set logVoucherNumB
   *
   * @param int $logVoucherNumB
   *
   * @return TbProductCostRateList
   */
  public function setLogVoucherNumB($logVoucherNumB)
  {
    $this->log_voucher_num_b = $logVoucherNumB;

    return $this;
  }

  /**
   * Get logVoucherNumB
   *
   * @return int
   */
  public function getLogVoucherNumB()
  {
    return $this->log_voucher_num_b;
  }

  /**
   * Set accumulatedCostRateChange
   *
   * @param int $accumulatedCostRateChange
   *
   * @return TbProductCostRateList
   */
  public function setAccumulatedCostRateChange($accumulatedCostRateChange)
  {
    $this->accumulated_cost_rate_change = $accumulatedCostRateChange;

    return $this;
  }

  /**
   * Get accumulatedCostRateChange
   *
   * @return int
   */
  public function getAccumulatedCostRateChange()
  {
    return $this->accumulated_cost_rate_change;
  }

  /**
   * Set thresholdTermVoucherNumAverage
   *
   * @param double $thresholdTermVoucherNumAverage
   *
   * @return TbProductCostRateList
   */
  public function setThresholdTermVoucherNumAverage($thresholdTermVoucherNumAverage)
  {
    $this->threshold_term_voucher_num_average = $thresholdTermVoucherNumAverage;

    return $this;
  }

  /**
   * Get thresholdTermVoucherNumAverage
   *
   * @return double
   */
  public function getThresholdTermVoucherNumAverage()
  {
    return $this->threshold_term_voucher_num_average;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbProductCostRateList
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
   * @return TbProductCostRateList
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
  private $shaken = 0;


  /**
   * Set shaken
   *
   * @param int $shaken
   *
   * @return TbProductCostRateList
   */
  public function setShaken($shaken)
  {
    $this->shaken = $shaken;

    return $this;
  }

  /**
   * Get shaked
   *
   * @return int
   */
  public function getShaken()
  {
    return $this->shaken;
  }
}
