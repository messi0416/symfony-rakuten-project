<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherItemOrderYm
 */
class TbSalesDetailVoucherItemOrderYm
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
     * @return TbSalesDetailVoucherItemOrderYm
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
     * @var string
     */
    private $orderYM = '';


    /**
     * Set orderYM
     *
     * @param string $orderYM
     *
     * @return TbSalesDetailVoucherItemOrderYm
     */
    public function setOrderYM($orderYM)
    {
        $this->orderYM = $orderYM;

        return $this;
    }

    /**
     * Get orderYM
     *
     * @return string
     */
    public function getOrderYM()
    {
        return $this->orderYM;
    }
}
