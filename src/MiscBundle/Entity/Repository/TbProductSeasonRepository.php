<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbProductSeason;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductSeasonRepository
 */
class TbProductSeasonRepository extends BaseRepository
{
  /**
   * 値下げ月設定の取得
   */
  public function getProductSeasonSetting($daihyoSyohinCode)
  {
    $row = $this->find($daihyoSyohinCode);
    $tmp = $row->toScalarArray();
    unset($tmp['daihyo_syohin_code']);

    return $tmp;
  }

  public function setProductSeasonSetting($daihyoSyohinCode, $discountSeasonSetting)
  {
    $row = $this->find($daihyoSyohinCode);
    $row->setS1($discountSeasonSetting['s1']);
    $row->setS2($discountSeasonSetting['s2']);
    $row->setS3($discountSeasonSetting['s3']);
    $row->setS4($discountSeasonSetting['s4']);
    $row->setS5($discountSeasonSetting['s5']);
    $row->setS6($discountSeasonSetting['s6']);
    $row->setS7($discountSeasonSetting['s7']);
    $row->setS8($discountSeasonSetting['s8']);
    $row->setS9($discountSeasonSetting['s9']);
    $row->setS10($discountSeasonSetting['s10']);
    $row->setS11($discountSeasonSetting['s11']);
    $row->setS12($discountSeasonSetting['s12']);
  }

  /**
   * 指定した代表商品コードのモール商品メイン情報を返す
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array
   */
  public function findProductSeasonWithMainProductInfo($daihyoSyohinCode)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
        s.*,
        m.daihyo_syohin_name,
        m.picfolderP1,
        m.picnameP1,
        m.set_flg,
        m.登録日時
      FROM
        tb_product_season s
        JOIN tb_mainproducts m
          ON s.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE
        s.daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    $list['mainProduct'] = [
      'daihyoSyohinCode' => $result['daihyo_syohin_code'],
      'daihyoSyohinName' => $result['daihyo_syohin_name'],
      'imageDir' => $result['picfolderP1'],
      'imageFile' => pathinfo($result['picnameP1'])['filename'],
      'setFlg' => (int)$result['set_flg'],
      'registrationDate' => $result['登録日時'],
    ];

    $list['months'] = [
      'hattyuten' => [],
      'nesage' => [],
      'kisetsuzaikoteisu' => [],
    ];
    for ($i = 1; $i <= 12; $i++) {
      if ($result["m{$i}"]) {
        $list['months']['hattyuten'][] = $i;
      }
      if ($result["s{$i}"]) {
        $list['months']['nesage'][] = $i;
      }
      if ($result["c{$i}"]) {
        $list['months']['kisetsuzaikoteisu'][] = $i;
      }
    }

    return $list;
  }

  /**
   * 指定された代表商品コードと月設定に基づいてテーブルを更新する
   *
   * @param string $daihyoSyohinCode 更新する代表商品コード
   * @param array $months 'hattyuten', 'nesage', 'kisetsuzaikoteisu'をキー、ONに設定する月の配列を値とする連想配列
   * @return void
   */
  function updateProductSeason($daihyoSyohinCode, $months) {
    $dbMain = $this->getConnection('main');

    $sql = "UPDATE tb_product_season SET ";

    $kindMapping = [
      'hattyuten' => 'm',
      'nesage' => 's',
      'kisetsuzaikoteisu' => 'c',
    ];
    foreach ($kindMapping as $item => $column) {
      for ($i = 1; $i <= 12; $i++) {
        $onMonths = $months[$item];
        $value = in_array($i, $onMonths) ? -1 : 0;
        $sql .= "{$column}{$i} = {$value}, ";
      }
    }

    // 末尾のカンマとスペースを削除します
    $sql = rtrim($sql, ', ');
    $sql .= " WHERE daihyo_syohin_code = :daihyoSyohinCode";

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
  }
}
