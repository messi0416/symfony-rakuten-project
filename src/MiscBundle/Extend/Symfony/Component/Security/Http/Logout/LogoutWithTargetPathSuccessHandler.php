<?php
/**
 * ログアウト後、次のログイン先を指定する
 */

namespace MiscBundle\Extend\Symfony\Component\Security\Http\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
// use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Default logout success handler will redirect users to a configured path.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Alexander <iam.asm89@gmail.com>
 */
class LogoutWithTargetPathSuccessHandler extends DefaultLogoutSuccessHandler
{
  /**
   * @param HttpUtils $httpUtils
   * @param string $targetUrl
   * @param TokenStorageInterface $tokenStorage
   */
  public function __construct(HttpUtils $httpUtils, $targetUrl = '/')
  {
    parent::__construct($httpUtils, $targetUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function onLogoutSuccess(Request $request)
  {
    // 再ログイン後のURL指定があれば、そちらへリダイレクトするレスポンスを返す。
    // （ログアウト後にリダイレクトして認証例外となりその時のrequestUrl が _security.{providerKey}.target_path へ書き込まれる）
    $targetUrl = $this->targetUrl;
    if ($requestedTarget = $request->get('target_path')) {
      $targetUrl = $requestedTarget;
    }

    return $this->httpUtils->createRedirectResponse($request, $targetUrl);
  }
}
