<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelTradeInfo.class.php');

class AlibabaTradeGetSellerOrderListResult {

        	
    private $result;
    
        /**
    * @return 查询返回结果
    */
        public function getResult() {
        return $this->result;
    }
    
    /**
     * 设置查询返回结果     
     * @param array include @see AlibabaopenplatformtrademodelTradeInfo[] $result     
          
     * 此参数必填     */
    public function setResult(AlibabaopenplatformtrademodelTradeInfo $result) {
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
    
        	
    private $totalRecord;
    
        /**
    * @return 总记录数
    */
        public function getTotalRecord() {
        return $this->totalRecord;
    }
    
    /**
     * 设置总记录数     
     * @param Long $totalRecord     
          
     * 此参数必填     */
    public function setTotalRecord( $totalRecord) {
        $this->totalRecord = $totalRecord;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "result", $this->stdResult )) {
    			$resultResult=$this->stdResult->{"result"};
    				$object = json_decode ( json_encode ( $resultResult ), true );
					$this->result = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtrademodelTradeInfoResult=new AlibabaopenplatformtrademodelTradeInfo();
						$AlibabaopenplatformtrademodelTradeInfoResult->setArrayResult($arrayobject );
						$this->result [$i] = $AlibabaopenplatformtrademodelTradeInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMessage", $this->stdResult )) {
    				$this->errorMessage = $this->stdResult->{"errorMessage"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalRecord", $this->stdResult )) {
    				$this->totalRecord = $this->stdResult->{"totalRecord"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "result", $this->arrayResult )) {
    		$resultResult=$arrayResult['result'];
    			$this->result = new AlibabaopenplatformtrademodelTradeInfo();
    			$this->result->setStdResult ( $resultResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMessage", $this->arrayResult )) {
    			$this->errorMessage = $arrayResult['errorMessage'];
    			}
    		    	    			    		    			if (array_key_exists ( "totalRecord", $this->arrayResult )) {
    			$this->totalRecord = $arrayResult['totalRecord'];
    			}
    		    	    		}

}
?>