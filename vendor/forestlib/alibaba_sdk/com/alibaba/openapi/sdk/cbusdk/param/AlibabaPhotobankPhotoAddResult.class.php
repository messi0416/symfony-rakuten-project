<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaphotobankphotoResponseDomain.class.php');

class AlibabaPhotobankPhotoAddResult {

        	
    private $image;
    
        /**
    * @return 图片信息
    */
        public function getImage() {
        return $this->image;
    }
    
    /**
     * 设置图片信息     
     * @param AlibabaphotobankphotoResponseDomain $image     
          
     * 此参数必填     */
    public function setImage(AlibabaphotobankphotoResponseDomain $image) {
        $this->image = $image;
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
    
        	
    private $errorMsg;
    
        /**
    * @return 错误信息描述
    */
        public function getErrorMsg() {
        return $this->errorMsg;
    }
    
    /**
     * 设置错误信息描述     
     * @param String $errorMsg     
          
     * 此参数必填     */
    public function setErrorMsg( $errorMsg) {
        $this->errorMsg = $errorMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "image", $this->stdResult )) {
    				$imageResult=$this->stdResult->{"image"};
    				$this->image = new AlibabaphotobankphotoResponseDomain();
    				$this->image->setStdResult ( $imageResult);
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
				    		    		if (array_key_exists ( "image", $this->arrayResult )) {
    		$imageResult=$arrayResult['image'];
    			    			$this->image = new AlibabaphotobankphotoResponseDomain();
    			    			$this->image->setStdResult ( $imageResult);
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