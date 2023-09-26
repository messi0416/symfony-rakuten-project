<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeToleranceCollection extends SDKDomain {

       	
    private $toleranceFreight;
    
        /**
    * @return 运费是否被容错. 0: 没有容错。 1：被容错.
    */
        public function getToleranceFreight() {
        return $this->toleranceFreight;
    }
    
    /**
     * 设置运费是否被容错. 0: 没有容错。 1：被容错.     
     * @param String $toleranceFreight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setToleranceFreight( $toleranceFreight) {
        $this->toleranceFreight = $toleranceFreight;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "toleranceFreight", $this->stdResult )) {
    				$this->toleranceFreight = $this->stdResult->{"toleranceFreight"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "toleranceFreight", $this->arrayResult )) {
    			$this->toleranceFreight = $arrayResult['toleranceFreight'];
    			}
    		    	    		}
 
   
}
?>