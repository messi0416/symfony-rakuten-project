<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaassuranceorderDetail extends SDKDomain {

       	
    private $flagList;
    
        /**
    * @return 返回四种flag html文本
    */
        public function getFlagList() {
        return $this->flagList;
    }
    
    /**
     * 设置返回四种flag html文本     
     * @param array include @see String[] $flagList     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFlagList( $flagList) {
        $this->flagList = $flagList;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "flagList", $this->stdResult )) {
    				$this->flagList = $this->stdResult->{"flagList"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "flagList", $this->arrayResult )) {
    			$this->flagList = $arrayResult['flagList'];
    			}
    		    	    		}
 
   
}
?>