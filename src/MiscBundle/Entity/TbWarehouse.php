<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\TbWarehouseRepository;

/**
 * TbWarehouse
 */
class TbWarehouse
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * 出荷可否
   * @return bool
   */
  public function isShipmentEnabled()
  {
    return $this->getShipmentEnabled() != 0;
  }

  /**
   * 出荷可否 表示用文言
   * @return string
   */
  public function getShipmentEnabledDisplay()
  {
    return $this->isShipmentEnabled() ? '○' : '×';
  }

  /**
   * 販売可否
   * @return bool
   */
  public function isSaleEnabled()
  {
    return $this->getSaleEnabled() != 0;
  }

  /**
   * 販売可否 表示用文言
   * @return string
   */
  public function getSaleEnabledDisplay()
  {
    return $this->isSaleEnabled() ? '○' : '×';
  }

  /**
   * SHOPLIST対応
   * @return bool
   */
  public function isShoplistEnabled()
  {
    return $this->getShoplistFlag() != 0;
  }

  /**
   * SHOPLIST対応 表示用文言
   * @return string
   */
  public function getShoplistEnabledDisplay()
  {
    return $this->isSaleEnabled() ? '○' : '×';
  }


  /**
   * FBA倉庫か
   */
  public function isFbaVirtualWarehouse()
  {
    return $this->getId() === TbWarehouseRepository::FBA_MULTI_WAREHOUSE_ID;
  }

  /**
   * 藪吉か
   */
  public function isYabuyoshiWarehouse()
  {
    return $this->getId() === TbWarehouseRepository::YABUYOSHI_WAREHOUSE_ID;
  }


  // -------------------------------------
  // properties
  // -------------------------------------

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $symbol = '';

  /**
   * @var integer
   */
  private $display_order = 9999;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set name
   *
   * @param string $name
   * @return TbWarehouse
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set symbol
   *
   * @param string $symbol
   * @return TbWarehouse
   */
  public function setSymbol($symbol)
  {
    $this->symbol = $symbol;

    return $this;
  }

  /**
   * Get symbol
   *
   * @return string
   */
  public function getSymbol()
  {
    return $this->symbol;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbWarehouse
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
   * @return TbWarehouse
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
   * Set display_order
   *
   * @param integer $displayOrder
   * @return TbWarehouse
   */
  public function setDisplayOrder($displayOrder)
  {
      $this->display_order = $displayOrder;

      return $this;
  }

  /**
   * Get display_order
   *
   * @return integer
   */
  public function getDisplayOrder()
  {
      return $this->display_order;
  }

  /**
   * @var integer
   */
  private $shipment_enabled = 0;

  /**
   * @var integer
   */
  private $shipment_priority = 0;


  /**
   * Set shipment_enabled
   *
   * @param integer $shipmentEnabled
   * @return TbWarehouse
   */
  public function setShipmentEnabled($shipmentEnabled)
  {
    $this->shipment_enabled = (bool)$shipmentEnabled ? -1 : 0;

    return $this;
  }

  /**
   * Get shipment_enabled
   *
   * @return integer
   */
  public function getShipmentEnabled()
  {
    return $this->shipment_enabled;
  }

  /**
   * Set shipment_priority
   *
   * @param integer $shipmentPriority
   * @return TbWarehouse
   */
  public function setShipmentPriority($shipmentPriority)
  {
    $this->shipment_priority = $shipmentPriority;

    return $this;
  }

  /**
   * Get shipment_priority
   *
   * @return integer
   */
  public function getShipmentPriority()
  {
    return $this->shipment_priority;
  }
    /**
     * @var integer
     */
    private $sale_enabled = 0;

    /**
     * @var integer
     */
    private $transport_priority = 0;


    /**
     * Set sale_enabled
     *
     * @param integer $saleEnabled
     * @return TbWarehouse
     */
    public function setSaleEnabled($saleEnabled)
    {
        $this->sale_enabled = (bool)$saleEnabled ? -1 : 0;

        return $this;
    }

    /**
     * Get sale_enabled
     *
     * @return integer
     */
    public function getSaleEnabled()
    {
        return $this->sale_enabled;
    }

    /**
     * Set transport_priority
     *
     * @param integer $transportPriority
     * @return TbWarehouse
     */
    public function setTransportPriority($transportPriority)
    {
        $this->transport_priority = $transportPriority;

        return $this;
    }

    /**
     * Get transport_priority
     *
     * @return integer
     */
    public function getTransportPriority()
    {
        return $this->transport_priority;
    }

    /**
     * @var integer
     */
    private $fba_transport_priority = 0;


    /**
     * Set fba_transport_priority
     *
     * @param integer $fbaTransportPriority
     * @return TbWarehouse
     */
    public function setFbaTransportPriority($fbaTransportPriority)
    {
        $this->fba_transport_priority = $fbaTransportPriority;

        return $this;
    }

    /**
     * Get fba_transport_priority
     *
     * @return integer
     */
    public function getFbaTransportPriority()
    {
        return $this->fba_transport_priority;
    }

    /**
     * @var integer
     */
    private $shoplist_flag = 0;

    /**
     * Set shoplist_flag
     *
     * @param integer $shoplistFlag
     * @return TbWarehouse
     */
    public function setShoplistFlag($shoplistFlag)
    {
        $this->shoplist_flag = $shoplistFlag;

        return $this;
    }

    /**
     * Get shoplist_flag
     *
     * @return integer
     */
    public function getShoplistFlag()
    {
        return $this->shoplist_flag;
    }

    /**
     * @var integer
     */
    private $result_history_display_flg = 0;

    /**
     * Set result_history_display_flg
     *
     * @param integer $resultHistoryDisplayFlg
     * @return TbWarehouse
     */
    public function setResultHistoryDisplayFlg($resultHistoryDisplayFlg)
    {
        $this->result_history_display_flg = $resultHistoryDisplayFlg;

        return $this;
    }

    /**
     * Get result_history_display_flg
     *
     * @return integer
     */
    public function getResultHistoryDisplayFlg()
    {
        return $this->result_history_display_flg;
    }

    /**
     * @var integer
     */
    private $own_flg;

    /**
     * Set own_flg
     *
     * @param integer $ownFlg
     * @return TbWarehouse
     */
    public function setOwnFlg($ownFlg)
    {
      $this->own_flg = $ownFlg;

      return $this;
    }

    /**
     * Get own_flg
     *
     * @return integer
     */
    public function getOwnFlg()
    {
      return $this->own_flg;
    }

    /**
     * @var integer
     */
    private $asset_flg;
    
    /**
     * Set asset_flg
     *
     * @param integer $assetFlg
     * @return TbWarehouse
     */
    public function setAssetFlg($assetFlg)
    {
      $this->asset_flg = $assetFlg;
      
      return $this;
    }
    
    /**
     * Get asset_flg
     *
     * @return integer
     */
    public function getAssetFlg()
    {
      return $this->asset_flg;
    }
    
    
    /**
     * @var integer
     */
    private $terminate_flg;

    /**
     * Set terminate_flg
     *
     * @param integer $terminateFlg
     * @return TbWarehouse
     */
    public function setTerminateFlg($terminateFlg)
    {
      $this->terminate_flg = $terminateFlg;

      return $this;
    }

    /**
     * Get terminate_flg
     *
     * @return integer
     */
    public function getTerminateFlg()
    {
      return $this->terminate_flg;
    }

}
