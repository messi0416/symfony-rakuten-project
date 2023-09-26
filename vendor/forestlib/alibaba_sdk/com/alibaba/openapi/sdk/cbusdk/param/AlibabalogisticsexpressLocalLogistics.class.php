<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsexpressLocalLogistics extends SDKDomain {

       	
    private $logisticsNumbers;
    
        /**
    * @return 国内物流单号
    */
        public function getLogisticsNumbers() {
        return $this->logisticsNumbers;
    }
    
    /**
     * 设置国内物流单号     
     * @param array include @see String[] $logisticsNumbers     
     * 参数示例：<pre>1324，13445</pre>     
     * 此参数必填     */
    public function setLogisticsNumbers( $logisticsNumbers) {
        $this->logisticsNumbers = $logisticsNumbers;
    }
    
        	
    private $logisticsCompany;
    
        /**
    * @return 国内物流公司，EMS：EMS，
Yuantong Express：圆通速递，
ZTO Express：中通速递，
SHENTONG Express：申通速递，
YUNDA：韵达快运，
TTKExpress(TianTianExpress)：天天快递，
S.F.Express：顺丰速运，
FedEx：联邦快递，
ZJS Express：宅急送，
EBON Express：一邦速递，
CRE(ZhongTIEExpress)：中铁快运，
Deppon Express：德邦物流，
HT Express：汇通快运，
CNEX Express：佳吉快运，
Stars Express：星晨急便，
CCES：CCES，
OTHERS：其它
    */
        public function getLogisticsCompany() {
        return $this->logisticsCompany;
    }
    
    /**
     * 设置国内物流公司，EMS：EMS，
Yuantong Express：圆通速递，
ZTO Express：中通速递，
SHENTONG Express：申通速递，
YUNDA：韵达快运，
TTKExpress(TianTianExpress)：天天快递，
S.F.Express：顺丰速运，
FedEx：联邦快递，
ZJS Express：宅急送，
EBON Express：一邦速递，
CRE(ZhongTIEExpress)：中铁快运，
Deppon Express：德邦物流，
HT Express：汇通快运，
CNEX Express：佳吉快运，
Stars Express：星晨急便，
CCES：CCES，
OTHERS：其它     
     * @param String $logisticsCompany     
     * 参数示例：<pre>YUNDA</pre>     
     * 此参数必填     */
    public function setLogisticsCompany( $logisticsCompany) {
        $this->logisticsCompany = $logisticsCompany;
    }
    
        	
    private $packageQuantity;
    
        /**
    * @return 包裹数量
    */
        public function getPackageQuantity() {
        return $this->packageQuantity;
    }
    
    /**
     * 设置包裹数量     
     * @param Integer $packageQuantity     
     * 参数示例：<pre>3</pre>     
     * 此参数必填     */
    public function setPackageQuantity( $packageQuantity) {
        $this->packageQuantity = $packageQuantity;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "logisticsNumbers", $this->stdResult )) {
    				$this->logisticsNumbers = $this->stdResult->{"logisticsNumbers"};
    			}
    			    		    				    			    			if (array_key_exists ( "logisticsCompany", $this->stdResult )) {
    				$this->logisticsCompany = $this->stdResult->{"logisticsCompany"};
    			}
    			    		    				    			    			if (array_key_exists ( "packageQuantity", $this->stdResult )) {
    				$this->packageQuantity = $this->stdResult->{"packageQuantity"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "logisticsNumbers", $this->arrayResult )) {
    			$this->logisticsNumbers = $arrayResult['logisticsNumbers'];
    			}
    		    	    			    		    			if (array_key_exists ( "logisticsCompany", $this->arrayResult )) {
    			$this->logisticsCompany = $arrayResult['logisticsCompany'];
    			}
    		    	    			    		    			if (array_key_exists ( "packageQuantity", $this->arrayResult )) {
    			$this->packageQuantity = $arrayResult['packageQuantity'];
    			}
    		    	    		}
 
   
}
?>