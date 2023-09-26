<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPhotobankAlbumDeleteResult {

        	
    private $isSuccess;
    
        /**
    * @return 删除是否成功
    */
        public function getIsSuccess() {
        return $this->isSuccess;
    }
    
    /**
     * 设置删除是否成功     
     * @param Boolean $isSuccess     
          
     * 此参数必填     */
    public function setIsSuccess( $isSuccess) {
        $this->isSuccess = $isSuccess;
    }
    
        	
    private $reason;
    
        /**
    * @return 删除不成功的原因描述
    */
        public function getReason() {
        return $this->reason;
    }
    
    /**
     * 设置删除不成功的原因描述     
     * @param String $reason     
          
     * 此参数必填     */
    public function setReason( $reason) {
        $this->reason = $reason;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "isSuccess", $this->stdResult )) {
    				$this->isSuccess = $this->stdResult->{"isSuccess"};
    			}
    			    		    				    			    			if (array_key_exists ( "reason", $this->stdResult )) {
    				$this->reason = $this->stdResult->{"reason"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "isSuccess", $this->arrayResult )) {
    			$this->isSuccess = $arrayResult['isSuccess'];
    			}
    		    	    			    		    			if (array_key_exists ( "reason", $this->arrayResult )) {
    			$this->reason = $arrayResult['reason'];
    			}
    		    	    		}

}
?>