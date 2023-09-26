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

include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyGetParam.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyPostParam.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyGetResult.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyPostResult.class.php');

class ExampleFacade {
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

  /**
   * @return SyncAPIClient
   */
	public function getAPIClient() {
		if ($this->syncAPIClient == null) {
			$this->initClient ();
		}
		return $this->syncAPIClient;
	}
	
	/**
	 * 鏍规嵁鎺堟潈鐮佹崲鍙栨巿鏉冧护鐗�
	 *
	 * @param
	 *        	code 鎺堟潈鐮�
	 * @return 鎺堟潈浠ょ墝
	 */
	public function getToken($code) {
		$reqPolicy = new RequestPolicy ();
		$reqPolicy->httpMethod = "POST";
		$reqPolicy->needAuthorization = false;
		$reqPolicy->requestSendTimestamp = true;
		$reqPolicy->useHttps = true;
		$reqPolicy->requestProtocol = DataProtocol::param2;
		
		$request = new APIRequest ();
		$request->addtionalParams ["code"] = $code;
		$request->addtionalParams ["grant_type"] = "authorization_code";
		$request->addtionalParams ["need_refresh_token"] = true;
		$request->addtionalParams ["client_id"] = $this->appKey;
		$request->addtionalParams ["client_secret"] = $this->secKey;
		$request->addtionalParams ["redirect_uri"] = "default";
		$apiId = new APIId ( "system.oauth2", "getToken", $reqPolicy->defaultApiVersion );
		$request->apiId = $apiId;
		
		$resultDefinition = new AuthorizationToken ();
		$this->getAPIClient ()->send ( $request, $resultDefinition, $reqPolicy );
		return $resultDefinition;
	}
	
	/**
	 * 鍒锋柊token
	 *
	 * @param
	 *        	refreshToken refresh 浠ょ墝
	 * @return 鎺堟潈浠ょ墝
	 */
	public function refreshToken($refreshToken) {
		$reqPolicy = new RequestPolicy ();
		$reqPolicy->httpMethod = "POST";
		$reqPolicy->needAuthorization = false;
		$reqPolicy->requestSendTimestamp = true;
		$reqPolicy->useHttps = true;
		$reqPolicy->requestProtocol = DataProtocol::param2;
		
		$request = new APIRequest ();
		$request->addtionalParams ["refreshToken"] = $refreshToken;
		$request->addtionalParams ["grant_type"] = "refresh_token";
		$request->addtionalParams ["client_id"] = $this->appKey;
		$request->addtionalParams ["client_secret"] = $this->secKey;
		$apiId = new APIId ( "system.oauth2", "getToken", $reqPolicy->defaultApiVersion );
		$request->apiId = $apiId;
		
		$resultDefinition = new AuthorizationToken ();
		$this->getAPIClient ()->send ( $request, $resultDefinition, $reqPolicy );
		return $resultDefinition;
	}
	public function exampleFamilyGet(ExampleFamilyGetParam $param, ExampleFamilyGetResult $resultDefinition) {
		$reqPolicy = new RequestPolicy ();
		$reqPolicy->httpMethod = "POST";
		$reqPolicy->needAuthorization = false;
		$reqPolicy->requestSendTimestamp = false;
		$reqPolicy->useHttps = false;
		$reqPolicy->useSignture = false;
		$reqPolicy->accessPrivateApi = false;
		
		$request = new APIRequest ();
		$apiId = new APIId ( "api.example", "example.family.get", 1 );
		$request->apiId = $apiId;
		
		$request->requestEntity = $param;
		
		$this->getAPIClient ()->send ( $request, $resultDefinition, $reqPolicy );
	}
	public function exampleFamilyPost(ExampleFamilyPostParam $param, $accessToken, ExampleFamilyPostResult $resultDefinition) {
		$reqPolicy = new RequestPolicy ();
		$reqPolicy->httpMethod = "POST";
		$reqPolicy->needAuthorization = true;
		$reqPolicy->requestSendTimestamp = false;
		$reqPolicy->useHttps = false;
		$reqPolicy->useSignture = true;
		$reqPolicy->accessPrivateApi = false;
		
		$request = new APIRequest ();
		$apiId = new APIId ( "api.example", "example.family.post", 1 );
		$request->apiId = $apiId;
		
		$request->requestEntity = $param;
		$request->accessToken = $accessToken;
		$this->getAPIClient ()->send ( $request, $resultDefinition, $reqPolicy );
	}
}
?>
