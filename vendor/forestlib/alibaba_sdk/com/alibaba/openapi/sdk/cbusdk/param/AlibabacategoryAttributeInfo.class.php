<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabacategoryAttributeValueInfo.class.php');

class AlibabacategoryAttributeInfo extends SDKDomain {

       	
    private $attrID;
    
        /**
    * @return 属性id
    */
        public function getAttrID() {
        return $this->attrID;
    }
    
    /**
     * 设置属性id     
     * @param Long $attrID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttrID( $attrID) {
        $this->attrID = $attrID;
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
    
        	
    private $required;
    
        /**
    * @return 是否必填属性
    */
        public function getRequired() {
        return $this->required;
    }
    
    /**
     * 设置是否必填属性     
     * @param Boolean $required     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRequired( $required) {
        $this->required = $required;
    }
    
        	
    private $units;
    
        /**
    * @return 该属性的单位
    */
        public function getUnits() {
        return $this->units;
    }
    
    /**
     * 设置该属性的单位     
     * @param array include @see String[] $units     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnits( $units) {
        $this->units = $units;
    }
    
        	
    private $isSKUAttribute;
    
        /**
    * @return 该属性能否当成SKU属性
    */
        public function getIsSKUAttribute() {
        return $this->isSKUAttribute;
    }
    
    /**
     * 设置该属性能否当成SKU属性     
     * @param Boolean $isSKUAttribute     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSKUAttribute( $isSKUAttribute) {
        $this->isSKUAttribute = $isSKUAttribute;
    }
    
        	
    private $attrValues;
    
        /**
    * @return 属性可选的属性值
    */
        public function getAttrValues() {
        return $this->attrValues;
    }
    
    /**
     * 设置属性可选的属性值     
     * @param array include @see AlibabacategoryAttributeValueInfo[] $attrValues     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttrValues(AlibabacategoryAttributeValueInfo $attrValues) {
        $this->attrValues = $attrValues;
    }
    
        	
    private $inputType;
    
        /**
    * @return 输入类型
    */
        public function getInputType() {
        return $this->inputType;
    }
    
    /**
     * 设置输入类型     
     * @param String $inputType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setInputType( $inputType) {
        $this->inputType = $inputType;
    }
    
        	
    private $isSupportCustomizeValue;
    
        /**
    * @return 用成SKU属性时，是否支持自定义属性值名称，1688不返回该信息
    */
        public function getIsSupportCustomizeValue() {
        return $this->isSupportCustomizeValue;
    }
    
    /**
     * 设置用成SKU属性时，是否支持自定义属性值名称，1688不返回该信息     
     * @param Boolean $isSupportCustomizeValue     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSupportCustomizeValue( $isSupportCustomizeValue) {
        $this->isSupportCustomizeValue = $isSupportCustomizeValue;
    }
    
        	
    private $isSupportCustomizeImage;
    
        /**
    * @return 用成SKU属性时，是否支持自定义图片展示，1688不返回该信息
    */
        public function getIsSupportCustomizeImage() {
        return $this->isSupportCustomizeImage;
    }
    
    /**
     * 设置用成SKU属性时，是否支持自定义图片展示，1688不返回该信息     
     * @param Boolean $isSupportCustomizeImage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsSupportCustomizeImage( $isSupportCustomizeImage) {
        $this->isSupportCustomizeImage = $isSupportCustomizeImage;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "attrID", $this->stdResult )) {
    				$this->attrID = $this->stdResult->{"attrID"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "required", $this->stdResult )) {
    				$this->required = $this->stdResult->{"required"};
    			}
    			    		    				    			    			if (array_key_exists ( "units", $this->stdResult )) {
    				$this->units = $this->stdResult->{"units"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSKUAttribute", $this->stdResult )) {
    				$this->isSKUAttribute = $this->stdResult->{"isSKUAttribute"};
    			}
    			    		    				    			    			if (array_key_exists ( "attrValues", $this->stdResult )) {
    			$attrValuesResult=$this->stdResult->{"attrValues"};
    				$object = json_decode ( json_encode ( $attrValuesResult ), true );
					$this->attrValues = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabacategoryAttributeValueInfoResult=new AlibabacategoryAttributeValueInfo();
						$AlibabacategoryAttributeValueInfoResult->setArrayResult($arrayobject );
						$this->attrValues [$i] = $AlibabacategoryAttributeValueInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "inputType", $this->stdResult )) {
    				$this->inputType = $this->stdResult->{"inputType"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSupportCustomizeValue", $this->stdResult )) {
    				$this->isSupportCustomizeValue = $this->stdResult->{"isSupportCustomizeValue"};
    			}
    			    		    				    			    			if (array_key_exists ( "isSupportCustomizeImage", $this->stdResult )) {
    				$this->isSupportCustomizeImage = $this->stdResult->{"isSupportCustomizeImage"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "attrID", $this->arrayResult )) {
    			$this->attrID = $arrayResult['attrID'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "required", $this->arrayResult )) {
    			$this->required = $arrayResult['required'];
    			}
    		    	    			    		    			if (array_key_exists ( "units", $this->arrayResult )) {
    			$this->units = $arrayResult['units'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSKUAttribute", $this->arrayResult )) {
    			$this->isSKUAttribute = $arrayResult['isSKUAttribute'];
    			}
    		    	    			    		    		if (array_key_exists ( "attrValues", $this->arrayResult )) {
    		$attrValuesResult=$arrayResult['attrValues'];
    			$this->attrValues = AlibabacategoryAttributeValueInfo();
    			$this->attrValues->$this->setStdResult ( $attrValuesResult);
    		}
    		    	    			    		    			if (array_key_exists ( "inputType", $this->arrayResult )) {
    			$this->inputType = $arrayResult['inputType'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSupportCustomizeValue", $this->arrayResult )) {
    			$this->isSupportCustomizeValue = $arrayResult['isSupportCustomizeValue'];
    			}
    		    	    			    		    			if (array_key_exists ( "isSupportCustomizeImage", $this->arrayResult )) {
    			$this->isSupportCustomizeImage = $arrayResult['isSupportCustomizeImage'];
    			}
    		    	    		}
 
   
}
?>