<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaProductGroupGetResult {

        	
    private $name;
    
        /**
    * @return 分组名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置分组名称     
     * @param String $name     
          
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $parentID;
    
        /**
    * @return 上级分组ID，如果当前为顶级分组则此值为-1
    */
        public function getParentID() {
        return $this->parentID;
    }
    
    /**
     * 设置上级分组ID，如果当前为顶级分组则此值为-1     
     * @param Long $parentID     
          
     * 此参数必填     */
    public function setParentID( $parentID) {
        $this->parentID = $parentID;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "parentID", $this->stdResult )) {
    				$this->parentID = $this->stdResult->{"parentID"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "parentID", $this->arrayResult )) {
    			$this->parentID = $arrayResult['parentID'];
    			}
    		    	    		}

}
?>