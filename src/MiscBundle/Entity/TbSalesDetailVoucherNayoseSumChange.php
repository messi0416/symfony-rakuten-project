<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherNayoseSumChange
 */
class TbSalesDetailVoucherNayoseSumChange
{
    /**
     * @var integer
     */
    private $idx = '0';


    /**
     * Set idx
     *
     * @param integer $idx
     *
     * @return TbSalesDetailVoucherNayoseSumChange
     */
    public function setIdx($idx)
    {
        $this->idx = $idx;

        return $this;
    }

    /**
     * Get idx
     *
     * @return integer
     */
    public function getIdx()
    {
        return $this->idx;
    }
}

