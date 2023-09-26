<?php

namespace MiscBundle\Entity;

/**
 * TbVendoraddress
 */
class TbVendoraddress
{
    /**
     * @var integer
     */
    private $vendoraddressCd;

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     */
    private $sireCode;

    /**
     * @var string
     */
    private $sireAdress;

    /**
     * @var integer
     */
    private $setbefore = '0';

    /**
     * @var integer
     */
    private $setafter = '0';

    /**
     * @var \DateTime
     */
    private $checkdate;

    /**
     * @var boolean
     */
    private $stop = '0';

    /**
     * @var integer
     */
    private $price = '99999';

    /**
     * @var boolean
     */
    private $soldout = '0';

    /**
     * @var integer
     */
    private $retrycnt = '0';


    /**
     * Get vendoraddressCd
     *
     * @return integer
     */
    public function getVendoraddressCd()
    {
        return $this->vendoraddressCd;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     *
     * @return TbVendoraddress
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyoSyohinCode = $daihyoSyohinCode;

        return $this;
    }

    /**
     * Get daihyoSyohinCode
     *
     * @return string
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyoSyohinCode;
    }

    /**
     * Set sireCode
     *
     * @param string $sireCode
     *
     * @return TbVendoraddress
     */
    public function setSireCode($sireCode)
    {
        $this->sireCode = $sireCode;

        return $this;
    }

    /**
     * Get sireCode
     *
     * @return string
     */
    public function getSireCode()
    {
        return $this->sireCode;
    }

    /**
     * Set sireAdress
     *
     * @param string $sireAdress
     *
     * @return TbVendoraddress
     */
    public function setSireAdress($sireAdress)
    {
        $this->sireAdress = $sireAdress;

        return $this;
    }

    /**
     * Get sireAdress
     *
     * @return string
     */
    public function getSireAdress()
    {
        return $this->sireAdress;
    }

    /**
     * Set setbefore
     *
     * @param integer $setbefore
     *
     * @return TbVendoraddress
     */
    public function setSetbefore($setbefore)
    {
        $this->setbefore = $setbefore;

        return $this;
    }

    /**
     * Get setbefore
     *
     * @return integer
     */
    public function getSetbefore()
    {
        return $this->setbefore;
    }

    /**
     * Set setafter
     *
     * @param integer $setafter
     *
     * @return TbVendoraddress
     */
    public function setSetafter($setafter)
    {
        $this->setafter = $setafter;

        return $this;
    }

    /**
     * Get setafter
     *
     * @return integer
     */
    public function getSetafter()
    {
        return $this->setafter;
    }

    /**
     * Set checkdate
     *
     * @param \DateTime $checkdate
     *
     * @return TbVendoraddress
     */
    public function setCheckdate($checkdate)
    {
        $this->checkdate = $checkdate;

        return $this;
    }

    /**
     * Get checkdate
     *
     * @return \DateTime
     */
    public function getCheckdate()
    {
        return $this->checkdate;
    }

    /**
     * Set stop
     *
     * @param boolean $stop
     *
     * @return TbVendoraddress
     */
    public function setStop($stop)
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Get stop
     *
     * @return boolean
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return TbVendoraddress
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set soldout
     *
     * @param boolean $soldout
     *
     * @return TbVendoraddress
     */
    public function setSoldout($soldout)
    {
        $this->soldout = $soldout;

        return $this;
    }

    /**
     * Get soldout
     *
     * @return boolean
     */
    public function getSoldout()
    {
        return $this->soldout;
    }

    /**
     * Set retrycnt
     *
     * @param integer $retrycnt
     *
     * @return TbVendoraddress
     */
    public function setRetrycnt($retrycnt)
    {
        $this->retrycnt = $retrycnt;

        return $this;
    }

    /**
     * Get retrycnt
     *
     * @return integer
     */
    public function getRetrycnt()
    {
        return $this->retrycnt;
    }
}

