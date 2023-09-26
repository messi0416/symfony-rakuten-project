<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductchoiceitems
 */
class TbProductchoiceitems
{
  use ArrayTrait;
  use FillTimestampTrait;

  /** @var TbMainproducts */
  private $product;

  /**
   * @return TbMainproducts
   */
  public function getProduct()
  {
    return $this->product;
  }

  /** @var TbShippingdivision */
  private $shippingdivision;

  /**
   * 送料設定が設定されていれば取得する
   * @return \MiscBundle\Entity\TbShippingdivision
   */
  public function getShippingdivision()
  {
    return $this->shippingdivision;
  }

  /**
   * カレントロケーション更新
   * ※実体は tb_product_location (position = 0) の方であり、こちらは転記
   */
  public function updateCurrentLocation($newLocationCode)
  {
    $this->setPreviouslocation($this->getLocation());
    $this->setLocation($newLocationCode);
  }

  /**
   * 倉庫を指定した場合には、その倉庫内のロケーションを取得
   * @param TbWarehouse $warehouse
   * @return \Doctrine\Common\Collections\Collection|\Doctrine\ORM\PersistentCollection|TbProductLocation[]
   */
  public function getActiveLocations($warehouse = null)
  {
    return $this->locations->filter(function($location) use ($warehouse) {
      /** @var TbProductLocation $location  */
      return $location->getPosition() >= 0
          && ($warehouse ? $warehouse->getId() == $location->getLocation()->getWarehouseId() : true)
        ;
    });
  }

  /**
   * @param $warehouse
   * @return int
   */
  public function getWarehouseStock($warehouse)
  {
    $result = 0;
    /** @var TbProductLocation $location */
    foreach($this->getActiveLocations($warehouse)->toArray() as $location) {
      $result += $location->getStock();
    }

    return $result;
  }

  /**
   * @param TbWarehouse $warehouse
   * @return \Doctrine\Common\Collections\Collection|\Doctrine\ORM\PersistentCollection|TbProductLocation[]
   */
  public function getOtherWarehouseLocations($warehouse = null)
  {
    return $this->locations->filter(function($location) use ($warehouse) {
      /** @var TbProductLocation $location  */
      return $location->getPosition() >= 0
      && ($warehouse ? $warehouse->getId() != $location->getLocation()->getWarehouseId() : false)
        ;
    });
  }

  /**
   * @param TbWarehouse $warehouse
   * @return \Doctrine\Common\Collections\Collection|\Doctrine\ORM\PersistentCollection|TbProductLocation[]
   */
  public function getAllLocations($warehouse = null, $current_location = null)
  {
    return $this->locations->filter(function($location) use ($warehouse, $current_location) {
      /** @var TbProductLocation $location  */
      return $location->getPosition() >= 0
      && !(($warehouse ? $warehouse->getId() == $location->getLocation()->getWarehouseId() : false)
      && ($current_location ? $current_location == $location->getLocation()->getLocationCode() : false))
        ;
    });
  }

  /**
   * @param TbWarehouse $warehouse
   * @return int
   */
  public function getMaxLocationPosition($warehouse = null)
  {
    $result = array_reduce($this->locations->toArray(), function($result, $location) use ($warehouse) {

      /** @var TbProductLocation $location */
      if (
           $result < $location->getPosition()
        && ($warehouse ? $warehouse->getId() != $location->getLocation()->getWarehouseId() : false)
      ) {
        return  $location->getPosition();
      } else {
        return $result;
      }
    }, 0);

    return $result;
  }

  /**
   * カラー取得
   */
  public function getColor()
  {
    if ($this->getProduct()->getColorAxis() === 'col') {
      return $this->getColname();
    } else {
      return $this->getRowname();
    }
  }

  /**
   * サイズ取得 ※非カラー選択肢
   */
  public function getSize()
  {
    if ($this->getProduct()->getColorAxis() === 'col') {
      return $this->getRowname();
    } else {
      return $this->getColname();
    }
  }


  /**
   * セット商品 設定SKU内訳数取得
   * ※一覧表示でSQL山盛りになるがシンプルに
   */
  public function getSetDetailsCount()
  {
     return $this->getSetSkuDetails()->count();
  }

  /**
   * セットSKU判定
   */
  public function isSetSku()
  {
    return $this->product ? $this->product->getSetFlg() != 0 : false;
  }


  /**
   * 重量設定漏れ
   * @return bool
   */
  public function isSetWeight()
  {
    return $this->getWeight() > 0;
  }

  /**
   * サイズ設定漏れ
   * @return bool
   */
  public function isSetSize()
  {
    return (
      $this->getDepth() > 0
      && $this->getWidth() > 0
      && $this->getHeight() > 0
    );
  }

  /**
   * 重量設定漏れ
   * @return bool
   */
  public function isSetWeightSize()
  {
    return $this->isSetSize() && $this->isSetWeight();
  }



  // ----------------------------------
  // field properties
  // ----------------------------------

  /**
   * @var int
   */
  private $displayOrder;

  /**
   * @var string
   */
  private $colname;

  /**
   * @var string
   */
  private $colcode;

  /**
   * @var string
   */
  private $rowname;

  /**
   * @var string
   */
  private $rowcode;

  /**
   * @var bool
   */
  private $orderEnabled = '0';

  /**
   * @var int
   */
  private $shippingdivisionId;

  /**
   * @var string
   */
  private $daihyoSyohinCode;

  /**
   * @var int
   */
  private $color_image_id;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set displayOrder
   *
   * @param int $displayOrder
   *
   * @return TbProductchoiceitems
   */
  public function setDisplayOrder($displayOrder)
  {
    $this->displayOrder = $displayOrder;

    return $this;
  }

  /**
   * Get displayOrder
   *
   * @return int
   */
  public function getDisplayOrder()
  {
    return $this->displayOrder;
  }

  /**
   * Set colname
   *
   * @param string $colname
   *
   * @return TbProductchoiceitems
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
   * Set colcode
   *
   * @param string $colcode
   *
   * @return TbProductchoiceitems
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
   * Set rowname
   *
   * @param string $rowname
   *
   * @return TbProductchoiceitems
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
   * Set rowcode
   *
   * @param string $rowcode
   *
   * @return TbProductchoiceitems
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
   * Set orderEnabled
   *
   * @param bool $orderEnabled
   *
   * @return TbProductchoiceitems
   */
  public function setOrderEnabled($orderEnabled)
  {
    $this->orderEnabled = $orderEnabled;

    return $this;
  }

  /**
   * Get orderEnabled
   *
   * @return bool
   */
  public function getOrderEnabled()
  {
    return $this->orderEnabled;
  }

  /**
   * Set shippingdivisionId
   *
   * @param int $shippingdivisionId
   *
   * @return TbProductchoiceitems
   */
  public function setShippingdivisionId($shippingdivisionId)
  {
    $this->shippingdivisionId = $shippingdivisionId;

    return $this;
  }

  /**
   * Get shippingdivisionId
   *
   * @return int
   */
  public function getShippingdivisionId()
  {
    return $this->shippingdivisionId;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TbProductchoiceitems
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyoSyohinCode = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyoSyohinCode;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TbProductchoiceitems
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
   * @return TbProductchoiceitems
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
     * @var string
     */
    private $neSyohinSyohinCode = '-';



    /**
     * Set neSyohinSyohinCode
     *
     * @return string
     */
    public function setNeSyohinSyohinCode($neSyohinSyohinCode)
    {
        $this->neSyohinSyohinCode = $neSyohinSyohinCode;
        return $this;
    }

    /**
     * Get neSyohinSyohinCode
     *
     * @return string
     */
    public function getNeSyohinSyohinCode()
    {
        return $this->neSyohinSyohinCode;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderHistories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderHistories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add orderHistory
     *
     * @param \MiscBundle\Entity\TbIndividualorderhistory $orderHistory
     *
     * @return TbProductchoiceitems
     */
    public function addOrderHistory(\MiscBundle\Entity\TbIndividualorderhistory $orderHistory)
    {
        $this->orderHistories[] = $orderHistory;

        return $this;
    }

    /**
     * Remove orderHistory
     *
     * @param \MiscBundle\Entity\TbIndividualorderhistory $orderHistory
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOrderHistory(\MiscBundle\Entity\TbIndividualorderhistory $orderHistory)
    {
        return $this->orderHistories->removeElement($orderHistory);
    }

    /**
     * Get orderHistories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderHistories()
    {
        return $this->orderHistories;
    }

    /**
     * Set product
     *
     * @param \MiscBundle\Entity\TbMainproducts $product
     *
     * @return TbProductchoiceitems
     */
    public function setProduct(\MiscBundle\Entity\TbMainproducts $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $locations;


    /**
     * Add location
     *
     * @param \MiscBundle\Entity\TbProductLocation $location
     *
     * @return TbProductchoiceitems
     */
    public function addLocation(\MiscBundle\Entity\TbProductLocation $location)
    {
        $this->locations[] = $location;

        return $this;
    }

    /**
     * Remove location
     *
     * @param \MiscBundle\Entity\TbProductLocation $location
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeLocation(\MiscBundle\Entity\TbProductLocation $location)
    {
        return $this->locations->removeElement($location);
    }

    /**
     * Get locations
     *
     * @return \Doctrine\ORM\PersistentCollection|TbProductLocation[]
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @var int
     */
    private $stock = '0';


    /**
     * Set stock
     *
     * @param int $stock
     *
     * @return TbProductchoiceitems
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
     * @var string
     */
    private $location = '_new';

    /**
     * @var int
     */
    private $free_stock = '0';

    /**
     * @var string
     */
    private $previouslocation = '_new';


    /**
     * Set location
     *
     * @param string $location
     *
     * @return TbProductchoiceitems
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set freeStock
     *
     * @param int $freeStock
     *
     * @return TbProductchoiceitems
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
     * Set previouslocation
     *
     * @param string $previouslocation
     *
     * @return TbProductchoiceitems
     */
    public function setPreviouslocation($previouslocation)
    {
        $this->previouslocation = $previouslocation;

        return $this;
    }

    /**
     * Get previouslocation
     *
     * @return string
     */
    public function getPreviouslocation()
    {
        return $this->previouslocation;
    }

    private $warehouseTo;

    /**
     * Set warehouseTo
     *
     * @param \MiscBundle\Entity\TbLocationWarehouseToList $warehouseTo
     *
     * @return TbProductchoiceitems
     */
    public function setWarehouseTo(\MiscBundle\Entity\TbLocationWarehouseToList $warehouseTo = null)
    {
        $this->warehouseTo = $warehouseTo;

        return $this;
    }

    /**
     * Get warehouseTo
     *
     * @return \MiscBundle\Entity\TbLocationWarehouseToList
     */
    public function getWarehouseTo()
    {
        return $this->warehouseTo;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $pickingList;


    /**
     * Add pickingList
     *
     * @param \MiscBundle\Entity\TbDeliveryPickingList $pickingList
     *
     * @return TbProductchoiceitems
     */
    public function addPickingList(\MiscBundle\Entity\TbDeliveryPickingList $pickingList)
    {
        $this->pickingList[] = $pickingList;

        return $this;
    }

    /**
     * Remove pickingList
     *
     * @param \MiscBundle\Entity\TbDeliveryPickingList $pickingList
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePickingList(\MiscBundle\Entity\TbDeliveryPickingList $pickingList)
    {
        return $this->pickingList->removeElement($pickingList);
    }

    /**
     * Get pickingList
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPickingList()
    {
        return $this->pickingList;
    }
    /**
     * @var \MiscBundle\Entity\TbLocationWarehouseToPickingList
     */
    private $warehouseToPickingList;


    /**
     * Set warehouseToPickingList
     *
     * @param \MiscBundle\Entity\TbLocationWarehouseToPickingList $warehouseToPickingList
     *
     * @return TbProductchoiceitems
     */
    public function setWarehouseToPickingList(\MiscBundle\Entity\TbLocationWarehouseToPickingList $warehouseToPickingList = null)
    {
        $this->warehouseToPickingList = $warehouseToPickingList;

        return $this;
    }

    /**
     * Get warehouseToPickingList
     *
     * @return \MiscBundle\Entity\TbLocationWarehouseToPickingList
     */
    public function getWarehouseToPickingList()
    {
        return $this->warehouseToPickingList;
    }
    /**
     * @var int
     */
    private $order_remain_num = '0';


    /**
     * Set orderRemainNum
     *
     * @param int $orderRemainNum
     *
     * @return TbProductchoiceitems
     */
    public function setOrderRemainNum($orderRemainNum)
    {
        $this->order_remain_num = $orderRemainNum;

        return $this;
    }

    /**
     * Get orderRemainNum
     *
     * @return int
     */
    public function getOrderRemainNum()
    {
        return $this->order_remain_num;
    }


    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $setSkuDetails;


    /**
     * Add setSkuDetails
     *
     * @param \MiscBundle\Entity\TbSetProductDetail $setSkuDetails
     * @return TbProductchoiceitems
     */
    public function addSetSkuDetail(\MiscBundle\Entity\TbSetProductDetail $setSkuDetails)
    {
        $this->setSkuDetails[] = $setSkuDetails;

        return $this;
    }

    /**
     * Remove setSkuDetails
     *
     * @param \MiscBundle\Entity\TbSetProductDetail $setSkuDetails
     */
    public function removeSetSkuDetail(\MiscBundle\Entity\TbSetProductDetail $setSkuDetails)
    {
        $this->setSkuDetails->removeElement($setSkuDetails);
    }

    /**
     * Get setSkuDetails
     *
     * @return \Doctrine\Common\Collections\Collection|TbSetProductDetail[]
     */
    public function getSetSkuDetails()
    {
        return $this->setSkuDetails;
    }
    /**
     * @var integer
     */
    private $weight = 0;

    /**
     * @var integer
     */
    private $depth = 0;

    /**
     * @var integer
     */
    private $width = 0;

    /**
     * @var integer
     */
    private $height = 0;

    /**
     * @var text
     */
    private $descriptionEn = null;

    /**
     * @var text
     */
    private $descriptionCn = null;

    /**
     * @var text
     */
    private $hintJa = null;

    /**
     * @var text
     */
    private $hintCn = null;


    /**
     * Set weight
     *
     * @param integer $weight
     * @return TbProductchoiceitems
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return TbProductchoiceitems
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return TbProductchoiceitems
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return TbProductchoiceitems
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set descriptionEn
     *
     * @param string $descriptionEn
     * @return TbProductchoiceitems
     */
    public function setDescriptionEn($descriptionEn)
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    /**
     * Get descriptionEn
     *
     * @return string
     */
    public function getDescriptionEn()
    {
        return $this->descriptionEn;
    }

    /**
     * Set descriptionCn
     *
     * @param string $descriptionCn
     * @return TbProductchoiceitems
     */
    public function setDescriptionCn($descriptionCn)
    {
        $this->descriptionCn = $descriptionCn;

        return $this;
    }

    /**
     * Get descriptionCn
     *
     * @return string
     */
    public function getDescriptionCn()
    {
        return $this->descriptionCn;
    }

    /**
     * Set hintJa
     *
     * @param string $hintJa
     * @return TbProductchoiceitems
     */
    public function setHintJa($hintJa)
    {
        $this->hintJa = $hintJa;

        return $this;
    }

    /**
     * Get hintJa
     *
     * @return string
     */
    public function getHintJa()
    {
        return $this->hintJa;
    }

    /**
     * Set hintCn
     *
     * @param string $hintCn
     * @return TbProductchoiceitems
     */
    public function setHintCn($hintCn)
    {
        $this->hintCn = $hintCn;

        return $this;
    }

    /**
     * Get hintCn
     *
     * @return string
     */
    public function getHintCn()
    {
        return $this->hintCn;
    }

/**
   * Set color_image_id
   *
   * @param string $colorImageId
   *
   * @return ProductImagesVariation
   */
  public function setColorImageId($colorImageId)
  {
    $this->color_image_id = $colorImageId;

    return $this;
  }

  /**
   * Get color_image_id
   *
   * @return string
   */
  public function getColorImageId()
  {
    return $this->color_image_id;
  }

    /**
     * @var string
     */
    private $support_colname = '';

    /**
     * @var string
     */
    private $support_rowname = '';


    /**
     * Set support_colname
     *
     * @param string $supportColname
     * @return TbProductchoiceitems
     */
    public function setSupportColname($supportColname)
    {
        $this->support_colname = $supportColname;

        return $this;
    }

    /**
     * Get support_colname
     *
     * @return string
     */
    public function getSupportColname()
    {
        return $this->support_colname;
    }

    /**
     * Set support_rowname
     *
     * @param string $supportRowname
     * @return TbProductchoiceitems
     */
    public function setSupportRowname($supportRowname)
    {
        $this->support_rowname = $supportRowname;

        return $this;
    }

    /**
     * Get support_rowname
     *
     * @return string
     */
    public function getSupportRowname()
    {
        return $this->support_rowname;
    }
}
