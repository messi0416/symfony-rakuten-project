<?php

namespace MiscBundle\Entity;

/**
 * TbImageCheck
 */
class TbImageCheck
{
    /**
     * @var string
     */
    private $mallKbn;

    /**
     * @var string
     */
    private $daihyoSyohinCode = '';

    /**
     * @var integer
     */
    private $idx;

    /**
     * @var string
     */
    private $pcMKbn;

    /**
     * @var integer
     */
    private $no;

    /**
     * @var string
     */
    private $picfolder;

    /**
     * @var string
     */
    private $picname;

    /**
     * @var string
     */
    private $url;


    /**
     * Set mallKbn
     *
     * @param string $mallKbn
     *
     * @return TbImageCheck
     */
    public function setMallKbn($mallKbn)
    {
        $this->mallKbn = $mallKbn;

        return $this;
    }

    /**
     * Get mallKbn
     *
     * @return string
     */
    public function getMallKbn()
    {
        return $this->mallKbn;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     *
     * @return TbImageCheck
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
     * Set idx
     *
     * @param integer $idx
     *
     * @return TbImageCheck
     */
    public function setIdx($idx)
    {
        $this->idx = $idx;

        return $this;
    }

    /**
     * Get idx
     *
     * @return integer
     */
    public function getIdx()
    {
        return $this->idx;
    }

    /**
     * Set pcMKbn
     *
     * @param string $pcMKbn
     *
     * @return TbImageCheck
     */
    public function setPcMKbn($pcMKbn)
    {
        $this->pcMKbn = $pcMKbn;

        return $this;
    }

    /**
     * Get pcMKbn
     *
     * @return string
     */
    public function getPcMKbn()
    {
        return $this->pcMKbn;
    }

    /**
     * Set no
     *
     * @param integer $no
     *
     * @return TbImageCheck
     */
    public function setNo($no)
    {
        $this->no = $no;

        return $this;
    }

    /**
     * Get no
     *
     * @return integer
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Set picfolder
     *
     * @param string $picfolder
     *
     * @return TbImageCheck
     */
    public function setPicfolder($picfolder)
    {
        $this->picfolder = $picfolder;

        return $this;
    }

    /**
     * Get picfolder
     *
     * @return string
     */
    public function getPicfolder()
    {
        return $this->picfolder;
    }

    /**
     * Set picname
     *
     * @param string $picname
     *
     * @return TbImageCheck
     */
    public function setPicname($picname)
    {
        $this->picname = $picname;

        return $this;
    }

    /**
     * Get picname
     *
     * @return string
     */
    public function getPicname()
    {
        return $this->picname;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return TbImageCheck
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
}

