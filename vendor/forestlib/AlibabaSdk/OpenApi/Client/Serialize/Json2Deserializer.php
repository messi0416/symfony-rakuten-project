<?php
namespace forestlib\AlibabaSdk\OpenApi\Client\Serialize;

use forestlib\AlibabaSdk\OpenApi\Client\Entity\ParentResult;
use forestlib\AlibabaSdk\OpenApi\Client\Exception\OceanException;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\DataProtocol;

class Json2Deserializer implements DeSerializer
{
  public function supportedContentType()
  {
    return DataProtocol::json2;
  }

  /**
   * @param DeSerializer $deSerializer
   * @param ParentResult $resultDefinition  (interfaceで受けるべき？)
   * @param string $charSet
   * @return ParentResult
   */
  public function deSerialize($deSerializer, $resultDefinition, $charSet = null)
  {
    $stdResult = json_decode($deSerializer);
    $resultDefinition->setStdResult($stdResult);
    return $resultDefinition;
  }

  public function buildException($deSerializer, $resultType, $charSet = null)
  {
    $exceptionStdResult = json_decode($deSerializer);
    $errorCode =  isset($exceptionStdResult->{"error_code"})
                ? $exceptionStdResult->{"error_code"}
                : (
                    isset($exceptionStdResult->{"error"})
                  ? $exceptionStdResult->{"error"}
                  : 'unknown_error'
                );
    $errorMessage = isset($exceptionStdResult->{"error_message"})
                ? $exceptionStdResult->{"error_message"}
                : (
                    isset($exceptionStdResult->{"error_description"})
                  ? $exceptionStdResult->{"error_description"}
                  : 'unknown error occurred'
                );

    $oceanException = new OceanException ($errorMessage);
    $oceanException->setErrorCode($errorCode);
    return $oceanException;
  }
}

?>
