<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradecomKeyValuePair.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeJsonGoodsParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaopenplatformtradeSpecInfo.class.php');

class AlibabaopenplatformtradeBizCargoGroup extends SDKDomain {

       	
    private $group;
    
        /**
    * @return 信息所属分组。多订单提交时用来分组。
    */
        public function getGroup() {
        return $this->group;
    }
    
    /**
     * 设置信息所属分组。多订单提交时用来分组。     
     * @param String $group     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGroup( $group) {
        $this->group = $group;
    }
    
        	
    private $cargoKey;
    
        /**
    * @return 商品id
    */
        public function getCargoKey() {
        return $this->cargoKey;
    }
    
    /**
     * 设置商品id     
     * @param String $cargoKey     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCargoKey( $cargoKey) {
        $this->cargoKey = $cargoKey;
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
    
        	
    private $unit;
    
        /**
    * @return 销售单位
    */
        public function getUnit() {
        return $this->unit;
    }
    
    /**
     * 设置销售单位     
     * @param String $unit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnit( $unit) {
        $this->unit = $unit;
    }
    
        	
    private $quantity;
    
        /**
    * @return 商品数量(计算金额用)
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置商品数量(计算金额用)     
     * @param Double $quantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
        	
    private $packageNum;
    
        /**
    * @return 商品件数
    */
        public function getPackageNum() {
        return $this->packageNum;
    }
    
    /**
     * 设置商品件数     
     * @param Double $packageNum     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPackageNum( $packageNum) {
        $this->packageNum = $packageNum;
    }
    
        	
    private $unitPrice;
    
        /**
    * @return 产品单价, 单位:元.
    */
        public function getUnitPrice() {
        return $this->unitPrice;
    }
    
    /**
     * 设置产品单价, 单位:元.     
     * @param Double $unitPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnitPrice( $unitPrice) {
        $this->unitPrice = $unitPrice;
    }
    
        	
    private $offerId;
    
        /**
    * @return Offer Id
    */
        public function getOfferId() {
        return $this->offerId;
    }
    
    /**
     * 设置Offer Id     
     * @param Long $offerId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOfferId( $offerId) {
        $this->offerId = $offerId;
    }
    
        	
    private $specId;
    
        /**
    * @return sku offer 时商品对应的specId.
    */
        public function getSpecId() {
        return $this->specId;
    }
    
    /**
     * 设置sku offer 时商品对应的specId.     
     * @param String $specId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecId( $specId) {
        $this->specId = $specId;
    }
    
        	
    private $skuId;
    
        /**
    * @return sku offer 时商品对应的skuId.
    */
        public function getSkuId() {
        return $this->skuId;
    }
    
    /**
     * 设置sku offer 时商品对应的skuId.     
     * @param Long $skuId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuId( $skuId) {
        $this->skuId = $skuId;
    }
    
        	
    private $categoryId;
    
        /**
    * @return 类目id
    */
        public function getCategoryId() {
        return $this->categoryId;
    }
    
    /**
     * 设置类目id     
     * @param Long $categoryId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCategoryId( $categoryId) {
        $this->categoryId = $categoryId;
    }
    
        	
    private $productAmount;
    
        /**
    * @return 金额，提交的时候需要判断金额是否发生变化。单位:元
    */
        public function getProductAmount() {
        return $this->productAmount;
    }
    
    /**
     * 设置金额，提交的时候需要判断金额是否发生变化。单位:元     
     * @param Double $productAmount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductAmount( $productAmount) {
        $this->productAmount = $productAmount;
    }
    
        	
    private $chooseFreeFreight;
    
        /**
    * @return 用户选择单品免运费 "0"：用户没有选择免用费 "1":用户选择免运费.
    */
        public function getChooseFreeFreight() {
        return $this->chooseFreeFreight;
    }
    
    /**
     * 设置用户选择单品免运费 "0"：用户没有选择免用费 "1":用户选择免运费.     
     * @param Integer $chooseFreeFreight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setChooseFreeFreight( $chooseFreeFreight) {
        $this->chooseFreeFreight = $chooseFreeFreight;
    }
    
        	
    private $discount;
    
        /**
    * @return 折扣。若没有填1.0
    */
        public function getDiscount() {
        return $this->discount;
    }
    
    /**
     * 设置折扣。若没有填1.0     
     * @param Double $discount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscount( $discount) {
        $this->discount = $discount;
    }
    
        	
    private $isVirtual;
    
        /**
    * @return 是否虚拟物品 0:不是， 1：虚拟物品
    */
        public function getIsVirtual() {
        return $this->isVirtual;
    }
    
    /**
     * 设置是否虚拟物品 0:不是， 1：虚拟物品     
     * @param Integer $isVirtual     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsVirtual( $isVirtual) {
        $this->isVirtual = $isVirtual;
    }
    
        	
    private $cartId;
    
        /**
    * @return 淘宝cartId
    */
        public function getCartId() {
        return $this->cartId;
    }
    
    /**
     * 设置淘宝cartId     
     * @param Long $cartId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCartId( $cartId) {
        $this->cartId = $cartId;
    }
    
        	
    private $promotionId;
    
        /**
    * @return 单品优惠id
    */
        public function getPromotionId() {
        return $this->promotionId;
    }
    
    /**
     * 设置单品优惠id     
     * @param String $promotionId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPromotionId( $promotionId) {
        $this->promotionId = $promotionId;
    }
    
        	
    private $mergedJsonVar;
    
        /**
    * @return json格式的所有数据. 批量下单进行了使用.
    */
        public function getMergedJsonVar() {
        return $this->mergedJsonVar;
    }
    
    /**
     * 设置json格式的所有数据. 批量下单进行了使用.     
     * @param String $mergedJsonVar     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMergedJsonVar( $mergedJsonVar) {
        $this->mergedJsonVar = $mergedJsonVar;
    }
    
        	
    private $buyerCharge;
    
        /**
    * @return 单个商品关联运费模板时买家承担服务费。true:买家，false:卖家
    */
        public function getBuyerCharge() {
        return $this->buyerCharge;
    }
    
    /**
     * 设置单个商品关联运费模板时买家承担服务费。true:买家，false:卖家     
     * @param Boolean $buyerCharge     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setBuyerCharge( $buyerCharge) {
        $this->buyerCharge = $buyerCharge;
    }
    
        	
    private $ext;
    
        /**
    * @return 扩张属性，以json串形式，存与offer同一级别的属性
    */
        public function getExt() {
        return $this->ext;
    }
    
    /**
     * 设置扩张属性，以json串形式，存与offer同一级别的属性     
     * @param String $ext     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExt( $ext) {
        $this->ext = $ext;
    }
    
        	
    private $attachedType;
    
        /**
    * @return 附加标的物类型, 拆单校验遇到此标记会越过此标的物的拆单结果比较 默认没有值, 存样服务: "cyfw"
    */
        public function getAttachedType() {
        return $this->attachedType;
    }
    
    /**
     * 设置附加标的物类型, 拆单校验遇到此标记会越过此标的物的拆单结果比较 默认没有值, 存样服务: "cyfw"     
     * @param String $attachedType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttachedType( $attachedType) {
        $this->attachedType = $attachedType;
    }
    
        	
    private $free;
    
        /**
    * @return 是否免费, 一般用于服务类型商品.
    */
        public function getFree() {
        return $this->free;
    }
    
    /**
     * 设置是否免费, 一般用于服务类型商品.     
     * @param Boolean $free     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFree( $free) {
        $this->free = $free;
    }
    
        	
    private $gift;
    
        /**
    * @return 赠品标记, hg: 换购 , zs: 赠送
    */
        public function getGift() {
        return $this->gift;
    }
    
    /**
     * 设置赠品标记, hg: 换购 , zs: 赠送     
     * @param String $gift     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGift( $gift) {
        $this->gift = $gift;
    }
    
        	
    private $extParams;
    
        /**
    * @return 扩展数据
    */
        public function getExtParams() {
        return $this->extParams;
    }
    
    /**
     * 设置扩展数据     
     * @param array include @see AlibabatradecomKeyValuePair[] $extParams     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExtParams(AlibabatradecomKeyValuePair $extParams) {
        $this->extParams = $extParams;
    }
    
        	
    private $supplierName;
    
        /**
    * @return 供应商名字
    */
        public function getSupplierName() {
        return $this->supplierName;
    }
    
    /**
     * 设置供应商名字     
     * @param String $supplierName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplierName( $supplierName) {
        $this->supplierName = $supplierName;
    }
    
        	
    private $warehouse;
    
        /**
    * @return 仓库名称
    */
        public function getWarehouse() {
        return $this->warehouse;
    }
    
    /**
     * 设置仓库名称     
     * @param String $warehouse     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWarehouse( $warehouse) {
        $this->warehouse = $warehouse;
    }
    
        	
    private $discountFee;
    
        /**
    * @return 减免金额, 单位, 元
    */
        public function getDiscountFee() {
        return $this->discountFee;
    }
    
    /**
     * 设置减免金额, 单位, 元     
     * @param Double $discountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscountFee( $discountFee) {
        $this->discountFee = $discountFee;
    }
    
        	
    private $additionalFee;
    
        /**
    * @return 附加费,单位，元
    */
        public function getAdditionalFee() {
        return $this->additionalFee;
    }
    
    /**
     * 设置附加费,单位，元     
     * @param Double $additionalFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAdditionalFee( $additionalFee) {
        $this->additionalFee = $additionalFee;
    }
    
        	
    private $sellerMemberId;
    
        /**
    * @return 卖家memberId
    */
        public function getSellerMemberId() {
        return $this->sellerMemberId;
    }
    
    /**
     * 设置卖家memberId     
     * @param String $sellerMemberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSellerMemberId( $sellerMemberId) {
        $this->sellerMemberId = $sellerMemberId;
    }
    
        	
    private $jsonGoodsParam;
    
        /**
    * @return 当前提交订单操作, 订单块对应的展示订单的货品url参数模型(本次提交订单对应的展示订单url参数, 此模型的该属性不由前端直接赋值)
    */
        public function getJsonGoodsParam() {
        return $this->jsonGoodsParam;
    }
    
    /**
     * 设置当前提交订单操作, 订单块对应的展示订单的货品url参数模型(本次提交订单对应的展示订单url参数, 此模型的该属性不由前端直接赋值)     
     * @param AlibabaopenplatformtradeJsonGoodsParam $jsonGoodsParam     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setJsonGoodsParam(AlibabaopenplatformtradeJsonGoodsParam $jsonGoodsParam) {
        $this->jsonGoodsParam = $jsonGoodsParam;
    }
    
        	
    private $specInfo;
    
        /**
    * @return 规格属性
    */
        public function getSpecInfo() {
        return $this->specInfo;
    }
    
    /**
     * 设置规格属性     
     * @param AlibabaopenplatformtradeSpecInfo $specInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecInfo(AlibabaopenplatformtradeSpecInfo $specInfo) {
        $this->specInfo = $specInfo;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "group", $this->stdResult )) {
    				$this->group = $this->stdResult->{"group"};
    			}
    			    		    				    			    			if (array_key_exists ( "cargoKey", $this->stdResult )) {
    				$this->cargoKey = $this->stdResult->{"cargoKey"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "unit", $this->stdResult )) {
    				$this->unit = $this->stdResult->{"unit"};
    			}
    			    		    				    			    			if (array_key_exists ( "quantity", $this->stdResult )) {
    				$this->quantity = $this->stdResult->{"quantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "packageNum", $this->stdResult )) {
    				$this->packageNum = $this->stdResult->{"packageNum"};
    			}
    			    		    				    			    			if (array_key_exists ( "unitPrice", $this->stdResult )) {
    				$this->unitPrice = $this->stdResult->{"unitPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "offerId", $this->stdResult )) {
    				$this->offerId = $this->stdResult->{"offerId"};
    			}
    			    		    				    			    			if (array_key_exists ( "specId", $this->stdResult )) {
    				$this->specId = $this->stdResult->{"specId"};
    			}
    			    		    				    			    			if (array_key_exists ( "skuId", $this->stdResult )) {
    				$this->skuId = $this->stdResult->{"skuId"};
    			}
    			    		    				    			    			if (array_key_exists ( "categoryId", $this->stdResult )) {
    				$this->categoryId = $this->stdResult->{"categoryId"};
    			}
    			    		    				    			    			if (array_key_exists ( "productAmount", $this->stdResult )) {
    				$this->productAmount = $this->stdResult->{"productAmount"};
    			}
    			    		    				    			    			if (array_key_exists ( "chooseFreeFreight", $this->stdResult )) {
    				$this->chooseFreeFreight = $this->stdResult->{"chooseFreeFreight"};
    			}
    			    		    				    			    			if (array_key_exists ( "discount", $this->stdResult )) {
    				$this->discount = $this->stdResult->{"discount"};
    			}
    			    		    				    			    			if (array_key_exists ( "isVirtual", $this->stdResult )) {
    				$this->isVirtual = $this->stdResult->{"isVirtual"};
    			}
    			    		    				    			    			if (array_key_exists ( "cartId", $this->stdResult )) {
    				$this->cartId = $this->stdResult->{"cartId"};
    			}
    			    		    				    			    			if (array_key_exists ( "promotionId", $this->stdResult )) {
    				$this->promotionId = $this->stdResult->{"promotionId"};
    			}
    			    		    				    			    			if (array_key_exists ( "mergedJsonVar", $this->stdResult )) {
    				$this->mergedJsonVar = $this->stdResult->{"mergedJsonVar"};
    			}
    			    		    				    			    			if (array_key_exists ( "buyerCharge", $this->stdResult )) {
    				$this->buyerCharge = $this->stdResult->{"buyerCharge"};
    			}
    			    		    				    			    			if (array_key_exists ( "ext", $this->stdResult )) {
    				$this->ext = $this->stdResult->{"ext"};
    			}
    			    		    				    			    			if (array_key_exists ( "attachedType", $this->stdResult )) {
    				$this->attachedType = $this->stdResult->{"attachedType"};
    			}
    			    		    				    			    			if (array_key_exists ( "free", $this->stdResult )) {
    				$this->free = $this->stdResult->{"free"};
    			}
    			    		    				    			    			if (array_key_exists ( "gift", $this->stdResult )) {
    				$this->gift = $this->stdResult->{"gift"};
    			}
    			    		    				    			    			if (array_key_exists ( "extParams", $this->stdResult )) {
    			$extParamsResult=$this->stdResult->{"extParams"};
    				$object = json_decode ( json_encode ( $extParamsResult ), true );
					$this->extParams = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradecomKeyValuePairResult=new AlibabatradecomKeyValuePair();
						$AlibabatradecomKeyValuePairResult->setArrayResult($arrayobject );
						$this->extParams [$i] = $AlibabatradecomKeyValuePairResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "supplierName", $this->stdResult )) {
    				$this->supplierName = $this->stdResult->{"supplierName"};
    			}
    			    		    				    			    			if (array_key_exists ( "warehouse", $this->stdResult )) {
    				$this->warehouse = $this->stdResult->{"warehouse"};
    			}
    			    		    				    			    			if (array_key_exists ( "discountFee", $this->stdResult )) {
    				$this->discountFee = $this->stdResult->{"discountFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "additionalFee", $this->stdResult )) {
    				$this->additionalFee = $this->stdResult->{"additionalFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "sellerMemberId", $this->stdResult )) {
    				$this->sellerMemberId = $this->stdResult->{"sellerMemberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "jsonGoodsParam", $this->stdResult )) {
    				$jsonGoodsParamResult=$this->stdResult->{"jsonGoodsParam"};
    				$this->jsonGoodsParam = new AlibabaopenplatformtradeJsonGoodsParam();
    				$this->jsonGoodsParam->setStdResult ( $jsonGoodsParamResult);
    			}
    			    		    				    			    			if (array_key_exists ( "specInfo", $this->stdResult )) {
    				$specInfoResult=$this->stdResult->{"specInfo"};
    				$this->specInfo = new AlibabaopenplatformtradeSpecInfo();
    				$this->specInfo->setStdResult ( $specInfoResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "group", $this->arrayResult )) {
    			$this->group = $arrayResult['group'];
    			}
    		    	    			    		    			if (array_key_exists ( "cargoKey", $this->arrayResult )) {
    			$this->cargoKey = $arrayResult['cargoKey'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "unit", $this->arrayResult )) {
    			$this->unit = $arrayResult['unit'];
    			}
    		    	    			    		    			if (array_key_exists ( "quantity", $this->arrayResult )) {
    			$this->quantity = $arrayResult['quantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "packageNum", $this->arrayResult )) {
    			$this->packageNum = $arrayResult['packageNum'];
    			}
    		    	    			    		    			if (array_key_exists ( "unitPrice", $this->arrayResult )) {
    			$this->unitPrice = $arrayResult['unitPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "offerId", $this->arrayResult )) {
    			$this->offerId = $arrayResult['offerId'];
    			}
    		    	    			    		    			if (array_key_exists ( "specId", $this->arrayResult )) {
    			$this->specId = $arrayResult['specId'];
    			}
    		    	    			    		    			if (array_key_exists ( "skuId", $this->arrayResult )) {
    			$this->skuId = $arrayResult['skuId'];
    			}
    		    	    			    		    			if (array_key_exists ( "categoryId", $this->arrayResult )) {
    			$this->categoryId = $arrayResult['categoryId'];
    			}
    		    	    			    		    			if (array_key_exists ( "productAmount", $this->arrayResult )) {
    			$this->productAmount = $arrayResult['productAmount'];
    			}
    		    	    			    		    			if (array_key_exists ( "chooseFreeFreight", $this->arrayResult )) {
    			$this->chooseFreeFreight = $arrayResult['chooseFreeFreight'];
    			}
    		    	    			    		    			if (array_key_exists ( "discount", $this->arrayResult )) {
    			$this->discount = $arrayResult['discount'];
    			}
    		    	    			    		    			if (array_key_exists ( "isVirtual", $this->arrayResult )) {
    			$this->isVirtual = $arrayResult['isVirtual'];
    			}
    		    	    			    		    			if (array_key_exists ( "cartId", $this->arrayResult )) {
    			$this->cartId = $arrayResult['cartId'];
    			}
    		    	    			    		    			if (array_key_exists ( "promotionId", $this->arrayResult )) {
    			$this->promotionId = $arrayResult['promotionId'];
    			}
    		    	    			    		    			if (array_key_exists ( "mergedJsonVar", $this->arrayResult )) {
    			$this->mergedJsonVar = $arrayResult['mergedJsonVar'];
    			}
    		    	    			    		    			if (array_key_exists ( "buyerCharge", $this->arrayResult )) {
    			$this->buyerCharge = $arrayResult['buyerCharge'];
    			}
    		    	    			    		    			if (array_key_exists ( "ext", $this->arrayResult )) {
    			$this->ext = $arrayResult['ext'];
    			}
    		    	    			    		    			if (array_key_exists ( "attachedType", $this->arrayResult )) {
    			$this->attachedType = $arrayResult['attachedType'];
    			}
    		    	    			    		    			if (array_key_exists ( "free", $this->arrayResult )) {
    			$this->free = $arrayResult['free'];
    			}
    		    	    			    		    			if (array_key_exists ( "gift", $this->arrayResult )) {
    			$this->gift = $arrayResult['gift'];
    			}
    		    	    			    		    		if (array_key_exists ( "extParams", $this->arrayResult )) {
    		$extParamsResult=$arrayResult['extParams'];
    			$this->extParams = AlibabatradecomKeyValuePair();
    			$this->extParams->$this->setStdResult ( $extParamsResult);
    		}
    		    	    			    		    			if (array_key_exists ( "supplierName", $this->arrayResult )) {
    			$this->supplierName = $arrayResult['supplierName'];
    			}
    		    	    			    		    			if (array_key_exists ( "warehouse", $this->arrayResult )) {
    			$this->warehouse = $arrayResult['warehouse'];
    			}
    		    	    			    		    			if (array_key_exists ( "discountFee", $this->arrayResult )) {
    			$this->discountFee = $arrayResult['discountFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "additionalFee", $this->arrayResult )) {
    			$this->additionalFee = $arrayResult['additionalFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "sellerMemberId", $this->arrayResult )) {
    			$this->sellerMemberId = $arrayResult['sellerMemberId'];
    			}
    		    	    			    		    		if (array_key_exists ( "jsonGoodsParam", $this->arrayResult )) {
    		$jsonGoodsParamResult=$arrayResult['jsonGoodsParam'];
    			    			$this->jsonGoodsParam = new AlibabaopenplatformtradeJsonGoodsParam();
    			    			$this->jsonGoodsParam->$this->setStdResult ( $jsonGoodsParamResult);
    		}
    		    	    			    		    		if (array_key_exists ( "specInfo", $this->arrayResult )) {
    		$specInfoResult=$arrayResult['specInfo'];
    			    			$this->specInfo = new AlibabaopenplatformtradeSpecInfo();
    			    			$this->specInfo->$this->setStdResult ( $specInfoResult);
    		}
    		    	    		}
 
   
}
?>