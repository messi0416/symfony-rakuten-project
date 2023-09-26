<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeSimpleBuyer.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabapaymentPayChannels.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeSimpleSeller.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeSimpleSeller.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeTradeModeModel.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeToleranceCollection.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeCargo.class.php');

class AlibabatradeMakeOrderModel extends SDKDomain {

       	
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
    
        	
    private $auccountPeriod;
    
        /**
    * @return 
    */
        public function getAuccountPeriod() {
        return $this->auccountPeriod;
    }
    
    /**
     * 设置     
     * @param Integer $auccountPeriod     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAuccountPeriod( $auccountPeriod) {
        $this->auccountPeriod = $auccountPeriod;
    }
    
        	
    private $bizGroup;
    
        /**
    * @return 
    */
        public function getBizGroup() {
        return $this->bizGroup;
    }
    
    /**
     * 设置     
     * @param String $bizGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBizGroup( $bizGroup) {
        $this->bizGroup = $bizGroup;
    }
    
        	
    private $client;
    
        /**
    * @return 
    */
        public function getClient() {
        return $this->client;
    }
    
    /**
     * 设置     
     * @param String $client     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setClient( $client) {
        $this->client = $client;
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
    
        	
    private $famousStep;
    
        /**
    * @return 
    */
        public function getFamousStep() {
        return $this->famousStep;
    }
    
    /**
     * 设置     
     * @param Boolean $famousStep     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFamousStep( $famousStep) {
        $this->famousStep = $famousStep;
    }
    
        	
    private $flowFlag;
    
        /**
    * @return 
    */
        public function getFlowFlag() {
        return $this->flowFlag;
    }
    
    /**
     * 设置     
     * @param String $flowFlag     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFlowFlag( $flowFlag) {
        $this->flowFlag = $flowFlag;
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
    
        	
    private $hasBeenDealtWireless;
    
        /**
    * @return 
    */
        public function getHasBeenDealtWireless() {
        return $this->hasBeenDealtWireless;
    }
    
    /**
     * 设置     
     * @param Boolean $hasBeenDealtWireless     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setHasBeenDealtWireless( $hasBeenDealtWireless) {
        $this->hasBeenDealtWireless = $hasBeenDealtWireless;
    }
    
        	
    private $instantSenceQuota;
    
        /**
    * @return 
    */
        public function getInstantSenceQuota() {
        return $this->instantSenceQuota;
    }
    
    /**
     * 设置     
     * @param Long $instantSenceQuota     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInstantSenceQuota( $instantSenceQuota) {
        $this->instantSenceQuota = $instantSenceQuota;
    }
    
        	
    private $instantSenceRaiseQuota;
    
        /**
    * @return 
    */
        public function getInstantSenceRaiseQuota() {
        return $this->instantSenceRaiseQuota;
    }
    
    /**
     * 设置     
     * @param Long $instantSenceRaiseQuota     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInstantSenceRaiseQuota( $instantSenceRaiseQuota) {
        $this->instantSenceRaiseQuota = $instantSenceRaiseQuota;
    }
    
        	
    private $isSupportNormalPayInsant;
    
        /**
    * @return 
    */
        public function getIsSupportNormalPayInsant() {
        return $this->isSupportNormalPayInsant;
    }
    
    /**
     * 设置     
     * @param Integer $isSupportNormalPayInsant     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSupportNormalPayInsant( $isSupportNormalPayInsant) {
        $this->isSupportNormalPayInsant = $isSupportNormalPayInsant;
    }
    
        	
    private $message;
    
        /**
    * @return 
    */
        public function getMessage() {
        return $this->message;
    }
    
    /**
     * 设置     
     * @param String $message     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMessage( $message) {
        $this->message = $message;
    }
    
        	
    private $remark;
    
        /**
    * @return 
    */
        public function getRemark() {
        return $this->remark;
    }
    
    /**
     * 设置     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->remark = $remark;
    }
    
        	
    private $resultCode;
    
        /**
    * @return 
    */
        public function getResultCode() {
        return $this->resultCode;
    }
    
    /**
     * 设置     
     * @param String $resultCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResultCode( $resultCode) {
        $this->resultCode = $resultCode;
    }
    
        	
    private $status;
    
        /**
    * @return 
    */
        public function getStatus() {
        return $this->status;
    }
    
    /**
     * 设置     
     * @param Boolean $status     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStatus( $status) {
        $this->status = $status;
    }
    
        	
    private $subBizType;
    
        /**
    * @return 
    */
        public function getSubBizType() {
        return $this->subBizType;
    }
    
    /**
     * 设置     
     * @param String $subBizType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSubBizType( $subBizType) {
        $this->subBizType = $subBizType;
    }
    
        	
    private $sumCarriage;
    
        /**
    * @return 
    */
        public function getSumCarriage() {
        return $this->sumCarriage;
    }
    
    /**
     * 设置     
     * @param Long $sumCarriage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSumCarriage( $sumCarriage) {
        $this->sumCarriage = $sumCarriage;
    }
    
        	
    private $sumPayment;
    
        /**
    * @return 
    */
        public function getSumPayment() {
        return $this->sumPayment;
    }
    
    /**
     * 设置     
     * @param Long $sumPayment     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSumPayment( $sumPayment) {
        $this->sumPayment = $sumPayment;
    }
    
        	
    private $sumPaymentNoCarriage;
    
        /**
    * @return 
    */
        public function getSumPaymentNoCarriage() {
        return $this->sumPaymentNoCarriage;
    }
    
    /**
     * 设置     
     * @param Long $sumPaymentNoCarriage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSumPaymentNoCarriage( $sumPaymentNoCarriage) {
        $this->sumPaymentNoCarriage = $sumPaymentNoCarriage;
    }
    
        	
    private $supportInvoice;
    
        /**
    * @return 
    */
        public function getSupportInvoice() {
        return $this->supportInvoice;
    }
    
    /**
     * 设置     
     * @param Boolean $supportInvoice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupportInvoice( $supportInvoice) {
        $this->supportInvoice = $supportInvoice;
    }
    
        	
    private $supportStepPay;
    
        /**
    * @return 
    */
        public function getSupportStepPay() {
        return $this->supportStepPay;
    }
    
    /**
     * 设置     
     * @param String $supportStepPay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupportStepPay( $supportStepPay) {
        $this->supportStepPay = $supportStepPay;
    }
    
        	
    private $buyer;
    
        /**
    * @return 
    */
        public function getBuyer() {
        return $this->buyer;
    }
    
    /**
     * 设置     
     * @param AlibabatradeSimpleBuyer $buyer     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyer(AlibabatradeSimpleBuyer $buyer) {
        $this->buyer = $buyer;
    }
    
        	
    private $payChannels;
    
        /**
    * @return 
    */
        public function getPayChannels() {
        return $this->payChannels;
    }
    
    /**
     * 设置     
     * @param AlibabapaymentPayChannels $payChannels     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayChannels(AlibabapaymentPayChannels $payChannels) {
        $this->payChannels = $payChannels;
    }
    
        	
    private $seller;
    
        /**
    * @return 
    */
        public function getSeller() {
        return $this->seller;
    }
    
    /**
     * 设置     
     * @param AlibabatradeSimpleSeller $seller     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSeller(AlibabatradeSimpleSeller $seller) {
        $this->seller = $seller;
    }
    
        	
    private $simpleSeller;
    
        /**
    * @return 
    */
        public function getSimpleSeller() {
        return $this->simpleSeller;
    }
    
    /**
     * 设置     
     * @param AlibabatradeSimpleSeller $simpleSeller     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSimpleSeller(AlibabatradeSimpleSeller $simpleSeller) {
        $this->simpleSeller = $simpleSeller;
    }
    
        	
    private $tradeModeModel;
    
        /**
    * @return 
    */
        public function getTradeModeModel() {
        return $this->tradeModeModel;
    }
    
    /**
     * 设置     
     * @param AlibabatradeTradeModeModel $tradeModeModel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeModeModel(AlibabatradeTradeModeModel $tradeModeModel) {
        $this->tradeModeModel = $tradeModeModel;
    }
    
        	
    private $toleranceCollection;
    
        /**
    * @return 
    */
        public function getToleranceCollection() {
        return $this->toleranceCollection;
    }
    
    /**
     * 设置     
     * @param AlibabatradeToleranceCollection $toleranceCollection     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setToleranceCollection(AlibabatradeToleranceCollection $toleranceCollection) {
        $this->toleranceCollection = $toleranceCollection;
    }
    
        	
    private $cargos;
    
        /**
    * @return 货品信息
    */
        public function getCargos() {
        return $this->cargos;
    }
    
    /**
     * 设置货品信息     
     * @param array include @see AlibabatradeCargo[] $cargos     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCargos(AlibabatradeCargo $cargos) {
        $this->cargos = $cargos;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "additionalFee", $this->stdResult )) {
    				$this->additionalFee = $this->stdResult->{"additionalFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "auccountPeriod", $this->stdResult )) {
    				$this->auccountPeriod = $this->stdResult->{"auccountPeriod"};
    			}
    			    		    				    			    			if (array_key_exists ( "bizGroup", $this->stdResult )) {
    				$this->bizGroup = $this->stdResult->{"bizGroup"};
    			}
    			    		    				    			    			if (array_key_exists ( "client", $this->stdResult )) {
    				$this->client = $this->stdResult->{"client"};
    			}
    			    		    				    			    			if (array_key_exists ( "discountFee", $this->stdResult )) {
    				$this->discountFee = $this->stdResult->{"discountFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "famousStep", $this->stdResult )) {
    				$this->famousStep = $this->stdResult->{"famousStep"};
    			}
    			    		    				    			    			if (array_key_exists ( "flowFlag", $this->stdResult )) {
    				$this->flowFlag = $this->stdResult->{"flowFlag"};
    			}
    			    		    				    			    			if (array_key_exists ( "group", $this->stdResult )) {
    				$this->group = $this->stdResult->{"group"};
    			}
    			    		    				    			    			if (array_key_exists ( "hasBeenDealtWireless", $this->stdResult )) {
    				$this->hasBeenDealtWireless = $this->stdResult->{"hasBeenDealtWireless"};
    			}
    			    		    				    			    			if (array_key_exists ( "instantSenceQuota", $this->stdResult )) {
    				$this->instantSenceQuota = $this->stdResult->{"instantSenceQuota"};
    			}
    			    		    				    			    			if (array_key_exists ( "instantSenceRaiseQuota", $this->stdResult )) {
    				$this->instantSenceRaiseQuota = $this->stdResult->{"instantSenceRaiseQuota"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSupportNormalPayInsant", $this->stdResult )) {
    				$this->isSupportNormalPayInsant = $this->stdResult->{"isSupportNormalPayInsant"};
    			}
    			    		    				    			    			if (array_key_exists ( "message", $this->stdResult )) {
    				$this->message = $this->stdResult->{"message"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    				    			    			if (array_key_exists ( "resultCode", $this->stdResult )) {
    				$this->resultCode = $this->stdResult->{"resultCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "status", $this->stdResult )) {
    				$this->status = $this->stdResult->{"status"};
    			}
    			    		    				    			    			if (array_key_exists ( "subBizType", $this->stdResult )) {
    				$this->subBizType = $this->stdResult->{"subBizType"};
    			}
    			    		    				    			    			if (array_key_exists ( "sumCarriage", $this->stdResult )) {
    				$this->sumCarriage = $this->stdResult->{"sumCarriage"};
    			}
    			    		    				    			    			if (array_key_exists ( "sumPayment", $this->stdResult )) {
    				$this->sumPayment = $this->stdResult->{"sumPayment"};
    			}
    			    		    				    			    			if (array_key_exists ( "sumPaymentNoCarriage", $this->stdResult )) {
    				$this->sumPaymentNoCarriage = $this->stdResult->{"sumPaymentNoCarriage"};
    			}
    			    		    				    			    			if (array_key_exists ( "supportInvoice", $this->stdResult )) {
    				$this->supportInvoice = $this->stdResult->{"supportInvoice"};
    			}
    			    		    				    			    			if (array_key_exists ( "supportStepPay", $this->stdResult )) {
    				$this->supportStepPay = $this->stdResult->{"supportStepPay"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyer", $this->stdResult )) {
    				$buyerResult=$this->stdResult->{"buyer"};
    				$this->buyer = new AlibabatradeSimpleBuyer();
    				$this->buyer->setStdResult ( $buyerResult);
    			}
    			    		    				    			    			if (array_key_exists ( "payChannels", $this->stdResult )) {
    				$payChannelsResult=$this->stdResult->{"payChannels"};
    				$this->payChannels = new AlibabapaymentPayChannels();
    				$this->payChannels->setStdResult ( $payChannelsResult);
    			}
    			    		    				    			    			if (array_key_exists ( "seller", $this->stdResult )) {
    				$sellerResult=$this->stdResult->{"seller"};
    				$this->seller = new AlibabatradeSimpleSeller();
    				$this->seller->setStdResult ( $sellerResult);
    			}
    			    		    				    			    			if (array_key_exists ( "simpleSeller", $this->stdResult )) {
    				$simpleSellerResult=$this->stdResult->{"simpleSeller"};
    				$this->simpleSeller = new AlibabatradeSimpleSeller();
    				$this->simpleSeller->setStdResult ( $simpleSellerResult);
    			}
    			    		    				    			    			if (array_key_exists ( "tradeModeModel", $this->stdResult )) {
    				$tradeModeModelResult=$this->stdResult->{"tradeModeModel"};
    				$this->tradeModeModel = new AlibabatradeTradeModeModel();
    				$this->tradeModeModel->setStdResult ( $tradeModeModelResult);
    			}
    			    		    				    			    			if (array_key_exists ( "toleranceCollection", $this->stdResult )) {
    				$toleranceCollectionResult=$this->stdResult->{"toleranceCollection"};
    				$this->toleranceCollection = new AlibabatradeToleranceCollection();
    				$this->toleranceCollection->setStdResult ( $toleranceCollectionResult);
    			}
    			    		    				    			    			if (array_key_exists ( "cargos", $this->stdResult )) {
    			$cargosResult=$this->stdResult->{"cargos"};
    				$object = json_decode ( json_encode ( $cargosResult ), true );
					$this->cargos = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradeCargoResult=new AlibabatradeCargo();
						$AlibabatradeCargoResult->setArrayResult($arrayobject );
						$this->cargos [$i] = $AlibabatradeCargoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "additionalFee", $this->arrayResult )) {
    			$this->additionalFee = $arrayResult['additionalFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "auccountPeriod", $this->arrayResult )) {
    			$this->auccountPeriod = $arrayResult['auccountPeriod'];
    			}
    		    	    			    		    			if (array_key_exists ( "bizGroup", $this->arrayResult )) {
    			$this->bizGroup = $arrayResult['bizGroup'];
    			}
    		    	    			    		    			if (array_key_exists ( "client", $this->arrayResult )) {
    			$this->client = $arrayResult['client'];
    			}
    		    	    			    		    			if (array_key_exists ( "discountFee", $this->arrayResult )) {
    			$this->discountFee = $arrayResult['discountFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "famousStep", $this->arrayResult )) {
    			$this->famousStep = $arrayResult['famousStep'];
    			}
    		    	    			    		    			if (array_key_exists ( "flowFlag", $this->arrayResult )) {
    			$this->flowFlag = $arrayResult['flowFlag'];
    			}
    		    	    			    		    			if (array_key_exists ( "group", $this->arrayResult )) {
    			$this->group = $arrayResult['group'];
    			}
    		    	    			    		    			if (array_key_exists ( "hasBeenDealtWireless", $this->arrayResult )) {
    			$this->hasBeenDealtWireless = $arrayResult['hasBeenDealtWireless'];
    			}
    		    	    			    		    			if (array_key_exists ( "instantSenceQuota", $this->arrayResult )) {
    			$this->instantSenceQuota = $arrayResult['instantSenceQuota'];
    			}
    		    	    			    		    			if (array_key_exists ( "instantSenceRaiseQuota", $this->arrayResult )) {
    			$this->instantSenceRaiseQuota = $arrayResult['instantSenceRaiseQuota'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSupportNormalPayInsant", $this->arrayResult )) {
    			$this->isSupportNormalPayInsant = $arrayResult['isSupportNormalPayInsant'];
    			}
    		    	    			    		    			if (array_key_exists ( "message", $this->arrayResult )) {
    			$this->message = $arrayResult['message'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    			    		    			if (array_key_exists ( "resultCode", $this->arrayResult )) {
    			$this->resultCode = $arrayResult['resultCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "status", $this->arrayResult )) {
    			$this->status = $arrayResult['status'];
    			}
    		    	    			    		    			if (array_key_exists ( "subBizType", $this->arrayResult )) {
    			$this->subBizType = $arrayResult['subBizType'];
    			}
    		    	    			    		    			if (array_key_exists ( "sumCarriage", $this->arrayResult )) {
    			$this->sumCarriage = $arrayResult['sumCarriage'];
    			}
    		    	    			    		    			if (array_key_exists ( "sumPayment", $this->arrayResult )) {
    			$this->sumPayment = $arrayResult['sumPayment'];
    			}
    		    	    			    		    			if (array_key_exists ( "sumPaymentNoCarriage", $this->arrayResult )) {
    			$this->sumPaymentNoCarriage = $arrayResult['sumPaymentNoCarriage'];
    			}
    		    	    			    		    			if (array_key_exists ( "supportInvoice", $this->arrayResult )) {
    			$this->supportInvoice = $arrayResult['supportInvoice'];
    			}
    		    	    			    		    			if (array_key_exists ( "supportStepPay", $this->arrayResult )) {
    			$this->supportStepPay = $arrayResult['supportStepPay'];
    			}
    		    	    			    		    		if (array_key_exists ( "buyer", $this->arrayResult )) {
    		$buyerResult=$arrayResult['buyer'];
    			    			$this->buyer = new AlibabatradeSimpleBuyer();
    			    			$this->buyer->$this->setStdResult ( $buyerResult);
    		}
    		    	    			    		    		if (array_key_exists ( "payChannels", $this->arrayResult )) {
    		$payChannelsResult=$arrayResult['payChannels'];
    			    			$this->payChannels = new AlibabapaymentPayChannels();
    			    			$this->payChannels->$this->setStdResult ( $payChannelsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "seller", $this->arrayResult )) {
    		$sellerResult=$arrayResult['seller'];
    			    			$this->seller = new AlibabatradeSimpleSeller();
    			    			$this->seller->$this->setStdResult ( $sellerResult);
    		}
    		    	    			    		    		if (array_key_exists ( "simpleSeller", $this->arrayResult )) {
    		$simpleSellerResult=$arrayResult['simpleSeller'];
    			    			$this->simpleSeller = new AlibabatradeSimpleSeller();
    			    			$this->simpleSeller->$this->setStdResult ( $simpleSellerResult);
    		}
    		    	    			    		    		if (array_key_exists ( "tradeModeModel", $this->arrayResult )) {
    		$tradeModeModelResult=$arrayResult['tradeModeModel'];
    			    			$this->tradeModeModel = new AlibabatradeTradeModeModel();
    			    			$this->tradeModeModel->$this->setStdResult ( $tradeModeModelResult);
    		}
    		    	    			    		    		if (array_key_exists ( "toleranceCollection", $this->arrayResult )) {
    		$toleranceCollectionResult=$arrayResult['toleranceCollection'];
    			    			$this->toleranceCollection = new AlibabatradeToleranceCollection();
    			    			$this->toleranceCollection->$this->setStdResult ( $toleranceCollectionResult);
    		}
    		    	    			    		    		if (array_key_exists ( "cargos", $this->arrayResult )) {
    		$cargosResult=$arrayResult['cargos'];
    			$this->cargos = AlibabatradeCargo();
    			$this->cargos->$this->setStdResult ( $cargosResult);
    		}
    		    	    		}
 
   
}
?>