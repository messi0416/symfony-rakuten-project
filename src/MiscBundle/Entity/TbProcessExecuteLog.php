<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProcessExecuteLog
 */
class TbProcessExecuteLog
{

    /** ステータス：処理中 */
    const STATUS_PROCESSING = 1;

    /** ステータス：正常終了 */
    const STATUS_FINISHED = 2;

    /** ステータス：エラー終了 */
    const STATUS_ERROR_END = 9;

    /** キュー名：キューなし */
    const QUEUE_NAME_NONE = 'none';

    /** キュー名：不明 */
    const QUEUE_NAME_UNKNOWN = 'unknown';

    const QUEUE_NAME_WOWMA_CSV_UPLOAD = 'wowmaCsvUpload';
    const QUEUE_NAME_NON_EXCLUSIVE = 'nonExclusive';
    const QUEUE_NAME_MAIN = 'main';
    const QUEUE_NAME_PPM_CSV_UPLOAD = 'ppmCsvUpload';
    const QUEUE_NAME_NE_UPLOAD = 'neUpload';
    const QUEUE_NAME_Q10_CSV_UPLOAD = 'q10CsvUpload';
    const QUEUE_NAME_RAKUTEN_CSV_UPLOAD = 'rakutenCsvUpload';
    const QUEUE_NAME_PRODUCT_IMAGE = 'productImage';
    const QUEUE_NAME_PRODUCT_SALES = 'productSales';


    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $processId;

    /**
     * @var \DateTime
     */
    private $startDatetime;

    /**
     * @var \DateTime
     */
    private $endDatetime;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var boolean
     */
    private $status;

    /**
     * @var integer
     */
    private $processNumber1;

    /**
     * @var integer
     */
    private $processNumber2;

    /**
     * @var integer
     */
    private $processNumber3;

    /**
     * @var float
     */
    private $version;

    /**
     * @var string
     */
    private $errorInformation;


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
     * Set processId
     *
     * @param integer $processId
     * @return TbProcessExecuteLog
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;

        return $this;
    }

    /**
     * Get processId
     *
     * @return integer
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * Set startDatetime
     *
     * @param \DateTime $startDatetime
     * @return TbProcessExecuteLog
     */
    public function setStartDatetime($startDatetime)
    {
        $this->startDatetime = $startDatetime;

        return $this;
    }

    /**
     * Get startDatetime
     *
     * @return \DateTime
     */
    public function getStartDatetime()
    {
        return $this->startDatetime;
    }

    /**
     * Set endDatetime
     *
     * @param \DateTime $endDatetime
     * @return TbProcessExecuteLog
     */
    public function setEndDatetime($endDatetime)
    {
        $this->endDatetime = $endDatetime;

        return $this;
    }

    /**
     * Get endDatetime
     *
     * @return \DateTime
     */
    public function getEndDatetime()
    {
        return $this->endDatetime;
    }

    /**
     * Set queueName
     *
     * @param string $queueName
     * @return TbProcessExecuteLog
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * Get queueName
     *
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbProcessExecuteLog
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
     * Set processNumber1
     *
     * @param integer $processNumber1
     * @return TbProcessExecuteLog
     */
    public function setProcessNumber1($processNumber1)
    {
        $this->processNumber1 = $processNumber1;

        return $this;
    }

    /**
     * Get processNumber1
     *
     * @return integer
     */
    public function getProcessNumber1()
    {
        return $this->processNumber1;
    }

    /**
     * Set processNumber2
     *
     * @param integer $processNumber2
     * @return TbProcessExecuteLog
     */
    public function setProcessNumber2($processNumber2)
    {
        $this->processNumber2 = $processNumber2;

        return $this;
    }

    /**
     * Get processNumber2
     *
     * @return integer
     */
    public function getProcessNumber2()
    {
        return $this->processNumber2;
    }

    /**
     * Set processNumber3
     *
     * @param integer $processNumber3
     * @return TbProcessExecuteLog
     */
    public function setProcessNumber3($processNumber3)
    {
        $this->processNumber3 = $processNumber3;

        return $this;
    }

    /**
     * Get processNumber3
     *
     * @return integer
     */
    public function getProcessNumber3()
    {
        return $this->processNumber3;
    }

    /**
     * Set version
     *
     * @param float $version
     * @return TbProcessExecuteLog
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return float
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set errorInformation
     *
     * @param string $errorInformation
     * @return TbProcessExecuteLog
     */
    public function setErrorInformation($errorInformation)
    {
        $this->errorInformation = $errorInformation;

        return $this;
    }

    /**
     * Get errorInformation
     *
     * @return string
     */
    public function getErrorInformation()
    {
        return $this->errorInformation;
    }
}
