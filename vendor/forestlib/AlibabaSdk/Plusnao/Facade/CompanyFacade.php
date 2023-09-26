<?php
namespace forestlib\AlibabaSdk\Plusnao\Facade;

use forestlib\AlibabaSdk\OpenApi\Client\APIId;
use forestlib\AlibabaSdk\OpenApi\Client\APIRequest;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\RequestPolicy;
use forestlib\AlibabaSdk\Plusnao\Entity\Company;
use forestlib\AlibabaSdk\Plusnao\Param\CompanyGetParam;
use forestlib\AlibabaSdk\Plusnao\Param\CompanyGetResult;

class CompanyFacade extends AbstractFacade
{
  /**
   * company 1件取得
   * @param String $memberId
   * @return Company
   */
  public function getCompany($memberId)
  {
    $param = new CompanyGetParam();
    $param->setMemberId($memberId);
    $result = new CompanyGetResult();

    $reqPolicy = new RequestPolicy();
    $reqPolicy->httpMethod = 'POST';
    $reqPolicy->needAuthorization = false;
    $reqPolicy->requestSendTimestamp = false;
    $reqPolicy->useHttps = false;
    $reqPolicy->useSignature = true;
    $reqPolicy->accessPrivateApi = false;

    $request = new APIRequest ();
    $apiId = new APIId('cn.alibaba.open', 'company.get', 1);
    $request->apiId = $apiId;

    $request->requestEntity = $param;

    $this->callApi($request, $result, $reqPolicy);

//    var_dump($result->getReturnOne());
//    var_dump($result->getTotal());
//    var_dump($result->isSuccess());

    $company = $result->getHydratedOne();
    return $company;
  }

}

?>
