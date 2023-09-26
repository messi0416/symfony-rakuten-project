<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbDeliverySplitRule;
use MiscBundle\Entity\TbSalesDetail;
/**
 * TbDeliveryMethodRepository
 */
class TbDeliveryMethodRepository extends BaseRepository {

  /**
   * 送料グループ種別が設定されているレコードを、IDをキー、エンティティを値とした連想配列で返却する。
   */
  public function getTbDeliveryMethodsWithShippingGroupCode() {
    $qb = $this->createQueryBuilder('d');
    $qb->andWhere('d.shippingGroupCode IS NOT NULL');
    $list = $qb->getQuery()->getResult();

    $result = array();
    foreach ($list as $data) {
      $result[$data->getDeliveryId()] = $data;
    }
    return $result;
  }

  /**
   * 梱包のための発送方法を返す。
   * @return array idと発送方法名の配列
   */
  public function findForPacking()
  {
    $idList = [
      intval(TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACKET), // ゆうパケット
      intval(TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI), // 定形外郵便
      intval(TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK_RSL), // ゆうパック(RSL)
      intval(TbSalesDetail::SHIPPING_METHOD_CODE_SAGAWA), // 佐川急便(e飛伝2)
      intval(TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACK), // ゆうパック
    ];
    $qb = $this->createQueryBuilder('d')
      ->select('d.deliveryId, d.deliveryName');
    $qb->andWhere($qb->expr()->in('d.deliveryId', $idList));
    $qb->orderBy('d.deliveryId', 'DESC');
    return $qb->getQuery()->getResult();
  }
}
