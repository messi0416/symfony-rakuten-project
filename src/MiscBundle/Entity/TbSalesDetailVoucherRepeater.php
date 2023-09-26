<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherRepeater
 */
class TbSalesDetailVoucherRepeater
{
    /**
     * @var \DateTime
     */
    private $id;


    /**
     * Get id
     *
     * @return \DateTime
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @var \DateTime
     */
    private $orderDate;


    /**
     * Get orderDate
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }
}
