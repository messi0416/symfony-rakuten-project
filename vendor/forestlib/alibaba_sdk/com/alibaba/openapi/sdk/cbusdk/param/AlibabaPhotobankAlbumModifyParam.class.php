<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaphotobankphotoAlbumDomain.class.php');

class AlibabaPhotobankAlbumModifyParam {

        
        /**
    * @return 图片相册信息
    */
        public function getAlbumInfo() {
        $tempResult = $this->sdkStdResult["albumInfo"];
        return $tempResult;
    }
    
    /**
     * 设置图片相册信息     
     * @param AlibabaphotobankphotoAlbumDomain $albumInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAlbumInfo(AlibabaphotobankphotoAlbumDomain $albumInfo) {
        $this->sdkStdResult["albumInfo"] = $albumInfo;
    }
    
        
        /**
    * @return 图片相册密码，可为空。如果不为空，则修改相册权限
    */
        public function getPassword() {
        $tempResult = $this->sdkStdResult["password"];
        return $tempResult;
    }
    
    /**
     * 设置图片相册密码，可为空。如果不为空，则修改相册权限     
     * @param String $password     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPassword( $password) {
        $this->sdkStdResult["password"] = $password;
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