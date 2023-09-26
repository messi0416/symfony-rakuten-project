<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressPackageItem.class.php');

class AlibabalogisticsexpressGoodsPackage extends SDKDomain {

       	
    private $totalVolume;
    
        /**
    * @return 总体积
    */
        public function getTotalVolume() {
        return $this->totalVolume;
    }
    
    /**
     * 设置总体积     
     * @param BigDecimal $totalVolume     
     * 参数示例：<pre>2.34</pre>     
     * 此参数必填     */
    public function setTotalVolume( $totalVolume) {
        $this->totalVolume = $totalVolume;
    }
    
        	
    private $dimUnit;
    
        /**
    * @return 尺寸单位，cm：厘米
    */
        public function getDimUnit() {
        return $this->dimUnit;
    }
    
    /**
     * 设置尺寸单位，cm：厘米     
     * @param String $dimUnit     
     * 参数示例：<pre>cm</pre>     
     * 此参数必填     */
    public function setDimUnit( $dimUnit) {
        $this->dimUnit = $dimUnit;
    }
    
        	
    private $totalQuantity;
    
        /**
    * @return 总件数
    */
        public function getTotalQuantity() {
        return $this->totalQuantity;
    }
    
    /**
     * 设置总件数     
     * @param Integer $totalQuantity     
     * 参数示例：<pre>5</pre>     
     * 此参数必填     */
    public function setTotalQuantity( $totalQuantity) {
        $this->totalQuantity = $totalQuantity;
    }
    
        	
    private $weightUnit;
    
        /**
    * @return 重量单位，kg：公斤
    */
        public function getWeightUnit() {
        return $this->weightUnit;
    }
    
    /**
     * 设置重量单位，kg：公斤     
     * @param String $weightUnit     
     * 参数示例：<pre>kg</pre>     
     * 此参数必填     */
    public function setWeightUnit( $weightUnit) {
        $this->weightUnit = $weightUnit;
    }
    
        	
    private $totalWeight;
    
        /**
    * @return 总重量
    */
        public function getTotalWeight() {
        return $this->totalWeight;
    }
    
    /**
     * 设置总重量     
     * @param BigDecimal $totalWeight     
     * 参数示例：<pre>2.34</pre>     
     * 此参数必填     */
    public function setTotalWeight( $totalWeight) {
        $this->totalWeight = $totalWeight;
    }
    
        	
    private $packageItems;
    
        /**
    * @return 包裹项
    */
        public function getPackageItems() {
        return $this->packageItems;
    }
    
    /**
     * 设置包裹项     
     * @param array include @see AlibabalogisticsexpressPackageItem[] $packageItems     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPackageItems(AlibabalogisticsexpressPackageItem $packageItems) {
        $this->packageItems = $packageItems;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "totalVolume", $this->stdResult )) {
    				$this->totalVolume = $this->stdResult->{"totalVolume"};
    			}
    			    		    				    			    			if (array_key_exists ( "dimUnit", $this->stdResult )) {
    				$this->dimUnit = $this->stdResult->{"dimUnit"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalQuantity", $this->stdResult )) {
    				$this->totalQuantity = $this->stdResult->{"totalQuantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "weightUnit", $this->stdResult )) {
    				$this->weightUnit = $this->stdResult->{"weightUnit"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalWeight", $this->stdResult )) {
    				$this->totalWeight = $this->stdResult->{"totalWeight"};
    			}
    			    		    				    			    			if (array_key_exists ( "packageItems", $this->stdResult )) {
    			$packageItemsResult=$this->stdResult->{"packageItems"};
    				$object = json_decode ( json_encode ( $packageItemsResult ), true );
					$this->packageItems = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabalogisticsexpressPackageItemResult=new AlibabalogisticsexpressPackageItem();
						$AlibabalogisticsexpressPackageItemResult->setArrayResult($arrayobject );
						$this->packageItems [$i] = $AlibabalogisticsexpressPackageItemResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "totalVolume", $this->arrayResult )) {
    			$this->totalVolume = $arrayResult['totalVolume'];
    			}
    		    	    			    		    			if (array_key_exists ( "dimUnit", $this->arrayResult )) {
    			$this->dimUnit = $arrayResult['dimUnit'];
    			}
    		    	    			    		    			if (array_key_exists ( "totalQuantity", $this->arrayResult )) {
    			$this->totalQuantity = $arrayResult['totalQuantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "weightUnit", $this->arrayResult )) {
    			$this->weightUnit = $arrayResult['weightUnit'];
    			}
    		    	    			    		    			if (array_key_exists ( "totalWeight", $this->arrayResult )) {
    			$this->totalWeight = $arrayResult['totalWeight'];
    			}
    		    	    			    		    		if (array_key_exists ( "packageItems", $this->arrayResult )) {
    		$packageItemsResult=$arrayResult['packageItems'];
    			$this->packageItems = AlibabalogisticsexpressPackageItem();
    			$this->packageItems->$this->setStdResult ( $packageItemsResult);
    		}
    		    	    		}
 
   
}
?>