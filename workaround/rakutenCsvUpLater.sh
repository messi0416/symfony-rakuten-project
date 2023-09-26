#!/bin/bash

echo "ExportRakutenSKUAttribute"
/home/workuser/ne_api/app/console --env=prod batch:update-sku-rakuten-attribute
sleep 60

echo "ExportRakutenCsvRakuten"
/home/workuser/ne_api/app/console --env=prod batch:export-csv-rakuten --target-shop=rakuten --do-upload=0
sleep 60

echo "ExportRakutenCsvMotto"
/home/workuser/ne_api/app/console --env=prod batch:export-csv-rakuten --target-shop=motto --skip-common-process=1 --skip-rakutencommon-process=1 --do-upload=0
sleep 60

echo "ExportRakutenCsvlaforest"
/home/workuser/ne_api/app/console --env=prod batch:export-csv-rakuten --target-shop=laforest --skip-common-process=1 --skip-rakutencommon-process=1 --do-upload=0
sleep 60

echo "ExportRakutenCsvDolcissimo"
/home/workuser/ne_api/app/console --env=prod batch:export-csv-rakuten --target-shop=dolcissimo --skip-common-process=1 --skip-rakutencommon-process=1 --do-upload=0
sleep 60

echo "ExportRakutenCsvGekipla"
/home/workuser/ne_api/app/console --env=prod batch:export-csv-rakuten --target-shop=gekipla --skip-common-process=1 --skip-rakutencommon-process=1 --do-upload=0
sleep 60


echo "ExportRakutenGoldCsv"
/home/workuser/ne_api/app/console --env=prod batch:enqueue --command=export_csv_rakuten_gold
sleep 60


echo "UPLOAD"
echo "UploadRakutenCsvRakuten"
/home/workuser/ne_api/app/console batch:export-csv-rakuten-upload --env=prod --target-shop=rakuten
sleep 60

echo "UploadRakutenCsvMotto"
/home/workuser/ne_api/app/console batch:export-csv-rakuten-upload --env=prod --target-shop=motto
sleep 60

echo "UploadRakutenCsvlaforest"
/home/workuser/ne_api/app/console batch:export-csv-rakuten-upload --env=prod --target-shop=laforest
sleep 60

echo "UploadRakutenCsvDolcissimo"
/home/workuser/ne_api/app/console batch:export-csv-rakuten-upload --env=prod --target-shop=dolcissimo
sleep 60

echo "UploadRakutenCsvGekipla"
/home/workuser/ne_api/app/console batch:export-csv-rakuten-upload --env=prod --target-shop=gekipla
sleep 60
