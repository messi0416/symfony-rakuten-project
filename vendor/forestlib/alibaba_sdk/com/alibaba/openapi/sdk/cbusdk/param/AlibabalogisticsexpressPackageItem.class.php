<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsexpressPackageItem extends SDKDomain {

       	
    private $weight;
    
        /**
    * @return 重量，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826
    */
        public function getWeight() {
        return $this->weight;
    }
    
    /**
     * 设置重量，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826     
     * @param BigDecimal $weight     
     * 参数示例：<pre>2.34</pre>     
     * 此参数必填     */
    public function setWeight( $weight) {
        $this->weight = $weight;
    }
    
        	
    private $height;
    
        /**
    * @return 高，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826
    */
        public function getHeight() {
        return $this->height;
    }
    
    /**
     * 设置高，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826     
     * @param BigDecimal $height     
     * 参数示例：<pre>2.34</pre>     
     * 此参数必填     */
    public function setHeight( $height) {
        $this->height = $height;
    }
    
        	
    private $packageType;
    
        /**
    * @return 包装类型，box：纸箱
    */
        public function getPackageType() {
        return $this->packageType;
    }
    
    /**
     * 设置包装类型，box：纸箱     
     * @param String $packageType     
     * 参数示例：<pre>box</pre>     
     * 此参数必填     */
    public function setPackageType( $packageType) {
        $this->packageType = $packageType;
    }
    
        	
    private $width;
    
        /**
    * @return 宽，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826
    */
        public function getWidth() {
        return $this->width;
    }
    
    /**
     * 设置宽，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826     
     * @param BigDecimal $width     
     * 参数示例：<pre>2.34</pre>     
     * 此参数必填     */
    public function setWidth( $width) {
        $this->width = $width;
    }
    
        	
    private $length;
    
        /**
    * @return 长，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826
    */
        public function getLength() {
        return $this->length;
    }
    
    /**
     * 设置长，限制条件参照：http://service.alibaba.com/supplier/faq_detail/13840826.htm?id=13840826     
     * @param BigDecimal $length     
     * 参数示例：<pre>2.34</pre>     
     * 此参数必填     */
    public function setLength( $length) {
        $this->length = $length;
    }
    
        	
    private $quantity;
    
        /**
    * @return 数量
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置数量     
     * @param Integer $quantity     
     * 参数示例：<pre>1</pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "weight", $this->stdResult )) {
    				$this->weight = $this->stdResult->{"weight"};
    			}
    			    		    				    			    			if (array_key_exists ( "height", $this->stdResult )) {
    				$this->height = $this->stdResult->{"height"};
    			}
    			    		    				    			    			if (array_key_exists ( "packageType", $this->stdResult )) {
    				$this->packageType = $this->stdResult->{"packageType"};
    			}
    			    		    				    			    			if (array_key_exists ( "width", $this->stdResult )) {
    				$this->width = $this->stdResult->{"width"};
    			}
    			    		    				    			    			if (array_key_exists ( "length", $this->stdResult )) {
    				$this->length = $this->stdResult->{"length"};
    			}
    			    		    				    			    			if (array_key_exists ( "quantity", $this->stdResult )) {
    				$this->quantity = $this->stdResult->{"quantity"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "weight", $this->arrayResult )) {
    			$this->weight = $arrayResult['weight'];
    			}
    		    	    			    		    			if (array_key_exists ( "height", $this->arrayResult )) {
    			$this->height = $arrayResult['height'];
    			}
    		    	    			    		    			if (array_key_exists ( "packageType", $this->arrayResult )) {
    			$this->packageType = $arrayResult['packageType'];
    			}
    		    	    			    		    			if (array_key_exists ( "width", $this->arrayResult )) {
    			$this->width = $arrayResult['width'];
    			}
    		    	    			    		    			if (array_key_exists ( "length", $this->arrayResult )) {
    			$this->length = $arrayResult['length'];
    			}
    		    	    			    		    			if (array_key_exists ( "quantity", $this->arrayResult )) {
    			$this->quantity = $arrayResult['quantity'];
    			}
    		    	    		}
 
   
}
?>