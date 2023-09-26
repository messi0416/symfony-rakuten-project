<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductReviews
 */
class TbProductReviews
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $neMallId;

    /**
     * @var string
     */
    private $originalReviewId;

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     */
    private $neSyohinSyohinCode;

    /**
     * @var integer
     */
    private $voucherNumber;

    /**
     * @var \DateTime
     */
    private $reviewDatetime;

    /**
     * @var integer
     */
    private $score;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $body;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var boolean
     */
    private $deleteFlg;


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
     * Set neMallId
     *
     * @param integer $neMallId
     * @return TbProductReviews
     */
    public function setNeMallId($neMallId)
    {
        $this->neMallId = $neMallId;

        return $this;
    }

    /**
     * Get neMallId
     *
     * @return integer
     */
    public function getNeMallId()
    {
        return $this->neMallId;
    }

    /**
     * Set originalReviewId
     *
     * @param string $originalReviewId
     * @return TbProductReviews
     */
    public function setOriginalReviewId($originalReviewId)
    {
        $this->originalReviewId = $originalReviewId;

        return $this;
    }

    /**
     * Get originalReviewId
     *
     * @return string
     */
    public function getOriginalReviewId()
    {
        return $this->originalReviewId;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbProductReviews
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyoSyohinCode = $daihyoSyohinCode;

        return $this;
    }

    /**
     * Get daihyoSyohinCode
     *
     * @return string
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyoSyohinCode;
    }

    /**
     * Set neSyohinSyohinCode
     *
     * @param string $neSyohinSyohinCode
     * @return TbProductReviews
     */
    public function setNeSyohinSyohinCode($neSyohinSyohinCode)
    {
        $this->neSyohinSyohinCode = $neSyohinSyohinCode;

        return $this;
    }

    /**
     * Get neSyohinSyohinCode
     *
     * @return string
     */
    public function getNeSyohinSyohinCode()
    {
        return $this->neSyohinSyohinCode;
    }

    /**
     * Set voucherNumber
     *
     * @param integer $voucherNumber
     * @return TbProductReviews
     */
    public function setVoucherNumber($voucherNumber)
    {
        $this->voucherNumber = $voucherNumber;

        return $this;
    }

    /**
     * Get voucherNumber
     *
     * @return integer
     */
    public function getVoucherNumber()
    {
        return $this->voucherNumber;
    }

    /**
     * Set reviewDatetime
     *
     * @param \DateTime $reviewDatetime
     * @return TbProductReviews
     */
    public function setReviewDatetime($reviewDatetime)
    {
        $this->reviewDatetime = $reviewDatetime;

        return $this;
    }

    /**
     * Get reviewDatetime
     *
     * @return \DateTime
     */
    public function getReviewDatetime()
    {
        return $this->reviewDatetime;
    }

    /**
     * Set score
     *
     * @param integer $score
     * @return TbProductReviews
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return TbProductReviews
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return TbProductReviews
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbProductReviews
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
     * Set deleteFlg
     *
     * @param boolean $deleteFlg
     *
     * @return TbProductReviews
     */
    public function setDeleteFlg($deleteFlg)
    {
      $this->deleteFlg = $deleteFlg;

      return $this;
    }

    /**
     * Get deleteFlg
     *
     * @return boolean
     */
    public function getDeleteFlg()
    {
      return $this->deleteFlg;
    }
}
