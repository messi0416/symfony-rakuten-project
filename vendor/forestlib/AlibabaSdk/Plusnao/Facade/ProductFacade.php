<?php
/**
 * 2017/07/18 認証が必要で、またWEBブラウザでないとAccessTokenがとれない（Cookie？）ので、現在利用せず。試験実装中のまま残す。
 */

namespace forestlib\AlibabaSdk\Plusnao\Facade;

use forestlib\AlibabaSdk\OpenApi\Client\APIId;
use forestlib\AlibabaSdk\OpenApi\Client\APIRequest;
use forestlib\AlibabaSdk\OpenApi\Client\Policy\RequestPolicy;
use forestlib\AlibabaSdk\Plusnao\Entity\Offer;
use forestlib\AlibabaSdk\Plusnao\Entity\Product;
use forestlib\AlibabaSdk\Plusnao\Param\OfferGetParam;
use forestlib\AlibabaSdk\Plusnao\Param\OfferGetResult;
use forestlib\AlibabaSdk\Plusnao\Param\OfferSearchParam;
use forestlib\AlibabaSdk\Plusnao\Param\OfferSearchResult;
use forestlib\AlibabaSdk\Plusnao\Param\ProductGetParam;
use forestlib\AlibabaSdk\Plusnao\Param\ProductGetResult;

class ProductFacade extends AbstractFacade
{

  /**
   * product 1件取得
   * @param $productId
   * @param null|array $returnFields
   * @return Product
   */
  public function getProduct($productId, $returnFields = null)
  {
    $param = new ProductGetParam();
    $param->setProductID($productId);
    $param->setWebSite('1688');
    // $param->setScene('1688');

    if ($returnFields) {
      $param->setReturnFields($returnFields);
    } else {
      $param->setDefaultReturnFields();
    }

    $result = new ProductGetResult();

    $reqPolicy = new RequestPolicy();
    $reqPolicy->httpMethod = 'POST';
    $reqPolicy->needAuthorization = true;
    $reqPolicy->requestSendTimestamp = false;
    $reqPolicy->useHttps = false;
    $reqPolicy->useSignature = true;
    $reqPolicy->accessPrivateApi = false;

    $request = new APIRequest ();
    $request->accessToken = $this->auth->getAccessToken(); // TODO accessToken無効時のrefreshTokenによるリトライ処理
    // $apiId = new APIId('com.alibaba.product', 'alibaba.product.get', 1);
    $apiId = new APIId('com.alibaba.product', 'alibaba.agent.product.get', 1);

    $request->apiId = $apiId;

    $request->requestEntity = $param;

    $this->callApi($request, $result, $reqPolicy);

    var_dump('moge!!');
    var_dump($result->getReturnOne());
    var_dump($result->getTotal());
    var_dump($result->isSuccess());

    throw new \RuntimeException('DEBUG STOP!!');

    $product = $result->getHydratedOne();
    return $product;
  }
//
//  /**
//   * @param OfferSearchParam $param
//   * @return OfferSearchResult
//   */
//  public function searchOffers($param)
//  {
//    $result = new OfferSearchResult();
//
//    $param->setDefaultReturnFields();
//
//    $reqPolicy = new RequestPolicy();
//    $reqPolicy->httpMethod = 'POST';
//    $reqPolicy->needAuthorization = false;
//    $reqPolicy->requestSendTimestamp = false;
//    $reqPolicy->useHttps = false;
//    $reqPolicy->useSignature = true;
//    $reqPolicy->accessPrivateApi = false;
//
//    $request = new APIRequest ();
//    $apiId = new APIId('cn.alibaba.open', 'offer.search', 1);
//    $request->apiId = $apiId;
//
//    $request->requestEntity = $param;
//
//    $this->callApi($request, $result, $reqPolicy);
//
//    return $result;
//  }

}

?>
