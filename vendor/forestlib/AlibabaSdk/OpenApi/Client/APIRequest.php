<?php
namespace forestlib\AlibabaSdk\OpenApi\Client;

use forestlib\AlibabaSdk\OpenApi\Client\Entity\AuthorizationToken;

class APIRequest
{
  var $apiId;
  var $additionalParams = array();
  var $requestEntity;
  var $attachments = array();
  var $authCodeKey;
  var $accessToken;
  var $authToken;
}
