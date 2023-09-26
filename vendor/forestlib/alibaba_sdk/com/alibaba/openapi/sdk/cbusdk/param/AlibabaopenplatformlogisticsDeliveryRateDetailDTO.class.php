<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformlogisticsDeliveryRateDTO.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformlogisticsDeliverySysRateDTO.class.php');

class AlibabaopenplatformlogisticsDeliveryRateDetailDTO extends SDKDomain {

       	
    private $operateType;
    
        /**
    * @return 费率操作类型：INSERT,UPDATE,DELETE
    */
        public function getOperateType() {
        return $this->operateType;
    }
    
    /**
     * 设置费率操作类型：INSERT,UPDATE,DELETE     
     * @param String $operateType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOperateType( $operateType) {
        $this->operateType = $operateType;
    }
    
        	
    private $isSysRate;
    
        /**
    * @return 是否系统模板
    */
        public function getIsSysRate() {
        return $this->isSysRate;
    }
    
    /**
     * 设置是否系统模板     
     * @param Boolean $isSysRate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSysRate( $isSysRate) {
        $this->isSysRate = $isSysRate;
    }
    
        	
    private $toAreaCodeText;
    
        /**
    * @return 地址编码文本，用顿号隔开。例如：上海、福建省、广东省
    */
        public function getToAreaCodeText() {
        return $this->toAreaCodeText;
    }
    
    /**
     * 设置地址编码文本，用顿号隔开。例如：上海、福建省、广东省     
     * @param String $toAreaCodeText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setToAreaCodeText( $toAreaCodeText) {
        $this->toAreaCodeText = $toAreaCodeText;
    }
    
        	
    private $rateDTO;
    
        /**
    * @return 普通子模板费率
    */
        public function getRateDTO() {
        return $this->rateDTO;
    }
    
    /**
     * 设置普通子模板费率     
     * @param AlibabaopenplatformlogisticsDeliveryRateDTO $rateDTO     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRateDTO(AlibabaopenplatformlogisticsDeliveryRateDTO $rateDTO) {
        $this->rateDTO = $rateDTO;
    }
    
        	
    private $sysRateDTO;
    
        /**
    * @return 系统子模板费率
    */
        public function getSysRateDTO() {
        return $this->sysRateDTO;
    }
    
    /**
     * 设置系统子模板费率     
     * @param AlibabaopenplatformlogisticsDeliverySysRateDTO $sysRateDTO     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSysRateDTO(AlibabaopenplatformlogisticsDeliverySysRateDTO $sysRateDTO) {
        $this->sysRateDTO = $sysRateDTO;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "operateType", $this->stdResult )) {
    				$this->operateType = $this->stdResult->{"operateType"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSysRate", $this->stdResult )) {
    				$this->isSysRate = $this->stdResult->{"isSysRate"};
    			}
    			    		    				    			    			if (array_key_exists ( "toAreaCodeText", $this->stdResult )) {
    				$this->toAreaCodeText = $this->stdResult->{"toAreaCodeText"};
    			}
    			    		    				    			    			if (array_key_exists ( "rateDTO", $this->stdResult )) {
    				$rateDTOResult=$this->stdResult->{"rateDTO"};
    				$this->rateDTO = new AlibabaopenplatformlogisticsDeliveryRateDTO();
    				$this->rateDTO->setStdResult ( $rateDTOResult);
    			}
    			    		    				    			    			if (array_key_exists ( "sysRateDTO", $this->stdResult )) {
    				$sysRateDTOResult=$this->stdResult->{"sysRateDTO"};
    				$this->sysRateDTO = new AlibabaopenplatformlogisticsDeliverySysRateDTO();
    				$this->sysRateDTO->setStdResult ( $sysRateDTOResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "operateType", $this->arrayResult )) {
    			$this->operateType = $arrayResult['operateType'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSysRate", $this->arrayResult )) {
    			$this->isSysRate = $arrayResult['isSysRate'];
    			}
    		    	    			    		    			if (array_key_exists ( "toAreaCodeText", $this->arrayResult )) {
    			$this->toAreaCodeText = $arrayResult['toAreaCodeText'];
    			}
    		    	    			    		    		if (array_key_exists ( "rateDTO", $this->arrayResult )) {
    		$rateDTOResult=$arrayResult['rateDTO'];
    			    			$this->rateDTO = new AlibabaopenplatformlogisticsDeliveryRateDTO();
    			    			$this->rateDTO->$this->setStdResult ( $rateDTOResult);
    		}
    		    	    			    		    		if (array_key_exists ( "sysRateDTO", $this->arrayResult )) {
    		$sysRateDTOResult=$arrayResult['sysRateDTO'];
    			    			$this->sysRateDTO = new AlibabaopenplatformlogisticsDeliverySysRateDTO();
    			    			$this->sysRateDTO->$this->setStdResult ( $sysRateDTOResult);
    		}
    		    	    		}
 
   
}
?>