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
class ComAlibabaTrade {

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


                                                        
            
           
        public function alibabaTradeGetSellerOrderList(AlibabaTradeGetSellerOrderListParam $param,   $accessToken ,  AlibabaTradeGetSellerOrderListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.getSellerOrderList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeGeneralCreateOrder(AlibabaTradeGeneralCreateOrderParam $param,   $accessToken ,  AlibabaTradeGeneralCreateOrderResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.general.CreateOrder", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeGeneralCreateOrderAlipay(AlibabaTradeGeneralCreateOrderAlipayParam $param,   $accessToken ,  AlibabaTradeGeneralCreateOrderAlipayResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.general.create.order.Alipay", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeGetBuyerView(AlibabaTradeGetBuyerViewParam $param,   $accessToken ,  AlibabaTradeGetBuyerViewResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.get.buyerView", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                    			                        
            
           
        public function alibabaAssuranceHtmlGet(  $accessToken ,  AlibabaAssuranceHtmlGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.assurance.html.get", 1);
			$request->apiId = $apiId;
                
                        
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeHtmlGet(AlibabaTradeHtmlGetParam $param,   $accessToken ,  AlibabaTradeHtmlGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.html.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeRefundOpAgreeReturnGoods(AlibabaTradeRefundOpAgreeReturnGoodsParam $param,   $accessToken ,  AlibabaTradeRefundOpAgreeReturnGoodsResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.refund.OpAgreeReturnGoods", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeGetSellerView(AlibabaTradeGetSellerViewParam $param,   $accessToken ,  AlibabaTradeGetSellerViewResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.get.sellerView", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeCancel(AlibabaTradeCancelParam $param,   $accessToken ,  AlibabaTradeCancelResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.cancel", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeQuotationOrderCreate(AlibabaTradeQuotationOrderCreateParam $param,   $accessToken ,  AlibabaTradeQuotationOrderCreateResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.quotationOrder.create", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeRefund(AlibabaTradeRefundParam $param,   $accessToken ,  AlibabaTradeRefundResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.refund.", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaInvoiceGet(AlibabaInvoiceGetParam $param,   $accessToken ,  AlibabaInvoiceGetResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.invoice.get", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeGeneralPreorder(AlibabaTradeGeneralPreorderParam $param,   $accessToken ,  AlibabaTradeGeneralPreorderResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.general.preorder", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaPaymentOrderBankCreate(AlibabaPaymentOrderBankCreateParam $param,   $accessToken ,  AlibabaPaymentOrderBankCreateResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.payment.order.bank.create", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeGetBuyerOrderList(AlibabaTradeGetBuyerOrderListParam $param,   $accessToken ,  AlibabaTradeGetBuyerOrderListResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.getBuyerOrderList", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
                                                        
            
           
        public function alibabaTradeSendGoods(AlibabaTradeSendGoodsParam $param,   $accessToken ,  AlibabaTradeSendGoodsResult $resultDefinition) {
            $reqPolicy = new RequestPolicy();
            $reqPolicy->httpMethod="POST";
            $reqPolicy->needAuthorization=true;
            $reqPolicy->requestSendTimestamp=false;
            $reqPolicy->useHttps=false;
            $reqPolicy->useSignture=true;
            $reqPolicy->accessPrivateApi=false;
           
            $request = new APIRequest ();
			$apiId = new APIId ("com.alibaba.trade", "alibaba.trade.sendGoods", 1);
			$request->apiId = $apiId;
                
            $request->requestEntity=$param;            
            $request->accessToken=$accessToken;            
            $this->getAPIClient()->send($request, $resultDefinition,
						$reqPolicy);
        }
           
}
?>