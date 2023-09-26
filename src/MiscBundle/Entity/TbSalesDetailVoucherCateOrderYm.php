<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherCateOrderYm
 */
class TbSalesDetailVoucherCateOrderYm
{
    /**
     * @var string
     */
    private $cateCode;


    /**
     * Set cateCode
     *
     * @param string $cateCode
     *
     * @return TbSalesDetailVoucherCateOrderYm
     */
    public function setCateCode($cateCode)
    {
        $this->cateCode = $cateCode;

        return $this;
    }

    /**
     * Get cateCode
     *
     * @return string
     */
    public function getCateCode()
    {
        return $this->cateCode;
    }
}

