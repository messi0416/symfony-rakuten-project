<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult.class.php');

class AlibabaTradeGeneralCreateOrderResult {

        	
    private $result;
    
        /**
    * @return 
    */
        public function getResult() {
        return $this->result;
    }
    
    /**
     * 设置     
     * @param AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult $result     
          
     * 此参数必填     */
    public function setResult(AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult $result) {
        $this->result = $result;
    }
    
        	
    private $errorCode;
    
        /**
    * @return 错误码
    */
        public function getErrorCode() {
        return $this->errorCode;
    }
    
    /**
     * 设置错误码     
     * @param String $errorCode     
          
     * 此参数必填     */
    public function setErrorCode( $errorCode) {
        $this->errorCode = $errorCode;
    }
    
        	
    private $errorMessage;
    
        /**
    * @return 错误信息
    */
        public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    /**
     * 设置错误信息     
     * @param String $errorMessage     
          
     * 此参数必填     */
    public function setErrorMessage( $errorMessage) {
        $this->errorMessage = $errorMessage;
    }
    
        	
    private $extErrorMessage;
    
        /**
    * @return 错误信息扩展
    */
        public function getExtErrorMessage() {
        return $this->extErrorMessage;
    }
    
    /**
     * 设置错误信息扩展     
     * @param String $extErrorMessage     
          
     * 此参数必填     */
    public function setExtErrorMessage( $extErrorMessage) {
        $this->extErrorMessage = $extErrorMessage;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "result", $this->stdResult )) {
    				$resultResult=$this->stdResult->{"result"};
    				$this->result = new AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult();
    				$this->result->setStdResult ( $resultResult);
    			}
    			    		    				    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMessage", $this->stdResult )) {
    				$this->errorMessage = $this->stdResult->{"errorMessage"};
    			}
    			    		    				    			    			if (array_key_exists ( "extErrorMessage", $this->stdResult )) {
    				$this->extErrorMessage = $this->stdResult->{"extErrorMessage"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "result", $this->arrayResult )) {
    		$resultResult=$arrayResult['result'];
    			    			$this->result = new AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult();
    			    			$this->result->setStdResult ( $resultResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMessage", $this->arrayResult )) {
    			$this->errorMessage = $arrayResult['errorMessage'];
    			}
    		    	    			    		    			if (array_key_exists ( "extErrorMessage", $this->arrayResult )) {
    			$this->extErrorMessage = $arrayResult['extErrorMessage'];
    			}
    		    	    		}

}
?>