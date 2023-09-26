<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductRfid
 */
class TbProductRfid
{
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $neSyohinSyohinCode;

  /**
   * @var integer
   */
  private $sequenceNum;

  /**
   * @var integer
   */
  private $createAccountId;

  /**
   * @var \DateTime
   */
  private $created;


  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   * @return TbProductRfid
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->neSyohinSyohinCode = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get neSyohinSyohinCode
   *
   * @return string 
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->neSyohinSyohinCode;
  }

  /**
   * Set sequenceNum
   *
   * @param integer $sequenceNum
   * @return TbProductRfid
   */
  public function setSequenceNum($sequenceNum)
  {
    $this->sequenceNum = $sequenceNum;

    return $this;
  }

  /**
   * Get sequenceNum
   *
   * @return integer 
   */
  public function getSequenceNum()
  {
    return $this->sequenceNum;
  }

  /**
   * Set createAccountId
   *
   * @param integer $createAccountId
   * @return TbProductRfid
   */
  public function setCreateAccountId($createAccountId)
  {
    $this->createAccountId = $createAccountId;

    return $this;
  }

  /**
   * Get createAccountId
   *
   * @return integer 
   */
  public function getCreateAccountId()
  {
    return $this->createAccountId;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbProductRfid
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
}
