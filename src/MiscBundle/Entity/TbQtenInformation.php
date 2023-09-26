<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbQtenInformation
 */
class TbQtenInformation extends BaseMallInformation
{
  /**
   * @var string
   */
  private $title;

  /**
   * @var string
   */
  private $q10Itemcode;

  /**
   * @var string
   */
  private $freeExplanation;


  public function getMallInfoForMallProduct()
  {
    $info = parent::getMallInfoForMallProduct();
    $info['q10Itemcode'] = $this->getQ10Itemcode();
    $info['freeExplanation'] = $this->getFreeExplanation();
    return $info;
  }

  public function getMallName()
  {
    return 'q10';
  }

  public function getShopName()
  {
    return 'q10';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return TbQtenInformation
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get title
   *
   * @return string 
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set q10Itemcode
   *
   * @param string $q10Itemcode
   * @return TbQtenInformation
   */
  public function setQ10Itemcode($q10Itemcode)
  {
    $this->q10Itemcode = $q10Itemcode;

    return $this;
  }

  /**
   * Get q10Itemcode
   *
   * @return string 
   */
  public function getQ10Itemcode()
  {
    return $this->q10Itemcode;
  }

  /**
   * Set freeExplanation
   *
   * @param string $freeExplanation
   * @return TbQtenInformation
   */
  public function setFreeExplanation($freeExplanation)
  {
    $this->freeExplanation = $freeExplanation;

    return $this;
  }

  /**
   * Get freeExplanation
   *
   * @return string 
   */
  public function getFreeExplanation()
  {
    return $this->freeExplanation;
  }
}
