<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * Tb1688Company
 */
class Tb1688Company
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $member_id;

  /**
   * @var string
   */
  private $company_name = '';

  /**
   * @var string
   */
  private $url = '';

  /**
   * @var string
   */
  private $company_category_info;

  /**
   * @var string
   */
  private $company_name_en = '';

  /**
   * @var string
   */
  private $production_service = '';

  /**
   * @var string
   */
  private $legal_status = '';

  /**
   * @var string
   */
  private $biz_place = '';

  /**
   * @var string
   */
  private $biz_model = '';

  /**
   * @var string
   */
  private $profile;

  /**
   * @var string
   */
  private $sire_code = '';

  /**
   * @var integer
   */
  private $registration_stop = 0;

  /**
   * @var integer
   */
  private $check_stop = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set member_id
   *
   * @param string $memberId
   * @return Tb1688Company
   */
  public function setMemberId($memberId)
  {
    $this->member_id = $memberId;

    return $this;
  }

  /**
   * Get member_id
   *
   * @return string 
   */
  public function getMemberId()
  {
    return $this->member_id;
  }

  /**
   * Set company_name
   *
   * @param string $companyName
   * @return Tb1688Company
   */
  public function setCompanyName($companyName)
  {
    $this->company_name = $companyName;

    return $this;
  }

  /**
   * Get company_name
   *
   * @return string 
   */
  public function getCompanyName()
  {
    return $this->company_name;
  }

  /**
   * Set url
   *
   * @param string $url
   * @return Tb1688Company
   */
  public function setUrl($url)
  {
    $this->url = $url;

    return $this;
  }

  /**
   * Get url
   *
   * @return string 
   */
  public function getUrl()
  {
    return $this->url;
  }

  /**
   * Set company_category_info
   *
   * @param string $companyCategoryInfo
   * @return Tb1688Company
   */
  public function setCompanyCategoryInfo($companyCategoryInfo)
  {
    $this->company_category_info = $companyCategoryInfo;

    return $this;
  }

  /**
   * Get company_category_info
   *
   * @return string 
   */
  public function getCompanyCategoryInfo()
  {
    return $this->company_category_info;
  }

  /**
   * Set company_name_en
   *
   * @param string $companyNameEn
   * @return Tb1688Company
   */
  public function setCompanyNameEn($companyNameEn)
  {
    $this->company_name_en = $companyNameEn;

    return $this;
  }

  /**
   * Get company_name_en
   *
   * @return string 
   */
  public function getCompanyNameEn()
  {
    return $this->company_name_en;
  }

  /**
   * Set production_service
   *
   * @param string $productionService
   * @return Tb1688Company
   */
  public function setProductionService($productionService)
  {
    $this->production_service = $productionService;

    return $this;
  }

  /**
   * Get production_service
   *
   * @return string 
   */
  public function getProductionService()
  {
    return $this->production_service;
  }

  /**
   * Set legal_status
   *
   * @param string $legalStatus
   * @return Tb1688Company
   */
  public function setLegalStatus($legalStatus)
  {
    $this->legal_status = $legalStatus;

    return $this;
  }

  /**
   * Get legal_status
   *
   * @return string 
   */
  public function getLegalStatus()
  {
    return $this->legal_status;
  }

  /**
   * Set biz_place
   *
   * @param string $bizPlace
   * @return Tb1688Company
   */
  public function setBizPlace($bizPlace)
  {
    $this->biz_place = $bizPlace;

    return $this;
  }

  /**
   * Get biz_place
   *
   * @return string 
   */
  public function getBizPlace()
  {
    return $this->biz_place;
  }

  /**
   * Set biz_model
   *
   * @param string $bizModel
   * @return Tb1688Company
   */
  public function setBizModel($bizModel)
  {
    $this->biz_model = $bizModel;

    return $this;
  }

  /**
   * Get biz_model
   *
   * @return string 
   */
  public function getBizModel()
  {
    return $this->biz_model;
  }

  /**
   * Set profile
   *
   * @param string $profile
   * @return Tb1688Company
   */
  public function setProfile($profile)
  {
    $this->profile = $profile;

    return $this;
  }

  /**
   * Get profile
   *
   * @return string 
   */
  public function getProfile()
  {
    return $this->profile;
  }

  /**
   * Set sire_code
   *
   * @param string $sireCode
   * @return Tb1688Company
   */
  public function setSireCode($sireCode)
  {
    $this->sire_code = $sireCode;

    return $this;
  }

  /**
   * Get sire_code
   *
   * @return string 
   */
  public function getSireCode()
  {
    return $this->sire_code;
  }

  /**
   * Set registration_stop
   *
   * @param integer $registrationStop
   * @return Tb1688Company
   */
  public function setRegistrationStop($registrationStop)
  {
    $this->registration_stop = $registrationStop;

    return $this;
  }

  /**
   * Get registration_stop
   *
   * @return integer 
   */
  public function getRegistrationStop()
  {
    return $this->registration_stop;
  }

  /**
   * Set check_stop
   *
   * @param integer $checkStop
   * @return Tb1688Company
   */
  public function setCheckStop($checkStop)
  {
    $this->check_stop = $checkStop;

    return $this;
  }

  /**
   * Get check_stop
   *
   * @return integer 
   */
  public function getCheckStop()
  {
    return $this->check_stop;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return Tb1688Company
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
   * @return Tb1688Company
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
