<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizMakeSingleOrderGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeQuotationIdParam.class.php');

class AlibabaTradeQuotationOrderCreateParam {

        
        /**
    * @return 下单详情信息
    */
        public function getMakeSingleOrderGroup() {
        $tempResult = $this->sdkStdResult["makeSingleOrderGroup"];
        return $tempResult;
    }
    
    /**
     * 设置下单详情信息     
     * @param AlibabaopenplatformtradeBizMakeSingleOrderGroup $makeSingleOrderGroup     
     * 参数示例：<pre>{"receiveAddressGroup":{"address":"聚合路699号","areaCode":"330108","fullName":"洪洲阳","mobile":"13817748888","postCode":"311200"}}</pre>     
     * 此参数必填     */
    public function setMakeSingleOrderGroup(AlibabaopenplatformtradeBizMakeSingleOrderGroup $makeSingleOrderGroup) {
        $this->sdkStdResult["makeSingleOrderGroup"] = $makeSingleOrderGroup;
    }
    
        
        /**
    * @return 下单流程类型，普通询报价："buyoffer";分阶段付款："bostep";多种类交易(item种类大于50个)："mulitem";其他方式，在交易下单页面选取："other";
    */
        public function getSubBiz() {
        $tempResult = $this->sdkStdResult["subBiz"];
        return $tempResult;
    }
    
    /**
     * 设置下单流程类型，普通询报价："buyoffer";分阶段付款："bostep";多种类交易(item种类大于50个)："mulitem";其他方式，在交易下单页面选取："other";     
     * @param String $subBiz     
     * 参数示例：<pre>buyoffer</pre>     
     * 此参数必填     */
    public function setSubBiz( $subBiz) {
        $this->sdkStdResult["subBiz"] = $subBiz;
    }
    
        
        /**
    * @return 询报价单参数标志
    */
        public function getQuotationInfo() {
        $tempResult = $this->sdkStdResult["quotationInfo"];
        return $tempResult;
    }
    
    /**
     * 设置询报价单参数标志     
     * @param AlibabaopenplatformtradeQuotationIdParam $quotationInfo     
     * 参数示例：<pre>{"buyerMemberId":"b2b-2248564064","quoteItemIds":[1107742990902],"supplyNoteId":"959751330902"}</pre>     
     * 此参数必填     */
    public function setQuotationInfo(AlibabaopenplatformtradeQuotationIdParam $quotationInfo) {
        $this->sdkStdResult["quotationInfo"] = $quotationInfo;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>