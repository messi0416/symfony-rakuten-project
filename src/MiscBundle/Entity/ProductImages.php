<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityInterface\ProductImagesInterface;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ProductImages
 */
class ProductImages implements ProductImagesInterface
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * 画像種別
   * @return string
   */
  public function getType()
  {
    return 'main';
  }

  /**
   * それぞれの画像ルート以下のディレクトリ+ファイル名のパスを取得
   * @return string
   */
  public function getFileDirPath()
  {
    return sprintf('%s/%s', $this->getDirectory(), $this->getFilename());
  }

  /**
   * Yahoo 仕様に沿ったファイル名か
   * ※ _1～_5 の順番までは気にしない
   */
  public function isValidYahooImageName()
  {
    $num = $this->getYahooImageNumber();
    $pattern = $num
      ? sprintf('/^%s_\d.jpg$/', preg_quote(strtolower($this->getDaihyoSyohinCode()), '/'))
      : sprintf('/^%s.jpg$/', preg_quote(strtolower($this->getDaihyoSyohinCode()), '/'));

    return (bool)preg_match($pattern, $this->getFilename());
  }

  /**
   * Yahoo 仕様のファイル名取得
   * p6以上は、実際にアップロードするとエラーにはなるが、名前を返す。
   */
  public function getYahooImageName()
  {
    $result = null;

    $num = $this->getYahooImageNumber();
    if (!$num) {
      $result = sprintf('%s.jpg', strtolower($this->getDaihyoSyohinCode()));
    } else {
      $result = sprintf('%s_%d.jpg', strtolower($this->getDaihyoSyohinCode()), $num);
    }

    return $result;
  }

  /**
   * Yahoo仕様の画像番号
   * p6以上は、実際にアップロードするとエラーにはなるが、番号は返す。
   * null: p1 , 1～8: p2～9
   */
  private function getYahooImageNumber()
  {
    if (preg_match('/^p(\d+)$/', $this->getCode(), $m)) {
      $num = intval($m[1]);
    } else {
      throw new \RuntimeException('no image code. can not create yahoo image number.');
    }

    $num = $num - 1;
    if ($num === 0) {
      $num = null;
    }

    return $num;
  }


  // ---------------------------------
  // setter, getter
  // ---------------------------------

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
   * @var string
   */
  private $md5hash = '';

  /**
   * @var string
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
   * @return ProductImages
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
   * @return ProductImages
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
   * @return ProductImages
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
   * @return ProductImages
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
   * @return ProductImages
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
   * @return ProductImages
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
   * Set md5hash
   *
   * @param string $md5hash
   *
   * @return ProductImages
   */
  public function setMd5hash($md5hash)
  {
    $this->md5hash = $md5hash;

    return $this;
  }

  /**
   * Get md5hash
   *
   * @return string
   */
  public function getMd5hash()
  {
    return $this->md5hash;
  }

  /**
   * Set created
   *
   * @param string $created
   *
   * @return ProductImages
   */
  public function setCreated($created)
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return string
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
   * @return ProductImages
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

  public function toArray()
  {
    return [
        'daihyo_syohin_code' => $this->daihyo_syohin_code,
        'address' => $this->address,
    ];
  }
}
