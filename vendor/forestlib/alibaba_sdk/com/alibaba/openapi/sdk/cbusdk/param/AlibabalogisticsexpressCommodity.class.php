<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');

class AlibabalogisticsexpressCommodity extends SDKDomain {

       	
    private $netWeight;
    
        /**
    * @return 净重（单位：kg）
    */
        public function getNetWeight() {
        return $this->netWeight;
    }
    
    /**
     * 设置净重（单位：kg）     
     * @param BigDecimal $netWeight     
     * 参数示例：<pre>22.23</pre>     
     * 此参数必填     */
    public function setNetWeight( $netWeight) {
        $this->netWeight = $netWeight;
    }
    
        	
    private $unit;
    
        /**
    * @return 单位，PCS：件
    */
        public function getUnit() {
        return $this->unit;
    }
    
    /**
     * 设置单位，PCS：件     
     * @param String $unit     
     * 参数示例：<pre>PCS</pre>     
     * 此参数必填     */
    public function setUnit( $unit) {
        $this->unit = $unit;
    }
    
        	
    private $enName;
    
        /**
    * @return 英文品名
    */
        public function getEnName() {
        return $this->enName;
    }
    
    /**
     * 设置英文品名     
     * @param String $enName     
     * 参数示例：<pre>telephone</pre>     
     * 此参数必填     */
    public function setEnName( $enName) {
        $this->enName = $enName;
    }
    
        	
    private $zhName;
    
        /**
    * @return 中文品名
    */
        public function getZhName() {
        return $this->zhName;
    }
    
    /**
     * 设置中文品名     
     * @param String $zhName     
     * 参数示例：<pre>电话机</pre>     
     * 此参数必填     */
    public function setZhName( $zhName) {
        $this->zhName = $zhName;
    }
    
        	
    private $originCountryCode;
    
        /**
    * @return 原产国，使用ISO 3166 2A
    */
        public function getOriginCountryCode() {
        return $this->originCountryCode;
    }
    
    /**
     * 设置原产国，使用ISO 3166 2A     
     * @param String $originCountryCode     
     * 参数示例：<pre>CN</pre>     
     * 此参数必填     */
    public function setOriginCountryCode( $originCountryCode) {
        $this->originCountryCode = $originCountryCode;
    }
    
        	
    private $statusDescTags;
    
        /**
    * @return 商品状态描述标签，EASY_BROKE: 易碎
    */
        public function getStatusDescTags() {
        return $this->statusDescTags;
    }
    
    /**
     * 设置商品状态描述标签，EASY_BROKE: 易碎     
     * @param array include @see String[] $statusDescTags     
     * 参数示例：<pre>EASY_BROKE</pre>     
     * 此参数必填     */
    public function setStatusDescTags( $statusDescTags) {
        $this->statusDescTags = $statusDescTags;
    }
    
        	
    private $quantity;
    
        /**
    * @return 商品件数
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置商品件数     
     * @param BigDecimal $quantity     
     * 参数示例：<pre>2</pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
        	
    private $hscode;
    
        /**
    * @return 海关商品编码
    */
        public function getHscode() {
        return $this->hscode;
    }
    
    /**
     * 设置海关商品编码     
     * @param String $hscode     
     * 参数示例：<pre>1234567890</pre>     
     * 此参数必填     */
    public function setHscode( $hscode) {
        $this->hscode = $hscode;
    }
    
        	
    private $price;
    
        /**
    * @return 单价
    */
        public function getPrice() {
        return $this->price;
    }
    
    /**
     * 设置单价     
     * @param AlibabalogisticscommonMoney $price     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPrice(AlibabalogisticscommonMoney $price) {
        $this->price = $price;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "netWeight", $this->stdResult )) {
    				$this->netWeight = $this->stdResult->{"netWeight"};
    			}
    			    		    				    			    			if (array_key_exists ( "unit", $this->stdResult )) {
    				$this->unit = $this->stdResult->{"unit"};
    			}
    			    		    				    			    			if (array_key_exists ( "enName", $this->stdResult )) {
    				$this->enName = $this->stdResult->{"enName"};
    			}
    			    		    				    			    			if (array_key_exists ( "zhName", $this->stdResult )) {
    				$this->zhName = $this->stdResult->{"zhName"};
    			}
    			    		    				    			    			if (array_key_exists ( "originCountryCode", $this->stdResult )) {
    				$this->originCountryCode = $this->stdResult->{"originCountryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "statusDescTags", $this->stdResult )) {
    				$this->statusDescTags = $this->stdResult->{"statusDescTags"};
    			}
    			    		    				    			    			if (array_key_exists ( "quantity", $this->stdResult )) {
    				$this->quantity = $this->stdResult->{"quantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "hscode", $this->stdResult )) {
    				$this->hscode = $this->stdResult->{"hscode"};
    			}
    			    		    				    			    			if (array_key_exists ( "price", $this->stdResult )) {
    				$priceResult=$this->stdResult->{"price"};
    				$this->price = new AlibabalogisticscommonMoney();
    				$this->price->setStdResult ( $priceResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "netWeight", $this->arrayResult )) {
    			$this->netWeight = $arrayResult['netWeight'];
    			}
    		    	    			    		    			if (array_key_exists ( "unit", $this->arrayResult )) {
    			$this->unit = $arrayResult['unit'];
    			}
    		    	    			    		    			if (array_key_exists ( "enName", $this->arrayResult )) {
    			$this->enName = $arrayResult['enName'];
    			}
    		    	    			    		    			if (array_key_exists ( "zhName", $this->arrayResult )) {
    			$this->zhName = $arrayResult['zhName'];
    			}
    		    	    			    		    			if (array_key_exists ( "originCountryCode", $this->arrayResult )) {
    			$this->originCountryCode = $arrayResult['originCountryCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "statusDescTags", $this->arrayResult )) {
    			$this->statusDescTags = $arrayResult['statusDescTags'];
    			}
    		    	    			    		    			if (array_key_exists ( "quantity", $this->arrayResult )) {
    			$this->quantity = $arrayResult['quantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "hscode", $this->arrayResult )) {
    			$this->hscode = $arrayResult['hscode'];
    			}
    		    	    			    		    		if (array_key_exists ( "price", $this->arrayResult )) {
    		$priceResult=$arrayResult['price'];
    			    			$this->price = new AlibabalogisticscommonMoney();
    			    			$this->price->$this->setStdResult ( $priceResult);
    		}
    		    	    		}
 
   
}
?>