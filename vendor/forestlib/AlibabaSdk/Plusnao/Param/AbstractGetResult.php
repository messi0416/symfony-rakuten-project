<?php
namespace forestlib\AlibabaSdk\Plusnao\Param;

use forestlib\AlibabaSdk\OpenApi\Client\Entity\ParentResult;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

class AbstractGetResult extends ParentResult
{
  public static $ENTITY_CLASS = '';

  /**
   * @return stdClass|null
   */
  public function getResult()
  {
    $stdResult = $this->getStdResult();
    return $stdResult && isset($stdResult->result) ? $stdResult->result: null;
  }

  /**
   * @return stdClass|null
   */
  public function getReturnData()
  {
    $result = $this->getResult();
    return $result ? $result->toReturn : null;
  }

  /**
   * @return stdClass|null
   */
  public function getReturnOne()
  {
    $return = $this->getReturnData();
    return $return ? (is_array($return) && count($return) ? $return[0] : $return) : null;
  }

  /**
   * @return bool
   */
  public function isSuccess()
  {
    $result = $this->getResult();
    return $result ? boolval($result->success) : false;
  }

  /**
   * @return int
   */
  public function getTotal()
  {
    $result = $this->getResult();
    return $result ? intval($result->total) : 0;
  }

  /**
   * 結果をクラスに変換
   * @param stdClass $data
   * @param string $entityClass
   * @return null|object
   */
  public function hydrate($data, $entityClass = null)
  {
    if (!$data || ! ($data instanceof stdClass)) {
      return null;
    }

    $entityClass = $entityClass ? $entityClass : static::$ENTITY_CLASS;
    if (!$entityClass) {

      $className = get_class($this); // child class
      $parts = explode('\\', $className);
      if ($parts) {
        $name = array_pop($parts);
        array_pop($parts); // change namespace
        array_push($parts, 'Entity');

        $name = str_replace('GetResult', '', $name);
        array_push($parts, $name);
        $entityClass = implode('\\', $parts);
      }
    }

    if (!$entityClass) {
      throw new \RuntimeException('can not determine entity class name.');
    }
    if (!class_exists($entityClass)) {
      throw new \RuntimeException('class not found. [' . $entityClass . ']');
    }

    $class = new $entityClass();
    $classRef = new ReflectionClass($entityClass);
    $publicProperties = $classRef->getProperties(ReflectionProperty::IS_PUBLIC);

    foreach($publicProperties as $prop) {
      if ($data instanceof stdClass) {
        if (property_exists($data, $prop->getName())) {
          $prop->setValue($class, $data->{$prop->getName()});
        }
      }
    }

    return $class;
  }

  /**
   * 結果をクラスオブジェクトにして返す
   * @param null $entityClass
   * @return null|object
   */
  public function getHydratedOne($entityClass = null)
  {
    $return = $this->getReturnOne();
    if (!$return) {
      return null;
    }

    return $this->hydrate($return, $entityClass);
  }

  /**
   * 結果配列をクラスオブジェクトの配列にして返す
   * @param null $entityClass
   * @return array
   */
  public function getHydratedList($entityClass = null)
  {
    $return = $this->getReturnData();
    if (!is_array($return)) {
      $return = [ $return ];
    }

    $result = [];
    foreach($return as $data) {
      $result[] = $this->hydrate($data, $entityClass);
    }

    return $result;
  }

}
