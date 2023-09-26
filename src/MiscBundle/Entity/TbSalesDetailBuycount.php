<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailBuycount
 */
class TbSalesDetailBuycount
{
    /**
     * @var integer
     */
    private $voucherNumber;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var integer
     */
    private $buyCount = '0';


    /**
     * Get voucherNumber
     *
     * @return integer
     */
    public function getVoucherNumber()
    {
        return $this->voucherNumber;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return TbSalesDetailBuycount
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return TbSalesDetailBuycount
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set buyCount
     *
     * @param integer $buyCount
     *
     * @return TbSalesDetailBuycount
     */
    public function setBuyCount($buyCount)
    {
        $this->buyCount = $buyCount;

        return $this;
    }

    /**
     * Get buyCount
     *
     * @return integer
     */
    public function getBuyCount()
    {
        return $this->buyCount;
    }
}

