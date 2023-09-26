<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressWarehouse.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');

class AlibabalogisticsexpressExpressWTDSolution extends SDKDomain {

       	
    private $solutionId;
    
        /**
    * @return 方案ID
    */
        public function getSolutionId() {
        return $this->solutionId;
    }
    
    /**
     * 设置方案ID     
     * @param Long $solutionId     
     * 参数示例：<pre>123456</pre>     
     * 此参数必填     */
    public function setSolutionId( $solutionId) {
        $this->solutionId = $solutionId;
    }
    
        	
    private $solutionName;
    
        /**
    * @return 方案名称
    */
        public function getSolutionName() {
        return $this->solutionName;
    }
    
    /**
     * 设置方案名称     
     * @param String $solutionName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSolutionName( $solutionName) {
        $this->solutionName = $solutionName;
    }
    
        	
    private $originZip;
    
        /**
    * @return 起始地邮编
    */
        public function getOriginZip() {
        return $this->originZip;
    }
    
    /**
     * 设置起始地邮编     
     * @param String $originZip     
     * 参数示例：<pre>315001</pre>     
     * 此参数必填     */
    public function setOriginZip( $originZip) {
        $this->originZip = $originZip;
    }
    
        	
    private $destinationCountryCode;
    
        /**
    * @return 目的国家代码，使用ISO 3166 2A
    */
        public function getDestinationCountryCode() {
        return $this->destinationCountryCode;
    }
    
    /**
     * 设置目的国家代码，使用ISO 3166 2A     
     * @param String $destinationCountryCode     
     * 参数示例：<pre>US</pre>     
     * 此参数必填     */
    public function setDestinationCountryCode( $destinationCountryCode) {
        $this->destinationCountryCode = $destinationCountryCode;
    }
    
        	
    private $carrierCode;
    
        /**
    * @return 承运商代码，最终负责承运的服务商
    */
        public function getCarrierCode() {
        return $this->carrierCode;
    }
    
    /**
     * 设置承运商代码，最终负责承运的服务商     
     * @param String $carrierCode     
     * 参数示例：<pre>FEDEX</pre>     
     * 此参数必填     */
    public function setCarrierCode( $carrierCode) {
        $this->carrierCode = $carrierCode;
    }
    
        	
    private $providerCode;
    
        /**
    * @return 服务商代码
    */
        public function getProviderCode() {
        return $this->providerCode;
    }
    
    /**
     * 设置服务商代码     
     * @param String $providerCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProviderCode( $providerCode) {
        $this->providerCode = $providerCode;
    }
    
        	
    private $serviceName;
    
        /**
    * @return 服务类型名称
    */
        public function getServiceName() {
        return $this->serviceName;
    }
    
    /**
     * 设置服务类型名称     
     * @param String $serviceName     
     * 参数示例：<pre>FedEx IE</pre>     
     * 此参数必填     */
    public function setServiceName( $serviceName) {
        $this->serviceName = $serviceName;
    }
    
        	
    private $discount;
    
        /**
    * @return 折扣：报价/基本价格 * 10，保留一位小数，如3.5
    */
        public function getDiscount() {
        return $this->discount;
    }
    
    /**
     * 设置折扣：报价/基本价格 * 10，保留一位小数，如3.5     
     * @param BigDecimal $discount     
     * 参数示例：<pre>3.5</pre>     
     * 此参数必填     */
    public function setDiscount( $discount) {
        $this->discount = $discount;
    }
    
        	
    private $logisticsType;
    
        /**
    * @return 物流类型，EXPRESS_WTD：快递仓到门
    */
        public function getLogisticsType() {
        return $this->logisticsType;
    }
    
    /**
     * 设置物流类型，EXPRESS_WTD：快递仓到门     
     * @param String $logisticsType     
     * 参数示例：<pre>EXPRESS_WTD</pre>     
     * 此参数必填     */
    public function setLogisticsType( $logisticsType) {
        $this->logisticsType = $logisticsType;
    }
    
        	
    private $providerName;
    
        /**
    * @return 服务商名称
    */
        public function getProviderName() {
        return $this->providerName;
    }
    
    /**
     * 设置服务商名称     
     * @param String $providerName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProviderName( $providerName) {
        $this->providerName = $providerName;
    }
    
        	
    private $cutoffTime;
    
        /**
    * @return 进仓截至时间
    */
        public function getCutoffTime() {
        return $this->cutoffTime;
    }
    
    /**
     * 设置进仓截至时间     
     * @param String $cutoffTime     
     * 参数示例：<pre>工作日 16:00</pre>     
     * 此参数必填     */
    public function setCutoffTime( $cutoffTime) {
        $this->cutoffTime = $cutoffTime;
    }
    
        	
    private $carrierName;
    
        /**
    * @return 承运商名称
    */
        public function getCarrierName() {
        return $this->carrierName;
    }
    
    /**
     * 设置承运商名称     
     * @param String $carrierName     
     * 参数示例：<pre>FEDEX</pre>     
     * 此参数必填     */
    public function setCarrierName( $carrierName) {
        $this->carrierName = $carrierName;
    }
    
        	
    private $serviceCode;
    
        /**
    * @return 服务类型代码，如FedEx IE、UPS-Expedited
    */
        public function getServiceCode() {
        return $this->serviceCode;
    }
    
    /**
     * 设置服务类型代码，如FedEx IE、UPS-Expedited     
     * @param String $serviceCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setServiceCode( $serviceCode) {
        $this->serviceCode = $serviceCode;
    }
    
        	
    private $deliverToWarehouse;
    
        /**
    * @return 交货的仓库
    */
        public function getDeliverToWarehouse() {
        return $this->deliverToWarehouse;
    }
    
    /**
     * 设置交货的仓库     
     * @param AlibabalogisticsexpressWarehouse $deliverToWarehouse     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDeliverToWarehouse(AlibabalogisticsexpressWarehouse $deliverToWarehouse) {
        $this->deliverToWarehouse = $deliverToWarehouse;
    }
    
        	
    private $estimatedCost;
    
        /**
    * @return 预算费用
    */
        public function getEstimatedCost() {
        return $this->estimatedCost;
    }
    
    /**
     * 设置预算费用     
     * @param AlibabalogisticscommonMoney $estimatedCost     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEstimatedCost(AlibabalogisticscommonMoney $estimatedCost) {
        $this->estimatedCost = $estimatedCost;
    }
    
        	
    private $transportMinDays;
    
        /**
    * @return 运输时效最小天数
    */
        public function getTransportMinDays() {
        return $this->transportMinDays;
    }
    
    /**
     * 设置运输时效最小天数     
     * @param Integer $transportMinDays     
     * 参数示例：<pre>3</pre>     
     * 此参数必填     */
    public function setTransportMinDays( $transportMinDays) {
        $this->transportMinDays = $transportMinDays;
    }
    
        	
    private $transportMaxDays;
    
        /**
    * @return 运输时效最大天数
    */
        public function getTransportMaxDays() {
        return $this->transportMaxDays;
    }
    
    /**
     * 设置运输时效最大天数     
     * @param Integer $transportMaxDays     
     * 参数示例：<pre>6</pre>     
     * 此参数必填     */
    public function setTransportMaxDays( $transportMaxDays) {
        $this->transportMaxDays = $transportMaxDays;
    }
    
        	
    private $userAgreementName;
    
        /**
    * @return 用户协议名称
    */
        public function getUserAgreementName() {
        return $this->userAgreementName;
    }
    
    /**
     * 设置用户协议名称     
     * @param String $userAgreementName     
     * 参数示例：<pre>《XXX用户协议》</pre>     
     * 此参数必填     */
    public function setUserAgreementName( $userAgreementName) {
        $this->userAgreementName = $userAgreementName;
    }
    
        	
    private $userAgreementLink;
    
        /**
    * @return 用户协议链接
    */
        public function getUserAgreementLink() {
        return $this->userAgreementLink;
    }
    
    /**
     * 设置用户协议链接     
     * @param String $userAgreementLink     
     * 参数示例：<pre>http://xxx.xxx.com/xxx.htm</pre>     
     * 此参数必填     */
    public function setUserAgreementLink( $userAgreementLink) {
        $this->userAgreementLink = $userAgreementLink;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "solutionId", $this->stdResult )) {
    				$this->solutionId = $this->stdResult->{"solutionId"};
    			}
    			    		    				    			    			if (array_key_exists ( "solutionName", $this->stdResult )) {
    				$this->solutionName = $this->stdResult->{"solutionName"};
    			}
    			    		    				    			    			if (array_key_exists ( "originZip", $this->stdResult )) {
    				$this->originZip = $this->stdResult->{"originZip"};
    			}
    			    		    				    			    			if (array_key_exists ( "destinationCountryCode", $this->stdResult )) {
    				$this->destinationCountryCode = $this->stdResult->{"destinationCountryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "carrierCode", $this->stdResult )) {
    				$this->carrierCode = $this->stdResult->{"carrierCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "providerCode", $this->stdResult )) {
    				$this->providerCode = $this->stdResult->{"providerCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "serviceName", $this->stdResult )) {
    				$this->serviceName = $this->stdResult->{"serviceName"};
    			}
    			    		    				    			    			if (array_key_exists ( "discount", $this->stdResult )) {
    				$this->discount = $this->stdResult->{"discount"};
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsType", $this->stdResult )) {
    				$this->logisticsType = $this->stdResult->{"logisticsType"};
    			}
    			    		    				    			    			if (array_key_exists ( "providerName", $this->stdResult )) {
    				$this->providerName = $this->stdResult->{"providerName"};
    			}
    			    		    				    			    			if (array_key_exists ( "cutoffTime", $this->stdResult )) {
    				$this->cutoffTime = $this->stdResult->{"cutoffTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "carrierName", $this->stdResult )) {
    				$this->carrierName = $this->stdResult->{"carrierName"};
    			}
    			    		    				    			    			if (array_key_exists ( "serviceCode", $this->stdResult )) {
    				$this->serviceCode = $this->stdResult->{"serviceCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "deliverToWarehouse", $this->stdResult )) {
    				$deliverToWarehouseResult=$this->stdResult->{"deliverToWarehouse"};
    				$this->deliverToWarehouse = new AlibabalogisticsexpressWarehouse();
    				$this->deliverToWarehouse->setStdResult ( $deliverToWarehouseResult);
    			}
    			    		    				    			    			if (array_key_exists ( "estimatedCost", $this->stdResult )) {
    				$estimatedCostResult=$this->stdResult->{"estimatedCost"};
    				$this->estimatedCost = new AlibabalogisticscommonMoney();
    				$this->estimatedCost->setStdResult ( $estimatedCostResult);
    			}
    			    		    				    			    			if (array_key_exists ( "transportMinDays", $this->stdResult )) {
    				$this->transportMinDays = $this->stdResult->{"transportMinDays"};
    			}
    			    		    				    			    			if (array_key_exists ( "transportMaxDays", $this->stdResult )) {
    				$this->transportMaxDays = $this->stdResult->{"transportMaxDays"};
    			}
    			    		    				    			    			if (array_key_exists ( "userAgreementName", $this->stdResult )) {
    				$this->userAgreementName = $this->stdResult->{"userAgreementName"};
    			}
    			    		    				    			    			if (array_key_exists ( "userAgreementLink", $this->stdResult )) {
    				$this->userAgreementLink = $this->stdResult->{"userAgreementLink"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "solutionId", $this->arrayResult )) {
    			$this->solutionId = $arrayResult['solutionId'];
    			}
    		    	    			    		    			if (array_key_exists ( "solutionName", $this->arrayResult )) {
    			$this->solutionName = $arrayResult['solutionName'];
    			}
    		    	    			    		    			if (array_key_exists ( "originZip", $this->arrayResult )) {
    			$this->originZip = $arrayResult['originZip'];
    			}
    		    	    			    		    			if (array_key_exists ( "destinationCountryCode", $this->arrayResult )) {
    			$this->destinationCountryCode = $arrayResult['destinationCountryCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "carrierCode", $this->arrayResult )) {
    			$this->carrierCode = $arrayResult['carrierCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "providerCode", $this->arrayResult )) {
    			$this->providerCode = $arrayResult['providerCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "serviceName", $this->arrayResult )) {
    			$this->serviceName = $arrayResult['serviceName'];
    			}
    		    	    			    		    			if (array_key_exists ( "discount", $this->arrayResult )) {
    			$this->discount = $arrayResult['discount'];
    			}
    		    	    			    		    			if (array_key_exists ( "logisticsType", $this->arrayResult )) {
    			$this->logisticsType = $arrayResult['logisticsType'];
    			}
    		    	    			    		    			if (array_key_exists ( "providerName", $this->arrayResult )) {
    			$this->providerName = $arrayResult['providerName'];
    			}
    		    	    			    		    			if (array_key_exists ( "cutoffTime", $this->arrayResult )) {
    			$this->cutoffTime = $arrayResult['cutoffTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "carrierName", $this->arrayResult )) {
    			$this->carrierName = $arrayResult['carrierName'];
    			}
    		    	    			    		    			if (array_key_exists ( "serviceCode", $this->arrayResult )) {
    			$this->serviceCode = $arrayResult['serviceCode'];
    			}
    		    	    			    		    		if (array_key_exists ( "deliverToWarehouse", $this->arrayResult )) {
    		$deliverToWarehouseResult=$arrayResult['deliverToWarehouse'];
    			    			$this->deliverToWarehouse = new AlibabalogisticsexpressWarehouse();
    			    			$this->deliverToWarehouse->$this->setStdResult ( $deliverToWarehouseResult);
    		}
    		    	    			    		    		if (array_key_exists ( "estimatedCost", $this->arrayResult )) {
    		$estimatedCostResult=$arrayResult['estimatedCost'];
    			    			$this->estimatedCost = new AlibabalogisticscommonMoney();
    			    			$this->estimatedCost->$this->setStdResult ( $estimatedCostResult);
    		}
    		    	    			    		    			if (array_key_exists ( "transportMinDays", $this->arrayResult )) {
    			$this->transportMinDays = $arrayResult['transportMinDays'];
    			}
    		    	    			    		    			if (array_key_exists ( "transportMaxDays", $this->arrayResult )) {
    			$this->transportMaxDays = $arrayResult['transportMaxDays'];
    			}
    		    	    			    		    			if (array_key_exists ( "userAgreementName", $this->arrayResult )) {
    			$this->userAgreementName = $arrayResult['userAgreementName'];
    			}
    		    	    			    		    			if (array_key_exists ( "userAgreementLink", $this->arrayResult )) {
    			$this->userAgreementLink = $arrayResult['userAgreementLink'];
    			}
    		    	    		}
 
   
}
?>