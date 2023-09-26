<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaLogisticsFreightTemplateAddResult {

        	
    private $templateID;
    
        /**
    * @return 模板ID
    */
        public function getTemplateID() {
        return $this->templateID;
    }
    
    /**
     * 设置模板ID     
     * @param Long $templateID     
          
     * 此参数必填     */
    public function setTemplateID( $templateID) {
        $this->templateID = $templateID;
    }
    
        	
    private $errorCode;
    
        /**
    * @return 错误编码
    */
        public function getErrorCode() {
        return $this->errorCode;
    }
    
    /**
     * 设置错误编码     
     * @param String $errorCode     
          
     * 此参数必填     */
    public function setErrorCode( $errorCode) {
        $this->errorCode = $errorCode;
    }
    
        	
    private $errorMsg;
    
        /**
    * @return 错误信息
    */
        public function getErrorMsg() {
        return $this->errorMsg;
    }
    
    /**
     * 设置错误信息     
     * @param String $errorMsg     
          
     * 此参数必填     */
    public function setErrorMsg( $errorMsg) {
        $this->errorMsg = $errorMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "templateID", $this->stdResult )) {
    				$this->templateID = $this->stdResult->{"templateID"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMsg", $this->stdResult )) {
    				$this->errorMsg = $this->stdResult->{"errorMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "templateID", $this->arrayResult )) {
    			$this->templateID = $arrayResult['templateID'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMsg", $this->arrayResult )) {
    			$this->errorMsg = $arrayResult['errorMsg'];
    			}
    		    	    		}

}
?>