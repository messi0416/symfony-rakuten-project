<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BaseRakutenMallInformation
 */
abstract class BaseRakutenMallInformation extends BaseMallInformation
{
  /**
   * @var string
   */
  protected $title;

  /**
   * @var integer
   */
  protected $warehouseFlg;

  /**
   * @var boolean
   */
  protected $warehouseStoredFlg;

  /**
   * @var string
   */
  protected $displayPrice;

  /**
   * @var string
   */
  protected $dualPriceControlNumber;

  /**
   * @var string
   */
  protected $productDescriptionPC;

  /**
   * @var string
   */
  protected $productDescriptionSP;

  /**
   * @var string
   */
  protected $salesDescriptionPC;


  public function getMallInfoForMallProduct()
  {
    $info = parent::getMallInfoForMallProduct();
    $info['warehouseFlg'] = (bool)$this->getWarehouseFlg();
    $info['warehouseStoredFlg'] = (bool)$this->getWarehouseStoredFlg();
    $info['displayPrice'] = $this->getDisplayPrice();
    $info['dualPriceControlNumber'] = $this->getDualPriceControlNumber();
    $info['productDescriptionPC'] = $this->getProductDescriptionPC();
    $info['productDescriptionSP'] = $this->getProductDescriptionSP();
    $info['salesDescriptionPC'] = $this->getSalesDescriptionPC();
    return $info;
  }

  public function getMallName()
  {
    return 'rakuten';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return BaseRakutenMallInformation
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
   * Set warehouseFlg
   *
   * @param integer $warehouseFlg
   * @return BaseRakutenMallInformation
   */
  public function setWarehouseFlg($warehouseFlg)
  {
    $this->warehouseFlg = $warehouseFlg;

    return $this;
  }

  /**
   * Get warehouseFlg
   *
   * @return integer 
   */
  public function getWarehouseFlg()
  {
    return $this->warehouseFlg;
  }

  /**
   * Set warehouseStoredFlg
   *
   * @param boolean $warehouseStoredFlg
   * @return BaseRakutenMallInformation
   */
  public function setWarehouseStoredFlg($warehouseStoredFlg)
  {
    $this->warehouseStoredFlg = $warehouseStoredFlg;

    return $this;
  }

  /**
   * Get warehouseStoredFlg
   *
   * @return boolean 
   */
  public function getWarehouseStoredFlg()
  {
    return $this->warehouseStoredFlg;
  }

  /**
   * Set displayPrice
   *
   * @param string $displayPrice
   * @return BaseRakutenMallInformation
   */
  public function setDisplayPrice($displayPrice)
  {
    $this->displayPrice = $displayPrice;

    return $this;
  }

  /**
   * Get displayPrice
   *
   * @return string 
   */
  public function getDisplayPrice()
  {
    return $this->displayPrice;
  }

  /**
   * Set dualPriceControlNumber
   *
   * @param string $dualPriceControlNumber
   * @return BaseRakutenMallInformation
   */
  public function setDualPriceControlNumber($dualPriceControlNumber)
  {
    $this->dualPriceControlNumber = $dualPriceControlNumber;

    return $this;
  }

  /**
   * Get dualPriceControlNumber
   *
   * @return string 
   */
  public function getDualPriceControlNumber()
  {
    return $this->dualPriceControlNumber;
  }

  /**
   * Set productDescriptionPC
   *
   * @param string $productDescriptionPC
   * @return BaseRakutenMallInformation
   */
  public function setProductDescriptionPC($productDescriptionPC)
  {
    $this->productDescriptionPC = $productDescriptionPC;

    return $this;
  }

  /**
   * Get productDescriptionPC
   *
   * @return string 
   */
  public function getProductDescriptionPC()
  {
    return $this->productDescriptionPC;
  }

  /**
   * Set productDescriptionSP
   *
   * @param string $productDescriptionSP
   * @return BaseRakutenMallInformation
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
   * Set salesDescriptionPC
   *
   * @param string $salesDescriptionPC
   * @return BaseRakutenMallInformation
   */
  public function setSalesDescriptionPC($salesDescriptionPC)
  {
    $this->salesDescriptionPC = $salesDescriptionPC;

    return $this;
  }

  /**
   * Get salesDescriptionPC
   *
   * @return string 
   */
  public function getSalesDescriptionPC()
  {
    return $this->salesDescriptionPC;
  }
}
