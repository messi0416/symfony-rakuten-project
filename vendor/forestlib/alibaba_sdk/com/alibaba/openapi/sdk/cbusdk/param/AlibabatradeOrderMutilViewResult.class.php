<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeSimpleBuyer.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeOrderViewModel.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeReceiveAddress.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatraderesultCodeDef.class.php');

class AlibabatradeOrderMutilViewResult extends SDKDomain {

       	
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
    
        	
    private $orderModels;
    
        /**
    * @return 
    */
        public function getOrderModels() {
        return $this->orderModels;
    }
    
    /**
     * 设置     
     * @param array include @see AlibabatradeOrderViewModel[] $orderModels     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderModels(AlibabatradeOrderViewModel $orderModels) {
        $this->orderModels = $orderModels;
    }
    
        	
    private $receiveAddress;
    
        /**
    * @return 
    */
        public function getReceiveAddress() {
        return $this->receiveAddress;
    }
    
    /**
     * 设置     
     * @param AlibabatradeReceiveAddress $receiveAddress     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setReceiveAddress(AlibabatradeReceiveAddress $receiveAddress) {
        $this->receiveAddress = $receiveAddress;
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
     * @param AlibabatraderesultCodeDef $resultCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResultCode(AlibabatraderesultCodeDef $resultCode) {
        $this->resultCode = $resultCode;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "buyer", $this->stdResult )) {
    				$buyerResult=$this->stdResult->{"buyer"};
    				$this->buyer = new AlibabatradeSimpleBuyer();
    				$this->buyer->setStdResult ( $buyerResult);
    			}
    			    		    				    			    			if (array_key_exists ( "orderModels", $this->stdResult )) {
    			$orderModelsResult=$this->stdResult->{"orderModels"};
    				$object = json_decode ( json_encode ( $orderModelsResult ), true );
					$this->orderModels = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradeOrderViewModelResult=new AlibabatradeOrderViewModel();
						$AlibabatradeOrderViewModelResult->setArrayResult($arrayobject );
						$this->orderModels [$i] = $AlibabatradeOrderViewModelResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "receiveAddress", $this->stdResult )) {
    				$receiveAddressResult=$this->stdResult->{"receiveAddress"};
    				$this->receiveAddress = new AlibabatradeReceiveAddress();
    				$this->receiveAddress->setStdResult ( $receiveAddressResult);
    			}
    			    		    				    			    			if (array_key_exists ( "resultCode", $this->stdResult )) {
    				$resultCodeResult=$this->stdResult->{"resultCode"};
    				$this->resultCode = new AlibabatraderesultCodeDef();
    				$this->resultCode->setStdResult ( $resultCodeResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "buyer", $this->arrayResult )) {
    		$buyerResult=$arrayResult['buyer'];
    			    			$this->buyer = new AlibabatradeSimpleBuyer();
    			    			$this->buyer->$this->setStdResult ( $buyerResult);
    		}
    		    	    			    		    		if (array_key_exists ( "orderModels", $this->arrayResult )) {
    		$orderModelsResult=$arrayResult['orderModels'];
    			$this->orderModels = AlibabatradeOrderViewModel();
    			$this->orderModels->$this->setStdResult ( $orderModelsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "receiveAddress", $this->arrayResult )) {
    		$receiveAddressResult=$arrayResult['receiveAddress'];
    			    			$this->receiveAddress = new AlibabatradeReceiveAddress();
    			    			$this->receiveAddress->$this->setStdResult ( $receiveAddressResult);
    		}
    		    	    			    		    		if (array_key_exists ( "resultCode", $this->arrayResult )) {
    		$resultCodeResult=$arrayResult['resultCode'];
    			    			$this->resultCode = new AlibabatraderesultCodeDef();
    			    			$this->resultCode->$this->setStdResult ( $resultCodeResult);
    		}
    		    	    		}
 
   
}
?>