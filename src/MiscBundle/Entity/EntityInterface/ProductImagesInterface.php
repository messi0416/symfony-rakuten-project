<?php
namespace MiscBundle\Entity\EntityInterface;

interface ProductImagesInterface
{
  /**
   * 画像種別
   * @return string
   */
  public function getType();

  /**
   * それぞれの画像ルート以下のディレクトリ+ファイル名のパスを取得
   * @return string
   */
  public function getFileDirPath();

  /**
   * ディレクトリ名取得
   * @return string
   */
  public function getDirectory();

  /**
   * ファイル名取得
   * @return string
   */
  public function getFilename();

}