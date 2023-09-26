#!/bin/bash

# プログラムID：99_fetch_similar_images.sh
# プログラム名：類似画像チェック用シェル？　用途不明

TARGET_DIR=/home/workuser/product_images_similar_check/unchecked

RSYNC_OPT=""
RSYNC_OPT="${RSYNC_OPT} -rltDv"
RSYNC_OPT="${RSYNC_OPT} -e ssh"
RSYNC_OPT="${RSYNC_OPT} --iconv=SJIS-WIN,UTF-8"
RSYNC_OPT="${RSYNC_OPT} --no-times"
RSYNC_OPT="${RSYNC_OPT} --dry-run"

CMD="rsync ${RSYNC_OPT} 'XServer:/home/plus-nao/plus-nao.com/public_html/PIC/類似画像チェックフォルダ' ${TARGET_DIR}"

echo $CMD;

${CMD};

# うまくイレギュラーが除外できないので、さくっと削除してしまう。
# rm -rf /home/workuser/product_images_mirror/itempic402/original/original/
