<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtrademodelOrderBaseInfo extends SDKDomain {

       	
    private $allDeliveredTime;
    
        /**
    * @return 完全发货时间
    */
        public function getAllDeliveredTime() {
        return $this->allDeliveredTime;
    }
    
    /**
     * 设置完全发货时间     
     * @param Date $allDeliveredTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAllDeliveredTime( $allDeliveredTime) {
        $this->allDeliveredTime = $allDeliveredTime;
    }
    
        	
    private $businessType;
    
        /**
    * @return 业务类型。国际站：ta(信保),wholesale(在线批发)
    */
        public function getBusinessType() {
        return $this->businessType;
    }
    
    /**
     * 设置业务类型。国际站：ta(信保),wholesale(在线批发)     
     * @param String $businessType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBusinessType( $businessType) {
        $this->businessType = $businessType;
    }
    
        	
    private $buyerID;
    
        /**
    * @return 买家主账号id
    */
        public function getBuyerID() {
        return $this->buyerID;
    }
    
    /**
     * 设置买家主账号id     
     * @param String $buyerID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerID( $buyerID) {
        $this->buyerID = $buyerID;
    }
    
        	
    private $buyerMemo;
    
        /**
    * @return 买家备忘信息
    */
        public function getBuyerMemo() {
        return $this->buyerMemo;
    }
    
    /**
     * 设置买家备忘信息     
     * @param String $buyerMemo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerMemo( $buyerMemo) {
        $this->buyerMemo = $buyerMemo;
    }
    
        	
    private $buyerSubID;
    
        /**
    * @return 买家子账号id，1688无此内容
    */
        public function getBuyerSubID() {
        return $this->buyerSubID;
    }
    
    /**
     * 设置买家子账号id，1688无此内容     
     * @param Long $buyerSubID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerSubID( $buyerSubID) {
        $this->buyerSubID = $buyerSubID;
    }
    
        	
    private $completeTime;
    
        /**
    * @return 完成时间
    */
        public function getCompleteTime() {
        return $this->completeTime;
    }
    
    /**
     * 设置完成时间     
     * @param Date $completeTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCompleteTime( $completeTime) {
        $this->completeTime = $completeTime;
    }
    
        	
    private $createTime;
    
        /**
    * @return 创建时间
    */
        public function getCreateTime() {
        return $this->createTime;
    }
    
    /**
     * 设置创建时间     
     * @param Date $createTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCreateTime( $createTime) {
        $this->createTime = $createTime;
    }
    
        	
    private $currency;
    
        /**
    * @return 币种，币种，整个交易单使用同一个币种。值范围：USD,RMB,HKD,GBP,CAD,AUD,JPY,KRW,EUR
    */
        public function getCurrency() {
        return $this->currency;
    }
    
    /**
     * 设置币种，币种，整个交易单使用同一个币种。值范围：USD,RMB,HKD,GBP,CAD,AUD,JPY,KRW,EUR     
     * @param String $currency     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCurrency( $currency) {
        $this->currency = $currency;
    }
    
        	
    private $id;
    
        /**
    * @return 交易id
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置交易id     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $modifyTime;
    
        /**
    * @return 修改时间
    */
        public function getModifyTime() {
        return $this->modifyTime;
    }
    
    /**
     * 设置修改时间     
     * @param Date $modifyTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setModifyTime( $modifyTime) {
        $this->modifyTime = $modifyTime;
    }
    
        	
    private $payTime;
    
        /**
    * @return 付款时间，如果有多次付款，这里返回的是首次付款时间
    */
        public function getPayTime() {
        return $this->payTime;
    }
    
    /**
     * 设置付款时间，如果有多次付款，这里返回的是首次付款时间     
     * @param Date $payTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayTime( $payTime) {
        $this->payTime = $payTime;
    }
    
        	
    private $receivingTime;
    
        /**
    * @return 收货时间，这里返回的是完全收货时间
    */
        public function getReceivingTime() {
        return $this->receivingTime;
    }
    
    /**
     * 设置收货时间，这里返回的是完全收货时间     
     * @param Date $receivingTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setReceivingTime( $receivingTime) {
        $this->receivingTime = $receivingTime;
    }
    
        	
    private $refund;
    
        /**
    * @return 退款金额，单位为元
    */
        public function getRefund() {
        return $this->refund;
    }
    
    /**
     * 设置退款金额，单位为元     
     * @param BigDecimal $refund     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRefund( $refund) {
        $this->refund = $refund;
    }
    
        	
    private $remark;
    
        /**
    * @return 备注，1688指下单时的备注
    */
        public function getRemark() {
        return $this->remark;
    }
    
    /**
     * 设置备注，1688指下单时的备注     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->remark = $remark;
    }
    
        	
    private $sellerID;
    
        /**
    * @return 卖家主账号id
    */
        public function getSellerID() {
        return $this->sellerID;
    }
    
    /**
     * 设置卖家主账号id     
     * @param String $sellerID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerID( $sellerID) {
        $this->sellerID = $sellerID;
    }
    
        	
    private $sellerMemo;
    
        /**
    * @return 卖家备忘信息
    */
        public function getSellerMemo() {
        return $this->sellerMemo;
    }
    
    /**
     * 设置卖家备忘信息     
     * @param String $sellerMemo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerMemo( $sellerMemo) {
        $this->sellerMemo = $sellerMemo;
    }
    
        	
    private $sellerSubID;
    
        /**
    * @return 卖家子账号id，1688无此内容
    */
        public function getSellerSubID() {
        return $this->sellerSubID;
    }
    
    /**
     * 设置卖家子账号id，1688无此内容     
     * @param Long $sellerSubID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerSubID( $sellerSubID) {
        $this->sellerSubID = $sellerSubID;
    }
    
        	
    private $shippingFee;
    
        /**
    * @return 运费，单位为元
    */
        public function getShippingFee() {
        return $this->shippingFee;
    }
    
    /**
     * 设置运费，单位为元     
     * @param BigDecimal $shippingFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShippingFee( $shippingFee) {
        $this->shippingFee = $shippingFee;
    }
    
        	
    private $status;
    
        /**
    * @return 交易状态
    */
        public function getStatus() {
        return $this->status;
    }
    
    /**
     * 设置交易状态     
     * @param String $status     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStatus( $status) {
        $this->status = $status;
    }
    
        	
    private $totalAmount;
    
        /**
    * @return 应付款总金额，totalAmount = ∑itemAmount + shippingFee，单位为元
    */
        public function getTotalAmount() {
        return $this->totalAmount;
    }
    
    /**
     * 设置应付款总金额，totalAmount = ∑itemAmount + shippingFee，单位为元     
     * @param BigDecimal $totalAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTotalAmount( $totalAmount) {
        $this->totalAmount = $totalAmount;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "allDeliveredTime", $this->stdResult )) {
    				$this->allDeliveredTime = $this->stdResult->{"allDeliveredTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "businessType", $this->stdResult )) {
    				$this->businessType = $this->stdResult->{"businessType"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerID", $this->stdResult )) {
    				$this->buyerID = $this->stdResult->{"buyerID"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerMemo", $this->stdResult )) {
    				$this->buyerMemo = $this->stdResult->{"buyerMemo"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerSubID", $this->stdResult )) {
    				$this->buyerSubID = $this->stdResult->{"buyerSubID"};
    			}
    			    		    				    			    			if (array_key_exists ( "completeTime", $this->stdResult )) {
    				$this->completeTime = $this->stdResult->{"completeTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "createTime", $this->stdResult )) {
    				$this->createTime = $this->stdResult->{"createTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "currency", $this->stdResult )) {
    				$this->currency = $this->stdResult->{"currency"};
    			}
    			    		    				    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "modifyTime", $this->stdResult )) {
    				$this->modifyTime = $this->stdResult->{"modifyTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "payTime", $this->stdResult )) {
    				$this->payTime = $this->stdResult->{"payTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "receivingTime", $this->stdResult )) {
    				$this->receivingTime = $this->stdResult->{"receivingTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "refund", $this->stdResult )) {
    				$this->refund = $this->stdResult->{"refund"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerID", $this->stdResult )) {
    				$this->sellerID = $this->stdResult->{"sellerID"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerMemo", $this->stdResult )) {
    				$this->sellerMemo = $this->stdResult->{"sellerMemo"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerSubID", $this->stdResult )) {
    				$this->sellerSubID = $this->stdResult->{"sellerSubID"};
    			}
    			    		    				    			    			if (array_key_exists ( "shippingFee", $this->stdResult )) {
    				$this->shippingFee = $this->stdResult->{"shippingFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "status", $this->stdResult )) {
    				$this->status = $this->stdResult->{"status"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalAmount", $this->stdResult )) {
    				$this->totalAmount = $this->stdResult->{"totalAmount"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "allDeliveredTime", $this->arrayResult )) {
    			$this->allDeliveredTime = $arrayResult['allDeliveredTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "businessType", $this->arrayResult )) {
    			$this->businessType = $arrayResult['businessType'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerID", $this->arrayResult )) {
    			$this->buyerID = $arrayResult['buyerID'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerMemo", $this->arrayResult )) {
    			$this->buyerMemo = $arrayResult['buyerMemo'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerSubID", $this->arrayResult )) {
    			$this->buyerSubID = $arrayResult['buyerSubID'];
    			}
    		    	    			    		    			if (array_key_exists ( "completeTime", $this->arrayResult )) {
    			$this->completeTime = $arrayResult['completeTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "createTime", $this->arrayResult )) {
    			$this->createTime = $arrayResult['createTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "currency", $this->arrayResult )) {
    			$this->currency = $arrayResult['currency'];
    			}
    		    	    			    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "modifyTime", $this->arrayResult )) {
    			$this->modifyTime = $arrayResult['modifyTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "payTime", $this->arrayResult )) {
    			$this->payTime = $arrayResult['payTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "receivingTime", $this->arrayResult )) {
    			$this->receivingTime = $arrayResult['receivingTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "refund", $this->arrayResult )) {
    			$this->refund = $arrayResult['refund'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerID", $this->arrayResult )) {
    			$this->sellerID = $arrayResult['sellerID'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerMemo", $this->arrayResult )) {
    			$this->sellerMemo = $arrayResult['sellerMemo'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerSubID", $this->arrayResult )) {
    			$this->sellerSubID = $arrayResult['sellerSubID'];
    			}
    		    	    			    		    			if (array_key_exists ( "shippingFee", $this->arrayResult )) {
    			$this->shippingFee = $arrayResult['shippingFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "status", $this->arrayResult )) {
    			$this->status = $arrayResult['status'];
    			}
    		    	    			    		    			if (array_key_exists ( "totalAmount", $this->arrayResult )) {
    			$this->totalAmount = $arrayResult['totalAmount'];
    			}
    		    	    		}
 
   
}
?>