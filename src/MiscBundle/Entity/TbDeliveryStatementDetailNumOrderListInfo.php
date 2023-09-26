<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * TbDeliveryStatementDetailNumOrderListInfo
 */
class TbDeliveryStatementDetailNumOrderListInfo
{
  use ArrayTrait;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $shipping_date;

  /**
   * @var integer
   */
  private $page_item_num = 0;

  /**
   * @var string
   */
  private $account_name = '';

  /**
   * @var integer
   */
  private $update_number = 0;

  /**
   * @var \DateTime
   */
  private $last_updated;


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
   * Set shipping_date
   *
   * @param \DateTime $shippingDate
   * @return TbDeliveryStatementDetailNumOrderListInfo
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
   * Set page_item_num
   *
   * @param integer $pageItemNum
   * @return TbDeliveryStatementDetailNumOrderListInfo
   */
  public function setPageItemNum($pageItemNum)
  {
    $this->page_item_num = $pageItemNum;

    return $this;
  }

  /**
   * Get page_item_num
   *
   * @return integer 
   */
  public function getPageItemNum()
  {
    return $this->page_item_num;
  }

  /**
   * Set account_name
   *
   * @param string $accountName
   * @return TbDeliveryStatementDetailNumOrderListInfo
   */
  public function setAccountName($accountName)
  {
    $this->account_name = $accountName;

    return $this;
  }

  /**
   * Get account_name
   *
   * @return string 
   */
  public function getAccountName()
  {
    return $this->account_name;
  }

  /**
   * Set update_number
   *
   * @param integer $updateNumber
   * @return TbDeliveryStatementDetailNumOrderListInfo
   */
  public function setUpdateNumber($updateNumber)
  {
    $this->update_number = $updateNumber;

    return $this;
  }

  /**
   * Get update_number
   *
   * @return integer 
   */
  public function getUpdateNumber()
  {
    return $this->update_number;
  }

  /**
   * Set last_updated
   *
   * @param \DateTime $lastUpdated
   * @return TbDeliveryStatementDetailNumOrderListInfo
   */
  public function setLastUpdated($lastUpdated)
  {
    $this->last_updated = $lastUpdated;

    return $this;
  }

  /**
   * Get last_updated
   *
   * @return \DateTime 
   */
  public function getLastUpdated()
  {
    return $this->last_updated;
  }
}
