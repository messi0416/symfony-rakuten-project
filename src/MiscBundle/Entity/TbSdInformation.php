<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSdInformation
 */
class TbSdInformation
{
    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     */
    private $daihyoSyohinName;

    /**
     * @var string
     */
    private $baseSize;

    /**
     * @var string
     */
    private $baseSozai;

    /**
     * @var string
     */
    private $baseChuiJiko;

    /**
     * @var string
     */
    private $baseComment;

    /**
     * @var integer
     */
    private $sdBango;

    /**
     * @var integer
     */
    private $registrationState;

    /**
     * @var string
     */
    private $syohinTitle;

    /**
     * @var string
     */
    private $brandName;

    /**
     * @var integer
     */
    private $genre;

    /**
     * @var integer
     */
    private $target;

    /**
     * @var string
     */
    private $syohinZokusei;

    /**
     * @var string
     */
    private $size;

    /**
     * @var string
     */
    private $sozai;

    /**
     * @var string
     */
    private $seisanchi;

    /**
     * @var string
     */
    private $naiyoRyo;

    /**
     * @var string
     */
    private $genzaiRyo;

    /**
     * @var string
     */
    private $hozonHoho;

    /**
     * @var string
     */
    private $kikakuHosoku;

    /**
     * @var string
     */
    private $package;

    /**
     * @var integer
     */
    private $seizoNen;

    /**
     * @var integer
     */
    private $syohinFuda;

    /**
     * @var integer
     */
    private $tokuteiHoken;

    /**
     * @var string
     */
    private $chuiJiko;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     */
    private $kyokaBango;

    /**
     * @var integer
     */
    private $syukkaNoki;

    /**
     * @var string
     */
    private $syukkaYotei;

    /**
     * @var integer
     */
    private $zaikoKagiri;

    /**
     * @var integer
     */
    private $bettoSoryo;

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
     * @return TbSdInformation
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
     * @return TbSdInformation
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
     * Set registrationState
     *
     * @param integer $registrationState
     * @return TbSdInformation
     */
    public function setRegistrationState($registrationState)
    {
        $this->registrationState = $registrationState;

        return $this;
    }

    /**
     * Get registrationState
     *
     * @return integer 
     */
    public function getRegistrationState()
    {
        return $this->registrationState;
    }

    /**
     * Set syohinTitle
     *
     * @param string $syohinTitle
     * @return TbSdInformation
     */
    public function setSyohinTitle($syohinTitle)
    {
        $this->syohinTitle = $syohinTitle;

        return $this;
    }

    /**
     * Get syohinTitle
     *
     * @return string 
     */
    public function getSyohinTitle()
    {
        return $this->syohinTitle;
    }

    /**
     * Set brandName
     *
     * @param string $brandName
     * @return TbSdInformation
     */
    public function setBrandName($brandName)
    {
        $this->brandName = $brandName;

        return $this;
    }

    /**
     * Get brandName
     *
     * @return string 
     */
    public function getBrandName()
    {
        return $this->brandName;
    }

    /**
     * Set genre
     *
     * @param integer $genre
     * @return TbSdInformation
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get genre
     *
     * @return integer 
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * Set target
     *
     * @param integer $target
     * @return TbSdInformation
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return integer 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set syohinZokusei
     *
     * @param string $syohinZokusei
     * @return TbSdInformation
     */
    public function setSyohinZokusei($syohinZokusei)
    {
        $this->syohinZokusei = $syohinZokusei;

        return $this;
    }

    /**
     * Get syohinZokusei
     *
     * @return string 
     */
    public function getSyohinZokusei()
    {
        return $this->syohinZokusei;
    }

    /**
     * Set size
     *
     * @param string $size
     * @return TbSdInformation
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return string 
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set sozai
     *
     * @param string $sozai
     * @return TbSdInformation
     */
    public function setSozai($sozai)
    {
        $this->sozai = $sozai;

        return $this;
    }

    /**
     * Get sozai
     *
     * @return string 
     */
    public function getSozai()
    {
        return $this->sozai;
    }

    /**
     * Set seisanchi
     *
     * @param string $seisanchi
     * @return TbSdInformation
     */
    public function setSeisanchi($seisanchi)
    {
        $this->seisanchi = $seisanchi;

        return $this;
    }

    /**
     * Get seisanchi
     *
     * @return string 
     */
    public function getSeisanchi()
    {
        return $this->seisanchi;
    }

    /**
     * Set naiyoRyo
     *
     * @param string $naiyoRyo
     * @return TbSdInformation
     */
    public function setNaiyoRyo($naiyoRyo)
    {
        $this->naiyoRyo = $naiyoRyo;

        return $this;
    }

    /**
     * Get naiyoRyo
     *
     * @return string 
     */
    public function getNaiyoRyo()
    {
        return $this->naiyoRyo;
    }

    /**
     * Set genzaiRyo
     *
     * @param string $genzaiRyo
     * @return TbSdInformation
     */
    public function setGenzaiRyo($genzaiRyo)
    {
        $this->genzaiRyo = $genzaiRyo;

        return $this;
    }

    /**
     * Get genzaiRyo
     *
     * @return string 
     */
    public function getGenzaiRyo()
    {
        return $this->genzaiRyo;
    }

    /**
     * Set hozonHoho
     *
     * @param string $hozonHoho
     * @return TbSdInformation
     */
    public function setHozonHoho($hozonHoho)
    {
        $this->hozonHoho = $hozonHoho;

        return $this;
    }

    /**
     * Get hozonHoho
     *
     * @return string 
     */
    public function getHozonHoho()
    {
        return $this->hozonHoho;
    }

    /**
     * Set kikakuHosoku
     *
     * @param string $kikakuHosoku
     * @return TbSdInformation
     */
    public function setKikakuHosoku($kikakuHosoku)
    {
        $this->kikakuHosoku = $kikakuHosoku;

        return $this;
    }

    /**
     * Get kikakuHosoku
     *
     * @return string 
     */
    public function getKikakuHosoku()
    {
        return $this->kikakuHosoku;
    }

    /**
     * Set package
     *
     * @param string $package
     * @return TbSdInformation
     */
    public function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return string 
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set seizoNen
     *
     * @param integer $seizoNen
     * @return TbSdInformation
     */
    public function setSeizoNen($seizoNen)
    {
        $this->seizoNen = $seizoNen;

        return $this;
    }

    /**
     * Get seizoNen
     *
     * @return integer 
     */
    public function getSeizoNen()
    {
        return $this->seizoNen;
    }

    /**
     * Set syohinFuda
     *
     * @param integer $syohinFuda
     * @return TbSdInformation
     */
    public function setSyohinFuda($syohinFuda)
    {
        $this->syohinFuda = $syohinFuda;

        return $this;
    }

    /**
     * Get syohinFuda
     *
     * @return integer 
     */
    public function getSyohinFuda()
    {
        return $this->syohinFuda;
    }

    /**
     * Set tokuteiHoken
     *
     * @param integer $tokuteiHoken
     * @return TbSdInformation
     */
    public function setTokuteiHoken($tokuteiHoken)
    {
        $this->tokuteiHoken = $tokuteiHoken;

        return $this;
    }

    /**
     * Get tokuteiHoken
     *
     * @return integer 
     */
    public function getTokuteiHoken()
    {
        return $this->tokuteiHoken;
    }

    /**
     * Set chuiJiko
     *
     * @param string $chuiJiko
     * @return TbSdInformation
     */
    public function setChuiJiko($chuiJiko)
    {
        $this->chuiJiko = $chuiJiko;

        return $this;
    }

    /**
     * Get chuiJiko
     *
     * @return string 
     */
    public function getChuiJiko()
    {
        return $this->chuiJiko;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return TbSdInformation
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set kyokaBango
     *
     * @param string $kyokaBango
     * @return TbSdInformation
     */
    public function setKyokaBango($kyokaBango)
    {
        $this->kyokaBango = $kyokaBango;

        return $this;
    }

    /**
     * Get kyokaBango
     *
     * @return string 
     */
    public function getKyokaBango()
    {
        return $this->kyokaBango;
    }

    /**
     * Set syukkaNoki
     *
     * @param integer $syukkaNoki
     * @return TbSdInformation
     */
    public function setSyukkaNoki($syukkaNoki)
    {
        $this->syukkaNoki = $syukkaNoki;

        return $this;
    }

    /**
     * Get syukkaNoki
     *
     * @return integer 
     */
    public function getSyukkaNoki()
    {
        return $this->syukkaNoki;
    }

    /**
     * Set syukkaYotei
     *
     * @param string $syukkaYotei
     * @return TbSdInformation
     */
    public function setSyukkaYotei($syukkaYotei)
    {
        $this->syukkaYotei = $syukkaYotei;

        return $this;
    }

    /**
     * Get syukkaYotei
     *
     * @return string 
     */
    public function getSyukkaYotei()
    {
        return $this->syukkaYotei;
    }

    /**
     * Set zaikoKagiri
     *
     * @param integer $zaikoKagiri
     * @return TbSdInformation
     */
    public function setZaikoKagiri($zaikoKagiri)
    {
        $this->zaikoKagiri = $zaikoKagiri;

        return $this;
    }

    /**
     * Get zaikoKagiri
     *
     * @return integer 
     */
    public function getZaikoKagiri()
    {
        return $this->zaikoKagiri;
    }

    /**
     * Set bettoSoryo
     *
     * @param integer $bettoSoryo
     * @return TbSdInformation
     */
    public function setBettoSoryo($bettoSoryo)
    {
        $this->bettoSoryo = $bettoSoryo;

        return $this;
    }

    /**
     * Get bettoSoryo
     *
     * @return integer 
     */
    public function getBettoSoryo()
    {
        return $this->bettoSoryo;
    }

    /**
     * Set syuppinDate
     *
     * @param string $syuppinDate
     * @return TbSdInformation
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
     * @return TbSdInformation
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
     * Set daihyoSyohinName
     *
     * @param string $daihyoSyohinName
     * @return TbSdInformation
     */
    public function setDaihyoSyohinName($daihyoSyohinName)
    {
        $this->daihyoSyohinName = $daihyoSyohinName;

        return $this;
    }

    /**
     * Get daihyoSyohinName
     *
     * @return string 
     */
    public function getDaihyoSyohinName()
    {
        return $this->daihyoSyohinName;
    }

    /**
     * Set baseSize
     *
     * @param string $baseSize
     * @return TbSdInformation
     */
    public function setBaseSize($baseSize)
    {
        $this->baseSize = $baseSize;

        return $this;
    }

    /**
     * Get baseSize
     *
     * @return string 
     */
    public function getBaseSize()
    {
        return $this->baseSize;
    }

    /**
     * Set baseSozai
     *
     * @param string $baseSozai
     * @return TbSdInformation
     */
    public function setBaseSozai($baseSozai)
    {
        $this->baseSozai = $baseSozai;

        return $this;
    }

    /**
     * Get baseSozai
     *
     * @return string 
     */
    public function getBaseSozai()
    {
        return $this->baseSozai;
    }

    /**
     * Set baseChuiJiko
     *
     * @param string $baseChuiJiko
     * @return TbSdInformation
     */
    public function setBaseChuiJiko($baseChuiJiko)
    {
        $this->baseChuiJiko = $baseChuiJiko;

        return $this;
    }

    /**
     * Get baseChuiJiko
     *
     * @return string 
     */
    public function getBaseChuiJiko()
    {
        return $this->baseChuiJiko;
    }

    /**
     * Set baseComment
     *
     * @param string $baseComment
     * @return TbSdInformation
     */
    public function setBaseComment($baseComment)
    {
        $this->baseComment = $baseComment;

        return $this;
    }

    /**
     * Get baseComment
     *
     * @return string 
     */
    public function getBaseComment()
    {
        return $this->baseComment;
    }
}
