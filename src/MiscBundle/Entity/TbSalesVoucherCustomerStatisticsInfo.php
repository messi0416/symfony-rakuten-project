<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSalesVoucherCustomerStatisticsInfo
 */
class TbSalesVoucherCustomerStatisticsInfo
{
    /**
     * @var integer
     */
    private $voucherNumber;

    /**
     * @var string
     */
    private $hashTel;

    /**
     * @var string
     */
    private $prefectureCd;


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
     * Set hashTel
     *
     * @param string $hashTel
     * @return TbSalesVoucherCustomerStatisticsInfo
     */
    public function setHashTel($hashTel)
    {
        $this->hashTel = $hashTel;

        return $this;
    }

    /**
     * Get hashTel
     *
     * @return string 
     */
    public function getHashTel()
    {
        return $this->hashTel;
    }

    /**
     * Set prefectureCd
     *
     * @param string $prefectureCd
     * @return TbSalesVoucherCustomerStatisticsInfo
     */
    public function setPrefectureCd($prefectureCd)
    {
        $this->prefectureCd = $prefectureCd;

        return $this;
    }

    /**
     * Get prefectureCd
     *
     * @return string 
     */
    public function getPrefectureCd()
    {
        return $this->prefectureCd;
    }
}
