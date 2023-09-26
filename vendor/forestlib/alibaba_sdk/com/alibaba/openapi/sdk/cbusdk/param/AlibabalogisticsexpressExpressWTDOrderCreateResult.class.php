<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');

class AlibabalogisticsexpressExpressWTDOrderCreateResult extends SDKDomain {

       	
    private $orderId;
    
        /**
    * @return 订单ID
    */
        public function getOrderId() {
        return $this->orderId;
    }
    
    /**
     * 设置订单ID     
     * @param Long $orderId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->orderId = $orderId;
    }
    
        	
    private $estimatedCost;
    
        /**
    * @return 预估费用
    */
        public function getEstimatedCost() {
        return $this->estimatedCost;
    }
    
    /**
     * 设置预估费用     
     * @param AlibabalogisticscommonMoney $estimatedCost     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEstimatedCost(AlibabalogisticscommonMoney $estimatedCost) {
        $this->estimatedCost = $estimatedCost;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "orderId", $this->stdResult )) {
    				$this->orderId = $this->stdResult->{"orderId"};
    			}
    			    		    				    			    			if (array_key_exists ( "estimatedCost", $this->stdResult )) {
    				$estimatedCostResult=$this->stdResult->{"estimatedCost"};
    				$this->estimatedCost = new AlibabalogisticscommonMoney();
    				$this->estimatedCost->setStdResult ( $estimatedCostResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "orderId", $this->arrayResult )) {
    			$this->orderId = $arrayResult['orderId'];
    			}
    		    	    			    		    		if (array_key_exists ( "estimatedCost", $this->arrayResult )) {
    		$estimatedCostResult=$arrayResult['estimatedCost'];
    			    			$this->estimatedCost = new AlibabalogisticscommonMoney();
    			    			$this->estimatedCost->$this->setStdResult ( $estimatedCostResult);
    		}
    		    	    		}
 
   
}
?>