<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalgisticsMySendGoodsAddress extends SDKDomain {

       	
    private $addressName;
    
        /**
    * @return 
    */
        public function getAddressName() {
        return $this->addressName;
    }
    
    /**
     * 设置     
     * @param String $addressName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressName( $addressName) {
        $this->addressName = $addressName;
    }
    
        	
    private $addressType;
    
        /**
    * @return 
    */
        public function getAddressType() {
        return $this->addressType;
    }
    
    /**
     * 设置     
     * @param String $addressType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressType( $addressType) {
        $this->addressType = $addressType;
    }
    
        	
    private $areaName;
    
        /**
    * @return 
    */
        public function getAreaName() {
        return $this->areaName;
    }
    
    /**
     * 设置     
     * @param String $areaName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAreaName( $areaName) {
        $this->areaName = $areaName;
    }
    
        	
    private $cityName;
    
        /**
    * @return 
    */
        public function getCityName() {
        return $this->cityName;
    }
    
    /**
     * 设置     
     * @param String $cityName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCityName( $cityName) {
        $this->cityName = $cityName;
    }
    
        	
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
    
        	
    private $country;
    
        /**
    * @return 
    */
        public function getCountry() {
        return $this->country;
    }
    
    /**
     * 设置     
     * @param String $country     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCountry( $country) {
        $this->country = $country;
    }
    
        	
    private $fullAddress;
    
        /**
    * @return 
    */
        public function getFullAddress() {
        return $this->fullAddress;
    }
    
    /**
     * 设置     
     * @param String $fullAddress     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFullAddress( $fullAddress) {
        $this->fullAddress = $fullAddress;
    }
    
        	
    private $id;
    
        /**
    * @return 
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
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
    
        	
    private $leastCode;
    
        /**
    * @return 
    */
        public function getLeastCode() {
        return $this->leastCode;
    }
    
    /**
     * 设置     
     * @param String $leastCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLeastCode( $leastCode) {
        $this->leastCode = $leastCode;
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
    
        	
    private $post;
    
        /**
    * @return 
    */
        public function getPost() {
        return $this->post;
    }
    
    /**
     * 设置     
     * @param String $post     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPost( $post) {
        $this->post = $post;
    }
    
        	
    private $provinceName;
    
        /**
    * @return 
    */
        public function getProvinceName() {
        return $this->provinceName;
    }
    
    /**
     * 设置     
     * @param String $provinceName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProvinceName( $provinceName) {
        $this->provinceName = $provinceName;
    }
    
        	
    private $sendGoodsContactor;
    
        /**
    * @return 
    */
        public function getSendGoodsContactor() {
        return $this->sendGoodsContactor;
    }
    
    /**
     * 设置     
     * @param String $sendGoodsContactor     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSendGoodsContactor( $sendGoodsContactor) {
        $this->sendGoodsContactor = $sendGoodsContactor;
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
    
        	
    private $wangwangNo;
    
        /**
    * @return 
    */
        public function getWangwangNo() {
        return $this->wangwangNo;
    }
    
    /**
     * 设置     
     * @param String $wangwangNo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWangwangNo( $wangwangNo) {
        $this->wangwangNo = $wangwangNo;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "addressName", $this->stdResult )) {
    				$this->addressName = $this->stdResult->{"addressName"};
    			}
    			    		    				    			    			if (array_key_exists ( "addressType", $this->stdResult )) {
    				$this->addressType = $this->stdResult->{"addressType"};
    			}
    			    		    				    			    			if (array_key_exists ( "areaName", $this->stdResult )) {
    				$this->areaName = $this->stdResult->{"areaName"};
    			}
    			    		    				    			    			if (array_key_exists ( "cityName", $this->stdResult )) {
    				$this->cityName = $this->stdResult->{"cityName"};
    			}
    			    		    				    			    			if (array_key_exists ( "companyName", $this->stdResult )) {
    				$this->companyName = $this->stdResult->{"companyName"};
    			}
    			    		    				    			    			if (array_key_exists ( "country", $this->stdResult )) {
    				$this->country = $this->stdResult->{"country"};
    			}
    			    		    				    			    			if (array_key_exists ( "fullAddress", $this->stdResult )) {
    				$this->fullAddress = $this->stdResult->{"fullAddress"};
    			}
    			    		    				    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "isDefault", $this->stdResult )) {
    				$this->isDefault = $this->stdResult->{"isDefault"};
    			}
    			    		    				    			    			if (array_key_exists ( "leastCode", $this->stdResult )) {
    				$this->leastCode = $this->stdResult->{"leastCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobile", $this->stdResult )) {
    				$this->mobile = $this->stdResult->{"mobile"};
    			}
    			    		    				    			    			if (array_key_exists ( "phone", $this->stdResult )) {
    				$this->phone = $this->stdResult->{"phone"};
    			}
    			    		    				    			    			if (array_key_exists ( "post", $this->stdResult )) {
    				$this->post = $this->stdResult->{"post"};
    			}
    			    		    				    			    			if (array_key_exists ( "provinceName", $this->stdResult )) {
    				$this->provinceName = $this->stdResult->{"provinceName"};
    			}
    			    		    				    			    			if (array_key_exists ( "sendGoodsContactor", $this->stdResult )) {
    				$this->sendGoodsContactor = $this->stdResult->{"sendGoodsContactor"};
    			}
    			    		    				    			    			if (array_key_exists ( "userId", $this->stdResult )) {
    				$this->userId = $this->stdResult->{"userId"};
    			}
    			    		    				    			    			if (array_key_exists ( "wangwangNo", $this->stdResult )) {
    				$this->wangwangNo = $this->stdResult->{"wangwangNo"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "addressName", $this->arrayResult )) {
    			$this->addressName = $arrayResult['addressName'];
    			}
    		    	    			    		    			if (array_key_exists ( "addressType", $this->arrayResult )) {
    			$this->addressType = $arrayResult['addressType'];
    			}
    		    	    			    		    			if (array_key_exists ( "areaName", $this->arrayResult )) {
    			$this->areaName = $arrayResult['areaName'];
    			}
    		    	    			    		    			if (array_key_exists ( "cityName", $this->arrayResult )) {
    			$this->cityName = $arrayResult['cityName'];
    			}
    		    	    			    		    			if (array_key_exists ( "companyName", $this->arrayResult )) {
    			$this->companyName = $arrayResult['companyName'];
    			}
    		    	    			    		    			if (array_key_exists ( "country", $this->arrayResult )) {
    			$this->country = $arrayResult['country'];
    			}
    		    	    			    		    			if (array_key_exists ( "fullAddress", $this->arrayResult )) {
    			$this->fullAddress = $arrayResult['fullAddress'];
    			}
    		    	    			    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "isDefault", $this->arrayResult )) {
    			$this->isDefault = $arrayResult['isDefault'];
    			}
    		    	    			    		    			if (array_key_exists ( "leastCode", $this->arrayResult )) {
    			$this->leastCode = $arrayResult['leastCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobile", $this->arrayResult )) {
    			$this->mobile = $arrayResult['mobile'];
    			}
    		    	    			    		    			if (array_key_exists ( "phone", $this->arrayResult )) {
    			$this->phone = $arrayResult['phone'];
    			}
    		    	    			    		    			if (array_key_exists ( "post", $this->arrayResult )) {
    			$this->post = $arrayResult['post'];
    			}
    		    	    			    		    			if (array_key_exists ( "provinceName", $this->arrayResult )) {
    			$this->provinceName = $arrayResult['provinceName'];
    			}
    		    	    			    		    			if (array_key_exists ( "sendGoodsContactor", $this->arrayResult )) {
    			$this->sendGoodsContactor = $arrayResult['sendGoodsContactor'];
    			}
    		    	    			    		    			if (array_key_exists ( "userId", $this->arrayResult )) {
    			$this->userId = $arrayResult['userId'];
    			}
    		    	    			    		    			if (array_key_exists ( "wangwangNo", $this->arrayResult )) {
    			$this->wangwangNo = $arrayResult['wangwangNo'];
    			}
    		    	    		}
 
   
}
?>