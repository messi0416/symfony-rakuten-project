<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherRepeaterShop
 */
class TbSalesDetailVoucherRepeaterShop
{
    /**
     * @var string
     */
    private $id;


    /**
     * Set id
     *
     * @param string $id
     *
     * @return TbSalesDetailVoucherRepeaterShop
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string
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
     * @return TbSalesDetailVoucherRepeaterShop
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
