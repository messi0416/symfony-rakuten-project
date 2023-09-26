<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbStockHistory
 */
class TbStockHistory
{
  /**
   * @var integer
   */
  private $id;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->stockHistory = new \Doctrine\Common\Collections\ArrayCollection();
  }

  /**
   * Get id
   *
   * @return integer 
   */
  public function getId()
  {
    return $this->id;
  }
}
