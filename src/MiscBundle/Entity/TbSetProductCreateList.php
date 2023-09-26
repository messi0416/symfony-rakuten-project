<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbSetProductCreateListRepository;

/**
 * TbSetProductCreateList
 */
class TbSetProductCreateList
{
  use ArrayTrait;
  use FillTimestampTrait;

  // ==================================
  // properties
  // ==================================

  /**
   * @var integer
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $date;

  /**
   * @var integer
   */
  private $number;

  /**
   * @var string
   */
  private $set_sku;

  /**
   * @var integer
   */
  private $required_num = 0;

  /**
   * @var integer
   */
  private $creatable_num = 0;

  /**
   * @var integer
   */
  private $create_num = 0;

  /**
   * @var integer
   */
  private $status = 0;

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
   * Set date
   *
   * @param \DateTime $date
   * @return TbSetProductCreateList
   */
  public function setDate($date)
  {
    $this->date = $date;

    return $this;
  }

  /**
   * Get date
   *
   * @return \DateTime 
   */
  public function getDate()
  {
    return $this->date;
  }

  /**
   * Set number
   *
   * @param integer $number
   * @return TbSetProductCreateList
   */
  public function setNumber($number)
  {
    $this->number = $number;

    return $this;
  }

  /**
   * Get number
   *
   * @return integer 
   */
  public function getNumber()
  {
    return $this->number;
  }

  /**
   * Set set_sku
   *
   * @param string $setSku
   * @return TbSetProductCreateList
   */
  public function setSetSku($setSku)
  {
    $this->set_sku = $setSku;

    return $this;
  }

  /**
   * Get set_sku
   *
   * @return string 
   */
  public function getSetSku()
  {
    return $this->set_sku;
  }

  /**
   * Set required_num
   *
   * @param integer $requiredNum
   * @return TbSetProductCreateList
   */
  public function setRequiredNum($requiredNum)
  {
    $this->required_num = $requiredNum;

    return $this;
  }

  /**
   * Get required_num
   *
   * @return integer 
   */
  public function getRequiredNum()
  {
    return $this->required_num;
  }

  /**
   * Set creatable_num
   *
   * @param integer $creatableNum
   * @return TbSetProductCreateList
   */
  public function setCreatableNum($creatableNum)
  {
    $this->creatable_num = $creatableNum;

    return $this;
  }

  /**
   * Get creatable_num
   *
   * @return integer 
   */
  public function getCreatableNum()
  {
    return $this->creatable_num;
  }

  /**
   * Set create_num
   *
   * @param integer $createNum
   * @return TbSetProductCreateList
   */
  public function setCreateNum($createNum)
  {
    $this->create_num = $createNum;

    return $this;
  }

  /**
   * Get create_num
   *
   * @return integer 
   */
  public function getCreateNum()
  {
    return $this->create_num;
  }

  /**
   * Set status
   *
   * @param integer $status
   * @return TbSetProductCreateList
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get status
   *
   * @return integer 
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbSetProductCreateList
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
   * @return TbSetProductCreateList
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
