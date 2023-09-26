<?php

/**
 * タオバオWEB巡回 スクレイピング 結果ステータス定義クラス
 */
class TaobaoCheckStatuses
{
  const CHANGE_TYPE_DELETED = 1;
  const CHANGE_TYPE_SOLDOUT = 2;
  const CHANGE_TYPE_ADDED = 3;
  const CHANGE_TYPE_NAME_CHANGED = 4;
  const CHANGE_TYPE_SKU_SOLDOUT = 5;
  const CHANGE_TYPE_SKU_CHANGED = 6;
  const CHANGE_TYPE_SKU_ADDED = 7;
  const CHANGE_TYPE_PRICE_CHANGED = 8;

  /**
   * @return bool
   */
  public static function isEnvDev()
  {
    return file_exists('/this_is_dev_server');
  }

}