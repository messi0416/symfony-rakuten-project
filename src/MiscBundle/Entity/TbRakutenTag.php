<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbRakutenTag
 */
class TbRakutenTag
{
  use ArrayTrait;

  /**
   * 項目選択肢に指定可能か
   * @return bool
   */
  public function isSelectEnabled()
  {
    // ※ true は -1
    return $this->getSelectEnabled() != 0;
  }


  // ================================================
  // setter, getter
  // ================================================

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
  private $path = '';

  /**
   * @var string
   */
  private $classification = '';

  /**
   * @var string
   */
  private $tag = '';

  /**
   * @var int
   */
  private $select_enabled = 0;


  /**
   * Set directoryId
   *
   * @param string $directoryId
   *
   * @return TbRakutenTag
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
   * @return TbRakutenTag
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
   * Set path
   *
   * @param string $path
   *
   * @return TbRakutenTag
   */
  public function setPath($path)
  {
    $this->path = $path;

    return $this;
  }

  /**
   * Get path
   *
   * @return string
   */
  public function getPath()
  {
    return $this->path;
  }

  /**
   * Set classification
   *
   * @param string $classification
   *
   * @return TbRakutenTag
   */
  public function setClassification($classification)
  {
    $this->classification = $classification;

    return $this;
  }

  /**
   * Get classification
   *
   * @return string
   */
  public function getClassification()
  {
    return $this->classification;
  }

  /**
   * Set tag
   *
   * @param string $tag
   *
   * @return TbRakutenTag
   */
  public function setTag($tag)
  {
    $this->tag = $tag;

    return $this;
  }

  /**
   * Get tag
   *
   * @return string
   */
  public function getTag()
  {
    return $this->tag;
  }

  /**
   * Set selectEnabled
   *
   * @param int $selectEnabled
   *
   * @return TbRakutenTag
   */
  public function setSelectEnabled($selectEnabled)
  {
    $this->select_enabled = $selectEnabled;

    return $this;
  }

  /**
   * Get selectEnabled
   *
   * @return int
   */
  public function getSelectEnabled()
  {
    return $this->select_enabled;
  }
}
