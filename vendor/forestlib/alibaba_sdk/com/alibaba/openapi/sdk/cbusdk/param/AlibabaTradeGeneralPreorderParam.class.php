<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeGoodsInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeReceiveAddress.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradecomKeyValuePair.class.php');

class AlibabaTradeGeneralPreorderParam {

        
        /**
    * @return 购买的货物列表
    */
        public function getGoods() {
        $tempResult = $this->sdkStdResult["goods"];
        return $tempResult;
    }
    
    /**
     * 设置购买的货物列表     
     * @param array include @see AlibabatradeGoodsInfo[] $goods     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGoods(AlibabatradeGoodsInfo $goods) {
        $this->sdkStdResult["goods"] = $goods;
    }
    
        
        /**
    * @return 收货地址，可以填写买家的收货地址ID，或者买家的收货地址信息
    */
        public function getReceiveAddress() {
        $tempResult = $this->sdkStdResult["receiveAddress"];
        return $tempResult;
    }
    
    /**
     * 设置收货地址，可以填写买家的收货地址ID，或者买家的收货地址信息     
     * @param AlibabatradeReceiveAddress $receiveAddress     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setReceiveAddress(AlibabatradeReceiveAddress $receiveAddress) {
        $this->sdkStdResult["receiveAddress"] = $receiveAddress;
    }
    
        
        /**
    * @return 扩展信息
    */
        public function getExtension() {
        $tempResult = $this->sdkStdResult["extension"];
        return $tempResult;
    }
    
    /**
     * 设置扩展信息     
     * @param array include @see AlibabatradecomKeyValuePair[] $extension     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExtension(AlibabatradecomKeyValuePair $extension) {
        $this->sdkStdResult["extension"] = $extension;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>