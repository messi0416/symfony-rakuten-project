<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabatradeCargoImage extends SDKDomain {

       	
    private $imageURI;
    
        /**
    * @return 
    */
        public function getImageURI() {
        return $this->imageURI;
    }
    
    /**
     * 设置     
     * @param String $imageURI     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setImageURI( $imageURI) {
        $this->imageURI = $imageURI;
    }
    
        	
    private $searchImageURI;
    
        /**
    * @return 
    */
        public function getSearchImageURI() {
        return $this->searchImageURI;
    }
    
    /**
     * 设置     
     * @param String $searchImageURI     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSearchImageURI( $searchImageURI) {
        $this->searchImageURI = $searchImageURI;
    }
    
        	
    private $size310x310ImageURI;
    
        /**
    * @return 
    */
        public function getSize310x310ImageURI() {
        return $this->size310x310ImageURI;
    }
    
    /**
     * 设置     
     * @param String $size310x310ImageURI     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSize310x310ImageURI( $size310x310ImageURI) {
        $this->size310x310ImageURI = $size310x310ImageURI;
    }
    
        	
    private $summImageURI;
    
        /**
    * @return 
    */
        public function getSummImageURI() {
        return $this->summImageURI;
    }
    
    /**
     * 设置     
     * @param String $summImageURI     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSummImageURI( $summImageURI) {
        $this->summImageURI = $summImageURI;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "imageURI", $this->stdResult )) {
    				$this->imageURI = $this->stdResult->{"imageURI"};
    			}
    			    		    				    			    			if (array_key_exists ( "searchImageURI", $this->stdResult )) {
    				$this->searchImageURI = $this->stdResult->{"searchImageURI"};
    			}
    			    		    				    			    			if (array_key_exists ( "size310x310ImageURI", $this->stdResult )) {
    				$this->size310x310ImageURI = $this->stdResult->{"size310x310ImageURI"};
    			}
    			    		    				    			    			if (array_key_exists ( "summImageURI", $this->stdResult )) {
    				$this->summImageURI = $this->stdResult->{"summImageURI"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "imageURI", $this->arrayResult )) {
    			$this->imageURI = $arrayResult['imageURI'];
    			}
    		    	    			    		    			if (array_key_exists ( "searchImageURI", $this->arrayResult )) {
    			$this->searchImageURI = $arrayResult['searchImageURI'];
    			}
    		    	    			    		    			if (array_key_exists ( "size310x310ImageURI", $this->arrayResult )) {
    			$this->size310x310ImageURI = $arrayResult['size310x310ImageURI'];
    			}
    		    	    			    		    			if (array_key_exists ( "summImageURI", $this->arrayResult )) {
    			$this->summImageURI = $arrayResult['summImageURI'];
    			}
    		    	    		}
 
   
}
?>