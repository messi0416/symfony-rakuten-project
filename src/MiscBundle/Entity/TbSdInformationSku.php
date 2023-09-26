<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSdInformationSku
 */
class TbSdInformationSku
{
    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     */
    private $colname;

    /**
     * @var string
     */
    private $rowname;

    /**
     * @var integer
     */
    private $sdBango;

    /**
     * @var integer
     */
    private $setBango;

    /**
     * @var string
     */
    private $kisyaHinban;

    /**
     * @var string
     */
    private $janCode;

    /**
     * @var integer
     */
    private $setHyojiJun;

    /**
     * @var string
     */
    private $uchiwake;

    /**
     * @var integer
     */
    private $sankoKakakuSyubetsu;

    /**
     * @var integer
     */
    private $sankoKakaku;

    /**
     * @var integer
     */
    private $hanbaiTanka;

    /**
     * @var integer
     */
    private $setGotoSuryo;

    /**
     * @var integer
     */
    private $zaikoSu;

    /**
     * @var integer
     */
    private $syuppinJokyo;

    /**
     * @var integer
     */
    private $zaikoRendo;

    /**
     * @var string
     */
    private $syuppinDate;

    /**
     * @var string
     */
    private $lastUpdate;


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbSdInformatinSku
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
     * Set sdBango
     *
     * @param integer $sdBango
     * @return TbSdInformatinSku
     */
    public function setSdBango($sdBango)
    {
        $this->sdBango = $sdBango;

        return $this;
    }

    /**
     * Get sdBango
     *
     * @return integer 
     */
    public function getSdBango()
    {
        return $this->sdBango;
    }

    /**
     * Set setBango
     *
     * @param integer $setBango
     * @return TbSdInformatinSku
     */
    public function setSetBango($setBango)
    {
        $this->setBango = $setBango;

        return $this;
    }

    /**
     * Get setBango
     *
     * @return integer 
     */
    public function getSetBango()
    {
        return $this->setBango;
    }

    /**
     * Set kisyaHinban
     *
     * @param string $kisyahinban
     * @return TbSdInformatinSku
     */
    public function setKisyaHinban($kisyaHinban)
    {
        $this->kisyaHinban = $kisyaHinban;

        return $this;
    }

    /**
     * Get kisyaHinban
     *
     * @return string 
     */
    public function getKisyaHinban()
    {
        return $this->kisyaHinban;
    }

    /**
     * Set janCode
     *
     * @param string $janCode
     * @return TbSdInformatinSku
     */
    public function setJanCode($janCode)
    {
        $this->janCode = $janCode;

        return $this;
    }

    /**
     * Get janCode
     *
     * @return string 
     */
    public function getJanCode()
    {
        return $this->janCode;
    }

    /**
     * Set setHyojiJun
     *
     * @param integer $setHyojiJun
     * @return TbSdInformatinSku
     */
    public function setSetHyojiJun($setHyojiJun)
    {
        $this->setHyojiJun = $setHyojiJun;

        return $this;
    }

    /**
     * Get setHyojiJun
     *
     * @return integer 
     */
    public function getSetHyojiJun()
    {
        return $this->setHyojiJun;
    }

    /**
     * Set uchiwake
     *
     * @param string $uchiwake
     * @return TbSdInformatinSku
     */
    public function setUchiwake($uchiwake)
    {
        $this->uchiwake = $uchiwake;

        return $this;
    }

    /**
     * Get uchiwake
     *
     * @return string 
     */
    public function getUchiwake()
    {
        return $this->uchiwake;
    }

    /**
     * Set sankoKakakuSyubetsu
     *
     * @param integer $sankoKakakuSyubetsu
     * @return TbSdInformatinSku
     */
    public function setSankoKakakuSyubetsu($sankoKakakuSyubetsu)
    {
        $this->sankoKakakuSyubetsu = $sankoKakakuSyubetsu;

        return $this;
    }

    /**
     * Get sankoKakakuSyubetsu
     *
     * @return integer 
     */
    public function getSankoKakakuSyubetsu()
    {
        return $this->sankoKakakuSyubetsu;
    }

    /**
     * Set sankoKakaku
     *
     * @param integer $sankoKakaku
     * @return TbSdInformatinSku
     */
    public function setSankoKakaku($sankoKakaku)
    {
        $this->sankoKakaku = $sankoKakaku;

        return $this;
    }

    /**
     * Get sankoKakaku
     *
     * @return integer 
     */
    public function getSankoKakaku()
    {
        return $this->sankoKakaku;
    }

    /**
     * Set hanbaiTanka
     *
     * @param integer $hanbaiTanka
     * @return TbSdInformatinSku
     */
    public function setHanbaiTanka($hanbaiTanka)
    {
        $this->hanbaiTanka = $hanbaiTanka;

        return $this;
    }

    /**
     * Get hanbaiTanka
     *
     * @return integer 
     */
    public function getHanbaiTanka()
    {
        return $this->hanbaiTanka;
    }

    /**
     * Set setGotoSuryo
     *
     * @param integer $setGotoSuryo
     * @return TbSdInformatinSku
     */
    public function setSetGotoSuryo($setGotoSuryo)
    {
        $this->setGotoSuryo = $setGotoSuryo;

        return $this;
    }

    /**
     * Get setGotoSuryo
     *
     * @return integer 
     */
    public function getSetGotoSuryo()
    {
        return $this->setGotoSuryo;
    }

    /**
     * Set zaikoSu
     *
     * @param integer $zaikoSu
     * @return TbSdInformatinSku
     */
    public function setZaikoSu($zaikoSu)
    {
        $this->zaikoSu = $zaikoSu;

        return $this;
    }

    /**
     * Get zaikoSu
     *
     * @return integer 
     */
    public function getZaikoSu()
    {
        return $this->zaikoSu;
    }

    /**
     * Set syuppinJokyo
     *
     * @param integer $syuppinJokyo
     * @return TbSdInformatinSku
     */
    public function setSyuppinJokyo($syuppinJokyo)
    {
        $this->syuppinJokyo = $syuppinJokyo;

        return $this;
    }

    /**
     * Get syuppinJokyo
     *
     * @return integer 
     */
    public function getSyuppinJokyo()
    {
        return $this->syuppinJokyo;
    }

    /**
     * Set zaikoRendo
     *
     * @param integer $zaikoRendo
     * @return TbSdInformatinSku
     */
    public function setZaikoRendo($zaikoRendo)
    {
        $this->zaikoRendo = $zaikoRendo;

        return $this;
    }

    /**
     * Get zaikoRendo
     *
     * @return integer 
     */
    public function getZaikoRendo()
    {
        return $this->zaikoRendo;
    }

    /**
     * Set syuppinDate
     *
     * @param string $syuppinDate
     * @return TbSdInformatinSku
     */
    public function setSyuppinDate($syuppinDate)
    {
        $this->syuppinDate = $syuppinDate;

        return $this;
    }

    /**
     * Get syuppinDate
     *
     * @return string 
     */
    public function getSyuppinDate()
    {
        return $this->syuppinDate;
    }

    /**
     * Set lastUpdate
     *
     * @param string $lastUpdate
     * @return TbSdInformatinSku
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * Get lastUpdate
     *
     * @return string 
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Set colname
     *
     * @param string $colname
     * @return TbSdInformationSku
     */
    public function setColname($colname)
    {
        $this->colname = $colname;

        return $this;
    }

    /**
     * Get colname
     *
     * @return string 
     */
    public function getColname()
    {
        return $this->colname;
    }

    /**
     * Set rowname
     *
     * @param string $rowname
     * @return TbSdInformationSku
     */
    public function setRowname($rowname)
    {
        $this->rowname = $rowname;

        return $this;
    }

    /**
     * Get rowname
     *
     * @return string 
     */
    public function getRowname()
    {
        return $this->rowname;
    }
}
