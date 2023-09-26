<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSdSyohinZokusei
 */
class TbSdSyohinZokusei
{
    /**
     * @var integer
     */
    private $genreCode;

    /**
     * @var string
     */
    private $genreName;

    /**
     * @var string
     */
    private $shiyo;

    /**
     * @var string
     */
    private $koumokuName;

    /**
     * @var string
     */
    private $setteiNaiyo;

    /**
     * @var integer
     */
    private $syohinZokusei;


    /**
     * Set genreCode
     *
     * @param integer $genreCode
     * @return TbSdSyohinZokusei
     */
    public function setGenreCode($genreCode)
    {
        $this->genreCode = $genreCode;

        return $this;
    }

    /**
     * Get genreCode
     *
     * @return integer 
     */
    public function getGenreCode()
    {
        return $this->genreCode;
    }

    /**
     * Set genreName
     *
     * @param string $genreName
     * @return TbSdSyohinZokusei
     */
    public function setGenreName($genreName)
    {
        $this->genreName = $genreName;

        return $this;
    }

    /**
     * Get genreName
     *
     * @return string 
     */
    public function getGenreName()
    {
        return $this->genreName;
    }

    /**
     * Set shiyo
     *
     * @param string $shiyo
     * @return TbSdSyohinZokusei
     */
    public function setShiyo($shiyo)
    {
        $this->shiyo = $shiyo;

        return $this;
    }

    /**
     * Get shiyo
     *
     * @return string 
     */
    public function getShiyo()
    {
        return $this->shiyo;
    }

    /**
     * Set koumokuName
     *
     * @param string $koumokuName
     * @return TbSdSyohinZokusei
     */
    public function setKoumokuName($koumokuName)
    {
        $this->koumokuName = $koumokuName;

        return $this;
    }

    /**
     * Get koumokuName
     *
     * @return string 
     */
    public function getKoumokuName()
    {
        return $this->koumokuName;
    }

    /**
     * Set setteiNaiyo
     *
     * @param string $setteiNaiyo
     * @return TbSdSyohinZokusei
     */
    public function setSetteiNaiyo($setteiNaiyo)
    {
        $this->setteiNaiyo = $setteiNaiyo;

        return $this;
    }

    /**
     * Get setteiNaiyo
     *
     * @return string 
     */
    public function getSetteiNaiyo()
    {
        return $this->setteiNaiyo;
    }

    /**
     * Set syohinZokusei
     *
     * @param integer $syohinZokusei
     * @return TbSdSyohinZokusei
     */
    public function setSyohinZokusei($syohinZokusei)
    {
        $this->syohinZokusei = $syohinZokusei;

        return $this;
    }

    /**
     * Get syohinZokusei
     *
     * @return integer 
     */
    public function getSyohinZokusei()
    {
        return $this->syohinZokusei;
    }
}
