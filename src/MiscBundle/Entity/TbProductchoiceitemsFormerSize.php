<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductchoiceitemsFormerSize
 */
class TbProductchoiceitemsFormerSize
{
    /**
     * @var string
     */
    private $neSyohinSyohinCode;

    /**
     * @var integer
     */
    private $weight;

    /**
     * @var integer
     */
    private $depth;

    /**
     * @var integer
     */
    private $width;

    /**
     * @var integer
     */
    private $height;

    /**
     * @var boolean
     */
    private $changedFlg;


    /**
     * Get neSyohinSyohinCode
     *
     * @return string 
     */
    public function getNeSyohinSyohinCode()
    {
        return $this->neSyohinSyohinCode;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return TbProductchoiceitemsFormerSize
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return TbProductchoiceitemsFormerSize
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth
     *
     * @return integer 
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return TbProductchoiceitemsFormerSize
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return TbProductchoiceitemsFormerSize
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set changedFlg
     *
     * @param boolean $changedFlg
     * @return TbProductchoiceitemsFormerSize
     */
    public function setChangedFlg($changedFlg)
    {
        $this->changedFlg = $changedFlg;

        return $this;
    }

    /**
     * Get changedFlg
     *
     * @return boolean 
     */
    public function getChangedFlg()
    {
        return $this->changedFlg;
    }
}
