<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tb1688VendorProduct
 */
class Tb1688VendorProduct
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $alibaba_vendor_id;

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var integer
     */
    private $offer_id = 0;

    /**
     * @var \DateTime
     */
    private $last_checked;

    /**
     * @var string
     */
    private $daihyo_syohin_code = '';

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;


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
     * Set alibaba_vendor_id
     *
     * @param integer $alibabaVendorId
     * @return Tb1688VendorProduct
     */
    public function setAlibabaVendorId($alibabaVendorId)
    {
        $this->alibaba_vendor_id = $alibabaVendorId;

        return $this;
    }

    /**
     * Get alibaba_vendor_id
     *
     * @return integer 
     */
    public function getAlibabaVendorId()
    {
        return $this->alibaba_vendor_id;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Tb1688VendorProduct
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set offer_id
     *
     * @param integer $offerId
     * @return Tb1688VendorProduct
     */
    public function setOfferId($offerId)
    {
        $this->offer_id = $offerId;

        return $this;
    }

    /**
     * Get offer_id
     *
     * @return integer 
     */
    public function getOfferId()
    {
        return $this->offer_id;
    }

    /**
     * Set last_checked
     *
     * @param \DateTime $lastChecked
     * @return Tb1688VendorProduct
     */
    public function setLastChecked($lastChecked)
    {
        $this->last_checked = $lastChecked;

        return $this;
    }

    /**
     * Get last_checked
     *
     * @return \DateTime 
     */
    public function getLastChecked()
    {
        return $this->last_checked;
    }

    /**
     * Set daihyo_syohin_code
     *
     * @param string $daihyoSyohinCode
     * @return Tb1688VendorProduct
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyo_syohin_code = $daihyoSyohinCode;

        return $this;
    }

    /**
     * Get daihyo_syohin_code
     *
     * @return string 
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyo_syohin_code;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Tb1688VendorProduct
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
     * @return Tb1688VendorProduct
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
    /**
     * @ORM\PrePersist
     */
    public function fillTimestamps()
    {
        // Add your code here
    }
}
