<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductSalesAccountAggregateReservation
 */
class TbProductSalesAccountAggregateReservation
{
  /**
   * @var integer
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $ordrerDateFrom;

  /**
   * @var \DateTime
   */
  private $ordrerDateTo;

  /**
   * @var string
   */
  private $daihyoSyohinCode;

  /**
   * @var integer
   */
  private $aggregatedFlg;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set ordrerDateFrom
   *
   * @param \DateTime $ordrerDateFrom
   * @return TbProductSalesAccountAggregateReservation
   */
  public function setOrdrerDateFrom($ordrerDateFrom)
  {
    $this->ordrerDateFrom = $ordrerDateFrom;

    return $this;
  }

  /**
   * Get ordrerDateFrom
   *
   * @return \DateTime 
   */
  public function getOrdrerDateFrom()
  {
    return $this->ordrerDateFrom;
  }

  /**
   * Set ordrerDateTo
   *
   * @param \DateTime $ordrerDateTo
   * @return TbProductSalesAccountAggregateReservation
   */
  public function setOrdrerDateTo($ordrerDateTo)
  {
    $this->ordrerDateTo = $ordrerDateTo;

    return $this;
  }

  /**
   * Get ordrerDateTo
   *
   * @return \DateTime 
   */
  public function getOrdrerDateTo()
  {
    return $this->ordrerDateTo;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   * @return TbProductSalesAccountAggregateReservation
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyoSyohinCode = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string 
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyoSyohinCode;
  }

  /**
   * Set aggregatedFlg
   *
   * @param integer $aggregatedFlg
   * @return TbProductSalesAccountAggregateReservation
   */
  public function setAggregatedFlg($aggregatedFlg)
  {
    $this->aggregatedFlg = $aggregatedFlg;

    return $this;
  }

  /**
   * Get aggregatedFlg
   *
   * @return integer 
   */
  public function getAggregatedFlg()
  {
    return $this->aggregatedFlg;
  }
}
