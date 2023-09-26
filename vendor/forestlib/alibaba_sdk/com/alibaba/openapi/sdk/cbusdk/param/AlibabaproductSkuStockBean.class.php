<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductSkuStockBean extends SDKDomain {

       	
    private $skuId;
    
        /**
    * @return 有的产品拥有sku信息，对已售数量的变更需要指定SKU信息。请注意：针对1688的业务场景，该字段请填写specId，不要填写skuId。
    */
        public function getSkuId() {
        return $this->skuId;
    }
    
    /**
     * 设置有的产品拥有sku信息，对已售数量的变更需要指定SKU信息。请注意：针对1688的业务场景，该字段请填写specId，不要填写skuId。     
     * @param String $skuId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuId( $skuId) {
        $this->skuId = $skuId;
    }
    
        	
    private $stockChange;
    
        /**
    * @return 如果值为5，说明增加5个可售数量。如果值为-9，说明减少9个可售数量。
    */
        public function getStockChange() {
        return $this->stockChange;
    }
    
    /**
     * 设置如果值为5，说明增加5个可售数量。如果值为-9，说明减少9个可售数量。     
     * @param Integer $stockChange     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStockChange( $stockChange) {
        $this->stockChange = $stockChange;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "skuId", $this->stdResult )) {
    				$this->skuId = $this->stdResult->{"skuId"};
    			}
    			    		    				    			    			if (array_key_exists ( "stockChange", $this->stdResult )) {
    				$this->stockChange = $this->stdResult->{"stockChange"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "skuId", $this->arrayResult )) {
    			$this->skuId = $arrayResult['skuId'];
    			}
    		    	    			    		    			if (array_key_exists ( "stockChange", $this->arrayResult )) {
    			$this->stockChange = $arrayResult['stockChange'];
    			}
    		    	    		}
 
   
}
?>