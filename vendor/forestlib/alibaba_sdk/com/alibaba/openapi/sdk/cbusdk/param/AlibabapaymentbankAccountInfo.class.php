<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabapaymentbankAccountInfo extends SDKDomain {

       	
    private $accountBranch;
    
        /**
    * @return 
    */
        public function getAccountBranch() {
        return $this->accountBranch;
    }
    
    /**
     * 设置     
     * @param String $accountBranch     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAccountBranch( $accountBranch) {
        $this->accountBranch = $accountBranch;
    }
    
        	
    private $accountName;
    
        /**
    * @return 
    */
        public function getAccountName() {
        return $this->accountName;
    }
    
    /**
     * 设置     
     * @param String $accountName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAccountName( $accountName) {
        $this->accountName = $accountName;
    }
    
        	
    private $accountNo;
    
        /**
    * @return 
    */
        public function getAccountNo() {
        return $this->accountNo;
    }
    
    /**
     * 设置     
     * @param String $accountNo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAccountNo( $accountNo) {
        $this->accountNo = $accountNo;
    }
    
        	
    private $accountPlace;
    
        /**
    * @return 
    */
        public function getAccountPlace() {
        return $this->accountPlace;
    }
    
    /**
     * 设置     
     * @param String $accountPlace     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAccountPlace( $accountPlace) {
        $this->accountPlace = $accountPlace;
    }
    
        	
    private $groupName;
    
        /**
    * @return 
    */
        public function getGroupName() {
        return $this->groupName;
    }
    
    /**
     * 设置     
     * @param String $groupName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGroupName( $groupName) {
        $this->groupName = $groupName;
    }
    
        	
    private $paymentLines;
    
        /**
    * @return 
    */
        public function getPaymentLines() {
        return $this->paymentLines;
    }
    
    /**
     * 设置     
     * @param String $paymentLines     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPaymentLines( $paymentLines) {
        $this->paymentLines = $paymentLines;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "accountBranch", $this->stdResult )) {
    				$this->accountBranch = $this->stdResult->{"accountBranch"};
    			}
    			    		    				    			    			if (array_key_exists ( "accountName", $this->stdResult )) {
    				$this->accountName = $this->stdResult->{"accountName"};
    			}
    			    		    				    			    			if (array_key_exists ( "accountNo", $this->stdResult )) {
    				$this->accountNo = $this->stdResult->{"accountNo"};
    			}
    			    		    				    			    			if (array_key_exists ( "accountPlace", $this->stdResult )) {
    				$this->accountPlace = $this->stdResult->{"accountPlace"};
    			}
    			    		    				    			    			if (array_key_exists ( "groupName", $this->stdResult )) {
    				$this->groupName = $this->stdResult->{"groupName"};
    			}
    			    		    				    			    			if (array_key_exists ( "paymentLines", $this->stdResult )) {
    				$this->paymentLines = $this->stdResult->{"paymentLines"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "accountBranch", $this->arrayResult )) {
    			$this->accountBranch = $arrayResult['accountBranch'];
    			}
    		    	    			    		    			if (array_key_exists ( "accountName", $this->arrayResult )) {
    			$this->accountName = $arrayResult['accountName'];
    			}
    		    	    			    		    			if (array_key_exists ( "accountNo", $this->arrayResult )) {
    			$this->accountNo = $arrayResult['accountNo'];
    			}
    		    	    			    		    			if (array_key_exists ( "accountPlace", $this->arrayResult )) {
    			$this->accountPlace = $arrayResult['accountPlace'];
    			}
    		    	    			    		    			if (array_key_exists ( "groupName", $this->arrayResult )) {
    			$this->groupName = $arrayResult['groupName'];
    			}
    		    	    			    		    			if (array_key_exists ( "paymentLines", $this->arrayResult )) {
    			$this->paymentLines = $arrayResult['paymentLines'];
    			}
    		    	    		}
 
   
}
?>