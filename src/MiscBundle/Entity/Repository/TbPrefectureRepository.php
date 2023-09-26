<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use MiscBundle\Util\BatchLogger;

/**
 * 都道府県マスタリポジトリ
 * @author a-jinno
 */
class TbPrefectureRepository extends BaseRepository
{
  /**
   * 都道府県マスタを、都道府県名をキー、コードを値とする連想配列で返却する
   */
  public function getPrefectureNameMap() {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');

    $sql = "SELECT name, prefecture_cd FROM tb_prefecture";
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $map = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    return $map;
  }

  /**
   * 指定都道府県チェックカラムについて、指定都道府県の適用可否を返す。
   *
   * 指定都道府県チェックカラムや都道府県名が存在しないものの場合も0(適用不可)を返す。
   * @param string $prefectureCheckColumn 都道府県チェックカラム
   * @param string $prefectureName 都道府県名
   * @return int 0:適用不可|1:適用可能
   */
  public function checkPrefectureCheckColumnAvailability($prefectureCheckColumn, $prefectureName)
  {
    $sql = <<<EOD
      SELECT
        {$prefectureCheckColumn}
      FROM
        tb_prefecture
      WHERE
        name = :name
EOD;
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':name', $prefectureName, \PDO::PARAM_STR);
    $stmt->execute();
    $availableFlg = $stmt->fetchColumn();
    if ($availableFlg === false) {
      $availableFlg = 0;
    }
    return (int)$availableFlg;
  }


  /**
   * 指定都道府県チェックカラムについて、利用可能な都道府県名を配列で返す。
   *
   * @param string $prefectureCheckColumn 都道府県チェックカラム
   * @return array 利用可能都道府県一覧
   */
  public function findCheckColumnAvailabilityPrefectures($prefectureCheckColumn)
  {
    $sql = <<<EOD
      SELECT
        name
      FROM
        tb_prefecture
      WHERE
        {$prefectureCheckColumn} <> 0
EOD;
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $prefectures = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    return $prefectures;
  }
}