<?php

namespace Plusnao\YahooAgentBundle\EventListener;

use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
use MiscBundle\Entity\Repository\SymfonyUserYahooAgentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class BeforeFilterControllerEventListener
{
  /**
   * @param FilterControllerEvent $event
   */
  public function onKernelController(FilterControllerEvent $event)
  {
    $c = $event->getController();

    if (!is_array($c)) {
      return;
    }
    /** @var Controller $controller */
    $controller = $c[0];

    // YahooAgentBundle のコントローラのみの挙動
    if ($controller instanceof \Plusnao\YahooAgentBundle\Controller\BaseController) {

      /** @var \Plusnao\YahooAgentBundle\Controller\BaseController $controller */

      // 未ログインならここには来ないはずだが、一応スルー
      /** @var SymfonyUserInterface $account */
      $account = $controller->get('security.token_storage')->getToken()->getUser();
      if (!$account || ! ($account instanceof SymfonyUserInterface)) {
        return;
      }

      // 店舗コードが取得できなければエラー
      $request = $event->getRequest();
      $shopCode = $request->get('shopCode');
      if (!$shopCode) {
        throw new \RuntimeException('no shop code!!');
      }

      // 店舗コードに該当する設定がなければエラー
      /** @var SymfonyUserYahooAgentRepository $repo */
      $repo = $controller->getDoctrine()->getRepository('MiscBundle:SymfonyUserYahooAgent');
      $shopAccounts = $repo->getActiveAccountByShopCode($shopCode);
      if (!$shopAccounts) {
        throw new \RuntimeException('no shop!!');
      }

      // ADMIN権限有無
      $shopAccount = null;
      /** @var AuthorizationChecker $auth */
      $auth = $controller->get('security.authorization_checker');
      if ($auth->isGranted('ROLE_YAHOO_AGENT_ADMIN')) {
        // 全てのSHOPでOK
        $shopAccount = reset($shopAccounts);
      } else {
        $matched = false;
        foreach($shopAccounts as $shopAccount) {
          if ($shopAccount->getId() === $account->getId()) {
            $matched = true;
            break;
          }
        }
        if (!$matched) {
          throw new \RuntimeException('invalid account for this shop!! [ ' . $shopCode . '][' . $account->getId() . ']');
        }
      }

      $controller->setShopAccount($shopAccount);
    }
  }
}
