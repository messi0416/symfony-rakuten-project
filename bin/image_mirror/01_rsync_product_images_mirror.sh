#!/bin/bash

# プログラムID：01_rsync_product_images_mirror.sh
# プログラム名：Xserverへの画像転送シェル？ Xserverからでも画像はBatchSV02から読んでるはずなので今は使ってないはず

TARGET_DIR=/home/workuser/product_images_mirror/

RSYNC_OPT=""
RSYNC_OPT="${RSYNC_OPT} -rltDv"
RSYNC_OPT="${RSYNC_OPT} -e ssh"
RSYNC_OPT="${RSYNC_OPT} --iconv=SJIS-WIN,UTF-8"
RSYNC_OPT="${RSYNC_OPT} --no-times"
RSYNC_OPT="${RSYNC_OPT} --exclude=*.JPG"
RSYNC_OPT="${RSYNC_OPT} --include=00*/"
RSYNC_OPT="${RSYNC_OPT} --include=fullsizepic/"
RSYNC_OPT="${RSYNC_OPT} --include=itempic*/"
RSYNC_OPT="${RSYNC_OPT} --include=share/"
RSYNC_OPT="${RSYNC_OPT} --include=shohin/"
RSYNC_OPT="${RSYNC_OPT} --include=shohin*/"
RSYNC_OPT="${RSYNC_OPT} --include=shouhinpic*/"
RSYNC_OPT="${RSYNC_OPT} --include=tm/"
RSYNC_OPT="${RSYNC_OPT} --include=**/original"
RSYNC_OPT="${RSYNC_OPT} --exclude=**/original/original/"
RSYNC_OPT="${RSYNC_OPT} --exclude=**/original/original/*"
RSYNC_OPT="${RSYNC_OPT} --include=**/original/*"
RSYNC_OPT="${RSYNC_OPT} --exclude=*"
# RSYNC_OPT="${RSYNC_OPT} --dry-run"

CMD="rsync ${RSYNC_OPT} XServer:/home/plus-nao/plus-nao.com/public_html/PIC/ ${TARGET_DIR}"

echo $CMD;

${CMD};

# うまくイレギュラーが除外できないので、さくっと削除してしまう。
rm -rf /home/workuser/product_images_mirror/itempic402/original/original/

# イレギュラー追加
scp 'XServer:/home/plus-nao/plus-nao.com/public_html/PIC/itempic506/*.jpg' /home/workuser/product_images_mirror/itempic506/original/
rsync -rltDv -e ssh --iconv=SJIS-WIN,UTF-8 --no-times 'XServer:/home/plus-nao/plus-nao.com/public_html/PIC/itempic507/' /home/workuser/product_images_mirror/itempic507/original/


