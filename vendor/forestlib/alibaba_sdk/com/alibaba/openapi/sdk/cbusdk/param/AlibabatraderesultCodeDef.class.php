<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatraderesultCodeDef extends SDKDomain {

       	
    private $message;
    
        /**
    * @return 错误描述
    */
        public function getMessage() {
        return $this->message;
    }
    
    /**
     * 设置错误描述     
     * @param String $message     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMessage( $message) {
        $this->message = $message;
    }
    
        	
    private $resultCode;
    
        /**
    * @return 错误码
    */
        public function getResultCode() {
        return $this->resultCode;
    }
    
    /**
     * 设置错误码     
     * @param String $resultCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResultCode( $resultCode) {
        $this->resultCode = $resultCode;
    }
    
        	
    private $success;
    
        /**
    * @return 是否成功
    */
        public function getSuccess() {
        return $this->success;
    }
    
    /**
     * 设置是否成功     
     * @param Boolean $success     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSuccess( $success) {
        $this->success = $success;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "message", $this->stdResult )) {
    				$this->message = $this->stdResult->{"message"};
    			}
    			    		    				    			    			if (array_key_exists ( "resultCode", $this->stdResult )) {
    				$this->resultCode = $this->stdResult->{"resultCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "success", $this->stdResult )) {
    				$this->success = $this->stdResult->{"success"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "message", $this->arrayResult )) {
    			$this->message = $arrayResult['message'];
    			}
    		    	    			    		    			if (array_key_exists ( "resultCode", $this->arrayResult )) {
    			$this->resultCode = $arrayResult['resultCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "success", $this->arrayResult )) {
    			$this->success = $arrayResult['success'];
    			}
    		    	    		}
 
   
}
?>