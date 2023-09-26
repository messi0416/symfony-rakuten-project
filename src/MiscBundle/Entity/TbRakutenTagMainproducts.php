<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbRakutenTagMainproducts
 */
class TbRakutenTagMainproducts
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $directory_id;

  /**
   * @var string
   */
  private $tag_id;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TbRakutenTagMainproducts
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
   * Set directoryId
   *
   * @param string $directoryId
   *
   * @return TbRakutenTagMainproducts
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
   * @return TbRakutenTagMainproducts
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
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbRakutenTagMainproducts
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
   * @return TbRakutenTagMainproducts
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
