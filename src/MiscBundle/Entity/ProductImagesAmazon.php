<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityInterface\ProductImagesInterface;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ProductImagesAmazon
 */
class ProductImagesAmazon implements ProductImagesInterface
{
  use FillTimestampTrait;

  /**
   * 画像種別
   * @return string
   */
  public function getType()
  {
    return 'amazon';
  }

  /**
   * それぞれの画像ルート以下のディレクトリ+ファイル名のパスを取得
   * @return string
   */
  public function getFileDirPath()
  {
    return sprintf('%s%s', (strlen($this->getDirectory()) ? ($this->getDirectory() . '/') : ''), $this->getFilename());
  }

  // ----------------------------------
  // properties
  // ----------------------------------

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $code;

  /**
   * @var string
   */
  private $address;

  /**
   * @var string
   */
  private $directory;

  /**
   * @var string
   */
  private $filename;

  /**
   * @var string
   */
  private $phash = '';

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   *
   * @return ProductImagesAmazon
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
   * Set code
   *
   * @param string $code
   *
   * @return ProductImagesAmazon
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
   * Set address
   *
   * @param string $address
   *
   * @return ProductImagesAmazon
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
   * Set directory
   *
   * @param string $directory
   *
   * @return ProductImagesAmazon
   */
  public function setDirectory($directory)
  {
    $this->directory = $directory;

    return $this;
  }

  /**
   * Get directory
   *
   * @return string
   */
  public function getDirectory()
  {
    return $this->directory;
  }

  /**
   * Set filename
   *
   * @param string $filename
   *
   * @return ProductImagesAmazon
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;

    return $this;
  }

  /**
   * Get filename
   *
   * @return string
   */
  public function getFilename()
  {
    return $this->filename;
  }

  /**
   * Set phash
   *
   * @param string $phash
   *
   * @return ProductImagesAmazon
   */
  public function setPhash($phash)
  {
    $this->phash = $phash;

    return $this;
  }

  /**
   * Get phash
   *
   * @return string
   */
  public function getPhash()
  {
    return $this->phash;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return ProductImagesAmazon
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
   * @return ProductImagesAmazon
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
