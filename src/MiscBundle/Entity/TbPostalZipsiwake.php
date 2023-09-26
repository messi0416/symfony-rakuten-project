<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbPostalZipsiwake
 */
class TbPostalZipsiwake
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $zipCode;

    /**
     * @var string
     */
    private $siwakeCode;


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
     * Set zipCode
     *
     * @param string $zipCode
     * @return TbPostalZipsiwake
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string 
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set siwakeCode
     *
     * @param string $siwakeCode
     * @return TbPostalZipsiwake
     */
    public function setSiwakeCode($siwakeCode)
    {
        $this->siwakeCode = $siwakeCode;

        return $this;
    }

    /**
     * Get siwakeCode
     *
     * @return string 
     */
    public function getSiwakeCode()
    {
        return $this->siwakeCode;
    }
}
