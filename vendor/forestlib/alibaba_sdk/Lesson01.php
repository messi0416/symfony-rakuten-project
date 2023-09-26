<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

include_once ('com/alibaba/china/openapi/client/example/ExampleFacade.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyGetParam.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyPostParam.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyGetResult.class.php');
include_once ('com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyPostResult.class.php');

include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('com/alibaba/openapi/client/util/DateUtil.class.php');

$exampleFacade = new ExampleFacade ();
// $exampleFacade->setAppKey ( "Your appKey" );
// $exampleFacade->setSecKey ( "Your appSecret" );
// $exampleFacade->setServerHost ( "server host" );

$exampleFacade->setAppKey('8462834');
$exampleFacade->setSecKey('7szMSnBUwGR');
$exampleFacade->setServerHost('gw.open.1688.com');

//you need change this refresh token when you run this example.
$testRefreshToken ="6291ba7b-8658-4cea-9e45-b880a66e2d11";

try {
	// --------------------------first example starting----------------------------------

  $reqPolicy = new RequestPolicy ();
  $reqPolicy->httpMethod = "POST";
  $reqPolicy->needAuthorization = false;
  $reqPolicy->requestSendTimestamp = false;
  $reqPolicy->useHttps = false;
  $reqPolicy->useSignture = true;
  $reqPolicy->accessPrivateApi = false;

  $request = new APIRequest ();
  $apiId = new APIId ( "cn.alibaba.open", "offer.search", 1 );
  $request->apiId = $apiId;

  $param = new ExampleOfferGetParam();
  $param->setOfferId('536588503946');
  $param->setMemberId('praguefactory');

  $request->requestEntity = $param;
  $exampleFamilyGetResult = new ExampleFamilyGetResult ();

  $exampleFacade->getAPIClient ()->send ( $request, $exampleFamilyGetResult, $reqPolicy );

  $result = $exampleFamilyGetResult->getResult()->stdResult;
  foreach($result->toReturn as $row) {
    var_dump($row);
  }

  // var_dump($exampleFamilyGetResult->getResult());

  throw new \RuntimeException('moge!!');


	$param = new ExampleFamilyGetParam ();
	$param->setFamilyNumber ( 1 );
	$exampleFamilyGetResult = new ExampleFamilyGetResult ();
	
	$exampleFacade->exampleFamilyGet ( $param, $exampleFamilyGetResult );
	$exampleFamily = $exampleFamilyGetResult->getResult ();
	echo "ExampleFamilyGet call get the result, the familyNumber is ";
	echo $exampleFamilyGetResult->getResult ()->getFamilyNumber ();
	echo " and the name of father is ";
	echo $exampleFamilyGetResult->getResult ()->getFather ()->getName ();
	echo ", the birthday of fanther is ";
	echo $exampleFamilyGetResult->getResult ()->getFather ()->getBirthday ();
	echo "<br/>";
	// ----------------------------first example end-------------------------------------

	
	// --------------------------second example starting----------------------------------
	$exampleFamilyPostParam = new ExampleFamilyPostParam ();
	// set the simple parameter
	$exampleFamilyPostParam->setComments ( "SDK Example" );
	
	// set a complex domain as parameter
	$exampleFamily = new ExampleFamily ();
	
	$exampleFamily->setFamilyNumber ( 12 );
	$exampleFather = new ExamplePerson ();
	$exampleFather->setAge ( 31 );
	$exampleFather->setBirthday ( "19780312101010000" );
	$exampleFather->setName ( "John" );
	$exampleFamily->setFather ( $exampleFather );
	$exampleFamilyPostParam->setFamily ( $exampleFamily );
	
	// simulate the feature of upload image.
	$fileContent = file_get_contents ( "example.png" );
	$houseImg = new ByteArray ();
	$houseImg->setBytesValue ( $fileContent );
	$exampleFamilyPostParam->setHouseImg ( $houseImg );
	
	$authorizationToken = $exampleFacade->refreshToken($testRefreshToken);
	echo "refresh token:";
	echo $authorizationToken->getAccessToken();
	echo "<br/>";
	
	$exampleFamilyPostResult = new ExampleFamilyPostResult ();
	$exampleFacade->exampleFamilyPost ( $exampleFamilyPostParam, $authorizationToken->getAccessToken(), $exampleFamilyPostResult );
	echo "ExampleFamilyPost call get the result, the descriptin of result is ";
	echo $exampleFamilyPostResult->getResultDesc ();
	echo "<br/>";
	echo "ExampleFamilyPost call get the result, the father name upset is ";
	echo $exampleFamilyPostResult->getResult ()->getFather ()->getName ();
	// --------------------------second example starting----------------------------------
} catch ( OceanException $ex ) {
	echo "Exception occured with code[";
	echo $ex->getErrorCode ();
	echo "] message [";
	echo $ex->getMessage ();
	echo "].";
}



include_once ('com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('com/alibaba/openapi/client/entity/ByteArray.class.php');
class ExampleOfferGetParam
{
  private $sdkStdResult = array ();

  /**
   */
  public function getOfferId() {
    $tempResult = $this->sdkStdResult ["offerId"];
    return $tempResult;
  }

  public function setOfferId($offerId) {
    $this->sdkStdResult ["offerId"] = $offerId;
  }

  public function getSdkStdResult() {
    return $this->sdkStdResult;
  }

  public function getMemberId()
  {
    $tempResult = $this->sdkStdResult ["memberId"];
    return $tempResult;
  }

  public function setMemberId($memberId)
  {
    $this->sdkStdResult ["memberId"] = $memberId;
  }

}

?>
