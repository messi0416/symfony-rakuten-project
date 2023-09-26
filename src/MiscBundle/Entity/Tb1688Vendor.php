<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;

/**
 * Tb1688Vendor
 */
class Tb1688Vendor
{
  use ArrayTrait;
  
  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $code = '';

  /**
   * @var string
   */
  private $url = '';

  /**
   * @var string
   */
  private $sire_code = '';

  /**
   * @var integer
   */
  private $registration_available = -1;

  /**
   * @var integer
   */
  private $target_flag = -1;

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
   * Set code
   *
   * @param string $code
   * @return Tb1688Vendor
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
   * Set url
   *
   * @param string $url
   * @return Tb1688Vendor
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
   * Set sire_code
   *
   * @param string $sireCode
   * @return Tb1688Vendor
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
   * Set registration_available
   *
   * @param integer $registrationAvailable
   * @return Tb1688Vendor
   */
  public function setRegistrationAvailable($registrationAvailable)
  {
    $this->registration_available = $registrationAvailable;

    return $this;
  }

  /**
   * Get registration_available
   *
   * @return integer 
   */
  public function getRegistrationAvailable()
  {
    return $this->registration_available;
  }

  /**
   * Set target_flag
   *
   * @param integer $targetFlag
   * @return Tb1688Vendor
   */
  public function setTargetFlag($targetFlag)
  {
    $this->target_flag = $targetFlag;

    return $this;
  }

  /**
   * Get target_flag
   *
   * @return integer 
   */
  public function getTargetFlag()
  {
    return $this->target_flag;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return Tb1688Vendor
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
   * @return Tb1688Vendor
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
   * @ORM\PrePersist
   */
  public function fillTimestamps()
  {
    // Add your code here
  }
    /**
     * @var string
     */
    private $name = '';


    /**
     * Set name
     *
     * @param string $name
     * @return Tb1688Vendor
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
}
