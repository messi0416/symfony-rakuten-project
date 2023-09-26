<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtradeBizInvoiceGroup extends SDKDomain {

       	
    private $address;
    
        /**
    * @return 街道
    */
        public function getAddress() {
        return $this->address;
    }
    
    /**
     * 设置街道     
     * @param String $address     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddress( $address) {
        $this->address = $address;
    }
    
        	
    private $addressAndPhone;
    
        /**
    * @return 地址|电话
    */
        public function getAddressAndPhone() {
        return $this->addressAndPhone;
    }
    
    /**
     * 设置地址|电话     
     * @param String $addressAndPhone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressAndPhone( $addressAndPhone) {
        $this->addressAndPhone = $addressAndPhone;
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
    
        	
    private $bankAndAccount;
    
        /**
    * @return 开户行及帐号
    */
        public function getBankAndAccount() {
        return $this->bankAndAccount;
    }
    
    /**
     * 设置开户行及帐号     
     * @param String $bankAndAccount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBankAndAccount( $bankAndAccount) {
        $this->bankAndAccount = $bankAndAccount;
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
    
        	
    private $companyName;
    
        /**
    * @return 购货公司名
    */
        public function getCompanyName() {
        return $this->companyName;
    }
    
    /**
     * 设置购货公司名     
     * @param String $companyName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCompanyName( $companyName) {
        $this->companyName = $companyName;
    }
    
        	
    private $fullName;
    
        /**
    * @return 收票人姓名
    */
        public function getFullName() {
        return $this->fullName;
    }
    
    /**
     * 设置收票人姓名     
     * @param String $fullName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFullName( $fullName) {
        $this->fullName = $fullName;
    }
    
        	
    private $invoiceFlag;
    
        /**
    * @return 发票标记
    */
        public function getInvoiceFlag() {
        return $this->invoiceFlag;
    }
    
    /**
     * 设置发票标记     
     * @param Boolean $invoiceFlag     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInvoiceFlag( $invoiceFlag) {
        $this->invoiceFlag = $invoiceFlag;
    }
    
        	
    private $invoiceType;
    
        /**
    * @return 0：普通发票，1:增值税发票
    */
        public function getInvoiceType() {
        return $this->invoiceType;
    }
    
    /**
     * 设置0：普通发票，1:增值税发票     
     * @param Integer $invoiceType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInvoiceType( $invoiceType) {
        $this->invoiceType = $invoiceType;
    }
    
        	
    private $localInvoiceId;
    
        /**
    * @return 增值税本地发票号
    */
        public function getLocalInvoiceId() {
        return $this->localInvoiceId;
    }
    
    /**
     * 设置增值税本地发票号     
     * @param String $localInvoiceId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLocalInvoiceId( $localInvoiceId) {
        $this->localInvoiceId = $localInvoiceId;
    }
    
        	
    private $mergedJsonVar;
    
        /**
    * @return 前端提交的json数据格式
    */
        public function getMergedJsonVar() {
        return $this->mergedJsonVar;
    }
    
    /**
     * 设置前端提交的json数据格式     
     * @param String $mergedJsonVar     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMergedJsonVar( $mergedJsonVar) {
        $this->mergedJsonVar = $mergedJsonVar;
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
    
        	
    private $taxpayerIdentifier;
    
        /**
    * @return 纳税识别码
    */
        public function getTaxpayerIdentifier() {
        return $this->taxpayerIdentifier;
    }
    
    /**
     * 设置纳税识别码     
     * @param String $taxpayerIdentifier     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTaxpayerIdentifier( $taxpayerIdentifier) {
        $this->taxpayerIdentifier = $taxpayerIdentifier;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "address", $this->stdResult )) {
    				$this->address = $this->stdResult->{"address"};
    			}
    			    		    				    			    			if (array_key_exists ( "addressAndPhone", $this->stdResult )) {
    				$this->addressAndPhone = $this->stdResult->{"addressAndPhone"};
    			}
    			    		    				    			    			if (array_key_exists ( "areaCode", $this->stdResult )) {
    				$this->areaCode = $this->stdResult->{"areaCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "bankAndAccount", $this->stdResult )) {
    				$this->bankAndAccount = $this->stdResult->{"bankAndAccount"};
    			}
    			    		    				    			    			if (array_key_exists ( "cityCode", $this->stdResult )) {
    				$this->cityCode = $this->stdResult->{"cityCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "companyName", $this->stdResult )) {
    				$this->companyName = $this->stdResult->{"companyName"};
    			}
    			    		    				    			    			if (array_key_exists ( "fullName", $this->stdResult )) {
    				$this->fullName = $this->stdResult->{"fullName"};
    			}
    			    		    				    			    			if (array_key_exists ( "invoiceFlag", $this->stdResult )) {
    				$this->invoiceFlag = $this->stdResult->{"invoiceFlag"};
    			}
    			    		    				    			    			if (array_key_exists ( "invoiceType", $this->stdResult )) {
    				$this->invoiceType = $this->stdResult->{"invoiceType"};
    			}
    			    		    				    			    			if (array_key_exists ( "localInvoiceId", $this->stdResult )) {
    				$this->localInvoiceId = $this->stdResult->{"localInvoiceId"};
    			}
    			    		    				    			    			if (array_key_exists ( "mergedJsonVar", $this->stdResult )) {
    				$this->mergedJsonVar = $this->stdResult->{"mergedJsonVar"};
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
    			    		    				    			    			if (array_key_exists ( "provinceCode", $this->stdResult )) {
    				$this->provinceCode = $this->stdResult->{"provinceCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "taxpayerIdentifier", $this->stdResult )) {
    				$this->taxpayerIdentifier = $this->stdResult->{"taxpayerIdentifier"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "address", $this->arrayResult )) {
    			$this->address = $arrayResult['address'];
    			}
    		    	    			    		    			if (array_key_exists ( "addressAndPhone", $this->arrayResult )) {
    			$this->addressAndPhone = $arrayResult['addressAndPhone'];
    			}
    		    	    			    		    			if (array_key_exists ( "areaCode", $this->arrayResult )) {
    			$this->areaCode = $arrayResult['areaCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "bankAndAccount", $this->arrayResult )) {
    			$this->bankAndAccount = $arrayResult['bankAndAccount'];
    			}
    		    	    			    		    			if (array_key_exists ( "cityCode", $this->arrayResult )) {
    			$this->cityCode = $arrayResult['cityCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "companyName", $this->arrayResult )) {
    			$this->companyName = $arrayResult['companyName'];
    			}
    		    	    			    		    			if (array_key_exists ( "fullName", $this->arrayResult )) {
    			$this->fullName = $arrayResult['fullName'];
    			}
    		    	    			    		    			if (array_key_exists ( "invoiceFlag", $this->arrayResult )) {
    			$this->invoiceFlag = $arrayResult['invoiceFlag'];
    			}
    		    	    			    		    			if (array_key_exists ( "invoiceType", $this->arrayResult )) {
    			$this->invoiceType = $arrayResult['invoiceType'];
    			}
    		    	    			    		    			if (array_key_exists ( "localInvoiceId", $this->arrayResult )) {
    			$this->localInvoiceId = $arrayResult['localInvoiceId'];
    			}
    		    	    			    		    			if (array_key_exists ( "mergedJsonVar", $this->arrayResult )) {
    			$this->mergedJsonVar = $arrayResult['mergedJsonVar'];
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
    		    	    			    		    			if (array_key_exists ( "provinceCode", $this->arrayResult )) {
    			$this->provinceCode = $arrayResult['provinceCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "taxpayerIdentifier", $this->arrayResult )) {
    			$this->taxpayerIdentifier = $arrayResult['taxpayerIdentifier'];
    			}
    		    	    		}
 
   
}
?>