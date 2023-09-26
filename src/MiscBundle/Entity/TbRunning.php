<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbRunning
 */
class TbRunning
{
    use ArrayTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $proc;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $startDatetime;

    /**
     * @var string
     */
    private $estimateTime;


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
     * Set proc
     *
     * @param string $proc
     *
     * @return TbRunning
     */
    public function setProc($proc)
    {
        $this->proc = $proc;

        return $this;
    }

    /**
     * Get proc
     *
     * @return string
     */
    public function getProc()
    {
        return $this->proc;
    }

    /**
     * Set queueName
     *
     * @param string $queueName
     *
     * @return TbRunning
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
     * Set startDatetime
     *
     * @param string $startDatetime
     *
     * @return TbRunning
     */
    public function setStartDatetime($startDatetime)
    {
        $this->startDatetime = $startDatetime;

        return $this;
    }

    /**
     * Get startDatetime
     *
     * @return string
     */
    public function getStartDatetime()
    {
        return $this->startDatetime;
    }

    /**
     * Set estimateTime
     *
     * @param string $estimateTime
     *
     * @return TbRunning
     */
    public function setEstimateTime($estimateTime)
    {
        $this->estimateTime = $estimateTime;

        return $this;
    }

    /**
     * Get estimateTime
     *
     * @return string
     */
    public function getEstimateTime()
    {
        return $this->estimateTime;
    }
}
