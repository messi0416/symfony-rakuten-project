<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbStockChangeHistoryExcludeProduct
 */
class TbStockChangeHistoryExcludeProduct
{
  /**
   * @var string
   */
  private $daihyoSyohinCode;


  /**
   * Get daihyoSyohinCode
   *
   * @return string 
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyoSyohinCode;
  }
}
