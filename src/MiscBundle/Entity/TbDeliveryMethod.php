<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Entity\TbShippingdivision;

/**
 * TbDeliveryMethod
 */
class TbDeliveryMethod
{

    /** 送料グループ設定から配送方法へのマッピング：これを元に変換を行う */
    const SHIPPING_GROUP_CODE_DELIVERY_MAPPING = [
        // 宅配便
        TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN => [
          'id' =>   34,
          'name' => DbCommonUtil::DELIVERY_METHOD_YUU_PACK_RSL
        ],
        // メール便（廃止されているが、ゆうパケットとして扱う）
        TbShippingdivision::SHIPPING_GROUP_CODE_MAILBIN => [
          'id' =>   35,
          'name' => DbCommonUtil::DELIVERY_METHOD_YUU_PACKET
        ],
        // 定形郵便
        TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI => [
          'id' =>   50,
          'name' => DbCommonUtil::DELIVERY_METHOD_TEIKEI
        ],
        // 定形外郵便
        TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI => [
          'id' =>   41,
          'name' => DbCommonUtil::DELIVERY_METHOD_TEIKEIGAI
        ],
        // ゆうパケット
        TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET => [
          'id' =>   35,
          'name' => DbCommonUtil::DELIVERY_METHOD_YUU_PACKET
        ],
        // ねこポス（もう廃止されているはず）
        TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU => [
          'id' =>   28,
          'name' => DbCommonUtil::DELIVERY_METHOD_NEKOPOSU
        ],
        // クリックポスト
        TbShippingdivision::SHIPPING_GROUP_CODE_CLICKPOST => [
          'id' =>   36,
          'name' => DbCommonUtil::DELIVERY_METHOD_CLICKPOST
        ],
        // 個別送料
        TbShippingdivision::SHIPPING_GROUP_CODE_KOBETSU => [
          'id' =>   34,
          'name' => DbCommonUtil::DELIVERY_METHOD_YUU_PACK_RSL
        ],
    ];

    /**
     * @var integer
     */
    private $deliveryId = '0';

    /**
     * @var string
     */
    private $deliveryName;

    /**
     * @var integer
     */
    private $deliveryCost = '0';

    /**
     * @var boolean
     */
    private $inquiryNumberNeedFlg;

    /**
     * @var integer
     */
    private $shippingGroupCode;


    /**
     * Get deliveryId
     *
     * @return integer
     */
    public function getDeliveryId()
    {
        return $this->deliveryId;
    }

    /**
     * Set deliveryName
     *
     * @param string $deliveryName
     *
     * @return TbDeliveryMethod
     */
    public function setDeliveryName($deliveryName)
    {
        $this->deliveryName = $deliveryName;

        return $this;
    }

    /**
     * Get deliveryName
     *
     * @return string
     */
    public function getDeliveryName()
    {
        return $this->deliveryName;
    }

    /**
     * Set deliveryCost
     *
     * @param integer $deliveryCost
     *
     * @return TbDeliveryMethod
     */
    public function setDeliveryCost($deliveryCost)
    {
        $this->deliveryCost = $deliveryCost;

        return $this;
    }

    /**
     * Get deliveryCost
     *
     * @return integer
     */
    public function getDeliveryCost()
    {
        return $this->deliveryCost;
    }

    /**
     * Set inquiryNumberNeedFlg
     *
     * @param boolean $inquiryNumberNeedFlg
     *
     * @return TbDeliveryMethod
     */
    public function setInquiryNumberNeedFlg($inquiryNumberNeedFlg)
    {
        $this->inquiryNumberNeedFlg = $inquiryNumberNeedFlg;

        return $this;
    }

    /**
     * Get inquiryNumberNeedFlg
     *
     * @return boolean
     */
    public function getInquiryNumberNeedFlg()
    {
        return $this->inquiryNumberNeedFlg;
    }

    /**
     * Set shippingGroupCode
     *
     * @param integer $shippingGroupCode
     *
     * @return TbDeliveryMethod
     */
    public function setShippingGroupCodet($shippingGroupCode)
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
}
