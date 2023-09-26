<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbMainproductsImportability;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;

/**
 * TbMainproductsImportabilityRepository
 */
class TbMainproductsImportabilityRepository extends BaseRepository
{
  /**
   * 商品輸出入可否管理画面のための代表商品情報を返す。
   * description, hint, 画像は複数存在し得るので、カンマ区切りで1行に集約する
   * @param array $conditions 検索条件
   * @param int $limit 1ページに表示する件数
   * @param int $page 現在ページ数
   * @return array 全件数と商品情報のリスト
   */
  public function findList($conditions, $limit, $page)
  {
    $result = [];

    $wheres = [];
    $params = [];
    // 条件：代表商品コード
    if ($conditions['daihyoSyohinCode']) {
      $wheres[] = 'm.daihyo_syohin_code = :daihyoSyohinCode';
      $params[':daihyoSyohinCode'] = $conditions['daihyoSyohinCode'];
    }
    // 条件：カテゴリ
    if ($conditions['category']) {
      // スラッシュと全角スペースは半角スペースに置換し、連続した半角スペースは1つにまとめる
      // 現状各フィールドに、半角スペースを連続で使用したり、
      $category = str_replace(['/', '　'], ' ', $conditions['category']);
      $category = preg_replace('/\s+/', ' ', $category);
      $wheres[] = <<<EOD
        REPLACE(CONCAT(
                        pd.フィールド1, ' ',
                        pd.フィールド2, ' ',
                        pd.フィールド3, ' ',
                        pd.フィールド4, ' ',
                        pd.フィールド5
                      ), '　', ' ') LIKE :category
EOD;
        $params[':category'] = '%' . $category . '%';
    }
    // 条件：注残ステータス(SKUに選択した注残ステータスを1件以上含む)
    // isset付けないと、全解除の場合、空配列でなくキー自体存在しないことになるようでエラーになる
    if (isset($conditions['filterRemainStatusKeys'])) {
      $i = 1;
      $remainStatuses = '';
      foreach($conditions['filterRemainStatusKeys'] as $filterRemainStatusKey) {
        $params['remainStatus' . $i] = $filterRemainStatusKey;
        $remainStatuses .= ', :remainStatus' . $i;
        $i++;
      }
      $remainStatuses = substr($remainStatuses, 2);
      $wheres[] = <<<EOD
        m.daihyo_syohin_code IN (
          SELECT DISTINCT pci.daihyo_syohin_code
          FROM tb_individualorderhistory ioh
          INNER JOIN tb_productchoiceitems pci ON ioh.商品コード = pci.ne_syohin_syohin_code
          WHERE ioh.remain_status IN ({$remainStatuses})
          AND ioh.注残計 > 0)
EOD;
    }
    // 条件：ステータス
    if ($conditions['status'] !== '') {
      if ($conditions['status'] === '0') {
        $wheres[] = '(i.importability_status IS NULL OR i.importability_status = :status)';
      } else {
        $wheres[] = 'i.importability_status = :status';
      }
      $params[':status'] = $conditions['status'];
    }
    // 条件：最終更新者
    if ($conditions['updateUserName']) {
      $wheres[] = 'u.username = :updateUserName';
      $params[':updateUserName'] = $conditions['updateUserName'];
    }
    // 条件：設定日From
    if ($conditions['settingDateFrom']) {
      $settingDateFrom = new \DateTime($conditions['settingDateFrom']);
      $settingDateFrom->setTime(0, 0, 0);
      $wheres[] = 'i.status_updated >= :settingDateFrom';
      $params[':settingDateFrom'] = $settingDateFrom->format('Y-m-d H:i:s');
    }
    // 条件：設定日To
    if ($conditions['settingDateTo']) {
      $settingDateTo = new \DateTime($conditions['settingDateTo']);
      $settingDateTo->setTime(23, 59, 59);
      $wheres[] = 'i.status_updated <= :settingDateTo';
      $params[':settingDateTo'] = $settingDateTo->format('Y-m-d H:i:s');
    }
    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $dbMain = $this->getConnection('main');

    // 元々GROUP BYを使っていて、knp_paginatorでの処理は手間がかかるので独自pagination
    // 今は、knp_paginatorでできると思うが修正にかかる時間との兼ね合いで保留
    $sql = <<<EOD
      SELECT
        COUNT(DISTINCT m.daihyo_syohin_code)
      FROM
        tb_mainproducts m
        INNER JOIN tb_plusnaoproductdirectory pd
          ON m.NEディレクトリID = pd.NEディレクトリID
        LEFT JOIN tb_mainproducts_importability i
          ON m.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN symfony_users u
          ON i.status_update_account_id = u.id
      WHERE
        1 {$addWheres}
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $result['count'] = (int)$stmt->fetchColumn();

    // 代表商品情報本体（1つと限らない項目は同時に取得しようとすると重くなるので、別クエリで対応）
    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code AS daihyoSyohinCode,
        COALESCE(i.importability_status, 0) AS status,
        pd.フィールド1 AS field1,
        pd.フィールド2 AS field2,
        pd.フィールド3 AS field3,
        pd.フィールド4 AS field4,
        pd.フィールド5 AS field5,
        m.daihyo_syohin_name AS daihyoSyohinName,
        i.note
      FROM
        tb_mainproducts m
        /* PK結合なので自然にPKが使われるはずだが、なぜか本番がALLになるのでFORCE INDEXで指定 */
        INNER JOIN tb_plusnaoproductdirectory pd FORCE INDEX(PRIMARY)
          ON m.NEディレクトリID = pd.NEディレクトリID
        LEFT JOIN tb_mainproducts_importability i
          ON m.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN symfony_users u
          ON i.status_update_account_id = u.id
      WHERE
        1 {$addWheres}
      ORDER BY
        pd.フィールド1, pd.フィールド2, pd.フィールド3, pd.フィールド4, pd.フィールド5, m.daihyo_syohin_code
      LIMIT :limit OFFSET :offset
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $limit, \PDO::PARAM_INT);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $result['list'] = $this->appendListDetail($list);

    return $result;
  }

  /**
   * 商品輸出入可否管理画面のための代表商品情報に詳細を追加する。
   * @param array $list 商品情報本体
   * @return array 詳細追加後の商品情報リスト
   */
  private function appendListDetail($list) {
    $dbMain = $this->getConnection('main');

    // 代表商品情報詳細（本体とは別に、descriptions・hint・画像関連を取得）
    $daihyoSyohinCodes = [];
    foreach ($list as $row) {
      $daihyoSyohinCodes[] = $dbMain->quote($row['daihyoSyohinCode'], \PDO::PARAM_STR);
    }
    $codeListStr = implode(',', $daihyoSyohinCodes);
    if (!$codeListStr) {
      return [];
    }
    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code AS daihyoSyohinCode,
        GROUP_CONCAT(DISTINCT pci.description_en SEPARATOR ',') AS descriptions,
        GROUP_CONCAT(DISTINCT pci.hint_ja SEPARATOR ',') AS hints,
        GROUP_CONCAT(DISTINCT CASE
                                WHEN p.directory = '' OR p.filename = '' THEN ''
                                ELSE CONCAT('/', p.directory, '/', p.filename)
                              END SEPARATOR ',') AS imagePaths
      FROM
        tb_mainproducts m
        LEFT JOIN tb_productchoiceitems pci
          ON m.daihyo_syohin_code = pci.daihyo_syohin_code
        LEFT JOIN product_images p
          ON m.daihyo_syohin_code = p.daihyo_syohin_code
        LEFT JOIN product_images_attention_image pa
          ON p.md5hash = pa.md5hash
      WHERE
        (pa.attention_flg IS NULL OR pa.attention_flg = 0)
        AND m.daihyo_syohin_code IN ( {$codeListStr} )
      GROUP BY
        m.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $detail = $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);

    // 別々に取得した情報を1つにまとめる。
    return array_map(function($row) use($detail) {
      $data = $detail[$row['daihyoSyohinCode']];
      $row['descriptions'] =  explode(',', $data['descriptions']);
      $row['hints'] = explode(',', $data['hints']);
      $row['imagePaths'] = explode(',', $data['imagePaths']);
      return $row;
    }, $list);
  }
}
