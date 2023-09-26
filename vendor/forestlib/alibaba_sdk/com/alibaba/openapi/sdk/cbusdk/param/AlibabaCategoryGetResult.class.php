<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabacategoryCategoryInfo.class.php');

class AlibabaCategoryGetResult {

        	
    private $categoryInfo;
    
        /**
    * @return 类目列表
    */
        public function getCategoryInfo() {
        return $this->categoryInfo;
    }
    
    /**
     * 设置类目列表     
     * @param array include @see AlibabacategoryCategoryInfo[] $categoryInfo     
          
     * 此参数必填     */
    public function setCategoryInfo(AlibabacategoryCategoryInfo $categoryInfo) {
        $this->categoryInfo = $categoryInfo;
    }
    
        	
    private $errorMsg;
    
        /**
    * @return 错误信息
    */
        public function getErrorMsg() {
        return $this->errorMsg;
    }
    
    /**
     * 设置错误信息     
     * @param String $errorMsg     
          
     * 此参数必填     */
    public function setErrorMsg( $errorMsg) {
        $this->errorMsg = $errorMsg;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "categoryInfo", $this->stdResult )) {
    			$categoryInfoResult=$this->stdResult->{"categoryInfo"};
    				$object = json_decode ( json_encode ( $categoryInfoResult ), true );
					$this->categoryInfo = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabacategoryCategoryInfoResult=new AlibabacategoryCategoryInfo();
						$AlibabacategoryCategoryInfoResult->setArrayResult($arrayobject );
						$this->categoryInfo [$i] = $AlibabacategoryCategoryInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "errorMsg", $this->stdResult )) {
    				$this->errorMsg = $this->stdResult->{"errorMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "categoryInfo", $this->arrayResult )) {
    		$categoryInfoResult=$arrayResult['categoryInfo'];
    			$this->categoryInfo = new AlibabacategoryCategoryInfo();
    			$this->categoryInfo->setStdResult ( $categoryInfoResult);
    		}
    		    	    			    		    			if (array_key_exists ( "errorMsg", $this->arrayResult )) {
    			$this->errorMsg = $arrayResult['errorMsg'];
    			}
    		    	    		}

}
?>