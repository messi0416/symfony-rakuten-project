<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformlogisticsDeliverySubTemplateDTO extends SDKDomain {

       	
    private $chargeType;
    
        /**
    * @return 计件类型。0:重量 1:件数 2:体积
    */
        public function getChargeType() {
        return $this->chargeType;
    }
    
    /**
     * 设置计件类型。0:重量 1:件数 2:体积     
     * @param Integer $chargeType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setChargeType( $chargeType) {
        $this->chargeType = $chargeType;
    }
    
        	
    private $creator;
    
        /**
    * @return 创建人
    */
        public function getCreator() {
        return $this->creator;
    }
    
    /**
     * 设置创建人     
     * @param String $creator     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCreator( $creator) {
        $this->creator = $creator;
    }
    
        	
    private $gmtCreate;
    
        /**
    * @return 创建时间
    */
        public function getGmtCreate() {
        return $this->gmtCreate;
    }
    
    /**
     * 设置创建时间     
     * @param Date $gmtCreate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGmtCreate( $gmtCreate) {
        $this->gmtCreate = $gmtCreate;
    }
    
        	
    private $gmtModified;
    
        /**
    * @return 修改时间
    */
        public function getGmtModified() {
        return $this->gmtModified;
    }
    
    /**
     * 设置修改时间     
     * @param Date $gmtModified     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGmtModified( $gmtModified) {
        $this->gmtModified = $gmtModified;
    }
    
        	
    private $id;
    
        /**
    * @return 主键id，也就是子模板id
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置主键id，也就是子模板id     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $isSysTemplate;
    
        /**
    * @return 是否系统模板
    */
        public function getIsSysTemplate() {
        return $this->isSysTemplate;
    }
    
    /**
     * 设置是否系统模板     
     * @param Boolean $isSysTemplate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSysTemplate( $isSysTemplate) {
        $this->isSysTemplate = $isSysTemplate;
    }
    
        	
    private $memberId;
    
        /**
    * @return 会员memberId
    */
        public function getMemberId() {
        return $this->memberId;
    }
    
    /**
     * 设置会员memberId     
     * @param String $memberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMemberId( $memberId) {
        $this->memberId = $memberId;
    }
    
        	
    private $modifier;
    
        /**
    * @return 修改人
    */
        public function getModifier() {
        return $this->modifier;
    }
    
    /**
     * 设置修改人     
     * @param String $modifier     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setModifier( $modifier) {
        $this->modifier = $modifier;
    }
    
        	
    private $serviceChargeType;
    
        /**
    * @return 运费承担类型 卖家承担：0；买家承担：1。
    */
        public function getServiceChargeType() {
        return $this->serviceChargeType;
    }
    
    /**
     * 设置运费承担类型 卖家承担：0；买家承担：1。     
     * @param Integer $serviceChargeType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setServiceChargeType( $serviceChargeType) {
        $this->serviceChargeType = $serviceChargeType;
    }
    
        	
    private $serviceType;
    
        /**
    * @return 服务类型。0:快递 1:货运 2:货到付款
    */
        public function getServiceType() {
        return $this->serviceType;
    }
    
    /**
     * 设置服务类型。0:快递 1:货运 2:货到付款     
     * @param Integer $serviceType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setServiceType( $serviceType) {
        $this->serviceType = $serviceType;
    }
    
        	
    private $templateId;
    
        /**
    * @return 主模板id
    */
        public function getTemplateId() {
        return $this->templateId;
    }
    
    /**
     * 设置主模板id     
     * @param Long $templateId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTemplateId( $templateId) {
        $this->templateId = $templateId;
    }
    
        	
    private $type;
    
        /**
    * @return 子模板类型 0基准 1增值。默认0。
    */
        public function getType() {
        return $this->type;
    }
    
    /**
     * 设置子模板类型 0基准 1增值。默认0。     
     * @param Integer $type     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setType( $type) {
        $this->type = $type;
    }
    
        	
    private $operateType;
    
        /**
    * @return 操作类型：INSERT，UPDATE，DELETE
    */
        public function getOperateType() {
        return $this->operateType;
    }
    
    /**
     * 设置操作类型：INSERT，UPDATE，DELETE     
     * @param String $operateType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOperateType( $operateType) {
        $this->operateType = $operateType;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "chargeType", $this->stdResult )) {
    				$this->chargeType = $this->stdResult->{"chargeType"};
    			}
    			    		    				    			    			if (array_key_exists ( "creator", $this->stdResult )) {
    				$this->creator = $this->stdResult->{"creator"};
    			}
    			    		    				    			    			if (array_key_exists ( "gmtCreate", $this->stdResult )) {
    				$this->gmtCreate = $this->stdResult->{"gmtCreate"};
    			}
    			    		    				    			    			if (array_key_exists ( "gmtModified", $this->stdResult )) {
    				$this->gmtModified = $this->stdResult->{"gmtModified"};
    			}
    			    		    				    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSysTemplate", $this->stdResult )) {
    				$this->isSysTemplate = $this->stdResult->{"isSysTemplate"};
    			}
    			    		    				    			    			if (array_key_exists ( "memberId", $this->stdResult )) {
    				$this->memberId = $this->stdResult->{"memberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "modifier", $this->stdResult )) {
    				$this->modifier = $this->stdResult->{"modifier"};
    			}
    			    		    				    			    			if (array_key_exists ( "serviceChargeType", $this->stdResult )) {
    				$this->serviceChargeType = $this->stdResult->{"serviceChargeType"};
    			}
    			    		    				    			    			if (array_key_exists ( "serviceType", $this->stdResult )) {
    				$this->serviceType = $this->stdResult->{"serviceType"};
    			}
    			    		    				    			    			if (array_key_exists ( "templateId", $this->stdResult )) {
    				$this->templateId = $this->stdResult->{"templateId"};
    			}
    			    		    				    			    			if (array_key_exists ( "type", $this->stdResult )) {
    				$this->type = $this->stdResult->{"type"};
    			}
    			    		    				    			    			if (array_key_exists ( "operateType", $this->stdResult )) {
    				$this->operateType = $this->stdResult->{"operateType"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "chargeType", $this->arrayResult )) {
    			$this->chargeType = $arrayResult['chargeType'];
    			}
    		    	    			    		    			if (array_key_exists ( "creator", $this->arrayResult )) {
    			$this->creator = $arrayResult['creator'];
    			}
    		    	    			    		    			if (array_key_exists ( "gmtCreate", $this->arrayResult )) {
    			$this->gmtCreate = $arrayResult['gmtCreate'];
    			}
    		    	    			    		    			if (array_key_exists ( "gmtModified", $this->arrayResult )) {
    			$this->gmtModified = $arrayResult['gmtModified'];
    			}
    		    	    			    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSysTemplate", $this->arrayResult )) {
    			$this->isSysTemplate = $arrayResult['isSysTemplate'];
    			}
    		    	    			    		    			if (array_key_exists ( "memberId", $this->arrayResult )) {
    			$this->memberId = $arrayResult['memberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "modifier", $this->arrayResult )) {
    			$this->modifier = $arrayResult['modifier'];
    			}
    		    	    			    		    			if (array_key_exists ( "serviceChargeType", $this->arrayResult )) {
    			$this->serviceChargeType = $arrayResult['serviceChargeType'];
    			}
    		    	    			    		    			if (array_key_exists ( "serviceType", $this->arrayResult )) {
    			$this->serviceType = $arrayResult['serviceType'];
    			}
    		    	    			    		    			if (array_key_exists ( "templateId", $this->arrayResult )) {
    			$this->templateId = $arrayResult['templateId'];
    			}
    		    	    			    		    			if (array_key_exists ( "type", $this->arrayResult )) {
    			$this->type = $arrayResult['type'];
    			}
    		    	    			    		    			if (array_key_exists ( "operateType", $this->arrayResult )) {
    			$this->operateType = $arrayResult['operateType'];
    			}
    		    	    		}
 
   
}
?>