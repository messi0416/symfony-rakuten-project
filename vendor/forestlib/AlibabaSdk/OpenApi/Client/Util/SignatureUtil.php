<?php
namespace forestlib\AlibabaSdk\OpenApi\Client\Util;

use forestlib\AlibabaSdk\OpenApi\Client\Policy\ClientPolicy;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\RequestPolicy;

class SignatureUtil
{

  /**
   *
   * @param string $path
   * @param array $parameters
   * @param RequestPolicy $requestPolicy
   * @param ClientPolicy $clientPolicy
   * @return string
   */
  public static function signature($path, array $parameters, RequestPolicy $requestPolicy, ClientPolicy $clientPolicy)
  {
    $paramsToSign = array();
    foreach ($parameters as $k => $v) {
      $paramToSign = $k . $v;
      Array_push($paramsToSign, $paramToSign);
    }
    sort($paramsToSign);
    $implodeParams = implode($paramsToSign);
    $pathAndParams = $path . $implodeParams;
    $sign = hash_hmac("sha1", $pathAndParams, $clientPolicy->secKey, true);
    $signHexWithLowercase = bin2hex($sign);
    $signHexUppercase = strtoupper($signHexWithLowercase);
    return $signHexUppercase;
  }
}

?>
