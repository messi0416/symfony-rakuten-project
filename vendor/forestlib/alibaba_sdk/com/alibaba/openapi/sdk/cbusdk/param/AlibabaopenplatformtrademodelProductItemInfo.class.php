<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtrademodelGuaranteeTermsInfo.class.php');

class AlibabaopenplatformtrademodelProductItemInfo extends SDKDomain {

       	
    private $cargoNumber;
    
        /**
    * @return 指定规格的货号，国际站无需关注。该字段不一定有值，仅仅在企业采购下单时才会把货号记录。别的订单类型的货号只能通过商品接口去获取。请注意：通过商品接口获取时的货号和下单时的货号可能不一致，因为下单完成后卖家可能修改商品信息，改变了货号。
    */
        public function getCargoNumber() {
        return $this->cargoNumber;
    }
    
    /**
     * 设置指定规格的货号，国际站无需关注。该字段不一定有值，仅仅在企业采购下单时才会把货号记录。别的订单类型的货号只能通过商品接口去获取。请注意：通过商品接口获取时的货号和下单时的货号可能不一致，因为下单完成后卖家可能修改商品信息，改变了货号。     
     * @param String $cargoNumber     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCargoNumber( $cargoNumber) {
        $this->cargoNumber = $cargoNumber;
    }
    
        	
    private $description;
    
        /**
    * @return 描述,1688无此信息
    */
        public function getDescription() {
        return $this->description;
    }
    
    /**
     * 设置描述,1688无此信息     
     * @param String $description     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDescription( $description) {
        $this->description = $description;
    }
    
        	
    private $itemAmount;
    
        /**
    * @return 实付金额，单位为元
    */
        public function getItemAmount() {
        return $this->itemAmount;
    }
    
    /**
     * 设置实付金额，单位为元     
     * @param BigDecimal $itemAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setItemAmount( $itemAmount) {
        $this->itemAmount = $itemAmount;
    }
    
        	
    private $name;
    
        /**
    * @return 商品名称
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置商品名称     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $price;
    
        /**
    * @return 原始单价，以元为单位
    */
        public function getPrice() {
        return $this->price;
    }
    
    /**
     * 设置原始单价，以元为单位     
     * @param BigDecimal $price     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPrice( $price) {
        $this->price = $price;
    }
    
        	
    private $productID;
    
        /**
    * @return 产品ID（非在线产品为空）
    */
        public function getProductID() {
        return $this->productID;
    }
    
    /**
     * 设置产品ID（非在线产品为空）     
     * @param Long $productID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductID( $productID) {
        $this->productID = $productID;
    }
    
        	
    private $productImgUrl;
    
        /**
    * @return 商品图片url
    */
        public function getProductImgUrl() {
        return $this->productImgUrl;
    }
    
    /**
     * 设置商品图片url     
     * @param array include @see String[] $productImgUrl     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductImgUrl( $productImgUrl) {
        $this->productImgUrl = $productImgUrl;
    }
    
        	
    private $productSnapshotUrl;
    
        /**
    * @return 产品快照url，交易订单产生时会自动记录下当时的商品快照，供后续纠纷时参考
    */
        public function getProductSnapshotUrl() {
        return $this->productSnapshotUrl;
    }
    
    /**
     * 设置产品快照url，交易订单产生时会自动记录下当时的商品快照，供后续纠纷时参考     
     * @param String $productSnapshotUrl     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductSnapshotUrl( $productSnapshotUrl) {
        $this->productSnapshotUrl = $productSnapshotUrl;
    }
    
        	
    private $quantity;
    
        /**
    * @return 以unit为单位的数量，例如多少个、多少件、多少箱
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置以unit为单位的数量，例如多少个、多少件、多少箱     
     * @param BigDecimal $quantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
        	
    private $refund;
    
        /**
    * @return 退款金额，单位为元
    */
        public function getRefund() {
        return $this->refund;
    }
    
    /**
     * 设置退款金额，单位为元     
     * @param BigDecimal $refund     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setRefund( $refund) {
        $this->refund = $refund;
    }
    
        	
    private $skuID;
    
        /**
    * @return skuID
    */
        public function getSkuID() {
        return $this->skuID;
    }
    
    /**
     * 设置skuID     
     * @param Long $skuID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuID( $skuID) {
        $this->skuID = $skuID;
    }
    
        	
    private $sort;
    
        /**
    * @return 排序字段，商品列表按此字段进行排序，从0开始，1688不提供
    */
        public function getSort() {
        return $this->sort;
    }
    
    /**
     * 设置排序字段，商品列表按此字段进行排序，从0开始，1688不提供     
     * @param Integer $sort     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSort( $sort) {
        $this->sort = $sort;
    }
    
        	
    private $status;
    
        /**
    * @return 子订单状态
    */
        public function getStatus() {
        return $this->status;
    }
    
    /**
     * 设置子订单状态     
     * @param String $status     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStatus( $status) {
        $this->status = $status;
    }
    
        	
    private $subItemID;
    
        /**
    * @return 商品明细条目ID
    */
        public function getSubItemID() {
        return $this->subItemID;
    }
    
    /**
     * 设置商品明细条目ID     
     * @param Long $subItemID     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSubItemID( $subItemID) {
        $this->subItemID = $subItemID;
    }
    
        	
    private $type;
    
        /**
    * @return 类型，国际站使用，供卖家标注商品所属类型
    */
        public function getType() {
        return $this->type;
    }
    
    /**
     * 设置类型，国际站使用，供卖家标注商品所属类型     
     * @param String $type     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setType( $type) {
        $this->type = $type;
    }
    
        	
    private $unit;
    
        /**
    * @return 售卖单位	例如：个、件、箱
    */
        public function getUnit() {
        return $this->unit;
    }
    
    /**
     * 设置售卖单位	例如：个、件、箱     
     * @param String $unit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnit( $unit) {
        $this->unit = $unit;
    }
    
        	
    private $weight;
    
        /**
    * @return 重量	按重量单位计算的重量，例如：100
    */
        public function getWeight() {
        return $this->weight;
    }
    
    /**
     * 设置重量	按重量单位计算的重量，例如：100     
     * @param String $weight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWeight( $weight) {
        $this->weight = $weight;
    }
    
        	
    private $weightUnit;
    
        /**
    * @return 重量单位	例如：g，kg，t
    */
        public function getWeightUnit() {
        return $this->weightUnit;
    }
    
    /**
     * 设置重量单位	例如：g，kg，t     
     * @param String $weightUnit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWeightUnit( $weightUnit) {
        $this->weightUnit = $weightUnit;
    }
    
        	
    private $guaranteesTerms;
    
        /**
    * @return 保障条款，此字段仅针对1688
    */
        public function getGuaranteesTerms() {
        return $this->guaranteesTerms;
    }
    
    /**
     * 设置保障条款，此字段仅针对1688     
     * @param array include @see AlibabaopenplatformtrademodelGuaranteeTermsInfo[] $guaranteesTerms     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGuaranteesTerms(AlibabaopenplatformtrademodelGuaranteeTermsInfo $guaranteesTerms) {
        $this->guaranteesTerms = $guaranteesTerms;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "cargoNumber", $this->stdResult )) {
    				$this->cargoNumber = $this->stdResult->{"cargoNumber"};
    			}
    			    		    				    			    			if (array_key_exists ( "description", $this->stdResult )) {
    				$this->description = $this->stdResult->{"description"};
    			}
    			    		    				    			    			if (array_key_exists ( "itemAmount", $this->stdResult )) {
    				$this->itemAmount = $this->stdResult->{"itemAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "price", $this->stdResult )) {
    				$this->price = $this->stdResult->{"price"};
    			}
    			    		    				    			    			if (array_key_exists ( "productID", $this->stdResult )) {
    				$this->productID = $this->stdResult->{"productID"};
    			}
    			    		    				    			    			if (array_key_exists ( "productImgUrl", $this->stdResult )) {
    				$this->productImgUrl = $this->stdResult->{"productImgUrl"};
    			}
    			    		    				    			    			if (array_key_exists ( "productSnapshotUrl", $this->stdResult )) {
    				$this->productSnapshotUrl = $this->stdResult->{"productSnapshotUrl"};
    			}
    			    		    				    			    			if (array_key_exists ( "quantity", $this->stdResult )) {
    				$this->quantity = $this->stdResult->{"quantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "refund", $this->stdResult )) {
    				$this->refund = $this->stdResult->{"refund"};
    			}
    			    		    				    			    			if (array_key_exists ( "skuID", $this->stdResult )) {
    				$this->skuID = $this->stdResult->{"skuID"};
    			}
    			    		    				    			    			if (array_key_exists ( "sort", $this->stdResult )) {
    				$this->sort = $this->stdResult->{"sort"};
    			}
    			    		    				    			    			if (array_key_exists ( "status", $this->stdResult )) {
    				$this->status = $this->stdResult->{"status"};
    			}
    			    		    				    			    			if (array_key_exists ( "subItemID", $this->stdResult )) {
    				$this->subItemID = $this->stdResult->{"subItemID"};
    			}
    			    		    				    			    			if (array_key_exists ( "type", $this->stdResult )) {
    				$this->type = $this->stdResult->{"type"};
    			}
    			    		    				    			    			if (array_key_exists ( "unit", $this->stdResult )) {
    				$this->unit = $this->stdResult->{"unit"};
    			}
    			    		    				    			    			if (array_key_exists ( "weight", $this->stdResult )) {
    				$this->weight = $this->stdResult->{"weight"};
    			}
    			    		    				    			    			if (array_key_exists ( "weightUnit", $this->stdResult )) {
    				$this->weightUnit = $this->stdResult->{"weightUnit"};
    			}
    			    		    				    			    			if (array_key_exists ( "guaranteesTerms", $this->stdResult )) {
    			$guaranteesTermsResult=$this->stdResult->{"guaranteesTerms"};
    				$object = json_decode ( json_encode ( $guaranteesTermsResult ), true );
					$this->guaranteesTerms = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabaopenplatformtrademodelGuaranteeTermsInfoResult=new AlibabaopenplatformtrademodelGuaranteeTermsInfo();
						$AlibabaopenplatformtrademodelGuaranteeTermsInfoResult->setArrayResult($arrayobject );
						$this->guaranteesTerms [$i] = $AlibabaopenplatformtrademodelGuaranteeTermsInfoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "cargoNumber", $this->arrayResult )) {
    			$this->cargoNumber = $arrayResult['cargoNumber'];
    			}
    		    	    			    		    			if (array_key_exists ( "description", $this->arrayResult )) {
    			$this->description = $arrayResult['description'];
    			}
    		    	    			    		    			if (array_key_exists ( "itemAmount", $this->arrayResult )) {
    			$this->itemAmount = $arrayResult['itemAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "price", $this->arrayResult )) {
    			$this->price = $arrayResult['price'];
    			}
    		    	    			    		    			if (array_key_exists ( "productID", $this->arrayResult )) {
    			$this->productID = $arrayResult['productID'];
    			}
    		    	    			    		    			if (array_key_exists ( "productImgUrl", $this->arrayResult )) {
    			$this->productImgUrl = $arrayResult['productImgUrl'];
    			}
    		    	    			    		    			if (array_key_exists ( "productSnapshotUrl", $this->arrayResult )) {
    			$this->productSnapshotUrl = $arrayResult['productSnapshotUrl'];
    			}
    		    	    			    		    			if (array_key_exists ( "quantity", $this->arrayResult )) {
    			$this->quantity = $arrayResult['quantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "refund", $this->arrayResult )) {
    			$this->refund = $arrayResult['refund'];
    			}
    		    	    			    		    			if (array_key_exists ( "skuID", $this->arrayResult )) {
    			$this->skuID = $arrayResult['skuID'];
    			}
    		    	    			    		    			if (array_key_exists ( "sort", $this->arrayResult )) {
    			$this->sort = $arrayResult['sort'];
    			}
    		    	    			    		    			if (array_key_exists ( "status", $this->arrayResult )) {
    			$this->status = $arrayResult['status'];
    			}
    		    	    			    		    			if (array_key_exists ( "subItemID", $this->arrayResult )) {
    			$this->subItemID = $arrayResult['subItemID'];
    			}
    		    	    			    		    			if (array_key_exists ( "type", $this->arrayResult )) {
    			$this->type = $arrayResult['type'];
    			}
    		    	    			    		    			if (array_key_exists ( "unit", $this->arrayResult )) {
    			$this->unit = $arrayResult['unit'];
    			}
    		    	    			    		    			if (array_key_exists ( "weight", $this->arrayResult )) {
    			$this->weight = $arrayResult['weight'];
    			}
    		    	    			    		    			if (array_key_exists ( "weightUnit", $this->arrayResult )) {
    			$this->weightUnit = $arrayResult['weightUnit'];
    			}
    		    	    			    		    		if (array_key_exists ( "guaranteesTerms", $this->arrayResult )) {
    		$guaranteesTermsResult=$arrayResult['guaranteesTerms'];
    			$this->guaranteesTerms = AlibabaopenplatformtrademodelGuaranteeTermsInfo();
    			$this->guaranteesTerms->$this->setStdResult ( $guaranteesTermsResult);
    		}
    		    	    		}
 
   
}
?>