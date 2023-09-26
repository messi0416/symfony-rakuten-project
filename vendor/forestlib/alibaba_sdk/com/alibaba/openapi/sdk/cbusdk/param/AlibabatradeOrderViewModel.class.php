<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatraderesultCodeDef.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeMakeOrderModel.class.php');

class AlibabatradeOrderViewModel extends SDKDomain {

       	
    private $afterFlowIds;
    
        /**
    * @return 
    */
        public function getAfterFlowIds() {
        return $this->afterFlowIds;
    }
    
    /**
     * 设置     
     * @param array include @see String[] $afterFlowIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAfterFlowIds( $afterFlowIds) {
        $this->afterFlowIds = $afterFlowIds;
    }
    
        	
    private $resultCode;
    
        /**
    * @return 
    */
        public function getResultCode() {
        return $this->resultCode;
    }
    
    /**
     * 设置     
     * @param AlibabatraderesultCodeDef $resultCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResultCode(AlibabatraderesultCodeDef $resultCode) {
        $this->resultCode = $resultCode;
    }
    
        	
    private $orderModel;
    
        /**
    * @return 
    */
        public function getOrderModel() {
        return $this->orderModel;
    }
    
    /**
     * 设置     
     * @param AlibabatradeMakeOrderModel $orderModel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderModel(AlibabatradeMakeOrderModel $orderModel) {
        $this->orderModel = $orderModel;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "afterFlowIds", $this->stdResult )) {
    				$this->afterFlowIds = $this->stdResult->{"afterFlowIds"};
    			}
    			    		    				    			    			if (array_key_exists ( "resultCode", $this->stdResult )) {
    				$resultCodeResult=$this->stdResult->{"resultCode"};
    				$this->resultCode = new AlibabatraderesultCodeDef();
    				$this->resultCode->setStdResult ( $resultCodeResult);
    			}
    			    		    				    			    			if (array_key_exists ( "orderModel", $this->stdResult )) {
    				$orderModelResult=$this->stdResult->{"orderModel"};
    				$this->orderModel = new AlibabatradeMakeOrderModel();
    				$this->orderModel->setStdResult ( $orderModelResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "afterFlowIds", $this->arrayResult )) {
    			$this->afterFlowIds = $arrayResult['afterFlowIds'];
    			}
    		    	    			    		    		if (array_key_exists ( "resultCode", $this->arrayResult )) {
    		$resultCodeResult=$arrayResult['resultCode'];
    			    			$this->resultCode = new AlibabatraderesultCodeDef();
    			    			$this->resultCode->$this->setStdResult ( $resultCodeResult);
    		}
    		    	    			    		    		if (array_key_exists ( "orderModel", $this->arrayResult )) {
    		$orderModelResult=$arrayResult['orderModel'];
    			    			$this->orderModel = new AlibabatradeMakeOrderModel();
    			    			$this->orderModel->$this->setStdResult ( $orderModelResult);
    		}
    		    	    		}
 
   
}
?>