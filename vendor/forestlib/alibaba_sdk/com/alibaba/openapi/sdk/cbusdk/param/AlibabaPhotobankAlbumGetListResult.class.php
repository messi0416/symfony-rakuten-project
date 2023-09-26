<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaphotobankphotoAlbumDomain.class.php');

class AlibabaPhotobankAlbumGetListResult {

        	
    private $albumInfos;
    
        /**
    * @return 相册信息
    */
        public function getAlbumInfos() {
        return $this->albumInfos;
    }
    
    /**
     * 设置相册信息     
     * @param array include @see AlibabaphotobankphotoAlbumDomain[] $albumInfos     
          
     * 此参数必填     */
    public function setAlbumInfos(AlibabaphotobankphotoAlbumDomain $albumInfos) {
        $this->albumInfos = $albumInfos;
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
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "albumInfos", $this->stdResult )) {
    			$albumInfosResult=$this->stdResult->{"albumInfos"};
    				$object = json_decode ( json_encode ( $albumInfosResult ), true );
					$this->albumInfos = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaphotobankphotoAlbumDomainResult=new AlibabaphotobankphotoAlbumDomain();
						$AlibabaphotobankphotoAlbumDomainResult->setArrayResult($arrayobject );
						$this->albumInfos [$i] = $AlibabaphotobankphotoAlbumDomainResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "errorMsg", $this->stdResult )) {
    				$this->errorMsg = $this->stdResult->{"errorMsg"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorCode", $this->stdResult )) {
    				$this->errorCode = $this->stdResult->{"errorCode"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "albumInfos", $this->arrayResult )) {
    		$albumInfosResult=$arrayResult['albumInfos'];
    			$this->albumInfos = new AlibabaphotobankphotoAlbumDomain();
    			$this->albumInfos->setStdResult ( $albumInfosResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorMsg", $this->arrayResult )) {
    			$this->errorMsg = $arrayResult['errorMsg'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorCode", $this->arrayResult )) {
    			$this->errorCode = $arrayResult['errorCode'];
    			}
    		    	    		}

}
?>