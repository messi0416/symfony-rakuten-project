<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtradeBizOtherInfoGroup extends SDKDomain {

       	
    private $additionalFee;
    
        /**
    * @return 附加费,单位，元
    */
        public function getAdditionalFee() {
        return $this->additionalFee;
    }
    
    /**
     * 设置附加费,单位，元     
     * @param Double $additionalFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAdditionalFee( $additionalFee) {
        $this->additionalFee = $additionalFee;
    }
    
        	
    private $appointedArrivalDate;
    
        /**
    * @return 约定到货日期. 需要把到货日期转换为毫秒传过来.
    */
        public function getAppointedArrivalDate() {
        return $this->appointedArrivalDate;
    }
    
    /**
     * 设置约定到货日期. 需要把到货日期转换为毫秒传过来.     
     * @param Long $appointedArrivalDate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAppointedArrivalDate( $appointedArrivalDate) {
        $this->appointedArrivalDate = $appointedArrivalDate;
    }
    
        	
    private $autoConfirmReceipt;
    
        /**
    * @return 自动确认收货时间，单位为s
    */
        public function getAutoConfirmReceipt() {
        return $this->autoConfirmReceipt;
    }
    
    /**
     * 设置自动确认收货时间，单位为s     
     * @param Long $autoConfirmReceipt     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAutoConfirmReceipt( $autoConfirmReceipt) {
        $this->autoConfirmReceipt = $autoConfirmReceipt;
    }
    
        	
    private $buyerBizPhone;
    
        /**
    * @return 买家业务联系电话
    */
        public function getBuyerBizPhone() {
        return $this->buyerBizPhone;
    }
    
    /**
     * 设置买家业务联系电话     
     * @param String $buyerBizPhone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerBizPhone( $buyerBizPhone) {
        $this->buyerBizPhone = $buyerBizPhone;
    }
    
        	
    private $buyerCommpanyName;
    
        /**
    * @return 买家公司名称
    */
        public function getBuyerCommpanyName() {
        return $this->buyerCommpanyName;
    }
    
    /**
     * 设置买家公司名称     
     * @param String $buyerCommpanyName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerCommpanyName( $buyerCommpanyName) {
        $this->buyerCommpanyName = $buyerCommpanyName;
    }
    
        	
    private $calCarriage;
    
        /**
    * @return 页面运费模板计算出的运费 单位:分
    */
        public function getCalCarriage() {
        return $this->calCarriage;
    }
    
    /**
     * 设置页面运费模板计算出的运费 单位:分     
     * @param Long $calCarriage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCalCarriage( $calCarriage) {
        $this->calCarriage = $calCarriage;
    }
    
        	
    private $channel;
    
        /**
    * @return 下单来源渠道
    */
        public function getChannel() {
        return $this->channel;
    }
    
    /**
     * 设置下单来源渠道     
     * @param String $channel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setChannel( $channel) {
        $this->channel = $channel;
    }
    
        	
    private $checkCode;
    
        /**
    * @return 验证码
    */
        public function getCheckCode() {
        return $this->checkCode;
    }
    
    /**
     * 设置验证码     
     * @param String $checkCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCheckCode( $checkCode) {
        $this->checkCode = $checkCode;
    }
    
        	
    private $chooseFreeFreight;
    
        /**
    * @return 用户是否选择店铺免运费 。 "0"：用户没有选择免用费 "1":用户选择免运费.
    */
        public function getChooseFreeFreight() {
        return $this->chooseFreeFreight;
    }
    
    /**
     * 设置用户是否选择店铺免运费 。 "0"：用户没有选择免用费 "1":用户选择免运费.     
     * @param String $chooseFreeFreight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setChooseFreeFreight( $chooseFreeFreight) {
        $this->chooseFreeFreight = $chooseFreeFreight;
    }
    
        	
    private $codServiceType;
    
        /**
    * @return 分档id
    */
        public function getCodServiceType() {
        return $this->codServiceType;
    }
    
    /**
     * 设置分档id     
     * @param String $codServiceType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCodServiceType( $codServiceType) {
        $this->codServiceType = $codServiceType;
    }
    
        	
    private $crossPromotionIds;
    
        /**
    * @return 跨订单优惠
    */
        public function getCrossPromotionIds() {
        return $this->crossPromotionIds;
    }
    
    /**
     * 设置跨订单优惠     
     * @param array include @see String[] $crossPromotionIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCrossPromotionIds( $crossPromotionIds) {
        $this->crossPromotionIds = $crossPromotionIds;
    }
    
        	
    private $crossShopPromotions;
    
        /**
    * @return 跨店店铺优惠内容,不包含优惠券
    */
        public function getCrossShopPromotions() {
        return $this->crossShopPromotions;
    }
    
    /**
     * 设置跨店店铺优惠内容,不包含优惠券     
     * @param array include @see String[] $crossShopPromotions     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCrossShopPromotions( $crossShopPromotions) {
        $this->crossShopPromotions = $crossShopPromotions;
    }
    
        	
    private $discountFee;
    
        /**
    * @return 计算完货品金额后再次进行的减免金额. 单位: 元
    */
        public function getDiscountFee() {
        return $this->discountFee;
    }
    
    /**
     * 设置计算完货品金额后再次进行的减免金额. 单位: 元     
     * @param Double $discountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscountFee( $discountFee) {
        $this->discountFee = $discountFee;
    }
    
        	
    private $engine;
    
        /**
    * @return 交易流程引擎标识
    */
        public function getEngine() {
        return $this->engine;
    }
    
    /**
     * 设置交易流程引擎标识     
     * @param String $engine     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEngine( $engine) {
        $this->engine = $engine;
    }
    
        	
    private $extId;
    
        /**
    * @return 订单级别扩展数据由业务方自行解析, 框架不做任何处理。
    */
        public function getExtId() {
        return $this->extId;
    }
    
    /**
     * 设置订单级别扩展数据由业务方自行解析, 框架不做任何处理。     
     * @param String $extId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExtId( $extId) {
        $this->extId = $extId;
    }
    
        	
    private $filledCarriage;
    
        /**
    * @return 用户填写的运费 单位:元
    */
        public function getFilledCarriage() {
        return $this->filledCarriage;
    }
    
    /**
     * 设置用户填写的运费 单位:元     
     * @param Double $filledCarriage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFilledCarriage( $filledCarriage) {
        $this->filledCarriage = $filledCarriage;
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
    
        	
    private $guaranteeFee;
    
        /**
    * @return 页面传过来的阿里信用凭证担保费. 单位：元
    */
        public function getGuaranteeFee() {
        return $this->guaranteeFee;
    }
    
    /**
     * 设置页面传过来的阿里信用凭证担保费. 单位：元     
     * @param Double $guaranteeFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGuaranteeFee( $guaranteeFee) {
        $this->guaranteeFee = $guaranteeFee;
    }
    
        	
    private $lgArguments;
    
        /**
    * @return 物流二级key value
    */
        public function getLgArguments() {
        return $this->lgArguments;
    }
    
    /**
     * 设置物流二级key value     
     * @param String $lgArguments     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLgArguments( $lgArguments) {
        $this->lgArguments = $lgArguments;
    }
    
        	
    private $mergedJsonVar;
    
        /**
    * @return json格式的所有数据.
    */
        public function getMergedJsonVar() {
        return $this->mergedJsonVar;
    }
    
    /**
     * 设置json格式的所有数据.     
     * @param String $mergedJsonVar     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMergedJsonVar( $mergedJsonVar) {
        $this->mergedJsonVar = $mergedJsonVar;
    }
    
        	
    private $message;
    
        /**
    * @return 买家留言
    */
        public function getMessage() {
        return $this->message;
    }
    
    /**
     * 设置买家留言     
     * @param String $message     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMessage( $message) {
        $this->message = $message;
    }
    
        	
    private $mixAmount;
    
        /**
    * @return 混批金额, 单位:元。必填
    */
        public function getMixAmount() {
        return $this->mixAmount;
    }
    
    /**
     * 设置混批金额, 单位:元。必填     
     * @param String $mixAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMixAmount( $mixAmount) {
        $this->mixAmount = $mixAmount;
    }
    
        	
    private $mixNumber;
    
        /**
    * @return 混批数量。除非为0，否则必填
    */
        public function getMixNumber() {
        return $this->mixNumber;
    }
    
    /**
     * 设置混批数量。除非为0，否则必填     
     * @param String $mixNumber     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMixNumber( $mixNumber) {
        $this->mixNumber = $mixNumber;
    }
    
        	
    private $needCheckCode;
    
        /**
    * @return 是否需要验证码
    */
        public function getNeedCheckCode() {
        return $this->needCheckCode;
    }
    
    /**
     * 设置是否需要验证码     
     * @param Boolean $needCheckCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedCheckCode( $needCheckCode) {
        $this->needCheckCode = $needCheckCode;
    }
    
        	
    private $needCheckInstant;
    
        /**
    * @return 是否使用协议提额极速到账，用来接收checkbox的状态 。
     * -1：页面上未出现checkbox，走老的极速到账逻辑
     * 0：页面上出现了checkbox，但未被买家选中，表示不走极速到账交易，走支付宝担保交易
     * 1：页面上出现了checkbox，且被买家选中，表示走提额极速到账
    */
        public function getNeedCheckInstant() {
        return $this->needCheckInstant;
    }
    
    /**
     * 设置是否使用协议提额极速到账，用来接收checkbox的状态 。
     * -1：页面上未出现checkbox，走老的极速到账逻辑
     * 0：页面上出现了checkbox，但未被买家选中，表示不走极速到账交易，走支付宝担保交易
     * 1：页面上出现了checkbox，且被买家选中，表示走提额极速到账     
     * @param Integer $needCheckInstant     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedCheckInstant( $needCheckInstant) {
        $this->needCheckInstant = $needCheckInstant;
    }
    
        	
    private $needInstallment;
    
        /**
    * @return 是否需要分期付款
    */
        public function getNeedInstallment() {
        return $this->needInstallment;
    }
    
    /**
     * 设置是否需要分期付款     
     * @param Boolean $needInstallment     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedInstallment( $needInstallment) {
        $this->needInstallment = $needInstallment;
    }
    
        	
    private $needRegist;
    
        /**
    * @return 判断前台是否需要登录注册
    */
        public function getNeedRegist() {
        return $this->needRegist;
    }
    
    /**
     * 设置判断前台是否需要登录注册     
     * @param Boolean $needRegist     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNeedRegist( $needRegist) {
        $this->needRegist = $needRegist;
    }
    
        	
    private $orderCodFee;
    
        /**
    * @return cod服务费
    */
        public function getOrderCodFee() {
        return $this->orderCodFee;
    }
    
    /**
     * 设置cod服务费     
     * @param Double $orderCodFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderCodFee( $orderCodFee) {
        $this->orderCodFee = $orderCodFee;
    }
    
        	
    private $orderContractContent;
    
        /**
    * @return 交易合约内容
    */
        public function getOrderContractContent() {
        return $this->orderContractContent;
    }
    
    /**
     * 设置交易合约内容     
     * @param String $orderContractContent     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderContractContent( $orderContractContent) {
        $this->orderContractContent = $orderContractContent;
    }
    
        	
    private $payChannel;
    
        /**
    * @return 支付渠道
    */
        public function getPayChannel() {
        return $this->payChannel;
    }
    
    /**
     * 设置支付渠道     
     * @param String $payChannel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayChannel( $payChannel) {
        $this->payChannel = $payChannel;
    }
    
        	
    private $payEntry;
    
        /**
    * @return 选择的支付入口
    */
        public function getPayEntry() {
        return $this->payEntry;
    }
    
    /**
     * 设置选择的支付入口     
     * @param String $payEntry     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayEntry( $payEntry) {
        $this->payEntry = $payEntry;
    }
    
        	
    private $payTimeout;
    
        /**
    * @return 定点付款超时时间，单位为ms。 需要把付款超时的日期Date类型转换为毫秒传过来.
    */
        public function getPayTimeout() {
        return $this->payTimeout;
    }
    
    /**
     * 设置定点付款超时时间，单位为ms。 需要把付款超时的日期Date类型转换为毫秒传过来.     
     * @param Long $payTimeout     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayTimeout( $payTimeout) {
        $this->payTimeout = $payTimeout;
    }
    
        	
    private $payWay;
    
        /**
    * @return 支付方式。 "6"：全额付款，"7"：分阶段付
    */
        public function getPayWay() {
        return $this->payWay;
    }
    
    /**
     * 设置支付方式。 "6"：全额付款，"7"：分阶段付     
     * @param String $payWay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayWay( $payWay) {
        $this->payWay = $payWay;
    }
    
        	
    private $promotionId;
    
        /**
    * @return 店铺优惠id
    */
        public function getPromotionId() {
        return $this->promotionId;
    }
    
    /**
     * 设置店铺优惠id     
     * @param String $promotionId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPromotionId( $promotionId) {
        $this->promotionId = $promotionId;
    }
    
        	
    private $selectedLogistics;
    
        /**
    * @return 选择的物流方案标识
    */
        public function getSelectedLogistics() {
        return $this->selectedLogistics;
    }
    
    /**
     * 设置选择的物流方案标识     
     * @param String $selectedLogistics     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSelectedLogistics( $selectedLogistics) {
        $this->selectedLogistics = $selectedLogistics;
    }
    
        	
    private $sellerBizPhone;
    
        /**
    * @return 卖家业务联系电话
    */
        public function getSellerBizPhone() {
        return $this->sellerBizPhone;
    }
    
    /**
     * 设置卖家业务联系电话     
     * @param String $sellerBizPhone     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerBizPhone( $sellerBizPhone) {
        $this->sellerBizPhone = $sellerBizPhone;
    }
    
        	
    private $sellerCompanyname;
    
        /**
    * @return 卖家公司名称
    */
        public function getSellerCompanyname() {
        return $this->sellerCompanyname;
    }
    
    /**
     * 设置卖家公司名称     
     * @param String $sellerCompanyname     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerCompanyname( $sellerCompanyname) {
        $this->sellerCompanyname = $sellerCompanyname;
    }
    
        	
    private $site;
    
        /**
    * @return 站点标识
    */
        public function getSite() {
        return $this->site;
    }
    
    /**
     * 设置站点标识     
     * @param String $site     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSite( $site) {
        $this->site = $site;
    }
    
        	
    private $sumCarriage;
    
        /**
    * @return 总运费。除非为0，否则必填
    */
        public function getSumCarriage() {
        return $this->sumCarriage;
    }
    
    /**
     * 设置总运费。除非为0，否则必填     
     * @param Double $sumCarriage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSumCarriage( $sumCarriage) {
        $this->sumCarriage = $sumCarriage;
    }
    
        	
    private $supportInvoice;
    
        /**
    * @return 是否支持发票标识.
    */
        public function getSupportInvoice() {
        return $this->supportInvoice;
    }
    
    /**
     * 设置是否支持发票标识.     
     * @param Boolean $supportInvoice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupportInvoice( $supportInvoice) {
        $this->supportInvoice = $supportInvoice;
    }
    
        	
    private $toleranceFreight;
    
        /**
    * @return 用来标记运费是否被容错。便于提交订单时跳过运费相关逻辑校验。 1：运费被容错。 0:正常运费.
    */
        public function getToleranceFreight() {
        return $this->toleranceFreight;
    }
    
    /**
     * 设置用来标记运费是否被容错。便于提交订单时跳过运费相关逻辑校验。 1：运费被容错。 0:正常运费.     
     * @param String $toleranceFreight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setToleranceFreight( $toleranceFreight) {
        $this->toleranceFreight = $toleranceFreight;
    }
    
        	
    private $totalAmount;
    
        /**
    * @return 货品总金额:， 货品总金额 + 运费，单位: 元。必填
    */
        public function getTotalAmount() {
        return $this->totalAmount;
    }
    
    /**
     * 设置货品总金额:， 货品总金额 + 运费，单位: 元。必填     
     * @param Double $totalAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTotalAmount( $totalAmount) {
        $this->totalAmount = $totalAmount;
    }
    
        	
    private $totalProductAmount;
    
        /**
    * @return 商品总金额，确认订购时，页面显示的总金额，用来进行校验。 商品总金额 - 店铺级优惠.
    */
        public function getTotalProductAmount() {
        return $this->totalProductAmount;
    }
    
    /**
     * 设置商品总金额，确认订购时，页面显示的总金额，用来进行校验。 商品总金额 - 店铺级优惠.     
     * @param Double $totalProductAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTotalProductAmount( $totalProductAmount) {
        $this->totalProductAmount = $totalProductAmount;
    }
    
        	
    private $umpSysAvailble;
    
        /**
    * @return 用来标记ump系统是否可用，这个变量会在前台页面埋点，供ump联动使用. 1：ump 系统可用 0:ump系统不可用.
    */
        public function getUmpSysAvailble() {
        return $this->umpSysAvailble;
    }
    
    /**
     * 设置用来标记ump系统是否可用，这个变量会在前台页面埋点，供ump联动使用. 1：ump 系统可用 0:ump系统不可用.     
     * @param String $umpSysAvailble     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUmpSysAvailble( $umpSysAvailble) {
        $this->umpSysAvailble = $umpSysAvailble;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "additionalFee", $this->stdResult )) {
    				$this->additionalFee = $this->stdResult->{"additionalFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "appointedArrivalDate", $this->stdResult )) {
    				$this->appointedArrivalDate = $this->stdResult->{"appointedArrivalDate"};
    			}
    			    		    				    			    			if (array_key_exists ( "autoConfirmReceipt", $this->stdResult )) {
    				$this->autoConfirmReceipt = $this->stdResult->{"autoConfirmReceipt"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerBizPhone", $this->stdResult )) {
    				$this->buyerBizPhone = $this->stdResult->{"buyerBizPhone"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerCommpanyName", $this->stdResult )) {
    				$this->buyerCommpanyName = $this->stdResult->{"buyerCommpanyName"};
    			}
    			    		    				    			    			if (array_key_exists ( "calCarriage", $this->stdResult )) {
    				$this->calCarriage = $this->stdResult->{"calCarriage"};
    			}
    			    		    				    			    			if (array_key_exists ( "channel", $this->stdResult )) {
    				$this->channel = $this->stdResult->{"channel"};
    			}
    			    		    				    			    			if (array_key_exists ( "checkCode", $this->stdResult )) {
    				$this->checkCode = $this->stdResult->{"checkCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "chooseFreeFreight", $this->stdResult )) {
    				$this->chooseFreeFreight = $this->stdResult->{"chooseFreeFreight"};
    			}
    			    		    				    			    			if (array_key_exists ( "codServiceType", $this->stdResult )) {
    				$this->codServiceType = $this->stdResult->{"codServiceType"};
    			}
    			    		    				    			    			if (array_key_exists ( "crossPromotionIds", $this->stdResult )) {
    				$this->crossPromotionIds = $this->stdResult->{"crossPromotionIds"};
    			}
    			    		    				    			    			if (array_key_exists ( "crossShopPromotions", $this->stdResult )) {
    				$this->crossShopPromotions = $this->stdResult->{"crossShopPromotions"};
    			}
    			    		    				    			    			if (array_key_exists ( "discountFee", $this->stdResult )) {
    				$this->discountFee = $this->stdResult->{"discountFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "engine", $this->stdResult )) {
    				$this->engine = $this->stdResult->{"engine"};
    			}
    			    		    				    			    			if (array_key_exists ( "extId", $this->stdResult )) {
    				$this->extId = $this->stdResult->{"extId"};
    			}
    			    		    				    			    			if (array_key_exists ( "filledCarriage", $this->stdResult )) {
    				$this->filledCarriage = $this->stdResult->{"filledCarriage"};
    			}
    			    		    				    			    			if (array_key_exists ( "group", $this->stdResult )) {
    				$this->group = $this->stdResult->{"group"};
    			}
    			    		    				    			    			if (array_key_exists ( "guaranteeFee", $this->stdResult )) {
    				$this->guaranteeFee = $this->stdResult->{"guaranteeFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "lgArguments", $this->stdResult )) {
    				$this->lgArguments = $this->stdResult->{"lgArguments"};
    			}
    			    		    				    			    			if (array_key_exists ( "mergedJsonVar", $this->stdResult )) {
    				$this->mergedJsonVar = $this->stdResult->{"mergedJsonVar"};
    			}
    			    		    				    			    			if (array_key_exists ( "message", $this->stdResult )) {
    				$this->message = $this->stdResult->{"message"};
    			}
    			    		    				    			    			if (array_key_exists ( "mixAmount", $this->stdResult )) {
    				$this->mixAmount = $this->stdResult->{"mixAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "mixNumber", $this->stdResult )) {
    				$this->mixNumber = $this->stdResult->{"mixNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "needCheckCode", $this->stdResult )) {
    				$this->needCheckCode = $this->stdResult->{"needCheckCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "needCheckInstant", $this->stdResult )) {
    				$this->needCheckInstant = $this->stdResult->{"needCheckInstant"};
    			}
    			    		    				    			    			if (array_key_exists ( "needInstallment", $this->stdResult )) {
    				$this->needInstallment = $this->stdResult->{"needInstallment"};
    			}
    			    		    				    			    			if (array_key_exists ( "needRegist", $this->stdResult )) {
    				$this->needRegist = $this->stdResult->{"needRegist"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderCodFee", $this->stdResult )) {
    				$this->orderCodFee = $this->stdResult->{"orderCodFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderContractContent", $this->stdResult )) {
    				$this->orderContractContent = $this->stdResult->{"orderContractContent"};
    			}
    			    		    				    			    			if (array_key_exists ( "payChannel", $this->stdResult )) {
    				$this->payChannel = $this->stdResult->{"payChannel"};
    			}
    			    		    				    			    			if (array_key_exists ( "payEntry", $this->stdResult )) {
    				$this->payEntry = $this->stdResult->{"payEntry"};
    			}
    			    		    				    			    			if (array_key_exists ( "payTimeout", $this->stdResult )) {
    				$this->payTimeout = $this->stdResult->{"payTimeout"};
    			}
    			    		    				    			    			if (array_key_exists ( "payWay", $this->stdResult )) {
    				$this->payWay = $this->stdResult->{"payWay"};
    			}
    			    		    				    			    			if (array_key_exists ( "promotionId", $this->stdResult )) {
    				$this->promotionId = $this->stdResult->{"promotionId"};
    			}
    			    		    				    			    			if (array_key_exists ( "selectedLogistics", $this->stdResult )) {
    				$this->selectedLogistics = $this->stdResult->{"selectedLogistics"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerBizPhone", $this->stdResult )) {
    				$this->sellerBizPhone = $this->stdResult->{"sellerBizPhone"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerCompanyname", $this->stdResult )) {
    				$this->sellerCompanyname = $this->stdResult->{"sellerCompanyname"};
    			}
    			    		    				    			    			if (array_key_exists ( "site", $this->stdResult )) {
    				$this->site = $this->stdResult->{"site"};
    			}
    			    		    				    			    			if (array_key_exists ( "sumCarriage", $this->stdResult )) {
    				$this->sumCarriage = $this->stdResult->{"sumCarriage"};
    			}
    			    		    				    			    			if (array_key_exists ( "supportInvoice", $this->stdResult )) {
    				$this->supportInvoice = $this->stdResult->{"supportInvoice"};
    			}
    			    		    				    			    			if (array_key_exists ( "toleranceFreight", $this->stdResult )) {
    				$this->toleranceFreight = $this->stdResult->{"toleranceFreight"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalAmount", $this->stdResult )) {
    				$this->totalAmount = $this->stdResult->{"totalAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalProductAmount", $this->stdResult )) {
    				$this->totalProductAmount = $this->stdResult->{"totalProductAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "umpSysAvailble", $this->stdResult )) {
    				$this->umpSysAvailble = $this->stdResult->{"umpSysAvailble"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "additionalFee", $this->arrayResult )) {
    			$this->additionalFee = $arrayResult['additionalFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "appointedArrivalDate", $this->arrayResult )) {
    			$this->appointedArrivalDate = $arrayResult['appointedArrivalDate'];
    			}
    		    	    			    		    			if (array_key_exists ( "autoConfirmReceipt", $this->arrayResult )) {
    			$this->autoConfirmReceipt = $arrayResult['autoConfirmReceipt'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerBizPhone", $this->arrayResult )) {
    			$this->buyerBizPhone = $arrayResult['buyerBizPhone'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerCommpanyName", $this->arrayResult )) {
    			$this->buyerCommpanyName = $arrayResult['buyerCommpanyName'];
    			}
    		    	    			    		    			if (array_key_exists ( "calCarriage", $this->arrayResult )) {
    			$this->calCarriage = $arrayResult['calCarriage'];
    			}
    		    	    			    		    			if (array_key_exists ( "channel", $this->arrayResult )) {
    			$this->channel = $arrayResult['channel'];
    			}
    		    	    			    		    			if (array_key_exists ( "checkCode", $this->arrayResult )) {
    			$this->checkCode = $arrayResult['checkCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "chooseFreeFreight", $this->arrayResult )) {
    			$this->chooseFreeFreight = $arrayResult['chooseFreeFreight'];
    			}
    		    	    			    		    			if (array_key_exists ( "codServiceType", $this->arrayResult )) {
    			$this->codServiceType = $arrayResult['codServiceType'];
    			}
    		    	    			    		    			if (array_key_exists ( "crossPromotionIds", $this->arrayResult )) {
    			$this->crossPromotionIds = $arrayResult['crossPromotionIds'];
    			}
    		    	    			    		    			if (array_key_exists ( "crossShopPromotions", $this->arrayResult )) {
    			$this->crossShopPromotions = $arrayResult['crossShopPromotions'];
    			}
    		    	    			    		    			if (array_key_exists ( "discountFee", $this->arrayResult )) {
    			$this->discountFee = $arrayResult['discountFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "engine", $this->arrayResult )) {
    			$this->engine = $arrayResult['engine'];
    			}
    		    	    			    		    			if (array_key_exists ( "extId", $this->arrayResult )) {
    			$this->extId = $arrayResult['extId'];
    			}
    		    	    			    		    			if (array_key_exists ( "filledCarriage", $this->arrayResult )) {
    			$this->filledCarriage = $arrayResult['filledCarriage'];
    			}
    		    	    			    		    			if (array_key_exists ( "group", $this->arrayResult )) {
    			$this->group = $arrayResult['group'];
    			}
    		    	    			    		    			if (array_key_exists ( "guaranteeFee", $this->arrayResult )) {
    			$this->guaranteeFee = $arrayResult['guaranteeFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "lgArguments", $this->arrayResult )) {
    			$this->lgArguments = $arrayResult['lgArguments'];
    			}
    		    	    			    		    			if (array_key_exists ( "mergedJsonVar", $this->arrayResult )) {
    			$this->mergedJsonVar = $arrayResult['mergedJsonVar'];
    			}
    		    	    			    		    			if (array_key_exists ( "message", $this->arrayResult )) {
    			$this->message = $arrayResult['message'];
    			}
    		    	    			    		    			if (array_key_exists ( "mixAmount", $this->arrayResult )) {
    			$this->mixAmount = $arrayResult['mixAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "mixNumber", $this->arrayResult )) {
    			$this->mixNumber = $arrayResult['mixNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "needCheckCode", $this->arrayResult )) {
    			$this->needCheckCode = $arrayResult['needCheckCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "needCheckInstant", $this->arrayResult )) {
    			$this->needCheckInstant = $arrayResult['needCheckInstant'];
    			}
    		    	    			    		    			if (array_key_exists ( "needInstallment", $this->arrayResult )) {
    			$this->needInstallment = $arrayResult['needInstallment'];
    			}
    		    	    			    		    			if (array_key_exists ( "needRegist", $this->arrayResult )) {
    			$this->needRegist = $arrayResult['needRegist'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderCodFee", $this->arrayResult )) {
    			$this->orderCodFee = $arrayResult['orderCodFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderContractContent", $this->arrayResult )) {
    			$this->orderContractContent = $arrayResult['orderContractContent'];
    			}
    		    	    			    		    			if (array_key_exists ( "payChannel", $this->arrayResult )) {
    			$this->payChannel = $arrayResult['payChannel'];
    			}
    		    	    			    		    			if (array_key_exists ( "payEntry", $this->arrayResult )) {
    			$this->payEntry = $arrayResult['payEntry'];
    			}
    		    	    			    		    			if (array_key_exists ( "payTimeout", $this->arrayResult )) {
    			$this->payTimeout = $arrayResult['payTimeout'];
    			}
    		    	    			    		    			if (array_key_exists ( "payWay", $this->arrayResult )) {
    			$this->payWay = $arrayResult['payWay'];
    			}
    		    	    			    		    			if (array_key_exists ( "promotionId", $this->arrayResult )) {
    			$this->promotionId = $arrayResult['promotionId'];
    			}
    		    	    			    		    			if (array_key_exists ( "selectedLogistics", $this->arrayResult )) {
    			$this->selectedLogistics = $arrayResult['selectedLogistics'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerBizPhone", $this->arrayResult )) {
    			$this->sellerBizPhone = $arrayResult['sellerBizPhone'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerCompanyname", $this->arrayResult )) {
    			$this->sellerCompanyname = $arrayResult['sellerCompanyname'];
    			}
    		    	    			    		    			if (array_key_exists ( "site", $this->arrayResult )) {
    			$this->site = $arrayResult['site'];
    			}
    		    	    			    		    			if (array_key_exists ( "sumCarriage", $this->arrayResult )) {
    			$this->sumCarriage = $arrayResult['sumCarriage'];
    			}
    		    	    			    		    			if (array_key_exists ( "supportInvoice", $this->arrayResult )) {
    			$this->supportInvoice = $arrayResult['supportInvoice'];
    			}
    		    	    			    		    			if (array_key_exists ( "toleranceFreight", $this->arrayResult )) {
    			$this->toleranceFreight = $arrayResult['toleranceFreight'];
    			}
    		    	    			    		    			if (array_key_exists ( "totalAmount", $this->arrayResult )) {
    			$this->totalAmount = $arrayResult['totalAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "totalProductAmount", $this->arrayResult )) {
    			$this->totalProductAmount = $arrayResult['totalProductAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "umpSysAvailble", $this->arrayResult )) {
    			$this->umpSysAvailble = $arrayResult['umpSysAvailble'];
    			}
    		    	    		}
 
   
}
?>