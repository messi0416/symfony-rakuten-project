<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductCode
 */
class TbProductCode
{
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var string
   */
  private $barcode;


  /**
   * Set id
   *
   * @param integer $id
   * @return TbProductCode
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

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
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbProductCode
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get ne_syohin_syohin_code
   *
   * @return string 
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->ne_syohin_syohin_code;
  }

  /**
   * Set barcode
   *
   * @param string $barcode
   * @return TbProductCode
   */
  public function setBarcode($barcode)
  {
    $this->barcode = $barcode;

    return $this;
  }

  /**
   * Get barcode
   *
   * @return string 
   */
  public function getBarcode()
  {
    return $this->barcode;
  }
}
