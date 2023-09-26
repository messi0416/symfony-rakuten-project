<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizReceiveAddressGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizInvoiceGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizOtherInfoGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizCargoGroup.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeBizStepPayGroup.class.php');

class AlibabaopenplatformtradeBizMakeSingleOrderGroup extends SDKDomain {

       	
    private $receiveAddressGroup;
    
        /**
    * @return 收货地址信息
    */
        public function getReceiveAddressGroup() {
        return $this->receiveAddressGroup;
    }
    
    /**
     * 设置收货地址信息     
     * @param AlibabaopenplatformtradeBizReceiveAddressGroup $receiveAddressGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setReceiveAddressGroup(AlibabaopenplatformtradeBizReceiveAddressGroup $receiveAddressGroup) {
        $this->receiveAddressGroup = $receiveAddressGroup;
    }
    
        	
    private $invoiceGroup;
    
        /**
    * @return 发票信息
    */
        public function getInvoiceGroup() {
        return $this->invoiceGroup;
    }
    
    /**
     * 设置发票信息     
     * @param AlibabaopenplatformtradeBizInvoiceGroup $invoiceGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInvoiceGroup(AlibabaopenplatformtradeBizInvoiceGroup $invoiceGroup) {
        $this->invoiceGroup = $invoiceGroup;
    }
    
        	
    private $otherInfoGroup;
    
        /**
    * @return 多个其它信息时使用，比如一些费用
    */
        public function getOtherInfoGroup() {
        return $this->otherInfoGroup;
    }
    
    /**
     * 设置多个其它信息时使用，比如一些费用     
     * @param AlibabaopenplatformtradeBizOtherInfoGroup $otherInfoGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOtherInfoGroup(AlibabaopenplatformtradeBizOtherInfoGroup $otherInfoGroup) {
        $this->otherInfoGroup = $otherInfoGroup;
    }
    
        	
    private $cargoGroups;
    
        /**
    * @return 商品信息
    */
        public function getCargoGroups() {
        return $this->cargoGroups;
    }
    
    /**
     * 设置商品信息     
     * @param array include @see AlibabaopenplatformtradeBizCargoGroup[] $cargoGroups     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCargoGroups(AlibabaopenplatformtradeBizCargoGroup $cargoGroups) {
        $this->cargoGroups = $cargoGroups;
    }
    
        	
    private $stepPayGroup;
    
        /**
    * @return 阶段支付信息
    */
        public function getStepPayGroup() {
        return $this->stepPayGroup;
    }
    
    /**
     * 设置阶段支付信息     
     * @param AlibabaopenplatformtradeBizStepPayGroup $stepPayGroup     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStepPayGroup(AlibabaopenplatformtradeBizStepPayGroup $stepPayGroup) {
        $this->stepPayGroup = $stepPayGroup;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "receiveAddressGroup", $this->stdResult )) {
    				$receiveAddressGroupResult=$this->stdResult->{"receiveAddressGroup"};
    				$this->receiveAddressGroup = new AlibabaopenplatformtradeBizReceiveAddressGroup();
    				$this->receiveAddressGroup->setStdResult ( $receiveAddressGroupResult);
    			}
    			    		    				    			    			if (array_key_exists ( "invoiceGroup", $this->stdResult )) {
    				$invoiceGroupResult=$this->stdResult->{"invoiceGroup"};
    				$this->invoiceGroup = new AlibabaopenplatformtradeBizInvoiceGroup();
    				$this->invoiceGroup->setStdResult ( $invoiceGroupResult);
    			}
    			    		    				    			    			if (array_key_exists ( "otherInfoGroup", $this->stdResult )) {
    				$otherInfoGroupResult=$this->stdResult->{"otherInfoGroup"};
    				$this->otherInfoGroup = new AlibabaopenplatformtradeBizOtherInfoGroup();
    				$this->otherInfoGroup->setStdResult ( $otherInfoGroupResult);
    			}
    			    		    				    			    			if (array_key_exists ( "cargoGroups", $this->stdResult )) {
    			$cargoGroupsResult=$this->stdResult->{"cargoGroups"};
    				$object = json_decode ( json_encode ( $cargoGroupsResult ), true );
					$this->cargoGroups = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtradeBizCargoGroupResult=new AlibabaopenplatformtradeBizCargoGroup();
						$AlibabaopenplatformtradeBizCargoGroupResult->setArrayResult($arrayobject );
						$this->cargoGroups [$i] = $AlibabaopenplatformtradeBizCargoGroupResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "stepPayGroup", $this->stdResult )) {
    				$stepPayGroupResult=$this->stdResult->{"stepPayGroup"};
    				$this->stepPayGroup = new AlibabaopenplatformtradeBizStepPayGroup();
    				$this->stepPayGroup->setStdResult ( $stepPayGroupResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "receiveAddressGroup", $this->arrayResult )) {
    		$receiveAddressGroupResult=$arrayResult['receiveAddressGroup'];
    			    			$this->receiveAddressGroup = new AlibabaopenplatformtradeBizReceiveAddressGroup();
    			    			$this->receiveAddressGroup->$this->setStdResult ( $receiveAddressGroupResult);
    		}
    		    	    			    		    		if (array_key_exists ( "invoiceGroup", $this->arrayResult )) {
    		$invoiceGroupResult=$arrayResult['invoiceGroup'];
    			    			$this->invoiceGroup = new AlibabaopenplatformtradeBizInvoiceGroup();
    			    			$this->invoiceGroup->$this->setStdResult ( $invoiceGroupResult);
    		}
    		    	    			    		    		if (array_key_exists ( "otherInfoGroup", $this->arrayResult )) {
    		$otherInfoGroupResult=$arrayResult['otherInfoGroup'];
    			    			$this->otherInfoGroup = new AlibabaopenplatformtradeBizOtherInfoGroup();
    			    			$this->otherInfoGroup->$this->setStdResult ( $otherInfoGroupResult);
    		}
    		    	    			    		    		if (array_key_exists ( "cargoGroups", $this->arrayResult )) {
    		$cargoGroupsResult=$arrayResult['cargoGroups'];
    			$this->cargoGroups = AlibabaopenplatformtradeBizCargoGroup();
    			$this->cargoGroups->$this->setStdResult ( $cargoGroupsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "stepPayGroup", $this->arrayResult )) {
    		$stepPayGroupResult=$arrayResult['stepPayGroup'];
    			    			$this->stepPayGroup = new AlibabaopenplatformtradeBizStepPayGroup();
    			    			$this->stepPayGroup->$this->setStdResult ( $stepPayGroupResult);
    		}
    		    	    		}
 
   
}
?>