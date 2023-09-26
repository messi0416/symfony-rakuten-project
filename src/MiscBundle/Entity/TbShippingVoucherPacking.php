<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShippingVoucherPacking
 */
class TbShippingVoucherPacking
{
    use FillTimestampTrait;

    /** ステータス：未着手 */
    const STATUS_NONE = 0;
    /** ステータス：進行中 */
    const STATUS_PROCESSING = 1;
    /** ステータス：OK */
    const STATUS_OK = 2;
    /** ステータス：商品不足 */
    const STATUS_SHORTAGE = 3;
    /** ステータス：保留 */
    const STATUS_ON_HOLD = 4;
    /** ステータス：出荷STOP待ち */
    const STATUS_WAIT_SHIPPING_STOP = 5;
    /** ステータス：出荷STOP */
    const STATUS_SHIPPING_STOP = 6;

    /** 有効なお問い合わせ番号ステータス：有効なお問い合わせ番号がない */
    const VALID_INQUIRY_NUMBER_STATUS_NONE = 0;
    /** 有効なお問い合わせ番号ステータス：ラベル再発行待ち */
    const VALID_INQUIRY_NUMBER_STATUS_WAIT = 1;
    /** 有効なお問い合わせ番号ステータス：有効なお問い合わせ番号がある */
    const VALID_INQUIRY_NUMBER_STATUS_EXIST = 2;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $voucherId;

    /**
     * @var integer
     */
    private $voucherNumber;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $labelReissueFlg;

    /**
     * @var integer
     */
    private $latestDeliveryMethodId;

    /**
     * @var integer
     */
    private $validInquiryNumberStatus;

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
     * Set voucherId
     *
     * @param integer $voucherId
     * @return TbShippingVoucherPacking
     */
    public function setVoucherId($voucherId)
    {
        $this->voucherId = $voucherId;

        return $this;
    }

    /**
     * Get voucherId
     *
     * @return integer
     */
    public function getVoucherId()
    {
        return $this->voucherId;
    }

    /**
     * Set voucherNumber
     *
     * @param integer $voucherNumber
     * @return TbShippingVoucherPacking
     */
    public function setVoucherNumber($voucherNumber)
    {
        $this->voucherNumber = $voucherNumber;

        return $this;
    }

    /**
     * Get voucherNumber
     *
     * @return integer
     */
    public function getVoucherNumber()
    {
        return $this->voucherNumber;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbShippingVoucherPacking
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
     * Set labelReissueFlg
     *
     * @param integer $labelReissueFlg
     * @return TbShippingVoucherPacking
     */
    public function setLabelReissueFlg($labelReissueFlg)
    {
        $this->labelReissueFlg = $labelReissueFlg;

        return $this;
    }

    /**
     * Get labelReissueFlg
     *
     * @return integer
     */
    public function getLabelReissueFlg()
    {
        return $this->labelReissueFlg;
    }

    /**
     * Set latestDeliveryMethodId
     *
     * @param integer $latestDeliveryMethodId
     * @return TbShippingVoucherPacking
     */
    public function setLatestDeliveryMethodId($latestDeliveryMethodId)
    {
        $this->latestDeliveryMethodId = $latestDeliveryMethodId;

        return $this;
    }

    /**
     * Get latestDeliveryMethodId
     *
     * @return integer
     */
    public function getLatestDeliveryMethodId()
    {
        return $this->latestDeliveryMethodId;
    }

    /**
     * Set validInquiryNumberStatus
     *
     * @param integer $validInquiryNumberStatus
     * @return TbShippingVoucherPacking
     */
    public function setValidInquiryNumberStatus($validInquiryNumberStatus)
    {

      $this->validInquiryNumberStatus = $validInquiryNumberStatus;

      return $this;
    }

    /**
     * Get validInquiryNumberStatus
     *
     * @return integer
     */
    public function getValidInquiryNumberStatus()
    {
      return $this->validInquiryNumberStatus;
    }

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbShippingVoucherPacking
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
     * @return TbShippingVoucherPacking
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
