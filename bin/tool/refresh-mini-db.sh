#!/bin/bash

# バッチサーバからダウンロードして再構築する
# ターゲットは test_plusnao_db
# バッチサーバは .ssh/config に BatchSV02 という名前で登録している前提

# -d : ダウンロード込
# -i : 画像の更新のみ（DBの更新なし）

BATCH_SERVER="BatchSV02"

DO_DOWNLOAD=0
DO_UPDATE_DB=1
DO_DOWNLOAD_IMAGE=0

while getopts di OPT
do
  case $OPT in
    "d" ) DO_DOWNLOAD=1; DO_DOWNLOAD_IMAGE=1 ;;
    "i" ) DO_UPDATE_DB=0; DO_DOWNLOAD_IMAGE=1 ;;
  esac
done

# ${0} の dirname を取得
cwd=`dirname "${0}"`
# ${0} が 相対パスの場合は cd して pwd を取得
expr "${0}" : "/.*" > /dev/null || cwd=`(cd "${cwd}" && pwd)`

bin_dir=`dirname ${cwd}`
root_dir=`dirname ${bin_dir}`
data_dir="${root_dir}/data/dev_dump"
sql_dir="${bin_dir}/sql"

conf_file_develop="${bin_dir}/backup/conf/develop.cnf"
db_name_test="test_plusnao_db"
db_name_test_log="test_plusnao_log_db"


schema_file="${data_dir}/mini_db.schema.sql"
stored_file="${data_dir}/mini_db.stored.sql"
dump_file="${data_dir}/mini_db.sql.gz"

log_schema_file="${data_dir}/mini_log_db.schema.sql"

# ファイルがなければダウンロード
if [ ! -e "$dump_file" ]; then
  DO_DOWNLOAD=1;
fi

if [ $DO_DOWNLOAD -eq 1 ]; then
  scp ${BATCH_SERVER}:/home/workuser/backup/daily/mini_db.schema.sql ${schema_file}
  scp ${BATCH_SERVER}:/home/workuser/backup/daily/mini_db.stored.sql ${stored_file}
  scp ${BATCH_SERVER}:/home/workuser/backup/daily/mini_db.sql.gz ${dump_file}

  scp ${BATCH_SERVER}:/home/workuser/backup/daily/mini_log_db.schema.sql ${log_schema_file}
fi

if [ $DO_DOWNLOAD -eq 1 ] || [ $DO_UPDATE_DB -eq 1 ]; then
  mysql --defaults-file="${conf_file_develop}" ${db_name_test} < ${schema_file}
  gunzip -c ${dump_file} | mysql --defaults-file="${conf_file_develop}" ${db_name_test}
  mysql --defaults-file="${conf_file_develop}" ${db_name_test} < ${stored_file}

  # ログDB
  mysql --defaults-file="${conf_file_develop}" ${db_name_test_log} < ${log_schema_file}

  # 60年カレンダー 再作成
  mysql --defaults-file="${conf_file_develop}" ${db_name_test} < "${sql_dir}/refresh_calendar.sql"

  # 価格履歴テーブル データ投入（ダミーデータ）
  php ${root_dir}/app/console --env=test dev:create-dummy-product-price-log --days=30

  # fixture 投入 (--apend)
  php ${root_dir}/app/console --env=test doctrine:fixtures:load --append

fi

# 画像の同期ダウンロード
# バッチサーバ -> 開発環境。存在する商品の画像のみ取得する
IMAGE_SRC_DIR=${BATCH_SERVER}:/home/workuser/product_images_original/
IMAGE_DST_DIR=/home/workuser/product_images_original/
IMAGE_COPY_DIR=/home/workuser/product_images/

SQL=$(cat << EOS
SELECT folder FROM (
  SELECT picfolderP1 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP2 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP3 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP4 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP5 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP6 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP7 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP8 AS folder FROM tb_mainproducts
    UNION SELECT picfolderP9 AS folder FROM tb_mainproducts
    UNION SELECT picfolderM1 AS folder FROM tb_mainproducts
    UNION SELECT picfolderM2 AS folder FROM tb_mainproducts
    UNION SELECT picfolderM3 AS folder FROM tb_mainproducts
) T
WHERE folder IS NOT NULL
ORDER BY folder
EOS
)

PICT_FOLDERS=(`mysql --defaults-file="${conf_file_develop}" ${db_name_test} -NB -e "$SQL"`)

RSYNC_OPT=""
RSYNC_OPT="${RSYNC_OPT} -rltD"
RSYNC_OPT="${RSYNC_OPT} --delete"
RSYNC_OPT="${RSYNC_OPT} -e ssh"

for folder in ${PICT_FOLDERS[@]}; do
    RSYNC_OPT="${RSYNC_OPT} --include=${folder}/"
    RSYNC_OPT="${RSYNC_OPT} --include=${folder}/*"
done

RSYNC_OPT="${RSYNC_OPT} --exclude=*"

CMD="rsync ${RSYNC_OPT} ${IMAGE_SRC_DIR} ${IMAGE_DST_DIR}"

if [ $DO_DOWNLOAD_IMAGE -eq 1 ]; then
  echo $CMD;
  ${CMD};

  # original から コピー
  rsync -rltpD --delete "${IMAGE_DST_DIR}" "${IMAGE_COPY_DIR}"
fi
