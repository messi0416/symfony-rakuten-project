<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbBoxCodeRepository
 */
class TbBoxCodeRepository extends BaseRepository
{
  /**
   * 箱バーコードから、対応する箱コードを取得して返す。
   * @param string $boxBarCode
   * @return string
   */
  public function findBoxCodeByBarCode($boxBarCode)
  {
    $sql = <<<EOD
      SELECT
        box_code
      FROM
        tb_box_code
      WHERE
        barcode = :barcode
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':barcode', $boxBarCode, \PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $row ? $row['box_code'] : '';
  }
}
