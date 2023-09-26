<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtradeBizReceiveAddressGroup extends SDKDomain {

       	
    private $address;
    
        /**
    * @return 街道地址
    */
        public function getAddress() {
        return $this->address;
    }
    
    /**
     * 设置街道地址     
     * @param String $address     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->address = $address;
    }
    
        	
    private $addressId;
    
        /**
    * @return 用户在阿里巴巴已经保存的收货地址的id
    */
        public function getAddressId() {
        return $this->addressId;
    }
    
    /**
     * 设置用户在阿里巴巴已经保存的收货地址的id     
     * @param Long $addressId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressId( $addressId) {
        $this->addressId = $addressId;
    }
    
        	
    private $areaCode;
    
        /**
    * @return 地区编码
    */
        public function getAreaCode() {
        return $this->areaCode;
    }
    
    /**
     * 设置地区编码     
     * @param String $areaCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAreaCode( $areaCode) {
        $this->areaCode = $areaCode;
    }
    
        	
    private $areaText;
    
        /**
    * @return 地区
    */
        public function getAreaText() {
        return $this->areaText;
    }
    
    /**
     * 设置地区     
     * @param String $areaText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAreaText( $areaText) {
        $this->areaText = $areaText;
    }
    
        	
    private $cityCode;
    
        /**
    * @return 城市编码
    */
        public function getCityCode() {
        return $this->cityCode;
    }
    
    /**
     * 设置城市编码     
     * @param String $cityCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCityCode( $cityCode) {
        $this->cityCode = $cityCode;
    }
    
        	
    private $cityText;
    
        /**
    * @return 城市
    */
        public function getCityText() {
        return $this->cityText;
    }
    
    /**
     * 设置城市     
     * @param String $cityText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCityText( $cityText) {
        $this->cityText = $cityText;
    }
    
        	
    private $fullName;
    
        /**
    * @return 收货人姓名
    */
        public function getFullName() {
        return $this->fullName;
    }
    
    /**
     * 设置收货人姓名     
     * @param String $fullName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFullName( $fullName) {
        $this->fullName = $fullName;
    }
    
        	
    private $group;
    
        /**
    * @return 信息所属分组。多订单提交时用来分组
    */
        public function getGroup() {
        return $this->group;
    }
    
    /**
     * 设置信息所属分组。多订单提交时用来分组     
     * @param String $group     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGroup( $group) {
        $this->group = $group;
    }
    
        	
    private $isTemp;
    
        /**
    * @return 是否为临时地址
    */
        public function getIsTemp() {
        return $this->isTemp;
    }
    
    /**
     * 设置是否为临时地址     
     * @param Boolean $isTemp     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsTemp( $isTemp) {
        $this->isTemp = $isTemp;
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
    * @return 手机
    */
        public function getMobile() {
        return $this->mobile;
    }
    
    /**
     * 设置手机     
     * @param String $mobile     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMobile( $mobile) {
        $this->mobile = $mobile;
    }
    
        	
    private $phone;
    
        /**
    * @return 电话
    */
        public function getPhone() {
        return $this->phone;
    }
    
    /**
     * 设置电话     
     * @param String $phone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPhone( $phone) {
        $this->phone = $phone;
    }
    
        	
    private $pickType;
    
        /**
    * @return 提货类型
    */
        public function getPickType() {
        return $this->pickType;
    }
    
    /**
     * 设置提货类型     
     * @param String $pickType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPickType( $pickType) {
        $this->pickType = $pickType;
    }
    
        	
    private $postCode;
    
        /**
    * @return 邮编
    */
        public function getPostCode() {
        return $this->postCode;
    }
    
    /**
     * 设置邮编     
     * @param String $postCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPostCode( $postCode) {
        $this->postCode = $postCode;
    }
    
        	
    private $provinceCode;
    
        /**
    * @return 省份编码
    */
        public function getProvinceCode() {
        return $this->provinceCode;
    }
    
    /**
     * 设置省份编码     
     * @param String $provinceCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProvinceCode( $provinceCode) {
        $this->provinceCode = $provinceCode;
    }
    
        	
    private $provinceText;
    
        /**
    * @return 省份
    */
        public function getProvinceText() {
        return $this->provinceText;
    }
    
    /**
     * 设置省份     
     * @param String $provinceText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProvinceText( $provinceText) {
        $this->provinceText = $provinceText;
    }
    
        	
    private $isText;
    
        /**
    * @return 收货地址是否以文本传输
    */
        public function getIsText() {
        return $this->isText;
    }
    
    /**
     * 设置收货地址是否以文本传输     
     * @param Boolean $isText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsText( $isText) {
        $this->isText = $isText;
    }
    
        	
    private $warehouse;
    
        /**
    * @return 仓库名称
    */
        public function getWarehouse() {
        return $this->warehouse;
    }
    
    /**
     * 设置仓库名称     
     * @param String $warehouse     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWarehouse( $warehouse) {
        $this->warehouse = $warehouse;
    }
    
        	
    private $isDefault;
    
        /**
    * @return 是否为默认地址
    */
        public function getIsDefault() {
        return $this->isDefault;
    }
    
    /**
     * 设置是否为默认地址     
     * @param Boolean $isDefault     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsDefault( $isDefault) {
        $this->isDefault = $isDefault;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "addressId", $this->stdResult )) {
    				$this->addressId = $this->stdResult->{"addressId"};
    			}
    			    		    				    			    			if (array_key_exists ( "areaCode", $this->stdResult )) {
    				$this->areaCode = $this->stdResult->{"areaCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "areaText", $this->stdResult )) {
    				$this->areaText = $this->stdResult->{"areaText"};
    			}
    			    		    				    			    			if (array_key_exists ( "cityCode", $this->stdResult )) {
    				$this->cityCode = $this->stdResult->{"cityCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "cityText", $this->stdResult )) {
    				$this->cityText = $this->stdResult->{"cityText"};
    			}
    			    		    				    			    			if (array_key_exists ( "fullName", $this->stdResult )) {
    				$this->fullName = $this->stdResult->{"fullName"};
    			}
    			    		    				    			    			if (array_key_exists ( "group", $this->stdResult )) {
    				$this->group = $this->stdResult->{"group"};
    			}
    			    		    				    			    			if (array_key_exists ( "isTemp", $this->stdResult )) {
    				$this->isTemp = $this->stdResult->{"isTemp"};
    			}
    			    		    				    			    			if (array_key_exists ( "memberId", $this->stdResult )) {
    				$this->memberId = $this->stdResult->{"memberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobile", $this->stdResult )) {
    				$this->mobile = $this->stdResult->{"mobile"};
    			}
    			    		    				    			    			if (array_key_exists ( "phone", $this->stdResult )) {
    				$this->phone = $this->stdResult->{"phone"};
    			}
    			    		    				    			    			if (array_key_exists ( "pickType", $this->stdResult )) {
    				$this->pickType = $this->stdResult->{"pickType"};
    			}
    			    		    				    			    			if (array_key_exists ( "postCode", $this->stdResult )) {
    				$this->postCode = $this->stdResult->{"postCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "provinceCode", $this->stdResult )) {
    				$this->provinceCode = $this->stdResult->{"provinceCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "provinceText", $this->stdResult )) {
    				$this->provinceText = $this->stdResult->{"provinceText"};
    			}
    			    		    				    			    			if (array_key_exists ( "isText", $this->stdResult )) {
    				$this->isText = $this->stdResult->{"isText"};
    			}
    			    		    				    			    			if (array_key_exists ( "warehouse", $this->stdResult )) {
    				$this->warehouse = $this->stdResult->{"warehouse"};
    			}
    			    		    				    			    			if (array_key_exists ( "isDefault", $this->stdResult )) {
    				$this->isDefault = $this->stdResult->{"isDefault"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "addressId", $this->arrayResult )) {
    			$this->addressId = $arrayResult['addressId'];
    			}
    		    	    			    		    			if (array_key_exists ( "areaCode", $this->arrayResult )) {
    			$this->areaCode = $arrayResult['areaCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "areaText", $this->arrayResult )) {
    			$this->areaText = $arrayResult['areaText'];
    			}
    		    	    			    		    			if (array_key_exists ( "cityCode", $this->arrayResult )) {
    			$this->cityCode = $arrayResult['cityCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "cityText", $this->arrayResult )) {
    			$this->cityText = $arrayResult['cityText'];
    			}
    		    	    			    		    			if (array_key_exists ( "fullName", $this->arrayResult )) {
    			$this->fullName = $arrayResult['fullName'];
    			}
    		    	    			    		    			if (array_key_exists ( "group", $this->arrayResult )) {
    			$this->group = $arrayResult['group'];
    			}
    		    	    			    		    			if (array_key_exists ( "isTemp", $this->arrayResult )) {
    			$this->isTemp = $arrayResult['isTemp'];
    			}
    		    	    			    		    			if (array_key_exists ( "memberId", $this->arrayResult )) {
    			$this->memberId = $arrayResult['memberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobile", $this->arrayResult )) {
    			$this->mobile = $arrayResult['mobile'];
    			}
    		    	    			    		    			if (array_key_exists ( "phone", $this->arrayResult )) {
    			$this->phone = $arrayResult['phone'];
    			}
    		    	    			    		    			if (array_key_exists ( "pickType", $this->arrayResult )) {
    			$this->pickType = $arrayResult['pickType'];
    			}
    		    	    			    		    			if (array_key_exists ( "postCode", $this->arrayResult )) {
    			$this->postCode = $arrayResult['postCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "provinceCode", $this->arrayResult )) {
    			$this->provinceCode = $arrayResult['provinceCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "provinceText", $this->arrayResult )) {
    			$this->provinceText = $arrayResult['provinceText'];
    			}
    		    	    			    		    			if (array_key_exists ( "isText", $this->arrayResult )) {
    			$this->isText = $arrayResult['isText'];
    			}
    		    	    			    		    			if (array_key_exists ( "warehouse", $this->arrayResult )) {
    			$this->warehouse = $arrayResult['warehouse'];
    			}
    		    	    			    		    			if (array_key_exists ( "isDefault", $this->arrayResult )) {
    			$this->isDefault = $arrayResult['isDefault'];
    			}
    		    	    		}
 
   
}
?>