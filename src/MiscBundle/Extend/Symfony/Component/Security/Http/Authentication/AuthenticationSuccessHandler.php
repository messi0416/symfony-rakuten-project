<?php

namespace MiscBundle\Extend\Symfony\Component\Security\Http\Authentication;

use Doctrine\ORM\EntityNotFoundException;
use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * デフォルトのログイン成功時ハンドラを拡張
 * https://symfony.com/doc/2.8/reference/configuration/security.html#success-handler
 */
class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
  /** @var Container */
  protected $container;

  /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
  protected $doctrine;

  public function __construct(HttpUtils $httpUtils, array $options) {
    parent::__construct($httpUtils, $options);
  }

  /**
   * @param Container $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }

  /**
   * @return Container $container
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * @param string
   * @return \Doctrine\Bundle\DoctrineBundle\Registry
   */
  protected function getDoctrine()
  {
    if (!isset($this->doctrine)) {
      $this->doctrine = $this->getContainer()->get('doctrine');
    }
    return $this->doctrine;
  }

  /**
   * ログイン成功時処理
   * ログイン失敗回数と最終ログイン日時を更新
   * @param Request $request
   * @param TokenInterface $token
   * @return RedirectResponse
   * @throws \Exception
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token)
  {
    $account = $token->getUser();
    if (! $account instanceof SymfonyUserInterface) {
      throw new AccessDeniedException();
    }

    $em = $this->getDoctrine()->getManager('main');
    // フォレストスタッフ
    if ($account->isForestStaff()) {
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
      $user = $repo->find($account->getId());
      // ログイン出来ているのでないはずがないが一応
      if ($user === null) {
        throw new EntityNotFoundException();
      }
      $user->setLoginErrorCount(0);
      $user->setLastLoginDatetime(new \DateTime());
    }
    // 発注依頼先
    elseif ($account->isClient()) {
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserClient');
      $user = $repo->find($account->getId());
      if ($user === null) {
        throw new EntityNotFoundException();
      }
      $user->setLastLoginDatetime(new \DateTime());
    }
    $em->flush();

    return parent::onAuthenticationSuccess($request, $token);
  }
}
