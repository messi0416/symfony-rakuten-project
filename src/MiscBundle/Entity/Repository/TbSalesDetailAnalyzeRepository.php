<?php

namespace MiscBundle\Entity\Repository;

use AppBundle\Form\Entity\SalesResearchVendorStockoutTermTypeEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Entity\TbSalesDetailAnalyze;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Entity\VSalesVoucher;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Plusnao\MainBundle\Form\Entity\SalesRankingSearchTypeEntity;
use Plusnao\MainBundle\Form\Type\SalesRankingSearchType;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * TbSalesDetailAnalyzeRepository
 *
 */
class TbSalesDetailAnalyzeRepository extends BaseRepository
{
  const SALES_RANKING_MAX_DISPLAYS = 150;

  /**
   * @param array $requestData
   * @param bool $addSireName
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getSalesRankingList($requestData, $addSireName = false)
  {
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getConnection('main');
    /** @var \MiscBundle\Util\DbCommonUtil $dbUtil */
    $dbUtil = $this->getContainer()->get('misc.util.db_common');

    $dateAStart = $requestData['dateAStart'];
    $dateAEnd = $requestData['dateAEnd'];
    $userId = $requestData['userId'];

    // 期間中に1日でも担当した商品一覧
    $responsibleProducts = $userId ? $this->findResponsibleProducts($requestData) : [];
    if ($userId && empty($responsibleProducts)) {
      return [];
    }

    $rankings = $this->findProductRankings($requestData, $addSireName);
    // 担当商品による絞り込み
    // findProductRankingsのクエリに結合するより、PHPで絞り込む方が速かった。
    // http://tk2-217-18298.vs.sakura.ne.jp/issues/185703#note-8
    if ($userId) {
      $rankings = $this->limitRankingsToResponsibleProducts($rankings, $responsibleProducts);
    }

    $rowTemplate = [
        'rank'                 => 0
      , 'daihyo_syohin_code'   => ''
      , 'daihyo_syohin_name'   => ''
      , 'thumbnail'            => ''
      , 'genka_tanka'          => 0
      , 'baika_tanka'          => 0
      , 'item_num'             => 0
      , 'voucher_num'          => 0
      , 'sales_amount'         => 0
      , 'item_num_a'           => 0
      , 'voucher_num_a'        => 0
      , 'sales_amount_a'       => 0
      , 'review_num'           => 0
      , 'review_point_average' => 0.0
      , 'big_category'         => ''
      , 'mid_category'         => ''
      , 'sire_code'            => ''
      , 'sire_name'            => ''
      , 'detail_url'           => ''
    ];
    $rank = 1;
    $syohinCodes = []; // A期間取得用
    $result = [];
    foreach ($rankings as $row) {
      $item = array_merge($rowTemplate, $row);
      $daihyoSyohinCode = $item['daihyo_syohin_code'];
      $item['rank'] = $rank++;
      $item['detail_url'] = $dbUtil->getRakutenProductDetailUrl($daihyoSyohinCode);
      $item['analyze_url'] = $dbUtil->getNintProductDetailUrl($daihyoSyohinCode);
      $result[$daihyoSyohinCode] = $item;
      $syohinCodes[] = $db->quote($daihyoSyohinCode, \PDO::PARAM_STR);
    }

    if ($dateAStart && $dateAEnd && count($syohinCodes)) {
      $syohinCodeList = implode(',', $syohinCodes);
      $sql = <<<EOD
        SELECT
            M.daihyo_syohin_code
          , SUM(A.受注数)              AS item_num
          , COUNT(DISTINCT A.伝票番号) AS voucher_num
          , SUM(A.小計)                AS sales_amount
        FROM tb_sales_detail_analyze A
        INNER JOIN tb_mainproducts M ON A.daihyo_syohin_code = M.daihyo_syohin_code
        WHERE A.受注日 BETWEEN :dateAStart AND :dateAEnd
          AND A.キャンセル区分   = '0'
          AND A.明細行キャンセル = '0'
          AND A.daihyo_syohin_code IN ( {$syohinCodeList} )
        GROUP BY A.daihyo_syohin_code
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':dateAStart', $dateAStart, \PDO::PARAM_STR);
      $stmt->bindValue(':dateAEnd', $dateAEnd, \PDO::PARAM_STR);
      $stmt->execute();

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $code = $row['daihyo_syohin_code'];
        if (isset($result[$code])) {
          $result[$code]['item_num_a'] = $row['item_num'];
          $result[$code]['voucher_num_a'] = $row['voucher_num'];
          $result[$code]['sales_amount_a'] = $row['sales_amount'];
        }
      }
    }
    return $result;
  }

  /**
   * 取得期間・比較期間いずれかに1日でも担当者だった商品一覧を取得。
   * 
   * 後に扱いやすように、代表商品コードをキー、値をダミー('_')とした連想配列を返却し、
   * 担当者の指定が無い場合は空配列を返却する。（検索速度優先）
   * @param array $conditions
   * @return array
   */
  private function findResponsibleProducts($requestData)
  {
    $dateAStart = $requestData['dateAStart'];
    $dateAEnd = $requestData['dateAEnd'];
    $dateBStart = $requestData['dateBStart'];
    $dateBEnd = $requestData['dateBEnd'];
    $userId = $requestData['userId'];

    $addDateAWhere = '';
    if ($dateAStart && $dateAEnd) {
      $addDateAWhere = <<<EOD
        OR (
          apply_start_date <= :dateAEnd
          AND (apply_end_date IS NULL OR apply_end_date >= :dateAStart)
        )
EOD;
    }
    $sql = <<<EOD
      SELECT
        DISTINCT daihyo_syohin_code, '_'
      FROM
        tb_product_sales_account
      WHERE
        user_id = :userId
        AND status = :registration
        AND (
          (
            apply_start_date <= :dateBEnd
            AND (apply_end_date IS NULL OR apply_end_date >= :dateBStart)
          )
          {$addDateAWhere}
        )
EOD;
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getConnection('main');
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
    $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_INT);
    $stmt->bindValue(':dateBStart', $dateBStart, \PDO::PARAM_STR);
    $stmt->bindValue(':dateBEnd', $dateBEnd, \PDO::PARAM_STR);
    if ($dateAStart && $dateAEnd) {
      $stmt->bindValue(':dateAStart', $dateAStart, \PDO::PARAM_STR);
      $stmt->bindValue(':dateAEnd', $dateAEnd, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /**
   * 条件に合う商品ランキングを取得
   * @param array $requestData
   * @param bool $addSireName
   * @return array
   */
  private function findProductRankings($requestData, $addSireName)
  {
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getConnection('main');
    /** @var \MiscBundle\Util\DbCommonUtil $dbUtil */
    $dbUtil = $this->getContainer()->get('misc.util.db_common');

    $dateBStart = $requestData['dateBStart'];
    $dateBEnd = $requestData['dateBEnd'];
    $userId = $requestData['userId'];
    $rankingTarget = $requestData['rankingTarget'];
    $bigCategory = $requestData['bigCategory'];
    $midCategory = $requestData['midCategory'];
    $keyword = $requestData['keyword'];

    // ランキング対象により分岐
    switch ($rankingTarget) {
      case SalesRankingSearchType::RANKING_TARGET_SALES_AMOUNT:
        $targetName = 'sales_amount';
        break;
      case SalesRankingSearchType::RANKING_TARGET_ITEM_NUM:
        $targetName = 'item_num';
        break;
      case SalesRankingSearchType::RANKING_TARGET_VOUCHER_NUM:
        $targetName = 'voucher_num';
        break;
      default:
        throw new RuntimeException('ランキング対象が正しくありません。');
    }

    $orderBy = sprintf(" ORDER BY %s DESC ", $targetName);

    // カテゴリ
    $categoryWhere = '';
    $categoryWhereParam = '';
    if ($bigCategory) {
      $categoryWhere = sprintf(" AND D.rakutencategories_1 LIKE :category ");
      if ($midCategory) {
        $likeStr = $bigCategory . '\\\\' . $midCategory;
      } else {
        $likeStr = $bigCategory;
      }
      $categoryWhereParam = $dbUtil->escapeLikeString($likeStr) . '%';
    }

    // キーワード
    $keywordWhere = '';
    $keywordWhereParams = [];
    if ($keyword) {
      $keywordWhere = ' AND (M.daihyo_syohin_code LIKE :keyword OR M.daihyo_syohin_name LIKE :keywordLike) ';
      $keywordWhereParams = [
          ':keyword' => $dbUtil->escapeLikeString($keyword) . '%'
        , ':keywordLike' => '%' . $dbUtil->escapeLikeString($keyword) . '%'
      ];
    }

    // 商品売上担当者を限定しない場合は、必要以上にデータ取得する必要が無いのでLIMIT句追加
    $limit = '';
    if (!$userId) {
      $limit = 'LIMIT ' . self::SALES_RANKING_MAX_DISPLAYS;
    }

    // 仕入先名 取得判定
    // フォレストスタッフのログイン（symfony_users）では仕入先名も取得する
    $sireNameColumn = $addSireName ? "V.sire_name" : "''";

    $sql = <<<EOD
      SELECT
          M.daihyo_syohin_code
        , M.daihyo_syohin_name
        ,   CAL.genka_tnk_ave
          + CAST(M.additional_cost AS SIGNED)
          + CAL.fixed_cost          AS genka_tanka
        , IR.baika_tanka            AS baika_tanka
        , SUM(A.受注数)              AS item_num
        , COUNT(DISTINCT A.伝票番号) AS voucher_num
        , SUM(A.小計)                AS sales_amount
        , CASE WHEN (M.商品画像P1Adress IS NOT NULL AND M.商品画像P1Adress <> '') THEN 商品画像P1Adress
                WHEN (M.商品画像P2Adress IS NOT NULL AND M.商品画像P2Adress <> '') THEN 商品画像P2Adress
                WHEN (M.商品画像P3Adress IS NOT NULL AND M.商品画像P3Adress <> '') THEN 商品画像P3Adress
                WHEN (M.商品画像P4Adress IS NOT NULL AND M.商品画像P4Adress <> '') THEN 商品画像P4Adress
                WHEN (M.商品画像P5Adress IS NOT NULL AND M.商品画像P5Adress <> '') THEN 商品画像P5Adress
                WHEN (M.商品画像P6Adress IS NOT NULL AND M.商品画像P6Adress <> '') THEN 商品画像P6Adress
                ELSE ''
          END AS thumbnail
        , COALESCE(CAL.review_num, 0) AS review_num
        , COALESCE(CAL.review_point_ave, 0) AS review_point_average

        , SUBSTRING_INDEX(D.rakutencategories_1, '\\\\', 1) AS big_category
        , REPLACE (
              SUBSTRING(
                  SUBSTRING_INDEX(D.rakutencategories_1, '\\\\', 2)
                , CHAR_LENGTH(
                  SUBSTRING_INDEX(D.rakutencategories_1, '\\\\', 1)
                ) + 1
              )
              , '\\\\'
              , ''
        ) AS mid_category
        , V.sire_code
        , {$sireNameColumn} AS sire_name
      FROM tb_sales_detail_analyze A
      INNER JOIN tb_mainproducts M ON A.daihyo_syohin_code = M.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal CAL ON A.daihyo_syohin_code = CAL.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory D ON M.NEディレクトリID = D.NEディレクトリID
      LEFT JOIN  tb_vendormasterdata V ON M.sire_code = V.sire_code
      LEFT JOIN (
        SELECT
            daihyo_syohin_code
          , MAX(baika_tanka) AS baika_tanka
        FROM tb_rakuteninformation
        GROUP BY daihyo_syohin_code
      ) AS IR ON M.daihyo_syohin_code = IR.daihyo_syohin_code
      WHERE A.受注日 BETWEEN :dateBStart AND :dateBEnd
        AND A.キャンセル区分   = '0'
        AND A.明細行キャンセル = '0'
        /* カテゴリ */
        {$categoryWhere}
        {$keywordWhere}
      GROUP BY A.daihyo_syohin_code
      {$orderBy}
      {$limit}
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':dateBStart', $dateBStart, \PDO::PARAM_STR);
    $stmt->bindValue(':dateBEnd', $dateBEnd, \PDO::PARAM_STR);

    // カテゴリ条件付き
    if ($categoryWhere) {
      $stmt->bindValue(':category', $categoryWhereParam, \PDO::PARAM_STR);
    }
    // キーワード条件付き
    if ($keywordWhere) {
      $stmt->bindValue(':keyword', $keywordWhereParams[':keyword'], \PDO::PARAM_STR);
      $stmt->bindValue(':keywordLike', $keywordWhereParams[':keywordLike'], \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * ランキング一覧を担当商品に限定する
   * @param array $rankings ランキング一覧
   * @param array $responsibleProducts 担当商品一覧
   * @return array
   */
  private function limitRankingsToResponsibleProducts($rankings, $responsibleProducts)
  {
    $count = 0;
    $list = [];
    foreach ($rankings as $item) {
      if (isset($responsibleProducts[$item['daihyo_syohin_code']])) {
        $list[] = $item;
        $count++;
        if ($count >= self::SALES_RANKING_MAX_DISPLAYS) {
          break;
        }
      }
    }
    return $list;
  }

  /**
   * 仕入先別欠品率 一覧
   * @param SalesResearchVendorStockoutTermTypeEntity $term
   * @return array
   */
  public function getVendorStockoutList(SalesResearchVendorStockoutTermTypeEntity $term)
  {
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getConnection('main');

    if (!$term->dateStart || !$term->dateEnd) {
      throw new RuntimeException('取得期間が指定されていません。');
    }

    $sql  = " SELECT  ";
    $sql .= "       sire.sire_code ";
    $sql .= "     , sire.sire_name ";
    $sql .= "     , COALESCE(SALES.profit_num, 0)          AS profit_num ";
    $sql .= "     , COALESCE(SALES.stockout_profit_num, 0) AS stockout_profit_num ";
    $sql .= "     , sire.表示順                            AS display_order ";
    $sql .= "     , COALESCE(ITEMS.item_count, 0)          AS item_count "; // 「商品数」
    $sql .= "     , COALESCE(AMOUNT.stock_amount, 0)       AS stock_amount "; // 「在庫額」
    $sql .= "     , COALESCE(ESTIMATE_COST.estimated_stock_cost, 0) AS estimated_stock_cost "; // 「仮仕入れコスト」
    $sql .= " FROM ";
    $sql .= "     tb_vendormasterdata as sire ";
    $sql .= "     LEFT JOIN ( ";
    $sql .= "         SELECT ";
    $sql .= "             sire.sire_code ";
    $sql .= "           , sire.sire_name ";
    $sql .= "           , COUNT(*) AS profit_num ";
    $sql .= "           , SUM( ";
    $sql .= "             CASE  ";
    $sql .= "               WHEN (a.キャンセル区分 = '5' OR (a.明細行キャンセル = '1' AND a.キャンセル区分 <> '1')) THEN 1  ";
    $sql .= "               ELSE 0 ";
    $sql .= "             END ";
    $sql .= "           ) AS stockout_profit_num ";
    $sql .= "         FROM ";
    $sql .= "           tb_sales_detail_analyze AS a ";
    $sql .= "           INNER JOIN tb_mainproducts AS m ON a.daihyo_syohin_code = m.daihyo_syohin_code  ";
    $sql .= "           INNER JOIN tb_vendormasterdata as sire on m.sire_code = sire.sire_code  ";
    $sql .= "         WHERE ";
    $sql .= "                 sire.取引状態 = 0 "; // 取引中のみ取得
    $sql .= "             AND a.受注日 BETWEEN :dateStart AND :dateEnd ";
    $sql .= "             AND a.daihyo_syohin_code <> ''  ";
    $sql .= "         GROUP BY ";
    $sql .= "             sire.sire_code ";
    $sql .= "     ) AS SALES ON sire.sire_code = SALES.sire_code  ";

    // 商品件数、在庫金額取得
    // こちらは販売中の「代表商品」数
    $sql .= "   LEFT JOIN (  ";
    $sql .= "     SELECT ";
    $sql .= "       m.sire_code ";
    $sql .= "       , COUNT(m.daihyo_syohin_code) as item_count  ";
    $sql .= "     FROM ";
    $sql .= "       tb_mainproducts AS m  ";
    $sql .= "       INNER JOIN tb_mainproducts_cal AS cal  ";
    $sql .= "         ON m.daihyo_syohin_code = cal.daihyo_syohin_code  ";
    $sql .= "     WHERE ";
    $sql .= "       cal.endofavailability IS NULL  ";
    $sql .= "     GROUP BY ";
    $sql .= "       m.sire_code ";
    $sql .= "   ) AS ITEMS ON sire.sire_code = ITEMS.sire_code  ";

    // こちらはフリー在庫の商品金額合計
    $sql .= "   LEFT JOIN ( ";
    $sql .= "     SELECT ";
    $sql .= "         m.sire_code ";
    $sql .= "       , TRUNCATE(SUM(  ";
    $sql .= "           ((cal.genka_tnk_ave + m.additional_cost) * (1 + sire.additional_cost_rate / 100) + cal.fixed_cost) * choice.フリー在庫数 ";
    $sql .= "         ), 0) AS stock_amount ";
    $sql .= "     FROM ";
    $sql .= "       tb_mainproducts AS m  ";
    $sql .= "       INNER JOIN tb_vendormasterdata as sire  ";
    $sql .= "         ON m.sire_code = sire.sire_code   ";
    $sql .= "       INNER JOIN tb_mainproducts_cal as cal  ";
    $sql .= "         ON m.daihyo_syohin_code = cal.daihyo_syohin_code  ";
    $sql .= "       INNER JOIN tb_productchoiceitems as choice  ";
    $sql .= "         ON m.daihyo_syohin_code = choice.daihyo_syohin_code ";
    // $sql .= "     WHERE sire.取引状態 = 0  ";
    $sql .= "     GROUP BY ";
    $sql .= "       m.sire_code ";
    $sql .= "   ) AS AMOUNT ON sire.sire_code = AMOUNT.sire_code  ";

    // 仮仕入れコスト
    $sql .= "   LEFT JOIN (  ";
    $sql .= "     SELECT  ";
    $sql .= "         m.sire_code  ";
    $sql .= "       , TRUNCATE(SUM(   ";
    $sql .= "           (m.genka_tnk + m.additional_cost + cal.fixed_cost) * C.non_stock_choices ";
    $sql .= "         ), 0) AS estimated_stock_cost  ";
    $sql .= "     FROM  ";
    $sql .= "       tb_mainproducts AS m   ";
    $sql .= "       INNER JOIN tb_vendormasterdata   AS sire   ON m.sire_code = sire.sire_code ";
    $sql .= "       INNER JOIN tb_mainproducts_cal   AS cal    ON m.daihyo_syohin_code = cal.daihyo_syohin_code ";
    $sql .= "       INNER JOIN ( ";
    $sql .= "           SELECT  ";
    $sql .= "               p.daihyo_syohin_code  ";
    $sql .= "             , COUNT(*) AS non_stock_choices ";
    $sql .= "           FROM tb_productchoiceitems p ";
    $sql .= "           INNER JOIN tb_mainproducts_cal cal ON p.daihyo_syohin_code = cal.daihyo_syohin_code  ";
    $sql .= "           WHERE p.受発注可能フラグ <> 0  ";
    $sql .= "             AND p.フリー在庫数 = 0  ";
    $sql .= "             AND cal.endofavailability IS NULL  ";
    $sql .= "           GROUP BY p.daihyo_syohin_code  ";
    $sql .= "       ) AS C ON m.daihyo_syohin_code = C.daihyo_syohin_code  ";
    $sql .= "     GROUP BY  ";
    $sql .= "       m.sire_code  ";
    $sql .= "   ) AS ESTIMATE_COST ON sire.sire_code = ESTIMATE_COST.sire_code ";

    $sql .= " WHERE sire.取引状態 = 0 "; // 取引中のみ取得
    $sql .= " ORDER BY sire.表示順 ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':dateStart', $term->dateStart->format('Y-m-d'));
    $stmt->bindValue(':dateEnd', $term->dateEnd->format('Y-m-d'));
    $stmt->execute();

    $result = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result[] = $row;
    }

    return $result;
  }

  /**
   * 同梱漏れ受注一覧取得
   * @param array $conditions
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getUncombinedOrderList($conditions = [])
  {
    $dbMain = $this->getConnection('main');

    $params = [];
    $addSqlShop = '';
    $addSqlStatuses = '';

    if (isset($conditions['shop']) && strlen($conditions['shop'])) {
      $addSqlShop = " AND a.店舗コード = :neMallId ";
      $params[':neMallId'] = intval($conditions['shop']);
    }
    if (isset($conditions['readyOnly']) && strlen($conditions['readyOnly']) && boolval($conditions['readyOnly'])) {
      $addSqlStatuses = " AND 準備未完了数 = 0 ";
    }

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');
    $mallPpm = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_PPM);

    // パフォーマンスのため tb_sales_detail_analyze で絞込JOIN
    $sql = <<<EOD
      SELECT
          d.`送り先名`                    AS 送り先名
        , d.`送り先（住所1+住所2）`        AS 送り先住所
        , COUNT(DISTINCT a.`伝票番号`)    AS 同梱件数
        , SUM(CASE
            WHEN a.`受注状態` <> '納品書印刷待ち' THEN 1 ELSE 0
          END) AS 準備未完了数
        , GROUP_CONCAT(DISTINCT CONCAT(a.`伝票番号`, ' [' , a.店舗名, ']') ORDER BY a.伝票番号 SEPARATOR '\\n') AS 伝票番号
      FROM tb_sales_detail d
      INNER JOIN tb_sales_detail_analyze a ON d.伝票番号 = a.伝票番号 AND d.明細行 = a.明細行
      WHERE a.`キャンセル区分` = '0'
        AND a.`明細行キャンセル` = '0'
        AND a.`受注状態` IN ( '起票済(CSV/手入力)', '納品書印刷待ち' )
        AND a.`購入者名` <> 'クルーズ株式会社'
        AND ( /* ポンパレのみ、 */
             a.店舗コード <> :neMallIdPpm
          OR (
                  d.`購入者名` = d.`送り先名`
              AND d.`購入者カナ` = d.`送り先カナ`
              AND d.`購入者電話番号` = d.`送り先電話番号`
              AND d.`購入者郵便番号` = d.`送り先郵便番号`
              AND d.`購入者住所1` = d.`送り先住所1`
              AND d.`購入者住所2` = d.`送り先住所2`
          )
        )
        {$addSqlShop}
      GROUP BY d.`送り先名`
             , d.`送り先（住所1+住所2）`
      HAVING 同梱件数 > 1
        {$addSqlStatuses}
      ORDER BY MIN(a.`伝票番号`)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallIdPpm', $mallPpm->getNeMallId(), \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $list;
  }



  /**
   * FBAマルチ商品混合受注一覧取得
   * @param array $conditions
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getFbaMultiIncludedOrderList($conditions = [])
  {
    $dbMain = $this->getConnection('main');

    $params = [];
//    $addSqlShop = '';
//    $addSqlStatuses = '';
//
//    if (isset($conditions['shop']) && strlen($conditions['shop'])) {
//      $addSqlShop = " AND a.店舗コード = :neMallId ";
//      $params[':neMallId'] = intval($conditions['shop']);
//    }

    $sql = <<<EOD
      SELECT
          a.`伝票番号`
        , a.`店舗名`
        , DATE_FORMAT(a.`受注日`, '%Y-%m-%d') AS 受注日
        , a.`受注状態`
        , COUNT(*) AS 明細件数
        , SUM(CASE WHEN m.fba_multi_flag = 0 THEN 0 ELSE 1 END) AS fba_multi_count
      FROM tb_sales_detail_analyze a
      INNER JOIN tb_mainproducts m ON a.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE a.`受注状態` <> '出荷確定済（完了）'
        AND a.`キャンセル区分` = '0'
        AND a.`明細行キャンセル` = '0'
      GROUP BY  a.`伝票番号`
              , a.`店舗名`
              , DATE_FORMAT(a.`受注日`, '%Y-%m-%d')
              , a.`受注状態`
      HAVING fba_multi_count > 0
         AND 明細件数 <> fba_multi_count
      ORDER BY a.`受注日`, a.`伝票番号`
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

    /**
     * 指定期間の商品別受注数取得
     * @param DateTime $order_date_start
     * @param DateTime $order_date_end
     * retrun
     */
    public function getSumVoucherByOrderDate($order_date_start, $order_date_end)
    {
        $db = $this->getConnection('main');
        $sql = <<<EOD
        SELECT
           A.商品コード（伝票）, SUM(A.受注数) AS SUM
        FROM tb_sales_detail_analyze A
        WHERE A.受注日 BETWEEN :dateAStart AND :dateAEnd
          AND A.キャンセル区分   = '0'
          AND A.明細行キャンセル = '0'
        GROUP BY A.商品コード（伝票）
        HAVING SUM(A.受注数) > 0
        ORDER BY A.商品コード（伝票）
EOD;

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':dateAStart', $order_date_start , \PDO::PARAM_STR);
        $stmt->bindValue(':dateAEnd'  , $order_date_end , \PDO::PARAM_STR);
        $stmt->execute();
        $stmt = $stmt->fetchAll();
        return $stmt;
        
    }

    /**
     * @param int $order_date
     * @return VSalesVoucher
     */
    public function getVoucherByCondition($scheduled_shipping_date_start, $scheduled_shipping_date_end)
    {
        $db = $this->getConnection('main');
        if (strlen($scheduled_shipping_date_start)) {
            $scheduled_shipping_date_end = (new \DateTime())->format('Y-m-d');
        }
        $shipment_confirmed_date_start = (new \DateTime())->format('Y-m-d');
        $shipment_confirmed_date_end = (new \DateTime())->modify('-1 year')->format('Y-m-d');

        $sql = <<<EOD
        SELECT
           *
        FROM tb_sales_detail_analyze A
        WHERE  A.キャンセル区分   = '0'
          AND A.明細行キャンセル = '0'
          AND A.出荷予定日 BETWEEN :ScheduledShippingDateStart AND :ScheduledShippingDateEnd
          AND A.明細行 = '1'
          AND A.受注数 = '1'
          AND A.店舗名 NOT LIKE '%YahooPlusNao%'
          AND A.店舗名 NOT LIKE '%Yahoo(おとりよせ.com）%'
          AND A.店舗名 NOT LIKE '%Amazon%'
          AND A.店舗名 NOT LIKE '%Wowma%'
          AND A.店舗名 NOT LIKE '%ポンパレ%'
          AND A.発送方法 NOT LIKE '%ゆうパケット%'
          AND A.発送方法 NOT LIKE '%定形外郵便%'
          AND A.発送方法 NOT LIKE '%クリックポスト%'
          AND A.発送方法 NOT LIKE '%佐川急便%'
          AND A.発送方法 NOT LIKE '%定形外郵便%'
          AND A.出荷確定日 BETWEEN :ShipmentConfirmedDateStart AND :ShipmentConfirmedDateAEnd
          AND A.受注番号 NOT LIKE '%-fk%'
          AND A.受注番号 NOT LIKE '%-bk%'
        ORDER BY A.daihyo_syohin_code ASC, A.出荷確定日 DESC, A.伝票番号 DESC
EOD;

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':ScheduledShippingDateStart', $scheduled_shipping_date_start , \PDO::PARAM_STR);
        $stmt->bindValue(':ScheduledShippingDateEnd'  , $scheduled_shipping_date_end , \PDO::PARAM_STR);
        $stmt->bindValue(':ShipmentConfirmedDateStart'  , $shipment_confirmed_date_start , \PDO::PARAM_STR);
        $stmt->bindValue(':ShipmentConfirmedDateAEnd'  , $shipment_confirmed_date_end , \PDO::PARAM_STR);
        $stmt->execute();
        $stmt = $stmt->fetchAll();
        return $stmt;

    }

    /**
     * 有効な単品購入の受注情報について、発送方法を取得し、商品コード昇順、出荷確定日昇順、伝票番号昇順に並べたEntityのリストとして返却します。
     * <p>
     * このメソッドでは取得するカラムを制限しているため、結果は連想配列の配列となり、個々のレコードは以下のキーを持ちます。
     * ・ne_syohin_syohin_code（商品コード（伝票））
     * ・daihyo_syohin_code（daihyo_syohin_code：代表商品コード）
     * ・shipping_method_code（配送方法コード）
     * ・canceled（キャンセル区分）
     * ・detail_canceled（明細行キャンセル）
     * ・shipping_date（出荷確定日）
     * ・voucher_number（伝票番号）
     * ・order_account_in_charge（受注担当者）
     *
     * また、詳細な取得条件は以下の通りです。
     * 出荷確定済のみ
     * 対象サイトは以下の通り
     * ・楽天：YahooPlusNao：Yahooおとりよせ：Amazon：Wowma：ポンパレ：Q10
     * ・同梱伝票（有効な明細行数が2以上の伝票）は除く（※キャンセルを含めて2件以上ならば同梱として扱う）
     * ・複数個購入伝票（明細行に2個以上の受注があるもの）は除く（※キャンセルを含めて2件以上ならば同梱として扱う）
     * ・受注番号に-fk -bk が付いているものは除く
     * ・代表商品コードがないものは除く（2018-10～2018-12のデータにいくつか見られます）
     * ・受注担当者が「API接続ID」
     *
     * @param \DateTime $startDate 検索対象の出荷確定日の始点（この日を含む）（nullable）
     * @param \DateTime $endDate 検索対象の出荷確定日の終点（この日を含む）（nullable）
     * @param int $startVoucharNumber 検索対象の伝票番号の始点（この番号を含む）、指定がない場合0（not null)
     * @param int $endVoucharNumber 検索対象の伝票番号の終点（この番号を含む）指定がない場合0（not null)
     */
    public function getSingleItemPurchaseShippingMethod(
        \DateTime $startDate = null, \DateTime $endDate = null, int $startVoucharNumber = null, int $endVoucherNumber = null) {
      /** @var BatchLogger $logger */
      $logger = $this->getContainer()->get('misc.util.batch_logger');
      /** @var EntityManager $em */
      $em = $this->getEntityManager();

      $paramArray = array();
      $dql = 'SELECT a.voucher_number, a.ne_syohin_syohin_code, a.daihyo_syohin_code, a.shipping_method_code, a.canceled, a.detail_canceled, a.shipping_date, a.special_note'
              . ' FROM MiscBundle:TbSalesDetailAnalyze a'
              . ' INNER JOIN MiscBundle:TbSalesDetail d WITH a.voucher_number = d.voucher_number AND a.line_number = d.line_number'
              . ' WHERE a.order_status = :orderStatus'
              . ' AND a.ne_mall_id IN (:neMallIdRakuten, :neMallIdMotto, :neMallIdLaforest, :neMallIdDolcissimo, :neMallIdGekipla, :neMallIdWowma, :neMallIdAmazon, :neMallIdYahoo, :neMallIdQoo10, :neMallIdPpm, :neMallIdOtoriyose)'
              . ' AND a.order_number NOT LIKE :fkOrderNumber AND a.order_number NOT LIKE :bkOrderNumber'
              . ' AND a.daihyo_syohin_code != :daihyoSyohinCode'
              . ' AND d.order_account_in_charge = :apiConnectIdName';
      $paramArray['orderStatus'] = TbSalesDetail::ORDER_STATUS_VALUE_FIX;
      $paramArray['neMallIdRakuten'] = TbShoppingMall::NE_MALL_ID_RAKUTEN;
      $paramArray['neMallIdMotto'] = TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
      $paramArray['neMallIdLaforest'] = TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
      $paramArray['neMallIdDolcissimo'] = TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
      $paramArray['neMallIdGekipla'] = TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
      $paramArray['neMallIdWowma'] = TbShoppingMall::NE_MALL_ID_WOWMA;
      $paramArray['neMallIdAmazon'] = TbShoppingMall::NE_MALL_ID_AMAZON;
      $paramArray['neMallIdYahoo'] = TbShoppingMall::NE_MALL_ID_YAHOO;
      $paramArray['neMallIdQoo10'] = TbShoppingMall::NE_MALL_ID_QTEN;
      $paramArray['neMallIdPpm'] = TbShoppingMall::NE_MALL_ID_PPM;
      $paramArray['neMallIdOtoriyose'] = TbShoppingMall::NE_MALL_ID_OTORIYOSE;
      $paramArray['fkOrderNumber'] = '%-fk%';
      $paramArray['bkOrderNumber'] = '%-bk%';
      $paramArray['daihyoSyohinCode'] = '';
      $paramArray['apiConnectIdName'] = TbSalesDetail::ORDER_ACCOUNT_IN_CHARGE_API;

      if (! empty($startDate)) {
        $dql .= ' AND a.shipping_date >= :startDate';
        $paramArray['startDate'] = $startDate->format('Y-m-d H:i:s');
      }
      if (! empty($endDate)) {
        $dql .= ' AND a.shipping_date <= :endDate';
        $paramArray['endDate'] = $endDate->format('Y-m-d H:i:s');
      }
      if (! empty($startVoucharNumber)) {
        $dql .= ' AND a.voucher_number >= :startVoucherNumber';
        $paramArray['startVoucherNumber'] = $startVoucharNumber;
      }
      if (! empty($endVoucherNumber)) {
        $dql .= ' AND a.voucher_number <= :endVoucherNumber';
        $paramArray['endVoucherNumber'] = $endVoucherNumber;
      }
      $dql .= ' GROUP BY a.voucher_number'
              . ' HAVING count(a) = 1 AND SUM(a.ordered_num) = 1'
              . ' ORDER BY a.daihyo_syohin_code, a.shipping_date, a.voucher_number';

      $query = $em->createQuery($dql);

      foreach ($paramArray as $key => $value) {
        $query->setParameter($key, $value);
      }
      return $query->getResult();
    }
    
    /**
     * 現在出荷待ちの受注の平均明細数を取得する。
     * 
     * 現時点では検索条件指定なしで、以下の条件に合致するものを取得する。
     * ・受注ステータスが「納品書印刷待ち・納品書印刷済み」で、未キャンセル
     * ・販売モールがSHOPLISTではない
     * 
     * 戻り値は以下の項目の連想配列。
     * ・detail_average 平均明細数
     * 
     * @return array 平均明細数の連想配列
     */
    public function getDetailNumAverage() {
      $db = $this->getConnection('main');
      $sql = <<<EOD
        SELECT 
          count(*) / count(distinct 伝票番号) as detail_average
        FROM tb_sales_detail_analyze
        WHERE 受注状態 IN (:orderStatusWaitPrint, :orderStatusPrinted) 
        AND キャンセル区分 = 0 AND 明細行キャンセル = 0
        AND 店舗コード <> :mallIdShoplist
EOD;
      $stmt = $db->prepare($sql);
      
      $stmt->bindValue(':mallIdShoplist', TbShoppingMall::NE_MALL_ID_SHOPLIST, \PDO::PARAM_STR);
      $stmt->bindValue(':orderStatusWaitPrint', TbSalesDetail::ORDER_STATUS_WAIT_PRINT, \PDO::PARAM_STR);
      $stmt->bindValue(':orderStatusPrinted', TbSalesDetail::ORDER_STATUS_PRINTED, \PDO::PARAM_STR);
      $stmt->execute();
      $result = $stmt->fetch(\PDO::FETCH_ASSOC);
      return $result;
    }
    
    
    /**
     * 指定されたSKUリストの受注数を取得して返却する。
     * 
     * ※2023/06現在は、出荷済みやキャンセルも含め、過去の全受注数量を返却している。
     * 「未出荷のみ」「キャンセルは除く」「受注数量ではなく明細数」などの要件が出た場合は、引数に $conditionを追加して使い分けることを想定。
     * 
     * @param array $skuList 受注数を取得するSKUの配列
     * @return SKUコードをキー、受注数を値とする連想配列
     */
    public function findSalesQuantity($skuList) {
      $skuListStr = "'" . implode("', '", $skuList) . "'";
      $sql = <<<EOD
      SELECT
        pci.ne_syohin_syohin_code
        , SUM(IFNULL(a.受注数, 0)) as sales_quantity
      FROM
        tb_productchoiceitems pci
        LEFT JOIN tb_sales_detail_analyze a ON pci.ne_syohin_syohin_code = a.商品コード（伝票）
      WHERE pci.ne_syohin_syohin_code IN ($skuListStr)
      GROUP BY a.商品コード（伝票）
EOD;
      $stmt = $this->getConnection('main')->prepare($sql);
      $stmt->execute();
      return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
