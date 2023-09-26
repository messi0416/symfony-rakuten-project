<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductSKUAttrInfo extends SDKDomain {

       	
    private $attributeID;
    
        /**
    * @return sku属性ID
    */
        public function getAttributeID() {
        return $this->attributeID;
    }
    
    /**
     * 设置sku属性ID     
     * @param Long $attributeID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttributeID( $attributeID) {
        $this->attributeID = $attributeID;
    }
    
        	
    private $attValueID;
    
        /**
    * @return sku值ID，1688不用关注
    */
        public function getAttValueID() {
        return $this->attValueID;
    }
    
    /**
     * 设置sku值ID，1688不用关注     
     * @param Long $attValueID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttValueID( $attValueID) {
        $this->attValueID = $attValueID;
    }
    
        	
    private $attributeValue;
    
        /**
    * @return sku值内容，国际站不用关注
    */
        public function getAttributeValue() {
        return $this->attributeValue;
    }
    
    /**
     * 设置sku值内容，国际站不用关注     
     * @param String $attributeValue     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttributeValue( $attributeValue) {
        $this->attributeValue = $attributeValue;
    }
    
        	
    private $customValueName;
    
        /**
    * @return 自定义属性值名称，1688无需关注
    */
        public function getCustomValueName() {
        return $this->customValueName;
    }
    
    /**
     * 设置自定义属性值名称，1688无需关注     
     * @param String $customValueName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCustomValueName( $customValueName) {
        $this->customValueName = $customValueName;
    }
    
        	
    private $skuImageUrl;
    
        /**
    * @return sku图片
    */
        public function getSkuImageUrl() {
        return $this->skuImageUrl;
    }
    
    /**
     * 设置sku图片     
     * @param String $skuImageUrl     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuImageUrl( $skuImageUrl) {
        $this->skuImageUrl = $skuImageUrl;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "attributeID", $this->stdResult )) {
    				$this->attributeID = $this->stdResult->{"attributeID"};
    			}
    			    		    				    			    			if (array_key_exists ( "attValueID", $this->stdResult )) {
    				$this->attValueID = $this->stdResult->{"attValueID"};
    			}
    			    		    				    			    			if (array_key_exists ( "attributeValue", $this->stdResult )) {
    				$this->attributeValue = $this->stdResult->{"attributeValue"};
    			}
    			    		    				    			    			if (array_key_exists ( "customValueName", $this->stdResult )) {
    				$this->customValueName = $this->stdResult->{"customValueName"};
    			}
    			    		    				    			    			if (array_key_exists ( "skuImageUrl", $this->stdResult )) {
    				$this->skuImageUrl = $this->stdResult->{"skuImageUrl"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "attributeID", $this->arrayResult )) {
    			$this->attributeID = $arrayResult['attributeID'];
    			}
    		    	    			    		    			if (array_key_exists ( "attValueID", $this->arrayResult )) {
    			$this->attValueID = $arrayResult['attValueID'];
    			}
    		    	    			    		    			if (array_key_exists ( "attributeValue", $this->arrayResult )) {
    			$this->attributeValue = $arrayResult['attributeValue'];
    			}
    		    	    			    		    			if (array_key_exists ( "customValueName", $this->arrayResult )) {
    			$this->customValueName = $arrayResult['customValueName'];
    			}
    		    	    			    		    			if (array_key_exists ( "skuImageUrl", $this->arrayResult )) {
    			$this->skuImageUrl = $arrayResult['skuImageUrl'];
    			}
    		    	    		}
 
   
}
?>