#!/bin/bash

# プログラムID：upload_file.sh
# プログラム名：用途不明、本番にファイルをアップするためのシェル？

# 実行ユーザ。ホームディレクトリの指定に利用
EXEC_USER=`whoami`
DST_SERVER="BatchSV02"

SRC_DIR="/home/${EXEC_USER}/working/ne_api/"
DST_DIR="${DST_SERVER}:/home/workuser/ne_api/"

if [ $# -eq 0 ]; then
  echo "Usage: `basename $0` file " 1>&2
  exit 1
fi
if [ $# -gt 1 ]; then
  echo "Usage: `basename $0` file " 1>&2
  exit 1
fi

CMD="scp ${SRC_DIR}$1 ${DST_DIR}$1";
${CMD};

echo "done!"
