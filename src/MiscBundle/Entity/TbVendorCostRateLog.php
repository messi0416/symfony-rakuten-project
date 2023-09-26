<?php

namespace MiscBundle\Entity;

/**
 * TbVendorCostRateLog
 */
class TbVendorCostRateLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $log_date;

    /**
     * @var string
     */
    private $sire_code;

    /**
     * @var string
     */
    private $sire_name;

    /**
     * @var integer
     */
    private $cost_rate;


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
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return TbVendorCostRateLog
     */
    public function setLogDate($logDate)
    {
        $this->log_date = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->log_date;
    }

    /**
     * Set sireCode
     *
     * @param string $sireCode
     *
     * @return TbVendorCostRateLog
     */
    public function setSireCode($sireCode)
    {
        $this->sire_code = $sireCode;

        return $this;
    }

    /**
     * Get sireCode
     *
     * @return string
     */
    public function getSireCode()
    {
        return $this->sire_code;
    }

    /**
     * Set sireName
     *
     * @param string $sireName
     *
     * @return TbVendorCostRateLog
     */
    public function setSireName($sireName)
    {
        $this->sire_name = $sireName;

        return $this;
    }

    /**
     * Get sireName
     *
     * @return string
     */
    public function getSireName()
    {
        return $this->sire_name;
    }

    /**
     * Set costRate
     *
     * @param integer $costRate
     *
     * @return TbVendorCostRateLog
     */
    public function setCostRate($costRate)
    {
        $this->cost_rate = $costRate;

        return $this;
    }

    /**
     * Get costRate
     *
     * @return integer
     */
    public function getCostRate()
    {
        return $this->cost_rate;
    }
}
