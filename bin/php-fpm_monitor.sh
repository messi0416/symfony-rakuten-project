#!/bin/sh

#php-fpmログモニタプログラム

LOGFILE="/var/log/php7.0-fpm.log"
#LOGFILE="/root/bin/test"

COUNT=`cat $LOGFILE | grep -E "$(date +%d-%b-%Y) ($(date +%H)|$(printf "%02d" $(expr $(date +%H) - 1)))" | grep "server reached pm.max_children setting" | wc -l`

#echo $COUNT

if [ $COUNT -ge 1 ]; then
    echo "[`date +%d-%b-%Y#%H:%M:%S`] Alart for forest-ntfy."; 
    curl -H "Title: php-fpm log monitor" -H "Priority: high" -H "Tags: warning" -d "プロセスプールが断続的に溢れています" ntfy.sh/forest-ntfy
fi
