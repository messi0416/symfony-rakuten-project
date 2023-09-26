<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductAttribute.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductImageInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductSKUInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductSaleInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductShippingInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductInternationalTradeInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductExtendInfo.class.php');

class AlibabaproductProductInfo extends SDKDomain {

       	
    private $productID;
    
        /**
    * @return 商品ID
    */
        public function getProductID() {
        return $this->productID;
    }
    
    /**
     * 设置商品ID     
     * @param Long $productID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductID( $productID) {
        $this->productID = $productID;
    }
    
        	
    private $productType;
    
        /**
    * @return 商品类型，在线批发商品(wholesale)或者询盘商品(sourcing)，1688网站缺省为wholesale
    */
        public function getProductType() {
        return $this->productType;
    }
    
    /**
     * 设置商品类型，在线批发商品(wholesale)或者询盘商品(sourcing)，1688网站缺省为wholesale     
     * @param String $productType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductType( $productType) {
        $this->productType = $productType;
    }
    
        	
    private $categoryID;
    
        /**
    * @return 类目ID，标识商品所属类目
    */
        public function getCategoryID() {
        return $this->categoryID;
    }
    
    /**
     * 设置类目ID，标识商品所属类目     
     * @param Long $categoryID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCategoryID( $categoryID) {
        $this->categoryID = $categoryID;
    }
    
        	
    private $attributes;
    
        /**
    * @return 商品属性和属性值
    */
        public function getAttributes() {
        return $this->attributes;
    }
    
    /**
     * 设置商品属性和属性值     
     * @param array include @see AlibabaproductProductAttribute[] $attributes     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttributes(AlibabaproductProductAttribute $attributes) {
        $this->attributes = $attributes;
    }
    
        	
    private $groupID;
    
        /**
    * @return 分组ID，确定商品所属分组。1688可传入多个分组ID，国际站同一个商品只能属于一个分组，因此默认只取第一个
    */
        public function getGroupID() {
        return $this->groupID;
    }
    
    /**
     * 设置分组ID，确定商品所属分组。1688可传入多个分组ID，国际站同一个商品只能属于一个分组，因此默认只取第一个     
     * @param array include @see Long[] $groupID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGroupID( $groupID) {
        $this->groupID = $groupID;
    }
    
        	
    private $status;
    
        /**
    * @return 商品状态。auditing：审核中；online：已上网；FailAudited：审核未通过；outdated：已过期；member delete(d)：用户删除；delete：审核删除；published 已上架。此状态为系统内部控制，外部无法修改。
    */
        public function getStatus() {
        return $this->status;
    }
    
    /**
     * 设置商品状态。auditing：审核中；online：已上网；FailAudited：审核未通过；outdated：已过期；member delete(d)：用户删除；delete：审核删除；published 已上架。此状态为系统内部控制，外部无法修改。     
     * @param String $status     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStatus( $status) {
        $this->status = $status;
    }
    
        	
    private $subject;
    
        /**
    * @return 商品标题，最多128个字符
    */
        public function getSubject() {
        return $this->subject;
    }
    
    /**
     * 设置商品标题，最多128个字符     
     * @param String $subject     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSubject( $subject) {
        $this->subject = $subject;
    }
    
        	
    private $description;
    
        /**
    * @return 商品详情描述，可包含图片中心的图片URL
    */
        public function getDescription() {
        return $this->description;
    }
    
    /**
     * 设置商品详情描述，可包含图片中心的图片URL     
     * @param String $description     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDescription( $description) {
        $this->description = $description;
    }
    
        	
    private $language;
    
        /**
    * @return 语种，参见FAQ 语种枚举值，1688网站默认传入CHINESE
    */
        public function getLanguage() {
        return $this->language;
    }
    
    /**
     * 设置语种，参见FAQ 语种枚举值，1688网站默认传入CHINESE     
     * @param String $language     
     * 参数示例：<pre>ENGLISH</pre>     
     * 此参数必填     */
    public function setLanguage( $language) {
        $this->language = $language;
    }
    
        	
    private $periodOfValidity;
    
        /**
    * @return 信息有效期，按天计算，国际站无此信息
    */
        public function getPeriodOfValidity() {
        return $this->periodOfValidity;
    }
    
    /**
     * 设置信息有效期，按天计算，国际站无此信息     
     * @param Integer $periodOfValidity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPeriodOfValidity( $periodOfValidity) {
        $this->periodOfValidity = $periodOfValidity;
    }
    
        	
    private $bizType;
    
        /**
    * @return 业务类型。1：商品，2：加工，3：代理，4：合作，5：商务服务。国际站按默认商品。
    */
        public function getBizType() {
        return $this->bizType;
    }
    
    /**
     * 设置业务类型。1：商品，2：加工，3：代理，4：合作，5：商务服务。国际站按默认商品。     
     * @param Integer $bizType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBizType( $bizType) {
        $this->bizType = $bizType;
    }
    
        	
    private $pictureAuth;
    
        /**
    * @return 是否图片私密信息，国际站此字段无效
    */
        public function getPictureAuth() {
        return $this->pictureAuth;
    }
    
    /**
     * 设置是否图片私密信息，国际站此字段无效     
     * @param Boolean $pictureAuth     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPictureAuth( $pictureAuth) {
        $this->pictureAuth = $pictureAuth;
    }
    
        	
    private $image;
    
        /**
    * @return 商品主图
    */
        public function getImage() {
        return $this->image;
    }
    
    /**
     * 设置商品主图     
     * @param AlibabaproductProductImageInfo $image     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setImage(AlibabaproductProductImageInfo $image) {
        $this->image = $image;
    }
    
        	
    private $skuInfos;
    
        /**
    * @return sku信息
    */
        public function getSkuInfos() {
        return $this->skuInfos;
    }
    
    /**
     * 设置sku信息     
     * @param array include @see AlibabaproductProductSKUInfo[] $skuInfos     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuInfos(AlibabaproductProductSKUInfo $skuInfos) {
        $this->skuInfos = $skuInfos;
    }
    
        	
    private $saleInfo;
    
        /**
    * @return 商品销售信息
    */
        public function getSaleInfo() {
        return $this->saleInfo;
    }
    
    /**
     * 设置商品销售信息     
     * @param AlibabaproductProductSaleInfo $saleInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSaleInfo(AlibabaproductProductSaleInfo $saleInfo) {
        $this->saleInfo = $saleInfo;
    }
    
        	
    private $shippingInfo;
    
        /**
    * @return 商品物流信息
    */
        public function getShippingInfo() {
        return $this->shippingInfo;
    }
    
    /**
     * 设置商品物流信息     
     * @param AlibabaproductProductShippingInfo $shippingInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShippingInfo(AlibabaproductProductShippingInfo $shippingInfo) {
        $this->shippingInfo = $shippingInfo;
    }
    
        	
    private $internationalTradeInfo;
    
        /**
    * @return 商品国际贸易信息，1688无需处理此字段
    */
        public function getInternationalTradeInfo() {
        return $this->internationalTradeInfo;
    }
    
    /**
     * 设置商品国际贸易信息，1688无需处理此字段     
     * @param AlibabaproductProductInternationalTradeInfo $internationalTradeInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInternationalTradeInfo(AlibabaproductProductInternationalTradeInfo $internationalTradeInfo) {
        $this->internationalTradeInfo = $internationalTradeInfo;
    }
    
        	
    private $extendInfos;
    
        /**
    * @return 商品扩展信息
    */
        public function getExtendInfos() {
        return $this->extendInfos;
    }
    
    /**
     * 设置商品扩展信息     
     * @param array include @see AlibabaproductProductExtendInfo[] $extendInfos     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExtendInfos(AlibabaproductProductExtendInfo $extendInfos) {
        $this->extendInfos = $extendInfos;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "productID", $this->stdResult )) {
    				$this->productID = $this->stdResult->{"productID"};
    			}
    			    		    				    			    			if (array_key_exists ( "productType", $this->stdResult )) {
    				$this->productType = $this->stdResult->{"productType"};
    			}
    			    		    				    			    			if (array_key_exists ( "categoryID", $this->stdResult )) {
    				$this->categoryID = $this->stdResult->{"categoryID"};
    			}
    			    		    				    			    			if (array_key_exists ( "attributes", $this->stdResult )) {
    			$attributesResult=$this->stdResult->{"attributes"};
    				$object = json_decode ( json_encode ( $attributesResult ), true );
					$this->attributes = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductProductAttributeResult=new AlibabaproductProductAttribute();
						$AlibabaproductProductAttributeResult->setArrayResult($arrayobject );
						$this->attributes [$i] = $AlibabaproductProductAttributeResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "groupID", $this->stdResult )) {
    				$this->groupID = $this->stdResult->{"groupID"};
    			}
    			    		    				    			    			if (array_key_exists ( "status", $this->stdResult )) {
    				$this->status = $this->stdResult->{"status"};
    			}
    			    		    				    			    			if (array_key_exists ( "subject", $this->stdResult )) {
    				$this->subject = $this->stdResult->{"subject"};
    			}
    			    		    				    			    			if (array_key_exists ( "description", $this->stdResult )) {
    				$this->description = $this->stdResult->{"description"};
    			}
    			    		    				    			    			if (array_key_exists ( "language", $this->stdResult )) {
    				$this->language = $this->stdResult->{"language"};
    			}
    			    		    				    			    			if (array_key_exists ( "periodOfValidity", $this->stdResult )) {
    				$this->periodOfValidity = $this->stdResult->{"periodOfValidity"};
    			}
    			    		    				    			    			if (array_key_exists ( "bizType", $this->stdResult )) {
    				$this->bizType = $this->stdResult->{"bizType"};
    			}
    			    		    				    			    			if (array_key_exists ( "pictureAuth", $this->stdResult )) {
    				$this->pictureAuth = $this->stdResult->{"pictureAuth"};
    			}
    			    		    				    			    			if (array_key_exists ( "image", $this->stdResult )) {
    				$imageResult=$this->stdResult->{"image"};
    				$this->image = new AlibabaproductProductImageInfo();
    				$this->image->setStdResult ( $imageResult);
    			}
    			    		    				    			    			if (array_key_exists ( "skuInfos", $this->stdResult )) {
    			$skuInfosResult=$this->stdResult->{"skuInfos"};
    				$object = json_decode ( json_encode ( $skuInfosResult ), true );
					$this->skuInfos = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductProductSKUInfoResult=new AlibabaproductProductSKUInfo();
						$AlibabaproductProductSKUInfoResult->setArrayResult($arrayobject );
						$this->skuInfos [$i] = $AlibabaproductProductSKUInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "saleInfo", $this->stdResult )) {
    				$saleInfoResult=$this->stdResult->{"saleInfo"};
    				$this->saleInfo = new AlibabaproductProductSaleInfo();
    				$this->saleInfo->setStdResult ( $saleInfoResult);
    			}
    			    		    				    			    			if (array_key_exists ( "shippingInfo", $this->stdResult )) {
    				$shippingInfoResult=$this->stdResult->{"shippingInfo"};
    				$this->shippingInfo = new AlibabaproductProductShippingInfo();
    				$this->shippingInfo->setStdResult ( $shippingInfoResult);
    			}
    			    		    				    			    			if (array_key_exists ( "internationalTradeInfo", $this->stdResult )) {
    				$internationalTradeInfoResult=$this->stdResult->{"internationalTradeInfo"};
    				$this->internationalTradeInfo = new AlibabaproductProductInternationalTradeInfo();
    				$this->internationalTradeInfo->setStdResult ( $internationalTradeInfoResult);
    			}
    			    		    				    			    			if (array_key_exists ( "extendInfos", $this->stdResult )) {
    			$extendInfosResult=$this->stdResult->{"extendInfos"};
    				$object = json_decode ( json_encode ( $extendInfosResult ), true );
					$this->extendInfos = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductProductExtendInfoResult=new AlibabaproductProductExtendInfo();
						$AlibabaproductProductExtendInfoResult->setArrayResult($arrayobject );
						$this->extendInfos [$i] = $AlibabaproductProductExtendInfoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "productID", $this->arrayResult )) {
    			$this->productID = $arrayResult['productID'];
    			}
    		    	    			    		    			if (array_key_exists ( "productType", $this->arrayResult )) {
    			$this->productType = $arrayResult['productType'];
    			}
    		    	    			    		    			if (array_key_exists ( "categoryID", $this->arrayResult )) {
    			$this->categoryID = $arrayResult['categoryID'];
    			}
    		    	    			    		    		if (array_key_exists ( "attributes", $this->arrayResult )) {
    		$attributesResult=$arrayResult['attributes'];
    			$this->attributes = AlibabaproductProductAttribute();
    			$this->attributes->$this->setStdResult ( $attributesResult);
    		}
    		    	    			    		    			if (array_key_exists ( "groupID", $this->arrayResult )) {
    			$this->groupID = $arrayResult['groupID'];
    			}
    		    	    			    		    			if (array_key_exists ( "status", $this->arrayResult )) {
    			$this->status = $arrayResult['status'];
    			}
    		    	    			    		    			if (array_key_exists ( "subject", $this->arrayResult )) {
    			$this->subject = $arrayResult['subject'];
    			}
    		    	    			    		    			if (array_key_exists ( "description", $this->arrayResult )) {
    			$this->description = $arrayResult['description'];
    			}
    		    	    			    		    			if (array_key_exists ( "language", $this->arrayResult )) {
    			$this->language = $arrayResult['language'];
    			}
    		    	    			    		    			if (array_key_exists ( "periodOfValidity", $this->arrayResult )) {
    			$this->periodOfValidity = $arrayResult['periodOfValidity'];
    			}
    		    	    			    		    			if (array_key_exists ( "bizType", $this->arrayResult )) {
    			$this->bizType = $arrayResult['bizType'];
    			}
    		    	    			    		    			if (array_key_exists ( "pictureAuth", $this->arrayResult )) {
    			$this->pictureAuth = $arrayResult['pictureAuth'];
    			}
    		    	    			    		    		if (array_key_exists ( "image", $this->arrayResult )) {
    		$imageResult=$arrayResult['image'];
    			    			$this->image = new AlibabaproductProductImageInfo();
    			    			$this->image->$this->setStdResult ( $imageResult);
    		}
    		    	    			    		    		if (array_key_exists ( "skuInfos", $this->arrayResult )) {
    		$skuInfosResult=$arrayResult['skuInfos'];
    			$this->skuInfos = AlibabaproductProductSKUInfo();
    			$this->skuInfos->$this->setStdResult ( $skuInfosResult);
    		}
    		    	    			    		    		if (array_key_exists ( "saleInfo", $this->arrayResult )) {
    		$saleInfoResult=$arrayResult['saleInfo'];
    			    			$this->saleInfo = new AlibabaproductProductSaleInfo();
    			    			$this->saleInfo->$this->setStdResult ( $saleInfoResult);
    		}
    		    	    			    		    		if (array_key_exists ( "shippingInfo", $this->arrayResult )) {
    		$shippingInfoResult=$arrayResult['shippingInfo'];
    			    			$this->shippingInfo = new AlibabaproductProductShippingInfo();
    			    			$this->shippingInfo->$this->setStdResult ( $shippingInfoResult);
    		}
    		    	    			    		    		if (array_key_exists ( "internationalTradeInfo", $this->arrayResult )) {
    		$internationalTradeInfoResult=$arrayResult['internationalTradeInfo'];
    			    			$this->internationalTradeInfo = new AlibabaproductProductInternationalTradeInfo();
    			    			$this->internationalTradeInfo->$this->setStdResult ( $internationalTradeInfoResult);
    		}
    		    	    			    		    		if (array_key_exists ( "extendInfos", $this->arrayResult )) {
    		$extendInfosResult=$arrayResult['extendInfos'];
    			$this->extendInfos = AlibabaproductProductExtendInfo();
    			$this->extendInfos->$this->setStdResult ( $extendInfosResult);
    		}
    		    	    		}
 
   
}
?>