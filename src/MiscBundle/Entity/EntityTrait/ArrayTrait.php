<?php

namespace MiscBundle\Entity\EntityTrait;

use ReflectionClass;
use ReflectionProperty;

Trait ArrayTrait
{
  /**
   * スカラ値のみの配列に変換する（JSON変換前処理用）
   * @param string $keyFormat
   * @param string $baseClassName
   * @return array
   */
  public function toScalarArray($keyFormat = null, $baseClassName = null)
  {
    $result = [];

    $className = get_class($this);

    if ($baseClassName) {
      $ref = new ReflectionClass($baseClassName);
    } else if (preg_match('/Proxies\\\\__CG__\\\\(.*)/', $className, $m)) {
      $ref = new ReflectionClass($m[1]);
    } else {
      $ref = new ReflectionClass($this);
    }

    $properties = $ref->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
    foreach($properties as $prop) {

      $prop->setAccessible(true);

      $k = $prop->getName();
      $v = $prop->getValue($this);

      if ($keyFormat == 'snake') {
        $k = $this->convertToSnakeCase($k);
      } else if ($keyFormat == 'camel') {
        $k = $this->convertToCamelCase($k);
      }

      // 日付
      if ($v instanceof \DateTime) {
        $v = $v->format('Y-m-d H:i:s');
      }

      // その他のオブジェクトや配列ならスキップ
      if (is_array($v) || is_object($v)) {
        continue;
      }

      $result[$k] = $v;
    }

    return $result;
  }

  /**
   * 文字列をスネークケースへ変換
   * @param string $str
   * @return string
   */
  private function convertToSnakeCase($str)
  {
    return preg_replace_callback('/(^[A-Z]+)|([A-Z]+)/', function($matches){
      return isset($matches[2]) ? ('_' . strtolower($matches[2])) : strtolower($matches[1]);
    }, $str);
  }

  /**
   * 文字列をキャメルケースへ変換
   * @param string $str
   * @return string
   */
  private function convertToCamelCase($str)
  {
    return preg_replace_callback('/(_[a-z0-9])/', function($matches){
      return str_replace('_', '', strtoupper($matches[1]));
    }, $str);
  }

}

?>
