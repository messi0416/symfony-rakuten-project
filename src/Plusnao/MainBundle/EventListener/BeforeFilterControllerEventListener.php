<?php

namespace Plusnao\MainBundle\EventListener;

use AppBundle\Security\User\SymfonyUserClientProvider;
use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
// use MiscBundle\Entity\Repository\SymfonyUserYahooAgentRepository;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
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

    // 注残一覧 依頼先取得処理
    if ($controller instanceof \Plusnao\MainBundle\Controller\BaseAgentController) {

      /** @var \Plusnao\MainBundle\Controller\BaseAgentController $controller */
      // 未ログインならここには来ないはずだが、一応スルー
      $token = $controller->get('security.token_storage')->getToken();
      /** @var SymfonyUserInterface $account */
      $account = $token ? $token->getUser() : null;
      if (!$account || ! ($account instanceof SymfonyUserInterface)) {
        return;
      }

      // 依頼先コードが取得できれば取得
      $request = $event->getRequest();
      $agentLoginName = $request->cookies->get('agentName');

      // URLでの指定があれば上書き
      if ($request->get('agentName')) {
        $agentLoginName = $request->get('agentName');
      }

      $agent = null;
      if ($agentLoginName) {
        /** @var BaseRepository $repo */
        $repo = $controller->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
        /** @var PurchasingAgent $agent */
        $agent = $repo->findOneBy([ 'login_name' => $agentLoginName ]);
      }

      // 該当する依頼先設定があれば、ログインアカウントとの整合性チェック
      if ($agent) {
        // ForestStaff であれば、そのまま取得できたものをセット
        if ($account->isForestStaff()) {
          $controller->setAgent($agent);

        } else {
          if (method_exists($account, 'getAgentId')) {
            if ($agent->getId() === $account->getAgentId()) {
              $controller->setAgent($agent);
            } else {

              // 該当エージェントで同名のユーザがいるのであれば、そちらで問題なし。
              /** @var SymfonyUserClientProvider $userClientProvider */
              $userClientProvider = $controller->get('app.symfony_user_client_provider');
              $user = $userClientProvider->loadUserByUsername($account->getUsername(), $agent->getLoginName());

              if ($user) {
                $controller->setAgent($agent);
              }
            }
          }
        }
      }
    }
  }
}
