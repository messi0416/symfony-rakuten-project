<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherOrderYm
 */
class TbSalesDetailVoucherOrderYm
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
     * @return TbSalesDetailVoucherOrderYm
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
     * @var string
     */
    private $orderYM = '';


    /**
     * Set orderYM
     *
     * @param string $orderYM
     *
     * @return TbSalesDetailVoucherOrderYm
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
