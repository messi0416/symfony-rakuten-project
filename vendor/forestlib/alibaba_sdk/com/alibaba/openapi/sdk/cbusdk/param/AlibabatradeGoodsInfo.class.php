<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradecomKeyValuePair.class.php');

class AlibabatradeGoodsInfo extends SDKDomain {

       	
    private $cartId;
    
        /**
    * @return 
    */
        public function getCartId() {
        return $this->cartId;
    }
    
    /**
     * 设置     
     * @param Long $cartId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCartId( $cartId) {
        $this->cartId = $cartId;
    }
    
        	
    private $ext;
    
        /**
    * @return 
    */
        public function getExt() {
        return $this->ext;
    }
    
    /**
     * 设置     
     * @param String $ext     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExt( $ext) {
        $this->ext = $ext;
    }
    
        	
    private $flow;
    
        /**
    * @return 
    */
        public function getFlow() {
        return $this->flow;
    }
    
    /**
     * 设置     
     * @param String $flow     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFlow( $flow) {
        $this->flow = $flow;
    }
    
        	
    private $id;
    
        /**
    * @return 
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置     
     * @param String $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $offerId;
    
        /**
    * @return 
    */
        public function getOfferId() {
        return $this->offerId;
    }
    
    /**
     * 设置     
     * @param Long $offerId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOfferId( $offerId) {
        $this->offerId = $offerId;
    }
    
        	
    private $quantity;
    
        /**
    * @return 
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置     
     * @param Double $quantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
        	
    private $specId;
    
        /**
    * @return 
    */
        public function getSpecId() {
        return $this->specId;
    }
    
    /**
     * 设置     
     * @param String $specId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecId( $specId) {
        $this->specId = $specId;
    }
    
        	
    private $tradeMode;
    
        /**
    * @return 
    */
        public function getTradeMode() {
        return $this->tradeMode;
    }
    
    /**
     * 设置     
     * @param String $tradeMode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeMode( $tradeMode) {
        $this->tradeMode = $tradeMode;
    }
    
        	
    private $tradeWay;
    
        /**
    * @return 
    */
        public function getTradeWay() {
        return $this->tradeWay;
    }
    
    /**
     * 设置     
     * @param String $tradeWay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeWay( $tradeWay) {
        $this->tradeWay = $tradeWay;
    }
    
        	
    private $extParams;
    
        /**
    * @return 
    */
        public function getExtParams() {
        return $this->extParams;
    }
    
    /**
     * 设置     
     * @param array include @see AlibabatradecomKeyValuePair[] $extParams     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExtParams(AlibabatradecomKeyValuePair $extParams) {
        $this->extParams = $extParams;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "cartId", $this->stdResult )) {
    				$this->cartId = $this->stdResult->{"cartId"};
    			}
    			    		    				    			    			if (array_key_exists ( "ext", $this->stdResult )) {
    				$this->ext = $this->stdResult->{"ext"};
    			}
    			    		    				    			    			if (array_key_exists ( "flow", $this->stdResult )) {
    				$this->flow = $this->stdResult->{"flow"};
    			}
    			    		    				    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "offerId", $this->stdResult )) {
    				$this->offerId = $this->stdResult->{"offerId"};
    			}
    			    		    				    			    			if (array_key_exists ( "quantity", $this->stdResult )) {
    				$this->quantity = $this->stdResult->{"quantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "specId", $this->stdResult )) {
    				$this->specId = $this->stdResult->{"specId"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeMode", $this->stdResult )) {
    				$this->tradeMode = $this->stdResult->{"tradeMode"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeWay", $this->stdResult )) {
    				$this->tradeWay = $this->stdResult->{"tradeWay"};
    			}
    			    		    				    			    			if (array_key_exists ( "extParams", $this->stdResult )) {
    			$extParamsResult=$this->stdResult->{"extParams"};
    				$object = json_decode ( json_encode ( $extParamsResult ), true );
					$this->extParams = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradecomKeyValuePairResult=new AlibabatradecomKeyValuePair();
						$AlibabatradecomKeyValuePairResult->setArrayResult($arrayobject );
						$this->extParams [$i] = $AlibabatradecomKeyValuePairResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "cartId", $this->arrayResult )) {
    			$this->cartId = $arrayResult['cartId'];
    			}
    		    	    			    		    			if (array_key_exists ( "ext", $this->arrayResult )) {
    			$this->ext = $arrayResult['ext'];
    			}
    		    	    			    		    			if (array_key_exists ( "flow", $this->arrayResult )) {
    			$this->flow = $arrayResult['flow'];
    			}
    		    	    			    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "offerId", $this->arrayResult )) {
    			$this->offerId = $arrayResult['offerId'];
    			}
    		    	    			    		    			if (array_key_exists ( "quantity", $this->arrayResult )) {
    			$this->quantity = $arrayResult['quantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "specId", $this->arrayResult )) {
    			$this->specId = $arrayResult['specId'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeMode", $this->arrayResult )) {
    			$this->tradeMode = $arrayResult['tradeMode'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeWay", $this->arrayResult )) {
    			$this->tradeWay = $arrayResult['tradeWay'];
    			}
    		    	    			    		    		if (array_key_exists ( "extParams", $this->arrayResult )) {
    		$extParamsResult=$arrayResult['extParams'];
    			$this->extParams = AlibabatradecomKeyValuePair();
    			$this->extParams->$this->setStdResult ( $extParamsResult);
    		}
    		    	    		}
 
   
}
?>