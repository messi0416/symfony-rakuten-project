<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductTbNicknameToUserIdParam {

        
        /**
    * @return 淘宝登录名
    */
        public function getNickname() {
        $tempResult = $this->sdkStdResult["nickname"];
        return $tempResult;
    }
    
    /**
     * 设置淘宝登录名     
     * @param String $nickname     
     * 参数示例：<pre>yqq002</pre>     
     * 此参数必填     */
    public function setNickname( $nickname) {
        $this->sdkStdResult["nickname"] = $nickname;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>