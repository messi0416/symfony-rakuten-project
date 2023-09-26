<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbMaintenanceSchedule
 */
class TbMaintenanceSchedule
{
  use ArrayTrait;
  use FillTimestampTrait;
  
  /** メンテナンス種別： Yahoo定期メンテナンス */
  const MAINTENANCE_TYPE_YAHOO_SCHEDULED = 1;
  
  /** メンテナンス種別文言 */
  const MAINTENANCE_NAME_YAHOO_SCHEDULED = "YahooFTP（定期メンテナンス）";
  
  /** メンテナンス文言リスト */
  const MAINTENANCE_TYPE_LIST = [
    self::MAINTENANCE_TYPE_YAHOO_SCHEDULED => self::MAINTENANCE_NAME_YAHOO_SCHEDULED
  ];


  /**
   * @var integer
   */
  private $id;

  /**
   * @var integer
   */
  private $maintenanceType;

  /**
   * @var \DateTime
   */
  private $startDatetime;

  /**
   * @var \DateTime
   */
  private $endDatetime;

  /**
   * @var string
   */
  private $note;

  /**
   * @var boolean
   */
  private $deleteFlg;

  /**
   * @var integer
   */
  private $updateAccountId;

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
   * Set maintenanceType
   *
   * @param integer $maintenanceType
   * @return TbMaintenanceSchedule
   */
  public function setMaintenanceType($maintenanceType)
  {
    $this->maintenanceType = $maintenanceType;

    return $this;
  }

  /**
   * Get maintenanceType
   *
   * @return integer 
   */
  public function getMaintenanceType()
  {
    return $this->maintenanceType;
  }

  /**
   * Set startDatetime
   *
   * @param \DateTime $startDatetime
   * @return TbMaintenanceSchedule
   */
  public function setStartDatetime($startDatetime)
  {
    $this->startDatetime = $startDatetime;

    return $this;
  }

  /**
   * Get startDatetime
   *
   * @return \DateTime 
   */
  public function getStartDatetime()
  {
    return $this->startDatetime;
  }

  /**
   * Set endDatetime
   *
   * @param \DateTime $endDatetime
   * @return TbMaintenanceSchedule
   */
  public function setEndDatetime($endDatetime)
  {
    $this->endDatetime = $endDatetime;

    return $this;
  }

  /**
   * Get endDatetime
   *
   * @return \DateTime 
   */
  public function getEndDatetime()
  {
    return $this->endDatetime;
  }

  /**
   * Set note
   *
   * @param string $note
   * @return TbMaintenanceSchedule
   */
  public function setNote($note)
  {
    $this->note = $note;

    return $this;
  }

  /**
   * Get note
   *
   * @return string 
   */
  public function getNote()
  {
    return $this->note;
  }

  /**
   * Set deleteFlg
   *
   * @param boolean $deleteFlg
   * @return TbMaintenanceSchedule
   */
  public function setDeleteFlg($deleteFlg)
  {
    $this->deleteFlg = $deleteFlg;

    return $this;
  }

  /**
   * Get deleteFlg
   *
   * @return boolean 
   */
  public function getDeleteFlg()
  {
    return $this->deleteFlg;
  }

  /**
   * Set updateAccountId
   *
   * @param integer $updateAccountId
   * @return TbMaintenanceSchedule
   */
  public function setUpdateAccountId($updateAccountId)
  {
    $this->updateAccountId = $updateAccountId;

    return $this;
  }

  /**
   * Get updateAccountId
   *
   * @return integer 
   */
  public function getUpdateAccountId()
  {
    return $this->updateAccountId;
  }

  /**
   * Set updated
   *
   * @param \DateTime $updated
   * @return TbMaintenanceSchedule
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
