<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeOrderMutilViewResult.class.php');

class AlibabaTradeGeneralPreorderResult {

        	
    private $result;
    
        /**
    * @return 多订单展示处理结果
    */
        public function getResult() {
        return $this->result;
    }
    
    /**
     * 设置多订单展示处理结果     
     * @param AlibabatradeOrderMutilViewResult $result     
          
     * 此参数必填     */
    public function setResult(AlibabatradeOrderMutilViewResult $result) {
        $this->result = $result;
    }
    
        	
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
          
     * 此参数必填     */
    public function setErrorCode( $errorCode) {
        $this->errorCode = $errorCode;
    }
    
        	
    private $errorMessage;
    
        /**
    * @return 
    */
        public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    /**
     * 设置     
     * @param String $errorMessage     
          
     * 此参数必填     */
    public function setErrorMessage( $errorMessage) {
        $this->errorMessage = $errorMessage;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "result", $this->stdResult )) {
    				$resultResult=$this->stdResult->{"result"};
    				$this->result = new AlibabatradeOrderMutilViewResult();
    				$this->result->setStdResult ( $resultResult);
    			}
    			    		    				    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMessage", $this->stdResult )) {
    				$this->errorMessage = $this->stdResult->{"errorMessage"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "result", $this->arrayResult )) {
    		$resultResult=$arrayResult['result'];
    			    			$this->result = new AlibabatradeOrderMutilViewResult();
    			    			$this->result->setStdResult ( $resultResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMessage", $this->arrayResult )) {
    			$this->errorMessage = $arrayResult['errorMessage'];
    			}
    		    	    		}

}
?>