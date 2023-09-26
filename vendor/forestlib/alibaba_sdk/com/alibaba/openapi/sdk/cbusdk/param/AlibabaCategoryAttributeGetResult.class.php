<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabacategoryAttributeInfo.class.php');

class AlibabaCategoryAttributeGetResult {

        	
    private $attributes;
    
        /**
    * @return 类目属性信息
    */
        public function getAttributes() {
        return $this->attributes;
    }
    
    /**
     * 设置类目属性信息     
     * @param array include @see AlibabacategoryAttributeInfo[] $attributes     
          
     * 此参数必填     */
    public function setAttributes(AlibabacategoryAttributeInfo $attributes) {
        $this->attributes = $attributes;
    }
    
        	
    private $errorMsg;
    
        /**
    * @return 错误描述
    */
        public function getErrorMsg() {
        return $this->errorMsg;
    }
    
    /**
     * 设置错误描述     
     * @param String $errorMsg     
          
     * 此参数必填     */
    public function setErrorMsg( $errorMsg) {
        $this->errorMsg = $errorMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "attributes", $this->stdResult )) {
    			$attributesResult=$this->stdResult->{"attributes"};
    				$object = json_decode ( json_encode ( $attributesResult ), true );
					$this->attributes = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabacategoryAttributeInfoResult=new AlibabacategoryAttributeInfo();
						$AlibabacategoryAttributeInfoResult->setArrayResult($arrayobject );
						$this->attributes [$i] = $AlibabacategoryAttributeInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "errorMsg", $this->stdResult )) {
    				$this->errorMsg = $this->stdResult->{"errorMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "attributes", $this->arrayResult )) {
    		$attributesResult=$arrayResult['attributes'];
    			$this->attributes = new AlibabacategoryAttributeInfo();
    			$this->attributes->setStdResult ( $attributesResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorMsg", $this->arrayResult )) {
    			$this->errorMsg = $arrayResult['errorMsg'];
    			}
    		    	    		}

}
?>