<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbRfidReadingsRepository
 */
class TbRfidReadingsRepository extends BaseRepository
{
  /**
   * 読取IDから、箱タグ・商品タグの連想配列の配列を返す。
   * @param int $readingId
   * @return array 次のキーを持つ連想配列の配列
   *     'box_tag' => 箱タグ,
   *     'product_tag' => 商品タグ,
   */
  public function findRfidReadingsByReadingId($readingId)
  {
    $sql = <<<EOD
      SELECT
        box_tag, product_tag
      FROM
        tb_rfid_readings
      WHERE
        reading_id = :readingId
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':readingId', $readingId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
