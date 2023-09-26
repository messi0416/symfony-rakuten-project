<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeSubPayInfo extends SDKDomain {

       	
    private $actualPayFee;
    
        /**
    * @return 
    */
        public function getActualPayFee() {
        return $this->actualPayFee;
    }
    
    /**
     * 设置     
     * @param Long $actualPayFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setActualPayFee( $actualPayFee) {
        $this->actualPayFee = $actualPayFee;
    }
    
        	
    private $additionalFee;
    
        /**
    * @return 
    */
        public function getAdditionalFee() {
        return $this->additionalFee;
    }
    
    /**
     * 设置     
     * @param Long $additionalFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAdditionalFee( $additionalFee) {
        $this->additionalFee = $additionalFee;
    }
    
        	
    private $agreement;
    
        /**
    * @return 
    */
        public function getAgreement() {
        return $this->agreement;
    }
    
    /**
     * 设置     
     * @param String $agreement     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAgreement( $agreement) {
        $this->agreement = $agreement;
    }
    
        	
    private $amount;
    
        /**
    * @return 
    */
        public function getAmount() {
        return $this->amount;
    }
    
    /**
     * 设置     
     * @param Long $amount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAmount( $amount) {
        $this->amount = $amount;
    }
    
        	
    private $buyerConfirmTimeout;
    
        /**
    * @return 
    */
        public function getBuyerConfirmTimeout() {
        return $this->buyerConfirmTimeout;
    }
    
    /**
     * 设置     
     * @param Long $buyerConfirmTimeout     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerConfirmTimeout( $buyerConfirmTimeout) {
        $this->buyerConfirmTimeout = $buyerConfirmTimeout;
    }
    
        	
    private $buyerPayTimeout;
    
        /**
    * @return 
    */
        public function getBuyerPayTimeout() {
        return $this->buyerPayTimeout;
    }
    
    /**
     * 设置     
     * @param Long $buyerPayTimeout     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerPayTimeout( $buyerPayTimeout) {
        $this->buyerPayTimeout = $buyerPayTimeout;
    }
    
        	
    private $deliveryPercent;
    
        /**
    * @return 
    */
        public function getDeliveryPercent() {
        return $this->deliveryPercent;
    }
    
    /**
     * 设置     
     * @param Double $deliveryPercent     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDeliveryPercent( $deliveryPercent) {
        $this->deliveryPercent = $deliveryPercent;
    }
    
        	
    private $discountFee;
    
        /**
    * @return 
    */
        public function getDiscountFee() {
        return $this->discountFee;
    }
    
    /**
     * 设置     
     * @param Long $discountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscountFee( $discountFee) {
        $this->discountFee = $discountFee;
    }
    
        	
    private $group;
    
        /**
    * @return 
    */
        public function getGroup() {
        return $this->group;
    }
    
    /**
     * 设置     
     * @param String $group     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGroup( $group) {
        $this->group = $group;
    }
    
        	
    private $instantPay;
    
        /**
    * @return 
    */
        public function getInstantPay() {
        return $this->instantPay;
    }
    
    /**
     * 设置     
     * @param Integer $instantPay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInstantPay( $instantPay) {
        $this->instantPay = $instantPay;
    }
    
        	
    private $isStepPayAll;
    
        /**
    * @return 
    */
        public function getIsStepPayAll() {
        return $this->isStepPayAll;
    }
    
    /**
     * 设置     
     * @param Integer $isStepPayAll     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsStepPayAll( $isStepPayAll) {
        $this->isStepPayAll = $isStepPayAll;
    }
    
        	
    private $itemDiscountFee;
    
        /**
    * @return 
    */
        public function getItemDiscountFee() {
        return $this->itemDiscountFee;
    }
    
    /**
     * 设置     
     * @param Long $itemDiscountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setItemDiscountFee( $itemDiscountFee) {
        $this->itemDiscountFee = $itemDiscountFee;
    }
    
        	
    private $key;
    
        /**
    * @return 
    */
        public function getKey() {
        return $this->key;
    }
    
    /**
     * 设置     
     * @param String $key     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setKey( $key) {
        $this->key = $key;
    }
    
        	
    private $needLogistics;
    
        /**
    * @return 
    */
        public function getNeedLogistics() {
        return $this->needLogistics;
    }
    
    /**
     * 设置     
     * @param Integer $needLogistics     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedLogistics( $needLogistics) {
        $this->needLogistics = $needLogistics;
    }
    
        	
    private $needSellerAction;
    
        /**
    * @return 
    */
        public function getNeedSellerAction() {
        return $this->needSellerAction;
    }
    
    /**
     * 设置     
     * @param Integer $needSellerAction     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedSellerAction( $needSellerAction) {
        $this->needSellerAction = $needSellerAction;
    }
    
        	
    private $needSellerCallNext;
    
        /**
    * @return 
    */
        public function getNeedSellerCallNext() {
        return $this->needSellerCallNext;
    }
    
    /**
     * 设置     
     * @param Integer $needSellerCallNext     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedSellerCallNext( $needSellerCallNext) {
        $this->needSellerCallNext = $needSellerCallNext;
    }
    
        	
    private $payFee;
    
        /**
    * @return 
    */
        public function getPayFee() {
        return $this->payFee;
    }
    
    /**
     * 设置     
     * @param Long $payFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayFee( $payFee) {
        $this->payFee = $payFee;
    }
    
        	
    private $percent;
    
        /**
    * @return 
    */
        public function getPercent() {
        return $this->percent;
    }
    
    /**
     * 设置     
     * @param Double $percent     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPercent( $percent) {
        $this->percent = $percent;
    }
    
        	
    private $postFee;
    
        /**
    * @return 
    */
        public function getPostFee() {
        return $this->postFee;
    }
    
    /**
     * 设置     
     * @param Long $postFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPostFee( $postFee) {
        $this->postFee = $postFee;
    }
    
        	
    private $price;
    
        /**
    * @return 
    */
        public function getPrice() {
        return $this->price;
    }
    
    /**
     * 设置     
     * @param Long $price     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPrice( $price) {
        $this->price = $price;
    }
    
        	
    private $sellerActionName;
    
        /**
    * @return 
    */
        public function getSellerActionName() {
        return $this->sellerActionName;
    }
    
    /**
     * 设置     
     * @param String $sellerActionName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerActionName( $sellerActionName) {
        $this->sellerActionName = $sellerActionName;
    }
    
        	
    private $stepName;
    
        /**
    * @return 
    */
        public function getStepName() {
        return $this->stepName;
    }
    
    /**
     * 设置     
     * @param String $stepName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStepName( $stepName) {
        $this->stepName = $stepName;
    }
    
        	
    private $stepNo;
    
        /**
    * @return 
    */
        public function getStepNo() {
        return $this->stepNo;
    }
    
    /**
     * 设置     
     * @param Integer $stepNo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStepNo( $stepNo) {
        $this->stepNo = $stepNo;
    }
    
        	
    private $stepTemplateInfo;
    
        /**
    * @return 
    */
        public function getStepTemplateInfo() {
        return $this->stepTemplateInfo;
    }
    
    /**
     * 设置     
     * @param String $stepTemplateInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStepTemplateInfo( $stepTemplateInfo) {
        $this->stepTemplateInfo = $stepTemplateInfo;
    }
    
        	
    private $templateId;
    
        /**
    * @return 
    */
        public function getTemplateId() {
        return $this->templateId;
    }
    
    /**
     * 设置     
     * @param Long $templateId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTemplateId( $templateId) {
        $this->templateId = $templateId;
    }
    
        	
    private $transferAfterConfirm;
    
        /**
    * @return 
    */
        public function getTransferAfterConfirm() {
        return $this->transferAfterConfirm;
    }
    
    /**
     * 设置     
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
    			    		    				    			    			if (array_key_exists ( "additionalFee", $this->stdResult )) {
    				$this->additionalFee = $this->stdResult->{"additionalFee"};
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
    			    		    				    			    			if (array_key_exists ( "deliveryPercent", $this->stdResult )) {
    				$this->deliveryPercent = $this->stdResult->{"deliveryPercent"};
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
    			    		    				    			    			if (array_key_exists ( "key", $this->stdResult )) {
    				$this->key = $this->stdResult->{"key"};
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
    			    		    				    			    			if (array_key_exists ( "postFee", $this->stdResult )) {
    				$this->postFee = $this->stdResult->{"postFee"};
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
    			    		    				    			    			if (array_key_exists ( "stepTemplateInfo", $this->stdResult )) {
    				$this->stepTemplateInfo = $this->stdResult->{"stepTemplateInfo"};
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
    		    	    			    		    			if (array_key_exists ( "additionalFee", $this->arrayResult )) {
    			$this->additionalFee = $arrayResult['additionalFee'];
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
    		    	    			    		    			if (array_key_exists ( "deliveryPercent", $this->arrayResult )) {
    			$this->deliveryPercent = $arrayResult['deliveryPercent'];
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
    		    	    			    		    			if (array_key_exists ( "key", $this->arrayResult )) {
    			$this->key = $arrayResult['key'];
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
    		    	    			    		    			if (array_key_exists ( "postFee", $this->arrayResult )) {
    			$this->postFee = $arrayResult['postFee'];
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
    		    	    			    		    			if (array_key_exists ( "stepTemplateInfo", $this->arrayResult )) {
    			$this->stepTemplateInfo = $arrayResult['stepTemplateInfo'];
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