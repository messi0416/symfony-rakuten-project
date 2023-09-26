<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class Alibabaassuranceflag extends SDKDomain {

       	
    private $pauseStatus;
    
        /**
    * @return 信保灭灯状态，true为灭灯，false为非灭灯
    */
        public function getPauseStatus() {
        return $this->pauseStatus;
    }
    
    /**
     * 设置信保灭灯状态，true为灭灯，false为非灭灯     
     * @param Boolean $pauseStatus     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPauseStatus( $pauseStatus) {
        $this->pauseStatus = $pauseStatus;
    }
    
        	
    private $flagList;
    
        /**
    * @return 若信保灭灯状态为false，返回四种flag html文本
    */
        public function getFlagList() {
        return $this->flagList;
    }
    
    /**
     * 设置若信保灭灯状态为false，返回四种flag html文本     
     * @param array include @see String[] $flagList     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFlagList( $flagList) {
        $this->flagList = $flagList;
    }
    
        	
    private $guideURL;
    
        /**
    * @return 若信保灭灯状态为true，返回引导url
    */
        public function getGuideURL() {
        return $this->guideURL;
    }
    
    /**
     * 设置若信保灭灯状态为true，返回引导url     
     * @param String $guideURL     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGuideURL( $guideURL) {
        $this->guideURL = $guideURL;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "pauseStatus", $this->stdResult )) {
    				$this->pauseStatus = $this->stdResult->{"pauseStatus"};
    			}
    			    		    				    			    			if (array_key_exists ( "flagList", $this->stdResult )) {
    				$this->flagList = $this->stdResult->{"flagList"};
    			}
    			    		    				    			    			if (array_key_exists ( "guideURL", $this->stdResult )) {
    				$this->guideURL = $this->stdResult->{"guideURL"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "pauseStatus", $this->arrayResult )) {
    			$this->pauseStatus = $arrayResult['pauseStatus'];
    			}
    		    	    			    		    			if (array_key_exists ( "flagList", $this->arrayResult )) {
    			$this->flagList = $arrayResult['flagList'];
    			}
    		    	    			    		    			if (array_key_exists ( "guideURL", $this->arrayResult )) {
    			$this->guideURL = $arrayResult['guideURL'];
    			}
    		    	    		}
 
   
}
?>