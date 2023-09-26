<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ChouchouClairProduct
 */
class ChouchouClairProduct
{
  use ArrayTrait;
  use FillTimestampTrait;
  
  /**
   * @var string
   */
  private $code;

  /**
   * @var string
   */
  private $branch_code;

  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $catch_copy;

  /**
   * @var string
   */
  private $jan_code;

  /**
   * @var string
   */
  private $maker_code;

  /**
   * @var string
   */
  private $category;

  /**
   * @var string
   */
  private $start_date;

  /**
   * @var string
   */
  private $end_date;

  /**
   * @var string
   */
  private $shipping_condition;

  /**
   * @var string
   */
  private $return_flag;

  /**
   * @var string
   */
  private $size;

  /**
   * @var string
   */
  private $standard;

  /**
   * @var string
   */
  private $comment;

  /**
   * @var string
   */
  private $notes;

  /**
   * @var string
   */
  private $stamp;

  /**
   * @var string
   */
  private $style;

  /**
   * @var string
   */
  private $search_tag1;

  /**
   * @var string
   */
  private $search_tag2;

  /**
   * @var string
   */
  private $search_tag3;

  /**
   * @var string
   */
  private $search_tag4;

  /**
   * @var string
   */
  private $search_tag5;

  /**
   * @var string
   */
  private $image1;

  /**
   * @var string
   */
  private $image1_caption;

  /**
   * @var string
   */
  private $image2;

  /**
   * @var string
   */
  private $image2_caption;

  /**
   * @var string
   */
  private $image3;

  /**
   * @var string
   */
  private $image3_caption;

  /**
   * @var string
   */
  private $image4;

  /**
   * @var string
   */
  private $image4_caption;

  /**
   * @var string
   */
  private $image5;

  /**
   * @var string
   */
  private $image5_caption;

  /**
   * @var string
   */
  private $image6;

  /**
   * @var string
   */
  private $image6_caption;

  /**
   * @var string
   */
  private $image7;

  /**
   * @var string
   */
  private $image7_caption;

  /**
   * @var string
   */
  private $image8;

  /**
   * @var string
   */
  private $image8_caption;

  /**
   * @var string
   */
  private $image9;

  /**
   * @var string
   */
  private $image9_caption;

  /**
   * @var string
   */
  private $image10;

  /**
   * @var string
   */
  private $image10_caption;

  /**
   * @var int
   */
  private $row_num;

  /**
   * @var string
   */
  private $detail;

  /**
   * @var string
   */
  private $price_type;

  /**
   * @var string
   */
  private $retail_price;

  /**
   * @var int
   */
  private $wholesale_price;

  /**
   * @var int
   */
  private $set_num;

  /**
   * @var int
   */
  private $stock;

  /**
   * @var string
   */
  private $branch_delete_flag;

  /**
   * @var string
   */
  private $hide_price_flag;

  /**
   * @var string
   */
  private $available_flag;

  /**
   * @var string
   */
  private $sales_method;

  /**
   * @var string
   */
  private $sales_site;

  /**
   * @var string
   */
  private $keyword1;

  /**
   * @var string
   */
  private $keyword2;

  /**
   * @var string
   */
  private $keyword3;

  /**
   * @var string
   */
  private $brand_id;

  /**
   * @var string
   */
  private $discount_start_date;

  /**
   * @var string
   */
  private $discount_end_date;

  /**
   * @var string
   */
  private $discount_rate;

  /**
   * @var string
   */
  private $postage_type;

  /**
   * @var string
   */
  private $postage;

  /**
   * @var string
   */
  private $bulk_sale_flag;

  /**
   * @var string
   */
  private $reprint_permittion;

  /**
   * @var int
   */
  private $pre_stock;

  /**
   * @var \DateTime
   */
  private $stock_modified;

  /**
   * @var string
   */
  private $modified_user_type;

  /**
   * @var int
   */
  private $modified_user;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set code
   *
   * @param string $code
   *
   * @return ChouchouClairProduct
   */
  public function setCode($code)
  {
    $this->code = $code;

    return $this;
  }

  /**
   * Get code
   *
   * @return string
   */
  public function getCode()
  {
    return $this->code;
  }

  /**
   * Set branchCode
   *
   * @param string $branchCode
   *
   * @return ChouchouClairProduct
   */
  public function setBranchCode($branchCode)
  {
    $this->branch_code = $branchCode;

    return $this;
  }

  /**
   * Get branchCode
   *
   * @return string
   */
  public function getBranchCode()
  {
    return $this->branch_code;
  }

  /**
   * Set name
   *
   * @param string $name
   *
   * @return ChouchouClairProduct
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
   * Set catchCopy
   *
   * @param string $catchCopy
   *
   * @return ChouchouClairProduct
   */
  public function setCatchCopy($catchCopy)
  {
    $this->catch_copy = $catchCopy;

    return $this;
  }

  /**
   * Get catchCopy
   *
   * @return string
   */
  public function getCatchCopy()
  {
    return $this->catch_copy;
  }

  /**
   * Set janCode
   *
   * @param string $janCode
   *
   * @return ChouchouClairProduct
   */
  public function setJanCode($janCode)
  {
    $this->jan_code = $janCode;

    return $this;
  }

  /**
   * Get janCode
   *
   * @return string
   */
  public function getJanCode()
  {
    return $this->jan_code;
  }

  /**
   * Set makerCode
   *
   * @param string $makerCode
   *
   * @return ChouchouClairProduct
   */
  public function setMakerCode($makerCode)
  {
    $this->maker_code = $makerCode;

    return $this;
  }

  /**
   * Get makerCode
   *
   * @return string
   */
  public function getMakerCode()
  {
    return $this->maker_code;
  }

  /**
   * Set category
   *
   * @param string $category
   *
   * @return ChouchouClairProduct
   */
  public function setCategory($category)
  {
    $this->category = $category;

    return $this;
  }

  /**
   * Get category
   *
   * @return string
   */
  public function getCategory()
  {
    return $this->category;
  }

  /**
   * Set startDate
   *
   * @param string $startDate
   *
   * @return ChouchouClairProduct
   */
  public function setStartDate($startDate)
  {
    $this->start_date = $startDate;

    return $this;
  }

  /**
   * Get startDate
   *
   * @return string
   */
  public function getStartDate()
  {
    return $this->start_date;
  }

  /**
   * Set endDate
   *
   * @param string $endDate
   *
   * @return ChouchouClairProduct
   */
  public function setEndDate($endDate)
  {
    $this->end_date = $endDate;

    return $this;
  }

  /**
   * Get endDate
   *
   * @return string
   */
  public function getEndDate()
  {
    return $this->end_date;
  }

  /**
   * Set shippingCondition
   *
   * @param string $shippingCondition
   *
   * @return ChouchouClairProduct
   */
  public function setShippingCondition($shippingCondition)
  {
    $this->shipping_condition = $shippingCondition;

    return $this;
  }

  /**
   * Get shippingCondition
   *
   * @return string
   */
  public function getShippingCondition()
  {
    return $this->shipping_condition;
  }

  /**
   * Set returnFlag
   *
   * @param string $returnFlag
   *
   * @return ChouchouClairProduct
   */
  public function setReturnFlag($returnFlag)
  {
    $this->return_flag = $returnFlag;

    return $this;
  }

  /**
   * Get returnFlag
   *
   * @return string
   */
  public function getReturnFlag()
  {
    return $this->return_flag;
  }

  /**
   * Set size
   *
   * @param string $size
   *
   * @return ChouchouClairProduct
   */
  public function setSize($size)
  {
    $this->size = $size;

    return $this;
  }

  /**
   * Get size
   *
   * @return string
   */
  public function getSize()
  {
    return $this->size;
  }

  /**
   * Set standard
   *
   * @param string $standard
   *
   * @return ChouchouClairProduct
   */
  public function setStandard($standard)
  {
    $this->standard = $standard;

    return $this;
  }

  /**
   * Get standard
   *
   * @return string
   */
  public function getStandard()
  {
    return $this->standard;
  }

  /**
   * Set comment
   *
   * @param string $comment
   *
   * @return ChouchouClairProduct
   */
  public function setComment($comment)
  {
    $this->comment = $comment;

    return $this;
  }

  /**
   * Get comment
   *
   * @return string
   */
  public function getComment()
  {
    return $this->comment;
  }

  /**
   * Set notes
   *
   * @param string $notes
   *
   * @return ChouchouClairProduct
   */
  public function setNotes($notes)
  {
    $this->notes = $notes;

    return $this;
  }

  /**
   * Get notes
   *
   * @return string
   */
  public function getNotes()
  {
    return $this->notes;
  }

  /**
   * Set stamp
   *
   * @param string $stamp
   *
   * @return ChouchouClairProduct
   */
  public function setStamp($stamp)
  {
    $this->stamp = $stamp;

    return $this;
  }

  /**
   * Get stamp
   *
   * @return string
   */
  public function getStamp()
  {
    return $this->stamp;
  }

  /**
   * Set style
   *
   * @param string $style
   *
   * @return ChouchouClairProduct
   */
  public function setStyle($style)
  {
    $this->style = $style;

    return $this;
  }

  /**
   * Get style
   *
   * @return string
   */
  public function getStyle()
  {
    return $this->style;
  }

  /**
   * Set searchTag1
   *
   * @param string $searchTag1
   *
   * @return ChouchouClairProduct
   */
  public function setSearchTag1($searchTag1)
  {
    $this->search_tag1 = $searchTag1;

    return $this;
  }

  /**
   * Get searchTag1
   *
   * @return string
   */
  public function getSearchTag1()
  {
    return $this->search_tag1;
  }

  /**
   * Set searchTag2
   *
   * @param string $searchTag2
   *
   * @return ChouchouClairProduct
   */
  public function setSearchTag2($searchTag2)
  {
    $this->search_tag2 = $searchTag2;

    return $this;
  }

  /**
   * Get searchTag2
   *
   * @return string
   */
  public function getSearchTag2()
  {
    return $this->search_tag2;
  }

  /**
   * Set searchTag3
   *
   * @param string $searchTag3
   *
   * @return ChouchouClairProduct
   */
  public function setSearchTag3($searchTag3)
  {
    $this->search_tag3 = $searchTag3;

    return $this;
  }

  /**
   * Get searchTag3
   *
   * @return string
   */
  public function getSearchTag3()
  {
    return $this->search_tag3;
  }

  /**
   * Set searchTag4
   *
   * @param string $searchTag4
   *
   * @return ChouchouClairProduct
   */
  public function setSearchTag4($searchTag4)
  {
    $this->search_tag4 = $searchTag4;

    return $this;
  }

  /**
   * Get searchTag4
   *
   * @return string
   */
  public function getSearchTag4()
  {
    return $this->search_tag4;
  }

  /**
   * Set searchTag5
   *
   * @param string $searchTag5
   *
   * @return ChouchouClairProduct
   */
  public function setSearchTag5($searchTag5)
  {
    $this->search_tag5 = $searchTag5;

    return $this;
  }

  /**
   * Get searchTag5
   *
   * @return string
   */
  public function getSearchTag5()
  {
    return $this->search_tag5;
  }

  /**
   * Set image1
   *
   * @param string $image1
   *
   * @return ChouchouClairProduct
   */
  public function setImage1($image1)
  {
    $this->image1 = $image1;

    return $this;
  }

  /**
   * Get image1
   *
   * @return string
   */
  public function getImage1()
  {
    return $this->image1;
  }

  /**
   * Set image1Caption
   *
   * @param string $image1Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage1Caption($image1Caption)
  {
    $this->image1_caption = $image1Caption;

    return $this;
  }

  /**
   * Get image1Caption
   *
   * @return string
   */
  public function getImage1Caption()
  {
    return $this->image1_caption;
  }

  /**
   * Set image2
   *
   * @param string $image2
   *
   * @return ChouchouClairProduct
   */
  public function setImage2($image2)
  {
    $this->image2 = $image2;

    return $this;
  }

  /**
   * Get image2
   *
   * @return string
   */
  public function getImage2()
  {
    return $this->image2;
  }

  /**
   * Set image2Caption
   *
   * @param string $image2Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage2Caption($image2Caption)
  {
    $this->image2_caption = $image2Caption;

    return $this;
  }

  /**
   * Get image2Caption
   *
   * @return string
   */
  public function getImage2Caption()
  {
    return $this->image2_caption;
  }

  /**
   * Set image3
   *
   * @param string $image3
   *
   * @return ChouchouClairProduct
   */
  public function setImage3($image3)
  {
    $this->image3 = $image3;

    return $this;
  }

  /**
   * Get image3
   *
   * @return string
   */
  public function getImage3()
  {
    return $this->image3;
  }

  /**
   * Set image3Caption
   *
   * @param string $image3Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage3Caption($image3Caption)
  {
    $this->image3_caption = $image3Caption;

    return $this;
  }

  /**
   * Get image3Caption
   *
   * @return string
   */
  public function getImage3Caption()
  {
    return $this->image3_caption;
  }

  /**
   * Set image4
   *
   * @param string $image4
   *
   * @return ChouchouClairProduct
   */
  public function setImage4($image4)
  {
    $this->image4 = $image4;

    return $this;
  }

  /**
   * Get image4
   *
   * @return string
   */
  public function getImage4()
  {
    return $this->image4;
  }

  /**
   * Set image4Caption
   *
   * @param string $image4Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage4Caption($image4Caption)
  {
    $this->image4_caption = $image4Caption;

    return $this;
  }

  /**
   * Get image4Caption
   *
   * @return string
   */
  public function getImage4Caption()
  {
    return $this->image4_caption;
  }

  /**
   * Set image5
   *
   * @param string $image5
   *
   * @return ChouchouClairProduct
   */
  public function setImage5($image5)
  {
    $this->image5 = $image5;

    return $this;
  }

  /**
   * Get image5
   *
   * @return string
   */
  public function getImage5()
  {
    return $this->image5;
  }

  /**
   * Set image5Caption
   *
   * @param string $image5Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage5Caption($image5Caption)
  {
    $this->image5_caption = $image5Caption;

    return $this;
  }

  /**
   * Get image5Caption
   *
   * @return string
   */
  public function getImage5Caption()
  {
    return $this->image5_caption;
  }

  /**
   * Set image6
   *
   * @param string $image6
   *
   * @return ChouchouClairProduct
   */
  public function setImage6($image6)
  {
    $this->image6 = $image6;

    return $this;
  }

  /**
   * Get image6
   *
   * @return string
   */
  public function getImage6()
  {
    return $this->image6;
  }

  /**
   * Set image6Caption
   *
   * @param string $image6Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage6Caption($image6Caption)
  {
    $this->image6_caption = $image6Caption;

    return $this;
  }

  /**
   * Get image6Caption
   *
   * @return string
   */
  public function getImage6Caption()
  {
    return $this->image6_caption;
  }

  /**
   * Set image7
   *
   * @param string $image7
   *
   * @return ChouchouClairProduct
   */
  public function setImage7($image7)
  {
    $this->image7 = $image7;

    return $this;
  }

  /**
   * Get image7
   *
   * @return string
   */
  public function getImage7()
  {
    return $this->image7;
  }

  /**
   * Set image7Caption
   *
   * @param string $image7Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage7Caption($image7Caption)
  {
    $this->image7_caption = $image7Caption;

    return $this;
  }

  /**
   * Get image7Caption
   *
   * @return string
   */
  public function getImage7Caption()
  {
    return $this->image7_caption;
  }

  /**
   * Set image8
   *
   * @param string $image8
   *
   * @return ChouchouClairProduct
   */
  public function setImage8($image8)
  {
    $this->image8 = $image8;

    return $this;
  }

  /**
   * Get image8
   *
   * @return string
   */
  public function getImage8()
  {
    return $this->image8;
  }

  /**
   * Set image8Caption
   *
   * @param string $image8Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage8Caption($image8Caption)
  {
    $this->image8_caption = $image8Caption;

    return $this;
  }

  /**
   * Get image8Caption
   *
   * @return string
   */
  public function getImage8Caption()
  {
    return $this->image8_caption;
  }

  /**
   * Set image9
   *
   * @param string $image9
   *
   * @return ChouchouClairProduct
   */
  public function setImage9($image9)
  {
    $this->image9 = $image9;

    return $this;
  }

  /**
   * Get image9
   *
   * @return string
   */
  public function getImage9()
  {
    return $this->image9;
  }

  /**
   * Set image9Caption
   *
   * @param string $image9Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage9Caption($image9Caption)
  {
    $this->image9_caption = $image9Caption;

    return $this;
  }

  /**
   * Get image9Caption
   *
   * @return string
   */
  public function getImage9Caption()
  {
    return $this->image9_caption;
  }

  /**
   * Set image10
   *
   * @param string $image10
   *
   * @return ChouchouClairProduct
   */
  public function setImage10($image10)
  {
    $this->image10 = $image10;

    return $this;
  }

  /**
   * Get image10
   *
   * @return string
   */
  public function getImage10()
  {
    return $this->image10;
  }

  /**
   * Set image10Caption
   *
   * @param string $image10Caption
   *
   * @return ChouchouClairProduct
   */
  public function setImage10Caption($image10Caption)
  {
    $this->image10_caption = $image10Caption;

    return $this;
  }

  /**
   * Get image10Caption
   *
   * @return string
   */
  public function getImage10Caption()
  {
    return $this->image10_caption;
  }

  /**
   * Set rowNum
   *
   * @param int $rowNum
   *
   * @return ChouchouClairProduct
   */
  public function setRowNum($rowNum)
  {
    $this->row_num = $rowNum;

    return $this;
  }

  /**
   * Get rowNum
   *
   * @return int
   */
  public function getRowNum()
  {
    return $this->row_num;
  }

  /**
   * Set detail
   *
   * @param string $detail
   *
   * @return ChouchouClairProduct
   */
  public function setDetail($detail)
  {
    $this->detail = $detail;

    return $this;
  }

  /**
   * Get detail
   *
   * @return string
   */
  public function getDetail()
  {
    return $this->detail;
  }

  /**
   * Set priceType
   *
   * @param string $priceType
   *
   * @return ChouchouClairProduct
   */
  public function setPriceType($priceType)
  {
    $this->price_type = $priceType;

    return $this;
  }

  /**
   * Get priceType
   *
   * @return string
   */
  public function getPriceType()
  {
    return $this->price_type;
  }

  /**
   * Set retailPrice
   *
   * @param string $retailPrice
   *
   * @return ChouchouClairProduct
   */
  public function setRetailPrice($retailPrice)
  {
    $this->retail_price = $retailPrice;

    return $this;
  }

  /**
   * Get retailPrice
   *
   * @return string
   */
  public function getRetailPrice()
  {
    return $this->retail_price;
  }

  /**
   * Set wholesalePrice
   *
   * @param int $wholesalePrice
   *
   * @return ChouchouClairProduct
   */
  public function setWholesalePrice($wholesalePrice)
  {
    $this->wholesale_price = $wholesalePrice;

    return $this;
  }

  /**
   * Get wholesalePrice
   *
   * @return int
   */
  public function getWholesalePrice()
  {
    return $this->wholesale_price;
  }

  /**
   * Set setNum
   *
   * @param int $setNum
   *
   * @return ChouchouClairProduct
   */
  public function setSetNum($setNum)
  {
    $this->set_num = $setNum;

    return $this;
  }

  /**
   * Get setNum
   *
   * @return int
   */
  public function getSetNum()
  {
    return $this->set_num;
  }

  /**
   * Set stock
   *
   * @param int $stock
   *
   * @return ChouchouClairProduct
   */
  public function setStock($stock)
  {
    $this->stock = $stock;

    return $this;
  }

  /**
   * Get stock
   *
   * @return int
   */
  public function getStock()
  {
    return $this->stock;
  }

  /**
   * Set branchDeleteFlag
   *
   * @param string $branchDeleteFlag
   *
   * @return ChouchouClairProduct
   */
  public function setBranchDeleteFlag($branchDeleteFlag)
  {
    $this->branch_delete_flag = $branchDeleteFlag;

    return $this;
  }

  /**
   * Get branchDeleteFlag
   *
   * @return string
   */
  public function getBranchDeleteFlag()
  {
    return $this->branch_delete_flag;
  }

  /**
   * Set hidePriceFlag
   *
   * @param string $hidePriceFlag
   *
   * @return ChouchouClairProduct
   */
  public function setHidePriceFlag($hidePriceFlag)
  {
    $this->hide_price_flag = $hidePriceFlag;

    return $this;
  }

  /**
   * Get hidePriceFlag
   *
   * @return string
   */
  public function getHidePriceFlag()
  {
    return $this->hide_price_flag;
  }

  /**
   * Set availableFlag
   *
   * @param string $availableFlag
   *
   * @return ChouchouClairProduct
   */
  public function setAvailableFlag($availableFlag)
  {
    $this->available_flag = $availableFlag;

    return $this;
  }

  /**
   * Get availableFlag
   *
   * @return string
   */
  public function getAvailableFlag()
  {
    return $this->available_flag;
  }

  /**
   * Set salesMethod
   *
   * @param string $salesMethod
   *
   * @return ChouchouClairProduct
   */
  public function setSalesMethod($salesMethod)
  {
    $this->sales_method = $salesMethod;

    return $this;
  }

  /**
   * Get salesMethod
   *
   * @return string
   */
  public function getSalesMethod()
  {
    return $this->sales_method;
  }

  /**
   * Set salesSite
   *
   * @param string $salesSite
   *
   * @return ChouchouClairProduct
   */
  public function setSalesSite($salesSite)
  {
    $this->sales_site = $salesSite;

    return $this;
  }

  /**
   * Get salesSite
   *
   * @return string
   */
  public function getSalesSite()
  {
    return $this->sales_site;
  }

  /**
   * Set keyword1
   *
   * @param string $keyword1
   *
   * @return ChouchouClairProduct
   */
  public function setKeyword1($keyword1)
  {
    $this->keyword1 = $keyword1;

    return $this;
  }

  /**
   * Get keyword1
   *
   * @return string
   */
  public function getKeyword1()
  {
    return $this->keyword1;
  }

  /**
   * Set keyword2
   *
   * @param string $keyword2
   *
   * @return ChouchouClairProduct
   */
  public function setKeyword2($keyword2)
  {
    $this->keyword2 = $keyword2;

    return $this;
  }

  /**
   * Get keyword2
   *
   * @return string
   */
  public function getKeyword2()
  {
    return $this->keyword2;
  }

  /**
   * Set keyword3
   *
   * @param string $keyword3
   *
   * @return ChouchouClairProduct
   */
  public function setKeyword3($keyword3)
  {
    $this->keyword3 = $keyword3;

    return $this;
  }

  /**
   * Get keyword3
   *
   * @return string
   */
  public function getKeyword3()
  {
    return $this->keyword3;
  }

  /**
   * Set brandId
   *
   * @param string $brandId
   *
   * @return ChouchouClairProduct
   */
  public function setBrandId($brandId)
  {
    $this->brand_id = $brandId;

    return $this;
  }

  /**
   * Get brandId
   *
   * @return string
   */
  public function getBrandId()
  {
    return $this->brand_id;
  }

  /**
   * Set discountStartDate
   *
   * @param string $discountStartDate
   *
   * @return ChouchouClairProduct
   */
  public function setDiscountStartDate($discountStartDate)
  {
    $this->discount_start_date = $discountStartDate;

    return $this;
  }

  /**
   * Get discountStartDate
   *
   * @return string
   */
  public function getDiscountStartDate()
  {
    return $this->discount_start_date;
  }

  /**
   * Set discountEndDate
   *
   * @param string $discountEndDate
   *
   * @return ChouchouClairProduct
   */
  public function setDiscountEndDate($discountEndDate)
  {
    $this->discount_end_date = $discountEndDate;

    return $this;
  }

  /**
   * Get discountEndDate
   *
   * @return string
   */
  public function getDiscountEndDate()
  {
    return $this->discount_end_date;
  }

  /**
   * Set discountRate
   *
   * @param string $discountRate
   *
   * @return ChouchouClairProduct
   */
  public function setDiscountRate($discountRate)
  {
    $this->discount_rate = $discountRate;

    return $this;
  }

  /**
   * Get discountRate
   *
   * @return string
   */
  public function getDiscountRate()
  {
    return $this->discount_rate;
  }

  /**
   * Set postageType
   *
   * @param string $postageType
   *
   * @return ChouchouClairProduct
   */
  public function setPostageType($postageType)
  {
    $this->postage_type = $postageType;

    return $this;
  }

  /**
   * Get postageType
   *
   * @return string
   */
  public function getPostageType()
  {
    return $this->postage_type;
  }

  /**
   * Set postage
   *
   * @param string $postage
   *
   * @return ChouchouClairProduct
   */
  public function setPostage($postage)
  {
    $this->postage = $postage;

    return $this;
  }

  /**
   * Get postage
   *
   * @return string
   */
  public function getPostage()
  {
    return $this->postage;
  }

  /**
   * Set bulkSaleFlag
   *
   * @param string $bulkSaleFlag
   *
   * @return ChouchouClairProduct
   */
  public function setBulkSaleFlag($bulkSaleFlag)
  {
    $this->bulk_sale_flag = $bulkSaleFlag;

    return $this;
  }

  /**
   * Get bulkSaleFlag
   *
   * @return string
   */
  public function getBulkSaleFlag()
  {
    return $this->bulk_sale_flag;
  }

  /**
   * Set reprintPermittion
   *
   * @param string $reprintPermittion
   *
   * @return ChouchouClairProduct
   */
  public function setReprintPermittion($reprintPermittion)
  {
    $this->reprint_permittion = $reprintPermittion;

    return $this;
  }

  /**
   * Get reprintPermittion
   *
   * @return string
   */
  public function getReprintPermittion()
  {
    return $this->reprint_permittion;
  }

  /**
   * Set preStock
   *
   * @param int $preStock
   *
   * @return ChouchouClairProduct
   */
  public function setPreStock($preStock)
  {
    $this->pre_stock = $preStock;

    return $this;
  }

  /**
   * Get preStock
   *
   * @return int
   */
  public function getPreStock()
  {
    return $this->pre_stock;
  }

  /**
   * Set stockModified
   *
   * @param \DateTime $stockModified
   *
   * @return ChouchouClairProduct
   */
  public function setStockModified($stockModified)
  {
    $this->stock_modified = $stockModified;

    return $this;
  }

  /**
   * Get stockModified
   *
   * @return \DateTime
   */
  public function getStockModified()
  {
    return $this->stock_modified;
  }

  /**
   * Set modifiedUserType
   *
   * @param string $modifiedUserType
   *
   * @return ChouchouClairProduct
   */
  public function setModifiedUserType($modifiedUserType)
  {
    $this->modified_user_type = $modifiedUserType;

    return $this;
  }

  /**
   * Get modifiedUserType
   *
   * @return string
   */
  public function getModifiedUserType()
  {
    return $this->modified_user_type;
  }

  /**
   * Set modifiedUser
   *
   * @param int $modifiedUser
   *
   * @return ChouchouClairProduct
   */
  public function setModifiedUser($modifiedUser)
  {
    $this->modified_user = $modifiedUser;

    return $this;
  }

  /**
   * Get modifiedUser
   *
   * @return int
   */
  public function getModifiedUser()
  {
    return $this->modified_user;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return ChouchouClairProduct
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
   *
   * @return ChouchouClairProduct
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
