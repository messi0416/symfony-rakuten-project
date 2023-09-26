<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Util\DbCommonUtil;

/**
 * TbShippingdivision
 */
class TbShippingdivision
{

    use ArrayTrait;

    /** 送料設定ID: 宅配便(60) */
    const SHIPPING_DIVISION_ID_TAKUHAIBIN_60 = 11;
    /** 送料設定ID: 宅配便(80) */
    const SHIPPING_DIVISION_ID_TAKUHAIBIN_80 = 12;
    /** 送料設定ID: 宅配便(100) */
    const SHIPPING_DIVISION_ID_TAKUHAIBIN_100 = 13;
    /** 送料設定ID: 宅配便(140) */
    const SHIPPING_DIVISION_ID_TAKUHAIBIN_140 = 14;
    /** 送料設定ID: 宅配便(160) */
    const SHIPPING_DIVISION_ID_TAKUHAIBIN_160 = 15;

    /** 送料グループ種別：宅配便 */
    const SHIPPING_GROUP_CODE_TAKUHAIBIN = "1";
    /** 送料グループ種別：メール便 */
    const SHIPPING_GROUP_CODE_MAILBIN = "2";
    /** 送料グループ種別：定形外郵便 */
    const SHIPPING_GROUP_CODE_TEIKEIGAI = "3";
    /** 送料グループ種別：ゆうパケット */
    const SHIPPING_GROUP_CODE_YUU_PACKET = "4";
    /** 送料グループ種別：ねこポス */
    const SHIPPING_GROUP_CODE_NEKOPOSU = "5";
    /** 送料グループ種別：クリックポスト */
    const SHIPPING_GROUP_CODE_CLICKPOST = "6";
    /** 送料グループ種別：個別送料 */
    const SHIPPING_GROUP_CODE_KOBETSU = "7";
    /** 送料グループ種別：定形郵便 */
    const SHIPPING_GROUP_CODE_TEIKEI = "8";

    /** 送料設定ID：定形外郵便(規格内) */
    const SHIPPINGDIVISION_ID_TEIKEIGAI_STANDARD = 8;

    /**
     * 送料グループ種別のリストを、コード => 名称 の形式の連想配列で返却する。
     */
    public static function getShippingGroupList() {
        return array(
            TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN => "宅配便",
            TbShippingdivision::SHIPPING_GROUP_CODE_MAILBIN => "メール便",
            TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI => "定形郵便",
            TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI => "定形外郵便",
            TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET => "ゆうパケット",
            TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU => "ねこポス",
            TbShippingdivision::SHIPPING_GROUP_CODE_CLICKPOST => "クリックポスト",
            TbShippingdivision::SHIPPING_GROUP_CODE_KOBETSU => "個別送料",
        );
    }

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $price;

    /**
     * @var integer
     */
    private $maxThreeEdgeSum;

    /**
     * @var string
     */
    private $maxThreeEdgeIndividual;

    /**
     * @var integer
     */
    private $maxWeight;

    /**
     * @var integer
     */
    private $shippingGroupCode;

    /**
     * @var string
     */
    private $note;

    /**
     * @var boolean
     */
    private $terminateFlg;

    /**
     * @var integer
     */
    private $updSymfonyUsersId;

    /**
     * @var \DateTime
     */
    private $updDt;

    /**
     * @var TbMainproducts
     */ 
    private $product;

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
     * Set name
     *
     * @param string $name
     * @return TbShippingdivision
     */
    public function setName($name)
    {
      $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set price
     *
     * @param integer $price
     * @return TbShippingdivision
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set maxThreeEdgeSum
     *
     * @param integer $maxThreeEdgeSum
     * @return TbShippingdivision
     */
    public function setMaxThreeEdgeSum($maxThreeEdgeSum)
    {
        $this->maxThreeEdgeSum = $maxThreeEdgeSum;

        return $this;
    }

    /**
     * Get maxThreeEdgeSum
     *
     * @return integer
     */
    public function getMaxThreeEdgeSum()
    {
        return $this->maxThreeEdgeSum;
    }

    /**
     * Set maxThreeEdgeIndividual
     *
     * @param string $maxThreeEdgeIndividual
     * @return TbShippingdivision
     */
    public function setMaxThreeEdgeIndividual($maxThreeEdgeIndividual)
    {
        $this->maxThreeEdgeIndividual = $maxThreeEdgeIndividual;

        return $this;
    }

    /**
     * Get maxThreeEdgeIndividual
     *
     * @return string
     */
    public function getMaxThreeEdgeIndividual()
    {
        return $this->maxThreeEdgeIndividual;
    }

    /**
     * Set maxWeight
     *
     * @param integer $maxWeight
     * @return TbShippingdivision
     */
    public function setMaxWeight($maxWeight)
    {
        $this->maxWeight = $maxWeight;

        return $this;
    }

    /**
     * Get maxWeight
     *
     * @return integer
     */
    public function getMaxWeight()
    {
        return $this->maxWeight;
    }

    /**
     * Set shippingGroupCode
     *
     * @param integer $shippingGroupCode
     * @return TbShippingdivision
     */
    public function setShippingGroupCode($shippingGroupCode)
    {
        $this->shippingGroupCode = $shippingGroupCode;

        return $this;
    }

    /**
     * Get shippingGroupCode
     *
     * @return integer
     */
    public function getShippingGroupCode()
    {
        return $this->shippingGroupCode;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return TbShippingdivision
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set terminateFlg
     *
     * @param boolean $terminateFlg
     * @return TbShippingdivision
     */
    public function setTerminateFlg($terminateFlg)
    {
        $this->terminateFlg = $terminateFlg;

        return $this;
    }

    /**
     * Get terminateFlg
     *
     * @return boolean
     */
    public function getTerminateFlg()
    {
        return $this->terminateFlg;
    }

    /**
     * Set updSymfonyUsersId
     *
     * @param integer $updSymfonyUsersId
     * @return TbShippingdivision
     */
    public function setUpdSymfonyUsersId($updSymfonyUsersId)
    {
        $this->updSymfonyUsersId = $updSymfonyUsersId;

        return $this;
    }

    /**
     * Get updSymfonyUsersId
     *
     * @return integer
     */
    public function getUpdSymfonyUsersId()
    {
        return $this->updSymfonyUsersId;
    }

    /**
     * Set updDt
     *
     * @param \DateTime $updDt
     * @return TbShippingdivision
     */
    public function setUpdDt($updDt)
    {
        $this->updDt = $updDt;

        return $this;
    }

    /**
     * Get updDt
     *
     * @return \DateTime
     */
    public function getUpdDt()
    {
        return $this->updDt;
    }
}
