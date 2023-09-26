#!/bin/bash

# プログラムID：02_original_mv.sh
# プログラム名：移行用シェル？
# 移行用： XServer ミラーディレクトリから、product_images_original へコピーする。
#          この処理の後、画像チェック最終日時をnullにして画像アップロード前処理を行うことで、
#          product_images にアップロード用画像が一式作成される。

cd /home/workuser/product_images_mirror/

# cp コマンドの ディレクトリ -> ディレクトリ の仕様で、下記で _mirror/xxx/original/ 内のファイルが、
# ディレクトリを作成しながら _original/xxx/ 以下へコピーされる
find . -type d -name "original" | sed -e "s/original//" | xargs -i sh -c 'mkdir -p /home/workuser/product_images_original/{}/; cp -ur {}/original/* /home/workuser/product_images_original/{}/'
