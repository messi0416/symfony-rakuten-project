<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShoplistInformation
 */
class TbShoplistInformation extends BaseMallInformation
{
  /**
   * @var string
   */
  private $title;


  public function getMallName()
  {
    return 'shoplist';
  }

  public function getShopName()
  {
    return 'shoplist';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return TbShoplistInformation
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
}
