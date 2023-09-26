<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShippingVoucherNoneedInquiryNumber
 */
class TbShippingVoucherNoneedInquiryNumber
{
    use FillTimestampTrait;

    /** @var integer ステータス：未登録 */
    const STATUS_UNREGISTERED = 0;

    /** @var integer ステータス：登録済み */
    const STATUS_REGISTERED = 1;

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
     * @var string
     */
    private $inquiryNumber;

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
     * @return TbShippingVoucherNoneedInquiryNumber
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
     * @return TbShippingVoucherNoneedInquiryNumber
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
     * @return TbShippingVoucherNoneedInquiryNumber
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
     * Set inquiryNumber
     *
     * @param string $inquiryNumber
     * @return TbShippingVoucherNoneedInquiryNumber
     */
    public function setInquiryNumber($inquiryNumber)
    {
        $this->inquiryNumber = $inquiryNumber;

        return $this;
    }

    /**
     * Get inquiryNumber
     *
     * @return string
     */
    public function getInquiryNumber()
    {
        return $this->inquiryNumber;
    }

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbShippingVoucherNoneedInquiryNumber
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
     * @return TbShippingVoucherNoneedInquiryNumber
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
