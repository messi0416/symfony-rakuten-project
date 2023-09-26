<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbBoxCode
 */
class TbBoxCode
{
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $boxCode;

  /**
   * @var string
   */
  private $barcode;


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
   * Set boxCode
   *
   * @param string $boxCode
   * @return TbBoxCode
   */
  public function setBoxCode($boxCode)
  {
    $this->boxCode = $boxCode;

    return $this;
  }

  /**
   * Get boxCode
   *
   * @return string 
   */
  public function getBoxCode()
  {
    return $this->boxCode;
  }

  /**
   * Set barcode
   *
   * @param string $barcode
   * @return TbBoxCode
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
