#!/bin/bash

# プログラムID：sync_dev_web.sh
# プログラム名：不明、読み取る感じはローカル開発環境用のシェル？

CLEAR_ALL=0
START_PROCESSES=0
while getopts cp OPT
do
  case ${OPT} in
    "c" ) CLEAR_ALL=1 ;;
    "p" ) START_PROCESSES=1;;
  esac
done

# 実行ユーザ。ホームディレクトリの指定に利用
EXEC_USER=`whoami`

SRC_DIR="/home/${EXEC_USER}/working/ne_api/"
DST_DIR="/home/${EXEC_USER}/www/starlight.plusnao.local/ne_api/"

cd $SRC_DIR;

# タイムスタンプをgitコミット日時に変更
# echo "=== ファイルのタイムスタンプを更新(git commmit日時)"
# /usr/bin/perl bin/git-set-file-times;

# www へデプロイ
RSYNC_OPT=""

# XServer へソースコードのデプロイ
RSYNC_OPT="${RSYNC_OPT} -rltDv"
RSYNC_OPT="${RSYNC_OPT} --delete"
RSYNC_OPT="${RSYNC_OPT} --exclude=.git/"
RSYNC_OPT="${RSYNC_OPT} --exclude=.idea/"
RSYNC_OPT="${RSYNC_OPT} --exclude=.editorconfig"
# RSYNC_OPT="${RSYNC_OPT} --exclude=/bin/" # nodeをここに入れたので必要に。
RSYNC_OPT="${RSYNC_OPT} --exclude=/app/cache/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/app/logs/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/dev/"
# data ※ include を先に記述する。excludeが先では無視される。
RSYNC_OPT="${RSYNC_OPT} --include=/data/yahoo/yahoo_no_image.gif"
RSYNC_OPT="${RSYNC_OPT} --include=/data/product/*"
RSYNC_OPT="${RSYNC_OPT} --include=/data/templates/*"
RSYNC_OPT="${RSYNC_OPT} --exclude=/data/*/*"
RSYNC_OPT="${RSYNC_OPT} --exclude=/WEB_CSV/"
# WEB assets はサーバ側で生成
RSYNC_OPT="${RSYNC_OPT} --exclude=/web/bundles/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/web/css/"
RSYNC_OPT="${RSYNC_OPT} --exclude=/web/js/"
# parameters_prod.yml をrenameするまで上書きしないため、exclude
# RSYNC_OPT="${RSYNC_OPT} --exclude=/app/config/parameters.yml"
RSYNC_OPT="${RSYNC_OPT} -e ssh"
# RSYNC_OPT="${RSYNC_OPT} --size-only"
# RSYNC_OPT="${RSYNC_OPT} --checksum"
# RSYNC_OPT="${RSYNC_OPT} --dry-run"

# echo "rsync ${RSYNC_OPT} ${SRC_DIR} ${DST_DIR}"
rsync ${RSYNC_OPT} ${SRC_DIR} ${DST_DIR}

# www でのデプロイ処理
batch_root_path="$DST_DIR"

if [ "${CLEAR_ALL}" = "1" ]; then

  # 共通初期処理
  echo "=== COMMON init "
  command="cd ${batch_root_path}"
  command="${command}; bash bin/common_init.sh"
  eval "${command}"

fi

if [ "${START_PROCESSES}" = "1" ]; then
#  # パーミッション設定
#  echo "=== chmod files and dirs";
#  command="cd ${batch_root_path}"
#  command="${command}; find ${batch_root_path}/ -type f -print0 | xargs -0 chmod +r "
#  command="${command}; find ${batch_root_path}/ -type d -print0 | xargs -0 chmod -R +rx "
#  eval "${command}"

  # 常駐処理 再起動
  cd "${batch_root_path}"; bin/restart_resque_worker.sh;
  cd "${batch_root_path}"

  # node.js の通知サーバは要root起動 (SSL証明書の読み込み権限のため)
  # /etc/sudoers の設定で、該当nodeコマンドの実行をNOPASSWDに設定しているため、sudo でそのまま実行。
  #   > workuser ALL=(ALL:ALL) NOPASSWD: /home/workuser/.nvm/versions/node/v4.1.1/bin/node
  # sudo /home/workuser/.nvm/versions/node/v4.1.1/bin/node /home/workuser/.nvm/versions/node/v4.1.1/bin/forever stopall
  # sudo /home/workuser/.nvm/versions/node/v4.1.1/bin/node /home/workuser/.nvm/versions/node/v4.1.1/bin/forever start -p /home/workuser/.forever -l /home/workuser/.forever/node-app.log -a --workingDir /home/workuser/working/ne_api/bin/node /home/workuser/working/ne_api/bin/node/plusnao_notification_server.js
  sudo /home/workuser/.nvm/versions/node/v10.0.0/bin/node /home/workuser/.nvm/versions/node/v10.0.0/bin/forever stopall
  sudo /home/workuser/.nvm/versions/node/v10.0.0/bin/node /home/workuser/.nvm/versions/node/v10.0.0/bin/forever start -p /home/workuser/.forever -l /home/workuser/.forever/node-app.log -a --workingDir /home/workuser/working/ne_api/bin/node /home/workuser/working/ne_api/bin/node/plusnao_notification_server.js

  # もし sudoers の設定がうまくいってない場合は仕方ないので下記を表示して終了。
  # echo "下記コマンドを実行し、通知サーバを再起動してください。"
  # echo "  sudo /home/workuser/.nvm/versions/node/v4.1.1/bin/node /home/workuser/.nvm/versions/node/v4.1.1/bin/forever stopall"
  # echo "  sudo /home/workuser/.nvm/versions/node/v4.1.1/bin/node /home/workuser/.nvm/versions/node/v4.1.1/bin/forever start -p /home/workuser/.forever -l /home/workuser/.forever/node-app.log -a --workingDir /home/workuser/working/ne_api/bin/node /home/workuser/working/ne_api/bin/node/plusnao_notification_server.js"

fi

echo "DONE!!";
