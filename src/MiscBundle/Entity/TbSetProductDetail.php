<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbSetProductDetail
 */
class TbSetProductDetail
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var string
   */
  private $set_ne_syohin_syohin_code;

  /**
   * @var string
   */
  private $ne_syohin_syohin_code;

  /**
   * @var integer
   */
  private $num = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set set_ne_syohin_syohin_code
   *
   * @param string $setNeSyohinSyohinCode
   * @return TbSetProductDetail
   */
  public function setSetNeSyohinSyohinCode($setNeSyohinSyohinCode)
  {
    $this->set_ne_syohin_syohin_code = $setNeSyohinSyohinCode;

    return $this;
  }

  /**
   * Get set_ne_syohin_syohin_code
   *
   * @return string 
   */
  public function getSetNeSyohinSyohinCode()
  {
    return $this->set_ne_syohin_syohin_code;
  }

  /**
   * Set ne_syohin_syohin_code
   *
   * @param string $neSyohinSyohinCode
   * @return TbSetProductDetail
   */
  public function setNeSyohinSyohinCode($neSyohinSyohinCode)
  {
    $this->ne_syohin_syohin_code = $neSyohinSyohinCode;

    return $this;
  }

  /**
   * Get ne_syohin_syohin_code
   *
   * @return string 
   */
  public function getNeSyohinSyohinCode()
  {
    return $this->ne_syohin_syohin_code;
  }

  /**
   * Set num
   *
   * @param integer $num
   * @return TbSetProductDetail
   */
  public function setNum($num)
  {
    $this->num = $num;

    return $this;
  }

  /**
   * Get num
   *
   * @return integer 
   */
  public function getNum()
  {
    return $this->num;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbSetProductDetail
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
   * @return TbSetProductDetail
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
   * @var \MiscBundle\Entity\TbProductchoiceitems
   */
  private $choiceItem;


  /**
   * Set choiceItem
   *
   * @param \MiscBundle\Entity\TbProductchoiceitems $choiceItem
   * @return TbSetProductDetail
   */
  public function setChoiceItem(\MiscBundle\Entity\TbProductchoiceitems $choiceItem = null)
  {
    $this->choiceItem = $choiceItem;

    return $this;
  }

  /**
   * Get choiceItem
   *
   * @return \MiscBundle\Entity\TbProductchoiceitems 
   */
  public function getChoiceItem()
  {
    return $this->choiceItem;
  }
}
