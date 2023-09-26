<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRealShopReturnReport
 */
class TbRealShopReturnReport
{
    /**
     * @var \DateTime
     */
    private $return_date;

    /**
     * @var integer
     */
    private $number = 0;

    /**
     * @var string
     */
    private $ne_syohin_syohin_code;

    /**
     * @var integer
     */
    private $shop_stock = 0;

    /**
     * @var integer
     */
    private $move_num = 0;

    /**
     * @var integer
     */
    private $status = 0;

    /**
     * @var integer
     */
    private $create_account_id = 0;

    /**
     * @var string
     */
    private $create_account_name = '';

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;


    /**
     * Set return_date
     *
     * @param \DateTime $returnDate
     * @return TbRealShopReturnReport
     */
    public function setReturnDate($returnDate)
    {
        $this->return_date = $returnDate;

        return $this;
    }

    /**
     * Get return_date
     *
     * @return \DateTime 
     */
    public function getReturnDate()
    {
        return $this->return_date;
    }

    /**
     * Set number
     *
     * @param integer $number
     * @return TbRealShopReturnReport
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return integer 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set ne_syohin_syohin_code
     *
     * @param string $neSyohinSyohinCode
     * @return TbRealShopReturnReport
     */
    public function setNeSyohinSyohinCode($neSyohinSyohinCode)
    {
        $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

        return $this;
    }

    /**
     * Get ne_syohin_syohin_code
     *
     * @return string 
     */
    public function getNeSyohinSyohinCode()
    {
        return $this->ne_syohin_syohin_code;
    }

    /**
     * Set shop_stock
     *
     * @param integer $shopStock
     * @return TbRealShopReturnReport
     */
    public function setShopStock($shopStock)
    {
        $this->shop_stock = $shopStock;

        return $this;
    }

    /**
     * Get shop_stock
     *
     * @return integer 
     */
    public function getShopStock()
    {
        return $this->shop_stock;
    }

    /**
     * Set move_num
     *
     * @param integer $moveNum
     * @return TbRealShopReturnReport
     */
    public function setMoveNum($moveNum)
    {
        $this->move_num = $moveNum;

        return $this;
    }

    /**
     * Get move_num
     *
     * @return integer 
     */
    public function getMoveNum()
    {
        return $this->move_num;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbRealShopReturnReport
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set create_account_id
     *
     * @param integer $createAccountId
     * @return TbRealShopReturnReport
     */
    public function setCreateAccountId($createAccountId)
    {
        $this->create_account_id = $createAccountId;

        return $this;
    }

    /**
     * Get create_account_id
     *
     * @return integer 
     */
    public function getCreateAccountId()
    {
        return $this->create_account_id;
    }

    /**
     * Set create_account_name
     *
     * @param string $createAccountName
     * @return TbRealShopReturnReport
     */
    public function setCreateAccountName($createAccountName)
    {
        $this->create_account_name = $createAccountName;

        return $this;
    }

    /**
     * Get create_account_name
     *
     * @return string 
     */
    public function getCreateAccountName()
    {
        return $this->create_account_name;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbRealShopReturnReport
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbRealShopReturnReport
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
