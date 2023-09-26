<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BaseYahooMallInformation
 */
abstract class BaseYahooMallInformation extends BaseMallInformation
{
  /**
   * @var string
   */
  protected $title;

  /**
   * @var string
   */
  protected $inputCaption;

  /**
   * @var string
   */
  protected $inputSpAdditional;

  /**
   * @var string
   */
  protected $prRate;


  public function getMallInfoForMallProduct()
  {
    $prRate = $this->getPrRate();

    $info = parent::getMallInfoForMallProduct();
    $info['prRate'] = is_null($prRate) ? $prRate : (float)$prRate;
    $info['inputCaption'] = $this->getInputCaption();
    $info['inputSpAdditional'] = $this->getInputSpAdditional();
    return $info;
  }

  public function getMallName()
  {
    return 'yahoo';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return BaseYahooMallInformation
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
   * Set inputCaption
   *
   * @param string $inputCaption
   * @return BaseYahooMallInformation
   */
  public function setInputCaption($inputCaption)
  {
    $this->inputCaption = $inputCaption;

    return $this;
  }

  /**
   * Get inputCaption
   *
   * @return string 
   */
  public function getInputCaption()
  {
    return $this->inputCaption;
  }

  /**
   * Set inputSpAdditional
   *
   * @param string $inputSpAdditional
   * @return BaseYahooMallInformation
   */
  public function setInputSpAdditional($inputSpAdditional)
  {
    $this->inputSpAdditional = $inputSpAdditional;

    return $this;
  }

  /**
   * Get inputSpAdditional
   *
   * @return string 
   */
  public function getInputSpAdditional()
  {
    return $this->inputSpAdditional;
  }

  /**
   * Set prRate
   *
   * @param string $prRate
   * @return BaseYahooMallInformation
   */
  public function setPrRate($prRate)
  {
    $this->prRate = $prRate;

    return $this;
  }

  /**
   * Get prRate
   *
   * @return string 
   */
  public function getPrRate()
  {
    return $this->prRate;
  }
}
