<?php

namespace MiscBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * TbMainproducts
 */
class MappedSuperClassTbMainproducts
{
  /** @var TbMainproductsCal */
  protected $cal;

  /** @var ArrayCollection|TbProductchoiceitems[] */
  protected $choiceItems;

  /** @var TbMainproductsEnglish */
  protected $english;

  /**
   * @var TbShippingdivision
   */ 
  protected $shippingdivision;

  /**
   * @var \MiscBundle\Entity\TbVendormasterdata
   */
  protected $vendor;

  public function __construct()
  {
    $this->choiceItems = new ArrayCollection();
  }

  /**
   * cal 取得
   * @return TbMainproductsCal
   */
  public function getCal()
  {
    return $this->cal;
  }

  /**
   * choiceItems 取得
   * @return ArrayCollection|TbProductchoiceitems[]
   */
  public function getChoiceItems()
  {
    return $this->choiceItems;
  }

  /**
   * english 取得
   * @return TbMainproductsEnglish
   */
  public function getEnglish()
  {
    return $this->english;
  }

  /**
   * get shippingdivision
   * 
   * @return TbShippingdivision
   */
  public function getShippingdivision()
  {
    return $this->shippingdivision;
  }

  /**
   * set shippingdivision
   *
   * @param \MiscBundle\Entity\TbShippingdivision $shippingdivision
   * @return TbMainproducts
   */
  public function setShippingdivision(\MiscBundle\Entity\TbShippingdivision $shippingdivision = null)
  {
    $this->shippingdivision = $shippingdivision;

    return $this;
  }

  /**
   * メイン画像URL取得
   * @param string $parentPath
   * @return null|string
   */
  public function getImageUrl($parentPath = '')
  {
    return TbMainproductsRepository::createImageUrl($this->getImageP1Directory(), $this->getImageP1Filename(), $parentPath);
  }

  /**
   * 楽天URL取得
   * @return string
   */
  public function getRakutenDetailUrl()
  {
    return TbMainproductsRepository::getRakutenDetailUrl($this->getDaihyoSyohinCode());
  }

  /**
   * 画像関連 getter メソッド名取得
   * @param string $type 'caption' | 'address' | 'directory' | 'filename'
   * @param string $code 'p001' ～ 'p009'
   * @return string メソッド名 (get～～)
   */
  public static function getGetterName($type, $code)
  {
    if (
         !in_array($type, ['caption', 'address', 'directory', 'filename'])
      || !preg_match('/^p\d+$/', $code)
    ) {
      throw new RuntimeException('invalid arguments.');
    }

    if (preg_match('/^p(\d+)$/', $code, $m)) {
      $code = sprintf('p%d', intval($m[1])); // フィールド名に合わせて桁落とし。
    }

    return sprintf('getImage%s%s', ucfirst($code), ucfirst($type));
  }

  /**
   * 画像関連 setter メソッド名取得
   * @param string $type 'caption' | 'address' | 'directory' | 'filename'
   * @param string $code 'p001' ～ 'p009'
   * @return string メソッド名 (set～～)
   */
  public static function getSetterName($type, $code)
  {
    if (
         !in_array($type, ['caption', 'address', 'directory', 'filename'])
      || !preg_match('/^p\d+$/', $code)
    ) {
      throw new RuntimeException('invalid arguments.');
    }

    if (preg_match('/^p(\d+)$/', $code, $m)) {
      $code = sprintf('p%d', intval($m[1])); // フィールド名に合わせて桁落とし。
    }

    return sprintf('setImage%s%s', ucfirst($code), ucfirst($type));
  }


  /**
   * 画像関連 項目セット
   * @param string $type 'caption' | 'address' | 'directory' | 'filename'
   * @param string $code 'p001' ～ 'p009'
   * @param string $value
   * @return TbMainproducts $this
   */
  public function setImageFieldData($type, $code, $value)
  {
    $method = self::getSetterName($type, $code);
    return $this->{$method}($value);
  }

  /**
   * 画像関連 項目取得
   * @param string $type 'caption' | 'address' | 'directory' | 'filename'
   * @param string $code 'p001' ～ 'p009'
   */
  public function getImageFieldData($type, $code)
  {
    $method = self::getGetterName($type, $code);
    return $this->{$method}();
  }

  /**
   * 重量設定漏れ
   * @return bool
   */
  public function isSetWeight()
  {
    return (!is_null($this->getWeight()) && $this->getWeight() > 0);
  }

  /**
   * サイズ設定漏れ
   * @return bool
   */
  public function isSetSize()
  {
    return (
      $this->getDepth() > 0
      && $this->getWidth() > 0
      && $this->getHeight() > 0
    );
  }

  /**
   * 重量設定漏れ
   * @return bool
   */
  public function isSetWeightSize()
  {
    return $this->isSetSize() && $this->isSetWeight();
  }

  /**
   * 重厚計測 ON ?
   * @return bool
   */
  public function isWeightCheckNeed()
  {
    if ($this->getCal()) {
      return $this->getCal()->isWeightCheckNeed();
    }

    return null;
  }

  // -------------------------------
  // 連結データ操作
  // -------------------------------
  /**
   * メール便枚数 セット
   * @param $num
   */
  public function setMailSendNums($num)
  {
    if ($this->getCal()) {
      $this->getCal()->setMailSendNums($num);
    }
  }

  /**
   * メール便枚数 ゲット
   * @return int
   */
  public function getMailSendNums()
  {
    if ($this->getCal()) {
      return $this->getCal()->getMailSendNums();
    }

    return null;
  }

  /**
   * 重厚計測 セット
   * @param $val
   */
  public function setWeightCheckNeedFlg($val)
  {
    if ($this->getCal()) {
      $val = boolval($val) ? -1 : 0;
      $this->getCal()->setWeightCheckNeedFlg($val);
    }
  }

  /**
   * 重厚計測 ゲット
   * @return bool
   */
  public function getWeightCheckNeedFlg()
  {
    if ($this->getCal()) {
      return $this->getCal()->getWeightCheckNeedFlg();
    }

    return null;
  }

  /**
   * 圧縮商品 セット
   * @param $val
   */
  public function setCompressFlg($val)
  {
    if ($this->getCal()) {
      $val = boolval($val) ? -1 : 0;
      $this->getCal()->setCompressFlg($val);
    }
  }

  /**
   * 圧縮商品 ゲット
   * @return bool
   */
  public function getCompressFlg()
  {
    if ($this->getCal()) {
      return $this->getCal()->getCompressFlg();
    }

    return null;
  }

  /**
   * セット商品判定 （セットSKU保持判定）
   */
  public function hasSetSku()
  {
    foreach($this->getChoiceItems() as $choice) {
      if ($choice->isSetSku()) {
        return true;
      }
    }

    return false;
  }


  // -----------------------------------
  // setter, getter
  // -----------------------------------

  /**
   * @var string
   */
  protected $daihyoSyohinCode;

  /**
   * @var string
   */
  protected $ne_directory_id;

  /**
   * @var string
   */
  protected $yahoo_directory_id;

  /**
   * @var string
   */
  protected $image_p1_address;

  /**
   * @var string
   */
  protected $image_p2_address;

  /**
   * @var string
   */
  protected $image_p3_address;

  /**
   * @var string
   */
  protected $image_p4_address;

  /**
   * @var string
   */
  protected $image_p5_address;

  /**
   * @var string
   */
  protected $image_p6_address;

  /**
   * @var string
   */
  protected $image_p7_address;

  /**
   * @var string
   */
  protected $image_p8_address;

  /**
   * @var string
   */
  protected $image_p9_address;

  /**
   * @var string
   */
  protected $image_p1_filename;

  /**
   * @var string
   */
  protected $image_p2_filename;

  /**
   * @var string
   */
  protected $image_p3_filename;

  /**
   * @var string
   */
  protected $image_p4_filename;

  /**
   * @var string
   */
  protected $image_p5_filename;

  /**
   * @var string
   */
  protected $image_p6_filename;

  /**
   * @var string
   */
  protected $image_p7_filename;

  /**
   * @var string
   */
  protected $image_p8_filename;

  /**
   * @var string
   */
  protected $image_p9_filename;

  /**
   * @var string
   */
  protected $image_p1_directory;

  /**
   * @var string
   */
  protected $image_p2_directory;

  /**
   * @var string
   */
  protected $image_p3_directory;

  /**
   * @var string
   */
  protected $image_p4_directory;

  /**
   * @var string
   */
  protected $image_p5_directory;

  /**
   * @var string
   */
  protected $image_p6_directory;

  /**
   * @var string
   */
  protected $image_p7_directory;

  /**
   * @var string
   */
  protected $image_p8_directory;

  /**
   * @var string
   */
  protected $image_p9_directory;

  /**
   * @var string
   */
  protected $image_p1_caption;

  /**
   * @var string
   */
  protected $image_p2_caption;

  /**
   * @var string
   */
  protected $image_p3_caption;

  /**
   * @var string
   */
  protected $image_p4_caption;

  /**
   * @var string
   */
  protected $image_p5_caption;

  /**
   * @var string
   */
  protected $image_p6_caption;

  /**
   * @var string
   */
  protected $image_p7_caption;

  /**
   * @var string
   */
  protected $image_p8_caption;

  /**
   * @var string
   */
  protected $image_p9_caption;

  /**
   * @var int
   */
  protected $shippingdivision_id;

  /**
   * @var int
   */
  protected $weight;

  /**
   * @var int
   */
  protected $depth = 0;

  /**
   * @var int
   */
  protected $width = 0;

  /**
   * @var int
   */
  protected $height = 0;

  /**
   * @var string
   */
  protected $person = '';

  /**
   * @var boolean
   */
  protected $price_unlinked_check;

  /**
   * @var boolean
   */
  protected $manual_guerrilla_sale;

  /**
   * Set daihyoSyohinCode
   *
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
   * Set ne_directory_id
   *
   * @return TbMainproducts
   */
  public function setNeDirectoryId($neDirectoryId)
  {
    $this->ne_directory_id = $neDirectoryId;

    return $this;
  }

  /**
   * Get ne_directory_id
   *
   * @return string
   */
  public function getNeDirectoryId()
  {
    return $this->ne_directory_id;
  }


  /**
   * Set yahoo_directory_id
   *
   * @return TbMainproducts
   */
  public function setYahooDirectoryId($yahooDirectoryId)
  {
    $this->yahoo_directory_id = $yahooDirectoryId;

    return $this;
  }

  /**
   * Get yahoo_directory_id
   *
   * @return string
   */
  public function getYahooDirectoryId()
  {
    return $this->yahoo_directory_id;
  }

  /**
   * Set imageP1Address
   *
   * @param string $imageP1Address
   *
   * @return TbMainproducts
   */
  public function setImageP1Address($imageP1Address)
  {
    $this->image_p1_address = $imageP1Address;

    return $this;
  }

  /**
   * Get imageP1Address
   *
   * @return string
   */
  public function getImageP1Address()
  {
    return $this->image_p1_address;
  }

  /**
   * Set imageP2Address
   *
   * @param string $imageP2Address
   *
   * @return TbMainproducts
   */
  public function setImageP2Address($imageP2Address)
  {
    $this->image_p2_address = $imageP2Address;

    return $this;
  }

  /**
   * Get imageP2Address
   *
   * @return string
   */
  public function getImageP2Address()
  {
    return $this->image_p2_address;
  }

  /**
   * Set imageP3Address
   *
   * @param string $imageP3Address
   *
   * @return TbMainproducts
   */
  public function setImageP3Address($imageP3Address)
  {
    $this->image_p3_address = $imageP3Address;

    return $this;
  }

  /**
   * Get imageP3Address
   *
   * @return string
   */
  public function getImageP3Address()
  {
    return $this->image_p3_address;
  }

  /**
   * Set imageP4Address
   *
   * @param string $imageP4Address
   *
   * @return TbMainproducts
   */
  public function setImageP4Address($imageP4Address)
  {
    $this->image_p4_address = $imageP4Address;

    return $this;
  }

  /**
   * Get imageP4Address
   *
   * @return string
   */
  public function getImageP4Address()
  {
    return $this->image_p4_address;
  }

  /**
   * Set imageP5Address
   *
   * @param string $imageP5Address
   *
   * @return TbMainproducts
   */
  public function setImageP5Address($imageP5Address)
  {
    $this->image_p5_address = $imageP5Address;

    return $this;
  }

  /**
   * Get imageP5Address
   *
   * @return string
   */
  public function getImageP5Address()
  {
    return $this->image_p5_address;
  }

  /**
   * Set imageP6Address
   *
   * @param string $imageP6Address
   *
   * @return TbMainproducts
   */
  public function setImageP6Address($imageP6Address)
  {
    $this->image_p6_address = $imageP6Address;

    return $this;
  }

  /**
   * Get imageP6Address
   *
   * @return string
   */
  public function getImageP6Address()
  {
    return $this->image_p6_address;
  }

  /**
   * Set imageP7Address
   *
   * @param string $imageP7Address
   *
   * @return TbMainproducts
   */
  public function setImageP7Address($imageP7Address)
  {
    $this->image_p7_address = $imageP7Address;

    return $this;
  }

  /**
   * Get imageP7Address
   *
   * @return string
   */
  public function getImageP7Address()
  {
    return $this->image_p7_address;
  }

  /**
   * Set imageP8Address
   *
   * @param string $imageP8Address
   *
   * @return TbMainproducts
   */
  public function setImageP8Address($imageP8Address)
  {
    $this->image_p8_address = $imageP8Address;

    return $this;
  }

  /**
   * Get imageP8Address
   *
   * @return string
   */
  public function getImageP8Address()
  {
    return $this->image_p8_address;
  }

  /**
   * Set imageP9Address
   *
   * @param string $imageP9Address
   *
   * @return TbMainproducts
   */
  public function setImageP9Address($imageP9Address)
  {
    $this->image_p9_address = $imageP9Address;

    return $this;
  }

  /**
   * Get imageP9Address
   *
   * @return string
   */
  public function getImageP9Address()
  {
    return $this->image_p9_address;
  }

  /**
   * Set imageP1Filename
   *
   * @param string $imageP1Filename
   *
   * @return TbMainproducts
   */
  public function setImageP1Filename($imageP1Filename)
  {
    $this->image_p1_filename = $imageP1Filename;

    return $this;
  }

  /**
   * Get imageP1Filename
   *
   * @return string
   */
  public function getImageP1Filename()
  {
    return $this->image_p1_filename;
  }

  /**
   * Set imageP2Filename
   *
   * @param string $imageP2Filename
   *
   * @return TbMainproducts
   */
  public function setImageP2Filename($imageP2Filename)
  {
    $this->image_p2_filename = $imageP2Filename;

    return $this;
  }

  /**
   * Get imageP2Filename
   *
   * @return string
   */
  public function getImageP2Filename()
  {
    return $this->image_p2_filename;
  }

  /**
   * Set imageP3Filename
   *
   * @param string $imageP3Filename
   *
   * @return TbMainproducts
   */
  public function setImageP3Filename($imageP3Filename)
  {
    $this->image_p3_filename = $imageP3Filename;

    return $this;
  }

  /**
   * Get imageP3Filename
   *
   * @return string
   */
  public function getImageP3Filename()
  {
    return $this->image_p3_filename;
  }

  /**
   * Set imageP4Filename
   *
   * @param string $imageP4Filename
   *
   * @return TbMainproducts
   */
  public function setImageP4Filename($imageP4Filename)
  {
    $this->image_p4_filename = $imageP4Filename;

    return $this;
  }

  /**
   * Get imageP4Filename
   *
   * @return string
   */
  public function getImageP4Filename()
  {
    return $this->image_p4_filename;
  }

  /**
   * Set imageP5Filename
   *
   * @param string $imageP5Filename
   *
   * @return TbMainproducts
   */
  public function setImageP5Filename($imageP5Filename)
  {
    $this->image_p5_filename = $imageP5Filename;

    return $this;
  }

  /**
   * Get imageP5Filename
   *
   * @return string
   */
  public function getImageP5Filename()
  {
    return $this->image_p5_filename;
  }

  /**
   * Set imageP6Filename
   *
   * @param string $imageP6Filename
   *
   * @return TbMainproducts
   */
  public function setImageP6Filename($imageP6Filename)
  {
    $this->image_p6_filename = $imageP6Filename;

    return $this;
  }

  /**
   * Get imageP6Filename
   *
   * @return string
   */
  public function getImageP6Filename()
  {
    return $this->image_p6_filename;
  }

  /**
   * Set imageP7Filename
   *
   * @param string $imageP7Filename
   *
   * @return TbMainproducts
   */
  public function setImageP7Filename($imageP7Filename)
  {
    $this->image_p7_filename = $imageP7Filename;

    return $this;
  }

  /**
   * Get imageP7Filename
   *
   * @return string
   */
  public function getImageP7Filename()
  {
    return $this->image_p7_filename;
  }

  /**
   * Set imageP8Filename
   *
   * @param string $imageP8Filename
   *
   * @return TbMainproducts
   */
  public function setImageP8Filename($imageP8Filename)
  {
    $this->image_p8_filename = $imageP8Filename;

    return $this;
  }

  /**
   * Get imageP8Filename
   *
   * @return string
   */
  public function getImageP8Filename()
  {
    return $this->image_p8_filename;
  }

  /**
   * Set imageP9Filename
   *
   * @param string $imageP9Filename
   *
   * @return TbMainproducts
   */
  public function setImageP9Filename($imageP9Filename)
  {
    $this->image_p9_filename = $imageP9Filename;

    return $this;
  }

  /**
   * Get imageP9Filename
   *
   * @return string
   */
  public function getImageP9Filename()
  {
    return $this->image_p9_filename;
  }

  /**
   * Set imageP1Directory
   *
   * @param string $imageP1Directory
   *
   * @return TbMainproducts
   */
  public function setImageP1Directory($imageP1Directory)
  {
    $this->image_p1_directory = $imageP1Directory;

    return $this;
  }

  /**
   * Get imageP1Directory
   *
   * @return string
   */
  public function getImageP1Directory()
  {
    return $this->image_p1_directory;
  }

  /**
   * Set imageP2Directory
   *
   * @param string $imageP2Directory
   *
   * @return TbMainproducts
   */
  public function setImageP2Directory($imageP2Directory)
  {
    $this->image_p2_directory = $imageP2Directory;

    return $this;
  }

  /**
   * Get imageP2Directory
   *
   * @return string
   */
  public function getImageP2Directory()
  {
    return $this->image_p2_directory;
  }

  /**
   * Set imageP3Directory
   *
   * @param string $imageP3Directory
   *
   * @return TbMainproducts
   */
  public function setImageP3Directory($imageP3Directory)
  {
    $this->image_p3_directory = $imageP3Directory;

    return $this;
  }

  /**
   * Get imageP3Directory
   *
   * @return string
   */
  public function getImageP3Directory()
  {
    return $this->image_p3_directory;
  }

  /**
   * Set imageP4Directory
   *
   * @param string $imageP4Directory
   *
   * @return TbMainproducts
   */
  public function setImageP4Directory($imageP4Directory)
  {
    $this->image_p4_directory = $imageP4Directory;

    return $this;
  }

  /**
   * Get imageP4Directory
   *
   * @return string
   */
  public function getImageP4Directory()
  {
    return $this->image_p4_directory;
  }

  /**
   * Set imageP5Directory
   *
   * @param string $imageP5Directory
   *
   * @return TbMainproducts
   */
  public function setImageP5Directory($imageP5Directory)
  {
    $this->image_p5_directory = $imageP5Directory;

    return $this;
  }

  /**
   * Get imageP5Directory
   *
   * @return string
   */
  public function getImageP5Directory()
  {
    return $this->image_p5_directory;
  }

  /**
   * Set imageP6Directory
   *
   * @param string $imageP6Directory
   *
   * @return TbMainproducts
   */
  public function setImageP6Directory($imageP6Directory)
  {
    $this->image_p6_directory = $imageP6Directory;

    return $this;
  }

  /**
   * Get imageP6Directory
   *
   * @return string
   */
  public function getImageP6Directory()
  {
    return $this->image_p6_directory;
  }

  /**
   * Set imageP7Directory
   *
   * @param string $imageP7Directory
   *
   * @return TbMainproducts
   */
  public function setImageP7Directory($imageP7Directory)
  {
    $this->image_p7_directory = $imageP7Directory;

    return $this;
  }

  /**
   * Get imageP7Directory
   *
   * @return string
   */
  public function getImageP7Directory()
  {
    return $this->image_p7_directory;
  }

  /**
   * Set imageP8Directory
   *
   * @param string $imageP8Directory
   *
   * @return TbMainproducts
   */
  public function setImageP8Directory($imageP8Directory)
  {
    $this->image_p8_directory = $imageP8Directory;

    return $this;
  }

  /**
   * Get imageP8Directory
   *
   * @return string
   */
  public function getImageP8Directory()
  {
    return $this->image_p8_directory;
  }

  /**
   * Set imageP9Directory
   *
   * @param string $imageP9Directory
   *
   * @return TbMainproducts
   */
  public function setImageP9Directory($imageP9Directory)
  {
    $this->image_p9_directory = $imageP9Directory;

    return $this;
  }

  /**
   * Get imageP9Directory
   *
   * @return string
   */
  public function getImageP9Directory()
  {
    return $this->image_p9_directory;
  }

  /**
   * Set imageP1Caption
   *
   * @param string $imageP1Caption
   *
   * @return TbMainproducts
   */
  public function setImageP1Caption($imageP1Caption)
  {
    $this->image_p1_caption = $imageP1Caption;

    return $this;
  }

  /**
   * Get imageP1Caption
   *
   * @return string
   */
  public function getImageP1Caption()
  {
    return $this->image_p1_caption;
  }

  /**
   * Set imageP2Caption
   *
   * @param string $imageP2Caption
   *
   * @return TbMainproducts
   */
  public function setImageP2Caption($imageP2Caption)
  {
    $this->image_p2_caption = $imageP2Caption;

    return $this;
  }

  /**
   * Get imageP2Caption
   *
   * @return string
   */
  public function getImageP2Caption()
  {
    return $this->image_p2_caption;
  }

  /**
   * Set imageP3Caption
   *
   * @param string $imageP3Caption
   *
   * @return TbMainproducts
   */
  public function setImageP3Caption($imageP3Caption)
  {
    $this->image_p3_caption = $imageP3Caption;

    return $this;
  }

  /**
   * Get imageP3Caption
   *
   * @return string
   */
  public function getImageP3Caption()
  {
    return $this->image_p3_caption;
  }

  /**
   * Set imageP4Caption
   *
   * @param string $imageP4Caption
   *
   * @return TbMainproducts
   */
  public function setImageP4Caption($imageP4Caption)
  {
    $this->image_p4_caption = $imageP4Caption;

    return $this;
  }

  /**
   * Get imageP4Caption
   *
   * @return string
   */
  public function getImageP4Caption()
  {
    return $this->image_p4_caption;
  }

  /**
   * Set imageP5Caption
   *
   * @param string $imageP5Caption
   *
   * @return TbMainproducts
   */
  public function setImageP5Caption($imageP5Caption)
  {
    $this->image_p5_caption = $imageP5Caption;

    return $this;
  }

  /**
   * Get imageP5Caption
   *
   * @return string
   */
  public function getImageP5Caption()
  {
    return $this->image_p5_caption;
  }

  /**
   * Set imageP6Caption
   *
   * @param string $imageP6Caption
   *
   * @return TbMainproducts
   */
  public function setImageP6Caption($imageP6Caption)
  {
    $this->image_p6_caption = $imageP6Caption;

    return $this;
  }

  /**
   * Get imageP6Caption
   *
   * @return string
   */
  public function getImageP6Caption()
  {
    return $this->image_p6_caption;
  }

  /**
   * Set imageP7Caption
   *
   * @param string $imageP7Caption
   *
   * @return TbMainproducts
   */
  public function setImageP7Caption($imageP7Caption)
  {
    $this->image_p7_caption = $imageP7Caption;

    return $this;
  }

  /**
   * Get imageP7Caption
   *
   * @return string
   */
  public function getImageP7Caption()
  {
    return $this->image_p7_caption;
  }

  /**
   * Set imageP8Caption
   *
   * @param string $imageP8Caption
   *
   * @return TbMainproducts
   */
  public function setImageP8Caption($imageP8Caption)
  {
    $this->image_p8_caption = $imageP8Caption;

    return $this;
  }

  /**
   * Get imageP8Caption
   *
   * @return string
   */
  public function getImageP8Caption()
  {
    return $this->image_p8_caption;
  }

  /**
   * Set imageP9Caption
   *
   * @param string $imageP9Caption
   *
   * @return TbMainproducts
   */
  public function setImageP9Caption($imageP9Caption)
  {
    $this->image_p9_caption = $imageP9Caption;

    return $this;
  }

  /**
   * Get imageP9Caption
   *
   * @return string
   */
  public function getImageP9Caption()
  {
    return $this->image_p9_caption;
  }


  /**
   * Set priceUnlinkedCheck
   *
   * @param boolean $priceUnlinkedCheck
   *
   * @return TbMainproducts
   */
  public function setPriceUnlinkedCheck($priceUnlinkedCheck)
  {
    $this->price_unlinked_check = $priceUnlinkedCheck;

    return $this;
  }

  /**
   * Get priceUnlinkedCheck
   *
   * @return boolean
   */
  public function getPriceUnlinkedCheck()
  {
    return $this->price_unlinked_check;
  }

  /**
   * Set manualGuerrillaSale
   *
   * @param boolean $manualGuerrillaSale
   *
   * @return TbMainproducts
   */
  public function setManualGuerrillaSale($manualGuerrillaSale)
  {
    $this->manual_guerrilla_sale = $manualGuerrillaSale;

    return $this;
  }

  /**
   * Get manualGuerrillaSale
   *
   * @return boolean
   */
  public function getManualGuerrillaSale()
  {
    return $this->manual_guerrilla_sale;
  }


  /**
   * @var string
   */
  protected $syohin_kbn = '10';

  /**
   * @var int
   */
  protected $genka_tnk;

  /**
   * @var string
   */
  protected $daihyo_syohin_name;

  /**
   * @var string
   */
  protected $sire_name;

  /**
   * @var string
   */
  protected $col_type_name;

  /**
   * @var string
   */
  protected $row_type_name;

  /**
   * @var string
   */
  protected $col_type;

  /**
   * @var string
   */
  protected $row_type;


  /**
   * Set syohinKbn
   *
   * @param string $syohinKbn
   *
   * @return TbMainproducts
   */
  public function setSyohinKbn($syohinKbn)
  {
    $this->syohin_kbn = $syohinKbn;

    return $this;
  }

  /**
   * Get syohinKbn
   *
   * @return string
   */
  public function getSyohinKbn()
  {
    return $this->syohin_kbn;
  }

  /**
   * Set genkaTnk
   *
   * @param int $genkaTnk
   *
   * @return TbMainproducts
   */
  public function setGenkaTnk($genkaTnk)
  {
    $this->genka_tnk = $genkaTnk;

    return $this;
  }

  /**
   * Get genkaTnk
   *
   * @return int
   */
  public function getGenkaTnk()
  {
    return $this->genka_tnk;
  }

  /**
   * Set daihyoSyohinName
   *
   * @param string $daihyoSyohinName
   *
   * @return TbMainproducts
   */
  public function setDaihyoSyohinName($daihyoSyohinName)
  {
    $this->daihyo_syohin_name = $daihyoSyohinName;

    return $this;
  }

  /**
   * Get daihyoSyohinName
   *
   * @return string
   */
  public function getDaihyoSyohinName()
  {
    return $this->daihyo_syohin_name;
  }

  /**
   * Set sireName
   *
   * @param string $sireName
   *
   * @return TbMainproducts
   */
  public function setSireName($sireName)
  {
    $this->sire_name = $sireName;

    return $this;
  }

  /**
   * Get sireName
   *
   * @return string
   */
  public function getSireName()
  {
    return $this->sire_name;
  }

  /**
   * Set colTypeName
   *
   * @param string $colTypeName
   *
   * @return TbMainproducts
   */
  public function setColTypeName($colTypeName)
  {
    $this->col_type_name = $colTypeName;

    return $this;
  }

  /**
   * Get colTypeName
   *
   * @return string
   */
  public function getColTypeName()
  {
    return $this->col_type_name;
  }

  /**
   * Set rowTypeName
   *
   * @param string $rowTypeName
   *
   * @return TbMainproducts
   */
  public function setRowTypeName($rowTypeName)
  {
    $this->row_type_name = $rowTypeName;

    return $this;
  }

  /**
   * Get rowTypeName
   *
   * @return string
   */
  public function getRowTypeName()
  {
    return $this->row_type_name;
  }

  /**
   * Set colType
   *
   * @param string $colType
   *
   * @return TbMainproducts
   */
  public function setColType($colType)
  {
    $this->col_type = $colType;

    return $this;
  }

  /**
   * Get colType
   *
   * @return string
   */
  public function getColType()
  {
    return $this->col_type;
  }

  /**
   * Set rowType
   *
   * @param string $rowType
   *
   * @return TbMainproducts
   */
  public function setRowType($rowType)
  {
    $this->row_type = $rowType;

    return $this;
  }

  /**
   * Get rowType
   *
   * @return string
   */
  public function getRowType()
  {
    return $this->row_type;
  }

  /**
   * Set cal
   *
   * @param \MiscBundle\Entity\TbMainproductsCal $cal
   *
   * @return TbMainproducts
   */
  public function setCal(\MiscBundle\Entity\TbMainproductsCal $cal = null)
  {
    $this->cal = $cal;

    return $this;
  }

  /**
   * Add choiceItem
   *
   * @param \MiscBundle\Entity\TbProductchoiceitems $choiceItem
   *
   * @return TbMainproducts
   */
  public function addChoiceItem(\MiscBundle\Entity\TbProductchoiceitems $choiceItem)
  {
    $this->choiceItems[] = $choiceItem;

    return $this;
  }

  /**
   * Remove choiceItem
   *
   * @param \MiscBundle\Entity\TbProductchoiceitems $choiceItem
   *
   * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
   */
  public function removeChoiceItem(\MiscBundle\Entity\TbProductchoiceitems $choiceItem)
  {
    return $this->choiceItems->removeElement($choiceItem);
  }

  /**
   * Set english
   *
   * @param \MiscBundle\Entity\TbMainproductsEnglish $english
   *
   * @return TbMainproducts
   */
  public function setEnglish(\MiscBundle\Entity\TbMainproductsEnglish $english = null)
  {
    $this->english = $english;

    return $this;
  }

  /**
   * @var string
   */
  protected $sire_code;


  /**
   * Set sireCode
   *
   * @param string $sireCode
   *
   * @return TbMainproducts
   */
  public function setSireCode($sireCode)
  {
    $this->sire_code = $sireCode;

    return $this;
  }

  /**
   * Get sireCode
   *
   * @return string
   */
  public function getSireCode()
  {
    return $this->sire_code;
  }

  /**
   * Set vendor
   *
   * @param \MiscBundle\Entity\TbVendormasterdata $vendor
   *
   * @return TbMainproducts
   */
  public function setVendor(\MiscBundle\Entity\TbVendormasterdata $vendor = null)
  {
    $this->vendor = $vendor;

    return $this;
  }

  /**
   * Get vendor
   *
   * @return \MiscBundle\Entity\TbVendormasterdata
   */
  public function getVendor()
  {
    return $this->vendor;
  }

  /**
   * @var string
   */
  protected $order_comment;

  /**
   * Set orderComment
   *
   * @param string $orderComment
   *
   * @return TbMainproducts
   */
  public function setOrderComment($orderComment)
  {
    $this->order_comment = $orderComment;

    return $this;
  }

  /**
   * Get orderComment
   *
   * @return string
   */
  public function getOrderComment()
  {
    return $this->order_comment;
  }


  /**
   * Get shippingdivision_id
   *
   * @return int
   */
  public function getShippingdivisionId()
  {
    return $this->shippingdivision_id;
  }

  /**
   * Set weight
   *
   * @param $weight
   * @return TbMainproducts
   */
  public function setWeight($weight)
  {
    $this->weight = $weight;

    return $this;
  }

  /**
   * Get weight
   *
   * @return string
   */
  public function getWeight()
  {
    return $this->weight;
  }

  /**
   * Set depth
   *
   * @param $depth
   * @return TbMainproducts
   */
  public function setDepth($depth)
  {
    $this->depth = $depth;

    return $this;
  }

  /**
   * Get depth
   *
   * @return string
   */
  public function getDepth()
  {
    return $this->depth;
  }

  /**
   * Set width
   *
   * @param $width
   * @return TbMainproducts
   */
  public function setWidth($width)
  {
    $this->width = $width;

    return $this;
  }

  /**
   * Get width
   *
   * @return string
   */
  public function getWidth()
  {
    return $this->width;
  }

  /**
   * Set height
   *
   * @param $height
   * @return TbMainproducts
   */
  public function setHeight($height)
  {
    $this->height = $height;

    return $this;
  }

  /**
   * Get height
   *
   * @return string
   */
  public function getHeight()
  {
    return $this->height;
  }

  /**
   * 登録日時
   */
  /**
   * @var \DateTime
   */
  protected $registered_datetime;

  /**
   * Setter
   *
   * @param \DateTime $registered_datetime
   *
   * @return TbMainproductsCal
   */
  public function setRegisteredDatetime($registered_datetime)
  {
    $this->registered_datetime = $registered_datetime;

    return $this;
  }

  /**
   * Getter
   *
   * @return \DateTime
   */
  public function getRegisteredDatetime()
  {
    return $this->registered_datetime;
  }

  /**
   * 販売開始日
   */
  /**
   * @var \DateTime
   */
  protected $sale_start_date;

  /**
   * Setter
   *
   * @param \DateTime $sale_start_date
   *
   * @return TbMainproductsCal
   */
  public function setSaleStartDate($sale_start_date)
  {
    $this->sale_start_date = $sale_start_date;

    return $this;
  }

  /**
   * Getter
   *
   * @return \DateTime
   */
  public function getSaleStartDate()
  {
    return $this->sale_start_date;
  }


  /**
   * @var string
   */
  protected $color_axis = '';


  /**
   * Set colorAxis
   *
   * @param string $colorAxis
   *
   * @return MappedSuperClassTbMainproducts
   */
  public function setColorAxis($colorAxis)
  {
    $this->color_axis = $colorAxis;

    return $this;
  }

  /**
   * Get colorAxis
   *
   * @return string
   */
  public function getColorAxis()
  {
    return $this->color_axis;
  }

  /**
   * Set person
   *
   * @param string $person
   *
   * @return MappedSuperClassTbMainproducts
   */
  public function setPerson($person)
  {
    $this->person = $person;

    return $this;
  }

  /**
   * Get colorAxis
   *
   * @return string
   */
  public function getPerson()
  {
    return $this->person;
  }


  // ----------------------------------
  // 各種文言カラム
  // ----------------------------------
  private $description;
  private $about_size;
  private $about_color;
  private $about_material;
  private $about_brand;
  private $usage_note;
  private $supplemental_explanation;
  private $short_description;
  private $short_supplemental_explanation;

  public function setDescription($description)
  {
    $this->description = $description;
    return $this;
  }

  public function setAboutSize($aboutSize)
  {
    $this->about_size = $aboutSize;
    return $this;
  }

  public function setAboutColor($aboutColor)
  {
    $this->about_color = $aboutColor;
    return $this;
  }

  public function setAboutMaterial($aboutMaterial)
  {
    $this->about_material = $aboutMaterial;
    return $this;
  }

  public function setAboutBrand($aboutBrand)
  {
    $this->about_brand = $aboutBrand;
    return $this;
  }

  public function setUsageNote($usageNote)
  {
    $this->usage_note = $usageNote;
    return $this;
  }

  public function setSupplementalExplanation($supplementalExplanation)
  {
    $this->supplemental_explanation = $supplementalExplanation;
    return $this;
  }

  public function setShortDescription($shortDescription)
  {
    $this->short_description = $shortDescription;
    return $this;
  }

  public function setShortSupplementalExplanation($shortSupplementalExplanation)
  {
    $this->short_supplemental_explanation = $shortSupplementalExplanation;
    return $this;
  }

  public function getDescription()
  {
    return $this->description;
  }

  public function getAboutSize()
  {
    return $this->about_size;
  }

  public function getAboutColor()
  {
    return $this->about_color;
  }

  public function getAboutMaterial()
  {
    return $this->about_material;
  }

  public function getAboutBrand()
  {
    return $this->about_brand;
  }

  public function getUsageNote()
  {
    return $this->usage_note;
  }

  public function getSupplementalExplanation()
  {
    return $this->supplemental_explanation;
  }

  public function getShortDescription()
  {
    return $this->short_description;
  }

  public function getShortSupplementalExplanation()
  {
    return $this->short_supplemental_explanation;
  }

  /**
   * @var integer
   */
  protected $set_flg = 0;

  public function setSetFlg($setFlg)
  {
    return $this->set_flg = $setFlg;
  }

  public function getSetFlg()
  {
    return $this->set_flg;
  }

  /**
   * @var varchar
   */
  protected $company_code;

  /**
   * Set company_code
   *
   * @param $company_code
   * @return TbMainproducts
   */
  public function setCompanyCode($company_code)
  {
    $this->company_code = $company_code;

    return $this;
  }

  /**
   * Get company_code
   *
   * @return string
   */
  public function getCompanyCode()
  {
    return $this->company_code;
  }
}
