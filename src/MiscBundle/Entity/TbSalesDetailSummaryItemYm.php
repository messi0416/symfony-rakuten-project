<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSalesDetailSummaryItemYm
 */
class TbSalesDetailSummaryItemYm
{
    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var integer
     */
    private $orderYM;

    /**
     * @var integer
     */
    private $voucherQuantity;

    /**
     * @var integer
     */
    private $orderQuantity;

    /**
     * @var integer
     */
    private $detailAmountIncludingCost;

    /**
     * @var integer
     */
    private $detailAmount;

    /**
     * @var integer
     */
    private $detailGrossProfit;

    /**
     * @var integer
     */
    private $additionalAmount;

    /**
     * @var integer
     */
    private $subtractionAmount;


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbSalesDetailSummaryItemYm
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
     * Set orderYM
     *
     * @param integer $orderYM
     * @return TbSalesDetailSummaryItemYm
     */
    public function setOrderYM($orderYM)
    {
        $this->orderYM = $orderYM;

        return $this;
    }

    /**
     * Get orderYM
     *
     * @return integer 
     */
    public function getOrderYM()
    {
        return $this->orderYM;
    }

    /**
     * Set voucherQuantity
     *
     * @param integer $voucherQuantity
     * @return TbSalesDetailSummaryItemYm
     */
    public function setVoucherQuantity($voucherQuantity)
    {
        $this->voucherQuantity = $voucherQuantity;

        return $this;
    }

    /**
     * Get voucherQuantity
     *
     * @return integer 
     */
    public function getVoucherQuantity()
    {
        return $this->voucherQuantity;
    }

    /**
     * Set orderQuantity
     *
     * @param integer $orderQuantity
     * @return TbSalesDetailSummaryItemYm
     */
    public function setOrderQuantity($orderQuantity)
    {
        $this->orderQuantity = $orderQuantity;

        return $this;
    }

    /**
     * Get orderQuantity
     *
     * @return integer 
     */
    public function getOrderQuantity()
    {
        return $this->orderQuantity;
    }

    /**
     * Set detailAmountIncludingCost
     *
     * @param integer $detailAmountIncludingCost
     * @return TbSalesDetailSummaryItemYm
     */
    public function setDetailAmountIncludingCost($detailAmountIncludingCost)
    {
        $this->detailAmountIncludingCost = $detailAmountIncludingCost;

        return $this;
    }

    /**
     * Get detailAmountIncludingCost
     *
     * @return integer 
     */
    public function getDetailAmountIncludingCost()
    {
        return $this->detailAmountIncludingCost;
    }

    /**
     * Set detailAmount
     *
     * @param integer $detailAmount
     * @return TbSalesDetailSummaryItemYm
     */
    public function setDetailAmount($detailAmount)
    {
        $this->detailAmount = $detailAmount;

        return $this;
    }

    /**
     * Get detailAmount
     *
     * @return integer 
     */
    public function getDetailAmount()
    {
        return $this->detailAmount;
    }

    /**
     * Set detailGrossProfit
     *
     * @param integer $detailGrossProfit
     * @return TbSalesDetailSummaryItemYm
     */
    public function setDetailGrossProfit($detailGrossProfit)
    {
        $this->detailGrossProfit = $detailGrossProfit;

        return $this;
    }

    /**
     * Get detailGrossProfit
     *
     * @return integer 
     */
    public function getDetailGrossProfit()
    {
        return $this->detailGrossProfit;
    }

    /**
     * Set additionalAmount
     *
     * @param integer $additionalAmount
     * @return TbSalesDetailSummaryItemYm
     */
    public function setAdditionalAmount($additionalAmount)
    {
        $this->additionalAmount = $additionalAmount;

        return $this;
    }

    /**
     * Get additionalAmount
     *
     * @return integer 
     */
    public function getAdditionalAmount()
    {
        return $this->additionalAmount;
    }

    /**
     * Set subtractionAmount
     *
     * @param integer $subtractionAmount
     * @return TbSalesDetailSummaryItemYm
     */
    public function setSubtractionAmount($subtractionAmount)
    {
        $this->subtractionAmount = $subtractionAmount;

        return $this;
    }

    /**
     * Get subtractionAmount
     *
     * @return integer 
     */
    public function getSubtractionAmount()
    {
        return $this->subtractionAmount;
    }
}
