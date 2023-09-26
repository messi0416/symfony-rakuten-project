<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbStopWorker
 */
class TbStopWorker
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $stop_worker;

    /**
     * @var integer
     */
    private $stop_time = 10;

    /**
     * @var integer
     */
    private $process_id;

    /**
     * @var integer
     */
    private $is_active = -1;

    /**
     * @var \DateTime
     */
    private $created_at = '0000-00-00 00:00:00';


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
     * Set username
     *
     * @param string $username
     * @return TbStopWorker
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set stop_worker
     *
     * @param string $stopWorker
     * @return TbStopWorker
     */
    public function setStopWorker($stopWorker)
    {
        $this->stop_worker = $stopWorker;

        return $this;
    }

    /**
     * Get stop_worker
     *
     * @return string 
     */
    public function getStopWorker()
    {
        return $this->stop_worker;
    }

    /**
     * Set stop_time
     *
     * @param integer $stopTime
     * @return TbStopWorker
     */
    public function setStopTime($stopTime)
    {
        $this->stop_time = $stopTime;

        return $this;
    }

    /**
     * Get stop_time
     *
     * @return integer 
     */
    public function getStopTime()
    {
        return $this->stop_time;
    }

    /**
     * Set process_id
     *
     * @param integer $processId
     * @return TbStopWorker
     */
    public function setProcessId($processId)
    {
        $this->process_id = $processId;

        return $this;
    }

    /**
     * Get process_id
     *
     * @return integer 
     */
    public function getProcessId()
    {
        return $this->process_id;
    }

    /**
     * Set is_active
     *
     * @param integer $isActive
     * @return TbStopWorker
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;

        return $this;
    }

    /**
     * Get is_active
     *
     * @return integer 
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return TbStopWorker
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    /**
     * @var string
     */
    private $cancel_user;


    /**
     * Set cancel_user
     *
     * @param string $cancelUser
     * @return TbStopWorker
     */
    public function setCancelUser($cancelUser)
    {
        $this->cancel_user = $cancelUser;

        return $this;
    }

    /**
     * Get cancel_user
     *
     * @return string 
     */
    public function getCancelUser()
    {
        return $this->cancel_user;
    }
}
