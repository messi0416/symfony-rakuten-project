<?php

namespace MiscBundle\Entity;

/**
 * 仮想エンティティ 商品別原価率一覧表 データ
 * VProductCostRateItem
 */
class VProductCostRateItem
{
  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $sire_code;

  /**
   * @var string
   */
  private $syohin_kbn = '10';

  /**
   * @var int
   */
  private $genka_tnk;

  /**
   * @var string
   */
  private $daihyo_syohin_name;

  /**
   * @var int
   */
  private $stock_num = '0';

  /**
   * @var int
   */
  private $stock_cost = '0';

  /**
   * @var int
   */
  private $weight = '0';

  /**
   * @var int
   */
  private $additional_cost = '0';

  /**
   * @var int
   */
  private $baika_tnk = '0';

  /**
   * Get daihyoSyohinCode
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set sireCode
   *
   * @param string $sireCode
   *
   * @return VProductCostRateItem
   */
  public function setSireCode($sireCode)
  {
    $this->sire_code = $sireCode;

    return $this;
  }

  /**
   * Get sireCode
   *
   * @return string
   */
  public function getSireCode()
  {
    return $this->sire_code;
  }

  /**
   * Set syohinKbn
   *
   * @param string $syohinKbn
   *
   * @return VProductCostRateItem
   */
  public function setSyohinKbn($syohinKbn)
  {
    $this->syohin_kbn = $syohinKbn;

    return $this;
  }

  /**
   * Get syohinKbn
   *
   * @return string
   */
  public function getSyohinKbn()
  {
    return $this->syohin_kbn;
  }

  /**
   * Set genkaTnk
   *
   * @param int $genkaTnk
   *
   * @return VProductCostRateItem
   */
  public function setGenkaTnk($genkaTnk)
  {
    $this->genka_tnk = $genkaTnk;

    return $this;
  }

  /**
   * Get genkaTnk
   *
   * @return int
   */
  public function getGenkaTnk()
  {
    return $this->genka_tnk;
  }

  /**
   * Set daihyoSyohinName
   *
   * @param string $daihyoSyohinName
   *
   * @return VProductCostRateItem
   */
  public function setDaihyoSyohinName($daihyoSyohinName)
  {
    $this->daihyo_syohin_name = $daihyoSyohinName;

    return $this;
  }

  /**
   * Get daihyoSyohinName
   *
   * @return string
   */
  public function getDaihyoSyohinName()
  {
    return $this->daihyo_syohin_name;
  }

  /**
   * Set stockNum
   *
   * @param int $stockNum
   *
   * @return VProductCostRateItem
   */
  public function setStockNum($stockNum)
  {
    $this->stock_num = $stockNum;

    return $this;
  }

  /**
   * Get stockNum
   *
   * @return int
   */
  public function getStockNum()
  {
    return $this->stock_num;
  }

  /**
   * Set stockCost
   *
   * @param int $stockCost
   *
   * @return VProductCostRateItem
   */
  public function setStockCost($stockCost)
  {
    $this->stock_cost = $stockCost;

    return $this;
  }

  /**
   * Get stockCost
   *
   * @return int
   */
  public function getStockCost()
  {
    return $this->stock_cost;
  }

  /**
   * Set weight
   *
   * @param int $weight
   *
   * @return VProductCostRateItem
   */
  public function setWeight($weight)
  {
    $this->weight = $weight;

    return $this;
  }

  /**
   * Get weight
   *
   * @return int
   */
  public function getWeight()
  {
    return $this->weight;
  }

  /**
   * Set additionalCost
   *
   * @param int $additionalCost
   *
   * @return VProductCostRateItem
   */
  public function setAdditionalCost($additionalCost)
  {
    $this->additional_cost = $additionalCost;

    return $this;
  }

  /**
   * Get additionalCost
   *
   * @return int
   */
  public function getAdditionalCost()
  {
    return $this->additional_cost;
  }

  /**
   * Set baikaTnk
   *
   * @param int $baikaTnk
   *
   * @return VProductCostRateItem
   */
  public function setBaikaTnk($baikaTnk)
  {
    $this->baika_tnk = $baikaTnk;

    return $this;
  }

  /**
   * Get baikaTnk
   *
   * @return int
   */
  public function getBaikaTnk()
  {
    return $this->baika_tnk;
  }
}
