<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Util\DbCommonUtil;

/**
 * TbSalesDetail
 */
class TbSalesDetail
{
  use ArrayTrait;

  // 配送情報出力関連 受注関連コード tb_delivery_method の delivery_id を定義
  const PAYMENT_METHOD_CODE_DAIBIKI = '1'; // 「1:代金引換」
  const SHIPPING_METHOD_CODE_SAGAWA = '13'; // 佐川
  const SHIPPING_METHOD_CODE_CLICKPOST = '14'; // クリックポスト
  const SHIPPING_METHOD_CODE_CLICKPOST_2 = '36'; // クリックポスト_2
  const SHIPPING_METHOD_CODE_MAILBIN = '22'; // ヤマトDM便
  const SHIPPING_METHOD_CODE_NEKOPOSU = '28'; // ねこポス
  const SHIPPING_METHOD_CODE_YUU_PACK = '30'; // ゆうパック
  const SHIPPING_METHOD_CODE_YUU_PACK_RSL = '34'; // ゆうパック(RSL)
  const SHIPPING_METHOD_CODE_YUU_PACKET = '35'; // ゆうパケット
  const SHIPPING_METHOD_CODE_TEIKEIGAI_DAIBIKI = '40'; // 定形外代引き
  const SHIPPING_METHOD_CODE_TEIKEIGAI = '41'; // 定形外
  const SHIPPING_METHOD_CODE_TEIKEI = '50'; // 定形
  const SHIPPING_METHOD_CODE_RAKUTEN_EXPRESS = '61'; // 楽天エクスプレス
  const SHIPPING_METHOD_CODE_FUKUYAMA = '55'; // 福山
  const SHIPPING_METHOD_CODE_YAMATO = '20'; // ﾔﾏﾄ(発払い)B2v6

  // 受注状態文字列

  /** 受注状態：取込情報不足 */
  const ORDER_STATUS_CAPTURE_INFO_LACK = "取込情報不足";
  /** 受注状態：受注メール取込済 */
  const ORDER_STATUS_MAIL_IMPORTED = "受注メール取込済";
  /** 受注状態：起票済(CSV/手入力) */
  const ORDER_STATUS_DRAFTED = "起票済(CSV/手入力)";
  /** 受注状態：納品書印刷待ち */
  const ORDER_STATUS_WAIT_PRINT = "納品書印刷待ち";
  /** 受注状態：納品書印刷済 */
  const ORDER_STATUS_PRINTED = "納品書印刷済";
  /** 受注状態：出荷確定済（完了） */
  const ORDER_STATUS_VALUE_FIX = "出荷確定済（完了）";

  // like用店舗名。前方・後方とも % を付けて使用する事。実際の店舗名は「Plus Nao 楽天市場店」といった形式となる。
  /** 店舗名（LIKE用）：YahooPlusNao */
  const SHOP_NAME_VALUE_LIKE_YAHOO = "Yahoo%PlusNao";
  /** 店舗名（LIKE用）：Yahoo(おとりよせ.com） */
  const SHOP_NAME_VALUE_LIKE_YAHOO_OTORIYOSE = "Yahoo(おとりよせ.com）";
  /** 店舗名（LIKE用）：Amazon */
  const SHOP_NAME_VALUE_LIKE_AMAZON = "Amazon";
  /** 店舗名（LIKE用）：Wowma */
  const SHOP_NAME_VALUE_LIKE_WOWMA = "Wowma";
  /** 店舗名（LIKE用）：ポンパレ */
  const SHOP_NAME_VALUE_LIKE_PONPARE = "ポンパレ";
  /** 店舗名（LIKE用）：楽天 */
  const SHOP_NAME_VALUE_LIKE_RAKUTEN = "楽天";

  // 受注状態（NE側で定義）　https://developer.next-engine.com/api/api_v1_system_orderstatus/info
  /** 受注状態コード：出荷確定済（完了） */
  const ORDER_STATUS_CODE_SHIPPING_FINISH = 50;

  // 受注担当者文字列
  /** 受注担当者：API接続ID */
  const ORDER_ACCOUNT_IN_CHARGE_API = "API接続ID";

  /**
   * 代引きか否か
   */
  public function isDaibiki()
  {
    return intval($this->getPaymentMethodCode()) == self::PAYMENT_METHOD_CODE_DAIBIKI;
  }

  /**
   * 有効な伝票・明細か
   */
  public function isActive()
  {
    return $this->getCanceled() == 0 && $this->getDetailCanceled() == 0;
  }

  /**
   * 配送先都道府県取得
   */
  public function getDeliveryPrefecture()
  {
    $prefectures = [
      '北海道'
      , '青森県'
      , '岩手県'
      , '宮城県'
      , '秋田県'
      , '山形県'
      , '福島県'
      , '茨城県'
      , '栃木県'
      , '群馬県'
      , '埼玉県'
      , '千葉県'
      , '東京都'
      , '神奈川県'
      , '新潟県'
      , '富山県'
      , '石川県'
      , '福井県'
      , '山梨県'
      , '長野県'
      , '岐阜県'
      , '静岡県'
      , '愛知県'
      , '三重県'
      , '滋賀県'
      , '京都府'
      , '大阪府'
      , '兵庫県'
      , '奈良県'
      , '和歌山県'
      , '鳥取県'
      , '島根県'
      , '岡山県'
      , '広島県'
      , '山口県'
      , '徳島県'
      , '香川県'
      , '愛媛県'
      , '高知県'
      , '福岡県'
      , '佐賀県'
      , '長崎県'
      , '熊本県'
      , '大分県'
      , '宮崎県'
      , '鹿児島県'
      , '沖縄県'
    ];

    $pattern = sprintf('/^(%s)/u', implode('|', $prefectures));
    if (preg_match($pattern, $this->getDeliveryAddress1(), $m)) {
      return $m[1];
    }

    return null;
  }

  /**
   * 配送先郵便番号 ハイフン付き（Amazon FBAマルチチャネルなど）
   */
  public function getDeliveryZipcodeWithHyphen()
  {
    $ret = $this->getDeliveryZipcode();
    if (strlen($ret) && preg_match('/^(\d{3})(\d{2,4})$/', $ret, $m)) {
      $ret = sprintf('%s-%s', $m[1], $m[2]);
    }
    return $ret;
  }

  /**
   * 税率
   * ※本来はデータで持っているべきだが持っていない。
   *   NextEngineでは税欄で合計のつじつまを合わせるため、計算からの算出も不可。
   *   ひとまずの用途として固定値で返す。
   *   完了済みの過去データに対しての計算に用いなければ問題無い、はず。
   */
  private $tax_rate = DbCommonUtil::CURRENT_TAX_RATE;
  public function getTaxRate()
  {
    return $this->tax_rate;
  }

  /**
   * 税込み売単価
   */
  public function getUnitPriceWithTax()
  {
    return floor($this->getUnitPrice() * $this->getTaxRate());
  }

  /**
   * 売単価税額
   */
  public function getUnitTax()
  {
    return $this->getUnitPriceWithTax() - $this->getUnitPrice();
  }


  // -------------------------------------
  /**
   * @var integer
   */
  private $voucher_number;

  /**
   * @var integer
   */
  private $line_number;

  /**
   * @var string
   */
  private $order_number;

  /**
   * @var \DateTime
   */
  private $order_date;

  /**
   * @var \DateTime
   */
  private $shipping_date;

  /**
   * @var \DateTime
   */
  private $import_date;

  /**
   * @var string
   */
  private $canceled = '0';

  /**
   * @var string
   */
  private $shop_name;

  /**
   * @var string
   */
  private $ne_mall_id;

  /**
   * @var string
   */
  private $shipping_method_name;

  /**
   * @var string
   */
  private $shipping_method_code;

  /**
   * @var string
   */
  private $order_status = '';

  /**
   * @var string
   */
  private $detail_canceled = '';

  /**
   * @var string
   */
  private $ne_syohin_syohin_code = '';

  /**
   * @var integer
   */
  private $ordered_num = 0;

  /**
   * @var integer
   */
  private $assigned_num = 0;

  /**
   * @var \DateTime
   */
  private $assigned_date;

  /**
   * @var string
   */
  private $customer_name = '';

  /**
   * @var string
   */
  private $delivery_name = '';

  /**
   * @var string
   */
  private $order_account_in_charge = '';


  /**
   * Set voucher_number
   *
   * @param integer $voucherNumber
   * @return TbSalesDetail
   */
  public function setVoucherNumber($voucherNumber)
  {
    $this->voucher_number = $voucherNumber;

    return $this;
  }

  /**
   * Get voucher_number
   *
   * @return integer
   */
  public function getVoucherNumber()
  {
    return $this->voucher_number;
  }

  /**
   * Set line_number
   *
   * @param integer $lineNumber
   * @return TbSalesDetail
   */
  public function setLineNumber($lineNumber)
  {
    $this->line_number = $lineNumber;

    return $this;
  }

  /**
   * Get line_number
   *
   * @return integer
   */
  public function getLineNumber()
  {
    return $this->line_number;
  }

  /**
   * Set order_number
   *
   * @param string $orderNumber
   * @return TbSalesDetail
   */
  public function setOrderNumber($orderNumber)
  {
    $this->order_number = $orderNumber;

    return $this;
  }

  /**
   * Get order_number
   *
   * @return string
   */
  public function getOrderNumber()
  {
    return $this->order_number;
  }

  /**
   * Set order_date
   *
   * @param \DateTime $orderDate
   * @return TbSalesDetail
   */
  public function setOrderDate($orderDate)
  {
    $this->order_date = $orderDate;

    return $this;
  }

  /**
   * Get order_date
   *
   * @return \DateTime
   */
  public function getOrderDate()
  {
    return $this->order_date;
  }

  /**
   * Set shipping_date
   *
   * @param \DateTime $shippingDate
   * @return TbSalesDetail
   */
  public function setShippingDate($shippingDate)
  {
    $this->shipping_date = $shippingDate;

    return $this;
  }

  /**
   * Get shipping_date
   *
   * @return \DateTime
   */
  public function getShippingDate()
  {
    return $this->shipping_date;
  }

  /**
   * Set import_date
   *
   * @param \DateTime $importDate
   * @return TbSalesDetail
   */
  public function setImportDate($importDate)
  {
    $this->import_date = $importDate;

    return $this;
  }

  /**
   * Get import_date
   *
   * @return \DateTime
   */
  public function getImportDate()
  {
    return $this->import_date;
  }

  /**
   * Set canceled
   *
   * @param string $canceled
   * @return TbSalesDetail
   */


  public function setCanceled($canceled)
  {
    $this->canceled = $canceled;

    return $this;
  }

  /**
   * Get canceled
   *
   * @return string
   */
  public function getCanceled()
  {
    return $this->canceled;
  }

  /**
   * Set shop_name
   *
   * @param string $shopName
   * @return TbSalesDetail
   */
  public function setShopName($shopName)
  {
    $this->shop_name = $shopName;

    return $this;
  }

  /**
   * Get shop_name
   *
   * @return string
   */
  public function getShopName()
  {
    return $this->shop_name;
  }

  /**
   * Set ne_mall_id
   *
   * @param string $neMallId
   * @return TbSalesDetail
   */
  public function setNeMallId($neMallId)
  {
    $this->ne_mall_id = $neMallId;

    return $this;
  }

  /**
   * Get ne_mall_id
   *
   * @return string
   */
  public function getNeMallId()
  {
    return $this->ne_mall_id;
  }

  /**
   * Set shipping_method_name
   *
   * @param string $shippingMethodName
   * @return TbSalesDetail
   */
  public function setShippingMethodName($shippingMethodName)
  {
    $this->shipping_method_name = $shippingMethodName;

    return $this;
  }

  /**
   * Get shipping_method_name
   *
   * @return string
   */
  public function getShippingMethodName()
  {
    return $this->shipping_method_name;
  }

  /**
   * Set shipping_method_code
   *
   * @param string $shippingMethodCode
   * @return TbSalesDetail
   */
  public function setShippingMethodCode($shippingMethodCode)
  {
    $this->shipping_method_code = $shippingMethodCode;

    return $this;
  }

  /**
   * Get shipping_method_code
   *
   * @return string
   */
  public function getShippingMethodCode()
  {
    return $this->shipping_method_code;
  }

  /**
   * Set order_status
   *
   * @param string $orderStatus
   * @return TbSalesDetail
   */
  public function setOrderStatus($orderStatus)
  {
    $this->order_status = $orderStatus;

    return $this;
  }

  /**
   * Get order_status
   *
   * @return string
   */
  public function getOrderStatus()
  {
    return $this->order_status;
  }

  /**
   * Set detail_canceled
   *
   * @param string $detailCanceled
   * @return TbSalesDetail
   */
  public function setDetailCanceled($detailCanceled)
  {
    $this->detail_canceled = $detailCanceled;

    return $this;
  }

  /**
   * Get detail_canceled
   *
   * @return string
   */
  public function getDetailCanceled()
  {
    return $this->detail_canceled;
  }

  /**
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbSalesDetail
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
   * Set ordered_num
   *
   * @param integer $orderedNum
   * @return TbSalesDetail
   */
  public function setOrderedNum($orderedNum)
  {
    $this->ordered_num = $orderedNum;

    return $this;
  }

  /**
   * Get ordered_num
   *
   * @return integer
   */
  public function getOrderedNum()
  {
    return $this->ordered_num;
  }

  /**
   * Set assigned_num
   *
   * @param integer $assignedNum
   * @return TbSalesDetail
   */
  public function setAssignedNum($assignedNum)
  {
    $this->assigned_num = $assignedNum;

    return $this;
  }

  /**
   * Get assigned_num
   *
   * @return integer
   */
  public function getAssignedNum()
  {
    return $this->assigned_num;
  }

  /**
   * Set assigned_date
   *
   * @param \DateTime $assignedDate
   * @return TbSalesDetail
   */
  public function setAssignedDate($assignedDate)
  {
    $this->assigned_date = $assignedDate;

    return $this;
  }

  /**
   * Get assigned_date
   *
   * @return \DateTime
   */
  public function getAssignedDate()
  {
    return $this->assigned_date;
  }

  /**
   * Set customer_name
   *
   * @param string $customerName
   * @return TbSalesDetail
   */
  public function setCustomerName($customerName)
  {
    $this->customer_name = $customerName;

    return $this;
  }

  /**
   * Get customer_name
   *
   * @return string
   */
  public function getCustomerName()
  {
    return $this->customer_name;
  }

  /**
   * Set delivery_name
   *
   * @param string $deliveryName
   * @return TbSalesDetail
   */
  public function setDeliveryName($deliveryName)
  {
    $this->delivery_name = $deliveryName;

    return $this;
  }

  /**
   * @var string
   */
  private $delivery_time_zone = '';

  /**
   * Get delivery_name
   *
   * @return string
   */
  public function getDeliveryName()
  {
    return $this->delivery_name;
  }

  /**
   * @var string
   */
  private $delivery_name_kana = '';

  /**
   * @var string
   */
  private $delivery_tel = '';

  /**
   * @var string
   */
  private $delivery_zipcode = '';

  /**
   * @var string
   */
  private $delivery_address1 = '';

  /**
   * @var string
   */
  private $delivery_address2 = '';

  /**
   * @var \DateTime
   */
  private $shipping_planed_date_manual;

  /**
   * @var \DateTime
   */
  private $shipping_ordered_date;

  /**
   * Set delivery_time_zone
   *
   * @param string $deliveryTimeZone
   * @return TbSalesDetail
   */
  public function setDeliveryTimeZone($deliveryTimeZone)
  {
    $this->delivery_time_zone = $deliveryTimeZone;

    return $this;
  }
  /**
   * Get delivery_time_zone
   *
   * @return string
   */
  public function getDeliveryTimeZone()
  {
    return $this->delivery_time_zone;
  }
  /**
   * Set delivery_name_kana
   *
   * @param string $deliveryNameKana
   * @return TbSalesDetail
   */
  public function setDeliveryNameKana($deliveryNameKana)
  {
    $this->delivery_name_kana = $deliveryNameKana;

    return $this;
  }

  /**
   * Get delivery_name_kana
   *
   * @return string
   */
  public function getDeliveryNameKana()
  {
    return $this->delivery_name_kana;
  }

  /**
   * Set delivery_tel
   *
   * @param string $deliveryTel
   * @return TbSalesDetail
   */
  public function setDeliveryTel($deliveryTel)
  {
    $this->delivery_tel = $deliveryTel;

    return $this;
  }

  /**
   * Get delivery_tel
   *
   * @return string
   */
  public function getDeliveryTel()
  {
    return $this->delivery_tel;
  }

  /**
   * Set delivery_zipcode
   *
   * @param string $deliveryZipcode
   * @return TbSalesDetail
   */
  public function setDeliveryZipcode($deliveryZipcode)
  {
    $this->delivery_zipcode = $deliveryZipcode;

    return $this;
  }

  /**
   * Get delivery_zipcode
   *
   * @return string
   */
  public function getDeliveryZipcode()
  {
    return $this->delivery_zipcode;
  }

  /**
   * Set delivery_address1
   *
   * @param string $deliveryAddress1
   * @return TbSalesDetail
   */
  public function setDeliveryAddress1($deliveryAddress1)
  {
    $this->delivery_address1 = $deliveryAddress1;

    return $this;
  }

  /**
   * Get delivery_address1
   *
   * @return string
   */
  public function getDeliveryAddress1()
  {
    return $this->delivery_address1;
  }

  /**
   * Set delivery_address2
   *
   * @param string $deliveryAddress2
   * @return TbSalesDetail
   */
  public function setDeliveryAddress2($deliveryAddress2)
  {
    $this->delivery_address2 = $deliveryAddress2;

    return $this;
  }

  /**
   * Get delivery_address2
   *
   * @return string
   */
  public function getDeliveryAddress2()
  {
    return $this->delivery_address2;
  }

  /**
   * Set shipping_planed_date_manual
   *
   * @param \DateTime $shippingPlanedDateManual
   * @return TbSalesDetail
   */
  public function setShippingPlanedDateManual($shippingPlanedDateManual)
  {
    $this->shipping_planed_date_manual = $shippingPlanedDateManual;

    return $this;
  }

  /**
   * Get shipping_planed_date_manual
   *
   * @return \DateTime
   */
  public function getShippingPlanedDateManual()
  {
    return $this->shipping_planed_date_manual;
  }

  /**
   * Set shipping_ordered_date
   *
   * @param \DateTime $shippingOrderedDate
   * @return TbSalesDetail
   */
  public function setShippingOrderedDate($shippingOrderedDate)
  {
    $this->shipping_ordered_date = $shippingOrderedDate;

    return $this;
  }

  /**
   * Get shipping_ordered_date
   *
   * @return \DateTime
   */
  public function getShippingOrderedDate()
  {
    return $this->shipping_ordered_date;
  }

  /**
   * @var string
   */
  private $payment_method_name;

  /**
   * @var string
   */
  private $payment_method_code;

  /**
   * @var integer
   */
  private $payment_total = 0;

  /**
   * @var integer
   */
  private $tax = 0;

  /**
   * @var integer
   */
  private $point_size = 0;

  /**
   * @var integer
   */
  private $delivery_charge = 0;

  /**
   * @var integer
   */
  private $discounted_amount = 0;

  /**
   * Set payment_method_name
   *
   * @param string $paymentMethodName
   * @return TbSalesDetail
   */
  public function setPaymentMethodName($paymentMethodName)
  {
    $this->payment_method_name = $paymentMethodName;

    return $this;
  }

  /**
   * Get payment_method_name
   *
   * @return string
   */
  public function getPaymentMethodName()
  {
    return $this->payment_method_name;
  }

  /**
   * Set payment_method_code
   *
   * @param string $paymentMethodCode
   * @return TbSalesDetail
   */
  public function setPaymentMethodCode($paymentMethodCode)
  {
    $this->payment_method_code = $paymentMethodCode;

    return $this;
  }

  /**
   * Get payment_method_code
   *
   * @return string
   */
  public function getPaymentMethodCode()
  {
    return $this->payment_method_code;
  }

  /**
   * Set payment_total
   *
   * @param integer $paymentTotal
   * @return TbSalesDetail
   */
  public function setPaymentTotal($paymentTotal)
  {
    $this->payment_total = $paymentTotal;

    return $this;
  }

  /**
   * Get payment_total
   *
   * @return integer
   */
  public function getPaymentTotal()
  {
    return $this->payment_total;
  }

  /**
   * Set tax
   *
   * @param integer $tax
   * @return TbSalesDetail
   */
  public function setTax($tax)
  {
    $this->tax = $tax;

    return $this;
  }

  /**
   * Get tax
   *
   * @return integer
   */
  public function getTax()
  {
    return $this->tax;
  }
  /**
   * Set point_size
   *
   * @param integer $pointSize
   * @return TbSalesDetail
   */
  public function setPointSize($pointSize)
  {
    $this->point_size = $pointSize;

    return $this;
  }

  /**
   * Get point_size
   *
   * @return integer
   */
  public function getPointSize()
  {
    return $this->point_size;
  }

  /**
   * Set delivery_charge
   *
   * @param integer $deliveryCharge
   * @return TbSalesDetail
   */
  public function setDeliveryCharge($deliveryCharge)
  {
    $this->delivery_charge = $deliveryCharge;

    return $this;
  }

  /**
   * Get delivery_charge
   *
   * @return integer
   */
  public function getDeliveryCharge()
  {
    return $this->delivery_charge;
  }

  /**
   * Set discounted_amount
   *
   * @param integer $discountedAmount
   * @return TbSalesDetail
   */
  public function setDiscountedAmount($discountedAmount)
  {
    $this->discounted_amount = $discountedAmount;

    return $this;
  }

  /**
   * Get discounted_amount
   *
   * @return integer
   */
  public function getDiscountedAmount()
  {
    return $this->discounted_amount;
  }

  /**
   * @var string
   */
  private $customer_name_kana = '';

  /**
   * @var string
   */
  private $customer_tel = '';

  /**
   * @var string
   */
  private $customer_zipcode = '';

  /**
   * @var string
   */
  private $customer_address1 = '';

  /**
   * @var string
   */
  private $customer_address2 = '';

  /**
   * @var string
   */
  private $customer_mail = '';


  /**
   * Set customer_name_kana
   *
   * @param string $customerNameKana
   * @return TbSalesDetail
   */
  public function setCustomerNameKana($customerNameKana)
  {
    $this->customer_name_kana = $customerNameKana;

    return $this;
  }

  /**
   * Get customer_name_kana
   *
   * @return string
   */
  public function getCustomerNameKana()
  {
    return $this->customer_name_kana;
  }

  /**
   * Set customer_tel
   *
   * @param string $customerTel
   * @return TbSalesDetail
   */
  public function setCustomerTel($customerTel)
  {
    $this->customer_tel = $customerTel;

    return $this;
  }

  /**
   * Get customer_tel
   *
   * @return string
   */
  public function getCustomerTel()
  {
    return $this->customer_tel;
  }

  /**
   * Set customer_zipcode
   *
   * @param string $customerZipcode
   * @return TbSalesDetail
   */
  public function setCustomerZipcode($customerZipcode)
  {
    $this->customer_zipcode = $customerZipcode;

    return $this;
  }

  /**
   * Get customer_zipcode
   *
   * @return string
   */
  public function getCustomerZipcode()
  {
    return $this->customer_zipcode;
  }

  /**
   * Set customer_address1
   *
   * @param string $customerAddress1
   * @return TbSalesDetail
   */
  public function setCustomerAddress1($customerAddress1)
  {
    $this->customer_address1 = $customerAddress1;

    return $this;
  }

  /**
   * Get customer_address1
   *
   * @return string
   */
  public function getCustomerAddress1()
  {
    return $this->customer_address1;
  }

  /**
   * Set customer_address2
   *
   * @param string $customerAddress2
   * @return TbSalesDetail
   */
  public function setCustomerAddress2($customerAddress2)
  {
    $this->customer_address2 = $customerAddress2;

    return $this;
  }

  /**
   * Get customer_address2
   *
   * @return string
   */
  public function getCustomerAddress2()
  {
    return $this->customer_address2;
  }

  /**
   * Set customer_mail
   *
   * @param string $customerMail
   * @return TbSalesDetail
   */
  public function setCustomerMail($customerMail)
  {
    $this->customer_mail = $customerMail;

    return $this;
  }

  /**
   * Get customer_mail
   *
   * @return string
   */
  public function getCustomerMail()
  {
    return $this->customer_mail;
  }
  /**
   * @var integer
   */
  private $payment_charge = 0;


  /**
   * Set payment_charge
   *
   * @param integer $paymentCharge
   * @return TbSalesDetail
   */
  public function setPaymentCharge($paymentCharge)
  {
    $this->payment_charge = $paymentCharge;

    return $this;
  }

  /**
   * Get payment_charge
   *
   * @return integer
   */
  public function getPaymentCharge()
  {
    return $this->payment_charge;
  }

  /**
   * Set order_account_in_charge
   *
   * @param integer $orderAccountInCharge
   * @return TbSalesDetail
   */
  public function setOrderAccountInCharge($orderAccountInCharge)
  {
    $this->order_account_in_charge = $orderAccountInCharge;

    return $this;
  }

  /**
   * Get order_account_in_charge
   *
   * @return string
   */
  public function getOrderAccountInCharge()
  {
    return $this->order_account_in_charge;
  }

  /**
   * @var string
   */
  private $voucher_syohin_name = '';


  /**
   * Set voucher_syohin_name
   *
   * @param string $voucherSyohinName
   * @return TbSalesDetail
   */
  public function setVoucherSyohinName($voucherSyohinName)
  {
    $this->voucher_syohin_name = $voucherSyohinName;

    return $this;
  }

  /**
   * Get voucher_syohin_name
   *
   * @return string
   */
  public function getVoucherSyohinName()
  {
    return $this->voucher_syohin_name;
  }


  /**
   * @var integer
   */
  private $unit_price = 0;


  /**
   * Set unit_price
   *
   * @param integer $unitPrice
   * @return TbSalesDetail
   */
  public function setUnitPrice($unitPrice)
  {
    $this->unit_price = $unitPrice;

    return $this;
  }

  /**
   * Get unit_price
   *
   * @return integer
   */
  public function getUnitPrice()
  {
    return $this->unit_price;
  }
}
