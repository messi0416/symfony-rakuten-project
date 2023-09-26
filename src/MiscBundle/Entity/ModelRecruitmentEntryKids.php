<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ModelRecruitmentEntryKids
 */
class ModelRecruitmentEntryKids
{
  use FillTimestampTrait;

  /**
   * 画像ディレクトリ名作成
   */
  public function getImageDirName()
  {
    if (!$this->getId()) {
      throw new \RuntimeException('no id exists.');
    }

    return sprintf('kids%04d', $this->getId());
  }


  // ---------------------------------------
  // properties
  // ---------------------------------------
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $address;

  /**
   * @var string
   */
  private $phone;

  /**
   * @var string
   */
  private $mail;

  /**
   * @var integer
   */
  private $age_y;

  /**
   * @var integer
   */
  private $age_m;

  /**
   * @var integer
   */
  private $age_months;

  /**
   * @var integer
   */
  private $height;

  /**
   * @var integer
   */
  private $weight;

  /**
   * @var string
   */
  private $comment;

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
   * Set name
   *
   * @param string $name
   * @return ModelRecruitmentEntryKids
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
   * Set address
   *
   * @param string $address
   * @return ModelRecruitmentEntryKids
   */
  public function setAddress($address)
  {
    $this->address = $address;

    return $this;
  }

  /**
   * Get address
   *
   * @return string 
   */
  public function getAddress()
  {
    return $this->address;
  }

  /**
   * Set phone
   *
   * @param string $phone
   * @return ModelRecruitmentEntryKids
   */
  public function setPhone($phone)
  {
    $this->phone = $phone;

    return $this;
  }

  /**
   * Get phone
   *
   * @return string 
   */
  public function getPhone()
  {
    return $this->phone;
  }

  /**
   * Set mail
   *
   * @param string $mail
   * @return ModelRecruitmentEntryKids
   */
  public function setMail($mail)
  {
    $this->mail = $mail;

    return $this;
  }

  /**
   * Get mail
   *
   * @return string 
   */
  public function getMail()
  {
    return $this->mail;
  }

  /**
   * Set age_y
   *
   * @param integer $ageY
   * @return ModelRecruitmentEntryKids
   */
  public function setAgeY($ageY)
  {
    $this->age_y = $ageY;

    return $this;
  }

  /**
   * Get age_y
   *
   * @return integer 
   */
  public function getAgeY()
  {
    return $this->age_y;
  }

  /**
   * Set age_m
   *
   * @param integer $ageM
   * @return ModelRecruitmentEntryKids
   */
  public function setAgeM($ageM)
  {
    $this->age_m = $ageM;

    return $this;
  }

  /**
   * Get age_m
   *
   * @return integer 
   */
  public function getAgeM()
  {
    return $this->age_m;
  }

  /**
   * Set age_months
   *
   * @param integer $ageMonths
   * @return ModelRecruitmentEntryKids
   */
  public function setAgeMonths($ageMonths)
  {
    $this->age_months = $ageMonths;

    return $this;
  }

  /**
   * Get age_months
   *
   * @return integer 
   */
  public function getAgeMonths()
  {
    return $this->age_months;
  }

  /**
   * Set height
   *
   * @param integer $height
   * @return ModelRecruitmentEntryKids
   */
  public function setHeight($height)
  {
    $this->height = $height;

    return $this;
  }

  /**
   * Get height
   *
   * @return integer 
   */
  public function getHeight()
  {
    return $this->height;
  }

  /**
   * Set weight
   *
   * @param integer $weight
   * @return ModelRecruitmentEntryKids
   */
  public function setWeight($weight)
  {
    $this->weight = $weight;

    return $this;
  }

  /**
   * Get weight
   *
   * @return integer 
   */
  public function getWeight()
  {
    return $this->weight;
  }

  /**
   * Set comment
   *
   * @param string $comment
   * @return ModelRecruitmentEntryKids
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
   * Set created
   *
   * @param \DateTime $created
   * @return ModelRecruitmentEntryKids
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
   * @return ModelRecruitmentEntryKids
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
