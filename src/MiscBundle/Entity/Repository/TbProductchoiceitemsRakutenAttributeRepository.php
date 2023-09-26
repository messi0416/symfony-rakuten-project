<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbMainproductsCal;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * TbProductchoiceitemsRakutenAttributeRepository
 */
class TbProductchoiceitemsRakutenAttributeRepository extends BaseRepository
{
  /** 商品属性ID: ブランド名 */
  const ATTRIBUTE_ID_BRAND_NAME = 1;
  /** 商品属性ID: シリーズ名 */
  const ATTRIBUTE_ID_SERIES_NAME = 3;
  /** 商品属性ID: メーカー型番 */
  const ATTRIBUTE_ID_MAKER_MODEL_NUMBER = 5;
  /** 商品属性ID: 代表カラー */
  const ATTRIBUTE_ID_REPRESENT_COLOR = 8;
  /** 商品属性ID: カラー */
  const ATTRIBUTE_ID_COLOR = 36;
  /** 商品属性ID: 原産国／製造国 */
  const ATTRIBUTE_ID_ORIGIN_COUNTRY = 298;
  /** 商品属性ID: 靴のサイズ（JP） */
  const ATTRIBUTE_ID_SHOES_SIZE_JP = 299;
  /** 商品属性ID: 素材（生地・毛糸） */
  const ATTRIBUTE_ID_MATERIAL = 981;

  /**
   * 指定条件のSKU別商品属性値が未登録であれば自動で値を設定し、自動設定件数を返す。
   *
   * 代表商品コードの指定がある場合は、それに紐付くSKUを全て対象とする。
   * 指定がない場合は、全SKUの内、次の条件を全て満たすものを対象とする。
   * (1) アダルトチェックが、「ブラック」・「未審査」以外
   * (2) deliverycodeが、「仮登録」以外
   * (3) 「楽天plusnaoの倉庫格納フラグがOFF」
   *      または「販売開始日が1週間以内で、現在も販売中の新商品」
   * ※既に空文字以外で登録されている場合は上書きはしない。
   * @param string $daihyoSyohinCode 代表商品コード
   * @param boolean $onlyRequired 必須属性に限るか
   * @return integer 自動設定件数(バルクアップデートの仕様により、
   *   UPDATEの場合は2行、INSERTの場合は1行でカウントされる。厳密な更新行数を取るには、
   *   呼び出し側で存在件数を取得し、この戻り値から減算すること。)
   */
  public function autoUpsertSkuAttribute($daihyoSyohinCode = '', $onlyRequired = false)
  {
    // 代表商品コードの指定がある場合は、それに紐づくSKUのみ更新とする
    $wheres = [];
    $params = [];
    if ($daihyoSyohinCode) {
      $wheres[] = 'm.daihyo_syohin_code = :daihyoSyohinCode';
      $params[':daihyoSyohinCode'] = $daihyoSyohinCode;
    } else {
      $wheres[] = <<<EOD
        cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND cal.deliverycode <> :deliveryCodeTemporary
        AND (
          i.warehouse_stored_flg = 0
          OR (
            m.販売開始日 >= :startOfAvailability
            AND (cal.endofavailability IS NULL OR cal.endofavailability >= :endOfAvailability)
          )
        )
EOD;
      $params[':adultCheckStatusBlack'] = TbMainproductsCal::ADULT_CHECK_STATUS_BLACK;
      $params[':adultCheckStatusNone'] = TbMainproductsCal::ADULT_CHECK_STATUS_NONE;
      $params[':deliveryCodeTemporary'] = TbMainproductsCal::DELIVERY_CODE_TEMPORARY;
      $today = new \DateTime();
      $startOfAvailability = $today->modify("-7 day")->format('Y-m-d');
      $endOfAvailability = $today->format('Y-m-d 23:59:59');
      $params[':startOfAvailability'] = $startOfAvailability;
      $params[':endOfAvailability'] = $endOfAvailability;
    }

    if ($onlyRequired) {
      $wheres[] = 'ga.required_flg <> 0';
    }

    $addWheres = ' AND ' . implode(' AND ', $wheres);
    $sql = <<<EOD
      INSERT INTO
        tb_productchoiceitems_rakuten_attribute
      SELECT
        auto_setting.ne_syohin_syohin_code,
        auto_setting.tb_rakuten_genre_attribute_id,
        auto_setting.new_value
      FROM
        /* 自動設定値を、tb_productchoiceitems_rakuten_attributeの形式に合わせて構成 */
        (
          SELECT
            pci.ne_syohin_syohin_code AS ne_syohin_syohin_code,
            ga.id AS tb_rakuten_genre_attribute_id,
            /* 代表カラー tb_productchoiceitems_color が2件以上ある可能性があるので、簡単にMINで1件にする */
            MIN(CASE
              /* カラー */
              WHEN ga.attribute_id = :colorId THEN (
                CASE
                  WHEN color_m.sku_color_name IS NULL THEN '-'
                  WHEN color_m.sku_color_name = '' THEN '-'
                  ELSE color_m.sku_color_name
                END
              )
              /* 代表カラー */
              WHEN ga.attribute_id = :representColorId THEN (
                CASE
                  WHEN color_t.name IS NULL THEN '-'
                  WHEN color_t.name = '' THEN '-'
                  WHEN color_t.name = 'カーキ' THEN 'カーキグリーン'
                  WHEN color_t.name = 'クリア' THEN '透明'
                  ELSE color_t.name
                END
              )
              /* 素材（生地・毛糸） */
              WHEN ga.attribute_id = :materialId THEN (
                CASE
                  WHEN pci.hint_ja IS NULL THEN '-'
                  WHEN pci.hint_ja = '' THEN '-'
                  ELSE pci.hint_ja
                END
              )
              /* ブランド名 */
              WHEN ga.attribute_id = :brandNameId THEN '-'
              /* シリーズ名 */
              WHEN ga.attribute_id = :seriesNameId THEN '-'
              /* メーカー型番 */
              WHEN ga.attribute_id = :makerModelNumberId THEN '-'
              /* 原産国／製造国 */
              WHEN ga.attribute_id = :originCountryId THEN '-'
              /* 靴のサイズ（JP） */
              WHEN ga.attribute_id = :shoesSizeJpId THEN '-'
              ELSE '-'
            END) AS new_value
          FROM
            tb_productchoiceitems pci
            JOIN tb_mainproducts m
              ON pci.daihyo_syohin_code = m.daihyo_syohin_code
            JOIN tb_mainproducts_cal cal
              ON m.daihyo_syohin_code = cal.daihyo_syohin_code
            JOIN tb_rakuteninformation i
              ON m.daihyo_syohin_code = i.daihyo_syohin_code
            JOIN tb_plusnaoproductdirectory d
              ON m.NEディレクトリID = d.NEディレクトリID
            JOIN tb_rakuten_genre_attribute ga
              ON d.楽天ディレクトリID = ga.rakuten_genre_id
            LEFT JOIN tb_productchoiceitems_color color
              ON pci.ne_syohin_syohin_code = color.ne_syohin_syohin_code
            LEFT JOIN tb_color_mapping color_m
              ON color.color_name = color_m.sku_color_name
            LEFT JOIN tb_color_type color_t
              ON color_m.color_type_id = color_t.id
          WHERE
            ga.delete_flg = 0
            {$addWheres}
          GROUP BY
            pci.ne_syohin_syohin_code, ga.id
        ) auto_setting
        /* 比較のため、既存データと外部結合 */
        LEFT JOIN tb_productchoiceitems_rakuten_attribute a
          ON auto_setting.ne_syohin_syohin_code = a.ne_syohin_syohin_code
          AND auto_setting.tb_rakuten_genre_attribute_id = a.tb_rakuten_genre_attribute_id
      WHERE
        /* 既に空文字以外の値が設定されている属性は自動更新の対象から除外 */
        (a.value IS NULL OR a.value = '')
      ON DUPLICATE KEY UPDATE
        value = VALUES(value)
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':colorId', self::ATTRIBUTE_ID_COLOR, \PDO::PARAM_INT);
    $stmt->bindValue(':representColorId', self::ATTRIBUTE_ID_REPRESENT_COLOR, \PDO::PARAM_INT);
    $stmt->bindValue(':materialId', self::ATTRIBUTE_ID_MATERIAL, \PDO::PARAM_INT);
    $stmt->bindValue(':brandNameId', self::ATTRIBUTE_ID_BRAND_NAME, \PDO::PARAM_INT);
    $stmt->bindValue(':seriesNameId', self::ATTRIBUTE_ID_SERIES_NAME, \PDO::PARAM_INT);
    $stmt->bindValue(':makerModelNumberId', self::ATTRIBUTE_ID_MAKER_MODEL_NUMBER, \PDO::PARAM_INT);
    $stmt->bindValue(':originCountryId', self::ATTRIBUTE_ID_ORIGIN_COUNTRY, \PDO::PARAM_INT);
    $stmt->bindValue(':shoesSizeJpId', self::ATTRIBUTE_ID_SHOES_SIZE_JP, \PDO::PARAM_INT);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->rowCount();
  }

  /**
   * 属性ごとに、指定代表商品コードに紐づくSKUの、指定属性IDの値を連想配列で返却する
   * 
   * 属性の登録が無い場合は、属性値の部分を空文字とする
   * @param string $daihyoSyohinCode 代表商品コード
   * @param array $attributeIds 属性値が必要な属性IDのリスト
   * @return array 以下の形式の連想配列の連想配列
   * [
   *    8 => [
   *      'zak-73992-RO-480' => 'レッド',
   *      'zak-73992-RO-420' => 'レッド',
   *      'zak-73992-BK-480' => 'ブラック',
   *      'zak-73992-BK-420' => 'ブラック',
   *    ],
   *    36 => [
   *      'zak-73992-RO-480' => 'ローズ',
   *      'zak-73992-RO-420' => 'ローズ',
   *      'zak-73992-BK-480' => 'ブラック',
   *      'zak-73992-BK-420' => 'ブラック',
   *    ],
   *    ...
   * ]
   */
  public function findSkuAttributes($daihyoSyohinCode, $attributeIds)
  {
    $attributeIdsStr = implode(', ', $attributeIds);
    $sql = <<<EOD
      SELECT
        ga.attribute_id,
        a.ne_syohin_syohin_code,
        a.value
      FROM
        tb_productchoiceitems_rakuten_attribute a
        JOIN tb_rakuten_genre_attribute ga
          ON a.tb_rakuten_genre_attribute_id = ga.id
        JOIN tb_productchoiceitems pci
          ON a.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      WHERE
        pci.daihyo_syohin_code = :daihyoSyohinCode
        AND ga.attribute_id IN ({$attributeIdsStr})
      ORDER BY
        ga.attribute_id, a.ne_syohin_syohin_code
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // 一旦、指定属性ID全てに、SKU毎の値を空文字で登録（初期化）
    $skuList = array_unique(array_column($list, 'ne_syohin_syohin_code'));
    $skuInitialValues = [];
    foreach ($skuList as $sku) {
      $skuInitialValues[$sku] = '';
    }
    $skuAttributes = [];
    foreach ($attributeIds as $attributeId) {
      $skuAttributes[$attributeId] = $skuInitialValues;
    }

    // 登録の有る属性についてはその値で空文字を上書き
    foreach ($list as $item) {
      $skuAttributes[$item['attribute_id']][$item['ne_syohin_syohin_code']] = $item['value'];
    }
    return $skuAttributes;
  }

  /**
   * SKU別商品属性項目を指定の内容で一括更新する。未登録の場合は登録する。
   * @param array $modifiedList 変更リスト（キーに「sku(SKU)」「id(楽天商品属性項目ID)」「value(値)」を持つ連想配列の配列）
   */
  public function upsertSkuAttributes($modifiedList)
  {
    $values = [];
    foreach ($modifiedList as $modified) {
      $sku = $modified['sku'];
      $id = $modified['id'];
      $value = $modified['value'];
      $values[] = "('{$sku}', {$id}, '{$value}')";
    }
    $valuesStr = implode(', ', $values);

    $sql = <<<EOD
      INSERT INTO
        tb_productchoiceitems_rakuten_attribute
      VALUES
        {$valuesStr}
      ON DUPLICATE KEY UPDATE
        value = VALUES(value)
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }
}
