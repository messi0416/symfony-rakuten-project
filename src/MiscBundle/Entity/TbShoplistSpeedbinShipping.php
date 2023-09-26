<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbShoplistSpeedbinShipping
 */
class TbShoplistSpeedbinShipping
{
    use ArrayTrait;
    use FillTimestampTrait;
  
    /** ステータス: 未処理 */
    const STATUS_NONE = 0;
    /** ステータス: 集計中 */
    const STATUS_ONGOING = 1;
    /** ステータス: 集計完了 */
    const STATUS_DONE = 2;
    /** ステータス: 確定ファイル取込済 */
    const STATUS_IMPORTED = 3;
    /** ステータス: エラー */
    const STATUS_ERROR = 9;
    
    public static $STATUS_DISPLAYS = [
      self::STATUS_NONE => '未処理'
      , self::STATUS_ONGOING => '集計中'
      , self::STATUS_DONE => '集計完了'
      , self::STATUS_IMPORTED => '確定ファイル取込済'
      , self::STATUS_ERROR => 'エラー'
    ];
  
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $shoplistSalesLatestDate;

    /**
     * @var \DateTime
     */
    private $speedbinStockImportDatetime;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $shoplistOrderId;

    /**
     * @var integer
     */
    private $createUserId;

    /**
     * @var \DateTime
     */
    private $created;


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
     * Set shoplistSalesLatestDate
     *
     * @param \DateTime $shoplistSalesLatestDate
     * @return TbShoplistSpeedbinShipping
     */
    public function setShoplistSalesLatestDate($shoplistSalesLatestDate)
    {
        $this->shoplistSalesLatestDate = $shoplistSalesLatestDate;

        return $this;
    }

    /**
     * Get shoplistSalesLatestDate
     *
     * @return \DateTime 
     */
    public function getShoplistSalesLatestDate()
    {
        return $this->shoplistSalesLatestDate;
    }

    /**
     * Set speedbinStockImportDatetime
     *
     * @param \DateTime $speedbinStockImportDatetime
     * @return TbShoplistSpeedbinShipping
     */
    public function setSpeedbinStockImportDatetime($speedbinStockImportDatetime)
    {
        $this->speedbinStockImportDatetime = $speedbinStockImportDatetime;

        return $this;
    }

    /**
     * Get speedbinStockImportDatetime
     *
     * @return \DateTime 
     */
    public function getSpeedbinStockImportDatetime()
    {
        return $this->speedbinStockImportDatetime;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbShoplistSpeedbinShipping
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set shoplistOrderId
     *
     * @param integer $shoplistOrderId
     * @return TbShoplistSpeedbinShipping
     */
    public function setShoplistOrderId($shoplistOrderId)
    {
        $this->shoplistOrderId = $shoplistOrderId;

        return $this;
    }

    /**
     * Get shoplistOrderId
     *
     * @return integer 
     */
    public function getShoplistOrderId()
    {
        return $this->shoplistOrderId;
    }

    /**
     * Set createUserId
     *
     * @param integer $createUserId
     * @return TbShoplistSpeedbinShipping
     */
    public function setCreateUserId($createUserId)
    {
        $this->createUserId = $createUserId;

        return $this;
    }

    /**
     * Get createUserId
     *
     * @return integer 
     */
    public function getCreateUserId()
    {
        return $this->createUserId;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbShoplistSpeedbinShipping
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
}
