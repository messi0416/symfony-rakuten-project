<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeSimpleSeller extends SDKDomain {

       	
    private $companyName;
    
        /**
    * @return 
    */
        public function getCompanyName() {
        return $this->companyName;
    }
    
    /**
     * 设置     
     * @param String $companyName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCompanyName( $companyName) {
        $this->companyName = $companyName;
    }
    
        	
    private $isFree;
    
        /**
    * @return 
    */
        public function getIsFree() {
        return $this->isFree;
    }
    
    /**
     * 设置     
     * @param Boolean $isFree     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsFree( $isFree) {
        $this->isFree = $isFree;
    }
    
        	
    private $isGuaranteeSupport;
    
        /**
    * @return 
    */
        public function getIsGuaranteeSupport() {
        return $this->isGuaranteeSupport;
    }
    
    /**
     * 设置     
     * @param Boolean $isGuaranteeSupport     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsGuaranteeSupport( $isGuaranteeSupport) {
        $this->isGuaranteeSupport = $isGuaranteeSupport;
    }
    
        	
    private $isTP;
    
        /**
    * @return 
    */
        public function getIsTP() {
        return $this->isTP;
    }
    
    /**
     * 设置     
     * @param Boolean $isTP     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsTP( $isTP) {
        $this->isTP = $isTP;
    }
    
        	
    private $memberId;
    
        /**
    * @return 
    */
        public function getMemberId() {
        return $this->memberId;
    }
    
    /**
     * 设置     
     * @param String $memberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMemberId( $memberId) {
        $this->memberId = $memberId;
    }
    
        	
    private $mobile;
    
        /**
    * @return 
    */
        public function getMobile() {
        return $this->mobile;
    }
    
    /**
     * 设置     
     * @param String $mobile     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMobile( $mobile) {
        $this->mobile = $mobile;
    }
    
        	
    private $name;
    
        /**
    * @return 
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $userId;
    
        /**
    * @return 
    */
        public function getUserId() {
        return $this->userId;
    }
    
    /**
     * 设置     
     * @param Long $userId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUserId( $userId) {
        $this->userId = $userId;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "companyName", $this->stdResult )) {
    				$this->companyName = $this->stdResult->{"companyName"};
    			}
    			    		    				    			    			if (array_key_exists ( "isFree", $this->stdResult )) {
    				$this->isFree = $this->stdResult->{"isFree"};
    			}
    			    		    				    			    			if (array_key_exists ( "isGuaranteeSupport", $this->stdResult )) {
    				$this->isGuaranteeSupport = $this->stdResult->{"isGuaranteeSupport"};
    			}
    			    		    				    			    			if (array_key_exists ( "isTP", $this->stdResult )) {
    				$this->isTP = $this->stdResult->{"isTP"};
    			}
    			    		    				    			    			if (array_key_exists ( "memberId", $this->stdResult )) {
    				$this->memberId = $this->stdResult->{"memberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobile", $this->stdResult )) {
    				$this->mobile = $this->stdResult->{"mobile"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "userId", $this->stdResult )) {
    				$this->userId = $this->stdResult->{"userId"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "companyName", $this->arrayResult )) {
    			$this->companyName = $arrayResult['companyName'];
    			}
    		    	    			    		    			if (array_key_exists ( "isFree", $this->arrayResult )) {
    			$this->isFree = $arrayResult['isFree'];
    			}
    		    	    			    		    			if (array_key_exists ( "isGuaranteeSupport", $this->arrayResult )) {
    			$this->isGuaranteeSupport = $arrayResult['isGuaranteeSupport'];
    			}
    		    	    			    		    			if (array_key_exists ( "isTP", $this->arrayResult )) {
    			$this->isTP = $arrayResult['isTP'];
    			}
    		    	    			    		    			if (array_key_exists ( "memberId", $this->arrayResult )) {
    			$this->memberId = $arrayResult['memberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobile", $this->arrayResult )) {
    			$this->mobile = $arrayResult['mobile'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "userId", $this->arrayResult )) {
    			$this->userId = $arrayResult['userId'];
    			}
    		    	    		}
 
   
}
?>