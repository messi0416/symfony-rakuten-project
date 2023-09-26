<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticscommonMoney.class.php');

class AlibabalogisticsexpressCustomsDeclarationInfo extends SDKDomain {

       	
    private $needNormalDeclaration;
    
        /**
    * @return 是否需要正式报关
    */
        public function getNeedNormalDeclaration() {
        return $this->needNormalDeclaration;
    }
    
    /**
     * 设置是否需要正式报关     
     * @param Boolean $needNormalDeclaration     
     * 参数示例：<pre>true</pre>     
     * 此参数必填     */
    public function setNeedNormalDeclaration( $needNormalDeclaration) {
        $this->needNormalDeclaration = $needNormalDeclaration;
    }
    
        	
    private $totalDeclaredValue;
    
        /**
    * @return 
    */
        public function getTotalDeclaredValue() {
        return $this->totalDeclaredValue;
    }
    
    /**
     * 设置     
     * @param AlibabalogisticscommonMoney $totalDeclaredValue     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTotalDeclaredValue(AlibabalogisticscommonMoney $totalDeclaredValue) {
        $this->totalDeclaredValue = $totalDeclaredValue;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "needNormalDeclaration", $this->stdResult )) {
    				$this->needNormalDeclaration = $this->stdResult->{"needNormalDeclaration"};
    			}
    			    		    				    			    			if (array_key_exists ( "totalDeclaredValue", $this->stdResult )) {
    				$totalDeclaredValueResult=$this->stdResult->{"totalDeclaredValue"};
    				$this->totalDeclaredValue = new AlibabalogisticscommonMoney();
    				$this->totalDeclaredValue->setStdResult ( $totalDeclaredValueResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "needNormalDeclaration", $this->arrayResult )) {
    			$this->needNormalDeclaration = $arrayResult['needNormalDeclaration'];
    			}
    		    	    			    		    		if (array_key_exists ( "totalDeclaredValue", $this->arrayResult )) {
    		$totalDeclaredValueResult=$arrayResult['totalDeclaredValue'];
    			    			$this->totalDeclaredValue = new AlibabalogisticscommonMoney();
    			    			$this->totalDeclaredValue->$this->setStdResult ( $totalDeclaredValueResult);
    		}
    		    	    		}
 
   
}
?>