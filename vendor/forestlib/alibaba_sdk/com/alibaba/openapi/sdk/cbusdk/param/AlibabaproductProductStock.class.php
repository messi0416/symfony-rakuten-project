<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductSkuStockBean.class.php');

class AlibabaproductProductStock extends SDKDomain {

       	
    private $productId;
    
        /**
    * @return 产品ID
    */
        public function getProductId() {
        return $this->productId;
    }
    
    /**
     * 设置产品ID     
     * @param Long $productId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductId( $productId) {
        $this->productId = $productId;
    }
    
        	
    private $productAmountChange;
    
        /**
    * @return 有的产品没有sku信息，对已售数量的变更设置在这里。如果值为5，说明增加5个可售数量。如果值为-9，说明减少9个可售数量。
    */
        public function getProductAmountChange() {
        return $this->productAmountChange;
    }
    
    /**
     * 设置有的产品没有sku信息，对已售数量的变更设置在这里。如果值为5，说明增加5个可售数量。如果值为-9，说明减少9个可售数量。     
     * @param Integer $productAmountChange     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductAmountChange( $productAmountChange) {
        $this->productAmountChange = $productAmountChange;
    }
    
        	
    private $skuStocks;
    
        /**
    * @return SKU的库存的变更信息
    */
        public function getSkuStocks() {
        return $this->skuStocks;
    }
    
    /**
     * 设置SKU的库存的变更信息     
     * @param array include @see AlibabaproductSkuStockBean[] $skuStocks     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuStocks(AlibabaproductSkuStockBean $skuStocks) {
        $this->skuStocks = $skuStocks;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "productId", $this->stdResult )) {
    				$this->productId = $this->stdResult->{"productId"};
    			}
    			    		    				    			    			if (array_key_exists ( "productAmountChange", $this->stdResult )) {
    				$this->productAmountChange = $this->stdResult->{"productAmountChange"};
    			}
    			    		    				    			    			if (array_key_exists ( "skuStocks", $this->stdResult )) {
    			$skuStocksResult=$this->stdResult->{"skuStocks"};
    				$object = json_decode ( json_encode ( $skuStocksResult ), true );
					$this->skuStocks = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductSkuStockBeanResult=new AlibabaproductSkuStockBean();
						$AlibabaproductSkuStockBeanResult->setArrayResult($arrayobject );
						$this->skuStocks [$i] = $AlibabaproductSkuStockBeanResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "productId", $this->arrayResult )) {
    			$this->productId = $arrayResult['productId'];
    			}
    		    	    			    		    			if (array_key_exists ( "productAmountChange", $this->arrayResult )) {
    			$this->productAmountChange = $arrayResult['productAmountChange'];
    			}
    		    	    			    		    		if (array_key_exists ( "skuStocks", $this->arrayResult )) {
    		$skuStocksResult=$arrayResult['skuStocks'];
    			$this->skuStocks = AlibabaproductSkuStockBean();
    			$this->skuStocks->$this->setStdResult ( $skuStocksResult);
    		}
    		    	    		}
 
   
}
?>