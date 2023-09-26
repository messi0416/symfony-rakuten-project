<?php
namespace forestlib\AlibabaSdk\Plusnao\Param;

class CompanyGetParam extends AbstractParam
{
  /**
   * @return string
   */
  public function getMemberId()
  {
    return isset($this->sdkStdResult['memberId']) ? $this->sdkStdResult['memberId'] : null;
  }

  /**
   * @param string $memberId
   */
  public function setMemberId($memberId)
  {
    $this->sdkStdResult["memberId"] = $memberId;
  }
}

?>
