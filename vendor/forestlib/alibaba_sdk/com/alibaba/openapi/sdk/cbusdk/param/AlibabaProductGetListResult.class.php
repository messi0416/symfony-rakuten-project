<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaproductProductInfo.class.php');

class AlibabaProductGetListResult {

        	
    private $productInfos;
    
        /**
    * @return 商品列表
    */
        public function getProductInfos() {
        return $this->productInfos;
    }
    
    /**
     * 设置商品列表     
     * @param array include @see AlibabaproductProductInfo[] $productInfos     
          
     * 此参数必填     */
    public function setProductInfos(AlibabaproductProductInfo $productInfos) {
        $this->productInfos = $productInfos;
    }
    
        	
    private $count;
    
        /**
    * @return 总条数
    */
        public function getCount() {
        return $this->count;
    }
    
    /**
     * 设置总条数     
     * @param Integer $count     
          
     * 此参数必填     */
    public function setCount( $count) {
        $this->count = $count;
    }
    
        	
    private $currentPage;
    
        /**
    * @return 当前页
    */
        public function getCurrentPage() {
        return $this->currentPage;
    }
    
    /**
     * 设置当前页     
     * @param Integer $currentPage     
          
     * 此参数必填     */
    public function setCurrentPage( $currentPage) {
        $this->currentPage = $currentPage;
    }
    
        	
    private $pageSize;
    
        /**
    * @return 每页大小
    */
        public function getPageSize() {
        return $this->pageSize;
    }
    
    /**
     * 设置每页大小     
     * @param Integer $pageSize     
          
     * 此参数必填     */
    public function setPageSize( $pageSize) {
        $this->pageSize = $pageSize;
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
					    			    			if (array_key_exists ( "productInfos", $this->stdResult )) {
    			$productInfosResult=$this->stdResult->{"productInfos"};
    				$object = json_decode ( json_encode ( $productInfosResult ), true );
					$this->productInfos = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaproductProductInfoResult=new AlibabaproductProductInfo();
						$AlibabaproductProductInfoResult->setArrayResult($arrayobject );
						$this->productInfos [$i] = $AlibabaproductProductInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "count", $this->stdResult )) {
    				$this->count = $this->stdResult->{"count"};
    			}
    			    		    				    			    			if (array_key_exists ( "currentPage", $this->stdResult )) {
    				$this->currentPage = $this->stdResult->{"currentPage"};
    			}
    			    		    				    			    			if (array_key_exists ( "pageSize", $this->stdResult )) {
    				$this->pageSize = $this->stdResult->{"pageSize"};
    			}
    			    		    				    			    			if (array_key_exists ( "errorMsg", $this->stdResult )) {
    				$this->errorMsg = $this->stdResult->{"errorMsg"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "productInfos", $this->arrayResult )) {
    		$productInfosResult=$arrayResult['productInfos'];
    			$this->productInfos = new AlibabaproductProductInfo();
    			$this->productInfos->setStdResult ( $productInfosResult);
    		}
    		    	    			    		    			if (array_key_exists ( "count", $this->arrayResult )) {
    			$this->count = $arrayResult['count'];
    			}
    		    	    			    		    			if (array_key_exists ( "currentPage", $this->arrayResult )) {
    			$this->currentPage = $arrayResult['currentPage'];
    			}
    		    	    			    		    			if (array_key_exists ( "pageSize", $this->arrayResult )) {
    			$this->pageSize = $arrayResult['pageSize'];
    			}
    		    	    			    		    			if (array_key_exists ( "errorMsg", $this->arrayResult )) {
    			$this->errorMsg = $arrayResult['errorMsg'];
    			}
    		    	    		}

}
?>