<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtradeBizStepPayGroup extends SDKDomain {

       	
    private $actualPayFee;
    
        /**
    * @return 实际付款金额，含邮费，减了优惠.
    */
        public function getActualPayFee() {
        return $this->actualPayFee;
    }
    
    /**
     * 设置实际付款金额，含邮费，减了优惠.     
     * @param Long $actualPayFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setActualPayFee( $actualPayFee) {
        $this->actualPayFee = $actualPayFee;
    }
    
        	
    private $agreement;
    
        /**
    * @return 协议路径
    */
        public function getAgreement() {
        return $this->agreement;
    }
    
    /**
     * 设置协议路径     
     * @param String $agreement     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAgreement( $agreement) {
        $this->agreement = $agreement;
    }
    
        	
    private $amount;
    
        /**
    * @return 购买数量
    */
        public function getAmount() {
        return $this->amount;
    }
    
    /**
     * 设置购买数量     
     * @param Long $amount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAmount( $amount) {
        $this->amount = $amount;
    }
    
        	
    private $buyerConfirmTimeout;
    
        /**
    * @return 买家不确认的超时时间
    */
        public function getBuyerConfirmTimeout() {
        return $this->buyerConfirmTimeout;
    }
    
    /**
     * 设置买家不确认的超时时间     
     * @param Long $buyerConfirmTimeout     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerConfirmTimeout( $buyerConfirmTimeout) {
        $this->buyerConfirmTimeout = $buyerConfirmTimeout;
    }
    
        	
    private $buyerPayTimeout;
    
        /**
    * @return 买家不付款的超时时间
    */
        public function getBuyerPayTimeout() {
        return $this->buyerPayTimeout;
    }
    
    /**
     * 设置买家不付款的超时时间     
     * @param Long $buyerPayTimeout     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerPayTimeout( $buyerPayTimeout) {
        $this->buyerPayTimeout = $buyerPayTimeout;
    }
    
        	
    private $discountFee;
    
        /**
    * @return 店铺优惠的分摊
    */
        public function getDiscountFee() {
        return $this->discountFee;
    }
    
    /**
     * 设置店铺优惠的分摊     
     * @param Long $discountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscountFee( $discountFee) {
        $this->discountFee = $discountFee;
    }
    
        	
    private $group;
    
        /**
    * @return 信息所属分组。多订单提交时用来分组。
    */
        public function getGroup() {
        return $this->group;
    }
    
    /**
     * 设置信息所属分组。多订单提交时用来分组。     
     * @param String $group     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGroup( $group) {
        $this->group = $group;
    }
    
        	
    private $instantPay;
    
        /**
    * @return 是否允许即时到帐 1 是 0否
    */
        public function getInstantPay() {
        return $this->instantPay;
    }
    
    /**
     * 设置是否允许即时到帐 1 是 0否     
     * @param Integer $instantPay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInstantPay( $instantPay) {
        $this->instantPay = $instantPay;
    }
    
        	
    private $isStepPayAll;
    
        /**
    * @return 是否一次性付款. 0:不是. 1：是一次性付款.
    */
        public function getIsStepPayAll() {
        return $this->isStepPayAll;
    }
    
    /**
     * 设置是否一次性付款. 0:不是. 1：是一次性付款.     
     * @param Integer $isStepPayAll     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsStepPayAll( $isStepPayAll) {
        $this->isStepPayAll = $isStepPayAll;
    }
    
        	
    private $itemDiscountFee;
    
        /**
    * @return 商品优惠的分摊
    */
        public function getItemDiscountFee() {
        return $this->itemDiscountFee;
    }
    
    /**
     * 设置商品优惠的分摊     
     * @param Long $itemDiscountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setItemDiscountFee( $itemDiscountFee) {
        $this->itemDiscountFee = $itemDiscountFee;
    }
    
        	
    private $mergedJsonVar;
    
        /**
    * @return stepPayGroup模型的页面json格式
    */
        public function getMergedJsonVar() {
        return $this->mergedJsonVar;
    }
    
    /**
     * 设置stepPayGroup模型的页面json格式     
     * @param String $mergedJsonVar     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMergedJsonVar( $mergedJsonVar) {
        $this->mergedJsonVar = $mergedJsonVar;
    }
    
        	
    private $needLogistics;
    
        /**
    * @return 是否需要物流
    */
        public function getNeedLogistics() {
        return $this->needLogistics;
    }
    
    /**
     * 设置是否需要物流     
     * @param Integer $needLogistics     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedLogistics( $needLogistics) {
        $this->needLogistics = $needLogistics;
    }
    
        	
    private $needSellerAction;
    
        /**
    * @return 是否需要卖家操作和买家确认
    */
        public function getNeedSellerAction() {
        return $this->needSellerAction;
    }
    
    /**
     * 设置是否需要卖家操作和买家确认     
     * @param Integer $needSellerAction     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedSellerAction( $needSellerAction) {
        $this->needSellerAction = $needSellerAction;
    }
    
        	
    private $needSellerCallNext;
    
        /**
    * @return 是否需要卖家推进
    */
        public function getNeedSellerCallNext() {
        return $this->needSellerCallNext;
    }
    
    /**
     * 设置是否需要卖家推进     
     * @param Integer $needSellerCallNext     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedSellerCallNext( $needSellerCallNext) {
        $this->needSellerCallNext = $needSellerCallNext;
    }
    
        	
    private $payFee;
    
        /**
    * @return 创建时需要付款的金额，不含邮费
    */
        public function getPayFee() {
        return $this->payFee;
    }
    
    /**
     * 设置创建时需要付款的金额，不含邮费     
     * @param Long $payFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayFee( $payFee) {
        $this->payFee = $payFee;
    }
    
        	
    private $percent;
    
        /**
    * @return 支付金额分摊比例：0~1之间的小数.
    */
        public function getPercent() {
        return $this->percent;
    }
    
    /**
     * 设置支付金额分摊比例：0~1之间的小数.     
     * @param Double $percent     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPercent( $percent) {
        $this->percent = $percent;
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
     * @param Long $price     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPrice( $price) {
        $this->price = $price;
    }
    
        	
    private $sellerActionName;
    
        /**
    * @return 卖家操作的名称
    */
        public function getSellerActionName() {
        return $this->sellerActionName;
    }
    
    /**
     * 设置卖家操作的名称     
     * @param String $sellerActionName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerActionName( $sellerActionName) {
        $this->sellerActionName = $sellerActionName;
    }
    
        	
    private $stepName;
    
        /**
    * @return 当前步骤的名称
    */
        public function getStepName() {
        return $this->stepName;
    }
    
    /**
     * 设置当前步骤的名称     
     * @param String $stepName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStepName( $stepName) {
        $this->stepName = $stepName;
    }
    
        	
    private $stepNo;
    
        /**
    * @return 阶段序列.
    */
        public function getStepNo() {
        return $this->stepNo;
    }
    
    /**
     * 设置阶段序列.     
     * @param Integer $stepNo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStepNo( $stepNo) {
        $this->stepNo = $stepNo;
    }
    
        	
    private $templateId;
    
        /**
    * @return 使用的模板id
    */
        public function getTemplateId() {
        return $this->templateId;
    }
    
    /**
     * 设置使用的模板id     
     * @param Long $templateId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTemplateId( $templateId) {
        $this->templateId = $templateId;
    }
    
        	
    private $transferAfterConfirm;
    
        /**
    * @return 阶段结束是否打款
    */
        public function getTransferAfterConfirm() {
        return $this->transferAfterConfirm;
    }
    
    /**
     * 设置阶段结束是否打款     
     * @param Integer $transferAfterConfirm     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTransferAfterConfirm( $transferAfterConfirm) {
        $this->transferAfterConfirm = $transferAfterConfirm;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "actualPayFee", $this->stdResult )) {
    				$this->actualPayFee = $this->stdResult->{"actualPayFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "agreement", $this->stdResult )) {
    				$this->agreement = $this->stdResult->{"agreement"};
    			}
    			    		    				    			    			if (array_key_exists ( "amount", $this->stdResult )) {
    				$this->amount = $this->stdResult->{"amount"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerConfirmTimeout", $this->stdResult )) {
    				$this->buyerConfirmTimeout = $this->stdResult->{"buyerConfirmTimeout"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerPayTimeout", $this->stdResult )) {
    				$this->buyerPayTimeout = $this->stdResult->{"buyerPayTimeout"};
    			}
    			    		    				    			    			if (array_key_exists ( "discountFee", $this->stdResult )) {
    				$this->discountFee = $this->stdResult->{"discountFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "group", $this->stdResult )) {
    				$this->group = $this->stdResult->{"group"};
    			}
    			    		    				    			    			if (array_key_exists ( "instantPay", $this->stdResult )) {
    				$this->instantPay = $this->stdResult->{"instantPay"};
    			}
    			    		    				    			    			if (array_key_exists ( "isStepPayAll", $this->stdResult )) {
    				$this->isStepPayAll = $this->stdResult->{"isStepPayAll"};
    			}
    			    		    				    			    			if (array_key_exists ( "itemDiscountFee", $this->stdResult )) {
    				$this->itemDiscountFee = $this->stdResult->{"itemDiscountFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "mergedJsonVar", $this->stdResult )) {
    				$this->mergedJsonVar = $this->stdResult->{"mergedJsonVar"};
    			}
    			    		    				    			    			if (array_key_exists ( "needLogistics", $this->stdResult )) {
    				$this->needLogistics = $this->stdResult->{"needLogistics"};
    			}
    			    		    				    			    			if (array_key_exists ( "needSellerAction", $this->stdResult )) {
    				$this->needSellerAction = $this->stdResult->{"needSellerAction"};
    			}
    			    		    				    			    			if (array_key_exists ( "needSellerCallNext", $this->stdResult )) {
    				$this->needSellerCallNext = $this->stdResult->{"needSellerCallNext"};
    			}
    			    		    				    			    			if (array_key_exists ( "payFee", $this->stdResult )) {
    				$this->payFee = $this->stdResult->{"payFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "percent", $this->stdResult )) {
    				$this->percent = $this->stdResult->{"percent"};
    			}
    			    		    				    			    			if (array_key_exists ( "price", $this->stdResult )) {
    				$this->price = $this->stdResult->{"price"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerActionName", $this->stdResult )) {
    				$this->sellerActionName = $this->stdResult->{"sellerActionName"};
    			}
    			    		    				    			    			if (array_key_exists ( "stepName", $this->stdResult )) {
    				$this->stepName = $this->stdResult->{"stepName"};
    			}
    			    		    				    			    			if (array_key_exists ( "stepNo", $this->stdResult )) {
    				$this->stepNo = $this->stdResult->{"stepNo"};
    			}
    			    		    				    			    			if (array_key_exists ( "templateId", $this->stdResult )) {
    				$this->templateId = $this->stdResult->{"templateId"};
    			}
    			    		    				    			    			if (array_key_exists ( "transferAfterConfirm", $this->stdResult )) {
    				$this->transferAfterConfirm = $this->stdResult->{"transferAfterConfirm"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "actualPayFee", $this->arrayResult )) {
    			$this->actualPayFee = $arrayResult['actualPayFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "agreement", $this->arrayResult )) {
    			$this->agreement = $arrayResult['agreement'];
    			}
    		    	    			    		    			if (array_key_exists ( "amount", $this->arrayResult )) {
    			$this->amount = $arrayResult['amount'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerConfirmTimeout", $this->arrayResult )) {
    			$this->buyerConfirmTimeout = $arrayResult['buyerConfirmTimeout'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerPayTimeout", $this->arrayResult )) {
    			$this->buyerPayTimeout = $arrayResult['buyerPayTimeout'];
    			}
    		    	    			    		    			if (array_key_exists ( "discountFee", $this->arrayResult )) {
    			$this->discountFee = $arrayResult['discountFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "group", $this->arrayResult )) {
    			$this->group = $arrayResult['group'];
    			}
    		    	    			    		    			if (array_key_exists ( "instantPay", $this->arrayResult )) {
    			$this->instantPay = $arrayResult['instantPay'];
    			}
    		    	    			    		    			if (array_key_exists ( "isStepPayAll", $this->arrayResult )) {
    			$this->isStepPayAll = $arrayResult['isStepPayAll'];
    			}
    		    	    			    		    			if (array_key_exists ( "itemDiscountFee", $this->arrayResult )) {
    			$this->itemDiscountFee = $arrayResult['itemDiscountFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "mergedJsonVar", $this->arrayResult )) {
    			$this->mergedJsonVar = $arrayResult['mergedJsonVar'];
    			}
    		    	    			    		    			if (array_key_exists ( "needLogistics", $this->arrayResult )) {
    			$this->needLogistics = $arrayResult['needLogistics'];
    			}
    		    	    			    		    			if (array_key_exists ( "needSellerAction", $this->arrayResult )) {
    			$this->needSellerAction = $arrayResult['needSellerAction'];
    			}
    		    	    			    		    			if (array_key_exists ( "needSellerCallNext", $this->arrayResult )) {
    			$this->needSellerCallNext = $arrayResult['needSellerCallNext'];
    			}
    		    	    			    		    			if (array_key_exists ( "payFee", $this->arrayResult )) {
    			$this->payFee = $arrayResult['payFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "percent", $this->arrayResult )) {
    			$this->percent = $arrayResult['percent'];
    			}
    		    	    			    		    			if (array_key_exists ( "price", $this->arrayResult )) {
    			$this->price = $arrayResult['price'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerActionName", $this->arrayResult )) {
    			$this->sellerActionName = $arrayResult['sellerActionName'];
    			}
    		    	    			    		    			if (array_key_exists ( "stepName", $this->arrayResult )) {
    			$this->stepName = $arrayResult['stepName'];
    			}
    		    	    			    		    			if (array_key_exists ( "stepNo", $this->arrayResult )) {
    			$this->stepNo = $arrayResult['stepNo'];
    			}
    		    	    			    		    			if (array_key_exists ( "templateId", $this->arrayResult )) {
    			$this->templateId = $arrayResult['templateId'];
    			}
    		    	    			    		    			if (array_key_exists ( "transferAfterConfirm", $this->arrayResult )) {
    			$this->transferAfterConfirm = $arrayResult['transferAfterConfirm'];
    			}
    		    	    		}
 
   
}
?>