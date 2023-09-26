<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaTradeRefundParam {

        
        /**
    * @return 退款单Id
    */
        public function getRefundId() {
        $tempResult = $this->sdkStdResult["refundId"];
        return $tempResult;
    }
    
    /**
     * 设置退款单Id     
     * @param String $refundId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRefundId( $refundId) {
        $this->sdkStdResult["refundId"] = $refundId;
    }
    
        
        /**
    * @return 操作者memberID。如果为系统，则传入system
    */
        public function getMemberId() {
        $tempResult = $this->sdkStdResult["memberId"];
        return $tempResult;
    }
    
    /**
     * 设置操作者memberID。如果为系统，则传入system     
     * @param String $memberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMemberId( $memberId) {
        $this->sdkStdResult["memberId"] = $memberId;
    }
    
        
        /**
    * @return 退款操作类型
    */
        public function getRefundOperateType() {
        $tempResult = $this->sdkStdResult["refundOperateType"];
        return $tempResult;
    }
    
    /**
     * 设置退款操作类型     
     * @param String $refundOperateType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRefundOperateType( $refundOperateType) {
        $this->sdkStdResult["refundOperateType"] = $refundOperateType;
    }
    
        
        /**
    * @return 卖家收货地址
    */
        public function getAddress() {
        $tempResult = $this->sdkStdResult["address"];
        return $tempResult;
    }
    
    /**
     * 设置卖家收货地址     
     * @param String $address     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->sdkStdResult["address"] = $address;
    }
    
        
        /**
    * @return 邮编
    */
        public function getPost() {
        $tempResult = $this->sdkStdResult["post"];
        return $tempResult;
    }
    
    /**
     * 设置邮编     
     * @param String $post     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPost( $post) {
        $this->sdkStdResult["post"] = $post;
    }
    
        
        /**
    * @return 电话
    */
        public function getPhone() {
        $tempResult = $this->sdkStdResult["phone"];
        return $tempResult;
    }
    
    /**
     * 设置电话     
     * @param String $phone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhone( $phone) {
        $this->sdkStdResult["phone"] = $phone;
    }
    
        
        /**
    * @return 全名
    */
        public function getFullName() {
        $tempResult = $this->sdkStdResult["fullName"];
        return $tempResult;
    }
    
    /**
     * 设置全名     
     * @param String $fullName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFullName( $fullName) {
        $this->sdkStdResult["fullName"] = $fullName;
    }
    
        
        /**
    * @return 手机
    */
        public function getMobilePhone() {
        $tempResult = $this->sdkStdResult["mobilePhone"];
        return $tempResult;
    }
    
    /**
     * 设置手机     
     * @param String $mobilePhone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMobilePhone( $mobilePhone) {
        $this->sdkStdResult["mobilePhone"] = $mobilePhone;
    }
    
        
        /**
    * @return 凭证(如果货品状态为"已发货"，买家在退款协议中选择了"没有收到货"，系统强制要求卖家上传凭证)
    */
        public function getVouchers() {
        $tempResult = $this->sdkStdResult["vouchers"];
        return $tempResult;
    }
    
    /**
     * 设置凭证(如果货品状态为"已发货"，买家在退款协议中选择了"没有收到货"，系统强制要求卖家上传凭证)     
     * @param array include @see String[] $vouchers     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setVouchers( $vouchers) {
        $this->sdkStdResult["vouchers"] = $vouchers;
    }
    
        
        /**
    * @return 说明
    */
        public function getDiscription() {
        $tempResult = $this->sdkStdResult["discription"];
        return $tempResult;
    }
    
    /**
     * 设置说明     
     * @param String $discription     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscription( $discription) {
        $this->sdkStdResult["discription"] = $discription;
    }
    
        
        /**
    * @return 操作者角色(不可为空)
    */
        public function getOperatorRole() {
        $tempResult = $this->sdkStdResult["operatorRole"];
        return $tempResult;
    }
    
    /**
     * 设置操作者角色(不可为空)     
     * @param String $operatorRole     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOperatorRole( $operatorRole) {
        $this->sdkStdResult["operatorRole"] = $operatorRole;
    }
    
        
        /**
    * @return 纠纷类型
    */
        public function getDisputeType() {
        $tempResult = $this->sdkStdResult["disputeType"];
        return $tempResult;
    }
    
    /**
     * 设置纠纷类型     
     * @param Integer $disputeType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDisputeType( $disputeType) {
        $this->sdkStdResult["disputeType"] = $disputeType;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>