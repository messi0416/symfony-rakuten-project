<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaPhotobankAlbumAddResult {

        	
    private $albumID;
    
        /**
    * @return 相册ID
    */
        public function getAlbumID() {
        return $this->albumID;
    }
    
    /**
     * 设置相册ID     
     * @param Long $albumID     
          
     * 此参数必填     */
    public function setAlbumID( $albumID) {
        $this->albumID = $albumID;
    }
    
        	
    private $errCode;
    
        /**
    * @return 错误编码
    */
        public function getErrCode() {
        return $this->errCode;
    }
    
    /**
     * 设置错误编码     
     * @param String $errCode     
          
     * 此参数必填     */
    public function setErrCode( $errCode) {
        $this->errCode = $errCode;
    }
    
        	
    private $errMsg;
    
        /**
    * @return 错误信息
    */
        public function getErrMsg() {
        return $this->errMsg;
    }
    
    /**
     * 设置错误信息     
     * @param String $errMsg     
          
     * 此参数必填     */
    public function setErrMsg( $errMsg) {
        $this->errMsg = $errMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "albumID", $this->stdResult )) {
    				$this->albumID = $this->stdResult->{"albumID"};
    			}
    			    		    				    			    			if (array_key_exists ( "errCode", $this->stdResult )) {
    				$this->errCode = $this->stdResult->{"errCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "errMsg", $this->stdResult )) {
    				$this->errMsg = $this->stdResult->{"errMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "albumID", $this->arrayResult )) {
    			$this->albumID = $arrayResult['albumID'];
    			}
    		    	    			    		    			if (array_key_exists ( "errCode", $this->arrayResult )) {
    			$this->errCode = $arrayResult['errCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "errMsg", $this->arrayResult )) {
    			$this->errMsg = $arrayResult['errMsg'];
    			}
    		    	    		}

}
?>