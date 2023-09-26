<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductchoiceitemsColor
 */
class TbProductchoiceitemsColor
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $ne_syohin_syohin_code;

    /**
     * @var string
     */
    private $yahoo_spec_value_id;

    /**
     * @var string
     */
    private $color_name;

    /**
     * @var integer
     */
    private $configured_flg;


    /**
     * Set id
     *
     * @param integer $id
     * @return TbProductchoiceitemsColor
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set ne_syohin_syohin_code
     *
     * @param string $neSyohinSyohinCode
     * @return TbProductchoiceitemsColor
     */
    public function setNeSyohinSyohinCode($neSyohinSyohinCode)
    {
        $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

        return $this;
    }

    /**
     * Get ne_syohin_syohin_code
     *
     * @return string 
     */
    public function getNeSyohinSyohinCode()
    {
        return $this->ne_syohin_syohin_code;
    }

    /**
     * Set yahoo_spec_value_id
     *
     * @param string $yahooSpecValueId
     * @return TbProductchoiceitemsColor
     */
    public function setYahooSpecValueId($yahooSpecValueId)
    {
        $this->yahoo_spec_value_id = $yahooSpecValueId;

        return $this;
    }

    /**
     * Get yahoo_spec_value_id
     *
     * @return string 
     */
    public function getYahooSpecValueId()
    {
        return $this->yahoo_spec_value_id;
    }

    /**
     * Set color_name
     *
     * @param string $colorName
     * @return TbProductchoiceitemsColor
     */
    public function setColorName($colorName)
    {
        $this->color_name = $colorName;

        return $this;
    }

    /**
     * Get color_name
     *
     * @return string 
     */
    public function getColorName()
    {
        return $this->color_name;
    }

    /**
     * Set configured_flg
     *
     * @param integer $configuredFlg
     * @return TbProductchoiceitemsColor
     */
    public function setConfiguredFlg($configuredFlg)
    {
        $this->configured_flg = $configuredFlg;

        return $this;
    }

    /**
     * Get configured_flg
     *
     * @return integer 
     */
    public function getConfiguredFlg()
    {
        return $this->configured_flg;
    }
}
