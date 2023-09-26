<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductProductOperateResult extends SDKDomain {

       	
    private $productId;
    
        /**
    * @return 
    */
        public function getProductId() {
        return $this->productId;
    }
    
    /**
     * 设置     
     * @param Long $productId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductId( $productId) {
        $this->productId = $productId;
    }
    
        	
    private $result;
    
        /**
    * @return 
    */
        public function getResult() {
        return $this->result;
    }
    
    /**
     * 设置     
     * @param Boolean $result     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResult( $result) {
        $this->result = $result;
    }
    
        	
    private $code;
    
        /**
    * @return 
    */
        public function getCode() {
        return $this->code;
    }
    
    /**
     * 设置     
     * @param String $code     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCode( $code) {
        $this->code = $code;
    }
    
        	
    private $desc;
    
        /**
    * @return 
    */
        public function getDesc() {
        return $this->desc;
    }
    
    /**
     * 设置     
     * @param String $desc     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDesc( $desc) {
        $this->desc = $desc;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "productId", $this->stdResult )) {
    				$this->productId = $this->stdResult->{"productId"};
    			}
    			    		    				    			    			if (array_key_exists ( "result", $this->stdResult )) {
    				$this->result = $this->stdResult->{"result"};
    			}
    			    		    				    			    			if (array_key_exists ( "code", $this->stdResult )) {
    				$this->code = $this->stdResult->{"code"};
    			}
    			    		    				    			    			if (array_key_exists ( "desc", $this->stdResult )) {
    				$this->desc = $this->stdResult->{"desc"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "productId", $this->arrayResult )) {
    			$this->productId = $arrayResult['productId'];
    			}
    		    	    			    		    			if (array_key_exists ( "result", $this->arrayResult )) {
    			$this->result = $arrayResult['result'];
    			}
    		    	    			    		    			if (array_key_exists ( "code", $this->arrayResult )) {
    			$this->code = $arrayResult['code'];
    			}
    		    	    			    		    			if (array_key_exists ( "desc", $this->arrayResult )) {
    			$this->desc = $arrayResult['desc'];
    			}
    		    	    		}
 
   
}
?>