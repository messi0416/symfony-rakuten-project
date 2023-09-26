<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtrademodelTradeTermsInfo extends SDKDomain {

       	
    private $payStatus;
    
        /**
    * @return 支付状态。国际站：WAIT_PAY(未支付),PAYER_PAID(已完成支付),PART_SUCCESS(部分支付成功),PAY_SUCCESS(支付成功),CLOSED(风控关闭),CANCELLED(支付撤销),SUCCESS(成功),FAIL(失败)
    */
        public function getPayStatus() {
        return $this->payStatus;
    }
    
    /**
     * 设置支付状态。国际站：WAIT_PAY(未支付),PAYER_PAID(已完成支付),PART_SUCCESS(部分支付成功),PAY_SUCCESS(支付成功),CLOSED(风控关闭),CANCELLED(支付撤销),SUCCESS(成功),FAIL(失败)     
     * @param String $payStatus     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayStatus( $payStatus) {
        $this->payStatus = $payStatus;
    }
    
        	
    private $payTime;
    
        /**
    * @return 完成阶段支付时间
    */
        public function getPayTime() {
        return $this->payTime;
    }
    
    /**
     * 设置完成阶段支付时间     
     * @param Date $payTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayTime( $payTime) {
        $this->payTime = $payTime;
    }
    
        	
    private $payWay;
    
        /**
    * @return 支付方式。国际站：ECL(融资支付),CC(信用卡),TT(线下TT),ACH(echecking支付)
    */
        public function getPayWay() {
        return $this->payWay;
    }
    
    /**
     * 设置支付方式。国际站：ECL(融资支付),CC(信用卡),TT(线下TT),ACH(echecking支付)     
     * @param String $payWay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayWay( $payWay) {
        $this->payWay = $payWay;
    }
    
        	
    private $phasAmount;
    
        /**
    * @return 阶段金额
    */
        public function getPhasAmount() {
        return $this->phasAmount;
    }
    
    /**
     * 设置阶段金额     
     * @param BigDecimal $phasAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhasAmount( $phasAmount) {
        $this->phasAmount = $phasAmount;
    }
    
        	
    private $phase;
    
        /**
    * @return 阶段
    */
        public function getPhase() {
        return $this->phase;
    }
    
    /**
     * 设置阶段     
     * @param Long $phase     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhase( $phase) {
        $this->phase = $phase;
    }
    
        	
    private $phaseCondition;
    
        /**
    * @return 阶段条件，1688无此内容
    */
        public function getPhaseCondition() {
        return $this->phaseCondition;
    }
    
    /**
     * 设置阶段条件，1688无此内容     
     * @param String $phaseCondition     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhaseCondition( $phaseCondition) {
        $this->phaseCondition = $phaseCondition;
    }
    
        	
    private $phaseDate;
    
        /**
    * @return 阶段时间，1688无此内容
    */
        public function getPhaseDate() {
        return $this->phaseDate;
    }
    
    /**
     * 设置阶段时间，1688无此内容     
     * @param String $phaseDate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhaseDate( $phaseDate) {
        $this->phaseDate = $phaseDate;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "payStatus", $this->stdResult )) {
    				$this->payStatus = $this->stdResult->{"payStatus"};
    			}
    			    		    				    			    			if (array_key_exists ( "payTime", $this->stdResult )) {
    				$this->payTime = $this->stdResult->{"payTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "payWay", $this->stdResult )) {
    				$this->payWay = $this->stdResult->{"payWay"};
    			}
    			    		    				    			    			if (array_key_exists ( "phasAmount", $this->stdResult )) {
    				$this->phasAmount = $this->stdResult->{"phasAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "phase", $this->stdResult )) {
    				$this->phase = $this->stdResult->{"phase"};
    			}
    			    		    				    			    			if (array_key_exists ( "phaseCondition", $this->stdResult )) {
    				$this->phaseCondition = $this->stdResult->{"phaseCondition"};
    			}
    			    		    				    			    			if (array_key_exists ( "phaseDate", $this->stdResult )) {
    				$this->phaseDate = $this->stdResult->{"phaseDate"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "payStatus", $this->arrayResult )) {
    			$this->payStatus = $arrayResult['payStatus'];
    			}
    		    	    			    		    			if (array_key_exists ( "payTime", $this->arrayResult )) {
    			$this->payTime = $arrayResult['payTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "payWay", $this->arrayResult )) {
    			$this->payWay = $arrayResult['payWay'];
    			}
    		    	    			    		    			if (array_key_exists ( "phasAmount", $this->arrayResult )) {
    			$this->phasAmount = $arrayResult['phasAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "phase", $this->arrayResult )) {
    			$this->phase = $arrayResult['phase'];
    			}
    		    	    			    		    			if (array_key_exists ( "phaseCondition", $this->arrayResult )) {
    			$this->phaseCondition = $arrayResult['phaseCondition'];
    			}
    		    	    			    		    			if (array_key_exists ( "phaseDate", $this->arrayResult )) {
    			$this->phaseDate = $arrayResult['phaseDate'];
    			}
    		    	    		}
 
   
}
?>