<?php
class RequestPolicy {
	
	var $requestSendTimestamp;
	var $useHttps = false;
	var $requestProtocol = DataProtocol::param2;
	var $responseProtocol = DataProtocol::param2;
	var $responseCompress = true;
	var $requestCompressThreshold = - 1;
	var $timeout = 5000;
	var $httpMethod = "POST";
	var $queryStringCharset = "GB18030";
	var $contentCharset = "UTF-8";
	var $useSignture = true;
	var $needAuthorization = false;
	var $accessPrivateApi = false;
	var $defaultApiVersion = 1;
	
}
?>