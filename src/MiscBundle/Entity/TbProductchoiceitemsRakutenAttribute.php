<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductchoiceitemsRakutenAttribute
 */
class TbProductchoiceitemsRakutenAttribute
{
  /**
   * @var string
   */
  private $neSyohinSyohinCode;

  /**
   * @var integer
   */
  private $tbRakutenGenreAttributeId;

  /**
   * @var string
   */
  private $value;


  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   * @return TbProductchoiceitemsRakutenAttribute
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->neSyohinSyohinCode = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get neSyohinSyohinCode
   *
   * @return string 
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->neSyohinSyohinCode;
  }

  /**
   * Set tbRakutenGenreAttributeId
   *
   * @param integer $tbRakutenGenreAttributeId
   * @return TbProductchoiceitemsRakutenAttribute
   */
  public function setTbRakutenGenreAttributeId($tbRakutenGenreAttributeId)
  {
    $this->tbRakutenGenreAttributeId = $tbRakutenGenreAttributeId;

    return $this;
  }

  /**
   * Get tbRakutenGenreAttributeId
   *
   * @return integer 
   */
  public function getTbRakutenGenreAttributeId()
  {
    return $this->tbRakutenGenreAttributeId;
  }

  /**
   * Set value
   *
   * @param string $value
   * @return TbProductchoiceitemsRakutenAttribute
   */
  public function setValue($value)
  {
    $this->value = $value;

    return $this;
  }

  /**
   * Get value
   *
   * @return string 
   */
  public function getValue()
  {
    return $this->value;
  }
}
