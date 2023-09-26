<?php

namespace MiscBundle\Entity;

/**
 * TbMallPaymentMethod
 */
class TbMallPaymentMethod
{
    /**
     * @var integer
     */
    private $neMallId;

    /**
     * @var integer
     */
    private $paymentId = '0';

    /**
     * @var float
     */
    private $paymentCostRatio = '0';


    /**
     * Set neMallId
     *
     * @param integer $neMallId
     *
     * @return TbMallPaymentMethod
     */
    public function setNeMallId($neMallId)
    {
        $this->neMallId = $neMallId;

        return $this;
    }

    /**
     * Get neMallId
     *
     * @return integer
     */
    public function getNeMallId()
    {
        return $this->neMallId;
    }

    /**
     * Set paymentId
     *
     * @param integer $paymentId
     *
     * @return TbMallPaymentMethod
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * Get paymentId
     *
     * @return integer
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * Set paymentCostRatio
     *
     * @param float $paymentCostRatio
     *
     * @return TbMallPaymentMethod
     */
    public function setPaymentCostRatio($paymentCostRatio)
    {
        $this->paymentCostRatio = $paymentCostRatio;

        return $this;
    }

    /**
     * Get paymentCostRatio
     *
     * @return float
     */
    public function getPaymentCostRatio()
    {
        return $this->paymentCostRatio;
    }
}

