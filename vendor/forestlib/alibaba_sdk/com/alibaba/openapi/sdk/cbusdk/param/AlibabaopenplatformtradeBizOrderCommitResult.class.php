<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizSimpleOrderModel.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeResultCodeDef.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradecomKeyValuePair.class.php');

class AlibabaopenplatformtradeBizOrderCommitResult extends SDKDomain {

       	
    private $afterFlowIds;
    
        /**
    * @return 需要后续执行的流程标示列表。是一个List<String>
    */
        public function getAfterFlowIds() {
        return $this->afterFlowIds;
    }
    
    /**
     * 设置需要后续执行的流程标示列表。是一个List<String>     
     * @param array include @see String[] $afterFlowIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAfterFlowIds( $afterFlowIds) {
        $this->afterFlowIds = $afterFlowIds;
    }
    
        	
    private $customOffer;
    
        /**
    * @return 是否加工定制下单
    */
        public function getCustomOffer() {
        return $this->customOffer;
    }
    
    /**
     * 设置是否加工定制下单     
     * @param Boolean $customOffer     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCustomOffer( $customOffer) {
        $this->customOffer = $customOffer;
    }
    
        	
    private $orderAmount;
    
        /**
    * @return 订单实付款金额，单位为分
    */
        public function getOrderAmount() {
        return $this->orderAmount;
    }
    
    /**
     * 设置订单实付款金额，单位为分     
     * @param Long $orderAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderAmount( $orderAmount) {
        $this->orderAmount = $orderAmount;
    }
    
        	
    private $orderId;
    
        /**
    * @return 下单成功后的订单id.
    */
        public function getOrderId() {
        return $this->orderId;
    }
    
    /**
     * 设置下单成功后的订单id.     
     * @param Long $orderId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->orderId = $orderId;
    }
    
        	
    private $outBizEntryIds;
    
        /**
    * @return 外部业务关联依赖的明细id. 比如: 代销订单的代销id列表。是一个List<String>
    */
        public function getOutBizEntryIds() {
        return $this->outBizEntryIds;
    }
    
    /**
     * 设置外部业务关联依赖的明细id. 比如: 代销订单的代销id列表。是一个List<String>     
     * @param array include @see String[] $outBizEntryIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOutBizEntryIds( $outBizEntryIds) {
        $this->outBizEntryIds = $outBizEntryIds;
    }
    
        	
    private $payChannel;
    
        /**
    * @return 支付渠道
    */
        public function getPayChannel() {
        return $this->payChannel;
    }
    
    /**
     * 设置支付渠道     
     * @param String $payChannel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayChannel( $payChannel) {
        $this->payChannel = $payChannel;
    }
    
        	
    private $success;
    
        /**
    * @return 是否成功
    */
        public function getSuccess() {
        return $this->success;
    }
    
    /**
     * 设置是否成功     
     * @param Boolean $success     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSuccess( $success) {
        $this->success = $success;
    }
    
        	
    private $orderModel;
    
        /**
    * @return 下单成功后,会将订单简易模型返回
    */
        public function getOrderModel() {
        return $this->orderModel;
    }
    
    /**
     * 设置下单成功后,会将订单简易模型返回     
     * @param AlibabaopenplatformtradeBizSimpleOrderModel $orderModel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderModel(AlibabaopenplatformtradeBizSimpleOrderModel $orderModel) {
        $this->orderModel = $orderModel;
    }
    
        	
    private $resultCode;
    
        /**
    * @return 结果码
    */
        public function getResultCode() {
        return $this->resultCode;
    }
    
    /**
     * 设置结果码     
     * @param AlibabaopenplatformtradeResultCodeDef $resultCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResultCode(AlibabaopenplatformtradeResultCodeDef $resultCode) {
        $this->resultCode = $resultCode;
    }
    
        	
    private $extModels;
    
        /**
    * @return 扩展属性
    */
        public function getExtModels() {
        return $this->extModels;
    }
    
    /**
     * 设置扩展属性     
     * @param array include @see AlibabatradecomKeyValuePair[] $extModels     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExtModels(AlibabatradecomKeyValuePair $extModels) {
        $this->extModels = $extModels;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "afterFlowIds", $this->stdResult )) {
    				$this->afterFlowIds = $this->stdResult->{"afterFlowIds"};
    			}
    			    		    				    			    			if (array_key_exists ( "customOffer", $this->stdResult )) {
    				$this->customOffer = $this->stdResult->{"customOffer"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderAmount", $this->stdResult )) {
    				$this->orderAmount = $this->stdResult->{"orderAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderId", $this->stdResult )) {
    				$this->orderId = $this->stdResult->{"orderId"};
    			}
    			    		    				    			    			if (array_key_exists ( "outBizEntryIds", $this->stdResult )) {
    				$this->outBizEntryIds = $this->stdResult->{"outBizEntryIds"};
    			}
    			    		    				    			    			if (array_key_exists ( "payChannel", $this->stdResult )) {
    				$this->payChannel = $this->stdResult->{"payChannel"};
    			}
    			    		    				    			    			if (array_key_exists ( "success", $this->stdResult )) {
    				$this->success = $this->stdResult->{"success"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderModel", $this->stdResult )) {
    				$orderModelResult=$this->stdResult->{"orderModel"};
    				$this->orderModel = new AlibabaopenplatformtradeBizSimpleOrderModel();
    				$this->orderModel->setStdResult ( $orderModelResult);
    			}
    			    		    				    			    			if (array_key_exists ( "resultCode", $this->stdResult )) {
    				$resultCodeResult=$this->stdResult->{"resultCode"};
    				$this->resultCode = new AlibabaopenplatformtradeResultCodeDef();
    				$this->resultCode->setStdResult ( $resultCodeResult);
    			}
    			    		    				    			    			if (array_key_exists ( "extModels", $this->stdResult )) {
    			$extModelsResult=$this->stdResult->{"extModels"};
    				$object = json_decode ( json_encode ( $extModelsResult ), true );
					$this->extModels = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradecomKeyValuePairResult=new AlibabatradecomKeyValuePair();
						$AlibabatradecomKeyValuePairResult->setArrayResult($arrayobject );
						$this->extModels [$i] = $AlibabatradecomKeyValuePairResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "afterFlowIds", $this->arrayResult )) {
    			$this->afterFlowIds = $arrayResult['afterFlowIds'];
    			}
    		    	    			    		    			if (array_key_exists ( "customOffer", $this->arrayResult )) {
    			$this->customOffer = $arrayResult['customOffer'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderAmount", $this->arrayResult )) {
    			$this->orderAmount = $arrayResult['orderAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderId", $this->arrayResult )) {
    			$this->orderId = $arrayResult['orderId'];
    			}
    		    	    			    		    			if (array_key_exists ( "outBizEntryIds", $this->arrayResult )) {
    			$this->outBizEntryIds = $arrayResult['outBizEntryIds'];
    			}
    		    	    			    		    			if (array_key_exists ( "payChannel", $this->arrayResult )) {
    			$this->payChannel = $arrayResult['payChannel'];
    			}
    		    	    			    		    			if (array_key_exists ( "success", $this->arrayResult )) {
    			$this->success = $arrayResult['success'];
    			}
    		    	    			    		    		if (array_key_exists ( "orderModel", $this->arrayResult )) {
    		$orderModelResult=$arrayResult['orderModel'];
    			    			$this->orderModel = new AlibabaopenplatformtradeBizSimpleOrderModel();
    			    			$this->orderModel->$this->setStdResult ( $orderModelResult);
    		}
    		    	    			    		    		if (array_key_exists ( "resultCode", $this->arrayResult )) {
    		$resultCodeResult=$arrayResult['resultCode'];
    			    			$this->resultCode = new AlibabaopenplatformtradeResultCodeDef();
    			    			$this->resultCode->$this->setStdResult ( $resultCodeResult);
    		}
    		    	    			    		    		if (array_key_exists ( "extModels", $this->arrayResult )) {
    		$extModelsResult=$arrayResult['extModels'];
    			$this->extModels = AlibabatradecomKeyValuePair();
    			$this->extModels->$this->setStdResult ( $extModelsResult);
    		}
    		    	    		}
 
   
}
?>