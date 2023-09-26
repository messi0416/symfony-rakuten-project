<?php

namespace MiscBundle\Entity\EntityTrait;

Trait FillTimestampTrait
{
  /**
   * 保存前処理 タイムスタンプ更新
   * 更新日時の更新はDBのON UPDATEに任せる...ことができなかった。（null代入で null をSQLに突っ込むしかできない無能ライブラリ）
   */
  public function fillTimestamps()
  {
    if (property_exists($this, 'created') && is_null($this->created)) {
      $this->created = new \DateTime();
    }

    if (property_exists($this, 'updated') && is_null($this->updated)) {
      $this->updated = new \DateTime();
    }
  }

}

?>
