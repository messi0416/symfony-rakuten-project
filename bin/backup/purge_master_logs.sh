#!/bin/sh

# プログラムID：purge_master_logs.sh
# プログラム名：バイナリログ削除シェル

# 一定時間毎にmasterのバイナリログを削除する。（1日単位ではディスクが持たないため）
mysql -h160.16.50.121 -uroot -pHXKG6Hmm < /home/workuser/ne_api/bin/backup/purge_master_logs.sql