<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductDeleteParam {

        
        /**
    * @return 商品ID
    */
        public function getProductID() {
        $tempResult = $this->sdkStdResult["productID"];
        return $tempResult;
    }
    
    /**
     * 设置商品ID     
     * @param Long $productID     
     * 参数示例：<pre>123456</pre>     
     * 此参数必填     */
    public function setProductID( $productID) {
        $this->sdkStdResult["productID"] = $productID;
    }
    
        
        /**
    * @return 站点信息，指定调用的API是属于国际站（alibaba）还是1688网站（1688）
    */
        public function getWebSite() {
        $tempResult = $this->sdkStdResult["webSite"];
        return $tempResult;
    }
    
    /**
     * 设置站点信息，指定调用的API是属于国际站（alibaba）还是1688网站（1688）     
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