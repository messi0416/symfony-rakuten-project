<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtrademodelInternationalLogisticsInfo extends SDKDomain {

       	
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
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->address = $address;
    }
    
        	
    private $allDeliveredTime;
    
        /**
    * @return 完全发货时间
    */
        public function getAllDeliveredTime() {
        return $this->allDeliveredTime;
    }
    
    /**
     * 设置完全发货时间     
     * @param Date $allDeliveredTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAllDeliveredTime( $allDeliveredTime) {
        $this->allDeliveredTime = $allDeliveredTime;
    }
    
        	
    private $alternateAddress;
    
        /**
    * @return 备用地址
    */
        public function getAlternateAddress() {
        return $this->alternateAddress;
    }
    
    /**
     * 设置备用地址     
     * @param String $alternateAddress     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAlternateAddress( $alternateAddress) {
        $this->alternateAddress = $alternateAddress;
    }
    
        	
    private $carrier;
    
        /**
    * @return 承运商
    */
        public function getCarrier() {
        return $this->carrier;
    }
    
    /**
     * 设置承运商     
     * @param String $carrier     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCarrier( $carrier) {
        $this->carrier = $carrier;
    }
    
        	
    private $city;
    
        /**
    * @return 城市
    */
        public function getCity() {
        return $this->city;
    }
    
    /**
     * 设置城市     
     * @param String $city     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCity( $city) {
        $this->city = $city;
    }
    
        	
    private $cityCode;
    
        /**
    * @return 城市编号
    */
        public function getCityCode() {
        return $this->cityCode;
    }
    
    /**
     * 设置城市编号     
     * @param String $cityCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCityCode( $cityCode) {
        $this->cityCode = $cityCode;
    }
    
        	
    private $contactPerson;
    
        /**
    * @return 联系人姓名
    */
        public function getContactPerson() {
        return $this->contactPerson;
    }
    
    /**
     * 设置联系人姓名     
     * @param String $contactPerson     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setContactPerson( $contactPerson) {
        $this->contactPerson = $contactPerson;
    }
    
        	
    private $country;
    
        /**
    * @return 国家
    */
        public function getCountry() {
        return $this->country;
    }
    
    /**
     * 设置国家     
     * @param String $country     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCountry( $country) {
        $this->country = $country;
    }
    
        	
    private $countryCode;
    
        /**
    * @return 国家编号
    */
        public function getCountryCode() {
        return $this->countryCode;
    }
    
    /**
     * 设置国家编号     
     * @param String $countryCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCountryCode( $countryCode) {
        $this->countryCode = $countryCode;
    }
    
        	
    private $fax;
    
        /**
    * @return 传真
    */
        public function getFax() {
        return $this->fax;
    }
    
    /**
     * 设置传真     
     * @param String $fax     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFax( $fax) {
        $this->fax = $fax;
    }
    
        	
    private $faxArea;
    
        /**
    * @return 传真地区区号
    */
        public function getFaxArea() {
        return $this->faxArea;
    }
    
    /**
     * 设置传真地区区号     
     * @param String $faxArea     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFaxArea( $faxArea) {
        $this->faxArea = $faxArea;
    }
    
        	
    private $faxCountry;
    
        /**
    * @return 传真国家编号
    */
        public function getFaxCountry() {
        return $this->faxCountry;
    }
    
    /**
     * 设置传真国家编号     
     * @param String $faxCountry     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFaxCountry( $faxCountry) {
        $this->faxCountry = $faxCountry;
    }
    
        	
    private $insuranceFee;
    
        /**
    * @return 物流保险费
    */
        public function getInsuranceFee() {
        return $this->insuranceFee;
    }
    
    /**
     * 设置物流保险费     
     * @param BigDecimal $insuranceFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInsuranceFee( $insuranceFee) {
        $this->insuranceFee = $insuranceFee;
    }
    
        	
    private $logisticsCode;
    
        /**
    * @return 委托单号
    */
        public function getLogisticsCode() {
        return $this->logisticsCode;
    }
    
    /**
     * 设置委托单号     
     * @param String $logisticsCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsCode( $logisticsCode) {
        $this->logisticsCode = $logisticsCode;
    }
    
        	
    private $logisticsFee;
    
        /**
    * @return 物流费用
    */
        public function getLogisticsFee() {
        return $this->logisticsFee;
    }
    
    /**
     * 设置物流费用     
     * @param BigDecimal $logisticsFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsFee( $logisticsFee) {
        $this->logisticsFee = $logisticsFee;
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
    
        	
    private $mobileArea;
    
        /**
    * @return 移动电话地区区号
    */
        public function getMobileArea() {
        return $this->mobileArea;
    }
    
    /**
     * 设置移动电话地区区号     
     * @param String $mobileArea     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMobileArea( $mobileArea) {
        $this->mobileArea = $mobileArea;
    }
    
        	
    private $mobileCountry;
    
        /**
    * @return 移动电话国家编号
    */
        public function getMobileCountry() {
        return $this->mobileCountry;
    }
    
    /**
     * 设置移动电话国家编号     
     * @param String $mobileCountry     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMobileCountry( $mobileCountry) {
        $this->mobileCountry = $mobileCountry;
    }
    
        	
    private $port;
    
        /**
    * @return 港口
    */
        public function getPort() {
        return $this->port;
    }
    
    /**
     * 设置港口     
     * @param String $port     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPort( $port) {
        $this->port = $port;
    }
    
        	
    private $portCode;
    
        /**
    * @return 港口编号
    */
        public function getPortCode() {
        return $this->portCode;
    }
    
    /**
     * 设置港口编号     
     * @param String $portCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPortCode( $portCode) {
        $this->portCode = $portCode;
    }
    
        	
    private $province;
    
        /**
    * @return 省份
    */
        public function getProvince() {
        return $this->province;
    }
    
    /**
     * 设置省份     
     * @param String $province     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProvince( $province) {
        $this->province = $province;
    }
    
        	
    private $provinceCode;
    
        /**
    * @return 省份编号
    */
        public function getProvinceCode() {
        return $this->provinceCode;
    }
    
    /**
     * 设置省份编号     
     * @param String $provinceCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProvinceCode( $provinceCode) {
        $this->provinceCode = $provinceCode;
    }
    
        	
    private $shipmentAbsoluteDate;
    
        /**
    * @return 绝对时间
    */
        public function getShipmentAbsoluteDate() {
        return $this->shipmentAbsoluteDate;
    }
    
    /**
     * 设置绝对时间     
     * @param Date $shipmentAbsoluteDate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentAbsoluteDate( $shipmentAbsoluteDate) {
        $this->shipmentAbsoluteDate = $shipmentAbsoluteDate;
    }
    
        	
    private $shipmentAbsoluteZone;
    
        /**
    * @return 买家时区
    */
        public function getShipmentAbsoluteZone() {
        return $this->shipmentAbsoluteZone;
    }
    
    /**
     * 设置买家时区     
     * @param String $shipmentAbsoluteZone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentAbsoluteZone( $shipmentAbsoluteZone) {
        $this->shipmentAbsoluteZone = $shipmentAbsoluteZone;
    }
    
        	
    private $shipmentDateType;
    
        /**
    * @return 倒计时类型。absolute(绝对),relative(相对)
    */
        public function getShipmentDateType() {
        return $this->shipmentDateType;
    }
    
    /**
     * 设置倒计时类型。absolute(绝对),relative(相对)     
     * @param String $shipmentDateType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentDateType( $shipmentDateType) {
        $this->shipmentDateType = $shipmentDateType;
    }
    
        	
    private $shipmentMethod;
    
        /**
    * @return 发货方式。AIR(空运),SEA(海运),EXPRESS(快递),LAND(陆运),UNKNOWN(未知)
    */
        public function getShipmentMethod() {
        return $this->shipmentMethod;
    }
    
    /**
     * 设置发货方式。AIR(空运),SEA(海运),EXPRESS(快递),LAND(陆运),UNKNOWN(未知)     
     * @param String $shipmentMethod     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentMethod( $shipmentMethod) {
        $this->shipmentMethod = $shipmentMethod;
    }
    
        	
    private $shipmentRelativeDuration;
    
        /**
    * @return 相对时间长度
    */
        public function getShipmentRelativeDuration() {
        return $this->shipmentRelativeDuration;
    }
    
    /**
     * 设置相对时间长度     
     * @param String $shipmentRelativeDuration     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentRelativeDuration( $shipmentRelativeDuration) {
        $this->shipmentRelativeDuration = $shipmentRelativeDuration;
    }
    
        	
    private $shipmentRelativeField;
    
        /**
    * @return 相对时间单位。day(天),hour(时),second(秒)
    */
        public function getShipmentRelativeField() {
        return $this->shipmentRelativeField;
    }
    
    /**
     * 设置相对时间单位。day(天),hour(时),second(秒)     
     * @param String $shipmentRelativeField     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentRelativeField( $shipmentRelativeField) {
        $this->shipmentRelativeField = $shipmentRelativeField;
    }
    
        	
    private $shipmentRelativeStart;
    
        /**
    * @return 相对时间的开始点。pre_amount(预付款到帐),final_amount(尾款到帐)
    */
        public function getShipmentRelativeStart() {
        return $this->shipmentRelativeStart;
    }
    
    /**
     * 设置相对时间的开始点。pre_amount(预付款到帐),final_amount(尾款到帐)     
     * @param String $shipmentRelativeStart     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipmentRelativeStart( $shipmentRelativeStart) {
        $this->shipmentRelativeStart = $shipmentRelativeStart;
    }
    
        	
    private $telephone;
    
        /**
    * @return 电话
    */
        public function getTelephone() {
        return $this->telephone;
    }
    
    /**
     * 设置电话     
     * @param String $telephone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTelephone( $telephone) {
        $this->telephone = $telephone;
    }
    
        	
    private $telephoneArea;
    
        /**
    * @return 电话地区区号
    */
        public function getTelephoneArea() {
        return $this->telephoneArea;
    }
    
    /**
     * 设置电话地区区号     
     * @param String $telephoneArea     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTelephoneArea( $telephoneArea) {
        $this->telephoneArea = $telephoneArea;
    }
    
        	
    private $telephoneCountryv;
    
        /**
    * @return 电话国家编号
    */
        public function getTelephoneCountryv() {
        return $this->telephoneCountryv;
    }
    
    /**
     * 设置电话国家编号     
     * @param String $telephoneCountryv     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTelephoneCountryv( $telephoneCountryv) {
        $this->telephoneCountryv = $telephoneCountryv;
    }
    
        	
    private $tradeTerm;
    
        /**
    * @return 贸易条款说明
    */
        public function getTradeTerm() {
        return $this->tradeTerm;
    }
    
    /**
     * 设置贸易条款说明     
     * @param String $tradeTerm     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeTerm( $tradeTerm) {
        $this->tradeTerm = $tradeTerm;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "allDeliveredTime", $this->stdResult )) {
    				$this->allDeliveredTime = $this->stdResult->{"allDeliveredTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "alternateAddress", $this->stdResult )) {
    				$this->alternateAddress = $this->stdResult->{"alternateAddress"};
    			}
    			    		    				    			    			if (array_key_exists ( "carrier", $this->stdResult )) {
    				$this->carrier = $this->stdResult->{"carrier"};
    			}
    			    		    				    			    			if (array_key_exists ( "city", $this->stdResult )) {
    				$this->city = $this->stdResult->{"city"};
    			}
    			    		    				    			    			if (array_key_exists ( "cityCode", $this->stdResult )) {
    				$this->cityCode = $this->stdResult->{"cityCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "contactPerson", $this->stdResult )) {
    				$this->contactPerson = $this->stdResult->{"contactPerson"};
    			}
    			    		    				    			    			if (array_key_exists ( "country", $this->stdResult )) {
    				$this->country = $this->stdResult->{"country"};
    			}
    			    		    				    			    			if (array_key_exists ( "countryCode", $this->stdResult )) {
    				$this->countryCode = $this->stdResult->{"countryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "fax", $this->stdResult )) {
    				$this->fax = $this->stdResult->{"fax"};
    			}
    			    		    				    			    			if (array_key_exists ( "faxArea", $this->stdResult )) {
    				$this->faxArea = $this->stdResult->{"faxArea"};
    			}
    			    		    				    			    			if (array_key_exists ( "faxCountry", $this->stdResult )) {
    				$this->faxCountry = $this->stdResult->{"faxCountry"};
    			}
    			    		    				    			    			if (array_key_exists ( "insuranceFee", $this->stdResult )) {
    				$this->insuranceFee = $this->stdResult->{"insuranceFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsCode", $this->stdResult )) {
    				$this->logisticsCode = $this->stdResult->{"logisticsCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsFee", $this->stdResult )) {
    				$this->logisticsFee = $this->stdResult->{"logisticsFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobile", $this->stdResult )) {
    				$this->mobile = $this->stdResult->{"mobile"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobileArea", $this->stdResult )) {
    				$this->mobileArea = $this->stdResult->{"mobileArea"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobileCountry", $this->stdResult )) {
    				$this->mobileCountry = $this->stdResult->{"mobileCountry"};
    			}
    			    		    				    			    			if (array_key_exists ( "port", $this->stdResult )) {
    				$this->port = $this->stdResult->{"port"};
    			}
    			    		    				    			    			if (array_key_exists ( "portCode", $this->stdResult )) {
    				$this->portCode = $this->stdResult->{"portCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "province", $this->stdResult )) {
    				$this->province = $this->stdResult->{"province"};
    			}
    			    		    				    			    			if (array_key_exists ( "provinceCode", $this->stdResult )) {
    				$this->provinceCode = $this->stdResult->{"provinceCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentAbsoluteDate", $this->stdResult )) {
    				$this->shipmentAbsoluteDate = $this->stdResult->{"shipmentAbsoluteDate"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentAbsoluteZone", $this->stdResult )) {
    				$this->shipmentAbsoluteZone = $this->stdResult->{"shipmentAbsoluteZone"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentDateType", $this->stdResult )) {
    				$this->shipmentDateType = $this->stdResult->{"shipmentDateType"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentMethod", $this->stdResult )) {
    				$this->shipmentMethod = $this->stdResult->{"shipmentMethod"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentRelativeDuration", $this->stdResult )) {
    				$this->shipmentRelativeDuration = $this->stdResult->{"shipmentRelativeDuration"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentRelativeField", $this->stdResult )) {
    				$this->shipmentRelativeField = $this->stdResult->{"shipmentRelativeField"};
    			}
    			    		    				    			    			if (array_key_exists ( "shipmentRelativeStart", $this->stdResult )) {
    				$this->shipmentRelativeStart = $this->stdResult->{"shipmentRelativeStart"};
    			}
    			    		    				    			    			if (array_key_exists ( "telephone", $this->stdResult )) {
    				$this->telephone = $this->stdResult->{"telephone"};
    			}
    			    		    				    			    			if (array_key_exists ( "telephoneArea", $this->stdResult )) {
    				$this->telephoneArea = $this->stdResult->{"telephoneArea"};
    			}
    			    		    				    			    			if (array_key_exists ( "telephoneCountryv", $this->stdResult )) {
    				$this->telephoneCountryv = $this->stdResult->{"telephoneCountryv"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeTerm", $this->stdResult )) {
    				$this->tradeTerm = $this->stdResult->{"tradeTerm"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "allDeliveredTime", $this->arrayResult )) {
    			$this->allDeliveredTime = $arrayResult['allDeliveredTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "alternateAddress", $this->arrayResult )) {
    			$this->alternateAddress = $arrayResult['alternateAddress'];
    			}
    		    	    			    		    			if (array_key_exists ( "carrier", $this->arrayResult )) {
    			$this->carrier = $arrayResult['carrier'];
    			}
    		    	    			    		    			if (array_key_exists ( "city", $this->arrayResult )) {
    			$this->city = $arrayResult['city'];
    			}
    		    	    			    		    			if (array_key_exists ( "cityCode", $this->arrayResult )) {
    			$this->cityCode = $arrayResult['cityCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "contactPerson", $this->arrayResult )) {
    			$this->contactPerson = $arrayResult['contactPerson'];
    			}
    		    	    			    		    			if (array_key_exists ( "country", $this->arrayResult )) {
    			$this->country = $arrayResult['country'];
    			}
    		    	    			    		    			if (array_key_exists ( "countryCode", $this->arrayResult )) {
    			$this->countryCode = $arrayResult['countryCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "fax", $this->arrayResult )) {
    			$this->fax = $arrayResult['fax'];
    			}
    		    	    			    		    			if (array_key_exists ( "faxArea", $this->arrayResult )) {
    			$this->faxArea = $arrayResult['faxArea'];
    			}
    		    	    			    		    			if (array_key_exists ( "faxCountry", $this->arrayResult )) {
    			$this->faxCountry = $arrayResult['faxCountry'];
    			}
    		    	    			    		    			if (array_key_exists ( "insuranceFee", $this->arrayResult )) {
    			$this->insuranceFee = $arrayResult['insuranceFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "logisticsCode", $this->arrayResult )) {
    			$this->logisticsCode = $arrayResult['logisticsCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "logisticsFee", $this->arrayResult )) {
    			$this->logisticsFee = $arrayResult['logisticsFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobile", $this->arrayResult )) {
    			$this->mobile = $arrayResult['mobile'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobileArea", $this->arrayResult )) {
    			$this->mobileArea = $arrayResult['mobileArea'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobileCountry", $this->arrayResult )) {
    			$this->mobileCountry = $arrayResult['mobileCountry'];
    			}
    		    	    			    		    			if (array_key_exists ( "port", $this->arrayResult )) {
    			$this->port = $arrayResult['port'];
    			}
    		    	    			    		    			if (array_key_exists ( "portCode", $this->arrayResult )) {
    			$this->portCode = $arrayResult['portCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "province", $this->arrayResult )) {
    			$this->province = $arrayResult['province'];
    			}
    		    	    			    		    			if (array_key_exists ( "provinceCode", $this->arrayResult )) {
    			$this->provinceCode = $arrayResult['provinceCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentAbsoluteDate", $this->arrayResult )) {
    			$this->shipmentAbsoluteDate = $arrayResult['shipmentAbsoluteDate'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentAbsoluteZone", $this->arrayResult )) {
    			$this->shipmentAbsoluteZone = $arrayResult['shipmentAbsoluteZone'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentDateType", $this->arrayResult )) {
    			$this->shipmentDateType = $arrayResult['shipmentDateType'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentMethod", $this->arrayResult )) {
    			$this->shipmentMethod = $arrayResult['shipmentMethod'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentRelativeDuration", $this->arrayResult )) {
    			$this->shipmentRelativeDuration = $arrayResult['shipmentRelativeDuration'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentRelativeField", $this->arrayResult )) {
    			$this->shipmentRelativeField = $arrayResult['shipmentRelativeField'];
    			}
    		    	    			    		    			if (array_key_exists ( "shipmentRelativeStart", $this->arrayResult )) {
    			$this->shipmentRelativeStart = $arrayResult['shipmentRelativeStart'];
    			}
    		    	    			    		    			if (array_key_exists ( "telephone", $this->arrayResult )) {
    			$this->telephone = $arrayResult['telephone'];
    			}
    		    	    			    		    			if (array_key_exists ( "telephoneArea", $this->arrayResult )) {
    			$this->telephoneArea = $arrayResult['telephoneArea'];
    			}
    		    	    			    		    			if (array_key_exists ( "telephoneCountryv", $this->arrayResult )) {
    			$this->telephoneCountryv = $arrayResult['telephoneCountryv'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeTerm", $this->arrayResult )) {
    			$this->tradeTerm = $arrayResult['tradeTerm'];
    			}
    		    	    		}
 
   
}
?>