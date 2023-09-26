<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductProductModifiable extends SDKDomain {

       	
    private $productId;
    
        /**
    * @return 商品ID
    */
        public function getProductId() {
        return $this->productId;
    }
    
    /**
     * 设置商品ID     
     * @param Long $productId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductId( $productId) {
        $this->productId = $productId;
    }
    
        	
    private $modifiable;
    
        /**
    * @return 是否可以修改
    */
        public function getModifiable() {
        return $this->modifiable;
    }
    
    /**
     * 设置是否可以修改     
     * @param Boolean $modifiable     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setModifiable( $modifiable) {
        $this->modifiable = $modifiable;
    }
    
        	
    private $desc;
    
        /**
    * @return 如果不能修改，描述信息
    */
        public function getDesc() {
        return $this->desc;
    }
    
    /**
     * 设置如果不能修改，描述信息     
     * @param String $desc     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDesc( $desc) {
        $this->desc = $desc;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "productId", $this->stdResult )) {
    				$this->productId = $this->stdResult->{"productId"};
    			}
    			    		    				    			    			if (array_key_exists ( "modifiable", $this->stdResult )) {
    				$this->modifiable = $this->stdResult->{"modifiable"};
    			}
    			    		    				    			    			if (array_key_exists ( "desc", $this->stdResult )) {
    				$this->desc = $this->stdResult->{"desc"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "productId", $this->arrayResult )) {
    			$this->productId = $arrayResult['productId'];
    			}
    		    	    			    		    			if (array_key_exists ( "modifiable", $this->arrayResult )) {
    			$this->modifiable = $arrayResult['modifiable'];
    			}
    		    	    			    		    			if (array_key_exists ( "desc", $this->arrayResult )) {
    			$this->desc = $arrayResult['desc'];
    			}
    		    	    		}
 
   
}
?>