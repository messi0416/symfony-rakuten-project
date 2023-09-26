<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaphotobankphotoAlbumDomain extends SDKDomain {

       	
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
    * @return 相册名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置相册名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $description;
    
        /**
    * @return 相册描述，国际站无此信息
    */
        public function getDescription() {
        return $this->description;
    }
    
    /**
     * 设置相册描述，国际站无此信息     
     * @param String $description     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDescription( $description) {
        $this->description = $description;
    }
    
        	
    private $authority;
    
        /**
    * @return 相册访问权限。取值范围:0-不公开；1-公开；2-密码访问。只有开通旺铺的会员可以设置相册访问权限为“1-公开”和“2-密码访问”，未开通旺铺的会员相册访问权限限制为“0-不公开”。国际站无此信息
    */
        public function getAuthority() {
        return $this->authority;
    }
    
    /**
     * 设置相册访问权限。取值范围:0-不公开；1-公开；2-密码访问。只有开通旺铺的会员可以设置相册访问权限为“1-公开”和“2-密码访问”，未开通旺铺的会员相册访问权限限制为“0-不公开”。国际站无此信息     
     * @param Integer $authority     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAuthority( $authority) {
        $this->authority = $authority;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "albumID", $this->stdResult )) {
    				$this->albumID = $this->stdResult->{"albumID"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "description", $this->stdResult )) {
    				$this->description = $this->stdResult->{"description"};
    			}
    			    		    				    			    			if (array_key_exists ( "authority", $this->stdResult )) {
    				$this->authority = $this->stdResult->{"authority"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "albumID", $this->arrayResult )) {
    			$this->albumID = $arrayResult['albumID'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "description", $this->arrayResult )) {
    			$this->description = $arrayResult['description'];
    			}
    		    	    			    		    			if (array_key_exists ( "authority", $this->arrayResult )) {
    			$this->authority = $arrayResult['authority'];
    			}
    		    	    		}
 
   
}
?>