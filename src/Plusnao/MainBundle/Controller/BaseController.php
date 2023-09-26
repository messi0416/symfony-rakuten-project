<?php

namespace Plusnao\MainBundle\Controller;

use MiscBundle\Entity\SymfonyUserClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BaseController extends Controller
{
  /**
   * ログインアカウント ユーザ取得
   * @return SymfonyUserClient
   */
  protected function getLoginUser()
  {
    // ログインアカウント
    /** @var TokenInterface $token */
    $token = $this->get('security.token_storage')->getToken();
    $account = $token ? $token->getUser() : null;
    return is_object($account) ? $account : null;
  }

  /**
   * Flash セット
   * @param string $type
   * @param mixed $messages
   */
  protected function setFlash($type, $messages)
  {
    /** @var FlashBag $session */
    // $session = $this->get('session')->getFlashBag();

    $this->get('session')->getFlashBag()->set($type, $messages);
  }

}
