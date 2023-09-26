<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductLocationLogComment
 */
class TbProductLocationLogComment
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $account;

  /**
   * @var string
   */
  private $operation;

  /**
   * @var string
   */
  private $action_key;

  /**
   * @var string
   */
  private $comment;

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
   * Set account
   *
   * @param string $account
   * @return TbProductLocationLogComment
   */
  public function setAccount($account)
  {
    $this->account = $account;

    return $this;
  }

  /**
   * Get account
   *
   * @return string 
   */
  public function getAccount()
  {
    return $this->account;
  }

  /**
   * Set operation
   *
   * @param string $operation
   * @return TbProductLocationLogComment
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
   * Set action_key
   *
   * @param string $actionKey
   * @return TbProductLocationLogComment
   */
  public function setActionKey($actionKey)
  {
    $this->action_key = $actionKey;

    return $this;
  }

  /**
   * Get action_key
   *
   * @return string 
   */
  public function getActionKey()
  {
    return $this->action_key;
  }

  /**
   * Set comment
   *
   * @param string $comment
   * @return TbProductLocationLogComment
   */
  public function setComment($comment)
  {
    $this->comment = $comment;

    return $this;
  }

  /**
   * Get comment
   *
   * @return string 
   */
  public function getComment()
  {
    return $this->comment;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbProductLocationLogComment
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
