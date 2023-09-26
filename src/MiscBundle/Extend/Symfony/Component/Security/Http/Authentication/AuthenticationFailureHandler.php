<?php

namespace MiscBundle\Extend\Symfony\Component\Security\Http\Authentication;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\SymfonyUsersLockLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * デフォルトのログイン失敗時ハンドラを拡張
 * https://symfony.com/doc/2.8/reference/configuration/security.html#success-handler
 */
class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
  /** @var Container */
  protected $container;

  /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
  protected $doctrine;

  public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, array $options, LoggerInterface $logger = null) {
    parent::__construct($httpKernel, $httpUtils, $options, $logger);
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
   * ログイン失敗時処理
   * 一定回数以上パスワードログインに失敗すればアカウントを停止する
   * @param Request $request
   * @param AuthenticationException $exception
   * @return RedirectResponse
   * @throws \Exception
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    
    // フォレストユーザーのみ
    $webSystemFQDN = $this->getContainer()->getParameter('host_main');
    if ($webSystemFQDN === $request->getHost()) {
      // パスワードが異なる以外のエラーは処理しない
      if (! ($exception instanceof BadCredentialsException)) {
        return parent::onAuthenticationFailure($request, $exception);
      }

      // ユーザー名はユニーク制約があるので問題ない...はず
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
      $user = $repo->findOneBy(['username' => $exception->getToken()->getUsername()]);
      if ($user === null) {
        return parent::onAuthenticationFailure($request, $exception);
      }

      // エラー回数記録 規定回数以上であればアカウントロック
      $errorCount = $user->getLoginErrorCount();
      $errorCount++;
      $user->setLoginErrorCount($errorCount);
      $request->getSession()->set('errorCount', $errorCount);
      $em = $this->getDoctrine()->getManager('main');
      if (SymfonyUsers::LIMIT_ERROR_TIME <= $errorCount) {
        $user->setIsLocked(SymfonyUsers::IS_LOCKED);
        $user->setLockedDatetime(new \DateTime());
        
        // ロックした時の情報を保存 エラーが出てたらロックすること優先。エラーはログを吐いて握りつぶす
        try {
          /** @var SymfonyUsersLockLog $lockLog */
          $lockLog = new SymfonyUsersLockLog();
          $lockLog->setAccountId($user->getId());
          $lockLog->setAccessIp(ip2long($request->getClientIp()));
          $lockLog->setAgent($request->headers->get('User-Agent'));
          $lockLog->setLockedDatetime(new \DateTime());
          $em->persist($lockLog);
        } catch (Exception $e) {
          $logger->error("アカウントロック時にエラー発生： user:" . $user->getId() . ':' . $e->getMessage() . $e->getTraceAsString());
        }
      }
      $em->flush();
    }

    return parent::onAuthenticationFailure($request, $exception);
  }
}
