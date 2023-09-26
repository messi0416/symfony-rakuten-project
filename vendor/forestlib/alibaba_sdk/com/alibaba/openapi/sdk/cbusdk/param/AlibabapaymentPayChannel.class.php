<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabapaymentPayChannel extends SDKDomain {

       	
    private $errorCode;
    
        /**
    * @return 
    */
        public function getErrorCode() {
        return $this->errorCode;
    }
    
    /**
     * 设置     
     * @param String $errorCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setErrorCode( $errorCode) {
        $this->errorCode = $errorCode;
    }
    
        	
    private $isAvaliable;
    
        /**
    * @return 
    */
        public function getIsAvaliable() {
        return $this->isAvaliable;
    }
    
    /**
     * 设置     
     * @param Boolean $isAvaliable     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsAvaliable( $isAvaliable) {
        $this->isAvaliable = $isAvaliable;
    }
    
        	
    private $isNeedBuyerSign;
    
        /**
    * @return 
    */
        public function getIsNeedBuyerSign() {
        return $this->isNeedBuyerSign;
    }
    
    /**
     * 设置     
     * @param Boolean $isNeedBuyerSign     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsNeedBuyerSign( $isNeedBuyerSign) {
        $this->isNeedBuyerSign = $isNeedBuyerSign;
    }
    
        	
    private $name;
    
        /**
    * @return 
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "isAvaliable", $this->stdResult )) {
    				$this->isAvaliable = $this->stdResult->{"isAvaliable"};
    			}
    			    		    				    			    			if (array_key_exists ( "isNeedBuyerSign", $this->stdResult )) {
    				$this->isNeedBuyerSign = $this->stdResult->{"isNeedBuyerSign"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "isAvaliable", $this->arrayResult )) {
    			$this->isAvaliable = $arrayResult['isAvaliable'];
    			}
    		    	    			    		    			if (array_key_exists ( "isNeedBuyerSign", $this->arrayResult )) {
    			$this->isNeedBuyerSign = $arrayResult['isNeedBuyerSign'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    		}
 
   
}
?>