<?php
namespace forestlib\AlibabaSdk\OpenApi\Client\Serialize;

use forestlib\AlibabaSdk\OpenApi\Client\Policy\DataProtocol;

class SerializerProvider
{
  private static $serializerStore = array();
  private static $deSerializerStore = array();
  private static $isInitialized = false;

  private static function initial()
  {
    self::$serializerStore [DataProtocol::param2] = new Param2RequestSerializer ();
    self::$serializerStore [DataProtocol::http] = new Param2RequestSerializer ();
    self::$deSerializerStore [DataProtocol::json2] = new Json2Deserializer ();
    self::$deSerializerStore [DataProtocol::param2] = new Json2Deserializer ();
    self::$deSerializerStore [DataProtocol::http] = new Json2Deserializer ();
    self::$isInitialized = true;
  }

  public static function getSerializer($key)
  {
    if (!self::$isInitialized) {
      self::initial();
    }
    $result = self::$serializerStore [$key];
    return $result;
  }

  public static function getDeSerializer($key)
  {
    if (!self::$isInitialized) {
      self::initial();
    }
    $result = self::$deSerializerStore [$key];
    return $result;
  }
}

?>
