<?php

namespace Plusnao\YahooAgentBundle\Controller;

use MiscBundle\Entity\SymfonyUserClient;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class BaseController extends Controller
{
  /** @var SymfonyUserYahooAgent */
  protected $shopAccount;

  /**
   * ログインアカウント ユーザ取得
   * @return SymfonyUserClient
   */
  protected function getLoginUser()
  {
    // ログインアカウント
    $account = $this->get('security.token_storage')->getToken()->getUser();
    return $account;
  }

  /**
   * 操作対象SHOPアカウント
   * @return SymfonyUserYahooAgent|null
   */
  public function getShopAccount()
  {
    return $this->shopAccount;
  }

  /**
   * 操作対象SHOPアカウント
   * BeforeFilterControllerEventListener により自動セット
   * @param SymfonyUserYahooAgent
   */
  public function setShopAccount($shopAccount)
  {
    $this->shopAccount = $shopAccount;
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
