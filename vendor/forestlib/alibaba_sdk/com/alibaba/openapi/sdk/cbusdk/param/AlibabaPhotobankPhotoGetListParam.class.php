<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPhotobankPhotoGetListParam {

        
        /**
    * @return 相册ID
    */
        public function getAlbumID() {
        $tempResult = $this->sdkStdResult["albumID"];
        return $tempResult;
    }
    
    /**
     * 设置相册ID     
     * @param Long $albumID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAlbumID( $albumID) {
        $this->sdkStdResult["albumID"] = $albumID;
    }
    
        
        /**
    * @return 页码。取值范围:大于零的整数;默认值为1，即返回第一页数据
    */
        public function getPageNo() {
        $tempResult = $this->sdkStdResult["pageNo"];
        return $tempResult;
    }
    
    /**
     * 设置页码。取值范围:大于零的整数;默认值为1，即返回第一页数据     
     * @param Integer $pageNo     
     * 参数示例：<pre>5</pre>     
     * 此参数必填     */
    public function setPageNo( $pageNo) {
        $this->sdkStdResult["pageNo"] = $pageNo;
    }
    
        
        /**
    * @return 返回列表结果集每页条数。取值范围:大于零的整数;最大值：30
    */
        public function getPageSize() {
        $tempResult = $this->sdkStdResult["pageSize"];
        return $tempResult;
    }
    
    /**
     * 设置返回列表结果集每页条数。取值范围:大于零的整数;最大值：30     
     * @param Integer $pageSize     
     * 参数示例：<pre>20</pre>     
     * 此参数必填     */
    public function setPageSize( $pageSize) {
        $this->sdkStdResult["pageSize"] = $pageSize;
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