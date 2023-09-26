<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabalogisticsFreightTemplate extends SDKDomain {

       	
    private $addressCodeText;
    
        /**
    * @return 地址信息
    */
        public function getAddressCodeText() {
        return $this->addressCodeText;
    }
    
    /**
     * 设置地址信息     
     * @param String $addressCodeText     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAddressCodeText( $addressCodeText) {
        $this->addressCodeText = $addressCodeText;
    }
    
        	
    private $fromAreaCode;
    
        /**
    * @return 发货地址地区码
    */
        public function getFromAreaCode() {
        return $this->fromAreaCode;
    }
    
    /**
     * 设置发货地址地区码     
     * @param String $fromAreaCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFromAreaCode( $fromAreaCode) {
        $this->fromAreaCode = $fromAreaCode;
    }
    
        	
    private $id;
    
        /**
    * @return 地址ID
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置地址ID     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $memberId;
    
        /**
    * @return 会员ID
    */
        public function getMemberId() {
        return $this->memberId;
    }
    
    /**
     * 设置会员ID     
     * @param String $memberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMemberId( $memberId) {
        $this->memberId = $memberId;
    }
    
        	
    private $name;
    
        /**
    * @return 名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $remark;
    
        /**
    * @return 备注
    */
        public function getRemark() {
        return $this->remark;
    }
    
    /**
     * 设置备注     
     * @param String $remark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRemark( $remark) {
        $this->remark = $remark;
    }
    
        	
    private $status;
    
        /**
    * @return 状态
    */
        public function getStatus() {
        return $this->status;
    }
    
    /**
     * 设置状态     
     * @param Integer $status     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStatus( $status) {
        $this->status = $status;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "addressCodeText", $this->stdResult )) {
    				$this->addressCodeText = $this->stdResult->{"addressCodeText"};
    			}
    			    		    				    			    			if (array_key_exists ( "fromAreaCode", $this->stdResult )) {
    				$this->fromAreaCode = $this->stdResult->{"fromAreaCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "memberId", $this->stdResult )) {
    				$this->memberId = $this->stdResult->{"memberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "remark", $this->stdResult )) {
    				$this->remark = $this->stdResult->{"remark"};
    			}
    			    		    				    			    			if (array_key_exists ( "status", $this->stdResult )) {
    				$this->status = $this->stdResult->{"status"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "addressCodeText", $this->arrayResult )) {
    			$this->addressCodeText = $arrayResult['addressCodeText'];
    			}
    		    	    			    		    			if (array_key_exists ( "fromAreaCode", $this->arrayResult )) {
    			$this->fromAreaCode = $arrayResult['fromAreaCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "memberId", $this->arrayResult )) {
    			$this->memberId = $arrayResult['memberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "remark", $this->arrayResult )) {
    			$this->remark = $arrayResult['remark'];
    			}
    		    	    			    		    			if (array_key_exists ( "status", $this->arrayResult )) {
    			$this->status = $arrayResult['status'];
    			}
    		    	    		}
 
   
}
?>