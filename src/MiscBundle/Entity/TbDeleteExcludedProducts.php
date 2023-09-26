<?php

namespace MiscBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * TbDeleteExcludedProducts
 */
class TbDeleteExcludedProducts
{
  /**
   * @Assert\Callback
   * @param ExecutionContextInterface $context
   */
  public function validateCategory(ExecutionContextInterface $context)
  {
    if (strlen($this->syohin_code)) {
      if (!preg_match('/^[a-zA-Z0-9-]+$/', $this->syohin_code)) {
        $context->buildViolation('商品コードに半角英数字以外が使われています。')
          ->atPath('syohin_code')
          ->addViolation()
        ;
      }
    }
  }



  // ========================================
  // getter, setter
  // ========================================
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $syohin_code;

    /**
     * @var int
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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set syohinCode
     *
     * @param string $syohinCode
     *
     * @return TbDeleteExcludedProducts
     */
    public function setSyohinCode($syohinCode)
    {
        $this->syohin_code = $syohinCode;

        return $this;
    }

    /**
     * Get syohinCode
     *
     * @return string
     */
    public function getSyohinCode()
    {
        return $this->syohin_code;
    }

    /**
     * Set displayOrder
     *
     * @param int $displayOrder
     *
     * @return TbDeleteExcludedProducts
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->display_order = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->display_order;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return TbDeleteExcludedProducts
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
     * @return TbDeleteExcludedProducts
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
     * @var string
     */
    private $comment;


    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return TbDeleteExcludedProducts
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
     * @var integer
     */
    private $mall_id;


    /**
     * Set mall_id
     *
     * @param integer $mallId
     * @return TbDeleteExcludedProducts
     */
    public function setMallId($mallId)
    {
        $this->mall_id = $mallId;

        return $this;
    }

    /**
     * Get mall_id
     *
     * @return integer 
     */
    public function getMallId()
    {
        return $this->mall_id;
    }
}
