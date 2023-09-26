<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShippingReissueLabel
 */
class TbShippingReissueLabel
{
    use FillTimestampTrait;

    /** @var integer ステータス：未発行 */
    const STATUS_UNISSUED = 0;

    /** @var integer ステータス：発行済み */
    const STATUS_ISSUED = 1;

    /** @var integer ステータス：削除 */
    const STATUS_DELETE = 9;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $shippingVoucherPackingId;

    /**
     * @var integer
     */
    private $deliveryMethodId;

    /**
     * @var integer
     */
    private $status;

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
     * Set shippingVoucherPackingId
     *
     * @param integer $shippingVoucherPackingId
     * @return TbShippingReissueLabel
     */
    public function setShippingVoucherPackingId($shippingVoucherPackingId)
    {
        $this->shippingVoucherPackingId = $shippingVoucherPackingId;

        return $this;
    }

    /**
     * Get shippingVoucherPackingId
     *
     * @return integer
     */
    public function getShippingVoucherPackingId()
    {
        return $this->shippingVoucherPackingId;
    }

    /**
     * Set deliveryMethodId
     *
     * @param integer $deliveryMethodId
     * @return TbShippingReissueLabel
     */
    public function setDeliveryMethodId($deliveryMethodId)
    {
        $this->deliveryMethodId = $deliveryMethodId;

        return $this;
    }

    /**
     * Get deliveryMethodId
     *
     * @return integer
     */
    public function getDeliveryMethodId()
    {
        return $this->deliveryMethodId;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbShippingReissueLabel
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbShippingReissueLabel
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
     * @return TbShippingReissueLabel
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
