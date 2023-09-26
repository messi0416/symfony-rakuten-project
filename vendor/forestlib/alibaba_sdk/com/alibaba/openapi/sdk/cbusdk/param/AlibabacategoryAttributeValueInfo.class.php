<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabacategoryAttributeValueInfo extends SDKDomain {

       	
    private $attrValueID;
    
        /**
    * @return 属性值id
    */
        public function getAttrValueID() {
        return $this->attrValueID;
    }
    
    /**
     * 设置属性值id     
     * @param Long $attrValueID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttrValueID( $attrValueID) {
        $this->attrValueID = $attrValueID;
    }
    
        	
    private $name;
    
        /**
    * @return 名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $enName;
    
        /**
    * @return 英文名称
    */
        public function getEnName() {
        return $this->enName;
    }
    
    /**
     * 设置英文名称     
     * @param String $enName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEnName( $enName) {
        $this->enName = $enName;
    }
    
        	
    private $childAttrs;
    
        /**
    * @return 该属性值的子属性id
    */
        public function getChildAttrs() {
        return $this->childAttrs;
    }
    
    /**
     * 设置该属性值的子属性id     
     * @param array include @see Long[] $childAttrs     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setChildAttrs( $childAttrs) {
        $this->childAttrs = $childAttrs;
    }
    
        	
    private $isSKU;
    
        /**
    * @return 是否SKU属性值
    */
        public function getIsSKU() {
        return $this->isSKU;
    }
    
    /**
     * 设置是否SKU属性值     
     * @param Boolean $isSKU     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSKU( $isSKU) {
        $this->isSKU = $isSKU;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "attrValueID", $this->stdResult )) {
    				$this->attrValueID = $this->stdResult->{"attrValueID"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "enName", $this->stdResult )) {
    				$this->enName = $this->stdResult->{"enName"};
    			}
    			    		    				    			    			if (array_key_exists ( "childAttrs", $this->stdResult )) {
    				$this->childAttrs = $this->stdResult->{"childAttrs"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSKU", $this->stdResult )) {
    				$this->isSKU = $this->stdResult->{"isSKU"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "attrValueID", $this->arrayResult )) {
    			$this->attrValueID = $arrayResult['attrValueID'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "enName", $this->arrayResult )) {
    			$this->enName = $arrayResult['enName'];
    			}
    		    	    			    		    			if (array_key_exists ( "childAttrs", $this->arrayResult )) {
    			$this->childAttrs = $arrayResult['childAttrs'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSKU", $this->arrayResult )) {
    			$this->isSKU = $arrayResult['isSKU'];
    			}
    		    	    		}
 
   
}
?>