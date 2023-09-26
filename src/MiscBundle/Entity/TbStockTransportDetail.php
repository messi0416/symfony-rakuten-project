<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbStockTransportDetail
 */
class TbStockTransportDetail
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $transport_id;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var integer
   */
  private $amount = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set transport_id
   *
   * @param int $transportId
   * @return TbStockTransportDetail
   */
  public function setTransportId($transportId)
  {
    $this->transport_id = $transportId;

    return $this;
  }

  /**
   * Get transport_id
   *
   * @return int
   */
  public function getTransportId()
  {
    return $this->transport_id;
  }

  /**
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbStockTransportDetail
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
   * Set amount
   *
   * @param integer $amount
   * @return TbStockTransportDetail
   */
  public function setAmount($amount)
  {
    $this->amount = $amount;

    return $this;
  }

  /**
   * Get amount
   *
   * @return integer 
   */
  public function getAmount()
  {
    return $this->amount;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbStockTransportDetail
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
   * @return TbStockTransportDetail
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
   * @var integer
   */
  private $picked = 0;

  /**
   * @var integer
   */
  private $shortage = 0;


  /**
   * Set picked
   *
   * @param integer $picked
   * @return TbStockTransportDetail
   */
  public function setPicked($picked)
  {
    $this->picked = $picked;

    return $this;
  }

  /**
   * Get picked
   *
   * @return integer 
   */
  public function getPicked()
  {
    return $this->picked;
  }

  /**
   * Set shortage
   *
   * @param integer $shortage
   * @return TbStockTransportDetail
   */
  public function setShortage($shortage)
  {
    $this->shortage = $shortage;

    return $this;
  }

  /**
   * Get shortage
   *
   * @return integer 
   */
  public function getShortage()
  {
    return $this->shortage;
  }
}
