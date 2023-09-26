<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeReceiveAddress extends SDKDomain {

       	
    private $address;
    
        /**
    * @return 
    */
        public function getAddress() {
        return $this->address;
    }
    
    /**
     * 设置     
     * @param String $address     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->address = $address;
    }
    
        	
    private $addressCode;
    
        /**
    * @return 
    */
        public function getAddressCode() {
        return $this->addressCode;
    }
    
    /**
     * 设置     
     * @param String $addressCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressCode( $addressCode) {
        $this->addressCode = $addressCode;
    }
    
        	
    private $addressCodeText;
    
        /**
    * @return 
    */
        public function getAddressCodeText() {
        return $this->addressCodeText;
    }
    
    /**
     * 设置     
     * @param String $addressCodeText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressCodeText( $addressCodeText) {
        $this->addressCodeText = $addressCodeText;
    }
    
        	
    private $addressId;
    
        /**
    * @return 
    */
        public function getAddressId() {
        return $this->addressId;
    }
    
    /**
     * 设置     
     * @param Long $addressId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressId( $addressId) {
        $this->addressId = $addressId;
    }
    
        	
    private $bizType;
    
        /**
    * @return 
    */
        public function getBizType() {
        return $this->bizType;
    }
    
    /**
     * 设置     
     * @param String $bizType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBizType( $bizType) {
        $this->bizType = $bizType;
    }
    
        	
    private $isDefault;
    
        /**
    * @return 
    */
        public function getIsDefault() {
        return $this->isDefault;
    }
    
    /**
     * 设置     
     * @param Boolean $isDefault     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsDefault( $isDefault) {
        $this->isDefault = $isDefault;
    }
    
        	
    private $fullName;
    
        /**
    * @return 
    */
        public function getFullName() {
        return $this->fullName;
    }
    
    /**
     * 设置     
     * @param String $fullName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFullName( $fullName) {
        $this->fullName = $fullName;
    }
    
        	
    private $latest;
    
        /**
    * @return 
    */
        public function getLatest() {
        return $this->latest;
    }
    
    /**
     * 设置     
     * @param Boolean $latest     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLatest( $latest) {
        $this->latest = $latest;
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
    
        	
    private $phone;
    
        /**
    * @return 
    */
        public function getPhone() {
        return $this->phone;
    }
    
    /**
     * 设置     
     * @param String $phone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhone( $phone) {
        $this->phone = $phone;
    }
    
        	
    private $postCode;
    
        /**
    * @return 
    */
        public function getPostCode() {
        return $this->postCode;
    }
    
    /**
     * 设置     
     * @param String $postCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPostCode( $postCode) {
        $this->postCode = $postCode;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "addressCode", $this->stdResult )) {
    				$this->addressCode = $this->stdResult->{"addressCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "addressCodeText", $this->stdResult )) {
    				$this->addressCodeText = $this->stdResult->{"addressCodeText"};
    			}
    			    		    				    			    			if (array_key_exists ( "addressId", $this->stdResult )) {
    				$this->addressId = $this->stdResult->{"addressId"};
    			}
    			    		    				    			    			if (array_key_exists ( "bizType", $this->stdResult )) {
    				$this->bizType = $this->stdResult->{"bizType"};
    			}
    			    		    				    			    			if (array_key_exists ( "isDefault", $this->stdResult )) {
    				$this->isDefault = $this->stdResult->{"isDefault"};
    			}
    			    		    				    			    			if (array_key_exists ( "fullName", $this->stdResult )) {
    				$this->fullName = $this->stdResult->{"fullName"};
    			}
    			    		    				    			    			if (array_key_exists ( "latest", $this->stdResult )) {
    				$this->latest = $this->stdResult->{"latest"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobile", $this->stdResult )) {
    				$this->mobile = $this->stdResult->{"mobile"};
    			}
    			    		    				    			    			if (array_key_exists ( "phone", $this->stdResult )) {
    				$this->phone = $this->stdResult->{"phone"};
    			}
    			    		    				    			    			if (array_key_exists ( "postCode", $this->stdResult )) {
    				$this->postCode = $this->stdResult->{"postCode"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "addressCode", $this->arrayResult )) {
    			$this->addressCode = $arrayResult['addressCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "addressCodeText", $this->arrayResult )) {
    			$this->addressCodeText = $arrayResult['addressCodeText'];
    			}
    		    	    			    		    			if (array_key_exists ( "addressId", $this->arrayResult )) {
    			$this->addressId = $arrayResult['addressId'];
    			}
    		    	    			    		    			if (array_key_exists ( "bizType", $this->arrayResult )) {
    			$this->bizType = $arrayResult['bizType'];
    			}
    		    	    			    		    			if (array_key_exists ( "isDefault", $this->arrayResult )) {
    			$this->isDefault = $arrayResult['isDefault'];
    			}
    		    	    			    		    			if (array_key_exists ( "fullName", $this->arrayResult )) {
    			$this->fullName = $arrayResult['fullName'];
    			}
    		    	    			    		    			if (array_key_exists ( "latest", $this->arrayResult )) {
    			$this->latest = $arrayResult['latest'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobile", $this->arrayResult )) {
    			$this->mobile = $arrayResult['mobile'];
    			}
    		    	    			    		    			if (array_key_exists ( "phone", $this->arrayResult )) {
    			$this->phone = $arrayResult['phone'];
    			}
    		    	    			    		    			if (array_key_exists ( "postCode", $this->arrayResult )) {
    			$this->postCode = $arrayResult['postCode'];
    			}
    		    	    		}
 
   
}
?>