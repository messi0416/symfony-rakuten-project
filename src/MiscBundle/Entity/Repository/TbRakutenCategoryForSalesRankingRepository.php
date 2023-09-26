<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbRakutenCategoryForSalesRanking;
use MiscBundle\Util\DbCommonUtil;

/**
 * TbRakutenCategoryForSalesRankingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbRakutenCategoryForSalesRankingRepository extends BaseRepository
{
  /**
   * カテゴリ一覧 再生成
   */
  public function renewRakutenCategoryForSalesRanking()
  {
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getConnection('main');

    $db->query('TRUNCATE tb_rakuten_category_for_sales_ranking');

    $sql = <<<EOD
      INSERT INTO tb_rakuten_category_for_sales_ranking (
            big_category
          , mid_category
          , display_order
      )
      SELECT
          SUBSTRING_INDEX(dir.rakutencategories_1, '\\\\', 1) AS big_category
        , REPLACE (
              SUBSTRING(
                  SUBSTRING_INDEX(dir.rakutencategories_1, '\\\\', 2)
                , CHAR_LENGTH(
                  SUBSTRING_INDEX(dir.rakutencategories_1, '\\\\', 1)
                ) + 1
              )
              , '\\\\'
              , ''
        ) AS mid_category
        , MIN(rakutencategories_1_order) AS display_order
      FROM tb_plusnaoproductdirectory dir
      INNER JOIN tb_mainproducts m ON dir.NEディレクトリID = m.NEディレクトリID
      GROUP BY big_category, mid_category
EOD;
    $db->query($sql);

    // 最終更新日時更新
    /** @var \MiscBundle\Util\DbCommonUtil $dbUtil */
    $dbUtil = $this->getContainer()->get('misc.util.db_common');
    $dbUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_RAKUTEN_CATEGORY_FOR_SALES_RANKING);
  }

  /**
   * 楽天カテゴリ 大カテゴリ 中カテゴリ取得
   *
   * [
   *   (大カテゴリ名) => [ big_category => (大カテゴリ名),  mid_category => (中カテゴリ名), display_order => (表示順) ]
   * ]
   * @return array
   */
  public function getAllForPullDown()
  {
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getConnection('main');
    /** @var \MiscBundle\Util\DbCommonUtil $dbUtil */
    $dbUtil = $this->getContainer()->get('misc.util.db_common');

    $result = [];

    // 最終更新日時取得 ＆ 本日更新していなければ更新
    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    $lastUpdated = $dbUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_RAKUTEN_CATEGORY_FOR_SALES_RANKING);
    if (!$lastUpdated || $lastUpdated < $today) {
      $this->renewRakutenCategoryForSalesRanking();
    }

    /** @var TbRakutenCategoryForSalesRanking[] $categories */
    $categories = $this->findAll();

    /** @var TbRakutenCategoryForSalesRanking[] $tmp */
    $tmp = [];
    $bigCategoryOrders = [];
    foreach ($categories as $category) {
      $tmp[] = $category;
      $bigCategory = $category->getBigCategory();
      if (!isset($bigCategoryOrders[$bigCategory])) {
        $bigCategoryOrders[$bigCategory] = [
            'display_order' => $category->getDisplayOrder()
          , 'big_category'  => $bigCategory
        ];
      } else {
        // より小さければ並び順上書き
        if ($bigCategoryOrders[$bigCategory]['display_order'] > $category->getDisplayOrder()) {
          $bigCategoryOrders[$bigCategory]['display_order'] = $category->getDisplayOrder();
        }
      }
    }

    // 大カテゴリ 並べ変え処理
    uasort($bigCategoryOrders, function($a, $b){
      if ($a['display_order'] == $b['display_order']) {
        return strcmp($a['big_category'], $b['big_category']);
      } else {
        return $a['display_order'] < $b['display_order'] ? -1 : 1;
      }
    });

    // 並び順通りに枠を作成
    // 大カテゴリ全てに空選択を追加（あるやつとないやつとがあるので揃える）
    foreach($bigCategoryOrders as $bigCategory => $x) {
      $result[$bigCategory] = [];
      $empty = [
          'big_category' => $bigCategory
        , 'mid_category' => ''
        , 'display_order' => 0
      ];
      $result[$bigCategory][] = $empty;
    }

    // 中カテゴリ
    foreach($tmp as $category) {
      $bigCategory = $category->getBigCategory();
      // 中カテゴリが空のものは うえで追加した empty 配列に代用させるためお役御免
      // ※大カテゴリの並び順のためには取得が必要。
      if ($category->getMidCategory() == '') {
        continue;
      }
      $result[$bigCategory][] = [
          'big_category' => $bigCategory
        , 'mid_category' => $category->getMidCategory()
        , 'display_order' => $category->getDisplayOrder()
      ];
    }

    // 中カテゴリ 並び替え処理
    foreach($result as $bigCategory => &$midCategories) {
      usort($midCategories, function($a, $b) {
        if ($a['display_order'] == $b['display_order']) {
          return strcmp($a['mid_category'], $b['mid_category']);
        } else {
          return $a['display_order'] < $b['display_order'] ? -1 : 1;
        }
      });
    }

    return $result;
  }

}
