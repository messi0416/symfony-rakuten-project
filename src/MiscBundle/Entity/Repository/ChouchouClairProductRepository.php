<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\ORM\QueryBuilder;
use MiscBundle\Entity\ChouchouClairProductLog;
use MiscBundle\Util\DbCommonUtil;
use Plusnao\MainBundle\Form\Entity\ChouchouClairStockListDownloadCsvTypeEntity;
use Plusnao\MainBundle\Form\Entity\ChouchouClairStockListSearchTypeEntity;

/**
 * ChouchouClairProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ChouchouClairProductRepository extends BaseRepository
{
  /**
   * 商品一覧検索 件数取得
   * @param ChouchouClairStockListSearchTypeEntity $conditions
   * @return int
   */
  public function getProductCount($conditions = null)
  {
    $qb = $this->createGetProductsQueryBuilder($conditions);
    $qb->select('COUNT(p.code) AS count');

    $count = 0;
    $result = $qb->getQuery()->getArrayResult();
    if ($result) {
      $count = $result[0]['count'];
    }
    return $count;
  }

  /**
   * 商品一覧検索取得
   * @param ChouchouClairStockListSearchTypeEntity $conditions
   * @param integer $limit
   * @return array
   */
  public function getProducts($conditions = null, $limit = null)
  {
    $qb = $this->createGetProductsQueryBuilder($conditions);

    $qb->select('p.code');
    $qb->addSelect('p.name');
    $qb->addSelect('p.branch_code');
    $qb->addSelect('p.detail');
    $qb->addSelect('p.wholesale_price');
    $qb->addSelect('p.stock');
    $qb->addSelect('p.pre_stock');
    $qb->addSelect('p.stock_modified');

    $qb->addOrderBy('p.code', 'ASC');
    $qb->addOrderBy('p.branch_code', 'ASC');

    if ($limit) {
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getArrayResult();
  }

  /**
   * 検索クエリビルダー作成
   * @param ChouchouClairStockListSearchTypeEntity $conditions
   * @return QueryBuilder
   */
  private function createGetProductsQueryBuilder($conditions = null)
  {
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');

    $qb = $this->createQueryBuilder('p');

    if ($conditions) {
      // 商品管理番号
      $code = $conditions->code;
      if (strlen($code)) {
        $qb->andWhere('p.code LIKE :code')->setParameter(':code', $commonUtil->escapeLikeString($code) . '%');
      }

      // キーワード（商品名）
      $keywords = [];
      $keyword = $conditions->keyword;
      $keyword = preg_replace('/[ 　]+/u', ' ', $keyword); // 半角スペースと全角スペースの連続を1つの半角スペースに変換
      if (trim($keyword)) {
        $keywords = explode(' ', trim($keyword));
      }
      if ($keywords) {
        foreach($keywords as $i => $word) {
          $key = sprintf('word%02d', $i);
          $qb->andWhere('p.name LIKE :' . $key)->setParameter(':' . $key, '%' . $commonUtil->escapeLikeString($word) . '%');
        }
      }

      // 修正済みのみ
      $target = $conditions->searchTarget;
      if ($target == ChouchouClairStockListSearchTypeEntity::LIST_TARGET_MODIFIED) {
        $qb->andWhere('p.stock_modified IS NOT NULL');
      }
    }

    return $qb;
  }

  /**
   * CSVダウンロード用 stmt 取得
   * @param ChouchouClairStockListDownloadCsvTypeEntity $condition
   * @return \PDOStatement
   */
  public function getDownloadCsvDataStmt($condition)
  {
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $dbMain = $this->getConnection('main');

    $fields = $this->getCsvFields();
    $fields[] = 'stock_modified'; // ログの最終取得日時に保存するために取得
    $selectStr = implode(', ', $fields);

    $wheres = [];
    $params = [];
    if ($condition->dateStart) {
      $wheres[] = 'stock_modified >= :dateStart';
      $params[':dateStart'] = $condition->dateStart->format('Y-m-d 0:0:0');
    }
    if ($condition->dateEnd) {
      $wheres[] = 'stock_modified < :dateEnd';
      $end = clone $condition->dateEnd;
      $end->modify('+1 day');
      $params[':dateEnd'] = $end->format('Y-m-d 0:0:0');
    }

    $sql = <<<EOD
      SELECT
        {$selectStr}
      FROM chouchou_clair_product
      WHERE stock_modified IS NOT NULL
EOD;
    if ($wheres) {
      $sql .= ' AND ';
      $sql .= implode(' AND ', $wheres);
    }
    $sql .= " ORDER BY stock_modified ";

    // $logger->info($sql);

    $stmt = $dbMain->prepare($sql);
    if ($params) {
      foreach($params as $k => $v) {
        $stmt->bindValue($k, $v, \PDO::PARAM_STR);
      }
    }

    $stmt->execute();
    return $stmt;
  }

  /// フィールド取得
  public function getCsvFields()
  {
    return [
        '商品管理番号'
      , '商品名'
      , 'キャッチコピー'
      , 'JANコード'
      , 'メーカー品番'
      , 'カテゴリ'
      , '掲載開始日'
      , '掲載終了日'
      , '出荷条件'
      , '良品返品'
      , 'サイズ・容量'
      , '規格'
      , 'コメント'
      , '注意事項'
      , 'スタンプ'
      , 'スタイル'
      , '検索タグ1'
      , '検索タグ2'
      , '検索タグ3'
      , '検索タグ4'
      , '検索タグ5'
      , '画像1'
      , '画像1キャプション'
      , '画像2'
      , '画像2キャプション'
      , '画像3'
      , '画像3キャプション'
      , '画像4'
      , '画像4キャプション'
      , '画像5'
      , '画像5キャプション'
      , '画像6'
      , '画像6キャプション'
      , '画像7'
      , '画像7キャプション'
      , '画像8'
      , '画像8キャプション'
      , '画像9'
      , '画像9キャプション'
      , '画像10'
      , '画像10キャプション'
      , '注文欄番号'
      , '商品管理枝番号'
      , '内訳'
      , '参考価格種別'
      , '上代価格'
      , '卸価格'
      , 'セット毎数量'
      , '在庫数'
      , '枝番号削除フラグ'
      , '価格非公開フラグ'
      , '販売中'
      , '販売方法'
      , '販売サイト'
      , '固定キーワード1'
      , '固定キーワード2'
      , '固定キーワード3'
      , 'ブランド管理ID'
      , '割引開始日時'
      , '割引終了日時'
      , '割引率'
      , '送料区分'
      , '商品個別送料'
      , 'まとめ買い割引対象商品フラグ'
      , '画像転載許可フラグ'
    ];
  }

}