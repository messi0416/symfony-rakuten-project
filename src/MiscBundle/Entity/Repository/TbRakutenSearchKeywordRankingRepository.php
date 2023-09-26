<?php
namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbRakutenSearchKeyword;
use MiscBundle\Entity\TbRakutenSearchKeywordRanking;
use MiscBundle\Util\DbCommonUtil;

/**
 * 楽天検索キーワードランキングリポジトリ
 * TbRakutenSearchKeywordRankingRepository
 * @author a-jinno
 */
class TbRakutenSearchKeywordRankingRepository extends BaseRepository
{

  /**
   * 1日ぶんのランキングを全件削除する。
   * @param Datetime $rankingDate 削除対象日。
   */
  public function deleteByDate($rankingDate) {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $qb = $em->createQueryBuilder();
    $qb
      ->delete('MiscBundle:TbRakutenSearchKeywordRanking', 'r')
      ->where('r.rankingDate = :rankingDate')
      ->setParameter('rankingDate', $rankingDate->format('Y-m-d'));
    $result = $qb->getQuery()->getResult();
  }

  /**
   * ランキングを1件登録する。
   * @param Date $date ランキング日
   * @param int $rank ランキング
   * @param string $keyword キーワード文字列。複合キーワードの場合、スペース区切りでまとめて引き渡し、このメソッド内で分割する。
   * @param BatchLogger $logger ログ
   */
  public function addRanking($rankingDate, $rank, $keyword, $logger) {

    /** @var EntityManager $em */
    $em = $this->getEntityManager();
    /** @var TbRakutenSearchKeywordRepository */
    $keywordRepository = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbRakutenSearchKeyword');

    $keywordEntity = $keywordRepository->findOneByKeyword($keyword);
    if (!$keywordEntity) {
      $keywordEntity = new TbRakutenSearchKeyword();
      $keywordEntity->setKeyword($keyword);
      $em->persist($keywordEntity);
      $em->flush();
    }
    $ranking = new TbRakutenSearchKeywordRanking();
    $ranking->setRankingDate($rankingDate);
    $ranking->setRank($rank);
    $ranking->setKeywordId($keywordEntity->getId());
    $em->persist($ranking);
  }

  /**
   * 楽天キーワード対象日リストを取得
   * @param Date $targetDate
   * @param String $limit
   * @return 対象日リスト
   */
  public function getRakutenKeywordTargetDayList($targetDate, $limit) {
    $dbMain = $this->getConnection('main');
    $params = [];
    $sql = <<<EOD
      SELECT 
        kr.rank
        , k.keyword
      FROM tb_rakuten_search_keyword_ranking kr
      INNER JOIN tb_rakuten_search_keyword k ON (kr.keyword_id = k.id)
      WHERE kr.ranking_date = :targetDate
      LIMIT :limit
EOD;
    $params[':targetDate'] =  $targetDate->format('Y-m-d');
    
    $stmt = $dbMain->prepare($sql);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->bindValue(':limit', intval($limit), \PDO::PARAM_INT);
    $stmt->execute();
    
    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }
    
    return $result;
  }

  /**
   * 楽天キーワード一覧 日付比較検索リスト取得
   * @param array $conditions
   * @return 日付比較検索リスト
   */
  public function getRakutenKeywordDateComparisonSearchList($conditions = [])
  {
    $dbMain = $this->getConnection('main');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    
    $params = [];
    
    $sql = <<<EOD
      SELECT 
        T.rank as rank
        , D.keyword as keyword1
        , T.keyword as keyword2
        , DR.rank-T.rank as fluctuation
      FROM 
      (
        SELECT 
          kr.rank
          , k.keyword
        FROM tb_rakuten_search_keyword_ranking kr
        INNER JOIN tb_rakuten_search_keyword k ON (kr.keyword_id = k.id)
        WHERE kr.ranking_date = :diffTargetDate
        LIMIT :limit
      ) D LEFT OUTER JOIN 
      (
        SELECT 
          kr.rank
          , k.keyword
        FROM tb_rakuten_search_keyword_ranking kr
        INNER JOIN tb_rakuten_search_keyword k ON (kr.keyword_id = k.id)
        WHERE kr.ranking_date = :targetDate
        LIMIT :limit
      ) T ON (D.rank = T.rank)
      LEFT OUTER JOIN 
      (
        SELECT 
          kr.rank
          , k.keyword
        FROM tb_rakuten_search_keyword_ranking kr
        INNER JOIN tb_rakuten_search_keyword k ON (kr.keyword_id = k.id)
        WHERE kr.ranking_date = :diffTargetDate
      ) DR ON (T.keyword = DR.keyword)
EOD;
    $params[':diffTargetDate'] =  $conditions['diff_target_date']->format('Y-m-d');
    $params[':targetDate'] =  $conditions['target_date']->format('Y-m-d');
    
    $stmt = $dbMain->prepare($sql);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->bindValue(':limit', intval($conditions['limit']), \PDO::PARAM_INT);
    $stmt->execute();
    
    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }
    
    return $result;
  }

  /**
   * 楽天キーワード一覧 キーワード検索リスト取得
   * @param array $conditions
   * @return キーワード検索リスト
   */
  public function getRakutenKeywordRankingKeywordSearchList($conditions = [])
  {
    $dbMain = $this->getConnection('main');
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');
    $params = [];
    $joinSql = '';
    
    $sql = 'SELECT K.keyword';
    $index = 1;
    $targetDateList = [];
    foreach ($conditions['target_date_list'] as $targetDate) {
      $targetDateList[] = "'$targetDate'";
      $sql = $sql.", TARGET_$index.rank as rank$index ";
      $joinSql = $joinSql.'LEFT OUTER JOIN (';
      $joinSqlSelect =  <<<EOD
        SELECT
        kr.rank
        , k.keyword
        FROM
        tb_rakuten_search_keyword_ranking kr
        INNER JOIN tb_rakuten_search_keyword k
        ON (kr.keyword_id = k.id)
        WHERE
        kr.ranking_date IN ('$targetDate')
EOD;
      $joinSql = $joinSql.$joinSqlSelect.")TARGET_$index  ON (K.keyword = TARGET_$index.keyword) ";
      $index++;
    }
    $targetDateSql = implode(', ', $targetDateList);
    $keywordTableSql = <<<EOD
      FROM ( 
        SELECT
          k.keyword 
        FROM
          tb_rakuten_search_keyword_ranking kr 
          INNER JOIN tb_rakuten_search_keyword k 
            ON (kr.keyword_id = k.id) 
        WHERE
          kr.ranking_date IN ($targetDateSql) 
          AND k.keyword LIKE :keyword 
        GROUP BY
          k.keyword
      ) K 
EOD;
    $sql = $sql.$keywordTableSql.$joinSql;
    $params[':keyword'] = '%' . $commonUtil->escapeLikeString($conditions['keyword']) . '%';
    $stmt = $dbMain->prepare($sql);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }
    $stmt->execute();
    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }
    return $result;
  }
}