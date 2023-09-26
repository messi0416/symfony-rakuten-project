<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductAttribute.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductImageInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductSKUInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductSaleInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductShippingInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductInternationalTradeInfo.class.php');

class AlibabaProductAddParam {

        
        /**
    * @return 商品类型，在线批发商品(wholesale)或者询盘商品(sourcing)，1688网站缺省为wholesale
    */
        public function getProductType() {
        $tempResult = $this->sdkStdResult["productType"];
        return $tempResult;
    }
    
    /**
     * 设置商品类型，在线批发商品(wholesale)或者询盘商品(sourcing)，1688网站缺省为wholesale     
     * @param String $productType     
     * 参数示例：<pre>wholesale</pre>     
     * 此参数必填     */
    public function setProductType( $productType) {
        $this->sdkStdResult["productType"] = $productType;
    }
    
        
        /**
    * @return 类目ID，由相应类目API获取
    */
        public function getCategoryID() {
        $tempResult = $this->sdkStdResult["categoryID"];
        return $tempResult;
    }
    
    /**
     * 设置类目ID，由相应类目API获取     
     * @param Long $categoryID     
     * 参数示例：<pre>123456</pre>     
     * 此参数必填     */
    public function setCategoryID( $categoryID) {
        $this->sdkStdResult["categoryID"] = $categoryID;
    }
    
        
        /**
    * @return 商品属性和属性值
    */
        public function getAttributes() {
        $tempResult = $this->sdkStdResult["attributes"];
        return $tempResult;
    }
    
    /**
     * 设置商品属性和属性值     
     * @param array include @see AlibabaproductProductAttribute[] $attributes     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttributes(AlibabaproductProductAttribute $attributes) {
        $this->sdkStdResult["attributes"] = $attributes;
    }
    
        
        /**
    * @return 分组ID，确定商品所属分组。1688可传入多个分组ID，国际站同一个商品只能属于一个分组，因此默认只取第一个
    */
        public function getGroupID() {
        $tempResult = $this->sdkStdResult["groupID"];
        return $tempResult;
    }
    
    /**
     * 设置分组ID，确定商品所属分组。1688可传入多个分组ID，国际站同一个商品只能属于一个分组，因此默认只取第一个     
     * @param array include @see Long[] $groupID     
     * 参数示例：<pre>123456</pre>     
     * 此参数必填     */
    public function setGroupID( $groupID) {
        $this->sdkStdResult["groupID"] = $groupID;
    }
    
        
        /**
    * @return 商品标题，最多128个字符。标题内容将被系统切分作为关键字，因此API将不再单独提供关键字字段。
    */
        public function getSubject() {
        $tempResult = $this->sdkStdResult["subject"];
        return $tempResult;
    }
    
    /**
     * 设置商品标题，最多128个字符。标题内容将被系统切分作为关键字，因此API将不再单独提供关键字字段。     
     * @param String $subject     
     * 参数示例：<pre>新款女装 立领套头毛衫</pre>     
     * 此参数必填     */
    public function setSubject( $subject) {
        $this->sdkStdResult["subject"] = $subject;
    }
    
        
        /**
    * @return 商品详情描述，可包含图片中心的图片URL
    */
        public function getDescription() {
        $tempResult = $this->sdkStdResult["description"];
        return $tempResult;
    }
    
    /**
     * 设置商品详情描述，可包含图片中心的图片URL     
     * @param String $description     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDescription( $description) {
        $this->sdkStdResult["description"] = $description;
    }
    
        
        /**
    * @return 语种，参见FAQ 语种枚举值，1688网站默认传入CHINESE
    */
        public function getLanguage() {
        $tempResult = $this->sdkStdResult["language"];
        return $tempResult;
    }
    
    /**
     * 设置语种，参见FAQ 语种枚举值，1688网站默认传入CHINESE     
     * @param String $language     
     * 参数示例：<pre>ENGLISH</pre>     
     * 此参数必填     */
    public function setLanguage( $language) {
        $this->sdkStdResult["language"] = $language;
    }
    
        
        /**
    * @return 信息有效期，按天计算，国际站可不填
    */
        public function getPeriodOfValidity() {
        $tempResult = $this->sdkStdResult["periodOfValidity"];
        return $tempResult;
    }
    
    /**
     * 设置信息有效期，按天计算，国际站可不填     
     * @param Integer $periodOfValidity     
     * 参数示例：<pre>200</pre>     
     * 此参数必填     */
    public function setPeriodOfValidity( $periodOfValidity) {
        $this->sdkStdResult["periodOfValidity"] = $periodOfValidity;
    }
    
        
        /**
    * @return 业务类型。1：商品，2：加工，3：代理，4：合作，5：商务服务；不传入默认按照商品发布；国际站按默认商品。
    */
        public function getBizType() {
        $tempResult = $this->sdkStdResult["bizType"];
        return $tempResult;
    }
    
    /**
     * 设置业务类型。1：商品，2：加工，3：代理，4：合作，5：商务服务；不传入默认按照商品发布；国际站按默认商品。     
     * @param Integer $bizType     
     * 参数示例：<pre>1</pre>     
     * 此参数必填     */
    public function setBizType( $bizType) {
        $this->sdkStdResult["bizType"] = $bizType;
    }
    
        
        /**
    * @return 是否图片私密信息，国际站此字段无效
    */
        public function getPictureAuth() {
        $tempResult = $this->sdkStdResult["pictureAuth"];
        return $tempResult;
    }
    
    /**
     * 设置是否图片私密信息，国际站此字段无效     
     * @param Boolean $pictureAuth     
     * 参数示例：<pre>true</pre>     
     * 此参数必填     */
    public function setPictureAuth( $pictureAuth) {
        $this->sdkStdResult["pictureAuth"] = $pictureAuth;
    }
    
        
        /**
    * @return 商品主图
    */
        public function getImage() {
        $tempResult = $this->sdkStdResult["image"];
        return $tempResult;
    }
    
    /**
     * 设置商品主图     
     * @param AlibabaproductProductImageInfo $image     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setImage(AlibabaproductProductImageInfo $image) {
        $this->sdkStdResult["image"] = $image;
    }
    
        
        /**
    * @return SKU信息，这里可传入多组信息
    */
        public function getSkuInfos() {
        $tempResult = $this->sdkStdResult["skuInfos"];
        return $tempResult;
    }
    
    /**
     * 设置SKU信息，这里可传入多组信息     
     * @param array include @see AlibabaproductProductSKUInfo[] $skuInfos     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuInfos(AlibabaproductProductSKUInfo $skuInfos) {
        $this->sdkStdResult["skuInfos"] = $skuInfos;
    }
    
        
        /**
    * @return 商品销售信息
    */
        public function getSaleInfo() {
        $tempResult = $this->sdkStdResult["saleInfo"];
        return $tempResult;
    }
    
    /**
     * 设置商品销售信息     
     * @param AlibabaproductProductSaleInfo $saleInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSaleInfo(AlibabaproductProductSaleInfo $saleInfo) {
        $this->sdkStdResult["saleInfo"] = $saleInfo;
    }
    
        
        /**
    * @return 商品物流信息
    */
        public function getShippingInfo() {
        $tempResult = $this->sdkStdResult["shippingInfo"];
        return $tempResult;
    }
    
    /**
     * 设置商品物流信息     
     * @param AlibabaproductProductShippingInfo $shippingInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShippingInfo(AlibabaproductProductShippingInfo $shippingInfo) {
        $this->sdkStdResult["shippingInfo"] = $shippingInfo;
    }
    
        
        /**
    * @return 商品国际贸易信息，1688无需处理此字段
    */
        public function getInternationalTradeInfo() {
        $tempResult = $this->sdkStdResult["internationalTradeInfo"];
        return $tempResult;
    }
    
    /**
     * 设置商品国际贸易信息，1688无需处理此字段     
     * @param AlibabaproductProductInternationalTradeInfo $internationalTradeInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInternationalTradeInfo(AlibabaproductProductInternationalTradeInfo $internationalTradeInfo) {
        $this->sdkStdResult["internationalTradeInfo"] = $internationalTradeInfo;
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