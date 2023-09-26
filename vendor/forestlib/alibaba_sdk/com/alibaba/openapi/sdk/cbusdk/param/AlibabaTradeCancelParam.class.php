<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaTradeCancelParam {

        
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
    * @return 交易id
    */
        public function getTradeID() {
        $tempResult = $this->sdkStdResult["tradeID"];
        return $tempResult;
    }
    
    /**
     * 设置交易id     
     * @param Long $tradeID     
     * 参数示例：<pre>123456</pre>     
     * 此参数必填     */
    public function setTradeID( $tradeID) {
        $this->sdkStdResult["tradeID"] = $tradeID;
    }
    
        
        /**
    * @return 原因描述
    */
        public function getCancelReason() {
        $tempResult = $this->sdkStdResult["cancelReason"];
        return $tempResult;
    }
    
    /**
     * 设置原因描述     
     * @param String $cancelReason     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCancelReason( $cancelReason) {
        $this->sdkStdResult["cancelReason"] = $cancelReason;
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