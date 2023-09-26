<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductSalesAccountResultHistory
 */
class TbProductSalesAccountResultHistory
{
    use FillTimestampTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $productSalesAccountId;

    /**
     * @var \DateTime
     */
    private $targetDate;

    /**
     * @var integer
     */
    private $salesAmount;

    /**
     * @var integer
     */
    private $profitAmount;

    /**
     * @var integer
     */
    private $shoplistSalesAmount;

    /**
     * @var integer
     */
    private $shoplistProfitAmount;

    /**
     * @var integer
     */
    private $stockQuantity = 0;

    /**
     * @var integer
     */
    private $stockAmount = 0;

    /**
     * @var integer
     */
    private $remainQuantity = 0;

    /**
     * @var integer
     */
    private $remainAmount = 0;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \MiscBundle\Entity\TbProductSalesAccount
     */
    private $productSalesAccount;


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
     * Set productSalesAccountId
     *
     * @param integer $productSalesAccountId
     * @return TbProductSalesAccountResultHistory
     */
    public function setProductSalesAccountId($productSalesAccountId)
    {
        $this->productSalesAccountId = $productSalesAccountId;

        return $this;
    }

    /**
     * Get productSalesAccountId
     *
     * @return integer
     */
    public function getProductSalesAccountId()
    {
        return $this->productSalesAccountId;
    }

    /**
     * Set targetDate
     *
     * @param \DateTime $targetDate
     * @return TbProductSalesAccountResultHistory
     */
    public function setTargetDate($targetDate)
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    /**
     * Get targetDate
     *
     * @return \DateTime
     */
    public function getTargetDate()
    {
        return $this->targetDate;
    }

    /**
     * Set salesAmount
     *
     * @param integer $salesAmount
     * @return TbProductSalesAccountResultHistory
     */
    public function setSalesAmount($salesAmount)
    {
        $this->salesAmount = $salesAmount;

        return $this;
    }

    /**
     * Get salesAmount
     *
     * @return integer
     */
    public function getSalesAmount()
    {
        return $this->salesAmount;
    }

    /**
     * Set profitAmount
     *
     * @param integer $profitAmount
     * @return TbProductSalesAccountResultHistory
     */
    public function setProfitAmount($profitAmount)
    {
        $this->profitAmount = $profitAmount;

        return $this;
    }

    /**
     * Get profitAmount
     *
     * @return integer
     */
    public function getProfitAmount()
    {
        return $this->profitAmount;
    }

    /**
     * Set shoplistSalesAmount
     *
     * @param integer $shoplistSalesAmount
     * @return TbProductSalesAccountResultHistory
     */
    public function setShoplistSalesAmount($shoplistSalesAmount)
    {
        $this->shoplistSalesAmount = $shoplistSalesAmount;

        return $this;
    }

    /**
     * Get shoplistSalesAmount
     *
     * @return integer
     */
    public function getShoplistSalesAmount()
    {
        return $this->shoplistSalesAmount;
    }

    /**
     * Set shoplistProfitAmount
     *
     * @param integer $shoplistProfitAmount
     * @return TbProductSalesAccountResultHistory
     */
    public function setShoplistProfitAmount($shoplistProfitAmount)
    {
        $this->shoplistProfitAmount = $shoplistProfitAmount;

        return $this;
    }

    /**
     * Get shoplistProfitAmount
     *
     * @return integer
     */
    public function getShoplistProfitAmount()
    {
        return $this->shoplistProfitAmount;
    }

    /**
     * Set stockQuantity
     *
     * @param integer $stockQuantity
     * @return TbProductSalesAccountResultHistory
     */
    public function setStockQuantity($stockQuantity)
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    /**
     * Get stockQuantity
     *
     * @return integer
     */
    public function getStockQuantity()
    {
        return $this->stockQuantity;
    }

    /**
     * Set stockAmount
     *
     * @param integer $stockAmount
     * @return TbProductSalesAccountResultHistory
     */
    public function setStockAmount($stockAmount)
    {
        $this->stockAmount = $stockAmount;

        return $this;
    }

    /**
     * Get stockAmount
     *
     * @return integer
     */
    public function getStockAmount()
    {
        return $this->stockAmount;
    }

    /**
     * Set remainQuantity
     *
     * @param integer $remainQuantity
     * @return TbProductSalesAccountResultHistory
     */
    public function setRemainQuantity($remainQuantity)
    {
        $this->remainQuantity = $remainQuantity;

        return $this;
    }

    /**
     * Get remainQuantity
     *
     * @return integer
     */
    public function getRemainQuantity()
    {
        return $this->remainQuantity;
    }

    /**
     * Set remainAmount
     *
     * @param integer $remainAmount
     * @return TbProductSalesAccountResultHistory
     */
    public function setRemainAmount($remainAmount)
    {
        $this->remainAmount = $remainAmount;

        return $this;
    }

    /**
     * Get remainAmount
     *
     * @return integer
     */
    public function getRemainAmount()
    {
        return $this->remainAmount;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbProductSalesAccountResultHistory
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
     * Set productSalesAccount
     *
     * @param \MiscBundle\Entity\TbProductSalesAccount $productSalesAccount
     * @return TbProductSalesAccountResultHistory
     */
    public function setProductSalesAccount(\MiscBundle\Entity\TbProductSalesAccount $productSalesAccount = null)
    {
        $this->productSalesAccount = $productSalesAccount;

        return $this;
    }

    /**
     * Get productSalesAccount
     *
     * @return \MiscBundle\Entity\TbProductSalesAccount
     */
    public function getProductSalesAccount()
    {
        return $this->productSalesAccount;
    }
}
