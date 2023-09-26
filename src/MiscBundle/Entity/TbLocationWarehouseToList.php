<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\Repository\TbMainproductsRepository;

/**
 * TbLocationWarehouseToList
 */
class TbLocationWarehouseToList
{
  /**
   * 値下げ許可 表示用文言
   */
  public function getPricedownFlgDisplay()
  {
    return $this->getPricedownFlg() == 0 ? '不許可' : '許可';
  }

  /**
   * 値下げシーズン 表示用文言
   */
  public function getSalesSeasonDisplay()
  {
    return $this->getSalesSeason() == 0 ? 'OFF' : 'ON';
  }


  /**
   * 画像URL取得（サムネイル用）
   */
  public function getImageUrl($parentPath = '')
  {
    return TbMainproductsRepository::createImageUrl($this->getPictDirectory(), $this->getPictFilename(), $parentPath);
  }

  // -------------------------------

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var string
   */
  private $daihyo_syohin_code = '';

  /**
   * @var string
   */
  private $colcode = '';

  /**
   * @var string
   */
  private $rowcode = '';

  /**
   * @var string
   */
  private $colname = '';

  /**
   * @var string
   */
  private $rowname = '';

  /**
   * @var int
   */
  private $sort_order = 0;

  /**
   * @var int
   */
  private $stock = 0;

  /**
   * @var int
   */
  private $allocation_num = 0;

  /**
   * @var int
   */
  private $free_stock = 0;

  /**
   * @var int
   */
  private $order_num = 0;

  /**
   * @var int
   */
  private $reserve_num = 0;

  /**
   * @var int
   */
  private $warehouse_total = 0;

  /**
   * @var int
   */
  private $warehouse_stock = 0;

  /**
   * @var int
   */
  private $move_num = 0;

  /**
   * @var int
   */
  private $pricedown_flg = 0;

  /**
   * @var string
   */
  private $pict_directory = '';

  /**
   * @var string
   */
  private $pict_filename = '';


  /**
   * Set neSyohinSyohinCode
   *
   * @param string $neSyohinSyohinCode
   *
   * @return TbLocationWarehouseToList
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get neSyohinSyohinCode
   *
   * @return string
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->ne_syohin_syohin_code;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TbLocationWarehouseToList
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

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
   * Set colcode
   *
   * @param string $colcode
   *
   * @return TbLocationWarehouseToList
   */
  public function setColcode($colcode)
  {
    $this->colcode = $colcode;

    return $this;
  }

  /**
   * Get colcode
   *
   * @return string
   */
  public function getColcode()
  {
    return $this->colcode;
  }

  /**
   * Set rowcode
   *
   * @param string $rowcode
   *
   * @return TbLocationWarehouseToList
   */
  public function setRowcode($rowcode)
  {
    $this->rowcode = $rowcode;

    return $this;
  }

  /**
   * Get rowcode
   *
   * @return string
   */
  public function getRowcode()
  {
    return $this->rowcode;
  }

  /**
   * Set colname
   *
   * @param string $colname
   *
   * @return TbLocationWarehouseToList
   */
  public function setColname($colname)
  {
    $this->colname = $colname;

    return $this;
  }

  /**
   * Get colname
   *
   * @return string
   */
  public function getColname()
  {
    return $this->colname;
  }

  /**
   * Set rowname
   *
   * @param string $rowname
   *
   * @return TbLocationWarehouseToList
   */
  public function setRowname($rowname)
  {
    $this->rowname = $rowname;

    return $this;
  }

  /**
   * Get rowname
   *
   * @return string
   */
  public function getRowname()
  {
    return $this->rowname;
  }

  /**
   * Set sortOrder
   *
   * @param int $sortOrder
   *
   * @return TbLocationWarehouseToList
   */
  public function setSortOrder($sortOrder)
  {
    $this->sort_order = $sortOrder;

    return $this;
  }

  /**
   * Get sortOrder
   *
   * @return int
   */
  public function getSortOrder()
  {
    return $this->sort_order;
  }

  /**
   * Set stock
   *
   * @param int $stock
   *
   * @return TbLocationWarehouseToList
   */
  public function setStock($stock)
  {
    $this->stock = $stock;

    return $this;
  }

  /**
   * Get stock
   *
   * @return int
   */
  public function getStock()
  {
    return $this->stock;
  }

  /**
   * Set allocationNum
   *
   * @param int $allocationNum
   *
   * @return TbLocationWarehouseToList
   */
  public function setAllocationNum($allocationNum)
  {
    $this->allocation_num = $allocationNum;

    return $this;
  }

  /**
   * Get allocationNum
   *
   * @return int
   */
  public function getAllocationNum()
  {
    return $this->allocation_num;
  }

  /**
   * Set freeStock
   *
   * @param int $freeStock
   *
   * @return TbLocationWarehouseToList
   */
  public function setFreeStock($freeStock)
  {
    $this->free_stock = $freeStock;

    return $this;
  }

  /**
   * Get freeStock
   *
   * @return int
   */
  public function getFreeStock()
  {
    return $this->free_stock;
  }

  /**
   * Set orderNum
   *
   * @param int $orderNum
   *
   * @return TbLocationWarehouseToList
   */
  public function setOrderNum($orderNum)
  {
    $this->order_num = $orderNum;

    return $this;
  }

  /**
   * Get orderNum
   *
   * @return int
   */
  public function getOrderNum()
  {
    return $this->order_num;
  }

  /**
   * Set reserveNum
   *
   * @param int $reserveNum
   *
   * @return TbLocationWarehouseToList
   */
  public function setReserveNum($reserveNum)
  {
    $this->reserve_num = $reserveNum;

    return $this;
  }

  /**
   * Get reserveNum
   *
   * @return int
   */
  public function getReserveNum()
  {
    return $this->reserve_num;
  }

  /**
   * Set warehouseTotal
   *
   * @param int $warehouseTotal
   *
   * @return TbLocationWarehouseToList
   */
  public function setWarehouseTotal($warehouseTotal)
  {
    $this->warehouse_total = $warehouseTotal;

    return $this;
  }

  /**
   * Get warehouseTotal
   *
   * @return int
   */
  public function getWarehouseTotal()
  {
    return $this->warehouse_total;
  }

  /**
   * Set warehouseStock
   *
   * @param int $warehouseStock
   *
   * @return TbLocationWarehouseToList
   */
  public function setWarehouseStock($warehouseStock)
  {
    $this->warehouse_stock = $warehouseStock;

    return $this;
  }

  /**
   * Get warehouseStock
   *
   * @return int
   */
  public function getWarehouseStock()
  {
    return $this->warehouse_stock;
  }

  /**
   * Set moveNum
   *
   * @param int $moveNum
   *
   * @return TbLocationWarehouseToList
   */
  public function setMoveNum($moveNum)
  {
    $this->move_num = $moveNum;

    return $this;
  }

  /**
   * Get moveNum
   *
   * @return int
   */
  public function getMoveNum()
  {
    return $this->move_num;
  }

  /**
   * Set pricedownFlg
   *
   * @param int $pricedownFlg
   *
   * @return TbLocationWarehouseToList
   */
  public function setPricedownFlg($pricedownFlg)
  {
    $this->pricedown_flg = $pricedownFlg;

    return $this;
  }

  /**
   * Get pricedownFlg
   *
   * @return int
   */
  public function getPricedownFlg()
  {
    return $this->pricedown_flg;
  }

  /**
   * Set pictDirectory
   *
   * @param string $pictDirectory
   *
   * @return TbLocationWarehouseToList
   */
  public function setPictDirectory($pictDirectory)
  {
    $this->pict_directory = $pictDirectory;

    return $this;
  }

  /**
   * Get pictDirectory
   *
   * @return string
   */
  public function getPictDirectory()
  {
    return $this->pict_directory;
  }

  /**
   * Set pictFilename
   *
   * @param string $pictFilename
   *
   * @return TbLocationWarehouseToList
   */
  public function setPictFilename($pictFilename)
  {
    $this->pict_filename = $pictFilename;

    return $this;
  }

  /**
   * Get pictFilename
   *
   * @return string
   */
  public function getPictFilename()
  {
    return $this->pict_filename;
  }
    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;


    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return TbLocationWarehouseToList
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return TbLocationWarehouseToList
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @var \MiscBundle\Entity\TbProductchoiceitems
     */
    private $choiceItem;


    /**
     * Set choiceItem
     *
     * @param \MiscBundle\Entity\TbProductchoiceitems $choiceItem
     *
     * @return TbLocationWarehouseToList
     */
    public function setChoiceItem(\MiscBundle\Entity\TbProductchoiceitems $choiceItem = null)
    {
        $this->choiceItem = $choiceItem;

        return $this;
    }

    /**
     * Get choiceItem
     *
     * @return \MiscBundle\Entity\TbProductchoiceitems
     */
    public function getChoiceItem()
    {
        return $this->choiceItem;
    }
    /**
     * @var int
     */
    private $sales_season = 0;


    /**
     * Set salesSeason
     *
     * @param int $salesSeason
     *
     * @return TbLocationWarehouseToList
     */
    public function setSalesSeason($salesSeason)
    {
        $this->sales_season = $salesSeason;

        return $this;
    }

    /**
     * Get salesSeason
     *
     * @return int
     */
    public function getSalesSeason()
    {
        return $this->sales_season;
    }
}
