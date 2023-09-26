<?php
namespace forestlib\AlibabaSdk\Plusnao\Param;

abstract class AbstractParam
{
  protected $sdkStdResult = array();

  public function getSdkStdResult()
  {
    return $this->sdkStdResult;
  }
}

?>
