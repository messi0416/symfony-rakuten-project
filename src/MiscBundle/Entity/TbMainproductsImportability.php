<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbMainproductsImportability
 */
class TbMainproductsImportability
{
    use FillTimestampTrait;

    /** ステータス：未設定 */
    const STATUS_UNREGISTERED = 0;
    /** ステータス：可 */
    const STATUS_AVAILABLE = 1;
    /** ステータス：不可 */
    const STATUS_UNAVAILABLE = 2;
    /** ステータス：保留 */
    const STATUS_PENDING = 3;

    const STATUS_LIST = [
        self::STATUS_UNREGISTERED => '未設定',
        self::STATUS_AVAILABLE => '可',
        self::STATUS_UNAVAILABLE => '不可',
        self::STATUS_PENDING => '保留'
    ];

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var integer
     */
    private $importabilityStatus;

    /**
     * @var integer
     */
    private $statusUpdateAccountId;

    /**
     * @var \DateTime
     */
    private $statusUpdated;

    /**
     * @var string
     */
    private $note;

    /**
     * @var integer
     */
    private $updateAccountId;

    /**
     * @var \DateTime
     */
    private $updated;


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbMainproductsImportability
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
     * Set importabilityStatus
     *
     * @param integer $importabilityStatus
     * @return TbMainproductsImportability
     */
    public function setImportabilityStatus($importabilityStatus)
    {
        $this->importabilityStatus = $importabilityStatus;

        return $this;
    }

    /**
     * Get importabilityStatus
     *
     * @return integer
     */
    public function getImportabilityStatus()
    {
        return $this->importabilityStatus;
    }

    /**
     * Set statusUpdateAccountId
     *
     * @param integer $statusUpdateAccountId
     * @return TbMainproductsImportability
     */
    public function setStatusUpdateAccountId($statusUpdateAccountId)
    {
        $this->statusUpdateAccountId = $statusUpdateAccountId;

        return $this;
    }

    /**
     * Get statusUpdateAccountId
     *
     * @return integer
     */
    public function getStatusUpdateAccountId()
    {
        return $this->statusUpdateAccountId;
    }

    /**
     * Set statusUpdated
     *
     * @param \DateTime $statusUpdated
     * @return TbMainproductsImportability
     */
    public function setStatusUpdated($statusUpdated)
    {
        $this->statusUpdated = $statusUpdated;

        return $this;
    }

    /**
     * Get statusUpdated
     *
     * @return \DateTime
     */
    public function getStatusUpdated()
    {
        return $this->statusUpdated;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return TbMainproductsImportability
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbMainproductsImportability
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
     * @return TbMainproductsImportability
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
