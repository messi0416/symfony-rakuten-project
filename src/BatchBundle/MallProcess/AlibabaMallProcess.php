<?php

namespace BatchBundle\MallProcess;
use forestlib\AlibabaSdk\Plusnao\Facade\AbstractFacade;
use forestlib\AlibabaSdk\Plusnao\Facade\CompanyFacade;
use forestlib\AlibabaSdk\Plusnao\Facade\OfferFacade;
use forestlib\AlibabaSdk\Plusnao\Facade\ProductFacade;
use forestlib\AlibabaSdk\Plusnao\Param\CompanyGetResult;
use forestlib\AlibabaSdk\Plusnao\Param\OfferSearchParam;
use forestlib\AlibabaSdk\Plusnao\Param\OfferSearchResult;
use MiscBundle\Entity\ProductImages;

/**
 * モール別特殊処理 - Alibaba
 */
class AlibabaMallProcess extends BaseMallProcess
{
  const PAGE_SIZE_MIN_SEARCH_OFFER = 20;
  const PAGE_SIZE_MAX_SEARCH_OFFER = 50; // ドキュメントでは20～200となっているが、実際には50件しか取得できていない？


  /**
   * 仕入れ先URLの不要なクエリパラメータを除去して保存する。
   * https://detail.1688.com/offer/00000000000.html のURLが対象
   */
  public function trimAlibabaSireAddressQueryParameter()
  {
    $db = $this->getDb('main');
    $sql = <<<EOD
      UPDATE
      tb_vendoraddress va
      SET va.sire_adress = REPLACE(
        SUBSTRING(
            SUBSTRING_INDEX(va.sire_adress, '?', 1)
          , CHAR_LENGTH(SUBSTRING_INDEX(va.sire_adress, '?', 1 - 1)) + 1
        )
      , '?', '')
      WHERE va.sire_adress LIKE '%detail.1688.com%?%'
EOD;
    $result = $db->exec($sql);

    return $result;
  }

  /**
   * API接続情報
   */
  public function getApiConfig()
  {
    $apiConfig = $this->getContainer()->getParameter('alibaba_api');
    $config = [
        'appKey' => $apiConfig['app_key']
      , 'secKey' => $apiConfig['sec_key']
      , 'serverHost' => $apiConfig['server_host']

      , 'hooks' => [
          'after_call_api' => [
            [$this, 'increaseApiCallCount']
        ]
      ]
    ];

    return $config;
  }


  /**
   * 仕入れ先URLから offerId を取得
   * @param string $url
   * @return string|null
   */
  public function getOfferIdByUrl($url)
  {
    $result = null; // https://detail.1688.com/offer/528047338173.html
    if (preg_match('|http(?:s)?://detail.1688.com/offer/(\d+)\.html|', $url, $m)) {
      $result = $m[1];
    }

    return $result;
  }

  /**
   * (API) offer 1件取得
   * @param string $offerId
   * @return \forestlib\AlibabaSdk\Plusnao\Entity\Offer
   */
  public function apiGetOffer($offerId)
  {
    // 商品情報取得
    $offerFacade = new OfferFacade($this->getApiConfig());
    $offer = $offerFacade->getOffer($offerId);
    return $offer;
  }


  /**
   * (API) company 1件取得
   * @param string $memberId
   * @return \forestlib\AlibabaSdk\Plusnao\Entity\Company
   */
  public function apiGetCompany($memberId)
  {
    // 商品情報取得 テスト
    $companyFacade = new CompanyFacade($this->getApiConfig());
    $company = $companyFacade->getCompany($memberId);

    return $company;
  }

  /**
   * (API) company Products 1ページ分取得
   * @param string $memberId
   * @param int $page
   * @param int $pageSize
   * @return OfferSearchResult
   */
  public function apiGetCompanyProducts($memberId, $page = 1, $pageSize = self::PAGE_SIZE_MAX_SEARCH_OFFER)
  {
    $config = $this->getApiConfig();

    $param = new OfferSearchParam();
    $param->setMemberId($memberId);
    $param->setPageNo($page);
    $param->setPageSize($pageSize); // 20 ～ 200
    $param->setOrderBy('gmt_create:asc');
    $param->setStatus('published');

    $offerFacade = new OfferFacade($config);
    $offerSearchResult = $offerFacade->searchOffers($param);

    return $offerSearchResult;
  }



  /**
   * (API) product 1件取得
   * @param string $productId
   * @return \forestlib\AlibabaSdk\Plusnao\Entity\Product
   */
  public function apiGetProduct($productId)
  {
    // 商品情報取得
    $productFacade = new ProductFacade($this->getApiConfig());
    $product = $productFacade->getProduct($productId);

    return $product;
  }

  /**
   * API利用回数 取得
   */
  public function getApiCallCount()
  {
    $dbMain = $this->getDb('main');
    $num = $dbMain->query("SELECT num FROM tb_1688_api_call c WHERE c.log_date = CURRENT_DATE")->fetchColumn(0);
    return intval($num);
  }

  /**
   * API利用回数 増加
   * @param int $num
   */
  public function increaseApiCallCount($num = 1)
  {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      INSERT INTO tb_1688_api_call (
          `log_date`
        , `num`
      ) VALUES (
          CURRENT_DATE
        , :num
      )
      ON DUPLICATE KEY UPDATE
        `num` = `num` + VALUES(`num`)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':num', $num);
    $stmt->execute();
  }


}
