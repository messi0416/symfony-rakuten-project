<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherShippingYm
 */
class TbSalesDetailVoucherShippingYm
{
    /**
     * @var integer
     */
    private $id = '0';


    /**
     * Set id
     *
     * @param integer $id
     *
     * @return TbSalesDetailVoucherShippingYm
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
