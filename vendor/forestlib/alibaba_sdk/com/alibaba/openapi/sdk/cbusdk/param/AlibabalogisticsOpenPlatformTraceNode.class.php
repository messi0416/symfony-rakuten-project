<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsOpenPlatformTraceNode extends SDKDomain {

       	
    private $action;
    
        /**
    * @return 
    */
        public function getAction() {
        return $this->action;
    }
    
    /**
     * 设置     
     * @param String $action     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAction( $action) {
        $this->action = $action;
    }
    
        	
    private $areaCode;
    
        /**
    * @return 
    */
        public function getAreaCode() {
        return $this->areaCode;
    }
    
    /**
     * 设置     
     * @param String $areaCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAreaCode( $areaCode) {
        $this->areaCode = $areaCode;
    }
    
        	
    private $encrypt;
    
        /**
    * @return 
    */
        public function getEncrypt() {
        return $this->encrypt;
    }
    
    /**
     * 设置     
     * @param String $encrypt     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEncrypt( $encrypt) {
        $this->encrypt = $encrypt;
    }
    
        	
    private $acceptTime;
    
        /**
    * @return 
    */
        public function getAcceptTime() {
        return $this->acceptTime;
    }
    
    /**
     * 设置     
     * @param String $acceptTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAcceptTime( $acceptTime) {
        $this->acceptTime = $acceptTime;
    }
    
        	
    private $remark;
    
        /**
    * @return 
    */
        public function getRemark() {
        return $this->remark;
    }
    
    /**
     * 设置     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->remark = $remark;
    }
    
        	
    private $facilityType;
    
        /**
    * @return 
    */
        public function getFacilityType() {
        return $this->facilityType;
    }
    
    /**
     * 设置     
     * @param String $facilityType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFacilityType( $facilityType) {
        $this->facilityType = $facilityType;
    }
    
        	
    private $facilityNo;
    
        /**
    * @return 
    */
        public function getFacilityNo() {
        return $this->facilityNo;
    }
    
    /**
     * 设置     
     * @param String $facilityNo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFacilityNo( $facilityNo) {
        $this->facilityNo = $facilityNo;
    }
    
        	
    private $facilityName;
    
        /**
    * @return 
    */
        public function getFacilityName() {
        return $this->facilityName;
    }
    
    /**
     * 设置     
     * @param String $facilityName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFacilityName( $facilityName) {
        $this->facilityName = $facilityName;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "action", $this->stdResult )) {
    				$this->action = $this->stdResult->{"action"};
    			}
    			    		    				    			    			if (array_key_exists ( "areaCode", $this->stdResult )) {
    				$this->areaCode = $this->stdResult->{"areaCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "encrypt", $this->stdResult )) {
    				$this->encrypt = $this->stdResult->{"encrypt"};
    			}
    			    		    				    			    			if (array_key_exists ( "acceptTime", $this->stdResult )) {
    				$this->acceptTime = $this->stdResult->{"acceptTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    				    			    			if (array_key_exists ( "facilityType", $this->stdResult )) {
    				$this->facilityType = $this->stdResult->{"facilityType"};
    			}
    			    		    				    			    			if (array_key_exists ( "facilityNo", $this->stdResult )) {
    				$this->facilityNo = $this->stdResult->{"facilityNo"};
    			}
    			    		    				    			    			if (array_key_exists ( "facilityName", $this->stdResult )) {
    				$this->facilityName = $this->stdResult->{"facilityName"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "action", $this->arrayResult )) {
    			$this->action = $arrayResult['action'];
    			}
    		    	    			    		    			if (array_key_exists ( "areaCode", $this->arrayResult )) {
    			$this->areaCode = $arrayResult['areaCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "encrypt", $this->arrayResult )) {
    			$this->encrypt = $arrayResult['encrypt'];
    			}
    		    	    			    		    			if (array_key_exists ( "acceptTime", $this->arrayResult )) {
    			$this->acceptTime = $arrayResult['acceptTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    			    		    			if (array_key_exists ( "facilityType", $this->arrayResult )) {
    			$this->facilityType = $arrayResult['facilityType'];
    			}
    		    	    			    		    			if (array_key_exists ( "facilityNo", $this->arrayResult )) {
    			$this->facilityNo = $arrayResult['facilityNo'];
    			}
    		    	    			    		    			if (array_key_exists ( "facilityName", $this->arrayResult )) {
    			$this->facilityName = $arrayResult['facilityName'];
    			}
    		    	    		}
 
   
}
?>