<?php

namespace MiscBundle\Entity;

/**
 * TbRakutenKeywordRankingLog
 */
class TbRakutenKeywordRankingLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var DateTime
     */
    private $logDate;

    /**
     * @var integer
     */
    private $rank;

    /**
     * @var string
     */
    private $keyword;


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
     * @return TbRakutenKeywordRankingLog
     */
    public function setLogDate(\DateTime $logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     *
     * @return TbRakutenKeywordRankingLog
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
     * Set keyword
     *
     * @param string $keyword
     *
     * @return TbRakutenKeywordRankingLog
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
}
