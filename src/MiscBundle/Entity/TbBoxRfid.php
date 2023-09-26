<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbBoxRfid
 */
class TbBoxRfid
{
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $boxCode;

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
   * Set boxCode
   *
   * @param string $boxCode
   * @return TbBoxRfid
   */
  public function setBoxCode($boxCode)
  {
    $this->boxCode = $boxCode;

    return $this;
  }

  /**
   * Get boxCode
   *
   * @return string 
   */
  public function getBoxCode()
  {
    return $this->boxCode;
  }

  /**
   * Set sequenceNum
   *
   * @param integer $sequenceNum
   * @return TbBoxRfid
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
   * @return TbBoxRfid
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
   * @return TbBoxRfid
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
