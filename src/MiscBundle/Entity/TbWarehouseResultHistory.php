<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbWarehouseResultHistory
 */
class TbWarehouseResultHistory
{
    use ArrayTrait;
    use FillTimestampTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $warehouseId;

    /**
     * @var string
     */
    private $targetDate;

    /**
     * @var integer
     */
    private $pickingSum;

    /**
     * @var integer
     */
    private $warehousePickingSum;

    /**
     * @var integer
     */
    private $shippingSum;

    /**
     * @var integer
     */
    private $shippingSumShoplist;

    /**
     * @var integer
     */
    private $shippingSumRsl;

    /**
     * @var integer
     */
    private $shippingSumSagawa;

    /**
     * @var integer
     */
    private $shippingSumYamato;

    /**
     * @var integer
     */
    private $operationTimeSum;

    /**
     * @var integer
     */
    private $updateAccountId;

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
     * Set warehouseId
     *
     * @param integer $warehouseId
     * @return TbWarehouseResultHistory
     */
    public function setWarehouseId($warehouseId)
    {
        $this->warehouseId = $warehouseId;

        return $this;
    }

    /**
     * Get warehouseId
     *
     * @return integer 
     */
    public function getWarehouseId()
    {
        return $this->warehouseId;
    }

    /**
     * Set targetDate
     *
     * @param string $targetDate
     * @return TbWarehouseResultHistory
     */
    public function setTargetDate($targetDate)
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    /**
     * Get targetDate
     *
     * @return string
     */
    public function getTargetDate()
    {
        return $this->targetDate;
    }

    /**
     * Set pickingSum
     *
     * @param integer $pickingSum
     * @return TbWarehouseResultHistory
     */
    public function setPickingSum($pickingSum)
    {
        $this->pickingSum = $pickingSum;

        return $this;
    }

    /**
     * Get pickingSum
     *
     * @return integer 
     */
    public function getPickingSum()
    {
        return $this->pickingSum;
    }

    /**
     * Set warehousePickingSum
     *
     * @param integer $warehousePickingSum
     * @return TbWarehouseResultHistory
     */
    public function setWarehousePickingSum($warehousePickingSum)
    {
        $this->warehousePickingSum = $warehousePickingSum;

        return $this;
    }

    /**
     * Get warehousePickingSum
     *
     * @return integer 
     */
    public function getWarehousePickingSum()
    {
        return $this->warehousePickingSum;
    }

    /**
     * Set shippingSum
     *
     * @param integer $shippingSum
     * @return TbWarehouseResultHistory
     */
    public function setShippingSum($shippingSum)
    {
        $this->shippingSum = $shippingSum;

        return $this;
    }

    /**
     * Get shippingSum
     *
     * @return integer 
     */
    public function getShippingSum()
    {
        return $this->shippingSum;
    }

    /**
     * Set shippingSumShoplist
     *
     * @param integer $shippingSumShoplist
     * @return TbWarehouseResultHistory
     */
    public function setShippingSumShoplist($shippingSumShoplist)
    {
        $this->shippingSumShoplist = $shippingSumShoplist;

        return $this;
    }

    /**
     * Get shippingSumShoplist
     *
     * @return integer
     */
    public function getShippingSumShoplist()
    {
        return $this->shippingSumShoplist;
    }

    /**
     * Set shippingSumRsl
     *
     * @param integer $shippingSumRsl
     * @return TbWarehouseResultHistory
     */
    public function setShippingSumRsl($shippingSumRsl)
    {
        $this->shippingSumRsl = $shippingSumRsl;

        return $this;
    }

    /**
     * Get shippingSumRsl
     *
     * @return integer
     */
    public function getShippingSumRsl()
    {
        return $this->shippingSumRsl;
    }

    /**
     * Set shippingSumSagawa
     *
     * @param integer $shippingSumSagawa
     * @return TbWarehouseResultHistory
     */
    public function setShippingSumSagawa($shippingSumSagawa)
    {
        $this->shippingSumSagawa = $shippingSumSagawa;

        return $this;
    }

    /**
     * Get shippingSumSagawa
     *
     * @return integer
     */
    public function getShippingSumSagawa()
    {
        return $this->shippingSumSagawa;
    }

    /**
     * Set shippingSumYamato
     *
     * @param integer $shippingSumYamato
     * @return TbWarehouseResultHistory
     */
    public function setShippingSumYamato($shippingSumYamato)
    {
        $this->shippingSumYamato = $shippingSumYamato;

        return $this;
    }

    /**
     * Get shippingSumYamato
     *
     * @return integer
     */
    public function getShippingSumYamato()
    {
        return $this->shippingSumYamato;
    }

    /**
     * Set operationTimeSum
     *
     * @param integer $operationTimeSum
     * @return TbWarehouseResultHistory
     */
    public function setOperationTimeSum($operationTimeSum)
    {
        $this->operationTimeSum = $operationTimeSum;

        return $this;
    }

    /**
     * Get operationTimeSum
     *
     * @return integer 
     */
    public function getOperationTimeSum()
    {
        return $this->operationTimeSum;
    }

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbWarehouseResultHistory
     */
    public function setUpdateAccountId($updateAccountId)
    {
        $this->updateAccountId = $updateAccountId;

        return $this;
    }

    /**
     * Get updateAccountId
     *
     * @return integer 
     */
    public function getUpdateAccountId()
    {
        return $this->updateAccountId;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbWarehouseResultHistory
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
}
