<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressCommodity.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressGoodsPackage.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressContact.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressLocalLogistics.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressContact.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressCustomsDeclarationInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressGoodsPackage.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressUserActionTrace.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressLogisticsTrackTrace.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressExpressWTDSolution.class.php');

class AlibabalogisticsexpressExpressWTDOrder extends SDKDomain {

       	
    private $createOrderTime;
    
        /**
    * @return 下单时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）
    */
        public function getCreateOrderTime() {
        return $this->createOrderTime;
    }
    
    /**
     * 设置下单时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）     
     * @param String $createOrderTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCreateOrderTime( $createOrderTime) {
        $this->createOrderTime = $createOrderTime;
    }
    
        	
    private $shippingTime;
    
        /**
    * @return 发货时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）
    */
        public function getShippingTime() {
        return $this->shippingTime;
    }
    
    /**
     * 设置发货时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）     
     * @param String $shippingTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShippingTime( $shippingTime) {
        $this->shippingTime = $shippingTime;
    }
    
        	
    private $remark;
    
        /**
    * @return 备注
    */
        public function getRemark() {
        return $this->remark;
    }
    
    /**
     * 设置备注     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->remark = $remark;
    }
    
        	
    private $mainTrackingNumber;
    
        /**
    * @return 国际主运单号（快递单号）：可用于查询跟踪信息
    */
        public function getMainTrackingNumber() {
        return $this->mainTrackingNumber;
    }
    
    /**
     * 设置国际主运单号（快递单号）：可用于查询跟踪信息     
     * @param String $mainTrackingNumber     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMainTrackingNumber( $mainTrackingNumber) {
        $this->mainTrackingNumber = $mainTrackingNumber;
    }
    
        	
    private $trackingNumbers;
    
        /**
    * @return 国际运单号（快递单号）
    */
        public function getTrackingNumbers() {
        return $this->trackingNumbers;
    }
    
    /**
     * 设置国际运单号（快递单号）     
     * @param array include @see String[] $trackingNumbers     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTrackingNumbers( $trackingNumbers) {
        $this->trackingNumbers = $trackingNumbers;
    }
    
        	
    private $orderId;
    
        /**
    * @return 订单ID
    */
        public function getOrderId() {
        return $this->orderId;
    }
    
    /**
     * 设置订单ID     
     * @param Long $orderId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderId( $orderId) {
        $this->orderId = $orderId;
    }
    
        	
    private $warehouseMemo;
    
        /**
    * @return 入库时,仓库填写的备注
    */
        public function getWarehouseMemo() {
        return $this->warehouseMemo;
    }
    
    /**
     * 设置入库时,仓库填写的备注     
     * @param String $warehouseMemo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWarehouseMemo( $warehouseMemo) {
        $this->warehouseMemo = $warehouseMemo;
    }
    
        	
    private $status;
    
        /**
    * @return 状态，WAIT_ARRIVE_WAREHOUSE：待发货，
CARGO_ARRIVE_WAREHOUSE：货物抵达仓库，
CARGO_ENTER_WAREHOUSE：已入库，
SHIPPED：已发货，
FINISH：订单完成，
WAREHOUSE_REJECT：仓库拒绝收货，
CLOSE：关闭
    */
        public function getStatus() {
        return $this->status;
    }
    
    /**
     * 设置状态，WAIT_ARRIVE_WAREHOUSE：待发货，
CARGO_ARRIVE_WAREHOUSE：货物抵达仓库，
CARGO_ENTER_WAREHOUSE：已入库，
SHIPPED：已发货，
FINISH：订单完成，
WAREHOUSE_REJECT：仓库拒绝收货，
CLOSE：关闭     
     * @param String $status     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStatus( $status) {
        $this->status = $status;
    }
    
        	
    private $destinationCountryCode;
    
        /**
    * @return 目的国家代码，使用ISO 3166 2A
    */
        public function getDestinationCountryCode() {
        return $this->destinationCountryCode;
    }
    
    /**
     * 设置目的国家代码，使用ISO 3166 2A     
     * @param String $destinationCountryCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDestinationCountryCode( $destinationCountryCode) {
        $this->destinationCountryCode = $destinationCountryCode;
    }
    
        	
    private $actualPayedTime;
    
        /**
    * @return 支付时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）
    */
        public function getActualPayedTime() {
        return $this->actualPayedTime;
    }
    
    /**
     * 设置支付时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）     
     * @param String $actualPayedTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setActualPayedTime( $actualPayedTime) {
        $this->actualPayedTime = $actualPayedTime;
    }
    
        	
    private $leavePortTime;
    
        /**
    * @return 离港时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）
    */
        public function getLeavePortTime() {
        return $this->leavePortTime;
    }
    
    /**
     * 设置离港时间（ISO 8601 GMT/UTC，例：2004-05-03T17:30:08+08:00）     
     * @param String $leavePortTime     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLeavePortTime( $leavePortTime) {
        $this->leavePortTime = $leavePortTime;
    }
    
        	
    private $commoditys;
    
        /**
    * @return 商品信息
    */
        public function getCommoditys() {
        return $this->commoditys;
    }
    
    /**
     * 设置商品信息     
     * @param array include @see AlibabalogisticsexpressCommodity[] $commoditys     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCommoditys(AlibabalogisticsexpressCommodity $commoditys) {
        $this->commoditys = $commoditys;
    }
    
        	
    private $originalGoodsPackage;
    
        /**
    * @return 原始货物包裹
    */
        public function getOriginalGoodsPackage() {
        return $this->originalGoodsPackage;
    }
    
    /**
     * 设置原始货物包裹     
     * @param AlibabalogisticsexpressGoodsPackage $originalGoodsPackage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOriginalGoodsPackage(AlibabalogisticsexpressGoodsPackage $originalGoodsPackage) {
        $this->originalGoodsPackage = $originalGoodsPackage;
    }
    
        	
    private $payableAmount;
    
        /**
    * @return 应付费用金额
    */
        public function getPayableAmount() {
        return $this->payableAmount;
    }
    
    /**
     * 设置应付费用金额     
     * @param AlibabalogisticscommonMoney $payableAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPayableAmount(AlibabalogisticscommonMoney $payableAmount) {
        $this->payableAmount = $payableAmount;
    }
    
        	
    private $consignee;
    
        /**
    * @return 收件人
    */
        public function getConsignee() {
        return $this->consignee;
    }
    
    /**
     * 设置收件人     
     * @param AlibabalogisticsexpressContact $consignee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setConsignee(AlibabalogisticsexpressContact $consignee) {
        $this->consignee = $consignee;
    }
    
        	
    private $estimatedCost;
    
        /**
    * @return 预估费用
    */
        public function getEstimatedCost() {
        return $this->estimatedCost;
    }
    
    /**
     * 设置预估费用     
     * @param AlibabalogisticscommonMoney $estimatedCost     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEstimatedCost(AlibabalogisticscommonMoney $estimatedCost) {
        $this->estimatedCost = $estimatedCost;
    }
    
        	
    private $localLogistics;
    
        /**
    * @return 本地物流信息
    */
        public function getLocalLogistics() {
        return $this->localLogistics;
    }
    
    /**
     * 设置本地物流信息     
     * @param AlibabalogisticsexpressLocalLogistics $localLogistics     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLocalLogistics(AlibabalogisticsexpressLocalLogistics $localLogistics) {
        $this->localLogistics = $localLogistics;
    }
    
        	
    private $shipper;
    
        /**
    * @return 寄件人
    */
        public function getShipper() {
        return $this->shipper;
    }
    
    /**
     * 设置寄件人     
     * @param AlibabalogisticsexpressContact $shipper     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setShipper(AlibabalogisticsexpressContact $shipper) {
        $this->shipper = $shipper;
    }
    
        	
    private $customsDeclarationInfo;
    
        /**
    * @return 报关信息
    */
        public function getCustomsDeclarationInfo() {
        return $this->customsDeclarationInfo;
    }
    
    /**
     * 设置报关信息     
     * @param AlibabalogisticsexpressCustomsDeclarationInfo $customsDeclarationInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCustomsDeclarationInfo(AlibabalogisticsexpressCustomsDeclarationInfo $customsDeclarationInfo) {
        $this->customsDeclarationInfo = $customsDeclarationInfo;
    }
    
        	
    private $actualGoodsPackage;
    
        /**
    * @return 实际货物包裹（仓库称重后）
    */
        public function getActualGoodsPackage() {
        return $this->actualGoodsPackage;
    }
    
    /**
     * 设置实际货物包裹（仓库称重后）     
     * @param AlibabalogisticsexpressGoodsPackage $actualGoodsPackage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setActualGoodsPackage(AlibabalogisticsexpressGoodsPackage $actualGoodsPackage) {
        $this->actualGoodsPackage = $actualGoodsPackage;
    }
    
        	
    private $actualPayedAmount;
    
        /**
    * @return 实际支付费用
    */
        public function getActualPayedAmount() {
        return $this->actualPayedAmount;
    }
    
    /**
     * 设置实际支付费用     
     * @param AlibabalogisticscommonMoney $actualPayedAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setActualPayedAmount(AlibabalogisticscommonMoney $actualPayedAmount) {
        $this->actualPayedAmount = $actualPayedAmount;
    }
    
        	
    private $userActionTraces;
    
        /**
    * @return 用户操作行为轨迹
    */
        public function getUserActionTraces() {
        return $this->userActionTraces;
    }
    
    /**
     * 设置用户操作行为轨迹     
     * @param array include @see AlibabalogisticsexpressUserActionTrace[] $userActionTraces     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUserActionTraces(AlibabalogisticsexpressUserActionTrace $userActionTraces) {
        $this->userActionTraces = $userActionTraces;
    }
    
        	
    private $logisticsTrackTraces;
    
        /**
    * @return 物流跟踪信息（已经出运的订单才有）
    */
        public function getLogisticsTrackTraces() {
        return $this->logisticsTrackTraces;
    }
    
    /**
     * 设置物流跟踪信息（已经出运的订单才有）     
     * @param array include @see AlibabalogisticsexpressLogisticsTrackTrace[] $logisticsTrackTraces     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setLogisticsTrackTraces(AlibabalogisticsexpressLogisticsTrackTrace $logisticsTrackTraces) {
        $this->logisticsTrackTraces = $logisticsTrackTraces;
    }
    
        	
    private $paymentStatus;
    
        /**
    * @return 支付状态。NEW：不需要支付，WAIT_PAY：待支付，PAYED：已支付
    */
        public function getPaymentStatus() {
        return $this->paymentStatus;
    }
    
    /**
     * 设置支付状态。NEW：不需要支付，WAIT_PAY：待支付，PAYED：已支付     
     * @param String $paymentStatus     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPaymentStatus( $paymentStatus) {
        $this->paymentStatus = $paymentStatus;
    }
    
        	
    private $closeType;
    
        /**
    * @return 订单关闭类型，只有当status为CLOSE时才有值。USER_CANCEL_ORDER：用户取消，USER_RETURN_ORDER：用户申请退货，LEAVE_WAREHOUSE_RETURN：出库退货
    */
        public function getCloseType() {
        return $this->closeType;
    }
    
    /**
     * 设置订单关闭类型，只有当status为CLOSE时才有值。USER_CANCEL_ORDER：用户取消，USER_RETURN_ORDER：用户申请退货，LEAVE_WAREHOUSE_RETURN：出库退货     
     * @param String $closeType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCloseType( $closeType) {
        $this->closeType = $closeType;
    }
    
        	
    private $closeReason;
    
        /**
    * @return 订单关闭原因，只有当status为CLOSE时才有值。
    */
        public function getCloseReason() {
        return $this->closeReason;
    }
    
    /**
     * 设置订单关闭原因，只有当status为CLOSE时才有值。     
     * @param String $closeReason     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCloseReason( $closeReason) {
        $this->closeReason = $closeReason;
    }
    
        	
    private $warehouseRejectReason;
    
        /**
    * @return 仓库拒绝收货原因，只有当status为WAREHOUSE_REJECT时才有值。
    */
        public function getWarehouseRejectReason() {
        return $this->warehouseRejectReason;
    }
    
    /**
     * 设置仓库拒绝收货原因，只有当status为WAREHOUSE_REJECT时才有值。     
     * @param String $warehouseRejectReason     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWarehouseRejectReason( $warehouseRejectReason) {
        $this->warehouseRejectReason = $warehouseRejectReason;
    }
    
        	
    private $promptText;
    
        /**
    * @return 订单提示文本（如：请及时发货，如果仓库30天内未收到您的货物，物流订单将自动关闭。)
    */
        public function getPromptText() {
        return $this->promptText;
    }
    
    /**
     * 设置订单提示文本（如：请及时发货，如果仓库30天内未收到您的货物，物流订单将自动关闭。)     
     * @param String $promptText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPromptText( $promptText) {
        $this->promptText = $promptText;
    }
    
        	
    private $orderUrl;
    
        /**
    * @return 在阿里系统的订单详情URL
    */
        public function getOrderUrl() {
        return $this->orderUrl;
    }
    
    /**
     * 设置在阿里系统的订单详情URL     
     * @param String $orderUrl     
     * 参数示例：<pre>http://shipping.alibaba.com/orderDetail.htm?id=xxxx</pre>     
     * 此参数必填     */
    public function setOrderUrl( $orderUrl) {
        $this->orderUrl = $orderUrl;
    }
    
        	
    private $solution;
    
        /**
    * @return 方案
    */
        public function getSolution() {
        return $this->solution;
    }
    
    /**
     * 设置方案     
     * @param AlibabalogisticsexpressExpressWTDSolution $solution     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSolution(AlibabalogisticsexpressExpressWTDSolution $solution) {
        $this->solution = $solution;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "createOrderTime", $this->stdResult )) {
    				$this->createOrderTime = $this->stdResult->{"createOrderTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "shippingTime", $this->stdResult )) {
    				$this->shippingTime = $this->stdResult->{"shippingTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    				    			    			if (array_key_exists ( "mainTrackingNumber", $this->stdResult )) {
    				$this->mainTrackingNumber = $this->stdResult->{"mainTrackingNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "trackingNumbers", $this->stdResult )) {
    				$this->trackingNumbers = $this->stdResult->{"trackingNumbers"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderId", $this->stdResult )) {
    				$this->orderId = $this->stdResult->{"orderId"};
    			}
    			    		    				    			    			if (array_key_exists ( "warehouseMemo", $this->stdResult )) {
    				$this->warehouseMemo = $this->stdResult->{"warehouseMemo"};
    			}
    			    		    				    			    			if (array_key_exists ( "status", $this->stdResult )) {
    				$this->status = $this->stdResult->{"status"};
    			}
    			    		    				    			    			if (array_key_exists ( "destinationCountryCode", $this->stdResult )) {
    				$this->destinationCountryCode = $this->stdResult->{"destinationCountryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "actualPayedTime", $this->stdResult )) {
    				$this->actualPayedTime = $this->stdResult->{"actualPayedTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "leavePortTime", $this->stdResult )) {
    				$this->leavePortTime = $this->stdResult->{"leavePortTime"};
    			}
    			    		    				    			    			if (array_key_exists ( "commoditys", $this->stdResult )) {
    			$commoditysResult=$this->stdResult->{"commoditys"};
    				$object = json_decode ( json_encode ( $commoditysResult ), true );
					$this->commoditys = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabalogisticsexpressCommodityResult=new AlibabalogisticsexpressCommodity();
						$AlibabalogisticsexpressCommodityResult->setArrayResult($arrayobject );
						$this->commoditys [$i] = $AlibabalogisticsexpressCommodityResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "originalGoodsPackage", $this->stdResult )) {
    				$originalGoodsPackageResult=$this->stdResult->{"originalGoodsPackage"};
    				$this->originalGoodsPackage = new AlibabalogisticsexpressGoodsPackage();
    				$this->originalGoodsPackage->setStdResult ( $originalGoodsPackageResult);
    			}
    			    		    				    			    			if (array_key_exists ( "payableAmount", $this->stdResult )) {
    				$payableAmountResult=$this->stdResult->{"payableAmount"};
    				$this->payableAmount = new AlibabalogisticscommonMoney();
    				$this->payableAmount->setStdResult ( $payableAmountResult);
    			}
    			    		    				    			    			if (array_key_exists ( "consignee", $this->stdResult )) {
    				$consigneeResult=$this->stdResult->{"consignee"};
    				$this->consignee = new AlibabalogisticsexpressContact();
    				$this->consignee->setStdResult ( $consigneeResult);
    			}
    			    		    				    			    			if (array_key_exists ( "estimatedCost", $this->stdResult )) {
    				$estimatedCostResult=$this->stdResult->{"estimatedCost"};
    				$this->estimatedCost = new AlibabalogisticscommonMoney();
    				$this->estimatedCost->setStdResult ( $estimatedCostResult);
    			}
    			    		    				    			    			if (array_key_exists ( "localLogistics", $this->stdResult )) {
    				$localLogisticsResult=$this->stdResult->{"localLogistics"};
    				$this->localLogistics = new AlibabalogisticsexpressLocalLogistics();
    				$this->localLogistics->setStdResult ( $localLogisticsResult);
    			}
    			    		    				    			    			if (array_key_exists ( "shipper", $this->stdResult )) {
    				$shipperResult=$this->stdResult->{"shipper"};
    				$this->shipper = new AlibabalogisticsexpressContact();
    				$this->shipper->setStdResult ( $shipperResult);
    			}
    			    		    				    			    			if (array_key_exists ( "customsDeclarationInfo", $this->stdResult )) {
    				$customsDeclarationInfoResult=$this->stdResult->{"customsDeclarationInfo"};
    				$this->customsDeclarationInfo = new AlibabalogisticsexpressCustomsDeclarationInfo();
    				$this->customsDeclarationInfo->setStdResult ( $customsDeclarationInfoResult);
    			}
    			    		    				    			    			if (array_key_exists ( "actualGoodsPackage", $this->stdResult )) {
    				$actualGoodsPackageResult=$this->stdResult->{"actualGoodsPackage"};
    				$this->actualGoodsPackage = new AlibabalogisticsexpressGoodsPackage();
    				$this->actualGoodsPackage->setStdResult ( $actualGoodsPackageResult);
    			}
    			    		    				    			    			if (array_key_exists ( "actualPayedAmount", $this->stdResult )) {
    				$actualPayedAmountResult=$this->stdResult->{"actualPayedAmount"};
    				$this->actualPayedAmount = new AlibabalogisticscommonMoney();
    				$this->actualPayedAmount->setStdResult ( $actualPayedAmountResult);
    			}
    			    		    				    			    			if (array_key_exists ( "userActionTraces", $this->stdResult )) {
    			$userActionTracesResult=$this->stdResult->{"userActionTraces"};
    				$object = json_decode ( json_encode ( $userActionTracesResult ), true );
					$this->userActionTraces = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabalogisticsexpressUserActionTraceResult=new AlibabalogisticsexpressUserActionTrace();
						$AlibabalogisticsexpressUserActionTraceResult->setArrayResult($arrayobject );
						$this->userActionTraces [$i] = $AlibabalogisticsexpressUserActionTraceResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsTrackTraces", $this->stdResult )) {
    			$logisticsTrackTracesResult=$this->stdResult->{"logisticsTrackTraces"};
    				$object = json_decode ( json_encode ( $logisticsTrackTracesResult ), true );
					$this->logisticsTrackTraces = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabalogisticsexpressLogisticsTrackTraceResult=new AlibabalogisticsexpressLogisticsTrackTrace();
						$AlibabalogisticsexpressLogisticsTrackTraceResult->setArrayResult($arrayobject );
						$this->logisticsTrackTraces [$i] = $AlibabalogisticsexpressLogisticsTrackTraceResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "paymentStatus", $this->stdResult )) {
    				$this->paymentStatus = $this->stdResult->{"paymentStatus"};
    			}
    			    		    				    			    			if (array_key_exists ( "closeType", $this->stdResult )) {
    				$this->closeType = $this->stdResult->{"closeType"};
    			}
    			    		    				    			    			if (array_key_exists ( "closeReason", $this->stdResult )) {
    				$this->closeReason = $this->stdResult->{"closeReason"};
    			}
    			    		    				    			    			if (array_key_exists ( "warehouseRejectReason", $this->stdResult )) {
    				$this->warehouseRejectReason = $this->stdResult->{"warehouseRejectReason"};
    			}
    			    		    				    			    			if (array_key_exists ( "promptText", $this->stdResult )) {
    				$this->promptText = $this->stdResult->{"promptText"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderUrl", $this->stdResult )) {
    				$this->orderUrl = $this->stdResult->{"orderUrl"};
    			}
    			    		    				    			    			if (array_key_exists ( "solution", $this->stdResult )) {
    				$solutionResult=$this->stdResult->{"solution"};
    				$this->solution = new AlibabalogisticsexpressExpressWTDSolution();
    				$this->solution->setStdResult ( $solutionResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "createOrderTime", $this->arrayResult )) {
    			$this->createOrderTime = $arrayResult['createOrderTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "shippingTime", $this->arrayResult )) {
    			$this->shippingTime = $arrayResult['shippingTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    			    		    			if (array_key_exists ( "mainTrackingNumber", $this->arrayResult )) {
    			$this->mainTrackingNumber = $arrayResult['mainTrackingNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "trackingNumbers", $this->arrayResult )) {
    			$this->trackingNumbers = $arrayResult['trackingNumbers'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderId", $this->arrayResult )) {
    			$this->orderId = $arrayResult['orderId'];
    			}
    		    	    			    		    			if (array_key_exists ( "warehouseMemo", $this->arrayResult )) {
    			$this->warehouseMemo = $arrayResult['warehouseMemo'];
    			}
    		    	    			    		    			if (array_key_exists ( "status", $this->arrayResult )) {
    			$this->status = $arrayResult['status'];
    			}
    		    	    			    		    			if (array_key_exists ( "destinationCountryCode", $this->arrayResult )) {
    			$this->destinationCountryCode = $arrayResult['destinationCountryCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "actualPayedTime", $this->arrayResult )) {
    			$this->actualPayedTime = $arrayResult['actualPayedTime'];
    			}
    		    	    			    		    			if (array_key_exists ( "leavePortTime", $this->arrayResult )) {
    			$this->leavePortTime = $arrayResult['leavePortTime'];
    			}
    		    	    			    		    		if (array_key_exists ( "commoditys", $this->arrayResult )) {
    		$commoditysResult=$arrayResult['commoditys'];
    			$this->commoditys = AlibabalogisticsexpressCommodity();
    			$this->commoditys->$this->setStdResult ( $commoditysResult);
    		}
    		    	    			    		    		if (array_key_exists ( "originalGoodsPackage", $this->arrayResult )) {
    		$originalGoodsPackageResult=$arrayResult['originalGoodsPackage'];
    			    			$this->originalGoodsPackage = new AlibabalogisticsexpressGoodsPackage();
    			    			$this->originalGoodsPackage->$this->setStdResult ( $originalGoodsPackageResult);
    		}
    		    	    			    		    		if (array_key_exists ( "payableAmount", $this->arrayResult )) {
    		$payableAmountResult=$arrayResult['payableAmount'];
    			    			$this->payableAmount = new AlibabalogisticscommonMoney();
    			    			$this->payableAmount->$this->setStdResult ( $payableAmountResult);
    		}
    		    	    			    		    		if (array_key_exists ( "consignee", $this->arrayResult )) {
    		$consigneeResult=$arrayResult['consignee'];
    			    			$this->consignee = new AlibabalogisticsexpressContact();
    			    			$this->consignee->$this->setStdResult ( $consigneeResult);
    		}
    		    	    			    		    		if (array_key_exists ( "estimatedCost", $this->arrayResult )) {
    		$estimatedCostResult=$arrayResult['estimatedCost'];
    			    			$this->estimatedCost = new AlibabalogisticscommonMoney();
    			    			$this->estimatedCost->$this->setStdResult ( $estimatedCostResult);
    		}
    		    	    			    		    		if (array_key_exists ( "localLogistics", $this->arrayResult )) {
    		$localLogisticsResult=$arrayResult['localLogistics'];
    			    			$this->localLogistics = new AlibabalogisticsexpressLocalLogistics();
    			    			$this->localLogistics->$this->setStdResult ( $localLogisticsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "shipper", $this->arrayResult )) {
    		$shipperResult=$arrayResult['shipper'];
    			    			$this->shipper = new AlibabalogisticsexpressContact();
    			    			$this->shipper->$this->setStdResult ( $shipperResult);
    		}
    		    	    			    		    		if (array_key_exists ( "customsDeclarationInfo", $this->arrayResult )) {
    		$customsDeclarationInfoResult=$arrayResult['customsDeclarationInfo'];
    			    			$this->customsDeclarationInfo = new AlibabalogisticsexpressCustomsDeclarationInfo();
    			    			$this->customsDeclarationInfo->$this->setStdResult ( $customsDeclarationInfoResult);
    		}
    		    	    			    		    		if (array_key_exists ( "actualGoodsPackage", $this->arrayResult )) {
    		$actualGoodsPackageResult=$arrayResult['actualGoodsPackage'];
    			    			$this->actualGoodsPackage = new AlibabalogisticsexpressGoodsPackage();
    			    			$this->actualGoodsPackage->$this->setStdResult ( $actualGoodsPackageResult);
    		}
    		    	    			    		    		if (array_key_exists ( "actualPayedAmount", $this->arrayResult )) {
    		$actualPayedAmountResult=$arrayResult['actualPayedAmount'];
    			    			$this->actualPayedAmount = new AlibabalogisticscommonMoney();
    			    			$this->actualPayedAmount->$this->setStdResult ( $actualPayedAmountResult);
    		}
    		    	    			    		    		if (array_key_exists ( "userActionTraces", $this->arrayResult )) {
    		$userActionTracesResult=$arrayResult['userActionTraces'];
    			$this->userActionTraces = AlibabalogisticsexpressUserActionTrace();
    			$this->userActionTraces->$this->setStdResult ( $userActionTracesResult);
    		}
    		    	    			    		    		if (array_key_exists ( "logisticsTrackTraces", $this->arrayResult )) {
    		$logisticsTrackTracesResult=$arrayResult['logisticsTrackTraces'];
    			$this->logisticsTrackTraces = AlibabalogisticsexpressLogisticsTrackTrace();
    			$this->logisticsTrackTraces->$this->setStdResult ( $logisticsTrackTracesResult);
    		}
    		    	    			    		    			if (array_key_exists ( "paymentStatus", $this->arrayResult )) {
    			$this->paymentStatus = $arrayResult['paymentStatus'];
    			}
    		    	    			    		    			if (array_key_exists ( "closeType", $this->arrayResult )) {
    			$this->closeType = $arrayResult['closeType'];
    			}
    		    	    			    		    			if (array_key_exists ( "closeReason", $this->arrayResult )) {
    			$this->closeReason = $arrayResult['closeReason'];
    			}
    		    	    			    		    			if (array_key_exists ( "warehouseRejectReason", $this->arrayResult )) {
    			$this->warehouseRejectReason = $arrayResult['warehouseRejectReason'];
    			}
    		    	    			    		    			if (array_key_exists ( "promptText", $this->arrayResult )) {
    			$this->promptText = $arrayResult['promptText'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderUrl", $this->arrayResult )) {
    			$this->orderUrl = $arrayResult['orderUrl'];
    			}
    		    	    			    		    		if (array_key_exists ( "solution", $this->arrayResult )) {
    		$solutionResult=$arrayResult['solution'];
    			    			$this->solution = new AlibabalogisticsexpressExpressWTDSolution();
    			    			$this->solution->$this->setStdResult ( $solutionResult);
    		}
    		    	    		}
 
   
}
?>