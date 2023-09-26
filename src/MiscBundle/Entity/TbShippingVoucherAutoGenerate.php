<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbShippingVoucherAutoGenerate
 */
class TbShippingVoucherAutoGenerate
{
    use FillTimestampTrait;

    /** ステータス：登録済 */
    const STATUS_REGISTERED = 0;
    /** ステータス：処理中 */
    const STATUS_PROCESSING = 1;
    /** ステータス：完了 */
    const STATUS_FINISHED = 2;
    /** ステータス：完了(対象無し) */
    const STATUS_FINISHED_NO_TARGET = 3;
    /** ステータス：エラー */
    const STATUS_ERROR = 4;
    /** ステータス：エラー(再生成済) */
    const STATUS_ERROR_REGENERATED = 5;

    const STATUS_LIST = [
        self::STATUS_REGISTERED => '登録済',
        self::STATUS_PROCESSING => '処理中',
        self::STATUS_FINISHED => '完了',
        self::STATUS_FINISHED_NO_TARGET => '完了(対象無し)',
        self::STATUS_ERROR => 'エラー',
        self::STATUS_ERROR_REGENERATED => 'エラー(再生成済)'
    ];

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $packingGroupId;

    /**
     *
     * @var integer
     */
    private $companyId;

    /**
     * @var integer
     */
    private $warehouseId;

    /**
     * @var string
     */
    private $deliveryMethod;

    /**
     * @var integer
     */
    private $page;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $targetNum;

    /**
     * @var integer
     */
    private $registNum;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var integer
     */
    private $accountId;

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
     * Set packingGroupId
     *
     * @param integer $packingGroupId
     * @return TbShippingVoucherAutoGenerate
     */
    public function setPackingGroupId($packingGroupId)
    {
        $this->packingGroupId = $packingGroupId;

        return $this;
    }

    /**
     * Get packingGroupId
     *
     * @return integer
     */
    public function getPackingGroupId()
    {
        return $this->packingGroupId;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     * @return TbShippingVoucherAutoGenerate
     */
    public function setCompanyId($companyId)
    {
      $this->companyId = $companyId;

      return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
      return $this->companyId;
    }

    /**
     * Set warehouseId
     *
     * @param integer $warehouseId
     * @return TbShippingVoucherAutoGenerate
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
     * Set deliveryMethod
     *
     * @param string $deliveryMethod
     * @return TbShippingVoucherAutoGenerate
     */
    public function setDeliveryMethod($deliveryMethod)
    {
        $this->deliveryMethod = $deliveryMethod;

        return $this;
    }

    /**
     * Get deliveryMethod
     *
     * @return string
     */
    public function getDeliveryMethod()
    {
        return $this->deliveryMethod;
    }

    /**
     * Set page
     *
     * @param integer $page
     * @return TbShippingVoucherAutoGenerate
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbShippingVoucherAutoGenerate
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
     * Set targetNum
     *
     * @param integer $targetNum
     * @return TbShippingVoucherAutoGenerate
     */
    public function setTargetNum($targetNum)
    {
        $this->targetNum = $targetNum;

        return $this;
    }

    /**
     * Get targetNum
     *
     * @return integer
     */
    public function getTargetNum()
    {
        return $this->targetNum;
    }

    /**
     * Set registNum
     *
     * @param integer $registNum
     * @return TbShippingVoucherAutoGenerate
     */
    public function setRegistNum($registNum)
    {
        $this->registNum = $registNum;

        return $this;
    }

    /**
     * Get registNum
     *
     * @return integer
     */
    public function getRegistNum()
    {
        return $this->registNum;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     * @return TbShippingVoucherAutoGenerate
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set accountId
     *
     * @param integer $accountId
     * @return TbShippingVoucherAutoGenerate
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbShippingVoucherAutoGenerate
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
     * @return TbShippingVoucherAutoGenerate
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
     * ステータスの論理値を返す。
     */
    public function getStatusName()
    {
        if (is_null($this->status)) {
            return "";
        }
        return self::STATUS_LIST[$this->status];
    }
}
