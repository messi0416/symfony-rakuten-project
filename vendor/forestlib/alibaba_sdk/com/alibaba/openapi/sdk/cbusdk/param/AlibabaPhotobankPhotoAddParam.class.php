<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPhotobankPhotoAddParam {

        
        /**
    * @return 相册ID，1688必须传此参数，国际站可不传
    */
        public function getAlbumID() {
        $tempResult = $this->sdkStdResult["albumID"];
        return $tempResult;
    }
    
    /**
     * 设置相册ID，1688必须传此参数，国际站可不传     
     * @param Long $albumID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAlbumID( $albumID) {
        $this->sdkStdResult["albumID"] = $albumID;
    }
    
        
        /**
    * @return 图片名称。最长30个中文字符
    */
        public function getName() {
        $tempResult = $this->sdkStdResult["name"];
        return $tempResult;
    }
    
    /**
     * 设置图片名称。最长30个中文字符     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->sdkStdResult["name"] = $name;
    }
    
        
        /**
    * @return 图片描述。最长2000个中文字符
    */
        public function getDescription() {
        $tempResult = $this->sdkStdResult["description"];
        return $tempResult;
    }
    
    /**
     * 设置图片描述。最长2000个中文字符     
     * @param String $description     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDescription( $description) {
        $this->sdkStdResult["description"] = $description;
    }
    
        
        /**
    * @return 是否打上默认水印，国际站无需处理此字段
    */
        public function getDrawTxt() {
        $tempResult = $this->sdkStdResult["drawTxt"];
        return $tempResult;
    }
    
    /**
     * 设置是否打上默认水印，国际站无需处理此字段     
     * @param Boolean $drawTxt     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDrawTxt( $drawTxt) {
        $this->sdkStdResult["drawTxt"] = $drawTxt;
    }
    
        
        /**
    * @return 图片的二进制数据，向服务端提交文件即可 (二进制文件数组 PHP 的话，用 base64_encode 转换 ，JAVA 是 通过 IOUtils.toByteArray 转换)
    */
        public function getImageBytes() {
        $tempResult = $this->sdkStdResult["imageBytes"];
        return $tempResult;
    }
    
    /**
     * 设置图片的二进制数据，向服务端提交文件即可 (二进制文件数组 PHP 的话，用 base64_encode 转换 ，JAVA 是 通过 IOUtils.toByteArray 转换)     
     * @param array include @see Byte[] $imageBytes     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setImageBytes( $imageBytes) {
        $this->sdkStdResult["imageBytes"] = $imageBytes;
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