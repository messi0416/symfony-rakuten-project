<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabapaymentChannelPreparePayResult.class.php');

class AlibabaPaymentOrderBankCreateResult {

        	
    private $result;
    
        /**
    * @return 预支付单返回结果
    */
        public function getResult() {
        return $this->result;
    }
    
    /**
     * 设置预支付单返回结果     
     * @param AlibabapaymentChannelPreparePayResult $result     
          
     * 此参数必填     */
    public function setResult(AlibabapaymentChannelPreparePayResult $result) {
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
    
        	
    private $errorMessagge;
    
        /**
    * @return 错误信息
    */
        public function getErrorMessagge() {
        return $this->errorMessagge;
    }
    
    /**
     * 设置错误信息     
     * @param String $errorMessagge     
          
     * 此参数必填     */
    public function setErrorMessagge( $errorMessagge) {
        $this->errorMessagge = $errorMessagge;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "result", $this->stdResult )) {
    				$resultResult=$this->stdResult->{"result"};
    				$this->result = new AlibabapaymentChannelPreparePayResult();
    				$this->result->setStdResult ( $resultResult);
    			}
    			    		    				    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMessagge", $this->stdResult )) {
    				$this->errorMessagge = $this->stdResult->{"errorMessagge"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "result", $this->arrayResult )) {
    		$resultResult=$arrayResult['result'];
    			    			$this->result = new AlibabapaymentChannelPreparePayResult();
    			    			$this->result->setStdResult ( $resultResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMessagge", $this->arrayResult )) {
    			$this->errorMessagge = $arrayResult['errorMessagge'];
    			}
    		    	    		}

}
?>