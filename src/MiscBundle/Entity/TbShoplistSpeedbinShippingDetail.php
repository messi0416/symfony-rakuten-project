<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShoplistSpeedbinShippingDetail
 */
class TbShoplistSpeedbinShippingDetail
{
    /**
     * @var integer
     */
    private $shoplistSpeedbinShippingId;

    /**
     * @var string
     */
    private $skuCode;

    /**
     * @var integer
     */
    private $salesQuantityShoplist;

    /**
     * @var integer
     */
    private $currentSpeedbinStockQuantity;

    /**
     * @var integer
     */
    private $transportingQuantity;

    /**
     * @var integer
     */
    private $warehouseStockQuantity;

    /**
     * @var integer
     */
    private $unshippedSalesQuantity;

    /**
     * @var integer
     */
    private $notForSaleQuantity;

    /**
     * @var integer
     */
    private $salesQuantityOther;

    /**
     * @var integer
     */
    private $deliverableQuantity;

    /**
     * @var integer
     */
    private $plannedQuantity;

    /**
     * @var integer
     */
    private $fixedQuantity;


    /**
     * Set shoplistSpeedbinShippingId
     *
     * @param integer $shoplistSpeedbinShippingId
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setShoplistSpeedbinShippingId($shoplistSpeedbinShippingId)
    {
        $this->shoplistSpeedbinShippingId = $shoplistSpeedbinShippingId;

        return $this;
    }

    /**
     * Get shoplistSpeedbinShippingId
     *
     * @return integer 
     */
    public function getShoplistSpeedbinShippingId()
    {
        return $this->shoplistSpeedbinShippingId;
    }

    /**
     * Set skuCode
     *
     * @param string $skuCode
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setSkuCode($skuCode)
    {
        $this->skuCode = $skuCode;

        return $this;
    }

    /**
     * Get skuCode
     *
     * @return string 
     */
    public function getSkuCode()
    {
        return $this->skuCode;
    }

    /**
     * Set salesQuantityShoplist
     *
     * @param integer $salesQuantityShoplist
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setSalesQuantityShoplist($salesQuantityShoplist)
    {
        $this->salesQuantityShoplist = $salesQuantityShoplist;

        return $this;
    }

    /**
     * Get salesQuantityShoplist
     *
     * @return integer 
     */
    public function getSalesQuantityShoplist()
    {
        return $this->salesQuantityShoplist;
    }

    /**
     * Set currentSpeedbinStockQuantity
     *
     * @param integer $currentSpeedbinStockQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setCurrentSpeedbinStockQuantity($currentSpeedbinStockQuantity)
    {
        $this->currentSpeedbinStockQuantity = $currentSpeedbinStockQuantity;

        return $this;
    }

    /**
     * Get currentSpeedbinStockQuantity
     *
     * @return integer 
     */
    public function getCurrentSpeedbinStockQuantity()
    {
        return $this->currentSpeedbinStockQuantity;
    }

    /**
     * Set transportingQuantity
     *
     * @param integer $transportingQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setTransportingQuantity($transportingQuantity)
    {
        $this->transportingQuantity = $transportingQuantity;

        return $this;
    }

    /**
     * Get transportingQuantity
     *
     * @return integer 
     */
    public function getTransportingQuantity()
    {
        return $this->transportingQuantity;
    }

    /**
     * Set warehouseStockQuantity
     *
     * @param integer $warehouseStockQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setWarehouseStockQuantity($warehouseStockQuantity)
    {
        $this->warehouseStockQuantity = $warehouseStockQuantity;

        return $this;
    }

    /**
     * Get warehouseStockQuantity
     *
     * @return integer 
     */
    public function getWarehouseStockQuantity()
    {
        return $this->warehouseStockQuantity;
    }

    /**
     * Set unshippedSalesQuantity
     *
     * @param integer $unshippedSalesQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setUnshippedSalesQuantity($unshippedSalesQuantity)
    {
        $this->unshippedSalesQuantity = $unshippedSalesQuantity;

        return $this;
    }

    /**
     * Get unshippedSalesQuantity
     *
     * @return integer 
     */
    public function getUnshippedSalesQuantity()
    {
        return $this->unshippedSalesQuantity;
    }

    /**
     * Set notForSaleQuantity
     *
     * @param integer $notForSaleQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setNotForSaleQuantity($notForSaleQuantity)
    {
        $this->notForSaleQuantity = $notForSaleQuantity;

        return $this;
    }

    /**
     * Get notForSaleQuantity
     *
     * @return integer 
     */
    public function getNotForSaleQuantity()
    {
        return $this->notForSaleQuantity;
    }

    /**
     * Set salesQuantityOther
     *
     * @param integer $salesQuantityOther
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setSalesQuantityOther($salesQuantityOther)
    {
        $this->salesQuantityOther = $salesQuantityOther;

        return $this;
    }

    /**
     * Get salesQuantityOther
     *
     * @return integer 
     */
    public function getSalesQuantityOther()
    {
        return $this->salesQuantityOther;
    }

    /**
     * Set deliverableQuantity
     *
     * @param integer $deliverableQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setDeliverableQuantity($deliverableQuantity)
    {
        $this->deliverableQuantity = $deliverableQuantity;

        return $this;
    }

    /**
     * Get deliverableQuantity
     *
     * @return integer 
     */
    public function getDeliverableQuantity()
    {
        return $this->deliverableQuantity;
    }

    /**
     * Set plannedQuantity
     *
     * @param integer $plannedQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setPlannedQuantity($plannedQuantity)
    {
        $this->plannedQuantity = $plannedQuantity;

        return $this;
    }

    /**
     * Get plannedQuantity
     *
     * @return integer 
     */
    public function getPlannedQuantity()
    {
        return $this->plannedQuantity;
    }

    /**
     * Set fixedQuantity
     *
     * @param integer $fixedQuantity
     * @return TbShoplistSpeedbinShippingDetail
     */
    public function setFixedQuantity($fixedQuantity)
    {
        $this->fixedQuantity = $fixedQuantity;

        return $this;
    }

    /**
     * Get fixedQuantity
     *
     * @return integer 
     */
    public function getFixedQuantity()
    {
        return $this->fixedQuantity;
    }
}
