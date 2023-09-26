<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressExpressWTDSolutionGetListParam.class.php');

class AlibabaLogisticsInternationalexpressWtdSolutionGetListParam {

        
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
    
        
        /**
    * @return 请求参数
    */
        public function getQueryParam() {
        $tempResult = $this->sdkStdResult["queryParam"];
        return $tempResult;
    }
    
    /**
     * 设置请求参数     
     * @param AlibabalogisticsexpressExpressWTDSolutionGetListParam $queryParam     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQueryParam(AlibabalogisticsexpressExpressWTDSolutionGetListParam $queryParam) {
        $this->sdkStdResult["queryParam"] = $queryParam;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>