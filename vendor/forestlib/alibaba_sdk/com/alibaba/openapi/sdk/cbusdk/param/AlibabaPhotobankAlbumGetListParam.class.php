<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPhotobankAlbumGetListParam {

        
        /**
    * @return 如传入相册ID，1688返回当前相册信息，国际站返回当前相册及下级信息。如不传此参数，1688返回所有相册信息列表，国际站返回所有一级相册列表
    */
        public function getId() {
        $tempResult = $this->sdkStdResult["id"];
        return $tempResult;
    }
    
    /**
     * 设置如传入相册ID，1688返回当前相册信息，国际站返回当前相册及下级信息。如不传此参数，1688返回所有相册信息列表，国际站返回所有一级相册列表     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->sdkStdResult["id"] = $id;
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