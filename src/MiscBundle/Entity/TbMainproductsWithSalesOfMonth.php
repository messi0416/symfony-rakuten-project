<?php

namespace MiscBundle\Entity;

/**
 * TbMainproducts
 */
class TbMainproductsWithSalesOfMonth Extends MappedSuperClassTbMainproducts
{

  /// 臨時フィールド
  /** @var int */
  private $salesOfMonth = 0;
  public function setSalesOfMonth($sales) { $this->salesOfMonth = $sales; }
  public function getSalesOfMonth() { return $this->salesOfMonth; }

}
