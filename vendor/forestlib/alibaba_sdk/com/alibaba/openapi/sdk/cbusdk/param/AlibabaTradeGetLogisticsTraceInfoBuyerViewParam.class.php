<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaTradeGetLogisticsTraceInfoBuyerViewParam {

        
        /**
    * @return 该订单下的物流编号
    */
        public function getLogisticsId() {
        $tempResult = $this->sdkStdResult["logisticsId"];
        return $tempResult;
    }
    
    /**
     * 设置该订单下的物流编号     
     * @param String $logisticsId     
     * 参数示例：<pre>AL8234243</pre>     
     * 此参数必填     */
    public function setLogisticsId( $logisticsId) {
        $this->sdkStdResult["logisticsId"] = $logisticsId;
    }
    
        
        /**
    * @return 订单号
    */
        public function getOrderId() {
        $tempResult = $this->sdkStdResult["orderId"];
        return $tempResult;
    }
    
    /**
     * 设置订单号     
     * @param Long $orderId     
     * 参数示例：<pre>13342343</pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->sdkStdResult["orderId"] = $orderId;
    }
    
        
        /**
    * @return 是1688业务还是icbu业务
    */
        public function getWebSite() {
        $tempResult = $this->sdkStdResult["webSite"];
        return $tempResult;
    }
    
    /**
     * 设置是1688业务还是icbu业务     
     * @param String $webSite     
     * 参数示例：<pre>1688或者alibaba</pre>     
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