<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenGenre
 */
class TbRakutenGenre
{
  /**
   * @var string
   */
  private $rakutenGenreId;

  /**
   * @var string
   */
  private $firstPathName;

  /**
   * @var string
   */
  private $pathName;

  /**
   * @var integer
   */
  private $unavailableFlg;


  /**
   * Get rakutenGenreId
   *
   * @return string 
   */
  public function getRakutenGenreId()
  {
    return $this->rakutenGenreId;
  }

  /**
   * Set firstPathName
   *
   * @param string $firstPathName
   * @return TbRakutenGenre
   */
  public function setFirstPathName($firstPathName)
  {
    $this->firstPathName = $firstPathName;

    return $this;
  }

  /**
   * Get firstPathName
   *
   * @return string 
   */
  public function getFirstPathName()
  {
    return $this->firstPathName;
  }

  /**
   * Set pathName
   *
   * @param string $pathName
   * @return TbRakutenGenre
   */
  public function setPathName($pathName)
  {
    $this->pathName = $pathName;

    return $this;
  }

  /**
   * Get pathName
   *
   * @return string 
   */
  public function getPathName()
  {
    return $this->pathName;
  }

  /**
   * Set unavailableFlg
   *
   * @param integer $unavailableFlg
   * @return TbRakutenGenre
   */
  public function setUnavailableFlg($unavailableFlg)
  {
    $this->unavailableFlg = $unavailableFlg;

    return $this;
  }

  /**
   * Get unavailableFlg
   *
   * @return integer 
   */
  public function getUnavailableFlg()
  {
    return $this->unavailableFlg;
  }
}
