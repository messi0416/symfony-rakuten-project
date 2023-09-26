<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');

class AlibabaopenplatformtradeQuotationIdParam extends SDKDomain {

       	
    private $buyerMemberId;
    
        /**
    * @return 采购商 memberId（必填）
    */
        public function getBuyerMemberId() {
        return $this->buyerMemberId;
    }
    
    /**
     * 设置采购商 memberId（必填）     
     * @param String $buyerMemberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerMemberId( $buyerMemberId) {
        $this->buyerMemberId = $buyerMemberId;
    }
    
        	
    private $purchaseNoteId;
    
        /**
    * @return 报价项Id（选填）
    */
        public function getPurchaseNoteId() {
        return $this->purchaseNoteId;
    }
    
    /**
     * 设置报价项Id（选填）     
     * @param Long $purchaseNoteId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPurchaseNoteId( $purchaseNoteId) {
        $this->purchaseNoteId = $purchaseNoteId;
    }
    
        	
    private $quoteItemIds;
    
        /**
    * @return 询价单/招标单ID（选填）
    */
        public function getQuoteItemIds() {
        return $this->quoteItemIds;
    }
    
    /**
     * 设置询价单/招标单ID（选填）     
     * @param array include @see Long[] $quoteItemIds     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuoteItemIds( $quoteItemIds) {
        $this->quoteItemIds = $quoteItemIds;
    }
    
        	
    private $supplyNoteId;
    
        /**
    * @return 报价单/投标单ID（必填）
    */
        public function getSupplyNoteId() {
        return $this->supplyNoteId;
    }
    
    /**
     * 设置报价单/投标单ID（必填）     
     * @param String $supplyNoteId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplyNoteId( $supplyNoteId) {
        $this->supplyNoteId = $supplyNoteId;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "buyerMemberId", $this->stdResult )) {
    				$this->buyerMemberId = $this->stdResult->{"buyerMemberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "purchaseNoteId", $this->stdResult )) {
    				$this->purchaseNoteId = $this->stdResult->{"purchaseNoteId"};
    			}
    			    		    				    			    			if (array_key_exists ( "quoteItemIds", $this->stdResult )) {
    				$this->quoteItemIds = $this->stdResult->{"quoteItemIds"};
    			}
    			    		    				    			    			if (array_key_exists ( "supplyNoteId", $this->stdResult )) {
    				$this->supplyNoteId = $this->stdResult->{"supplyNoteId"};
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "buyerMemberId", $this->arrayResult )) {
    			$this->buyerMemberId = $arrayResult['buyerMemberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "purchaseNoteId", $this->arrayResult )) {
    			$this->purchaseNoteId = $arrayResult['purchaseNoteId'];
    			}
    		    	    			    		    			if (array_key_exists ( "quoteItemIds", $this->arrayResult )) {
    			$this->quoteItemIds = $arrayResult['quoteItemIds'];
    			}
    		    	    			    		    			if (array_key_exists ( "supplyNoteId", $this->arrayResult )) {
    			$this->supplyNoteId = $arrayResult['supplyNoteId'];
    			}
    		    	    		}
 
   
}
?>