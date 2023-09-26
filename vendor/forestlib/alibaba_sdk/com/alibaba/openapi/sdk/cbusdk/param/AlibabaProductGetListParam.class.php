<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGetListParam {

        
        /**
    * @return 类目ID，国际站传入-1，返回所有商品列表
    */
        public function getCategoryID() {
        $tempResult = $this->sdkStdResult["categoryID"];
        return $tempResult;
    }
    
    /**
     * 设置类目ID，国际站传入-1，返回所有商品列表     
     * @param Long $categoryID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCategoryID( $categoryID) {
        $this->sdkStdResult["categoryID"] = $categoryID;
    }
    
        
        /**
    * @return 页码。取值范围:大于零的整数;默认值为1，即返回第一页数据。
    */
        public function getPageNo() {
        $tempResult = $this->sdkStdResult["pageNo"];
        return $tempResult;
    }
    
    /**
     * 设置页码。取值范围:大于零的整数;默认值为1，即返回第一页数据。     
     * @param Integer $pageNo     
     * 参数示例：<pre>5</pre>     
     * 此参数必填     */
    public function setPageNo( $pageNo) {
        $this->sdkStdResult["pageNo"] = $pageNo;
    }
    
        
        /**
    * @return 返回列表结果集每页条数。取值范围:大于零的整数;最大值：30;
    */
        public function getPageSize() {
        $tempResult = $this->sdkStdResult["pageSize"];
        return $tempResult;
    }
    
    /**
     * 设置返回列表结果集每页条数。取值范围:大于零的整数;最大值：30;     
     * @param Integer $pageSize     
     * 参数示例：<pre>20</pre>     
     * 此参数必填     */
    public function setPageSize( $pageSize) {
        $this->sdkStdResult["pageSize"] = $pageSize;
    }
    
        
        /**
    * @return 格式:yyyy-MM-dd HH:mm:ss；1、如果传了时间戳参数，则按增量返回，即返回按指定获取条件且对应商品信息的最近更新时间(GMTModified)晚于该时间戳的商品列表信息。2、如果没有传时间戳参数，则取所有的满足条件的商品信息。1688的时间目前只能支持精确到天。
    */
        public function getTimeStamp() {
        $tempResult = $this->sdkStdResult["timeStamp"];
        return $tempResult;
    }
    
    /**
     * 设置格式:yyyy-MM-dd HH:mm:ss；1、如果传了时间戳参数，则按增量返回，即返回按指定获取条件且对应商品信息的最近更新时间(GMTModified)晚于该时间戳的商品列表信息。2、如果没有传时间戳参数，则取所有的满足条件的商品信息。1688的时间目前只能支持精确到天。     
     * @param String $timeStamp     
     * 参数示例：<pre>2015-04-02 00:00:00</pre>     
     * 此参数必填     */
    public function setTimeStamp( $timeStamp) {
        $this->sdkStdResult["timeStamp"] = $timeStamp;
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
    
        
        /**
    * @return 格式:yyyy-MM-dd HH:mm:ss；1、如果传了时间戳参数，则按增量返回，即返回按指定获取条件且对应商品信息的最近更新时间(GMTModified)早于该时间戳的商品列表信息。2、如果没有传时间戳参数，则取所有的满足条件的商品信息。
    */
        public function getEndTimeStamp() {
        $tempResult = $this->sdkStdResult["endTimeStamp"];
        return $tempResult;
    }
    
    /**
     * 设置格式:yyyy-MM-dd HH:mm:ss；1、如果传了时间戳参数，则按增量返回，即返回按指定获取条件且对应商品信息的最近更新时间(GMTModified)早于该时间戳的商品列表信息。2、如果没有传时间戳参数，则取所有的满足条件的商品信息。     
     * @param String $endTimeStamp     
     * 参数示例：<pre>2015-04-02 01:00:00</pre>     
     * 此参数必填     */
    public function setEndTimeStamp( $endTimeStamp) {
        $this->sdkStdResult["endTimeStamp"] = $endTimeStamp;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>