<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsexpressContact extends SDKDomain {

       	
    private $countryName;
    
        /**
    * @return 国家名称
    */
        public function getCountryName() {
        return $this->countryName;
    }
    
    /**
     * 设置国家名称     
     * @param String $countryName     
     * 参数示例：<pre>美国</pre>     
     * 此参数必填     */
    public function setCountryName( $countryName) {
        $this->countryName = $countryName;
    }
    
        	
    private $faxCountry;
    
        /**
    * @return 传真国家号
    */
        public function getFaxCountry() {
        return $this->faxCountry;
    }
    
    /**
     * 设置传真国家号     
     * @param String $faxCountry     
     * 参数示例：<pre>86</pre>     
     * 此参数必填     */
    public function setFaxCountry( $faxCountry) {
        $this->faxCountry = $faxCountry;
    }
    
        	
    private $countryCode;
    
        /**
    * @return 国家代码，使用ISO 3166 2A
    */
        public function getCountryCode() {
        return $this->countryCode;
    }
    
    /**
     * 设置国家代码，使用ISO 3166 2A     
     * @param String $countryCode     
     * 参数示例：<pre>US</pre>     
     * 此参数必填     */
    public function setCountryCode( $countryCode) {
        $this->countryCode = $countryCode;
    }
    
        	
    private $phoneCity;
    
        /**
    * @return 电话区号
    */
        public function getPhoneCity() {
        return $this->phoneCity;
    }
    
    /**
     * 设置电话区号     
     * @param String $phoneCity     
     * 参数示例：<pre>010</pre>     
     * 此参数必填     */
    public function setPhoneCity( $phoneCity) {
        $this->phoneCity = $phoneCity;
    }
    
        	
    private $mobileCountry;
    
        /**
    * @return 手机国家号
    */
        public function getMobileCountry() {
        return $this->mobileCountry;
    }
    
    /**
     * 设置手机国家号     
     * @param String $mobileCountry     
     * 参数示例：<pre>86</pre>     
     * 此参数必填     */
    public function setMobileCountry( $mobileCountry) {
        $this->mobileCountry = $mobileCountry;
    }
    
        	
    private $city;
    
        /**
    * @return 城市名
    */
        public function getCity() {
        return $this->city;
    }
    
    /**
     * 设置城市名     
     * @param String $city     
     * 参数示例：<pre>New York</pre>     
     * 此参数必填     */
    public function setCity( $city) {
        $this->city = $city;
    }
    
        	
    private $personNameZh;
    
        /**
    * @return 联系人姓名（中文）
    */
        public function getPersonNameZh() {
        return $this->personNameZh;
    }
    
    /**
     * 设置联系人姓名（中文）     
     * @param String $personNameZh     
     * 参数示例：<pre>刘小二</pre>     
     * 此参数必填     */
    public function setPersonNameZh( $personNameZh) {
        $this->personNameZh = $personNameZh;
    }
    
        	
    private $faxNumber;
    
        /**
    * @return 传真号码
    */
        public function getFaxNumber() {
        return $this->faxNumber;
    }
    
    /**
     * 设置传真号码     
     * @param String $faxNumber     
     * 参数示例：<pre>88889999</pre>     
     * 此参数必填     */
    public function setFaxNumber( $faxNumber) {
        $this->faxNumber = $faxNumber;
    }
    
        	
    private $postalCode;
    
        /**
    * @return 邮编
    */
        public function getPostalCode() {
        return $this->postalCode;
    }
    
    /**
     * 设置邮编     
     * @param String $postalCode     
     * 参数示例：<pre>518012</pre>     
     * 此参数必填     */
    public function setPostalCode( $postalCode) {
        $this->postalCode = $postalCode;
    }
    
        	
    private $phoneNumber;
    
        /**
    * @return 电话号码
    */
        public function getPhoneNumber() {
        return $this->phoneNumber;
    }
    
    /**
     * 设置电话号码     
     * @param String $phoneNumber     
     * 参数示例：<pre>99998888</pre>     
     * 此参数必填     */
    public function setPhoneNumber( $phoneNumber) {
        $this->phoneNumber = $phoneNumber;
    }
    
        	
    private $companyNameZh;
    
        /**
    * @return 公司名称（中文）
    */
        public function getCompanyNameZh() {
        return $this->companyNameZh;
    }
    
    /**
     * 设置公司名称（中文）     
     * @param String $companyNameZh     
     * 参数示例：<pre>测试公司</pre>     
     * 此参数必填     */
    public function setCompanyNameZh( $companyNameZh) {
        $this->companyNameZh = $companyNameZh;
    }
    
        	
    private $email;
    
        /**
    * @return 邮箱
    */
        public function getEmail() {
        return $this->email;
    }
    
    /**
     * 设置邮箱     
     * @param String $email     
     * 参数示例：<pre>test@test.com</pre>     
     * 此参数必填     */
    public function setEmail( $email) {
        $this->email = $email;
    }
    
        	
    private $address;
    
        /**
    * @return 详细地址
    */
        public function getAddress() {
        return $this->address;
    }
    
    /**
     * 设置详细地址     
     * @param String $address     
     * 参数示例：<pre>浙江省杭州市滨江区网商路999号</pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->address = $address;
    }
    
        	
    private $faxCity;
    
        /**
    * @return 传真区号
    */
        public function getFaxCity() {
        return $this->faxCity;
    }
    
    /**
     * 设置传真区号     
     * @param String $faxCity     
     * 参数示例：<pre>010</pre>     
     * 此参数必填     */
    public function setFaxCity( $faxCity) {
        $this->faxCity = $faxCity;
    }
    
        	
    private $companyNameEn;
    
        /**
    * @return 公司名称（英文）
    */
        public function getCompanyNameEn() {
        return $this->companyNameEn;
    }
    
    /**
     * 设置公司名称（英文）     
     * @param String $companyNameEn     
     * 参数示例：<pre>ABC test company</pre>     
     * 此参数必填     */
    public function setCompanyNameEn( $companyNameEn) {
        $this->companyNameEn = $companyNameEn;
    }
    
        	
    private $stateProvinceCode;
    
        /**
    * @return 州省代码
    */
        public function getStateProvinceCode() {
        return $this->stateProvinceCode;
    }
    
    /**
     * 设置州省代码     
     * @param String $stateProvinceCode     
     * 参数示例：<pre>NY</pre>     
     * 此参数必填     */
    public function setStateProvinceCode( $stateProvinceCode) {
        $this->stateProvinceCode = $stateProvinceCode;
    }
    
        	
    private $personNameEn;
    
        /**
    * @return 联系人姓名（英文）
    */
        public function getPersonNameEn() {
        return $this->personNameEn;
    }
    
    /**
     * 设置联系人姓名（英文）     
     * @param String $personNameEn     
     * 参数示例：<pre>Jick Liu</pre>     
     * 此参数必填     */
    public function setPersonNameEn( $personNameEn) {
        $this->personNameEn = $personNameEn;
    }
    
        	
    private $mobileNumber;
    
        /**
    * @return 手机号码
    */
        public function getMobileNumber() {
        return $this->mobileNumber;
    }
    
    /**
     * 设置手机号码     
     * @param String $mobileNumber     
     * 参数示例：<pre>13899999999</pre>     
     * 此参数必填     */
    public function setMobileNumber( $mobileNumber) {
        $this->mobileNumber = $mobileNumber;
    }
    
        	
    private $phoneCountry;
    
        /**
    * @return 电话国家号
    */
        public function getPhoneCountry() {
        return $this->phoneCountry;
    }
    
    /**
     * 设置电话国家号     
     * @param String $phoneCountry     
     * 参数示例：<pre>86</pre>     
     * 此参数必填     */
    public function setPhoneCountry( $phoneCountry) {
        $this->phoneCountry = $phoneCountry;
    }
    
        	
    private $stateProvinceName;
    
        /**
    * @return 州省名称
    */
        public function getStateProvinceName() {
        return $this->stateProvinceName;
    }
    
    /**
     * 设置州省名称     
     * @param String $stateProvinceName     
     * 参数示例：<pre>New York State</pre>     
     * 此参数必填     */
    public function setStateProvinceName( $stateProvinceName) {
        $this->stateProvinceName = $stateProvinceName;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "countryName", $this->stdResult )) {
    				$this->countryName = $this->stdResult->{"countryName"};
    			}
    			    		    				    			    			if (array_key_exists ( "faxCountry", $this->stdResult )) {
    				$this->faxCountry = $this->stdResult->{"faxCountry"};
    			}
    			    		    				    			    			if (array_key_exists ( "countryCode", $this->stdResult )) {
    				$this->countryCode = $this->stdResult->{"countryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "phoneCity", $this->stdResult )) {
    				$this->phoneCity = $this->stdResult->{"phoneCity"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobileCountry", $this->stdResult )) {
    				$this->mobileCountry = $this->stdResult->{"mobileCountry"};
    			}
    			    		    				    			    			if (array_key_exists ( "city", $this->stdResult )) {
    				$this->city = $this->stdResult->{"city"};
    			}
    			    		    				    			    			if (array_key_exists ( "personNameZh", $this->stdResult )) {
    				$this->personNameZh = $this->stdResult->{"personNameZh"};
    			}
    			    		    				    			    			if (array_key_exists ( "faxNumber", $this->stdResult )) {
    				$this->faxNumber = $this->stdResult->{"faxNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "postalCode", $this->stdResult )) {
    				$this->postalCode = $this->stdResult->{"postalCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "phoneNumber", $this->stdResult )) {
    				$this->phoneNumber = $this->stdResult->{"phoneNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "companyNameZh", $this->stdResult )) {
    				$this->companyNameZh = $this->stdResult->{"companyNameZh"};
    			}
    			    		    				    			    			if (array_key_exists ( "email", $this->stdResult )) {
    				$this->email = $this->stdResult->{"email"};
    			}
    			    		    				    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "faxCity", $this->stdResult )) {
    				$this->faxCity = $this->stdResult->{"faxCity"};
    			}
    			    		    				    			    			if (array_key_exists ( "companyNameEn", $this->stdResult )) {
    				$this->companyNameEn = $this->stdResult->{"companyNameEn"};
    			}
    			    		    				    			    			if (array_key_exists ( "stateProvinceCode", $this->stdResult )) {
    				$this->stateProvinceCode = $this->stdResult->{"stateProvinceCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "personNameEn", $this->stdResult )) {
    				$this->personNameEn = $this->stdResult->{"personNameEn"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobileNumber", $this->stdResult )) {
    				$this->mobileNumber = $this->stdResult->{"mobileNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "phoneCountry", $this->stdResult )) {
    				$this->phoneCountry = $this->stdResult->{"phoneCountry"};
    			}
    			    		    				    			    			if (array_key_exists ( "stateProvinceName", $this->stdResult )) {
    				$this->stateProvinceName = $this->stdResult->{"stateProvinceName"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "countryName", $this->arrayResult )) {
    			$this->countryName = $arrayResult['countryName'];
    			}
    		    	    			    		    			if (array_key_exists ( "faxCountry", $this->arrayResult )) {
    			$this->faxCountry = $arrayResult['faxCountry'];
    			}
    		    	    			    		    			if (array_key_exists ( "countryCode", $this->arrayResult )) {
    			$this->countryCode = $arrayResult['countryCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "phoneCity", $this->arrayResult )) {
    			$this->phoneCity = $arrayResult['phoneCity'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobileCountry", $this->arrayResult )) {
    			$this->mobileCountry = $arrayResult['mobileCountry'];
    			}
    		    	    			    		    			if (array_key_exists ( "city", $this->arrayResult )) {
    			$this->city = $arrayResult['city'];
    			}
    		    	    			    		    			if (array_key_exists ( "personNameZh", $this->arrayResult )) {
    			$this->personNameZh = $arrayResult['personNameZh'];
    			}
    		    	    			    		    			if (array_key_exists ( "faxNumber", $this->arrayResult )) {
    			$this->faxNumber = $arrayResult['faxNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "postalCode", $this->arrayResult )) {
    			$this->postalCode = $arrayResult['postalCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "phoneNumber", $this->arrayResult )) {
    			$this->phoneNumber = $arrayResult['phoneNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "companyNameZh", $this->arrayResult )) {
    			$this->companyNameZh = $arrayResult['companyNameZh'];
    			}
    		    	    			    		    			if (array_key_exists ( "email", $this->arrayResult )) {
    			$this->email = $arrayResult['email'];
    			}
    		    	    			    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "faxCity", $this->arrayResult )) {
    			$this->faxCity = $arrayResult['faxCity'];
    			}
    		    	    			    		    			if (array_key_exists ( "companyNameEn", $this->arrayResult )) {
    			$this->companyNameEn = $arrayResult['companyNameEn'];
    			}
    		    	    			    		    			if (array_key_exists ( "stateProvinceCode", $this->arrayResult )) {
    			$this->stateProvinceCode = $arrayResult['stateProvinceCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "personNameEn", $this->arrayResult )) {
    			$this->personNameEn = $arrayResult['personNameEn'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobileNumber", $this->arrayResult )) {
    			$this->mobileNumber = $arrayResult['mobileNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "phoneCountry", $this->arrayResult )) {
    			$this->phoneCountry = $arrayResult['phoneCountry'];
    			}
    		    	    			    		    			if (array_key_exists ( "stateProvinceName", $this->arrayResult )) {
    			$this->stateProvinceName = $arrayResult['stateProvinceName'];
    			}
    		    	    		}
 
   
}
?>