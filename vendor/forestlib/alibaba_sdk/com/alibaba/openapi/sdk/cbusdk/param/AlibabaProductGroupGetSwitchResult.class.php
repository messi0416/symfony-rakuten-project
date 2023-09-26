<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGroupGetSwitchResult {

        	
    private $switchValue;
    
        /**
    * @return true：已开启；false：未开启
    */
        public function getSwitchValue() {
        return $this->switchValue;
    }
    
    /**
     * 设置true：已开启；false：未开启     
     * @param Boolean $switchValue     
          
     * 此参数必填     */
    public function setSwitchValue( $switchValue) {
        $this->switchValue = $switchValue;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "switchValue", $this->stdResult )) {
    				$this->switchValue = $this->stdResult->{"switchValue"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "switchValue", $this->arrayResult )) {
    			$this->switchValue = $arrayResult['switchValue'];
    			}
    		    	    		}

}
?>