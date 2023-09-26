<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbPlusnaoproductdirectory;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;

/**
 * TbPlusnaoproductdirectoryRepository
 *
 */
class TbPlusnaoproductdirectoryRepository extends BaseRepository
{
  const ALTER_STRING_FOR_FILTER_CHOICE_EMPTY_STRING = '（空白）';

  /**
   * 「アダルトの登録される可能性のあるカテゴリ」取得処理
   * @return TbPlusnaoproductdirectory[]
   */
  public function getAdultDirectories()
  {
    $sql = <<<EOD
      SELECT
         d.*
      FROM tb_plusnaoproductdirectory d
      WHERE (
          (
                d.`フィールド2` IN (
                      'パーティー・イベント用品・販促品'
                    , 'レディースインナー'
                    , 'メンズインナー'
                )
             OR (d.`フィールド2` = '婦人服' AND d.`フィールド3` = 'コスチューム')
          )
          AND NOT (
                 d.`フィールド2` = 'レディースインナー'
             AND d.`フィールド3` IN ('ブラジャー単品', '靴下')
          )
          AND NOT (
                 d.`フィールド2` = 'メンズインナー'
             AND d.`フィールド3` IN ('靴下')
          )
        )
EOD;

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:TbPlusnaoproductdirectory', 'm');

    $query = $em->createNativeQuery($sql, $rsm);

    return $query->getResult();
  }

  /**
   * 一覧 データ取得
   * @param array $conditions
   * @param array $orders
   * @param int $page
   * @param int $limit
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findDirectoryCount($conditions = [])
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    /** @var EntityManager $em */
    $em = $this->getEntityManager();
    
    $fieldName = 'フィールド1';
    $totalName = '全ディレクトリ合計';
    $groupBy = array('フィールド1');
    $wheres = array();

    // 絞込条件：取込日（from）
    if (isset($conditions['dateFrom']) && $conditions['dateFrom'] instanceof \DateTimeInterface) {
      $params[':dateFrom'] =  $conditions['dateFrom']->format('Y-m-d');
    }
    // 絞込条件：取込日（to）
    if (isset($conditions['dateTo']) && $conditions['dateTo'] instanceof \DateTimeInterface) {
      $params[':dateTo'] =  $conditions['dateTo']->format('Y-m-d');
    }
    
    // フィールド1
    if (isset($conditions['field1']) && !is_null($conditions['field1'])) {
      $fieldName = 'フィールド2';
      $groupBy[] = 'フィールド2';
      $wheres[] = 'フィールド1 = :field1';
      $params[':field1'] =  $conditions['field1'];
      $totalName = $conditions['field1'];
    }

    // フィールド2
    if (isset($conditions['field2']) && !is_null($conditions['field2'])) {
      $fieldName = 'フィールド3';
      $groupBy[] = 'フィールド3';
      $wheres[] = 'フィールド2 = :field2';
      $params[':field2'] =  $conditions['field2'];
      $totalName = $conditions['field2'];
    }

    // フィールド3
    if (isset($conditions['field3']) && !is_null($conditions['field3'])) {
      $fieldName = 'フィールド4';
      $groupBy[] = 'フィールド4';
      $wheres[] = 'フィールド3 = :field3';
      $params[':field3'] =  $conditions['field3'];
      $totalName = $conditions['field3'];
    }

    // フィールド4
    if (isset($conditions['field4']) && !is_null($conditions['field4'])) {
      $fieldName = 'フィールド5';
      $groupBy[] = 'フィールド5';
      $wheres[] = 'フィールド4 = :field4';
      $params[':field4'] =  $conditions['field4'];
      $totalName = $conditions['field4'];
    }

    // フィールド5
    if (isset($conditions['field5']) && !is_null($conditions['field5'])) {
      $fieldName = 'フィールド6';
      $groupBy[] = 'フィールド6';
      $wheres[] = 'フィールド5 = :field5';
      $params[':field5'] =  $conditions['field5'];
      $totalName = $conditions['field5'];
    }

    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $sql = <<<EOD
      SELECT
        {$fieldName} as field
        ,SUM(IFNULL(mp.cnt,0)) AS cnt
        ,SUM(IFNULL(mp2.cnt_all,0)) AS cnt_all
        ,SUM(IFNULL(mp3.cnt_sale,0)) AS cnt_sale
        ,SUM(IFNULL(mp4.cnt_instant,0)) AS cnt_instant
      FROM
      (
        SELECT
          NEディレクトリID
          ,{$fieldName}
        FROM
          tb_plusnaoproductdirectory
        WHERE
          1
          {$addWheres}
      ) ppd
      -- 期間内出品登録数
      LEFT JOIN
      (
        SELECT
          NEディレクトリID
          ,count(*) as cnt
        FROM
          tb_mainproducts
        WHERE
      	`登録日時` BETWEEN :dateFrom AND :dateTo
        GROUP BY
          NEディレクトリID
      ) mp
      ON ppd.NEディレクトリID = mp.NEディレクトリID
      -- 全登録数
      LEFT JOIN
      (
        SELECT
          NEディレクトリID
          ,count(*) as cnt_all
        FROM
          tb_mainproducts mp
        INNER JOIN
      	tb_mainproducts_cal mpc
        ON
      	mp.daihyo_syohin_code = mpc.daihyo_syohin_code
        WHERE
          mpc.deliverycode IN ('0','1','2','3')
        GROUP BY
          NEディレクトリID
      ) mp2
      ON ppd.NEディレクトリID = mp2.NEディレクトリID
      -- 販売中
      LEFT JOIN
      (
        SELECT
          NEディレクトリID
          ,count(*) as cnt_sale
        FROM
          tb_mainproducts mp
        INNER JOIN
      	tb_mainproducts_cal mpc
        ON
      	mp.daihyo_syohin_code = mpc.daihyo_syohin_code
        WHERE
          mpc.deliverycode IN ('0','1','2')
        GROUP BY
          NEディレクトリID
      ) mp3
      ON ppd.NEディレクトリID = mp3.NEディレクトリID
      -- 販売中
      LEFT JOIN
      (
        SELECT
          NEディレクトリID
          ,count(*) as cnt_instant
        FROM
          tb_mainproducts mp
        INNER JOIN
      	tb_mainproducts_cal mpc
        ON
      	mp.daihyo_syohin_code = mpc.daihyo_syohin_code
        WHERE
          mpc.deliverycode IN ('0','1')
        GROUP BY
          NEディレクトリID
      ) mp4
      ON ppd.NEディレクトリID = mp4.NEディレクトリID
      GROUP BY
        `field`
EOD;

    $totalSql = <<<EOD
      SELECT
        '{$totalName}' as field
        ,SUM(cnt) AS cnt
        ,SUM(cnt_all) AS cnt_all
        ,SUM(cnt_sale) AS cnt_sale
        ,SUM(cnt_instant) AS cnt_instant
      FROM
      (
        {$sql}
      ) tmp
EOD;

    $rsm =  new ResultSetMapping();
    $rsm->addScalarResult('field', 'field', 'string');
    $rsm->addScalarResult('cnt', 'cnt', 'integer');
    $rsm->addScalarResult('cnt_all', 'cnt_all', 'integer');
    $rsm->addScalarResult('cnt_sale', 'cnt_sale', 'integer');
    $rsm->addScalarResult('cnt_instant', 'cnt_instant', 'integer');

    $resultOrders = [];
    $defaultOrders = [
    ];
/*
    if ($orders) {
      foreach($orders as $k => $v) {
        switch($k) {
          case 'daihyo_syohin_code':
//            $k = 'o.' . $k;
            break;
        }

        $resultOrders[$k] = $v;
        if (isset($defaultOrders[$k])) {
          unset($defaultOrders[$k]);
        }
      }
    }
    $query->setOrders(array_merge($resultOrders, $defaultOrders));
*/

    $query = $em->createNativeQuery($sql, $rsm);
    foreach($params as $k => $v) {
      $query->setParameter($k, $v);
    }
    $result = $query->getResult();
    
    foreach($result as $key => $row){
      if($row['cnt_sale'] > 0){
        $result[$key]['rate'] = round($row['cnt_instant'] / $row['cnt_sale'] * 100,1);
      } else {
        $result[$key]['rate'] = 0;
      }
    }
    
    $totalQuery = $em->createNativeQuery($totalSql, $rsm);
    foreach($params as $k => $v) {
      $totalQuery->setParameter($k, $v);
    }
    $totalResult = $totalQuery->getResult();

    foreach($totalResult as $key => $row){
      if($row['cnt_sale'] > 0){
        $totalResult[$key]['rate'] = round($row['cnt_instant'] / $row['cnt_sale'] * 100,1);
      } else {
        $totalResult[$key]['rate'] = 0;
      }
    }

    
    $ret = array(
      'data' => $result
      ,'total' => $totalResult[0]
    );

    return $ret;
  }

  /**
   * フィールド配列取得
   * @param string $type
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getFieldList()
  {
    $dbMain = $this->getConnection('main');

    $fields = [
    ];

    // lv3
    $sql = <<<EOD
        SELECT
          `フィールド1` as field1
          ,`フィールド2` as field2
          ,`フィールド3` as field3
          ,`フィールド4` as field4
          ,`フィールド5` as field5
        FROM
          tb_plusnaoproductdirectory
        ORDER BY
          `フィールド1`
          ,`フィールド2`
          ,`フィールド3`
          ,`フィールド4`
          ,`フィールド5`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
      // 絞り込みのため、空白文字は「（空白）」に置き換える。
      if(strlen($row['field1'])){
        $field1 = $row['field1'];
        $field2 = $row['field2'];
        $field3 = $row['field3'];
        $field4 = $row['field4'];
        $field5 = $row['field5'];
        
        if(!isset($fields[$field1][$field2][$field3][$field4])) $fields[$field1][$field2][$field3][$field4] = array();
        if(!in_array($field5,$fields[$field1][$field2][$field3][$field4]))$fields[$field1][$field2][$field3][$field4][] = $field5;
      }
    }

    return $fields;
  }

  /**
   * 楽天plusnao販売商品及び新商品の楽天ジャンルIDの配列を返す。
   *
   * 次の1-3全てを満たす商品に紐づく楽天ジャンルIDの一覧を配列として返却する。
   * (1) アダルトチェックが、「ブラック」・「未審査」以外
   * (2) deliverycodeが、「仮登録」以外
   * (3) 「楽天plusnaoの倉庫格納フラグがOFF」
   *      または「販売開始日が1週間以内で、現在も販売中の新商品」
   * @return array 楽天ジャンルIDの配列
   */
  public function findGenreIdsForRakutenPlusnaoAndNewProducts()
  {
    $today = new \DateTime();
    $startOfAvailability = $today->modify("-7 day")->format('Y-m-d');
    $endOfAvailability = $today->format('Y-m-d 23:59:59');
    $sql = <<<EOD
      SELECT
        distinct d.楽天ディレクトリID
      FROM
        tb_plusnaoproductdirectory d
        JOIN tb_mainproducts m
          ON d.NEディレクトリID = m.NEディレクトリID
        JOIN tb_mainproducts_cal cal
          ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        JOIN tb_rakuteninformation i
          ON m.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE
        cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND cal.deliverycode <> :deliveryCodeTemporary
        AND (
          i.warehouse_stored_flg = 0
          OR (
            m.販売開始日 >= :startOfAvailability
            AND (cal.endofavailability IS NULL OR cal.endofavailability >= :endOfAvailability)
          )
        )
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':startOfAvailability', $startOfAvailability, \PDO::PARAM_STR);
    $stmt->bindValue(':endOfAvailability', $endOfAvailability, \PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }
}
