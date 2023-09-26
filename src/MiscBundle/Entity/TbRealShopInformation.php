<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbRealShopInformation
 */
class TbRealShopInformation
{
  use ArrayTrait;
  use FillTimestampTrait;

    /**
     * @var string
     */
    private $daihyo_syohin_code;

    /**
     * @var integer
     */
    private $baika_tanka = 0;

    /**
     * @var integer
     */
    private $original_price = 0;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;


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
     * Set baika_tanka
     *
     * @param integer $baikaTanka
     * @return TbRealShopInformation
     */
    public function setBaikaTanka($baikaTanka)
    {
        $this->baika_tanka = $baikaTanka;

        return $this;
    }

    /**
     * Get baika_tanka
     *
     * @return integer 
     */
    public function getBaikaTanka()
    {
        return $this->baika_tanka;
    }

    /**
     * Set original_price
     *
     * @param integer $originalPrice
     * @return TbRealShopInformation
     */
    public function setOriginalPrice($originalPrice)
    {
        $this->original_price = $originalPrice;

        return $this;
    }

    /**
     * Get original_price
     *
     * @return integer 
     */
    public function getOriginalPrice()
    {
        return $this->original_price;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbRealShopInformation
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
     * @return TbRealShopInformation
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
     * Set daihyo_syohin_code
     *
     * @param string $daihyoSyohinCode
     * @return TbRealShopInformation
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyo_syohin_code = $daihyoSyohinCode;

        return $this;
    }
    /**
     * @var string
     */
    private $label_type = 'tag';


    /**
     * Set label_type
     *
     * @param string $labelType
     * @return TbRealShopInformation
     */
    public function setLabelType($labelType)
    {
        $this->label_type = $labelType;

        return $this;
    }

    /**
     * Get label_type
     *
     * @return string 
     */
    public function getLabelType()
    {
        return $this->label_type;
    }
}
