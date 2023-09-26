<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsOpenPlatformLogisticsStep extends SDKDomain {

       	
    private $acceptTime;
    
        /**
    * @return 物流跟踪单该步骤的时间
    */
        public function getAcceptTime() {
        return $this->acceptTime;
    }
    
    /**
     * 设置物流跟踪单该步骤的时间     
     * @param String $acceptTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAcceptTime( $acceptTime) {
        $this->acceptTime = $acceptTime;
    }
    
        	
    private $remark;
    
        /**
    * @return 备注
    */
        public function getRemark() {
        return $this->remark;
    }
    
    /**
     * 设置备注     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->remark = $remark;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "acceptTime", $this->stdResult )) {
    				$this->acceptTime = $this->stdResult->{"acceptTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "acceptTime", $this->arrayResult )) {
    			$this->acceptTime = $arrayResult['acceptTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    		}
 
   
}
?>