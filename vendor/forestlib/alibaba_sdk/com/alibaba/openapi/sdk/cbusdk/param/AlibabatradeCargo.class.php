<?php

include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeSubPayInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradespecInfo.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabatradeCargoImage.class.php');

class AlibabatradeCargo extends SDKDomain {

       	
    private $additionalFee;
    
        /**
    * @return 
    */
        public function getAdditionalFee() {
        return $this->additionalFee;
    }
    
    /**
     * 设置     
     * @param Double $additionalFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAdditionalFee( $additionalFee) {
        $this->additionalFee = $additionalFee;
    }
    
        	
    private $amount;
    
        /**
    * @return 
    */
        public function getAmount() {
        return $this->amount;
    }
    
    /**
     * 设置     
     * @param Double $amount     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAmount( $amount) {
        $this->amount = $amount;
    }
    
        	
    private $attachedType;
    
        /**
    * @return 
    */
        public function getAttachedType() {
        return $this->attachedType;
    }
    
    /**
     * 设置     
     * @param String $attachedType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAttachedType( $attachedType) {
        $this->attachedType = $attachedType;
    }
    
        	
    private $catTree;
    
        /**
    * @return 
    */
        public function getCatTree() {
        return $this->catTree;
    }
    
    /**
     * 设置     
     * @param String $catTree     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCatTree( $catTree) {
        $this->catTree = $catTree;
    }
    
        	
    private $categoryId;
    
        /**
    * @return 
    */
        public function getCategoryId() {
        return $this->categoryId;
    }
    
    /**
     * 设置     
     * @param Long $categoryId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setCategoryId( $categoryId) {
        $this->categoryId = $categoryId;
    }
    
        	
    private $dangerous;
    
        /**
    * @return 
    */
        public function getDangerous() {
        return $this->dangerous;
    }
    
    /**
     * 设置     
     * @param Boolean $dangerous     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDangerous( $dangerous) {
        $this->dangerous = $dangerous;
    }
    
        	
    private $discountFee;
    
        /**
    * @return 
    */
        public function getDiscountFee() {
        return $this->discountFee;
    }
    
    /**
     * 设置     
     * @param Double $discountFee     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDiscountFee( $discountFee) {
        $this->discountFee = $discountFee;
    }
    
        	
    private $externalId;
    
        /**
    * @return 
    */
        public function getExternalId() {
        return $this->externalId;
    }
    
    /**
     * 设置     
     * @param String $externalId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setExternalId( $externalId) {
        $this->externalId = $externalId;
    }
    
        	
    private $finalUnitPrice;
    
        /**
    * @return 
    */
        public function getFinalUnitPrice() {
        return $this->finalUnitPrice;
    }
    
    /**
     * 设置     
     * @param Double $finalUnitPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setFinalUnitPrice( $finalUnitPrice) {
        $this->finalUnitPrice = $finalUnitPrice;
    }
    
        	
    private $gift;
    
        /**
    * @return 
    */
        public function getGift() {
        return $this->gift;
    }
    
    /**
     * 设置     
     * @param String $gift     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setGift( $gift) {
        $this->gift = $gift;
    }
    
        	
    private $icon;
    
        /**
    * @return 
    */
        public function getIcon() {
        return $this->icon;
    }
    
    /**
     * 设置     
     * @param String $icon     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIcon( $icon) {
        $this->icon = $icon;
    }
    
        	
    private $itemId;
    
        /**
    * @return 
    */
        public function getItemId() {
        return $this->itemId;
    }
    
    /**
     * 设置     
     * @param Long $itemId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setItemId( $itemId) {
        $this->itemId = $itemId;
    }
    
        	
    private $key;
    
        /**
    * @return 
    */
        public function getKey() {
        return $this->key;
    }
    
    /**
     * 设置     
     * @param String $key     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setKey( $key) {
        $this->key = $key;
    }
    
        	
    private $marketingScene;
    
        /**
    * @return 
    */
        public function getMarketingScene() {
        return $this->marketingScene;
    }
    
    /**
     * 设置     
     * @param String $marketingScene     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMarketingScene( $marketingScene) {
        $this->marketingScene = $marketingScene;
    }
    
        	
    private $maxPrice;
    
        /**
    * @return 
    */
        public function getMaxPrice() {
        return $this->maxPrice;
    }
    
    /**
     * 设置     
     * @param Double $maxPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMaxPrice( $maxPrice) {
        $this->maxPrice = $maxPrice;
    }
    
        	
    private $maxQuantity;
    
        /**
    * @return 
    */
        public function getMaxQuantity() {
        return $this->maxQuantity;
    }
    
    /**
     * 设置     
     * @param Integer $maxQuantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMaxQuantity( $maxQuantity) {
        $this->maxQuantity = $maxQuantity;
    }
    
        	
    private $message;
    
        /**
    * @return 
    */
        public function getMessage() {
        return $this->message;
    }
    
    /**
     * 设置     
     * @param String $message     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMessage( $message) {
        $this->message = $message;
    }
    
        	
    private $miniPrice;
    
        /**
    * @return 
    */
        public function getMiniPrice() {
        return $this->miniPrice;
    }
    
    /**
     * 设置     
     * @param Double $miniPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMiniPrice( $miniPrice) {
        $this->miniPrice = $miniPrice;
    }
    
        	
    private $miniQuantity;
    
        /**
    * @return 
    */
        public function getMiniQuantity() {
        return $this->miniQuantity;
    }
    
    /**
     * 设置     
     * @param Integer $miniQuantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setMiniQuantity( $miniQuantity) {
        $this->miniQuantity = $miniQuantity;
    }
    
        	
    private $name;
    
        /**
    * @return 
    */
        public function getName() {
        return $this->name;
    }
    
    /**
     * 设置     
     * @param String $name     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setName( $name) {
        $this->name = $name;
    }
    
        	
    private $offerId;
    
        /**
    * @return 
    */
        public function getOfferId() {
        return $this->offerId;
    }
    
    /**
     * 设置     
     * @param Long $offerId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOfferId( $offerId) {
        $this->offerId = $offerId;
    }
    
        	
    private $orderCargoMaxPrice;
    
        /**
    * @return 
    */
        public function getOrderCargoMaxPrice() {
        return $this->orderCargoMaxPrice;
    }
    
    /**
     * 设置     
     * @param Double $orderCargoMaxPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderCargoMaxPrice( $orderCargoMaxPrice) {
        $this->orderCargoMaxPrice = $orderCargoMaxPrice;
    }
    
        	
    private $orderCargoMinPrice;
    
        /**
    * @return 
    */
        public function getOrderCargoMinPrice() {
        return $this->orderCargoMinPrice;
    }
    
    /**
     * 设置     
     * @param Double $orderCargoMinPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderCargoMinPrice( $orderCargoMinPrice) {
        $this->orderCargoMinPrice = $orderCargoMinPrice;
    }
    
        	
    private $orderSourceType;
    
        /**
    * @return 
    */
        public function getOrderSourceType() {
        return $this->orderSourceType;
    }
    
    /**
     * 设置     
     * @param String $orderSourceType     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setOrderSourceType( $orderSourceType) {
        $this->orderSourceType = $orderSourceType;
    }
    
        	
    private $priceFactor;
    
        /**
    * @return 
    */
        public function getPriceFactor() {
        return $this->priceFactor;
    }
    
    /**
     * 设置     
     * @param Double $priceFactor     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPriceFactor( $priceFactor) {
        $this->priceFactor = $priceFactor;
    }
    
        	
    private $productDesc;
    
        /**
    * @return 
    */
        public function getProductDesc() {
        return $this->productDesc;
    }
    
    /**
     * 设置     
     * @param String $productDesc     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setProductDesc( $productDesc) {
        $this->productDesc = $productDesc;
    }
    
        	
    private $promotion;
    
        /**
    * @return 
    */
        public function getPromotion() {
        return $this->promotion;
    }
    
    /**
     * 设置     
     * @param Double $promotion     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setPromotion( $promotion) {
        $this->promotion = $promotion;
    }
    
        	
    private $quantity;
    
        /**
    * @return 
    */
        public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * 设置     
     * @param Double $quantity     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setQuantity( $quantity) {
        $this->quantity = $quantity;
    }
    
        	
    private $resultCode;
    
        /**
    * @return 
    */
        public function getResultCode() {
        return $this->resultCode;
    }
    
    /**
     * 设置     
     * @param String $resultCode     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setResultCode( $resultCode) {
        $this->resultCode = $resultCode;
    }
    
        	
    private $skuId;
    
        /**
    * @return 
    */
        public function getSkuId() {
        return $this->skuId;
    }
    
    /**
     * 设置     
     * @param Long $skuId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSkuId( $skuId) {
        $this->skuId = $skuId;
    }
    
        	
    private $specId;
    
        /**
    * @return 
    */
        public function getSpecId() {
        return $this->specId;
    }
    
    /**
     * 设置     
     * @param String $specId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecId( $specId) {
        $this->specId = $specId;
    }
    
        	
    private $summImage;
    
        /**
    * @return 
    */
        public function getSummImage() {
        return $this->summImage;
    }
    
    /**
     * 设置     
     * @param String $summImage     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSummImage( $summImage) {
        $this->summImage = $summImage;
    }
    
        	
    private $supplierName;
    
        /**
    * @return 
    */
        public function getSupplierName() {
        return $this->supplierName;
    }
    
    /**
     * 设置     
     * @param String $supplierName     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplierName( $supplierName) {
        $this->supplierName = $supplierName;
    }
    
        	
    private $supplyMemberId;
    
        /**
    * @return 
    */
        public function getSupplyMemberId() {
        return $this->supplyMemberId;
    }
    
    /**
     * 设置     
     * @param String $supplyMemberId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSupplyMemberId( $supplyMemberId) {
        $this->supplyMemberId = $supplyMemberId;
    }
    
        	
    private $taoOfferId;
    
        /**
    * @return 
    */
        public function getTaoOfferId() {
        return $this->taoOfferId;
    }
    
    /**
     * 设置     
     * @param String $taoOfferId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setTaoOfferId( $taoOfferId) {
        $this->taoOfferId = $taoOfferId;
    }
    
        	
    private $unit;
    
        /**
    * @return 
    */
        public function getUnit() {
        return $this->unit;
    }
    
    /**
     * 设置     
     * @param String $unit     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnit( $unit) {
        $this->unit = $unit;
    }
    
        	
    private $unitPrice;
    
        /**
    * @return 
    */
        public function getUnitPrice() {
        return $this->unitPrice;
    }
    
    /**
     * 设置     
     * @param Double $unitPrice     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUnitPrice( $unitPrice) {
        $this->unitPrice = $unitPrice;
    }
    
        	
    private $url;
    
        /**
    * @return 
    */
        public function getUrl() {
        return $this->url;
    }
    
    /**
     * 设置     
     * @param String $url     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setUrl( $url) {
        $this->url = $url;
    }
    
        	
    private $isVirtual;
    
        /**
    * @return 
    */
        public function getIsVirtual() {
        return $this->isVirtual;
    }
    
    /**
     * 设置     
     * @param Boolean $isVirtual     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setIsVirtual( $isVirtual) {
        $this->isVirtual = $isVirtual;
    }
    
        	
    private $warehouse;
    
        /**
    * @return 
    */
        public function getWarehouse() {
        return $this->warehouse;
    }
    
    /**
     * 设置     
     * @param String $warehouse     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWarehouse( $warehouse) {
        $this->warehouse = $warehouse;
    }
    
        	
    private $weight;
    
        /**
    * @return 
    */
        public function getWeight() {
        return $this->weight;
    }
    
    /**
     * 设置     
     * @param Double $weight     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setWeight( $weight) {
        $this->weight = $weight;
    }
    
        	
    private $subPayInfors;
    
        /**
    * @return 
    */
        public function getSubPayInfors() {
        return $this->subPayInfors;
    }
    
    /**
     * 设置     
     * @param array include @see AlibabatradeSubPayInfo[] $subPayInfors     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSubPayInfors(AlibabatradeSubPayInfo $subPayInfors) {
        $this->subPayInfors = $subPayInfors;
    }
    
        	
    private $specInfo;
    
        /**
    * @return 
    */
        public function getSpecInfo() {
        return $this->specInfo;
    }
    
    /**
     * 设置     
     * @param array include @see AlibabatradespecInfo[] $specInfo     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setSpecInfo(AlibabatradespecInfo $specInfo) {
        $this->specInfo = $specInfo;
    }
    
        	
    private $images;
    
        /**
    * @return 
    */
        public function getImages() {
        return $this->images;
    }
    
    /**
     * 设置     
     * @param array include @see AlibabatradeCargoImage[] $images     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setImages(AlibabatradeCargoImage $images) {
        $this->images = $images;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "additionalFee", $this->stdResult )) {
    				$this->additionalFee = $this->stdResult->{"additionalFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "amount", $this->stdResult )) {
    				$this->amount = $this->stdResult->{"amount"};
    			}
    			    		    				    			    			if (array_key_exists ( "attachedType", $this->stdResult )) {
    				$this->attachedType = $this->stdResult->{"attachedType"};
    			}
    			    		    				    			    			if (array_key_exists ( "catTree", $this->stdResult )) {
    				$this->catTree = $this->stdResult->{"catTree"};
    			}
    			    		    				    			    			if (array_key_exists ( "categoryId", $this->stdResult )) {
    				$this->categoryId = $this->stdResult->{"categoryId"};
    			}
    			    		    				    			    			if (array_key_exists ( "dangerous", $this->stdResult )) {
    				$this->dangerous = $this->stdResult->{"dangerous"};
    			}
    			    		    				    			    			if (array_key_exists ( "discountFee", $this->stdResult )) {
    				$this->discountFee = $this->stdResult->{"discountFee"};
    			}
    			    		    				    			    			if (array_key_exists ( "externalId", $this->stdResult )) {
    				$this->externalId = $this->stdResult->{"externalId"};
    			}
    			    		    				    			    			if (array_key_exists ( "finalUnitPrice", $this->stdResult )) {
    				$this->finalUnitPrice = $this->stdResult->{"finalUnitPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "gift", $this->stdResult )) {
    				$this->gift = $this->stdResult->{"gift"};
    			}
    			    		    				    			    			if (array_key_exists ( "icon", $this->stdResult )) {
    				$this->icon = $this->stdResult->{"icon"};
    			}
    			    		    				    			    			if (array_key_exists ( "itemId", $this->stdResult )) {
    				$this->itemId = $this->stdResult->{"itemId"};
    			}
    			    		    				    			    			if (array_key_exists ( "key", $this->stdResult )) {
    				$this->key = $this->stdResult->{"key"};
    			}
    			    		    				    			    			if (array_key_exists ( "marketingScene", $this->stdResult )) {
    				$this->marketingScene = $this->stdResult->{"marketingScene"};
    			}
    			    		    				    			    			if (array_key_exists ( "maxPrice", $this->stdResult )) {
    				$this->maxPrice = $this->stdResult->{"maxPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "maxQuantity", $this->stdResult )) {
    				$this->maxQuantity = $this->stdResult->{"maxQuantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "message", $this->stdResult )) {
    				$this->message = $this->stdResult->{"message"};
    			}
    			    		    				    			    			if (array_key_exists ( "miniPrice", $this->stdResult )) {
    				$this->miniPrice = $this->stdResult->{"miniPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "miniQuantity", $this->stdResult )) {
    				$this->miniQuantity = $this->stdResult->{"miniQuantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "name", $this->stdResult )) {
    				$this->name = $this->stdResult->{"name"};
    			}
    			    		    				    			    			if (array_key_exists ( "offerId", $this->stdResult )) {
    				$this->offerId = $this->stdResult->{"offerId"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderCargoMaxPrice", $this->stdResult )) {
    				$this->orderCargoMaxPrice = $this->stdResult->{"orderCargoMaxPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderCargoMinPrice", $this->stdResult )) {
    				$this->orderCargoMinPrice = $this->stdResult->{"orderCargoMinPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "orderSourceType", $this->stdResult )) {
    				$this->orderSourceType = $this->stdResult->{"orderSourceType"};
    			}
    			    		    				    			    			if (array_key_exists ( "priceFactor", $this->stdResult )) {
    				$this->priceFactor = $this->stdResult->{"priceFactor"};
    			}
    			    		    				    			    			if (array_key_exists ( "productDesc", $this->stdResult )) {
    				$this->productDesc = $this->stdResult->{"productDesc"};
    			}
    			    		    				    			    			if (array_key_exists ( "promotion", $this->stdResult )) {
    				$this->promotion = $this->stdResult->{"promotion"};
    			}
    			    		    				    			    			if (array_key_exists ( "quantity", $this->stdResult )) {
    				$this->quantity = $this->stdResult->{"quantity"};
    			}
    			    		    				    			    			if (array_key_exists ( "resultCode", $this->stdResult )) {
    				$this->resultCode = $this->stdResult->{"resultCode"};
    			}
    			    		    				    			    			if (array_key_exists ( "skuId", $this->stdResult )) {
    				$this->skuId = $this->stdResult->{"skuId"};
    			}
    			    		    				    			    			if (array_key_exists ( "specId", $this->stdResult )) {
    				$this->specId = $this->stdResult->{"specId"};
    			}
    			    		    				    			    			if (array_key_exists ( "summImage", $this->stdResult )) {
    				$this->summImage = $this->stdResult->{"summImage"};
    			}
    			    		    				    			    			if (array_key_exists ( "supplierName", $this->stdResult )) {
    				$this->supplierName = $this->stdResult->{"supplierName"};
    			}
    			    		    				    			    			if (array_key_exists ( "supplyMemberId", $this->stdResult )) {
    				$this->supplyMemberId = $this->stdResult->{"supplyMemberId"};
    			}
    			    		    				    			    			if (array_key_exists ( "taoOfferId", $this->stdResult )) {
    				$this->taoOfferId = $this->stdResult->{"taoOfferId"};
    			}
    			    		    				    			    			if (array_key_exists ( "unit", $this->stdResult )) {
    				$this->unit = $this->stdResult->{"unit"};
    			}
    			    		    				    			    			if (array_key_exists ( "unitPrice", $this->stdResult )) {
    				$this->unitPrice = $this->stdResult->{"unitPrice"};
    			}
    			    		    				    			    			if (array_key_exists ( "url", $this->stdResult )) {
    				$this->url = $this->stdResult->{"url"};
    			}
    			    		    				    			    			if (array_key_exists ( "isVirtual", $this->stdResult )) {
    				$this->isVirtual = $this->stdResult->{"isVirtual"};
    			}
    			    		    				    			    			if (array_key_exists ( "warehouse", $this->stdResult )) {
    				$this->warehouse = $this->stdResult->{"warehouse"};
    			}
    			    		    				    			    			if (array_key_exists ( "weight", $this->stdResult )) {
    				$this->weight = $this->stdResult->{"weight"};
    			}
    			    		    				    			    			if (array_key_exists ( "subPayInfors", $this->stdResult )) {
    			$subPayInforsResult=$this->stdResult->{"subPayInfors"};
    				$object = json_decode ( json_encode ( $subPayInforsResult ), true );
					$this->subPayInfors = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradeSubPayInfoResult=new AlibabatradeSubPayInfo();
						$AlibabatradeSubPayInfoResult->setArrayResult($arrayobject );
						$this->subPayInfors [$i] = $AlibabatradeSubPayInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "specInfo", $this->stdResult )) {
    			$specInfoResult=$this->stdResult->{"specInfo"};
    				$object = json_decode ( json_encode ( $specInfoResult ), true );
					$this->specInfo = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradespecInfoResult=new AlibabatradespecInfo();
						$AlibabatradespecInfoResult->setArrayResult($arrayobject );
						$this->specInfo [$i] = $AlibabatradespecInfoResult;
					}
    			}
    			    		    				    			    			if (array_key_exists ( "images", $this->stdResult )) {
    			$imagesResult=$this->stdResult->{"images"};
    				$object = json_decode ( json_encode ( $imagesResult ), true );
					$this->images = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$AlibabatradeCargoImageResult=new AlibabatradeCargoImage();
						$AlibabatradeCargoImageResult->setArrayResult($arrayobject );
						$this->images [$i] = $AlibabatradeCargoImageResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    			if (array_key_exists ( "additionalFee", $this->arrayResult )) {
    			$this->additionalFee = $arrayResult['additionalFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "amount", $this->arrayResult )) {
    			$this->amount = $arrayResult['amount'];
    			}
    		    	    			    		    			if (array_key_exists ( "attachedType", $this->arrayResult )) {
    			$this->attachedType = $arrayResult['attachedType'];
    			}
    		    	    			    		    			if (array_key_exists ( "catTree", $this->arrayResult )) {
    			$this->catTree = $arrayResult['catTree'];
    			}
    		    	    			    		    			if (array_key_exists ( "categoryId", $this->arrayResult )) {
    			$this->categoryId = $arrayResult['categoryId'];
    			}
    		    	    			    		    			if (array_key_exists ( "dangerous", $this->arrayResult )) {
    			$this->dangerous = $arrayResult['dangerous'];
    			}
    		    	    			    		    			if (array_key_exists ( "discountFee", $this->arrayResult )) {
    			$this->discountFee = $arrayResult['discountFee'];
    			}
    		    	    			    		    			if (array_key_exists ( "externalId", $this->arrayResult )) {
    			$this->externalId = $arrayResult['externalId'];
    			}
    		    	    			    		    			if (array_key_exists ( "finalUnitPrice", $this->arrayResult )) {
    			$this->finalUnitPrice = $arrayResult['finalUnitPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "gift", $this->arrayResult )) {
    			$this->gift = $arrayResult['gift'];
    			}
    		    	    			    		    			if (array_key_exists ( "icon", $this->arrayResult )) {
    			$this->icon = $arrayResult['icon'];
    			}
    		    	    			    		    			if (array_key_exists ( "itemId", $this->arrayResult )) {
    			$this->itemId = $arrayResult['itemId'];
    			}
    		    	    			    		    			if (array_key_exists ( "key", $this->arrayResult )) {
    			$this->key = $arrayResult['key'];
    			}
    		    	    			    		    			if (array_key_exists ( "marketingScene", $this->arrayResult )) {
    			$this->marketingScene = $arrayResult['marketingScene'];
    			}
    		    	    			    		    			if (array_key_exists ( "maxPrice", $this->arrayResult )) {
    			$this->maxPrice = $arrayResult['maxPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "maxQuantity", $this->arrayResult )) {
    			$this->maxQuantity = $arrayResult['maxQuantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "message", $this->arrayResult )) {
    			$this->message = $arrayResult['message'];
    			}
    		    	    			    		    			if (array_key_exists ( "miniPrice", $this->arrayResult )) {
    			$this->miniPrice = $arrayResult['miniPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "miniQuantity", $this->arrayResult )) {
    			$this->miniQuantity = $arrayResult['miniQuantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "name", $this->arrayResult )) {
    			$this->name = $arrayResult['name'];
    			}
    		    	    			    		    			if (array_key_exists ( "offerId", $this->arrayResult )) {
    			$this->offerId = $arrayResult['offerId'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderCargoMaxPrice", $this->arrayResult )) {
    			$this->orderCargoMaxPrice = $arrayResult['orderCargoMaxPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderCargoMinPrice", $this->arrayResult )) {
    			$this->orderCargoMinPrice = $arrayResult['orderCargoMinPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "orderSourceType", $this->arrayResult )) {
    			$this->orderSourceType = $arrayResult['orderSourceType'];
    			}
    		    	    			    		    			if (array_key_exists ( "priceFactor", $this->arrayResult )) {
    			$this->priceFactor = $arrayResult['priceFactor'];
    			}
    		    	    			    		    			if (array_key_exists ( "productDesc", $this->arrayResult )) {
    			$this->productDesc = $arrayResult['productDesc'];
    			}
    		    	    			    		    			if (array_key_exists ( "promotion", $this->arrayResult )) {
    			$this->promotion = $arrayResult['promotion'];
    			}
    		    	    			    		    			if (array_key_exists ( "quantity", $this->arrayResult )) {
    			$this->quantity = $arrayResult['quantity'];
    			}
    		    	    			    		    			if (array_key_exists ( "resultCode", $this->arrayResult )) {
    			$this->resultCode = $arrayResult['resultCode'];
    			}
    		    	    			    		    			if (array_key_exists ( "skuId", $this->arrayResult )) {
    			$this->skuId = $arrayResult['skuId'];
    			}
    		    	    			    		    			if (array_key_exists ( "specId", $this->arrayResult )) {
    			$this->specId = $arrayResult['specId'];
    			}
    		    	    			    		    			if (array_key_exists ( "summImage", $this->arrayResult )) {
    			$this->summImage = $arrayResult['summImage'];
    			}
    		    	    			    		    			if (array_key_exists ( "supplierName", $this->arrayResult )) {
    			$this->supplierName = $arrayResult['supplierName'];
    			}
    		    	    			    		    			if (array_key_exists ( "supplyMemberId", $this->arrayResult )) {
    			$this->supplyMemberId = $arrayResult['supplyMemberId'];
    			}
    		    	    			    		    			if (array_key_exists ( "taoOfferId", $this->arrayResult )) {
    			$this->taoOfferId = $arrayResult['taoOfferId'];
    			}
    		    	    			    		    			if (array_key_exists ( "unit", $this->arrayResult )) {
    			$this->unit = $arrayResult['unit'];
    			}
    		    	    			    		    			if (array_key_exists ( "unitPrice", $this->arrayResult )) {
    			$this->unitPrice = $arrayResult['unitPrice'];
    			}
    		    	    			    		    			if (array_key_exists ( "url", $this->arrayResult )) {
    			$this->url = $arrayResult['url'];
    			}
    		    	    			    		    			if (array_key_exists ( "isVirtual", $this->arrayResult )) {
    			$this->isVirtual = $arrayResult['isVirtual'];
    			}
    		    	    			    		    			if (array_key_exists ( "warehouse", $this->arrayResult )) {
    			$this->warehouse = $arrayResult['warehouse'];
    			}
    		    	    			    		    			if (array_key_exists ( "weight", $this->arrayResult )) {
    			$this->weight = $arrayResult['weight'];
    			}
    		    	    			    		    		if (array_key_exists ( "subPayInfors", $this->arrayResult )) {
    		$subPayInforsResult=$arrayResult['subPayInfors'];
    			$this->subPayInfors = AlibabatradeSubPayInfo();
    			$this->subPayInfors->$this->setStdResult ( $subPayInforsResult);
    		}
    		    	    			    		    		if (array_key_exists ( "specInfo", $this->arrayResult )) {
    		$specInfoResult=$arrayResult['specInfo'];
    			$this->specInfo = AlibabatradespecInfo();
    			$this->specInfo->$this->setStdResult ( $specInfoResult);
    		}
    		    	    			    		    		if (array_key_exists ( "images", $this->arrayResult )) {
    		$imagesResult=$arrayResult['images'];
    			$this->images = AlibabatradeCargoImage();
    			$this->images->$this->setStdResult ( $imagesResult);
    		}
    		    	    		}
 
   
}
?>