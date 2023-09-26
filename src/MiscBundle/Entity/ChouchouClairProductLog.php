<?php

namespace MiscBundle\Entity;
use Doctrine\Tests\DBAL\Types\ArrayTest;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ChouchouClairProductLog
 */
class ChouchouClairProductLog
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $log_date;

  /**
   * @var string
   */
  private $user_type;

  /**
   * @var int
   */
  private $user;

  /**
   * @var string
   */
  private $operation;

  /**
   * @var string
   */
  private $target;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set id
   *
   * @param string $id
   *
   * @return ChouchouClairProductLog
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  /**
   * Get id
   *
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set logDate
   *
   * @param \DateTime $logDate
   *
   * @return ChouchouClairProductLog
   */
  public function setLogDate($logDate)
  {
    $this->log_date = $logDate;

    return $this;
  }

  /**
   * Get logDate
   *
   * @return \DateTime
   */
  public function getLogDate()
  {
    return $this->log_date;
  }

  /**
   * Set userType
   *
   * @param string $userType
   *
   * @return ChouchouClairProductLog
   */
  public function setUserType($userType)
  {
    $this->user_type = $userType;

    return $this;
  }

  /**
   * Get userType
   *
   * @return string
   */
  public function getUserType()
  {
    return $this->user_type;
  }

  /**
   * Set user
   *
   * @param int $user
   *
   * @return ChouchouClairProductLog
   */
  public function setUser($user)
  {
    $this->user = $user;

    return $this;
  }

  /**
   * Get user
   *
   * @return int
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * Set operation
   *
   * @param string $operation
   *
   * @return ChouchouClairProductLog
   */
  public function setOperation($operation)
  {
    $this->operation = $operation;

    return $this;
  }

  /**
   * Get operation
   *
   * @return string
   */
  public function getOperation()
  {
    return $this->operation;
  }

  /**
   * Set target
   *
   * @param string $target
   *
   * @return ChouchouClairProductLog
   */
  public function setTarget($target)
  {
    $this->target = $target;

    return $this;
  }

  /**
   * Get target
   *
   * @return string
   */
  public function getTarget()
  {
    return $this->target;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return ChouchouClairProductLog
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
   * @return ChouchouClairProductLog
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
   * @var \DateTime
   */
  private $last_stock_modified;


  /**
   * Set lastStockModified
   *
   * @param \DateTime $lastStockModified
   *
   * @return ChouchouClairProductLog
   */
  public function setLastStockModified($lastStockModified)
  {
    $this->last_stock_modified = $lastStockModified;

    return $this;
  }

  /**
   * Get lastStockModified
   *
   * @return \DateTime
   */
  public function getLastStockModified()
  {
    return $this->last_stock_modified;
  }
}
