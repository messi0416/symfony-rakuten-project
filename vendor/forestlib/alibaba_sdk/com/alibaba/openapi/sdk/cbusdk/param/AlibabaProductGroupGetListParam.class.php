<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGroupGetListParam {

        
        /**
    * @return 分组ID
    */
        public function getGroupID() {
        $tempResult = $this->sdkStdResult["groupID"];
        return $tempResult;
    }
    
    /**
     * 设置分组ID     
     * @param Long $groupID     
     * 参数示例：<pre>如果传入分组ID，则返回该分组的所有子分组，如传入-1则返回所有一级分组</pre>     
     * 此参数必填     */
    public function setGroupID( $groupID) {
        $this->sdkStdResult["groupID"] = $groupID;
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