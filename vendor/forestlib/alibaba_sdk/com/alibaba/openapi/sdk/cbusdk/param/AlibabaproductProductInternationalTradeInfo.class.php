<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductProductInternationalTradeInfo extends SDKDomain {

       	
    private $fobCurrency;
    
        /**
    * @return FOB价格货币，参见FAQ 货币枚举值
    */
        public function getFobCurrency() {
        return $this->fobCurrency;
    }
    
    /**
     * 设置FOB价格货币，参见FAQ 货币枚举值     
     * @param String $fobCurrency     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFobCurrency( $fobCurrency) {
        $this->fobCurrency = $fobCurrency;
    }
    
        	
    private $fobMinPrice;
    
        /**
    * @return FOB最小价格
    */
        public function getFobMinPrice() {
        return $this->fobMinPrice;
    }
    
    /**
     * 设置FOB最小价格     
     * @param String $fobMinPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFobMinPrice( $fobMinPrice) {
        $this->fobMinPrice = $fobMinPrice;
    }
    
        	
    private $fobMaxPrice;
    
        /**
    * @return FOB最大价格
    */
        public function getFobMaxPrice() {
        return $this->fobMaxPrice;
    }
    
    /**
     * 设置FOB最大价格     
     * @param String $fobMaxPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFobMaxPrice( $fobMaxPrice) {
        $this->fobMaxPrice = $fobMaxPrice;
    }
    
        	
    private $fobUnitType;
    
        /**
    * @return FOB计量单位，参见FAQ 计量单位枚举值
    */
        public function getFobUnitType() {
        return $this->fobUnitType;
    }
    
    /**
     * 设置FOB计量单位，参见FAQ 计量单位枚举值     
     * @param String $fobUnitType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFobUnitType( $fobUnitType) {
        $this->fobUnitType = $fobUnitType;
    }
    
        	
    private $paymentMethods;
    
        /**
    * @return 付款方式，参见FAQ 付款方式枚举值
    */
        public function getPaymentMethods() {
        return $this->paymentMethods;
    }
    
    /**
     * 设置付款方式，参见FAQ 付款方式枚举值     
     * @param array include @see String[] $paymentMethods     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPaymentMethods( $paymentMethods) {
        $this->paymentMethods = $paymentMethods;
    }
    
        	
    private $minOrderQuantity;
    
        /**
    * @return 最小起订量
    */
        public function getMinOrderQuantity() {
        return $this->minOrderQuantity;
    }
    
    /**
     * 设置最小起订量     
     * @param Integer $minOrderQuantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMinOrderQuantity( $minOrderQuantity) {
        $this->minOrderQuantity = $minOrderQuantity;
    }
    
        	
    private $minOrderUnitType;
    
        /**
    * @return 最小起订量计量单位，参见FAQ 计量单位枚举值
    */
        public function getMinOrderUnitType() {
        return $this->minOrderUnitType;
    }
    
    /**
     * 设置最小起订量计量单位，参见FAQ 计量单位枚举值     
     * @param String $minOrderUnitType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMinOrderUnitType( $minOrderUnitType) {
        $this->minOrderUnitType = $minOrderUnitType;
    }
    
        	
    private $supplyQuantity;
    
        /**
    * @return supplyQuantity
    */
        public function getSupplyQuantity() {
        return $this->supplyQuantity;
    }
    
    /**
     * 设置supplyQuantity     
     * @param Integer $supplyQuantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplyQuantity( $supplyQuantity) {
        $this->supplyQuantity = $supplyQuantity;
    }
    
        	
    private $supplyUnitType;
    
        /**
    * @return 供货能力计量单位，参见FAQ 计量单位枚举值
    */
        public function getSupplyUnitType() {
        return $this->supplyUnitType;
    }
    
    /**
     * 设置供货能力计量单位，参见FAQ 计量单位枚举值     
     * @param String $supplyUnitType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplyUnitType( $supplyUnitType) {
        $this->supplyUnitType = $supplyUnitType;
    }
    
        	
    private $supplyPeriodType;
    
        /**
    * @return 供货能力周期，参见FAQ 时间周期枚举值
    */
        public function getSupplyPeriodType() {
        return $this->supplyPeriodType;
    }
    
    /**
     * 设置供货能力周期，参见FAQ 时间周期枚举值     
     * @param String $supplyPeriodType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplyPeriodType( $supplyPeriodType) {
        $this->supplyPeriodType = $supplyPeriodType;
    }
    
        	
    private $deliveryPort;
    
        /**
    * @return 发货港口
    */
        public function getDeliveryPort() {
        return $this->deliveryPort;
    }
    
    /**
     * 设置发货港口     
     * @param String $deliveryPort     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDeliveryPort( $deliveryPort) {
        $this->deliveryPort = $deliveryPort;
    }
    
        	
    private $deliveryTime;
    
        /**
    * @return 发货期限
    */
        public function getDeliveryTime() {
        return $this->deliveryTime;
    }
    
    /**
     * 设置发货期限     
     * @param String $deliveryTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDeliveryTime( $deliveryTime) {
        $this->deliveryTime = $deliveryTime;
    }
    
        	
    private $consignmentDate;
    
        /**
    * @return 新发货期限
    */
        public function getConsignmentDate() {
        return $this->consignmentDate;
    }
    
    /**
     * 设置新发货期限     
     * @param Integer $consignmentDate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setConsignmentDate( $consignmentDate) {
        $this->consignmentDate = $consignmentDate;
    }
    
        	
    private $packagingDesc;
    
        /**
    * @return 常规包装
    */
        public function getPackagingDesc() {
        return $this->packagingDesc;
    }
    
    /**
     * 设置常规包装     
     * @param String $packagingDesc     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPackagingDesc( $packagingDesc) {
        $this->packagingDesc = $packagingDesc;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "fobCurrency", $this->stdResult )) {
    				$this->fobCurrency = $this->stdResult->{"fobCurrency"};
    			}
    			    		    				    			    			if (array_key_exists ( "fobMinPrice", $this->stdResult )) {
    				$this->fobMinPrice = $this->stdResult->{"fobMinPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "fobMaxPrice", $this->stdResult )) {
    				$this->fobMaxPrice = $this->stdResult->{"fobMaxPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "fobUnitType", $this->stdResult )) {
    				$this->fobUnitType = $this->stdResult->{"fobUnitType"};
    			}
    			    		    				    			    			if (array_key_exists ( "paymentMethods", $this->stdResult )) {
    				$this->paymentMethods = $this->stdResult->{"paymentMethods"};
    			}
    			    		    				    			    			if (array_key_exists ( "minOrderQuantity", $this->stdResult )) {
    				$this->minOrderQuantity = $this->stdResult->{"minOrderQuantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "minOrderUnitType", $this->stdResult )) {
    				$this->minOrderUnitType = $this->stdResult->{"minOrderUnitType"};
    			}
    			    		    				    			    			if (array_key_exists ( "supplyQuantity", $this->stdResult )) {
    				$this->supplyQuantity = $this->stdResult->{"supplyQuantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "supplyUnitType", $this->stdResult )) {
    				$this->supplyUnitType = $this->stdResult->{"supplyUnitType"};
    			}
    			    		    				    			    			if (array_key_exists ( "supplyPeriodType", $this->stdResult )) {
    				$this->supplyPeriodType = $this->stdResult->{"supplyPeriodType"};
    			}
    			    		    				    			    			if (array_key_exists ( "deliveryPort", $this->stdResult )) {
    				$this->deliveryPort = $this->stdResult->{"deliveryPort"};
    			}
    			    		    				    			    			if (array_key_exists ( "deliveryTime", $this->stdResult )) {
    				$this->deliveryTime = $this->stdResult->{"deliveryTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "consignmentDate", $this->stdResult )) {
    				$this->consignmentDate = $this->stdResult->{"consignmentDate"};
    			}
    			    		    				    			    			if (array_key_exists ( "packagingDesc", $this->stdResult )) {
    				$this->packagingDesc = $this->stdResult->{"packagingDesc"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "fobCurrency", $this->arrayResult )) {
    			$this->fobCurrency = $arrayResult['fobCurrency'];
    			}
    		    	    			    		    			if (array_key_exists ( "fobMinPrice", $this->arrayResult )) {
    			$this->fobMinPrice = $arrayResult['fobMinPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "fobMaxPrice", $this->arrayResult )) {
    			$this->fobMaxPrice = $arrayResult['fobMaxPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "fobUnitType", $this->arrayResult )) {
    			$this->fobUnitType = $arrayResult['fobUnitType'];
    			}
    		    	    			    		    			if (array_key_exists ( "paymentMethods", $this->arrayResult )) {
    			$this->paymentMethods = $arrayResult['paymentMethods'];
    			}
    		    	    			    		    			if (array_key_exists ( "minOrderQuantity", $this->arrayResult )) {
    			$this->minOrderQuantity = $arrayResult['minOrderQuantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "minOrderUnitType", $this->arrayResult )) {
    			$this->minOrderUnitType = $arrayResult['minOrderUnitType'];
    			}
    		    	    			    		    			if (array_key_exists ( "supplyQuantity", $this->arrayResult )) {
    			$this->supplyQuantity = $arrayResult['supplyQuantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "supplyUnitType", $this->arrayResult )) {
    			$this->supplyUnitType = $arrayResult['supplyUnitType'];
    			}
    		    	    			    		    			if (array_key_exists ( "supplyPeriodType", $this->arrayResult )) {
    			$this->supplyPeriodType = $arrayResult['supplyPeriodType'];
    			}
    		    	    			    		    			if (array_key_exists ( "deliveryPort", $this->arrayResult )) {
    			$this->deliveryPort = $arrayResult['deliveryPort'];
    			}
    		    	    			    		    			if (array_key_exists ( "deliveryTime", $this->arrayResult )) {
    			$this->deliveryTime = $arrayResult['deliveryTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "consignmentDate", $this->arrayResult )) {
    			$this->consignmentDate = $arrayResult['consignmentDate'];
    			}
    		    	    			    		    			if (array_key_exists ( "packagingDesc", $this->arrayResult )) {
    			$this->packagingDesc = $arrayResult['packagingDesc'];
    			}
    		    	    		}
 
   
}
?>