#!/bin/sh

# プログラムID：health_check_DBSV01.sh
# プログラム名：ヘルスチェック用シェル(DBSV01)

# 実行パスの取得
SCRIPT_DIR=$(cd $(dirname $0); pwd)

# RedmineのAPIキーとURL
API_KEY="adaf219a7b3c768db5a530518d3c4c1abf95e879"
API_URL="http://tk2-217-18298.vs.sakura.ne.jp/issues.json"

# 対象インスタンス、SSHの接続にも使うので、.ssh/configの設定が必要
target="DBSV01"

# 各種ファイル位置を指定
timestamp=${SCRIPT_DIR}/health/timestamp_${target}
ticket_json=${SCRIPT_DIR}/health/${target}.json

# 一度エラーを検知した時の休眠期間
stop_time="3 hours"

# 空き容量アラートを出す閾値
used_alert=85

# エラーフラグ
error_flg=0

# 初回起動向け、対象のフォルダがあるかどうかを確認
if [ ! -d ${SCRIPT_DIR}/health ]; then
  mkdir ${SCRIPT_DIR}/health
fi

if [ ! -e ${timestamp} ]; then
  echo 0 > ${timestamp}
fi

# タイムスタンプ値を取得
error=$(cat ${timestamp})
now=$(date "+%s")

# 現在時刻と比較し、指定時刻になっていなければ休眠期間
if [ $now -lt $error ]; then
  exit
else
  echo 0 > ${timestamp}
fi

# 使用率を取得
target_df=$(ssh ${target} 'df -h')
target_used=`echo "${target_df}" | sed -n -e '/dev\/vda3/s/^[^ ]* *[^ ]* *[^ ]* *[^ ]* *\([0-9]*\).*$/\1/p'`

# 閾値オーバーならエラーフラグ
if [ $used_alert -lt $target_used ]; then
  error_flg=1
fi

# エラーフラグ立ってたらチケットと、タイムスタンプファイルの発行
if [ $error_flg -eq 1 ]; then
  date "+%s" --date "${stop_time}" > ${timestamp}
  
  # 結果の生成
  result="
使用率:${target_used}%
"
  
  # ものすごく面倒だけどこうでもしないと通らない
  cat <<EOS > ${ticket_json}
{
  "issue":{
    "subject":"[ヘルスチェックエラー]${target}で異常が発生しています。"
   ,"project_id":"4"
   ,"priority_id":"3"
   ,"assigned_to_id":"44"
   ,"tracker_id":"1"
   ,"description":"${result}"
  }
}
EOS
  
  # チケットとして送信
  curl -X POST -H "Content-Type: application/json" -H "X-Redmine-API-Key: ${API_KEY}" -d @${ticket_json} {$API_URL}  
fi

