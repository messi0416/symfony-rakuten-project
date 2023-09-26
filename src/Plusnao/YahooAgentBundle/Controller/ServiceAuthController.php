<?php

namespace Plusnao\YahooAgentBundle\Controller;

use Doctrine\ORM\EntityRepository;
use MiscBundle\Entity\TbYahooAgentApiAuth;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use YConnect\Constant\OIDConnectDisplay;
use YConnect\Constant\OIDConnectPrompt;
use YConnect\Constant\ResponseType;


class ServiceAuthController extends BaseController
{
  /**
   * Yahoo API 認証処理
   */
  public function yahooAuthAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');

    /** @var \MiscBundle\Util\WebAccessUtil $webUtil */
    $webAccessUtil = $this->get('misc.util.web_access');

    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->get('misc.util.string');

    $redirectUri = $this->generateUrl('yahoo_service_auth_yahoo_callback', [ 'shopCode' => $this->getShopAccount()->getShopCode() ], UrlGeneratorInterface::ABSOLUTE_URL);

    // リクエストとコールバック間の検証用のランダムな文字列
    $state = $stringUtil->makeRandomString(64);

    // リプレイアタック対策のランダムな文字列
    $nonce = $stringUtil->makeRandomString(64);

    $scopes = array(
      // OIDConnectScope::OPENID,
      // OIDConnectScope::PROFILE,
      // OIDConnectScope::EMAIL,
      // OIDConnectScope::ADDRESS
    );

    // 認証要求情報 保存
    $auth = new TbYahooAgentApiAuth();
    $auth->setSymfonyUsersId($this->getShopAccount()->getId());
    $auth->setShopCode($this->getShopAccount()->getShopCode());
    $auth->setState($state);
    $auth->setNonce($nonce);
    $auth->setScopes(implode(',', $scopes));
    $auth->setRedirectUrl($redirectUri);

    /** @var \Doctrine\ORM\EntityManager $em */
    $em = $this->getDoctrine()->getManager();
    $em->persist($auth);
    $em->flush();

    // YConnectクライアントインスタンス生成
    $client = $webAccessUtil->getYahooApiClient();

    // Authorizationエンドポイントにリクエスト
    // この中でリダイレクトヘッダが出力される。できれば、ラップしてSymfonyのレスポンスを返すべき
    $client->requestAuth(
        $redirectUri
      , $state
      , $nonce
      // , ResponseType::CODE_IDTOKEN
      , ResponseType::CODE
      , $scopes
      , OIDConnectDisplay::DEFAULT_DISPLAY
      , [ OIDConnectPrompt::DEFAULT_PROMPT ]
    );
    exit;

    // return new JsonResponse([]);
  }

  /**
   * Yahoo API 認証コールバック
   */
  public function yahooAuthCallbackAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');

    try {
      /** @var EntityRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbYahooAgentApiAuth');
      /** @var TbYahooAgentApiAuth $auth */
      $auth = $repo->findOneBy([ 'state' => $request->get('state') ], [ 'created' => 'desc' ]);
      if (!$auth) {
        throw new RuntimeException('can not find yahoo pai auth by state : ' . $request->get('state'));
      }

      // URL保存
      $auth->setRedirectedUrl($request->getRequestUri());

      /** @var \MiscBundle\Util\WebAccessUtil $webUtil */
      $webAccessUtil = $this->get('misc.util.web_access');

      // 戻りURLパラメータ 検証 および 認可コード(authorization code) 要求
      $client = $webAccessUtil->getYahooApiClient();
      $authCode = $client->getAuthorizationCode($request->get('state'));

      $auth->setAuthCode($authCode);
      $logger->info('auth code: ' . $authCode);

      $redirectUrl = $this->generateUrl('yahoo_service_auth_yahoo_callback', [ 'shopCode' => $this->getShopAccount()->getShopCode() ], UrlGeneratorInterface::ABSOLUTE_URL);
      $logger->info('redirect url' . $redirectUrl);

      // アクセストークン および リフレッシュトークン取得
      // Tokenエンドポイントにリクエスト
      $client->requestAccessToken(
          $redirectUrl
        , $authCode
      );

      // アクセストークン、リフレッシュトークン
      $auth->setAccessToken($client->getAccessToken());
      $auth->setRefreshToken($client->getRefreshToken());
      $auth->setExpirationWithSecondsTerm($client->getAccessTokenExpiration());

      // 認証情報 保存
      $this->getDoctrine()->getManager()->flush();

      $this->setFlash('success', 'Yahoo APIの認証に成功しました。APIへのアクセスが可能です。');

    } catch (\Exception $e) {
      $logger->error('Yahoo API 認証エラー:' . $this->getShopAccount()->getShopCode() . ':' . $e->getMessage());

      $this->setFlash('danger', 'Yahoo APIの認証に失敗しました。');
    }

    return $this->redirectToRoute('yahoo_product_list', ['shopCode' => $this->getShopAccount()->getShopCode()]);
  }

}
