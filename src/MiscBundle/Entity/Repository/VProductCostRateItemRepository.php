<?php
/**
 * SQLを用いた仮想テーブルエンティティ でのページネーション 実験実装
 * ※現在未使用だが、参考実装としてしばらく置いておきます。
 */

namespace MiscBundle\Entity\Repository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;

/**
 * VProductCostRateItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VProductCostRateItemRepository extends BaseRepository
{
  /**
   * 商品別原価率 一覧表取得
   */
  public function getListPagination($conditions = [], $orders = [], $limit = 20, $page = 1)
  {
    $em = $this->getEntityManager();
    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:VProductCostRateItem', 'm');

    $sqlSelect = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , cal.baika_tnk
EOD;
    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
EOD;

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setOrders($orders);

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator = $this->getContainer()->get('knp_paginator');

    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }

}
