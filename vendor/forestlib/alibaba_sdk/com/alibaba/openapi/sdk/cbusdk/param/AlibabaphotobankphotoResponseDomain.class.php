<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaphotobankphotoResponseDomain extends SDKDomain {

       	
    private $id;
    
        /**
    * @return 图片ID
    */
        public function getId() {
        return $this->id;
    }
    
    /**
     * 设置图片ID     
     * @param Long $id     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setId( $id) {
        $this->id = $id;
    }
    
        	
    private $albumID;
    
        /**
    * @return 相册ID
    */
        public function getAlbumID() {
        return $this->albumID;
    }
    
    /**
     * 设置相册ID     
     * @param Long $albumID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAlbumID( $albumID) {
        $this->albumID = $albumID;
    }
    
        	
    private $name;
    
        /**
    * @return 图片名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置图片名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $url;
    
        /**
    * @return 图片url
    */
        public function getUrl() {
        return $this->url;
    }
    
    /**
     * 设置图片url     
     * @param String $url     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUrl( $url) {
        $this->url = $url;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "id", $this->stdResult )) {
    				$this->id = $this->stdResult->{"id"};
    			}
    			    		    				    			    			if (array_key_exists ( "albumID", $this->stdResult )) {
    				$this->albumID = $this->stdResult->{"albumID"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "url", $this->stdResult )) {
    				$this->url = $this->stdResult->{"url"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "id", $this->arrayResult )) {
    			$this->id = $arrayResult['id'];
    			}
    		    	    			    		    			if (array_key_exists ( "albumID", $this->arrayResult )) {
    			$this->albumID = $arrayResult['albumID'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "url", $this->arrayResult )) {
    			$this->url = $arrayResult['url'];
    			}
    		    	    		}
 
   
}
?>