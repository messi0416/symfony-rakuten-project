<?php

namespace MiscBundle\Entity;

/**
 * TbPaymentMethod
 */
class TbPaymentMethod
{
    /**
     * @var integer
     */
    private $paymentId = '0';

    /**
     * @var string
     */
    private $paymentName;

    /**
     * @var float
     */
    private $paymentCostRatio = '0';


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
     * Set paymentName
     *
     * @param string $paymentName
     *
     * @return TbPaymentMethod
     */
    public function setPaymentName($paymentName)
    {
        $this->paymentName = $paymentName;

        return $this;
    }

    /**
     * Get paymentName
     *
     * @return string
     */
    public function getPaymentName()
    {
        return $this->paymentName;
    }

    /**
     * Set paymentCostRatio
     *
     * @param float $paymentCostRatio
     *
     * @return TbPaymentMethod
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

