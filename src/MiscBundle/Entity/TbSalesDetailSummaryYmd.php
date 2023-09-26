<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSalesDetailSummaryYmd
 */
class TbSalesDetailSummaryYmd
{
    /**
     * @var \DateTime
     */
    private $orderDate;

    /**
     * @var integer
     */
    private $totalSales;

    /**
     * @var integer
     */
    private $totalGrossProfit;


    /**
     * Get orderDate
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * Set totalSales
     *
     * @param integer $totalSales
     * @return TbSalesDetailSummaryYmd
     */
    public function setTotalSales($totalSales)
    {
        $this->totalSales = $totalSales;

        return $this;
    }

    /**
     * Get totalSales
     *
     * @return integer
     */
    public function getTotalSales()
    {
        return $this->totalSales;
    }

    /**
     * Set totalGrossProfit
     *
     * @param integer $totalGrossProfit
     * @return TbSalesDetailSummaryYmd
     */
    public function setTotalGrossProfit($totalGrossProfit)
    {
        $this->totalGrossProfit = $totalGrossProfit;

        return $this;
    }

    /**
     * Get totalGrossProfit
     *
     * @return integer
     */
    public function getTotalGrossProfit()
    {
        return $this->totalGrossProfit;
    }
}
