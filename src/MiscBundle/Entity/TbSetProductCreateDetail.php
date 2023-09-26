<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSetProductCreateDetail
 */
class TbSetProductCreateDetail
{
  /**
   * @var integer
   */
  private $list_id;

  /**
   * @var string
   */
  private $detail_sku;

  /**
   * @var integer
   */
  private $detail_free_stock = 0;

  /**
   * @var integer
   */
  private $detail_num = 0;

  /**
   * @var integer
   */
  private $create_num = 0;

  /**
   * @var integer
   */
  private $picking_num = 0;


  /**
   * Set list_id
   *
   * @param integer $listId
   * @return TbSetProductCreateDetail
   */
  public function setListId($listId)
  {
    $this->list_id = $listId;

    return $this;
  }

  /**
   * Get list_id
   *
   * @return integer 
   */
  public function getListId()
  {
    return $this->list_id;
  }

  /**
   * Set detail_sku
   *
   * @param string $detailSku
   * @return TbSetProductCreateDetail
   */
  public function setDetailSku($detailSku)
  {
    $this->detail_sku = $detailSku;

    return $this;
  }

  /**
   * Get detail_sku
   *
   * @return string 
   */
  public function getDetailSku()
  {
    return $this->detail_sku;
  }

  /**
   * Set detail_free_stock
   *
   * @param integer $detailFreeStock
   * @return TbSetProductCreateDetail
   */
  public function setDetailFreeStock($detailFreeStock)
  {
    $this->detail_free_stock = $detailFreeStock;

    return $this;
  }

  /**
   * Get detail_free_stock
   *
   * @return integer 
   */
  public function getDetailFreeStock()
  {
    return $this->detail_free_stock;
  }

  /**
   * Set detail_num
   *
   * @param integer $detailNum
   * @return TbSetProductCreateDetail
   */
  public function setDetailNum($detailNum)
  {
    $this->detail_num = $detailNum;

    return $this;
  }

  /**
   * Get detail_num
   *
   * @return integer 
   */
  public function getDetailNum()
  {
    return $this->detail_num;
  }

  /**
   * Set create_num
   *
   * @param integer $createNum
   * @return TbSetProductCreateDetail
   */
  public function setCreateNum($createNum)
  {
    $this->create_num = $createNum;

    return $this;
  }

  /**
   * Get create_num
   *
   * @return integer 
   */
  public function getCreateNum()
  {
    return $this->create_num;
  }

  /**
   * Set picking_num
   *
   * @param integer $pickingNum
   * @return TbSetProductCreateDetail
   */
  public function setPickingNum($pickingNum)
  {
    $this->picking_num = $pickingNum;

    return $this;
  }

  /**
   * Get picking_num
   *
   * @return integer 
   */
  public function getPickingNum()
  {
    return $this->picking_num;
  }
}
