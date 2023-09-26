<?php

namespace MiscBundle\Entity;

/**
 * TbSalesAnaMatch
 */
class TbSalesAnaMatch
{
    /**
     * @var integer
     */
    private $year = '0';

    /**
     * @var integer
     */
    private $month = '0';

    /**
     * @var integer
     */
    private $total;


    /**
     * Set year
     *
     * @param integer $year
     *
     * @return TbSalesAnaMatch
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set month
     *
     * @param integer $month
     *
     * @return TbSalesAnaMatch
     */
    public function setMonth($month)
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get month
     *
     * @return integer
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set total
     *
     * @param integer $total
     *
     * @return TbSalesAnaMatch
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->total;
    }
}

