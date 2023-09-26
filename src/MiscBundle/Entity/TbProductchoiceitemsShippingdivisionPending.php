<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use AppBundle\Entity\TbMainproducts;

/**
 * TbProductchoiceitemsShippingdivisionPending
 */
class TbProductchoiceitemsShippingdivisionPending {

  use ArrayTrait;
  use FillTimestampTrait;

  /** 処理ステータス：保留 */
  const REFLECT_STATUS_PENDING = 1;
  /** 処理ステータス：保留 */
  const REFLECT_STATUS_REFLECTED = 2;
  /** 処理ステータス：却下 */
  const REFLECT_STATUS_REJECTED = 3;
  /** 処理ステータス：自動取消 */
  const REFLECT_STATUS_CANCEL = 4;

  /** @var TbMainproducts */
  protected $product;

  /** @var ArrayCollection|TbProductchoiceitems[] 軸コードが一致するもののみの TbProductchoiceitems のリスト。自動設定ではない */
  protected $choiceItems;

  /**
   * products 設定
   *
   * @param
   *          TbMainproducts
   */
  public function setProduct($product) {
    $this->product = $product;

    return $this;
  }

  /**
   * products 取得
   *
   * @return TbMainproducts
   */
  public function getProduct() {
    return $this->$product;
  }

  /**
   * choiceItems 設定
   *
   * @param
   *          ArrayCollection|TbProductchoiceitems[]
   */
  public function setChoiceItems($choiceItems) {
    $this->choiceitems = $choiceItems;

    return $this;
  }

  /**
   * choiceItems 取得
   *
   * @return ArrayCollection|TbProductchoiceitems[]
   */
  public function getChoiceItems() {
    return $this->$choiceItems;
  }

  /**
   *
   * @var integer
   */
  private $id;

  /**
   *
   * @var string
   */
  private $daihyoSyohinCode;

  /**
   *
   * @var string
   */
  private $bundleAxis;

  /**
   *
   * @var string
   */
  private $axisCode;

  /**
   *
   * @var string
   */
  private $targetNeSyohinSyohinCode;

  /**
   *
   * @var integer
   */
  private $prevShippingdivisionId;

  /**
   *
   * @var integer
   */
  private $pendingShippingdivisionId;

  /**
   *
   * @var integer
   */
  private $targetVoucharNumber;

  /**
   *
   * @var integer
   */
  private $reflectStatus;

  /**
   *
   * @var \DateTime
   */
  private $created;

  /**
   *
   * @var integer
   */
  private $updSymfonyUsersId;

  /**
   *
   * @var \DateTime
   */
  private $updated;

  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set daihyoSyohinCode
   *
   * @param string $daihyoSyohinCode
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode) {
    $this->daihyoSyohinCode = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyoSyohinCode
   *
   * @return string
   */
  public function getDaihyoSyohinCode() {
    return $this->daihyoSyohinCode;
  }

  /**
   * Set bundleAxis
   *
   * @param string $bundleAxis
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setBundleAxis($bundleAxis) {
    $this->bundleAxis = $bundleAxis;

    return $this;
  }

  /**
   * Get bundleAxis
   *
   * @return string
   */
  public function getBundleAxis() {
    return $this->bundleAxis;
  }

  /**
   * Set axisCode
   *
   * @param string $axisCode
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setAxisCode($axisCode) {
    $this->axisCode = $axisCode;

    return $this;
  }

  /**
   * Get axisCode
   *
   * @return string
   */
  public function getAxisCode() {
    return $this->axisCode;
  }

  /**
   * Set targetNeSyohinSyohinCode
   *
   * @param string $targetNeSyohinSyohinCode
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setTargetNeSyohinSyohinCode($targetNeSyohinSyohinCode) {
    $this->targetNeSyohinSyohinCode = $targetNeSyohinSyohinCode;

    return $this;
  }

  /**
   * Get targetNeSyohinSyohinCode
   *
   * @return string
   */
  public function getTargetNeSyohinSyohinCode() {
    return $this->targetNeSyohinSyohinCode;
  }

  /**
   * Set prevShippingdivisionId
   *
   * @param integer $prevShippingdivisionId
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setPrevShippingdivisionId($prevShippingdivisionId) {
    $this->prevShippingdivisionId = $prevShippingdivisionId;

    return $this;
  }

  /**
   * Get prevShippingdivisionId
   *
   * @return integer
   */
  public function getPrevShippingdivisionId() {
    return $this->prevShippingdivisionId;
  }

  /**
   * Set pendingShippingdivisionId
   *
   * @param integer $pendingShippingdivisionId
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setPendingShippingdivisionId($pendingShippingdivisionId) {
    $this->pendingShippingdivisionId = $pendingShippingdivisionId;

    return $this;
  }

  /**
   * Get pendingShippingdivisionId
   *
   * @return integer
   */
  public function getPendingShippingdivisionId() {
    return $this->pendingShippingdivisionId;
  }

  /**
   * Set targetVoucharNumber
   *
   * @param integer $targetVoucharNumber
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setTargetVoucharNumber($targetVoucharNumber) {
    $this->targetVoucharNumber = $targetVoucharNumber;

    return $this;
  }

  /**
   * Get targetVoucharNumber
   *
   * @return integer
   */
  public function getTargetVoucharNumber() {
    return $this->targetVoucharNumber;
  }

  /**
   * Set reflectStatus
   *
   * @param integer $reflectStatus
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setReflectStatus($reflectStatus) {
    $this->reflectStatus = $reflectStatus;

    return $this;
  }

  /**
   * Get reflectStatus
   *
   * @return integer
   */
  public function getReflectStatus() {
    return $this->reflectStatus;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setCreated($created) {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return \DateTime
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Set updSymfonyUsersId
   *
   * @param integer $updSymfonyUsersId
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setUpdSymfonyUsersId($updSymfonyUsersId) {
    $this->updSymfonyUsersId = $updSymfonyUsersId;

    return $this;
  }

  /**
   * Get updSymfonyUsersId
   *
   * @return integer
   */
  public function getUpdSymfonyUsersId() {
    return $this->updSymfonyUsersId;
  }

  /**
   * Set updated
   *
   * @param \DateTime $updated
   * @return TbProductchoiceitemsShippingdivisionPending
   */
  public function setUpdated($updated) {
    $this->updated = $updated;

    return $this;
  }

  /**
   * Get updated
   *
   * @return \DateTime
   */
  public function getUpdated() {
    return $this->updated;
  }
}
