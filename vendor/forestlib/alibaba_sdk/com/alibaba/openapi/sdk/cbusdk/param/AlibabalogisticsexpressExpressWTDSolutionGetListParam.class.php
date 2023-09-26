<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabalogisticsexpressGoodsPackage.class.php');

class AlibabalogisticsexpressExpressWTDSolutionGetListParam extends SDKDomain {

       	
    private $originZip;
    
        /**
    * @return 起始地邮编
    */
        public function getOriginZip() {
        return $this->originZip;
    }
    
    /**
     * 设置起始地邮编     
     * @param String $originZip     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOriginZip( $originZip) {
        $this->originZip = $originZip;
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
    
        	
    private $goodsPackage;
    
        /**
    * @return 包裹信息
    */
        public function getGoodsPackage() {
        return $this->goodsPackage;
    }
    
    /**
     * 设置包裹信息     
     * @param AlibabalogisticsexpressGoodsPackage $goodsPackage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGoodsPackage(AlibabalogisticsexpressGoodsPackage $goodsPackage) {
        $this->goodsPackage = $goodsPackage;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "originZip", $this->stdResult )) {
    				$this->originZip = $this->stdResult->{"originZip"};
    			}
    			    		    				    			    			if (array_key_exists ( "destinationCountryCode", $this->stdResult )) {
    				$this->destinationCountryCode = $this->stdResult->{"destinationCountryCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "goodsPackage", $this->stdResult )) {
    				$goodsPackageResult=$this->stdResult->{"goodsPackage"};
    				$this->goodsPackage = new AlibabalogisticsexpressGoodsPackage();
    				$this->goodsPackage->setStdResult ( $goodsPackageResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "originZip", $this->arrayResult )) {
    			$this->originZip = $arrayResult['originZip'];
    			}
    		    	    			    		    			if (array_key_exists ( "destinationCountryCode", $this->arrayResult )) {
    			$this->destinationCountryCode = $arrayResult['destinationCountryCode'];
    			}
    		    	    			    		    		if (array_key_exists ( "goodsPackage", $this->arrayResult )) {
    		$goodsPackageResult=$arrayResult['goodsPackage'];
    			    			$this->goodsPackage = new AlibabalogisticsexpressGoodsPackage();
    			    			$this->goodsPackage->$this->setStdResult ( $goodsPackageResult);
    		}
    		    	    		}
 
   
}
?>