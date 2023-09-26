<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailProfit
 */
class TbSalesDetailProfit
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
     * @return TbSalesDetailProfit
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
    /**
     * @var \DateTime
     */
    private $orderDate;


    /**
     * Set orderDate
     *
     * @param \DateTime $orderDate
     *
     * @return TbSalesDetailProfit
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
