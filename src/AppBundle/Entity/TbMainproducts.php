<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbMainproducts
 *
 * @ORM\Table(name="tb_mainproducts")
 * @ORM\Entity
 */
class TbMainproducts
{
    /**
     * @var string
     *
     * @ORM\Column(name="daihyo_syohin_code", type="string", length=30)
     * @ORM\Id
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     *
     * @ORM\Column(name="daihyo_syohin_name", type="string", length=255)
     */
    private $daihyoSyohinName;

    /**
     * @var string
     *
     * @ORM\Column(name="サイズについて", type="string", length=2000)
     */
    private $aboutSize;

    /**
     * @var string
     *
     * @ORM\Column(name="素材について", type="string", length=1000)
     */
    private $aboutSozai;

    /**
     * @var string
     *
     * @ORM\Column(name="使用上の注意", type="string", length=2000)
     */
    private $shiyouChui;

    /**
     * @var string
     *
     * @ORM\Column(name="商品コメントPC", type="string", length=3000)
     */
    private $syohinCommentPC;

    /**
     * @var string
     *
     * @ORM\Column(name="NEディレクトリID", type="string", length=13)
     */
    private $NEDirectoryID;

    /**
     * @var string
     *
     * @ORM\Column(name="YAHOOディレクトリID", type="string", length=13)
     */
    private $YahooDirectoryID;

    /**
     * @var integer
     *
     * @ORM\Column(name="総在庫数", type="integer")
     */
    private $soZaikoSu;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP1", type="string")
     */
    private $picfolderP1;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP2", type="string")
     */
    private $picfolderP2;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP3", type="string")
     */
    private $picfolderP3;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP4", type="string")
     */
    private $picfolderP4;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP5", type="string")
     */
    private $picfolderP5;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP6", type="string")
     */
    private $picfolderP6;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP7", type="string")
     */
    private $picfolderP7;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP8", type="string")
     */
    private $picfolderP8;

    /**
     * @var string
     *
     * @ORM\Column(name="picfolderP9", type="string")
     */
    private $picfolderP9;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP1", type="string")
     */
    private $picnameP1;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP2", type="string")
     */
    private $picnameP2;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP3", type="string")
     */
    private $picnameP3;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP4", type="string")
     */
    private $picnameP4;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP5", type="string")
     */
    private $picnameP5;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP6", type="string")
     */
    private $picnameP6;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP7", type="string")
     */
    private $picnameP7;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP8", type="string")
     */
    private $picnameP8;

    /**
     * @var string
     *
     * @ORM\Column(name="picnameP9", type="string")
     */
    private $picnameP9;


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbMainproducts
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
     * Set daihyoSyohinName
     *
     * @param string $daihyoSyohinName
     * @return TbMainproducts
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
     * Set aboutSize
     *
     * @param string $aboutSize
     * @return TbMainproducts
     */
    public function setAboutSize($aboutSize)
    {
        $this->aboutSize = $aboutSize;

        return $this;
    }

    /**
     * Get aboutSize
     *
     * @return string 
     */
    public function getAboutSize()
    {
        return $this->aboutSize;
    }

    /**
     * Set aboutSozai
     *
     * @param string $aboutSozai
     * @return TbMainproducts
     */
    public function setAboutSozai($aboutSozai)
    {
        $this->aboutSozai = $aboutSozai;

        return $this;
    }

    /**
     * Get aboutSozai
     *
     * @return string 
     */
    public function getAboutSozai()
    {
        return $this->aboutSozai;
    }

    /**
     * Set shiyouChui
     *
     * @param string $shiyouChui
     * @return TbMainproducts
     */
    public function setShiyouChui($shiyouChui)
    {
        $this->shiyouChui = $shiyouChui;

        return $this;
    }

    /**
     * Get shiyouChui
     *
     * @return string 
     */
    public function getShiyouChui()
    {
        return $this->shiyouChui;
    }

    /**
     * Set syohinCommentPC
     *
     * @param string $syohinCommentPC
     * @return TbMainproducts
     */
    public function setSyohinCommentPC($syohinCommentPC)
    {
        $this->syohinCommentPC = $syohinCommentPC;

        return $this;
    }

    /**
     * Get syohinCommentPC
     *
     * @return string 
     */
    public function getSyohinCommentPC()
    {
        return $this->syohinCommentPC;
    }


    /**
     * Set soZaikoSu
     *
     * @param integer $soZaikoSu
     * @return TbMainproducts
     */
    public function setSoZaikoSu($soZaikoSu)
    {
        $this->soZaikoSu = $soZaikoSu;

        return $this;
    }

    /**
     * Get soZaikoSu
     *
     * @return integer 
     */
    public function getSoZaikoSu()
    {
        return $this->soZaikoSu;
    }

    /**
     * Set picfolderP1
     *
     * @param string $picfolderP1
     * @return TbMainproducts
     */
    public function setPicfolderP1($picfolderP1)
    {
        $this->picfolderP1 = $picfolderP1;

        return $this;
    }

    /**
     * Get picfolderP1
     *
     * @return string 
     */
    public function getPicfolderP1()
    {
        return $this->picfolderP1;
    }

    /**
     * Set picfolderP2
     *
     * @param string $picfolderP2
     * @return TbMainproducts
     */
    public function setPicfolderP2($picfolderP2)
    {
        $this->picfolderP2 = $picfolderP2;

        return $this;
    }

    /**
     * Get picfolderP2
     *
     * @return string 
     */
    public function getPicfolderP2()
    {
        return $this->picfolderP2;
    }

    /**
     * Set picfolderP3
     *
     * @param string $picfolderP3
     * @return TbMainproducts
     */
    public function setPicfolderP3($picfolderP3)
    {
        $this->picfolderP3 = $picfolderP3;

        return $this;
    }

    /**
     * Get picfolderP3
     *
     * @return string 
     */
    public function getPicfolderP3()
    {
        return $this->picfolderP3;
    }

    /**
     * Set picfolderP4
     *
     * @param string $picfolderP4
     * @return TbMainproducts
     */
    public function setPicfolderP4($picfolderP4)
    {
        $this->picfolderP4 = $picfolderP4;

        return $this;
    }

    /**
     * Get picfolderP4
     *
     * @return string 
     */
    public function getPicfolderP4()
    {
        return $this->picfolderP4;
    }

    /**
     * Set picfolderP5
     *
     * @param string $picfolderP5
     * @return TbMainproducts
     */
    public function setPicfolderP5($picfolderP5)
    {
        $this->picfolderP5 = $picfolderP5;

        return $this;
    }

    /**
     * Get picfolderP5
     *
     * @return string 
     */
    public function getPicfolderP5()
    {
        return $this->picfolderP5;
    }

    /**
     * Set picfolderP6
     *
     * @param string $picfolderP6
     * @return TbMainproducts
     */
    public function setPicfolderP6($picfolderP6)
    {
        $this->picfolderP6 = $picfolderP6;

        return $this;
    }

    /**
     * Get picfolderP6
     *
     * @return string 
     */
    public function getPicfolderP6()
    {
        return $this->picfolderP6;
    }

    /**
     * Set picfolderP7
     *
     * @param string $picfolderP7
     * @return TbMainproducts
     */
    public function setPicfolderP7($picfolderP7)
    {
        $this->picfolderP7 = $picfolderP7;

        return $this;
    }

    /**
     * Get picfolderP7
     *
     * @return string 
     */
    public function getPicfolderP7()
    {
        return $this->picfolderP7;
    }

    /**
     * Set picfolderP8
     *
     * @param string $picfolderP8
     * @return TbMainproducts
     */
    public function setPicfolderP8($picfolderP8)
    {
        $this->picfolderP8 = $picfolderP8;

        return $this;
    }

    /**
     * Get picfolderP8
     *
     * @return string 
     */
    public function getPicfolderP8()
    {
        return $this->picfolderP8;
    }

    /**
     * Set picfolderP9
     *
     * @param string $picfolderP9
     * @return TbMainproducts
     */
    public function setPicfolderP9($picfolderP9)
    {
        $this->picfolderP9 = $picfolderP9;

        return $this;
    }

    /**
     * Get picfolderP9
     *
     * @return string 
     */
    public function getPicfolderP9()
    {
        return $this->picfolderP9;
    }

    /**
     * Set picnameP1
     *
     * @param string $picnameP1
     * @return TbMainproducts
     */
    public function setPicnameP1($picnameP1)
    {
        $this->picnameP1 = $picnameP1;

        return $this;
    }

    /**
     * Get picnameP1
     *
     * @return string 
     */
    public function getPicnameP1()
    {
        return $this->picnameP1;
    }

    /**
     * Set picnameP2
     *
     * @param string $picnameP2
     * @return TbMainproducts
     */
    public function setPicnameP2($picnameP2)
    {
        $this->picnameP2 = $picnameP2;

        return $this;
    }

    /**
     * Get picnameP2
     *
     * @return string 
     */
    public function getPicnameP2()
    {
        return $this->picnameP2;
    }

    /**
     * Set picnameP3
     *
     * @param string $picnameP3
     * @return TbMainproducts
     */
    public function setPicnameP3($picnameP3)
    {
        $this->picnameP3 = $picnameP3;

        return $this;
    }

    /**
     * Get picnameP3
     *
     * @return string 
     */
    public function getPicnameP3()
    {
        return $this->picnameP3;
    }

    /**
     * Set picnameP4
     *
     * @param string $picnameP4
     * @return TbMainproducts
     */
    public function setPicnameP4($picnameP4)
    {
        $this->picnameP4 = $picnameP4;

        return $this;
    }

    /**
     * Get picnameP4
     *
     * @return string 
     */
    public function getPicnameP4()
    {
        return $this->picnameP4;
    }

    /**
     * Set picnameP5
     *
     * @param string $picnameP5
     * @return TbMainproducts
     */
    public function setPicnameP5($picnameP5)
    {
        $this->picnameP5 = $picnameP5;

        return $this;
    }

    /**
     * Get picnameP5
     *
     * @return string 
     */
    public function getPicnameP5()
    {
        return $this->picnameP5;
    }

    /**
     * Set picnameP6
     *
     * @param string $picnameP6
     * @return TbMainproducts
     */
    public function setPicnameP6($picnameP6)
    {
        $this->picnameP6 = $picnameP6;

        return $this;
    }

    /**
     * Get picnameP6
     *
     * @return string 
     */
    public function getPicnameP6()
    {
        return $this->picnameP6;
    }

    /**
     * Set picnameP7
     *
     * @param string $picnameP7
     * @return TbMainproducts
     */
    public function setPicnameP7($picnameP7)
    {
        $this->picnameP7 = $picnameP7;

        return $this;
    }

    /**
     * Get picnameP7
     *
     * @return string 
     */
    public function getPicnameP7()
    {
        return $this->picnameP7;
    }

    /**
     * Set picnameP8
     *
     * @param string $picnameP8
     * @return TbMainproducts
     */
    public function setPicnameP8($picnameP8)
    {
        $this->picnameP8 = $picnameP8;

        return $this;
    }

    /**
     * Get picnameP8
     *
     * @return string 
     */
    public function getPicnameP8()
    {
        return $this->picnameP8;
    }

    /**
     * Set picnameP9
     *
     * @param string $picnameP9
     * @return TbMainproducts
     */
    public function setPicnameP9($picnameP9)
    {
        $this->picnameP9 = $picnameP9;

        return $this;
    }

    /**
     * Get picnameP9
     *
     * @return string 
     */
    public function getPicnameP9()
    {
        return $this->picnameP9;
    }

    /**
     * Set NEDirectoryID
     *
     * @param string $nEDirectoryID
     * @return TbMainproducts
     */
    public function setNEDirectoryID($nEDirectoryID)
    {
        $this->NEDirectoryID = $nEDirectoryID;

        return $this;
    }

    /**
     * Get NEDirectoryID
     *
     * @return string 
     */
    public function getNEDirectoryID()
    {
        return $this->NEDirectoryID;
    }

    /**
     * Set YahooDirectoryID
     *
     * @param string $yahooDirectoryID
     * @return TbMainproducts
     */
    public function setYahooDirectoryID($yahooDirectoryID)
    {
        $this->YahooDirectoryID = $yahooDirectoryID;

        return $this;
    }

    /**
     * Get YahooDirectoryID
     *
     * @return string 
     */
    public function getYahooDirectoryID()
    {
        return $this->YahooDirectoryID;
    }
}
