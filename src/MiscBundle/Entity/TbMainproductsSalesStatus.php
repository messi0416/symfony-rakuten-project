<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbMainproductsSalesStatus
 */
class TbMainproductsSalesStatus
{
    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var int
     */
    private $orderableFlg;

    /**
     * @var int
     */
    private $activeFlg;

    /**
     * @var int
     */
    private $zaikoTeisuExistFlg;

    /**
     * @var int
     */
    private $baikaTanka;

    /**
     * @var string
     */
    private $bigCategory;

    /**
     * @var string
     */
    private $midCategory;

    /**
     * @var string
     */
    private $sireCode;

    /**
     * @var string
     */
    private $sireName;


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
     * Set orderableFlg
     *
     * @param int $orderableFlg
     * @return TbMainproductsSalesStatus
     */
    public function setOrderableFlg($orderableFlg)
    {
        $this->orderableFlg = $orderableFlg;

        return $this;
    }

    /**
     * Get orderableFlg
     *
     * @return int
     */
    public function getOrderableFlg()
    {
        return $this->orderableFlg;
    }

    /**
     * Set activeFlg
     *
     * @param int $activeFlg
     * @return TbMainproductsSalesStatus
     */
    public function setActiveFlg($activeFlg)
    {
        $this->activeFlg = $activeFlg;

        return $this;
    }

    /**
     * Get activeFlg
     *
     * @return int
     */
    public function getActiveFlg()
    {
        return $this->activeFlg;
    }

    /**
     * Set zaikoTeisuExistFlg
     *
     * @param int $zaikoTeisuExistFlg
     * @return TbMainproductsSalesStatus
     */
    public function setZaikoTeisuExistFlg($zaikoTeisuExistFlg)
    {
        $this->zaikoTeisuExistFlg = $zaikoTeisuExistFlg;

        return $this;
    }

    /**
     * Get zaikoTeisuExistFlg
     *
     * @return int
     */
    public function getZaikoTeisuExistFlg()
    {
        return $this->zaikoTeisuExistFlg;
    }

    /**
     * Set baikaTanka
     *
     * @param int $baikaTanka
     * @return TbMainproductsSalesStatus
     */
    public function setBaikaTanka($baikaTanka)
    {
        $this->baikaTanka = $baikaTanka;

        return $this;
    }

    /**
     * Get baikaTanka
     *
     * @return int
     */
    public function getBaikaTanka()
    {
        return $this->baikaTanka;
    }

    /**
     * Set bigCategory
     *
     * @param string $bigCategory
     * @return TbMainproductsSalesStatus
     */
    public function setBigCategory($bigCategory)
    {
        $this->bigCategory = $bigCategory;

        return $this;
    }

    /**
     * Get bigCategory
     *
     * @return string
     */
    public function getBigCategory()
    {
        return $this->bigCategory;
    }

    /**
     * Set midCategory
     *
     * @param string $midCategory
     * @return TbMainproductsSalesStatus
     */
    public function setMidCategory($midCategory)
    {
        $this->midCategory = $midCategory;

        return $this;
    }

    /**
     * Get midCategory
     *
     * @return string
     */
    public function getMidCategory()
    {
        return $this->midCategory;
    }

    /**
     * Set sireCode
     *
     * @param string $sireCode
     * @return TbMainproductsSalesStatus
     */
    public function setSireCode($sireCode)
    {
        $this->sireCode = $sireCode;

        return $this;
    }

    /**
     * Get sireCode
     *
     * @return string
     */
    public function getSireCode()
    {
        return $this->sireCode;
    }

    /**
     * Set sireName
     *
     * @param string $sireName
     * @return TbMainproductsSalesStatus
     */
    public function setSireName($sireName)
    {
        $this->sireName = $sireName;

        return $this;
    }

    /**
     * Get sireName
     *
     * @return string
     */
    public function getSireName()
    {
        return $this->sireName;
    }
}
