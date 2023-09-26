<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductAddResult {

        	
    private $productID;
    
        /**
    * @return 操作成功的情况下会返回商品ID
    */
        public function getProductID() {
        return $this->productID;
    }
    
    /**
     * 设置操作成功的情况下会返回商品ID     
     * @param Long $productID     
          
     * 此参数必填     */
    public function setProductID( $productID) {
        $this->productID = $productID;
    }
    
        	
    private $errorMsg;
    
        /**
    * @return 错误返回信息
    */
        public function getErrorMsg() {
        return $this->errorMsg;
    }
    
    /**
     * 设置错误返回信息     
     * @param String $errorMsg     
          
     * 此参数必填     */
    public function setErrorMsg( $errorMsg) {
        $this->errorMsg = $errorMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "productID", $this->stdResult )) {
    				$this->productID = $this->stdResult->{"productID"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMsg", $this->stdResult )) {
    				$this->errorMsg = $this->stdResult->{"errorMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "productID", $this->arrayResult )) {
    			$this->productID = $arrayResult['productID'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMsg", $this->arrayResult )) {
    			$this->errorMsg = $arrayResult['errorMsg'];
    			}
    		    	    		}

}
?>