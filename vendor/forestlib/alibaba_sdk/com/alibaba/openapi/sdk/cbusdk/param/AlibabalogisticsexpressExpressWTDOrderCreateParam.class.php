<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressCustomsDeclarationInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressLocalLogistics.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressContact.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressCommodity.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressContact.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressGoodsPackage.class.php');

class AlibabalogisticsexpressExpressWTDOrderCreateParam extends SDKDomain {

       	
    private $solutionId;
    
        /**
    * @return 方案ID
    */
        public function getSolutionId() {
        return $this->solutionId;
    }
    
    /**
     * 设置方案ID     
     * @param Long $solutionId     
     * 参数示例：<pre>12346</pre>     
     * 此参数必填     */
    public function setSolutionId( $solutionId) {
        $this->solutionId = $solutionId;
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
     * 参数示例：<pre>US</pre>     
     * 此参数必填     */
    public function setDestinationCountryCode( $destinationCountryCode) {
        $this->destinationCountryCode = $destinationCountryCode;
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
    
        	
    private $goodsPackage;
    
        /**
    * @return 货物包裹
    */
        public function getGoodsPackage() {
        return $this->goodsPackage;
    }
    
    /**
     * 设置货物包裹     
     * @param AlibabalogisticsexpressGoodsPackage $goodsPackage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGoodsPackage(AlibabalogisticsexpressGoodsPackage $goodsPackage) {
        $this->goodsPackage = $goodsPackage;
    }
    
        	
    private $agreeUserAgreement;
    
        /**
    * @return 是否同意用户协议，必须同意协议才能创建订单
    */
        public function getAgreeUserAgreement() {
        return $this->agreeUserAgreement;
    }
    
    /**
     * 设置是否同意用户协议，必须同意协议才能创建订单     
     * @param Boolean $agreeUserAgreement     
     * 参数示例：<pre>true</pre>     
     * 此参数必填     */
    public function setAgreeUserAgreement( $agreeUserAgreement) {
        $this->agreeUserAgreement = $agreeUserAgreement;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "solutionId", $this->stdResult )) {
    				$this->solutionId = $this->stdResult->{"solutionId"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    				    			    			if (array_key_exists ( "destinationCountryCode", $this->stdResult )) {
    				$this->destinationCountryCode = $this->stdResult->{"destinationCountryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "customsDeclarationInfo", $this->stdResult )) {
    				$customsDeclarationInfoResult=$this->stdResult->{"customsDeclarationInfo"};
    				$this->customsDeclarationInfo = new AlibabalogisticsexpressCustomsDeclarationInfo();
    				$this->customsDeclarationInfo->setStdResult ( $customsDeclarationInfoResult);
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
    			    		    				    			    			if (array_key_exists ( "consignee", $this->stdResult )) {
    				$consigneeResult=$this->stdResult->{"consignee"};
    				$this->consignee = new AlibabalogisticsexpressContact();
    				$this->consignee->setStdResult ( $consigneeResult);
    			}
    			    		    				    			    			if (array_key_exists ( "goodsPackage", $this->stdResult )) {
    				$goodsPackageResult=$this->stdResult->{"goodsPackage"};
    				$this->goodsPackage = new AlibabalogisticsexpressGoodsPackage();
    				$this->goodsPackage->setStdResult ( $goodsPackageResult);
    			}
    			    		    				    			    			if (array_key_exists ( "agreeUserAgreement", $this->stdResult )) {
    				$this->agreeUserAgreement = $this->stdResult->{"agreeUserAgreement"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "solutionId", $this->arrayResult )) {
    			$this->solutionId = $arrayResult['solutionId'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    			    		    			if (array_key_exists ( "destinationCountryCode", $this->arrayResult )) {
    			$this->destinationCountryCode = $arrayResult['destinationCountryCode'];
    			}
    		    	    			    		    		if (array_key_exists ( "customsDeclarationInfo", $this->arrayResult )) {
    		$customsDeclarationInfoResult=$arrayResult['customsDeclarationInfo'];
    			    			$this->customsDeclarationInfo = new AlibabalogisticsexpressCustomsDeclarationInfo();
    			    			$this->customsDeclarationInfo->$this->setStdResult ( $customsDeclarationInfoResult);
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
    		    	    			    		    		if (array_key_exists ( "commoditys", $this->arrayResult )) {
    		$commoditysResult=$arrayResult['commoditys'];
    			$this->commoditys = AlibabalogisticsexpressCommodity();
    			$this->commoditys->$this->setStdResult ( $commoditysResult);
    		}
    		    	    			    		    		if (array_key_exists ( "consignee", $this->arrayResult )) {
    		$consigneeResult=$arrayResult['consignee'];
    			    			$this->consignee = new AlibabalogisticsexpressContact();
    			    			$this->consignee->$this->setStdResult ( $consigneeResult);
    		}
    		    	    			    		    		if (array_key_exists ( "goodsPackage", $this->arrayResult )) {
    		$goodsPackageResult=$arrayResult['goodsPackage'];
    			    			$this->goodsPackage = new AlibabalogisticsexpressGoodsPackage();
    			    			$this->goodsPackage->$this->setStdResult ( $goodsPackageResult);
    		}
    		    	    			    		    			if (array_key_exists ( "agreeUserAgreement", $this->arrayResult )) {
    			$this->agreeUserAgreement = $arrayResult['agreeUserAgreement'];
    			}
    		    	    		}
 
   
}
?>