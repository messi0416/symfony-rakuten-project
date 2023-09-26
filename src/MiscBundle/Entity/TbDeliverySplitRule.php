<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbDeliverySplitRule
 */
class TbDeliverySplitRule
{

    use ArrayTrait;

    /** delivery_id: 佐川 */
    const DELIVERY_ID_SAGAWA = 13;
    /** delivery_id: ヤマト */
    const DELIVERY_ID_YAMATO = 20;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $rulename;

    /**
     * @var integer
     */
    private $checkorder;

    /**
     * @var string
     */
    private $prefectureCheckColumn;

    /**
     * @var integer
     */
    private $longlength;

    /**
     * @var integer
     */
    private $middlelength;

    /**
     * @var integer
     */
    private $shortlength;

    /**
     * @var integer
     */
    private $totallength;

    /**
     * @var integer
     */
    private $volume;

    /**
     * @var integer
     */
    private $weight;

    /**
     * @var integer
     */
    private $sizecheck;

    /**
     * @var integer
     */
    private $maxflg;

    /**
     * @var integer
     */
    private $deliveryId;

    /**
     * @var integer
     */
    private $groupid;

    /**
     * @var string
     */
    private $groupname;


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
     * Set rulename
     *
     * @param string $rulename
     * @return TbDeliverySplitRule
     */
    public function setRulename($rulename)
    {
        $this->rulename = $rulename;

        return $this;
    }

    /**
     * Get rulename
     *
     * @return string 
     */
    public function getRulename()
    {
        return $this->rulename;
    }

    /**
     * Set checkorder
     *
     * @param integer $checkorder
     * @return TbDeliverySplitRule
     */
    public function setCheckorder($checkorder)
    {
        $this->checkorder = $checkorder;

        return $this;
    }

    /**
     * Get checkorder
     *
     * @return integer 
     */
    public function getCheckorder()
    {
        return $this->checkorder;
    }

    /**
     * Set prefectureCheckColumn
     *
     * @param string $prefectureCheckColumn
     * @return TbDeliverySplitRule
     */
    public function setPrefectureCheckColumn($prefectureCheckColumn)
    {
        $this->prefectureCheckColumn = $prefectureCheckColumn;

        return $this;
    }

    /**
     * Get prefectureCheckColumn
     *
     * @return string 
     */
    public function getPrefectureCheckColumn()
    {
        return $this->prefectureCheckColumn;
    }

    /**
     * Set longlength
     *
     * @param integer $longlength
     * @return TbDeliverySplitRule
     */
    public function setLonglength($longlength)
    {
        $this->longlength = $longlength;

        return $this;
    }

    /**
     * Get longlength
     *
     * @return integer 
     */
    public function getLonglength()
    {
        return $this->longlength;
    }

    /**
     * Set middlelength
     *
     * @param integer $middlelength
     * @return TbDeliverySplitRule
     */
    public function setMiddlelength($middlelength)
    {
        $this->middlelength = $middlelength;

        return $this;
    }

    /**
     * Get middlelength
     *
     * @return integer 
     */
    public function getMiddlelength()
    {
        return $this->middlelength;
    }

    /**
     * Set shortlength
     *
     * @param integer $shortlength
     * @return TbDeliverySplitRule
     */
    public function setShortlength($shortlength)
    {
        $this->shortlength = $shortlength;

        return $this;
    }

    /**
     * Get shortlength
     *
     * @return integer 
     */
    public function getShortlength()
    {
        return $this->shortlength;
    }

    /**
     * Set totallength
     *
     * @param integer $totallength
     * @return TbDeliverySplitRule
     */
    public function setTotallength($totallength)
    {
        $this->totallength = $totallength;

        return $this;
    }

    /**
     * Get totallength
     *
     * @return integer 
     */
    public function getTotallength()
    {
        return $this->totallength;
    }

    /**
     * Set volume
     *
     * @param integer $volume
     * @return TbDeliverySplitRule
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     * Get volume
     *
     * @return integer 
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return TbDeliverySplitRule
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set sizecheck
     *
     * @param integer $sizecheck
     * @return TbDeliverySplitRule
     */
    public function setSizecheck($sizecheck)
    {
        $this->sizecheck = $sizecheck;

        return $this;
    }

    /**
     * Get sizecheck
     *
     * @return integer 
     */
    public function getSizecheck()
    {
        return $this->sizecheck;
    }

    /**
     * Set maxflg
     *
     * @param integer $maxflg
     * @return TbDeliverySplitRule
     */
    public function setMaxflg($maxflg)
    {
        $this->maxflg = $maxflg;

        return $this;
    }

    /**
     * Get maxflg
     *
     * @return integer 
     */
    public function getMaxflg()
    {
        return $this->maxflg;
    }

    /**
     * Set deliveryId
     *
     * @param integer $deliveryId
     * @return TbDeliverySplitRule
     */
    public function setDeliveryId($deliveryId)
    {
        $this->deliveryId = $deliveryId;

        return $this;
    }

    /**
     * Get deliveryId
     *
     * @return integer 
     */
    public function getDeliveryId()
    {
        return $this->deliveryId;
    }

    /**
     * Set groupid
     *
     * @param integer $groupid
     * @return TbDeliverySplitRule
     */
    public function setGroupid($groupid)
    {
        $this->groupid = $groupid;

        return $this;
    }

    /**
     * Get groupid
     *
     * @return integer 
     */
    public function getGroupid()
    {
        return $this->groupid;
    }

    /**
     * Set groupname
     *
     * @param string $groupname
     * @return TbDeliverySplitRule
     */
    public function setGroupname($groupname)
    {
        $this->groupname = $groupname;

        return $this;
    }

    /**
     * Get groupname
     *
     * @return string 
     */
    public function getGroupname()
    {
        return $this->groupname;
    }
}
