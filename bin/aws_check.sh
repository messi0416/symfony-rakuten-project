#!/bin/bash

# S3バケット名
BUCKET_NAME="backup-forest"

# RedmineのAPIキーとURL
API_KEY="adaf219a7b3c768db5a530518d3c4c1abf95e879"
API_URL="http://tk2-217-18298.vs.sakura.ne.jp/issues.json"

# 実行パスの取得
SCRIPT_DIR=$(cd $(dirname $0); pwd)

# 各種ファイル位置を指定
target="aws_s3"
ticket_json=${SCRIPT_DIR}/health/${target}.json

# S3バケットの存在をチェック
aws s3api head-bucket --bucket $BUCKET_NAME --profile backup.forest 2>/dev/null

if [ $? -eq 0 ]; then
  #echo "S3 connection successful."
  exit 0
else

  cat <<EOS > ${ticket_json}
{
  "issue":{
    "subject":"[DBSV02] AWS S3チェックエラー"
   ,"project_id":"4"
   ,"priority_id":"3"
   ,"assigned_to_id":"44"
   ,"tracker_id":"1"
   ,"description":"AWS S3に接続できません"
  }
}
EOS

  # チケットとして送信
  curl -X POST -H "Content-Type: application/json" -H "X-Redmine-API-Key: ${API_KEY}" -d @${ticket_json} {$API_URL}

  exit 1
fi