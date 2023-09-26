<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabapaymentPayChannel.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabapaymentPayChannel.class.php');

class AlibabapaymentPayChannels extends SDKDomain {

       	
    private $availbleChannels;
    
        /**
    * @return 可选支付渠道
    */
        public function getAvailbleChannels() {
        return $this->availbleChannels;
    }
    
    /**
     * 设置可选支付渠道     
     * @param array include @see AlibabapaymentPayChannel[] $availbleChannels     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAvailbleChannels(AlibabapaymentPayChannel $availbleChannels) {
        $this->availbleChannels = $availbleChannels;
    }
    
        	
    private $defaultSelected;
    
        /**
    * @return 默认已选支付渠道
    */
        public function getDefaultSelected() {
        return $this->defaultSelected;
    }
    
    /**
     * 设置默认已选支付渠道     
     * @param AlibabapaymentPayChannel $defaultSelected     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDefaultSelected(AlibabapaymentPayChannel $defaultSelected) {
        $this->defaultSelected = $defaultSelected;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "availbleChannels", $this->stdResult )) {
    			$availbleChannelsResult=$this->stdResult->{"availbleChannels"};
    				$object = json_decode ( json_encode ( $availbleChannelsResult ), true );
					$this->availbleChannels = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabapaymentPayChannelResult=new AlibabapaymentPayChannel();
						$AlibabapaymentPayChannelResult->setArrayResult($arrayobject );
						$this->availbleChannels [$i] = $AlibabapaymentPayChannelResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "defaultSelected", $this->stdResult )) {
    				$defaultSelectedResult=$this->stdResult->{"defaultSelected"};
    				$this->defaultSelected = new AlibabapaymentPayChannel();
    				$this->defaultSelected->setStdResult ( $defaultSelectedResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "availbleChannels", $this->arrayResult )) {
    		$availbleChannelsResult=$arrayResult['availbleChannels'];
    			$this->availbleChannels = AlibabapaymentPayChannel();
    			$this->availbleChannels->$this->setStdResult ( $availbleChannelsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "defaultSelected", $this->arrayResult )) {
    		$defaultSelectedResult=$arrayResult['defaultSelected'];
    			    			$this->defaultSelected = new AlibabapaymentPayChannel();
    			    			$this->defaultSelected->$this->setStdResult ( $defaultSelectedResult);
    		}
    		    	    		}
 
   
}
?>