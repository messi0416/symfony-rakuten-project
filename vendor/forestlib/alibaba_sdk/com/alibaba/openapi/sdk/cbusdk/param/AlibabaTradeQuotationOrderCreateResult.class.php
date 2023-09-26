<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult.class.php');

class AlibabaTradeQuotationOrderCreateResult {

        	
    private $orderResult;
    
        /**
    * @return 订单创建结果
    */
        public function getOrderResult() {
        return $this->orderResult;
    }
    
    /**
     * 设置订单创建结果     
     * @param AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult $orderResult     
          
     * 此参数必填     */
    public function setOrderResult(AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult $orderResult) {
        $this->orderResult = $orderResult;
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
					    			    			if (array_key_exists ( "orderResult", $this->stdResult )) {
    				$orderResultResult=$this->stdResult->{"orderResult"};
    				$this->orderResult = new AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult();
    				$this->orderResult->setStdResult ( $orderResultResult);
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
				    		    		if (array_key_exists ( "orderResult", $this->arrayResult )) {
    		$orderResultResult=$arrayResult['orderResult'];
    			    			$this->orderResult = new AlibabaopenplatformtraderesultTradeCreateGeneralOrderResult();
    			    			$this->orderResult->setStdResult ( $orderResultResult);
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