<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaLogisticsInternationalexpressWtdOrderCancelParam {

        
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
     * 参数示例：<pre>123456</pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->sdkStdResult["orderId"] = $orderId;
    }
    
        
        /**
    * @return 取消原因类型，可多选。<br>not_payment：未收到货款，
buyer_cancel_order：买家取消订单，
expected_delivery_time：赶不上预定发货期，
product_quality_problem：货物质量问题，
logistics_price_high：物流服务价格太高，
transport_long_time：物流服务运输时间太长，
on_service：对服务态度不满意，
operation_complex：操作太复杂，
loss_goods：货物丢失，
damaged_goods：货物破损，
no_container：没有舱位，
user_cancle_container：用户要求取消订舱，
goods_cannot_clearance：货物无法通关，
other_reasons：其它原因
    */
        public function getCancelReasonTypes() {
        $tempResult = $this->sdkStdResult["cancelReasonTypes"];
        return $tempResult;
    }
    
    /**
     * 设置取消原因类型，可多选。<br>not_payment：未收到货款，
buyer_cancel_order：买家取消订单，
expected_delivery_time：赶不上预定发货期，
product_quality_problem：货物质量问题，
logistics_price_high：物流服务价格太高，
transport_long_time：物流服务运输时间太长，
on_service：对服务态度不满意，
operation_complex：操作太复杂，
loss_goods：货物丢失，
damaged_goods：货物破损，
no_container：没有舱位，
user_cancle_container：用户要求取消订舱，
goods_cannot_clearance：货物无法通关，
other_reasons：其它原因     
     * @param array include @see String[] $cancelReasonTypes     
     * 参数示例：<pre>not_payment</pre>     
     * 此参数必填     */
    public function setCancelReasonTypes( $cancelReasonTypes) {
        $this->sdkStdResult["cancelReasonTypes"] = $cancelReasonTypes;
    }
    
        
        /**
    * @return 备注
    */
        public function getRemark() {
        $tempResult = $this->sdkStdResult["remark"];
        return $tempResult;
    }
    
    /**
     * 设置备注     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->sdkStdResult["remark"] = $remark;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>