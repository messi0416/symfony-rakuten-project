<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbYahooOtoriyoseAccessLog
 */
class TbYahooOtoriyoseAccessLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $targetDate;

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var integer
     */
    private $pv;

    /**
     * @var integer
     */
    private $uu;


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
     * Set targetDate
     *
     * @param \DateTime $targetDate
     * @return TbYahooOtoriyoseAccessLog
     */
    public function setTargetDate($targetDate)
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    /**
     * Get targetDate
     *
     * @return \DateTime 
     */
    public function getTargetDate()
    {
        return $this->targetDate;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbYahooOtoriyoseAccessLog
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
     * Set pv
     *
     * @param integer $pv
     * @return TbYahooOtoriyoseAccessLog
     */
    public function setPv($pv)
    {
        $this->pv = $pv;

        return $this;
    }

    /**
     * Get pv
     *
     * @return integer 
     */
    public function getPv()
    {
        return $this->pv;
    }

    /**
     * Set uu
     *
     * @param integer $uu
     * @return TbYahooOtoriyoseAccessLog
     */
    public function setUu($uu)
    {
        $this->uu = $uu;

        return $this;
    }

    /**
     * Get uu
     *
     * @return integer 
     */
    public function getUu()
    {
        return $this->uu;
    }
}
