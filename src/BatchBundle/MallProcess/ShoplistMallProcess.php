<?php

namespace BatchBundle\MallProcess;

/**
 * Class ShoplistMallProcess
 */
class ShoplistMallProcess extends BaseMallProcess
{

  const CSV_TYPE_SALES_PRODUCT = 'sales_product';
  const CSV_TYPE_SPEED_BIN_STOCK = 'speed_bin_stock';
  const CSV_TYPE_ORDER_APPROVED = 'order_approved';
  const CSV_TYPE_SIMPLE_ORDER = 'simple_order'; // 手作成

  const CSV_NAME_SPEED_BIN_NEXT_ENGINE_ORDER = 'next_engine.csv';
  const CSV_NAME_SPEED_BIN_SHOPLIST_UPLOAD = 'shoplist.csv';

  /**
   * CSV取込用一時テーブル作成 商品詳細CSV
   * 
   * 価格更新CSVアップロードで利用される。
   * @param \Doctrine\DBAL\Connection $db
   * @return String
   */
  public function createTemporaryTableProductDetailCsv($db)
  {
    $tableName = 'tmp_work_shoplist_product_detail';

    $db->query("DROP TEMPORARY TABLE IF EXISTS " . $tableName);

    $sql = <<<EOD
      CREATE TEMPORARY TABLE {$tableName} (
          `コントロールカラム` VARCHAR(4) NOT NULL DEFAULT ''
        , `商品管理番号（商品URL）` VARCHAR(50) NOT NULL DEFAULT ''
        , `商品番号` VARCHAR(50) NOT NULL DEFAULT ''
        , `全商品ディレクトリID` VARCHAR(20) NOT NULL DEFAULT ''
        , `タグID` VARCHAR(20) NOT NULL DEFAULT ''
        , `PC用キャッチコピー` VARCHAR(255) NOT NULL DEFAULT ''
        , `モバイル用キャッチコピー` VARCHAR(255) NOT NULL DEFAULT ''
        , `商品名` VARCHAR(255) NOT NULL DEFAULT ''
        , `販売価格` INTEGER NOT NULL DEFAULT 0
        , `表示価格` INTEGER NOT NULL DEFAULT 0
        , `消費税` INTEGER NOT NULL DEFAULT 0
        , `送料` INTEGER NOT NULL DEFAULT 0
        , `個別送料` INTEGER NOT NULL DEFAULT 0
        , `送料区分1` INTEGER NOT NULL DEFAULT 0
        , `送料区分2` INTEGER NOT NULL DEFAULT 0
        , `代引料` INTEGER NOT NULL DEFAULT 0
        , `倉庫指定` INTEGER NOT NULL DEFAULT 0
        , `商品情報レイアウト` INTEGER NOT NULL DEFAULT 0
        , `注文ボタン` INTEGER NOT NULL DEFAULT 0
        , `資料請求ボタン` INTEGER NOT NULL DEFAULT 0
        , `商品問い合わせボタン` INTEGER NOT NULL DEFAULT 0
        , `再入荷お知らせボタン` INTEGER NOT NULL DEFAULT 0
        , `のし対応` INTEGER NOT NULL DEFAULT 0
        , `PC用商品説明文` VARCHAR(255) NOT NULL DEFAULT ''
        , `スマートフォン用商品説明文` VARCHAR(255) NOT NULL DEFAULT ''
        , `PC用販売説明文` VARCHAR(255) NOT NULL DEFAULT ''
        , `商品画像URL` VARCHAR(255) NOT NULL DEFAULT ''
        , `商品画像名（ALT）` VARCHAR(255) NOT NULL DEFAULT ''
        , `動画` VARCHAR(255) NOT NULL DEFAULT ''
        , `販売期間指定` VARCHAR(20) NOT NULL DEFAULT ''
        , `注文受付数` INTEGER NOT NULL DEFAULT 0
        , `在庫タイプ` INTEGER NOT NULL DEFAULT 0
        , `在庫数` VARCHAR(10) NOT NULL DEFAULT ''
        , `在庫数表示` VARCHAR(10) NOT NULL DEFAULT ''
        , `項目選択肢別在庫用横軸項目名` VARCHAR(20) NOT NULL DEFAULT ''
        , `項目選択肢別在庫用縦軸項目名` VARCHAR(20) NOT NULL DEFAULT ''
        , `項目選択 肢別在庫用残り表示閾値` INTEGER NOT NULL DEFAULT 0
        , `RAC番号` VARCHAR(30) NOT NULL DEFAULT ''
        , `サーチ非表示` VARCHAR(30) NOT NULL DEFAULT ''
        , `闇市パスワード` VARCHAR(30) NOT NULL DEFAULT ''
        , `カタログID` INTEGER NOT NULL DEFAULT 0
        , `在庫戻しフラグ` INTEGER NOT NULL DEFAULT 0
        , `在庫切れ時の注文受付` INTEGER NOT NULL DEFAULT 0
        , `在庫あり時納期管理番号` INTEGER NOT NULL DEFAULT 0
        , `在庫切れ時納期管理番号` INTEGER NOT NULL DEFAULT 0
        , `予約商品発売日` VARCHAR(20) NOT NULL DEFAULT ''
        , `ポイント変倍率` INTEGER NOT NULL DEFAULT 0
        , `ポイント変倍率適用期間` VARCHAR(255) NOT NULL DEFAULT ''
        , `ヘッダー・フッター・レフトナビ` VARCHAR(255) NOT NULL DEFAULT ''
        , `表示項目の並び順` VARCHAR(255) NOT NULL DEFAULT ''
        , `共通説明文（小）` VARCHAR(255) NOT NULL DEFAULT ''
        , `目玉商品` VARCHAR(255) NOT NULL DEFAULT ''
        , `共通説明文（大）` VARCHAR(255) NOT NULL DEFAULT ''
        , `レビュー本文表示` INTEGER NOT NULL DEFAULT 0
        , `あす楽配送管理番号` INTEGER NOT NULL DEFAULT 0
        , `海外配送管理番号` INTEGER NOT NULL DEFAULT 0
        , `サイズ表リンク` VARCHAR(255) NOT NULL DEFAULT ''
        , `二重価格文言管理番号` INTEGER NOT NULL DEFAULT 0
        , `カタログIDなしの理由` VARCHAR(255) NOT NULL DEFAULT ''
        , `配送方法セット管理番号` INTEGER NOT NULL DEFAULT 0
        , `白背景画像URL` VARCHAR(255) NOT NULL DEFAULT ''
        , `メーカー提供情報表示` VARCHAR(255) NOT NULL DEFAULT ''
        , `地域別個別送料管理番号` INTEGER NOT NULL DEFAULT 0
        , `消費税率` INTEGER NOT NULL DEFAULT 0
        , `メール便`  INTEGER NOT NULL DEFAULT 0
        , `下代` INTEGER NOT NULL DEFAULT 0
        , `ブランドコード` VARCHAR(30) NOT NULL DEFAULT ''
        , `商品連携ID1` VARCHAR(255) NOT NULL DEFAULT ''
        , `商品連携ID2` VARCHAR(255) NOT NULL DEFAULT ''
        , `カラー軸` VARCHAR(30) NOT NULL DEFAULT ''
      ) Engine=InnoDB DEFAULT CHARSET utf8
EOD;
    $db->query($sql);

    return $tableName;
  }

  public static $PRODUCT_DETAIL_CSV_HEADERS = [
      'コントロールカラム'
    , '商品管理番号（商品URL）'
    , '商品番号'
    , '全商品ディレクトリID'
    , 'タグID'
    , 'PC用キャッチコピー'
    , 'モバイル用キャッチコピー'
    , '商品名'
    , '販売価格'
    , '表示価格'
    , '消費税'
    , '送料'
    , '個別送料'
    , '送料区分1'
    , '送料区分2'
    , '代引料'
    , '倉庫指定'
    , '商品情報レイアウト'
    , '注文ボタン'
    , '資料請求ボタン'
    , '商品問い合わせボタン'
    , '再入荷お知らせボタン'
    , 'のし対応'
    , 'PC用商品説明文'
    , 'スマートフォン用商品説明文'
    , 'PC用販売説明文'
    , '商品画像URL'
    , '商品画像名（ALT）'
    , '動画'
    , '販売期間指定'
    , '注文受付数'
    , '在庫タイプ'
    , '在庫数'
    , '在庫数表示'
    , '項目選択肢別在庫用横軸項目名'
    , '項目選択肢別在庫用縦軸項目名'
    , '項目選択肢別在庫用残り表示閾値'
    , 'RAC番号'
    , 'サーチ非表示'
    , '闇市パスワード'
    , 'カタログID'
    , '在庫戻しフラグ'
    , '在庫切れ時の注文受付'
    , '在庫あり時納期管理番号'
    , '在庫切れ時納期管理番号'
    , '予約商品発売日'
    , 'ポイント変倍率'
    , 'ポイント変倍率適用期間'
    , 'ヘッダー・フッター・レフトナビ'
    , '表示項目の並び順'
    , '共通説明文（小）'
    , '目玉商品'
    , '共通説明文（大）'
    , 'レビュー本文表示'
    , 'あす楽配送管理番号'
    , '海外配送管理番号'
    , 'サイズ表リンク'
    , '二重価格文言管理番号'
    , 'カタログIDなしの理由'
    , '配送方法セット管理番号'
    , '白背景画像URL'
    , 'メーカー提供情報表示'
    , '地域別個別送料管理番号'
    , '消費税率'
    , 'メール便'
    , '下代'
    , 'ブランドコード'
    , '商品連携ID1'
    , '商品連携ID2'
    , 'カラー軸'
  ];
}
