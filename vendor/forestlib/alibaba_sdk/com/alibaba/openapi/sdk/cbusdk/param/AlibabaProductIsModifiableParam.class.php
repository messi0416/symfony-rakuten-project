<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductIsModifiableParam {

        
        /**
    * @return 
    */
        public function getProductIds() {
        $tempResult = $this->sdkStdResult["productIds"];
        return $tempResult;
    }
    
    /**
     * 设置     
     * @param array include @see Long[] $productIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductIds( $productIds) {
        $this->sdkStdResult["productIds"] = $productIds;
    }
    
        
        /**
    * @return 
    */
        public function getWebSite() {
        $tempResult = $this->sdkStdResult["webSite"];
        return $tempResult;
    }
    
    /**
     * 设置     
     * @param String $webSite     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWebSite( $webSite) {
        $this->sdkStdResult["webSite"] = $webSite;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>