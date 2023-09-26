#!/bin/bash

# プログラムID：health_check_DBSV03.sh
# プログラム名：ヘルスチェック用シェル(BatchSV02)

# 実行パスの取得
SCRIPT_DIR=$(cd $(dirname $0); pwd)

# 対象インスタンス、SSHの接続にも使うので、.ssh/configの設定が必要
target2="DBSV03"

# エラーフラグ
error_flg=0

#
# LA(Load Average)チェック
#

# エラーフラグクリア
error_flg=0

# DBSV01 5分平均負荷
target_load_average=$(ssh ${target2} "uptime| awk 'FS = \" \"{print \$11}' | sed -e \"s/,//g\"")

# 閾値オーバーならエラーフラグ（閾値=4.00 今後調整）
if [ "$(echo "${target_load_average} > ${target_load_average_threshold}" | bc)" -eq 1 ]; then
  error_flg=1
fi

# エラーフラグ立ってたらntfy通知
if [ $error_flg -eq 1 ]; then  
  # チケットとして送信
  curl -H "Title: Load agerage warning" -H "Priority: high" -H "Tags: warning" -d "${target2}: Load averageが高くなっています[${target_load_average}]" ntfy.sh/forest-ntfy
fi
