<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;

/**
 * TbIndividualorderhistory
 */
class TbIndividualorderhistory
{
  use ArrayTrait;

  public function updateRemainStatusByStatusDates()
  {
    if ($this->getRemainStockoutDate()) {
      $this->setRemainStatus(TbIndividualorderhistoryRepository::REMAIN_STATUS_SHORTAGE);
    } else if ($this->getRemainShippingDate()) {
      $this->setRemainStatus(TbIndividualorderhistoryRepository::REMAIN_STATUS_SHIPPED);
    } else if ($this->getRemainWaitingDate()) {
      $this->setRemainStatus(TbIndividualorderhistoryRepository::REMAIN_STATUS_WAITED);
    } else if ($this->getRemainArrivedDate()) {
      $this->setRemainStatus(TbIndividualorderhistoryRepository::REMAIN_STATUS_ARRIVED);
    } else if ($this->getRemainOrderedDate()) {
      $this->setRemainStatus(TbIndividualorderhistoryRepository::REMAIN_STATUS_ORDERED);
    } else {
      $this->setRemainStatus(TbIndividualorderhistoryRepository::REMAIN_STATUS_UNTREATED);
    }

    return $this;
  }

  /**
   * 注残数 再計算
   * generated column 実装により削除予定
   */
//  public function recalculateRemainNum()
//  {
//    $remainNum = $this->getOrderNum()
//               - $this->getRegularNum()   /* 良品 */
//               - $this->getDefectiveNum() /* 暫定欠品 */
//               - $this->getShortageNum()  /* 欠品 */
//    ;
//    $this->setRemainNum($remainNum);
//  }

  // ===================================
  // properties
  // ===================================

  /**
   * @var int
   */
  private $shipping_type = TbIndividualorderhistoryRepository::SHIPPING_TYPE_AIR;

  /**
   * @var string
   */
  private $syohin_code;

  /**
   * @var int
   */
  private $order_num = '0';

  /**
   * @var int
   */
  private $remain_num = '0';

  /**
   * @var \DateTime
   */
  private $scheduled_date;

  /**
   * @var string
   */
  private $comment = '';

  /**
   * @var string
   */
  private $sire_code;

  /**
   * @var string
   */
  private $agent_code = '';

  /**
   * @var \DateTime
   */
  private $order_date;

  /**
   * @var string
   */
  private $remain_status = '';

  /**
   * @var \DateTime
   */
  private $remain_ordered_date;

  /**
   * @var \DateTime
   */
  private $remain_waiting_date;

  /**
   * @var \DateTime
   */
  private $remain_shipping_date;

  /**
   * @var \DateTime
   */
  private $remain_stockout_date;

  /**
   * @var string
   */
  private $shipping_number = '';
  
  /**
   * @var string
   */
  private $shipping_operation_number = '';
  
  /**
   * @var string
   */
  private $support_colname = '';

  /**
   * @var string
   */
  private $support_rowname = '';

  /**
   * 発送種別を設定する
   *
   * @param string $shippingType
   *
   * @return TbIndividualorderhistory
   */
  public function setShippingType($shippingType)
  {
    $this->shipping_type = $shippingType;

    return $this;
  }

  /**
   * 発送種別を取得する
   *
   * @return string
   */
  public function getShippingType()
  {
    return $this->shipping_type;
  }

  /**
   * Set syohinCode
   *
   * @param string $syohinCode
   *
   * @return TbIndividualorderhistory
   */
  public function setSyohinCode($syohinCode)
  {
    $this->syohin_code = $syohinCode;

    return $this;
  }

  /**
   * Get syohinCode
   *
   * @return string
   */
  public function getSyohinCode()
  {
    return $this->syohin_code;
  }

  /**
   * Set orderNum
   *
   * @param int $orderNum
   *
   * @return TbIndividualorderhistory
   */
  public function setOrderNum($orderNum)
  {
    $this->order_num = $orderNum;

    return $this;
  }

  /**
   * Get orderNum
   *
   * @return int
   */
  public function getOrderNum()
  {
    return $this->order_num;
  }

  /**
   * Set remainNum
   *
   * @param int $remainNum
   *
   * @return TbIndividualorderhistory
   */
  public function setRemainNum($remainNum)
  {
    $this->remain_num = $remainNum;

    return $this;
  }

  /**
   * Get remainNum
   *
   * @return int
   */
  public function getRemainNum()
  {
    return $this->remain_num;
  }

  /**
   * Set scheduledDate
   *
   * @param \DateTime $scheduledDate
   *
   * @return TbIndividualorderhistory
   */
  public function setScheduledDate($scheduledDate)
  {
    $this->scheduled_date = $scheduledDate;

    return $this;
  }

  /**
   * Get scheduledDate
   *
   * @return \DateTime
   */
  public function getScheduledDate()
  {
    return $this->scheduled_date;
  }

  /**
   * Set comment
   *
   * @param string $comment
   *
   * @return TbIndividualorderhistory
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
   * Set sireCode
   *
   * @param string $sireCode
   *
   * @return TbIndividualorderhistory
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
   * Set agentCode
   *
   * @param string $agentCode
   *
   * @return TbIndividualorderhistory
   */
  public function setAgentCode($agentCode)
  {
    $this->agent_code = $agentCode;

    return $this;
  }

  /**
   * Get agentCode
   *
   * @return string
   */
  public function getAgentCode()
  {
    return $this->agent_code;
  }

  /**
   * Set orderDate
   *
   * @param \DateTime $orderDate
   *
   * @return TbIndividualorderhistory
   */
  public function setOrderDate($orderDate)
  {
    $this->order_date = $orderDate;

    return $this;
  }

  /**
   * Get orderDate
   *
   * @return \DateTime
   */
  public function getOrderDate()
  {
    return $this->order_date;
  }

  /**
   * Set remainStatus
   *
   * @param string $remainStatus
   *
   * @return TbIndividualorderhistory
   */
  public function setRemainStatus($remainStatus)
  {
    $this->remain_status = $remainStatus;

    return $this;
  }

  /**
   * Get remainStatus
   *
   * @return string
   */
  public function getRemainStatus()
  {
    return $this->remain_status;
  }

  /**
   * Set remainOrderedDate
   *
   * @param \DateTime $remainOrderedDate
   *
   * @return TbIndividualorderhistory
   */
  public function setRemainOrderedDate($remainOrderedDate)
  {
    $this->remain_ordered_date = $remainOrderedDate;

    return $this;
  }

  /**
   * Get remainOrderedDate
   *
   * @return \DateTime
   */
  public function getRemainOrderedDate()
  {
    return $this->remain_ordered_date;
  }


  /**
   * Set remainWaitingDate
   *
   * @param \DateTime $remainWaitingDate
   *
   * @return TbIndividualorderhistory
   */
  public function setRemainWaitingDate($remainWaitingDate)
  {
    $this->remain_waiting_date = $remainWaitingDate;

    return $this;
  }

  /**
   * Get remainWaitingDate
   *
   * @return \DateTime
   */
  public function getRemainWaitingDate()
  {
    return $this->remain_waiting_date;
  }


  /**
   * Set remainShippingDate
   *
   * @param \DateTime $remainShippingDate
   *
   * @return TbIndividualorderhistory
   */
  public function setRemainShippingDate($remainShippingDate)
  {
    $this->remain_shipping_date = $remainShippingDate;

    return $this;
  }

  /**
   * Get remainShippingDate
   *
   * @return \DateTime
   */
  public function getRemainShippingDate()
  {
    return $this->remain_shipping_date;
  }

  /**
   * Set remainStockoutDate
   *
   * @param \DateTime $remainStockoutDate
   *
   * @return TbIndividualorderhistory
   */
  public function setRemainStockoutDate($remainStockoutDate)
  {
    $this->remain_stockout_date = $remainStockoutDate;

    return $this;
  }

  /**
   * Get remainStockoutDate
   *
   * @return \DateTime
   */
  public function getRemainStockoutDate()
  {
    return $this->remain_stockout_date;
  }

  /**
   * Set shippingNumber
   *
   * @param string $shippingNumber
   *
   * @return TbIndividualorderhistory
   */
  public function setShippingNumber($shippingNumber)
  {
    $this->shipping_number = $shippingNumber;

    return $this;
  }

  /**
   * Get shippingNumber
   *
   * @return string
   */
  public function getShippingNumber()
  {
    return $this->shipping_number;
  }

  /**
   * Get shippingOperationNumber
   *
   * @return string
   */
  public function getShippingOperationNumber()
  {
    return $this->shipping_operation_number;
  }

    /**
   * Set shippingOperationNumber
   *
   * @param string $shippingOperationNumber
   *
   * @return TbIndividualorderhistory
   */
  public function setShippingOperationNumber($shippingOperationNumber)
  {
    $this->shipping_operation_number = $shippingOperationNumber;

    return $this;
  }

  /**
   * Set supportColname
   *
   * @param string $supportColname
   *
   * @return TbIndividualorderhistory
   */
  public function setSupportColname($supportColname)
  {
    $this->support_colname = $supportColname;

    return $this;
  }

  /**
   * Get supportColname
   *
   * @return string
   */
  public function getSupportColname()
  {
    return $this->support_colname;
  }

  /**
   * Set supportRowname
   *
   * @param string $supportRowname
   *
   * @return TbIndividualorderhistory
   */
  public function setSupportRowname($supportRowname)
  {
    $this->support_rowname = $supportRowname;

    return $this;
  }

  /**
   * Get supportRowname
   *
   * @return string
   */
  public function getSupportRowname()
  {
    return $this->support_rowname;
  }

  /**
   * @var int
   */
  private $voucher_number;


  /**
   * Set voucherNumber
   *
   * @param int $voucherNumber
   *
   * @return TbIndividualorderhistory
   */
  public function setVoucherNumber($voucherNumber)
  {
    $this->voucher_number = $voucherNumber;

    return $this;
  }

  /**
   * Get voucherNumber
   *
   * @return int
   */
  public function getVoucherNumber()
  {
    return $this->voucher_number;
  }

  /**
   * @var \MiscBundle\Entity\TbProductChoiceItems
   */
  private $choiceItem;


  /**
   * Set choiceItem
   *
   * @param \MiscBundle\Entity\TbProductChoiceItems $choiceItem
   *
   * @return TbIndividualorderhistory
   */
  public function setChoiceItem(\MiscBundle\Entity\TbProductChoiceItems $choiceItem = null)
  {
    $this->choiceItem = $choiceItem;

    return $this;
  }

  /**
   * Get choiceItem
   *
   * @return \MiscBundle\Entity\TbProductChoiceItems
   */
  public function getChoiceItem()
  {
    return $this->choiceItem;
  }
    /**
     * @var string
     */
    private $vendor_comment;

    /**
     * @var \DateTime
     */
    private $vendor_comment_updated;

    /**
     * @var \DateTime
     */
    private $vendor_comment_processed;


    /**
     * Set vendorComment
     *
     * @param string $vendorComment
     *
     * @return TbIndividualorderhistory
     */
    public function setVendorComment($vendorComment)
    {
        $this->vendor_comment = $vendorComment;

        return $this;
    }

    /**
     * Get vendorComment
     *
     * @return string 
     */
    public function getVendorComment()
    {
        return $this->vendor_comment;
    }

    /**
     * Set vendorCommentUpdated
     *
     * @param \DateTime $vendorCommentUpdated
     *
     * @return TbIndividualorderhistory
     */
    public function setVendorCommentUpdated($vendorCommentUpdated)
    {
        $this->vendor_comment_updated = $vendorCommentUpdated;

        return $this;
    }

    /**
     * Get vendorCommentUpdated
     *
     * @return \DateTime
     */
    public function getVendorCommentUpdated()
    {
        return $this->vendor_comment_updated;
    }

    /**
     * Set vendorCommentProcessed
     *
     * @param \DateTime $vendorCommentProcessed
     *
     * @return TbIndividualorderhistory
     */
    public function setVendorCommentProcessed($vendorCommentProcessed)
    {
        $this->vendor_comment_processed = $vendorCommentProcessed;

        return $this;
    }

    /**
     * Get vendorCommentProcessed
     *
     * @return \DateTime
     */
    public function getVendorCommentProcessed()
    {
        return $this->vendor_comment_processed;
    }
    /**
     * @var \DateTime
     */
    private $remain_arrived_date;


    /**
     * Set remainArrivedDate
     *
     * @param \DateTime $remainArrivedDate
     *
     * @return TbIndividualorderhistory
     */
    public function setRemainArrivedDate($remainArrivedDate)
    {
        $this->remain_arrived_date = $remainArrivedDate;

        return $this;
    }

    /**
     * Get remainArrivedDate
     *
     * @return \DateTime
     */
    public function getRemainArrivedDate()
    {
        return $this->remain_arrived_date;
    }
    /**
     * @var int
     */
    private $id;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @var int
     */
    private $regularNum = '0';

    /**
     * @var int
     */
    private $defectiveNum = '0';

    /**
     * @var int
     */
    private $shortageNum = '0';


    /**
     * Set regularNum
     *
     * @param int $regularNum
     *
     * @return TbIndividualorderhistory
     */
    public function setRegularNum($regularNum)
    {
        $this->regularNum = $regularNum;

        return $this;
    }

    /**
     * Get regularNum
     *
     * @return int
     */
    public function getRegularNum()
    {
        return $this->regularNum;
    }

    /**
     * Set defectiveNum
     *
     * @param int $defectiveNum
     *
     * @return TbIndividualorderhistory
     */
    public function setDefectiveNum($defectiveNum)
    {
        $this->defectiveNum = $defectiveNum;

        return $this;
    }

    /**
     * Get defectiveNum
     *
     * @return int
     */
    public function getDefectiveNum()
    {
        return $this->defectiveNum;
    }

    /**
     * Set shortageNum
     *
     * @param int $shortageNum
     *
     * @return TbIndividualorderhistory
     */
    public function setShortageNum($shortageNum)
    {
        $this->shortageNum = $shortageNum;

        return $this;
    }

    /**
     * Get shortageNum
     *
     * @return int
     */
    public function getShortageNum()
    {
        return $this->shortageNum;
    }

    /**
     * @var string
     */
    private $remain_ordered_person = '';

    /**
     * @var string
     */
    private $remain_arrived_person = '';

    /**
     * @var string
     */
    private $remain_waiting_person = '';

    /**
     * @var string
     */
    private $remain_shipping_person = '';

    /**
     * @var string
     */
    private $remain_stockout_person = '';


    /**
     * Set remain_ordered_person
     *
     * @param string $remainOrderedPerson
     * @return TbIndividualorderhistory
     */
    public function setRemainOrderedPerson($remainOrderedPerson)
    {
        $this->remain_ordered_person = $remainOrderedPerson;

        return $this;
    }

    /**
     * Get remain_ordered_person
     *
     * @return string 
     */
    public function getRemainOrderedPerson()
    {
        return $this->remain_ordered_person;
    }

    /**
     * Set remain_arrived_person
     *
     * @param string $remainArrivedPerson
     * @return TbIndividualorderhistory
     */
    public function setRemainArrivedPerson($remainArrivedPerson)
    {
        $this->remain_arrived_person = $remainArrivedPerson;

        return $this;
    }

    /**
     * Get remain_arrived_person
     *
     * @return string 
     */
    public function getRemainArrivedPerson()
    {
        return $this->remain_arrived_person;
    }

    /**
     * Set remain_waiting_person
     *
     * @param string $remainWaitingPerson
     * @return TbIndividualorderhistory
     */
    public function setRemainWaitingPerson($remainWaitingPerson)
    {
        $this->remain_waiting_person = $remainWaitingPerson;

        return $this;
    }

    /**
     * Get remain_waiting_person
     *
     * @return string 
     */
    public function getRemainWaitingPerson()
    {
        return $this->remain_waiting_person;
    }

    /**
     * Set remain_shipping_person
     *
     * @param string $remainShippingPerson
     * @return TbIndividualorderhistory
     */
    public function setRemainShippingPerson($remainShippingPerson)
    {
        $this->remain_shipping_person = $remainShippingPerson;

        return $this;
    }

    /**
     * Get remain_shipping_person
     *
     * @return string 
     */
    public function getRemainShippingPerson()
    {
        return $this->remain_shipping_person;
    }

    /**
     * Set remain_stockout_person
     *
     * @param string $remainStockoutPerson
     * @return TbIndividualorderhistory
     */
    public function setRemainStockoutPerson($remainStockoutPerson)
    {
        $this->remain_stockout_person = $remainStockoutPerson;

        return $this;
    }

    /**
     * Get remain_stockout_person
     *
     * @return string 
     */
    public function getRemainStockoutPerson()
    {
        return $this->remain_stockout_person;
    }

    /**
     * @var string
     */
    private $receive_order_number = '';


    /**
     * Set receive_order_number
     *
     * @param string $receiveOrderNumber
     * @return TbIndividualorderhistory
     */
    public function setReceiveOrderNumber($receiveOrderNumber)
    {
        $this->receive_order_number = $receiveOrderNumber;

        return $this;
    }

    /**
     * Get receive_order_number
     *
     * @return string 
     */
    public function getReceiveOrderNumber()
    {
        return $this->receive_order_number;
    }
    /**
     * @var string
     */
    private $warehousing_number = '';


    /**
     * Set warehousing_number
     *
     * @param string $warehousingNumber
     * @return TbIndividualorderhistory
     */
    public function setWarehousingNumber($warehousingNumber)
    {
        $this->warehousing_number = $warehousingNumber;

        return $this;
    }

    /**
     * Get warehousing_number
     *
     * @return string 
     */
    public function getWarehousingNumber()
    {
        return $this->warehousing_number;
    }

    /**
     * @var integer
     */
    private $unallocated_flg = '0';

    /**
     * @var \DateTime
     */
    private $unallocated_flg_updated;

    /**
     * @var string
     */
    private $checklist_comment;

    /**
     * @var string
     */
    private $checklist_nw;

    /**
     * @var string
     */
    private $checklist_meas;

    /**
     * Set unallocated_flg
     *
     * @param integer $unallocatedFlg
     * @return TbIndividualorderhistory
     */
    public function setUnallocatedFlg($unallocatedFlg)
    {
        $this->unallocated_flg = $unallocatedFlg;

        return $this;
    }

    /**
     * Get unallocated_flg
     *
     * @return integer
     */
    public function getUnallocatedFlg()
    {
        return $this->unallocated_flg;
    }

    /**
     * Set unallocated_flg_updated
     *
     * @param \DateTime $unallocatedFlgUpdated
     * @return TbIndividualorderhistory
     */
    public function setUnallocatedFlgUpdated($unallocatedFlgUpdated)
    {
        $this->unallocated_flg_updated = $unallocatedFlgUpdated;

        return $this;
    }

    /**
     * Get unallocated_flg_updated
     *
     * @return \DateTime 
     */
    public function getUnallocatedFlgUpdated()
    {
        return $this->unallocated_flg_updated;
    }

    /**
     * Set checklist_comment
     *
     * @param string $checklistComment
     * @return TbIndividualorderhistory
     */
    public function setChecklistComment($checklistComment)
    {
        $this->checklist_comment = $checklistComment;

        return $this;
    }

    /**
     * Get checklist_comment
     *
     * @return string 
     */
    public function getChecklistComment()
    {
        return $this->checklist_comment;
    }

    /**
     * Set checklist_nw
     *
     * @param string $checklistNw
     * @return TbIndividualorderhistory
     */
    public function setChecklistNw($checklistNw)
    {
        $this->checklist_nw = $checklistNw;

        return $this;
    }

    /**
     * Get checklist_nw
     *
     * @return string 
     */
    public function getChecklistNw()
    {
        return $this->checklist_nw;
    }

    /**
     * Set checklist_meas
     *
     * @param string $checklistMeas
     * @return TbIndividualorderhistory
     */
    public function setChecklistMeas($checklistMeas)
    {
        $this->checklist_meas = $checklistMeas;

        return $this;
    }

    /**
     * Get checklist_meas
     *
     * @return string 
     */
    public function getChecklistMeas()
    {
        return $this->checklist_meas;
    }
}
