<?php

namespace MiscBundle\Entity;

/**
 * TbRakutenReviews
 */
class TbRakutenReviews
{
  /**
   * 配列に変換
   */
  public function toArray()
  {
    return [
        'id'                  => $this->id
      , 'review_type'         => $this->review_type
      , 'product_name'        => $this->product_name
      , 'review_url'          => $this->review_url
      , 'point'               => $this->point
      , 'post_datetime'       => $this->post_datetime
      , 'title'               => $this->title
      , 'review'              => $this->review
      , 'flag'                => $this->flag
      , 'order_number'        => $this->order_number
      , 'daihyo_syohin_code'  => $this->daihyo_syohin_code
      , 'order_datetime'      => $this->order_datetime ? $this->order_datetime->format('Y/m/d H:i:s') : ''
    ];
  }


    /**
     * @var integer
     */
    private $id;


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
     * @var string
     */
    private $review_type;

    /**
     * @var string
     */
    private $product_name;

    /**
     * @var string
     */
    private $review_url;

    /**
     * @var integer
     */
    private $point = 0;

    /**
     * @var string
     */
    private $post_datetime;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $review;

    /**
     * @var string
     */
    private $flag;

    /**
     * @var string
     */
    private $order_number;

    /**
     * @var string
     */
    private $daihyo_syohin_code;

    /**
     * @var \DateTime
     */
    private $order_datetime;


    /**
     * Set reviewType
     *
     * @param string $reviewType
     *
     * @return TbRakutenReviews
     */
    public function setReviewType($reviewType)
    {
        $this->review_type = $reviewType;

        return $this;
    }

    /**
     * Get reviewType
     *
     * @return string
     */
    public function getReviewType()
    {
        return $this->review_type;
    }

    /**
     * Set productName
     *
     * @param string $productName
     *
     * @return TbRakutenReviews
     */
    public function setProductName($productName)
    {
        $this->product_name = $productName;

        return $this;
    }

    /**
     * Get productName
     *
     * @return string
     */
    public function getProductName()
    {
        return $this->product_name;
    }

    /**
     * Set reviewUrl
     *
     * @param string $reviewUrl
     *
     * @return TbRakutenReviews
     */
    public function setReviewUrl($reviewUrl)
    {
        $this->review_url = $reviewUrl;

        return $this;
    }

    /**
     * Get reviewUrl
     *
     * @return string
     */
    public function getReviewUrl()
    {
        return $this->review_url;
    }

    /**
     * Set point
     *
     * @param integer $point
     *
     * @return TbRakutenReviews
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point
     *
     * @return integer
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set postDatetime
     *
     * @param string $postDatetime
     *
     * @return TbRakutenReviews
     */
    public function setPostDatetime($postDatetime)
    {
        $this->post_datetime = $postDatetime;

        return $this;
    }

    /**
     * Get postDatetime
     *
     * @return string
     */
    public function getPostDatetime()
    {
        return $this->post_datetime;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return TbRakutenReviews
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set review
     *
     * @param string $review
     *
     * @return TbRakutenReviews
     */
    public function setReview($review)
    {
        $this->review = $review;

        return $this;
    }

    /**
     * Get review
     *
     * @return string
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set flag
     *
     * @param string $flag
     *
     * @return TbRakutenReviews
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * Get flag
     *
     * @return string
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * Set orderNumber
     *
     * @param string $orderNumber
     *
     * @return TbRakutenReviews
     */
    public function setOrderNumber($orderNumber)
    {
        $this->order_number = $orderNumber;

        return $this;
    }

    /**
     * Get orderNumber
     *
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     *
     * @return TbRakutenReviews
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyo_syohin_code = $daihyoSyohinCode;

        return $this;
    }

    /**
     * Get daihyoSyohinCode
     *
     * @return string
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyo_syohin_code;
    }

    /**
     * Set orderDatetime
     *
     * @param \DateTime $orderDatetime
     *
     * @return TbRakutenReviews
     */
    public function setOrderDatetime($orderDatetime)
    {
        $this->order_datetime = $orderDatetime;

        return $this;
    }

    /**
     * Get orderDatetime
     *
     * @return \DateTime
     */
    public function getOrderDatetime()
    {
        return $this->order_datetime;
    }
}
