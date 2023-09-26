<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbAmazoninfomation
 */
class TbAmazoninfomation extends BaseMallInformation
{
  /**
   * @var string
   */
  private $title;

  /**
   * @var integer
   */
  private $fbaBaika;

  /**
   * @var boolean
   */
  private $fbaFlg;

  /**
   * @var integer
   */
  private $snlBaika;

  /**
   * @var boolean
   */
  private $snlFlg;


  public function getMallInfoForMallProduct()
  {
    $info = parent::getMallInfoForMallProduct();
    $info['fbaBaika'] = $this->getFbaBaika();
    $info['fbaFlg'] = (bool)$this->getFbaFlg();
    $info['snlBaika'] = $this->getSnlBaika();
    $info['snlFlg'] = (bool)$this->getSnlFlg();
    return $info;
  }

  public function getMallName()
  {
    return 'amazon';
  }

  public function getShopName()
  {
    return 'amazon';
  }


  /**
   * Set title
   *
   * @param string $title
   * @return TbAmazoninfomation
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
   * Set fbaBaika
   *
   * @param integer $fbaBaika
   * @return TbAmazoninfomation
   */
  public function setFbaBaika($fbaBaika)
  {
    $this->fbaBaika = $fbaBaika;

    return $this;
  }

  /**
   * Get fbaBaika
   *
   * @return integer 
   */
  public function getFbaBaika()
  {
    return $this->fbaBaika;
  }

  /**
   * Set fbaFlg
   *
   * @param boolean $fbaFlg
   * @return TbAmazoninfomation
   */
  public function setFbaFlg($fbaFlg)
  {
    $this->fbaFlg = $fbaFlg;

    return $this;
  }

  /**
   * Get fbaFlg
   *
   * @return boolean 
   */
  public function getFbaFlg()
  {
    return $this->fbaFlg;
  }

  /**
   * Set snlBaika
   *
   * @param integer $snlBaika
   * @return TbAmazoninfomation
   */
  public function setSnlBaika($snlBaika)
  {
    $this->snlBaika = $snlBaika;

    return $this;
  }

  /**
   * Get snlBaika
   *
   * @return integer 
   */
  public function getSnlBaika()
  {
    return $this->snlBaika;
  }

  /**
   * Set snlFlg
   *
   * @param boolean $snlFlg
   * @return TbAmazoninfomation
   */
  public function setSnlFlg($snlFlg)
  {
    $this->snlFlg = $snlFlg;

    return $this;
  }

  /**
   * Get snlFlg
   *
   * @return boolean 
   */
  public function getSnlFlg()
  {
    return $this->snlFlg;
  }
}
