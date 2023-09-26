<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductStock.class.php');

class AlibabaProductModifyStockParam {

        
        /**
    * @return 产品库存变更信息
    */
        public function getProductStockChange() {
        $tempResult = $this->sdkStdResult["productStockChange"];
        return $tempResult;
    }
    
    /**
     * 设置产品库存变更信息     
     * @param array include @see AlibabaproductProductStock[] $productStockChange     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductStockChange(AlibabaproductProductStock $productStockChange) {
        $this->sdkStdResult["productStockChange"] = $productStockChange;
    }
    
        
        /**
    * @return 1688或者alibaba
    */
        public function getWebSite() {
        $tempResult = $this->sdkStdResult["webSite"];
        return $tempResult;
    }
    
    /**
     * 设置1688或者alibaba     
     * @param String $webSite     
     * 参数示例：<pre>1688</pre>     
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