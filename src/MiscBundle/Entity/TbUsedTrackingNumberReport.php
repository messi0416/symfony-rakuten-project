<?php

namespace MiscBundle\Entity;

use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbUsedTrackingNumberReport
 */
class TbUsedTrackingNumberReport
{
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var integer
   */
  private $deliveryMethodId;

  /**
   * @var integer
   */
  private $downloadCountEdi;

  /**
   * @var integer
   */
  private $downloadCountNe;

  /**
   * @var \DateTime
   */
  private $created;


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
   * Set deliveryMethodId
   *
   * @param integer $deliveryMethodId
   * @return TbUsedTrackingNumberReport
   */
  public function setDeliveryMethodId($deliveryMethodId)
  {
    $this->deliveryMethodId = $deliveryMethodId;

    return $this;
  }

  /**
   * Get deliveryMethodId
   *
   * @return integer
   */
  public function getDeliveryMethodId()
  {
    return $this->deliveryMethodId;
  }

  /**
   * Set downloadCountEdi
   *
   * @param integer $downloadCountEdi
   * @return TbUsedTrackingNumberReport
   */
  public function setDownloadCountEdi($downloadCountEdi)
  {
    $this->downloadCountEdi = $downloadCountEdi;

    return $this;
  }

  /**
   * Get downloadCountEdi
   *
   * @return integer
   */
  public function getDownloadCountEdi()
  {
    return $this->downloadCountEdi;
  }

  /**
   * Set downloadCountNe
   *
   * @param integer $downloadCountNe
   * @return TbUsedTrackingNumberReport
   */
  public function setDownloadCountNe($downloadCountNe)
  {
    $this->downloadCountNe = $downloadCountNe;

    return $this;
  }

  /**
   * Get downloadCountNe
   *
   * @return integer
   */
  public function getDownloadCountNe()
  {
    return $this->downloadCountNe;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbUsedTrackingNumberReport
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
