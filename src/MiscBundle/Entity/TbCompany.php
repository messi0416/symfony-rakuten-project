<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbCompanyRepository;

/**
 * TbCompany
 */
class TbCompany
{
  use ArrayTrait;
  use FillTimestampTrait;

  // -------------------------------------
  // properties
  // -------------------------------------

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $code;

  /**
   * @var string
   */
  private $name;

  /**
   * @var integer
   */
  private $display_order = 9999;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


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
   * Set name
   *
   * @param string $name
   * @return TbCompany
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
   * Set code
   *
   * @param string $code
   * @return TbCompany
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
   * Set created
   *
   * @param \DateTime $created
   * @return TbCompany
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
   * @return TbCompany
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
   * Set display_order
   *
   * @param integer $displayOrder
   * @return TbCompany
   */
  public function setDisplayOrder($displayOrder)
  {
      $this->display_order = $displayOrder;

      return $this;
  }

  /**
   * Get display_order
   *
   * @return integer
   */
  public function getDisplayOrder()
  {
      return $this->display_order;
  }

  /**
   * @var int
   */
  private $status = 0;

  /**
   * Set status
   *
   * @param int $status
   *
   * @return TbVendormasterdata
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get status
   *
   * @return int
   */
  public function getStatus()
  {
    return $this->status;
  }

}
