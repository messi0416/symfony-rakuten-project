<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductProductShippingInfo extends SDKDomain {

       	
    private $freightTemplateID;
    
        /**
    * @return 运费模板ID，1688使用两类特殊模板来标明使用：运费说明、 卖家承担运费的情况。此参数通过调用运费模板相关API获取
    */
        public function getFreightTemplateID() {
        return $this->freightTemplateID;
    }
    
    /**
     * 设置运费模板ID，1688使用两类特殊模板来标明使用：运费说明、 卖家承担运费的情况。此参数通过调用运费模板相关API获取     
     * @param Long $freightTemplateID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFreightTemplateID( $freightTemplateID) {
        $this->freightTemplateID = $freightTemplateID;
    }
    
        	
    private $unitWeight;
    
        /**
    * @return 单位重量
    */
        public function getUnitWeight() {
        return $this->unitWeight;
    }
    
    /**
     * 设置单位重量     
     * @param Double $unitWeight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnitWeight( $unitWeight) {
        $this->unitWeight = $unitWeight;
    }
    
        	
    private $packageSize;
    
        /**
    * @return 尺寸，单位是厘米，长宽高范围是1-9999999。1688无需关注此字段
    */
        public function getPackageSize() {
        return $this->packageSize;
    }
    
    /**
     * 设置尺寸，单位是厘米，长宽高范围是1-9999999。1688无需关注此字段     
     * @param String $packageSize     
     * 参数示例：<pre>10x20x50</pre>     
     * 此参数必填     */
    public function setPackageSize( $packageSize) {
        $this->packageSize = $packageSize;
    }
    
        	
    private $volume;
    
        /**
    * @return 体积，单位是立方厘米，范围是1-9999999，1688无需关注此字段
    */
        public function getVolume() {
        return $this->volume;
    }
    
    /**
     * 设置体积，单位是立方厘米，范围是1-9999999，1688无需关注此字段     
     * @param Integer $volume     
     * 参数示例：<pre>500</pre>     
     * 此参数必填     */
    public function setVolume( $volume) {
        $this->volume = $volume;
    }
    
        	
    private $handlingTime;
    
        /**
    * @return 备货期，单位是天，范围是1-60。1688无需处理此字段
    */
        public function getHandlingTime() {
        return $this->handlingTime;
    }
    
    /**
     * 设置备货期，单位是天，范围是1-60。1688无需处理此字段     
     * @param Integer $handlingTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setHandlingTime( $handlingTime) {
        $this->handlingTime = $handlingTime;
    }
    
        	
    private $sendGoodsAddressId;
    
        /**
    * @return 发货地址ID，国际站无需处理此字段
    */
        public function getSendGoodsAddressId() {
        return $this->sendGoodsAddressId;
    }
    
    /**
     * 设置发货地址ID，国际站无需处理此字段     
     * @param Long $sendGoodsAddressId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSendGoodsAddressId( $sendGoodsAddressId) {
        $this->sendGoodsAddressId = $sendGoodsAddressId;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "freightTemplateID", $this->stdResult )) {
    				$this->freightTemplateID = $this->stdResult->{"freightTemplateID"};
    			}
    			    		    				    			    			if (array_key_exists ( "unitWeight", $this->stdResult )) {
    				$this->unitWeight = $this->stdResult->{"unitWeight"};
    			}
    			    		    				    			    			if (array_key_exists ( "packageSize", $this->stdResult )) {
    				$this->packageSize = $this->stdResult->{"packageSize"};
    			}
    			    		    				    			    			if (array_key_exists ( "volume", $this->stdResult )) {
    				$this->volume = $this->stdResult->{"volume"};
    			}
    			    		    				    			    			if (array_key_exists ( "handlingTime", $this->stdResult )) {
    				$this->handlingTime = $this->stdResult->{"handlingTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "sendGoodsAddressId", $this->stdResult )) {
    				$this->sendGoodsAddressId = $this->stdResult->{"sendGoodsAddressId"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "freightTemplateID", $this->arrayResult )) {
    			$this->freightTemplateID = $arrayResult['freightTemplateID'];
    			}
    		    	    			    		    			if (array_key_exists ( "unitWeight", $this->arrayResult )) {
    			$this->unitWeight = $arrayResult['unitWeight'];
    			}
    		    	    			    		    			if (array_key_exists ( "packageSize", $this->arrayResult )) {
    			$this->packageSize = $arrayResult['packageSize'];
    			}
    		    	    			    		    			if (array_key_exists ( "volume", $this->arrayResult )) {
    			$this->volume = $arrayResult['volume'];
    			}
    		    	    			    		    			if (array_key_exists ( "handlingTime", $this->arrayResult )) {
    			$this->handlingTime = $arrayResult['handlingTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "sendGoodsAddressId", $this->arrayResult )) {
    			$this->sendGoodsAddressId = $arrayResult['sendGoodsAddressId'];
    			}
    		    	    		}
 
   
}
?>