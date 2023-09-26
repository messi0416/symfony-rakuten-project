<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SymfonyUsersLockLog
 */
class SymfonyUsersLockLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $accountId;

    /**
     * @var integer
     */
    private $accessIp;

    /**
     * @var string
     */
    private $agent;

    /**
     * @var \DateTime
     */
    private $lockedDatetime;


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
     * Set accountId
     *
     * @param integer $accountId
     * @return SymfonyUsersLockLog
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
     * Set accessIp
     *
     * @param integer $accessIp
     * @return SymfonyUsersLockLog
     */
    public function setAccessIp($accessIp)
    {
        $this->accessIp = $accessIp;

        return $this;
    }

    /**
     * Get accessIp
     *
     * @return integer 
     */
    public function getAccessIp()
    {
        return $this->accessIp;
    }

    /**
     * Set agent
     *
     * @param string $agent
     * @return SymfonyUsersLockLog
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Get agent
     *
     * @return string 
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * Set lockedDatetime
     *
     * @param \DateTime $lockedDatetime
     * @return SymfonyUsersLockLog
     */
    public function setLockedDatetime($lockedDatetime)
    {
        $this->lockedDatetime = $lockedDatetime;

        return $this;
    }

    /**
     * Get lockedDatetime
     *
     * @return \DateTime 
     */
    public function getLockedDatetime()
    {
        return $this->lockedDatetime;
    }
}
