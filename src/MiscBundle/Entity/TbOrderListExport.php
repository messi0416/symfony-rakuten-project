<?php

namespace MiscBundle\Entity;

/**
 * TbOrderListExport
 */
class TbOrderListExport
{

  /** 出力ステータス：処理開始 */
  const EXPORT_STATUS_START  = 0;
  /** 出力ステータス：データ取得中 */
  const EXPORT_STATUS_FIND_DATA  = 1;
  /** 出力ステータス：Excelインスタンス生成中 */
  const EXPORT_STATUS_CREATE_EXCEL  = 2;
  /** 出力ステータス：Sheet1生成中 */
  const EXPORT_STATUS_CREATE_SHEET1  = 3;
  /** 出力ステータス：Sheet2生成中 */
  const EXPORT_STATUS_CREATE_SHEET2  = 4;
  /** 出力ステータス：Sheet3生成中 */
  const EXPORT_STATUS_CREATE_SHEET3  = 5;
  /** 出力ステータス：完了 */
  const EXPORT_STATUS_FINISH  = 6;
  /** 出力ステータス：エラー */
  const EXPORT_STATUS_ERROR  = 9;

    /**
   * @var integer
   */
    private $id;

    /**
   * @var integer
   */
    private $vendor;

    /**
     * @var integer
     */
    private $exportStatus;

    /**
   * @var integer
   */
    private $totalProducts;

    /**
   * @var integer
   */
    private $account;

    /**
   * @var integer
   */
    private $isForestStaff;

    /**
   * @var integer
   */
    private $isClient;

    /**
   * @var integer
   */
    private $isYahooAgent;

    /**
   * @var \DateTime
   */
    private $lastDownload;

   /**
   * @var string
   */
    private $file;

    /**
     * @var string
     */
    private $message;

    /**
   * @var \DateTime
   */
    private $created;

    /**
   * @var \DateTime
   */
    private $updated;

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
   * Set vendor
   *
   * @param integer $vendor
   * @return TbOrderListExport
   */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
    * Get vendor
    *
    * @return integer
    */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * Set export_status
     *
     * @param integer $exportStatus
     * @return TbOrderListExport
     */
    public function setExportStatus($exportStatus)
    {
      $this->exportStatus = $exportStatus;

      return $this;
    }

    /**
     * Get export_status
     *
     * @return integer
     */
    public function getExportStatus()
    {
      return $this->exportStatus;
    }

    /**
   * Set total_products
   *
   * @param integer $totalProducts
   * @return TbOrderListExport
   */
    public function setTotalProducts($totalProducts)
    {
        $this->totalProducts = $totalProducts;

        return $this;
    }

    /**
    * Get total_products
    *
    * @return integer
    */
    public function getTotalProducts()
    {
        return $this->totalProducts;
    }

    /**
   * Set account
   *
   * @param integer $account
   * @return TbOrderListExport
   */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
    * Get account
    *
    * @return integer
    */
    public function getAccount()
    {
        return $this->account;
    }

    /**
   * Set is_forest_staff
   *
   * @param integer $isClient
   * @return TbOrderListExport
   */
    public function setIsForestStaff($isForestStaff)
    {
        $this->isForestStaff = $isForestStaff;

        return $this;
    }

    /*
    * Get is_forest_staff
    *
    * @return integer
    */
    public function getIsForestStaff()
    {
        return $this->isForestStaff;
    }

    /**
   * Set is_client
   *
   * @param integer $isClient
   * @return TbOrderListExport
   */
    public function setIsClient($isClient)
    {
        $this->isClient = $isClient;

        return $this;
    }

    /**
    * Get is_client
    *
    * @return integer
    */
    public function getIsClient()
    {
        return $this->isClient;
    }

    /**
   * Set is_yahoo_agent
   *
   * @param integer $account
   * @return TbOrderListExport
   */
    public function setIsYahooAgent($isYahooAgent)
    {
        $this->isYahooAgent = $isYahooAgent;

        return $this;
    }

    /**
    * Get is_yahoo_agent
    *
    * @return integer
    */
    public function getIsYahooAgent()
    {
        return $this->isYahooAgent;
    }

    /**
   * Set last_download
   *
   * @param \DateTime $lastDownload
   * @return TbOrderListExport
   */
    public function setLastDownload($lastDownload)
    {
        $this->lastDownload = $lastDownload;

        return $this;
    }

    /**
    * Get last_download
    *
    * @return \DateTime
    */
    public function getLastDownload()
    {
        return $this->lastDownload;
    }

    /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbOrderListExport
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
   * Set file
   *
   * @param String $file
   * @return TbOrderListExport
   */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
    * Get file
    *
    * @return String
    */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set message
     *
     * @param String $message
     * @return TbOrderListExport
     */
    public function setMessage($message)
    {
      $this->message = $message;

      return $this;
    }

    /**
     * Get message
     *
     * @return String
     */
    public function getMessage()
    {
      return $this->message;
    }

    /**
    * Set updated
    *
    * @param \DateTime $updated
    * @return TbOrderListExport
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

    public function toArray()
  {
    return [
        'id' => $this->id
        , 'vendor' => $this->vendor
        , 'export_status' => $this->exportStatus
        , 'total_products' => $this->totalProducts
        , 'account' => $this->account
        , 'is_client' => $this->isClient
        , 'is_yahoo_agent' => $this->isYahooAgent
        , 'last_download' => $this->lastDownload
        , 'file' => $this->file
        , 'message' => $this->message
        , 'created' => $this->created ? $this->created->format('Y-m-d H:i:s') : null
        , 'updated' => $this->updated ? $this->updated->format('Y-m-d H:i:s') : null
    ];
  }
}
