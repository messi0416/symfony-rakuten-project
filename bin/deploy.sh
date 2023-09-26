#!/bin/bash

# プログラムID：deploy.sh
# プログラム名：本番反映用シェル
# BatchSV02のworkuserパスワードが必要

# 実行ユーザ。ホームディレクトリの指定に利用
EXEC_USER=`whoami`
DST_SERVER="BatchSV02"

SRC_DIR="/home/workuser/working/ne_api/"
DST_DIR="${DST_SERVER}:/home/workuser/ne_api/"

cd $SRC_DIR;

# parameters.env.yml ファイルの存在確認
PARAMETERS_FILE="../documents/web_system_parameters/parameters.env.yml.prod"
if [ ! -e "${PARAMETERS_FILE}" ]; then
  echo "本番用パラメータファイル: ${PARAMETERS_FILE} が見つかりません。デプロイを中止します。"
  exit;
fi


# タイムスタンプをgitコミット日時に変更
echo "=== ファイルのタイムスタンプを更新(git commmit日時)"
/usr/bin/perl bin/git-set-file-times;
 

# 本番サーバ へデプロイ
RSYNC_OPT=""

RSYNC_OPT="${RSYNC_OPT} -rltDv"
RSYNC_OPT="${RSYNC_OPT} --delete"
RSYNC_OPT="${RSYNC_OPT} --exclude=.git/"
RSYNC_OPT="${RSYNC_OPT} --exclude=.idea/"
RSYNC_OPT="${RSYNC_OPT} --exclude=.editorconfig"
RSYNC_OPT="${RSYNC_OPT} --exclude=phpstan.neon"
RSYNC_OPT="${RSYNC_OPT} --exclude=.vscode/setting.json"
# RSYNC_OPT="${RSYNC_OPT} --exclude=/bin/" # nodeをここに入れたので必要に。
RSYNC_OPT="${RSYNC_OPT} --exclude=/app/cache/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/app/logs/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/dev/"
# data ※ include を先に記述する。excludeが先では無視される。
RSYNC_OPT="${RSYNC_OPT} --include=/data/yahoo/yahoo_no_image.gif"
RSYNC_OPT="${RSYNC_OPT} --include=/data/product/*"
RSYNC_OPT="${RSYNC_OPT} --include=/data/templates/*"
RSYNC_OPT="${RSYNC_OPT} --include=/data/cron/crontab"
RSYNC_OPT="${RSYNC_OPT} --include=/data/cron/crontab_cube"
RSYNC_OPT="${RSYNC_OPT} --include=/data/cron/crontab_dev"
RSYNC_OPT="${RSYNC_OPT} --include=/data/cron/crontab_root"
RSYNC_OPT="${RSYNC_OPT} --exclude=/data/*/*"
RSYNC_OPT="${RSYNC_OPT} --exclude=/WEB_CSV/"
# WEB assets はサーバ側で生成
RSYNC_OPT="${RSYNC_OPT} --exclude=/web/bundles/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/web/css/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/web/js/"
# parameters.yml はドンガラのまま、parameters.env.yml を上書きする。のでそちらをexclude
RSYNC_OPT="${RSYNC_OPT} --exclude=/app/config/parameters.env.yml"
RSYNC_OPT="${RSYNC_OPT} -e ssh"
# RSYNC_OPT="${RSYNC_OPT} --size-only"
# RSYNC_OPT="${RSYNC_OPT} --checksum"
# RSYNC_OPT="${RSYNC_OPT} --dry-run"

# echo "rsync ${RSYNC_OPT} ${SRC_DIR} ${DST_DIR}"
rsync ${RSYNC_OPT} ${SRC_DIR} ${DST_DIR}

# サーバ上でのデプロイ処理
batch_root_path="/home/workuser/ne_api"

# parameters.env.yml の差し替え （最優先）
echo "=== parameter.env.yml.prod => parameters.env.yml"
scp "${PARAMETERS_FILE}" "${DST_SERVER}:${batch_root_path}/app/config/parameters.env.yml"

# 共通初期処理
echo "=== COMMON init "
command="cd ${batch_root_path}"
command="${command}; bash bin/common_init.sh"
ssh ${DST_SERVER} "${command}"

# パーミッション設定
echo "=== chmod files and dirs";
command="cd ${batch_root_path}"
command="${command}; find ${batch_root_path}/ -type f -print0 | xargs -0 chmod +r "
command="${command}; find ${batch_root_path}/ -type d -print0 | xargs -0 chmod -R +rx "
ssh ${DST_SERVER} "${command}"

# Crontab 更新
echo "=== update crontab";
command="cd ${batch_root_path}"
command="${command}; bash bin/apply_crontab.sh"
ssh ${DST_SERVER} "${command}"

# Crontab 更新 (root) ※要 workuserのパスワード入力
echo "=== update crontab (root)";
command="cd ${batch_root_path}"
command="${command}; sudo crontab data/cron/crontab_root"
ssh -t ${DST_SERVER} "${command}"

# Server上でパーミッションの設定
# command="cd ${batch_root_path}"
# command="${command}; find ${batch_root_path}/ -type f -print0 | xargs -0 chmod +r "
# command="${command}; find ${batch_root_path}/ -type d -print0 | xargs -0 chmod -R +rx "
#
#command="${command}; mkdir -p ${batch_root_path}/app/cache; chmod 0777 ${batch_root_path}/app/cache "
#command="${command}; mkdir -p ${batch_root_path}/app/logs; chmod 0777 ${batch_root_path}/app/logs "
#command="${command}; mkdir -p ${batch_root_path}/data; chmod 0777 ${batch_root_path}/data/ "
#
#ssh ${DST_SERVER} "${command}"
