#!/bin/sh
# プログラムID：mysqlbackup.sh
# プログラム名：MySQLバックアップシェルスクリプト
# 作成日　　　：2015/07/14
# 作成者　　　：石橋辰徳,hirai
# 修正履歴　　：2015/08/13　石橋　Rev1.0.1　ストアドを個別にバックアップするように修正
# 修正履歴　　：2015/08/13　hirai daily, monthly 追加 その他
# 修正履歴　　：2017/08/02　hirai 整理
# 修正履歴　　：2018/07/18　hirai レプリケーション用ロック処理追加

# バックアップファイルを何回分残しておくか
period=7 # 日次
weeks=4 # 週次

PROD=1 # 本番環境
if [ -e /this_is_dev_server ]; then
  PROD=0
fi

# 接続設定ファイル
conf_file_root="/home/workuser/ne_api/bin/backup/conf/root.cnf"
# conf_file_plusnao="/home/workuser/ne_api/bin/backup/conf/plusnao.cnf" #=> こちらはひとまず使わない
conf_file_plusnao_main="/home/workuser/ne_api/bin/backup/conf/plusnao.cnf.main"

db_name_main="plusnao_db"
db_name_log="plusnao_log_db"

# -- テスト環境
if [ ${PROD} -eq 0 ]; then
  conf_file_root="/home/workuser/working/ne_api/bin/backup/conf/develop_root.cnf"
  # conf_file_plusnao="/home/workuser/working/ne_api/bin/backup/conf/develop.cnf"
  conf_file_plusnao_main="/home/workuser/working/ne_api/bin/backup/conf/develop_root.cnf"

  db_name_main="test_plusnao_db"
  db_name_log="test_plusnao_log_db"
fi

# バックアップ保存ディレクトリ
dirpath='/home/workuser/backup/daily'
dirpath_monthly='/home/workuser/backup/monthly'
dirpath_weekly='/home/workuser/backup/weekly'

# ファイル名を定義
filename=`date +%Y%m%d`
filename_stored="${filename}_stored"
filename_schema="${filename}_schema"

# 通知コマンド
command_notify="/home/workuser/ne_api/app/console --env=prod batch:daily-db-backup-check --filename=${filename}"
if [ ${PROD} -eq 0 ]; then
  command_notify="/home/workuser/working/ne_api/app/console batch:daily-db-backup-check --filename=${filename}"
fi

# 通知処理：開始
last_log_id=`${command_notify} --mode=start`

# mysqldump実行(データ、ストアドプロシージャ、スキーマ、関数)
echo "MySQLデータバックアップ中..."

# レプリケーション用 サーバロック処理。複数DBのダンプを取るため、サーバごと固めてしまう。
mysql --defaults-file="${conf_file_root}" -e "STOP SLAVE;"
mysql --defaults-file="${conf_file_root}" -e "FLUSH TABLES WITH READ LOCK;"
mysql --defaults-file="${conf_file_root}" -e "SET GLOBAL read_only = ON;"

# bin-log ポジションをログに出力
echo "SLAVE STATUS:"
mysql --defaults-file="${conf_file_root}" -e "SHOW SLAVE STATUS \G;"

# view一覧取得。 データダンプからは除外、スキーマダンプでも別途出力し、DB名を除去する。
sql=$(cat << EOS
  SELECT
    TABLE_NAME
  FROM INFORMATION_SCHEMA.TABLES
  WHERE table_schema = '${db_name_main}'
    AND TABLE_TYPE = 'VIEW'
    ORDER BY TABLE_NAME
  ;
EOS
)

VIEWS=`mysql --defaults-file="${conf_file_root}" ${db_name_main} -N -e "${sql}"`

ADDITIONAL_OPTIONS=""
for i in ${VIEWS}
do
  ADDITIONAL_OPTIONS="${ADDITIONAL_OPTIONS} --ignore-table=${db_name_main}.${i}"
done;

echo "`date` 全バックアップ開始"

# レプリケーション用 ロック出力
ADDITIONAL_OPTIONS="${ADDITIONAL_OPTIONS} --lock-all-tables"

# root等の権限でないと、レプリケーション用の flush-logs などができない。
# 全データdump （schema, stored なし）
mysqldump --defaults-file="${conf_file_root}" --skip-triggers --skip-add-drop-table --no-create-info ${ADDITIONAL_OPTIONS} ${db_name_main} | gzip -c > "${dirpath}/${filename}.gz"

# スキーマのバックアップ
# トリガ・ルーチンは含まない。
# 一部VIEWのダンプにはDB名が含まれるため、テストDBへのリストア時にエラーとなってしまうので、別途出力する。
echo "`date` スキーマのみバックアップ"
ADDITIONAL_OPTIONS=""
for i in ${VIEWS}
do
  ADDITIONAL_OPTIONS="${ADDITIONAL_OPTIONS} --ignore-table=${db_name_main}.${i}"
done;

mysqldump --defaults-file="${conf_file_root}" --skip-triggers --no-data --skip-dump-date ${db_name_main} ${ADDITIONAL_OPTIONS} > "${dirpath}/${filename_schema}.sql"

# VIEWの別途出力（追記）
echo "" >> "${dirpath}/${filename_schema}.sql"
OUTPUT_TABLES=""
for i in ${VIEWS}
do
  OUTPUT_TABLES="${OUTPUT_TABLES} ${i}"
done;

mysqldump --defaults-file="${conf_file_root}" --no-data --skip-dump-date ${db_name_main} ${OUTPUT_TABLES} \
 | sed -E "s/\`${db_name_main}\`\.//g" \
 >> "${dirpath}/${filename_schema}.sql"

# そしてDEFINER 除去
sed -i -e '/^\/\*![0-9]* DEFINER=/d' "${dirpath}/${filename_schema}.sql"
sed -i -E 's/CREATE DEFINER=.+ (FUNCTION|PROCEDURE)/CREATE \1/g' "${dirpath}/${filename_schema}.sql"
sed -i -E "s/\/\*![0-9]* DEFINER=\`[^\`]+\`@\`[^\`]+\`\*\/ //g" "${dirpath}/${filename_schema}.sql"
# AUTO_INCREMENT設定も除去（スキーマ定義には不要。差分が見づらい）
sed -i -E 's/ AUTO_INCREMENT=[0-9]+//g' "${dirpath}/${filename_schema}.sql"

echo "`date` トリガ・ルーチンのみバックアップ開始"
# ※トリガ・ルーチンはレプリケーション時に作成していないため本番から直接取得（行ベースなのでなくてもよい、はず。）
mysqldump --defaults-file="${conf_file_plusnao_main}" ${db_name_main} --no-create-info --no-data --routines --triggers --skip-dump-date > "${dirpath}/${filename_stored}.sql"

# ストアドのリストア用補正 : DEFINER 記述を削除する （テストDBへのリストアなど）
sed -i -e '/^\/\*![0-9]* DEFINER=/d' "${dirpath}/${filename_stored}.sql"
sed -i -E 's/CREATE DEFINER=.+ (FUNCTION|PROCEDURE)/CREATE \1/g' "${dirpath}/${filename_stored}.sql"
sed -i -E "s/\/\*![0-9]* DEFINER=\`[^\`]+\`@\`[^\`]+\`\*\/ //g" "${dirpath}/${filename_stored}.sql"


echo "`date` ログDB スキーマのみバックアップ"
mysqldump --defaults-file="${conf_file_root}" ${db_name_log} --routines --no-data --skip-dump-date > "${dirpath}/${filename}_log_schema_with_stored.sql"
# そしてDEFINER 除去
sed -i -e '/^\/\*![0-9]* DEFINER=/d' "${dirpath}/${filename}_log_schema_with_stored.sql"
sed -i -E 's/CREATE DEFINER=.+ (FUNCTION|PROCEDURE)/CREATE \1/g' "${dirpath}/${filename}_log_schema_with_stored.sql"
sed -i -E "s/\/\*![0-9]* DEFINER=\`[^\`]+\`@\`[^\`]+\`\*\/ //g" "${dirpath}/${filename}_log_schema_with_stored.sql"
# AUTO_INCREMENT設定も除去（スキーマ定義には不要。差分が見づらい）
sed -i -E 's/ AUTO_INCREMENT=[0-9]+//g' "${dirpath}/${filename}_log_schema_with_stored.sql"

echo "`date` ログDB 全バックアップ開始"

# レプリケーション用 ロック出力
ADDITIONAL_OPTIONS=""
ADDITIONAL_OPTIONS="${ADDITIONAL_OPTIONS} --lock-all-tables"

mysqldump --defaults-file="${conf_file_root}" ${ADDITIONAL_OPTIONS} ${db_name_log} | gzip -c > "${dirpath}/${filename}_log.gz"

# 古いバックアップファイルを削除
oldfile=`date --date "$period days ago" +%Y%m%d`
echo ${oldfile}
rm -f ${dirpath}/${oldfile}.gz
rm -f ${dirpath}/${oldfile}_schema.sql
rm -f ${dirpath}/${oldfile}_stored.sql
rm -f ${dirpath}/${oldfile}_log_schema_with_stored.sql
rm -f ${dirpath}/${oldfile}_log.gz

echo "============================"
ls -la ${dirpath}/${filename}*
echo "============================"

# 通知処理 ＆ 失敗時メール送信処理：日次終了
last_log_id=`${command_notify} --mode=daily_end --path=${dirpath} --last-log=${last_log_id}`

# 月次処理 毎月題1月曜日
weekday=`date +%w`
today=`date +%d`
if [ ${weekday} -eq 1 ] && [ $((${today} / 7)) -eq 0 ]; then
  echo "`date +%Y/%m/%d` 月次バックアップを保存します。"
  cp ${dirpath}/${filename}.gz  ${dirpath_monthly}/
  cp ${dirpath}/${filename_schema}.sql ${dirpath_monthly}/
  cp ${dirpath}/${filename_stored}.sql ${dirpath_monthly}/
  cp ${dirpath}/${filename}_log_schema_with_stored.sql ${dirpath_monthly}/
  cp ${dirpath}/${filename}_log.gz ${dirpath_monthly}/

  # 通知処理 ＆ 失敗時メール送信処理：月次終了
  last_log_id=`${command_notify} --mode=monthly_end --path=${dirpath_monthly} --last-log=${last_log_id}`
fi
                                                
# 週次処理 毎週月曜日
if [ ${weekday} -eq 1 ]; then
  echo "`date +%Y/%m/%d` 週次バックアップを保存します。"
  cp ${dirpath}/${filename}.gz  ${dirpath_weekly}/
  cp ${dirpath}/${filename_schema}.sql ${dirpath_weekly}/
  cp ${dirpath}/${filename_stored}.sql ${dirpath_weekly}/
  cp ${dirpath}/${filename}_log_schema_with_stored.sql ${dirpath_weekly}/
  cp ${dirpath}/${filename}_log.gz  ${dirpath_weekly}/

  # 古いバックアップファイルを削除
  oldfile=`date --date "$weeks weeks ago" +%Y%m%d`
  rm -f ${dirpath_weekly}/${oldfile}.gz
  rm -f ${dirpath_weekly}/${oldfile}_schema.sql
  rm -f ${dirpath_weekly}/${oldfile}_stored.sql
  rm -f ${dirpath_weekly}/${oldfile}_log_schema_with_stored.sql
  rm -f ${dirpath_weekly}/${oldfile}_log.gz

  # 通知処理 ＆ 失敗時メール送信処理：週次終了
  last_log_id=`${command_notify} --mode=weekly_end --path=${dirpath_weekly} --last-log=${last_log_id}`
fi

# レプリケーション用 サーバロック解除
mysql --defaults-file="${conf_file_root}" -e "SET GLOBAL read_only = OFF;"
mysql --defaults-file="${conf_file_root}" -e "UNLOCK TABLES;"
mysql --defaults-file="${conf_file_root}" -e "START SLAVE;"

# 通知処理 ＆ 失敗時メール送信処理：日次終了
last_log_id=`${command_notify} --mode=end --last-log=${last_log_id}`

echo "`date` MySQLデータバックアップ 終了しました"
