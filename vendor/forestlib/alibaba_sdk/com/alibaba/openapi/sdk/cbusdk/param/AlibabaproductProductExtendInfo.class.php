<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductProductExtendInfo extends SDKDomain {

       	
    private $key;
    
        /**
    * @return 扩展结构的key
    */
        public function getKey() {
        return $this->key;
    }
    
    /**
     * 设置扩展结构的key     
     * @param String $key     
     * 参数示例：<pre>代销价格,consignPrice;
买家保障,buyerProtection;</pre>     
     * 此参数必填     */
    public function setKey( $key) {
        $this->key = $key;
    }
    
        	
    private $value;
    
        /**
    * @return 扩展结构的value
    */
        public function getValue() {
        return $this->value;
    }
    
    /**
     * 设置扩展结构的value     
     * @param String $value     
     * 参数示例：<pre>代销价格,key为skuId，value为用户设置的代销价，
示例：31151771910:2088.0;31151771909:2088.0;31151771908:2088.0;31152339121:2088.0;
买家保障,string数组，value为买保全拼，
示例：["psbj","swtwlybt","swtbh","ssbxsfh"]</pre>     
     * 此参数必填     */
    public function setValue( $value) {
        $this->value = $value;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "key", $this->stdResult )) {
    				$this->key = $this->stdResult->{"key"};
    			}
    			    		    				    			    			if (array_key_exists ( "value", $this->stdResult )) {
    				$this->value = $this->stdResult->{"value"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "key", $this->arrayResult )) {
    			$this->key = $arrayResult['key'];
    			}
    		    	    			    		    			if (array_key_exists ( "value", $this->arrayResult )) {
    			$this->value = $arrayResult['value'];
    			}
    		    	    		}
 
   
}
?>