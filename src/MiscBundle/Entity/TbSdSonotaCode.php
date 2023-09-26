<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSdSonotaCode
 */
class TbSdSonotaCode
{
    /**
     * @var string
     */
    private $komoku;

    /**
     * @var string
     */
    private $syosai;

    /**
     * @var integer
     */
    private $code;


    /**
     * Set komoku
     *
     * @param string $komoku
     * @return TbSdSonotaCode
     */
    public function setKomoku($komoku)
    {
        $this->komoku = $komoku;

        return $this;
    }

    /**
     * Get komoku
     *
     * @return string 
     */
    public function getKomoku()
    {
        return $this->komoku;
    }

    /**
     * Set syosai
     *
     * @param string $syosai
     * @return TbSdSonotaCode
     */
    public function setSyosai($syosai)
    {
        $this->syosai = $syosai;

        return $this;
    }

    /**
     * Get syosai
     *
     * @return string 
     */
    public function getSyosai()
    {
        return $this->syosai;
    }

    /**
     * Set code
     *
     * @param integer $code
     * @return TbSdSonotaCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer 
     */
    public function getCode()
    {
        return $this->code;
    }
}
