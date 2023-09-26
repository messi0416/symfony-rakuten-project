<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPaymentOrderBankCreateParam {

        
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
     * 参数示例：<pre></pre>     
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