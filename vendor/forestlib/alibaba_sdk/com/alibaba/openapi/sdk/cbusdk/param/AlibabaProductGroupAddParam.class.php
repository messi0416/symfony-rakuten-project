<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGroupAddParam {

        
        /**
    * @return 分组名称
    */
        public function getName() {
        $tempResult = $this->sdkStdResult["name"];
        return $tempResult;
    }
    
    /**
     * 设置分组名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->sdkStdResult["name"] = $name;
    }
    
        
        /**
    * @return 上级分组ID，如果建立顶级分组设为-1
    */
        public function getParentID() {
        $tempResult = $this->sdkStdResult["parentID"];
        return $tempResult;
    }
    
    /**
     * 设置上级分组ID，如果建立顶级分组设为-1     
     * @param Long $parentID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setParentID( $parentID) {
        $this->sdkStdResult["parentID"] = $parentID;
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