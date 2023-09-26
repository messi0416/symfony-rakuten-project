<?php

namespace MiscBundle\Entity;

use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;


/**
 * TbProductchoiceitemsShippingGroupLog
 */
class TbProductchoiceitemsShippingGroupLog
{
    use ArrayTrait;
    use FillTimestampTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     */
    private $bundleAxis;

    /**
     * @var string
     */
    private $axisCode;

    /**
     * @var string
     */
    private $targetNeSyohinSyohinCode;

    /**
     * @var integer
     */
    private $shippingGroupCode;

    /**
     * @var string
     */
    private $createNeSyohinSyohinCode;

    /**
     * @var boolean
     */
    private $reflectedFlg;

    /**
     * @var integer
     */
    private $createSymfonyUsersId;

    /**
     * @var \DateTime
     */
    private $created;


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
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbProductchoiceitemsShippingGroupLog
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
     * Set bundleAxis
     *
     * @param string $bundleAxis
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setBundleAxis($bundleAxis)
    {
        $this->bundleAxis = $bundleAxis;

        return $this;
    }

    /**
     * Get bundleAxis
     *
     * @return string
     */
    public function getBundleAxis()
    {
        return $this->bundleAxis;
    }

    /**
     * Set axisCode
     *
     * @param string $axisCode
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setAxisCode($axisCode)
    {
        $this->axisCode = $axisCode;

        return $this;
    }

    /**
     * Get axisCode
     *
     * @return string
     */
    public function getAxisCode()
    {
        return $this->axisCode;
    }

    /**
     * Set targetNeSyohinSyohinCode
     *
     * @param string $targetNeSyohinSyohinCode
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setTargetNeSyohinSyohinCode($targetNeSyohinSyohinCode)
    {
      $this->targetNeSyohinSyohinCode = $targetNeSyohinSyohinCode;

      return $this;
    }

    /**
     * Get targetNeSyohinSyohinCode
     *
     * @return string
     */
    public function getTargetNeSyohinSyohinCode()
    {
      return $this->targetNeSyohinSyohinCode;
    }

    /**
     * Set shippingGroupCode
     *
     * @param integer $shippingGroupCode
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setShippingGroupCode($shippingGroupCode)
    {
        $this->shippingGroupCode = $shippingGroupCode;

        return $this;
    }

    /**
     * Get shippingGroupCode
     *
     * @return integer
     */
    public function getShippingGroupCode()
    {
        return $this->shippingGroupCode;
    }

    /**
     * Set createNeSyohinSyohinCode
     *
     * @param string $createNeSyohinSyohinCode
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setCreateNeSyohinSyohinCode($createNeSyohinSyohinCode)
    {
        $this->createNeSyohinSyohinCode = $createNeSyohinSyohinCode;

        return $this;
    }

    /**
     * Get createNeSyohinSyohinCode
     *
     * @return string
     */
    public function getCreateNeSyohinSyohinCode()
    {
        return $this->createNeSyohinSyohinCode;
    }

    /**
     * Set reflectedFlg
     *
     * @param string $reflectedFlg
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setReflectedFlg($reflectedFlg)
    {
      $this->reflectedFlg = $reflectedFlg;

      return $this;
    }

    /**
     * Get reflectedFlg
     *
     * @return string
     */
    public function getReflectedFlg()
    {
      return $this->reflectedFlg;
    }

    /**
     * Set createSymfonyUsersId
     *
     * @param integer $createSymfonyUsersId
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setCreateSymfonyUsersId($createSymfonyUsersId)
    {
        $this->createSymfonyUsersId = $createSymfonyUsersId;

        return $this;
    }

    /**
     * Get createSymfonyUsersId
     *
     * @return integer
     */
    public function getCreateSymfonyUsersId()
    {
        return $this->createSymfonyUsersId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return TbProductchoiceitemsShippingGroupLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
}
