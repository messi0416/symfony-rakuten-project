<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherItemShippingYm
 */
class TbSalesDetailVoucherItemShippingYm
{
    /**
     * @var string
     */
    private $daihyoSyohinCode = '';


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     *
     * @return TbSalesDetailVoucherItemShippingYm
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
}
