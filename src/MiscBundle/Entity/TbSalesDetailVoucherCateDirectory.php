<?php

namespace MiscBundle\Entity;

/**
 * TbSalesDetailVoucherCateDirectory
 */
class TbSalesDetailVoucherCateDirectory
{
    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $cateCode;

    /**
     * @var string
     */
    private $rakutencategories1;

    /**
     * @var string
     */
    private $rakutencategories1Root;

    /**
     * @var integer
     */
    private $rakutencategories1Order;

    /**
     * @var string
     */
    private $rakutencategories1Branch;


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
     * Set cateCode
     *
     * @param string $cateCode
     *
     * @return TbSalesDetailVoucherCateDirectory
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

    /**
     * Set rakutencategories1
     *
     * @param string $rakutencategories1
     *
     * @return TbSalesDetailVoucherCateDirectory
     */
    public function setRakutencategories1($rakutencategories1)
    {
        $this->rakutencategories1 = $rakutencategories1;

        return $this;
    }

    /**
     * Get rakutencategories1
     *
     * @return string
     */
    public function getRakutencategories1()
    {
        return $this->rakutencategories1;
    }

    /**
     * Set rakutencategories1Root
     *
     * @param string $rakutencategories1Root
     *
     * @return TbSalesDetailVoucherCateDirectory
     */
    public function setRakutencategories1Root($rakutencategories1Root)
    {
        $this->rakutencategories1Root = $rakutencategories1Root;

        return $this;
    }

    /**
     * Get rakutencategories1Root
     *
     * @return string
     */
    public function getRakutencategories1Root()
    {
        return $this->rakutencategories1Root;
    }

    /**
     * Set rakutencategories1Order
     *
     * @param integer $rakutencategories1Order
     *
     * @return TbSalesDetailVoucherCateDirectory
     */
    public function setRakutencategories1Order($rakutencategories1Order)
    {
        $this->rakutencategories1Order = $rakutencategories1Order;

        return $this;
    }

    /**
     * Get rakutencategories1Order
     *
     * @return integer
     */
    public function getRakutencategories1Order()
    {
        return $this->rakutencategories1Order;
    }

    /**
     * Set rakutencategories1Branch
     *
     * @param string $rakutencategories1Branch
     *
     * @return TbSalesDetailVoucherCateDirectory
     */
    public function setRakutencategories1Branch($rakutencategories1Branch)
    {
        $this->rakutencategories1Branch = $rakutencategories1Branch;

        return $this;
    }

    /**
     * Get rakutencategories1Branch
     *
     * @return string
     */
    public function getRakutencategories1Branch()
    {
        return $this->rakutencategories1Branch;
    }
}

