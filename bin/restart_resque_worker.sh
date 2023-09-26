#!/bin/bash

# プログラムID：restart_resque_worker.sh
# プログラム名：ワーカー再起動用シェル(BatchSV02)

usage_exit() {
        echo "Usage: $0 [-e env]" 1>&2
        exit 1
}

while getopts e:h OPT
do
    case ${OPT} in
        e)  APP_ENV=$OPTARG
            ;;
        h)  usage_exit
            ;;
        \?) usage_exit
            ;;
    esac
done
shift $((OPTIND - 1))

if [ "${APP_ENV}" = "" ]; then
  APP_ENV="test"
fi

echo "ENV: ${APP_ENV}";

# ディレクトリ
SCRIPT_DIR=$(cd $(dirname $(readlink -f $0 || echo $0));pwd -P);
APP_DIR=$(dirname $SCRIPT_DIR);

cd ${APP_DIR};

# 全停止
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-stop --all;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:scheduledworker-stop;

${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start main;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start neUpload;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start nonExclusive;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start rakutenCsvUpload;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start wowmaCsvUpload;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start q10CsvUpload;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start ppmCsvUpload;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start alibabaApi;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start productImage;
${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:worker-start productSales;


# scheduledworkerがPIDファイルをapp/cacheに作る微妙仕様のため、PIDファイルがないことはむしろ日常。
# その場合には、強制終了する。
ps=(`pgrep -f "ResqueBundle/Command/../bin/resque-scheduler"`)
for i in "${ps[@]}"
do
  if [ "${i}" != "" ]; then
    cmd="kill -9 ${i}"
    echo "stop remained scheduledworker : ${cmd}"
    `${cmd}`
  fi
done

${APP_DIR}/app/console --env=${APP_ENV} bcc:resque:scheduledworker-start --force;

echo "restart resque workers done!";

