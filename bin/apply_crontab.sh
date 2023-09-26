#!/bin/bash

# デプロイ時に呼び出される。
# また、webシステムからのcrontab自動生成時にも呼び出されるため注意。
# プログラムID：apply_crontab.sh
# プログラム名：crontab更新用シェル

# ディレクトリ
SCRIPT_DIR=$(cd $(dirname $(readlink -f $0 || echo $0));pwd -P);
APP_DIR=$(dirname $SCRIPT_DIR);
DATA_DIR="${APP_DIR}/data"
CRON_DIR="${DATA_DIR}/cron"


# 開発環境では不用意にcronは動かさない。
if [ -e "/this_is_dev_server" ]; then
  # webシステム自動生成 crontab
  auto_files=($(ls -1d ${CRON_DIR}/auto/*));

  crontabs=("${CRON_DIR}/crontab_dev" ${auto_files[@]});
elif [ -e "/this_is_batchsv04_server" ]; then
  # webシステム自動生成 crontab ※BatchSV04には不要な設定
  #auto_files=($(ls -1d ${CRON_DIR}/auto/*));

  #crontabs=("${CRON_DIR}/crontab_batchsv04" ${auto_files[@]});
  crontabs=("${CRON_DIR}/crontab_batchsv04");
else
  # webシステム自動生成 crontab
  auto_files=($(ls -1d ${CRON_DIR}/auto/*));

  crontabs=("${CRON_DIR}/crontab" ${auto_files[@]});
fi

cat ${crontabs[@]} | crontab;

