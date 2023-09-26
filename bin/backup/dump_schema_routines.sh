#!/bin/sh

# プログラムID：dump_schema_routines.sh
# プログラム名：スキーマだけ抜くシェル（？）、運用箇所は不明

# スキーマ＋ルーチンだけダンプ
mysqldump --routines --no-data --skip-dump-date -ukir084880 -pdadaabc2323 plusnao_db > schema_with_stored.sql
# そしてDEFINER 除去
sed -i -e '/^\/\*!50013 DEFINER=/d' schema_with_stored.sql
sed -i -E 's/CREATE DEFINER=.+ (FUNCTION|PROCEDURE)/CREATE \1/g' schema_with_stored.sql
sed -i -E 's/\/\*\!50020 DEFINER=`.*`@`%`\*\/ //g' schema_with_stored.sql

# AUTO_INCREMENT設定も除去（スキーマ定義には不要。差分が見づらい）
sed -i -E 's/ AUTO_INCREMENT=\d+//g' schema_with_stored.sql


