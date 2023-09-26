<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbPpmInformation
 */
class TbPpmInformation extends BaseMallInformation
{
  /**
   * @var string
   */
  private $title;

  /**
   * @var string
   */
  private $productDescription1;

  /**
   * @var string
   */
  private $productDescription2;

  /**
   * @var string
   */
  private $productDescriptionSP;

  /**
   * @var string
   */
  private $productDescriptionText;


  public function getMallInfoForMallProduct()
  {
    $info = parent::getMallInfoForMallProduct();
    $info['productDescription1'] = $this->getProductDescription1();
    $info['productDescription2'] = $this->getProductDescription2();
    $info['productDescriptionSP'] = $this->getProductDescriptionSP();
    $info['productDescriptionText'] = $this->getProductDescriptionText();
    return $info;
  }

  public function getMallName()
  {
    return 'ppm';
  }

  public function getShopName()
  {
    return 'ppm';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return TbPpmInformation
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
   * Set productDescription1
   *
   * @param string $productDescription1
   * @return TbPpmInformation
   */
  public function setProductDescription1($productDescription1)
  {
    $this->productDescription1 = $productDescription1;

    return $this;
  }

  /**
   * Get productDescription1
   *
   * @return string 
   */
  public function getProductDescription1()
  {
    return $this->productDescription1;
  }

  /**
   * Set productDescription2
   *
   * @param string $productDescription2
   * @return TbPpmInformation
   */
  public function setProductDescription2($productDescription2)
  {
    $this->productDescription2 = $productDescription2;

    return $this;
  }

  /**
   * Get productDescription2
   *
   * @return string 
   */
  public function getProductDescription2()
  {
    return $this->productDescription2;
  }

  /**
   * Set productDescriptionSP
   *
   * @param string $productDescriptionSP
   * @return TbPpmInformation
   */
  public function setProductDescriptionSP($productDescriptionSP)
  {
    $this->productDescriptionSP = $productDescriptionSP;

    return $this;
  }

  /**
   * Get productDescriptionSP
   *
   * @return string 
   */
  public function getProductDescriptionSP()
  {
    return $this->productDescriptionSP;
  }

  /**
   * Set productDescriptionText
   *
   * @param string $productDescriptionText
   * @return TbPpmInformation
   */
  public function setProductDescriptionText($productDescriptionText)
  {
    $this->productDescriptionText = $productDescriptionText;

    return $this;
  }

  /**
   * Get productDescriptionText
   *
   * @return string 
   */
  public function getProductDescriptionText()
  {
    return $this->productDescriptionText;
  }
}
