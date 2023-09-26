<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGroupAddResult {

        	
    private $groupID;
    
        /**
    * @return 分组ID
    */
        public function getGroupID() {
        return $this->groupID;
    }
    
    /**
     * 设置分组ID     
     * @param Long $groupID     
          
     * 此参数必填     */
    public function setGroupID( $groupID) {
        $this->groupID = $groupID;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "groupID", $this->stdResult )) {
    				$this->groupID = $this->stdResult->{"groupID"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "groupID", $this->arrayResult )) {
    			$this->groupID = $arrayResult['groupID'];
    			}
    		    	    		}

}
?>