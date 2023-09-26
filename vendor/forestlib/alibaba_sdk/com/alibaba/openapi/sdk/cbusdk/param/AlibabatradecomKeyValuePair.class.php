<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradecomKeyValuePair extends SDKDomain {

       	
    private $key;
    
        /**
    * @return 键值对的Key
    */
        public function getKey() {
        return $this->key;
    }
    
    /**
     * 设置键值对的Key     
     * @param String $key     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setKey( $key) {
        $this->key = $key;
    }
    
        	
    private $value;
    
        /**
    * @return 键值对的Value
    */
        public function getValue() {
        return $this->value;
    }
    
    /**
     * 设置键值对的Value     
     * @param String $value     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setValue( $value) {
        $this->value = $value;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "key", $this->stdResult )) {
    				$this->key = $this->stdResult->{"key"};
    			}
    			    		    				    			    			if (array_key_exists ( "value", $this->stdResult )) {
    				$this->value = $this->stdResult->{"value"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "key", $this->arrayResult )) {
    			$this->key = $arrayResult['key'];
    			}
    		    	    			    		    			if (array_key_exists ( "value", $this->arrayResult )) {
    			$this->value = $arrayResult['value'];
    			}
    		    	    		}
 
   
}
?>