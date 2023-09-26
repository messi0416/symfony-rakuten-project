<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabapaymentbankAccountInfo.class.php');

class AlibabapaymentChannelPreparePayResult extends SDKDomain {

       	
    private $paymentCode;
    
        /**
    * @return 
    */
        public function getPaymentCode() {
        return $this->paymentCode;
    }
    
    /**
     * 设置     
     * @param String $paymentCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPaymentCode( $paymentCode) {
        $this->paymentCode = $paymentCode;
    }
    
        	
    private $sellerBankAccountInfo;
    
        /**
    * @return 
    */
        public function getSellerBankAccountInfo() {
        return $this->sellerBankAccountInfo;
    }
    
    /**
     * 设置     
     * @param AlibabapaymentbankAccountInfo $sellerBankAccountInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerBankAccountInfo(AlibabapaymentbankAccountInfo $sellerBankAccountInfo) {
        $this->sellerBankAccountInfo = $sellerBankAccountInfo;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "paymentCode", $this->stdResult )) {
    				$this->paymentCode = $this->stdResult->{"paymentCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerBankAccountInfo", $this->stdResult )) {
    				$sellerBankAccountInfoResult=$this->stdResult->{"sellerBankAccountInfo"};
    				$this->sellerBankAccountInfo = new AlibabapaymentbankAccountInfo();
    				$this->sellerBankAccountInfo->setStdResult ( $sellerBankAccountInfoResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "paymentCode", $this->arrayResult )) {
    			$this->paymentCode = $arrayResult['paymentCode'];
    			}
    		    	    			    		    		if (array_key_exists ( "sellerBankAccountInfo", $this->arrayResult )) {
    		$sellerBankAccountInfoResult=$arrayResult['sellerBankAccountInfo'];
    			    			$this->sellerBankAccountInfo = new AlibabapaymentbankAccountInfo();
    			    			$this->sellerBankAccountInfo->$this->setStdResult ( $sellerBankAccountInfoResult);
    		}
    		    	    		}
 
   
}
?>