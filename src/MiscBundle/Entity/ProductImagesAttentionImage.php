<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductImagesAttentionImage
 */
class ProductImagesAttentionImage
{
    /**
     * @var string
     */
    private $md5hash;

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     */
    private $imagePath;

    /**
     * @var integer
     */
    private $useProductNumOnsale;

    /**
     * @var integer
     */
    private $useProductNumAll;

    /**
     * @var boolean
     */
    private $attentionFlg;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var integer
     */
    private $updateAccountId;

    /**
     * @var \DateTime
     */
    private $updated;


    /**
     * Get md5hash
     *
     * @return string
     */
    public function getMd5hash()
    {
        return $this->md5hash;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return ProductImagesAttentionImage
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
     * Set imagePath
     *
     * @param string $imagePath
     * @return ProductImagesAttentionImage
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * Get imagePath
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Set useProductNumOnsale
     *
     * @param integer $useProductNumOnsale
     * @return ProductImagesAttentionImage
     */
    public function setUseProductNumOnsale($useProductNumOnsale)
    {
        $this->useProductNumOnsale = $useProductNumOnsale;

        return $this;
    }

    /**
     * Get useProductNumOnsale
     *
     * @return integer
     */
    public function getUseProductNumOnsale()
    {
        return $this->useProductNumOnsale;
    }

    /**
     * Set useProductNumAll
     *
     * @param integer $useProductNumAll
     * @return ProductImagesAttentionImage
     */
    public function setUseProductNumAll($useProductNumAll)
    {
        $this->useProductNumAll = $useProductNumAll;

        return $this;
    }

    /**
     * Get useProductNumAll
     *
     * @return integer
     */
    public function getUseProductNumAll()
    {
        return $this->useProductNumAll;
    }

    /**
     * Set isAttentionFlg
     *
     * @param boolean $attentionFlg
     * @return ProductImagesAttentionImage
     */
    public function setAttentionFlg($attentionFlg)
    {
        $this->attentionFlg = $attentionFlg;

        return $this;
    }

    /**
     * Get attentionFlg
     *
     * @return boolean
     */
    public function getAttentionFlg()
    {
        return $this->attentionFlg;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ProductImagesAttentionImage
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
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return ProductImagesAttentionImage
     */
    public function setUpdateAccountId($updateAccountId)
    {
        $this->updateAccountId = $updateAccountId;

        return $this;
    }

    /**
     * Get updateAccountId
     *
     * @return integer
     */
    public function getUpdateAccountId()
    {
        return $this->updateAccountId;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return ProductImagesAttentionImage
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
}
