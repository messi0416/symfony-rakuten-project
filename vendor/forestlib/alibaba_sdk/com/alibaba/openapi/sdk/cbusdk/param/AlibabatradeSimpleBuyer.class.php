<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeSimpleBuyer extends SDKDomain {

       	
    private $memberId;
    
        /**
    * @return 会员memberId
    */
        public function getMemberId() {
        return $this->memberId;
    }
    
    /**
     * 设置会员memberId     
     * @param String $memberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMemberId( $memberId) {
        $this->memberId = $memberId;
    }
    
        	
    private $name;
    
        /**
    * @return 会员名字
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置会员名字     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $userId;
    
        /**
    * @return userId
    */
        public function getUserId() {
        return $this->userId;
    }
    
    /**
     * 设置userId     
     * @param Long $userId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUserId( $userId) {
        $this->userId = $userId;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "memberId", $this->stdResult )) {
    				$this->memberId = $this->stdResult->{"memberId"};
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
				    		    			if (array_key_exists ( "memberId", $this->arrayResult )) {
    			$this->memberId = $arrayResult['memberId'];
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