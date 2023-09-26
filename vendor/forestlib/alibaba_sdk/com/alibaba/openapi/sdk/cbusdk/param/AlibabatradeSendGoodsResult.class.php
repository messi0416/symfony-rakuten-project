<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeSendGoodsResult extends SDKDomain {

       	
    private $logisticsId;
    
        /**
    * @return 物流单号
    */
        public function getLogisticsId() {
        return $this->logisticsId;
    }
    
    /**
     * 设置物流单号     
     * @param String $logisticsId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsId( $logisticsId) {
        $this->logisticsId = $logisticsId;
    }
    
        	
    private $orderId;
    
        /**
    * @return 订单号
    */
        public function getOrderId() {
        return $this->orderId;
    }
    
    /**
     * 设置订单号     
     * @param String $orderId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->orderId = $orderId;
    }
    
        	
    private $orderEntryIds;
    
        /**
    * @return 订单明细ID，以逗号分隔
    */
        public function getOrderEntryIds() {
        return $this->orderEntryIds;
    }
    
    /**
     * 设置订单明细ID，以逗号分隔     
     * @param String $orderEntryIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderEntryIds( $orderEntryIds) {
        $this->orderEntryIds = $orderEntryIds;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "logisticsId", $this->stdResult )) {
    				$this->logisticsId = $this->stdResult->{"logisticsId"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderId", $this->stdResult )) {
    				$this->orderId = $this->stdResult->{"orderId"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderEntryIds", $this->stdResult )) {
    				$this->orderEntryIds = $this->stdResult->{"orderEntryIds"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "logisticsId", $this->arrayResult )) {
    			$this->logisticsId = $arrayResult['logisticsId'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderId", $this->arrayResult )) {
    			$this->orderId = $arrayResult['orderId'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderEntryIds", $this->arrayResult )) {
    			$this->orderEntryIds = $arrayResult['orderEntryIds'];
    			}
    		    	    		}
 
   
}
?>