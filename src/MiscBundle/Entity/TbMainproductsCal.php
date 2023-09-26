<?php

namespace MiscBundle\Entity;

/**
 * TbMainproductsCal
 */
class TbMainproductsCal
{
  /*
Public Enum E_送料設定
    宅配別 = 0
    宅配込 = 1
    個別送料 = 2
    メール便込 = 3
    定形外込 = 4
End Enum
  */

  // delivery code
  const DELIVERY_CODE_READY = 0; // 即納
  const DELIVERY_CODE_READY_PARTIALLY = 1; // 一部即納
  const DELIVERY_CODE_PURCHASE_ON_ORDER = 2; // 受発注のみ
  const DELIVERY_CODE_FINISHED = 3; // 販売終了
  const DELIVERY_CODE_TEMPORARY = 4; // 仮登録
  const DELIVERY_CODE_ERROR = 9; // エラー

  public static $DELIVERY_CODE_LIST = [
      self::DELIVERY_CODE_READY =>  '即納'
    , self::DELIVERY_CODE_READY_PARTIALLY => '一部即納'
    , self::DELIVERY_CODE_PURCHASE_ON_ORDER => '受発注のみ'
    , self::DELIVERY_CODE_FINISHED => '販売終了'
    , self::DELIVERY_CODE_TEMPORARY => '仮登録'
    , self::DELIVERY_CODE_ERROR => 'エラー'
  ];

  // adult_check_status
  const ADULT_CHECK_STATUS_NONE = '未審査';
  const ADULT_CHECK_STATUS_WHITE = 'ホワイト';
  const ADULT_CHECK_STATUS_GRAY = 'グレー';
  const ADULT_CHECK_STATUS_BLACK = 'ブラック';

  public static $ADULT_CHECK_STATUS_LIST = [
      self::ADULT_CHECK_STATUS_NONE  => self::ADULT_CHECK_STATUS_NONE
    , self::ADULT_CHECK_STATUS_WHITE  => self::ADULT_CHECK_STATUS_WHITE
    , self::ADULT_CHECK_STATUS_GRAY  => self::ADULT_CHECK_STATUS_GRAY
    , self::ADULT_CHECK_STATUS_BLACK  => self::ADULT_CHECK_STATUS_BLACK
  ];

  // quality_level
  const QUALITY_LEVEL_NONE = 0;
  const QUALITY_LEVEL_NG = 1;
  const QUALITY_LEVEL_OK = 5;
  const QUALITY_LEVEL_GOOD = 10;

  /** @var TbMainproducts */
  private $product;

  /**
   * @return TbMainproducts
   */
  public function getProduct()
  {
    return $this->product;
  }

  /**
   * Get deliverycode
   *
   * @return integer
   */
  public function getDeliverycodeDisplay()
  {
    return self::$DELIVERY_CODE_LIST[$this->deliverycode];
  }

  /**
   * 重厚計測チェック ON?
   * @return bool
   */
  public function isWeightCheckNeed()
  {
    return $this->getWeightCheckNeedFlg() <> 0;
  }


  // ----------------------------------------
  // field properties
  // ----------------------------------------

  /**
   * @var string
   */
  private $daihyoSyohinCode;

  /**
   * @var \DateTime
   */
  private $endofavailability;

  /**
   * @var integer
   */
  private $deliverycode = '4';

  /**
   * @var integer
   */
  private $genkaTnkAve;

  /**
   * @var integer
   */
  private $baikaTnk;

  /**
   * @var \DateTime
   */
  private $sunfactoryset;

  /**
   * @var string
   */
  private $listSomeInstantDelivery;

  /**
   * @var integer
   */
  private $priority = '0';

  /**
   * @var \DateTime
   */
  private $earliestOrderDate;

  /**
   * @var integer
   */
  private $delayDays;

  /**
   * @var integer
   */
  private $visibleFlg = '1';

  /**
   * @var integer
   */
  private $salesVolume = 0;

  /**
   * @var boolean
   */
  private $makeshopRegistrationFlug = '0';

  /**
   * @var boolean
   */
  private $rakutenRegistrationFlug = '0';

  /**
   * @var boolean
   */
  private $croozmallRegistrationFlug = '0';

  /**
   * @var boolean
   */
  private $amazonRegistrationFlug = '0';

  /**
   * @var integer
   */
  private $annualSales = '0';

  /**
   * @var \DateTime
   */
  private $rakutenRegistrationFlugDate;

  /**
   * @var integer
   */
  private $setnum;

  /**
   * @var string
   */
  private $rakutencategoryTep;

  /**
   * @var integer
   */
  private $beingNum;

  /**
   * @var boolean
   */
  private $mallPriceFlg = '0';

  /**
   * @var string
   */
  private $daihyoSyohinLabel = '';

  /**
   * @var integer
   */
  private $maxbuynum = '0';

  /**
   * @var integer
   */
  private $outlet = '0';

  /**
   * @var integer
   */
  private $bigSize = '0';

  /**
   * @var integer
   */
  private $viewrank = '0';

  /**
   * @var boolean
   */
  private $reviewrequest = '0';

  /**
   * @var string
   */
  private $reviewPointAve = '0.0';

  /**
   * @var integer
   */
  private $reviewNum = '0';

  /**
   * @var string
   */
  private $searchCode = '';

  /**
   * @var integer
   */
  private $fixedCost = '0';

  /**
   * @var integer
   */
  private $notfoundImageNoRakuten = '0';

  /**
   * @var integer
   */
  private $notfoundImageNoDena = '0';

  /**
   * @var boolean
   */
  private $startupFlg = '-1';

  /**
   * @var boolean
   */
  private $pricedownFlg = '-1';

  /**
   * @var boolean
   */
  private $redFlg = '0';

  /**
   * @var \DateTime
   */
  private $lastOrderdate;

  /**
   * @var boolean
   */
  private $wangStatus = '0';

  /**
   * @var boolean
   */
  private $orderingAvoidFlg = '0';

  /**
   * @var boolean
   */
  private $soldoutCheckFlg = '0';

  /**
   * @var integer
   */
  private $labelRemarkFlg = '0';

  /**
   * @var integer
   */
  private $sizeCheckNeedFlg = '0';

  /**
   * @var integer
   */
  private $weightCheckNeedFlg = '0';

  /**
   * @var integer
   */
  private $deliverycodePre = '4';

  /**
   * @var integer
   */
  private $highSalesRateFlg = '0';

  /**
   * @var double
   */
  private $mailSendNums;

  /**
   * @var string
   */
  private $memo;

  /**
   * @var \DateTime
   */
  private $timestamp = 'CURRENT_TIMESTAMP';

  /**
   * @var string
   */
  private $rakutencategories3 = '';


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
   * Set endofavailability
   *
   * @param \DateTime $endofavailability
   *
   * @return TbMainproductsCal
   */
  public function setEndofavailability($endofavailability)
  {
    $this->endofavailability = $endofavailability;

    return $this;
  }

  /**
   * Get endofavailability
   *
   * @return \DateTime
   */
  public function getEndofavailability()
  {
    return $this->endofavailability;
  }

  /**
   * Set deliverycode
   *
   * @param integer $deliverycode
   *
   * @return TbMainproductsCal
   */
  public function setDeliverycode($deliverycode)
  {
    $this->deliverycode = $deliverycode;

    return $this;
  }

  /**
   * Get deliverycode
   *
   * @return integer
   */
  public function getDeliverycode()
  {
    return $this->deliverycode;
  }

  /**
   * Set genkaTnkAve
   *
   * @param integer $genkaTnkAve
   *
   * @return TbMainproductsCal
   */
  public function setGenkaTnkAve($genkaTnkAve)
  {
    $this->genkaTnkAve = $genkaTnkAve;

    return $this;
  }

  /**
   * Get genkaTnkAve
   *
   * @return integer
   */
  public function getGenkaTnkAve()
  {
    return $this->genkaTnkAve;
  }

  /**
   * Set baikaTnk
   *
   * @param integer $baikaTnk
   *
   * @return TbMainproductsCal
   */
  public function setBaikaTnk($baikaTnk)
  {
    $this->baikaTnk = $baikaTnk;

    return $this;
  }

  /**
   * Get baikaTnk
   *
   * @return integer
   */
  public function getBaikaTnk()
  {
    return $this->baikaTnk;
  }

  /**
   * Set sunfactoryset
   *
   * @param \DateTime $sunfactoryset
   *
   * @return TbMainproductsCal
   */
  public function setSunfactoryset($sunfactoryset)
  {
    $this->sunfactoryset = $sunfactoryset;

    return $this;
  }

  /**
   * Get sunfactoryset
   *
   * @return \DateTime
   */
  public function getSunfactoryset()
  {
    return $this->sunfactoryset;
  }

  /**
   * Set listSomeInstantDelivery
   *
   * @param string $listSomeInstantDelivery
   *
   * @return TbMainproductsCal
   */
  public function setListSomeInstantDelivery($listSomeInstantDelivery)
  {
    $this->listSomeInstantDelivery = $listSomeInstantDelivery;

    return $this;
  }

  /**
   * Get listSomeInstantDelivery
   *
   * @return string
   */
  public function getListSomeInstantDelivery()
  {
    return $this->listSomeInstantDelivery;
  }

  /**
   * Set priority
   *
   * @param integer $priority
   *
   * @return TbMainproductsCal
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;

    return $this;
  }

  /**
   * Get priority
   *
   * @return integer
   */
  public function getPriority()
  {
    return $this->priority;
  }

  /**
   * Set earliestOrderDate
   *
   * @param \DateTime $earliestOrderDate
   *
   * @return TbMainproductsCal
   */
  public function setEarliestOrderDate($earliestOrderDate)
  {
    $this->earliestOrderDate = $earliestOrderDate;

    return $this;
  }

  /**
   * Get earliestOrderDate
   *
   * @return \DateTime
   */
  public function getEarliestOrderDate()
  {
    return $this->earliestOrderDate;
  }

  /**
   * Set delayDays
   *
   * @param integer $delayDays
   *
   * @return TbMainproductsCal
   */
  public function setDelayDays($delayDays)
  {
    $this->delayDays = $delayDays;

    return $this;
  }

  /**
   * Get delayDays
   *
   * @return integer
   */
  public function getDelayDays()
  {
    return $this->delayDays;
  }

  /**
   * Set visibleFlg
   *
   * @param integer $visibleFlg
   *
   * @return TbMainproductsCal
   */
  public function setVisibleFlg($visibleFlg)
  {
    $this->visibleFlg = $visibleFlg;

    return $this;
  }

  /**
   * Get visibleFlg
   *
   * @return integer
   */
  public function getVisibleFlg()
  {
    return $this->visibleFlg;
  }

  /**
   * Set salesVolume
   *
   * @param integer $salesVolume
   *
   * @return TbMainproductsCal
   */
  public function setSalesVolume($salesVolume)
  {
    $this->salesVolume = $salesVolume;

    return $this;
  }

  /**
   * Get salesVolume
   *
   * @return integer
   */
  public function getSalesVolume()
  {
    return $this->salesVolume;
  }

  /**
   * Set makeshopRegistrationFlug
   *
   * @param boolean $makeshopRegistrationFlug
   *
   * @return TbMainproductsCal
   */
  public function setMakeshopRegistrationFlug($makeshopRegistrationFlug)
  {
    $this->makeshopRegistrationFlug = $makeshopRegistrationFlug;

    return $this;
  }

  /**
   * Get makeshopRegistrationFlug
   *
   * @return boolean
   */
  public function getMakeshopRegistrationFlug()
  {
    return $this->makeshopRegistrationFlug;
  }

  /**
   * Set rakutenRegistrationFlug
   *
   * @param boolean $rakutenRegistrationFlug
   *
   * @return TbMainproductsCal
   */
  public function setRakutenRegistrationFlug($rakutenRegistrationFlug)
  {
    $this->rakutenRegistrationFlug = $rakutenRegistrationFlug;

    return $this;
  }

  /**
   * Get rakutenRegistrationFlug
   *
   * @return boolean
   */
  public function getRakutenRegistrationFlug()
  {
    return $this->rakutenRegistrationFlug;
  }

  /**
   * Set croozmallRegistrationFlug
   *
   * @param boolean $croozmallRegistrationFlug
   *
   * @return TbMainproductsCal
   */
  public function setCroozmallRegistrationFlug($croozmallRegistrationFlug)
  {
    $this->croozmallRegistrationFlug = $croozmallRegistrationFlug;

    return $this;
  }

  /**
   * Get croozmallRegistrationFlug
   *
   * @return boolean
   */
  public function getCroozmallRegistrationFlug()
  {
    return $this->croozmallRegistrationFlug;
  }

  /**
   * Set amazonRegistrationFlug
   *
   * @param boolean $amazonRegistrationFlug
   *
   * @return TbMainproductsCal
   */
  public function setAmazonRegistrationFlug($amazonRegistrationFlug)
  {
    $this->amazonRegistrationFlug = $amazonRegistrationFlug;

    return $this;
  }

  /**
   * Get amazonRegistrationFlug
   *
   * @return boolean
   */
  public function getAmazonRegistrationFlug()
  {
    return $this->amazonRegistrationFlug;
  }

  /**
   * Set annualSales
   *
   * @param integer $annualSales
   *
   * @return TbMainproductsCal
   */
  public function setAnnualSales($annualSales)
  {
    $this->annualSales = $annualSales;

    return $this;
  }

  /**
   * Get annualSales
   *
   * @return integer
   */
  public function getAnnualSales()
  {
    return $this->annualSales;
  }

  /**
   * Set rakutenRegistrationFlugDate
   *
   * @param \DateTime $rakutenRegistrationFlugDate
   *
   * @return TbMainproductsCal
   */
  public function setRakutenRegistrationFlugDate($rakutenRegistrationFlugDate)
  {
    $this->rakutenRegistrationFlugDate = $rakutenRegistrationFlugDate;

    return $this;
  }

  /**
   * Get rakutenRegistrationFlugDate
   *
   * @return \DateTime
   */
  public function getRakutenRegistrationFlugDate()
  {
    return $this->rakutenRegistrationFlugDate;
  }

  /**
   * Set setnum
   *
   * @param integer $setnum
   *
   * @return TbMainproductsCal
   */
  public function setSetnum($setnum)
  {
    $this->setnum = $setnum;

    return $this;
  }

  /**
   * Get setnum
   *
   * @return integer
   */
  public function getSetnum()
  {
    return $this->setnum;
  }

  /**
   * Set rakutencategoryTep
   *
   * @param string $rakutencategoryTep
   *
   * @return TbMainproductsCal
   */
  public function setRakutencategoryTep($rakutencategoryTep)
  {
    $this->rakutencategoryTep = $rakutencategoryTep;

    return $this;
  }

  /**
   * Get rakutencategoryTep
   *
   * @return string
   */
  public function getRakutencategoryTep()
  {
    return $this->rakutencategoryTep;
  }

  /**
   * Set beingNum
   *
   * @param integer $beingNum
   *
   * @return TbMainproductsCal
   */
  public function setBeingNum($beingNum)
  {
    $this->beingNum = $beingNum;

    return $this;
  }

  /**
   * Get beingNum
   *
   * @return integer
   */
  public function getBeingNum()
  {
    return $this->beingNum;
  }

  /**
   * Set mallPriceFlg
   *
   * @param boolean $mallPriceFlg
   *
   * @return TbMainproductsCal
   */
  public function setMallPriceFlg($mallPriceFlg)
  {
    $this->mallPriceFlg = $mallPriceFlg;

    return $this;
  }

  /**
   * Get mallPriceFlg
   *
   * @return boolean
   */
  public function getMallPriceFlg()
  {
    return $this->mallPriceFlg;
  }

  /**
   * Set daihyoSyohinLabel
   *
   * @param string $daihyoSyohinLabel
   *
   * @return TbMainproductsCal
   */
  public function setDaihyoSyohinLabel($daihyoSyohinLabel)
  {
    $this->daihyoSyohinLabel = $daihyoSyohinLabel;

    return $this;
  }

  /**
   * Get daihyoSyohinLabel
   *
   * @return string
   */
  public function getDaihyoSyohinLabel()
  {
    return $this->daihyoSyohinLabel;
  }

  /**
   * Set maxbuynum
   *
   * @param integer $maxbuynum
   *
   * @return TbMainproductsCal
   */
  public function setMaxbuynum($maxbuynum)
  {
    $this->maxbuynum = $maxbuynum;

    return $this;
  }

  /**
   * Get maxbuynum
   *
   * @return integer
   */
  public function getMaxbuynum()
  {
    return $this->maxbuynum;
  }

  /**
   * Set outlet
   *
   * @param boolean $outlet
   *
   * @return TbMainproductsCal
   */
  public function setOutlet($outlet)
  {
    $this->outlet = $outlet;

    return $this;
  }

  /**
   * Get outlet
   *
   * @return boolean
   */
  public function getOutlet()
  {
    return $this->outlet;
  }

  /**
   * Set bigSize
   *
   * @param boolean $bigSize
   *
   * @return TbMainproductsCal
   */
  public function setBigSize($bigSize)
  {
    $this->bigSize = $bigSize;

    return $this;
  }

  /**
   * Get bigSize
   *
   * @return boolean
   */
  public function getBigSize()
  {
    return $this->bigSize;
  }

  /**
   * Set viewrank
   *
   * @param integer $viewrank
   *
   * @return TbMainproductsCal
   */
  public function setViewrank($viewrank)
  {
    $this->viewrank = $viewrank;

    return $this;
  }

  /**
   * Get viewrank
   *
   * @return integer
   */
  public function getViewrank()
  {
    return $this->viewrank;
  }

  /**
   * Set reviewrequest
   *
   * @param boolean $reviewrequest
   *
   * @return TbMainproductsCal
   */
  public function setReviewrequest($reviewrequest)
  {
    $this->reviewrequest = $reviewrequest;

    return $this;
  }

  /**
   * Get reviewrequest
   *
   * @return boolean
   */
  public function getReviewrequest()
  {
    return $this->reviewrequest;
  }

  /**
   * Set reviewPointAve
   *
   * @param string $reviewPointAve
   *
   * @return TbMainproductsCal
   */
  public function setReviewPointAve($reviewPointAve)
  {
    $this->reviewPointAve = $reviewPointAve;

    return $this;
  }

  /**
   * Get reviewPointAve
   *
   * @return string
   */
  public function getReviewPointAve()
  {
    return $this->reviewPointAve;
  }

  /**
   * Set reviewNum
   *
   * @param integer $reviewNum
   *
   * @return TbMainproductsCal
   */
  public function setReviewNum($reviewNum)
  {
    $this->reviewNum = $reviewNum;

    return $this;
  }

  /**
   * Get reviewNum
   *
   * @return integer
   */
  public function getReviewNum()
  {
    return $this->reviewNum;
  }

  /**
   * Set searchCode
   *
   * @param string $searchCode
   *
   * @return TbMainproductsCal
   */
  public function setSearchCode($searchCode)
  {
    $this->searchCode = $searchCode;

    return $this;
  }

  /**
   * Get searchCode
   *
   * @return string
   */
  public function getSearchCode()
  {
    return $this->searchCode;
  }

  /**
   * Set fixedCost
   *
   * @param integer $fixedCost
   *
   * @return TbMainproductsCal
   */
  public function setFixedCost($fixedCost)
  {
    $this->fixedCost = $fixedCost;

    return $this;
  }

  /**
   * Get fixedCost
   *
   * @return integer
   */
  public function getFixedCost()
  {
    return $this->fixedCost;
  }

  /**
   * Set notfoundImageNoRakuten
   *
   * @param integer $notfoundImageNoRakuten
   *
   * @return TbMainproductsCal
   */
  public function setNotfoundImageNoRakuten($notfoundImageNoRakuten)
  {
    $this->notfoundImageNoRakuten = $notfoundImageNoRakuten;

    return $this;
  }

  /**
   * Get notfoundImageNoRakuten
   *
   * @return integer
   */
  public function getNotfoundImageNoRakuten()
  {
    return $this->notfoundImageNoRakuten;
  }

  /**
   * Set notfoundImageNoDena
   *
   * @param integer $notfoundImageNoDena
   *
   * @return TbMainproductsCal
   */
  public function setNotfoundImageNoDena($notfoundImageNoDena)
  {
    $this->notfoundImageNoDena = $notfoundImageNoDena;

    return $this;
  }

  /**
   * Get notfoundImageNoDena
   *
   * @return integer
   */
  public function getNotfoundImageNoDena()
  {
    return $this->notfoundImageNoDena;
  }

  /**
   * Set startupFlg
   *
   * @param boolean $startupFlg
   *
   * @return TbMainproductsCal
   */
  public function setStartupFlg($startupFlg)
  {
    $this->startupFlg = $startupFlg;

    return $this;
  }

  /**
   * Get startupFlg
   *
   * @return boolean
   */
  public function getStartupFlg()
  {
    return $this->startupFlg;
  }

  /**
   * Set pricedownFlg
   *
   * @param boolean $pricedownFlg
   *
   * @return TbMainproductsCal
   */
  public function setPricedownFlg($pricedownFlg)
  {
    $this->pricedownFlg = $pricedownFlg;

    return $this;
  }

  /**
   * Get pricedownFlg
   *
   * @return boolean
   */
  public function getPricedownFlg()
  {
    return $this->pricedownFlg;
  }

  /**
   * Set redFlg
   *
   * @param boolean $redFlg
   *
   * @return TbMainproductsCal
   */
  public function setRedFlg($redFlg)
  {
    $this->redFlg = $redFlg;

    return $this;
  }

  /**
   * Get redFlg
   *
   * @return boolean
   */
  public function getRedFlg()
  {
    return $this->redFlg;
  }

  /**
   * Set lastOrderdate
   *
   * @param \DateTime $lastOrderdate
   *
   * @return TbMainproductsCal
   */
  public function setLastOrderdate($lastOrderdate)
  {
    $this->lastOrderdate = $lastOrderdate;

    return $this;
  }

  /**
   * Get lastOrderdate
   *
   * @return \DateTime
   */
  public function getLastOrderdate()
  {
    return $this->lastOrderdate;
  }

  /**
   * Set wangStatus
   *
   * @param boolean $wangStatus
   *
   * @return TbMainproductsCal
   */
  public function setWangStatus($wangStatus)
  {
    $this->wangStatus = $wangStatus;

    return $this;
  }

  /**
   * Get wangStatus
   *
   * @return boolean
   */
  public function getWangStatus()
  {
    return $this->wangStatus;
  }

  /**
   * Set orderingAvoidFlg
   *
   * @param integer $orderingAvoidFlg
   *
   * @return TbMainproductsCal
   */
  public function setOrderingAvoidFlg($orderingAvoidFlg)
  {
    $this->orderingAvoidFlg = $orderingAvoidFlg;

    return $this;
  }

  /**
   * Get orderingAvoidFlg
   *
   * @return integer
   */
  public function getOrderingAvoidFlg()
  {
    return $this->orderingAvoidFlg;
  }

  /**
   * Set soldoutCheckFlg
   *
   * @param integer $soldoutCheckFlg
   *
   * @return TbMainproductsCal
   */
  public function setSoldoutCheckFlg($soldoutCheckFlg)
  {
    $this->soldoutCheckFlg = $soldoutCheckFlg;

    return $this;
  }

  /**
   * Get soldoutCheckFlg
   *
   * @return integer
   */
  public function getSoldoutCheckFlg()
  {
    return $this->soldoutCheckFlg;
  }

  /**
   * Set labelRemarkFlg
   *
   * @param integer $labelRemarkFlg
   *
   * @return TbMainproductsCal
   */
  public function setLabelRemarkFlg($labelRemarkFlg)
  {
    $this->labelRemarkFlg = $labelRemarkFlg;

    return $this;
  }

  /**
   * Get labelRemarkFlg
   *
   * @return integer
   */
  public function getLabelRemarkFlg()
  {
    return $this->labelRemarkFlg;
  }

  /**
   * Set sizeCheckNeedFlg
   *
   * @param integer $sizeCheckNeedFlg
   *
   * @return TbMainproductsCal
   */
  public function setSizeCheckNeedFlg($sizeCheckNeedFlg)
  {
    $this->sizeCheckNeedFlg = boolval($sizeCheckNeedFlg ? -1 : 0);

    return $this;
  }

  /**
   * Get sizeCheckNeedFlg
   *
   * @return integer
   */
  public function getSizeCheckNeedFlg()
  {
    return boolval($this->sizeCheckNeedFlg);
  }

  /**
   * Set weightCheckNeedFlg
   *
   * @param integer $weightCheckNeedFlg
   *
   * @return TbMainproductsCal
   */
  public function setWeightCheckNeedFlg($weightCheckNeedFlg)
  {
    $this->weightCheckNeedFlg = boolval($weightCheckNeedFlg) ? -1 : 0;

    return $this;
  }

  /**
   * Get weightCheckNeedFlg
   *
   * @return bool
   */
  public function getWeightCheckNeedFlg()
  {
    return boolval($this->weightCheckNeedFlg);
  }

  /**
   * Set deliverycodePre
   *
   * @param integer $deliverycodePre
   *
   * @return TbMainproductsCal
   */
  public function setDeliverycodePre($deliverycodePre)
  {
    $this->deliverycodePre = $deliverycodePre;

    return $this;
  }

  /**
   * Get deliverycodePre
   *
   * @return integer
   */
  public function getDeliverycodePre()
  {
    return $this->deliverycodePre;
  }

  /**
   * Set highSalesRateFlg
   *
   * @param boolean $highSalesRateFlg
   *
   * @return TbMainproductsCal
   */
  public function setHighSalesRateFlg($highSalesRateFlg)
  {
    $this->highSalesRateFlg = $highSalesRateFlg;

    return $this;
  }

  /**
   * Get highSalesRateFlg
   *
   * @return boolean
   */
  public function getHighSalesRateFlg()
  {
    return $this->highSalesRateFlg;
  }

  /**
   * Set mailSendNums
   *
   * @param double $mailSendNums
   *
   * @return TbMainproductsCal
   */
  public function setMailSendNums($mailSendNums)
  {
    $this->mailSendNums = $mailSendNums;

    return $this;
  }

  /**
   * Get mailSendNums
   *
   * @return double
   */
  public function getMailSendNums()
  {
    return $this->mailSendNums;
  }

  /**
   * Set memo
   *
   * @param string $memo
   *
   * @return TbMainproductsCal
   */
  public function setMemo($memo)
  {
    $this->memo = $memo;

    return $this;
  }

  /**
   * Get memo
   *
   * @return string
   */
  public function getMemo()
  {
    return $this->memo;
  }

  /**
   * Set timestamp
   *
   * @param \DateTime $timestamp
   *
   * @return TbMainproductsCal
   */
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;

    return $this;
  }

  /**
   * Get timestamp
   *
   * @return \DateTime
   */
  public function getTimestamp()
  {
    return $this->timestamp;
  }

  /**
   * Set rakutencategories3
   *
   * @param string $rakutencategories3
   *
   * @return TbMainproductsCal
   */
  public function setRakutencategories3($rakutencategories3)
  {
    $this->rakutencategories3 = $rakutencategories3;

    return $this;
  }

  /**
   * Get rakutencategories3
   *
   * @return string
   */
  public function getRakutencategories3()
  {
    return $this->rakutencategories3;
  }
  
  
  /**
   * @var int
   */
  private $costRate = 0;

  /**
   * @var int
   */
  private $bundleNumAverage = 0;


  /**
   * Set costRate
   *
   * @param int $costRate
   *
   * @return TbMainproductsCal
   */
  public function setCostRate($costRate)
  {
    $this->costRate = $costRate;

    return $this;
  }

  /**
   * Get costRate
   *
   * @return int
   */
  public function getCostRate()
  {
    return $this->costRate;
  }

  /**
   * Set bundleNumAverage
   *
   * @param int $bundleNumAverage
   *
   * @return TbMainproductsCal
   */
  public function setBundleNumAverage($bundleNumAverage)
  {
    $this->bundleNumAverage = $bundleNumAverage;

    return $this;
  }

  /**
   * Get bundleNumAverage
   *
   * @return int
   */
  public function getBundleNumAverage()
  {
    return $this->bundleNumAverage;
  }

  /**
   * Set product
   *
   * @param \MiscBundle\Entity\TbMainproducts $product
   *
   * @return TbMainproductsCal
   */
  public function setProduct(\MiscBundle\Entity\TbMainproducts $product = null)
  {
    $this->product = $product;

    return $this;
  }

 /**
   * @var string
   */
  private $adultCheckStatus = '未審査';

 /**
   * Set adultCheckStatus
   *
   * @param string $adultCheckStatus
   *
   * @return TbMainproductsCal
   */
  public function setAdultCheckStatus($adultCheckStatus)
  {
    $this->adultCheckStatus = $adultCheckStatus;
    return $this;
  }

  /**
   * Get adultCheckStatus
   *
   * @return string
   */
  public function getAdultCheckStatus()
  {
    return $this->adultCheckStatus;
  }

  /**
   * @var integer
   */
  private $compressFlg = '0';

  /**
   * Set compressFlg
   *
   * @param integer $compressFlg
   *
   * @return TbMainproductsCal
   */
  public function setCompressFlg($compressFlg)
  {
    $this->compressFlg = boolval($compressFlg) ? -1 : 0;
    return $this;
  }

  /**
   * Get compressFlg
   *
   * @return integer
   */
  public function getCompressFlg()
  {
    return boolval($this->compressFlg);
  }
  
  /**
   * @var int
   */
  private $imagePhotoNeedFlg = '0';


  /**
   * Set imagePhotoNeedFlg
   *
   * @param int $imagePhotoNeedFlg
   *
   * @return TbMainproductsCal
   */
  public function setImagePhotoNeedFlg($imagePhotoNeedFlg)
  {
    $this->imagePhotoNeedFlg = $imagePhotoNeedFlg;

    return $this;
  }

  /**
   * Get imagePhotoNeedFlg
   *
   * @return int
   */
  public function getImagePhotoNeedFlg()
  {
    return $this->imagePhotoNeedFlg;
  }
  /**
   * @var integer
   */
  private $qualityLevel = 0;

  /**
   * @var \DateTime
   */
  private $qualityLevelUpdated;


  /**
   * Set qualityLevel
   *
   * @param integer $qualityLevel
   * @return TbMainproductsCal
   */
  public function setQualityLevel($qualityLevel)
  {
    $this->qualityLevel = $qualityLevel;

    return $this;
  }

  /**
   * Get qualityLevel
   *
   * @return integer 
   */
  public function getQualityLevel()
  {
    return $this->qualityLevel;
  }

  /**
   * Set qualityLevelUpdated
   *
   * @param \DateTime $qualityLevelUpdated
   * @return TbMainproductsCal
   */
  public function setQualityLevelUpdated($qualityLevelUpdated)
  {
    $this->qualityLevelUpdated = $qualityLevelUpdated;

    return $this;
  }

  /**
   * Get qualityLevelUpdated
   *
   * @return \DateTime 
   */
  public function getQualityLevelUpdated()
  {
    return $this->qualityLevelUpdated;
  }
}
