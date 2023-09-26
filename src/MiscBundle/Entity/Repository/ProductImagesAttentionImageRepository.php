<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbProductReviews;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;

/**
 * 商品画像アテンション画像リポジトリ。
 */
class ProductImagesAttentionImageRepository extends BaseRepository
{
  /**
   * 商品アテンション画像一覧取得
   * @param array $conditions
   * @param int $limit
   * @param int $page
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findAttentionImageList($conditions = [], $limit = 100, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger->debug("アテンション画像検索条件：" . print_r($conditions, true));

    $conditionParams = [];

    $sqlSelect = <<<EOD
      SELECT
        ai.md5hash
        , ai.image_path
        , ai.use_product_num_onsale
        , ai.use_product_num_all
        , ai.attention_flg
EOD;

    $sqlBody = <<<EOD
      FROM
        product_images_attention_image ai
EOD;

    // 代表商品コード
    if (isset($conditions['daihyoSyohinCode']) && strlen($conditions['daihyoSyohinCode'])) {
      $logger->debug("検索条件あり");
      $addSqlBody = <<<EOD
        LEFT JOIN product_images i ON ai.md5hash = i.md5hash
        LEFT JOIN (
            SELECT daihyo_syohin_code, MAX(code) as code
            FROM product_images
            GROUP BY daihyo_syohin_code
        ) max_image_code ON i.daihyo_syohin_code = max_image_code.daihyo_syohin_code AND i.code = max_image_code.code
        WHERE
            i.daihyo_syohin_code = :daihyo_syohin_code
EOD;
      $sqlBody .= $addSqlBody;
      $conditionParams[':daihyo_syohin_code'] = $conditions['daihyoSyohinCode'];
    }

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('md5hash', 'md5hash', 'string');
    $rsm->addScalarResult('image_path', 'image_path', 'string');
    $rsm->addScalarResult('use_product_num_onsale', 'use_product_num_onsale', 'integer');
    $rsm->addScalarResult('use_product_num_all', 'use_product_num_all', 'integer');
    $rsm->addScalarResult('attention_flg', 'attention_flg', 'integer');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    foreach($conditionParams as $k => $v) {
      $query->setParameter($k, $v);
    }

    $resultOrders = [];
    $defaultOrders = [
        'use_product_num_onsale' => 'DESC'
        , 'use_product_num_all' => 'DESC'
    ];

    $query->setOrders(array_merge($resultOrders, $defaultOrders));

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
        , $page
        , $limit
        );
    return $pagination;
  }

  /**
   * 引数で渡された配列の各データについて、アテンション画像フラグが変更されていれば更新する。
   * @param array $list 更新対象のmd5hashをキー、アテンション画像フラグ値（数値型）を値とした連想配列
   */
  public function updateAttentionFlg(array $list) {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    /** @var EntityManager $em */
    $em = $this->getEntityManager();
    try {
      $em->beginTransaction();
      foreach ($list as $key => $value) {
        $attentionImage = $this->find($key);
        // 指定されたデータがあり、かつアテンション画像フラグが更新されていれば
        if ($attentionImage && $attentionImage->getAttentionFlg() != $value) {
          $attentionImage->setAttentionFlg($value);
        }
      }
      $em->flush();
      $em->commit();
    } catch (Exception $e) {
      if ($em->getConnection()->isTransactionActive()) {
        $em->rollback();
      }
      throw $e;
    }
  }

}