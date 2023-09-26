<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenGenreAttribute
 */
class TbRakutenGenreAttribute
{
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $rakutenGenreId;

  /**
   * @var integer
   */
  private $attributeId;

  /**
   * @var string
   */
  private $attributeName;

  /**
   * @var string
   */
  private $attributeUnit;

  /**
   * @var integer
   */
  private $requiredFlg;


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
   * Set rakutenGenreId
   *
   * @param string $rakutenGenreId
   * @return TbRakutenGenreAttribute
   */
  public function setRakutenGenreId($rakutenGenreId)
  {
    $this->rakutenGenreId = $rakutenGenreId;

    return $this;
  }

  /**
   * Get rakutenGenreId
   *
   * @return string 
   */
  public function getRakutenGenreId()
  {
    return $this->rakutenGenreId;
  }

  /**
   * Set attributeId
   *
   * @param integer $attributeId
   * @return TbRakutenGenreAttribute
   */
  public function setAttributeId($attributeId)
  {
    $this->attributeId = $attributeId;

    return $this;
  }

  /**
   * Get attributeId
   *
   * @return integer 
   */
  public function getAttributeId()
  {
    return $this->attributeId;
  }

  /**
   * Set attributeName
   *
   * @param string $attributeName
   * @return TbRakutenGenreAttribute
   */
  public function setAttributeName($attributeName)
  {
    $this->attributeName = $attributeName;

    return $this;
  }

  /**
   * Get attributeName
   *
   * @return string 
   */
  public function getAttributeName()
  {
    return $this->attributeName;
  }

  /**
   * Set attributeUnit
   *
   * @param string $attributeUnit
   * @return TbRakutenGenreAttribute
   */
  public function setAttributeUnit($attributeUnit)
  {
    $this->attributeUnit = $attributeUnit;

    return $this;
  }

  /**
   * Get attributeUnit
   *
   * @return string 
   */
  public function getAttributeUnit()
  {
    return $this->attributeUnit;
  }

  /**
   * Set requiredFlg
   *
   * @param integer $requiredFlg
   * @return TbRakutenGenreAttribute
   */
  public function setRequiredFlg($requiredFlg)
  {
    $this->requiredFlg = $requiredFlg;

    return $this;
  }

  /**
   * Get requiredFlg
   *
   * @return integer
   */
  public function getRequiredFlg()
  {
    return $this->requiredFlg;
  }
}
