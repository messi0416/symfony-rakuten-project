#!/bin/bash

# プログラムID：health_check_BatchSV02.sh
# プログラム名：ヘルスチェック用シェル(BatchSV02)

# 実行パスの取得
SCRIPT_DIR=$(cd $(dirname $0); pwd)

# 対象インスタンス、SSHの接続にも使うので、.ssh/configの設定が必要
target="BatchSV02"

# エラーフラグ
error_flg=0

#############################################################
# HTTPステータスチェック
#############################################################

# ９～１０時台でエラーが発生しやすいページ
sites=(
  "https://starlight.plusnao.co.jp/"
  "https://starlight.plusnao.co.jp/picking/list"
  "https://starlight.plusnao.co.jp/location/index"
  "https://starlight.plusnao.co.jp/health.html"
)

for site in "${sites[@]}" ; do
  # HTTPステータスを取得
  target_nginx_status=$(curl -k -LI ${site} -o /dev/null -w '%{http_code}\n' -s)

  # 閾値オーバーならエラーフラグ
  if [ $target_nginx_status -ne 200 ]; then
    error_flg=1
  fi
done

# エラーフラグ立ってたらntfy通知
if [ $error_flg -eq 1 ]; then  
  # 通知送信
  curl -H "Title: nginx status error" -H "Priority: high" -H "Tags: error" -d "${target}: HTTPステータスエラーが発生しています" ntfy.sh/forest-ntfy
fi

# エラーフラグクリア
error_flg=0

#############################################################
# LA(Load Average)チェック
#############################################################

# エラー閾値
target_load_average_threshold=4.00

# BatchSV02 5分平均負荷
target_load_average=$(uptime | awk 'FS = " "{print $11}' | sed -e "s/,//g")

# 閾値オーバーならエラーフラグ（閾値=4.00 今後調整）
if [ "$(echo "${target_load_average} > ${target_load_average_threshold}" | bc)" -eq 1 ]; then
  error_flg=1
fi

# エラーフラグ立ってたらntfy通知
if [ $error_flg -eq 1 ]; then  
  # 通知送信
  curl -H "Title: Load average warning" -H "Priority: high" -H "Tags: warning" -d "${target}: Load averageが高くなっています[${target_load_average}]" ntfy.sh/forest-ntfy
fi
