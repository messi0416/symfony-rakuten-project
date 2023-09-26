<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradecomKeyValuePair.class.php');

class AlibabaopenplatformtradeJsonGoodsParam extends SDKDomain {

       	
    private $cartId;
    
        /**
    * @return 淘宝cartId
    */
        public function getCartId() {
        return $this->cartId;
    }
    
    /**
     * 设置淘宝cartId     
     * @param Long $cartId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCartId( $cartId) {
        $this->cartId = $cartId;
    }
    
        	
    private $ext;
    
        /**
    * @return 该参数是url中完整的id参数对应的json格式数据中,key等于ext的参数值。前端传入的完整 附加参数(json格式), 改参数值在extParams中也会存在,以key=="key:ExtensionDataJson"方式存储。例如:.../order/smart_make_order.htm?flow=xxx&id=[{"flow":"paired","offerId":"2100538036253","quantity":50,"specId":"d5855b19f9812b8ab98cd3084e8f6f74","ext":"{"":""}"},{"flow":"paired","offerId":"2100538036253","quantity":10,"specId":"84b3ab919b484e03b38b9afe60ea3401"}]&payWay=-1&makeOrderSource&isDefaultSelectCod
    */
        public function getExt() {
        return $this->ext;
    }
    
    /**
     * 设置该参数是url中完整的id参数对应的json格式数据中,key等于ext的参数值。前端传入的完整 附加参数(json格式), 改参数值在extParams中也会存在,以key=="key:ExtensionDataJson"方式存储。例如:.../order/smart_make_order.htm?flow=xxx&id=[{"flow":"paired","offerId":"2100538036253","quantity":50,"specId":"d5855b19f9812b8ab98cd3084e8f6f74","ext":"{"":""}"},{"flow":"paired","offerId":"2100538036253","quantity":10,"specId":"84b3ab919b484e03b38b9afe60ea3401"}]&payWay=-1&makeOrderSource&isDefaultSelectCod     
     * @param String $ext     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExt( $ext) {
        $this->ext = $ext;
    }
    
        	
    private $flow;
    
        /**
    * @return 所属流程
    */
        public function getFlow() {
        return $this->flow;
    }
    
    /**
     * 设置所属流程     
     * @param String $flow     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFlow( $flow) {
        $this->flow = $flow;
    }
    
        	
    private $id;
    
        /**
    * @return id,Cargo 标识
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置id,Cargo 标识     
     * @param String $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $offerId;
    
        /**
    * @return offer Id
    */
        public function getOfferId() {
        return $this->offerId;
    }
    
    /**
     * 设置offer Id     
     * @param Long $offerId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOfferId( $offerId) {
        $this->offerId = $offerId;
    }
    
        	
    private $quantity;
    
        /**
    * @return 数量
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置数量     
     * @param Double $quantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
        	
    private $specId;
    
        /**
    * @return sku offer的specId.
    */
        public function getSpecId() {
        return $this->specId;
    }
    
    /**
     * 设置sku offer的specId.     
     * @param String $specId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecId( $specId) {
        $this->specId = $specId;
    }
    
        	
    private $tradeMode;
    
        /**
    * @return 交易模式参数。目前只支持支付宝交易（值：alipay）。与traderWay都是必填且必须一致。
    */
        public function getTradeMode() {
        return $this->tradeMode;
    }
    
    /**
     * 设置交易模式参数。目前只支持支付宝交易（值：alipay）。与traderWay都是必填且必须一致。     
     * @param String $tradeMode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeMode( $tradeMode) {
        $this->tradeMode = $tradeMode;
    }
    
        	
    private $tradeWay;
    
        /**
    * @return 交易模式的分类。目前只支持支付宝交易（值：6）。与tradeMode都是必填且必须一致
    */
        public function getTradeWay() {
        return $this->tradeWay;
    }
    
    /**
     * 设置交易模式的分类。目前只支持支付宝交易（值：6）。与tradeMode都是必填且必须一致     
     * @param String $tradeWay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeWay( $tradeWay) {
        $this->tradeWay = $tradeWay;
    }
    
        	
    private $extParams;
    
        /**
    * @return 扩展数据,附加参数集合,自定各个业务场景下的附加属性。该参数集合包含了 ext的数据, key:ExtensionDataJson
    */
        public function getExtParams() {
        return $this->extParams;
    }
    
    /**
     * 设置扩展数据,附加参数集合,自定各个业务场景下的附加属性。该参数集合包含了 ext的数据, key:ExtensionDataJson     
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