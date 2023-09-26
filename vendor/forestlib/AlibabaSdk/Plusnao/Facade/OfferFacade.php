<?php
namespace forestlib\AlibabaSdk\Plusnao\Facade;

use forestlib\AlibabaSdk\OpenApi\Client\APIId;
use forestlib\AlibabaSdk\OpenApi\Client\APIRequest;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\RequestPolicy;
use forestlib\AlibabaSdk\Plusnao\Entity\Offer;
use forestlib\AlibabaSdk\Plusnao\Param\OfferGetParam;
use forestlib\AlibabaSdk\Plusnao\Param\OfferGetResult;
use forestlib\AlibabaSdk\Plusnao\Param\OfferSearchParam;
use forestlib\AlibabaSdk\Plusnao\Param\OfferSearchResult;

class OfferFacade extends AbstractFacade
{
  /**
   * offer 1件取得
   * @param $offerId
   * @param null|array $returnFields
   * @return Offer
   */
  public function getOffer($offerId, $returnFields = null)
  {
    $param = new OfferGetParam();
    $param->setOfferId($offerId);

    if ($returnFields) {
      $param->setReturnFields($returnFields);
    } else {
      $param->setDefaultReturnFields();
    }

    $result = new OfferGetResult();

    $reqPolicy = new RequestPolicy();
    $reqPolicy->httpMethod = 'POST';
    $reqPolicy->needAuthorization = false;
    $reqPolicy->requestSendTimestamp = false;
    $reqPolicy->useHttps = false;
    $reqPolicy->useSignature = true;
    $reqPolicy->accessPrivateApi = false;

    $request = new APIRequest ();
    // $apiId = new APIId('cn.alibaba.open', 'offer.get', 2);
    $apiId = new APIId('cn.alibaba.open', 'offer.get', 1);
    $request->apiId = $apiId;

    $request->requestEntity = $param;

    $this->callApi($request, $result, $reqPolicy);

    $offer = $result->getHydratedOne();
    return $offer;
  }

  /**
   * @param OfferSearchParam $param
   * @return OfferSearchResult
   */
  public function searchOffers($param)
  {
    $result = new OfferSearchResult();

    $param->setDefaultReturnFields();

    $reqPolicy = new RequestPolicy();
    $reqPolicy->httpMethod = 'POST';
    $reqPolicy->needAuthorization = false;
    $reqPolicy->requestSendTimestamp = false;
    $reqPolicy->useHttps = false;
    $reqPolicy->useSignature = true;
    $reqPolicy->accessPrivateApi = false;

    $request = new APIRequest ();
    $apiId = new APIId('cn.alibaba.open', 'offer.search', 1);
    $request->apiId = $apiId;

    $request->requestEntity = $param;

    $this->callApi($request, $result, $reqPolicy);

    return $result;
  }

}

?>
