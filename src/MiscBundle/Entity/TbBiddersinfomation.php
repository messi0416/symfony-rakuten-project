<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbBiddersinfomation
 */
class TbBiddersinfomation extends BaseMallInformation
{
  /**
   * @var string
   */
  private $title;

  /**
   * @var string
   */
  private $searchKeyword1;

  /**
   * @var string
   */
  private $searchKeyword2;

  /**
   * @var string
   */
  private $searchKeyword3;


  public function getMallInfoForMallProduct()
  {
    $info = parent::getMallInfoForMallProduct();
    $info['searchKeyword1'] = $this->getSearchKeyword1();
    $info['searchKeyword2'] = $this->getSearchKeyword2();
    $info['searchKeyword3'] = $this->getSearchKeyword3();
    return $info;
  }

  public function getMallName()
  {
    return 'wowma';
  }

  public function getShopName()
  {
    return 'wowma';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return TbBiddersinfomation
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
   * Set searchKeyword1
   *
   * @param string $searchKeyword1
   * @return TbBiddersinfomation
   */
  public function setSearchKeyword1($searchKeyword1)
  {
    $this->searchKeyword1 = $searchKeyword1;

    return $this;
  }

  /**
   * Get searchKeyword1
   *
   * @return string 
   */
  public function getSearchKeyword1()
  {
    return $this->searchKeyword1;
  }

  /**
   * Set searchKeyword2
   *
   * @param string $searchKeyword2
   * @return TbBiddersinfomation
   */
  public function setSearchKeyword2($searchKeyword2)
  {
    $this->searchKeyword2 = $searchKeyword2;

    return $this;
  }

  /**
   * Get searchKeyword2
   *
   * @return string 
   */
  public function getSearchKeyword2()
  {
    return $this->searchKeyword2;
  }

  /**
   * Set searchKeyword3
   *
   * @param string $searchKeyword3
   * @return TbBiddersinfomation
   */
  public function setSearchKeyword3($searchKeyword3)
  {
    $this->searchKeyword3 = $searchKeyword3;

    return $this;
  }

  /**
   * Get searchKeyword3
   *
   * @return string 
   */
  public function getSearchKeyword3()
  {
    return $this->searchKeyword3;
  }
}
