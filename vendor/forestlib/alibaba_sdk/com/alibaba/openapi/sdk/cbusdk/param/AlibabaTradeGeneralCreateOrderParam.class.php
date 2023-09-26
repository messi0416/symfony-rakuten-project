<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizCargoGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizInvoiceGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizOtherInfoGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizReceiveAddressGroup.class.php');

class AlibabaTradeGeneralCreateOrderParam {

        
        /**
    * @return 商品信息列表。JSON串，其中：offerId，quantity（数量），specId（sku offer对应的specId），unitPrice（单价）这几个字段必须有值
    */
        public function getCargoGroups() {
        $tempResult = $this->sdkStdResult["cargoGroups"];
        return $tempResult;
    }
    
    /**
     * 设置商品信息列表。JSON串，其中：offerId，quantity（数量），specId（sku offer对应的specId），unitPrice（单价）这几个字段必须有值     
     * @param array include @see AlibabaopenplatformtradeBizCargoGroup[] $cargoGroups     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCargoGroups(AlibabaopenplatformtradeBizCargoGroup $cargoGroups) {
        $this->sdkStdResult["cargoGroups"] = $cargoGroups;
    }
    
        
        /**
    * @return 发票信息，若没有可不填。
    */
        public function getInvoiceGroup() {
        $tempResult = $this->sdkStdResult["invoiceGroup"];
        return $tempResult;
    }
    
    /**
     * 设置发票信息，若没有可不填。     
     * @param AlibabaopenplatformtradeBizInvoiceGroup $invoiceGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInvoiceGroup(AlibabaopenplatformtradeBizInvoiceGroup $invoiceGroup) {
        $this->sdkStdResult["invoiceGroup"] = $invoiceGroup;
    }
    
        
        /**
    * @return 其它信息，针对订单级别。JSON串格式，重要字段：message（买家留言），totalAmount（必填字段，总金额= 货品总金额 + 运费，单位: 元），mixAmount（混批金额，必填），mixNumber（混批数量），sumCarriage（总运费，单位元），filledCarriage（用户填写的运费 单位:元），promotionId(优惠id，如果有订单级别优惠，必须传此值才有效)，additionalFee（附加费,单位元）
    */
        public function getOtherInfoGroup() {
        $tempResult = $this->sdkStdResult["otherInfoGroup"];
        return $tempResult;
    }
    
    /**
     * 设置其它信息，针对订单级别。JSON串格式，重要字段：message（买家留言），totalAmount（必填字段，总金额= 货品总金额 + 运费，单位: 元），mixAmount（混批金额，必填），mixNumber（混批数量），sumCarriage（总运费，单位元），filledCarriage（用户填写的运费 单位:元），promotionId(优惠id，如果有订单级别优惠，必须传此值才有效)，additionalFee（附加费,单位元）     
     * @param AlibabaopenplatformtradeBizOtherInfoGroup $otherInfoGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOtherInfoGroup(AlibabaopenplatformtradeBizOtherInfoGroup $otherInfoGroup) {
        $this->sdkStdResult["otherInfoGroup"] = $otherInfoGroup;
    }
    
        
        /**
    * @return 收货地址。JSON串，主要字段：addressId（用户在阿里巴巴保存的地址的id。若能提供，其他可以不填。若不能提供，请设为-1），fullName（收货人姓名），areaCode，cityCode，provinceCode（县/区，市，省份编码。参考”行政区划代码“），address（街道地址），mobile（手机），phone（电话），postCode（邮编）
    */
        public function getReceiveAddressGroup() {
        $tempResult = $this->sdkStdResult["receiveAddressGroup"];
        return $tempResult;
    }
    
    /**
     * 设置收货地址。JSON串，主要字段：addressId（用户在阿里巴巴保存的地址的id。若能提供，其他可以不填。若不能提供，请设为-1），fullName（收货人姓名），areaCode，cityCode，provinceCode（县/区，市，省份编码。参考”行政区划代码“），address（街道地址），mobile（手机），phone（电话），postCode（邮编）     
     * @param AlibabaopenplatformtradeBizReceiveAddressGroup $receiveAddressGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setReceiveAddressGroup(AlibabaopenplatformtradeBizReceiveAddressGroup $receiveAddressGroup) {
        $this->sdkStdResult["receiveAddressGroup"] = $receiveAddressGroup;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>