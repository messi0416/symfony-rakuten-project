<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbShippingVoucher
 */
class TbShippingVoucher
{
  use ArrayTrait;
  use FillTimestampTrait;

  /** @var integer ステータス：ピッキング待ち */
  const STATUS_WAIT_PICKING = 0;

  /** @var integer ステータス：梱包未処理 */
  const STATUS_UNPROCESSED_PACKAGING = 1;

  /** @var integer ステータス：梱包中 */
  const STATUS_PACKING = 2;

  /** @var integer ステータス：完了 */
  const STATUS_FINISHED = 3;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var integer
   */
  private $shippingVoucherPackingGroupId;

  /**
   * @var integer
   */
  private $account = 0;

  /**
   * @var integer
   */
  private $status = 0;

  /**
   * @var \DateTime
   */
  private $imported;

  /**
   * @var integer
   */
  private $warehouse_id = 1;

  /**
   * @var integer
   */
  private $warehouse_daily_number = NULL;

  /**
   * @var \DateTime
   */
  private $picking_list_date;

  /**
   * @var integer
   */
  private $picking_list_number = 0;

  /**
   * @var integer
   */
  private $packingAccountId;

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
   * Set shippingVoucherPackingGroupId
   *
   * @param integer $shippingVoucherPackingGroupId
   * @return TbShippingVoucher
   */
  public function setShippingVoucherPackingGroupId($shippingVoucherPackingGroupId)
  {
    $this->shippingVoucherPackingGroupId = $shippingVoucherPackingGroupId;

    return $this;
  }

  /**
   * Get shippingVoucherPackingGroupId
   *
   * @return integer
   */
  public function getShippingVoucherPackingGroupId()
  {
    return $this->shippingVoucherPackingGroupId;
  }

  /**
   * Set account
   *
   * @param integer $account
   * @return TbShippingVoucher
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
   * Set status
   *
   * @param integer $status
   * @return TbShippingVoucher
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
   * Set imported
   *
   * @param \DateTime $imported
   * @return TbShippingVoucher
   */
  public function setImported($imported)
  {
    $this->imported = $imported;

    return $this;
  }

  /**
   * Get imported
   *
   * @return \DateTime 
   */
  public function getImported()
  {
    return $this->imported;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbShippingVoucher
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
   * @return TbShippingVoucher
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

  /**
   * Set picking_list_date
   *
   * @param \DateTime $pickingListDate
   * @return TbShippingVoucher
   */
  public function setPickingListDate($pickingListDate)
  {
    $this->picking_list_date = $pickingListDate;

    return $this;
  }

  /**
   * Get picking_list_date
   *
   * @return \DateTime 
   */
  public function getPickingListDate()
  {
    return $this->picking_list_date;
  }

  /**
   * Set picking_list_number
   *
   * @param integer $pickingListNumber
   * @return TbShippingVoucher
   */
  public function setPickingListNumber($pickingListNumber)
  {
    $this->picking_list_number = $pickingListNumber;

    return $this;
  }

  /**
   * Get picking_list_number
   *
   * @return integer 
   */
  public function getPickingListNumber()
  {
    return $this->picking_list_number;
  }

  /**
   * @var string
   */
  private $file_hash = '';

  /**
   * Set file_hash
   *
   * @param string $fileHash
   * @return TbShippingVoucher
   */
  public function setFileHash($fileHash)
  {
    $this->file_hash = $fileHash;

    return $this;
  }

  /**
   * Get file_hash
   *
   * @return string
   */
  public function getFileHash()
  {
    return $this->file_hash;
  }

  /**
   * Set warehouse_id
   *
   * @param integer $warehouseId
   * @return TbShippingVoucher
   */
  public function setWarehouseId($warehouseId)
  {
    $this->warehouse_id = $warehouseId;

    return $this;
  }

  /**
   * Get warehouse_id
   *
   * @return integer
   */
  public function getWarehouseId()
  {
    return $this->warehouse_id;
  }

  /**
   * Set warehouse_daily_number
   *
   * @param integer $warehouseDailyNumber
   * @return TbShippingVoucher
   */
  public function setWarehouseDailyNumber($warehouseDailyNumber)
  {
    $this->warehouse_daily_number = $warehouseDailyNumber;

    return $this;
  }

  /**
   * Get warehouse_daily_number
   *
   * @return integer
   */
  public function getWarehouseDailyNumber()
  {
    return $this->warehouse_daily_number;
  }

  /**
   * @var string
   */
  private $picking_block_pattern = '';


  /**
   * Set picking_block_pattern
   *
   * @param string $pickingBlockPattern
   * @return TbShippingVoucher
   */
  public function setPickingBlockPattern($pickingBlockPattern)
  {
    $this->picking_block_pattern = $pickingBlockPattern;

    return $this;
  }

  /**
   * Get picking_block_pattern
   *
   * @return string 
   */
  public function getPickingBlockPattern()
  {
    return $this->picking_block_pattern;
  }

  /**
   * Set $packingAccountId
   *
   * @param integer $packingAccountId
   * @return TbShippingVoucher
   */
  public function setPackingAccountId($packingAccountId)
  {
    $this->packingAccountId = $packingAccountId;

    return $this;
  }

  /**
   * Get $packingAccountId
   *
   * @return integer
   */
  public function getPackingAccountId()
  {
    return $this->packingAccountId;
  }

  /**
   * @var \DateTime
   */
  private $statement_downloaded;

  /**
   * @var \DateTime
   */
  private $label_downloaded;


  /**
   * Set statement_downloaded
   *
   * @param \DateTime $statementDownloaded
   * @return TbShippingVoucher
   */
  public function setStatementDownloaded($statementDownloaded)
  {
    $this->statement_downloaded = $statementDownloaded;

    return $this;
  }

  /**
   * Get statement_downloaded
   *
   * @return \DateTime 
   */
  public function getStatementDownloaded()
  {
    return $this->statement_downloaded;
  }

  /**
   * Set label_downloaded
   *
   * @param \DateTime $labelDownloaded
   * @return TbShippingVoucher
   */
  public function setLabelDownloaded($labelDownloaded)
  {
    $this->label_downloaded = $labelDownloaded;

    return $this;
  }

  /**
   * Get label_downloaded
   *
   * @return \DateTime 
   */
  public function getLabelDownloaded()
  {
    return $this->label_downloaded;
  }

  /**
   * ステータスの論理値を返す.
   * @param int $value ステータス
   * @return string ステータスの論理値
   */
  public function getDescription($value)
  {
    if (is_null($value)) {
      return "";
    }
    switch ($value) {
      case TbShippingVoucher::STATUS_WAIT_PICKING:
        return 'ピッキング待ち';
      case TbShippingVoucher::STATUS_UNPROCESSED_PACKAGING:
        return '梱包未処理';
      case TbShippingVoucher::STATUS_PACKING:
        return '梱包中';
      case TbShippingVoucher::STATUS_FINISHED:
        return '完了';
      default:
        return "";
    }
  }
}
