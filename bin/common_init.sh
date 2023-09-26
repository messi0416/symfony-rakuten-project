#!/bin/bash

# プログラムID：common_init.sh
# プログラム名：キャッシュ更新など初期化用シェル
# 本番反映時にも実行される

# ディレクトリ
SCRIPT_DIR=$(cd $(dirname $(readlink -f $0 || echo $0));pwd -P);
APP_DIR=$(dirname $SCRIPT_DIR);

# 作業ディレクトリ移動
cd ${APP_DIR};

# 必要なディレクトリの作成
echo "=== create directories "

# CSVデータ取込用 ディレクトリ ※2016/01/29 現時点で利用中
# 最低限必要なディレクトリはここで作っておいた方がいいかも（誤削除対応も含めて
mkdir -p ${APP_DIR}/data/sessions;
mkdir -p ${APP_DIR}/data/dev_dump;
mkdir -p ${APP_DIR}/data/dev_test;
mkdir -p ${APP_DIR}/data/orders;
mkdir -p ${APP_DIR}/data/review;
mkdir -p ${APP_DIR}/data/stocks;
mkdir -p ${APP_DIR}/data/view_ranking;
mkdir -p ${APP_DIR}/data/access_count;
mkdir -p ${APP_DIR}/data/upload_image_yahoo;
mkdir -p ${APP_DIR}/data/images;
mkdir -p ${APP_DIR}/data/images/model;
mkdir -p ${APP_DIR}/data/templates/Logistics;

# CSVデータ出力用 ディレクトリ ※データ出力のためのみのインポート用も含む
mkdir -p ${APP_DIR}/WEB_CSV/;
mkdir -p ${APP_DIR}/WEB_CSV/NextEngine;
mkdir -p ${APP_DIR}/WEB_CSV/NextEngineUpdateStock;
mkdir -p ${APP_DIR}/WEB_CSV/RakutenNokiKanri;

mkdir -p ${APP_DIR}/WEB_CSV/Rakuten/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Rakuten/Export;
mkdir -p ${APP_DIR}/WEB_CSV/Rakuten/Downloaded; # 楽天CSV出力 インポートファイル手動アップロード箇所
mkdir -p ${APP_DIR}/WEB_CSV/Rakuten/Tag;
mkdir -p ${APP_DIR}/WEB_CSV/Yahoo/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Yahoo/Export;
mkdir -p ${APP_DIR}/WEB_CSV/ChouchouClair/Import;
mkdir -p ${APP_DIR}/WEB_CSV/ChouchouClair/Export;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/Export;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/FBA;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/SNL;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/FBAServiceRequest;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/FBALabel;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/FBAOrder;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/FBAOrder/csv;
mkdir -p ${APP_DIR}/WEB_CSV/Amazon/FBAOrder/data;
mkdir -p ${APP_DIR}/WEB_CSV/AmazonCom/Import;
mkdir -p ${APP_DIR}/WEB_CSV/AmazonCom/Export;
mkdir -p ${APP_DIR}/WEB_CSV/AmazonCom/FBA;
mkdir -p ${APP_DIR}/WEB_CSV/Shoplist/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Shoplist/Export;
mkdir -p ${APP_DIR}/WEB_CSV/Shoplist/SpeedBin;
mkdir -p ${APP_DIR}/WEB_CSV/Ppm/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Ppm/Export;
mkdir -p ${APP_DIR}/WEB_CSV/Wowma/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Wowma/Export;
mkdir -p ${APP_DIR}/WEB_CSV/Wowma/Downloaded;

mkdir -p ${APP_DIR}/WEB_CSV/MallOrder/Import;
mkdir -p ${APP_DIR}/WEB_CSV/MallOrder/Export;

mkdir -p ${APP_DIR}/WEB_CSV/YabuyoshiServiceRequest;
mkdir -p ${APP_DIR}/WEB_CSV/Yabuyoshi/Import;

# CSVデータ取込用 ディレクトリ ※2016/01/29 現時点で未使用。改修で利用する見込み？
mkdir -p ${APP_DIR}/WEB_CSV/Import;
mkdir -p ${APP_DIR}/WEB_CSV/Import/ne_inout;
mkdir -p ${APP_DIR}/WEB_CSV/Import/ne_order;
mkdir -p ${APP_DIR}/WEB_CSV/Import/ne_stock;
mkdir -p ${APP_DIR}/WEB_CSV/Import/rakuten_ranking;
mkdir -p ${APP_DIR}/WEB_CSV/Import/rakuten_review;
mkdir -p ${APP_DIR}/WEB_CSV/Import/yahoo;
mkdir -p ${APP_DIR}/WEB_CSV/Import/goods;

# 商品画像ディレクトリ・商品画像バックアップディレクトリ 作成
# ※ここは開発都合で "workuser" 固定
mkdir -p /home/workuser/product_images
mkdir -p /home/workuser/product_images_original

# 必要なファイルの作成
touch "app/config/parameters.env.yml"

# キャッシュクリア
echo "=== cache clear"
app/console cache:clear --no-warmup --env=prod
app/console cache:clear --no-warmup --env=dev
app/console cache:clear --no-warmup --env=test

# アセット更新
echo "=== dump assets (assetic)"
app/console assets:install --env=prod
app/console assetic:dump --env=prod
app/console assets:install --env=dev
app/console assetic:dump --env=dev
app/console assets:install --env=test
app/console assetic:dump --env=test

# 完了
echo "COMMON INITIAL PROCEDURE done.";
