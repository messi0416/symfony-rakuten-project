<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * TbRakutenGenreAttributeRepository
 */
class TbRakutenGenreAttributeRepository extends BaseRepository
{
  /**
   * 楽天商品属性項目マスタにデータを追加ないし更新し、その対象件数を返す。
   * 
   * @param array $attributesList ジャンルIDをキー、属性情報の配列を値に持った連想配列の配列
   * @return integer 更新件数（バルクアップデートの仕様により、
   *   UPDATEの場合は2行、INSERTの場合は1行でカウントされる。厳密な更新行数を取るには、
   *   呼び出し側で存在件数を取得し、この戻り値から減算すること。）
   */
  public function upsertRakutenGenreAttribute($attributesList)
  {
    $dbMain = $this->getConnection('main');
    $dbMainName = $dbMain->getDatabase();
    $dbTmp = $this->getConnection('tmp');
    $dbTmpName = $dbTmp->getDatabase();

    // (1) 一時テーブルを作成して、引数で指定の属性情報をそのまま投入
    $dbTmp->query('DROP TABLE IF EXISTS tmp_rakuten_genre_attribute');
    $dbTmp->query("CREATE TABLE tmp_rakuten_genre_attribute LIKE {$dbMainName}.tb_rakuten_genre_attribute");

    $insertValues = [];
    foreach ($attributesList as $genreId => $attributes) {
      foreach ($attributes as $attribute) {
        $id = $attribute['id'];
        $name = $attribute['nameJa'];
        $unit = $attribute['unit'] ?: '';
        $requiredFlg = $attribute['properties']['rmsMandatoryFlg'] ? 1 : 0;
        $deleteFlg = 0;
        $insertValues[] = "($genreId, $id, '{$name}', '{$unit}', $requiredFlg, $deleteFlg)";
      }
    }

    // 1000件ずつinsert
    $insertValuesChunk = array_chunk($insertValues, 1000);
    foreach ($insertValuesChunk as $insertValues) {
      $insertValuesStr = join(",", $insertValues);
      $sql = <<<EOD
        INSERT INTO
          tmp_rakuten_genre_attribute (
            rakuten_genre_id,
            attribute_id,
            attribute_name,
            attribute_unit,
            required_flg,
            delete_flg
          )
        VALUES
          {$insertValuesStr};
EOD;
      $stmt = $dbTmp->prepare($sql);
      $stmt->execute();
    }

    // (2) 本テーブルを追加ないし更新
    $sql = <<<EOD
      INSERT INTO tb_rakuten_genre_attribute (
        rakuten_genre_id,
        attribute_id,
        attribute_name,
        attribute_unit,
        required_flg,
        delete_flg
      )
      SELECT
        rakuten_genre_id,
        attribute_id,
        attribute_name,
        attribute_unit,
        required_flg,
        delete_flg
      FROM
        {$dbTmpName}.tmp_rakuten_genre_attribute
      ON DUPLICATE KEY UPDATE
        attribute_name = VALUES(attribute_name),
        attribute_unit = VALUES(attribute_unit),
        required_flg = VALUES(required_flg),
        delete_flg = VALUES(delete_flg)
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $upsertCount = $stmt->rowCount();

    // (3) 引数で指定の属性情報に無いレコードは削除フラグをONに更新。
    $sql = <<<EOD
      UPDATE
        tb_rakuten_genre_attribute ga
        LEFT JOIN {$dbTmpName}.tmp_rakuten_genre_attribute tga
          ON ga.rakuten_genre_id = tga.rakuten_genre_id
          AND ga.attribute_id = tga.attribute_id
      SET
        ga.delete_flg = 1
      WHERE
        ga.delete_flg = 0
        AND tga.id IS NULL
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $deleteFlgUpdateCount = $stmt->rowCount();

    return $upsertCount + $deleteFlgUpdateCount;
  }

  /**
   * 指定した楽天ジャンルIDの属性情報を連想配列で返す
   * @param integer $rakutenGenreId 楽天ジャンルID
   * @param boolean $onlyRequiredAttributes 必須属性に限るか
   * @return array 商品属性IDをキーにした以下の形式の連想配列
   * [
   *    1 => [
   *      'id' => 10701
   *      'name' => 'ブランド名',
   *      'unit' => '',
   *      'requiredFlg' => 1,
   *    ],
   *    20 => [
   *      'id' => 10710
   *      'name' => '本体横幅',
   *      'unit' => 'cm',
   *      'requiredFlg' => 0,
   *    ],
   *    ...
   * ]
   */
  public function findGenreAttributes($rakutenGenreId, $onlyRequiredAttributes = false)
  {
    $addWhere = '';
    if ($onlyRequiredAttributes) {
      $addWhere = 'AND ga.required_flg <> 0';
    }
    $sql = <<<EOD
      SELECT
        ga.attribute_id,
        ga.id,
        ga.attribute_name AS name,
        ga.attribute_unit AS unit,
        ga.required_flg AS requiredFlg
      FROM
        tb_rakuten_genre_attribute ga
      WHERE
        ga.rakuten_genre_id = :rakutenGenreId
        AND ga.delete_flg = 0
        {$addWhere}
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':rakutenGenreId', $rakutenGenreId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE);
  }
}
