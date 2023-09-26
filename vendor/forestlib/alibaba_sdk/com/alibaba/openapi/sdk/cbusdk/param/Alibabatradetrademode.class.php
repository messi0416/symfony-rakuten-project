<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeSubPayInfo.class.php');

class Alibabatradetrademode extends SDKDomain {

       	
    private $desc;
    
        /**
    * @return 
    */
        public function getDesc() {
        return $this->desc;
    }
    
    /**
     * 设置     
     * @param String $desc     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDesc( $desc) {
        $this->desc = $desc;
    }
    
        	
    private $forbiddenV3;
    
        /**
    * @return 
    */
        public function getForbiddenV3() {
        return $this->forbiddenV3;
    }
    
    /**
     * 设置     
     * @param Boolean $forbiddenV3     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setForbiddenV3( $forbiddenV3) {
        $this->forbiddenV3 = $forbiddenV3;
    }
    
        	
    private $isSupportInstantPay;
    
        /**
    * @return 
    */
        public function getIsSupportInstantPay() {
        return $this->isSupportInstantPay;
    }
    
    /**
     * 设置     
     * @param Integer $isSupportInstantPay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSupportInstantPay( $isSupportInstantPay) {
        $this->isSupportInstantPay = $isSupportInstantPay;
    }
    
        	
    private $name;
    
        /**
    * @return 
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $number;
    
        /**
    * @return 
    */
        public function getNumber() {
        return $this->number;
    }
    
    /**
     * 设置     
     * @param Integer $number     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setNumber( $number) {
        $this->number = $number;
    }
    
        	
    private $processFlowId;
    
        /**
    * @return 
    */
        public function getProcessFlowId() {
        return $this->processFlowId;
    }
    
    /**
     * 设置     
     * @param String $processFlowId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProcessFlowId( $processFlowId) {
        $this->processFlowId = $processFlowId;
    }
    
        	
    private $processTemplateCode;
    
        /**
    * @return 
    */
        public function getProcessTemplateCode() {
        return $this->processTemplateCode;
    }
    
    /**
     * 设置     
     * @param String $processTemplateCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProcessTemplateCode( $processTemplateCode) {
        $this->processTemplateCode = $processTemplateCode;
    }
    
        	
    private $tradeMode;
    
        /**
    * @return 
    */
        public function getTradeMode() {
        return $this->tradeMode;
    }
    
    /**
     * 设置     
     * @param String $tradeMode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeMode( $tradeMode) {
        $this->tradeMode = $tradeMode;
    }
    
        	
    private $tradeWay;
    
        /**
    * @return 
    */
        public function getTradeWay() {
        return $this->tradeWay;
    }
    
    /**
     * 设置     
     * @param String $tradeWay     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeWay( $tradeWay) {
        $this->tradeWay = $tradeWay;
    }
    
        	
    private $tradeWayScene;
    
        /**
    * @return 
    */
        public function getTradeWayScene() {
        return $this->tradeWayScene;
    }
    
    /**
     * 设置     
     * @param String $tradeWayScene     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTradeWayScene( $tradeWayScene) {
        $this->tradeWayScene = $tradeWayScene;
    }
    
        	
    private $type;
    
        /**
    * @return 
    */
        public function getType() {
        return $this->type;
    }
    
    /**
     * 设置     
     * @param String $type     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setType( $type) {
        $this->type = $type;
    }
    
        	
    private $version;
    
        /**
    * @return 
    */
        public function getVersion() {
        return $this->version;
    }
    
    /**
     * 设置     
     * @param String $version     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setVersion( $version) {
        $this->version = $version;
    }
    
        	
    private $subPayInfors;
    
        /**
    * @return 
    */
        public function getSubPayInfors() {
        return $this->subPayInfors;
    }
    
    /**
     * 设置     
     * @param array include @see AlibabatradeSubPayInfo[] $subPayInfors     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSubPayInfors(AlibabatradeSubPayInfo $subPayInfors) {
        $this->subPayInfors = $subPayInfors;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "desc", $this->stdResult )) {
    				$this->desc = $this->stdResult->{"desc"};
    			}
    			    		    				    			    			if (array_key_exists ( "forbiddenV3", $this->stdResult )) {
    				$this->forbiddenV3 = $this->stdResult->{"forbiddenV3"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSupportInstantPay", $this->stdResult )) {
    				$this->isSupportInstantPay = $this->stdResult->{"isSupportInstantPay"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "number", $this->stdResult )) {
    				$this->number = $this->stdResult->{"number"};
    			}
    			    		    				    			    			if (array_key_exists ( "processFlowId", $this->stdResult )) {
    				$this->processFlowId = $this->stdResult->{"processFlowId"};
    			}
    			    		    				    			    			if (array_key_exists ( "processTemplateCode", $this->stdResult )) {
    				$this->processTemplateCode = $this->stdResult->{"processTemplateCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeMode", $this->stdResult )) {
    				$this->tradeMode = $this->stdResult->{"tradeMode"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeWay", $this->stdResult )) {
    				$this->tradeWay = $this->stdResult->{"tradeWay"};
    			}
    			    		    				    			    			if (array_key_exists ( "tradeWayScene", $this->stdResult )) {
    				$this->tradeWayScene = $this->stdResult->{"tradeWayScene"};
    			}
    			    		    				    			    			if (array_key_exists ( "type", $this->stdResult )) {
    				$this->type = $this->stdResult->{"type"};
    			}
    			    		    				    			    			if (array_key_exists ( "version", $this->stdResult )) {
    				$this->version = $this->stdResult->{"version"};
    			}
    			    		    				    			    			if (array_key_exists ( "subPayInfors", $this->stdResult )) {
    			$subPayInforsResult=$this->stdResult->{"subPayInfors"};
    				$object = json_decode ( json_encode ( $subPayInforsResult ), true );
					$this->subPayInfors = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradeSubPayInfoResult=new AlibabatradeSubPayInfo();
						$AlibabatradeSubPayInfoResult->setArrayResult($arrayobject );
						$this->subPayInfors [$i] = $AlibabatradeSubPayInfoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "desc", $this->arrayResult )) {
    			$this->desc = $arrayResult['desc'];
    			}
    		    	    			    		    			if (array_key_exists ( "forbiddenV3", $this->arrayResult )) {
    			$this->forbiddenV3 = $arrayResult['forbiddenV3'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSupportInstantPay", $this->arrayResult )) {
    			$this->isSupportInstantPay = $arrayResult['isSupportInstantPay'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "number", $this->arrayResult )) {
    			$this->number = $arrayResult['number'];
    			}
    		    	    			    		    			if (array_key_exists ( "processFlowId", $this->arrayResult )) {
    			$this->processFlowId = $arrayResult['processFlowId'];
    			}
    		    	    			    		    			if (array_key_exists ( "processTemplateCode", $this->arrayResult )) {
    			$this->processTemplateCode = $arrayResult['processTemplateCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeMode", $this->arrayResult )) {
    			$this->tradeMode = $arrayResult['tradeMode'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeWay", $this->arrayResult )) {
    			$this->tradeWay = $arrayResult['tradeWay'];
    			}
    		    	    			    		    			if (array_key_exists ( "tradeWayScene", $this->arrayResult )) {
    			$this->tradeWayScene = $arrayResult['tradeWayScene'];
    			}
    		    	    			    		    			if (array_key_exists ( "type", $this->arrayResult )) {
    			$this->type = $arrayResult['type'];
    			}
    		    	    			    		    			if (array_key_exists ( "version", $this->arrayResult )) {
    			$this->version = $arrayResult['version'];
    			}
    		    	    			    		    		if (array_key_exists ( "subPayInfors", $this->arrayResult )) {
    		$subPayInforsResult=$arrayResult['subPayInfors'];
    			$this->subPayInfors = AlibabatradeSubPayInfo();
    			$this->subPayInfors->$this->setStdResult ( $subPayInforsResult);
    		}
    		    	    		}
 
   
}
?>