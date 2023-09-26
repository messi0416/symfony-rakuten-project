<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductInfo.class.php');

class AlibabaProductGetResult {

        	
    private $productInfo;
    
        /**
    * @return 商品详细信息
    */
        public function getProductInfo() {
        return $this->productInfo;
    }
    
    /**
     * 设置商品详细信息     
     * @param AlibabaproductProductInfo $productInfo     
          
     * 此参数必填     */
    public function setProductInfo(AlibabaproductProductInfo $productInfo) {
        $this->productInfo = $productInfo;
    }
    
        	
    private $createTime;
    
        /**
    * @return 创建时间
    */
        public function getCreateTime() {
        return $this->createTime;
    }
    
    /**
     * 设置创建时间     
     * @param Date $createTime     
          
     * 此参数必填     */
    public function setCreateTime( $createTime) {
        $this->createTime = $createTime;
    }
    
        	
    private $lastUpdateTime;
    
        /**
    * @return 最后修改时间
    */
        public function getLastUpdateTime() {
        return $this->lastUpdateTime;
    }
    
    /**
     * 设置最后修改时间     
     * @param Date $lastUpdateTime     
          
     * 此参数必填     */
    public function setLastUpdateTime( $lastUpdateTime) {
        $this->lastUpdateTime = $lastUpdateTime;
    }
    
        	
    private $lastRepostTime;
    
        /**
    * @return 最近重发时间，国际站无此信息
    */
        public function getLastRepostTime() {
        return $this->lastRepostTime;
    }
    
    /**
     * 设置最近重发时间，国际站无此信息     
     * @param Date $lastRepostTime     
          
     * 此参数必填     */
    public function setLastRepostTime( $lastRepostTime) {
        $this->lastRepostTime = $lastRepostTime;
    }
    
        	
    private $approvedTime;
    
        /**
    * @return 审核通过时间，国际站无此信息
    */
        public function getApprovedTime() {
        return $this->approvedTime;
    }
    
    /**
     * 设置审核通过时间，国际站无此信息     
     * @param Date $approvedTime     
          
     * 此参数必填     */
    public function setApprovedTime( $approvedTime) {
        $this->approvedTime = $approvedTime;
    }
    
        	
    private $expireTime;
    
        /**
    * @return 过期时间，国际站无此信息
    */
        public function getExpireTime() {
        return $this->expireTime;
    }
    
    /**
     * 设置过期时间，国际站无此信息     
     * @param Date $expireTime     
          
     * 此参数必填     */
    public function setExpireTime( $expireTime) {
        $this->expireTime = $expireTime;
    }
    
        	
    private $errMsg;
    
        /**
    * @return 返回错误信息
    */
        public function getErrMsg() {
        return $this->errMsg;
    }
    
    /**
     * 设置返回错误信息     
     * @param String $errMsg     
          
     * 此参数必填     */
    public function setErrMsg( $errMsg) {
        $this->errMsg = $errMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "productInfo", $this->stdResult )) {
    				$productInfoResult=$this->stdResult->{"productInfo"};
    				$this->productInfo = new AlibabaproductProductInfo();
    				$this->productInfo->setStdResult ( $productInfoResult);
    			}
    			    		    				    			    			if (array_key_exists ( "createTime", $this->stdResult )) {
    				$this->createTime = $this->stdResult->{"createTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "lastUpdateTime", $this->stdResult )) {
    				$this->lastUpdateTime = $this->stdResult->{"lastUpdateTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "lastRepostTime", $this->stdResult )) {
    				$this->lastRepostTime = $this->stdResult->{"lastRepostTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "approvedTime", $this->stdResult )) {
    				$this->approvedTime = $this->stdResult->{"approvedTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "expireTime", $this->stdResult )) {
    				$this->expireTime = $this->stdResult->{"expireTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "errMsg", $this->stdResult )) {
    				$this->errMsg = $this->stdResult->{"errMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "productInfo", $this->arrayResult )) {
    		$productInfoResult=$arrayResult['productInfo'];
    			    			$this->productInfo = new AlibabaproductProductInfo();
    			    			$this->productInfo->setStdResult ( $productInfoResult);
    		}
    		    	    			    		    			if (array_key_exists ( "createTime", $this->arrayResult )) {
    			$this->createTime = $arrayResult['createTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "lastUpdateTime", $this->arrayResult )) {
    			$this->lastUpdateTime = $arrayResult['lastUpdateTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "lastRepostTime", $this->arrayResult )) {
    			$this->lastRepostTime = $arrayResult['lastRepostTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "approvedTime", $this->arrayResult )) {
    			$this->approvedTime = $arrayResult['approvedTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "expireTime", $this->arrayResult )) {
    			$this->expireTime = $arrayResult['expireTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "errMsg", $this->arrayResult )) {
    			$this->errMsg = $arrayResult['errMsg'];
    			}
    		    	    		}

}
?>