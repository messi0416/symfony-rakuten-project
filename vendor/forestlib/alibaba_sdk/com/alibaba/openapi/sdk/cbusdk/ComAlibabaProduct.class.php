<?php

include_once ('com/alibaba/openapi/client/APIId.class.php');
include_once ('com/alibaba/openapi/client/APIRequest.class.php');
include_once ('com/alibaba/openapi/client/APIResponse.class.php');
include_once ('com/alibaba/openapi/client/SyncAPIClient.class.php');
include_once ('com/alibaba/openapi/client/entity/AuthorizationToken.class.php');
include_once ('com/alibaba/openapi/client/entity/ParentResult.class.php');
include_once ('com/alibaba/openapi/client/entity/ResponseStatus.class.php');
include_once ('com/alibaba/openapi/client/entity/ResponseWrapper.class.php');
include_once ('com/alibaba/openapi/client/policy/ClientPolicy.class.php');
include_once ('com/alibaba/openapi/client/policy/DataProtocol.class.php');
include_once ('com/alibaba/openapi/client/policy/RequestPolicy.class.php');

include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetSellerOrderListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGeneralCreateOrderParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGeneralCreateOrderAlipayParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetBuyerViewParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaAssuranceHtmlGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeHtmlGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeRefundOpAgreeReturnGoodsParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetSellerViewParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeCancelParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeQuotationOrderCreateParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeRefundParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaInvoiceGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGeneralPreorderParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPaymentOrderBankCreateParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetBuyerOrderListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeSendGoodsParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaAgentProductGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductRepostParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductModifyStockParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductExpireParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumModifyParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductIsModifiableParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupGetSwitchParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupSetSwitchParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductTokenlessGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductTbNicknameToUserIdParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsFreightTemplateAddParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsFreightTemplateGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductEditParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumDeleteParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankPhotoDeleteParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankPhotoGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupDeleteParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaCategoryAttributeGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaCategoryGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankPhotoAddParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductDeleteParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumAddParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductAddParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupAddParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMyFreightTemplateUpdateParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMyFreightTemplateCreateParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMySendGoodsAddressListGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMyFreightTemplateListGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetLogisticsTraceInfoBuyerViewParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetLogisticsTraceInfoSellerViewParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderReturnGoodsParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderCancelParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdSolutionGetListParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderAddParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderGetParam.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetSellerOrderListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGeneralCreateOrderResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGeneralCreateOrderAlipayResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetBuyerViewResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaAssuranceHtmlGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeHtmlGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeRefundOpAgreeReturnGoodsResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetSellerViewResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeCancelResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeQuotationOrderCreateResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeRefundResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaInvoiceGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGeneralPreorderResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPaymentOrderBankCreateResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetBuyerOrderListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeSendGoodsResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaAgentProductGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductRepostResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductModifyStockResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductExpireResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumModifyResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductIsModifiableResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupGetSwitchResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupSetSwitchResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductTokenlessGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductTbNicknameToUserIdResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsFreightTemplateAddResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsFreightTemplateGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductEditResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumDeleteResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankPhotoDeleteResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankPhotoGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupDeleteResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaCategoryAttributeGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaCategoryGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankPhotoAddResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductDeleteResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaPhotobankAlbumAddResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductAddResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaProductGroupAddResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMyFreightTemplateUpdateResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMyFreightTemplateCreateResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMySendGoodsAddressListGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsMyFreightTemplateListGetResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetLogisticsTraceInfoBuyerViewResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaTradeGetLogisticsTraceInfoSellerViewResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderReturnGoodsResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderCancelResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdSolutionGetListResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderAddResult.class.php');
include_once ('com/alibaba/openapi/sdk/cbusdk/param/AlibabaLogisticsInternationalexpressWtdOrderGetResult.class.php');

/**
 * API调用的入口
 */
class ComAlibabaProduct {

    private $serverHost = "gw.open.1688.com";
	private $httpPort = 80;
	private $httpsPort = 443;
	private $appKey;
	private $secKey;
	private $syncAPIClient;
	
	public function setServerHost($serverHost) {
		$this->serverHost = $serverHost;
	}
	public function setHttpPort($httpPort) {
		$this->httpPort = $httpPort;
	}
	public function setHttpsPort($httpsPort) {
		$this->httpsPort = $httpsPort;
	}
	public function setAppKey($appKey) {
		$this->appKey = $appKey;
	}
	public function setSecKey($secKey) {
		$this->secKey = $secKey;
	}
	public function initClient() {
		$clientPolicy = new ClientPolicy ();
		$clientPolicy->appKey = $this->appKey;
		$clientPolicy->secKey = $this->secKey;
		$clientPolicy->httpPort = $this->httpPort;
		$clientPolicy->httpsPort = $this->httpsPort;
		$clientPolicy->serverHost = $this->serverHost;
		
		$this->syncAPIClient = new SyncAPIClient ( $clientPolicy );
	}
	
	public function getAPIClient() {
		if ($this->syncAPIClient == null) {
			$this->initClient ();
		}
		return $this->syncAPIClient;
	}

	/**
	 * 根据授权码换取授权令牌
	 * 
	 * @param code 授权码
	 * @return 授权令牌
	 */
	public function getToken($code) {
		$reqPolicy = new RequestPolicy();
		$reqPolicy->httpMethod="POST";
        $reqPolicy->needAuthorization=false;
        $reqPolicy->requestSendTimestamp=true;
        $reqPolicy->useHttps=true;
		$reqPolicy->requestProtocol=DataProtocol::param2;
           
        $request = new APIRequest ();
        $request->addtionalParams["code"]=$code;
        $request->addtionalParams["grant_type"]="authorization_code";
        $request->addtionalParams["need_refresh_token"]=true;
        $request->addtionalParams["client_id"]=$this->appKey;
        $request->addtionalParams["client_secret"]=$this->secKey;
        $request->addtionalParams["redirect_uri"]="default";
		$apiId = new APIId ("system.oauth2", "getToken", $reqPolicy->defaultApiVersion);
		$request->apiId = $apiId;

		$resultDefinition = new AuthorizationToken();
        $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
		return $resultDefinition;
	}
	
	
	/**
	 * 刷新token
	 * 
	 * @param refreshToken refresh 令牌
	 * @return 授权令牌
	 */
	public function refreshToken($refreshToken) {
		$reqPolicy = new RequestPolicy();
		$reqPolicy->httpMethod="POST";
        $reqPolicy->needAuthorization=false;
        $reqPolicy->requestSendTimestamp=true;
        $reqPolicy->useHttps=true;
		$reqPolicy->requestProtocol=DataProtocol::param2;
           
        $request = new APIRequest ();
        $request->addtionalParams["refreshToken"]=$refreshToken;
        $request->addtionalParams["grant_type"]="refresh_token";
        $request->addtionalParams["client_id"]=$this->appKey;
        $request->addtionalParams["client_secret"]=$this->secKey;
		$apiId = new APIId ("system.oauth2", "getToken", $reqPolicy->defaultApiVersion);
		$request->apiId = $apiId;

		$resultDefinition = new AuthorizationToken();
        $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
		return $resultDefinition;
	}


                                                        
            
           
        public function alibabaAgentProductGet(AlibabaAgentProductGetParam $param,   $accessToken ,  AlibabaAgentProductGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.agent.product.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductRepost(AlibabaProductRepostParam $param,   $accessToken ,  AlibabaProductRepostResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.repost", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductModifyStock(AlibabaProductModifyStockParam $param,   $accessToken ,  AlibabaProductModifyStockResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.modifyStock", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductExpire(AlibabaProductExpireParam $param,   $accessToken ,  AlibabaProductExpireResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.expire", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankAlbumModify(AlibabaPhotobankAlbumModifyParam $param,   $accessToken ,  AlibabaPhotobankAlbumModifyResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.album.modify", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductIsModifiable(AlibabaProductIsModifiableParam $param,   $accessToken ,  AlibabaProductIsModifiableResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.isModifiable", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGroupGetSwitch(AlibabaProductGroupGetSwitchParam $param,   $accessToken ,  AlibabaProductGroupGetSwitchResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.group.getSwitch", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGroupSetSwitch(AlibabaProductGroupSetSwitchParam $param,   $accessToken ,  AlibabaProductGroupSetSwitchResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.group.setSwitch", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductTokenlessGet(AlibabaProductTokenlessGetParam $param,   $accessToken ,  AlibabaProductTokenlessGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.tokenless.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductTbNicknameToUserId(AlibabaProductTbNicknameToUserIdParam $param,   $accessToken ,  AlibabaProductTbNicknameToUserIdResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.tbNicknameToUserId", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaLogisticsFreightTemplateAdd(AlibabaLogisticsFreightTemplateAddParam $param,   $accessToken ,  AlibabaLogisticsFreightTemplateAddResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.logistics.freightTemplate.add", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaLogisticsFreightTemplateGetList(AlibabaLogisticsFreightTemplateGetListParam $param,   $accessToken ,  AlibabaLogisticsFreightTemplateGetListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.logistics.freightTemplate.getList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductEdit(AlibabaProductEditParam $param,   $accessToken ,  AlibabaProductEditResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.edit", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGetList(AlibabaProductGetListParam $param,   $accessToken ,  AlibabaProductGetListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.getList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankAlbumDelete(AlibabaPhotobankAlbumDeleteParam $param,   $accessToken ,  AlibabaPhotobankAlbumDeleteResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.album.delete", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankPhotoDelete(AlibabaPhotobankPhotoDeleteParam $param,   $accessToken ,  AlibabaPhotobankPhotoDeleteResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.photo.delete", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankAlbumGetList(AlibabaPhotobankAlbumGetListParam $param,   $accessToken ,  AlibabaPhotobankAlbumGetListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.album.getList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankPhotoGetList(AlibabaPhotobankPhotoGetListParam $param,   $accessToken ,  AlibabaPhotobankPhotoGetListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.photo.getList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGroupGetList(AlibabaProductGroupGetListParam $param,   $accessToken ,  AlibabaProductGroupGetListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.group.getList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGroupGet(AlibabaProductGroupGetParam $param,   $accessToken ,  AlibabaProductGroupGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.group.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGroupDelete(AlibabaProductGroupDeleteParam $param,   $accessToken ,  AlibabaProductGroupDeleteResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.group.delete", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaCategoryAttributeGet(AlibabaCategoryAttributeGetParam $param,   $accessToken ,  AlibabaCategoryAttributeGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.category.attribute.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGet(AlibabaProductGetParam $param,   $accessToken ,  AlibabaProductGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                            
            
           
        public function alibabaCategoryGet(AlibabaCategoryGetParam $param ,  AlibabaCategoryGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=false;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=false;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.category.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
                        
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankPhotoAdd(AlibabaPhotobankPhotoAddParam $param,   $accessToken ,  AlibabaPhotobankPhotoAddResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=false;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.photo.add", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductDelete(AlibabaProductDeleteParam $param,   $accessToken ,  AlibabaProductDeleteResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.delete", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPhotobankAlbumAdd(AlibabaPhotobankAlbumAddParam $param,   $accessToken ,  AlibabaPhotobankAlbumAddResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.photobank.album.add", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductAdd(AlibabaProductAddParam $param,   $accessToken ,  AlibabaProductAddResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.add", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaProductGroupAdd(AlibabaProductGroupAddParam $param,   $accessToken ,  AlibabaProductGroupAddResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.product", "alibaba.product.group.add", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
           
}
?>