<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGroupSetSwitchParam {

        
        /**
    * @return 设置状态，true：已开启；false：未开启
    */
        public function getSwitchValue() {
        $tempResult = $this->sdkStdResult["switchValue"];
        return $tempResult;
    }
    
    /**
     * 设置设置状态，true：已开启；false：未开启     
     * @param Boolean $switchValue     
     * 参数示例：<pre>true</pre>     
     * 此参数必填     */
    public function setSwitchValue( $switchValue) {
        $this->sdkStdResult["switchValue"] = $switchValue;
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