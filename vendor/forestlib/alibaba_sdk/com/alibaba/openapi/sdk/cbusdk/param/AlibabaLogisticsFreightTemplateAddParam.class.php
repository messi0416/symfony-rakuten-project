<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaLogisticsFreightTemplateAddParam {

        
        /**
    * @return 运费模板类型，可选值：freeshipping(全球免邮)，not(全球不发货)
    */
        public function getTemplateType() {
        $tempResult = $this->sdkStdResult["templateType"];
        return $tempResult;
    }
    
    /**
     * 设置运费模板类型，可选值：freeshipping(全球免邮)，not(全球不发货)     
     * @param String $templateType     
     * 参数示例：<pre>freeshipping</pre>     
     * 此参数必填     */
    public function setTemplateType( $templateType) {
        $this->sdkStdResult["templateType"] = $templateType;
    }
    
        
        /**
    * @return 运费模板发货地，可选值：US（美国）,UK(英国),DE(德国),ES(西班牙),CN(中国)
    */
        public function getDispatchLocations() {
        $tempResult = $this->sdkStdResult["dispatchLocations"];
        return $tempResult;
    }
    
    /**
     * 设置运费模板发货地，可选值：US（美国）,UK(英国),DE(德国),ES(西班牙),CN(中国)     
     * @param array include @see String[] $dispatchLocations     
     * 参数示例：<pre>"US"
"UK"</pre>     
     * 此参数必填     */
    public function setDispatchLocations( $dispatchLocations) {
        $this->sdkStdResult["dispatchLocations"] = $dispatchLocations;
    }
    
        
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
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>