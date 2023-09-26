<?php
namespace forestlib\AlibabaSdk\Plusnao\Param;

class ProductGetParam extends AbstractParam
{
  protected $defaultReturnFields = [
      'productID'
    , 'webSite'
    , 'scene'
  ];

  /**
   * @return string
   */
  public function getProductID()
  {
    return isset($this->sdkStdResult['productID']) ? $this->sdkStdResult['productID'] : null;
  }

  /**
   * @param string $productID
   */
  public function setProductID($productID)
  {
    $this->sdkStdResult['productID'] = $productID;
  }

  /**
   * @return string
   */
  public function getWebSite()
  {
    return isset($this->sdkStdResult['webSite']) ? $this->sdkStdResult['webSite'] : null;
  }

  /**
   * @param string $webSite
   */
  public function setWebSite($webSite)
  {
    $this->sdkStdResult['webSite'] = $webSite;
  }

  /**
   * @return string
   */
  public function getScene()
  {
    return isset($this->sdkStdResult['scene']) ? $this->sdkStdResult['scene'] : null;
  }

  /**
   * @param string $scene
   */
  public function setScene($scene)
  {
    $this->sdkStdResult['scene'] = $scene;
  }


  /**
   * @return string
   */
  public function getReturnFields()
  {
    return isset($this->sdkStdResult['returnFields']) ? $this->sdkStdResult['returnFields'] : null;
  }

  /**
   * @param string $returnFields
   */
  public function setReturnFields($returnFields)
  {
    $this->sdkStdResult['returnFields'] = $returnFields;
  }

  public function setDefaultReturnFields()
  {
    $this->setReturnFields($this->defaultReturnFields);
  }

}

?>
