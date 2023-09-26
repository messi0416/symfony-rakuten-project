<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbProductLocationLog
 * 履歴
 */
class TbProductLocationLog
{
  use ArrayTrait;

  // ------------------------------
  
  /**
   * @var int
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
  private $type;

  /**
   * @var string
   */
  private $action_key;

  /**
   * @var int
   */
  private $location_id = 0;

  /**
   * @var string
   */
  private $location_code = '';

  /**
   * @var string
   */
  private $ne_syohin_syohin_code = '';

  /**
   * @var string
   */
  private $pre_info;

  /**
   * @var string
   */
  private $post_info;

  /**
   * @var \DateTime
   */
  private $created;


  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set account
   *
   * @param string $account
   *
   * @return TbProductLocationLog
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
   *
   * @return TbProductLocationLog
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
   * Set type
   *
   * @param string $type
   *
   * @return TbProductLocationLog
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /**
   * Get type
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set actionKey
   *
   * @param string $actionKey
   *
   * @return TbProductLocationLog
   */
  public function setActionKey($actionKey)
  {
    $this->action_key = $actionKey;

    return $this;
  }

  /**
   * Get actionKey
   *
   * @return string
   */
  public function getActionKey()
  {
    return $this->action_key;
  }

  /**
   * Set locationId
   *
   * @param int $locationId
   *
   * @return TbProductLocationLog
   */
  public function setLocationId($locationId)
  {
    $this->location_id = $locationId;

    return $this;
  }

  /**
   * Get locationId
   *
   * @return int
   */
  public function getLocationId()
  {
    return $this->location_id;
  }

  /**
   * Set locationCode
   *
   * @param string $locationCode
   *
   * @return TbProductLocationLog
   */
  public function setLocationCode($locationCode)
  {
    $this->location_code = $locationCode;

    return $this;
  }

  /**
   * Get locationCode
   *
   * @return string
   */
  public function getLocationCode()
  {
    return $this->location_code;
  }

  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   *
   * @return TbProductLocationLog
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get neSyohinSyohinCode
   *
   * @return string
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->ne_syohin_syohin_code;
  }

  /**
   * Set preInfo
   *
   * @param string $preInfo
   *
   * @return TbProductLocationLog
   */
  public function setPreInfo($preInfo)
  {
    $this->pre_info = $preInfo;

    return $this;
  }

  /**
   * Get preInfo
   *
   * @return string
   */
  public function getPreInfo()
  {
    return $this->pre_info;
  }

  /**
   * Set postInfo
   *
   * @param string $postInfo
   *
   * @return TbProductLocationLog
   */
  public function setPostInfo($postInfo)
  {
    $this->post_info = $postInfo;

    return $this;
  }

  /**
   * Get postInfo
   *
   * @return string
   */
  public function getPostInfo()
  {
    return $this->post_info;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbProductLocationLog
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
   * @var string
   */
  private $comment;

  /**
   * Set comment
   *
   * @param string $comment
   * @return TbProductLocationLog
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
}
