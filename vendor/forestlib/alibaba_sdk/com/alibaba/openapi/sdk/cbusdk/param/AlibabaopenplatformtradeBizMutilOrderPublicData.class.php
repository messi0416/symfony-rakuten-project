<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtradeBizMutilOrderPublicData extends SDKDomain {

       	
    private $bizType;
    
        /**
    * @return 页面级别的业务场景. 比如: 零售通(完整独立的订购页面)
    */
        public function getBizType() {
        return $this->bizType;
    }
    
    /**
     * 设置页面级别的业务场景. 比如: 零售通(完整独立的订购页面)     
     * @param String $bizType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBizType( $bizType) {
        $this->bizType = $bizType;
    }
    
        	
    private $freeCarriageMinProductAmount;
    
        /**
    * @return 零售通全场满包邮产品总金额
    */
        public function getFreeCarriageMinProductAmount() {
        return $this->freeCarriageMinProductAmount;
    }
    
    /**
     * 设置零售通全场满包邮产品总金额     
     * @param Long $freeCarriageMinProductAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFreeCarriageMinProductAmount( $freeCarriageMinProductAmount) {
        $this->freeCarriageMinProductAmount = $freeCarriageMinProductAmount;
    }
    
        	
    private $sumPaymentLimitMin;
    
        /**
    * @return 页面级别的显示条件, 所有订单总金额之和的最小值。单位：分
    */
        public function getSumPaymentLimitMin() {
        return $this->sumPaymentLimitMin;
    }
    
    /**
     * 设置页面级别的显示条件, 所有订单总金额之和的最小值。单位：分     
     * @param Long $sumPaymentLimitMin     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSumPaymentLimitMin( $sumPaymentLimitMin) {
        $this->sumPaymentLimitMin = $sumPaymentLimitMin;
    }
    
        	
    private $supportFreeCarriage;
    
        /**
    * @return 是否支持零售通全场满包邮
    */
        public function getSupportFreeCarriage() {
        return $this->supportFreeCarriage;
    }
    
    /**
     * 设置是否支持零售通全场满包邮     
     * @param Boolean $supportFreeCarriage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupportFreeCarriage( $supportFreeCarriage) {
        $this->supportFreeCarriage = $supportFreeCarriage;
    }
    
        	
    private $supportInvoice;
    
        /**
    * @return 当页面所有的订单都支持发票时=true，页面中有一个订单块不支持发票 = false
    */
        public function getSupportInvoice() {
        return $this->supportInvoice;
    }
    
    /**
     * 设置当页面所有的订单都支持发票时=true，页面中有一个订单块不支持发票 = false     
     * @param Boolean $supportInvoice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupportInvoice( $supportInvoice) {
        $this->supportInvoice = $supportInvoice;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "bizType", $this->stdResult )) {
    				$this->bizType = $this->stdResult->{"bizType"};
    			}
    			    		    				    			    			if (array_key_exists ( "freeCarriageMinProductAmount", $this->stdResult )) {
    				$this->freeCarriageMinProductAmount = $this->stdResult->{"freeCarriageMinProductAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "sumPaymentLimitMin", $this->stdResult )) {
    				$this->sumPaymentLimitMin = $this->stdResult->{"sumPaymentLimitMin"};
    			}
    			    		    				    			    			if (array_key_exists ( "supportFreeCarriage", $this->stdResult )) {
    				$this->supportFreeCarriage = $this->stdResult->{"supportFreeCarriage"};
    			}
    			    		    				    			    			if (array_key_exists ( "supportInvoice", $this->stdResult )) {
    				$this->supportInvoice = $this->stdResult->{"supportInvoice"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "bizType", $this->arrayResult )) {
    			$this->bizType = $arrayResult['bizType'];
    			}
    		    	    			    		    			if (array_key_exists ( "freeCarriageMinProductAmount", $this->arrayResult )) {
    			$this->freeCarriageMinProductAmount = $arrayResult['freeCarriageMinProductAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "sumPaymentLimitMin", $this->arrayResult )) {
    			$this->sumPaymentLimitMin = $arrayResult['sumPaymentLimitMin'];
    			}
    		    	    			    		    			if (array_key_exists ( "supportFreeCarriage", $this->arrayResult )) {
    			$this->supportFreeCarriage = $arrayResult['supportFreeCarriage'];
    			}
    		    	    			    		    			if (array_key_exists ( "supportInvoice", $this->arrayResult )) {
    			$this->supportInvoice = $arrayResult['supportInvoice'];
    			}
    		    	    		}
 
   
}
?>