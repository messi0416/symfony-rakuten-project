<?php

namespace MiscBundle\Entity;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbShoppingMall
 */
class TbShoppingMall
{
  use ArrayTrait;
  use FillTimestampTrait;

  // NEモールIDリスト
  /** NEモールID: 楽天 */
  const NE_MALL_ID_RAKUTEN = '1';
  /** NEモールID: WOWMA */
  const NE_MALL_ID_WOWMA = '2';
  /** NEモールID: AMAZON */
  const NE_MALL_ID_AMAZON = '9';
  /** NEモールID: Yahoo */
  const NE_MALL_ID_YAHOO = '12';
  /** NEモールID: Qoo10 */
  const NE_MALL_ID_QTEN = '13';
  /** NEモールID: ポンパレモール */
  const NE_MALL_ID_PPM = '16';
  /** NEモールID: SHOPLIST */
  const NE_MALL_ID_SHOPLIST = '18';
  /** NEモールID: Yahoo（おとりよせ） */
  const NE_MALL_ID_OTORIYOSE = '20';
  /** NEモールID: Amazon(USA) */
  const NE_MALL_ID_AMAZON_COM = '99';
  /** NEモールID: Yahoo（Kawa-e-mon） */
  const NE_MALL_ID_KAWA_E_MON = '14';
  /** NEモールID: 楽天シャンゼ */
  const NE_MALL_ID_RAKUTEN_SHANZE = '24';
  /** NEモールID: 楽天ロジ */
  const NE_MALL_ID_RAKUTEN_LOGI = '25';
  /** NEモールID: 楽天ドルチッシモ */
  const NE_MALL_ID_RAKUTEN_DOLTI = '27';
  /** NEモールID: 楽天motto-motto */
  const NE_MALL_ID_RAKUTEN_MOTTO = '31';
  /** NEモールID: 楽天laforest */
  const NE_MALL_ID_RAKUTEN_LAFOREST = '32';
  /** NEモールID: 楽天激安プラネット */
  const NE_MALL_ID_RAKUTEN_GEKIPLA = '35';
  
  /** NEモール名：　SHOPLIST */
  const NE_MALL_NAME_SHOPLIST = 'SHOPLIST PlusNao';
  
  // バッチパラメータ用店舗名は、モール間で重複させない事。たとえば Yahoo plusnaoと楽天plusnaoは、それぞれ plusnaoとrakutenとしている。
  
  /** バッチパラメータ用店舗名: Yahoo plusnao */
  const BATCH_SHOP_CODE_YAHOO_PLUSNAO = 'plusnao';
  /** バッチパラメータ用店舗名: Yahoo kawa-e-mon */
  const BATCH_SHOP_CODE_YAHOO_KAWAEMON = 'kawaemon';
  /** バッチパラメータ用店舗名: Yahoo おとりよせ */
  const BATCH_SHOP_CODE_YAHOO_OTORIYOSE = 'otoriyose';
  
  /** モール内店舗コード: Yahoo plusnao */
  const MALL_SHOP_CODE_YAHOO_PLUSNAO = 'plusnao';
  /** モール内店舗コード: Yahoo kawa-e-mon */
  const MALL_SHOP_CODE_YAHOO_KAWAEMON = 'kawa-e-mon';
  /** モール内店舗コード: Yahoo おとりよせ */
  const MALL_SHOP_CODE_YAHOO_OTORIYOSE = 'mignonlindo';
  
  
  /**
   * Yahoo店舗の配列を取得する
   * @return string[]
   */
  public static function getYahooShopList() {
    return [self::BATCH_SHOP_CODE_YAHOO_PLUSNAO, self::BATCH_SHOP_CODE_YAHOO_KAWAEMON, self::BATCH_SHOP_CODE_YAHOO_OTORIYOSE];
  }
  
  /**
   * バッチ用店舗名を元に、モール内の店舗コードを取得して返却する。
   * 店舗コードが未定義の場合はnullを返却する。
   * @param string $batchShopName バッチパラメータ用店舗名
   */
  public static function getMallShopCode($batchShopCode) {
    if ($batchShopCode === self::BATCH_SHOP_CODE_YAHOO_PLUSNAO) {
      return self::MALL_SHOP_CODE_YAHOO_PLUSNAO;
    } else if ($batchShopCode === self::BATCH_SHOP_CODE_YAHOO_KAWAEMON) {
      return self::MALL_SHOP_CODE_YAHOO_KAWAEMON;
    } else if (self::BATCH_SHOP_CODE_YAHOO_OTORIYOSE) {
      return self::MALL_SHOP_CODE_YAHOO_OTORIYOSE;
    } else {
      return null;
    }
  }
  
  /**
   * モール判定
   * @param $mallId
   * @return bool
   */
  public function isMallId($mallId)
  {
    if (!preg_match('/^\d+$/', $mallId)) {
      throw new \InvalidArgumentException('not integer. may be mall code was given.');
    }

    return $this->getMallId() === intval($mallId);
  }

  /**
   * club-forestか？
   */
  public function isClubForest()
  {
    return $this->getNeMallId() === DbCommonUtil::MALL_ID_EC02;
  }

  // -------------------------------------------------------------
  // properties
  // -------------------------------------------------------------

  /**
   * @var integer
   */
  private $mallId;

  /**
   * @var integer
   */
  private $neMallId;

  /**
   * @var string
   */
  private $mallName;

  /**
   * @var string
   */
  private $mallNameShort1;

  /**
   * @var string
   */
  private $mallNameShort2;

  /**
   * @var string
   */
  private $neMallName;

  /**
   * @var string
   */
  private $mallUrl;

  /**
   * @var integer
   */
  private $additionalCostRatio = '0';

  /**
   * @var float
   */
  private $systemUsageCostRatio = '0';

  /**
   * @var boolean
   */
  private $obeyPostageSetting = '-1';

  /**
   * @var string
   */
  private $mallDesc;

  /**
   * @var integer
   */
  private $mallSort;

  /**
   * Get mallId
   *
   * @return integer
   */
  public function getMallId()
  {
      return $this->mallId;
  }

  /**
   * Set neMallId
   *
   * @param integer $neMallId
   *
   * @return TbShoppingMall
   */
  public function setNeMallId($neMallId)
  {
      $this->neMallId = $neMallId;

      return $this;
  }

  /**
   * Get neMallId
   *
   * @return integer
   */
  public function getNeMallId()
  {
      return $this->neMallId;
  }

  /**
   * Set mallName
   *
   * @param string $mallName
   *
   * @return TbShoppingMall
   */
  public function setMallName($mallName)
  {
      $this->mallName = $mallName;

      return $this;
  }

  /**
   * Get mallName
   *
   * @return string
   */
  public function getMallName()
  {
      return $this->mallName;
  }

  /**
   * Set mallNameShort1
   *
   * @param string $mallNameShort1
   *
   * @return TbShoppingMall
   */
  public function setMallNameShort1($mallNameShort1)
  {
      $this->mallNameShort1 = $mallNameShort1;

      return $this;
  }

  /**
   * Get mallNameShort1
   *
   * @return string
   */
  public function getMallNameShort1()
  {
      return $this->mallNameShort1;
  }

  /**
   * Set mallNameShort2
   *
   * @param string $mallNameShort2
   *
   * @return TbShoppingMall
   */
  public function setMallNameShort2($mallNameShort2)
  {
      $this->mallNameShort2 = $mallNameShort2;

      return $this;
  }

  /**
   * Get mallNameShort2
   *
   * @return string
   */
  public function getMallNameShort2()
  {
      return $this->mallNameShort2;
  }

  /**
   * Set neMallName
   *
   * @param string $neMallName
   *
   * @return TbShoppingMall
   */
  public function setNeMallName($neMallName)
  {
      $this->neMallName = $neMallName;

      return $this;
  }

  /**
   * Get neMallName
   *
   * @return string
   */
  public function getNeMallName()
  {
      return $this->neMallName;
  }

  /**
   * Set mallUrl
   *
   * @param string $mallUrl
   *
   * @return TbShoppingMall
   */
  public function setMallUrl($mallUrl)
  {
      $this->mallUrl = $mallUrl;

      return $this;
  }

  /**
   * Get mallUrl
   *
   * @return string
   */
  public function getMallUrl()
  {
      return $this->mallUrl;
  }

  /**
   * Set additionalCostRatio
   *
   * @param integer $additionalCostRatio
   *
   * @return TbShoppingMall
   */
  public function setAdditionalCostRatio($additionalCostRatio)
  {
      $this->additionalCostRatio = $additionalCostRatio;

      return $this;
  }

  /**
   * Get additionalCostRatio
   *
   * @return integer
   */
  public function getAdditionalCostRatio()
  {
      return $this->additionalCostRatio;
  }

  /**
   * Set systemUsageCostRatio
   *
   * @param float $systemUsageCostRatio
   *
   * @return TbShoppingMall
   */
  public function setSystemUsageCostRatio($systemUsageCostRatio)
  {
      $this->systemUsageCostRatio = $systemUsageCostRatio;

      return $this;
  }

  /**
   * Get systemUsageCostRatio
   *
   * @return float
   */
  public function getSystemUsageCostRatio()
  {
      return $this->systemUsageCostRatio;
  }

  /**
   * Set obeyPostageSetting
   *
   * @param boolean $obeyPostageSetting
   *
   * @return TbShoppingMall
   */
  public function setObeyPostageSetting($obeyPostageSetting)
  {
      $this->obeyPostageSetting = $obeyPostageSetting;

      return $this;
  }

  /**
   * Get obeyPostageSetting
   *
   * @return boolean
   */
  public function getObeyPostageSetting()
  {
      return $this->obeyPostageSetting;
  }

  /**
   * Set mallDesc
   *
   * @param string $mallDesc
   *
   * @return TbShoppingMall
   */
  public function setMallDesc($mallDesc)
  {
      $this->mallDesc = $mallDesc;

      return $this;
  }

  /**
   * Get mallDesc
   *
   * @return string
   */
  public function getMallDesc()
  {
      return $this->mallDesc;
  }

  /**
   * Set mallSort
   *
   * @param integer $mallSort
   *
   * @return TbShoppingMall
   */
  public function setMallSort($mallSort)
  {
      $this->mallSort = $mallSort;

      return $this;
  }

  /**
   * Get mallSort
   *
   * @return integer
   */
  public function getMallSort()
  {
      return $this->mallSort;
  }
  
  /**
   * @var integer
   */
  private $neOrderUploadPatternId;

  /**
   * Set neOrderUploadPatternId
   *
   * @param integer $neOrderUploadPatternId
   * @return TbShoppingMall
   */
  public function setNeOrderUploadPatternId($neOrderUploadPatternId)
  {
    $this->neOrderUploadPatternId = $neOrderUploadPatternId;

    return $this;
  }

  /**
   * Get neOrderUploadPatternId
   *
   * @return integer 
   */
  public function getNeOrderUploadPatternId()
  {
    return $this->neOrderUploadPatternId;
  }
    /**
     * @var string
     */
    private $shippingVoucherTitle = '';

    /**
     * @var string
     */
    private $shippingVoucherText;

    /**
     * @var string
     */
    private $shippingVoucherSub01Title = '';

    /**
     * @var string
     */
    private $shippingVoucherSub01Text;

    /**
     * @var string
     */
    private $shippingVoucherSub02Title = '';

    /**
     * @var string
     */
    private $shippingVoucherSub02Text;

    /**
     * @var string
     */
    private $shippingVoucherSub03Title = '';

    /**
     * @var string
     */
    private $shippingVoucherSub03Text;

    /**
     * @var string
     */
    private $shippingVoucherShopInfo;

    /**
     * @var integer
     */
    private $shippingVoucherShowBuyerAddress = 0;

    /**
     * @var integer
     */
    private $shippingVoucherShowShippingAddress = 0;


    /**
     * Set shippingVoucherTitle
     *
     * @param string $shippingVoucherTitle
     * @return TbShoppingMall
     */
    public function setShippingVoucherTitle($shippingVoucherTitle)
    {
        $this->shippingVoucherTitle = $shippingVoucherTitle;

        return $this;
    }

    /**
     * Get shippingVoucherTitle
     *
     * @return string 
     */
    public function getShippingVoucherTitle()
    {
        return $this->shippingVoucherTitle;
    }

    /**
     * Set shippingVoucherText
     *
     * @param string $shippingVoucherText
     * @return TbShoppingMall
     */
    public function setShippingVoucherText($shippingVoucherText)
    {
        $this->shippingVoucherText = $shippingVoucherText;

        return $this;
    }

    /**
     * Get shippingVoucherText
     *
     * @return string 
     */
    public function getShippingVoucherText()
    {
        return $this->shippingVoucherText;
    }

    /**
     * Set shippingVoucherSub01Title
     *
     * @param string $shippingVoucherSub01Title
     * @return TbShoppingMall
     */
    public function setShippingVoucherSub01Title($shippingVoucherSub01Title)
    {
        $this->shippingVoucherSub01Title = $shippingVoucherSub01Title;

        return $this;
    }

    /**
     * Get shippingVoucherSub01Title
     *
     * @return string 
     */
    public function getShippingVoucherSub01Title()
    {
        return $this->shippingVoucherSub01Title;
    }

    /**
     * Set shippingVoucherSub01Text
     *
     * @param string $shippingVoucherSub01Text
     * @return TbShoppingMall
     */
    public function setShippingVoucherSub01Text($shippingVoucherSub01Text)
    {
        $this->shippingVoucherSub01Text = $shippingVoucherSub01Text;

        return $this;
    }

    /**
     * Get shippingVoucherSub01Text
     *
     * @return string 
     */
    public function getShippingVoucherSub01Text()
    {
        return $this->shippingVoucherSub01Text;
    }

    /**
     * Set shippingVoucherSub02Title
     *
     * @param string $shippingVoucherSub02Title
     * @return TbShoppingMall
     */
    public function setShippingVoucherSub02Title($shippingVoucherSub02Title)
    {
        $this->shippingVoucherSub02Title = $shippingVoucherSub02Title;

        return $this;
    }

    /**
     * Get shippingVoucherSub02Title
     *
     * @return string 
     */
    public function getShippingVoucherSub02Title()
    {
        return $this->shippingVoucherSub02Title;
    }

    /**
     * Set shippingVoucherSub02Text
     *
     * @param string $shippingVoucherSub02Text
     * @return TbShoppingMall
     */
    public function setShippingVoucherSub02Text($shippingVoucherSub02Text)
    {
        $this->shippingVoucherSub02Text = $shippingVoucherSub02Text;

        return $this;
    }

    /**
     * Get shippingVoucherSub02Text
     *
     * @return string 
     */
    public function getShippingVoucherSub02Text()
    {
        return $this->shippingVoucherSub02Text;
    }

    /**
     * Set shippingVoucherSub03Title
     *
     * @param string $shippingVoucherSub03Title
     * @return TbShoppingMall
     */
    public function setShippingVoucherSub03Title($shippingVoucherSub03Title)
    {
        $this->shippingVoucherSub03Title = $shippingVoucherSub03Title;

        return $this;
    }

    /**
     * Get shippingVoucherSub03Title
     *
     * @return string 
     */
    public function getShippingVoucherSub03Title()
    {
        return $this->shippingVoucherSub03Title;
    }

    /**
     * Set shippingVoucherSub03Text
     *
     * @param string $shippingVoucherSub03Text
     * @return TbShoppingMall
     */
    public function setShippingVoucherSub03Text($shippingVoucherSub03Text)
    {
        $this->shippingVoucherSub03Text = $shippingVoucherSub03Text;

        return $this;
    }

    /**
     * Get shippingVoucherSub03Text
     *
     * @return string 
     */
    public function getShippingVoucherSub03Text()
    {
        return $this->shippingVoucherSub03Text;
    }

    /**
     * Set shippingVoucherShopInfo
     *
     * @param string $shippingVoucherShopInfo
     * @return TbShoppingMall
     */
    public function setShippingVoucherShopInfo($shippingVoucherShopInfo)
    {
        $this->shippingVoucherShopInfo = $shippingVoucherShopInfo;

        return $this;
    }

    /**
     * Get shippingVoucherShopInfo
     *
     * @return string 
     */
    public function getShippingVoucherShopInfo()
    {
        return $this->shippingVoucherShopInfo;
    }

    /**
     * Set shippingVoucherShowBuyerAddress
     *
     * @param integer $shippingVoucherShowBuyerAddress
     * @return TbShoppingMall
     */
    public function setShippingVoucherShowBuyerAddress($shippingVoucherShowBuyerAddress)
    {
        $this->shippingVoucherShowBuyerAddress = $shippingVoucherShowBuyerAddress;

        return $this;
    }

    /**
     * Get shippingVoucherShowBuyerAddress
     *
     * @return integer 
     */
    public function getShippingVoucherShowBuyerAddress()
    {
        return $this->shippingVoucherShowBuyerAddress;
    }

    /**
     * Set shippingVoucherShowShippingAddress
     *
     * @param integer $shippingVoucherShowShippingAddress
     * @return TbShoppingMall
     */
    public function setShippingVoucherShowShippingAddress($shippingVoucherShowShippingAddress)
    {
        $this->shippingVoucherShowShippingAddress = $shippingVoucherShowShippingAddress;

        return $this;
    }

    /**
     * Get shippingVoucherShowShippingAddress
     *
     * @return integer 
     */
    public function getShippingVoucherShowShippingAddress()
    {
        return $this->shippingVoucherShowShippingAddress;
    }

    /**
     * @var integer
     */
    private $updateAccountId;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     *
     * @return TbShoppingMall
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
     *
     * @return TbShoppingMall
     */
    public function setUpdated(\DateTime $updated)
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
