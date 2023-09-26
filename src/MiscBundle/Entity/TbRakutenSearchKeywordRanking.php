<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbRakutenSearchKeywordRanking
 */
class TbRakutenSearchKeywordRanking
{
    use ArrayTrait;
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $rankingDate;

    /**
     * @var integer
     */
    private $rank;

    /**
     * @var integer
     */
    private $keywordId;


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
     * Set rankingDate
     *
     * @param \DateTime $rankingDate
     * @return TbRakutenSearchKeywordRanking
     */
    public function setRankingDate($rankingDate)
    {
        $this->rankingDate = $rankingDate;

        return $this;
    }

    /**
     * Get rankingDate
     *
     * @return \DateTime 
     */
    public function getRankingDate()
    {
        return $this->rankingDate;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return TbRakutenSearchKeywordRanking
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return integer 
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set keywordId
     *
     * @param integer $keywordId
     * @return TbRakutenSearchKeywordRanking
     */
    public function setKeywordId($keywordId)
    {
        $this->keywordId = $keywordId;

        return $this;
    }

    /**
     * Get keywordId
     *
     * @return integer 
     */
    public function getKeywordId()
    {
        return $this->keywordId;
    }
}
