<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbMixedProduct
 */
class TbMixedProduct
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $mall_code;

  /**
   * @var string
   */
  private $daihyo_syohin_code;

  /**
   * @var string
   */
  private $parent;

  /**
   * @var integer
   */
  private $display_order = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;

  /**
   * Set mall_code
   *
   * @param string $mallCode
   * @return TbMixedProduct
   */
  public function setMallCode($mallCode)
  {
    $this->mall_code = $mallCode;

    return $this;
  }

  /**
   * Get mall_code
   *
   * @return string
   */
  public function getMallCode()
  {
    return $this->mall_code;
  }

  /**
   * Set daihyo_syohin_code
   *
   * @param string $daihyoSyohinCode
   * @return TbMixedProduct
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyo_syohin_code
   *
   * @return string
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }

  /**
   * Set parent
   *
   * @param string $parent
   * @return TbMixedProduct
   */
  public function setParent($parent)
  {
    $this->parent = $parent;

    return $this;
  }

  /**
   * Get parent
   *
   * @return string
   */
  public function getParent()
  {
    return $this->parent;
  }

  /**
   * Set display_order
   *
   * @param integer $displayOrder
   * @return TbMixedProduct
   */
  public function setDisplayOrder($displayOrder)
  {
    $this->display_order = $displayOrder;

    return $this;
  }

  /**
   * Get display_order
   *
   * @return integer
   */
  public function getDisplayOrder()
  {
    return $this->display_order;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbMixedProduct
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
   * @return TbMixedProduct
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
