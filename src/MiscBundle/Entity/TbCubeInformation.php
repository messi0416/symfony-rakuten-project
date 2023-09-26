<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbCubeinformation
 */
class TbCubeInformation extends BaseMallInformation
{
  /**
   * @var string
   */
  private $title;


  public function getMallName()
  {
    return 'cube';
  }

  public function getShopName()
  {
    return 'cube';
  }

  /**
   * Set title
   *
   * @param string $title
   * @return TbCubeInformation
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
