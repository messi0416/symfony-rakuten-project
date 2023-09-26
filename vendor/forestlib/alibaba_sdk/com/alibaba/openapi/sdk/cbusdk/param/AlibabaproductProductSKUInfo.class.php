<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductSKUAttrInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductPriceRange.class.php');

class AlibabaproductProductSKUInfo extends SDKDomain {

       	
    private $attributes;
    
        /**
    * @return SKU属性值，可填多组信息
    */
        public function getAttributes() {
        return $this->attributes;
    }
    
    /**
     * 设置SKU属性值，可填多组信息     
     * @param array include @see AlibabaproductSKUAttrInfo[] $attributes     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttributes(AlibabaproductSKUAttrInfo $attributes) {
        $this->attributes = $attributes;
    }
    
        	
    private $cargoNumber;
    
        /**
    * @return 指定规格的货号，国际站无需关注
    */
        public function getCargoNumber() {
        return $this->cargoNumber;
    }
    
    /**
     * 设置指定规格的货号，国际站无需关注     
     * @param String $cargoNumber     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCargoNumber( $cargoNumber) {
        $this->cargoNumber = $cargoNumber;
    }
    
        	
    private $amountOnSale;
    
        /**
    * @return 可销售数量，国际站无需关注
    */
        public function getAmountOnSale() {
        return $this->amountOnSale;
    }
    
    /**
     * 设置可销售数量，国际站无需关注     
     * @param Integer $amountOnSale     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAmountOnSale( $amountOnSale) {
        $this->amountOnSale = $amountOnSale;
    }
    
        	
    private $retailPrice;
    
        /**
    * @return 建议零售价，国际站无需关注
    */
        public function getRetailPrice() {
        return $this->retailPrice;
    }
    
    /**
     * 设置建议零售价，国际站无需关注     
     * @param Double $retailPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRetailPrice( $retailPrice) {
        $this->retailPrice = $retailPrice;
    }
    
        	
    private $price;
    
        /**
    * @return 报价时该规格的单价，国际站注意要点：含有SKU属性的在线批发产品设定具体价格时使用此值，若设置阶梯价格则使用priceRange
    */
        public function getPrice() {
        return $this->price;
    }
    
    /**
     * 设置报价时该规格的单价，国际站注意要点：含有SKU属性的在线批发产品设定具体价格时使用此值，若设置阶梯价格则使用priceRange     
     * @param Double $price     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPrice( $price) {
        $this->price = $price;
    }
    
        	
    private $priceRange;
    
        /**
    * @return 阶梯报价，1688无需关注
    */
        public function getPriceRange() {
        return $this->priceRange;
    }
    
    /**
     * 设置阶梯报价，1688无需关注     
     * @param array include @see AlibabaproductProductPriceRange[] $priceRange     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPriceRange(AlibabaproductProductPriceRange $priceRange) {
        $this->priceRange = $priceRange;
    }
    
        	
    private $skuCode;
    
        /**
    * @return 商品编码，1688无需关注
    */
        public function getSkuCode() {
        return $this->skuCode;
    }
    
    /**
     * 设置商品编码，1688无需关注     
     * @param String $skuCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuCode( $skuCode) {
        $this->skuCode = $skuCode;
    }
    
        	
    private $skuId;
    
        /**
    * @return skuId, 国际站无需关注
    */
        public function getSkuId() {
        return $this->skuId;
    }
    
    /**
     * 设置skuId, 国际站无需关注     
     * @param Long $skuId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuId( $skuId) {
        $this->skuId = $skuId;
    }
    
        	
    private $specId;
    
        /**
    * @return specId, 国际站无需关注
    */
        public function getSpecId() {
        return $this->specId;
    }
    
    /**
     * 设置specId, 国际站无需关注     
     * @param String $specId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecId( $specId) {
        $this->specId = $specId;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "attributes", $this->stdResult )) {
    			$attributesResult=$this->stdResult->{"attributes"};
    				$object = json_decode ( json_encode ( $attributesResult ), true );
					$this->attributes = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductSKUAttrInfoResult=new AlibabaproductSKUAttrInfo();
						$AlibabaproductSKUAttrInfoResult->setArrayResult($arrayobject );
						$this->attributes [$i] = $AlibabaproductSKUAttrInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "cargoNumber", $this->stdResult )) {
    				$this->cargoNumber = $this->stdResult->{"cargoNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "amountOnSale", $this->stdResult )) {
    				$this->amountOnSale = $this->stdResult->{"amountOnSale"};
    			}
    			    		    				    			    			if (array_key_exists ( "retailPrice", $this->stdResult )) {
    				$this->retailPrice = $this->stdResult->{"retailPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "price", $this->stdResult )) {
    				$this->price = $this->stdResult->{"price"};
    			}
    			    		    				    			    			if (array_key_exists ( "priceRange", $this->stdResult )) {
    			$priceRangeResult=$this->stdResult->{"priceRange"};
    				$object = json_decode ( json_encode ( $priceRangeResult ), true );
					$this->priceRange = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductProductPriceRangeResult=new AlibabaproductProductPriceRange();
						$AlibabaproductProductPriceRangeResult->setArrayResult($arrayobject );
						$this->priceRange [$i] = $AlibabaproductProductPriceRangeResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "skuCode", $this->stdResult )) {
    				$this->skuCode = $this->stdResult->{"skuCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "skuId", $this->stdResult )) {
    				$this->skuId = $this->stdResult->{"skuId"};
    			}
    			    		    				    			    			if (array_key_exists ( "specId", $this->stdResult )) {
    				$this->specId = $this->stdResult->{"specId"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "attributes", $this->arrayResult )) {
    		$attributesResult=$arrayResult['attributes'];
    			$this->attributes = AlibabaproductSKUAttrInfo();
    			$this->attributes->$this->setStdResult ( $attributesResult);
    		}
    		    	    			    		    			if (array_key_exists ( "cargoNumber", $this->arrayResult )) {
    			$this->cargoNumber = $arrayResult['cargoNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "amountOnSale", $this->arrayResult )) {
    			$this->amountOnSale = $arrayResult['amountOnSale'];
    			}
    		    	    			    		    			if (array_key_exists ( "retailPrice", $this->arrayResult )) {
    			$this->retailPrice = $arrayResult['retailPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "price", $this->arrayResult )) {
    			$this->price = $arrayResult['price'];
    			}
    		    	    			    		    		if (array_key_exists ( "priceRange", $this->arrayResult )) {
    		$priceRangeResult=$arrayResult['priceRange'];
    			$this->priceRange = AlibabaproductProductPriceRange();
    			$this->priceRange->$this->setStdResult ( $priceRangeResult);
    		}
    		    	    			    		    			if (array_key_exists ( "skuCode", $this->arrayResult )) {
    			$this->skuCode = $arrayResult['skuCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "skuId", $this->arrayResult )) {
    			$this->skuId = $arrayResult['skuId'];
    			}
    		    	    			    		    			if (array_key_exists ( "specId", $this->arrayResult )) {
    			$this->specId = $arrayResult['specId'];
    			}
    		    	    		}
 
   
}
?>