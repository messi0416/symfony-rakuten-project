<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbProductReviews;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;

/**
 */
class TbProductReviewsRepository extends BaseRepository
{

  /**
   * 商品レビュー一覧取得
   * @param array $conditions
   * @param int $limit
   * @param int $page
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findReviewList($conditions = [], $limit = 100, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $conditionParams = [];

    $sqlSelect = <<<EOD
      select
        pr.id as id
        , pr.review_datetime as review_date
        , pr.score
        , mc.review_point_ave as review_point_ave
        , mc.review_num as review_num
        , m.picfolderP1 AS image_dir
        , m.picnameP1   AS image_file
        , pr.daihyo_syohin_code as daihyo_syohin_code
        , pr.ne_syohin_syohin_code as sku_code
        , m.daihyo_syohin_name as daihyo_syohin_name
        , pr.title as title
        , pr.body as body
        , sm.mall_name_short1 as posting_site
EOD;

    $sqlBody = <<<EOD
      from tb_product_reviews pr
      left join tb_mainproducts m ON pr.daihyo_syohin_code = m.daihyo_syohin_code
      left join tb_shopping_mall sm ON pr.ne_mall_id = sm.ne_mall_id
      left join tb_mainproducts_cal mc ON pr.daihyo_syohin_code = mc.daihyo_syohin_code
      WHERE pr.delete_flg = 0

EOD;

    $paramAndSql = $this->getParamAndSql($conditions);
    $sqlBody .= $paramAndSql['sql'];
    $conditionParams = $paramAndSql['paramArray'];

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id', 'string');
    $rsm->addScalarResult('review_date', 'review_date', 'string');
    $rsm->addScalarResult('score', 'score', 'integer');
    $rsm->addScalarResult('review_point_ave', 'score_average', 'string');
    $rsm->addScalarResult('review_num', 'review_num', 'string');
    $rsm->addScalarResult('image_dir', 'image_dir', 'string');
    $rsm->addScalarResult('image_file', 'image_file', 'string');
    $rsm->addScalarResult('sku_code', 'sku_code', 'string');
    $rsm->addScalarResult('daihyo_syohin_code', 'daihyo_syohin_code', 'string');
    $rsm->addScalarResult('daihyo_syohin_name', 'daihyo_syohin_name', 'string');
    $rsm->addScalarResult('title', 'title', 'string');
    $rsm->addScalarResult('body', 'body', 'string');
    $rsm->addScalarResult('posting_site', 'posting_site', 'string');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    foreach($conditionParams as $k => $v) {
      $query->setParameter($k, $v);
    }

    $resultOrders = [];
    $defaultOrders = [
      'pr.review_datetime' => 'DESC'
      , 'pr.updated' => 'DESC'
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
   * 商品レビューから、代表商品ごとに、検索条件に合致した平均点とレビュー数を取得して返却する。
   * 平均点とレビュー数は、tb_mainproducts_cal にある全期間のものではなく、レビューテーブルから再集計する。
   * 戻り値は、代表商品をキー、代表商品ごとのデータの配列を値とする連想配列となる。
   * 
   * 戻り値の例：
   * [
   *     'top-12345' => ['daihyo_syohin_code' => 'top-12345', 'review_point_ave' => 4.3, 'review_point_num' => 6]
   *   , 'aut-00001' => ['daihyo_syohin_code' => 'aut-00001', 'review_point_ave' => 3.9, 'review_point_num' => 12]
   * ]
   */
  public function findProductReviewSummaryByCondition($conditions = []) {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code
        , AVG(score) as review_point_ave
        , COUNT(*) as review_point_num
      FROM tb_product_reviews pr
      /* 
        SELECT句で、正式な代表商品コードを使うための結合。
        tb_product_reviews.daihyo_syohin_code は、大文字・小文字が合っていない場合がある
        TODO: #209356 対応後は、結合不要になる見込み。
      */
      JOIN tb_mainproducts m
        ON pr.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE 1 = 1
EOD;
    $paramAndSql = $this->getParamAndSql($conditions);
    $sql .= $paramAndSql['sql'];
    $sql .= "GROUP BY daihyo_syohin_code";
    $paramArray = $paramAndSql['paramArray'];
    
    $stmt = $dbMain->prepare($sql);
    if ($paramArray) {
      foreach($paramArray as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
  }

  /**
   * 商品レビュー平均取得
   * @param array $conditions
   * @return array 商品レビュー平均
   */
  public function getAllAverage($conditions = [])
  {
    $dbMain = $this->getConnection('main');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $paramArray = [];

    $sql = <<<EOD
      select
        round(sum(pr.score)/count(pr.id),2) as all_average
EOD;

    $sql .= <<<EOD
      from tb_product_reviews pr
      left join tb_mainproducts m ON pr.daihyo_syohin_code = m.daihyo_syohin_code
      left join tb_shopping_mall sm ON pr.ne_mall_id = sm.ne_mall_id
      WHERE 1 = 1

EOD;
    $paramAndSql = $this->getParamAndSql($conditions);
    $sql .= $paramAndSql['sql'];
    $paramArray = $paramAndSql['paramArray'];

    $stmt = $dbMain->prepare($sql);
    if ($paramArray) {
      foreach($paramArray as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->execute();
    $result = null;
    if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result = $row;
    }
    return $result;
  }
  
  /**
   * 指定された店舗の、登録日が最も新しいレビューを取得する。
   * 登録日が同一の場合は、IDが新しいほうを取得する。
   * @param TbProductReviews $neMallId NEモールID
   */
  public function getLastReviewByShop($neMallId) {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();
    $dql = <<<EOD
      SELECT r
      FROM MiscBundle:TbProductReviews r
      WHERE r.neMallId = :neMallId
      ORDER BY r.reviewDatetime DESC, r.id DESC
EOD;
    $query = $em->createQuery($dql);
    $query->setParameter('neMallId', $neMallId);
    $query->setMaxResults(1);
    $list = $query->getResult();
    if ($list) {
      return $list[0];
    }
    return null;
  }

  /**
   * 商品レビュー一覧取得、商品レビュー平均取得共通パラメータ、SQL生成
   * @param array $conditions
   * @return array 検索条件に対するパラメータとSQL配列
   */
  private function getParamAndSql ($conditions){
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');
    $sql = '';
    $paramArray = [];
    // レビュー日 FROM
    if (isset($conditions['date_from']) && $conditions['date_from'] instanceof \DateTimeInterface) {
      $sql .= " AND pr.review_datetime >= :dateFrom ";
      $paramArray[':dateFrom'] =  $conditions['date_from']->format('Y-m-d');
    }
    // レビュー日 TO
    if (isset($conditions['date_to']) && $conditions['date_to'] instanceof \DateTimeInterface) {
      $sql .= " AND pr.review_datetime < :dateTo ";
      $paramArray[':dateTo'] =  $conditions['date_to']->modify('+1 day')->format('Y-m-d');
    }

    // スコア下限
    if (isset($conditions['score_from']) && strlen($conditions['score_from'])) {
      $sql .= " AND pr.score >= :scoreFrom ";
      $paramArray[':scoreFrom'] = $conditions['score_from'];
    }

    // スコア上限
    if (isset($conditions['score_to']) && strlen($conditions['score_to'])) {
      $sql .= " AND pr.score <= :scoreTo";
      $paramArray[':scoreTo'] = $conditions['score_to'];
    }

    // 商品コード
    if (isset($conditions['daihyo_syohin_code']) && strlen(trim($conditions['daihyo_syohin_code']))) {
      $sql .= " AND pr.daihyo_syohin_code = :daihyoSyohinCode ";
      $paramArray[':daihyoSyohinCode'] = trim($conditions['daihyo_syohin_code']);
    }
    
    // 商品コード（複数） 配列で渡す
    if (isset($conditions['daihyo_syohin_code_list']) && count($conditions['daihyo_syohin_code_list'])) {
      $codeList = [];
      $dbMain = $this->getConnection('main');
      foreach ($conditions['daihyo_syohin_code_list'] as $row) {
        $codeList[] = $dbMain->quote($row, \PDO::PARAM_STR);
      }
      $codeListStr = implode(',', $codeList);
      $sql .= " AND pr.daihyo_syohin_code IN ($codeListStr) ";
    }

    // 商品名
    if (isset($conditions['daihyo_syohin_name']) && strlen(trim($conditions['daihyo_syohin_name']))) {
      $word = '%' . $commonUtil->escapeLikeString(trim($conditions['daihyo_syohin_name'])) . '%';
      $sql .= " AND m.daihyo_syohin_name like :daihyoSyohinName ";
      $paramArray[':daihyoSyohinName'] = $word;
    }

    // 投稿サイト
    if (isset($conditions['ne_mall_id']) && strlen($conditions['ne_mall_id'])) {
      $sql .= " AND pr.ne_mall_id = :neMallId ";
      $paramArray[':neMallId'] = $conditions['ne_mall_id'];
    }
    return [
      'sql' => $sql
      , 'paramArray' => $paramArray
    ];
  }

  /**
   * ユーザIDかチームIDごとに、指定日からのレビュー点合計とレビュー数を連想配列で返す。
   * 
   * 戻り値の例：
   * [
   *     1 => ['totalScore' => 7312, 'count' => 1826],
   *     2 => ['totalScore' => 20015, 'count' => 4834],
   *     ....
   * ]
   * @param array $conditions 検索条件
   * @param string $target 検索対象 'account'|'team'
   * @param string $reviewDateFrom レビュー日FROM Y-m-d H:i:s|''
   * @return array 代表商品毎のレビュー点合計とレビュー数の情報を持つ連想配列
   */
  public function findTotalReviewsAndCountByTarget($conditions, $target, $reviewDateFrom = '')
  {
    // (1) 期間内関連商品取得用サブクエリ作成
    $targetDateFrom = $conditions['targetDateFrom'];
    $targetDateTo = $conditions['targetDateTo'];
    $selectTask = isset($conditions['selectTask']) ? array_map('intval', $conditions['selectTask']) : [];
    $applyStartDateFrom = $conditions['applyStartDateFrom'];
    $applyStartDateTo = $conditions['applyStartDateTo'];

    if ($targetDateFrom && $targetDateTo && $targetDateFrom > $targetDateTo) {
      return [];
    }
    if ($applyStartDateFrom && $applyStartDateTo && $applyStartDateFrom > $applyStartDateTo) {
      return [];
    }

    $addWheres = '';
    $wheres = [];
    $params = [];
    if ($targetDateFrom) {
      $wheres[] = '(a.apply_end_date IS NULL OR a.apply_end_date >= :targetDateFrom)';
      $wheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateTimeFrom)';
      $params[':targetDateFrom'] = $targetDateFrom;
      $params[':targetDateTimeFrom'] = $targetDateFrom . ' 00:00:00';
    }
    if ($targetDateTo) {
      $wheres[] = 'a.apply_start_date <= :targetDateTo';
      $wheres[] = 'm.販売開始日 <= :targetDateTo';
      $params[':targetDateTo'] = $targetDateTo;
    }
    if ($applyStartDateFrom) {
      $wheres[] = 'a.apply_start_date >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] = $applyStartDateFrom;
    }
    if ($applyStartDateTo) {
      $wheres[] = 'a.apply_start_date <= :applyStartDateTo';
      $params[':applyStartDateTo'] = $applyStartDateTo;
    }
    if (count($selectTask) > 0) {
      $selectTaskStr = implode(', ', $selectTask);
      $wheres[] = "a.product_sales_task_id IN ({$selectTaskStr})";
    }
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $relatedProductFromAndWhere = <<<EOD
      FROM
        tb_product_sales_account a
        INNER JOIN tb_mainproducts m
          ON a.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal c
          ON a.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        a.status = :registration
        {$addWheres}
EOD;

    // (2) レビュー情報取得
    // レビュー日From: 指定無しなら1年前
    if ($reviewDateFrom === '') {
      $reviewDateFrom = (new \DateTime())->modify('-1 year')->setTime(0, 0, 0)->format('Y-m-d');
    }
    $targetSql = '';
    if ($target === 'account') {
      $targetSql = 'user_id';
    } elseif ($target === 'team') {
      $targetSql = 'team_id';
    } else {
      return [];
    }

    $sql = <<<EOD
      SELECT
        target_product.{$targetSql},
        SUM(review.total_score) totalScore,
        SUM(review.review_num) count
      FROM
        /* 集計対象毎の期間内関連商品一覧 */
        (
          SELECT DISTINCT
            a.{$targetSql},
            a.daihyo_syohin_code
          {$relatedProductFromAndWhere}
        ) target_product
        INNER JOIN (
          /* （期間内関連）商品毎の、レビュー合計とレビュー件数 */
          SELECT 
            pr.daihyo_syohin_code,
            SUM(score) as total_score,
            COUNT(*) as review_num
          FROM
            tb_product_reviews pr
            INNER JOIN (
              /* 期間内関連商品一覧 */
              SELECT DISTINCT
                a.daihyo_syohin_code
              {$relatedProductFromAndWhere}
            ) related_products
              ON pr.daihyo_syohin_code = related_products.daihyo_syohin_code
          WHERE
            review_datetime >= :reviewDateFrom
          GROUP BY
            pr.daihyo_syohin_code
        ) review
          ON target_product.daihyo_syohin_code = review.daihyo_syohin_code
      GROUP BY
        target_product.{$targetSql}
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    $stmt->bindValue(':reviewDateFrom', $reviewDateFrom, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
  }

  /**
   * 指定期間内に販売していた担当者あり商品の、指定日からのレビュー点合計とレビュー数を連想配列で返す。
   * 
   * 戻り値の例：
   * [
   *     'all' => [
   *         'totalScore' => 3456,
   *         'count' => 789,
   *     ],
   *     'noAccount' => [
   *         'totalScore' => 1234,
   *         'count' => 567,
   *     ],
   * ]
   * @param array $relatedProductConditions 期間内関連商品検索条件
   * @param string $reviewDateFrom レビュー日FROM Y-m-d H:i:s|''
   * @return array 代表商品毎のレビュー点合計とレビュー数の情報を持つ連想配列
   */
  public function findReviewsInPeriod($relatedProductConditions, $reviewDateFrom = '')
  {
    $reviews = [
      'all' => [
        'totalScore' => 0,
        'count' => 0,
      ],
      'noAccount' => [
        'totalScore' => 0,
        'count' => 0,
      ],
    ];

    $params = [];

    $targetDateFrom = $relatedProductConditions['targetDateFrom'];
    $targetDateTo = $relatedProductConditions['targetDateTo'];
    if ($targetDateTo !== '' && $targetDateFrom > $targetDateTo) {
      return $reviews;
    }
    $relatedProductWheres = [];
    // 条件：対象日時From
    if ($targetDateFrom) {
      $relatedProductWheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateFrom)';
      $params[':targetDateFrom'] = $targetDateFrom . ' 00:00:00';
    }
    // 条件：対象日時To
    if ($targetDateTo) {
      $relatedProductWheres[] = 'm.販売開始日 <= :targetDateTo';
      $params[':targetDateTo'] = $targetDateTo;
    }
    $addRelatedProductWheres = '';
    if ($relatedProductWheres) {
      $addRelatedProductWheres = ' AND ' . implode(' AND ', $relatedProductWheres);
    }

    // レビュー日From: 指定無しなら1年前
    if ($reviewDateFrom === '') {
      $reviewDateFrom = (new \DateTime())->modify('-1 year')->setTime(0, 0, 0)->format('Y-m-d');
    }
    $sql = <<<EOD
      SELECT 
        SUM(score) as totalScore,
        COUNT(*) as count
      FROM
        tb_product_reviews pr
        JOIN (
          /* 検索条件の期間中に販売していた代表商品 */
          SELECT
            m.daihyo_syohin_code
          FROM
            tb_mainproducts m
            INNER JOIN tb_mainproducts_cal c
              ON m.daihyo_syohin_code = c.daihyo_syohin_code
          WHERE
            1
            {$addRelatedProductWheres}
        ) relatedProduct
          ON pr.daihyo_syohin_code = relatedProduct.daihyo_syohin_code
      WHERE
        review_datetime >= :reviewDateFrom
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->bindValue(':reviewDateFrom', $reviewDateFrom, \PDO::PARAM_STR);
    $stmt->execute();
    $reviews = $stmt->fetch(\PDO::FETCH_ASSOC);
    $totalScore = $reviews ? $reviews['totalScore'] : 0;
    $count = $reviews ? $reviews['count'] : 0;

    // 担当者有り商品のレビュー情報を取得する
    $accountReviews = $this->findAccountReviewsTotal($relatedProductConditions, $reviewDateFrom);

    $reviews['all']['totalScore'] = $totalScore;
    $reviews['all']['count'] = $count;
    $reviews['noAccount']['totalScore'] = $totalScore - $accountReviews['totalScore'];
    $reviews['noAccount']['count'] = $count - $accountReviews['count'];
    return $reviews;
  }

  /**
   * 指定期間内に販売していた担当者有り商品の、指定日からのレビュー点合計とレビュー数を連想配列で返す。
   * 
   * 戻り値の例：
   * [
   *     'totalScore' => 2222,
   *     'count' => 222,
   * ]
   * @param array $conditions 期間内関連商品検索条件
   * @param string $reviewDateFrom レビュー日FROM Y-m-d H:i:s|''
   * @return array 代表商品毎のレビュー点合計とレビュー数の情報を持つ連想配列
   */
  public function findAccountReviewsTotal($conditions, $reviewDateFrom = '')
  {
    $accountReviews = [
      'totalScore' => 0,
      'count' => 0,
    ];

    $targetDateFrom = $conditions['targetDateFrom'];
    $targetDateTo = $conditions['targetDateTo'];
    $selectTask = isset($conditions['selectTask']) ? array_map('intval', $conditions['selectTask']) : [];
    $applyStartDateFrom = $conditions['applyStartDateFrom'];
    $applyStartDateTo = $conditions['applyStartDateTo'];

    if ($targetDateFrom && $targetDateTo && $targetDateFrom > $targetDateTo) {
      return $accountReviews;
    }
    if ($applyStartDateFrom && $applyStartDateTo && $applyStartDateFrom > $applyStartDateTo) {
      return $accountReviews;
    }

    $addWheres = '';
    $wheres = [];
    $params = [];
    if ($targetDateFrom) {
      $wheres[] = '(a.apply_end_date IS NULL OR a.apply_end_date >= :targetDateFrom)';
      $wheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateTimeFrom)';
      $params[':targetDateFrom'] = $targetDateFrom;
      $params[':targetDateTimeFrom'] = $targetDateFrom . ' 00:00:00';
    }
    if ($targetDateTo) {
      $wheres[] = 'a.apply_start_date <= :targetDateTo';
      $wheres[] = 'm.販売開始日 <= :targetDateTo';
      $params[':targetDateTo'] = $targetDateTo;
    }
    if ($applyStartDateFrom) {
      $wheres[] = 'a.apply_start_date >= :applyStartDateFrom';
      $params[':applyStartDateFrom'] = $applyStartDateFrom;
    }
    if ($applyStartDateTo) {
      $wheres[] = 'a.apply_start_date <= :applyStartDateTo';
      $params[':applyStartDateTo'] = $applyStartDateTo;
    }
    if (count($selectTask) > 0) {
      $selectTaskStr = implode(', ', $selectTask);
      $wheres[] = "a.product_sales_task_id IN ({$selectTaskStr})";
    }
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    // レビュー日From: 指定無しなら1年前
    if ($reviewDateFrom === '') {
      $reviewDateFrom = (new \DateTime())->modify('-1 year')->setTime(0, 0, 0)->format('Y-m-d');
    }
    $sql = <<<EOD
      SELECT 
        SUM(score) as totalScore,
        COUNT(*) as count
      FROM
        tb_product_reviews pr
        JOIN (
          /* 検索条件の期間中に販売していた代表商品 */
          SELECT DISTINCT
            m.daihyo_syohin_code
          FROM
            tb_product_sales_account a
            INNER JOIN tb_mainproducts m
              ON a.daihyo_syohin_code = m.daihyo_syohin_code
            INNER JOIN tb_mainproducts_cal c
              ON a.daihyo_syohin_code = c.daihyo_syohin_code
          WHERE
            a.status = :registration
            {$addWheres}
        ) relatedProduct
          ON pr.daihyo_syohin_code = relatedProduct.daihyo_syohin_code
      WHERE
        review_datetime >= :reviewDateFrom
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->bindValue(':reviewDateFrom', $reviewDateFrom, \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    $accountReviews['totalScore'] = $result ? $result['totalScore'] : 0;
    $accountReviews['count'] = $result ? $result['count'] : 0;
    return $accountReviews;
  }
}
