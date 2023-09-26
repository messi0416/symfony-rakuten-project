<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbRakutenTagProductchoiceitems
 */
class TbRakutenTagProductchoiceitems
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var string
   */
  private $directory_id;

  /**
   * @var string
   */
  private $tag_id;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   *
   * @return TbRakutenTagProductchoiceitems
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
   * Set directoryId
   *
   * @param string $directoryId
   *
   * @return TbRakutenTagProductchoiceitems
   */
  public function setDirectoryId($directoryId)
  {
    $this->directory_id = $directoryId;

    return $this;
  }

  /**
   * Get directoryId
   *
   * @return string
   */
  public function getDirectoryId()
  {
    return $this->directory_id;
  }

  /**
   * Set tagId
   *
   * @param string $tagId
   *
   * @return TbRakutenTagProductchoiceitems
   */
  public function setTagId($tagId)
  {
    $this->tag_id = $tagId;

    return $this;
  }

  /**
   * Get tagId
   *
   * @return string
   */
  public function getTagId()
  {
    return $this->tag_id;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TbRakutenTagProductchoiceitems
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbRakutenTagProductchoiceitems
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
   * @return TbRakutenTagProductchoiceitems
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
