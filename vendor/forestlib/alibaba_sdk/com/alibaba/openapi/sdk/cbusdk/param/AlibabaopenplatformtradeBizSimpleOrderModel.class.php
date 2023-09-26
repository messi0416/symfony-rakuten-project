<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizSimpleOrderEntryModel.class.php');

class AlibabaopenplatformtradeBizSimpleOrderModel extends SDKDomain {

       	
    private $buyerAlipayId;
    
        /**
    * @return 买家支付宝id
    */
        public function getBuyerAlipayId() {
        return $this->buyerAlipayId;
    }
    
    /**
     * 设置买家支付宝id     
     * @param String $buyerAlipayId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerAlipayId( $buyerAlipayId) {
        $this->buyerAlipayId = $buyerAlipayId;
    }
    
        	
    private $buyerLoginId;
    
        /**
    * @return 买家中文站会员登录ID
    */
        public function getBuyerLoginId() {
        return $this->buyerLoginId;
    }
    
    /**
     * 设置买家中文站会员登录ID     
     * @param String $buyerLoginId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerLoginId( $buyerLoginId) {
        $this->buyerLoginId = $buyerLoginId;
    }
    
        	
    private $buyerMemberId;
    
        /**
    * @return 买家中文站会员ID
    */
        public function getBuyerMemberId() {
        return $this->buyerMemberId;
    }
    
    /**
     * 设置买家中文站会员ID     
     * @param String $buyerMemberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerMemberId( $buyerMemberId) {
        $this->buyerMemberId = $buyerMemberId;
    }
    
        	
    private $buyerUserId;
    
        /**
    * @return 买家阿里帐号ID(包括淘宝帐号Id)
    */
        public function getBuyerUserId() {
        return $this->buyerUserId;
    }
    
    /**
     * 设置买家阿里帐号ID(包括淘宝帐号Id)     
     * @param Long $buyerUserId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerUserId( $buyerUserId) {
        $this->buyerUserId = $buyerUserId;
    }
    
        	
    private $id;
    
        /**
    * @return 订单主键ID
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置订单主键ID     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $outOrderId;
    
        /**
    * @return 
    */
        public function getOutOrderId() {
        return $this->outOrderId;
    }
    
    /**
     * 设置     
     * @param String $outOrderId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOutOrderId( $outOrderId) {
        $this->outOrderId = $outOrderId;
    }
    
        	
    private $sellerAlipayId;
    
        /**
    * @return 卖家支付宝id
    */
        public function getSellerAlipayId() {
        return $this->sellerAlipayId;
    }
    
    /**
     * 设置卖家支付宝id     
     * @param String $sellerAlipayId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerAlipayId( $sellerAlipayId) {
        $this->sellerAlipayId = $sellerAlipayId;
    }
    
        	
    private $sellerLoginId;
    
        /**
    * @return 卖家中文站会员登录ID
    */
        public function getSellerLoginId() {
        return $this->sellerLoginId;
    }
    
    /**
     * 设置卖家中文站会员登录ID     
     * @param String $sellerLoginId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerLoginId( $sellerLoginId) {
        $this->sellerLoginId = $sellerLoginId;
    }
    
        	
    private $sellerMemberId;
    
        /**
    * @return 卖家中文站会员ID
    */
        public function getSellerMemberId() {
        return $this->sellerMemberId;
    }
    
    /**
     * 设置卖家中文站会员ID     
     * @param String $sellerMemberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerMemberId( $sellerMemberId) {
        $this->sellerMemberId = $sellerMemberId;
    }
    
        	
    private $sellerUserId;
    
        /**
    * @return 卖家阿里帐号ID(包括淘宝帐号Id)
    */
        public function getSellerUserId() {
        return $this->sellerUserId;
    }
    
    /**
     * 设置卖家阿里帐号ID(包括淘宝帐号Id)     
     * @param Long $sellerUserId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerUserId( $sellerUserId) {
        $this->sellerUserId = $sellerUserId;
    }
    
        	
    private $subBuyerUserId;
    
        /**
    * @return 买家子帐号ID
    */
        public function getSubBuyerUserId() {
        return $this->subBuyerUserId;
    }
    
    /**
     * 设置买家子帐号ID     
     * @param Long $subBuyerUserId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSubBuyerUserId( $subBuyerUserId) {
        $this->subBuyerUserId = $subBuyerUserId;
    }
    
        	
    private $succSumPayment;
    
        /**
    * @return 应付款金额
    */
        public function getSuccSumPayment() {
        return $this->succSumPayment;
    }
    
    /**
     * 设置应付款金额     
     * @param Long $succSumPayment     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSuccSumPayment( $succSumPayment) {
        $this->succSumPayment = $succSumPayment;
    }
    
        	
    private $tbId;
    
        /**
    * @return 淘宝订单主键ID
    */
        public function getTbId() {
        return $this->tbId;
    }
    
    /**
     * 设置淘宝订单主键ID     
     * @param Long $tbId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTbId( $tbId) {
        $this->tbId = $tbId;
    }
    
        	
    private $tradeTypeStr;
    
        /**
    * @return 交易类型
    */
        public function getTradeTypeStr() {
        return $this->tradeTypeStr;
    }
    
    /**
     * 设置交易类型     
     * @param String $tradeTypeStr     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeTypeStr( $tradeTypeStr) {
        $this->tradeTypeStr = $tradeTypeStr;
    }
    
        	
    private $orderEntryModel;
    
        /**
    * @return 子订单明细,简易的子订单模型
    */
        public function getOrderEntryModel() {
        return $this->orderEntryModel;
    }
    
    /**
     * 设置子订单明细,简易的子订单模型     
     * @param array include @see AlibabaopenplatformtradeBizSimpleOrderEntryModel[] $orderEntryModel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderEntryModel(AlibabaopenplatformtradeBizSimpleOrderEntryModel $orderEntryModel) {
        $this->orderEntryModel = $orderEntryModel;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "buyerAlipayId", $this->stdResult )) {
    				$this->buyerAlipayId = $this->stdResult->{"buyerAlipayId"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerLoginId", $this->stdResult )) {
    				$this->buyerLoginId = $this->stdResult->{"buyerLoginId"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerMemberId", $this->stdResult )) {
    				$this->buyerMemberId = $this->stdResult->{"buyerMemberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerUserId", $this->stdResult )) {
    				$this->buyerUserId = $this->stdResult->{"buyerUserId"};
    			}
    			    		    				    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "outOrderId", $this->stdResult )) {
    				$this->outOrderId = $this->stdResult->{"outOrderId"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerAlipayId", $this->stdResult )) {
    				$this->sellerAlipayId = $this->stdResult->{"sellerAlipayId"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerLoginId", $this->stdResult )) {
    				$this->sellerLoginId = $this->stdResult->{"sellerLoginId"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerMemberId", $this->stdResult )) {
    				$this->sellerMemberId = $this->stdResult->{"sellerMemberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerUserId", $this->stdResult )) {
    				$this->sellerUserId = $this->stdResult->{"sellerUserId"};
    			}
    			    		    				    			    			if (array_key_exists ( "subBuyerUserId", $this->stdResult )) {
    				$this->subBuyerUserId = $this->stdResult->{"subBuyerUserId"};
    			}
    			    		    				    			    			if (array_key_exists ( "succSumPayment", $this->stdResult )) {
    				$this->succSumPayment = $this->stdResult->{"succSumPayment"};
    			}
    			    		    				    			    			if (array_key_exists ( "tbId", $this->stdResult )) {
    				$this->tbId = $this->stdResult->{"tbId"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeTypeStr", $this->stdResult )) {
    				$this->tradeTypeStr = $this->stdResult->{"tradeTypeStr"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderEntryModel", $this->stdResult )) {
    			$orderEntryModelResult=$this->stdResult->{"orderEntryModel"};
    				$object = json_decode ( json_encode ( $orderEntryModelResult ), true );
					$this->orderEntryModel = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtradeBizSimpleOrderEntryModelResult=new AlibabaopenplatformtradeBizSimpleOrderEntryModel();
						$AlibabaopenplatformtradeBizSimpleOrderEntryModelResult->setArrayResult($arrayobject );
						$this->orderEntryModel [$i] = $AlibabaopenplatformtradeBizSimpleOrderEntryModelResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "buyerAlipayId", $this->arrayResult )) {
    			$this->buyerAlipayId = $arrayResult['buyerAlipayId'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerLoginId", $this->arrayResult )) {
    			$this->buyerLoginId = $arrayResult['buyerLoginId'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerMemberId", $this->arrayResult )) {
    			$this->buyerMemberId = $arrayResult['buyerMemberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerUserId", $this->arrayResult )) {
    			$this->buyerUserId = $arrayResult['buyerUserId'];
    			}
    		    	    			    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "outOrderId", $this->arrayResult )) {
    			$this->outOrderId = $arrayResult['outOrderId'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerAlipayId", $this->arrayResult )) {
    			$this->sellerAlipayId = $arrayResult['sellerAlipayId'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerLoginId", $this->arrayResult )) {
    			$this->sellerLoginId = $arrayResult['sellerLoginId'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerMemberId", $this->arrayResult )) {
    			$this->sellerMemberId = $arrayResult['sellerMemberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerUserId", $this->arrayResult )) {
    			$this->sellerUserId = $arrayResult['sellerUserId'];
    			}
    		    	    			    		    			if (array_key_exists ( "subBuyerUserId", $this->arrayResult )) {
    			$this->subBuyerUserId = $arrayResult['subBuyerUserId'];
    			}
    		    	    			    		    			if (array_key_exists ( "succSumPayment", $this->arrayResult )) {
    			$this->succSumPayment = $arrayResult['succSumPayment'];
    			}
    		    	    			    		    			if (array_key_exists ( "tbId", $this->arrayResult )) {
    			$this->tbId = $arrayResult['tbId'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeTypeStr", $this->arrayResult )) {
    			$this->tradeTypeStr = $arrayResult['tradeTypeStr'];
    			}
    		    	    			    		    		if (array_key_exists ( "orderEntryModel", $this->arrayResult )) {
    		$orderEntryModelResult=$arrayResult['orderEntryModel'];
    			$this->orderEntryModel = AlibabaopenplatformtradeBizSimpleOrderEntryModel();
    			$this->orderEntryModel->$this->setStdResult ( $orderEntryModelResult);
    		}
    		    	    		}
 
   
}
?>