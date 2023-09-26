<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherSireOrderYm
 */
class TbSalesDetailVoucherSireOrderYm
{
    /**
     * @var string
     */
    private $sireCode;


    /**
     * Set sireCode
     *
     * @param string $sireCode
     *
     * @return TbSalesDetailVoucherSireOrderYm
     */
    public function setSireCode($sireCode)
    {
        $this->sireCode = $sireCode;

        return $this;
    }

    /**
     * Get sireCode
     *
     * @return string
     */
    public function getSireCode()
    {
        return $this->sireCode;
    }
}

