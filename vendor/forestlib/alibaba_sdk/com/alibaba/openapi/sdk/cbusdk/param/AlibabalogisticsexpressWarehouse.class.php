<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsexpressWarehouse extends SDKDomain {

       	
    private $phone;
    
        /**
    * @return 联系人电话
    */
        public function getPhone() {
        return $this->phone;
    }
    
    /**
     * 设置联系人电话     
     * @param String $phone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhone( $phone) {
        $this->phone = $phone;
    }
    
        	
    private $consigneeName;
    
        /**
    * @return 联系人名称
    */
        public function getConsigneeName() {
        return $this->consigneeName;
    }
    
    /**
     * 设置联系人名称     
     * @param String $consigneeName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setConsigneeName( $consigneeName) {
        $this->consigneeName = $consigneeName;
    }
    
        	
    private $fax;
    
        /**
    * @return 联系人传真
    */
        public function getFax() {
        return $this->fax;
    }
    
    /**
     * 设置联系人传真     
     * @param String $fax     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFax( $fax) {
        $this->fax = $fax;
    }
    
        	
    private $memo;
    
        /**
    * @return 备注
    */
        public function getMemo() {
        return $this->memo;
    }
    
    /**
     * 设置备注     
     * @param String $memo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMemo( $memo) {
        $this->memo = $memo;
    }
    
        	
    private $address;
    
        /**
    * @return 仓库地址
    */
        public function getAddress() {
        return $this->address;
    }
    
    /**
     * 设置仓库地址     
     * @param String $address     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->address = $address;
    }
    
        	
    private $serviceTime;
    
        /**
    * @return 服务时间
    */
        public function getServiceTime() {
        return $this->serviceTime;
    }
    
    /**
     * 设置服务时间     
     * @param String $serviceTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setServiceTime( $serviceTime) {
        $this->serviceTime = $serviceTime;
    }
    
        	
    private $name;
    
        /**
    * @return 仓库名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置仓库名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $servicePhone;
    
        /**
    * @return 服务电话
    */
        public function getServicePhone() {
        return $this->servicePhone;
    }
    
    /**
     * 设置服务电话     
     * @param String $servicePhone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setServicePhone( $servicePhone) {
        $this->servicePhone = $servicePhone;
    }
    
        	
    private $prompt;
    
        /**
    * @return 提示信息
    */
        public function getPrompt() {
        return $this->prompt;
    }
    
    /**
     * 设置提示信息     
     * @param String $prompt     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPrompt( $prompt) {
        $this->prompt = $prompt;
    }
    
        	
    private $code;
    
        /**
    * @return 仓库代码
    */
        public function getCode() {
        return $this->code;
    }
    
    /**
     * 设置仓库代码     
     * @param String $code     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCode( $code) {
        $this->code = $code;
    }
    
        	
    private $providerCode;
    
        /**
    * @return 服务商代码
    */
        public function getProviderCode() {
        return $this->providerCode;
    }
    
    /**
     * 设置服务商代码     
     * @param Long $providerCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProviderCode( $providerCode) {
        $this->providerCode = $providerCode;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "phone", $this->stdResult )) {
    				$this->phone = $this->stdResult->{"phone"};
    			}
    			    		    				    			    			if (array_key_exists ( "consigneeName", $this->stdResult )) {
    				$this->consigneeName = $this->stdResult->{"consigneeName"};
    			}
    			    		    				    			    			if (array_key_exists ( "fax", $this->stdResult )) {
    				$this->fax = $this->stdResult->{"fax"};
    			}
    			    		    				    			    			if (array_key_exists ( "memo", $this->stdResult )) {
    				$this->memo = $this->stdResult->{"memo"};
    			}
    			    		    				    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "serviceTime", $this->stdResult )) {
    				$this->serviceTime = $this->stdResult->{"serviceTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "servicePhone", $this->stdResult )) {
    				$this->servicePhone = $this->stdResult->{"servicePhone"};
    			}
    			    		    				    			    			if (array_key_exists ( "prompt", $this->stdResult )) {
    				$this->prompt = $this->stdResult->{"prompt"};
    			}
    			    		    				    			    			if (array_key_exists ( "code", $this->stdResult )) {
    				$this->code = $this->stdResult->{"code"};
    			}
    			    		    				    			    			if (array_key_exists ( "providerCode", $this->stdResult )) {
    				$this->providerCode = $this->stdResult->{"providerCode"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "phone", $this->arrayResult )) {
    			$this->phone = $arrayResult['phone'];
    			}
    		    	    			    		    			if (array_key_exists ( "consigneeName", $this->arrayResult )) {
    			$this->consigneeName = $arrayResult['consigneeName'];
    			}
    		    	    			    		    			if (array_key_exists ( "fax", $this->arrayResult )) {
    			$this->fax = $arrayResult['fax'];
    			}
    		    	    			    		    			if (array_key_exists ( "memo", $this->arrayResult )) {
    			$this->memo = $arrayResult['memo'];
    			}
    		    	    			    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "serviceTime", $this->arrayResult )) {
    			$this->serviceTime = $arrayResult['serviceTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "servicePhone", $this->arrayResult )) {
    			$this->servicePhone = $arrayResult['servicePhone'];
    			}
    		    	    			    		    			if (array_key_exists ( "prompt", $this->arrayResult )) {
    			$this->prompt = $arrayResult['prompt'];
    			}
    		    	    			    		    			if (array_key_exists ( "code", $this->arrayResult )) {
    			$this->code = $arrayResult['code'];
    			}
    		    	    			    		    			if (array_key_exists ( "providerCode", $this->arrayResult )) {
    			$this->providerCode = $arrayResult['providerCode'];
    			}
    		    	    		}
 
   
}
?>