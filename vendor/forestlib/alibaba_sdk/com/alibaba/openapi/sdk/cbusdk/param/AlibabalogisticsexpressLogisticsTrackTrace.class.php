<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsexpressLogisticsTrackTrace extends SDKDomain {

       	
    private $eventDesc;
    
        /**
    * @return 事件说明，备注
    */
        public function getEventDesc() {
        return $this->eventDesc;
    }
    
    /**
     * 设置事件说明，备注     
     * @param String $eventDesc     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEventDesc( $eventDesc) {
        $this->eventDesc = $eventDesc;
    }
    
        	
    private $eventValue;
    
        /**
    * @return 事件Value（如货物扫描的地点）
    */
        public function getEventValue() {
        return $this->eventValue;
    }
    
    /**
     * 设置事件Value（如货物扫描的地点）     
     * @param String $eventValue     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEventValue( $eventValue) {
        $this->eventValue = $eventValue;
    }
    
        	
    private $eventCode;
    
        /**
    * @return 事件代码（可能为空）
    */
        public function getEventCode() {
        return $this->eventCode;
    }
    
    /**
     * 设置事件代码（可能为空）     
     * @param String $eventCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEventCode( $eventCode) {
        $this->eventCode = $eventCode;
    }
    
        	
    private $eventName;
    
        /**
    * @return 事件名称
    */
        public function getEventName() {
        return $this->eventName;
    }
    
    /**
     * 设置事件名称     
     * @param String $eventName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEventName( $eventName) {
        $this->eventName = $eventName;
    }
    
        	
    private $eventTime;
    
        /**
    * @return 事件时间(ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）
    */
        public function getEventTime() {
        return $this->eventTime;
    }
    
    /**
     * 设置事件时间(ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）     
     * @param String $eventTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEventTime( $eventTime) {
        $this->eventTime = $eventTime;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "eventDesc", $this->stdResult )) {
    				$this->eventDesc = $this->stdResult->{"eventDesc"};
    			}
    			    		    				    			    			if (array_key_exists ( "eventValue", $this->stdResult )) {
    				$this->eventValue = $this->stdResult->{"eventValue"};
    			}
    			    		    				    			    			if (array_key_exists ( "eventCode", $this->stdResult )) {
    				$this->eventCode = $this->stdResult->{"eventCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "eventName", $this->stdResult )) {
    				$this->eventName = $this->stdResult->{"eventName"};
    			}
    			    		    				    			    			if (array_key_exists ( "eventTime", $this->stdResult )) {
    				$this->eventTime = $this->stdResult->{"eventTime"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "eventDesc", $this->arrayResult )) {
    			$this->eventDesc = $arrayResult['eventDesc'];
    			}
    		    	    			    		    			if (array_key_exists ( "eventValue", $this->arrayResult )) {
    			$this->eventValue = $arrayResult['eventValue'];
    			}
    		    	    			    		    			if (array_key_exists ( "eventCode", $this->arrayResult )) {
    			$this->eventCode = $arrayResult['eventCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "eventName", $this->arrayResult )) {
    			$this->eventName = $arrayResult['eventName'];
    			}
    		    	    			    		    			if (array_key_exists ( "eventTime", $this->arrayResult )) {
    			$this->eventTime = $arrayResult['eventTime'];
    			}
    		    	    		}
 
   
}
?>