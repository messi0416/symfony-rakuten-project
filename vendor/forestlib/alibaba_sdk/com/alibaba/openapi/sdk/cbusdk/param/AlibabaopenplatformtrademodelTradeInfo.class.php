<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelOrderBaseInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelGuaranteeTermsInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelInternationalLogisticsInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelNativeLogisticsInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelProductItemInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelTradeTermsInfo.class.php');

class AlibabaopenplatformtrademodelTradeInfo extends SDKDomain {

       	
    private $baseInfo;
    
        /**
    * @return 订单基础信息
    */
        public function getBaseInfo() {
        return $this->baseInfo;
    }
    
    /**
     * 设置订单基础信息     
     * @param AlibabaopenplatformtrademodelOrderBaseInfo $baseInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBaseInfo(AlibabaopenplatformtrademodelOrderBaseInfo $baseInfo) {
        $this->baseInfo = $baseInfo;
    }
    
        	
    private $guaranteesTerms;
    
        /**
    * @return 保障条款
    */
        public function getGuaranteesTerms() {
        return $this->guaranteesTerms;
    }
    
    /**
     * 设置保障条款     
     * @param AlibabaopenplatformtrademodelGuaranteeTermsInfo $guaranteesTerms     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGuaranteesTerms(AlibabaopenplatformtrademodelGuaranteeTermsInfo $guaranteesTerms) {
        $this->guaranteesTerms = $guaranteesTerms;
    }
    
        	
    private $internationalLogistics;
    
        /**
    * @return 国际物流
    */
        public function getInternationalLogistics() {
        return $this->internationalLogistics;
    }
    
    /**
     * 设置国际物流     
     * @param AlibabaopenplatformtrademodelInternationalLogisticsInfo $internationalLogistics     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInternationalLogistics(AlibabaopenplatformtrademodelInternationalLogisticsInfo $internationalLogistics) {
        $this->internationalLogistics = $internationalLogistics;
    }
    
        	
    private $nativeLogistics;
    
        /**
    * @return 国内物流
    */
        public function getNativeLogistics() {
        return $this->nativeLogistics;
    }
    
    /**
     * 设置国内物流     
     * @param AlibabaopenplatformtrademodelNativeLogisticsInfo $nativeLogistics     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNativeLogistics(AlibabaopenplatformtrademodelNativeLogisticsInfo $nativeLogistics) {
        $this->nativeLogistics = $nativeLogistics;
    }
    
        	
    private $productItems;
    
        /**
    * @return 商品条目信息
    */
        public function getProductItems() {
        return $this->productItems;
    }
    
    /**
     * 设置商品条目信息     
     * @param array include @see AlibabaopenplatformtrademodelProductItemInfo[] $productItems     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductItems(AlibabaopenplatformtrademodelProductItemInfo $productItems) {
        $this->productItems = $productItems;
    }
    
        	
    private $tradeTerms;
    
        /**
    * @return 交易条款
    */
        public function getTradeTerms() {
        return $this->tradeTerms;
    }
    
    /**
     * 设置交易条款     
     * @param array include @see AlibabaopenplatformtrademodelTradeTermsInfo[] $tradeTerms     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeTerms(AlibabaopenplatformtrademodelTradeTermsInfo $tradeTerms) {
        $this->tradeTerms = $tradeTerms;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "baseInfo", $this->stdResult )) {
    				$baseInfoResult=$this->stdResult->{"baseInfo"};
    				$this->baseInfo = new AlibabaopenplatformtrademodelOrderBaseInfo();
    				$this->baseInfo->setStdResult ( $baseInfoResult);
    			}
    			    		    				    			    			if (array_key_exists ( "guaranteesTerms", $this->stdResult )) {
    				$guaranteesTermsResult=$this->stdResult->{"guaranteesTerms"};
    				$this->guaranteesTerms = new AlibabaopenplatformtrademodelGuaranteeTermsInfo();
    				$this->guaranteesTerms->setStdResult ( $guaranteesTermsResult);
    			}
    			    		    				    			    			if (array_key_exists ( "internationalLogistics", $this->stdResult )) {
    				$internationalLogisticsResult=$this->stdResult->{"internationalLogistics"};
    				$this->internationalLogistics = new AlibabaopenplatformtrademodelInternationalLogisticsInfo();
    				$this->internationalLogistics->setStdResult ( $internationalLogisticsResult);
    			}
    			    		    				    			    			if (array_key_exists ( "nativeLogistics", $this->stdResult )) {
    				$nativeLogisticsResult=$this->stdResult->{"nativeLogistics"};
    				$this->nativeLogistics = new AlibabaopenplatformtrademodelNativeLogisticsInfo();
    				$this->nativeLogistics->setStdResult ( $nativeLogisticsResult);
    			}
    			    		    				    			    			if (array_key_exists ( "productItems", $this->stdResult )) {
    			$productItemsResult=$this->stdResult->{"productItems"};
    				$object = json_decode ( json_encode ( $productItemsResult ), true );
					$this->productItems = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtrademodelProductItemInfoResult=new AlibabaopenplatformtrademodelProductItemInfo();
						$AlibabaopenplatformtrademodelProductItemInfoResult->setArrayResult($arrayobject );
						$this->productItems [$i] = $AlibabaopenplatformtrademodelProductItemInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "tradeTerms", $this->stdResult )) {
    			$tradeTermsResult=$this->stdResult->{"tradeTerms"};
    				$object = json_decode ( json_encode ( $tradeTermsResult ), true );
					$this->tradeTerms = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtrademodelTradeTermsInfoResult=new AlibabaopenplatformtrademodelTradeTermsInfo();
						$AlibabaopenplatformtrademodelTradeTermsInfoResult->setArrayResult($arrayobject );
						$this->tradeTerms [$i] = $AlibabaopenplatformtrademodelTradeTermsInfoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "baseInfo", $this->arrayResult )) {
    		$baseInfoResult=$arrayResult['baseInfo'];
    			    			$this->baseInfo = new AlibabaopenplatformtrademodelOrderBaseInfo();
    			    			$this->baseInfo->$this->setStdResult ( $baseInfoResult);
    		}
    		    	    			    		    		if (array_key_exists ( "guaranteesTerms", $this->arrayResult )) {
    		$guaranteesTermsResult=$arrayResult['guaranteesTerms'];
    			    			$this->guaranteesTerms = new AlibabaopenplatformtrademodelGuaranteeTermsInfo();
    			    			$this->guaranteesTerms->$this->setStdResult ( $guaranteesTermsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "internationalLogistics", $this->arrayResult )) {
    		$internationalLogisticsResult=$arrayResult['internationalLogistics'];
    			    			$this->internationalLogistics = new AlibabaopenplatformtrademodelInternationalLogisticsInfo();
    			    			$this->internationalLogistics->$this->setStdResult ( $internationalLogisticsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "nativeLogistics", $this->arrayResult )) {
    		$nativeLogisticsResult=$arrayResult['nativeLogistics'];
    			    			$this->nativeLogistics = new AlibabaopenplatformtrademodelNativeLogisticsInfo();
    			    			$this->nativeLogistics->$this->setStdResult ( $nativeLogisticsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "productItems", $this->arrayResult )) {
    		$productItemsResult=$arrayResult['productItems'];
    			$this->productItems = AlibabaopenplatformtrademodelProductItemInfo();
    			$this->productItems->$this->setStdResult ( $productItemsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "tradeTerms", $this->arrayResult )) {
    		$tradeTermsResult=$arrayResult['tradeTerms'];
    			$this->tradeTerms = AlibabaopenplatformtrademodelTradeTermsInfo();
    			$this->tradeTerms->$this->setStdResult ( $tradeTermsResult);
    		}
    		    	    		}
 
   
}
?>