<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucher
 */
class TbSalesDetailVoucher
{
    /**
     * @var integer
     */
    private $id;


    /**
     * Get id
     *
     * @return integer
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
     * Set orderDate
     *
     * @param \DateTime $orderDate
     *
     * @return TbSalesDetailVoucher
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $orderDate;

        return $this;
    }

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
