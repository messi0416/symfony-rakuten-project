<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtrademodelNativeLogisticsItemsInfo extends SDKDomain {

       	
    private $deliveredTime;
    
        /**
    * @return 发货时间
    */
        public function getDeliveredTime() {
        return $this->deliveredTime;
    }
    
    /**
     * 设置发货时间     
     * @param Date $deliveredTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDeliveredTime( $deliveredTime) {
        $this->deliveredTime = $deliveredTime;
    }
    
        	
    private $logisticsCode;
    
        /**
    * @return 物流编号
    */
        public function getLogisticsCode() {
        return $this->logisticsCode;
    }
    
    /**
     * 设置物流编号     
     * @param String $logisticsCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsCode( $logisticsCode) {
        $this->logisticsCode = $logisticsCode;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "deliveredTime", $this->stdResult )) {
    				$this->deliveredTime = $this->stdResult->{"deliveredTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsCode", $this->stdResult )) {
    				$this->logisticsCode = $this->stdResult->{"logisticsCode"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "deliveredTime", $this->arrayResult )) {
    			$this->deliveredTime = $arrayResult['deliveredTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "logisticsCode", $this->arrayResult )) {
    			$this->logisticsCode = $arrayResult['logisticsCode'];
    			}
    		    	    		}
 
   
}
?>