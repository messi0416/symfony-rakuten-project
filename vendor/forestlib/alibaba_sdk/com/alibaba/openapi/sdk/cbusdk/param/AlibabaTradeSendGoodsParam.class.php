<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaTradeSendGoodsParam {

        
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
     * 参数示例：<pre>1688</pre>     
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
     * @param String $orderId     
     * 参数示例：<pre>123423</pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->sdkStdResult["orderId"] = $orderId;
    }
    
        
        /**
    * @return 订单明细ID, 多个明细请用英文逗号分隔
    */
        public function getOrderEntryIds() {
        $tempResult = $this->sdkStdResult["orderEntryIds"];
        return $tempResult;
    }
    
    /**
     * 设置订单明细ID, 多个明细请用英文逗号分隔     
     * @param String $orderEntryIds     
     * 参数示例：<pre>13234,1233</pre>     
     * 此参数必填     */
    public function setOrderEntryIds( $orderEntryIds) {
        $this->sdkStdResult["orderEntryIds"] = $orderEntryIds;
    }
    
        
        /**
    * @return 用户备注
    */
        public function getRemarks() {
        $tempResult = $this->sdkStdResult["remarks"];
        return $tempResult;
    }
    
    /**
     * 设置用户备注     
     * @param String $remarks     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemarks( $remarks) {
        $this->sdkStdResult["remarks"] = $remarks;
    }
    
        
        /**
    * @return 物流公司ID
    */
        public function getLogisticsCompanyId() {
        $tempResult = $this->sdkStdResult["logisticsCompanyId"];
        return $tempResult;
    }
    
    /**
     * 设置物流公司ID     
     * @param String $logisticsCompanyId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsCompanyId( $logisticsCompanyId) {
        $this->sdkStdResult["logisticsCompanyId"] = $logisticsCompanyId;
    }
    
        
        /**
    * @return logisticsCompanyId=8时，这个字段必填，需要填写其他的物流公司名称
    */
        public function getSelfCompanyName() {
        $tempResult = $this->sdkStdResult["selfCompanyName"];
        return $tempResult;
    }
    
    /**
     * 设置logisticsCompanyId=8时，这个字段必填，需要填写其他的物流公司名称     
     * @param String $selfCompanyName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSelfCompanyName( $selfCompanyName) {
        $this->sdkStdResult["selfCompanyName"] = $selfCompanyName;
    }
    
        
        /**
    * @return 物流公司运单号
    */
        public function getLogisticsBillNo() {
        $tempResult = $this->sdkStdResult["logisticsBillNo"];
        return $tempResult;
    }
    
    /**
     * 设置物流公司运单号     
     * @param String $logisticsBillNo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsBillNo( $logisticsBillNo) {
        $this->sdkStdResult["logisticsBillNo"] = $logisticsBillNo;
    }
    
        
        /**
    * @return 系统发货时间
    */
        public function getGmtSystemSend() {
        $tempResult = $this->sdkStdResult["gmtSystemSend"];
        return $tempResult;
    }
    
    /**
     * 设置系统发货时间     
     * @param String $gmtSystemSend     
     * 参数示例：<pre>2012-04-13 09:38:00</pre>     
     * 此参数必填     */
    public function setGmtSystemSend( $gmtSystemSend) {
        $this->sdkStdResult["gmtSystemSend"] = $gmtSystemSend;
    }
    
        
        /**
    * @return 卖家发货时间
    */
        public function getGmtLogisticsCompanySend() {
        $tempResult = $this->sdkStdResult["gmtLogisticsCompanySend"];
        return $tempResult;
    }
    
    /**
     * 设置卖家发货时间     
     * @param String $gmtLogisticsCompanySend     
     * 参数示例：<pre>2012-04-13 09:38:00</pre>     
     * 此参数必填     */
    public function setGmtLogisticsCompanySend( $gmtLogisticsCompanySend) {
        $this->sdkStdResult["gmtLogisticsCompanySend"] = $gmtLogisticsCompanySend;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>