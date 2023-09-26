<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaproductProductImageInfo extends SDKDomain {

       	
    private $images;
    
        /**
    * @return 主图列表，需先使用图片上传接口上传图片
    */
        public function getImages() {
        return $this->images;
    }
    
    /**
     * 设置主图列表，需先使用图片上传接口上传图片     
     * @param array include @see String[] $images     
     * 参数示例：<pre>["http://g03.s.alicdn.com/kf/HTB1PYE9IpXXXXbsXVXXq6xXFXXXg/200042360/HTB1PYE9IpXXXXbsXVXXq6xXFXXXg.jpg","http://g01.s.alicdn.com/kf/HTB1tNhsIFXXXXb2XXXXq6xXFXXX9/200042360/HTB1tNhsIFXXXXb2XXXXq6xXFXXX9.jpg"]</pre>     
     * 此参数必填     */
    public function setImages( $images) {
        $this->images = $images;
    }
    
        	
    private $isWatermark;
    
        /**
    * @return 是否打水印，是(true)或否(false)，1688无需关注此字段，1688的水印信息在上传图片时处理
    */
        public function getIsWatermark() {
        return $this->isWatermark;
    }
    
    /**
     * 设置是否打水印，是(true)或否(false)，1688无需关注此字段，1688的水印信息在上传图片时处理     
     * @param Boolean $isWatermark     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsWatermark( $isWatermark) {
        $this->isWatermark = $isWatermark;
    }
    
        	
    private $isWatermarkFrame;
    
        /**
    * @return 水印是否有边框，有边框(true)或者无边框(false)，1688无需关注此字段，1688的水印信息在上传图片时处理
    */
        public function getIsWatermarkFrame() {
        return $this->isWatermarkFrame;
    }
    
    /**
     * 设置水印是否有边框，有边框(true)或者无边框(false)，1688无需关注此字段，1688的水印信息在上传图片时处理     
     * @param Boolean $isWatermarkFrame     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsWatermarkFrame( $isWatermarkFrame) {
        $this->isWatermarkFrame = $isWatermarkFrame;
    }
    
        	
    private $watermarkPosition;
    
        /**
    * @return 水印位置，在中间(center)或者在底部(bottom)，1688无需关注此字段，1688的水印信息在上传图片时处理
    */
        public function getWatermarkPosition() {
        return $this->watermarkPosition;
    }
    
    /**
     * 设置水印位置，在中间(center)或者在底部(bottom)，1688无需关注此字段，1688的水印信息在上传图片时处理     
     * @param String $watermarkPosition     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWatermarkPosition( $watermarkPosition) {
        $this->watermarkPosition = $watermarkPosition;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "images", $this->stdResult )) {
    				$this->images = $this->stdResult->{"images"};
    			}
    			    		    				    			    			if (array_key_exists ( "isWatermark", $this->stdResult )) {
    				$this->isWatermark = $this->stdResult->{"isWatermark"};
    			}
    			    		    				    			    			if (array_key_exists ( "isWatermarkFrame", $this->stdResult )) {
    				$this->isWatermarkFrame = $this->stdResult->{"isWatermarkFrame"};
    			}
    			    		    				    			    			if (array_key_exists ( "watermarkPosition", $this->stdResult )) {
    				$this->watermarkPosition = $this->stdResult->{"watermarkPosition"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "images", $this->arrayResult )) {
    			$this->images = $arrayResult['images'];
    			}
    		    	    			    		    			if (array_key_exists ( "isWatermark", $this->arrayResult )) {
    			$this->isWatermark = $arrayResult['isWatermark'];
    			}
    		    	    			    		    			if (array_key_exists ( "isWatermarkFrame", $this->arrayResult )) {
    			$this->isWatermarkFrame = $arrayResult['isWatermarkFrame'];
    			}
    		    	    			    		    			if (array_key_exists ( "watermarkPosition", $this->arrayResult )) {
    			$this->watermarkPosition = $arrayResult['watermarkPosition'];
    			}
    		    	    		}
 
   
}
?>