<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaLogisticsInternationalexpressWtdOrderGetParam {

        
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
    
        
        /**
    * @return 订单ID
    */
        public function getOrderId() {
        $tempResult = $this->sdkStdResult["orderId"];
        return $tempResult;
    }
    
    /**
     * 设置订单ID     
     * @param Long $orderId     
     * 参数示例：<pre>12341234</pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->sdkStdResult["orderId"] = $orderId;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>