<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelNativeLogisticsItemsInfo.class.php');

class AlibabaopenplatformtrademodelNativeLogisticsInfo extends SDKDomain {

       	
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
    
        	
    private $area;
    
        /**
    * @return 县，区
    */
        public function getArea() {
        return $this->area;
    }
    
    /**
     * 设置县，区     
     * @param String $area     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setArea( $area) {
        $this->area = $area;
    }
    
        	
    private $areaCode;
    
        /**
    * @return 省市区编码
    */
        public function getAreaCode() {
        return $this->areaCode;
    }
    
    /**
     * 设置省市区编码     
     * @param String $areaCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAreaCode( $areaCode) {
        $this->areaCode = $areaCode;
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
    
        	
    private $zip;
    
        /**
    * @return 邮编
    */
        public function getZip() {
        return $this->zip;
    }
    
    /**
     * 设置邮编     
     * @param String $zip     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setZip( $zip) {
        $this->zip = $zip;
    }
    
        	
    private $logisticsItems;
    
        /**
    * @return 运单明细
    */
        public function getLogisticsItems() {
        return $this->logisticsItems;
    }
    
    /**
     * 设置运单明细     
     * @param array include @see AlibabaopenplatformtrademodelNativeLogisticsItemsInfo[] $logisticsItems     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsItems(AlibabaopenplatformtrademodelNativeLogisticsItemsInfo $logisticsItems) {
        $this->logisticsItems = $logisticsItems;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "area", $this->stdResult )) {
    				$this->area = $this->stdResult->{"area"};
    			}
    			    		    				    			    			if (array_key_exists ( "areaCode", $this->stdResult )) {
    				$this->areaCode = $this->stdResult->{"areaCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "city", $this->stdResult )) {
    				$this->city = $this->stdResult->{"city"};
    			}
    			    		    				    			    			if (array_key_exists ( "contactPerson", $this->stdResult )) {
    				$this->contactPerson = $this->stdResult->{"contactPerson"};
    			}
    			    		    				    			    			if (array_key_exists ( "fax", $this->stdResult )) {
    				$this->fax = $this->stdResult->{"fax"};
    			}
    			    		    				    			    			if (array_key_exists ( "mobile", $this->stdResult )) {
    				$this->mobile = $this->stdResult->{"mobile"};
    			}
    			    		    				    			    			if (array_key_exists ( "province", $this->stdResult )) {
    				$this->province = $this->stdResult->{"province"};
    			}
    			    		    				    			    			if (array_key_exists ( "telephone", $this->stdResult )) {
    				$this->telephone = $this->stdResult->{"telephone"};
    			}
    			    		    				    			    			if (array_key_exists ( "zip", $this->stdResult )) {
    				$this->zip = $this->stdResult->{"zip"};
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsItems", $this->stdResult )) {
    			$logisticsItemsResult=$this->stdResult->{"logisticsItems"};
    				$object = json_decode ( json_encode ( $logisticsItemsResult ), true );
					$this->logisticsItems = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtrademodelNativeLogisticsItemsInfoResult=new AlibabaopenplatformtrademodelNativeLogisticsItemsInfo();
						$AlibabaopenplatformtrademodelNativeLogisticsItemsInfoResult->setArrayResult($arrayobject );
						$this->logisticsItems [$i] = $AlibabaopenplatformtrademodelNativeLogisticsItemsInfoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "area", $this->arrayResult )) {
    			$this->area = $arrayResult['area'];
    			}
    		    	    			    		    			if (array_key_exists ( "areaCode", $this->arrayResult )) {
    			$this->areaCode = $arrayResult['areaCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "city", $this->arrayResult )) {
    			$this->city = $arrayResult['city'];
    			}
    		    	    			    		    			if (array_key_exists ( "contactPerson", $this->arrayResult )) {
    			$this->contactPerson = $arrayResult['contactPerson'];
    			}
    		    	    			    		    			if (array_key_exists ( "fax", $this->arrayResult )) {
    			$this->fax = $arrayResult['fax'];
    			}
    		    	    			    		    			if (array_key_exists ( "mobile", $this->arrayResult )) {
    			$this->mobile = $arrayResult['mobile'];
    			}
    		    	    			    		    			if (array_key_exists ( "province", $this->arrayResult )) {
    			$this->province = $arrayResult['province'];
    			}
    		    	    			    		    			if (array_key_exists ( "telephone", $this->arrayResult )) {
    			$this->telephone = $arrayResult['telephone'];
    			}
    		    	    			    		    			if (array_key_exists ( "zip", $this->arrayResult )) {
    			$this->zip = $arrayResult['zip'];
    			}
    		    	    			    		    		if (array_key_exists ( "logisticsItems", $this->arrayResult )) {
    		$logisticsItemsResult=$arrayResult['logisticsItems'];
    			$this->logisticsItems = AlibabaopenplatformtrademodelNativeLogisticsItemsInfo();
    			$this->logisticsItems->$this->setStdResult ( $logisticsItemsResult);
    		}
    		    	    		}
 
   
}
?>