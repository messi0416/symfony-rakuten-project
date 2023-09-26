<?php

namespace AppBundle\Controller;

use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BaseController extends Controller
{
  /**
   * ログインアカウント ユーザ取得
   * @return SymfonyUsers
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
   * @return \BCC\ResqueBundle\Resque
   */
  protected function getResque()
  {
    return $this->get('bcc_resque.resque');
  }

  /**
   * キュー名・コマンド名でJobを一括取得
   * @param $queueName
   * @param $commandName
   * @return \BatchBundle\Job\MainJob[]
   */
  protected function findQueuesByCommandName($queueName, $commandName)
  {
    $result = [];

    $queue = $this->getResque()->getQueue($queueName);
    if ($queue) {
      /** @var MainJob $job */
      foreach($queue->getJobs() as $job) {
        if ($job->getCommand() == $commandName) {
          $result[] = $job;
        }
      }
    }

    return $result;
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

  /**
   * Flash クリア
   */
  protected function clearFlash()
  {
    /** @var FlashBag $session */
    // $session = $this->get('session')->getFlashBag();

    $this->get('session')->getFlashBag()->clear();
  }


}
