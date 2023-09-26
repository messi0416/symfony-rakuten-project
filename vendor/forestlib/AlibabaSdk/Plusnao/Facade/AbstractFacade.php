<?php
namespace forestlib\AlibabaSdk\Plusnao\Facade;

use forestlib\AlibabaSdk\OpenApi\Client\APIId;
use forestlib\AlibabaSdk\OpenApi\Client\APIRequest;
use forestlib\AlibabaSdk\OpenApi\Client\Entity\AuthorizationToken;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\ClientPolicy;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\DataProtocol;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\RequestPolicy;
use forestlib\AlibabaSdk\OpenApi\Client\SyncAPIClient;

class AbstractFacade
{
  protected $serverHost = "gw.open.1688.com";
  protected $httpPort = 80;
  protected $httpsPort = 443;
  protected $appKey;
  protected $secKey;
  protected $syncAPIClient;

  /** @var AuthorizationToken */
  protected $auth;

  protected $hooks = []; // after_call_api のみ実装

  public function __construct($config = [])
  {
    if (array_key_exists('appKey', $config)) {
      $this->setAppKey($config['appKey']);
    }
    if (array_key_exists('secKey', $config)) {
      $this->setSecKey($config['secKey']);
    }
    if (array_key_exists('serverHost', $config)) {
      $this->setServerHost($config['serverHost']);
    }

    $this->auth = new AuthorizationToken();
    if (array_key_exists('accessToken', $config)) {
      $this->auth->setAccessToken($config['accessToken']);
    }

    if (array_key_exists('refreshToken', $config)) {
      $this->auth->setRefreshToken($config['refreshToken']);
    }

    if (array_key_exists('hooks', $config)) {
      $this->setHooks($config['hooks']);
    }

  }

  public function setServerHost($serverHost)
  {
    $this->serverHost = $serverHost;
  }

  public function setHttpPort($httpPort)
  {
    $this->httpPort = $httpPort;
  }

  public function setHttpsPort($httpsPort)
  {
    $this->httpsPort = $httpsPort;
  }

  public function setAppKey($appKey)
  {
    $this->appKey = $appKey;
  }

  public function setSecKey($secKey)
  {
    $this->secKey = $secKey;
  }

  public function initClient()
  {
    $clientPolicy = new ClientPolicy();
    $clientPolicy->appKey = $this->appKey;
    $clientPolicy->secKey = $this->secKey;
    $clientPolicy->httpPort = $this->httpPort;
    $clientPolicy->httpsPort = $this->httpsPort;
    $clientPolicy->serverHost = $this->serverHost;

    $this->syncAPIClient = new SyncAPIClient($clientPolicy);
  }

  /**
   * @return SyncAPIClient
   */
  public function getAPIClient()
  {
    if ($this->syncAPIClient == null) {
      $this->initClient();
    }
    return $this->syncAPIClient;
  }

  /**
   * @param string $code
   * @return AuthorizationToken
   * @throws
   */
  public function getToken($code)
  {
    $reqPolicy = new RequestPolicy();
    $reqPolicy->httpMethod = "POST";
    $reqPolicy->needAuthorization = false;
    $reqPolicy->requestSendTimestamp = true;
    $reqPolicy->useHttps = true;
    $reqPolicy->requestProtocol = DataProtocol::param2;
    $reqPolicy->useSignature = false;

    $request = new APIRequest();
    $request->additionalParams["code"] = $code;
    $request->additionalParams["grant_type"] = "authorization_code";
    $request->additionalParams["need_refresh_token"] = 'true';
    $request->additionalParams["client_id"] = $this->appKey;
    $request->additionalParams["client_secret"] = $this->secKey;
    $request->additionalParams["redirect_uri"] = "https://forest.plusnao.co.jp/pub/alibaba/callback/default";

    $apiId = new APIId("system.oauth2", "getToken", $reqPolicy->defaultApiVersion);
    $request->apiId = $apiId;

    $resultDefinition = new AuthorizationToken();
    $this->callApi($request, $resultDefinition, $reqPolicy);
    return $resultDefinition;
  }

  /**
   * @param string $refreshToken
   * @return AuthorizationToken
   * @throws
   */
  public function refreshToken($refreshToken)
  {
    $reqPolicy = new RequestPolicy();
    $reqPolicy->httpMethod = "POST";
    $reqPolicy->needAuthorization = false;
    $reqPolicy->requestSendTimestamp = true;
    $reqPolicy->useHttps = true;
    $reqPolicy->requestProtocol = DataProtocol::param2;
    $reqPolicy->useSignature = false;

    $request = new APIRequest();
    $request->additionalParams["refreshToken"] = $refreshToken;
    $request->additionalParams["grant_type"] = "refresh_token";
    $request->additionalParams["client_id"] = $this->appKey;
    $request->additionalParams["client_secret"] = $this->secKey;
    $apiId = new APIId ("system.oauth2", "getToken", $reqPolicy->defaultApiVersion);
    $request->apiId = $apiId;

    $resultDefinition = new AuthorizationToken();
    $this->callApi($request, $resultDefinition, $reqPolicy);
    return $resultDefinition;
  }


  /**
   * フック処理登録
   * @param array $hooks
   */
  public function setHooks($hooks)
  {
    $this->hooks = $hooks;
  }

  /**
   * リクエスト送信 ＆ フック処理
   * @param APIRequest $request
   * @param $result
   * @param RequestPolicy $reqPolicy
   * @throws
   */
  public function callApi(APIRequest $request, $result, RequestPolicy $reqPolicy)
  {
    $this->getAPIClient()->send($request, $result, $reqPolicy);

    if (isset($this->hooks['after_call_api'])) {
      foreach($this->hooks['after_call_api'] as $hook) {
        call_user_func($hook);
      }
    }
  }

}
