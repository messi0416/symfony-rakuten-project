<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductPriceRange.class.php');

class AlibabaproductProductSaleInfo extends SDKDomain {

       	
    private $supportOnlineTrade;
    
        /**
    * @return 是否支持网上交易。true：支持 false：不支持，国际站不需关注此字段
    */
        public function getSupportOnlineTrade() {
        return $this->supportOnlineTrade;
    }
    
    /**
     * 设置是否支持网上交易。true：支持 false：不支持，国际站不需关注此字段     
     * @param Boolean $supportOnlineTrade     
     * 参数示例：<pre>TRUE</pre>     
     * 此参数必填     */
    public function setSupportOnlineTrade( $supportOnlineTrade) {
        $this->supportOnlineTrade = $supportOnlineTrade;
    }
    
        	
    private $mixWholeSale;
    
        /**
    * @return 是否支持混批，国际站无需关注此字段
    */
        public function getMixWholeSale() {
        return $this->mixWholeSale;
    }
    
    /**
     * 设置是否支持混批，国际站无需关注此字段     
     * @param Boolean $mixWholeSale     
     * 参数示例：<pre>TRUE</pre>     
     * 此参数必填     */
    public function setMixWholeSale( $mixWholeSale) {
        $this->mixWholeSale = $mixWholeSale;
    }
    
        	
    private $saleType;
    
        /**
    * @return 销售方式，按件卖(normal)或者按批卖(batch)，1688站点无需关注此字段
    */
        public function getSaleType() {
        return $this->saleType;
    }
    
    /**
     * 设置销售方式，按件卖(normal)或者按批卖(batch)，1688站点无需关注此字段     
     * @param String $saleType     
     * 参数示例：<pre>normal</pre>     
     * 此参数必填     */
    public function setSaleType( $saleType) {
        $this->saleType = $saleType;
    }
    
        	
    private $priceAuth;
    
        /**
    * @return 是否价格私密信息，国际站无需关注此字段
    */
        public function getPriceAuth() {
        return $this->priceAuth;
    }
    
    /**
     * 设置是否价格私密信息，国际站无需关注此字段     
     * @param Boolean $priceAuth     
     * 参数示例：<pre>TRUE</pre>     
     * 此参数必填     */
    public function setPriceAuth( $priceAuth) {
        $this->priceAuth = $priceAuth;
    }
    
        	
    private $priceRanges;
    
        /**
    * @return 区间价格。按数量范围设定的区间价格
    */
        public function getPriceRanges() {
        return $this->priceRanges;
    }
    
    /**
     * 设置区间价格。按数量范围设定的区间价格     
     * @param array include @see AlibabaproductProductPriceRange[] $priceRanges     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPriceRanges(AlibabaproductProductPriceRange $priceRanges) {
        $this->priceRanges = $priceRanges;
    }
    
        	
    private $amountOnSale;
    
        /**
    * @return 可售数量，国际站无需关注此字段
    */
        public function getAmountOnSale() {
        return $this->amountOnSale;
    }
    
    /**
     * 设置可售数量，国际站无需关注此字段     
     * @param Double $amountOnSale     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAmountOnSale( $amountOnSale) {
        $this->amountOnSale = $amountOnSale;
    }
    
        	
    private $unit;
    
        /**
    * @return 计量单位
    */
        public function getUnit() {
        return $this->unit;
    }
    
    /**
     * 设置计量单位     
     * @param String $unit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnit( $unit) {
        $this->unit = $unit;
    }
    
        	
    private $minOrderQuantity;
    
        /**
    * @return 最小起订量，范围是1-99999。1688无需处理此字段
    */
        public function getMinOrderQuantity() {
        return $this->minOrderQuantity;
    }
    
    /**
     * 设置最小起订量，范围是1-99999。1688无需处理此字段     
     * @param Integer $minOrderQuantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMinOrderQuantity( $minOrderQuantity) {
        $this->minOrderQuantity = $minOrderQuantity;
    }
    
        	
    private $batchNumber;
    
        /**
    * @return 每批数量，默认为空或者非零值
    */
        public function getBatchNumber() {
        return $this->batchNumber;
    }
    
    /**
     * 设置每批数量，默认为空或者非零值     
     * @param Integer $batchNumber     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBatchNumber( $batchNumber) {
        $this->batchNumber = $batchNumber;
    }
    
        	
    private $retailprice;
    
        /**
    * @return 建议零售价，国际站无需关注
    */
        public function getRetailprice() {
        return $this->retailprice;
    }
    
    /**
     * 设置建议零售价，国际站无需关注     
     * @param Double $retailprice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRetailprice( $retailprice) {
        $this->retailprice = $retailprice;
    }
    
        	
    private $tax;
    
        /**
    * @return 税率相关信息，内容由用户自定，国际站无需关注
    */
        public function getTax() {
        return $this->tax;
    }
    
    /**
     * 设置税率相关信息，内容由用户自定，国际站无需关注     
     * @param String $tax     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTax( $tax) {
        $this->tax = $tax;
    }
    
        	
    private $sellunit;
    
        /**
    * @return 售卖单位，如果为批量售卖，代表售卖的单位，例如1"手"=12“件"的"手"，国际站无需关注
    */
        public function getSellunit() {
        return $this->sellunit;
    }
    
    /**
     * 设置售卖单位，如果为批量售卖，代表售卖的单位，例如1"手"=12“件"的"手"，国际站无需关注     
     * @param String $sellunit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellunit( $sellunit) {
        $this->sellunit = $sellunit;
    }
    
        	
    private $quoteType;
    
        /**
    * @return 普通报价-FIXED_PRICE("0"),SKU规格报价-SKU_PRICE("1"),SKU区间报价（商品维度）-SKU_PRICE_RANGE_FOR_OFFER("2"),SKU区间报价（SKU维度）-SKU_PRICE_RANGE("3")，国际站无需关注
    */
        public function getQuoteType() {
        return $this->quoteType;
    }
    
    /**
     * 设置普通报价-FIXED_PRICE("0"),SKU规格报价-SKU_PRICE("1"),SKU区间报价（商品维度）-SKU_PRICE_RANGE_FOR_OFFER("2"),SKU区间报价（SKU维度）-SKU_PRICE_RANGE("3")，国际站无需关注     
     * @param Integer $quoteType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuoteType( $quoteType) {
        $this->quoteType = $quoteType;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "supportOnlineTrade", $this->stdResult )) {
    				$this->supportOnlineTrade = $this->stdResult->{"supportOnlineTrade"};
    			}
    			    		    				    			    			if (array_key_exists ( "mixWholeSale", $this->stdResult )) {
    				$this->mixWholeSale = $this->stdResult->{"mixWholeSale"};
    			}
    			    		    				    			    			if (array_key_exists ( "saleType", $this->stdResult )) {
    				$this->saleType = $this->stdResult->{"saleType"};
    			}
    			    		    				    			    			if (array_key_exists ( "priceAuth", $this->stdResult )) {
    				$this->priceAuth = $this->stdResult->{"priceAuth"};
    			}
    			    		    				    			    			if (array_key_exists ( "priceRanges", $this->stdResult )) {
    			$priceRangesResult=$this->stdResult->{"priceRanges"};
    				$object = json_decode ( json_encode ( $priceRangesResult ), true );
					$this->priceRanges = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductProductPriceRangeResult=new AlibabaproductProductPriceRange();
						$AlibabaproductProductPriceRangeResult->setArrayResult($arrayobject );
						$this->priceRanges [$i] = $AlibabaproductProductPriceRangeResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "amountOnSale", $this->stdResult )) {
    				$this->amountOnSale = $this->stdResult->{"amountOnSale"};
    			}
    			    		    				    			    			if (array_key_exists ( "unit", $this->stdResult )) {
    				$this->unit = $this->stdResult->{"unit"};
    			}
    			    		    				    			    			if (array_key_exists ( "minOrderQuantity", $this->stdResult )) {
    				$this->minOrderQuantity = $this->stdResult->{"minOrderQuantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "batchNumber", $this->stdResult )) {
    				$this->batchNumber = $this->stdResult->{"batchNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "retailprice", $this->stdResult )) {
    				$this->retailprice = $this->stdResult->{"retailprice"};
    			}
    			    		    				    			    			if (array_key_exists ( "tax", $this->stdResult )) {
    				$this->tax = $this->stdResult->{"tax"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellunit", $this->stdResult )) {
    				$this->sellunit = $this->stdResult->{"sellunit"};
    			}
    			    		    				    			    			if (array_key_exists ( "quoteType", $this->stdResult )) {
    				$this->quoteType = $this->stdResult->{"quoteType"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "supportOnlineTrade", $this->arrayResult )) {
    			$this->supportOnlineTrade = $arrayResult['supportOnlineTrade'];
    			}
    		    	    			    		    			if (array_key_exists ( "mixWholeSale", $this->arrayResult )) {
    			$this->mixWholeSale = $arrayResult['mixWholeSale'];
    			}
    		    	    			    		    			if (array_key_exists ( "saleType", $this->arrayResult )) {
    			$this->saleType = $arrayResult['saleType'];
    			}
    		    	    			    		    			if (array_key_exists ( "priceAuth", $this->arrayResult )) {
    			$this->priceAuth = $arrayResult['priceAuth'];
    			}
    		    	    			    		    		if (array_key_exists ( "priceRanges", $this->arrayResult )) {
    		$priceRangesResult=$arrayResult['priceRanges'];
    			$this->priceRanges = AlibabaproductProductPriceRange();
    			$this->priceRanges->$this->setStdResult ( $priceRangesResult);
    		}
    		    	    			    		    			if (array_key_exists ( "amountOnSale", $this->arrayResult )) {
    			$this->amountOnSale = $arrayResult['amountOnSale'];
    			}
    		    	    			    		    			if (array_key_exists ( "unit", $this->arrayResult )) {
    			$this->unit = $arrayResult['unit'];
    			}
    		    	    			    		    			if (array_key_exists ( "minOrderQuantity", $this->arrayResult )) {
    			$this->minOrderQuantity = $arrayResult['minOrderQuantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "batchNumber", $this->arrayResult )) {
    			$this->batchNumber = $arrayResult['batchNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "retailprice", $this->arrayResult )) {
    			$this->retailprice = $arrayResult['retailprice'];
    			}
    		    	    			    		    			if (array_key_exists ( "tax", $this->arrayResult )) {
    			$this->tax = $arrayResult['tax'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellunit", $this->arrayResult )) {
    			$this->sellunit = $arrayResult['sellunit'];
    			}
    		    	    			    		    			if (array_key_exists ( "quoteType", $this->arrayResult )) {
    			$this->quoteType = $arrayResult['quoteType'];
    			}
    		    	    		}
 
   
}
?>