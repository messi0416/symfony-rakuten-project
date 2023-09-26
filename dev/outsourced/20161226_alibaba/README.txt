1. 構成
DB関連の構成はdb_connect.phpで進行します。
server_name、db_user、db_password、db_nameを当該コンピューターに設置された資料基地の環境に合わせて修正します。

2.実行
linuxのterminalあるいはwindowsのcommand promptから下の指令を入力する。
php check_1688.php
この時tb_vendoraddressテーブルのstopフィールドが0人レコードらに対してデータを更新する。
製品情報資料はtb_1688_good_inform、tb_1688_good_sku_inform、tb_1688_good_sku_detail_informテーブルに挿入あるいは更新される。
そして削除、製品の変更、SKU変更など製品の更新の資料はtb_product_changelogテーブルに反映される。

(リナックスでcronに登録しようとする場合にはcrontabに該当指令を追加している。)

3.DB説明
tb_1688_good_inform:
	製品の特性資料を含むテーブルprimary keyはofferid(当該商品ページの呼び出しurlでの番号の値)
tb_1688_good_sku_inform:
	該当製品のSKU情報に対する一般的な情報を含めているテーブル。該当製品のチョンジョクの在庫量、割引価格などの情報を含んでいる。
tb_1688_good_sku_detail_inform:
	該当製品のSKU情報に対する具体的な情報を含めているテーブル。offeridのattributenameについて現在庫数量を保管している。
tb_product_changelog:
	製品の変更情報を保管するテーブル
	ログタイプ: 
		商品が削除された 場合
		商品が全部売れちゃった 場合
		商品が新たに追加された 場合
		商品名が変更された 場合
		どの細部種類の数量が売れた 場合
		どの細部種類の数量が変更された 場合
		どの細部の種類が追加された 場合