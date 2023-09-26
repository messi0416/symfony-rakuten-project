<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbRfidReadings
 */
class TbRfidReadings
{
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var integer
   */
  private $readingId;

  /**
   * @var string
   */
  private $boxTag;

  /**
   * @var string
   */
  private $productTag;

  /**
   * @var \DateTime
   */
  private $createdAt;

  /**
   * @var \DateTime
   */
  private $updatedAt;


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
   * Set readingId
   *
   * @param integer $readingId
   * @return TbRfidReadings
   */
  public function setReadingId($readingId)
  {
    $this->readingId = $readingId;

    return $this;
  }

  /**
   * Get readingId
   *
   * @return integer 
   */
  public function getReadingId()
  {
    return $this->readingId;
  }

  /**
   * Set boxTag
   *
   * @param string $boxTag
   * @return TbRfidReadings
   */
  public function setBoxTag($boxTag)
  {
    $this->boxTag = $boxTag;

    return $this;
  }

  /**
   * Get boxTag
   *
   * @return string 
   */
  public function getBoxTag()
  {
    return $this->boxTag;
  }

  /**
   * Set productTag
   *
   * @param string $productTag
   * @return TbRfidReadings
   */
  public function setProductTag($productTag)
  {
    $this->productTag = $productTag;

    return $this;
  }

  /**
   * Get productTag
   *
   * @return string 
   */
  public function getProductTag()
  {
    return $this->productTag;
  }

  /**
   * Set createdAt
   *
   * @param \DateTime $createdAt
   * @return TbRfidReadings
   */
  public function setCreatedAt($createdAt)
  {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Get createdAt
   *
   * @return \DateTime 
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * Set updatedAt
   *
   * @param \DateTime $updatedAt
   * @return TbRfidReadings
   */
  public function setUpdatedAt($updatedAt)
  {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Get updatedAt
   *
   * @return \DateTime 
   */
  public function getUpdatedAt()
  {
    return $this->updatedAt;
  }
}
