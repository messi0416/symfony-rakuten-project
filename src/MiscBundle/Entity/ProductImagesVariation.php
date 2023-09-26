<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityInterface\ProductImagesInterface;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ProductImagesVariation
 */
class ProductImagesVariation implements ProductImagesInterface
{
  use FillTimestampTrait;

  /**
   * 画像種別
   * @return string
   */
  public function getType()
  {
    return 'variation';
  }

  /**
   * それぞれの画像ルート以下のディレクトリ+ファイル名のパスを取得
   * @return string
   */
  public function getFileDirPath()
  {
    return sprintf('%s/%s', $this->getDirectory(), $this->getFilename());
  }

  // ------------------------

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
  private $variation_code;

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
   * @var int
   */
  private $color_image_id;

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
   * @return ProductImagesVariation
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
   * @return ProductImagesVariation
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
   * Set variationCode
   *
   * @param string $variationCode
   *
   * @return ProductImagesVariation
   */
  public function setVariationCode($variationCode)
  {
    $this->variation_code = $variationCode;

    return $this;
  }

  /**
   * Get variationCode
   *
   * @return string
   */
  public function getVariationCode()
  {
    return $this->variation_code;
  }

  /**
   * Set address
   *
   * @param string $address
   *
   * @return ProductImagesVariation
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
   * @return ProductImagesVariation
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
   * @return ProductImagesVariation
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
   * Set color_image_id
   *
   * @param string $colorImageId
   *
   * @return ProductImagesVariation
   */
  public function setColorImageId($colorImageId)
  {
    $this->color_image_id = $colorImageId;

    return $this;
  }

  /**
   * Get color_image_id
   *
   * @return string
   */
  public function getColorImageId()
  {
    return $this->color_image_id;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return ProductImagesVariation
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
   * @return ProductImagesVariation
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
