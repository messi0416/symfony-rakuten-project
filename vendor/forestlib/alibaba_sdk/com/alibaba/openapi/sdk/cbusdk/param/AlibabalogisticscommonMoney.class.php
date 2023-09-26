<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticscommonMoney extends SDKDomain {

       	
    private $amount;
    
        /**
    * @return 金额
    */
        public function getAmount() {
        return $this->amount;
    }
    
    /**
     * 设置金额     
     * @param BigDecimal $amount     
     * 参数示例：<pre>222.33</pre>     
     * 此参数必填     */
    public function setAmount( $amount) {
        $this->amount = $amount;
    }
    
        	
    private $currencyCode;
    
        /**
    * @return 货币代码，ISO 4217 Currency Codes
    */
        public function getCurrencyCode() {
        return $this->currencyCode;
    }
    
    /**
     * 设置货币代码，ISO 4217 Currency Codes     
     * @param String $currencyCode     
     * 参数示例：<pre>CNY</pre>     
     * 此参数必填     */
    public function setCurrencyCode( $currencyCode) {
        $this->currencyCode = $currencyCode;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "amount", $this->stdResult )) {
    				$this->amount = $this->stdResult->{"amount"};
    			}
    			    		    				    			    			if (array_key_exists ( "currencyCode", $this->stdResult )) {
    				$this->currencyCode = $this->stdResult->{"currencyCode"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "amount", $this->arrayResult )) {
    			$this->amount = $arrayResult['amount'];
    			}
    		    	    			    		    			if (array_key_exists ( "currencyCode", $this->arrayResult )) {
    			$this->currencyCode = $arrayResult['currencyCode'];
    			}
    		    	    		}
 
   
}
?>