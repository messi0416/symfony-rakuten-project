<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbRakutenGenreRepository
 */
class TbRakutenGenreRepository extends BaseRepository
{
  /**
   * 指定した代表商品コードについて、楽天属性画面に表示する代表商品情報を返す
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array
   */
  public function findRakutenAttributeDaihyoSyohinInfo($daihyoSyohinCode)
  {
    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code,
        m.daihyo_syohin_name,
        m.picfolderP1,
        m.picnameP1,
        d.フィールド1,
        d.フィールド2,
        d.フィールド3,
        d.フィールド4,
        d.フィールド5,
        g.path_name,
        g.rakuten_genre_id
      FROM
        tb_mainproducts m
        JOIN tb_plusnaoproductdirectory d
          ON d.NEディレクトリID = m.NEディレクトリID
        JOIN tb_rakuten_genre g
          ON d.楽天ディレクトリID = g.rakuten_genre_id
      WHERE
        m.daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$data) {
      return [];
    }

    $neDir = $data['フィールド1'];
    for ($i = 2; $i <= 5; $i++) {
      $neDir .= $data['フィールド' . $i] ? '>' . $data['フィールド' . $i] : '';
    }
    return [
      'code' => $data['daihyo_syohin_code'],
      'name' => $data['daihyo_syohin_name'],
      'dir' => $data['picfolderP1'],
      'file' => pathinfo($data['picnameP1'])['filename'],
      'neDir' => $neDir,
      'rakutenGenre' => $data['path_name'],
      'rakutenGenreId' => $data['rakuten_genre_id'],
    ];
  }
}
