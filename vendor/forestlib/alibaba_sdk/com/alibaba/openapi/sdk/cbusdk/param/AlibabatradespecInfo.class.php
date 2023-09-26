<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradespecInfo extends SDKDomain {

       	
    private $specName;
    
        /**
    * @return 规格属性名称
    */
        public function getSpecName() {
        return $this->specName;
    }
    
    /**
     * 设置规格属性名称     
     * @param String $specName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecName( $specName) {
        $this->specName = $specName;
    }
    
        	
    private $specValue;
    
        /**
    * @return 规格属性值
    */
        public function getSpecValue() {
        return $this->specValue;
    }
    
    /**
     * 设置规格属性值     
     * @param String $specValue     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecValue( $specValue) {
        $this->specValue = $specValue;
    }
    
        	
    private $unit;
    
        /**
    * @return 规格属性单位
    */
        public function getUnit() {
        return $this->unit;
    }
    
    /**
     * 设置规格属性单位     
     * @param String $unit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnit( $unit) {
        $this->unit = $unit;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "specName", $this->stdResult )) {
    				$this->specName = $this->stdResult->{"specName"};
    			}
    			    		    				    			    			if (array_key_exists ( "specValue", $this->stdResult )) {
    				$this->specValue = $this->stdResult->{"specValue"};
    			}
    			    		    				    			    			if (array_key_exists ( "unit", $this->stdResult )) {
    				$this->unit = $this->stdResult->{"unit"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "specName", $this->arrayResult )) {
    			$this->specName = $arrayResult['specName'];
    			}
    		    	    			    		    			if (array_key_exists ( "specValue", $this->arrayResult )) {
    			$this->specValue = $arrayResult['specValue'];
    			}
    		    	    			    		    			if (array_key_exists ( "unit", $this->arrayResult )) {
    			$this->unit = $arrayResult['unit'];
    			}
    		    	    		}
 
   
}
?>