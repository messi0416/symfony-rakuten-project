<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbDeliveryChangeShippingMethod;

/**
 * TbDeliveryChangeShippingMethodRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbDeliveryChangeShippingMethodRepository extends BaseRepository
{
  const STATUS_NONE = 0;
  const STATUS_DONE = 1;

  public static $STATUS_DISPLAYS = [
      self::STATUS_NONE => '未処理'
    , self::STATUS_DONE => '完了'
  ];


  /**
   * 該当日の未処理データ一覧取得
   * @param \DateTimeInterface $date
   * @param string $shippingMethodCode
   * @return \MiscBundle\Entity\TbDeliveryChangeShippingMethod[]
   */
  public function findList($date = null, $shippingMethodCode = null)
  {
    $qb = $this->createQueryBuilder('c');
    if ($date) {
      $qb->andWhere('c.date = :date')->setParameter(':date',  $date->format('Y-m-d'), \PDO::PARAM_STR);
    }
    $qb->andWhere('c.status = :statusNone')->setParameter(':statusNone', self::STATUS_NONE, \PDO::PARAM_INT);

    if ($shippingMethodCode) {
      $qb->andWhere('c.current_receive_order_delivery_id = :shippingMethodCode')->setParameter(':shippingMethodCode', $shippingMethodCode, \PDO::PARAM_STR);
    }

    $qb->addOrderBy('c.created', 'DESC');

    /** @var TbDeliveryChangeShippingMethod[] $results */
    $results = $qb->getQuery()->getResult();

    return $results;
  }

  /**
   * 該当日の未処理データ一覧取得（スカラー配列）
   * @param \DateTimeInterface $date
   * @return array
   */
  public function getList($date = null)
  {
    $results = $this->findList($date);
    $list = [];
    foreach($results as $change) {
      $list[] = $change->toScalarArray('camel');
    }

    return $list;
  }

  /**
   * 未処理データ 1件取得
   * @param $voucherNumber
   * @return TbDeliveryChangeShippingMethod|null
   */
  public function findActiveOneByVoucherNumber($voucherNumber)
  {
    $qb = $this->createQueryBuilder('c');
    $qb->andWhere('c.voucher_number = :voucherNumber')->setParameter(':voucherNumber', $voucherNumber, \PDO::PARAM_STR);
    $qb->andWhere('c.status = :statusNone')->setParameter(':statusNone', self::STATUS_NONE, \PDO::PARAM_INT);
    $qb->addOrderBy('c.voucher_number', 'DESC');

    /** @var TbDeliveryChangeShippingMethod[] $results */
    $results = $qb->getQuery()->getResult();

    $ret = $results ? $results[0] : null;

    return $ret;
  }



}
