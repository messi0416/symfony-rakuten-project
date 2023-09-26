<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BaseMallInformation
 */
abstract class BaseMallInformation
{
  /**
   * @var string
   */
  protected $daihyoSyohinCode;

  /**
   * @var integer
   */
  protected $registrationFlg;

  /**
   * @var boolean
   */
  protected $originalPriceFlg;

  /**
   * @var integer
   */
  protected $baikaTanka;


  /**
   * モール名を取得
   *
   * @return string
   */
  abstract public function getMallName();

  /**
   * 店舗名を取得
   *
   * @return string
   */
  abstract public function getShopName();

  /**
   * モール商品画面に必要な情報を連想配列で返す
   *
   * @return Array
   */
  public function getMallInfoForMallProduct()
  {
    return [
      'mall' => $this->getMallName(),
      'shop' => $this->getShopName(),
      'title' => $this->getTitle(),
      'registrationFlg' => (bool)$this->getRegistrationFlg(),
      'baikaTanka' => $this->getBaikaTanka(),
      'originalPriceFlg' => (bool)$this->getOriginalPriceFlg(),
    ];
  }

  /**
   * モール商品情報を更新する
   *
   * @param string $key 更新対象のカラム
   * @param string $value 更新値
   * @return Array 
   */
  public function updateMallProductInfo($key, $value)
  {
    $method = 'set' . ucfirst($key);
    $this->$method($value);
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string 
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyoSyohinCode;
  }

  /**
   * Set registrationFlg
   *
   * @param integer $registrationFlg
   * @return BaseMallInformation
   */
  public function setRegistrationFlg($registrationFlg)
  {
    $this->registrationFlg = $registrationFlg;

    return $this;
  }

  /**
   * Get registrationFlg
   *
   * @return integer 
   */
  public function getRegistrationFlg()
  {
    return $this->registrationFlg;
  }

  /**
   * Set originalPriceFlg
   *
   * @param boolean $originalPriceFlg
   * @return BaseMallInformation
   */
  public function setOriginalPriceFlg($originalPriceFlg)
  {
    $this->originalPriceFlg = $originalPriceFlg;

    return $this;
  }

  /**
   * Get originalPriceFlg
   *
   * @return boolean 
   */
  public function getOriginalPriceFlg()
  {
    return $this->originalPriceFlg;
  }

  /**
   * Set baikaTanka
   *
   * @param integer $baikaTanka
   * @return BaseMallInformation
   */
  public function setBaikaTanka($baikaTanka)
  {
    $this->baikaTanka = $baikaTanka;

    return $this;
  }

  /**
   * Get baikaTanka
   *
   * @return integer 
   */
  public function getBaikaTanka()
  {
    return $this->baikaTanka;
  }
}
