<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbPickingScore
 */
class TbPickingScore
{
    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $scFirstScore;

    /**
     * @var integer
     */
    private $scSecondScore;

    /**
     * @var integer
     */
    private $scThirdScore;

    /**
     * @var integer
     */
    private $vFirstScore;

    /**
     * @var integer
     */
    private $vSecondScore;

    /**
     * @var integer
     */
    private $vThirdScore;

    /**
     * @var integer
     */
    private $othersFirstScore;

    /**
     * @var integer
     */
    private $othersSecondScore;

    /**
     * @var integer
     */
    private $othersThirdScore;


    /**
     * Set userId
     *
     * @param integer $userId
     * @return TbPickingScore
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set scFirstScore
     *
     * @param integer $scFirstScore
     * @return TbPickingScore
     */
    public function setScFirstScore($scFirstScore)
    {
        $this->scFirstScore = $scFirstScore;

        return $this;
    }

    /**
     * Get scFirstScore
     *
     * @return integer 
     */
    public function getScFirstScore()
    {
        return $this->scFirstScore;
    }

    /**
     * Set scSecondScore
     *
     * @param integer $scSecondScore
     * @return TbPickingScore
     */
    public function setScSecondScore($scSecondScore)
    {
        $this->scSecondScore = $scSecondScore;

        return $this;
    }

    /**
     * Get scSecondScore
     *
     * @return integer 
     */
    public function getScSecondScore()
    {
        return $this->scSecondScore;
    }

    /**
     * Set scThirdScore
     *
     * @param integer $scThirdScore
     * @return TbPickingScore
     */
    public function setScThirdScore($scThirdScore)
    {
        $this->scThirdScore = $scThirdScore;

        return $this;
    }

    /**
     * Get scThirdScore
     *
     * @return integer 
     */
    public function getScThirdScore()
    {
        return $this->scThirdScore;
    }

    /**
     * Set vFirstScore
     *
     * @param integer $vFirstScore
     * @return TbPickingScore
     */
    public function setVFirstScore($vFirstScore)
    {
        $this->vFirstScore = $vFirstScore;

        return $this;
    }

    /**
     * Get vFirstScore
     *
     * @return integer 
     */
    public function getVFirstScore()
    {
        return $this->vFirstScore;
    }

    /**
     * Set vSecondScore
     *
     * @param integer $vSecondScore
     * @return TbPickingScore
     */
    public function setVSecondScore($vSecondScore)
    {
        $this->vSecondScore = $vSecondScore;

        return $this;
    }

    /**
     * Get vSecondScore
     *
     * @return integer 
     */
    public function getVSecondScore()
    {
        return $this->vSecondScore;
    }

    /**
     * Set vThirdScore
     *
     * @param integer $vThirdScore
     * @return TbPickingScore
     */
    public function setVThirdScore($vThirdScore)
    {
        $this->vThirdScore = $vThirdScore;

        return $this;
    }

    /**
     * Get vThirdScore
     *
     * @return integer 
     */
    public function getVThirdScore()
    {
        return $this->vThirdScore;
    }

    /**
     * Set othersFirstScore
     *
     * @param integer $othersFirstScore
     * @return TbPickingScore
     */
    public function setOthersFirstScore($othersFirstScore)
    {
        $this->othersFirstScore = $othersFirstScore;

        return $this;
    }

    /**
     * Get othersFirstScore
     *
     * @return integer 
     */
    public function getOthersFirstScore()
    {
        return $this->othersFirstScore;
    }

    /**
     * Set othersSecondScore
     *
     * @param integer $othersSecondScore
     * @return TbPickingScore
     */
    public function setOthersSecondScore($othersSecondScore)
    {
        $this->othersSecondScore = $othersSecondScore;

        return $this;
    }

    /**
     * Get othersSecondScore
     *
     * @return integer 
     */
    public function getOthersSecondScore()
    {
        return $this->othersSecondScore;
    }

    /**
     * Set othersThirdScore
     *
     * @param integer $othersThirdScore
     * @return TbPickingScore
     */
    public function setOthersThirdScore($othersThirdScore)
    {
        $this->othersThirdScore = $othersThirdScore;

        return $this;
    }

    /**
     * Get othersThirdScore
     *
     * @return integer 
     */
    public function getOthersThirdScore()
    {
        return $this->othersThirdScore;
    }
}
