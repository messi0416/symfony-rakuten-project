<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShippingVoucherPackingGroup
 */
class TbShippingVoucherPackingGroup
{
    /** ステータス: 未処理 */
    const STATUS_NONE = 0;
    /** ステータス: 処理中 */
    const STATUS_ONGOING = 1;
    /** ステータス: 完了 */
    const STATUS_DONE = 2;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $deliveryMethodId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var string
     */
    private $shippingVoucherPdfFilename;

    /**
     * @var string
     */
    private $packingComment;


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
     * Set deliveryMethodId
     *
     * @param integer $deliveryMethodId
     * @return TbShippingVoucherPackingGroup
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
     * Set name
     *
     * @param string $name
     * @return TbShippingVoucherPackingGroup
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
     * Set status
     *
     * @param integer $status
     * @return TbShippingVoucherPackingGroup
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
     * Set shippingVoucherPdfFilename
     *
     * @param string $shippingVoucherPdfFilename
     * @return TbShippingVoucherPackingGroup
     */
    public function setShippingVoucherPdfFilename($shippingVoucherPdfFilename)
    {
        $this->shippingVoucherPdfFilename = $shippingVoucherPdfFilename;

        return $this;
    }

    /**
     * Get shippingVoucherPdfFilename
     *
     * @return string 
     */
    public function getShippingVoucherPdfFilename()
    {
        return $this->shippingVoucherPdfFilename;
    }

    /**
     * Set packingComment
     *
     * @param string $packingComment
     * @return TbShippingVoucherPackingGroup
     */
    public function setPackingComment($packingComment)
    {
        $this->packingComment = $packingComment;

        return $this;
    }

    /**
     * Get packingComment
     *
     * @return string 
     */
    public function getPackingComment()
    {
        return $this->packingComment;
    }
}
