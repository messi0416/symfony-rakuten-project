<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TmpProductImages
 */
class TmpProductImages
{
  use FillTimestampTrait;


  /// 判定
  public function isDeleteFlgDeleted()
  {
    return $this->getDeleteFlg() != 0;
  }

  /// 判定
  public function isAmazonMain()
  {
    return $this->getImageCode() == 'amazonMain';
  }

  /// 判定
  public function isVariation()
  {
    return boolval(preg_match('/^-([a-zA-Z0-9-]*)$/', $this->getImageCode()));
  }

  // ---------------------------------------------
  // setter, getter
  // ---------------------------------------------

  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $image_key;

  /**
   * @var string
   */
  private $image_code;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $image;

  /**
   * @var int
   */
  private $delete_flg;

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
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set imageKey
   *
   * @param string $imageKey
   *
   * @return TmpProductImages
   */
  public function setImageKey($imageKey)
  {
    $this->image_key = $imageKey;

    return $this;
  }

  /**
   * Get imageKey
   *
   * @return string
   */
  public function getImageKey()
  {
    return $this->image_key;
  }

  /**
   * Set imageCode
   *
   * @param string $imageCode
   *
   * @return TmpProductImages
   */
  public function setImageCode($imageCode)
  {
    $this->image_code = $imageCode;

    return $this;
  }

  /**
   * Get imageCode
   *
   * @return string
   */
  public function getImageCode()
  {
    return $this->image_code;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return TmpProductImages
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
   * Set image
   *
   * @param string $image
   *
   * @return TmpProductImages
   */
  public function setImage($image)
  {
    $this->image = $image;

    return $this;
  }

  /**
   * Get image
   *
   * @return string
   */
  public function getImage()
  {
    return $this->image;
  }

  /**
   * Set deleteFlg
   *
   * @param int $deleteFlg
   *
   * @return TmpProductImages
   */
  public function setDeleteFlg($deleteFlg)
  {
    $this->delete_flg = $deleteFlg;

    return $this;
  }

  /**
   * Get deleteFlg
   *
   * @return int
   */
  public function getDeleteFlg()
  {
    return $this->delete_flg;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return TmpProductImages
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
   *
   * @return TmpProductImages
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
