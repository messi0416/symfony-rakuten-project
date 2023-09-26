<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPhotobankAlbumAddParam {

        
        /**
    * @return 相册名称。最长30个中文字符
    */
        public function getName() {
        $tempResult = $this->sdkStdResult["name"];
        return $tempResult;
    }
    
    /**
     * 设置相册名称。最长30个中文字符     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->sdkStdResult["name"] = $name;
    }
    
        
        /**
    * @return 相册描述。最长2000个中文字符，国际站无需处理此字段
    */
        public function getDescription() {
        $tempResult = $this->sdkStdResult["description"];
        return $tempResult;
    }
    
    /**
     * 设置相册描述。最长2000个中文字符，国际站无需处理此字段     
     * @param String $description     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDescription( $description) {
        $this->sdkStdResult["description"] = $description;
    }
    
        
        /**
    * @return 相册访问权限。取值范围:0-不公开；1-公开；2-密码访问。只有开通旺铺的会员可以设置相册访问权限为“1-公开”和“2-密码访问”，未开通旺铺的会员相册访问权限限制为“0-不公开”，国际站无需处理此字段
    */
        public function getAuthority() {
        $tempResult = $this->sdkStdResult["authority"];
        return $tempResult;
    }
    
    /**
     * 设置相册访问权限。取值范围:0-不公开；1-公开；2-密码访问。只有开通旺铺的会员可以设置相册访问权限为“1-公开”和“2-密码访问”，未开通旺铺的会员相册访问权限限制为“0-不公开”，国际站无需处理此字段     
     * @param Integer $authority     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAuthority( $authority) {
        $this->sdkStdResult["authority"] = $authority;
    }
    
        
        /**
    * @return 相册访问密码。4-16位非中文字符。当authority为2-密码访问时必填，国际站无需处理此字段
    */
        public function getPassword() {
        $tempResult = $this->sdkStdResult["password"];
        return $tempResult;
    }
    
    /**
     * 设置相册访问密码。4-16位非中文字符。当authority为2-密码访问时必填，国际站无需处理此字段     
     * @param String $password     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPassword( $password) {
        $this->sdkStdResult["password"] = $password;
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